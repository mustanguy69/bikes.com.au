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
 * @copyright   Copyright (c) 2018 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */


class Plumrocket_Newsletterpopup_Model_Observer
{
    /**
     * Observer for core_collection_abstract_load_before event
     *
     * @param Varien_Event_Observer $observer
     * @return self
     */
    public function coreCollectionAbstractLoadBefore($observer)
    {
        $collection = $observer->getCollection();

        // Check of moduleEnabled must be after checking of collection type
        if ($collection instanceof Mage_SalesRule_Model_Resource_Rule_Collection
            && Mage::helper('newsletterpopup')->moduleEnabled()
        ) {
            $select = $collection->getSelect();
            $conditions = $select->getPart(Zend_Db_Select::WHERE);
            $hasCoupon = false;

            foreach ($conditions as $key => $condition) {
                if (strpos($condition, 'to_date is null') !== false) {
                    unset($conditions[$key]);
                }

                if (strpos($condition, 'rule_coupons') !== false) {
                    $hasCoupon = true;
                }
            }

            if ($hasCoupon) {
                $coreDate = Mage::getModel('core/date');
                $connection = $collection->getConnection();

                $select->reset(Zend_Db_Select::WHERE)
                    ->setPart(Zend_Db_Select::WHERE, $conditions);

                /**
                 * We will use GMT datetime for defining coupon expiration date
                 * This will allow us to more accurately checking
                 * coupon expiration period in depending on
                 * the time zone of current customer
                 */
                $newCondition = $connection->quoteInto(
                    ' rule_coupons.np_expiration_date >= ?',
                    $coreDate->gmtDate()
                );

                /**
                 * This is default magento condition
                 * for validation of coupon expiration date
                 * It will be use when our custom expiration date is null
                 */
                $oldCondition = $connection->quoteInto(
                    '(to_date is null or to_date >= ?)',
                    $coreDate->date('Y-m-d')
                );

                $customCondition = "(rule_coupons.np_expiration_date is not null "
                    . Zend_Db_Select::SQL_AND
                    . $newCondition . ")"
                    . Zend_Db_Select::SQL_OR
                    . "(rule_coupons.np_expiration_date is null "
                    . Zend_Db_Select::SQL_AND
                    . $oldCondition
                    . ")";

                $select->where($customCondition);
            }
        }

        return $this;
    }

    public function adminSystemConfigChangedSection($observer)
    {
        $data = $observer->getEvent()->getData();

        if ($data['section'] == 'newsletterpopup') {
            $groups = Mage::app()->getRequest()->getParam('groups');
            foreach (array('desktop', 'tablet', 'mobile') as $type) {
                $groups['size']['fields'][$type]['value'][1] = preg_replace("/[^0-9]/", "", $groups['size']['fields'][$type]['value'][1]);
            }

            Mage::getSingleton('adminhtml/config_data')
                ->setSection($data['section'])
                ->setWebsite($data['website'])
                ->setStore($data['store'])
                ->setGroups($groups)
                ->save();
            Mage::getConfig()->reinit();
        }
        return $observer;
    }

    public function saveOrder($observer)
    {
        if (!Mage::helper('newsletterpopup')->moduleEnabled()) {
            return $this;
        }

        $order = $observer->getEvent()->getOrder();

        if ($code = $order->getCouponCode()) {
            $email = $order->getCustomerEmail();
            $historyItem = NULL;

            if ($email) {
                $historyItem = Mage::getModel('newsletterpopup/history')
                    ->getCollection()
                    ->addFieldToFilter('customer_email', $email)
                    ->addFieldToFilter('coupon_code', $code)
                    ->getFirstItem();
            }

            if (is_null($historyItem) || ! $historyItem->getId()) {
                $historyItem = Mage::getModel('newsletterpopup/history')->load($code, 'coupon_code');
            }

            if ($historyItem->getId()) {
                if (($order->getState() == Mage_Sales_Model_Order::STATE_CANCELED)
                    || ($order->getState() == Mage_Sales_Model_Order::STATE_HOLDED)
                ) {
                    $this->_save($historyItem, 0, 0, 0);
                } else {
                    $this->_save(
                        $historyItem,
                        $order->getIncrementId(),
                        $order->getId(),
                        $order->getGrandTotal()
                    );
                }
            }
        }
    }

    private function _save($historyItem, $incrementId, $orderId, $grandTotal)
    {
        $boolHistoryGT = $historyItem->getData('grand_total') > 0;
        $boolGT = $grandTotal > 0;

        if ($boolGT != $boolHistoryGT) {
            $popup = Mage::getModel('newsletterpopup/popup')->load( $historyItem->getPopupId() );
            // check if linked popup exists
            if ($popup->getId()) {
                $tr = ($grandTotal)?
                    $popup->getData('total_revenue') + $grandTotal:
                    $popup->getData('total_revenue') - $historyItem->getData('grand_total');
                $addOrdersCount = ($grandTotal)? 1: -1;

                $popup
                    ->setData('orders_count', $popup->getData('orders_count') + $addOrdersCount)
                    ->setData('total_revenue', $tr)
                    ->save();
            }

            $historyItem
                ->setData('increment_id', $incrementId)
                ->setData('order_id', $orderId)
                ->setData('grand_total', $grandTotal)
                ->save();
        }
    }

    public function customerLogin($observer)
    {
        $helper = Mage::helper('newsletterpopup');
        if (!$helper->moduleEnabled()) {
            return $this;
        }

        if ($customer = $observer->getCustomer()) {
            $helper->visitorId($customer->getId());
        }
    }

    /**
     * Observer for core_layout_block_create_after event
     *
     * @param Varien_Event_Observer $observer
     * @return self
     */
    public function coreLayoutBlockCreateAfter($observer)
    {
        $block = $observer->getEvent()->getBlock();

        if ($block instanceof Mage_Adminhtml_Block_Promo_Quote_Edit_Tab_Coupons_Grid
            && Mage::helper('newsletterpopup')->moduleEnabled()
        ) {
            // It will displaying np_expiration_date on Manage Coupons Grid
            $block->addColumnAfter('np_expiration_date', array(
                    'header' => Mage::helper('newsletterpopup')->__('Expires On'),
                    'index' => 'np_expiration_date',
                    'type' => 'datetime',
                    'align' => 'center',
                    'width' => '160'
                ),
                'created_at'
            );
        }

        return $this;
    }
}
