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


class Plumrocket_Newsletterpopup_Block_Preview_Popup extends Plumrocket_Newsletterpopup_Block_Popup
{
    protected $_popup = NULL;

    public function getPopup()
    {
        if (is_null($this->_popup)) {
            $request = $this->getRequest();

            $id = (int)$request->getParam('id');
            if (!$id) {
                $id = (int)$request->getParam('entity_id');
            }

            if($request->getParam('is_template')) {
                $this->_popup = Mage::helper('newsletterpopup')->getPopupTemplateById($id);
            }else{
                $this->_popup = Mage::helper('newsletterpopup')->getPopupById($id);
            }

            $data = $request->getPost();
            if(!isset($data['code']) && !empty($data['code_base64'])) {
                $request->setParam('code', base64_decode($data['code_base64']));
            }
            if(!isset($data['style']) && !empty($data['style_base64'])) {
                $request->setParam('style', base64_decode($data['style_base64']));
            }

            $fields = array(
                'animation',
                'text_title',
                'signup_fields',
                'subscription_mode',
                'mailchimp_list',
                'text_description',
                'text_success',
                'text_submit',
                'text_cancel',

                'name',
                'code',
                'style',
            );

            foreach ($fields as $field) {
                $val = $request->getParam($field);
                if ($val) {
                    if ($field == 'mailchimp_list') {
                        $this->_popup->setData('custom_' . $field, $this->_loadMailChimpList($val));
                    } else if ($field == 'signup_fields') {
                        $this->_popup->setData('custom_' . $field, $this->_loadFormFields($val));
                    } else {
                        $this->_popup->setData($field, $val);
                    }
                }
            }
        }

        return $this->_popup;
    }

    protected function _loadMailChimpList($data)
    {
        $mailchimpList = Mage::getSingleton('newsletterpopup/values_mailchimplist')->toOptionHash();
        return $this->_loadData($data, $mailchimpList, 'newsletterpopup/mailchimpList');
    }

    protected function _loadFormFields($data)
    {
        $systemItemsKeys = Mage::helper('newsletterpopup')->getPopupFormFields(0, false);
        return $this->_loadData($data, $systemItemsKeys, 'newsletterpopup/formField');
    }

    protected function _loadData($data, $keys, $modelName)
    {
        $result = array();
        foreach ($keys as $key => $_) {
            if (array_key_exists($key, $data) && isset($data[$key]['enable'])) {
                $result[] = Mage::getModel($modelName)->setData(array(
                    'popup_id'      => $this->_popup->getId(),
                    'name'          => $key,
                    'label'         => $data[$key]['label'],
                    'enable'        => (int)isset($data[$key]['enable']),
                    'sort_order'    => (int)$data[$key]['sort_order'],
                ));
            }
        }

        uasort($result, create_function('$a, $b', 'return $a["sort_order"] > $b["sort_order"]? 1 : 0;'));
        return $result;
    }
}
