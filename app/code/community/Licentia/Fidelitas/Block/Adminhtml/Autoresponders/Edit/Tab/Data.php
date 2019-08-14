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
class Licentia_Fidelitas_Block_Adminhtml_Autoresponders_Edit_Tab_Data extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {

        $current = Mage::registry('current_autoresponder');

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('page_');

        $event = $this->getRequest()->getParam('event');

        if ($current->getId()) {
            $event = $current->getEvent();
        }


        $fieldset2 = $form->addFieldset('content_fieldset', array('legend' => $this->__('Content')));

        $fieldset2->addField('name', 'text', array(
            'name'     => 'name',
            'label'    => $this->__('Name'),
            'title'    => $this->__('Name'),
            "required" => true,
        ));

        $js = '
        <style type="text/css">#togglepage_message, .add-image{ display:none !important;} #message{width:275px !important;height:125px !important; }</style>
<script type="text/javascript">
    function checkChars(field,divHtml){
        var size = 160 - $F(field).length
        $(divHtml).update(size);
    }
</script>';

        $wysiwygConfig = Mage::getSingleton('cms/wysiwyg_config')->getConfig(
            array('tab_id' => $this->getTabId())
        );

        $wysiwygConfig->setData('hidden', 1);
        $wysiwygConfig->setData('add_images', false);

        $extraMsg = '';
        if ($event == 'new_shipment') {
            $extraMsg = "Use {track_number} and {track_title} to be replaced by carrier name and tracking number.";
        }


        $fieldset2->addField('message', 'editor', array(
            "label"    => $this->__("Message"),
            "class"    => "required-entry",
            "required" => true,
            "onkeyup"  => "checkChars(this,'charsLeft')",
            "name"     => "message",
            'config'   => $wysiwygConfig,
            'wysiwyg'  => true,
            'required' => true,
            "note"     => $this->__($extraMsg . ' 160 characters limit. [<span id="charsLeft">160</span> left]'),
        ))->setAfterElementHtml($js);


        if (!Mage::app()->isSingleStoreMode()) {
            $field = $fieldset2->addField('store_ids', 'multiselect', array(
                'name'     => 'store_ids[]',
                'label'    => Mage::helper('cms')->__('Store View'),
                'title'    => Mage::helper('cms')->__('Store View'),
                'required' => true,
                'values'   => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
            ));
            $renderer = $this->getLayout()->createBlock('adminhtml/store_switcher_form_renderer_fieldset_element');
            $field->setRenderer($renderer);
        } else {
            $fieldset2->addField('store_ids', 'hidden', array(
                'name'  => 'store_ids[]',
                'value' => Mage::app()->getStore(true)->getId(),
            ));
            $current->setStoreIds(Mage::app()->getStore(true)->getId());
        }

        $outputFormatDate = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        $fieldset2->addField('from_date', 'date', array(
            'name'   => 'from_date',
            'format' => $outputFormatDate,
            'label'  => $this->__('Active From Date'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
        ));
        $fieldset2->addField('to_date', 'date', array(
            'name'   => 'to_date',
            'format' => $outputFormatDate,
            'label'  => $this->__('Active To Date'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
        ));


        $fieldset2->addField('active', "select", array(
            "label"   => $this->__('Status'),
            "options" => array('1' => $this->__('Active'), '0' => $this->__('Inactive')),
            "name"    => 'active',
        ));

        $this->setForm($form);

        if ($current) {
            $form->addValues($current->getData());
        }

        return parent::_prepareForm();
    }

}
