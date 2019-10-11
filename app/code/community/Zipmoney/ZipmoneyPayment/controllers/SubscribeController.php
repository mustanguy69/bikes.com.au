<?php

class Zipmoney_ZipmoneyPayment_SubscribeController extends Mage_Core_Controller_Front_Action {

    /**
     * @var Zipmoney_ZipmoneyPayment_Helper_Logging
     */
    protected $_logging = null;

    protected function _construct()
    {
        parent::_construct();
        $this->_logging = $this->_getLogHandler();
    }

    /**
     * @return Zipmoney_ZipmoneyPayment_Helper_Logging
     */
    protected function _getLogHandler()
    {
        if (!$this->_logging) {
            $this->_logging = Mage::helper('zipmoneypayment/logging');
        }
        return $this->_logging;
    }

    function ipnValid($mer_id,$mer_key) {
        $merchant_id = trim(Mage::getStoreConfig('payment/zipmoneypayment/id'));
        $merchant_key = trim(Mage::getStoreConfig('payment/zipmoneypayment/key'));
        return ($mer_id==$merchant_id&&$mer_key==$merchant_key);
    }

    public function indexAction() {
        $response = file_get_contents("php://input");
        Mage::helper('zipmoneypayment')->writelog("Data Received Subscribe:", $response);
        if ($response != '') {
            $json_variable = json_decode($response);
            if ($json_variable->Type == 'SubscriptionConfirmation') {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $json_variable->SubscribeURL);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $output = curl_exec($ch);
                curl_close($ch);
            } else if ($json_variable->Type == 'Notification') {
                try {
                    $oHelper = Mage::helper('zipmoneypayment');
                    $oOrderHelper = Mage::helper('zipmoneypayment/order');
                    if (!isset($json_variable->Message)) {
                        throw Mage::exception('Zipmoney_ZipmoneyPayment', $oHelper->__('Notification Message is not set.'));
                    }
                    $variable = json_decode($json_variable->Message);
                    if (!isset($variable->response)) {
                        throw Mage::exception('Zipmoney_ZipmoneyPayment', $oHelper->__('Notification response is not set.'));
                    }
                    $response = $variable->response;
                    Mage::helper('zipmoneypayment')->writelog("Data Received Subscribe Notification:", json_encode($response));

                    $vNotificationType = isset($variable->type) ? $variable->type : '';
                    $orderid = isset($response->order_id) ? $response->order_id : null;
                    $txnId = isset($response->txn_id) ? $response->txn_id : null;
                    $comment = isset($response->error_message) ? $response->error_message : '';
                    $vReference = isset($response->reference) ? $response->reference : null;
                    $vMerchantId = isset($response->merchant_id) ? $response->merchant_id : null;
                    $vMerchantKey = isset($response->merchant_key) ? $response->merchant_key : null;

                    /**
                     * set current merchant_id, and scope (store_id)
                     */
                    Mage::getSingleton('zipmoneypayment/storeScope')->setMerchantId($vMerchantId);
                    Mage::getSingleton('zipmoneypayment/storeScope')->setMerchantKey($vMerchantKey);
                    if ($orderid) {
                        $oOrder = Mage::getModel('sales/order')->loadByIncrementId($orderid);
                        if ($oOrder && $oOrder->getId()) {
                            Mage::getSingleton('zipmoneypayment/storeScope')->setStoreId($oOrder->getStoreId());
                        }
                    }

                    if(!$this->ipnValid($vMerchantId, $vMerchantKey)) {
                        $this->_logging->writeLog($oHelper->__('Incorrect API keys.'), Zend_log::WARN);
                        return;
                    }
                    /**
                     * There's no orderid for 'configuration_updated' notification
                     */
                    if ($vNotificationType != 'configuration_updated') {
                        if (!$this->checkIPN($orderid, $vNotificationType)) {
                            $this->_logging->writeLog($oHelper->__('Invalid IPN. Order id: %s, notification: %s', $orderid, $vNotificationType), Zend_log::INFO);
                            return;
                        }
                    }
                    $this->_logging->writeLog($oHelper->__('Received zipMoney notification (' . $vNotificationType . ').'), Zend_log::DEBUG);
                    switch ($vNotificationType) {
                        case 'authorise_succeeded':
                            $bIsOrderExpress = $this->_checkOrderIsExpress($orderid);

                            if (!$this->_checkTxnIdAvailable($txnId)) {
                                $this->_logging->writeLog($oHelper->__('Duplicate notification.'), Zend_Log::INFO);
                                $this->_logging->writeLog($oHelper->__('Ignoring duplicate authorise_succeeded (txn_id: ' . $txnId . ')notification.'), Zend_Log::DEBUG);
                                return;
                            }

                            if ($bIsOrderExpress) {
                                $oOrderHelper->authorise($orderid, $txnId);
                                $this->_logging->writeLog($oHelper->__('Successful to authorise.'), Zend_log::INFO);
                            } else {
                                $this->authed($orderid, $txnId);
                            }
                            break;
                        case 'authorise_failed':
                            $this->add_order_comment($orderid, 'zipMoney Authorised failed: ' . $comment);
                            break;
                        case 'authorise_under_review':
                            $this->auth_review($orderid, $txnId);
                            break;
                        case 'cancel_succeeded':
                            if ($vReference) {
                                $this->_logging->writeLog($oHelper->__('Cancelling order ' . $orderid . ' is successful in zipMoney.'), Zend_log::NOTICE);
                            } else {
                                $this->cancelled($orderid, $txnId);
                            }
                            break;
                        case 'cancel_failed':
                            $this->add_order_comment($orderid, 'zipMoney Canceled failed: ' . $comment);
                            break;
                        case 'charge_succeeded':
                            $bIsOrderExpress = $this->_checkOrderIsExpress($orderid);

                            if (!$this->_checkTxnIdAvailable($txnId)) {
                                $this->_logging->writeLog($oHelper->__('Duplicate notification.'), Zend_Log::INFO);
                                $this->_logging->writeLog($oHelper->__('Ignoring duplicate charge_succeeded (txn_id: ' . $txnId . ')notification.'), Zend_Log::DEBUG);
                                return;
                            }

                            if ($bIsOrderExpress) {
                                $oOrderHelper->authoriseAndCapture($orderid, $txnId);
                                $this->_logging->writeLog($oHelper->__('Successful to authorise and capture.'), Zend_log::INFO);
                            } else {
                                $this->auth_capture($orderid, $txnId);
                            }
                            break;
                        case 'charge_failed':
                            $this->add_order_comment($orderid, 'zipMoney Charge failed: ' . $comment);
                            break;
                        case 'capture_succeeded':
                            $bIsOrderExpress = $this->_checkOrderIsExpress($orderid);

                            if (!$this->_checkTxnIdAvailable($txnId)) {
                                $this->_logging->writeLog($oHelper->__('Duplicate notification.'), Zend_Log::INFO);
                                $this->_logging->writeLog($oHelper->__('Ignoring duplicate charge_succeeded (txn_id: ' . $txnId . ')notification.'), Zend_Log::DEBUG);
                                return;
                            }
                            
                            if ($bIsOrderExpress) {
                                $oOrderHelper->capture($orderid, $txnId);
                                $this->_logging->writeLog($oHelper->__('Successful to capture.'), Zend_log::INFO);
                            } else {
                                $this->captured($orderid, $txnId);
                            }
                            break;
                        case 'capture_failed':
                            $order = Mage::getModel('sales/order')->loadByIncrementId($orderid);
                            $order->setStatus('zip_authorised');
                            $order->save();
                            $this->add_order_comment($orderid, 'zipMoney Captured failed: ' . $comment);
                            break;
                        case 'refund_succeeded':
                            if ($vReference) {
                                if ($this->_checkRefundReferenceAvailable($vReference)) {
                                    // ignore
                                    $this->_logging->writeLog($oHelper->__('Refund for order ' . $orderid . ' is successful in zipMoney.'), Zend_log::NOTICE);
                                } else {
                                    // add to Magento notification
                                    $vMessage = $oHelper->__('Refund reference ' . $vReference . ' for order ' . $orderid . ' does not exist.');
                                    $this->_logging->writeLog($vMessage, Zend_log::ERR);
                                    $this->add_order_comment($orderid, $vMessage);
                                }
                            } else {
                                // Refund request from zipMoney
                                if (!$this->_checkTxnIdAvailable($txnId, true)) {
                                    $this->_logging->writeLog($oHelper->__('Duplicate notification.'), Zend_Log::INFO);
                                    $this->_logging->writeLog($oHelper->__('Ignoring duplicate refund_succeeded (txn_id: ' . $txnId . ')notification.'), Zend_Log::DEBUG);
                                    return;
                                }
                                $this->refund(json_encode($response), $orderid, $txnId);
                            }
                            break;
                        case 'refund_failed':
                            $vMessage = $oHelper->__('Refund for order ' . $orderid . ' failed in zipMoney. ') . $comment;
                            $this->_logging->writeLog($vMessage, Zend_log::WARN);
                            $this->add_order_comment($orderid, $vMessage);
                            break;
                        case 'authorise_declined':
                        case 'order_cancelled':
                            $this->order_cancelled($orderid, $txnId, $response->status);
                            break;
                        case 'configuration_updated':
                            try {
                                Mage::helper('zipmoneypayment')->requestConfigAndUpdate();
                            } catch (Exception $e) {
                                // reset api key hash
                                $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_HASH;
                                Mage::getModel('zipmoneypayment/config')->saveConfigByMatchedScopes($vPath, '', $vMerchantId);
                                throw $e;
                            }
                            break;
                        default:
                            break;
                    }
                } catch (Zipmoney_ZipmoneyPayment_Exception $e) {
                    $this->_logging->writeLog($e->getMessage(), Zend_Log::ERR);
                    $this->_logging->writeLog($response, Zend_Log::ERR);
                    $this->_logging->writeLog($e->getTraceAsString(), Zend_Log::ERR);
                    $iCode = $e->getErrorCode();
                    $vMessage = $e->getMessage();
                    $this->_setResponse($response->merchant_id, $response->merchant_key, $vMessage, $iCode);
                } catch (Exception $e) {
                    $this->_logging->writeLog($e->getMessage(), Zend_Log::ERR);
                    $this->_logging->writeLog($response, Zend_Log::ERR);
                    $this->_logging->writeLog($e->getTraceAsString(), Zend_Log::ERR);
                    $vMessage = $e->getMessage();
                    $this->_setResponse($response->merchant_id, $response->merchant_key, $vMessage);
                }
            }
        }
    }

    protected function _checkRefundReferenceAvailable($vReference)
    {
        $collection = Mage::getModel("sales/order_creditmemo")->getCollection()->addAttributeToFilter("refund_reference", $vReference);
        if ($collection->count() > 0) {
            return true;
        }
        return false;
    }

    protected function _checkTxnIdAvailable($vTxnId, $bRefund = false)
    {
        if ($bRefund) {
            $collection = Mage::getModel("sales/order_creditmemo")->getCollection()->addAttributeToFilter("zipmoney_txn_id", $vTxnId);
            if ($collection->count() > 0) {
                return false;
            }
        } else {
            $collection = Mage::getModel('sales/order_payment_transaction')->getCollection()->addAttributeToFilter("txn_id", $vTxnId);
            if ($collection->count() > 0) {
                foreach ($collection as $oItem) {
                    $oPayment = Mage::getModel('sales/order_payment')->load($oItem->getPaymentId());
                    if ($oPayment && $oPayment->getId() != 0) {
                        if (Zipmoney_ZipmoneyPayment_Helper_Data::PAYMENT_METHOD_CODE == $oPayment->getMethod()) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    protected function _checkOrderIsExpress($vOrderIncId)
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $bIsOrderExpress = false;
        if ($vOrderIncId) {
            $oOrderHelper = Mage::helper('zipmoneypayment/order');
            $bIsOrderExpress = $oOrderHelper->isOrderExpress($vOrderIncId);
            $vOrderTypeText = $bIsOrderExpress ? 'Express' : 'Standard';
            $this->_logging->writeLog($oHelper->__('Order (' . $vOrderIncId . ') is ' . $vOrderTypeText . '.'), Zend_log::DEBUG);
        } else {
            $this->_logging->writeLog($oHelper->__('Order id is empty or null.'), Zend_log::WARN);
        }
        return $bIsOrderExpress;
    }

    protected function _setResponse($vMerchantId, $vMerchantKey, $vMessage = null, $iCode = 0, $bSuccess = false)
    {
        $data = array(
            'merchant_id'       => $vMerchantId,
            'merchant_key'      => $vMerchantKey,
            'success'           => $bSuccess,
            'code'              => $iCode,
            'message'           => $vMessage
        );
        $this->getResponse()->setHttpResponseCode($bSuccess ? Mage_Api2_Model_Server::HTTP_OK : Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $jsonData = Mage::helper('core')->jsonEncode($data);
        $this->getResponse()->setBody($jsonData);
    }

    public function checkIPN($orderid, $type) {

        $oHelper = Mage::helper('zipmoneypayment');
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderid);
        if (!$order || !$order->getId()) {
            $this->_logging->writeLog($oHelper->__('Can not load the order ' . $orderid), Zend_log::WARN);
            return false;
        }
        $order_status = $order->getStatus();
        $this->_logging->writeLog($oHelper->__('Order status: ' . $order_status), Zend_log::DEBUG);
        if ($order_status == 'zip_order_cancelled' || $order_status == 'closed' || $order_status == 'zip_cancelled') {
            return false;
        } elseif ($order_status == 'zip_authorised' && $type == 'authorise_succeeded') {
            return false;
        } elseif ($order_status == 'processing' && $type == 'capture_succeeded') {
            return false;
        } else {
            return true;
        }
    }

    public function add_order_comment($orderid, $comment) {
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderid);
        $order->addStatusHistoryComment($comment);
        $order->save();
    }

    public function order_cancelled($orderid, $txn_id,$r_status) {

        $order = Mage::getModel('sales/order')->loadByIncrementId($orderid);
        $order_sate = $order->getState();
        if ($order_sate != Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_AUTHORIZED && $order_sate != 'processing') {


				$cancelState = Mage_Sales_Model_Order::STATE_CANCELED;
				foreach ($order->getAllItems() as $item) {
					if ($cancelState != Mage_Sales_Model_Order::STATE_PROCESSING && $item->getQtyToRefund()) {
						if ($item->getQtyToShip() > $item->getQtyToCancel()) {
							$cancelState = Mage_Sales_Model_Order::STATE_PROCESSING;
						} else {
							$cancelState = Mage_Sales_Model_Order::STATE_COMPLETE;
						}
					}
					$item->cancel();
				}

				$state = Mage_Sales_Model_Order::STATE_CANCELED;
                if($r_status=="Declined")
                    $status = Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_ORDER_DECLINED;
                else
                    $status = Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_ORDER_CANCELLED;

				$order->setState($state, $status, 'Order status has been updated', true)->save();
				Mage::helper('zipmoneypayment')->writelog("(CANCELLED) order:", $orderid);


        }
    }

    public function auth_review($orderid, $txn_id) {
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderid);
		$order_sate = $order->getState();
        if ($order_sate != Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_AUTHORIZED && $order_sate != 'processing') {
            $status = Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_AUTHORIZED_REVIEW;
            $state = 'payment_review';
            Mage::helper('zipmoneypayment')->writelog("Auth (AUTHORIZED_REVIEW) order:", $orderid);
            $order->setState($state, $status, 'Order status has been updated', true)->save();
        }
    }

    public function auth_capture($orderid, $txn_id) {
        $status = Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_AUTHORIZED;
        $description = "";
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderid);
		$order->sendNewOrderEmail();
        $order->setEmailSent(true);
        $amount = number_format($order->getGrandTotal(), 2, '.', '');
        $payment = $order->getPayment();
        if ($payment->getStatus() != $status) {
            Mage::helper('zipmoneypayment')->writelog("Auth (Subscribe) order:", $orderid);
            $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
            $payment->setStatus($status)
                    ->setTransactionId($txn_id)
                    ->setStatusDescription($description)
                    ->setAdditionalData(serialize(''))
                    ->setIsTransactionClosed('')
                    ->authorize(true, $amount)
                    ->save();
            $order->setPayment($payment);
            /**
             * Set invoice_action_flag to false to avoid auto invoicing by 3rd party
             */
            Mage::helper('zipmoneypayment/logging')->writeLog(Mage::helper('zipmoneypayment')->__('Set order invoice_action_flag to false.'), Zend_Log::INFO);
            $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_INVOICE, false);
            $order->save(); //Save details in order
        }
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderid);
        try {
            if (!$order->canInvoice()) {
                Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
            }
            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
            if (!$invoice->getTotalQty()) {
                Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
            }
            Mage::getSingleton('core/session')->setTxnzipMoney($txn_id);
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
            $transactionSave->save();
            Mage::getSingleton('core/session')->unsTxnzipMoney();
        } catch (Mage_Core_Exception $e) {

        }
    }

    public function refund($vJson, $vOrderId, $vTxnId)
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $oCmHelper = Mage::helper('zipmoneypayment/creditmemo');
        $this->_logging->writeLog($oHelper->__('Calling refund for order (' . $vOrderId . ').'), Zend_log::DEBUG);

        $vComment = $oHelper->__('The order has been refunded (Txn ID: %s).' , $vTxnId);
        $bCustomerNotified = true;
        $bIncludeComment = true;
        $oCmHelper->createCreditMemo($vJson, $vComment, $bCustomerNotified, $bIncludeComment);
    }

    public function cancelled($orderid, $txn_id) {
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderid);
        $state = Mage_Sales_Model_Order::STATE_CANCELED;
        $status = Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_CANCELLED;
        $comment = 'Order status has been updated';
        $isCustomerNotified = true;
        $order->setState($state, $status, $comment, $isCustomerNotified)->save();
    }

    public function captured($orderid, $txn_id) {

        $order = Mage::getModel('sales/order')->loadByIncrementId($orderid);

        $current_status = $order->getStatus();
        Mage::helper('zipmoneypayment')->writelog("Current Status: ", $current_status);

        if ($order->getId() && $current_status == 'zip_authorised') {
            Mage::helper('zipmoneypayment')->writelog("Step capture:", " 3 ");

            try {
                if (!$order->canInvoice()) {
                    Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
                }
                $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                if (!$invoice->getTotalQty()) {
                    Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
                }
                Mage::getSingleton('core/session')->setTxnzipMoney($txn_id);
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

    public function authed($orderid, $txnId) {
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderid);
        $order->sendNewOrderEmail();
        $order->setEmailSent(true);
        $order_sate = $order->getState();
        $status = Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_AUTHORIZED;
        if ($order_sate != 'pending_payment') {
            $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
            $description = "";
            $amount = number_format($order->getGrandTotal(), 2, '.', '');
            $payment = $order->getPayment();
            Mage::helper('zipmoneypayment')->writelog("Auth (Subscribe) order:", $orderid);
            $payment->setTransactionId($txnId)
                    ->setStatusDescription('Payment was successful.')
                    ->setAdditionalData(serialize(''))
                    ->setIsTransactionClosed('')
                    ->authorize(true, $amount)
                    ->save();
            $order->setPayment($payment);
            /**
             * Set invoice_action_flag to false to avoid auto invoicing by 3rd party
             */
            Mage::helper('zipmoneypayment/logging')->writeLog(Mage::helper('zipmoneypayment')->__('Set order invoice_action_flag to false.'), Zend_Log::INFO);
            $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_INVOICE, false);
            $order->setState($state, $status, 'Order status has been updated', true
            )->save();
        }
    }
}
