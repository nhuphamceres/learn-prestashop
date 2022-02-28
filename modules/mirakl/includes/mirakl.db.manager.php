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
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.mirakl@common-services.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class MiraklDBManager
{
    // todo: Constants used from many places should be bring to MiraklConstant
    const TABLE_PS_ORDERS = 'orders';
    const TABLE_MKP_ORDERS = 'marketplace_orders';

    /** @var Mirakl */
    protected $module;

    /** @var Context */
    protected $context;

    /** @var array */
    private $errors = array();

    private $queryResult = array();

    public function __construct($module, $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    public function addTableMkpOrders()
    {
        $mkpOrderTbl = _DB_PREFIX_ . self::TABLE_MKP_ORDERS;

        if (!MiraklTools::tableExists($mkpOrderTbl)) {
            // Todo: Shared table with Amazon, CDiscount
            // Todo: More query criteria for Amazon order only in this table
            $sql = 'CREATE TABLE IF NOT EXISTS `' . $mkpOrderTbl . '` (
                        `id_order` INT NOT NULL ,
                        `mp_order_id` VARCHAR( 64 ) NOT NULL,
                        `mp_status` INT NOT NULL DEFAULT 0,
                        `channel` VARCHAR( 16 ) NULL,
                        `channel_status` VARCHAR( 24 ) NULL DEFAULT NULL,
                        `marketplace_id` VARCHAR( 16 ) NULL DEFAULT NULL,
                        `buyer_name` VARCHAR( 32 ) NULL DEFAULT NULL,
                        `sales_channel` VARCHAR( 32 ) NULL DEFAULT NULL,
                        `order_channel` VARCHAR( 32 ) NULL DEFAULT NULL,
                        `ship_service_level` VARCHAR( 32 ) NULL DEFAULT NULL,
                        `ship_category` VARCHAR( 16 ) NULL DEFAULT NULL,
                        `is_prime` BOOL NOT NULL DEFAULT 0,
                        `is_premium` BOOL NOT NULL DEFAULT 0,
                        `is_business` BOOL NOT NULL DEFAULT 0,
                        `earliest_ship_date` datetime DEFAULT NULL,
                        `latest_ship_date` datetime DEFAULT NULL,
                        `earliest_delivery_date` datetime DEFAULT NULL,
                        `latest_delivery_date` datetime DEFAULT NULL,
                        `shipping_services` VARCHAR(255) NULL,
                        PRIMARY KEY (`id_order`) ,
                        KEY `mp_order_id`(`mp_order_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';

            return $this->executeStatement($sql);
        }

        // mp_order_id of Mirakl is quite long, increase it to 64 in case other modules set as 32
        $alterLength = "ALTER TABLE `$mkpOrderTbl` MODIFY `mp_order_id` VARCHAR( 64 ) NOT NULL";
        return $this->executeStatement($alterLength);
    }

    public function copyOrdersToShareTable()
    {
        $psOrdersTbl = _DB_PREFIX_ . self::TABLE_PS_ORDERS;
        $mkpOrdersTbl = _DB_PREFIX_ . self::TABLE_MKP_ORDERS;

        if (!MiraklTools::fieldExistsR($psOrdersTbl, 'mp_order_id') || !MiraklTools::fieldExistsR($psOrdersTbl, 'mp_channel')) {
            return true;
        };

        $getSql = "SELECT `id_order`, `mp_order_id`, `mp_channel`, `reference`, `payment` 
                   FROM `$psOrdersTbl` WHERE `module` = '" . pSQL('mirakl') . "'";
        if (!$this->executeStatement($getSql, true)) {
            return false;
        }

        foreach ($this->queryResult as $order) {
            $mpOrderId = $order['mp_order_id'] ? $order['mp_order_id'] : $order['reference'];
            $channel = $order['mp_channel'] ? $order['mp_channel'] : $order['payment'];
            $replaceSql = "REPLACE INTO `$mkpOrdersTbl`(`id_order`, `mp_order_id`, `mp_status`, `sales_channel`, `order_channel`) 
                              VALUES('" . pSQL($order['id_order']) . "', '" . pSQL($mpOrderId) . "', 0, '" . pSQL($channel) . "', 'Mirakl')";
            if (!$this->executeStatement($replaceSql)) {
                return false;
            }
        }

        return true;
    }

    public function addTableProductOption()
    {
        $productOptTbl = _DB_PREFIX_ . MiraklConstant::TABLE_PRODUCT_OPTION;

        if (!MiraklTools::tableExists($productOptTbl)) {
            $tblSql = 'CREATE TABLE IF NOT EXISTS `' . pSQL($productOptTbl) . '` (
                      `id_product` INT NOT NULL ,
                      `id_lang` INT NOT NULL ,
                      `force` TINYINT NOT NULL DEFAULT  "0",
                      `disable` TINYINT NULL DEFAULT NULL,
                      `price` FLOAT NULL DEFAULT NULL,
                      `shipping` VARCHAR(32) NULL DEFAULT NULL,
                      `text` VARCHAR(128) NULL DEFAULT NULL,
                      `mkp_specific_fields` text NULL DEFAULT NULL,
                       UNIQUE KEY `id_product` (`id_product`,`id_lang`)
                    );';
            return $this->executeStatement($tblSql);
        }

        if (!MiraklTools::fieldExistsR($productOptTbl, 'mkp_specific_fields')) {
            $fieldSql = "ALTER TABLE `$productOptTbl` ADD COLUMN `mkp_specific_fields` text NULL DEFAULT NULL";
            return $this->executeStatement($fieldSql);
        }

        return true;
    }

    public function getErrors()
    {
        $errors = $this->errors;
        $this->errors = array();
        return $errors;
    }

    /**
     * @param $sql
     * @param false $select
     * @return bool
     */
    protected function executeStatement($sql, $select = false)
    {
        if ($select) {
            $result = Db::getInstance()->executeS($sql);
            if (is_array($result)) {
                $this->queryResult = $result;
                return true;
            }
        } else {
            if (Db::getInstance()->execute($sql)) {
                return true;
            }
        }

        $this->errors[] = Db::getInstance()->getMsgError() . ' .SQL: ' . $sql;
        return false;
    }
}
