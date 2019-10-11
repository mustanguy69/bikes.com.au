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


class Plumrocket_Newsletterpopup_Block_Adminhtml_Templates_Renderer_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
    	$html = sprintf('<a href="%s"><span>%s</span></a>',
    		Mage::helper('adminhtml')->getUrl('*/*/edit' , array('id' => $row->getId())),
    		Mage::helper('newsletterpopup')->__('Edit')
    	);

    	if($row->isBase()) {
    		$html .= sprintf('<div><img src="%s" title="%s" style="height: 18px;" /></div>',
	    		$this->getSkinUrl('images/plumrocket/newsletterpopup/lock.png'),
	    		Mage::helper('newsletterpopup')->__('This theme is one of the default Newsletter popup themes. It cannot be edited or deleted. Instead, you can duplicate it and then edit.')
	    	);
    	}

    	return $html;
    }  
} 
