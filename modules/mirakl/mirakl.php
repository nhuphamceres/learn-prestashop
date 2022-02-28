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
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 *  Support by mail  :  support.mirakl@common-services.com
 */

require_once(dirname(__FILE__).'/classes/tools.class.php');
require_once(dirname(__FILE__).'/classes/mirakl.marketplace.php');
require_once(dirname(__FILE__).'/includes/mirakl.constant.php');

/**
 * Class Mirakl
 */
class Mirakl extends Module
{
    const ADD = 'a';
    const REMOVE = 'd';
    const UPDATE = 'u';

    const FIELD_DESCRIPTION_LONG = 1;
    const FIELD_DESCRIPTION_SHORT = 2;
    const FIELD_DESCRIPTION_BOTH = 3;
    const FIELD_DESCRIPTION_NONE = 4;
    const FIELD_DESCRIPTION_FEATURES = 2;

    const NAME_NAME_ONLY = 1;
    const NAME_NAME_ATTRIBUTES = 2;
    const NAME_BRAND_NAME_ATTRIBUTES = 3;
    const NAME_NAME_BRAND_ATTRIBUTES = 4;
    const NAME_NAME_REFERENCE = 5;

    const ATTRIBUTES_SHORT = 1;
    const ATTRIBUTES_LONG  = 2;
    const ATTRIBUTES_NO    = 3;
    const ATTRIBUTES_REF   = 4;

    const TRASH_DOMAIN = 'mirakl.mp.common-services.com';

    const SUPPLIER_REFERENCE = 1;
    const REFERENCE = 2;
    const CATEGORY = 3;
    const MANUFACTURER = 4;
    const UNITY = 5;
    const META_TITLE = 6;
    const META_DESCRIPTION = 7;
    const WEIGHT = 8;
    const UID = 9;

    const LF = "\n";

    // Tables
    const TABLE_MIRAKL_PRODUCT_OPTION = 'mirakl_product_option';
    // Sep-25-2018: Share mirakl_product_option for all marketplaces

    // Configurations
    const CONFIG_CURRENT_VERSION = 'MIRAKL_CURRENT_VERSION';    // Use 1 config for all marketplaces
    const CONFIG_INSTANT_TOKEN = 'MIRAKL_INSTANT_TOKEN';        // Global config, share between all shops
    const CONFIG_CONTEXT_DATA = 'MIRAKL_CONTEXT_DATA';          // Global config, encrypt
    const CONFIG_PS_TOKEN = 'MIRAKL_PS_TOKEN';                  // Only save when install, use for all marketplaces

    // Configurations - Not encrypt
    const CONFIG_SAVED = 'MIRAKL_SAVED_CONFIGURATION';          // Determine if specific store of specific marketplace is saved or not
    const CONFIG_CUSTOMER_ID = 'MIRAKL_CUSTOMER_ID';
    const CONFIG_ADDRESS_ID = 'MIRAKL_ADDRESS_ID';
    const CONFIG_ID_EMPLOYEE = 'MIRAKL_ID_EMPLOYEE';
    const CONFIG_USERNAME = 'MIRAKL_USERNAME';                  // todo: Maybe not used
    const CONFIG_API_KEY = 'MIRAKL_API_KEY';
    const CONFIG_DEBUG = 'MIRAKL_DEBUG';
    const CONFIG_CATEGORIES = 'MIRAKL_CATEGORIES';
    const CONFIG_CARRIER = 'MIRAKL_CARRIER';
    const CONFIG_DELIVERY_TIME = 'MIRAKL_DELIVERY_TIME';
    const CONFIG_ADDITIONAL_SHIPPING_PRICE = 'MIRAKL_ADDITIONAL_SP';
    const CONFIG_CARRIER_RELAY = 'MIRAKL_CARRIER_RELAY';
    const CONFIG_ORDER_STATES = 'MIRAKL_ORDERS_STATES';
    const CONFIG_ORDER_CARRIERS = 'MIRAKL_ORDER_CARRIERS';
    const CONFIG_USE_SPECIALS = 'MIRAKL_USE_SPECIALS';
    const CONFIG_USE_TAXES = 'MIRAKL_USE_TAXES';
    const CONFIG_PRODUCT_NAME = 'MIRAKL_PRODUCT_NAME';
    const CONFIG_DESCRIPTION_FIELD = 'MIRAKL_DECRIPTION_FIELD';
    const CONFIG_DESCRIPTION_FEATURES = 'MIRAKL_DECRIPTION_FEATURES';
    const CONFIG_DESCRIPTION_HTML = 'MIRAKL_DECRIPTION_HTML';
    const CONFIG_NO_IMAGE = 'MIRAKL_NO_IMAGE';
    const CONFIG_WAREHOUSE = 'MIRAKL_WAREHOUSE';
    const CONFIG_IMAGE_TYPE = 'MIRAKL_IMAGE_TYPE';
    const CONFIG_CUSTOMER_GROUP = 'MIRAKL_CUSTOMER_GROUP';
    const CONFIG_SMART_ROUNDING = 'MIRAKL_SMART_ROUNDING';

    const CONFIG_PREPRODUCTION = 'MIRAKL_PREPRODUCTION';
    const CONFIG_OPERATOR = 'MIRAKL_OPERATOR';
    const CONFIG_LAST_CREATE = 'MIRAKL_LAST_CREATE';
    const CONFIG_LAST_CREATE_CRON = 'MIRAKL_LAST_CREATE_CRON';
    const CONFIG_LAST_UPDATE = 'MIRAKL_LAST_UPDATE';
    const CONFIG_LAST_UPDATE_CRON = 'MIRAKL_LAST_UPDATE_CRON';
    const CONFIG_LAST_UPDATE_CRON_LITE = 'MIRAKL_LAST_UPDATE_CRON_LITE';

    // Configurations - Encrypt
    const CONFIG_PROFILES = 'MIRAKL_PROFILES';
    const CONFIG_PROFILE_TO_CATEGORY = 'MIRAKL_PROFILES_CAT';
    const CONFIG_FILTER_MFR = 'MIRAKL_FILTER_MFR';
    const CONFIG_FILTER_SUPPLIERS = 'MIRAKL_FILTER_SUPPLIERS';
    const CONFIG_LAST_IMPORT = 'MIRAKL_LAST_IMPORT';
    const CONFIG_LAST_CREATE_URL = 'MIRAKL_LAST_CREATE_URL';
    const CONFIG_LAST_UPDATE_URL = 'MIRAKL_LAST_UPDATE_URL';

    // Contain all data of selected marketplace, data in ini file
    public static $marketplace_params;
    // Uppercase of marketplace name in ini. todo: Maybe needn't it
    public static $marketplace_key = null;

    public static $prestashop_fields = array(
        self::SUPPLIER_REFERENCE,
        self::REFERENCE,
        self::CATEGORY,
        self::MANUFACTURER,
        self::UNITY,
        self::META_TITLE,
        self::META_DESCRIPTION,
        self::WEIGHT,
        self::UID
    );

    public static $instant_token = null;

    private $html        = '';
    private $post_errors = array();

    // todo: Fix this. Use while install / uninstall / postProcess, not encrypt
    private $vars = array(
        'mirakl_api_key' => array(
            'name' => 'API Key',
            'required' => true,
            'configuration' => self::CONFIG_API_KEY
        ),
        'preproduction' => array(
            'name' => 'preproduction',
            'required' => false,
            'configuration' => self::CONFIG_PREPRODUCTION
        ),
        'debug' => array(
            'name' => 'debug',
            'required' => false,
            'configuration' => self::CONFIG_DEBUG
        ),
        'delivery_time' => array(
            'name' => 'delivery time',
            'required' => true,
            'configuration' => self::CONFIG_DELIVERY_TIME,
        ),
        'additional_shipping_price' => array(
            'name' => 'normal shipping price',
            'configuration' => self::CONFIG_ADDITIONAL_SHIPPING_PRICE,
            'required' => false,
            'pattern' => 'isPrice'
        ),
        'carrier' => array(
            'name' => 'Carrier',
            'configuration' => self::CONFIG_CARRIER,
            'required' => true,
        ),
        'carrier_relay' => array(
            'name' => 'Carrier (Relay)',
            'configuration' => self::CONFIG_CARRIER_RELAY,
            'required' => false,
        ),
        'carrier_incoming' => array(
            'name' => 'Incoming carrier mapping',
            'configuration' => MiraklConstant::CONFIG_CARRIER_INCOMING_MAPPING,
            'required' => false,
        ),
//            'order_carriers' => array(
//                'name' => 'Carrier',
//                'configuration' => self::CONFIG_ORDER_CARRIERS,
//                'required' => true,
//            ),
        'orderstate' => array(
            'name' => 'order state',
            'required' => true,
            'configuration' => self::CONFIG_ORDER_STATES
        ),
        'category' => array(
            'name' => 'category',
            'required' => true,
            'configuration' => self::CONFIG_CATEGORIES
        ),
        'taxes' => array(
            'name' => 'Use Taxes',
            'required' => false,
            'default' => true,
            'configuration' => self::CONFIG_USE_TAXES
        ),
        'specials' => array(
            'name' => 'Use Specials',
            'required' => false,
            'default' => true,
            'configuration' => self::CONFIG_USE_SPECIALS
        ),
        'warehouse' => array(
            'name' => 'Warehouse',
            'required' => false,
            'configuration' => self::CONFIG_WAREHOUSE
        ),
        'image_type' => array(
            'name' => 'Image Type',
            'required' => false,
            'configuration' => self::CONFIG_IMAGE_TYPE
        ),
        'customer_group' => array(
            'name' => 'Customer Group',
            'required' => false,
            'configuration' => self::CONFIG_CUSTOMER_GROUP
        ),
        'description_field' => array(
            'name' => 'Description Field',
            'required' => false,
            'configuration' => self::CONFIG_DESCRIPTION_FIELD
        ),
        'product_name' => array(
            'name' => 'Description Field',
            'required' => false,
            'configuration' => self::CONFIG_PRODUCT_NAME
        ),
        'description_features' => array(
            'name' => 'Add Features',
            'required' => false,
            'configuration' => self::CONFIG_DESCRIPTION_FEATURES
        ),
        'description_html' => array(
            'name' => 'HTML Description',
            'required' => false,
            'configuration' => self::CONFIG_DESCRIPTION_HTML
        ),
        'smart_rounding' => array(
            'name' => 'Smart Rounding',
            'required' => false,
            'default' => false,
            'configuration' => self::CONFIG_SMART_ROUNDING
        ),
        'no_image' => array(
            'name' => 'Ignore products without images',
            'required' => false,
            'configuration' => self::CONFIG_NO_IMAGE
        ),
        // Marketplace specific configuration
        'mkp_specific_fields' => array(
            'required' => false,
            'configuration' => MiraklConstant::CONFIG_MKP_SPECIFIC_FIELDS,
        ),
    );

