<?php

class Zipmoney_ZipmoneyPayment_Model_Source_StripBannerSize {

    public function toOptionArray() {
        return array(
            array(
                'value' => Zipmoney_ZipmoneyPayment_Model_Config::MARKETING_BANNERS_STRIP_SIZE_1,
                'label' => Mage::helper('core')->__('800*50 (example)')
            ),
            array(
                'value' => Zipmoney_ZipmoneyPayment_Model_Config::MARKETING_BANNERS_STRIP_SIZE_2,
                'label' => Mage::helper('core')->__('850*60 (example)')
            )
        );
    }

}
