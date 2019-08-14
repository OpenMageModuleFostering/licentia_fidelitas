<?php

/**
 * Licentia Fidelitas - Advanced Email and SMS Marketing Automation for E-Goi
 *
 * NOTICE OF LICENSE
 * This source file is subject to the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/4.0/
 *
 * @title      Advanced Email and SMS Marketing Automation
 * @category   Marketing
 * @package    Licentia
 * @author     Bento Vilas Boas <bento@licentia.pt>
 * @copyright  Copyright (c) 2016 E-Goi - http://e-goi.com
 * @license    Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International
 */
class Licentia_Fidelitas_Block_Adminhtml_Lists_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {
        parent::__construct();
        $this->_objectId = "list_id";
        $this->_blockGroup = "fidelitas";
        $this->_controller = "adminhtml_lists";

        $this->_removeButton('delete');

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
        if (Mage::registry("current_list")->getId()) {
            return $this->__("Edit List '%s'", $this->htmlEscape(Mage::registry("current_list")->getTitle()));
        } else {
            return $this->__("Add List");
        }
    }

}
