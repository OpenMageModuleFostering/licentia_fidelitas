<?php

class Licentia_Fidelitas_Model_Extra extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {

        $this->_init('fidelitas/extra');
    }

    public function addInitialFields($list = false)
    {
        $this->addField('store_id', $list);
    }


    public function addField($name, $list = false)
    {
        if (!$list) {
            $list = Mage::getModel('fidelitas/lists')->getList()->getData('listnum');
        }

        $extra = Mage::getModel('fidelitas/egoi')
            ->setData(array('listID' => $list))
            ->getLists();

        foreach ($extra->getData() as $extraField) {
            if (isset($extraField['extra_fields']) && is_array($extraField['extra_fields'])) {
                foreach ($extraField['extra_fields'] as $field) {
                    if (isset($field['ref']) && $field['ref'] == $name) {
                        return true;
                    }
                }
            }
        }

        $data = array('listID' => $list, 'name' => $name);
        $result = Mage::getModel('fidelitas/egoi')->setData($data)->addExtraField();

        return $this->setData(array(
                'extra_code'     => 'extra_' . $result->getData('new_id'),
                'attribute_code' => $name,
                'system'         => 1)
        )
            ->save();

    }


    public function updateExtra($data, $system = 0)
    {
        $collection = $this->getCollection();

        foreach ($collection as $item) {
            $item->delete();
        }

        foreach ($data as $key => $value) {

            if ($value == '0') {
                continue;
            }

            $new = array();
            $new['attribute_code'] = $value;
            $new['extra_code'] = $key;
            $new['system'] = $system;

            $this->setData($new)->save();
        }
    }

    public function getExtra()
    {
        $collection = $this->getCollection();
        foreach ($collection as $item) {

            if (is_numeric($item->getData('extra_code'))) {
                $item->setData('extra_code', 'extra_' . $item->getData('extra_code'));
            }

        }
        return $collection;
    }

}
