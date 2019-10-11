<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Block_Widget_Abstract extends Zipmoney_ZipmoneyPayment_Block_Abstract
{
    protected function _isShow($vType, $vPosition)
    {
        if (!$this->_isZipMoneyPaymentActive()) {
            return false;
        }
        if (!$this->_isMarketingBannersActive()) {
            return false;
        }
        $vCurPage = $this->_getCurrentPageType();
        if (!$vCurPage) {
            return false;
        }
        $bShow = $this->_isShowBannerOnCurPage($vCurPage, $vType, $vPosition);
        return $bShow;
    }

    protected function _setBannerBlock($vBannerType)
    {
        switch ($vBannerType) {
            case Zipmoney_ZipmoneyPayment_Model_Config::MARKETING_BANNERS_TYPE_STRIP:
                $this->_setStripBanner();
                break;
            case Zipmoney_ZipmoneyPayment_Model_Config::MARKETING_BANNERS_TYPE_SIDE:
                $this->_setSideBanner();
                break;
            default:
                // should never go to here
                break;
        }
    }

    protected function _setStripBanner()
    {
        $block = $this->getLayout()->createBlock('zipmoneypayment/widget_banner_strip');
        $this->setChild('zipmoneypayment.widget.banner.strip', $block);
    }

    protected function _setSideBanner()
    {
        $block = $this->getLayout()->createBlock('zipmoneypayment/widget_banner_side');
        $this->setChild('zipmoneypayment.widget.banner.side', $block);
    }

    protected function _isMarketingBannersActive()
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_MARKETING_BANNERS_ACTIVE;
        return Mage::getStoreConfig($vPath) ? true : false;
    }

    protected function _isShowBannerOnCurPage($vCurPage, $vBannerType, $vPosition)
    {
        $bConfigDisplay = $this->_isBannerActive($vCurPage, $vBannerType);
        if (!$bConfigDisplay) {
            return false;
        }
        $vConfigPosition = $this->_getBannerPosition($vCurPage, $vBannerType);
        if ($vPosition == $vConfigPosition) {
            return true;
        } else {
            return false;
        }
    }

    protected function _getBannerPosition($vCurPage, $vBannerType)
    {
        $vPathPrefix = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_MARKETING_BANNERS_PREFIX;
        $vPath = $vPathPrefix . $vCurPage . '_page_banner/' . $vBannerType . '_banner_position';
        $vPos = Mage::getStoreConfig($vPath);
        return $vPos;
    }

    protected function _isBannerActive($vCurPage, $vBannerType)
    {
        $vPathPrefix = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_MARKETING_BANNERS_PREFIX;
        $vPath = $vPathPrefix . $vCurPage . '_page_banner/' . $vBannerType . '_banner_active';
        $iShow = Mage::getStoreConfig($vPath);
        return $iShow ? true : false;
    }

    protected function _getCurrentPageType()
    {
        $oRequest = Mage::app()->getRequest();
        $vModule = $oRequest->getModuleName();
        if ($vModule == 'cms') {
            $vId = Mage::getSingleton('cms/page')->getIdentifier();
            $iPos = strpos($vId, 'home');
            if ($iPos === 0) {
                return 'home';
            }
        }else if ($vModule == 'catalog') {
            $vController = $oRequest->getControllerName();
            if ($vController == 'product') {
                return 'product';
            } else if ($vController == 'category') {
                return 'category';
            }
        } else if ($vModule == 'checkout') {
            $vController = $oRequest->getControllerName();
            if ($vController == 'cart') {
                return 'cart';
            }
        }
        return '';
    }
}