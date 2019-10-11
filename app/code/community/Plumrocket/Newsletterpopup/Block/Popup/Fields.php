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


class Plumrocket_Newsletterpopup_Block_Popup_Fields extends Mage_Customer_Block_Widget_Abstract
{
	public function __construct()
	{
		parent::__construct();
		$this->setTemplate('newsletterpopup/popup/fields.phtml');
	}

	public function getFields()
	{
		if ($data = $this->_getPopup()->getData('custom_signup_fields')) {
			return $data;
		}
		$popupId = $this->_getPopup()->getId();
		if($this->_getPopup()->getIsTemplate()) {
			$popupId = 0;
		}
		return Mage::helper('newsletterpopup')->getPopupFormFields($popupId, true);
	}

	public function createBlock($field)
	{
		$blockName = 'field';
        if (in_array($field->getName(), array('dob', 'prefix', 'suffix', 'agreement'))) {
            $blockName = $field->getName();
        }
		return $this->getLayout()
			->createBlock('newsletterpopup/popup_fields_' . $blockName)
			->setField($field)
			->setPopup($this->_getPopup());
	}

	protected function _getPopup()
	{
		return $this->getParentBlock()->getPopup();
	}
}
