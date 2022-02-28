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

require_once(dirname(__FILE__).'/env.php');
require_once(dirname(__FILE__).'/../amazon.php');

require_once(dirname(__FILE__).'/../classes/amazon.address.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.order_item.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.order_info.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.order.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.orderhistory.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.cart.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.payment.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.support.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.webservice.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.logger.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.mail.logger.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.remote_cart.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.orders_reports.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.batch.class.php');
require_once(dirname(__FILE__).'/../common/order.class.php');

class AmazonImportOrder extends Amazon
{
    public static $errors     = array();
    public static $warnings   = array();
    public static $messages   = array();
    public static $send_email = false;
    public static $orders     = array();
    
    private static $logContent = '';

    // PS or module configuration
    protected $psCatalogMode = false;
    protected $psStockManagement = false;
    protected $amzFbaDecreaseStock = false;

    protected $psEnableTaxes = false;
    protected $amzMkp2IsoId = array();
    protected $amzAutoCreateProduct;
    protected $amzCurrencyConvert;
    protected $amzIdCustomer;
    protected $amzAccountType = Amazon::ACCOUNT_TYPE_INDIVIDUAL;
    protected $amzCarriers = array();
    protected $amzAmzCarriers = array();    // todo: Better name
    protected $amzNoCombination;
    protected $amzEnableTaxes = true;
    protected $amzTaxComplyEURules = false;
    protected $amzTaxOnBusinessOrder = false;
    protected $amzForceVATRecalculation = false;
    protected $amzOrdersReportManagement;
    protected $amzLang2Region;
    protected $amzRegion2Lang;
    protected $amzPaymentRegion;

    protected $amzOrderStates = array();
    protected $amzPreOrder = false;
    protected $amzOrderStateFBAIncoming = 0;
    protected $amzIncomingStateOfOrderAttrsCombination = array();

    protected $amzExpertMode = false;
    protected $amzIsDemo = false;

    // EU feature?
    protected $amzFtIsEu = false;
    protected $amzMasterMkp;

    // Run time variables
    protected $cronMode;
    /** @var AmazonWebService */
    protected $amazonApi;
    protected $idCustomerGroup;
    protected $moduleInstallOpensi;
    protected $tokenOrders;
    protected $importedCounter = 0;

    // To check if order is special FBA
    private $_marketplaces;         // Amazon languages
    private $_default_tax_rule;     // Default tax rule for each marketplace, use for special FBA order

    public function __construct()
    {
        parent::__construct();

        AmazonContext::restore($this->context);

        // Parent already fetched module features
        $this->amzFtIsEu = isset($this->amazon_features['amazon_europe']) && (bool)($this->amazon_features['amazon_europe']);
        $this->amzMasterMkp = AmazonConfiguration::get(self::CONFIG_MASTER);

        // todo: Constants
        $this->psCatalogMode = (bool)Configuration::get('PS_CATALOG_MODE');
        $this->psStockManagement = (bool)Configuration::get('PS_STOCK_MANAGEMENT');
        $this->amzFbaDecreaseStock = (bool)Configuration::get('AMAZON_FBA_DECREASE_STOCK');
        $this->psEnableTaxes = !Tax::excludeTaxeOption();   // Not used anymore

        $marketPlaceIds = AmazonConfiguration::get('MARKETPLACE_ID');
        $this->amzMkp2IsoId = array_flip($marketPlaceIds);
        // Import unknown products as a new product
        $this->amzAutoCreateProduct = (bool)Configuration::get('AMAZON_AUTO_CREATE');
        $this->amzCurrencyConvert = (bool)Configuration::get('AMAZON_CONVERT_CURRENCY');
        $this->amzIdCustomer = Configuration::get('AMAZON_CUSTOMER_ID');
        $this->idCustomerGroup = $this->resolveIdCustomerGroup();
        $accountType = AmazonConfiguration::get('ACCOUNT_TYPE');
        if (!empty($accountType) && is_numeric($accountType)) {
            // backward compatibility issue.
            $this->amzAccountType = $accountType;
        }

        $this->amzCarriers = AmazonConfiguration::get('CARRIER');
        $this->amzAmzCarriers = AmazonConfiguration::get('AMAZON_CARRIER');
        
        $this->amzNoCombination = (bool)Configuration::get('AMAZON_NO_COMBINATIONS');
        // Use Taxes - Amazon Config overrides PS Config
        $this->amzEnableTaxes = (bool)AmazonConfiguration::get(AmazonConstant::CONFIG_TAXES);
        $this->amzTaxComplyEURules = $this->amzEnableTaxes && AmazonConfiguration::get(AmazonConstant::CONFIG_OI_TAXES_COMPLY_EU_VAT_RULES);
        $this->amzTaxOnBusinessOrder = $this->amzEnableTaxes && AmazonConfiguration::get(AmazonConstant::CONFIG_OI_TAXES_ON_BUSINESS_ORDERS);
        $this->amzForceVATRecalculation = $this->amzEnableTaxes && AmazonConfiguration::get(AmazonConstant::CONFIG_OI_TAXES_FORCE_RECALCULATION);
        
        $this->amzOrderStates = AmazonConfiguration::get('ORDER_STATE');
        $this->amzPreOrder = (bool)Configuration::get(AmazonConstant::CONFIG_LEGACY_PRE_ORDER);
        $this->amzOrderStateFBAIncoming = (int)Configuration::get('AMAZON_FBA_ORDER_STATE');
        $this->amzIncomingStateOfOrderAttrsCombination =
            AmazonConfiguration::get(AmazonConstant::CONFIG_ORDER_STATES_INCOMING_OF_ORDER_ATTRS_COMBINATION);

        $amzFeatures = $this->getAmazonFeatures();
        $this->amzOrdersReportManagement = $amzFeatures['orders_reports'];

        $this->amzLang2Region = AmazonConfiguration::get(AmazonConstant::CONFIG_LANG_TO_REGION);
        $this->amzRegion2Lang = array_flip($this->amzLang2Region);
        $this->amzPaymentRegion = (bool)Configuration::get('AMAZON_PAYMENT_REGION');
        $this->amzExpertMode = $amzFeatures['expert_mode'];
        $this->amzIsDemo = $amzFeatures['demo_mode'] || isset($_SERVER['DropBox']) || Tools::getValue('import_demo');
        
        $this->moduleInstallOpensi = AmazonTools::moduleIsInstalled('opensi');
        $this->tokenOrders = Tools::getValue('token_order');

        require_once dirname(__FILE__) . '/../classes/amazon.cron_failed_order.class.php';
    }

    public static function jsonDisplayExit()
    {
        $output = null;

        if (Amazon::$debug_mode) {
            $output = ob_get_contents();
        }
        if (!count(AmazonImportOrder::$orders)) {
            if (!Amazon::$debug_mode) {
                $result = trim(ob_get_clean());
            } else {
                $result = null;
            }

            if ($result) {
                AmazonImportOrder::$warnings[] = $result;
            }
        } else {
            if (!Amazon::$debug_mode) {
                $result = trim(ob_get_clean());
            } else {
                $result = null;
            }

            if ($result) {
                AmazonImportOrder::$messages[] = $result;
            }
        }

        if ((self::$send_email && count(AmazonImportOrder::$errors) && Tools::getValue('cron')) || (self::$send_email && Amazon::$debug_mode)) {
            $email = null;

            if (count(AmazonImportOrder::$messages)) {
                $email .= sprintf('Messages : ').self::LF;
                foreach (AmazonImportOrder::$messages as $message) {
                    $email .= ' - '.$message.self::LF;
                }
            }
            if (count(AmazonImportOrder::$warnings)) {
                $email .= sprintf('Warnings : ').self::LF;
                foreach (AmazonImportOrder::$warnings as $warning) {
                    $email .= ' - '.$warning.self::LF;
                }
            }
            if (count(AmazonImportOrder::$errors)) {
                $email .= sprintf('Errors : ').self::LF;
                foreach (AmazonImportOrder::$errors as $error) {
                    $email .= ' - '.$error.self::LF;
                }
            }

            if ($output) {
                $email .= sprintf('Orders : ').self::LF;
                $email .= print_r(AmazonImportOrder::$orders, true);

                $email .= sprintf('Output : ').self::LF;
                $email .= $output;
            }
            AmazonMailLogger::message($email);
        }

        foreach (array(
                     AmazonImportOrder::$errors,
                     AmazonImportOrder::$warnings,
                     AmazonImportOrder::$messages
                 ) as $key => $tofix_array) {
            if (is_array($tofix_array) && count($tofix_array)) {
                // Fix rare issues

                $tofix_array[$key] = self::fixEncoding($tofix_array[$key]);
            }
        }

        $json = json_encode(array(
            'orders' => AmazonImportOrder::$orders,
            'count' => count(AmazonImportOrder::$orders),
            'error' => (bool)count(AmazonImportOrder::$errors),
            'errors' => AmazonImportOrder::$errors,
            'warning' => (bool)count(AmazonImportOrder::$warnings),
            'warnings' => AmazonImportOrder::$warnings,
            'message' => count(AmazonImportOrder::$messages),
            'messages' => AmazonImportOrder::$messages
        ));

        if (($callback = Tools::getValue('callback'))) {
            self::logContent('Manual import');
            echo (string)$callback.'('.$json.')';
        } else {
            self::logContent('Cron import');
            echo $json;
            // Save the list of failed order
            $failedOrders = array_filter(self::$orders, function($order) {
                return !$order['status'];
            });
            self::mergePreviousFailedOrders($failedOrders);
        }

        self::logContent($json);
        $logger = new AmazonLogger(AmazonLogger::CHANNEL_ORDER_IMPORT);
        $logger->debug(self::getLogContent());
    }

    public static function fixEncoding(&$array_to_fix)
    {
        if (is_array($array_to_fix) && count($array_to_fix)) {
            foreach ($array_to_fix as $key => $item) {
                if (!mb_check_encoding($item, 'UTF-8')) {
                    $array_to_fix[$key] = mb_convert_encoding($item, "UTF-8");
                }
            }
        }

        return ($array_to_fix);
    }

    private function createProduct($ASIN, $sku, $name, $price)
    {
        if (!AmazonTools::validateSKU($sku) || !AmazonTools::validateASIN($ASIN)) {
            return(false);
        }
        if (AmazonConfiguration::shopIsFeatureActive()) {
            $id_shop = (int)$this->context->shop->id;
        } else {
            $id_shop = null;
        }
        $product = new AmazonProduct($sku, false, (int)$this->context->language->id, 'reference', $id_shop);

        if (Validate::isLoadedObject($product)) {
            return($product);
        }

        $id_lang_default = (int)Configuration::get('PS_LANG_DEFAULT');
        $languages = Language::getLanguages(false);
        $language_array = array();
        $language_array[$id_lang_default] = null;

        $name_array = array();
        $link_array = array();

        foreach ($languages as $language) {
            $id_lang = (int)$language['id_lang'];
            $name_array[$id_lang] = Tools::substr(str_replace(array('<', '>', ';', '=', '#', '{', '}'), '/', $name), 0, 128);
            $link_array[$id_lang] = Tools::substr(Tools::link_rewrite($name_array[$id_lang]), 0, 128) ;
        }
        $reference = Tools::substr($sku, 0, 32);

        if (!Validate::isReference($sku)) {
            return(false);
        }

        $product = new Product();
        $product->name = $name_array;
        $product->reference = $reference;
        $product->active = true;
        $product->available_for_order = true;
        $product->visibility = 'none';
        $product->id_tax_rules_group = 0;
        $product->is_virtual = 0;
        $product->tax_name = null;
        $product->tax_rate = 0;
        $product->price = (float)$price;
        $product->link_rewrite = $link_array;
        $product->id_product_attribute = null;
        if (method_exists('Product', 'getIdTaxRulesGroupMostUsed')) {
            $product->id_tax_rules_group = (int)Product::getIdTaxRulesGroupMostUsed();
        }

        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s - %s::%s - line #%d', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
            CommonTools::p("New Product: ".print_r(get_object_vars($product), true));
        }

