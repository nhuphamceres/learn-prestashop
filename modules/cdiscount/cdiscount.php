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
 * @author    Olivier B.
 * @copyright Copyright (c) 2011-2017 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   CDiscount
  * Support by mail:  support.cdiscount@common-services.com
 */

if (!defined('DS')) {
    define('DS', '/');
}
define('CDISCOUNT_EXPERIMENTAL_FEATURES', in_array($_SERVER['SERVER_ADDR'], array('91.121.46.68', 'w.x.y.z')) || isset($_SERVER['DropBox']));

require_once(_PS_MODULE_DIR_ . 'cdiscount/common/tools.class.php');
require_once(_PS_MODULE_DIR_ . 'cdiscount/common/configuration.class.php');
require_once(_PS_MODULE_DIR_ . 'cdiscount/classes/cdiscount.configuration.class.php');
require_once(dirname(__FILE__) . '/includes/cdiscount.constant.php');

if (!class_exists('ConfigureMessage')) {
    require_once(_PS_MODULE_DIR_.'cdiscount/classes/shared/configure_message.class.php');
}

class Cdiscount extends Module
{
    const ENABLE_EXPERIMENTAL_FEATURES = CDISCOUNT_EXPERIMENTAL_FEATURES;

    const MODULE = 'cdiscount';
    const NAME = 'CDiscount';
    const TITLE = 'Cdiscount';
    const KEY = 'CDISCOUNT';

    const TABLE_PRODUCT_OPTION = 'cdiscount_product_option';
    const TABLE_CDISCOUNT_OFFERS = 'cdiscount_offers';

    const ADD = 'a';
    const REMOVE = 'd';
    const UPDATE = 'u';

    const IMPORT_BY_ID = 1;
    const IMPORT_BY_SKU = 2;

    const TITLE_NAME_ATTRIBUTES = 1;
    const TITLE_BRAND_NAME_ATTRIBUTES = 2;
    const TITLE_CATEGORY_NAME_ATTRIBUTES = 3;
    const TITLE_CATEGORY_BRAND_NAME_ATTRIBUTES = 4;
    const TITLE_NAME_REFERENCE = 5;
    const TITLE_NAME_ATTRIBUTES_WITH_LABEL = 6;

    const IMPORT_MAX_CATEGORIES = 1000;

    const PERMISSIONS_DIRECTORY = 0775;
    const PERMISSIONS_STD_FILE = 0664;

    const EAN_POLICY_NORMAL = 1;
    const EAN_POLICY_EXEMPT = 2;
    const EAN_POLICY_PERMISSIVE = 3;

    const TRASH_DOMAIN = 'cdiscount.mp.common-services.com';

    const FIELD_DESCRIPTION_LONG  = 1;
    const FIELD_DESCRIPTION_SHORT = 2;

    const XML_DIRECTORY = 'xml';
    const LF = "\n";

    public static $default_categories = array();

    public static $channel_colors = array('1' => 'navy', '2' => 'pink', '3' => 'green', '4' => 'silver', '5' => 'orange');

    public static $carrier_labels
        = array(
            'Standard' => 'Standard',
            'Tracked' => 'Suivi',
            'Registered' => 'Recommand&eacute;',
            'EnMagasin' => 'Retrait imm&eacute;diat en magasin',
            'RelaisColis' => 'Relais Colis',
            'SoColissimo' => 'So Colissimo',
            'Relay' => 'En Magasin',
            'MondialRelay' => 'Mondial Relay',
            'TNT' => 'TNT',
            'BigParcelEco' => 'Livraison Gros colis Eco (>30kg)',
            'BigParcelStandard' => 'Livraison Gros colis Standard (>30kg)',
            'BigParcelComfort' => 'Livraison Gros colis Confort (>30kg)',
            'Express' => 'Livraison Express (Express)',
            'Fast' => 'Livraison Rapide (Express)'
        );
    public static $predefined_carriers = array(
            'STD' => 'Standard',
            'TRK' => 'Tracked',
            'REG' => 'Registered',
            'RIM' => 'EnMagasin',
            'RCO' => 'RelaisColis',
            'SO1' => 'SoColissimo',
            'MAG' => 'Relay',
            'REL' => 'MondialRelay',
            'TNT' => 'TNT',
            'LV1' => 'BigParcelEco',
            'LV2' => 'BigParcelStandard',
            'LV3' => 'BigParcelComfort',
            'EXP' => 'Express',
            'FST' => 'Fast',
        );

    public static $carrier_for_clogistique = array(
        array('Code' => 'STD', 'Name' => 'Standard Colissimo Suivi'),
        array('Code' => 'COL', 'Name' => 'A domicile'),
        array('Code' => 'TNT', 'Name' => 'En Express'),
        array('Code' => 'ADX', 'Name' => 'Adrexo'),
        array('Code' => 'M30', 'Name' => 'Points retrait Cdiscount'),
        array('Code' => 'KIA', 'Name' => 'Kiala'),
        array('Code' => 'RCO', 'Name' => 'Relais Colis'),
        array('Code' => 'RCD', 'Name' => 'Relais Cdiscount'),
        array('Code' => 'SO1', 'Name' => 'So Colissimo'),
        array('Code' => 'SO2', 'Name' => 'So Colissimo'),
        array('Code' => 'REL', 'Name' => 'Mondial Relay'),
        array('Code' => 'TNX', 'Name' => 'TNT Express Relais'),
        array('Code' => 'ABC', 'Name' => 'Abricolis'),
        array('Code' => 'TNI', 'Name' => 'TNT International'),
        array('Code' => 'CHX', 'Name' => 'Relais Express Chronopost'),
        array('Code' => 'CSX', 'Name' => 'Consignes Express Chronopost'),
        array('Code' => 'CDS', 'Name' => 'Colissimo'),
        array('Code' => 'BD2', 'Name' => 'Maritime à Domicile'),
        array('Code' => 'BD3', 'Name' => 'Aérien à Domicile'),
        array('Code' => 'BE2', 'Name' => 'Maritime en Point Retrait'),
        array('Code' => 'BE3', 'Name' => 'Aérien en Point Retrait'),
        array('Code' => 'CHD', 'Name' => 'En Express'),
        array('Code' => 'DTL', 'Name' => 'Bluedistrib'),
        array('Code' => 'CHC', 'Name' => 'Sur rendez-vous'),
        array('Code' => 'RCX', 'Name' => 'Relais Colis Express'),
        array('Code' => 'TRP', 'Name' => 'Livraison devant chez vous'),
        array('Code' => 'LSP', 'Name' => 'Livraison chez vous'),
        array('Code' => 'RDO', 'Name' => 'Standard'),
        array('Code' => 'PRM', 'Name' => 'Livraison et installation chez vous'),
        array('Code' => 'EMP', 'Name' => 'Point Retrait'),
        array('Code' => 'VIR', 'Name' => 'Transporteur'),
        array('Code' => 'RDE', 'Name' => 'Eco'),
        array('Code' => 'EMX', 'Name' => 'Point Retrait Express'),
        array('Code' => 'BE1', 'Name' => 'Maritime en Point Retrait'),
        array('Code' => 'BD1', 'Name' => 'Maritime à Domicile'),
        array('Code' => 'LV4', 'Name' => 'Livraison installation et montage chez vous'),
        array('Code' => 'LDX', 'Name' => 'Livraison Express chez vous'),
        array('Code' => 'CHR', 'Name' => 'Chronopost'),
        array('Code' => 'PXP', 'Name' => 'Paris Express'),
        array('Code' => 'RIM', 'Name' => 'Retrait immédiat en magasin'),
        array('Code' => 'TNT', 'Name' => 'TNT Express'),
        array('Code' => 'LDR', 'Name' => 'Direct Relais'),
        array('Code' => 'CRE', 'Name' => 'Chrono Relais'),
        array('Code' => 'FDR', 'Name' => 'Livraison directe par le fournisseur'),
        array('Code' => 'MAG', 'Name' => 'Livraison en magasin'),
        array('Code' => 'DPS', 'Name' => 'DPS'),
        array('Code' => 'CHJ', 'Name' => 'CHJ'),
        array('Code' => 'DPD', 'Name' => 'DPD'),
        array('Code' => 'AGK', 'Name' => 'AGK'),
        array('Code' => 'COX', 'Name' => 'COX'),
    );

    public static $carriers_more_than_30kg = array(
        'LV1', 'LV2', 'LV3',
    );

    public static $seller_informations   = null;
    public static $global_informations   = null;
    public static $carriers_info        = null;
    public static $carriers_params      = null;
    public static $carriers_clogistique = null;
    public static $tracking_mapping = null;

    public static $instant_token = null;

    public static $models            = array();
    public static $attributes_groups = array();
    public static $attributes        = array();
    public static $features          = array();
    public static $features_values   = array();
    public static $validValuesByModelId = array();
    protected static $specificFields = array();
    protected static $profiles       = array();

    protected static $cdModel;

    public static $debug_mode = false;

    public $clogistique = null;

    protected $_cd_conditions = array(
        1 => 'LikeNew',
        2 => 'VeryGoodState',
        3 => 'GoodState',
        4 => 'AverageState',
        5 => 'Refurbished',
        6 => 'New'
    );
    private $_html = '';

    private $_vars = array(
        'username' => array(
            'name' => 'Utilisateur',
            'required' => true,
            'configuration' => 'CDISCOUNT_USERNAME'
        ),
        'password' => array(
            'name' => 'Mot de Passe',
            'required' => true,
            'configuration' => 'CDISCOUNT_PASSWORD'
        ),
        'preproduction' => array(
            'name' => 'Preproduction',
            'required' => false,
            'configuration' => 'CDISCOUNT_PREPRODUCTION'
        ),
        'debug' => array(
            'name' => 'Debug',
            'required' => false,
            'configuration' => 'CDISCOUNT_DEBUG'
        ),
        'preparation_time' => array(
            'name' => 'Temps de Préparation par Défaut',
            'required' => true,
            'configuration' => 'CDISCOUNT_PREPARATION_TIME',
            'default' => '3',
            'pattern' => 'isInt'
        ),
        'carriers_mapping' => array(
            'name' => 'Configuration des Transporteurs - 1',
            'configuration' => 'CDISCOUNT_CARRIERS_INFO',
            'required' => true
        ),
        'carriers_params' => array(
            'name' => 'Configuration des Transporteurs - 2',
            'configuration' => 'CDISCOUNT_CARRIERS_PARAMS',
            'required' => true
        ),
        'carriers_clogistique' => array(
            'name' => 'Configuration des Transporteurs - C Logistique',
            'configuration' => 'CDISCOUNT_CARRIERS_CLOGISTIQUE',
            'required' => false
        ),
        'tracking_mapping' => array(
            'name' => 'Mapping Transporteurs/Services de Livraison',
            'configuration' => 'CDISCOUNT_TRACKING_MAPPING',
            'required' => false
        ),
        'orderstate' => array(
            'name' => 'order state',
            'required' => true,
            'configuration' => 'CDISCOUNT_ORDERS_STATES'
        ),
        'import_type' => array(
            'name' => 'import_type',
            'required' => false,
            'default' => self::IMPORT_BY_SKU,
            'configuration' => 'CDISCOUNT_IMPORT_TYPE'
        ),
        'bulk_mode' => array(
            'name' => 'bulk_mode',
            'required' => false,
            'default' => true,
            'configuration' => 'CDISCOUNT_BULK_MODE'
        ),
        'extra_fees' => array(
            'name' => 'extra_fees',
            'required' => false,
            'default' => true,
            'configuration' => 'CDISCOUNT_EXTRA_FEES'
        ),
        'allow_oos' => array(
            'name' => 'Allow Out of Stock',
            'default' => false,
            'required' => false,
            'configuration' => 'CDISCOUNT_ALLOW_OOS'
        ),
        'taxes' => array(
            'name' => 'Use Taxes',
            'required' => false,
            'default' => true,
            'configuration' => 'CDISCOUNT_USE_TAXES'
        ),
        'smart_rounding' => array(
            'name' => 'Smart Rounding',
            'required' => false,
            'default' => true,
            'configuration' => 'CDISCOUNT_SMART_ROUNDING'
        ),
        'clogistique' => array(
            'name' => 'C Logistique',
            'required' => false,
            'default' => false,
            'configuration' => 'CDISCOUNT_CLOGISTIQUE'
        ),
        'clogistique_destock' => array(
            'name' => 'C Logistique - Destock',
            'required' => false,
            'default' => true,
            'configuration' => 'CDISCOUNT_CLOGISTIQUE_DESTOCK'
        ),
        'disable_proxy' => array(
            'name' => 'Disabling Proxy',
            'required' => false,
            'default' => false,
            'configuration' => 'CDISCOUNT_DISABLE_PROXY'
        ),
        'carriers_modules' => array(
            'name' => 'Carriers/Modules',
            'required' => false,
            'default' => false,
            'configuration' => 'CDISCOUNT_CARRIERS_MODULES'
        ),
        'specials' => array(
            'name' => 'Use Specials',
            'default' => true,
            'required' => false,
            'default' => true,
            'configuration' => 'CDISCOUNT_USE_SPECIALS'
        ),
        'formula_on_specials' => array(
            'name' => 'formula on Specials',
            'default' => true,
            'required' => false,
            'default' => true,
            'configuration' => 'CDISCOUNT_FORMULA_ON_SPECIALS'
        ),
        'on_sale_period' => array(
            'name' => 'On Sale Period',
            'default' => false,
            'required' => false,
            'default' => true,
            'configuration' => 'CDISCOUNT_ON_SALE_PERIOD'
        ),
        'title_format' => array(
            'name' => 'Title format',
            'default' => self::TITLE_NAME_ATTRIBUTES_WITH_LABEL,
            'required' => true,
            'warn' => false,
            'configuration' => 'CDISCOUNT_TITLE_FORMAT',
        ),
        'ean_policy' => array(
            'name' => 'EAN Policy',
            'default' => self::EAN_POLICY_NORMAL,
            'required' => true,
            'warn' => false,
            'configuration' => 'CDISCOUNT_EAN_POLICY'
        ),
        'align_active' => array(
            'name' => 'Align/Repricing Auto',
            'default' => false,
            'required' => false,
            'configuration' => 'CDISCOUNT_ALIGNMENT_ACTIVE'
        ),
        'expert_mode' => array(
            'name' => 'Expert Mode',
            'default' => false,
            'required' => false,
            'configuration' => 'CDISCOUNT_EXPERT_MODE'
        ),
        'warehouse' => array(
            'name' => 'Warehouse',
            'required' => false,
            'configuration' => 'CDISCOUNT_WAREHOUSE'
        ),
        'image_type' => array(
            'name' => 'Image Type',
            'required' => false,
            'default' => 'thickbox_default',
            'configuration' => 'CDISCOUNT_IMAGE_TYPE'
        ),
        'email_logs' => array(
            'name' => 'Logs by Email',
            'required' => false,
            'default' => false,
            'configuration' => 'CDISCOUNT_EMAIL_LOGS'
        ),
        'fashion_color' => array(
            'name' => 'Fashion - Color Field',
            'required' => false,
            'configuration' => 'CDISCOUNT_FASHION_COLOR'
        ),
        'fashion_size' => array(
            'name' => 'Fashion - Size Field',
            'required' => false,
            'configuration' => 'CDISCOUNT_FASHION_SIZE'
        ),
        'comments' => array(
            'name' => 'Fashion - Size Field',
            'required' => false,
            'configuration' => 'CDISCOUNT_DEFAULT_COMMENT'
        ),
        'individual' => array(
            'name' => 'Individual Account',
            'default' => true,
            'required' => false,
            'configuration' => 'CDISCOUNT_INDIVIDUAL_ACCOUNT'
        ),
        'domain' => array(
            'name' => 'Domain Name',
            'required' => false,
            'configuration' => 'CDISCOUNT_INDIVIDUAL_DOMAIN'
        ),
        'description_field' => array(
            'name' => 'Description Field',
            'required' => false,
            'configuration' => 'CDISCOUNT_DESCRIPTION_FIELD',
            'default' => self::FIELD_DESCRIPTION_SHORT
        ),
        'marketing_description' => array(
            'name' => 'Marketing Description',
            'required' => false,
            'configuration' => 'CDISCOUNT_MARKETING_DESCRIPTION',
            'default' => false
        ),
        'long_description_field' => array(
            'name' => 'Long Description Field',
            'required' => false,
            'configuration' => 'CDISCOUNT_LONG_DESCRIPTION_FIELD',
            'default' => self::FIELD_DESCRIPTION_LONG
        ),
        'multitenant' => array(
            'name' => 'Multitenant',
            'required' => false,
            'configuration' => 'CDISCOUNT_MULTITENANT'
        ),
        'mail_invoice' => array(
            'name' => 'Invoice Messaging',
            'required' => false,
            'configuration' => 'CDISCOUNT_MESSAGING'
        ),
        'price_filter' => array(
            'name' => 'Price Filter',
            'required' => false,
            'configuration' => 'CDISCOUNT_PRICE_FILTER'
        ),
        'stock_mininum' => array(
            'name' => 'Stock Filter',
            'required' => false,
            'configuration' => 'CDISCOUNT_STOCK_FILTER'
        ),
        'id_group' => array(
            'name' => 'Customer Group',
            'required' => false,
            'configuration' => 'CDISCOUNT_CUSTOMER_GROUP'
        ),
        'employee' => array(
            'name' => 'Employee',
            'required' => false,
            'configuration' => 'CDISCOUNT_EMPLOYEE'
        ),
        'multichannel' => array(
            'name' => 'Multichannel',
            'required' => false,
            'configuration' => 'CDISCOUNT_MULTICHANNEL'
        )
    );

