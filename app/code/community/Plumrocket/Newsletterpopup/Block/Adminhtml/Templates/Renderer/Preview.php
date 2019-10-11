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


class Plumrocket_Newsletterpopup_Block_Adminhtml_Templates_Renderer_Preview extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
    	return sprintf('<a href="%s" target="_blank"><button><span>%s</span></button></a>',
    		Mage::helper('newsletterpopup')->validateUrl(
    			Mage::helper('newsletterpopup/adminhtml')->getFrontendUrl('newsletterpopup/index/preview' , array('id' => $row->getId(), 'is_template' => 1))
    		),
    		Mage::helper('newsletterpopup')->__('Preview')
    	);
    }  
} 
