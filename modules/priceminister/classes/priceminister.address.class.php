<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
 * Contact by Email :  support.priceminister@common-services.com
 */

require_once dirname(__FILE__).'/priceminister.tools.class.php';

class PriceMinisterAddress extends Address
{
    const ALIAS_PREFIX = 'PriceMinister';
    public static $validation = array(
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

    /* Use to manage shipping cost calculation */
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

                $locationAlias = 'RakutenFrance - '.Tools::strtoupper($region);

                if (($id_address = self::addressExistsByAlias($locationAlias, $id_customer))) {
                    $addressMap[$region] = $id_address;
                    continue;
                }
                $address = new Address();
                $address->alias = $locationAlias;
                $address->id_customer = $id_customer;
                $address->firstname = 'RakutenFrance';
                $address->lastname = 'Marketplace';
                $address->address1 = 'RakutenFrance shipping reference address';
                $address->address2 = 'Please do not remove';
                $address->postcode = $postcode;
                $address->phone = '0100000000';
                $address->phone_mobile = '0100000000';
                $address->other = 'This address is used by the RakutenFrance Marketplace Module, please do not edit or remove !';
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
            PriceMinisterConfiguration::updateValue(PriceMinister::CONFIG_PM_ADDRESS_MAP, $addressMap);
        }

