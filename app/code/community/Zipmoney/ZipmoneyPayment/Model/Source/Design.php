<?php

class Zipmoney_ZipmoneyPayment_Model_Source_Design {

    public function toOptionArray() {
        return array(
            array(
                'value' => 'general',
                'label' => Mage::helper('core')->__('Standard')
            ),
            array(
                'value' => 'specific',
                'label' => Mage::helper('core')->__('Promotional')
            )
        );
    }

}
