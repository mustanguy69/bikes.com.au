<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Block_Checkout_Onepage_Review_Button extends Zipmoney_ZipmoneyPayment_Block_Abstract
{
    public function __construct()
    {
        parent::__construct();
        if($this->_isZipMoneyPaymentActive() && $this->_isZipMoneyPaymentSelected() && $this->_isExpressPaymentEnabled()) {
            $this->setTemplate('zipmoney/zipmoneypayment/checkout/onepage/review/button.phtml');
        } else {
            $this->setTemplate('checkout/onepage/review/button.phtml');
        }
    }

    protected function _isExpressPaymentEnabled()
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_EXPRESS_CHECKOUT_EXPRESS_CHECKOUT_ACTIVE;
        $iExpressPayment = Mage::getStoreConfig($vPath);
        return $iExpressPayment ? true : false;
    }

    protected function _isZipMoneyPaymentSelected()
    {
        return Mage::helper('zipmoneypayment/quote')->isZipMoneyPaymentSelected();
    }

    /**
     * return redirect url for 'place order' button
     *
     * @return string
     */
    public function getExpressRedirectUrl()
    {
        /**
         * As checkout page is always secure, should also return secure url.
         */
        $vUrl = Mage::getUrl('zipmoneypayment/Express/redirect/', array('_secure' => true));
        return $vUrl;
    }
}