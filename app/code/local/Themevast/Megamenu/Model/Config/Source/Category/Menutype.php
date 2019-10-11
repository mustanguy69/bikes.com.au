<?php
class Themevast_Megamenu_Model_Config_Source_Category_Menutype extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{	
	public function getAllOptions()
	{
        return array(
            array('value'=>'0', 'label'=>Mage::helper('adminhtml')->__('Default')),
            array('value'=>'1', 'label'=>Mage::helper('adminhtml')->__('Full')),
            array('value'=>'2', 'label'=>Mage::helper('adminhtml')->__('Grid')),
        );
    }
}

