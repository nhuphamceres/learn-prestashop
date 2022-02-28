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
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

class AmazonDBManager
{
    const TABLE_MARKETPLACE_VIDR_SHIPMENT = 'amazon_vidr_shipment';
    const TABLE_MARKETPLACE_VIDR_SHIPMENT_ORDER_MAPPING = 'amazon_vidr_shipment_order';
    const TABLE_MKP_ORDERS = 'marketplace_orders';
    const TABLE_MKP_ORDER_ITEMS = 'marketplace_order_items';
    const TABLE_MARKETPLACE_ORDER_ADDRESS = 'marketplace_order_address';

    /**
     * @var Amazon
     */
    public $module;

    /**
     * @var string All error concat
     */
    public $error = '';

    private $charset;
    private $collation;
    private $tableOptions;

    // todo: Remove in far future when VIDR works fine
    private static $isShipmentTableUpdatedWithPkId;
    private static $isShipmentTableUpdatedWithTransactionId;
    private static $isShipmentTableUpdatedWithStatusText;
    private static $isShipmentTableUpdatedWithStatusDescription;
    private static $isShipmentTableRemoveInvoiceStatus;

    private static $isShipmentTableUpdateWithMkpKey;
    private static $isShipmentTableUpdateWithTransactionTypeKey;

    public function __construct($module)
    {
        $this->module = $module;
        $this->charset = $this->module->ps177x ? 'CHARSET=utf8mb4' : 'CHARSET=utf8';
        $this->collation = $this->module->ps177x ? 'COLLATE=utf8mb4_unicode_ci' : '';   // Let DB choose default collation
        $this->tableOptions = "DEFAULT $this->charset $this->collation";
    }

    public function getErrors()
    {
        $errors = $this->error;
        $this->error = '';
        return $errors;
    }

    public function runFromTexts($texts)
    {
        $texts = str_replace(array('_DB_PREFIX_'), array(_DB_PREFIX_), $texts);
        $texts = preg_split("/;\s*[\r\n]+/", trim($texts));

        $success = true;
        foreach ($texts as $query) {
            $success = $success && Db::getInstance()->execute(trim($query));
        }

        return $success;
    }

    public function migrateCarrierMappingOutgoing($force)
    {
        if ($force || !AmazonConfiguration::getIdByName('AMAZON_CARRIER_OUTGOING')) {
            $result = array();
            $legacyMappings = AmazonConfiguration::get('CARRIER_DEFAULT');
            if (is_array($legacyMappings) && count($legacyMappings)) {
                foreach ($legacyMappings as $idLang => $legacyMapping) {
                    $mappingOfLang = array();

                    if (isset($legacyMapping['prestashop']) && is_array($legacyMapping['prestashop'])) {
                        foreach ($legacyMapping['prestashop'] as $index => $legacyPs) {
                            $legacyAmazon = isset($legacyMapping['amazon'], $legacyMapping['amazon'][$index]) ?
                                $legacyMapping['amazon'][$index] : '';
                            $legacyCustom = isset($legacyMapping['custom_value'], $legacyMapping['custom_value'][$index]) ?
                                $legacyMapping['custom_value'][$index] : '';
                            $shippingService = isset($legacyMapping['shipping_service'], $legacyMapping['shipping_service'][$index]) ?
                                $legacyMapping['shipping_service'][$index] : '';
                            $customMethod = isset($legacyMapping['custom_method'], $legacyMapping['custom_method'][$index]) ?
                                $legacyMapping['custom_method'][$index] : '';
                            $mappingOfLang[] = array(
                                'ps' => $legacyPs,
                                'amazon' => $legacyAmazon,
                                'custom_carrier' => $legacyCustom,
                                'shipping_service' => $shippingService,
                                'custom_method' => $customMethod,
                            );
                        }
                    }

                    $result[$idLang] = $mappingOfLang;
                }
            }

            AmazonConfiguration::updateValue('CARRIER_OUTGOING', $result);
        }
    }

