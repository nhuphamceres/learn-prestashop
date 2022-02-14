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

class PriceMinisterModels
{
    const MAIN_GROUP = 'model';
    public static $pm_groups = array('product', 'advert', 'media', 'campaigns');
    public static $model_base = array('name' => '', 'product_type' => '', 'product' => array(),
        'advert' => array(), 'media' => array(), 'campaigns' => array(),);

    public static function save($model_id, $field_group, $field_name, $field_idx, $field_value, $field_multiple)
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MODELS)) {
            return null;
        }

        return (Db::getInstance()->Execute('REPLACE INTO `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MODELS.'`
            (`model_id`, `field_group`, `field_name`, `field_idx`, `field_value`, `field_multiple`) 
             VALUES ("'.pSQL($model_id).'", "'.pSQL($field_group).'", "'.pSQL($field_name).
            '", "'.pSQL($field_idx).'", "'.pSQL($field_value).'", "'.pSQL($field_multiple).'"); ', false));
    }

    public static function getAll()
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MODELS)) {
            return null;
        }

        $list = array();
        $result = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MODELS.'`  ORDER by `model_id`; ', true, false);

        foreach ($result as $row) {
            self::addElementToModelsArray($list, $row);
        }

        return $list;
    }

    private static function addElementToModelsArray(&$models, $row)
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MODELS)) {
            return null;
        }

        $model_id = $row['model_id'];
        $group = $row['field_group'];
        $field_name = $row['field_name'];
        $field_idx = $row['field_idx'];
        $field_value = $row['field_value'];
        $field_multiple = $row['field_multiple'];

        if (!isset($models[$model_id])) {
            $models[$model_id] = self::$model_base;
        }

        switch ($group) {
            case self::MAIN_GROUP:
                $models[$model_id][$field_name] = $field_value;
                break;
            case 'campaigns':

                if (!isset($models[$model_id][$group])) {
                    $models[$model_id][$group] = array();
                }

                if (Tools::strtoupper($field_multiple) == 'S') {
                    if (!isset($models[$model_id][$group][$field_name])) {
                        $models[$model_id][$group][$field_name] = array();
                    }

                    $models[$model_id][$group][$field_name][$field_idx] = $field_value;
                } else {
                    $models[$model_id][$group][$field_name] = $field_value;
                }
                break;
            default:

                if (!isset($models[$model_id][$group])) {
                    $models[$model_id][$group] = array();
                }

                if (Tools::strtoupper($field_multiple) == 'S') {
                    if (!isset($models[$model_id][$group][$field_name])) {
                        $models[$model_id][$group][$field_name] = array();
                    }
                    //used for multiple selection elements
                    $models[$model_id][$group][$field_name][$field_value] = true;
                } else {
                    $models[$model_id][$group][$field_name] = $field_value;
                }
        }
    }

    public static function getAllModelsNames()
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MODELS)) {
            return null;
        }

        $result = Db::getInstance()->ExecuteS('SELECT `model_id`, `field_value`  FROM `'
            ._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MODELS.
            '`  WHERE `field_group` = "model" AND `field_name` = "name"
                    ORDER by `model_id`; ', true, false);

        return $result;
    }

    public static function getXMLModelsFileName()
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MODELS)) {
            return null;
        }

        $result = array();

        $product_types = Db::getInstance()->ExecuteS(
            'SELECT DISTINCT `field_value`  FROM
			`'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MODELS.'`
			WHERE `field_name` = "product_type"
            ORDER BY `model_id`',
            true,
            false
        );

        if (is_array($product_types) && count($product_types)) {
            foreach ($product_types as $product_type) {
                $result[] = $product_type['field_value'];
            }
        }

        return $result;
    }

    public static function delete($model_id)
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MODELS)) {
            return null;
        }

        return (Db::getInstance()->Execute('DELETE `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MODELS.'`
             WHERE `model_id`="'.pSQL($model_id).'" ; '));
    }

    public static function deleteAll()
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MODELS)) {
            return null;
        }

        return (Db::getInstance()->Execute(' TRUNCATE TABLE `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MODELS.'` ; '));
    }
}