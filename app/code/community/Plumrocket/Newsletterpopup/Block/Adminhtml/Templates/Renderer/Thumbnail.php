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


class Plumrocket_Newsletterpopup_Block_Adminhtml_Templates_Renderer_Thumbnail extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $path = Mage::helper('newsletterpopup/adminhtml')->getScreenUrl($row);
        $html = '';
        if ($path !== false) {
        	$html = '<div style="text-align: center;"><img ';
            $html .= 'id="' . $this->getColumn()->getId() . '" ';
            $html .= 'src="' . $path . '?b=' . time() . '" ';
            $html .= 'class="grid-image ' . $this->getColumn()->getInlineCss() . '" ';
            $html .= 'style="height: 85px; max-width: 200px;" /></div>';
        }
        return $html;
    }
}