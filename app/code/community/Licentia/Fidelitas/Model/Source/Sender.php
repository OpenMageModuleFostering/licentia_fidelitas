<?php

class Licentia_Fidelitas_Model_Source_Sender
{

    public function toOptionArray()
    {
        $attributes = Mage::getModel('fidelitas/egoi')->getSenders();
        $return = array();

        foreach ($attributes->getData() as $attribute) {
            $return[] = array('value' => $attribute['fromid'], 'label' => $attribute['sender']);
        }

        return $return;
    }

}
