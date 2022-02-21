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

require_once(dirname(__FILE__).'/env.php');
require_once(dirname(__FILE__).'/../cdiscount.php');

require_once(dirname(__FILE__).'/../classes/cdiscount.tools.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.configuration.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.product.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.config.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.webservice.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.context.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.batch.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.support.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.zip.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.logger.class.php');

require_once(dirname(__FILE__).'/../common/tools.class.php');
require_once(dirname(__FILE__).'/../common/configuration.class.php');

class CDiscountExportProducts extends CDiscount
{
    const DISCOUNT_SALE = 3;
    /*const DISCOUNT_FLASH = 2 ;*/

    const LF = "\n";
    const CRLF = "\r\n";
    const OFFERS_FILE = 'Offers.xml';

    const GAP_MIN_FOR_ALIGNMENT = 0.98;

    public $directory;
    public $export;

    private $_folder = 'update';
    private $xml_dir = 'update';

    private $errors = array();
    private $_cr    = Cdiscount::LF;

    private static $logContent = '';

    public function __construct()
    {
        parent::__construct();

        CDiscountContext::restore($this->context);

        $this->ps_images = 'http://'.htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'img/p/';

        $this->directory = realpath(basename(__FILE__.'/../'));
        $this->export = $this->directory.'/'.'export';

        $this->export_url = $this->url.'functions/downloadxml.php?filename=';
        $this->zip_url = $this->url.'functions/downloadzip.php?filename=';
        $this->pickup_url = 'http://'.CommonTools::getHttpHost(false, true).$this->url.'export/';

        $this->zipfile = CDiscountTools::getFriendlyUrl(Configuration::get('PS_SHOP_NAME')).'-update.zip';

        $this->clogistique = (bool)Configuration::get(parent::KEY.'_CLOGISTIQUE');
        $this->dev_mode = (bool)Configuration::get(parent::KEY.'_DEV_MODE');
    }

    public function dispatch()
    {
        $cdtoken = Tools::getValue('cdtoken');
        $action = Tools::getValue('action');

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            if (Shop::isFeatureActive()) {
                $id_shop = (int)Validate::isLoadedObject($this->context->shop) && (int)$this->context->shop->id ? $this->context->shop->id : 1;

                Shop::setContext(Shop::CONTEXT_SHOP, $id_shop);
            }
        }

        //  Check Access Tokens
        //
        $token = parent::decode(Configuration::get(parent::KEY.'_PS_TOKEN'));

        if ($cdtoken != $token) {
            $this->dieOnError($this->l('Wrong Token'));
        }

