<?php
class BikeExchange_WS_Model_Resource_Taxons_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        parent::_construct();

        $this->_init(
            'bikeexchange_ws/taxons',
            'bikeexchange_ws/taxons'
        );
    }
}