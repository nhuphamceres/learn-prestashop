<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
 * Contact by Email :  support.priceminister@common-services.com
 */

require_once dirname(__FILE__).'/priceminister.tools.class.php';

class PriceMinisterProductExt extends Product
{

    public static function shippingPerItem($weight, $price, $debug = false)
    {
        require_once(_PS_MODULE_DIR_.'priceminister/classes/priceminister.configuration.class.php');

        $shipping_per_item_table = array();

        static $pm_shipping = null;
        static $id_address = null;

        if ($pm_shipping == null) {
            $pm_shipping = PriceMinister::getConfig(PriceMinister::CONFIG_PM_SHIPPING);
            $address_map = PriceMinisterConfiguration::get(PriceMinister::CONFIG_PM_ADDRESS_MAP);
            $id_address = null;

            if (!is_array($address_map) || !isset($address_map['fr'])) {
                if ($debug) {
                    printf('%s(%s): %s', basename(__FILE__), __LINE__, 'Configuration is incomplete - please save the configuration of your module...');
                }

                return (false);
            } else {
                $id_address = (int)$address_map['fr'];
            }
        }

        if (!$pm_shipping['shipping_per_item']) {
            return (false);
        }

        $shipping_options_table = $pm_shipping['shipping_options'];
        $shipping_table = $pm_shipping['shipping_table'];
        $shipping_defaults = $pm_shipping['shipping_defaults'];

        if (is_array($shipping_options_table) && count($shipping_options_table)) {
            foreach ($shipping_options_table as $shipping_method => $shipping_options) {
                if (!is_array($shipping_options)) {
                    continue;
                }

                foreach (array_keys($shipping_options) as $shipping_option) {
                    $id_carrier = isset($shipping_table['carrier'][$shipping_method][$shipping_option]) ? (int)$shipping_table['carrier'][$shipping_method][$shipping_option] : null;
                    $id_zone = isset($shipping_table['zone'][$shipping_method][$shipping_option]) ? (int)$shipping_table['zone'][$shipping_method][$shipping_option] : null;

                    $shipping_tax_incl = 0;

                    if ($id_carrier && $id_zone) {
                        $carrier = new Carrier($id_carrier);

                        if (!Validate::isLoadedObject($carrier)) {
                            if ($debug) {
                                printf('%s(%d): %s (%d)', basename(__FILE__), __LINE__, 'Unable to load carrier', $id_carrier);
                            }
                        } else {
                            $carrier_tax_rate = 0;

                            // Carrier Taxes
                            //
                            if (method_exists('Carrier', 'getTaxesRate')) {
                                $tax_address = new Address((int)$id_address);

                                if (Validate::isLoadedObject($tax_address)) {
                                    $carrier_tax_rate = (float)$carrier->getTaxesRate($tax_address);
                                }
                            } else {
                                if (method_exists('Tax', 'getCarrierTaxRate')) {
                                    $carrier_tax_rate = (float)Tax::getCarrierTaxRate($id_carrier, (int)$id_address);
                                }
                            }

                            $carrier_shipping_price = 0;
                            $shipping_tax_incl = 0;

                            if ($carrier instanceOf Carrier && method_exists('Carrier', 'getDeliveryPriceByWeight')) {
                                $carrier_shipping_price = $carrier->getDeliveryPriceByWeight($weight, $id_zone);

                                $shipping_tax_excl = $carrier_shipping_price;
                                $shipping_tax_incl = ((((float)$carrier_tax_rate * (float)$shipping_tax_excl) / 100) + (float)$shipping_tax_excl);
                            }

                            if (!(int)$carrier_shipping_price && $carrier instanceOf Carrier && method_exists('Carrier', 'getDeliveryPriceByPrice')) {
                                $carrier_shipping_price = $carrier->getDeliveryPriceByPrice($price, $id_zone);
                                $shipping_tax_excl = $carrier_shipping_price;
                                $shipping_tax_incl = ((((float)$carrier_tax_rate * (float)$shipping_tax_excl) / 100) + (float)$shipping_tax_excl);
                            }
                        }
                    }

                    $minimum = isset($shipping_defaults['minimum'][$shipping_method][$shipping_option]) ?
                        (float)$shipping_defaults['minimum'][$shipping_method][$shipping_option] : null;

                    $shipping_fees = $minimum > $shipping_tax_incl ?
                        number_format($minimum, 2) : number_format($shipping_tax_incl, 2);

                    // In case of manually set minimum = 0. todo: Should not implement this because Rakuten does not accept 0 or negative shipping fee
                    if ($shipping_fees || isset($shipping_defaults['minimum'][$shipping_method][$shipping_option])) {
                        $shipping_method_index = $shipping_method; // trim(Tools::strtoupper($shipping_method));

                        $shipping_per_item_table[$shipping_method_index][$shipping_option] = array(
                            'authorization' => 1,
                            'leader_price' => $shipping_fees,
                        );

                        if (isset($shipping_defaults['additionnal'][$shipping_method_index][$shipping_option])) {
                            $additionnal_shipping_fees = abs(($shipping_fees * (float)$shipping_defaults['additionnal'][$shipping_method_index][$shipping_option]) / 100);

                            if ((float)$additionnal_shipping_fees) {
                                $shipping_per_item_table[$shipping_method][$shipping_option]['follower_price'] = $additionnal_shipping_fees;
                            }
                        }
                    }
                }
            }
        }

        return ($shipping_per_item_table);
    }

