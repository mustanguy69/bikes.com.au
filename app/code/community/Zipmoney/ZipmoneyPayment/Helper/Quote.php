<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Helper_Quote extends Mage_Core_Helper_Abstract
{
    const QUOTE_CHECKOUT_SOURCE_PRODUCT     = 'product';
    const QUOTE_CHECKOUT_SOURCE_CART        = 'cart';
    const QUOTE_CHECKOUT_SOURCE_CHECKOUT    = 'checkout';

     /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_oSessionQuote = false;

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_oDbQuote = false;

    /**
     * @var Zipmoney_ZipmoneyPayment_Model_Express_Checkout
     */
    protected $_checkout = false;

    /**
     * Checkout mode type
     * @var string
     */
    protected $_checkoutType = 'zipmoneypayment/express_checkout';

    /**
     * Return checkout session object
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    private function __getSessionQuote()
    {
        if (!$this->_oSessionQuote) {
            $this->_oSessionQuote = $this->getCheckoutSession()->getQuote();
        }
        return $this->_oSessionQuote;
    }

    private function __getDbQuote($iQuoteId)
    {
        if($iQuoteId) {
            /**
             * Not using Mage::getModel('sales/quote')->load() is because it will be loading the quote from current store.
             * If it is an API call, the current store is based on the current url, which may be a different store from the quote.
             */
            $oQuote = Mage::getModel('sales/quote')->getCollection()->addFieldToFilter("entity_id", $iQuoteId)->getFirstItem();
            if($oQuote && $oQuote->getId()) {
                $this->_oDbQuote = $oQuote;
            } else {
                $this->_oDbQuote = false;
            }
        }
        return $this->_oDbQuote;
    }

    /**
     * @param null $iQuoteId
     * @return bool|Mage_Sales_Model_Quote|Varien_Object
     */
    public function getQuote($iQuoteId = null)
    {
        if($iQuoteId) {
            return $this->__getDbQuote($iQuoteId);
        } else {
            return $this->__getSessionQuote();
        }
    }

    protected function _initCheckout($iQuoteId = null)
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $oLogging = Mage::helper('zipmoneypayment/logging');
        $oQuote = $this->getQuote($iQuoteId);
        if (!$oQuote) {
            Mage::throwException($oHelper->__('The quote does not exist.'));
        }
        $bHasItems = $oQuote->hasItems();
        $bHasError = $oQuote->getHasError();
        if (!$bHasItems || $bHasError) {
            $vMessage = $oHelper->__('Unable to initialize Express Checkout.');
            if ($bHasItems) {
                $vMessage = $vMessage. $oHelper->__(' The quote has no items.');
            }
            if ($bHasError) {
                foreach ($oQuote->getErrors() as $vErr) {
                    $vMessage = $vMessage . ' [' . $oHelper->__($vErr->getType()) .']' . $oHelper->__($vErr->getCode());
                }
            }
            $oLogging->writeLog($oHelper->__('Activated quote ' . $oQuote->getId() . '.'), Zend_Log::WARN);
            Mage::throwException($vMessage);
        }
        $this->_checkout = Mage::getModel($this->_checkoutType, array(
            'quote'  => $oQuote,
        ));

        return $this->_checkout;
    }

    public function getExpressCheckout($iQuoteId = null)
    {
        if (!$this->_checkout) {
            $this->_initCheckout($iQuoteId);
        }
        if ($iQuoteId && $this->_checkout->getQuote()->getId() != $iQuoteId) {
            $this->_initCheckout($iQuoteId);
        }

        return $this->_checkout;
    }

    /**
     * Call Zip Api to request redirect url
     * @param $oQuote
     * @param $vCheckoutSource
     * @return null
     * @throws Mage_Core_Exception
     */
    public function getRedirectUrl($oQuote, $vCheckoutSource)
    {
        $oApiHelper = Mage::helper('zipmoneypayment/api');

        $oQuote->collectTotals();
        if (!$oQuote->getGrandTotal() && !$this->_quote->hasNominalItems()) {
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $this->__('Cannot process the order due to zero amount.'));
        }
        $oQuote->reserveOrderId()->save();

        $aRawData = array(
            'quote' => $oQuote,
            'checkout_source' => $vCheckoutSource
        );
        $vEndpoint = ZipMoney_ApiSettings::API_TYPE_QUOTE_QUOTE;
        $aRequestData = $oApiHelper->prepareDataForZipRequest($vEndpoint, $aRawData);
        $oResponse = $oApiHelper->callZipApi($vEndpoint, $aRequestData);
        $oLogging = Mage::helper('zipmoneypayment/logging');
        $oLogging->writeLog(json_encode($aRequestData), Zend_Log::WARN);

        $aResponseData = $oApiHelper->getResponseData($oResponse);
        if(!$aResponseData || !is_array($aResponseData)) {
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $this->__('Cannot get redirect URL from zipMoney.'));
        }

        if(!isset($aResponseData['merchant_id'])
            || !isset($aResponseData['merchant_key'])
            || !$oApiHelper->isApiKeysValid($aResponseData['merchant_id'], $aResponseData['merchant_key'])) {
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $this->__('Incorrect API keys in response.'));    // response api key are invalid
        }
        if(isset($aResponseData['redirect_url'])) {
            return $aResponseData['redirect_url'];
        }
        return null;
    }

    /**
     * @param $oQuote
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function activateQuote($oQuote)
    {
        /**@var Mage_Sales_Model_Quote $oQuote*/
        if ($oQuote && $oQuote->getId()) {
            if (!$oQuote->getIsActive()) {
                $vOrderIncId = $oQuote->getReservedOrderId();
                if ($vOrderIncId) {
                    $oOrder = Mage::getModel('sales/order')->loadByIncrementId($vOrderIncId);
                    if ($oOrder && $oOrder->getId()) {
                        $vMessage = $this->__('Can not activate the quote. It has already been converted to order.');
                        throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
                    }
                }
                $oQuote->setIsActive(1)->save();
                $oLogging = Mage::helper('zipmoneypayment/logging');
                $oLogging->writeLog($this->__('Activated quote ' . $oQuote->getId() . '.'), Zend_Log::WARN);
                return true;
            }
        }
        return false;
    }

    /**
     * @param $oQuote
     * @return bool
     */
    public function deactivateQuote($oQuote)
    {
        /**@var Mage_Sales_Model_Quote $oQuote*/
        if ($oQuote && $oQuote->getId()) {
            if ($oQuote->getIsActive()) {
                $oQuote->setIsActive(0)->save();
                $oLogging = Mage::helper('zipmoneypayment/logging');
                $oLogging->writeLog($this->__('Deactivated quote ' . $oQuote->getId() . '.'), Zend_Log::WARN);
                return true;
            }
        }
        return false;
    }

    /**
     * Check session quote if zipMoney is selected as payment method
     *
     * @return bool
     */
    public function isZipMoneyPaymentSelected() {
        $oQuote = Mage::getSingleton('checkout/session')->getQuote();
        $vPaymentCode = $oQuote->getPayment()->getMethod();
        if(Zipmoney_ZipmoneyPayment_Helper_Data::PAYMENT_METHOD_CODE == $vPaymentCode) {
            return true;
        }
        return false;
    }

    public function needsShipping($quote)
    {
        $oLogging = Mage::helper('zipmoneypayment/logging');
        $items    = $quote->getItemsCollection();
        $needs_shipping = true;

        if($items->count()){        
            $needs_shipping = false;
            foreach ($quote->getItemsCollection() as $_item) {
                if ($_item->getParentItemId()) {
                    continue;
                }
                if (!$_item->getProduct()->isVirtual()) {
                    $needs_shipping = true;
                    continue;
                }
            }        
            $oLogging->writeLog($this->__('Quote needs shipping? ' . ($needs_shipping?"Yes":"No")), Zend_Log::INFO);
        }

     return $needs_shipping;
    }
}