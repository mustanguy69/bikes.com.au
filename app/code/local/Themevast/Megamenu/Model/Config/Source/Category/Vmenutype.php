<?php
class Themevast_Megamenu_Model_Config_Source_Category_Vmenutype extends Varien_Object
{	
	public function toOptionArray()
	{
        return array(
            array('value'=>'0', 'label'=>Mage::helper('adminhtml')->__('Default')),
            array('value'=>'1', 'label'=>Mage::helper('adminhtml')->__('Grid')),
        );
    }
}