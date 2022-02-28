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

require_once(dirname(__FILE__).'/shop.class.php');

if (!class_exists('MiraklContext')) {
    class MiraklContext
    {
        const SHOP_CONTEXT  = 'shop_context';
        const ID_SHOP_GROUP = 'id_shop_group';
        const ID_SHOP       = 'id_shop';
        const ID_EMPLOYEE = 'id_employee';
        const ID_CURRENCY = 'id_currency';
        const ID_COUNTRY  = 'id_country';
        const ID_LANGUAGE = 'id_language';

        public static function restore(&$context, $shop = null, $debug = 0)
        {
            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                if (!Shop::isFeatureActive()) {
                    $context = Context::getContext();
                    if (!property_exists($context, 'controller') || !is_object($context->controller)) {
                        $context->controller = new FrontController();
                    }

                    return (true);
                }

                $stored_contexts = unserialize(MiraklTools::decode((Configuration::getGlobalValue(Mirakl::CONFIG_CONTEXT_DATA)))); // TODO: Validation: Configuration Requirement
                $context_key = $shop instanceof Shop ? self::getKey($shop) : Tools::getValue('context_key');

                if (!is_array($stored_contexts) || !count($stored_contexts) || !is_string($context_key)
                    || !isset($stored_contexts[$context_key]) || !$stored_contexts[$context_key] || !is_object($stored_contexts[$context_key])) {
                    if ($debug) {
                        printf('%s(#%d): Wrong context, please configure your module first', basename(__FILE__), __LINE__);
                    }

                    return false;
                }

                $id_employee = $stored_contexts[$context_key]->id_employee;
                $id_currency = $stored_contexts[$context_key]->id_currency;
                $id_country = $stored_contexts[$context_key]->id_country;
                $id_language = $stored_contexts[$context_key]->id_language;

                $context->controller = isset($stored_contexts[$context_key]->controller) && is_object($stored_contexts[$context_key]->controller) 
                    ? $stored_contexts[$context_key]->controller 
                    : new FrontController();
                $context->employee = new Employee($id_employee);
                $context->currency = new Currency($id_currency);
                $context->country = new Country($id_country);
                $context->language = new Language($id_language);

                
                $id_shop = (int)$stored_contexts[$context_key]->id_shop;
                if ((int)$id_shop && is_numeric($id_shop)) {
                    $context->shop = new Shop($id_shop);
                }

                MiraklShop::setShop($context->shop);
            }

            return true;
        }

        public static function save($context, $employee = null)
        {
            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                $stored_contexts = unserialize(MiraklTools::decode((Configuration::getGlobalValue(Mirakl::CONFIG_CONTEXT_DATA))));
                $mirakl_contexts = is_array($stored_contexts) && count($stored_contexts) ? $stored_contexts : array();

                // save the whole controller instance instead of its ID and name only
                // because PS 1.6s have problem creating AdminModulesController when restoring context
                // https://common-services-force.monday.com/boards/1971464818/pulses/1989896654
                $context_data = array(
                    'id_shop' => $context->shop->id,
                    'id_shop_group' => $context->shop->id_shop_group,
                    'id_currency' => $context->currency->id,
                    'id_country' => $context->country->id,
                    'id_language' => $context->language->id,
                    'id_employee' => Validate::isLoadedObject($employee) ? $employee->id : $context->employee->id,
                    'controller' => $context->controller,
                );

                $context_key = self::getKey($context->shop);
                $mirakl_contexts[$context_key] = json_decode(json_encode($context_data));//convert all as a stdClass

                return Configuration::updateGlobalValue(Mirakl::CONFIG_CONTEXT_DATA, MiraklTools::encode(serialize($mirakl_contexts)));
            }

            return true;
        }

        public static function getKey($shop)
        {
            if (version_compare(_PS_VERSION_, '1.5', '<') || !$shop instanceof Shop || !Shop::isFeatureActive()) {
                return false;
            }

            $id_shop = (int)$shop->id;
            $id_shop_group = (int)$shop->id_shop_group;
            // create a short key
            $context_key = dechex(crc32(sprintf('%02d_%02d', $id_shop, $id_shop_group)));

            return $context_key;
        }

        /**
         * In the move of applying this simple way instead of complex "restore" one.
         * Each request should attach context params it wants to set context correctly.
         * Context params include: shop, currency, employee, customer, country, language...
         * In future, this is the only used method
         *
         * @throws PrestaShopException
         */
        public static function set()
        {
            // Set shop context
            if (version_compare(_PS_VERSION_, '1.5', '>=') && Shop::isFeatureActive()) {
                $shop_context   = Tools::getValue(self::SHOP_CONTEXT, Shop::getContext());
                $id_shop_group  = (int)Tools::getValue(self::ID_SHOP_GROUP);
                $id_shop        = (int)Tools::getValue(self::ID_SHOP);

                if (Shop::CONTEXT_ALL == $shop_context) {
                    // Context is "All shop", no need id_shop / is_shop_group
                    Shop::setContext(Shop::CONTEXT_ALL);
                } elseif (Shop::CONTEXT_GROUP == $shop_context) {
                    // Context is "Group shop", no need id_shop
                    Shop::setContext(Shop::CONTEXT_GROUP, $id_shop_group);
                } else {
                    // Context is "Shop"
                    Shop::setContext(Shop::CONTEXT_SHOP, $id_shop);
                }
            }

            // Set other context parameters: employee, currency, country, language
            $context_element = array(
                'employee'  => array('key' => self::ID_EMPLOYEE, 'object' => 'Employee'),
                'currency'  => array('key' => self::ID_CURRENCY, 'object' => 'Currency'),
                'country'   => array('key' => self::ID_COUNTRY, 'object' => 'Country'),
                'language'  => array('key' => self::ID_LANGUAGE, 'object' => 'Language'),
            );

            foreach ($context_element as $key => $context_unit) {
                $id = Tools::getValue($context_unit['key']);
                if ($id) {
                    $object = new $context_unit['object']($id);
                    if (Validate::isLoadedObject($object)) {
                        Context::getContext()->$key = $object;
                    }
                }
            }
        }

        /**
         * Get current context params
         * @return array
         */
        public static function getContextParams()
        {
            $context = Context::getContext();

            return array(
                self::SHOP_CONTEXT  => Shop::getContext(),
                self::ID_SHOP_GROUP => Shop::getContextShopGroupID(true),
                self::ID_SHOP       => Shop::getContextShopID(true),
                self::ID_EMPLOYEE => $context->employee->id,
                self::ID_CURRENCY => $context->currency->id,
                self::ID_COUNTRY  => $context->country->id,
                self::ID_LANGUAGE => $context->language->id,
            );
        }

        /**
         * Build current context params as url
         * @return string
         */
        public static function getContextParamUrl()
        {
            return http_build_query(self::getContextParams());
        }
    }
}
