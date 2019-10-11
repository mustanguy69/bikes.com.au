<?php

class Zipmoney_ZipmoneyPayment_StandardController extends Mage_Core_Controller_Front_Action {

    public function redirectAction() {
        $session = Mage::getSingleton('checkout/session');
        $orderIncrementId = $session->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        
        $billingaddress = $order->getBillingAddress();
        
        $shippingaddress = $order->getShippingAddress();

        //Check shipping address for virtual product
        if (!is_object($shippingaddress)) {
            $shippingaddress = $billingaddress;
        }

        $order_items = array();
        $_customer_createat = '';
        $currencyDesc = Mage::app()->getStore()->getCurrentCurrencyCode();
        $totals = number_format($order->getGrandTotal(), 2, '.', '');
        $address = ($billingaddress) ? $billingaddress->getStreet() : null;
        $address1 = ($shippingaddress) ? $shippingaddress->getStreet() : null;
        $ordered_items = $order->getAllItems();
        $_customer_dob = "";
        foreach ($ordered_items as $item) {
        	if($item->getParentItemId()==NULL)
        	{
        	$item_name = $item->getName();
            $item_id = $item->getProductId();
            $_newProduct = Mage::getModel('catalog/product')->load($item_id);
            $item_sku = $_newProduct->getSku();
            $item_desc = strip_tags($_newProduct->getShortDescription());
            $image_url = Mage::helper('catalog/image')->init($_newProduct, 'image');
            $category = "";
            foreach ($_newProduct->getCategoryIds() as $category_id) {
                $category.= "," . Mage::getModel('catalog/category')->load($category_id)->getName();
            }
            $category = ltrim($category, ",");
        	
        	if($item->getProductType()=="configurable")
        	{
        		$sku_ = $item->getSku();
        		Mage::helper('zipmoneypayment')->writelog("SKU:", $sku_);

        		$product_ = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku_);
        		$item_name = 	$product_->getName();		  
        		$item_sku = 	$product_->getSku();
        		$item_id  = 	$product_->getId();
        		$image_url = Mage::helper('catalog/image')->init($product_, 'image');
        		$order_items[] = array(
        			'quantity' => (int) $item->getQtyOrdered(),
        			'name' => "$item_name",
        			'price' => (float) $item->getPrice(),
        			'description' => "$item_desc",
        			'category' => $category,
        			'image_url' => "$image_url",
        			'sku' => "$item_sku",
        			'id' => "$item_id"
        		);
        	}
        	else
        	{
        		 $order_items[] = array(
                'quantity' => (int) $item->getQtyOrdered(),
                'name' => "$item_name",
                'price' => (float) $item->getPrice(),
                'description' => "$item_desc",
                'category' => $category,
                'image_url' => "$image_url",
                'sku' => "$item_sku",
                'id' => "$item_id"
        		);
        	}
        	}
        }
        $i = 0;
        if (Mage::helper('customer')->isLoggedIn()) {
            $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
            $prefix = $customer->getPrefix();
            $gender = $customer->getGender();

            $logCustomer = Mage::getModel('log/customer')->loadByCustomer($customer);
            $lastVisited = $logCustomer->getLoginAtTimestamp();
            $lastVisited = date('Y-m-d H:i:s', $lastVisited);

            $_customer_createat = substr($customer->getCreatedAt(), 0, 10) . " 00:00:00";
            $_customer_dob = $customer->getDob();
            $orderCollection = Mage::getModel('sales/order')->getCollection()
                    ->addFieldToFilter('customer_id', array('eq' => array($customer->getId())))
                    ->addFieldToFilter('status', array('eq' => 'complete'));

            $lifetime_sales_amount = 0;
            $maximum_sale_value = 0;

            $lifetime_sales_refunded_amount = 0;
            foreach ($orderCollection AS $order_row) {
                if ($order_row->getStatus() == 'complete') {
                    $i++;
                    $lifetime_sales_amount+=$order_row->getGrandTotal();
                    if ($order_row->getGrandTotal() > $maximum_sale_value) {
                        $maximum_sale_value = $order_row->getGrandTotal();
                    }
                } else {
                    $lifetime_sales_refunded_amount+=$order_row->getGrandTotal();   // TODO: Should never hit this line. Code cleanup may be needed.
                }
            }
            if ($i > 0)
                $average_sale_value = (float) round($lifetime_sales_amount / $i, 2);
            else
                $average_sale_value = 0;
            $lifetime_sales_refunded_amount = 0;
            $orderCollection_2 = Mage::getModel('sales/order')->getCollection()
                    ->addFieldToFilter('customer_id', array('eq' => array($customer->getId())))
                    ->addFieldToFilter('status', array('eq' => 'closed'));
            foreach ($orderCollection_2 AS $order_row) {

                $lifetime_sales_refunded_amount+=$order_row->getGrandTotal();
            }
        } else {
            $prefix = $order->getCustomerPrefix();
            $gender = $order->getCustomerGender();
            $_customer_dob = $order->getCustomerDob();
            $lastVisited = "";
            $lifetime_sales_amount = 0;
            $maximum_sale_value = 0;
            $average_sale_value = 0;
            $lifetime_sales_refunded_amount = 0;
        }

