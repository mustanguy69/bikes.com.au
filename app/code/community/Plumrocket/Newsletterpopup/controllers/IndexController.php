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


class Plumrocket_Newsletterpopup_IndexController extends Mage_Core_Controller_Front_Action
{
    public function blockAction()
    {
        $this->getResponse()
            ->clearHeader('Location')
            ->clearRawHeader('Location')
            ->setHttpResponseCode(200)
            ->setBody(
                $this->getLayout()->createBlock('newsletterpopup/template')->toHtml()
            );
    }

    public function previewAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function snapshotAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function subscribeAction()
    {
        $session = Mage::getSingleton('customer/session');
        try {
            if (! Mage::helper('newsletterpopup')->moduleEnabled()) {
                Mage::throwException($this->__('The Plumrocket Newsletter Popup Module is disabled.'));
            }

            $email = $this->getRequest()->getParam('email');
            if (! Zend_Validate::is($email, 'EmailAddress')) {
                Mage::throwException($this->__('Please enter a valid email address.'));
            }

            if (Mage::getStoreConfig('newsletterpopup/disposable_emails/disable')) {
                $_email = preg_replace('#[[:space:]]#', '', $email);
                preg_match('#@([\w-.]+$)#is', $_email, $domain);
                if(!empty($domain[1])) {
                    preg_match('#(?:^|[\s,]+)'. preg_quote($domain[1]) .'(?:$|[\s,]+)#i', Mage::getStoreConfig('newsletterpopup/disposable_emails/domains'), $math);
                    if(!empty($math)) {
                        Mage::throwException($this->__('This email address provider is blocked. Please try again with different email address.'));
                    }
                }
            }

            $subscriber = Mage::getModel('newsletterpopup/subscriber')->load($email, 'subscriber_email');
            if ((int)$subscriber->getId() !== 0) {
                Mage::throwException($this->__('This email address is already assigned to another user.'));
            }

            $inputData = $this->getRequest()->getParams();
            // Prepare Region.
            if (empty($inputData['region']) && !empty($inputData['region_id']) && is_numeric($inputData['region_id'])) {
                if ($region = Mage::getModel('directory/region')->load($inputData['region_id'])) {
                    $inputData['region'] = $region->getName();
                }
            }

            $subscriber->customSubscribe($email, $this, $inputData);
        }
        catch (Mage_Core_Exception $e) {
            $session->addError($e->getMessage());
        }
        catch (Exception $e) {
            $session->addError($this->__('Unknown Error'));
            Mage::logException($e);
        }

        $data = array(
            'error'=> 0,
            'messages' => array(),
            'hasSuccessTextPlaceholders' => false,
        );

        $messages = $session->getMessages(true);
        foreach ($messages->getItems() as $message) {
            if ($message->getType() != Mage_Core_Model_Message::SUCCESS) {
                $data['error'] = 1;
            }
            if (! array_key_exists($message->getType(), $data['messages'])) {
                $data['messages'][$message->getType()] = array();
            }
            $data['messages'][$message->getType()][] = $message->getCode();
        }

        if ($popupId = $this->getRequest()->getParam('id')) {
            $data['hasSuccessTextPlaceholders'] = Mage::helper('newsletterpopup')
                ->getPopupById($popupId)
                ->hasSuccessTextPlaceholders();
        }

        $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true)
            ->clearHeader('Location')
            ->clearRawHeader('Location')
            ->setBody(json_encode($data));
    }

    public function cancelAction()
    {
        Mage::getSingleton('newsletterpopup/subscriber')->cancel();
        $this->getResponse()
            ->setHeader('Content-type', 'application/json')
            ->setBody(json_encode(array('error'=> 0, 'messages' => array())));
    }

    public function historyAction()
    {
        if ($actionText = $this->getRequest()->getParam('npaction')) {
            $actionText = Mage::helper('core/string')->substr(strip_tags($actionText), 0, 200);
            Mage::getSingleton('newsletterpopup/subscriberEncoded')->history($actionText);
        }

        $this->getResponse()
            ->setHeader('Content-type', 'application/json')
            ->setBody(json_encode(array('error'=> 0, 'messages' => array())));
    }

    public function psloginAction()
    {
        $js = array();
        if ($pslogin = $this->getRequest()->getParam('pslogin')) {
            $js[] = 'var newspopupForm = window.opener.pjQuery_1_12_4(".newspopup_up_bg_form");';

            if (!empty($pslogin['firstname'])) {
                $js[] = 'newspopupForm.find("input[name=firstname]").val("'. trim($pslogin['firstname']) .'");';
            }

            if (!empty($pslogin['lastname'])) {
                $js[] = 'newspopupForm.find("input[name=lastname]").val("'. trim($pslogin['lastname']) .'");';
            }

            if (!empty($pslogin['email']) && !Mage::helper('pslogin')->isFakeMail($pslogin['email'])) {
                $js[] = 'newspopupForm.find("input[type=email]").val("'. trim($pslogin['email']) .'");';
            }

            if (!empty($pslogin['dob'])) {
                list($year, $month, $day) = explode('-', $pslogin['dob'], 3);
                if ($year > 0) {
                    $js[] = 'newspopupForm.find("input[name=year]").val("'. $year .'");';
                }
                if ($month > 0) {
                    $js[] = 'newspopupForm.find("input[name=month]").val("'. $month .'");';
                }
                if ($day > 0) {
                    $js[] = 'newspopupForm.find("input[name=day]").val("'. $day .'");';
                }
            }

            if (!empty($pslogin['gender'])) {
                $js[] = 'newspopupForm.find("input[name=gender][value='. $pslogin['gender'] .']").prop("checked", true);';
            }

            if (count($js) > 1) {
                $js[] = 'window.opener.pjQuery_1_12_4(".newspopup_up_bg_form:visible").submit();';
            }
        }

        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('core/template')
                ->setJs('if(window.opener && window.opener.location && !window.opener.closed) { '. join(' ', $js) .' window.close(); }')
                ->setTemplate('pslogin/runjs.phtml')
                ->toHtml()
        );

    }

}
