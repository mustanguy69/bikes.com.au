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
 * @copyright   Copyright (c) 2018 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */


class Plumrocket_Newsletterpopup_Block_Adminhtml_Popups_Renderer_Input extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Input
{
    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(Varien_Object $row)
    {
        if ('agreement' == $row->getName()) {
            return $this->renderTextarea($row);
        }

        return parent::render($row);
    }

    /**
     * Renders textarea
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function renderTextarea(Varien_Object $row)
    {
        $html = '<textarea ';
        $html .= 'name="' . $this->getColumn()->getId() . '" ';
        $html .= 'rows="3" ';
        $html .= 'class="input-text input-text-textarea ' . $this->getColumn()->getInlineCss() . '">';
        $html .= $this->escapeHtml($row->getData($this->getColumn()->getIndex()));
        $html .= '</textarea>';
        return $html;
    }
}
