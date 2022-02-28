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

class MiraklEcoTaxManager
{
    protected $rate;

    public function isEnable()
    {
        return $this->getRate() > 0;
    }

    /**
     * @return float|int
     */
    private function getRate()
    {
        if (!isset($this->rate)) {
            $this->rate = (version_compare(_PS_VERSION_, '1.4', '>=')) ?
                Tax::getProductEcotaxRate() : 0;
        }

        return $this->rate;
    }
}

class MiraklExportProducts extends MiraklInventoryExporting
{
    const FEED_VERSION = '1.9';

    public $directory;
    public $export;
    private $errors = array();

    public static $features_values = array();
    public static $features        = array();
    protected static $brlf;

    /** @var Category[] */
    protected $psLoadCategories = array();

    private $html_descriptions = false;
    private $separator         = ';';
    private $debug;
    private $ps_images;
    private $export_url;
    private $zip_url;
    private $pickup_url;
    private $filename;
    
    private $ecoTaxManager;

    const LF = "\n";
    const CRLF = "\r\n";

    public function __construct()
    {
        parent::__construct(true);

        MiraklContext::restore($this->context);

        $this->debug = (bool)Mirakl::getConfig(Mirakl::CONFIG_DEBUG);

        $this->ps_images = MiraklTools::getProtocol().htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'img/p/';

        $token = Mirakl::getConfigGlobalMarketplace(Mirakl::CONFIG_PS_TOKEN, true);

        $this->directory = realpath(basename(__FILE__.'/../'));
        $this->export = $this->directory.DS.'export'.DS.'create';
        $this->export_url = $this->url.'functions/download.php?token='.$token.'&filename=';
        $this->zip_url = $this->url.'';
        $this->pickup_url = 'http://'.MiraklTools::getHttpHost(false, true).$this->url.'export/';

        // Sep-25-2018: Share mirakl_product_option for all marketplaces

        // Force to FRENCH
        $this->id_lang = (int)Language::getIdByIso('fr');

        // If no FRENCH then force to current lang
        if (!$this->id_lang) {
            $this->id_lang = Context::getContext()->language->id;
        }

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

        $this->ecoTaxManager = new MiraklEcoTaxManager();
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

        $this->filename = Tools::strtolower(MiraklTools::getFriendlyUrl(Configuration::get('PS_SHOP_NAME'))).'-create-products.csv';

        //  Check Access Tokens
        $token = Mirakl::getConfigGlobalMarketplace(Mirakl::CONFIG_PS_TOKEN, true);

        if ($metoken != $token) {
            $this->dieOnError($this->l('Wrong Token'));
        }

        self::loadFeatures();
        self::loadAttributes();

        switch ($action) {
            case 'export':
                $this->initRuntimeParameters() && $this->productCreate();
                break;
            case 'last_export':
                $this->lastExport();
                break;
            case 'cron':
                $this->runModeCron = true;
                $this->initRuntimeParameters() && $this->productCreate();
                break;
        }
    }

    private function dieOnError($msg)
    {
        echo $msg;
        $output = ob_get_clean().self::$brlf;
        $json = Tools::jsonEncode(array('error' => true, 'msg' => $output));

        // jQuery Output or PHP Output
        if (($callback = Tools::getValue('callback'))) { // jquery
            echo (string)$callback.'('.$json.')';
        } else { // cron
            return $json;
        }
        die;
    }

    // http://wpscholar.com/blog/filter-multidimensional-array-php/
    public static function arrayFilterRecursive(array $array, callable $callback = null)
    {
        $array = is_callable($callback) ? array_filter($array, $callback) : array_filter($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = call_user_func(array(__CLASS__, __FUNCTION__), $value, $callback);
            }
        }

