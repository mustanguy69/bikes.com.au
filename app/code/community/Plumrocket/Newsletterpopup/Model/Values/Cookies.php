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


class Plumrocket_Newsletterpopup_Model_Values_Cookies extends Plumrocket_Newsletterpopup_Model_Values_Base
{
    const _GLOBAL = 0;
    const _SEPARATE = 1;

    public function toOptionHash()
    {
        return array(
            self::_GLOBAL   => Mage::helper('newsletterpopup')->__('Global cookie for all popups'),
            self::_SEPARATE => Mage::helper('newsletterpopup')->__('Separate cookie for each popup'),
        );
    }
}
