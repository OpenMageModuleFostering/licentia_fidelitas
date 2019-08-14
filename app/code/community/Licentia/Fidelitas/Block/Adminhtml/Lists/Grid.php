<?php

class Licentia_Fidelitas_Block_Adminhtml_Lists_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('importerGrid');
        $this->setDefaultSort('list_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {

        $collection = Mage::getModel('fidelitas/lists')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('list_id', array(
            'header' => $this->__('ID'),
            'width'  => '50px',
            'index'  => 'list_id',
        ));

        $this->addColumn('title', array(
            'header' => $this->__('List Title'),
            'align'  => 'left',
            'index'  => 'title',
        ));

        $this->addColumn('internal_name', array(
            'header' => $this->__('Internal Name'),
            'align'  => 'left',
            'index'  => 'internal_name',
        ));

        /*
          $this->addColumn('subs_total', array(
          'header' => $this->__('Total Subscribers'),
          'type' => 'number',
          'width' => '60px',
          'index' => 'subs_total',
          ));


        $this->addColumn('action', array(
            'header'   => $this->__('Action'),
            'type'     => 'action',
            'width'    => '150px',
            'filter'   => false,
            'sortable' => false,
            'actions'  => array(array(
                'url'     => $this->getUrl('adminhtml/fidelitas_subscribers/index', array('listnum' => '$listnum')),
                'caption' => $this->__('View Subscribers'),
            )),
            'index'    => 'type',
            'sortable' => false,
        ));
 */
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

}
