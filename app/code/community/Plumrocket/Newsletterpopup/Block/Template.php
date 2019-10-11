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


class Plumrocket_Newsletterpopup_Block_Template extends Mage_Core_Block_Template
{
    protected $_layoutBased = false;

    protected function _isEnabled()
    {
        return Mage::helper('newsletterpopup')->moduleEnabled();
    }

    protected function _cacheInit()
    {
        if ($this->_isEnabled() && $this->getPopup() && !$this->getPopup()->getIsTemplate()) {
            $id = $this->getPopup()->getId();
            if ($id > 0) {
                $this->addData(array(
                    'cache_lifetime'    => 86400, // (seconds) data lifetime in the cache
                    'cache_tags'        => array('newsletterpopup_' . $id),
                    'cache_key'         => $id
                ));
            }
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->_cacheInit();
    }

    public function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->_isEnabled() && !$this->_layoutBased) {
            $this->setTemplate('newsletterpopup/popup.phtml')
            ->setChild('popup.body',
                $this->getLayout()->createBlock('newsletterpopup/popup')
            );
        }
    }

    protected function _toHtml()
    {
        if (!$this->_isEnabled()
            // if not found or in account pages or popup included in array of locked popups
            || ($this->getPopup()->getId() == 0)
        ) {
            $this->setTemplate('newsletterpopup/empty.phtml');
        }
        return parent::_toHtml();
    }

    public function getPopup()
    {
        return Mage::helper('newsletterpopup')->getCurrentPopup();
    }

    public function getSuccessPage()
    {
        $page = '';
        if (Mage::helper('newsletterpopup')->moduleEnabled()) {
            switch ($this->getPopup()->getData('success_page')) {
                case '':
                case Plumrocket_Newsletterpopup_Model_Values_Redirectto::STAY_ON_PAGE:
                    $page = '';
                    break;
                case Plumrocket_Newsletterpopup_Model_Values_Redirectto::CUSTOM_URL:
                    $page = $this->getPopup()->getData('custom_success_page');
                    break;
                case Plumrocket_Newsletterpopup_Model_Values_Redirectto::ACCOUNT_PAGE:
                    $page = Mage::getUrl('customer/account');
                    break;
                case Plumrocket_Newsletterpopup_Model_Values_Redirectto::LOGIN_PAGE:
                    $page = Mage::getUrl('customer/account/login');
                    break;
                default:
                    $page = Mage::getUrl($this->getPopup()->getData('success_page'));
                    break;
            }
        }

        return $page;
    }
}
