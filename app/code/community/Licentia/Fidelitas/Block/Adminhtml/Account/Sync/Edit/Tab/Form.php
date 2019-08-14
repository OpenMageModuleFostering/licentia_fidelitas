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
class Licentia_Fidelitas_Block_Adminhtml_Account_Sync_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {

        $current = Mage::registry("egoi_lists");

        $mls = array();
        $mls[0] = $this->__('Create New List');
        foreach ($current->getData() as $ml) {
            $mls[$ml['listnum']] = $ml['title'];
        }

        $stores = Mage::getSingleton('adminhtml/system_store')->getStoreCollection();

        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset("fidelitas_form", array("legend" => $this->__("Existing Stores")));

        foreach ($stores as $store) {

            $fieldset->addField($store->getId(), "select", array(
                "name"   => 'store[' . $store->getId() . ']',
                "values" => $mls,
                "label"  => $this->__('Store View') . ' - ' . $store->getName(),
                "note"   => Mage::getModel('adminhtml/system_store')->getStoreNameWithWebsite($store->getId()),
            ));
        }

        return parent::_prepareForm();
    }

}
