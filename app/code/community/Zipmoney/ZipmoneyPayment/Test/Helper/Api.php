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
 * Class Zipmoney_ZipmoneyPayment_Test_Helper_Api
 * @loadSharedFixture scope.yaml
 */
class Zipmoney_ZipmoneyPayment_Test_Helper_Api extends EcomDev_PHPUnit_Test_Case
{
    /** @var Zipmoney_ZipmoneyPayment_Helper_Data $helper*/
    protected $helper;
    protected $responseJson;

    protected function setUp()
    {
        parent::setUp();

        $this->_mockSessionCookie('customer/session');
        $this->_mockSessionCookie('admin/session');
        $this->_mockSessionCookie('adminhtml/session');
        $this->_mockSessionCookie('core/session');
        $this->_mockSessionCookie('checkout/session');

        $this->helper = Mage::helper('zipmoneypayment');
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

    /**
     * @test
     * @cover Zipmoney_ZipmoneyPayment_Helper_Api_compareQuoteInRequestAndDb
     * @group Zipmoney_ZipmoneyPayment
     * @loadFixture products.yaml
     * @loadFixture customers.yaml
     * @loadFixture shipping_table_rate.yaml
     * @loadFixture quotes.yaml
     * @loadFixture quote_items.yaml
     * @loadFixture quote_addresses.yaml
     * @dataProvider dataProvider
     */
    public function testCheckQuoteDetailsForGetShippingMethods($iStoreId, $iQuoteId, $vJson, $bException, $vErrorMessage)
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $oQuoteHelper = Mage::helper('zipmoneypayment/quote');
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($iStoreId);

        $oQuote = $oQuoteHelper->getQuote($iQuoteId);
        $oRequest = json_decode(unserialize($vJson));

        try {
            $aResult = Mage::helper('zipmoneypayment/api')->compareQuoteInRequestAndDb($oQuote, $oRequest, true, false, false, false, true, true);
            $bChanged = isset($aResult['changed']) ? $aResult['changed'] : null;
            $vMessage = isset($aResult['message']) ? $aResult['message'] : '';
            if ($bChanged === null) {
                $vMessage = $oHelper->__('Incorrect return value from compareQuoteInRequestAndDb.');
                throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
            }
            if ($bChanged) {
                $vMessage = $oHelper->__('The shopping cart details may have been changed. ') . $vMessage;
                throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
            }
        } catch (Exception $e) {
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);       // It is important to stop it, otherwise we'd get 'Cannot complete this operation from non-admin area.' exception.
            $this->assertTrue($bException, 'Unexpected exception during getting shipping methods. Message: ' . $e->getMessage());
            $vExpMessage = $oHelper->__($vErrorMessage);
            $this->assertNotFalse(strpos($e->getMessage(), $vExpMessage), 'Error message does not match. Actually: ' . $e->getMessage());
            return;
        }
        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);       // It is important to stop it, otherwise we'd get 'Cannot complete this operation from non-admin area.' exception.
        $this->assertFalse($bException, 'Have not got expected exception during getting shipping methods.');
    }

    /**
     * @test
     * @cover Zipmoney_ZipmoneyPayment_Helper_Api_confirmShippingMethod
     * @group Zipmoney_ZipmoneyPayment
     * @loadFixture products.yaml
     * @loadFixture customers.yaml
     * @loadFixture shipping_table_rate.yaml
     * @loadFixture quotes.yaml
     * @loadFixture quote_items.yaml
     * @loadFixture quote_addresses.yaml
     * @dataProvider dataProvider
     */
    public function testCheckQuoteDetailsForConfirmShippingMethod($iStoreId, $iQuoteId, $vJson, $bException, $vErrorMessage)
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $oQuoteHelper = Mage::helper('zipmoneypayment/quote');
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($iStoreId);

        $oQuote = $oQuoteHelper->getQuote($iQuoteId);
        $oRequest = json_decode(unserialize($vJson));

        try {
            $aResult = Mage::helper('zipmoneypayment/api')->compareQuoteInRequestAndDb($oQuote, $oRequest, true, true, false, true, true, true);
            $bChanged = isset($aResult['changed']) ? $aResult['changed'] : null;
            $vMessage = isset($aResult['message']) ? $aResult['message'] : '';
            if ($bChanged === null) {
                $vMessage = $oHelper->__('Incorrect return value from compareQuoteInRequestAndDb.');
                throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
            }
            if ($bChanged) {
                $vMessage = $oHelper->__('The shopping cart details may have been changed. ') . $vMessage;
                throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
            }
        } catch (Exception $e) {
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);       // It is important to stop it, otherwise we'd get 'Cannot complete this operation from non-admin area.' exception.
            $this->assertTrue($bException, 'Unexpected exception during confirming shipping method. Message: ' . $e->getMessage());
            $vExpMessage = $oHelper->__($vErrorMessage);
            $this->assertNotFalse(strpos($e->getMessage(), $vExpMessage), 'Error message does not match. Actually: ' . $e->getMessage());
            return;
        }
        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);       // It is important to stop it, otherwise we'd get 'Cannot complete this operation from non-admin area.' exception.
        $this->assertFalse($bException, 'Have not got expected exception during confirming shipping method.');
    }

    /**
     * @test
     * @cover Zipmoney_ZipmoneyPayment_Helper_Api_compareQuoteInRequestAndDb
     * @group Zipmoney_ZipmoneyPayment
     * @loadFixture products.yaml
     * @loadFixture customers.yaml
     * @loadFixture shipping_table_rate.yaml
     * @loadFixture quotes.yaml
     * @loadFixture quote_items.yaml
     * @loadFixture quote_addresses.yaml
     * @dataProvider dataProvider
     */
    public function testCheckQuoteDetailsForConfirmOrder($iStoreId, $iQuoteId, $vJson, $bException, $vErrorMessage)
    {
        $oHelper = Mage::helper('zipmoneypayment');
        $oQuoteHelper = Mage::helper('zipmoneypayment/quote');
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($iStoreId);

        $oQuote = $oQuoteHelper->getQuote($iQuoteId);
        $oRequest = json_decode(unserialize($vJson));

        try {
            $aResult = Mage::helper('zipmoneypayment/api')->compareQuoteInRequestAndDb($oQuote, $oRequest, true, true, true, false, true, false);
            $bChanged = isset($aResult['changed']) ? $aResult['changed'] : null;
            $vMessage = isset($aResult['message']) ? $aResult['message'] : '';
            if ($bChanged === null) {
                $vMessage = $oHelper->__('Incorrect return value from compareQuoteInRequestAndDb.');
                throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
            }
            if ($bChanged) {
                $vMessage = $oHelper->__('The shopping cart details may have been changed. ') . $vMessage;
                throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
            }
        } catch (Exception $e) {
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);       // It is important to stop it, otherwise we'd get 'Cannot complete this operation from non-admin area.' exception.
            $this->assertTrue($bException, 'Unexpected exception during confirming shipping method. Message: ' . $e->getMessage());
            $vExpMessage = $oHelper->__($vErrorMessage);
            $this->assertNotFalse(strpos($e->getMessage(), $vExpMessage), 'Error message does not match. Actually: ' . $e->getMessage());
            return;
        }
        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);       // It is important to stop it, otherwise we'd get 'Cannot complete this operation from non-admin area.' exception.
        $this->assertFalse($bException, 'Have not got expected exception during confirming shipping method.');
    }
}