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


class Plumrocket_Newsletterpopup_Block_Js extends Mage_Core_Block_Template
{
	private $_disableThis = false;

	protected function _toHtml()
	{
		if (!Mage::helper('newsletterpopup')->moduleEnabled() || $this->_disableThis) {
			$this->setTemplate('newsletterpopup/empty.phtml');
		}
		return parent::_toHtml();
	}

	public function getPopupArea()
	{
		if (! Mage::helper('newsletterpopup')->moduleEnabled()) {
			return Plumrocket_Newsletterpopup_Model_Values_Show::ON_ALL_PAGES;
		}

		// if cookie is global and array of locked popups is not empty
		if (Mage::getStoreConfig('newsletterpopup/general/cookies_usage') == Plumrocket_Newsletterpopup_Model_Values_Cookies::_GLOBAL
			&& Mage::helper('newsletterpopup')->getLockedPopupIds()
		) {
			return Plumrocket_Newsletterpopup_Model_Values_Show::ON_ACCOUNT_PAGES;
		}

		$request 	= $this->getRequest();
		$controller = $request->getControllerName();
		$action 	= $request->getActionName();
		$route 		= $request->getRouteName();
		$module 	= $request->getModuleName();
		
		if ((($route == 'cms') && ($controller == 'index') && ($action == 'index'))
			|| (($route == 'privatesales') && ($controller == 'homepage') && ($action == 'index'))
			|| (($route == 'catalog') && ($controller == 'category') && ($action == 'homepage'))
		) {
			return Plumrocket_Newsletterpopup_Model_Values_Show::ON_HOME_PAGE;

		} elseif (($route == 'catalog') && ($controller == 'category')) { //  && ($action == 'view')
			return Plumrocket_Newsletterpopup_Model_Values_Show::ON_CATEGORY_PAGES;

		} elseif (($route == 'catalog') && ($controller == 'product')) { //  && ($action == 'view')
			return Plumrocket_Newsletterpopup_Model_Values_Show::ON_PRODUCT_PAGES;

		}  elseif ($route == 'cms') {
			return Plumrocket_Newsletterpopup_Model_Values_Show::ON_CMS_PAGES;

		} elseif (($route == 'customer') && ($controller == 'account')) {
			return Plumrocket_Newsletterpopup_Model_Values_Show::ON_ACCOUNT_PAGES;
		}

		return Plumrocket_Newsletterpopup_Model_Values_Show::ON_ALL_PAGES;
	}

	public function isEnableAnalytics()
	{
		return Mage::helper('newsletterpopup')->moduleEnabled() && (bool)Mage::getStoreConfig('newsletterpopup/general/enable_analytics');
	}

	public function disable()
	{
		$this->_disableThis = true;

		$parent = $this->getParentBlock();
		// Another our modules may share it js file
		//$parent->removeItem('js', 'plumrocket/jquery-1.12.4.min.js');
		$parent->removeItem('skin_js', 'js/plumrocket/newsletterpopup/popup.js');
		$parent->removeItem('skin_css', 'css/plumrocket/newsletterpopup/newsletterpopup.css');
		$parent->removeItem('skin_css', 'css/plumrocket/newsletterpopup/newsletterpopup-additional.css');
		$parent->removeItem('skin_css', 'css/plumrocket/newsletterpopup/newsletterpopup-ie8.css');
	}

	public function getActionUrl()
	{
		return Mage::helper('newsletterpopup')->validateUrl($this->getUrl('newsletterpopup/index/subscribe'));
	}

	public function getCancelUrl()
	{
		return Mage::helper('newsletterpopup')->validateUrl($this->getUrl('newsletterpopup/index/cancel'));
	}

	public function getBlockUrl()
	{
		return Mage::helper('newsletterpopup')->validateUrl($this->getUrl('newsletterpopup/index/block'));
	}

	public function getHistoryUrl()
	{
		return Mage::helper('newsletterpopup')->validateUrl($this->getUrl('newsletterpopup/index/history'));
	}

	// function _bot_detected()
	// {
	// 	return (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT']));
	// }
}
