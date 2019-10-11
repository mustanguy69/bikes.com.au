<?php
/**
 * Magmodules.eu - http://www.magmodules.eu.
 *
 * NOTICE OF LICENSE
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.magmodules.eu/MM-LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magmodules.eu so we can send you a copy immediately.
 *
 * @category      Magmodules
 * @package       Magmodules_Googleshopping
 * @author        Magmodules <info@magmodules.eu>
 * @copyright     Copyright (c) 2017 (http://www.magmodules.eu)
 * @license       https://www.magmodules.eu/terms.html  Single Service License
 */

class Magmodules_Googleshopping_Model_Observer
{

    /**
     * @param $schedule
     */
    public function scheduledGenerateGoogleshopping($schedule)
    {
        $enabled = Mage::getStoreConfig('googleshopping/general/enabled');
        $cron = Mage::getStoreConfig('googleshopping/generate/cron');
        $nextStore = Mage::helper('googleshopping')->getUncachedConfigValue('googleshopping/generate/cron_next');
        if ($enabled && $cron) {
            $storeIds = Mage::helper('googleshopping')->getStoreIds('googleshopping/generate/enabled');
            if (empty($nextStore) || ($nextStore >= count($storeIds))) {
                $nextStore = 0;
            }

            $storeId = $storeIds[$nextStore];
            $timeStart = microtime(true);
            $appEmulation = Mage::getSingleton('core/app_emulation');
            $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
            if ($result = Mage::getModel('googleshopping/googleshopping')->generateFeed($storeId, $timeStart)) {
                $html = '<a href="' . $result['url'] . '" target="_blank">' . $result['url'] . '</a><br/><small>Date: ' . $result['date'] . ' (cron) - Products: ' . $result['qty'] . ' - Time: ' . number_format((microtime(true) - $timeStart), 4) . '</small>';
                $config = new Mage_Core_Model_Config();
                $config->saveConfig('googleshopping/generate/feed_result', $html, 'stores', $storeId);
            }

            $config->saveConfig('googleshopping/generate/cron_next', ($nextStore + 1), 'default', 0);
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
        }
    }

}