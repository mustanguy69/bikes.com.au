<?php

class BikeExchange_WS_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('bikeexchange_ws/adminhtml_items'));
        $this->renderLayout();
    }
}