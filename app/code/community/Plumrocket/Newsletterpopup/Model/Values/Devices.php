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


class Plumrocket_Newsletterpopup_Model_Values_Devices extends Plumrocket_Newsletterpopup_Model_Values_Base
{
    const ALL      = 'all';
    const DESKTOP  = 'desktop';
    const TABLET   = 'tablet';
    const MOBILE   = 'mobile';

    public function toOptionHash()
    {
        return array(
            // self::ALL      => Mage::helper('newsletterpopup')->__('All Devices'),
            self::DESKTOP  => Mage::helper('newsletterpopup')->__('Desktop'),
            self::TABLET   => Mage::helper('newsletterpopup')->__('Tablet'),
            self::MOBILE   => Mage::helper('newsletterpopup')->__('Mobile'),
        );
    }
}