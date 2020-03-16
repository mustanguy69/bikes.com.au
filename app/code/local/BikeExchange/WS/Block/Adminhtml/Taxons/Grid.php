<?php


class BikeExchange_WS_Block_Adminhtml_Taxons_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();

    }

    protected function _prepareCollection()
    {

        $collection = Mage::getModel(
            'bikeexchange_ws/taxons'
        )->getCollection();

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('category_name',
            array(
                'header'=> 'Category Name',
                'index' => 'category_name',
            ));
        $this->addColumn('taxon_name',
            array(
                'header'=> 'Taxon Name',
                'index' => 'taxon_name',
            ));
        $this->addColumn('taxon_slug',
            array(
                'header'=> 'Taxon Slug',
                'index' => 'taxon_slug',
            ));


        return parent::_prepareColumns();
    }


    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getEntityId()));
    }


}