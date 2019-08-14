<?php

class Licentia_Fidelitas_Adminhtml_Fidelitas_AccountController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {

        $this->loadLayout()
             ->_setActiveMenu('fidelitas/account');

        return $this;
    }

    public function refreshAction()
    {


        $cron = Mage::getModel('cron/schedule');
        $data['status'] = 'pending';
        $data['job_code'] = 'fidelitas_sync_subscribers';
        $data['scheduled_at'] = Mage::getSingleton('core/date')->gmtDate();
        $data['created_at'] = Mage::getSingleton('core/date')->gmtDate();
        $cron->setData($data)->save();
        $this->_getSession()->addSuccess($this->__('Data will be synced next time cron runs'));


        return $this->_redirectReferer();
    }

    public function validateSmtpAction()
    {

        try {

            $transport = Mage::helper('fidelitas')->getSmtpTransport();

            $salesSender = Mage::getStoreConfig('trans_email/ident_general/name');
            $salesEmail = Mage::getStoreConfig('trans_email/ident_general/email');

            $mail = new Zend_Mail('utf-8');
            $mail->setBodyHtml(
                'If you are receiving this message, everything seems to be ok with your SMTP configuration'
            );
            $mail->setFrom($salesEmail, $salesSender)
                 ->addTo($salesEmail, $salesSender)
                 ->setSubject('E-Goi / Magento - Test');

            $mail->send($transport);

            $this->_getSession()->addSuccess(
                'Success. Everything seems to be ok with your setting. We sent an email to ' . $salesEmail
            );

        } catch (Exception $e) {
            $this->_getSession()->addError('Error Testing your Settings: ' . $e->getMessage());
        }

        return $this->_redirectReferer();
    }

    public function listAction()
    {

        try {
            $listnum = $this->getRequest()->getPost('list_id');

            if ($listnum) {
                Mage::getModel('fidelitas/lists')->getList()->setData('listnum', $listnum)->save();
                Mage::getModel('fidelitas/extra')->getCollection()->walk('delete');
                Mage::getModel('fidelitas/lists')->getList(true);

                /** @var Mage_Core_Model_Resource $resource */
                $resource = Mage::getSingleton('core/resource');
                $write = $resource->getConnection('core_write');
                $write->update($resource->getTableName('fidelitas_subscribers'), ['list' => $listnum]);

            }
            $this->_getSession()->addSuccess($this->__('List Updated. Please map the attributes to the new list'));

        } catch (Exception $e) {

            $this->_getSession()->addError($e->getMessage());
        }

        $this->_redirect('*/fidelitas_lists/');

        return;
    }

    public function indexAction()
    {

        try {
            $cron = Mage::getModel('cron/schedule')
                        ->getCollection()->setOrder('finished_at', 'DESC')
                        ->setPageSize(1)
                        ->getFirstItem();

            $firstDay = new Zend_Date($cron['finished_at']);
            $lastDay = new Zend_Date(now());
            $diff = $lastDay->sub($firstDay)->toValue('m');

            if ($diff > 20 || !$cron->getId()) {
                $this->_getSession()->addError(
                    $this->__('WARNING: Your cron is not running. Background data sync will not occur.')
                );
            }

            $auth = Mage::getModel('fidelitas/egoi')->validateEgoiEnvironment();
            if (!$auth) {
                $this->_redirect('adminhtml/fidelitas_account/new');

                return;
            }

            $okList = Mage::getModel('fidelitas/lists')->getList(true, true);

            if (is_integer($okList) && $okList == -1) {
                $this->_getSession()->addError(
                    $this->__(
                        'WARNING: We cannot find your E-Goi List Mapped to this Store. If this errors continues, please use the section on your right "Clear Data" to disconnect and start the mapping process again'
                    )
                );
            }
        } catch (Exception $e) {

        }

        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('fidelitas/adminhtml_account'));
        $this->renderLayout();
    }

    public function bulkAction()
    {

        if ($this->getRequest()->getParam('export')) {

            unlink(Mage::getBaseDir('tmp') . '/egoi_export.csv');

            $cron = Mage::getModel('cron/schedule');
            $data['status'] = 'pending';
            $data['job_code'] = 'fidelitas_export_bulk';
            $data['scheduled_at'] = Mage::getSingleton('core/date')->gmtDate();
            $data['created_at'] = Mage::getSingleton('core/date')->gmtDate();
            $cron->setData($data)->save();

            $file = Mage::getBaseDir('tmp') . '/egoi.txt';
            file_put_contents($file, '0');

            #Mage::getModel('fidelitas/egoi')->addSubscriberBulk(true);
            #$file = Mage::getBaseDir('tmp') . '/egoi_export.csv';
            #return $this->_prepareDownloadResponse('egoi_export.csv', file_get_contents($file));

            $this->_getSession()->addSuccess('You will get an email when the file is reafy to download');

            return $this->_redirect('*/fidelitas_account/index/');
        }

        $cron = Mage::getModel('cron/schedule');
        $data['status'] = 'pending';
        $data['job_code'] = 'fidelitas_sync_bulk';
        $data['scheduled_at'] = Mage::getSingleton('core/date')->gmtDate();
        $data['created_at'] = Mage::getSingleton('core/date')->gmtDate();
        $cron->setData($data)->save();
        $this->_getSession()->addSuccess($this->__('Data will be synced next time cron runs'));

        $this->_redirect('*/*/');

        return;

    }

    public function clearAction()
    {

        $core = Mage::getModel('core/config');
        $core->saveConfig('fidelitas/config/api_key', "0", 'default', 0);

        $data = ['lists', 'autoresponders', 'events', 'subscribers', 'extra'];

        $resource = Mage::getSingleton('core/resource');
        $write = $resource->getConnection('core_write');

        foreach ($data as $delete) {
            try {
                $model = Mage::getModel('fidelitas/' . $delete);
                if (!$model) {
                    continue;
                }
                $write->truncateTable($model->getResource()->getMainTable());

            } catch (Exception $e) {

            }
        }

        Mage::getModel('fidelitas/account')->getAccount()->delete();

        Mage::getSingleton('admin/session')->getUser()->setData('fidelitasAuth', false);
        Mage::getConfig()->reinit();
        Mage::app()->reinitStores();

        $this->_redirect('*/*/');
    }

    public function supportAction()
    {

        $cron = Mage::getModel('cron/schedule')
                    ->getCollection()
                    ->setOrder('finished_at', 'DESC')
                    ->setPageSize(1)
                    ->getFirstItem();

        $firstDay = new Zend_Date($cron['finished_at']);
        $lastDay = new Zend_Date(now());
        $diff = $lastDay->sub($firstDay)->get('m');

        if ($diff > 20 || !$cron->getId()) {
            $this->_getSession()->addError(
                $this->__('WARNING: Your cron is not running. Background data sync will not occur.')
            );
        }

        $info = Mage::getModel('fidelitas/egoi')->getUserData()->getData();

        Mage::register('current_account', $info[0]);

        if ($this->getRequest()->isPost()) {

            $params = array_merge($info[0], $this->getRequest()->getPost());

            unset($params['form_key']);
            unset($params['usernname']);
            unset($params['credits']);
            unset($params['user_level']);
            unset($params['fax']);
            unset($params['gender']);
            unset($params['api_key']);

            $email = 'integrations@e-goi.com';

            $msg = '';
            $params['date'] = Mage::getSingleton('core/date')->gmtDate();

            foreach ($params as $key => $value) {
                $msg .= "$key : $value <br>";
            }

            $mail = Mage::getModel('core/email');
            $mail->setToName('Support');
            $mail->setToEmail($email);
            $mail->setBody($msg);
            $mail->setSubject('Contacto - Magento Extension');
            $mail->setFromEmail($params['email']);
            $mail->setFromName($params['first_name'] . ' ' . $params['last_name']);
            $mail->setType('html');

            try {
                $t = $mail->send();

                if ($t === false) {
                    throw new Exception('Unable to send. Please send an email to ' . $email);
                }

                $this->_getSession()->addSuccess($this->__('Your request has been sent'));
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($e->getMessage());
                $this->_redirect('*/*/support');
            }

            $this->_redirectReferer();

            return;
        }

        $this->loadLayout();
        $this->_setActiveMenu('fidelitas/account');

        $this->_addContent($this->getLayout()->createBlock('fidelitas/adminhtml_account_support_edit'))
             ->_addLeft($this->getLayout()->createBlock('fidelitas/adminhtml_account_support_edit_tabs'));

        $this->renderLayout();
    }

    public function newAction()
    {

        $this->getRequest()->setParam('op', 'api');
        $op = $this->getRequest()->getParam('op');

        if ($op == 'api') {
            $this->_getSession()->addNotice(
                $this->__(
                    "If you don't have an E-Goi account please %s. If you want to know more about E-Goi %s",
                    '<a target="_blank" href="http://bo.e-goi.com/?action=registo&aff=fadb7a3c20">click here</a>',
                    '<a target="_blank" href="http://www.e-goi.com/index.php?aff=fadb7a3c20">click here</a>'
                )
            );
        } else {
            $this->_getSession()->addNotice(
                $this->__(
                    'If you already have an E-Goi account please %s',
                    '<a href="' . $this->getUrl('*/*/*/op/api') . '">click here</a>'
                )
            );
        }

        $model = new Varien_Object();

        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        Mage::register('current_account', $model);

        $this->loadLayout();
        $this->_setActiveMenu('fidelitas/account');

        $this->_addContent($this->getLayout()->createBlock('fidelitas/adminhtml_account_new_edit'))
             ->_addLeft($this->getLayout()->createBlock('fidelitas/adminhtml_account_new_edit_tabs'));

        $this->renderLayout();
    }

    public function firstAction()
    {

        $this->_initAction();
        $this->_getSession()->setData('fidelitas_first_run', true);

        try {
            Mage::getModel('fidelitas/lists')->getList(true);
            Mage::getModel('fidelitas/account')->getAccount();

        } catch (Exception $e) {
            $this->_getSession()->addError('NO_MORE_LISTS_ALLOWED');
            $this->_redirect('*/*/', ['id' => $this->getRequest()->getParam('id')]);

            return;
        }

        $this->_redirect('*/*/sync');

        return;
    }

    public function syncAction()
    {

        $admin = Mage::getSingleton('admin/session')->getUser();
        $user = $admin->getId();

        if ($this->_getSession()->getData('fidelitas_first_run') === true) {
            Mage::getModel('fidelitas/account')->getAccount()->setData('cron', 3)->setData('notify_user', $user)->save(
            );
        } else {
            Mage::getModel('fidelitas/account')->getAccount()->setData('cron', 1)->setData('notify_user', $user)->save(
            );
        }

        $cron = Mage::getModel('cron/schedule')->getCollection()
                    ->addFieldToFilter('job_code', 'fidelitas_sync_manually')
                    ->addFieldToFilter('status', 'pending');

        if ($cron->getSize() > 0) {
            $this->_getSession()->addError($this->__('Please wait until previous cron ends'));
        } else {
            $cron = Mage::getModel('cron/schedule');
            $data['status'] = 'pending';
            $data['job_code'] = 'fidelitas_sync_manually';
            $data['scheduled_at'] = Mage::getSingleton('core/date')->gmtDate();
            $data['created_at'] = Mage::getSingleton('core/date')->gmtDate();
            $cron->setData($data)->save();
            $this->_getSession()->addSuccess($this->__('Data will be synced next time cron runs'));
        }

        $this->_redirect('*/*/');

        return;
    }

    public function saveAction()
    {

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            try {
                $model = Mage::getModel('fidelitas/egoi')->setData('api_key', $data['api_key'])->checkLogin(
                    $data['api_key']
                );
                if ($model->getData('user_id')) {
                    Mage::getConfig()->saveConfig('fidelitas/config/api_key', $data['api_key']);
                    Mage::getConfig()->cleanCache();

                    $lists = Mage::getModel('fidelitas/egoi')->getLists();
                    if (count($lists->getData()) == 0) {
                        $this->_getSession()->addSuccess(
                            $this->__(
                                'Success!!! Please wait while we setup the environment. Don\'t close or refresh this page.'
                            )
                        );
                    } else {
                        $this->_getSession()->addSuccess($this->__('Success!!!'));
                    }
                    $this->_redirect('*/*/first/op/ok');

                    return;
                }

            } catch (Exception $e) {

                $this->_getSession()->addError($this->__('Apikey invalid'));
                $this->_redirect('*/*/new/op/api');

                return;
            }

            $this->_redirect('*/*/');
        }
    }

    protected function _isAllowed()
    {

        return Mage::getSingleton('admin/session')->isAllowed('fidelitas/account');
    }

}