    private $_config = array(
        'CDISCOUNT_TOKEN_VALIDITY' => null,
        'CDISCOUNT_DECRIPTION_FIELD' => null,
        'CDISCOUNT_LAST_IMPORT' => null,
        'CDISCOUNT_CLOGISTIQUE_RESTOCK' => null,
        'CDISCOUNT_CURRENT_VERSION' => null,
        'CDISCOUNT_CONTEXT_DATA' => null,
        'CDISCOUNT_BATCH_UPDATE' => null,
        'CDISCOUNT_BATCH_CREATE' => null,
        'CDISCOUNT_LAST_EXPORT' => null,
        'CDISCOUNT_LAST_UPDATE' => null,
        'CDISCOUNT_LAST_UPDATE_CRON' => null,
        'CDISCOUNT_TOKEN' => null,
        'CDISCOUNT_PS_TOKEN' => null,
        'CDISCOUNT_ATTRIBUTES_MAPPING_L' => null,
        'CDISCOUNT_ATTRIBUTES_MAPPING_R' => null,
        'CDISCOUNT_PRICE_FILTER' => null,
        'CDISCOUNT_STOCK_FILTER' => null,
        'CDISCOUNT_FILTER_MANUFACTURERS' => null,
        'CDISCOUNT_FILTER_SUPPLIERS' => null,
        'CDISCOUNT_PROFILES' => null,
        'CDISCOUNT_MODELS' => null,
        'CDISCOUNT_PROFILES_CATEGORIES' => null,
        'CDISCOUNT_CUSTOMER_ID' => null,
        'CDISCOUNT_CONDITION_MAP' => null,
        'CDISCOUNT_ADDRESS_MAP' => null,
        'CDISCOUNT_CARRIERS_CLOGISTIQUE' => null,
        'CDISCOUNT_DEV_MODE' => false,
        'CDISCOUNT_INSTANT_TOKEN' => null
    );
    private $_conditions = array(
        1 => 'Comme neuf',
        2 => 'Tr&egrave;s bon &eacute;tat',
        3 => 'Bon &eacute;tat',
        4 => 'Etat correct',
        5 => 'Neuf Reconditionn&eacute;',
        6 => 'Neuf'
    );
    private $categories = null;

    public $debug    = false;
    public $dev_mode = false;

    public $ps17x = false;
    public $ps16x = false;
    public $ps15x = false;

    /** @var CDiscountAdminConfigure */
    public $adminConfigurationManager;
    /** @var CDiscountDebugDetails */
    public $debugDetails;

    /** @var CDiscountDBManager */
    public $dbManager;

    /** @var CDiscountHookManager */
    public $hookManager;

    /** @var CDiscountPostProcessManager */
    public $postProcessManager;

    protected $images;
    protected $js;
    protected $id_lang;
    protected $path;
    protected $url;

    public function __construct()
    {
        $this->name = 'cdiscount';
        $this->author = 'Common-Services';
        $this->author_address = '0x96116FE33A6268AE9E878Dbc609A02BdCcc285E0';
        $this->tab = 'market_place';
        $this->version = '4.4.35';

        $this->module_key = '9e24fa781d1c0b40e62152c4856d16a5';
        $this->need_instance = 1;
        parent::__construct();
        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('CDiscount');
        $this->description = $this->l('Manages products and orders on CDiscount MarketPlace');

        $this->bootstrap = true;

        $this->url = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/';
        $this->path = _PS_MODULE_DIR_.$this->name.DS;
        $this->path_mail = $this->path.'mail/';
        $this->path_pdf = $this->path.'pdf/';

        $this->images = $this->url.'views/img/';
        $this->js = $this->url.'views/js/';

        /* Backward compatibility */
        if (_PS_VERSION_ < '1.5') {
            require(_PS_MODULE_DIR_.$this->name.'/backward_compatibility/backward.php');
        }

        $this->initContext();

        if ((defined('PS_ADMIN_DIR') || defined('_PS_ADMIN_DIR_'))) {
            require_once(_PS_MODULE_DIR_.'cdiscount/classes/cdiscount.tools.class.php');
        }

        $this->cdIncludes();
    }

    protected function cdIncludes()
    {
        require_once(dirname(__FILE__) . '/includes/cdiscount.admin_configure.php');
        require_once(dirname(__FILE__) . '/includes/cdiscount.debug.php');
        require_once(dirname(__FILE__) . '/includes/cdiscount.db.manager.php');
        require_once(dirname(__FILE__) . '/includes/cdiscount.hook.manager.php');
        require_once(dirname(__FILE__) . '/includes/cdiscount.post_process.manager.php');
        $this->adminConfigurationManager = new CDiscountAdminConfigure($this, $this->context);
        $this->debugDetails = new CDiscountDebugDetails();
        $this->dbManager = new CDiscountDBManager($this, $this->context);
        $this->hookManager = new CDiscountHookManager($this, $this->context);
        $this->postProcessManager = new CDiscountPostProcessManager($this, $this->context);
    }

    private function initContext()
    {
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->ps17x = true;
        }
        if (version_compare(_PS_VERSION_, '1.6', '>=')) {
            $this->ps16x = true;
        }
        self::$debug_mode = (bool)(Configuration::get(self::KEY.'_DEBUG') || Tools::getValue('debug', false));

