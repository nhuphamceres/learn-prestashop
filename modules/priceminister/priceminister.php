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
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   Rakuten
 * Contact by Email :  support.rakuten@common-services.com
 */

require_once dirname(__FILE__) . '/includes/rakuten.constant.php';
if (!class_exists('ConfigureMessage')) {
    require_once(_PS_MODULE_DIR_.'priceminister/classes/shared/configure_message.class.php');
}

class PriceMinister extends Module
{
    const ADD = 'a';
    const REMOVE = 'd';
    const UPDATE = 'u';

    // Table definition
    const TABLE_PRICEMINISTER_ORDERS                = 'priceminister_orders';
    const TABLE_PRICEMINISTER_PRODUCT_OPTION        = 'priceminister_product_option';
    const TABLE_PRICEMINISTER_PRODUCT_ORDERED       = 'priceminister_product_ordered';
    const TABLE_PRICEMINISTER_MODELS                = 'priceminister_model';
    const TABLE_PRICEMINISTER_MAPPINGS              = 'priceminister_mappings';
    const TABLE_PRICEMINISTER_MAPPINGS_DET          = 'priceminister_mappings_det';
    // Contain all profiles, models, categories...
    const TABLE_PRICEMINISTER_CONFIGURATION         = 'pm_configuration';
    const TABLE_PRICEMINISTER_CONFIGURATION_COMMON  = 'priceminister_configuration';
    const TABLE_PRICEMINISTER_REPRICING             = 'priceminister_repricing';
    const TABLE_PRICEMINISTER_STRATEGY              = 'priceminister_strategy';

    // Config key definition, stored in PS configuration table
    const CONFIG_PM_DEBUG       = 'PM_DEBUG';
    const CONFIG_PM_VERSION     = 'PM_CURRENT_VERSION';
    const CONFIG_PM_CREDENTIALS = 'PM_CREDENTIALS';
    const CONFIG_PM_SHIPPING    = 'PM_SHIPPING';
    const CONFIG_PM_PARAMETERS  = 'PM_PARAMETERS';
    const CONFIG_PM_FILTERS     = 'PM_FILTERS';
    const CONFIG_PM_ORDERS      = 'PM_ORDERS';
    const CONFIG_PM_CUSTOMER_ID = 'PM_CUSTOMER_ID';
    const CONFIG_PM_CRON_TOKEN  = 'PM_CRON_TOKEN';
    const CONFIG_BATCH_UPDATE   = 'PRICEMINISTER_BATCH_UPDATE';
    const CONFIG_BATCH_CREATE   = 'PRICEMINISTER_BATCH_CREATE';
    // Long config will be stored in module configuration table
    const CONFIG_PM_ADDRESS_MAP     = 'ADDRESS_MAP';
    const CONFIG_PM_CONTEXT_DATA    = 'CONTEXT_DATA';

    const TRASH_DOMAIN = 'priceminister.mp.common-services.com';
    const FEATURE = 'Feature Value';
    const ATTRIBUTE = 'Attribute Value';
    const PM = 'PM Value';
    const DELIVERY_STANDARD_ = 1;
    /* mapping and models variables */
    const DELIVERY_COLLECTION_POINT = 2;
    const ONE_CUSTOMER_ACCOUNT = 1;
    const INDIVIDUAL_CUSTOMER_ACCOUNT = 2;
    public static $attributes_groups_options = array();
    public static $attributes_groups = array();
    public static $attributes_options = array();
    public static $attributes = array();
    /* mapping and models constants */
    public static $features_options = array();
    public static $features_values = array();
    public static $features = array();
    public static $pm_carriers = array(
        'Colis Prive',
        'So Colissimo',
        'Colissimo',
        'CHRONOPOST',
        'DPD',
        'Mondial Relay',
        'Kiala',
        'TNT',
        'UPS',
        'Fedex',
        'Tatex',
        'GLS',
        'DHL',
        'France Express',
        'Courrier Suivi',
        'Exapaq',
        'Autre'
    );
    /* validation and errors */
    public static $pm_shipping_methods = array(
        'Normal',
        'Suivi',
        'Recommande',
        'So Colissimo',
        'Chronopost',
        'Retrait chez le vendeur',
        'Express',
        'Point relais Mondial Relay',
        'Relais Kiala',
        'EN TELECHARGEMENT'
    );
    public static $pm_shipping_methods_type = array(
        'Normal' => self::DELIVERY_STANDARD_,
        'Suivi' => self::DELIVERY_STANDARD_,
        'Recommande' => self::DELIVERY_STANDARD_,
        'So Colissimo' => self::DELIVERY_COLLECTION_POINT,
        'Chronopost' => self::DELIVERY_STANDARD_,
        'Retrait chez le vendeur',
        'Express' => self::DELIVERY_STANDARD_,
        'Point relais Mondial Relay' => self::DELIVERY_COLLECTION_POINT
    );
    public static $pm_shipping_zones = array(
        'France' => 'France',
        'Drom_Com' => 'DROM COM',
        'Europe' => 'Europe',
        'Monde' => 'Monde'
    );
    public static $pm_shipping_options = array(
        'NORMAL' => 'Normal',
        'RECOMMANDE' => 'Recommand&eacute;',
        'SUIVI' => 'Suivi',
        'EXPRESS_DELIVERY' => 'Express',
        'MONDIAL_RELAY' => 'Mondial Relay',
        'KIALA' => 'Kiala'
    );
    public static $pm_shipping_options_settings = array(
        'France' => array('NORMAL', 'RECOMMANDE', 'SUIVI', 'EXPRESS_DELIVERY', 'MONDIAL_RELAY', 'KIALA'),
        'Drom_Com' => array('NORMAL', 'RECOMMANDE'),
        'Europe' => array('NORMAL', 'RECOMMANDE'),
        'Monde' => array('NORMAL', 'RECOMMANDE')
    );
    public static $config_template = array(
        self::CONFIG_PM_SHIPPING    => array('shipping_methods', 'pm_carriers', 'ps_carriers', 'shipping_per_item', 'shipping_options', 'shipping_table', 'shipping_defaults'),
        self::CONFIG_PM_PARAMETERS  => array('import_method', 'specials', 'warehouse', 'image_type', 'image_optionnal', 'condition_map', 'safe_descriptions', 'customer_group'),
        self::CONFIG_PM_CREDENTIALS => array('login', 'token', 'test'),
        self::CONFIG_PM_FILTERS     => array('manufacturers', 'suppliers', 'outofstock', 'price'),
        self::CONFIG_PM_ORDERS      => array('status_incoming', 'status_sent', 'customer_account', 'email_domain', 'shippingfromcountry')
    );
    public $conditions = array(
        'N' => 'Neuf',
        'CN' => 'Comme neuf',
        'TBE' => 'Tr&egrave;s bon Etat',
        'BE' => 'Bon Etat',
        'EC' => 'Etat correct',
    );
    public $_conditions_pm = array(
        'N' => 0,
        'CN' => 10,
        'TBE' => 20,
        'BE' => 30,
        'EC' => 40,
    );
    public $weight_table = array(
        100000 => '+ de 100 kg',
        70000 => '70 &agrave; 100 kg',
        50000 => '50 &agrave; 70 kg',
        30000 => '30 &agrave; 50 kg',
        15000 => '15 &agrave; 30 kg',
        3000 => '3 &agrave; 10 kg',
        500 => '500 g &agrave; 3 kg',
        250 => '250 &agrave; 500 g',
        100 => '100 &agrave; 250 g',
        0 => '0 &agrave; 100 g',
    );
    public $weight_table_alternative = array(
        100000 => 'Plus de 3 Kg',
        3000 => 'De 500 &agrave; 3000 g',
        250 => 'De 250 &agrave; 500 g',
        0 => 'Moins de 250 g',
    );

    public $url;
    public $images;
	public $id_lang;
	public $debug = false;
    /** @var RakutenDebugDetails */
    public $debugDetails;

    protected $config = array();
    protected $_default_comment = 'Envoi rapide et soign&eacute;...';
    protected $ps17x;
    protected $ps16x;
    protected $ps15x;
    private $_categories = null;
    private $onlyActivesCategories = false;
    private $_html = '';
    private $_postErrors = array();
    private $post_data = array();

    // Default config when install, delete when uninstall
    private $_config = array(
        self::CONFIG_PM_VERSION     => null,
        self::CONFIG_PM_CREDENTIALS => null,
        self::CONFIG_PM_PARAMETERS  => null,
        self::CONFIG_PM_ORDERS      => null,
        self::CONFIG_PM_SHIPPING    => null,
        self::CONFIG_PM_CUSTOMER_ID => null,
        self::CONFIG_PM_ADDRESS_MAP => null,
        self::CONFIG_PM_CRON_TOKEN  => null,
        self::CONFIG_PM_CONTEXT_DATA => null,
        self::CONFIG_BATCH_UPDATE   => null,
        self::CONFIG_BATCH_CREATE   => null
    );

    public function __construct()
    {
        $this->name = 'priceminister';
        $this->author = 'Common-Services';
        $this->page = basename(__FILE__, '.php');
        $this->tab = 'market_place';
        $this->version = '4.4.13';
        $this->module_key = '8a0a8e805c0c0247ffe7d6f7b31028cf';
        $this->author_address = '0x96116FE33A6268AE9E878Dbc609A02BdCcc285E0';
        $this->displayName = $this->l('Rakuten France');
        $this->description = $this->l('This extension allow to manage products, orders, stock from/to Rakuten France');
        $this->bootstrap = true;

        parent::__construct();

        $this->path = str_replace('\\', '/', dirname(__FILE__)).'/';
        $this->url = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/';
        $this->images = $this->url.'views/img/';
        $this->js = $this->url.'views/js/';

        if ((defined('PS_ADMIN_DIR') || defined('_PS_ADMIN_DIR_')) && self::isInstalled($this->name)) {
            if (!is_array(unserialize(Configuration::get(self::CONFIG_PM_CREDENTIALS)))) {
                $this->warning = $this->l('Your module is not yet configured !');
            }

            if (!function_exists('curl_init')) {
                $this->warning = $this->l('PHP cURL must be installed for this module working...');
            }

            if (!(int)Configuration::get('PS_SHOP_ENABLE')) {
                $this->warning = $this->l('Be carefull, your shop is in maintenance mode, the module might not work in that mode');
            }

            if (!$this->active) {
                $this->warning = $this->l('Be carefull, your module is inactive, this mode stops all pending operations for this module, please change the status to active in your module list');
            }
        }

        if ((bool)Configuration::get(self::CONFIG_PM_DEBUG, 0)) {
            $this->debug = true;
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }

        /* Backward compatibility */
        if (_PS_VERSION_ < '1.5') {
            require(_PS_MODULE_DIR_.$this->name.'/backward_compatibility/backward.php');
        }

        $this->initContext();
    }

    /* Retrocompatibility 1.4/1.5 */
    private function initContext()
    {
        $this->ps17x = version_compare(_PS_VERSION_, '1.7', '>=');
        $this->ps16x = version_compare(_PS_VERSION_, '1.6', '>=');
        $this->ps15x = !$this->ps17x && !$this->ps16x;

        $this->context = Context::getContext();
        $this->id_lang = isset($this->context->language) && Validate::isLoadedObject($this->context->language) ?
            $this->context->language->id : Configuration::get('PS_LANG_DEFAULT');

        require_once dirname(__FILE__) . '/classes/rakuten.debug.php';
        $this->debugDetails = new RakutenDebugDetails();
    }

    public static function getTemplateShippingFilename($template)
    {
        return PriceMinister::getTemplateDir().DIRECTORY_SEPARATOR.'shipping'.DIRECTORY_SEPARATOR.$template.'.xml';
    }

    public static function saveList($listname, $contents)
    {
        if (strripos($listname, '_def', -3) !== false) {
            $listname = Tools::substr($listname, 0, -4);
        }

        if (!$contents instanceof SimpleXMLElement) {
            return;
        }

        $output_file = self::getTemplateListsFilename($listname);

        if (file_exists($output_file)) {
            return;
        }

        $contents->asXML($output_file);
    }

    public function install()
    {
        $pass = true;

        foreach ($this->_config as $key => $value) {
            // todo: Mix of base64 and serialize config key, it works but need enhance later
            if (!Configuration::updateValue($key, (is_array($value) ? PriceMinisterTools::base64Encode(serialize($value)) : $value))) {
                $pass = false;
            }
        }

        if (!parent::install() || !$this->_installTables() || !$this->_createCustomer()) {
            $pass = false;
        }

        // Install Tabs
        if ($pass) {
            $pass = $this->_tabSetup(self::ADD);
        }

        // ADD Hooks
        $this->_hookSetup(self::ADD);

        if ($pass) {
            require_once(dirname(__FILE__).'/classes/priceminister.context.class.php');
            $pass = PriceMinisterContext::save($this->context);
        }

        return ((bool)$pass);
    }

    private function _installTables()
    {
        $pass = true;
        $errors = null;

        // Check if exists
        //
        $tables = array();
        $query = Db::getInstance()->executeS('SHOW TABLES', true, false);
        foreach ($query as $rows) {
            foreach ($rows as $table) {
                $tables[$table] = 1;
            }
        }

        if (!isset($tables[_DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_ORDERED])) {
            $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_ORDERED.'` (
                        `id_order` INT NOT NULL ,
                        `id_product` INT NULL DEFAULT NULL,
                        `id_product_attribute` INT NULL DEFAULT NULL ,
                        `itemid` INT NULL DEFAULT NULL
			) DEFAULT CHARSET=utf8;';

            if (!Db::getInstance()->execute($sql)) {
                $errors .= 'ERROR: '.$sql;
                $pass = false;
            }
        }

        if (!isset($tables[_DB_PREFIX_.self::TABLE_PRICEMINISTER_REPRICING])) {
            $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_REPRICING.'` (
                        `id_repricing` int(11) unsigned NOT NULL AUTO_INCREMENT,
                        `name` varchar(64) NOT NULL DEFAULT "",
                        `active` tinyint(1) NOT NULL DEFAULT 1,
                        `aggressiveness` tinyint(2) NOT NULL DEFAULT 10,
                        `base` tinyint(1) NOT NULL DEFAULT 1,
                        `limit` tinyint(3) NOT NULL DEFAULT 10,
                        `delta` varchar(15) NOT NULL DEFAULT "0;0",
                        PRIMARY KEY (`id_repricing`)
                ) DEFAULT CHARSET=utf8;';

