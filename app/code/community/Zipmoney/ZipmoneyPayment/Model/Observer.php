<?php
/**
 * @category  Aligent
 * @package   Zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * @var Zipmoney_ZipmoneyPayment_Helper_Logging
     */
    protected $_logging = null;

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

    protected function _exceptionHandling($e, $vFrontMessage = null, $vRearMessage = null, $bAddToSession = true)
    {
        $oLogging = $this->_getLogHandler();
        $iLogLevel = Zend_Log::ERR;
        $vMessage = '';
        if ($vFrontMessage) {
            $vMessage = $vMessage . $vFrontMessage . ' ';
        }
        if ($e) {
            Mage::logException($e);
            $oLogging->writeLog($e->getTraceAsString(), Zend_Log::DEBUG);
            $vMessage = $vMessage . $e->getMessage() . ' ';
        }
        if ($vRearMessage) {
            $vMessage = $vMessage . $vRearMessage;
        }
        if ($bAddToSession) {
            Mage::getSingleton('adminhtml/session')->addError($vMessage);
        }
        $oLogging->writeLog($vMessage, $iLogLevel);
    }

    protected function _errorHandling(Zend_Http_Response $oResponse, $vSuccessMessage, $vErrorMessage)
    {
        $oLogging = $this->_getLogHandler();
        $oHelper = Mage::helper('zipmoneypayment');
        if ($oResponse->isSuccessful()) {
            $oLogging->writeLog($vSuccessMessage, Zend_Log::INFO);
            Mage::getSingleton('adminhtml/session')->addSuccess($vSuccessMessage);
        } else {
            $oBody = json_decode($oResponse->getBody());
            $vMessage = $vErrorMessage;
            if (isset($oBody->Message)) {
                $vMessage = $vErrorMessage . ' ' . $oHelper->__($oBody->Message);
            }
            $oLogging->writeLog($vErrorMessage, Zend_Log::WARN);
            $oLogging->writeLog($oResponse->getBody(), Zend_Log::DEBUG);
            Mage::getSingleton('adminhtml/session')->addError($vMessage);
        }
    }

    /**
     * Request configuration data from zipMoney
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function requestConfigFromZip(Varien_Event_Observer $observer)
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $vMerchantId = Mage::helper('zipmoneypayment/api')->getMerchantId(true);
        try {
            if ($oHelper->refreshApiKeysHash()) {
                // API Keys has been changed, request latest config data from zipMoney
                $oHelper->requestConfigAndUpdate();
            }
        } catch (Exception $e) {
            $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_HASH;
            Mage::getModel('zipmoneypayment/config')->saveConfigByMatchedScopes($vPath, '', $vMerchantId);
            $vMessage = $oHelper->__('An error occurred during requesting config data from zipMoney.');
            $this->_exceptionHandling($e, $vMessage);
        }
    }

    /**
     * check if order was created by zipMoney
     *
     * @param Mage_Sales_Model_Order $oOrder
     * @return bool
     */
    protected function _isOrderZipMoney(Mage_Sales_Model_Order $oOrder)
    {
        if (!$oOrder || !$oOrder->getId()) {
            return false;
        }
        // check if the order was created by zipMoney
        $oPayment = $oOrder->getPayment();
        if ($oPayment && $oPayment->getId()) {
            if(Zipmoney_ZipmoneyPayment_Helper_Data::PAYMENT_METHOD_CODE == $oPayment->getMethod()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check whether related shipping address fields changed, and decide to notify zipMoney or not.
     *
     * @param Mage_Sales_Model_Order_Address $oAddress
     * @return bool
     */
    public function isNotifyShippingAddressUpdated(Mage_Sales_Model_Order_Address $oAddress)
    {
        // check if the order was created by zipMoney
        $oOrder = $oAddress->getOrder();
        $iOrigOrderId = $oOrder->getOrigData('entity_id');
        $iOrderId = $oOrder->getId();
        if ($iOrigOrderId !== $iOrderId) {
            // triggered by creating/placing order
            return false;
        }

        $bIsOrderZip = $this->_isOrderZipMoney($oOrder);
        if (!$bIsOrderZip) {
            return false;
        }

        if ($oAddress->getAddressType() != Mage_Customer_Model_Address_Abstract::TYPE_SHIPPING) {
            return false;
        }
        if (is_null($oAddress->getOrigData())) {
            return false;
        }
        if ($oAddress->getOrigData('prefix') != $oAddress->getPrefix()
            || $oAddress->getOrigData('firstname') != $oAddress->getFirstname()
            || $oAddress->getOrigData('middlename') != $oAddress->getMiddlename()
            || $oAddress->getOrigData('lastname') != $oAddress->getLastname()
            || $oAddress->getOrigData('suffix') != $oAddress->getSuffix()
            || $oAddress->getOrigData('company') != $oAddress->getCompany()
            || $oAddress->getOrigData('street') != $oAddress->getData('street')
            || $oAddress->getOrigData('city') != $oAddress->getCity()
            || $oAddress->getOrigData('country_id') != $oAddress->getCountryId()
            || $oAddress->getOrigData('region_id') != $oAddress->getData('region_id')
            || $oAddress->getOrigData('region') != $oAddress->getData('region')     // for the case of free text field of state
            || $oAddress->getOrigData('postcode') != $oAddress->getPostcode()
            || $oAddress->getOrigData('telephone') != $oAddress->getTelephone()
            || $oAddress->getOrigData('fax') != $oAddress->getFax()
            || $oAddress->getOrigData('vat_id') != $oAddress->getVatId()
        ) {
            return true;
        }

        return false;
    }

    /**
     * Notify zipMoney when order's shipping address is updated
     *
     * @param Varien_Event_Observer $observer
     */
    public function notifyShippingAddressUpdated(Varien_Event_Observer $observer)
    {
        $oLogging = $this->_getLogHandler();
        /** @var Mage_Sales_Model_Order_Address $oAddress */
        $oEvent = $observer->getEvent();
        $oAddress = $oEvent->getAddress();

        // set scope
        if ($oAddress && $oAddress instanceof Mage_Sales_Model_Order_Address) {
            Mage::getSingleton('zipmoneypayment/storeScope')->setStoreId($oAddress->getOrder()->getStoreId());
        }

        if (!$this->isNotifyShippingAddressUpdated($oAddress)) {
            return;
        }

        //notify zipMoney with updated shipping address
        $oHelper = Mage::helper('zipmoneypayment');
        $oApiHelper = Mage::helper('zipmoneypayment/api');
        $oOrder = $oAddress->getOrder();
        $oLogging->writeLog($oHelper->__('Calling notifyShippingAddressUpdated for order ' . $oOrder->getIncrementId() . '.'), Zend_Log::DEBUG);

        /**
         * raw data:
         *  order obj   => $oOrder
         *  shipping_address obj => $oAddress
         */
        $aRawData = array(
            'order' => $oOrder,
            'shipping_address' => $oAddress
        );

        try {
            $vEndpoint = ZipMoney_ApiSettings::API_TYPE_ORDER_SHIPPING_ADDRESS;
            $aRequestData = $oApiHelper->prepareDataForZipRequest($vEndpoint, $aRawData);
            $oResponse = $oApiHelper->callZipApi($vEndpoint, $aRequestData);
            $vSuccessMessage = $oHelper->__('Notified zipMoney that shipping address was updated.');
            $vErrorMessage = $oHelper->__('Cannot notify zipMoney.');
            $this->_errorHandling($oResponse, $vSuccessMessage, $vErrorMessage);
        } catch (Exception $e) {
            $oLogging->writeLog(json_encode($aRequestData), Zend_Log::DEBUG);
            $vMessage = $oHelper->__('An error occurred during notifying zipMoney.');
            $this->_exceptionHandling($e, $vMessage);
        }
    }

    /**
     * Check whether order is cancel, and decide to notify zipMoney or not
     *
     * @param Mage_Sales_Model_Order $oOrder
     * @return bool
     */
    public function isNotifyOrderCancelled(Mage_Sales_Model_Order $oOrder)
    {
        if (!$oOrder || !$oOrder->getId()) {
            return false;
        }

        $oLogging = $this->_getLogHandler();
        $oHelper = Mage::helper('zipmoneypayment');
        // check if the order was created by zipMoney
        $bIsOrderZip = $this->_isOrderZipMoney($oOrder);
        if (!$bIsOrderZip) {
            $oLogging->writeLog($oHelper->__('Order ' . $oOrder->getIncrementId() . ' was not created by zipMoney. Will not notify zipMoney to cancel order.'), Zend_Log::DEBUG);
            return false;
        }

        $vOriginalState = $oOrder->getOrigData('state');
        $vCurState = $oOrder->getState();
        if ($vCurState != Mage_Sales_Model_Order::STATE_CANCELED
            || $vOriginalState == $vCurState) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Notify zipMoney when an order is cancelled
     *
     * @param Varien_Event_Observer $observer
     */
    public function notifyOrderCancelled(Varien_Event_Observer $observer)
    {
        $oLogging = $this->_getLogHandler();
        /** @var Mage_Sales_Model_Order $oOrder */
        $oEvent = $observer->getEvent();
        $oOrder = $oEvent->getOrder();

        // set scope
        if ($oOrder) {
            Mage::getSingleton('zipmoneypayment/storeScope')->setStoreId($oOrder->getStoreId());
        }

        if (!$this->isNotifyOrderCancelled($oOrder)) {
            return;
        }

        // notify zipMoney via existing cancel Zip endpoint
        $oHelper = Mage::helper('zipmoneypayment');
        $oApiHelper = Mage::helper('zipmoneypayment/api');
        $oLogging->writeLog($oHelper->__('Calling notifyOrderCancelled for order ' . $oOrder->getIncrementId() . '.'), Zend_Log::DEBUG);
        /**
         * raw data:
         *  order obj   => $oOrder
         */
        $aRawData = array(
            'order' => $oOrder
        );

        try {
            $vEndpoint = ZipMoney_ApiSettings::API_TYPE_ORDER_CANCEL;
            $aRequestData = $oApiHelper->prepareDataForZipRequest($vEndpoint, $aRawData);
            $oResponse = $oApiHelper->callZipApi($vEndpoint, $aRequestData);
            $vSuccessMessage = $oHelper->__('Notified zipMoney that order was cancelled.');
            $vErrorMessage = $oHelper->__('Cannot notify zipMoney.');
            $this->_errorHandling($oResponse,$vSuccessMessage, $vErrorMessage);
        } catch (Exception $e) {
            $oLogging->writeLog(json_encode($aRequestData), Zend_Log::DEBUG);
            $vMessage = $oHelper->__('An error occurred during notifying zipMoney.');
            $this->_exceptionHandling($e, $vMessage);
        }
    }

    protected function isAvoidInvoicing(Mage_Sales_Model_Order $oOrder)
    {
        if (!$oOrder || !$oOrder->getId()) {
            return false;
        }

        $vOriginalStatus = $oOrder->getOrigData('status');
        $vStatus = $oOrder->getStatus();
        $vState = $oOrder->getState();

        // check if the order was created by zipMoney
        $bIsOrderZip = $this->_isOrderZipMoney($oOrder);
        if (!$bIsOrderZip) {
            return false;
        }
        /**
         * do not create invoice if any of the follow is true
         *  1) order status from '' to 'zip_pending', and order new state is 'new' or 'processing'
         *  2) order status from 'zip_pending' to 'zip_authorised', and order new state is 'new' or 'processing'
         */
        if (Mage_Sales_Model_Order::STATE_NEW == $vState
            || Mage_Sales_Model_Order::STATE_PROCESSING == $vState) {
            if (!$vOriginalStatus
                && Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_NEW == $vStatus) {
                return true;
            }
            if (Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_NEW == $vOriginalStatus
                && Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_AUTHORIZED == $vStatus) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set order invoice_action_flag to false to avoid 3rd party module creating invoice automatically
     *
     * @param Varien_Event_Observer $observer
     */
    public function setInvoiceActionFlag(Varien_Event_Observer $observer)
    {
        $oLogging = $this->_getLogHandler();
        $oHelper = Mage::helper('zipmoneypayment');

        /** @var Mage_Sales_Model_Order $oOrder */
        $oEvent = $observer->getEvent();
        $oOrder = $oEvent->getOrder();

        if (!$this->isAvoidInvoicing($oOrder)) {
            return;
        }

        if ($oOrder->getActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_INVOICE) !== false) {
            $vOriginalStatus = $oOrder->getOrigData('status');
            $vOriginalState = $oOrder->getOrigData('state');
            $vStatus = $oOrder->getStatus();
            $vState = $oOrder->getState();
            $oLogging->writeLog($oHelper->__('Original state: %s; new state: %s', $vOriginalState, $vState), Zend_Log::DEBUG);
            $oLogging->writeLog($oHelper->__('Original status: %s; new status: %s', $vOriginalStatus, $vStatus), Zend_Log::DEBUG);

            $oLogging->writeLog($oHelper->__('Set order invoice_action_flag to false.'), Zend_Log::DEBUG);
            $oOrder->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_INVOICE, false);
        }
    }


    public function zipActionPredispatch($observer)
    {   
        $oLogging = $this->_getLogHandler();
        $oHelper = Mage::helper('zipmoneypayment');
               
        $currency_old = Mage::app()->getStore()->getCurrentCurrencyCode();

        if($currency_old == "AUD")
           return;

        Mage::app()->getStore()->setCurrentCurrencyCode('AUD');

        $currency_new = Mage::app()->getStore()->getCurrentCurrencyCode();   

        $oLogging->writeLog($oHelper->__('Previous Currency = %s, Current Currency = %s',$currency_old,$currency_new), Zend_Log::DEBUG);

    }



}