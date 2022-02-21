<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Common-Services Co., Ltd. is strictly forbidden.
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
 * @copyright Copyright (c) 2011-2017 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   CDiscount
  * Support by mail:  support.cdiscount@common-services.com
 */

class CDiscountAddress extends Address
{
    const KEY = CDiscount::KEY;
    const NAME = CDiscount::NAME;

    public static function createShippingLocations($id_customer)
    {
        $addressMap = array();
        $marketplaces = array('fr');
        $pass = false;

        if (is_array($marketplaces)) {
            $pass = true;

            foreach ($marketplaces as $region) {
                $city = null;
                $state = null;
                $iso_code = null;
                $postcode = null;

                switch ($region) {
                    case 'fr': // France
                        $city = 'Paris';
                        $postcode = '75000';
                        break;
                }
                if (!$iso_code) {
                    $iso_code = Tools::strtoupper($region);
                }

                if (!$city) {
                    continue;
                }

                $locationAlias = self::NAME.' - '.Tools::strtoupper($region);

                if (($id_address = self::addressExistsByAlias($locationAlias, $id_customer))) {
                    $addressMap[$region] = $id_address;
                    continue;
                }
                $address = new Address();
                $address->alias = $locationAlias;
                $address->id_customer = $id_customer;
                $address->firstname = self::NAME;
                $address->lastname = 'Marketplace';
                $address->company = self::NAME;
                $address->address1 = self::NAME.' shipping reference address';
                $address->address2 = 'Please do not remove';
                $address->postcode = $postcode;
                $address->phone_mobile = '0600000099';
                $address->phone = '0100000099';
                $address->other = 'This address is used by the '.self::NAME.' Marketplace Module, please do not edit or remove !';
                $address->city = $city;
                $address->state = $state;
                $address->id_country = Country::getByIso($iso_code);
                $address->add();

                if (Validate::isLoadedObject($address)) {
                    $pass = true && $pass;
                } else {
                    $pass = false && $pass;
                }

                $addressMap[$region] = $address->id;
            }
            Configuration::updateValue(self::KEY.'_ADDRESS_MAP', self::encode(serialize($addressMap)));
        }

        return ($pass);
    }

