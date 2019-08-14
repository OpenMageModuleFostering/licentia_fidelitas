<?php

class Licentia_Fidelitas_Model_Observer
{

    /**
     * Add order information into Egoimmerce block to render on checkout success pages
     *
     * @param Varien_Event_Observer $observer
     */
    public function setEgoimmerceOnOrderSuccessPageView(Varien_Event_Observer $observer)
    {
        $orderIds = $observer->getEvent()->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }
        $block = Mage::app()->getFrontController()->getAction()->getLayout()->getBlock('egoimmerce');
        if ($block) {
            $block->setOrderIds($orderIds);
        }
    }


    public function addToAutoList($event)
    {

        $order = $event->getEvent()->getOrder();

        try {

            if (!Mage::getStoreConfig('fidelitas/config/auto')) {
                return false;
            }

            Mage::getModel('newsletter/subscriber')->subscribe($order->getCustomerEmail());

        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

}
