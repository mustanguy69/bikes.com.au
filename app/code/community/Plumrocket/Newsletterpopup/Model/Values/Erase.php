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


class Plumrocket_Newsletterpopup_Model_Values_Erase extends Plumrocket_Newsletterpopup_Model_Values_Base
{
    const WEEK_1 = 7;
    const WEEK_2 = 14;
    const WEEK_3 = 21;
    const MON_1 = 30;
    const MON_3 = 91;
    const MON_6 = 182;
    const MON_12 = 365;
    const MON_0 = 0;
    
    public function toOptionHash()
    {
        return array(
            self::WEEK_1   => Mage::helper('newsletterpopup')->__('Older than 1 week'),
            self::WEEK_2   => Mage::helper('newsletterpopup')->__('Older than 2 weeks'),
            self::WEEK_3   => Mage::helper('newsletterpopup')->__('Older than 3 weeks'),
            self::MON_1    => Mage::helper('newsletterpopup')->__('Older than 1 month'),
            self::MON_3    => Mage::helper('newsletterpopup')->__('Older than 3 months'),
            self::MON_6    => Mage::helper('newsletterpopup')->__('Older than 6 months'),
            self::MON_12   => Mage::helper('newsletterpopup')->__('Older than 1 year'),
            self::MON_0    => Mage::helper('newsletterpopup')->__('Never'),
        );
    }
}