    /**
     * Specify if an address is already in base
     *
     * @param $alias
     * @return id_address
     */
    public static function addressExistsByAlias($alias, $id_customer = null)
    {
        if ($id_customer) {
            $cdAddress_filter = ' AND a.`id_customer`='.(int)$id_customer;
        } else {
            $cdAddress_filter = '';
        }

        $row = Db::getInstance()->getRow('
                 SELECT `id_address`, `date_add`
                 FROM '._DB_PREFIX_.'address a
                 WHERE a.`alias` = "'.pSQL($alias).'"'.$cdAddress_filter);

        if (!$row) {
            return(false);
        } else {
            if (time() - strtotime($row['date_add']) < 60*60*24*30) {
                return ($row['id_address']);
            } else {
                return(false);
            }
        }
    }

    private static function encode($data)
    {
        return (CDiscountTools::encode($data));
    }

    public static function cleanLogin($text)
    {
        $text = mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8');
        $text = preg_replace(array('/&szlig;/', '/&(..)lig;/', '/&([aoueAOUE])uml;/', '/&(.)[^;]*;/'), array(
            'ss',
            '$1',
            '$1'.'e',
            '$1'
        ), $text);
        $text = preg_replace('/[!<>?=+@{}_$%]*$/u', '', $text); // remove non printable
        return $text;
    }

    public function lookupOrCreateAddress($cdAddress, $id_lang, $create = true)
    {
        $alias = $this->hash($cdAddress);

        if (!($id_address = $this->addressExistsByAlias($alias)) && $create) {
            $this->id_country = Country::getByIso(Tools::substr(Tools::strtoupper(trim($cdAddress['Country'])), 0, 2));

            if (!$this->id_country) {
                if (Cdiscount::$debug_mode) {
                    CommonTools::d($cdAddress);
                }
                return (false);
            }

            $this->country = Country::getNameById($id_lang, $this->id_country);
            $this->alias = $alias;

            if (isset($cdAddress['RelayId']) && is_string($cdAddress['RelayId'])) {
                $shippingPoint = $this->_filter(trim($cdAddress['ShippingPoint']));

                switch ($cdAddress['ShippingCode']) {
                    case 'REL':
                        $type = 'Mondial Relay';
                        break;
                    case 'SO1':
                        $type = 'So Colissimo';
                        break;
                    default:
                        $type = 'Point de Retrait';
                        break;
                }
                $this->company = sprintf('%s - %s', $type, $shippingPoint);
                $this->firstname = $this->_filter(Tools::strtoupper($cdAddress['FirstName']));
                $this->lastname = $this->_filter(Tools::strtoupper($cdAddress['LastName']));
            } else {
                if (isset($cdAddress['LastName']) && Tools::strlen($cdAddress['LastName'])) {
                    $this->lastname = $this->_filter(Tools::strtoupper($cdAddress['LastName']));
                } else {
                    if (isset($cdAddress['LastName']) && Tools::strlen($cdAddress['LastName'])) {
                        $this->lastname = $this->_filter(Tools::strtoupper($cdAddress['LastName']));
                    }
                }

                if (empty($this->lastname)) {
                    $this->lastname = 'Unknown';
                }

                $this->firstname = '';

                if (isset($cdAddress['FirstName']) && Tools::strlen($cdAddress['FirstName'])) {
                    $this->firstname .= $this->_filter(Tools::strtoupper($cdAddress['FirstName']));
                }
                /*
                if (isset($cdAddress['LastName']) && Tools::strlen($cdAddress['LastName'])) {
                    $this->firstname .= ' - '.$this->_filter(Tools::strtoupper($cdAddress['LastName']));
                }
                */

                if (empty($this->firstname)) {
                    $this->firstname = 'Unknown';
                }

                $this->company = !empty($cdAddress['CompanyName']) ? CommonTools::ucwords($cdAddress['CompanyName']) : '';
            }
            $street = isset($cdAddress['Street']) && is_string($cdAddress['Street']) ? (string)$cdAddress['Street'] : '';
            $city = isset($cdAddress['City']) && is_string($cdAddress['City']) ? (string)$cdAddress['City'] : '';
            $zipcode = isset($cdAddress['ZipCode']) && is_string($cdAddress['ZipCode']) ? (string)$cdAddress['ZipCode'] : '';

            $this->address1 = (string)$this->_filter($street);

            $address2 = null;

            foreach (array('Building', 'ApartmentNumber', 'PlaceName') as $item) {
                if (isset($cdAddress[$item]) && !is_array($cdAddress[$item]) && Tools::strlen((string)$cdAddress[$item])) {
                    $item_name = $this->_filter((string)$cdAddress[$item]);

                    if (Tools::strlen($item_name) <= 4) {
                        $address2 .= sprintf('%s: %s - ', $cdAddress['Labels'][$item], $item_name);
                    } else {
                        $address2 .= $item_name.' - ';
                    }
                }
            }
            
            $address2 = trim(rtrim($address2, ' - '));
            $addressRules = $this->getValidationRules('Address');
            $result = explode('|', wordwrap($address2, $addressRules['size']['address2'], '|', true));

            $this->address2 = rtrim($result[0], ' - ');

            if (Tools::strtoupper($this->address1) == Tools::strtoupper($this->address2)) {
                $this->address2 = null;
            }

            $this->other = '';
            if (isset($cdAddress['RelayId']) && is_string($cdAddress['RelayId'])) {
                $this->other = 'RELAY_ID_'.(string)sprintf('%06s', $cdAddress['RelayId']).' - ';
            }

            if (isset($cdAddress['Instructions']) && !is_array($cdAddress['Instructions'])) {
                $this->other .= (string)$cdAddress['Instructions'];
            }

            if (isset($result[1])) {
                $this->other .= ' - '.$result[1];
            }

            $this->other = rtrim($this->other, ' - ');

            $this->postcode = $zipcode;
            $this->city = $this->_filter(CommonTools::ucwords(Tools::strtolower($city)));

            if (!is_array($cdAddress['Phone']) && Tools::strlen($cdAddress['Phone'])) {
                $this->phone = $this->_filter((string)$cdAddress['Phone']);
            }

            if (!is_array($cdAddress['MobilePhone']) && Tools::strlen($cdAddress['MobilePhone'])) {
                $this->phone_mobile = $this->_filter((string)$cdAddress['MobilePhone']);

                if (!empty($this->phone_mobile) && Tools::strlen($this->phone) && $this->checkPhoneIsRequired()) {
                    $this->phone = $this->phone_mobile;
                }
            }

            if (empty($this->phone) && $this->checkPhoneIsRequired()) {
                $this->phone = '0600000099';
            }

            //  fields sizes must match with parent Address class
            //
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
                $this->{$field} = Tools::substr($this->{$field}, 0, $this->fieldsSize[$field]);
            }

            if (!$this->validateFields(false, false)) {
                if (Cdiscount::$debug_mode) {
                    CommonTools::d(get_object_vars($this));
                }
                return (false);
            }

            $this->add();

            return ($this->id);
        }

        return ($id_address);
    }

