<?php
/**
 * NOTICE OF LICENSE
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
 * @author    Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 *  Support by mail  :  support.mirakl@common-services.com
 */

// Sep-25-2018: Use only 1 main class for all marketplaces

require_once(dirname(__FILE__).'/inventory.exporting.php');

// Sep-25-2018: Use only 1 main class for all marketplaces
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

class MiraklUpdatingOffers extends MiraklInventoryExporting
{
    const UPDATE = 'NORMAL';
    const REPLACE = 'REPLACE';

    private static $delete_action = 'DELETE';
    private static $no_expire = false;
    public $directory;
    public $export;
    public $export_url;
    public $zip_url;
    public $pickup_url;

    private $errors = array();
    private $separator = ';';
    private $operation = self::UPDATE;
    private $debug;
    private $ps_images;
    private $filename;

    protected static $brlf;
    const LF = "\n";
    const CRLF = "\r\n";

    private $mkpAllProfiles;

    // Some marketplace requires additional fields
    private $mkpSpecificFields = array();

    public function __construct()
    {
        parent::__construct(false);

        MiraklContext::restore($this->context);

        $token = Mirakl::getConfigGlobalMarketplace(Mirakl::CONFIG_PS_TOKEN, true);

        $this->debug = (bool)Tools::getValue('debug') ?: Mirakl::getConfig(Mirakl::CONFIG_DEBUG);

        if ($this->debug) {
            @ini_set('display_errors', 'on');
            @define('_PS_DEBUG_SQL_', true);
            @error_reporting(E_ALL | E_STRICT);
        }

        $this->ps_images = MiraklTools::getProtocol()
            . htmlspecialchars(
                $_SERVER['HTTP_HOST'],
                ENT_COMPAT,
                'UTF-8'
            ) . __PS_BASE_URI__ . 'img/p/';
        $this->directory = realpath(basename(__FILE__.'/../'));
        $this->export = $this->directory.DS.'export'.DS.'update';
        $this->export_url = $this->url.'functions/download.php?token='.$token.'&filename=';
        $this->zip_url = $this->url.'';
        $this->pickup_url = 'http://'.MiraklTools::getHttpHost(false, true).$this->url.'export/';

        // Sep-25-2018: Share mirakl_product_option for all marketplaces

        self::$brlf = nl2br("\n")."\n";

        // Get Customer Group
        $customer_id_group = self::getConfig(self::CONFIG_CUSTOMER_GROUP);
        if (!$customer_id_group) {
            $customer_id_group = version_compare(_PS_VERSION_, '1.5', '>=') ?
                (int)Configuration::get('PS_CUSTOMER_GROUP') : (int)_PS_DEFAULT_CUSTOMER_GROUP_;
        }

        // Apply Customer Group to current customer
        if (!isset(Context::getContext()->customer) || !Validate::isLoadedObject(Context::getContext()->customer)) {
            Context::getContext()->customer = new Customer((int)self::getConfig(self::CONFIG_CUSTOMER_ID));
        }
        Context::getContext()->customer->id_default_group = $customer_id_group;

        $this->mkpAllProfiles = Mirakl::getConfig(Mirakl::CONFIG_PROFILES, true);

        $this->resolveMkpSpecificFields();
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return parent::l($string, basename(__FILE__, '.php'), $id_lang);
    }

    public function dispatch()
    {
        ob_start();

        $metoken = Tools::getValue('metoken');
        $action = Tools::getValue('action');

        $id_employee = (int)Mirakl::getConfig(Mirakl::CONFIG_ID_EMPLOYEE);

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $employee = null;

            if ($id_employee) {
                $employee = new Employee($id_employee);
            }

            if (!Validate::isLoadedObject($employee)) {
                $this->dieOnError($this->l('Wrong Employee, please save the module configuration'));
            }

            $this->context = Context::getContext();
            $this->context->customer->is_guest = true;
            $this->context->customer->id_default_group = (int)Configuration::get('PS_CUSTOMER_GROUP');
            $this->context->cart = new Cart();
            $this->context->employee = $employee;
            $this->context->currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        } else {
            $default_currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
            $cart = $this->context->cart;
            $cookie = $this->context->cookie;
            $cart->id_currency = $cookie->id_currency = $default_currency->id;
        }

        $this->filename = Tools::strtolower(MiraklTools::getFriendlyUrl(Configuration::get('PS_SHOP_NAME'))).'-offers.csv';

        //  Check Access Tokens
        $token = Mirakl::getConfigGlobalMarketplace(Mirakl::CONFIG_PS_TOKEN, true);

        if ($metoken != $token) {
            $this->dieOnError($this->l('Wrong Token'));
        }

