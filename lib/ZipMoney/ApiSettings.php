<?php
/**
 * @category  Aligent
 * @package   ZipMoney_SDK
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class ZipMoney_ApiSettings
{
    const ENVIRONMENT_TEST                          = 'sandbox';
    const ENVIRONMENT_LIVE                          = 'production';

    const ENV_TEST_BASE_URL                         = 'https://api.sandbox.zipmoney.com.au/v1/';
    const ENV_LIVE_BASE_URL                         = 'https://api.zipmoney.com.au/v1/';

    const ENDPOINT_SETTINGS                         = 'settings';
    const ENDPOINT_CONFIGURE                        = 'configure';
    const ENDPOINT_QUOTE                            = 'quote';
    const ENDPOINT_ORDER_SHIPPING_ADDRESS           = 'order';
    const ENDPOINT_ORDER_CANCEL                     = 'cancel';
    const ENDPOINT_ORDER_REFUND                     = 'refund';
    const ENDPOINT_CHECKOUT                         = 'checkout';
    const ENDPOINT_HEART_BEAT                       = 'Heartbeat';

    const API_TYPE_MERCHANT_SETTINGS                = 'merchant_settings';
    const API_TYPE_MERCHANT_CONFIGURE               = 'merchant_configure';
    const API_TYPE_QUOTE_QUOTE                      = 'quote_quote';
    const API_TYPE_ORDER_SHIPPING_ADDRESS           = 'order_shipping_address';
    const API_TYPE_ORDER_CANCEL                     = 'order_cancel';
    const API_TYPE_ORDER_REFUND                     = 'refund';
    const API_TYPE_CHECKOUT                         = 'checkout';
    const API_TYPE_HEART_BEAT                       = 'heartbeat';

    protected $_vEnv;

    public function __construct($vEnv = self::ENVIRONMENT_LIVE)
    {
        $this->_vEnv = $vEnv;
    }

    /**
     * Get ZipMoney API endpoint url by type and environment
     *
     * @param $vType
     * @param null $vEnvironment
     * @return null|string
     */
    public function getUrl($vType, $vEnvironment = null)
    {
        $vUrl = null;
        $vBaseUrl = $this->getApiBaseUrl($vEnvironment);
        $vEndPoint = '';

        switch ($vType) {
            case self::API_TYPE_MERCHANT_SETTINGS:
                $vEndPoint = self::ENDPOINT_SETTINGS;
                break;
            case self::API_TYPE_MERCHANT_CONFIGURE:
                $vEndPoint = self::ENDPOINT_CONFIGURE;
                break;
            case self::API_TYPE_QUOTE_QUOTE:
                $vEndPoint = self::ENDPOINT_QUOTE;
                break;
            case self::API_TYPE_ORDER_SHIPPING_ADDRESS:
                $vEndPoint = self::ENDPOINT_ORDER_SHIPPING_ADDRESS;
                break;
            case self::API_TYPE_ORDER_CANCEL:
                $vEndPoint = self::ENDPOINT_ORDER_CANCEL;
                break;
            case self::API_TYPE_ORDER_REFUND:
                $vEndPoint = self::ENDPOINT_ORDER_REFUND;
                break;
            case self::API_TYPE_CHECKOUT:
                $vEndPoint = self::ENDPOINT_CHECKOUT;
                break;
            case self::API_TYPE_HEART_BEAT:
                $vEndPoint = self::ENDPOINT_HEART_BEAT;
                break;
            default:
                break;
        }

        if ($vBaseUrl && $vEndPoint) {
            $vUrl = $vBaseUrl . ltrim($vEndPoint, '/');
        }

        return $vUrl;
    }

    protected function _isEnvLive($vEnvironment = null)
    {
        if ($vEnvironment) {
            return ($vEnvironment == self::ENVIRONMENT_LIVE);
        } else {
            return ($this->_vEnv == self::ENVIRONMENT_LIVE);
        }
    }

    protected function _isEnvTest($vEnvironment = null)
    {
        if ($vEnvironment) {
            return ($vEnvironment == self::ENVIRONMENT_TEST);
        } else {
            return ($this->_vEnv == self::ENVIRONMENT_TEST);
        }
    }

    public function getApiBaseUrl($vEnvironment = null)
    {
        $vBaseUrl = '';
        if($this->_isEnvLive($vEnvironment)) {
            $vBaseUrl = self::ENV_LIVE_BASE_URL;
        } else if($this->_isEnvTest($vEnvironment)) {
            $vBaseUrl = self::ENV_TEST_BASE_URL;
        }
        return $vBaseUrl;
    }
}
