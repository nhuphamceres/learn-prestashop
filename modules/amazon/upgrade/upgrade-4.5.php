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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Amazon $module
 * @return bool
 */
function upgrade_module_4_5($module)
{
    $shipmentTbl = _DB_PREFIX_ . 'amazon_vidr_shipment';
    if (!AmazonTools::tableExists($shipmentTbl)) {
        $sql = "CREATE TABLE IF NOT EXISTS `$shipmentTbl` (
                `shipping_id` VARCHAR(25) NOT NULL,
                `marketplace` VARCHAR(5) NULL DEFAULT NULL,
                `transaction_type` VARCHAR(25) NOT NULL,
                `invoice_status` TINYINT(1) NOT NULL COMMENT '#1: InvoicePending, #2: CreditNotePending',
                `is_amazon_invoiced` TINYINT(1) NOT NULL,
                `shipment_date` DATETIME NOT NULL,
                `seller_vat_number` VARCHAR(50) NULL,
                `id_currency` INT(11) UNSIGNED NOT NULL,
                `shipment_data` TEXT NOT NULL,
                `order` INT(11) NOT NULL DEFAULT 0,
                `item` INT(11) NOT NULL DEFAULT 0,
	            `process_status` TINYINT(1) NOT NULL COMMENT '#0: Pending, #1: Processing, #2: Done, #3: Error, #4: Error-Order not imported!',
	            `processed_data` TEXT NULL,
	            `date_add` DATETIME NOT NULL,
	            `date_upd` TIMESTAMP,
                PRIMARY KEY (`shipping_id`)
            ) ENGINE INNODB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;";
        if (!Db::getInstance()->execute($sql)) {
            return false;
        }
    }

    $shipmentOrderTbl = _DB_PREFIX_ . 'amazon_vidr_shipment_order';
    if (!AmazonTools::tableExists($shipmentOrderTbl)) {
        $sql = "CREATE TABLE IF NOT EXISTS `$shipmentOrderTbl` (
                `id_shipment_order` INT(11) NOT NULL AUTO_INCREMENT,
                `shipment_id` VARCHAR(25) COLLATE utf8_unicode_ci NOT NULL,
                `mp_order_id` VARCHAR(32) COLLATE utf8_unicode_ci NOT NULL,
                PRIMARY KEY (`id_shipment_order`),
                KEY `ps_amazon_vidr_shipment_order_shipment_id` (`shipment_id`),
                KEY `ps_amazon_vidr_shipment_order_mp_order_id` (`mp_order_id`)
            ) ENGINE=INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        if (!Db::getInstance()->execute($sql)) {
            return false;
        }
    }

    return true;
}
