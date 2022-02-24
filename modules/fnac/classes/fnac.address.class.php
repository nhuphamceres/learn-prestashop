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
 * ...........................................................................
 *
 * @author    Alexandre D. & Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  contact@common-services.com
 */

class FNAC_Address extends Address
{

    public $iso3 = array(
        'AFG' => 'AF',
        'ALA' => 'AX',
        'ALB' => 'AL',
        'DZA' => 'DZ',
        'ASM' => 'AS',
        'AND' => 'AD',
        'AGO' => 'AO',
        'AIA' => 'AI',
        'ATA' => 'AQ',
        'ATG' => 'AG',
        'ARG' => 'AR',
        'ARM' => 'AM',
        'ABW' => 'AW',
        'AUS' => 'AU',
        'AUT' => 'AT',
        'AZE' => 'AZ',
        'BHS' => 'BS',
        'BHR' => 'BH',
        'BGD' => 'BD',
        'BRB' => 'BB',
        'BLR' => 'BY',
        'BEL' => 'BE',
        'BLZ' => 'BZ',
        'BEN' => 'BJ',
        'BMU' => 'BM',
        'BTN' => 'BT',
        'BOL' => 'BO',
        'BES' => 'BQ',
        'BIH' => 'BA',
        'BWA' => 'BW',
        'BVT' => 'BV',
        'BRA' => 'BR',
        'IOT' => 'IO',
        'VGB' => 'VG',
        'BRN' => 'BN',
        'BGR' => 'BG',
        'BFA' => 'BF',
        'BDI' => 'BI',
        'KHM' => 'KH',
        'CMR' => 'CM',
        'CAN' => 'CA',
        'CPV' => 'CV',
        'CYM' => 'KY',
        'CAF' => 'CF',
        'TCD' => 'TD',
        'CHL' => 'CL',
        'CHN' => 'CN',
        'CXR' => 'CX',
        'CCK' => 'CC',
        'COL' => 'CO',
        'COM' => 'KM',
        'COG' => 'CG',
        'COD' => 'CD',
        'COK' => 'CK',
        'CRI' => 'CR',
        'CIV' => 'CI',
        'HRV' => 'HR',
        'CUB' => 'CU',
        'CUW' => 'CW',
        'CYP' => 'CY',
        'CZE' => 'CZ',
        'DNK' => 'DK',
        'DJI' => 'DJ',
        'DMA' => 'DM',
        'DOM' => 'DO',
        'TLS' => 'TL',
        'ECU' => 'EC',
        'EGY' => 'EG',
        'SLV' => 'SV',
        'GNQ' => 'GQ',
        'ERI' => 'ER',
        'EST' => 'EE',
        'ETH' => 'ET',
        'FLK' => 'FK',
        'FRO' => 'FO',
        'FJI' => 'FJ',
        'FIN' => 'FI',
        'FRA' => 'FR',
        'GUF' => 'GF',
        'PYF' => 'PF',
        'ATF' => 'TF',
        'GAB' => 'GA',
        'GMB' => 'GM',
        'GEO' => 'GE',
        'DEU' => 'DE',
        'GHA' => 'GH',
        'GIB' => 'GI',
        'GRC' => 'GR',
        'GRL' => 'GL',
        'GRD' => 'GD',
        'GLP' => 'GP',
        'GUM' => 'GU',
        'GTM' => 'GT',
        'GGY' => 'GG',
        'GIN' => 'GN',
        'GNB' => 'GW',
        'GUY' => 'GY',
        'HTI' => 'HT',
        'HMD' => 'HM',
        'HND' => 'HN',
        'HKG' => 'HK',
        'HUN' => 'HU',
        'ISL' => 'IS',
        'IND' => 'IN',
        'IDN' => 'ID',
        'IRN' => 'IR',
        'IRQ' => 'IQ',
        'IRL' => 'IE',
        'IMN' => 'IM',
        'ISR' => 'IL',
        'ITA' => 'IT',
        'JAM' => 'JM',
        'JPN' => 'JP',
        'JEY' => 'JE',
        'JOR' => 'JO',
        'KAZ' => 'KZ',
        'KEN' => 'KE',
        'KIR' => 'KI',
        'XKX' => 'XK',
        'KWT' => 'KW',
        'KGZ' => 'KG',
        'LAO' => 'LA',
        'LVA' => 'LV',
        'LBN' => 'LB',
        'LSO' => 'LS',
        'LBR' => 'LR',
        'LBY' => 'LY',
        'LIE' => 'LI',
        'LTU' => 'LT',
        'LUX' => 'LU',
        'MAC' => 'MO',
        'MKD' => 'MK',
        'MDG' => 'MG',
        'MWI' => 'MW',
        'MYS' => 'MY',
        'MDV' => 'MV',
        'MLI' => 'ML',
        'MLT' => 'MT',
        'MHL' => 'MH',
        'MTQ' => 'MQ',
        'MRT' => 'MR',
        'MUS' => 'MU',
        'MYT' => 'YT',
        'MEX' => 'MX',
        'FSM' => 'FM',
        'MDA' => 'MD',
        'MCO' => 'MC',
        'MNG' => 'MN',
        'MNE' => 'ME',
        'MSR' => 'MS',
        'MAR' => 'MA',
        'MOZ' => 'MZ',
        'MMR' => 'MM',
        'NAM' => 'NA',
        'NRU' => 'NR',
        'NPL' => 'NP',
        'NLD' => 'NL',
        'ANT' => 'AN',
        'NCL' => 'NC',
        'NZL' => 'NZ',
        'NIC' => 'NI',
        'NER' => 'NE',
        'NGA' => 'NG',
        'NIU' => 'NU',
        'NFK' => 'NF',
        'PRK' => 'KP',
        'MNP' => 'MP',
        'NOR' => 'NO',
        'OMN' => 'OM',
        'PAK' => 'PK',
        'PLW' => 'PW',
        'PSE' => 'PS',
        'PAN' => 'PA',
        'PNG' => 'PG',
        'PRY' => 'PY',
        'PER' => 'PE',
        'PHL' => 'PH',
        'PCN' => 'PN',
        'POL' => 'PL',
        'PRT' => 'PT',
        'PRI' => 'PR',
        'QAT' => 'QA',
        'REU' => 'RE',
        'ROU' => 'RO',
        'RUS' => 'RU',
        'RWA' => 'RW',
        'ESH' => 'EH',
        'BLM' => 'BL',
        'SHN' => 'SH',
        'KNA' => 'KN',
        'LCA' => 'LC',
        'MAF' => 'MF',
        'SPM' => 'PM',
        'VCT' => 'VC',
        'WSM' => 'WS',
        'SMR' => 'SM',
        'STP' => 'ST',
        'SAU' => 'SA',
        'SEN' => 'SN',
        'SRB' => 'RS',
        'SYC' => 'SC',
        'SLE' => 'SL',
        'SGP' => 'SG',
        'SVK' => 'SK',
        'SVN' => 'SI',
        'SLB' => 'SB',
        'SOM' => 'SO',
        'ZAF' => 'ZA',
        'SGS' => 'GS',
        'KOR' => 'KR',
        'SSD' => 'SS',
        'ESP' => 'ES',
        'LKA' => 'LK',
        'SDN' => 'SD',
        'SUR' => 'SR',
        'SJM' => 'SJ',
        'SWZ' => 'SZ',
        'SWE' => 'SE',
        'CHE' => 'CH',
        'SYR' => 'SY',
        'TWN' => 'TW',
        'TJK' => 'TJ',
        'TZA' => 'TZ',
        'THA' => 'TH',
        'TGO' => 'TG',
        'TKL' => 'TK',
        'TON' => 'TO',
        'TTO' => 'TT',
        'TUN' => 'TN',
        'TUR' => 'TR',
        'TKM' => 'TM',
        'TCA' => 'TC',
        'TUV' => 'TV',
        'UGA' => 'UG',
        'UKR' => 'UA',
        'ARE' => 'AE',
        'GBR' => 'GB',
        'USA' => 'US',
        'UMI' => 'UM',
        'URY' => 'UY',
        'VIR' => 'VI',
        'UZB' => 'UZ',
        'VUT' => 'VU',
        'VAT' => 'VA',
        'VEN' => 'VE',
        'VNM' => 'VN',
        'WLF' => 'WF',
        'YEM' => 'YE',
        'ZMB' => 'ZM',
        'ZWE' => 'ZW',
        // SPAIN Exceptions
        'CEM' => 'ES',
        'BAL' => 'ES',
        'CNR' => 'ES'
    );

