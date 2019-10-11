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


class Plumrocket_Newsletterpopup_Adminhtml_Newsletterpopup_PopupsController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();

        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('newsletterpopup')->__('Manage Newsletter Popups'));
    }

    public function editAction()
    {
        $result = false;

        if ($id = $this->getRequest()->getParam('id')) {
            $popup = Mage::getModel('newsletterpopup/popup')->load($id);
            if ($popup->getId()) {
                $popup->getConditions()->setJsFormObject('rule_conditions_fieldset');
                $popup->getConditions()->setJsFormObject('popup_conditions_fieldset');
                Mage::register('popup', $popup);
                $result = true;
            }
        }

        if ($result) {
            $this->loadLayout();
            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('newsletterpopup')->__('This Popup no longer exists.'));
            $this->_redirect('*/*/index');
        }
    }

    public function newAction()
    {
        Mage::register('popup', Mage::getModel('newsletterpopup/popup'));

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
            $mailchimpData = $data['mailchimp_list'];
            $fieldsData = $data['signup_fields'];
            unset($data['mailchimp_list']);
            unset($data['signup_fields']);

            $model = (isset($data['entity_id']) && ($data['entity_id'] > 0)) ?
                 Mage::getModel('newsletterpopup/popup')->load($data['entity_id']) :
                 Mage::getModel('newsletterpopup/popup');

            $session = Mage::getSingleton('adminhtml/session');

            if (isset($data['rule']['conditions'])) {
                $data['conditions'] = $data['rule']['conditions'];
            }
            if (isset($data['rule']['actions'])) {
                $data['actions'] = $data['rule']['actions'];
            }
            unset($data['rule']);
            $model->loadPost($data);

            // try to save it
            try {
                $model->save();
                $model->cleanCache();
                $id = $model->getId();

                $this->_saveMailChimpList($mailchimpData, $id);
                $this->_saveFormFields($fieldsData, $id);

                $model->generateThumbnail();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('newsletterpopup')->__('The Popup has been saved.')
                );
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('newsletterpopup')->__($e->getMessage())
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e,
                    Mage::helper('newsletterpopup')->__($e->getMessage())
                );
            }
        }

        if ($this->getRequest()->getParam('back') && $id > 0) {
            $this->_redirect('*/*/edit', array('id' => $id, 'active_tab' => $this->getRequest()->getParam('active_tab', 'general_section')));
            return;
        }

        $this->_redirect('*/*/index', array('_current' => true));
    }

    protected function _saveMailChimpList($data, $popup_id)
    {
        if (!Mage::helper('newsletterpopup/adminhtml')->isMaichimpEnabled()) {
            return false;
        }
        $collectionData = Mage::helper('newsletterpopup')->getPopupMailchimpList($popup_id, false);
        $mailchimpList = Mage::getSingleton('newsletterpopup/values_mailchimplist')->toOptionHash();

        foreach ($mailchimpList as $key => $name) {
            if (array_key_exists($key, $data)) {
                if (array_key_exists($key, $collectionData)) {
                    $list = $collectionData[$key];
                } else {
                    $list = Mage::getModel('newsletterpopup/mailchimpList');
                    $list->setData('popup_id', $popup_id);
                    $list->setData('name', $key);
                }
                $list->setData('label', $data[$key]['label']);
                $list->setData('enable', (int)isset($data[$key]['enable']));
                $list->setData('sort_order', (int)$data[$key]['sort_order']);
                $list->save();
            }
        }

        return true;
    }

    protected function _saveFormFields($data, $popup_id)
    {
        if (!$popup_id) {
            return false;
        }
        // Email is require field
        if (isset($data['email'])) {
            $data['email']['enable'] = 1;
        }
        // If Confirmation is enabled but Password not then enable Password
        if (isset($data['confirm_password'])
            && isset($data['confirm_password']['enable'])
            && isset($data['password'])
            && !isset($data['password']['enable'])
        ) {
            $data['password']['enable'] = 1;
        }

        $systemItemsKeys = Mage::helper('newsletterpopup')->getPopupFormFieldsKeys(0, false);
        $popupItems = Mage::helper('newsletterpopup')->getPopupFormFields($popup_id, false);

        foreach ($systemItemsKeys as $name) {
            if (array_key_exists($name, $data)) {
                if (array_key_exists($name, $popupItems)) {
                    $field = $popupItems[$name];
                } else {
                    $field = Mage::getModel('newsletterpopup/formField');
                    $field->setData('popup_id', $popup_id);
                    $field->setData('name', $name);
                }
                $field->setData('label', $data[$name]['label']);
                $field->setData('enable', (int)isset($data[$name]['enable']));
                $field->setData('sort_order', (int)$data[$name]['sort_order']);
                $field->save();
            }
        }

        return true;
    }

    public function duplicateAction()
    {
        if ($id = (int)$this->getRequest()->getParam('id')) {
            try {
                $newId = $this->_duplicate($id);
                if ($newId && ($id != $newId)) {
                    $id = $newId;
                    Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('newsletterpopup')->__('The Popup has been duplicated.')
                    );
                }
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('newsletterpopup')->__($e->getMessage())
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e,
                    Mage::helper('newsletterpopup')->__($e->getMessage())
                );
            }
        }
        $this->_redirect('*/*/edit', array('id' => $id));
    }

    protected function _duplicate($id)
    {
        $orig = Mage::getModel('newsletterpopup/popup')->load($id);
        if ($orig->getId()) {
            $clone = clone $orig;

            $cloneData = $clone->getData();
            $cloneData['status'] = 0;
            $cloneData['name'] .= Mage::helper('newsletterpopup')->__(' (duplicate)');
            $cloneData['views_count'] = 0;
            $cloneData['subscribers_count'] = 0;
            $cloneData['orders_count'] = 0;
            $cloneData['total_revenue'] = 0;
            unset($cloneData['entity_id']);

            $clone->setData($cloneData);
            $clone->save();

            $oldId = $id;
            $id = $clone->getId();

            $dublicatedTable = array('newsletterpopup_mailchimp_list', 'newsletterpopup_form_fields');
            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
            foreach ($dublicatedTable as $tableName) {
                $tableName = Mage::getSingleton('core/resource')->getTableName($tableName);
                $connection->query(sprintf("INSERT INTO %s (`popup_id`, `name`, `label`, `enable`, `sort_order`)
                        SELECT %u, `name`, `label`, `enable`, `sort_order`
                        FROM %s
                        WHERE `popup_id` = %u;",
                    $tableName,
                    $id,
                    $tableName,
                    $oldId
                ));
            }
        }

        return $id;
    }

    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            $this->_delete($id);

            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('newsletterpopup')->__('The Popup has been deleted.'));
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('newsletterpopup')->__('The Popup has not been deleted.'));
        }
        $this->_redirect('*/*/index', array('_current' => true));
    }

    protected function _delete($id)
    {
        Mage::getModel('newsletterpopup/popup')->load($id)->delete();
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');

        $connection->query(sprintf("DELETE FROM %s WHERE `popup_id` = '%u'",
            Mage::getSingleton('core/resource')->getTableName('newsletterpopup_history'),
            $id
        ));

        $connection->query(sprintf("DELETE FROM %s WHERE `popup_id` = '%u'",
            Mage::getSingleton('core/resource')->getTableName('newsletterpopup_mailchimp_list'),
            $id
        ));

        $connection->query(sprintf("DELETE FROM %s WHERE `popup_id` = '%u'",
            Mage::getSingleton('core/resource')->getTableName('newsletterpopup_form_fields'),
            $id
        ));

        $connection->query(sprintf("DELETE FROM %s WHERE `popup_id` = '%u'",
            Mage::getSingleton('core/resource')->getTableName('newsletterpopup_hold'),
            $id
        ));
    }

    /**
     * Check the permission to run it
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('plumrocket/newsletterpopup/manage_popups');
    }

    /**
     * Prepare extended time fields that was passing in POST data
     *
     * @param  array $data
     * @param  string|null $fieldName
     * @return self
     */
    protected function _prepareExtendedTime(&$data, $fieldName)
    {
        if (isset($data[$fieldName]) && is_array($data[$fieldName])) {
            $offset = Mage::helper('newsletterpopup')->getOffsetFromExtendedTime(
                $data[$fieldName],
                $fieldName
            );

            $data[$fieldName] = !$offset
                ? null
                : implode(',', $data[$fieldName]);
        }

        return $this;
    }

    /**
     * Filtering posted data. Converting localized data if needed
     *
     * @param array
     * @return array
     */
    protected function _filterPostData($data)
    {
        $this->_prepareExtendedTime($data, 'cookie_time_frame');
        $this->_prepareExtendedTime($data, 'coupon_expiration_time');

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

    	$dateFormat = Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
		if (isset($data['start_date']) && $data['start_date'] != null) {
			$value = new Zend_Date($data['start_date'], $dateFormat, 'en');
			$data['start_date'] = strftime('%Y-%m-%d %H:%M:%S', $value->get());
        }

        if (isset($data['end_date']) && $data['end_date'] != null) {
			$value = new Zend_Date($data['end_date'], $dateFormat, 'en');
			$data['end_date'] = strftime('%Y-%m-%d %H:%M:%S', $value->get());
        }

        if (!isset($data['mailchimp_list'])) {
            $data['mailchimp_list'] = array();
        }

        if (!isset($data['signup_fields'])) {
            $data['signup_fields'] = array();
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
                foreach ($ids as $id) {
                    switch ($action) {
                        case 'enable':
                            $model = Mage::getModel('newsletterpopup/popup')->load($id);
                            $model->setStatus('1')
                                ->save();
                            break;
                        case 'disable':
                            $model = Mage::getModel('newsletterpopup/popup')->load($id);
                            $model->setStatus('0')
                                ->save();
                            break;
                        case 'delete':
                            $this->_delete($id);
                            break;
                        case 'duplicate':
                            $this->_duplicate($id);
                            break;
                    }
                }
                $messages = array(
                    'enable'    => 'Total of %s record(s) were successfully enabled',
                    'disable'   => 'Total of %s record(s) were successfully disabled',
                    'delete'    => 'Total of %s record(s) were successfully deleted',
                    'duplicate' => 'Total of %s record(s) were successfully duplicated',
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

    public function newConditionHtmlAction()
    {
        $id = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        $model = Mage::getModel($type)
            ->setId($id)
            ->setType($type)
            ->setRule(Mage::getModel('newsletterpopup/popup'))
            ->setPrefix('conditions');
        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof Mage_Rule_Model_Condition_Abstract) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }

    public function loadTemplateAction()
    {
        $data = array();
        if($id = $this->getRequest()->getParam('id')) {
            if($template = Mage::helper('newsletterpopup')->getPopupTemplateById($id)) {
                $data = $template->getData();
            }
        }
        $this->getResponse()->setBody(json_encode($data));
    }
}