    // Only used when install / uninstall
    private static $config = array(
        self::CONFIG_OPERATOR           => null,
        self::CONFIG_CONTEXT_DATA       => null,
        self::CONFIG_CURRENT_VERSION    => null,
        self::CONFIG_LAST_IMPORT        => null,
        self::CONFIG_LAST_CREATE        => null,
        self::CONFIG_LAST_CREATE_URL    => null,
        self::CONFIG_LAST_UPDATE        => null,
        self::CONFIG_LAST_UPDATE_CRON   => null,
        self::CONFIG_LAST_UPDATE_URL    => null,
        self::CONFIG_PS_TOKEN           => null,
        self::CONFIG_PROFILES           => null,
        self::CONFIG_PROFILE_TO_CATEGORY => null,
        self::CONFIG_CUSTOMER_ID        => null,
        self::CONFIG_ADDRESS_ID         => null,
        self::CONFIG_FILTER_MFR         => null,
        self::CONFIG_FILTER_SUPPLIERS   => null,
        self::CONFIG_ID_EMPLOYEE        => null,
        self::CONFIG_INSTANT_TOKEN      => null
    );

    public $id_lang = null;

    private $categories = null;

    public static $features        = array();
    public static $features_values = array();

    public static $attributes        = array();
    public static $attributes_groups = array();

    public $marketplace_id_lang = null;
    public $page;
    public $url;
    public $path;
    public $images;
    public $js;
    public $ps16x = false;

    /** @var MiraklMigration */
    protected $migrationManager;

    /** @var MiraklHookManager */
    protected $hookManager;

    /** @var MiraklMarketplace Refactor */
    public $externalMkp;

    /** @var MiraklAdminConfigurationManager */
    protected $adminConfigurationManager;

    public function l($string, $specific = false, $id_lang = null)
    {
        if ($specific == false) {
            $specific = 'mirakl';
        }

        if (_PS_VERSION_ < '1.5') {
            return (parent::l($string, $specific, $id_lang));
        }

        return (Translate::getModuleTranslation($this->name, $string, $specific));
    }

    public function __construct()
    {
        $this->name = 'mirakl';
        $this->tab = 'market_place';
        $this->author = 'Common-Services';
        $this->version = '1.3.105';
        $this->need_instance = 1;
        $this->displayName = $this->l('Mirakl Marketplace');
        $this->description = $this->l('This extension allow to manage products, orders, stock from/to Mirakl');
        $this->bootstrap = true;
        $this->page = basename(__FILE__, '.php');
        $this->module_key = 'd64931b444351666f5a62f690fcc8528';
        $this->author_address = '0x96116FE33A6268AE9E878Dbc609A02BdCcc285E0';

        $this->url = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/';
        $this->path = _PS_MODULE_DIR_.$this->name.'/';
        $this->images = $this->url.'views/img/';
        $this->js = $this->url.'views/js/';

        if (_PS_VERSION_ < '1.5') {
            require(dirname(__FILE__).'/backward_compatibility/backward.php');
        }

        // Init marketplace before initContext
        require_once dirname(__FILE__).'/classes/mirakl.marketplace.php';
        self::$marketplace_params = MiraklMarketplace::init();
        $this->externalMkp = MiraklMarketplace::getInstance(MiraklMarketplace::$current_mkp, self::$marketplace_params);
        $this->initContext();

        self::$marketplace_key = Tools::strtoupper(self::$marketplace_params['name']);

        parent::__construct();

        require_once(_PS_MODULE_DIR_ . '/mirakl/includes/mirakl.migration.php');
        require_once(_PS_MODULE_DIR_ . '/mirakl/includes/mirakl.hook.manager.php');
        require_once(_PS_MODULE_DIR_ . '/mirakl/includes/mirakl.admin.configuration.manager.php');
        $this->migrationManager = new MiraklMigration($this, $this->context);
        $this->hookManager = new MiraklHookManager($this, $this->context);
        $this->adminConfigurationManager = new MiraklAdminConfigurationManager($this);
    }

    public function initContext()
    {
        // todo: Consider change name and description for different marketplace
        $marketplace_data = self::$marketplace_params;

        $this->ps16x = version_compare(_PS_VERSION_, '1.6', '>=');

        $this->id_lang = $this->marketplace_id_lang = (int)Context::getContext()->language->id;

        if (is_array($marketplace_data) && array_key_exists('lang', $marketplace_data)) {
            $language = MiraklTools::language($marketplace_data['lang']);
            if (is_array($language) && count($language)) {
                $this->id_lang = $this->marketplace_id_lang = (int)$language['id_lang'];
            }
        }

        if ((defined('PS_ADMIN_DIR') || defined('_PS_ADMIN_DIR_'))) {
            if (MiraklTools::moduleIsInstalled($this->name)) {
                if (!is_writeable($this->path)) {
                    $this->warning = sprintf($this->l('The export directory is not writeable... please fix permissions').' (%s)', $this->path);
                }

                if (!function_exists('curl_init')) {
                    $this->warning = $this->l('PHP cURL must be installed for this module working...');
                }

                if (!Configuration::get('PS_SHOP_ENABLE')) {
                    $this->warning = $this->l('Be carefull, your shop is in maintenance mode, the module could not work in that mode');
                }
            }
        }
    }

    public function install()
    {
        $pass = true;
        // Install Hooks

        foreach (self::$config as $key => $value) {
            if ($value == null) {
                continue;
            }

            // Don't care, it never happens
            if (!self::updateConfig($key, $value, true)) {
                $this->post_errors[] = sprintf('%s - key: %s, value: %s', $this->l('Unable to install : Some configuration values'), $key, nl2br(print_r($value, true)));
                $pass = false;
            }
        }

        // Oct-3rd-2018: We save default configs for each marketplace right before display configure page if it's the first time it show up.
        // So don't need to initialize while install

        self::updateConfigGlobalMarketplace(
            self::CONFIG_PS_TOKEN,
            md5(time() + rand(100, 999)),
            true
        );

        if (!parent::install()) {
            $this->post_errors[] = $this->l('Unable to install: parent()');
            $pass = false;
        }

        // Install Tabs
        $this->tabSetup(self::ADD);

        if (!$this->addMarketPlaceTables()) {
            $this->post_errors[] = $this->l('Unable to install: addMarketPlaceTables()');
            $this->post_errors[] = Db::getInstance()->getMsgError();
            $pass = false;
        }

        if (!$this->migrationManager->migrateDuringInstallation()) {
            $this->post_errors[] = array_merge($this->post_errors, $this->migrationManager->getErrors());
            $pass = false;
        }

        if (!$this->createCustomer()) {
            $this->post_errors[] = $this->l('Unable to install: createCustomer');
            $pass = false;
        }

        if ($pass) {
            require_once(dirname(__FILE__).'/classes/context.class.php');
            $pass = MiraklContext::save($this->context, $this->context->employee);
        }

        if (!$this->hookManager->registerHooks()) {
            foreach ($this->hookManager->getFailedHooks() as $failedHook) {
                $this->post_errors[] = $this->l('Unable to Register Hook').':'.$failedHook;
            }
        }

        return (bool)$pass;
    }


    public function uninstall()
    {
        $pass = true;

        if (!parent::uninstall()) {
            $pass = false;
        }

        // Remove Tabs
        $this->tabSetup(self::REMOVE);

        // UnInstall Hooks
        if (!$this->hookManager->unregisterHooks()) {
            foreach ($this->hookManager->getFailedHooks() as $failedHook) {
                $this->post_errors[] = $this->l('Unable to Unregister Hook').':'.$failedHook;
            }
        }

        if (!$this->removeMarketPlaceTables()) {
            $this->post_errors[] = $this->l('Unable to uninstall: MarketPlace Tables');
        }

        if (!$this->deleteCustomer()) {
            $this->post_errors[] = $this->l('Unable to install: _deleteCustomer');
        }

        foreach ($this->vars as $var) {     // Delete while uninstall
            Configuration::deleteByName($var['configuration']);
        }

        foreach (array_keys(self::$config) as $key) {
            if (!Configuration::deleteByName($key)) {
                $pass = $pass && false;
            }
        }

        return $pass;
    }

    /**
     * Display additional info about module.
     */
    public function moduleInfo()
    {
        $this->context->smarty->assign(array(
            'versionCheck' => $this->versionChecking(),
            'memoryPeak'   => $this->memoryPeak()
        ));

        $this->html .= $this->context->smarty->fetch($this->path.'views/templates/admin/configure/module_info.tpl');
    }

    /**
     * Check if version mismatch.
     * @return array
     */
    protected function versionChecking()
    {
        $version_checking = array('active' => false);
        $saved_version = Configuration::get(self::CONFIG_CURRENT_VERSION);

        if ((int)$saved_version) {
            if (version_compare($this->version, $saved_version, '>')) {
                $version_checking = array(
                    'active'            => true,
                    'class'             => $this->ps16x ? 'alert alert-success' : 'conf',
                    'savedVersion'      => $saved_version,
                    'currentVersion'    => $this->version
                );
            }
        }

        return $version_checking;
    }

    /**
     * Get post count and memory peak after save configuration.
     * @return array
     */
    protected function memoryPeak()
    {
        $peak = array('active' => false);

        if (Tools::isSubmit('validateForm_mirakl') && self::getConfig(self::CONFIG_DEBUG)) {
            $peak = array(
                'active'    => true,
                'memory'    => memory_get_peak_usage() / 1024 / 1024,
                'postCount' => count($_POST, COUNT_RECURSIVE)
            );
        }

        return $peak;
    }


    public function getContent()
    {
        // Check if current marketplace is saved before, if not, save default configs.
        // This helps merchant to minimize work for new marketplace.
        $this->saveDefaultConfigForMarketplace();

        self::$instant_token = $instant_token = md5(_PS_ROOT_DIR_._PS_VERSION_.(isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time()));

        self::updateConfigGlobalShop(self::CONFIG_INSTANT_TOKEN, $instant_token);

        $this->context->smarty->caching = false;
        $this->context->smarty->force_compile = true;

        require_once(_PS_MODULE_DIR_.$this->name.'/classes/shared/configure_message.class.php');

        if (Tools::isSubmit('validateForm_'.$this->name) && !$this->post_errors) {
            $this->postProcess();
            self::updateConfig(self::CONFIG_SAVED, true);       // Configs have been saved
        } elseif (Tools::isSubmit('mirakl-marketplace')) {
            $this->html .= $this->displayConfirmation(sprintf('%s : %s.', $this->l('Mirakl Marketplace changed to'), self::$marketplace_params['display_name']));
        }

        $this->displayForm();

        return $this->html;
    }

    /**
     * Get config for current marketplace
     * @param $key
     * @param $decode
     * @param null $id_lang
     * @param null $id_shop_group
     * @param null $id_shop
     * @return bool|mixed
     */
    public static function getConfig($key, $decode = false, $id_lang = null, $id_shop_group = null, $id_shop = null)
    {
        $current_mkp = MiraklMarketplace::getCurrentMarketplace();
        if (!$current_mkp) {
            return false;
        }

        // Get current configuration value
        $values_array = self::getConfigArray($key, $decode, $id_lang, $id_shop_group, $id_shop);

        // Check config for current marketplace
        if (!isset($values_array[$current_mkp])) {
            return false;
        }

        return $values_array[$current_mkp];
    }

    // Get one configuration for all marketplaces
    public static function getConfigGlobalMarketplace($key, $decode = false, $id_lang = null, $id_shop_group = null, $id_shop = null)
    {
        return self::getConfigRaw($key, $decode, $id_lang, $id_shop_group, $id_shop);
    }

    // Get global config for current marketplace
    public static function getConfigGlobalShop($key, $decode = false)
    {
        return self::getConfig($key, $decode, null, 0, 0);
    }

