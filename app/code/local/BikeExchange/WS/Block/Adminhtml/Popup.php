<?php


class BikeExchange_WS_Block_Adminhtml_Popup extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    // We declare the content of our items container
    public function __construct()
    {
        $this->_blockGroup = 'bikeexchange_ws';

        // The controller must match the second half of how we call the block
        $this->_controller = 'adminhtml_popup';

        $this->_headerText = Mage::helper('adminhtml')->__('Add BikeExchange Items');

        parent::__construct();

        $this->_removeButton('add');
    }
}