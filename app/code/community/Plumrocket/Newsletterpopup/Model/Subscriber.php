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


class Plumrocket_Newsletterpopup_Model_Subscriber extends Mage_Newsletter_Model_Subscriber
{
    // new method
    public function customSubscribe($email, $controller, $data = array())
    {
        $subscriber = Mage::getSingleton('newsletterpopup/subscriberEncoded');
        $customer = $subscriber->validateCustomer($data);
        $address = $subscriber->validateAddress($data);
        if ($customer === false || $address === false) {
            return false;
        }

        if($customerId = $subscriber->tryRegisterCustomer($customer, $controller)) {
            // save address
            $address->setCustomerId($customerId)
                ->setIsDefaultBilling(true)
                ->setIsDefaultShipping(true);

            $address->save();
        }

        $this->addData(
            Mage::getSingleton('newsletterpopup/subscriberEncoded')->getAdditionalData($data)
        );

        $status = $this->subscribe($email);

        $session = Mage::getSingleton('customer/session');
        if ($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) {
            $subscriber->holdSubscribe($email, $data);
            $session->addSuccess(Mage::helper('newsletterpopup')->__('Thank you for subscribing to our newsletter! Confirmation request has been sent.'));
        } else {
            $subscriber->subscribe($email, $data);
            if ($succText = $subscriber->getPopup()->getPreparedTextSuccess()) {
                $session->addSuccess($succText);
            }
            //$session->addSuccess(Mage::helper('newsletterpopup')->__('Thank you for subscribing to our newsletter! Your subscription request has been confirmed and you will be receiving more information by email shortly!'));
        }
        return $status;
    }

    // new method
    public function cancel()
    {
        return Mage::helper('newsletterpopup')->moduleEnabled() && !Mage::app()->getStore()->isAdmin()
            ? Mage::getSingleton('newsletterpopup/subscriberEncoded')->cancel()
            : false;
    }

    public function confirm($code)
    {
        $result = parent::confirm($code);
        if ($result && Mage::helper('newsletterpopup')->moduleEnabled()) {
            Mage::getSingleton('newsletterpopup/subscriberEncoded')
                ->releaseSubscribe($this);
        }
        return $result;
    }

    public function sendConfirmationSuccessEmail()
    {
        if (Mage::getSingleton('newsletterpopup/subscriberEncoded')->getPopup()->getSendEmail()) {
            if (Mage::helper('newsletterpopup')->moduleEnabled()
                && Mage::getSingleton('newsletterpopup/subscriberEncoded')->getPopup()->getId() > 0
            ) {
                return $this;
            }
            return parent::sendConfirmationSuccessEmail();
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _beforeSave()
    {
        if (!$this->getId()) {
            $this->isObjectNew(true);
        }
        Mage::dispatchEvent('model_save_before', array('object'=>$this));

        /**
         * Fix for mailchimp double opt-in function.
         * Disable logic in Ebizmarts_MailChimp_Model_Observer::handleSubscriber
         */
        $flag = $this->getIsStatusChanged();
        $this->setIsStatusChanged(false);
        Mage::dispatchEvent($this->_eventPrefix.'_save_before', $this->_getEventData());
        $this->setIsStatusChanged($flag);

        return $this;
    }
}
