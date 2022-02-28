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

require_once(_PS_MODULE_DIR_.'amazon/classes/amazon.tools.class.php');

function upgrade_module_4_4_048($module)
{
    $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_VAT.'` (
                `marketplace` VARCHAR(32) NOT NULL,
                `mp_order_id` VARCHAR(32) NOT NULL,
                `order_date` DATETIME DEFAULT NULL,
                `tax_date` DATETIME DEFAULT NULL,
                `tax_model` VARCHAR(32) DEFAULT NULL,
                `tax_responsible_party` VARCHAR(32) DEFAULT NULL,
                `currency` VARCHAR(5) DEFAULT NULL,
                `id_currency` INT(10) UNSIGNED DEFAULT NULL,
                `display_price` DECIMAL(10, 2) DEFAULT NULL,
                `display_price_tax_inclusive` TINYINT(1) DEFAULT NULL,
                `selling_price_tax_exclusive` DECIMAL(10, 2) DEFAULT NULL,
                `total_tax` DECIMAL(10, 2) DEFAULT NULL,
                `display_promo_amount` DECIMAL(10, 2) DEFAULT NULL,
                `display_promo_tax_inclusive` DECIMAL(10, 2) DEFAULT NULL,
                `date_add` datetime NOT NULL,
                `date_upd` datetime NOT NULL,
                UNIQUE KEY `order` (`marketplace`, `mp_order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';

    return Db::getInstance()->execute($sql);
}
