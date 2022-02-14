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

require_once(_PS_MODULE_DIR_.'priceminister/classes/priceminister.configuration.class.php');

class PriceMinisterShop extends Shop
{

    public static function setShop($shop)
    {
        self::$context_id_shop = $shop->id;
        self::$context_id_shop_group = $shop->id_shop_group;
        self::$context = self::CONTEXT_SHOP;
    }
}

class PriceMinisterContext
{

    public static function restore(&$context, $shop = null)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            if (!Shop::isFeatureActive()) {
                return true;
            }

            if ($shop instanceOf Shop) {
                $context_key = self::getKey($shop);
            } else {
                $context_key = Tools::getValue('context_key');
            }

             // $storedContexts = PriceMinisterConfiguration::getGlobalValue(PriceMinister::CONFIG_PM_CONTEXT_DATA);
            // Fix
            // Because contexts are saved by shop instead of global...
            // TODO Maybe check in priceminister.php how is the context stored ?
            // TODO To fix in configuration.class.php
            $storedContexts = Db::getInstance()->getValue(
                'SELECT `value`
                FROM `'._DB_PREFIX_.'priceminister_configuration`
                WHERE `name` = "PM_CONTEXT_DATA"
                AND `value` LIKE "%'.pSQL($context_key).'%"'
            );

            $storedContexts = Tools::unSerialize($storedContexts);

            if (!is_array($storedContexts) || !count($storedContexts) || !$context_key) {
                printf('%s(#%d): Wrong context, please configure your module first', basename(__FILE__), __LINE__);
                return (false);
            }

            if (!isset($storedContexts[$context_key]) || !$storedContexts[$context_key] || !is_array($storedContexts[$context_key])) {
                printf('%s(#%d): Wrong context, please configure your module first', basename(__FILE__), __LINE__) ;
                return (false);
            }

            // Set global context
            if ($storedContexts[$context_key]['context'] == Shop::CONTEXT_ALL) {
                Shop::setContext(Shop::CONTEXT_ALL);
            } else {
                Shop::setContext(
                    Shop::CONTEXT_SHOP,
                    $storedContexts[$context_key]['id_shop']
                );
            }

//            $context->employee = new Employee($storedContexts[$context_key]->employee);
//            $context->currency = new Currency($storedContexts[$context_key]->currency);
//            $context->country = new Country($storedContexts[$context_key]->country);
//            $context->shop = new Shop($storedContexts[$context_key]->shop);
//            $context->language = new Language($storedContexts[$context_key]->language);

            Context::getContext()->shop = new Shop($storedContexts[$context_key]['id_shop']);

            $context = Context::getContext();

            // Already done in Shop::setContext()
            // PriceMinisterShop::setShop($context->shop);
        }

        return (true);
    }

    public static function getKey($shop)
    {
        if (!$shop instanceof Shop) {
            return (false);
        }

        $id_shop = (int)$shop->id;
        $id_shop_group = (int)$shop->id_shop_group;
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $shop_context = Shop::getContext();
        } else {
            $shop_context = 1;
        }

        $context_key = dechex(crc32(sprintf('%02d_%02d_%02d', $id_shop, $id_shop_group, $shop_context))); // create a short key

        return ($context_key);
    }

    public static function save($context, $employee = null)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $storedContexts = PriceMinisterConfiguration::getGlobalValue(PriceMinister::CONFIG_PM_CONTEXT_DATA);

            if (is_array($storedContexts) && count($storedContexts)) {
                $PMContexts = $storedContexts;
            } else {
                $PMContexts = array();
            }

            $contextData = new Context();
            $contextData->shop = $context->shop;

            if (Validate::isLoadedObject($employee)) {
                $contextData->employee = $employee;
            } else {
                $contextData->employee = $context->employee;
            }

            $contextData->currency = $context->currency;
            $contextData->country = $context->country;
            $contextData->language = $context->language;

            $contextKey = self::getKey($contextData->shop);

            if (!isset($PMContexts[$contextKey]) || !is_array($PMContexts[$contextKey])) {
                $PMContexts[$contextKey] = array();
            }

            // $PMContexts[$contextKey] = $contextData;

            // To save space in PM_CONTEXT_DATA
            // We only store the Shop::CONTEXT and Shop::$id
            // Then we simple setShop from them
            // @see PriceMinisterContext::restore()
            $PMContexts[$contextKey]['id_shop'] = $context->shop->id;
            $PMContexts[$contextKey]['context'] = Shop::getContext();

            return (PriceMinisterConfiguration::updateGlobalValue(PriceMinister::CONFIG_PM_CONTEXT_DATA, $PMContexts));
        }

        return (true);
    }
}