        switch ($action) {
            case 'export':
                $this->initRuntimeParameters() && $this->productUpdate();
                break;
            case 'last_update':
                $this->lastUpdate();
                break;
            case 'cron':
                // todo: Apply for all actions. Override context by new implementation
                MiraklContext::set();
                $this->runModeCron = true;
                $this->initRuntimeParameters() && $this->productUpdate();
                break;
            case 'cron_lite':
                MiraklContext::set();
                $this->runModeCron = true;
                $this->initRuntimeParameters() && $this->productUpdateLite();
                break;
        }
    }

    private function dieOnError($msg)
    {
        echo $msg;
        $output = ob_get_clean();
        $json = Tools::jsonEncode(array('error' => true, 'msg' => $output));

        // jQuery Output or PHP Output
        if (($callback = Tools::getValue('callback'))) { // jquery
            echo (string)$callback.'('.$json.')';
        } else { // cron
            return $json;
        }
        die;
    }

    private function updateOffers($file, $offers_list, $send = true)
    {
        require_once(dirname(__FILE__).'/../classes/mirakl.api.offers.php');

        $marketplace_params = self::$marketplace_params;
        $marketplace_params['debug'] = $this->debug;
        $marketplace_params['api_key'] = Mirakl::getConfig(Mirakl::CONFIG_API_KEY);

        if ($send && empty($marketplace_params['api_key'])) {
            $this->errors[] = sprintf(
                '%s'.self::$brlf,
                $this->l('Missing API Key - Please configure your API Key in the module configuration')
            );

            return (false);
        }

        $offers = new MiraklApiOffers($marketplace_params);

        if ($this->debug) {
            echo 'FILE:'.$file.self::$brlf;
            MiraklTools::pre(array(nl2br(print_r($offers, true)).self::$brlf));
        }

        if (!is_array($offers_list) || !count($offers_list)) {
            $this->errors[] = sprintf('%s'.self::$brlf, $this->l('Empty offer list'));

            return (false);
        }

        if ($this->debug) {
            MiraklTools::pre(array(nl2br(print_r($offers_list, true)).self::$brlf));
        }

        if (!($fp = fopen($file, 'w+'))) {
            $this->errors[] = sprintf('%s: %s'.self::$brlf, $this->l('Unable to write in file'), $file);

            return (false);
        }

        $keys = array();
        foreach ($offers_list as $offer_list) {
            $keys = array_unique(array_merge($keys, array_map('trim', array_keys($offer_list))));
        }

        ksort($keys);
        fputcsv($fp, $keys, $this->separator);

        $entry = array_fill_keys($keys, null);

        foreach ($offers_list as $offer) {
            $csv_array = array_map('trim', array_merge($entry, $offer));

//            fputcsv(
//                $fp,
//                array_map(function ($item) {
//                    return $item ? trim($item) : 'NC';
//                }, array_merge(array_fill_keys($keys, null), $csv_array)),
//                $this->separator
//            );
            fputcsv($fp, $csv_array, $this->separator);
        }
        fclose($fp);

        $params = array('file' => $file, 'import_mode' => $this->operation);

        if ($this->debug) {
            echo self::$brlf;
            echo 'Offer Count: '.count($offers_list).self::$brlf;
            echo 'Memory: '.number_format(memory_get_usage() / 1024).'k'.self::$brlf;
            echo 'Params: '.nl2br(print_r($params, true)).self::$brlf;
        }

        if ($send) {
            $response = $offers->imports($params);

            if ($this->debug) {
                MiraklTools::pre(array(nl2br(htmlspecialchars(print_r($response, true)))));
            }

            return $response;
        } else {
            return true;
        }
    }

    private function updateOffersResponse($response)
    {
        if (empty($response)) {
            $this->errors[] = sprintf('%s', $this->l('Remote service didnt respond'));

            return (false);
        }
        $xml = simplexml_load_string($response);

        if (!$xml instanceof SimpleXMLElement) {
            $this->errors[] = sprintf(
                '%s: %s',
                $this->l('Remote service return unexpected content'),
                nl2br(print_r($response, true))
            );

            return (false);
        }
        if (isset($xml->error_code) && (int)$xml->error_code) {
            $error_str = $xml->error;

            if (isset($xml->service_code) && !empty($xml->service_code)) {
                $error_str .= ' - '.MiraklTools::bold('Service Code').': '.(string)$xml->service_code;
            }

            if (isset($xml->error_details)) {
                $error_str .= ' - '.MiraklTools::bold('Error Message').': '.(string)$xml->error_details;
            }

            $this->errors[] = sprintf(MiraklTools::bold('Webservice Error').': %s', $error_str);

            return (false);
        }
        $import_id = null;

        if (isset($xml->import_id) && (int)$xml->import_id) {
            $import_id = (int)$xml->import_id;
            printf('%s:'.MiraklTools::bold('#%d').self::$brlf, $this->l('File successfully sent, Import ID'), $import_id);
        }

        return ($import_id);
    }

    private function fileList()
    {
        // Generic function sorting files by date
        $output_dir = sprintf('%s/', rtrim($this->export, '/'));

        if (!is_dir($output_dir)) {
            return null;
        }

        $files = glob($output_dir.'*.csv');

        if (!is_array($files) || !count($files)) {
            return null;
        }

        // Sort by date
        foreach ($files as $key => $file) {
            $files[filemtime($file)] = $file;
            unset($files[$key]);
        }
        ksort($files);

        return $files;
    }

    private function cleanup()
    {
        // Cleanup oldest files
        $files = $this->fileList();

        if (is_array($files) && count($files)) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    private function productUpdate()
    {
        $country_iso_code = Tools::strtolower(Configuration::get('PS_LOCALE_COUNTRY'));
        $id_country = (int)Country::getByIso($country_iso_code);

        $id_default_customer_group = self::getConfig(self::CONFIG_CUSTOMER_GROUP);
        if (!$id_default_customer_group) {
            $id_default_customer_group = Configuration::get('PS_CUSTOMER_GROUP');
        }

        $latency = Mirakl::getConfig(Mirakl::CONFIG_DELIVERY_TIME);

        $any_customer_address = Db::getInstance()->getRow('SELECT id_address FROM `'._DB_PREFIX_.'address` WHERE id_country='.(int)$id_country.' AND id_customer > 0');
        $id_address = isset($any_customer_address['id_address']) ? (int)$any_customer_address['id_address'] : null;

        $this->loadFeatures();
        $this->loadAttributes();

        $error = false;
        $count = $p = 0;
        $history = array();
        $sku_history = array();
        $duplicateAlongCategories = array();

        $this->cleanup();

        $id_lang = $this->id_lang;

        if ($this->marketplace_id_lang) {
            $id_lang = $this->marketplace_id_lang;
        }

        if ($this->debug) {
            echo 'Id Lang: '.$id_lang.self::$brlf;
        }

        $urls = array();
        $to_mirakl = array();
        $offers_errors = array();

        $miraklSupport = new MiraklSupport();

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $id_shop = 1;
            $id_warehouse = null;
            $default_category = 1;
        } else {
            $default_category = $this->context->shop->id_category;

            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                $id_warehouse = Mirakl::getConfig(Mirakl::CONFIG_WAREHOUSE);
            }

            if (empty($id_warehouse) || !is_numeric($id_warehouse)) {
                $id_warehouse = null;
            }

            if (Shop::isFeatureActive()) {
                $id_shop = (int)Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
            } else {
                $id_shop = 1;
            }
        }

        $create_in_stock = false;
        $all_offers = false;
        $matching = false;

        // Parameters
        if (Tools::getValue('all-offers')) {
            $all_offers = true;
        }

        if (Tools::getValue('update-in-stock')) {
            $create_in_stock = true;
        }

        if (Tools::getValue('replace')) {
            $this->operation = self::REPLACE;
        }

        if (Tools::getValue('matching')) {
            $matching = true;
        }

        if (Tools::getValue('send-offers')) {
            $send = true;
        } elseif ($this->runModeCron) {
            $send = true;
        } else {
            $send = false;
        }

        $date_start_init = MiraklTools::oldest();
        $date_start_init = str_replace('/', '-', Tools::substr($date_start_init, 0, strpos($date_start_init, ' ')));

        $marketplace_params = self::$marketplace_params;

        if ($this->runModeCron) {
            $create_in_stock = false;

            if (Tools::getValue('force')) {
                $cron_date = $date_start_init;
            } else {
                $cron_date = Mirakl::getConfig(Mirakl::CONFIG_LAST_UPDATE_CRON);
            }

            if (!$cron_date) {
                $date_from = $date_start_init;
            } else {
                $date_from = $cron_date;
            }
        } else {
            $date_from = Tools::getValue('last-update');

            if (!$date_from) {
                $date_from = date('Y-m-d', strtotime('yesterday'));
            }
        }

        if ($all_offers) {
            $date_from = $date_start_init;
        }

        // Categories Settings
        $default_categories = Mirakl::getConfig(Mirakl::CONFIG_CATEGORIES);
        if (!is_array($default_categories) || !count($default_categories)) {
            $this->errors[] = sprintf(
                '%s(%d): %s',
                basename(__FILE__),
                __LINE__,
                $this->l('You must configure the categories to update')
            );
            $error = true;
        }

        $default_profiles = $this->mkpAllProfiles;
        $default_profiles2categories = Mirakl::getConfig(Mirakl::CONFIG_PROFILE_TO_CATEGORY, true);

        // Prices Parameters
        $use_taxes      = (bool)Mirakl::getConfig(Mirakl::CONFIG_USE_TAXES);
        $use_specials   = (bool)Mirakl::getConfig(Mirakl::CONFIG_USE_SPECIALS);
        $smart_rounding = (bool)Mirakl::getConfig(Mirakl::CONFIG_SMART_ROUNDING);

        if (is_array($marketplace_params) && array_key_exists('separator', $marketplace_params)
            && Tools::strlen($marketplace_params['separator'])) {
            $separator = $marketplace_params['separator'];
        } else {
            $separator = '.';
        }

        $decription_field = Mirakl::getConfig(Mirakl::CONFIG_DESCRIPTION_FIELD);
        $decription_field = ($decription_field ? $decription_field : Mirakl::FIELD_DESCRIPTION_LONG);
        $decription_html = (bool)Mirakl::getConfig(Mirakl::CONFIG_DESCRIPTION_HTML);

        $from_currency = new Currency((int)(Configuration::get('PS_CURRENCY_DEFAULT')));
        $to_currency = $from_currency;

        if (array_key_exists('currency', $marketplace_params) && Tools::strlen($marketplace_params['currency'])) {
            $to_currency = new Currency(Currency::getIdByIsoCode($marketplace_params['currency']));

            if (!Validate::isLoadedObject($to_currency)) {
                $this->errors[] = sprintf(
                    '%s(%d): %s(%s)',
                    basename(__FILE__),
                    __LINE__,
                    $this->l('Unable to load currency'),
                    $marketplace_params['currency']
                );
                $error = true;
            }
        }

        if ($from_currency->iso_code != $to_currency->iso_code) {
            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                $this->context->currency = $to_currency;
            } else {
                $cart = $this->context->cart;
                $cookie = $this->context->cookie;
                $cart->id_currency = $cookie->id_currency = $to_currency->id;
            }
        }

        $product_name_pre = (int)Mirakl::getConfig(Mirakl::CONFIG_PRODUCT_NAME);
        $product_name_format = ($product_name_pre ? $product_name_pre : self::NAME_NAME_ONLY);

        // Condition Map: Sep-25-2018: Remove

        // Exclusions
        $excluded_manufacturers = Mirakl::getConfig(Mirakl::CONFIG_FILTER_MFR, true);
        $excluded_suppliers = Mirakl::getConfig(Mirakl::CONFIG_FILTER_SUPPLIERS, true);

        // Path to XML
        $output_dir = $this->export;

        // Files
        $offers_file = $output_dir.DS.$this->filename;

        // Carriers
        $carriers = Carrier::getCarriers($id_lang);
        $carrier_tax_rate = null;

        // Carrier Configuration - Standard
        $selected_carrier = (int)Mirakl::getConfig(Mirakl::CONFIG_CARRIER);
        $id_carrier = null;

        if ((int)$selected_carrier && is_array($carriers) && count($carriers)) {
            foreach ($carriers as $carrier) {
                if ((int)$carrier['id_carrier'] == (int)$selected_carrier) {
                    $id_carrier = $carrier['id_carrier'];
                }
            }
        }

        if ($id_carrier == null) {
            $id_carrier = $selected_carrier;
        }

        if ($id_carrier == null) {
            $this->errors[] = sprintf(
                '%s(%d): %s',
                basename(__FILE__),
                __LINE__,
                $this->l('You must configure your primary carrier')
            );
            $error = true;
        }
        $carrier = new Carrier($id_carrier);

        if (!Validate::isLoadedObject($carrier)) {
            $this->errors[] = sprintf(
                '%s(%d): %s(%d)',
                basename(__FILE__),
                __LINE__,
                $this->l('Unable to load carrier'),
                $id_carrier
            );
            $error = true;
        }

        // Carrier Configuration - Relay
        // $selected_carrier_relay = (int)Mirakl::getConfig(Mirakl::CONFIG_CARRIER_RELAY);
        $selected_carrier_relay = null;
        $id_carrier_relay = null;

        if ((int)$selected_carrier_relay && is_array($carriers) && count($carriers)) {
            foreach ($carriers as $carrier) {
                if ((int)$carrier['id_carrier'] == (int)$selected_carrier_relay) {
                    $id_carrier_relay = $carrier['id_carrier'];
                }
            }
        }

        $carrier_relay = null;

        if ($id_carrier_relay) {
            $carrier_relay = new Carrier($id_carrier_relay);

            if (!Validate::isLoadedObject($carrier_relay)) {
                $this->errors[] = sprintf(
                    '%s(%d): %s(%d)',
                    basename(__FILE__),
                    __LINE__,
                    $this->l('Unable to load carrier'),
                    $id_carrier
                );
                $error = true;
            }
        }

        $marketplace_params = self::$marketplace_params;

        if (is_array($marketplace_params) && array_key_exists('options', $marketplace_params) && count($marketplace_params['options'])) {
            foreach (array_keys($marketplace_params['options']) as $option_field) {
                switch ($option_field) {
                    case 'no-delete':
                        self::$delete_action = 'UPDATE';
                        break;
                    case 'no-expire':
                        self::$no_expire = true;
                        break;
                }
            }
        }

        // Download URLs
        $urls[$this->filename] = sprintf('%s%s', $this->export_url, 'update/'.$this->filename);

        // Check rights
        if (!is_dir($output_dir) && !mkdir($output_dir)) {
            $this->errors[] = sprintf(
                '%s(%s): %s(%s)',
                basename(__FILE__),
                __LINE__,
                $this->l('Unable to create the directory'),
                $output_dir
            );
            $error = true;
        }

        if (!is_writable($output_dir)) {
            chmod($output_dir, 0775);
        }

        if (array_key_exists('ean_field_name', $marketplace_params) && Tools::strlen($marketplace_params['ean_field_name'])) {
            $ean_field_name = $marketplace_params['ean_field_name'];
        } else {
            $ean_field_name = 'EAN';
        }

        // Export Loop
        if (!$error && $default_categories) {
            foreach (array_reverse($default_categories) as $id_category) {
                if ($id_category == $default_category) {
                    continue;
                }

                $profile = isset($default_profiles2categories[$id_category]) ? $default_profiles2categories[$id_category] : null;

                if (!$profile) {
                    $category = new Category($id_category, $id_lang);

                    $this->errors[] = sprintf(
                        '%s(%d): %s - %s (%d)',
                        basename(__FILE__),
                        __LINE__,
                        $this->l('You must sets a profile for this category'),
                        $category->name,
                        $category->id
                    );
                    $error = true;
                    continue;
                }
                $selected_profile = false;
                foreach ($default_profiles['name'] as $selected_profile => $profile_name) {
                    if ($profile_name === $profile) {
                        break;
                    }
                }

                if ($selected_profile === false) {
                    $this->errors[] = sprintf(
                        '%s(%d): %s',
                        basename(__FILE__),
                        __LINE__,
                        $this->l('You must configure your profiles first')
                    );
                    $error = true;
                    continue;
                }

                $profile_attr = isset($default_profiles['combinations_attr'][$selected_profile]) ? $default_profiles['combinations_attr'][$selected_profile] : '';
                $min_quantity_alert = isset($default_profiles['min_quantity_alert'][$selected_profile]) ? $default_profiles['min_quantity_alert'][$selected_profile] : '';
                $logistic_class = isset($default_profiles['logistic_class'][$selected_profile]) ? $default_profiles['logistic_class'][$selected_profile] : '';
                $profile_price_rule = isset($default_profiles['price_rule'][$selected_profile]) ? $default_profiles['price_rule'][$selected_profile] : false;
                $profile_shipping_rule = isset($default_profiles['shipping_rule'][$selected_profile]) ? (float)$default_profiles['shipping_rule'][$selected_profile] : 0;
                $warranty = isset($default_profiles['warranty'][$selected_profile]) ? $default_profiles['warranty'][$selected_profile] : '';

                $products = MiraklProduct::getExportProducts(
                    $id_category,
                    $this->onlyActiveOne,
                    $create_in_stock,
                    $date_from,
                    null,
                    $this->debug
                );

                if ($products) {
                    foreach ($products as $product) {
                        $id_product = $product['id_product'];

                        // Products with multiples categories ;
                        if (isset($duplicateAlongCategories[$id_product])) {
                            continue;
                        }
                        $duplicateAlongCategories[$id_product] = true;

                        $details = new Product($id_product, false, $this->id_lang);
                        if (!Validate::isLoadedObject($details)) {
                            $this->errors[] = sprintf($this->l('Could not load the product id: %d'), $id_product);
                            $error = true;
                            continue;
                        }

                        // Filtering Manufacturer & Supplier
                        if ($details->id_manufacturer) {
                            if (is_array($excluded_manufacturers)
                                && in_array($details->id_manufacturer, $excluded_manufacturers)) {
                                continue;
                            }
                        }

                        if ($details->id_supplier) {
                            if (is_array($excluded_suppliers) && in_array($details->id_supplier, $excluded_suppliers)) {
                                continue;
                            }
                        }

                        // RDC only sells NEW products, skip the used one
                        /*if (Tools::strtolower($details->condition) != 'new') {
                            if ($this->debug) {
                                $this->errors[] = sprintf($this->l('Product ID #%d is not NEW.'), $id_product);
                            }
                            continue;
                        }*/

                        $manufacturer = Manufacturer::getNameById((int)$details->id_manufacturer);

                        $product_features = $details->getFeatures();

                        // Product Options
                        $options = MiraklProduct::getProductOptions($id_product, $id_lang);

                        $disabled = $options['disable'] ? true : false;
                        $force = $options['force'] ? true : false;
                        $latency_override = (int)$options['shipping'] ? (int)$options['shipping'] : null;

                        if ($disabled) {
                            continue;
                        }

                        $price_override = false;
                        if (!empty($options['price']) && is_numeric((float)$options['price'])) {
                            $price_override = (float)$options['price'];
                        }

                        if (!$details->active && $this->onlyActiveOne) {
                            continue;
                        }

                        // Product Combinations
                        if (version_compare(_PS_VERSION_, '1.5', '<')) {
                            $combinations = $details->getAttributeCombinaisons($id_lang);
                        } else {
                            $combinations = $details->getAttributeCombinations($id_lang);
                        }

                        // Pas de combinaison, on en cr?e une fictive pour rentrer dans la boucle
                        if (!is_array($combinations) || !count($combinations)) {
                            $combinations = array(
                                0 => array(
                                    'reference' => $details->reference,
                                    'ean13' => $details->ean13,
                                    'id_product_attribute' => 0,
                                    'id_attribute_group' => 0,
                                    'id_attribute' => 0,
                                    'minimal_quantity' => $details->minimal_quantity,
                                    'meta_title' => $details->meta_title,
                                    'unity' => $details->unity,
                                    'weight' => 0,
                                    'meta_description' => $details->meta_description,
                                    'default_on' => 0
                                )
                            );
                        }
                        // Grouping Combinations
                        asort($combinations);

                        $group_details = array();

                        foreach ($combinations as $combination) {
                            $id_product_attribute = isset($combination['id_product_attribute']) ? $combination['id_product_attribute'] : 0;
                            $id_attribute_group = isset($combination['id_attribute_group']) ? $combination['id_attribute_group'] : 0;
                            $id_attribute = isset($combination['id_attribute']) ? $combination['id_attribute'] : 0;

                            $group_details[$id_product_attribute][$id_attribute_group] = array();
                            $group_details[$id_product_attribute][$id_attribute_group]['reference'] = $combination['reference'];
                            $group_details[$id_product_attribute][$id_attribute_group]['weight'] = $combination['weight'];
                            $group_details[$id_product_attribute][$id_attribute_group]['unity'] = isset($combination['unity']) ? $combination['unity'] : $details->unity;
                            $group_details[$id_product_attribute][$id_attribute_group]['id_attribute_group'] = $combination['id_attribute_group'];
                            $group_details[$id_product_attribute][$id_attribute_group]['id_attribute'] = $combination['id_attribute'];

                            // Synch Field (EAN, UPC, SKU ...)
                            $group_details[$id_product_attribute][$id_attribute_group]['ean13'] = $combination['ean13'];

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
                        foreach ($group_details as $id_product_attribute => $combination) {
                            $idx++;
                            $group_detail = array();

                            $id_attribute_group = isset($combination['id_attribute_group']) ? $combination['id_attribute_group'] : 0;
                            $id_attribute = isset($combination['id_attribute']) ? $combination['id_attribute'] : 0;

                            $ean13 = '';
                            $reference = '';
                            $weight = $details->weight;
                            $attributes_desc = null;
                            $unity = null;

                            foreach ($combination as $group_detail) {
                                if ($group_detail['attribute_name']) {
                                    if ($profile_attr != Mirakl::ATTRIBUTES_NO) {
                                        if ($profile_attr == Mirakl::ATTRIBUTES_LONG) {
                                            $attributes_desc .= sprintf(
                                                '%s: %s - ',
                                                $group_detail['group_name'],
                                                $group_detail['attribute_name']
                                            );
                                        } elseif ($profile_attr == Mirakl::ATTRIBUTES_SHORT) {
                                            $attributes_desc .= sprintf('%s - ', $group_detail['attribute_name']);
                                        }
                                    }
                                }
                                if (isset($group_detail['reference']) && !empty($group_detail['reference'])) {
                                    $reference = $group_detail['reference'];
                                }
                                if (isset($group_detail['ean13'])) {
                                    $ean13 = $group_detail['ean13'];
                                }

                                $weight = (float)$details->weight + (float)$group_detail['weight'];
                                $unity = isset($group_detail['unity']) && (int)$group_detail['unity'] ? (int)$group_detail['unity'] : (int)$details->unity;
                            }

                            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                                $supplier_reference = ProductSupplier::getProductSupplierReference(
                                    $id_product,
                                    $id_product_attribute,
                                    $details->id_supplier
                                );
                            } else {
                                $supplier_reference = $details->supplier_reference;
                            }

                            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                                $quantity = Product::getRealQuantity(
                                    $details->id,
                                    $id_product_attribute ? $id_product_attribute : null,
                                    $id_warehouse,
                                    $id_shop
                                );
                            } else {
                                $quantity = (int)MiraklProduct::getProductQuantity($id_product, $id_product_attribute);
                            }

                            if ($disabled) {
                                $quantity = 0;
                            }

                            if ($force) {
                                $quantity = 999;
                            }

                            if (!$quantity && $create_in_stock) {
                                if ($this->debug) {
                                    $this->errors[] = sprintf(
                                        $this->l('Skipping out of stock product: %d'),
                                        $id_product
                                    );
                                }
                                continue;
                            }

                            // Force $id_default_group again to make sure the getPrice() method returns the
                            // correct price for the correct customer group
                            Context::getContext()->customer->id_default_group = $id_default_customer_group;

                            // Price
                            $priceResolver = $this->resolvePrice($options, $profile_price_rule, $profile_shipping_rule, $details, $id_product_attribute);
                            $price = $priceResolver['tax_incl'];
                            $price_tax_excl = $priceResolver['tax_excl'];

                            $sales = array();

                            // Apply Sales for PS > 1.4
                            if (!$price_override && version_compare(_PS_VERSION_, '1.4', '>=') && $use_specials) {
                                $sales = $this->resolveSalePrice(
                                    $details,
                                    $id_default_customer_group,
                                    $id_product_attribute,
                                    $price,
                                    $profile_shipping_rule,
                                    $profile_price_rule,
                                    $use_taxes,
                                    $use_specials,
                                    $smart_rounding,
                                    $separator
                                );
                            }

                            if (empty($reference)) {
                                $offers_errors['empty_reference'][] = array(
                                    'ean13' => $ean13,
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute
                                );
                                continue;
                            }

                            if ($matching && !(int)$ean13) {
                                $offers_errors['missing_ean_matching'][] = array(
                                    'reference' => $reference,
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute
                                );
                                continue;
                            }

                            if ((int)$ean13) {
                                if (!MiraklTools::eanupcCheck($ean13)) {
                                    $offers_errors['wrong_ean'][$ean13] = array(
                                        'ean13' => $ean13,
                                        'id_product' => $id_product,
                                        'id_product_attribute' => $id_product_attribute
                                    );
                                    continue;
                                }
                                if ($ean13 && Tools::strlen($ean13) && isset($history[$ean13])) {
                                    $offers_errors['duplicate_ean'][$ean13] = array(
                                        'reference' => $reference,
                                        'ean13' => $ean13,
                                        'id_product' => $id_product,
                                        'id_product_attribute' => $id_product_attribute
                                    );
                                    continue;
                                }
                                $history[$ean13] = true;
                            }

                            if (Tools::strlen($reference) && isset($sku_history[$reference])) {
                                $offers_errors['duplicate_reference'][] = array(
                                    'reference' => $reference,
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute
                                );
                                continue;
                            }
                            $sku_history[$reference] = true;

                            $ecotax_rate = null;
                            $ecotax = null;

                            if (version_compare(_PS_VERSION_, '1.4', '>=')) {
                                $ecotax_rate = (float)Tax::getProductEcotaxRate();

                                if ($use_taxes) {
                                    $ecotax_tax_amount = Tools::ps_round($ecotax, 2);
                                    $ecotax_tax_amount = Tools::ps_round(
                                        $ecotax_tax_amount * (1 + $ecotax_rate / 100),
                                        2
                                    );

                                    $ecotax = $ecotax_tax_amount;
                                }
                            }

                            //
                            // Product Name
                            //
                            switch ($product_name_format) {
                                case self::NAME_NAME_ATTRIBUTES:
                                    if ($attributes_desc) {
                                        $name = rtrim(sprintf(
                                            '%s (%s)',
                                            $details->name,
                                            rtrim($attributes_desc, ' - ')
                                        ), ' - ');
                                        $name = trim(rtrim($name, '-'));
                                        $product_name = $name;
                                    } else {
                                        $product_name = $details->name;
                                    }
                                    break;
                                case self::NAME_BRAND_NAME_ATTRIBUTES:
                                    if ($attributes_desc) {
                                        $name = rtrim(sprintf(
                                            '%s - %s (%s)',
                                            $manufacturer,
                                            $details->name,
                                            rtrim($attributes_desc, ' - ')
                                        ), ' - ');
                                        $name = trim(rtrim($name, '-'));
                                        $product_name = $name;
                                    } else {
                                        $product_name = sprintf('%s - %s', $manufacturer, $details->name);
                                    }
                                    break;
                                case self::NAME_NAME_BRAND_ATTRIBUTES:
                                    if ($attributes_desc) {
                                        $name = rtrim(sprintf(
                                            '%s - %s - (%s)',
                                            $details->name,
                                            $manufacturer,
                                            rtrim($attributes_desc, ' - ')
                                        ), ' - ');
                                        $name = trim(rtrim($name, '-'));
                                        $product_name = $name;
                                    } else {
                                        $product_name = sprintf('%s - %s', $details->name, $manufacturer);
                                    }
                                    break;
                                case self::NAME_NAME_REFERENCE:
                                    $product_name = $details->name;

                                    if ($reference) {
                                        $product_name = sprintf('%s - %s', $product_name, $reference);
                                    }
                                    break;
                                default:
                                    $product_name = $details->name;
                                    break;
                            }

                            $to_mirakl[$p]['ProductName'] = $product_name;

                            // Carrier Taxes
                            if ($carrier_tax_rate == null) {
                                if (method_exists('Carrier', 'getTaxesRate')) {
                                    $carrier = new Carrier($id_carrier);

                                    if (Validate::isLoadedObject($carrier)) {
                                        $tax_address = new Address((int)Mirakl::getConfig(Mirakl::CONFIG_ADDRESS_ID));

                                        if (Validate::isLoadedObject($tax_address)) {
                                            $carrier_tax_rate = (float)$carrier->getTaxesRate($tax_address);
                                        }
                                    }
                                } elseif (method_exists('Tax', 'getCarrierTaxRate')) {
                                    if ($id_carrier) {
                                        $carrier = new Carrier($id_carrier);

                                        if (Validate::isLoadedObject($carrier)) {
                                            $carrier_tax_rate = (float)Tax::getCarrierTaxRate($id_carrier, (int)Mirakl::getConfig(Mirakl::CONFIG_ADDRESS_ID));
                                        }
                                    }
                                }
                            }

                            if ($matching) {
                                $product_id_type = $ean_field_name;

                                if (in_array(MiraklMarketplace::getCurrentMarketplace(), array('gosport', 'intermarche'))) {
                                    $product_id_type = Tools::strtolower($product_id_type);
                                }

                                $product_id = $ean13;
                                $operation_message = sprintf(
                                    'Matching attempt for %s, %s',
                                    $product_name,
                                    date('Y-m-d H:i:s')
                                );
                            } else {
//                                $product_id = 'SKU';
//                                $product_id_type = $reference;
                                $product_id = null;
                                $product_id_type = null;
                                $operation_message = sprintf(
                                    'Offer update for %s, %s',
                                    $product_name,
                                    date('Y-m-d H:i:s')
                                );
                            }

                            $description = null;
//                            $description = "REBAJAS1";

                            $to_mirakl[$p]['sku'] = $reference;

                            if ($product_id_type) {
                                $to_mirakl[$p]['product-id'] = $product_id_type == 'EAN' ?
                                    sprintf('%013s', $product_id) : $product_id;
                                $to_mirakl[$p]['product-id-type'] = $product_id_type;
                            }

                            if (is_array($marketplace_params) && array_key_exists('separator', $marketplace_params) && Tools::strlen($marketplace_params['separator'])) {
                                $separator = $marketplace_params['separator'];
                            } else {
                                $separator = '.';
                            }

                            $to_mirakl[$p]['description'] = $description;
                            // $to_mirakl[$p]['description-short'] = $details->description_short;
                            // $to_mirakl[$p]['internal-description'] = $operation_message;

                            if ($smart_rounding) {
                                $to_mirakl[$p]['price'] = MiraklTools::smartRounding($price, $separator);
                            } else {
                                $to_mirakl[$p]['price'] = number_format(Tools::ps_round($price, 2), 2, $separator, '');
                            }
                            $to_mirakl[$p]['price-tax-excl'] = $price_tax_excl;

                            $to_mirakl[$p]['price-additional-info'] = '';
                            $to_mirakl[$p]['quantity'] = ($quantity < 0) ? 0 : $quantity;
                            $to_mirakl[$p]['weight'] = sprintf('%.02f', $weight);
                            if (in_array(MiraklMarketplace::getCurrentMarketplace(), array('eprice'))) {
                                $to_mirakl[$p]['weight'] *= 1000;
                            }

                            $to_mirakl[$p]['min-quantity-alert'] = $min_quantity_alert;

                            if (array_key_exists('conditions', $marketplace_params) && count($marketplace_params['conditions'])) {
                                $product_condition = MiraklTools::toKey($details->condition);

                                if (array_key_exists($product_condition, $marketplace_params['conditions'])) {
                                    $to_mirakl[$p]['state'] = $marketplace_params['conditions'][$product_condition];
                                } else {
                                    $to_mirakl[$p]['state'] = 11;
                                }
                            } else {
                                $to_mirakl[$p]['state'] = 11;
                            }

                            $to_mirakl[$p]['logistic-class'] = $logistic_class;

                            if (!self::$no_expire) {
                                $to_mirakl[$p]['available-start-date'] = date('Y-m-d', strtotime('now'));
                                $to_mirakl[$p]['available-end-date'] = date('Y-m-d', strtotime('now + 30 day'));
                            } else {
                                $to_mirakl[$p]['available-start-date'] = '';
                                $to_mirakl[$p]['available-end-date'] = '';
                            }

                            // Optionnal/Marketplace custom fields ;
                            if (is_array($marketplace_params) && array_key_exists('fields', $marketplace_params)) {
                                foreach ($marketplace_params['fields'] as $field) {
                                    switch ($field['prestashop']) {
                                        case 'vat':
                                            $product_tax_rate = null;

                                            if (method_exists('Tax', 'getProductTaxRate') && $id_address) {
                                                $product_tax_rate = (float)(Tax::getProductTaxRate(
                                                    $details->id,
                                                    $id_address
                                                ));
                                            } elseif ($id_address) {
                                                $product_tax_rate = (float)(Tax::getApplicableTax(
                                                    $details->id_tax,
                                                    $details->tax_rate,
                                                    $id_address
                                                ));
                                            }

                                            if ($product_tax_rate) {
                                                if (in_array(MiraklMarketplace::getCurrentMarketplace(), array('metro'))) {
                                                    $product_tax_rate = str_replace('.', '-', $product_tax_rate);
                                                }

                                                $to_mirakl[$p][$field['mirakl']] = $product_tax_rate;
                                            } else {
                                                $to_mirakl[$p][$field['mirakl']] = $field['default'];
                                            }

                                            break;

                                        case 'ecotax':
                                            $ecotax_rate = null;

                                            if (version_compare(_PS_VERSION_, '1.4', '>=')) {
                                                $ecotax_rate = (float)Tax::getProductEcotaxRate();

                                                if ($use_taxes && $ecotax_rate) {
                                                    $ecotaxTaxAmount = Tools::ps_round(
                                                        $ecotax * ($ecotax_rate / 100),
                                                        2
                                                    );

                                                    $ecotax += $ecotaxTaxAmount;
                                                }
                                            }
                                            if ($ecotax_rate) {
                                                $to_mirakl[$p][$field['mirakl']] = $ecotax_rate;
                                            } else {
                                                $to_mirakl[$p][$field['mirakl']] = $field['default'];
                                            }

                                            break;

                                        case 'on_sale':
                                            $to_mirakl[$p][$field['mirakl']] = $details->on_sale;
                                            break;

                                        case 'striked_price':
                                            $striked_price = $details->getPrice(
                                                $use_taxes,
                                                $id_product_attribute,
                                                2,
                                                null,
                                                false,
                                                false
                                            );

                                            if ($smart_rounding) {
                                                $to_mirakl[$p][$field['mirakl']] = MiraklTools::smartRounding(
                                                    $striked_price,
                                                    $separator
                                                );
                                            } else {
                                                $to_mirakl[$p][$field['mirakl']] = number_format(Tools::ps_round(
                                                    $striked_price,
                                                    2,
                                                    $separator,
                                                    ''
                                                ), 2);
                                            }

                                            break;

                                        case 'discount_price':
                                            if (array_key_exists('salePrice', $sales) && (float)$sales['salePrice']) {
                                                $to_mirakl[$p][$field['mirakl']] = $sales['salePrice'];

                                                if ($smart_rounding) {
                                                    $to_mirakl[$p]['price'] = MiraklTools::smartRounding(
                                                        $price,
                                                        $separator
                                                    );
                                                } else {
                                                    $to_mirakl[$p]['price'] = number_format(
                                                        Tools::ps_round($price, 2),
                                                        2,
                                                        $separator,
                                                        ''
                                                    );
                                                }
                                            } else {
                                                $to_mirakl[$p][$field['mirakl']] = null;
                                            }

                                            break;

                                        case 'discount_start_date':
                                            if (array_key_exists('dateStart', $sales) && (float)$sales['dateStart']) {
                                                $to_mirakl[$p][$field['mirakl']] = $sales['dateStart'];
                                            } else {
                                                $to_mirakl[$p][$field['mirakl']] = null;
                                            }

                                            break;
                                        case 'discount_end_date':
                                            if (array_key_exists('dateEnd', $sales) && (float)$sales['dateEnd']) {
                                                $to_mirakl[$p][$field['mirakl']] = $sales['dateEnd'];
                                            } else {
                                                $to_mirakl[$p][$field['mirakl']] = null;
                                            }

                                            break;

                                        case 'latency':
                                            $delivery_time = $latency_override ? $latency_override : $latency;

                                            if (is_numeric($delivery_time)) {
                                                $to_mirakl[$p][$field['mirakl']] = $delivery_time;
                                            } else {
                                                $to_mirakl[$p][$field['mirakl']] = $field['default'];
                                            }
                                            break;

                                        case 'ean':
                                            $to_mirakl[$p][$field['mirakl']] = $ean13;
                                            break;

                                        // referenciagenericaeci of El Corte Ingles is handled as specific field

                                        default:
                                            $to_mirakl[$p][$field['mirakl']] = isset($to_mirakl[$p][$field['prestashop']])
                                                ? $to_mirakl[$p][$field['prestashop']] : $field['default'];
                                            break;
                                    }
                                }
                            }

                            // Additionnals/Marketplace custom fields ;
                            if (is_array($marketplace_params) && array_key_exists('additionnals', $marketplace_params)) {
                                foreach ($marketplace_params['additionnals'] as $additionnal) {
                                    $field_name = $additionnal['mirakl'];

                                    if (empty($field_name)) {
                                        continue;
                                    }
                                    $default = $additionnal['default'];
                                    $required = (bool)$additionnal['required'];

                                    if (isset($default_profiles[$field_name][$selected_profile])) {
                                        $selected_field = explode(
                                            '-',
                                            $default_profiles[$field_name][$selected_profile]
                                        );
                                        $field_type = isset($selected_field[0]) ? $selected_field[0] : null;
                                        $field_id = isset($selected_field[1]) ? $selected_field[1] : null;

                                        if ($required && ($field_type == null || $field_id == null) && !Tools::strlen($default)) {
                                            $offers_errors['missing_field'][$field_name] = array(
                                                'reference' => $reference,
                                                'id_product' => $id_product,
                                                'id_product_attribute' => $id_product_attribute
                                            );
                                            unset($to_mirakl[$p]);
                                            continue;
                                        }
                                        $to_mirakl[$p][$field_name] = $default;

                                        switch ($field_type) {
                                            case 'a'://attributes
                                                if (array_key_exists($field_id, $combination)) {
                                                    $to_mirakl[$p][$field_name] = $combination[$field_id]['attribute_name'];
                                                }
                                                break;
                                            case 'f'://features
                                                $features_value = array();

                                                if (is_array($product_features)) {
                                                    foreach ($product_features as $feature) {
                                                        $id_feature = (int)$feature['id_feature'];
                                                        $id_feature_value = (int)$feature['id_feature_value'];

                                                        if ($id_feature != $field_id) {
                                                            continue;
                                                        }

                                                        $features_values = FeatureValue::getFeatureValuesWithLang(
                                                            $id_lang,
                                                            $id_feature,
                                                            $feature['custom']
                                                        );

                                                        foreach ($features_values as $features_value) {
                                                            if ((int)$features_value['id_feature_value'] == $id_feature_value) {
                                                                break;
                                                            } else {
                                                                $features_value = null;
                                                            }
                                                            break;
                                                        }
                                                    }
                                                }
                                                if (is_array($features_value) && array_key_exists('value', $feature)) {
                                                    $to_mirakl[$p][$field_name] = $features_value['value'];
                                                }
                                                break;

                                            case 'p'://prestashop field
                                                switch ($field_id) {
                                                    case Mirakl::REFERENCE:
                                                        $value = $reference;
                                                        break;
                                                    case Mirakl::SUPPLIER_REFERENCE:
                                                        $value = $supplier_reference;
                                                        break;
                                                    case Mirakl::MANUFACTURER:
                                                        $value = Manufacturer::getNameById($details->id_manufacturer);
                                                        break;
                                                    case Mirakl::CATEGORY:
                                                        $category = new Category($id_category);
                                                        $value = $category->name[$id_lang];
                                                        break;
                                                    case Mirakl::META_TITLE:
                                                        $value = $combination['meta_title'];
                                                        break;
                                                    case Mirakl::META_DESCRIPTION:
                                                        $value = $combination['meta_description'];
                                                        break;
                                                    case Mirakl::UNITY:
                                                        $value = $unity;
                                                        break;
                                                    case Mirakl::WEIGHT:
                                                        $value = $weight;
                                                        break;
                                                    case Mirakl::UID:
                                                        $value = sprintf('EAN|%s', $details->ean13);
                                                        break;
                                                    case Mirakl::WARRANTY:
                                                        $value = (int)$warranty;
                                                        break;
                                                    default:
                                                        $value = null;
                                                        break;
                                                }
                                                if (Tools::strlen($value)) {
                                                    $to_mirakl[$p][$field_name] = $value;
                                                }
                                                break;
                                        }
                                        if ((!isset($to_mirakl[$p][$field_name]) || empty($to_mirakl[$p][$field_name])) && isset($additionnal['required']) && $additionnal['required'] == true) {
                                            $this->errors[] = sprintf(
                                                $this->l('Missing mandatory field').': %s',
                                                $field_name
                                            );
                                            $error = true;
                                            continue;
                                        }
                                    }
                                }
                            }
                            
                            // Marketplace required fields
                            foreach ($this->fulfillMkpSpecificFields($options, $selected_profile) as $specificFieldName => $specificFieldValue) {
                                $to_mirakl[$p][$specificFieldName] = $specificFieldValue;
                            }

                            if (!isset($to_mirakl[$p])) {
                                continue;
                            }

                            if (!$matching) {
                                // $to_mirakl[$p]['update-delete'] = ($quantity <= 0) ? self::$delete_action : 'UPDATE';
                                // Force to UPDATE even with stock 0 else the product is completely deleted from Mirakl.
                                // Instead, update the product with stock 0.
                                $to_mirakl[$p]['update-delete'] = '';
                                // delete: delete the offer
                                // update: update the offer if exist. Therefore, the very first request will failed because there is nothing to update
                                // "" (empty): update if exist, create if not exist ---> Should always use this value.
                            }

                            if (is_array($marketplace_params) && array_key_exists('exclude', $marketplace_params) && count($marketplace_params['exclude'])) {
                                foreach (array_keys($marketplace_params['exclude']) as $exclude_field) {
                                    if (array_key_exists($exclude_field, $to_mirakl[$p])) {
                                        unset($to_mirakl[$p][$exclude_field]);
                                    }
                                }
                            }

                            foreach ($this->externalMkp->getExcludeProductUpdate() as $exclude_field) {
                                if (array_key_exists($exclude_field, $to_mirakl[$p])) {
                                    unset($to_mirakl[$p][$exclude_field]);
                                }
                            }

                            if ($this->debug) {
                                echo 'Content:'.nl2br(print_r($to_mirakl[$p], true)).self::$brlf;
                                echo self::$brlf;
                                echo 'Memory: '.number_format(memory_get_usage() / 1024).'k';
                            }

                            $count++;
                            $p++;

                            if ($this->debug) {
                                printf(
                                    'Exporting Product: %d id: %d reference: %s %s',
                                    $idx,
                                    $details->id,
                                    $reference,
                                    self::$brlf
                                );
                            }
                        } // end foreach combinations
                    }
                } // end foreach products
            } // end foreach categories
        } // end if

        if (is_array($offers_errors)) {
            foreach (array(
                'empty_reference',
                'duplicate_ean',
                'duplicate_reference',
                'wrong_ean',
                'missing_ean',
                'missing_field'
            ) as $error_type) {
                if (isset($offers_errors[$error_type]) && is_array($offers_errors[$error_type]) && count($offers_errors[$error_type])) {
                    $msg = null;
                    foreach ($offers_errors[$error_type] as $value => $product_error) {
                        switch ($error_type) {
                            case 'empty_reference':
                                if ($msg == null) {
                                    $msg = $this->l('Products having empty references, Product ID').': [';
                                }

                                if ($product_error['id_product_attribute']) {
                                    $msg .= sprintf(
                                        '%d/%d, ',
                                        $product_error['id_product'],
                                        $product_error['id_product_attribute']
                                    );
                                } else {
                                    $msg .= sprintf('%d, ', $product_error['id_product']);
                                }

                                break;

                            case 'duplicate_ean':
                                if ($msg == null) {
                                    $msg = $this->l('Duplicate EAN entry for product, References').': [';
                                }

                                $msg .= sprintf(
                                    '%s, ',
                                    $product_error['reference'] ? $product_error['reference'] : '#'.$product_error['id_product']
                                );
                                break;

                            case 'missing_ean':
                                if ($msg == null) {
                                    $msg = $this->l('EAN is missing, References').': [';
                                }

                                $msg .= sprintf(
                                    '%s, ',
                                    $product_error['reference'] ? $product_error['reference'] : '#'.$product_error['id_product']
                                );
                                break;

                            case 'missing_ean_matching':
                                if ($msg == null) {
                                    $msg = $this->l('Products without EAN ignored in matching mode, References').': [';
                                }

                                $msg .= sprintf(
                                    '%s, ',
                                    $product_error['reference'] ? $product_error['reference'] : '#'.$product_error['id_product']
                                );
                                break;

                            case 'duplicate_reference':
                                if ($msg == null) {
                                    $msg = $this->l('Duplicate reference entry for product, Product ID').': [';
                                }

                                if ($product_error['id_product_attribute']) {
                                    $msg .= sprintf(
                                        '%d/%d, ',
                                        $product_error['id_product'],
                                        $product_error['id_product_attribute']
                                    );
                                } else {
                                    $msg .= sprintf('%d, ', $product_error['id_product']);
                                }
                                break;

                            case 'wrong_ean':
                                if ($msg == null) {
                                    $msg = $this->l('EAN is incorrect, Product ID').': [';
                                }

                                if ($product_error['id_product_attribute']) {
                                    $msg .= sprintf(
                                        '%d/%d, ',
                                        $product_error['id_product'],
                                        $product_error['id_product_attribute']
                                    );
                                } else {
                                    $msg .= sprintf('%d, ', $product_error['id_product']);
                                }
                                break;

                            case 'missing_field':
                                if ($msg == null) {
                                    $msg = $this->l('Missing Mandatory profile field').': ['.$value;
                                }
                                break;
                        }
                    }

                    if ($msg) {
                        $msg = rtrim($msg, ', ').']';

                        switch ($error_type) {
                            case 'empty_reference':
                                $msg = $miraklSupport->message($msg, MiraklSupport::FUNCTION_EXPORT_EMPTY_REFERENCE);
                                break;
                            case 'duplicate_reference':
                                $msg = $miraklSupport->message($msg, MiraklSupport::FUNCTION_EXPORT_DUPLICATE);
                                break;
                            case 'wrong_ean':
                                $msg = $miraklSupport->message($msg, MiraklSupport::FUNCTION_EXPORT_WRONG_EAN);
                                break;
                            case 'duplicate_ean':
                                $msg = $miraklSupport->message($msg, MiraklSupport::FUNCTION_EXPORT_DUPLICATE_EAN);
                                break;
                            case 'missing_ean':
                                $msg = $miraklSupport->message($msg, MiraklSupport::FUNCTION_EXPORT_MISSING_EAN);
                                break;
                            case 'missing_ean_matching':
                                $msg = $miraklSupport->message(
                                    $msg,
                                    MiraklSupport::FUNCTION_EXPORT_MISSING_EAN_MATCHING
                                );
                                break;
                            default:
                                $msg .= nl2br("\n\n");
                                break;
                        }
                        $this->errors[] = $msg;
                    }
                }
            }
        }

        $msg = '';

        if (isset($to_mirakl) && is_array($to_mirakl) && $count = count($to_mirakl)) {
            if (!($response = $this->updateOffers($offers_file, $to_mirakl, $send))) {
                $this->errors[] = sprintf('UpdateOffers() failed...');
                $error = true;
            }

            if ($send && $this->updateOffersResponse($response)) {
                $msg .= sprintf($this->l('%d offers created... %s').self::$brlf, $count, null);
            } elseif ($send) {
                $this->errors[] = sprintf($this->l('Update offers failed.').self::$brlf, $count);
                $error = true;
            }
        } else {
            $this->errors[] = $this->l('No Offers, nothing to do...');
            $error = true;
        }

        // Export Output
        $output = ob_get_clean();

        if (!$error && $count) {
            $msg .= sprintf('%s %s', $count, $this->l('Products Successfully Exported'));
        } else {
            $msg .= $this->l('An error occured while exporting the products');
        }

        if ($send && $count && !$this->runModeCron) {
            Mirakl::updateConfig(Mirakl::CONFIG_LAST_UPDATE, date('Y-m-d H:i:s'));
            Mirakl::updateConfig(Mirakl::CONFIG_LAST_UPDATE_URL, $urls, true);
        } elseif ($send && $this->runModeCron) {
            Mirakl::updateConfig(Mirakl::CONFIG_LAST_UPDATE_CRON, date('Y-m-d H:i:s'));
        } elseif (!$count) {
            $msg = $this->l('Nothing to Update');
        }

        $json = Tools::jsonEncode(
            array(
                'error' => count($this->errors),
                'errors' => count($this->errors) ? $this->errors : null,
                'msg' => $msg,
                'output' => $output,
                'urls' => $urls,
                'count' => $count
            )
        );

        header('Content-Type: application/json', true, 200);
        echo $json;
        exit;

        // jQuery Output or PHP Output
