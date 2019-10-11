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
 * Class Zipmoney_ZipmoneyPayment_Test_Helper_Creditmemo
 * @loadSharedFixture scope.yaml
 */
class Zipmoney_ZipmoneyPayment_Test_Helper_Creditmemo extends EcomDev_PHPUnit_Test_Case
{
    protected function setUp()
    {
        parent::setUp();
    }

    protected function _getStock($iProductId)
    {
        $oProduct = Mage::getModel('catalog/product')->load($iProductId);
        $iStock = (int)Mage::getModel('cataloginventory/stock_item')
            ->loadByProduct($oProduct)->getQty();
        return $iStock;
    }

    /**
     * @loadFixture products.yaml
     * @loadFixture customers.yaml
     * @loadFixture orders.yaml
     * @loadFixture order_addresses.yaml
     * @loadFixture order_payments.yaml
     * @dataProvider dataProvider
     */
    public function testCreateCreditMemo($vJson, $bException, $vErrorMessage)
    {
        $oCmHelper = Mage::helper('zipmoneypayment/creditmemo');

        $oRequest = json_decode($vJson);

        $vReason = isset($oRequest->reason) ? $oRequest->reason : null;
        $vOrderIncId = isset($oRequest->order_id) ? $oRequest->order_id : null;
        $vStatus = isset($oRequest->status) ? $oRequest->status : null;
        $vTxnId = isset($oRequest->txn_id) ? $oRequest->txn_id : null;
        $fRefundAmount = isset($oRequest->refund_amount) ? (float)$oRequest->refund_amount : null;
        $bReturnInventory = isset($oRequest->return_inventory) ? (bool)$oRequest->return_inventory : null;

        $vComment = $oCmHelper->__('The order has been refunded (Txn ID: %s).' , $vTxnId);
        $bCustomerNotified = true;
        $bIncludeComment = true;

        /** @var Mage_Sales_Model_Order $oOrder */
        $oOrder = Mage::getModel('sales/order')->loadByIncrementId($vOrderIncId);
        if ($oOrder->getId()) {
            $fGrandTotal = $oOrder->getGrandTotal();
            $aExpStocks = array();
            foreach ($oOrder->getAllItems() as $oItem) {
                /** @var Mage_Sales_Model_Order_Item $oItem */
                $iProductId = $oItem->getProductId();
                $iStock = $this->_getStock($iProductId);
                $aExpStocks[$iProductId] = $iStock + $oItem->getQtyOrdered();
            }
        }

        try {
            $oCreditmemo = $oCmHelper->createCreditMemo($vJson, $vComment, $bCustomerNotified, $bIncludeComment);
        } catch (Exception $e) {
            $vMsg = $e->getMessage();
            $this->assertTrue($bException, 'Unexpected exception while creating credit memo. Message: ' . $vMsg);
            $bMatch = (strpos($vMsg, $vErrorMessage) !== false);
            $this->assertTrue($bMatch, 'Error message does not match. Actual: ' . $vMsg);
            return;
        }
        $this->assertFalse($bException, 'Have not got expected exception while creating credit memo.');

        $oOrder = Mage::getModel('sales/order')->loadByIncrementId($vOrderIncId);
        $vState = $oOrder->getState();
        $fSubtotal = $oOrder->getSubtotal();
        $fShippingAmount = $oOrder->getShippingAmount();
        $fShippingRefunded = $oOrder->getShippingRefunded();
        $fSubtotalRefunded = $oOrder->getSubtotalRefunded();
        $fTotalOfflineRefunded = $oOrder->getTotalOfflineRefunded();
        $fTotalRefunded = $oOrder->getTotalRefunded();

        $vCmTxnId = $oCreditmemo->getZipmoneyTxnId();
        $fCmGrandTotal = $oCreditmemo->getGrandTotal();

        $this->assertEquals($fRefundAmount, $fCmGrandTotal, 'Incorrect credit memo grand total. Exp:' . $fRefundAmount . ', Act:' . $fCmGrandTotal);
        $this->assertEquals($fRefundAmount, $fTotalRefunded, 'Incorrect order total refunded. Exp:' . $fRefundAmount . ', Act:' . $fTotalRefunded);
        $this->assertEquals($fRefundAmount, $fTotalOfflineRefunded, 'Incorrect order total offline refunded. Exp:' . $fRefundAmount . ', Act:' . $fTotalOfflineRefunded);
        $this->assertSame($vTxnId, $vCmTxnId, 'Incorrect credit memo zipMoney txn id. Exp:' . $vTxnId . ', Act:' . $vCmTxnId);

        if ($fGrandTotal == $fRefundAmount) {
            // full order refund
            $this->assertSame(Mage_Sales_Model_Order::STATE_CLOSED, $vState, 'Incorrect order state. Exp:' . Mage_Sales_Model_Order::STATE_CLOSED . ', Act:' . $vState);
            $this->assertEquals($fShippingAmount, $fShippingRefunded, 'Incorrect order shipping refunded. Exp:' . $fShippingAmount . ', Act:' . $fShippingRefunded);
            $this->assertEquals($fSubtotal, $fSubtotalRefunded, 'Incorrect order subtotal refunded. Exp:' . $fSubtotal . ', Act:' . $fSubtotalRefunded);
            $this->assertEquals(count($oOrder->getAllItems()), count($oCreditmemo->getAllItems()), 'Incorrect order subtotal refunded. Exp:' . count($oOrder->getAllItems()) . ', Act:' . count($oCreditmemo->getAllItems()));

            if ($bReturnInventory) {
                foreach ($oOrder->getAllItems() as $oItem) {
                    /** @var Mage_Sales_Model_Order_Item $oItem */
                    $iProductId = $oItem->getProductId();
                    $iStock = $this->_getStock($iProductId);
                    $this->assertEquals($aExpStocks[$iProductId], $iStock, 'Incorrect product (' . $iProductId . ') stock. Exp:' . $fShippingAmount . ', Act:' . $fShippingRefunded);
                }
            }
        } else {
            // partial amount refund
            $this->assertNotSame(Mage_Sales_Model_Order::STATE_CLOSED, $vState, 'Incorrect order state. Exp not:' . Mage_Sales_Model_Order::STATE_CLOSED);
            $this->assertEquals(0, $fShippingRefunded, 'Incorrect order shipping refunded. Exp:0, Act:' . $fShippingRefunded);
            $this->assertEquals(0, count($oCreditmemo->getAllItems()), 'Incorrect credit memo items count. Exp:0, Act:' . count($oCreditmemo->getAllItems()));
        }
    }
}