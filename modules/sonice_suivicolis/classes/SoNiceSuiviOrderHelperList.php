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
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice@common-services.com
 */

class SoNiceOrderHelperList
{

    /** @var array */
    public $list;

    /** @var string */
    public $html = '';

    /** @var array */
    public $id_carriers;

    /** @var array */
    public $config;

    /** @var array */
    public $filter_statuses;

    /** @var int */
    public $id_lang;

    /** @var int */
    public $weeks;

    /** @var Context */
    public $context;


    public function __construct($id_lang = null)
    {
        if (!class_exists('Context')) {
            require_once(dirname(__FILE__).'/../backward_compatibility/backward.php');
        }

        $this->context = Context::getContext();
        $this->id_lang = (int)$id_lang;

        if (!$this->id_lang && isset($this->context->language) && Validate::isLoadedObject($this->context->language)) {
            $this->id_lang = (int)$this->context->language->id;
        } else {
            $this->id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        }

        $this->config = unserialize(Configuration::get(
            'SONICE_SUIVICOLIS_CONF',
            null,
            $this->context->shop->id_shop_group,
            $this->context->shop->id
        ));
        $this->filter_statuses = array_merge(
            array(0),
            (array)unserialize(Configuration::get(
                'SONICE_SUIVICOLIS_STATUSES',
                null,
                $this->context->shop->id_shop_group,
                $this->context->shop->id
            ))
        );
        $this->weeks = isset($this->config['nb_weeks']) && $this->config['nb_weeks'] ?
            (int)$this->config['nb_weeks'] : 1;

        $this->setCarrierList();
    }

    private function setCarrierList()
    {
        $this->id_carriers = array_filter(array_merge(array(0), (array)unserialize(Configuration::get(
            'SONICE_SUIVICOLIS_CARRIER',
            null,
            null,
            Context::getContext()->shop->id
        ))), array($this, 'arrayFilterNotNullOrFalse'));

        $deleted_carriers = array();
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $deleted_carriers = Db::getInstance()->executeS(
                'SELECT `id_carrier`
				FROM `'._DB_PREFIX_.'carrier`
				WHERE `id_reference` IN (
                    SELECT `id_reference`
                    FROM `'._DB_PREFIX_.'carrier`
                    WHERE `id_carrier` IN ('.pSQL(implode(', ', $this->id_carriers)).')
                )'
            );
        }

        $old_carriers = array_map(array($this, 'getIdCarrierFromArray'), $deleted_carriers);

