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

class AmazonTaxes
{
    public static function getPtcFile()
    {
        return(_PS_MODULE_DIR_.'/amazon/settings/taxes/ptc.csv');
    }

    public static function createTable()
    {
        $pass = true;
        $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_TAXES.'` (
                  `ptc` varchar(32),
                  `lang` varchar(8),
                  `description` varchar(256) DEFAULT NULL,
                  PRIMARY KEY `ptc_index` (`ptc`, `lang`), KEY `lang_index` (`lang`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';

        if (!Db::getInstance()->execute($sql)) {
            $pass = false;
        }
        return($pass);
    }

    public static function populatePtc()
    {
        $pass = true;

        if (file_exists(self::getPtcFile()) && is_readable(self::getPtcFile())) {
            if (!($csvfh = fopen(self::getPtcFile(), 'r'))) {
                return (false);
            }
            while ($data = fgetcsv($csvfh, 1024, ';')) {
                if (is_array($data) && count($data) < 3) {
                    continue;
                }
                if (is_array($data) && count($data)) {
                    $ptc = $data[0];
                    $lang = $data[1];
                    $description = $data[2];


                    $sql = 'REPLACE INTO `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_TAXES.'` (`ptc`, `lang`, `description`) VALUES ("'.pSQL($ptc).'", "'.pSQL($lang).'", "'.pSQL($description).'")';
                    $pass = $pass && Db::getInstance()->execute($sql);
                }
            }
        }
        return($pass);
    }
    public static function getPtcList($lang)
    {
        if (AmazonTools::tableExists(_DB_PREFIX_.Amazon::TABLE_MARKETPLACE_TAXES)) {
            $ptcs = array();

            $sql = 'SELECT * FROM `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_TAXES.'` WHERE `lang` = "'.pSQL($lang).'"';
            $results = Db::getInstance()->executeS($sql);

            if (!is_array($results) || !count($results)) {
                $sql = 'SELECT * FROM `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_TAXES.'` WHERE `lang` = "en"';
                $results = Db::getInstance()->executeS($sql);
            }

            if (is_array($results) && count($results)) {
                foreach ($results as $result) {
                    $ptc = $result['ptc'];
                    $lang = $result['lang'];
                    $description = $result['description'];
                    $ptcs[$ptc] = array('ptc' => $ptc, 'lang' => $lang, 'description' => $description);
                }
                return($ptcs);
            }
        }
        return(false);
    }

    /**
     * Get tax rules group ca apply to selected country
     * @param int $id_country
     * @param bool $only_active
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     */
    public static function getTaxRuleGroupsByCountry($id_country, $only_active = true)
    {
        $shopJoin = '';
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $shopJoin = Shop::addSqlAssociation('tax_rules_group', 'g');
        }

    	$condition = array();
        // PS1.6.0.9: ps_tax_rules_group only has 3 columns: id_tax_rules_group, name, active
        if (version_compare(_PS_VERSION_, '1.6.1.0', '>=')) {
            $condition[] = 'g.deleted = 0';
        }
	    if ($only_active) {
	    	$condition[] = 'g.`active` = 1';
	    }
	    $where = implode(' AND ', $condition);

        $sql = 'SELECT DISTINCT g.id_tax_rules_group, g.name, g.active
                FROM `' . _DB_PREFIX_ . 'tax_rules_group` g' . $shopJoin .
                ' JOIN `' . _DB_PREFIX_ . 'tax_rule` tr ON(g.id_tax_rules_group = tr.id_tax_rules_group)' .
                ' WHERE ' . $where .
                ' AND (tr.id_country = 0 OR tr.id_country = ' . (int)$id_country . ')' .
                ' ORDER BY g.name ASC';

        return Db::getInstance()->executeS($sql);
    }
}
