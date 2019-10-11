<?php
/**
 * @package
 * @author dvs.spy (divyesh@tatvic)
 * @license Tatvic Enhanced Ecommerce
 */
class Tatvic_Uaee_Model_System_Config_Source_Orderid
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'entity_id', 'label'=>Mage::helper('tatvic_uaee')->__('ID')),
            array('value' => 'increment_id', 'label'=>Mage::helper('tatvic_uaee')->__('Increment ID')),
        );
    }
}