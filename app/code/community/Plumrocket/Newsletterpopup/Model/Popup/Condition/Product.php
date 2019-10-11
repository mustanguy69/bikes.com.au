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


class Plumrocket_Newsletterpopup_Model_Popup_Condition_Product extends Mage_Rule_Model_Condition_Product_Abstract
{

    public function loadAttributeOptions()
    {
        parent::loadAttributeOptions();
        $attributes = array();
        foreach($this->getAttributeOption() as $code => $label) {
            $attributes[$code] = Mage::helper('newsletterpopup')->__('Product') .' '. $label;
        }

        $this->setAttributeOption($attributes);
        return $this;
    }

    public function validate(Varien_Object $object)
    {
        $result = false;
        if (!$object instanceof Mage_Catalog_object_Product) {
            $object = $object->getProduct();
        }

        if ($object && $object->getId()) {
            if (!$object->hasData($this->getAttribute())) {
                $object->load($object->getId());
            }

            $result = parent::validate($object);
        }

        return $result;
    }
}