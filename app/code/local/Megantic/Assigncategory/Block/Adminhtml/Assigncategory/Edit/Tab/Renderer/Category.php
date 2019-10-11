<?php
class Megantic_Assigncategory_Block_Adminhtml_Assigncategory_Edit_Tab_Renderer_Category extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {

        /*$productCategories = array();
        $product = Mage::getModel('catalog/product')->load($row->getData('entity_id'));
        $categories = $product->getCategoryCollection()->addAttributeToSelect('name');
        foreach ($categories as $category) {
            array_push($productCategories, $category->getName());
        }
        return implode('<br>', $productCategories);*/

        $categoriesHtml = '';
        $categories     = $row->getCategoryCollection()->addNameToResult();
        if ($categories)
        {
            foreach ($categories as $category)
            {
                $path        = '';
                $pathInStore = $category->getPathInStore();
                $pathIds     = array_reverse(explode(',', $pathInStore));

                $categories = $category->getParentCategories();

                foreach ($pathIds as $categoryId) {
                    if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                        $path .= $categories[$categoryId]->getName() . '/';
                    }
                }

                if ($path)
                {
                    $path = substr($path, 0, -1);
                    $path = '<div style="font-size: 90%; margin-bottom: 8px; border-bottom: 1px dotted #bcbcbc;">' . $path . '</div>';
                }

                $categoriesHtml .= $path;
            }
        }
        return $categoriesHtml;



    }
}