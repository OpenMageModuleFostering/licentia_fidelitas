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
class Licentia_Fidelitas_Model_Autoresponders extends Mage_Core_Model_Abstract
{

    const MYSQL_DATE = 'yyyy-MM-dd';
    const MYSQL_DATETIME = 'yyyy-MM-dd HH:mm:ss';

    protected function _construct()
    {

        $this->_init('fidelitas/autoresponders');
    }

    public function toOptionArray()
    {

        $return = array(
            'order_new'      => Mage::helper('fidelitas')->__('Order - New Order'),
            #'order_product'  => Mage::helper('fidelitas')->__('Order - Bought Specific Product'),
            'order_status'   => Mage::helper('fidelitas')->__('Order - Order Status Changes'),
            'new_invoice'    => Mage::helper('fidelitas')->__('New Invoice'),
            'new_shipment'   => Mage::helper('fidelitas')->__('New Shipment'),
            'new_creditmemo' => Mage::helper('fidelitas')->__('New Creditmemo'),
        );


        return $return;
    }

    public function newOrderDocument($event)
    {

        $document = $event->getEvent()->getDataObject();

        if ($document instanceof Mage_Sales_Model_Order_Invoice) {
            $type = 'new_invoice';
        } elseif ($document instanceof Mage_Sales_Model_Order_Shipment) {
            $type = 'new_shipment';
        } elseif ($document instanceof Mage_Sales_Model_Order_Creditmemo) {
            $type = 'new_creditmemo';
        } else {
            return false;
        }

        $order = $document->getOrder();

        $phone = Mage::getModel('fidelitas/egoi')->getPhone($order);

        if (!$phone) {
            return false;
        }

        $autoresponders = $this->_getCollection($order->getStoreId())
            ->addFieldToFilter('event', $type);

        $customer = new Varien_Object;
        $customer->setName($order->getCustomerName())
            ->setEmail($order->getCustomerEmail())
            ->setId($order->getCustomerId());

        foreach ($autoresponders as $autoresponder) {
            $this->_insertData($autoresponder, $phone, $order->getStoreId(), $customer, $document->getId());
        }
    }

    public function changeStatus($event)
    {

        $order = $event->getEvent()->getOrder();
        $newStatus = $order->getData('status');
        $olderStatus = $order->getOrigData('status');

        if ($newStatus == $olderStatus) {
            return;
        }

        $phone = Mage::getModel('fidelitas/egoi')->getPhone($order);

        if (!$phone) {
            return false;
        }

        $autoresponders = $this->_getCollection($order->getStoreId())
            ->addFieldToFilter('event', 'order_status')
            ->addFieldToFilter('order_status', $newStatus);

        $customer = new Varien_Object;
        $customer->setName($order->getCustomerName())
            ->setEmail($order->getCustomerEmail())
            ->setId($order->getCustomerId());

        foreach ($autoresponders as $autoresponder) {
            $this->_insertData($autoresponder, $phone, $order->getStoreId(), $customer, $order->getId());
        }
    }

    public function newOrder($event)
    {

        $order = $event->getEvent()->getOrder();

        $autoresponders = $this->_getCollection($order->getStoreId())
            ->addFieldToFilter('event', array('in' => array('order_product', 'order_new')));

        $customer = new Varien_Object;
        $customer->setName($order->getCustomerName())
            ->setEmail($order->getCustomerEmail())
            ->setId($order->getCustomerId());

        foreach ($autoresponders as $autoresponder) {

            if ($autoresponder->getEvent() == 'order_product') {
                $items = $order->getAllItems();
                $ok = false;
                foreach ($items as $item) {
                    if ($item->getProductId() == $autoresponder->getProduct()) {
                        $ok = true;
                        break;
                    }
                }
                if ($ok === false) {
                    break;
                }
            }

            $phone = Mage::getModel('fidelitas/egoi')->getPhone($order);

            if (!$phone) {
                return false;
            }


            $this->_insertData($autoresponder, $phone, $order->getStoreId(), $customer, $order->getId());
        }
    }

