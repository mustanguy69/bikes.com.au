<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * @package     Plumrocket_Newsletterpopup
 * @copyright   Copyright (c) 2018 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */


class Plumrocket_Newsletterpopup_Model_Popup extends Mage_Rule_Model_Abstract
{
    protected function _construct()
    {
        if (Mage::getSingleton('plumbase/observer')->customer() == Mage::getSingleton('plumbase/product')->currentCustomer()) {
            $this->_init('newsletterpopup/popup');

            $defaultTemplate = Mage::getSingleton('newsletterpopup/template')->load(20);

            $defaults = array(
                'success_page'      =>'__stay__',
                'display_popup'     => 'after_time_delay',
                'template_id'       => $defaultTemplate->getId(),
                'code'              => $defaultTemplate->getCode(),
                'style'             => $defaultTemplate->getStyle(),
                'send_email'        => 1,
                'delay_time'        => '5',
                'page_scroll'       => '50',
                'css_selector'      => '',
                'cookie_time_frame' => '30',
                'store_id'          => '0',
                'text_title'        => Mage::helper('newsletterpopup')->__('Join our email list and SAVE'),
                'text_submit'       => Mage::helper('newsletterpopup')->__('Submit'),
                'text_cancel'       => Mage::helper('newsletterpopup')->__('No Thanks'),
                'code_length'       => 12,
                'code_format'       => 'alphanum',
                'code_prefix'       => '',
                'code_suffix'       => '',
                'code_dash'         => 0,
                'email_template'    => 'newsletterpopup_general_email_template',
                'animation'         => 'fadeInDownBig',
                'signup_method'     => 'signup_only',
                'subscription_mode' => 'all_selected',
                'conditions_serialized' => Mage::helper('newsletterpopup/adminhtml')->getDefaultRule(true),
            );
            $data = $this->getData();

            foreach ($defaults as $key => $val) {
                if (! array_key_exists($key, $data)) {
                    $this->setData($key, $val);
                }
            }
        }
    }

    public function getConditionsInstance()
    {
        return Mage::getModel('newsletterpopup/popup_condition_combine');
    }

    public function getActionsInstance()
    {
        return Mage::getModel('rule/action_collection');
    }

    public function getData($key='', $index=null)
    {
        if (!Mage::app()->getStore()->isAdmin()) {
            if (in_array($key, array('text_description', 'text_success'))) {
                return Mage::helper('cms')->getBlockTemplateProcessor()
                    ->filter(parent::getData($key));
            }
        }

        return parent::getData($key, $index);
    }

    public function getOffsetForCookieTimeFrame()
    {
        return Mage::helper('newsletterpopup')->getOffsetFromExtendedTime(
            $this->getCookieTimeFrame(),
            'cookie_time_frame'
        );
    }

    public function cleanCache()
    {
        Mage::app()->cleanCache('newsletterpopup_' . $this->getId());
    }

    public function generateThumbnail()
    {
        if (Mage::helper('newsletterpopup/adminhtml')->checkIfHtmlToImageInstalled()) {
            $dirPath = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . 'newsletterpopup';
            if (! file_exists($dirPath)) {
                if (!mkdir($dirPath)) {
                    Mage::getSingleton('adminhtml/session')->addError(
                        Mage::helper('newsletterpopup')->__('Directory was not created. Access denied.'));
                    return false;
                }
            }

            $previewUrl = Mage::helper('newsletterpopup/adminhtml')->getFrontendUrl('newsletterpopup/index/snapshot' , array('id' => $this->getId()));
            $filePath = $this->getThumbnailFilePath();
            $cacheFilePath = $this->getThumbnailCacheFilePath();

            if (file_exists($cacheFilePath)) {
                unlink($cacheFilePath);
            }

            exec("wkhtmltoimage --crop-w 800 $previewUrl $filePath");
        }
        return true;
    }

    private function _webOrDirFormat($formatAsWeb = false, $path) {
        return ($formatAsWeb)?
            Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . $path:
            Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . $path;
    }

    public function getThumbnailFilePath($forWeb = false)
    {
        return $this->_webOrDirFormat($forWeb, DS . 'newsletterpopup' . DS . 'popup_' . $this->getId() . '.png');
    }

