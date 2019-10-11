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


class Plumrocket_Newsletterpopup_Block_Popup_Fields_Field extends Mage_Customer_Block_Widget_Abstract
{
    public function _toHtml()
    {
        $this->setTemplate('newsletterpopup/popup/fields/' . $this->getField()->getName() . '.phtml');
        return parent::_toHtml();
    }

    public function getLabel()
    {
        return $this->getField()->getLabel();
    }

    public function isRequired()
    {
        $name = $this->getField()->getName();
        if (in_array($name, array('confirm_password', 'confirm_email'))) {
            return true;
        }

        return $this->_getAttribute($name)->getIsRequired();
    }

    public function getFieldName($name = null)
    {
        if (null === $name) {
            $name = $this->getField()->getName();
        }

        return parent::getFieldName($name);
    }

    public function getFieldId($name = null)
    {
        if (null === $name) {
            $name = $this->getField()->getName();
        }

        return 'nl_' . parent::getFieldId($name) . '_' . $this->getPopup()->getId();
    }
}
