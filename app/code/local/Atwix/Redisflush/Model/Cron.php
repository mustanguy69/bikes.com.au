<?php
/**
 * Atwix
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 * @category    Atwix_Mod
 * @package     Atwix_Redisflush
 * @author      Atwix Core Team
 * @copyright   Copyright (c) 2014 Atwix (http://www.atwix.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 * file path
 * magento_root/app/code/local/Atwix/Redisflush/Model/Cron.php
 */
class Atwix_Redisflush_Model_Cron extends Mage_Core_Model_Abstract
{
    public function flushRedisCache()
    {
        try {
            $output = shell_exec('redis-cli flushall');
            Mage::log('Redis cache flush script ran with message. ' . $output, null, 'atwix_flushcache.log', true);
            Mage::getSingleton('adminhtml/session')->addSuccess('Redis cache flush script ran with message. ' . $output);
        } catch(Exception $e) {
            Mage::log($e->getMessage(), null, 'atwix_flushcache.log', true);
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }
}