    public function checkPhoneIsRequired()
    {
        static $checkPhoneIsRequired = null;

        if ($checkPhoneIsRequired !== null) {
            return $checkPhoneIsRequired;
        }

        // Check if phone is mandatory
        //
        $addressCheck = new Address();

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $addressRequiredFields = $addressCheck->getfieldsRequiredDatabase();

            if (is_array($addressRequiredFields) && count($addressRequiredFields)) {
                foreach ($addressRequiredFields as $addressRequiredField) {
                    if (isset($addressRequiredField['field_name']) && ($addressRequiredField['field_name'] == 'phone_mobile' || $addressRequiredField['field_name'] == 'phone')) {
                        break;
                    }
                }
            }
        }

        $addressRules = $addressCheck->getValidationRules('Address');

        if (is_array($addressRules['required']) && in_array(array('phone_mobile', 'phone'), $addressRules['required'])) {
            $checkPhoneIsRequired = true;
            return(true);
        }
        return $checkPhoneIsRequired = false;
    }

    public function hash($source)
    {
        $str = null;

        foreach (array('CustomerId', 'LastName', 'FirstName', 'Street', 'ZipCode', 'City') as $item) {
            if (array_key_exists($item, $source) && is_string($source[$item])) {
                $str.= $source[$item];
            }
        }
        return (md5($str));
    }

    public static function _filter($text)
    {
        if (!self::isJapanese($text)) {
            $text = mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8');

            $searches = array('&szlig;', '&(..)lig;', '&([aouAOU])uml;', '&(.)[^;]*;');
            $replacements = array('ss', '\\1', '\\1'.'e', '\\1');

            foreach ($searches as $key => $search) {
                $text = mb_ereg_replace($search, $replacements[$key], $text);
            }
        }

        $text = str_replace('_', '/', $text);
        $text = mb_ereg_replace('[\x00-\x1F\x21-\x2E\x3A-\x3F\x5B-\x60\x7B-\x7F]', '', $text); // remove non printable
        $text = mb_ereg_replace('[!<>?=+@{}_$%]*$', '', $text);// remove chars rejected by Validate class

        return $text;
    }

    //http://stackoverflow.com/questions/2856942/how-to-check-if-the-word-is-japanese-or-english-using-php
    public static function isJapanese($word)
    {
        return preg_match('/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $word);
    }
}
