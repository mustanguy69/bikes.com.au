<?php
/**
 * @category  Aligent
 * @package   Zipmoney
 * @author    Andi Han <andi@aligent.com.au>
 * @copyright 2014 Aligent Consulting.
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.aligent.com.au/
 */

class Zipmoney_ZipmoneyPayment_Helper_Order extends Mage_Core_Helper_Abstract
{

    /**
     * @var Zipmoney_ZipmoneyPayment_Model_Express_Checkout
     */
    protected $_checkout = null;

    /**
     * @param null $iQuoteId
     * @return Zipmoney_ZipmoneyPayment_Model_Express_Checkout
     */
    protected function _initCheckout($iQuoteId = null)
    {
        $this->_checkout = Mage::helper('zipmoneypayment/quote')->getExpressCheckout($iQuoteId);
        return $this->_checkout;
    }

    protected function _getQuote($iQuoteId)
    {
        return Mage::helper('zipmoneypayment/quote')->getQuote($iQuoteId);
    }

    protected function _getCheckoutSession()
    {
        return Mage::helper('zipmoneypayment/quote')->getCheckoutSession();
    }

    /**
     * @param $vOrderIncId
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function isOrderExpress($vOrderIncId)
    {
        $oOrder = Mage::getModel('sales/order')->loadByIncrementId($vOrderIncId);
        if (!$oOrder || !$oOrder->getId()) {
            $vMessage = $this->__('Incorrect order id.');
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
        }
        return ($oOrder->getPayment()->getIsZipmoneyExpress() == 1) ? true : false;
    }

    /**
     * Authorise payment
     *
     * @param Mage_Sales_Model_Order $oOrder
     * @param $vTxnId
     * @param string $vPreparedMessage
     * @throws Exception
     */
    protected function _authorisePayment(Mage_Sales_Model_Order $oOrder, $vTxnId, $vPreparedMessage = '')
    {
        /** @var Mage_Sales_Model_Order_Payment $oPayment */
        $fAmount = $oOrder->getBaseTotalDue();
        $oPayment = $oOrder->getPayment();
        $oPayment->setTransactionId($vTxnId);
        $oPayment->setPreparedMessage($vPreparedMessage);
        $oPayment->setIsTransactionClosed(0);
        $oPayment->registerAuthorizationNotification($fAmount);

        $status = Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_AUTHORIZED;
        $oOrder->setStatus($status);
        $oOrder->save();

        if (!$oOrder->getEmailSent()) {
            $oOrder->sendNewOrderEmail();
        }
    }

    /**
     * Capture payment
     *
     * @param Mage_Sales_Model_Order $oOrder
     * @param $vParentTxnId
     * @param $vTxnId
     * @param string $vPreparedMessage
     * @return Mage_Sales_Model_Order_Payment
     * @throws Exception
     */
    protected function _capturePayment(Mage_Sales_Model_Order $oOrder, $vParentTxnId, $vTxnId, $vPreparedMessage = '')
    {
        /** @var Mage_Sales_Model_Order_Payment $oPayment */
        $fAmount = $oOrder->getBaseTotalDue();
        $oPayment = $oOrder->getPayment();
        if ($vParentTxnId) {
            $oPayment->setParentTransactionId($vParentTxnId);
            $oPayment->setShouldCloseParentTransaction(true);
        }
        $oPayment->setTransactionId($vTxnId);
        $oPayment->setPreparedMessage($vPreparedMessage);
        $oPayment->setIsTransactionClosed(0);
        $oPayment->registerCaptureNotification($fAmount);
        $oOrder->save();

        // notify customer
        $oInvoice = $oPayment->getCreatedInvoice();
        if ($oInvoice && !$oOrder->getEmailSent()) {
            $oOrder->sendNewOrderEmail()->addStatusHistoryComment(
                Mage::helper('zipmoneypayment')->__('Notified customer about invoice #%s.', $oInvoice->getIncrementId())
            )
                ->setIsCustomerNotified(true)
                ->save();
        }
        return $oPayment;
    }

    /**
     * Convert quote to order (Pending)
     * @param $iQuoteId
     * @param $vShippingMethodCode
     * @param $oRequest
     * @return Mage_Sales_Model_Order
     * @throws Mage_Core_Exception
     */
    public function confirmOrder($iQuoteId)
    {
        $oQuote = $this->_getQuote($iQuoteId);
        if (!$oQuote) {
            $vMessage = $this->__('Cannot find the quote.');
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
        }
        if (!$oQuote->getIsActive()) {
            $vMessage = $this->__('The quote is not active.');
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
        }

        $this->_initCheckout($iQuoteId);
        // update shipping method, collect totals, convert quote to order
        $this->_checkout->place(true);

        $oSession = $this->_getCheckoutSession();
        $oSession->clearHelperData();

        $oSession->setLastQuoteId($iQuoteId)->setLastSuccessQuoteId($iQuoteId);

        $oOrder = $this->_checkout->getOrder();
        if ($oOrder && $oOrder->getId()) {
            $oSession->setLastOrderId($oOrder->getId())
                ->setLastRealOrderId($oOrder->getIncrementId());
            $oPayment = $oOrder->getPayment();
            $oPayment->setIsZipmoneyExpress(1);
            $oPayment->save();
        } else {
            $vMessage = $this->__('Can not create order.');
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
        }
        return $oOrder;
    }

