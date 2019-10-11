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


class Plumrocket_Newsletterpopup_Block_System_Config_FieldsTag extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->getLayout()->createBlock('newsletterpopup/system_config_fieldsTag_inputTable')
            ->setContainerFieldId($element->getName())
            ->setRowKey('name')
            ->addColumn('orig_label', array(
                'header'    => Mage::helper('newsletterpopup')->__('Newsletter Popup Field'),
                'index'     => 'orig_label',
                'type'      => 'label',
                'width'     => '36%',
            ))
            ->addColumn('label', array(
                'header'    => Mage::helper('newsletterpopup')->__('Mailchimp Field'),
                'index'     => 'label',
                'type'      => 'input',
                'width'     => '28%',
            ))
            ->setArray($element->getValue())
            ->toHtml();
    }

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = parent::render($element);
        $html = str_replace('<td class="value', '<td colspan="2" class="value', $html);
        // delete last td
        $html = str_replace('<td class=""></td>', '', $html);

        return $html;
    }
}