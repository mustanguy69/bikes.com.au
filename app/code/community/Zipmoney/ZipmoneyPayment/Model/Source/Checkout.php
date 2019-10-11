<?php

class Zipmoney_ZipmoneyPayment_Model_Source_Checkout {

    public function toOptionArray() {
        return array(
            array(
                'value' => 'onepage',
                'label' => Mage::helper('core')->__('Onepage Checkout (Default)')
            ),
            array(
                'value' => 'onestep',
                'label' => Mage::helper('core')->__('OneStepCheckOut')
            )
        );
    }

}
