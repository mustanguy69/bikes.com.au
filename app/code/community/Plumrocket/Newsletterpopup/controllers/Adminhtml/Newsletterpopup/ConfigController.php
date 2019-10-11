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


class Plumrocket_Newsletterpopup_Adminhtml_Newsletterpopup_ConfigController extends Mage_Adminhtml_Controller_Action
{
    public function refreshAction()
    {
        $result = array(
            'error'     => true,
            'message'   => 'Wkhtmltoimage was not found. Please contact your webserver admin to install thumbnail generation tool.',
        );

        if (Mage::getSingleton('admin/session')->isAllowed('plumrocket/newsletterpopup')) {
            // already found
            if (Mage::helper('newsletterpopup/adminhtml')->checkIfHtmlToImageInstalled()) {
                $result['error'] = false;
                $result['message'] = 'Wkhtmltoimage is already configured. Thumbnail generation is Enabled.';
            } else {
                $cacheKeyName = Mage::helper('newsletterpopup/adminhtml')->getHtmlToImageCacheKeyName();
                $path = '/sbin /usr/sbin /usr/local/bin ~';

                $resPath = shell_exec("find $path -name \"wkhtmltoimage\"");
                if ($resPath) {
                    Mage::app()->getCache()->save($resPath, $cacheKeyName, array(), 86400 * 365 * 40);
                    $result['error'] = false;
                    $result['message'] = 'Wkhtmltoimage has been found. Thumbnail generation is now Enabled.';
                }
            }
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($result));
    }

    protected function _isAllowed()
    {
        return parent::_isAllowed();
    }
}
