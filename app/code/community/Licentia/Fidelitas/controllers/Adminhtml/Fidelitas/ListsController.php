<?php

class Licentia_Fidelitas_Adminhtml_Fidelitas_ListsController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('fidelitas/lists');

        $auth = Mage::getModel('fidelitas/egoi')->validateEgoiEnvironment();
        if (!$auth) {
            $this->_redirect('adminhtml/fidelitas_account/new');
            return;
        }

        return $this;
    }

    public function indexAction()
    {

        $list = Mage::getModel('fidelitas/lists')->getList();

        $this->_redirect('*/*/edit', array('id' => $list->getId()));
        return;

        $this->_title($this->__('E-Goi'))->_title($this->__('Lists'));
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('fidelitas/adminhtml_lists'));
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function editAction()
    {
        $this->_title($this->__('E-Goi'))->_title($this->__('Lists'));
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('fidelitas/lists');
        Mage::register('current_list', $model);

        $model->load($id);
        if (!$model->getId()) {
            $this->_getSession()->addError($this->__('This list no longer exists.'));
            $this->_redirect('*/*');
            return;
        }

        if ($model->getId()) {
            $extra = Mage::getModel('fidelitas/extra')->getExtra();
            foreach ($extra as $item) {
                $model->setData($item->getData('extra_code'), $item->getData('attribute_code'));
            }
        }

        $this->_title($model->getTitle());

        // set entered data if was error when we do save
        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        $this->_initAction();

        $this->_addContent($this->getLayout()->createBlock('fidelitas/adminhtml_lists_edit'))
            ->_addLeft($this->getLayout()->createBlock('fidelitas/adminhtml_lists_edit_tabs'));

        $this->renderLayout();
    }


    public function saveAction()
    {

        if ($data = $this->getRequest()->getPost()) {

            $model = Mage::getModel('fidelitas/lists')->getList();

            try {
                $extra = array();

                foreach ($data as $key => $element) {
                    if (stripos($key, 'extra_') !== false) {
                        $extra[$key] = $element;
                    }
                }

                $model->addData($data)->save();

                if ($data['listID']) {
                    Mage::getModel('fidelitas/extra')->updateExtra($extra, 1);
                }

                $this->_getSession()->addSuccess($this->__('List was successfully saved'));
                $this->_getSession()->setFormData(false);

                // check if 'Save and Continue'
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_getSession()->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        $this->_getSession()->addError($this->__('Unable to find List to save'));
        $this->_redirect('*/*/');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('fidelitas/lists');
    }

}
