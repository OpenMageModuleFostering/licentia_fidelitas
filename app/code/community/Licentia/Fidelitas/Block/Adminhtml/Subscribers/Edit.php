<?php

class Licentia_Fidelitas_Block_Adminhtml_Subscribers_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {
        parent::__construct();
        $this->_objectId = "source_id";
        $this->_blockGroup = "fidelitas";
        $this->_controller = "adminhtml_subscribers";

        $this->_addButton("saveandcontinuebarcode", array(
            "label"   => $this->__("Save and Continue Edit"),
            "onclick" => "saveAndContinueEdit()",
            "class"   => "save",
        ), -100);

        $this->_formScripts[] = " function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }";

    }

    public function getHeaderText()
    {

        $current = Mage::registry("current_subscriber");

        if ($current && $current->getData()) {

            return $this->__("Edit Subscriber: " . $current->getData('last_name') . ', ' . $current->getData('first_name'));
        } else {
            return $this->__("Add Subscriber");
        }
    }

}
