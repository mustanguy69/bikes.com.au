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


class Plumrocket_Newsletterpopup_Model_Values_Method extends Plumrocket_Newsletterpopup_Model_Values_Base
{
    const AFTER_TIME_DELAY  = 'after_time_delay';
    const LEAVE_SITE        = 'leave_page';
    const PAGE_SCROLL       = 'on_page_scroll';
    const MOUSEOVER         = 'on_mouseover';
    const CLICK             = 'on_click';
    const MANUALLY          = 'manually';
    //const CLOSE_PAGE        = 'close_page';

    public function toOptionHash()
    {
        return array(
            self::AFTER_TIME_DELAY  => Mage::helper('newsletterpopup')->__('After time delay'),
            self::LEAVE_SITE        => Mage::helper('newsletterpopup')->__('When leaving site (out of focus)'),
            self::PAGE_SCROLL       => Mage::helper('newsletterpopup')->__('On Page Scroll'),
            self::MOUSEOVER         => Mage::helper('newsletterpopup')->__('On Mouse Over'),
            self::CLICK             => Mage::helper('newsletterpopup')->__('On Click'),
            //self::CLOSE_PAGE        => Mage::helper('newsletterpopup')->__('On window close'),
            self::MANUALLY          => Mage::helper('newsletterpopup')->__('Manually (for web developers)'),
        );
    }
}