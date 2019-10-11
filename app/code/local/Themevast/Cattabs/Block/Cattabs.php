<?php
class Themevast_Cattabs_Block_Cattabs extends Mage_Catalog_Block_Product_Abstract
{
    // public function getPalayDelay()
    // {
    //     if(!$this->getData('play_delay')) return 0;
    //     return (int)$this->getData('play_delay');
    // }

    public function getTabs()
    {
        $catIds = $this->getCatIds(); 
        $cats = Mage::getModel('catalog/category')->getCollection()
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('entity_id', array('in' => explode(',', $this->getData('cat_ids'))));
        $tabs = array();
        foreach ($cats as $cat) {
            $tabs[$cat->getId()] = $cat->getName();
        }
        return $tabs;
    }

    public function getOptions()
    {
        $options = array();
        $options['limit'] = $this->getData('limit');
        $options['widthImage']  = $this->getData('widthImage');
        $options['heightImage'] = $this->getData('heightImage');
        $options['rows'] = $this->getData('rows');
        return json_encode($options);
    }
	// public function getLoadedProductCollection() 
 //    {
 //        $catId = (int) $this->getRequest()->getPost('catId');
 //        $limit = (int) $this->getRequest()->getPost('limit');
 //        $widthImage = (int) $this->getRequest()->getPost('widthImage');
 //        $heightImage = (int) $this->getRequest()->getPost('heightImage');
 //        $_category = Mage::getModel('catalog/category')->load($catId);
 //        $product = Mage::getModel('catalog/product');
 //        $_productCollection = $product->getCollection()
 //                                    ->addAttributeToSelect('*')
 //                                    ->addCategoryFilter($_category);
	// 	Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($_productCollection);
	// 	Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($_productCollection);
	// 	$_productCollection->setPageSize($limit);
 //        $_productCollection->load();
	// 	return $_productCollection;
		
 //    }

    public function getLoadedProductCollection()  
    {
        $catId = (int) $this->getRequest()->getPost('catId');
        $limit = (int) $this->getRequest()->getPost('limit');
        $category = Mage::getModel('catalog/category')->load($catId);
        $collection = $category->getProductCollection()->addAttributeToSort('position');
        Mage::getModel('catalog/layer')->prepareProductCollection($collection);
        $collection->setPageSize($limit);
        $collection->load();
        return $collection;
        
    }

   

    public function getConfig($cfg) {
        $config = Mage::getStoreConfig('cattabs');
        if (isset($config['general'][$cfg]) ) return $config['general'][$cfg];
    }

    public function generateRandomString($length = 10) {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
    public function getRow(){
        $rows = (int) $this->getRequest()->getPost('rows');
        return $rows;
    }
}
