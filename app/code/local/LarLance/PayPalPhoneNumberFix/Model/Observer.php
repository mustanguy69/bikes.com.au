<?php

class LarLance_PayPalPhoneNumberFix_Model_Observer
{

    /**
     * Add phone to address if empty
     *
     * @param Varien_Event_Observer $observer
     * @return void
     * @throws Varien_Exception
     */
    public function addPhoneToAddress($observer)
    {
        $order = $observer->getOrder();
        if ($order->getPayment()->getMethod() !== 'paypal_express') {
            return;
        }
        $post = Mage::app()->getRequest()->getPost();
        $address =  $order->getShippingAddress();

        if (!empty($post) &&
            isset($post['paypal-telephone'])
        ) {
            $phone = strip_tags($post['paypal-telephone']['telephone']);
            $address->setData('telephone',  $phone);
            $order->getBillingAddress()->setData('telephone',  $phone);
        }

    }
}
