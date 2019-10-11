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


class Plumrocket_Newsletterpopup_Helper_Adminhtml extends Mage_Core_Helper_Abstract
{
    const THUMBNAIL_WIDTH = 512;

    private $_mailchimp = false;
    private $_checkIfHtmlToImageInstalledResult = null;

    public function isMaichimpEnabled()
    {
        return (bool)Mage::getStoreConfig('newsletterpopup/mailchimp/enable');
    }

    public function getMcapi()
    {
        if (! $this->_mailchimp) {
            if ($this->isMaichimpEnabled()) {
                $this->_mailchimp = new Plumrocket_Newsletterpopup_Model_Mcapi(
                    trim(Mage::helper('core')->decrypt(Mage::getStoreConfig('newsletterpopup/mailchimp/key'))),
                    true
                );
            }
        }
        return $this->_mailchimp;
    }

    private function _getPath($package_theme)
    {
        return Mage::getBaseDir('design') . DS . 'frontend' . DS . $package_theme . DS . 'template' . DS . 'newsletterpopup' . DS . 'templates' . DS;
    }

    public function getTemplates()
    {
        $collection = Mage::getModel('newsletterpopup/template')
            ->getCollection()
            ->addExpressionFieldToSelect('template_type', 'IF(main_table.base_template_id >= 0, 1, -1)', array())
            ->addExpressionFieldToSelect('is_template', new Zend_Db_Expr(1), array());

        $resource = Mage::getSingleton('core/resource');
        $collection->getSelect()
            ->joinLeft(array('t' => $resource->getTableName('newsletterpopup/template')), 't.entity_id = main_table.base_template_id', array('base_template_name' => 'name'));

        return $collection;
    }

    public function checkIfHtmlToImageInstalled()
    {
        $disabled = explode(',', ini_get('disable_functions'));
        if (in_array('shell_exec', $disabled)) {
            return false;
        }

        if (is_null($this->_checkIfHtmlToImageInstalledResult)) {
            $cacheKeyName = $this->getHtmlToImageCacheKeyName();

            if (shell_exec('which wkhtmltoimage')) {
                $this->_checkIfHtmlToImageInstalledResult = true;
            } else {
                $path = Mage::app()->getCache()->load($cacheKeyName);
                if ($path) {
                    $this->_checkIfHtmlToImageInstalledResult = shell_exec("find $path -name \"wkhtmltoimage\"");

                    if (! $this->_checkIfHtmlToImageInstalledResult) {
                        // moved or deleted
                        Mage::app()->getCache()->remove($cacheKeyName);
                        Mage::getSingleton('adminhtml/session')->addWarning('The wkhtmltoimage thumbnail generation tool is missing.
                            Newsletter popup thumbnail generation is now disabled.
                            Please contact your webserver admin to install wkhtmltoimage command line tool.');
                    }
                }
            }
        }

        return $this->_checkIfHtmlToImageInstalledResult;
    }

    public function getHtmlToImageCacheKeyName()
    {
        return 'newsletter_popup_htmltoimage';
    }

    public function getFrontendUrl($url, $params = array(), $checkDomain = false)
    {
        $result = null;
        $websites = Mage::app()->getWebsites(true);
        foreach($websites as $website) {

            if ($website->getCode() == 'admin') continue;

            $storeId = $website
                ->getDefaultGroup()
                ->getDefaultStoreId();

            $result = Mage::app()->getStore($storeId)->getUrl($url, $params);
            if(!$checkDomain || ($checkDomain && parse_url(Mage::getBaseUrl(), PHP_URL_HOST) == parse_url($result, PHP_URL_HOST))) {
                break;
            }
        }

        return $result;
    }

    public function getBaseScreenUrl($obj, $useParentBase = false)
    {
        if($useParentBase) {
            if($obj->getBaseTemplateId() == 0) {
                return false;
            }

            if(!$name = $obj->getBaseTemplateName()) {
                return false;
            }
        }else{
            if($obj->getBaseTemplateId() != -1) {
                return false;
            }

            if(!$name = $obj->getTemplateName()) {
                $name = $obj->getName();
            }
        }

        $name = str_replace(array('.', ' '), array('', '_'), $name);
        return Mage::getDesign()->getSkinUrl('images/plumrocket/newsletterpopup/screens/'. strtolower($name) .'.jpg');
    }

    public function getScreenUrl($item)
    {
        if($screenUrl = $this->getBaseScreenUrl($item)) {
            return $screenUrl;
        }

        $filePath = $item->getThumbnailFilePath();
        $previewPath = $item->getThumbnailCacheFilePath(true);

        if (!file_exists($filePath)) {
            $item->generateThumbnail();
            $previewPath = false;
        }

        if (file_exists($filePath)) {
            $cachedFilePath = $item->getThumbnailCacheFilePath();
            if (!$previewPath || !file_exists($cachedFilePath)) {

                $previewPath = Mage::helper('newsletterpopup/image')->resize(
                    'newsletterpopup/'. ($item->getIsTemplate()? 'popup_template_' : 'popup_') . $item->getId() . '.png',
                    self::THUMBNAIL_WIDTH
                );
            }
        } else {
            $previewPath = false;
        }

        if(!$previewPath) {
            $previewPath = $this->getBaseScreenUrl($item, true);
        }

        if (!$previewPath) {
            $previewPath = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN) . 'adminhtml/default/default/images/plumrocket/newsletterpopup/none.jpg';
        }

        return $previewPath;
    }

    public function getDefaultRule($serialised = false)
    {
        $rule = array(
            'type' => 'newsletterpopup/popup_condition_combine',
            'attribute' => null,
            'operator' => null,
            'value' => '1',
            'is_value_processed' => null,
            'aggregator' => 'all',
            'conditions' => array(),
        );

        $rule['conditions'][] = array(
            'type' => 'newsletterpopup/popup_condition_general',
            'attribute' => 'current_device',
            'operator' => '()',
            'value' => array(
                Plumrocket_Newsletterpopup_Model_Values_Devices::DESKTOP,
                Plumrocket_Newsletterpopup_Model_Values_Devices::TABLET
            ),
            'is_value_processed' => false,
        );

        return $serialised? serialize($rule) : $rule;
    }
}
