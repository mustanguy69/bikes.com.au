<?php

class BikeExchange_WS_Adminhtml_TaxonsController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('bikeexchange_ws/adminhtml_taxons'));
        $this->renderLayout();
    }

    public function editAction()
    {
        $taxon = Mage::getModel('bikeexchange_ws/taxons');
        $taxon->load($this->getRequest()->getParam('id', false));

        if ($postData = $this->getRequest()->getPost('taxonData')) {
            try {
                $taxon->addData($postData);
                $taxon->save();

                $this->_getSession()->addSuccess(
                    $this->__('The taxon has been saved.')
                );

                return $this->_redirect(
                    'bikeexchange/adminhtml_taxons/index');
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($e->getMessage());
            }
        }

        Mage::register('current_taxon', $taxon);

        $this->loadLayout()
            ->renderLayout();
    }

    public function newAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('bikeexchange_ws/bikeexchange-taxons');
        $this->renderLayout();
    }

    public function deleteAction()
    {
        $taxon = Mage::getModel('bikeexchange_ws/taxons');
        $taxon->load($this->getRequest()->getParam('id', false));
        $taxon->delete();

        $this->_getSession()->addSuccess(
            $this->__('The taxon has been deleted.')
        );

        return $this->_redirect(
            'bikeexchange/adminhtml_taxons/index');
    }


}