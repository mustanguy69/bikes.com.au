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


class Plumrocket_Newsletterpopup_Block_System_Config_Htmltoimage extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);

        if (Mage::helper('newsletterpopup/adminhtml')->checkIfHtmlToImageInstalled()) {
            $message = 'Thumbnail Generation is Enabled.';
        } else {
            $message = 'Thumbnail Generation is <span style="color: #eb5e00;">Disabled</span>.
            In order for popup thumbnail to appear, please install the wkhtmltoimage command line tool
            to render HTML into image. See "installation" chapter of
            <a href="http://wiki.plumrocket.com/wiki/Magento_Newsletter_Popup_v1.x_Installation#Installing_WKHTMLTOIMAGE_command_line_tool" target="_blank">our online documentation</a> for more info.
            <br /><br />
            <span id="wkhtmltoimage_status">
                <button style="" onclick="htmltoimageSubmitRequest()" class="scalable"
                type="button" title="Find Wkhtmltoimage Tool">
                    <span>
                        <span>
                            <span>Find Wkhtmltoimage Tool</span>
                        </span>
                    </span>
                </button>
            </span>
            <script type="text/javascript">
            function htmltoimageSubmitRequest()
            {
                new Ajax.Request("' . Mage::helper("adminhtml")->getUrl('adminhtml/newsletterpopup_config/refresh') . '", {
                    method: "get",
                    onSuccess: function successFunc(response){
                        if (200 == response.status){
                            var json = response.responseText.evalJSON();
                            if (json) {
                                var tp = (json.error)? "error": "success";
                                var text = "<div id=\"messages\"><ul class=\"messages\"><li class=\"" + tp + "-msg\"><ul><li><span>" + json.message + "</span></li></ul></li></ul></div>";
                                $("wkhtmltoimage_status").update(text);
                            }
                        }
                    },
                    onFailure:  function () {}
                });
            }
            </script>';
        }
        return '<div class="checkboxes" style="border: 1px solid #ccc; padding: 5px; background-color: #fdfdfd;">' . $message . '</div>';
    }
}
