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


class Plumrocket_Newsletterpopup_Block_Adminhtml_Newsletter_Subscriber_Grid extends Mage_Adminhtml_Block_Newsletter_Subscriber_Grid
{
    protected function _prepareColumns()
    {
        if (!Mage::helper('newsletterpopup')->moduleEnabled()) {
            return parent::_prepareColumns();
        }

        $this->addColumn('subscriber_id', array(
            'header'    => Mage::helper('newsletter')->__('ID'),
            'index'     => 'subscriber_id'
        ));

        $this->addColumn('email', array(
            'header'    => Mage::helper('newsletter')->__('Email'),
            'index'     => 'subscriber_email'
        ));

        $this->addColumn('type', array(
            'header'    => Mage::helper('newsletter')->__('Type'),
            'index'     => 'type',
            'type'      => 'options',
            'options'   => array(
                1  => Mage::helper('newsletter')->__('Guest'),
                2  => Mage::helper('newsletter')->__('Customer')
            )
        ));

        $this->_processAdditionalField('prefix');

        $this->addColumn('firstname', array(
            'header'    => Mage::helper('newsletter')->__('First Name'),
            'index'     => 'customer_firstname',
            'default'   =>    '----'
        ));

        $this->_processAdditionalField('middlename');

        $this->addColumn('lastname', array(
            'header'    => Mage::helper('newsletter')->__('Last Name'),
            'index'     => 'customer_lastname',
            'default'   =>    '----'
        ));

        $this->_processAdditionalField('suffix');

        $this->addColumn('status', array(
            'header'    => Mage::helper('newsletter')->__('Status'),
            'index'     => 'subscriber_status',
            'type'      => 'options',
            'options'   => array(
                Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE   => Mage::helper('newsletter')->__('Not Activated'),
                Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED   => Mage::helper('newsletter')->__('Subscribed'),
                Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED => Mage::helper('newsletter')->__('Unsubscribed'),
                Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED => Mage::helper('newsletter')->__('Unconfirmed'),
            )
        ));

        $this->_processAdditionalField('dob');
        $this->_processAdditionalField('gender');
        $this->_processAdditionalField('taxvat');

        $this->_processAdditionalField('telephone');
        $this->_processAdditionalField('fax');
        $this->_processAdditionalField('company');

        $this->_processAdditionalField('country_id');
        $this->_processAdditionalField('region');
        $this->_processAdditionalField('city');
        $this->_processAdditionalField('street');
        $this->_processAdditionalField('postcode');

        $this->addColumn('website', array(
            'header'    => Mage::helper('newsletter')->__('Website'),
            'index'     => 'website_id',
            'type'      => 'options',
            'options'   => $this->_getWebsiteOptions()
        ));

        $this->addColumn('group', array(
            'header'    => Mage::helper('newsletter')->__('Store'),
            'index'     => 'group_id',
            'type'      => 'options',
            'options'   => $this->_getStoreGroupOptions()
        ));

        $this->addColumn('store', array(
            'header'    => Mage::helper('newsletter')->__('Store View'),
            'index'     => 'store_id',
            'type'      => 'options',
            'options'   => $this->_getStoreOptions()
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('customer')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('customer')->__('Excel XML'));
        return Mage_Adminhtml_Block_Widget_Grid::_prepareColumns();
    }

    private function _processAdditionalField($field)
    {
        if (!$this->_showColumn($field)) {
            return false;
        }

        switch ($field) {
            case 'middlename':
                $this->addColumn('middlename', array(
                    'header'    => Mage::helper('customer')->__('Middle Name'),
                    'index'     => 'subscriber_middlename',
                    'default'   => '----'
                ));
                break;
            case 'suffix':
                $this->addColumn('suffix', array(
                    'header'    => Mage::helper('customer')->__('Suffix'),
                    'index'     => 'subscriber_suffix',
                ));
                break;
            case 'dob':
                $this->addColumn('dob', array(
                    'header'    => Mage::helper('customer')->__('Date of Birth'),
                    'index'     => 'subscriber_dob',
                    'type'      => 'date',
                ));
                break;
            case 'gender':
                $options = Mage::getResourceSingleton('customer/customer')->getAttribute('gender')->getSource()->getAllOptions();
                if (isset($options[0]) && $options[0]['value'] == '') {
                    unset($options[0]);
                }
                $this->addColumn('gender', array(
                    'header'    => Mage::helper('customer')->__('Gender'),
                    'index'     => 'subscriber_gender',
                    'type'      => 'options',
                    'options'    => $this->_getOptions($options),
                ));
                break;
            case 'taxvat':
                $this->addColumn('taxvat', array(
                    'header'    => Mage::helper('customer')->__('Tax/VAT Number'),
                    'index'     => 'subscriber_taxvat',
                ));
                break;
            case 'prefix':
                $this->addColumn('prefix', array(
                    'header'    => Mage::helper('customer')->__('Prefix'),
                    'index'     => 'subscriber_prefix',
                ));
                break;
            case 'telephone':
                $this->addColumn('subscriber_telephone', array(
                    'header'    => Mage::helper('customer')->__('Telephone'),
                    'index'     => 'subscriber_telephone'
                ));
                break;
            case 'fax':
                $this->addColumn('subscriber_fax', array(
                    'header'    => Mage::helper('customer')->__('Fax'),
                    'index'     => 'subscriber_fax'
                ));
                break;
            case 'company':
                $this->addColumn('subscriber_company', array(
                    'header'    => Mage::helper('customer')->__('Company'),
                    'index'     => 'subscriber_company'
                ));
                break;
            case 'street':
                $this->addColumn('subscriber_street', array(
                    'header'    => Mage::helper('customer')->__('Street'),
                    'index'     => 'subscriber_street'
                ));
                break;
            case 'city':
                $this->addColumn('subscriber_city', array(
                    'header'    => Mage::helper('customer')->__('City'),
                    'index'     => 'subscriber_city'
                ));
                break;
            case 'country_id':
                $this->addColumn('subscriber_country_id', array(
                    'header'    => Mage::helper('customer')->__('Country'),
                    'index'     => 'subscriber_country_id',
                    'type'      => 'options',
                    'options'   => $this->_getAllCountry()
                ));
                break;
            case 'region':
                $this->addColumn('subscriber_region', array(
                    'header'    => Mage::helper('customer')->__('Region'),
                    'index'     => 'subscriber_region'
                ));
                break;
            case 'postcode':
                $this->addColumn('subscriber_postcode', array(
                    'header'    => Mage::helper('customer')->__('Postcode'),
                    'index'     => 'subscriber_postcode'
                ));
                break;
            default:
                return false;
        }
    }

    protected function _getAllCountry()
    {
        $countries = array();
        $options = Mage::getResourceModel('directory/country_collection')->load()->toOptionArray(false);
        foreach($options as $options){
            $countries[$options['value']]=$options['label'];
        }
        return $countries;
    }

    protected function _showColumn($field)
    {
        $size = Mage::getResourceModel('newsletter/subscriber_collection')
            ->addFieldToFilter("subscriber_{$field}", array('neq' => ''))
            ->addFieldToFilter("subscriber_{$field}", array('neq' => '0000-00-00'))
            ->getSize();
        return $size > 0;
    }
}
