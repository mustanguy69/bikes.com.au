<?php

$installer = $this;

$installer->startSetup();

$this->addAttribute('catalog_category', 'cat_column_count', array(
    'group'             => 'Themevast',
    'label'             => 'Column count',
    'type'              => 'varchar',
    'note'              => "e.g. 1, 2, 3. The maximum number of columns in the popup.",
    'backend'           => '',
    'frontend_input'    => '',
    'frontend'          => '',
    'input'             => 'text', //text, textarea, select, file, image, multilselect
    'default' => array(0),
    'class'             => '',
    'global'             => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,//scope can be SCOPE_STORE or SCOPE_GLOBAL or SCOPE_WEBSITE
    'visible'           => true,
    'frontend_class'    => '',
    'required'          => false,//or true
    'user_defined'      => true,
    'default'           => '',
    'sort_order'        => 40,//any number will do
));

$installer->addAttribute('catalog_category', 'cat_icon', array(
    'group'             => 'Themevast',
    'label'             => 'Top Level Icon image',
    'note'              => "",
    'type'              => 'varchar',
    'input'             => 'image',
    'visible'           => true,
    'required'          => false,
    'backend'           => 'catalog/category_attribute_backend_image',
    'frontend'          => '',
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'user_defined'      => true,
    'visible_on_front'  => true,
    'wysiwyg_enabled'   => false,
    //'is_html_allowed_on_front'  => false,
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'sort_order' => 100,
));

$installer->addAttribute('catalog_category', 'cat_block_bottom', array(
    'group'             => 'Themevast',
    'label'             => 'Bottom Content',
    'note'              => "<strong style='color:red'>Top-level categories only.</strong><br />This content will be shown bottom after submenu.",
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
    'sort_order'        => 50,//any number will do
));

$installer->addAttribute('catalog_category', 'cat_block_pos', array(
    'group'             => 'Themevast',
    'label'             => 'Left Content',
    'note'              => "Change position of right block to left.",
    'type'              => 'text',
    'input'             => 'select',
    'visible'           => true,
    'required'          => false,
    'source'            => 'eav/entity_attribute_source_boolean',
    'backend'           => '',
    'frontend'          => '',
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'user_defined'      => true,
    'visible_on_front'  => true,
    'wysiwyg_enabled'   => false,
    'is_html_allowed_on_front'  => true,
    'default'           => '',
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'sort_order'        => 35,//any number will do
));

$installer->endSetup(); 
?>