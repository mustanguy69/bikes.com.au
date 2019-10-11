<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Helper_Widget extends Mage_Core_Helper_Abstract
{
    const WIDGET_ASSET_TYPE_PRODUCT             = 'productwidget';
    const WIDGET_ASSET_TYPE_CART                = 'cartwidget';
    const WIDGET_ASSET_TYPE_TERMS_DIALOG        = 'termsdialog';
    const WIDGET_ASSET_TYPE_CHECKOUT_DIALOG     = 'checkoutdialog';
    const WIDGET_ASSET_TYPE_CART_BUTTON         = 'cartbutton';
    const WIDGET_ASSET_TYPE_PRODUCT_BUTTON      = 'productbutton';
    const WIDGET_ASSET_TYPE_CART_BUTTON_LINK    = 'cartbuttonlink';
    const WIDGET_ASSET_TYPE_PRODUCT_BUTTON_LINK = 'productbuttonlink';
    const WIDGET_ASSET_TYPE_STRIP_BANNER        = 'stripbanner';
    const WIDGET_ASSET_TYPE_SIDE_BANNER         = 'sidebanner';

    protected $_aDefaultAssetValues = null;
    protected $_aAssets = null;

    /**
     * Get default asset values from asset_values
     *
     * @return mixed|null
     */
    public function getDefaultAssetValues()
    {
        if(!$this->_aDefaultAssetValues) {
            $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_ASSET_VALUES;
            $this->_aDefaultAssetValues = unserialize(Mage::getStoreConfig($vPath));
        }
        return $this->_aDefaultAssetValues;
    }

    /**
     * Get all assets
     *
     * @return mixed|null
     */
    public function getAllAssets()
    {
        if(!$this->_aAssets) {
            $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_ASSETS;
            $this->_aAssets = unserialize(Mage::getStoreConfig($vPath));
        }
        return $this->_aAssets;
    }

    private function __cmp($a, $b)
    {
        if (!isset($a["sequence"])) {
            $a["sequence"] = '0';
        }
        if (!isset($b["sequence"])) {
            $b["sequence"] = '0';
        }
        return strcmp($a["sequence"], $b["sequence"]);
    }

    /**
     * Get specific type asset array (from database), and sort by sequence
     *
     * @param $vAssetType
     * @return array
     */
    public function getAssetByType($vAssetType)
    {
        $aAssetArray = array();
        $aAssets = $this->getAllAssets();
        if (!$aAssets) {
            return $aAssetArray;
        }
        foreach($aAssets as $aAsset) {
            if(!isset($aAsset['type']) || $aAsset['type'] != $vAssetType) {
                continue;
            }
            $aAssetArray[] = $aAsset;
        }
        usort($aAssetArray, array("Zipmoney_ZipmoneyPayment_Helper_Widget", '__cmp'));
        return $aAssetArray;
    }

    /**
     * Get asset values (values in asset overwrite default values stored in asset_values)
     *
     * @param $aDefaultAssetValues
     * @param $aAssetValues
     * @return array
     */
    protected function getReplaceValues($aDefaultAssetValues, $aAssetValues)
    {
        if(!$aAssetValues || !is_array($aAssetValues)) {
            $aAssetValues = array();
        }
        if(!$aDefaultAssetValues || !is_array($aDefaultAssetValues)) {
            $aDefaultAssetValues = array();
        }

        if(!$aAssetValues || count($aAssetValues) <= 0) {
            $aReplaceValues = $aDefaultAssetValues;
        } else {
            $aValuesNotInDefault = array();
            foreach ($aAssetValues as $aValue) {
                $bIsOverwrite = false;
                foreach ($aDefaultAssetValues as $vKey => $vDefaultValue) {
                    if ($aValue['key'] == $vDefaultValue['key']) {
                        $aDefaultAssetValues[$vKey] = array_merge($vDefaultValue, $aValue);
                        $bIsOverwrite = true;
                        break;
                    }
                }
                if (!$bIsOverwrite) {
                    $aValuesNotInDefault[] = $aValue;
                }
            }
            $aReplaceValues = array_merge($aDefaultAssetValues, $aValuesNotInDefault);
        }

        return $aReplaceValues;
    }

    /**
     * Replace tokens in html with asset values
     *
     * @param $vHtml
     * @param $aAsset
     * @return mixed
     */
    public function replaceHtmlToken($vHtml, $aAsset)
    {
        $aDefaultAssetValues = $this->getDefaultAssetValues();
        if(!$aDefaultAssetValues || !is_array($aDefaultAssetValues) || count($aDefaultAssetValues) <= 0) {
            return $vHtml;
        }
        $aAssetValues = isset($aAsset['asset_values']) ? $aAsset['asset_values'] : array();
        $aReplaceValues = $this->getReplaceValues($aDefaultAssetValues, $aAssetValues);
        foreach($aReplaceValues as $aValue) {
            if(!isset($aValue['key']) || !isset($aValue['value'])) {
                continue;
            }
            $vHtml = str_replace($aValue['key'], $aValue['value'], $vHtml);
        }
        return $vHtml;
    }

    /**
     * Get all pages of an asset by asset type
     *
     * @param $vAssetType
     * @return array
     */
    public function getPagesByAssetType($vAssetType)
    {
        $aPages = array();

        $aAssetList = $this->getAssetByType($vAssetType);
        if (!$aAssetList) {
            return $aPages;
        }
        $iNum = 0;
        foreach($aAssetList as $aAsset) {
            $vUrl = isset($aAsset['url']) ? $aAsset['url'] : null;
            $vHtml = Mage::helper('zipmoneypayment')->getUrlContent($vUrl);
            $vHtml = str_replace("\r", "", $vHtml);
            $vHtml = str_replace("\n", "", $vHtml);
            $vHtml = $this->replaceHtmlToken($vHtml, $aAsset);
            $aPages[$iNum] = $vHtml;
            $iNum++;
        }
        return $aPages;
    }

    /**
     * Get all pages of Terms & Conditions
     *
     * @return array
     */
    public function getAllTermsPages()
    {
        return $this->getPagesByAssetType(self::WIDGET_ASSET_TYPE_TERMS_DIALOG);
    }

    /**
     * Get all pages of learn more modal on checkout page
     * @return array
     */
    public function getCheckoutDialogPages()
    {
        return $this->getPagesByAssetType(self::WIDGET_ASSET_TYPE_CHECKOUT_DIALOG);
    }

    /**
     * Get default content on modal. It shows up if there's no pages found.
     * @return string
     */
    public function getDefaultContent()
    {
        $vHtml = '<div style="background: white; padding: 20px; border-radius: 5px;"><span><h2>' . $this->__('Cannot get the content.') . '</h2></span></div>';
        return $vHtml;
    }

    /**
     * Get strip/side banner html
     *
     * @param $vBannerType  strip/side
     * @param $vPosition
     * @return string
     */
    public function getBannerHtml($vBannerType, $vPosition)
    {
        $vHtml = '';
        if ($vBannerType == Zipmoney_ZipmoneyPayment_Model_Config::MARKETING_BANNERS_TYPE_STRIP) {
            $vAssetType = Zipmoney_ZipmoneyPayment_Helper_Widget::WIDGET_ASSET_TYPE_STRIP_BANNER;
        } else if ($vBannerType == Zipmoney_ZipmoneyPayment_Model_Config::MARKETING_BANNERS_TYPE_SIDE) {
            $vAssetType = Zipmoney_ZipmoneyPayment_Helper_Widget::WIDGET_ASSET_TYPE_SIDE_BANNER;
        } else {
            return null;
        }
        $aAssets = $this->getAssetByType($vAssetType);
        if(!$aAssets || !is_array($aAssets) || count($aAssets) < 1) {
            return null;
        }
        foreach($aAssets as $aBannerAsset) {
            $vUrl = isset($aBannerAsset['url']) ? $aBannerAsset['url'] : null;
            $vHtml = Mage::helper('zipmoneypayment')->getUrlContent($vUrl);
            $vHtml = str_replace("\r", "", $vHtml);
            $vHtml = str_replace("\n", "", $vHtml);
            $vHtml = $this->replaceHtmlToken($vHtml, $aBannerAsset);
            break;
        }
        return $vHtml;
    }
}