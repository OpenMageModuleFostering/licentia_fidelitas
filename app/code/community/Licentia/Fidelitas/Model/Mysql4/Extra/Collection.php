<?php

class Licentia_Fidelitas_Model_Mysql4_Extra_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('fidelitas/extra');
    }


}
