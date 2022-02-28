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
 *
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.mirakl@common-services.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class MiraklMigration
{
    const CONFIG_MIGRATION_VERSION = 'MIRAKL_MIGRATION_VERSION';

    /**
     * @var Mirakl
     */
    protected $module;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var MiraklDBManager
     */
    protected $dbManager;

    public function __construct($module, $context)
    {
        $this->module = $module;
        $this->context = $context;
        require_once(_PS_MODULE_DIR_ . '/mirakl/includes/mirakl.db.manager.php');
        $this->dbManager = new MiraklDBManager($module, $context);
    }

    public function migrateDuringInstallation()
    {
        return $this->dbManager->addTableMkpOrders() && $this->dbManager->addTableProductOption();
    }

    public function migrateDuringSaveConfiguration()
    {
        return $this->dbManager->addTableMkpOrders() && $this->dbManager->addTableProductOption();
    }

    public function migrate()
    {
        $migrationVersion = Configuration::getGlobalValue(self::CONFIG_MIGRATION_VERSION);
        if (!$migrationVersion) {
            $migrationVersion = '0.0.0';
        }

        if (version_compare($migrationVersion, '1.3.4', '<') && $this->migrate_1_3_4()) {
            return Configuration::updateGlobalValue(self::CONFIG_MIGRATION_VERSION, '1.3.4');
        }

        return true;
    }

    protected function migrate_1_3_4()
    {
        return
            $this->dbManager->addTableMkpOrders() &&    // Create marketplace_orders table
            $this->dbManager->copyOrdersToShareTable(); // Move order from ps_orders
        // Keep old columns as the moment
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->dbManager->getErrors();
    }
}
