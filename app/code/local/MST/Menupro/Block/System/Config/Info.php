<?php
class MST_Menupro_Block_System_Config_Info
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    
    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        //$html = base64_decode('PGRpdiBzdHlsZT0iY2xlYXI6IGJvdGg7IiA+PGEgaHJlZj0iaHR0cDovL21hZ2ViYXkuY29tLyIgdGFyZ2V0PSJfYmxhbmsiID48aW1nIHdpZHRoPSIxMDAlIiBzcmM9Imh0dHA6Ly9tYWdlYmF5LmNvbS9pbnRyby9pbnRyb19tYWdlYmF5LmpwZyIgYWx0PSIiIC8+PC9hPjwvZGl2Pg==');
        //$html = '<div style="clear: both;" ><a href="http://magebay.com/" target="_blank" ><img width="100%" src="http://magebay.com/intro/intro_magebay.jpg" alt="" /></a></div>';
        $html = '';
        return $html;
    }
}
