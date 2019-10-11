<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * @package     Plumrocket_Newsletterpopup
 * @copyright   Copyright (c) 2018 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();
$connection = $installer->getConnection();

// Change column type
$connection->modifyColumn(
    $installer->getTable('newsletterpopup_form_fields'),
    'label',
    'TEXT'
);

// Add agreement field
$data = array(
    'name' => 'agreement',
    'label' => 'I have read and agree to the <a href="#" target="_blank">Terms of Service</a>',
    'enable' => 0,
    'sort_order' => 210,
    'popup_id' => 0,
);

Mage::getModel('newsletterpopup/formField')->setData($data)->save();

// Update jQuery version
$connection->update(
    $installer->getTable('newsletterpopup_templates'),
    array(
        'code' => new Zend_Db_Expr('REPLACE(`code`, \'pjQuery_1_10_2\', \'pjQuery_1_12_4\')'),
    )
);

$installer->endSetup();
