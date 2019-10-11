<?php
class LarLance_Sales_Helper_Data extends Mage_Core_Helper_Abstract
{
    const ALLOW_WITH_FREESHIPPING = 'shipping/option/allow_with_freeshipping';
    const SHOW_ORIGINAL = 'shipping/option/show_original';
    /**
     * @return array
     */
    public function getShippingMethodsAllowedWithFree()
    {
        $methodList = Mage::getStoreConfig(self::ALLOW_WITH_FREESHIPPING);
        $allowedMethods = array_filter(explode(',', $methodList));
        return $allowedMethods;
    }


    public function isShowOriginal()
    {
        return Mage::getStoreConfig(self::SHOW_ORIGINAL);
    }
}
	 