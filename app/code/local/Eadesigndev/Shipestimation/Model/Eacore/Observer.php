<?php
class  Eadesigndev_Shipestimation_Model_Eacore_Observer
{
    public function preDispatch(Varien_Event_Observer $observer)
    {
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {

            $feedModel  = Mage::getModel('shipestimation/eacore_feed');

            $feedModel->checkUpdate();
        }
    }
}