        if ($product->validateFields(false, true)) {
            $product->add();

            if (!Validate::isLoadedObject($product)) {
                return(false);
            }

            if (method_exists('StockAvailable', 'setProductOutOfStock')) {
                StockAvailable::setProductOutOfStock((int)$product->id, 1);
            }
            AmazonProduct::updateProductOptions($product->id, $id_lang_default, 'asin1', $ASIN);

            return($product);
        } else {
            return(false);
        }
    }

    public function import()
    {
        $timestart = time();
        $orders_ids = array();

        if (Amazon::$debug_mode) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }
        self::$send_email = (bool)Configuration::get('AMAZON_EMAIL');

        ob_start();

        register_shutdown_function(array('AmazonImportOrder', 'jsonDisplayExit'));

        $this->cronMode = false;
        $currentDate = date('Y-m-d H:i:s');

        // Regions
        //
        $main_region = '??';

        // WGET MODE
        if (Tools::getValue('cron')) {
            $lang = Tools::getValue('lang');
            $cronToken = Tools::getValue('cron_token');

            $cr = nl2br(Amazon::LF); // carriage return

            $this->cronMode = true;
            $this->output('Starting Order Query in WS API/Cron Mode'.' - '.$currentDate);

            $date1 = date('c', strtotime('now - 1 day'));
            $date2 = date('c', strtotime('now - 15 min'));

            if (!isset($this->amzRegion2Lang[$lang]) || !$this->amzRegion2Lang[$lang]) {
                die($this->l('No selected language, nothing to do...'));
            }

            $id_lang = $this->amzRegion2Lang[$lang];
            $main_region = $this->amzLang2Region[$id_lang];

            if (!AmazonTools::checkToken($cronToken)) {
                die('Wrong Token');
            }

            $status = Tools::getValue('status', 'Unshipped');
        } else {
            //
            // Web Mode
            //
            $cr = nl2br(Amazon::LF); // carriage return

            $status = (string)Tools::getValue('statuses');
            // todo: Maybe not used anymore
            $id_lang = (int)Tools::getValue('amazon_lang');
            $tokens = Tools::getValue('amazon_token');

            if ((int)Tools::getValue('europe')) {
                $masterMarketplace = AmazonConfiguration::get(self::CONFIG_MASTER);

                if (isset($this->amzRegion2Lang[$masterMarketplace]) && $this->amzRegion2Lang[$masterMarketplace]) {
                    $id_lang = $this->amzRegion2Lang[$masterMarketplace];
                } else {
                    $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
                }
                $main_region = 'eu';
                $europe = 1;
            } else {
                if (isset($this->amzLang2Region[$id_lang]) && $this->amzLang2Region[$id_lang]) {
                    $main_region = $this->amzLang2Region[$id_lang];
                }
                $europe = 0;
            }


            if (!AmazonTools::checkToken($tokens)) {
                die($this->l('Wrong Token'));
            }

            if (!(int)$id_lang) {
                die($this->l('No selected language, nothing to do...'));
            }
        }

        $log_filename = $this->logInit($main_region);

        $batches = new AmazonBatches('batch_acknowledge');
        $last_import = $batches->getLastForRegion($main_region);

        $id_warehouse = null;
        $id_shop = null;

        if (version_compare(_PS_VERSION_, '1.5', '>')) {
            $employee = null;
            $id_employee = Configuration::get('AMAZON_EMPLOYEE');

            if ($id_employee) {
                $employee = new Employee($id_employee);
            }

            if (!Validate::isLoadedObject($employee)) {
                die($this->l('Wrong Employee, please save the module configuration'));
            }

            $this->context->customer->is_guest = true;
            $this->context->customer->id_default_group = (int)Configuration::get('AMAZON_CUSTOMER_GROUP');
            $this->context->cart = new Cart();
            $this->context->link = new Link(); // added for Mail Alert
            $this->context->employee = $employee;
            $this->context->currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
            $this->context->cart->id_currency = $this->context->currency->id;
            $this->context->cart->id_lang = $this->id_lang;

            if (AmazonConfiguration::shopIsFeatureActive()) {
                $id_shop = (int)$this->context->shop->id;
                $id_shop_group = (int)$this->context->shop->id_shop_group;
            } else {
                $id_shop = null;
            }

            $id_warehouse = (int)Configuration::get('AMAZON_WAREHOUSE');
        }

        $origin_currency = $this->context->currency->iso_code;
        $id_currency = (int)$this->context->currency->id;

        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s - %s::%s - line #%d', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
            CommonTools::p("amazon carriers: ". print_r($this->amzAmzCarriers, true));
        }

        // Currency
        //
        $currencies = AmazonConfiguration::get('CURRENCY');

        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s - %s::%s - line #%d', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
            CommonTools::p("currencies: ". print_r($currencies, true));
        }

        $this->preCheck();

        // Init
        //
        $amazon = AmazonTools::selectPlatforms($id_lang, Amazon::$debug_mode);

        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s - %s::%s - line #%d', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
            CommonTools::p(sprintf('%s%s%s', print_r($amazon['auth'], true), print_r($amazon['params'], true), print_r($amazon['platforms'], true)));
        }

        if (!$this->amazonApi = new AmazonWebService($amazon['auth'], $amazon['params'], $amazon['platforms'], Amazon::$debug_mode, nl2br(Amazon::LF))) {
            echo $this->l('Unable to login').$cr;
            die;
        }
        if ($this->amazon_features['demo_mode']) {
            $this->amazonApi->demo = true;
        }

        /** 
         * Remove checking of AMAZON_CURRENT_VERSION
         * die(AmazonSupport::message($this->l('Module version and configuration mismatch, please edit and save your module configuration'), AmazonSupport::TUTORIAL_AFTER_INSTALLATION));
         */

        // Check the server's clock drift
        $request_time = time();
        $to_date = $request_time - 240;

        $result = $this->amazonApi->serviceStatus(true);

        if (isset($result->GetServiceStatusResult)) {
            if (isset($result->GetServiceStatusResult->Timestamp)) {
                $to_date = min(strtotime((string)$result->GetServiceStatusResult->Timestamp) - 120, $request_time);
            }
        }

        $param_channels = Tools::strtoupper(trim(Tools::getValue('channel')));
        $param_channels = explode(',', $param_channels);

        if ((in_array(self::AFN, $param_channels) && in_array(self::MFN, $param_channels)) 
            || (!in_array(self::AFN, $param_channels) && !in_array(self::MFN, $param_channels))
        ) {
            $channel = '';
        } elseif (in_array(self::AFN, $param_channels)) {
            $channel = self::AFN;
        } elseif (in_array(self::MFN, $param_channels)) {
            $channel = self::MFN;
        } else {
            $channel = '';
        }

        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s - %s::%s - line #%d', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
            CommonTools::p(sprintf('Channel: %s', $channel));
        }

        // Orders reports management
        $date_max = null;

        if ($this->amzOrdersReportManagement) {
            $sql = 'SELECT MAX(`date`) as date_max FROM `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDER_ADDRESS.'`';
            $result = Db::getInstance()->getValue($sql);

            if (Tools::strlen($result)) {
                $timestamp = strtotime($result);
                $now = time();

                if ($timestamp + 86400 < $now) { // cron order reports is not executed or no orders in the table ?
                    $this->amzOrdersReportManagement = false;
                } else {
                    $date_max = date('c', max($now - 86400, $timestamp));
                }
            } else {
                $this->amzOrdersReportManagement = false;
            }
        }

        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s(#%d): - Order Reports management: %s, date_max: %s', basename(__FILE__), __LINE__, $this->amzOrdersReportManagement ? 'Yes' : 'No', $date_max));
        }

        if ($this->cronMode) {
            // Try previous failed first
            $this->logContent('Trying previous failed...');

            $mkp_ids =  $this->amazonApi->getMarketplaces();
            $mkp_ids[] = $this->amazonApi->getMarketplaceId();
            $this->tryPreviousFailedOrders($mkp_ids, $id_lang, $id_shop, $id_currency, $log_filename, $origin_currency, $id_warehouse);

            $returnXML = true;

            if (Tools::getValue('recheck')) {
                // override default parameters - recheck and reimport late orders

                $status = 'Shipped';
                $channel = self::AFN;
                $date1 = date('c', strtotime($date1.' -15 days'));
                $date2 = date('c', strtotime($date2.' -2 days'));
            } elseif (Tools::getValue('doublecheck')) {
                // override default parameters - recheck and reimport late orders

                $status = 'Unshipped';
                $date1 = date('c', strtotime($date1.' -7 days'));
                $date2 = date('c', strtotime($date2.' -1 days'));
            } elseif (!empty($last_import)) {
                if ($this->amazon_features['remote_cart']) {
                    $time_gap = 60 * 60 * 4;//4 hours
                } else {
                    $time_gap = 60 * 15;
                }

                $date1 = date('c', max(strtotime($last_import), time() - 86400) - $time_gap); // last import less drift
                $date2 = date('c', $to_date);
            } else {
                $date2 = date('c', $to_date);
            }

            if ($date_max) {
                if (strtotime($date1) >= strtotime($date2)) {
                    sprintf('%s (due to parameter "Order Reports")'.$cr, $this->l('No orders fetched from Amazon'));

                    if (Amazon::$debug_mode) {
                        CommonTools::p(sprintf('%s - %s::%s - line #%d', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
                        CommonTools::p(sprintf('order reports active: date1: %s, date2: %s', $date1, $date_max));
                    }
                    die;
                }
                $date1 = date('c', strtotime($date_max.' -1 days'));
                $date2 = $date_max;
            }

            echo "Fetching orders from $date1 to $date2 ".$cr;
            $this->logContent("Fetching orders from $date1 to $date2");

            $date1 = gmdate('Y-m-d\TH:i:s\Z', strtotime($date1));
            $date2 = gmdate('Y-m-d\TH:i:s\Z', strtotime($date2));

            if (!$amazonOrders = $this->amazonApi->GetUnshippedOrdersListv4($date1, $date2, $status, $channel, $returnXML, $this->cronMode)) {
                printf($this->l('No orders fetched from Amazon').$cr);
                die;
            }

            if (isset($amazonOrders->Error)) {
                $caller = AmazonTools::callingFunction();

                $message = sprintf('%s : %s', 'Error From', $caller).self::LF;
                $message .= sprintf('%s :', $this->l('Error while retrieving orders')).self::LF;
                $message .= sprintf('Type : %s', $amazonOrders->Error->Type).self::LF;
                $message .= sprintf('Code : %s', $amazonOrders->Error->Code).self::LF;
                $message .= sprintf('Message : %s', $amazonOrders->Error->Message).self::LF;
                $message .= sprintf('Request ID : %s', $amazonOrders->RequestID).self::LF;

                if (self::$send_email) {
                    AmazonMailLogger::message($message);
                }
                die($message);
            }
        } else {
            $orders_ids = Tools::getValue('order_id');

            if (!is_array($orders_ids) || !count($orders_ids)) {
                printf($this->l('Nothing to import...').$cr);
                die;
            }

            if (!$amazonOrders = $this->amazonApi->GetOrders($orders_ids)) {
                printf($this->l('No orders fetched from Amazon').$cr);
                die;
            }
        }

        /**
         * @var  $key
         * @var PlacedOrder $order
         */
        foreach ($amazonOrders as $order) {
            $order_id = (string)$order->AmazonOrderId;
            if (!$this->cronMode && !in_array($order_id, $orders_ids)) {
                continue;
            }

            $this->importOneOrder($order, $id_lang, $id_shop, $id_currency, $log_filename, $origin_currency, $id_warehouse);
        }

        // Save Session
        $batches = new AmazonBatches('batch_acknowledge');
        $batch = new AmazonBatch($timestart);
        $batch->id = uniqid();
        $batch->timestop = time();
        $batch->type = $this->cronMode ? $this->l('Cron') : $this->l('Manual');
        $batch->region = $main_region;
        $batch->created = $this->importedCounter;
        $batch->updated = 0;
        $batch->deleted = 0;
        $batches->add($batch);
        $batches->save();

        // Acknowledge
        if (!$this->moduleFeatures->dev_mode) {
            if (is_array(AmazonImportOrder::$orders) && count(AmazonImportOrder::$orders) && $this->importedCounter) {
                $submissionFeedId = $this->amazonApi->acknowledgeOrders(AmazonImportOrder::$orders, $this->amzIsDemo);
                $this->saveAcknowledgeFeed($this->amazonApi->getLastFeedContent(), $submissionFeedId);
                if ($submissionFeedId && $this->amzExpertMode && !$this->amzIsDemo) {
                    $batches = new AmazonBatches('batch_acknowledge');
                    $batch = new AmazonBatch($timestart);
                    $batch->id = $submissionFeedId;
                    $batch->timestop = time();
                    $batch->type = 'Acknowledge (Orders)';
                    $batch->region = $main_region;
                    $batch->created = 0;
                    $batch->updated = $this->importedCounter;
                    $batch->deleted = 0;
                    $batches->add($batch);
                    $batches->save();
                }
            }
        }

        // Remote Cart - cleanup
        if ($this->amazon_features['remote_cart'] && $this->psStockManagement && AmazonRemoteCart::tableExists()) {
            $expireds = AmazonRemoteCart::expiredCarts();

            if (is_array($expireds) && count($expireds)) {
                foreach ($expireds as $expired) {
                    $mp_order_id = $expired['mp_order_id'];
                    $sku = $expired['reference'];
                    $quantity = $expired['quantity'];

                    $product = new AmazonProduct($sku, false, $id_lang, 'reference', $id_shop);

                    if (!Validate::isLoadedObject($product)) {
                        continue;
                    }

                    // Restore stock
                    if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                        StockAvailable::updateQuantity($product->id, $product->id_product_attribute ? $product->id_product_attribute : null, $quantity, $id_shop);
                    } else {
                        $productQuantity = Product::getQuantity($product->id, $product->id_product_attribute ? $product->id_product_attribute : null);
                        AmazonProduct::updateProductQuantity($product->id, $product->id_product_attribute ? $product->id_product_attribute : null, $productQuantity + $quantity);
                    }

                    if ($log_filename) {
                        file_put_contents($log_filename, sprintf('%s - Remove expired product from cart  - order: %s, sku: %s, quantity: %s'.Amazon::LF, date('c'), $mp_order_id, $sku, $quantity), FILE_APPEND);
                    }

                    AmazonRemoteCart::removeFromCart($mp_order_id, $sku);
                }
            }
        }
    }

    protected function importOneOrder($order, $id_lang, $id_shop, $id_currency, $log_filename, $origin_currency, $id_warehouse)
    {
        $order_id = (string)$order->AmazonOrderId;
        $processOrder = $this->importOneOrderMainLogic($order, $id_lang, $id_shop, $id_currency, $log_filename, $origin_currency, $id_warehouse);
        $processOrderStatus = $processOrder['status'];
        if ($processOrderStatus !== 1) {
            $failedReason = '';
            if (isset($processOrder['warning'])) {
                AmazonImportOrder::$warnings[] = $processOrder['warning'];
                $failedReason = $processOrder['raw_warning'];
            }
            if (isset($processOrder['error'])) {
                AmazonImportOrder::$errors[] = $processOrder['error'];
                $failedReason = $processOrder['raw_error'];
            }
            $orderResult = array('status' => false, 'reason' => $failedReason);
        } else {
            $orderResult = $processOrder['order_data_result'];
            $orderResult['status'] = true;
            $this->importedCounter++;
        }
        $orderResult['purchase_date'] = $order->PurchaseDate;
        $orderResult['marketplace'] = $order->MarketPlaceId;

        // Resolve order if it was recorded as failed earlier
        if ($processOrderStatus === 1 || $processOrderStatus === -1) {
            $previousFailedOrders = AmazonCronFailedOrder::getAllCronFailedOrders();
            if (isset($previousFailedOrders[$order_id])) {
                unset($previousFailedOrders[$order_id]);
                Configuration::updateValue(AmazonConstant::IMPORT_ORDERS_CRON_FAILED_LIST, json_encode($previousFailedOrders));
            }
        }

        // prevent overriding when tryPreviousFailedOrders imported the order successfully, to acknowledge later
        if (!isset(AmazonImportOrder::$orders[$order_id]) || !AmazonImportOrder::$orders[$order_id]['status']) {
            AmazonImportOrder::$orders[$order_id] = $orderResult;
        }
    }

    /**
     * @param PlacedOrder $order
     * @param $id_lang
     * @param $id_shop
     * @param $id_currency
     * @param $log_filename
     * @param $origin_currency
     * @param $id_warehouse
     * @return array(
     *  status => int {0:false, 1:success, -1:resolve (cancelled, imported, AFN no price)},
     *  [warning => str], [error => str], [order_data_result => array])
     */
    protected function importOneOrderMainLogic($order, $id_lang, $id_shop, $id_currency, $log_filename, $origin_currency, $id_warehouse)
    {
        $orderDataResult = array();

        $order_id = (string)$order->AmazonOrderId;
        $this->output("Importing order: $order_id");
        $preorder_switch = false;

        // Skipping Cancelled Orders
        if ((string)$order->OrderStatus == AmazonOrder::ORDER_CANCELED) {
            return array('status' => -1, 'raw_warning' => 'Skipping Canceled Order', 'warning' => $this->l('Skipping Canceled Order'));
        }

        $pending_order = AmazonOrder::ORDER_PENDING == (string)$order->OrderStatus;
        $this->pd("Pending order: " . $pending_order ? 'Yes' : 'No', print_r(get_object_vars($order), true));

        // Skip already imported order
        if (AmazonOrder::checkByMpId($order_id)) {
            return array('status' => -1, 'raw_warning' => 'Has already been imported...', 'warning' => sprintf($this->l('Order ID (%s) has already been imported...'), $order_id));
        }

        // Check order items
        if (!($Items = $this->amazonApi->getOrderItems($order_id))) {
            return array('status' => 0, 'raw_error' => 'Unable to retrieve items. Details: ' . $this->amazonApi->getSimpleLog(), 'error' => $this->l('Unable to retrieve items for Amazon Order').': '.$order_id);
        }
        $preCheckItems = $this->preCheckItems($Items, $order->AmazonOrderId, $this->amzAutoCreateProduct, $id_shop, $id_lang);
        $Items = $preCheckItems['items'];
        if (!$preCheckItems['pass']) {
            return array('status' => 0, 'raw_error' => $preCheckItems['reason'], 'error' => $preCheckItems['reason']);
        }

        $id_currency_from = null;
        
        $fulfilment_channel = (string)$order->FulfillmentChannel;
        if($fulfilment_channel == Amazon::AFN 
            && 'Non-Amazon' == (string)$order->SalesChannel 
            && !$order->OrderTotalCurrency){
            return array('status' => -1, 'raw_warning' => 'AFN order without price', 'warning' => sprintf($this->l("Order ID (%s) is AFN and there's no price..."), $order_id));
        }

        if (!$pending_order) {
            // Langue de la Commande
            if (isset($this->amzMkp2IsoId[$order->MarketPlaceId])) {
                $id_lang = $this->amzMkp2IsoId[$order->MarketPlaceId];
            }

            // Amazon Region (fr, de, ...)
            $region = isset($this->amzLang2Region[$id_lang]) ? Tools::strtoupper($this->amzLang2Region[$id_lang]) : null;

            if (empty($order->Address->Name) || empty($order->Address)) {
                $order->Address->Name = $order->BuyerName;
                $order->Address->AddressLine1 = $this->l('No Address');
                $order->Address->City = $this->l('Unknown');
                $order->Address->PostalCode = '1000';
                $order->Address->CountryCode = $region;
            }
            if (!isset($order->BuyerEmail) || empty($order->BuyerEmail)) {
                $order->BuyerEmail = sprintf('amz-%s@%s', $order->AmazonOrderId, str_replace('@', '', Amazon::TRASH_DOMAIN));
            }

            $id_currency = (int)Currency::getIdByIsoCode($order->OrderTotalCurrency);
            
            //if order includes no amount/currency
            if(!$id_currency 
            && !$order->OrderTotalCurrency
            && $fulfilment_channel == Amazon::AFN ){
                //default currency will rely on marketplace configuration
                $currencies = AmazonConfiguration::get('CURRENCY');
                $isocode = $currencies[$id_lang];
                $id_currency = (int)Currency::getIdByIsoCode($isocode);
            }

            $currency = $this->checkCurrency($id_currency);
            if ($currency) {
                $this->context->currency = $currency;
            } else {
                return array('status' => 0, 'raw_error' => 'Unable to load currency', 'error' => $this->l('Unable to load currency') . ': ' . $order->OrderTotalCurrency);
            }

            $id_currency_from = $id_currency;
            if ($this->context->cart->id_currency != $id_currency && $this->amzCurrencyConvert) {
                $id_currency = $this->context->cart->id_currency;
            }

            if ($this->amzOrdersReportManagement) {
                $addresses = AmazonAddress::getAmazonBillingAddress($order_id);

                if (isset($addresses->shipping_address) && $addresses->shipping_address instanceof AmazonWsAddress) {
                    $order->Address = $addresses->shipping_address;
                    $this->pd('Delivery Address:', print_r(get_object_vars($addresses->shipping_address), true));
                }
            }

            // Customer
            $resolveCustomer = $this->resolveCustomer($order, $id_lang, $order_id);
            if (!$resolveCustomer['status']) {
                return array('status' => 0, 'raw_error' => $resolveCustomer['reason'], 'error' => $resolveCustomer['reason']);
            }
            $id_customer = $resolveCustomer['id_customer'];

            $shipping_address_id = $this->resolveShippingAddress($id_customer, $id_lang, $order);
            $billing_address_id = $shipping_address_id;
            if (!$shipping_address_id) {
                return array('status' => 0, 'raw_error' => 'Address creation failed', 'error' => sprintf($this->l('Address creation failed for order #%s'), $order->AmazonOrderId));
            }
            if ($this->amzOrdersReportManagement && isset($addresses->billing_address) && $addresses->billing_address instanceof SimpleXMLElement) {
                $billing_address = new AmazonAddress();
                $billing_address->id_customer = $id_customer;
                $billing_address_id = $billing_address->lookupOrCreateamazonAddress($id_lang, $addresses->billing_address);
            }

            // todo: Use lately
            $date_add = date('Y-m-d H:i:s', strtotime($order->PurchaseDate));
            $shipping_method = trim((string)$order->ShipServiceLevel);

            // todo: Should do earlier, after $id_lang?
            if (!array_key_exists($id_lang, $this->amzAmzCarriers)) {
                return array('status' => 0, 'raw_error' => 'Missing carrier mapping', 'error' => sprintf('%s#%d: '.'Missing carrier mapping for lang id %d', basename(__FILE__), __LINE__, $id_lang));
            }
            
            $count_carrier = is_array($this->amzAmzCarriers[$id_lang]) ? count($this->amzAmzCarriers[$id_lang]) : 0;
            for ($i = 0, $id_carrier = 0; $i < $count_carrier; $i++) {
                if (array_key_exists($i, $this->amzAmzCarriers[$id_lang]) && md5($shipping_method) == $this->amzAmzCarriers[$id_lang][$i]) {
                    $id_carrier = $this->amzCarriers[$id_lang][$i];
                }
            }
            
            $this->pd('Carriers: ' . print_r($this->amzAmzCarriers[$id_lang], true));
            $this->pd('Shipping method: ' . print_r($shipping_method, true));
            $this->pd('id_carrier: ' . print_r($id_carrier, true));

            // todo: Separate function
            if (!$id_carrier) {
                return array('status' => 0, 'raw_error' => 'Unable to associate the carrier', 'error' => AmazonSupport::message(sprintf($this->l('Unable to associate the carrier (%s) for order #%s'), $shipping_method, $order->AmazonOrderId), AmazonSupport::FUNCTION_IMPORT_CARRIER_MAPPING));
            }
        }

        $channel = (string)$order->FulfillmentChannel;
        $sales_channel = (string)$order->SalesChannel;
        $order_channel = (string)$order->OrderChannel;
        $marketplace_id = (string)$order->MarketPlaceId;
        $buyer_name = (string)$order->BuyerName;
        $is_europe = AmazonTools::isEuropeMarketplaceId($marketplace_id);

        $earliest_ship_date = $order->EarliestShipDate;
        $latest_ship_date = $order->LatestShipDate;
        $earliest_delivery_date = $order->EarliestDeliveryDate;
        $latest_delivery_date = $order->LatestDeliveryDate;

        // Not used $order->ShipmentServiceLevelCategory
        $is_prime = $order->IsPrime;
        $is_premium = $order->IsPremiumOrder;
        $is_business = $order->IsBusinessOrder;

        $status = (string)$order->OrderStatus;

        $itemDetails = array();

        if (!$pending_order) {
            // Building Cart
            //
            $cart = new AmazonCart();
            AmazonCart::$debug_mode = Amazon::$debug_mode;
            $cart->id_address_delivery = $shipping_address_id;
            $cart->id_address_invoice = $billing_address_id;
            $cart->id_carrier = $id_carrier;
            $cart->id_currency = $id_currency;
            $cart->id_customer = $id_customer;
            $cart->id_lang = $id_lang;
            $cart->tax_on_business_order = $this->amzTaxOnBusinessOrder;

            if (($validation_message = $cart->validateFields(false, true)) !== true) {
                $this->deleteCart($cart);
                return array('status' => 0, 'raw_error' => 'Field Validation failed for this cart', 'error' => sprintf('%s#%d: '.'Field Validation failed for this cart (Order: %s) - Reason: %s', basename(__FILE__), __LINE__, $order->AmazonOrderId, $validation_message));
            }

            $cart->amazon_order_info = new AmazonOrderInfo;
            $cart->amazon_order_info->mp_order_id = $order_id;
            $cart->amazon_order_info->mp_status = $status;
            $cart->amazon_order_info->channel = $channel;
            $cart->amazon_order_info->marketplace_id = Tools::substr($marketplace_id, 0, 16);
            $cart->amazon_order_info->buyer_name = Tools::substr($buyer_name, 0, 32);
            $cart->amazon_order_info->sales_channel = Tools::substr($sales_channel, 0, 32);
            $cart->amazon_order_info->order_channel = Tools::substr($order_channel, 0, 32);
            $cart->amazon_order_info->ship_service_level = Tools::substr($shipping_method, 0, 32);
            $cart->amazon_order_info->is_prime = (bool)$is_prime;
            $cart->amazon_order_info->is_premium = (bool)$is_premium;
            $cart->amazon_order_info->is_business = (bool)$is_business;
            $cart->amazon_order_info->earliest_ship_date = $earliest_ship_date;
            $cart->amazon_order_info->latest_ship_date = $latest_ship_date;
            $cart->amazon_order_info->earliest_delivery_date = $earliest_delivery_date;
            $cart->amazon_order_info->latest_delivery_date = $latest_delivery_date;
            // Set tax for FBA
            $cart->tax_for_fba = $this->taxForFBA($sales_channel, $channel, $id_lang);

            $cart->add();
        }

        // todo: Consider use shipping country instead of $sales_channel
        $euSaleToOtherCountry = $this->euSaleToOtherCountry($is_europe, $sales_channel);
        $this->output("EU sale to other country: $euSaleToOtherCountry");
        $totalQuantity = $totalSaleableQuantity = 0;
        $mpStatusId = constant('AmazonOrder::'.Tools::strtoupper($status));
        $total_shipping = 0;
        $total_shipping_tax = 0;
        $i = 0;
        $productTax = null;

        /** @var OrderedItem $item */
        foreach ($Items as $item) {
            $quantity = (int)$item->QuantityOrdered;

            if ((float)$item->ShippingDiscountAmount && is_numeric($item->ShippingDiscountAmount)) {
                $shipping_discount = (float)$item->ShippingDiscountAmount;
            } else {
                $shipping_discount = 0;
            }

            if ($id_currency_from != $id_currency && $this->amzCurrencyConvert) {
                $from_currency = new Currency($id_currency_from);

                $id_currency = $this->context->cart->id_currency;
                $discount = Tools::ps_round(Tools::convertPrice((float)$item->PromotionDiscountAmount, $from_currency, false), 2);
                $price = Tools::ps_round(Tools::convertPrice((float)($item->ItemPriceAmount - ($discount ? $discount : 0)) / $quantity, $from_currency, false), 2);
                $giftwrap = $item->GifWrapPrice ? Tools::ps_round(Tools::convertPrice((float)$item->GifWrapPrice, $from_currency, false), 2) : null;
                $shipping_price = (float)$item->ShippingPriceAmount ? Tools::ps_round(Tools::convertPrice((float)$item->ShippingPriceAmount - $shipping_discount, $from_currency, false), 2) : null;
                $item_tax = (float)$item->TaxesInformation->ItemTaxAmount ? Tools::ps_round(Tools::convertPrice((float)$item->TaxesInformation->ItemTaxAmount, $from_currency, false), 2) : null;
                $shipping_tax = (float)$item->TaxesInformation->ShippingTaxAmount ? Tools::ps_round(Tools::convertPrice((float)$item->TaxesInformation->ShippingTaxAmount, $from_currency, false), 2) : null;
                $giftwrap_tax = (float)$item->TaxesInformation->GiftWrapTaxAmount ? Tools::ps_round(Tools::convertPrice((float)$item->TaxesInformation->GiftWrapTaxAmount, $from_currency, false), 2) : null;

                if (Amazon::$debug_mode) {
                    CommonTools::p("Cart:");
                    CommonTools::p(sprintf('%s - %s::%s - line #%d', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
                    CommonTools::p(sprintf('currency conversion: %d/%d', $id_currency_from, $id_currency));
                    CommonTools::p(sprintf('price: %.02f/%.02f', (float)($item->ItemPriceAmount - $discount) / $quantity, $price));
                }
            } else {
                $discount = (float)$item->PromotionDiscountAmount;
                $price = (float)(((float)($item->ItemPriceAmount) - (float)$discount) / $quantity);
                $giftwrap = $item->GifWrapPrice ? (float)$item->GifWrapPrice : null;
                $shipping_price = (float)$item->ShippingPriceAmount - $shipping_discount;
                $item_tax = (float)$item->TaxesInformation->ItemTaxAmount;
                $shipping_tax = (float)$item->TaxesInformation->ShippingTaxAmount;
                $giftwrap_tax = (float)$item->TaxesInformation->GiftWrapTaxAmount;
            }
            // Dec-05-2018: Amazon product has tax if it has any type of taxes
            $amazon_has_tax = $item_tax || $shipping_tax || $giftwrap_tax;

            $total_shipping += $shipping_price;
            $total_shipping_tax += $shipping_tax;
            $totalQuantity += (int)$item->QuantityOrdered;
            $product_name = (string)$item->Title;
            $SKU = trim((string)$item->SKU);
            $ASIN = trim((string)$item->ASIN);
            $giftmsg = $item->GiftMessageText ? (string)$item->GiftMessageText : null;
            $order_item_id = $item->OrderItemId;
            $auto_create_import = false;

            $product = new AmazonProduct($SKU, false, $id_lang, 'reference', $id_shop);

            if ($this->amzAutoCreateProduct && !Validate::isLoadedObject($product)) {
                $new_product = $this->createProduct($ASIN, $SKU, $product_name, $price);

                if (!Validate::isLoadedObject($new_product)) {
                    AmazonImportOrder::$errors[] = sprintf('%s#%d: '.$this->l('Unable to create product for order #%s product ASIN: %s SKU: %s'), basename(__FILE__), __LINE__, $order->AmazonOrderId, $item->ASIN, $SKU);
                    unset($itemDetails[$SKU]);
                    continue;
                }
                $product = $new_product;
                $auto_create_import = true;
            }

            if (!Validate::isLoadedObject($product)) {
                /*
                 * This error can happen if the product has not been loaded, recurring case: the field title or description is not filled for the target language.
                 */
                AmazonImportOrder::$errors[] = AmazonSupport::message(sprintf('%s#%d: '.$this->l('Unable to find the expected product for order #%s product ASIN: %s SKU: %s'), basename(__FILE__), __LINE__, $order->AmazonOrderId, $item->ASIN, $SKU), AmazonSupport::FUNCTION_IMPORT_UNKNOWN_SKU);
                unset($itemDetails[$SKU]);
                continue;
            }
            $id_product = (int)$product->id;

            if (Amazon::$debug_mode) {
                CommonTools::p(sprintf('%s - %s::%s - line #%d', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
                CommonTools::p("Product: ".print_r(get_object_vars($product), true));
            }

            if (($validation_message = $product->validateFields(false, true)) !== true) {
                AmazonImportOrder::$errors[] = sprintf('%s#%d: '.'Field Validation failed for this product (Order: %s, SKU: %s) - Reason: %s', basename(__FILE__), __LINE__, $order->AmazonOrderId, $SKU, $validation_message);

                unset($itemDetails[$SKU]);
                continue;
            }

            if ($product->active === '') {
                AmazonImportOrder::$errors[] = sprintf('%s(%d): Invalid Product Sheet - product: "%s".', basename(__FILE__), __LINE__, $SKU);
                unset($itemDetails[$SKU]);
                continue;
            }

            if (!(bool)$product->active) {
                AmazonImportOrder::$errors[] = AmazonSupport::message(sprintf($this->l('Unable to import inactive product "%s" - Please activate this product prior to import the order.'), $SKU), AmazonSupport::FUNCTION_IMPORT_INACTIVE_UNAVAILABLE);
                unset($itemDetails[$SKU]);
                continue;
            }

            if (isset($product->available_for_order) && !$product->available_for_order) {
                AmazonImportOrder::$errors[] = AmazonSupport::message(sprintf($this->l('Unable to import unavailable product "%s" - Please set "available product" to yes for this product prior to import the order.'), $SKU), AmazonSupport::FUNCTION_IMPORT_INACTIVE_UNAVAILABLE);
                unset($itemDetails[$SKU]);
                continue;
            }

            if (!$product->id_product_attribute && isset($product->minimal_quantity) && $product->minimal_quantity > 1) {
                AmazonImportOrder::$errors[] = AmazonSupport::message(sprintf($this->l('Unable to import product \"%s\" for order #%s. The product has a minimum orderable quantity, that is not compatible with marketplaces modules'), $SKU, $order->AmazonOrderId), AmazonSupport::FUNCTION_IMPORT_INACTIVE_UNAVAILABLE);
                unset($itemDetails[$SKU]);
                continue;
            }

            $cart_management = $this->amazon_features['remote_cart']
                && (!($this->moduleFeatures->fba && $channel == Amazon::AFN) || $this->amzFbaDecreaseStock)
                && $this->psStockManagement && AmazonRemoteCart::tableExists();

            if (Amazon::$debug_mode) {
                CommonTools::p(sprintf('%s - %s::%s - line #%d', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
                CommonTools::p("FBA: ". ($this->moduleFeatures->fba ? 'Yes' : 'No'));
                CommonTools::p("Channel: ".$channel);
                CommonTools::p("Remote Cart: ". (AmazonRemoteCart::tableExists() ? 'Yes' : 'No'));
                CommonTools::p("cart management: ". ($cart_management ? 'Yes' : 'No'));
                CommonTools::p("stock management: ". ($this->psStockManagement ? 'Yes' : 'No'));
                CommonTools::p("log file: ".$log_filename);
            }

            // Handle Remote Cart - reserve product for pending orders on Amazon
            //
            if ($cart_management && $pending_order) {
                if (Amazon::$debug_mode) {
                    CommonTools::p(sprintf('%s - %s::%s - line #%d', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
                    CommonTools::p("case: cart_management & pending_order");
                }

                if (!AmazonRemoteCart::inCart($order_id, $SKU)) {
                    if ($log_filename) {
                        file_put_contents($log_filename, sprintf('%s - Adding product to remote cart - order: %s, sku: %s, quantity: %s, date: %s'.Amazon::LF, date('c'), $order_id, $SKU, $quantity, $order->PurchaseDate), FILE_APPEND);
                    }

                    AmazonRemoteCart::addCart($order_id, $SKU, $quantity, strtotime($order->PurchaseDate));

                    // Decrease stock
                    if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                        StockAvailable::updateQuantity($id_product, $product->id_product_attribute ? $product->id_product_attribute : null, $quantity * -1, $id_shop);
                    } else {
                        $productQuantity = Product::getQuantity((int)$id_product, $product->id_product_attribute ? $product->id_product_attribute : null);
                        AmazonProduct::updateProductQuantity($id_product, $product->id_product_attribute ? $product->id_product_attribute : null, $productQuantity - $quantity);
                    }
                } else {
                    if ($log_filename) {
                        file_put_contents($log_filename, sprintf('%s - Keep product in cart - order: %s, sku: %s, quantity: %s, date: %s'.Amazon::LF, date('c'), $order_id, $SKU, $quantity, $order->PurchaseDate), FILE_APPEND);
                    }

                    AmazonRemoteCart::updateCart($order_id, $SKU, $quantity, strtotime($order->PurchaseDate));
                }
                continue; // Important, we do not proceed the order as it is in pending state.
            } elseif ($cart_management) {
                if (AmazonRemoteCart::inCart($order_id, $SKU)) {
                    AmazonRemoteCart::removeFromCart($order_id, $SKU);

                    if ($log_filename) {
                        file_put_contents($log_filename, sprintf('%s - Remove from cart - order: %s, sku: %s, quantity: %s, date: %s'.Amazon::LF, date('c'), $order_id, $SKU, $quantity, $order->PurchaseDate), FILE_APPEND);
                    }

                    // Restore stock
                    if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                        StockAvailable::updateQuantity($id_product, $product->id_product_attribute ? $product->id_product_attribute : null, $quantity, $id_shop);
                    } else {
                        $productQuantity = Product::getQuantity((int)$id_product, $product->id_product_attribute ? $product->id_product_attribute : null);
                        AmazonProduct::updateProductQuantity($id_product, $product->id_product_attribute ? $product->id_product_attribute : null, $productQuantity + $quantity);
                    }
                }
            }

            if ($pending_order) { // !!! Important, do not import pending orders !
                continue;
            }

            if ($this->context->cart->id_currency != $id_currency_from && $this->amzCurrencyConvert) {
                $display_currency = sprintf('%s &gt; %s', $item->ItemPriceCurrency, $origin_currency);
            } else {
                $display_currency = $item->ItemPriceCurrency;
            }
            $orderDataResult['products'][$i] = array(
                'SKU' => $SKU,
                'ASIN' => $item->ASIN,
                'OrderItemId' => $order_item_id,
                'product' => $product_name,
                'quantity' => $quantity,
                'currency' => $display_currency,
                'price' => AmazonTools::displayPrice($price * $quantity, $id_currency, false, $this->context),
                'id_product' => $product->id,
                'id_product_attribute' => $product->id_product_attribute,
            );

            // 2020-09-29: Each customization now expected to be separate row.
            // 2020-10-01: Keep the complete data
            $customization = $item->Customization;
            $hasCustomization = is_array($customization);

            // 2021-07-22: Remove the case item already exists, that should not happen?
            // Create new item
            $itemKey = $hasCustomization ? $order_item_id : 'cs_non_customization';
            // Additional information: DeemedResellerCategory, IossNumber
            $additionalInfo = array();
            if ($item->DeemedResellerCategory) {
                $additionalInfo['DeemedResellerCategory'] = array('display_name' => 'Deemed Reseller Category', 'value' => $item->DeemedResellerCategory);
            }
            if ($item->IossNumber) {
                $additionalInfo['IossNumber'] = array('display_name' => 'Ioss Number', 'value' => $item->IossNumber);
            }

            // Calculate product tax
            if ($this->amzTaxComplyEURules) {
                // Calculate just one time. todo: This should be the right approach
                if (!$productTax) {
                    $productTax = $this->resolvePsTaxRuleToAdaptEURules($is_business, $this->saleToAnotherCountry($sales_channel), $price * $quantity, $item_tax, $shipping_address_id, $amazon_has_tax, $cart, $product);
                }
            } else {
                $productTax = $this->calculateProductTax($is_business, $euSaleToOtherCountry, $price * $quantity, $item_tax, $amazon_has_tax, $product, $shipping_address_id, isset($cart) ? $cart : null);
            }
            $itemDetails[$SKU]['cs_items'][$itemKey] = array(
                'id' => $SKU,
                'qty' => $quantity,
                'price' => $price,
                'name' => $product_name,
                'dummy' => $auto_create_import,
                'giftwrap' => $giftwrap,
                'giftmsg' => $giftmsg,
                // 2021-07-22: Move total 'shipping' + 'amazon_shipping_tax' to cart, no need to put them in each product
                'fees' => 0,
                'amazon_has_tax' => !$this->amzForceVATRecalculation && $amazon_has_tax,
                'amazon_item_tax' => $item_tax,
                'amazon_giftwrap_tax' => $giftwrap_tax,
                // These 5 params below are used in FBA tax or CommonCart:marketplaceGetTaxRate() or normal product tax
                'tax_rate'              => $productTax->rate,
                'id_tax_rules_group'    => $productTax->id_tax_rules_group,
                'id_tax'                => $productTax->id_tax,
                'id_product'            => $product->id,
                'id_address_delivery'   => $shipping_address_id,
                'order_item_id' => $order_item_id,
                'asin' => $item->ASIN,
                'europe' => $is_europe,
                'is_business' => $is_business,          // todo: Same for all items in order
                'customization' => $customization,
                'additional_info' => $additionalInfo,
            );

            if (Amazon::$debug_mode) {
                CommonTools::p("Order Info:");
                CommonTools::p(sprintf('%s - %s::%s - line #%d - itemDetails', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
                CommonTools::p($itemDetails[$SKU]);
            }

            // Product Combinations
            //
            if (!$this->amzNoCombination && version_compare(_PS_VERSION_, '1.5', '<')) {
                $combinations = $product->getAttributeCombinaisons($id_lang);
            } else {
                $combinations = $product->getAttributeCombinations($id_lang);
            }

            if (Amazon::$debug_mode) {
                CommonTools::p("Combinations:");
                CommonTools::p(sprintf('%s - %s::%s - line #%d - %s', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__, print_r($combinations, true)));
            }
            $id_product_attribute = 0;
            $minimal_quantity = $product->minimal_quantity;

            if ($combinations) {
                foreach ($combinations as $combination) {
                    if (trim($combination['reference']) == $SKU) {
                        $id_product_attribute = (int)$combination['id_product_attribute'];
                        $itemDetails[$SKU]['id_product_attribute'] = $id_product_attribute;
                        $minimal_quantity = $combination['minimal_quantity'];
                        break;
                    }
                }
            }

            if ($minimal_quantity > 1) {
                AmazonImportOrder::$errors[] = AmazonSupport::message(sprintf('%s - %s (%d/%d)', $this->l('Couldn\'t import a product with a minimal quantity greater than 1'), $order_id, $id_product, $id_product_attribute), AmazonSupport::FUNCTION_IMPORT_INACTIVE_UNAVAILABLE);
                unset($itemDetails[$SKU]);
                continue;
            }

            if (!$this->psStockManagement) {
                $productQuantity = $quantity;
            } else {
                if (version_compare(_PS_VERSION_, '1.5', '<')) {
                    $productQuantity = Product::getQuantity((int)$id_product, $id_product_attribute ? $id_product_attribute : null);
                } else {
                    $productQuantity = Product::getRealQuantity($id_product, $id_product_attribute ? $id_product_attribute : null, $id_warehouse, $id_shop);
                }
            }

            $restockBeforeAddCart = $this->restockBeforeAddCart(
                $auto_create_import, $channel,
                $id_shop, $id_product, $id_product_attribute,
                $product->out_of_stock, $productQuantity, $quantity
            );
            if (!$restockBeforeAddCart) {
                AmazonImportOrder::$errors[] = sprintf('%s ASIN: %s SKU: %s Order: #%s', $this->l('Not enough stock to import this product'), $item->ASIN, $SKU, $order_id);
                unset($itemDetails[$SKU]);
                continue;
            }

            $preorder_switch = $preorder_switch || $this->isPreOrder($product->available_date, $order->PurchaseDate);

            if ($cart->updateQty($quantity, $id_product, $id_product_attribute) < 0) {
                AmazonImportOrder::$errors[] = $this->l('Couldn\'t update cart quantity: not enough stock ?').' (ASIN:'.$item->ASIN.' SKU:'.$SKU.' Order: #'.$order->AmazonOrderId.')';
                unset($itemDetails[$SKU]);
                continue;
            }

            if (Amazon::$debug_mode) {
                CommonTools::p("Cart:");
                CommonTools::p(sprintf('%s - %s::%s - line #%d', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
                CommonTools::p(get_object_vars($cart));
            }

            $i++;

            $totalSaleableQuantity += (int)$item->QuantityOrdered;
        }

        if ($pending_order) { // order in cart on amazon side, do not import
            return array('status' => 0, 'raw_warning' => 'Ignore pending order', 'warning' => sprintf('Cart management: Ignore pending order: %s', $order_id));
        }
        if ($totalQuantity != $totalSaleableQuantity) {
            $this->deleteCart($cart);
            return array('status' => 0, 'raw_error' => 'Product count mismatch', 'error' => $this->l('Skipping Order: Product count mismatch').' ('.$order->AmazonOrderId.')');
        }
        if (!count($itemDetails)) {
            $this->deleteCart($cart);
            return array('status' => 0, 'raw_error' => 'No products for this order', 'error' => $this->l('Skipping Order: No products for this order').' ('.$order->AmazonOrderId.')');
        }

        $this->pd(print_r($itemDetails, true), 'Cart:', print_r(get_object_vars($cart), true));

        // Using price, shipping details etc... from the Market Place
        require_once dirname(__FILE__) . '/../classes/order_import/amazon.oi.order.shipping.php';
        $amzOIShipping = new AmazonOrderImportingOrderShipping($marketplace_id, $total_shipping, $total_shipping_tax);
        $cart->amazonProducts = $itemDetails;
        $cart->amazonChannel = $channel;
        // todo: This should be the right approach
        if ($this->amzTaxComplyEURules) {
            $shippingTax = $this->calculateShippingPricesByProductTax($amzOIShipping->getPriceTaxIncl(), $amzOIShipping->getTaxAmount(), $productTax);
        } else {
            $shippingTax = $this->calculateShippingTax($is_business, $euSaleToOtherCountry, $amzOIShipping->getPriceTaxIncl(), $amzOIShipping->getTaxAmount(), $shipping_address_id, $cart);
        }
        $cart->amzShippingPriceTaxIncl = $shippingTax->taxIncl;
        $cart->amzShippingPriceTaxExcl = $shippingTax->getTaxExclRespectRate();
        $cart->amazonShippingTax = $shippingTax->getTaxAmountRespectRate();
        $cart->amzShippingTaxRate = $shippingTax->taxRate;

        // Payment Title
        //
        if ($this->amzPaymentRegion) {
            $paymentTitle = trim(sprintf('%s %s', Amazon::PAYMENT_METHOD_TITLE, $region));
        } else {
            $paymentTitle = Amazon::PAYMENT_METHOD_TITLE;
        }

        // duplication du panier, important !!!
        //
        $acart = $cart;
        if (($validation_message = $acart->validateFields(false, true)) !== true) {
            $this->deleteCart($acart);
            return array('status' => 0, 'raw_error' => 'Field Validation failed for this cart', 'error' => sprintf('%s#%d: '.'Field Validation failed for this cart (Order: %s) - Reason: %s', basename(__FILE__), __LINE__, $order->AmazonOrderId, $validation_message));
        }

        $cart_result = $acart->getProducts();
        $this->pd('Cart Result:', print_r($cart_result, true));
        if (!is_array($cart_result) || !count($cart_result)) {
            $this->deleteCart($acart);
            return array('status' => 0, 'raw_error' => 'Unable to get product from cart for order', 'error' => sprintf('%s#%d: '.$this->l('Unable to get product from cart for order: %s, product: %s'), basename(__FILE__), __LINE__, $order->AmazonOrderId, $SKU));
        }

        // PreOrder : switch to preorder configured order state
        $id_order_state = $this->getIdStateForOrder($preorder_switch, $is_prime, $is_business, $channel);
        if ($id_order_state === false) {
            $this->deleteCart($cart);
            return array('status' => 0, 'raw_warning' => 'No available order state', 'warning' => 'No available order state');
        }

        $payment = new AmazonPaymentModule();
        if (!$i) {
            $this->deleteCart($cart);
            return array('status' => 0, 'raw_error' => 'No products for this order', 'error' => $this->l('Skipping Order: No products for this order').' ('.$order->AmazonOrderId.')');
        } elseif (($newOrderId = $payment->validateMarketplaceOrder($cart->id, $id_order_state, $paymentTitle, $order_id, $mpStatusId, $acart, $this->amzEnableTaxes, $this->moduleInstallOpensi ? null : $date_add))) {
            if ($this->tokenOrders) {
                $url = '?tab=AdminOrders&id_order='.$newOrderId.'&vieworder&token='.$this->tokenOrders;
                $order_link = html_entity_decode('&lt;a href="'.$url.'" title="" target="_blank" &gt;'.$order->AmazonOrderId.' ('.$newOrderId.')&lt;/a&gt;');
                $orderDataResult['link'] = $order_link;
            }
            $orderDataResult['merchant_order_id'] = $newOrderId;

            return array('status' => 1, 'order_data_result' => $orderDataResult);
        } else {
            $this->deleteCart($cart);
            return array('status' => 0, 'raw_error' => 'Error while importing this order', 'error' => sprintf(
                '%s. %s',
                $this->l('Error while importing this order ID') . ': ' . $order->AmazonOrderId,
                $payment->getError()
            ));
        }
    }

    protected function resolveIdCustomerGroup()
    {
        // Get the group configured in module configuration
        $id_customer_group = (int)Configuration::get('AMAZON_CUSTOMER_GROUP');
        if ((int)$id_customer_group && is_numeric($id_customer_group)) {
            $group = new Group($id_customer_group);
            if (Validate::isLoadedObject($group)) {
                return $id_customer_group;
            }
        }

        // Otherwise, get default group
        return version_compare(_PS_VERSION_, '1.5', '>=') ?
            Configuration::get('PS_CUSTOMER_GROUP') : (int)_PS_DEFAULT_CUSTOMER_GROUP_;
    }

    /**
     * @param $order
     * @param $id_lang
     * @param $order_id
     * @return array
     */
    protected function resolveCustomer($order, $id_lang, $order_id)
    {
        // Amazon Global Customer Account ID
        $id_customer = $this->amzIdCustomer;

        if ($this->amzAccountType == Amazon::ACCOUNT_TYPE_INDIVIDUAL && isset($order->BuyerEmail) && !empty($order->BuyerEmail)) {
            $email_address = (string)$order->BuyerEmail;
            $customer = new Customer();
            $customer->getByEmail($email_address);

            if ($customer->id) {
                // Existing
                $id_customer = $customer->id;
            } else {
                $name = AmazonAddress::getAmazonName((string)$order->Address->Name);
                $customer->firstname = $name['firstname'];
                $customer->lastname = $name['lastname'];
                $customer->newsletter = false;
                $customer->optin = false;
                $customer->email = $email_address;
                $customer->passwd = md5(rand());
                $customer->id_default_group = $this->idCustomerGroup;
                $customer->id_lang = $id_lang;

                if (!Validate::isName($customer->firstname) || !Validate::isName($customer->lastname) || !Validate::isEmail($customer->email)) {
                    return array('status' => false, 'reason' => basename(__FILE__).': '.__LINE__.' - '.$this->l('Order').': #'.$order_id.' - '.$this->l('Couldn\'t add this customer').' : '.print_r($name, true).'('.$email_address.')');
                } else {
                    if (!$customer->validateFields(false, false)) {
                        return array(
                            'status' => false,
                            'reason' => basename(__FILE__).': '.__LINE__.' - '.$this->l('Order').': #'.$order_id.' - '.$this->l('Couldn\'t add this customer').' : '.print_r($name, true).'('.$email_address.')'. ', Error : ' . print_r($customer->validateFields(false, true), true)
                        );
                    } elseif (!$customer->add()) {
                        return array(
                            'status' => false,
                            'reason' => basename(__FILE__).': '.__LINE__.' - '.$this->l('Order').': #'.$order_id.' - '.$this->l('Couldn\'t add this customer').' : '.print_r($name, true).'('.$email_address.')'
                        );
                    }

                    $id_customer = $customer->id;
                }
            }

            $this->pd('Customer:', print_r(get_object_vars($customer), true));
        }
        
        return array('status' => true, 'id_customer' => $id_customer);
    }

    protected function resolveShippingAddress($id_customer, $id_lang, $order)
    {
        $shipping_address = new AmazonAddress();
        $shipping_address->id_customer = $id_customer;
        $shipping_address_id = $shipping_address->lookupOrCreateamazonAddress($id_lang, $order->Address);
        foreach ($shipping_address->getLogs() as $log) {
            self::logContent($log);
        }

        return !$shipping_address_id ? false : $shipping_address_id;
    }
    
    protected function preCheck()
    {
        if ($this->psCatalogMode) {
            die($this->l('Your shop is in catalog mode, you can\'t import orders'));
        }
        if (!is_array($this->amzOrderStates)) {
            die($this->l('Incoming order state must be configured - Modules > Amazon > Parameters > Orders States'));
        }
    }
    
    protected function checkCurrency($id_currency)
    {
        if ($id_currency) {
            $currency = new Currency($id_currency);
            if (Validate::isLoadedObject($currency)) {
                return $currency;
            }
        }

        return false;
    }

    public function logInit($region)
    {
        $action = 'import';
        $output_dir = _PS_MODULE_DIR_.'/amazon/log/';
        $log = $output_dir.date('Ymd_His').'.'.$action.'-'.$region.'.log';

        if (!is_dir($output_dir)) {
            mkdir($output_dir);
        }
        if (is_dir($output_dir) && AmazonTools::isDirWriteable($output_dir)) {
            $files = glob($output_dir.'*.'.$action.'-'.$region.'.log');

            if (is_array($files) && count($files)) {
                foreach ($files as $key => $file) {
                    if (filemtime($file) < time() - (86400 * 3)) {
                        unlink($file);
                    }
                }
            }
            return($log);
        }
        return(null);
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        static $lang = null;

        if(!$lang)
            $lang = Amazon::availableLang(Language::getIsoById($this->id_lang));

        return (parent::l($string, basename(__FILE__, '.php'), $lang));
    }

    private function euSaleToOtherCountry($destinationIsEurope, $sales_channel)
    {
        return $this->amzFtIsEu
            && $destinationIsEurope
            && AmazonTools::isEurope($this->amzMasterMkp)
            && $this->saleToAnotherCountry($sales_channel);
    }
    
    private function saleToAnotherCountry($sales_channel)
    {
        // Check if sales channel is different from MASTER marketplace
        $master_mp = Tools::strtolower($this->amzMasterMkp);

        if (self::$debug_mode) {
            CommonTools::p(sprintf('%s - %s::%s - line #%d - master: %s sale channel: %s', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__, $master_mp, $sales_channel));
        }

        if(empty($master_mp) && self::$debug_mode){
            CommonTools::p(sprintf('%s - %s::%s - line #%d - master platform is not defined', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
        }

        if (!empty($master_mp) && strpos($sales_channel, $master_mp) !== false) {
            $sales_channel_segment = explode('.', $sales_channel);

            if (self::$debug_mode) {
                CommonTools::p(sprintf('%s - %s::%s - line #%d - segment: %s', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__, print_r($sales_channel_segment, true)));
            }
            if (count($sales_channel_segment) === 2) {
                $marketplace = trim(Tools::strtolower($sales_channel_segment[1]));

                if (self::$debug_mode) {
                    CommonTools::p(sprintf('%s - %s::%s - line #%d - compare: %s', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__, $marketplace));
                }

                if ($marketplace == $master_mp) {
                    return false;
                }
            }
        }
        if (self::$debug_mode) {
            CommonTools::p(sprintf('%s - %s::%s - line #%d', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
        }

        return(true);
    }
    /**
     * Check if order is FBA, and destination is different from Amazon_master
     * @param $sales_channel
     * @param $fulfilment_channel
     * @param int $id_lang
     * @return object
     */
    private function taxForFBA($sales_channel, $fulfilment_channel, $id_lang)
    {
        $result                 = new stdClass();
        $result->active         = false;
        $result->id_tax_rule    = null;
        $result->id_country     = null;

        // 1. Check if this order is special
        // If order is Amazon Fulfillment
        if ($fulfilment_channel != Amazon::AFN) {
            return $result;                 // Order is not FBA
        }

        // Check if sales channel is different from MASTER marketplace
        if ($this->saleToAnotherCountry($sales_channel))
        {
            return $result;           // Destination is the same with Amazon_master
        }

        // 2. For custom tax calculation, need (1) override tax rule and (2) country to apply tax rule
        if (!$this->_default_tax_rule) {
            $this->_default_tax_rule = AmazonConfiguration::get(Amazon::CONFIG_DEFAULT_TAX_RULE_FOR_MP);
        }
        if (!$this->_marketplaces) {
            $this->_marketplaces = AmazonTools::languages();
        }

        $id_tax_rule = $id_country = null;
        if (isset($this->_default_tax_rule[$id_lang])) {
            $id_tax_rule = $this->_default_tax_rule[$id_lang];
        }
        if (isset($this->_marketplaces[$id_lang])) {
            $country_iso = $this->_marketplaces[$id_lang]['country_iso_code'];
            $id_country  = Country::getByIso($country_iso);
        }

        // Dec 5, 2018: Fix: Amazon invoice display wrong tax because of empty id_tax_rule.
        if (isset($id_tax_rule, $id_country) && $id_tax_rule && $id_country) {
            $result->active         = true;
            $result->id_tax_rule    = $id_tax_rule;
            $result->id_country     = $id_country;
        }

        return $result;
    }

    /**
     * @return AmazonOrderImportingPsTax
     */
    private function resolvePsTaxRuleToAdaptEURules($isBusiness, $saleToOtherCountry, $amzPriceTaxIncl, $amzTaxAmount, $shippingAddressId, $amazon_has_tax, $cart, $product)
    {
        $useTaxes = $this->amzEnableTaxes;
        
        if (!$useTaxes || $isBusiness && $saleToOtherCountry) {
            // Only no tax on abroad business
            $data = new AmazonOrderImportingPsTax(0, 0, false);
        } elseif ($amzTaxAmount) {
            $taxRule = $this->guessTaxByAddress($amzPriceTaxIncl, $amzTaxAmount, $shippingAddressId);
            $data = $taxRule ?: $this->calculateProductTaxFallback($amazon_has_tax, $cart, $product, $shippingAddressId);
        } else {
            $data = $this->calculateProductTaxFallback($amazon_has_tax, $cart, $product, $shippingAddressId);
        }

        $this->output('Resolved PS tax rule: ' . print_r(array(
                'Tax rule: ' . print_r($data, true),
                print_r($cart->tax_for_fba, true),
                'Use tax: ' . $useTaxes,
                'Is business order: ' . $isBusiness,
                'Item has tax: ' . $amazon_has_tax,
                'EU sale to other country: ' . $saleToOtherCountry,
            ), true));

        return $data;
    }

    /**
     * Fix wrong item tax. Calculate product tax rate:
     * - Not use tax || business || cart not initialized (ignore later, so don't need to calculate): tax rate = 0
     * - EU sale to other country, guess tax by destination country
     * - Product not has Amazon tax + Order is special FBA: tax rate = FBA calculation
     * - Other cases: Normal tax rate
     * @param $is_business
     * @param $euSaleToOtherCountry
     * @param $amzItemPrice (x quantity)
     * @param $amzItemTax (x quantity)
     * @param bool $amazon_has_tax Determine if this product has <ItemTax> in OrderItem.xml. If not, calculate our tax.
     * @param AmazonProduct $product
     * @param int $shipping_address_id
     * @param null|AmazonCart $cart
     * @return AmazonOrderImportingPsTax
     */
    private function calculateProductTax($is_business, $euSaleToOtherCountry, $amzItemPrice, $amzItemTax, $amazon_has_tax, $product, $shipping_address_id, $cart = null)
    {
        $useTaxes = $this->amzEnableTaxes;

        // Calculate product tax rate
        if (!$useTaxes || $is_business && !$this->amzTaxOnBusinessOrder || !isset($cart) || !($cart instanceof AmazonCart)) {
            $data = new AmazonOrderImportingPsTax(0, 0, false);
        } else {
            if ($euSaleToOtherCountry && $amzItemTax) {
                // EU sale to other country, tax rate is closest rate of destination country
                // If Amz does not have tax, fallback to PS
                $tax = $this->guessTaxByAddress($amzItemPrice, $amzItemTax, $shipping_address_id);
                $data = $tax ? $tax :
                    $this->calculateProductTaxFallback($amazon_has_tax, $cart, $product, $shipping_address_id);
            } else {
                $data = $this->calculateProductTaxFallback($amazon_has_tax, $cart, $product, $shipping_address_id);
            }
        }

        $this->output('Item price calculated: ' . print_r(array(
                'Item tax: ' . print_r($data, true),
                print_r($cart->tax_for_fba, true),
                'Use tax: ' . $useTaxes,
                'Is business order: ' . $is_business,
                'Item has tax: ' . $amazon_has_tax,
                'EU sale to other country: ' . $euSaleToOtherCountry,
                'Force calculate tax on business order: ' . $this->amzTaxOnBusinessOrder,
            ), true));

        return $data;
    }

    /**
     * @param $priceTaxIncl
     * @param $taxAmount
     * @param $idAddress
     * @return AmazonOrderImportingPsTax|false
     */
    private function guessTaxByAddress($priceTaxIncl, $taxAmount, $idAddress)
    {
        $amzCalculatedTaxRate = $taxAmount / ($priceTaxIncl - $taxAmount) * 100;
        $shippingAddress = new Address($idAddress);
        $countryTaxRates = $this->getAllTaxRatesOfCountry($shippingAddress->id_country);
        if (count($countryTaxRates)) {
            $nearestVatRule = $this->findNearestValue($amzCalculatedTaxRate, $countryTaxRates, 'rate');
            return new AmazonOrderImportingPsTax($nearestVatRule['rate'], $nearestVatRule['id_tax_rules_group'], $nearestVatRule['id_tax']);
        }
        
        return false;
    }

    /**
     * @param bool $amazon_has_tax
     * @param AmazonCart $cart
     * @param Product $product
     * @param int $shipping_address_id
     * @return AmazonOrderImportingPsTax
     */
    private function calculateProductTaxFallback($amazon_has_tax, $cart, $product, $shipping_address_id)
    {
        if (!$amazon_has_tax && $cart->isTaxRateFBAApplicable()) {
            // Tax rate by FBA, not sure if this case still effect. If not, consider remove
            $tax_rate = $cart->getTaxRateFBA();
            $id_tax_rules_group = $cart->tax_for_fba->id_tax_rule;
        } else {
            // Normal tax calculation by shipping address
            if (method_exists('Tax', 'getProductTaxRate')) {
                $tax_rate = (float)(Tax::getProductTaxRate($product->id, $shipping_address_id));
            } else {
                $tax_rate = (float)(Tax::getApplicableTax($product->id_tax, $product->tax_rate, $shipping_address_id));
            }
            $id_tax_rules_group = isset($product->id_tax_rules_group) ? $product->id_tax_rules_group : (int)Product::getIdTaxRulesGroupMostUsed();
        }
        
        return new AmazonOrderImportingPsTax($tax_rate, $id_tax_rules_group, isset($product->id_tax) ? $product->id_tax : false);
    }

    /**
     * @param $id_country
     * @return array|bool|mysqli_result|PDOStatement|resource|null
     */
    private function getAllTaxRatesOfCountry($id_country)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT t.id_tax, t.rate, rg.`id_tax_rules_group`
			FROM `' . _DB_PREFIX_ . 'tax_rules_group` rg
			JOIN `' . _DB_PREFIX_ . 'tax_rule` tr ON (tr.`id_tax_rules_group` = rg.`id_tax_rules_group`)
			JOIN `' . _DB_PREFIX_ . 'tax` t ON (t.`id_tax` = tr.`id_tax`)
			WHERE tr.`id_country` = ' . (int)$id_country . '
			AND 0 between `zipcode_from` AND `zipcode_to`
			ORDER BY t.`rate`'
        );

        return $rows && is_array($rows) && count($rows) ? $rows : array();
    }

    /**
     * @param $needle
     * @param array $haystack
     * @param $findBy
     * @return array
     */
    private function findNearestValue($needle, $haystack, $findBy)
    {
        $this->pd('Finding nearest of - in:', $needle, print_r($haystack, true));
        $min = abs($needle - $haystack[0][$findBy]);
        $nearest = $haystack[0];
        foreach ($haystack as $item) {
            $diff = abs($needle - $item[$findBy]);
            if ($diff < $min) {
                $min = $diff;
                $nearest = $item;
            }
        }

        return $nearest;
    }

    /**
     * @param $shippingPriceTaxIncl
     * @param $shippingTax
     * @param AmazonOrderImportingPsTax $psTaxRule
     * @return AmazonOrderImportingPrices
     */
    private function calculateShippingPricesByProductTax($shippingPriceTaxIncl, $shippingTax, $psTaxRule)
    {
        if ($shippingPriceTaxIncl <= 0) {
            // Free shipping
            $data = new AmazonOrderImportingPrices(0, 0, 0, 0);
        } elseif ($psTaxRule->rate <= 0) {
            $data = new AmazonOrderImportingPrices($shippingPriceTaxIncl, $shippingPriceTaxIncl, 0, 0);
        } else {
            $data = new AmazonOrderImportingPrices($shippingPriceTaxIncl, $shippingPriceTaxIncl - $shippingTax, $shippingTax, $psTaxRule->rate);
        }
        $this->output('Shipping prices calculated: ' . print_r(array(
                'Result: ' . print_r($data, true),
                'Shipping price tax included: ' . $shippingPriceTaxIncl,
                'Shipping tax: ' . $shippingTax,
                'PS tax rule: ' . print_r($psTaxRule, true),
            ), true));

        return $data;
    }
    
    /**
     * @param $isBusiness
     * @param $euSaleToOtherCountry
     * @param $shippingPriceTaxIncl
     * @param $shippingTax
     * @param $shipping_address_id
     * @param $cart
     * @return AmazonOrderImportingPrices
     */
    private function calculateShippingTax($isBusiness, $euSaleToOtherCountry, $shippingPriceTaxIncl, $shippingTax, $shipping_address_id, $cart)
    {
        $useTaxes = $this->amzEnableTaxes;

        // Calculate shipping tax rate
        if (!$useTaxes || $isBusiness && !$this->amzTaxOnBusinessOrder || !isset($cart) || !($cart instanceof AmazonCart)) {
            // Business: no tax, use price_tax_incl for both prices
            $data = new AmazonOrderImportingPrices($shippingPriceTaxIncl, $shippingPriceTaxIncl, 0, 0);
        } else {
            if ($euSaleToOtherCountry) {
                $tax = $this->guessTaxByAddress($shippingPriceTaxIncl, $shippingTax, $shipping_address_id);
                if ($tax) {
                    $data = new AmazonOrderImportingPrices($shippingPriceTaxIncl, $shippingPriceTaxIncl - $shippingTax, $shippingTax, $tax->rate);
                } else {
                    $data = $this->calculateShippingTaxFallback($shippingPriceTaxIncl, $shippingTax, $cart);
                }
            } else {
                $data = $this->calculateShippingTaxFallback($shippingPriceTaxIncl, $shippingTax, $cart);
            }
        }

        $this->output('Shipping price calculated: ' . print_r(array(
                'Shipping tax: ' . print_r($data, true),
                print_r($cart->tax_for_fba, true),
                'Use tax: ' . $useTaxes,
                'Is business order: ' . $isBusiness,
                'EU sale to other country: ' . $euSaleToOtherCountry
            ), true));
        
        return $data;
    }

    /**
     * @param $shippingPriceTaxIncl
     * @param $shippingTax
     * @param AmazonCart $cart
     * @return AmazonOrderImportingPrices
     */
    private function calculateShippingTaxFallback($shippingPriceTaxIncl, $shippingTax, $cart)
    {
        // Shipping free
        if ($shippingPriceTaxIncl == 0) {
            return new AmazonOrderImportingPrices(0, 0, $shippingTax, 0);
        }

        if ($shippingTax) {
            // If products carry shipping tax, just use them
            $shippingTaxExcl = $shippingPriceTaxIncl - $shippingTax;
        } else {
            // If not, get shipping prices from Amz cart, and calculate tax based on PS tax
            $psCarrierTaxRate = $cart->getTaxRate('carrier', null);
            $shippingTaxExcl = $shippingPriceTaxIncl / ((100 + $psCarrierTaxRate) / 100);
        }
        $shippingTaxIncl = $shippingPriceTaxIncl;

        return new AmazonOrderImportingPrices(
            max(0, $shippingTaxIncl),
            max(0, $shippingTaxExcl),
            $shippingTax,
            Tools::ps_round(($shippingTaxIncl / $shippingTaxExcl - 1) * 100, 0)
        );
    }

    /**
     * @param OrderedItem[] $Items
     * @param string $mpOrderId
     * @param bool $auto_create
     * @param $id_shop
     * @return array
     */
    protected function preCheckItems($Items, $mpOrderId, $auto_create, $id_shop, $id_lang=null)
    {
        $checkPass = true;
        $reason = '';
        foreach ($Items as $item_key => $item) {
            $quantity = (int)$item->QuantityOrdered;

            if ($quantity <= 0) {
                // todo: Find a way to return error, not set global
                AmazonImportOrder::$warnings[] = AmazonSupport::message(sprintf('%s#%d: '.$this->l('Skipping zero quantity item for order #%s product SKU: %s'), basename(__FILE__), __LINE__, $mpOrderId, trim((string)$item->SKU)), null);
                unset($Items[$item_key]);
            } else {
                $SKU = trim((string)$item->SKU);

                $productCheckArray = AmazonProduct::checkProduct($SKU, $id_shop, $id_lang);
                $productCheck = $productCheckArray['count'];

                if ($productCheck == 0 && !$auto_create) {
                    $reason = AmazonSupport::message(sprintf($this->l('SKU/Reference not found in your database. Please check existance of this product: "%s"'), $SKU), AmazonSupport::FUNCTION_IMPORT_UNEXISTENT_SKU);
                    $checkPass = false;
                } elseif ($productCheck > 1) {
                    $reason = AmazonSupport::message(sprintf($this->l('Unable to import duplicate product "%s" - Please remove the duplicate product in your database.'), $SKU), AmazonSupport::FUNCTION_IMPORT_DUPLICATE_SKU);
                    $checkPass = false;
                }
            }
        }

        return array('items' => $Items, 'pass' => $checkPass, 'reason' => $reason);
    }

    /**
     * @param $auto_create_import
     * @param $channel
     * @param $id_shop
     * @param $id_product
     * @param $id_product_attribute
     * @param int $productOutOfStockStatus
     * @param int $productQuantity Qty in this order
     * @param int $quantity Product remain aty
     * @return bool
     */
    protected function restockBeforeAddCart(
        $auto_create_import,
        $channel,
        $id_shop,
        $id_product,
        $id_product_attribute,
        $productOutOfStockStatus,
        $productQuantity,
        $quantity
    )
    {
        $force_import = Product::isAvailableWhenOutOfStock($productOutOfStockStatus);

        $this->pd(sprintf(
            'Stock concern. PS stock management: %d. PS product out_of_stock rule: %d. 
            PS is product available when out of stock: %d. Order qty: %d, stock qty: %d. 
            AMZ FBA feature enable: %s. AMZ channel: %s. AMZ Auto creat dummy product: %d',
            $this->psStockManagement, $productOutOfStockStatus, $force_import, $quantity, $productQuantity,
            $this->moduleFeatures->fba, $channel, $auto_create_import
        ));

        if ($this->psStockManagement) {
            $isFba = $this->moduleFeatures->fba && $channel == Amazon::AFN;
            // Tran 2020-12-24: Why in FBA, force_import only when qty <= 0
            // $force_import = $force_import && !max(0, $productQuantity);

            $restock = false;
            if ($auto_create_import) {
                $restock = true;
            } elseif ($isFba && !$force_import && $productQuantity - $quantity < 0) {
                return false;
            } elseif ($isFba) {
                // In case of FBA restock in all cases
                if (!$this->amzFbaDecreaseStock || $force_import) {
                    $restock = true;
                }
            } elseif (!$force_import && $productQuantity - $quantity < 0) {
                return false;
            }
            // 2020-10-09: Tran does not restock to allow quantity go negative.
//            elseif ($productQuantity - $quantity < 0) {
//                $restock = true;
//            }

            $this->pd('Cart:', 'restock: ' . ($restock ? 'true' : 'false'), "force_import: $force_import");

            // todo: This prevent negative stock?
            if ($restock) {
                if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                    StockAvailable::updateQuantity($id_product, $id_product_attribute ? $id_product_attribute : null, $quantity, $id_shop);
                } else {
                    AmazonProduct::updateProductQuantity($id_product, $id_product_attribute ? $id_product_attribute : null, $productQuantity + $quantity);
                }
            }
        }

        return true;
    }

    /**
     * @param string $psProductAvailableDate
     * @param string $amzOrderPurchaseDate
     * @return bool
     */
    protected function isPreOrder($psProductAvailableDate, $amzOrderPurchaseDate)
    {
        $orderStates = $this->amzOrderStates;
        $OSPreOrder = Amazon::ORDER_STATE_PREORDER;

        // is a preorder, is an unavailable product, order states for preorder is configured
        if (
            $this->amzPreOrder
            && version_compare(_PS_VERSION_, '1.5', '>=')
            && Validate::isDate($psProductAvailableDate)
            && is_array($orderStates) && isset($orderStates[$OSPreOrder]) && (int)$orderStates[$OSPreOrder])
        {
            $dateNow = time();
            $psDateRestock = strtotime($psProductAvailableDate);
            $dateOrder = strtotime($amzOrderPurchaseDate);

            if ($psDateRestock && $psDateRestock > $dateNow && $psDateRestock > $dateOrder) {
                return true;
            }
        }

        return false;
    }

    /**
     * Order state priority: Preorder > {Combination} > FBA > Prime > Business > Normal incoming
     * @param $preorder_switch
     * @param bool $is_prime
     * @param bool $is_business
     * @param $channel
     * @return false|int
     */
    protected function getIdStateForOrder($preorder_switch, $is_prime, $is_business, $channel)
    {
        // If Pre-order, try it only
        if ($preorder_switch) {
            $id_state_preorder = $this->amzOrderStates[Amazon::ORDER_STATE_PREORDER];
            if ($id_state_preorder) {
                return (int)$id_state_preorder;
            }

            AmazonImportOrder::$errors[] = AmazonSupport::message(
                $this->l('Please configure Preorder order status in module configuration'),
                AmazonSupport::FUNCTION_IMPORT_ORDER_STATUS
            );

            return false;
        }

        // If has attributes combination, try it (optional)
        $hasStateCombination = is_array($this->amzIncomingStateOfOrderAttrsCombination) && count($this->amzIncomingStateOfOrderAttrsCombination);
        $isFBA = (bool)$this->amazon_features['fba'] && $channel == Amazon::AFN;
        
        if ($hasStateCombination) {
            $orderAttributesCombination = array(
                AmazonConstant::ORDER_INCOMING_TYPE_FBA => $isFBA,
                AmazonConstant::ORDER_INCOMING_TYPE_PRIME => $is_prime,
                AmazonConstant::ORDER_INCOMING_TYPE_BUSINESS => $is_business,
            );
            $id_state_attrs_combination = $this->getIdStateFromMultipleOrderAttributes($orderAttributesCombination);
            if ($id_state_attrs_combination) {
                return (int)$id_state_attrs_combination;
            }
        }

        // Otherwise, use traditional way
        return $this->getIdStateAfterMultipleOrderAttributes($isFBA, $is_prime, $is_business);
    }

    /**
     * @param $is_fba
     * @param $is_prime
     * @param $is_business
     * @return false|int
     */
    private function getIdStateAfterMultipleOrderAttributes($is_fba, $is_prime, $is_business)
    {
        $orderStates = $this->amzOrderStates;

        // If FBA, try it only
        if ($is_fba) {
            $id_state_fba = $this->amzOrderStateFBAIncoming;
            if ($id_state_fba) {
                return (int)$id_state_fba;
            }

            AmazonImportOrder::$errors[] = AmazonSupport::message(
                $this->l('Please configure FBA order status in module configuration'),
                AmazonSupport::FUNCTION_IMPORT_ORDER_STATUS
            );

            return false;
        }

        $id_order_state = 0;
        // Try Prime, Business, normal incoming
        if ($is_prime && isset($orderStates[Amazon::ORDER_STATE_PRIMEORDER])) {
            $id_order_state = $orderStates[Amazon::ORDER_STATE_PRIMEORDER];
        } elseif ($is_business && isset($orderStates[Amazon::ORDER_STATE_BUSINESS])) {
            $id_order_state = $orderStates[Amazon::ORDER_STATE_BUSINESS];
        } elseif (isset($orderStates[Amazon::ORDER_STATE_STANDARD])) {
            $id_order_state = $orderStates[Amazon::ORDER_STATE_STANDARD];
        }

        if ($id_order_state) {
            return (int)$id_order_state;
        }

        AmazonImportOrder::$errors[] = AmazonSupport::message(
            $this->l('Please configure order statuses in module configuration'),
            AmazonSupport::FUNCTION_IMPORT_ORDER_STATUS
        );

        return false;
    }
    
    private function getIdStateFromMultipleOrderAttributes($orderAttributes)
    {
        foreach ($this->amzIncomingStateOfOrderAttrsCombination as $combination) {
            $combinationAttributes = $combination['attr'];
            $state = $combination['state'];
            if (is_array($combinationAttributes) && count($combinationAttributes) && $state) {
                if ($this->orderAttributesFitDefinedCombination($orderAttributes, $combinationAttributes)) {
                    return $state;
                }
            }
        }
        
        return false;
    }
    
    private function orderAttributesFitDefinedCombination($orderAttributes, $combinationAttributes)
    {
        foreach ($combinationAttributes as $attribute) {
            // Combination has the attribute, but order doesn't ---> False
            if ($attribute && (!isset($orderAttributes[$attribute]) || !$orderAttributes[$attribute])) {
                return false;
            }
            // Attribute is unselected, ignore
        }
        
        return true;
    }

    /**
     * @param AmazonCart $cart
     */
    protected function deleteCart($cart)
    {
        if (Validate::isLoadedObject($cart)) {
            $cart->delete();
        }
    }

    protected function saveAcknowledgeFeed($feedData, $submissionFeedId)
    {
        if ($submissionFeedId) {
            $ackFileName = date('Y-m-d') . '-' . $submissionFeedId;
            self::logContent("Acknowledge orders successfully. Feed ID: $submissionFeedId. Feed content is in: $ackFileName");
        } else {
            $ackFileName = date('Y-m-d') . '-' . rand(100000000000, 999999999999);
            self::logContent("Failed to acknowledge orders! Feed content is logged in: $ackFileName");
        }

        $ackLogger = new AmazonLogger(array(AmazonLogger::CHANNEL_ORDER_IMPORT, AmazonLogger::SUB_OI_ACK), $ackFileName);
        $ackLogger->info($feedData->feed);
        if ($feedData->sumbission) {
            $ackLogger->info($feedData->sumbission);
        }
    }

    public function clearAllPreviousFailedOrders()
    {
        if (Amazon::$debug_mode) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }

        ob_start();
        register_shutdown_function(array('AmazonImportOrder', 'jsonDisplayExit'));

        $tokens = Tools::getValue('amazon_token');

        if (!AmazonTools::checkToken($tokens)) {
            die($this->l('Wrong Token'));
        }

        if (AmazonCronFailedOrder::removeAllCronFailedOrders()) {
            AmazonImportOrder::$messages[] = $this->l('Cleared cron failed orders');
        } else {
            AmazonImportOrder::$errors[] = $this->l('Error while clearing cron failed orders');
        }
    }

    protected function tryPreviousFailedOrders($id_mkps, $id_lang, $id_shop, $id_currency, $log_filename, $origin_currency, $id_warehouse)
    {
        $failedOrders = self::getSomePreviousFailedOrders($id_mkps);
        $this->logContent('Trying this turn: ' . print_r($failedOrders, true));
        if (count($failedOrders)) {
            $orders = $this->amazonApi->getOrders(array_keys($failedOrders));
            if (is_array($orders)) {
                foreach ($orders as $order) {
                    $this->importOneOrder($order, $id_lang, $id_shop, $id_currency, $log_filename, $origin_currency, $id_warehouse);
                }
            }       
        }
    }

    protected static function getSomePreviousFailedOrders($id_mkps = array())
    {
        $previousFailedOrders = AmazonCronFailedOrder::getAllCronFailedOrders($id_mkps);
        // Sort randomly
        if ((rand(0, 1) - 0.5) < 0) {
            uasort($previousFailedOrders, function($a, $b) {
                return $a['attempt'] <= $b['attempt'];
            });
        } else {
            uasort($previousFailedOrders, function($a, $b) {
                return strtotime($a['purchase_date']) <= strtotime($b['purchase_date']);
            });
        }
        
        return array_slice($previousFailedOrders, 0, 10, true);
    }

    protected static function mergePreviousFailedOrders($failedOrders)
    {
        $previousFailedOrders = AmazonCronFailedOrder::getAllCronFailedOrders();
        foreach ($failedOrders as $mpOrderId => $orderData) {
            if (!isset($previousFailedOrders[$mpOrderId])) {
                $previousFailedOrders[$mpOrderId] = $orderData;
                $previousFailedOrders[$mpOrderId]['attempt'] = 1;
            } else {
                $newReason = sprintf('%s - %s', date('c'), $orderData['reason']);
                $previousFailedOrders[$mpOrderId]['attempt']++;
                $previousFailedOrders[$mpOrderId]['reason'] = is_array($previousFailedOrders[$mpOrderId]['reason'])
                    ? array_merge($previousFailedOrders[$mpOrderId]['reason'], $newReason) : $newReason;
            }
        }
        Configuration::updateValue(AmazonConstant::IMPORT_ORDERS_CRON_FAILED_LIST, json_encode($previousFailedOrders));
    }

    protected function pd()
    {
        if (Amazon::$debug_mode) {
            $backTrace = debug_backtrace();
            $caller = array_shift($backTrace);
            $fileSegment = explode('/', $caller['file']);
            $file = array_pop($fileSegment);

            foreach (func_get_args() as $arg) {
                AmazonTools::p(sprintf('%s(#%d): %s', $file, $caller['line'], $arg));
            }
        }
    }
    
    private static function logContent($log)
    {
        self::$logContent .= $log . Amazon::LF;
    }
    
    private static function getLogContent()
    {
        $logContent = self::$logContent;
        self::$logContent = '';
        return $logContent;
    }
    
    public function getOrder()
    {
        $lang = Tools::getValue('lang');
        $id_lang = $this->amzRegion2Lang[$lang];

        $amazon = AmazonTools::selectPlatforms($id_lang, true);
        $_amazonApi = new AmazonWebService($amazon['auth'], $amazon['params'], $amazon['platforms'], true, nl2br(Amazon::LF));
        $_amazonApi->getOrderById(Tools::getValue('mpOrderId'));
        print_r($_amazonApi, true);
        exit;
    }

    protected function output($msg)
    {
        if (Amazon::$debug_mode) {
            echo $msg . nl2br(Amazon::LF);    
        }
        $this->logContent($msg);
    }
}

class AmazonOrderImportingPsTax
{
    public $rate;
    public $id_tax_rules_group;
    public $id_tax;
    
    public function __construct($rate, $id_tax_rules_group, $id_tax)
    {
        $this->rate = $rate;
        $this->id_tax_rules_group = $id_tax_rules_group;
        $this->id_tax = $id_tax;
    }
}

class AmazonOrderImportingPrices
{
    public $taxIncl;
    public $taxExcl;
    public $taxAmount;
    public $taxRate;
    protected $consistent = true;
    
    public function __construct($taxIncl, $taxExcl, $taxAmount, $taxRate)
    {
        $this->taxIncl = Tools::ps_round($taxIncl, 2);
        $this->taxExcl = Tools::ps_round($taxExcl, 2);
        $this->taxAmount = $taxAmount;
        $this->taxRate = $taxRate;

        // No tax amount but has tax rate
        if ($this->taxIncl == $this->taxExcl && $this->taxAmount <= 0 && $this->taxRate > 0) {
            $this->consistent = false;
        }
    }

    public function getTaxExclRespectRate()
    {
        return $this->consistent ? $this->taxExcl
            : Tools::ps_round($this->taxIncl * 100 / (100 + $this->taxRate), 2);
    }

    public function getTaxAmountRespectRate()
    {
        return $this->consistent ? $this->taxAmount
            : Tools::ps_round($this->taxIncl * $this->taxRate / (100 + $this->taxRate), 2);
    }
}

$amazonImportOrder = new AmazonImportOrder();
if (Tools::getValue('danger')) {
    $amazonImportOrder->getOrder();
} elseif (Tools::getValue('action') == 'clear_failed_orders') {
    $amazonImportOrder->clearAllPreviousFailedOrders();
} else {
    $amazonImportOrder->import();    
}
