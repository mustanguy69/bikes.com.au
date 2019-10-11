<?php

class Zipmoney_ZipmoneyPayment_Model_Source_Positioncart {
    public function toOptionArray() {
        return array(
            array(
                'value' => 'above',
                'label' => Mage::helper('core')->__('Above button')
            ),
            array(
                'value' => 'below',
                'label' => Mage::helper('core')->__('Below button')
            )
        );
    }
}
