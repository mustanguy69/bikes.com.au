<?php
class Themevast_Megamenu_Block_Megamenu extends Mage_Catalog_Block_Navigation
{
    // protected $config        = array();
    protected $catMega       = 'megamenu_catid_%d';
    protected $catMegaRight  = 'megamenu_catid_%d_right';
    protected $megamenuExtra = 'megamenu_extra';
    protected $megamenuLink  = 'megamenu_link';

    // protected function _construct()
    // {
    //     parent::_construct();

    //     $this->config = Mage::helper('megamenu')->getGeneralCfg();
    // }

    public function drawMegamenuHome()
    {
        $html ='';
        $showhome = Mage::getStoreConfig('megamenu/general/showhome');
        if($showhome){
            $active = '';
            $homePage = Mage::getStoreConfig('web/default/cms_home_page');
            if(Mage::getSingleton('cms/page')->getIdentifier() == $homePage 
                && Mage::app()->getFrontController()->getRequest()->getRouteName() == 'cms') {
                $active = ' act';  
            }

            $html .= '<li id="megamenu_home" class="level0 home' . $active . '">';
                $html .= '<a href="'.Mage::helper('core/url')->getHomeUrl().'"><span>' .$this->__('Home'). '</span></a>';
            $html .= '</li>';
        } 
        return $html;       
    }

    public function drawMegamenuMain()
    {
        $html ='';
        $cats = Mage::helper('catalog/category')->getStoreCategories();
        if(count($cats)){
            $item = 1;
            if(Mage::getStoreConfig('megamenu/general/vmenutype') == 0){
                foreach ($cats as $cat){
                    $html .= $this->_renderVCategoryMenuItemHtml(
                    $cat,
                    0,
                    false,
                    false,
                    $item
                );
                    $item++;
                }
            }else{
                foreach ($cats as $cat){
                $html .= $this->drawMegamenuItem($cat,0,false,$item);
                $item++;
                }
            }
        }
        return $html;    
    }

    public function drawMegamenuExtra()
    {   
        $blockExtra = '';
        $store = Mage::app()->getStore();
        $collection = Mage::getModel('cms/block')->getCollection()
                    ->addStoreFilter($store, true)
                    ->addFieldToFilter('identifier', array('like'=>$this->megamenuExtra.'%'))
                    ->addFieldToFilter('is_active', 1);
        foreach($collection as $block){
            $blockExtra .= $this->drawMegamenuBlock($block->getIdentifier());
        }
        return $blockExtra;
    }

    public function drawMegamenuLink()
    {
        $html = '';
        $blockLink = $this->getLayout()->createBlock('cms/block')->setBlockId($this->megamenuLink)->toHtml();
        if ($blockLink){
            $html .= '<div id="megamenu_link" class="megamenu"><div class="level-top">' .$blockLink. '</div></div>';
        }
        return $html;
    }

    public function drawMegamenuLinks()
    {
        $blockLink = '';
        $store = Mage::app()->getStore();
        $collection = Mage::getModel('cms/block')->getCollection()
                    ->addStoreFilter($store, true)
                    ->addFieldToFilter('identifier', array('like'=>$this->megamenuLink.'%'))
                    ->addFieldToFilter('is_active', 1);
        foreach($collection as $block){
            $blockLink .= $this->drawMegamenuBlock($block->getIdentifier());
        }
        return $blockLink;
    }

