<?php

class Zipmoney_ZipmoneyPayment_Model_Payment extends Mage_Payment_Model_Method_Abstract {

    protected $_code = 'zipmoneypayment';
    protected $_formBlockType = 'zipmoneypayment/standard_form';
    protected $_isInitializeNeeded = true;
    protected $_url = "";
    protected $_dev = false;

    /**
     * Availability options
     */
    protected $_isGateway = false;
    protected $_canOrder = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canFetchTransactionInfo = true;
    protected $_canCreateBillingAgreement = true;
    protected $_canReviewPayment = true;
	
	protected $_client =  Zipmoney_ZipmoneyPayment_Model_Config::MODULE_VERSION;
    protected $_platform = Zipmoney_ZipmoneyPayment_Model_Config::MODULE_PLATFORM;
	
	

    /**
     * zipMoney Payment instance
     *
     * @var Zipmoney_ZipmoneyPayment_Model_Payment
     */

    /**
     * Payment additional information key for payment action
     * @var string
     */
    protected $_isOrderPaymentActionKey = 'is_order_action';

    /**
     * Payment additional information key for number of used authorizations
     * @var string
     */
    protected $_authorizationCountKey = 'authorization_count';

    function __construct() {
        parent::__construct();
        $oApiHelper = Mage::helper('zipmoneypayment/api');
        $this->_url = $oApiHelper->getApi()->getBaseUrl();
    }

    public function initialize($paymentAction, $stateObject) {
        $state = Mage_Sales_Model_Order::STATE_NEW;
        $status = Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_NEW;
        $stateObject->setState($state);
        $stateObject->setStatus($status);
        $stateObject->setIsNotified(false);
    }

    /**
     * Get request for zipMoney capture endpoint
     *
     * @param Varien_Object $payment
     * @return array
     */
    public function getFieldcapture(Varien_Object $payment)
    {
        $order = $payment->getOrder();
        $ordered_items = $order->getAllItems();
        $totals = number_format($order->getGrandTotal(), 2, '.', '');
        foreach ($ordered_items as $item) {
            $order_items[] = array(
                'quantity' => (int) $item->getQtyOrdered(),
                'name' => $item->getName(),
                'price' => $item->getPrice(),
                'description' => $item->getName(),
                'id' => $item->getProductId()
            );
        }
        $totals_ = $order->getData();
        $order_ = array(
            'id' => $order->getIncrementId(),
            'tax' => $totals_['tax_amount'],
            'shipping_value' => $totals_['shipping_amount'],
            'total' => $totals,
            'detail' => $order_items
        );

        // get merchant id/key based on the order's store id
        $iMerchantId = Mage::helper('zipmoneypayment/api')->getMerchantId();
        $vMerchantKey = Mage::helper('zipmoneypayment/api')->getMerchantKey();

        $postData = array(
            'merchant_id' => $iMerchantId,
            'merchant_key' => trim($vMerchantKey),
            'order_id' => $order->getIncrementId(),
            'txn_id' => $payment->getLastTransId(),
            'order' => $order_,
			'version' => array(
				'client'=> $this->_client ,
				'platform'=>  $this->_platform
			)
        );
        return $postData;
    }

    /**
     * Invoice the order (from Magento backend)
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this
     * @throws Exception
     */
    public function capture(Varien_Object $payment, $amount)
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $ipntxn = Mage::getSingleton('core/session')->getTxnzipMoney();
        Mage::getSingleton('core/session')->unsTxnzipMoney();
        Mage::helper('zipmoneypayment/logging')->writeLog(Mage::helper('zipmoneypayment')->__('IPN txn from session: %s', $ipntxn), Zend_Log::DEBUG);

        // set scope to singleton
        if ($payment && $payment->getOrder()) {
            Mage::getSingleton('zipmoneypayment/storeScope')->setStoreId($payment->getOrder()->getStoreId());
        }

