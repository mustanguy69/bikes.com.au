<?php


class BikeExchange_WS_Block_Adminhtml_Variants extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    // We declare the content of our items container
    public function __construct()
    {
        $this->_blockGroup = 'bikeexchange_ws';

        // The controller must match the second half of how we call the block
        $this->_controller = 'adminhtml_variants';

        $this->_headerText = Mage::helper('adminhtml')->__('BikeExchange Variants');
        parent::__construct();
    }
}