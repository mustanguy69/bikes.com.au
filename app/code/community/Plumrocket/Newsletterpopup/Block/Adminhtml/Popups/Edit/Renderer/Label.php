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


class Plumrocket_Newsletterpopup_Block_Adminhtml_Popups_Edit_Renderer_Label extends Varien_Data_Form_Element_Abstract
{
    public function __construct($attributes=array())
    {
        parent::__construct($attributes);
        $this->setType('label');
    }

    function getHtml()
    {
        return '
            <tr id="popup_code_container"' . ($this->getHidden()? ' style="display: none;"': '') . '>
                <td colspan="2">
                    <ul class="messages">
                        <li class="notice-msg">
                            <ul>
                                <li>
                                ' . $this->getEscapedValue() . '
                                </li>
                            </ul>
                        </li>
                    </ul>
                </td>
            </tr>';
    }
}