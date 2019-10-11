<?php

class Zipmoney_ZipmoneyPayment_Model_Cronjob {

    public function cleanOrders() {
        if (Mage::getStoreConfig('payment/zipmoney_developer_settings/cron_enabled') == 1) {
            Mage::helper('zipmoneypayment')->writelog("zipMoney Cronjob :", "cleanOrders");
            $date = Mage::app()->getLocale()->storeDate(Mage::app()->getStore(), null, true)->toString('Y-MM-dd HH:mm:ss');
            $days = Mage::getStoreConfig('payment/zipmoney_developer_settings/cron_frequency');
            $new_date = strtotime('-' . $days . ' day', strtotime($date));
            $new_date = date('Y-m-d H:i:s', $new_date);
            Mage::helper('zipmoneypayment')->writelog("zipMoney Cronjob Time:", $new_date);
            $orders = Mage::getModel('sales/order')->getCollection()
                    ->addAttributeToFilter('created_at', array('lteq' => $new_date))
                    ->addFieldToFilter('status', array('eq' => array('zip_pending')))
                    ->load();
            foreach ($orders as $order) {
                if ($order->getPayment()->getMethodInstance()->getCode() == 'zipmoneypayment') {
                    Mage::helper('zipmoneypayment')->writelog("zipMoney Cronjob (Canceled) Order ID :", $order->getId());
                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
                }
            }
        }
    }

}
