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


class Plumrocket_Newsletterpopup_Model_Values_SubscriptionMode extends Plumrocket_Newsletterpopup_Model_Values_Base
{
    const ALL_LIST 			= 'all';
    const ALL_SELECTED_LIST = 'all_selected';
    const ONE_LIST_RADIO 	= 'one_radio';
    const ONE_LIST_SELECT 	= 'one_select';
    const MUPTIPLE_LIST 	= 'multiple';

    public function toOptionHash()
    {
        return array(
            //self::ALL_LIST          => Mage::helper('newsletterpopup')->__('Automatically subscribe to all lists (hide selector)'),
            self::ALL_SELECTED_LIST => Mage::helper('newsletterpopup')->__('Automatically subscribe to selected lists (hide selection in frontend)'),
            self::ONE_LIST_RADIO    => Mage::helper('newsletterpopup')->__('Let user choose one list (radio-buttons)'),
            self::ONE_LIST_SELECT   => Mage::helper('newsletterpopup')->__('Let user choose one list (selectbox)'),
            self::MUPTIPLE_LIST     => Mage::helper('newsletterpopup')->__('Let user choose multiple lists (checkboxes)'),
        );
    }
}