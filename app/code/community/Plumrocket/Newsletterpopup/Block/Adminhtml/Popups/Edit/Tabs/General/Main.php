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


class Plumrocket_Newsletterpopup_Block_Adminhtml_Popups_Edit_Tabs_General_Main
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $popup = Mage::registry('popup'); 

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('popup_');
		
        $fieldset = $form->addFieldset('general_fieldset', array('legend' => Mage::helper('newsletterpopup')->__('General')));

        $fieldset->addField('name', 'text', array(
            'name'      => 'name',
            'label'     => Mage::helper('newsletterpopup')->__('Popup Name'),
            'class'     => 'required-entry',
            'required'  => true
        ));

        $fieldset->addField('status', 'select', array(
            'name'      => 'status',
            'label'     => Mage::helper('newsletterpopup')->__('Status'),
            'values'    => Mage::getSingleton('newsletterpopup/values_status')->toOptionHash()
        ));

        $coupons = array(0 => 'No');
        $dates = array();
        $useAutoGeneration = array();
        $items = Mage::getModel('salesrule/rule')->getCollection()
            ->addFieldToFilter('coupon_type', array('gt' => 1));
        foreach ($items as $item) {
            $coupons[ $item->getId() ] = $item->getName();
            $useAutoGeneration[$item->getId()] = (bool)$item->getUseAutoGeneration() ? 1 : 0;

            $start_date = strtotime($item->getFromDate());
            $end_date = strtotime($item->getToDate());

            $dates[ $item->getId() ] = array(
                'from_date' => ($start_date)? strftime('%m/%d/%Y %I:%M %p', $start_date): '',
                'to_date'   => ($end_date)? strftime('%m/%d/%Y %I:%M %p', $end_date): ''
            );
        }
        $previewUrl = Mage::helper('newsletterpopup/adminhtml')->getFrontendUrl('newsletterpopup/index/preview', array(), true);
        $fieldset->addField('coupon_code', 'select', array(
            'name'      => 'coupon_code',
            'label'     => Mage::helper('newsletterpopup')->__('Use Coupon Code'),
            'values'    => $coupons,
            'note'      => 'Select Shopping Cart Price Rule that should be used to award users who opted-in for email newsletter.',
            'after_element_html' => '<script type="text/javascript">
                coupons_date = '. json_encode($dates) .';
                use_auto_generation = '. json_encode($useAutoGeneration) .';
                previewUrl = "' . Mage::helper('newsletterpopup')->validateUrl($previewUrl) . '";
            </script>'
        ));

        $fieldset->addField('start_date', 'date', array( 
			'name'		=> 'start_date',
			'label'		=> Mage::helper('newsletterpopup')->__('Start Date'), 
			'image'		=> $this->getSkinUrl('images/grid-cal.gif'), 
			'input_format' => Varien_Date::DATETIME_INTERNAL_FORMAT, 
			'time'		=> 1,
			'format'	=> Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT), 
		));

        $fieldset->addField('end_date', 'date', array( 
			'name'		=> 'end_date',
			'label'		=> Mage::helper('newsletterpopup')->__('End date'), 
			'image'		=> $this->getSkinUrl('images/grid-cal.gif'), 
			'input_format' => Varien_Date::DATETIME_INTERNAL_FORMAT, 
			'time'		=> 1,
			'format'	=> Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT),
			'note'		=> 'Period when newsletter popup is active. Dates will be automatically loaded from selected Shopping Cart Price Rule but can be manually changed.' 
		));

		$fieldset->addField('success_page', 'select', array(
            'name'      => 'success_page',
            'label'     => Mage::helper('newsletterpopup')->__('Subscription Success Page'),
            'values'    => Mage::getSingleton('newsletterpopup/values_redirectto')->toOptionHash(),
        ));

        $fieldset->addField('custom_success_page', 'text', array(
            'name'      => 'custom_success_page',
            'label'     => Mage::helper('newsletterpopup')->__('Custom Success Page URL'),
            'note'		=> 'Please enter the full URL of the page, including the domain name, to which you will be redirecting.',
            'after_element_html' => '<script type="text/javascript">pjQuery_1_12_4(document).ready(function() { checkSuccessPage(); });</script>',
        ));

        $fieldset->addField('send_email', 'select', array(
            'name'      => 'send_email',
            'label'     => Mage::helper('newsletterpopup')->__('Send Autoresponder Email'),
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'note'		=> 'Send email when user successfully subscribed to your email newsletter.'
        ));

        $fieldset->addField('email_template', 'select', array(
            'name'      => 'email_template',
            'label'     => Mage::helper('newsletterpopup')->__('Autoresponder Email Template'),
            'values'    => Mage::getModel('newsletterpopup/source_email_template')->toOptionArray(),
            'note'      => 'Magento will send this email after user successfully subscribed to your email newsletter.',
            'after_element_html' => '<script type="text/javascript">pjQuery_1_12_4(document).ready(function() { checkSendEmail(); });</script>',
        ));

        $form->setValues($popup->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
