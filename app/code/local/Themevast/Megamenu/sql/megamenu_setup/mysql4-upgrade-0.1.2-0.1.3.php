<?php

$installer = $this;

$installer->startSetup();


$installer->addAttribute('catalog_category', 'cat_block_top', array(
    'group'             => 'Themevast',
    'label'             => 'Top Content',
    'note'              => "<strong style='color:red'>Top-level categories only.</strong><br />This content will be shown at top of the submenu.",
    'type'              => 'text',
    'input'             => 'textarea',
    'visible'           => true,
    'required'          => false,
    'backend'           => '',
    'frontend'          => '',
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'user_defined'      => true,
    'visible_on_front'  => true,
    'wysiwyg_enabled'   => true,
    'is_html_allowed_on_front'  => true,
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'sort_order'        => 49,//any number will do
));


$installer->endSetup(); 
?>