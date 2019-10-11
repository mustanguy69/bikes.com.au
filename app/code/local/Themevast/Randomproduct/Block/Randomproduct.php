<?php
class Themevast_Randomproduct_Block_Randomproduct extends Mage_Catalog_Block_Product_Abstract
{

	protected $_config = array();

	protected function _construct()
	{
		if(!$this->_config) $this->_config = Mage::getStoreConfig('randomproduct/general'); 
	}

	public function getConfig($cfg = null)
	{
		if (isset($this->_config[$cfg]) ) return $this->_config[$cfg];
		return ; // return $this->_config;
	}

	public function getColumnCount()
	{
		$slide = $this->getConfig('slide');
		$rows  = $this->getConfig('rows');
		if($slide && $rows >1) $column = $rows;
		else $column = $this->getConfig('qty');
		return $column;
	}

    protected function getProductCollection()
    {
        $collection = Mage::getResourceModel('catalog/product_collection')
                            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
							->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
                            ->addMinimalPrice()
                            ->addTaxPercents()
                            ->addStoreFilter();
        $collection->getSelect()->order('rand()');
        $collection->setPageSize($this->getConfig('qty'))->setCurPage(1);		
        Mage::getModel('review/review')->appendSummary($collection);                       
        return $collection;
    }

    public function generateRandomString($length = 10) {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

}

