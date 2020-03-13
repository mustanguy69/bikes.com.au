<?php

class BikeExchange_WS_Model_Observer
{
    public function catalog_product_save_after($observer)
    {
        // Get the current product data
        $product = $observer->getProduct();
        // Get the Helper with the api connection
        $helper = Mage::helper('bikeexchange_ws');
        // If BikeExchange sync enabled
        if ($product->getBikeexchangeStatus()) {
            // If BikeExchange ID set
            if($product->getBikeexchangeId()) {
                // Get BikeExchange product with the ID
                $bikeExchangeProduct = json_decode($helper->bikeExchangeApiCall('client/adverts/'.$product->getBikeexchangeId(), 'GET'), true);
                // If BikeExchange product get
                if (!isset($bikeExchangeProduct['errors'])) {
                    // Update Data and send to BikeExchange
                    $advertData = $helper->createUpdateAdvert($product);
                    $updateAdvert = $helper->bikeExchangeApiCall('client/adverts/'.$product->getBikeexchangeId(), 'PUT', json_encode($advertData));
                    $updateAdvertDecode = json_decode($updateAdvert, true);

                    // Display Success or Errors
                    if (!isset($updateAdvertDecode['errors'])) {
                        Mage::getSingleton('core/session')->addSuccess('BikeExchange : Product Saved');
                    } else {
                        Mage::getSingleton('core/session')->addError('BikeExchange : An error has occurred');
                    }
                } else {
                    Mage::getSingleton('core/session')->addError('BikeExchange : An error has occurred, wrong id or wrong config');
                }

            } else {
                // Magento Dont get BikeExchange ID but sync is enabled --> creation

                // creating advert
                $advertData = $helper->createUpdateAdvert($product);
                $addAdvert = $helper->bikeExchangeApiCall('client/adverts/', 'POST', json_encode($advertData));
                $addAdvertDecoded = json_decode($addAdvert , true);

                // creating variants for this advert
                $variantData = $helper->createVariants($product);
                $addVariant = $helper->bikeExchangeApiCall('client/adverts/'.$addAdvertDecoded['data']['id'].'/variants', 'POST', json_encode($variantData));
                $addVariantDecoded = json_decode($addVariant, true);

                // Linking an image to the advert
                $imageData = $helper->createImage($product);
                $addImage = $helper->bikeExchangeApiCall('client/adverts/'.$addAdvertDecoded['data']['id'].'/images', 'POST', json_encode($imageData));
                $addImageDecoded = json_decode($addImage, true);

                if (isset($addVariantDecoded['errors'])) {
                    Mage::getSingleton('core/session')->addError('BikeExchange : Error on the variant, check the details');
                }

                if (isset($addImageDecoded['errors'])) {
                    Mage::getSingleton('core/session')->addError('BikeExchange : Image not imported');
                }

                if (!isset($addAdvertDecoded['errors'])) {
                    // Persisit ID from bikeexchange return
                    $product->setData('bikeexchange_id', $addAdvertDecoded['data']['id']);
                    $product->getResource()->saveAttribute($product, 'bikeexchange_id');

                    Mage::getSingleton('core/session')->addSuccess('BikeExchange : Product Saved');
                } else {
                    Mage::getSingleton('core/session')->addError('BikeExchange : An error has occurred, product not saved');
                }
            }
        } else {
            // Delete product from BikeExchange + empty id field
            if($product->getBikeexchangeId()) {
                $delete = $helper->bikeExchangeApiCall('client/adverts/'.$product->getBikeexchangeId(), 'DELETE');
                $deleteDecoded = json_decode($delete, true);
                $product->setData('bikeexchange_id', '');
                $product->setData('bikeexchange_status', false);
                $product->save();
                if (!isset($deleteDecoded['errors'])) {
                    Mage::getSingleton('core/session')->addSuccess('BikeExchange : Product deleted');
                }
            }
        }
    }

    public function catalog_product_load_after($observer) {
        $event = $observer->getEvent();
        $product = $event->getProduct();

        if ($product->getBikeexchangeId() !== null) {
            $product->lockAttribute('bikeexchange_id');
        }
    }
}