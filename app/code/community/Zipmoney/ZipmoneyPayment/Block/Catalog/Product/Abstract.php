<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Block_Catalog_Product_Abstract extends Zipmoney_ZipmoneyPayment_Block_Abstract
{
    protected function _isWidgetProductActive()
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_WIDGET_CONFIGURATION_PRODUCT_ACTIVE;
        return Mage::getStoreConfig($vPath) == 'enabled';
    }

    protected function _isExpressButtonEnabled()
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_EXPRESS_CHECKOUT_PRODUCT_EXPRESS_BUTTON_ACTIVE;
        $iExpressButtonEnabled = Mage::getStoreConfig($vPath);
        return $iExpressButtonEnabled ? true : false;
    }

    protected function _isCurrentDesignEnabled($vDesign = 'general')
    {
        $vConfig = Mage::getStoreConfig('payment/zipmoney_widgets_onfiguration/productdesign');
        if (!$vConfig) {
            $vConfig = 'general';       // default value
        }
        return $vConfig == $vDesign;
    }

    protected function _isCurrentPositionEnabled($vPosition = 'button')
    {
        $vConfig = Mage::getStoreConfig('payment/zipmoney_widgets_onfiguration/productposition');
        if (!$vConfig) {
            $vConfig = 'button';       // default value
        }
        return $vConfig == $vPosition;
    }

    protected function _isProductWidgetPriceAvailable()
    {
        $fTotal = Mage::getModel('catalog/product')->load(Mage::registry('current_product')->getId())->getPrice();
        $fConfig = Mage::getStoreConfig('payment/zipmoney_widgets_onfiguration/productwidgetprice');
        if (!$fConfig) {
            $fConfig = 0;            // default value
        }
        return $fConfig <= $fTotal;
    }

    /**
     * Retrieve current product model
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        if (!Mage::registry('product') && $this->getProductId()) {
            $product = Mage::getModel('catalog/product')->load($this->getProductId());
            Mage::register('product', $product);
        }
        return Mage::registry('product');
    }

    public function getExpressPaymentUrl()
    {
        return $this->getUrl('zipmoneypayment/Quote/addToCartExpressPayment/');
    }

    public function getWidgetUrl()
    {
        $url = $this->getWidgetUrlByType(null, 'image');
        return $url;
    }

    public function getExpressButtonUrl()
    {
        $url = $this->getWidgetUrlByType(Zipmoney_ZipmoneyPayment_Helper_Widget::WIDGET_ASSET_TYPE_PRODUCT_BUTTON, 'image');
        return $url;
    }

    public function getAdditionalText()
    {
        $vAdditionalText = $this->getValueByAssetContentType('productbuttonlink', 'text', 'value');
        $aAsset = $this->getWidgetAsset(Zipmoney_ZipmoneyPayment_Helper_Widget::WIDGET_ASSET_TYPE_PRODUCT_BUTTON_LINK);
        $vAdditionalText = Mage::helper("zipmoneypayment/widget")->replaceHtmlToken($vAdditionalText, $aAsset);
        return $vAdditionalText;
    }

    public function isShowBasedOnConditions($vDesign, $vPosition)
    {
        if ($this->_isCurrentDesignEnabled($vDesign)
            && $this->_isCurrentPositionEnabled($vPosition)
            && $this->_isProductWidgetPriceAvailable()
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

    public function getReturnUrl()
    {
        $vReturnUrl = '';
        if ($this->_isIframeFlowEnabled()) {
            $vCurrentUrl = $this->helper('core/url')->getCurrentUrl();
            $vReturnUrl = $vCurrentUrl;
            if (Mage::app()->getRequest()->getParam('zip') != 'iframe') {
                $aUrl = parse_url($vCurrentUrl);
                if (!isset($aUrl['zip'])) {
                    $vReturnUrl = $vCurrentUrl . (parse_url($vCurrentUrl, PHP_URL_QUERY) ? '&' : '?') . 'zip=iframe';
                }
            }
        }
        return $vReturnUrl;
    }

    public function showIframeCheckout()
    {
        if (Mage::app()->getRequest()->getParam('zip') == 'iframe') {
            return 1;
        }
        return 0;
    }
}