    /**
     * Update config for current marketplace
     * @param $key
     * @param $value
     * @param $encode
     * @param bool $html
     * @param null $id_shop_group
     * @param null $id_shop
     * @return bool
     */
    public static function updateConfig($key, $value, $encode = false, $html = false, $id_shop_group = null, $id_shop = null)
    {
        $current_mkp = MiraklMarketplace::getCurrentMarketplace();
        if (!$current_mkp) {
            return false;
        }

        // Get current configuration value
        $stored_values_array = self::getConfigArray($key, $encode, null, $id_shop_group, $id_shop);

        // Assign config for current marketplace
        $stored_values_array[$current_mkp] = $value;

        // Update config
        $new_values_serialize = serialize($stored_values_array);
        $new_values = $encode ? MiraklTools::encode($new_values_serialize) : $new_values_serialize;

        return Configuration::updateValue($key, $new_values, $html, $id_shop_group, $id_shop);
    }

    // Update one config for all marketplace
    public static function updateConfigGlobalMarketplace($key, $value, $encode, $html = false, $id_shop_group = null, $id_shop = null)
    {
        $value = $encode ? MiraklTools::encode($value) : $value;

        return Configuration::updateValue($key, $value, $html, $id_shop_group, $id_shop);
    }

    // Update global config for current marketplace
    public static function updateConfigGlobalShop($key, $value, $encode = false, $html = false)
    {
        return self::updateConfig($key, $value, $encode, $html, 0, 0);
    }

    /**
     * Get configuration by key in array format
     * @param $key
     * @param bool $decode Does value encrypt or not
     * @param $id_lang
     * @param $id_shop_group
     * @param $id_shop
     * @return array|bool|mixed
     */
    private static function getConfigArray($key, $decode, $id_lang, $id_shop_group, $id_shop)
    {
        $stored_values = self::getConfigRaw($key, $decode, $id_lang, $id_shop_group, $id_shop);
        // Most of configurations are array, for multi-marketplace
        $stored_values_array = MiraklTools::unSerialize($stored_values, true);

        if (!is_array($stored_values_array) || !count($stored_values_array)) {
            return array();
        }

        return $stored_values_array;
    }

    // Get raw configuration in db
    private static function getConfigRaw($key, $decode, $id_lang, $id_shop_group, $id_shop)
    {
        $stored_values_raw = Configuration::get($key, $id_lang, $id_shop_group, $id_shop);
        return $decode ? MiraklTools::decode($stored_values_raw) : $stored_values_raw;
    }

    private function postProcess()
    {
        foreach ($this->vars as $key => $value) {       // Save while submit
            $cfg = Tools::getValue($key);

            if ($value['required'] && $cfg == null) {
                $this->post_errors[] = $this->l(ucwords($value['name'])).' '.$this->l(' is required');
            } else {
                if (!empty($value['pattern']) && !call_user_func(array('Validate', $value['pattern']), $cfg)) {
                    $this->post_errors[] = $this->l(ucwords($value['name'])).' '.$this->l(' is not valid');
                } else {
                    if (!empty($value['function'])) {
                        $this->{$value['function']}();
                    }
                    // todo: Origin was encoding for array
                    self::updateConfig($value['configuration'], $cfg);
                }
            }
        }

        self::updateConfig(self::CONFIG_ID_EMPLOYEE, $this->context->employee->id);
        self::updateConfig(self::CONFIG_FILTER_MFR, Tools::getValue('excluded-manufacturers'), true);
        self::updateConfig(self::CONFIG_FILTER_SUPPLIERS, Tools::getValue('selected-suppliers'), true);

        require_once(dirname(__FILE__).'/classes/context.class.php');

        MiraklContext::save($this->context, $this->context->employee);

        /**
         * Save Profiles
         */
        $profiles = Tools::getValue('profiles');

        if (isset($profiles['price_rule']) && is_array($profiles['price_rule']) && count($profiles['price_rule'])) {
            $price_rules = $profiles['price_rule'];

            foreach ($price_rules as $rule) {
                $price_rules_errors = $this->checkPriceRules($rule);
                if (count($price_rules_errors)) {
                    // If has price errors, add them to error array
                    foreach ($price_rules_errors as $price_rules_error) {
                        MiraklConfigureMessage::error($price_rules_error);
                    }
                }
            }
        }

        self::updateConfig(self::CONFIG_PROFILES, $profiles, true);

        $profile2category = Tools::getValue('profile2category');

        self::updateConfig(self::CONFIG_PROFILE_TO_CATEGORY, $profile2category, true);

        if (!$this->addMarketPlaceTables()) {
            $this->post_errors[] = $this->l('Unable to install: addMarketPlaceTables()');
            $this->post_errors[] = Db::getInstance()->getMsgError();
        }

        if (!$this->migrationManager->migrateDuringSaveConfiguration() || !$this->migrationManager->migrate()) {
            $this->post_errors[] = array_merge($this->post_errors, $this->migrationManager->getErrors());
        }

        // Install Hooks
        if (!$this->hookManager->registerHooks()) {
            foreach ($this->hookManager->getFailedHooks() as $failedHook) {
                $this->post_errors[] = $this->l('Unable to Register Hook').':'.$failedHook;
            }
        }

        // Update Tabs
        $this->tabSetup(self::UPDATE);

        if (!count($this->post_errors)) {
            $this->html .= $this->displayConfirmation($this->l('Configuration has been saved'));
        } else {
            foreach ($this->post_errors as $err) {
                $this->html .= $this->displayError($err);
            }
        }

        if (Tools::getValue('mirakl_api_key')) {
            Configuration::updateValue(self::CONFIG_CURRENT_VERSION, $this->version);
        }
    }

    /**
     * @param $price_rules
     * @return array
     */
    private function checkPriceRules($price_rules)
    {
        if (!is_array($price_rules)) {
            return array($this->l('An error occured with price rules, not an array.'));
        }

        $error = array();

        $rule = isset($price_rules['rule']) ? $price_rules['rule'] : null;
        $type = isset($price_rules['type']) ? $price_rules['type'] : null;

        if (!is_array($rule['from']) || !count($rule['from']) || !is_array($rule['to']) || !count($rule['to'])) {
            return ($error);
        }

        if (!reset($rule['from']) && !reset($rule['to'])) {
            return ($error);
        }

        if ((reset($rule['from']) && !reset($rule['to'])) || (!reset($rule['from']) && reset($rule['from']) != '0' && reset($rule['to']))) {
            $error[] = sprintf('%s => %s', $this->l('Price rule incomplete'), $this->l('Missing range element'));

            return ($error);
        }

        if (($type == 'percent' && !reset($rule['percent'])) || ($type == 'value' && !reset($rule['value']))) {
            $error[] = sprintf('%s => %s', $this->l('Price rule incomplete'), $this->l('Missing value'));

            return ($error);
        }

        $prev_from = -1;
        $prev_to = -1;

        foreach ($rule['from'] as $key => $val) {
            if (max($prev_from, $val) == $prev_from) {
                $error[] = sprintf('%s => %s %d', $this->l('Your range FROM is lower than the previous one'), $this->l('Rule ligne'), $key + 1);
                break;
            } elseif ($rule['to'][$key] && max($prev_to, $rule['to'][$key]) == $prev_to) {
                $error[] = sprintf('%s => %s %d', $this->l('Your range TO is lower than the previous one'), $this->l('Rule ligne'), $key + 1);
                break;
            } elseif ($rule['to'][$key] && max($rule['to'][$key], $val) == $val) {
                $error[] = sprintf('%s => %s %d', $this->l('Your range TO is lower than your range FROM'), $this->l('Rule ligne'), $key + 1);
                break;
            }

            $prev_from = $val;
            $prev_to = $rule['to'][$key];
        }

        return ($error);
    }


    private function selectedTab()
    {
        if (MiraklMarketplace::getCurrentMarketplace()) {
            return 'menu-informations';
        }

        return (Tools::getValue('selected_tab') ? Tools::getValue('selected_tab') : 'menu-mirakl');
    }


