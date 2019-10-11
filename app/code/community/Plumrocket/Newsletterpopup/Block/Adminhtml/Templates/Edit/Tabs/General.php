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


class Plumrocket_Newsletterpopup_Block_Adminhtml_Templates_Edit_Tabs_General
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    protected function _prepareForm()
    {
        $template = Mage::registry('template'); 

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('template_');
        
        $fieldset = $form->addFieldset('general_fieldset', array('legend' => Mage::helper('newsletterpopup')->__('General')));
        
        $fieldset->addField('entity_id', 'hidden', array(
            'name'      => 'entity_id'
        ));

        $fieldset->addField('name', 'text', array(
            'name'      => 'name',
            'label'     => Mage::helper('newsletterpopup')->__('Theme Name'),
            'class'     => 'required-entry',
            'required'  => true
        ));

        $fieldset->addField('code_image', 'note', array(
            'label' => Mage::helper('newsletterpopup')->__('HTML Template'),
            'class'     => 'required-entry',
            'required'  => true,
            'after_element_html' => $this->getLayout()
                ->createBlock('adminhtml/widget_button')
                ->addData(array(
                    'id'      => 'code_image',
                    'label'   => $this->__('Insert Image...'),
                    'type'    => 'button',
                    'class'   => 'add-image',
                    'onclick' => "cmSyncSelectionByEditor('#template_code', codeEditor); MediabrowserUtility.openDialog('". Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/cms_wysiwyg_images/index', array('target_element_id' => 'template_code')) ."', null, null, 'Insert Image...');",
                ))
                ->toHtml(),
        ));

        $fieldset->addField('code', 'textarea', array(
            'name'      => 'code',
            'class'     => 'required-entry',
            'required'  => true,
        ));

        $fieldset->addField('style', 'textarea', array(
            'name'      => 'style',
            'label'     => Mage::helper('newsletterpopup')->__('CSS Styles'),
            'note'      => 'Use hotkeys “CTRL” + “SPACE” (or “F1”) to show autocompletion hints.',
        ));

        $form->setValues($template->getData());
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
        return Mage::helper('newsletterpopup')->__('General');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('newsletterpopup')->__('General');
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
