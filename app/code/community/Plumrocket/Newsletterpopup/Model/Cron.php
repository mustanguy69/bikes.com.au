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


class Plumrocket_Newsletterpopup_Model_Cron extends Mage_Core_Model_Abstract
{
	public function clearOldHistory($schedule)
	{
		if (Mage::helper('newsletterpopup')->moduleEnabled()
			&& Mage::getStoreConfig('newsletterpopup/general/enable_history')
		) {
			// count of months
			$offset = (int)Mage::getStoreConfig('newsletterpopup/general/erase_history') * 86400;
			// if 0 then never erase
			if ($offset) {
				Mage::getSingleton('core/resource')->getConnection('core_write')
					->query(sprintf("DELETE FROM %s WHERE `date_created` <= '%s'",
						Mage::getSingleton('core/resource')->getTableName('newsletterpopup_history'),
						strftime('%Y-%m-%d %H:%M:%S', time() - $offset)
					));
				//$jobConfig = Mage::getConfig()->getNode('crontab/jobs')->{$schedule->getJobCode()};
			}
		}
	}
}
