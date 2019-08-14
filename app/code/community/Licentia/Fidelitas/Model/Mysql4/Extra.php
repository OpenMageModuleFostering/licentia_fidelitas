<?php

class Licentia_Fidelitas_Model_Mysql4_Extra extends Mage_Core_Model_Mysql4_Abstract
{

    public function _construct()
    {
        $this->_init('fidelitas/extra', 'record_id');
    }

}
