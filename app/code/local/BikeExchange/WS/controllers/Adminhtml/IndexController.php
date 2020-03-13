<?php

class BikeExchange_WS_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('bikeexchange_ws/adminhtml_items'));
        $this->renderLayout();
    }

    public function popupAction()
    {
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('bikeexchange_ws/adminhtml_popup'));
        $this->renderLayout();
    }

    public function massDeleteAction()
    {
        $requestIds = $this->getRequest()->getParams('bikeexchange_delete_select');
        $helper = Mage::helper('bikeexchange_ws');
        foreach ($requestIds['bikeexchange_delete_select'] as $id) {
            $product = Mage::getModel('catalog/product')->load($id);
            $delete = $helper->bikeExchangeApiCall('client/adverts/'.$product->getBikeexchangeId(), 'DELETE');
            $deleteDecoded = json_decode($delete);
            $product->setData('bikeexchange_id', '');
            $product->setData('bikeexchange_status', false);
            $product->save();
        }

        Mage::getSingleton('core/session')->addSuccess('BikeExchange : Products deleted');

        $this->_redirect('*/*/');
    }

    public function massAddAction()
    {
        $requestIds = $this->getRequest()->getParams('bikeexchange_add_select');
        $helper = Mage::helper('bikeexchange_ws');
        $errors = 0;
        $imagesErrors = 0;
        $success = 0;
        foreach ($requestIds['bikeexchange_add_select'] as $id) {
            $product = Mage::getModel('catalog/product')->load($id);
            // creating advert
            $advertData = $helper->createUpdateAdvert($product);
            $addAdvert = $helper->bikeExchangeApiCall('client/adverts/', 'POST', json_encode($advertData));
            $addAdvertDecoded = json_decode($addAdvert , true);
            if (!isset($addAdvertDecoded['errors'])) {
                // Persisit ID from bikeexchange return
                $product->setData('bikeexchange_id', $addAdvertDecoded['data']['id']);
                $product->setData('bikeexchange_status', true);
                $product->getResource()->saveAttribute($product, 'bikeexchange_id');
                $product->getResource()->saveAttribute($product, 'bikeexchange_status');
                $success++;

            } else {
                $errors++;
            }

            // creating variants for this advert
            $variantData = $helper->createVariants($product);
            $addVariant = $helper->bikeExchangeApiCall('client/adverts/'.$addAdvertDecoded['data']['id'].'/variants', 'POST', json_encode($variantData));
            $addVariantDecoded = json_decode($addVariant, true);

            if (isset($addVariantDecoded['errors'])) {
                $errors++;
            }

            // Linking an image to the advert
            $imageData = $helper->createImage($product);
            $addImage = $helper->bikeExchangeApiCall('client/adverts/'.$addAdvertDecoded['data']['id'].'/images', 'POST', json_encode($imageData));
            $addImageDecoded = json_decode($addImage, true);

            if (isset($addImageDecoded['errors'])) {
                $imagesErrors++;
            }

        }

        if($success > 0) {
            Mage::getSingleton('core/session')->addSuccess('BikeExchange : ' . $success . ' Product Saved');
        }

        if($errors != 0) {
            Mage::getSingleton('core/session')->addError('BikeExchange : '.$errors.' errors occured during the adding');
        }

        if($imagesErrors != 0) {
            Mage::getSingleton('core/session')->addError('BikeExchange : '.$imagesErrors.' images not been imported');
        }

        return $this->popupAction();
    }
}