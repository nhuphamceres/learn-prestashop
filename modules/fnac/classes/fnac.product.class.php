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
 * ...........................................................................
 *
 * @author    Alexandre D. & Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  contact@common-services.com
 */

class FNAC_Product extends Product
{

    public static function getProductById($id_product)
    {
        $sql = 'SELECT `reference` FROM `'._DB_PREFIX_.'product` p where id_product = "'.(int)$id_product.'" ;';

        $rq = Db::getInstance()->ExecuteS($sql);

        return ($rq[0]['reference']);
    }

    public static function getByReference($reference)
    {
        // get combination first
        $sql = 'SELECT `id_product` FROM `'._DB_PREFIX_.'product_attribute` p where reference = "'.pSQL($reference).'" ;';

        $rq = Db::getInstance()->ExecuteS($sql);

        if (!isset($rq[0]) || !$rq[0]['id_product']) {
            $sql = 'SELECT `id_product` FROM `'._DB_PREFIX_.'product` p where reference = "'.pSQL($reference).'" ;';

            $rq = Db::getInstance()->ExecuteS($sql);

            if (!isset($rq[0]) || !$rq[0]['id_product']) {
                return false;
            }
        }

        return new self($rq[0]['id_product']);
    }

    public static function productsBetween($id_lang, $date1, $date2, $id_category = false, $only_active = false)
    {
        $date1 = date('Y-m-d', strtotime($date1)).' 00:00';
        $date2 = date('Y-m-d', strtotime($date2)).' 23:59';

        $sql = 'SELECT DISTINCT p.id_product
                  FROM `'._DB_PREFIX_.'product` p '.
            ($id_category ? 'LEFT JOIN `'._DB_PREFIX_.'category_product` c ON (c.`id_product` = p.`id_product`)' : '').' WHERE 1 '.
            ($id_category ? ' AND c.`id_category` = '.(int)$id_category : '').
            ($only_active ? ' AND p.`active` = 1' : '').'
                  AND (
                         p.date_upd between "'.pSQL($date1).'" and "'.pSQL($date2).'"
                      OR p.date_add between "'.pSQL($date1).'" and "'.pSQL($date2).'"
                  )
                  GROUP by p.id_product ORDER BY p.date_add desc ';

        $rq = Db::getInstance()->ExecuteS($sql);

        return ($rq);
    }

    public static function oldest()
    {
        $sql = '
            SELECT MIN(date_add) as date_min FROM `'._DB_PREFIX_.'product`;';
        if (($rq = Db::getInstance()->ExecuteS($sql)) && is_array($rq)) {
            $result = array_shift($rq);

            return (preg_replace('/ .*/', '', $result['date_min']));
        } else {
            return (false);
        }
    }

    /* Options produits (actif, stock etc..) */
    public static function getProductOptions($id_product, $id_lang)
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'fnac_product_option` p where id_product = '.(int)$id_product;

        $rq = Db::getInstance()->ExecuteS($sql);

