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
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   CDiscount
 * Support by mail:  support.cdiscount@common-services.com
 */

class CDiscountHookManager
{
    /** @var Cdiscount */
    public $module;

    /** @var Context */
    public $context;

    public function __construct($module, $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * Same code for all our modules. Also modify others if change (amazon, cdiscount)
     * This hook is called 2 time for each module, 1 for filter then 1 for listing
     * @param $params
     */
    public function actionAdminOrdersListingFieldsModifier($params)
    {
        $module = $this->module->name;
        $moduleTbl = _DB_PREFIX_ . 'marketplace_orders';
        $moduleTblAlias = "cs_mp_order_alias";
        $moduleField = 'mp_order_id';

        if (isset($params['fields']) && !isset($params['select']) && !isset($params['join'])) {
            // Filter injection
            $this->pd("$module filter")->pd($params['fields']);

            if (!isset($params['fields']['mp_order_id'])) {
                $params['fields']['mp_order_id'] = array(
                    // Although identical to listing injection, but cannot omit. Otherwise, sort / search not working
                    'filter_key' => "$moduleTblAlias!mp_order_id",    // Adjust filter key in search form
                    'cs_integrated' => false,
                );
            }
        } elseif (isset($params['fields'], $params['select'], $params['join'])) {
            // Listing injection
            $this->pd("$module listing")->pd($params['fields']);

            if (!isset($params['fields']['mp_order_id'])
                || !isset($params['fields']['mp_order_id']['cs_integrated']) || !$params['fields']['mp_order_id']['cs_integrated']) {
                $params['join'] .= " LEFT JOIN `$moduleTbl` AS `$moduleTblAlias` ON (a.`id_order` = $moduleTblAlias.`id_order`)";
                $params['select'] .= ", `$moduleTblAlias`.`$moduleField` AS `mp_order_id`";
                $params['fields']['mp_order_id'] = array(
                    'title' => 'Marketplace Order ID',
                    'align' => 'text-center',
                    'class' => 'fixed-width-xs',
                    'filter_key' => "$moduleTblAlias!mp_order_id",    // Adjust filter key in search form
                    'cs_integrated' => true,
                );
            }
        }

        $this->pd("$module params after resolve:")->pd($params);
    }

    /**
     * @param $debug
     * @return CDiscountHookManager
     */
    private function pd($debug)
    {
        // $this->module->debug is lately resolved (getContent), so it always false in in this time, use '$debug_mode' instead
        if (Cdiscount::$debug_mode) {
            CDiscountToolsR::p($debug);
        }

        return $this;
    }
}
