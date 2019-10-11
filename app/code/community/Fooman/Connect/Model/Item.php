<?php

/*
 * @author     Kristof Ringleff
 * @package    Fooman_Connect
 * @copyright  Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Fooman_Connect_Model_Item extends Fooman_Connect_Model_Abstract
{
    const ITEM_CODE_MAX_LENGTH = 30;
    const NAME_MAX_LENGTH = 50;

    protected function _construct()
    {
        $this->_init('foomanconnect/item');
    }

    public function getId()
    {
        return $this->getItemCode();
    }

    public function ensureItemsExist($data, $storeId)
    {
        $this->queueSkus($data, $storeId);
        $this->exportItems($storeId);
    }

    public function queueSkus($data, $storeId)
    {
        foreach ($data['invoiceLines'] as $line) {
			if($line['itemCode'] === "rewardpoints_earn") {
				continue;
			}
            if (isset($line['itemCode'])) {
                $this->unsetData();
                $this->load($line['itemCode']);
                if ($this->getXeroExportStatus() != Fooman_Connect_Model_Status::EXPORTED) {
                    $this->setStoreId($storeId);
                    $this->setItemCode(trim(str_replace("\0", "", $line['itemCode'])));
                    $this->setDescription(trim(str_replace("\0", "", $line['name'])));
                    //$this->setName(substr($line['name'], 0, self::NAME_MAX_LENGTH));
                    $this->save();
                }
            }
        }
    }

    public function exportProducts()
    {

        $stores = Mage::app()->getStores();
        foreach ($stores as $store) {
            $this->queueProducts($store);
            $this->exportItems($store->getStoreId());
        }
    }

    public function queueProducts($store)
    {
        $collection = Mage::getModel('catalog/product')
            ->getCollection()
            ->setStore($store)
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('xero_item_code')
            ->addAttributeToFilter('type_id', array('in' => array('simple', 'virtual', 'downloadable')));
        foreach ($collection as $product) {
            $itemCode = $product->getXeroItemCode() ? $product->getXeroItemCode() : $product->getSku();
            if (strlen($itemCode) < self::ITEM_CODE_MAX_LENGTH) {
                $this->setData(array());
                $this->setStoreId($store->getStoreId());
                $this->setItemCode($itemCode);
                $this->setDescription(substr($product->getName(), 0, self::NAME_MAX_LENGTH));
                $this->save();
            }
        }
    }

    public function exportItemsForAllStores()
    {
        $stores = array_keys(Mage::app()->getStores());
        foreach ($stores as $storeId) {
            if (Mage::getStoreConfigFlag('foomanconnect/cron/xeroautomatic', $storeId)) {
                try {
                    $this->exportItems($storeId);
                } catch (Exception $e) {
                    //don't stop cron execution
                    //exception has already been logged
                }
            }
        }
    }

    public function importItemsForAllStores()
    {
        $stores = array_keys(Mage::app()->getStores());
        $done = array();
        foreach ($stores as $storeId) {
            $key = Mage::helper('core')->decrypt(
                Mage::getStoreConfig('foomanconnect/settings/consumerkey', $storeId)
            );
            if (!isset($done[$key])) {
                $this->importFromXero($storeId);
                $done[$key] = true;
            }
        }
    }

    public function exportItems($storeId)
    {
        $collection = $this->getCollection()->setPageSize(300);
        $collection->getUnexportedItems();

        $collection->addFieldToFilter('store_id', $storeId);

        if (count($collection)) {
            $data = $collection->toArray();
            try {
                $result = $this->sendToXero(
                    Fooman_ConnectLicense_Model_DataSource_Converter_ItemsXml::convert($data['items']),
                    $storeId
                );
                foreach ($result['Items'] as $item) {
                    $this->unsetData();
                    $this->setItemCode($item['Code']);
                    $this->setDescription($item['Description']);
                    $this->setXeroItemId($item['ItemID']);
                    $this->setXeroExportStatus(Fooman_Connect_Model_Status::EXPORTED);
                    $this->save();
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
            if (count($collection) == 300) {
                throw new Fooman_Connect_TemporaryException('Exported 300 items, but more need to export. Please rerun the previous action.');
            }
        }
    }

    public function importFromXero($storeId)
    {
        try {
            $result = $this->getFromXero(
                $storeId
            );
            foreach ($result['Items'] as $item) {
                $this->unsetData();
                $this->setItemCode($item['Code']);
                $this->setDescription($item['Description']);
                $this->setXeroItemId($item['ItemID']);
                $this->setXeroExportStatus(Fooman_Connect_Model_Status::EXPORTED);
                $this->save();
            }
        } catch (Exception $e) {
            Mage::logException($e);
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
            Fooman_Connect_Model_Xero_Api::ITEMS, Zend_Http_Client::POST, $xml
        );
    }

    /**
     * @param $storeId
     *
     * @return array
     */
    public function getFromXero($storeId)
    {
        return $this->getApi()->setStoreId($storeId)->sendData(
            Fooman_Connect_Model_Xero_Api::ITEMS
        );
    }
}
