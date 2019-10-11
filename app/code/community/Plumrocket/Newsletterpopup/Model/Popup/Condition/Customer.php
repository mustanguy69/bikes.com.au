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


class Plumrocket_Newsletterpopup_Model_Popup_Condition_Customer extends Mage_Rule_Model_Condition_Abstract
{
    public function loadAttributeOptions()
    {
        $attributes = array(
            'newsleter_subscribed' => Mage::helper('newsletterpopup')->__('Customer Subscribed'),
            'created_at' => Mage::helper('newsletterpopup')->__('Customer Created At'),
            // 'created_in' => Mage::helper('newsletterpopup')->__('Created In'),
            // 'default_billing' => Mage::helper('newsletterpopup')->__('Default Billing'),
            // 'default_shipping' => Mage::helper('newsletterpopup')->__('Default Shipping'),
            'dob' => Mage::helper('newsletterpopup')->__('Customer DOB'),
            'age' => Mage::helper('newsletterpopup')->__('Customer Age'),
            'gender' => Mage::helper('newsletterpopup')->__('Customer Gender'),
            'group_id' => Mage::helper('newsletterpopup')->__('Customer Group ID'),
            'firstname' => Mage::helper('newsletterpopup')->__('Customer Firstname'),
            'lastname' => Mage::helper('newsletterpopup')->__('Customer Lastname'),
            'middlename' => Mage::helper('newsletterpopup')->__('Customer Middlename'),

            'region' => Mage::helper('newsletterpopup')->__('Customer Region'),
            'region_id' => Mage::helper('newsletterpopup')->__('Customer State/Province'),
            'country_id' => Mage::helper('newsletterpopup')->__('Customer Country'),

            // 'store_id' => Mage::helper('newsletterpopup')->__('Store ID'),
            // 'taxvat' => Mage::helper('newsletterpopup')->__('Taxvat'),
            // 'website_id' => Mage::helper('newsletterpopup')->__('Website ID'),
        );

        $this->setAttributeOption($attributes);

        return $this;
    }

    public function getAttributeElement()
    {
        $element = parent::getAttributeElement();
        $element->setShowAsText(true);
        return $element;
    }

    public function getInputType()
    {
        switch ($this->getAttribute()) {
            case 'age':
                return 'numeric';

            case 'country_id': case 'region_id':
                return 'select';

            case 'newsleter_subscribed': case 'gender': case 'group_id':
                return 'multiselect';

            case 'created_at': case 'dob':
                return 'date';
        }
        return 'string';
    }

    public function getValueElementType()
    {
        switch ($this->getAttribute()) {
            case 'country_id': case 'region_id':
                return 'select';

            case 'newsleter_subscribed': case 'gender': case 'group_id':
                return 'multiselect';

            case 'created_at': case 'dob':
                return 'date';
        }
        return 'text';
    }

    public function getExplicitApply()
    {
        switch ($this->getInputType()) {
            case 'date':
                return true;
        }
        return false;
    }

    public function getValueElement()
    {
        $params = array();

        if($this->getValueElementType() == 'date') {
            $params['image'] = Mage::getDesign()->getSkinUrl('images/grid-cal.gif');
        }

        return parent::getValueElement()->addData($params);
    }

    public function getValueSelectOptions()
    {
        if (!$this->hasData('value_select_options')) {
            switch ($this->getAttribute()) {
                case 'newsleter_subscribed':
                    $options = Mage::getModel('adminhtml/system_config_source_yesno')
                        ->toOptionArray();
                    break;

                case 'gender':
                    $options = Mage::getResourceSingleton('customer/customer')
                        ->getAttribute('gender')
                        ->getSource()
                        ->getAllOptions(false);
                    break;

                case 'group_id':
                    /*$options = Mage::getModel('adminhtml/system_config_source_customer_group')
                        ->toOptionArray();*/
                    $options = Mage::getResourceModel('customer/group_collection')
                        ->load()
                        ->toOptionArray();

                    $options[0]['label'] = 'NO ACCOUNT';
                    break;
                
                case 'country_id':
                    $options = Mage::getModel('adminhtml/system_config_source_country')
                        ->toOptionArray();
                        unset($options[0]);
                    break;

                case 'region_id':
                    $options = Mage::getModel('adminhtml/system_config_source_allregion')
                        ->toOptionArray();
                        unset($options[0]);
                    break;

                default:
                    $options = array();
            }

            $this->setData('value_select_options', $options);
        }
        return $this->getData('value_select_options');
    }

}