    public static function getProductBySKU($sku)
    {
        $sql = 'SELECT IF (pa.`id_product`, concat(pa.`id_product`, "_", pa.`id_product_attribute`), p.`id_product`) as id_product FROM `'._DB_PREFIX_.'product` p
                LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa on (p.id_product = pa.id_product)
                WHERE pa.`reference` = "'.pSQL($sku).'" or p.`reference` = "'.pSQL($sku).'" LIMIT 1 ; ';

        $ret = Db::getInstance()->ExecuteS($sql);

        if (!$ret) {
            return (null);
        }

        $ret = array_shift($ret);

        return ($ret['id_product']);
    }

    public static function getProductBySKUDemo()
    {
        $sql = '
            SELECT IF (pa.`id_product`, concat(pa.`id_product`, "_", pa.`id_product_attribute`), p.`id_product`) as id_product FROM `'._DB_PREFIX_.'product` p
                LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa on (p.id_product = pa.id_product)
                LEFT JOIN `'._DB_PREFIX_.'stock_available` sa on (p.id_product = sa.id_product)
                WHERE p.`active` = 1 AND sa.`quantity` > 0 ORDER BY RAND() ';

        $ret = Db::getInstance()->getRow($sql);

        if (!$ret) {
            return (null);
        }

        return ($ret['id_product']);
    }

    /**
     * @param $SKU
     * @param Shop|int $shop
     * @param bool $debug
     * @return bool|int
     */
    public static function checkProduct($SKU, $shop = null, $debug = false)
    {
        if (is_int($shop) && $shop) {
            $shop = new Shop($shop);
        }

        if (!$shop) {
            $shop = Context::getContext()->shop;
        }

        if (method_exists($shop, 'getContext') && $shop::getContext() == Shop::CONTEXT_ALL) {
            $id_shop = 0;
        } else {
            $id_shop = $shop->id;
        }

        $count = 0;

        if (empty($SKU)) {
            if ($debug) {
                print_r(__METHOD__.' : No SKU provided.');
            }
            return (false);
        }

        $sql = 'SELECT COUNT(`id_product`) FROM `'._DB_PREFIX_.'product_attribute` WHERE `reference` = "'.pSQL($SKU).'"';
        $result = (int)Db::getInstance()->getValue($sql);

        if ($debug) {
            print_r(__METHOD__.' : SQL'."\n".$sql);
            print_r(__METHOD__.' : Result'."\n".print_r($result, 1));
        }

        $count += (int)$result;

        $sql = 'SELECT COUNT(p.`id_product`) FROM `'._DB_PREFIX_.'product` p ';
        // TODO is it useful ? Why not just check all Db, why care of ID_SHOP ??
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $sql .= $id_shop ? ' JOIN `'._DB_PREFIX_.'product_shop` ps ON (ps.`id_shop` = '.(int)$id_shop.' AND ps.`id_product` = p.`id_product`) ' : null;
        }
        $sql .= 'WHERE `reference` = "'.pSQL($SKU).'"';
        $result = (int)Db::getInstance()->getValue($sql);