    public function displayForm()
    {
        require_once(_PS_MODULE_DIR_.$this->name.'/classes/shared/configure_tab.class.php');
        require_once(_PS_MODULE_DIR_.$this->name.'/classes/context.class.php');
        require_once(_PS_MODULE_DIR_.$this->name.'/classes/support.class.php');

        $token = Mirakl::getConfigGlobalMarketplace(Mirakl::CONFIG_PS_TOKEN, true);
        if (!Tools::strlen($token)) {
            self::updateConfigGlobalMarketplace(
                self::CONFIG_PS_TOKEN,
                md5(time() + rand(100, 999)),
                true
            );
        }

        $marketplace_params = self::$marketplace_params;

        if ($this->categories == null) {
            $this->categories = Category::getCategories((int)$this->id_lang, false);
        }

        $this->loadFeatures();
        $this->loadAttributes();

        $instant_token_param = 'instant_token='.self::$instant_token;
        $context_key_param   = 'context_key='.MiraklContext::getKey($this->context->shop);
        $debug = (bool)self::getConfig(self::CONFIG_DEBUG);
        $miraklSupport = new MiraklSupport();

        $view_params = array(
            'request_uri'   => Tools::htmlentitiesUTF8(filter_input(INPUT_SERVER, 'REQUEST_URI')),
            'images_url'    => $this->images,
            'js_url'        => $this->js,
            'module_url'    => $this->url,
            'module_path'   => dirname(__FILE__),
            'tools_url'     => $this->url."functions/tools.php?$instant_token_param&$context_key_param",
            'check_url'     => $this->url."functions/check.php?$instant_token_param",
            'id_lang'            => $this->id_lang,
            'module_description' => $this->description,
            'version'            => $this->version,
            'mirakl_app_name'    => $this->name,
            'ps_version'         => _PS_VERSION_,
            'mirakl_token'       => self::$instant_token,
            // Marketplace configurations
            'mkps'                  => MiraklMarketplace::getMarketplaces(),
            'current_mkp'           => MiraklMarketplace::getCurrentMarketplace(),
            'md5_shop_name'         => MiraklMarketplace::getConfigFileName(),
            'me_marketplace_email'  => $marketplace_params['email'],
            'marketplace_logo'      => isset($marketplace_params['marketplace_logo']) ? $marketplace_params['marketplace_logo'] : null,
            'me_name'               => $this->displayName,
            'me_version'            => $this->version,
            // Configuration tabs
            'selected_tab'      => $this->selectedTab(),
            'me_informations'   => $this->information(),
            'me_profiles'       => $this->profiles(),
            'me_categories'     => $this->categories(),
            'me_transport'      => $this->transport(),
            'me_orders'         => $this->orders(),
            'me_settings'       => $this->settings(),
            'me_filters'        => $this->filters(),
            'me_cron'           => $this->cron(),
            'me_mkp_specific'   => array(), // Placeholder for marketplace specific configuration
            // Context params
            'context'            => MiraklContext::getContextParams(),
            'mirakl_context_key' => MiraklContext::getKey($this->context->shop),
            // Credentials
            'me_mirakl_api_key' => self::getConfig(self::CONFIG_API_KEY),
            'me_debug'          => $debug ? 'checked="checked"' : '',
            'me_debug_style'    => $debug ? ' style="color:red" ' : '',
            // Support
            'me_tutorial_credentials' => $miraklSupport->gethreflink(MiraklSupport::TUTORIAL_API_KEYPAIRS),
            'me_tutorial_profiles'    => $miraklSupport->gethreflink(MiraklSupport::TUTORIAL_PROFILES),
            'me_tutorial_categories'  => $miraklSupport->gethreflink(MiraklSupport::TUTORIAL_CATEGORIES),
            'me_tutorial_transport'   => $miraklSupport->gethreflink(MiraklSupport::TUTORIAL_TRANSPORT),
            'me_tutorial_orders'      => $miraklSupport->gethreflink(MiraklSupport::TUTORIAL_ORDERS),
            'me_tutorial_parameters'  => $miraklSupport->gethreflink(MiraklSupport::TUTORIAL_PARAMETERS),
            'me_tutorial_cron'        => $miraklSupport->gethreflink(MiraklSupport::TUTORIAL_CRON),
            'me_tutorial_filters'     => $miraklSupport->gethreflink(MiraklSupport::TUTORIAL_FILTERS),
            // Others
            'alert_class' => array(
                'danger'    => $this->ps16x ? 'alert alert-danger' : 'error',
                'warning'   => $this->ps16x ? 'alert alert-warning' : 'warn',
                'success'   => $this->ps16x ? 'alert alert-success' : 'conf',
                'info'      => $this->ps16x ? 'alert alert-info' : 'info'
            ),
        );

        $tab_list = array(
            array('id' => 'mirakl', 'img' => 'mirakl', 'name' => $this->displayName, 'selected' => true),
            array('id' => 'informations', 'img' => 'information', 'name' => 'Informations', 'selected' => false),
        );
        if ($this->externalMkp->getCurrentMarketplaceR() != MiraklMarketplace::MKP_TEMP) {
            $tab_list = array_merge($tab_list, array(
                array('id' => 'credentials', 'img' => 'key', 'name' => $this->l('Credentials'), 'selected' => false),
                array('id' => 'profiles', 'img' => 'profiles', 'name' => $this->l('Profiles'), 'selected' => false),
                array('id' => 'categories', 'img' => 'categories', 'name' => $this->l('Categories'), 'selected' => false),
                array('id' => 'transport', 'img' => 'lorry', 'name' => $this->l('Transport'), 'selected' => false),
                array('id' => 'orders', 'img' => 'calculator', 'name' => $this->l('Orders'), 'selected' => false),
                array('id' => 'settings', 'img' => 'cog_edit', 'name' => $this->l('Settings'), 'selected' => false),
                array('id' => 'filters', 'img' => 'filter', 'name' => $this->l('Filters'), 'selected' => false),
                array('id' => 'cron', 'img' => 'clock', 'name' => $this->l('Cron'), 'selected' => false),
            ));
        }
        if ($this->externalMkp->hasAdditionalConfiguration()) {
            // todo: Change to the icon of particular marketplace
            $tab_list[] = array('id' => 'mkp_specific', 'img' => 'mirakl', 'name' => $this->externalMkp->getDisplayNameR(), 'selected' => false);
            $view_params['me_mkp_specific'] = $this->adminConfigurationManager->tabMkpAdditional();
        }

        $this->context->smarty->assign($view_params)->assign('mirakl_tab_list', $tab_list);

        $this->moduleInfo();
        $this->html .= MiraklConfigureMessage::display()   // Display all errors, warnings...
            . $this->context->smarty->fetch($this->path . 'views/templates/admin/configure/header.tpl')
            . ConfigureTab::generateTabs($tab_list, $this->name)
            . $this->context->smarty->fetch($this->path . 'views/templates/admin/configure/configuration.tpl');
    }

    private function information()
    {
        if ((bool)Configuration::get('PS_FORCE_SMARTY_2') == true) {
            die(Tools::displayError('This module is not compatible with Smarty v2. Please switch to Smarty v3 in Preferences Tab.'));
        }

        $lang = Language::getIsoById($this->id_lang);

        // Display only if the module seems to be configured
        $display = true;

        $php_infos = array();
        $prestashop_infos = array();

        // PHP Configuration Check
        if (!function_exists('curl_init')) {
            $php_infos['curl'] = array();
            $php_infos['curl']['message'] = $this->l('PHP cURL must be installed on this server. The module require the cURL library and can\'t work without');
            $php_infos['curl']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
            $php_infos['curl']['link'] = 'http://php.net/manual/'.$lang.'/book.curl.php';
        }

        if (!method_exists('DOMDocument', 'createElement')) {
            $php_infos['curl'] = array();
            $php_infos['curl']['message'] = $this->l(
                'PHP DOMDocument (XML Library) must be installed on this server.
                                                The module require this library and can\'t work without'
            );
            $php_infos['curl']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
            $php_infos['curl']['link'] = 'http://php.net/manual/'.$lang.'/class.domdocument.php';
        }

        if (is_dir(_PS_MODULE_DIR_.'mirakl/export') && !is_writable(_PS_MODULE_DIR_.'mirakl/export')) {
            $php_infos['export_permissions']['message'] = sprintf($this->l('You have to set write permissions to the %s directory and its subsequents files'), _PS_MODULE_DIR_.'mirakl/export');
            $php_infos['export_permissions']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        }

        if (($max_execution_time = ini_get('max_execution_time')) && $max_execution_time < 120) {
            $php_infos['maintenance']['message'] = sprintf($this->l('PHP value: max_execution_time recommended value is at least 120. your limit is currently set to %d'), $max_execution_time);
            $php_infos['maintenance']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }

        $disable_functions = array_map('trim', explode(',', ini_get('disable_functions')));
        if (in_array('parse_ini_file', $disable_functions)) {
            $php_infos['parse_ini_file']['message'] = $this->l(
                'PHP function: parse_ini_file() must be enabled on this server.
                    The module requires this function and can not work without it.'
            );
            $php_infos['parse_ini_file']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        }

        // Memory Limit
        $memory_limit = ini_get('memory_limit');
        $unit = Tools::strtolower(Tools::substr($memory_limit, -1));
        $val = (float)preg_replace('[^0-9]', '', $memory_limit);
        switch ($unit) {
            case 'g':
                $val = $val * 1024 * 1024 * 1024;
                break;
            case 'm':
                $val = $val * 1024 * 1024;
                break;
            case 'k':
                $val = $val * 1024;
                break;
            default:
                $val = false;
        }

        // Switch to MB
        $memory_limit = $val / (1024 * 1024);

        $recommended_memory_limit = 128;
        if ($memory_limit < $recommended_memory_limit) {
            $php_infos['memory']['message'] = sprintf(
                $this->l('PHP value: memory_limit recommended value is at least %sMB. your limit is currently set to %sMB').html_entity_decode('&lt;br /&gt;'),
                $recommended_memory_limit,
                $memory_limit
            );
            $php_infos['memory']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }

        if ((ini_get('suhosin.post.max_vars') && ini_get('suhosin.post.max_vars') < 1000) || (ini_get('suhosin.request.max_vars') && ini_get('suhosin.request.max_vars') < 1000)) {
            $php_infos['suhosin']['message'] = sprintf($this->l('PHP value: suhosin/max_vars could trouble your module configuration').html_entity_decode('&lt;br /&gt;'));
            $php_infos['suhosin']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }

        if (ini_get('max_input_vars') != null && ini_get('max_input_vars') < 1000) {
            $php_infos['max_input_vars']['message'] = sprintf($this->l('PHP value: max_input_vars could trouble your module configuration').html_entity_decode('&lt;br /&gt;'));
            $php_infos['max_input_vars']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }

        // Prestashop Configuration Check
        if (!(int)Configuration::get('PS_SHOP_ENABLE')) {
            $prestashop_infos['maintenance']['message'] = $this->l('Be careful, your shop is in maintenance mode, the module might not work in that mode');
            $prestashop_infos['maintenance']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }

        // Check if birthday is mandatory
        $pass = true;
        $customer_check = new Customer();

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $custom_required_fields = $customer_check->getfieldsRequiredDatabase();

            if (is_array($custom_required_fields) && count($custom_required_fields)) {
                foreach ($custom_required_fields as $custom_required_field) {
                    if (isset($custom_required_field['field_name']) && $custom_required_field['field_name'] == 'birthday') {
                        $pass = false;
                    }
                }
            }
        }

        $customer_rules = $customer_check->getValidationRules('Customer');
        $pass = $pass && !(is_array($customer_rules['required']) && in_array('birthday', $customer_rules['required']));

        if (!$pass) {
            $prestashop_infos['birthday_issue']['message'] = $this->l(
                'Birthday field is required which is not the default in Prestashop core program.
                                                                 This configuration is not allowed by Marketplaces modules. Please fix it !'
            );
            $prestashop_infos['birthday_issue']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        }

        // Check if SuperAdmin has not been removed
        if (!Validate::isLoadedObject(new Employee(1))) {
            $prestashop_infos['employee_issue']['message'] = $this->l(
                'Employee #1 is missing and mandatory. This is the SuperAdministrator,
                                                                     it has certainly be removed by mistake, please restore it.'
            );
            $prestashop_infos['employee_issue']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
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

        $view_params = array();
        $view_params['images'] = $this->images;
        $view_params['display'] = $display;
        $view_params['php_infos'] = $php_infos;
        $view_params['php_info_ok'] = $php_info_ok;
        $view_params['prestashop_infos'] = $prestashop_infos;
        $view_params['prestashop_info_ok'] = $prestashop_info_ok;
        $view_params['url'] = $this->url;
        $view_params['support_informations_url'] = $this->url.'functions/check.php?id_lang='.$this->id_lang.
            '&instant_token='.self::$instant_token.
            '&selected-mkp='.$this->externalMkp->getCurrentMarketplaceR();

        return $view_params;
    }


    private function orders()
    {
        $id_lang = $this->context->cookie->id_lang;
        $view_params = array(
            'ps_order_states' => OrderState::getOrderStates($id_lang),
            'mi_order_states' => array(),
        );
        $savedMiraklStates = self::getConfig(self::CONFIG_ORDER_STATES);
        /*
          'MIRAKL_CA' => 'Mirakl - Commande accept e',
          'MIRAKL_CE' => 'Mirakl - Commande exp di e',
          'MIRAKL_CL' => 'Mirakl - Commande livr e'
         */
        $orderConfigs = array(
            array(
                'name' => 'MIRAKL_CA',
                'enable' => true,
                'default' => '_PS_OS_PAYMENT_',
                'desc' => $this->l('Choose a default incoming order status'),
                'help_text' => $this->l('Choose the default order state for new incoming orders'),
            ),
            array(
                'name' => 'MIRAKL_CE',
                'enable' => true,
                'default' => '_PS_OS_SHIPPING_',
                'desc' => $this->l('Choose a default sent order status'),
                'help_text' => $this->l('Choose the default order state for sent orders'),
            ),
            array(
                'name' => 'MIRAKL_CL',
                'enable' => $this->externalMkp->hasUpdateOrderDeliveryStatus(),
                'default' => '_PS_OS_DELIVERED_',
                'desc' => $this->l('Choose a default received order status'),
                'help_text' => $this->l('Choose the default order state for delivered orders'),
            ),
        );
        foreach ($orderConfigs as $orderConfig) {
            $oConfigName = $orderConfig['name'];
            $oConfigDefault = $orderConfig['default'];

            $orderState = is_array($savedMiraklStates) && isset($savedMiraklStates[$oConfigName]) ? $savedMiraklStates[$oConfigName] : '';
            if (!$orderState) {
                $orderState = defined($oConfigDefault) ? constant($oConfigDefault) : (int)Configuration::get($oConfigDefault);
            }
            $view_params['mi_order_states'][] = array_merge($orderConfig, array(
                'value' => $orderState,
            ));
        }

        // Carriers
//        $order_carriers = self::getConfig(self::CONFIG_ORDER_CARRIERS);
//        $carriers = Carrier::getCarriers($this->context->cookie->id_lang);

        /*
        if (!isset($order_carriers['relay'])) {
            $order_carriers['relay'] = null;
        }

        if (!isset($order_carriers['standard'])) {
            $order_carriers['standard'] = null;
        }

        $view_params['order_carriers'] = array();
        $view_params['order_carriers']['standard'] = array();
        $view_params['order_carriers']['relay'] = array();

        foreach ($carriers as $carrier) {
            $carrier_array = array();
            $carrier_array['value'] = (int)$carrier['id_carrier'];
            $carrier_array['desc'] = $carrier['name'];

            $carrier_array['selected'] = (int)$carrier['id_carrier'] == $order_carriers['standard'] ? 'selected="selected"' : '';
            $view_params['order_carriers']['standard'][] = $carrier_array;

            $carrier_array['selected'] = (int)$carrier['id_carrier'] == $order_carriers['relay'] ? 'selected="selected"' : '';
            $view_params['order_carriers']['relay'][] = $carrier_array;
        }
        */
        return $view_params;
    }


