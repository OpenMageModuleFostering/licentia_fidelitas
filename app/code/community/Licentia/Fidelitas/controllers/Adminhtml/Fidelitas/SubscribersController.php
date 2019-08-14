<?php

class Licentia_Fidelitas_Adminhtml_Fidelitas_SubscribersController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('fidelitas/subscribers');

        $auth = Mage::getModel('fidelitas/egoi')->validateEgoiEnvironment();
        if (!$auth) {
            $this->_redirect('adminhtml/fidelitas_account/new');
            return;
        }
        return $this;
    }

    public function indexAction()
    {
        $this->_initAction();

        $this->_title($this->__('E-Goi'))->_title($this->__('Subscribers'));

        $list = Mage::getModel('fidelitas/lists')->getList();
        Mage::register('current_list', $list);

        $this->_addContent($this->getLayout()->createBlock('fidelitas/adminhtml_subscribers'));
        $this->renderLayout();
    }

    public function gridAction()
    {
        $list = Mage::getModel('fidelitas/lists')->load($this->getRequest()->getParam('list', 0), 'listnum');
        Mage::register('current_list', $list);

        $this->loadLayout();
        $this->renderLayout();
    }


    public function listAction()
    {
        $this->_title($this->__('E-Goi'))->_title($this->__('Subscribers'));
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('fidelitas/adminhtml_subscribers'));
        $this->renderLayout();
    }


    public function editAction()
    {

        $this->_title($this->__('E-Goi'))->_title($this->__('Subscribers'))->_title($this->__('Edit'));
        $id = $this->getRequest()->getParam('id');

        $model = Mage::getModel('fidelitas/subscribers')->load($id);

        $this->_title($model->getId() ? $model->getName() : $this->__('New'));

        if ($model->getId() || $id == 0) {
            $data = $this->_getSession()->getFormData(true);
            if (!empty($data)) {
                $model->addData($data);
            }

            $model->setData('list', $model->getData('list') . '-' . $model->getData('store_id'));
            $model->setData('cellphone_prefix', '+' . substr($model->getData('cellphone'), 0, strpos($model->getData('cellphone'), '-')));
            $model->setData('cellphone', substr($model->getData('cellphone'), strpos($model->getData('cellphone'), '-') + 1));

            Mage::register('current_subscriber', $model);

            $this->loadLayout();
            $this->_setActiveMenu('fidelitas/subscriber');

            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Subscriber Manager'), Mage::helper('adminhtml')->__('Subscriber Manager'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Subscriber'), Mage::helper('adminhtml')->__('Subscriber'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent($this->getLayout()->createBlock('fidelitas/adminhtml_subscribers_edit'))
                ->_addLeft($this->getLayout()->createBlock('fidelitas/adminhtml_subscribers_edit_tabs'));

            $this->renderLayout();
        } else {
            $this->_getSession()->addError($this->__('Subscriber does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function saveAction()
    {

        if ($data = $this->getRequest()->getPost()) {

            if (strlen($data['cellphone']) > 0) {
                $data['cellphone'] = $data['cellphone_prefix'] . '-' . $data['cellphone'];
            }
            $id = $this->getRequest()->getParam('id');

            $model = Mage::getModel('fidelitas/subscribers');
            $model->setData($data);

            try {

                if ($id) {
                    $model->setId($id);
                }

                $model->save();

                $this->_getSession()->addSuccess($this->__('Subscriber was successfully saved'));
                $this->_getSession()->setFormData(false);


                // check if 'Save and Continue'
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/index/');
                return;
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_getSession()->setFormData($data);
                $this->_redirect('*/*/edit/id/' . $id);
                return;
            }
        }

        $this->_getSession()->addError($this->__('Unable to find subscriber to save'));
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {

        if ($this->getRequest()->getParam('id')) {

            try {

                $id = $this->getRequest()->getParam('id');

                $db = Mage::getModel('fidelitas/subscribers')->load($id);
                $db->delete();

                $this->_getSession()->addSuccess(Mage::helper('adminhtml')->__('Subscriber was successfully deleted'));

                $this->_redirect('*/*/');
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_redirect('*/*/');
            }
        } else {
            $this->_redirect('*/*/');
        }
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('fidelitas/subscribers');
    }

}
