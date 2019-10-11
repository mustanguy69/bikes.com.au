<?php
class Themevast_Producttabs_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getProducttabsCfg($cfg) 
    {
        $config = Mage::getStoreConfig('producttabs/general');
        if(isset($config[$cfg])) return $config[$cfg];
    }

    public function getProductCfg($cfg)
    {
        $config =  Mage::getStoreConfig('producttabs/product_show');
        if(isset($config[$cfg])) return $config[$cfg];
    }

}