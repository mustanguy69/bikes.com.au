<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Pgrid
 */
class Amasty_Pgrid_Adminhtml_AttributeController extends Mage_Adminhtml_Controller_Action
{
    public function saveAction()
    {
        $request = Mage::app()->getRequest();

        $preset = $request->getParam('preset', 0) == 1;

        $attributes = $preset ? array()
            : $request->getParam('pattribute', array());

        // will store columns by admin users, if necessary
        $groupId = $request->getParam('column_group_id', 1);
        $extraKey = $request->getParam('attributesKey', '');

        //Only change default template, without changes of columns
        if ($request->getParam('change-template') == 1) {
            $this->_changeGroup($groupId,$extraKey);
            Mage::getConfig()->cleanCache();
            return $this->_redirectBack();
        }
        $category = $request->getParam('category', '') == 1;

        if ($request->getParam('is-new-template') != 1) {
            $group = Mage::getModel('ampgrid/columngroup')->load($groupId);
        } else {
            $group = Mage::getModel('ampgrid/columngroup');
            $group->setData('title', $request->getParam('template-name'));
            $group->setData('user_id', Mage::getSingleton('admin/session')->getUser()->getId());
        }
        $group->setData('additional_columns', $category ? $group->getCategoriesKey() : '');
        $group->setData('attributes', implode(',',array_keys($attributes)));
        $group->save();

        $this->_changeGroup($group->getId(), $extraKey);
        Mage::getConfig()->cleanCache();
        
        return $this->_redirectBack();
    }

    protected function _redirectBack()
    {
        $backUrl = Mage::app()->getRequest()->getParam('backurl');
        if (!$backUrl)
        {
            $backUrl = Mage::getUrl('adminhtml/catalog/product');
        }
        return $this->getResponse()->setRedirect($backUrl);
    }

    protected function _changeGroup($groupId, $attributeKey = '') {
        if (Mage::getStoreConfig('ampgrid/attr/byadmin'))
        {
            $attributeKey .= Mage::getSingleton('admin/session')->getUser()->getId();
        }

        Mage::getConfig()->saveConfig('ampgrid/attributes/ongrid' . $attributeKey, $groupId);

    }
}