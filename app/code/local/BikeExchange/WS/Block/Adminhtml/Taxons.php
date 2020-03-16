<?php


class BikeExchange_WS_Block_Adminhtml_Taxons extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    // We declare the content of our items container
    public function __construct()
    {
        $this->_blockGroup = 'bikeexchange_ws';

        // The controller must match the second half of how we call the block
        $this->_controller = 'adminhtml_taxons';

        $this->_headerText = Mage::helper('adminhtml')->__('BikeExchange Taxons');
        parent::__construct();
    }

    public function getCreateUrl()
    {
        return $this->getUrl(
            'bikeexchange/adminhtml_taxons/edit'
        );
    }
}