        $this->context = Context::getContext();
        $id_lang = (int)Tools::getValue('id_lang');//For Ajax Scripts
        $this->id_lang = $id_lang ? $id_lang : (int)$this->context->language->id;
    }

    public function getContent()
    {
        Configuration::UpdateValue(self::KEY.'_INSTANT_TOKEN', self::$instant_token = md5(_PS_ROOT_DIR_._PS_VERSION_.(isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time())), false, 0, 0);

        if (Configuration::get(self::KEY.'_DEBUG')) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
            $this->debug = true;
            self::$debug_mode = true;
        } else {
            $this->debug = false;
        }

        if (version_compare(_PS_VERSION_, '1.5', '>=') && Shop::isFeatureActive() && in_array($this->context->shop->getContext(), array(Shop::CONTEXT_GROUP, Shop::CONTEXT_ALL))) {
            ConfigureMessage::warning($this->l('Please select a target shop prior to configure your module'));
        }

        require_once(dirname(__FILE__).'/classes/'.self::MODULE.'.tools.class.php');
        require_once(dirname(__FILE__).'/classes/'.self::MODULE.'.model.class.php');
        require_once(dirname(__FILE__).'/classes/'.self::MODULE.'.specificfield.class.php');
        require_once(dirname(__FILE__).'/classes/'.self::MODULE.'.categories.class.php');
        require_once(dirname(__FILE__).'/classes/'.self::MODULE.'.support.class.php');
        require_once(dirname(__FILE__).'/classes/'.self::MODULE.'.config.class.php');
        require_once(dirname(__FILE__).'/classes/'.self::MODULE.'.context.class.php');

        require_once(dirname(__FILE__).'/classes/shared/configure_tab.class.php');

        if (Configuration::get(self::KEY.'_MININUM')) {
            $this->run_min_configuration = true;
        } else {
            $this->run_min_configuration = false;
            // Only load universe
            CDiscountCategories::loadUniverses();
        }

        $xml_dir = dirname(__FILE__).DS.self::XML_DIRECTORY;
        @chmod($xml_dir, self::PERMISSIONS_DIRECTORY);

        $this->versionCheck();

        if (Tools::isSubmit('validateForm') && !ConfigureMessage::hasErrorMessage()) {
            $this->_postProcess();
        }

        // Migrate data before show configuration
        $this->dbManager->migrateData(false);

        $this->_displayForm();

        return $this->_html;
    }

    public function versionCheck()
    {
        $currentVersion = Configuration::get(self::KEY.'_CURRENT_VERSION');

        if ($currentVersion == null) {
            $this->_html
                .= '
                    <div '.($this->ps16x ? 'class="alert alert-info"' : ' class="hint"').' style="display:block;font-size:1.2em;position:relative;">
                        <ul>
                            <li>'.$this->l('Be effective, do not waste your time :').'</li>
                            <li>'.$this->l('For beginning, Supposing you have basis knowledge on it, please use basis functions').'</li>
                            <li>'.$this->l('Do not try to hard tune the module. Almost all parameters are correctly configured by default.').'</li>
                        </ul>
                    </div>
                    <br />';
        }
    }

    private function _postProcess()
    {
        require_once(dirname(__FILE__).'/classes/'.self::MODULE.'.context.class.php');

        CDiscountContext::save($this->context);

        if (!$this->addMarketPlaceTables()) {
            ConfigureMessage::error($this->l('Unable to install: _addMarketPlaceTables()'));
            $pass = false;
        }

        if (!($id_customer = $this->createCustomer())) {
            ConfigureMessage::error($this->l('Unable to install: _createCustomer'));
            $pass = false;
        }

        require_once(_PS_MODULE_DIR_.self::MODULE.'/classes/'.self::MODULE.'.address.class.php');

        CDiscountAddress::createShippingLocations($id_customer);

        // Install Hooks
        //
        $this->hookSetup(self::ADD);

        // Update Tabs
        //
        $this->tabSetup(self::UPDATE);


        foreach ($this->_vars as $key => $value) {
            $cfg = Tools::getValue($key);

            $check = !isset($value['warn']) || (isset($value['warn']) && $value['warn'] == true) ? true : false;

            if ($check && $value['required'] && $cfg == null) {
                //
                ConfigureMessage::error($this->l(CommonTools::ucwords($value['name'])).' '.$this->l(' is required'));
            } else {
                if ($check && !empty($value['pattern']) && isset($value['pattern'])) {
                    $function = $value['pattern'];
                    if (!Validate::$function($cfg)) {
                        ConfigureMessage::error($this->l(CommonTools::ucwords($value['name'])).' '.$this->l(' is not valid'));
                    } else {
                        if (!is_array($cfg)) {
                            Configuration::updateValue($value['configuration'], $cfg);
                        } else {
                            Configuration::updateValue($value['configuration'], self::encode(serialize($cfg)));
                        }
                    }
                } else {
                    if (!is_array($cfg)) {
                        Configuration::updateValue($value['configuration'], $cfg);
                    } else {
                        Configuration::updateValue($value['configuration'], self::encode(serialize($cfg)));
                    }
                }
            }
        }

        if (!Tools::getValue('ean_policy')) {
            ConfigureMessage::error($this->l('Please choose an EAN policy in Parameters tab'));
        }

        // Mappings (previous named Fashion)
        //
        $fashion = Tools::getValue('fashion', array(
            'prestashop' => array(),
            'cdiscount' => array()
        ));

        // Remove empty entries
        //
        foreach (array_keys($fashion['cdiscount']) as $key1) {
            if (is_array($fashion['cdiscount'][$key1])) {
                foreach ($fashion['cdiscount'][$key1] as $key2 => $item) {
                    if (empty($item)) {
                        unset($fashion['cdiscount'][$key1][$key2]);
                        unset($fashion['prestashop'][$key1][$key2]);
                    }
                    if (!count($fashion['cdiscount'][$key1]) || !count($fashion['prestashop'][$key1])) {
                        unset($fashion['cdiscount'][$key1]);
                        unset($fashion['prestashop'][$key1]);
                    }
                }
            }
        }
        Configuration::updateValue(self::KEY.'_ATTRIBUTES_MAPPING_L', self::encode(serialize($fashion['prestashop'])));
        Configuration::updateValue(self::KEY.'_ATTRIBUTES_MAPPING_R', self::encode(serialize($fashion['cdiscount'])));

        Configuration::updateValue(self::KEY.'_FILTER_MANUFACTURERS', self::encode(serialize(Tools::getValue('excluded-manufacturers'))));
        Configuration::updateValue(self::KEY.'_FILTER_SUPPLIERS', self::encode(serialize(Tools::getValue('selected-suppliers'))));

        $profiles = Tools::getValue('profiles');

        if (isset($profiles['price_rule']) && is_array($profiles['price_rule']) && count($profiles['price_rule'])) {
            $price_rules = $profiles['price_rule'];

            foreach ($price_rules as $rule) {
                $check_price_rules = $this->checkPriceRules($rule);
                if (Tools::strlen($check_price_rules)) {
                    ConfigureMessage::error($check_price_rules);
                }
            }
        }

        // Models are not need to load before process
        $features_mapping = Tools::getValue('features_mapping', array());
        if (is_array($features_mapping) && count($features_mapping)) {
            $features_mapping = CDiscountTools::arrayFilterRecursive($features_mapping);
        }

        $sizes_mapping = Tools::getValue('sizes_mapping', array());
        if (is_array($sizes_mapping) && count($sizes_mapping)) {
            $sizes_mapping = CDiscountTools::arrayFilterRecursive($sizes_mapping);
        }

        // Model & specific fields
        $this->postProcessManager->saveConfiguration();
        CDiscountConfiguration::updateValue('features_mapping', $features_mapping);
        CDiscountConfiguration::updateValue('sizes_mapping', $sizes_mapping);

        if (CDiscountCategories::isLoaded()) {
            // New profile config key
            CDiscountConfiguration::updateValue(CDiscountConstant::CONFIG_PROFILES, $profiles);
            CDiscountConfiguration::updateValue('categories', Tools::getValue('category'));
            CDiscountConfiguration::updateValue('profiles_categories', Tools::getValue('profile2category'));
        }

        $condition_map = Tools::getValue('condition_map');

        if (!isset($condition_map) || !$condition_map[6]) {
            ConfigureMessage::error($this->l('The condition map must be filled'));
        } else {
            Configuration::updateValue(self::KEY.'_CONDITION_MAP', self::encode(serialize($condition_map)));
        }

        Configuration::updateValue(self::KEY.'_DEV_MODE', (bool)Tools::getValue('dev_mode_value', false));

        //
        // New EAN matching file upload (for EAN exemption
        //
        if (isset($_FILES['eanmatchingcsv'])) {
            require_once(dirname(__FILE__).'/classes/vobase.class.php');
            require_once(dirname(__FILE__).'/classes/'.self::MODULE.'.eancsvmanager.class.php');

            if (isset($_FILES['eanmatchingcsv']) && isset($_FILES['eanmatchingcsv']['tmp_name']) && !empty($_FILES['eanmatchingcsv']['tmp_name'])) {
                $eancsvmanager = new CDiscountEanCsvManager($_FILES['eanmatchingcsv']['tmp_name']);
                $result = $eancsvmanager->getTotalData();

                if ($result) {
                    $i = 0;
                    $count = 0;
                    foreach ($result as $eanentry) {
                        if ($eanentry->id_product && $eanentry->id_product_attribute) {
                            $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` SET `ean13` = "'.pSQL($eanentry->ean).'" WHERE NOT `ean13` > ""';
                            $sql .= ' AND `id_product`='.(int)$eanentry->id_product.' AND `id_product_attribute`='.(int)$eanentry->id_product_attribute;
                            if (Db::getInstance()->Execute($sql)) {
                                $count++;
                            }
                        } elseif ($eanentry->id_product) {
                            $sql = 'UPDATE `'._DB_PREFIX_.'product` SET `ean13` = "'.pSQL($eanentry->ean).'" WHERE NOT `ean13` > ""';
                            $sql .= ' AND `id_product`='.(int)$eanentry->id_product;
                            if (Db::getInstance()->Execute($sql)) {
                                $count++;
                            }
                        }
                    }
                    $this->_html .= $this->displayConfirmation(sprintf('%d %s', $count, $this->l('products has been updated')));
                } else {
                    ConfigureMessage::error(sprintf('%s/%d: %s', basename(__FILE__), __LINE__, $this->l('Nothing to match in this file')));
                }
            }
        }

        if (!ConfigureMessage::hasErrorMessage()) {
            ConfigureMessage::success($this->l('Configuration has been saved'));
        }

        if (Tools::getValue('username')) {
            Configuration::updateValue(self::KEY.'_CURRENT_VERSION', $this->version);
        }

        if (Configuration::get(self::KEY.'_DEBUG')) {
            ConfigureMessage::info(sprintf('Memory Peak: %.02f MB - Post Count: %s', memory_get_peak_usage() / 1024 / 1024, count($_POST, COUNT_RECURSIVE)));
        }
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
            $error .= sprintf('%s => %s<br>', $this->l('Price rule incomplete for'), '-', $this->l('Missing range element'));

            return ($error);
        }

        if (($type == 'percent' && !reset($rule['percent'])) || ($type == 'value' && !reset($rule['value']))) {
            $error .= sprintf('%s %s => %s<br>', $this->l('Price rule incomplete for'), '-', $this->l('Missing value'));

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

    private function _displayForm()
    {
        $this->dev_mode = Configuration::get(self::KEY.'_DEV_MODE');
        $this->clogistique = (bool)Configuration::get(self::KEY.'_CLOGISTIQUE');

        self::$seller_informations = CDiscountConfig::getSellerInformation($this->debug);
        self::$global_informations = CDiscountConfig::GetGlobalConfiguration($this->debug);
        self::$carriers_info = unserialize(self::decode(Configuration::get(self::KEY.'_CARRIERS_INFO')));
        self::$carriers_params = unserialize(self::decode(Configuration::get(self::KEY.'_CARRIERS_PARAMS')));
        self::$tracking_mapping = unserialize(self::decode(Configuration::get(self::KEY.'_TRACKING_MAPPING')));
        if ($this->clogistique) {
            self::$carriers_clogistique = unserialize(self::decode(Configuration::get(self::KEY.'_CARRIERS_CLOGISTIQUE')));
        }
        $this->loadProfiles();
        $this->loadAttributes();
        $support = new CDiscountSupport($this->id_lang);

        $cd_has_multichannel = isset(self::$seller_informations['Multichannel']['Belgium'])
            && (bool)self::$seller_informations['Multichannel']['Belgium'];

        $view_params = array(
            'PS16_class' => $this->ps16x ? 'bootstrap' : '',
            'request_uri' => Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']),
            'images_url' => $this->images,
            'js_url' => $this->js,
            'module_url' => $this->url,
            'instant_token' => self::$instant_token,
            'module_path' => dirname(__FILE__),
            'id_lang' => $this->id_lang,
            'module_description' => $this->description,
            'version' => $this->version,
            'context_key' => CDiscountContext::getKey($this->context->shop),
            'ps_version' => _PS_VERSION_,
            'cd_version' => $this->version,
            'support_language' => $support->lang,

            'cd_username' => trim(Configuration::get(self::KEY.'_USERNAME')),
            'cd_password' => trim(Configuration::get(self::KEY.'_PASSWORD')),
            'cd_has_multichannel' => $cd_has_multichannel,
            'cd_multichannel' => trim(Configuration::get(self::KEY.'_MULTICHANNEL')),
            'cd_expert_mode' => (bool)Configuration::get(self::KEY.'_EXPERT_MODE'),

            'cd_imports' => $this->tabImports(),
            'cd_models' => $this->adminConfigurationManager->tabModels(),
            'cd_marketplace' => $this->tabMarketplace(),
            'cd_profiles' => $this->adminConfigurationManager->tabProfiles(),
            'cd_categories' => $this->tabCategories(),
            'cd_informations' => $this->tabInformations(),
            'cd_mappings' => $this->tabMappings(),
            'cd_transport' => $this->tabTransport(),
            'cd_orders' => $this->tabOrders(),
            'cd_settings' => $this->tabSettings(),
            'cd_filters' => $this->tabFilters(),
            'cd_messaging' => $this->tabMessaging(),
            'cd_cron' => $this->tabCron(),

            'cd_detailed_debug' => $this->debugDetails->getAll(),
        );

        //Debug config
        if ($this->debug) {
            $view_params['debug'] = 'TRUE';
            $view_params['cd_memory_peak_usage'] = memory_get_peak_usage() / 1024 / 1024;
        }

        if (($preproduction = Configuration::get(self::KEY.'_PREPRODUCTION'))) {
            $view_params['cd_preproduction_check'] = $preproduction ? true : false;
        } else {
            $view_params['cd_preproduction_check'] = false;
        }

        $view_params['cd_debug'] = Configuration::get(self::KEY.'_DEBUG') ? true : false;
        $view_params['cd_debug_style'] = $this->debug ? ' style="color:red" ' : '';

        $view_params['tools_url'] = $this->url.'functions/tools.php?instant_token='.self::$instant_token;
        $view_params['selected_tab'] = $this->selectedTab();
        $view_params['ps16x'] = $this->ps16x;

        $view_params['alert_class'] = array(
            'danger' => $this->ps16x ? 'alert alert-danger' : 'error',
            'warning' => $this->ps16x ? 'alert alert-warning' : 'warn',
            'success' => $this->ps16x ? 'alert alert-success' : 'conf',
            'info' => $this->ps16x ? 'alert alert-info' : 'info',
        );

        $view_params['dev_mode'] = isset($_SERVER['DropBox']) && $_SERVER['DropBox'];
        $view_params['dev_mode_value'] = $this->dev_mode;

        $tabList = array();
        $tabList[] = array(
            'id' => self::MODULE,
            'img' => self::MODULE,
            'name' => self::TITLE,
            'selected' => $view_params['selected_tab'] == self::MODULE
        );
        $tabList[] = array(
            'id' => 'informations',
            'img' => 'information',
            'name' => 'Informations',
            'selected' => $view_params['selected_tab'] == 'informations'
        );
        $tabList[] = array(
            'id' => 'credentials',
            'img' => 'key',
            'name' => $this->l('Credentials'),
            'selected' => $view_params['selected_tab'] == 'credentials'
        );
        $tabList[] = array(
            'id' => 'imports',
            'img' => 'download',
            'name' => $this->l('Imports'),
            'selected' => $view_params['selected_tab'] == 'imports'
        );
        $tabList[] = array(
            'id' => 'models',
            'img' => 'shapes',
            'name' => $this->l('Models'),
            'selected' => $view_params['selected_tab'] == 'models'
        );
        $tabList[] = array(
            'id' => 'profiles',
            'img' => 'profiles',
            'name' => $this->l('Profiles'),
            'selected' => $view_params['selected_tab'] == 'profiles'
        );
        $tabList[] = array(
            'id' => 'categories',
            'img' => 'categories',
            'name' => $this->l('Categories'),
            'selected' => $view_params['selected_tab'] == 'categories'
        );
        $tabList[] = array(
            'id' => 'mapping',
            'img' => 'mapping',
            'name' => $this->l('Mappings'),
            'selected' => $view_params['selected_tab'] == 'mapping'
        );
        $tabList[] = array(
            'id' => 'transport',
            'img' => 'lorry',
            'name' => $this->l('Transport'),
            'selected' => $view_params['selected_tab'] == 'transport'
        );
        $tabList[] = array(
            'id' => 'orders',
            'img' => 'calculator',
            'name' => $this->l('Orders'),
            'selected' => $view_params['selected_tab'] == 'orders'
        );
        $tabList[] = array(
            'id' => 'settings',
            'img' => 'cog_edit',
            'name' => $this->l('Settings'),
            'selected' => $view_params['selected_tab'] == 'settings'
        );
        $tabList[] = array(
            'id' => 'filters',
            'img' => 'filter',
            'name' => $this->l('Filters'),
            'selected' => $view_params['selected_tab'] == 'filters'
        );

        // CDiscount returns more than one entry
        if (isset(self::$seller_informations['Multichannel']) && self::$seller_informations['Multichannel']['Multitenant']) {
            $multitenants = self::multitenantGetList();

            if (is_array($multitenants) && count($multitenants)) {
                $view_params['cd_multitenant'] = $this->tabMultitenant();
                $tabList[] = array(
                'id' => 'multitenant',
                'img' => 'three_tags',
                'name' => $this->l('Multitenant'),
                'selected' => false
                );
            };
        }
        $tabList[] = array('id' => 'messaging', 'img' => 'mail', 'name' => $this->l('Messaging'), 'selected' => false);
        $tabList[] = array('id' => 'cron', 'img' => 'clock', 'name' => $this->l('Cron'), 'selected' => false);

        $this->context->smarty->assign($view_params);

        $this->_html .= ConfigureMessage::display() .
            $this->context->smarty->fetch($this->path.'views/templates/admin/configure/header.tpl') .
            ConfigureTab::generateTabs($tabList) .
            $this->context->smarty->fetch($this->path.'views/templates/admin/configure/'.self::MODULE.'.tpl');
    }

    public function tabImports()
    {
        $view_params = array();
        $view_params['category_max'] = Cdiscount::IMPORT_MAX_CATEGORIES;
        $view_params['ean_exemption'] = Configuration::get(self::KEY.'_EAN_EXEMPTION');
        $universesTimestamp = CDiscountCategories::getUniversesTimestamp();
        $universesLoad = false;
        $categoriesTimestamp = CDiscountCategories::getAllowedCategoriesTimestamp();
        $categoriesLoad = false;
        $currentToken = CDiscountTools::currentToken();


        // Models
        //
        if ($this->run_min_configuration) {
            $universesTimestamp = time();
        }

        // Universes (main categories)
        //
        if ($this->run_min_configuration) {
            $view_params['universesClass'] = $this->ps16x ? 'alert alert-warning' : 'warn';
            $view_params['universesInfo'] = $this->l('Running minimum configuration, models and profiles ignored. Export of products won\'t be operationnal.');
            $universesTimestamp = time();
        } elseif (!$this->dev_mode && $currentToken == false) {
            $view_params['universesClass'] = $this->ps16x ? 'alert alert-warning' : 'warn';
            $view_params['universesInfo'] = $this->l('Please configure your module and check connectivity to allow datas to be downloaded');
            $universesTimestamp = time();
        } elseif (!$this->dev_mode && $universesTimestamp === false) {
            $view_params['universesClass'] = $this->ps16x ? 'alert alert-danger' : 'error';
            $view_params['universesInfo'] = $this->l('The file is wrong or missing, trying to download...');
            $universesLoad = true;
        } else {
            $view_params['universesClass'] = $this->ps16x ? 'alert alert-info' : 'info';
            $view_params['universesInfo'] = $this->l('File last updated on').' '.CommonTools::displayDate(date('Y-m-d H:i:s', $universesTimestamp), $this->id_lang, true);
        }

        // Renew after 15 days and some minutes
        if (!$this->dev_mode && time() - $universesTimestamp > (15 * 86400 + rand(0, 3600)) || $universesLoad) {
            $view_params['universesRenew'] = true;
            $view_params['universesRenewInfo'] = $this->l('The file is missing or expired. It will be reloaded automatically from now. Please wait a while.');
        } else {
            $view_params['universesRenew'] = false;
            $view_params['universesRenewInfo'] = $this->l('The file will be reloaded automatically from now. Please wait a while.');
        }

        // Categories (main categories)
        //
        if ($this->run_min_configuration) {
            $view_params['categoriesClass'] = $this->ps16x ? 'alert alert-warning' : 'warn';
            $view_params['categoriesInfo'] = $this->l('Running minimum configuration, models and profiles ignored. Export of products won\'t be operationnal.');
            $categoriesTimestamp = time();
            $categoriesLoad = null;
        } elseif (!$this->dev_mode && $currentToken == false) {
            $view_params['categoriesClass'] = $this->ps16x ? 'alert alert-warning' : 'warn';
            $view_params['categoriesInfo'] = $this->l('Please configure your module and check connectivity to allow datas to be downloaded');
            $categoriesTimestamp = time();
            $categoriesLoad = null;
        } elseif (!$this->dev_mode && ($countAllowed = CDiscountCategories::countAllowed()) !== false && $countAllowed <= 0) {
            $view_params['categoriesClass'] = $this->ps16x ? 'alert alert-danger' : 'error';
            $view_params['categoriesInfo'] = $this->l('You are not allowed to spread offers in any category. Please contact your seller support and notify that no category has the AllowOfferIntegration flag set to on.');
            $categoriesLoad = null;
        } elseif (!$this->dev_mode && $categoriesTimestamp === false) {
            $view_params['categoriesClass'] = $this->ps16x ? 'alert alert-danger' : 'error';
            $view_params['categoriesInfo'] = $this->l('The file is wrong or missing, trying to download...');
            $categoriesLoad = true;
        } else {
            $view_params['categoriesClass'] = $this->ps16x ? 'alert alert-info' : 'info';
            $view_params['categoriesInfo'] = $this->l('File last updated on').' '.CommonTools::displayDate(date('Y-m-d H:i:s', $categoriesTimestamp), $this->id_lang, true);
        }

        $view_params['categoriesRenew'] = false;
        $view_params['categoriesRenewInfo'] = null;

        if (!$this->dev_mode && $categoriesLoad !== null) {
            // Renew after 15 days and some minutes
            if (time() - $categoriesTimestamp > (15 * 86400 + rand(0, 3600)) || $categoriesLoad) {
                $view_params['categoriesRenew'] = true;
                $view_params['categoriesRenewInfo'] = $this->l('The file is missing or expired. It will be reloaded automatically from now. Please wait a while.');
            } else {
                $view_params['categoriesRenew'] = false;
                $view_params['categoriesRenewInfo'] = $this->l('The file will be reloaded automatically from now. Please wait a while.');
            }
        }
        //Debug config
        if (Configuration::get(self::KEY.'_DEBUG')) {
            $view_params['debug'] = 1;
        } else {
            $view_params['debug'] = 0;
        }

        return $view_params;
    }

    public function loadAttributes()
    {
        self::$attributes_groups = array();
        self::$attributes = array();


        $attributes_groups = AttributeGroup::getAttributesGroups((int)($this->context->cookie->id_lang));

        if (is_array($attributes_groups) && count($attributes_groups)) {
            foreach ($attributes_groups as $attribute_group) {
                self::$attributes_groups[$attribute_group['id_attribute_group']] = $attribute_group;
            }
        } // reindex

        $attributes = Attribute::getAttributes((int)($this->context->cookie->id_lang), true);

        if (is_array($attributes) && count($attributes)) {
            foreach ($attributes as $attribute) {
                self::$attributes[$attribute['id_attribute_group']][$attribute['id_attribute']] = $attribute['name'];
            }
        } // reindex

        $features = Feature::getFeatures($this->id_lang);
        $featureslist = array();

        if (is_array($features) && count($features)) {
            foreach ($features as $k => $feature) {
                $featureslist[$feature['id_feature']] = $feature;
            }
        }  // reindex

        self::$features = $featureslist;

        $features_values = array();

        if (is_array($featureslist) && count($featureslist)) {
            foreach (array_keys($featureslist) as $id_feature) {
                $feature_values = FeatureValue::getFeatureValuesWithLang((int)($this->context->cookie->id_lang), $id_feature);
                $featute_values_tmp = array();

                if (is_array($feature_values) && count($feature_values)) {
                    // Reindex
                    foreach ($feature_values as $fv) {
                        $id_feature_value = $fv['id_feature_value'];
                        $featute_values_tmp[$id_feature_value] = $fv;
                    }
                    if (count($featute_values_tmp)) {
                        $features_values[$id_feature] = $featute_values_tmp;
                    }
                }
            }
        }

        self::$features_values = $features_values;
    }

    // Move to separate file

    public function tabMarketplace()
    {
        $view_params = array();
        $view_params['images'] = $this->images;
        $view_params['url'] = $this->url;

        return $view_params;
    }

    public function tabInformations()
    {
        require_once(_PS_MODULE_DIR_.'cdiscount/common/configuration_check.class.php');

        $expert_mode = (bool)Configuration::get(self::KEY.'_EXPERT_MODE');

        if ((bool)Configuration::get('PS_FORCE_SMARTY_2') == true) {
            die(sprintf('<div class="error">%s</span>', Tools::displayError('This module is not compatible with Smarty v2. Please switch to Smarty v3 in Preferences Tab.')));
        }

        require_once(dirname(__FILE__).'/classes/'.self::MODULE.'.tools.class.php');

        $lang = Language::getIsoById($this->id_lang);
        $support = new CDiscountSupport($this->id_lang);

        // Display only if the module seems to be configured
        //
        $display = true;

        if (!($token = Configuration::get(self::KEY.'_TOKEN')) || empty($token)) {
            $display = false;
        }

        $php_infos = array();
        $module_infos = array();
        $cdiscount_infos = array();
        $prestashop_infos = array();
        $env_infos = array();
        $display_env = false;
        $currentVersion = Configuration::get(self::KEY.'_CURRENT_VERSION');

        if (Tools::strlen($currentVersion) && version_compare($this->version, $currentVersion, '>')) {
            $module_infos['update'] = array();
            $module_infos['update']['message'] = sprintf($this->l('Module Update: Your version will be auto-updated from %s to %s after configuration changes'), $currentVersion, $this->version);
            $module_infos['update']['message'] .= ' - '.$this->l('Please verify again your settings. Please clear your Smarty and Browser caches...');
            $module_infos['update']['tutorial'] = $support->gethreflink(CDiscountSupport::TUTORIAL_MODULE_UPDATE);
            $module_infos['update']['level'] = 'info '.($this->ps16x ? 'alert alert-warning' : 'warn');
        }

        if (is_dir(_PS_MODULE_DIR_.self::MODULE.'/export') && !CommonTools::isDirWriteable(_PS_MODULE_DIR_.self::MODULE.'/export')) {
            $module_infos['export_permissions']['message'] = sprintf($this->l('You have to set write permissions to the %s directory and its subsequents files'), _PS_MODULE_DIR_.self::MODULE.'/export');
            $module_infos['export_permissions']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        }
        if (is_dir(_PS_MODULE_DIR_.self::MODULE.'/export/xml') && !CommonTools::isDirWriteable(_PS_MODULE_DIR_.self::MODULE.'/export/xml')) {
            $module_infos['export_permissions_xml']['message'] = sprintf($this->l('You have to set write permissions to the %s directory and its subsequents files'), _PS_MODULE_DIR_.self::MODULE.'/export');
            $module_infos['export_permissions_xml']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        }
        if (is_dir(_PS_MODULE_DIR_.self::MODULE.'/export/csv') && !CommonTools::isDirWriteable(_PS_MODULE_DIR_.self::MODULE.'/export/csv')) {
            $module_infos['export_permissions_csv']['message'] = sprintf($this->l('You have to set write permissions to the %s directory and its subsequents files'), _PS_MODULE_DIR_.self::MODULE.'/export/csv');
            $module_infos['export_permissions_csv']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        }
        if (is_dir(_PS_MODULE_DIR_.self::MODULE.'/xml') && !CommonTools::isDirWriteable(_PS_MODULE_DIR_.self::MODULE.'/xml')) {
            $module_infos['export_permissions_xml2']['message'] = sprintf($this->l('You have to set write permissions to the %s directory and its subsequents files'), _PS_MODULE_DIR_.self::MODULE.'/xml');
            $module_infos['export_permissions_xml2']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        }

        if ($expert_mode) {
            $module_infos['expert_mode'] = array();
            $module_infos['expert_mode']['message'] = $this->l('Expert Mode is active');
            $module_infos['expert_mode']['level'] = 'info '.($this->ps16x ? 'alert alert-warning' : 'warn');
        }

        if (!is_array(self::$default_categories) || !count(array_filter(self::$default_categories))) {
            $module_infos['categories'] = array();
            $module_infos['categories']['message'] = $this->l('You didn\'t checked yet any category, in category tab');
            $module_infos['categories']['level'] = 'info '.($this->ps16x ? 'alert alert-warning' : 'warn');
            $module_infos['categories']['tutorial'] = $support->gethreflink(CDiscountSupport::TUTORIAL_CATEGORIES);
        }

        // AJAX Checker
        //
        $env_infos['ajax'] = array();
        $env_infos['ajax']['message'] = $this->l('AJAX execution failed. Please first verify your module configuration. If the problem persists please send a screenshot of this page to the support.');
        $env_infos['ajax']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        $env_infos['ajax']['display'] = false;
        $env_infos['ajax']['script'] = array(
            'name' => 'env_check_url',
            'url' => $this->url.'functions/check_env.php?action=ajax&instant_token='.self::$instant_token
        );

        // max_input_var Checker
        //
        $env_infos['miv'] = array();
        $env_infos['miv']['message'] = sprintf($this->l('Your PHP configuration limits the maximum number of fields to post in a form : %s for max_input_vars. Please ask your hosting provider to increase this limit.'), ini_get('max_input_vars'));
        $env_infos['miv']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        $env_infos['miv']['display'] = false;
        $env_infos['miv']['script'] = array('name' => 'miv');

        if (!$this->active) {
            $env_infos['inactive'] = array();
            $env_infos['inactive']['message'] = $this->l('Be carefull, your module is inactive, this mode stops all pending operations for this module, please change the status to active in your module list');
            $env_infos['inactive']['level'] = 'info '.($this->ps16x ? 'alert alert-warning' : 'warn');
            $env_infos['inactive']['display'] = false;
        }

        if (!CommonTools::tableExists(_DB_PREFIX_.'configuration') === null) {
            $env_infos['show_tables_failed'] = array();
            $env_infos['show_tables_failed']['message'] = sprintf('%s: %s', $this->l('Your hosting doesnt allow'), 'SHOW TABLES');
            $env_infos['show_tables_failed']['level'] = 'error '.($this->ps16x ? 'alert alert-danger' : '');
            $env_infos['show_tables_failed']['display'] = true;
            $env_infos['show_tables_failed']['script'] = array('name' => 'show_tables');
            $display_env = true;
        }

        // PHP Configuration Check
        //

        if (in_array(@Tools::strtolower(ini_get('display_errors')), array('1', 'on'))) {
            $php_infos['display_error']['message'] = $this->l('PHP display_errors is On.');
            $php_infos['display_error']['level'] = 'info '.($this->ps16x ? 'alert alert-info' : '');
        }

        if (!function_exists('curl_init')) {
            $php_infos['curl'] = array();
            $php_infos['curl']['message'] = $this->l('PHP cURL must be installed on this server. The module require the cURL library and can\'t work without');
            $php_infos['curl']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
            $php_infos['curl']['link'] = 'http://php.net/manual/'.$lang.'/book.curl.php';
        }

        if (!method_exists('DOMDocument', 'createElement')) {
            $php_infos['domdocument'] = array();
            $php_infos['domdocument']['message'] = $this->l('PHP DOMDocument (XML Library) must be installed on this server. The module require this library and can\'t work without');
            $php_infos['domdocument']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
            $php_infos['domdocument']['link'] = 'http://php.net/manual/'.$lang.'/class.domdocument.php';
        }

        if (!function_exists('mb_convert_encoding')) {
            $php_infos['curl'] = array();
            $php_infos['curl']['message'] = $this->l('Multibyte PHP Library must be installed on this server. The module require the mb functions and can\'t work without it');
            $php_infos['curl']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
            $php_infos['curl']['link'] = 'http://php.net/manual/'.$lang.'/ref.mbstring.php';
        }


        $recommended_memory_limit = 256;
        if ($memory_limit = CommonConfigurationCheck::getMemoryLimit() < $recommended_memory_limit) {
            $php_infos['memory']['message'] = sprintf($this->l('PHP value: memory_limit recommended value is at least %sMB. your limit is currently set to %sMB'), $recommended_memory_limit, $memory_limit);
            $php_infos['memory']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }

        // Prestashop Configuration Check
        //

        if (!(int)Configuration::get('PS_SHOP_ENABLE')) {
            $prestashop_infos['maintenance']['message'] = $this->l('Be carefull, your shop is in maintenance mode, the module might not work in that mode');
            $prestashop_infos['maintenance']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }

        if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_ === true) {
            $prestashop_infos['mod_dev']['message'] = $this->l('Prestashop _PS_MODE_DEV_ is active.');
            $prestashop_infos['mod_dev']['level'] = 'info '.($this->ps16x ? 'alert alert-info' : '');
            $prestashop_infos['mod_dev']['id'] = 'prestashop-info-dev';
        }

        if ((bool)Configuration::get('PS_CATALOG_MODE')) {
            $prestashop_infos['catalog']['message'] = $this->l('Your shop is in catalog mode, you wont be able to import orders, you can switch off this mode in Preferences > Products tab');
            $prestashop_infos['catalog']['level'] = 'warn '.($this->ps16x ? 'alert alert-warning' : '');
        }

        if (!(int)Language::getIdByIso('fr')) {
            $prestashop_infos['catalog']['message'] = 'CDiscount is a French marketplace, French language pack has to be installed';
            $prestashop_infos['catalog']['level'] = 'warn '.($this->ps16x ? 'alert alert-danger' : '');
        }

        if (!CommonConfigurationCheck::checkShopUrl()) {
            $prestashop_infos['wrong_domain']['message'] = $this->l('Your are currently connected with the following domain name:').' <span style="color:navy">'.$_SERVER['HTTP_HOST'].'</span><br />'.
                $this->l('This one is different from the main shop domain name set in "Preferences > SEO & URLs":').' <span style="color:green">'.$this->context->shop->domain.'</span>';
            $prestashop_infos['wrong_domain']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        }

        if (($max_execution_time = ini_get('max_execution_time')) && $max_execution_time < 120) {
            $prestashop_infos['max_execution_time']['message'] = sprintf($this->l('PHP value: max_execution_time recommended value is at least 120. your limit is currently set to %d'), $max_execution_time);
            $prestashop_infos['max_execution_time']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }

        foreach (array('birthday', 'company', 'siret', 'optin', 'newsletter') as $field) {
            if (!CommonConfigurationCheck::mandatoryCustomerField($field)) {
                $prestashop_infos[$field.'_issue']['message'] = sprintf($this->l('%s field is required, this is not a required value by default in Prestashop core program. This configuration is not allowed by Marketplaces modules. Please fix it!'), CommonTools::ucfirst($field));
                $prestashop_infos[$field.'_issue']['level'] = 'error '.($this->ps16x ? 'alert alert-danger' : '');
            }
        }

        if (!CommonConfigurationCheck::checkAddress()) {
            $prestashop_infos['phone_issue']['message'] = $this->l('Phone field is not required by default, but required in your configuration. This configuration is not allowed by Marketplaces modules. Please fix it !');
            $prestashop_infos['phone_issue']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        }

        if (CommonConfigurationCheck::hasOverrides()) {
            $prestashop_infos['overrides']['message'] = $this->l('Your Prestashop potentially runs some overrides. This information is necessary only in case of support');
            $prestashop_infos['overrides']['level'] = 'info '.($this->ps16x ? 'alert alert-info' : '');
        }

        if (!CommonConfigurationCheck::checkCountryConsistency()) {
            $prestashop_infos['locale_country']['message'] = sprintf('Inconsistency in localization settings, country code: "%s"', Configuration::get('PS_LOCALE_COUNTRY'));
            $prestashop_infos['locale_country']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        }

        if (is_array(self::$seller_informations) && count(self::$seller_informations) && array_key_exists('Seller', self::$seller_informations) && self::$seller_informations['Seller']['IsAvailable']) {
            if (in_array(self::$seller_informations['Seller']['IsAvailable'], array(null, 'Holidays', 'BannedSeller'))) {
                $state_css = 'alert alert-warning' ;
            } else {
                $state_css = 'alert alert-success';
            }

            $diag = sprintf($this->l('Connected to CDiscount as %s, status: %s'), self::$seller_informations['Seller']['ShopName'], self::$seller_informations['Seller']['IsAvailable']);

            $cdiscount_infos['cdiscount_infos']['message'] = $diag;
            $cdiscount_infos['cdiscount_infos']['level'] = 'info '.($this->ps16x ? $state_css : '');
        }

        if (!count($cdiscount_infos)) {
            $cdiscount_info_ok = false;
        } else {
            $cdiscount_info_ok = true;
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

        if (count($module_infos) == 1) {
            $module_info_ok = true;
        } else {
            $module_info_ok = false;
        }

        $suhosin_max_vars = @ini_get('suhosin.post.max_vars');
        $max_input_vars = @ini_get('max_input_vars');

        return array(
            'images' => $this->images,
            'display' => $display,
            'display_env' => $display_env,
            'env_infos' => $env_infos,
            'php_infos' => $php_infos,
            'module_infos' => $module_infos,
            'module_infos_ok' => $module_info_ok,
            'php_info_ok' => $php_info_ok,
            'module_info_ok' => $module_infos,
            'prestashop_infos' => $prestashop_infos,
            'prestashop_info_ok' => $prestashop_info_ok,
            'cdiscount_infos' => $cdiscount_infos,
            'cdiscount_info_ok' => $cdiscount_info_ok,
            'url' => $this->url,
            'max_input_vars' => $suhosin_max_vars > $max_input_vars ? $suhosin_max_vars : $max_input_vars,
            'expert_mode' => $expert_mode,
            'mode_dev' => defined('_PS_MODE_DEV_') && _PS_MODE_DEV_,
            'support_information_url' => $this->url.'functions/check.php?instant_token='.self::$instant_token.'&action=support_zip_file',
            'support_information_file_name' => CDiscountTools::getFriendlyUrl(Configuration::get('PS_SHOP_NAME')),
            'tutorial_php' => $support->gethreflink(CDiscountSupport::TUTORIAL_PHP),
        );
    }

    public static function multitenantGetList($debug = false)
    {
        require_once(dirname(__FILE__).'/classes/cdiscount.config.class.php');

        if (!is_array(self::$seller_informations) || !count(self::$seller_informations)) {
            if ((self::$seller_informations = CDiscountConfig::getSellerInformation($debug)) == null) {
                return null;
            }
        }

        $multitenant_cfg = unserialize(self::decode(Configuration::get(self::KEY.'_MULTITENANT')));

        $multitenant = array();

        $url = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/cdiscount/';
        $path = _PS_MODULE_DIR_.'cdiscount'.DS;
        $images = $url.'views/img/';

        $last_key = key(array_slice(self::$seller_informations['OfferPool'], -1, 1, true));

        if ((bool)Configuration::get(self::KEY.'_DEV_MODE')) {
            self::$seller_informations['OfferPool'] = array_merge(
                self::$seller_informations['OfferPool'],
                array(
                    array('Id' => 12, 'Description' => 'CornerHomme'),
                    array(
                        'Id' => 11,
                        'Description' => 'ComptoirDesParfums'
                    ),
                    array(
                        'Id' => 10,
                        'Description' => 'MonCornerKids'
                    ),
                    array(
                        'Id' => 9,
                        'Description' => 'MonCornerJardin'
                    ),
                    array(
                        'Id' => 6,
                        'Description' => 'MonCornerBrico'
                    ),
                    array('Id' => 5, 'Description' => 'MonCornerBaby'),
                    array('Id' => 2, 'Description' => 'MonCornerDeco')
                )
            );
        }

        foreach (self::$seller_informations['OfferPool'] as $key => $offerpool) {
            if (!is_array($offerpool)) {
                continue;
            }

            $Id = $offerpool['Id'];

            $multitenant[$Id] = $offerpool;

            $image_file = sprintf('%sviews/img/marketplaces/%s.png', $path, $offerpool['Id']);
            $image_url = sprintf('%smarketplaces/%s.png', $images, $offerpool['Id']);

            if (file_exists($image_file)) {
                $multitenant[$Id]['Image'] = $image_url;
            }

            if (is_array($multitenant_cfg) && count($multitenant_cfg) && in_array($offerpool['Id'], array_keys($multitenant_cfg))) {
                $multitenant[$Id]['Checked'] = true;
            }

            if ($key == $last_key) {
                $multitenant[$Id]['Last'] = true;
            }
        }

        return $multitenant;
    }

    // Move to separate file

    public function tabCategories()
    {
        $view_params = array();

        $categories = $this->getCategories();
        $index = array();

        self::$default_categories = $default_categories = CDiscountConfiguration::get('categories');

        $default_profiles2categories = CDiscountConfiguration::get('profiles_categories');

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_shop = Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;

            $shop = new Shop($id_shop);
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

        $html_categories = self::recurseCategoryForInclude($index, $categories, $first, $default_category, null, $default_categories, $default_profiles2categories, false);
        $view_params['list'] = $html_categories;
        // todo: Why used &$this->profiles
        $view_params['profiles'] = $this->loadProfiles();

        return $view_params;
    }


    /**
     * @param $indexedCategories
     * @param $categories
     * @param $current
     * @param int $id_category
     * @param null $id_category_default
     * @param array $default_categories
     * @param array $default_profiles
     * @param bool|false $next
     *
     * @return string
     */
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
        $img = ($next == false) ? 'lv1.gif' : 'lv'.$level.'_'.($todo == $doneC ? 'f' : 'b').'.gif';
        $selected_profile = null;
        // todo: Why used &$this->profiles['name']
        $saved_profiles = $this->loadProfiles();
        $saved_profiles = $saved_profiles['name'];

        if (is_array($saved_profiles) && count($saved_profiles)) {
            if (is_array($saved_profiles) && count($saved_profiles) == 1 && !empty($checked)) {
                $default_profile = true;
            } else {
                $default_profile = false;
            }

            foreach ($saved_profiles as $profile) {
                if (!isset($profile) || empty($profile)) {
                    continue;
                }

                if (isset($default_profiles[$id_category]) && $default_profiles[$id_category] == $profile || $default_profile) {
                    $selected_profile = $profile;
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
            'disabled' => !$next,
        );

        if (isset($categories[$id_category])) {
            if ($categories[$id_category]) {
                foreach (array_keys($categories[$id_category]) as $key) {
                    if ($key != 'infos') {
                        self::recurseCategoryForInclude($indexedCategories, $categories, $categories[$id_category][$key], $key, $id_category_default, $default_categories, $default_profiles, true);
                    }
                }
            }
        }

        return ($categories_table);
    }

    /**
     * Todo: Use model internal Id instead of model name
     */
    public function tabMappings()
    {
        $hasSizeFields = $this->adminConfigurationManager->tabMappingHasSizeFields();
        $view_params = array(
            'has_size_fields' => (count($hasSizeFields['size_attributes']) + count($hasSizeFields['size_features'])) > 0,
            'has_size_attributes_fields' => count($hasSizeFields['size_attributes']) > 0,
            'has_size_features_fields' => count($hasSizeFields['size_features']) > 0,
        );

        $attributes_groups = self::$attributes_groups;
        $attributes = self::$attributes;
        $features = self::$features;
        $features_values = &self::$features_values;

        $mapping_left = unserialize(self::decode(Configuration::get(self::KEY.'_ATTRIBUTES_MAPPING_L')));
        $mapping_right = unserialize(self::decode(Configuration::get(self::KEY.'_ATTRIBUTES_MAPPING_R')));

        $models = $this->loadModels();
        if (is_array($models) && count($models)) {
            /*
             * Color Mapping
             */
            $sizes_mappings_map = CDiscountConfiguration::get('sizes_mapping');
            $size_mapping_attributes = array();
            $size_mapping_features = array();

            foreach ($models as $modelInternalId => $modelData) {
                $moduleModel = new CDiscountModuleModel($modelInternalId, $modelData);

                // Ignore if no variation
                if (!$moduleModel->categoryId || !$moduleModel->modelId || !$moduleModel->hasVariation()) {
                    continue;
                }

                $model_key = CDiscountModel::toKey($moduleModel->name);
                $attribute_size_field = (int)$moduleModel->fashionSize;
                $feature_size_field = (int)$moduleModel->featureSize;
                if ($attribute_size_field) {
                    if (!array_key_exists($attribute_size_field, $attributes_groups)) {
                        continue;
                    }

                    $title = sprintf('%s (%s)', $moduleModel->name, $moduleModel->modelName);
                    $current_attributes = $attributes[$attribute_size_field];
                    $selected_attribute = is_array($sizes_mappings_map) &&
                        isset($sizes_mappings_map['attribute'], $sizes_mappings_map['attribute'][$model_key], $sizes_mappings_map['attribute'][$model_key][$attribute_size_field]) ?
                        $sizes_mappings_map['attribute'][$model_key][$attribute_size_field] : null;

                    $size_mapping_attributes[$model_key] = array(
                        'title' => $title,
                        'id_attribute_group' => $attribute_size_field,
                        'attributes_group_name' => $attributes_groups[$attribute_size_field]['name'],
                        'attributes' => $current_attributes,
                        'model_id' => $moduleModel->modelId,
                        'category_id' => $moduleModel->categoryId,
                        'selected_attributes' => $selected_attribute,
                    );
                }

                if ($feature_size_field) {
                    if (!array_key_exists($feature_size_field, $features) || !array_key_exists($feature_size_field, $features_values)) {
                        continue;
                    }

                    $title = sprintf('%s (%s)', $moduleModel->name, $moduleModel->modelName);
                    $current_features_values = $features_values[$feature_size_field];
                    $selected_feature = is_array($sizes_mappings_map) &&
                        isset($sizes_mappings_map['feature'], $sizes_mappings_map['feature'][$model_key], $sizes_mappings_map['feature'][$model_key][$feature_size_field]) ?
                        $sizes_mappings_map['feature'][$model_key][$feature_size_field] : null;

                    $size_mapping_features[$model_key] = array(
                        'title' => $title,
                        'id_feature' => $features[$feature_size_field]['id_feature'],
                        'features_values' => $current_features_values,
                        'model_id' => $moduleModel->modelId,
                        'category_id' => $moduleModel->categoryId,
                        'selected_features' => $selected_feature,
                    );
                }
            }

            $sizes_mapping = array('attributes' => $size_mapping_attributes, 'features' => $size_mapping_features);
            $view_params['sizes_mapping'] = $sizes_mapping;

            /*
             * Features Mapping
             */
            $features_mapping = array();
            $features_mappings_map = CDiscountConfiguration::get('features_mapping');

            foreach ($models as $modelInternalId => $modelData) {
                $moduleModel = new CDiscountModuleModel($modelInternalId, $modelData);

                if (!$moduleModel->categoryId || !$moduleModel->modelId) {
                    continue;
                }

                $model_id = $moduleModel->modelId;
                $model_name = $moduleModel->name;
                $model_key = CDiscountModel::toKey($moduleModel->name);
                $categoryId = $moduleModel->categoryId;
                $marketplace_model_name = $moduleModel->modelName;

                if (!array_key_exists($model_id, self::$validValuesByModelId)) {
                    self::$validValuesByModelId[$model_id] = self::getInstanceCDiscountModel()->getModelValuesByModelId($categoryId, $model_id);
                }
                $valid_values = &self::$validValuesByModelId[$model_id];

                $specifics_fields = CDiscountSpecificField::getInstance()->getConfigurationByKey($modelInternalId);
                $title = null;
                $previous_title = null;

                if (is_array($specifics_fields) && count($specifics_fields)) {
                    foreach ($specifics_fields as $cdiscount_attribute_key => $specific_field_entry) {
                        if (empty($cdiscount_attribute_key)) {
                            continue;
                        }

                        if (is_array($specific_field_entry) && count($specific_field_entry) && array_key_exists('value', $specific_field_entry)) {
                            $matched = array();
                            $cdiscount_default_attribute_value = $specific_field_entry['value'];
                            $associated_feature_id = (int)$specific_field_entry['feature'];

                            if (!$associated_feature_id) {
                                // not associated feature, we do not create a mapping entry

                                continue;
                            }

                            if (array_key_exists($cdiscount_attribute_key, $valid_values) && array_key_exists('values', $valid_values[$cdiscount_attribute_key])) {
                                if (empty($cdiscount_attribute_key)) {
                                    continue;
                                }

                                if (!array_key_exists((int)$associated_feature_id, $features)) {
                                    continue;
                                }

                                if (!array_key_exists((int)$associated_feature_id, $features_values) || !count($features_values[(int)$associated_feature_id])) {
                                    continue;
                                }

                                if (!count($features_values[(int)$associated_feature_id]) || !count($valid_values[$cdiscount_attribute_key]['values'])) {
                                    continue;
                                }

                                // $features_mappings_map (saved features mappings): Path: model_key / $cdiscount_attribute_key / valid_value_key
                                if (is_array($features_mappings_map) && array_key_exists($model_key, $features_mappings_map) && array_key_exists($cdiscount_attribute_key, $features_mappings_map[$model_key])) {
                                    $selected_values = $features_mappings_map[$model_key][$cdiscount_attribute_key];
                                } else {
                                    $selected_values = array();
                                }

                                $title = sprintf('%s (%s)', $model_name, $marketplace_model_name);

                                $current_features_values = $features_values;

                                // Remove matching values
                                foreach ($current_features_values[(int)$associated_feature_id] as $key => $feature_value) {
                                    $feature_value_key = CDiscountModel::toKey($feature_value['value']);

                                    // Feature Value is already a CDiscount valid value, we remove it
                                    if (in_array($feature_value_key, array_keys($valid_values[$cdiscount_attribute_key]['values']))) {
                                        unset($current_features_values[(int)$associated_feature_id][$key]);
                                        unset($valid_values[$cdiscount_attribute_key]['values'][$feature_value_key]);
                                        $matched[] = $feature_value['value'];
                                    }
                                }

                                if (count($matched) == count($valid_values[$cdiscount_attribute_key]) || count($matched) == count($current_features_values[(int)$associated_feature_id])) {
                                    continue;
                                }
                                if (count($valid_values[$cdiscount_attribute_key]) && count($current_features_values[(int)$associated_feature_id])) {
                                    $features_mapping[$model_name][$cdiscount_attribute_key] = array();
                                    $features_mapping[$model_name][$cdiscount_attribute_key]['title'] = $previous_title === null || $title != $previous_title ? $title : null;
                                    $features_mapping[$model_name][$cdiscount_attribute_key]['model_key'] = $model_key;
                                    $features_mapping[$model_name][$cdiscount_attribute_key]['name'] = $valid_values[$cdiscount_attribute_key]['title'];
                                    $features_mapping[$model_name][$cdiscount_attribute_key]['valid_values'] = $valid_values[$cdiscount_attribute_key]['values'];
                                    $features_mapping[$model_name][$cdiscount_attribute_key]['selected_values'] = $selected_values;
                                    $features_mapping[$model_name][$cdiscount_attribute_key]['default'] = $cdiscount_default_attribute_value;
                                    $features_mapping[$model_name][$cdiscount_attribute_key]['id_feature'] = (int)$associated_feature_id;
                                    $features_mapping[$model_name][$cdiscount_attribute_key]['feature'] = $features[(int)$associated_feature_id];
                                    $features_mapping[$model_name][$cdiscount_attribute_key]['feature_values'] = $current_features_values[(int)$associated_feature_id];

                                    if (is_array($matched) && count($matched)) {
                                        $matching_list = implode(', ', array_unique($matched));

                                        if (Tools::strlen($matching_list) > 64) {
                                            preg_replace('/(?<=^.{64}).{4,}(?=.{64}$)/', '...', $matching_list);
                                        }

                                        $features_mapping[$model_name][$cdiscount_attribute_key]['matched'] = $matching_list;
                                    }
                                }
                                $previous_title = $title;
                            }
                        }
                    }
                }
            }

            $view_params['features_mapping'] = $features_mapping;
        }


        if ($attributes) {
            $view_params['attr_saved_groups'] = array();
            $previousgrp = -1;
            $index = 0;
            foreach ($attributes as $idgrp => $group) {
                $attr_saved_group = array();

                if (in_array($idgrp, $hasSizeFields['size_attributes'])) {
                    continue;
                }

                if ($idgrp != $previousgrp) {
                    $index = 0;
                    $attr_saved_group['label'] = 1;
                }
                $previousgrp = $idgrp;

                $saved = array_merge(array(0 => false), isset($mapping_left[$idgrp]) ? $mapping_left[$idgrp] : array());
                $temp = array_merge(array(0 => false), isset($mapping_right[$idgrp]) ? $mapping_right[$idgrp] : array());
                $attr_saved_group['idgrp'] = $idgrp;

                foreach ($attributes_groups as $attributes_group) {
                    if (isset($attributes_group['id_attribute_group']) && $attributes_group['id_attribute_group'] == $idgrp) {
                        $attr_saved_group['attr_group_name'] = $attributes_group['name'];
                        $attr_saved_group['group'] = $group;
                        break;
                    }
                }
                $keys_to_del = array();
                foreach ($saved as $sKey => $s) {
                    if ($s === '') {
                        $keys_to_del[] = $sKey;
                    }
                }

                foreach ($keys_to_del as $key) {
                    unset($saved[$key]);
                }

                $saved_length = count($saved);
                foreach ($saved as $idx => $selected) {
                    $saved_length--;
                    if (!$selected && $selected !== false) {
                        continue;
                    }

                    $attr_saved_group['index'] = $index;
                    $attr_saved_group['disabled'] = '';

                    if ($group) {
                        $attr_saved_group['groups'] = array();
                    }

                    foreach ($group as $idattr => $attrname) {
                        $groups_array = array();
                        if ($selected == $idattr) {
                            $set = ' selected="selected"';
                        } else {
                            $set = '';
                        }

                        $groups_array['value'] = $idattr;
                        $groups_array['selected'] = $set;
                        $groups_array['desc'] = addslashes($attrname);

                        $attr_saved_group['groups'][] = $groups_array;
                    }

                    if ($saved_length == 0) {
                        $attr_saved_group['last'] = 1;
                    }

                    $attr_saved_group['selected'] = $selected;
                    $attr_saved_group['fashion_cd_value'] = isset($temp) && isset($temp[(int)$idx]) ? $temp[(int)$idx] : '';
                    $attr_saved_group['display_del'] = $index == 0 ? 'display:none' : '';
                    $attr_saved_group['display_add'] = $index > 0 ? 'display:none' : '';

                    $view_params['attr_saved_groups'][] = $attr_saved_group;

                    $index++;
                }
            }
        }
        return $view_params;
    }

    public static function decode($configuration)
    {
        return base64_decode($configuration);   // TODO Validation: Configuration requirement
    }

    public function tabTransport()
    {
        $view_params = array();

        // Carriers
        //
        $carriers_info = self::$carriers_info;
        $carriers_params = self::$carriers_params;
        $carriers_clogistique = self::$carriers_clogistique;

        // Aug-23-2018: Remove ps_carriers_only option

        if (defined('Carrier::ALL_CARRIERS')) {
            $all_carriers = Carrier::ALL_CARRIERS;
        } elseif (defined('ALL_CARRIERS')) {
            $all_carriers = ALL_CARRIERS;
        } else {
            $all_carriers = 5;
        }

        // Aug-23-2018: Get all carriers, include modules's
        $carriers = Carrier::getCarriers($this->context->cookie->id_lang, false, false, false, null, $all_carriers);

        /* ADD Mondial Relay carriers if we have some */
        $mr_carriers = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'carrier` WHERE `external_module_name` = "mondialrelay" AND `deleted` = 0');

        if (is_array($mr_carriers) && count($mr_carriers)) {
            $carriers = array_merge($carriers, $mr_carriers);
        }

        /* ADD SoColissimo carriers if we have some */
        $so_carriers = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'carrier` WHERE `external_module_name` IN ("soliberte", "soflexibilite") AND `deleted` = 0');

        if (is_array($so_carriers) && count($so_carriers)) {
            $carriers = array_merge($carriers, $so_carriers);
        }

        $view_params['preparation_time'] = Configuration::get(self::KEY.'_PREPARATION_TIME');
        $view_params['preparation_time'] = $view_params['preparation_time'] == false ? '1' : $view_params['preparation_time'];

        $this->debugDetails->configuration('Global information', print_r(self::$global_informations, 1), 'Carriers info', print_r($carriers_info, 1));
        if (is_array(self::$global_informations) && array_key_exists('Carriers', self::$global_informations) && count(self::$global_informations)) {
            $tracking_mapping_active = true;
            $view_params['tracking_carriers'] = self::$global_informations['Carriers'];
        } else {
            $tracking_mapping_active = false;
            $view_params['tracking_carriers'] = array();
        }
        $view_params['tracking_mapping'] = array();
        $this->debugDetails->configuration('Tracking mapping status', $tracking_mapping_active);

        $carriers_mapping = array();

        $carrier_methods_list = self::$predefined_carriers;
        $available_carrier_methods_list = array();

        if (is_array(self::$seller_informations) && isset(self::$seller_informations['DeliveryModeInformation']) && is_array(self::$seller_informations['DeliveryModeInformation'])) {
            foreach (self::$seller_informations['DeliveryModeInformation'] as $dmi) {
                if (!is_array($dmi)) {
                    continue;
                }
                $available_carrier_methods_list[$dmi['Code']] = $dmi['Name'];
            }
        }
        $optionnals = false;

        foreach ($carrier_methods_list as $code => $type) {
            if (is_array($available_carrier_methods_list) && count($available_carrier_methods_list)) {
                if (!in_array($code, array_keys($available_carrier_methods_list))) {
                    unset($carriers_params[$type]);
                    continue;
                }
            }
            if (!isset($carriers_params[$type])) {
                unset($carriers_params[$type]);
            }

            $carriers_params[$type]['Code'] = $code;

            foreach (array('ChargeMin', 'ChargeAdd') as $param) {
                if (!isset($carriers_params[$type][$param])) {
                    $carriers_params[$type][$param] = null;
                } elseif ((float)$carriers_params[$type][$param]) {
                    $carriers_params[$type][$param] = sprintf('%.02f', $carriers_params[$type][$param]);
                }
            }

            foreach ($carriers as $carrier) {
                if (!isset($carriers_info[$type])) {
                    $carriers_info[$type] = null;
                }

                #if (isset($carrier['external_module_name']) && !empty($carrier['external_module_name']) && !in_array($type, array('RelaisColis', 'SoColissimo', 'Relay', 'MondialRelay', 'TNT')))
                #    continue;

                if (in_array($type, array('RelaisColis', 'SoColissimo', 'Relay', 'MondialRelay', 'TNT'))) {
                    $optionnals = true;
                    $carriers_params[$type]['optionnal'] = true;
                } else {
                    $carriers_params[$type]['optionnal'] = false;
                }
                $id_carrier = (int)$carrier['id_carrier'];

                $carrier_array = array();
                $carrier_array['value'] = $id_carrier;
                $carrier_array['selected'] = $selected = ((int)$carrier['id_carrier'] == $carriers_info[$type] ? 'selected="selected"' : '');
                $carrier_array['desc'] = $carrier['name'];

                if ($selected && $tracking_mapping_active) {
                    $cdiscount_id = is_array(self::$tracking_mapping) && count(self::$tracking_mapping) && array_key_exists($id_carrier, self::$tracking_mapping) ? self::$tracking_mapping[$id_carrier] : null;

                    $view_params['tracking_mapping'][$id_carrier] = array(
                    'prestashop_name' => $carrier['name'],
                    'prestashop_id' => $id_carrier,
                    'cdiscount_id' => $cdiscount_id
                    );
                }

                $carriers_mapping[$type][] = $carrier_array;
            }
        }
        if ($this->clogistique) {
            $view_params['carriers_clogistique']['prestashop'] = array();
            $view_params['carriers_clogistique']['mapping'] = array();

            foreach (self::$carrier_for_clogistique as $clogistique_carrier_label) {
                $clogistique_carrier_code = $clogistique_carrier_label['Code'];
                $clogistique_carrier_label = $clogistique_carrier_label['Name'];

                $view_params['carriers_clogistique']['clogistique'][$clogistique_carrier_code] = array('code' => $clogistique_carrier_code, 'label' => $clogistique_carrier_label);
                $view_params['carriers_clogistique']['prestashop'] = array();

                if (is_array($carriers_clogistique) && array_key_exists($clogistique_carrier_code, $carriers_clogistique)) {
                    $view_params['carriers_clogistique']['mapping'][$clogistique_carrier_code] = $carriers_clogistique[$clogistique_carrier_code];
                } else {
                    $view_params['carriers_clogistique']['mapping'][$clogistique_carrier_code] = null;
                }
            }

            foreach ($carriers as $carrier) {
                $id_carrier = (int)$carrier['id_carrier'];
                $carrier_array = array();
                $carrier_array['id_carrier'] = $id_carrier;
                $carrier_array['selected'] = $selected = ((int)$carrier['id_carrier'] == 0 ? 'selected="selected"' : '');
                $carrier_array['desc'] = $carrier['name'];

                $cdiscount_id = is_array(self::$tracking_mapping) && count(self::$tracking_mapping) && array_key_exists($id_carrier, self::$tracking_mapping) ? self::$tracking_mapping[$id_carrier] : null;

                if ($selected && $tracking_mapping_active) {
                    $view_params['tracking_mapping'][$id_carrier] = array(
                    'prestashop_name' => $carrier['name'],
                    'prestashop_id' => $id_carrier,
                    'cdiscount_id' => $cdiscount_id
                    );
                }

                $view_params['carriers_clogistique']['prestashop'][] = $carrier_array;
            }
        }
        $view_params['experimental_features'] = self::ENABLE_EXPERIMENTAL_FEATURES;
        $view_params['carriers_info'] = $carriers_mapping;
        $view_params['carriers_params'] = $carriers_params;
        $view_params['carriers_optionnals'] = $optionnals;
        $view_params['carriers_labels'] = self::$carrier_labels;

        return $view_params;
    }

    public function tabOrders()
    {
        $view_params = array();

        $id_lang = $this->context->cookie->id_lang;
        $order_states = unserialize(self::decode(Configuration::get(self::KEY.'_ORDERS_STATES')));

        $order_state_accepted = null;
        $order_state_sent = null;
        $order_state_delivered = null;
        $order_state_delivered_clogistique = null;

        if (is_array($order_states)) {
            $order_state_accepted = $order_states[self::KEY.'_CA'];
            $order_state_sent = $order_states[self::KEY.'_CE'];
            $order_state_delivered = $order_states[self::KEY.'_CL'];
            $order_state_delivered_clogistique = isset($order_states[self::KEY.'_CLCL']) ? $order_states[self::KEY.'_CLCL'] : null;
        }

        if (!$order_state_accepted) {
            $order_state_accepted = defined('_PS_OS_PAYMENT_') ? _PS_OS_PAYMENT_ : (int)Configuration::get('PS_OS_PAYMENT');
        }
        if (!$order_state_sent) {
            $order_state_sent = defined('_PS_OS_SHIPPING_') ? _PS_OS_SHIPPING_ : (int)Configuration::get('PS_OS_SHIPPING');
        }
        if (!$order_state_delivered) {
            $order_state_delivered = defined('_PS_OS_DELIVERED_') ? _PS_OS_DELIVERED_ : (int)Configuration::get('PS_OS_DELIVERED');
        }
        if (!$order_state_delivered_clogistique) {
            $order_state_delivered_clogistique = defined('_PS_OS_SHIPPING_') ? _PS_OS_DELIVERED_ : (int)Configuration::get('PS_OS_SHIPPING');
        }

        $orderStates = OrderState::getOrderStates($id_lang);

        $view_params['cd_mapping_order_states_01'] = array();

        foreach ($orderStates as $orderState) {
            if (!(int)$orderState['id_order_state']) {
                continue;
            }
            $states_array = array();

            if ((int)$orderState['id_order_state'] == $order_state_accepted) {
                $selected = 'selected="selected"';
            } else {
                $selected = '';
            }

            $states_array['value'] = (int)$orderState['id_order_state'];
            $states_array['selected'] = $selected;
            $states_array['desc'] = $orderState['name'];

            $view_params['cd_mapping_order_states_01'][] = $states_array;
        }

        $view_params['cd_mapping_order_states_02'] = array();

        foreach ($orderStates as $orderState) {
            if (!(int)$orderState['id_order_state']) {
                continue;
            }
            if ((int)$orderState['id_order_state'] == $order_state_sent) {
                $selected = 'selected="selected"';
            } else {
                $selected = '';
            }

            $states_array = array();
            $states_array['value'] = (int)$orderState['id_order_state'];
            $states_array['selected'] = $selected;
            $states_array['desc'] = $orderState['name'];
            $view_params['cd_mapping_order_states_02'][] = $states_array;
        }

        //
        // Order Statuses - Delivered
        //
        $view_params['cd_mapping_order_states_03'] = array();

        foreach ($orderStates as $orderState) {
            if (!(int)$orderState['id_order_state']) {
                continue;
            }
            if ((int)$orderState['id_order_state'] == $order_state_delivered) {
                $selected = 'selected="selected"';
            } else {
                $selected = '';
            }

            $states_array = array();
            $states_array['value'] = (int)$orderState['id_order_state'];
            $states_array['selected'] = $selected;
            $states_array['desc'] = $orderState['name'];
            $view_params['cd_mapping_order_states_03'][] = $states_array;
        }
        $view_params['expert_mode'] = $expert_mode = (bool)Configuration::get(self::KEY.'_EXPERT_MODE');

        if ($expert_mode && $this->clogistique) {
            //
            // Order Statuses - Delivered
            //
            $view_params['cd_mapping_order_states_cl'] = array();

            foreach ($orderStates as $orderState) {
                if (!(int)$orderState['id_order_state']) {
                    continue;
                }
                if ((int)$orderState['id_order_state'] == $order_state_delivered_clogistique) {
                    $selected = 'selected="selected"';
                } else {
                    $selected = '';
                }

                $states_array = array();
                $states_array['value'] = (int)$orderState['id_order_state'];
                $states_array['selected'] = $selected;
                $states_array['desc'] = $orderState['name'];
                $view_params['cd_mapping_order_states_cl'][] = $states_array;
            }
        }

        //
        // Bulk Mode
        //
        $view_params['bulk_mode'] = Configuration::get(self::KEY.'_BULK_MODE') ? 'checked' : '';
        $view_params['extra_fees'] = Configuration::get(self::KEY.'_EXTRA_FEES') ? 'checked' : '';

        return $view_params;
    }

    public function tabSettings()
    {
        $on_sale_period = Configuration::get(self::KEY.'_ON_SALE_PERIOD');

        $view_params = array();
        $view_params['specials'] = Configuration::get(self::KEY.'_USE_SPECIALS') ? 'checked="checked"' : '';
        $view_params['formula_on_specials'] = Configuration::get(self::KEY.'_FORMULA_ON_SPECIALS') ? 'checked="checked"' : '';
        $view_params['on_sale_period'] = $on_sale_period === false || (int)$on_sale_period == 1 ? 'checked="checked"' : '';
        $view_params['taxes'] = Configuration::get(self::KEY.'_USE_TAXES') ? 'checked="checked"' : '';
        $view_params['smart_rounding'] = Configuration::get(self::KEY.'_SMART_ROUNDING') ? 'checked="checked"' : '';
        $view_params['clogistique'] = Configuration::get(self::KEY.'_CLOGISTIQUE') ? 'checked="checked"' : '';
        $view_params['clogistique_destock'] = Configuration::get(self::KEY.'_CLOGISTIQUE_DESTOCK') ? 'checked="checked"' : '';
        $view_params['oos_checked'] = Configuration::get(self::KEY.'_ALLOW_OOS') ? 'checked="checked"' : '';
        $view_params['disable_proxy'] = Configuration::get(self::KEY.'_DISABLE_PROXY') ? 'checked="checked"' : '';

        $import_type = (Configuration::get(self::KEY.'_IMPORT_TYPE') ? Configuration::get(self::KEY.'_IMPORT_TYPE') : self::IMPORT_BY_ID);
        $view_params['import_by_id'] = self::IMPORT_BY_ID;
        $view_params['import_by_id_checked'] = ($import_type == self::IMPORT_BY_ID) ? 'checked="checked"' : '';
        $view_params['import_by_sku'] = self::IMPORT_BY_SKU;
        $view_params['import_by_sku_checked'] = ($import_type == self::IMPORT_BY_SKU) ? 'checked="checked"' : '';

        $decription_field_pre = Configuration::get(self::KEY.'_DESCRIPTION_FIELD');
        $decription_field = ($decription_field_pre ? $decription_field_pre : self::FIELD_DESCRIPTION_SHORT);
        $view_params['long_description'] = self::FIELD_DESCRIPTION_LONG;
        $view_params['long_description_checked'] = $decription_field == self::FIELD_DESCRIPTION_LONG ? 'checked="checked"' : '';
        $view_params['short_description'] = self::FIELD_DESCRIPTION_SHORT;
        $view_params['short_description_checked'] = $decription_field == self::FIELD_DESCRIPTION_SHORT ? 'checked="checked"' : '';

        $long_description_field_pre = Configuration::get(self::KEY.'_LONG_DESCRIPTION_FIELD');
        $long_description_field = ($long_description_field_pre ? $long_description_field_pre : self::FIELD_DESCRIPTION_LONG);

        $view_params['long_description_field_short_checked'] = $long_description_field == self::FIELD_DESCRIPTION_SHORT ? 'checked="checked"' : '';
        $view_params['long_description_field_long_checked'] = $long_description_field == self::FIELD_DESCRIPTION_LONG ? 'checked="checked"' : '';

        $view_params['marketing_description'] = (bool)Configuration::get(self::KEY.'_MARKETING_DESCRIPTION');

        $align_active = Configuration::get(self::KEY.'_ALIGNMENT_ACTIVE');
        $view_params['align_active_checked'] = $align_active ? ' checked="checked"' : '';

        $expert_mode = Configuration::get(self::KEY.'_EXPERT_MODE');
        $view_params['expert_mode_checked'] = $expert_mode ? ' checked="checked"' : '';

        $view_params['ean_policies']['normal'] = self::EAN_POLICY_NORMAL;
        $view_params['ean_policies']['exempt'] = self::EAN_POLICY_EXEMPT;
        $view_params['ean_policies']['permissive'] = self::EAN_POLICY_PERMISSIVE;

        $view_params['ean_policy'] = (int)Configuration::get(self::KEY.'_EAN_POLICY');

        $view_params['title_format'] = (int)Configuration::get(self::KEY.'_TITLE_FORMAT');
        $view_params['title_formats'] = array(
            'title_name_attributes_with_label' => self::TITLE_NAME_ATTRIBUTES_WITH_LABEL,
            'title_name_attributes' => self::TITLE_NAME_ATTRIBUTES,
            'title_brand_name_attributes' => self::TITLE_BRAND_NAME_ATTRIBUTES,
            'title_category_name_attributes' => self::TITLE_CATEGORY_NAME_ATTRIBUTES,
            'title_category_brand_name_attributes' => self::TITLE_CATEGORY_BRAND_NAME_ATTRIBUTES,
            'title_name_reference' => self::TITLE_NAME_REFERENCE,
        );

        $email_logs = Configuration::get(self::KEY.'_EMAIL_LOGS');
        $view_params['email_logs_checked'] = $email_logs ? ' checked="checked"' : '';

        // Shop Configuration
        //
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $view_params['ps_version_gt_15_or_equal'] = '1';

            // Warehouse (PS 1.5 with Stock Management)
            //
            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                $view_params['ps_advanced_stock_management'] = '1';

                $view_params['warehouse_options'] = array();

                foreach (Warehouse::getWarehouses(true) as $warehouse) {
                    $warehouse_array = array();
                    if ((int)$warehouse['id_warehouse'] == (int)Configuration::get(self::KEY.'_WAREHOUSE')) {
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

        //
        // Image Type (PS 1.5.3.1+)
        //
        if (version_compare(_PS_VERSION_, '1.4.1', '>=')) {
            $image_type = Configuration::get(self::KEY.'_IMAGE_TYPE');

            $view_params['ps_version_gt_141'] = '1';

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


        $view_params['employee'] = array();
        $id_selected_employee = (int)Configuration::get(self::KEY.'_EMPLOYEE');

        // Employee::getEmployees is displayed as deprecated in PS 1.4 ... but not in PS 1.5
        foreach (@Employee::getEmployees() as $employee) {
            $id_employee = (int)$employee['id_employee'];

            if ($id_employee == $id_selected_employee) {
                $selected = true;
            } else {
                $selected = false;
            }

            $view_params['employee'][$id_employee]['name'] = (isset($employee['name']) ? $employee['name'] : sprintf('%s %s', $employee['firstname'], $employee['lastname']));
            $view_params['employee'][$id_employee]['selected'] = $selected;
        }


        $conditionMap = unserialize(self::decode(Configuration::get(self::KEY.'_CONDITION_MAP')));

        // Products Condition/State
        //
        $sql = 'SHOW COLUMNS FROM `'._DB_PREFIX_.'product` where Field = "condition"';
        $query = Db::getInstance()->ExecuteS($sql);

        if (is_array($query)) {
            $query = array_shift($query);
        }

        // Uncompatible with PS < 1.4 / Looking for compatibility or assuming New as default
        //
        if (isset($query['Field']) && $query['Field'] == 'condition') {
            $view_params['field_condition'] = '1';
            $view_params['product_conditions'] = array();
            // For i18n
            //
            $default_conditions = array($this->l('new'), $this->l('used'), $this->l('refurbished'));

            // Fetch columns names
            //
            preg_match_all("/'([\w ]*)'/", $query['Type'], $ps_conditions);

            $i = 0;
            foreach ($this->_conditions as $key => $condition) {
                $i++;

                $disabled = '';

                $prod_condition = array();
                $prod_condition['key_condition'] = $condition.' ('.$key.')';
                $prod_condition['key'] = $key;
                $prod_condition['idx'] = $i;
                $prod_condition['disabled'] = $disabled;

                $prod_condition['conditions_list'] = array();
                $selectedValue = false;
                foreach ($ps_conditions[1] as $ps_condition) {
                    $conditions_array = array();

                    if (isset($conditionMap[$key]) && !empty($conditionMap[$key]) && $conditionMap[$key] == $ps_condition) {
                        $selectedValue = true;
                        $selected = 'selected="selected"';
                    } else {
                        $selected = '';
                    }

                    $conditions_array['value'] = $ps_condition;
                    $conditions_array['selected'] = $selected;
                    $conditions_array['desc'] = CommonTools::ucfirst($this->l($ps_condition));

                    $prod_condition['conditions_list'][] = $conditions_array;
                }
                if (!$selectedValue) {
                    //assigns a defalt value selected
                    foreach ($prod_condition['conditions_list'] as $idxc => $c) {
                        if ($c['value'] == 'new' && $key == 6) {
                            // 6 > New

                            $arrayC = $c;
                            $arrayC['selected'] = 'selected="selected"';
                            $prod_condition['conditions_list'][$idxc] = $arrayC;
                        } else {
                            continue;
                        }
                    }
                }
                $view_params['product_conditions'][] = $prod_condition;
            }
        }

        $view_params['comments'] = Configuration::get(self::KEY.'_DEFAULT_COMMENT');

        $view_params['individual'] = Configuration::get(self::KEY.'_INDIVIDUAL_ACCOUNT') ? 'checked="checked"' : '';
        $view_params['domain'] = Configuration::get(self::KEY.'_INDIVIDUAL_DOMAIN');

        if (empty($view_params['domain'])) {
            $view_params['domain'] = sprintf('@%s', self::TRASH_DOMAIN);
        }

        if (empty($view_params['individual'])) {
            $style = 'display:none;';
        } else {
            $style = !$expert_mode ? 'display:none' : '';
        }

        $view_params['style'] = $style;

        //
        // Customer groups
        //
        $view_params['customer_groups'] = array();

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_default_customer_group = Configuration::get('PS_CUSTOMER_GROUP');
        } else {
            $id_default_customer_group = (int)_PS_DEFAULT_CUSTOMER_GROUP_;
        }

        $id_customer_group = (int)Configuration::get(self::KEY.'_CUSTOMER_GROUP');

        if (!(int)$id_customer_group || !is_numeric($id_customer_group)) {
            $id_customer_group = $id_default_customer_group;
        }

        foreach (Group::getGroups($this->context->language->id, true) as $customer_group) {
            $id_group = (int)$customer_group['id_group'];

            if ($id_group == $id_customer_group) {
                $selected = true;
            } else {
                $selected = false;
            }

            $view_params['customer_groups'][$id_group]['name'] = $customer_group['name'];
            $view_params['customer_groups'][$id_group]['selected'] = $selected;
        }
        $view_params['id_default_customer_group'] = $id_default_customer_group;

        return $view_params;
    }

    private function tabMessaging()
    {
        $mail_invoice = unserialize(self::decode(Configuration::get('CDISCOUNT_MESSAGING')));
        $pass = true;
        $lang = Language::getIsoById($this->id_lang);
        $mail_templates = null;
        $mail_add_files = null;

        $view_params = array();
        $view_params['images'] = $this->images;
        $view_params['url'] = $this->url;
        $view_params['is_ps15'] = version_compare(_PS_VERSION_, '1.5', '>=');

        // Order State
        $orderStates = OrderState::getOrderStates($this->id_lang);

        $view_params['order_states'] = array();

        $c = 0;
        foreach ($orderStates as $orderState) {
            if (!(int)$orderState['id_order_state']) {
                continue;
            }

            if (!$orderState['invoice'] || $orderState['send_email']) {
                continue;
            }

            $view_params['order_states'][$c]['value'] = (int)$orderState['id_order_state'];
            $view_params['order_states'][$c]['name'] = $orderState['name'];
            $c++;
        }

        // Mail Template
        $mailDir = sprintf('%s%s/*.html', $this->path_mail, $lang);

        if (is_dir($this->path_mail.$lang)) {
            $files = glob($mailDir);

            if ($files) {
                $result = preg_replace('#.*/(\w*)\.html#', '$1', $files);

                if (is_array($result)) {
                    $mail_templates = array_unique($result);
                } else {
                    $pass = false;
                }
            } else {
                $pass = false;
            }
        } else {
            $pass = false;
        }

        // Optionnal Additionnal File
        if (is_dir($this->path_pdf)) {
            $files = glob($this->path_pdf.'*.pdf');

            if ($files) {
                $result = preg_replace('#.*/(\w*)#', '$1', $files);

                if (is_array($result)) {
                    $mail_add_files = array_unique($result);
                }
            }
        }

        $view_params['mail_templates'] = $mail_templates;
        $view_params['mail_add_files'] = $mail_add_files;

        $view_params['mail_invoice'] = array();
        $view_params['mail_invoice']['active'] = isset($mail_invoice['active']) ? $mail_invoice['active'] : false;
        $view_params['mail_invoice']['template'] = isset($mail_invoice['template']) ? $mail_invoice['template'] : null;
        $view_params['mail_invoice']['additionnal'] = isset($mail_invoice['additionnal']) ? $mail_invoice['additionnal'] : null;
        $view_params['mail_invoice']['order_state'] = isset($mail_invoice['order_state']) && $mail_invoice['order_state'] ? $mail_invoice['order_state'] : null;

        return ($view_params);
    }

    private function tabFilters()
    {
        $view_params = array();
        $view_params['images'] = $this->images;
        $view_params['url'] = $this->url;

        $selected_manufacturers = unserialize(self::decode(Configuration::get(self::KEY.'_FILTER_MANUFACTURERS')));
        $selected_suppliers = unserialize(self::decode(Configuration::get(self::KEY.'_FILTER_SUPPLIERS')));

        // Price Filtering
        //
        $price_filter = unserialize(self::decode(Configuration::get(self::KEY.'_PRICE_FILTER')));

        $view_params['prices'] = array();
        $view_params['prices']['currency_sign'] = Currency::getDefaultCurrency()->sign;
        $view_params['prices']['gt'] = null;
        $view_params['prices']['lt'] = null;

        if (is_array($price_filter) && isset($price_filter['gt']) && (float)$price_filter['gt']) {
            $view_params['prices']['gt'] = sprintf('%.02f', $price_filter['gt']);
        }

        if (is_array($price_filter) && isset($price_filter['lt']) && (float)$price_filter['lt']) {
            $view_params['prices']['lt'] = sprintf('%.02f', $price_filter['lt']);
        }


        //stock

        $view_params['stock'] = (int)Configuration::get(self::KEY.'_STOCK_FILTER');

        // Manufacturers Filtering
        //
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
        //
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

        return ($view_params);
    }

    public function tabCron()
    {
        $context_key = CDiscountContext::getKey($this->context->shop);

        $token = self::decode(Configuration::get(self::KEY.'_PS_TOKEN'));

        $view_params = array();
        $view_params['cronjobs'] = array();
        $view_params['cronjobs']['exists'] = is_dir(_PS_MODULE_DIR_.'cronjobs/');
        $view_params['cronjobs']['installed'] = (bool)CDiscountTools::moduleIsInstalled('cronjobs');


        $view_params['stdtypes'] = array('update', 'accept', 'import', 'status');

        $view_params['update']['title'] = $this->l('Offers Update');
        $view_params['update']['url'] = CommonTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/functions/products_update.php?action=cron&context_key='.$context_key.'&cdtoken='.$token;
        $view_params['update']['url_short'] = preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', CommonTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_)).'/'.$this->name.'/functions/products_update.php?...';
        $view_params['update']['frequency'] = -1;

        $view_params['accept']['title'] = $this->l('Orders - Acceptation');
        $view_params['accept']['url'] = CommonTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/functions/orders_accept.php?action=cron&context_key='.$context_key.'&cdtoken='.$token;
        $view_params['accept']['url_short'] = preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', CommonTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_)).'/'.$this->name.'/functions/orders_accept.php?...';
        $view_params['accept']['frequency'] = -1;

        $view_params['import']['title'] = $this->l('Orders - Import');
        $view_params['import']['url'] = CommonTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/functions/orders_import.php?action=cron&context_key='.$context_key.'&cdtoken='.$token;
        $view_params['import']['url_short'] = preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', CommonTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_)).'/'.$this->name.'/functions/orders_import.php?...';
        $view_params['import']['frequency'] = -1;

        if (Configuration::get(self::KEY.'_BULK_MODE')) {
            $view_params['bulk_mode'] = true;
        } else {
            $view_params['bulk_mode'] = false;
        }

        $view_params['status']['title'] = $this->l('Orders - Status');
        $view_params['status']['url'] = CommonTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/functions/orders_status.php?action=cron&context_key='.$context_key.'&cdtoken='.$token;
        $view_params['status']['url_short'] = preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', CommonTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_)).'/'.$this->name.'/functions/orders_status.php?...';
        $view_params['status']['frequency'] = 4;

        $view_params['multitenants'] = array();
        /* Obsolete
        if (isset(self::$seller_informations['Multichannel']) && self::$seller_informations['Multichannel']['Multitenant']) {
            $multitenants = self::multitenantGetList();

            if (is_array($multitenants) && count($multitenants)) {
                $view_params['multitenant'] = array();

                foreach ($multitenants as $multitenant) {
                    if (isset($multitenant['Checked']) && $multitenant['Checked'] && $multitenant['Id'] != 1) {
                        $view_params['multitenants'][] = array(
                            'Id' => $multitenant['Id'],
                            'Description' => $multitenant['Description'],
                            'title' => sprintf('%s (%s)', $this->l('Multitenant Offers'), $multitenant['Description']),
                            'frequency' => -1,
                            'url' => CommonTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/functions/products_update.php?action=cron&channel='.$multitenant['Id'].'&context_key='.$context_key.'&cdtoken='.$token,
                            'url_short' => preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', CommonTools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_)).'/'.$this->name.'/functions/products_update.php?action=cron&channel='.$multitenant['Id'].'...'
                        );
                    }
                }
            }
        }
        */
        return ($view_params);
    }

    public function tabMultitenant()
    {
        $view_params = array();
        $view_params['offerpool'] = null;
        $view_params['display'] = false;
        $status = null;

        if (!CDiscountTools::currentToken()) {
            $status = 'auth';
        } else {
            $sellerinformations = self::$seller_informations;

            if (!is_array($sellerinformations)) {
                $status = 'failed';
            } else {
                $multitenant = $this->multitenantGetList();

                if (count($multitenant) > 1) {
                    $view_params['display'] = true;
                }

                $view_params['offerpool'] = $multitenant;
            }
        }

        $view_params['status'] = $status;

        return ($view_params);
    }


    private function selectedTab()
    {
        return ((Tools::getValue('selected_tab') ? Tools::getValue('selected_tab') : ('menu-'.self::MODULE)));
    }


    private function autoAddCSS($url, $media = 'all')
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            return ($this->context->controller->addCSS($url, $media) && '');
        } else {
            return (sprintf('<link rel="stylesheet" type="text/css" href="%s">', $url));
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
            return (sprintf('<script type="text/javascript" src="%s"></script>', $url));
        }
    }

    public function hookBackOfficeHeader($params)
    {
        return ($this->hookDisplayBackOfficeHeader($params));
    }

    private function manageInvoiceOrderState($params)
    {
        $id_order = (int)$params['id_order'];

        // Mail/Invoice is Active ?
        //
        $mail_invoice = unserialize(CDiscountTools::decode(Configuration::get('CDISCOUNT_MESSAGING')));

        if (!isset($mail_invoice['active']) || !(int)$mail_invoice['active'] || !isset($mail_invoice['order_state'])) {
            return (false);
        }

        $order = new Order($id_order);

        if (!Validate::isLoadedObject($order)) {
            return (false);
        }

        // Not a cdiscount order
        //
        if (Tools::strtolower($order->module) != Tools::strtolower($this->name)) {
            return (false);
        }

        $debug = (bool)Configuration::get('CDISCOUNT_DEBUG');

        // Matching Order Status
        //
        if ($params['newOrderStatus']->id != (int)$mail_invoice['order_state']) {
            return (false);
        }

        // Starting Mail/Invoice sending
        //
        require_once(dirname(__FILE__).'/classes/cdiscount.messaging.class.php');

        $messaging = new CDiscountMessaging($debug);
        $result = $messaging->sendInvoice($id_order);

        if ($debug) {
            if (!$result) {
                printf('%s:#%d CDiscountMessaging::sendInvoice(%d) failed'."<br />\n", basename(__FILE__), __LINE__, $id_order);
            } else {
                printf('%s:#%d CDiscountMessaging::sendInvoice(%d) successfully done: %s'."<br />\n", basename(__FILE__), __LINE__, $id_order, print_r($result, true));
            }
            die;
        }

        return (false);
    }

    public function hookDisplaybackOfficeHeader($params)
    {
        $html = null;
        $cdiscountTab = null;
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $context_param = null;
            $cdiscountTab = Tools::strtolower(Tools::getValue('tab')) == 'admincatalog';
        } else {
            return(null);
        }

        if ($cdiscountTab && Tools::getValue('id_product') && (Tools::getValue('addproduct') !== false || Tools::getValue('updateproduct') !== false)) {
            $html .= '<meta name="'.self::MODULE.'-options" content="'.$this->url.'functions/product_options.php" />'."\n";
            $html .= '<meta name="'.self::MODULE.'-options-json" content="'.$this->url.'functions/product_options_action.php" />'."\n";

            $html .= $this->autoAddCSS($this->url.'views/css/product_ext.css');
            $html .= $this->autoAddJS($this->url.'views/js/product_options.js');
        }

        if (version_compare(_PS_VERSION_, '1.4', '<')) {
            print($html);
        } else {
            return ($html);
        }
    }

    public function hookUpdateQuantity($params)
    {
        return ($this->hookActionUpdateQuantity($params));
    }

    public function hookActionUpdateQuantity($params)
    {
        require_once(dirname(__FILE__).'/classes/'.self::MODULE.'.product.class.php');

        if (isset($params['product']) && is_object($params['product'])) {
            $id_product = (int)$params['product']->id;
        } elseif (isset($params['id_product'])) {
            $id_product = $params['id_product'];
        } elseif (isset($params['product']['id_product'])) {
            $id_product = (int)$params['product']['id_product'];
        } else {
            return(false);
        }

        CDiscountProduct::updateProductDate($id_product);
    }

    public function hookUpdateProduct($params)
    {
        return ($this->hookActionUpdateQuantity($params));
    }

    public function hookAdminOrder($params)
    {
        return ($this->hookDisplayAdminOrder($params));
    }

    public function hookDisplayAdminOrder($params)
    {
        require_once(_PS_MODULE_DIR_.$this->name.'/classes/'.self::MODULE.'.admin_order.class.php');

        $adminOrder = new CDiscountAdminOrder();
        $this->_html = $adminOrder->marketplaceOrderDisplay($params);

        return ($this->_html);
    }

    public function hookUpdateOrderStatus($params)
    {
        return ($this->hookActionOrderStatusUpdate($params));
    }

    public function hookActionOrderStatusUpdate($params)
    {
        require_once(_PS_MODULE_DIR_.$this->name.'/classes/'.self::MODULE.'.admin_order.class.php');

        $adminOrder = new CDiscountAdminOrder();
        $this->_html = $adminOrder->marketplaceHookActionOrderStatusUpdate($params);

        $this->manageInvoiceOrderState($params);
    }

    public function hookUpdateCarrier($params)
    {
        return ($this->hookActionCarrierUpdate($params));
    }

    public function hookActionCarrierUpdate($params)
    {
        $cd_carriers = unserialize(self::decode(Configuration::get(self::KEY.'_CARRIERS_INFO')));

        if ($cd_carriers && is_array($cd_carriers)) {
            foreach ($cd_carriers as $method => $id_carrier) {
                if ($cd_carriers[$method] == $params['id_carrier']) {
                    $cd_carriers[$method] = $params['carrier']->id;
                }
            }
            Configuration::updateValue(self::KEY.'_CARRIERS_INFO', self::encode(serialize($cd_carriers)));
        }
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        require_once(_PS_MODULE_DIR_.$this->name.'/classes/cdiscount.product_tab.class.php');

        $adminProductTab = new CDiscountExtProduct();

        $html = $adminProductTab->marketplaceProductTabContent($params);

        if (Tools::strlen($html)) {
            return ($html);
        } else {
            return ('<br />');
        } // Prevents error: "A server error occurred while loading the tabs: some tabs could not be loaded."
    }

    /**
     * Show marketplace order id in Admin Orders.
     * Same code for all our modules. Also modify others if change (amazon, cdiscount)
     * @param $params
     */
    public function hookActionAdminOrdersListingFieldsModifier($params)
    {
        $this->hookManager->actionAdminOrdersListingFieldsModifier($params);
    }


    public function install()
    {
        foreach ($this->_config as $key => $value) {
            if ($value == null) {
                continue;
            }
        }

        if (!Configuration::updateValue($key, (is_array($value) ? self::encode(serialize($value)) : self::encode($value)))) {
            ConfigureMessage::error(sprintf('%s - key: %s, value: %s', $this->l('Unable to install : Some configuration values'), $key, nl2br(print_r($value, true))));
            $pass = false;
        }
        Configuration::updateValue(self::KEY.'_PS_TOKEN', self::encode(md5(time() + rand())));

        $pass = true;

        foreach ($this->_vars as $var) {
            Configuration::updateValue($var['configuration'], (isset($var['default']) && !empty($var['default']) ? $var['default'] : ''));
        }

        Configuration::updateValue(self::KEY.'_TOKEN', '');

        if (!parent::install()) {
            ConfigureMessage::error($this->l('Unable to install: parent()')) && $pass = false;
        }

        // Install Hooks
        //
        $this->hookSetup(self::ADD);

        // Install Tabs
        //
        $this->tabSetup(self::ADD);

        if (!$this->addMarketPlaceTables()) {
            ConfigureMessage::error($this->l('Unable to install: _addMarketPlaceTables()')) && $pass = false;
        }

        if (!$this->addConfigurationTable()) {
            ConfigureMessage::error($this->l('Unable to install: _addConfigurationTable()')) && $pass = false;
        }

        if (!$this->createCustomer()) {
            ConfigureMessage::error($this->l('Unable to install: _createCustomer')) && $pass = false;
        }

        if ($pass) {
            require_once(dirname(__FILE__).'/classes/'.self::MODULE.'.context.class.php');
            $pass = CDiscountContext::save($this->context);
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
        //
        $this->tabSetup(self::REMOVE);

        // UnInstall Hooks
        //
        $this->hookSetup(self::REMOVE);


        if (!$this->removeMarketPlaceTables()) {
            ConfigureMessage::error($this->l('Unable to uninstall: MarketPlace Tables')) && $pass = false;
        }

        if (!$this->deleteCustomer()) {
            ConfigureMessage::error($this->l('Unable to install: _deleteCustomer')) && $pass = false;
        }

        foreach ($this->_vars as $var) {
            Configuration::deleteByName($var['configuration']);
        }

        foreach ($this->_config as $key => $value) {
            if (!Configuration::deleteByName($key)) {
                $pass = $pass && false;
            }
        }

        return ($pass);
    }

    private function hookSetup($action)
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            //
            $expectedHooks = array(
                'backOfficeHeader',
                'updateQuantity',
                'updateProduct',
                'adminOrder',
                'updateOrderStatus',
                'updateCarrier',
            );
        } else {
            //
            $expectedHooks = array(
                'displayBackOfficeHeader',
                'actionUpdateQuantity',
                'displayAdminOrder',
                'actionOrderStatusUpdate',
                'actionCarrierUpdate',
                'displayAdminProductsExtra',
                'actionAdminOrdersListingFieldsModifier',
            );
        }
        // GDPR compliance
        $expectedHooks = array_merge($expectedHooks, array(
            'registerGDPRConsent',
            'actionDeleteGDPRCustomer',
            'actionExportGDPRData'
        ));

        $pass = true;


        if (!$this->registerHook('adminOrder')) {
            ConfigureMessage::error($this->l('Unable to install: adminOrder')) && $pass = false;
        }

        if (!$this->registerHook('UpdateOrderStatus')) {
            ConfigureMessage::error($this->l('Unable to install: UpdateOrderStatus')) && $pass = false;
        }

        if ($action == self::ADD) {
            foreach ($expectedHooks as $expectedHook) {
                if (!$this->isRegisteredInHook($expectedHook)) {
                    if (!$this->registerHook($expectedHook)) {
                        ConfigureMessage::error($this->l('Unable to Register Hook').':'.$expectedHook) && $pass = false;
                    }
                }
            }
        }

        if ($action == self::REMOVE) {
            foreach ($expectedHooks as $expectedHook) {
                if ($this->isRegisteredInHook($expectedHook)) {
                    if (!$this->unregisterHook($expectedHook)) {
                        ConfigureMessage::error($this->l('Unable to Unregister Hook')).':'.$expectedHook && $pass = false;
                    }
                }
            }
        }

        return ($pass);
    }

    public function isRegisteredInHook($hook)
    {
        if (method_exists('Module', 'isRegisteredInHook')) {
            return (parent::isRegisteredInHook($hook));
        } else {
            return Db::getInstance()->getValue('
                        SELECT COUNT(*)
                        FROM `'._DB_PREFIX_.'hook_module` hm
                        LEFT JOIN `'._DB_PREFIX_.'hook` h ON (h.`id_hook` = hm.`id_hook`)
                        WHERE h.`name` = \''.pSQL($hook).'\'
                        AND hm.`id_module` = '.(int)$this->id);
        }
    }

    public static function encode($configuration)
    {
        return base64_encode($configuration);   // TODO Validation: Configuration requirement
    }

    public function tabSetup($action)
    {
        $adminOrders = version_compare(_PS_VERSION_, '1.7', '>=') ? 'AdminParentOrders'  : 'AdminOrders';

        require_once(dirname(__FILE__).'/classes/shared/tab.class.php');
        $pass = true;

        // Adding Tab
        switch ($action) {
            case self::ADD:
            case self::UPDATE:
                if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                    if (!Tab::getIdFromClassName('Admin'.self::NAME.'Products')) {
                        $pass = $pass && CommonServicesTab::Setup($action, 'Admin'.self::NAME.'Products', $this->displayName, 'AdminCatalog');
                    }
                    if (!Tab::getIdFromClassName('Admin'.self::NAME.'Orders')) {
                        $pass = $pass && CommonServicesTab::Setup($action, 'Admin'.self::NAME.'Orders', $this->displayName, $adminOrders);
                    }
                } else {
                    $pass = $pass && CommonServicesTab::Setup($action, 'Products'.self::NAME, $this->displayName, 'AdminCatalog');
                    $pass = $pass && CommonServicesTab::Setup($action, 'Orders'.self::NAME, $this->displayName, $adminOrders);
                }
                break;
            case self::REMOVE:
                if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                    $pass = $pass && CommonServicesTab::Setup($action, 'Admin'.self::NAME.'Products', $this->displayName, 'AdminCatalog');
                    $pass = $pass && CommonServicesTab::Setup($action, 'Admin'.self::NAME.'Orders', $this->displayName, $adminOrders);
                } else {
                    $pass = $pass && CommonServicesTab::Setup($action, 'Products'.self::NAME, $this->displayName, 'AdminCatalog');
                    $pass = $pass && CommonServicesTab::Setup($action, 'Orders'.self::NAME, $this->displayName, $adminOrders);
                }
                break;
            default:
                return (false);
        }

        return ($pass);
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

    /**
     * Parse customer input to email and name
     * @param $customer
     * @return array
     */
    private function _extractCustomerDataForGDPR($customer)
    {
        $email = $first_name = $last_name = '';
        if (is_string($customer)) {
            // This is email
            $email = $customer;
        } else if (is_array($customer)) {
            // Customer data in array
            if (isset($customer['email'])) {
                $email = $customer['email'];
            }
            if (isset($customer['firstname'])) {
                $first_name = $customer['firstname'];
            }
            if (isset($customer['lastname'])) {
                $last_name = $customer['lastname'];
            }
        }

        $email  = trim($email);

        if ($first_name && $last_name && Tools::strlen($first_name) && Tools::strlen($last_name)) {
            $name = array(trim($first_name.' '.$last_name), trim($last_name.' '.$first_name));
        } else {
            $name = trim($first_name.' '.$last_name);
        }

        return array('email' => $email, 'name' => $name);
    }

    /**
     * Add cdiscount_configuration table
     * @return bool
     */
    private function addConfigurationTable()
    {
        $config_table      = _DB_PREFIX_ . CDiscountConfiguration::$configuration_table;
        $config_lang_table = $config_table . '_lang';
        $result = true;

        if (! CommonTools::tableExists($config_table)) {
            $sql = "CREATE TABLE `{$config_table}` (
                    `id_configuration` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_shop_group` INT(11) UNSIGNED DEFAULT NULL,
                    `id_shop` INT(11) UNSIGNED DEFAULT NULL,
                    `name` VARCHAR(254) NOT NULL,
                    `value` MEDIUMBLOB,
                    `date_add` DATETIME NOT NULL,
                    `date_upd` DATETIME NOT NULL,
                    PRIMARY KEY (`id_configuration`),
                    KEY `name` (`name`),
                    KEY `id_shop` (`id_shop`),
                    KEY `id_shop_group` (`id_shop_group`)
                  ) ENGINE=INNODB DEFAULT CHARSET=utf8;";

            $result &= Db::getInstance()->execute($sql);
        }

        if (! CommonTools::tableExists($config_lang_table)) {
            $sql = "CREATE TABLE `{$config_lang_table}` (
                  `id_configuration` int(10) unsigned NOT NULL,
                  `id_lang` int(10) unsigned NOT NULL,
                  `value` text,
                  `date_upd` datetime DEFAULT NULL,
                  PRIMARY KEY (`id_configuration`,`id_lang`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

            $result &= Db::getInstance()->execute($sql);
        }

        return $result;
    }

    private function addMarketPlaceTables()
    {
        $pass = true;

        if (!CommonTools::tableExists(_DB_PREFIX_.'marketplace_configuration')) {
            $sql
            = '
                    CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'marketplace_configuration` (
                    `marketplace` VARCHAR( 32 ) NULL DEFAULT NULL ,
                    `configuration` VARCHAR( 32 ) NULL DEFAULT NULL ,
                    `value` LONGTEXT NOT NULL,
                    UNIQUE KEY `configuration` (`marketplace` ,`configuration`)
                    ) ;';

            if (!Db::getInstance()->Execute($sql)) {
                ConfigureMessage::error(sprintf('Failed to create table %s', 'marketplace_configuration'));
                $pass = false;
            }
        }
        if (CommonTools::tableExists(_DB_PREFIX_.self::TABLE_PRODUCT_OPTION)) {
            $pass = true;
            $sqls = array();

            $fields = array();

            // Update - ADD new fields
            //
            $query = Db::getInstance()->ExecuteS('show columns from `'._DB_PREFIX_.self::TABLE_PRODUCT_OPTION.'`');
            foreach ($query as $row) {
                $fields[$row['Field']] = 1;
            }

            if (!isset($fields['shipping'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.self::TABLE_PRODUCT_OPTION.'` ADD  `shipping` VARCHAR(32) NULL DEFAULT NULL AFTER `price`';
            }

            if (!isset($fields['price_up'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.self::TABLE_PRODUCT_OPTION.'` ADD  `price_up` FLOAT NULL DEFAULT NULL AFTER `price`';
            }

            if (!isset($fields['price_down'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.self::TABLE_PRODUCT_OPTION.'` ADD  `price_down` FLOAT NULL DEFAULT NULL AFTER `price_up`';
            }

            if (!isset($fields['shipping_delay'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.self::TABLE_PRODUCT_OPTION.'` ADD  `shipping_delay` FLOAT NULL DEFAULT NULL AFTER `shipping`';
            }

            if (!isset($fields['clogistique'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.self::TABLE_PRODUCT_OPTION.'` ADD  `clogistique` TINYINT NULL DEFAULT NULL AFTER `shipping_delay`';
            }

            if (!isset($fields['valueadded'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.self::TABLE_PRODUCT_OPTION.'` ADD  `valueadded` FLOAT NULL DEFAULT NULL AFTER `clogistique`';
            }

            foreach ($sqls as $sql) {
                $pass = Db::getInstance()->Execute($sql) && $pass;
            }
            if (!$pass) {
                ConfigureMessage::error(sprintf('%s(%d): Create additionnal fields failed', basename(__FILE__), __LINE__));
            }
        } else {
            $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::TABLE_PRODUCT_OPTION.'` (
                  `id_product` INT NOT NULL ,
                  `id_lang` INT NOT NULL ,
                  `force` TINYINT NOT NULL DEFAULT  "0",
                  `disable` TINYINT NULL DEFAULT NULL,
                  `price` FLOAT NULL DEFAULT NULL,
                  `price_up` FLOAT NULL DEFAULT NULL,
                  `price_down` FLOAT NULL DEFAULT NULL,
                  `shipping` VARCHAR(32) NULL DEFAULT NULL,
                  `shipping_delay` FLOAT NULL DEFAULT NULL,
                  `clogistique` TINYINT NOT NULL DEFAULT  "0",
                  `valueadded` FLOAT NULL DEFAULT NULL,
                  `text` VARCHAR(128) NULL DEFAULT NULL,
                   UNIQUE KEY `id_product` (`id_product`,`id_lang`)
                  ) ;';

            if (!Db::getInstance()->Execute($sql)) {
                ConfigureMessage::error(sprintf('%s(%d): Create table %s failed', basename(__FILE__), __LINE__), self::TABLE_PRODUCT_OPTION);
                $pass = false;
            }
        }


        if (!CommonTools::tableExists(_DB_PREFIX_.self::TABLE_CDISCOUNT_OFFERS)) {
            $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::TABLE_CDISCOUNT_OFFERS.'` (
                        `sku` VARCHAR(32),
                        `cdiscount_sku` VARCHAR(32),
                        `date` datetime DEFAULT NULL,
                        UNIQUE KEY (`sku`)
					) ;';

            if (!Db::getInstance()->Execute($sql)) {
                ConfigureMessage::error(sprintf('%s(%d): Create table %s failed', basename(__FILE__), __LINE__), self::TABLE_CDISCOUNT_OFFERS);
                $pass = false;
            }
        }

        if (!$this->dbManager->addMarketplaceTables()) {
            ConfigureMessage::error($this->dbManager->getErrors());
            $pass = false;
        }

        return ($pass);
    }

    private function createCustomer()
    {
        if (($id_customer = (int)Configuration::get(self::KEY.'_CUSTOMER_ID'))) {
            $customer = new Customer($id_customer);

            if (Validate::isLoadedObject($customer)) {
                return ($customer->id);
            }
        }
        $var = explode('@', Configuration::get('PS_SHOP_EMAIL'));
        $max = 10;

        while ($max--) {
            $email = 'no-reply-'.rand(11111111, 9999999).'@'.$var[1];

            if (!Validate::isEmail($email)) {
                return(false);
            }

            if (!Customer::customerExists($email)) {
                $customer = new Customer();
                $customer->firstname = self::NAME;
                $customer->lastname = self::NAME;
                $customer->email = $email;
                $customer->id_gender = 1;
                $customer->birthday = '1970-01-01';
                $customer->newsletter = false;
                $customer->optin = false;
                $customer->passwd = md5(rand(1111111111, 9999999999));
                $customer->active = true;
                $customer->date_add = date('Y-m-d H:i:s');
                $customer->date_upd = $customer->date_add;
                $customer->add();

                if (Validate::isLoadedObject($customer)) {
                    Configuration::updateValue(self::KEY.'_CUSTOMER_ID', $customer->id);
                    return($customer->id);
                } else {
                    return(false);
                }
            }
        }
        return (false);
    }

    private function removeMarketPlaceTables()
    {
        $pass = true;

        // Check if exists
        //
        $tables = array();
        $query = Db::getInstance()->ExecuteS('show tables');
        foreach ($query as $rows) {
            foreach ($rows as $table) {
                $tables[$table] = 1;
            }
        }

        if (isset($tables[_DB_PREFIX_.self::TABLE_PRODUCT_OPTION])) {
            $sql = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.self::TABLE_PRODUCT_OPTION.'` ; ';

            if (!Db::getInstance()->Execute($sql)) {
                $pass = false;
            }
        }

        return ($pass);
    }

    private function deleteCustomer()
    {
        $customer = new Customer();
        $customer->id = Configuration::get(self::KEY.'_CUSTOMER_ID');

        return ($customer->delete());
    }

    public function loadProfiles()
    {
        if (!count(self::$profiles)) {
            if (version_compare($this->version, '4.2', '<')) {
                // Load old config
                $profiles = CDiscountConfiguration::get('profiles');
                if (!is_array($profiles) || !count($profiles) || !array_key_exists('name', $profiles) || !is_array($profiles['name'])) {
                    $profiles = array('name' => array());
                }
            } else {
                // Load new config
                $profiles = CDiscountConfiguration::get(CDiscountConstant::CONFIG_PROFILES);
                if (!is_array($profiles) || !count($profiles)) {
                    $profiles = array('name' => array());
                }
            }
            self::$profiles = $profiles;
        }

        return self::$profiles;
    }

    public function unloadProfiles()
    {
        self::$profiles = array();
        return $this;
    }

    public function loadModels()
    {
        if (!count(self::$models)) {
            if (version_compare($this->version, '4.4', '>=')) {
                $configKey = CDiscountConstant::CONFIG_MODELS;
            } elseif (version_compare($this->version, '4.2', '>=')) {
                $configKey = 'models_2020';
            } else {
                $configKey = 'models';
            }

            $models = CDiscountConfiguration::get($configKey);
            if (!is_array($models) || !count($models)) {
                $models = array();
            }

            self::$models = $models;
        }

        return self::$models;
    }

    // Refresh cache
    public function unLoadModels()
    {
        self::$models = array();
        return $this;
    }

    public function loadSpecificFields()
    {
        if (!count(self::$specificFields)) {
            self::$specificFields = CDiscountConfiguration::get(CDiscountConstant::CONFIG_SPECIFIC_FIELDS);
        }

        return self::$specificFields;
    }

    public function unloadSpecificFields()
    {
        self::$specificFields = array();
        return $this;
    }

    public static function getInstanceCDiscountModel()
    {
        if (!self::$cdModel) {
            $username = Configuration::get(Cdiscount::KEY.'_USERNAME');
            $password = Configuration::get(Cdiscount::KEY.'_PASSWORD');
            $production = !Configuration::get(Cdiscount::KEY.'_PREPRODUCTION');
            self::$cdModel = CDiscountModel::getInstance($username, $password, $production, self::$debug_mode);
        }

        return self::$cdModel;
    }

    public function getCategories()
    {
        if (is_null($this->categories)) {
            $this->categories = Category::getCategories((int)$this->id_lang, false);
        }

        return $this->categories;
    }

    public function getUniverseOptions()
    {
        require_once(dirname(__FILE__) . '/classes/cdiscount.categories.class.php');
        $universeOptions = array();

        $universes = CDiscountCategories::loadUniverses();
        foreach ($universes as $cd_category) {
            $universeOptions[] = array('value' => $cd_category, 'desc' => $cd_category);
        }

        return $universeOptions;
    }

    public function viewParamsAlertClass()
    {
        return array(
            'danger' => $this->ps16x ? 'alert alert-danger' : 'error',
            'warning' => $this->ps16x ? 'alert alert-warning' : 'warn',
            'success' => $this->ps16x ? 'alert alert-success' : 'conf',
            'info' => $this->ps16x ? 'alert alert-info' : 'info',
        );
    }

    protected function edd()
    {
        if ($this->debug) {
            $backTrace = debug_backtrace();
            $callerStack = array();
            for ($i = 1; $i <= 3; $i++) {   // Get 3 back trace
                $caller = array_shift($backTrace);
                $fileSegment = explode(DIRECTORY_SEPARATOR, $caller['file']);
                $file = array_pop($fileSegment);
                $callerStack[] = sprintf('%s(#%d)', $file, $caller['line']);
            }

            $callerStackStr = implode(' - ', $callerStack) . ': ';
            foreach (func_get_args() as $arg) {
                CDiscountToolsR::p($callerStackStr . print_r($arg, true));
            }
        }
    }
}
