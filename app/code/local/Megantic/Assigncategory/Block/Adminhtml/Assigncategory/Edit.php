<?php

class Megantic_Assigncategory_Block_Adminhtml_Assigncategory_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'assigncategory';
        $this->_controller = 'adminhtml_assigncategory';
        
        $this->_addButton('add_new', array(
        'label'   => Mage::helper('catalog')->__('Products Selected: <span class="prd-cnt">0</span>'),
        'class'   => 'prd_count'));
        $this->_removeButton('back');
        $this->_updateButton('save', 'label', Mage::helper('assigncategory')->__('Submit'));
        $this->_updateButton('delete', 'label', Mage::helper('assigncategory')->__('Delete Item'));
        
		
        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('assigncategory_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'assigncategory_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'assigncategory_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('assigncategory_data') && Mage::registry('assigncategory_data')->getId() ) {
            //return Mage::helper('assigncategory')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('assigncategory_data')->getTitle()));
        } else {
            //return Mage::helper('assigncategory')->__('Add Item');
        }
    }
}
