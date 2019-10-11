<?php
$installer = $this;
$installer->startSetup();
$updateBlock = true;

$content ='<div class="banner-partners container">
                <div class="col-sm-4 banner-item">
                    <img src="/skin/frontend/tv_bikeworld_package/tv_bikeworld2/images/banner1.jpg" alt="image description"/>
                </div>
                <div class="col-sm-4 banner-item">
                    <img src="/skin/frontend/tv_bikeworld_package/tv_bikeworld2/images/banner2.jpg" alt="image description"/>
                </div>
                <div class="col-sm-4 banner-item">
                    <img src="/skin/frontend/tv_bikeworld_package/tv_bikeworld2/images/banner3.jpg" alt="image description"/>
                </div>
            </div>';

$staticBlock = array(
    'title' => 'Banner Partners',
    'identifier' => 'banner_partners',
    'content' => $content,
    'is_active' => 1,
    'stores' => array(0, 1)
);
$installer->addCmsBlock($staticBlock, $updateBlock);

$content_2 ='<div class="category-banners row">
                <div class="col-sm-4 banner-item">
                    <a href="#"><img src="/skin/frontend/tv_bikeworld_package/tv_bikeworld2/images/banner4.jpg" alt="image description"/></a>
                </div>
                <div class="col-sm-4 banner-item">
                    <a href="#"><img src="/skin/frontend/tv_bikeworld_package/tv_bikeworld2/images/banner5.jpg" alt="image description"/></a>
                </div>
                <div class="col-sm-4 banner-item">
                    <a href="#"><img src="/skin/frontend/tv_bikeworld_package/tv_bikeworld2/images/banner6.jpg" alt="image description"/></a>
                </div>
            </div>';

$staticBlock_2 = array(
    'title' => 'Banner Category',
    'identifier' => 'category_banners',
    'content' => $content_2,
    'is_active' => 1,
    'stores' => array(0, 1)
);
$installer->addCmsBlock($staticBlock_2, $updateBlock);

$installer->endSetup();
