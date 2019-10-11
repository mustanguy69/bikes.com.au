<?php

class Zipmoney_ZipmoneyPayment_Model_Source_Position {

    public function toOptionArray() {
        return array(
            array(
                'value' => 'price',
                'label' => Mage::helper('core')->__('Below price')
            ),
            array(
                'value' => 'button',
                'label' => Mage::helper('core')->__('Below button')
            )
        );
    }

}
