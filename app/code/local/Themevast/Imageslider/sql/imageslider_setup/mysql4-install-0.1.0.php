<?php
$installer = $this;

$installer->startSetup();

$installer->run("
  -- DROP TABLE IF EXISTS {$this->getTable('imageslider')};
  CREATE TABLE {$this->getTable('imageslider')} (
    `imageslider_id` int(11) unsigned NOT NULL auto_increment,
    `title` varchar(255) NOT NULL default '',
    `link` varchar(255) NOT NULL default '',
    `image` varchar(255) NOT NULL default '',
    `effect` varchar(255) NOT NULL default '',
    `description` text NOT NULL default '',
    `stores` varchar(255) NOT NULL default '0',
    `order` smallint(6) NOT NULL default '0',
    `status` smallint(6) NOT NULL default '0',
    `created_time` datetime NULL,
    `update_time` datetime NULL,
    PRIMARY KEY (`imageslider_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

