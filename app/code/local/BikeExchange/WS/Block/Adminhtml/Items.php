<?php


class BikeExchange_WS_Block_Adminhtml_Items extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    // We declare the content of our items container
    public function __construct()
    {
        $this->_blockGroup = 'bikeexchange_ws';

        // The controller must match the second half of how we call the block
        $this->_controller = 'adminhtml_items';

        $this->_headerText = Mage::helper('adminhtml')->__('BikeExchange Items');

        parent::__construct();
    }
}