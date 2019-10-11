<?php

/**
 * EaDesgin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eadesign.ro so we can send you a copy immediately.
 *
 * @category    Eadesigndev_Shipestimation
 * @copyright   Copyright (c) 2008-2015 EaDesign by Eco Active S.R.L.
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
class Eadesigndev_Shipestimation_IndexController extends Mage_Core_Controller_Front_Action
{


    public function indexAction()
    {
    }

    public function quotationAction()
    {
        $shippingblock = $this->getLayout()->createBlock('checkout/cart_shipping');
        $country = $this->getRequest()->getParam('country_id');		/* echo 'Country: '.$country.' '; */
        $regionId = $this->getRequest()->getParam('region_id');		/* echo 'Region: '.$regionId.' '; */
        $cityId = $this->getRequest()->getParam('city_id');		/* echo 'City: '.$cityId.' '; */
        $zipId = $this->getRequest()->getParam('zip_id');		/* echo 'Zip: '.$zipId. ' '; */
        $productId = $this->getRequest()->getParam('productId');		/* echo 'Product: '.$productId. ' '; */		        $_product = Mage::getModel('catalog/product')->load($productId);
		$params = $this->getRequest()->getParams();		$reqOb = new Varien_Object($params);		$_product->getStockItem()->setUseConfigManageStock(false);        $_product->getStockItem()->setManageStock(false);
        $quote = Mage::getModel('sales/quote');

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCountryId($country);
        $shippingAddress->setRegionId($regionId);
        $shippingAddress->setCity($cityId);
        $shippingAddress->setPostcode($zipId);
        $shippingAddress->setCollectShippingRates(true);


        $quote->addProduct($_product, $reqOb);
        $quote->getShippingAddress()->collectTotals();
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();

        $rates = $quote->getShippingAddress()->getGroupedAllShippingRates();


        if(empty($rates)){
             echo  Mage::helper('shipping')->__('There are no rates available');
        }


        foreach ($rates as $code=>$rate) {
            $carierName = '<div class="cariername"><strong>'
                . $shippingblock->getCarrierName($code)
                .'</strong></div>';
            echo $carierName;

            foreach($rate as $r){
                $price = Mage::helper('core')->currency($r->getPrice(), true, false);
                $rates  = '<div>&bull;&nbsp;&nbsp;'
                    . $r->getMethodTitle()
                    . ' <strong>'
                    . $price
                    .' </strong><br/>';
                echo $rates;
            }
        }
        return;		
    }
}
