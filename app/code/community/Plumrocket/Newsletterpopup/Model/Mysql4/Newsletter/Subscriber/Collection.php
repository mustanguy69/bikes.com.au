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


class Plumrocket_Newsletterpopup_Model_Mysql4_Newsletter_Subscriber_Collection extends Mage_Newsletter_Model_Resource_Subscriber_Collection
{

    public function showCustomerInfo()
    {
        if (! Mage::helper('newsletterpopup')->moduleEnabled()) {
            return parent::showCustomerInfo();
        }
        
        $adapter = $this->getConnection();
        $customer = Mage::getModel('customer/customer');
        $firstname  = $customer->getAttribute('firstname');
        $lastname   = $customer->getAttribute('lastname');

        $lastNameExpr = $this->getResource()->getReadConnection()
            ->getCheckSql('ISNULL(customer_lastname_table.value)', 'main_table.subscriber_lastname', 'customer_lastname_table.value');

        $firstNameExpr = $this->getResource()->getReadConnection()
            ->getCheckSql('ISNULL(customer_firstname_table.value)', 'main_table.subscriber_firstname', 'customer_firstname_table.value');

        $this->getSelect()
            ->joinLeft(
                array('customer_lastname_table'=>$lastname->getBackend()->getTable()),
                $adapter->quoteInto('customer_lastname_table.entity_id=main_table.customer_id
                 AND customer_lastname_table.attribute_id = ?', (int)$lastname->getAttributeId()),
                array('customer_lastname'=> new Zend_Db_Expr($lastNameExpr))
            )
            ->joinLeft(
                array('customer_firstname_table'=>$firstname->getBackend()->getTable()),
                $adapter->quoteInto('customer_firstname_table.entity_id=main_table.customer_id
                 AND customer_firstname_table.attribute_id = ?', (int)$firstname->getAttributeId()),
                array('customer_firstname'=>new Zend_Db_Expr($firstNameExpr))
            );

        return $this;
    }
}
