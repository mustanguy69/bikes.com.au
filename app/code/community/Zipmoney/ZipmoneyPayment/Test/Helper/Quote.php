<?php
/**
 * @category  Aligent
 * @package   Package Name
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

/**
 * Class Zipmoney_ZipmoneyPayment_Test_Helper_Quote
 * @loadSharedFixture scope.yaml
 */
class Zipmoney_ZipmoneyPayment_Test_Helper_Quote extends EcomDev_PHPUnit_Test_Case
{
    /** @var Zipmoney_ZipmoneyPayment_Helper_Quote */
    protected $_helper;
    protected $_customer;

    protected function setUp()
    {
        parent::setUp();
        $this->_helper = Mage::helper('zipmoneypayment/quote');

        $this->_mockSessionCookie('customer/session');
        $this->_mockSessionCookie('admin/session');
        $this->_mockSessionCookie('adminhtml/session');
        $this->_mockSessionCookie('core/session');
        $this->_mockSessionCookie('checkout/session');

        Mage::unregister('_singleton/eav/config');
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

    protected function _mockGetCustomer()
    {
        $zipmoneyHelperMock = $this->getHelperMock('zipmoneypayment/api', array('getLoggedInCustomer'));
        $zipmoneyHelperMock->expects($this->any())
            ->method('getLoggedInCustomer')
            ->will($this->returnValue($this->_getLoggedInCustomerMock()));
        $this->replaceByMock('helper', 'zipmoneypayment/api', $zipmoneyHelperMock);
    }

    protected function _getLoggedInCustomerMock()
    {
        return $this->_customer;
    }

    /**
     * @loadFixture customers.yaml
     * @loadFixture products.yaml
     * @loadFixture quotes.yaml
     * @loadFixture quote_items.yaml
     * @loadFixture quote_addresses.yaml
     * @dataProvider dataProvider
     */
    public function testGetRequestData($iStoreId, $iQuoteId, $iCustomerId, $vCheckoutSource, $bException, $vErrorMessage)
    {
        Mage::app()->getStore()->resetConfig();     // It's important to resetConfig, otherwise something unexpected would happen.

        $oCustomer = null;
        $oQuote = Mage::getModel('sales/quote')->getCollection()->addFieldToFilter("entity_id", $iQuoteId)->getFirstItem();
        if ($oQuote->getCheckoutMethod() == 'customer' && $iCustomerId) {
            $oCustomer = Mage::getModel('customer/customer')->load($iCustomerId);
            $this->_customer = $oCustomer;
            $this->_mockGetCustomer();
        }

        $oHelper = Mage::helper('zipmoneypayment');
        $oApiHelper = Mage::helper('zipmoneypayment/api');
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($iStoreId);

        try {
            $aRawData = array(
                'quote' => $oQuote,
                'checkout_source' => $vCheckoutSource
            );
            $vEndpoint = ZipMoney_ApiSettings::API_TYPE_QUOTE_QUOTE;
            $aRequestData = $oApiHelper->prepareDataForZipRequest($vEndpoint, $aRawData);
            $this->assertNotNull($aRequestData);
        } catch (Exception $e) {
            $this->assertTrue($bException, 'Unexpected exception. Message: ' . $e->getMessage());
            $vExpMessage = $oHelper->__($vErrorMessage);
            $this->assertSame($vExpMessage, $e->getMessage(), 'Error message does not match.');
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            return;
        }
        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);       // It is important to stop it, otherwise we'd get 'Cannot complete this operation from non-admin area.' exception.

        $this->assertFalse($bException, 'Have not got expected exception.');
        if (!$bException) {
            $this->_checkResult($oQuote, $oCustomer, $vCheckoutSource, $aRequestData);
        }
    }

    protected function _checkResult(Mage_Sales_Model_Quote $oQuote, $oCustomer, $vCheckoutSource, $aRequestData)
    {
        /** @var Mage_Customer_Model_Customer $oCustomer */
        $iQuoteId = $oQuote->getId();

        $this->assertTrue(isset($aRequestData['quote_id']), 'Missing quote_id');
        $this->assertTrue(isset($aRequestData['token']), 'Missing token');
        $this->assertTrue(isset($aRequestData['checkout_source']), 'Missing checkout_source');
        $this->assertTrue(isset($aRequestData['shipping_address']), 'Missing shipping_address');
        $this->assertTrue(isset($aRequestData['billing_address']), 'Missing billing_address');
        $this->assertTrue(isset($aRequestData['order']), 'Missing order');

        $this->assertEquals($iQuoteId, $aRequestData['quote_id']);
        $this->assertEquals($oQuote->getZipmoneyToken(), $aRequestData['token']);
        $this->assertEquals($vCheckoutSource, $aRequestData['checkout_source']);

        $aShippingAddress = $aRequestData['shipping_address'];
        $aBillingAddress = $aRequestData['billing_address'];
        $aOrder = $aRequestData['order'];
        if ($oCustomer) {
            $this->assertTrue(isset($aRequestData['consumer']));
            $aConsumer = $aRequestData['consumer'];
            $this->_checkConsumer($oCustomer, $aConsumer);
        }
        $oShippingAddress = $oQuote->getShippingAddress();
        $this->_checkAddress($oShippingAddress, $aShippingAddress, true);

        $oBillingAddress = $oQuote->getBillingAddress();
        $this->_checkAddress($oBillingAddress, $aBillingAddress, false);

        $this->_checkOrder($oQuote, $aOrder, $oShippingAddress);
    }

