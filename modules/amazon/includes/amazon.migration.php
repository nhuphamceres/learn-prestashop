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
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

class AmazonMigration
{
    const CONFIG_MIGRATION_VERSION = 'AMAZON_MIGRATION_VERSION';

    /** @var Amazon */
    protected $module;

    /** @var AmazonDBManager */
    protected $dbManager;

    /** @var AmazonLogger */
    protected $logger;

    public function __construct($module)
    {
        $this->module = $module;

        require_once(_PS_MODULE_DIR_ . '/amazon/includes/amazon.db.manager.php');
        $this->dbManager = new AmazonDBManager($module);

        require_once(_PS_MODULE_DIR_ . '/amazon/classes/amazon.logger.class.php');
        $this->logger = new AmazonLogger('');   // Dummy instance
    }

    /**
     * On installation, on postProcess
     * @return bool
     */
    public function migrateMarketplaceTables()
    {
        return $this->dbManager->addMarketPlaceTables();
    }

    public function migrateDuringSaveConfiguration()
    {
        $this->dbManager->migrateCarrierMappingOutgoing(true);
    }

    // todo: Handle error
    public function migrate()
    {
        $migrationVersion = Configuration::getGlobalValue(self::CONFIG_MIGRATION_VERSION);
        if (!$migrationVersion) {
            $migrationVersion = '0.0.0';
        }

        if (version_compare($migrationVersion, '4.9.352', '<') && $this->migrate_4_9_352()) {
            return Configuration::updateGlobalValue(self::CONFIG_MIGRATION_VERSION, '4.9.352');
        }
        if (version_compare($migrationVersion, '4.9.387', '<')) {
            $migrate4_9_387 = $this->migrate_4_9_387();
            if ($migrate4_9_387['code'] === 1) {
                return Configuration::updateGlobalValue(self::CONFIG_MIGRATION_VERSION, '4.9.387');
            }
        }

        // Additional job
        $this->clearLogs();

        return true;
    }

    protected function clearLogs()
    {
        // There is 50% percent that triggers logs clearance
        if ((rand(0, 1) - 0.5) < 0) {
            $this->logger->clearOldLogs();
        }
    }

    protected function migrate_4_9_352()
    {
        require_once dirname(__FILE__) . '/../classes/amazon.vidr_shipment.class.php';

        // todo: Remove in future when VIDR works fine
        AmazonDBManager::upgradeStructureTableVIDRShipment();
        AmazonDBManager::upgradeStructureTableVIDRShipment2();  // 2021-06-01

        $tbl = AmazonVIDRShipment::getTableName();
        $sql = "DELETE v1 FROM `$tbl` v1 JOIN `$tbl` v2
                USING (`shipping_id`, `marketplace`, `transaction_id`, `transaction_type`)
                WHERE v1.id < v2.id";
        return Db::getInstance()->execute($sql);
    }

    protected function migrate_4_9_387()
    {
        return $this->installAmazonStates();
    }

    public function installAmazonStates()
    {
        require_once dirname(__FILE__) . '/../classes/amazon.remote.downloader.class.php';
        $downloader = new AmazonRemoteDownloader(AmazonRemoteDownloader::RES_STATES, false);
        if (!$downloader->downloadResource()) {
            $resultCode = -1;
        } else {
            $sqls = $downloader->getResource();
            if (!$this->dbManager->runFromTexts($sqls)) {
                $resultCode = -2;
            } else {
                $resultCode = 1;
            }
        }

        return array('code' => $resultCode, 'debug' => $downloader->getErrors());
    }

    public function getDbManager()
    {
        return $this->dbManager;
    }

    /**
     * @return string
     */
    public function getErrors()
    {
        return $this->dbManager->getErrors();
    }
}
