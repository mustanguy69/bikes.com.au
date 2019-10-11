<?php

class Zipmoney_ZipmoneyPayment_Model_Source_StripBannerPosition {

    public function toOptionArray() {
        return array(
            array(
                'value' => Zipmoney_ZipmoneyPayment_Model_Config::MARKETING_BANNERS_POSITION_TOP,
                'label' => Mage::helper('core')->__('top')
            ),
            // Hide what is not supported
//            array(
//                'value' => Zipmoney_ZipmoneyPayment_Model_Config::MARKETING_BANNERS_POSITION_BOTTOM,
//                'label' => Mage::helper('core')->__('bottom')
//            )
        );
    }

}
