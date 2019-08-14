<?php

class Licentia_Fidelitas_Model_Egoi extends Varien_Object
{

    const PLUGIN_KEY = 'e419a126e087bed65ad7fe8342f2f493';
    const API_URL = 'http://api.e-goi.com/v2/soap.php?wsdl';


    protected $_client;


    public function _construct()
    {
        parent::_construct();

        ini_set('default_socket_timeout', 120);

        $this->_client = new Zend_Soap_Client(self::API_URL, array("user_agent" => "Mozilla/5.0 (Windows NT 6.1; rv:12.0) Gecko/20120403211507 Firefox/12.0"));
    }


    public function send($number, $message, $storeId = null)
    {

        if (!$number) {
            return false;
        }

        $method = Mage::getStoreConfig('fidelitas/config/method', $storeId);

        if ($method == 'campaign') {

            $this->setData('subject', 'Sent from Magento');
            $this->setData('fromID', Mage::getStoreConfig('fidelitas/config/sender', $storeId));
            $this->setData('listID', Mage::getModel('fidelitas/lists')->getList()->getData('listnum'));
            $this->setData('message', $message);
            $this->setData('cellphone', $number);

            $client = new Zend_XmlRpc_Client('http://api.e-goi.com/v2/xmlrpc.php');
            $result = $client->call('sendSMS', array($this->getDataKey()));

            $this->processServiceResult($this->_client->sendSMS($this->getDataKey()));

            Mage::log($this->getData(), 2, 'fidelitas-sms-data.log', true);

            if ($this->getData('id')) {
                return true;
            } else {
                return false;
            }

        } else {

            $url = 'https://www51.e-goi.com/api/public/sms/send';

            $data = array(
                "apikey"     => Mage::getStoreConfig('fidelitas/config/api_key', $storeId),
                "mobile"     => $number,
                "senderHash" => Mage::getStoreConfig('fidelitas/config/sender', $storeId),
                "message"    => $message,
            );

            $data = Zend_Json::encode($data);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, "$data");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = Zend_Json::decode($output);
            if (is_array($result) && is_array($result['errors'])) {
                $result = implode(' ', $result['errors']);
            }

            return ($http_status == 200) ? true : '. Server Response: ' . $result;
        }
    }

    public function getPhone($document)
    {

        if (!is_object($document) && stripos($document, '-') === false) {
            return false;
        }

        if (!is_object($document) && stripos($document, '-')) {
            return $this->validateNumber($document);
        }


        if ($document instanceof Mage_Sales_Model_Order) {
            $billing = $document->getBillingAddress();
        } else if ($document instanceof Mage_Customer_Model_Customer) {
            $billing = $document->getPrimaryBillingAddress();
        } elseif (is_object($document->getOrder())) {
            $billing = $document->getOrder()->getBillingAddress();
        }

        $prefix = Mage::getModel('fidelitas/subscribers')->getPrefixForCountry($billing->getCountryId());

        $cellphoneField = Mage::getStoreConfig('fidelitas/config/cellphone');
        $number = preg_replace('/\D/', '', $billing->getData($cellphoneField));
        $number = ltrim($number, $prefix);
        $number = ltrim($number, 0);

        return $this->validateNumber($prefix . '-' . $number);
    }

    public function validateNumber($number)
    {
        $url = 'https://www51.e-goi.com/api/public/sms/validatePhone/' . $number;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        if (!$number) {
            return false;
        }

        $final = Zend_Json::decode($result);

        if (array_key_exists('errorCode', $final)) {
            return false;
        }

        return $number;
    }

    public function validateEgoiEnvironment()
    {
        $auth = Mage::getSingleton('admin/session')->getUser()->getData('fidelitasAuth');

        if ($auth === true) {
            return true;
        }

        $info = $this->getUserData()->getData();

        if (!isset($info[0]) || !isset($info[0]['user_id']) || (int)$info[0]['user_id'] == 0) {
            return false;
        }

        $account = Mage::getModel('fidelitas/account')->getAccount();

        if ((int)$account->getData('cliente_id') == 0) {

            $n = Mage::getModel('fidelitas/egoi')->getAccountDetails()->getData();
            $account->addData($n[0])->save();

            $account = Mage::getModel('fidelitas/account')->getAccount();

            if ((int)$account->getData('cliente_id') == 0) {
                return false;
            }
        }

        Mage::getSingleton('admin/session')->getUser()->setData('fidelitasAuth', true);

        return true;
    }

    public function formatFields($data)
    {

        if (!is_array($data)) {
            $data = array('RESULT' => $data);
        }

        if (count($data) == 1 && isset($data['ERROR'])) {
            Mage::log(serialize($data), 2, 'fidelitas-egoi.log');
            $data = array(0 => $data);
            $this->setData($data);

            return;
        }

        if (!array_key_exists(0, $data)) {
            $data = array(0 => $data);
        }

        foreach ($data as $key => $value) {
            $data[$key] = array_change_key_case($value, CASE_LOWER);
        }

        $this->setData($data);

        return $this;
    }


    public function addSubscriberBulk($generate = false)
    {
        $meta = Mage::getModel('newsletter/subscriber')->getCollection()
            ->addFieldToFilter('subscriber_status', 1);

        $list = Mage::getModel('fidelitas/lists')->getList(true);
        $extra = Mage::getModel('fidelitas/extra')->getExtra();

        $processNumber = 500;

        if ($generate) {
            $processNumber = 999999999999;
        }

        $i = 0;

        try {
            while ($i * $processNumber <= $meta->getSize()) {

                $i++;

                $core = Mage::getModel('newsletter/subscriber')
                    ->getCollection()
                    ->setPageSize($processNumber)
                    ->setCurPage($i);

                $subscribers = array();
                $indexArray = array();
                $subI = 0;

                /** @var Mage_Newsletter_Model_Subscriber $subscriber */
                foreach ($core as $subscriber) {
                    $subI++;

                    try {
                        $storeId = $subscriber->getStoreId();

                        $data = array();

                        $data['email'] = $subscriber->getEmail();
                        $indexArray[] = 'email';

                        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                            $subscriber->delete();
                            continue;
                        }

                        $locale = Mage::getStoreConfig('general/locale/code', $storeId);
                        $language = Locale::getDisplayLanguage($locale);

                        /** @var Licentia_Fidelitas_Model_Subscribers $fidelitas */
                        $fidelitas = Mage::getModel('fidelitas/subscribers')->load($subscriber->getEmail(), 'email');
                        $customer = $fidelitas->findCustomer($subscriber->getEmail(), 'email');

                        foreach ($extra as $element) {
                            if ($element->getData('attribute_code') == 'magento_locale') {
                                $data[$element->getData('extra_code')] = $language;
                                $indexArray[] = $element->getData('extra_code');
                            }
                            if ($element->getData('attribute_code') == 'magento_store') {
                                $data[$element->getData('extra_code')] = Mage::getModel('adminhtml/system_store')->getStoreNameWithWebsite($storeId);
                                $indexArray[] = $element->getData('extra_code');
                            }
                            if ($element->getData('attribute_code') == 'magento_store_id') {
                                $data[$element->getData('extra_code')] = $storeId;
                                $indexArray[] = $element->getData('extra_code');
                            }

                            if (stripos($element->getData('attribute_code'), 'static_') !== false) {
                                $data[$element->getData('extra_code')] = $fidelitas->getData(str_replace('static_', '', $subscriber->getData('attribute_code')));
                                $indexArray[] = $element->getData('extra_code');
                            }
                        }


                        $data['birth_date'] = '';
                        $indexArray[] = 'birth_date';
                        $data['first_name'] = '';
                        $indexArray[] = 'first_name';
                        $data['last_name'] = '';
                        $indexArray[] = 'last_name';
                        $data['cellphone'] = '';
                        $indexArray[] = 'cellphone';

                        if ($customer) {
                            $data['birth_date'] = $customer->getData('dob');
                            $data['first_name'] = $customer->getData('firstname');
                            $data['last_name'] = $customer->getData('lastname');

                            if ($customer->getData('cellphone')) {
                                $data['cellphone'] = $customer->getData('cellphone');
                            }

                            foreach ($extra as $element) {

                                if (stripos($element->getData('attribute_code'), 'static_') !== false) {
                                    $data[$element->getData('extra_code')] = $customer->getData(str_replace('static_', '', $element->getData('attribute_code')));

                                    $indexArray[] = $element->getData('extra_code');
                                    continue;
                                }

                                $billing = false;
                                if (stripos($element->getData('attribute_code'), 'addr_') !== false) {
                                    $attributeCode = substr($element->getData('attribute_code'), 5);
                                    $billing = true;
                                } else {
                                    $attributeCode = $element->getData('attribute_code');
                                }

                                if (!$customer->getData($attributeCode)) {
                                    if ($billing) {
                                        $customer = $this->findCustomer($customer->getId(), 'entity_id', $attributeCode);
                                    } else {
                                        $customer = Mage::getModel('customer/customer')->load($customer->getId());
                                    }
                                }
                                if ($customer->getData($attributeCode)) {
                                    $data[$element->getData('extra_code')] = $customer->getData($attributeCode);

                                    $indexArray[] = $element->getData('extra_code');
                                }
                            }
                        }

                        $data['status'] = 1;
                        /*
                        $data['status'] = $subscriber->getStatus();
                        $indexArray[] = 'status';
                        if ($subscriber->getStatus() == 4) {
                            $data['status'] = 1;//Confirmed
                        }
                        if ($subscriber->getStatus() == 3) {
                            $data['status'] = 2;
                        }
                        if ($subscriber->getStatus() == 2) {
                            $data['status'] = 4;
                        }
                        */

                        if ($subI == 1) {
                            $subscribers[] = $indexArray;
                        }

                        $subscribers[] = $data;
                    } catch (Exception $e) {
                    }
                }

                if ($generate === true) {
                    return $subscribers;
                }

                $params = array(
                    'apikey'        => Mage::getStoreConfig('fidelitas/config/api_key'),
                    'plugin_key'    => self::PLUGIN_KEY,
                    'listID'        => $list->getListnum(),
                    'compareField'  => 'email',
                    'operation'     => 2,
                    'autoresponder' => 0,
                    'subscribers'   => $subscribers,
                );

                $this->processServiceResult($this->_client->addSubscriberBulk($params));
            }

        } catch (Exception $e) {
            return false;
        }
        return true;
    }


    public function getDataKey()
    {

        $data = $this->getData();
        $data['apikey'] = Mage::getStoreConfig('fidelitas/config/api_key');
        $data['plugin_key'] = self::PLUGIN_KEY;

        return $data;
    }


    public function processServiceResult($result, $index = null)
    {

        if (!is_array($result)) {
            $result = array('result' => $result);
        }

        $result = array_change_key_case($result, CASE_LOWER);

        if ($index && isset($result[$index])) {
            $result = $result[$index];
            $result = array_change_key_case($result, CASE_LOWER);
        }

        $this->setData($result);

        $additionalData = serialize(array('request' => $this->_client->getLastRequest(), 'response' => $this->_client->getLastResponse()));

        if (isset($result['error'])) {
            Mage::log(serialize($additionalData), 2, 'fidelitas-egoi.log', true);
            throw new Mage_Core_Exception(Mage::helper('fidelitas')->__($result['error']));
        }

        return $this;
    }

    public function addExtraField()
    {
        $this->setData('type', 'texto');
        return $this->processServiceResult($this->_client->addExtraField($this->getDataKey()));
    }

    public function getAccountDetails()
    {
        $this->formatFields($this->_client->getClientData($this->getDataKey()));

        return $this;
    }


    public function getUserData()
    {
        $this->formatFields($this->_client->getUserData($this->getDataKey()));

        return $this;
    }


    public function getSenders()
    {
        $this->setData('channel', 'telemovel');
        $this->formatFields($this->_client->getSenders($this->getDataKey()));

        return $this;
    }


    public function getLists($listnum = null)
    {

        $result = $this->_client->getLists($this->getDataKey());

        if (is_array($result)) {
            foreach ($result as $key => $value) {

                if ($listnum && $listnum != $value['listnum']) {
                    unset($result[$key]);
                }

                if (is_array($value) && (isset($value['extra_fields']) && !is_array($value['extra_fields']))) {
                    continue;
                }

                foreach ($value['extra_fields'] as $eKey => $eValue) {
                    unset($result[$key]['extra_fields'][$eKey]['listnum']);
                    unset($result[$key]['extra_fields'][$eKey]['opcoes']);
                }
            }
        }

        $this->formatFields($result);

        return $this;
    }


    public function getSubscriberData()
    {
        $this->formatFields($this->_client->subscriberData($this->getDataKey()));

        return $this;
    }


    public function editApiCallback()
    {
        return $this->processServiceResult($this->_client->editApiCallback($this->getDataKey()));
    }


    public function createList()
    {
        return $this->processServiceResult($this->_client->createList($this->getDataKey()));
    }


    public function updateList()
    {
        return $this->processServiceResult($this->_client->updateList($this->getDataKey()));
    }


    public function addSubscriber()
    {
        $this->setData('status', 1);
        return $this->processServiceResult($this->_client->addSubscriber($this->getDataKey()));
    }


    public function editSubscriber()
    {
        return $this->processServiceResult($this->_client->editSubscriber($this->getDataKey()));
    }


    public function removeSubscriber()
    {
        $result = Mage::getModel('fidelitas/egoi')
            ->setData('listID', $this->getData('listID'))
            ->setData('subscriber', $this->getSubscriber())
            ->getSubscriberData()
            ->getData();

        if (is_array($result) && $result[0]['subscriber']['STATUS'] != 2) {
            return $this->processServiceResult($this->_client->removeSubscriber($this->getDataKey()));
        }

        if ($this->getData('inCron')) {
            return;
        }

        return array('error' => Mage::helper('fidelitas')->__('Subscriber not found or action not allowed'));
    }


    public function checkLogin($apiKey = null)
    {

        $data = $this->getDataKey();
        if ($apiKey) {
            $data['apikey'] = $apiKey;
        }
        $this->processServiceResult($this->_client->checklogin($data));

        return $this;
    }


    public function sync()
    {
        $account = Mage::getModel('fidelitas/account')->getAccount();
        $key = Mage::getStoreConfig('fidelitas/config/api_key');

        if (!$key) {
            return;
        }

        $models = array('subscribers', 'account');
        foreach ($models as $sync) {
            Mage::getModel('fidelitas/' . $sync)->cron();
        }

        $account->setData('cron', 0)->save();
    }


    public function syncm()
    {
        $account = Mage::getModel('fidelitas/account')->getAccount();

        if ($account->getCron() == 3) {
            Mage::registry('fidelitas_first_run', true);
        }
        if ($account->getCron() == 1 || $account->getCron() == 3) {
            $account->setData('cron', 0)->save();
            Mage::getModel('fidelitas/egoi')->sync();
        }
    }

}
