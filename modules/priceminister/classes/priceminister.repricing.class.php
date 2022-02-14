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
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Common-Services Co., Ltd.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 * ...........................................................................
 *
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.priceminister@common-services.com
 */

class PriceMinisterRepricing
{

    const REPRICING_BASED_ON_WHOLESALE_PRICE = 1;
    const REPRICING_BASED_ON_REGULAR_PRICE = 2;
    public $id_repricing;
    public $name;
    public $active;
    public $aggressiveness;
    public $base;
    public $limit;
    public $delta;

    public function __construct($id_repricing = null)
    {
        if ($id_repricing) {
            $strategie = Db::getInstance()->getRow(
                'SELECT *
                FROM `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_REPRICING.'`
                WHERE `id_repricing` = '.(int)$id_repricing
            );

            if (is_array($strategie) && count($strategie)) {
                foreach ($strategie as $item => $value) {
                    $this->{$item} = $value;
                }

                $delta = explode(';', $this->delta);
                $this->delta = array_combine(array('min', 'max'), $delta);
            }
        }
    }

    public static function getAll($active = true)
    {
        static $cache_strategies;

        if (!$cache_strategies || !is_array($cache_strategies) || !count($cache_strategies)) {
            if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_REPRICING)) {
                return null;
            }

            $result = Db::getInstance()->executeS(
                'SELECT *
                FROM `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_REPRICING.'`
                -- WHERE `active` = '.(int)$active
            );

            if (is_array($result) && count($result)) {
                foreach ($result as $key => $val) {
                    $delta = explode(';', $val['delta']);
                    $result[$key]['delta'] = array_combine(array('min', 'max'), $delta);
                }
            }

            $cache_strategies = array();
            foreach ($result as $v) {
                $cache_strategies[(int)$v['id_repricing']] = $v;
            }
        }

        return $cache_strategies;
    }

    public static function save($strategies = null)
    {
        if (is_array($strategies) && count($strategies)) {
            $sql = '';

            foreach ($strategies as $strategie) {
                $sql .= '
                    INSERT INTO `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_REPRICING.'` VALUES (
                        NULL,
                        "'.pSQL($strategie['name']).'",
                        '.pSQL($strategie['active']).',
                        '.(int)$strategie['aggressiveness'].',
                        '.(int)$strategie['base'].',
                        '.(int)$strategie['limit'].',
                        "'.pSQL(implode(';', $strategie['delta'])).'"
                    );
                    ';
            }

            return (bool)Db::getInstance()->execute($sql);
        }

        return false;
    }

    public static function deleteAll()
    {
        return (bool)Db::getInstance()->execute('TRUNCATE TABLE `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_REPRICING.'`');
    }

    public static function getCategoryForIdProduct($id_product)
    {
        $category_set = array();
        $categories = PriceMinisterCategories::getAll();

        if (!$categories || !count($categories)) {
            return (false);
        }

        $list = implode(',', $categories);
        $sql = 'SELECT `id_category` FROM `'._DB_PREFIX_.'category_product` WHERE `id_product` = '.(int)$id_product.' AND `id_category` IN('.$list.')';

        if (!$results = Db::getInstance()->executeS($sql)) {
            return (false);
        }

        if (is_array($results) && count($results)) {
            foreach ($results as $result) {
                if (array_key_exists('id_category', $result)) {
                    $category_set[] = $result['id_category'];
                }
            }
        }

        return ($category_set);
    }

    public static function getProductStrategy($id_product, $id_product_attribute = null, $id_lang = null)
    {
        if ($id_product_attribute) {
            $attribute_sql = ' AND `id_product_attribute`='.(int)$id_product_attribute;
        } else {
            $attribute_sql = ' AND (`id_product_attribute` IS NULL OR `id_product_attribute` = 0)';
        }

        if (!$id_lang) {
            $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        }

        $result = Db::getInstance()->executeS(
            'SELECT *
            FROM `'._DB_PREFIX_.'priceminister_strategy`
            WHERE `id_product` = '.(int)$id_product.'
            AND `id_lang` = '.(int)$id_lang.$attribute_sql.'
            LIMIT 1'
        );

        return ($result);
    }

    public static function saveRepricing($item)
    {
        if (!$item || !is_array($item) || !count($item) || !isset($item['id_product'])) {
            return false;
        }

        return Db::getInstance()->execute(
            'REPLACE INTO `'._DB_PREFIX_.'priceminister_strategy` VALUES (
                NULL,
                '.(int)$item['id_product'].',
                '.(int)$item['id_product_attribute'].',
                '.(int)$item['id_lang'].',
                '.(float)$item['minimum_price'].',
                '.(float)$item['actual_price'].',
                '.(float)$item['target_price'].',
                '.(float)$item['gap'].'
            )'
        );
    }

    public function isLoadedObject()
    {
        return $this->id_repricing && $this->name && $this->aggressiveness &&
            $this->base && $this->limit && is_array($this->delta) && isset($this->delta['min']) &&
            isset($this->delta['max']) && $this->delta['min'] && $this->delta['max'];
    }
}
