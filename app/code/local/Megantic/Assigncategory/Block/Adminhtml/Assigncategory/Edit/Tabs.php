<?php


class Megantic_Assigncategory_Block_Adminhtml_Assigncategory_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('assigncategory_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('assigncategory')->__('Item Information'));
  }

  protected function _beforeToHtml()
  {
		$this->addTab('product_section', array(
						   'label'     => Mage::helper('assigncategory')->__('Select Product'),
						   'title'     => Mage::helper('assigncategory')->__('Select Produc'),
						   'url'       => $this->getUrl('*/*/productgrid', array('_current' => true)),
						   'class'     => 'ajax',
     	 	));
	 
		$this->addTab('categories', array(
                'label'     => Mage::helper('catalog')->__('Add to Categories'),
                'url'       => $this->getUrl('*/*/categories', array('_current' => true)),
                'class'     => 'ajax',
          ));

    $this->addTab('movecategories', array(
                'label'     => Mage::helper('catalog')->__('Move to Categories'),
                'url'       => $this->getUrl('*/*/categoriesmove', array('_current' => true)),
                'class'     => 'ajax',
          ));
		$this->addTab('remove_from_categories', array(
                'label'     => Mage::helper('catalog')->__('Remove from Categories'),
                'url'       => $this->getUrl('*/*/categoriesremove', array('_current' => true)),
                'class'     => 'ajax',
          ));
		
     
      return parent::_beforeToHtml();
  }
}
