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
require_once(_PS_MODULE_DIR_.'amazon/classes/amazon.configuration.class.php');

function upgrade_module_4_2_258($module)
{
    $config_table      = _DB_PREFIX_ . AmazonConfiguration::$configuration_table;
    $config_lang_table = $config_table . '_lang';
    $result = true;

    if (! AmazonTools::tableExists($config_table)) {
        $sql = "CREATE TABLE `{$config_table}` (
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
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

        $result &= Db::getInstance()->execute($sql);
    }

    if (! AmazonTools::tableExists($config_lang_table)) {
        $sql = "CREATE TABLE `{$config_lang_table}` (
                  `id_configuration` int(10) unsigned NOT NULL,
                  `id_lang` int(10) unsigned NOT NULL,
                  `value` text,
                  `date_upd` datetime DEFAULT NULL,
                  PRIMARY KEY (`id_configuration`,`id_lang`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $result &= Db::getInstance()->execute($sql);
    }

    return $result;
}
