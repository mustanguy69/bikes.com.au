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


class Plumrocket_Newsletterpopup_Block_Popup_Mailchimp extends Mage_Core_Block_Template
{
	protected function _toHtml()
	{
		$mode = $this->getPopup()->getSubscriptionMode();
		switch ($mode) {
			case Plumrocket_Newsletterpopup_Model_Values_SubscriptionMode::ONE_LIST_RADIO:
				$this->setTemplate('newsletterpopup/popup/mailchimp/radio.phtml');
				break;
			case Plumrocket_Newsletterpopup_Model_Values_SubscriptionMode::ONE_LIST_SELECT:
				$this->setTemplate('newsletterpopup/popup/mailchimp/select.phtml');
				break;
			case Plumrocket_Newsletterpopup_Model_Values_SubscriptionMode::MUPTIPLE_LIST:
				$this->setTemplate('newsletterpopup/popup/mailchimp/checkbox.phtml');
				break;
			case Plumrocket_Newsletterpopup_Model_Values_SubscriptionMode::ALL_LIST:
			case Plumrocket_Newsletterpopup_Model_Values_SubscriptionMode::ALL_SELECTED_LIST:
			default:
				$this->setTemplate('newsletterpopup/empty.phtml');
				break;
		}

		return parent::_toHtml();
	}

	public function getLists()
	{
		// for preview: dynamic generated data
		if ($data = $this->getPopup()->getData('custom_mailchimp_list')) {
			return $data;
		}

		$popupId = $this->getPopup()->getId();
		if($this->getPopup()->getIsTemplate()) {
			$popupId = 0;
		}
		return Mage::helper('newsletterpopup')->getPopupMailchimpList($popupId, true);
	}

	protected function getPopup()
	{
		return $this->getParentBlock()->getPopup();
	}
}
