<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Helper_Logging extends Mage_Core_Helper_Abstract
{
    const LOG_FILE = 'zipMoney-payment.log';

    public function isLoggingEnabled($iStoreId = null)
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_DEVELOPER_SETTINGS_LOGGING_ENABLED;
        if ($iStoreId !== null) {
            $iEnabled = Mage::app()->getStore($iStoreId)->getConfig($vPath);
        } else {
            $iEnabled = Mage::getModel('zipmoneypayment/config')->getConfigByCurrentScope($vPath);
        }
        return $iEnabled ? true : false;
    }

    public function getConfigLoggingLevel($iStoreId = null)
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_DEVELOPER_SETTINGS_LOG_LEVEL;
        if ($iStoreId !== null) {
            $iConfigLevel = Mage::app()->getStore($iStoreId)->getConfig($vPath);
        } else {
            $iConfigLevel = Mage::getModel('zipmoneypayment/config')->getConfigByCurrentScope($vPath);
        }
        if ($iConfigLevel === null) {
            $iConfigLevel = Zend_log::INFO;
        }
        return $iConfigLevel;
    }

    public function getLogFile($iStoreId = null)
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_DEVELOPER_SETTINGS_LOG_FILE;
        if ($iStoreId !== null) {
            $vFileName = Mage::app()->getStore($iStoreId)->getConfig($vPath);
        } else {
            $vFileName = Mage::getModel('zipmoneypayment/config')->getConfigByCurrentScope($vPath);
        }
        if (!$vFileName) {
            $vFileName = self::LOG_FILE;
        }
        return $vFileName;
    }

    /**
     * Write log into log file with log_level
     *
     * @param $vMessage
     * @param int $iLevel
     * @param null $iStoreId
     */
    public function writeLog($vMessage, $iLevel = Zend_log::INFO, $iStoreId = null)
    {
        if (!$this->isLoggingEnabled($iStoreId)) {
            return;
        }
        $iConfigLevel = $this->getConfigLoggingLevel($iStoreId);

        // errors are always logged.
        if ($iConfigLevel < 3) {
            $iConfigLevel = Zend_log::INFO; // default log level
        }
        $vFileName = $this->getLogFile($iStoreId);
        if ($iLevel > $iConfigLevel) {
            return;
        }
        Mage::log($vMessage, $iLevel, $vFileName);
    }
}