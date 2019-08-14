<?php

class Licentia_Fidelitas_Model_Source_Domains
{

    public function toOptionArray()
    {
        $return = array();

        $url = 'https://www51.e-goi.com/api/public/mail/domains';

        $data = array(
            "apikey" => Mage::getStoreConfig('fidelitas/config/api_key'),
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

        if ($output) {
            $result = Zend_Json::decode($output);

            foreach ($result as $item) {

                $return[] = array('value' => $item['domain'], 'label' => $item['domain']);
            }
        }

        return $return;
    }

}
