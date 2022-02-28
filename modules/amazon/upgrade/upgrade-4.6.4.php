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
 * @author    Tran Pham
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

/**
 * Add column to store customization for each item in Amazon order
 * @param Amazon $module
 * @return bool
 */
function upgrade_module_4_6_4($module)
{
    $table = _DB_PREFIX_ . 'marketplace_order_items';

    if (CommonTools::tableExists($table) && !AmazonTools::amazonFieldExists($table, 'id_order_detail')) {
        $sql = "ALTER TABLE `" . pSQL($table) . "` ADD COLUMN `id_order_detail` INT(11) UNSIGNED NULL DEFAULT NULL";
        return Db::getInstance()->execute($sql);
    }

    return true;
}
