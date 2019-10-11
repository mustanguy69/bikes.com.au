<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Cart
 */
class Amasty_Cart_Block_Catalog_Product_List_Related extends Mage_Catalog_Block_Product_List_Related
{
    const MAX_ITEM_COUNT = 3;

    protected function _prepareData()
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::registry('product');

        $this->_itemCollection = $product->getRelatedProductCollection()
            ->addAttributeToSelect('required_options')
            ->setPositionOrder()
            ->addStoreFilter();

        if (Mage::helper('catalog')->isModuleEnabled('Mage_Checkout')) {
            Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter(
                $this->_itemCollection,
                Mage::getSingleton('checkout/session')->getQuoteId()
            );
            $this->_addProductAttributesAndPrices($this->_itemCollection);
        }
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection(
            $this->_itemCollection
        );

        /* Amasty code start add limit and stock filter*/
        Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($this->_itemCollection);
        $this->_itemCollection->getSelect()->limit(self::MAX_ITEM_COUNT);
        /*Amasty code end*/

        $this->_itemCollection->load();

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        if (Mage::helper('core')->isModuleEnabled('Amasty_GiftCard')) {
            $this->addPriceBlockType(
                'amgiftcard',
                'amgiftcard/catalog_product_price',
                'amasty/amgiftcard/catalog/product/price.phtml'
            );
        }
        $this->addPriceBlockType('bundle', 'bundle/catalog_product_price', 'bundle/catalog/product/price.phtml');

        return $this;
    }
}
