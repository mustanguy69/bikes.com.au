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


class Plumrocket_Newsletterpopup_Block_Adminhtml_Renderer_Time extends Varien_Data_Form_Element_Abstract
{
    public function __construct($attributes=array())
    {
        parent::__construct($attributes);
        $this->setType('text');
    }

    /**
     * {@inheritdoc}
     */
    public function getHtml()
    {
        $htmlStart = '<tr><td class="label"><label>' . $this->getLabel() . '</label></td>'
            . '<td class="value">';

        $htmlEnd = '</td></tr>' . $this->getAfterElementHtml();

        $helperData = Mage::helper('newsletterpopup');

        /* Get current extended time */
        $time = $helperData->extendedTimeToArray(
            $this->getValue(),
            $this->getName()
        );

        /* Get the first of formats by field param */
        $formats = $helperData->getExtTimeFormats($this->getName());
        $fields = array_shift($formats);
        $formatLabels = array();

        if (!is_array($fields)) {
            return $htmlStart . $helperData->__('Unexpected field format.') . $htmlEnd;
        }

        /* Initialize empty html for new field */
        $htmlFields = '';

        foreach ($helperData->getAllowedExtTimeFields() as $field) {
            if (is_array($fields) && in_array($field, $fields)) {
                $elId = implode('_', [$this->getHtmlId(), $field]);
                $htmlFields .= '<select id="' . $elId
                    . '" name="' . $this->getName() . '[' . $field . ']" '
                    . ($this->getDisabled() ? ' disabled="disabled" ' : '')
                    . $this->serialize($this->getHtmlAttributes())
                    . ' style="width:40px">' . PHP_EOL;

                $range = array();
                $delimiter = '';

                switch ($field) {
                    case Plumrocket_Newsletterpopup_Helper_Data::FORMAT_DAY:
                        $range = range(0, 89);
                        $formatLabels[] = $helperData->__('Days');
                        break;
                    case Plumrocket_Newsletterpopup_Helper_Data::FORMAT_HOUR:
                        $range = range(0, 23);
                        $delimiter = ':';
                        $formatLabels[] = $helperData->__('Hours');
                        break;
                    case Plumrocket_Newsletterpopup_Helper_Data::FORMAT_MIN:
                        $range = range(0, 59);
                        $delimiter = in_array(Plumrocket_Newsletterpopup_Helper_Data::FORMAT_SEC, $fields)
                            ? ':' : '';
                        $formatLabels[] = $helperData->__('Minutes');
                        break;
                    case Plumrocket_Newsletterpopup_Helper_Data::FORMAT_SEC:
                        $range = range(0, 59);
                        $formatLabels[] = $helperData->__('Seconds');
                        break;
                }

                foreach ($range as $item) {
                    $item = str_pad($item, 2, '0', STR_PAD_LEFT);
                    $htmlFields .= '<option value="' . $item . '" '
                        . ($time[$field] == $item ? 'selected="selected"' : '')
                        . '>' . $item . '</option>';
                }

                $htmlFields .= '</select>&nbsp;' . $delimiter . '&nbsp;' . PHP_EOL;
            }
        }

        /* Html comment for extended time field */
        $htmlComment = '<p class="note" id="note_extended_time"><span>'
            . $helperData->__('Time format') . ': '
            . '<u>' . implode('</u>,&nbsp;<u>', $formatLabels) . '</u>.<br/>'
            . '</span>' . $this->getNote() . '</p>';

        $html = $htmlStart . $htmlFields . $htmlComment . $htmlEnd;

        return $html;
    }
}
