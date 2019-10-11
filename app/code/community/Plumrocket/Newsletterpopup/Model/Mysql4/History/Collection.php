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


class Plumrocket_Newsletterpopup_Model_Mysql4_History_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init("newsletterpopup/history");
    }

    public function addCustomerNameToSelect()
    {
        $firstnameAttr = Mage::getModel('eav/entity_attribute')->loadByCode('1', 'firstname');
        $lastnameAttr = Mage::getModel('eav/entity_attribute')->loadByCode('1', 'lastname');

        $prefix = Mage::getConfig()->getTablePrefix();
        $this->getSelect()
            ->joinLeft(
                array('ce1' => $prefix . 'customer_entity_varchar'),
                '`ce1`.`entity_id` = `main_table`.`customer_id` AND `ce1`.`attribute_id` = ' . $firstnameAttr->getAttributeId(),
                array('firstname' => 'value')
            )
            ->joinLeft(
                array('ce2' => $prefix . 'customer_entity_varchar'),
                '`ce2`.`entity_id` = `main_table`.`customer_id` AND `ce2`.`attribute_id` = ' . $lastnameAttr->getAttributeId(),
                array('lastname' => 'value')
            )
            // check if customer exists, because firstname and lastname will not every exists
            ->joinLeft(
                array('ce' => $prefix . 'customer_entity'),
                '`ce`.`entity_id` = `main_table`.`customer_id`',
                array('cid' => 'entity_id')
            )
            ->columns(new Zend_Db_Expr("CONCAT(`ce1`.`value`, ' ',`ce2`.`value`) AS customer_name"));

        return $this;
    }

    public function addNameFilter($value)
    {
        if (is_numeric($value)) {
            $this->getSelect()->where("`customer_id` = {$value}");
        } else {
            $inputKeywords = explode(' ', $value);
            $select = array();
            foreach ($inputKeywords as $keyword) {
                $select[] = "CONCAT(`ce1`.`value`, ' ',`ce2`.`value`) LIKE '%{$keyword}%'";
            }
            $this->getSelect()->where(implode(' AND ', $select));
        }
        return $this;
    }

    public function addActionTextToResult()
    {
        $prefix = Mage::getConfig()->getTablePrefix();
        $this->getSelect()
            ->joinLeft(
                array('ha' => $prefix . 'newsletterpopup_history_action'),
                '`ha`.`id` = `main_table`.`action_id`',
                new Zend_Db_Expr("IFNULL(`ha`.`text`, `main_table`.`action`) AS 'action'")
            );

        return $this;
    }
}