    public function calculateSendDate($autoresponder)
    {
        if ($autoresponder->getSendMoment() == 'occurs') {
            $date = Mage::app()->getLocale()->date()
                ->get(self::MYSQL_DATETIME);
        }

        if ($autoresponder->getSendMoment() == 'after') {
            $date = Mage::app()->getLocale()->date();

            if ($autoresponder->getAfterHours() > 0) {
                $date->addHour($autoresponder->getAfterHours());
            }
            if ($autoresponder->getAfterDays() > 0) {
                $date->addDay($autoresponder->getAfterDays());
            }
            $date->get(self::MYSQL_DATETIME);
        }

        return $date;
    }

    public function send()
    {
        $date = Mage::app()->getLocale()->date()->get(self::MYSQL_DATETIME);

        $smsCollection = Mage::getModel('fidelitas/events')->getCollection()
            ->addFieldToFilter('sent', 0)
            ->addFieldToFilter('send_at', array('lteq' => $date));

        foreach ($smsCollection as $cron) {

            $autoresponder = Mage::getModel('fidelitas/autoresponders')->load($cron->getAutoresponderId());

            $message = Mage::helper('cms')->getBlockTemplateProcessor()->filter($autoresponder->getMessage());

            if ($autoresponder->getEvent() == 'new_shipment') {

                $track = Mage::getModel('sales/order_shipment')
                    ->load($cron->getDataObjectId())
                    ->getTracksCollection()
                    ->getFirstItem();

                if ($track->getId()) {
                    $message = str_replace(
                        array('{track_number}', '{track_title}'),
                        array($track->getTrackNumber(), $track->getTitle()),
                        $message
                    );
                }
            }

            $result = Mage::getModel('fidelitas/egoi')->send($cron->getCellphone(), $message);

            if ($result === true) {
                $cron->setSent(1)->setMessage($message)->setSentAt($date)->save();
            }
        }
    }

    protected function _insertData($autoresponder, $number, $storeId, $customer, $dataObjectId = null)
    {

        $storeIds = explode(',', $autoresponder->getStoreIds());

        if (!in_array($storeId, $storeIds)) {
            return false;
        }

        $data = array();
        $data['send_at'] = $this->calculateSendDate($autoresponder);
        $data['autoresponder_id'] = $autoresponder->getId();
        $data['cellphone'] = $number;
        $data['customer_id'] = $customer->getId();
        $data['customer_name'] = $customer->getName();
        $data['customer_email'] = $customer->getEmail();
        $data['event'] = $autoresponder->getEvent();
        $data['created_at'] = new Zend_Db_Expr('NOW()');
        $data['sent'] = 0;
        $data['data_object_id'] = $dataObjectId;

        Mage::getModel('fidelitas/events')->setData($data)->save();
        $autoresponder->setData('number_subscribers', $autoresponder->getData('number_subscribers') + 1)->save();
    }

    public function toFormValues()
    {
        $return = array();
        $collection = $this->getCollection()
            ->addFieldToSelect('name')
            ->addFieldToSelect('autoresponder_id')
            ->setOrder('name', 'ASC');
        foreach ($collection as $autoresponder) {
            $return[$autoresponder->getId()] = $autoresponder->getName() . ' (ID:' . $autoresponder->getId() . ')';
        }

        return $return;
    }

    protected function _getCollection($storeId)
    {

        $date = Mage::app()->getLocale()->date()->get(self::MYSQL_DATE);
        //Version Compatability
        $return = $this->getCollection()
            ->addFieldToFilter('active', 1);

        $return->getSelect()
            ->where(" FIND_IN_SET('0', store_ids) OR FIND_IN_SET(?, store_ids)", $storeId)
            ->where(" from_date <=? or from_date IS NULL ", $date)
            ->where(" to_date >=? or to_date IS NULL ", $date);


        return $return;
    }

}
