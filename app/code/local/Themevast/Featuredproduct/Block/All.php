<?php
class Themevast_Featuredproduct_Block_All extends Themevast_Featuredproduct_Block_Featuredproduct
{

	protected $_config = array();

	protected function _construct()
	{
		if(!$this->_config) $this->_config = Mage::getStoreConfig('featuredproduct/general'); 
	}

	public function getConfig($cfg = null)
	{
		if (isset($this->_config[$cfg]) ) return $this->_config[$cfg];
		return;
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
    	$storeId = Mage::app()->getStore()->getId();
    	$attributes = Mage::getSingleton('catalog/config')->getProductAttributes();

        $collection = Mage::getModel('catalog/product')->getCollection();
		
		 $category_id = $this->getCategoryId();

        $collection = Mage::getResourceModel('catalog/product_collection');
		
		        if($category_id) {
					$category = Mage::getModel('catalog/category')->load($category_id);    

				    $collection->addAttributeToSelect($attributes)
								->addCategoryFilter($category)
				              	->addMinimalPrice()
				              	->addFinalPrice()
				              	->addTaxPercents()
				              	->addAttributeToFilter('status', 1)
				              	->addStoreFilter($storeId);		
				} else {
				    $collection->addAttributeToSelect($attributes)
				              	->addMinimalPrice()
				              	->addFinalPrice()
				              	->addTaxPercents()
				              	->addAttributeToFilter('status', 1)
				              	->addStoreFilter($storeId);
												
				}
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
        
        $collection->setPageSize($this->getConfig('qty'))->setCurPage(1);
        Mage::getModel('review/review')->appendSummary($collection);     
        //var_dump($collection->getAllIds());                   
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

