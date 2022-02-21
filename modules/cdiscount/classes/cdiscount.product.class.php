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

class CDiscountProduct extends Product
{
    const TABLE_PRODUCT_OPTION = CDiscount::TABLE_PRODUCT_OPTION;

    public static function getProductSKUById($id_product, $id_product_attribute = null)
    {
        if ($id_product_attribute) {
            $sql = 'SELECT `reference` FROM `'._DB_PREFIX_.'product_attribute`
                WHERE `id_product` = '.(int)$id_product.' AND `id_product_attribute` = '.(int)$id_product_attribute;
        } else {
            $sql = 'SELECT `reference` FROM `'._DB_PREFIX_.'product`
                WHERE `id_product` = '.(int)$id_product;
        }

        $ret = Db::getInstance()->getRow($sql);

        if (!is_array($ret) || !array_key_exists('id_product', $ret)) {
            return (null);
        }

        return ($ret['id_product']);
    }

    public static function getProductBySKU($sku, $id_shop = null)
    {
        // get combination first
        $sql = 'SELECT CONCAT(pa.`id_product`, "_", pa.`id_product_attribute`) as id_product FROM `'._DB_PREFIX_.'product_attribute` pa ';
        $sql .= $id_shop ? ' JOIN `'._DB_PREFIX_.'product_shop` ps on (ps.`id_shop` = '.(int)$id_shop.' and ps.`id_product` = pa.`id_product`) ' : null;
        $sql .= 'WHERE `reference` = "'.pSQL(trim($sku)).'"';

        $result = Db::getInstance()->getRow($sql);

        if (!$result || !Tools::strlen($result['id_product'])) {
            $sql = 'SELECT p.`id_product` FROM `'._DB_PREFIX_.'product` p ';
            $sql .= $id_shop ? ' JOIN `'._DB_PREFIX_.'product_shop` ps on (ps.`id_shop` = '.(int)$id_shop.' and ps.`id_product` = p.`id_product`) ' : null;
            $sql .= 'WHERE `reference` = "'.pSQL(trim($sku)).'"';

            $result = Db::getInstance()->getRow($sql);

            if (!$result || !$result['id_product']) {
                return false;
            }
        }

        return ($result['id_product'] ? $result['id_product'] : null);
    }

    public static function getProductBySKUDemo()
    {
        $sql = '
            SELECT IF (pa.`id_product`, concat(pa.`id_product`, "_", pa.`id_product_attribute`), p.`id_product`) as id_product FROM `'._DB_PREFIX_.'product` p
                LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa on (p.id_product = pa.id_product)
                LEFT JOIN `'._DB_PREFIX_.'stock_available` sa on (p.id_product = sa.id_product)
                WHERE p.`active` = 1 AND sa.`quantity` > 0 ORDER BY RAND() ;
				HAVING p.`reference` > ""';

        $ret = Db::getInstance()->executeS($sql);

        if (!is_array($ret)) {
            return (null);
        }

        $result = array_shift($ret);

        if (!is_array($result) || !array_key_exists('id_product', $result)) {
            return (null);
        }

        return ($result['id_product']);
    }

    public static function checkProduct($SKU, $id_shop = null)
    {
        $count = 0;

        $sql = 'SELECT count(p.`id_product`) as count FROM `'._DB_PREFIX_.'product_attribute` p ';
        $sql .= $id_shop ? ' JOIN `'._DB_PREFIX_.'product_shop` ps on (ps.`id_shop` = '.(int)$id_shop.' AND ps.`id_product` = p.`id_product`) ' : null;
        $sql .= 'WHERE `reference` = "'.pSQL(trim($SKU)).'"';

        $result = Db::getInstance()->getRow($sql);

        if (isset($result['count']) && (int)$result['count']) {
            $count += (int)$result['count'];
        }

        $sql = 'SELECT count(p.`id_product` ) as count FROM `'._DB_PREFIX_.'product` p ';
        $sql .= $id_shop ? ' JOIN `'._DB_PREFIX_.'product_shop` ps on (ps.`id_shop` = '.(int)$id_shop.' AND ps.`id_product` = p.`id_product`) ' : null;
        $sql .= 'WHERE `reference` = "'.pSQL(trim($SKU)).'"';

        $result = Db::getInstance()->getRow($sql);

        if (isset($result['count'])) {
            $count += (int)$result['count'];
        }

        return ($count);
    }

