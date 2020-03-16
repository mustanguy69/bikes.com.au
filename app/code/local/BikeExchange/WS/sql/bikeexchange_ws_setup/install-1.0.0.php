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

$attrData = array(
    'bikeexchange_status'=> 'false',
    'bikeexchange_id'=> '',
);
$storeId = 0;
$productIds = Mage::getModel('catalog/product')->getCollection()->getAllIds();
Mage::getModel("catalog/product_action")->updateAttributes(
    $productIds,
    $attrData,
    $storeId
);

$this->startSetup();
$table = new Varien_Db_Ddl_Table();
$table->setName($this->getTable('bikeexchange_ws/taxons'));
$table->addColumn(
    'entity_id',
    Varien_Db_Ddl_Table::TYPE_INTEGER,
    10,
    array(
        'auto_increment' => true,
        'unsigned' => true,
        'nullable'=> false,
        'primary' => true
    )
);
$table->addColumn(
    'category_id',
    Varien_Db_Ddl_Table::TYPE_INTEGER,
    100,
    array(
        'nullable' => false,
    )
);
$table->addColumn(
    'category_name',
    Varien_Db_Ddl_Table::TYPE_VARCHAR,
    255,
    array(
        'nullable' => false,
    )
);
$table->addColumn(
    'taxon_name',
    Varien_Db_Ddl_Table::TYPE_VARCHAR,
    255,
    array(
        'nullable' => false,
    )
);
$table->addColumn(
    'taxon_slug',
    Varien_Db_Ddl_Table::TYPE_VARCHAR,
    255,
    array(
        'nullable' => false,
    )
);

$table->setOption('type', 'InnoDB');
$table->setOption('charset', 'utf8');
$this->getConnection()->createTable($table);

$this->endSetup();