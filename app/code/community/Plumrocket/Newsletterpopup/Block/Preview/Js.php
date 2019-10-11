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


class Plumrocket_Newsletterpopup_Block_Preview_Js extends Plumrocket_Newsletterpopup_Block_Js
{
	protected function _toHtml()
	{
		return Mage_Core_Block_Template::_toHtml();
	}

	public function getPopupArea()
	{
		return Plumrocket_Newsletterpopup_Model_Values_Show::ON_ACCOUNT_PAGES;
	}

	public function isEnableAnalytics()
	{
		return false;
	}
}