    public static function getSimpleProductName($id_product, $id_lang = 1)
    {
        $sql = 'SELECT `name` FROM `'._DB_PREFIX_.'product_lang` WHERE `id_product` = '.(int)$id_product.' AND `id_lang`='.(int)$id_lang;

        $ret = Db::getInstance()->getRow($sql);

        if (isset($ret['name'])) {
            return ($ret['name']);
        } else {
            return (null);
        }
    }

    public static function getExportProducts($category, $create_active, $create_in_stock, $date_from, $date_to, $id_shop, $debug = false)
    {
        if ($create_active) {
            $create_active = ' p.`active` > 0 ';
        } else {
            $create_active = ' 1 ';
        }

        if ($date_from && $date_to) {
            $date_filter = '(p.`date_add` BETWEEN "'.$date_from.' 00:00" AND "'.$date_to.' 23:59:59")';
        } elseif ($date_from) {
            $date_filter = 'p.`date_add` >= "'.$date_from.' 00:00"';
        } else {
            $date_filter = '1';
        }

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            if ($create_in_stock) {
                $in_stock = ' p.`quantity` > 0 ';
            } else {
                $in_stock = ' 1 ';
            }

            $sql
                = '
                SELECT p.id_product FROM `'._DB_PREFIX_.'product` p
                    LEFT JOIN `'._DB_PREFIX_.'category_product` cp on (cp.id_product = p.id_product) '
                .' WHERE cp.`id_category` = '.(int)$category.' AND '
                .$create_active.'AND'
                .$in_stock.' AND '
                .$date_filter.'
                GROUP BY p.id_product ';
        } else {
            if ($create_in_stock) {
                $in_stock = ' sa.`quantity` > 0 ';
            } else {
                $in_stock = ' 1 ';
            }
            $join = $id_shop ? ' JOIN `'._DB_PREFIX_.'product_shop` ps on (ps.`id_shop` = '.(int)$id_shop.' AND ps.`id_product` = p.`id_product`) ' : null;

            $sql
                = '
                SELECT p.id_product FROM `'._DB_PREFIX_.'product` p'.$join.'
                    LEFT JOIN `'._DB_PREFIX_.'category_product` cp on (cp.id_product = p.id_product)
                    LEFT JOIN `'._DB_PREFIX_.'stock_available` sa on (p.id_product = sa.id_product)
                    WHERE cp.`id_category` = '.(int)$category.' AND '
                .$create_active.'AND'
                .$in_stock.' AND '
                .$date_filter.'
                GROUP by p.id_product';
        }

        if ($debug) {
            CommonTools::p("SQL:".print_r($sql, true));
        }

        $rq = Db::getInstance()->ExecuteS($sql);

        if ($debug) {
            CommonTools::p("SQL : " . print_r($rq, true));
        }

