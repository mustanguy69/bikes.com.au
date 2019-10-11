<?php

class Themevast_Producttabs_Model_System_Config_Type
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'mostviewed', 'label'=>Mage::helper('adminhtml')->__('Most Viewed')),
            array('value' => 'newproduct', 'label'=>Mage::helper('adminhtml')->__('New Arrivals')),
            array('value' => 'random', 'label'=>Mage::helper('adminhtml')->__('Random <i class="fa fa-refresh" aria-hidden="true" style="vertical-align: middle; font-size: 24px;"></i>')),
            array('value' => 'featured', 'label'=>Mage::helper('adminhtml')->__('Featured')),
            array('value' => 'saleproduct', 'label'=>Mage::helper('adminhtml')->__('Hot Deals')),
            array('value' => 'bestseller', 'label'=>Mage::helper('adminhtml')->__('Best Sellers')),

            
        );
    }
}