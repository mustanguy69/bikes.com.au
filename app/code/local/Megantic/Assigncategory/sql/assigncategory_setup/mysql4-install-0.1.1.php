<?php

$installer = $this;

$installer->startSetup();

/**

write your modulename

**/

$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable(Megantic_Assigncategory_Model_Authenticate::MEGANTIC_DOMAIN_TABLE_NAME)} (
  `modules` int(11) unsigned NOT NULL auto_increment,  
  `domain_name` varchar(255),  
  `megantic_module` varchar(255),
  PRIMARY KEY (`modules`)
) ENGINE = INNODB CHARSET=utf8;

");

$installer->endSetup(); 
?>
