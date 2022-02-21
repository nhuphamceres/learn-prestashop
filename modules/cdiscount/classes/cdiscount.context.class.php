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

class CDiscountShop extends Shop
{
    public static function setShop($shop)
    {
        self::$context_id_shop = $shop->id;
        self::$context_id_shop_group = $shop->id_shop_group;
        self::$context = self::CONTEXT_SHOP;
    }
}


class CDiscountContext extends CDiscount
{
    public static function restore(&$context, $shop = null, $debug = false)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            if (!Shop::isFeatureActive()) {
                $context = Context::getContext();
                if ($context instanceof Context && !$context->controller instanceof Controller) {
                    $context->controller = new FrontController();
                }
                return (true);
            }
            $storedContexts = unserialize(parent::decode(Configuration::getGlobalValue(parent::KEY.'_CONTEXT_DATA')));

            if ($shop instanceof Shop) {
                $context_key = self::getKey($shop);
            } else {
                $context_key = Tools::getValue('context_key');
            }

            if (!is_array($storedContexts) || !count($storedContexts) || !is_string($context_key)) {
                if ($debug) {
                    printf('%s(#%d): Wrong context, please configure your module first', basename(__FILE__), __LINE__);
                }

                return (false);
            }

            if (!isset($storedContexts[$context_key]) || !$storedContexts[$context_key] || !is_object($storedContexts[$context_key])) {
                if ($debug) {
                    printf('%s(#%d): Wrong context, please configure your module first', basename(__FILE__), __LINE__);
                }

                return (false);
            }

            $idEmployee = $storedContexts[$context_key]->id_employee;
            $idCurrency = $storedContexts[$context_key]->id_currency;
            $idCountry = $storedContexts[$context_key]->id_country;
            $idLanguage = $storedContexts[$context_key]->id_language;

            $context->employee = new Employee($idEmployee);
            if (isset($storedContexts[$context_key]->class_name_controller) && isset($storedContexts[$context_key]->id_controller)) {
                $classNameController = $storedContexts[$context_key]->class_name_controller;
                $idController = $storedContexts[$context_key]->id_controller;
                $context->controller = new $classNameController($idController);
            } else {
                $context->controller = new FrontController();
            }
            $context->currency = new Currency($idCurrency);
            $context->country = new Country($idCountry);
            $context->language = new Language($idLanguage);

            
            $idShop = (int)$storedContexts[$context_key]->id_shop;
            if ((int)$idShop && is_numeric($idShop)) {
                $context->shop = new Shop($idShop);
            }

            CDiscountShop::setShop($context->shop);
        }

        return (true);
    }

    public static function getKey($shop)
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            return (null);
        }

        if (!Shop::isFeatureActive()) {
            return (null);
        }

        if (!$shop instanceof Shop && !$shop instanceof StdClass) {
            return (null);
        }

        $id_shop = (int)$shop->id;
        $id_shop_group = (int)$shop->id_shop_group;

        $context_key = dechex(crc32(sprintf('%02d_%02d', $id_shop, $id_shop_group))); // create a short key

        return ($context_key);
    }

    public static function save($context, $employee = null)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=') && Shop::isFeatureActive()) {
            $storedContexts = unserialize(parent::decode(Configuration::getGlobalValue(parent::KEY.'_CONTEXT_DATA')));

            if (is_array($storedContexts) && count($storedContexts)) {
                $moduleContexts = $storedContexts;
            } else {
                $moduleContexts = array();
            }

            $contextData = array(
                'id_shop' => $context->shop->id,
                'id_currency' => $context->currency->id,
                'id_country' => $context->country->id,
                'id_language' => $context->language->id,
                'id_controller' => $context->controller->id,
                'class_name_controller' => $context->controller->controller_name . 'Controller'
            );
            if (Validate::isLoadedObject($employee)) {
                $contextData['id_employee'] = $employee->id;
            } else {
                $contextData['id_employee'] = $context->employee->id;
            }

            $contextData = Tools::jsonDecode(Tools::jsonEncode($contextData));//convert all as a stdClass

            $contextKey = self::getKey($context->shop);
            if (!isset($moduleContexts[$contextKey]) || !is_array($moduleContexts[$contextKey])) {
                $moduleContexts[$contextKey] = array();
            }

            $moduleContexts[$contextKey] = $contextData;

            return (Configuration::updateGlobalValue(parent::KEY.'_CONTEXT_DATA', parent::encode(serialize($moduleContexts))));
        }

        return (true);
    }
}
