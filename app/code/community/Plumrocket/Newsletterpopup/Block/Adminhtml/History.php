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


class Plumrocket_Newsletterpopup_Block_Adminhtml_History extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_controller = 'adminhtml_history';
        $this->_blockGroup = 'newsletterpopup';
        $this->_headerText = Mage::helper('newsletterpopup')->__('Newsletter Popup History');

        $this->_removeButton('add');
    }

    protected function _prepareLayout()
    {
       $this->setChild(
            'grid',
            $this->getLayout()->createBlock(
                $this->_blockGroup . '/' . $this->_controller . '_grid',
                $this->_controller . '.grid'
            )
            ->setSaveParametersInSession(true)
        );

       return parent::_prepareLayout();
    }
}
