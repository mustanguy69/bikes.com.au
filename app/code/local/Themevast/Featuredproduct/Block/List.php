<?php
class Themevast_Featuredproduct_Block_List extends Themevast_Featuredproduct_Block_Featuredproduct
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
        $todayDate  = Mage::app()->getLocale()->date()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
		
		 $category_id = $this->getCategoryId();

        $collection = Mage::getResourceModel('catalog/product_collection');
		
		        if($category_id) {
					$category = Mage::getModel('catalog/category')->load($category_id);    

					$collection->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
					->addCategoryFilter($category)
							->addAttributeToFilter('special_from_date', array('or'=> array(
								0 => array('date' => true, 'to' => $todayDate),
								1 => array('is' => new Zend_Db_Expr('null')))
							), 'left')
						->addAttributeToFilter('special_to_date', array('or'=> array(
							0 => array('date' => true, 'from' => $todayDate),
							1 => array('is' => new Zend_Db_Expr('null')))
							), 'left')
						->addAttributeToFilter(
							array(
								array('attribute' => 'special_from_date', 'is'=>new Zend_Db_Expr('not null')),
								array('attribute' => 'special_to_date', 'is'=>new Zend_Db_Expr('not null'))
								)
						  )
						->addAttributeToSort('special_to_date','desc')
						->addTaxPercents()
						->addStoreFilter()
						->setOrder($this->getRequest()->getParam('order'), $this->getRequest()->getParam('dir'))
						->addStoreFilter($storeId);
						
					} else {

					$collection->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
							->addAttributeToFilter('special_from_date', array('or'=> array(
								0 => array('date' => true, 'to' => $todayDate),
								1 => array('is' => new Zend_Db_Expr('null')))
							), 'left')
						->addAttributeToFilter('special_to_date', array('or'=> array(
							0 => array('date' => true, 'from' => $todayDate),
							1 => array('is' => new Zend_Db_Expr('null')))
							), 'left')
						->addAttributeToFilter(
							array(
								array('attribute' => 'special_from_date', 'is'=>new Zend_Db_Expr('not null')),
								array('attribute' => 'special_to_date', 'is'=>new Zend_Db_Expr('not null'))
								)
						  )
						->addAttributeToSort('special_to_date','desc')
						->addTaxPercents()
						->addStoreFilter()
						->setOrder($this->getRequest()->getParam('order'), $this->getRequest()->getParam('dir'))
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

