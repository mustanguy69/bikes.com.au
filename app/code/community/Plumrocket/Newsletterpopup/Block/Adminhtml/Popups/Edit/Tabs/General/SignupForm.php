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


class Plumrocket_Newsletterpopup_Block_Adminhtml_Popups_Edit_Tabs_General_SignupForm
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $popup = Mage::registry('popup');

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('popup_');

        $fieldset = $form->addFieldset('signup_fieldset', array('legend' => Mage::helper('newsletterpopup')->__('Signup Form')));

        $fieldset->addField('signup_method', 'select', array(
            'name'      => 'signup_method',
            'label'     => Mage::helper('newsletterpopup')->__('User Sign-Up Method'),
            'values'    => Mage::getSingleton('newsletterpopup/values_signupMethod')->toOptionHash(),
            'note'      => 'Please note: if customer registration is selected and password field is not enabled below, then passwords will be generated automatically for each user and sent by email'
        ));

        $fieldset->addField('signup_fields', 'text', array(
            'name'      => 'signup_fields',
            'label'     => Mage::helper('newsletterpopup')->__('Enable Form Fields'),
            'note'      => 'Selected fields will be displayed on sign-up form. Please note, "Email" is require field.'
        ));

        $form->getElement('signup_fields')
        ->setRenderer(
            $this->getLayout()->createBlock('newsletterpopup/adminhtml_popups_edit_renderer_inputTable')
        )
        ->getRenderer()
            ->setContainerFieldId('signup_fields')
            ->setRowKey('name')
            ->addColumn('enable', array(
                'header'    => Mage::helper('newsletterpopup')->__('Enable'),
                'index'     => 'enable',
                'type'      => 'checkbox',
                'value'     => '1',
                'width'     => '5%',
            ))
            ->addColumn('orig_label', array(
                'header'    => Mage::helper('newsletterpopup')->__('Field'),
                'index'     => 'orig_label',
                'type'      => 'label',
                'width'     => '40%',
            ))
            ->addColumn('label', array(
                'header'    => Mage::helper('newsletterpopup')->__('Displayed Name'),
                'index'     => 'label',
                'type'      => 'input',
                'renderer'  => 'newsletterpopup/adminhtml_popups_renderer_input',
                'width'     => '40%',
            ))
            ->addColumn('sort_order', array(
                'header'    => Mage::helper('newsletterpopup')->__('Sort Order'),
                'index'     => 'sort_order',
                'type'      => 'input',
                'width'     => '40px',
                'width'     => '15%',
            ))
            ->setArray($this->_getFields($popup->getId()));

        $form->setValues($popup->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _getFields($popup_id)
    {
        $labels = array(
            'email'             => Mage::helper('newsletterpopup')->__('Your Email Address'),
            'confirm_email'     => Mage::helper('newsletterpopup')->__('Confirm Your Email Address'),
        );

        $systemItems = Mage::helper('newsletterpopup')->getPopupFormFields(0, false);
        $popupItems = Mage::helper('newsletterpopup')->getPopupFormFields($popup_id, false);

        $result = array();
        foreach ($systemItems as $name => $_systemItem) {
            if (array_key_exists($name, $popupItems)) {
                $data = $popupItems[$name]->getData();
                $orig_label = $_systemItem['label'];
            } else {
                $data = $systemItems[$name]->getData();
                $orig_label = $data['label'];
                if (array_key_exists($name, $labels)) {
                    $data['label'] = $labels[$name];
                }
            }

            // Agreement row has different origin label and label in textarea
            if ('agreement' === $_systemItem['name']) {
                $orig_label = Mage::helper('newsletterpopup')->__('Consent Checkbox');
            }

            $data['orig_label'] = $orig_label;
            $result[] = $data;
        }

        uasort($result, create_function('$a, $b', 'return $a["sort_order"] > $b["sort_order"]? 1 : 0;'));
        return $result;
    }
}