        //if ($_customer_dob == "")
        //    $_customer_dob = date('Y-m-d H:i:s', time());
        $totals_ = $order->getData();
        $order_ = array(
            'id' => "$orderIncrementId",
            'tax' => (float) $totals_['tax_amount'],
            'shipping_value' => (float) $totals_['shipping_amount'],
            'total' => (float) $totals,
            'detail' => $order_items
        );
        if ($gender == '')
            $gender = 0;

        $consumer = array(
            'gender' => $gender,
            'email' => $order->getCustomerEmail(),
            'first_name' => $order->getCustomerFirstname(),
            'last_name' => $order->getCustomerLastname(),
            'phone' => ($billingaddress) ? $billingaddress->getTelephone() : '',
            'title' => "$prefix",
            'account_created_on' => "$_customer_createat",
            'last_login' => "$lastVisited",
            'lifetime_sales_amount' => (float) $lifetime_sales_amount,
            'average_sale_value' => (float) $average_sale_value,
            'maximum_sale_value' => (float) $maximum_sale_value,
            'lifetime_sales_units' => (int) $i,
            'lifetime_sales_refunded_amount' => (float) $lifetime_sales_refunded_amount
        );
        if ($_customer_dob != "") {
            $consumer['dob'] = "$_customer_dob";
        }
        $address_b = "";
        if (isset($address[0])) {
            $address_b = $address[0];
        } else {
            $address_b.=$address[1];
        }

        $address_b_line1 = "";
        $address_b_line2 = "";
        if (isset($address[0])) {
            $address_b_line1 = $address[0];
        }
        if (isset($address[1])) {
            $address_b_line2 = $address[1];
        }

        if ($billingaddress) {
            $billing_address = array(
                'last_name' => $billingaddress->getLastname(),
                'first_name' => $billingaddress->getFirstname(),
                'line1' => $address_b_line1,
                'line2' => $address_b_line2,
                'country' => $billingaddress->getCountryId(),
                'zip' => $billingaddress->getPostcode(),
                'city' => $billingaddress->getCity(),
                'state' => $billingaddress->getRegion()
            );
        } else {
            $billing_address = array();
        }

        $address_line1 = "";
        $address_line2 = "";
        if (isset($address1[0])) {
            $address_line1 = $address1[0];
        }
        if (isset($address1[1])) {
            $address_line2 = $address1[1];
        }
        if ($shippingaddress) {
            $shipping_address = array(
                'last_name' => $shippingaddress->getLastname(),
                'first_name' => $shippingaddress->getFirstname(),
                'line1' => $address_line1,
                'line2' => $address_line2,
                'country' => $shippingaddress->getCountryId(),
                'zip' => $shippingaddress->getPostcode(),
                'city' => $shippingaddress->getCity(),
                'state' => $shippingaddress->getRegion()
            );
        } else {
            $shipping_address = array();
        }

        if (Mage::helper('zipmoneypayment')->isCaptureMethod()) {
            $charge = true;
        } else {
            $charge = false;
        }

