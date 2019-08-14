<?php

class Licentia_Fidelitas_Helper_Data extends Mage_Core_Helper_Abstract
{

    const XML_PATH_ACTIVE = 'fidelitas/config/analytics';


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

}
