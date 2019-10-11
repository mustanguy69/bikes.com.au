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


class Plumrocket_Newsletterpopup_Model_Mysql4_Template_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('newsletterpopup/template');
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

    public function addFieldToFilter($field, $alias = null)
    {
        if($field == 'template_type') {
            $field = 'base_template_id';
            if(isset($alias['eq']) && $alias['eq'] != -1) {
                $alias = array(
                    'neq' => -1
                );
            }
        }

        return parent::addFieldToFilter($field, $alias);
    }
}
