<?php

class Themevast_Themevast_Block_Adminhtml_System_Config_Font extends Mage_Adminhtml_Block_System_Config_Form_Field {

	protected function _getElementHtml( Varien_Data_Form_Element_Abstract $element ) {
	$output = parent::_getElementHtml($element);
	$output.=
         '<span id="'.$element->getHtmlId().'_exs" style="font-size:30px;line-height: 30px; display:block;padding:10px 0 0 0; font-Family: '.Mage::getStoreConfig('themevast/color_general/my_font').'">Themevast $99.99</span>
         
                     <script type="text/javascript">
                         jQuery.noConflict();
                         var ff= "'.Mage::getStoreConfig('themevast/color_general/my_font').'";
                             jQuery("#'.$element->getHtmlId().'").change(function(){
                                 jQuery("#'.$element->getHtmlId().'_exs").css({ fontFamily: jQuery("#'.$element->getHtmlId().'").val().replace("+"," ") });
                                 jQuery("<link />",{href:"http://fonts.googleapis.com/css?family="+jQuery("#'.$element->getHtmlId().'").val(),rel:"stylesheet",type:"text/css"}).appendTo("head");
                             });
							jQuery(window).on("load",function(){
								 jQuery("<link />",{href:"http://fonts.googleapis.com/css?family="+ff,rel:"stylesheet",type:"text/css"}).appendTo("head");
							});
                     </script>';

        return $output;
	}

}