    /* Genere un cle qui sera le nom d'alias dans la table */
    public function hash($obj)
    {
        $str = $obj->firstname.$obj->lastname.$obj->company.$obj->address1.$obj->address2.$obj->address3.
            $obj->zipcode.$obj->city.$obj->country;

        if (isset($obj->phone)) {
            $str .= $obj->phone;
        }

        if (isset($obj->mobile)) {
            $str .= $obj->mobile;
        }

        if (isset($obj->nif)) {
            $str .= $obj->nif;
        }

        return (md5($str));
    }

    /**
     * Specify if an address is already in base
     *
     * @param $alias
     * @return id_address
     */
    public static function addressExistsByAlias($alias)
    {
        $row = Db::getInstance()->getRow('
             SELECT `id_address`
             FROM '._DB_PREFIX_.'address a
             WHERE a.`alias` = "'.pSQL($alias).'"');

        return ($row['id_address']);
    }

    private function _filter($text)
    {
        $text = preg_replace('/[\x5B-\x5F\x7B-\x7F]/', '', $text);
        $text = preg_replace('/[!<>?=+@{}_$%]*$/u', '', $text); // remove non printable
        return $text;
    }

    public static function cleanLogin($text)
    {
        $text = mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8');
        $text = preg_replace(array('/&szlig;/', '/&(..)lig;/', '/&([aoueAOUE])uml;/', '/&(.)[^;]*;/'), array('ss', '$1', '$1e', '$1'), $text);
        $text = preg_replace('/[!<>?=+@{}_$%]*$/u', '', $text); // remove non printable

        // Remove numbers
        $text = preg_replace('/[0-9]/', ' ', $text);

        return Tools::substr(trim($text), 0, 32);
    }

    public function lookupOrCreateFnacAddress($fnacAddress)
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            require_once(dirname(__FILE__).'/../backward_compatibility/backward.php');
        }

