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
 * @copyright   Copyright (c) 2017 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */


class Plumrocket_Newsletterpopup_Model_Template extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        if (Mage::getSingleton('plumbase/observer')->customer() == Mage::getSingleton('plumbase/product')->currentCustomer()) {
            $this->_init('newsletterpopup/template');
        }
    }

    protected function _beforeSave()
    {
        if (!$this->getData('skip_base_template_validation')) {
            if ($this->getOrigData('base_template_id') == -1) {
                // It's base template, create new.
                $this->setBaseTemplateId($this->getOrigData('entity_id'));
                $this->setId(null);
            } elseif($this->isObjectNew()) {
                $this->setBaseTemplateId($this->getOrigData('base_template_id'));
            }
        }

        // Set dates.
        $date = Mage::getModel('core/date')->date('Y-m-d H:i:s');
        if ($this->isObjectNew()) {
            $this->setData('created_at', $date);
            $this->setData('updated_at', null);
        }else{
            $this->setData('updated_at', $date);
        }

        return parent::_beforeSave();
    }

    public function delete()
    {
        if(!$this->canDelete()) {
            return $this;
        }

        return parent::delete();
    }

    public function canDelete()
    {
        if($this->isBase()) {
            return false;
        }

        $hasPopups = Mage::getSingleton('newsletterpopup/popup')
            ->getCollection()
            ->addFieldToFilter('template_id', $this->getId())
            ->getSize();

        if($hasPopups) {
            return false;
        }

        return true;
    }

    public function isBase()
    {
        if($this->getOrigData('base_template_id') == -1) {
            return true;
        }
    }

    protected function _afterLoad()
    {
        $this->setIsTemplate(true);
        return parent::_afterLoad();
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

            $previewUrl = Mage::helper('newsletterpopup/adminhtml')->getFrontendUrl('newsletterpopup/index/snapshot' , array('id' => $this->getId(), 'is_template' => 1));

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
        return $this->_webOrDirFormat($forWeb, DS . 'newsletterpopup' . DS . 'popup_template_' . $this->getId() . '.png');
    }

    public function getThumbnailCacheFilePath($forWeb = false)
    {
        return $this->_webOrDirFormat($forWeb, DS . 'newsletterpopup' . DS . 'cache' . DS . Plumrocket_Newsletterpopup_Helper_Adminhtml::THUMBNAIL_WIDTH .'x'. Plumrocket_Newsletterpopup_Helper_Adminhtml::THUMBNAIL_WIDTH . DS . 'popup_template_' . $this->getId() . '.png');
    }
}
