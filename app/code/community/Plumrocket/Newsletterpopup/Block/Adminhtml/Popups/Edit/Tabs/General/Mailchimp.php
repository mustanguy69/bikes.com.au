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


class Plumrocket_Newsletterpopup_Block_Adminhtml_Popups_Edit_Tabs_General_Mailchimp
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $popup = Mage::registry('popup');
        $disabled = !Mage::helper('newsletterpopup/adminhtml')->isMaichimpEnabled();

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('popup_');
		
        $fieldset = $form->addFieldset('mailchimp_fieldset', array('legend' => Mage::helper('newsletterpopup')->__('Mailchimp List Managment')));

        if ($disabled) {
            $fieldset->addType('extended_label','Plumrocket_Newsletterpopup_Block_Adminhtml_Popups_Edit_Renderer_Label');

            $fieldset->addField('mailchimp_label', 'extended_label', array(
                'hidden'    => false
            ));
            $popup->setData('mailchimp_label', Mage::helper('newsletterpopup')->__('Mailchimp Synchronization is not enabled in System Configuration -> Newsltter Popup. This section is disabled.'));
        }

        $fieldset->addField('subscription_mode', 'select', array(
            'name'      => 'subscription_mode',
            'label'     => Mage::helper('newsletterpopup')->__('User Subscription Mode'),
            'values'    => Mage::getSingleton('newsletterpopup/values_subscriptionMode')->toOptionHash(),
            'note'      => 'Here you can allow users to subscribe to the list of their choice or automatically subscribe each new user to all Mailchimp Lists',
            'disabled'  => $disabled,
        ));

        $fieldset->addField('mailchimp_list', 'text', array(
            'name'      => 'mailchimp_list',
            'label'     => Mage::helper('newsletterpopup')->__('Enable Mailchimp Lists'),
            'note'      => 'Only enabled mailchimp lists will be displayes in newsletter popup',
            'disabled'  => $disabled,
        ));

        $form->getElement('mailchimp_list')
        ->setRenderer(
            $this->getLayout()->createBlock('newsletterpopup/adminhtml_popups_edit_renderer_inputTable')
        )
        ->getRenderer()
            ->setContainerFieldId('mailchimp_list')
            ->setRowKey('name')
            ->addColumn('enable', array(
                'header'    => Mage::helper('newsletterpopup')->__('Enable'),
                'index'     => 'enable',
                'type'      => 'checkbox',
                'value'     => '1',
                'width'     => '5%',
            ))
            ->addColumn('orig_label', array(
                'header'    => Mage::helper('newsletterpopup')->__('Mailchimp List'),
                'index'     => 'orig_label',
                'type'      => 'label',
                'width'     => '40%',
            ))
            ->addColumn('label', array(
                'header'    => Mage::helper('newsletterpopup')->__('Displayed List Name'),
                'index'     => 'label',
                'type'      => 'input',
                'width'     => '40%',
            ))
            ->addColumn('sort_order', array(
                'header'    => Mage::helper('newsletterpopup')->__('Sort Order'),
                'index'     => 'sort_order',
                'type'      => 'input',
                'width'     => '15%',
            ))
            ->setArray($this->_getMailchimpData($popup->getId()));

        $form->setValues($popup->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    private function _getMailchimpData($id)
    {
        if (!Mage::helper('newsletterpopup/adminhtml')->isMaichimpEnabled()) {
            return array();
        }
        $collectionData = Mage::helper('newsletterpopup')->getPopupMailchimpList($id, false);

        $result = array();
        $mailchimpList = Mage::getSingleton('newsletterpopup/values_mailchimplist')->toOptionHash();
        foreach ($mailchimpList as $key => $name) {
            if (array_key_exists($key, $collectionData)) {
                $data = $collectionData[$key]->getData();
            } else {
                $data = array(
                    'name'       => $key,
                    'label'      => $name,
                    'enable'     => '0',
                    'sort_order' => 0,
                );
            }
            $data['orig_label'] = $name;
            $result[] = $data;
        }

        uasort($result, create_function('$a, $b', 'return $a["sort_order"] > $b["sort_order"]? 1 : 0;'));
        return $result;
    }
}
