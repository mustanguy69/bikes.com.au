<?php
/** @var Fooman_Connect_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

$installer->addAttribute('catalog_product', 'xero_item_code', array(
    'group' => 'General',
    'input' => 'text',
    'visible_on_front' => 0,
    'label' => 'Xero Item Code',
    'comment'=> 'Leave empty to use SKU',
    'required' => 0
    )
);
$installer->endSetup();
