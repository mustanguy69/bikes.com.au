<?php
class LarLance_Shipping_Helper_Data extends Mage_Core_Helper_Abstract
{
    const CLICK_AND_COLLECT = 'Click and Collect';
    const CLICK_AND_COLLECT_ONLY = 'Click and Collect Only';
    const DELIVERY_ONLY = 'Delivery Only';

    public function getAllowedShippingForProduct($product)
    {
        $deliveryValue = $product->getAttributeText('delivery_method');
        $attributeShipingMethodRestriction = $this->getAttributeShipingMethodRestriction();

        $allowedMethods = array();
        if(isset($attributeShipingMethodRestriction[$deliveryValue])) {
            $allowedMethods[$deliveryValue] = $attributeShipingMethodRestriction[$deliveryValue];
        }
        if(count($allowedMethods) == 0) {
            $allowedMethods = $attributeShipingMethodRestriction;
        }
        return $allowedMethods;
    }

    public function getAttributeShipingMethodRestriction()
    {
        return array(self::DELIVERY_ONLY => 'Delivery',
                      self::CLICK_AND_COLLECT_ONLY => 'IN-STORE PICKUP',
                   );
    }

    public function isProductClickAndCollect($product) {
        $deliveryValue = strtolower($product->getAttributeText('delivery_method'));
        if(!empty($deliveryValue) && strpos($deliveryValue, strtolower(self::CLICK_AND_COLLECT)) === false) {
            return false;
        }
        return true;
    }

    public function isProductClickAndCollectOnly($product) {
        $deliveryValue = strtolower($product->getAttributeText('delivery_method'));
        return (bool)(strtolower(self::CLICK_AND_COLLECT_ONLY) == $deliveryValue);
    }
}