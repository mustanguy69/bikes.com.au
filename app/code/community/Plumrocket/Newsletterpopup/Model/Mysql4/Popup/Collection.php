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


class Plumrocket_Newsletterpopup_Model_Mysql4_Popup_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init("newsletterpopup/popup");
    }

    public function addStoreFilter($store)
    {
        if ($store instanceof Mage_Core_Model_Store) {
            $store = $store->getId();
        }

        if (is_array($store)) {
            $store = $store[0];
        }

        $this->getSelect()->where("FIND_IN_SET('{$store}', `store_id`) OR FIND_IN_SET('0', `store_id`)");

        return $this;
    }

    public function addTemplateData()
    {
        $this->join(array('t' => 'newsletterpopup/template'), 't.entity_id = main_table.template_id', array('name as template_name', 'base_template_id', 'code', 'style'));
        $this->getSelect()
            ->joinLeft(array('t2' => $this->getTable('newsletterpopup/template')), 't2.entity_id = t.base_template_id', array('base_template_name' => 'name'));

        return $this;
    }
}