            if (!Db::getInstance()->execute($sql)) {
                $errors .= 'ERROR: '.$sql;
                $pass = false;
            }
        }

        if (!isset($tables[_DB_PREFIX_.self::TABLE_PRICEMINISTER_STRATEGY])) {
            $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_STRATEGY.'` (
                        `id_strategy` int(11) unsigned NOT NULL AUTO_INCREMENT,
                        `id_product` int(11) unsigned NOT NULL,
                        `id_product_attribute` int(11) unsigned DEFAULT NULL,
                        `id_lang` int(11) unsigned NOT NULL,
                        `minimum_price` FLOAT NOT NULL,
                        `actual_price` FLOAT NOT NULL,
                        `target_price` FLOAT NOT NULL,
                        `gap` FLOAT NOT NULL,
                        PRIMARY KEY (`id_strategy`),
                        UNIQUE KEY `u_id_product_mix` (`id_product`, `id_product_attribute`)
                ) DEFAULT CHARSET=utf8;';

            if (!Db::getInstance()->execute($sql)) {
                $errors .= 'ERROR: '.$sql;
                $pass = false;
            }
        }

        if (isset($tables[_DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_OPTION])) {
            $pass = true;
            $sqls = array();

            $fields = array();

            // Price Minister Update - ADD new fields
            //
            $query = Db::getInstance()->executeS('SHOW COLUMNS FROM `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_OPTION.'`');
            foreach ($query as $row) {
                $fields[$row['Field']] = 1;
            }

            // For Next updates
            if (isset($fields['disable'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_OPTION.'` CHANGE  `disable`  `disable` TINYINT(1) NULL DEFAULT NULL';
            }
            if (isset($fields['force'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_OPTION.'` CHANGE  `force`  `force` INT NULL DEFAULT NULL';
            }
            if (isset($fields['text'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_OPTION.'` CHANGE  `text`  `text` VARCHAR(128) NULL DEFAULT NULL';
            }

            foreach ($sqls as $sql) {
                // Minimal update, no need to show errors
                Db::getInstance()->execute($sql);
            }
            $sqls = array();

            if (!isset($fields['id_product_attribute'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_OPTION.'` ADD  `id_product_attribute` INT NOT NULL AFTER `id_product`';
            }

            if (!isset($fields['repricing_min'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_OPTION.'` ADD  `repricing_min` FLOAT NULL DEFAULT NULL';
            }

            if (!isset($fields['repricing_max'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_OPTION.'` ADD  `repricing_max` FLOAT NULL DEFAULT NULL';
            }

            $unique_key_fields = array();
            $query = Db::getInstance()->executeS('SHOW KEYS FROM `'._DB_PREFIX_.'priceminister_product_option`');
            foreach ($query as $row) {
                $unique_key_fields[] = $row['Column_name'];
            }
            if (!in_array('id_product_attribute', $unique_key_fields)) {
                $sqls[] = 'ALTER TABLE `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_OPTION.'` DROP INDEX `id_product`,
				ADD UNIQUE KEY `id_product` (`id_product`, `id_product_attribute`, `id_lang`)';
            }

            foreach ($sqls as $sql) {
                if (!Db::getInstance()->execute($sql)) {
                    $errors .= 'ERROR: '.$sql.'<br>';
                    $pass = false;
                }
            }
        } else {
            $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_OPTION.'` (
                    `id_product` INT NOT NULL ,
                    `id_product_attribute` INT NOT NULL ,
                    `id_lang` INT NOT NULL ,
                    `force` TINYINT NULL DEFAULT NULL ,
                    `disable` TINYINT NULL DEFAULT NULL,
                    `price` FLOAT NULL NULL DEFAULT NULL,
                    `text` VARCHAR(128) NULL DEFAULT NULL,
                    UNIQUE KEY `id_product` (`id_product`, `id_product_attribute`, `id_lang`)
                    ) DEFAULT CHARSET=utf8';

            if (!Db::getInstance()->execute($sql)) {
                $errors .= 'ERROR: '.$sql;
                $pass = false;
            }
        }

        if (!isset($tables[_DB_PREFIX_.self::TABLE_PRICEMINISTER_ORDERS])) {
            $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_ORDERS.'` (
					`id_order` INT NOT NULL ,
					`mp_order_id` VARCHAR( 32 ) NOT NULL,
					`shipping_type` VARCHAR( 32 ) NOT NULL,
					`relay` VARCHAR( 32 ) NOT NULL,
					PRIMARY KEY (  `id_order` ) ,
					UNIQUE (`mp_order_id`)
					) DEFAULT CHARSET=utf8;';

            if (!Db::getInstance()->execute($sql)) {
                $errors .= 'ERROR: '.$sql;
                $pass = false;
            }
        }

        if (!isset($tables[_DB_PREFIX_.self::TABLE_PRICEMINISTER_CONFIGURATION])) {
            $sql = '
                            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_CONFIGURATION.'` (
                            `conf_id` int(11) NOT NULL,
                            `conf_type` VARCHAR( 60 ) NOT NULL ,
                            `field_group` VARCHAR( 60 ) NOT NULL ,
                            `field_name` VARCHAR( 240 ) NOT NULL DEFAULT "",
                            `field_idx` int(11) NOT NULL DEFAULT 0,
                            `field_value` VARCHAR( 1024 ) NULL DEFAULT NULL,
                            `field_multiple` CHAR(1) NOT NULL DEFAULT "N",
                            PRIMARY KEY (`conf_id`, `conf_type` (25), `field_group` (25), `field_name` (25), `field_idx`)
                            ) DEFAULT CHARSET=utf8;';

            if (!Db::getInstance()->execute($sql)) {
                $errors .= 'ERROR: '.$sql.'<br />\n';
                $pass = false;
            }
        }

        if (!isset($tables[_DB_PREFIX_.self::TABLE_PRICEMINISTER_MODELS])) {
            $sql = '
                            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_MODELS.'` (
                            `model_id` int(11) NOT NULL ,
                            `field_group` VARCHAR( 60 ) NOT NULL ,
                            `field_name` VARCHAR( 240 ) NOT NULL DEFAULT "" ,
                            `field_idx` int(11) NOT NULL DEFAULT 0,
                            `field_value` VARCHAR( 1024 ) NULL DEFAULT NULL,
                            `field_multiple` CHAR( 60 ) NOT NULL DEFAULT "N",
                            PRIMARY KEY (`model_id`, `field_group`, `field_name`, `field_idx`)
                            ) DEFAULT CHARSET=utf8 ;';

            if (!Db::getInstance()->execute($sql)) {
                $errors .= 'ERROR: '.$sql.'<br />\n';
                $pass = false;
            }
        }

        if (!isset($tables[_DB_PREFIX_.self::TABLE_PRICEMINISTER_MAPPINGS])) {
            $sql = '
                            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_MAPPINGS.'` (
                            `id_pm_mapping` int(10) unsigned NOT NULL auto_increment,
                            `id_prestashop` int(11) NOT NULL ,
                            `id_priceminister` VARCHAR( 32 ) NULL DEFAULT NULL ,
                            `type` int(2) DEFAULT 1 ,
                            `default_value` VARCHAR( 32 ) NULL DEFAULT NULL ,
                            PRIMARY KEY (`id_pm_mapping`),
                            UNIQUE KEY `pm_mapping_uk` (`id_prestashop` ,`id_priceminister`, `type`)
                            ) DEFAULT CHARSET=utf8 ;';

            if (!Db::getInstance()->execute($sql)) {
                $errors .= 'ERROR: '.$sql.'<br />\n';
                $pass = false;
            }
        }

        if (!isset($tables[_DB_PREFIX_.self::TABLE_PRICEMINISTER_MAPPINGS_DET])) {
            $sql = '
                            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::TABLE_PRICEMINISTER_MAPPINGS_DET.'` (
                            `id_pm_mapping` int(10) unsigned NOT NULL,
                            `ps_value` VARCHAR( 60 ) NULL DEFAULT NULL ,
                            `pm_value` VARCHAR( 60 ) NULL DEFAULT NULL ,
                            UNIQUE KEY `pm_mapping_child_uk` (`id_pm_mapping` ,`ps_value`, `pm_value`)
                            ) DEFAULT CHARSET=utf8 ;';

            if (!Db::getInstance()->execute($sql)) {
                $errors .= 'ERROR: '.$sql.'<br />\n';
                $pass = false;
            }
        }

        if ($errors && Tools::strlen($errors)) {
            ConfigureMessage::error($errors);
        }

        if ($errors) {
            $this->_postErrors[] = $errors;
        }

        return ($pass);
    }

    private function _createCustomer()
    {
        $pass = true;

        if (($id_customer = (int)Configuration::get(self::CONFIG_PM_CUSTOMER_ID))) {
            $customer = new Customer($id_customer);

            if (Validate::isLoadedObject($customer)) {
                return ($customer->id);
            }
        }

        // Fakemail
        //
        $var = explode('@', Configuration::get('PS_SHOP_EMAIL'));
        $email = 'no-reply-'.rand(500, 9999999999).'@'.$var[1];

        $customer = new Customer();
        $customer->id_gender = 1;
        $customer->firstname = 'Rakuten France';
        $customer->lastname = 'Rakuten France';
        $customer->email = $email;
        $customer->newsletter = false;
        $customer->optin = false;
        $customer->passwd = md5(rand(50000000, 9999999999));
        $customer->active = true;

        $pass = $customer->add();

        if (Validate::isLoadedObject($customer)) {
            Configuration::updateValue(PriceMinister::CONFIG_PM_CUSTOMER_ID, $customer->id);

            return ($customer->id);
        }

        return ($pass);
    }

    public function _tabSetup($action)
    {
        require_once(dirname(__FILE__).'/classes/shared/tab.class.php');
        $pass = true;

        // Adding Tab
        switch ($action) {
            case self::ADD :
            case self::UPDATE :
            case self::REMOVE :
                $order_parent_tab = $this->ps17x ? 'AdminParentOrders' : 'AdminOrders';

                if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                    $pass = $pass && CommonServicesTab::Setup($action, 'AdminPriceMinisterProducts', $this->displayName, 'AdminCatalog');
                    $pass = $pass && CommonServicesTab::Setup($action, 'AdminPriceMinisterOrders', $this->displayName, $order_parent_tab);
                } else {
                    $pass = $pass && CommonServicesTab::Setup($action, 'PriceMinisterProduct', $this->displayName, 'AdminCatalog');
                    $pass = $pass && CommonServicesTab::Setup($action, 'PriceMinisterOrder', $this->displayName, 'AdminOrders');
                }
                break;
            default :
                return (false);
        }

        return ($pass);
    }

    private function _hookSetup($action)
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $expectedHooks = array('updateCarrier', 'adminOrder', 'backOfficeHeader');
        } else {
            $expectedHooks = array(
                'actionCarrierUpdate',
                'displayAdminOrder',
                'displayAdminProductsExtra',
                'actionProductUpdate',
                'actionUpdateQuantity',
                'actionValidateOrder'
            );
        }
        // GDPR compliant
        $expectedHooks[] = 'registerGDPRConsent';
        $expectedHooks[] = 'actionDeleteGDPRCustomer';
        $expectedHooks[] = 'actionExportGDPRData';

        $hook_to_remove = array('displayBackOfficeHeader');
        $pass = true;

        if (in_array($action, array(self::REMOVE, self::UPDATE))) {
            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                $expectedHooks = array_merge($expectedHooks, $hook_to_remove);
            }

            foreach ($expectedHooks as $expectedHook) {
                if ($this->isRegisteredInHook($expectedHook)) {
                    if (!$this->unregisterHook($expectedHook)) {
                        $this->_postErrors[] = $this->l('Unable to Unregister Hook').':'.$expectedHook;
                        $pass = false;
                    }
                }
            }

            $key_hook_to_remove = array_search('displayBackOfficeHeader', $expectedHooks);
            if ($key_hook_to_remove !== false) {
                unset($expectedHooks[$key_hook_to_remove]);
            }
        }
        if (in_array($action, array(self::ADD, self::UPDATE))) {
            foreach ($expectedHooks as $expectedHook) {
                if (!$this->isRegisteredInHook($expectedHook)) {
                    if (!$this->registerHook($expectedHook)) {
                        $this->_postErrors[] = $this->l('Unable to Register Hook').':'.$expectedHook;
                        $pass = false;
                    }
                }
            }
        }

        return ($pass);
    }

    public function uninstall()
    {
        $pass = true;

        // Remove Hooks
        //
        $this->_hookSetup(self::REMOVE);

        if (!parent::uninstall() || !$this->_tabSetup(self::REMOVE) || !$this->_deleteCustomer() || !$this->_removeTables()) {
            $pass = false;
        }

        foreach ($this->_config as $key => $value) {
            if (!Configuration::deleteByName($key)) {
                $pass = false;
            }
        }

        return ($pass);
    }

    private function _deleteCustomer()
    {
        $customer = new Customer();
        $customer->id = Configuration::get(self::CONFIG_PM_CUSTOMER_ID);

        return ($customer->delete());
    }

    private function _removeTables()
    {
        $tables = array(
            _DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_OPTION,
            _DB_PREFIX_.self::TABLE_PRICEMINISTER_MODELS,
            _DB_PREFIX_.self::TABLE_PRICEMINISTER_MAPPINGS,
            _DB_PREFIX_.self::TABLE_PRICEMINISTER_MAPPINGS_DET,
            _DB_PREFIX_.self::TABLE_PRICEMINISTER_CONFIGURATION
        );
        $sql = '';
        foreach ($tables as $table) {
            $sql .= 'DROP TABLE  `'.$table.'` ; ';
        }

        return (Db::getInstance()->execute($sql));
    }

    public function getContent()
    {
        Configuration::UpdateValue('PM_INSTANT_TOKEN', $instant_token = md5(_PS_ROOT_DIR_._PS_VERSION_.(isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time())), false, 0, 0);

        require_once(dirname(__FILE__).'/classes/priceminister.context.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.tools.class.php');

        require_once(dirname(__FILE__).'/classes/shared/configure_tab.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.support.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.mappings.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.models.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.profiles.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.categories.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.prof2categories.class.php');

        require_once(dirname(__FILE__).'/classes/priceminister.api.webservices.php');
        require_once(dirname(__FILE__).'/classes/priceminister.form.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.api.products.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.configuration.class.php');

        $this->context->smarty->caching = false;
        $this->context->smarty->force_compile = true;

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $this->context->controller->addJqueryUI('ui.sortable');
        }

        $this->versionCheck();

        if ($this->_categories == null) {
            $this->_categories = Category::getCategories((int)$this->id_lang, $this->onlyActivesCategories);
        }

        $view_params = array();

        $view_params['errors_list'] = array();
        $view_params['request_uri'] = Tools::htmlentitiesUTF8(filter_input(INPUT_SERVER, 'REQUEST_URI'));
        $view_params['images_url'] = $this->images;
        $view_params['js_url'] = $this->js;
        $view_params['module_url'] = $this->url;
        $view_params['module_path'] = $this->path;
        $view_params['module_description'] = $this->description;
        $view_params['version'] = $this->version;
        $view_params['cronjobs_url'] = $this->url.'functions/cronjobs.php';

        $support = new PriceMinisterSupport($this->id_lang);
        $view_params['support_language'] = $support->lang;

        $this->debugDetails->configuration('Before PS1.4 compatibility');
        // July-10-2018: PS1.4 compatibility
        $this->_compat14();
        $toolsReflect = new \ReflectionClass('Tools');
        $this->debugDetails->configuration('Calling', $toolsReflect->getFileName(), print_r($_POST, true));
        if (Tools::isSubmit('submit')) {
            $this->debugDetails->configuration('Submitting data...');
            $this->_postValidation();
            $this->_postProcess();
        }

        $this->_loadSettings();
        $this->_load_attributes();
        $this->_load_features();

        $view_params['selected_tab'] = $this->_selected_tab();
        $view_params['ps_version'] = _PS_VERSION_;

        $view_params['selected_tab_priceminister'] = $view_params['selected_tab'] == 'priceminister' ? 'selected' : '';
        $view_params['selected_tab_informations'] = $view_params['selected_tab'] == 'informations' ? 'selected' : '';
        $view_params['selected_tab_credentials'] = $view_params['selected_tab'] == 'credentials' ? 'selected' : '';
        $view_params['selected_tab_categories'] = $view_params['selected_tab'] == 'categories' ? 'selected' : '';
        $view_params['selected_tab_profiles'] = $view_params['selected_tab'] == 'profiles' ? 'selected' : '';
        $view_params['selected_tab_models'] = $view_params['selected_tab'] == 'models' ? 'selected' : '';
        $view_params['selected_tab_parameters'] = $view_params['selected_tab'] == 'parameters' ? 'selected' : '';
        $view_params['selected_tab_shipping'] = $view_params['selected_tab'] == 'shipping' ? 'selected' : '';
        $view_params['selected_tab_mappings'] = $view_params['selected_tab'] == 'mappings' ? 'selected' : '';
        $view_params['selected_tab_filters'] = $view_params['selected_tab'] == 'filters' ? 'selected' : '';
        $view_params['selected_tab_orders'] = $view_params['selected_tab'] == 'orders' ? 'selected' : '';
        $view_params['selected_tab_cron'] = $view_params['selected_tab'] == 'cron' ? 'selected' : '';

        $view_params['priceminister'] = $this->_priceminister();
        $view_params['informations'] = $this->_informations();
        $view_params['credentials'] = $this->_credentials();
        $view_params['categories'] = $this->_categories();
        $view_params['profiles'] = $this->_profiles();
        $view_params['models'] = $this->_models();
        $view_params['parameters'] = $this->_parameters();
        $view_params['shippings'] = $this->_shipping();
        $view_params['mapping'] = $this->_mappings();
        $view_params['filters'] = $this->_filters();
        $view_params['orders'] = $this->_orders();
        $view_params['repricing'] = $this->_repricing();
        $view_params['cron'] = $this->_cron();
        $view_params['rkt_detailed_debug'] = $this->debugDetails->getAll();

        $alert_class = array();
        $alert_class['danger'] = $this->ps16x ? 'alert alert-danger' : 'error';
        $alert_class['warning'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        $alert_class['success'] = $this->ps16x ? 'alert alert-success' : 'conf';
        $alert_class['info'] = $this->ps16x ? 'alert alert-info' : 'hint';

        $tab_list = array();
        $tab_list[] = array('id' => 'priceminister', 'img' => 'priceminister', 'name' => $this->l('RakutenFrance'), 'selected' => true);
        $tab_list[] = array('id' => 'informations', 'img' => 'information', 'name' => 'Informations', 'selected' => false);
        $tab_list[] = array('id' => 'credentials', 'img' => 'account_functions', 'name' => $this->l('Authentication'), 'selected' => false);
        $tab_list[] = array('id' => 'parameters', 'img' => 'cog_edit', 'name' => $this->l('Parameters'), 'selected' => false);
        $tab_list[] = array('id' => 'shipping', 'img' => 'lorry', 'name' => $this->l('Shipping'), 'selected' => false);
        $tab_list[] = array('id' => 'models', 'img' => 'shapes', 'name' => $this->l('Models'), 'selected' => false);
        $tab_list[] = array('id' => 'profiles', 'img' => 'profiles', 'name' => $this->l('Profiles'), 'selected' => false);
        $tab_list[] = array('id' => 'categories', 'img' => 'categories', 'name' => $this->l('Categories'), 'selected' => false);
        $tab_list[] = array('id' => 'mappings', 'img' => 'mapping', 'name' => $this->l('Mappings'), 'selected' => false);
        $tab_list[] = array('id' => 'filters', 'img' => 'filter', 'name' => $this->l('Filters'), 'selected' => false);
        $tab_list[] = array('id' => 'orders', 'img' => 'calculator', 'name' => $this->l('Orders'), 'selected' => false);
        $tab_list[] = array('id' => 'repricing', 'img' => 'repricing', 'name' => 'Repricing', 'selected' => false);
        $tab_list[] = array('id' => 'cron', 'img' => 'clock', 'name' => $this->l('Cron'), 'selected' => false);

        $this->context->smarty->assign($view_params);
        $this->context->smarty->assign('alert_class', $alert_class);
        $this->context->smarty->assign('ps16x', $this->ps16x);

        $this->_html .= ConfigureMessage::display();
        $this->_html .= $this->context->smarty->fetch($this->path.'views/templates/admin/configure/header.tpl');
        $this->_html .= ConfigureTab::generateTabs($tab_list);
        $this->_html .= $this->context->smarty->fetch($this->path.'views/templates/admin/configure/priceminister.tpl');

        return ($this->_html);
    }

    public function versionCheck()
    {
        // Oct-18-2018: Tran removes unused code
        ConfigureMessage::info('<ul>
                                <li>'.$this->l('Be effective, do not waste your time :').'</li>
                                <li>'.$this->l('For beginning, Supposing you have basis knowledge on it, please use basis functions').'</li>
                                <li>'.$this->l('Do not try to hard tune the module. Almost all parameters are correctly configured by default.').'</li>
                              </ul>');
    }

    private function _postValidation()
    {
        $pm_credentials = Tools::getValue('pm_credentials');
        $pm_parameters = Tools::getValue('pm_parameters');
        $pm_orders = Tools::getValue('pm_orders');
        $pm_shipping = Tools::getValue('pm_shipping');
        $pm_profiles = Tools::getValue('pm_profiles');
        $pm_categories = Tools::getValue('category');
        $pm_profile_to_category = Tools::getValue('profile2category');

        $condition_map = Tools::getValue('condition_map');

        if (is_array($pm_credentials) && empty($pm_credentials['login'])) {
            if (empty($pm_credentials['login'])) {
                ConfigureMessage::error($this->l('You must fill your Login'));
            }
            if (empty($pm_credentials['token'])) {
                ConfigureMessage::error($this->l('You must fill your Token'));
            }
            if (Tools::strlen($pm_credentials['token']) != 32) {
                ConfigureMessage::error($this->l('The token must contains 32 characters'));
            }
        }

        if (is_array($pm_orders) && empty($pm_orders['status_incoming'])) {
            ConfigureMessage::error($this->l('You must choose an order state'));
        }

        if (is_array($pm_orders) && empty($pm_orders['status_sent'])) {
            ConfigureMessage::error($this->l('You must choose a sent order state'));
        }

        if (is_array($pm_parameters) && empty($pm_parameters['condition_map']['N'])) {
            ConfigureMessage::error($this->l('The condition map must be filled'));
        }

        $pass = true;

        if (!max((array)$pm_shipping['ps_carriers'])) {
            $pass = false;
        }

        if (isset($pm_shipping['shipping_per_item']) && (bool)$pm_shipping['shipping_per_item']) {
            if (!isset($pm_shipping['shipping_table']['carrier']) || !count($pm_shipping['shipping_table']['carrier'])) {
                $pass = false;
            }

            if (!isset($pm_shipping['shipping_table']['zone']) || !count($pm_shipping['shipping_table']['zone'])) {
                $pass = false;
            }

            if (!$pass) {
                ConfigureMessage::error($this->l('You must configure your shipping matrix'));
            }
        }

        if (is_array($pm_profiles) && count($pm_profiles)) {
            foreach ($pm_profiles as $p_key => $pm_profile) {
                if (isset($pm_profile['price_rule']) && is_array($pm_profile['price_rule']) && count($pm_profile['price_rule'])) {
                    $check_price_rules = $this->checkPriceRules($pm_profile['price_rule']);

                    if (Tools::strlen($check_price_rules)) {
                        //non valid data is not saved, only for profile with errors
                        $pm_profiles[$p_key]['price_rule'] = array('type' => null, 'rule' => array());
                        ConfigureMessage::error(sprintf('%s: "%s" - %s: %s', $this->l('Profile'), $pm_profile['name'], $this->l('Price Rule'), $check_price_rules));
                    }
                }
            }
            //only valid data is considered for being saved
            $this->post_data['pm_profiles'] = $pm_profiles;
        }

        if (!is_array($pm_categories) || !count($pm_categories)) {
            ConfigureMessage::error($this->l('You must select at least one category in the Categories tab.'));
        } elseif (!count(array_filter($pm_profile_to_category))) {
            ConfigureMessage::error($this->l('You must select a profile for your category in the Categories tab.'));
        } else {
            foreach ($pm_categories as $categeory) {
                if (!isset($pm_profile_to_category[$categeory]) || !$pm_profile_to_category[$categeory]) {
                    ConfigureMessage::error(sprintf('%s "%s" %s.', $this->l('You must set a profile for the category'), PriceMinisterCategories::getCategoryName($categeory), $this->l('in the Categories tab')));
                }
            }
        }

        if (!($id_customer = $this->_createCustomer())) {
            ConfigureMessage::error($this->l('Unable to install: _createCustomer'));
        } else {
            require_once(_PS_MODULE_DIR_.'priceminister/classes/priceminister.address.class.php');
            PriceMinisterAddress::createShippingLocations($id_customer);
        }

        $this->_hookSetup(self::UPDATE);
        $this->_tabSetup(self::UPDATE);

        $this->debugDetails->configuration('Validated submitted data');
    }

    private function checkPriceRules($price_rules)
    {
        if (!is_array($price_rules)) {
            return ($this->l('An error occured with price rules, not an array.'));
        }

        $error = '';

        $rule = isset($price_rules['rule']) ? $price_rules['rule'] : null;
        $type = isset($price_rules['type']) ? $price_rules['type'] : null;

        if (!is_array($rule['from']) || !count($rule['from']) || !is_array($rule['to']) || !count($rule['to'])) {
            return ($error);
        }

        if (!reset($rule['from']) && !reset($rule['to'])) {
            return ($error);
        }

        if ((reset($rule['from']) && !reset($rule['to'])) || (!reset($rule['from']) && reset($rule['from']) != '0' && reset($rule['to']))) {
            $error .= sprintf('%s => %s<br>', $this->l('Price rule incomplete'), $this->l('Missing range element'));

            return ($error);
        }

        $prev_from = -1;
        $prev_to = -1;

        foreach ($rule['from'] as $key => $val) {
            if (max($prev_from, $val) == $prev_from) {
                $error .= sprintf('%s => %s %d<br>', $this->l('Your range FROM is lower than the previous one'), $this->l('Rule ligne'), $key + 1);
                break;
            } else {
                if ($rule['to'][$key] && max($prev_to, $rule['to'][$key]) == $prev_to) {
                    $error .= sprintf('%s => %s %d<br>', $this->l('Your range TO is lower than the previous one'), $this->l('Rule ligne'), $key + 1);
                    break;
                } else {
                    if ($rule['to'][$key] && max($rule['to'][$key], $val) == $val) {
                        $error .= sprintf('%s => %s %d<br>', $this->l('Your range TO is lower than your range FROM'), $this->l('Rule ligne'), $key + 1);
                        break;
                    }
                }
            }

            $prev_from = $val;
            $prev_to = $rule['to'][$key];
        }

        return ($error);
    }

    private function _postProcess()
    {
        require_once(dirname(__FILE__).'/classes/priceminister.context.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.repricing.class.php');
        PriceMinisterContext::save($this->context);

        // Tables Updates
        if (!$this->_installTables()) {
            ConfigureMessage::error('_installTables failed');
        }

        $debug = Tools::getValue('debug');

        $pm_credentials = Tools::getValue('pm_credentials');
        $pm_parameters = Tools::getValue('pm_parameters');
        $pm_orders = Tools::getValue('pm_orders');
        $pm_filters = Tools::getValue('pm_filters');
        $pm_shipping = Tools::getValue('pm_shipping');
        $pm_profiles = isset($this->post_data['pm_profiles']) ? $this->post_data['pm_profiles'] : array(); //Tools::getValue('pm_profiles');
        $pm_categories = Tools::getValue('category');
        $profile2category = Tools::getValue('profile2category');

        Configuration::updateValue(self::CONFIG_PM_DEBUG, (bool)$debug);
        Configuration::updateValue(self::CONFIG_PM_CREDENTIALS, is_array($pm_credentials) ? serialize($pm_credentials) : null);
        Configuration::updateValue(self::CONFIG_PM_PARAMETERS, is_array($pm_parameters) ? serialize($pm_parameters) : null);
        Configuration::updateValue(self::CONFIG_PM_ORDERS, is_array($pm_orders) ? serialize($pm_orders) : null);
        Configuration::updateValue(self::CONFIG_PM_FILTERS, is_array($pm_filters) ? serialize($pm_filters) : null);
        Configuration::updateValue(self::CONFIG_PM_SHIPPING, is_array($pm_shipping) ? serialize($pm_shipping) : null);

        if (is_array($pm_credentials) && !empty($pm_credentials['login']) && Tools::strlen($pm_credentials['token']) == 32) {
            Configuration::updateValue(self::CONFIG_PM_CRON_TOKEN, md5($pm_credentials['login'].$pm_credentials['token']));
        }

        // MODELS
        $pm_models = Tools::getValue('models', array());
        if (isset($pm_models['_key_'])) {
            unset($pm_models['_key_']);
        }

        // Models
        PriceMinisterModels::deleteAll();
        PriceMinisterMappings::deleteAll();

        if (count($pm_models) > 0) {
            $noidx = 0;
            $main_group = PriceMinisterModels::MAIN_GROUP;
            $pm_groups = PriceMinisterModels::$pm_groups;
            foreach ($pm_models as $key => $model) {
                //key = model_id
                //Model general fields
                PriceMinisterModels::save($key, $main_group, 'name', $noidx, isset($model['name']) ? $model['name'] : '', 'N');
                PriceMinisterModels::save($key, $main_group, 'product_type', $noidx, isset($model['product_type']) ? $model['product_type'] : '', 'N');

                foreach ($pm_groups as $group) {
                    if (isset($model[$group])) {
                        if ($group == 'campaigns') {
                            foreach ($model[$group] as $field_name => $value) {
                                if (is_array($value)) {
                                    foreach ($value as $idx => $val) {
                                        PriceMinisterModels::save($key, $group, $field_name, $idx, $val, 'S');
                                    }
                                } else {
                                    PriceMinisterModels::save($key, $group, $field_name, $noidx, $value, 'N');
                                }
                            }
                        } else {
                            foreach ($model[$group] as $field_name => $value) {
                                if (is_array($value)) {
                                    //this is when the element is a multiple select element
                                    //when this happens:  KEY=element and VALUE=boolean
                                    $idx = 0;
                                    foreach ($value as $name => $selected) {
                                        PriceMinisterModels::save($key, $group, $field_name, $idx, $name, 'S');
                                        $idx++;
                                    }
                                } else {
                                    PriceMinisterModels::save($key, $group, $field_name, $noidx, $value, 'N');
                                }
                            }
                        }
                    }
                }
            }
        }

        PriceMinisterProfiles::deleteAll();
        //profiles
        foreach ($pm_profiles as $profile_id => $profile) {
            $profile_options = array(
                'images_optionnals' => isset($profile['images_optionnals']) ? $profile['images_optionnals'] : '0',
                'name_with_attributes' => isset($profile['name_with_attributes']) ? $profile['name_with_attributes'] : '0',
                'short_long_description' => isset($profile['short_long_description']) ? $profile['short_long_description'] : '0',
                'filter_description' => isset($profile['filter_description']) ? $profile['filter_description'] : '0',
                'repricing_strategie' => isset($profile['repricing_strategie']) && $profile['repricing_strategie'] ? $profile['repricing_strategie'] : '0',
                'no_ean' => isset($profile['no_ean']) && $profile['no_ean'] ? $profile['no_ean'] : '0'
            );
            $model = isset($profile['model']) ? $profile['model'] : '';
            PriceMinisterProfiles::save($profile_id, $profile['name'], $model, $profile['price_rule'], $profile_options);
        }

        // Repricing
        $strategies = Tools::getValue('strategies');
        PriceMinisterRepricing::deleteAll();
        if (isset($strategies['_key_'])) {
            unset($strategies['_key_']);
        }
        if (is_array($strategies) && count($strategies)) {
            PriceMinisterRepricing::save($strategies);
        }

        //Categories
        PriceMinisterCategories::deleteAll();
        PriceMinisterCategories::save($pm_categories);

        //profiles to categories
        PriceMinisterProfiles2Categories::deleteAll();
        PriceMinisterProfiles2Categories::save($profile2category);

        // Array of the form id_attribute_group=>(product_type1, product_type2, ... product_typen)
        $attr_mapping_table = array();

        // Array of the form id_feature=>(product_type1, product_type2, ... product_typen)
        $feat_mapping_table = array();

        $attr_lists = array();
        $feat_lists = array();
        $default_values = array();

        //mapping_table
        foreach ($pm_models as $model) {
            if (!is_array($model)) {
                continue;
            }
            foreach ($model as $model_detail) {
                if (!is_array($model_detail)) {
                    continue;
                }
                foreach ($model_detail as $key => $val) {
                    if (preg_match('/(_opt)$/', $key)) {
                        $base = preg_replace('/(_opt)$/', '', $key);
                        $key_opt = $base.'_opt';
                        $key_attr = $base.'_attr';
                        $key_feat = $base.'_feat';
                        $key_list = $base.'_list';
                        $key_def = $base.'_def';

                        //prevent any mistake
                        if (!isset($model_detail[$key]) || !isset($model_detail[$key_opt]) || !isset($model_detail[$key_attr]) || !isset($model_detail[$key_feat]) || !$base) {
                            continue;
                        }

                        //Attribute mapping
                        if ($model_detail[$key_attr] != '') {
                            $attr_mapping_table =
                                self::addMappingValue($attr_mapping_table, $model_detail[$key_attr], $base);
                            $default_values[$base] = isset($model_detail[$key_def]) ? $model_detail[$key_def] : '';
                            if (isset($model_detail[$key_list])) {
                                $attr_lists[$base] = $model_detail[$key_list];
                            }
                        } elseif ($model_detail[$key_feat] != '') {
                            //Features Mapping
                            $feat_mapping_table = self::addMappingValue($feat_mapping_table, $model_detail[$key_feat], $base);
                            $default_values[$base] = $model_detail[$key_def];

                            if (isset($model_detail[$key_list])) {
                                $feat_lists[$base] = $model_detail[$key_list];
                            }
                        }
                    }
                }
            }
        }

        $mapping = Tools::getValue('mapping', array(
            'prestashop' => array(),
            'priceminister' => array()
        ));

        $features_mapping = Tools::getValue('features_mapping', array(
            'prestashop' => array(),
            'priceminister' => array()
        ));

        $this->_filter_mappings($mapping);
        $this->_filter_mappings($features_mapping);

        $this->_save_mappings($attr_mapping_table, $mapping, PriceMinisterMappings::ATTRIBUTE_TYPE, $default_values);
        $this->_save_mappings($feat_mapping_table, $features_mapping, PriceMinisterMappings::FEATURE_TYPE, $default_values);

        if (!ConfigureMessage::hasErrorMessage()) {
            ConfigureMessage::success($this->l('Configuration updated'));
        }

        if ($debug) {
            ConfigureMessage::success(sprintf('Memory Peak: %.02f MB - Post Count: %s', memory_get_peak_usage() / 1024 / 1024, count($_POST, COUNT_RECURSIVE)));
        }

        //reset data
        $this->post_data['pm_profiles'] = array();
        $this->debugDetails->configuration('Saved submitted data');
    }

    public static function addMappingValue($array, $id, $product_type)
    {
        if (!is_array($array)) {
            $array = array();
        }

        if (!isset($array[$id])) {
            $array[$id] = array();
        }

        if (!in_array($product_type, $array[$id])) {
            $array[$id][] = $product_type;
        }

        return $array;
    }

    private function _filter_mappings(&$mapping)
    {
        // Remove empty entries
        //
        if (is_array($mapping) && array_key_exists('priceminister', $mapping) && array_key_exists('prestashop', $mapping)) {
            foreach (array_keys($mapping['prestashop']) as $key1) {
                if (is_array($mapping['prestashop'][$key1])) {
                    foreach ($mapping['prestashop'][$key1] as $key2 => $item) {
                        foreach ($item as $idx => $item_det) {
                            if ($item_det == '') {
                                unset($mapping['prestashop'][$key1][$key2][$idx]);
                            }
                        }
                        if (!isset($mapping['priceminister'][$key1][$key2]) || empty($mapping['priceminister'][$key1][$key2])) {
                            unset($mapping['priceminister'][$key1][$key2]);
                            unset($mapping['prestashop'][$key1][$key2]);
                        }
                        if (!isset($mapping['priceminister'][$key1]) || !count($mapping['prestashop'][$key1])) {
                            unset($mapping['priceminister'][$key1]);
                            unset($mapping['prestashop'][$key1]);
                        }
                    }
                }
            }
        } else {
            $mapping = array();
        }
    }

    private function _save_mappings($mapping_table, $mapping_detail, $type, $default_values)
    {
        //save mapping
        foreach ($mapping_table as $id_ps => $id_pm_values) {
            //save parent data
            foreach ($id_pm_values as $id_pm) {
                $default_value = isset($default_values[$id_pm]) ? $default_values[$id_pm] : '';
                $id_pm_mapping = PriceMinisterMappings::add($id_ps, $id_pm, $type, $default_value);
                //save children data
                if ($id_pm_mapping) {
                    $ps_values = isset($mapping_detail['prestashop'][$id_ps]) && isset($mapping_detail['prestashop'][$id_ps][$id_pm]) ? $mapping_detail['prestashop'][$id_ps][$id_pm] : array();
                    $pm_values = isset($mapping_detail['priceminister'][$id_ps]) && isset($mapping_detail['priceminister'][$id_ps][$id_pm]) ? $mapping_detail['priceminister'][$id_ps][$id_pm] : array();
                    foreach ($ps_values as $idx_ps_value => $ps_value) {
                        if ($ps_value == '') {
                            continue;
                        }

                        $pm_value = isset($pm_values[$idx_ps_value]) ? $pm_values[$idx_ps_value] : '';

                        if ($pm_value == '') {
                            continue;
                        }

                        PriceMinisterMappings::addDetail($id_pm_mapping, $ps_value, $pm_value);
                    }
                }
            }
        }
    }

    private function _loadSettings()
    {
        //if already filled
        if (count($this->config) > 0) {
            return;
        }

        $this->config['api'] = PriceMinisterTools::Auth();

        $this->config['pm_lists'] = array();

        // Mappings
        $attr_mapping_table = PriceMinisterMappings::getMappingTable(PriceMinisterMappings::ATTRIBUTE_TYPE);
        $feat_mapping_table = PriceMinisterMappings::getMappingTable(PriceMinisterMappings::FEATURE_TYPE);

        $attributes_mapping_left = array();
        $attributes_mapping_right = array();

        foreach ($attr_mapping_table as $ps_value => $pm_values) {
            $attributes_mapping_left[$ps_value] = array();
            $attributes_mapping_right[$ps_value] = array();

            foreach ($pm_values as $id_mapping => $pm_value) {
                $attributes_mapping_left[$ps_value][$pm_value] = array();
                $attributes_mapping_right[$ps_value][$pm_value] = array();
                $details = PriceMinisterMappings::getDetails($id_mapping);
                if (isset($details[0])) {
                    foreach ($details[0] as $detail) {
                        $attributes_mapping_left[$ps_value][$pm_value][] = isset($detail['ps_value']) ? $detail['ps_value'] : '';
                        $attributes_mapping_right[$ps_value][$pm_value][] = isset($detail['pm_value']) ? $detail['pm_value'] : '';
                    }
                }
            }
        }

        $features_mapping_left = array();
        $features_mapping_right = array();

        foreach ($feat_mapping_table as $ps_value => $pm_values) {
            $features_mapping_left[$ps_value] = array();
            $features_mapping_right[$ps_value] = array();

            foreach ($pm_values as $id_mapping => $pm_value) {
                $features_mapping_left[$ps_value][$pm_value] = array();
                $features_mapping_right[$ps_value][$pm_value] = array();
                $details = PriceMinisterMappings::getDetails($id_mapping);
                if (isset($details[0])) {
                    foreach ($details[0] as $detail) {
                        $features_mapping_left[$ps_value][$pm_value][] = isset($detail['ps_value']) ? $detail['ps_value'] : '';
                        $features_mapping_right[$ps_value][$pm_value][] = isset($detail['pm_value']) ? $detail['pm_value'] : '';
                    }
                }
            }
        }

        if (is_array($attr_mapping_table) && is_array($feat_mapping_table)) {
            $this->config['attr_mapping_table'] = $attr_mapping_table;
            $this->config['feat_mapping_table'] = $feat_mapping_table;
        } else {
            $this->config['attr_mapping_table'] = null;
            $this->config['feat_mapping_table'] = null;
        }

        if (is_array($attributes_mapping_left) && is_array($attributes_mapping_right)) {
            $this->config['attributes_mapping_left'] = $attributes_mapping_left;
            $this->config['attributes_mapping_right'] = $attributes_mapping_right;
        } else {
            $this->config['attributes_mapping_left'] = null;
            $this->config['attributes_mapping_right'] = null;
        }

        if (is_array($features_mapping_left) && is_array($features_mapping_right)) {
            $this->config['features_mapping_left'] = $features_mapping_left;
            $this->config['features_mapping_right'] = $features_mapping_right;
        } else {
            $this->config['features_mapping_left'] = null;
            $this->config['features_mapping_right'] = null;
        }
    }

    private function _load_attributes()
    {
        $languages = Language::getLanguages(false);

        self::$attributes_groups = array();
        self::$attributes = array();

        foreach ($languages as $language) {
            $id_lang = $language['id_lang'];
            if ($id_lang != $this->id_lang) {
                continue;
            }

            $attributes_groups = AttributeGroup::getAttributesGroups($id_lang);

            if (is_array($attributes_groups) && count($attributes_groups)) {
                self::$attributes_groups[$id_lang] = array();

                foreach ($attributes_groups as $attribute_group) {
                    $id_attribute_group = (int)$attribute_group['id_attribute_group'];

                    self::$attributes_groups[$id_lang][$id_attribute_group] = $attribute_group;
                }
            }

            $attributes = Attribute::getAttributes($id_lang, true);

            if (is_array($attributes) && count($attributes)) {
                self::$attributes[$id_lang] = array();

                foreach ($attributes as $attribute) {
                    $id_attribute_group = (int)$attribute['id_attribute_group'];
                    $id_attribute = (int)$attribute['id_attribute'];

                    self::$attributes[$id_lang][$id_attribute_group][$id_attribute] = $attribute;
                }
            }
        }
    }

    private function _load_features()
    {
        $languages = Language::getLanguages(false);

        foreach ($languages as $language) {
            $id_lang = $language['id_lang'];

            foreach (Feature::getFeatures($id_lang) as $feature) {
                $id_feature = (int)$feature['id_feature'];

                $features_values = FeatureValue::getFeatureValuesWithLang($id_lang, $id_feature);
                $custom_features_values = FeatureValue::getFeatureValues($id_feature);

                if (is_array($features_values) && count($features_values)) {
                    $feature['is_color_feature'] = false; // Used by models and Mapping

                    self::$features[$id_lang][$id_feature] = $feature;

                    foreach ($features_values as $feature_value) {
                        self::$features_values[$id_lang][$id_feature][$feature_value['id_feature_value']] = $feature_value;
                    }
                } elseif (is_array($custom_features_values) && count($custom_features_values)) {
                    $feature['is_color_feature'] = false;

                    self::$features[$id_lang][$id_feature] = $feature;

                    foreach ($custom_features_values as $custom_feature_value) {
                        self::$features_values[$id_lang][$id_feature][$custom_feature_value['id_feature_value']] = $custom_feature_value;
                        self::$features_values[$id_lang][$id_feature][$custom_feature_value['id_feature_value']]['id_lang'] = $id_lang;

                        $feature_value_tmp = FeatureValue::getFeatureValueLang((int)$custom_feature_value['id_feature_value']);
                        if (is_array($feature_value_tmp)) {
                            self::$features_values[$id_lang][$id_feature][$custom_feature_value['id_feature_value']]['value'] = $feature_value_tmp[0]['value'];
                        }
                    }
                }
            }
        }
    }

    private function _selected_tab()
    {
        $selected_tab = Tools::getValue('selected_tab');
        $default_tab = 'priceminister';

        return ($selected_tab ? $selected_tab : $default_tab);
    }

    public function _priceminister()
    {
        $view_params = array();
        $view_params['prestashop_version'] = _PS_VERSION_;

        return $view_params;
    }

    public function _informations()
    {
        require_once(dirname(__FILE__).'/classes/priceminister.configuration_check.class.php');

        if ((bool)Configuration::get('PS_FORCE_SMARTY_2') == true) {
            die(sprintf('<div class="error">%s</span>', Tools::displayError('This module is not compatible with Smarty v2. Please switch to Smarty v3 in Preferences Tab.')));
        }

        $lang = Language::getIsoById($this->id_lang);
        $display = true;
        $php_infos = array();
        $module_infos = array();
        $prestashop_infos = array();
        $env_infos = array();

        // AJAX Checker
        $env_infos['ajax'] = array();
        $env_infos['ajax']['message'] = $this->l('AJAX execution failed. Please first verify your module configuration. If the problem persists please send a screenshot of this page to the support.');
        $env_infos['ajax']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        $env_infos['ajax']['display'] = false;
        $env_infos['ajax']['script'] = array('name' => 'env_check_url', 'url' => $this->url.'functions/check_env.php?action=ajax');

        // Module info
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.self::TABLE_PRICEMINISTER_ORDERS)) {
            $module_infos['missing_table_order']['message'] = sprintf('%s: %s', $this->l('Missing required table'), _DB_PREFIX_.self::TABLE_PRICEMINISTER_ORDERS);
            $module_infos['missing_table_order']['level'] = 'error '.($this->ps16x ? 'alert alert-danger' : '');
        }
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_OPTION)) {
            $module_infos['missing_table_options']['message'] = sprintf('%s: %s', $this->l('Missing required table'), _DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_OPTION);
            $module_infos['missing_table_options']['level'] = 'error '.($this->ps16x ? 'alert alert-danger' : '');
        }
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_ORDERED)) {
            $module_infos['missing_table_product_ordered']['message'] = sprintf('%s: %s', $this->l('Missing required table'), _DB_PREFIX_.self::TABLE_PRICEMINISTER_PRODUCT_ORDERED);
            $module_infos['missing_table_product_ordered']['level'] = 'error '.($this->ps16x ? 'alert alert-danger' : '');
        }
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.self::TABLE_PRICEMINISTER_MODELS)) {
            $module_infos['missing_table_models']['message'] = sprintf('%s: %s', $this->l('Missing required table'), _DB_PREFIX_.self::TABLE_PRICEMINISTER_MODELS);
            $module_infos['missing_table_models']['level'] = 'error '.($this->ps16x ? 'alert alert-danger' : '');
        }
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.self::TABLE_PRICEMINISTER_MAPPINGS)) {
            $module_infos['missing_table_product_mapping']['message'] = sprintf('%s: %s', $this->l('Missing required table'), _DB_PREFIX_.self::TABLE_PRICEMINISTER_MAPPINGS);
            $module_infos['missing_table_product_mapping']['level'] = 'error '.($this->ps16x ? 'alert alert-danger' : '');
        }
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.self::TABLE_PRICEMINISTER_MAPPINGS_DET)) {
            $module_infos['missing_table_mapping_det']['message'] = sprintf('%s: %s', $this->l('Missing required table'), _DB_PREFIX_.self::TABLE_PRICEMINISTER_MAPPINGS_DET);
            $module_infos['missing_table_mapping_det']['level'] = 'error '.($this->ps16x ? 'alert alert-danger' : '');
        }
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.self::TABLE_PRICEMINISTER_CONFIGURATION)) {
            $module_infos['missing_table_configuration']['message'] = sprintf('%s: %s', $this->l('Missing required table'), _DB_PREFIX_.self::TABLE_PRICEMINISTER_CONFIGURATION);
            $module_infos['missing_table_configuration']['level'] = 'error '.($this->ps16x ? 'alert alert-danger' : '');
        }
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.self::TABLE_PRICEMINISTER_REPRICING)) {
            $module_infos['missing_table_configuration']['message'] = sprintf('%s: %s', $this->l('Missing required table'), _DB_PREFIX_.self::TABLE_PRICEMINISTER_REPRICING);
            $module_infos['missing_table_configuration']['level'] = 'error '.($this->ps16x ? 'alert alert-danger' : '');
        }
        if (Configuration::get(self::CONFIG_PM_DEBUG)) {
            $module_infos['debug']['message'] = $this->l('Debug Mode is activated, that is not recommended');
            $module_infos['debug']['level'] = 'info '.($this->ps16x ? 'alert alert-warning' : 'warn');
        }
        if (!$this->active) {
            $module_infos['inactive']['message'] = $this->l('Be careful, your module is inactive, this mode stops all pending operations for this module, please change the status to active in your module list');
            $module_infos['inactive']['level'] = 'info '.($this->ps16x ? 'alert alert-warning' : 'warn');
        }
        $categories = PriceMinisterCategories::getAll();
        if (!is_array($categories) || !count($categories)) {
            $module_infos['categories']['message'] = $this->l('You didn\'t checked yet any category, in category tab');
            $module_infos['categories']['level'] = 'info '.($this->ps16x ? 'alert alert-warning' : 'warn');
        }
        $dirs = array(
            _PS_MODULE_DIR_.'priceminister'.DIRECTORY_SEPARATOR.'export',
            _PS_MODULE_DIR_.'priceminister'.DIRECTORY_SEPARATOR.'xml'
        );
        $messages = null;
        foreach ($dirs as $dir) {
            if (!PriceMinisterTools::isDirWriteable($dir)) {
                $messages[] = sprintf($this->l('You have to set write permissions to the %s directory and its subsequents files'), $dir);
            }
        }
        if (is_array($messages) && count($messages)) {
            foreach ($messages as $key => $message) {
                $module_infos['permissions_'.$key]['message'] = $message;
                $module_infos['permissions_'.$key]['level'] = 'error '.($this->ps16x ? 'alert alert-danger' : '');
            }
        }
        if (PriceMinisterTools::tableExists(_DB_PREFIX_.'configuration') === null) {
            $env_infos['show_tables_failed']['message'] = sprintf('%s: %s', $this->l('Your hosting doesnt allow'), 'SHOW TABLES');
            $env_infos['show_tables_failed']['level'] = 'error '.($this->ps16x ? 'alert alert-danger' : '');
        }

        // PHP Configuration Check
        if (in_array(@Tools::strtolower(ini_get('display_errors')), array('1', 'on'))) {
            $php_infos['display_error']['message'] = $this->l('PHP display_errors is On.');
            $php_infos['display_error']['level'] = $this->ps16x ? 'alert alert-info' : 'info';
        }

        if (!function_exists('curl_init')) {
            $php_infos['curl']['message'] = $this->l('PHP cURL must be installed on this server. The module require the cURL library and can\'t work without');
            $php_infos['curl']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
            $php_infos['curl']['link'] = 'http://php.net/manual/'.$lang.'/book.curl.php';
        }

        if (!method_exists('DOMDocument', 'createElement')) {
            $php_infos['domdoc']['message'] = $this->l('PHP DOMDocument (XML Library) must be installed on this server. The module require this library and can\'t work without');
            $php_infos['domdoc']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
            $php_infos['domdoc']['link'] = 'http://php.net/manual/'.$lang.'/class.domdocument.php';
        }

        if (!PriceMinisterConfigurationCheck::checkShopUrl()) {
            $php_infos['wrong_domain']['message'] = $this->l('Your are currently connected with the following domain name:').' <span style="color:navy">'.$_SERVER['HTTP_HOST'].'</span><br />'.
                $this->l('This one is different from the main shop domain name set in "Preferences > SEO & URLs":').' <span style="color:green">'.Configuration::get('PS_SHOP_DOMAIN').'</span>';
            $php_infos['wrong_domain']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        }

        if (($max_execution_time = ini_get('max_execution_time')) && $max_execution_time < 120) {
            $php_infos['timelimit']['message'] = sprintf($this->l('PHP value: max_execution_time recommended value is at least 120. your limit is currently set to %d').'<br />', $max_execution_time);
            $php_infos['timelimit']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }

        // Memory limit
        $memory_limit = PriceMinisterConfigurationCheck::getMemoryLimit();
        $recommended_memory_limit = 128;
        if ($memory_limit > 0 && $memory_limit < $recommended_memory_limit) {
            $php_infos['memory']['message'] = sprintf($this->l('PHP value: memory_limit recommended value is at least %sMB. your limit is currently set to %sMB').'<br />', $recommended_memory_limit, $memory_limit);
            $php_infos['memory']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }

        if ((ini_get('suhosin.post.max_vars') && ini_get('suhosin.post.max_vars') < 1000) || (ini_get('suhosin.request.max_vars') && ini_get('suhosin.request.max_vars') <= 1000)) {
            $php_infos['suhosin']['message'] = sprintf($this->l('PHP value: suhosin/max_vars could trouble your module configuration').'<br />');
            $php_infos['suhosin']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }

        if (ini_get('max_input_vars') != null && ini_get('max_input_vars') < 1000) {
            $php_infos['max_input_vars']['message'] = sprintf($this->l('PHP value: max_input_vars could trouble your module configuration').'<br />');
            $php_infos['max_input_vars']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }

        // Prestashop Configuration Check
        if (!(int)Configuration::get('PS_SHOP_ENABLE')) {
            $prestashop_infos['maintenance']['message'] = $this->l('Be carefull, your shop is in maintenance mode, the module might not work in that mode');
            $prestashop_infos['maintenance']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }
        if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_ === true) {
            $prestashop_infos['mod_dev']['message'] = $this->l('Prestashop _PS_MODE_DEV_ is active.');
            $prestashop_infos['mod_dev']['level'] = $this->ps16x ? 'alert alert-info' : 'info';
        }
        if ((bool)Configuration::get('PS_CATALOG_MODE')) {
            $prestashop_infos['catalog']['message'] = $this->l('Your shop is in catalog mode, you wont be able to import orders, you can switch off this mode in Preferences > Products tab');
            $prestashop_infos['catalog']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }

        if (PriceMinisterConfigurationCheck::hasOverrides()) {
            $prestashop_infos['override']['message'] = $this->l('Your Prestashop potentially runs some overrides. This information is necessary only in case of support');
            $prestashop_infos['override']['level'] = 'info '.($this->ps16x ? 'alert alert-info' : '');
        }

        // Check if birthday is mandatory
        if (!PriceMinisterConfigurationCheck::mandatoryCustomerField('birthday')) {
            $prestashop_infos['birthday_issue']['message'] = $this->l('Birthday field is required which is not the default in Prestashop core program. This configuration is not allowed by Marketplaces modules. Please fix it !');
            $prestashop_infos['birthday_issue']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        }

        // Check if phone is mandatory
        if (!PriceMinisterConfigurationCheck::checkAddress()) {
            $prestashop_infos['phone_issue']['message'] = $this->l('Phone field is not required by default, but required in your configuration. This configuration is not allowed by Marketplaces modules. Please fix it !');
            $prestashop_infos['phone_issue']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        }

        if (!count($prestashop_infos)) {
            $prestashop_info_ok = true;
        } else {
            $prestashop_info_ok = false;
        }

        if (!count($php_infos)) {
            $php_info_ok = true;
        } else {
            $php_info_ok = false;
        }

        if (!count($module_infos)) {
            $module_info_ok = true;
        } else {
            $module_info_ok = false;
        }

        $view_params = array();
        $view_params['images'] = $this->images;
        $view_params['display'] = $display;
        $view_params['env_infos'] = $env_infos;
        $view_params['php_infos'] = $php_infos;
        $view_params['php_info_ok'] = $php_info_ok;
        $view_params['module_infos'] = $module_infos;
        $view_params['module_info_ok'] = $module_info_ok;
        $view_params['prestashop_infos'] = $prestashop_infos;
        $view_params['prestashop_info_ok'] = $prestashop_info_ok;
        $view_params['support_informations_url'] = $this->url.'functions/check.php?id_lang='.$this->id_lang.'&instant_token='.Configuration::get('PM_INSTANT_TOKEN', null, 0, 0);
        $view_params['url'] = $this->url;

        return $view_params;
    }

    private function _credentials($selected_tab = false)
    {
        $view_params = array();

        $debug = Configuration::get(self::CONFIG_PM_DEBUG);

        $view_params = self::getConfig(self::CONFIG_PM_CREDENTIALS);

        $view_params['debug'] = (bool)$debug;

        $context_param = sprintf('&context_key=%s', PriceMinisterContext::getKey($this->context->shop));

        $view_params['test_style'] = $view_params['test'] ? ' style="color:red" ' : '';
        $view_params['debug_style'] = $view_params['debug'] ? ' style="color:red" ' : '';
        $view_params['locahost'] = in_array($_SERVER['SERVER_ADDR'], array('::1', '127.0.0.1', '37.59.50.71', '172.21.0.5'));
        $view_params['check_url'] = $this->url.'functions/check.php?id_lang='.$this->id_lang.'&instant_token='.Configuration::get('PM_INSTANT_TOKEN', null, 0, 0).$context_param;

        return $view_params;
    }

    public static function getConfig($key)
    {
        if (!isset(self::$config_template[$key])) {
            return false;
        }

        $config_keys = self::$config_template[$key];
        $config_array = array_fill_keys($config_keys, null);

        if (in_array($key, array('PM_SHIPPING'))) {
            require_once dirname(__FILE__).'/classes/priceminister.configuration.class.php';

            $config_items = unserialize(PriceMinisterConfiguration::getGlobalValue($key));
            if (!$config_items || !is_array($config_items) || !count($config_items)) {
                $config_items = unserialize(Configuration::getGlobalValue($key));
            }
        } else {
            $config_items = unserialize(Configuration::get($key));
        }

        return array_merge($config_array, is_array($config_items) ? $config_items : array());
    }

    private function _categories()
    {
        $view_params = array();

        /*if ($this->_categories == null) {
            $categories = Category::getCategories(intval($this->id_lang), $this->onlyActivesCategories);
            $this->_categories = $categories;
        } else {
            $categories = $this->_categories;
        }*/

        $categories = Category::getCategories(intval($this->id_lang), $this->onlyActivesCategories);

        $index = array();

        $default_categories = PriceMinisterCategories::getAll();

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $shop = $this->context->shop;
            $first = null;

            foreach ($categories as $categories1) {
                foreach ($categories1 as $category) {
                    if ($category['infos']['id_category'] == Category::getRootCategory(null, $shop)->id_category) {
                        $first = $category;
                    }
                }
            }

            $default_category = $shop->id_category;
        } else {
            foreach ($categories as $first1 => $categories_array) {
                break;
            }
            foreach ($categories_array as $first2 => $categories_array2) {
                break;
            }
            $first = $categories[$first1][$first2];
            $default_category = 1;
        }

        $default_profiles2categories = PriceMinisterProfiles2Categories::getAll();
        $html_categories = self::recurseCategoryForInclude($index, $categories, $first, $default_category, null, $default_categories, $default_profiles2categories, true);
        $view_params['list'] = $html_categories;
        $view_params['profiles'] = PriceMinisterProfiles::getAll();

        return $view_params;
    }

    public function recurseCategoryForInclude($indexedCategories, $categories, $current, $id_category = 1, $id_category_default = null, $default_categories = array(), $default_profiles = array(), $next = false)
    {
        static $done;
        static $irow;
        static $categories_table;

        $categories_table = isset($categories_table) ? $categories_table : array();

        if (is_array($default_categories) && in_array($id_category, $default_categories)) {
            $checked = ' checked="checked"';
        } elseif (!is_array($default_categories) || !count($default_categories)) {
            $checked = ' checked="checked"';
        } else {
            $checked = '';
        }

        if (!isset($done[$current['infos']['id_parent']])) {
            $done[$current['infos']['id_parent']] = 0;
        }
        $done[$current['infos']['id_parent']] += 1;

        $todo = count($categories[$current['infos']['id_parent']]);
        $doneC = $done[$current['infos']['id_parent']];

        $level = $current['infos']['level_depth'] + 1;
        $img = $next == false ? 'lv1.gif' : 'lv'.$level.'_'.($todo == $doneC ? 'f' : 'b').'.gif';
        $selected_profile = null;
        $saved_profiles = PriceMinisterProfiles::getAll();

        if (is_array($saved_profiles) && count($saved_profiles)) {
            foreach ($saved_profiles as $profile_id => $profile) {
                if (!isset($profile['name']) || empty($profile['name'])) {
                    continue;
                }

                if (isset($default_profiles[$id_category]) && $default_profiles[$id_category] == $profile['name']) {
                    $selected_profile = $profile['name'];
                }
            }
        }

        $categories_table[$id_category] = array(
            'level' => $level,
            'img_level' => $this->images.$img,
            'alt_row' => $irow++ % 2,
            'id_category_default' => $id_category_default == $id_category,
            'checked' => $checked,
            'name' => Tools::stripslashes($current['infos']['name']),
            'profile' => $selected_profile,
            'disabled' => !$next
        );

        if (isset($categories[$id_category])) {
            if ($categories[$id_category]) {
                foreach ($categories[$id_category] as $key => $row) {
                    if ($key != 'infos') {
                        self::recurseCategoryForInclude($indexedCategories, $categories, $categories[$id_category][$key], $key, $id_category_default, $default_categories, $default_profiles, true);
                    }
                }
            }
        }

        return ($categories_table);
    }

    private function _profiles()
    {
        $view_params = array();
        $view_params['profiles_data'] = null;

        $current_currency = Currency::getDefaultCurrency();

        $profiles = PriceMinisterProfiles::getAll();

        $models = PriceMinisterModels::getAllModelsNames();
        $model_options = array();

        if (is_array($models) && count($models)) {
            foreach ($models as $m) {
                $option = array();
                $option['value'] = $m['model_id'];
                $option['desc'] = $m['field_value'];
                $model_options[$m['model_id']] = $option;
            }
        }

        $profile_id = 0;

        $price_rule_default = array();
        $price_rule_default['currency_sign'] = isset($current_currency->sign) ? $current_currency->sign : null;
        $price_rule_default['type'] = 'percent';
        $price_rule_default['rule']['from'][0] = '';
        $price_rule_default['rule']['to'][0] = '';
        $price_rule_default['rule']['percent'][0] = '';
        $price_rule_default['rule']['value'][0] = '';

        if (is_array($profiles) && count($profiles)) {
            foreach ($profiles as $profile_id => $profile) {
                if (!isset($profiles[$profile_id]['name']) || empty($profiles[$profile_id]['name'])) {
                    unset($profiles[$profile_id]);
                    continue;
                }

                if (!isset($profiles[$profile_id]['model']) || $profiles[$profile_id]['model'] == '') {
                    $profiles[$profile_id]['model'] = null;
                    $profiles[$profile_id]['model_name'] = null;
                } else {
                    $model_id = $profiles[$profile_id]['model'];
                    $profiles[$profile_id]['model_name'] = isset($model_options[$model_id]['desc']) ? $model_options[$model_id]['desc'] : null;
                }

                if (!isset($profiles[$profile_id]['images_optionnals'])) {
                    $profiles[$profile_id]['images_optionnals'] = false;
                }

                if (isset($profile['price_rule']) && is_array($profile['price_rule'])
                    && isset($profile['price_rule']['rule']['from']) && is_array($profile['price_rule']['rule']['from'])
                    && isset($profile['price_rule']['rule']['to']) && is_array($profile['price_rule']['rule']['to'])
                ) {
                    $price_rule['currency_sign'] = isset($current_currency->sign) ? $current_currency->sign : null;
                    $price_rule['type'] = isset($profile['price_rule']['type']) ? $profile['price_rule']['type'] : 'percent';

                    if (isset($profile['price_rule']['rule']['from']) && is_array($profile['price_rule']['rule']['from'])
                        && isset($profile['price_rule']['rule']['to']) && is_array($profile['price_rule']['rule']['to'])
                    ) {
                        $price_rule['rule'] = $profile['price_rule']['rule'];
                        if (!count($profile['price_rule']['rule']['from'])
                            && !count($profile['price_rule']['rule']['to']) && !count($profile['price_rule']['rule']['value'])
                        ) {
                            $price_rule['rule']['from'][0] = '';
                            $price_rule['rule']['to'][0] = '';
                            $price_rule['rule']['percent'][0] = '';
                            $price_rule['rule']['value'][0] = '';
                        }
                    }
                } else {
                    // first use
                    $price_rule = $price_rule_default;
                }
                $profiles[$profile_id]['price_rule'] = $price_rule;
            }
            $view_params['profiles_data'] = $profiles;
        } else {
            $profiles = null;
        }

        $profile_empty = array();
        $profile_empty['name'] = null;
        $profile_empty['model'] = null;
        $profile_empty['model_name'] = null;
        $profile_empty['price_rule'] = $price_rule_default;
        $profile_empty['images_optionnals'] = false;
        $profile_empty['no_ean'] = true;

        $view_params['profile_empty'] = $profile_empty;
        $view_params['model_options'] = $model_options;

        return ($view_params);
    }

    private function _models()
    {
        $view_params = array();
        $pm_credentials = unserialize(Configuration::get(self::CONFIG_PM_CREDENTIALS));
        $p = new PriceMinisterForm();

        if (isset($pm_credentials['login']) && $pm_credentials['login'] && isset($pm_credentials['token']) && $pm_credentials['token'] && !$pm_credentials['test']) {
            $view_params['model_options'] = $p->getProductTypes(null);
        } else {
            $view_params['model_options'] = $p->getProductTypes(null, false, false);
        }

        $view_params['pm_models'] = array();
        $view_params['model_default'] = array(
            'idx' => '',
            'name' => '',
            'model_option' => '',
            'product_type_template' => ''
        );

        $models = PriceMinisterModels::getAll();
        if (count($models) > 0) {
            foreach ($models as $idx => $model) {
                $info = array();
                $info['idx'] = $idx;
                $info['name'] = isset($model['name']) ? $model['name'] : '';
                $info['model_option'] = $model['product_type'];
                $info['product_type_template'] = $p->getProductTypeTemplate($model['product_type'], $model, false, $idx);
                $view_params['pm_models'][] = $info;
            }
        }

        return ($view_params);
    }

    private function _parameters()
    {
        $view_params = array();
        $view_params = self::getConfig(self::CONFIG_PM_PARAMETERS);

        if ($view_params['import_method'] === null) {
            $view_params['import_method'] = 'SKU';
        }

        if ($view_params['specials'] === null) {
            $view_params['specials'] = true;
        }

        $warehouse = $view_params['warehouse'];// PS 1.5
        $image_type = $view_params['image_type'];
        $condition_map = $view_params['condition_map'];
        $customer_group = $view_params['customer_group'];

        // Shop Configuration
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $view_params['version_1_5'] = 1;

            // Warehouse (PS 1.5 with Stock Management)
            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                $view_params['advanced_management'] = '1';
                $view_params['advanced_management_options'] = array();
                $current_id_warehouse = (int)$warehouse;

                foreach (Warehouse::getWarehouses(true) as $warehouse) {
                    $advmgt_option = array();

                    if ((int)$warehouse['id_warehouse'] == $current_id_warehouse) {
                        $selected = 'selected="selected"';
                    } else {
                        $selected = '';
                    }

                    $advmgt_option['selected'] = $selected;
                    $advmgt_option['value'] = (int)$warehouse['id_warehouse'];
                    $advmgt_option['desc'] = $warehouse['name'];
                    $view_params['advanced_management_options'][] = $advmgt_option;
                }
            }
        }

        // Customer group
        $customer_groups = Group::getGroups($this->context->language->id, true);
        $current_customer_group = $customer_group ? $customer_group :
            (version_compare(_PS_VERSION_, '1.5', '>=') ?
                (int)Configuration::get('PS_CUSTOMER_GROUP') : (int)_PS_DEFAULT_CUSTOMER_GROUP_);

        $view_params['customer_groups'] = array_map(
            array($this, 'arrayMapCustomerGroup'),
            $customer_groups,
            array_fill(0, count($customer_groups), $current_customer_group)
        );

        // Image Type (PS 1.5.2+)
        if (version_compare(_PS_VERSION_, '1.4.1', '>=')) {
            if ($image_type == null) {
                $image_type = version_compare(_PS_VERSION_, '1.5', '>=') ?
                    'thickbox_default' : 'thickbox'; /* Validation : ImageType::getFormattedName() not available in many PS version... */
            }

            if (method_exists('ImageType', 'getImagesTypes')) {
                $images_types = ImageType::getImagesTypes();
            } else {
                $images_types = array(0 => array('name' => 'thickbox'));
            }

            foreach (ImageType::getImagesTypes() as $imageType) {
                $image_type_option = array();

                if (!(bool)$imageType['products']) {
                    continue;
                }

                if ($imageType['name'] == $image_type) {
                    $selected = 'selected="selected"';
                } else {
                    $selected = '';
                }

                $image_type_option['selected'] = $selected;
                $image_type_option['value'] = $imageType['name'];
                $image_type_option['desc'] = $imageType['name'];
                $view_params['image_types'][] = $image_type_option;
            }
        }

        // Products Condition/State
        $sql = 'SHOW COLUMNS FROM `'._DB_PREFIX_.'product` where Field = "condition"';
        $query = Db::getInstance()->executeS($sql);

        if (is_array($query)) {
            $query = array_shift($query);
        }

        // Uncompatible with PS < 1.4 / Looking for compatibility or assuming New as default
        if (isset($query['Field']) && $query['Field'] == 'condition') {
            $view_params['product_conditions'] = array();
            $ps_conditions = array();
            // Fetch columns names
            preg_match_all('/\'([\w ]*)\'/', $query['Type'], $ps_conditions);
            $i = 1;
            foreach ($this->conditions as $key => $condition) {
                $product_condition = array();
                $product_condition['value'] = sprintf('%s (%s)', $condition, $key);
                $product_condition['key'] = $key;
                $product_condition['index'] = $i;
                $product_condition['options'] = array();
                $i++;

                foreach ($ps_conditions[1] as $ps_condition) {
                    $product_condition_options = array();
                    if (isset($condition_map[$key]) && !empty($condition_map[$key]) && $condition_map[$key] == $ps_condition) {
                        $selected = 'selected="selected"';
                    } elseif ($condition_map == null && $condition == 'Neuf' && $ps_condition == 'new') { // Default
                        $selected = 'selected="selected"';
                    } else {
                        $selected = '';
                    }

                    $product_condition_options['selected'] = $selected;
                    $product_condition_options['value'] = $ps_condition;
                    $product_condition_options['desc'] = Tools::ucfirst($this->l($ps_condition));
                    $product_condition['options'][] = $product_condition_options;
                }

                $view_params['product_conditions'][] = $product_condition;
            }
        }

        return $view_params;
    }

    /* HOOKS FOR BACKWARD COMPATIBILITY - PRESTASHOP 1.3 and 1.4 */

    private function _shipping()
    {
        $view_params = self::getConfig(self::CONFIG_PM_SHIPPING);
        $view_params['parameters'] = array();

        $carriers = Carrier::getCarriers($this->id_lang, true, false, false, null, Carrier::ALL_CARRIERS);

        $settings_pm_carriers = $view_params['pm_carriers'];
        $settings_ps_carriers = $view_params['ps_carriers'];

        /* ADD Mondial Relay carriers if we have some */
        $mr_carriers = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'carrier` WHERE `external_module_name` = "mondialrelay" AND `deleted` = 0');

        if (is_array($mr_carriers) && count($mr_carriers)) {
            $carriers = array_merge($carriers, $mr_carriers);
        }

        foreach (self::$pm_shipping_methods as $shipping_method) {
            $shipping = array();
            $shipping['method'] = $shipping_method;
            $shipping['pm_carriers'] = array();
            $shipping['ps_carriers'] = array();

            foreach (self::$pm_carriers as $pm_carrier) {
                $carrier = array();

                if (isset($settings_pm_carriers[$shipping_method]) && $settings_pm_carriers[$shipping_method] == $pm_carrier) {
                    $selected = 'selected="selected"';
                } else {
                    $selected = '';
                }

                $carrier['selected'] = $selected;
                $carrier['value'] = $pm_carrier;
                $carrier['desc'] = $pm_carrier;
                $shipping['pm_carriers'][] = $carrier;
            }

            if (is_array($carriers) && count($carriers)) {
                foreach ($carriers as $ps_carrier) {
                    $carrier = array();

                    if (isset($settings_ps_carriers[$shipping_method]) && $settings_ps_carriers[$shipping_method] == $ps_carrier['id_carrier']) {
                        $selected = 'selected="selected"';
                    } else {
                        $selected = '';
                    }

                    $carrier['selected'] = $selected;
                    $carrier['value'] = $ps_carrier['id_carrier'];
                    $carrier['desc'] = $ps_carrier['name'];
                    $shipping['ps_carriers'][] = $carrier;
                }
            } else {
                $shipping = array();
            }

            $view_params['parameters']['shipping_methods'][] = $shipping;
        }

        $shipping_options_settings = $view_params['shipping_options'];

        foreach (self::$pm_shipping_zones as $key => $pm_shipping_zone) {
            $shipping_options = array();
            $shipping_options['key'] = $key;
            $shipping_options['name'] = $pm_shipping_zone;
            $shipping_options['options'] = array();

            foreach (self::$pm_shipping_options as $option_key => $shipping_option) {
                if (in_array($option_key, self::$pm_shipping_options_settings[$key])) {
                    $shipping_options['options'][$option_key]['key'] = $option_key;
                    $shipping_options['options'][$option_key]['name'] = $shipping_option;
                    $shipping_options['options'][$option_key]['selected'] = (isset($shipping_options_settings[$key][$option_key]) && $shipping_options_settings[$key][$option_key]);
                }
            }

            $view_params['parameters']['shipping_options'][] = $shipping_options;
        }

        $view_params['parameters']['shipping_zones'] = array();
        $view_params['parameters']['ps_zones'] = Zone::getZones(false);

        $shipping_table = $view_params['shipping_table'];
        $shipping_defaults = $view_params['shipping_defaults'];

        foreach (self::$pm_shipping_zones as $key => $pm_shipping_zone) {
            foreach (self::$pm_shipping_options as $option_key => $shipping_option) {
                if (!in_array($option_key, self::$pm_shipping_options_settings[$key])) {
                    continue;
                }

                $shipping_settings = array();
                $shipping_settings['key1'] = $key;
                $shipping_settings['key2'] = $option_key;
                $shipping_settings['display'] = (isset($shipping_options_settings[$key][$option_key]) && $shipping_options_settings[$key][$option_key]);
                $shipping_settings['name'] = sprintf('%s / %s', $pm_shipping_zone, $shipping_option);

                if (isset($shipping_table['zone'][$key][$option_key]) && $shipping_table['zone'][$key][$option_key]) {
                    $shipping_settings['selected_zone'] = $shipping_table['zone'][$key][$option_key];
                } else {
                    $shipping_settings['selected_zone'] = null;
                }

                if (isset($shipping_table['carrier'][$key][$option_key]) && $shipping_table['carrier'][$key][$option_key]) {
                    $shipping_settings['selected_carrier'] = $shipping_table['carrier'][$key][$option_key];
                } else {
                    $shipping_settings['selected_carrier'] = null;
                }

                if (isset($shipping_defaults['minimum'][$key][$option_key]) && $shipping_defaults['minimum'][$key][$option_key]) {
                    $shipping_settings['minimum'] = sprintf('%.02f', $shipping_defaults['minimum'][$key][$option_key]);
                } else {
                    $shipping_settings['minimum'] = null;
                }

                if (isset($shipping_defaults['additionnal'][$key][$option_key]) && $shipping_defaults['additionnal'][$key][$option_key]) {
                    $shipping_settings['additionnal'] = sprintf('%g', $shipping_defaults['additionnal'][$key][$option_key]);
                } else {
                    $shipping_settings['additionnal'] = null;
                }

                $view_params['parameters']['shipping_table'][] = $shipping_settings;
            }
        }

        return ($view_params);
    }

    private function _mappings()
    {
        $selected_tab = $this->_selected_tab();
        $view_params = array();

        $view_params['selected_tab'] = $selected_tab == 'mapping' ? true : false;
        $view_params['images_url'] = $this->images;
        $view_params['attributes'] = array();
        $view_params['feature'] = array();
        $id_lang = $this->id_lang;

        // Attributes Mapping
        //
        $id_attribute_group = null;
        $view_params['attributes']['config'][$id_lang] = array();

        if (array_key_exists($id_lang, self::$attributes_groups)) {
            $attributes_groups = &self::$attributes_groups[$id_lang];
        } //TODO: Preserve the reference
        else {
            $attributes_groups = array();
        }

        if (array_key_exists($id_lang, self::$attributes)) {
            $attributes = self::$attributes[$id_lang];
        } else {
            $attributes = array();
        }

//        if (!is_array($attributes) || !count($attributes) || !is_array($attributes_groups) || !count($attributes_groups)) {
//            return $view_params;
//        }

        $product_types = PriceMinisterModels::getXMLModelsFileName();
        $attr_mapping_table = $this->config['attr_mapping_table'];
        $view_params['attributes']['attribute_groups'] = array();

        if (is_array($this->config['attr_mapping_table'])) {
            foreach ($attributes_groups as $attribute_group) {
                $id_attribute_group = (int)$attribute_group['id_attribute_group'];

                if (!isset($attr_mapping_table[$id_attribute_group])) {
                    continue;
                }

                $view_params['attributes']['attribute_groups'][$id_attribute_group] = array('id' => $id_attribute_group, 'name' => PriceMinisterTools::encodeHtml($attribute_group['name']));
                $view_params['attributes']['config'][$id_attribute_group] = array();

                foreach ($attr_mapping_table[$id_attribute_group] as $pm_group) {
                    $mapping_left = isset($this->config['attributes_mapping_left'][$id_attribute_group]) && isset($this->config['attributes_mapping_left'][$id_attribute_group][$pm_group]) ? $this->config['attributes_mapping_left'][$id_attribute_group][$pm_group] : array(0 => false);
                    $mapping_right = isset($this->config['attributes_mapping_right'][$id_attribute_group]) && isset($this->config['attributes_mapping_right'][$id_attribute_group][$pm_group]) ? $this->config['attributes_mapping_right'][$id_attribute_group][$pm_group] : array(0 => false);

                    if (count($mapping_left) == 0) {
                        $mapping_left = $mapping_right = array(0 => false);
                    }

                    $index = 0;

                    $view_params['attributes']['config'][$id_attribute_group][$pm_group] = array();
                    $view_params['attributes']['config'][$id_attribute_group][$pm_group]['name'] = PriceMinisterTools::encodeHtml($attribute_group['name']).' - '.Tools::ucfirst($pm_group);
                    $view_params['attributes']['config'][$id_attribute_group][$pm_group]['left'] = array();
                    $view_params['attributes']['config'][$id_attribute_group][$pm_group]['right'] = array();
                    $view_params['attributes']['config'][$id_attribute_group][$pm_group]['matching'] = null;
                    $matching = array();

                    foreach ($mapping_left as $left) {
                        $view_params['attributes']['config'][$id_attribute_group][$pm_group]['left'][$index] = array();

                        $right = isset($mapping_right[$index]) ? $mapping_right[$index] : null;
                        $array = array();

                        if (!array_key_exists($pm_group, $this->config['pm_lists'])) {
                            $this->config['pm_lists'][$pm_group] = PriceMinister::loadList($pm_group, $product_types);
                        }

                        if (array_key_exists($id_attribute_group, $attributes)) {
                            foreach ($attributes[$id_attribute_group] as $key2 => $attribute) {
                                $id_attribute = (int)$attribute['id_attribute'];

                                if ($attribute['id_attribute_group'] != $id_attribute_group) {
                                    continue;
                                }

                                if (in_array($id_attribute, array_values($mapping_left))) {
                                    $used = true;
                                } else {
                                    $used = false;
                                }

                                $selected = (string)$left === (string)$id_attribute ? true : false;

                                // To not display matching attributes
                                if (in_array($attribute['name'], $this->config['pm_lists'][$pm_group])) {
                                    $matching[] = $attribute['name'];
                                    continue;
                                }

                                $view_params['attributes']['config'][$id_attribute_group][$pm_group]['left'][$index][$id_attribute]['name'] = PriceMinisterTools::encodeHtml($attribute['name']);
                                $view_params['attributes']['config'][$id_attribute_group][$pm_group]['left'][$index][$id_attribute]['selected'] = $selected;
                                $view_params['attributes']['config'][$id_attribute_group][$pm_group]['left'][$index][$id_attribute]['used'] = $used;
                            }
                        }

                        foreach ($this->config['pm_lists'][$pm_group] as $valueR2) {
                            $selected = ((string)$right === (string)$valueR2) ? true : false;
                            $array[$valueR2] = array('name' => $valueR2, 'selected' => $selected);
                        }
                        $view_params['attributes']['config'][$id_attribute_group][$pm_group]['right'][$index] = $array;

                        $index++;
                    }

                    if (is_array($matching) && count($matching)) {
                        $matching_list = implode(', ', array_unique($matching));
                        $view_params['attributes']['config'][$id_attribute_group][$pm_group]['matching'] = $matching_list;
                    }

                    $view_params['attributes']['config'][$id_attribute_group][$pm_group]['items'] = $index - 1;
                }
            }
        }
        $view_params['attributes']['config']['last'] = $id_attribute_group;

        // FeaturesMapping
        $id_feature = false;
        $feat_mapping_table = $this->config['feat_mapping_table'];
        $view_params['feature']['feature_groups'] = array();

        if (is_array($this->config['feat_mapping_table']) && is_array(self::$features) && array_key_exists($id_lang, self::$features)) {
            foreach (self::$features[$id_lang] as $feature) {
                $id_feature = (int)$feature['id_feature'];
                if (!isset($feat_mapping_table[$id_feature])) {
                    continue;
                }
                $features_values = self::$features_values[$id_lang][$id_feature];

                $view_params['feature']['feature_groups'][$id_feature] = array('id' => $id_feature, 'name' => PriceMinisterTools::encodeHtml($feature['name']));
                $view_params['feature']['config'][$id_feature] = array();

                foreach ($feat_mapping_table[$id_feature] as $pm_group) {
                    $mapping_left = isset($this->config['features_mapping_left'][$id_feature]) && isset($this->config['features_mapping_left'][$id_feature][$pm_group]) ? $this->config['features_mapping_left'][$id_feature][$pm_group] : array(0 => false);
                    $mapping_right = isset($this->config['features_mapping_right'][$id_feature]) && isset($this->config['features_mapping_right'][$id_feature][$pm_group]) ? $this->config['features_mapping_right'][$id_feature][$pm_group] : array(0 => false);
                    if (count($mapping_left) == 0) {
                        $mapping_left = $mapping_right = array(0 => false);
                    }
                    $index = 0;

                    $view_params['feature']['config'][$id_feature][$pm_group] = array();
                    $view_params['feature']['config'][$id_feature][$pm_group]['name'] = PriceMinisterTools::encodeHtml($feature['name']).' - '.$pm_group;
                    $view_params['feature']['config'][$id_feature][$pm_group]['left'] = array();
                    $view_params['feature']['config'][$id_feature][$pm_group]['right'] = array();

                    foreach ($mapping_left as $left) {
                        $view_params['feature']['config'][$id_feature][$pm_group]['left'][$index] = array();

                        foreach ($features_values as $key2 => $feature_value) {
                            $id_attribute = (int)$feature_value['id_feature_value'];

                            if ($feature_value['id_feature'] != $id_feature) {
                                continue;
                            }

                            if (in_array($id_attribute, array_values($mapping_left))) {
                                $used = true;
                            } else {
                                $used = false;
                            }

                            $selected = ((string)$left === (string)$id_attribute) ? true : false;

                            $view_params['feature']['config'][$id_feature][$pm_group]['left'][$index][$id_attribute]['name'] = PriceMinisterTools::encodeHtml($feature_value['value']);
                            $view_params['feature']['config'][$id_feature][$pm_group]['left'][$index][$id_attribute]['selected'] = $selected;
                            $view_params['feature']['config'][$id_feature][$pm_group]['left'][$index][$id_attribute]['used'] = $used;
                        }

                        $right = isset($mapping_right[$index]) ? $mapping_right[$index] : null;
                        $array = array();

                        if (!array_key_exists($pm_group, $this->config['pm_lists'])) {
                            $this->config['pm_lists'][$pm_group] = PriceMInister::loadList($pm_group, $product_types);
                        }

                        foreach ($this->config['pm_lists'][$pm_group] as $valueR2) {
                            $selected = ((string)$right === (string)$valueR2) ? true : false;
                            $array[$valueR2] = array('name' => $valueR2, 'selected' => $selected);
                        }

                        // Feature with input text instead of selector
                        if (!count($array) && $right) {
                            $array = $right;
                        }

                        $view_params['feature']['config'][$id_feature][$pm_group]['right'][$index] = $array;
                        $index++;
                    }
                    $view_params['feature']['config'][$id_feature][$pm_group]['items'] = $index - 1;
                }
            }
        }

        if ($id_feature !== false) {
            $view_params['feature']['config']['last'] = $id_feature;
        }

        return ($view_params);
    }

    public static function loadList($listname, $product_types = null)
    {
        static $model_list = array();

        if (strripos($listname, '_def', -3) !== false) {
            $listname = Tools::substr($listname, 0, -4);
        }

        $pm_options = array();

        if (is_array($product_types) && count($product_types)) {
            foreach ($product_types as $product_type) {
                if (is_array($model_list) && count($model_list) && isset($model_list[$product_type]) && is_array($model_list[$product_type]) && isset($model_list[$product_type][$listname]) && count($model_list[$product_type][$listname])) {
                    $pm_options = array_merge($pm_options, $model_list[$product_type][$listname]);
                    continue;
                }

                $output_file = self::getTemplateFilename($product_type);

                if (file_exists($output_file)) {
                    $input_data = simplexml_load_file($output_file);

                    if (isset($input_data->response->attributes->advert) && isset($input_data->response->attributes->advert->attribute) && count($input_data->response->attributes->advert->attribute)) {
                        foreach ($input_data->response->attributes->advert->attribute as $advert) {
                            if ($advert->key == $listname) {
                                foreach ($advert->valueslist->value as $opt_value) {
                                    $pm_options[] = (string)$opt_value;
                                }
                            }
                        }
                    }
                    if (isset($input_data->response->attributes->product) && isset($input_data->response->attributes->product->attribute) && count($input_data->response->attributes->product->attribute)) {
                        foreach ($input_data->response->attributes->product->attribute as $advert) {
                            if ($advert->key == $listname && $advert->valueslist->value != null) {
                                foreach ($advert->valueslist->value as $opt_value) {
                                    $pm_options[] = (string)$opt_value;
                                }
                            }
                        }
                    }
                }

                if (is_array($pm_options) && count($pm_options)) {
                    $model_list[$product_type][$listname] = $pm_options;
                }
            }
        }

        if (!is_array($pm_options) || !count($pm_options)) {
            $output_file = self::getTemplateListsFilename($listname);
            if (!file_exists($output_file)) {
                return array();
            }

            $input_data = simplexml_load_file($output_file);
            $pm_options = array();

            if (isset($input_data->valueslist) && isset($input_data->valueslist->value)) {
                foreach ($input_data->valueslist->value as $opt_value) {
                    $pm_options[] = (string)$opt_value;
                }
            }
        }

        return $pm_options;
    }

    public static function getTemplateFilename($template)
    {
        return PriceMinister::getTemplateDir().DIRECTORY_SEPARATOR.$template.'.xml';
    }

    public static function getTemplateDir()
    {
        //this constant stores the defined folder to store XML cache:
        return realpath(dirname(__FILE__).'/xml');
    }

    public static function getTemplateListsFilename($listname)
    {
        $listname = PriceMinisterTools::encodeHtml($listname);

        return PriceMinister::getTemplateDir().DIRECTORY_SEPARATOR.'lists'.DIRECTORY_SEPARATOR.$listname.'.xml';
    }

    private function _filters()
    {
        $filters = self::getConfig(self::CONFIG_PM_FILTERS);

        // Manufacturers Filtering
        //
        $manufacturers = Manufacturer::getManufacturers(false, $this->id_lang);

        $filtered_manufacturers = array();
        $available_manufacturers = array();

        if (is_array($manufacturers) && count($manufacturers)) {
            foreach ($manufacturers as $manufacturer) {
                if (is_array($filters['manufacturers']) && in_array((string)$manufacturer['id_manufacturer'], $filters['manufacturers'])) {
                    continue;
                }

                $available_manufacturers[$manufacturer['id_manufacturer']] = $manufacturer['name'];
            }
            if (isset($filters['manufacturers']) && is_array($filters['manufacturers']) && count($filters['manufacturers'])) {
                foreach ($manufacturers as $manufacturer) {
                    if (isset($filters['manufacturers']) && is_array($filters['manufacturers']) && !in_array((string)$manufacturer['id_manufacturer'], $filters['manufacturers'])) {
                        continue;
                    }

                    $filtered_manufacturers[$manufacturer['id_manufacturer']] = $manufacturer['name'];
                }
            }
        }

        // Suppliers Filtering
        //
        $suppliers = Supplier::getSuppliers(false, $this->id_lang);

        $filtered_suppliers = array();
        $available_suppliers = array();

        if (is_array($suppliers) && count($suppliers)) {
            foreach ($suppliers as $supplier) {
                if (is_array($filters['suppliers']) && in_array((string)$supplier['id_supplier'], $filters['suppliers'])) {
                    continue;
                }

                $available_suppliers[$supplier['id_supplier']] = $supplier['name'];
            }
            if (is_array($filters['suppliers']) && count($filters['suppliers'])) {
                foreach ($suppliers as $supplier) {
                    if (isset($filters['suppliers']) && is_array($filters['suppliers']) && !in_array((string)$supplier['id_supplier'], $filters['suppliers'])) {
                        continue;
                    }

                    $filtered_suppliers[$supplier['id_supplier']] = $supplier['name'];
                }
            }
        }

	    return array(
		    'images'        => $this->images,
		    'url'           => $this->url,
		    'manufacturers' => array(
			    'available' => $available_manufacturers,
			    'filtered'  => $filtered_manufacturers,
		    ),
		    'suppliers'     => array(
			    'available' => $available_suppliers,
			    'filtered'  => $filtered_suppliers,
		    ),
		    'outofstock'    => $filters['outofstock'],
		    'price'         => $filters['price'],
	    );
    }

    private function _orders()
    {
        $view_params = self::getConfig(self::CONFIG_PM_ORDERS);
        if (!$view_params['status_incoming']) {
            $view_params['status_incoming'] = defined('_PS_OS_PAYMENT_') ? _PS_OS_PAYMENT_ : (int)Configuration::get('PS_OS_PAYMENT');
        }
        if (!$view_params['status_sent']) {
            $view_params['status_sent'] = defined('_PS_OS_PAYMENT_') ? _PS_OS_SHIPPING_ : (int)Configuration::get('PS_OS_SHIPPING');
        }
        if (empty($view_params['email_domain'])) {
            $view_params['email_domain'] = sprintf('@%s', self::TRASH_DOMAIN);
        }
        $customer_account = (int)$view_params['customer_account'];
        if (!$customer_account) {
            $view_params['customer_account'] = self::ONE_CUSTOMER_ACCOUNT;
        }

        return array_merge($view_params, array(
            'images' => $this->images,
            'url' => $this->url,
            'order_states' => array_map(function ($orderState) {
                return array(
                    'value' => (int)$orderState['id_order_state'],
                    'desc' => $orderState['name'],
                );
            }, OrderState::getOrderStates($this->id_lang)),
            'one_customer_account' => self::ONE_CUSTOMER_ACCOUNT,
            'individual_customer_account' => self::INDIVIDUAL_CUSTOMER_ACCOUNT,
            'shipping_from_countries' => array('AD','AL','AT','BE','BG','CH','CN','CY','CZ','DE','DK','EE','ES','FI','FR','GB','GR','HR','HU','IE','IS','IT','LI','LT','LU','LV','MC','MD','MK','MT','NL','NO','PL','PT','RO','SE','SI','SK','SM','UA'),
        ));
    }

    private function _repricing()
    {
        $view_params = array();

        $repricing_strategies = PriceMinisterRepricing::getAll();
        $repricing_empty = array(
            'id_repricing' => null,
            'name' => '',
            'active' => '1',
            'aggressiveness' => '10',
            'base' => '1',
            'limit' => '10',
            'delta' => '0;0',
        );

        $view_params['repricing_empty'] = $repricing_empty;
        $view_params['repricing_strategies'] = $repricing_strategies;

        return $view_params;
    }

    private function _cron()
    {
        $view_params = array(
            'cronjobs' => array(
                'exists' => is_dir(_PS_MODULE_DIR_.'cronjobs/'),
                'installed' => (bool)PriceMinisterTools::moduleIsInstalled('cronjobs'),
            ),
            'stdtypes' => array('accept_url', 'import_url', 'order_update', 'create_url', 'synch_url'/*, 'repricing_fetch', 'repricing_treat'*/),
            'synch_url' => null,
        );

        $pm_token = Configuration::get(self::CONFIG_PM_CRON_TOKEN);
        $context_key = PriceMinisterContext::getKey(Validate::isLoadedObject($this->context->shop) ? $this->context->shop : null);

        if ($pm_token) {
            $view_params = array_merge($view_params, array(
                'accept_url' => array(
                    'title' => $this->l('Orders - Acceptation'),
                    'url' => PriceMinisterTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/functions/orders_import.php?action=accept&cron=1&lang=fr&pm_token='.$pm_token.'&context_key='.$context_key,
                    'url_short' => preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', PriceMinisterTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_)).'/'.$this->name.'/functions/orders_import.php?...',
                    'frequency' => 1,
                ),
                'import_url' => array(
                    'title' => $this->l('Orders - Import'),
                    'url' => PriceMinisterTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/functions/orders_import.php?action=import&cron=1&lang=fr&pm_token='.$pm_token.'&context_key='.$context_key,
                    'url_short' => preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', PriceMinisterTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_)).'/'.$this->name.'/functions/orders_import.php?...',
                    'frequency' => 1,
                ),
                'order_update' => array(
                    'title' => $this->l('Orders - Update'),
                    'url' => PriceMinisterTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/functions/cron_orders_update.php?cron=1&lang=fr&pm_token='.$pm_token.'&context_key='.$context_key,
                    'url_short' => preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', PriceMinisterTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_)).'/'.$this->name.'/functions/cron_orders_update.php?...',
                    'frequency' => 24,
                ),
                'create_url' => array(
                    'title' => $this->l('Products'),
                    'url' => PriceMinisterTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/functions/products.php?action=cron&lang=fr&pm_token='.$pm_token.'&context_key='.$context_key,
                    'url_short' => preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', PriceMinisterTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_)).'/'.$this->name.'/functions/products.php?...',
                    'frequency' => 1,
                ),
                'synch_url' => array(
                    'title' => $this->l('Offers'),
                    'url' => PriceMinisterTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/functions/offers.php?action=cron&lang=fr&pm_token='.$pm_token.'&context_key='.$context_key,
                    'url_short' => preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', PriceMinisterTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_)).'/'.$this->name.'/functions/offers.php?...',
                    'frequency' => 1,
                ),
                'repricing_fetch' => array(
                    'title' => $this->l('Repricing - Fetch'),
                    'url' => PriceMinisterTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/functions/repricing.php?action=fetch&pm_token='.$pm_token.'&context_key='.$context_key,
                    'url_short' => preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', PriceMinisterTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_)).'/'.$this->name.'/functions/offers.php?...',
                    'frequency' => 1,
                ),
                'repricing_treat' => array(
                    'title' => $this->l('Repricing - Treat'),
                    'url' => PriceMinisterTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/functions/repricing.php?action=treat&pm_token='.$pm_token.'&context_key='.$context_key,
                    'url_short' => preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', PriceMinisterTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_)).'/'.$this->name.'/functions/offers.php?...',
                    'frequency' => 1,
                ),
            ));
        }

        return ($view_params);
    }

    /* Functions used for Models and Mappings */

    public function hookDisplayAdminProductsExtra($params)
    {
        require_once(_PS_MODULE_DIR_.$this->name.'/classes/priceminister.product_tab.class.php');

        $productExtManager = new PriceMinisterProductTab();
        $this->_html .= $productExtManager->DoIt($params);

        return ($this->_html);
    }

    public function hookUpdateCarrier($params)
    {
        $this->hookActionCarrierUpdate($params);
    }

    /**
     * @param $params
     * @return bool
     */
    public function hookActionProductUpdate($params)
    {
        if (isset($params['product']->id)) {
            $id_product = $params['product']->id;
        } elseif (isset($params['id_product'])) {
            $id_product = $params['id_product'];
        } elseif (isset($params['product'])) {
            $id_product = $params['product']['id_product'];
        } else {
            return false;
        }

        return Db::getInstance()->execute(
            'UPDATE `'._DB_PREFIX_.'product`
            SET `date_upd` = "'.pSQL(date('Y-m-d H:i:s')).'"
            WHERE `id_product` = '.(int)$id_product
        );
    }

    /**
     * @param array $params array(
     *      'id_product' => (int) Product ID,
     *      'id_product_attribute' => (int) Product attribute ID,
     *      'quantity' => (int) New product quantity
     *  );
     * @return bool
     */
    public function hookActionUpdateQuantity($params)
    {
        return $this->hookActionProductUpdate($params);
    }

    /**
     * @param Cart[]|Order[]|Customer[]|Currency[]|OrderState[] $params array(
     *      'cart' => (object) Cart,
     *      'order' => (object) Order,
     *      'customer' => (object) Customer,
     *      'currency' => (object) Currency,
     *      'orderStatus' => (object) OrderState
     *  );
     * @return bool
     */
    public function hookActionValidateOrder($params)
    {
        foreach ($params['order']->getProducts() as $product) {
            return Db::getInstance()->execute(
                'UPDATE `'._DB_PREFIX_.'product`
                SET `date_upd` = "'.pSQL(date('Y-m-d H:i:s')).'"
                WHERE `id_product` = '.(int)$product['product_id']
            );
        }
    }

    public function hookActionCarrierUpdate($params)
    {
        require_once dirname(__FILE__) . '/common/tools.class.php';
        $pm_shippings = self::getConfig(self::CONFIG_PM_SHIPPING);
        $pass = false;

        if (is_array($pm_shippings['ps_carriers']) && count($pm_shippings['ps_carriers'])) {
            foreach ($pm_shippings['ps_carriers'] as $id_lang => $ps_carriers) {
                foreach ($pm_shippings['ps_carriers'] as $name => $carrier) {
                    if ($pm_shippings['ps_carriers'][$name] == $params['id_carrier']) {
                        $pm_shippings['ps_carriers'][$name] = $params['carrier']->id;
                        $pass = true;
                    }
                }
            }
        }

        if ($pass) {
            Configuration::updateValue(self::CONFIG_PM_SHIPPING, serialize($pm_shippings));
        }
    }

    public function hookAdminOrder($params)
    {
        return ($this->hookDisplayAdminOrder($params));
    }

    public function hookDisplayAdminOrder($params)
    {
        require_once(_PS_MODULE_DIR_.$this->name.'/classes/priceminister.order.class.php');

        $id_order = (int)$params['id_order'];
        $order = new PriceMinisterOrder($id_order);

        // Not a price minister order
        if ($order->module != $this->name) {
            return (false);
        }

        require_once(dirname(__FILE__).'/classes/priceminister.tools.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.context.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.api.webservices.php');
        require_once(dirname(__FILE__).'/classes/priceminister.sales.api.php');

        $context_key = PriceMinisterContext::getKey(Validate::isLoadedObject($this->context->shop) ? $this->context->shop : null);
        $credentials = unserialize(Configuration::get(self::CONFIG_PM_CREDENTIALS));

        if (isset($credentials['test']) && $credentials['test']) {
            $order_ext = mt_rand(100000, 999999);
        } elseif (!$order_ext = PriceMinisterOrder::getOrderExt($id_order)) {
            return (false);
        }

        $pm_tokens = Configuration::get(self::CONFIG_PM_CRON_TOKEN);

        if (isset(self::$pm_shipping_methods_type[$order_ext['shipping_type']]) && self::$pm_shipping_methods_type[$order_ext['shipping_type']] == self::DELIVERY_COLLECTION_POINT) {
            $is_collection_point = true;
        } else {
            $is_collection_point = false;
        }

        // Shipping informations
        $prepaidlabelurl = null;
        $trackingnumber = null;
        $config = PriceMinisterTools::Auth();

        if (in_array($order_ext['shipping_type'], array('So Colissimo', 'Chronopost')) && !$order->shipping_number && is_array($config)) {
            $pm_sales = new PM_Sales($config);
            $shipping_information = $pm_sales->getshippinginformation($order_ext['mp_order_id']);

            if (array_key_exists('shippinginformation', $shipping_information) &&
                array_key_exists('prepaidlabel', $shipping_information['shippinginformation']) &&
                $shipping_information['shippinginformation']['prepaidlabel']['available'] == 'Y') {
                $prepaidlabel = $shipping_information['shippinginformation']['prepaidlabel'];

                $prepaidlabelurl = $prepaidlabel['prepaidlabelurl'];
                PriceMinisterOrder::updateOrderExt(array('prepaidlabelurl' => $prepaidlabelurl), $order->id);

                $order->shipping_number = $prepaidlabel['trackinginformation']['trackingnumber'];
                $order->update();

                if (version_compare(_PS_VERSION_, '1.5.0.4', '>=')) {
                    $id_order_carrier = Db::getInstance()->getValue(
                        'SELECT `id_order_carrier`
                        FROM `'._DB_PREFIX_.'order_carrier`
                        WHERE `id_order` = '.(int)$order->id
                    );

                    if ($id_order_carrier) {
                        $order_carrier = new OrderCarrier($id_order_carrier);

                        if (Validate::isLoadedObject($order_carrier)) {
                            $order_carrier->tracking_number = $prepaidlabel['trackinginformation']['trackingnumber'];
                            $order_carrier->update();
                        }
                    }
                }
            }
        }
        // !Shipping informations

        $this->context->smarty->assign(array(
            'pm_url' => $this->url,
            'pm_images' => $this->images,
            'pm_marketPlaceOrderId' => (isset($credentials['test']) && $credentials['test']) ? $order_ext : $order_ext['mp_order_id'],
            'pm_marketPlaceShipping' => null,
            'pm_id_order' => $order->id,
            'pm_order_lang' => Language::getIsoById($order->id_lang),
            'pm_id_lang' => $order->id_lang,
            'pm_token' => $pm_tokens,
            'pm_context_key' => $context_key,
            'pm_order_page_url' => PriceMinisterTools::orderPageURL($order_ext['mp_order_id']),
            'pm_is_collection_point' => $is_collection_point,
            'prepaidlabelurl' => $prepaidlabelurl ?: (isset($order_ext['prepaidlabelurl']) ? $order_ext['prepaidlabelurl'] : null)
        ));

        $this->_autoAddCSS($this->url.'views/css/displayAdminOrder.css');
        $this->_html .= $this->context->smarty->fetch($this->path.'views/templates/hook/displayAdminOrder.tpl');

        return ($this->_html);
    }

    private function _autoAddCSS($url, $media = 'all')
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            return ($this->context->controller->addCSS($url, $media) && '');
        } else {
            echo '<link rel="stylesheet" type="text/css" href="'.$url.'">';

            return (true);
        }
    }

    // todo: Convert to non-hook function
    public function hookActionOrderStatusUpdate($params)
    {
        require_once(_PS_MODULE_DIR_.$this->name.'/classes/priceminister.order.class.php');
        require_once(_PS_MODULE_DIR_.$this->name.'/classes/priceminister.tools.class.php');

        $debug = (bool)Configuration::get(self::CONFIG_PM_DEBUG);
        $id_order = (int)$params['id_order'];

        $order = new PriceMinisterOrder($id_order);
        if (!Validate::isLoadedObject($order)) {
            return (false);
        }

        // Not a price minister order
        if ($order->module != $this->name) {
            return false;
        }

        // Config
        $pm_credentials = self::getConfig(self::CONFIG_PM_CREDENTIALS);
        $pm_orders = self::getConfig(self::CONFIG_PM_ORDERS);

        if (!$pm_orders['status_sent']) {
            if ($debug) {
                die(sprintf('%s(%d): Debug Mode On: Order tab is not yet configured', basename(__FILE__), __LINE__));
            }

            return false;
        }

        $sentstate = $pm_orders['status_sent'];

        // Matching Order Status
        if ($params['newOrderStatus']->id != $sentstate) {
            return (false);
        }

        if (!$order_ext = PriceMinisterOrder::getOrderExt($id_order)) {
            if ($debug) {
                die(sprintf('%s(%d): Debug Mode On: unable to fetch OrderExt details', basename(__FILE__), __LINE__));
            }

            return false;
        }

        $trackingNumber = null;

        if (isset($order->shipping_number) && !empty($order->shipping_number)) {
            $trackingNumber = $order->shipping_number;
        } elseif (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_order_carrier = (int)Db::getInstance()->getValue(
                'SELECT `id_order_carrier`
                FROM `'._DB_PREFIX_.'order_carrier`
                WHERE `id_order` = '.(int)$order->id.'
                ORDER BY `id_order_carrier` DESC'
            );

            $order_carrier = new OrderCarrier($id_order_carrier);
            if (Validate::isLoadedObject($order_carrier) && $order_carrier->tracking_number) {
                $trackingNumber = $order_carrier->tracking_number;
            }
        }

        // Shipping empty
        if (empty($trackingNumber)) {
            if ($debug) {
                die(sprintf('%s(%d): Debug Mode On: Tracking Number is empty', basename(__FILE__), __LINE__));
            }
            return (false);
        }

        // Get item list
        $ordered_items = $order->getMarketplaceItem();

        if (!$ordered_items) {
            if ($debug) {
                die(sprintf('%s(%d): Debug Mode On: Ordered item list is empty', basename(__FILE__), __LINE__));
            }

            return (false);
        }
        // Shipping Matrix
        $pm_shipping = self::getConfig(self::CONFIG_PM_SHIPPING);

        $shipping_methods = $pm_shipping['shipping_methods'];
        $pm_carriers = $pm_shipping['pm_carriers'];
        $ps_carriers = $pm_shipping['ps_carriers'];

        if (!count($shipping_methods) || !count($pm_carriers) || !count($ps_carriers)) {
            if ($debug) {
                echo '<pre>\n';
                printf('%s(%d): Debug Mode On: Shipping Matrix is not configured for this order (%d)', basename(__FILE__), __LINE__, $order->id);
                echo nl2br(print_r($shipping_methods, true));
                echo nl2br(print_r($pm_carriers, true));
                echo nl2br(print_r($ps_carriers, true));
                echo '</pre>\n';
                die;
            }

            return (false);
        }

        // TODO
        if ($order_ext['shipping_type'] == 'Recommandé') {
            $order_ext['shipping_type'] = 'Recommande';
        } elseif ($order_ext['shipping_type'] == 'EN TÉLÉCHARGEMENT') {
            $order_ext['shipping_type'] = 'EN TELECHARGEMENT';
        }

        if (!isset($pm_carriers[$order_ext['shipping_type']]) || !$pm_carriers[$order_ext['shipping_type']]) {
            if ($debug) {
                die(sprintf('%s(%d): Debug Mode On: No associated carrier for this order (%d)', basename(__FILE__), __LINE__, $order->id));
            }

            return (false);
        }

        $carrier = new Carrier((int)$ps_carriers[$order_ext['shipping_type']]);

        if (!Validate::isLoadedObject($carrier)) {
            if ($debug) {
                die(sprintf('%s(%d): Debug Mode On: Unable to load carrier for this order (%s/%d)', basename(__FILE__), __LINE__, $ps_carriers[$order_ext['shipping_type']], $order->id));
            }

            return (false);
        }
        $trackingUrl = str_replace('@', $trackingNumber, $carrier->url);

        // PM Configuration
        //
        $config = PriceMinisterTools::Auth();

        if (!is_array($config)) {
            if ($debug) {
                die(sprintf('%s(%d): Debug Mode On: Module is not enough configured', basename(__FILE__), __LINE__));
            }

            return (false);
        }

        // Orders Output Settings
        $config['output_type'] = 'juicy';
        $config['output'] = 'json';

        // PM Settings
        $params = array();
        $dom = new DOMDocument();
        $dom->encoding = 'UTF-8';
        $dom->formatOutput = true;
        $items = $dom->createElement('items');
        $dom->appendChild($items);

        require_once(_PS_MODULE_DIR_.$this->name.'/classes/priceminister.api.webservices.php');
        require_once(_PS_MODULE_DIR_.$this->name.'/classes/priceminister.sales.api.php');

        $pmSales = new PM_Sales($config);

        foreach ($ordered_items as $key => $ordered_item) {
            $item = $dom->createElement('item');

            $purchaseid = $dom->createElement('purchaseid', $order_ext['mp_order_id']);
            $itemid = $dom->createElement('itemid', $ordered_item['itemid']);
            $transporter_name = $dom->createElement(
                'transporter',
                in_array($pm_carriers[$order_ext['shipping_type']], array('Normal', 'Retrait chez le vendeur')) ?
                    null : $pm_carriers[$order_ext['shipping_type']]
            );
            $tracking_number = $dom->createElement(
                'trackingnumber',
                $trackingNumber
            );
            $tracking_url = $dom->createElement(
                'trackingurl',
                in_array($pm_carriers[$order_ext['shipping_type']], array('Autre', 'Kiala')) ?
                    $trackingUrl : null
            );

            $item->appendChild($purchaseid);
            $item->appendChild($itemid);
            $item->appendChild($transporter_name);
            $item->appendChild($tracking_number);
            $item->appendChild($tracking_url);

            $items->appendChild($item);
        }

        $filename = $this->path.'export/trackingpackageinfos.xml';
        $dom->save($filename);
        $params['file'] = '@'.$filename;

        if ($pm_credentials['test']) {
            if ($debug) {
                echo '<pre>\n';
                echo 'RakutenFrance, Debug Mode On: Params: ';
                echo nl2br(print_r($params, true));
                echo '</pre>\n';
                die;
            }
        } else {
            $result = $pmSales->importitemshippingstatus($params);

            if ($debug) {
                echo '<pre>\n';
                echo 'RakutenFrance, Debug Mode On: W/S Result: ';
                echo nl2br(print_r($result, true));
                echo '</pre>\n';
                var_dump($params);
                die;
            }
        }

        return true;
    }

    public function hookBackOfficeHeader($params)
    {
        return ($this->hookDisplayBackOfficeHeader($params));
    }

    public function hookDisplayBackOfficeHeader($params)
    {
        $html = '';

        $controller = Tools::strtolower(Tools::getValue('controller'));
        $tab = Tools::strtolower(Tools::getValue('tab'));
        $updateproduct = (Tools::getValue('addproduct') !== false || Tools::getValue('updateproduct') !== false) && Tools::getValue('id_product') !== false;

        $html .= '<meta name="priceminister-options" content="'.$this->url.'functions/product_ext.php" />';
        $html .= '<meta name="priceminister-options-json" content="'.$this->url.'functions/product_ext.json.php" />';

        if ((version_compare(_PS_VERSION_, '1.5', '<') && $tab == 'admincatalog' && $updateproduct) || (version_compare(_PS_VERSION_, '1.5', '>=') && $controller == 'adminproducts' && $updateproduct)) {
            $html .= $this->_autoAddCSS($this->url.'views/css/product_ext.css');
            $html .= $this->_autoAddJS($this->url.'views/js/product_extpm.js');
        }

        if (version_compare(_PS_VERSION_, '1.4', '<')) {
            print $html;
        } else {
            return ($html);
        }
    }

    private function _autoAddJS($url)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            return ($this->context->controller->addJS($url) && '');
        } else {
            return (sprintf('<script type="text/javascript" src="%s"></script>', $url));
        }
    }

    protected function loadGeneralModuleConfig()
    {
        require_once(dirname(__FILE__).'/classes/priceminister.tools.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.mappings.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.models.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.profiles.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.categories.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.prof2categories.class.php');
        require_once(dirname(__FILE__).'/classes/priceminister.repricing.class.php');

        $this->_loadSettings();
        $this->_load_attributes();
        $this->_load_features();
    }

    private function arrayMapCustomerGroup($customer_group, $selected)
    {
        return array(
            'id_group' => $customer_group['id_group'],
            'name' => $customer_group['name'],
            'selected' => $selected == $customer_group['id_group']
        );
    }

    /*******************************************************************************************************************
     * Module does not store customer data, these hooks are just symbolic
     * GDPR compliance: Export customer data
     * @param string|array $customer
     * @return string
     */
    public function hookActionExportGDPRData($customer)
    {
        return json_encode(array());
    }

    /**
     * GDPR compliance: Delete customer data
     * @param string|array $customer
     * @return string
     */
    public function hookActionDeleteGDPRCustomer($customer)
    {
        return json_encode(true);
    }

    /**
     * 1.4 Compatibility only. It does not have auto upgrade. We upgrade manually
     * Rename config table and create new
     * @throws PrestaShopDatabaseException
     */
    private function _compat14()
    {
        include_once(_PS_MODULE_DIR_.'priceminister/upgrade/Upgrade-4.2.01.php');

        // Create common configuration table if does not exist
        if (function_exists('upgrade_module_4_2_01')) {
            if (!upgrade_module_4_2_01($this)) {
                ConfigureMessage::error('ERROR: Cannot create common configuration table');
            }
        }
    }
}
