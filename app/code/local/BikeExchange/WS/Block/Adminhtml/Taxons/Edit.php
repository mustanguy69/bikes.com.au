<?php
class BikeExchange_WS_Block_Adminhtml_Taxons_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    protected function _construct()
    {
        $this->_blockGroup = 'bikeexchange_ws';
        $this->_controller = 'adminhtml_taxons';

        $this->_mode = 'edit';

        $newOrEdit = $this->getRequest()->getParam('id')
            ? $this->__('Edit')
            : $this->__('New');
        $this->_headerText =  $newOrEdit . ' ' . $this->__('Taxons');
    }

}