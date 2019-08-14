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
class Licentia_Fidelitas_Adminhtml_Fidelitas_AutorespondersController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('fidelitas/autoresponders');

        if (!Mage::getStoreConfig('fidelitas/config/api_key') ||
            !Mage::getStoreConfig('fidelitas/config/sender')
        ) {
            $this->_redirect('*/system_config/edit', array('section' => 'fidelitas'));
        }

        return $this;
    }

    public function indexAction()
    {
        $this->_title($this->__('E-Goi'))->_title($this->__('SMS Notifications'));

        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('fidelitas/adminhtml_autoresponders'));
        $this->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $this->_title($this->__('E-Goi'))->_title($this->__('SMS Notifications'));

        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('fidelitas/autoresponders')->load($id);
        $model->setData('store_ids', explode(',', $model->getStoreIds()));

        if ($model->getId() || $id == 0) {

            $data = $this->_getSession()->getFormData();

            if (!empty($data)) {
                $model->addData($data);
            }
            Mage::register('current_autoresponder', $model);


            $this->_title($model->getId() ? $model->getName() : $this->__('New'));

            $this->loadLayout();
            $this->_setActiveMenu('fidelitas/autoresponders');

            $this->_addContent($this->getLayout()->createBlock('fidelitas/adminhtml_autoresponders_edit'))
                ->_addLeft($this->getLayout()->createBlock('fidelitas/adminhtml_autoresponders_edit_tabs'));
            $this->renderLayout();
        } else {
            $this->_getSession()->addError($this->__('SMS Notification does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function saveAction()
    {

        if ($this->getRequest()->getPost()) {

            $data = $this->getRequest()->getPost();
            $data = $this->_filterDates($data, array('from_date', 'to_date'));

            $id = $this->getRequest()->getParam('id');

            $data['store_ids'] = implode(',', $data['store_ids']);

            $model = Mage::getModel('fidelitas/autoresponders');

            try {
                if ($id) {
                    $model->setId($id);
                }

                if (isset($data['product'])) {
                    $data['product'] = trim($data['product']);
                }

                $model->addData($data);

                if ($model->getData('event') == 'order_product') {
                    $product = Mage::getModel('catalog/product')->load($model->getData('product'));

                    if (!$product->getId()) {
                        throw new Mage_Core_Exception('Product Not Found');
                    }
                }

                $model->save();

                $this->_getSession()->setFormData(false);
                $this->_getSession()->addSuccess($this->__('The SMS Notification has been saved.'));

                // check if 'Save and Continue'
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_getSession()->setFormData($data);

                if ($this->getRequest()->getParam('id')) {
                    $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                } else {
                    $this->_redirect('*/*/new');
                }

                return;
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('An error occurred while saving the SMS Notification data. Please review the log and try again.'));
                Mage::logException($e);
                $this->_getSession()->setFormData($data);
                $this->_redirect('*/*/new', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {


        if ($id = $this->getRequest()->getParam('id')) {
            try {

                $model = Mage::getModel('fidelitas/autoresponders');
                $model->load($id);
                $model->delete();

                $this->_getSession()->addSuccess($this->__('The SMS Notification has been deleted.'));
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('An error occurred while deleting the SMS Notification. Please review the log and try again.'));
                Mage::logException($e);
                $this->_redirect('*/*/edit', array('id' => $id));
                return;
            }
        }
        $this->_getSession()->addError($this->__('Unable to find a SMS Notification to delete.'));
        $this->_redirect('*/*/');
    }

    public function validateEnvironmentAction()
    {
        $params = $this->getRequest()->getParams();
        $number = $params['number'];

        if (!Mage::getModel('fidelitas/egoi')->validateNumber($number)) {
            $this->_getSession()->addError($this->__('Please insert a valid Phone Number xxx-xxxxxx'));
            $this->_redirectReferer();
            return;
        }

        $result = Mage::getModel('fidelitas/egoi')->send($number, 'Test Message from Magento Store');

        if ($result !== true) {
            $this->_getSession()->addError($this->__('ERROR: Check your settings' . $result));
        } else {
            $this->_getSession()->addSuccess($this->__('Message Sent'));
        }

        $this->_redirectReferer();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('fidelitas/autoresponders');
    }

}
