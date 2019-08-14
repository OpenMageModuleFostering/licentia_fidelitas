<?php

class Licentia_Fidelitas_CallbackController extends Mage_Core_Controller_Front_Action
{

    public function indexAction()
    {

        $remove = $this->getRequest()->getPost('removeSubscriber');
        $add = $this->getRequest()->getPost('addSubscriber');
        $data = isset($remove) ? $remove : $add;
        $data = $this->_object2array(simplexml_load_string($data));

        if (!is_array($data)) {
            return;
        }
        $data = array_change_key_case($data);

        foreach ($data as $key => $value) {
            if (is_array($value) && count($value) == 0) {
                unset($data[$key]);
            }
        }

        $data['inCron'] = true;
        $data['inCallback'] = true;

        $newletter = Mage::getModel('fidelitas/subscribers');
        try {
            if ($add) {
                $newletter->setData($data['email'])->save();
            }

            if ($remove) {
                #$newletter->loadByEmail($data['email'])->unsubscribe();
            }
        } catch (Exception $e) {

        }

    }

    protected function _object2array($data)
    {
        if (!is_object($data) && !is_array($data)) {
            return $data;
        }

        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        return array_map(array($this, '_object2array'), $data);
    }

}
