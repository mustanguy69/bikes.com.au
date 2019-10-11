<?php
class Themevast_Themevast_Model_System_Config_Source_Themecolor
{
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }

    public function getAllOptions($withEmpty = true)
    {
        $options = array(
            
            
            array('value'=>'color/home5_red.css', 'label'=>Mage::helper('adminhtml')->__('Red')),
            array('value'=>'color/home5_blue.css', 'label'=>Mage::helper('adminhtml')->__('Blue')),
            array('value'=>'color/home5_green.css', 'label'=>Mage::helper('adminhtml')->__('Green')),
             
        );
        $label = $options ? Mage::helper('core')->__('-- Please Select --') : Mage::helper('core')->__('-- One Color --');
        if ($withEmpty) {
            array_unshift($options, array(
                'value' => '',
                'label' => $label
            ));
        }
        return $options;
    }

}
