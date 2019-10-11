<?php
class LarLance_Sales_Model_Sales_Quote_Address extends Mage_Sales_Model_Quote_Address
{
    /**
     * Retrieve all grouped shipping rates
     *
     * @return array
     */
    public function getGroupedAllShippingRates()
    {
        $rates = array();
        foreach ($this->getShippingRatesCollection() as $rate) {
            if (!$rate->isDeleted() && $rate->getCarrierInstance()) {
                if (!isset($rates[$rate->getCarrier()])) {
                    $rates[$rate->getCarrier()] = array();
                }

                $rates[$rate->getCarrier()][] = $rate;
                $rates[$rate->getCarrier()][0]->carrier_sort_order = $rate->getCarrierInstance()->getSortOrder();
            }
        }

        if($this->isClickAndCollectInCart()) {
            $filteredMethods = $this->_filterShippingMethods($rates, LarLance_Shipping_Helper_Data::CLICK_AND_COLLECT_ONLY, true);
        }
        elseif($this->isDeliveryOnly()) {
            $filteredMethods = $this->_filterShippingMethods($rates, LarLance_Shipping_Helper_Data::DELIVERY_ONLY, false);
        }
        else {
            $filteredMethods = $rates;
        }

        $allowedMethod = [];
        if (array_key_exists('freeshipping', $filteredMethods)) {
            $allowedMethodsConfig = Mage::helper('larlancesales')->getShippingMethodsAllowedWithFree();
            // BCA-25 26 - hide regular shipping option when free shipping applies via cart rule criteria. Except methods in special setting
            foreach ($filteredMethods as $key=>$rate) {
                if('freeshipping' == $key || (count($allowedMethodsConfig) > 0 && in_array($rate[0]->getCarrierTitle(),$allowedMethodsConfig))) {
                    $allowedMethods[$key] = $rate;
                }
            }
        }
        else {
            $allowedMethods = $filteredMethods;
        }

        if(Mage::helper('larlancesales')->isShowOriginal() && count($allowedMethods) == 0) {
            $allowedMethods = $rates;
        }
        uasort($allowedMethods, array($this, '_sortRates'));
        return $allowedMethods;
    }

    protected function _filterShippingMethods($rates, $allowedMethod, $exceptFree = false)
    {
        $allowedRates = [];
        $attrShippingMethodRelation = Mage::helper('larlance_shipping')->getAttributeShipingMethodRestriction();
        $allowedMethodDelivery = $attrShippingMethodRelation[$allowedMethod];
        foreach ($rates as $key=>$rate) {
            if($rate[0]->getCarrierTitle() == $allowedMethodDelivery || (!$exceptFree && $key=='freeshipping') ) {
                $allowedRates[$key] = $rate;
            }
        }
        return $allowedRates;
    }


    public function isClickAndCollectInCart()
    {
        $quote = $this->getQuote();
        $cartItems = $quote->getAllVisibleItems();
        foreach ($cartItems as $item) {
            $product = $item->getProduct();
            $deliveryMethodValue = Mage::getModel('catalog/product')->getResource()
                    ->getAttributeRawValue($product->getId(), 'delivery_method', Mage::app()->getStore());

            $deliveryMethod = $product->getResource()->getAttribute('delivery_method')->getSource()->getOptionText($deliveryMethodValue);
            if($deliveryMethod == LarLance_Shipping_Helper_Data::CLICK_AND_COLLECT_ONLY) {
                return true;
            }
        }
        return false;
    }

    public function isDeliveryOnly()
    {
        $quote = $this->getQuote();
        $cartItems = $quote->getAllVisibleItems();
        foreach ($cartItems as $item) {
            $product = $item->getProduct();
            $deliveryMethodValue = Mage::getModel('catalog/product')->getResource()
                ->getAttributeRawValue($product->getId(), 'delivery_method', Mage::app()->getStore());

            $deliveryMethod = $product->getResource()->getAttribute('delivery_method')->getSource()->getOptionText($deliveryMethodValue);
            if($deliveryMethod == LarLance_Shipping_Helper_Data::DELIVERY_ONLY) {
                return true;
            }
        }
        return false;
    }
}
		