//        if (($callback = Tools::getValue('callback'))) { // jquery
//            echo (string)$callback.'('.$json.')';
//        } else { // cron
//            return $json;
//        }
//        die;
    }

    private function productUpdateLite()
    {
        $country_iso_code = Tools::strtolower(Configuration::get('PS_LOCALE_COUNTRY'));
        $id_country = (int)Country::getByIso($country_iso_code);

        $id_default_customer_group = self::getConfig(self::CONFIG_CUSTOMER_GROUP);
        if (!$id_default_customer_group) {
            $id_default_customer_group = Configuration::get('PS_CUSTOMER_GROUP');
        }

        $latency = Mirakl::getConfig(Mirakl::CONFIG_DELIVERY_TIME);

        $any_customer_address = Db::getInstance()->getRow('SELECT id_address FROM `'._DB_PREFIX_.'address` WHERE id_country='.(int)$id_country.' AND id_customer > 0');
        $id_address = isset($any_customer_address['id_address']) ? (int)$any_customer_address['id_address'] : null;

        $this->loadFeatures();
        $this->loadAttributes();

        $error = false;
        $count = $p = 0;
        $history = array();
        $sku_history = array();
        $duplicateAlongCategories = array();

        $this->cleanup();

        $id_lang = $this->id_lang;

        if ($this->marketplace_id_lang) {
            $id_lang = $this->marketplace_id_lang;
        }

        if ($this->debug) {
            echo 'Id Lang: '.$id_lang.self::$brlf;
        }

        $urls = array();
        $to_mirakl = array();
        $offers_errors = array();

        $miraklSupport = new MiraklSupport();

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $id_shop = 1;
            $id_warehouse = null;
            $default_category = 1;
        } else {
            $default_category = $this->context->shop->id_category;

            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                $id_warehouse = Mirakl::getConfig(Mirakl::CONFIG_WAREHOUSE);
            }

            if (empty($id_warehouse) || !is_numeric($id_warehouse)) {
                $id_warehouse = null;
            }

            if (Shop::isFeatureActive()) {
                $id_shop = (int)Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
            } else {
                $id_shop = 1;
            }
        }

        $create_in_stock = false;
        $all_offers = false;
        $matching = false;

        // Parameters
        if (Tools::getValue('all-offers')) {
            $all_offers = true;
        }

        if (Tools::getValue('update-in-stock')) {
            $create_in_stock = true;
        }

        if (Tools::getValue('replace')) {
            $this->operation = self::REPLACE;
        }

        if (Tools::getValue('matching')) {
            $matching = true;
        }

        if (Tools::getValue('send-offers')) {
            $send = true;
        } elseif ($this->runModeCron) {
            $send = true;
        } else {
            $send = false;
        }

        $date_start_init = MiraklTools::oldest();
        $date_start_init = str_replace('/', '-', Tools::substr($date_start_init, 0, strpos($date_start_init, ' ')));

        $marketplace_params = self::$marketplace_params;

        if ($this->runModeCron) {
            $create_in_stock = false;

            if (Tools::getValue('force')) {
                $cron_date = $date_start_init;
            } else {
                $cron_date = Mirakl::getConfig(Mirakl::CONFIG_LAST_UPDATE_CRON_LITE);
            }

            if (!$cron_date) {
                $date_from = $date_start_init;
            } else {
                $date_from = $cron_date;
            }
        } else {
            $date_from = Tools::getValue('last-update');

            if (!$date_from) {
                $date_from = date('Y-m-d', strtotime('yesterday'));
            }
        }

        if ($all_offers) {
            $date_from = $date_start_init;
        }

        // Categories Settings
        $default_categories = Mirakl::getConfig(Mirakl::CONFIG_CATEGORIES);
        if (!count($default_categories)) {
            $this->errors[] = sprintf(
                '%s(%d):',
                basename(__FILE__),
                __LINE__,
                $this->l('You must configure the categories to update')
            );
            $error = true;
        }

        $default_profiles = $this->mkpAllProfiles;
        $default_profiles2categories = Mirakl::getConfig(Mirakl::CONFIG_PROFILE_TO_CATEGORY, true);

        // Prices Parameters
        $use_taxes      = (bool)Mirakl::getConfig(Mirakl::CONFIG_USE_TAXES);
        $use_specials   = (bool)Mirakl::getConfig(Mirakl::CONFIG_USE_SPECIALS);
        $smart_rounding = (bool)Mirakl::getConfig(Mirakl::CONFIG_SMART_ROUNDING);

        if (is_array($marketplace_params) && array_key_exists('separator', $marketplace_params)
            && Tools::strlen($marketplace_params['separator'])) {
            $separator = $marketplace_params['separator'];
        } else {
            $separator = '.';
        }

        $decription_field = Mirakl::getConfig(Mirakl::CONFIG_DESCRIPTION_FIELD);
        $decription_field = ($decription_field ? $decription_field : Mirakl::FIELD_DESCRIPTION_LONG);
        $decription_html = (bool)Mirakl::getConfig(Mirakl::CONFIG_DESCRIPTION_HTML);

        $from_currency = new Currency((int)(Configuration::get('PS_CURRENCY_DEFAULT')));
        $to_currency = $from_currency;

        if (array_key_exists('currency', $marketplace_params) && Tools::strlen($marketplace_params['currency'])) {
            $to_currency = new Currency(Currency::getIdByIsoCode($marketplace_params['currency']));

            if (!Validate::isLoadedObject($to_currency)) {
                $this->errors[] = sprintf(
                    '%s(%d): %s(%s)',
                    basename(__FILE__),
                    __LINE__,
                    $this->l('Unable to load currency'),
                    $marketplace_params['currency']
                );
                $error = true;
            }
        }

        if ($from_currency->iso_code != $to_currency->iso_code) {
            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                $this->context->currency = $to_currency;
            } else {
                $cart = $this->context->cart;
                $cookie = $this->context->cookie;
                $cart->id_currency = $cookie->id_currency = $to_currency->id;
            }
        }

        $product_name_pre = (int)Mirakl::getConfig(Mirakl::CONFIG_PRODUCT_NAME);
        $product_name_format = ($product_name_pre ? $product_name_pre : self::NAME_NAME_ONLY);

        // Condition Map: Sep-25-2018: Remove

        // Exclusions
        $excluded_manufacturers = Mirakl::getConfig(Mirakl::CONFIG_FILTER_MFR, true);
        $excluded_suppliers = Mirakl::getConfig(Mirakl::CONFIG_FILTER_SUPPLIERS, true);

        // Path to XML
        $output_dir = $this->export;


        // Files
        $this->filename = Tools::strtolower(MiraklTools::getFriendlyUrl(Configuration::get('PS_SHOP_NAME'))).'-offers-lite.csv';
        $offers_file = $output_dir.DS.$this->filename;

        // Carriers
        $carriers = Carrier::getCarriers($id_lang);
        $carrier_tax_rate = null;

        // Carrier Configuration - Standard
        $selected_carrier = (int)Mirakl::getConfig(Mirakl::CONFIG_CARRIER);
        $id_carrier = null;

        if ((int)$selected_carrier && is_array($carriers) && count($carriers)) {
            foreach ($carriers as $carrier) {
                if ((int)$carrier['id_carrier'] == (int)$selected_carrier) {
                    $id_carrier = $carrier['id_carrier'];
                }
            }
        }

        if ($id_carrier == null) {
            $id_carrier = $selected_carrier;
        }

        if ($id_carrier == null) {
            $this->errors[] = sprintf(
                '%s(%d): %s',
                basename(__FILE__),
                __LINE__,
                $this->l('You must configure your primary carrier')
            );
            $error = true;
        }
        $carrier = new Carrier($id_carrier);

        if (!Validate::isLoadedObject($carrier)) {
            $this->errors[] = sprintf(
                '%s(%d): %s(%d)',
                basename(__FILE__),
                __LINE__,
                $this->l('Unable to load carrier'),
                $id_carrier
            );
            $error = true;
        }

        // Carrier Configuration - Relay
        // $selected_carrier_relay = (int)Mirakl::getConfig(Mirakl::CONFIG_CARRIER_RELAY);
        $selected_carrier_relay = null;
        $id_carrier_relay = null;

        if ((int)$selected_carrier_relay && is_array($carriers) && count($carriers)) {
            foreach ($carriers as $carrier) {
                if ((int)$carrier['id_carrier'] == (int)$selected_carrier_relay) {
                    $id_carrier_relay = $carrier['id_carrier'];
                }
            }
        }

        $carrier_relay = null;

        if ($id_carrier_relay) {
            $carrier_relay = new Carrier($id_carrier_relay);

            if (!Validate::isLoadedObject($carrier_relay)) {
                $this->errors[] = sprintf(
                    '%s(%d): %s(%d)',
                    basename(__FILE__),
                    __LINE__,
                    $this->l('Unable to load carrier'),
                    $id_carrier
                );
                $error = true;
            }
        }

        $marketplace_params = self::$marketplace_params;

        if (is_array($marketplace_params) && array_key_exists('options', $marketplace_params) && count($marketplace_params['options'])) {
            foreach (array_keys($marketplace_params['options']) as $option_field) {
                switch ($option_field) {
                    case 'no-delete':
                        self::$delete_action = 'UPDATE';
                        break;
                    case 'no-expire':
                        self::$no_expire = true;
                        break;
                }
            }
        }

        // Download URLs
        $urls[$this->filename] = sprintf('%s%s', $this->export_url, 'update/'.$this->filename);

        // Check rights
        if (!is_dir($output_dir) && !mkdir($output_dir)) {
            $this->errors[] = sprintf(
                '%s(%s): %s(%s)',
                basename(__FILE__),
                __LINE__,
                $this->l('Unable to create the directory'),
                $output_dir
            );
            $error = true;
        }

        if (!is_writable($output_dir)) {
            chmod($output_dir, 0775);
        }

        if (array_key_exists('ean_field_name', $marketplace_params) && Tools::strlen($marketplace_params['ean_field_name'])) {
            $ean_field_name = $marketplace_params['ean_field_name'];
        } else {
            $ean_field_name = 'EAN';
        }

        // Export Loop
        if (!$error && $default_categories) {
            foreach ($default_categories as $id_category) {
                if ($id_category == $default_category) {
                    continue;
                }

                $profile = isset($default_profiles2categories[$id_category]) ? $default_profiles2categories[$id_category] : null;

                if (!$profile) {
                    $category = new Category($id_category, $id_lang);

                    $this->errors[] = sprintf(
                        '%s(%d): %s - %s (%d)',
                        basename(__FILE__),
                        __LINE__,
                        $this->l('You must sets a profile for this category'),
                        $category->name,
                        $category->id
                    );
                    $error = true;
                    continue;
                }
                $selected_profile = false;
                foreach ($default_profiles['name'] as $selected_profile => $profile_name) {
                    if ($profile_name === $profile) {
                        break;
                    }
                }

                if ($selected_profile === false) {
                    $this->errors[] = sprintf(
                        '%s(%d): %s',
                        basename(__FILE__),
                        __LINE__,
                        $this->l('You must configure your profiles first')
                    );
                    $error = true;
                    continue;
                }

                $profile_name = isset($default_profiles['name'][$selected_profile]) ? $default_profiles['name'][$selected_profile] : '';
                $profile_attr = isset($default_profiles['combinations_attr'][$selected_profile]) ? $default_profiles['combinations_attr'][$selected_profile] : '';
                $min_quantity_alert = isset($default_profiles['min_quantity_alert'][$selected_profile]) ? $default_profiles['min_quantity_alert'][$selected_profile] : '';
                $logistic_class = isset($default_profiles['logistic_class'][$selected_profile]) ? $default_profiles['logistic_class'][$selected_profile] : '';
                $profile_price_rule = isset($default_profiles['price_rule'][$selected_profile]) ? $default_profiles['price_rule'][$selected_profile] : false;
                $profile_shipping_rule = isset($default_profiles['shipping_rule'][$selected_profile]) ? (float)$default_profiles['shipping_rule'][$selected_profile] : 0;
                $warranty = isset($default_profiles['warranty'][$selected_profile]) ? $default_profiles['warranty'][$selected_profile] : '';

                $products = MiraklProduct::getExportProducts(
                    $id_category,
                    $this->onlyActiveOne,
                    $create_in_stock,
                    $date_from,
                    null,
                    $this->debug
                );

                if ($products) {
                    foreach ($products as $product) {
                        $id_product = $product['id_product'];

                        // Products with multiples categories ;
                        if (isset($duplicateAlongCategories[$id_product])) {
                            continue;
                        }
                        $duplicateAlongCategories[$id_product] = true;

                        $details = new Product($id_product, false, $this->id_lang);
                        if (!Validate::isLoadedObject($details)) {
                            $this->errors[] = sprintf($this->l('Could not load the product id: %d'), $id_product);
                            $error = true;
                            continue;
                        }

                        // Filtering Manufacturer & Supplier
                        if ($details->id_manufacturer) {
                            if (is_array($excluded_manufacturers)
                                && in_array($details->id_manufacturer, $excluded_manufacturers)) {
                                continue;
                            }
                        }

                        if ($details->id_supplier) {
                            if (is_array($excluded_suppliers) && in_array($details->id_supplier, $excluded_suppliers)) {
                                continue;
                            }
                        }

                        // RDC only sells NEW products, skip the used one
                        /*if (Tools::strtolower($details->condition) != 'new') {
                            if ($this->debug) {
                                $this->errors[] = sprintf($this->l('Product ID #%d is not NEW.'), $id_product);
                            }
                            continue;
                        }*/

                        $manufacturer = Manufacturer::getNameById((int)$details->id_manufacturer);

                        $product_features = $details->getFeatures();

                        // Product Options
                        $options = MiraklProduct::getProductOptions($id_product, $id_lang);

                        $disabled = $options['disable'] ? true : false;
                        $force = $options['force'] ? true : false;
                        $latency_override = (int)$options['shipping'] ? (int)$options['shipping'] : null;

                        if ($disabled) {
                            continue;
                        }

                        $price_override = false;
                        if (!empty($options['price']) && is_numeric((float)$options['price'])) {
                            $price_override = (float)$options['price'];
                        }

                        if (!$details->active && $this->onlyActiveOne) {
                            continue;
                        }

                        // Product Combinations
                        if (version_compare(_PS_VERSION_, '1.5', '<')) {
                            $combinations = $details->getAttributeCombinaisons($id_lang);
                        } else {
                            $combinations = $details->getAttributeCombinations($id_lang);
                        }

                        // Pas de combinaison, on en cr?e une fictive pour rentrer dans la boucle
                        if (!is_array($combinations) || !count($combinations)) {
                            $combinations = array(
                                0 => array(
                                    'reference' => $details->reference,
                                    'ean13' => $details->ean13,
                                    'id_product_attribute' => 0,
                                    'id_attribute_group' => 0,
                                    'id_attribute' => 0,
                                    'minimal_quantity' => $details->minimal_quantity,
                                    'meta_title' => $details->meta_title,
                                    'unity' => $details->unity,
                                    'weight' => 0,
                                    'meta_description' => $details->meta_description,
                                    'default_on' => 0
                                )
                            );
                        }
                        // Grouping Combinations
                        asort($combinations);

                        $group_details = array();

                        foreach ($combinations as $combination) {
                            $id_product_attribute = isset($combination['id_product_attribute']) ? $combination['id_product_attribute'] : 0;
                            $id_attribute_group = isset($combination['id_attribute_group']) ? $combination['id_attribute_group'] : 0;
                            $id_attribute = isset($combination['id_attribute']) ? $combination['id_attribute'] : 0;

                            $group_details[$id_product_attribute][$id_attribute_group] = array();
                            $group_details[$id_product_attribute][$id_attribute_group]['reference'] = $combination['reference'];
                            $group_details[$id_product_attribute][$id_attribute_group]['weight'] = $combination['weight'];
                            $group_details[$id_product_attribute][$id_attribute_group]['unity'] = isset($combination['unity']) ? $combination['unity'] : $details->unity;
                            $group_details[$id_product_attribute][$id_attribute_group]['id_attribute_group'] = $combination['id_attribute_group'];
                            $group_details[$id_product_attribute][$id_attribute_group]['id_attribute'] = $combination['id_attribute'];

                            // Synch Field (EAN, UPC, SKU ...)
                            $group_details[$id_product_attribute][$id_attribute_group]['ean13'] = $combination['ean13'];

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
                        foreach ($group_details as $id_product_attribute => $combination) {
                            $idx++;
                            $group_detail = array();

                            $id_attribute_group = isset($combination['id_attribute_group']) ? $combination['id_attribute_group'] : 0;
                            $id_attribute = isset($combination['id_attribute']) ? $combination['id_attribute'] : 0;

                            $ean13 = '';
                            $reference = '';
                            $weight = $details->weight;
                            $attributes_desc = null;
                            $unity = null;

                            foreach ($combination as $group_detail) {
                                if ($group_detail['attribute_name']) {
                                    if ($profile_attr != Mirakl::ATTRIBUTES_NO) {
                                        if ($profile_attr == Mirakl::ATTRIBUTES_LONG) {
                                            $attributes_desc .= sprintf(
                                                '%s: %s - ',
                                                $group_detail['group_name'],
                                                $group_detail['attribute_name']
                                            );
                                        } elseif ($profile_attr == Mirakl::ATTRIBUTES_SHORT) {
                                            $attributes_desc .= sprintf('%s - ', $group_detail['attribute_name']);
                                        }
                                    }
                                }
                                if (isset($group_detail['reference']) && !empty($group_detail['reference'])) {
                                    $reference = $group_detail['reference'];
                                }
                                if (isset($group_detail['ean13'])) {
                                    $ean13 = $group_detail['ean13'];
                                }

                                $weight = (float)$details->weight + (float)$group_detail['weight'];
                                $unity = isset($group_detail['unity']) && (int)$group_detail['unity'] ? (int)$group_detail['unity'] : (int)$details->unity;
                            }

                            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                                $supplier_reference = ProductSupplier::getProductSupplierReference(
                                    $id_product,
                                    $id_product_attribute,
                                    $details->id_supplier
                                );
                            } else {
                                $supplier_reference = $details->supplier_reference;
                            }

                            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                                $quantity = Product::getRealQuantity(
                                    $details->id,
                                    $id_product_attribute ? $id_product_attribute : null,
                                    $id_warehouse,
                                    $id_shop
                                );
                            } else {
                                $quantity = (int)MiraklProduct::getProductQuantity($id_product, $id_product_attribute);
                            }

                            if ($disabled) {
                                $quantity = 0;
                            }

                            if ($force) {
                                $quantity = 999;
                            }

                            if (!$quantity && $create_in_stock) {
                                if ($this->debug) {
                                    $this->errors[] = sprintf(
                                        $this->l('Skipping out of stock product: %d'),
                                        $id_product
                                    );
                                }
                                continue;
                            }

                            // Force $id_default_group again to make sure the getPrice() method returns the
                            // correct price for the correct customer group
                            Context::getContext()->customer->id_default_group = $id_default_customer_group;

                            // Price
                            $priceResolver = $this->resolvePrice($options, $profile_price_rule, $profile_shipping_rule, $details, $id_product_attribute);
                            $price = $priceResolver['tax_incl'];
                            $price_tax_excl = $priceResolver['tax_excl'];

                            $sales = array();

                            // Apply Sales for PS > 1.4
                            if (!$price_override && version_compare(_PS_VERSION_, '1.4', '>=') && $use_specials) {
                                $sales = $this->resolveSalePrice(
                                    $details,
                                    $id_default_customer_group,
                                    $id_product_attribute,
                                    $price,
                                    $profile_shipping_rule,
                                    $profile_price_rule,
                                    $use_taxes,
                                    $use_specials,
                                    $smart_rounding,
                                    $separator
                                );
                            }

                            if (empty($reference)) {
                                $offers_errors['empty_reference'][] = array(
                                    'ean13' => $ean13,
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute
                                );
                                continue;
                            }

                            if ($matching && !(int)$ean13) {
                                $offers_errors['missing_ean_matching'][] = array(
                                    'reference' => $reference,
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute
                                );
                                continue;
                            }

                            if ((int)$ean13) {
                                if (!MiraklTools::eanupcCheck($ean13)) {
                                    $offers_errors['wrong_ean'][$ean13] = array(
                                        'ean13' => $ean13,
                                        'id_product' => $id_product,
                                        'id_product_attribute' => $id_product_attribute
                                    );
                                    continue;
                                }
                                if ($ean13 && Tools::strlen($ean13) && isset($history[$ean13])) {
                                    $offers_errors['duplicate_ean'][$ean13] = array(
                                        'reference' => $reference,
                                        'ean13' => $ean13,
                                        'id_product' => $id_product,
                                        'id_product_attribute' => $id_product_attribute
                                    );
                                    continue;
                                }
                                $history[$ean13] = true;
                            }

                            if (Tools::strlen($reference) && isset($sku_history[$reference])) {
                                $offers_errors['duplicate_reference'][] = array(
                                    'reference' => $reference,
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute
                                );
                                continue;
                            }
                            $sku_history[$reference] = true;

                            $ecotax_rate = null;
                            $ecotax = null;

                            if (version_compare(_PS_VERSION_, '1.4', '>=')) {
                                $ecotax_rate = (float)Tax::getProductEcotaxRate();

                                if ($use_taxes) {
                                    $ecotax_tax_amount = Tools::ps_round($ecotax, 2);
                                    $ecotax_tax_amount = Tools::ps_round(
                                        $ecotax_tax_amount * (1 + $ecotax_rate / 100),
                                        2
                                    );

                                    $ecotax = $ecotax_tax_amount;
                                }
                            }

                            //
                            // Product Name
                            //
                            switch ($product_name_format) {
                                case self::NAME_NAME_ATTRIBUTES:
                                    if ($attributes_desc) {
                                        $name = rtrim(sprintf(
                                            '%s (%s)',
                                            $details->name,
                                            rtrim($attributes_desc, ' - ')
                                        ), ' - ');
                                        $name = trim(rtrim($name, '-'));
                                        $product_name = $name;
                                    } else {
                                        $product_name = $details->name;
                                    }
                                    break;
                                case self::NAME_BRAND_NAME_ATTRIBUTES:
                                    if ($attributes_desc) {
                                        $name = rtrim(sprintf(
                                            '%s - %s (%s)',
                                            $manufacturer,
                                            $details->name,
                                            rtrim($attributes_desc, ' - ')
                                        ), ' - ');
                                        $name = trim(rtrim($name, '-'));
                                        $product_name = $name;
                                    } else {
                                        $product_name = sprintf('%s - %s', $manufacturer, $details->name);
                                    }
                                    break;
                                case self::NAME_NAME_BRAND_ATTRIBUTES:
                                    if ($attributes_desc) {
                                        $name = rtrim(sprintf(
                                            '%s - %s - (%s)',
                                            $details->name,
                                            $manufacturer,
                                            rtrim($attributes_desc, ' - ')
                                        ), ' - ');
                                        $name = trim(rtrim($name, '-'));
                                        $product_name = $name;
                                    } else {
                                        $product_name = sprintf('%s - %s', $details->name, $manufacturer);
                                    }
                                    break;
                                case self::NAME_NAME_REFERENCE:
                                    $product_name = $details->name;

                                    if ($reference) {
                                        $product_name = sprintf('%s - %s', $product_name, $reference);
                                    }
                                    break;
                                default:
                                    $product_name = $details->name;
                                    break;
                            }

                            $to_mirakl[$p]['ProductName'] = $product_name;

                            // Carrier Taxes
                            if ($carrier_tax_rate == null) {
                                if (method_exists('Carrier', 'getTaxesRate')) {
                                    $carrier = new Carrier($id_carrier);

                                    if (Validate::isLoadedObject($carrier)) {
                                        $tax_address = new Address((int)Mirakl::getConfig(Mirakl::CONFIG_ADDRESS_ID));

                                        if (Validate::isLoadedObject($tax_address)) {
                                            $carrier_tax_rate = (float)$carrier->getTaxesRate($tax_address);
                                        }
                                    }
                                } elseif (method_exists('Tax', 'getCarrierTaxRate')) {
                                    if ($id_carrier) {
                                        $carrier = new Carrier($id_carrier);

                                        if (Validate::isLoadedObject($carrier)) {
                                            $carrier_tax_rate = (float)Tax::getCarrierTaxRate($id_carrier, (int)Mirakl::getConfig(Mirakl::CONFIG_ADDRESS_ID));
                                        }
                                    }
                                }
                            }

                            if ($matching) {
                                $product_id_type = $ean_field_name;

                                if (in_array(MiraklMarketplace::getCurrentMarketplace(), array('gosport', 'intermarche'))) {
                                    $product_id_type = Tools::strtolower($product_id_type);
                                }

                                $product_id = $ean13;
                                $operation_message = sprintf(
                                    'Matching attempt for %s, %s',
                                    $product_name,
                                    date('Y-m-d H:i:s')
                                );
                            } else {
//                                $product_id = 'SKU';
//                                $product_id_type = $reference;
                                $product_id = null;
                                $product_id_type = null;
                                $operation_message = sprintf(
                                    'Offer update for %s, %s',
                                    $product_name,
                                    date('Y-m-d H:i:s')
                                );
                            }

                            $description = null;
//                            $description = "REBAJAS1";

                            $to_mirakl[$p]['sku'] = $reference;

                            if ($product_id_type) {
                                $to_mirakl[$p]['product-id'] = $product_id_type == 'EAN' ?
                                    sprintf('%013s', $product_id) : $product_id;
                                $to_mirakl[$p]['product-id-type'] = $product_id_type;
                            }

                            if (is_array($marketplace_params) && array_key_exists('separator', $marketplace_params) && Tools::strlen($marketplace_params['separator'])) {
                                $separator = $marketplace_params['separator'];
                            } else {
                                $separator = '.';
                            }

                            $to_mirakl[$p]['description'] = $description;
                            // $to_mirakl[$p]['description-short'] = $details->description_short;
                            // $to_mirakl[$p]['internal-description'] = $operation_message;

                            if ($smart_rounding) {
                                $to_mirakl[$p]['price'] = MiraklTools::smartRounding($price, $separator);
                            } else {
                                $to_mirakl[$p]['price'] = number_format(Tools::ps_round($price, 2), 2, $separator, '');
                            }
                            $to_mirakl[$p]['price-tax-excl'] = $price_tax_excl;

                            $to_mirakl[$p]['price-additional-info'] = '';
                            $to_mirakl[$p]['quantity'] = ($quantity < 0) ? 0 : $quantity;
                            $to_mirakl[$p]['weight'] = sprintf('%.02f', $weight);
                            if (in_array(MiraklMarketplace::getCurrentMarketplace(), array('eprice'))) {
                                $to_mirakl[$p]['weight'] *= 1000;
                            }

                            $to_mirakl[$p]['min-quantity-alert'] = $min_quantity_alert;

                            if (array_key_exists('conditions', $marketplace_params) && count($marketplace_params['conditions'])) {
                                $product_condition = MiraklTools::toKey($details->condition);

                                if (array_key_exists($product_condition, $marketplace_params['conditions'])) {
                                    $to_mirakl[$p]['state'] = $marketplace_params['conditions'][$product_condition];
                                } else {
                                    $to_mirakl[$p]['state'] = 11;
                                }
                            } else {
                                $to_mirakl[$p]['state'] = 11;
                            }

                            $to_mirakl[$p]['logistic-class'] = $logistic_class;

                            if (!self::$no_expire) {
                                $to_mirakl[$p]['available-start-date'] = date('Y-m-d', strtotime('now'));
                                $to_mirakl[$p]['available-end-date'] = date('Y-m-d', strtotime('now + 30 day'));
                            } else {
                                $to_mirakl[$p]['available-start-date'] = '';
                                $to_mirakl[$p]['available-end-date'] = '';
                            }

                            // Optionnal/Marketplace custom fields ;
                            if (is_array($marketplace_params) && array_key_exists('fields', $marketplace_params)) {
                                foreach ($marketplace_params['fields'] as $field) {
                                    switch ($field['prestashop']) {
                                        case 'vat':
                                            $product_tax_rate = null;

                                            if (method_exists('Tax', 'getProductTaxRate') && $id_address) {
                                                $product_tax_rate = (float)(Tax::getProductTaxRate(
                                                    $details->id,
                                                    $id_address
                                                ));
                                            } elseif ($id_address) {
                                                $product_tax_rate = (float)(Tax::getApplicableTax(
                                                    $details->id_tax,
                                                    $details->tax_rate,
                                                    $id_address
                                                ));
                                            }
                                            if ($product_tax_rate) {
                                                $to_mirakl[$p][$field['mirakl']] = $product_tax_rate;
                                            } else {
                                                $to_mirakl[$p][$field['mirakl']] = $field['default'];
                                            }

                                            break;

                                        case 'ecotax':
                                            $ecotax_rate = null;

                                            if (version_compare(_PS_VERSION_, '1.4', '>=')) {
                                                $ecotax_rate = (float)Tax::getProductEcotaxRate();

                                                if ($use_taxes && $ecotax_rate) {
                                                    $ecotaxTaxAmount = Tools::ps_round(
                                                        $ecotax * ($ecotax_rate / 100),
                                                        2
                                                    );

                                                    $ecotax += $ecotaxTaxAmount;
                                                }
                                            }
                                            if ($ecotax_rate) {
                                                $to_mirakl[$p][$field['mirakl']] = $ecotax_rate;
                                            } else {
                                                $to_mirakl[$p][$field['mirakl']] = $field['default'];
                                            }

                                            break;

                                        case 'on_sale':
                                            $to_mirakl[$p][$field['mirakl']] = $details->on_sale;
                                            break;

                                        case 'striked_price':
                                            $striked_price = $details->getPrice(
                                                $use_taxes,
                                                $id_product_attribute,
                                                2,
                                                null,
                                                false,
                                                false
                                            );

                                            if ($smart_rounding) {
                                                $to_mirakl[$p][$field['mirakl']] = MiraklTools::smartRounding(
                                                    $striked_price,
                                                    $separator
                                                );
                                            } else {
                                                $to_mirakl[$p][$field['mirakl']] = number_format(Tools::ps_round(
                                                    $striked_price,
                                                    2,
                                                    $separator,
                                                    ''
                                                ), 2);
                                            }

                                            break;

                                        case 'discount_price':
                                            if (array_key_exists('salePrice', $sales) && (float)$sales['salePrice']) {
                                                $to_mirakl[$p][$field['mirakl']] = $sales['salePrice'];

                                                if ($smart_rounding) {
                                                    $to_mirakl[$p]['price'] = MiraklTools::smartRounding(
                                                        $price,
                                                        $separator
                                                    );
                                                } else {
                                                    $to_mirakl[$p]['price'] = number_format(
                                                        Tools::ps_round($price, 2),
                                                        2,
                                                        $separator,
                                                        ''
                                                    );
                                                }
                                            } else {
                                                $to_mirakl[$p][$field['mirakl']] = null;
                                            }

                                            break;

                                        case 'discount_start_date':
                                            if (array_key_exists('dateStart', $sales) && (float)$sales['dateStart']) {
                                                $to_mirakl[$p][$field['mirakl']] = $sales['dateStart'];
                                            } else {
                                                $to_mirakl[$p][$field['mirakl']] = null;
                                            }

                                            break;
                                        case 'discount_end_date':
                                            if (array_key_exists('dateEnd', $sales) && (float)$sales['dateEnd']) {
                                                $to_mirakl[$p][$field['mirakl']] = $sales['dateEnd'];
                                            } else {
                                                $to_mirakl[$p][$field['mirakl']] = null;
                                            }

                                            break;

                                        case 'latency':
                                            $delivery_time = $latency_override ? $latency_override : $latency;

                                            if (is_numeric($delivery_time)) {
                                                $to_mirakl[$p][$field['mirakl']] = $delivery_time;
                                            } else {
                                                $to_mirakl[$p][$field['mirakl']] = $field['default'];
                                            }
                                            break;

                                        case 'ean':
                                            $to_mirakl[$p][$field['mirakl']] = $ean13;
                                            break;

                                        case 'referenciagenericaeci':
                                            if (property_exists($details, 'referenciagenericaeci') && $details->referenciagenericaeci) {
                                                $to_mirakl[$p][$field['mirakl']] = $details->referenciagenericaeci;
                                            } else {
                                                try {
                                                    $to_mirakl[$p][$field['mirakl']] = Db::getInstance()->getValue(
                                                        'SELECT `referenciagenericaeci` FROM `'._DB_PREFIX_.'product` WHERE `id_product` = '.(int)$details->id
                                                    );
                                                } catch (PrestaShopDatabaseException $exception) {
                                                }
                                            }

                                            if (!isset($to_mirakl[$p][$field['mirakl']]) || !$to_mirakl[$p][$field['mirakl']]) {
                                                $to_mirakl[$p][$field['mirakl']] = $details->isbn;
                                            }

                                            if (!isset($to_mirakl[$p][$field['mirakl']]) || !$to_mirakl[$p][$field['mirakl']]) {
                                                $to_mirakl[$p][$field['mirakl']] = $field['default'];
                                            }
                                            break;

                                        default:
                                            $to_mirakl[$p][$field['mirakl']] = $field['default'];
                                            break;
                                    }
                                }
                            }

                            // Additionnals/Marketplace custom fields ;
                            if (is_array($marketplace_params) && array_key_exists('additionnals', $marketplace_params)) {
                                foreach ($marketplace_params['additionnals'] as $additionnal) {
                                    $field_name = $additionnal['mirakl'];

                                    if (empty($field_name)) {
                                        continue;
                                    }
                                    $default = $additionnal['default'];
                                    $required = (bool)$additionnal['required'];

                                    if (isset($default_profiles[$field_name][$selected_profile])) {
                                        $selected_field = explode(
                                            '-',
                                            $default_profiles[$field_name][$selected_profile]
                                        );
                                        $field_type = isset($selected_field[0]) ? $selected_field[0] : null;
                                        $field_id = isset($selected_field[1]) ? $selected_field[1] : null;

                                        if ($required && ($field_type == null || $field_id == null) && !Tools::strlen($default)) {
                                            $offers_errors['missing_field'][$field_name] = array(
                                                'reference' => $reference,
                                                'id_product' => $id_product,
                                                'id_product_attribute' => $id_product_attribute
                                            );
                                            unset($to_mirakl[$p]);
                                            continue;
                                        }
                                        $to_mirakl[$p][$field_name] = $default;

                                        switch ($field_type) {
                                            case 'a'://attributes
                                                if (array_key_exists($field_id, $combination)) {
                                                    $to_mirakl[$p][$field_name] = $combination[$field_id]['attribute_name'];
                                                }
                                                break;
                                            case 'f'://features
                                                $features_value = array();

                                                if (is_array($product_features)) {
                                                    foreach ($product_features as $feature) {
                                                        $id_feature = (int)$feature['id_feature'];
                                                        $id_feature_value = (int)$feature['id_feature_value'];

                                                        if ($id_feature != $field_id) {
                                                            continue;
                                                        }

                                                        $features_values = FeatureValue::getFeatureValuesWithLang(
                                                            $id_lang,
                                                            $id_feature,
                                                            $feature['custom']
                                                        );

                                                        foreach ($features_values as $features_value) {
                                                            if ((int)$features_value['id_feature_value'] == $id_feature_value) {
                                                                break;
                                                            } else {
                                                                $features_value = null;
                                                            }
                                                            break;
                                                        }
                                                    }
                                                }
                                                if (is_array($features_value) && array_key_exists('value', $feature)) {
                                                    $to_mirakl[$p][$field_name] = $features_value['value'];
                                                }
                                                break;

                                            case 'p'://prestashop field
                                                switch ($field_id) {
                                                    case Mirakl::REFERENCE:
                                                        $value = $reference;
                                                        break;
                                                    case Mirakl::SUPPLIER_REFERENCE:
                                                        $value = $supplier_reference;
                                                        break;
                                                    case Mirakl::MANUFACTURER:
                                                        $value = Manufacturer::getNameById($details->id_manufacturer);
                                                        break;
                                                    case Mirakl::CATEGORY:
                                                        $category = new Category($id_category);
                                                        $value = $category->name[$id_lang];
                                                        break;
                                                    case Mirakl::META_TITLE:
                                                        $value = $combination['meta_title'];
                                                        break;
                                                    case Mirakl::META_DESCRIPTION:
                                                        $value = $combination['meta_description'];
                                                        break;
                                                    case Mirakl::UNITY:
                                                        $value = $unity;
                                                        break;
                                                    case Mirakl::WEIGHT:
                                                        $value = $weight;
                                                        break;
                                                    case Mirakl::UID:
                                                        $value = sprintf('EAN|%s', $details->ean13);
                                                        break;
                                                    case Mirakl::WARRANTY:
                                                        $value = (int)$warranty;
                                                        break;
                                                    default:
                                                        $value = null;
                                                        break;
                                                }
                                                if (Tools::strlen($value)) {
                                                    $to_mirakl[$p][$field_name] = $value;
                                                }
                                                break;
                                        }
                                        if ((!isset($to_mirakl[$p][$field_name]) || empty($to_mirakl[$p][$field_name])) && isset($additionnal['required']) && $additionnal['required'] == true) {
                                            $this->errors[] = sprintf(
                                                $this->l('Missing mandatory field').': %s',
                                                $field_name
                                            );
                                            $error = true;
                                            continue;
                                        }
                                    }
                                }
                            }

                            if (!isset($to_mirakl[$p])) {
                                continue;
                            }

                            if (!$matching) {
                                $to_mirakl[$p]['update-delete'] = '';
                            }

                            if (is_array($marketplace_params) && array_key_exists('exclude', $marketplace_params) && count($marketplace_params['exclude'])) {
                                foreach (array_keys($marketplace_params['exclude']) as $exclude_field) {
                                    if (array_key_exists($exclude_field, $to_mirakl[$p])) {
                                        unset($to_mirakl[$p][$exclude_field]);
                                    }
                                }
                            }

                            if ($this->debug) {
                                echo 'Content:'.nl2br(print_r($to_mirakl[$p], true)).self::$brlf;
                                echo self::$brlf;
                                echo 'Memory: '.number_format(memory_get_usage() / 1024).'k';
                            }

                            $count++;
                            $p++;

                            if ($this->debug) {
                                printf(
                                    'Exporting Product: %d id: %d reference: %s %s',
                                    $idx,
                                    $details->id,
                                    $reference,
                                    self::$brlf
                                );
                            }
                        } // end foreach combinations
                    }
                } // end foreach products
            } // end foreach categories
        } // end if

        if (is_array($offers_errors)) {
            foreach (array(
                         'empty_reference',
                         'duplicate_ean',
                         'duplicate_reference',
                         'wrong_ean',
                         'missing_ean',
                         'missing_field'
                     ) as $error_type) {
                if (isset($offers_errors[$error_type]) && is_array($offers_errors[$error_type]) && count($offers_errors[$error_type])) {
                    $msg = null;
                    foreach ($offers_errors[$error_type] as $value => $product_error) {
                        switch ($error_type) {
                            case 'empty_reference':
                                if ($msg == null) {
                                    $msg = $this->l('Products having empty references, Product ID').': [';
                                }

                                if ($product_error['id_product_attribute']) {
                                    $msg .= sprintf(
                                        '%d/%d, ',
                                        $product_error['id_product'],
                                        $product_error['id_product_attribute']
                                    );
                                } else {
                                    $msg .= sprintf('%d, ', $product_error['id_product']);
                                }

                                break;

                            case 'duplicate_ean':
                                if ($msg == null) {
                                    $msg = $this->l('Duplicate EAN entry for product, References').': [';
                                }

                                $msg .= sprintf(
                                    '%s, ',
                                    $product_error['reference'] ? $product_error['reference'] : '#'.$product_error['id_product']
                                );
                                break;

                            case 'missing_ean':
                                if ($msg == null) {
                                    $msg = $this->l('EAN is missing, References').': [';
                                }

                                $msg .= sprintf(
                                    '%s, ',
                                    $product_error['reference'] ? $product_error['reference'] : '#'.$product_error['id_product']
                                );
                                break;

                            case 'missing_ean_matching':
                                if ($msg == null) {
                                    $msg = $this->l('Products without EAN ignored in matching mode, References').': [';
                                }

                                $msg .= sprintf(
                                    '%s, ',
                                    $product_error['reference'] ? $product_error['reference'] : '#'.$product_error['id_product']
                                );
                                break;

                            case 'duplicate_reference':
                                if ($msg == null) {
                                    $msg = $this->l('Duplicate reference entry for product, Product ID').': [';
                                }

                                if ($product_error['id_product_attribute']) {
                                    $msg .= sprintf(
                                        '%d/%d, ',
                                        $product_error['id_product'],
                                        $product_error['id_product_attribute']
                                    );
                                } else {
                                    $msg .= sprintf('%d, ', $product_error['id_product']);
                                }
                                break;

                            case 'wrong_ean':
                                if ($msg == null) {
                                    $msg = $this->l('EAN is incorrect, Product ID').': [';
                                }

                                if ($product_error['id_product_attribute']) {
                                    $msg .= sprintf(
                                        '%d/%d, ',
                                        $product_error['id_product'],
                                        $product_error['id_product_attribute']
                                    );
                                } else {
                                    $msg .= sprintf('%d, ', $product_error['id_product']);
                                }
                                break;

                            case 'missing_field':
                                if ($msg == null) {
                                    $msg = $this->l('Missing Mandatory profile field').': ['.$value;
                                }
                                break;
                        }
                    }

                    if ($msg) {
                        $msg = rtrim($msg, ', ').']';

                        switch ($error_type) {
                            case 'empty_reference':
                                $msg = $miraklSupport->message($msg, MiraklSupport::FUNCTION_EXPORT_EMPTY_REFERENCE);
                                break;
                            case 'duplicate_reference':
                                $msg = $miraklSupport->message($msg, MiraklSupport::FUNCTION_EXPORT_DUPLICATE);
                                break;
                            case 'wrong_ean':
                                $msg = $miraklSupport->message($msg, MiraklSupport::FUNCTION_EXPORT_WRONG_EAN);
                                break;
                            case 'duplicate_ean':
                                $msg = $miraklSupport->message($msg, MiraklSupport::FUNCTION_EXPORT_DUPLICATE_EAN);
                                break;
                            case 'missing_ean':
                                $msg = $miraklSupport->message($msg, MiraklSupport::FUNCTION_EXPORT_MISSING_EAN);
                                break;
                            case 'missing_ean_matching':
                                $msg = $miraklSupport->message(
                                    $msg,
                                    MiraklSupport::FUNCTION_EXPORT_MISSING_EAN_MATCHING
                                );
                                break;
                            default:
                                $msg .= nl2br("\n\n");
                                break;
                        }
                        $this->errors[] = $msg;
                    }
                }
            }
        }

        foreach ($to_mirakl as $key => $product) {
            $to_mirakl[$key] = array_filter($product, function ($index) {
                return in_array($index, array('sku', 'quantity'));
            }, ARRAY_FILTER_USE_KEY);
        }

        $msg = '';

        if (isset($to_mirakl) && is_array($to_mirakl) && $count = count($to_mirakl)) {
            if (!($response = $this->updateOffers($offers_file, $to_mirakl, $send))) {
                $this->errors[] = sprintf('UpdateOffers() failed...');
                $error = true;
            }

            if ($send && $this->updateOffersResponse($response)) {
                $msg .= sprintf($this->l('%d offers created... %s').self::$brlf, $count, null);
            } elseif ($send) {
                $this->errors[] = sprintf($this->l('Update offers failed.').self::$brlf, $count);
                $error = true;
            }
        } else {
            $this->errors[] = $this->l('No Offers, nothing to do...');
            $error = true;
        }

        // Export Output
        $output = ob_get_clean();

        if (!$error && $count) {
            $msg .= sprintf('%s %s', $count, $this->l('Products Successfully Exported'));
        } else {
            $msg .= $this->l('An error occured while exporting the products');
        }

        if ($send && $count && !$this->runModeCron) {
            Mirakl::updateConfig(Mirakl::CONFIG_LAST_UPDATE, date('Y-m-d H:i:s'));
            Mirakl::updateConfig(Mirakl::CONFIG_LAST_UPDATE_URL, $urls, true);
        } elseif ($send && $this->runModeCron) {
            Mirakl::updateConfig(Mirakl::CONFIG_LAST_UPDATE_CRON, date('Y-m-d H:i:s'));
        } elseif (!$count) {
            $msg = $this->l('Nothing to Update');
        }

        $json = Tools::jsonEncode(
            array(
                'error' => count($this->errors),
                'errors' => count($this->errors) ? $this->errors : null,
                'msg' => $msg,
                'output' => $output,
                'urls' => $urls,
                'count' => $count
            )
        );

        header('Content-Type: application/json', true, 200);
        echo $json;
        exit;

        // jQuery Output or PHP Output
