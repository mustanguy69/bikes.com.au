<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Block_Checkout_Cart_Abstract extends Zipmoney_ZipmoneyPayment_Block_Abstract
{
    protected function _isWidgetCartActive()
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_WIDGET_CONFIGURATION_CART_ACTIVE;
        return Mage::getStoreConfig($vPath) == 'enabled';
    }

    protected function _isExpressButtonEnabled()
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_EXPRESS_CHECKOUT_CART_EXPRESS_BUTTON_ACTIVE;
        return Mage::getStoreConfig($vPath) ? true : false;
    }

    protected function _isCurrentDesignEnabled($vDesign = 'general')
    {
        $vConfig = Mage::getStoreConfig('payment/zipmoney_widgets_onfiguration/cartdesign');
        if (!$vConfig) {
            $vConfig = 'general';       // default value
        }
        return $vConfig == $vDesign;
    }

    protected function _isCurrentPositionEnabled($vPosition = 'below')
    {
        $vConfig = Mage::getStoreConfig('payment/zipmoney_widgets_onfiguration/cartposition');
        if (!$vConfig) {
            $vConfig = 'below';       // default value
        }
        return $vConfig == $vPosition;
    }

    public function getExpressPaymentUrl()
    {
        return $this->getUrl('zipmoneypayment/Quote/checkoutExpressPayment/');
    }

    public function getWidgetUrl()
    {
        $url = $this->getWidgetUrlByType(null, 'image');
        return $url;
    }

    public function getExpressButtonUrl()
    {
        $url = $this->getWidgetUrlByType(Zipmoney_ZipmoneyPayment_Helper_Widget::WIDGET_ASSET_TYPE_CART_BUTTON, 'image');
        return $url;
    }

    public function getAdditionalText()
    {
        $vAdditionalText = $this->getValueByAssetContentType('cartbuttonlink', 'text', 'value');
        $aAsset = $this->getWidgetAsset(Zipmoney_ZipmoneyPayment_Helper_Widget::WIDGET_ASSET_TYPE_CART_BUTTON_LINK);
        $vAdditionalText = Mage::helper("zipmoneypayment/widget")->replaceHtmlToken($vAdditionalText, $aAsset);
        return $vAdditionalText;
    }

    public function isShowBasedOnConditions($vDesign, $vPosition)
    {
        if ($this->_isCurrentDesignEnabled($vDesign)
            && $this->_isCurrentPositionEnabled($vPosition)
        ) {
            return true;
        } else {
            return false;
        }
    }

    protected function _isShowExpressButton($vDesign, $vPosition)
    {
        if ($this->_isExpressCheckoutEnabled()
            && $this->_isCurrentDesignEnabled($vDesign)
            && $this->_isCurrentPositionEnabled($vPosition)
            && $this->_isExpressButtonEnabled()
        ) {
            return true;
        } else {
            return false;
        }
    }
}