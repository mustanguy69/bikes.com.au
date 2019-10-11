<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

abstract class Zipmoney_ZipmoneyPayment_Controller_Express_Abstract extends Mage_Core_Controller_Front_Action
{
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

    /**
     * Get checkout session model instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Handles error
     *
     * @param $e
     * @param null $vFrontMessage
     * @param null $vRearMessage
     * @param bool $bAddToSession
     * @param bool $bAddToResponse
     * @param int $iHttpCode
     * @param bool $bGoToErrorPage
     * @param null $vPath
     * @return null|string
     */
    protected function _errorHandling($e, $vFrontMessage = null, $vRearMessage = null, $bAddToSession = true, $bAddToResponse = false, $iHttpCode = Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR, $bGoToErrorPage = false, $vPath = null)
    {
        $iLogLevel = Zend_Log::WARN;

        /** @var Exception $e */
        if (!$e && !$vFrontMessage && !$vRearMessage) {
            return null;
        }

        $vMessage = '';
        $vResponseMessage = '';
        if ($vFrontMessage) {
            $vMessage = $vMessage . $vFrontMessage . ' ';
        }
        if ($e) {
            $iLogLevel = Zend_Log::ERR;
            Mage::logException($e);
            $this->_logging->writeLog($e->getTraceAsString(), Zend_Log::ERR);
            $vMessage = $vMessage . $e->getMessage() . ' ';
            $vResponseMessage = $e->getMessage();
        }
        if ($vRearMessage) {
            $vMessage = $vMessage . $vRearMessage;
        }
        if ($bAddToSession) {
            Mage::getSingleton('core/session')->addError(Mage::helper('core')->escapeHtml($vMessage));
        }
        if ($bAddToResponse) {
            // do not send prefix (front_message) if it's an exception
            if (!$vResponseMessage) {
                $vResponseMessage = $vFrontMessage ? $vFrontMessage : $vRearMessage;
            }
            $aData = array(
                'message'   => $vResponseMessage
            );
            $this->_setResponseData($aData, $iHttpCode);
        }
        $this->_logging->writeLog($vMessage, $iLogLevel);
        if ($bGoToErrorPage) {
            $vUrl = Mage::helper('zipmoneypayment')->getErrorUrl();
            $this->getResponse()->setRedirect($vUrl);
        } else if ($vPath) {
            $vUrl = Mage::getUrl($vPath);
            $this->getResponse()->setRedirect($vUrl);
        }
        return $vMessage;
    }

    protected function _getRequestBody()
    {
        $vRequest = $this->getRequest()->getRawBody();
        if (!$vRequest) {
            $aData = array(
                'message'   => Mage::helper('zipmoneypayment')->__('Can not accept the request.')
            );
            $this->_setResponseData($aData, Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
            return null;
        }
        $oRequest = json_decode($vRequest);

        $this->setRequestScope($oRequest);
        return $oRequest;
    }


    /**
     * Check API keys availability
     *
     * @param $oRequest
     * @return bool
     */
    protected function _isApiKeysValid($oRequest)
    {
        if (!$oRequest || !isset($oRequest->merchant_id) || !isset($oRequest->merchant_key)) {
            return false;
        }
        $oApiHelper = Mage::helper('zipmoneypayment/api');
        if ($oApiHelper->isApiKeysValid($oRequest->merchant_id, $oRequest->merchant_key)) {
            return true;
        }
        return false;
    }

    protected function _setResponseData($aData, $iResponseCode = Mage_Api2_Model_Server::HTTP_OK)
    {
        $this->getResponse()->setHttpResponseCode($iResponseCode);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $jsonData = Mage::helper('core')->jsonEncode($aData);
        $this->getResponse()->setBody($jsonData);
    }

    /**
     * Generate and save quote token, if it does not exist.
     *
     * @param $oQuote
     */
    protected function _checkQuoteToken($oQuote)
    {
        /** @var Mage_Sales_Model_Quote $oQuote */
        if ($oQuote && !$oQuote->getZipmoneyToken()) {
            $vToken = md5(rand(0, 9999999));
            $oQuote->setZipmoneyToken($vToken);
            $oQuote->save();
        }
    }

    /**
     * Check availability of API keys in request
     *
     * @param $oRequest
     * @return bool
     */
    protected function _checkRequestApiKeys($oRequest)
    {
        $oHelper = Mage::helper('zipmoneypayment');
        if ($this->_isApiKeysValid($oRequest)) {
            return true;
        }
        $vFrontMessage = $oHelper->__('Incorrect API keys.');
        $this->_errorHandling(null, $vFrontMessage, null, false, true);
        return false;
    }

    /**
     * Check if selected_option_id is present.
     *
     * @param $oRequest
     * @return bool
     */
    protected function _checkRequestShippingMethod($oRequest)
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $vShippingMethodCode = isset($oRequest->shipping_address->selected_option_id) ?  $oRequest->shipping_address->selected_option_id : null;
        if (!$vShippingMethodCode) {
            $vFrontMessage = $oHelper->__('Cannot find selected shipping method.');
            $this->_errorHandling(null, $vFrontMessage, null, false, true);
            return false;
        } else {
            return true;
        }
    }


