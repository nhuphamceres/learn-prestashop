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

require_once dirname(__FILE__).'/fnac.tools.class.php';

class FNAC_Shop extends Shop
{
    public static function setShop($shop)
    {
        self::$context_id_shop = $shop->id;
        self::$context_id_shop_group = $shop->id_shop_group;
        self::$context = self::CONTEXT_SHOP;
    }
}


class FNAC_Context
{
    public static function restore(&$context, $shop = null)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=') && Shop::isFeatureActive()) {
            $storedContexts = unserialize(FNAC_Tools::decode(Configuration::getGlobalValue('FNAC_CONTEXT_DATA')));

            if ($shop instanceof Shop) {
                $context_key = self::getKey($shop);
            } else {
                $context_key = Tools::getValue('context_key');
            }

            if (!is_array($storedContexts) || !count($storedContexts) || !$context_key) {
                printf('%s(#%d): Wrong context, please configure your module first', basename(__FILE__), __LINE__);
                return (false);
            }

            if (!isset($storedContexts[$context_key]) || !$storedContexts[$context_key] || !$storedContexts[$context_key] instanceof Context) {
                printf('%s(#%d): Wrong context, please configure your module first', basename(__FILE__), __LINE__);
                return (false);
            }

            $context->employee = $storedContexts[$context_key]->employee;
            // @see below the save function
            $context->currency = new Currency($storedContexts[$context_key]->currency);
            $context->country = $storedContexts[$context_key]->country;
            $context->shop = $storedContexts[$context_key]->shop;
            $context->language = $storedContexts[$context_key]->language;

            FNAC_Shop::setShop($context->shop);
        }

        return (true);
    }

    public static function save($context, $employee = null)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            // 0:"kwanzas angolais (1977–1
            $storedContexts = unserialize(FNAC_Tools::decode(Configuration::getGlobalValue('FNAC_CONTEXT_DATA')));

            if (is_array($storedContexts) && count($storedContexts)) {
                $fnacContexts = $storedContexts;
            } else {
                $fnacContexts = array();
            }

            $contextData = new Context();
            $contextData->shop = $context->shop;

            if (Validate::isLoadedObject($employee)) {
                $contextData->employee = $employee;
            } else {
                $contextData->employee = $context->employee;
            }

            $contextData->shop = $context->shop;
            $contextData->currency = $context->currency;
            $contextData->country = $context->country;
            $contextData->language = $context->language;

            $contextKey = self::getKey($contextData->shop);

            if (!isset($fnacContexts[$contextKey]) || !is_array($fnacContexts[$contextKey])) {
                $fnacContexts[$contextKey] = array();
            }

            $fnacContexts[$contextKey] = $contextData;

            // Remove useless stuff
            $fnacContexts[$contextKey]->controller = null;
            $fnacContexts[$contextKey]->override_controller_name_for_translations = null;
            $fnacContexts[$contextKey]->tab = null;
            $fnacContexts[$contextKey]->smarty = null;
            $fnacContexts[$contextKey]->mobile_detect = null;
            $fnacContexts[$contextKey]->mode = null;

            // On PS1.7, currency contains a repositery of currency details with special caracters
            // which result in a failure when serialize comes.
            // Save ID instead and load it when charging the context.
            $fnacContexts[$contextKey]->currency = $fnacContexts[$contextKey]->currency->id;

            return (Configuration::updateGlobalValue('FNAC_CONTEXT_DATA', FNAC_Tools::encode(serialize($fnacContexts))));
        }

        return (true);
    }

    public static function getKey($shop)
    {
        if (!$shop instanceof Shop) {
            return (false);
        }

        if (version_compare(_PS_VERSION_, '1.5', '<') || !Shop::isFeatureActive()) {
            return (false);
        }

        $id_shop = (int)$shop->id;

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_shop_group = (int)isset($shop->id_shop_group) ? $shop->id_shop_group : 1;
        } else {
            $id_shop_group = 1;
        }

        $context_key = dechex(crc32(sprintf('%02d_%02d', $id_shop, $id_shop_group))); // create a short key

        return ($context_key);
    }
}
