<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * @package     Plumrocket_Newsletterpopup
 * @copyright   Copyright (c) 2017 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */


class Plumrocket_Newsletterpopup_Helper_DataEncoded extends Mage_Core_Helper_Abstract
{
	protected $_popup = NULL;
	
	public function getCurrentPopup()
	{
		if (is_null($this->_popup)) {
			if (! Mage::helper('newsletterpopup')->moduleEnabled()) {
				$item = Mage::getModel('newsletterpopup/popup');
			} elseif ($id = (int)Mage::app()->getRequest()->getParam('id')) {
				$item = Mage::getModel('newsletterpopup/popup')->load($id);
			/*} elseif ($this->isSubscribed()) {
				// If customer was subscribed.
				$item = Mage::getModel('newsletterpopup/popup');*/
			} else {
				$area = Mage::app()->getRequest()->getParam('area');
				// we disable popups on customer account pages
				if ($area == Plumrocket_Newsletterpopup_Model_Values_Show::ON_ACCOUNT_PAGES) {
					$item = Mage::getModel('newsletterpopup/popup');
				} else {
					$lockedIds = Mage::helper('newsletterpopup')->getLockedPopupIds();

					// if cookie is global and already locked any popup(s)
					if (Mage::getStoreConfig('newsletterpopup/general/cookies_usage') == Plumrocket_Newsletterpopup_Model_Values_Cookies::_GLOBAL
						&& $lockedIds
					) {
						$item = Mage::getModel('newsletterpopup/popup');
					} else {
						$now = strftime('%Y-%m-%d %H:%M:%S', time());
						$order_by = array('display_popup ASC');

						$popups = Mage::getModel('newsletterpopup/popup')
							->getCollection()
							->addTemplateData()
							// `status` int(1) NOT NULL DEFAULT '0',
							->addFieldToFilter('status', Plumrocket_Newsletterpopup_Model_Values_Status::STATUS_ENABLED)
							->addFieldToFilter('display_popup', array('neq' => Plumrocket_Newsletterpopup_Model_Values_Method::MANUALLY))
							// `store_id` varchar(32) NOT NULL DEFAULT '0',
							->addStoreFilter(Mage::app()->getStore());
							// `cookie_time_frame` int(11) NOT NULL DEFAULT '7',
							// Filtered by COOKIE and check out in _toHTML method of block.

						// `start_date` datetime DEFAULT NULL,
						$popups->getSelect()->where("(`start_date` <= '$now') OR (`start_date` IS NULL)");
						// `end_date` datetime DEFAULT NULL,
						$popups->getSelect()->where("(`end_date` >= '$now') OR (`end_date` IS NULL)");

						// check cookies id
						if (Mage::getStoreConfig('newsletterpopup/general/cookies_usage') == Plumrocket_Newsletterpopup_Model_Values_Cookies::_SEPARATE
							&& $lockedIds
						) {
							$popups->getSelect()->where('`main_table`.`entity_id` NOT IN (?)', $lockedIds);
						}

						if ($order_by) {
							$popups->getSelect()->order($order_by);
						}

						$space = $this->_getSpace();

						//echo $popups->getSelect();
						// $item = $popups->getFirstItem();
						$item = Mage::getModel('newsletterpopup/popup');
						foreach ($popups as $key => $_popup) {
							// If customer is subscribed and have not special rule, use default logic: don't show popup.
							if(false === strpos($_popup->getData('conditions_serialized'), 'newsleter_subscribed') && $this->isSubscribed($space->getCustomer())) {
								continue;
							}

							if($_popup->validate($space)) {
								$item = $_popup;
								break;
							}
						}
					}
				}
			}
			// load coupon code
			$item = Mage::helper('newsletterpopup')->assignCoupon($item);
			$this->_popup = $item;
		}

		return $this->_popup;
	}

	protected function _getSpace()
	{
		$space = new Varien_Object();
		$request = Mage::app()->getRequest();

		// General.
        $space->setData('current_device', $this->_getDevice());
        $space->setData('current_page_type', $request->getParam('area'));
        $space->setData('current_cms_page', $request->getParam('cmsPage'));
        $space->setData('current_page_url', $request->getParam('referer'));
        $space->setData('category_ids', $request->getParam('categoryId'));
        if (!$prevPopups = Mage::getSingleton('core/session')->getData('newsletterpopup_prev_popups')) {
            $prevPopups = array();
        }
        $space->setData('prev_popups_count', count($prevPopups));

        // Customer.
        $_customer = null;
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $_customer = Mage::getSingleton('customer/session')->getCustomer();
        } elseif ($visitorId = Mage::helper('newsletterpopup')->visitorId()) {
            $_customer = Mage::getSingleton('customer/customer')->load($visitorId);
        }

        if($_customer) {
            $data = $_customer->getData();
            if(!empty($data['dob'])) {
                $data['age'] = floor((time() - strtotime($data['dob'])) / 31556926);
            }
            $data['newsleter_subscribed'] = $this->isSubscribed($_customer)? 1 : 0;
            $data['customer'] = $_customer;
            $space->addData($data);
        }else{
            $space->setData('newsleter_subscribed', 0);
            $space->setData('group_id', 0);
        }

        // Cart.
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        $cart = array(
            'quote'             => $quote,
            'cart_base_subtotal'=> round($quote->getGrandTotal(), 2),
            'cart_total_qty'    => (int)Mage::helper('checkout/cart')->getSummaryCount(),
            'cart_total_items'  => (int)$quote->getItemsCount()
        );
        $space->addData($cart);

        // Product.
        if($request->getParam('productId') && $_product = Mage::getModel('catalog/product')->load($request->getParam('productId'))) {
            $space->setData('product', $_product);
            $space->setData('category_ids', 0);
        }

        return $space;
    }

    protected function _getDevice()
    {
        $width = (int)Mage::app()->getRequest()->getParam('w');
        $result = Plumrocket_Newsletterpopup_Model_Values_Devices::ALL;

        if ($width) {
            $_conf = Mage::getStoreConfig('newsletterpopup/size');
            $conf = array();

            foreach ($_conf as $device => $hash) {
                $a = explode(',', $hash);
                if (isset($a[1])) {
                    $sum = $width - (int)$a[1];
                    $condition = $a[0];

                    if (($sum === 0 && ($condition == 'el' || $condition == 'eg'))
                        || ($sum > 0 && ($condition == 'eg' || $condition == 'g'))
                        || ($sum < 0 && ($condition == 'el' || $condition == 'l'))
                    ) {
                        $conf[ abs($sum) ] = $device;
                    }
                }
            }
            ksort($conf);
            if ($conf) {
                $result = reset($conf);
            }
        }

        return $result;
    }

    public function getLockedPopupIds()
    {
        $cookies = Mage::getModel('core/cookie')->get();
        $ids = array();

        foreach ($cookies as $name => $val) {
            if (preg_match('/^newsletterpopup_disable_popup_([0-9]+)$/', $name, $mtc)) {
                if (isset($mtc[1])) {
                    $ids[] = (int)$mtc[1];
                }
            }
        }
        return $ids;
    }

    public function isSubscribed($customer = null)
    {
        $status = false;
        if (is_null($customer)) {
            $session = Mage::getSingleton('customer/session');
            if ($session->isLoggedIn()) {
                $customer = $session->getCustomer();
            }
        }

        if ($customer instanceof Varien_Object && $email = $customer->getEmail()) {
            $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
            $status = $subscriber->isSubscribed();
        }

        return $status;
    }
}