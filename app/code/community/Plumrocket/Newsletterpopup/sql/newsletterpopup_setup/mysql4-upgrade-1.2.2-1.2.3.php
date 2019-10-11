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

$tableName = $this->getTable('newsletterpopup_form_fields');
$installer->run("
	INSERT INTO `{$tableName}` 
		(`name`, `enable`, `label`, `sort_order`, `popup_id`)
		SELECT * FROM 
			(SELECT 'email', 1, CONCAT('Email', ''), 10, 1 + 0) AS tmp
		WHERE NOT EXISTS (
			SELECT `name` 
			FROM {$tableName} 
			WHERE 
				`name` = 'email' 
				AND `popup_id` = 1
		) LIMIT 1;
");

$installer->endSetup();