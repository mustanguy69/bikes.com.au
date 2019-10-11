<?php

class Zipmoney_ZipmoneyPayment_Model_Source_Frequency {

    public function toOptionArray() {
        $arr = array();
        for ($i = 1; $i <= 30; $i++) {
            $arr[] = array(
                'value' => $i,
                'label' => Mage::helper('core')->__($i)
            );
        }
        return $arr;
    }

}