    public function addMarketPlaceTables()
    {
        return $this->addTableMkpOrders()
            && $this->addTableMkpOrderItems()
            && $this->addTableMkpProductAction()
            && $this->addTableMkpProductOption()
            && $this->addTableVIDRShipment()
            && $this->addTableVIDRShipmentOrderMapping()
            && $this->addIdOrderDetailOnMkpOrderItems()
            && $this->addTableAmzStates();
    }

    /**
     * todo: Remove in far future when VIDR works fine
     */
    public static function upgradeStructureTableVIDRShipmentOrderMapping()
    {
        $mappingTbl = _DB_PREFIX_ . self::TABLE_MARKETPLACE_VIDR_SHIPMENT_ORDER_MAPPING;
        if (!AmazonTools::fieldExistsR($mappingTbl, 'buyer_vat_number', false)) {
            Db::getInstance()->execute("ALTER TABLE `$mappingTbl` ADD COLUMN `buyer_vat_number` varchar(50) NULL");
        }
        if (!AmazonTools::fieldExistsR($mappingTbl, 'buyer_vat_number_process_status')) {
            Db::getInstance()->execute("ALTER TABLE `$mappingTbl` ADD COLUMN `buyer_vat_number_process_status` TINYINT(1) NOT NULL DEFAULT 0");
        }
        if (!AmazonTools::fieldExistsR($mappingTbl, 'billing_address')) {
            Db::getInstance()->execute("ALTER TABLE `$mappingTbl` ADD COLUMN `billing_address` VARCHAR(2048) NULL");
        }
        if (!AmazonTools::fieldExistsR($mappingTbl, 'billing_address_process_status')) {
            Db::getInstance()->execute("ALTER TABLE `$mappingTbl` ADD COLUMN `billing_address_process_status` TINYINT(1) NOT NULL DEFAULT 0");
        }
    }

    /**
     * todo: Remove in far future when VIDR works fine
     */
    public static function upgradeStructureTableVIDRShipment()
    {
        $shipmentTbl = _DB_PREFIX_ . self::TABLE_MARKETPLACE_VIDR_SHIPMENT;
        if (is_null(self::$isShipmentTableUpdatedWithPkId)) {
            if (!AmazonTools::amazonFieldExists($shipmentTbl, 'id')) { // no cache
                self::$isShipmentTableUpdatedWithPkId =
                    Db::getInstance()->execute("ALTER TABLE `$shipmentTbl` DROP PRIMARY KEY, ADD `id` INT(11) PRIMARY KEY AUTO_INCREMENT FIRST;");
            }
        }
        if (is_null(self::$isShipmentTableUpdatedWithTransactionId)) {
            if (!AmazonTools::amazonFieldExists($shipmentTbl, 'transaction_id')) { // no cache
                self::$isShipmentTableUpdatedWithTransactionId =
                    DB::getInstance()->execute("ALTER TABLE `$shipmentTbl` ADD `transaction_id` VARCHAR(128) AFTER `marketplace`;");
            }
        }
        if (is_null(self::$isShipmentTableUpdatedWithStatusText)) {
            if (!AmazonTools::amazonFieldExists($shipmentTbl, 'invoice_status_text')) { // no cache
                self::$isShipmentTableUpdatedWithStatusText =
                    DB::getInstance()->execute("ALTER TABLE `$shipmentTbl` ADD `invoice_status_text` VARCHAR(32) AFTER `transaction_type`;");
            }
        }
        if (is_null(self::$isShipmentTableUpdatedWithStatusDescription)) {
            if (!AmazonTools::amazonFieldExists($shipmentTbl, 'invoice_status_description')) { // no cache
                self::$isShipmentTableUpdatedWithStatusDescription =
                    DB::getInstance()->execute("ALTER TABLE `$shipmentTbl` ADD `invoice_status_description` VARCHAR(512) AFTER `invoice_status_text`;");
            }
        }

        // Delete unused column
        if (is_null(self::$isShipmentTableRemoveInvoiceStatus)) {
            if (AmazonTools::amazonFieldExists($shipmentTbl, 'invoice_status')) { // no cache
                self::$isShipmentTableRemoveInvoiceStatus =
                    DB::getInstance()->execute("ALTER TABLE `$shipmentTbl` DROP `invoice_status`;");
            }
        }
    }

