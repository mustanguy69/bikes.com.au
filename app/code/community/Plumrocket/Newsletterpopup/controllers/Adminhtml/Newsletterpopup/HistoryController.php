<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * @package     Plumrocket_Newsletterpopup
 * @copyright   Copyright (c) 2017 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */


class Plumrocket_Newsletterpopup_Adminhtml_Newsletterpopup_HistoryController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function massAction()
    {
        $action = $this->getRequest()->getParam('action');
        $ids = $this->getRequest()->getParam('history_id');

        if (is_array($ids) && $ids) {
            try {
                foreach ($ids as $id) {
                    $model = Mage::getModel('newsletterpopup/history')->load($id);
                    switch ($action) {
                        case 'delete':
                            $model->delete();
                            break;
                    }
                }
                $messages = array(
                    'delete'    => 'Total of %s record(s) were successfully deleted'
                );

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('newsletterpopup')->__($messages[$action], count($ids))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('newsletterpopup')->__($e->getMessage()));
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('newsletterpopup')->__('Please select item(s)'));
        }
        $this->_redirect('*/*/index');
    }

    public function exportCsvAction()
    {
        $fileName   = 'newsletterpopup_history.csv';
        $content    = $this->getLayout()->createBlock('newsletterpopup/adminhtml_history_grid')
            ->getCsvFile();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName   = 'newsletterpopup_history.xml';
        $content    = $this->getLayout()->createBlock('newsletterpopup/adminhtml_history_grid')
            ->getExcelFile();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('plumrocket/newsletterpopup/manage_history');
    }
}
