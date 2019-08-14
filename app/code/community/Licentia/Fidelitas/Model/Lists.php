<?php

class Licentia_Fidelitas_Model_Lists extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {

        $this->_init('fidelitas/lists');
    }


    public function save()
    {

        $model = Mage::getModel('fidelitas/egoi');
        $data = $this->getData();

        $this->setData('canal_email', '1');

        $egoi = Mage::getModel('fidelitas/egoi')->getLists();
        foreach ($egoi->getData() as $list) {
            if (isset($list['extra_fields']) && is_array($list['extra_fields'])) {
                $i = 0;
                foreach ($list['extra_fields'] as $field) {
                    if (isset($field['ref']) && $field['ref'] == 'magento_store_id') {
                        $i++;
                    }
                    if (isset($field['ref']) && $field['ref'] == 'magento_store') {
                        $i++;
                    }
                    if (isset($field['ref']) && $field['ref'] == 'magento_locale') {
                        $i++;
                    }
                    if ($i == 2) {
                        $this->setData('listnum', $list['listnum']);
                        break 2;
                    }
                }
            }
        }

        if (!$this->getData('listID') && $this->getData('listnum')) {
            $this->setData('listID', $this->getData('listnum'));
            $data['listID'] = $this->getData('listnum');
        }

        $total = $this->getCollection()->getFirstItem();

        if ($total->getId()) {
            $this->setId($total->getId());
        }

        if ($this->getData('listnum')) {

            if (isset($data['nome'])) {
                $data['name'] = $data['nome'];
            }
            $data['title'] = $data['nome'];
            if (isset($data['nome'])) {
                $this->setData('title', $data['nome']);
            }
            $model->addData($data);
            $model->updateList($data);
        } else {

            $model->setData($data);
            $model->createList();
            $this->setData('listnum', $model->getData('list_id'));
            $this->setData('title', $data['nome']);
        }

        $parent = parent::save();


        return $parent;
    }

    public function _afterSave()
    {
        $this->updateCallback();

        return parent::_afterSave();
    }

    public function updateCallback($id = null)
    {

        if ($id) {
            $list = $this->load($id);
        } else {
            $list = $this;
        }

        $store = Mage::app()->getStore();
        $url = $store->getBaseUrl() . 'fidelitas/callback/';

        $callback = array();
        $callback['listID'] = $list->hasListnum() ? $list->getData('listnum') : $list->getData('listID');
        $callback['callback_url'] = $url;

        $callback['notif_api_1'] = 1;
        $callback['notif_api_2'] = 1;
        $callback['notif_api_3'] = 1;
        $callback['notif_api_7'] = 1;
        $callback['notif_api_8'] = 1;
        $callback['notif_api_9'] = 1;
        $callback['notif_api_10'] = 1;
        $callback['notif_api_15'] = 1;

        Mage::getModel('fidelitas/egoi')->setData($callback)->editApiCallback();
    }

    public function getList($forceFields = false)
    {
        $result = $this->getCollection()->getFirstItem();

        if (!$result->getId()) {
            $data = array();
            $data['nome'] = 'General';
            $data['title'] = 'General';
            $data['name'] = 'General';
            $data['internal_name'] = '[Magento List]';
            $this->setData($data)->save();
            $result = $this;
        }


        if ($forceFields && $result->getData('listnum')) {

            $extra = Mage::getModel('fidelitas/egoi')
                ->setData(array('listID' => $result->getData('listnum')))
                ->getLists();
            $addExtra = true;
            $idMagentoStore = 0;
            $idMagentoStoreId = 0;
            $idMagentoLocale = 0;
            foreach ($extra->getData() as $list) {
                if (isset($list['extra_fields']) && is_array($list['extra_fields'])) {
                    $i = 0;
                    foreach ($list['extra_fields'] as $field) {
                        if (isset($field['ref']) && $field['ref'] == 'magento_store_id') {
                            $idMagentoStoreId = $field['id'];
                            $i++;
                        }
                        if (isset($field['ref']) && $field['ref'] == 'magento_store') {
                            $idMagentoStore = $field['id'];
                            $i++;
                        }
                        if (isset($field['ref']) && $field['ref'] == 'magento_locale') {
                            $idMagentoLocale = $field['id'];
                            $i++;
                        }
                        if ($i == 3) {
                            $addExtra = false;
                            break 2;
                        }
                    }
                }
            }

            if ($addExtra) {
                Mage::getModel('fidelitas/extra')->addInitialFields($result->getData('listnum'));
            } else {

                $existsLocale = Mage::getModel('fidelitas/extra')
                    ->getCollection()
                    ->addFieldToFilter('attribute_code', 'magento_locale')
                    ->getFirstItem();

                $existsStore = Mage::getModel('fidelitas/extra')
                    ->getCollection()
                    ->addFieldToFilter('attribute_code', 'magento_store')
                    ->getFirstItem();


                $existsStoreId = Mage::getModel('fidelitas/extra')
                    ->getCollection()
                    ->addFieldToFilter('attribute_code', 'magento_store_id')
                    ->getFirstItem();


                if (!$existsLocale->getId()) {
                    Mage::getModel('fidelitas/extra')
                        ->setData(array('extra_code' => 'extra_' . $idMagentoLocale, 'attribute_code' => 'magento_locale'))
                        ->save();
                }

                if (!$existsStore->getId()) {
                    Mage::getModel('fidelitas/extra')
                        ->setData(array('extra_code' => 'extra_' . $idMagentoStore, 'attribute_code' => 'magento_store'))
                        ->save();
                }

                if (!$existsStoreId->getId()) {
                    Mage::getModel('fidelitas/extra')
                        ->setData(array('extra_code' => 'extra_' . $idMagentoStoreId, 'attribute_code' => 'magento_store_id'))
                        ->save();
                }

            }

        }

        return $result;
    }
}
