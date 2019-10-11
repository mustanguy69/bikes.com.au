<?php
/**
 * @category  Aligent
 * @package   zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Block_Express_Error extends Zipmoney_ZipmoneyPayment_Block_Abstract
{
    public function getHeadText()
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_HANDLING_SERVICE_UNAVAILABLE_HEADING;
        $vText = $this->__(Mage::getStoreConfig($vPath));
        if (!$vText) {
            $vText = $this->__('The service is unavailable');
        }
        return $vText;
    }

    public function getBodyText()
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_HANDLING_SERVICE_UNAVAILABLE_BODY;
        $vText = $this->__(Mage::getStoreConfig($vPath));
        if (!$vText) {
            $vText = $this->__('Unfortunately the zipMoney - Buy now, Pay later service is currently unavailable. We will be right back and apologies for the inconvenience.');
        }
        return $vText;
    }

    public function getErrorTypeText()
    {
        try {
            $iCode = (int)$this->getRequest()->getParam('code');
        } catch (Exception $e) {
            $iCode = 0;
        }
        switch($iCode)
        {
            case 0:
                $vText = $this->__('General Error');
                break;
            case 400:
                $vText = $this->__('400 Bad Request');
                break;
            case 401:
                $vText = $this->__('401 Unauthorized');
                break;
            case 403:
                $vText = $this->__('403 Forbidden');
                break;
            case 404:
                $vText = $this->__('404 Not Found');
                break;
            case 409:
                $vText = $this->__('409 Conflict');
                break;
            default:
                $vText = $this->getRequest()->getParam('code') . $this->__(' General Error');
                break;
        }
        return $vText;
    }
}