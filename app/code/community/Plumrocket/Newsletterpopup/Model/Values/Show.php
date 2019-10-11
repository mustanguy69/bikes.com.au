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


class Plumrocket_Newsletterpopup_Model_Values_Show extends Plumrocket_Newsletterpopup_Model_Values_Base
{
    const ON_ALL_PAGES      = 'all';
    const ON_HOME_PAGE      = 'home';
    const ON_CATEGORY_PAGES = 'category';
    const ON_PRODUCT_PAGES  = 'product';
    const ON_CMS_PAGES      = 'cms';
    const ON_ACCOUNT_PAGES  = 'account';

    public function toOptionHash()
    {
        return array(
            // self::ON_ALL_PAGES       => Mage::helper('newsletterpopup')->__('All pages (Excluding account pages)'),
            self::ON_HOME_PAGE       => Mage::helper('newsletterpopup')->__('Home page'),
            self::ON_CATEGORY_PAGES  => Mage::helper('newsletterpopup')->__('Category pages'),
            self::ON_PRODUCT_PAGES   => Mage::helper('newsletterpopup')->__('Product pages'),
            self::ON_CMS_PAGES       => Mage::helper('newsletterpopup')->__('CMS pages'),
        );
    }
}