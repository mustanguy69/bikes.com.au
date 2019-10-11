<?php
class Themevast_Producttabs_Block_Producttabs extends Mage_Core_Block_Template
{

    public function getProducttabsCfg($cfg)
    {
        return Mage::helper('producttabs')->getProducttabsCfg($cfg);
    }

    public function getProductCfg($cfg)
    {
        return Mage::helper('producttabs')->getProductCfg($cfg);
    }

    public function getTypeDefault()
    {
        $setdef = explode(',', $this->getProductCfg('product_type'));
        return $setdef[0];   return $setdef[1];
    }

    public function sortTabs()
    {
        //return Mage::getStoreConfig('producttabs/general/sort_name');
        return $this->getProducttabsCfg('sort_name');
    }

	public function getTabs()
	{
        $types = Mage::getSingleton("producttabs/system_config_type")->toOptionArray();
        $cfg = $this->getProductCfg('product_type');
        $cfg = explode(',', $cfg);
        $tabs = array();
        foreach ($types as $type) {
            if(in_array($type['value'], $cfg)){
                $tabs[$type['value']] = $type['label'];
            }
        }

        return $tabs;
	}


    public function setBxslider()
    {
  

        $auto           = (int) $this->getProducttabsCfg('auto');
        $speed          = (int) $this->getProducttabsCfg('speed');
        $video          = (int) $this->getProducttabsCfg('video');
        $vertical       = (int) $this->getProducttabsCfg('vertical');
        $minSlides      = (int) $this->getProducttabsCfg('minslides');
        $maxSlides      = (int) $this->getProducttabsCfg('maxslides');
        $moveSlides     = (int) $this->getProducttabsCfg('moveslides');
        $startSlide     = (int) $this->getProducttabsCfg('startslide');
        $slideWidth     = (int) $this->getProducttabsCfg('slidewidth');
        $slideMargin    = (int) $this->getProducttabsCfg('slidemargin');
	
	$script =  'slideMargin: 20,';
       
        if($auto)           $script    .= "auto: $auto,";
        if($speed)          $script    .= "speed: $speed,";
        if($vertical)       $script    .= "mode: 'vertical',";
        if($minSlides)      $script    .= "minSlides: $minSlides,";
        if($maxSlides)      $script    .= "maxSlides: $maxSlides,";
        if($moveSlides)     $script    .= "moveSlides: $moveSlides,";
        if($startSlide)     $script    .= "startSlide: $startSlide,";
        if($slideWidth)     $script    .= "slideWidth: $slideWidth,";
        if($slideMargin)    $script    .= "slideMargin: $slideMargin,";

        return $script;

    }
     public function getRow(){
        $rows = (int) $this->getRequest()->getPost('rows');
        return $rows;
    }

}