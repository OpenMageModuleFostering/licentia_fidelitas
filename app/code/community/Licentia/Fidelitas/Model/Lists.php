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
                    if (isset($field['ref']) && $field['ref'] == 'store_ud') {
                        $i++;
                    }
                    if ($i == 1) {
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
        return;
        if ($id) {
            $list = $this->load($id);
        } else {
            $list = $this;
        }

        $store = Mage::app()->getStore();
        $url = $store->getBaseUrl() . 'egoi/callback/';

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

    public function getList($forceFields = false, $listCheck = false)
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

            $egoi = Mage::getModel('fidelitas/egoi')->getLists();

            if ($listCheck) {
                $ok = false;
                foreach ($egoi->getData() as $list) {
                    if ($list['listnum'] == $result->getData('listnum')) {
                        $ok = true;
                        break;
                    }
                }
                if (!$ok) {
                    return -1;
                }
            }

            $extra = Mage::getModel('fidelitas/egoi')
                ->setData(array('listID' => $result->getData('listnum')))
                ->getLists();
            $addExtra = true;
            $idMagentoStore = 0;

            foreach ($extra->getData() as $list) {
                if (isset($list['extra_fields']) && is_array($list['extra_fields'])) {
                    foreach ($list['extra_fields'] as $field) {
                        if (isset($field['ref']) && $field['ref'] == 'store_id') {
                            $idMagentoStore = $field['id'];
                            $addExtra = false;
                            break 2;
                        }
                    }
                }
            }

            if ($addExtra) {
                Mage::getModel('fidelitas/extra')->addInitialFields($result->getData('listnum'));
            } else {

                $existsStore = Mage::getModel('fidelitas/extra')
                    ->getCollection()
                    ->addFieldToFilter('attribute_code', 'store_id')
                    ->getFirstItem();

                if (!$existsStore->getId()) {
                    Mage::getModel('fidelitas/extra')
                        ->setData(array('extra_code' => 'extra_' . $idMagentoStore, 'attribute_code' => 'store_id'))
                        ->save();
                }

            }

        }

        return $result;
    }
}