        if ($rq) {
            return (array_shift($rq));
        } else {
            return (array(
                'force' => 0, 'nopexport' => 0, 'noqexport' => 0, 'latency' => 0, 'disable' => 0, 'price' => '', 'asin1' => '', 'asin2' => '', 'asin3' => '', 'text' => '', 'text_es' => '', 'text_pt' => '', 'shipping' => '', 'time_to_ship' => ''
            ));
        }
    }

    public static function setProductOptions($id_product, $id_lang, $options)
    {
        $fields = array();
        $query = Db::getInstance()->ExecuteS('SHOW COLUMNS FROM `'._DB_PREFIX_.'fnac_product_option`');
        foreach ($query as $row) {
            $fields[$row['Field']] = 1;
        }

        if (!isset($fields['text_pt'])) {
            $sql = 'ALTER TABLE `'._DB_PREFIX_.'fnac_product_option` ADD `text_pt` varchar(128) DEFAULT NULL';
            if (!Db::getInstance()->execute($sql)) {
                echo 'Unable to get alter table [fnac_product_option] to add: text_pt.<br />';
                die;
            }
        }

        if (!isset($fields['time_to_ship'])) {
            $sql = 'ALTER TABLE `'._DB_PREFIX_.'fnac_product_option` ADD `time_to_ship` INT NULL DEFAULT NULL';
            if (!Db::getInstance()->execute($sql)) {
                echo 'Unable to get alter table [fnac_product_option] to add: time_to_ship.<br />';
                die;
            }
        }

        $sql = 'REPLACE INTO `'._DB_PREFIX_.'fnac_product_option` (`id_product`, `id_lang`, `force`, `price`, `text`,  `text_es`, `text_pt`, `disable`, `time_to_ship`) VALUES ('.
            pSQL($id_product).', '.
            pSQL($id_lang).', '. // TODO Might be necessary to remove the id_lang, c.f.: getProductOptions()
            (int)$options['force'].', '.
            (float)$options['price'].', "'.
            pSQL($options['text']).'", "'.
            pSQL($options['text_es']).'", "'.
            pSQL($options['text_pt']).'", '.
            (int)$options['disable'].',
            '.pSQL($options['time_to_ship']).');';

        $rq = Db::getInstance()->Execute($sql);

        $sql = 'UPDATE `'._DB_PREFIX_.'product` set date_upd="'.pSQL(date('Y-m-d H:i:s')).'" where id_product='.(int)$id_product.' ;';
        Db::getInstance()->Execute($sql);

        return ($rq);
    }

    /* Propagate Text */
    public static function propagateProductOptionsText($id_product, $id_lang, $id_category, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'fnac_product_option` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`text` = "'.pSQL($option['text']).'", mpo.`text_es` = "'.pSQL($option['text_es']).'", mpo.`text_pt` = "'.pSQL($option['text_pt']).'"
            WHERE p.id_product = mpo.id_product and p.id_category_default = '.(int)$id_category.' and id_lang = '.(int)$id_lang;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = false && $pass;
        }

        $pass = self::updateProductDate($id_product) && $pass;

        return ($pass);
    }

    public static function propagateToShopProductOptionsText($id_product, $id_lang, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'fnac_product_option` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`text` = "'.pSQL($option['text']).'", mpo.`text_es` = "'.pSQL($option['text_es']).'", mpo.`text_pt` = "'.pSQL($option['text_pt']).'"
            WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = $pass && false;
        }

        $pass = self::updateProductDate($id_product) && $pass;


        return ($pass);
    }

    public static function propagateToManufacturerProductOptionsText($id_product, $id_lang, $id_manufacturer, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'fnac_product_option` mpo, `'._DB_PREFIX_.'product` p, `'._DB_PREFIX_.'manufacturer` m SET
            mpo.`text` = "'.pSQL($option['text']).'", mpo.`text_es` = "'.pSQL($option['text_es']).'", mpo.`text_pt` = "'.pSQL($option['text_pt']).'"
            WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang.' and p.`id_manufacturer` = m.`id_manufacturer` AND p.`id_manufacturer` ='.(int)$id_manufacturer;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = $pass && false;
        }

        $pass = self::updateProductDate($id_product) && $pass;


        return ($pass);
    }

    /* Propagate Disable */
    public static function propagateProductOptionsDisable($id_product, $id_lang, $id_category, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'fnac_product_option` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`disable` = '.(int)$option['disable'].'
            WHERE p.id_product = mpo.id_product and p.id_category_default = '.(int)$id_category.' and id_lang = '.(int)$id_lang;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = false && $pass;
        }

        $pass = self::updateProductDate($id_product) && $pass;

        return ($pass);
    }

    public static function propagateToShopProductOptionsDisable($id_product, $id_lang, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'fnac_product_option` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`disable` = '.(int)$option['disable'].'
            WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = $pass && false;
        }

        $pass = self::updateProductDate($id_product) && $pass;

        return ($pass);
    }

    public static function propagateToManufacturerProductOptionsDisable($id_product, $id_lang, $id_manufacturer, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'fnac_product_option` mpo, `'._DB_PREFIX_.'product` p, `'._DB_PREFIX_.'manufacturer` m SET
            mpo.`disable` = '.(int)$option['disable'].'
            WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang.' and p.`id_manufacturer` = m.`id_manufacturer` AND p.`id_manufacturer` ='.(int)$id_manufacturer;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = $pass && false;
        }

        $pass = self::updateProductDate($id_product) && $pass;

        return ($pass);
    }

    /* Propagate Force */
    public static function propagateProductOptionsForce($id_product, $id_lang, $id_category, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'fnac_product_option` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`force` = '.(int)$option['force'].'
            WHERE p.id_product = mpo.id_product and p.id_category_default = '.(int)$id_category.' and id_lang = '.(int)$id_lang;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = false && $pass;
        }

        $pass = self::updateProductDate($id_product) && $pass;

        return ($pass);
    }

    public static function propagateToShopProductOptionsForce($id_product, $id_lang, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'fnac_product_option` mpo, `'._DB_PREFIX_.'product` p SET
            mpo.`force` = '.(int)$option['force'].'
            WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = $pass && false;
        }

        $pass = self::updateProductDate($id_product) && $pass;

        return ($pass);
    }

    public static function propagateToManufacturerProductOptionsForce($id_product, $id_lang, $id_manufacturer, $option)
    {
        $pass = true;

        $pass = self::initProductOptions($id_lang) && $pass;

        $sql = 'UPDATE `'._DB_PREFIX_.'fnac_product_option` mpo, `'._DB_PREFIX_.'product` p, `'._DB_PREFIX_.'manufacturer` m SET
              mpo.`force` = '.(int)$option['force'].'
              WHERE p.id_product = mpo.id_product and id_lang = '.(int)$id_lang.' and p.`id_manufacturer` = m.`id_manufacturer` and p.`id_manufacturer` = '.(int)$id_manufacturer;

        if (!Db::getInstance()->Execute($sql)) {
            $pass = $pass && false;
        }

        $pass = self::updateProductDate($id_product) && $pass;

        return ($pass);
    }

    public static function updateProductDate($id_product)
    {
        $sql = '
          UPDATE `'._DB_PREFIX_.'product` set date_upd = "'.pSQL(date('Y-m-d H:i:s')).'" where id_product='.(int)$id_product;

        $rq = Db::getInstance()->Execute($sql);

        return ($rq);
    }

    public static function initProductOptions($id_lang)
    {
        $pass = true;
        $products = Db::getInstance()->ExecuteS('SELECT id_product from `'._DB_PREFIX_.'product`');

        foreach ($products as $product) {
            $sql = 'INSERT IGNORE `'._DB_PREFIX_.'fnac_product_option`
            (`id_product`,`id_lang`) values (
            '.(int)$product['id_product'].', '.(int)$id_lang.')';

            if (!Db::getInstance()->Execute($sql)) {
                $pass = $pass && false;
            }
        }

        return ($pass);
    }
}
