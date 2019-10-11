<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Environment
 *
 * @author vankh_000
 */
class Zipmoney_ZipmoneyPayment_Model_Source_Popupshow {
    public function toOptionArray() {
        return array(
            array(
                'value' => 'click',
                'label' => Mage::helper('core')->__('On Click')
            ),
            array(
                'value' => 'hover',
                'label' => Mage::helper('core')->__('On Hover')
            )
        );
    }
}
