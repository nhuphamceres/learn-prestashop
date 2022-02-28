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
 * @author    Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 *  Support by mail  :  support.mirakl@common-services.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

if (!class_exists('MiraklProduct')) {
    class MiraklProduct extends Product
    {
        public static $table_products_options = 'mirakl_product_option';

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
            }

            // if NULL, check in Supplier Reference
            if (!$result || !$result['id_product']) {
                $result = Db::getInstance()->getRow(
                    'SELECT `id_product`, `id_product_attribute`
                    FROM `'._DB_PREFIX_.'product_supplier`
                    WHERE `product_supplier_reference` = "'.pSQL(trim($sku)).'"'
                );

                if ($result['id_product']) {
                    if ($result['id_product_attribute']) {
                        $result['id_product'] .= '_'.$result['id_product_attribute'];
                    }

                    return $result['id_product'];
                }
            }

            // if NULL, check in EAN13 instead of Reference
            if (!$result || !$result['id_product']) {
                // Check combination first
                $result = Db::getInstance()->getRow(
                    'SELECT `id_product`, `id_product_attribute`
                    FROM `'._DB_PREFIX_.'product_attribute`
                    WHERE `ean13` = "'.pSQL($sku).'"'
                );

                if ($result['id_product']) {
                    if ($result['id_product_attribute']) {
                        $result['id_product'] .= '_'.$result['id_product_attribute'];
                    }

                    return $result['id_product'];
                }

                // Check product
                $result = Db::getInstance()->getRow(
                    'SELECT `id_product`
                    FROM `'._DB_PREFIX_.'product`
                    WHERE `ean13` = "'.pSQL($sku).'"'
                );
            }

            return ($result['id_product'] ? $result['id_product'] : null);
        }

        public static function getSimpleProductName($id_product, $id_lang = 1)
        {
            $sql = 'SELECT `name` FROM `'._DB_PREFIX_.'product_lang` WHERE `id_product` = '.(int)$id_product.' AND `id_lang`='.(int)$id_lang;

            $ret = Db::getInstance()->getRow($sql);

            if (isset($ret['name'])) {
                return $ret['name'];
            } else {
                return null;
            }
        }

        public static function getExportProducts($category, $create_active, $create_in_stock, $date_from, $date_to, $debug = false)
        {
            if (!$date_to) {
                $date_to = false;
            } //for validation

            if ($create_active) {
                $create_active = ' p.`active` > 0 ';
            } else {
                $create_active = ' 1 ';
            }

            if ($date_from) {
                $date_filter = '(p.`date_upd` >= "'.pSQL($date_from).' 00:00:00")';
            } else {
                $date_filter = '1';
            }

            if (version_compare(_PS_VERSION_, '1.5', '<')) {
                if ($create_in_stock) {
                    $in_stock = ' p.`quantity` > 0 ';
                } else {
                    $in_stock = ' 1 ';
                }

                $sql = 'SELECT p.id_product FROM `'._DB_PREFIX_.'product` p
                    LEFT JOIN `'._DB_PREFIX_.'category_product` cp on (cp.id_product = p.id_product)
                    WHERE cp.`id_category` = '.(int)$category.' AND '.$create_active.'AND'.$in_stock.' AND '.$date_filter.'
					GROUP BY p.id_product ';
            } else {
                if ($create_in_stock) {
                    $in_stock = ' sa.`quantity` > 0 ';
                } else {
                    $in_stock = ' 1 ';
                }

                $sql = 'SELECT p.id_product FROM `'._DB_PREFIX_.'product` p
                    	INNER JOIN `'._DB_PREFIX_.'product_shop` product_shop
                    	ON product_shop.id_product = p.id_product '.Shop::addSqlRestrictionOnLang('product_shop').'
                    	LEFT JOIN `'._DB_PREFIX_.'category_product` cp on (cp.id_product = p.id_product)
                    	LEFT JOIN `'._DB_PREFIX_.'stock_available` sa on (p.id_product = sa.id_product)
                    	WHERE cp.`id_category` = '.(int)$category.' AND '.$create_active.'AND'.$in_stock.' AND '.$date_filter.'
						GROUP by p.id_product';
            }

            if ($debug) {
                echo nl2br("\nSQL :".print_r($sql, true));
            }

            $rq = Db::getInstance()->ExecuteS($sql);

            if ($debug) {
                echo nl2br("\nSQL :".print_r($rq, true));
            }

            return $rq;
        }

        public static function getProductQuantity($id_product, $id_product_attribute)
        {
            if ($id_product_attribute) {
                $sql = 'SELECT pa.quantity FROM `'._DB_PREFIX_.'product` p
              LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa on (p.id_product = pa.id_product)
                  WHERE pa.id_product_attribute = '.(int)$id_product_attribute.' and p.id_product = '.(int)$id_product;
            } else {
                $sql = 'SELECT p.quantity FROM `'._DB_PREFIX_.'product` p
                  WHERE id_product = '.(int)$id_product;
            }
            if ($result = Db::getInstance()->getRow($sql)) {
                return $result['quantity'];
            } else {
                return false;
            }
        }

        public static function updateProductDate($id_product)
        {
            $sql = 'UPDATE `'._DB_PREFIX_.'product` set date_upd = "'.pSQL(date('Y-m-d H:i:s')).'" where id_product='.(int)$id_product;

            $rq = Db::getInstance()->Execute($sql);

            return $rq;
        }

        /**
         * @param $id_product
         * @param $id_lang
         * @return array|mixed
         * @throws \PrestaShopDatabaseException
         */
        public static function getProductOptions($id_product, $id_lang)
        {
            // Options produits (actif, stock etc..)
            $sql = 'SELECT * FROM `'._DB_PREFIX_.MiraklConstant::TABLE_PRODUCT_OPTION.'` p
                    WHERE id_product = "'.(int)$id_product.'" and id_lang = "'.(int)$id_lang.'" ;';

            if ($rq = Db::getInstance()->getRow($sql)) {
                return $rq;
            } else {
                return array(
                    'force' => 0,
                    'nopexport' => 0,
                    'noqexport' => 0,
                    'latency' => 0,
                    'disable' => 0,
                    'price' => '',
                    'price_down' => null,
                    'text' => '',
                    'shipping' => '',
                    'mkp_specific_fields' => '[]',
                );
            }
        }

        public static function setProductOptions($id_product, $id_lang, $options)
        {
            $sql = 'REPLACE INTO `'._DB_PREFIX_.MiraklConstant::TABLE_PRODUCT_OPTION.'`
                (`id_product`, `id_lang`, `force`, `price`, `text`, `disable`, `shipping`, `mkp_specific_fields`)
                VALUES('.(int)$id_product.', '.(int)$id_lang.', '.(int)$options['force'].', '.(float)$options['price'].', "'.pSQL($options['text']).'", '.(int)$options['disable'].', "'.pSQL($options['shipping']).'", "'.pSQL($options['mkp_specific_fields']).'"); ';
            $rq = Db::getInstance()->Execute($sql);

            $sql = 'UPDATE `'._DB_PREFIX_.'product` set date_upd="'.pSQL(date('Y-m-d H:i:s')).'" where id_product='.(int)$id_product.' ;';
            Db::getInstance()->Execute($sql);

            return $rq;
        }

        public static function propagateProductOptionsText($id_product, $id_lang, $id_category, $option)
        {
            // Propagate Text
            $pass = true;

            $pass = self::initProductOptions($id_lang) && $pass;

            if (!$id_category) {
                $id_category = (int)Db::getInstance()->getValue(
                    'SELECT `id_category_default`
                    FROM `'._DB_PREFIX_.'product`
                    WHERE `id_product` = '.(int)$id_product
                );
            }

            $sql = 'UPDATE `'._DB_PREFIX_.self::$table_products_options.'` mpo, `'._DB_PREFIX_.'product` p SET
			mpo.`text` = "'.pSQL($option['text']).'", p.`date_upd` = "'.pSQL(date('Y-m-d H:i:s')).'"
			WHERE p.id_product = mpo.id_product and p.id_category_default = '.(int)$id_category.' and id_lang = '.(int)$id_lang;

            if (!Db::getInstance()->execute($sql)) {
                $pass = false && $pass;
            }

            return $pass;
        }

        public static function propagateToShopProductOptionsText($id_product, $id_lang, $option)
        {
            $pass = true;

            $pass = self::initProductOptions($id_lang) && $pass;

            $sql = 'UPDATE `'._DB_PREFIX_.self::$table_products_options.'` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`text` = "'.pSQL($option['text']).'", p.`date_upd` = "'.pSQL(date('Y-m-d H:i:s')).'"
            WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang;

            if (!Db::getInstance()->execute($sql)) {
                $pass = $pass && false;
            }

            $pass = self::updateProductDate($id_product) && $pass;

            return $pass;
        }

        public static function propagateToManufacturerProductOptionsText($id_product, $id_lang, $id_manufacturer, $option)
        {
            $pass = true;

            $pass = self::initProductOptions($id_lang) && $pass;

            if (!$id_manufacturer) {
                $id_manufacturer = (int)Db::getInstance()->getValue(
                    'SELECT `id_manufacturer`
                    FROM `'._DB_PREFIX_.'product`
                    WHERE `id_product` = '.(int)$id_product
                );
            }

            $sql = 'UPDATE `'._DB_PREFIX_.self::$table_products_options.'` mpo, `'._DB_PREFIX_.'product` p, `'._DB_PREFIX_.'manufacturer` m SET
            mpo.`text` = "'.pSQL($option['text']).'", p.`date_upd` = "'.pSQL(date('Y-m-d H:i:s')).'"
            WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang.' and p.`id_manufacturer` = m.`id_manufacturer`
            AND p.`id_manufacturer` ='.(int)$id_manufacturer;

            if (!Db::getInstance()->Execute($sql)) {
                $pass = $pass && false;
            }

            $pass = self::updateProductDate($id_product) && $pass;

            return $pass;
        }

        public static function propagateProductOptionsDisable($id_product, $id_lang, $id_category, $option)
        {
            // Propagate Disable
            $pass = true;

            if (!$id_category) {
                $id_category = (int)Db::getInstance()->getValue(
                    'SELECT `id_category_default`
                    FROM `'._DB_PREFIX_.'product`
                    WHERE `id_product` = '.(int)$id_product
                );
            }

            $pass = self::initProductOptions($id_lang) && $pass;
            $sql = 'UPDATE `'._DB_PREFIX_.self::$table_products_options.'` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`disable` = '.(int)$option['disable'].', p.`date_upd` = "'.pSQL(date('Y-m-d H:i:s')).'"
            WHERE p.id_product = mpo.id_product and p.id_category_default = '.(int)$id_category.' and id_lang = '.(int)$id_lang;

            if (!Db::getInstance()->Execute($sql)) {
                $pass = false && $pass;
            }

            return $pass;
        }

        public static function propagateToShopProductOptionsDisable($id_product, $id_lang, $option)
        {
            $pass = true;

            $pass = self::initProductOptions($id_lang) && $pass;

            $sql = 'UPDATE `'._DB_PREFIX_.self::$table_products_options.'` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`disable` = '.(int)$option['disable'].', p.`date_upd` = "'.pSQL(date('Y-m-d H:i:s')).'"
            WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang;

            if (!Db::getInstance()->Execute($sql)) {
                $pass = $pass && false;
            }

            return $pass;
        }

        public static function propagateToManufacturerProductOptionsDisable($id_product, $id_lang, $id_manufacturer, $option)
        {
            $pass = true;

            $pass = self::initProductOptions($id_lang) && $pass;

            if (!$id_manufacturer) {
                $id_manufacturer = (int)Db::getInstance()->getValue(
                    'SELECT `id_manufacturer`
                    FROM `'._DB_PREFIX_.'product`
                    WHERE `id_product` = '.(int)$id_product
                );
            }

            $sql = 'UPDATE `'._DB_PREFIX_.self::$table_products_options.'` mpo, `'._DB_PREFIX_.'product` p, `'._DB_PREFIX_.'manufacturer` m SET
            mpo.`disable` = '.(int)$option['disable'].', p.`date_upd` = "'.pSQL(date('Y-m-d H:i:s')).'"
            WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang.'
            and p.`id_manufacturer` = m.`id_manufacturer` AND p.`id_manufacturer` ='.(int)$id_manufacturer;

            if (!Db::getInstance()->Execute($sql)) {
                $pass = $pass && false;
            }

            $pass = self::updateProductDate($id_product) && $pass;

            return $pass;
        }

        public static function propagateProductOptionsForce($id_product, $id_lang, $id_category, $option)
        {
            // Propagate Force
            $pass = true;
            $pass = self::initProductOptions($id_lang) && $pass;

            if (!$id_category) {
                $id_category = (int)Db::getInstance()->getValue(
                    'SELECT `id_category_default`
                    FROM `'._DB_PREFIX_.'product`
                    WHERE `id_product` = '.(int)$id_product
                );
            }

            $sql = 'UPDATE `'._DB_PREFIX_.self::$table_products_options.'` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`force` = '.(int)$option['force'].', p.`date_upd` = "'.pSQL(date('Y-m-d H:i:s')).'"
            WHERE p.id_product = mpo.id_product and p.id_category_default = '.(int)$id_category.' and id_lang = '.(int)$id_lang;

            if (!Db::getInstance()->Execute($sql)) {
                $pass = false && $pass;
            }

            return $pass;
        }

        public static function propagateToShopProductOptionsForce($id_product, $id_lang, $option)
        {
            $pass = true;

            $pass = self::initProductOptions($id_lang) && $pass;

            $sql = 'UPDATE `'._DB_PREFIX_.self::$table_products_options.'` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`force` = '.(int)$option['force'].', p.`date_upd` = "'.pSQL(date('Y-m-d H:i:s')).'"
            WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang;

            if (!Db::getInstance()->Execute($sql)) {
                $pass = $pass && false;
            }

            return $pass;
        }

        public static function propagateToManufacturerProductOptionsForce($id_product, $id_lang, $id_manufacturer, $option)
        {
            $pass = true;

            $pass = self::initProductOptions($id_lang) && $pass;

            if (!$id_manufacturer) {
                $id_manufacturer = (int)Db::getInstance()->getValue(
                    'SELECT `id_manufacturer`
                    FROM `'._DB_PREFIX_.'product`
                    WHERE `id_product` = '.(int)$id_product
                );
            }

            $sql = 'UPDATE `'._DB_PREFIX_.self::$table_products_options.'` mpo, `'._DB_PREFIX_.'product` p, `'._DB_PREFIX_.'manufacturer` m SET
              mpo.`force` = '.(int)$option['force'].', p.`date_upd` = "'.pSQL(date('Y-m-d H:i:s')).'"
              WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang.'
              and p.`id_manufacturer` = m.`id_manufacturer` and p.`id_manufacturer` = '.(int)$id_manufacturer;

            if (!Db::getInstance()->Execute($sql)) {
                $pass = $pass && false;
            }

            return $pass;
        }

        public static function initProductOptions($id_lang)
        {
            $pass = true;
            $products = Db::getInstance()->ExecuteS('SELECT `id_product` FROM `'._DB_PREFIX_.'product`');

            foreach ($products as $product) {
                $sql = 'INSERT IGNORE `'._DB_PREFIX_.self::$table_products_options.'`
            (`id_product`,`id_lang`) values (
            '.(int)$product['id_product'].', '.(int)$id_lang.')';

                if (!Db::getInstance()->execute($sql)) {
                    $pass = $pass && false;
                }
            }

            return $pass;
        }
    }
}