    protected function _checkAddress($oExpAddress, $aActAddress, $bShipping)
    {
        $this->assertEquals($oExpAddress->getFirstname(), $aActAddress['first_name'] , 'Expecting address first name: ' . $oExpAddress->getFirstname() . ', actually: '. $aActAddress['first_name']);
        $this->assertEquals($oExpAddress->getLastname(), $aActAddress['last_name'] , 'Expecting address last name: ' . $oExpAddress->getLastname() . ', actually: '. $aActAddress['last_name']);
        $this->assertEquals($oExpAddress->getEmail(), $aActAddress['email'] , 'Expecting address email: ' . $oExpAddress->getEmail() . ', actually: '. $aActAddress['email']);
        $this->assertEquals($oExpAddress->getStreet1(), $aActAddress['line1'] , 'Expecting address street: ' . $oExpAddress->getStreet1() . ', actually: '. $aActAddress['line1']);
        $this->assertEquals($oExpAddress->getCountry(), $aActAddress['country'] , 'Expecting address city: ' . $oExpAddress->getCountry() . ', actually: '. $aActAddress['country']);
        $this->assertEquals($oExpAddress->getPostcode(), $aActAddress['zip'] , 'Expecting address postcode: ' . $oExpAddress->getPostcode() . ', actually: '. $aActAddress['zip']);
        $this->assertEquals($oExpAddress->getCity(), $aActAddress['city'] , 'Expecting address city: ' . $oExpAddress->getCity() . ', actually: '. $aActAddress['city']);
        if ($oExpAddress->getRegionId()) {
            $this->assertEquals($oExpAddress->getRegionCode(), $aActAddress['state'], 'Expecting address region: ' . $oExpAddress->getRegionCode() . ', actually: ' . $aActAddress['state']);
        } else {
            $this->assertEquals($oExpAddress->getRegion(), $aActAddress['state'], 'Expecting address region: ' . $oExpAddress->getRegion() . ', actually: ' . $aActAddress['state']);
        }

        if ($bShipping) {
            $this->assertEquals($oExpAddress->getShippingMethod(), $aActAddress['selected_option_id'] , 'Expecting shiping method: ' . $oExpAddress->getShippingMethod() . ', actually: '. $aActAddress['selected_option_id']);
        }
    }

    protected function _checkConsumer($oExpCustomer, $aActConsumer)
    {
        if ($oExpCustomer->getGender()) {
            $vExpGender = Mage::getModel('customer/customer')->getResource()
                ->getAttribute('gender')
                ->getSource()
                ->getOptionText($oExpCustomer->getGender());
        }
        $this->assertEquals($oExpCustomer->getFirstname(), $aActConsumer['first_name'] , 'Expecting consumer firstname: ' . $oExpCustomer->getFirstname() . ', actually: '. $aActConsumer['first_name']);
        $this->assertEquals($oExpCustomer->getLastname(), $aActConsumer['last_name'] , 'Expecting consumer lastname: ' . $oExpCustomer->getLastname() . ', actually: '. $aActConsumer['last_name']);
        $this->assertEquals($oExpCustomer->getEmail(), $aActConsumer['email'] , 'Expecting consumer email: ' . $oExpCustomer->getEmail() . ', actually: '. $aActConsumer['email']);
        $this->assertEquals($vExpGender, $aActConsumer['gender'] , 'Expecting consumer gender: ' . $oExpCustomer->getGender() . ', actually: '. $aActConsumer['gender']);
        $this->assertEquals($oExpCustomer->getDob(), $aActConsumer['dob'] , 'Expecting consumer dob: ' . $oExpCustomer->getDob() . ', actually: '. $aActConsumer['dob']);
        $this->assertEquals($oExpCustomer->getPrefix(), $aActConsumer['title'] , 'Expecting consumer title: ' . $oExpCustomer->getPrefix() . ', actually: '. $aActConsumer['title']);
    }

    protected function _checkOrder(Mage_Sales_Model_Quote $oQuote, $aActOrder, $oShippingAddress)
    {
        $this->assertEquals($oQuote->getReservedOrderId(), $aActOrder['id'] , 'Expecting quote reservedOrderId: ' . $oQuote->getReservedOrderId() . ', actually: '. $aActOrder['id']);
        $this->assertEquals($oShippingAddress->getShippingInclTax(), $aActOrder['shipping_value'] , 'Expecting quote shipping_value: ' . $oShippingAddress->getShippingAmount() . ', actually: '. $aActOrder['shipping_value']);
        $this->assertEquals($oQuote->getGrandTotal(), $aActOrder['total'] , 'Expecting quote total: ' . $oQuote->getGrandTotal() . ', actually: '. $aActOrder['total']);
        $this->assertEquals($oShippingAddress->getTaxAmount(), $aActOrder['tax'] , 'Expecting quote tax: ' . $oShippingAddress->getTaxAmount() . ', actually: '. $aActOrder['tax']);

        $this->assertEquals(count($oQuote->getAllItems()), count($aActOrder['detail']) , 'Expecting quote item number: ' . count($oQuote->getAllItems()) . ', actually: '. count($aActOrder['detail']));
    }
}