<?php

class Themevast_Randomproduct_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getConfig($cfg=null) 
	{
		$config = Mage::getStoreConfig('randomproduct');
		if (isset($config['general'][$cfg]) ) return $config['general'][$cfg];
		return $config;
	}
}