    /**
     * Authorise payment (create Authorization payment), and update order status from Pending to zipMoney Authorised, state from New to Processing
     * @param $vOrderIncId
     * @param $vTxnId
     * @param string $vPreparedMessage
     * @return Mage_Sales_Model_Order
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function authorise($vOrderIncId, $vTxnId, $vPreparedMessage = '')
    {
        $oOrder = Mage::getModel('sales/order')->loadByIncrementId($vOrderIncId);
        if (!$oOrder || !$oOrder->getId()) {
            $vMessage = $this->__('Incorrect order id.');
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
        }
        $vCurrentState = $oOrder->getState();
        if ($vCurrentState != Mage_Sales_Model_Order::STATE_NEW) {
            $vMessage = $this->__('Invalid order state.');
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
        }
        $oPayment = $oOrder->getPayment();
        if ($oPayment && $oPayment->getId()) {
            $oTransaction = $oPayment->getTransaction($vTxnId);
            if ($oTransaction && $oTransaction->getId()) {
                $vMessage = $this->__('The payment transaction already exists.');
                throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
            }
        }

        $this->_authorisePayment($oOrder, $vTxnId, $vPreparedMessage);
        return $oOrder;
    }

    /**
     * Capture payment (update payment from Authorization to Capture), create invoice, update order status from zipMoney Authorised to Processing
     * @param $vOrderIncId
     * @param $vTxnId
     * @param string $vPreparedMessage
     * @return Mage_Sales_Model_Order
     * @throws Mage_Core_Exception
     */
    public function capture($vOrderIncId, $vTxnId, $vPreparedMessage = '')
    {
        $oOrder = Mage::getModel('sales/order')->loadByIncrementId($vOrderIncId);
        if (!$oOrder || !$oOrder->getId()) {
            $vMessage = $this->__('Incorrect order id.');
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
        }

        $vCurOrderStatus = $oOrder->getStatus();
        $vCurOrderState = $oOrder->getState();
        if (($vCurOrderState != Mage_Sales_Model_Order::STATE_PROCESSING && $vCurOrderState != Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)
            || ($vCurOrderStatus != Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_AUTHORIZED && $vCurOrderStatus != Zipmoney_ZipmoneyPayment_Model_Config::STATUS_MAGENTO_CAPTURE_PENDING)
                 // TODO: can a ZipMoney Pending order be captured?
            ) {
            $vMessage = $this->__('Invalid order state or status.');
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
        }

        $oPayment = $oOrder->getPayment();
        $oAuthorizationTransaction = $oPayment->getAuthorizationTransaction();
        if (!$oAuthorizationTransaction || !$oAuthorizationTransaction->getId()) {
            $vMessage = $this->__('Cannot find payment authorization transaction.');
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
        }
        if ($oAuthorizationTransaction->getTxnType() != Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH) {
            $vMessage = $this->__('Incorrect payment transaction type.');
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
        }
        if (!$oOrder->canInvoice()) {
            $vMessage = $this->__('Cannot create invoice for the order.');
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
        }
        $vParentTxnId = $oAuthorizationTransaction->getTxnId();
        $this->_capturePayment($oOrder, $vParentTxnId, $vTxnId, $vPreparedMessage);
        Mage::getSingleton('core/session')->setTxnzipMoney($vTxnId);
        return $oOrder;
    }

    /**
     * Authorise and capture payment (create Capture payment), create invoice, and update order status from Pending to Processing, state from New to Processing
     * @param $vOrderIncId
     * @param $vTxnId
     * @param $vPreparedMessage
     * @return Mage_Sales_Model_Order
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function authoriseAndCapture($vOrderIncId, $vTxnId, $vPreparedMessage = '')
    {
        $oOrder = Mage::getModel('sales/order')->loadByIncrementId($vOrderIncId);

        if (!$oOrder || !$oOrder->getId()) {
            $vMessage = $this->__('Incorrect order id.');
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
        }
        $vCurrentState = $oOrder->getState();
        if ($vCurrentState != Mage_Sales_Model_Order::STATE_NEW) {
            $vMessage = $this->__('Invalid order state.');
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
        }
        $oPayment = $oOrder->getPayment();
        if ($oPayment && $oPayment->getId()) {
            $oTransaction = $oPayment->getTransaction($vTxnId);
            if ($oTransaction && $oTransaction->getId()) {
                $vMessage = $this->__('The payment transaction already exists.');
                throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
            }
        }
        if (!$oOrder->canInvoice()) {
            $vMessage = $this->__('Cannot create invoice for the order.');
            throw Mage::exception('Zipmoney_ZipmoneyPayment', $vMessage);
        }
        $this->_capturePayment($oOrder, null, $vTxnId, $vPreparedMessage);
        Mage::getSingleton('core/session')->setTxnzipMoney($vTxnId);
        return $oOrder;
    }
}