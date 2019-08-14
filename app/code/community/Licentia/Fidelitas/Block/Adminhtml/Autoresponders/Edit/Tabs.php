<?php

/**
 * Licentia Fidelitas - SMS Notifications for E-Goi
 *
 * NOTICE OF LICENSE
 * This source file is subject to the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/4.0/
 *
 * @title      SMS Notifications
 * @category   Marketing
 * @package    Licentia
 * @author     Bento Vilas Boas <bento@licentia.pt>
 * @copyright  Copyright (c) 2016 E-Goi - http://e-goi.com
 * @license    Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International
 */
class Licentia_Fidelitas_Block_Adminhtml_Autoresponders_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {

    public function __construct() {
        parent::__construct();
        $this->setId("fidelitas_tabs");
        $this->setDestElementId("edit_form");
        $this->setTitle($this->__("SMS Notifications Information"));
    }

    protected function _beforeToHtml() {

        $current = Mage::registry('current_autoresponder');


        $this->addTab("main_section", array(
            "label" => $this->__("Settings"),
            "title" => $this->__("Settings"),
            "content" => $this->getLayout()->createBlock("fidelitas/adminhtml_autoresponders_edit_tab_main")->toHtml(),
        ));

        if (($this->getRequest()->getparam('send_moment') || $current->getId())) {
            $this->addTab("data_section", array(
                "label" => $this->__("Information"),
                "title" => $this->__("Information"),
                "content" => $this->getLayout()->createBlock("fidelitas/adminhtml_autoresponders_edit_tab_data")->toHtml(),
            ));

        }

        return parent::_beforeToHtml();
    }

}
