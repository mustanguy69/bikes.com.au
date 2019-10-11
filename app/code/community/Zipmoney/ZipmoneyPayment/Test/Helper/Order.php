<?php

/**
 * @category  Aligent
 * @package   Zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

/**
 * Class Zipmoney_ZipmoneyPayment_Test_Helper_Order
 * @loadSharedFixture scope.yaml
 */
class Zipmoney_ZipmoneyPayment_Test_Helper_Order extends EcomDev_PHPUnit_Test_Case
{

    /** @var Zipmoney_ZipmoneyPayment_Helper_Order */
    protected $_helper;
    protected $_customer;

    protected function setUp()
    {
        parent::setUp();
        $this->_helper = Mage::helper('zipmoneypayment/order');

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
     * @test
     * @cover Zipmoney_ZipmoneyPayment_Helper_Order_authoriseAndCapture
     * @group Zipmoney_ZipmoneyPayment
     * @loadFixture authoriseandcapture_orders.yaml
     * @loadFixture authoriseandcapture_payments.yaml
     * @dataProvider dataProvider
     */
    public function testAuthoriseAndCapture($vOrderId, $vTxnId, $bException, $vErrorMessage)
    {
        $oOrderHelper = Mage::helper('zipmoneypayment/order');
        $oHelper = Mage::helper('zipmoneypayment');
        try {
            $oOrder = $oOrderHelper->authoriseAndCapture($vOrderId, $vTxnId, '');
        } catch (Exception $e) {
            $this->assertTrue($bException, 'Unexpected exception during order finalisation. Message: ' . $e->getMessage());
            $vExpMessage = $oHelper->__($vErrorMessage);
            $this->assertSame($vExpMessage, $e->getMessage(), 'Error message does not match.');
            return;
        }
        $this->assertFalse($bException, 'Have not got expected exception during order finalisation.');
        $this->assertNotNull($oOrder);

        $cInvoices = $oOrder->getInvoiceCollection();
        $oInvoice = null;
        foreach ($cInvoices as $oItem) {
            $oInvoice = $oItem;
            break;
        }
        $oPayment = $oOrder->getPayment();
        $this->assertNotNull($oInvoice, 'Invoice is null.');
        $this->assertNotEmpty($oPayment, 'Payment is empty.');

        $vActState = $oOrder->getState();
        $vExpState = Mage_Sales_Model_Order::STATE_PROCESSING;
        $this->assertSame($vExpState, $vActState, 'Incorrect order state. Current state: ' . $vActState);

        $fExpGrandTotal = $oOrder->getGrandTotal();
        $this->assertEquals($fExpGrandTotal, $oInvoice->getGrandTotal(), 'Incorrect order grand total. Current grand total: ' . $oInvoice->getGrandTotal());
        $this->assertEquals($fExpGrandTotal, $oPayment->getAmountPaid(), 'Incorrect order paid amount. Current paid amount: ' . $oPayment->getAmountPaid());
    }

    /**
     * @test
     * @cover Zipmoney_ZipmoneyPayment_Helper_Order_confirmOrder
     * @group Zipmoney_ZipmoneyPayment
     * @loadFixture products.yaml
     * @loadFixture customers.yaml
     * @loadFixture confirmorder_shipping_table_rate.yaml
     * @loadFixture confirmorder_quotes.yaml
     * @loadFixture confirmorder_quote_items.yaml
     * @loadFixture confirmorder_quote_addresses.yaml
     * @dataProvider dataProvider
     */
    public function testConfirmOrder($iStoreId, $iCustomerId, $iQuoteId, $vShippingMethod, $vJson, $bException, $vErrorMessage)
    {
        Mage::app()->getStore()->resetConfig();     // It's important to resetConfig, otherwise something unexpected would happen.
        $oHelper = Mage::helper('zipmoneypayment');
        $oQuoteHelper = Mage::helper('zipmoneypayment/quote');
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($iStoreId);
        
        $oCustomer = null;
        if ($iCustomerId) {
            $oCustomer = Mage::getModel('customer/customer')->load($iCustomerId);
            $this->_customer = $oCustomer;
            $this->_mockGetCustomer();
        }

        try {
            $oOrder = $this->_helper->confirmOrder($iQuoteId);
            $oQuote = $oQuoteHelper->getQuote($iQuoteId);

            $this->assertNotNull($oQuote, 'Quote is null.');
            $this->assertNotNull($oOrder, 'Order is null.');
            $this->assertEquals(0, (int)$oQuote->getIsActive(), 'Expecting quote isActive: ' . 0 . ', actually: '. (int)$oQuote->getIsActive());
            $this->assertEquals($oQuote->getReservedOrderId(), $oOrder->getIncrementId(), 'Expecting order incrementId: ' . $oQuote->getReservedOrderId() . ', actually: '. $oOrder->getIncrementId());
            $this->assertEquals(Mage_Sales_Model_Order::STATE_NEW, $oOrder->getState(), 'Expecting order state: ' . $oQuote->getReservedOrderId() . ', actually: '. $oOrder->getIncrementId());
            $this->assertEquals(0, count($oOrder->getInvoiceCollection()), 'Invoice has been created, which should not.');
            $this->assertEquals($oQuote->getGrandTotal(), $oOrder->getGrandTotal(), 'Expecting order grand total: ' . $oQuote->getGrandTotal() . ', actually: '. $oOrder->getGrandTotal());

        } catch (Exception $e) {
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);       // It is important to stop it, otherwise we'd get 'Cannot complete this operation from non-admin area.' exception.
            $this->assertTrue($bException, 'Unexpected exception during confirming order. Message: ' . $e->getMessage());
            $vExpMessage = $oHelper->__($vErrorMessage);
            $this->assertNotFalse(strpos($e->getMessage(), $vExpMessage), 'Error message does not match. Actually: ' . $e->getMessage());
            return;
        }
        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);       // It is important to stop it, otherwise we'd get 'Cannot complete this operation from non-admin area.' exception.
        $this->assertFalse($bException, 'Have not got expected exception during confirming order.');
    }

    /**
     * @test
     * @cover Zipmoney_ZipmoneyPayment_Helper_Order_authorise
     * @group Zipmoney_ZipmoneyPayment
     * @loadFixture authorise_orders.yaml
     * @loadFixture authorise_payments.yaml
     * @dataProvider dataProvider
     */
    public function testAuthorise($vOrderId, $vTxnId, $bException, $vErrorMessage)
    {
        $oOrderHelper = Mage::helper('zipmoneypayment/order');
        $oHelper = Mage::helper('zipmoneypayment');
        try {
            $oOrder = $oOrderHelper->authorise($vOrderId, $vTxnId, '');
        } catch (Exception $e) {
            $this->assertTrue($bException, 'Unexpected exception during authorising payment. Message: ' . $e->getMessage());
            $vExpMessage = $oHelper->__($vErrorMessage);
            $this->assertSame($vExpMessage, $e->getMessage(), 'Error message does not match.');
            return;
        }
        $this->assertFalse($bException, 'Have not got expected exception during authorising payment.');

        // order
        $this->assertNotNull($oOrder);
        $fExpBaseGrandTotal = $oOrder->getBaseGrandTotal();
        $this->assertEquals($fExpBaseGrandTotal, $oOrder->getBaseTotalDue(), 'Incorrect order base total due. Current value: ' . $oOrder->getBaseTotalDue());

        // invoice
        $cInvoices = $oOrder->getInvoiceCollection();
        $this->assertEquals(0, count($cInvoices), 'Unexpected invoice.');

        // payment/transaction
        $oPayment = $oOrder->getPayment();
        $this->assertNotEmpty($oPayment, 'Payment is empty.');
        $oTxn = $oPayment->getCreatedTransaction();
        $oTxn->getTxnType();
        $this->assertSame('authorization', $oTxn->getTxnType(), 'Incorrect payment transaction type. Current value: ' . $oTxn->getTxnType());
        $this->assertSame('zipmoneypayment', $oPayment->getMethod(), 'Incorrect payment method. Current value: ' . $oPayment->getMethod());
        $this->assertSame($vTxnId, $oTxn->getTxnId(), 'Incorrect txn id. Current value: ' . $oTxn->getTxnId());
        $this->assertSame($vTxnId, $oPayment->getLastTransId(), 'Incorrect txn id. Current value: ' . $oPayment->getLastTransId());
        $this->assertEquals($fExpBaseGrandTotal, $oPayment->getBaseAmountOrdered(), 'Incorrect base ordered amount. Current value: ' . $oPayment->getBaseAmountOrdered());
        $this->assertEquals($fExpBaseGrandTotal, $oPayment->getBaseAmountAuthorized(), 'Incorrect base authorised amount. Current value: ' . $oPayment->getBaseAmountAuthorized());

        // order state/status
        $vActState = $oOrder->getState();
        $vExpState = Mage_Sales_Model_Order::STATE_PROCESSING;
        $this->assertSame($vExpState, $vActState, 'Incorrect order state. Current state: ' . $vActState);
        $vActStatus = $oOrder->getStatus();
        $vExpStatus = Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_AUTHORIZED;
        $this->assertSame($vExpStatus, $vActStatus, 'Incorrect order status. Current status: ' . $vActStatus);
    }

    /**
     * @test
     * @cover Zipmoney_ZipmoneyPayment_Helper_Order_capture
     * @group Zipmoney_ZipmoneyPayment
     * @loadFixture capture_orders.yaml
     * @loadFixture capture_payments.yaml
     * @dataProvider dataProvider
     */
    public function testCapture($vOrderId, $vTxnId, $bException, $vErrorMessage)
    {
        $oOrderHelper = Mage::helper('zipmoneypayment/order');
        $oHelper = Mage::helper('zipmoneypayment');
        try {
            $oOrder = $oOrderHelper->capture($vOrderId, $vTxnId);
        } catch (Exception $e) {
            $this->assertTrue($bException, 'Unexpected exception during capturing payment. Message: ' . $e->getMessage());
            $vExpMessage = $oHelper->__($vErrorMessage);
            $this->assertSame($vExpMessage, $e->getMessage(), 'Error message does not match.');
            return;
        }
        $this->assertFalse($bException, 'Have not got expected exception during capturing payment.');

        // order
        $this->assertNotNull($oOrder);
        $fExpBaseGrandTotal = $oOrder->getBaseGrandTotal();
        $this->assertEquals(0, $oOrder->getBaseTotalDue(), 'Incorrect order base total due. Current value: ' . $oOrder->getBaseTotalDue());

        // invoice
        $cInvoices = $oOrder->getInvoiceCollection();
        $oInvoice = null;
        foreach ($cInvoices as $oItem) {
            $oInvoice = $oItem;
            break;
        }
        $this->assertEquals($fExpBaseGrandTotal, $oInvoice->getBaseGrandTotal(), 'Incorrect order base grand total. Current value: ' . $oInvoice->getGrandTotal());
        $this->assertEquals($oOrder->getBaseShippingAmount(), $oInvoice->getBaseShippingAmount(), 'Incorrect order base shipping amount. Current value: ' . $oInvoice->getBaseShippingAmount());
        $this->assertEquals($oOrder->getBaseSubtotal(), $oInvoice->getBaseSubtotal(), 'Incorrect order base subtotal. Current value: ' . $oInvoice->getBaseSubtotal());
        $this->assertEquals($vTxnId, $oInvoice->getTransactionId(), 'Incorrect order transaction id. Current value: ' . $oInvoice->getTransactionId());

        // payment/transaction
        $oPayment = $oOrder->getPayment();
        $this->assertNotEmpty($oPayment, 'Payment is empty.');
        $oTxn = $oPayment->getCreatedTransaction();
        $oAuthorizationTxn = $oPayment->getAuthorizationTransaction();
        $this->assertNotNull($oTxn, 'Null value of created transaction.');
        $this->assertNotNull($oTxn->getParentTxnId(), 'Null value of parent txn id.');
        $this->assertSame('authorization', $oAuthorizationTxn->getTxnType(), 'Incorrect last payment transaction type. Current value: ' . $oAuthorizationTxn->getTxnType());
        $this->assertSame('capture', $oTxn->getTxnType(), 'Incorrect payment transaction type. Current value: ' . $oTxn->getTxnType());
        $this->assertSame('zipmoneypayment', $oPayment->getMethod(), 'Incorrect payment method. Current value: ' . $oPayment->getMethod());
        $this->assertSame($vTxnId, $oTxn->getTxnId(), 'Incorrect txn id. Current value: ' . $oTxn->getTxnId());
        $this->assertSame($oAuthorizationTxn->getTxnId(), $oTxn->getParentTxnId(), 'Incorrect txn id. Current value: ' . $oTxn->getParentTxnId());
        $this->assertSame($vTxnId, $oPayment->getLastTransId(), 'Incorrect last payment txn id. Current value: ' . $oPayment->getLastTransId());
        $this->assertEquals($fExpBaseGrandTotal, $oPayment->getBaseAmountOrdered(), 'Incorrect base ordered amount. Current value: ' . $oPayment->getBaseAmountOrdered());
        $this->assertEquals($fExpBaseGrandTotal, $oPayment->getBaseAmountPaid(), 'Incorrect base paid amount. Current value: ' . $oPayment->getBaseAmountPaid());

        // order state
        $vActState = $oOrder->getState();
        $vExpState = Mage_Sales_Model_Order::STATE_PROCESSING;
        $this->assertSame($vExpState, $vActState, 'Incorrect order state. Current state: ' . $vActState);

    }
}