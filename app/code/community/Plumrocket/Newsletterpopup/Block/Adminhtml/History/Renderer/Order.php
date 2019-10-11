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


class Plumrocket_Newsletterpopup_Block_Adminhtml_History_Renderer_Order extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
    	if ($row->getOrderId()) {
            $frontName = Mage::getConfig()->getNode('admin/routers/adminhtml/args/frontName');
	    	return sprintf('<a href="%s" target="_blank">%s</a>',
	    		Mage::helper("adminhtml")->getUrl($frontName . '/sales_order/view', array('order_id' => $row->getOrderId())),
	    		$row->getIncrementId()
	    	);
    	}
    	return '';
    }  
} 