        // Cle MD qui est l'alias dans la DB
        $alias = $this->hash($fnacAddress);

        // On recherche si le client existe deja, sinon on cree son entree dans le carnet d'adresse
        if (!$id_address = $this->addressExistsByAlias($alias)) {
            $address1 = $this->_filter((string)$fnacAddress->address1);
            $address2 = $this->_filter((string)$fnacAddress->address2);

            // ISO3 to ISO2
            if (isset($this->iso3[Tools::strtoupper($fnacAddress->country)]) && $this->iso3[Tools::strtoupper($fnacAddress->country)]) {
                $fnacAddress->country = $this->iso3[Tools::strtoupper($fnacAddress->country)];
            }

            // L'adresse n'existe pas on la cree
            $this->id_country = Country::getByIso((string)Tools::substr($fnacAddress->country, 0, 2));
            $this->country = Country::getNameById(Context::getContext()->cookie->id_lang, $this->id_country);
            $this->alias = $alias;
            $this->company = Tools::substr($this->_filter((string)$fnacAddress->company), 0, 64);
            $this->lastname = Tools::substr($this->_filter((string)$fnacAddress->lastname), 0, 32);
            $this->firstname = Tools::substr($this->_filter((string)$fnacAddress->firstname), 0, 32);

            $this->dni = '123456789';
            if (isset($fnacAddress->nif) && (string)$fnacAddress->nif) {
                $this->dni = (string)$fnacAddress->nif;
            }

            if (isset($fnacAddress->phone)) {
                $this->phone = $this->_filter((string)$fnacAddress->phone);
            }
            if (isset($fnacAddress->mobile)) {
                $this->phone_mobile = $this->_filter((string)$fnacAddress->mobile);
            }

            if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                $this->phone = $this->phone_mobile ? $this->phone_mobile : $this->phone;
            }

            $this->address1 = $this->_filter((string)$address1);
            $this->address2 = $this->_filter((string)$address2);
            $this->postcode = (string)$fnacAddress->zipcode;

            if (Tools::strtoupper(Tools::substr($fnacAddress->country, 0, 2)) == 'PT') {
                $this->postcode = str_replace('-', ' ', $this->postcode);
            }

            $this->city = $this->_filter((string)$fnacAddress->city);

            if (empty($this->address1)) {
                $this->address1 = 'Unkwnown';
            }

            //  fields sizes must match with parent Address class
            foreach (array('company', 'firstname', 'lastname', 'address1', 'address2', 'postcode', 'city') as $field) {
                $this->{$field} = Tools::substr($this->{$field}, 0, $this->fieldsSize[$field]);
            }

            if ($this->add()) {
                return ($this->id);
            }
            return (false);
        } else {
            return ($id_address);
        }
    }
}
