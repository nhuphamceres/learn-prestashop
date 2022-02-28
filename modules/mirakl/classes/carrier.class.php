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

if (!class_exists('MiraklCarrier')) {
    class MiraklCarrier extends Carrier
    {
        /**
         * @var array
         */
        private static $carrier_template = array(
            'name' => '',
            'id_tax' => 1,
            'id_tax_rules_group' => 1,
            'url' => null,
            'active' => false,
            'deleted' => 0,
            'shipping_handling' => false,
            'range_behavior' => 0,
            'is_module' => false,
            'id_zone' => 1,
            'shipping_external' => true,
            'external_module_name' => 'mirakl',
            'need_range' => true
        );

        public static function lookupOrCreateCarrier($carrier_name)
        {
            static $cached_carrier_ids;

            $context = Context::getContext();
            $id_lang = $context->language->id;

            if (defined('Carrier::ALL_CARRIERS')) {
                $all_carriers = Carrier::ALL_CARRIERS;
            } elseif (defined('ALL_CARRIERS')) {
                $all_carriers = ALL_CARRIERS;
            } else {
                $all_carriers = 5;
            }
            $mirakl_carrier_key = MiraklTools::toKey(self::filter($carrier_name));
            $carrier_found = null;

            if (!is_array($cached_carrier_ids) || !count($cached_carrier_ids)) {
                $cached_carrier_ids = Carrier::getCarriers($id_lang, false, false, false, null, $all_carriers);
            }

            if (is_array($cached_carrier_ids) && count($cached_carrier_ids)) {
                foreach ($cached_carrier_ids as $key => $carrier) {
                    // $carrier_key = MiraklTools::toKey(self::filter($carrier['name']));
                    $carrier_key = MiraklTools::toKey($carrier['name']);

                    if (Tools::strlen($carrier_key) && $carrier_key == $mirakl_carrier_key) {
                        $carrier_found = &$cached_carrier_ids[$key];
                        break;
                    }
                }
            }

            if ($carrier_found) {
                //use this carrier
                return((int)$carrier_found['id_carrier']);
            } else {
                // create carriers
                $carrier = new Carrier();

                foreach (self::$carrier_template as $k => $v) {
                    $carrier->{$k} = $v;
                }
                $carrier->name = self::filter($carrier_name);

                foreach (Language::getLanguages(false) as $language) {
                    $carrier->delay[$language['id_lang']] = $carrier->name.' via Mirakl';
                }

                if (!$carrier->add()) {
                    echo Tools::displayError('Unable to create carrier');
                    return (false);
                }

                $cached_carrier_ids[] = array(
                    'id_carrier' => $carrier->id,
                    'name' => $carrier->name
                );

                return((int)$carrier->id);
            }
        }

        public static function filter($text)
        {
            $text = htmlentities($text, ENT_NOQUOTES, 'UTF-8');
            $text = preg_replace(
                array(
                    '/&szlig;/',
                    '/&(..)lig;/',
                    '/&([aouAOU])uml;/',
                    '/&(.)[^;]*;/'
                ),
                array(
                    'ss',
                    '$1',
                    '$1e',
                    '$1'
                ),
                $text
            );
            $text = str_replace('_', '/', $text);
            $text = preg_replace('/[\x00-\x1F\x21-\x26\x3A-\x40\x5B-\x60\x7B-\x7F]/', '', $text); // remove non printable

            return $text;
        }
    }
}
