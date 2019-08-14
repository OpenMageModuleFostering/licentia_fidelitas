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


    public function isCustomerSubscribed($customerId)
    {
        $col = Mage::getModel('fidelitas/subscribers')
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId);

        return $col->getSize() > 0 ? $col->getFirstItem() : false;
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
