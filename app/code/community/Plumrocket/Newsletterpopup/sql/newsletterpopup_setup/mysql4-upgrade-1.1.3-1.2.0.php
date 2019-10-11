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
 * @copyright   Copyright (c) 2017 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */


$installer = $this;
$installer->startSetup();

$installer->run("
    DELETE FROM `{$this->getTable('core_config_data')}` WHERE `path` = 'newsletterpopup/general/email_template';
");

$installer->run("
    ALTER TABLE  `{$this->getTable('newsletterpopup_popups')}`
        ADD  `email_template` VARCHAR(64) NOT NULL DEFAULT 'newsletterpopup_general_email_template',
        DROP `text_note`,
        ADD `animation` VARCHAR( 32 ) NOT NULL,
        ADD `signup_method` SET('signup_only','register_signup') NOT NULL DEFAULT 'signup_only',
        ADD `subscription_mode` SET('all','all_selected','one_radio','one_select','multiple') NOT NULL DEFAULT 'all';
");

$installer->run("
    UPDATE `{$this->getTable('newsletterpopup_popups')}` SET `animation` = 'fadeInDownBig';
");


$installer->run("
    CREATE TABLE IF NOT EXISTS `{$this->getTable('newsletterpopup_mailchimp_list')}` (
        `entity_id` int(11) NOT NULL AUTO_INCREMENT,
        `popup_id` int(11) NOT NULL,
        `name` varchar(16) NOT NULL,
        `label` varchar(255) NOT NULL,
        `enable` int(1) NOT NULL DEFAULT '0',
        `sort_order` INT NOT NULL,
        PRIMARY KEY (`entity_id`),
        KEY `popup_id` (`popup_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
");

$installer->run("
    CREATE TABLE IF NOT EXISTS `{$this->getTable('newsletterpopup_form_fields')}` (
        `entity_id` int(11) NOT NULL AUTO_INCREMENT,
        `popup_id` int(11) NOT NULL,
        `name` varchar(32) NOT NULL,
        `label` varchar(64) NOT NULL,
        `enable` int(1) NOT NULL,
        `sort_order` int(11) NOT NULL,
        PRIMARY KEY (`entity_id`),
        KEY `popup_id` (`popup_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
");

$installer->run("
INSERT INTO `{$this->getTable('newsletterpopup_form_fields')}` (`name`, `enable`, `label`, `sort_order`, `popup_id`) VALUES
    ('email', 1, 'Email', 10, 0),
    ('confirm_email', 0, 'Confirm Email', 20, 0),
    ('firstname', 0, 'First Name', 30, 0),
    ('middlename', 0, 'Middle Name', 40, 0),
    ('lastname', 0, 'Last Name', 50, 0),
    ('suffix', 0, 'Suffix', 60, 0),
    ('dob', 0, 'Date of Birth', 70, 0),
    ('gender', 0, 'Gender', 80, 0),
    ('taxvat', 0, 'Tax/VAT Number', 90, 0),
    ('password', 0, 'Password', 100, 0),
    ('confirm_password', 0, 'Confirm Password', 110, 0);
");

$installer->run("
    CREATE TABLE IF NOT EXISTS `{$this->getTable('newsletterpopup_hold')}` (
        `email` varchar(255) NOT NULL,
        `popup_id` int(11) NOT NULL,
        `lists` varchar(1024) NOT NULL,
        PRIMARY KEY (`email`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
");

try {
    $installer->run("
        ALTER TABLE  `{$this->getTable('newsletter_subscriber')}`
            ADD `subscriber_firstname` varchar( 150 ) AFTER `subscriber_email`;
    ");
} catch (Exception $e) {}

try {
    $installer->run("
        ALTER TABLE  `{$this->getTable('newsletter_subscriber')}`
            ADD `subscriber_middlename` varchar( 150 ) AFTER `subscriber_firstname`;
    ");
} catch (Exception $e) {}

try {
    $installer->run("
        ALTER TABLE  `{$this->getTable('newsletter_subscriber')}`
            ADD `subscriber_lastname` varchar( 150 ) AFTER `subscriber_middlename`;
    ");
} catch (Exception $e) {}

try {
    $installer->run("
        ALTER TABLE  `{$this->getTable('newsletter_subscriber')}`
            ADD `subscriber_suffix` varchar(32) AFTER `subscriber_lastname`;
    ");
} catch (Exception $e) {}

try {
    $installer->run("
        ALTER TABLE  `{$this->getTable('newsletter_subscriber')}`
            ADD `subscriber_dob` datetime AFTER `subscriber_suffix`;
    ");
} catch (Exception $e) {}

try {
    $installer->run("
        ALTER TABLE  `{$this->getTable('newsletter_subscriber')}`
            ADD `subscriber_gender` int(11) AFTER `subscriber_dob`;
    ");
} catch (Exception $e) {}

try {
    $installer->run("
        ALTER TABLE  `{$this->getTable('newsletter_subscriber')}`
            ADD `subscriber_taxvat` varchar(128) AFTER `subscriber_gender`;
    ");
} catch (Exception $e) {}

$installer->endSetup();
