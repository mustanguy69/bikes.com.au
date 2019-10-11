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


class Plumrocket_Newsletterpopup_Model_Mysql4_Template extends Mage_Core_Model_Mysql4_Abstract
{
	
	protected function _construct()
	{
		$this->_init('newsletterpopup/template', 'entity_id');
	}

	protected function _afterDelete(Mage_Core_Model_Abstract $object)
    {
    	$object->isDeleted(true);
        return $this;
    }

}