<?php

class Licentia_Fidelitas_DownloadController extends Mage_Core_Controller_Front_Action
{

    public function indexAction()
    {

        $token = file_get_contents(Mage::getBaseDir('tmp') . '/egoi_token.txt');

        if ($token != $this->getRequest()->getParam('token')) {

            Mage::getSingleton('core/session')->addError($this->__('Invalid token'));

            return $this->_redirect('/');
        }
        $file = Mage::getBaseDir('tmp') . '/egoi.txt';
        if (is_file($file)) {
            unlink($file, '0');
        }

        return $this->_prepareDownloadResponse('egoi_export.csv', file_get_contents(Mage::getBaseDir('tmp') . '/egoi_export.csv'));

    }

}
