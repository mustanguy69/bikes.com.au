<?php

/**
 * Filename: Status.php.
 * Author: Muhammad Shahab Hameed
 * Date: 10/14/2016
 */
class Folio3_UncancelOrder_Model_System_Config_Status extends Varien_Object {

	public function toOptionArray() {
		return Mage::getModel( 'sales/order_status' )->getCollection()->toOptionArray();
	}
}