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


class Plumrocket_Newsletterpopup_Adminhtml_Newsletterpopup_TemplatesController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();

        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('newsletterpopup')->__('Manage Newsletter Themes'));
    }

    public function editAction()
    {
        $result = false;

        if ($id = $this->getRequest()->getParam('id')) {
            $template = Mage::getModel('newsletterpopup/template')->load($id);
            if ($template->getId()) {
                Mage::register('template', $template);
                $result = true;
            }
        }

        if ($result) {
            $this->loadLayout();
            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('newsletterpopup')->__('This Theme no longer exists.'));
            $this->_redirect('*/*/index');
        }
    }

    public function newAction()
    {
        Mage::register('template', Mage::getModel('newsletterpopup/template'));

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Save action
     */
    public function saveAction()
    {
        $id = 0;
        // check if data sent
        if (($data = $this->getRequest()->getPost())) {
            $data = $this->_filterPostData($data);

            if(!isset($data['code']) && !empty($data['code_base64'])) {
                $data['code'] = base64_decode($data['code_base64']);
            }
            if(!isset($data['style']) && !empty($data['style_base64'])) {
                $data['style'] = base64_decode($data['style_base64']);
            }

            //validating
            if (!$this->_validatePostData($data)) {
                $this->_redirect('*/*/index', array('_current' => true));
                return;
            }

            $model = (isset($data['entity_id']) && ($data['entity_id'] > 0)) ?
                 Mage::getModel('newsletterpopup/template')->load($data['entity_id']):
                 Mage::getModel('newsletterpopup/template');
            $model->setData($data);
            // try to save it
            try {
                $model->save();
                $id = $model->getId();

                // Clean cache for popups.
                $popups = Mage::getSingleton('newsletterpopup/popup')
                    ->getCollection()
                    ->addFieldToFilter('template_id', $id);

                foreach ($popups as $popup) {
                    $popup->cleanCache();
                }

                $model->generateThumbnail();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('newsletterpopup')->__('The Theme has been saved.'));
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('newsletterpopup')->__($e->getMessage()));
            } catch (Exception $e) {
                $this->_getSession()->addError($e,
                    Mage::helper('newsletterpopup')->__($e->getMessage()));
            }
        }

        if ($this->getRequest()->getParam('back') && $id > 0) {
            $this->_redirect('*/*/edit', array('id' => $id, 'active_tab' => $this->getRequest()->getParam('active_tab', 'general_section')));
            return;
        }

        $this->_redirect('*/*/index', array('_current' => true));
    }

    public function duplicateAction()
    {
        if (($id = (int)$this->getRequest()->getParam('id'))) {
            try {
                $newId = $this->_duplicate($id);
                if ($newId && ($id != $newId)) {
                    $id = $newId;
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('newsletterpopup')->__('The Theme has been duplicated.'));
                }
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('newsletterpopup')->__($e->getMessage()));
            } catch (Exception $e) {
                $this->_getSession()->addError($e,
                    Mage::helper('newsletterpopup')->__($e->getMessage()));
            }
        }
        $this->_redirect('*/*/edit', array('id' => $id));
    }

    protected function _duplicate($id)
    {
        $orig = Mage::getModel('newsletterpopup/template')->load($id);
        if ($orig->getId()) {
            $clone = clone $orig;

            $cloneData = $clone->getData();
            $cloneData['name'] .= Mage::helper('newsletterpopup')->__(' (duplicate)');
            unset($cloneData['entity_id']);

            $clone->setData($cloneData);
            $clone->save();
            $id = $clone->getId();
        }
        return $id;
    }

    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            if($this->_delete($id)) {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('newsletterpopup')->__('The Theme has been deleted.'));
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('newsletterpopup')->__('The Theme has not been deleted.'));
        }
        $this->_redirect('*/*/index', array('_current' => true));
    }

    protected function _delete($id)
    {
        return Mage::getModel('newsletterpopup/template')->load($id)->delete()->isDeleted();
    }

    /**
     * Check the permission to run it
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('plumrocket/newsletterpopup/manage_templates');
    }


    /**
     * Filtering posted data. Converting localized data if needed
     *
     * @param array
     * @return array
     */
    protected function _filterPostData($data)
    {
        if (isset($data['stores'])) {
            if (in_array(0, $data['stores'])) {
                $data['store_id'] = '0';
            } else {
                $data['store_id'] = implode(',', $data['stores']);
            }
        }

        if (isset($data['entity_id']) && empty($data['entity_id'])) {
            unset($data['entity_id']);
        }

        return $data;
    }
    /**
     * Validate post data
     *
     * @param array $data
     * @return bool     Return FALSE if someone item is invalid
     */
    protected function _validatePostData($data)
    {
        return true;
    }

    public function massAction()
    {
        $action = $this->getRequest()->getParam('action');
        $ids = $this->getRequest()->getParam('popup_id');

        if (is_array($ids) && $ids) {
            try {
                foreach ($ids as $n => $id) {
                    switch ($action) {
                        case 'delete':
                            if(!$this->_delete($id)) {
                                unset($ids[$n]);
                                if(empty($_sendNotification)) {
                                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('newsletterpopup')->__('One or more themes were not removed. They are either "default themes" or being used in popups at the moment.'));
                                    $_sendNotification = true;
                                }
                            }
                            break;
                        case 'duplicate':
                            $this->_duplicate($id);
                            break;
                    }
                }
                $messages = array(
                    'delete'    => 'Total of %s record(s) were successfully deleted',
                    'duplicate' => 'Total of %s record(s) were successfully duplicated',
                );

                if($ids) {
                    Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('newsletterpopup')->__($messages[$action], count($ids))
                    );
                }
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
}
