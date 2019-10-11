<?php

class Megantic_Assigncategory_Adminhtml_AssigncategoryController extends Mage_Adminhtml_Controller_Action
{
	public function preDispatch()
	{
		parent::preDispatch();

      #register domain event starts
        
		$generalEmail = Mage::getStoreConfig('trans_email/ident_general/email');
      $domainName = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		

		Mage::dispatchEvent('megantic_domain_authentication',
						array(
						'email' => $generalEmail,
                  'domain_name'=>$domainName,
						)

		);
		#register domain event ends
  }

	protected function _initProduct()
  {
      $this->_title($this->__('Catalog'))
           ->_title($this->__('Manage Products'));

      $productId  = (int) $this->getRequest()->getParam('id');
      $product    = Mage::getModel('catalog/product')
          ->setStoreId($this->getRequest()->getParam('store', 0));

      if (!$productId) {
          if ($setId = (int) $this->getRequest()->getParam('set')) {
              $product->setAttributeSetId($setId);
          }

          if ($typeId = $this->getRequest()->getParam('type')) {
              $product->setTypeId($typeId);
          }
      }

      $product->setData('_edit_mode', true);
      if ($productId) {
          try {
              $product->load($productId);
          } catch (Exception $e) {
              $product->setTypeId(Mage_Catalog_Model_Product_Type::DEFAULT_TYPE);
              Mage::logException($e);
          }
      }

      $attributes = $this->getRequest()->getParam('attributes');
      if ($attributes && $product->isConfigurable() &&
          (!$productId || !$product->getTypeInstance()->getUsedProductAttributeIds())) {
          $product->getTypeInstance()->setUsedProductAttributeIds(
              explode(",", base64_decode(urldecode($attributes)))
          );
      }

      // Required attributes of simple product for configurable creation
      if ($this->getRequest()->getParam('popup')
          && $requiredAttributes = $this->getRequest()->getParam('required')) {
          $requiredAttributes = explode(",", $requiredAttributes);
          foreach ($product->getAttributes() as $attribute) {
              if (in_array($attribute->getId(), $requiredAttributes)) {
                  $attribute->setIsRequired(1);
              }
          }
      }

      if ($this->getRequest()->getParam('popup')
          && $this->getRequest()->getParam('product')
          && !is_array($this->getRequest()->getParam('product'))
          && $this->getRequest()->getParam('id', false) === false) {

          $configProduct = Mage::getModel('catalog/product')
              ->setStoreId(0)
              ->load($this->getRequest()->getParam('product'))
              ->setTypeId($this->getRequest()->getParam('type'));

          /* @var $configProduct Mage_Catalog_Model_Product */
          $data = array();
          foreach ($configProduct->getTypeInstance()->getEditableAttributes() as $attribute) {

              /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
              if(!$attribute->getIsUnique()
                  && $attribute->getFrontend()->getInputType()!='gallery'
                  && $attribute->getAttributeCode() != 'required_options'
                  && $attribute->getAttributeCode() != 'has_options'
                  && $attribute->getAttributeCode() != $configProduct->getIdFieldName()) {
                  $data[$attribute->getAttributeCode()] = $configProduct->getData($attribute->getAttributeCode());
              }
          }

          $product->addData($data)
              ->setWebsiteIds($configProduct->getWebsiteIds());
      }

      Mage::register('product', $product);
      Mage::register('current_product', $product);
      Mage::getSingleton('cms/wysiwyg_config')->setStoreId($this->getRequest()->getParam('store'));
      return $product;
  }
	public function editAction() 
	{
		$this->loadLayout();
		$this->_setActiveMenu('assigncategory/items');

		$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
		$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

		$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

		$this->_addContent($this->getLayout()->createBlock('assigncategory/adminhtml_assigncategory_edit'))
			->_addLeft($this->getLayout()->createBlock('assigncategory/adminhtml_assigncategory_edit_tabs'));

		$this->renderLayout();
		
	}
	public function saveAction()


