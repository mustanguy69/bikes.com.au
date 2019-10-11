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


class Plumrocket_Newsletterpopup_Model_Backend_FieldsTag extends Mage_Core_Model_Config_Data
{
    protected $_fields = array(
        'email'         => 'EMAIL',
        'firstname'     => 'FNAME',
        'middlename'    => 'MNAME',
        'lastname'      => 'LNAME',
        'suffix'        => 'SUFFIX',
        'dob'           => 'DOB',
        'gender'        => 'GENDER',
        'taxvat'        => 'TAXVAT',
        'prefix'        => 'PRENAME',
        'telephone'     => 'TELEPHONE',
        'fax'           => 'FAX',
        'company'       => 'COMPANY',
        'street'        => 'STREET',
        'city'          => 'CITY',
        'country_id'    => 'COUNTRY',
        'region '       => 'STATE',
        'postcode'      => 'ZIPCODE',
        'coupon'        => 'COUPON',
    );

    public function parseValue($value)
    {
        $result = $this->_getFields();
        $values = json_decode($value);
        if ($values) {
            foreach ($values as $name => $value) {
                $result[$name]['label'] = (!empty($value))? (string)$value: $result[$name]['label'];
            }
        }

        return $result;
    }

    protected function _afterLoad()
    {
        $value = $this->parseValue($this->getValue());
		$this->setValue($value);
		parent::_afterLoad();
    }
 
    protected function _beforeSave()
    {
    	$toSave = array();
    	$values = $this->getValue();
    	$result = $this->_getFields();

    	foreach ($values as $name => $value) {
    		if (array_key_exists($name, $result)) {
    			$toSave[$name] = isset($value['label'])? (string)$value['label']: '';
    		}
    	}

    	$this->setValue(json_encode($toSave));
        parent::_beforeSave();
    }

    protected function _getFields()
    {
        $systemItems = Mage::helper('newsletterpopup')->getPopupFormFields(0, false);

        $result = array();
        foreach ($this->_fields as $key => $value) {
            $result[$key] = array(
                'name'      => $key,
                'orig_label'=> isset($systemItems[$key])? $systemItems[$key]->getData('label') : ucfirst($key),
                'label'     => $value,
            );
        }
        
        return $result;
    }

}