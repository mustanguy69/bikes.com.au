<?php

class Zipmoney_ZipmoneyPayment_Block_Catalog_Product_Tagline extends Zipmoney_ZipmoneyPayment_Block_Catalog_Product_Abstract
{
 
    public function _prepareLayout()
    {		
        if ($this->_isZipMoneyPaymentActive() &&   $this->_isActive()){
            $this->setTemplate('zipmoney/zipmoneypayment/catalog/product/view/tagline.phtml');
        }

    }

    private function _isActive()
    {
        return Mage::getStoreConfig(Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_WIDGET_CONFIGURATION_TAGLINE_ACTIVE_PRODUCT);
    }

}
