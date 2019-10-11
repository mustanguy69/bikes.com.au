<?php

$installer = $this;

$installer->startSetup();


$installer->addAttribute('catalog_category', 'dropdown_image', array(
    'group'             => 'Themevast',
    'label'             => 'Top Level dropdown background image',
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
    'sort_order' => 110,
));


$installer->endSetup(); 
?>