//        if (($callback = Tools::getValue('callback'))) { // jquery
//            echo (string)$callback.'('.$json.')';
//        } else { // cron
//            return $json;
//        }
//        die;
    }

    private function lastUpdate()
    {
        $callback = Tools::getValue('callback');

        $date = Mirakl::getConfig(Mirakl::CONFIG_LAST_UPDATE);
        $urls = Mirakl::getConfig(Mirakl::CONFIG_LAST_UPDATE_URL, true);

        if (!$date || !$urls) {
            echo (string)$callback.'()';
            exit(0);
        }
        $json = Tools::jsonEncode(
            array(
                'msg' => $this->l('Last Update').': '.$date,
                'urls' => $urls,
                'button' => null
            )
        );

        echo (string)$callback.'('.$json.')';
    }

    private function resolveMkpSpecificFields()
    {
        if ($this->externalMkp->hasAdditionalConfiguration()) {
            $result = array();
            $specificFields = $this->externalMkp->getSpecificFields();

            // Module configuration
            $saved = Mirakl::getConfig(MiraklConstant::CONFIG_MKP_SPECIFIC_FIELDS);

            foreach ($specificFields['specific_fields'] as $fieldName => $definition) {
                $result[$fieldName] = array(
                    'api' => $definition['api'],
                    'value' => isset($saved[$fieldName]) ? $saved[$fieldName] : '',
                );
            }

            $this->mkpSpecificFields = $result;
        }
    }

    private function fulfillMkpSpecificFields($productOptions, $selectedProfileId)
    {
        $result = array();
        $currentMkp = $this->externalMkp->getCurrentMarketplaceR();

        foreach ($this->mkpSpecificFields as $mkpSpecificFieldName => $mkpSpecificField) {
            $mkpSfOverrideByProduct = json_decode($productOptions['mkp_specific_fields'], true);
            if ($this->mkpAllProfiles && isset($this->mkpAllProfiles['specific_fields'])) {
                $mkpSfOverrideByProfile = $this->mkpAllProfiles['specific_fields'][$mkpSpecificFieldName][$selectedProfileId];
            }

            // order of priority: product > profile > marketplace global configuration
            if (isset($mkpSfOverrideByProduct[$currentMkp], $mkpSfOverrideByProduct[$currentMkp][$mkpSpecificFieldName])
                && $mkpSfOverrideByProduct[$currentMkp][$mkpSpecificFieldName]) {
                $result[$mkpSpecificField['api']] = $mkpSfOverrideByProduct[$currentMkp][$mkpSpecificFieldName];
            } elseif (isset($mkpSfOverrideByProfile) && !empty($mkpSfOverrideByProfile)) {
                $result[$mkpSpecificField['api']] = $mkpSfOverrideByProfile;
            } else {
                $result[$mkpSpecificField['api']] = $mkpSpecificField['value'];
            }
        }

        return $result;
    }

    /**
     * calculate product's sale price
     *
     * @param Product $product
     * @param int $id_default_customer_group
     * @param int $id_product_attribute
     * @param float $product_resolved_price
     * @param float $profile_shipping_rule
     * @param array $profile_price_rule
     * @param bool $use_taxes
     * @param bool $use_specials
     * @param bool $smart_rounding
     * @param string $separator
     *
     * @return array
     */
    private function resolveSalePrice(
        $product,
        $id_default_customer_group,
        $id_product_attribute,
        $product_resolved_price,
        $profile_shipping_rule,
        $profile_price_rule,
        $use_taxes,
        $use_specials,
        $smart_rounding,
        $separator
    ) {
        $specificPrice = SpecificPrice::getSpecificPrice(
            $product->id,
            $this->context->shop->id,
            $this->context->currency->id,
            (int)Configuration::get('PS_COUNTRY_DEFAULT'),
            $id_default_customer_group,
            1,
            $id_product_attribute,
            0,
            0,
            1
        );

        if ($this->debug) {
            echo 'Specific Price :'.nl2br(print_r($specificPrice, true)).self::$brlf;
        }

        if (!$specificPrice || !isset($specificPrice['reduction_type'])) {
            return array();
        }

        // Sales
        if (isset($specificPrice['from']) && isset($specificPrice['to']) && (int)$specificPrice['from'] && (int)$specificPrice['to']) {
            // ISO 8601
            // RDC format 'Y-m-d' not 'c'
            $dateStart = date('Y-m-d', strtotime($specificPrice['from']));
            $dateEnd = date('Y-m-d', strtotime($specificPrice['to']));
        } else {
            $dateStart = null;
            $dateEnd = null;
        }

        // Force $id_default_group again to make sure the getPrice() method returns the
        // correct price for the correct customer group
        Context::getContext()->customer->id_default_group = $id_default_customer_group;

        $salePrice = $product->getPrice(
            $use_taxes,
            ($id_product_attribute ? $id_product_attribute : null),
            6,
            null,
            false,
            $use_specials
        );

        if ($profile_price_rule) {
            $salePrice = MiraklTools::PriceRule($salePrice, $profile_price_rule);
            // add shipping increase/decrease from profile after calculating
            $salePrice += $profile_shipping_rule;
        }

        if ($salePrice && $salePrice < $product_resolved_price) {
            if (/*$details->on_sale && */$dateStart && $dateEnd) {
                $sales['dateStart'] = $dateStart;
                $sales['dateEnd'] = $dateEnd;
            }
            if ($smart_rounding) {
                $sales['salePrice'] = MiraklTools::smartRounding($salePrice, $separator);
            } else {
                $sales['salePrice'] = sprintf('%.02f', Tools::ps_round($salePrice, 2));
            }

            if ($this->debug) {
                echo 'Sale Price :'.nl2br(print_r($salePrice, true)).self::$brlf;
                echo 'Price :'.nl2br(print_r($product_resolved_price, true)).self::$brlf;
            }

            return $sales;
        }

        if ($this->debug) {
            echo 'Sale Price :'.nl2br(print_r($salePrice, true)).self::$brlf;
            echo 'Price :'.nl2br(print_r($product_resolved_price, true)).self::$brlf;
        }

        return array();
    }
    
}

$mirakl_offers_updating = new MiraklUpdatingOffers;
$mirakl_offers_updating->dispatch();
