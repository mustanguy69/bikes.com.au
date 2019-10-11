<?php

/*
 * @author     Kristof Ringleff
 * @package    Fooman_Connect
 * @copyright  Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Fooman_Connect_Model_Invoice extends Fooman_Connect_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('foomanconnect/invoice');
    }

    /**
     * @param int $invoiceId
     *
     * @return array
     */
    public function exportByInvoiceId($invoiceId)
    {
        $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);
        return $this->exportOne($invoice);
    }

    /**
     * @param Mage_Sales_Model_Order_Invoice $invoice
     *
     * @return array
     */
    public function exportOne(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $invoiceStatus = $this->load($invoice->getId(), 'invoice_id');
        if (!$invoiceStatus->getInvoiceId()) {
            $invoiceStatus->isObjectNew(true);
            $invoiceStatus->setInvoiceId((int)$invoice->getId());
        }
        if ($invoice->getBaseGrandTotal() == 0
            && !Mage::getStoreConfigFlag(
                'foomanconnect/order/exportzero', $invoice->getStoreId()
            )
        ) {
            $invoiceStatus->setXeroExportStatus(Fooman_Connect_Model_Status::WONT_EXPORT);
            $invoiceStatus->setXeroLastValidationErrors('');
            $invoiceStatus->save();
        } else {
            try {
                $invoiceData = array();
                $dataSource = Mage::getModel('foomanconnect/dataSource_invoice', array('invoice' => $invoice));
                $invoiceData = $dataSource->getInvoiceData();
                /*if ($invoice->getOrder()->getPayment()->getMethod() === 'bankpayment'
                    || $invoice->getOrder()->getPayment()->getMethod() === 'cashondelivery'
                ) {
                    $invoiceData['status'] = Fooman_Connect_Model_System_InitialInvoiceStatus::DRAFT;
                }*/
				unset($invoiceData['invoiceLines']['rewardpoints_earn']);
                Mage::getModel('foomanconnect/item')->ensureItemsExist($invoiceData, $invoice->getStoreId());
                $result = $this->sendToXero($dataSource->getXml($invoiceData), $invoice->getStoreId());
                if (!isset($result['Invoices'][0]['InvoiceID']) || empty($result['Invoices'][0]['InvoiceID'])) {
                    throw new Fooman_Connect_Exception('No Invoice Id received');
                }
                $invoiceStatus->setXeroInvoiceId($result['Invoices'][0]['InvoiceID']);
                $invoiceStatus->setXeroInvoiceNumber($result['Invoices'][0]['InvoiceNumber']);
                $invoiceStatus->setXeroExportStatus(Fooman_Connect_Model_Status::EXPORTED);
                $invoiceStatus->setXeroLastValidationErrors('');

                if ($invoiceData['status'] == Fooman_Connect_Model_System_InitialInvoiceStatus::AUTHORISED
                    && Mage::getStoreConfigFlag('foomanconnect/order/payment', $invoice->getStoreId())
                    && $invoice->getGrandTotal() > 0
                ) {
                    $data = array();
                    $data['xero_id'] = $invoiceStatus->getXeroInvoiceId();
                    if ($invoice->getOrder()->getPayment()->getMethod() === 'bankpayment') {
                        $data['account_id'] = Mage::getStoreConfig(
                            'foomanconnect/order/bankpaymentbankaccount', $invoice->getStoreId()
                        );
                    } elseif ($invoice->getOrder()->getPayment()->getMethod() === 'cashondelivery') {
                        $data['account_id'] = Mage::getStoreConfig(
                            'foomanconnect/order/codbankaccount', $invoice->getStoreId()
                        );
                    } elseif (strpos($invoice->getOrder()->getPayment()->getMethod(), 'paypal') !== false) {
                        $data['account_id'] = Mage::getStoreConfig(
                            'foomanconnect/order/paypalbankaccount', $invoice->getStoreId()
                        );
                    } else {
                        $data['account_id'] = Mage::getStoreConfig(
                            'foomanconnect/order/bankaccount', $invoice->getStoreId()
                        );
                    }
                    $data['status'] = 'AUTHORISED';
                    $data['reference'] = $invoice->getOrder()->getPayment()->getLastTransId();
                    $data['date'] = $invoiceData['createdAt'];
                    $data['amount'] = $result['Invoices'][0]['AmountDue'];
                    $this->sendPaymentToXero(
                        Fooman_ConnectLicense_Model_DataSource_Converter_PaymentsXml::convert(array('Invoices' => array($data))),
                        $invoice->getStoreId()
                    );
                    $invoiceStatus->setPaymentExportStatus(Fooman_Connect_Model_Status::EXPORTED);
                }
                $invoiceStatus->save();
                return $result;
            } catch (Fooman_Connect_TemporaryException $e) {
                //Only log will reattempt
                Mage::logException($e);
                Mage::throwException(
                    sprintf(
                        '%s: Export did not succeed %s. This could be a temporary issue - please try again.',
                        $invoice->getIncrementId(), $e->getMessage()
                    )
                );
            } catch (Fooman_Connect_Exception $e) {
                $xeroErrors = $e->getXeroErrors();
                if ((in_array('This document cannot be edited as it has a payment or credit note allocated to it.', $xeroErrors)
                        || in_array('Invoice not of valid status for modification', $xeroErrors))
                    && $invoiceStatus->getXeroExportStatus(Fooman_Connect_Model_Status::EXPORTED)
                ) {
                    $message = '<br/>Errors received from Xero: ' . implode('<br/>', $e->getXeroErrors());
                    Mage::throwException(sprintf('%s: Export did not succeed %s', $invoice->getIncrementId(), $message));
                } else {
                    $this->_handleError($invoiceStatus, $e, $e->getXeroErrors(), $invoice, $invoiceData);
                }
            } catch (Exception $e) {
                $this->_handleError($invoiceStatus, $e, $e->getMessage(), $invoice, $invoiceData);
            }
        }
    }

    public function exportInvoices()
    {
        $stores = array_keys(Mage::app()->getStores());
        foreach ($stores as $storeId) {
            if (Mage::getStoreConfigFlag('foomanconnect/cron/xeroautomatic', $storeId)) {
                $collection = $this->getCollection()->getUnexportedOrders($storeId)->setPageSize(self::PROCESS_PER_RUN);
                $collection->getSelect()->order('created_at DESC');
                $collection->addConfigDateFilter($storeId);
                foreach ($collection as $invoice) {
                    /** @var $invoice Fooman_Connect_Model_Invoice */
                    try {
                        $this->exportByInvoiceId($invoice->getEntityId());
                    } catch (Exception $e) {
                        //don't stop cron execution
                        //exception has already been logged
                    }
                }
            }
        }
    }

    /**
     * Depending on the invoice status in Xero we either can DELETE the invoice or if already authorised we need to VOID
     * as we don't know the status we could either query first and then take the appropriate action (always 2 requests)
     * or try the delete first and if rejected send the void request (sometimes 2 requests)
     *
     * @param $invoiceId
     */
    public function deleteOrVoidOne($invoiceId)
    {
        $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);
        $invoiceStatus = $this->load($invoiceId, 'invoice_id');
        if ($invoiceStatus->getXeroInvoiceNumber() && $invoiceStatus->getXeroExportStatus() == Fooman_Connect_Model_Status::EXPORTED) {
            try {
                $this->sendToXero(
                    Fooman_ConnectLicense_Model_DataSource_Converter_OrderCancelXml::convert($invoiceStatus->getXeroInvoiceNumber(), 'DELETED'),
                    $invoice->getStoreId()
                );

                $invoiceStatus->setXeroExportStatus(Fooman_Connect_Model_Status::EXPORTED_AND_DELETED);
                $invoiceStatus->save();
            } catch (Exception $e) {
                try {
                    $this->sendToXero(
                        Fooman_ConnectLicense_Model_DataSource_Converter_OrderCancelXml::convert($invoiceStatus->getXeroInvoiceNumber(), 'VOIDED'),
                        $invoice->getStoreId()
                    );
                    $invoiceStatus->setXeroExportStatus(Fooman_Connect_Model_Status::EXPORTED_AND_VOIDED);
                    $invoiceStatus->save();
                } catch (Exception $e) {
                    Mage::logException($e);
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
        return $this->_getSalesEntityViewId('invoice');
    }
}
