<?php
/**
 *
 *
 * @category  Aligent
 * @package   Zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */


/**
 * Class Zipmoney_ZipmoneyPayment_Test_Helper_Data
 * @loadSharedFixture scope.yaml
 */
class Zipmoney_ZipmoneyPayment_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case
{
    /** @var Zipmoney_ZipmoneyPayment_Helper_Data $helper*/
    protected $helper;
    protected $responseJson;

    protected function setUp()
    {
        parent::setUp();
        $this->_mockRequestConfigurationFromZipMoney();

        $this->_mockSessionCookie('customer/session');
        $this->_mockSessionCookie('admin/session');
        $this->_mockSessionCookie('adminhtml/session');
        $this->_mockSessionCookie('core/session');
        $this->_mockSessionCookie('checkout/session');

        $this->helper = Mage::helper('zipmoneypayment');

        $this->_initApiKeys();
    }

    protected function _mockSessionCookie($vSessionName)
    {
        $sessionMock = $this->getModelMock($vSessionName, array('init'));
        $sessionMock->expects($this->any())
            ->method('init')
            ->will($this->returnSelf());

        $this->replaceByMock('singleton', $vSessionName, $sessionMock);
        $this->replaceByMock('model', $vSessionName, $sessionMock);
    }

    protected function _mockRequestConfigurationFromZipMoney()
    {
        $zipmoneyHelperMock = $this->getHelperMock('zipmoneypayment/data', array('requestConfigurationFromZipMoney', 'sendBaseUrlToZipMoney'));
        $zipmoneyHelperMock->expects($this->any())
            ->method('requestConfigurationFromZipMoney')
            ->will($this->returnValue($this->_zipMoneyApiMockRequestConfigurationFromZipMoney(null, null)));
        $zipmoneyHelperMock->expects($this->any())
            ->method('sendBaseUrlToZipMoney')
            ->will($this->returnValue(null));
        $this->replaceByMock('helper', 'zipmoneypayment', $zipmoneyHelperMock);
    }

    public function testHelperIsZipmoney_ZipmoneyPayment_Helper_Data() {
        $this->assertInstanceOf('Zipmoney_ZipmoneyPayment_Helper_Data', $this->helper);
    }

    /**
     * Initialise api keys for websites
     */
    protected function _initApiKeys()
    {
        /**
         * zipsite0 and zipsite1 are sharing
         *      merchant_id: 65
         *      merchant_key: MEoi8gmJ5ZOgSXVg8imSxzu2jtnQpNvg1hIWDdUQDfI=
         *
         * zipsite2
         *      merchant_id: 64
         *      merchant_key: iTKnmy36rxn61siTYD4sZMPB5UY162T27WU3He0BQzU=
         */
        Mage::getModel('core/config')->saveConfig(Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_ID,
            '65', 'websites', 100);
        Mage::getModel('core/config')->saveConfig(Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_KEY,
            'MEoi8gmJ5ZOgSXVg8imSxzu2jtnQpNvg1hIWDdUQDfI=', 'websites', 100);
        Mage::getModel('core/config')->saveConfig(Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_ID,
            '65', 'websites', 101);
        Mage::getModel('core/config')->saveConfig(Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_KEY,
            'MEoi8gmJ5ZOgSXVg8imSxzu2jtnQpNvg1hIWDdUQDfI=', 'websites', 101);
        Mage::getModel('core/config')->saveConfig(Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_ID,
            '64', 'websites', 102);
        Mage::getModel('core/config')->saveConfig(Zipmoney_ZipmoneyPayment_Model_Config::PAYMENT_ZIPMONEY_PAYMENT_KEY,
            'iTKnmy36rxn61siTYD4sZMPB5UY162T27WU3He0BQzU=', 'websites', 102);
    }

