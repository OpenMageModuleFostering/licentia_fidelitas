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
class Licentia_Fidelitas_Adminhtml_Fidelitas_EventsController extends Mage_Adminhtml_Controller_Action {

    protected function _initAction() {
        $this->loadLayout()
                ->_setActiveMenu('fidelitas/events');

        return $this;
    }

    public function indexAction() {

        $this->_title($this->__('E-Goi'))->_title($this->__('Notifications Events'));

        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('fidelitas/adminhtml_events'));
        $this->renderLayout();
    }

    public function gridAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function massDeleteAction() {
        $changes = $this->getRequest()->getParam('events');
        if (!is_array($changes)) {
            $this->_getSession()->addError($this->__('Please select event(s).'));
        } else {
            try {
                foreach ($changes as $record) {
                    Mage::getModel('fidelitas/events')->load($record)->delete();
                }
                $this->_getSession()->addSuccess($this->__('Total of %d record(s) were deleted.', count($changes)));
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirectReferer();
    }

    /**
     * Export customer grid to CSV format
     */
    public function exportCsvAction() {
        $fileName = 'events.csv';
        $content = $this->getLayout()->createBlock('fidelitas/adminhtml_events_grid')
                ->getCsvFile();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export customer grid to XML format
     */
    public function exportXmlAction() {
        $fileName = 'events.xml';
        $content = $this->getLayout()->createBlock('fidelitas/adminhtml_events_grid')
                ->getExcelFile();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('fidelitas/events');
    }

}
