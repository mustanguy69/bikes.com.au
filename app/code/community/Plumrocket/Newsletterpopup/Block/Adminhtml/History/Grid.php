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


class Plumrocket_Newsletterpopup_Block_Adminhtml_History_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('manage_newsletterpopup_history_grid');
        $this->setDefaultSort('date_created');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('newsletterpopup/history')
            ->getCollection()
            ->addCustomerNameToSelect()
            ->addActionTextToResult()
            ->addFilterToMap('action', new Zend_Db_Expr("IFNULL(`ha`.`text`, `main_table`.`action`)"));

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('popup_id', array(
            'header'    => Mage::helper('newsletterpopup')->__('Popup ID'),
            'index'     => 'popup_id',
            'type'      => 'text',
            'width'     => '3%',
        ));

        $items = Mage::getModel('newsletterpopup/popup')->getCollection();
        $popups = array();
        foreach ($items as $item) {
            $popups[ $item->getId() ] = $item->getName();
        }
        $this->addColumn('popup_name', array(
            'header'    => Mage::helper('newsletterpopup')->__('Popup Name'),
            'index'     => 'popup_id',
            'type'      =>  'options',
            'options'   =>  $popups,
            'width'     => '10%',
        ));

        $this->addColumn('customer_name', array(
            'header'    => Mage::helper('newsletterpopup')->__('Customer Name'),
            'index'     => 'customer_name',
            'type'      => 'text',
            'width'     => '10%',
            'filter_condition_callback' => array($this, '_customerNameCondition'),
            'renderer'  => 'newsletterpopup/adminhtml_history_renderer_name',
        ));

        $this->addColumn('customer_email', array(
            'header'    => Mage::helper('newsletterpopup')->__('Email'),
            'index'     => 'customer_email',
            'type'      => 'text',
            'width'     => '10%',
        ));

        $groups = Mage::getResourceModel('customer/group_collection')
            ->load()
            ->toOptionHash();
        $this->addColumn('customer_group', array(
            'header'    =>  Mage::helper('newsletterpopup')->__('Customer Group'),
            'width'     =>  '5%',
            'index'     =>  'customer_group',
            'type'      =>  'options',
            'options'   =>  $groups,
        ));

        $this->addColumn('customer_ip', array(
            'header'    => Mage::helper('newsletterpopup')->__('Customer IP'),
            'index'     => 'customer_ip',
            'type'      => 'text',
            'width'     => '6%',
        ));

        $this->addColumn('landing_page', array(
            'header'    => Mage::helper('newsletterpopup')->__('Landing Page'),
            'index'     => 'landing_page',
            'type'      => 'text',
            'width'     => '20%',
        ));

        $this->addColumn('action', array(
            'header'    => Mage::helper('newsletterpopup')->__('Action'),
            'index'     => 'action',
            'type'      => 'text',
            // 'options'    => Mage::getSingleton('newsletterpopup/values_action')->toOptionHash(),
            'width'     => '5%',
        ));

        $this->addColumn('coupon_code', array(
            'header'    => Mage::helper('newsletterpopup')->__('Coupon Code'),
            'index'     => 'coupon_code',
            'type'      => 'text',
            'width'     => '6%',
        ));

        $this->addColumn('order_id', array(
            'header'    => Mage::helper('newsletterpopup')->__('Order #'),
            'index'     => 'order_id',
            'type'      => 'text',
            'width'     => '6%',
            'renderer'  => 'newsletterpopup/adminhtml_history_renderer_order',
        ));

        $this->addColumn('grand_total', array(
            'header'    => Mage::helper('newsletterpopup')->__('Order G.T.'),
            'index'     => 'grand_total',
            'type'      => 'price',
            'currency_code' => (string)Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE),
            'width'     => '4%',
            'frame_callback' => array($this, 'decoratePrice'),
        ));

        $this->addColumn('date_created', array(
            'header'    => Mage::helper('newsletterpopup')->__('Datetime'),
            'index'     => 'date_created',
            'type'      => 'datetime',
            'width'     => '6%',
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'    =>  Mage::helper('customer')->__('Store View'),
                'width'     =>  '6%',
                'index'     =>  'store_id',
                'type'      =>  'options',
                'options'   =>  Mage::getSingleton('adminhtml/system_store')->getStoreOptionHash(),
                'filter_index'  => 'main_table.store_id',
            ));
        }

        $this->addExportType('*/*/exportCsv', Mage::helper('customer')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('customer')->__('Excel XML'));
        return parent::_prepareColumns();
    }

    public function decoratePrice($value, $row, $column, $isExport)
    {
        if ((int)$row->getGrandTotal() === 0) {
            return '';
        }
        return $value;
    }

    protected function _customerNameCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
        $this->getCollection()->addNameFilter($value);
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('main_table.entity_id');
        $this->getMassactionBlock()->setFormFieldName('history_id');
        $this->getMassactionBlock()
            ->addItem('delete', array(
                'label'     => Mage::helper('newsletterpopup')->__('Delete'),
                'url'       => $this->getUrl('*/*/mass', array('action' => 'delete'))
            ));
        return $this;
    }
}
