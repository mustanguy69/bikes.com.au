<?php
class Themevast_Cattabs_IndexController extends Mage_Core_Controller_Front_Action 
{
    public function ajaxAction() {
        if ($this->getRequest()->isAjax()) {
            $this->loadLayout()->renderLayout();
            return $this;
        }       
    }
}

