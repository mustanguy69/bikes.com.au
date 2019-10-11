<?php

/**
 * Filename: Status.php.
 * Author: Muhammad Shahab Hameed
 * Date: 10/14/2016
 */
class Folio3_UncancelOrder_Model_Resource_Status extends Mage_Core_Model_Resource_Db_Abstract {
	protected function _construct() {
		$this->_init( 'folio3_uncancelorder/status', 'folio3_uncancelorder_id' );
	}
}