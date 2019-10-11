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


class Plumrocket_Newsletterpopup_Block_Preview_Template extends Plumrocket_Newsletterpopup_Block_Template
{
	protected $_layoutBased = true;
	protected $_popup = null;

	protected function _isEnabled()
	{
		return true;
	}

	protected function _cacheInit()
	{
		return false;
	}

	public function getPopup()
	{
		if (is_null($this->_popup)) {
			$request = $this->getRequest();

			$id = (int)$request->getParam('id');
			if (!$id) {
				$id = (int)$request->getParam('entity_id');
			}

			if($request->getParam('is_template')) {
				$this->_popup = Mage::helper('newsletterpopup')->getPopupTemplateById($id);
			}else{
				$this->_popup = Mage::helper('newsletterpopup')->getPopupById($id);
			}
		}
		return $this->_popup;
	}
}