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

require_once(_PS_MODULE_DIR_.'cdiscount/common/tools.class.php');

class CDiscountToolsR extends CommonTools
{
    // todo: Upgrading mismatch. Remove in future
    protected static $dbFieldExist = array();

    // todo: Upgrading mismatch. Remove in future
    public static function fieldExistsR($table, $field, $cache = true)
    {
        if (!$cache || !isset(self::$dbFieldExist[$table])) {
            self::$dbFieldExist[$table] = array();
            $query = Db::getInstance()->ExecuteS('SHOW COLUMNS FROM `'.pSQL($table).'`');
            if (is_array($query) && count($query)) {
                foreach ($query as $row) {
                    self::$dbFieldExist[$table][$row['Field']] = true;
                }
            }
        }

        return isset(self::$dbFieldExist[$table], self::$dbFieldExist[$table][$field]);
    }

    public static function isUsingOverride()
    {
        if (defined('_PS_OVERRIDE_DIR_') && !Configuration::get('PS_DISABLE_OVERRIDES')
            && ($override_content = CommonTools::globRecursive(_PS_OVERRIDE_DIR_ . '*.php'))) {
            foreach ($override_content as $fn) {
                if (preg_match('/[A-Z]\w+.php$/', $fn)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function getOverrideClasses()
    {
        $overrides = array();
        if (class_exists('PrestaShopAutoload')) {
            $prestashopAutoLoad = PrestaShopAutoload::getInstance();
            $prestashopAutoLoad->generateIndex();

            if (is_array($prestashopAutoLoad->index) && count($prestashopAutoLoad->index)) {
                foreach ($prestashopAutoLoad->index as $item) {
                    if (stripos($item['path'], 'override/') !== false) {
                        $overrides[] = $item['path'];
                    }
                }
            }
        }

        return $overrides;
    }
}
