<?php

class Themevast_Bestseller_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getConfig($cfg=null) 
	{
		$config = Mage::getStoreConfig('bestseller');
		if (isset($config['general'][$cfg]) ) return $config['general'][$cfg];
		return $config;
	}
}

