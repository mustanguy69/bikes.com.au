<?php
/**
 * Filename: Collection.php.
 * Author: Muhammad Shahab Hameed
 * Date: 10/14/2016
 */
class Folio3_UncancelOrder_Model_Resource_Status_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
	public function _construct()
	{
		$this->_init('folio3_uncancelorder/status');
	}
}