    private function transport()
    {
        // Oct-06-2018: Get all possible carriers, include module's
        if (defined('Carrier::ALL_CARRIERS')) {
            $all_carriers_const = Carrier::ALL_CARRIERS;
        } elseif (defined('ALL_CARRIERS')) {
            $all_carriers_const = ALL_CARRIERS;
        } else {
            $all_carriers_const = 5;
        }
        $carriers = Carrier::getCarriers($this->context->cookie->id_lang, false, false, false, null, $all_carriers_const);
        $delivery_time = self::getConfig(self::CONFIG_DELIVERY_TIME);
        $incomingMappings = self::getConfig(MiraklConstant::CONFIG_CARRIER_INCOMING_MAPPING);

        return array(
            'additional_shipping_price' => number_format((float)self::getConfig(self::CONFIG_ADDITIONAL_SHIPPING_PRICE), 2),
            'delivery_time' => is_string($delivery_time) ? $delivery_time : 1,
            'std_carrier_selected' => (int)self::getConfig(self::CONFIG_CARRIER),
            'relay_carrier_selected' => (int)self::getConfig(self::CONFIG_CARRIER_RELAY),   // Not used yet
            'mkp_carriers' => self::getConfig(MiraklConstant::CONFIG_CARRIERS_MKP),
            'ps_carriers' => array_map(function ($carrier) {
                return array(
                    'id_carrier' => $carrier['id_carrier'],
                    'carrier_name' => $carrier['name'],
                );
            }, $carriers),
            'incoming_mapping' => is_array($incomingMappings) ? $incomingMappings : array(),
        );
    }


