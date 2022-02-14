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

class PriceMinisterProfiles2Categories
{

    const CONF_TYPE = 'pm_profile_to_category';
    const MAIN_GROUP = 'profile_to_category';

    public static $table = PriceMinister::TABLE_PRICEMINISTER_CONFIGURATION;

    /* receives profile as it's obtained */
    public static function save($pm_profiles2categories)
    {
        if (!is_array($pm_profiles2categories) || !count($pm_profiles2categories)) {
            return false;
        }

        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.self::$table)) {
            return null;
        }

        $field_non_multiple = 'N';

        $inserts = array();

        $inserts[] = 'REPLACE INTO `'._DB_PREFIX_.self::$table.'`
				(`conf_id`, `conf_type`, `field_group`, `field_name`, `field_idx`, `field_value`, `field_multiple`)
				 VALUES';

        foreach ($pm_profiles2categories as $category => $pm_profile) {
            $inserts[] = '('.pSQL($category).', "'.
                pSQL(self::CONF_TYPE).'", "'.
                pSQL(self::MAIN_GROUP).'", "'.
                pSQL('profile').'", '.
                pSQL(0).', "'.
                pSQL($pm_profile).'", "'.
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
        $result = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.self::$table.
            '` WHERE `conf_type` = "'.pSQL(self::CONF_TYPE).'" ORDER by `conf_id`; ', true, false);

        foreach ($result as $row) {
            $list[$row['conf_id']] = $row['field_value'];
        }

        return $list;
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
