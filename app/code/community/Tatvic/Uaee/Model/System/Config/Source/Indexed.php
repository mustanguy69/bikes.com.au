<?php
/**
 * @package
 * @author Dvs.spy (divyesh@tatvic.com)
 * @license Tatvic Enhanced Ecommerce
 */
class Tatvic_Uaee_Model_System_Config_Source_Indexed
{
    public function toOptionArray()
    {
        return array(
			 array('value' => '1', 'label'=>Mage::helper('tatvic_uaee')->__('1')),
			 array('value' => '2', 'label'=>Mage::helper('tatvic_uaee')->__('2')),
			 array('value' => '3', 'label'=>Mage::helper('tatvic_uaee')->__('3')),
			 array('value' => '4', 'label'=>Mage::helper('tatvic_uaee')->__('4')),
			 array('value' => '5', 'label'=>Mage::helper('tatvic_uaee')->__('5')),
            
          //  array('value' => 'before_body_end', 'label'=>Mage::helper('tatvic_uaee')->__('Before Body End')),
        );
    }
}