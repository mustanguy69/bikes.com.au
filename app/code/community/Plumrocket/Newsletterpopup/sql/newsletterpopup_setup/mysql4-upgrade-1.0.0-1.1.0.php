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
	UPDATE `{$this->getTable('core_config_data')}` SET `value` = `value` * 30 WHERE `path` = 'newsletterpopup/general/erase_history';
");

$installer->run("
	ALTER TABLE  `{$this->getTable('newsletterpopup_popups')}` 
		ADD  `views_count` INT NOT NULL ,
		ADD  `subscribers_count` INT NOT NULL ,
		ADD  `orders_count` INT NOT NULL ,
		ADD  `total_revenue` decimal(12,4) NOT NULL;
");

$installer->run("
	ALTER TABLE  `{$this->getTable('newsletterpopup_history')}` 
		ADD  `increment_id` INT NOT NULL ,
		ADD  `order_id` INT NOT NULL ,
		ADD  `grand_total` decimal(12,4) NOT NULL;
");

$installer->run("
	UPDATE `{$this->getTable('newsletterpopup_history')}` nh
		LEFT JOIN `{$this->getTable('sales_flat_order')}` sfo
			ON `nh`.`coupon_code` = `sfo`.`coupon_code` 
		SET `nh`.`increment_id` = `sfo`.`increment_id`,
			`nh`.`order_id` = `sfo`.`entity_id`,
			`nh`.`grand_total` = `sfo`.`grand_total`
		WHERE `sfo`.`coupon_code` IS NOT NULL;
");

$installer->run("
	UPDATE `{$this->getTable('newsletterpopup_popups')}` np
		LEFT JOIN (
			SELECT 
				`popup_id`,
				COUNT(`entity_id`) as `views_count`,
				SUM(`action` = 'subscribe') as `subscribers_count`,
				SUM(`order_id`>0) as `orders_count`,
				SUM(`grand_total`) as `total_revenue`
			FROM `{$this->getTable('newsletterpopup_history')}`
			GROUP by `popup_id`
		) tmp 
			ON `np`.`entity_id` = `tmp`.`popup_id`
		SET 
			`np`.`views_count` = `tmp`.`views_count`,
			`np`.`subscribers_count` = `tmp`.`subscribers_count`,
			`np`.`orders_count` = `tmp`.`orders_count`,
			`np`.`total_revenue` = `tmp`.`total_revenue`;
");

$installer->endSetup();