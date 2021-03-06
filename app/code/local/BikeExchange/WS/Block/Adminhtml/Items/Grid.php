<?php


class BikeExchange_WS_Block_Adminhtml_Items_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();

    }


    protected function _prepareCollection()
    {

        $collection = Mage::getModel('catalog/product')->getResourceCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('bikeexchange_status')
            ->addAttributeToSelect('bikeexchange_id')
            ->addAttributeToSelect('thumbnail')
            ->addAttributeToFilter('bikeexchange_status',  1)
            ->addAttributeToFilter('bikeexchange_id',  ['neq' => null]);

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
        $this->addColumn('bikeexchange_id',
            array(
                'header'=> 'BikeExchange ID',
                'index' => 'bikeexchange_id',
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
        $this->getMassactionBlock()->setFormFieldName('bikeexchange_delete_select');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'=> 'Delete',
            'url'  => $this->getUrl('*/*/massDelete', array('' => '')),        // public function massDeleteAction() in Mage_Adminhtml_Tax_RateController
            'confirm' => 'Are you sure?'
        ));

        return $this;
    }


}