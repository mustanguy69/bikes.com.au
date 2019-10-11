<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_QuoteController extends Zipmoney_ZipmoneyPayment_Controller_Express_Abstract
{

    protected $_checkoutType = 'zipmoneypayment/express_checkout';

    /**
     * Check quote details, save updated shipping address, get shipping estimate rates, and get latest quote details
     */
    public function getShippingMethodsAction()
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $oQuoteHelper = Mage::helper('zipmoneypayment/quote');
        $oApiHelper = Mage::helper('zipmoneypayment/api');
        $oRequest = $this->_getRequestBody();
        $this->_logging->writeLog($oHelper->__('Calling getShippingMethodsAction'), Zend_Log::DEBUG);

        if (!$this->_isApiKeysValid($oRequest)) {
            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
            $this->_errorHandling(null, $oHelper->__('Incorrect API keys.'), null, false, true);
            return;
        }
        $iQuoteId = $oRequest->quote_id;

        try {
            if (!isset($oRequest->shipping_address) || !$oRequest->shipping_address) {
                $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
                $this->_errorHandling(null, $oHelper->__('No shipping address provided.'), null, false, true);
                return;
            }
            if (!$this->_checkShippingAddressAvailability($oRequest)) {
                $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
                $this->_errorHandling(null, $oHelper->__('Not enough shipping address details provided.'), null, false, true);
                return;
            }

            $oQuote = $oQuoteHelper->getQuote($iQuoteId);       // Get the quote from database instead of session
            if (!$oQuote || !$oQuote->getId()) {
                $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
                $this->_errorHandling(null, $oHelper->__('Can not load the quote (id:%s).', $iQuoteId), null, false, true);
                return;
            }

            /**
             * activate quote if needed
             */
            $oQuoteHelper->activateQuote($oQuote);
            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
            /**
             * Set shipping/billing address to quote
             */
            if (!$this->_saveQuoteAddress($oQuote->getShippingAddress(), $oRequest)) {
                /**
                 * force to set CollectShippingRates to true for shipping address, so that it will get the shipping rates with the latest config of shipping methods
                 * scenario: one of the shipping methods (e.g. free shipping) is disabled, and there's no change in the shipping address in the request
                 *      it would still get last shipping rates with CollectShippingRates false
                 */
                $oQuote->getShippingAddress()->setCollectShippingRates(true)->save();
            }
            $this->_saveQuoteAddress($oQuote->getBillingAddress(), $oRequest);

            /**
             * Get available shipping methods
             */
            $aRawData = array(
                'quote' => $oQuote,
                'request' => $oRequest,
                );
            $vAction = Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_GET_SHIPPING_METHODS;
            $aResponseData = $oApiHelper->prepareDataForResponse($vAction, $aRawData);
            if(!$aResponseData) {
                $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
                $this->_errorHandling(null, $oHelper->__('Failed to get shipping methods.'), null, false, true);
                return;
            }
            $this->_setResponseData($aResponseData, Mage_Api2_Model_Server::HTTP_OK);
            $this->_logging->writeLog($oHelper->__('Successful to get shipping methods.'), Zend_Log::INFO);
            if (isset($aResponseData['shipping_address']['options'])) {
                $this->_logging->writeLog(json_encode($aResponseData['shipping_address']['options']), Zend_Log::DEBUG);
            } else {
                $this->_logging->writeLog($oHelper->__('No shipping methods returned.'), Zend_Log::NOTICE);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::ERR);
            $this->_errorHandling($e, $oHelper->__('Can not get shipping methods.'), null, false, true);
        } catch (Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::ERR);
            $this->_errorHandling($e, $oHelper->__('Can not get shipping methods.'), null, false, true);
        }
    }

    /**
     * Retrieve shopping cart model object
     *
     * @return Mage_Checkout_Model_Cart
     */
    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    /**
     * Set redirect into response
     *
     * @param   string $path
     * @param   array $arguments
     * @return  Mage_Core_Controller_Varien_Action
     */
    protected function _redirect($path, $arguments = array())
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $this->_logging->writeLog($oHelper->__('Redirected to ') . $path, Zend_Log::DEBUG);
        return $this->setRedirectWithCookieCheck($path, $arguments);
    }

    /**
     * Redirect to zipMoney or cart page (may be from PDP or Cart)
     *
     * @param $vCheckoutSource
     * @param bool $bToCartPage     true - go to cart page; false - go to zipMoney
     */
    protected function _expressPaymentRedirect($vCheckoutSource, $bToCartPage = false)
    {
        if($bToCartPage) {
            $this->_redirect('checkout/cart');
            return;
        }

        $oHelper = Mage::helper('zipmoneypayment');
        try {
            $vRedirectUrl = $this->getRedirectUrl($vCheckoutSource);
            if($vRedirectUrl) {
                $this->_logging->writeLog($oHelper->__('Redirect URL is ') . $vRedirectUrl, Zend_Log::DEBUG);
                $this->_redirectUrl($vRedirectUrl);
                return;
            } else {
                $this->_errorHandling(null, $oHelper->__('Can not get the redirect URL.'), null, false, true);
                $this->_redirect('checkout/cart');
                return;
            }
        } catch (Zipmoney_ZipmoneyPayment_Exception $e) {
            $this->_errorHandling($e, $oHelper->__('Can not redirect to zipMoney.'), null, true, true, Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR, false, 'checkout/cart');
        } catch (Exception $e) {
            $this->_errorHandling($e, $oHelper->__('Can not redirect to zipMoney.'), null, true, true, Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR, false, 'checkout/cart');
        }
    }

    /**
     * Initialize product instance from request data
     *
     * @return Mage_Catalog_Model_Product || false
     */
    protected function _initProduct()
    {
        $iProductId = (int) $this->getRequest()->getParam('product');
        if ($iProductId) {
            $oProduct = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($iProductId);
            if ($oProduct->getId()) {
                return $oProduct;
            }
        }
        return false;
    }

    /**
     * Express button on PDP. Redirect to zipMoney page.
     */
    public function addToCartExpressPaymentAction()
    {
        $vCheckoutSource = Zipmoney_ZipmoneyPayment_Helper_Quote::QUOTE_CHECKOUT_SOURCE_PRODUCT;
        $this->_expressPaymentRedirect($vCheckoutSource);
    }

    /**
     * Express button on cart page. Redirect to zipMoney page.
     */
    public function checkoutExpressPaymentAction()
    {
        $vCheckoutSource = Zipmoney_ZipmoneyPayment_Helper_Quote::QUOTE_CHECKOUT_SOURCE_CART;
        $this->_expressPaymentRedirect($vCheckoutSource);
    }


    /**
     * Cancel quote, and redirect to cart page.
     */
    public function cancelQuoteAction()
    {
        $vAction = Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_CANCEL_QUOTE;
        $oHelper = Mage::helper('zipmoneypayment');
        $oQuoteHelper = Mage::helper('zipmoneypayment/quote');
        $oRequest = $this->getRequest();
        $this->_logging->writeLog($oHelper->__('Calling cancelQuoteAction'), Zend_Log::DEBUG);
        try {
            $iQuoteId       = $oRequest->getParam('quote_id');
            $vToken         = $oRequest->getParam('token');

            $oQuote = $oQuoteHelper->getQuote($iQuoteId);
            if (!$this->_checkRequestAvailability($vAction, $oRequest, $oQuote, $vToken)) {
                $this->_logging->writeLog(json_encode($oRequest->getParams()), Zend_Log::DEBUG);
                $vMessage = $oHelper->__('The request is unavailable.');
                throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
            }
            $oQuote->setIsActive(0)->save();
            $this->_expressPaymentRedirect(null, true);
            $this->_logging->writeLog($oHelper->__('Successful to cancel quote.'), Zend_Log::INFO);
        } catch (Mage_Core_Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest->getParams()), Zend_Log::DEBUG);
            $this->_errorHandling($e, $oHelper->__('An error occurred during cancelling quote.'), null, false, true, Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR, true);
            return;
        } catch (Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest->getParams()), Zend_Log::DEBUG);
            $this->_errorHandling($e, $oHelper->__('An error occurred during cancelling quote.'), null, false, true, Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR, true);
        }
    }

    public function declineAction()
    {
        $vAction = Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_DECLINED_APP;
        $oHelper = Mage::helper('zipmoneypayment');
        $oQuoteHelper = Mage::helper('zipmoneypayment/quote');
        $oRequest = $this->getRequest();
        $this->_logging->writeLog($oHelper->__('Calling declineAction'), Zend_Log::DEBUG);
        $vMessage = $oHelper->__('The application has been declined by zipMoney.');

        try {
            $iQuoteId       = $oRequest->getParam('quote_id');
            $vToken         = $oRequest->getParam('token');
            $oQuote = $oQuoteHelper->getQuote($iQuoteId);
            if (!$this->_checkRequestAvailability($vAction, $oRequest, $oQuote, $vToken)) {
                $this->_logging->writeLog(json_encode($oRequest->getParams()), Zend_Log::DEBUG);
                $vMessage = $oHelper->__('The request is unavailable.');
                throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest->getParams()), Zend_Log::DEBUG);
            $this->_errorHandling($e, $oHelper->__('An error occurred during declining application.'), null, false, true, Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR, true);
            return;
        } catch (Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest->getParams()), Zend_Log::DEBUG);
            $this->_errorHandling($e, $oHelper->__('An error occurred during declining application.'), null, false, true, Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR, true);
            return;
        }
        $this->_getSession()->addError(Mage::helper('core')->escapeHtml($vMessage));
        $this->_redirect('checkout/cart');
    }

    /**
     * Get latest quote details without checking quote
     */
    public function getQuoteDetailsAction()
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $oQuoteHelper = Mage::helper('zipmoneypayment/quote');
        $oApiHelper = Mage::helper('zipmoneypayment/api');

        $oRequest = $this->_getRequestBody();
        $this->_logging->writeLog($oHelper->__('Calling getQuoteDetailsAction'), Zend_Log::DEBUG);
        if (!$this->_isApiKeysValid($oRequest)) {
            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
            $this->_errorHandling(null, $oHelper->__('Incorrect API keys.'), null, false, true);
            return;
        }
        $iQuoteId = $oRequest->quote_id;

        try {
            $oQuote = $oQuoteHelper->getQuote($iQuoteId);
            if (!$oQuote || !$oQuote->getId()) {
                $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
                $this->_errorHandling(null, $oHelper->__('Can not load the quote (id: %s).', $iQuoteId), null, false, true);
                return;
            }

            $aRawData = array(
                'quote' => $oQuote,
                'request' => $oRequest,
                );
            $vAction = Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_GET_QUOTE_DETAILS;
            $aResponseData = $oApiHelper->prepareDataForResponse($vAction, $aRawData);
            if(!$aResponseData) {
                $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
                $this->_errorHandling(null, $oHelper->__('Failed to get quote details.'), null, false, true);
                return;
            }
            $this->_setResponseData($aResponseData, Mage_Api2_Model_Server::HTTP_OK);
            $this->_logging->writeLog($oHelper->__('Successful to get quote details.'), Zend_Log::INFO);
        } catch (Mage_Core_Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::ERR);
            $this->_errorHandling($e, $oHelper->__('Can not get quote details.'), null, false, true);
        } catch (Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::ERR);
            $this->_errorHandling($e, $oHelper->__('Can not get quote details.'), null, false, true);
        }
    }

    /**
     * Check quote details, update shipping method of the quote based on zip request, re-calculate quote totals, and get latest quote details
     */
    public function confirmShippingMethodAction()
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $oApiHelper = Mage::helper('zipmoneypayment/api');

        $this->_logging->writeLog($oHelper->__('Calling confirmShippingMethodAction'), Zend_Log::DEBUG);

        $oRequest = $this->_getRequestBody();
        if (!$this->_isApiKeysValid($oRequest)) {
            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
            $this->_errorHandling(null, $oHelper->__('Incorrect API keys.'), null, false, true);
            return;
        }
        $iQuoteId = $oRequest->quote_id;

        try {
            $vShippingMethodCode = isset($oRequest->shipping_address->selected_option_id) ?  $oRequest->shipping_address->selected_option_id : null;
            if (!$vShippingMethodCode) {
                $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
                $this->_errorHandling(null, $oHelper->__('No shipping method provided.'), null, false, true);
                return;
            }

            /**
             * save shipping method to quote
             */
            $oCheckout = Mage::helper('zipmoneypayment/quote')->getExpressCheckout($iQuoteId);
            $oQuote = $oCheckout->updateShippingMethod($vShippingMethodCode);
            $oQuote->save();

            $aRawData = array(
                'quote' => $oQuote,
                'request' => $oRequest,
            );

            $vAction = Zipmoney_ZipmoneyPayment_Helper_Api::ACTION_RESPONSE_TYPE_CONFIRM_SHIPPING_METHOD;
            $aResponseData = $oApiHelper->prepareDataForResponse($vAction, $aRawData);
            if(!$aResponseData) {
                $this->_logging->writeLog(json_encode($oRequest), Zend_Log::DEBUG);
                $this->_errorHandling(null, $oHelper->__('Failed to confirm shipping method.'), null, false, true);
                return;
            }
            $this->_setResponseData($aResponseData, Mage_Api2_Model_Server::HTTP_OK);
            $this->_logging->writeLog($oHelper->__('Successful to confirm shipping method.'), Zend_Log::INFO);
        } catch (Mage_Core_Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::ERR);
            $this->_errorHandling($e, $oHelper->__('Can not confirm shipping method.'), null, false, true);
        } catch (Exception $e) {
            $this->_logging->writeLog(json_encode($oRequest), Zend_Log::ERR);
            $this->_errorHandling($e, $oHelper->__('Can not confirm shipping method.'), null, false, true);
        }
    }

    /**
     * Get zipMoney redirect url
     */
    public function getRedirectUrlAction()
    {
        $vCheckoutSource = Zipmoney_ZipmoneyPayment_Helper_Quote::QUOTE_CHECKOUT_SOURCE_CART;
        $oHelper = Mage::helper('zipmoneypayment');

        if (!$this->getRequest()->isAjax()) {
            $this->_errorHandling(null, $oHelper->__('Can not accept the request.'), null, false, true, Mage_Api2_Model_Server::HTTP_NOT_ACCEPTABLE);
            return;
        }

        /**
         * check currently selected payment method
         */
        if (!Mage::helper('zipmoneypayment/quote')->isZipMoneyPaymentSelected()) {
            $oQuote = Mage::getSingleton('checkout/session')->getQuote();
            $vMethodCode = Zipmoney_ZipmoneyPayment_Helper_Data::PAYMENT_METHOD_CODE;
            $oQuote->getPayment()->setMethod($vMethodCode)->save();
            $this->_logging->writeLog($oHelper->__('Set payment method to %s', $vMethodCode), Zend_Log::INFO);
        }

        try {
            $vRedirectUrl = $this->getRedirectUrl($vCheckoutSource);
            if($vRedirectUrl) {
                $this->_logging->writeLog($oHelper->__('Redirect URL is ') . $vRedirectUrl, Zend_Log::DEBUG);
                $aData = array(
                    'url'       => $vRedirectUrl,
                );
                $this->_setResponseData($aData, Mage_Api2_Model_Server::HTTP_OK);
                return;
            } else {
                $this->_errorHandling(null, $oHelper->__('Can not get the redirect URL.'), null, false, true);
                return;
            }
        } catch (Zipmoney_ZipmoneyPayment_Exception $e) {
            $this->_errorHandling($e, $oHelper->__('Can not redirect to zipMoney.'), null, true, true, Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR, false, 'checkout/cart');
        } catch (Exception $e) {
            $this->_errorHandling($e, $oHelper->__('Can not redirect to zipMoney.'), null, true, true, Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR, false, 'checkout/cart');
        }
    }
}