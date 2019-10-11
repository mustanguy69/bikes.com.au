<?php
/**
 * @category  Aligent
 * @package   Package Name
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Model_Express_Checkout
{

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote = null;

    /**
     * @var int
     */
    protected $_customerId = null;

    /**
     * @var Mage_Sales_Model_Order
     */
    protected $_order = null;

    /**
     * @var Mage_Customer_Model_Session
     */
    protected $_customerSession;

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

    /**
     * Set quote instance
     *
     * @param array $aParams
     * @throws Exception
     */
    public function __construct($aParams = array())
    {
        if (isset($aParams['quote']) && $aParams['quote'] instanceof Mage_Sales_Model_Quote) {
            $this->_quote = $aParams['quote'];
        } else {
            throw new Exception('Quote instance is required.');
        }
        $this->_customerSession = Mage::getSingleton('customer/session');
    }

    /**
     * Setter for customer with billing and shipping address changing ability
     *
     * @param $oCustomer
     * @param null $oBillingAddress
     * @param null $oShippingAddress
     * @return $this
     */
    public function setCustomerWithAddressChange($oCustomer, $oBillingAddress = null, $oShippingAddress = null)
    {
        $this->_quote->assignCustomerWithAddressChange($oCustomer, $oBillingAddress, $oShippingAddress);
        $this->_customerId = $oCustomer->getId();
        return $this;
    }

    /**
     * Return order
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->_quote;
    }

    /**
     * Get customer session object
     *
     * @return Mage_Customer_Model_Session
     */
    public function getCustomerSession()
    {
        return $this->_customerSession;
    }

    /**
     * Make sure addresses will be saved without validation errors
     */
    private function _ignoreAddressValidation()
    {
        $this->_quote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$this->_quote->getIsVirtual()) {
            $this->_quote->getShippingAddress()->setShouldIgnoreValidation(true);
            if (!$this->_quote->getBillingAddress()->getEmail()) {
                $this->_quote->getBillingAddress()->setSameAsBilling(1);
            }
        }
    }

    /**
     * Set shipping method to quote, if needed
     * @param string $vMethodCode
     * @return Mage_Sales_Model_Quote
     */
    public function updateShippingMethod($vMethodCode)
    {
        $oLogging = $this->_getLogHandler();
        $oShippingAddress = $this->_quote->getShippingAddress();
        if (!$this->_quote->getIsVirtual() && $oShippingAddress) {
            $vOldMethod = $oShippingAddress->getShippingMethod();
            if ($vMethodCode != $vOldMethod) {
                $this->_ignoreAddressValidation();
                $oShippingAddress->setShippingMethod($vMethodCode);
                $oLogging->writeLog(Mage::helper('zipmoneypayment')->__('Updated shipping method to %s (old method: %s).', $vMethodCode, $vOldMethod), Zend_Log::INFO);
                $this->_quote->collectTotals();
            }
        }
        return $this->_quote;
    }

    /**
     * Get checkout method
     * @return string
     */
    public function getCheckoutMethod($bQuoteFromDb = true)
    {
        if (!$bQuoteFromDb) {
            if ($this->getCustomerSession()->isLoggedIn()) {
                return Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER;
            }
        }

        /**
         * checkout_method (in sales_flat_quote) could be:
         * - null
         * - guest
         * - register
         *
         * getCheckoutMethod could return:
         * - login_in (Mage_Sales_Model_Quote::CHECKOUT_METHOD_LOGIN_IN)
         * - guest
         * - register
         *
         * Todo: getCheckoutMethod is deprecated. Will need to replace it in the future.
         */
        if ($this->_quote->getCheckoutMethod() == Mage_Sales_Model_Quote::CHECKOUT_METHOD_LOGIN_IN) {
            return Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER;
        }

        if (!$this->_quote->getCheckoutMethod()) {
            if (Mage::helper('checkout')->isAllowedGuestCheckout($this->_quote)) {
                $this->_quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_GUEST);
            } else {
                $this->_quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER);
            }
        }
        return $this->_quote->getCheckoutMethod();
    }

    /**
     * Prepare quote for guest checkout order submit
     * @return Zipmoney_ZipmoneyPayment_Model_Express_Checkout
     */
    protected function _prepareGuestQuote()
    {
        $oQuote = $this->_quote;
        $oQuote->setCustomerId(null);
        $vBillingEmail = $oQuote->getBillingAddress()->getEmail();
        if ($vBillingEmail) {
            $oQuote->setCustomerEmail($vBillingEmail);
        }
        $oQuote->setCustomerIsGuest(true);
        $oQuote->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
        return $this;
    }


    protected function _lookupCustomerId()
    {
        return Mage::getModel('customer/customer')
            ->setWebsiteId(Mage::app()->getWebsite()->getId())
            ->loadByEmail($this->_quote->getCustomerEmail())
            ->getId();
    }

    /**
     * Prepare quote for customer registration and customer order submit
     * and restore magento customer data from quote
     *
     * @return Zipmoney_ZipmoneyPayment_Model_Express_Checkout
     */
    protected function _prepareNewCustomerQuote()
    {
        $oLogging = $this->_getLogHandler();
        $oQuote      = $this->_quote;
        $oBilling    = $oQuote->getBillingAddress();
        $oShipping   = $oQuote->isVirtual() ? null : $oQuote->getShippingAddress();

        $oLogging->writeLog($oLogging->__('Prepare to create new customer with email %s', $this->_quote->getCustomerEmail()), Zend_Log::INFO);
        $customerId = $this->_lookupCustomerId();
        if ($customerId) {
            $oLogging->writeLog($oLogging->__('The email has already been used for customer (id: %s) ', $customerId), Zend_Log::INFO);
            $this->getCustomerSession()->loginById($customerId);
            return $this->_prepareCustomerQuote();
        }

        $oCustomer = $oQuote->getCustomer();
        /** @var $oCustomer Mage_Customer_Model_Customer */
        $oCustomerBilling = $oBilling->exportCustomerAddress();
        $oCustomer->addAddress($oCustomerBilling);
        $oBilling->setCustomerAddress($oCustomerBilling);
        $oCustomerBilling->setIsDefaultBilling(true);

        if ($oShipping) {
            $oCustomerShipping = $oShipping->exportCustomerAddress();
            $oCustomer->addAddress($oCustomerShipping);
            $oShipping->setCustomerAddress($oCustomerShipping);
            $oCustomerShipping->setIsDefaultShipping(true);
        }

        if ($oQuote->getCustomerDob() && !$oBilling->getCustomerDob()) {
            $oBilling->setCustomerDob($oQuote->getCustomerDob());
        }

        if ($oQuote->getCustomerTaxvat() && !$oBilling->getCustomerTaxvat()) {
            $oBilling->setCustomerTaxvat($oQuote->getCustomerTaxvat());
        }

        if ($oQuote->getCustomerGender() && !$oBilling->getCustomerGender()) {
            $oBilling->setCustomerGender($oQuote->getCustomerGender());
        }

        Mage::helper('core')->copyFieldset('checkout_onepage_billing', 'to_customer', $oBilling, $oCustomer);
        $oCustomer->setEmail($oQuote->getCustomerEmail());
        $oCustomer->setPrefix($oQuote->getCustomerPrefix());
        $oCustomer->setFirstname($oQuote->getCustomerFirstname());
        $oCustomer->setMiddlename($oQuote->getCustomerMiddlename());
        $oCustomer->setLastname($oQuote->getCustomerLastname());
        $oCustomer->setSuffix($oQuote->getCustomerSuffix());
        $oCustomer->setPassword($oCustomer->decryptPassword($oQuote->getPasswordHash()));
        $oCustomer->setPasswordHash($oCustomer->hashPassword($oCustomer->getPassword()));
        $oLogging->writeLog($oLogging->__('Customer password is %s', ($oCustomer->getPassword() ? 'not empty' : 'empty')), Zend_Log::DEBUG);
        $oLogging->writeLog($oLogging->__('Customer password_hash is %s', ($oCustomer->getPasswordHash() ? 'not empty' : 'empty')), Zend_Log::DEBUG);
        $oCustomer->save();
        $oQuote->setCustomer($oCustomer);
        $oLogging->writeLog($oLogging->__('The new customer has been created successfully. Customer id: %s', $oCustomer->getId()), Zend_Log::INFO);
        return $this;
    }

    /**
     * Prepare quote for customer order submit
     *
     * @return Zipmoney_ZipmoneyPayment_Model_Express_Checkout
     */
    protected function _prepareCustomerQuote()
    {
        $oLogging = $this->_getLogHandler();
        $oQuote      = $this->_quote;
        $oBilling    = $oQuote->getBillingAddress();
        $oShipping   = $oQuote->isVirtual() ? null : $oQuote->getShippingAddress();

        $oCustomer = $this->getCustomerSession()->getCustomer();
        $oLogging->writeLog($oLogging->__('Load customer from session. Customer id: %s', $oCustomer->getId()), Zend_Log::DEBUG);

        if (!$oBilling->getCustomerId() || $oBilling->getSaveInAddressBook()) {
            $oCustomerBilling = $oBilling->exportCustomerAddress();
            $oCustomer->addAddress($oCustomerBilling);
            $oBilling->setCustomerAddress($oCustomerBilling);
        }
        if ($oShipping && ((!$oShipping->getCustomerId() && !$oShipping->getSameAsBilling())
                || (!$oShipping->getSameAsBilling() && $oShipping->getSaveInAddressBook()))) {
            $oCustomerShipping = $oShipping->exportCustomerAddress();
            $oCustomer->addAddress($oCustomerShipping);
            $oShipping->setCustomerAddress($oCustomerShipping);
        }

        if (isset($oCustomerBilling) && !$oCustomer->getDefaultBilling()) {
            $oCustomerBilling->setIsDefaultBilling(true);
        }
        if ($oShipping && isset($oCustomerBilling) && !$oCustomer->getDefaultShipping() && $oShipping->getSameAsBilling()) {
            $oCustomerBilling->setIsDefaultShipping(true);
        } elseif ($oShipping && isset($oCustomerShipping) && !$oCustomer->getDefaultShipping()) {
            $oCustomerShipping->setIsDefaultShipping(true);
        }
        $oQuote->setCustomer($oCustomer);

        return $this;
    }

    /**
     * Involve new customer to system
     *
     * @return Zipmoney_ZipmoneyPayment_Model_Express_Checkout
     */
    protected function _involveNewCustomer()
    {
        $oCustomer = $this->_quote->getCustomer();
        if ($oCustomer->isConfirmationRequired()) {
            $oCustomer->sendNewAccountEmail('confirmation');
            $vUrl = Mage::helper('customer')->getEmailConfirmationUrl($oCustomer->getEmail());
            $this->getCustomerSession()->addSuccess(
                Mage::helper('customer')->__('Account confirmation is required. Please, check your e-mail for confirmation link. To resend confirmation email please <a href="%s">click here</a>.', $vUrl)
            );
        } else {
            $oCustomer->sendNewAccountEmail();
            $this->getCustomerSession()->loginById($oCustomer->getId());
        }
        return $this;
    }

    /**
     * Update selected shipping method, re-collect totals, then place the order (convert quote to order),
     *
     * @param bool $bQuoteFromDb
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function place($bQuoteFromDb = true)
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $oLogging = $this->_getLogHandler();
        $oLogging->writeLog($oHelper->__('Quote grand total (initial): ' . $this->_quote->getGrandTotal()), Zend_Log::DEBUG);

        $bIsNewCustomer = false;
        $oLogging->writeLog($oHelper->__('Quote checkout_method: %s', $this->_quote->getData('checkout_method')), Zend_Log::INFO);
        $oLogging->writeLog($oHelper->__('Quote customer_id: %s', $this->_quote->getData('customer_id')), Zend_Log::INFO);
        $oLogging->writeLog($oHelper->__('Quote password_hash is %s', ($this->_quote->getData('password_hash') ? 'not empty' : 'empty')), Zend_Log::INFO);
        $vQuoteCheckoutMethod = $this->getCheckoutMethod($bQuoteFromDb);
        $oLogging->writeLog($oHelper->__('Quote checkout method: %s', $vQuoteCheckoutMethod), Zend_Log::INFO);
        /**
         * if the quote is loaded from database, then don't get customer from session and set to quote, because session might be expired
         */
        switch ($vQuoteCheckoutMethod) {
            case Mage_Checkout_Model_Type_Onepage::METHOD_GUEST:
                $this->_prepareGuestQuote();
                break;
            case Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER:
                $this->_prepareNewCustomerQuote();
                $bIsNewCustomer = true;
                break;
            default:
                if (!$bQuoteFromDb) {
                    $oLogging->writeLog($oHelper->__('Load customer from session.'), Zend_Log::DEBUG);
                    $this->_prepareCustomerQuote();
                }
                break;
        }

        $this->_ignoreAddressValidation();
        $this->_quote->getPayment()->setMethod(Zipmoney_ZipmoneyPayment_Helper_Data::PAYMENT_METHOD_CODE);
        $oLogging->writeLog($oHelper->__('Collect shipping rates: %s', $this->_quote->getShippingAddress()->getCollectShippingRates()), Zend_Log::DEBUG);
        $this->_quote->collectTotals();

        /** @var Mage_Sales_Model_Service_Quote $oService */
        $oService = Mage::getModel('sales/service_quote', $this->_quote);
        $oService->submitAll();
        $this->_quote->save();

        if ($bIsNewCustomer) {
            try {
                $this->_involveNewCustomer();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        $oOrder = $oService->getOrder();
        if (!$oOrder) {
            return;
        }

        switch ($oOrder->getState()) {
            case Mage_Sales_Model_Order::STATE_PENDING_PAYMENT:
                break;
            // regular placement, when everything is ok
            case Mage_Sales_Model_Order::STATE_PROCESSING:
            case Mage_Sales_Model_Order::STATE_COMPLETE:
            case Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW:
                $oOrder->sendNewOrderEmail();
                break;
        }
        $this->_order = $oOrder;
    }
}