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
class Licentia_Fidelitas_Block_Adminhtml_Account_New_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {

        $current = Mage::registry("current_account");

        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset("fidelitas_form", array("legend" => $this->__("New E-Goi Customers")));


        $fieldset->addField('idioma', "select", array(
            "values" => array('pt' => 'Portuguese', 'br' => 'Brazilian', 'en' => 'English'),
            "name"   => 'idioma',
            "label"  => $this->__('Language'),
        ));

        $fieldset->addField('primeiro_nome', "text", array(
            "label"    => $this->__('First Name'),
            "required" => true,
            "name"     => 'primeiro_nome',
        ));

        $fieldset->addField('ultimo_nome', "text", array(
            "label"    => $this->__('Last Name'),
            "required" => true,
            "name"     => 'ultimo_nome',
        ));

        $fieldset->addField('email', "text", array(
            "label"    => $this->__('Email'),
            "class"    => "required-entry validate-email",
            "required" => true,
            "name"     => 'email',
        ));

        $fieldset->addField('utilizador', "text", array(
            "label"    => $this->__('Username'),
            "class"    => "required-entry",
            "required" => true,
            "name"     => 'utilizador',
        ));

        $fieldset->addField('password', "password", array(
            "label"    => $this->__('Password'),
            "class"    => "required-entry",
            "required" => true,
            "name"     => 'password',
        ));

        $fieldset->addField('telefone_ind', "select", array(
            "label"  => $this->__('Telephone Prefix'),
            'values' => Licentia_Fidelitas_Model_Subscribers::getPhonePrefixs(),
            "name"   => 'telefone_ind',
        ));

        $fieldset->addField('telefone_numero', "text", array(
            "label" => $this->__('Telephone Number'),
            "name"  => 'telefone_numero',
        ));


        $fieldset->addField('empresa', "text", array(
            "label"    => $this->__('Company'),
            "required" => true,
            "name"     => 'empresa',
        ));

        $fieldset->addField('localidade', "text", array(
            "label"    => $this->__('City'),
            "required" => true,
            "name"     => 'localidade',
        ));

        $countries = Mage::getModel('core/locale')->getCountryTranslationList();
        $countries = array_combine(array_values($countries), $countries);

        $fieldset->addField('pais', "select", array(
            "label"   => $this->__('Country'),
            "options" => $countries,
            "name"    => 'pais',
        ));

        $fieldset->addField('terms', "checkbox", array(
            "label"    => $this->__('I agree with E-Goi terms and conditions'),
            "note"     => $this->__('<a href="http://bo.e-goi.com/termos_uso_en.php" target="_blank" >Click here to read them</a>'),
            "checked"  => '1',
            "disabled" => '1',
            "name"     => 'terms',
        ));

        if ($current->getData()) {
            $form->setValues($current->getData());
        }

        return parent::_prepareForm();
    }

}
