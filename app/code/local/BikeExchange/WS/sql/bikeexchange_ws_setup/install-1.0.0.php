<?php

$installer = $this;
$installer->addAttribute('catalog_product', 'bikeexchange_status', array(
    'group'             => 'BikeExchange',
    'type'              => 'int',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'Send to BikeExchange ?',
    'input'             => 'select',
    'class'             => '',
    'source'            => 'eav/entity_attribute_source_boolean',
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'           => true,
    'required'          => true,
    'user_defined'      => true,
    'default'           => 'false',
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'visible_on_front'  => false,
    'unique'            => false,
    'apply_to'          => 'simple,configurable',
    'is_configurable'   => false
));

$installer->addAttribute('catalog_product', 'bikeexchange_id', array(
    'group'             => 'BikeExchange',
    'type'              => 'text',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'BikeExchange ID',
    'input'             => 'text',
    'class'             => 'bike-exchange-id-input',
    'source'            => '',
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'           => true,
    'required'          => false,
    'user_defined'      => true,
    'default'           => '',
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'visible_on_front'  => false,
    'unique'            => false,
    'apply_to'          => 'simple,configurable',
    'is_configurable'   => false
));