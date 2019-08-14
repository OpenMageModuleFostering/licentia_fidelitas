<?php

class Licentia_Fidelitas_Helper_Data extends Mage_Core_Helper_Abstract
{

    const XML_PATH_ACTIVE = 'fidelitas/config/analytics';

    const TRANSACTIONAL_SERVER = 'bo51.e-goi.com';
    const TRANSACTIONAL_PORT = 587;
    const TRANSACTIONAL_AUTH = 'login';
    const TRANSACTIONAL_SSL = 'TLS';

    /**
     *
     * @param mixed $store
     *
     * @return bool
     */
    public function isEgoimmerceAvailable($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_ACTIVE, $store);
    }


    public function loadSubscriber()
    {
        $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();

        if ($customerId) {
            $uid = Mage::getModel('fidelitas/subscribers')
                ->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                ->getFirstItem();

            if ($uid->getData('uid')) {
                Mage::getSingleton('core/cookie')->set('egoi-subscriber', $uid->getData('uid'), true);
                return $uid;
            }
        }

        $uid = Mage::getSingleton('core/cookie')->get('egoi-subscriber');
        if ($uid) {
            $uid = Mage::getModel('fidelitas/subscribers')
                ->getCollection()
                ->addFieldToFilter('uid', $uid)
                ->getFirstItem();

            if ($uid->getData('uid')) {
                return $uid;
            }
        }

        return false;
    }

    public function getSmtpTransport($storeId)
    {

        $config = array('auth' => self::TRANSACTIONAL_AUTH, 'port' => self::TRANSACTIONAL_PORT);

        $config['ssl'] = self::TRANSACTIONAL_SSL;

        $config['username'] = Mage::getStoreConfig('fidelitas/transactional/username', $storeId);
        $config['password'] = Mage::getStoreConfig('fidelitas/transactional/password', $storeId);


        return new Zend_Mail_Transport_Smtp(self::TRANSACTIONAL_SERVER, $config);
    }

}
