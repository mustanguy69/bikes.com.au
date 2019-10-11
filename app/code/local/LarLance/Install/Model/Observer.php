<?php

class LarLance_Install_Model_Observer
{

    public function saveRelatedProducts(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Category $category */
        $category = $observer->getData('category');
//        $category->getProductCollection()->getAllIds();

        if (!empty($category)) {
            $isClearRelated = $category->getData('clear_related_products');
            $relatedProducts = $category->getData('related_products');
            $curentRelatedIds = $formatedArray = $allRelatedProductIds = array();

            if(!empty($relatedProducts)||($isClearRelated !== 0)){
                $currentCategory = Mage::getModel('catalog/category')->load($category->getData('id'));
                $productCollection = Mage::getResourceModel('catalog/product_collection')
                    ->addCategoryFilter($currentCategory)
                    ->getAllIds();

                if(!empty($productCollection)){
                    foreach ($productCollection as $productId){
                        $product = Mage::getModel('catalog/product')->load($productId);
                        /** @var Mage_Catalog_Model_Resource_Product_Link_Product_Collection $related_product_collection */
//                        $related_product_collection = $product->getRelatedProductCollection();
//                        $curentRelatedIds = $related_product_collection->getAllIds();
                        $allRelatedProductIds = $product->getRelatedProductIds();
                        if ($isClearRelated == 1){
                            $allRelatedProductIds = [];
                        } else {

                        }
                        if (!empty($relatedProducts)){
                            $arrRelatedProductsSKU = explode(",", str_replace(" ","",$relatedProducts));

                            /** @var Mage_Catalog_Model_Resource_Product_Collection $productsNewRelated */
                            $productsNewRelated = Mage::getModel('catalog/product')->getCollection();
                            $productsNewRelated->addAttributeToFilter('sku', array('in' => $arrRelatedProductsSKU));

                            $newProductids = $productsNewRelated->getAllIds();

                            $curentRelatedIds = array_unique(array_merge($allRelatedProductIds, $newProductids));

                        }

                        foreach ($curentRelatedIds as $id) {
                            $formatedArray[$id] = array('position' => 0);
                        }

                        $product->setRelatedLinkData($formatedArray);
                        $product->save();
                    }
                }
            }
            $category->setData('clear_related_products', 0);
            $category->setData('related_products', '');
        };
    }
}
