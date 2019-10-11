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


class Plumrocket_Newsletterpopup_Model_Popup_Condition_Found extends Mage_SalesRule_Model_Rule_Condition_Product_Found
{
    
    public function __construct()
    {
        parent::__construct();
        $this->setType('newsletterpopup/popup_condition_found');
    }

    public function validate(Varien_Object $object)
    {
        return parent::validate($object->getQuote());
    }

}