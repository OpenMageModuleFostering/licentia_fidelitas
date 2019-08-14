<?php

class Licentia_Fidelitas_Model_Account extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {

        $this->_init('fidelitas/account');
    }

    public function getAccount()
    {
        $col = $this->getCollection()->getFirstItem();

        if (!$col->getId()) {
            $this->save(array('company_name' => ' '));
            return $this;
        }

        return $col;

    }

    function cron()
    {

        $fid = Mage::getModel('fidelitas/egoi');
        $result = $fid->getAccountDetails()->getData();
        $result[0]['account_id'] = 1;
        $account = Mage::getModel('fidelitas/account')->getAccount();

        if ($account->getId()) {
            $account->setData($result[0])->save();
        } else {
            Mage::getModel('fidelitas/account')->setData($result[0])->save();
        }

    }

}
