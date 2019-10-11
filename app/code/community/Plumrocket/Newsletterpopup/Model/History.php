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


class Plumrocket_Newsletterpopup_Model_History extends Mage_Core_Model_Abstract
{
    protected $_checkEnabled = true;
    protected $_checkIpSkip = true;

    protected function _construct()
    {
        $this->_init('newsletterpopup/history');
    }

    protected function _beforeSave()
    {
        if (!$this->_checkEnabled || Mage::getStoreConfig('newsletterpopup/general/enable_history')) {

            $customerIp = Mage::helper('core/http')->getRemoteAddr();

            if ($this->_checkIpSkip) {
                $ipSkip = explode("\n", Mage::getStoreConfig('newsletterpopup/general/ip_skip'));
                foreach ($ipSkip as &$ip) {
                    $ip = trim($ip);
                }
                if($this->getAction() != Plumrocket_Newsletterpopup_Model_Values_Action::SUBSCRIBE && in_array($customerIp, $ipSkip)) {
                    $this->_dataSaveAllowed = false;
                    return $this;
                }
            }

            $customer = (Mage::getSingleton('customer/session')->isLoggedIn())?
                Mage::getSingleton('customer/session')->getCustomer(): false;

            $path = str_replace(array('http://', 'https://'), '', Mage::app()->getRequest()->getParam('referer'));
            $path = substr($path, strpos($path, '/'));

            $data = array_merge(array(
                'customer_id'       => ($customer)? $customer->getId(): 0,
                'customer_group'    => ($customer)? $customer->getGroupId(): 0,
                'customer_ip'       => $customerIp,
                'landing_page'      => $path,
                'store_id'          => Mage::app()->getStore()->getStoreId(),
                'date_created'      => strftime('%Y-%m-%d %H:%M:%S', time()),
            ), $this->getData());

            $this->setData($data);
        } else {
            $this->_dataSaveAllowed = false;
        }

        return $this;
    }

    public function save()
    {
        if ($this->getAction() == Plumrocket_Newsletterpopup_Model_Values_Action::OTHER && $this->getActionText()) {
            if (!$id = $this->_getResource()->insertOnDuplicate('newsletterpopup/history_action', array('text' => trim($this->getActionText())), array('text'))) {
                $resource = Mage::getSingleton('core/resource');
                $connection = $resource->getConnection('core_read');
                $id = $connection->fetchOne('SELECT `id` FROM '. $resource->getTableName('newsletterpopup/history_action') .' WHERE `text` = ?  LIMIT 1', trim($this->getActionText()));
            }

            if(!empty($id)) {
                $this->setActionId($id);
                $this->unsActionText();
            }
        }

        return parent::save();
    }

    public function checkEnabled($flag = null)
    {
        if (!is_null($flag)) {
            $thus->_checkEnabled = (bool)$flag;
        }
        return $thus->_checkEnabled;
    }

    public function checkIpSkip($flag = null)
    {
        if (!is_null($flag)) {
            $thus->_checkIpSkip = (bool)$flag;
        }
        return $thus->_checkIpSkip;
    }
}
