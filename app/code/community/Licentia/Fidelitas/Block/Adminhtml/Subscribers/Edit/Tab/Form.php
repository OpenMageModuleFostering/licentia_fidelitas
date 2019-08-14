<?php

class Licentia_Fidelitas_Block_Adminhtml_Subscribers_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {

        $current = Mage::registry('current_subscriber');

        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset("fidelitas_form", array("legend" => $this->__("Subscriber Information")));


        $fieldset->addField('email', "text", array(
            "label"    => $this->__('Email'),
            "class"    => "required-entry validate-email",
            "required" => true,
            "name"     => 'email',
        ));

        $fields = array('first_name', 'last_name',);

        foreach ($fields as $element) {
            $fieldset->addField($element, "text", array(
                "label" => $this->__(ucwords(str_replace('_', ' ', $element))),
                "name"  => $element,
            ));
        }

        $fieldset->addField('cellphone_prefix', "select", array(
            "label"  => $this->__('Cellphone Prefix'),
            'values' => Licentia_Fidelitas_Model_Subscribers::getPhonePrefixs(),
            "name"   => 'cellphone_prefix',
        ));

        $fieldset->addField('cellphone', "text", array(
            "label" => $this->__('Cellphone Number'),
            "name"  => 'cellphone',
        ));

        $fieldset->addField('status', "select", array(
            "label"   => $this->__('Status'),
            "name"    => 'status',
            'options' => array(4 => 'inactive', 1 => 'subscribed'),
        ));

        if ($current->getId()) {
            $form->addValues($current->getData());
        } else {
            $form->addValues(array('status' => 1));
        }

        return parent::_prepareForm();
    }

}
