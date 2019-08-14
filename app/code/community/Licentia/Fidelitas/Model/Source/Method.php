<?php

class Licentia_Fidelitas_Model_Source_Method
{

    public function toOptionArray()
    {
        $return = array();
        $return[] = array('value' => 'transactional', 'label' => 'Transactional API');
        $return[] = array('value' => 'campaign', 'label' => 'Campaign API');

        return $return;
    }

}
