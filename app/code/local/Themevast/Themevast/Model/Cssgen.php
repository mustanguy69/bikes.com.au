<?php 

class Themevast_Themevast_Model_Cssgen extends Mage_Core_Model_Abstract
{

//    public function saveCss($store)
//    {   
//        $mycolor = Mage::getStoreConfig('themevast/color_general',$store);
//        $headercolor = Mage::getStoreConfig('themevast/color_header',$store);
//        $footercolor = Mage::getStoreConfig('themevast/color_footer',$store);
//        $dirPath = Mage::getBaseDir('skin') . '/frontend/base/default/css/';
//        $filePath = $dirPath .$store. 'color.css';
// 
//        $css = 
//        '
//body {
//    color:#'.$mycolor['my_color'].'!important;
//    font: '.$mycolor['font_size'].'!important;
//    font-family:'.$mycolor['my_font'].'!important;
//    text-align: left;
//}
//.header-wrapper{
//    background: #'.$headercolor['h_color'].'!important;
//}      
//.footer-wrapper{
//    background: none repeat scroll 0 0  #'.$footercolor['f_color'].'!important;
//}
//        ';
//            $fw = new Varien_Io_File(); 
//            $fw->setAllowCreateFolders(true); 
//            $fw->open(array('path' => $dirPath));
//            $fw->streamOpen($filePath, 'w+'); 
//            $fw->streamLock(true); 
//            $fw->streamWrite($css); 
//            $fw->streamUnlock(); 
//            $fw->streamClose(); 
//
//        return true;
//    }
}