    protected function _checkQuoteScope($oQuote)
    {
        /** @var Mage_Sales_Model_Quote $oQuote */
        $oHelper = Mage::helper('zipmoneypayment');
        if ($oQuote) {
            $vQuoteWebsiteCode = $oQuote->getStore()->getWebsite()->getCode();
            $vWebsiteCode = Mage::app()->getWebsite()->getCode();
            if ($vQuoteWebsiteCode != $vWebsiteCode) {
                $vFrontMessage = $oHelper->__('The quote (id: %s, website: %s) does not belong to current website (code: %s) .', $oQuote->getId(), $vQuoteWebsiteCode, $vWebsiteCode);
                $this->_setResponseWithErrorMessage(null, $vFrontMessage);
                return false;
            }
        }
        return true;
    }

    /**
     * Check quote availability including quote_id, quote, quote is_active
     *
     * @param $oRequest
     * @param $iExpQuoteActive
     * @param bool $bCheckQuoteStatus
     * @return bool
     */
    protected function _checkRequestQuote($oRequest, $iExpQuoteActive = 1, $bCheckQuoteStatus = true)
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $oQuoteHelper = Mage::helper('zipmoneypayment/quote');
        $iQuoteId = isset($oRequest->quote_id) ?  $oRequest->quote_id : null;
        if (!$iQuoteId) {
            $vFrontMessage = $oHelper->__('Incorrect quote id.');
            $this->_errorHandling(null, $vFrontMessage, null, false, true);
            return false;
        }

