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


class Plumrocket_Newsletterpopup_Model_Popup_Condition_Combine extends Mage_Rule_Model_Condition_Combine
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('newsletterpopup/popup_condition_combine');
    }

    public function getNewChildSelectOptions()
    {
        $generalCondition = Mage::getModel('newsletterpopup/popup_condition_general');
        $generalAttributes = $generalCondition->loadAttributeOptions()->getAttributeOption();
        $general = array();
        foreach ($generalAttributes as $code=>$label) {
            $general[] = array('value'=>'newsletterpopup/popup_condition_general|'.$code, 'label'=>$label);
        }

        $customerCondition = Mage::getModel('newsletterpopup/popup_condition_customer');
        $customerAttributes = $customerCondition->loadAttributeOptions()->getAttributeOption();
        $customer = array();
        foreach ($customerAttributes as $code=>$label) {
            $customer[] = array('value'=>'newsletterpopup/popup_condition_customer|'.$code, 'label'=>$label);
        }

        $cartCondition = Mage::getModel('newsletterpopup/popup_condition_cart');
        $cartAttributes = $cartCondition->loadAttributeOptions()->getAttributeOption();
        $cart = array();
        foreach ($cartAttributes as $code=>$label) {
            $cart[] = array('value'=>'newsletterpopup/popup_condition_cart|'.$code, 'label'=>$label);
        }

        $productCondition = Mage::getModel('newsletterpopup/popup_condition_product');
        $productAttributes = $productCondition->loadAttributeOptions()->getAttributeOption();
        $product = array();
        foreach ($productAttributes as $code=>$label) {
            $product[] = array('value'=>'newsletterpopup/popup_condition_product|'.$code, 'label'=>$label);
        }

        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive($conditions, array(
            array('value'=>'newsletterpopup/popup_condition_combine', 'label'=>Mage::helper('newsletterpopup')->__('Conditions combination')),
            array('value'=>'newsletterpopup/popup_condition_found', 'label'=>Mage::helper('newsletterpopup')->__('Product attribute combination in shopping cart')),
            array('label'=>Mage::helper('newsletterpopup')->__('General'), 'value'=>$general),
            array('label'=>Mage::helper('newsletterpopup')->__('Customer Attribute'), 'value'=>$customer),
            array('label'=>Mage::helper('newsletterpopup')->__('Cart Attribute'), 'value'=>$cart),
            array('label'=>Mage::helper('newsletterpopup')->__('Current Product Page'), 'value'=>$product),
        ));

        $additional = new Varien_Object();
        Mage::dispatchEvent('newsletterpopup_rule_condition_combine', array('additional' => $additional));
        if ($additionalConditions = $additional->getConditions()) {
            $conditions = array_merge_recursive($conditions, $additionalConditions);
        }

        return $conditions;
    }
}
