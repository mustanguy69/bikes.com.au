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


class Plumrocket_Newsletterpopup_Block_Adminhtml_Popups_Edit_Tabs_Display
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    protected function _prepareForm()
    {
        $popup = Mage::registry('popup');

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('popup_');

        $fieldset = $form->addFieldset('display_fieldset', array('legend' => Mage::helper('newsletterpopup')->__('Display Settings')));

        $chooseButtonHtml = $this->getLayout()
            ->createBlock('adminhtml/widget_button')
            ->addData(array(
                'id'      => 'choose_template',
                'label'   => $this->__('Choose Theme'),
                'type'    => 'button',
            ))
            ->toHtml();

        $selectButtonHtml = $this->getLayout()
            ->createBlock('adminhtml/widget_button')
            ->addData(array(
                'label'   => $this->__('Select'),
                'type'    => 'button',
                'class'   => 'select_template',
            ))
            ->toHtml();

        $previewButtonHtml = $this->getLayout()
            ->createBlock('adminhtml/widget_button')
            ->addData(array(
                'label'   => $this->__('Preview'),
                'type'    => 'button',
                'class'   => 'preview_template',
                'onclick' => ''
            ))
            ->toHtml();

        $items = Mage::helper('newsletterpopup/adminhtml')
            ->getTemplates()
            ->setOrder('base_template_id', 'ASC')
            ->setOrder('entity_id', 'ASC');

        $countBaseTemplates = 0;
        foreach ($items as $item) { if($item->getBaseTemplateId() > -1) break; $countBaseTemplates++; }

        $html = $chooseButtonHtml .'<div id="template_id_picker" data-action="'. $this->getUrl('*/*/loadTemplate') .'"><div class="template-current"></div><div class="template-list" style="display: none;"><div class="template-title">Default Templates ('. $countBaseTemplates .')<div class="template-expand"><span></span></div></div><div class="template-wrapper"><div class="shadow"></div><ul>';

        foreach ($items as $item) {
            if($item->getBaseTemplateId() > -1 && empty($gotoMy)) {
                $html .= '</ul></div><div class="template-title">My Templates ('. (count($items) - $countBaseTemplates) .')<div class="template-expand"><span></span></div></div><div class="template-wrapper"><div class="shadow"></div><ul>';
                $gotoMy = true;
            }

            $screenUrl = Mage::helper('newsletterpopup/adminhtml')->getScreenUrl($item);

            $previewUrl = Mage::helper('newsletterpopup')->validateUrl(
                Mage::helper('newsletterpopup/adminhtml')->getFrontendUrl('newsletterpopup/index/preview' , array('id' => $item->getId(), 'is_template' => 1))
            );

            $html .= ('<li data-id="'. $item->getId() .'" title="'. $item->getName() .'"><div class="list-table-td">'. ($screenUrl? '<div class="screen-image" style="background-image: url(\''. $screenUrl .'\'); background-size: contain;"></div>' : '') .'</div><span>'. $item->getName() .'</span><div>'. $selectButtonHtml .'<a href="'. $previewUrl .'" target="_blank">'. $previewButtonHtml .'</a></div></li>');
        }
        $html .= '</ul></div></div></div>';


        $fieldset->addField('template_id', 'select', array(
            'name'      => 'template_id',
            'label'     => Mage::helper('newsletterpopup')->__('Popup Theme'),
            'required'  => true,
            'values'    => Mage::getSingleton('newsletterpopup/values_template')->toOptionHash(),
            'style'     => 'display: none;',
            'after_element_html' => $html,
        ));

        $fieldset->addField('display_popup', 'select', array(
            'name'      => 'display_popup',
            'label'     => Mage::helper('newsletterpopup')->__('Display Popup'),
            'values'    => Mage::getSingleton('newsletterpopup/values_method')->toOptionHash(),
            'note'      => 'See documentation for manual display method.'
        ));

        $fieldset->addField('delay_time', 'text', array(
            'name'      => 'delay_time',
            'label'     => Mage::helper('newsletterpopup')->__('Popup Time Delay'),
            'note'      => 'Time delay in seconds after which popup should be displayed. Enter "0" to display popup on page load.',
            'after_element_html' => '<script type="text/javascript">pjQuery_1_10_2(document).ready(function() { checkPopupMethod(); });</script>'
        ));

        $fieldset->addField('page_scroll', 'text', array(
            'name'      => 'page_scroll',
            'label'     => Mage::helper('newsletterpopup')->__('Scroll Threshold Trigger (%)'),
            'note'      => 'Page threshold in percent\'s (%) on scroll down, after which popup should be displayed. Example: set "30" to display popup on page after visitor scrolled down 30% of the page.',
            'after_element_html' => '<script type="text/javascript">pjQuery_1_10_2(document).ready(function() { checkPopupMethod(); });</script>'
        ));

        $fieldset->addField('css_selector', 'text', array(
            'name'      => 'css_selector',
            'label'     => Mage::helper('newsletterpopup')->__('CSS Selector'),
            'note'      => 'Enter the “ID” or “Class Name” of the object you want to use to trigger the newsletter popup. Example: enter “.btn-cart” to display newsletter popup on mouse over (or on-click) the “add to cart” button.',
            'after_element_html' => '<script type="text/javascript">pjQuery_1_10_2(document).ready(function() { checkPopupMethod(); });</script>'
        ));

        /**
         * Set Extended Time Field for cookie_time_frame
         * Custom Extended Time Format must be defined in Data_Helper
         */
        $fieldset->addType(
            'extended_time',
            'Plumrocket_Newsletterpopup_Block_Adminhtml_Renderer_Time'
        );

        $fieldset->addField('cookie_time_frame', 'extended_time', array(
            'name'  => 'cookie_time_frame',
            'label' => Mage::helper('newsletterpopup')->__('Cookie Timeout'),
            'note' => 'After the popup was closed - it will appear again to the same user in the specified time.
            <br/>Select "00" in all fields to never display popup again after it was closed for the first time.'
        ));

        $fieldset->addField('animation', 'select', array(
            'name'      => 'animation',
            'label'     => Mage::helper('newsletterpopup')->__('Animation'),
            'values'    => Mage::getSingleton('newsletterpopup/values_animation')->toOptionHash()
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField('store_id', 'multiselect', array(
                'name'      => 'stores[]',
                'label'     => Mage::helper('newsletterpopup')->__('Visible In'),
                'required'  => true,
                'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true)
            ));
        } else {
            $fieldset->addField('store_id', 'hidden', array(
                'name'      => 'stores[]',
                'value'     => Mage::app()->getStore(true)->getId()
            ));
            $popup->setStoreId(Mage::app()->getStore(true)->getId());
        }

        $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
            ->setTemplate('promo/fieldset.phtml')
            ->setNewChildUrl($this->getUrl('*/*/newConditionHtml/form/popup_conditions_fieldset'));

        $fieldset = $form->addFieldset('conditions_fieldset', array(
            'legend'=>Mage::helper('newsletterpopup')->__('Display Popup Restrictions (leave blank to show popup for all customers on all pages and devices)')
        ))->setRenderer($renderer);

        $fieldset->addField('conditions', 'text', array(
            'name' => 'conditions',
            'label' => Mage::helper('newsletterpopup')->__('Conditions'),
            'title' => Mage::helper('newsletterpopup')->__('Conditions'),
        ))->setRule($popup)->setRenderer(Mage::getBlockSingleton('rule/conditions'));

        $form->setValues($popup->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('newsletterpopup')->__('Display Settings');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('newsletterpopup')->__('Display Settings');
    }

    /**
     * Returns status flag about this tab can be shown or not
     *
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return true
     */
    public function isHidden()
    {
        return false;
    }

}
