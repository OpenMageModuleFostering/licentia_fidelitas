<?php

/**
 * Licentia Fidelitas - SMS Notifications for E-Goi
 *
 * NOTICE OF LICENSE
 * This source file is subject to the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/4.0/
 *
 * @title      SMS Notifications
 * @category   Marketing
 * @package    Licentia
 * @author     Bento Vilas Boas <bento@licentia.pt>
 * @copyright  Copyright (c) 2016 E-Goi - http://e-goi.com
 * @license    Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International
 */
class Licentia_Fidelitas_Block_Adminhtml_Events_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('events_grid');
        $this->setDefaultSort('event_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('fidelitas/events')
            ->getResourceCollection();

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        $this->addColumn('event_id', array(
            'header' => $this->__('ID'),
            'align'  => 'right',
            'width'  => '50px',
            'index'  => 'event_id',
        ));
        $this->addColumn('event', array(
            'header'  => $this->__('Event'),
            'index'   => 'event',
            'type'    => 'options',
            'options' => Mage::getModel('fidelitas/autoresponders')->toOptionArray(),
        ));

        $this->addColumn('autoresponder_id', array(
            'header'  => $this->__('Notification'),
            'index'   => 'autoresponder_id',
            'type'    => 'options',
            'options' => Mage::getModel('fidelitas/autoresponders')->toFormValues(),
        ));

        $this->addColumn('customer_id', array(
            'header'         => $this->__('Customer ID'),
            'index'          => 'customer_id',
            'frame_callback' => array($this, 'customerResult'),
        ));

        $this->addColumn('customer_name', array(
            'header' => $this->__('Customer Name'),
            'index'  => 'customer_name',
        ));
        /*
                $this->addColumn('customer_email', array(
                    'header' => $this->__('Customer Email'),
                    'index' => 'customer_email',
                ));
                */

        $this->addColumn('cellphone', array(
            'header' => $this->__('Cellphone'),
            'index'  => 'cellphone',
        ));

        $this->addColumn('message', array(
            'header' => $this->__('Message'),
            'index'  => 'message',
        ));

        $this->addColumn('sent_at', array(
            'header' => $this->__('Sent at'),
            'align'  => 'left',
            'width'  => '170px',
            'type'   => 'datetime',
            'index'  => 'sent_at',
        ));

        $this->addColumn('sent', array(
            'header'  => $this->__('Sent?'),
            'align'   => 'left',
            'width'   => '80px',
            'index'   => 'sent',
            'type'    => 'options',
            'options' => array('0' => $this->__('No'), '1' => $this->__('Yes')),
        ));

        $this->addExportType('*/*/exportCsv', $this->__('CSV'));
        $this->addExportType('*/*/exportXml', $this->__('Excel XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('event_id');
        $this->getMassactionBlock()->setFormFieldName('events');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'   => $this->__('Delete'),
            'url'     => $this->getUrl('*/fidelitas_events/massDelete'),
            'confirm' => Mage::helper('customer')->__('Are you sure?'),
        ));

        return $this;
    }

    public function customerResult($value)
    {

        if ((int)$value > 0) {
            $url = $this->getUrl('/customer/edit', array('id' => $value));
            return '<a href="' . $url . '">' . $this->__('Yes') . '</a>';
        }

        return $this->__('No');
    }

}
