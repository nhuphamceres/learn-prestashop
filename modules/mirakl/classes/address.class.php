<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Common-Services Co., Ltd.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 *
 * @author    Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 *  Support by mail  :  support.mirakl@common-services.com
 */

if (!class_exists('MiraklAddress')) {
    class MiraklAddress extends Address
    {
        public $validation
            = array(
                'company' => '/[<>=#{}]*/',
                'firstname' => '/[0-9!<>,;?=+()@#"°{}_$%:]*/',
                'lastname' => '/[0-9!<>,;?=+()@#"°{}_$%:]*/',
                'address1' => '/[!<>?=+@{}_$%]*/',
                'address2' => '/[!<>?=+@{}_$%]*/',
                'postcode' => '/[^a-zA-Z 0-9-]/',
                'city' => '/[!<>;?=+@#"°{}_$%]*/',
                'phone' => '/[+0-9. ()-]*/',
                'phone_mobile' => '/[+0-9. ()-]*/'
            );

        protected static $countryCodeMapping = array(
            'ESP' => 'ES',
            'FRA' => 'FR',
            'PRT' => 'PT',  // Portugal
        );

        /**
         * @param array $pm_address
         * @param null|string $customer_vat_id
         * @return string
         */
        public function hash($pm_address, $customer_vat_id = null)
        {
            $firstname = $pm_address['firstname'];
            // Genere un cle qui sera le nom d'alias dans la table
            $str = (string)$pm_address['lastname'] .
                (is_array($firstname) ? reset($firstname) : (string)$firstname) . // billing firstname could be an array
                (string)$pm_address['street_1'].(string)$pm_address['zip_code'].(string)$pm_address['city'];

            if ($customer_vat_id) {
                $str .= $customer_vat_id;
            }

            return md5($str);
        }

        public static function addressExistsByAlias($alias)
        {
            /**
             * Specify if an address is already in base
             *
             * @param $alias
             *
             * @return id_address
             */
            $row = Db::getInstance()->getRow(
                '
             SELECT `id_address`
             FROM '._DB_PREFIX_.'address a
             WHERE a.`alias` = "'.pSQL((string)$alias).'"'
            );

            return $row['id_address'];
        }

        public static function filter($text)
        {
            $text = htmlentities($text, ENT_NOQUOTES, 'UTF-8');
            $text = preg_replace(
                array(
                    '/&szlig;/', '/&(..)lig;/',
                    '/&([aouAOU])uml;/', '/&(.)[^;]*;/'),
                array(
                    'ss',
                    '$1',
                    '$1e',
                    '$1'
                ),
                $text
            );
            $text = str_replace('_', '/', $text);
            $text = preg_replace('/[\x00-\x1F\x21-\x2C\x2F\x3A-\x3F\x5B-\x60\x7B-\x7F]/', '', $text); // remove non printable

            return $text;
        }

        public static function cleanLogin($text)
        {
//            return Tools::substr($text, 0, 32); // <------ add this here
            $text = is_array($text) ? reset($text) : $text; // billing firstname could be an array
            $text = mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8');
            $text = preg_replace(
                array(
                    '/&szlig;/',
                    '/&(..)lig;/',
                    '/&([aoueAOUE])uml;/',
                    '/&(.)[^;]*;/',
                    '/[\^,.]/',
                ),
                array(
                    'ss',
                    '$1',
                    '$1e',
                    '$1',
                    ''
                ),
                $text
            );
            $text = trim(preg_replace('/[#:;|()!<>?=+@{}_$%0-9]*/u', '', $text)); // remove non printable

            return Tools::substr($text, 0, 32);
        }

        /**
         * @param array $me_address
         * @param int $id_lang
         * @param null|string $customer_vat_id
         * @return false|int|mixed
         */
        public function lookupOrCreateAddress($me_address, $id_lang, $customer_vat_id = null)
        {
            $alias = $this->hash($me_address, $customer_vat_id);
            $id_address = $this->addressExistsByAlias($alias);
            if ($id_address) {
                return $id_address;
            }

            $this->id_country = $this->guessCountry($me_address['country'], $me_address['country_iso_code']);
            $this->country = Country::getNameById($id_lang, $this->id_country);
            $this->alias = $alias;

            $this->lastname = !empty($me_address['lastname']) ? MiraklTools::ucfirst(self::cleanLogin($me_address['lastname'])) : 'unknown';

            if (!Validate::isName($this->lastname)) {
                $this->lastname = null;
            }

            $this->firstname = !empty($me_address['firstname']) ? MiraklTools::ucfirst(self::cleanLogin($me_address['firstname'])) : 'unknown';

            if (!Validate::isName($this->firstname)) {
                $this->firstname = null;
            }

            $this->company = !empty($me_address['company']) ? MiraklTools::ucfirst((string)$me_address['company']) : '';

            if (!Validate::isName($this->company)) {
                $this->company = null;
            }

            $this->address1 = (isset($me_address['street_1']) && !empty($me_address['street_1'])) ? (string)$this->filter($me_address['street_1']) : null;

            if (!Validate::isAddress($this->address1)) {
                $this->address1 = null;
            }

            if (is_array($me_address['street_2'])) {
                $me_address['street_2'] = reset($me_address['street_2']);
            }

            $this->address2 = (isset($me_address['street_2']) && !empty($me_address['street_2'])) ? (string)$this->filter($me_address['street_2']) : null;

            if (!Validate::isAddress($this->address2)) {
                $this->address2 = null;
            }
            $this->postcode = !empty($me_address['zip_code']) ? (string)$me_address['zip_code'] : 'unknown';

            if (!Validate::isPostCode($this->postcode)) {
                $this->postcode = null;
            }

            $this->city = (isset($me_address['city']) && !empty($me_address['city'])) ? $this->filter((string)ucwords(Tools::strtolower($me_address['city']))) : '-';

            if (!Validate::isCityName($this->city)) {
                $this->city = 'Unknown';
            }

            if (array_key_exists('phone', $me_address) && !is_array($me_address['phone']) && !empty($me_address['phone'])) {
                $this->phone = $this->extractPhoneNumberFromAddress($me_address['phone']);
            }

            // Only get from phone_secondary if failed in previous case
            if (!$this->phone && array_key_exists('phone_secondary', $me_address) && !is_array($me_address['phone_secondary']) && !empty($me_address['phone_secondary'])) {
                $this->phone = $this->extractPhoneNumberFromAddress($me_address['phone_secondary']);
            }

            if (!Validate::isPhoneNumber($this->phone)) {
                $this->phone = null;
            }

            if (!Tools::strlen($this->address1) && Tools::strlen($this->address2)) {
                $this->address1 = $this->address2;
                $this->address2 = null;
            }

            $this->date_add = date('Y-m-d H:i:s');// just to pass field validation
            $this->date_upd = date('Y-m-d H:i:s');// just to pass field validation

            if ($customer_vat_id) {
                $this->dni = $customer_vat_id;
                $this->vat_number = $customer_vat_id;
            }

            // Deal with DNI
            if (!$this->dni && $this->needIdentificationNumber()) {
                $this->dni = '99999999-R';
            }

            // fields sizes must match with parent Address class
            foreach (array(
                         'company',
                         'firstname',
                         'lastname',
                         'address1',
                         'address2',
                         'postcode',
                         'city',
                         'phone',
                         'phone_mobile'
                     ) as $field) {
                $this->{$field} = trim($this->{$field});
                $this->{$field} = Tools::substr($this->{$field}, 0, $this->fieldsSize[$field]);
            }

            $validation = $this->validateFields(false, true);
            if ($validation !== true) {
                echo "Address - Validate Fields Failed :".$validation."\n";
                return false;
            }

            $this->add();

            return $this->id;
        }

        public function pickupPointIdAddressByAlias($collectionpointaddress)
        {
            $alias = $this->hash($collectionpointaddress);
            $id_address = self::addressExistsByAlias($alias);

            return $id_address;
        }

        /**
         * Conforama has 19-digit phone format +33 (0) 686 765 073
         * @param $originalPhone
         * @return false|string
         */
        protected function extractPhoneNumberFromAddress($originalPhone)
        {
            $phoneNumber = preg_replace('/[^+0-9. ()\/-]/', '', $originalPhone);
            return Tools::substr($phoneNumber, 0, 19);
        }

        protected function needIdentificationNumber()
        {
            return (bool)Db::getInstance()->getValue('
                SELECT c.`need_identification_number`
                FROM `' . _DB_PREFIX_ . 'country` c
                WHERE c.`id_country` = ' . (int)$this->id_country);
        }

        protected function guessCountry($countryName, $isoCode3)
        {
            $countryName = trim($countryName);
            $isoCode3 = Tools::strtoupper($isoCode3);

            if ($isoCode3 && !empty($isoCode3)) {
                if (isset(self::$countryCodeMapping[$isoCode3])) {
                    $countryCode = self::$countryCodeMapping[$isoCode3];
                } else {
                    $countryCode = Tools::substr($isoCode3, 0, 2);
                }
                $idCountry = Country::getByIso($countryCode);
            } elseif ($countryName && !empty($countryName)) {
                if (Validate::isLanguageIsoCode($countryName)) {
                    $idCountry = Country::getByIso($countryName);
                } else {
                    $idCountry = Country::getIdByName(null, $countryName);
                }
            } else {
                $idCountry = Country::getByIso('FR');
            }

            return (int)$idCountry;
        }
    }
}
