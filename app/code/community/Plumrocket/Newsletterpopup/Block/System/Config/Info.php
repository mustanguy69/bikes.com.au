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


class Plumrocket_Newsletterpopup_Block_System_Config_Info extends Mage_Adminhtml_Block_System_Config_Form_Field
{ 

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);

        $key = trim(Mage::helper('core')->decrypt(Mage::getStoreConfig('newsletterpopup/mailchimp/key')));

        if (! Mage::getStoreConfig('newsletterpopup/mailchimp/enable')) {
            $message = 'Mailchimp Synchronization is disabled.';
        } elseif (!$key) {
            $message = 'Mailchimp API Key is not provided.';
        } else {
            $model = Mage::helper('newsletterpopup/adminhtml')->getMcapi();
            if ($model) {
                $message = $model->ping();
                if ($message == "Everything's Chimpy!" || $message == '') {
                	$profile = $model->getAccountDetails();

                    if (isset($profile['username']) && $profile['username']) {
            	        return sprintf('<ul class="checkboxes" style="border: 1px solid #ccc; padding: 5px; background-color: #fdfdfd;">
            	        	<li>Username: %s</li>
            	        	<li>Plan type: %s</li>
            	        	<li>Is in trial mode?: %s</li>
            	        	</ul>',
            		        $profile['username'],
            		        $profile['plan_type'],
            	        	$profile['is_trial']? 'Yes': 'No'
            	        );
                    } else {
                        $message = 'Mailchimp API Key is not valid.';
                    }
                } else {
                    $message = 'Mailchimp server returned error: ' . $message;
                }
            } else {
                $message = 'Connection failed.';
            }
        }
        return '<div class="checkboxes" style="border: 1px solid #ccc; padding: 5px; background-color: #fdfdfd;">' . $message . '</div>';
    }
}
