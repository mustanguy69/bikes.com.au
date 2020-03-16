<?php

class BikeExchange_WS_Model_Resource_Taxons extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('bikeexchange_ws/taxons', 'entity_id');
    }
}