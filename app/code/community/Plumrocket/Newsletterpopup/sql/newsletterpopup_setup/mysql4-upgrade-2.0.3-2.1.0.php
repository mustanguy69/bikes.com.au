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


$installer->run("CREATE TABLE IF NOT EXISTS `{$this->getTable('newsletterpopup_history_action')}` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `text` CHAR(200) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `text` (`text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

$installer->run("ALTER TABLE `{$this->getTable('newsletterpopup_history')}` CHANGE `action` `action` ENUM( 'subscribe', 'cancel', 'other' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'cancel'");
$installer->run("ALTER TABLE `{$this->getTable('newsletterpopup_popups')}` CHANGE `display_popup` `display_popup` ENUM( 'after_time_delay', 'leave_page', 'on_page_scroll', 'close_page', 'on_mouseover', 'on_click', 'manually' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'after_time_delay'");

try {
	$installer->run("ALTER TABLE `{$this->getTable('newsletterpopup_history')}` ADD `action_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `action`");
} catch (Exception $e) {}

try {
	$installer->run("ALTER TABLE `{$this->getTable('newsletterpopup_popups')}` ADD `css_selector` CHAR(100) NOT NULL AFTER `page_scroll` ");
} catch (Exception $e) {}

$installer->endSetup();
