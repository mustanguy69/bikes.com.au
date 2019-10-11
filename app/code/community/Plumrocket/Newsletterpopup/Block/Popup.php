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


class Plumrocket_Newsletterpopup_Block_Popup extends Mage_Core_Block_Template
{
	protected $_noAnimation = false;
	protected $_template = 'newsletterpopup/templates/newsletterpopup-system-template.phtml';

	public function _prepareLayout()
	{
		parent::_prepareLayout();

		$this->_templateProcessor = Mage::helper('cms')->getBlockTemplateProcessor();

		$layout = $this->getLayout();

		$this->setChild('popup.fields', 
				$layout->createBlock('newsletterpopup/popup_fields')
			)
			->setChild('popup.mailchimp', 
				$layout->createBlock('newsletterpopup/popup_mailchimp')
			);
	}


	public function getPopup()
	{
		if (!$this->hasData('popup')) {
			$this->setData('popup', Mage::helper('newsletterpopup')->getCurrentPopup());
		}

		return $this->getData('popup');
	}


	public function getPopupTemplate()
	{
		$popup = $this->getPopup();
		$code = $popup->getCode();
		if(false === strpos($code, '{{mailchimp_fields}}')) {
			$hasMailchimpTag = true;
		}

		$code = str_replace(
			Mage::helper('newsletterpopup')->getTemplatePlaceholders(),
			array(
				$popup->getData('text_cancel'),
				$popup->getData('text_title'),
				$popup->getData('text_description'),
				(empty($hasMailchimpTag)? $this->getChildHtml('popup.fields') : $this->getChildHtml()),
				(empty($hasMailchimpTag)? $this->getChildHtml('popup.mailchimp') : ''),
				$popup->getData('text_submit'),
			),
			$code
		);

		$code = str_replace('.newspopup_up_bg', '#newspopup_up_bg_'.$popup->getId(), $code);

        return $this->_templateProcessor->filter($code);
	}


	public function getPopupStyle()
	{
		$popup = $this->getPopup();
		$style = $this->_templateProcessor->filter($popup->getStyle());

		$id = '#newspopup_up_bg_'.$popup->getId();

		$style = preg_replace(
			array(
				'/,(\s*)(\.)/m', // do not add (\.|#)  NEVER!!!
				'/^(\s*)(\.|#)/m'
			),
			array(
				', '.$id.' $2',
				'$1'.$id.' $2'
			), $style);

		$style = str_replace(
			array(
				$id.' .newspopup_up_bg',
				$id.' .newspopup-blur',
				$id.' .newspopup_ov_hidden',

			),
			array(
				$id,
				'.newspopup-blur-'.$popup->getId(),
				'.newspopup_ov_hidden-'.$popup->getId(),
			),
			$style
		);

		$style = $this->_replaceFonts($style);

		return $style;
	}


	protected function _replaceFonts($content)
	{
		$to = Mage::getStoreConfig('web/secure/base_url').'skin/$2/font$3';
		$content = preg_replace(
			array(
				'/('.str_replace('/', '\/', Mage::getStoreConfig('web/secure/base_skin_url')).')(.*)\/font(.*)/m',
				'/('.str_replace('/', '\/', Mage::getStoreConfig('web/unsecure/base_skin_url')).')(.*)\/font(.*)/m'
			),
			array(
				$to,
				$to,
			), $content);
		return $content;
	}


	public function noAnimation()
	{
		$this->_noAnimation = true;
		return $this;
	}

	public function getAnimation()
	{
		return ($this->_noAnimation)? '': $this->getPopup()->getAnimation();
	}
}