    public static function upgradeStructureTableVIDRShipment2()
    {
        $shipmentTbl = self::getVCSShipmentTblName();
        if (is_null(self::$isShipmentTableUpdateWithMkpKey)) {
            if (!self::tableIndexExist($shipmentTbl, 'amazon_vidr_shipment_marketplace', false)) {
                self::$isShipmentTableUpdateWithMkpKey =
                    Db::getInstance()->execute("CREATE INDEX amazon_vidr_shipment_marketplace ON $shipmentTbl(marketplace)");
            }
        }
        if (is_null(self::$isShipmentTableUpdateWithTransactionTypeKey)) {
            if (!self::tableIndexExist($shipmentTbl, 'amazon_vidr_shipment_transaction_type', false)) {
                self::$isShipmentTableUpdateWithTransactionTypeKey =
                    Db::getInstance()->execute("CREATE INDEX amazon_vidr_shipment_transaction_type ON $shipmentTbl(transaction_type)");
            }
        }
    }

    private function addTableMkpOrders()
    {
        require_once dirname(dirname(__FILE__)) . '/classes/amazon.order.class.php';

        $mkpOrderTbl = _DB_PREFIX_ . self::TABLE_MKP_ORDERS;

        if (!AmazonTools::tableExists($mkpOrderTbl)) {
            // Todo: Shared table with CDiscount
            // Todo: More query criteria for Amazon order only in this table
            $sql = 'CREATE TABLE IF NOT EXISTS `' . $mkpOrderTbl . '` (
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
                        `is_prime` BOOL NOT NULL DEFAULT ' . AmazonOrder::REGULAR_ORDER . ',
                        `is_premium` BOOL NOT NULL DEFAULT ' . AmazonOrder::REGULAR_ORDER . ',
                        `is_business` BOOL NOT NULL DEFAULT ' . AmazonOrder::REGULAR_ORDER . ',
                        `earliest_ship_date` datetime DEFAULT NULL,
                        `latest_ship_date` datetime DEFAULT NULL,
                        `earliest_delivery_date` datetime DEFAULT NULL,
                        `latest_delivery_date` datetime DEFAULT NULL,
                        `shipping_services` VARCHAR(255) NULL,
                        PRIMARY KEY (`id_order`) ,
                        KEY `mp_order_id`(`mp_order_id`)
                    ) ENGINE=InnoDB ' . $this->tableOptions;

            return $this->executeStatement($sql);
        }

        $result = true;

        if (!AmazonTools::fieldExistsR($mkpOrderTbl, 'shipping_services')) {
            $result = $result && $this->executeStatement("ALTER TABLE `$mkpOrderTbl` ADD COLUMN `shipping_services` VARCHAR(255) NULL");
        }

        if (!AmazonTools::fieldExistsR($mkpOrderTbl, 'fulfillment_center_id')) {
            $result = $result && $this->executeStatement("ALTER TABLE `$mkpOrderTbl` ADD COLUMN `fulfillment_center_id` VARCHAR(32) NULL");
        }

        // Fix the wrong index
        if ($this->tableIndexExist($mkpOrderTbl, 'mp_order_id', true)) {
            $alterSql = "ALTER TABLE `$mkpOrderTbl` DROP INDEX `mp_order_id`, ADD KEY `mp_order_id` (`mp_order_id`);";
            $result = $result && $this->executeStatement($alterSql);
        }

