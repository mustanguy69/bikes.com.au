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


class Plumrocket_Newsletterpopup_Model_Values_Mailchimplist extends Plumrocket_Newsletterpopup_Model_Values_Base
{    
    public function toOptionHash()
    {
        $model = Mage::helper('newsletterpopup/adminhtml')->getMcapi();
        $items = array();
        if ($model) {
            $result = $model->lists();
            $lists = (array)$result['data'];

            foreach ($lists as $list) {
                $items[ $list['id'] ] = $list['name'];
            }
        }
        return $items;
    }
}
