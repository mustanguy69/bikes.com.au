<?php
class Themevast_Bestseller_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $title = $this->__('Bestseller');
        $this->loadLayout();
        $this->getLayout()->getBlock("head")->setTitle($title);
        $this->renderLayout();
    }

}
