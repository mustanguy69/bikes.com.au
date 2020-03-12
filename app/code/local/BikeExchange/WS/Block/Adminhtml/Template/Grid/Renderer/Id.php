<?php
class BikeExchange_WS_Block_Adminhtml_Template_Grid_Renderer_Id extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        if ($row->getBikeexchangeId() != null) {
            $out = "<input type='text' name='product[bikeexchange_id]' value='".$row->getBikeexchangeId()."' class='input-text' disabled>";
        } else {
            $out = "<input type='text' name='product[bikeexchange_id]'  class='input-text' >";
        }

        return $out;
    }
}