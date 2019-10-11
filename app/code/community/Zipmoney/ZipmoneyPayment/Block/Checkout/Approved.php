<?php

class Zipmoney_ZipmoneyPayment_Block_Checkout_Approved extends Mage_Core_Block_Template {

    public function getViewOrderUrl($orderId) {
        return $this->getUrl('sales/order/view/', array('order_id' => $orderId));
    }

    public function getOrderId() {
        return Mage::getSingleton('core/session')->getZipOrderId();
    }

}
