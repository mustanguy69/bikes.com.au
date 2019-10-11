<?php

class LarLance_Sales_Model_System_Config_Source_Shippingmethods
{
    public function toOptionArray()
    {
        if(!$this->_options) {
            $methodList = Mage::getStoreConfig('carriers');
            foreach ($methodList as $code => $methodData) {
                if('freeshipping' == $code) {
                    continue;
                }
                if (isset($methodData['title']) && $methodData['active'] == '1' && isset($methodData['title'])) {
                    $this->_options[] = ['value' => $code, 'label' => Mage::helper('sales')->__($methodData['title'])];
                }
            }
            array_unshift($this->_options, ['value' => '', 'label' => Mage::helper('sales')->__('')]);
        }
        return $this->_options;
    }
}