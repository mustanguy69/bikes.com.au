<?php

class MagicToolbox_MagicZoomPlus_Block_Adminhtml_Settings_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {

        parent::__construct();

        $this->setId('magiczoomplus_config_tabs');
        $this->setDestElementId('edit_form');//this should be same as the form id
        $this->setTitle('<span style="visibility: hidden">'.Mage::helper('magiczoomplus')->__('Supported blocks:').'</span>');

    }

    protected function _beforeToHtml()
    {

        $blocks = Mage::helper('magiczoomplus/params')->getProfiles();
        $activeTab = $this->getRequest()->getParam('tab', 'product');

        foreach ($blocks as $id => $label) {
            $this->addTab($id, array(
                'label'     => Mage::helper('magiczoomplus')->__($label),
                'title'     => Mage::helper('magiczoomplus')->__($label.' settings'),
                'content'   => $this->getLayout()->createBlock('magiczoomplus/adminhtml_settings_edit_tab_form', 'magiczoomplus_'.$id.'_settings_block')->toHtml(),
                'active'    => ($id == $activeTab) ? true : false
            ));
        }

        //NOTE: promo section for Sirv extension
        $this->addTab('promo', array(
            'label'     => Mage::helper('magiczoomplus')->__('CDN and Image Processing'),
            'title'     => Mage::helper('magiczoomplus')->__('CDN and Image Processing'),
            'content'   => $this->getLayout()->createBlock(
                'magiczoomplus/adminhtml_settings_edit_tab_promo',
                'magiczoomplus_promo_block'
            )->toHtml(),
            'active'    => ('promo' == $activeTab)
        ));

        return parent::_beforeToHtml();
    }
}