        return $result;
    }

    private function addTableMkpOrderItems()
    {
        $mkpOrderItemsTbl = _DB_PREFIX_ . self::TABLE_MKP_ORDER_ITEMS;

        if (!AmazonTools::tableExists($mkpOrderItemsTbl)) {
            // 2020-09-30: Add id_order_detail
            // 2021-07-07: Add additional information (IOSS / OSS)
            $sql = 'CREATE TABLE IF NOT EXISTS `' . $mkpOrderItemsTbl . '` (
                    `mp_order_id` VARCHAR(32) NULL DEFAULT NULL,
                    `order_item_id` VARCHAR(32) NULL DEFAULT NULL,
                    `id_order` INT NOT NULL,
                    `id_product` INT NULL DEFAULT NULL,
                    `id_product_attribute` INT NULL DEFAULT NULL ,
                    `quantity` INT NULL DEFAULT NULL,
                    `sku` VARCHAR(32) NULL DEFAULT NULL,
                    `asin` VARCHAR(16) NULL DEFAULT NULL,
                    `carrier_code` VARCHAR(16) NULL DEFAULT NULL,
                    `carrier_name` VARCHAR(32) NULL DEFAULT NULL,
                    `shipping_method` VARCHAR(16) NULL DEFAULT NULL,
                    `tracking_number` VARCHAR(24) NULL DEFAULT NULL,
                    `item_status` TINYINT NULL DEFAULT NULL,
                    `reason` VARCHAR(40) NULL DEFAULT NULL,
                    `id_order_detail` INT(11) UNSIGNED NULL DEFAULT NULL,
                    `customization` TEXT NULL,
                    `additional_info` TEXT NULL,
                    UNIQUE KEY `order_items_idx` (`mp_order_id`, `order_item_id`),
                    KEY `id_order_idx` (`id_order`),
                    KEY `mp_order_id_idx` (`mp_order_id`)
			) ENGINE=InnoDB ' . $this->tableOptions;

            return $this->executeStatement($sql);
        }

        if (!AmazonTools::fieldExistsR($mkpOrderItemsTbl, 'additional_info')) {
            return $this->executeStatement("ALTER TABLE `$mkpOrderItemsTbl` ADD COLUMN `additional_info` TEXT NULL");
        }

        return true;
    }

    protected function addTableMkpProductAction()
    {
        // todo: Consider primary key instead of unique key
        $productActionTbl = _DB_PREFIX_ . AmazonConstant::TABLE_MARKETPLACE_PRODUCT_ACTION;
        if (!AmazonTools::tableExists($productActionTbl)) {
            $sql = 'CREATE TABLE IF NOT EXISTS `' . $productActionTbl . '` (
                `id_product` int(11) NOT NULL,
                `id_product_attribute` int(11) DEFAULT NULL,
                `id_lang` int(11) NOT NULL,
                `sku` varchar(64) DEFAULT NULL,
                `marketplace` varchar(12) NOT NULL,
                `action` char(1) NOT NULL,
                `date_add` datetime DEFAULT NULL,
                `date_upd` datetime DEFAULT NULL,
                `id_shop` INT(11) UNSIGNED NULL DEFAULT 1,
                UNIQUE KEY `id_product_2021_02_08` (`id_product`, `id_lang`, `marketplace`, `action`, `id_shop`),
                KEY `id_lang` (`id_lang`,`marketplace`)
            ) ENGINE=InnoDB ' . $this->tableOptions;

            return $this->executeStatement($sql);
        }

        $success = true;

        // Add column
        if (!AmazonTools::amazonFieldExists($productActionTbl, 'id_shop')) {
            $success = $success &&
                $this->executeStatement("ALTER TABLE `$productActionTbl` ADD COLUMN `id_shop` INT(11) UNSIGNED NULL DEFAULT 1");
        }

        // Fix index
        if ($this->tableIndexExist($productActionTbl, 'id_product', true)) {
            $alterSql = 'ALTER TABLE `' . $productActionTbl . '` DROP INDEX `id_product`';
            $success = $success && $this->executeStatement($alterSql);
        }
        if ($this->tableIndexExist($productActionTbl, 'id_product_2020_12_10', true)) {
            $alterSql = 'ALTER TABLE `' . $productActionTbl . '`
                DROP INDEX `id_product_2020_12_10`, 
                ADD UNIQUE KEY `id_product_2021_02_08` (`id_product`, `id_lang`, `marketplace`, `action`, `id_shop`)';
            $success = $success && $this->executeStatement($alterSql);
        }

        return $success;
    }

    protected function addTableMkpProductOption()
    {
        // Support emoji bullet points in new version
        $columnCharset = $this->module->ps177x ? 'CHARACTER SET utf8mb4' : '';
        $productOptionTbl = _DB_PREFIX_ . AmazonConstant::TABLE_MARKETPLACE_PRODUCT_OPTION;

        if (!AmazonTools::tableExists($productOptionTbl)) {
            $sql = 'CREATE TABLE IF NOT EXISTS `' . $productOptionTbl . '` (
                `id_product` int(11) NOT NULL,
                `id_lang` int(11) NOT NULL,
                `id_product_attribute` int(11) NOT NULL DEFAULT 0,
                `force` tinyint(4) DEFAULT NULL,
                `nopexport` tinyint(4) DEFAULT NULL,
                `noqexport` tinyint(4) DEFAULT NULL,
                `fba` tinyint(4) DEFAULT NULL,
                `fba_value` FLOAT DEFAULT NULL,
                `latency` tinyint(4) DEFAULT NULL,
                `disable` tinyint(4) DEFAULT NULL,
                `price` FLOAT DEFAULT NULL,
                `asin1` varchar(16) DEFAULT NULL,
                `asin2` varchar(16) DEFAULT NULL,
                `asin3` varchar(16) DEFAULT NULL,
                `text` varchar(256) DEFAULT NULL,
                `bullet_point1` varchar(' . AmazonConstant::LENGTH_BULLET_POINT . ') ' . $columnCharset . ' DEFAULT NULL,
                `bullet_point2` varchar(' . AmazonConstant::LENGTH_BULLET_POINT . ') ' . $columnCharset . ' DEFAULT NULL,
                `bullet_point3` varchar(' . AmazonConstant::LENGTH_BULLET_POINT . ') ' . $columnCharset . ' DEFAULT NULL,
                `bullet_point4` varchar(' . AmazonConstant::LENGTH_BULLET_POINT . ') ' . $columnCharset . ' DEFAULT NULL,
                `bullet_point5` varchar(' . AmazonConstant::LENGTH_BULLET_POINT . ') ' . $columnCharset . ' DEFAULT NULL,
                `shipping` float DEFAULT NULL,
                `shipping_type` tinyint(4) DEFAULT NULL,
                `gift_wrap` tinyint(4) DEFAULT NULL,
                `gift_message` tinyint(4) DEFAULT NULL,
                `browsenode` varchar(16) DEFAULT NULL,
                `repricing_min` FLOAT DEFAULT NULL,
                `repricing_max` FLOAT DEFAULT NULL,
                `repricing_gap` FLOAT DEFAULT NULL,
                `shipping_group` varchar(32) DEFAULT NULL,
                `alternative_title` varchar(255) DEFAULT NULL,
                `alternative_description` TEXT NULL,
                `id_shop` INT(11) UNSIGNED NULL DEFAULT 1,
                UNIQUE KEY `id_product_2021_02_08` (`id_product`, `id_product_attribute`, `id_lang`, `id_shop`),
                KEY `ASIN` (`asin1`)
            ) ENGINE=InnoDB ' . $this->tableOptions;

            $createTableResult = $this->executeStatement($sql);
            $this->saveProductOptionTableFieldList();

            return $createTableResult;
        }

        // Current fields
        $fields = array();
        $query = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . $productOptionTbl . '`');
        if ($query) {
            foreach ($query as $row) {
                $fields[$row['Field']] = 1;
            }
        }

        // To update fields
        $updateFields = array(
            'browsenode' => 'varchar(16) DEFAULT NULL AFTER `gift_message`',
            'repricing_min' => 'FLOAT NULL DEFAULT NULL AFTER `browsenode`',
            'repricing_max' => 'FLOAT NULL DEFAULT NULL AFTER `repricing_min`',
            'repricing_gap' => 'FLOAT NULL DEFAULT NULL AFTER `repricing_max`',
            'shipping_group' => 'varchar(32) DEFAULT NULL AFTER `repricing_gap`',
            'alternative_title' => 'varchar(255) DEFAULT NULL AFTER `shipping_group`',
            'alternative_description' => 'TEXT NULL AFTER `alternative_title`',
            'id_shop' => 'INT(11) UNSIGNED NULL DEFAULT 1',
        );

        $success = true;

        // Drop primary key if exist
        if ($this->tableIndexExist($productOptionTbl, 'PRIMARY', true)) {
            $success = $success && $this->executeStatement("ALTER TABLE `$productOptionTbl` DROP PRIMARY KEY");
        }

        foreach ($updateFields as $updateField => $definition) {
            if (!isset($fields[$updateField])) {
                $success = $success && $this->executeStatement("ALTER TABLE `$productOptionTbl` ADD `$updateField` $definition");
            } elseif ($updateField == 'id_shop') {
                // `id_shop` is accidentally set as NOT NULL
                $success = $success && $this->executeStatement("ALTER TABLE `$productOptionTbl` MODIFY `id_shop` INT(11) UNSIGNED NULL DEFAULT 1");
            }
        }

        // Fix index, cannot use primary key because `id_shop` can be null
        if ($this->tableIndexExist($productOptionTbl, 'id_product_2020_12_15', true)) {
            $alterSql = 'ALTER TABLE `' . $productOptionTbl . '` DROP INDEX `id_product_2020_12_15`';
            $success = $success && $this->executeStatement($alterSql);
        }
        if (!$this->tableIndexExist($productOptionTbl, 'id_product_2021_02_08', true)) {
            $alterSql = 'ALTER TABLE `' . $productOptionTbl . '`
                ADD UNIQUE KEY `id_product_2021_02_08` (`id_product`, `id_product_attribute`, `id_lang`, `id_shop`)';
            $success = $success && $this->executeStatement($alterSql);
        }

        $this->saveProductOptionTableFieldList();

        return $success;
    }

    // todo: Maybe need an index on shipping_id
    protected function addTableVIDRShipment()
    {
        $tableName = _DB_PREFIX_ . self::TABLE_MARKETPLACE_VIDR_SHIPMENT;

        if (!AmazonTools::tableExists($tableName)) {
            // amazon_vidr_shipment_marketplace, amazon_vidr_shipment_transaction_type to find the shipment data of a particular order
            // to review VCS invoice
            $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `shipping_id` VARCHAR(25) NOT NULL,
                `marketplace` VARCHAR(5) NULL DEFAULT NULL,
                `transaction_id` VARCHAR(128) NULL COMMENT '#Shipment: shipping_id, #Return/Refund: unique transaction ID, used when upload creditnote',
                `transaction_type` VARCHAR(25) NOT NULL COMMENT 'SHIPMENT / RETURN / REFUND',
                `invoice_status_text` VARCHAR(32) NULL,
                `invoice_status_description` VARCHAR(512) NULL,
                `is_amazon_invoiced` TINYINT(1) NOT NULL,
                `shipment_date` DATETIME NOT NULL,
                `seller_vat_number` VARCHAR(50) NULL,
                `id_currency` INT(11) UNSIGNED NOT NULL,
                `shipment_data` TEXT NOT NULL,
                `order` INT(11) NOT NULL DEFAULT 0,
                `item` INT(11) NOT NULL DEFAULT 0,
	            `process_status` TINYINT(1) NOT NULL COMMENT '#0: Pending, #1: Processing, #2: Done, #3: Error, #4: Error-WS throttle, #5: Error-Order not imported!, #6: Error-PDF failed',
	            `processed_data` TEXT NULL,
	            `date_add` DATETIME NOT NULL,
	            `date_upd` TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `amazon_vidr_shipment_marketplace`(`marketplace`),  
                KEY `amazon_vidr_shipment_transaction_type`(`transaction_type`)
            ) ENGINE=InnoDB $this->tableOptions";

            return $this->executeStatement($sql);
        }

        return true;
    }

    protected function addTableVIDRShipmentOrderMapping()
    {
        $tableName = _DB_PREFIX_ . self::TABLE_MARKETPLACE_VIDR_SHIPMENT_ORDER_MAPPING;

        if (!AmazonTools::tableExists($tableName)) {
            $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (
                `id_shipment_order` INT(11) NOT NULL AUTO_INCREMENT,
                `shipment_id` VARCHAR(25) NOT NULL,
                `mp_order_id` VARCHAR(32) NOT NULL,
                `buyer_vat_number` VARCHAR(50) NULL,
                `buyer_vat_number_process_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '#0: Pending, #1: Processing, #2: Done, #3: Error - Empty!, #5: Error - Order not imported!, #11 #12: Error - Invalid address!',
                `billing_address` VARCHAR(2048) NULL,
                `billing_address_process_status` TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_shipment_order`),
                KEY `ps_amazon_vidr_shipment_order_shipment_id` (`shipment_id`),
                KEY `ps_amazon_vidr_shipment_order_mp_order_id` (`mp_order_id`)
            ) ENGINE=InnoDB $this->tableOptions";

            return $this->executeStatement($sql);
        }

        return true;
    }

    protected function addIdOrderDetailOnMkpOrderItems()
    {
        $mkpOrderItemsTbl = _DB_PREFIX_ . self::TABLE_MKP_ORDER_ITEMS;
        if (!AmazonTools::amazonFieldExists($mkpOrderItemsTbl, 'id_order_detail')) {
            $sql = "ALTER TABLE `$mkpOrderItemsTbl` ADD COLUMN `id_order_detail` INT(11) UNSIGNED NULL DEFAULT NULL";
            return $this->executeStatement($sql);
        }

        return true;
    }

    protected function addTableAmzStates()
    {
        $tableName = _DB_PREFIX_ . AmazonConstant::TABLE_AMZ_STATES;

        $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (
            `country_iso` varchar(3) NOT NULL,
            `zipcode_start` varchar(8) NOT NULL,
            `state_iso` varchar(8) NOT NULL,
            `state_name` varchar(255) DEFAULT NULL,
            `note` varchar(255) DEFAULT NULL,
            UNIQUE KEY `country_iso` (`country_iso`,`zipcode_start`)
        ) ENGINE=InnoDB $this->tableOptions";

        return $this->executeStatement($sql);
    }

    protected function executeStatement($sql)
    {
        if (!Db::getInstance()->execute($sql)) {
            $this->error .= 'ERROR: ' . $sql . nl2br(Amazon::LF);
            $this->error .= Db::getInstance()->getMsgError();
            return false;
        }

        return true;
    }

    private function saveProductOptionTableFieldList()
    {
        $fields = array();
        $query = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . AmazonConstant::TABLE_MARKETPLACE_PRODUCT_OPTION . '`');
        if ($query) {
            foreach ($query as $row) {
                $fields[] = $row['Field'];
            }
        }

        if (is_array($fields) && count($fields)) {
            Configuration::updateGlobalValue('AMAZON_PRODUCT_OPTION_FIELDS', implode(',', $fields));
        }
    }

    private static function tableIndexExist($tableName, $indexName, $isUnique)
    {
        $indices = Db::getInstance()->executeS('SHOW INDEX FROM `' . $tableName . '`');
        if (is_array($indices) && count($indices)) {
            foreach ($indices as $index) {
                if (Tools::strtoupper($index['Key_name']) == Tools::strtoupper($indexName) && $index['Non_unique'] == ($isUnique ? 0 : 1)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function getVCSShipmentTblName()
    {
        return _DB_PREFIX_ . self::TABLE_MARKETPLACE_VIDR_SHIPMENT;
    }
}
