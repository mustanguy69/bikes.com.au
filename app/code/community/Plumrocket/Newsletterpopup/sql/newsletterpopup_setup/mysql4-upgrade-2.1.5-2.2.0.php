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


$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$conn = $installer->getConnection();
$table = $installer->getTable('newsletterpopup_popups');

$conn->addColumn(
    $table,
    'coupon_expiration_time',
    'VARCHAR(32) NULL AFTER `end_date`'
);
$conn->modifyColumn(
    $table,
    'success_page',
    'VARCHAR(255)'
);
$conn->modifyColumn(
    $table,
    'cookie_time_frame',
    'VARCHAR(32) NULL'
);

$table = $installer->getTable('salesrule/coupon');

$conn->addColumn(
    $table,
    'np_expiration_date',
    'DATETIME NULL AFTER `expiration_date`'
);

$conn->update(
    $installer->getTable('newsletterpopup_templates'),
    array(
        'code' => new Zend_Db_Expr('REPLACE(`code`, \'pjQuery_1_9\', \'pjQuery_1_10_2\')'),
        'default_values' => new Zend_Db_Expr('REPLACE(`default_values`, \'Thank you for your subscription.\', \'Thank you for your subscription!\')')
    )
);

/**
 * Update Template Fireworks
 */
$templateModel = Mage::getModel('newsletterpopup/template')->load(12);

if ($templateModel && $templateModel->getId()) {
    $defaultValues = unserialize($templateModel->getData('default_values'));
    $defaultValues['text_success'] = '<p><strong style="font-size: 28px; line-height: 33px;">Enjoy 15% OFF Your Entire Purchase.</strong></p>'
        . '<p style="padding-top: 15px;">Enter Coupon Code:&nbsp;<strong style="color: #ca0b0b; background: #faffad; padding: 5px 7px; border-radius: 3px; border: 1px dashed #d4da65;">{{coupon_code}}</strong><br/>At Checkout</p>'
        . '<p style="padding-top: 24px; color: #d00000;">Hurry! This Offer Ends in 2 HOURS!</p>';

    $templateModel->setData('default_values', serialize($defaultValues))
        ->setData('skip_base_template_validation', true)
        ->save();
}

/**
 * Clear DDL cache
 */
try {
    $cache = Mage::app()->getCache();

    if ($cache != null) {
        $cache->clean('matchingTag', array(Varien_Db_Adapter_Pdo_Mysql::DDL_CACHE_TAG));
    }
} catch (Exception $e) {
    Mage::logException($e);
}

$installer->endSetup();
