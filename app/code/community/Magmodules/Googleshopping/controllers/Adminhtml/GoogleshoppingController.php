<?php
/**
 * Magmodules.eu - http://www.magmodules.eu.
 *
 * NOTICE OF LICENSE
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.magmodules.eu/MM-LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magmodules.eu so we can send you a copy immediately.
 *
 * @category      Magmodules
 * @package       Magmodules_Googleshopping
 * @author        Magmodules <info@magmodules.eu>
 * @copyright     Copyright (c) 2017 (http://www.magmodules.eu)
 * @license       https://www.magmodules.eu/terms.html  Single Service License
 */

class Magmodules_Googleshopping_Adminhtml_GoogleshoppingController extends Mage_Adminhtml_Controller_Action
{

    /**
     *
     */
    public function generateManualAction()
    {
        if (Mage::getStoreConfig('googleshopping/general/enabled')) {
            $storeId = $this->getRequest()->getParam('store_id');
            if (!empty($storeId)) {
                $timeStart = microtime(true);
                $appEmulation = Mage::getSingleton('core/app_emulation');
                $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
                if ($result = Mage::getModel('googleshopping/googleshopping')->generateFeed($storeId, $timeStart)) {
                    $html = '<a href="' . $result['url'] . '" target="_blank">' . $result['url'] . '</a><br/><small>Date: ' . $result['date'] . ' (manual) - Products: ' . $result['qty'] . ' - Time: ' . number_format((microtime(true) - $timeStart), 4) . '</small>';
                    $config = new Mage_Core_Model_Config();
                    $config->saveConfig('googleshopping/generate/feed_result', $html, 'stores', $storeId);
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('googleshopping')->__('Generated feed with %s products. %s', $result['qty'], '<a  style="float:right;" href="' . $this->getUrl('*/googleshopping/download/store_id/' . $storeId) . '">Download XML</a>'));
                    $limit = Mage::getStoreConfig('googleshopping/generate/limit', $storeId);
                    if ($limit > 0) {
                        Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('googleshopping')->__('Note, in the feed generate configuration tab you have enabled the product limit of %s.', $limit));
                    }
                } else {
                    $config = new Mage_Core_Model_Config();
                    $config->saveConfig('googleshopping/generate/feed_result', '', 'stores', $storeId);
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('googleshopping')->__('No products found, make sure your filters are configured with existing values.'));
                }

                $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('googleshopping')->__('Please enable the extension before generating the xml'));
        }

        $this->_redirect('adminhtml/system_config/edit/section/googleshopping');
    }

    /**
     *
     */
    public function addToFlatAction()
    {
        $nonFlatAttributes = Mage::helper('googleshopping')->checkFlatCatalog(Mage::getModel("googleshopping/googleshopping")->getFeedAttributes());
        foreach ($nonFlatAttributes as $key => $value) {
            Mage::getModel('catalog/resource_eav_attribute')->load($key)->setUsedInProductListing(1)->save();
        }

        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('googleshopping')->__('Attributes added to Flat Catalog, please reindex Product Flat Data.'));
        $this->_redirect('adminhtml/system_config/edit/section/googleshopping');
    }

    /**
     *
     */
    public function downloadAction()
    {
        $storeId = $this->getRequest()->getParam('store_id');
        $filepath = Mage::getModel('googleshopping/googleshopping')->getFileName('googleshopping', $storeId, 0);
        if (file_exists($filepath)) {
            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Pragma', 'no-cache', 1)
                ->setHeader('Content-type', 'application/force-download')
                ->setHeader('Content-Length', filesize($filepath))
                ->setHeader('Content-Disposition', 'attachment' . '; filename=' . basename($filepath));
            $this->getResponse()->clearBody();
            $this->getResponse()->sendHeaders();
            readfile($filepath);
        }
    }

    /**
     * @return mixed
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/googleshopping/googleshopping');
    }

}