        return ($pass);
    }

    /* Genere un cle qui sera le nom d'alias dans la table */

    /**
     * Specify if an address is already in base
     *
     * @param $alias
     * @return id_address
     */
    public static function addressExistsByAlias($alias, $id_customer = null)
    {
        if ($id_customer) {
            $customer_filter = ' AND a.`id_customer`='.(int)$id_customer;
        } else {
            $customer_filter = '';
        }

        $row = Db::getInstance()->getRow(
            'SELECT `id_address`
             FROM '._DB_PREFIX_.'address a
             WHERE a.`alias` = "'.pSQL($alias).'"'.$customer_filter.'
             AND `deleted` = 0'
        );

        return ((int)$row['id_address']);
    }

    public static function toEmailAddress($delivery_address)
    {
        if (!isset($delivery_address->firstname) || !isset($delivery_address->firstname)) {
            return (false);
        }

        $firstname = (string)$delivery_address->firstname;
        $lastname = (string)$delivery_address->lastname;

        $ret = array();

        foreach (array('firstname' => $firstname, 'lastname' => $lastname) as $key => $field) {
            $field = htmlentities($field, ENT_NOQUOTES, 'UTF-8'); // to HTML
            $field = preg_replace(array('/&szlig;/', '/&(..)lig;/', '/&([aouAOU])uml;/', '/&(.)[^;]*;/'), array('ss', '$1', '$1'.'e', '$1'), $field); // remove accents
            $field = preg_replace('/[!<>?=+@{}_$%]*$/u', '', $field);
            $field = preg_replace('/[^\p{L} \.\-]+/', '', $field);
            $field = str_replace(' ', '', $field);
            $ret[$key] = $field;
        }

        return ($ret);
    }

    public static function pickupPointIdAddressByAlias($id, $id_customer, $collectionpointaddress)
    {
        $alias = self::hash($collectionpointaddress);

        if (empty($id)) {
            die(Tools::displayError('FATAL: Alias is empty'));
        }
        if (!$id_customer) {
            die(Tools::displayError('FATAL: RakutenFrance customer is no longer available'));
        }

        $id_address = self::addressExistsByAlias($alias, $id_customer);

        return ($id_address);
    }

    public static function createAddressForPickupPoint($id, $id_customer, $collectionpointaddress)
    {
        if (empty($id)) {
            die(Tools::displayError('FATAL: Alias is empty'));
        }
        if (!$id_customer) {
            die(Tools::displayError('FATAL: RakutenFrance customer is no longer available'));
        }

        $alias = self::hash($collectionpointaddress);

        $full_label = sprintf('%s %s %s c/o %s', trim($collectionpointaddress->civility), self::_nfilter(trim($collectionpointaddress->lastname)), self::_nfilter(trim($collectionpointaddress->firstname)), self::_filter(trim($collectionpointaddress->name)));

        $address = new Address();
        $addressRules = Address::getValidationRules('Address'); /* Validation : Yes, it does exist ! */

        $country_code = Tools::strlen($collectionpointaddress->country) ?
            Tools::strtoupper(Tools::substr(trim($collectionpointaddress->country), 0, 2)) : 'FR';

		if ($country_code == 'FX') {
            $country_code = 'FR';
        }

		$address->id_country = (int)Country::getByIso($country_code);
		$address->country = Country::getNameById(Configuration::get('PS_LANG_DEFAULT'), $address->id_country);
		$address->id_customer = (int)$id_customer;

		// Make the pickup point name fitting into address1 & 2
		$result = explode('|', wordwrap($full_label, $addressRules['size']['firstname'], '|', true));
		$address->firstname = $result[0];

		if (isset($result[1])) {
            $remaining = explode('|', wordwrap($result[1], $addressRules['size']['lastname'], '|', true));
        } else {
            $remaining = null;
        }

		$address->alias = $alias;
		$address->lastname = (isset($remaining[0]) && !empty($remaining[0]) && $remaining[0]) ? $remaining[0] : '-';

		$address->company = isset($collectionpointaddress->company) ? self::_filter($collectionpointaddress->company) : null;
		$address->address1 = self::_filter($collectionpointaddress->address);
		$address->postcode = self::_filter($collectionpointaddress->zipcode);
		$address->city = self::_nfilter($collectionpointaddress->city);
		$address->other = sprintf('%s/%s', self::_filter($id), self::_filter($collectionpointaddress->contractlevel));

		// Safest way
		foreach (array('company', 'firstname', 'lastname', 'address1', 'address2', 'postcode', 'city', 'phone', 'phone_mobile') as $field) {
            $address->{$field} = preg_replace(self::$validation[$field], '', $address->{$field});

            if (isset($addressRules['required'][$field]) && $addressRules['required'][$field]) {
                if (empty($address->{$field})) {
                    $address->{$field} = '-';
                }
            }

            if (isset($addressRules['size'][$field]) && $addressRules['size'][$field]) {
                $address->{$field} = Tools::substr($address->{$field}, 0, $addressRules['size'][$field]);
            }
        }

		$address->active = 1;
		$address->deleted = 1;
		$address->add();

		if (!Validate::isLoadedObject($address)) {
            return (false);
        }

		return ((int)$address->id);
	}

    public function lookupOrCreateAddress($pmAddress)
    {
        $cookie = Context::getContext()->cookie;

        // Cle MD qui est l'alias dans la DB
        $alias = $this->hash($pmAddress);

        if ($pmAddress->countryalpha2 == 'FX') {
            $cc = 'FR';
        } else {
            $cc = $pmAddress->countryalpha2;
        }

        // !! DELIVERY
        // On recherche si le client existe deja, sinon on cree son entree dans le carnet d'adresse
        if (!($id_address = $this->addressExistsByAlias($alias))) {
            // L'adresse n'existe pas on la cree
            $this->id_country = Country::getByIso(Tools::strtoupper(trim($cc)));
            $this->country = Country::getNameById($cookie->id_lang, $this->id_country);
            $this->alias = $alias;

            $this->lastname = !empty($pmAddress->lastname) ? Tools::substr($this->_nfilter($pmAddress->lastname), 0, 32) : 'unknown';
            $this->firstname = !empty($pmAddress->firstname) ? Tools::substr($this->_nfilter($pmAddress->firstname), 0, 32) : 'unknown';

            $this->address1 = (string)$this->_filter($pmAddress->address1);
            $this->address2 = '';

            if (isset($pmAddress->address2) && is_array($pmAddress->address2)) {
                foreach ($pmAddress->address2 as $address2) {
                    $this->address2 .= (string)$this->_filter($address2);
                }
            }
            if (isset($pmAddress->address2) && !empty($pmAddress->address2)) {
                $this->address2 .= (string)$this->_filter($pmAddress->address2);
            }

            $this->postcode = (string)$pmAddress->zipcode;
            $this->city = $this->_nfilter((string)Tools::ucfirst($pmAddress->city));

            if (isset($pmAddress->phonenumber1) && Validate::isPhoneNumber($pmAddress->phonenumber1)) {
                $this->phone = $pmAddress->phonenumber1;
            } elseif (isset($pmAddress->phonenumber2) && Validate::isPhoneNumber($pmAddress->phonenumber2)) {
                $this->phone = $pmAddress->phonenumber2;
            }

            $this->phone = (string)$pmAddress->phonenumber1;
            $this->phone_mobile = (string)$pmAddress->phonenumber2;

            if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                $this->phone = $this->phone_mobile ? $this->phone_mobile : $this->phone;
            }

            //  fields sizes must match with parent Address class
            foreach (array('company', 'firstname', 'lastname', 'address1', 'address2', 'postcode', 'city', 'phone', 'phone_mobile') as $field) {
                $this->{$field} = Tools::substr($this->{$field}, 0, $this->fieldsSize[$field]);
            }

            $this->add();

            return ($this->id);
        } else {
            return ($id_address);
        }
    }

    public static function hash($obj, $small = false)
    {
        $str = $obj->lastname.$obj->firstname.(isset($obj->address1) ? $obj->address1 : $obj->address).$obj->zipcode.$obj->city.$obj->country;

        return ($small ? crc32($str) : md5($str));
    }

    private static function _nfilter($text)
    {
        $text = htmlentities($text, ENT_NOQUOTES, 'UTF-8');
        $text = preg_replace(array('/&szlig;/', '/&(..)lig;/', '/&([aouAOU])uml;/', '/&(.)[^;]*;/'), array('ss', '$1', '$1'.'e', '$1'), $text);
        $text = str_replace(str_split('/[]!<>?=+@(){}_$%*$'), ' ', $text);
        $text = preg_replace('/[^\p{L} \.\-]+/', '', $text);

        return $text;
    }

    private static function _filter($text)
    {
        $text = htmlentities($text, ENT_NOQUOTES, 'UTF-8');
        $text = preg_replace(array('/&szlig;/', '/&(..)lig;/', '/&([aouAOU])uml;/', '/&(.)[^;]*;/'), array('ss', '$1', '$1'.'e', '$1'), $text);
        $text = str_replace('_', '/', $text);
        $text = preg_replace('/[\x00-\x1F\x21-\x2E\x3A-\x3F\x5B-\x60\x7B-\x7F]/', '', $text); // remove non printable
        return $text;
    }
}
