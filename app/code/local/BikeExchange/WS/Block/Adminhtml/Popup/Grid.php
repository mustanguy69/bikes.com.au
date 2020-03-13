<?php


class BikeExchange_WS_Block_Adminhtml_Popup_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();

    }

    protected function _prepareCollection()
    {

        $collection = Mage::getModel('catalog/product')->getResourceCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('bikeexchange_id')
            ->addAttributeToSelect('thumbnail')
            ->addAttributeToFilter(
                array(
                    array('attribute'=> 'bikeexchange_id','null' => true),
                    array('attribute'=> 'bikeexchange_id','eq' => ''),
                    array('attribute'=> 'bikeexchange_id','eq' => 'NO FIELD')
                ),
                '',
                'left');

        $collection->setCurPage(1);

        $this->setCollection($collection);
        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {

        $this->addColumn('image', array(
            'header' => Mage::helper('catalog')->__('Image'),
            'align' => 'center',
            'index' => 'image',
            'width'     => '97',
            'renderer' => 'BikeExchange_WS_Block_Adminhtml_Template_Grid_Renderer_Image'
        ));
        $this->addColumn('entity_id',
            array(
                'header'=> Mage::helper('catalog')->__('Magento ID'),
                'width' => '50px',
                'type'  => 'number',
                'index' => 'entity_id',
            ));
        $this->addColumn('name',
            array(
                'header'=> 'Product Name',
                'index' => 'name',
            ));


        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('bikeexchange_status');
        $this->getMassactionBlock()->setFormFieldName('bikeexchange_add_select');

        $this->getMassactionBlock()->addItem('add', array(
            'label'=> 'Add',
            'url'  => $this->getUrl('*/*/massAdd', array('' => '')),
            'confirm' => 'Are you sure?'
        ));

        return $this;
    }


}