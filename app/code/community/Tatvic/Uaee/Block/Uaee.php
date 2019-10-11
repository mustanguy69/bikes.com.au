<?php
class Tatvic_Uaee_Block_Uaee extends Mage_Core_Block_Template
{
    public $_order;

    public function getAccountId()
    {
        return Mage::getStoreConfig('tatvic_uaee/general/account_id');
    }
	public function isAnon()
	{
		if(Mage::getStoreConfigFlag('tatvic_uaee/support/AnonIP')){
			return true;
		}
		return false;
	}
	public function isUserIDEnable(){
		if(Mage::getStoreConfigFlag('tatvic_uaee/support/CustID')){
			return true;
		}
		return false;
	
	}
	public function isContentGrouping(){
		if(Mage::getStoreConfigFlag('tatvic_uaee/support/ContentGrouping')){
			return true;
		}
		return false;
	
	}
    public function isActive()
    {
        if(Mage::getStoreConfigFlag('tatvic_uaee/general/enable')
            ){
                return true;
        }
        return false;
    }
	public function isPromoEnable(){
		if(Mage::getStoreConfigFlag('tatvic_uaee/support/promoToggle')
            ){
                return true;
        }
        return false;
	}
	public function getBrandAttr(){
		
		return Mage::getStoreConfig('tatvic_uaee/ecommerce/brand') != "" ? Mage::getStoreConfig('tatvic_uaee/ecommerce/brand') : "";
	}
    public function isEcommerce()
    {
        $successPath =  Mage::getStoreConfig('tatvic_uaee/ecommerce/success_url') != "" ? Mage::getStoreConfig('tatvic_uaee/ecommerce/success_url') : '/checkout/onepage/success';
        if(Mage::getStoreConfigFlag('tatvic_uaee/general/enable')
            && strpos($this->getRequest()->getPathInfo(), $successPath) !== false){
                return true;
        }
        return false;
    }

    public function isCheckout()
    {
        $checkoutPath =  Mage::getStoreConfig('tatvic_uaee/ecommerce/checkout_url') != "" ?  Mage::getStoreConfig('tatvic_uaee/ecommerce/checkout_url') : '/checkout/onepage';
        if(Mage::getStoreConfigFlag('tatvic_uaee/general/enable')
            && strpos($this->getRequest()->getPathInfo(), $checkoutPath) !== false){
            return true;
        }
        return false;
    }
	
    public function getCheckoutUrl()
    {
       return Mage::getStoreConfig('tatvic_uaee/ecommerce/checkout_url') != "" ?  Mage::getStoreConfig('tatvic_uaee/ecommerce/checkout_url') : '/checkout/onepage';
    }

    public function getActiveStep()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn() ? 'billing' : 'login';
    }

   
    /**
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if(!isset($this->_order)){
            $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
            $this->_order = Mage::getModel('sales/order')->load($orderId);
        }
        return $this->_order;
    }

    public function getTransactionIdField()
    {
        return 'entity_id';
    }

    public function getCustomerGroup()
    {
        $groupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        return Mage::getModel('customer/group')->load($groupId)->getCode();
    }

    public function getNumberOfOrders()
    {
        return Mage::getResourceModel('sale/order_collection')
            ->addFieldToFilter('customer_email', array('eq' => $this->getOrder()->getCustomerEmail()))
            ->getSize();
    }
   
    /**
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return Mage::registry('current_product');
    }

	public function getCategory()
	{
		$_helper = $this->helper('catalog/output');
		$_category_detail=Mage::registry('current_category'); 
		return $_category_detail;
	}
	public function getCategoryName($pid)
	{
		$product = Mage::getModel('catalog/product')->load($pid);

		$cats = $product->getCategoryIds();
		foreach ($cats as $category_id) {
		   $_cat = Mage::getModel('catalog/category')->load($category_id) ;
			return $_cat->getName();
		}
	}
	/* Tatvic Module : Get Product Details */
	public function getProductModel($productid)
	{
		 Mage::getModel('catalog/product');
		
		
	}
	public function getCatalog()
	{
		return Mage::registry('current_category');
	}
	public function getProducts(){
	//$categories = Mage::getResourceModel('catalog/product_collection')->getCollection();
    $categories=Mage::getModel('catalog/category')->getCollection();
    $result;
    foreach($categories as $cat)
    {
        $temp = null;
        $_temp = null;
        $are = false;
        $_cat = $cat->load();
        $temp['category'] = $_cat->getName();
        $prod = $_cat->getProductCollection()
                     ->addAttributeToFilter('name', array('like'=>'%'.$this->getRequest()->getParam('q').'%'));
        foreach($prod as $p){
            //die(print_r($p->load()));
            $_temp[] = $p->load();
            $are = true;
        }
        if($are){
            $temp[] = $_temp;
            $result[] = $temp;
        }
    }
    return $result;
	}
	public function getPromotions()
	{
		return Mage::getStoreConfig('tatvic_uaee/support/promotions') !='' ? Mage::getStoreConfig('tatvic_uaee/support/promotions') : 'empty';
	}
	public function getThreshold()
	{
		return Mage::getStoreConfig('tatvic_uaee/support/Threshold') != '' ? Mage::getStoreConfig('tatvic_uaee/support/Threshold') : '20'; 
               
	}
    public function getHomeId()
    {
        return Mage::getStoreConfig('tatvic_uaee/ecommerce/home_id') != '' ? Mage::getStoreConfig('tatvic_uaee/ecommerce/home_id') : '';
    }
	
	
}