        $data_ = array(
            'charge' => $charge,
            'currency_code' => "$currencyDesc",
            'consumer' => $consumer,
            'billing_address' => $billing_address,
            'shipping_address' => $shipping_address,
            'return_url' => Mage::getUrl('zipmoneypayment/standard/success/'),
            'cancel_url' => Mage::getUrl('zipmoneypayment/standard/cancel/'),
            'notify_url' => Mage::getUrl('zipmoneypayment/standard/success/'),
            'error_url' => Mage::getUrl('zipmoneypayment/standard/error/'),
            'decline_url'   => Mage::getUrl('checkout/cart'),
            'cart_url'      => Mage::getUrl('checkout/cart'),
            'success_url'   => Mage::getUrl('zipmoneypayment/standard/success/'),
            'refer_url'     => Mage::getUrl('zipmoneypayment/standard/success/'),
            'merchant_id' => trim(Mage::getStoreConfig('payment/zipmoneypayment/id')),
            'order_id' => $orderIncrementId,
            'merchant_key' => trim(Mage::getStoreConfig('payment/zipmoneypayment/key')),
            'txn_id' => "",         // todo: duplicate
            'order' => $order_,
            'changedBy' => "",
            'txn_id' => 1,          // todo: duplicate
        	'version' => array(
        		'client'=> Zipmoney_ZipmoneyPayment_Model_Config::MODULE_VERSION,
        		'platform'=> Zipmoney_ZipmoneyPayment_Model_Config::MODULE_PLATFORM
        	)
        );


        $data_post = json_encode($data_);



        Mage::helper('zipmoneypayment')->writelog("Data submit:", $data_post);
        $oApiHelper = Mage::helper('zipmoneypayment/api');
        $gateway_url = $oApiHelper->getApi()->getEndpointUrl(ZipMoney_ApiSettings::API_TYPE_CHECKOUT);
        $error_check = $oApiHelper->getApi()->getEndpointUrl(ZipMoney_ApiSettings::API_TYPE_HEART_BEAT);

        $timeout = 10;
        if(Mage::getStoreConfig('payment/zipmoney_developer_settings/timeout')>0)
        {
            $timeout=Mage::getStoreConfig('payment/zipmoney_developer_settings/timeout');
        }
        
        $httpClientConfig = array('maxredirects' => 0, 'timeout'=> $timeout);
        $client = new Varien_Http_Client($gateway_url, $httpClientConfig);
        $client->setRawData($data_post, 'application/json')->setMethod(Varien_Http_Client::POST);
        $client->setHeaders(array(
            'content-length' => strlen($data_post),
            'content-type' => 'application/json'));

        try {
            $response = $client->request();
        } catch (Exception $e) {
            // Avoid blank page when gets an exception, e.g. timeout
            $message = $e->getMessage();
            Mage::helper('zipmoneypayment')->writelog("Exception when getting redirect url:", $message);
            $this->_redirectUrl(Mage::getUrl('zipmoneypayment/standard/error/'));
        }

