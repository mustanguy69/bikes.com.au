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


class Plumrocket_Newsletterpopup_Block_Adminhtml_Popups_Renderer_Date extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
    	$fieldName = $this->getColumn()->getIndex();
    	$value = $row->getData($fieldName);
    	if ($value) {
    		if ($value == '0000-00-00 00:00:00') {
    			$value = '';
    		} else {
		    	$value = Mage::app()->getLocale()->utcDate(
		            $this->getStore(),
		            Varien_Date::toTimestamp($value),
		            true
		        );
			}
	    }
	    return (string)$value;
    }
} 
