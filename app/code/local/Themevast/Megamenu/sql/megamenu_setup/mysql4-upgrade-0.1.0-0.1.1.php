<?php

$installer = $this;

$installer->startSetup();

$this->addAttribute('catalog_category', 'enable_cat_top', array(
    'group'             => 'Themevast',
    'label'             => 'Top Level Inclue to top navigation',
    'type'              => 'varchar',
    'backend'           => '',
    'frontend_input'    => '',
    'frontend'          => '',
    'input'             => 'select', //text, textarea, select, file, image, multilselect
    'default' => array(0),
    'class'             => '',
    'source'            => 'eav/entity_attribute_source_boolean',//this is necessary for select and multilelect, for the rest leave it blank
    'global'             => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,//scope can be SCOPE_STORE or SCOPE_GLOBAL or SCOPE_WEBSITE
    'visible'           => true,
    'frontend_class'    => '',
    'required'          => false,//or true
    'user_defined'      => true,
    'default'           => '',
    'sort_order'        => 0,//any number will do
));

$installer->addAttribute('catalog_category', 'cat_menutype', array(
    'group'             => 'Themevast',
    'label'             => 'Top Level Dropdown Menu Type',
    'note'              => "",
    'type'              => 'varchar',
    'input'             => 'select',
    'source'            => 'megamenu/config_source_category_menutype',
    'visible'           => true,
    'required'          => false,
    'backend'           => '',
    'frontend'          => '',
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'user_defined'      => true,
    'visible_on_front'  => true,
    'wysiwyg_enabled'   => false,
    'is_html_allowed_on_front'  => false,
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'sort_order' => 10,
));

$installer->addAttribute('catalog_category', 'cat_block_right', array(
    'group'             => 'Themevast',
    'label'             => 'Right Content',
    'note'              => "<strong style='color:red'>Top-level categories only.</strong><br />This content will be shown right after submenu.",
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
    'sort_order' => 30,
));

$installer->endSetup(); 
?>