        if (!$response->isSuccessful()) {
            Mage::helper('zipmoneypayment/logging')->writeLog(json_encode($response->getBody()), Zend_Log::DEBUG);
            $message = $response->getMessage();
           $this->_redirectUrl(Mage::getUrl('zipmoneypayment/standard/error/') . "?code=$message");
          
        }
        else
        { 
            $result = new Varien_Object();
            $result->addData(json_decode($response->getBody(),true));
        	
        	$comment = 'Resume URL: '.$result->getRedirectUrl();
        	$order->addStatusHistoryComment($comment);
        	$order->save();
            Mage::helper('zipmoneypayment')->writelog("URL ->:", $result->getRedirectUrl());
            Mage::helper('zipmoneypayment')->writelog("Gateway:", $gateway_url);
           
            $this->_redirectUrl($result->getRedirectUrl());
            
        }

    }

    public function approvedAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function errorAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__(Mage::getStoreConfig('payment/zipmoney_handling/service_unavailable_heading')));
        $this->renderLayout();
    }

    public function cancelAction() {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getPaypalStandardQuoteId(true));
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
			$cart = Mage::getSingleton('checkout/cart');
			$cartTruncated = false;
			/* @var $cart Mage_Checkout_Model_Cart */
			$items = $order->getItemsCollection();
			foreach ($items as $item) {
				try {
					$cart->addOrderItem($item);
				} catch (Mage_Core_Exception $e){
					if (Mage::getSingleton('checkout/session')->getUseNotice(true)) {
						Mage::getSingleton('checkout/session')->addNotice($e->getMessage());
					}
					else {
						Mage::getSingleton('checkout/session')->addError($e->getMessage());
					}
					$this->_redirect('*/*/history');
				} catch (Exception $e) {
					Mage::getSingleton('checkout/session')->addException($e,
						Mage::helper('checkout')->__('Cannot add the item to shopping cart.')
					);
					$this->_redirect('checkout/cart');
				}
			}

			$cart->save();
			$order->cancel()->save();
            }
        }
        $this->_redirect('checkout/cart');
    }
	  
    public function updateAction() {
        $this->getResponse()->setRedirect(Mage::getUrl('checkout/onepage/success'));
        $response = $this->getRequest()->getParam('merchantResponse');
        $data = json_decode($response);
        $status = $data->status;
        $txnId = $data->txn_id;

        if ($status == "Authorised") {
            Mage::helper('zipmoneypayment')->writelog("Result-action (Authorize) :", $response);
            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($session->getZoozQuoteId(true));
            if ($session->getLastRealOrderId()) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
                if ($order->getId()) {
                    $amount = number_format($order->getGrandTotal(), 2, '.', '');
                    $payment = $order->getPayment();

                    $payment->setTransactionId($txnId)
                            ->setStatusDescription('Payment was successful.')
                            ->setAdditionalData(serialize(''))
                            ->setIsTransactionClosed('')
                            ->authorize(true, $amount)
                            ->save();

                    $order->setPayment($payment);
                    $setOrderAfterStatus = 'zip_authorised'; // If after status is empty set default status
                    $order->setState(Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_AUTHORIZED, $setOrderAfterStatus, 'Order status has been updated', true
                    )->save();
                }
            }
        } elseif ($status == "Captured") {
            Mage::helper('zipmoneypayment')->writelog("Result-action (Authorize & Captured) :", $response);
            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($session->getZoozQuoteId(true));
            if ($session->getLastRealOrderId()) {
                //authorize
                $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
                if ($order->getId()) {
                    $amount = number_format($order->getGrandTotal(), 2, '.', '');
                    $payment = $order->getPayment();

                    $payment->setStatus(Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_AUTHORIZED)
                            ->setTransactionId($txnId)
                            ->setStatusDescription('Payment was successful.')
                            ->setAdditionalData(serialize(''))
                            ->setIsTransactionClosed('')
                            ->authorize(true, $amount)
                            ->save();

                    $order->setPayment($payment);
                    $order->save();
                }
                $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
                if ($order->getId()) {
                    try {
                        if (!$order->canInvoice()) {
                            Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
                        }
                        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                        if (!$invoice->getTotalQty()) {
                            Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
                        }
                        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                        $invoice->register();
                        $transactionSave = Mage::getModel('core/resource_transaction')
                                ->addObject($invoice)
                                ->addObject($invoice->getOrder());
                        $transactionSave->save();
                    } catch (Mage_Core_Exception $e) {
                        
                    }
                }
            }
        }
        $this->getResponse()->setRedirect(Mage::getUrl('checkout/onepage/success'));
    }
   public function getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }

    public function successAction() {
        $order_id = $this->getRequest()->getParam('order_id');
      
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        
        $session = $this->getOnepage()->getCheckout();
         
        
        if (!$session->getLastSuccessQuoteId()) {
            Mage::getSingleton('core/session')->setZipOrderId($order_id);
            $this->getResponse()->setRedirect(Mage::getUrl('zipmoneypayment/standard/approved'));
        } else {
            $this->getResponse()->setRedirect(Mage::getUrl('checkout/onepage/success'));
        }
       

    }

}

?>