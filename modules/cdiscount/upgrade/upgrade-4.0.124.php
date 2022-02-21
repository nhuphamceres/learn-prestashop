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

/**
 * Auto-upgrade module
 * Create new configuration table
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_.'cdiscount/common/tools.class.php');
require_once(_PS_MODULE_DIR_.'cdiscount/common/configuration.class.php');
require_once(_PS_MODULE_DIR_.'cdiscount/classes/cdiscount.configuration.class.php');


function upgrade_module_4_0_124($module)
{
    $config_table = _DB_PREFIX_ . CDiscountConfiguration::$configuration_table;

    if (! CommonTools::tableExists($config_table)) {
        $sql = "CREATE TABLE `{$config_table}` (
                  `id_configuration` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                  `id_shop_group` INT(11) UNSIGNED DEFAULT NULL,
                  `id_shop` INT(11) UNSIGNED DEFAULT NULL,
                  `name` VARCHAR(254) NOT NULL,
                  `value` MEDIUMBLOB,
                  `date_add` DATETIME NOT NULL,
                  `date_upd` DATETIME NOT NULL,
                  PRIMARY KEY (`id_configuration`),
                  KEY `name` (`name`),
                  KEY `id_shop` (`id_shop`),
                  KEY `id_shop_group` (`id_shop_group`)
                ) ENGINE=INNODB DEFAULT CHARSET=utf8;";

        return Db::getInstance()->execute($sql);
    }

    return true;
}
