<?php

class Zipmoney_ZipmoneyPayment_Helper_Data extends Mage_Core_Helper_Abstract {

    const PAYMENT_METHOD_CODE   = 'zipmoneypayment';
    const CACHE_TAG             = 'zipmoney';
    const CACHE_LIFE_TIME       = 1800;

    public $assets_object;

    public function __construct() {
        $this->assets_object = new Varien_Object();
    }

    public function getAssets() {
        $satandard = Mage::getBaseUrl() . "media/zipmoney/standard.png";
        $promotion = Mage::getBaseUrl() . "media/zipmoney/promotion.png";

        $standard_frontside = Mage::getBaseUrl() . "media/zipmoney/standard-frontside.png";
        $standard_backside = Mage::getBaseUrl() . "media/zipmoney/standard-backside.png";
        $standard_pop_up = new Varien_Object();
        $standard_pop_up->setPage1($standard_frontside);
        $standard_pop_up->setPage2($standard_backside);

        $promotion_pop_up = new Varien_Object();
        $result = Mage::helper('zipmoneypayment')->setRawCallRequest(null, trim(Mage::getStoreConfig('payment/zipmoney_merchant_info/jsonpath')));
        $assets = $result->assets;
      
        $promotion_frontside = Mage::getBaseUrl() . "media/zipmoney/promotion-frontside.png";
        $promotion_backside = Mage::getBaseUrl() . "media/zipmoney/promotion-backside.png";

        $promotion_pop_up->setInterestFreeMonths($assets->promotion->interest_free_months);
        $promotion_pop_up->setTransactionLimitMin($assets->promotion->transaction_limit_min);

        $promotion_pop_up->setPage1($promotion_frontside);
        $promotion_pop_up->setPage2($promotion_backside);

        $this->assets_object->setStandardUrl($satandard);
        $this->assets_object->setPromotionUrl($promotion);
        $this->assets_object->setStandardInfobox($standard_pop_up);
        $this->assets_object->setPromotionInfobox($promotion_pop_up);
    }

