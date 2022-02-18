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

class PriceMinisterProfiles
{

    const CONF_TYPE = 'pm_profile';
    const MAIN_GROUP = 'profile';
    const PRICE_RULES_GROUP = 'price_rule';

    public static $table = PriceMinister::TABLE_PRICEMINISTER_CONFIGURATION;

    public static $profile_base = array('name' => null, 'model' => null,
        'price_rule' => array('type' => null,
            'rule' => array('from' => array(), 'to' => array(),
                'percent' => array(), 'value' => array())
        ));

    /* receives profile as it's obtained */
    public static function save($profile_id, $profile_name, $profile_model, $profile_price_rules, $profile_options)
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.self::$table)) {
            return null;
        }

        $profile_price_rules = is_array($profile_price_rules) ? $profile_price_rules : array();

        $field_non_multiple = 'N';
        $field_multiple = 'S';

        $inserts = array();
        $inserts[] = 'REPLACE INTO `'._DB_PREFIX_.self::$table.'`
                            (`conf_id`, `conf_type`, `field_group`, `field_name`, `field_idx`, `field_value`, `field_multiple`)
                             VALUES';

        $inserts[] = '('.pSQL($profile_id).', "'.
            pSQL(self::CONF_TYPE).'", "'.
            pSQL(self::MAIN_GROUP).'", "'.
            pSQL('name').'", '.
            pSQL(0).', "'.
            pSQL($profile_name).'", "'.
            pSQL($field_non_multiple).'"),';

        $inserts[] = '('.pSQL($profile_id).', "'.
            pSQL(self::CONF_TYPE).'", "'.
            pSQL(self::MAIN_GROUP).'", "'.
            pSQL('model').'", '.
            pSQL(0).', "'.
            pSQL($profile_model).'", "'.
            pSQL($field_non_multiple).'"),';

        if (isset($profile_price_rules['type'])) {
            $inserts[] = '('.pSQL($profile_id).', "'.
                pSQL(self::CONF_TYPE).'", "'.
                pSQL(self::PRICE_RULES_GROUP).'", "'.
                pSQL('type').'", '.
                pSQL(0).', "'.
                pSQL($profile_price_rules['type']).'", "'.
                pSQL($field_multiple).'"),';
        }

        if (isset($profile_price_rules['rule']) && isset($profile_price_rules['rule']['from'])) {
            foreach ($profile_price_rules['rule']['from'] as $key => $price_rule_from) {
                $from = isset($profile_price_rules['rule']['from']) && isset($profile_price_rules['rule']['from'][$key]) ? $profile_price_rules['rule']['from'][$key] : '';
                $to = isset($profile_price_rules['rule']['to']) && isset($profile_price_rules['rule']['to'][$key]) ? $profile_price_rules['rule']['to'][$key] : '';
                $percent = isset($profile_price_rules['rule']['percent']) && isset($profile_price_rules['rule']['percent'][$key]) ? $profile_price_rules['rule']['percent'][$key] : '';
                $value = isset($profile_price_rules['rule']['value']) && isset($profile_price_rules['rule']['value'][$key]) ? $profile_price_rules['rule']['value'][$key] : '';

                $inserts[] = '('.pSQL($profile_id).', "'.
                    pSQL(self::CONF_TYPE).'", "'.
                    pSQL(self::PRICE_RULES_GROUP).'", "'.
                    pSQL('from').'", '.
                    pSQL($key).', "'.
                    pSQL($from).'", "'.
                    pSQL($field_multiple).'"),';
                $inserts[] = '('.pSQL($profile_id).', "'.
                    pSQL(self::CONF_TYPE).'", "'.
                    pSQL(self::PRICE_RULES_GROUP).'", "'.
                    pSQL('to').'", '.
                    pSQL($key).', "'.
                    pSQL($to).'", "'.
                    pSQL($field_multiple).'"),';
                $inserts[] = '('.pSQL($profile_id).', "'.
                    pSQL(self::CONF_TYPE).'", "'.
                    pSQL(self::PRICE_RULES_GROUP).'", "'.
                    pSQL('percent').'", '.
                    pSQL($key).', "'.
                    pSQL($percent).'", "'.
                    pSQL($field_multiple).'"),';
                $inserts[] = '('.pSQL($profile_id).', "'.
                    pSQL(self::CONF_TYPE).'", "'.
                    pSQL(self::PRICE_RULES_GROUP).'", "'.
                    pSQL('value').'", '.
                    pSQL($key).', "'.
                    pSQL($value).'", "'.
                    pSQL($field_multiple).'"),';
            }
        }

        foreach ($profile_options as $key => $option) {
            $inserts[] = '('.pSQL($profile_id).', "'.
                pSQL(self::CONF_TYPE).'", "'.
                pSQL(self::MAIN_GROUP).'", "'.
                pSQL($key).'", '.
                pSQL(0).', "'.
                pSQL($option).'", "'.
                pSQL($field_non_multiple).'"),';
        }

        return (Db::getInstance()->Execute(rtrim(implode(' ', $inserts), ','), false));
    }

    public static function getAll()
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.self::$table)) {
            return null;
        }

        $list = array();

        static $result;
        if (!is_array($result)) {
            $result = Db::getInstance()->ExecuteS(
                'SELECT DISTINCT *
                FROM `'._DB_PREFIX_.self::$table.'`
                WHERE `conf_type` = "'.pSQL(self::CONF_TYPE).'"
                ORDER by `conf_id`; ',
                true,
                false
            );
        }

        if (!is_array($result)) {
            $result = [];
        }

        foreach ($result as $row) {
            self::addElementToProfilesArray($list, $row);
        }

        return $list;
    }

    private static function addElementToProfilesArray(&$profiles, $row)
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.self::$table)) {
            return null;
        }

        $profiles_id = $row['conf_id'];
        $group = $row['field_group'];
        $field_name = $row['field_name'];
        $field_idx = $row['field_idx'];
        $field_value = $row['field_value'];
        $field_multiple = $row['field_multiple'];

        if (!isset($profiles[$profiles_id])) {
            $profiles[$profiles_id] = self::$profile_base;
        }

        switch ($group) {
            case self::MAIN_GROUP:
                $profiles[$profiles_id][$field_name] = $field_value;
                break;
            case self::PRICE_RULES_GROUP:

                if (!isset($profiles[$profiles_id][$group])) {
                    $profiles[$profiles_id][$group] = array();
                }
                if ($field_name == 'type') {
                    $profiles[$profiles_id][$group][$field_name] = $field_value;
                } elseif (Tools::strtoupper($field_multiple) == 'S') {
                    if (!isset($profiles[$profiles_id][$group]['rule'])) {
                        $profiles[$profiles_id][$group]['rule'] = array();
                    }
                    if (!isset($profiles[$profiles_id][$group]['rule'][$field_name])) {
                        $profiles[$profiles_id][$group]['rule'][$field_name] = array();
                    }
                    //used for multiple selection elements
                    $profiles[$profiles_id][$group]['rule'][$field_name][$field_idx] = $field_value;
                    asort($profiles[$profiles_id][$group]['rule'][$field_name]);
                }
        }
    }

    public static function getAllProfilesNames()
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.self::$table)) {
            return null;
        }

        $result = Db::getInstance()->ExecuteS('SELECT `conf_id`, `field_value`  FROM `'
            ._DB_PREFIX_.self::$table.
            '`  WHERE `field_group` = "'.pSQL(self::MAIN_GROUP).'" AND `field_name` = "name"
                    ORDER by `conf_id`; ', true, false);

        return $result;
    }

    public static function delete($conf_id)
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.self::$table)) {
            return null;
        }

        return (Db::getInstance()->Execute('DELETE `'._DB_PREFIX_.self::$table.'`
             WHERE `conf_type` = "'.pSQL(self::CONF_TYPE).'" AND `conf_id`="'.pSQL($conf_id).'" ; '));
    }

    public static function deleteAll()
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.self::$table)) {
            return null;
        }

        return (Db::getInstance()->Execute(' DELETE FROM `'._DB_PREFIX_.self::$table.
            '` WHERE `conf_type` = "'.pSQL(self::CONF_TYPE).'" ; '));
    }
}