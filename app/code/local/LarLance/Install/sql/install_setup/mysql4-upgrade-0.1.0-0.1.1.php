<?php
$installer = $this;
$installer->startSetup();

$staticBlock = array(
    'title' => 'Hamburger menu',
    'identifier' => 'hamburger_menu',
    'content' => '777 Lorem ipsum 77',
    'is_active' => 1,
    'stores' => array(0, 1)
);

$updateBlock = true;
$installer->addCmsBlock($staticBlock, $updateBlock);


$cmsPageData = array(
    'title'           => 'My Static Page',
    'content_heading' => 'My Static Page',
    'root_template'   => 'one_column',
    'identifier'      => 'hamburger_menu',
    'content'         => '777 Lorem ipsum 777',
    'is_active'       => 1,
    'stores'          => array(0, 1),
    'sort_order'      => 0
);
$updatePage = true;
$installer->addCmsPage($cmsPageData, $updatePage);


$installer->endSetup();
	 