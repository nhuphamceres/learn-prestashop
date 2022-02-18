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

class PriceMinisterMappings
{

    const ATTRIBUTE_TYPE = 1;
    const FEATURE_TYPE = 2;

    public static function getMappingTable($type)
    {
        $list = array();

        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS)) {
            return $list;
        }

        $result = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS.'` WHERE `type` = "'.pSQL($type).'"; ');

        foreach ($result as $rec) {
            if ($rec['id_prestashop']) {
                if (!isset($list[$rec['id_prestashop']])) {
                    $list[$rec['id_prestashop']] = array();
                }
                $list[$rec['id_prestashop']][$rec['id_pm_mapping']] = $rec['id_priceminister'];
            }
        }

        return $list;
    }

    /* BULK OPERATIONS */
    public static function getAll($type)
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS)) {
            return null;
        }

        $result = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS.
            '` WHERE `type` = "'.pSQL($type).
            '" ORDER by `id_pm_mapping` ; ');

        return $result;
    }

    public static function deleteAll()
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS)) {
            return null;
        }

        $ok = self::deleteAllDetails();

        return ($ok && Db::getInstance()->Execute(' TRUNCATE TABLE `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS.'` ; '));
    }

    public static function deleteAllDetails()
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS_DET)) {
            return null;
        }

        return (Db::getInstance()->Execute(' TRUNCATE TABLE `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS_DET.'` ; '));
    }

    /* PARENT OPERATIONS */
    public static function add($id_prestashop, $id_priceminister, $type, $default_value = null)
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS)) {
            return null;
        }

        $result = (Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS.'`
            (`id_prestashop`, `id_priceminister`, `type`, `default_value`) 
            VALUES ("'.pSQL($id_prestashop).'", "'.
            pSQL($id_priceminister).'", "'.
            pSQL($type).'", "'.
            pSQL($default_value).'"); '));

        return Db::getInstance()->Insert_ID();
    }

    public static function updateDefaultValue($id_prestashop, $id_priceminister, $type, $default_value)
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS)) {
            return null;
        }

        return (Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS.'`
            (`id_prestashop`, `id_priceminister`, `type`, `default_value`) 
            VALUES ("'.pSQL($id_prestashop).'", "'.pSQL($id_priceminister).'", "'.pSQL($type).'", "'.pSQL($default_value).'"); '));
    }

    public static function get($id_prestashop, $id_priceminister, $type)
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS)) {
            return null;
        }

        $result = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS.
            '` WHERE `id_prestashop`="'.pSQL($id_prestashop).
            '"  AND `id_priceminister`="'.pSQL($id_priceminister).
            '" AND `type` = "'.pSQL($type).'"; ');

        return array($result);
    }

    public static function delete($id_prestashop, $id_priceminister, $type)
    {
        return (Db::getInstance()->Execute('DELETE `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS.'`
             WHERE `id_prestashop`="'.pSQL($id_prestashop).'"  AND `id_priceminister`="'.pSQL($id_priceminister).'"  AND `type` = "'.pSQL($type).'"; '));
    }

    /* CHILDREN OPERATIONS */
    public static function addDetail($id_pm_mapping, $ps_value, $pm_value)
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS)) {
            return null;
        }

        return (Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS_DET.'`
            (`id_pm_mapping`, `ps_value`, `pm_value`) 
            VALUES ("'.pSQL($id_pm_mapping).'", "'.pSQL($ps_value).'", "'.pSQL($pm_value).'"); '));
    }

    public static function getDetails($id_pm_mapping)
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS_DET)) {
            return null;
        }

        $result = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_MAPPINGS_DET.
            '` WHERE `id_pm_mapping`="'.pSQL($id_pm_mapping).'"; ');

        return array($result);
    }
}