    public function getThumbnailCacheFilePath($forWeb = false)
    {
        return $this->_webOrDirFormat($forWeb, DS . 'newsletterpopup' . DS . 'cache' . DS . Plumrocket_Newsletterpopup_Helper_Adminhtml::THUMBNAIL_WIDTH .'x'. Plumrocket_Newsletterpopup_Helper_Adminhtml::THUMBNAIL_WIDTH . DS . 'popup_' . $this->getId() . '.png');
    }

    protected function _beforeSave()
    {
        if($this->getTemplateId()) {
            if($template = Mage::getModel('newsletterpopup/template')->load($this->getTemplateId())) {
                $helper = Mage::helper('newsletterpopup');
                $templateCode = $helper->getNString($template->getCode());
                $popupCode = $helper->getNString($this->getCode());
                $templateStyle = $helper->getNString($template->getStyle());
                $popupStyle = $helper->getNString($this->getStyle());

                if(strnatcmp($templateCode, $popupCode) || strnatcmp($templateStyle, $popupStyle)) {

                    $countPopups = Mage::getSingleton('newsletterpopup/popup')
                        ->getCollection()
                        ->addFieldToFilter('template_id', $template->getId())
                        ->getSize();

                    if($template->getBaseTemplateId() == -1 || ($this->isObjectNew() && $countPopups >= 1) || (!$this->isObjectNew() && $countPopups > 1)) {
                        // Create new.
                        $template->setBaseTemplateId($template->getId());
                        $template->setId(null);
                        $template->setName($template->getName() . ' - '. $this->getName());
                    }

                    $template->addData(array(
                        'code'  => $popupCode,
                        'style' => $popupStyle,
                    ));

                    if($templateId = $template->save()->getId()) {
                        $template->generateThumbnail();
                        $this->setTemplateId($templateId);
                    }
                }
            }
        }

        $this->unsetData('template_name');
        $this->unsetData('code');
        $this->unsetData('style');

        return parent::_beforeSave();
    }

    protected function _afterLoad()
    {
        if($this->getTemplateId()) {
            if($template = Mage::getModel('newsletterpopup/template')->load($this->getTemplateId())) {
                $this->addData(array(
                    'template_name' => $template->getName(),
                    'code'  => $template->getCode(),
                    'style' => $template->getStyle(),
                ));
            }
        }
        return parent::_afterLoad();
    }

    public function textHasPlaceholder($text, $placeholder)
    {
        return false !== stripos($text, $placeholder);
    }

    public function hasSuccessTextPlaceholders()
    {
        $successText = $this->getTextSuccess();
        $placeholders = Mage::helper('newsletterpopup')->getSuccessTextPlaceholders();

        foreach ($placeholders as $placeholder) {
            if ($this->textHasPlaceholder($successText, $placeholder)) {
                return true;
            }
        }

        return false;
    }

    public function getPreparedTextSuccess()
    {
        $result = $this->getTextSuccess();
        $placeholders = Mage::helper('newsletterpopup')->getSuccessTextPlaceholders();

        foreach ($placeholders as $placeholder) {
            if ($this->textHasPlaceholder($result, $placeholder)) {
                $propertyName = trim($placeholder, '{}');
                $value = $this->getData('rule_' . $propertyName);

                $result = str_ireplace($placeholder, $value, $result);
            }
        }

        return $result;
    }

    public function getCouponExpLocaleDateTime($expirationDate = null)
    {
        if (! $expirationDate) {
            $expirationDate = $this->getCouponExpDateTime();
        }

        if (false === $expirationDate) {
            return false;
        }

        return Mage::app()->getLocale()->date(strtotime($expirationDate));
    }

    public function getCouponExpDateTime()
    {
        if ($expirationDate = $this->getData('coupon_expiration_time')) {
            $offset = Mage::helper('newsletterpopup')->getOffsetFromExtendedTime(
                $expirationDate,
                'coupon_expiration_time'
            );

            if ($offset > 0) {
                $coreDate = Mage::getModel('core/date');
                $now = $coreDate->timestamp();

                return $coreDate->gmtDate(null, $now + $offset);
            }
        }

        return false;
    }
}
