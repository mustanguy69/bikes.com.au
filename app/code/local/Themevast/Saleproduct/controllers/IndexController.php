<?php
class Themevast_Saleproduct_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $title = $this->__('Saleproduct');
        $this->loadLayout();
        $this->getLayout()->getBlock("head")->setTitle($title);
        $this->renderLayout();
    }

}
