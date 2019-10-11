<?php
/**
 * Created by PhpStorm.
 * User: Jane
 * Date: 5/23/2016
 * Time: 9:23 AM
 */

class Megantic_Assigncategory_Block_Adminhtml_Assigncategory_Edit_Tab_Renderer_Thumb extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $val = Mage::helper('catalog/image')->init($row, 'thumbnail')->resize(97);
        $out = "<img src=". $val ." width='97px'/>";
        return $out;
    }

}