    public function drawMegamenuItem($category, $level = 0, $last = false, $item)
    {
        $html = '';
        if (!$category->getIsActive()) return $html;
        $id = $category->getId();

        // --- Static Block ---
        $blockId = sprintf($this->catMega , $id); // --- static block key
        $blockHtml = Mage::app()->getLayout()->createBlock('cms/block')->setBlockId($blockId)->toHtml();
        /*check block right*/
        $blockIdRight = sprintf($this->catMegaRight, $id); // --- static block key

        $blockHtmlRight = Mage::app()->getLayout()->createBlock('cms/block')->setBlockId($blockIdRight)->toHtml();
        if($blockHtmlRight) $blockHtml = $blockHtmlRight;

        // --- Sub Categories ---
        $activeChildren = $this->getActiveChildren($category, $level);

        // --- class for active category ---
        $active = ($this->isCategoryActive($category)) ? ' act' : '';

        // --- Dropdown functions for show ---
        $dropDown = ($blockHtml || count($activeChildren));

        if ($dropDown) $html .= '<div id="megamenu_catid_' . $id . '" class="megamenu' . $active . ' nav-' .$item. '">';
        else $html .= '<div id="megamenu_catid_' . $id . '" class="megamenu' . $active . ' nav-' .$item. ' megamenu_no_child">';

        // --- Top Menu Item ---
        $name = $this->escapeHtml($category->getName());
        $name = str_replace('&nbsp;', '', $name);
        $html .= '<div class="level-top">';
        $html .= '<a href="'.$this->getCategoryUrl($category).'"><span>' . $name . '</span><i class="fa fa-angle-down"></i></a>';
        $label  = Mage::getModel("catalog/category")->load($category->getId())->getCatLabel();
        if($label) $html .= '<span class="'.$label.'">'.$this->__($label).'</span>'; 
        $html .= '</div>';       
        // --- Add Dropdown block (hidden) ---
        if ($dropDown){
            // --- Dropdown function for hide ---
            $html .= '<div id="dropdown' . $id . '" class="dropdown">';
                // --- draw Sub Categories ---
                if (count($activeChildren)){
                    $html .= '<div class="block1" id="block1' . $id . '">';
                        $html .= $this->drawColumns($activeChildren, $id);
                        if ($blockHtml && $blockHtmlRight){
                            $html .= '<div class="column blockright last">' .$blockHtml. '</div>';
                        }
                        $html .= '<div class="clearBoth"></div>';
                    $html .= '</div>';
                }
                // --- draw Custom User Block ---
                if ($blockHtml && !$blockHtmlRight){
                    $html .= '<div class="block2" id="block2' . $id . '">' .$blockHtml. '</div>';
                }
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function drawColumns($children, $id)
    {
        $html = '';
        // --- explode by columns ---

        $columns = (int)Mage::getStoreConfig('megamenu/general/count');
        if(Mage::getModel("catalog/category")->load($id)->getCatColumnCount())$columns = (int)Mage::getModel("catalog/category")->load($id)->getCatColumnCount();
        if ($columns < 1) $columns = 1; 
        $chunks = $this->explodeByColumns($children, $columns);
        $columChunk = count($chunks); 
        // --- draw columns ---
        $classSpecial = '';
        $keyLast = 0;
        foreach ($chunks as $key => $value){
            if(count($value)) $keyLast++;
        }
        $blockHtml = '';
        //$blockId = sprintf($this->catMega, $id); // --- static block key
        //$blockHtml = Mage::app()->getLayout()->createBlock('cms/block')->setBlockId($blockId)->toHtml();
        /*Check blog right*/
        //$blockIdRight = sprintf($this->catMegaRight, $id); // --- static block key
        $blockHtmlRight =  $this->helper('cms')->getBlockTemplateProcessor()->filter(Mage::getModel("catalog/category")->load($id)->getCatBlockRight());
        if($blockHtmlRight) $blockHtml = $blockHtmlRight;
        foreach ($chunks as $key => $value)
        {   Mage::getModel("catalog/category")->load($id)->getCatMenutype() == 1?$colWidth = ' col-md-4 col-sm-6 col-sms-6 col-smb-12':$colWidth = ' column' ;
            
            if (!count($value)) continue;
            if($key == $keyLast - 1){
                $classSpecial = ($blockHtmlRight && $blockHtml)? '':' last';
            }elseif($key == 0){
                $classSpecial = ' first';
            }else{
                $classSpecial = '';
            }
            $html .= '<div class="'. $classSpecial . ' col' . ($key+1) . $colWidth. '">' .$this->drawMenuItem($value, 1, $columChunk). '</div>';
        }
        return $html;
    }

    protected function getActiveChildren($parent, $level)
    {  
        $activeChildren = array();
        // --- check level ---
        $maxLevel = (int)Mage::getStoreConfig('megamenu/general/max_level');
        if ($maxLevel > 0)
        {
            if ($level >= ($maxLevel - 1)) return $activeChildren;
        }
        // --- / check level ---
        if (Mage::helper('catalog/category_flat')->isEnabled()){
            $children = $parent->getChildrenNodes();
            $childrenCount = count($children);
        }else {
            $children = $parent->getChildren();
            $childrenCount = $children->count();
        }
        $catsid = Mage::getStoreConfig('megamenu/general/catids');
        $arr_catsid = array();
        if($catsid && Mage::getStoreConfig('megamenu/general/showall')==1){    
            if(stristr($catsid, ',') === FALSE) $arr_catsid =  array(0 => $catsid);
            else $arr_catsid = explode(",", $catsid);
        }
        
        
        $hasChildren = $children && $childrenCount;
        if ($hasChildren){
            foreach ($children as $child){
                if(!in_array($child->getId(),$arr_catsid) ){
                if ($child->getIsActive()) array_push($activeChildren, $child);
                }
            }
        }
        return $activeChildren;
    }
      private static function _explodeArrayByColumnsHorisontal($list, $num)
    {
        if ($num <= 0) return array($list);
        $partition = array();
        $partition = array_pad($partition, $num, array());
        $i = 0;
        foreach ($list as $key => $value) {
            $partition[$i][$key] = $value;
            if (++$i == $num) $i = 0;
        }
        return $partition;
    }
    private function explodeByColumns($target, $num)
    {   
        $target = self::_explodeArrayByColumnsHorisontal($target, $num);
         
        if ((int)Mage::getStoreConfig('megamenu/general/integrate') && count($target)){
            // --- combine consistently numerically small column ---
            // --- 1. calc length of each column ---
            $max = 0; $columnsLength = array();
            foreach ($target as $key => $child){
                $count = 0;
                $this->_countChild($child, 1, $count);
                
                if ($max < $count) $max = $count;
                $columnsLength[$key] = $count;
            }
            
            // --- 2. merge small columns with next ---
            $xColumns = array(); $column = array(); $cnt = 0;
            $xColumnsLength = array(); $k = 0;
            
            foreach ($columnsLength as $key => $count){
                $cnt+= $count;
                if ($cnt > $max && count($column))
                {
                    $xColumns[$k] = $column;
                    $xColumnsLength[$k] = $cnt - $count;
                    $k++; $column = array(); $cnt = $count;
                }
                $column = array_merge($column, $target[$key]);
            }
            $xColumns[$k] = $column;
            $xColumnsLength[$k] = $cnt - $count;
            // --- 3. integrate columns of one element ---
            $target = $xColumns; $xColumns = array(); $nextKey = -1;
            if ($max > 1 && count($target) > 1){
                foreach($target as $key => $column){
                    if ($key == $nextKey) continue;
                    if ($xColumnsLength[$key] == 1){
                        // --- merge with next column ---
                        $nextKey = $key + 1;
                        if (isset($target[$nextKey]) && count($target[$nextKey]))
                        {
                            $xColumns[] = array_merge($column, $target[$nextKey]);
                            continue;
                        }
                    }
                    $xColumns[] = $column;
                }
                $target = $xColumns;
            }
        }
        return $target;
    }

    private function _countChild($children, $level, &$count)
    {
        foreach ($children as $child){
            if ($child->getIsActive()){
                $count++; $activeChildren = $this->getActiveChildren($child, $level);
                if (count($activeChildren) > 0) $this->_countChild($activeChildren, $level + 1, $count); 
            }
        }
    }

    public function drawMenuItem($children, $level = 1, $columChunk=null)
    {
        $html = '<div class="itemMenu level' . $level . '">';
        $keyCurrent = $this->getCurrentCategory()->getId();
        $countChildren = 0;
        $ClassNoChildren = '';
        foreach ($children as $child){
            $activeChildCat = $this->getActiveChildren($child, 0);
            if($activeChildCat) $countChildren++;
        }
        if($countChildren == 0){ 
            $ClassNoChildren = ' nochild'; 
        }
        

        foreach ($children as $child){
            if ($child->getIsActive()){
                $active = '';
                if ($this->isCategoryActive($child)){
                    $active = ' actParent';
                    if ($child->getId() == $keyCurrent) $active = ' act';
                }
                
                // --- format category name ---
                $name = $this->escapeHtml($child->getName());
                $name = str_replace(' ', '&nbsp;', $name);
               
                    $html .= '<a class="itemMenuName level' . $level . $active . $ClassNoChildren . '" href="' . $this->getCategoryUrl($child) . '"><span>' . $name . '</span></a>';
                
                $activeChildren = $this->getActiveChildren($child, $level);
                if (count($activeChildren) > 0){
                    $html .= '<div class="itemSubMenu level' . $level . '">' .$this->drawMenuItem($activeChildren, $level + 1). '</div>';
                }
            }
        }
        $html .= '</div>';
        return $html;
    }
    
    public function drawMegamenuBlock($blockId)
    {

        $html = '';
        $block = Mage::getModel('cms/block')->setStoreId(Mage::app()->getStore()->getId())->load($blockId);

        $blockHtml = Mage::app()->getLayout()->createBlock('cms/block')->setBlockId($blockId)->toHtml();
        $dropDown = $blockHtml;
        if ($dropDown) $html .= '<div id="'. $blockId . '" class="megamenu">';
        else $html .= '<div id="' . $blockId . '" class="megamenu">';

        $name = $block->getTitle();
        $name = str_replace(' ', '&nbsp;', $name);
        $html .= '<div class="level-top"><span class="block-title">' . $name . '<i class="fa fa-angle-down"></i></span></div>';

        // --- Add Dropdown block (hidden) ---
        if ($dropDown){
            $html .= '<div id="dropdown' . $blockId . '" class="dropdown cmsblock" style=" width: 904px;">';
            if ($blockHtml) $html .= '<div class="block2" id="block2' . $blockId . '">' .$blockHtml. '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }
     public function drawMegamenuItemTop($category, $level = 0, $last = false, $item)
    {    
        $html = '';
        if (!$category->getIsActive()) return $html;
        $id = $category->getId();
        $cat = Mage::getModel("catalog/category")->load($category->getId());
        $caticon = $cat->getCatIcon(); 
        $catType  = Mage::getModel("catalog/category")->load($category->getId())->getCatMenutype();
        $catbackground = $cat->getDropdownImage();
        $blockrightpos = Mage::getModel("catalog/category")->load($category->getId())->getCatBlockPos();
        
        if($catType == 0) return $this->_renderTopCategoryMenuItemHtml(
                $category,
                0,
                false,
                false,
                false, 
                $outermostItemClass = '', 
                $childrenWrapClass = '', 
                false,               
                $item
            );
        // --- Static Block ---$label  = Mage::getModel("catalog/category")->load($category->getId())->getCatLabel();
        //$blockId = sprintf($this->catMega , $id); // --- static block key
        $blockHtml =  $this->helper('cms')->getBlockTemplateProcessor()->filter(Mage::getModel("catalog/category")->load($id)->getCatBlockBottom());
       $blockHtmlTop =  $this->helper('cms')->getBlockTemplateProcessor()->filter(Mage::getModel("catalog/category")->load($id)->getCatBlockTop());
        $blockHtmlRight =  $this->helper('cms')->getBlockTemplateProcessor()->filter(Mage::getModel("catalog/category")->load($id)->getCatBlockRight());
        //if($blockHtmlRight) $blockHtml = $blockHtmlRight;

        // --- Sub Categories ---
        $activeChildren = $this->getActiveChildren($category, $level);

        // --- class for active category ---
        $active = ($this->isCategoryActive($category)) ? ' act' : '';

        // --- Dropdown functions for show ---
        $dropDown = ($blockHtml || count($activeChildren) || $blockHtmlRight);
        $catType == 1?$full = ' nav_product': $full='';
        if ($dropDown) $html .= '<div class="megamenu' . $active . ' nav-' .$item. $full.' ">';
        else $html .= '<div class="megamenu' . $active . ' nav-' .$item. ' megamenu_no_child">';

        // --- Top Menu Item ---
        $name = $this->escapeHtml($category->getName());
        $name = str_replace('&nbsp;', '', $name);
        $html .= '<div class="level-top">';
        $caticon?$html .= '<div class="icon-menu"><img class="level-top-icon" alt="" src='.Mage::getBaseUrl('media').'catalog/category/'.$caticon.'></div>':'';
        //$html .= '<a href="'.$this->getCategoryUrl($category).'"><span>' . $name . '</span><i class="fa fa-angle-down"></i></a>';
        $label  = Mage::getModel("catalog/category")->load($category->getId())->getCatLabel();
        if($label) $html .= '<span class="'.$label.'">'.$this->__($label).'</span>'; 
        $html .= '</div>';       
        // --- Add Dropdown block (hidden) ---
        if($catType == 1){
            if ($dropDown){
            
            $columns = (int)(Mage::getModel("catalog/category")->load($id)->getCatColumnCount()?Mage::getModel("catalog/category")->load($id)->getCatColumnCount():Mage::getStoreConfig('megamenu/general/count'));
            $columnWidth = 2*(6 - $columns);
            if($columnWidth <= 0)$columnWidth = 12;
            // --- Dropdown function for hide ---
            $html .= '<div id="dropdown' . $id . '" class="dropdown" style="background-image: url('.Mage::getBaseUrl('media').'catalog/category/'.$catbackground.')">';
                $html .= '<a class="title" href="'.$this->getCategoryUrl($category).'"><span>' . $name . '</span></a>';
                $html .= '<div class="top-cat-desc">'.$cat->getDescription().'</div>';
                if ($blockHtmlTop){
                    $html .= '<div class="block3" id="block3' . $id . '">' .$blockHtmlTop. '</div>';
                }
                // --- draw Sub Categories ---
              if (count($activeChildren)){
                        if ($blockHtmlRight){
                            if($blockrightpos == 1)$html .= '<div class="blockleft first col1 col-md-'.$columnWidth.' col-sm-6 col-sms-6 col-smb-12">' .$blockHtmlRight. '</div>';
                        }
                        $html .= '<div class="row">';
                        $html .= $this->drawColumns($activeChildren, $id);
                        $html .= '</div>';
                        if ($blockHtmlRight){
                            if($blockrightpos == 0 || $blockrightpos == '')$html .= '<div class="blockright last col1 col-md-'.$columnWidth.' col-sm-6 col-sms-6 col-smb-12">' .$blockHtmlRight. '</div>';
                        }
                        $html .= '<div class="clearBoth"></div>';
                    
                }
                // --- draw Custom User Block ---
                if ($blockHtml){
                    $html .= '<div class="block2" id="block2' . $id . '">' .$blockHtml. '</div>';
                }
            $html .= '</div>';    
            }else{
                $html .= '<div id="dropdown' . $id . '" class="dropdown">';
                $html .= '<a href="'.$this->getCategoryUrl($category).'"><span>' . $name . '</span><i class="fa fa-angle-down"></i></a>';
                $html .= '</div>'; 
            }
        } 
        if($catType == 2){
        if ($dropDown){
            
            // --- Dropdown function for hide ---
            $html .= '<div id="dropdown' . $id . '" class="dropdown" style="background-image: url('.Mage::getBaseUrl('media').'catalog/category/'.$catbackground.')">';
            $html .= '<a class="title" href="'.$this->getCategoryUrl($category).'"><span>' . $name . '</span><i class="fa fa-angle-down"></i></a>';
            $html .= '<div class="top-cat-desc">'.$cat->getDescription().'</div>';
                if ($blockHtmlTop){
                    $html .= '<div class="block3" id="block3' . $id . '">' .$blockHtmlTop. '</div>';
                }
                // --- draw Sub Categories ---
              if (count($activeChildren)){
                $html .= '<div class="block1" id="block1' . $id . '">';
                        if ($blockHtmlRight){
                            if($blockrightpos == 1)$html .= '<div class="column blockleft first">' .$blockHtmlRight. '</div>';
                        }
                    
                        $html .= $this->drawColumns($activeChildren, $id);
                        if ($blockHtmlRight){
                            if($blockrightpos == 0 || $blockrightpos == '')$html .= '<div class="column blockright last">' .$blockHtmlRight. '</div>';    
                        }
                        $html .= '<div class="clearBoth"></div>';
                    $html .= '</div>';
                }
                // --- draw Custom User Block ---
                if ($blockHtml){
                    $html .= '<div class="block2" id="block2' . $id . '">' .$blockHtml. '</div>';
                }
            $html .= '</div>';
            }else{
                $html .= '<div id="dropdown' . $id . '" class="dropdown">';
                $html .= '<a href="'.$this->getCategoryUrl($category).'"><span>' . $name . '</span><i class="fa fa-angle-down"></i></a>';
                $html .= '</div>'; 
            }
        }
        $html .= '</div>';
        return $html;
    }
        public function drawMegamenuMainTop()
    {
        $html ='';
        $cats = Mage::helper('catalog/category')->getStoreCategories();
        if(count($cats)){
            $item = 1;
            foreach ($cats as $cat){ 
                $addtoTop  = Mage::getModel("catalog/category")->load($cat->getId())->getEnableCatTop();
                if($addtoTop == 1){
                $html .= $this->drawMegamenuItemTop($cat,0,false,$item);
                $item++;
                }
            }
        }
        return $html;    
    }
    protected function _renderTopCategoryMenuItemHtml($category, $level = 0, $isLast = false, $isFirst = false,
        $isOutermost = false, $outermostItemClass = '', $childrenWrapClass = '', $noEventAttributes = false, $item)
    {
        if (!$category->getIsActive()) {
            return '';
        }
        $html = array();
        $caticon = Mage::getModel("catalog/category")->load($category->getId())->getCatIcon(); 
        // get all children
        // If Flat Data enabled then use it but only on frontend
        $flatHelper = Mage::helper('catalog/category_flat');
        if ($flatHelper->isAvailable() && $flatHelper->isBuilt(true) && !Mage::app()->getStore()->isAdmin()) {
            $children = (array)$category->getChildrenNodes();
            $childrenCount = count($children);
        } else {
            $children = $category->getChildren();
            $childrenCount = $children->count();
        }
        $hasChildren = ($children && $childrenCount);

        // select active children
        $activeChildren = array();
        foreach ($children as $child) {
            if ($child->getIsActive()) {
                $activeChildren[] = $child;
            }
        }
        $activeChildrenCount = count($activeChildren);
        $hasActiveChildren = ($activeChildrenCount > 0);

        // prepare list item html classes
        $classes = array();

        if($level==0)$classes[] = 'megamenu';
        $classes[] = 'level' . $level;
        if ($level==0) {
        $classes[] = 'nav-' . $item;
        }else{
        $classes[] = 'nav-' . $this->_getItemPosition($level);
        }
        if ($this->isCategoryActive($category)) {
            $classes[] = 'act';
        }
        $linkClass = '';
        if ($isOutermost && $outermostItemClass) {
            $classes[] = $outermostItemClass;
            $linkClass = ' class="'.$outermostItemClass.'"';
        }
        if ($isFirst) {
            $classes[] = 'first';
        }
        if ($isLast) {
            $classes[] = 'last';
        }
        if ($hasActiveChildren) {
            $classes[] = 'parent';
        }else{
            $classes[] = 'megamenu_no_child';
        }

        // prepare list item attributes
        $attributes = array();
        if (count($classes) > 0) {
            $attributes['class'] = implode(' ', $classes);
        }
       /* if ($hasActiveChildren && !$noEventAttributes) {
            $attributes['onmouseover'] = 'Element.addClassName(this, \'menu-active\')';
            $attributes['onmouseout'] = 'Element.removeClassName(this, \'menu-active\')';
        }*/

        // assemble list item with attributes
        $level==0?$htmlLi = '<div' : $htmlLi = '<li';
        
        foreach ($attributes as $attrName => $attrValue) {
            $htmlLi .= ' ' . $attrName . '="' . str_replace('"', '\"', $attrValue) . '"';
        }
        $htmlLi .= '>';
        $html[] = $htmlLi;
        $level==0?$html[] .= '<div class="level-top">':'';
        $level==0 && $caticon?$html[] .= '<div class="icon-menu"><img class="level-top-icon" alt="" src='.Mage::getBaseUrl('media').'catalog/category/'.$caticon.'></div>':'';
        $level!=0?$html[] = '<a href="'.$this->getCategoryUrl($category).'"'.$linkClass.'>':'';
        $level!=0?$html[] = '<span>' . $this->escapeHtml($category->getName()) . '</span>':'';
        //$level==0 && $hasActiveChildren?$html[] .= '<i class="fa fa-angle-down"></i>':'';
        $level!=0?$html[] = '</a>':'';
        $level==0?$html[] .= '</div>':'';
        // render children
        $htmlChildren = '';
        $j = 0;
        foreach ($activeChildren as $child) {
            $htmlChildren .= $this->_renderTopCategoryMenuItemHtml(
                $child,
                ($level + 1),
                ($j == $activeChildrenCount - 1),
                ($j == 0),
                false,
                $outermostItemClass,
                $childrenWrapClass,
                $noEventAttributes
            );
            $j++;
        }
        if (!empty($htmlChildren)) {
            if ($childrenWrapClass) {
                $html[] = '<div class="' . $childrenWrapClass . '">';
            }
            $html[] = '<ul class="level' . $level . '">';
            $level==0?$html[] = '<li><a class="title" href="'.$this->getCategoryUrl($category).'"'.$linkClass.'>':'';
            $level==0?$html[] = '<span>' . $this->escapeHtml($category->getName()) . '</span>':'';
            //$level==0 && $hasActiveChildren?$html[] .= '<i class="fa fa-angle-down"></i>':'';
            $level==0?$html[] = '</a></li>':'';
            $html[] = $htmlChildren;
            $html[] = '</ul>';
            if ($childrenWrapClass) {
                $html[] = '</div>';
            }
        }else{
                $html[] = '<div id="dropdown' . $category->getId() . '" class="dropdown">';
                $html[] = '<a href="'.$this->getCategoryUrl($category).'"><span>' . $this->escapeHtml($category->getName()) . '</span><i class="fa fa-angle-down"></i></a>';
                $html[] = '</div>';
            }

        $level==0?$html[] = '</div>' : $html[] = '</li>';

        $html = implode("\n", $html);
        return $html;
    }
    public function drawMegamenuAll()
    {
        $html ='';
        $id = Mage::app()->getStore()->getRootCategoryId();
        $rootCat = Mage::getModel('catalog/category')->load($id);
        $allChild = Mage::getModel('catalog/category')->getResource()->getAllChildren($rootCat);
        $html .= '<div id="megamenu_all" class="megamenu nav_product">';
                $html .= '<div class="level-top">';
                $html .= '<a href=""><span>' .$this->__('All'). '</span><i class="fa fa-angle-down"></i></a>';
                $html .= '</div>';
            
        
        $html .= '<div id="dropdown' . $id . '" class="dropdown">';
        $activeChildren = array();
         foreach ($allChild as $child){
                
            }
        $cats = Mage::helper('catalog/category')->getStoreCategories(); 
        if(count($cats)){
        
            foreach ($cats as $cat){
                 array_push($activeChildren, $cat);
            }
        }
        if(count($activeChildren)){ 
           $html .= $this->drawColumns($activeChildren, $id);
        } 
         $html .= '</div>'; 
         $html .= '</div>';
        return $html;    
    }
     protected function _renderVCategoryMenuItemHtml($category, $level = 0, $isLast = false, $isFirst = false,
        $isOutermost = false, $outermostItemClass = '', $childrenWrapClass = '', $noEventAttributes = false)
    {
        if (!$category->getIsActive()) {
            return '';
        }
        $caticon = Mage::getModel("catalog/category")->load($category->getId())->getCatIcon(); 
        $html = array();

        // get all children
        // If Flat Data enabled then use it but only on frontend
        $flatHelper = Mage::helper('catalog/category_flat');
        if ($flatHelper->isAvailable() && $flatHelper->isBuilt(true) && !Mage::app()->getStore()->isAdmin()) {
            $children = (array)$category->getChildrenNodes();
            $childrenCount = count($children);
        } else {
            $children = $category->getChildren();
            $childrenCount = $children->count();
        }
        $hasChildren = ($children && $childrenCount);

        // select active children
        $activeChildren = array();
        foreach ($children as $child) {
            if ($child->getIsActive()) {
                $activeChildren[] = $child;
            }
        }
        $activeChildrenCount = count($activeChildren);
        $hasActiveChildren = ($activeChildrenCount > 0);

        // prepare list item html classes
        $classes = array();
        $classes[] = 'level' . $level;
        $classes[] = 'nav-' . $this->_getItemPosition($level);
        if ($this->isCategoryActive($category)) {
            $classes[] = 'active';
        }
        $linkClass = '';
        if ($isOutermost && $outermostItemClass) {
            $classes[] = $outermostItemClass;
            $linkClass = ' class="'.$outermostItemClass.'"';
        }
        if ($isFirst) {
            $classes[] = 'first';
        }
        if ($isLast) {
            $classes[] = 'last';
        }
        if ($hasActiveChildren) {
            $classes[] = 'parent';
        }

        // prepare list item attributes
        $attributes = array();
        if (count($classes) > 0) {
            $attributes['class'] = implode(' ', $classes);
        }
       /* if ($hasActiveChildren && !$noEventAttributes) {
             $attributes['onmouseover'] = 'toggleMenu(this,1)';
             $attributes['onmouseout'] = 'toggleMenu(this,0)';
        }*/

        // assemble list item with attributes
        $htmlLi = '<li';
        foreach ($attributes as $attrName => $attrValue) {
            $htmlLi .= ' ' . $attrName . '="' . str_replace('"', '\"', $attrValue) . '"';
        }
        $htmlLi .= '>';
        $html[] = $htmlLi;
        $html[] = '<a href="'.$this->getCategoryUrl($category).'"'.$linkClass.'>';
        $html[] = $this->escapeHtml($category->getName()) ;
        $html[] = '</a>';
        $html[] = '<span></span>';

        // render children
        $htmlChildren = '';
        $j = 0;
        foreach ($activeChildren as $child) {
            $htmlChildren .= $this->_renderVCategoryMenuItemHtml(
                $child,
                ($level + 1),
                ($j == $activeChildrenCount - 1),
                ($j == 0),
                false,
                $outermostItemClass,
                $childrenWrapClass,
                $noEventAttributes
            );
            $j++;
        }
        if (!empty($htmlChildren)) {
            if ($childrenWrapClass) {
                $html[] = '<div class="' . $childrenWrapClass . '">';
            }
            $html[] = '<ul class="level' . $level . '">';
            $html[] = $htmlChildren;
            $html[] = '</ul>';
            if ($childrenWrapClass) {
                $html[] = '</div>';
            }
        }

        $html[] = '</li>';

        $html = implode("\n", $html);
        return $html;
    }
} 

