<?php

class Licentia_Fidelitas_Model_Subscribers extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('fidelitas/subscribers');
    }

    function cron()
    {

        Mage::log('started', null, 'fidelitas-sync-subs.log', true);

        $subscribers = $this->getCollection()
            ->addFieldToFilter('cellphone', '')
            ->addFieldToFilter('email', '');

        foreach ($subscribers as $subscriber) {
            $subscriber->delete();
        }

        $egoi = Mage::getModel('fidelitas/egoi');
        $list = Mage::getModel('fidelitas/lists')->getList();

        $limit = 1000;
        $end = false;
        $start = 0;
        do {
            $egoi->addData(array('listID' => $list->getListnum(), 'subscriber' => 'all_subscribers', 'limit' => $limit))
                ->setData('start', $start);

            try {
                $subscribers = $egoi->getSubscriberData()->getData();
            } catch (Exception $e) {
                Mage::logException($e);
            }

            if (isset($subscribers[0]['ERROR'])) {
                break;
            }

            if (count($subscribers[0]['subscriber']) < $limit) {
                $end = true;
            }

            foreach ($subscribers[0]['subscriber'] as $subscriber) {

                $subscriberData = array_change_key_case($subscriber, CASE_LOWER);

                if (!filter_var($subscriberData, FILTER_VALIDATE_EMAIL) && strlen($subscriberData['cellphone']) < 4) {
                    continue;
                }

                $jaExiste = $this->load($subscriberData['email'], 'email');
                if ($jaExiste->getId()) {
                    $subscriberData['subscriber_id'] = $jaExiste->getId();
                }

                if (strlen($subscriberData['birth_date']) > 0) {
                    $subscriberData['dob'] = $subscriberData['birth_date'];
                }

                try {
                    Mage::getModel('fidelitas/subscribers')
                        ->setData($subscriberData)
                        ->setData('inCron', true)
                        ->save();
                } catch (Exception $e) {

                }
            }

            $start += $limit;
        } while ($end === false);

        Mage::log('end', null, 'fidelitas-sync-subs.log', true);

        #$this->importCoreNewsletterSubscribers();

    }

    public function findCustomer($value, $attribute = 'entity_id', $billing = null)
    {

        $cellphoneField = Mage::getStoreConfig('fidelitas/config/cellphone');
        $customers = Mage::getModel('customer/customer')
            ->getCollection()
            ->addAttributeToSelect('firstname')
            ->addAttributeToSelect('lastname')
            ->addAttributeToSelect('store_id')
            ->addAttributeToSelect('dob')
            ->addAttributeToFilter($attribute, $value)
            ->joinAttribute('country_id', 'customer_address/country_id', 'default_billing', null, 'left')
            ->joinAttribute($cellphoneField, 'customer_address/' . $cellphoneField, 'default_billing', null, 'left');

        if ($billing && $cellphoneField != $billing && $attribute != $billing && 'country_id' != $billing) {
            $customers->joinAttribute($billing, 'customer_address/' . $billing, 'default_billing', null, 'left');
        }

        if ($customers->count() == 1) {
            $customer = $customers->getFirstItem();
            if (strlen($customer->getData($cellphoneField)) > 5) {
                $customer->setData('cellphone', $this->getPrefixForCountry($customer->getCountryId()) . '-' . preg_replace('/\D/', '', $customer->getData($cellphoneField)));
            }

            return $customer;
        }

        return false;
    }

    public function importCoreNewsletterSubscribers()
    {

        $news = Mage::getModel('newsletter/subscriber')
            ->getCollection()
            ->addFieldToFilter('subscriber_status', 1);

        $list = Mage::getModel('fidelitas/lists')->getList();

        /** @var Mage_Newsletter_Model_Subscriber $subscriber */
        foreach ($news as $subscriber) {
            if (!$subscriber->getStoreId()) {
                continue;
            }

            $data = array();

            if ($this->subscriberExists('email', $subscriber->getEmail())) {
                continue;
            }

            $data['email'] = $subscriber->getEmail();
            $data['status'] = 1;
            $data['customer_id'] = $subscriber->getCustomerId();
            $data['list'] = $list->getListnum();

            try {
                Mage::getModel('fidelitas/subscribers')->setData($data)->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }


    public function subscriberExists($field, $value)
    {

        $model = Mage::getModel('fidelitas/subscribers')
            ->getCollection()
            ->addFieldToFilter($field, $value);

        if ($model->count() != 1) {
            return false;
        }

        return $model->getFirstItem();
    }

    public function save()
    {
        $data = $this->getData();

        if (!$this->getEmail() || !filter_var($this->getEmail(), FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $this->findCustomer($data['email'], 'email');

        if ($customer) {
            $data['customer_id'] = $customer->getId();
            $data['birth_date'] =  substr($customer->getData('dob'), 0, 10);
            $data['first_name'] = $customer->getData('firstname');
            $data['last_name'] = $customer->getData('lastname');

            if ($customer->getData('cellphone')) {
                $data['cellphone'] = $customer->getData('cellphone');
            }
        }

        $info = $this->subscriberExists('email', $this->getEmail());

        if ($info && isset($data['inCallback'])) {
            $this->setId($info->getId());
        }

        if ($this->getData('inCron') === true && !Mage::registry('fidelitas_first_run')) {
            return parent::save();
        }

        $model = Mage::getModel('fidelitas/egoi');

        if ($info) {
            $data['subscriber'] = $info->getUid();
            $this->setId($info->getId());
        } elseif ($info = $this->subscriberExists('subscriber_id', $this->getId())) {
            $data['subscriber'] = $info->getUid();
            $this->setId($info->getId());
        }

        $data['listID'] = $data['list'];

        $model->addData($data);
        $this->addData($data);

        try {
            if ($this->getId()) {
                if ($model->getData('uid')) {
                    $model->setData('subscriber', $model->getData('uid'));
                }
                if ($this->getData('status') == 0) {
                    $model->setData('status', 4);
                } else {
                    $model->setData('status', 1);
                }
                $model->editSubscriber();

            } else {
                $result = $model->setData('status', 1)->addSubscriber();

                if (isset($result['uid'])) {
                    $this->setData('uid', $result->getData('uid'));
                    if (!Mage::app()->getStore()->isAdmin()) {
                        Mage::getSingleton('core/cookie')->set('egoi-subscriber', $result['uid'], true);
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return parent::save();
    }


    public function delete()
    {
        $model = Mage::getModel('fidelitas/egoi');

        $data = array();
        $data['listID'] = $this->getList();
        $data['subscriber'] = $this->getUid();

        if (!$this->getData('inCron') && !Mage::registry('fidelitas_clean')) {
            $model->setData($data)->removeSubscriber();

            Mage::getModel('newsletter/subscriber')->loadByEmail($data['email'])->delete();
        }

        return parent::delete();
    }

    public function updateFromNewsletterCore($event)
    {
        /** @var Mage_Newsletter_Model_Subscriber $subscriber */
        $subscriber = $event->getDataObject();
        $email = $subscriber->getSubscriberEmail();
        $subscriber->setImportMode(true);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (!$subscriber->getIsStatusChanged() &&
            $subscriber->getOrigData('subscriber_status') == $subscriber->getData('subscriber_status')
        ) {
            return;
        }

        try {
            $list = Mage::getModel('fidelitas/lists')->getList();

            $listId = $list->getListnum();
            $data = array();
            $data['list'] = $listId;
            $data['email'] = $email;
            $data['status'] = $subscriber->getSubscriberStatus() == 3 ? 0 : 1;
            $data['store_id'] = $subscriber->getStoreId();

            return $this->addData($data)->save();

        } catch (Exception $e) {
            Mage::logException($e);
        }

    }

    public static function getPhonePrefixs()
    {
        $phones = self::phonePrefixsList();

        $return = array();
        $return[''] = Mage::helper('fidelitas')->__('-- Please Choose --');
        foreach ($phones as $value) {
            $return[$value[2]] = ucwords(strtolower($value[0])) . ' (+' . $value[2] . ')';
        }

        asort($return);

        return $return;
    }

    public function getPrefixForCountry($countryCode)
    {

        $phones = self::phonePrefixsList();
        foreach ($phones as $phone) {

            if ($phone[1] == $countryCode) {
                return $phone[2];
            }
        }

        return '';
    }

    public static function phonePrefixsList()
    {

        return array(array('CANADA', 'CA', '1'),
            array('PUERTO RICO', 'PR', '1'),
            array('UNITED STATES', 'US', '1'),
            array('ARMENIA', 'AM', '7'),
            array('KAZAKHSTAN', 'KZ', '7'),
            array('RUSSIAN FEDERATION', 'RU', '7'),
            array('EGYPT', 'EG', '20'),
            array('SOUTH AFRICA (Zuid Afrika)', 'ZA', '27'),
            array('GREECE', 'GR', '30'),
            array('NETHERLANDS', 'NL', '31'),
            array('BELGIUM', 'BE', '32'),
            array('FRANCE', 'FR', '33'),
            array('SPAIN (España)', 'ES', '34'),
            array('HUNGARY', 'HU', '36'),
            array('ITALY', 'IT', '39'),
            array('ROMANIA', 'RO', '40'),
            array('SWITZERLAND (Confederation of Helvetia)', 'CH', '41'),
            array('AUSTRIA', 'AT', '43'),
            array('GREAT BRITAIN (United Kingdom)', 'GB', '44'),
            array('UNITED KINGDOM', 'GB', '44'),
            array('DENMARK', 'DK', '45'),
            array('SWEDEN', 'SE', '46'),
            array('NORWAY', 'NO', '47'),
            array('POLAND', 'PL', '48'),
            array('GERMANY (Deutschland)', 'DE', '49'),
            array('PERU', 'PE', '51'),
            array('MEXICO', 'MX', '52'),
            array('CUBA', 'CU', '53'),
            array('ARGENTINA', 'AR', '54'),
            array('BRAZIL', 'BR', '55'),
            array('CHILE', 'CL', '56'),
            array('COLOMBIA', 'CO', '57'),
            array('VENEZUELA', 'VE', '58'),
            array('MALAYSIA', 'MY', '60'),
            array('AUSTRALIA', 'AU', '61'),
            array('INDONESIA', 'ID', '62'),
            array('PHILIPPINES', 'PH', '63'),
            array('NEW ZEALAND', 'NZ', '64'),
            array('SINGAPORE', 'SG', '65'),
            array('THAILAND', 'TH', '66'),
            array('JAPAN', 'JP', '81'),
            array('KOREA (Republic of [South] Korea)', 'KR', '82'),
            array('VIET NAM', 'VN', '84'),
            array('CHINA', 'CN', '86'),
            array('TURKEY', 'TR', '90'),
            array('INDIA', 'IN', '91'),
            array('PAKISTAN', 'PK', '92'),
            array('AFGHANISTAN', 'AF', '93'),
            array('SRI LANKA (formerly Ceylon)', 'LK', '94'),
            array('MYANMAR (formerly Burma)', 'MM', '95'),
            array('IRAN (Islamic Republic of Iran)', 'IR', '98'),
            array('MOROCCO', 'MA', '212'),
            array('ALGERIA (El Djazaïr)', 'DZ', '213'),
            array('TUNISIA', 'TN', '216'),
            array('LIBYA (Libyan Arab Jamahirya)', 'LY', '218'),
            array('GAMBIA, THE', 'GM', '220'),
            array('SENEGAL', 'SN', '221'),
            array('MAURITANIA', 'MR', '222'),
            array('MALI', 'ML', '223'),
            array('GUINEA', 'GN', '224'),
            array('CÔTE D\'IVOIRE (Ivory Coast)', 'CI', '225'),
            array('BURKINA FASO', 'BF', '226'),
            array('NIGER', 'NE', '227'),
            array('TOGO', 'TG', '228'),
            array('BENIN', 'BJ', '229'),
            array('MAURITIUS', 'MU', '230'),
            array('LIBERIA', 'LR', '231'),
            array('SIERRA LEONE', 'SL', '232'),
            array('GHANA', 'GH', '233'),
            array('NIGERIA', 'NG', '234'),
            array('CHAD (Tchad)', 'TD', '235'),
            array('CENTRAL AFRICAN REPUBLIC', 'CF', '236'),
            array('CAMEROON', 'CM', '237'),
            array('CAPE VERDE', 'CV', '238'),
            array('SAO TOME AND PRINCIPE', 'ST', '239'),
            array('EQUATORIAL GUINEA', 'GQ', '240'),
            array('GABON', 'GA', '241'),
            array('CONGO, REPUBLIC OF', 'CG', '242'),
            array('CONGO, THE DEMOCRATIC REPUBLIC OF THE (formerly Zaire)', 'CD', '243'),
            array('ANGOLA', 'AO', '244'),
            array('GUINEA-BISSAU', 'GW', '245'),
            array('ASCENSION ISLAND', '', '247'),
            array('SEYCHELLES', 'SC', '248'),
            array('SUDAN', 'SD', '249'),
            array('RWANDA', 'RW', '250'),
            array('ETHIOPIA', 'ET', '251'),
            array('SOMALIA', 'SO', '252'),
            array('DJIBOUTI', 'DJ', '253'),
            array('KENYA', 'KE', '254'),
            array('TANZANIA', 'TZ', '255'),
            array('UGANDA', 'UG', '256'),
            array('BURUNDI', 'BI', '257'),
            array('MOZAMBIQUE (Moçambique)', 'MZ', '258'),
            array('ZAMBIA (formerly Northern Rhodesia)', 'ZM', '260'),
            array('MADAGASCAR', 'MG', '261'),
            array('RÉUNION', 'RE', '262'),
            array('ZIMBABWE', 'ZW', '263'),
            array('NAMIBIA', 'NA', '264'),
            array('MALAWI', 'MW', '265'),
            array('LESOTHO', 'LS', '266'),
            array('BOTSWANA', 'BW', '267'),
            array('SWAZILAND', 'SZ', '268'),
            array('COMOROS', 'KM', '269'),
            array('MAYOTTE', 'YT', '269'),
            array('SAINT HELENA', 'SH', '290'),
            array('ERITREA', 'ER', '291'),
            array('ARUBA', 'AW', '297'),
            array('FAEROE ISLANDS', 'FO', '298'),
            array('GREENLAND', 'GL', '299'),
            array('GIBRALTAR', 'GI', '350'),
            array('PORTUGAL', 'PT', '351'),
            array('LUXEMBOURG', 'LU', '352'),
            array('IRELAND', 'IE', '353'),
            array('ICELAND', 'IS', '354'),
            array('ALBANIA', 'AL', '355'),
            array('MALTA', 'MT', '356'),
            array('CYPRUS', 'CY', '357'),
            array('FINLAND', 'FI', '358'),
            array('BULGARIA', 'BG', '359'),
            array('LITHUANIA', 'LT', '370'),
            array('LATVIA', 'LV', '371'),
            array('ESTONIA', 'EE', '372'),
            array('MOLDOVA', 'MD', '373'),
            array('BELARUS', 'BY', '375'),
            array('ANDORRA', 'AD', '376'),
            array('MONACO', 'MC', '377'),
            array('SAN MARINO (Republic of)', 'SM', '378'),
            array('VATICAN CITY (Holy See)', 'VA', '379'),
            array('UKRAINE', 'UA', '380'),
            array('SERBIA (Republic of Serbia)', 'RS', '381'),
            array('MONTENEGRO', 'ME', '382'),
            array('CROATIA (Hrvatska)', 'HR', '385'),
            array('SLOVENIA', 'SI', '386'),
            array('BOSNIA AND HERZEGOVINA', 'BA', '387'),
            array('MACEDONIA (Former Yugoslav Republic of Macedonia)', 'MK', '389'),
            array('CZECH REPUBLIC', 'CZ', '420'),
            array('SLOVAKIA (Slovak Republic)', 'SK', '421'),
            array('LIECHTENSTEIN (Fürstentum Liechtenstein)', 'LI', '423'),
            array('FALKLAND ISLANDS (MALVINAS)', 'FK', '500'),
            array('BELIZE', 'BZ', '501'),
            array('GUATEMALA', 'GT', '502'),
            array('EL SALVADOR', 'SV', '503'),
            array('HONDURAS', 'HN', '504'),
            array('NICARAGUA', 'NI', '505'),
            array('COSTA RICA', 'CR', '506'),
            array('PANAMA', 'PA', '507'),
            array('SAINT PIERRE AND MIQUELON', 'PM', '508'),
            array('HAITI', 'HT', '509'),
            array('GUADELOUPE', 'GP', '590'),
            array('BOLIVIA', 'BO', '591'),
            array('GUYANA', 'GY', '592'),
            array('ECUADOR', 'EC', '593'),
            array('FRENCH GUIANA', 'GF', '594'),
            array('PARAGUAY', 'PY', '595'),
            array('MARTINIQUE', 'MQ', '596'),
            array('SURINAME', 'SR', '597'),
            array('URUGUAY', 'UY', '598'),
            array('BONAIRE, ST. EUSTATIUS, AND SABA', 'BQ', '599'),
            array('CURAÃ‡AO', 'CW', '599'),
            array('NETHERLANDS ANTILLES (obsolete)', 'AN', '599'),
            array('SINT MAARTEN', 'SX', '599'),
            array('TIMOR-LESTE (formerly East Timor)', 'TL', '670'),
            array('BRUNEI DARUSSALAM', 'BN', '673'),
            array('NAURU', 'NR', '674'),
            array('PAPUA NEW GUINEA', 'PG', '675'),
            array('TONGA', 'TO', '676'),
            array('SOLOMON ISLANDS', 'SB', '677'),
            array('VANUATU', 'VU', '678'),
            array('FIJI', 'FJ', '679'),
            array('PALAU', 'PW', '680'),
            array('WALLIS AND FUTUNA', 'WF', '681'),
            array('COOK ISLANDS', 'CK', '682'),
            array('NIUE', 'NU', '683'),
            array('SAMOA (formerly Western Samoa)', 'WS', '685'),
            array('KIRIBATI', 'KI', '686'),
            array('NEW CALEDONIA', 'NC', '687'),
            array('TUVALU', 'TV', '688'),
            array('FRENCH POLYNESIA', 'PF', '689'),
            array('TOKELAU', 'TK', '690'),
            array('MICRONESIA (Federated States of Micronesia)', 'FM', '691'),
            array('MARSHALL ISLANDS', 'MH', '692'),
            array('KOREA (Democratic Peoples Republic of [North] Korea)', 'KP', '850'),
            array('HONG KONG (Special Administrative Region of China)', 'HK', '852'),
            array('MACAO (Special Administrative Region of China)', 'MO', '853'),
            array('CAMBODIA', 'KH', '855'),
            array('LAO PEOPLE\'S DEMOCRATIC REPUBLIC', 'LA', '856'),
            array('BANGLADESH', 'BD', '880'),
            array('TAIWAN (Chinese Taipei for IOC)', 'TW', '886'),
            array('MALDIVES', 'MV', '960'),
            array('LEBANON', 'LB', '961'),
            array('JORDAN (Hashemite Kingdom of Jordan)', 'JO', '962'),
            array('SYRIAN ARAB REPUBLIC', 'SY', '963'),
            array('IRAQ', 'IQ', '964'),
            array('KUWAIT', 'KW', '965'),
            array('SAUDI ARABIA (Kingdom of Saudi Arabia)', 'SA', '966'),
            array('YEMEN (Yemen Arab Republic)', 'YE', '967'),
            array('OMAN', 'OM', '968'),
            array('PALESTINIAN TERRITORIES', 'PS', '970'),
            array('UNITED ARAB EMIRATES', 'AE', '971'),
            array('ISRAEL', 'IL', '972'),
            array('BAHRAIN', 'BH', '973'),
            array('QATAR', 'QA', '974'),
            array('BHUTAN', 'BT', '975'),
            array('MONGOLIA', 'MN', '976'),
            array('NEPAL', 'NP', '977'),
            array('TAJIKISTAN', 'TJ', '992'),
            array('TURKMENISTAN', 'TM', '993'),
            array('AZERBAIJAN', 'AZ', '994'),
            array('KYRGYZSTAN', 'KG', '996'),
            array('UZBEKISTAN', 'UZ', '998'),
            array('BAHAMAS', 'BS', '1242'),
            array('BARBADOS', 'BB', '1246'),
            array('ANGUILLA', 'AI', '1264'),
            array('ANTIGUA AND BARBUDA', 'AG', '1268'),
            array('VIRGIN ISLANDS, BRITISH', 'VG', '1284'),
            array('VIRGIN ISLANDS, U.S.', 'VI', '1340'),
            array('CAYMAN ISLANDS', 'KY', '1345'),
            array('BERMUDA', 'BM', '1441'),
            array('GRENADA', 'GD', '1473'),
            array('TURKS AND CAICOS ISLANDS', 'TC', '1649'),
            array('MONTSERRAT', 'MS', '1664'),
            array('NORTHERN MARIANA ISLANDS', 'MP', '1670'),
            array('GUAM', 'GU', '1671'),
            array('AMERICAN SAMOA', 'AS', '1684'),
            array('SAINT LUCIA', 'LC', '1758'),
            array('DOMINICA', 'DM', '1767'),
            array('SAINT VINCENT AND THE GRENADINES', 'VC', '1784'),
            array('DOMINICAN REPUBLIC', 'DO', '1809'),
            array('TRINIDAD AND TOBAGO', 'TT', '1868'),
            array('SAINT KITTS AND NEVIS', 'KN', '1869'),
            array('JAMAICA', 'JM', '1-876'));
    }


    public function sync()
    {

        $core = Mage::getModel('newsletter/subscriber')
            ->getCollection()
            ->addFieldToFilter('subscriber_status', 1);

        /** @var Mage_Newsletter_Model_Subscriber $susbcriber */
        foreach ($core as $susbcriber) {

            /** @var Mage_Customer_Model_Customer $customer */
            $customer = Mage::getModel('customer/customer')->load($susbcriber->getCustomerId());
            $egoi = Mage::getModel('fidelitas/subscribers')->load($susbcriber->getEmail(), 'email');

            if ($customer->getId()) {
                $data['email'] = $customer->getEmail();
                $data['customer_id'] = $customer->getId();
                $data['birth_date'] =  substr($customer->getData('dob'), 0, 10);
                $data['first_name'] = $customer->getData('firstname');
                $data['last_name'] = $customer->getData('lastname');

                if ($customer->getData('cellphone')) {
                    $data['cellphone'] = $customer->getData('cellphone');
                }
            }

            $egoi->addData($data)->save();

        }
    }
}
