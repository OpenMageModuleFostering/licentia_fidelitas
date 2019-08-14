<?php


class Licentia_Fidelitas_Block_Adminhtml_Lists_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId("fidelitas_tabs");
        $this->setDestElementId("edit_form");
        $this->setTitle($this->__("List Information"));
    }

    protected function _beforeToHtml()
    {
        $this->addTab("form_section", array(
            "label"   => $this->__("General"),
            "title"   => $this->__("General"),
            "content" => $this->getLayout()->createBlock("fidelitas/adminhtml_lists_edit_tab_main")->toHtml(),
        ));
        return parent::_beforeToHtml();
    }

}