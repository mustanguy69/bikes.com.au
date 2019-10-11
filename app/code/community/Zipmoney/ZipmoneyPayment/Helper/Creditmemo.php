<?php
/**
 * @category  Aligent
 * @package   Zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Helper_Creditmemo extends Mage_Core_Helper_Abstract
{
    protected function _generateCmData($vJson, $vComment, $bNotifyCustomer = false, $bIncludeComment = false)
    {
        $oLogging = Mage::helper('zipmoneypayment/logging');
        $oResponse = json_decode($vJson);

        $vOrderIncId = isset($oResponse->order_id) ? $oResponse->order_id : null;
        $vTxnId = isset($oResponse->txn_id) ? $oResponse->txn_id : null;
        $fRefundAmount = isset($oResponse->refund_amount) ? $oResponse->refund_amount : null;
        $bReturnInventory = isset($oResponse->return_inventory) ? (bool)$oResponse->return_inventory : false;
        $vReason = isset($oResponse->reason) ? $oResponse->reason : null;
        $vComment = $vComment . ' ' . $vReason;

        /** @var Mage_Sales_Model_Order $oOrder */
        $oOrder = Mage::getModel('sales/order')->loadByIncrementId($vOrderIncId);
        if(!$oOrder || !$oOrder->getId()) {
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $this->__('Can not load the order.'));
        }

        if (!$fRefundAmount) {
            $vMessage = $this->__('Incorrect refund amount.');
            $oLogging->writeLog($vMessage, Zend_Log::DEBUG, $oOrder->getStoreId());
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
        }

        $fGrandTotal = $oOrder->getGrandTotal();
        $aQtys = array();
        $aBackToStock = array();
        $fShippingAmount = 0;
        $fAdjustmentPositive = 0;
        if ($fGrandTotal == $fRefundAmount) {
            // full order refund
            $oLogging->writeLog($this->__('Full order refund.'), Zend_Log::INFO, $oOrder->getStoreId());
            $fShippingAmount = $oOrder->getShippingInclTax();

            /** @var Mage_Sales_Model_Order_Item $oOrderItem */
            foreach($oOrder->getAllItems() as $oOrderItem) {
                $iOrderItemId = $oOrderItem->getId();
                $aQtys[$iOrderItemId] = $oOrderItem->getQtyOrdered();
                if ($bReturnInventory == true) {
                    $aBackToStock[$iOrderItemId] = true;
                }
            }
        } else {
            // amount refund
            $oLogging->writeLog($this->__('Partial (amount) refund.'), Zend_Log::INFO, $oOrder->getStoreId());
            $fAdjustmentPositive = $fRefundAmount;
            foreach($oOrder->getAllItems() as $oOrderItem) {
                $aQtys[$oOrderItem->getId()] = 0;
            }
        }

        $aCreditMemoData = array(
            'qtys'  => $aQtys,
            'shipping_amount' => $fShippingAmount,
            'adjustment_positive' => $fAdjustmentPositive,
            'adjustment_negative' => '0'
        );
        $oCmObj = new Varien_Object();
        $oCmObj->setOrderIncrementId($vOrderIncId);
        $oCmObj->setTxnId($vTxnId);
        $oCmObj->setOrder($oOrder);
        $oCmObj->setCreditMemoData($aCreditMemoData);
        $oCmObj->setComment($vComment);
        $oCmObj->setNotifyCustomer($bNotifyCustomer);
        $oCmObj->setIncludeComment($bIncludeComment);
        $oCmObj->setBackToStock($aBackToStock);
        return $oCmObj;
    }

    /**
     * @param $oOrder
     * @return bool
     */
    protected function _canCreditmemo($oOrder)
    {
        $oLogging = Mage::helper('zipmoneypayment/logging');
        /** @var Mage_Sales_Model_Order $oOrder */
        if (!$oOrder->getId()) {
            $oLogging->writeLog($this->__('Cannot create credit memo, because the order does not exist. '), Zend_Log::WARN);
            return false;
        }
        if (!$oOrder->canCreditmemo()) {
            $oLogging->writeLog($this->__('Cannot create credit memo.'), Zend_Log::WARN);
            return false;
        }
        return true;
    }

    protected function _setBackToStockFlag($oCreditMemo, $aBackToStock = array())
    {
        /**
         * Process back to stock flags
         */
        foreach ($oCreditMemo->getAllItems() as $creditmemoItem) {
            $orderItem = $creditmemoItem->getOrderItem();
            $parentId = $orderItem->getParentItemId();
            if (isset($aBackToStock[$orderItem->getId()])) {
                $creditmemoItem->setBackToStock(true);
            } elseif ($orderItem->getParentItem() && isset($aBackToStock[$parentId]) && $aBackToStock[$parentId]) {
                $creditmemoItem->setBackToStock(true);
            } elseif (empty($savedData)) {
                $creditmemoItem->setBackToStock(Mage::helper('cataloginventory')->isAutoReturnEnabled());
            } else {
                $creditmemoItem->setBackToStock(false);
            }
        }
    }

    /**
     * @param $oCmData
     * @return Mage_Sales_Model_Order_Creditmemo
     * @throws Mage_Core_Exception
     */
    protected function _create($oCmData)
    {
        $oLogging = Mage::helper('zipmoneypayment/logging');
        if (!$oCmData) {
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $this->__('Can not create Credit Memo due to empty data.'));
        }
        /** @var Mage_Sales_Model_Order $oOrder */
        $oOrder             = $oCmData->getOrder();
        $aCreditMemoData    = $oCmData->getCreditMemoData();
        $vComment           = $oCmData->getComment();
        $vTxnId             = $oCmData->getTxnId();
        $bNotifyCustomer    = $oCmData->getNotifyCustomer();
        $bIncludeComment    = $oCmData->getIncludeComment();
        $aBackToStock       = $oCmData->getBackToStock();

        if (!$this->_canCreditmemo($oOrder)) {
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $this->__('The order (id: %s) can not be refunded.', $oOrder->getIncrementId()));
        }
        $oService = Mage::getModel('sales/service_order', $oOrder);
        $oCreditMemo = $oService->prepareCreditmemo($aCreditMemoData);
        $oCreditMemo->setOfflineRequested(true);

        if (!empty($aBackToStock)) {
            $oLogging->writeLog($this->__('Return all order items to stock.'), Zend_Log::INFO, $oOrder->getStoreId());
            $this->_setBackToStockFlag($oCreditMemo, $aBackToStock);
        }

        if ($bIncludeComment && !empty($vComment)) {
            $oCreditMemo->addComment($vComment, $bNotifyCustomer);
        }
        $oCreditMemo->setZipmoneyTxnId($vTxnId);
        try {
            $oCreditMemo->register();

            Mage::getModel('core/resource_transaction')
                ->addObject($oCreditMemo)
                ->addObject($oOrder)
                ->save();
            $oLogging->writeLog($this->__('Successful to create credit memo.'), Zend_Log::NOTICE, $oOrder->getStoreId());
        } catch (Mage_Core_Exception $e) {
            $oLogging->writeLog($e->getTraceAsString(), Zend_Log::DEBUG);
            $vMsg = $e->getMessage();
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $this->__('Failed to create Credit Memo for order %s. Message: %s', $oOrder->getIncrementId(), $vMsg));
        } catch (Exception $e) {
            $oLogging->writeLog($e->getTraceAsString(), Zend_Log::DEBUG);
            $vMsg = $e->getMessage();
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $this->__('Failed to create Credit Memo for order %s. Message: %s', $oOrder->getIncrementId(), $vMsg));
        }
        return $oCreditMemo;
    }

    /**
     * Generate refund reference value for credit memo
     *
     * @return string
     */
    public function generateRefundReference()
    {
        $vRefundRef = md5(rand(0, 9999999));
        return $vRefundRef;
    }

    /**
     * Create credit memo
     *
     * @param $vJson
     * @param $vComment
     * @param bool $bNotifyCustomer
     * @param bool $bIncludeComment
     * @return Mage_Sales_Model_Order_Creditmemo
     * @throws Mage_Core_Exception
     */
    public function createCreditMemo($vJson, $vComment, $bNotifyCustomer = false, $bIncludeComment = false)
    {
        $oCmData = $this->_generateCmData($vJson, $vComment, $bNotifyCustomer, $bIncludeComment);
        return $this->_create($oCmData);
    }
}