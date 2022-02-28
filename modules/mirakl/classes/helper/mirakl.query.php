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

if (!defined('_PS_VERSION_')) {
    exit;
}

if (!class_exists('MiraklHelperQuery')) {
    class MiraklHelperQuery
    {
        public static function pageMax($value = 10)
        {
            $value = (int)$value;
            if ($value > 100) {
                $value = 100;
            } elseif ($value < 1) {
                $value = 10;
            }

            return $value;
        }

        public static function pageOffset($value = 0)
        {
            $value = (int)$value;
            if ($value < 0) {
                $value = 0;
            }

            return $value;
        }

        public static function order($value = 'asc')
        {
            $valid_orders = array('asc', 'desc');
            $value = trim(Tools::strtolower($value));
            if (!in_array($value, $valid_orders)) {
                return $valid_orders[0];
            } else {
                return $value;
            }
        }

        public static function params($service = '', $key = '', $value = '')
        {
            $api = array();
            if ($key != 'sort') {
                $value = Tools::strtoupper($value);
            }

            //additional_fields
            $api['additional_fields']['entities'] = array('SHOP', 'OFFER', 'ORDER_LINE');

            //invoices
            $api['invoices']['sort'] = array('dateCreated');

            //messages
            $api['messages']['sort'] = array('dateCreated');
            $api['messages']['archived'] = array('FALSE', 'ALL', 'TRUE');
            $api['messages']['received'] = array('FALSE', 'ALL', 'TRUE');
            $api['messages']['visible'] = array('FALSE', 'ALL', 'TRUE');

            //offers
            $api['offers/imports']['import_mode'] = array('NORMAL', 'PARTIAL_UPDATE', 'REPLACE');
            $api['offers/messages']['archived'] = array('FALSE', 'ALL', 'TRUE');
            $api['offers/messages']['received'] = array('FALSE', 'ALL', 'TRUE');
            $api['offers/messages']['visible'] = array('FALSE', 'ALL', 'TRUE');
            $api['offers']['sort'] = array('totalPrice', 'price', 'productTitle');
            $api['offers/messages']['sort'] = array('dateCreated');
            $api['offers/states']['sort'] = array('sortIndex');

            //orders
            $api['orders']['sort'] = array('dateCreated');
            $api['orders/messages']['sort'] = array('dateCreated');
            $api['orders/messages']['archived'] = array('FALSE', 'TRUE', 'ALL');
            $api['orders/messages']['received'] = array('ALL', 'FALSE', 'TRUE');

            //products
            $api['products']['sort'] = array('productSku');
            $api['products/offers']['premium'] = array('ALL', 'FALSE', 'TRUE');
            $api['products/offers']['all_offers'] = array('FALSE', 'TRUE');
            $api['products/offers']['all_channels'] = array('FALSE', 'TRUE');
            $api['products/offers']['sort'] = array('bestPrice', 'bestEvaluation');

            //reasons
            $api['reasons/incident_open']['sort'] = array('sortIndex');
            $api['reasons/incident_close']['sort'] = array('sortIndex');
            $api['reasons/refund']['sort'] = array('sortIndex');
            $api['reasons/messaging']['sort'] = array('sortIndex');

            //shipping
            $api['shipping/carriers']['sort'] = array('sortIndex');

            if (isset($api[$service][$key])) {
                if (!in_array($value, $api[$service][$key])) {
                    return $api[$service][$key][0];
                } else {
                    return $value;
                }
            } else {
                return '';
            }
        }
    }
}
