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
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   CDiscount
 * Support by mail:  support.cdiscount@common-services.com
 */

require_once(_PS_MODULE_DIR_ . 'cdiscount/classes/cdiscount.order_info.class.php');
require_once(_PS_MODULE_DIR_ . 'cdiscount/classes/cdiscount.tools_r.class.php');

class CDiscountDBManager
{
    const OLD_TABLE_CDISCOUNT_ORDERS = 'cdiscount_orders';
    const TABLE_MARKETPLACE_ORDERS = 'marketplace_orders';

    /** @var Cdiscount */
    public $module;

    /** @var Context */
    public $context;

    protected $errors = array();

    public function __construct($module, $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    public function addMarketplaceTables()
    {
        return $this->migrateOrdersTable();
    }

    /**
     * todo: Be careful of outdated CommonConfiguration on multiple modules (not exist of getIdByName())
     * @param $force
     */
    public function migrateData($force)
    {
        if ($force || (
                ($this->module->ps16x || $this->module->ps17x) && (
                    !CDiscountConfiguration::getIdByName('CDISCOUNT_MODELS_4-4') ||
                    !CDiscountConfiguration::getIdByName('CDISCOUNT_SPECIFIC_FIELDS_4-4') ||
                    !CDiscountConfiguration::getIdByName('CDISCOUNT_PROFILES_4-4')
                )
            )
        ) {
            $this->migrateModel_4_4();
        }
    }

    // todo: Keep old table for compatible with Amazon, remove later.
    protected function migrateOrdersTable()
    {
        return $this->addTableMarketplaceOrders()
            && $this->moveOrdersData();
//            && $this->removeOldOrdersTable();
    }

    protected function addTableMarketplaceOrders()
    {
        $mpOrderTbl = _DB_PREFIX_ . self::TABLE_MARKETPLACE_ORDERS;
        if (!CommonTools::tableExists($mpOrderTbl, false)) {
            // Same as Amazon
            $sql = 'CREATE TABLE IF NOT EXISTS `' . $mpOrderTbl . '` (
                        `id_order` INT NOT NULL ,
                        `mp_order_id` VARCHAR( 32 ) NOT NULL,
                        `mp_status` INT NOT NULL DEFAULT 0,
                        `channel` VARCHAR( 16 ) NULL,
                        `channel_status` VARCHAR( 24 ) NULL DEFAULT NULL,
                        `marketplace_id` VARCHAR( 16 ) NULL DEFAULT NULL,
                        `buyer_name` VARCHAR( 32 ) NULL DEFAULT NULL,
                        `sales_channel` VARCHAR( 32 ) NULL DEFAULT NULL,
                        `order_channel` VARCHAR( 32 ) NULL DEFAULT NULL,
                        `ship_service_level` VARCHAR( 32 ) NULL DEFAULT NULL,
                        `ship_category` VARCHAR( 16 ) NULL DEFAULT NULL,
                        `is_prime` BOOL NOT NULL DEFAULT 0,
                        `is_premium` BOOL NOT NULL DEFAULT 0,
                        `is_business` BOOL NOT NULL DEFAULT 0,
                        `earliest_ship_date` datetime DEFAULT NULL,
                        `latest_ship_date` datetime DEFAULT NULL,
                        `earliest_delivery_date` datetime DEFAULT NULL,
                        `latest_delivery_date` datetime DEFAULT NULL,
                        `shipping_services` VARCHAR(255) NULL,
                        `cd_channel_id` INT NULL DEFAULT 1,
                        `cd_channel_name` VARCHAR( 32 ) NULL DEFAULT NULL,
                        `clogistique` BOOLEAN DEFAULT 0,
                        PRIMARY KEY (`id_order`) ,
                        KEY (`mp_order_id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';

            if (!Db::getInstance()->execute($sql)) {
                $this->pushError(sprintf('Failed to create table: %s', $mpOrderTbl), $sql, Db::getInstance()->getMsgError());
                return false;
            }

            return true;
        } else {
            $alterSql = array();
            $alterStatus = true;
            if (!CDiscountToolsR::fieldExistsR($mpOrderTbl, 'cd_channel_id', false)) {  // Refresh cache once
                $alterSql[] = "ALTER TABLE `$mpOrderTbl` ADD `cd_channel_id` INT NULL DEFAULT 1";
            }
            if (!CDiscountToolsR::fieldExistsR($mpOrderTbl, 'cd_channel_name')) {
                $alterSql[] = "ALTER TABLE `$mpOrderTbl` ADD `cd_channel_name` VARCHAR( 32 ) NULL DEFAULT NULL";
            }
            if (!CDiscountToolsR::fieldExistsR($mpOrderTbl, 'clogistique')) {
                $alterSql[] = "ALTER TABLE `$mpOrderTbl` ADD `clogistique` BOOLEAN DEFAULT 0";
            }
            foreach ($alterSql as $sql) {
                if (!Db::getInstance()->execute($sql)) {
                    $this->pushError('Cannot alter orders table', $sql, Db::getInstance()->getMsgError());
                    $alterStatus = false;
                }
            }

            return $alterStatus;
        }
    }

    protected function moveOrdersData()
    {
        $legacyTable = _DB_PREFIX_ . self::OLD_TABLE_CDISCOUNT_ORDERS;
        if (CommonTools::tableExists($legacyTable, false)) {
            $orders = Db::getInstance()->executeS('SELECT * FROM `' . $legacyTable . '`');
            if ($orders && is_array($orders) && count($orders)) {
                foreach ($orders as $order) {
                    $orderInfo = new CDiscountOrderInfo($order['id_order'], $order['mp_order_id'], $order['channel_id'], $order['channel_name'], $order['clogistique']);
                    if (!$orderInfo->saveOrderInfo()) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    protected function removeOldOrdersTable()
    {
        $legacyTable = _DB_PREFIX_ . self::OLD_TABLE_CDISCOUNT_ORDERS;
        if (CommonTools::tableExists($legacyTable)) {
            return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . $legacyTable . '`');
        }

        return true;
    }

    public function getErrors($flush = true)
    {
        $errors = '';
        foreach ($this->errors as $error) {
            $errors .= sprintf('%s - %s, sql: %s', $error['msg'], $error['error'], $error['sql']) . nl2br(Cdiscount::LF);
        }

        if ($flush) {
            $this->errors = array();
        }

        return $errors;
    }

    protected function pushError($message, $sql, $error)
    {
        $this->errors[] = array('msg' => $message, 'sql' => $sql, 'error' => $error);
    }

    private function migrateModel_4_4()
    {
        require_once(dirname(__FILE__) . '/../classes/cdiscount.model.class.php');

        $legacyModels = CDiscountConfiguration::get('models_2020');
        if (!is_array($legacyModels) || !count($legacyModels)) {
            $legacyModels = CDiscountConfiguration::get('models');
        }
        $legacySpecificFields = CDiscountConfiguration::get('specifics_fields');
        $legacyProfiles = CDiscountConfiguration::get('profiles_2020');
        if (!is_array($legacyProfiles) || !count($legacyProfiles)) {
            $legacyProfiles = CDiscountConfiguration::get('profiles');
        }

        if (is_array($legacyModels) && count($legacyModels)) {
            $models = array();
            $specificFields = array();

            // Get valid model indices
            $validIndices = array();
            if (isset($legacyModels['name']) && is_array($legacyModels['name'])) {
                foreach ($legacyModels['name'] as $nameIndex => $modelName) {
                    if ($modelName) {
                        $validIndices[] = $nameIndex;
                    }
                }
            }

            // Build new model structure
            foreach ($validIndices as $validIndex) {
                $modelUniId = uniqid('model_');
                $modelRefactor = array('id' => $modelUniId);
                $modelProperties = array(
                    'name', 'universe', 'category', 'category_name', 'model', 'model_external_name',
                    'public', 'gender', 'variant',
                    'fashion_size', 'feature_size', 'default_size', 'fashion_color', 'feature_color', 'default_color',
                );
                foreach ($modelProperties as $modelProperty) {
                    $value = isset($legacyModels[$modelProperty], $legacyModels[$modelProperty][$validIndex]) ?
                        $legacyModels[$modelProperty][$validIndex] : null;
                    $modelRefactor[$modelProperty] = $value;
                }
                $models[$modelUniId] = $modelRefactor;

                // Specific fields, change key to model unique ID instead of model_name_key
                $modelName = $legacyModels['name'][$validIndex];
                $modelNameKey = CDiscountModel::toKey($modelName);
                if (isset($legacySpecificFields[$modelNameKey])) {
                    $specificFields[$modelUniId] = $legacySpecificFields[$modelNameKey];
                }

                // Profiles, change [model] to model unique ID instead of model_name
                if (isset($legacyProfiles['model'])) {
                    $profileModels = $legacyProfiles['model'];
                    foreach ($profileModels as $profileModelIndex => $profileModelName) {
                        if ($modelName == $profileModelName) {
                            $legacyProfiles['model'][$profileModelIndex] = $modelUniId;
                        }
                    }
                }
            }

            CDiscountConfiguration::updateValue(CDiscountConstant::CONFIG_MODELS, $models);
            CDiscountConfiguration::updateValue(CDiscountConstant::CONFIG_SPECIFIC_FIELDS, $specificFields);
            CDiscountConfiguration::updateValue(CDiscountConstant::CONFIG_PROFILES, $legacyProfiles);
            $this->module->unLoadModels()->unloadSpecificFields()->unloadProfiles();
        }
    }
}
