<?php

class Zipmoney_ZipmoneyPayment_Block_Abstract extends Mage_Core_Block_Template {

    protected $_asset_type = null;
    public $assets_object;

    public function __construct() {
        $this->assets_object = new Varien_Object();
    }

    /**
     * Get msg icon url
     *
     * @param string $vType
     * @param bool $bIsSecure
     * @return string
     */
    public function getMsgIconUrl($vType = 'note')
    {
        switch($vType) {
            case 'error':
                $vUrl = $this->getSkinUrl('images/i_msg-error.gif');
                break;
            case 'note':
                $vUrl = $this->getSkinUrl('images/i_msg-note.gif');
                break;
            case 'success';
                $vUrl = $this->getSkinUrl('images/i_msg-success.gif');
                break;
            default:
                $vUrl = $this->getSkinUrl('images/i_msg-note.gif');
                break;
        }
        return $vUrl;
    }

    /**
     * Get ZipMoney logo url
     *
     * @param string $vType
     * @param bool $bIsSecure
     * @return string
     */
    public function getZipLogoImageUrl($vType = 'full')
    {
        if ($vType == 'full') {
            $vUrl = $this->getSkinUrl('zipmoney/images/zipmoney-logo-full.png');
        } else {
            $vUrl = $this->getSkinUrl('zipmoney/images/zipmoney-logo.png');
        }
        return $vUrl;
    }

    public function getAssets()
    {
        $satandard = Mage::getBaseUrl() . "media/zipmoney/standard.png?" . time();
        $promotion = Mage::getBaseUrl() . "media/zipmoney/promotion.png?" . time();

        $standard_frontside = Mage::getBaseUrl() . "media/zipmoney/standard-frontside.png?" . time();
        $standard_backside = Mage::getBaseUrl() . "media/zipmoney/standard-backside.png?" . time();
        $standard_pop_up = new Varien_Object();
        $standard_pop_up->setPage1($standard_frontside);
        $standard_pop_up->setPage2($standard_backside);

        $promotion_frontside = Mage::getBaseUrl() . "media/zipmoney/promotion-frontside.png?" . time();
        $promotion_backside = Mage::getBaseUrl() . "media/zipmoney/promotion-backside.png?" . time();
        $promotion_pop_up = new Varien_Object();
        $promotion_pop_up->setPage1($promotion_frontside);
        $promotion_pop_up->setPage2($promotion_backside);

        $this->assets_object->setStandardUrl($satandard);
        $this->assets_object->setPromotionUrl($promotion);
        $this->assets_object->setStandardInfobox($standard_pop_up);
        $this->assets_object->setPromotionInfobox($promotion_pop_up);
    }

    /**
     * Get ZipMoney marketing widget asset
     *
     * @param null $assetType
     * @return array|null
     */
    public function getWidgetAsset($assetType = null)
    {
        if(!$assetType) {
            $assetType = $this->_asset_type;
        }
        if(!$assetType) {
            return null;
        }
        $assetArray = Mage::helper('zipmoneypayment/widget')->getAssetByType($assetType);
        return $assetArray;
    }

    /**
     * Get widget content url
     *
     * @param null $vAssetType
     * @param null $vContentType
     * @return null
     */
    public function getWidgetUrlByType($vAssetType = null, $vContentType = null)
    {
        $vUrl = $this->getValueByAssetContentType($vAssetType, $vContentType, 'url');
        return $vUrl;
    }

    /**
     * Get all content urls of the widget
     *
     * @param null $vAssetType
     * @param null $vContentType
     * @return array|null
     */
    public function getMultipleWidgetUrlsByType($vAssetType = null, $vContentType = null)
    {
        $aUrls = $this->getMultipleValuesByAssetContentType($vAssetType, $vContentType, 'url');
        return $aUrls;
    }

    /**
     * Get raw value of the first asset by assetType, contentType and valueType
     *
     * @param $vAssetType
     * @param $vContentType
     * @param $vValueType
     * @return null
     */
    public function getValueByAssetContentType($vAssetType, $vContentType, $vValueType)
    {
        $vValue = null;
        $aAssetArray = $this->getWidgetAsset($vAssetType);
        if (is_null($aAssetArray) || empty($aAssetArray)) {
            return null;
        }

        foreach($aAssetArray as $aAsset) {
            if($vContentType) {
                if (!isset($aAsset['content_type']) || $aAsset['content_type'] != $vContentType) {
                    continue;
                }
            }
            $vValue = isset($aAsset[$vValueType]) ? $aAsset[$vValueType] : null;
            break;
        }
        return $vValue;
    }

    /**
     * Get raw values of assets by assetType, contentType and valueType
     *
     * @param $vAssetType
     * @param $vContentType
     * @param $vValueType
     * @return array|null
     */
    public function getMultipleValuesByAssetContentType($vAssetType, $vContentType, $vValueType)
    {
        $aValues = array();
        $aAssetArray = $this->getWidgetAsset($vAssetType);
        if (is_null($aAssetArray) || empty($aAssetArray)) {
            return null;
        }

        foreach($aAssetArray as $aAsset) {
            if($vContentType) {
                if (!isset($aAsset['content_type']) || $aAsset['content_type'] != $vContentType) {
                    continue;
                }
            }
            $vValue = isset($asset[$vValueType]) ? $asset[$vValueType] : null;
            $aValues[] = $vValue;
        }
        return $aValues;
    }

    /**
     * Check if Zipmoney is enabled from config
     *
     * @return bool
     */
    protected function _isZipMoneyPaymentActive()
    {
        // If API keys are empty, should regard the module as disabled.
        $iZipmoneyPayment = Mage::getStoreConfig(Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_ACTIVE);
        if ($iZipmoneyPayment) {
            $iMerchantId = Mage::getStoreConfig(Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_ID);
            $vMerchantKey = Mage::getStoreConfig(Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_KEY);
            if ($iMerchantId && $vMerchantKey) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if Zipmoney Express Checkout is enabled from config
     *
     * @return bool
     */
    protected function _isExpressCheckoutEnabled()
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_EXPRESS_CHECKOUT_EXPRESS_CHECKOUT_ACTIVE;
        return Mage::getStoreConfig($vPath) ? true : false;
    }

    protected function _isAllowedToCheckout()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            return true;
        } else {
            $oQuote = Mage::getSingleton('checkout/session')->getQuote();
            if (Mage::helper('checkout')->isAllowedGuestCheckout($oQuote)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Get the config of isIframeFlow
     *
     * @return bool
     */
    protected function _isIframeFlowEnabled()
    {
        return Mage::helper('zipmoneypayment')->isIframeCheckoutEnabled();
    }

    /**
     * Return ajax request url for iframe for PDP and cart page
     */
    public function getIframeRedirectUrl()
    {
        /**
         * if PDP/cart are using secure url, then return secure ajax request url.
         */
        $bIsSecure = Mage::app()->getFrontController()->getRequest()->isSecure();
        $vUrl = Mage::getUrl('zipmoneypayment/Quote/getRedirectUrl', array('_secure' => $bIsSecure));
        return $vUrl;
    }
}