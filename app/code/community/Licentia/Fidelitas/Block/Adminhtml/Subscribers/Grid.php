<?php

class Licentia_Fidelitas_Block_Adminhtml_Subscribers_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('campaign_grid');
        $this->setDefaultSort('subscriber_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {

        $collection = Mage::getModel('fidelitas/subscribers')
            ->getResourceCollection();

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('uid', array(
            'header' => $this->__('ID'),
            'align'  => 'right',
            'width'  => '90px',
            'index'  => 'uid',
        ));
        $this->addColumn('customer_id', array(
            'header'         => $this->__('Customer'),
            'align'          => 'center',
            'width'          => '50px',
            'index'          => 'customer_id',
            'frame_callback' => array($this, 'customerResult'),
        ));

        $this->addColumn('store_id', array(
            'header' => $this->__('List Store View'),
            'type'   => 'store',
            'width'  => '150px',
            'index'  => 'store_id',
        ));

        $this->addColumn('first_name', array(
            'header' => $this->__('First Name'),
            'align'  => 'left',
            'index'  => 'first_name',
        ));

        $this->addColumn('last_name', array(
            'header' => $this->__('Last Name'),
            'align'  => 'left',
            'index'  => 'last_name',
        ));

        $this->addColumn('email', array(
            'header' => $this->__('Email'),
            'align'  => 'left',
            'index'  => 'email',
        ));

        $this->addColumn('cellphone', array(
            'header' => $this->__('Cellphone'),
            'align'  => 'left',
            'index'  => 'cellphone',
        ));

        $this->addColumn('status', array(
            'header'  => $this->__('Status'),
            'align'   => 'left',
            'index'   => 'status',
            'type'    => 'options',
            'options' => array(0 => 'unsubscribed', '1' => 'subscribed'),
        ));

        $this->addColumn('email_sent', array(
            'header' => $this->__('Emails Sent'),
            'align'  => 'left',
            'index'  => 'email_sent',
            'type'   => 'number',
            'width'  => '40px',
        ));

        $this->addColumn('email_views', array(
            'header' => $this->__('Email Views'),
            'align'  => 'left',
            'index'  => 'email_views',
            'type'   => 'number',
            'width'  => '50px',
        ));

        $this->addColumn('sms_delivered', array(
            'header' => $this->__('SMS Delivered'),
            'align'  => 'left',
            'index'  => 'sms_delivered',
            'type'   => 'number',
            'width'  => '50px',
        ));

        return parent::_prepareColumns();
    }

    protected function _filterStoreCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $this->getCollection()->addStoreFilter($value);
    }

    public function customerResult($value)
    {

        if ((int)$value > 0) {
            $url = $this->getUrl('adminhtml/customer/edit', array('id' => $value));
            return '<a href="' . $url . '">' . $this->__('Yes') . '</a>';
        }

        return $this->__('No');
    }

    public function getGridUrl()
    {
        $list = Mage::registry('current_list');
        return $this->getUrl('*/*/grid', array('_current' => true, 'list' => $list->getId()));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

}