        $this->id_carriers = array_unique(array_merge($this->id_carriers, $old_carriers));
    }

    private function arrayFilterNotNullOrFalse($v)
    {
        return $v !== null && $v !== false;
    }

    private function getIdCarrierFromArray($v)
    {
        return $v['id_carrier'];
    }

    public function getOldPSQuery()
    {
        $sql = 'SELECT DISTINCT 
                  o.`id_order`,
                  o.`id_carrier`,
                  o.`id_customer`,
                  o.`id_cart`,
                  oh.`id_order_state` AS current_state,
                  osl.`name` AS current_state_name,
                  o.`id_address_delivery`,
                  o.`shipping_number`,
                  o.`date_add`,
                  c.`firstname`,
                  c.`lastname`,
                  car.`name` AS carrier_name,
                  car.`url`,
                  snsc.`coliposte_state`,
                  snsc.`coliposte_date`,
                  snsc.`coliposte_location`,
                  snsc.`date_upd`,
                  os.`color`
                FROM `'._DB_PREFIX_.'orders` o
                LEFT JOIN `'._DB_PREFIX_.'order_history` oh ON (o.`id_order` = oh.`id_order`)
                LEFT JOIN `'._DB_PREFIX_.'order_state` os ON (os.`id_order_state` = oh.`id_order_state`)
                LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (
                    oh.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int)$this->id_lang.' 
                )
                LEFT JOIN `'._DB_PREFIX_.'carrier` car ON (car.`id_carrier` = o.`id_carrier`)
                LEFT JOIN `'._DB_PREFIX_.'customer` c ON (o.`id_customer` = c.`id_customer`)
                LEFT JOIN `'._DB_PREFIX_.'sonice_suivicolis` snsc ON (
                    BINARY snsc.`shipping_number` = BINARY o.`shipping_number`
                )
                WHERE oh.`date_add` = (
                    SELECT MAX(`date_add`)
                    FROM `'._DB_PREFIX_.'order_history` oh2
                    WHERE o.`id_order` = oh2.`id_order`
                )
                AND o.`shipping_number` != ""
                AND oh.`id_order_state` NOT IN ('.implode(',', $this->filter_statuses).')
                AND o.`id_carrier` IN ('.implode(',', $this->id_carriers).')
                AND o.`date_add` > DATE_ADD(NOW(), INTERVAL - '.(int)$this->weeks.' WEEK)
                AND o.`id_order` NOT IN (SELECT `id_order` FROM `'._DB_PREFIX_.'order_return`)
                AND oh.`id_order_state` NOT IN (
                    SELECT `id_order_state`
                    FROM `'._DB_PREFIX_.'order_state`
                    WHERE `hidden` = 0
                    AND `logable` = 0
                    AND `delivery` = 0
                )
                GROUP BY o.`id_order` ORDER BY oh.`id_order` DESC';

        return $sql;
    }

    public function getNewPSQuery()
    {
        $sql = 'SELECT DISTINCT 
                  o.`id_order`,
                  o.`reference`,
                  o.`id_carrier`,
                  o.`id_customer`,
                  o.`id_cart`,
                  o.`current_state`,
                  osl.`name` AS current_state_name,
                  o.`id_address_delivery`,
                  IF(LENGTH(oc.`tracking_number`) >= 10, oc.`tracking_number`, o.`shipping_number`) AS shipping_number,
                  o.`date_add`,
                  c.`firstname`,
                  c.`lastname`,
                  car.`name` AS carrier_name,
                  car.`url`,
                  snsc.`coliposte_state`,
                  snsc.`coliposte_date`,
                  snsc.`coliposte_location`,
                  snsc.`date_upd`,
                  os.`color`
                FROM `'._DB_PREFIX_.'orders` o
                LEFT JOIN `'._DB_PREFIX_.'order_history` oh ON (o.`id_order` = oh.`id_order`)
                LEFT JOIN `'._DB_PREFIX_.'order_carrier` oc ON (o.`id_order` = oc.`id_order`)
                LEFT JOIN `'._DB_PREFIX_.'order_state` os ON (os.`id_order_state` = o.`current_state`)
                LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (
                    o.`current_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int)$this->id_lang.' 
                )
                LEFT JOIN `'._DB_PREFIX_.'carrier` car ON (car.`id_carrier` = oc.`id_carrier`)
                LEFT JOIN `'._DB_PREFIX_.'customer` c ON (o.`id_customer` = c.`id_customer`)
                LEFT JOIN `'._DB_PREFIX_.'sonice_suivicolis` snsc ON (
                    BINARY snsc.`shipping_number` = BINARY oc.`tracking_number` OR
                    BINARY snsc.`shipping_number` = BINARY o.`shipping_number`
                )
                WHERE oh.`date_add` = (
                    SELECT MAX(`date_add`)
                    FROM `'._DB_PREFIX_.'order_history` oh2
                    WHERE o.`id_order` = oh2.`id_order`
                )
                AND (oc.`tracking_number` != "" OR o.`shipping_number` != "")
                AND o.`current_state` NOT IN ('.implode(',', $this->filter_statuses).')
                AND o.`id_carrier` IN ('.implode(',', $this->id_carriers).')
                AND o.`date_add` > DATE_ADD(NOW(), INTERVAL - '.(int)$this->weeks.' WEEK)
                AND o.`id_order` NOT IN (SELECT `id_order` FROM `'._DB_PREFIX_.'order_return`) -- Was commented before, dont rememeber why...
                AND o.`current_state` NOT IN (
                    SELECT `id_order_state`
                    FROM `'._DB_PREFIX_.'order_state`
                    WHERE `hidden` = 0
                    AND `logable` = 0
                    AND `delivery` = 0
                    AND `shipped` = 0
                    AND `paid` = 0
                )
                AND o.`id_shop` '.
                (Shop::getContext() == Shop::CONTEXT_ALL ? 'LIKE "%"' : '= '.(int)Context::getContext()->shop->id).'
                GROUP BY o.`id_order`
                ORDER BY oh.`id_order` DESC';

        return $sql;
    }

    public function getOrders()
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $sql = $this->getNewPSQuery();
        } else {
            $sql = $this->getOldPSQuery();
        }
        
        return Db::getInstance()->executeS($sql);
    }
}
