<?php

class LarLance_Shipping_Block_Options extends Mage_Catalog_Block_Product_View_Options
{


    public function getAvailableShippingMethods(){
        $shippingOptions = Mage::getModel('larlancesales/system_config_source_shippingmethods')->toOptionArray();
        $product = $this->getProduct();
        $allowedShippingForProduct = Mage::helper('larlance_shipping')->getAllowedShippingForProduct($product);

        foreach($shippingOptions as $key=>$shippingOption) {
            if(!in_array($shippingOption['label'],$allowedShippingForProduct)) {
                unset($shippingOptions[$key]);
            }
        }
        return $shippingOptions;
    }

    public function isProductClickAndCollect()
    {
        $product = $this->getProduct();
        return Mage::helper('larlance_shipping')->isProductClickAndCollect($product);
    }
}