        return $array;
    }

    private function uploadProducts($file, $products_list, $send_file = false)
    {
        $count = 0;
        require_once(dirname(__FILE__).'/../classes/mirakl.api.products.php');

        $mirakl_params = self::$marketplace_params;
        $mirakl_params['debug'] = $this->debug;
        $mirakl_params['api_key'] = Mirakl::getConfig(Mirakl::CONFIG_API_KEY);

        $products = new MiraklApiProducts($mirakl_params);

        if ($this->debug) {
            echo 'FILE:'.$file.self::$brlf;
            echo nl2br(print_r($products_list, true)).self::$brlf;
        }

        if (!is_array($products_list) || !count($products_list)) {
            $this->errors[] = sprintf('%s - %s'.self::$brlf, $this->l('Empty offer list'));

            return (false);
        }

        if (!($fp = fopen($file, 'w+'))) {
            $this->errors[] = sprintf('%s - %s: %s'.self::$brlf, $this->l('Unable to write in file'), $file);

            return (false);
        }

        //$products_list = self::arrayFilterRecursive($products_list);

        $keys = array();

        foreach ($products_list as $CSV) {
            $keys = array_unique(array_merge($keys, array_map('trim', array_keys($CSV))));
        }

        fputcsv($fp, $keys, $this->separator);

        foreach ($products_list as $CSV) {
            $count++;

            $row = array();

            $CSV = array_combine(array_map('trim', array_keys($CSV)), $CSV);

            foreach ($keys as $key) {
                $row[$key] = array_key_exists($key, $CSV) ? $CSV[$key] : '';
            }

            fputcsv(
                $fp,
                $row,
                $this->separator
            );
        }
        fclose($fp);

        if ($this->debug) {
            echo self::$brlf;
            echo 'Offer Count: '.count($products_list).self::$brlf;
            echo 'Memory: '.number_format(memory_get_usage() / 1024).'k'.self::$brlf;
        }
        if ($send_file) {
            $response = $products->importsUpload($file);

            if ($this->debug) {
                MiraklTools::pre(array(nl2br(htmlspecialchars(print_r($response, true)))));
            }

            return $response;
        } else {
            return ($count);
        }
    }

    private function updateProductsResponse($response)
    {
        if (empty($response)) {
            $this->errors[] = sprintf('%s'.self::$brlf, $this->l('Remote service didnt respond'));

            return (false);
        }
        $xml = simplexml_load_string($response);

        if (!$xml instanceof SimpleXMLElement) {
            $this->errors[] = sprintf('%s: %s'.self::$brlf, $this->l('Remote service return unexpected content'), nl2br(print_r($response, true)));

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

            $this->errors[] = sprintf(MiraklTools::bold('Webservice Error').': %s'.self::$brlf, $error_str);

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

        $files = glob($output_dir.'*.xml');

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


    // Cleanup old files
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


    private function productCreate()
    {
        $error = false;
        $count = $p = 0;
        $history = array();
        $sku_history = array();

        $country_iso_code = Tools::strtolower(Configuration::get('PS_LOCALE_COUNTRY'));
        $id_country = (int)Country::getByIso($country_iso_code);
        $id_default_customer_group = self::getConfig(self::CONFIG_CUSTOMER_GROUP);
        if (!$id_default_customer_group) {
            $id_default_customer_group = Configuration::get('PS_CUSTOMER_GROUP');
        }

        $any_customer_address = Db::getInstance()->getRow('SELECT id_address FROM `'._DB_PREFIX_.'address` WHERE id_country='.(int)$id_country.' AND id_customer > 0');
        $id_address = isset($any_customer_address['id_address']) ? (int)$any_customer_address['id_address'] : null;

        $this->cleanup();

        $urls = array();
        $to_mirakl = array();
        $products_errors = array();
        $duplicateAlongCategories = array();

        $miraklSupport = new MiraklSupport();

        // Force french language for RDC
        $id_lang = Language::getIdByIso('FR');

        // Else use the one defined in the ini file
        if ($this->marketplace_id_lang) {
            $id_lang = $this->marketplace_id_lang;
        }

        $create_in_stock = false;
        $all_products = false;

        // Parameters
        if (Tools::getValue('create-all-products')) {
            $all_products = true;
        }

        if (Tools::getValue('create-in-stock')) {
            $create_in_stock = true;
        }

        if ($this->runModeCron) {
            $create_in_stock = true;

            if (Tools::getValue('force')) {
                $create_in_stock = true;
            }
            $cron_date = Mirakl::getConfig(Mirakl::CONFIG_LAST_CREATE_CRON);

            if (!$cron_date) {
                $date_from = str_replace('/', '-', strstr(MiraklTools::oldest(), ' ', true));
            } else {
                $date_from = $cron_date;
            }
        } else {
            $date_from = null; //TODO: products since last creation date : Tools::getValue('last-create');

            if (!$date_from) {
                $date_from = str_replace('/', '-', strstr(MiraklTools::oldest(), ' ', true));
            }
        }

        if (array_key_exists($id_lang, parent::$features)) {//TODO: Validation, yes it does ;)
            $features = parent::$features[$id_lang];//TODO: Validation, yes it does ;)
            $features_values = parent::$features_values[$id_lang];
        } else {
            $features = array();
            $features_values = array();
        }

        if ($all_products) {
            $date_from = str_replace('/', '-', strstr(MiraklTools::oldest(), ' ', true));
        }

        if ($this->debug) {
            MiraklTools::pre(array(
                sprintf('%s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__),
                "features: ".print_r($features, true),
                "features_values: ".print_r($features_values, true)
            ));
        }

        $marketplace_params = self::$marketplace_params;

        // Categories Settings
        $default_categories = Mirakl::getConfig(Mirakl::CONFIG_CATEGORIES);
        if (!count($default_categories)) {
            $this->errors[] = sprintf('%s(%d): %s', basename(__FILE__), __LINE__, $this->l('You must configure the categories to create')).self::$brlf;
            $error = true;
        }

        $default_profiles = Mirakl::getConfig(Mirakl::CONFIG_PROFILES, true);
        $default_profiles2categories = Mirakl::getConfig(Mirakl::CONFIG_PROFILE_TO_CATEGORY, true);

        // Condition Map: Sep-25-2018: Remove

        $product_name_pre = (int)Mirakl::getConfig(Mirakl::CONFIG_PRODUCT_NAME);
        $product_name_format = ($product_name_pre ? $product_name_pre : self::NAME_NAME_ONLY);

        $decription_field = Mirakl::getConfig(Mirakl::CONFIG_DESCRIPTION_FIELD);
        $decription_field = ($decription_field ? $decription_field : Mirakl::FIELD_DESCRIPTION_LONG);
        $decription_html = $this->html_descriptions = (bool)Mirakl::getConfig(Mirakl::CONFIG_DESCRIPTION_HTML);

        $use_specials = (bool)Mirakl::getConfig(Mirakl::CONFIG_USE_SPECIALS);

        $no_image = Mirakl::getConfig(Mirakl::CONFIG_NO_IMAGE);

        if ($no_image === false) {
            $no_image = true;
        }

        // Exclusions
        $excluded_manufacturers = Mirakl::getConfig(Mirakl::CONFIG_FILTER_MFR, true);
        $excluded_suppliers = Mirakl::getConfig(Mirakl::CONFIG_FILTER_SUPPLIERS, true);

        $use_taxes = (bool)Mirakl::getConfig(Mirakl::CONFIG_USE_TAXES);
        $smart_rounding = (bool)Mirakl::getConfig(Mirakl::CONFIG_SMART_ROUNDING);

        if (is_array($marketplace_params) && array_key_exists('separator', $marketplace_params)
            && Tools::strlen($marketplace_params['separator'])) {
            $separator = $marketplace_params['separator'];
        } else {
            $separator = '.';
        }

        // Path to XML
        $output_dir = $this->export;

        // Files
        $products_file = $output_dir.DS.$this->filename;

        // Download URLs
        $urls[$this->filename] = sprintf('%s%s', $this->export_url, 'create/'.$this->filename);

        // Check rights
        if (!is_dir($output_dir) && !mkdir($output_dir)) {
            $this->errors[] = sprintf('%s(%s): %s(%s)', basename(__FILE__), __LINE__, $this->l('Unable to create the directory'), $output_dir).self::$brlf;
            $error = true;
        }

        if (!is_writable($output_dir)) {
            chmod($output_dir, 0775);
        }

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $default_category = 1;
        } else {
            $default_category = $this->context->shop->id_category;
        }

        $from_currency = new Currency((int)(Configuration::get('PS_CURRENCY_DEFAULT')));
        $to_currency = $from_currency;

        if (array_key_exists('currency', $marketplace_params) && Tools::strlen($marketplace_params['currency'])) {
            $to_currency = new Currency(Currency::getIdByIsoCode($marketplace_params['currency']));

            if (!Validate::isLoadedObject($to_currency)) {
                $this->errors[] = sprintf('%s(%d): %s(%s)', basename(__FILE__), __LINE__, $this->l('Unable to load currency'), $marketplace_params['currency']);
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

        // Export Loop
        if (!$error && $default_categories) {
            foreach ($default_categories as $id_category) {
                if ($id_category == $default_category) {
                    continue;
                }

                /* We do not select categories anymore, all config is used instead
                if (!in_array($id_category, $selected_categories)) {
                    continue;
                }
                */

                $profile = isset($default_profiles2categories[$id_category]) ? $default_profiles2categories[$id_category] : null;

                if (!$profile) {
                    $this->errors[] = $this->pdl(
                        $this->l('You must sets a profile for this category'),
                        $this->getPsCategory($id_category, $this->marketplace_id_lang)->name
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
                    $this->errors[] = sprintf('%s(%d): %s', basename(__FILE__), __LINE__, $this->l('You must configure your profiles first'));
                    $error = true;
                    continue;
                }
                $profile_attr = isset($default_profiles['combinations_attr'][$selected_profile]) ? $default_profiles['combinations_attr'][$selected_profile] : '';
                $warranty = isset($default_profiles['warranty'][$selected_profile]) ? $default_profiles['warranty'][$selected_profile] : '';
                $logistic_class = isset($default_profiles['logistic_class'][$selected_profile]) ? $default_profiles['logistic_class'][$selected_profile] : '';
                $profile_price_rule = isset($default_profiles['price_rule'][$selected_profile]) ? $default_profiles['price_rule'][$selected_profile] : false;
                $profile_shipping_rule = isset($default_profiles['shipping_rule'][$selected_profile]) ? (float)$default_profiles['shipping_rule'][$selected_profile] : 0;

                $products = MiraklProduct::getExportProducts($id_category, $this->onlyActiveOne, $create_in_stock, $date_from, null, $this->debug);

                if ($products) {
                    foreach ($products as $product) {
                        $id_product = $product['id_product'];

                        // Products with multiples categories ;
                        if (isset($duplicateAlongCategories[$id_product])) {
                            continue;
                        }
                        $duplicateAlongCategories[$id_product] = true;

                        $details = new Product($id_product, false, $this->marketplace_id_lang);

                        if (!Validate::isLoadedObject($details)) {
                            $this->errors[] = sprintf($this->l('Could not load the product id: %d'), $id_product);
                            $error = true;
                            continue;
                        }
                        // Filtering Manufacturer & Supplier
                        if ($details->id_manufacturer) {
                            if (is_array($excluded_manufacturers) && in_array($details->id_manufacturer, $excluded_manufacturers)) {
                                continue;
                            }
                        }

                        if ($details->id_supplier) {
                            if (is_array($excluded_suppliers) && in_array($details->id_supplier, $excluded_suppliers)) {
                                continue;
                            }
                        }

                        // RDC only sells NEW products, skip the used one
                        if (Tools::strtolower($details->condition) != 'new') {
                            if ($this->debug) {
                                $this->errors[] = sprintf($this->l('Product ID #%d is not NEW.'), $id_product);
                            }
                            continue;
                        }

                        $manufacturer = Manufacturer::getNameById((int)$details->id_manufacturer);

                        // Product Options
                        $options = MiraklProduct::getProductOptions($id_product, $id_lang);

                        $disabled = $options['disable'] ? true : false;
                        $force = $options['force'] ? true : false;
                        
                        if ($disabled) {
                            continue;
                        }

                        if (!empty($options['price']) && is_numeric((float)$options['price'])) {
                            $price_override = (float)$options['price'];
                        } else {
                            $price_override = false;
                        }

                        if (!$details->active && $this->onlyActiveOne) {
                            continue;
                        }

                        if ($force) {
                            $details->quantity = 999;
                        }

                        // Product Combinations
                        if (version_compare(_PS_VERSION_, '1.5', '<')) {
                            $combinations = $details->getAttributeCombinaisons($this->marketplace_id_lang);
                        } else {
                            $combinations = $details->getAttributeCombinations($this->marketplace_id_lang);
                        }

                        $product_features = $details->getFeatures();

                        // Pas de combinaison, on en cr?e une fictive pour rentrer dans la boucle
                        if (!is_array($combinations) || empty($combinations)) {
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
                                    'default_on' => 0,
                                )
                            );
                        }
                        // Grouping Combinations
                        asort($combinations);

                        $group_details = array();
                        foreach ($combinations as $combination) {
                            $id_product_attribute = isset($combination['id_product_attribute']) ? $combination['id_product_attribute'] : 0;
                            $id_attribute_group = isset($combination['id_attribute_group']) ? $combination['id_attribute_group'] : 0;

                            $group_details[$id_product_attribute][$id_attribute_group] = array(
                                'reference' => $combination['reference'],
                                'weight' => $combination['weight'],
                                'unity' => isset($combination['unity']) ? $combination['unity'] : $details->unity,
                                'id_attribute_group' => $combination['id_attribute_group'],
                                'id_attribute' => $combination['id_attribute'],
                                'ean13' => $combination['ean13'],   // Synch Field (EAN, UPC, SKU ...)
                                'attribute_name' => isset($combination['attribute_name']) ? $combination['attribute_name']: '',
                                'group_name' => isset($combination['group_name']) ? $combination['group_name'] : '',
                            );
                        }

                        $idx = 0;
                        // Export Combinations or Products Alone
                        foreach ($group_details as $id_product_attribute => $combination) {
                            $idx++;

                            $ean13 = '';
                            $reference = '';
                            $attributes_desc = null;
                            $weight = $details->weight;
                            $unity = null;

                            // Attributes
                            foreach ($combination as $group_detail) {
                                if ($group_detail['attribute_name']) {
                                    if ($profile_attr != Mirakl::ATTRIBUTES_NO) {
                                        if ($profile_attr == Mirakl::ATTRIBUTES_LONG) {
                                            $attributes_desc .= sprintf('%s: %s - ', $group_detail['group_name'], $group_detail['attribute_name']);
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

                            // Features
                            $combination_features = array();
                            foreach ($product_features as $feature) {
                                $id_feature = (int)$feature['id_feature'];
                                $id_feature_value = (int)$feature['id_feature_value'];

                                // Is a custom value, load custom value
                                if ((bool)$feature['custom'] && !isset($features_values[$id_feature][$id_feature_value])) {
                                    $custom_features_values = FeatureValue::getFeatureValuesWithLang($id_lang, $id_feature, true);

                                    foreach ($custom_features_values as $custom_feature_value) {
                                        if ($custom_feature_value['id_feature_value'] != $id_feature_value) {
                                            continue;
                                        }

                                        if (!isset($features_values[$id_feature][$id_feature_value]['name'])) {
                                            $featurex = new Feature($id_feature, $this->marketplace_id_lang);
                                            $features_values[$id_feature][$id_feature_value]['name'] =  $custom_feature_value['name'] = $featurex->name;
                                        }
                                        $features_values[$id_feature][$id_feature_value] = $custom_feature_value;
                                        break;
                                    }
                                }

                                if (array_key_exists($id_feature, $features_values) && array_key_exists($id_feature_value, $features_values[$id_feature])) {
                                    $combination_features[$id_feature] = &$features_values[$id_feature][$id_feature_value];
                                }
                            }

                            if ($this->debug) {
                                MiraklTools::pre(array(
                                    sprintf('%s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__),
                                    "product features: ".print_r($product_features, true),
                                    "combination features: ".print_r($combination_features, true)
                                ));
                            }

                            // Product Code Check
                            if (!$this->externalMkp->isEanExemption()) {
                                if (!(int)$ean13) {
                                    $products_errors['missing_ean'][] = array(
                                        'reference' => $reference,
                                        'id_product' => $id_product,
                                        'id_product_attribute' => $id_product_attribute
                                    );
                                    continue;
                                }
                                if (!MiraklTools::eanupcCheck($ean13)) {
                                    $products_errors['wrong_ean'][$ean13] = array(
                                        'ean13' => $ean13,
                                        'id_product' => $id_product,
                                        'id_product_attribute' => $id_product_attribute
                                    );
                                    continue;
                                }
                            }
                            if ($ean13 && isset($history[$ean13])) {
                                $products_errors['duplicate_ean'][$ean13] = array(
                                    'reference' => $reference,
                                    'ean13' => $ean13,
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute
                                );
                                continue;
                            }
                            $history[$ean13] = true;

                            if ($reference && isset($sku_history[$reference])) {
                                $products_errors['duplicate_reference'][] = array(
                                    'reference' => $reference,
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute
                                );
                                continue;
                            }
                            $sku_history[$reference] = true;

                            //
                            // Quantity
                            //
                            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                                $quantity = Product::getRealQuantity($details->id, $id_product_attribute ? $id_product_attribute : null);
                            } else {
                                $quantity = (int)MiraklProduct::getProductQuantity($id_product, $id_product_attribute);
                            }

                            // Price
                            $priceResolver = $this->resolvePrice($options, $profile_price_rule, $profile_shipping_rule, $details, $id_product_attribute);
                            $price = $priceResolver['tax_incl'];
                            $price_tax_excl = $priceResolver['tax_excl'];

                            if (!$ean13) {
                                $ean13 = $details->ean13;
                            }
                            if (!$reference) {
                                $reference = $details->reference;
                            }
                            if ($disabled) {
                                $quantity = 0;
                            }

                            if (!$quantity && $create_in_stock) {
                                if ($this->debug) {
                                    $this->errors[] = sprintf($this->l('Skipping out of stock product: %d'), $id_product).self::$brlf;
                                }
                                continue;
                            }

                            $sales = array();

                            // Apply Sales for PS > 1.4
                            //
                            if (!$price_override && version_compare(_PS_VERSION_, '1.4', '>=') && $use_specials) {
                                $sales = $this->resolveSalePrice(
                                    $details,
                                    $id_default_customer_group,
                                    $id_product_attribute,
                                    $price,
                                    $profile_shipping_rule,
                                    $profile_price_rule,
                                    $use_taxes,
                                    $use_specials
                                );
                            }

                            if (empty($reference)) {
                                $products_errors['empty_reference'][] = array(
                                    'ean13' => $ean13,
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute
                                );
                                continue;
                            }


                            //
                            // Ecotax
                            //
                            $ecotax = $this->ecoTaxManager->isEnable() ? $details->ecotax : 0;

                            if (is_array($details->name) && count($details->name)) {
                                $details->name = $details->name[$this->marketplace_id_lang];
                            }

                            // Product Name
                            switch ($product_name_format) {
                                case self::NAME_NAME_ATTRIBUTES:
                                    if ($attributes_desc) {
                                        $name = rtrim(sprintf('%s (%s)', $details->name, rtrim($attributes_desc, ' - ')), ' - ');
                                        $name = trim(rtrim($name, '-'));
                                        $product_name = $name;
                                    } else {
                                        $product_name = $details->name;
                                    }
                                    break;
                                case self::NAME_BRAND_NAME_ATTRIBUTES:
                                    if ($attributes_desc) {
                                        $name = rtrim(sprintf('%s - %s (%s)', $manufacturer, $details->name, rtrim($attributes_desc, ' - ')), ' - ');
                                        $name = trim(rtrim($name, '-'));
                                        $product_name = $name;
                                    } else {
                                        $product_name = sprintf('%s - %s', $manufacturer, $details->name);
                                    }
                                    break;
                                case self::NAME_NAME_BRAND_ATTRIBUTES:
                                    if ($attributes_desc) {
                                        $name = rtrim(sprintf('%s - %s - (%s)', $details->name, $manufacturer, rtrim($attributes_desc, ' - ')), ' - ');
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


                            if (array_key_exists('title_max_length', $marketplace_params) && (int)$marketplace_params['title_max_length'] && Tools::strlen($product_name) > (int)$marketplace_params['title_max_length']) {
                                $var = explode('|', wordwrap($product_name, $marketplace_params['title_max_length'], '|'));
                                $truncated_name = sprintf('%s', $var[0]);
                                $product_name = $truncated_name;
                            }

                            $description = null;
                            $description_long = MiraklTools::formatDescription($details->description, $decription_html);
                            $description_short = MiraklTools::formatDescription($details->description_short, $decription_html);

                            switch ($decription_field) {
                                case Mirakl::FIELD_DESCRIPTION_LONG:
                                    $description = $description_long;
                                    break;
                                case Mirakl::FIELD_DESCRIPTION_SHORT:
                                    $description = $description_short;
                                    break;
                                case Mirakl::FIELD_DESCRIPTION_BOTH:
                                    $description = $description_long;
                                    $description .= $decription_html ? nl2br("\n") : "\n";
                                    $description .= $description_short;
                                    break;
                            }

                            if (array_key_exists('description_required', $marketplace_params) && (bool)$marketplace_params['description_required'] && !Tools::strlen($description)) {
                                $products_errors['description_required'][] = array(
                                    'reference' => $reference,
                                    'ean13' => $ean13,
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute
                                );
                                continue;
                            }

                            if (array_key_exists('description_length', $marketplace_params) && (int)$marketplace_params['description_length'] && Tools::strlen($description) > (int)$marketplace_params['description_length']) {
                                $var = explode('|', wordwrap($description, $marketplace_params['description_length'], '|'));
                                $truncated_description = sprintf('%s...', $var[0]);

                                if (Tools::strlen($truncated_description) >= $marketplace_params['description_length']) {
                                    $truncated_description = Tools::substr($truncated_description, 0, $marketplace_params['description_length']);
                                }
                                $description = $truncated_description;
                            }

                            if (array_key_exists('short_description_length', $marketplace_params) && (int)$marketplace_params['short_description_length']
                                && Tools::strlen($description_short) > (int)$marketplace_params['short_description_length']) {
                                $var = explode('|', wordwrap($description_short, $marketplace_params['short_description_length'], '|'));
                                $truncated_description = sprintf('%s...', $var[0]);

                                if (Tools::strlen($truncated_description) >= $marketplace_params['short_description_length']) {
                                    $truncated_description = Tools::substr($truncated_description, 0, $marketplace_params['short_description_length']);
                                }
                                $description_short = $truncated_description;
                            }

                            //
                            // Images
                            //
                            $images = array_fill_keys(array(0, 1, 2, 3, 4), null);

                            foreach (MiraklTools::getProductImages($details->id, $id_product_attribute, $id_lang) as $index => $image) {
                                $images[$index] = $this->ps_images.$image;
                            }

                            list($image1, $image2, $image3, $image4, $image5) = $images;

                            if (empty($image1)&& $no_image) {
                                $this->errors[] = sprintf($this->l('Missing Image for Product %s(%d/%d)').self::$brlf, $reference, $id_product, $id_product_attribute);
                                unset($to_mirakl[$p]);
                                continue;
                            }

                            if ($images) {
                                if ($this->debug) {
                                    MiraklTools::pre(array(
                                        sprintf('%s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__),
                                        sprintf('Products Images: %s'.self::$brlf, print_r($images, true))
                                    ));
                                }
                            }

                            //
                            // Manufacturer References
                            //
                            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                                $supplier_reference = ProductSupplier::getProductSupplierReference($id_product, $id_product_attribute, $details->id_supplier);
                            } else {
                                $supplier_reference = $details->supplier_reference;
                            }

                            if (Tools::strlen($details->reference)) {
                                $to_mirakl[$p]['parent-sku'] = $details->reference;
                            }

                            $productIdResolver = $this->resolveProductId($reference, $ean13);
                            $to_mirakl[$p]['sku'] = $reference;
                            $to_mirakl[$p]['product-id-type'] = $productIdResolver['type'];
                            $to_mirakl[$p]['product-id'] = $productIdResolver['product_id'];

                            $category_separator = $this->externalMkp->categorySeparator
                                ? $this->externalMkp->categorySeparator : ' | ';

                            // Category
                            //
                            $id_category_default = $details->id_category_default;

                            $to_mirakl[$p]['Product title'] = $product_name;

                            $to_mirakl[$p]['Category'] = $this->cPath($id_category_default, 1, $category_separator);
                            $to_mirakl[$p] = array_merge($to_mirakl[$p], $this->buildCategoryLevels($id_category_default, $id_lang, $category_separator));

                            $split_descriptions = false;

                            if (is_array($marketplace_params) && array_key_exists('options', $marketplace_params) && count($marketplace_params['options'])) {
                                foreach (array_keys($marketplace_params['options']) as $option_field) {
                                    switch ($option_field) {
                                        case 'split-descriptions':
                                            $split_descriptions = true;
                                            break;
                                    }
                                }
                            }

                            if ($split_descriptions) {
                                $to_mirakl[$p]['description'] = $description;
                                $to_mirakl[$p]['description-short'] = $description;
                            } else {
                                $to_mirakl[$p]['description'] = $description;
                            }

                            if (MiraklMarketplace::getCurrentMarketplace() == 'rueducommerce') {
                                $to_mirakl[$p]['description'] = Tools::substr($to_mirakl[$p]['description'], 0, 2000);
                            }

                            // Description short deja assigné plus haut ?
                            $to_mirakl[$p]['description-short'] = $description_short;

                            $to_mirakl[$p]['Picture'] = $image1;

                            if (Tools::strlen($image2)) {
                                $to_mirakl[$p]['Add.Picture-1'] = $image2;
                            }

                            if (Tools::strlen($image3)) {
                                $to_mirakl[$p]['Add.Picture-2'] = $image3;
                            }

                            if (Tools::strlen($image4)) {
                                $to_mirakl[$p]['Add.Picture-3'] = $image4;
                            }

                            if (Tools::strlen($image5)) {
                                $to_mirakl[$p]['Add.Picture-4'] = $image5;
                            }

                            $to_mirakl[$p]['Brand'] = $manufacturer;
                            $to_mirakl[$p]['Supplier Reference'] = $supplier_reference;

                            foreach ($combination as $group_detail) {
                                if (array_key_exists('attribute_name', $group_detail) && !empty($group_detail['attribute_name'])) {
                                    $to_mirakl[$p][$group_detail['group_name']] = $group_detail['attribute_name'];
                                }
                            }

                            if (is_array($combination_features) && count($combination_features)) {
                                foreach ($combination_features as $feature) {
                                    $feature_name = $feature['name'];
                                    $feature_value = $feature['value'];

                                    if (Tools::strlen($feature_value)) {
                                        $to_mirakl[$p][$feature_name] = $feature_value;
                                    }
                                }
                            }

                            $to_mirakl[$p]['internal-description'] = sprintf('Product Creation Attempt for %s, %s', $product_name, date('Y-m-d H:i:s'));

                            $to_mirakl[$p]['price-additional-info'] = $ecotax ? sprintf('%s %.02f', $this->l('Ecotaxe'), round((float)$ecotax, 2)) : '';

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
                            $to_mirakl[$p]['warranty'] = $warranty;
                            $to_mirakl[$p]['favorite-rank'] = '';
                            $to_mirakl[$p]['weight'] = sprintf('%.02f', $weight);

                            if (in_array(MiraklMarketplace::getCurrentMarketplace(), array('eprice'))) {
                                $to_mirakl[$p]['weight'] = max(
                                    1,
                                    floor($to_mirakl[$p]['weight'])
                                );
                            }

                            if ($smart_rounding) {
                                $to_mirakl[$p]['price'] = MiraklTools::smartRounding($price, $separator);
                            } else {
                                $to_mirakl[$p]['price'] = number_format(Tools::ps_round($price, 2), 2, $separator, '');
                            }

                            $to_mirakl[$p]['quantity'] = $quantity;

                            $to_mirakl[$p]['price-tax-excl'] = $price_tax_excl;
                            $to_mirakl[$p]['eco'] = $ecotax ? '1' : '0';
                            $to_mirakl[$p]['memtax'] = (float)$details->tax_rate ? '1' : '0';

                            // Optionnal/Marketplace custom fields ;
                            if (is_array($marketplace_params) && array_key_exists('fields', $marketplace_params)) {
                                foreach ($marketplace_params['fields'] as $field) {
                                    switch ($field['prestashop']) {
                                        case 'vat':
                                            $product_tax_rate = null;

                                            if (method_exists('Tax', 'getProductTaxRate') && $id_address) {
                                                $product_tax_rate = (float)(Tax::getProductTaxRate($details->id, $id_address));
                                            } elseif ($id_address) {
                                                $product_tax_rate = (float)(Tax::getApplicableTax($details->id_tax, $details->tax_rate, $id_address));
                                            }
                                            if ($product_tax_rate) {
                                                $to_mirakl[$p][$field['mirakl']] = $product_tax_rate;
                                            } else {
                                                $to_mirakl[$p][$field['mirakl']] = $field['default'];
                                            }

                                            break;

                                        case 'ecotax':
                                            $to_mirakl[$p][$field['mirakl']] = $this->ecoTaxManager->isEnable() ?
                                                $details->ecotax : $field['default'];
                                            break;

                                        case 'on_sale':
                                            $to_mirakl[$p][$field['mirakl']] = $details->on_sale;
                                            break;

                                        case 'striked_price':
                                            $to_mirakl[$p][$field['mirakl']] = number_format(Tools::ps_round($details->getPrice($use_taxes, $id_product_attribute, 2, null, false, false), 2), 2, $separator, '');
                                            break;

                                        case 'discount_price':
                                            if (array_key_exists('salePrice', $sales) && (float)$sales['salePrice']) {
                                                $to_mirakl[$p][$field['mirakl']] = number_format(Tools::ps_round($sales['salePrice'], 2), 2, $separator, '');
                                                $to_mirakl[$p]['price'] =  number_format(Tools::ps_round($price, 2), 2, $separator, '');
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
                                            $delivery_time = Mirakl::getConfig(Mirakl::CONFIG_DELIVERY_TIME);

                                            if (is_numeric($delivery_time)) {
                                                $to_mirakl[$p][$field['mirakl']] = $delivery_time;
                                            } else {
                                                $to_mirakl[$p][$field['mirakl']] = $field['default'];
                                            }
                                            break;

                                        case 'short_description':
                                            $to_mirakl[$p][$field['mirakl']] = $description = Tools::substr(
                                                MiraklTools::formatDescription($details->description_short, $decription_html),
                                                0,
                                                255
                                            );
                                            break;

                                        // referenciagenericaeci of El Corte Ingles is handled as specific field

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
                                    $required = $additionnal['required'];

                                    if (isset($default_profiles[$field_name][$selected_profile])) {
                                        $selected_field = explode('-', $default_profiles[$field_name][$selected_profile]);
                                        $field_type = isset($selected_field[0]) ? $selected_field[0] : null;
                                        $field_id = isset($selected_field[1]) ? $selected_field[1] : null;

                                        if (($field_type == null || $field_id == null) && !Tools::strlen($default) && $required) {
                                            $products_errors['missing_field'][$field_name] = array(
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

                                                        $features_values = FeatureValue::getFeatureValuesWithLang($this->marketplace_id_lang, $id_feature, $feature['custom']);

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
                                                        $value = $this->getPsCategory($id_category, $this->marketplace_id_lang)->name;
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
                                                        $value = sprintf('EAN|%s', $ean13);
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
                                            $this->errors[] = sprintf($this->l('Missing mandatory field').': %s', $field_name);
                                            $error = true;
                                            continue;
                                        }
                                    }
                                }
                            }

                            if (!array_key_exists($p, $to_mirakl)) {
                                continue;
                            }

                            if (is_array($marketplace_params) && array_key_exists('exclude', $marketplace_params) && count($marketplace_params['exclude'])) {
                                foreach (array_keys($marketplace_params['exclude']) as $exclude_field) {
                                    if (array_key_exists($exclude_field, $to_mirakl[$p])) {
                                        unset($to_mirakl[$p][$exclude_field]);
                                    }
                                }
                            }

                            $count++;

                            if ($this->debug) {
                                if ($this->debug) {
                                    MiraklTools::pre(array(
                                        sprintf('%s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__),
                                        sprintf('to mirakl: %s'.self::$brlf, print_r($to_mirakl[$p], true))
                                    ));
                                }
                            }
                            $p++;
                        } // end foreach combinations
                    }
                } // end foreach products
            } // end foreach categories
        } // end if

        if ($this->debug) {
            echo self::$brlf;
            echo 'Memory: '.number_format(memory_get_usage() / 1024).'k'.self::$brlf;
        }

        if (is_array($products_errors)) {
            foreach (array(
                         'empty_reference',
                         'duplicate_ean',
                         'duplicate_reference',
                         'wrong_ean',
                         'missing_ean',
                         'description_required',
                         'missing_field'
                     ) as $value => $error_type) {
                if (isset($products_errors[$error_type]) && is_array($products_errors[$error_type]) && count($products_errors[$error_type])) {
                    $msg = null;
                    foreach ($products_errors[$error_type] as $product_error) {
                        switch ($error_type) {
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

                            case 'missing_ean_matching':
                                if ($msg == null) {
                                    $msg = $this->l('Products without EAN ignored in matching mode, References').': [';
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

                            case 'description_required':
                                if ($msg == null) {
                                    $msg = $this->l('Description is required, References').': [';
                                }

                                $msg .= sprintf('%s, ', $product_error['reference'] ? $product_error['reference'] : '#'.$product_error['id_product']);
                                break;

                            case 'missing_field':
                                if ($msg == null) {
                                    $msg = $this->l('Missing mandatory profile field').': ['.$value;
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
                                $msg = $miraklSupport->message($msg, MiraklSupport::FUNCTION_EXPORT_MISSING_EAN_MATCHING);
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

        $send_products = (bool)Tools::getValue('send-products');

        $msg ='';
        if (isset($to_mirakl) && is_array($to_mirakl) && $count = count($to_mirakl)) {
            if (!($response = $this->uploadProducts($products_file, $to_mirakl, $send_products))) {
                $this->errors[] = sprintf('uploadProducts() failed...').self::$brlf;
                $error = true;
            }

            if (!$send_products && is_numeric($response)) {
                $count = $response;
            } else {
                $count = count($to_mirakl);
            }

            if ($send_products && $this->updateProductsResponse($response)) {
                $msg .= sprintf($this->l('%d products sent... %s').self::$brlf, $count, null);
                $error = true;
            } elseif ($count) {
                $msg .= sprintf($this->l('%d products created... %s').self::$brlf, $count, null);
            } else {
                $this->errors[] = sprintf($this->l('Product creation failed.').self::$brlf, $count);
                $error = true;
            }
        } else {
            $this->errors[] = $this->l('No Products, nothing to do...').self::$brlf;
            $error = true;
        }


        // Export Output
        $output = ob_get_clean();

        if (!$error && $count) {
            $msg .= $this->l('Products Successfully Exported').self::$brlf;
        } elseif ($error) {
            $msg .= $this->l('An error occured while exporting the products').self::$brlf;
            $error = true;
        }

        if ($count && !$this->runModeCron) {
            Mirakl::updateConfig(Mirakl::CONFIG_LAST_CREATE, date('Y-m-d H:i:s'));
            Mirakl::updateConfig(Mirakl::CONFIG_LAST_CREATE_URL, $urls, true);
        } elseif ($this->runModeCron) {
            Mirakl::updateConfig(Mirakl::CONFIG_LAST_CREATE_CRON, date('Y-m-d H:i:s'));
        } else {
            $error = true;
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

    private function resolveProductId($reference, $ean13)
    {
        $type = $this->externalMkp->ean_field_name;
        switch ($type) {
            case MiraklMarketplace::PRODUCT_ID_TYPE_SKU:
                $productId = $reference;
                break;
            default:
                $productId = sprintf('%s%013s', $this->externalMkp->productIDPrefix, trim($ean13));
                break;
        }

        return array('type' => $type, 'product_id' => $productId);
    }

    private function lastExport()
    {
        $callback = Tools::getValue('callback');

        $date = Mirakl::getConfig(Mirakl::CONFIG_LAST_CREATE);
        $urls = Mirakl::getConfig(Mirakl::CONFIG_LAST_CREATE_URL, true);

        if (!$date || !$urls) { //echo (string) $callback . '()';
            echo '';
            exit(0);
        }
        $json = Tools::jsonEncode(
            array(
                'msg' => $this->l('Last Creation').': '.$date,
                'urls' => $urls,
                'button' => null
            )
        );

        echo (string)$callback.'('.$json.')';
    }

    protected function buildCategoryLevels($idCategory, $idLang, $category_separator)
    {
        $result = array();
        $categories_list = $this->cPath($idCategory, $idLang, $category_separator);
        $categories_array = explode($category_separator, $categories_list);
        if (count($categories_array)) {
            $leafCategory = end($categories_array);
            for ($i = 0; $i < 6; $i++) {
                $label = sprintf('Category L%s', $i + 1);
                $result[$label] = isset($categories_array[$i]) ? $categories_array[$i] : $leafCategory;
            }
        }

        return $result;
    }

    /**
     * @param $idCategory
     * @param null $idLang
     * @return Category
     */
    protected function getPsCategory($idCategory, $idLang = null)
    {
        $categoryKey = sprintf('%d-%d', $idCategory, $idLang);
        if (!isset($this->psLoadCategories[$categoryKey])) {
            $this->psLoadCategories[$categoryKey] = new Category($idCategory, $idLang);
        }

        return $this->psLoadCategories[$categoryKey];
    }

    /**
     * @param $id_category
     * @param $id_lang
     * @param string $separator
     * @param string $category
     * @return string GrandCat | ParentCat | Cat
     */
    protected function cPath($id_category, $id_lang, $separator = ' | ', $category = '')
    {
        $c = $this->getPsCategory($id_category, $id_lang);
        if ($c->id_parent && $c->id_parent != 1) {
            $category .= $this->cPath($c->id_parent, $id_lang, $separator) . $separator;
        }
        $category .= $c->name;

        return rtrim($category, $separator);
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
        $use_specials
    ) {
        $specificPrice = SpecificPrice::getSpecificPrice($product->id,
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

        // Sales
        //
        if (!$product->on_sale 
            || !$specificPrice 
            || !isset($specificPrice['reduction_type']) 
            || !isset($specificPrice['from']) 
            || !isset($specificPrice['to']) 
            || !(int)$specificPrice['from'] 
            || !(int)$specificPrice['to']
        ) {
            return array();
        }

        // ISO 8601
        // RDC format 'Y-m-d' not 'c'
        $dateStart = date('Y-m-d', strtotime($specificPrice['from']));
        $dateEnd = date('Y-m-d', strtotime($specificPrice['to']));

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
            $sales['dateStart'] = $dateStart;
            $sales['dateEnd'] = $dateEnd;
            $sales['salePrice'] = sprintf('%.02f', Tools::ps_round($salePrice, 2));

            return $sales;
        }

        return array();
    }
    

    private function pdl()
    {
        $backTrace = debug_backtrace();
        $caller = array_shift($backTrace);
        $fileSegment = explode(DIRECTORY_SEPARATOR, $caller['file']);
        $file = array_pop($fileSegment);
        $callerStackStr = sprintf('%s(#%d)', $file, $caller['line']);

        return sprintf('%s: %s', $callerStackStr, implode(' - ', func_get_args()));
    }
}

$miraklProductsCreate = new MiraklExportProducts;
$miraklProductsCreate->dispatch();
