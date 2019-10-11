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
CREATE TABLE IF NOT EXISTS `{$this->getTable('newsletterpopup_popups')}` (
  `entity_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0',
  `coupon_code` int(11) NOT NULL DEFAULT '0',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `success_page` varchar(32) NOT NULL DEFAULT '__stay__',
  `custom_success_page` varchar(128) NOT NULL,
  `send_email` int(1) NOT NULL DEFAULT '0',
  `template_name` varchar(64) NOT NULL,
  `display_popup` enum('after_time_delay','leave_page','close_page','manually') NOT NULL DEFAULT 'after_time_delay',
  `delay_time` int(11) NOT NULL DEFAULT '5',
  `cookie_time_frame` int(11) NOT NULL DEFAULT '7',
  `store_id` varchar(32) NOT NULL DEFAULT '0',
  `show_on` set('all','home','category','product','cms') NOT NULL DEFAULT 'all',
  `devices` set('all','desktop','tablet','mobile') NOT NULL DEFAULT 'all',
  `customers_groups` varchar(32) NOT NULL DEFAULT '0',
  `text_title` varchar(64) NOT NULL,
  `text_description` text,
  `text_note` text,
  `text_success` text,
  `text_submit` varchar(64) NOT NULL,
  `text_cancel` varchar(64) NOT NULL,
  `code_length` INT(8) NOT NULL DEFAULT '12',
  `code_format` ENUM('alphanum','alpha','num') NOT NULL DEFAULT 'alphanum',
  `code_prefix` VARCHAR(16) NOT NULL,
  `code_suffix` VARCHAR(16) NOT NULL,
  `code_dash` INT(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (`entity_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
");

$installer->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('newsletterpopup_history')}` (
  `entity_id` int(11) NOT NULL AUTO_INCREMENT,
  `popup_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `customer_group` int(11) NOT NULL,
  `customer_ip` varchar(16) NOT NULL,
  `customer_email` varchar(128) NOT NULL,
  `coupon_code` varchar(64) NOT NULL,
  `landing_page` varchar(255) NOT NULL,
  `store_id` int(11) NOT NULL,
  `action` enum('subscribe','cancel') NOT NULL DEFAULT 'cancel',
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`entity_id`),
  KEY `popup_id` (`popup_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
");

$installer->run("
INSERT INTO `{$this->getTable('newsletterpopup_popups')}` (
  `name`, `status`, `coupon_code`, `start_date`, `end_date`, `success_page`, `custom_success_page`, `send_email`, `template_name`, `display_popup`, `delay_time`, `cookie_time_frame`, `store_id`, `show_on`, `devices`, `customers_groups`, `text_title`, `text_description`, `text_note`, `text_success`, `text_submit`, `text_cancel`, `code_length`, `code_format`, `code_prefix`, `code_suffix`, `code_dash`) VALUES
  (
    'Newsletter Popup $10 - Default Template', 
    1, 
    0, 
    NULL, 
    NULL, 
    '__stay__', 
    '', 
    1, 
    'newsletterpopup-default', 
    'after_time_delay', 
    5, 
    30,
    '0', 
    'all', 
    'all', 
    '0', 
    'GET $10 OFF YOUR FIRST ORDER', 
    '<p>Join Magento Store List and Save!<br />Subscribe Now &amp; Receive a $10 OFF coupon in your email!</p>', 
    '<p>Enter your email</p>', 
    '<p>Thank you for your subscription!</p>', 
    'Sign Up Now', 
    'Hide', 
    12, 
    'alphanum', 
    '', 
    '', 
    0
);
");
$installer->endSetup();