        $oQuote = $oQuoteHelper->getQuote($iQuoteId);
        if (!$oQuote) {
            $vFrontMessage = $oHelper->__('Can not load the quote (id: %s).', $iQuoteId);
            $this->_errorHandling(null, $vFrontMessage, null, false, true);
            return false;
        }
        if ($bCheckQuoteStatus) {
            if ($oQuote->getIsActive() != $iExpQuoteActive) {
                $vFrontMessage = $oHelper->__('Incorrect quote active status. Quote isActive: %s.', $oQuote->getIsActive());
                $this->_errorHandling(null, $vFrontMessage, null, false, true);
                return false;
            }
        }
        return true;
    }

    /**
     * Check order availability including increment_id, order, quote reserved_order_id
     *
     * @param $oRequest
     * @param $oQuote
     * @return bool
     */
    protected function _checkRequestOrder($oRequest, $oQuote)
    {
        $oHelper = Mage::helper('zipmoneypayment');
        if (!$oQuote) {
            return false;
        }
        $vReservedId = $oQuote->getReservedOrderId();
        $vOrderIncId = isset($oRequest->order->id) ?  $oRequest->order->id : null;
        if (!$vOrderIncId && !$vReservedId) {
            if ($vReservedId != $vOrderIncId) {
                $vFrontMessage = $oHelper->__('Incorrect order id.');
                $this->_errorHandling(null, $vFrontMessage, null, false, true);
                return false;
            }
        }
        $oOrder = Mage::getModel('sales/order')->loadByIncrementId($vReservedId);
        if (!$oOrder || $oOrder->isObjectNew() || !$oOrder->getId()) {
            $vFrontMessage = $oHelper->__('Can not load the order.');
            $this->_errorHandling(null, $vFrontMessage, null, false, true);
            return false;
        }
        return true;
    }

    /**
     * Check quote zipmoney token availability
     *
     * @param $oQuote
     * @param $vToken
     * @return bool
     */
    protected function _checkRequestToken($oQuote, $vToken)
    {
        $oHelper = Mage::helper('zipmoneypayment');
        if (!$oQuote) {
            return false;
        }
        if (!$vToken || !$oQuote->getZipmoneyToken() || $vToken != $oQuote->getZipmoneyToken()) {
            $vFrontMessage = $oHelper->__('Invalid token.');
            $this->_errorHandling(null, $vFrontMessage, null, false, true);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check request availability based on action
     *
     * @param $vAction
     * @param $oRequest
     * @param null $oQuote
     * @param null $vToken
     * @return bool
     */
    protected function _checkRequestAvailability($vAction, $oRequest, $oQuote = null, $vToken = null)
    {
        $bApiKeys = true;
        $bShippingMethod = true;
        $bQuote = true;
        $bOrder = true;
        $bToken = true;
        switch ($vAction) {
            case Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_GET_SHIPPING_METHODS:
                $bApiKeys = $this->_checkRequestApiKeys($oRequest);
                break;
            case Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_CONFIRM_ORDER:
                $bApiKeys = $this->_checkRequestApiKeys($oRequest);
                if (!$bApiKeys) {
                    return false;
                }
                $bShippingMethod = $this->_checkRequestShippingMethod($oRequest);
                if (!$bShippingMethod) {
                    return false;
                }
                $bQuote = $this->_checkRequestQuote($oRequest, null, false);
                break;
            case Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_FINALISE_ORDER:
                $bApiKeys = $this->_checkRequestApiKeys($oRequest);
                if (!$bApiKeys) {
                    return false;
                }
                $bQuote = $this->_checkRequestQuote($oRequest, 0);
                break;
            case Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_SUCCESS_PAGE:
                if (!$this->_checkQuoteScope($oQuote)) {
                    return false;
                }
                $bQuote = $this->_checkRequestQuote($oRequest, 0);
                if (!$bQuote) {
                    return false;
                }
                $bToken = $this->_checkRequestToken($oQuote, $vToken);
                if (!$bToken) {
                    return false;
                }
                $bOrder = $this->_checkRequestOrder($oRequest, $oQuote);
                break;
            case Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_CANCEL_QUOTE:
                if (!$this->_checkQuoteScope($oQuote)) {
                    return false;
                }
                $bQuote = $this->_checkRequestQuote($oRequest, 1);
                if (!$bQuote) {
                    return false;
                }
                $bToken = $this->_checkRequestToken($oQuote, $vToken);
                break;
            case Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_DECLINED_APP:
                if (!$this->_checkQuoteScope($oQuote)) {
                    return false;
                }
                $bQuote = $this->_checkRequestQuote($oRequest, null, false);
                if (!$bQuote) {
                    return false;
                }
                $bToken = $this->_checkRequestToken($oQuote, $vToken);
                break;
            case Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_UNDER_REVIEW_PAGE:
                if (!$this->_checkQuoteScope($oQuote)) {
                    return false;
                }
                $bQuote = $this->_checkRequestQuote($oRequest, 1);
                if (!$bQuote) {
                    return false;
                }
                $bToken = $this->_checkRequestToken($oQuote, $vToken);
                break;
            case Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_ERROR_PAGE:
                break;
            case Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_ORDER_STATUS:
                $bApiKeys = $this->_checkRequestApiKeys($oRequest);
                if (!$bApiKeys) {
                    return false;
                }
                $bQuote = $this->_checkRequestQuote($oRequest, 0);
                break;
            default:
                // should never go to here
                break;
        }
        $bAvailability = $bApiKeys && $bShippingMethod && $bQuote && $bOrder && $bToken;
        return $bAvailability;
    }

    /**
     * Create quote in Zip side if not existed, and request for redirect url
     *
     * @param $vCheckoutSource
     * @return null
     * @throws Mage_Core_Exception
     */
    public function getRedirectUrl($vCheckoutSource)
    {
        $vRedirectUrl = null;
        $oQuoteHelper = Mage::helper('zipmoneypayment/quote');
        $oQuote = $oQuoteHelper->getQuote();
        if (!$oQuote || !$oQuote->getId()) {
            throw Mage::exception('Zipmoney_ZipmoneyPayment', Mage::helper('zipmoneypayment')->__('The quote does not exist.'));
        }
        $this->_checkQuoteToken($oQuote);
        if ($oQuote->getIsMultiShipping()) {
            $oQuote->setIsMultiShipping(false);
            $oQuote->removeAllAddresses();
        }
        $oExpressCheckout = $oQuoteHelper->getExpressCheckout();

        $oCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $vQuoteCheckoutMethod = $oQuote->getCheckoutMethod();
        if ($oCustomer && $oCustomer->getId()) {
            $oExpressCheckout->setCustomerWithAddressChange(
                $oCustomer, $oQuote->getBillingAddress(), $oQuote->getShippingAddress()
            );
        } elseif ((!$vQuoteCheckoutMethod
                || $vQuoteCheckoutMethod != Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER)
            && !Mage::helper('checkout')->isAllowedGuestCheckout(
                $oQuote,
                $oQuote->getStoreId()
            )) {
            $vMessage = Mage::helper('zipmoneypayment')->__('To proceed to Checkout, please log in using your email address.');
            Mage::throwException($vMessage);
        }

        $vRedirectUrl = $oQuoteHelper->getRedirectUrl($oQuote, $vCheckoutSource);
        return $vRedirectUrl;
    }

    /**
     * Check shipping address availability
     *
     * @param $oRequest
     * @return bool
     */
    protected function _checkShippingAddressAvailability($oRequest)
    {
        if (!$oRequest) {
            return false;
        }

        $oReqAddress = null;
        if (isset($oRequest->shipping_address)) {
            $oReqAddress = $oRequest->shipping_address;
        }
        if (!$oReqAddress) {
            return false;
        }
        $vLine1         = isset($oReqAddress->line1) ? $oReqAddress->line1 : '';
        $vLine2         = isset($oReqAddress->line2) ? $oReqAddress->line2 : '';
        $vStreet        = trim($vLine1 . ' ' . $vLine2);
        $vCountryId     = isset($oReqAddress->country) ? $oReqAddress->country : null;
        $vRegionCode    = isset($oReqAddress->state) ? $oReqAddress->state : null;
        $vZip           = isset($oReqAddress->zip) ? $oReqAddress->zip : null;
        if (!$vZip) {
            $vZip = isset($oReqAddress->postcode) ? $oReqAddress->postcode : null;
        }
        $vCity          = isset($oReqAddress->suburb) ? $oReqAddress->suburb : null;
        if (!$vCity) {
            $vCity = isset($oReqAddress->city) ? $oReqAddress->city : null;
        }
        if (!$vStreet
            || !$vCity
            || !$vRegionCode
            || !$vCountryId
            || !$vZip ) {
            return false;
        }
        return true;
    }

    /**
     * Save/update quote address
     *
     * @param $oAddress
     * @param $oRequest
     * @return bool
     * @throws Exception
     */
    protected function _saveQuoteAddress($oAddress, $oRequest)
    {
        if (!$oRequest || !($oAddress instanceof Mage_Sales_Model_Quote_Address)) {
            return false;
        }

        $oReqAddress = null;
        $bIsShipping = false;
        if ($oAddress->getAddressType() == Mage_Customer_Model_Address_Abstract::TYPE_SHIPPING) {
            $bIsShipping = true;
            if (isset($oRequest->shipping_address)) {
                $oReqAddress = $oRequest->shipping_address;
            }
        } else if ($oAddress->getAddressType() == Mage_Customer_Model_Address_Abstract::TYPE_BILLING) {
            $bIsShipping = false;
            if (isset($oRequest->billing_address)) {
                $oReqAddress = $oRequest->billing_address;
            }
        }
        if (!$oReqAddress) {
            return false;
        }

        $vFirstName     = isset($oReqAddress->first_name) ? $oReqAddress->first_name : null;
        $vLastName      = isset($oReqAddress->last_name) ? $oReqAddress->last_name : null;
        $vEmail         = isset($oReqAddress->email) ? $oReqAddress->email : null;
        if (!$vFirstName && isset($oRequest->consumer->first_name)) {
            $vFirstName = $oRequest->consumer->first_name;
        }
        if (!$vLastName && isset($oRequest->consumer->last_name)) {
            $vLastName = $oRequest->consumer->last_name;
        }
        if (!$vEmail && isset($oRequest->consumer->email)) {
            $vEmail = $oRequest->consumer->email;
        }

        $vCity          = isset($oReqAddress->suburb) ? $oReqAddress->suburb : null;
        if (!$vCity) {
            $vCity = isset($oReqAddress->city) ? $oReqAddress->city : null;
        }
        $vRegion        = null;
        $iRegionId      = null;
        $vRegionCode    = isset($oReqAddress->state) ? $oReqAddress->state : null;
        $vCountryId     = isset($oReqAddress->country) ? $oReqAddress->country : null;
        $vZip           = isset($oReqAddress->zip) ? $oReqAddress->zip : null;
        if (!$vZip) {
            $vZip = isset($oReqAddress->postcode) ? $oReqAddress->postcode : null;
        }

        $vLine1 = isset($oReqAddress->line1) ? $oReqAddress->line1 : '';
        $vLine2 = isset($oReqAddress->line2) ? $oReqAddress->line2 : '';
        $aStreet = array($vLine1, $vLine2);

        // check if there's change in address
        $bChanged = false;
        if ($oAddress->getFirstname() != $vFirstName
            || $oAddress->getLastname() != $vLastName
            || $oAddress->getEmail() != $vEmail
            || $oAddress->getStreet1() != $vLine1
            || $oAddress->getStreet2() != $vLine2
            || $oAddress->getCountryId() != $vCountryId
            || $oAddress->getPostcode() != $vZip
            || $oAddress->getCity() != $vCity
            || $oAddress->getRegionCode() != $vRegionCode
        ) {
            $bChanged = true;
        }
        if (!$bChanged) {
            $this->_logging->writeLog($this->__('%s address has not been changed at zipMoney.', $oAddress->getAddressType()), Zend_log::INFO);
            return false;
        }

        $this->_logging->writeLog($this->__('%s address has been changed at zipMoney. Update related quote address.', $oAddress->getAddressType()), Zend_log::INFO);
        $aCurrentAddress = array(
            'first_name' => $oAddress->getFirstname(),
            'last_name' => $oAddress->getLastname(),
            'email' => $oAddress->getEmail(),
            'street1' => $oAddress->getStreet1(),
            'street2' => $oAddress->getStreet2(),
            'country_id' => $oAddress->getCountryId(),
            'postcode' => $oAddress->getPostcode(),
            'city' => $oAddress->getCity(),
            'region_code' => $oAddress->getRegionCode(),
        );
        $this->_logging->writeLog($this->__('Current quote address: %s', json_encode($aCurrentAddress)), Zend_log::DEBUG);

        if ($vCountryId && $vRegionCode) {
            $oRegionModel = Mage::getModel('directory/region')->loadByCode($vRegionCode, $vCountryId);
            $iRegionId = $oRegionModel->getId();
            $vRegion = $oRegionModel->getName();
        }

        /**
         * If region_id is null (or not a valid id), it means there's no such region set in database (in directory_country_region table)
         * Will set region_id as null, and region as the region_code from zip's request (into sales_flat_quote_address table)
         */
        if (!$iRegionId) {
            $iRegionId = null;
            $vRegion = $vRegionCode;
        }

        if ($bIsShipping) {
            /**
             * Because shipping address has been changed, set it to true
             * so that when quote->collectTotals(), it'll address->collectShippingRates()
             */
            $oAddress->setCollectShippingRates(true);
        }
        $oAddress->setFirstname($vFirstName);
        $oAddress->setLastname($vLastName);
        $oAddress->setEmail($vEmail);
        $oAddress->setStreet($aStreet);
        $oAddress->setCountryId($vCountryId);
        $oAddress->setPostcode($vZip);
        $oAddress->setCity($vCity);

        /**
         * If it's from free text field, save null region id, and save the input value to region
         * There's no region_code column in sales_flat_quote_address table, no need to save it.
         */
        $oAddress->setRegion($vRegion);
        $oAddress->setRegionId($iRegionId);
        $oAddress->save();
        return true;
    }

    /**
     * Set current scope based on a request (from zipMoney)
     *
     * @param $oRequest
     */
    public function setRequestScope($oRequest)
    {
        if (isset($oRequest->merchant_id)) {
            Mage::getSingleton('zipmoneypayment/storeScope')->setMerchantId($oRequest->merchant_id);
        }
        if (isset($oRequest->merchant_key)) {
            Mage::getSingleton('zipmoneypayment/storeScope')->setMerchantKey($oRequest->merchant_key);
        }
        if (isset($oRequest->quote_id)) {
            $oQuoteHelper = Mage::helper('zipmoneypayment/quote');
            $oQuote = $oQuoteHelper->getQuote($oRequest->quote_id);
            if ($oQuote && $oQuote->getId()) {
                Mage::getSingleton('zipmoneypayment/storeScope')->setStoreId($oQuote->getStoreId());
                Mage::app()->setCurrentStore($oQuote->getStoreId());
            }
        }
    }
}