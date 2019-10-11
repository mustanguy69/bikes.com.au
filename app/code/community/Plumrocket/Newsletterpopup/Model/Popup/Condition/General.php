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


class Plumrocket_Newsletterpopup_Model_Popup_Condition_General extends Mage_Rule_Model_Condition_Abstract
{
    public function loadAttributeOptions()
    {
        $attributes = array(
            'current_device'    => Mage::helper('newsletterpopup')->__('Device Type'),
            'current_page_type' => Mage::helper('newsletterpopup')->__('Current Page Type'),
            'current_cms_page'  => Mage::helper('newsletterpopup')->__('Current CMS Page'),
            'current_page_url'  => Mage::helper('newsletterpopup')->__('Current Page URL'),
            'category_ids'      => Mage::helper('newsletterpopup')->__('Current Category Page'),
            'prev_popups_count' => Mage::helper('newsletterpopup')->__('Number of Displayed Popups Per Session'),
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
            case 'current_device': case 'current_page_type': case 'current_cms_page':
                return 'multiselect';

            case 'category_ids':
                return 'category';

            case 'prev_popups_count':
                return 'numeric';
        }
        return 'string';
    }

    public function getValueElementType()
    {
        switch ($this->getAttribute()) {
            case 'current_device': case 'current_page_type': case 'current_cms_page':
                return 'multiselect';
        }
        return 'text';
    }

    public function getDefaultOperatorInputByType()
    {
        if (null === $this->_defaultOperatorInputByType) {
            parent::getDefaultOperatorInputByType();
            /*
             * '{}' and '!{}' are left for back-compatibility and equal to '==' and '!='
             */
            $this->_defaultOperatorInputByType['string'] = array('==', '!=', '{}', '!{}', '()', '!()');
            $this->_defaultOperatorInputByType['multiselect'] = array('()', '!()');
            $this->_defaultOperatorInputByType['category'] = array('()', '!()');
            $this->_defaultOperatorInputByType['numeric'] = array('==', '!=', '>=', '>', '<=', '<');
            $this->_arrayInputTypes[] = 'category';
        }
        return $this->_defaultOperatorInputByType;
    }

    public function getValueElementChooserUrl()
    {
        $url = false;
        switch ($this->getAttribute()) {
            case 'category_ids':
                $url = 'adminhtml/promo_widget/chooser'
                    .'/attribute/'.$this->getAttribute();
                if ($this->getJsFormObject()) {
                    $url .= '/form/'.$this->getJsFormObject();
                }
                break;
        }
        return $url!==false ? Mage::helper('adminhtml')->getUrl($url) : '';
    }

    public function getExplicitApply()
    {
        switch ($this->getAttribute()) {
            case 'category_ids':
                return true;
        }
        switch ($this->getInputType()) {
            case 'date':
                return true;
        }
        return false;
    }

    public function getValueAfterElementHtml()
    {
        $html = '';

        switch ($this->getAttribute()) {
            case 'category_ids':
                $image = Mage::getDesign()->getSkinUrl('images/rule_chooser_trigger.gif');
                break;
        }

        if (!empty($image)) {
            $html = '<a href="javascript:void(0)" class="rule-chooser-trigger"><img src="' . $image . '" alt="" class="v-middle rule-chooser-trigger" title="' . Mage::helper('rule')->__('Open Chooser') . '" /></a>';
        }
        return $html;
    }

    public function getValueSelectOptions()
    {
        if (!$this->hasData('value_select_options')) {
            switch ($this->getAttribute()) {
                case 'current_device':
                    $options = Mage::getModel('newsletterpopup/values_devices')
                        ->toOptionArray();
                    break;
                
                case 'current_page_type':
                    $options = Mage::getSingleton('newsletterpopup/values_show')
                        ->toOptionArray();
                    break;

                case 'current_cms_page':
                    $options = Mage::getModel('newsletterpopup/values_page')
                        ->toOptionArray();
                    break;

                /*case 'category_ids':
                    $options = Mage::getModel('adminhtml/system_config_source_category')
                        ->toOptionArray();
                    break;*/

                default:
                    $options = array();
            }
            $this->setData('value_select_options', $options);
        }
        return $this->getData('value_select_options');
    }

}