<?php
/**
 * NOTICE OF LICENSE
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Common-Services Co., Ltd.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 * ...........................................................................
 *
 * @author    debuss-a
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   priceminister
 * Support by mail  :  support.priceminister@common-services.com
 */

require_once(_PS_MODULE_DIR_.'priceminister/classes/priceminister.tools.class.php');

/**
 * @param PriceMinister $module
 * @return bool
 * @throws PrestaShopDatabaseException
 */
function upgrade_module_4_2_01($module) {
    $normal_table = _DB_PREFIX_.'pm_configuration';
    $common_table = _DB_PREFIX_.'priceminister_configuration';
    $common_lang_table = $common_table.'_lang';
    $result = true;

    // 1 - Change old config table to `pm_configuration`
    if (PriceMinisterTools::tableExists($common_table, false)
        && !PriceMinisterTools::tableExists($normal_table, false)) {
        $alter_sql = 'RENAME TABLE `'.$common_table.'` TO `'.$normal_table.'`';
        $result = Db::getInstance()->execute($alter_sql);
    }

    // 2 - Create common configuration table
    if (! PriceMinisterTools::tableExists($common_table, false)) {
        $sql = "CREATE TABLE `{$common_table}` (
                    `id_configuration` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_shop_group` INT(11) UNSIGNED DEFAULT NULL,
                    `id_shop` INT(11) UNSIGNED DEFAULT NULL,
                    `name` VARCHAR(254) NOT NULL,
                    `value` LONGTEXT,
                    `date_add` DATETIME NOT NULL,
                    `date_upd` DATETIME NOT NULL,
                    PRIMARY KEY (`id_configuration`),
                    KEY `name` (`name`),
                    KEY `id_shop` (`id_shop`),
                    KEY `id_shop_group` (`id_shop_group`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        $result = $result && Db::getInstance()->execute($sql);
    }

    if (! PriceMinisterTools::tableExists($common_lang_table, false)) {
        $sql = "CREATE TABLE `{$common_lang_table}` (
                  `id_configuration` int(10) unsigned NOT NULL,
                  `id_lang` int(10) unsigned NOT NULL,
                  `value` text,
                  `date_upd` datetime DEFAULT NULL,
                  PRIMARY KEY (`id_configuration`,`id_lang`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $result = $result && Db::getInstance()->execute($sql);
    }

    return $result;
}
