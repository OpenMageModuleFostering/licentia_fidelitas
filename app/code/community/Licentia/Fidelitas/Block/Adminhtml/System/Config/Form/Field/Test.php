<?php


class Licentia_Fidelitas_Block_Adminhtml_System_Config_Form_Field_Test extends Mage_Adminhtml_Block_System_Config_Form_Field {

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {

        $url = $this->getUrl('*/fidelitas_autoresponders/validateEnvironment');

        return '<button  onclick="window.location=\'' . $url . 'number/\'+$F(\'fidelitas_test_number\')" class="scalable" type="button" ><span><span><span>' . Mage::helper('fidelitas')->__('Test Now') . '</span></span></span></button>';
    }

}
