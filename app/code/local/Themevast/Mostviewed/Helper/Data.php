<?php

class Themevast_Mostviewed_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getConfig($cfg=null) 
	{
		$config = Mage::getStoreConfig('mostviewed');
		if (isset($config['general'][$cfg]) ) return $config['general'][$cfg];
		return $config;
	}
}

