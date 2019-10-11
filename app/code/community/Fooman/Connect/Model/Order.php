<?php

/*
 * @author     Kristof Ringleff
 * @package    Fooman_Connect
 * @copyright  Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Fooman_Connect_Model_Order extends Fooman_Connect_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('foomanconnect/order');
    }

    /**
     * @param int $orderId
     *
     * @return array
     */
    public function exportByOrderId($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        return $this->exportOne($order);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    public function exportOne(Mage_Sales_Model_Order $order)
    {
        /** @var Fooman_Connect_Model_Order $orderStatus */
        $orderStatus = $this->load($order->getId(), 'order_id');
        if (!$orderStatus->getOrderId()) {
            $orderStatus->isObjectNew(true);
            $orderStatus->setOrderId((int)$order->getId());
        }
        if ($order->getBaseGrandTotal() == 0
            && !Mage::getStoreConfigFlag(
                'foomanconnect/order/exportzero', $order->getStoreId()
            )
        ) {
            $orderStatus->setXeroExportStatus(Fooman_Connect_Model_Status::WONT_EXPORT);
            $orderStatus->setXeroLastValidationErrors('');
            $orderStatus->save();
        } else {
            try {
                $orderData = array();
                $dataSource = Mage::getModel('foomanconnect/dataSource_order', array('order' => $order));
                $orderData = $dataSource->getOrderData();
                /*if ($order->getPayment()->getMethod() === 'bankpayment'
                    || $order->getPayment()->getMethod() === 'cashondelivery'
                ) {
                    $orderData['status'] = Fooman_Connect_Model_System_InitialInvoiceStatus::DRAFT;
                }*/
				unset($orderData['invoiceLines']['rewardpoints_earn']);
                Mage::getModel('foomanconnect/item')->ensureItemsExist($orderData, $order->getStoreId());
                $result = $this->sendToXero($dataSource->getXml($orderData), $order->getStoreId());
                if (!isset($result['Invoices'][0]['InvoiceID']) || empty($result['Invoices'][0]['InvoiceID'])) {
                    throw new Fooman_Connect_Exception('No Invoice Id received');
                }
                $orderStatus->setXeroInvoiceId($result['Invoices'][0]['InvoiceID']);
                $orderStatus->setXeroInvoiceNumber($result['Invoices'][0]['InvoiceNumber']);
                $orderStatus->setXeroExportStatus(Fooman_Connect_Model_Status::EXPORTED);
                $orderStatus->setXeroLastValidationErrors('');

                if ($orderData['status'] == Fooman_Connect_Model_System_InitialInvoiceStatus::AUTHORISED
                    && Mage::getStoreConfigFlag('foomanconnect/order/payment', $order->getStoreId())
                    && $order->getGrandTotal() > 0
                ) {
                    $data = array();
                    $data['xero_id'] = $orderStatus->getXeroInvoiceId();
                    if ($order->getPayment()->getMethod() === 'bankpayment') {
                        $data['account_id'] = Mage::getStoreConfig(
                            'foomanconnect/order/bankpaymentbankaccount', $order->getStoreId()
                        );
                    } elseif ($order->getPayment()->getMethod() === 'cashondelivery') {
                        $data['account_id'] = Mage::getStoreConfig(
                            'foomanconnect/order/codbankaccount', $order->getStoreId()
                        );
                    } elseif (strpos($order->getPayment()->getMethod(), 'paypal') !== false) {
                        $data['account_id'] = Mage::getStoreConfig(
                            'foomanconnect/order/paypalbankaccount', $order->getStoreId()
                        );
                    } else {
                        $data['account_id'] = Mage::getStoreConfig(
                            'foomanconnect/order/bankaccount', $order->getStoreId()
                        );
                    }
                    $data['status'] = 'AUTHORISED';
                    $data['reference'] = $order->getPayment()->getLastTransId();
                    $data['date'] = $orderData['createdAt'];
                    $data['amount'] = $result['Invoices'][0]['AmountDue'];
                    $this->sendPaymentToXero(
                        Fooman_ConnectLicense_Model_DataSource_Converter_PaymentsXml::convert(array('Invoices' => array($data))),
                        $order->getStoreId()
                    );
                    $orderStatus->setPaymentExportStatus(Fooman_Connect_Model_Status::EXPORTED);
                }
                $orderStatus->save();
                return $result;
            } catch (Fooman_Connect_TemporaryException $e) {
                //Only log will reattempt
                Mage::logException($e);
                Mage::throwException(
                    sprintf(
                        '%s: Export did not succeed %s. This could be a temporary issue - please try again.',
                        $order->getIncrementId(), $e->getMessage()
                    )
                );
            } catch (Fooman_Connect_Exception $e) {
                $xeroErrors = $e->getXeroErrors();
                if ((in_array('This document cannot be edited as it has a payment or credit note allocated to it.', $xeroErrors)
                    || in_array('Invoice not of valid status for modification', $xeroErrors))
                    && $orderStatus->getXeroExportStatus(Fooman_Connect_Model_Status::EXPORTED)
                ) {
                    $message = '<br/>Errors received from Xero: ' . implode('<br/>', $e->getXeroErrors());
                    Mage::throwException(sprintf('%s: Export did not succeed %s', $order->getIncrementId(), $message));
                } else {
                    $this->_handleError($orderStatus, $e, $e->getXeroErrors(), $order, $orderData);
                }

            } catch (Exception $e) {
                $this->_handleError($orderStatus, $e, $e->getMessage(), $order, $orderData);
            }
        }
    }

    /**
     * Depending on the invoice status in Xero we either can DELETE the invoice or if already authorised we need to VOID
     * as we don't know the status we could either query first and then take the appropriate action (always 2 requests)
     * or try the delete first and if rejected send the void request (sometimes 2 requests)
     *
     * @param $orderId
     */
    public function deleteOrVoidOne($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        $orderStatus = $this->load($orderId, 'order_id');
        if ($orderStatus->getXeroInvoiceNumber() && $orderStatus->getXeroExportStatus() == Fooman_Connect_Model_Status::EXPORTED) {
            try {
                $this->sendToXero(
                    Fooman_ConnectLicense_Model_DataSource_Converter_OrderCancelXml::convert($orderStatus->getXeroInvoiceNumber(), 'DELETED'),
                    $order->getStoreId()
                );
                $orderStatus->setXeroExportStatus(Fooman_Connect_Model_Status::EXPORTED_AND_DELETED);
                $orderStatus->save();
            } catch (Exception $e) {
                try {
                    $this->sendToXero(
                        Fooman_ConnectLicense_Model_DataSource_Converter_OrderCancelXml::convert($orderStatus->getXeroInvoiceNumber(), 'VOIDED'),
                        $order->getStoreId()
                    );
                    $orderStatus->setXeroExportStatus(Fooman_Connect_Model_Status::EXPORTED_AND_VOIDED);
                    $orderStatus->save();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }
    }

    public function exportOrders()
    {
        $stores = array_keys(Mage::app()->getStores());
        foreach ($stores as $storeId) {
            if (Mage::getStoreConfigFlag('foomanconnect/cron/xeroautomatic', $storeId)) {
                /** @var Fooman_Connect_Model_Resource_Order_Collection $collection */
                $collection = $this->getCollection()->getUnexportedOrders($storeId)->setPageSize(self::PROCESS_PER_RUN);
                $collection->getSelect()->order('created_at DESC');
                $collection->addConfigDateFilter($storeId);
                foreach ($collection as $order) {
                    /** @var $order Fooman_Connect_Model_Order */
                    try {
                        $this->exportByOrderId($order->getEntityId());
                    } catch (Exception $e) {
                        //don't stop cron execution
                        //exception has already been logged
                    }
                }
            }
        }
    }

    /**
     * @param $xml
     * @param $storeId
     *
     * @return array
     */
    public function sendToXero($xml, $storeId)
    {
        return $this->getApi()->setStoreId($storeId)->sendData(
            Fooman_Connect_Model_Xero_Api::INVOICES_PATH, Zend_Http_Client::POST, $xml
        );
    }

    /**
     * @return bool|string
     */
    public function getSalesEntityViewId()
    {
        return $this->_getSalesEntityViewId('order');
    }
}