        if ($ipntxn != '') {
            // if txn (from zipMoney) is empty, do not call zipMoney capture endpoint
            Mage::helper('zipmoneypayment')->writelog("Step capture:", " 1 ");
            $payment->setTransactionId($ipntxn);
            $payment->setIsTransactionClosed(false);
            $payment->setStatus(Mage_Payment_Model_Method_Abstract::STATUS_APPROVED);
        } else {
            // if txn is not empty, then call zipMoney capture endpoint and invoice the order
            // firstly, set the order status to zip_capture_pending
            $order_id = $payment->getOrder()->getIncrementId();
            $order_temp = Mage::getModel('sales/order')
                    ->loadByIncrementId($order_id);
            $order_temp->setStatus('zip_capture_pending');
            $order_temp->save();

            // call zipMoney capture endpoint
            Mage::getSingleton('core/session')->setTxnflagcapture(1);
            $post_field = $this->getFieldcapture($payment);
            Mage::helper('zipmoneypayment')->writelog("Post-action(capture):", $post_field);
            Mage::helper('zipmoneypayment')->writelog("Post-action(capture):", json_encode($post_field));
            $result = Mage::helper('zipmoneypayment')->setRawCallRequest($post_field, $this->url_capture(), 1);
            Mage::helper('zipmoneypayment')->writelog("Result-action(capture) (convert to object):", json_encode($result));

            // process the result
            $transaction_new = $result->getTxnId();
            $status = $result->getStatus();
            $message = $result->getErrorMessage();
            Mage::helper('zipmoneypayment')->writelog("TXN_ID_CAPTURE:", $transaction_new);
            if ($transaction_new != '' && $transaction_new != 0 && $status == 'Captured' && $message == '') {
                $payment->setTransactionId($transaction_new);
                $payment->setIsTransactionClosed(false);
                $payment->setStatus(Mage_Payment_Model_Method_Abstract::STATUS_APPROVED);
            } else {
                Mage::helper('zipmoneypayment')->writelog("Could not capture Order #orderid:", $order_id);
                $this->throwException('Error in you capture request');
            }
        }
        return $this;
    }

    /**
     * Get request for zipMoney refund endpoint
     *
     * @param Varien_Object $payment
     * @param $amount
     * @return array
     */
    public function getFieldrefund(Varien_Object $payment, $amount)
    {
        $param_memo = Mage::app()->getRequest()->getParam('creditmemo');
        $comment_text = $param_memo['comment_text'];
        if (!$comment_text) {
            $comment_text = 'N/A';
        }

        $oApiHelper = Mage::helper('zipmoneypayment/api');
        $aRawData = array(
            'payment'       => $payment,
            'refund_amount' => $amount,
            'comment'       => $comment_text
        );
        $vEndpoint = ZipMoney_ApiSettings::API_TYPE_ORDER_REFUND;
        $aRequestData = $oApiHelper->prepareDataForZipRequest($vEndpoint, $aRawData);
        return $aRequestData;
    }

    /**
     * Online refund (from Magento backend)
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this
     * @throws Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $oLogging = Mage::helper('zipmoneypayment/logging');
        Mage::getSingleton('core/session')->setTxnflagrefund(1);

        // set scope to singleton
        if ($payment && $payment->getOrder()) {
            Mage::getSingleton('zipmoneypayment/storeScope')->setStoreId($payment->getOrder()->getStoreId());
        }

        // firstly, set order status to zip_refund_pending
        $order_id = $payment->getOrder()->getIncrementId();
        $order_temp = Mage::getModel('sales/order')
                ->loadByIncrementId($order_id);
        $order_temp->setStatus('zip_refund_pending');
        $order_temp->save();

        $oCreditmemo = $payment->getCreditmemo();
        if ($oCreditmemo) {
            $vRefundRef = Mage::helper('zipmoneypayment/creditmemo')->generateRefundReference();
            $oCreditmemo->setRefundReference($vRefundRef);
        }

        // call zipMoney endpoint to refund
        $post_field = $this->getFieldrefund($payment, $amount);
        Mage::helper('zipmoneypayment')->writelog("Post-action(refund):", json_encode($post_field));
        $result = Mage::helper('zipmoneypayment')->setRawCallRequest($post_field, $this->url_refund());
        Mage::helper('zipmoneypayment')->writelog("Result-action(refund) (convert to object):", $result);

        // process the result
        $transaction_new = $result->getTxnId();
        $status = $result->getStatus();
        $message = $result->getErrorMessage();
        Mage::helper('zipmoneypayment')->writelog("TXN_ID_REFUND:", $transaction_new);
        if ($transaction_new != ''
            && $transaction_new != 0
            && ($status == 'Refunded' || $status == 'refunded')
            && $message == '') {
            $oLogging->writeLog($oLogging->__('Success refund response for order ' . $order_id . ' from zipMoney.'),
                Zend_Log::NOTICE);
            $payment->setTransactionId($transaction_new);
            $payment->setIsTransactionClosed(false);
            $payment->setStatus(Mage_Payment_Model_Method_Abstract::STATUS_VOID);
        } else {
            $oLogging->writeLog($oLogging->__('Failure refund response for order ' . $order_id . ' from zipMoney. ' . $message),
                Zend_Log::ERR);
            Mage::helper('zipmoneypayment')->writelog("Could not refund Order #orderid:", $order_id);
            $this->showException('Error in you refund request');
        }
        return $this;
    }

    public function getFieldvoid(Varien_Object $payment) {
        $order = $payment->getOrder();

        $ordered_items = $order->getAllItems();
        $totals = number_format($order->getGrandTotal(), 2, '.', '');
        foreach ($ordered_items as $item) {
            $order_items[] = array(
                'quantity' => (int) $item->getQtyOrdered(),
                'name' => $item->getName(),
                'price' => $item->getPrice(),
                'description' => $item->getName(),
                'id' => $item->getProductId()
            );
        }
        $totals_ = $order->getData();
        $order_ = array(
            'id' => $order->getIncrementId(),
            'tax' => $totals_['tax_amount'],
            'shipping_value' => $totals_['shipping_amount'],
            'total' => $totals,
            'detail' => $order_items
        );
        $postData = array(
            'merchant_id' => trim(Mage::getStoreConfig('payment/zipmoneypayment/id')),
            'order_id' => $order->getIncrementId(),
            'merchant_key' => trim(Mage::getStoreConfig('payment/zipmoneypayment/key')),
            'txn_id' => $payment->getLastTransId(),
            'order' => $order_,
			'version' => array(
				'client'=> $this->_client ,
				'platform'=>  $this->_platform
			)
        );
        return $postData;
    }

    public function void(Varien_Object $payment) {
        if (!$this->canVoid($payment)) {
            Mage::throwException(Mage::helper('payment')->__('Void action is not available.'));
        }
        $post_field = $this->getFieldvoid($payment);
        Mage::helper('zipmoneypayment')->writelog("Post-action(void):", json_encode($post_field));
        $result = Mage::helper('zipmoneypayment')->setRawCallRequest($post_field, $this->url_cancel());
        Mage::helper('zipmoneypayment')->writelog("Result-action(void) (convert to object):", $result);
        $transaction_new = $result->getTxnId();
        $status = $result->getStatus();
        $message = $result->getErrorMessage();
        Mage::helper('zipmoneypayment')->writelog("TXN_ID_VOID:",$transaction_new);
        if ($transaction_new != '' && $transaction_new != 0 && $status == 'Cancelled' && $message == '') {
            $payment->setTransactionId($transaction_new);
            $payment->setIsTransactionClosed(true);
            $payment->setStatus(Mage_Payment_Model_Method_Abstract::STATUS_VOID);
        } else {
            
//Mage::helper('zipmoneypayment')->writelog("Could not void Order #orderid:", $order_id);
            $this->showException('Error in you void request');
        }
        return $this;
    }

    /**
     * Return Order place redirect url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl() {
        /**
         * Will be called when placing order
         * see app/code/core/Mage/Checkout/Model/Type/Onepage.php:808
         * and app/code/core/Mage/Paypal/Model/Standard.php:108
         */
        return Mage::getUrl('zipmoneypayment/standard/redirect', array('_secure' => true));
    }

    public function url_authorise() {
        return rtrim($this->_url, "/") . "/checkout";
    }

    public function url_capture() {

        return rtrim($this->_url, "/") . "/capture";
    }

    public function url_refund() {
        return rtrim($this->_url, "/") . "/refund";
    }

    public function url_cancel() {
        return rtrim($this->_url, "/") . "/cancel";
    }

    /**
     * Return zipMoney Express redirect url if current request is not savePayment (which works for oneStepCheckout)
     * @return null|string
     */
    public function getCheckoutRedirectUrl() {
        /**
         * return redirect url (it works for oneStep checkout)
         * see app/code/core/Mage/Checkout/controllers/OnepageController.php:478
         * and app/code/core/Mage/Paypal/Model/Express.php:428
         */

        $vAction     = Mage::app()->getRequest()->getActionName();
        $vController = Mage::app()->getRequest()->getControllerName();
        $vModule     = Mage::app()->getRequest()->getModuleName();

        if ($vModule == 'checkout' &&  $vController == 'onepage' &&  $vAction == 'savePayment') {
            $vUrl = null;
        } else {
            // oneStep checkout
            if (Mage::helper('zipmoneypayment')->isIframeCheckoutEnabled()) {
                // for zipMoney iframe checkout. Return current url with extra param appended, so that it will refresh current page with the param
                // if the param is present, will popup zipMoney iframe checkout
                
                if ($vAction == 'saveOrder' && 
                    ( Mage::app()->getRequest()->getModuleName() == "magestore_onestepcheckout" || 
                      Mage::app()->getRequest()->getModuleName() == "onestepcheckout" ))
                {
                    $vCurrentUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB,true)."onestepcheckout/index/";

                } else if ($vAction == 'save' && Mage::app()->getRequest()->getModuleName() == "gomage_checkout") 
                {
                    $vCurrentUrl = Mage::helper('core/url')->getHomeUrl()."gomage_checkout/onepage/";
                }  else  {   
                    if(Mage::app()->getRequest()->isAjax())
                        $vCurrentUrl = Mage::helper('checkout/url')->getCheckoutUrl();
                    else
                        $vCurrentUrl = Mage::helper('core/url')->getCurrentUrl();
                }

                $vUrl = $vCurrentUrl;

                
                if (Mage::app()->getRequest()->getParam('zip') != 'iframe') {
                    $aUrl = parse_url($vCurrentUrl);
                    if (!isset($aUrl['zip'])) {
                        $vUrl = $vCurrentUrl . (parse_url($vCurrentUrl, PHP_URL_QUERY) ? '&' : '?') . 'zip=iframe';
                    }
                }

            } else {
                // return zipMoney Express redirect url for oneStep checkout
                $vUrl = Mage::getUrl('zipmoneypayment/Quote/checkoutExpressPayment/');
            }
        }
        
        Mage::helper('zipmoneypayment/logging')->writeLog("Payment Redirect Url:- ".$vUrl);

        return $vUrl;
    }

    /**
     * return icon path 
     *
     * @return string
     */
    public function getCheckoutIconUrl(){
        $zip_product = Mage::getStoreConfig(Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_PRODUCT);
        $prefix      = Zipmoney_ZipmoneyPayment_Model_Config::CHECKOUT_ICON_PREFIX;
        $icon_path   = 'zipmoney/images/'. Zipmoney_ZipmoneyPayment_Model_Config::CHECKOUT_DEFAULT_ICON_NAME . $prefix ;

        if(isset($zip_product) && !empty($zip_product))
          $icon_path = 'zipmoney/images/'. strtolower($zip_product) . $prefix ;

      return $icon_path;
    }
}