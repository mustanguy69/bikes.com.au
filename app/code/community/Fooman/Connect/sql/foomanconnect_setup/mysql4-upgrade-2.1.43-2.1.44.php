<?php

/** @var Fooman_Connect_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('foomanconnect/order'),
    'payment_export_status',
    Varien_Db_Ddl_Table::TYPE_SMALLINT . " COMMENT 'Payment Export Status'"
);

$installer->getConnection()->addColumn(
    $installer->getTable('foomanconnect/invoice'),
    'payment_export_status',
    Varien_Db_Ddl_Table::TYPE_SMALLINT . " COMMENT 'Payment Export Status'"
);

$installer->getConnection()->addColumn(
    $installer->getTable('foomanconnect/creditmemo'),
    'payment_export_status',
    Varien_Db_Ddl_Table::TYPE_SMALLINT . " COMMENT 'Payment Export Status'"
);

$installer->endSetup();