    protected function updateOrderStatus($order_id, $status)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $order->setStatus($status);
        $order->save();
    }

    public function setRawCallRequest($request_data, $request_url) {
        $oLogging = Mage::helper('zipmoneypayment/logging');
        $flag_capture = Mage::getSingleton('core/session')->getTxnflagcapture();
        Mage::getSingleton('core/session')->unsTxnflagcapture();

        $flag_refund = Mage::getSingleton('core/session')->getTxnflagrefund();
        Mage::getSingleton('core/session')->unsTxnflagrefund();

        $oLogging->writeLog($this->__('Calling Endpoint: %s', $request_url), Zend_Log::DEBUG);
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_DEVELOPER_SETTINGS_TIMEOUT;
        $iTimeout = Mage::getStoreConfig($vPath);
        if (!$iTimeout) {
            $iTimeout = 60;     // default value
        }
        $httpClientConfig = array('timeout' => $iTimeout, 'maxredirects' => 0);
        $client = new Varien_Http_Client($request_url, $httpClientConfig);
        if ($request_data != null) {
            $json = json_encode($request_data);
            $client->setRawData($json, 'application/json')->setMethod(Varien_Http_Client::POST);
            $client->setHeaders(array(
                'content-length' => strlen($json),
                'content-type' => 'application/json'));
        } else {
            $client->setMethod(Varien_Http_Client::GET);
        }
        $order_id = isset($request_data['order_id']) ? $request_data['order_id'] : null;
        $t_start = $this->stime();
        try {
            $response = $client->request();
            $t_stime = $this->etime($t_start);
            Mage::helper('zipmoneypayment')->writelog("Time execute cURL(done):", $t_stime . "s\n");
        } catch (Exception $e) {
            if ($flag_capture == 1) {
                $this->updateOrderStatus($order_id, 'zip_authorised');
                $oLogging->writeLog($this->__('Set order %s status to zip_authorised due to exception when calling zipMoney endpoint.', $order_id), Zend_Log::DEBUG);
            }
            if ($flag_refund == 1) {
                $this->updateOrderStatus($order_id, 'processing');
                $oLogging->writeLog($this->__('Set order %s status to processing due to exception when calling zipMoney endpoint.', $order_id), Zend_Log::DEBUG);
            }

            $t_stime = $this->etime($t_start);
            Mage::helper('zipmoneypayment')->writelog("Time execute cURL(error):", $t_stime . "s\n");
            $oLogging->writeLog($this->__('Exception when calling zipMoney endpoint. %s', $e->getMessage()), Zend_Log::ERR);
            Mage::throwException($this->__('Gateway request error: %s', $e->getMessage()));
        }
        Mage::helper('zipmoneypayment')->writelog("Result cURL:", $response->getBody());
        /**
         * When the refund status is 'review' (and response is success),
         * don't change order status back and keep the status 'zip_pending_xxx'
         */
        if (!$response->isSuccessful()) {
            if ($flag_capture == 1) {
                $this->updateOrderStatus($order_id, 'zip_authorised');
                $oLogging->writeLog($this->__('Set order %s status to zip_authorised due to failure response from zipMoney.', $order_id), Zend_Log::DEBUG);
            }
            if ($flag_refund == 1) {
                $this->updateOrderStatus($order_id, 'processing');
                $oLogging->writeLog($this->__('Set order %s status to processing due to failure response from zipMoney.', $order_id), Zend_Log::DEBUG);
            }

            $oLogging->writeLog($this->__('Error cURL: %s', $response->getMessage()), Zend_Log::WARN);
            Mage::throwException($this->__('Gateway request error: %s', $response->getMessage()));
        }
        $result = new Varien_Object();
       
        $result->addData((array)json_decode($response->getBody()));
        return $result;
    }

    function stime() {
        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        return $start = $time;
    }

    function etime($start) {
        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        $finish = $time;
        $total_time = round(($finish - $start), 4);
        return $total_time;
    }

    public function setRawCallRequestGet($request_url) {
        $client = new Varien_Http_Client();
        $client->setUri($request_url);

        $response = $client->request();
        if (!$response->isSuccessful()) {
            Mage::throwException($this->__('Gateway request error: %s', $response->getMessage()));
        }
        $result = new Varien_Object();
        $result->addData(json_decode($response->getBody()));
        return $result;
    }

    public function writelog($tag, $log) {
        if (Mage::getStoreConfig('payment/zipmoney_developer_settings/logging_enabled') == 1) {
            $file = Mage::getStoreConfig('payment/zipmoney_developer_settings/log_file');
            $file_log = $file == "" ? "zipMoney.log" : $file;
            Mage::log($tag, null, $file_log);
            if ($this->is_json($log)) {
                Mage::log($log, null, $file_log);
                Mage::log($this->json_decode_nice($log), null, $file_log);
            } else {
                Mage::log($log, null, $file_log);
            }
        }
    }

    public function json_decode_nice($json, $assoc = FALSE) {
        $json = str_replace(array("\n", "\r"), "", $json);
        $json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/', '$1"$3":', $json);
        return json_decode($json, $assoc);
    }

    public function is_json($str) {
        if($str==""||$str==null||is_array($str))
                return false;
        return json_decode($str) != null;
    }

    public function setRawCallRequestxx($request_data, $request_url) {
        $httpClientConfig = array('maxredirects' => 0);
        $client = new Varien_Http_Client($request_url, $httpClientConfig);
        $client->setRawData($request_data)->setMethod(Varien_Http_Client::POST);
        $response = $client->request();
        if (!$response->isSuccessful()) {
            Mage::throwException($this->_getHelper()->__('Gateway request error: %s', $response->getMessage()));
        }
        $result = new Varien_Object();
        $result->addData($this->deformatNvp('&', $response->getBody()));
        return $result;
    }

    /**
     * Call Zip Api to request configuration
     * @return mixed|null
     * @throws Exception
     */
    public function requestConfigurationFromZipMoney()
    {
        $oLogging = Mage::helper('zipmoneypayment/logging');
        $oApiHelper = Mage::helper('zipmoneypayment/api');

        /**
         * raw data:
         */
        $aRawData = array();
        $vEndpoint = ZipMoney_ApiSettings::API_TYPE_MERCHANT_SETTINGS;
        $aRequestData = $oApiHelper->prepareDataForZipRequest($vEndpoint, $aRawData);
        try {
            $oLogging->writeLog(json_encode($aRequestData), Zend_Log::DEBUG);
            $oResponse = $oApiHelper->callZipApi($vEndpoint, $aRequestData);
        } catch (Exception $e) {
            throw $e;
        }
        return $oApiHelper->getResponseData($oResponse);
    }

    protected function _refreshCache()
    {
        $oLogging = Mage::helper('zipmoneypayment/logging');
        try {
            Mage::app()->getCacheInstance()->cleanType('config');
            Mage::app()->getCacheInstance()->cleanType('layout');
            Mage::app()->getCacheInstance()->cleanType('block_html');
            $oLogging->writeLog(Mage::helper('zipmoneypayment')->__('Cleaned cache for config, layout and block_html.'), Zend_Log::INFO);
        } catch (Exception $e) {
            $oLogging->writeLog($this->__('An error occurred during refreshing cache. ') . $e->getMessage(), Zend_Log::ERR);
            $oLogging->writeLog($e->getTraceAsString(), Zend_Log::DEBUG);
        }
    }

    /**
     * Call Zip Api to send Magento base_url back
     *
     * @param $vMerchantId
     * @param $vMerchantKey
     * @return string
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function sendBaseUrlToZipMoney($vMerchantId, $vMerchantKey)
    {
        $oLogging = Mage::helper('zipmoneypayment/logging');
        $oApiHelper = Mage::helper('zipmoneypayment/api');
        /**
         * raw data:
         *  base_url
         */
        $aMatched = Mage::getSingleton('zipmoneypayment/storeScope')->getMatchedScopes($vMerchantId);
        if (!is_array($aMatched)) {
            return null;
        }
        $vBaseUrl = null;
        $vScope = '';
        $iScopeId = 0;

        /**
         * Only send the base url of first matched website
         */
        foreach ($aMatched as $aScope) {
            if (!is_array($aScope)) {
                continue;
            }
            $vScope = isset($aScope['scope']) ? $aScope['scope'] : 'default';
            $iScopeId = isset($aScope['scope_id']) ? $aScope['scope_id'] : 0;
            $oDefaultStore = Mage::app()->getWebsite($iScopeId)->getDefaultStore();
            $vBaseUrl = $oDefaultStore->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, true);
            break;
        }

        if (!$vBaseUrl) {
            $oLogging->writeLog(Mage::helper('zipmoneypayment')->__('Can not get the secure base url for the website (scope: $s, id: %s)', $vScope, $iScopeId), Zend_Log::WARN);
            return null;
        }
        $oLogging->writeLog(Mage::helper('zipmoneypayment')->__('The secure base url of default store (scope: $s, id: %s) is %s', $vScope, $iScopeId, $vBaseUrl), Zend_Log::DEBUG);

        $aRawData = array(
            'base_url'      => $vBaseUrl,
        );
        $vEndpoint = ZipMoney_ApiSettings::API_TYPE_MERCHANT_CONFIGURE;
        $aRequestData = $oApiHelper->prepareDataForZipRequest($vEndpoint, $aRawData);
        try {
            $oResponse = $oApiHelper->callZipApi($vEndpoint, $aRequestData);
        } catch (Exception $e) {
            $oLogging->writeLog(json_encode($aRequestData), Zend_Log::DEBUG);
            throw $e;
        }
        return $vBaseUrl;
    }

    /**
     * Call Zip Api to request configuration data from zipMoney, and update.
     *
     * @return $this
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function requestConfigAndUpdate()
    {
        $oLogging = Mage::helper('zipmoneypayment/logging');
        $oApiHelper = Mage::helper('zipmoneypayment/api');

        // get by current scope
        $vMerchantId = $oApiHelper->getMerchantId(true);
        $vMerchantKey = $oApiHelper->getMerchantKey(true);

        if(!$vMerchantId || !$vMerchantKey) {   // both merchant_id and merchant_key should have been set
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $this->__('Empty Merchant Id or Merchant Key.'));
        }
        /**
         * request config data from zipMoney
         * and set firstTime flag false
         */
        $aResponseData = $this->requestConfigurationFromZipMoney();

        if(!$aResponseData) {
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $this->__('Failed to get data from zipMoney.'));
        }
        $vResMerchantId = isset($aResponseData['merchant_id']) ? $aResponseData['merchant_id'] : null;
        $vResMerchantKey = isset($aResponseData['merchant_key']) ? $aResponseData['merchant_key'] : null;
        if($vResMerchantId && $vResMerchantKey && $vResMerchantId == $vMerchantId && $vResMerchantKey == $vMerchantKey) {
            Mage::getModel('zipmoneypayment/config')->saveConfigByMatchedScopes(
                Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_CHECKOUT_TITLE,
                isset($aResponseData['Settings']['checkout_title']) ? $aResponseData['Settings']['checkout_title'] : '',
                $vMerchantId);
            Mage::getModel('zipmoneypayment/config')->saveConfigByMatchedScopes(
                Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_CHECKOUT_DETAIL_MESSAGE,
                isset($aResponseData['Settings']['checkout_description']) ? $aResponseData['Settings']['checkout_description'] : '',
                $vMerchantId);
            Mage::getModel('zipmoneypayment/config')->saveConfigByMatchedScopes(
                Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_DEVELOPER_SETTINGS_LOGGING_ENABLED,
                (isset($aResponseData['Settings']['log_enabled']) && $aResponseData['Settings']['log_enabled']) ? '1' : '0',
                $vMerchantId);
            Mage::getModel('zipmoneypayment/config')->saveConfigByMatchedScopes(
                Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_DEVELOPER_SETTINGS_LOG_LEVEL,
                isset($aResponseData['Settings']['log_level']) ? $aResponseData['Settings']['log_level'] : '6',
                $vMerchantId);
            Mage::getModel('zipmoneypayment/config')->saveConfigByMatchedScopes(
                Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_DEVELOPER_SETTINGS_TIMEOUT,
                isset($aResponseData['Settings']['timeout']) ? $aResponseData['Settings']['timeout'] : '60',
                $vMerchantId);
            Mage::getModel('zipmoneypayment/config')->saveConfigByMatchedScopes(
                Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_TITLE,
                isset($aResponseData['Settings']['title']) ? $aResponseData['Settings']['title'] : '',
                $vMerchantId);
            Mage::getModel('zipmoneypayment/config')->saveConfigByMatchedScopes(
                Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_CAPTURE_METHOD,
                isset($aResponseData['Settings']['capture_method']) ? $aResponseData['Settings']['capture_method'] : '0',
                $vMerchantId);
            Mage::getModel('zipmoneypayment/config')->saveConfigByMatchedScopes(
                Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_ASSETS,
                isset($aResponseData['assets']) ? serialize($aResponseData['assets']) : '',
                $vMerchantId);
            Mage::getModel('zipmoneypayment/config')->saveConfigByMatchedScopes(
                Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_ASSET_VALUES,
                isset($aResponseData['asset_values']) ? serialize($aResponseData['asset_values']) : '');
            Mage::getModel('core/config')->saveConfig(
                Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_PRODUCT,
                isset($aResponseData['Settings']['product']) ? $aResponseData['Settings']['product'] : '');
            Mage::getModel('core/config')->saveConfig(
                Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_PUBLIC_KEY, 
                isset($aResponseData['Settings']['merchant_public_key']) ? $aResponseData['Settings']['merchant_public_key'] : '');
            
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('zipMoney configuration has been updated.'));


            if (isset($aResponseData['Settings']['capture_method'])) {
                $iMethod = $aResponseData['Settings']['capture_method'];
                if ($iMethod === 0 || $iMethod === '0') {
                    $vPaymentMethod = Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE;
                } else {
                    $vPaymentMethod = Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE;
                }
                Mage::getModel('zipmoneypayment/config')->saveConfigByMatchedScopes(
                    Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_PAYMENT_METHOD,
                    $vPaymentMethod,
                    $vMerchantId);
            }
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('zipMoney configuration has been updated.'));

            $vBaseUrl = $this->sendBaseUrlToZipMoney($vMerchantId, $vMerchantKey);
            if ($vBaseUrl) {
                $vMessage = Mage::helper('zipmoneypayment')->__('Secure base URL (%s) has been sent to zipMoney.', $vBaseUrl);
                $oLogging->writeLog($vMessage, Zend_Log::NOTICE);
                Mage::getSingleton('adminhtml/session')->addSuccess($vMessage);
            }
            $this->_refreshCache();
        }
        return $this;
    }

    /**
     * Get html by URL (for Terms and Conditions modal)
     * @param $vUrl
     * @param bool $bAppendRandomQueryParameter
     * @return false|mixed|string
     */
    public function getUrlContent($vUrl, $bAppendRandomQueryParameter = true)
    {
        $oLogging = Mage::helper('zipmoneypayment/logging');
        $vCacheId = md5($vUrl);
        $oCache = Mage::app()->getCache();
        $vBannerContents = $oCache->load($vCacheId);
        if (!$vBannerContents) {
            try {
                $oApiHelper = Mage::helper('zipmoneypayment/api');
                if ($bAppendRandomQueryParameter) {
                    $vSeparator = (strpos($vUrl, '?') === false) ? '?' : '&';
                    $vUrl .= $vSeparator . 'random=' . mt_rand();
                }
                $oCacheInstance = Mage::app()->getCacheInstance();
                $aTags = array(self::CACHE_TAG);
                $aTags = array_merge($oCacheInstance->getTagsByType('layout'), $aTags);
                $vBannerContents = $oApiHelper->getApi()->getUrlContent($vUrl)->getBody();
            } catch (Exception $e) {
                $oLogging->writeLog($this->__('Failed to load content of url(' . $vUrl . '). ' . $e->getMessage()), Zend_Log::WARN);
                $oLogging->writeLog($e->getTraceAsString(), Zend_Log::DEBUG);
                return null;
            }
            try {
                $oCache->save($vBannerContents, $vCacheId, $aTags, self::CACHE_LIFE_TIME);
            } catch (Exception $e) {
                $oLogging->writeLog($this->__('Failed to cache content of url(' . $vUrl . '). ' . $e->getMessage()), Zend_Log::WARN);
                $oLogging->writeLog($e->getTraceAsString(), Zend_Log::DEBUG);
            }
        }
        return $vBannerContents;
    }

    /**
     * Check and update zipMoney API keys' hash
     *
     * @return bool
     */
    public function refreshApiKeysHash()
    {
        $oApiHelper = Mage::helper('zipmoneypayment/api');
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_HASH;
        $vCurrentHash = Mage::getModel('zipmoneypayment/config')->getConfigByCurrentScope($vPath);
        $vMerchantId = $oApiHelper->getMerchantId(true);
        $vMerchantKey = $oApiHelper->getMerchantKey(true);
        $vUpdatedHash = md5(serialize(array($vMerchantId, $vMerchantKey)));
        if ($vCurrentHash != $vUpdatedHash) {
            Mage::getModel('zipmoneypayment/config')->saveConfigByMatchedScopes($vPath, $vUpdatedHash, $vMerchantId);
            return true;
        }
        return false;
    }

    /**
     * get url of current store
     *
     * @param $vRoute
     * @param $aParam
     * @return string
     */
    public function getCurrentStoreUrl($vRoute, $aParam)
    {
        $iStoreId = Mage::getSingleton('zipmoneypayment/storeScope')->getStoreId();
        if ($iStoreId !== null) {
            $oStore = Mage::app()->getStore($iStoreId);
            $vUrl = $oStore->getUrl($vRoute, $aParam);
        } else {
            $vUrl = Mage::getUrl($vRoute, $aParam);
        }
        return $vUrl;
    }

    public function getErrorUrl($bIsSecure = false)
    {
        $vUrl = $this->getCurrentStoreUrl('zipmoneypayment/Express/error', array('_secure' => $bIsSecure));  // Zip can append param 'code' to it. e.g. ?code=400
        return $vUrl;
    }

    /**
     * Get return urls
     * @param $vType
     * @param Mage_Sales_Model_Quote $oQuote
     * @return string
     */
    public function getReturnUrl($vType, Mage_Sales_Model_Quote $oQuote)
    {
        $vReturnUrl = '';
        if (!$oQuote) {
            return '';
        }
        $bIsSecure = true;
        $iQuoteId = $oQuote->getId();
        $vToken = $oQuote->getZipmoneyToken();
        switch ($vType) {
            case 'success_url':
                /**
                 * todo: can use Mage::getUrl ? currentStore has been already set
                 */
                $vUrl = $this->getCurrentStoreUrl('zipmoneypayment/Express/success', array('_secure' => $bIsSecure));
                $vParam = 'quote_id=' . $iQuoteId . '&token=' . $vToken;
                $vReturnUrl = $vUrl . (parse_url($vUrl, PHP_URL_QUERY) ? '&' : '?') . $vParam;
                break;
            case 'cart_url':
                $vReturnUrl = $this->getCurrentStoreUrl('checkout/cart', array('_secure' => $bIsSecure));
                break;
            case 'refer_url':
                $vUrl = $this->getCurrentStoreUrl('zipmoneypayment/Express/underReview', array('_secure' => $bIsSecure));
                $vParam = 'quote_id=' . $iQuoteId . '&token=' . $vToken;
                $vReturnUrl = $vUrl . (parse_url($vUrl, PHP_URL_QUERY) ? '&' : '?') . $vParam;
                break;
            case 'cancel_url':
                $vUrl = $this->getCurrentStoreUrl('zipmoneypayment/Quote/cancelQuote', array('_secure' => $bIsSecure));
                $vParam = 'quote_id=' . $iQuoteId . '&token=' . $vToken;
                $vReturnUrl = $vUrl . (parse_url($vUrl, PHP_URL_QUERY) ? '&' : '?') . $vParam;
                break;
            case 'error_url':
                $vReturnUrl = $this->getErrorUrl($bIsSecure);
                break;
            case 'decline_url':
                $vUrl = $this->getCurrentStoreUrl('zipmoneypayment/Quote/decline', array('_secure' => $bIsSecure));
                $vParam = 'quote_id=' . $iQuoteId . '&token=' . $vToken;
                $vReturnUrl = $vUrl . (parse_url($vUrl, PHP_URL_QUERY) ? '&' : '?') . $vParam;
                break;
            default:
                // never gets into here
                break;
        }
        return $vReturnUrl;
    }

    /**
     * @param $vTitle
     * @param $vDescription
     * @param int $iSeverity
     * @throws Exception
     */
    public function logAdminMessage($vTitle, $vDescription, $iSeverity = Mage_AdminNotification_Model_Inbox::SEVERITY_NOTICE) {
        $oMessage = Mage::getModel('adminnotification/inbox');
        $oMessage->setSeverity($iSeverity);
        $oMessage->setDateAdded(date("c", time()));
        $oMessage->setTitle($vTitle);
        $oMessage->setDescription($vDescription);
        $oMessage->save();
    }

    /**
     * Get the config of isIframeFlow
     *
     * @return bool
     */
    public function isIframeCheckoutEnabled()
    {
        if (!Mage::getStoreConfig(Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_ACTIVE)) {
            return 0;
        }
        if (!Mage::getStoreConfig(Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_EXPRESS_CHECKOUT_EXPRESS_CHECKOUT_ACTIVE)) {
            return 0;
        }
        if (Mage::getStoreConfig(Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_EXPRESS_CHECKOUT_IFRAME_CHECKOUT_ACTIVE)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Get zipmoney iframe checkout lib url
     *
     * @param null $vType   reserved
     * @return string
     */
    public function getIframeLibUrl($vType = null)
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_ENVIRONMENT;
        $vEnv = Mage::getStoreConfig($vPath);      // environment: sandbox / production

        if ($vEnv == 'sandbox') {
            // test/sandbox
            $vIframeUrl = Zipmoney_ZipmoneyPayment_Model_Config::IFRAME_API_URL_TEST;
        } else {
            // live/production
            $vIframeUrl = Zipmoney_ZipmoneyPayment_Model_Config::IFRAME_API_URL_LIVE;
        }

        /**
         * todo: only for test on development EC2
         */
//        $vIframeUrl = Zipmoney_ZipmoneyPayment_Model_Config::IFRAME_API_URL_DEVELOPMENT;

        $vScript = '<script src="' . $vIframeUrl . '"></script>';

        return $vScript;
    }

    /**
     * Check if current setting of payment action is 'authorize_capture'
     *
     * @return bool
     */
    public function isCaptureMethod()
    {
        $vPath = Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_CAPTURE_METHOD;
        $iMethod = Mage::getStoreConfig($vPath);
        if ($iMethod === 0 || $iMethod === '0') {
            return true;
        }
        return false;
    }

    public function getRepayments($amount)
    {   
        if(!$amount) 
            return false;
    
        $min_monthly_payment_formatted = Mage::helper("zipmoneypayment/widget")->replaceHtmlToken("{RepaymentMin$}",null);
        
        if(!$min_monthly_payment_formatted) return false;

        $min_monthly_payment = str_replace("$","",$min_monthly_payment_formatted);

        if(!is_numeric($min_monthly_payment)) return false;

        $repayment_months  = array(6,3,2);
        $repayment_amt     = $amount;

        foreach($repayment_months as $mth){
            
            if($amount >= $mth * $min_monthly_payment){
              $repayment_amt =  $amount / $mth;
              break;
            }
        }

        $repayment_amt_formatted  = Mage::helper('core')->currency($repayment_amt , true, false);
        
        $zipPayLogo =  "<img src='".Mage::getDesign()->getSkinUrl("zipmoney/images/zippay-logo.png")."' title='zipPay' style='vertical-align:sub;display:inline;' />";

        if($repayment_amt == $amount)
            return $this->__("or as low as <span class='regular-price'> <span  class='price zip-price'>%s/mth</span></span> with %s",$min_monthly_payment_formatted,$zipPayLogo);
        else
            return $this->__("or <span class='regular-price'> <span  class='price zip-price'> %s x %s/mth </span></span>  with %s",$mth, $repayment_amt_formatted,$zipPayLogo);
    }

    
}