	{

		if ($data = $this->getRequest()->getPost()) 
		{
			if(isset($data['links']))
			{
		    	$products = Mage::helper('adminhtml/js')->decodeGridSerializedInput($data['links']['products']); //Save the array to your database
				$selectedProductIds = array();
			   	foreach($products as $_key => $_p ){
			   		array_push($selectedProductIds, $_key);
			   	}
				//$data['selectedProduct'] = implode(',',$selectedProductIds);
		  }	
              

			$addToCat = array_unique(explode(",",$data['category_ids']));
			$addToCat = array_filter($addToCat, 'strlen');
			
			
			$removeToCat = array_unique(explode(",",$data['category_ids_remove']));
			$removeToCat = array_filter($removeToCat, 'strlen');
			

			/*
				*  For get ids of category to move
				*/
			$moveToCat = array_unique(explode(",",$data['category_ids_move']));
			$moveToCat = array_filter($moveToCat, 'strlen');




			if(!empty($selectedProductIds)){

				if(!empty($removeToCat)){
				foreach($selectedProductIds as $id){
				
					$product = Mage::getModel('catalog/product')->load($id);
					$productAssignedCat = $product->getCategoryIds();
					Mage::log($moveToCat, null, 'mylogfile.log');
					
					$product->setCategoryIds(
      									array_diff($productAssignedCat, $removeToCat)
  									);
				  $product->save();
				}
			}

				/*
				*  For move product category
				*/
				if(!empty($moveToCat)){
					foreach($selectedProductIds as $id){
				
					$product = Mage::getModel('catalog/product')->load($id);
					$productAssignedCat = $product->getCategoryIds();
					
					$product->setCategoryIds($moveToCat);
				  $product->save();
				}}

				if(!empty($addToCat)){
				foreach($selectedProductIds as $id){
				
					$product = Mage::getModel('catalog/product')->load($id);
					$productAssignedCat = $product->getCategoryIds();
					
					$product->setCategoryIds(
      									array_merge($productAssignedCat, $addToCat)
  									);
				  $product->save();
				}
			}
				
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('assigncategory')->__('Action has been successfully performed'));
				$this->_redirect('*/*/edit');
				return;
			}else{
			
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('assigncategory')->__('Unable to perform action'));
        $this->_redirect('*/*/edit');
        return;
			
			}
		}
	}
	public function productgridAction(){
	  $this->loadLayout();
	  $this->getLayout()->getBlock('product.grid')
	  ->setProductsAssigned($this->getRequest()->getPost('products', null));
	  $this->renderLayout();
	}
	public function productgridajaxAction(){
    $this->loadLayout();
    $this->getLayout()->getBlock('product.grid')
    ->setProductsAssigned($this->getRequest()->getPost('products', null));
    $this->renderLayout();
  }
	public function categoriesAction()
  {
		$this->_initProduct();
    $this->loadLayout();
    $this->renderLayout();
  }
	public function categoriesremoveAction()
	{
		$this->_initProduct();
		$this->loadLayout();
		$this->renderLayout();
	}

  /*edited*/public function categoriesmoveAction()
  {
    $this->_initProduct();
    $this->loadLayout();
    $this->renderLayout();
 }
  /*edited*/

	public function categoriesJsonAction()
  {
		$product = $this->_initProduct();
    $this->getResponse()->setBody(
        $this->getLayout()->createBlock('assigncategory/adminhtml_assigncategory_edit_tab_categories')
            ->getCategoryChildrenJson($this->getRequest()->getParam('category'))
    );
  }
  public function categoriesremoveJsonAction()
  {
		$product = $this->_initProduct();
    $this->getResponse()->setBody(
        $this->getLayout()->createBlock('assigncategory/adminhtml_assigncategory_edit_tab_categoriesRemove')
            ->getCategoryChildrenJson($this->getRequest()->getParam('category'))
    );
  }

  /*edited*/public function categoriesmoveJsonAction()
  {
    $product = $this->_initProduct();
    $this->getResponse()->setBody(
        $this->getLayout()->createBlock('assigncategory/adminhtml_assigncategory_edit_tab_categoriesMove')
            ->getCategoryChildrenJson($this->getRequest()->getParam('category'))
    );
  }/*edited*/
}
