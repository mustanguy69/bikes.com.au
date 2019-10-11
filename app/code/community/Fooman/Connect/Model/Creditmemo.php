<?php

/*
 * @author     Kristof Ringleff
 * @package    Fooman_Connect
 * @copyright  Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Fooman_Connect_Model_Creditmemo extends Fooman_Connect_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('foomanconnect/creditmemo');
    }

    /**
     * @param int $creditmemoId
     *
     * @return array
     */
    public function exportByCreditmemoId($creditmemoId)
    {
        $creditmemo = Mage::getModel('sales/order_creditmemo')->load($creditmemoId);
        return $this->exportOne($creditmemo);
    }

    /**
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     *
     * @return array
     */
    public function exportOne(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $creditmemoStatus = $this->load($creditmemo->getId(), 'creditmemo_id');
        if (!$creditmemoStatus->getCreditmemoId()) {
            $creditmemoStatus->isObjectNew(true);
            $creditmemoStatus->setCreditmemoId((int)$creditmemo->getId());
        }
        if (0 == $creditmemo->getBaseGrandTotal()) {
            $creditmemoStatus->setXeroExportStatus(Fooman_Connect_Model_Status::WONT_EXPORT);
            $creditmemoStatus->setXeroLastValidationErrors('');
            $creditmemoStatus->save();
        } else {
            try {
                $creditmemoData = array();
                $dataSource     = Mage::getModel('foomanconnect/dataSource_creditmemo', array('creditmemo' => $creditmemo));
                $creditmemoData = $dataSource->getCreditmemoData();
                Mage::getModel('foomanconnect/item')->ensureItemsExist($creditmemoData, $creditmemo->getStoreId());
                $result = $this->sendToXero($dataSource->getXml($creditmemoData), $creditmemo->getStoreId());
                if (!isset($result['CreditNotes'][0]['CreditNoteID'])
                    || empty($result['CreditNotes'][0]['CreditNoteID'])
                ) {
                    throw new Fooman_Connect_Exception('No Creditmemo Id received');
                }
                $creditmemoStatus->setXeroCreditnoteId($result['CreditNotes'][0]['CreditNoteID']);
                $creditmemoStatus->setXeroCreditnoteNumber($result['CreditNotes'][0]['CreditNoteNumber']);
                $creditmemoStatus->setXeroExportStatus(Fooman_Connect_Model_Status::EXPORTED);
                $creditmemoStatus->setXeroLastValidationErrors('');
                $creditmemoStatus->save();
                if (Mage::getStoreConfigFlag('foomanconnect/creditmemo/cashrefund', $creditmemo->getStoreId())) {
                    $data = array();
                    $data['xero_id'] = $creditmemoStatus->getXeroCreditnoteId();
                    if (strpos($creditmemo->getOrder()->getPayment()->getMethod(), 'paypal') !== false) {
                        $data['account_id'] = Mage::getStoreConfig(
                            'foomanconnect/creditmemo/paypalrefundbankaccount', $creditmemo->getStoreId()
                        );
                    } else {
                        $data['account_id'] = Mage::getStoreConfig(
                            'foomanconnect/creditmemo/cashrefundbankaccount', $creditmemo->getStoreId()
                        );
                    }
                    $data['date'] = $creditmemoData['createdAt'];
                    $data['amount'] = $creditmemoData['grandTotal'];
                    $this->sendPaymentToXero(
                        Fooman_ConnectLicense_Model_DataSource_Converter_PaymentsXml::convert(array('CreditNotes' => array($data))),
                        $creditmemo->getStoreId()
                    );
                }
                return $result;
            } catch (Fooman_Connect_TemporaryException $e) {
                //Only log will reattempt
                Mage::logException($e);
                Mage::throwException(
                    sprintf(
                        '%s: Export did not succeed %s. This could be a temporary issue - please try again.',
                        $creditmemo->getIncrementId(), $e->getMessage()
                    )
                );
            } catch (Fooman_Connect_Exception $e) {
                $xeroErrors = $e->getXeroErrors();
                if ((in_array('This document cannot be edited as it has a payment or credit note allocated to it.', $xeroErrors)
                        || in_array('Invoice not of valid status for modification', $xeroErrors))
                    && $creditmemoStatus->getXeroExportStatus(Fooman_Connect_Model_Status::EXPORTED)
                ) {
                    $message = '<br/>Errors received from Xero: ' . implode('<br/>', $e->getXeroErrors());
                    Mage::throwException(sprintf('%s: Export did not succeed %s', $creditmemo->getIncrementId(), $message));
                } else {
                    $this->_handleError($creditmemoStatus, $e, $e->getXeroErrors(), $creditmemo, $creditmemoData);
                }
            } catch (Exception $e) {
                $this->_handleError($creditmemoStatus, $e, $e->getMessage(), $creditmemo, $creditmemoData);
            }
        }
    }

    public function exportCreditmemos()
    {
        $stores = array_keys(Mage::app()->getStores());
        foreach ($stores as $storeId) {
            if (Mage::getStoreConfigFlag('foomanconnect/cron/xeroautomatic', $storeId)) {
                /** @var Fooman_Connect_Model_Resource_Creditmemo_Collection $collection */
                $collection = $this->getCollection()->getUnexportedCreditmemos($storeId)->setPageSize(
                    self::PROCESS_PER_RUN
                );
                $collection->getSelect()->order('created_at DESC');
                $collection->addConfigDateFilter($storeId);
                foreach ($collection as $creditmemo) {
                    /** @var $creditmemo Fooman_Connect_Model_Creditmemo */
                    try {
                        $this->exportByCreditmemoId($creditmemo->getEntityId());
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
            Fooman_Connect_Model_Xero_Api::CREDITNOTES_PATH, Zend_Http_Client::POST, $xml
        );
    }

    /**
     * @return bool|string
     */
    public function getSalesEntityViewId()
    {
        return $this->_getSalesEntityViewId('creditmemo');
    }
}
