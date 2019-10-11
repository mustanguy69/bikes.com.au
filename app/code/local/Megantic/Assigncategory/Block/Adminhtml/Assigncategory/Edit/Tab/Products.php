<?php


class Megantic_Assigncategory_Block_Adminhtml_Assigncategory_Edit_Tab_Products extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('productGrid');
        $this->setUseAjax(true); // Using ajax grid is important
        $this->setDefaultSort('entity_id');
        // By default we have added a filter for the rows, that in_products value to be 1
        //$this->setDefaultFilter(array('in_products'=>1)); 
        $this->setSaveParametersInSession(false);  //Dont save paramters in session or else it creates problems
    }

    protected function _getStore() {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
  }
    
    protected function _prepareCollection()
    {
        $store = $this->_getStore();
        $collection = Mage::getModel('catalog/product')->getCollection()
                        ->addAttributeToSelect('sku')
                        ->addAttributeToSelect('name')
                        ->addAttributeToSelect('attribute_set_id')
                        ->addAttributeToSelect('thumbnail')
                        ->addAttributeToSelect('type_id')
                        ->joinField('qty',
                                'cataloginventory/stock_item',
                                'qty',
                                'product_id=entity_id',
                                '{{table}}.stock_id=1',
                                'left');

        if ($store->getId()) {
            //$collection->setStoreId($store->getId());
            $adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
            $collection->addStoreFilter($store);
            $collection->joinAttribute('name', 'catalog_product/name', 'entity_id', null, 'inner', $adminStore);
            $collection->joinAttribute('custom_name', 'catalog_product/name', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('price', 'catalog_product/price', 'entity_id', null, 'left', $store->getId());
        } else {
            $collection->addAttributeToSelect('price');
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        }

        $this->setCollection($collection);

        parent::_prepareCollection();
        $this->getCollection()->addWebsiteNamesToResult();
        return $this;
    }
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in product flag
        if ($column->getId() == 'in_products') {
            $ids = $this->_getSelectedProducts();
            if (empty($ids)) {
                $ids = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', array('in'=>$ids));
            } else {
                if($productIds) {
                    $this->getCollection()->addFieldToFilter('entity_id', array('nin'=>$ids));
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    protected function _prepareColumns()
    {

            $this->addColumn('in_products', array(
            'header_css_class' => 'a-center',
            'type' => 'checkbox',
            'values' => $this->_getSelectedProducts(),
            'align' => 'center',
            'index' => 'entity_id'
        ));

        
        
        $this->addColumn('entity_id',
                array(
                    'header' => Mage::helper('assigncategory')->__('ID'),
                    'width' => '40px',
                    'type' => 'number',
                    'index' => 'entity_id',
        ));


        $this->addColumn('image', array(
            'header' => Mage::helper('catalog')->__('Image'),
            'align' => 'left',
            'index' => 'image',
            'width'     => '97',
            'renderer' => 'Megantic_Assigncategory_Block_Adminhtml_Assigncategory_Edit_Tab_Renderer_Thumb'
        ));

        $this->addColumn('name',
                array(
                    'header' => Mage::helper('assigncategory')->__('Name'),
                    'index' => 'name',
                    'width' => '300px'
        ));


        // adding categories column

        $categoryFilter  = false;
        $categoryOptions = array();
        $categoryFilter = 'Megantic_Assigncategory_Block_Adminhtml_Assigncategory_Edit_Tab_Filter_Category';
        $categoryOptions = Mage::helper('assigncategory/category')->getOptionsForFilter();

        $this->addColumn('categories',
            array(
                'header'    => Mage::helper('catalog')->__('Categories'),
                'index'     => 'category_id',
                'renderer'  => 'Megantic_Assigncategory_Block_Adminhtml_Assigncategory_Edit_Tab_Renderer_Category',
                'sortable'  => false,
                //'filter'    => $categoryFilter,
                'filter_condition_callback' => array($this, '_callbackCategoryFilter'),
                'type'      => 'options',
                //'options'                   => $this->getCategoryOptions()
                'options'   => $categoryOptions
            ));



        $store = $this->_getStore();

        $this->addColumn('type',
                array(
                    'header' => Mage::helper('assigncategory')->__('Type'),
                    'width' => '60px',
                    'index' => 'type_id',
                    'type' => 'options',
                    'options' => Mage::getSingleton('catalog/product_type')->getOptionArray(),
        ));

        $sets = Mage::getResourceModel('eav/entity_attribute_set_collection')
                        ->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId())
                        ->load()
                        ->toOptionHash();

        $this->addColumn('sku',
                array(
                    'header' => Mage::helper('assigncategory')->__('SKU'),
                    'width' => '80px',
                    'index' => 'sku',
        ));

       $this->addColumn('position', array(
            'header'            => Mage::helper('assigncategory')->__('Position'),
            'name'              => 'position',
            'width'             => '10px',
            'type'              => 'number',
            'validate_class'    => 'validate-number',
            'index'             => 'position',
            'editable'          => true,
            'edit_only'         => true
        ));

        return parent::_prepareColumns();
    }

    public function getCategoryOptions()
{
    $option_array = array();
    $category_collection = Mage::getResourceModel('catalog/category_collection')
        ->addNameToResult()
        ->addAttributeToSort('position', 'asc');
    foreach ($category_collection as $category) {
        if ($category->getId() > 1 && $category->getName() != 'Root Catalog') {
            $option_array[$category->getId()] = $category->getName();
        }
    }
    return $option_array;
}

protected function _callbackCategoryFilter($collection, $column)
{
    if (!$value = $column->getFilter()->getValue()) {
        return null;
    }
    $collection->joinField(
        'category_id',
        'catalog/category_product',
        'category_id',
        'product_id = entity_id',
        '{{table}}.category_id=' . $column->getFilter()->getValue(),
        'inner'
    );
}

    public function getGridUrl()
    {
        return $this->_getData('grid_url') ? $this->_getData('grid_url') : $this->getUrl('*/*/productgridajax', array('_current'=>true));
    }
    
    protected function _getSelectedProducts()
  {
    $products = $this->getProductsAssigned();
    if (!is_array($products)) {
        $products = array_keys($this->getSelectedMyProducts());
    }
    return $products;
  }

    /**
     * Retrieve related products
     *
     * @return array
     */
    public function getSelectedMyProducts()
    {
      $products = array();
      /*foreach (Mage::registry('current_product')->getRelatedProducts() as $product) {
          $products[$product->getId()] = array('position' => $product->getPosition());
      }*/
      return $products;
    }


}