        return ($rq);
    }

    public static function getProductQuantity($id_product, $id_product_attribute)
    {
        if ($id_product_attribute) {
            $sql
                = '
              SELECT pa.quantity FROM `'._DB_PREFIX_.'product` p
              LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa on (p.id_product = pa.id_product)
                  WHERE pa.id_product_attribute = '.(int)$id_product_attribute.' and p.id_product = '.(int)$id_product;
        } else {
            $sql
                = '
              SELECT p.quantity FROM `'._DB_PREFIX_.'product` p
                  WHERE id_product = '.(int)$id_product;
        }
        if ($result = Db::getInstance()->getRow($sql)) {
            return ($result['quantity']);
        } else {
            return (false);
        }
    }

    public static function getUpdateProducts($category, $create_active, $create_in_stock, $date_from, $id_shop, $debug = false)
    {
        if ($create_active) {
            $create_active = ' AND p.`active` > 0 ';
        } else {
            $create_active = null;
        }

        if ($date_from) {
            $date_sql = ' AND p.`date_upd` >= "'.$date_from.'"';
        } else {
            $date_sql = null;
        }

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            if ($create_in_stock) {
                $in_stock = ' AND p.`quantity` > 0 ';
            } else {
                $in_stock = null;
            }

            $sql
                = '
                SELECT DISTINCT p.id_product FROM `'._DB_PREFIX_.'product` p
                    LEFT JOIN `'._DB_PREFIX_.'category_product` cp on (cp.id_product = p.id_product) '
                .' WHERE cp.`id_category` = '.(int)$category.$create_active.$date_sql.$in_stock;
        } else {
            if ($create_in_stock) {
                $in_stock = ' AND sa.`quantity` > 0 ';
            } else {
                $in_stock = null;
            }
            $join = $id_shop ? ' JOIN `'._DB_PREFIX_.'product_shop` ps on (ps.`id_shop` = '.(int)$id_shop.' AND ps.`id_product` = p.`id_product`) ' : null;

            $sql
                = '
                SELECT p.id_product FROM `'._DB_PREFIX_.'product` p'.$join.'
                    LEFT JOIN `'._DB_PREFIX_.'category_product` cp on (cp.id_product = p.id_product)';

            if ($create_in_stock) {
                $sql .= ' LEFT JOIN `'._DB_PREFIX_.'stock_available` sa on (p.id_product = sa.id_product)';
            }

            $sql
                .= '
                    WHERE cp.`id_category` = '.(int)$category.$create_active.$date_sql.$in_stock.'
                GROUP by p.id_product';
        }
        
        if ($debug) {
            echo "<pre>\n";
            echo "\nSQL :".print_r($sql, true);
            echo "</pre>\n";
        }

        $rq = Db::getInstance()->ExecuteS($sql);

        if ($debug) {
            echo nl2br("\nSQL :".print_r($rq, true));
        }

        return ($rq);
    }

    public static function getProductOptions($id_product, $id_lang)
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.self::TABLE_PRODUCT_OPTION.'` p where id_product = "'.$id_product.'" and id_lang = "'.$id_lang.'" ;';

        $rq = Db::getInstance()->ExecuteS($sql);

        if ($rq) {
            return (array_shift($rq));
        } else {
            return (array(
                'force' => 0,
                'nopexport' => 0,
                'noqexport' => 0,
                'latency' => 0,
                'disable' => 0,
                'price' => '',
                'price_up' => null,
                'price_down' => null,
                'text' => '',
                'shipping' => '',
                'shipping_delay' => ''
            ));
        }
    }

    public static function getProductOptionTypes()
    {
        static $data_types = null;

        if ($data_types === null) {
            $result = Db::getInstance()->ExecuteS('SHOW COLUMNS FROM '._DB_PREFIX_.self::TABLE_PRODUCT_OPTION);

            foreach ($result as $field) {
                if (strstr($field['Type'], 'float') !== false) {
                    $data_types[$field['Field']] = 'float';
                } elseif (strstr($field['Type'], 'int') !== false) {
                    $data_types[$field['Field']] = 'int';
                } elseif (strstr($field['Type'], 'varchar') !== false) {
                    $data_types[$field['Field']] = 'varchar';
                }
            }
        }

        return ($data_types);
    }

    public static function setProductOptions($id_product, $id_lang, $options)
    {
        if (!is_array($options) || !count($options)) {
            return (false);
        }

        $data_types = self::getProductOptionTypes();

        $fields = '`id_product`, `id_lang`, ';
        $values = sprintf('%d, %d, ', (int)$id_product, (int)$id_lang);

        foreach ($options as $option => $value) {
            if (isset($data_types[$option]) && $data_types[$option] == 'int') {
                $sql_value = (int)$value;
            } elseif (isset($data_types[$option]) && $data_types[$option] == 'float') {
                $sql_value = (float)$value;
            } else {
                $sql_value = '"'.pSQL($value).'"';
            }

            $fields .= sprintf('`%s`, ', $option);
            $values .= sprintf('%s, ', $sql_value);
        }
        $sql = sprintf('REPLACE INTO `'._DB_PREFIX_.self::TABLE_PRODUCT_OPTION.'` (%s) VALUES(%s);', rtrim($fields, ', '), rtrim($values, ', '));

        $rq = Db::getInstance()->Execute($sql);

        if (CDiscount::$debug_mode) {
            echo "<pre>\n";
            echo "\nSQL :".print_r($sql, true);
            echo "\nResult :".print_r($rq, true);
            echo "</pre>\n";
        }


        $sql = 'UPDATE `'._DB_PREFIX_.'product` set date_upd="'.pSQL(date('Y-m-d H:i:s')).'" where id_product='.(int)$id_product.' ;';
        Db::getInstance()->Execute($sql);

        return ($rq);
    }

    public static function initProductOptions($id_lang)
    {
        $pass = true;
        $products = Db::getInstance()->ExecuteS('SELECT id_product from `'._DB_PREFIX_.'product`');

        foreach ($products as $product) {
            $sql = 'INSERT IGNORE `'._DB_PREFIX_.self::TABLE_PRODUCT_OPTION.'`
            (`id_product`,`id_lang`) values (
            '.$product['id_product'].', '.(int)$id_lang.')';

            if (!Db::getInstance()->Execute($sql)) {
                $pass = $pass && false;
            }
        }

        return ($pass);
    }

    public static function updateProductDate($id_product)
    {
        $sql = ' UPDATE `'._DB_PREFIX_.'product` set `date_upd` = "'.date('Y-m-d H:i:s').'" where `id_product`='.(int)$id_product;

        $rq = Db::getInstance()->Execute($sql);

        return ($rq);
    }

    public static function propagateProductOptionToCategory($id_product, $id_lang, $id_category, $field, $value)
    {
        if (!is_numeric($id_category)) {
            return (false);
        }

        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $data_types = self::getProductOptionTypes();

        if (isset($data_types[$field]) && $data_types[$field] == 'int') {
            $sql_value = (int)$value;
        } elseif (isset($data_types[$field]) && $data_types[$field] == 'float') {
            $sql_value = (float)$value;
        } else {
            $sql_value = '"'.pSQL($value).'"';
        }

        $sql = 'UPDATE `'._DB_PREFIX_.self::TABLE_PRODUCT_OPTION.'` mpo, `'._DB_PREFIX_.'product` p SET
                    mpo.`'.pSQL($field).'` = '.$sql_value.'
					WHERE mpo.`id_lang` = '.(int)$id_lang.' AND mpo.`id_product` IN (SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `id_category_default` = '.(int)$id_category.')';

        $pass = $pass && Db::getInstance()->Execute($sql);

        return ($pass);
    }

    public static function propagateProductOptionToShop($id_product, $id_lang, $field, $value)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $data_types = self::getProductOptionTypes();

        if (isset($data_types[$field]) && $data_types[$field] == 'int') {
            $sql_value = (int)$value;
        } elseif (isset($data_types[$field]) && $data_types[$field] == 'float') {
            $sql_value = (float)$value;
        } else {
            $sql_value = '"'.pSQL($value).'"';
        }

        $sql = 'UPDATE `'._DB_PREFIX_.self::TABLE_PRODUCT_OPTION.'` mpo, `'._DB_PREFIX_.'product` p SET
                    mpo.`'.pSQL($field).'` = '.$sql_value.'
                    WHERE mpo.`id_lang` = '.(int)$id_lang;

        $pass = $pass && Db::getInstance()->Execute($sql);


        return ($pass);
    }

    public static function propagateProductOptionToManufacturer($id_product, $id_lang, $id_manufacturer, $field, $value)
    {
        if (!is_numeric($id_manufacturer)) {
            return (false);
        }

        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $data_types = self::getProductOptionTypes();

        if (isset($data_types[$field]) && $data_types[$field] == 'int') {
            $sql_value = (int)$value;
        } elseif (isset($data_types[$field]) && $data_types[$field] == 'float') {
            $sql_value = (float)$value;
        } else {
            $sql_value = '"'.pSQL($value).'"';
        }

        $sql = 'UPDATE `'._DB_PREFIX_.self::TABLE_PRODUCT_OPTION.'` mpo SET
                    mpo.`'.pSQL($field).'` = '.$sql_value.'
                    WHERE mpo.`id_lang` = '.(int)$id_lang.' AND mpo.`id_product` IN (SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `id_manufacturer` = '.(int)$id_manufacturer.')';

        $pass = $pass && Db::getInstance()->Execute($sql);


        return ($pass);
    }

    public static function propagateProductOptionToSupplier($id_product, $id_lang, $id_supplier, $field, $value)
    {
        if (!is_numeric($id_supplier)) {
            return (false);
        }

        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $data_types = self::getProductOptionTypes();

        if (isset($data_types[$field]) && $data_types[$field] == 'int') {
            $sql_value = (int)$value;
        } elseif (isset($data_types[$field]) && $data_types[$field] == 'float') {
            $sql_value = (float)$value;
        } else {
            $sql_value = '"'.pSQL($value).'"';
        }

        $sql = 'UPDATE `'._DB_PREFIX_.self::TABLE_PRODUCT_OPTION.'` mpo, `'._DB_PREFIX_.'product` p SET
                    mpo.`'.pSQL($field).'` = '.$sql_value.'
                    WHERE mpo.`id_lang` = '.(int)$id_lang.' AND mpo.`id_product` IN (SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `id_supplier` = '.(int)$id_supplier.')';

        $pass = $pass && Db::getInstance()->Execute($sql);

        return ($pass);
    }

    public static function marketplaceGetCategory($id_product)
    {
        static $categories = null;
        static $id_product_to_categories = null;

        if ($categories === null) {
            $categories = CDiscountConfiguration::get('categories');
        }

        if (!$categories || !count($categories)) {
            return (false);
        }

        if ($id_product_to_categories === null) {
            $id_product_to_categories = array();

            $sql = 'SELECT `id_product`, `id_category` FROM `'._DB_PREFIX_.'category_product`';

            if (!$results = Db::getInstance()->executeS($sql)) {
                return (false);
            }
            if (is_array($results) && count($results)) {
                foreach ($results as $result) {
                    if (array_key_exists('id_product', $result) && $result['id_product']) {
                        $id_productx = $result['id_product'];
                        $id_product_to_categories[$id_productx][] = $result['id_category'];
                    }
                }
            }
        }

        if (array_key_exists($id_product, $id_product_to_categories) && count($id_product_to_categories[$id_product])) {
            return($id_product_to_categories[$id_product]);
        } else {
            return(false);
        }
    }

    public static function getExistingProducts()
    {
        $products_array = array();
        $sql = 'SELECT `sku` FROM `'._DB_PREFIX_.Cdiscount::TABLE_CDISCOUNT_OFFERS.'`';

        if ($results = Db::getInstance()->executeS($sql)) {
            if (is_array($results) && count($results)) {
                foreach ($results as $result) {
                    $products_array[] = $result['sku'];
                }
            }
        }

        return($products_array);
    }
}
