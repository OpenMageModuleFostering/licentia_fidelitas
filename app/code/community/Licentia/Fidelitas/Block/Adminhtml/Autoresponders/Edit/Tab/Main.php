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
class Licentia_Fidelitas_Block_Adminhtml_Autoresponders_Edit_Tab_Main extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {

        $current = Mage::registry('current_autoresponder');

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('page_');

        $event = $this->getRequest()->getparam('event');
        $campaignId = $this->getRequest()->getparam('campaign_id');
        $sendMoment = $this->getRequest()->getparam('send_moment');
        $type = $this->getRequest()->getParam('type');

        $productName = '';
        if ($current->getId()) {
            $event = $current->getEvent();
            $type = $current->getChannel();

            if ($sendMoment) {
                $current->setData('send_moment', $sendMoment);
            }

            $sendMoment = $current->getSendMoment();

            if ($current->getProduct()) {
                $productName = Mage::getModel('catalog/product')->load($current->getProduct())->getName();
            }
        } else {
            $current->setData('event', $event);
            $current->setData('send_moment', $sendMoment);
        }

        $fieldset = $form->addFieldset('params_fieldset', array('legend' => $this->__('Settings')));

        $location = $this->getUrl('*/*/*', array('_current' => true)) . 'event/';

        $options = Mage::getModel('fidelitas/autoresponders')->toOptionArray();

        if (!$event) {
            array_unshift($options, $this->__('Please Select'));
        }

        $fieldset->addField('event', 'select', array(
            'name'     => 'event',
            'label'    => $this->__('Event Trigger'),
            'title'    => $this->__('Event Trigger'),
            'options'  => $options,
            'disabled' => $current->getId() ? true : false,
            "required" => true,
            "onchange" => "window.location='$location'+this.value",
        ));


        if ($event == 'order_product') {

            $fieldset->addField('product', 'text', array(
                'name'     => 'product',
                'label'    => $this->__('Product ID'),
                'title'    => $this->__('Product ID'),
                "note"     => $productName . ' <a target="_blank" href="' . $this->getUrl('*/catalog_product') . '">' . $this->__('Go to Product Listing') . '</a>',
                "required" => true,
            ));

        }

        if ($event) {
            $location = $this->getUrl('*/*/*', array('_current' => true, 'send_moment' => false)) . 'send_moment/';
            $location = "window.location='$location'+this.value";

            $options = array();

            if (!$current->getId() && !$sendMoment) {
                $options[''] = $this->__('Please Select');
            }
            $options['occurs'] = $this->__('When triggered');
            $options['after'] = $this->__('After...');

            $fieldset->addField('send_moment', "select", array(
                "label"    => $this->__('Send Moment'),
                "options"  => $options,
                "name"     => 'send_moment',
                "required" => true,
                "onchange" => "$location",
            ));
        }

        if ($sendMoment == 'after') {
            $fieldset->addField('after_hours', "select", array(
                "label"   => $this->__('After Hours'),
                "options" => array_combine(range(0, 23), range(0, 23)),
                "name"    => 'after_hours',
            ));


            $fieldset->addField('after_days', "select", array(
                "label"   => $this->__('After Days...'),
                "options" => array_combine(range(0, 30), range(0, 30)),
                "name"    => 'after_days',
            ));
        }


        if ($event == 'order_status' && $sendMoment) {
            $fieldset->addField('order_status', "select", array(
                "label"   => $this->__('New Status'),
                "options" => Mage::getSingleton('sales/order_config')->getStatuses(),
                "name"    => 'order_status',
            ));
        }


        $this->setForm($form);

        if ($current) {
            $form->addValues($current->getData());
        }

        return parent::_prepareForm();
    }

}
