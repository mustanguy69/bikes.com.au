<?php
/**
 * @category  Aligent
 * @package   ZipMoney_SDK
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class ZipMoney_Api
{
    protected $_oApiSettings;
    protected $_vMerchantId;
    protected $_vMerchantKey;

    /**
     * @param $vEnvironment
     * @param $vMerchantId
     * @param $vMerchantKey
     * @param null $oLogHandler
     */
    public function __construct($vEnvironment, $vMerchantId, $vMerchantKey, $oLogHandler = null)
    {
        /** @var ZipMoney_ApiSettings $apiSettings */
        $this->_oApiSettings = new ZipMoney_ApiSettings($vEnvironment);
        $this->_vMerchantId = $vMerchantId;
        $this->_vMerchantKey = $vMerchantKey;
    }

    /**
     * Add ZipMoney API keys to the request, if not existed
     *
     * @param $aRequestData
     * @return array
     */
    protected function _addApiKeysToRequest($aRequestData)
    {
        if (!$aRequestData) {
            $aRequestData = array();
        }
        if (is_array($aRequestData)) {
            if (!isset($aRequestData['merchant_id'])) {
                $aRequestData['merchant_id'] = $this->_vMerchantId;
            }
            if (!isset($aRequestData['merchant_key'])) {
                $aRequestData['merchant_key'] = $this->_vMerchantKey;
            }
        }
        return $aRequestData;
    }

    /**
     * Make http request
     *
     * @param $vJson
     * @param $vRequestUrl
     * @param int $iTimeout
     * @return Zend_Http_Response
     * @throws Zend_Http_Client_Exception
     */
    protected function request($vJson, $vRequestUrl, $iTimeout = 60)
    {
        $aHttpClientConfig = array('maxredirects' => 0, 'timeout' => $iTimeout);

        $oClient = new Zend_Http_Client($vRequestUrl, $aHttpClientConfig);
        if ($vJson != null) {
            $oClient->setRawData($vJson, 'application/json')->setMethod(Zend_Http_Client::POST);
            $oClient->setHeaders(array(
                'content-length' => strlen($vJson),
                'content-type' => 'application/json'));
        } else {
            $oClient->setMethod(Zend_Http_Client::GET);
        }

        $oResponse = $oClient->request();
        return $oResponse;
    }

    /**
     * Get content by url
     *
     * @param $vUrl
     * @param int $iTimeout
     * @return Zend_Http_Response
     * @throws Zend_Http_Client_Exception
     */
    public function getUrlContent($vUrl, $iTimeout = 60)
    {
        $oClient = new Zend_Http_Client($vUrl, array('timeout' => $iTimeout));
        $oResponse = $oClient->request();
        return $oResponse;
    }

    /**
     * Call ZipMoney API endpoint
     *
     * @param $vEndpoint
     * @param $vJson
     * @param int $iTimeout
     * @param null $vEnvironment
     * @return null|Zend_Http_Response
     * @throws Exception
     */
    public function callZipApi($vEndpoint, $vJson, $iTimeout = 60, $vEnvironment = null)
    {
        $aRequestData = json_decode($vJson);
        $oResponse = null;
        $aRequestData = $this->_addApiKeysToRequest($aRequestData);
        $vJson = json_encode($aRequestData);
        $vRequestUrl = $this->_oApiSettings->getUrl($vEndpoint, $vEnvironment);
        if (!$vRequestUrl) {
            $vMessage = 'Cannot get the endpoint url for type ' . $vEndpoint;
            throw new Exception($vMessage);
        }
        $oResponse = $this->request($vJson, $vRequestUrl, $iTimeout);
        return $oResponse;
    }

    public function getBaseUrl($vEnvironment = null)
    {
        return $this->_oApiSettings->getApiBaseUrl($vEnvironment);
    }

    public function getEndpointUrl($vType, $vEnvironment = null)
    {
        return $this->_oApiSettings->getUrl($vType, $vEnvironment);
    }
}