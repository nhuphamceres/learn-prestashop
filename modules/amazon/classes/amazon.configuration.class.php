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

require_once(dirname(__FILE__).'/../common/configuration.class.php');

class AmazonConfiguration extends CommonConfiguration
{
    public static $module = 'AMAZON';
    public static $configuration_table  = 'amazon_configuration';
    public static $old_configuration_table  = 'marketplace_configuration';

    private static $getByDirectSql;

    /**
     * todo: Remove in future because identical with parent. This is added due to outdated CommonConfiguration on other modules (but loaded before)
     * @param string $key
     * @param null $idShopGroup
     * @param null $idShop
     * @return int
     */
    public static function getIdByName($key, $idShopGroup = null, $idShop = null)
    {
        self::setDefinition();
        $configuration = ConfigurationCore::getIdByName($key, $idShopGroup, $idShop);
        self::unsetDefinition();

        return $configuration;
    }

    /**
     * As per her sister: Configuration::deleteByName
     * @param $configuration_key
     * @return bool
     */
    public static function deleteKey($configuration_key)
    {
        $prestashop_configuration_key = sprintf(static::$module.'_%s', AmazonTools::strtoupper($configuration_key));
        $marketplace_configuration_key = AmazonTools::strtolower($configuration_key);

        $pass = Configuration::deleteByName($prestashop_configuration_key);

        if (AmazonTools::tableExists(_DB_PREFIX_.self::$configuration_table)) {
            $pass = ($pass && Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.self::$configuration_table.'`
                    WHERE `name`="'.pSQL($prestashop_configuration_key).'"'));
        }

        if (AmazonTools::tableExists(_DB_PREFIX_.Amazon::TABLE_MARKETPLACE_CONFIGURATION)) {
            return ($pass && Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_CONFIGURATION.'`
                    WHERE `marketplace`="'.pSQL(Amazon::MARKETPLACE).'" AND `configuration`="'.pSQL($marketplace_configuration_key).'"'));
        }

        return ($pass);
    }

    public static function filter($obj)
    {
        return ($obj);//TODO: filter SimpleXMLElements

        if ($obj instanceof SimpleXMLElement) {
            return (null);
        } elseif (is_object($obj)) {
            foreach ($obj as $key => $val) {
                $obj->{$key} = self::filter($val);
            }
        } elseif (is_array($obj)) {
            foreach ($obj as $key => $val) {
                $obj[$key] = self::filter($val);
            }
        }

        return $obj;
    }

    /**
     * Update Amazon config for all shop
     * @param string $key
     * @param mixed $values
     * @param bool $html
     *
     * @return bool
     * todo: Move this function to CommonConfiguration
     */
    public static function updateGlobalValue($key, $values, $html = false)
    {
        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            // Version 1.5 and below does not use Configuration::$definition
            // Version 1.4 does not have multi-shop
            $where = "`name` = '{$key}'";
            if (CommonTools::getPsVersion() == '1.5') {
                $where .= Configuration::sqlRestriction(null, null);
            }

            $db     = Db::getInstance();
            $table  = _DB_PREFIX_.static::$configuration_table;
            $now    = date('Y-m-d H:i:s');

            $row = $db->getRow("SELECT * FROM `{$table}` WHERE {$where}");
            if ($row) {
                $result = $db->execute("UPDATE `{$table}` SET `value` = '".pSQL($values, $html)."', `date_upd` = '{$now}' WHERE {$where}");
            } else {
                $result = $db->execute("INSERT INTO `{$table}`(`name`, `value`, `date_add`, `date_upd`) VALUES('{$key}', '".pSQL($values, $html)."', '{$now}', '{$now}')");
            }
        } else {
            self::setDefinition();
            $result = parent::updateGlobalValue($key, $values, $html);
            self::unsetDefinition();
        }

        return $result;
    }

    /**
     * Get Amazon config for all shop
     * @param $key
     * @param null $id_lang
     *
     * @return string
     * todo: Move this function to CommonConfiguration
     */
    public static function getGlobalValue($key, $id_lang = null)
    {
        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            $db  = Db::getInstance();
            $sql = "SELECT `value` FROM `"._DB_PREFIX_.static::$configuration_table."` WHERE `name` = '".pSQL($key)."'";

            if (CommonTools::getPsVersion() == '1.5') {
                $result = $db->getValue($sql.Configuration::sqlRestriction(null, null));
            } else {
                $result = $db->getValue($sql);
            }
        } else {
            self::setDefinition();
            $result = parent::getGlobalValue($key, $id_lang);
            self::unsetDefinition();
        }

        return $result;
    }

    public static function get($configuration_key, $id_lang = null, $id_shop_group = null, $id_shop = null, $default = false)
    {
        if (!self::getByDirectSql()) {
            return parent::get($configuration_key, $id_lang, $id_shop_group, $id_shop, $default);
        }

        if (CommonTools::tableExists(_DB_PREFIX_ . static::$configuration_table)) {
            $sql = "SELECT `value` FROM `" . _DB_PREFIX_ . static::$configuration_table . "`
                    WHERE `name` = '" . pSQL(Tools::strtoupper(sprintf(static::$module . '_%s', $configuration_key))) . "'";
            $db = DB::getInstance();
            if (CommonTools::getPsVersion() == '1.4') {
                $result = $db->getValue($sql);
            } else {
                $id_shop = Shop::getContextShopID(true);
                $id_shop_group = Shop::getContextShopGroupID(true);
                $result = $db->getValue($sql . Configuration::sqlRestriction($id_shop_group, $id_shop));
            }
            if ($result !== false) {
                return unserialize(self::returnValue($result));
            }
        }

        return $default;
    }

    private static function getByDirectSql()
    {
        if (is_null(self::$getByDirectSql)) {
            self::$getByDirectSql = (bool)Configuration::get(AmazonConstant::CONFIG_GET_BY_DIRECT_SQL);
        }

        return self::$getByDirectSql;
    }
}