        if ($debug) {
            print_r(__METHOD__.' : SQL'."\n".$sql);
            print_r(__METHOD__.' : Result'."\n".print_r($result, 1));
        }

        $count += (int)$result;

        if ($debug) {
            print_r(__METHOD__.' : COUNT = '.$count);
        }

        return ($count);
    }

    public static function getProductQuantity($id_product, $id_product_attribute)
    {
        if ($id_product_attribute) {
            $sql = '
              SELECT pa.quantity FROM `'._DB_PREFIX_.'product` p
              LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa on (p.id_product = pa.id_product)
                  WHERE pa.id_product_attribute = '.(int)$id_product_attribute.' and p.id_product = '.(int)$id_product;
        } else {
            $sql = '
              SELECT p.quantity FROM `'._DB_PREFIX_.'product` p
                  WHERE id_product = '.(int)$id_product;
        }
        if ($result = Db::getInstance()->getRow($sql)) {
            return ($result['quantity']);
        } else {
            return (false);
        }
    }

    public static function getUpdateProducts($categories, $create_active, $create_in_stock, $date_from = null, $date_to = null, $debug = false)
    {
        return (self::getExportProducts('update', $categories, $create_active, $create_in_stock, $date_from, $date_to, $debug));
    }

    public static function getExportProducts($kind, $categories, $create_active, $create_in_stock, $date_from = null, $date_to = null, $debug = false)
    {
        $list = implode(',', $categories);

        if ($create_active) {
            $create_active = ' p.`active` > 0 ';
        } else {
            $create_active = ' 1 ';
        }

        if ($kind == 'create') {
            $field = 'date_add';
        } else {
            $field = 'date_upd';
        }

        if ($date_from) {
            $date_from_sql = 'p.`'.$field.'` >= "'.pSQL($date_from).'"';
        } else {
            $date_from_sql = '1';
        }

        if ($date_to) {
            $date_to_sql = 'p.`'.$field.'` <= "'.pSQL($date_to).'"';
        } else {
            $date_to_sql = '1';
        }

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            if ($create_in_stock) {
                $in_stock = ' p.`quantity` > 0 ';
            } else {
                $in_stock = ' 1 ';
            }

            $sql = '
                SELECT p.id_product FROM `'._DB_PREFIX_.'product` p
                    LEFT JOIN `'._DB_PREFIX_.'category_product` cp on (cp.id_product = p.id_product) '
                .' WHERE cp.`id_category` IN ('.$list.') AND '
                .$create_active.' AND '
                .$date_from_sql.' AND '
                .$date_to_sql.' AND '
                .$in_stock;
        } else {
            $context = Context::getContext();
            if (Tools::getValue('context_key')) {
                PriceMinisterContext::restore($context);
            }

            if ($create_in_stock) {
                $in_stock = ' sa.`quantity` > 0 ';
            } else {
                $in_stock = ' 1 ';
            }

            $sql = '
                SELECT p.id_product FROM `'._DB_PREFIX_.'product` p
                    LEFT JOIN `'._DB_PREFIX_.'category_product` cp on (cp.id_product = p.id_product)
                    LEFT JOIN `'._DB_PREFIX_.'stock_available` sa on (p.id_product = sa.id_product)
					'.(Tools::getValue('context_key') ? 'LEFT JOIN `'._DB_PREFIX_.'product_shop` ps ON (p.`id_product` = ps.`id_product`)' : '')
                .'
					WHERE cp.`id_category` IN ('.$list.') AND '
                .$create_active.' AND '
                .$date_from_sql.' AND '
                .$date_to_sql.' AND '
                .$in_stock.' AND '
                .(!Tools::getValue('all_shop') && Validate::isLoadedObject($context->shop) ?
                    'ps.`id_shop` = '.$context->shop->id : '1').'
					GROUP by p.id_product';
        }

        if ($debug) {
            echo nl2br("\nSQL :".print_r($sql, true));
        }

        $rq = Db::getInstance()->executeS($sql);

        if ($debug) {
            echo nl2br("\nSQL :".print_r($rq, true));
        }

        return ($rq);
    }

    public static function getCreateProducts($categories, $create_active, $create_in_stock, $date_from = null, $date_to = null, $debug = false)
    {
        return (self::getExportProducts('create', $categories, $create_active, $create_in_stock, $date_from, $date_to, $debug));
    }

    /* Options produits (actif, stock etc..) */

    public static function getProductOptions($id_product, $id_product_attribute = null, $id_lang = null)
    {
        if ($id_lang !== null) {
            $sql_lang = ' AND `id_lang` = '.(int)$id_lang;
        } else {
            $sql_lang = '';
        }

        $sql_attribute = ' AND `id_product_attribute` = '.((int)$id_product_attribute ? $id_product_attribute : 0);

        $sql = 'SELECT * FROM `'._DB_PREFIX_.'priceminister_product_option` p where `id_product` = '.(int)$id_product.$sql_attribute.$sql_lang;

        $rq = Db::getInstance()->ExecuteS($sql);

        return $rq;
    }

    public static function setProductOptions($id_product, $id_lang, $options, $id_product_attributes = null)
    {
        $option_fields = array(
            'id_product',
            'id_product_attribute',
            'id_lang',
            'force',
            'disable',
            'price',
            'text',
            'repricing_min',
            'repricing_max'
        );

        $fields_sql = null;

        foreach ($option_fields as $field) {
            $fields_sql .= sprintf('`%s`, ', $field);
        }
        $fields_sql = rtrim($fields_sql, ', ');

        $sql = 'REPLACE INTO `'._DB_PREFIX_.'priceminister_product_option` ('.$fields_sql.') values(';

        $insert_statement = null;

        foreach ($option_fields as $field) {
            switch ($field) {
                case 'id_product':
                    $insert_statement .= (int)$id_product.', ';
                    break;
                case 'id_product_attribute':
                    $insert_statement .= (int)$id_product_attributes.', ';
                    break;
                case 'id_lang':
                    $insert_statement .= (int)$id_lang.', ';
                    break;
                default:
                    if (array_key_exists($field, $options)) {
                        // Can also use filter_var() for float and int
                        if (is_bool($options[$field])) {
                            $insert_statement .= ((bool)$options[$field] ? 1 : 0).', ';
                        } elseif (is_float($options[$field]) || is_numeric($options[$field]) && ((float)$options[$field] != (int)$options[$field])) {
                            $insert_statement .= (float)$options[$field].', ';
                        } elseif (is_int($options[$field])) {
                            $insert_statement .= (int)$options[$field].', ';
                        } elseif (is_numeric($options[$field])) {
                            $insert_statement .= (int)$options[$field].', ';
                        } elseif (empty($options[$field])) {
                            $insert_statement .= 'null, ';
                        } else {
                            $insert_statement .= '"'.pSQL($options[$field]).'", ';
                        }
                    } else {
                        $insert_statement .= 'null, ';
                    }
            }
        }

        $sql .= rtrim($insert_statement, ' ,').');';

        $rq = Db::getInstance()->Execute($sql);

        return ($rq);
    }

    // Propagate Option
    public static function propagateProductOptionToCategory($id_product, $id_lang, $id_category, $field, $value)
    {
        $pass = self::initProductOptions($id_lang);

        $insert_statement = null;

        if (is_bool($value)) {
            $insert_statement .= sprintf('`%s`=%s', $field, ((bool)$value ? 1 : 0));
        } elseif (is_float($value)) {
            $insert_statement .= sprintf('`%s`=%s', $field, (float)$value);
        } elseif (is_int($value)) {
            $insert_statement .= sprintf('`%s`=%s', $field, (int)$value);
        } elseif (is_numeric($value)) {
            $insert_statement .= sprintf('`%s`=%s', $field, (int)$value);
        } elseif (empty($value)) {
            $insert_statement .= sprintf('`%s`=null', $field, (int)$value);
        } else {
            $insert_statement .= sprintf('`%s`="%s"', $field, pSQL($value));
        }
        $insert_statement .= ', p.`date_upd` = "'.date('Y-m-d H:i:s').'" ';

        $sql = 'UPDATE `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_PRODUCT_OPTION.'` mpo, `'._DB_PREFIX_.'product` p SET '.
            $insert_statement.'
                WHERE p.`id_product` = mpo.`id_product` AND p.`id_category_default` = '.(int)$id_category.' AND mpo.`id_lang`='.(int)$id_lang;

        if (!$rq = Db::getInstance()->Execute($sql)) {
            $pass = false;
        }

        return ($pass);
    }

    public static function initProductOptions($id_lang)
    {
        $pass = true;
        $products = Db::getInstance()->ExecuteS('SELECT id_product from `'._DB_PREFIX_.'product`');

        foreach ($products as $product) {
            $sql = 'INSERT IGNORE `'._DB_PREFIX_.'priceminister_product_option`
            (`id_product`,`id_lang`) values (
            '.$product['id_product'].', '.(int)$id_lang.')';

            if (!Db::getInstance()->Execute($sql)) {
                $pass = $pass && false;
            }
        }

        return ($pass);
    }

    public static function propagateProductOptionToShop($id_product, $id_lang, $field, $value)
    {
        $pass = self::initProductOptions($id_lang);

        $insert_statement = null;

        if (is_bool($value)) {
            $insert_statement .= sprintf('`%s`=%s', $field, ((bool)$value ? 1 : 0));
        } elseif (is_float($value)) {
            $insert_statement .= sprintf('`%s`=%s', $field, (float)$value);
        } elseif (is_int($value)) {
            $insert_statement .= sprintf('`%s`=%s', $field, (int)$value);
        } elseif (is_numeric($value)) {
            $insert_statement .= sprintf('`%s`=%s', $field, (int)$value);
        } elseif (empty($value)) {
            $insert_statement .= sprintf('`%s`=null', $field, (int)$value);
        } else {
            $insert_statement .= sprintf('`%s`="%s"', $field, pSQL($value));
        }
        $insert_statement .= ', p.`date_upd` = "'.date('Y-m-d H:i:s').'" ';

        $sql = 'UPDATE `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_PRODUCT_OPTION.'` mpo, `'._DB_PREFIX_.'product` p SET '.$insert_statement.' WHERE p.`id_product`=mpo.`id_product` AND mpo.`id_lang`='.(int)$id_lang;

        if (!$rq = Db::getInstance()->Execute($sql)) {
            $pass = $pass && false;
        }

        return ($pass);
    }

    public static function propagateProductOptionToManufacturer($id_product, $id_lang, $id_manufacturer, $field, $value)
    {
        $pass = self::initProductOptions($id_lang);

        $insert_statement = null;

        if (is_bool($value)) {
            $insert_statement .= sprintf('`%s`=%s ', $field, ((bool)$value ? 1 : 0));
        } elseif (is_float($value)) {
            $insert_statement .= sprintf('`%s`=%s ', $field, (float)$value);
        } elseif (is_int($value)) {
            $insert_statement .= sprintf('`%s`=%s ', $field, (int)$value);
        } elseif (is_numeric($value)) {
            $insert_statement .= sprintf('`%s`=%s ', $field, (int)$value);
        } elseif (empty($value)) {
            $insert_statement .= sprintf('`%s`=null ', $field, (int)$value);
        } else {
            $insert_statement .= sprintf('`%s`="%s" ', $field, pSQL($value));
        }
        $insert_statement .= ', p.`date_upd` = "'.date('Y-m-d H:i:s').'" ';

        $sql = 'UPDATE `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_PRODUCT_OPTION.'` mpo, `'._DB_PREFIX_.'product` p, `'._DB_PREFIX_.'manufacturer` m SET '.
            $insert_statement.' WHERE p.`id_product` = mpo.`id_product` AND mpo.`id_lang`='.(int)$id_lang.' AND p.`id_manufacturer`=m.`id_manufacturer` AND p.`id_manufacturer`='.(int)$id_manufacturer;

        if (!$rq = Db::getInstance()->Execute($sql)) {
            $pass = $pass && false;
        }

        return ($pass);
    }

    /* Propagate Text */

    public static function propagateProductOptionsText($id_product, $id_lang, $id_category, $option)
    {
        $pass = true;
        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'priceminister_product_option` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`text` = "'.pSQL($option['text']).'"
            WHERE p.id_product = mpo.id_product and p.id_category_default = '.(int)$id_category.' and id_lang = '.(int)$id_lang;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = false && $pass;
        }

        return ($pass);
    }

    public static function propagateToShopProductOptionsText($id_product, $id_lang, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'priceminister_product_option` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`text` = "'.pSQL($option['text']).'"
            WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = $pass && false;
        }

        return ($pass);
    }

    public static function propagateToManufacturerProductOptionsText($id_product, $id_lang, $id_manufacturer, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'priceminister_product_option` mpo, `'._DB_PREFIX_.'product` p, `'._DB_PREFIX_.'manufacturer` m SET
            mpo.`text` = "'.pSQL($option['text']).'"
            WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang.' and p.`id_manufacturer` = m.`id_manufacturer` AND p.`id_manufacturer`='.(int)$id_manufacturer;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = $pass && false;
        }

        return ($pass);
    }

    /* Propagate Disable */
    public static function propagateProductOptionsDisable($id_product, $id_lang, $id_category, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'priceminister_product_option` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`disable` = '.(int)$option['disable'].'
            WHERE p.id_product = mpo.id_product and p.id_category_default = '.(int)$id_category.' and id_lang = '.(int)$id_lang;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = false && $pass;
        }

        return ($pass);
    }

    public static function propagateToShopProductOptionsDisable($id_product, $id_lang, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'priceminister_product_option` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`disable` = '.(int)$option['disable'].'
            WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = $pass && false;
        }

        return ($pass);
    }

    public static function propagateToManufacturerProductOptionsDisable($id_product, $id_lang, $id_manufacturer, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'priceminister_product_option` mpo, `'._DB_PREFIX_.'product` p, `'._DB_PREFIX_.'manufacturer` m SET
            mpo.`disable` = '.(int)$option['disable'].'
            WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang.' and p.`id_manufacturer` = m.`id_manufacturer` AND p.`id_manufacturer`='.(int)$id_manufacturer;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = $pass && false;
        }

        return ($pass);
    }

    /* Propagate Force */
    public static function propagateProductOptionsForce($id_product, $id_lang, $id_category, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'priceminister_product_option` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`force` = '.(int)$option['force'].'
            WHERE p.id_product = mpo.id_product and p.id_category_default = '.(int)$id_category.' and id_lang = '.(int)$id_lang;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = false && $pass;
        }

        return ($pass);
    }

    public static function propagateToShopProductOptionsForce($id_product, $id_lang, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'priceminister_product_option` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`force` = '.(int)$option['force'].'
            WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = $pass && false;
        }

        return ($pass);
    }

    public static function propagateToManufacturerProductOptionsForce($id_product, $id_lang, $id_manufacturer, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'priceminister_product_option` mpo, `'._DB_PREFIX_.'product` p, `'._DB_PREFIX_.'manufacturer` m SET
              mpo.`force` = '.(int)$option['force'].'
              WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang.' and p.`id_manufacturer` = m.`id_manufacturer` AND p.`id_manufacturer`='.(int)$id_manufacturer;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = $pass && false;
        }

        return ($pass);
    }

    /**
     * @param $SKU
     * @param Shop|int $id_shop_default
     * @return bool|StdClass
     */
    public static function getBySKU($SKU, $shop = null)
    {
        ///////////
        if (is_int($shop) && $shop) {
            $shop = new Shop($shop);
        }

        if (!$shop) {
            $shop = Context::getContext()->shop;
        }

        if (method_exists($shop, 'getContext') && $shop::getContext() == Shop::CONTEXT_ALL) {
            $id_shop_default = 0;
        } else {
            $id_shop_default = $shop->id;
        }

        //if (!Validate::isReference($SKU)) {
        //    die(Tools::displayError(__FILE__.'  '.__LINE__));
        //}

        $query = '
			SELECT IF(pa.reference, pa.reference, p.reference) AS reference, p.id_product, IF (pa.id_product_attribute, pa.id_product_attribute, NULL) AS id_product_attribute
			FROM `'._DB_PREFIX_.'product` p
            LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON (p.id_product = pa.id_product) ';
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $query .= $id_shop_default ? ' JOIN `'._DB_PREFIX_.'product_shop` ps on (ps.`id_shop` = '.(int)$id_shop_default.' and ps.`id_product` = p.`id_product`) ' : null;
        }
        $query .= ' WHERE p.`reference` = "'.pSQL($SKU).'" OR pa.`reference` = "'.pSQL($SKU).'" ';

        //		if (version_compare(_PS_VERSION_, '1.5', '>=') && $id_shop_default)
        //			$query .= ' AND p.`id_shop_default` = '.(int)$id_shop_default;

        $query .= ' GROUP BY id_product ORDER BY id_product DESC';

        $result = Db::getInstance()->getRow($query);

        if (!isset($result['id_product'])) {
            return false;
        }

        $product_item = new StdClass;
        $product_item->id_product = (int)$result['id_product'];
        $product_item->id_product_attribute = $result['id_product_attribute'];

        return ($product_item);
    }

    public static function getByReference($reference)
    {
        if (!Validate::isReference($reference)) {
            die(Tools::displayError(__FILE__.'  '.__LINE__));
        }

        $result = Db::getInstance()->getRow('
            SELECT `id_product`
            FROM `'._DB_PREFIX_.'product` p
            WHERE p.`reference` = \''.pSQL($reference).'\'');
        if (!isset($result['id_product'])) {
            return false;
        }

        return new self((int)$result['id_product']);
    }

    public static function updateProductDate($id_product)
    {
        $sql = 'UPDATE `'._DB_PREFIX_.'product` set `date_upd` = "'.pSQL(date('Y-m-d H:i:s')).'" WHERE `id_product` = '.(int)$id_product;

        $rq = Db::getInstance()->Execute($sql);

        return ($rq);
    }

    public static function oldest()
    {
        $sql = '
            SELECT MIN(`date_add`) as date_min FROM `'._DB_PREFIX_.'product`;';
        if (($rq = Db::getInstance()->ExecuteS($sql)) && is_array($rq)) {
            $result = array_shift($rq);

            return ($result['date_min']);
        } else {
            return ('Never');
        }
    }

    public function getAttributeCombinationsById($id_product_attribute, $id_lang, $groupByIdAttributeGroup = true)
    {
        if (method_exists('Combination', 'isFeatureActive') && !Combination::isFeatureActive()) {
            return array();
        }

        if (method_exists('Product', 'getAttributeCombinationsById')) {
            return (parent::getAttributeCombinationsById($id_product_attribute, $id_lang));
        }

        $sql = 'SELECT pa.*, ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` AS group_name, al.`name` AS attribute_name,
                                    a.`id_attribute`, pa.`unit_price_impact`
                            FROM `'._DB_PREFIX_.'product_attribute` pa
                            LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac ON pac.`id_product_attribute` = pa.`id_product_attribute`
                            LEFT JOIN `'._DB_PREFIX_.'attribute` a ON a.`id_attribute` = pac.`id_attribute`
                            LEFT JOIN `'._DB_PREFIX_.'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
                            LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int)$id_lang.')
                            LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int)$id_lang.')
                            WHERE pa.`id_product` = '.(int)$this->id.'
                            AND pa.`id_product_attribute` = '.(int)$id_product_attribute.'
                            GROUP BY pa.`id_product_attribute`, ag.`id_attribute_group`
                            ORDER BY pa.`id_product_attribute`';

        $res = Db::getInstance()->executeS($sql);

        return $res;
    }
}