        switch ($action) {
            case 'export':
                $this->productUpdate();
                break;
            case 'history':
                $this->history();
                break;
            case 'report':
                $this->getReport();
                break;
            case 'cron':
                echo $this->productUpdate(true);
                break;
        }
    }

    private function dieOnError($msg)
    {
        echo $msg;
        $output = ob_get_clean().$this->_cr;
        $json = Tools::jsonEncode(array('error' => true, 'msg' => $output));
        // jQuery Output or PHP Output
        //
        if (($callback = Tools::getValue('callback'))) {
            // jquery

            echo (string)$callback.'('.$json.')';
        } else {
            // cron

            return ($json);
        }
        die;
    }

    public static function logExit()
    {
        if (!empty(self::$logContent)) {
            $logContent = Tools::getValue('callback') ? 'Manual update'.self::LF : 'Cron'.self::LF;
            $logContent .= self::getLogContent();

            $logger = new CdiscountLogger(CdiscountLogger::CHANNEL_PRODUCT_UPDATE);
            $logger->debug($logContent);
        }
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    private function productUpdate($cron = false)
    {
        $panic = false;
        $error = false;
        $count = $p = 0;
        $updated = $deleted = 0;
        $history = array();
        $product_errors = array();
        $sku_history = array();
        $msg = null;
        $timestart = time();
        $duplicateAlongCategories = array();

        $region = 'fr';
        $mpSupport = new CDiscountSupport($this->id_lang);

        // Force French
        $id_lang = Language::getIdByIso($region);
        $id_country = (int)Country::getByIso($region);

        if (!is_numeric($id_lang) || !(int)$id_lang) {
            die('<b>CDiscount is a french marketplace, it requires to have the french language installed</b>');
        }

        if (!is_numeric($id_country) || !(int)$id_country) {
            die('<b>CDiscount is a french marketplace, it requires to have the french country active</b>');
        }

        $toMarketplace = array();

        $id_currency = Configuration::get('PS_CURRENCY_DEFAULT');
        $stock_management = (bool)Configuration::get('PS_STOCK_MANAGEMENT');
        $id_country_default = (int)Configuration::get('PS_COUNTRY_DEFAULT');

        $cr = $this->_cr;

        ob_start();

        register_shutdown_function(array('CDiscountExportProducts', 'logExit'));

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $this->context->currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
            $this->context->customer->is_guest = true;

            $this->context->cart = new Cart();

            if (Shop::isFeatureActive()) {
                $id_shop = (int)Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
            } else {
                $id_shop = 1;
            }

            $id_warehouse = (int)Configuration::get(parent::KEY.'_WAREHOUSE');
        } else {
            $id_shop = null;
        }

        $target_currency_id = CurrencyCore::getIdByIsoCode('EUR');
        $target_currency = new Currency($target_currency_id);

        if (!Validate::isLoadedObject($target_currency)) {
            $this->errors[] = sprintf('%s(%d): %s', basename(__FILE__), __LINE__, $this->l('CDiscount is an European marketplace, EUR currency must be available in Localization > Currencies')).$cr;
            $error = true;
        }

        if ($target_currency_id != $id_currency) {
            $convert_currency = true;
        } else {
            $convert_currency = false;
        }


        $create_active = false;
        $create_in_stock = false;
        $all_offers = false;
        $dont_send = false;

        $date_param = Tools::getValue('since');

        if (Validate::isDate($date_param)) {
            $since = date('Y-m-d H:i:s', strtotime($date_param));
        } else {
            $since = null;
        }

        // Parameters
        //
        $channel = Tools::getValue('channel', 1);

        if (Tools::getValue('all-offers')) {
            $all_offers = true;
        }

        if (Tools::getValue('do-not-send')) {
            $dont_send = true;
        }

        if (Tools::getValue('purge-replace')) {
            $purgeAndReplace = true;
            $create_active = true;
        } else {
            $purgeAndReplace = false;
        }

        /*
         * Last Package
         */
        $batches = new CDiscountBatches($cron ? parent::KEY.'_BATCH_UPDATE_CRON' : parent::KEY.'_BATCH_UPDATE');

        $last_update = $batches->getLastForChannel(CdiscountConfig::OFFER_POOL_CDISCOUNT);

        if ($cron) {
            if (Tools::getValue('force')) {
                $purgeAndReplace = true;
                $create_active = true;
                $create_in_stock = true;
            }
            if ($since && Validate::isDate($since)) {
                $date_from = $since;
            } elseif (!Validate::isDate($last_update)) {
                $date_from = null;
            } else {
                $date_from = $last_update;
            }
        } elseif ($all_offers) {
            $date_from = null;
        } else {
            if ($since && Validate::isDate($since)) {
                $date_from = $since;
            } elseif (Validate::isDate($last_update)) {
                $date_from = $last_update;
            } else {
                $create_active = true;
                $create_in_stock = true;

                $date_from = CDiscountTools::oldest();
            }
        }

        if (!$dont_send && $last_update && strtotime($last_update) > time() - (5 * 60)) {
            $this->errors[] = sprintf('%s(%d): %s', basename(__FILE__), __LINE__, $this->l('Export has been done already, please wait at least 5 minutes between submissions')).$cr;
            $error = true;
        }

        // Categories Settings
        $default_categories = CDiscountConfiguration::get('categories');

        if (!is_array($default_categories) || !max($default_categories)) {
            $this->errors[] = sprintf('%s(%d): %s', basename(__FILE__), __LINE__, $this->l('You must configure the categories to update')).$cr;
            $error = true;
        }
        $oos = Configuration::get(parent::KEY.'_ALLOW_OOS') ? true : false;

        $default_profiles = $this->loadProfiles();
        $default_profiles2categories = CDiscountConfiguration::get('profiles_categories');

        $import_type = Configuration::get(parent::KEY.'_IMPORT_TYPE');
        $import_type = ($import_type ? $import_type : Cdiscount::IMPORT_BY_ID);

        // Prices Parameters
        //
        $useTaxes = (bool)Configuration::get(parent::KEY.'_USE_TAXES');
        $useSpecials = (bool)Configuration::get(parent::KEY.'_USE_SPECIALS');

        // Price Filter

        $params = unserialize(parent::decode(Configuration::get(parent::KEY.'_PRICE_FILTER')));
        $priceFilter = is_array($params) && count($params) ? $params : array();

        //Filter Stock
        //
        $stockMinFilter = (int)Configuration::get(parent::KEY.'_STOCK_FILTER');


        $formulaOnSpecials = (bool)Configuration::get(parent::KEY.'_FORMULA_ON_SPECIALS');
        $on_sale_period = Configuration::get(self::KEY.'_ON_SALE_PERIOD');

        // workarround to make true as a default
        if ($on_sale_period === false) {
            $on_sale_period = true;
        } elseif ($on_sale_period == false) {
            $on_sale_period = false;
        } else {
            $on_sale_period = true;
        }

        // Weight Unit
        switch (Tools::strtolower(Configuration::get('PS_WEIGHT_UNIT'))) {
            case 'g':
            case 'gr':
                $weight_multiplicator = 1000;
                break;
            default:
                $weight_multiplicator = 1;
        }
        // Condition Map
        //
        $conditionMap = unserialize(parent::decode(Configuration::get(parent::KEY.'_CONDITION_MAP')));
        if (is_array($conditionMap)) {
            $conditionMap = array_flip($conditionMap);
        }

        // Delivery Delays
        //
        $preparationTime = Configuration::get(parent::KEY.'_PREPARATION_TIME');

        // Carriers
        //
        // Carrier mappings
        $carriers_info = unserialize(parent::decode(Configuration::get(parent::KEY.'_CARRIERS_INFO')));
        $carriers_params = unserialize(parent::decode(Configuration::get(parent::KEY.'_CARRIERS_PARAMS')));

        $freeshipping_disabled = (bool)Configuration::get(parent::KEY.'_IGNORE_FREE_SHIPPING');
        $freeshipping_weight = $freeshipping_disabled ? false : Configuration::get('PS_SHIPPING_FREE_WEIGHT');
        $freeshipping_price = $freeshipping_disabled ? false : Configuration::get('PS_SHIPPING_FREE_PRICE');

        // Repricing/Alignment
        $align_active = (bool)Configuration::get(parent::KEY.'_ALIGNMENT_ACTIVE');
        $smart_rounding = (bool)Configuration::get(parent::KEY.'_SMART_ROUNDING');

        // Exclusions
        //
        $excluded_manufacturers = unserialize(parent::decode(Configuration::get(parent::KEY.'_FILTER_MANUFACTURERS')));
        $excluded_suppliers = unserialize(parent::decode(Configuration::get(parent::KEY.'_FILTER_SUPPLIERS')));
        $product_filtered = array();
        $product_filtered['manufacturer'] = array();
        $product_filtered['supplier'] = array();

        //
        // Comments
        //
        $default_comments = Configuration::get(parent::KEY.'_DEFAULT_COMMENT');

        // EAN Policy
        $ean_policy = Configuration::get(parent::KEY.'_EAN_POLICY');

        if (!$ean_policy) {
            $this->errors[] = sprintf('%s(%s): %s'.$cr, basename(__FILE__), __LINE__, $this->l('You must configure your EAN policy in your module configuration'));
            $error = true;
        }

        // Configuration customer Group or default customer group

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_default_customer_group = Configuration::get('PS_CUSTOMER_GROUP');
        } else {
            $id_default_customer_group = (int)_PS_DEFAULT_CUSTOMER_GROUP_;
        }

        $id_customer = (int)Configuration::get(parent::KEY.'_CUSTOMER_ID');
        $id_customer_group = (int)Configuration::get(parent::KEY.'_CUSTOMER_GROUP');

        if ($id_customer) {
            $this->context->customer = new Customer($id_customer);
        }

        if (!Validate::isLoadedObject($this->context->customer)) {
            $this->context->customer = new Customer();
        }

        if ((int)$id_customer_group && is_numeric($id_customer_group)) {
            $group = new Group($id_customer_group);

            if (!Validate::isLoadedObject($group)) {
                $id_customer_group = $id_default_customer_group;
            }

            unset($group);
        } else {
            $id_customer_group = $id_default_customer_group;
        }

        $this->context->customer->id_default_group = $id_customer_group;
        $this->context->customer->is_guest = true;

        // Multitenant
        $multitenants_cfg = unserialize(parent::decode(Configuration::get(parent::KEY.'_MULTITENANT')));
        $multitenants = array();

        if (is_array($multitenants_cfg) && count($multitenants_cfg)) {
            foreach (array_keys($multitenants_cfg) as $channel) {
                $multitenants[] = $channel;
            }
        } else {
            $multitenants = array(CDiscountConfig::OFFER_POOL_CDISCOUNT);
        }

        // Collect merchant data from cache or webservice
        //
        self::$seller_informations = CDiscountConfig::getSellerInformation(Cdiscount::$debug_mode, true);

        if (Cdiscount::$debug_mode) {
            CommonTools::p('Seller:');
            CommonTools::p(self::$seller_informations);
        }

        // Excluding unavailable/unmapped carriers
        //
        if (is_array(self::$seller_informations) && array_key_exists('DeliveryModeInformation', self::$seller_informations) && count(self::$seller_informations['DeliveryModeInformation'])) {
            $carrier_availables = array();

            foreach (self::$seller_informations['DeliveryModeInformation'] as $carrier_available) {
                if (!is_array($carrier_available)) {
                    continue;
                }
                $carrier_code = $carrier_available['Code'];

                if (array_key_exists($carrier_code, CDiscount::$predefined_carriers)) {
                    $carrier_name = CDiscount::$predefined_carriers[$carrier_code];
                    $carrier_availables[$carrier_name] = $carrier_name;
                }
            }
            if (count($carrier_availables)) {
                $carriers_params = array_intersect_key($carriers_params, $carrier_availables);
                $carriers_info = array_intersect_key($carriers_info, $carrier_availables);
            }
        }

        if (Cdiscount::$debug_mode) {
            CommonTools::p('Carriers:');
            CommonTools::p($carriers_params);
            CommonTools::p($carriers_info);
        }
        // Path to XML
        //
        $output_dir = sprintf('%s/%s', $this->export, $this->_folder);

        // Files
        //
        $rel_dir = $output_dir.'/'.'_rels';
        $rel_file = $output_dir.'/'.'_rels'.'/'.'.rels';
        $cont_dir = $output_dir.'/'.'Content';
        $type_file = $output_dir.'/'.'[Content_Types].xml';
        $offers_file = $output_dir.'/'.'Content'.'/'.self::OFFERS_FILE;

        // ZIP
        //
        $zipfile = $this->zipfile;

        $from = array();
        $from[] = $rel_file;
        $from[] = $offers_file;
        $from[] = $type_file;

        if (Cdiscount::$debug_mode) {
            echo nl2br(print_r($from, true)).$cr;
        }

        if (!is_array($carriers_info) || !count($carriers_info)) {
            $this->errors[] = sprintf('%s(%s): %s'.$cr, basename(__FILE__), __LINE__, $this->l('Carrier configuration is incomplete - please configure your module...'));
            $error = true;
        }
        $addressMap = unserialize(parent::decode(Configuration::get(parent::KEY.'_ADDRESS_MAP')));

        if (!is_array($addressMap) || !isset($addressMap['fr'])) {
            $this->errors[] = sprintf('%s(%s): %s'.$cr, basename(__FILE__), __LINE__, $this->l('Configuration is incomplete - please save the configuration of your module...'));
            $error = true;
        } else {
            $id_address = (int)$addressMap['fr'];
        }

        // Check rights
        //
        if (!is_dir($output_dir) && !mkdir($output_dir)) {
            $this->errors[] = sprintf('%s(%s): %s(%s)', basename(__FILE__), __LINE__, $this->l('Unable to create the directory'), $output_dir).$cr;
            $error = true;
        }

        if (!CommonTools::isDirWriteable($output_dir)) {
            @chmod($output_dir, Cdiscount::PERMISSIONS_DIRECTORY);
        }

        if (!$error && !is_dir($rel_dir)) {
            if (!mkdir($rel_dir)) {
                $this->errors[] = sprintf('%s(%s): %s(%s)', basename(__FILE__), __LINE__, $this->l('Unable to create the directory'), $rel_dir).$cr;
                $error = true;
            }
            if (!CommonTools::isDirWriteable($rel_dir)) {
                $this->errors[] = sprintf('%s(%s): %s(%s)', basename(__FILE__), __LINE__, $this->l('Unwriteable directory'), $rel_dir).$cr;
                $error = true;
            }
        } elseif (file_exists($rel_file) && !is_writeable($rel_file)) {
            $this->errors[] = sprintf('%s(%d): %s(%s)', basename(__FILE__), __LINE__, $this->l('Unwriteable file'), $rel_file).$cr;
            $error = true;
        }

        if (!$error && !is_dir($cont_dir)) {
            if (!mkdir($cont_dir)) {
                $this->errors[] = sprintf('%s(%s): %s(%s)', basename(__FILE__), __LINE__, $this->l('Unable to create the directory'), $cont_dir).$cr;
                $error = true;
            }
            if (!CommonTools::isDirWriteable($cont_dir)) {
                $this->errors[] = sprintf('%s(%s): %s(%s)', basename(__FILE__), __LINE__, $this->l('Unwriteable directory'), $cont_dir).$cr;
                $error = true;
            }
        }

        if (!$xml = $this->createRelationships($rel_file)) {
            $this->errors[] = sprintf('%s(%d): %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable create relationships file'), $rel_file).$cr;
            $error = true;
        }

        if (!$xml = $this->createContentType($type_file)) {
            $this->errors[] = sprintf('%s(%d): %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable create content type file'), $type_file).$cr;
            $error = true;
        }

        if (!is_array($default_profiles2categories) || !max($default_profiles2categories)) {
            $this->errors[] = sprintf('%s(%d): %s', basename(__FILE__), __LINE__, $this->l('You must assign at least one profile to one category')).$cr;
            $error = true;
        }
        $profile2category = CDiscountConfiguration::get('profiles_categories');
        $ps_categories = CDiscountConfiguration::get('categories');

        // Export Loop
        //
        if (!$error && $default_categories) {
            foreach ($default_categories as $key => $id_category) {
                $category = new Category($id_category, $id_lang);

                if (!isset($default_profiles2categories[$id_category]) || !is_string($default_profiles2categories[$id_category])) {
                    $this->errors[] = sprintf('%s(%d): %s - %s', basename(__FILE__), __LINE__, $this->l('You must sets a profile for this category'), $category->name);
                    $error = true;
                    continue;
                }
                $profile = $default_profiles2categories[$id_category];

                $selected_profile = false;
                foreach ($default_profiles['name'] as $selected_profile => $profile_name) {
                    if ($profile_name === $profile) {
                        break;
                    }
                }

                if ($selected_profile === false) {
                    $this->errors[] = sprintf('%s(%d): %s', basename(__FILE__), __LINE__, $this->l('You must configure your profiles first'));
                    $error = true;
                    continue;
                }
                $profile_name = isset($default_profiles['name'][$selected_profile]) ? $default_profiles['name'][$selected_profile] : '';
                $profile_price_align = isset($default_profiles['price_align'][$selected_profile]) ? $default_profiles['price_align'][$selected_profile] : '';
                $profile_price_rule = isset($default_profiles['price_rule'][$selected_profile]) ? $default_profiles['price_rule'][$selected_profile] : false;

                $profile_shipping_rule = isset($default_profiles['shipping_rule'][$selected_profile]) && (float)$default_profiles['shipping_rule'][$selected_profile] ? $default_profiles['shipping_rule'][$selected_profile] : null;
                $profile_preparation_time = isset($default_profiles['preparation_time'][$selected_profile]) && (int)$default_profiles['preparation_time'][$selected_profile] ? $default_profiles['preparation_time'][$selected_profile] : null;
                $profile_shipping_free = isset($default_profiles['shipping_free'][$selected_profile]) && (int)$default_profiles['shipping_free'][$selected_profile] ? $default_profiles['shipping_free'][$selected_profile] : null;
                $profile_shipping_include = isset($default_profiles['shipping_include'][$selected_profile]) && (int)$default_profiles['shipping_include'][$selected_profile] ? (bool)$default_profiles['shipping_include'][$selected_profile] : false;
                $profile_shipping_include_percentage = isset($default_profiles['shipping_include_percentage'][$selected_profile]) && (int)$default_profiles['shipping_include_percentage'][$selected_profile] ? (int)$default_profiles['shipping_include_percentage'][$selected_profile] : null;
                $profile_shipping_include_limit = isset($default_profiles['shipping_include_limit'][$selected_profile]) && (int)$default_profiles['shipping_include_limit'][$selected_profile] ? (int)$default_profiles['shipping_include_limit'][$selected_profile] : null;

                $profile_cdav = isset($default_profiles['cdav'][$selected_profile]) ? (bool)$default_profiles['cdav'][$selected_profile] : false;
                $profile_cdav_max = isset($default_profiles['cdav_max'][$selected_profile]) ? (float)$default_profiles['cdav_max'][$selected_profile] : false;

                if (Cdiscount::$debug_mode) {
                    CommonTools::p('Profile Data:');
                    CommonTools::p(sprintf('- Name: %s', $profile_name));
                    CommonTools::p(sprintf('- Align: %s', $profile_price_align ? 'Yes' : 'No'));
                    CommonTools::p(sprintf('- Rules: %s', print_r($profile_price_rule)));
                    CommonTools::p('Shipping:');
                    CommonTools::p(sprintf('- Rule: %s', $profile_shipping_rule));
                    CommonTools::p(sprintf('- Preparation Time: %s', $profile_preparation_time));
                    CommonTools::p(sprintf('- Free: %s', print_r($profile_shipping_free, true)));
                    CommonTools::p(sprintf('- Include: %s', $profile_shipping_include ? 'Yes' : 'No'));
                    CommonTools::p(sprintf('- Percentage: %02d%%', $profile_shipping_include_percentage));
                    CommonTools::p(sprintf('- Limit: %.02f', $profile_shipping_include_limit));
                    CommonTools::p(sprintf('- CDaV: %s Max: %.02f', $profile_cdav ? 'Yes' : 'No', $profile_cdav_max));
                }

                $shipping_to_charge_in_product_price = 0;

                $channels = isset($default_profiles['multitenant'][$selected_profile]) ? $default_profiles['multitenant'][$selected_profile] : array();

                if ($purgeAndReplace) {
                    $products = CDiscountProduct::getExportProducts($id_category, $create_active, $create_in_stock, null, null, $this->context->shop->id, Cdiscount::$debug_mode);
                } else {
                    $products = CDiscountProduct::getUpdateProducts($id_category, $create_active, $create_in_stock, $date_from, $this->context->shop->id, Cdiscount::$debug_mode);
                }

                if (!count($products)) {
                    $category = new Category($id_category);

                    if (Cdiscount::$debug_mode) {
                        CommonTools::p('Category Data:');
                        CommonTools::p(get_object_vars($category));
                    }
                }

                if ($products) {
                    foreach ($products as $product) {
                        $id_product = $product['id_product'];

                        // Products with multiples categories ;
                        if (isset($duplicateAlongCategories[$id_product])) {
                            continue;
                        }
                        $duplicateAlongCategories[$id_product] = true;

                        $details = new Product($id_product);

                        if (!Validate::isLoadedObject($details)) {
                            $this->errors[] = sprintf($this->l('Could not load the product id: %d'), $id_product);
                            $error = true;
                            continue;
                        }

                        $id_category = null;
                        $alternate_id_category = null;

                        if (is_array($profile2category) && array_key_exists((int)$details->id_category_default, $profile2category)) {
                            $id_category_default_has_profile = true;
                        } else {
                            $id_category_default_has_profile = false;
                        }
                        // switch to the right category
                        if ((int)$details->id_category_default && $id_category_default_has_profile) {
                            $product_categories = CDiscountProduct::marketplaceGetCategory($id_product);
                            $category_set = is_array($product_categories) && count($product_categories) ? array_merge(array((int)$details->id_category_default), $product_categories) : array((int)$details->id_category_default);
                        } else {
                            $category_set = CDiscountProduct::marketplaceGetCategory($id_product);
                            ;
                        }

                        if (!$id_category_default_has_profile) {
                            $cindex = array_search($details->id_category_default, $category_set);
                            if ($cindex !== false) {
                                unset($category_set[$cindex]);
                            }
                        }

                        if (is_array($category_set) && count($category_set)) {
                            $matching_categories = array_intersect($category_set, $ps_categories);

                            if (is_array($matching_categories)) {
                                $id_category = reset($matching_categories);
                            } else {
                                $id_category = reset($category_set);
                            }

                            if (count($category_set) > 1) {
                                if (in_array($id_category, $category_set) && $matching_categories) {
                                    $alternate_id_category = $id_category;
                                }

                                if (in_array($details->id_category_default, $category_set) && !$alternate_id_category && $id_category_default_has_profile) {
                                    $id_category = (int)$details->id_category_default;
                                } elseif (is_array($profile2category) && is_array($ps_categories)) {
                                    // Product has multiple categories in category selection
                                    if (count(array_intersect($category_set, $ps_categories)) > 1 && !in_array($details->id_category_default, $category_set)) {
                                        if (count(array_unique(array_intersect($category_set, array_keys($profile2category)))) > 1) {
                                            $this->errors[] = sprintf($this->l('Product "%s" has several profiles in serveral categories !'), $id_product);
                                        }
                                    }
                                }
                            }
                        } elseif ($details->id_category_default) {
                            $id_category = (int)$details->id_category_default;
                        } else {
                            if (CDiscount::$debug_mode) {
                                printf('Product has no category: %d'.$cr, $id_product);
                            }
                            continue;
                        }

                        // Filtering Manufacturer & Supplier
                        //
                        if ($details->id_manufacturer) {
                            if (is_array($excluded_manufacturers) && in_array($details->id_manufacturer, $excluded_manufacturers)) {
                                if (!isset($product_filtered['manufacturer'][$details->id_manufacturer])) {
                                    $product_filtered['manufacturer'][$details->id_manufacturer] = 0;
                                }
                                $product_filtered['manufacturer'][$details->id_manufacturer]++;

                                continue;
                            }
                        }


                        if ($details->id_supplier) {
                            if (is_array($excluded_suppliers) && in_array($details->id_supplier, $excluded_suppliers)) {
                                if (!isset($product_filtered['supplier'][$details->id_supplier])) {
                                    $product_filtered['supplier'][$details->id_supplier] = 0;
                                }
                                $product_filtered['supplier'][$details->id_supplier]++;

                                continue;
                            }
                        }

                        // Product Options
                        //
                        $options = CDiscountProduct::getProductOptions($id_product, $id_lang);

                        if ($this->clogistique) {
                            $clogistique_value_added = isset($options['valueadded']) && isset($options['clogistique']) && (bool)$options['clogistique'] && (float)$options['valueadded'] ? (float)$options['valueadded'] : 0;
                        } else {
                            $clogistique_value_added = 0;
                        }

                        $disabled = $options['disable'] ? true : false;
                        $force = $options['force'] ? true : false;
                        $text = $options['text'];
                        $price_up = (float)$options['price_up'] ? (float)$options['price_up'] : null;
                        $price_down = (float)$options['price_down'] ? (float)$options['price_down'] : null;
                        $shipping_delay = max(min((int)$options['shipping_delay'], 10), 0);
                        $shipping_override = $options['shipping'];
                        $priceOverride = false;

                        if (!empty($options['price']) && is_numeric((float)$options['price'])) {
                            $priceOverride = (float)$options['price'];
                        }

                        if (Cdiscount::$debug_mode) {
                            CommonTools::p(sprintf("Product Options for Product(%d)", $id_product));
                            CommonTools::p($options);
                        }

                        // Product Combinations
                        //
                        if (version_compare(_PS_VERSION_, '1.5', '<')) {
                            $combinations = $details->getAttributeCombinaisons($id_lang);
                        } else {
                            $combinations = $details->getAttributeCombinations($id_lang);
                        }

                        // Pas de combinaison, on en cr?e une fictive pour rentrer dans la boucle
                        //
                        if (!is_array($combinations) or empty($combinations)) {
                            $combinations = array(
                                0 => array(
                                    'reference' => $details->reference,
                                    'ecotax' => $details->ecotax,
                                    'ean13' => $details->ean13,
                                    'weight' => 0,
                                    'id_product_attribute' => 0
                                )
                            );
                        }


                        if (Cdiscount::$debug_mode) {
                            CommonTools::p("Product Data:".print_r(get_object_vars($details), true));
                            CommonTools::p("Combination Data: ".print_r($combinations, true));
                        }

                        // Grouping Combinations
                        //
                        asort($combinations);

                        $group_details = array();

                        foreach ($combinations as $comb => $combination) {
                            $id_product_attribute = isset($combination['id_product_attribute']) ? $combination['id_product_attribute'] : 0;
                            $id_attribute_group = isset($combination['id_attribute_group']) ? $combination['id_attribute_group'] : 0;

                            $group_details[$id_product_attribute][$id_attribute_group] = array();
                            $group_details[$id_product_attribute][$id_attribute_group]['reference'] = $combination['reference'];

                            // Synch Field (EAN, UPC, SKU ...)
                            //
                            $group_details[$id_product_attribute][$id_attribute_group]['ean13'] = $combination['ean13'];

                            $group_details[$id_product_attribute][$id_attribute_group]['weight'] = $combination['weight'];
                            $group_details[$id_product_attribute][$id_attribute_group]['ecotax'] = $combination['ecotax'];


                            if (isset($combination['attribute_name'])) {
                                $group_details[$id_product_attribute][$id_attribute_group]['attribute_name'] = $combination['attribute_name'];
                            } else {
                                $group_details[$id_product_attribute][$id_attribute_group]['attribute_name'] = '';
                            }

                            if (isset($combination['group_name'])) {
                                $group_details[$id_product_attribute][$id_attribute_group]['group_name'] = $combination['group_name'];
                            } else {
                                $group_details[$id_product_attribute][$id_attribute_group]['group_name'] = '';
                            }
                        }

                        $idx = 0;
                        // Export Combinations or Products Alone
                        //
                        foreach ($group_details as $id_product_attribute => $combination) {
                            $idx++;
                            $weight = $details->weight;
                            $ean13 = $details->ean13;
                            $reference = $details->reference;
                            $ecotax = $details->ecotax;

                            //Filter Price
                            $stdPrice = $details->getPrice($useTaxes, $id_product_attribute ? $id_product_attribute : null, 2, null, false, !$details->on_sale && $useSpecials);

                            if ($priceFilter && isset($priceFilter['gt']) && (int)$priceFilter['gt'] && (float)$stdPrice > (float)$priceFilter['gt']) {
                                printf($this->l('Skipping filtered product: price %.2f > %.2f'), $stdPrice, $priceFilter['gt']);
                                continue;
                            } elseif ($priceFilter && isset($priceFilter['lt']) && (int)$priceFilter['lt'] && (float)$stdPrice < (float)$priceFilter['lt']) {
                                printf($this->l('Skipping filtered product: price %.2f < %.2f'), $stdPrice, $priceFilter['lt']);
                                continue;
                            }

                            foreach ($combination as $group_detail) {
                                $reference = $group_detail['reference'];
                                $ean13 = $group_detail['ean13'];
                                $weight = $details->weight + $group_detail['weight'];
                                if ((float)$group_detail['ecotax']) {
                                    $ecotax = (float)$group_detail['ecotax'];
                                }
                                break;
                            }

                            if ((bool)Configuration::get('PS_STOCK_MANAGEMENT')) {
                                if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                                    $quantity = Product::getRealQuantity($details->id, $id_product_attribute ? $id_product_attribute : null, $id_warehouse, $id_shop);
                                } else {
                                    $quantity = Product::getQuantity($details->id, $id_product_attribute ? $id_product_attribute : null);
                                }
                            } else {
                                $quantity = 100;
                            }


                            if ($disabled) {
                                $quantity = 0;
                            }

                            if ($oos && $quantity <= 0 && !$disabled) {
                                $quantity = 100;
                            }

                            if (!$stock_management && $quantity <= 0 && !$disabled) {
                                $quantity = 10;
                            }

                            if (!$details->active) {
                                $quantity = 0;
                            }

                            if ($force) {
                                $quantity = 999;
                            }

                            //Filter Stock
                            if ((int)$stockMinFilter) {
                                $quantity -= $stockMinFilter;
                            }

                            // Price //
                            if ($priceOverride) {
                                $price = $priceOverride;
                            } elseif (!empty($profile_price_rule) && is_array($profile_price_rule) && count($profile_price_rule)) {
                                $price = $details->getPrice($useTaxes, $id_product_attribute ? $id_product_attribute : null, 2, null, false, !$details->on_sale && $useSpecials);
                                $price = CDiscountTools::priceRule($price, $profile_price_rule);
                            } else {
                                $price = $details->getPrice($useTaxes, $id_product_attribute ? $id_product_attribute : null, 2, null, false, !$details->on_sale && $useSpecials);
                            }

                            $price_for_repricing = max($stdPrice, $price);
                            $floor_price_for_repricing = null;
                            $lowestPrice = null;
                            $upperPrice = null;

                            if ($align_active) {
                                // Competitor price alignment - global
                                //
                                $percentage = (float)$profile_price_align;
                                if ($percentage >= 1 && $percentage < 99) {
                                    $lowestPrice = (float)Tools::ps_round($price_for_repricing * (100 - $percentage) / 100, 2);
                                } else {
                                    $lowestPrice = null;
                                }

                                // individual
                                if ($price_down && $price_down < $price_for_repricing) {
                                    $lowestPrice = (float)$price_down;
                                }

                                if ($price_up && $price_up > $price_for_repricing) {
                                    $upperPrice = (float)$price_up;
                                } else {
                                    $upperPrice = null;
                                }
                            }

                            if (isset($details->condition) && $details->condition && isset($conditionMap[$details->condition])) {
                                $condition = $conditionMap[$details->condition];
                            } else {
                                $condition = 6;
                            }

                            if ($import_type == Cdiscount::IMPORT_BY_SKU && !$reference) {
                                $product_errors['empty_reference'][] = array(
                                    'ean13' => $ean13,
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute
                                );
                                continue;
                            }

                            if (!CDiscountTools::validateSKU($reference)) {
                                $product_errors['invalid_reference'][] = array(
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute,
                                    'reference' => $reference
                                );
                                continue;
                            }

                            if ($ean_policy == Cdiscount::EAN_POLICY_NORMAL || ($ean_policy == Cdiscount::EAN_POLICY_PERMISSIVE && (int)$ean13)) {
                                if (empty($ean13)) {
                                    $product_errors['missing_ean'][] = array(
                                        'reference' => $reference,
                                        'id_product' => $id_product,
                                        'id_product_attribute' => $id_product_attribute
                                    );
                                    continue;
                                }
                                if (!CommonTools::eanUpcCheck($ean13)) {
                                    $product_errors['wrong_ean'][$ean13] = array(
                                        'ean13' => $ean13,
                                        'id_product' => $id_product,
                                        'id_product_attribute' => $id_product_attribute
                                    );
                                    continue;
                                }
                                if ($ean13 && isset($history[$details->condition.$ean13])) {
                                    $product_errors['duplicate_ean'][$ean13] = array(
                                        'reference' => $reference,
                                        'ean13' => $ean13,
                                        'id_product' => $id_product,
                                        'id_product_attribute' => $id_product_attribute
                                    );
                                    continue;
                                }
                                $history[$details->condition.$ean13] = true;
                            }

                            if ($import_type == Cdiscount::IMPORT_BY_SKU && $reference && isset($sku_history[$reference])) {
                                $product_errors['duplicate_reference'][] = array(
                                    'reference' => $reference,
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute
                                );
                                continue;
                            }
                            $sku_history[$reference] = true;

                            if ($import_type == Cdiscount::IMPORT_BY_SKU) {
                                // Product Export
                                //
                                $toMarketplace[$p]['SellerProductId'] = trim(str_replace('&', '&amp;', $reference));
                            } else {
                                if ($id_product_attribute) {
                                    $toMarketplace[$p]['SellerProductId'] = sprintf('%d_%d', $details->id, $id_product_attribute);
                                } else {
                                    $toMarketplace[$p]['SellerProductId'] = (int)$details->id;
                                }
                            }

                            $ecotax_rate = null;

                            if (version_compare(_PS_VERSION_, '1.4', '>=')) {
                                $ecotax_rate = (float)Tax::getProductEcotaxRate();

                                if ($useTaxes && $ecotax_rate) {
                                    $ecotaxTaxAmount = Tools::ps_round($ecotax * ($ecotax_rate / 100), 2);

                                    $ecotax += $ecotaxTaxAmount;
                                }
                            }

                            $product_tax_rate = 0;

                            if ($useTaxes) {
                                if (method_exists('Tax', 'getProductTaxRate')) {
                                    $product_tax_rate = (float)(Tax::getProductTaxRate($details->id, $id_address));
                                } else {
                                    $product_tax_rate = (float)(Tax::getApplicableTax($details->id_tax, $details->tax_rate, $id_address));
                                }
                            }

                            //
                            // Multitenant
                            //
                            $toMarketplace[$p]['Multitenant'] = array();

                            if ($multitenants && is_array($channels)) {
                                foreach (array_keys($channels) as $channel) {
                                    $toMarketplace[$p]['Multitenant'][] = (int)$channel;
                                }
                            } else {
                                $toMarketplace[$p]['Multitenant'] = array(1);
                            }

                            //
                            // Shipping
                            //
                            $toMarketplace[$p]['Shipping'] = array();

                            $isWeightMoreThan30kg = (float)$weight > (30 * $weight_multiplicator);
                            // Each offer has many shipping information, therefore loop all predefined carriers
                            foreach (parent::$predefined_carriers as $carrier_key => $type) {   // TODO Validation: Yes, it exists
                                // Legacy check, process if has mapping or in 5 types. Then check if it's valid PS carrier
                                // Combine to simple condition: has mapping
                                if (!array_key_exists($type, $carriers_info) || !($id_carrier = (int)$carriers_info[$type])) {
                                    continue;
                                }

                                $isCarrierMoreThan30kg = in_array($carrier_key, Cdiscount::$carriers_more_than_30kg);
                                if ($isWeightMoreThan30kg && !$isCarrierMoreThan30kg || !$isWeightMoreThan30kg && $isCarrierMoreThan30kg) {
                                    $isCarrierMoreThan30kg ? $this->pd('Carrier cannot handle parcel > 30kg')
                                        : $this->pd('Carrier can only handle parcel > 30kg');
                                    // If product's weight is outside of carrier capacity, just ignore as a warning
                                    continue;
                                }

                                $carrier = new Carrier($id_carrier);
                                if (!Validate::isLoadedObject($carrier)) {
                                    $this->errors[] = sprintf('%s(%d): %s (%d)'.$cr, basename(__FILE__), __LINE__, $this->l('Unable to load carrier'), $id_carrier);
                                    $error = true;
                                    unset($toMarketplace[$p]);
                                    continue;
                                }

                                $carrier_tax_rate = 0;

                                // Carrier Taxes
                                //
                                if (method_exists('Carrier', 'getTaxesRate')) {
                                    $tax_address = new Address((int)$id_address);

                                    if (Validate::isLoadedObject($tax_address)) {
                                        $carrier_tax_rate = (float)$carrier->getTaxesRate($tax_address);
                                    }
                                } else {
                                    if (method_exists('Tax', 'getCarrierTaxRate')) {
                                        $carrier_tax_rate = (float)Tax::getCarrierTaxRate($id_carrier, (int)$id_address);
                                    }
                                }

                                $carrier_shipping_price = 0;
                                $shipping_tax_incl = null;


                                if (is_numeric($freeshipping_weight) && (float)$freeshipping_weight > 0 && (float)$weight >= (float)$freeshipping_weight) {
                                    $freeshipping = true;
                                } elseif (is_numeric($freeshipping_price) && (float)$freeshipping_price > 0 && (float)$price >= (float)$freeshipping_price) {
                                    $freeshipping = true;
                                } else {
                                    $freeshipping = false;
                                }

                                if (!$freeshipping && $carrier instanceof Carrier && method_exists('Carrier', 'getDeliveryPriceByWeight')) {
                                    $carrier_shipping_price = $carrier->getDeliveryPriceByWeight($weight, Country::getIdZone($id_country));

                                    if ($carrier_shipping_price !== false) {
                                        $shipping_tax_excl = (float)$carrier_shipping_price;
                                        $shipping_tax_incl = ((((float)$carrier_tax_rate * (float)$shipping_tax_excl) / 100) + (float)$shipping_tax_excl);
                                    }
                                }

                                if (!$freeshipping && !(int)$carrier_shipping_price && $carrier instanceof Carrier && method_exists('Carrier', 'getDeliveryPriceByPrice') && !$shipping_tax_incl) {
                                    $carrier_shipping_price = $carrier->getDeliveryPriceByPrice($price, Country::getIdZone($id_country));

                                    if ($carrier_shipping_price !== false) {
                                        $shipping_tax_excl = (float)$carrier_shipping_price;
                                        $shipping_tax_incl = ((((float)$carrier_tax_rate * (float)$shipping_tax_excl) / 100) + (float)$shipping_tax_excl);
                                    }
                                }

                                if (Cdiscount::$debug_mode) {
                                    CommonTools::p(sprintf('Carrier: %s', print_r(get_object_vars($carrier), true)));
                                    CommonTools::p(sprintf('Carrier Tax Rate: %s', $carrier_tax_rate));
                                    CommonTools::p(sprintf('Free Shipping: %s', $freeshipping ? 'Yes' : 'No'));
                                    CommonTools::p(sprintf('Shipping Tax Excl: %s', isset($shipping_tax_excl) ? $shipping_tax_excl : null));
                                    CommonTools::p(sprintf('Shipping Tax Incl: %s', $shipping_tax_incl));
                                    CommonTools::p(sprintf('Profile Shipping Rule: %s', $profile_shipping_rule));
                                    CommonTools::p(sprintf('Profile Preparation Time: %s', $profile_preparation_time));
                                    CommonTools::p(sprintf('Product Weight: %s', $weight));
                                    CommonTools::p(sprintf('Shipping Override: %s', $shipping_override));
                                }

                                if ($type == 'Relay' && array_key_exists($type, $carriers_params)) {
                                    if ($convert_currency) {
                                        $shipping_tax_incl = Tools::convertPrice($shipping_tax_incl, $target_currency);
                                        $profile_shipping_rule = Tools::convertPrice($profile_shipping_rule, $target_currency);
                                        $carriers_params[$type]['ChargeMin'] = Tools::convertPrice($carriers_params[$type]['ChargeMin'], $target_currency);
                                        $carriers_params[$type]['ChargeAdd'] = Tools::convertPrice($carriers_params[$type]['ChargeAdd'], $target_currency);
                                    }
                                    if (is_array($carriers_params[$type]) && array_key_exists('MinLeadTime', $carriers_params[$type]) && (int)$carriers_params[$type]['MinLeadTime']) {
                                        $toMarketplace[$p]['InShopMinDeliveryTime'] = (int)$carriers_params[$type]['MinLeadTime'] + $shipping_delay;
                                    } else {
                                        $toMarketplace[$p]['InShopMaxDeliveryTime'] = max($preparationTime + $shipping_delay, 1);
                                    }

                                    if (is_array($carriers_params[$type]) && array_key_exists('MaxLeadTime', $carriers_params[$type]) && (int)$carriers_params[$type]['MaxLeadTime']) {
                                        $toMarketplace[$p]['InShopMaxDeliveryTime'] = (int)$carriers_params[$type]['MinLeadTime'] + $shipping_delay;
                                    } else {
                                        $toMarketplace[$p]['InShopMaxDeliveryTime'] = max($preparationTime + $shipping_delay, 2);
                                    }


                                    if (is_numeric($carriers_params[$type]['ChargeMin'])) {
                                        $toMarketplace[$p]['InShopUnitShippingPrice'] = max($shipping_tax_incl + $profile_shipping_rule, max($carriers_params[$type]['ChargeMin'] + $profile_shipping_rule, 0));
                                    } else {
                                        $toMarketplace[$p]['InShopUnitShippingPrice'] = max($shipping_tax_incl + $profile_shipping_rule, 0);
                                    }

                                    if (is_numeric($carriers_params[$type]['ChargeAdd'])) {
                                        $toMarketplace[$p]['InShopAdditionalShippingPrice'] = max($shipping_tax_incl + $profile_shipping_rule, max($carriers_params[$type]['ChargeAdd'] + $profile_shipping_rule, 0));
                                    } else {
                                        $toMarketplace[$p]['InShopAdditionalShippingPrice'] = max($shipping_tax_incl + $profile_shipping_rule, 0);
                                    }

                                    if (!(float)$toMarketplace[$p]['InShopAdditionalShippingPrice']) {
                                        unset($toMarketplace[$p]['InShopAdditionalShippingPrice']);
                                    }
                                }

                                $toMarketplace[$p]['Shipping'][$type] = array(
                                    'Carrier' => $carrier->name,
                                    'Mandatory' => in_array($type, array('Standard', 'Tracked', 'Registered')),
                                );

                                $chargeMin = 0;
                                foreach (array('ChargeMin', 'ChargeAdd') as $param) {
                                    $toMarketplace[$p]['Shipping'][$type][$param] = null;
                                    $this->pd(sprintf('Shipping Param: %s', $param));

                                    if (isset($carriers_params[$type][$param])) {
                                        $min_param = (float)$carriers_params[$type][$param] ? Tools::ps_round($carriers_params[$type][$param], 2) : null;
                                        if ($param == 'ChargeMin') {
                                            if (is_numeric($shipping_override)) {
                                                $value = $chargeMin = $shipping_override;
                                            } elseif (is_numeric($min_param)) {
                                                $value = $chargeMin = max($shipping_tax_incl + $profile_shipping_rule, max($min_param + $profile_shipping_rule, 0));
                                            } else {
                                                $value = $chargeMin = max((float)$shipping_tax_incl + $profile_shipping_rule, 0);
                                            }

                                            if ($profile_shipping_free) {
                                                // Offered shipping is selected for the profile
                                                //
                                                if (is_array($profile_shipping_free) && in_array($type, $profile_shipping_free)) {
                                                    $freeshipping = true;

                                                    // But, Shipping price has to be included in the product price
                                                    //
                                                    if ($profile_shipping_include) {
                                                        // But only a percentage
                                                        if ($profile_shipping_include_percentage) {
                                                            $shipping_to_charge_in_product_price = (float)($value * ($profile_shipping_include_percentage / 100));

                                                            // majoration exceed the limit
                                                            if ((int)$profile_shipping_include_limit && $shipping_to_charge_in_product_price > ((float)$price * ((float)$profile_shipping_include_limit / 100))) {
                                                                $shipping_to_charge_in_product_price = (float)($price * ((float)$profile_shipping_include_limit / 100));
                                                            }
                                                        } else {
                                                            // whole amount

                                                            $shipping_to_charge_in_product_price = (float)$value;
                                                        }
                                                    }
                                                }
                                            }

                                            if ($profile_shipping_free && $freeshipping && !$chargeMin) {
                                                $value = 0;
                                            } elseif (!$profile_shipping_free && $freeshipping && $chargeMin) {
                                                $value = $chargeMin;
                                            } elseif ($profile_shipping_free && $freeshipping && $chargeMin) {
                                                $value = 0;
                                            } elseif ($freeshipping && !$chargeMin) {
                                                $value = 0;
                                            }

                                            $this->pd(sprintf('Value: %s', $value));
                                        } elseif ($param == 'ChargeAdd') {
                                            if ($min_param === '0') {
                                                $value = 0;
                                            } else {
                                                $additionnal_shipping_tax_incl = ($chargeMin * $min_param) / 100;
                                                $value = max($additionnal_shipping_tax_incl + $profile_shipping_rule, 0);
                                            }
                                        } elseif ($param == 'MinLeadTime') {
                                            $value = (int)$min_param ? (int)$min_param + $shipping_delay : max($preparationTime + $shipping_delay, 1);
                                        } elseif ($param == 'MaxLeadTime') {
                                            $value = (int)$min_param ? (int)$min_param + $shipping_delay : max($preparationTime + $shipping_delay, 2);
                                        } else {
                                            $value = max((int)$min_param, 0);
                                        }
                                        if ($convert_currency) {
                                            $value = Tools::convertPrice($value, $target_currency);
                                        }
                                        $this->pd(sprintf('Value: %s', $value));
                                        $toMarketplace[$p]['Shipping'][$type][$param] = $value;
                                    }
                                }
                            }

                            if (!is_array($toMarketplace[$p]['Shipping']) || !count($toMarketplace[$p]['Shipping'])) {
                                $this->errors[] = '<b>'.sprintf('SKU: %s - %s', $reference, $this->l('Shipping is not well configured, you have to configure shipping rates in the module configuration.').$cr).'</b>';
                                $error = true;
                                $panic = true;
                                break;
                            }

                            // Price for external repricing solutions
                            if (method_exists('Product', 'externalPrice')) {
                                $externalPrice = Product::externalPrice($id_lang, $id_product, $id_product_attribute, $this->name, $region);

                                if (Cdiscount::$debug_mode) {
                                    CommonTools::p(sprintf('Product::externalPrice(%d, %d, %d, %s, %s) returned: %s', $id_lang, $id_product, $id_product_attribute, $this->name, $region, $externalPrice));
                                }

                                if (Validate::isPrice($externalPrice)) {
                                    $price = $externalPrice;
                                }
                            }

                            if ($convert_currency) {
                                $price = Tools::convertPrice($price, $target_currency);
                                $ecotax = Tools::convertPrice($ecotax, $target_currency);
                                $clogistique_value_added = Tools::convertPrice($clogistique_value_added, $target_currency);
                                $shipping_to_charge_in_product_price = Tools::convertPrice($shipping_to_charge_in_product_price, $target_currency);
                                $upperPrice = Tools::convertPrice($upperPrice, $target_currency);
                                $lowestPrice = Tools::convertPrice($lowestPrice, $target_currency);
                            }

                            $toMarketplace[$p]['ProductCondition'] = $condition;
                            $toMarketplace[$p]['Price'] = sprintf('%.02f', round($price + $clogistique_value_added + $shipping_to_charge_in_product_price, 2));
                            $toMarketplace[$p]['EcoPart'] = sprintf('%.02f', round($ecotax, 2));
                            $toMarketplace[$p]['DeaTax'] = '0';
                            $toMarketplace[$p]['Vat'] = $product_tax_rate ? $product_tax_rate : '0.00';
                            $toMarketplace[$p]['Stock'] = ($quantity < 0) ? 0 : $quantity;

                            //$toMarketplace[$p]['MinDeliveryTime'] = $MinDeliveryTime + $shipping_delay;
                            //$toMarketplace[$p]['MaxDeliveryTime'] = $MaxDeliveryTime + $shipping_delay;

                            if ((int)$profile_preparation_time && (int)$profile_preparation_time >= 1 && (int)$profile_preparation_time <= 10) {
                                $toMarketplace[$p]['PreparationTime'] = (int)$profile_preparation_time + (int)$shipping_delay;
                            } elseif ((int)$preparationTime > 0 && (int)$preparationTime < 10) {
                                $toMarketplace[$p]['PreparationTime'] = (int)$preparationTime + $shipping_delay;
                            } else {
                                $this->errors[] = $this->l('You must specify your preparation time in the module configuration');
                                unset($toMarketplace[$p]);
                                continue;
                            }

                            if ($profile_cdav && (float)$profile_cdav_max > 0 && $toMarketplace[$p]['Price'] < $profile_cdav_max) {
                                $toMarketplace[$p]['IsCDAV'] = "true";
                            }

                            if (empty($toMarketplace[$p]['Price']) || !Validate::isPrice($toMarketplace[$p]['Price'])) {
                                $this->errors[] = sprintf($this->l('Wrong price format for product %s(%d/%d) - %s').$cr, $reference, $id_product, $id_product_attribute, $toMarketplace[$p]['Price']);
                                unset($toMarketplace[$p]);
                                continue;
                            }

                            if (!empty($text)) {
                                $toMarketplace[$p]['Comment'] = str_replace('"', '', $text);
                            } elseif ($default_comments) {
                                $toMarketplace[$p]['Comment'] = str_replace('"', '', $default_comments);
                            }

                            // $details->active && $quantity : prevents offer creation
                            /*
                            if ($details->active && $quantity && !$disabled && (int)$ean13 && ($ean_policy == Cdiscount::EAN_POLICY_NORMAL || $ean_policy == Cdiscount::EAN_POLICY_PERMISSIVE)) {
                                $toMarketplace[$p]['ProductEan'] = sprintf('%013s', trim($ean13));
                            }
                            */
                            // $details->active && $quantity : prevents offer creation
                            if ((int)$ean13 && ($ean_policy == Cdiscount::EAN_POLICY_NORMAL || $ean_policy == Cdiscount::EAN_POLICY_PERMISSIVE)) {
                                $toMarketplace[$p]['ProductEan'] = sprintf('%013s', trim($ean13));
                            }

                            // Sales - Flash Sales
                            //
                            $discountItem = null;
                            $StrikedPrice = null;
                            $sale_price = null;

                            if (version_compare(_PS_VERSION_, '1.4', '>=') && $useSpecials && !$priceOverride) {
                                $specificPrice = SpecificPrice::getSpecificPrice($id_product, $id_shop, $id_currency, $id_country_default, (int)$id_customer_group, 1, $id_product_attribute ? $id_product_attribute : null, 0, 0, 1);

                                if (Cdiscount::$debug_mode) {
                                    CommonTools::p(sprintf('SpecificPrice: Parameters - id_product: %s, id_shop: %s, id_currency: %s, id_country: %s, id_group: %s, id_product_attribute: %s', $id_product, $id_shop, $id_currency, $id_country_default, $id_customer_group, $id_product_attribute));
                                    CommonTools::p('SpecificPrice:'.print_r($specificPrice, true));
                                }


                                // Sales
                                //
                                if ($details->on_sale && $on_sale_period && $specificPrice && isset($specificPrice['reduction_type']) && isset($specificPrice['from']) && isset($specificPrice['to']) && (int)$specificPrice['from'] && (int)$specificPrice['to']) {
                                    // ISO 8601
                                    $dateStart = date('c', strtotime($specificPrice['from']));
                                    $dateEnd = date('c', strtotime($specificPrice['to']));

                                    $sale_price = $beforePriceRule = $details->getPrice($useTaxes, ($id_product_attribute ? $id_product_attribute : null), 6, null, false, $useSpecials);

                                    if ($formulaOnSpecials && !empty($profile_price_rule) && is_array($profile_price_rule) && count($profile_price_rule)) {
                                        $sale_price = CDiscountTools::priceRule($sale_price, $profile_price_rule);
                                    }

                                    if (Cdiscount::$debug_mode) {
                                        CommonTools::p(sprintf('SpecificPrice: Price Before Rule: %.02f, After Rule: %.02f', $beforePriceRule, $sale_price));
                                    }

                                    if ($convert_currency) {
                                        $sale_price = Tools::convertPrice($sale_price, $target_currency);
                                    }

                                    $sale_price += $clogistique_value_added + $shipping_to_charge_in_product_price;

                                    if (Cdiscount::$debug_mode) {
                                        CommonTools::p(sprintf('SpecificPrice: Price C Logistique included: %.02f', $sale_price));
                                    }

                                    if ($sale_price < $toMarketplace[$p]['Price']) {
                                        $toMarketplace[$p]['Price'] = sprintf('%.02f', round($sale_price, 2));

                                        $reduction = round(($price + $clogistique_value_added + $shipping_to_charge_in_product_price) - $sale_price, 2);
                                        $percentage = round($reduction * 100 / ($price + $clogistique_value_added + $shipping_to_charge_in_product_price), 0, PHP_ROUND_HALF_UP);

                                        $discountItem['dateStart'] = $dateStart;
                                        $discountItem['dateEnd'] = $dateEnd;

                                        $regular_price = sprintf('%.02f', round(($price + $clogistique_value_added + $shipping_to_charge_in_product_price), 2));

                                        $discountItem['price'] = $regular_price;

                                        if ($smart_rounding) {
                                            $target_sale_price = CDiscountTools::smartRounding($sale_price);
                                            $reverse_percentage = 1-$percentage/100;
                                            $new_regular_price = $target_sale_price / $reverse_percentage;
                                            $discountItem['price'] = sprintf('%.02f', $new_regular_price);
                                            $toMarketplace[$p]['Price'] = $target_sale_price;

                                            if (Cdiscount::$debug_mode) {
                                                CommonTools::p(sprintf('Smart Rounding: Percentage: %f Target Price: %.02f Price: %.02f', $percentage, $target_sale_price, $new_regular_price));
                                            }
                                        }

                                        $discountItem['percentage'] = $percentage;
                                        $discountItem['type'] = self::DISCOUNT_SALE;
                                    }

                                    if (Cdiscount::$debug_mode) {
                                        CommonTools::p(sprintf('SpecificPrice: Parameters - is Sale: Start Date: %s Stop Date: %s Price: %.02f', $dateStart, $dateEnd, $sale_price));
                                        if ($discountItem) {
                                            CommonTools::p($discountItem);
                                        }
                                    }
                                } elseif (isset($specificPrice['reduction_type']) && $useSpecials) {
                                    $price = $details->getPrice($useTaxes, $id_product_attribute ? $id_product_attribute : null, 2, null, false, $useSpecials);

                                    $price += $clogistique_value_added + $shipping_to_charge_in_product_price;

                                    if ($formulaOnSpecials && !empty($profile_price_rule) && is_array($profile_price_rule) && count($profile_price_rule)) {
                                        $price = CDiscountTools::priceRule($price, $profile_price_rule);
                                    }

                                    if ($convert_currency) {
                                        $price = Tools::convertPrice($price, $target_currency);
                                    }

                                    if ($price < $toMarketplace[$p]['Price']) {
                                        $toMarketplace[$p]['Price'] = sprintf('%.02f', $price);

                                        if (Cdiscount::$debug_mode) {
                                            CommonTools::p(sprintf('SpecificPrice: price is greater that the price calculated earlier (%.02f/%.02f)', $price, $toMarketplace[$p]['Price']));
                                        }
                                    }

                                    $StrikedPrice = round($details->getPrice($useTaxes, $id_product_attribute ? $id_product_attribute : null, 2, null, false, false), 2);

                                    if ($convert_currency) {
                                        $StrikedPrice = Tools::convertPrice($StrikedPrice, $target_currency);
                                    }
                                    $StrikedPrice += $shipping_to_charge_in_product_price;

                                    if (Cdiscount::$debug_mode) {
                                        CommonTools::p(sprintf('SpecificPrice: Parameters - is Discount: Price: %.02f - Striked Price: %.02f - Shipping to Charge: %.02f', $price, $StrikedPrice, $shipping_to_charge_in_product_price));
                                    }
                                } elseif (Cdiscount::$debug_mode) {
                                    CommonTools::p(sprintf('SpecificPrice: No Sale found, useSpecials = %s', $useSpecials));
                                }
                            }

                            if ($StrikedPrice && $StrikedPrice > $toMarketplace[$p]['Price']) {
                                if (Cdiscount::$debug_mode) {
                                    CommonTools::p(sprintf('Discount: Price: %.02f - Striked Price: %.02f - Smart Rounding: %s', $toMarketplace[$p]['Price'], $StrikedPrice, $smart_rounding ? 'Yes' : 'No'));
                                }


                                if ($smart_rounding) {
                                    $toMarketplace[$p]['StrikedPrice'] = CDiscountTools::smartRounding($StrikedPrice);
                                } else {
                                    $toMarketplace[$p]['StrikedPrice'] = sprintf('%.02f', round($StrikedPrice, 2));
                                }
                            }

                            $toMarketplace[$p]['Discount'] = $discountItem;

                            if (!$discountItem && $smart_rounding && !$priceOverride) {
                                $toMarketplace[$p]['Price'] = CDiscountTools::smartRounding($toMarketplace[$p]['Price']);
                            }

                            if ($sale_price) {
                                // Case of discount on the price
                                $floor_price_for_repricing = Tools::ps_round(min($sale_price, $price, $lowestPrice), 2);
                            } elseif ($StrikedPrice) {
                                $floor_price_for_repricing = Tools::ps_round(min($StrikedPrice, $price, $lowestPrice), 2);
                            } else {
                                $floor_price_for_repricing = Tools::ps_round($lowestPrice, 2);
                            }

                            if (Cdiscount::$debug_mode && $align_active) {
                                CommonTools::p(sprintf('Alignment parameters'));
                                CommonTools::p(sprintf('Repricing base: %s', $price_for_repricing));
                                CommonTools::p(sprintf('Lowest Price: %s', $lowestPrice));
                                CommonTools::p(sprintf('Floor Price: %s', $floor_price_for_repricing));
                                CommonTools::p(sprintf('Price: %s', $price));
                                CommonTools::p(sprintf('C Logistique value added: %s', $clogistique_value_added));
                                CommonTools::p(sprintf('Shipping Charge in Product Price: %s', $shipping_to_charge_in_product_price));
                                CommonTools::p(sprintf('Upper Price: %s', $upperPrice));
                                CommonTools::p(sprintf('Regular Price: %s', $price));
                            }

                            if ($align_active && $lowestPrice) {
                                $minimum = sprintf('%.02f', round($lowestPrice + $clogistique_value_added + $shipping_to_charge_in_product_price, 2));
                                $has_condition_for_repricing = $minimum >= $floor_price_for_repricing && $minimum < $price + $clogistique_value_added + $shipping_to_charge_in_product_price;

                                if (Cdiscount::$debug_mode && $align_active) {
                                    CommonTools::p(sprintf('Current Price: %.02f', (float)$toMarketplace[$p]['Price']));
                                    CommonTools::p(sprintf('With gap: %.02f', (float)$toMarketplace[$p]['Price'] * self::GAP_MIN_FOR_ALIGNMENT));
                                    CommonTools::p(sprintf('Minimum: %.02f', $minimum));
                                    CommonTools::p(sprintf('Has condition for repricing: %s', $has_condition_for_repricing ? 'Yes' : 'No'));
                                }


                                if ((float)$toMarketplace[$p]['Price'] * self::GAP_MIN_FOR_ALIGNMENT > (float)$minimum && $has_condition_for_repricing) {
                                    $toMarketplace[$p]['MinimumPriceForPriceAlignment'] = $minimum;
                                    $toMarketplace[$p]['PriceMustBeAligned'] = 'Align';
                                } else {
                                    $toMarketplace[$p]['MinimumPriceForPriceAlignment'] = Tools::ps_round($price * self::GAP_MIN_FOR_ALIGNMENT, 2);
                                    $toMarketplace[$p]['PriceMustBeAligned'] = 'DontAlign';
                                }

                                if ($upperPrice) {
                                    $toMarketplace[$p]['Price'] = sprintf('%.02f', round($upperPrice + $clogistique_value_added + $shipping_to_charge_in_product_price, 2));
                                }
                            }

                            if (Cdiscount::$debug_mode) {
                                CommonTools::p('Content:'.print_r($toMarketplace[$p], true));
                                CommonTools::p('Memory: '.number_format(memory_get_usage() / 1024).'k');
                            }

                            if ($toMarketplace[$p]['Stock']) {
                                $updated++;
                            } else {
                                $deleted++;
                            }

                            $count++;

                            $p++;

                            if (Cdiscount::$debug_mode) {
                                CommonTools::p(sprintf('Exporting Product: %d id: %d reference: %s %s', $idx, $details->id, $reference, $cr));
                            }

                            if ($panic) {
                                break;
                            }
                        } // end foreach combinations

                        if ($panic) {
                            break;
                        }
                    }
                } // end foreach products

                if ($panic) {
                    break;
                }
            } // end foreach categories
        } // end if

        // Report filtered products
        foreach ($product_filtered as $filter_type => $filter_content) {
            if (isset($product_filtered[$filter_type]) && is_array($product_filtered[$filter_type]) && count($product_filtered[$filter_type])) {
                $msg = null;

                foreach ($filter_content as $id_object => $count) {
                    if (!$count) {
                        continue;
                    }

                    switch ($filter_type) {
                        case 'manufacturer':
                            if ($msg == null) {
                                $msg = $this->l('Products Filters - Manufacturer filter summary').':<br /> ';
                            }

                            $manufacturer = new Manufacturer($id_object, $this->id_lang);

                            if (Validate::isLoadedObject($manufacturer)) {
                                $msg .= sprintf("&nbsp;&nbsp;- %-'.50s %d<br />", $manufacturer->name, $count);
                            } else {
                                $msg = null;
                            }
                            break;

                        case 'supplier':
                            if ($msg == null) {
                                $msg = $this->l('Products Filters - Suppliers filter summary').':<br /> ';
                            }

                            $supplier = new Supplier($id_object, $this->id_lang);

                            if (Validate::isLoadedObject($supplier)) {
                                $msg .= sprintf("&nbsp;&nbsp;- %-'.50s %d<br />", $supplier->name, $count);
                            } else {
                                $msg = null;
                            }
                            break;
                    }
                }
                if ($msg) {
                    $this->errors[] = $msg;
                }
            }
        }

        if (is_array($product_errors)) {
            foreach ($product_errors as $error_type => $errorsOfType) {
                if (is_array($errorsOfType) && count($errorsOfType)) {
                    $msg = null;
                    foreach ($errorsOfType as $product_error) {
                        switch ($error_type) {
                            case 'invalid_reference':
                                if ($msg == null) {
                                    $msg = $this->l('Invalid SKU, References').': [';
                                }

                                if (Tools::strlen($product_error['reference'])) {
                                    $msg .= sprintf('%s, ', $product_error['reference']);
                                } else {
                                    $msg .= sprintf('%d/%d, ', $product_error['id_product'], $product_error['id_product_attribute']);
                                }

                                break;

                            case 'empty_reference':
                                if ($msg == null) {
                                    $msg = $this->l('Products having empty references, Product ID').': [';
                                }

                                if ($product_error['id_product_attribute']) {
                                    $msg .= sprintf('%d/%d, ', $product_error['id_product'], $product_error['id_product_attribute']);
                                } else {
                                    $msg .= sprintf('%d, ', $product_error['id_product']);
                                }


                                break;

                            case 'duplicate_ean':
                                if ($msg == null) {
                                    $msg = $this->l('Duplicate EAN entry for product, References').': [';
                                }

                                $msg .= sprintf('%s, ', $product_error['reference'] ? $product_error['reference'] : '#'.$product_error['id_product']);
                                break;

                            case 'missing_ean':
                                if ($msg == null) {
                                    $msg = $this->l('EAN is missing, References').': [';
                                }

                                $msg .= sprintf('%s, ', $product_error['reference'] ? $product_error['reference'] : '#'.$product_error['id_product']);
                                break;

                            case 'duplicate_reference':
                                if ($msg == null) {
                                    $msg = $this->l('Duplicate reference entry for product, Product ID').': [';
                                }

                                if ($product_error['id_product_attribute']) {
                                    $msg .= sprintf('%d/%d, ', $product_error['id_product'], $product_error['id_product_attribute']);
                                } else {
                                    $msg .= sprintf('%d, ', $product_error['id_product']);
                                }
                                break;

                            case 'wrong_ean':
                                if ($msg == null) {
                                    $msg = $this->l('EAN is incorrect, Product ID').': [';
                                }

                                if ($product_error['id_product_attribute']) {
                                    $msg .= sprintf('%d/%d, ', $product_error['id_product'], $product_error['id_product_attribute']);
                                } else {
                                    $msg .= sprintf('%d, ', $product_error['id_product']);
                                }
                                break;
                        }
                    }

                    if ($msg) {
                        $msg = rtrim($msg, ', ').']';

                        switch ($error_type) {
                            case 'empty_reference':
                                $msg = $mpSupport->message($msg, CDiscountSupport::FUNCTION_EXPORT_EMPTY_REFERENCE);
                                break;
                            case 'duplicate_reference':
                                $msg = $mpSupport->message($msg, CDiscountSupport::FUNCTION_EXPORT_DUPLICATE);
                                break;
                            case 'wrong_ean':
                                $msg = $mpSupport->message($msg, CDiscountSupport::FUNCTION_EXPORT_WRONG_EAN);
                                break;
                            case 'duplicate_ean':
                                $msg = $mpSupport->message($msg, CDiscountSupport::FUNCTION_EXPORT_DUPLICATE_EAN);
                                break;
                            case 'missing_ean':
                                $msg = $mpSupport->message($msg, CDiscountSupport::FUNCTION_EXPORT_MISSING_EAN);
                                break;
                            default:
                                $msg .= '<br /><br />';
                                break;
                        }
                        $this->errors[] = $msg;
                    }
                }
                $msg = null;
            }
        }

        if (isset($toMarketplace) && is_array($toMarketplace) && ($count = count($toMarketplace)) && $count > 0) {
            $packageId = false;

            if (!$this->createOffers($offers_file, $toMarketplace, $multitenants)) {
                $this->errors[] = sprintf($this->l('Offers file creation failed...')).$cr;
            } else {
                $msg = sprintf('%d %s: <a href="%s">%s</a>'.$cr, $count, $this->l('offers successfully created in'), $this->pickup_url.$zipfile, $zipfile);
            }

            if ($this->createZip($zipfile, str_replace($output_dir.'/', '', $from)) < 0) {
                $this->errors[] = sprintf($this->l('ZIP Package creation failed')).$cr;
            } else {
                $username = Configuration::get(parent::KEY.'_USERNAME');
                $password = Configuration::get(parent::KEY.'_PASSWORD');
                $production = !(Configuration::get(parent::KEY.'_PREPRODUCTION') ? true : false);
                $packageId = false;

                $webservice = new CDiscountWebservice($username, $password, $production, Cdiscount::$debug_mode, $this->dev_mode);
                $webservice->token = CDiscountTools::auth();

                if (!$this->dev_mode && !$webservice->token) {
                    $this->errors[] = sprintf($this->l('Authentification process with CDiscount failed !')).$cr;
                } else {
                    $params = array();
                    $params['FILE'] = sprintf('%s%s', $this->pickup_url, $zipfile);

                    if (!$dont_send) {
                        $packageId = $webservice->SubmitOfferPackage($params);
                        $this->logContent($webservice->getLogContent());
                    }

                    if ($this->dev_mode) {
                        $packageId = rand(10000, 900000);
                    }

                    if ($dont_send) {
                        $msg = sprintf('%s (%s)<br>', $this->l('Products Successfully Exported'), $count);
                    } else {
                        if (!($packageId)) {
                            $this->errors[] = $this->l('Package submission has failed, please verify your datas').$cr;
                        } else {
                            $msg = sprintf('%s %s', $count, $this->l('Products Successfully Exported')).$cr;
                            $msg .= sprintf("%s: %d<br>\n", $this->l('Exporting Package Id'), $packageId).$cr;
                        }
                    }
                }
            }

            $batches = new CDiscountBatches($cron ? parent::KEY.'_BATCH_UPDATE_CRON' : parent::KEY.'_BATCH_UPDATE');
            $batch = new CDiscountBatch($timestart);
            $batch->id = $packageId;
            $batch->channel = CdiscountConfig::OFFER_POOL_CDISCOUNT;
            $batch->timestop = time();
            $batch->updated = $updated;
            $batch->deleted = $deleted;
            $batches->add($batch);
            $batches->save();
        } else {
            $this->errors[] = '<b>'.$this->l('No offers to export matching those criterias').'</b>:'.$cr;
            $this->errors[]
                = sprintf(
                    '<ul>
                        <li>%s %s</li>
                        <li>%s</li>
                        <li>%s</li>
                        <li>%s %s</li>
                        <li>%s %s</li>
                    </ul>'.$cr,
                    $date_from ? $this->l('Modified products since') : $this->l('All products'),
                    $date_from,
                    $create_active ? $this->l('Only active products') : $this->l('Active and inactive products'),
                    $create_in_stock ? $this->l('Only in stock products') : $this->l('In stock and out of stock products'),
                    is_array($default_categories) ? count($default_categories) : 0,
                    $this->l('selected categories'),
                    is_array($default_profiles2categories) ? count(array_unique(array_filter($default_profiles2categories))) : 0,
                    $this->l('selected profiles')
                );
        }

        if (count($this->errors)) {
            $error = true;
        }

        // Export Output
        //
        $output = ob_get_clean();

        if ($error) {
            $this->errors[] = $this->l('An error occured while exporting the products').$cr;
            $error = true;
        }

        // jQuery Output or PHP Output
        //
        if (($callback = Tools::getValue('callback'))) {
            // jquery
            $json = json_encode(array(
                'error' => $error,
                'errors' => count($this->errors) ? $this->errors : null,
                'msg' => $msg,
                'output' => $output,
                'count' => $count
            ));
            echo (string)$callback.'('.$json.')';
        } else {
            // cron
            $this->pd('Count: ' . $count);
            $this->pd('Error:', print_r($this->errors, true));
            $this->pd('Message: ' . $msg);
            $this->pd('Output:', $output);
        }
        die;
    }

    private function createRelationships($file)
    {
        $Document = new DOMDocument();
        $Document->preserveWhiteSpace = true;
        $Document->formatOutput = true;
        $Document->encoding = 'utf-8';
        $Document->version = '1.0';

        $Relationships = $Document->appendChild($Document->createElement('Relationships'));
        $Relationships->setAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $Relationship = $Relationships->appendChild($Document->createElement('Relationship'));
        $Relationship->setAttribute('Type', 'http://cdiscount.com/uri/document');
        $Relationship->setAttribute('Target', '/Content/Offers.xml');
        $Relationship->setAttribute('Id', 'RId1'.uniqid());

        return ($this->saveXML($file, $Document));
    }

    private function saveXML($file, $Document)
    {
        $cr = $this->_cr;

        if (!is_dir(dirname($file))) {
            $this->errors[] = sprintf('%s(%s): %s - %s', basename(__FILE__), __LINE__, $this->l('Directory doesnt exist, please create it'), dirname($file)).$cr;

            return (false);
        }
        if (!CommonTools::isDirWriteable(dirname($file))) {
            $this->errors[] = sprintf('%s(%s): %s', basename(__FILE__), __LINE__, $this->l('Directory is not writeable')).$cr;

            return (false);
        }
        if (file_exists($file) && !is_writeable($file)) {
            if (!@chmod($file, Cdiscount::PERMISSIONS_STD_FILE)) {
                $this->errors[] = sprintf('%s(%s): %s', basename(__FILE__), __LINE__, $this->l('Unable to change permissions of the XML file')).$cr;

                return (false);
            }
        } else {
            if (file_put_contents($file, null) === false) {
                $this->errors[] = sprintf('%s(%s): %s - %s', basename(__FILE__), __LINE__, $this->l('Unable clean XML file'), $file).$cr;
            }
        }

        if (!$content = $Document->saveXML()) {
            $this->errors[] = sprintf('%s(%s): %s', basename(__FILE__), __LINE__, $this->l('Unable to generate XML')).$cr;

            return (false);
        }

        // Windows Format
        $content = str_replace(self::LF, self::CRLF, $content);

        if (file_put_contents($file, $content) === false) {
            $this->errors[] = sprintf('%s(%s): %s - %s', basename(__FILE__), __LINE__, $this->l('Unable to write to the XML file'), $file).$cr;

            return (false);
        }

        if (Cdiscount::$debug_mode) {
            echo nl2br(htmlentities($content, ENT_QUOTES, 'UTF-8')).$cr;
        }

        return (true);
    }

    private function createContentType($file)
    {
        $Document = new DOMDocument();
        $Document->preserveWhiteSpace = true;
        $Document->formatOutput = true;
        $Document->encoding = 'utf-8';
        $Document->version = '1.0';

        $Types = $Document->appendChild($Document->createElement('Types'));
        $Types->setAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/content-types');

        $Default = $Types->appendChild($Document->createElement('Default'));
        $Default->setAttribute('Extension', 'rels');
        $Default->setAttribute('ContentType', 'application/vnd.openxmlformats-package.relationships+xml');

        $Default = $Types->appendChild($Document->createElement('Default'));
        $Default->setAttribute('Extension', 'xml');
        $Default->setAttribute('ContentType', 'text/xml');

        return ($this->saveXML($file, $Document));
    }

    private function createOffers($file, $offers, $multitenant_cfg)
    {
        $cr = $this->_cr;

        if (Cdiscount::$debug_mode) {
            echo nl2br(print_r($offers, true)).$cr;
        }

        if (Tools::getValue('purge-replace')) {
            $purgeAndReplace = 'true';
        } else {
            $purgeAndReplace = 'false';
        }

        $Document = new DOMDocument();
        $Document->preserveWhiteSpace = true;
        $Document->formatOutput = true;

        $Document->encoding = 'utf-8';
        $Document->version = '1.0';

        $OfferPackage = $Document->appendChild($Document->createElement('OfferPackage'));
        $OfferPackage->setAttribute('Name', $this->l('Offers from').' '.CommonTools::ucwords(CDiscountTools::getFriendlyUrl(Configuration::get('PS_SHOP_NAME'))));
        $OfferPackage->setAttribute('PurgeAndReplace', $purgeAndReplace);
        $OfferPackage->setAttribute('xmlns', 'clr-namespace:Cdiscount.Service.OfferIntegration.Pivot;assembly=Cdiscount.Service.OfferIntegration');
        $OfferPackage->setAttribute('xmlns:x', 'http://schemas.microsoft.com/winfx/2006/xaml');


        $Offers = $OfferPackage->appendChild($Document->createElement('OfferPackage.Offers'));
        $OfferCollection = $Offers->appendChild($Document->createElement('OfferCollection'));
        $OfferCollection->setAttribute('Capacity', count($offers));

        foreach ($offers as $Product) {
            $Offer = $OfferCollection->appendChild($Document->createElement('Offer'));

            ksort($Product);

            foreach ($Product as $attr => $value) {
                if ($attr == 'Discount') {
                    $discountItem = $value; // for good understanding

                    switch ($discountItem['type']) {
                        case self::DISCOUNT_SALE:
                            $PriceAndDiscountList = $Offer->appendChild($Document->createElement('Offer.PriceAndDiscountList'));

                            $DiscountComponentList = $PriceAndDiscountList->appendChild($Document->createElement('DiscountComponentList'));
                            $DiscountComponentList->setAttribute('Capacity', '1');
                            $DiscountComponent = $DiscountComponentList->appendChild($Document->createElement('DiscountComponent'));
                            $DiscountComponent->setAttribute('DiscountUnit', '1');
                            $DiscountComponent->setAttribute('DiscountValue', (int)$discountItem['percentage']);
                            $DiscountComponent->setAttribute('SalesReferencePrice', (float)$discountItem['price']);
                            $DiscountComponent->setAttribute('Type', $discountItem['type']);
                            $DiscountComponent->setAttribute('StartDate', $discountItem['dateStart']);
                            $DiscountComponent->setAttribute('EndDate', $discountItem['dateEnd']);
                            break;
                    }
                } elseif ($attr == 'Shipping') {
                    $shippingItems = $value; // for good understanding

                    $Offer_ShippingInformationList = $Offer->appendChild($Document->createElement('Offer.ShippingInformationList'));

                    $ShippingInformationList = $Offer_ShippingInformationList->appendChild($Document->createElement('ShippingInformationList'));
                    $ShippingInformationListCapacity = 0;

                    // <ShippingInformation AdditionalShippingCharges="3.95" DeliveryMode="SoColissimo" MaxLeadTime="8" MinLeadTime="4" ShippingCharges="4.5" />
                    foreach ($shippingItems as $shippingMethod => $shippingItem) {
                        if (!is_numeric($shippingItem['ChargeMin']) && !(int)$shippingItem['Mandatory']) {
                            continue;
                        }

                        $ShippingInformation = $ShippingInformationList->appendChild($Document->createElement('ShippingInformation'));

                        if (isset($shippingItem['ChargeAdd']) && $shippingItem['ChargeAdd']) {
                            $ShippingInformation->setAttribute('AdditionalShippingCharges', sprintf('%.02f', $shippingItem['ChargeAdd']));
                        }

                        $ShippingInformation->setAttribute('DeliveryMode', $shippingMethod);

                        if (isset($shippingItem['MaxLeadTime']) && $shippingItem['MaxLeadTime']) {
                            $ShippingInformation->setAttribute('MaxLeadTime', (int)$shippingItem['MaxLeadTime']);
                        }

                        if (isset($shippingItem['MinLeadTime']) && $shippingItem['MinLeadTime']) {
                            $ShippingInformation->setAttribute('MinLeadTime', (int)$shippingItem['MinLeadTime']);
                        }

                        $ShippingInformation->setAttribute('ShippingCharges', sprintf('%.02f', $shippingItem['ChargeMin']));

                        $ShippingInformationListCapacity++;
                    }
                    $ShippingInformationList->setAttribute('Capacity', $ShippingInformationListCapacity);
                } elseif ($attr == 'Multitenant') {
                    $channels = $value; // for good understanding

                    if (is_array($multitenant_cfg) && count($multitenant_cfg) > 1) {
                        $offer_offer_pool_list = $Offer->appendChild($Document->createElement('Offer.OfferPoolList'));
                        $offer_pool_list = $offer_offer_pool_list->appendChild($Document->createElement('OfferPoolList'));
                        $offer_pool_list->setAttribute('Capacity', count($multitenant_cfg));

                        foreach ($multitenant_cfg as $channel) {
                            $offer_pool = $offer_pool_list->appendChild($Document->createElement('OfferPool'));
                            $offer_pool->setAttribute('Id', $channel);

                            if (in_array($channel, $channels)) {
                                $offer_pool->setAttribute('Published', 'True');
                            } else {
                                $offer_pool->setAttribute('Published', 'False');
                            }
                        }
                    }
                } else {
                    // Standard Offer
                    $Offer->setAttribute($attr, $value);
                }
            }
            if (Cdiscount::$debug_mode) {
                echo $cr;
                echo 'Memory: '.number_format(memory_get_usage() / 1024).'k'.$cr;
            }
        }

        if (Cdiscount::$debug_mode) {
            echo 'XML Output: '.nl2br(print_r($Document->saveXML(), true));
        }

        return ($this->saveXML($file, $Document));
    }

    private function createZip($zipfile, $from)
    {
        $cr = $this->_cr;
        $zipfile = sprintf('%s/../export/%s', dirname(__FILE__), $zipfile);
        $zipfile = str_replace('/', DIRECTORY_SEPARATOR, $zipfile);
        $current_wd = getcwd();
        $zipdir = str_replace('/', DIRECTORY_SEPARATOR, sprintf('%s/%s', dirname($zipfile), $this->xml_dir));

        if (!chdir($zipdir)) {
            $this->errors[] = sprintf('%s(%s): '.$this->l('Failed to change directory: %s'), basename(__FILE__), __LINE__, $zipdir);
            return (false);
        }

        if (!is_writable($zipdir)) {
            $this->errors[] = sprintf('%s(%s): '.$this->l('Directory is not writeable'). '- "%s"', basename(__FILE__), __LINE__, $zipdir).$cr;
            return(false);
        }

        if (Cdiscount::$debug_mode) {
            CommonTools::p($zipfile);
            CommonTools::p($from);
        }
        $cdiscountZip = new CdiscountZip();
        $result = $cdiscountZip->createZip($zipfile, $from);

        chdir($current_wd);

        return($result);
    }

    private function history()
    {
        $smarty = $this->context->smarty;
        $html = null;

        require_once(_PS_MODULE_DIR_.parent::MODULE.'/classes/'.parent::MODULE.'.batch.class.php');

        $batches_cron = new CDiscountBatches(parent::KEY.'_BATCH_UPDATE_CRON');
        $batches_cron_list = $batches_cron->load();

        $batches_std = new CDiscountBatches(parent::KEY.'_BATCH_UPDATE');
        $batches_std_list = $batches_std->load();

        $batches_results = array();

        if (!is_array($batches_std_list) && !is_array($batches_cron_list)) {
            ob_get_clean();
            header('HTTP/1.1 204 OK');
            die;
        }

        foreach ($batches_cron_list as $batch) {
            if ($batch instanceof CDiscountBatch) {
                $batches_results[$batch->timestart] = $batch->format();
                $batches_results[$batch->timestart]['type'] = $this->l('Cron');
                $batches_results[$batch->timestart]['records'] = $batch->created + $batch->updated + $batch->deleted;

                if ($this->dev_mode) {
                    $batches_results[$batch->timestart]['id'] = rand(10000, 900000);
                    $batches_results[$batch->timestart]['hasid'] = true;
                }
            }
        }
        foreach ($batches_std_list as $batch) {
            if ($batch instanceof CDiscountBatch) {
                if (!$batch->timestop) {
                    continue;
                }

                $batches_results[$batch->timestart] = $batch->format();
                $batches_results[$batch->timestart]['type'] = $this->l('Interactive');
                $batches_results[$batch->timestart]['records'] = $batch->created + $batch->updated + $batch->deleted;

                if ($this->dev_mode) {
                    $batches_results[$batch->timestart]['id'] = rand(10000, 900000);
                    $batches_results[$batch->timestart]['hasid'] = true;
                }
            }
        }

        if (!is_array($batches_results) || !count($batches_results)) {
            ob_get_clean();
            header('HTTP/1.1 204 OK');
            die;
        }
        krsort($batches_results);


        $xml = sprintf('%s/%s/Content/%s', $this->export, $this->_folder, self::OFFERS_FILE);
        $zip = sprintf('%s/%s', $this->export, $this->zipfile);

        if (file_exists($zip)) {
            $smarty->assign('zipurl', sprintf('%s%s', $this->pickup_url, $this->zipfile));
            $smarty->assign('zipfile', $this->zipfile);
        }

        if (file_exists($xml)) {
            $smarty->assign('xmlurl', sprintf('%s%s/Content/%s', $this->pickup_url, $this->_folder, self::OFFERS_FILE));
            $smarty->assign('xmlfile', self::OFFERS_FILE);
        }

        if (method_exists($smarty, 'setTemplateDir')) {
            $currentTemplates = $smarty->getTemplateDir();
            $additionnalTemplates = array($this->path.'views/templates/admin/catalog/');
            $smarty->setTemplateDir(is_array($currentTemplates) ? array_merge($currentTemplates, $additionnalTemplates) : $additionnalTemplates);
        } else {
            $smarty->template_dir = $this->path.'views/templates/admin/catalog/';
        }

        $smarty->assign('batches', $batches_results);
        $smarty->assign('images', $this->images);

        $html .= $smarty->fetch($this->path.'views/templates/admin/catalog/products_update_history.tpl');
        die($html);
    }

    private function getReport()
    {
        $cr = $this->_cr;
        ob_start();

        $packageId = Tools::getValue('id');
        $callback = Tools::getValue('callback');

        if (empty($callback) || $callback == '?') {
            $callback = 'jsonp_'.time();
        }

        $username = Configuration::get(parent::KEY.'_USERNAME');
        $password = Configuration::get(parent::KEY.'_PASSWORD');
        $debug = Configuration::get(parent::KEY.'_DEBUG');
        $production = !(Configuration::get(parent::KEY.'_PREPRODUCTION') ? true : false);

        $webservice = new CDiscountWebservice($username, $password, $production, $debug, $this->dev_mode);
        $webservice->token = CDiscountTools::auth();

        if (!$webservice->token) {
            $this->errors[] = sprintf('%s(%s): %s', basename(__FILE__), __LINE__, $this->l('Auth failed')).$cr;

            return (false);
        }

        $params = array();
        $params['PackageID'] = $packageId;

        if (!($offerPackageSubmissionResults = $webservice->GetOfferPackageSubmissionResult($params))) {
            $this->errors[] = sprintf($this->l('SubmitOfferPackage failed...')).$cr;
            $error = true;
        }
        $offerPackageSubmissionResultsOut = array();
        $offerPackageSubmissionResultContent = null;
        $success = false;

        if (is_array($offerPackageSubmissionResults) && reset($offerPackageSubmissionResults) instanceof SimpleXMLElement) {
            $offerPackageSubmissionResultContent
                = sprintf('%-24s %-16s %-16s %-24s %-24s %-10s %s', $this->l('Import Date'), $this->l('Status'), 'EAN', $this->l('Id/Reference'), $this->l('Sku (Marketplace)'), $this->l('Validated'), $this->l('Message'))."\n";

            foreach ($offerPackageSubmissionResults as $offerPackageSubmissionResult) {
                $offerPackageSubmissionResultOut = array();

                $date = CommonTools::displayDate(date('Y-m-d H:i:s', strtotime((string)$offerPackageSubmissionResult->LogDate)), $this->id_lang, true);
                $status = (string)$offerPackageSubmissionResult->OfferIntegrationStatus;

                switch ($status) {
                    case 'Integrated':
                        $status = $this->l('Integrated');
                        break;
                    case 'Rejected':
                        $status = $this->l('Rejected');
                        break;
                }

                $offerPackageSubmissionResultOut['LogDate'] = sprintf('%-24s', $date);
                $offerPackageSubmissionResultOut['OfferIntegrationStatus'] = sprintf('%-16s', $status);
                $offerPackageSubmissionResultOut['ProductEan'] = sprintf('%-16s', (string)$offerPackageSubmissionResult->ProductEan);
                $offerPackageSubmissionResultOut['SellerProductId'] = sprintf('%-24s', (string)$offerPackageSubmissionResult->SellerProductId);
                $offerPackageSubmissionResultOut['Sku'] = sprintf('%-24s', (string)$offerPackageSubmissionResult->Sku);
                $offerPackageSubmissionResultOut['Validated'] = sprintf('%-10s', (string)$offerPackageSubmissionResult->Validated == 'true' ? $this->l('Yes') : $this->l('No'));

                $messages = null;

                if (isset($offerPackageSubmissionResult->PropertyList)) {
                    $result = $offerPackageSubmissionResult->xpath('.//OfferReportPropertyLog');

                    if (is_array($result) && reset($result) instanceof SimpleXMLElement) {
                        foreach ($result as $logitem) {
                            $logrows = explode('|', (string)$logitem->LogMessage);
                            if (isset($logrows[5])) {
                                $messages .= $logrows[5]."\n";
                            }
                        }
                    }
                }
                $offerPackageSubmissionResultOut['LogMessage'] = (string)$messages;
                $offerPackageSubmissionResultContent .= implode(' ', $offerPackageSubmissionResultOut)."\n";

                $offerPackageSubmissionResultsOut[] = $offerPackageSubmissionResultOut;

                array_map('trim', $offerPackageSubmissionResultOut);
            }


            if (count($offerPackageSubmissionResultsOut)) {
                $success = true;
            }
        }

        $console = ob_get_clean();

        $json = Tools::jsonEncode(array(
            'success' => $success,
            'result' => $offerPackageSubmissionResultsOut,
            'content' => $offerPackageSubmissionResultContent,
            'console' => $console
        ));

        echo (string)$callback.'('.$json.')';
        die;
    }

    protected function pd()
    {
        require_once dirname(__FILE__).'/../classes/cdiscount.tools_r.class.php';

        if (Cdiscount::$debug_mode) {
            $backTrace = debug_backtrace();
            $caller = array_shift($backTrace);
            $fileSegment = explode(DIRECTORY_SEPARATOR, $caller['file']);
            $file = array_pop($fileSegment);
            $br = $this->_cr;

            $debug = array_map(function ($arg) use ($file, $caller, $br) {
                return sprintf('%s(#%d): %s', $file, $caller['line'], $arg) . $br;
            }, func_get_args());
            CDiscountToolsR::pre($debug);
        }
    }

    private static function logContent($log)
    {
        if (!empty($log)) {
            self::$logContent .= $log . self::LF;
        }
    }
    
    private static function getLogContent()
    {
        $logContent = self::$logContent;
        self::$logContent = '';
        return $logContent;
    }
}

$MarketplaceProductsCreate = new CDiscountExportProducts;
$MarketplaceProductsCreate->dispatch();
