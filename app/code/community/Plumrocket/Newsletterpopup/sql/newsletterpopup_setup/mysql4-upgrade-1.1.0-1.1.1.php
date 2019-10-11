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
	ALTER TABLE `{$this->getTable('newsletterpopup_popups')}` CHANGE `send_email` `send_email` INT( 1 ) NOT NULL DEFAULT '1';
");

$installer->run("
	ALTER TABLE `{$this->getTable('newsletterpopup_popups')}` CHANGE `cookie_time_frame` `cookie_time_frame` INT( 11 ) NOT NULL DEFAULT '30';
");

$installer->endSetup();