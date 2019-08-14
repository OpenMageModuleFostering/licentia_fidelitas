<?php

class Licentia_Fidelitas_Model_Source_Addressattributes
{

    public function toOptionArray()
    {
        $type = Mage::getModel('eav/entity_type')->loadByCode('customer_address');
        $attributes = Mage::getResourceModel('eav/entity_attribute_collection')->setEntityTypeFilter($type);
        $return = array();

        foreach ($attributes as $attribute) {
            $return[] = array('value' => $attribute['attribute_code'], 'label' => $attribute['frontend_label']);
        }

        return $return;
    }

}
