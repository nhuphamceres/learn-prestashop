<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Feed.biz
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Feed.biz is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Feed.biz.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 *
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

require_once(dirname(__FILE__) . '/../classes/amazon.configuration.class.php');

class AmazonShop extends Shop
{
    public static function setShop($shop)
    {
        self::$context_id_shop = $shop->id;
        self::$context_id_shop_group = $shop->id_shop_group;
        self::$context = self::CONTEXT_SHOP;
    }
}

class AmazonContext
{
    /**
     * Restore shop context for ajax scripts
     * @param $context
     * @param null $shop
     * @param bool|false $debug
     * @return bool
     */
    public static function restore(&$context, $shop = null, $debug = false)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            if (!Shop::isFeatureActive()) {
                $context = Context::getContext();
                if (!property_exists($context, 'controller') || !is_object($context->controller)) {
                    $context->controller = new FrontController();
                }

                return (true);
            }

            $storedContexts = unserialize(AmazonTools::decode(AmazonConfiguration::getGlobalValue('AMAZON_CONTEXT_DATA')));

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

            $context->controller = isset($storedContexts[$context_key]->controller) && is_object($storedContexts[$context_key]->controller) 
                ? $storedContexts[$context_key]->controller 
                : new FrontController();
            $context->employee = new Employee($idEmployee);
            $context->currency = new Currency($idCurrency);
            $context->country = new Country($idCountry);
            $context->language = new Language($idLanguage);

            
            $idShop = (int)$storedContexts[$context_key]->id_shop;
            if ((int)$idShop && is_numeric($idShop)) {
                $context->shop = new Shop($idShop);
            }

            AmazonShop::setShop($context->shop);
        }

        return (true);
    }

    /**
     * Generate an unique key to store the context
     * @param $shop
     * @return null|string
     */
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

    /**
     * Save store context
     * @param $context
     * @param null $employee
     * @param bool|false $debug
     * @return bool
     */
    public static function save($context, $employee = null, $debug = false)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $storedContexts = unserialize(AmazonTools::decode(AmazonConfiguration::getGlobalValue('AMAZON_CONTEXT_DATA')));

            if (is_array($storedContexts) && count($storedContexts)) {
                $amazonContexts = $storedContexts;
            } else {
                $amazonContexts = array();
            }

            // save the whole controller instance instead of its ID
            // because PS 1.6 has problem creating AdminModulesController when restoring context
            // https://common-services-force.monday.com/boards/1971464818/pulses/1989896654
            $contextData = array(
                'id_shop' => $context->shop->id,
                'id_currency' => $context->currency->id,
                'id_country' => $context->country->id,
                'id_language' => $context->language->id,
                'controller' => $context->controller,
            );
            if (Validate::isLoadedObject($employee)) {
                $contextData['id_employee'] = $employee->id;
            } else {
                $contextData['id_employee'] = $context->employee->id;
            }

            $contextData = Tools::jsonDecode(Tools::jsonEncode($contextData));//convert all as a stdClass

            $contextKey = self::getKey($context->shop);
            if (!isset($amazonContexts[$contextKey]) || !is_array($amazonContexts[$contextKey])) {
                $amazonContexts[$contextKey] = array();
            }

            $amazonContexts[$contextKey] = $contextData;

            return (AmazonConfiguration::updateGlobalValue('AMAZON_CONTEXT_DATA', AmazonTools::encode(serialize($amazonContexts))));
        }

        return (true);
    }
}
