<?php

class Licentia_Fidelitas_Block_Adminhtml_Lists_Edit_Tab_Main extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {

        $current = Mage::registry("current_list");

        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset("fidelitas_form", array("legend" => $this->__("List Information")));


        $fieldset->addField("nome", "text", array(
            "label"    => $this->__("List Name"),
            "class"    => "required-entry",
            "required" => true,
            "name"     => "nome",
        ));

        $fieldset->addField("internal_name", "text", array(
            "label"    => $this->__("Internal Name"),
            "class"    => "required-entry",
            "required" => true,
            "name"     => "internal_name",
        ));

        if ($current->getId()) {

            $remoteList = Mage::getModel('fidelitas/egoi')->getLists($current->getListnum())->getData();
            $remoteList = reset($remoteList);
            $remoteList = reset($remoteList);

            $productAttributes = Mage::getResourceSingleton('customer/customer')
                ->loadAllAttributes()
                ->getAttributesByCode();

            $attrToRemove = array('increment_id', 'updated_at', 'attribute_set_id', 'entity_type_id', 'confirmation', 'default_billing', 'default_shipping', 'password_hash');

            $attributes = array('0' => $this->__('--Ignore--'));
            foreach ($productAttributes as $attribute) {
                if (in_array($attribute->getAttributeCode(), $attrToRemove)) {
                    continue;
                }

                if (strlen($attribute->getFrontendLabel()) == 0) {
                    continue;
                }

                $attributes[$attribute->getAttributeCode()] = $attribute->getFrontendLabel() . ' - (Account)';
            }

            asort($attributes);

            $address = Mage::getModel('fidelitas/source_addressattributes')->toOptionArray();
            foreach ($address as $field) {
                $attributes['addr_' . $field['value']] = $field['label'] . ' - (Addresss)';
            }

            if (isset($remoteList['extra_fields'])) {

                $fieldset2 = $form->addFieldset("map_form", array("legend" => $this->__("Map List Attributes")));

                foreach ($remoteList['extra_fields'] as $field) {

                    $fieldset2->addField("extra_" . $field['id'], "select", array(
                        "label"    => $this->__($field['ref']),
                        "options"  => $attributes,
                        "required" => true,
                        "name"     => "extra_" . $field['id'],
                    ));
                }
            }
        }

        if ($current) {

            $currentValues = $current->getData();

            if (count($currentValues) > 0) {
                $currentValues['nome'] = $currentValues['title'];
            }

            $form->setValues($currentValues);

            if (count($currentValues) > 0) {

                $fieldset->addField("listID", "hidden", array(
                    "value"   => $currentValues['listnum'],
                    "no_span" => true,
                    "name"    => "listID",
                ));
            }
        }
        return parent::_prepareForm();
    }

}