    /**
     * reset storeScope singleton
     */
    protected function _resetScopeSingleton()
    {
        Mage::getSingleton('zipmoneypayment/storeScope')->setMerchantId(null);
        Mage::getSingleton('zipmoneypayment/storeScope')->setMerchantKey(null);
        Mage::getSingleton('zipmoneypayment/storeScope')->setScope(null);
        Mage::getSingleton('zipmoneypayment/storeScope')->setScopeId(null);
        Mage::getSingleton('zipmoneypayment/storeScope')->setStoreId(null);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testRequestConfigAndUpdate($iStoreId, $responseJson, $merchantId, $merchantKey, $assets, $assetValues, $settings)
    {
        $this->responseJson = $responseJson;

        // reset the storeScope singleton at the beginning of the request
        $this->_resetScopeSingleton();

        Mage::app()->setCurrentStore($iStoreId);

        // when 'configuration_updated' notification comes, merchant_id and merchant_key will be set in the storeScope singleton
        Mage::getSingleton('zipmoneypayment/storeScope')->setMerchantId($merchantId);
        Mage::getSingleton('zipmoneypayment/storeScope')->setMerchantKey($merchantKey);
        Mage::app()->getStore()->resetConfig();

        $this->_mockRequestConfigurationFromZipMoney();
        $this->helper = Mage::helper('zipmoneypayment');
        $this->helper->requestConfigAndUpdate();

        Mage::app()->getStore()->resetConfig();

        $dbMerchantId = Mage::getStoreConfig('payment/zipmoneypayment/id');
        $dbMerchantKey = Mage::getStoreConfig('payment/zipmoneypayment/key');
        $dbAssets = Mage::getStoreConfig('payment/zipmoneypayment/assets');
        $dbAssetValues = Mage::getStoreConfig('payment/zipmoneypayment/asset_values');

        $dbSettingsCaptureMethod = Mage::getStoreConfig('payment/zipmoneypayment/capture_method');
        $dbSettingsLogEnabled = Mage::getStoreConfig('payment/zipmoney_developer_settings/logging_enabled');
        $dbSettingsLogLevel = Mage::getStoreConfig('payment/zipmoney_developer_settings/log_level');
        $dbSettingsTimeout = Mage::getStoreConfig('payment/zipmoney_developer_settings/timeout');
        $dbSettingsTitle = Mage::getStoreConfig('payment/zipmoneypayment/title');
        $dbSettingsCheckoutTitle = Mage::getStoreConfig('payment/zipmoney_checkout/title');
        $dbSettingsCheckoutDes = Mage::getStoreConfig('payment/zipmoney_checkout/detailmessage');

        $settingsCaptureMethod = isset($settings['capture_method']) ? $settings['capture_method'] : null;
        $settingsLogEnabled = isset($settings['log_enabled']) ? ($settings['log_enabled']? 1 : 0) : null;
        $settingsLogLevel = isset($settings['log_level']) ? $settings['log_level'] : null;
        $settingsTimeout = isset($settings['timeout']) ? $settings['timeout'] : null;
        $settingsTitle = isset($settings['title']) ? $settings['title'] : null;
        $settingsCheckoutTitle = isset($settings['checkout_title']) ? $settings['checkout_title'] : null;
        $settingsCheckoutDes = isset($settings['checkout_description']) ? $settings['checkout_description'] : null;

        $this->assertEquals($merchantId, $dbMerchantId, 'Expecting $merchantId: '.$merchantId.', actually: '.$dbMerchantId);
        $this->assertEquals($merchantKey, $dbMerchantKey, 'Expecting $merchantKey: '.$merchantKey.', actually: '.$dbMerchantKey);
        $this->assertEquals($assets, $dbAssets, 'Expecting $assets: '.$assets.', actually: '.$dbAssets);
        $this->assertEquals($assetValues, $dbAssetValues, 'Expecting $assetValues: '.$assetValues.', actually: '.$dbAssetValues);

        $this->assertEquals($settingsCaptureMethod, $dbSettingsCaptureMethod, 'Expecting $settingsCaptureMethod: '.$settingsCaptureMethod.', actually: '.$dbSettingsCaptureMethod);
        $this->assertEquals($settingsLogEnabled, $dbSettingsLogEnabled, 'Expecting $settingsLogEnabled: '.$settingsLogEnabled.', actually: '.$dbSettingsLogEnabled);
        $this->assertEquals($settingsLogLevel, $dbSettingsLogLevel, 'Expecting $settingsLogLevel: '.$settingsLogLevel.', actually: '.$dbSettingsLogLevel);
        $this->assertEquals($settingsTimeout, $dbSettingsTimeout, 'Expecting $settingsTimeout: '.$settingsTimeout.', actually: '.$dbSettingsTimeout);
        $this->assertEquals($settingsTitle, $dbSettingsTitle, 'Expecting $settingsTitle: '.$settingsTitle.', actually: '.$dbSettingsTitle);
        $this->assertEquals($settingsCheckoutTitle, $dbSettingsCheckoutTitle, 'Expecting $settingsCheckoutTitle: '.$settingsCheckoutTitle.', actually: '.$dbSettingsCheckoutTitle);
        $this->assertEquals($settingsCheckoutDes, $dbSettingsCheckoutDes, 'Expecting $settingsCheckoutDes: '.$settingsCheckoutDes.', actually: '.$dbSettingsCheckoutDes);

        // reset the storeScope singleton at the end of the request
        $this->_resetScopeSingleton();
    }

    public function _zipMoneyApiMockRequestConfigurationFromZipMoney($requestData, $requestUrl) {
        $responseData = json_decode($this->responseJson, true);
        return $responseData;
    }
}