    private function settings()
    {
        $view_params = array();
        $view_params['specials'] = self::getConfig(self::CONFIG_USE_SPECIALS) ? 'checked="checked"' : '';
        $view_params['taxes']    = self::getConfig(self::CONFIG_USE_TAXES) ? 'checked="checked"' : '';
        $view_params['smart_rounding'] = self::getConfig(self::CONFIG_SMART_ROUNDING) ? 'checked="checked"' : '';

        $product_name_pre = (int)self::getConfig(self::CONFIG_PRODUCT_NAME);
        $product_name = ($product_name_pre ? $product_name_pre : self::NAME_NAME_ONLY);

        $view_params['product_name_only_checked'] = false;
        $view_params['product_name_attr_checked'] = false;
        $view_params['product_name_brand_attr_checked'] = false;
        $view_params['product_brand_name_attr_checked'] = false;
        $view_params['product_name_ref_checked'] = false;

        $view_params['name_name_only'] = self::NAME_NAME_ONLY;
        $view_params['name_name_attributes'] = self::NAME_NAME_ATTRIBUTES;
        $view_params['name_brand_name_attributes'] = self::NAME_BRAND_NAME_ATTRIBUTES;
        $view_params['name_name_brand_attributes'] = self::NAME_NAME_BRAND_ATTRIBUTES;
        $view_params['name_name_reference'] = self::NAME_NAME_REFERENCE;

        switch ($product_name) {
            case self::NAME_NAME_ONLY:
                $view_params['product_name_only_checked'] = true;
                break;
            case self::NAME_NAME_ATTRIBUTES:
                $view_params['product_name_attr_checked'] = true;
                break;
            case self::NAME_BRAND_NAME_ATTRIBUTES:
                $view_params['product_brand_name_attr_checked'] = true;
                break;
            case self::NAME_NAME_BRAND_ATTRIBUTES:
                $view_params['product_name_brand_attr_checked'] = true;
                break;
            case self::NAME_NAME_REFERENCE:
                $view_params['product_name_ref_checked'] = true;
                break;
        }

        $decription_field_pre = self::getConfig(self::CONFIG_DESCRIPTION_FIELD);
        $decription_field = ($decription_field_pre ? $decription_field_pre : self::FIELD_DESCRIPTION_LONG);

        $view_params['long_description'] = self::FIELD_DESCRIPTION_LONG;
        $view_params['long_description_checked'] = $decription_field == self::FIELD_DESCRIPTION_LONG ? 'checked="checked"' : '';
        $view_params['short_description'] = self::FIELD_DESCRIPTION_SHORT;
        $view_params['short_description_checked'] = $decription_field == self::FIELD_DESCRIPTION_SHORT ? 'checked="checked"' : '';
        $view_params['both_description'] = self::FIELD_DESCRIPTION_BOTH;
        $view_params['both_description_checked'] = $decription_field == self::FIELD_DESCRIPTION_BOTH ? 'checked="checked"' : '';
        $view_params['none_description'] = self::FIELD_DESCRIPTION_NONE;
        $view_params['none_description_checked'] = $decription_field == self::FIELD_DESCRIPTION_NONE ? 'checked="checked"' : '';


        $decription_features = (bool)self::getConfig(self::CONFIG_DESCRIPTION_FEATURES);
        $decription_html = (bool)self::getConfig(self::CONFIG_DESCRIPTION_HTML);

        $view_params['description_features'] = 1;
        $view_params['description_features_checked'] = $decription_features ? 'checked="checked"' : '';

        $view_params['description_html'] = 1;
        $view_params['description_html_checked'] = $decription_html ? 'checked="checked"' : '';

        $no_image = self::getConfig(self::CONFIG_NO_IMAGE);
        if ($no_image === false) { // default value
            $no_image = true;
        }
        $view_params['no_image'] = (bool)$no_image;

        // Shop Configuration
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $view_params['ps_version_gt_15_or_equal'] = '1';

            // Warehouse (PS 1.5 with Stock Management)
            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                $view_params['ps_advanced_stock_management'] = '1';
                $view_params['warehouse_options'] = array();

                foreach (Warehouse::getWarehouses(true) as $warehouse) {
                    $warehouse_array = array();
                    if ((int)$warehouse['id_warehouse'] == (int)self::getConfig(self::CONFIG_WAREHOUSE)) {
                        $selected = 'selected="selected"';
                    } else {
                        $selected = '';
                    }

                    $warehouse_array['value'] = (int)$warehouse['id_warehouse'];
                    $warehouse_array['selected'] = $selected;
                    $warehouse_array['desc'] = $warehouse['name'];
                    $view_params['warehouse_options'][] = $warehouse_array;
                }
            }
        }

        // Image Type (PS 1.5+)
        if (method_exists('ImageType', 'getImagesTypes')) {
            $images_types = ImageType::getImagesTypes();
            $image_type = self::getConfig(self::CONFIG_IMAGE_TYPE);

            if (is_array($images_types) && count($images_types)) {
                foreach ($images_types as $img_type) {
                    $image_type_option = array();

                    if (!(bool)$img_type['products']) {
                        continue;
                    } elseif ($img_type['name'] == $image_type) {
                        $selected = 'selected="selected"';
                    } else {
                        $selected = '';
                    }

                    $image_type_option['selected'] = $selected;
                    $image_type_option['value'] = $img_type['name'];
                    $image_type_option['desc'] = $img_type['name'];
                    $view_params['image_types'][] = $image_type_option;
                }
                $view_params['image_types'] = array_reverse($view_params['image_types']);
            }
        }

        // Customer groups
        $view_params['customer_groups'] = array();
        $customer_group = self::getConfig(self::CONFIG_CUSTOMER_GROUP);
        foreach (Group::getGroups(Context::getContext()->language->id) as $group) {
            // id_group name
            $selected = $group['id_group'] == $customer_group ? 'selected="selected"' : '';
            $view_params['customer_groups'][] = array(
                'selected' => $selected,
                'value' => $group['id_group'],
                'desc' => $group['name']
            );
        }

        return $view_params;
    }


    private function cron()
    {
        require_once(dirname(__FILE__).'/classes/context.class.php');

        $token          = self::getConfigGlobalMarketplace(self::CONFIG_PS_TOKEN, true);
        $context_param  = MiraklContext::getContextParamUrl();
        $marketplace    = MiraklMarketplace::getCurrentMarketplace();

        $module_path  = MiraklTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_);
        $short_module = preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', $module_path).'/'.$this->name;
        $url          = "$module_path/$this->name/functions/";
        $query        = "action=cron&metoken=$token&selected-mkp=$marketplace&$context_param";
        $query_lite   = "action=cron_lite&metoken=$token&selected-mkp=$marketplace&$context_param";

        $id_shop = Context::getContext()->shop->id;

        $view_params = array(
            'cronjobs' => array(
                'exists' => is_dir(_PS_MODULE_DIR_.'cronjobs/'),
                'installed' => (bool)MiraklTools::moduleIsInstalled('cronjobs')
            ),
            'stdtypes' => array('update', 'update_1_month', 'accept', 'import', 'update_orders'),
            'update' => array(
                'title'     => $this->l('Offers Update'),
                'url'       => $url.'products_update.php?'.$query,
                'url_short' => $short_module.'/functions/products_update.php?...',
                'frequency' => 1
            ),
            'update_lite' => array(
                'title'     => $this->l('Offers Update Lite'),
                'url'       => $url.'products_update.php?'.$query_lite,
                'url_short' => $short_module.'/functions/products_update.php?...',
                'frequency' => 1
            ),
            'update_1_month' => array(
                'title'     => $this->l('Offers Update').' - 24H',
                'url'       => $url.'products_update.php?'.'all-offers=1&'.$query,
                'url_short' => $short_module.'/functions/products_update.php?...',
                'frequency' => 24
            ),
            'accept' => array(
                'title'     => $this->l('Orders - Acceptation'),
                'url'       => $url.'orders_accept.php?'.$query,
                'url_short' => $short_module.'/functions/orders_accept.php?...',
                'frequency' => 1
            ),
            'import' => array(
                'title'     => $this->l('Orders - Import'),
                'url'       => $url.'orders_import.php?'.$query,
                'url_short' => $short_module.'/functions/orders_import.php?...',
                'frequency' => 1
            ),
            'update_orders' => array(
                'title'     => $this->l('Orders Status Update').' - 24H',
                'url'       => $url.'cron_order_status.php?id_shop='.$id_shop,
                'url_short' => $short_module.'/functions/cron_order_status.php?id_shop='.$id_shop,
                'frequency' => 24
            ),
            'update_url'            => $url.'products_update.php?'.$query,
            'update_lite_url'       => $url.'products_update.php?'.$query_lite,
            'update_1_month_url'    => $url.'products_update.php?'.'all-offers=1&'.$query,
            'accept_url'            => $url.'orders_accept.php?'.$query,
            'import_url'            => $url.'orders_import.php?'.$query,
            'update_orders_url'     => $url.'cron_order_status.php?'.$query
        );

        return $view_params;
    }

    private function categories()
    {
        $view_params = array();

        if ($this->categories == null) {
            $categories = Category::getCategories((int)$this->id_lang, false);
            $this->categories = $categories;
        } else {
            $categories = $this->categories;
        }
        $index = array();

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
            $categories_array = reset($categories);
            $first1 = key($categories);

            $first2 = key($categories_array);

            $first = $categories[$first1][$first2];
            $default_category = 1;
        }

        $default_categories = self::getConfig(self::CONFIG_CATEGORIES);
        $default_profiles2categories = self::getConfig(self::CONFIG_PROFILE_TO_CATEGORY, true);

        $view_params['list'] = self::recurseCategoryForInclude($index, $categories, $first, $default_category, null, $default_categories, $default_profiles2categories, true);
        $view_params['profiles'] = self::getConfig(self::CONFIG_PROFILES, true);
        return $view_params;
    }


    public function recurseCategoryForInclude($indexed_categories, $categories, $current, $id_category = 1, $id_category_default = null, $default_categories = array(), $default_profiles = array(), $init = false)
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

        if (!$current) {
            $current = array(
                'infos' => array(
                    'id_parent' => 0,
                    'level_depth' => 0,
                    'name' => ''
                )
            );
        }

        if (!isset($done[$current['infos']['id_parent']])) {
            $done[$current['infos']['id_parent']] = 0;
        }

        $done[$current['infos']['id_parent']] += 1;

        $todo = count($categories[$current['infos']['id_parent']]);
        $done_c = $done[$current['infos']['id_parent']];

        $level = $current['infos']['level_depth'] + 1;
        $img = ($init == true) ? 'lv1.gif' : 'lv'.$level.'_'.($todo == $done_c ? 'f' : 'b').'.gif';
        $selected_profile = null;
        $saved_profiles = self::getConfig(self::CONFIG_PROFILES, true);

        if (is_array($saved_profiles) && array_key_exists('name', $saved_profiles) && count($saved_profiles['name'])) {
            unset($saved_profiles['name']['_key_']);
            foreach ($saved_profiles['name'] as $profile_id => $profile_name) {
                if ($profile_name == null || !is_numeric($profile_id)) {
                    continue;
                }

                if (isset($default_profiles[$id_category]) && $default_profiles[$id_category] == $profile_name) {
                    $selected_profile = $profile_name;
                } elseif (count($saved_profiles['name']) == 1) {
                    $selected_profile = $profile_name;
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
            'profile' => $selected_profile
        );

        if (isset($categories[$id_category])) {
            if ($categories[$id_category]) {
                foreach (array_keys($categories[$id_category]) as $key) {
                    if ($key != 'infos') {
                        self::recurseCategoryForInclude($indexed_categories, $categories, $categories[$id_category][$key], $key, $id_category_default, $default_categories, $default_profiles);
                    }
                }
            }
        }

        return $categories_table;
    }


    private function profiles()
    {
        $view_params = array();

        $token = self::getConfigGlobalMarketplace(self::CONFIG_PS_TOKEN, true);

        $view_params['hierarchies_url'] = MiraklTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/functions/hierarchies.php?metoken='.$token;

        $profiles = self::getConfig(self::CONFIG_PROFILES, true);
        $current_currency = Currency::getDefaultCurrency();

        $view_params['attributes_short'] = self::ATTRIBUTES_SHORT;
        $view_params['attributes_long'] = self::ATTRIBUTES_LONG;
        $view_params['attributes_no'] = self::ATTRIBUTES_NO;
        $view_params['attributes_ref'] = self::ATTRIBUTES_REF;

        if ($profiles === false) {
            $profiles = array_fill_keys(array('name', 'model', 'formula', 'shipping_rule', 'warranty', 'combinations_attr'), null);
            $profiles['name'][1] = $this->l('Default');
        }

        // Form Template
        $profiles['name']['_key_'] = null;
        $profiles['model']['_key_'] = null;
        $profiles['formula']['_key_'] = null;
        $profiles['shipping_rule']['_key_'] = null;
        $profiles['warranty']['_key_'] = null;
        $profiles['combinations_attr']['_key_'] = null;

        // marketplace specific fields
        $mkp_additional_config = array();
        if ($this->externalMkp->hasAdditionalConfiguration()) {
            $mkp_additional_config = $this->adminConfigurationManager->tabMkpAdditional();
            foreach ($mkp_additional_config['specific_fields'] as $field_name => $field_type) {
                $profiles['specific_fields'][$field_name]['_key_'] = null;
            }
        }

        if ($profiles['name']) {
            $view_params['profiles_data'] = array();
        }

        $m = false;
        foreach ($profiles['name'] as $profile_id => $profile_name) {
            $profile_data = array();

            if ($profile_name == null && isset($m) && $m == true) {
                continue;
            } elseif ($profile_name == null) {
                $profile_div = 'id="master-profile" style="display:none"';
                $m = true;
            } else {
                $profile_div = 'id="profile-'.$profile_id.'"';
            }

            $profile_data['profile_div'] = $profile_div;
            $profile_data['profile_id'] = $profile_id;
            $profile_data['name'] = isset($profiles['name'][$profile_id]) ? $profiles['name'][$profile_id] : '';
            $profile_data['model'] = isset($profiles['model'][$profile_id]) ? $profiles['model'][$profile_id] : '';
            $profile_data['formula'] = isset($profiles['formula'][$profile_id]) ? $profiles['formula'][$profile_id] : '';
            $profile_data['shipping_rule'] = isset($profiles['shipping_rule'][$profile_id]) ? $profiles['shipping_rule'][$profile_id] : '';
            $profile_data['warranty'] = isset($profiles['warranty'][$profile_id]) ? (int)$profiles['warranty'][$profile_id] : '';
            $profile_data['combinations_attr'] = isset($profiles['combinations_attr'][$profile_id]) ? $profiles['combinations_attr'][$profile_id] : '';
            $profile_data['min_quantity_alert'] = isset($profiles['min_quantity_alert'][$profile_id]) ? $profiles['min_quantity_alert'][$profile_id] : '';
            $profile_data['logistic_class'] = isset($profiles['logistic_class'][$profile_id]) ? $profiles['logistic_class'][$profile_id] : '';
            if (!empty($mkp_additional_config)) {
                foreach ($mkp_additional_config['specific_fields'] as $field_name => $field_type) {
                    $profile_data['specific_fields'][$field_name] = $field_type;
                    $profile_data['specific_fields'][$field_name]['selected'] = isset($profiles['specific_fields'][$field_name][$profile_id]) ? $profiles['specific_fields'][$field_name][$profile_id] : '';
                }
            }

            if (isset($profiles['price_rule'][$profile_id]) && is_array($profiles['price_rule'][$profile_id]) && isset($profiles['price_rule'][$profile_id]['rule']['from']) && is_array($profiles['price_rule'][$profile_id]['rule']['from']) && isset($profiles['price_rule'][$profile_id]['rule']['to']) && is_array($profiles['price_rule'][$profile_id]['rule']['to'])) {
                $profile_data['price_rule']['currency_sign'] = isset($current_currency->sign) ? $current_currency->sign : ($current_currency->iso_code ? $current_currency->iso_code: null);
                $profile_data['price_rule']['type'] = isset($profiles['price_rule'][$profile_id]['type']) ? $profiles['price_rule'][$profile_id]['type'] : 'percent';

                if (isset($profiles['price_rule'][$profile_id]['rule']['from']) && is_array($profiles['price_rule'][$profile_id]['rule']['from']) && isset($profiles['price_rule'][$profile_id]['rule']['to']) && is_array($profiles['price_rule'][$profile_id]['rule']['to'])) {
                    $profile_data['price_rule']['rule'] = $profiles['price_rule'][$profile_id]['rule'];
                    if (!count($profiles['price_rule'][$profile_id]['rule']['from']) && !count($profiles['price_rule'][$profile_id]['rule']['to']) && !count($profiles['price_rule'][$profile_id]['rule']['value'])) {
                        $profile_data['price_rule']['rule']['from'][0] = '';
                        $profile_data['price_rule']['rule']['to'][0] = '';
                        $profile_data['price_rule']['rule']['percent'][0] = '';
                        $profile_data['price_rule']['rule']['value'][0] = '';
                    }
                }
            } else {
                // first use
                $profile_data['price_rule']['currency_sign'] = isset($current_currency->sign) ? $current_currency->sign : ($current_currency->iso_code ? $current_currency->iso_code: null);
                $profile_data['price_rule']['type'] = 'percent';
                $profile_data['price_rule']['rule']['from'][0] = '';
                $profile_data['price_rule']['rule']['to'][0] = '';
                $profile_data['price_rule']['rule']['percent'][0] = '';
                $profile_data['price_rule']['rule']['value'][0] = '';
            }

            if (array_key_exists('additionnals', self::$marketplace_params)) {
                $profile_data['features'] = isset(self::$features[$this->id_lang]) ? self::$features[$this->id_lang] : null;
                $profile_data['attributes'] = isset(self::$attributes_groups[$this->id_lang]) ? self::$attributes_groups[$this->id_lang] : null;
                $profile_data['prestashop_fields'] = array();

                foreach (self::$prestashop_fields as $id_prestashop_field) {
                    switch ($id_prestashop_field) {
                        case self::SUPPLIER_REFERENCE:
                            $name = $this->l('Supplier Reference');
                            break;
                        case self::REFERENCE:
                            $name = $this->l('Reference');
                            break;
                        case self::CATEGORY:
                            $name = $this->l('Category');
                            break;
                        case self::MANUFACTURER:
                            $name = $this->l('Manufacturer');
                            break;
                        case self::META_TITLE:
                            $name = $this->l('Meta Title');
                            break;
                        case self::META_DESCRIPTION:
                            $name = $this->l('Meta Description');
                            break;
                        case self::UNITY:
                            $name = $this->l('Unity');
                            break;
                        case self::WEIGHT:
                            $name = $this->l('Weight');
                            break;
                        case self::UID:
                            $name = $this->l('Id Type + Id');
                            break;
                        default:
                            $name = null;
                            break;
                    }
                    if ($name == null) {
                        continue;
                    }
                    $profile_data['prestashop_fields'][$id_prestashop_field] = $name;
                }
                $profile_data['additionnals'] = array();

                foreach (self::$marketplace_params['additionnals'] as $additionnal) {
                    $additionnal_field_name = $additionnal['mirakl'];

                    if (array_key_exists($additionnal_field_name, $profiles) && isset($profiles[$additionnal_field_name][$profile_id]) && Tools::strlen($profiles[$additionnal_field_name][$profile_id])) {
                        $selected_field = explode('-', $profiles[$additionnal_field_name][$profile_id]);
                        $field_type = $selected_field[0];
                        $field_id = $selected_field[1];
                        $additionnal['selected']['type'] = $field_type;
                        $additionnal['selected']['id'] = $field_id;
                    }

                    $profile_data['additionnals'][] = $additionnal;
                }
            }

            $view_params['profiles_data'][] = $profile_data;
        }

        return $view_params;
    }


    private function filters()
    {
        $view_params = array();
        $view_params['selected_tab'] = $this->selectedTab();
        $view_params['selected_tab_filters'] = $view_params['selected_tab'] == 'filters' ? 'selected' : '';
        $view_params['images'] = $this->images;
        $view_params['url'] = $this->url;

        $selected_manufacturers = self::getConfig(self::CONFIG_FILTER_MFR, true);
        $selected_suppliers = self::getConfig(self::CONFIG_FILTER_SUPPLIERS, true);

        // Manufacturers Filtering
        $manufacturers = Manufacturer::getManufacturers(false, $this->id_lang);

        $filtered_manufacturers = array();
        $available_manufacturers = array();

        if (is_array($manufacturers) && count($manufacturers)) {
            foreach ($manufacturers as $manufacturer) {
                if (is_array($selected_manufacturers) && in_array((string)$manufacturer['id_manufacturer'], $selected_manufacturers)) {
                    continue;
                }

                $available_manufacturers[$manufacturer['id_manufacturer']] = $manufacturer['name'];
            }

            if (is_array($selected_manufacturers) && count($selected_manufacturers)) {
                foreach ($manufacturers as $manufacturer) {
                    if (is_array($selected_manufacturers) && !in_array((string)$manufacturer['id_manufacturer'], $selected_manufacturers)) {
                        continue;
                    }

                    $filtered_manufacturers[$manufacturer['id_manufacturer']] = $manufacturer['name'];
                }
            }
        }

        $view_params['manufacturers'] = array();
        $view_params['manufacturers']['available'] = $available_manufacturers;
        $view_params['manufacturers']['filtered'] = $filtered_manufacturers;

        // Suppliers Filtering
        $suppliers = Supplier::getSuppliers(false, $this->id_lang);

        $filtered_suppliers = array();
        $available_suppliers = array();

        if (is_array($suppliers) && count($suppliers)) {
            foreach ($suppliers as $supplier) {
                if (is_array($selected_suppliers) && in_array((string)$supplier['id_supplier'], $selected_suppliers)) {
                    continue;
                }

                $available_suppliers[$supplier['id_supplier']] = $supplier['name'];
            }

            if (is_array($selected_suppliers) && count($selected_suppliers)) {
                foreach ($suppliers as $supplier) {
                    if (is_array($selected_suppliers) && !in_array((string)$supplier['id_supplier'], $selected_suppliers)) {
                        continue;
                    }

                    $filtered_suppliers[$supplier['id_supplier']] = $supplier['name'];
                }
            }
        }

        $view_params['suppliers'] = array();
        $view_params['suppliers']['available'] = $available_suppliers;
        $view_params['suppliers']['filtered'] = $filtered_suppliers;

        return $view_params;
    }

    /*
     * HOOKS
     */

    // Move hookSetup() to hook manager

    public function hookBackOfficeHeader($params)
    {
        return $this->hookDisplayBackOfficeHeader($params);
    }


    public function hookAdminOrder($params)
    {
        return $this->hookDisplayAdminOrder($params);
    }

    public function hookDisplayAdminOrderMain($params)
    {
        return $this->hookDisplayAdminOrder($params);
    }

    /**
     * HOOKS
     */
    public function hookDisplayBackOfficeHeader($params)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            return;
        }

        $html = null;
        $Tab = null;

        $Tab = Tools::strtolower(Tools::getValue('tab')) == 'admincatalog';

        if ($Tab && Tools::getValue('id_product') && (Tools::getValue('addproduct') !== false || Tools::getValue('updateproduct') !== false)) {
            $html .= html_entity_decode('&lt;').'meta name="'.$this->name.'-options" content="'.$this->url.'functions/product_extme.php" '.html_entity_decode('/&gt;')."\n";
            $html .= html_entity_decode('&lt;').'meta name="'.$this->name.'-options-json" content="'.$this->url.'functions/product_ext.json.php" '.html_entity_decode('/&gt;')."\n";

            $html .= $this->autoAddCSS($this->url.'views/css/product_ext.css');
            $html .= $this->autoAddJS($this->url.'views/js/product_extme.js?marketplace='.$this->name);
        }

        if (version_compare(_PS_VERSION_, '1.4', '<')) {
            print($html);

            return;
        }

        return ($html);
    }


    public function hookDisplayAdminOrder($params)
    {
        // Gestion des relations avec la MarketPlace sur la commande
        require_once(_PS_MODULE_DIR_.$this->name.'/classes/order.class.php');

        $id_order = (int)$params['id_order'];

        $order = new MiraklOrder($id_order);

        // Not a Mirakl order
        $marketplaces = array_merge(array('mirakl'), array_keys(MiraklMarketplace::getMarketplaces()));
        if (!in_array($order->module, $marketplaces)) {
            return (false);
        }

        // Set up the marketplace
        $_GET['selected-mkp'] = Tools::strtolower($order->marketplace_channel ?: $order->payment);
        Mirakl::$marketplace_params = MiraklMarketplace::init();

        $delivery_address = new Address((int)$order->id_address_delivery);
        $pr_id = null;

        if (Validate::isLoadedObject($delivery_address) && preg_match('/[__PR__]/', $delivery_address->other)) {
            $pr_id = explode('__PR__', $delivery_address->other);
            $pr_id = $pr_id[1];
        }

        // For Quick Access
        // 2021-06-27: Remove $orders_url = 'http://marchand.mirakl.com/dashboard/main/main'; MIRAKL_PREPRODUCTION

        // Real order URL
        // Example : https://vidaxl-prod.mirakl.net/mmp/shop/order/33101411362-A/information
        $orders_url = sprintf(
            '%s/mmp/shop/order/%s/information',
            str_replace('/api/', '', Mirakl::$marketplace_params['endpoint']),
            $order->marketplace_order_id
        );

        $this->html .= $this->autoAddCSS($this->url.'views/css/orders_sheet.css');
        $this->context->smarty->assign(array(
            'module_url'        => $this->url,
            'mirakl_order_id'   => $order->marketplace_order_id,
            'mirakl_channel'    => Tools::getValue('selected-mkp'),
            'mirakl_order_latest_ship_date' => $order->shipping_deadline,
            'ps16x'             => $this->ps16x,
            'ps177x'            => version_compare(_PS_VERSION_, '1.7.7', '>='),
            'image_path'        => $this->images,
            'order_url'         => $orders_url,
            'pr_id'             => $pr_id,
            'token'             => Mirakl::getConfigGlobalMarketplace(Mirakl::CONFIG_PS_TOKEN, true),
            'additional_fields'  => Db::getInstance()->executeS(
                'SELECT `code`, `value` FROM `'._DB_PREFIX_.'mirakl_order_additional_fields`
                WHERE `id_order` = '.(int)$id_order
            )
        ));
        $this->html .= $this->context->smarty->fetch($this->path.'views/templates/admin/orders/admin_order.tpl');

        return $this->html;
    }

    // todo: Refactor to non-hook action (cronjob)
    public function hookActionOrderStatusUpdate($params) // TODO postUpdate ?
    {
        if (!isset($params['is_cron']) || !$params['is_cron']) {
            return false;
        }

        $debug = (bool)Tools::getValue('debug', self::getConfig(self::CONFIG_DEBUG));

        require_once(_PS_MODULE_DIR_.$this->name.'/classes/order.class.php');
        require_once(_PS_MODULE_DIR_.$this->name.'/classes/mirakl.api.orders.php');

        $is_syncing_shipping = $params['is_syncing_shipping'];
        $id_order = (int)$params['id_order'];
        $order = new MiraklOrder($id_order);

        // Not a Mirakl order
        $marketplaces = array_merge(
            array('mirakl'),
            array_keys(MiraklMarketplace::getMarketplaces())
        );
        // if ($order->module != $this->name && $order->module != 'mirakl') {
        if (!in_array($order->module, $marketplaces)) {
            printf('Not a Mirakl order.');
            return false;
        }

        $order_states = self::getConfig(self::CONFIG_ORDER_STATES);
        if (!is_array($order_states)) {
            $order_states = @unserialize(Configuration::get(self::CONFIG_ORDER_STATES))[MiraklMarketplace::getCurrentMarketplace()];
        }

        if (!is_array($order_states)) {
            printf('$order_states not configured yet.');
            return false;
        } // not configured yet

        $mirakl_params = self::$marketplace_params;
        $mirakl_params['debug'] = $debug;
        $mirakl_params['api_key'] = self::getConfig(self::CONFIG_API_KEY);

        $mirakl = new MiraklApiOrders($mirakl_params);

        $order_state_sent = $order_states['MIRAKL_CE'];  // commande expediee
        $order_state_delivered = isset($order_states['MIRAKL_CL']) ? $order_states['MIRAKL_CL'] : 0;  // commande livree
        // Matching Order Status
        switch ($params['newOrderStatus']->id) {
            case $order_state_sent:
                break;
            case $order_state_delivered:
                if ($this->externalMkp->hasUpdateOrderDeliveryStatus()) {
                    return $mirakl->additionalFields($order->marketplace_order_id, array(array(
                       'code' => 'fecha-entrega',
                       'value' => date('c')
                    )));
                }

                printf('New order status delivered is not implemented yet.');
                return false;
            default:
                printf('New order status not sent or delivered.');
                return false;
        }

        $tracking_number = MiraklOrder::getShippingNumber($order);

        // get carrier by id_reference instead of id_carrier
        $id_carrier = Db::getInstance()->getValue('
            SELECT c1.id_carrier FROM ' . _DB_PREFIX_ . 'carrier c1
            WHERE c1.active = 1 AND c1.id_reference IN (
                SELECT c2.id_reference FROM ' . _DB_PREFIX_ . 'carrier c2
                WHERE c2.id_carrier = ' . (int)$order->id_carrier .'
            )'
        );
        $carrier = new Carrier($id_carrier);

        if (!Validate::isLoadedObject($carrier)) {
            printf('%s/%s: %s (%d)', basename(__FILE__), __LINE__, $this->l('Unable to load carrier'), (int)$order->id_carrier);
            return false;
        }

        $oid = $order->marketplace_order_id;

        if (empty($oid)) {
            printf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Missing Marketplace Order Id'));
            return false;
        }

        if ($tracking_number) {
            $process_shipping = array(
                'carrier_name' => $carrier->name,
                'tracking_number' => $tracking_number,
            );
            if (isset($carrier->url) && !empty($carrier->url)) {
                $process_shipping['carrier_url'] = str_replace('@', $order->shipping_number, $carrier->url);
            }
        } else {
            printf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Tracking Number is Empty'));
            return false;
        }

        $mirakl->tracking($oid, $process_shipping);
        // only sync the shipping if it was not synced before
        if ($is_syncing_shipping) {
            $mirakl->ship($oid);
        }

        return true;
    }


    public function hookActionCarrierUpdate($params)
    {
        // incoming carrier mapping
        $id_shop_group = Context::getContext()->shop->id_shop_group;
        $id_shop = Context::getContext()->shop->id;

        $marketplaces_config = MiraklTools::unSerialize(self::getConfigGlobalMarketplace(MiraklConstant::CONFIG_CARRIER_INCOMING_MAPPING, false, null, $id_shop_group, $id_shop));

        if (is_array($marketplaces_config)) {
            foreach ($marketplaces_config as &$marketplace) {
                foreach ($marketplace as &$carrier_mapping) {
                    if ($carrier_mapping['ps'] == $params['id_carrier']) {
                        $carrier_mapping['ps'] = $params['carrier']->id;
                    }
                }
            }

            self::updateConfigGlobalMarketplace(MiraklConstant::CONFIG_CARRIER_INCOMING_MAPPING, serialize($marketplaces_config), false, false, $id_shop_group, $id_shop);
        }

        /*
        // Standard
        $me_carrier = (int)self::getConfig(self::CONFIG_CARRIER);

        if ($me_carrier) {
            if ($me_carrier == $params['id_carrier']) {
                $me_carrier = $params['carrier']->id;
            }

            self::updateConfig(self::CONFIG_CARRIER, $me_carrier);
        }

        // Relay
        $me_carrier = (int)self::getConfig(self::CONFIG_CARRIER_RELAY);

        if ($me_carrier) {
            if ($me_carrier == $params['id_carrier']) {
                $me_carrier = $params['carrier']->id;
            }

            self::updateConfig(self::CONFIG_CARRIER_RELAY, $me_carrier);
        }
        */
    }


    public function hookUpdateCarrier($params)
    {
        return $this->hookActionCarrierUpdate($params);
    }

    public function hookActionAdminOrdersListingFieldsModifier($params)
    {
        $this->hookManager->actionAdminOrdersListingFieldsModifier($params);
    }
    public function hookActionOrderGridDefinitionModifier($params)
    {
        $this->hookManager->actionOrderGridDefinitionModifier($params);
    }
    public function hookActionOrderGridQueryBuilderModifier($params)
    {
        $this->hookManager->actionOrderGridQueryBuilderModifier($params);
    }

    public function hookDisplayPDFInvoice($object)
    {
        return $this->hookManager->displayPDFInvoice($object);
    }


    /* *********************************
     *  INSTALLATION
     * ****************************** */
    private function tabSetup($action)
    {
        $adminOrders = version_compare(_PS_VERSION_, '1.7', '>=') ? 'AdminParentOrders' : 'AdminOrders';

        require_once(dirname(__FILE__).'/classes/shared/tab.class.php');
        require_once(dirname(__FILE__).'/classes/product.class.php');

        $pass = true;

        // Adding Tab
        switch ($action) {
            case self::ADD:
            case self::UPDATE:
                if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                    if (!Tab::getIdFromClassName('AdminMiraklProducts')) {
                        $pass = $pass && CommonServicesTab::Setup($action, 'AdminMiraklProducts', $this->displayName, 'AdminCatalog');
                    }
                    if (!Tab::getIdFromClassName('AdminMiraklOrders')) {
                        $pass = $pass && CommonServicesTab::Setup($action, 'AdminMiraklOrders', $this->displayName, $adminOrders);
                    }
                } else {
                    $pass = $pass && CommonServicesTab::Setup($action, 'MiraklProduct', $this->displayName, 'AdminCatalog');
                    $pass = $pass && CommonServicesTab::Setup($action, 'MiraklOrder', $this->displayName, 'AdminOrders');
                }
                break;
            case self::REMOVE:
                if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                    $pass = $pass && CommonServicesTab::Setup($action, 'AdminMiraklProducts', $this->displayName, 'AdminCatalog');
                    $pass = $pass && CommonServicesTab::Setup($action, 'AdminMiraklOrders', $this->displayName, $adminOrders);
                } else {
                    $pass = $pass && CommonServicesTab::Setup($action, 'MiraklProduct', $this->displayName, 'AdminCatalog');
                    $pass = $pass && CommonServicesTab::Setup($action, 'MiraklOrder', $this->displayName, 'AdminOrders');
                }
                break;
            default:
                return (false);
        }

        return ($pass);
    }

    private function addMarketPlaceTables()
    {
        // MarketPlace Optionnal Table
        $pass = true;

        $table_name = _DB_PREFIX_.'mirakl_order_additional_fields';
        if (!MiraklTools::tableExists($table_name)) {
            $sql = 'CREATE TABLE IF NOT EXISTS `'.pSQL($table_name).'` (
                `id_mirakl_order_additional_fields` INT(11) NOT NULL AUTO_INCREMENT,
                `id_order` INT(11) NOT NULL,
                `code` VARCHAR(32) NOT NULL DEFAULT "",
                `value` VARCHAR(128) NOT NULL DEFAULT "",
                PRIMARY KEY  (`id_mirakl_order_additional_fields`),
                UNIQUE KEY `id_order_code_uk` (`id_order`, `code`)
            )';

            if (!Db::getInstance()->Execute($sql)) {
                $pass = false;
            }
        }
        return $pass;
    }


    private function removeMarketPlaceTables()
    {
        $pass = true;
        $table_name = _DB_PREFIX_.self::TABLE_MIRAKL_PRODUCT_OPTION;

        if (MiraklTools::tableExists($table_name)) {
            $sql = 'DROP TABLE IF EXISTS `'.pSQL($table_name).'` ; ';

            if (!Db::getInstance()->Execute($sql)) {
                $pass = false;
            }
        }

        return $pass;
    }


    private function createCustomer()
    {
        // ADD a customer / it will hold the market place orders
        // the customer is fake and has a random id, and random password
        $pass = true;
        $address_id = null;

        // Fake email
        $var = explode('@', Configuration::get('PS_SHOP_EMAIL'));
        if (!count($var) || !count($var) > 1) {
            $var = array('', 'ma-boutique.com');
        }
        $email = 'no-reply-'.rand(500, 99999999).'@'.$var[1];

        $customer = new Customer();
        $customer->firstname = 'Mirakl';
        $customer->lastname = 'Mirakl';
        $customer->email = $email;
        $customer->newsletter = false;
        $customer->optin = false;
        $customer->passwd = md5(rand(50000000, 99999999));
        $customer->active = true;
        $customer->add();

        if (!Validate::isLoadedObject($customer)) {
            return false;
        }

        self::updateConfig(self::CONFIG_CUSTOMER_ID, $customer->id);

        $address_id = $this->createAddress($customer->id);

        if (!$address_id) {
            $pass = false;
        }

        self::updateConfig(self::CONFIG_ADDRESS_ID, (int)$address_id);

        return $pass;
    }


    private function createAddress($id_customer)
    {
        $address = new Address();
        $address->id_customer = $id_customer;
        $address->id_country = Country::getByIso('fr');
        $address->country = 'France';
        $address->alias = 'Mirakl';
        $address->lastname = 'Mirakl';
        $address->firstname = 'Marketplace';
        $address->address1 = '4 place des Saisons';
        $address->postcode = '92400';
        $address->city = 'Courbevoie';
        $address->phone = '0972355111';
        $address->phone_mobile = '0972355111';
        $address->add();

        return $address->id;
    }


    private function deleteCustomer()
    {
        $customer = new Customer();
        $customer->id = self::getConfig(self::CONFIG_CUSTOMER_ID);

        return $customer->delete();
    }

    private function autoAddCSS($url, $media = 'all')
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            return ($this->context->controller->addCSS($url, $media) && '');
        } else {
            return (sprintf(html_entity_decode('&lt;link rel="stylesheet" type="text/css" href="%s"&gt;'), $url));
        }
    }

    private function autoAddJS($url)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            if (isset($this->context->controller) && method_exists($this->context->controller, 'addJquery')) {
                $this->context->controller->addJquery();
            }

            return ($this->context->controller->addJS($url) && '');
        } else {
            return sprintf(html_entity_decode('&lt;script type="text/javascript" src="%s"&gt;&lt;/script&gt;'), $url);
        }
    }


    protected static function loadAttributes($inactives = false)
    {
        $languages = Language::getLanguages();

        self::$attributes_groups = array();
        self::$attributes = array();

        foreach ($languages as $language) {
            $id_lang = $language['id_lang'];

            $attributes_groups = AttributeGroup::getAttributesGroups($id_lang);

            if (is_array($attributes_groups) && count($attributes_groups)) {
                self::$attributes_groups[$id_lang] = array();

                foreach ($attributes_groups as $attribute_group) {
                    $id_attribute_group = (int)$attribute_group['id_attribute_group'];

                    self::$attributes_groups[$id_lang][$id_attribute_group] = $attribute_group;
                }
            } else {
                self::$attributes_groups[$id_lang] = array();
            }

            $attributes = Attribute::getAttributes($id_lang, true);

            if (is_array($attributes) && count($attributes)) {
                self::$attributes[$id_lang] = array();

                foreach ($attributes as $attribute) {
                    $id_attribute_group = (int)$attribute['id_attribute_group'];
                    $id_attribute = (int)$attribute['id_attribute'];

                    self::$attributes[$id_lang][$id_attribute_group][$id_attribute] = $attribute;
                }
            } else {
                self::$attributes[$id_lang] = array();
            }
        }
    }

    protected static function loadFeatures($inactives = false, $custom = true)
    {
        $languages = Language::getLanguages();

        foreach ($languages as $language) {
            $id_lang = $language['id_lang'];

            $features = Feature::getFeatures($id_lang);

            if (is_array($features) && count($features)) {
                foreach ($features as $feature) {
                    $id_feature = (int)$feature['id_feature'];

                    $features_values = FeatureValue::getFeatureValuesWithLang($id_lang, $id_feature, $custom);

                    if (is_array($features_values) && count($features_values)) {
                        $feature['is_color_feature'] = false; // Used by Profiles and Mapping

                        self::$features[$id_lang][$id_feature] = $feature;

                        foreach ($features_values as $feature_value) {
                            $feature_value['name'] = $feature['name'];
                            self::$features_values[$id_lang][$id_feature][$feature_value['id_feature_value']] = $feature_value;
                        }
                    }
                }
            } else {
                self::$features_values[$id_lang] = array();
            }
        }
    }

    /**
     * Save default configurations for current marketplace
     */
    private function saveDefaultConfigForMarketplace()
    {
        if (!self::getConfig(self::CONFIG_SAVED)) {
            foreach ($this->vars as $var) {     // Init for first time
                self::updateConfig($var['configuration'], (!empty($var['default']) ? $var['default'] : ''));
            }
        }
    }

    public static function getExportDir()
    {
        $directory = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, realpath(basename(__FILE__."/../")))."/export/";

        return $directory;
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        require_once(_PS_MODULE_DIR_.$this->name.'/classes/product_tab.class.php');

        $adminProductTab = new MiraklExtManager();

        $html = $adminProductTab->marketplaceProductTabContent($params);

        if (Tools::strlen($html)) {
            return ($html);
        } else {
            return (nl2br("\n"));
        } // Prevents error: "A server error occurred while loading the tabs: some tabs could not be loaded."
    }

    /**
     * GDPR compliant: Export customer data
     * @param string|array $customer
     * @return string
     */
    public function hookActionExportGDPRData($customer)
    {
        return json_encode(array());
    }

    /**
     * GDPR compliant: Delete customer data
     * @param string|array $customer
     * @return string
     */
    public function hookActionDeleteGDPRCustomer($customer)
    {
        return json_encode(true);
    }
}
