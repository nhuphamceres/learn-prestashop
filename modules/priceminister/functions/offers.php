<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
 * Contact by Email :  support.priceminister@common-services.com
 */

$file = new SplFileInfo($_SERVER['SCRIPT_FILENAME']);

require_once dirname(dirname(dirname($file->getPath()))).'/config/config.inc.php';
require_once dirname(dirname(dirname($file->getPath()))).'/init.php';

require_once(dirname(__FILE__).'/../priceminister.php');

require_once(dirname(__FILE__).'/../classes/priceminister.api.webservices.php');

require_once(dirname(__FILE__).'/../classes/priceminister.tools.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.product.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.repricing.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.context.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.batch.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.support.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.form.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.api.webservices.php');
require_once(dirname(__FILE__).'/../classes/priceminister.api.products.class.php');

//ini_set('max_execution_time', 900);

class PriceMinisterOffers extends PriceMinister
{

    public static $errors = array();
    public static $warnings = array();
    public static $messages = array();
    public static $offers = array();
    public static $id_warehouse = 0;
    public $export;
    public $test_mode = false;
    protected $batch_timestart = 0;
    protected $max_execution_time = 0;
    protected $memory_limit = 0;
    protected $php_limits = 0;
    protected $start_time = 0;

    public function __construct($start_time)
    {
        parent::__construct();

        PriceMinisterContext::restore($this->context);

        // Set the correct shop context in the global context
        // Usefull for function to get image or stock for exemple
        if ($this->context->shop && Validate::isLoadedObject($this->context->shop)) {
            Context::getContext()->shop = $this->context->shop;

            $country = new Country((int)Configuration::get('PS_COUNTRY_DEFAULT'));
            if (Validate::isLoadedObject($country)) {
                $this->context->country = $country;
                Context::getContext()->country = $country;
            }
        }

        parent::loadGeneralModuleConfig();

        $this->batch_timestart = time();

        $this->max_execution_time = (int)ini_get('max_execution_time');
        $this->memory_limit = ini_get('memory_limit');
        $this->php_limits = $this->max_execution_time || $this->memory_limit ? true : false;
        $this->start_time = $start_time;

        $this->credentials = unserialize(Configuration::get(PriceMinister::CONFIG_PM_CREDENTIALS));

        if (Tools::getValue('debug')) {
            $this->debug = true;
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }

        $this->id_lang = Language::getIdByIso('FR');
    }

    public static function JSON_Display_Exit()
    {
        $result = trim(ob_get_clean());

        if (!empty($result)) {
            PriceMinisterOffers::$warnings[] = trim($result);
        }

        $json = Tools::jsonEncode(
            array(
                'count' => count(PriceMinisterOffers::$offers),
                'offers' => PriceMinisterOffers::$offers,
                'error' => (count(PriceMinisterOffers::$errors) ? true : false),
                'errors' => PriceMinisterOffers::$errors,
                'warning' => (count(PriceMinisterOffers::$warnings) ? true : false),
                'warnings' => PriceMinisterOffers::$warnings,
                'message' => count(PriceMinisterOffers::$messages),
                'messages' => PriceMinisterOffers::$messages
            )
        );

        if (($callback = Tools::getValue('callback'))) {
            echo $callback.'('.$json.')';
        } else {
            echo "<pre>\n";
            echo PriceMinisterTools::jsonPrettyPrint($json);
            echo "<pre>\n";
        }
    }

    public function Dispatch()
    {
        ob_start();
        register_shutdown_function(array('PriceMinisterOffers', 'JSON_Display_Exit'));

        //  Check Access Tokens
        //
        $pm_token = Configuration::get(PriceMinister::CONFIG_PM_CRON_TOKEN);

        if ($pm_token != Tools::getValue('pm_token')) {
            self::$errors[] = $this->l('Wrong Token');
            die;
        }

        $this->export = $this->path.'export/';

        switch ($action = Tools::getValue('action')) {
            case 'check':
            case 'export':
                $this->OffersList(false, $action, Tools::getValue('matching', 0));
                break;
            case 'cron':
                $this->OffersList(true, $action, Tools::getValue('matching', 0));
                break;
            default:
                self::$errors[] = 'Missing Parameter';
                die;
        }
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    public function OffersList($cron = false, $action = 'export', $matching_mode = false)
    {
        $count_products = 0;
        $count_combinations = 0;

        $i = 0;

        $loop_start_time = microtime(true);

        $private_comment = sprintf('%s %s', $this->l('Automatic update from'), PriceMinisterTools::getFriendlyUrl(Configuration::get('PS_SHOP_NAME')));

        $config_parameters = parent::getConfig(PriceMinister::CONFIG_PM_PARAMETERS);
        $config_filters = parent::getConfig(PriceMinister::CONFIG_PM_FILTERS);

        if (empty($this->config['api']['login']) || empty($this->config['api']['pwd'])) {
            self::$errors[] = sprintf($this->l('You must configure your keypairs first'), $this->export);
            die;
        }

        $export_method = $config_parameters['import_method'];

        $id_warehouse = (int)$config_parameters['warehouse'] ? $config_parameters['warehouse'] : null;
        $all_offers = Tools::getValue('all-offers');

        if ($all_offers) {
            //
            $last_update = null;
        } elseif ($cron) {
            $batches = new PriceMinisterBatches(PriceMinister::CONFIG_BATCH_UPDATE);
             $last_update = $batches->getLast();
//            $last_update = date('Y-m-d H:i:s', strtotime('-1 YEAR'));

            // Check limitations (1 call/300s)
            $diff_from_last_update = time() - strtotime($batches->getLast());
            if ($diff_from_last_update <= 300) {
                self::$warnings[] = sprintf(
                    $this->l('The call to this web service is limited to 1 per 5 minutes. Please try again in %s seconds.'),
                    300 - (int)$diff_from_last_update
                );
                die;
            }
        } else {
            $batches = new PriceMinisterBatches(PriceMinister::CONFIG_BATCH_UPDATE);
            $last_update = $batches->getLast();
        }

        if (!is_dir($this->export)) {
            if (!mkdir($this->export)) {
                self::$errors[] = sprintf($this->l('Unable to create directory: %s'), $this->export);
                die;
            }
        }

        if (!PriceMinisterTools::isDirWriteable($this->export)) {
            self::$errors[] = sprintf($this->l('Unable to write in the directory: %s'), $this->export);
            die;
        }

        $this->cleanup();

        $filename = $this->export.sprintf('%s-offers-%s.xml', date('Ymd-His'), PriceMinisterTools::getFriendlyUrl(Configuration::get('PS_SHOP_NAME')));

        $active = Tools::getValue('active');
        $in_stock = Tools::getValue('in-stock');

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $get_combination = 'getAttributeCombinaisons';
        } else {
            $get_combination = 'getAttributeCombinations';
        }

        $profiles = PriceMinisterProfiles::getAll();

        if (!is_array($profiles) || !count($profiles)) {
            self::$errors[] = sprintf($this->l('You must configure profiles first'));
            die;
        }

        $id_categories_checked = PriceMinisterCategories::getAll();

        if (!is_array($id_categories_checked) || !count($id_categories_checked)) {
            self::$errors[] = sprintf($this->l('You must select categories first'));
            die;
        }

        $profile2category = PriceMinisterProfiles2Categories::getAll();

        if (!is_array($profile2category) || !count($profile2category)) {
            self::$errors[] = sprintf($this->l('You must select profiles first'));
            die;
        }
        //Remove unselected profiles2categories
        foreach (array_keys($profile2category) as $key) {
            if (!in_array($key, $id_categories_checked)) {
                unset($profile2category[$key]);
            }
        }
        //Reindex Profiles
        foreach ($profiles as $key => $val) {
            unset($profiles[$key]);
            $val['profile_id'] = $key;
            $profiles[$val['name']] = $val;
        }

        // Repricing
        $use_repricing = true;
        $last_repricing_date = date((string)Configuration::get('PM_LAST_REPRICING'));
        if ($last_repricing_date) {
            $repricing_time_out = 14400; // 4 hours
            $repricing_last_time = strtotime($last_repricing_date);
            $repricing_elapse = time() - $repricing_last_time;

            if ($repricing_elapse && $repricing_elapse > $repricing_time_out) {
                self::$warnings[] = sprintf(
                    $this->l('Repricing is active but feed has not been sent since %s hours, sending price feed to be safe'),
                    (int)$repricing_elapse / (60 * 60)
                );
                $use_repricing = false;
            }
        }
//        $use_repricing = false; // TODO remove

        // Customer Group
        $customer_id_group = (int)$config_parameters['customer_group'];
        if (!$customer_id_group) {
            $customer_id_group = version_compare(_PS_VERSION_, '1.5', '>=') ?
                (int)Configuration::get('PS_CUSTOMER_GROUP') : (int)_PS_DEFAULT_CUSTOMER_GROUP_;
        }
        // Apply customer group to current customer
        /** @see Product::getPriceStatic() */
        if (!isset(Context::getContext()->customer) || !Validate::isLoadedObject(Context::getContext()->customer)) {
            Context::getContext()->customer = new Customer((int)Configuration::get(PriceMinister::CONFIG_PM_CUSTOMER_ID));
        }
        Context::getContext()->customer->id_default_group = $customer_id_group;

        $dom = new DOMDocument();
        $dom->encoding = 'UTF-8';
        $dom->formatOutput = false;
        $items = $dom->createElement('items');
        $dom->appendChild($items);

        $products_history = array();
        $sku_history = array();
        $ean_history = array();

        foreach (array_unique($profile2category) as $profile_name) {
            // Get id_categories for this profile
            $id_categories = array_keys($profile2category, $profile_name);

            if (!array_key_exists($profile_name, $profiles)) {
                continue;
            }

            $profile = $profiles[$profile_name];

            if ($this->debug) {
                echo '<hr />';
                printf('%s(#%d): New Profile: ', basename(__FILE__), __LINE__, $profile['name']);
                var_dump($profile);
            }

            $products = PriceMinisterProductExt::getUpdateProducts($id_categories, $active, $in_stock, $last_update, null, null, $this->debug);

            if (!count($products) && $this->debug) {
                printf('%s(#%d): No Products for: %s/%s', basename(__FILE__), __LINE__, $profile_name, implode(',', $id_categories));
                continue;
            }

            foreach ($products as $product_list_entry) {
                if ($this->php_limits) {
                    $loop_average = (microtime(true) - $loop_start_time) / ++$i;

                    if ($this->max_execution_time && (($loop_start_time - $this->start_time) + $loop_average * $i * 1.3) >= $this->max_execution_time) {
                        self::$errors[] = $this->l('PHP max_execution_time is about to be reached, process interrupted.');
                        self::$errors[] = 'max_execution_time : '.$this->max_execution_time;
                        die;
                    }

                    if ($this->memory_limit != -1 && memory_get_peak_usage() * 1.3 > Tools::convertBytes($this->memory_limit)) {
                        self::$errors[] = $this->l('PHP memory_limit is about to be reached, process interrupted.');
                        die;
                    }
                }

                $id_product = (int)$product_list_entry['id_product'];

                if (isset($products_history[$id_product])) {
                    continue;
                }
                $products_history[$id_product] = true;

                $product = new Product($id_product, false, $this->id_lang);

                if (!Validate::isLoadedObject($product)) {
                    continue;
                }

                // Filtering Manufacturer & Supplier
                //
                if ($product->id_manufacturer) {
                    if (is_array($config_filters['manufacturers']) && in_array($product->id_manufacturer, $config_filters['manufacturers'])) {
                        continue;
                    }
                }

                if ($product->id_supplier) {
                    if (is_array($config_filters['suppliers']) && in_array($product->id_supplier, $config_filters['suppliers'])) {
                        continue;
                    }
                }

                if ($export_method == 'SKU') {
                    $reference = trim($product->reference);
                } elseif ($export_method == 'ID') {
                    $reference = $id_product;
                } else {
                    $reference = 'p'.$id_product;
                }

                PriceMinisterOffers::$offers[$id_product] = array();
                PriceMinisterOffers::$offers[$id_product][0]['id_product'] = $id_product;
                PriceMinisterOffers::$offers[$id_product][0]['reference'] = $reference;
                PriceMinisterOffers::$offers[$id_product][0]['active'] = (bool)$product->active;
                PriceMinisterOffers::$offers[$id_product][0]['ean'] = (int)$product->ean13 ? $product->ean13 : null;
                PriceMinisterOffers::$offers[$id_product][0]['upc'] = (int)$product->upc ? sprintf('%013s', $product->upc) : null;
                PriceMinisterOffers::$offers[$id_product][0]['condition'] = $product->condition;
                PriceMinisterOffers::$offers[$id_product][0]['name'] = $product->name;
                PriceMinisterOffers::$offers[$id_product][0]['status'] = null;

                // Products Option
                $options = PriceMinisterProductExt::getProductOptions($id_product, null, $this->id_lang);
                if (is_array($options) && count($options)) {
                    $options = reset($options);
                } else {
                    $options = array_fill_keys(
                        array('id_product', 'id_product_attribute', 'id_lang', 'force', 'disable', 'price', 'text'),
                        null
                    );
                }

                if ($this->debug) {
                    echo '<hr />';
                    printf('%s(#%d): Product: ', basename(__FILE__), __LINE__, PriceMinisterOffers::$offers[$id_product]);
                    printf('%s(#%d): Product Options: ', basename(__FILE__), __LINE__, $options);
                }

                $quantity_override = null;
                $price_override = null;

                $disabled = (bool)$options['disable'];
                $force = (bool)$options['force'];
                $comment = trim(Tools::substr($options['text'], 0, 200));

                if ($disabled) {
                    $product->active = false;
                    $quantity_override = 0;
                } elseif ($force) {
                    $product->active = true;
                    $quantity_override = 999;
                }

                if (!empty($options['price']) && is_numeric((float)$options['price'])) {
                    $price_override = (float)$options['price'];
                }

                // Children
                if ($product->hasAttributes()) {
                    $combinations = $product->{$get_combination}($this->id_lang);
                    $count_products++;

                    PriceMinisterOffers::$offers[$id_product][0]['has_children'] = true;

                    foreach ($combinations as $key => $combination) {
                        $id_product_attribute = (int)$combination['id_product_attribute'];

                        $combination_options = PriceMinisterProductExt::getProductOptions($id_product, $id_product_attribute);

                        if (is_array($combination_options) && count($combination_options)) {
                            $combination_option = reset($combination_options);
                        } else {
                            $combination_option = $options;
                        }

                        $p_repricing = PriceMinisterRepricing::getProductStrategy($id_product, $id_product_attribute, $this->id_lang);
                        if (is_array($p_repricing) && count($p_repricing)) {
                            $p_repricing = reset($p_repricing);
                        }

                        $disabled = (bool)$combination_option['disable'];
                        $price_override = (float)$combination_option['price'] ? (float)$combination_option['price'] : null;
                        $quantity_override = (int)$combination_option['force'] ? (int)$combination_option['force'] : null;
                        $comment = $combination_option['text'] ? trim(Tools::substr($combination_option['text'], 0, 200)) : $this->_default_comment;

                        if ($disabled) {
                            $product->active = false;
                            $quantity_override = 0;
                        } elseif ($force) {
                            $product->active = true;
                            $quantity_override = $force;
                        }

                        if ($export_method == 'SKU') {
                            $combination_reference = trim($combination['reference']);
                        } elseif ($export_method == 'ID') {
                            $combination_reference = sprintf('%d_%d', $id_product, $id_product_attribute);
                        } else {
                            $combination_reference = sprintf('p%dc%d', $id_product, $id_product_attribute);
                        }

                        if (!empty($combination_reference)) {
                            $product_identifier = sprintf('%s "%s"', $this->l('Product'), $combination_reference);
                        } else {
                            $product_identifier = sprintf('%s: %d/%d', $this->l('Product ID/Combination'), $id_product, $id_product_attribute);
                        }

                        if (!isset(PriceMinisterOffers::$offers[$id_product][$id_product_attribute])) {
                            $duplicate = false;
                            $code_check = true;

                            if ((int)$combination['ean13'] && !PriceMinisterTools::eanUpcCheck($combination['ean13']) || PriceMinisterTools::eanUpcisPrivate($combination['ean13'])) {
                                self::$warnings[] = sprintf('%s - %s - "%s"', $product_identifier, PriceMinisterSupport::message(PriceMinisterSupport::FUNCTION_EXPORT_WRONG_EAN, $this->l('Wrong EAN Code')), $combination['ean13']);
                                $code_check = false;
                            }

                            if ((int)$combination['ean13']) {
                                if (isset($ean_history[$product->condition.$combination['ean13']])) {
                                    self::$warnings[] = sprintf('%s - %s - "%s"', $product_identifier, PriceMinisterSupport::message(PriceMinisterSupport::FUNCTION_EXPORT_DUPLICATE_EAN, $this->l('Duplicated EAN Code')), $combination['ean13']);
                                    $duplicate = true;
                                }
                                $ean_history[$product->condition.$combination['ean13']] = true;
                            }

                            if ($export_method == 'SKU') {
                                if (!empty($combination_reference) && isset($sku_history[$combination_reference])) {
                                    self::$warnings[] = sprintf('%s - %s - "%s"', $product_identifier, PriceMinisterSupport::message(PriceMinisterSupport::FUNCTION_EXPORT_DUPLICATE, $this->l('Duplicated Reference')), $combination_reference);
                                    $duplicate = true;
                                }
                                $sku_history[$combination_reference] = true;
                            }

                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute] = array();
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['export'] = true;
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['id_product'] = $id_product;
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['reference'] = trim($product->reference);
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['combination_reference'] = $combination_reference;
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['combination_ean'] = (int)$combination['ean13'] ? $combination['ean13'] : null;
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['combination_upc'] = (int)$combination['upc'] ? sprintf('%013s', $combination['upc']) : null;
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['condition'] = $product->condition;
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['attributes_list'] = null;
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['has_attributes'] = true;
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['name'] = $product->name;
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['weight'] = $product->weight;

                            if ($quantity_override === null) {
                                if (version_compare(_PS_VERSION_, '1.5', '<')) {
                                    $quantity = Product::getQuantity((int)$id_product, $id_product_attribute);
                                } else {
                                    $quantity = Product::getRealQuantity($id_product, $id_product_attribute, $id_warehouse);
                                }

                                $outofstock = isset($config_filters['outofstock']) && (int)$config_filters['outofstock'] ? (int)$config_filters['outofstock'] : 0;
                                if ($outofstock > $quantity) {
                                    $quantity = 0;
                                }
                            } else {
                                $quantity = $quantity_override;
                            }

                            // Maximum quantity to send is 999
                            $quantity = min($quantity, 999);

                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['quantity'] = $quantity > -1 ? $quantity : 0;

                            if ($this->credentials['test']) {
                                PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['status'] = 'active';
                            }
                            if ($duplicate) {
                                PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['export'] = false;
                                PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['status'] = 'na';
                            } elseif (!$code_check) {
                                PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['export'] = false;
                                PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['status'] = 'na';
                            } elseif ($this->credentials['test'] && empty(PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['combination_reference'])) {
                                PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['combination_reference'] = Tools::strtoupper(uniqid());
                            } elseif (empty(PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['combination_reference'])) {
                                PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['export'] = false;
                                PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['status'] = 'na';

                                self::$warnings[] = sprintf('%s - %s', $product_identifier, PriceMinisterSupport::message(PriceMinisterSupport::FUNCTION_EXPORT_EMPTY_REFERENCE, $this->l('Empty Reference')));
                            } elseif ($quantity && $product->active) {
                                PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['status'] = 'active';
                            } elseif (!$product->active) {
                                PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['status'] = 'inactive';
                                PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['quantity'] = 0;
                            } elseif (!$quantity) {
                                PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['status'] = 'oos';
                            } else {
                                PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['status'] = 'na';
                            }

                            // List Price
                            $regular_price = $product->getPrice($config_parameters['taxes'], $id_product_attribute, 2, null, false, false);

                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['price'] = sprintf('%.02f', Tools::ps_round($regular_price, 2));

                            $new_price = null;
                            if ($price_override) {
                                $price = $price_override;
                            } else {
                                if ($use_repricing && $p_repricing && isset($p_repricing['target_price']) && $p_repricing['target_price']) {
                                    $new_price = (float)$p_repricing['target_price'];
                                    if ($config_parameters['taxes']) {
                                        $tax = Tax::getProductTaxRate($id_product);
                                        $new_price = Tools::ps_round($new_price * (1 + ($tax / 100)), 2);
                                    }
                                } else {
                                    // Final Price
                                    $price = $product->getPrice($config_parameters['taxes'], $id_product_attribute, 2, null, false, $product->on_sale || $config_parameters['specials']);

                                    if (array_key_exists('price_rule', $profile) && is_array($profile['price_rule'])) {
                                        $new_price = PriceMinisterTools::PriceRule($price, $profile['price_rule']);
                                    }
                                }
                            }

                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['final_price'] = sprintf('%.02f', Tools::ps_round($new_price ? $new_price : $price, 2));
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['comment'] = $comment;
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['private_comment'] = $private_comment;

                            // Price filter
                            if (isset($config_filters['price']['up']) && $config_filters['price']['up'] && $config_filters['price']['up'] < PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['final_price'] ||
                                isset($config_filters['price']['down']) && $config_filters['price']['down'] && $config_filters['price']['down'] > PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['final_price']) {
                                PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['export'] = false;
                                PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['status'] = 'na';
                            }

                            $count_combinations++;
                        }
                        $id_attribute = (int)$combination['id_attribute'];

                        PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['attributes'][$id_attribute]['group_name'] = $combination['group_name'];
                        PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['attributes'][$id_attribute]['attribute_name'] = $combination['attribute_name'];
                        PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['attributes'][$id_attribute]['is_color_group'] = $combination['is_color_group'];

                        if (PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['attributes_list']) {
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['attributes_list'] .= ' - '.$combination['attribute_name'];
                        } else {
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['attributes_list'] = $combination['attribute_name'];
                        }

                        PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['weight'] += $combination['weight'];

                        if (isset($profile['name_with_attributes']) && $profile['name_with_attributes'] && PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['attributes_list']) {
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['name'] .= ' - '.$combination['attribute_name'];
                        }

                        if ($matching_mode && !PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['combination_ean']) {
                            PriceMinisterOffers::$offers[$id_product][$id_product_attribute]['export'] = false;

                            self::$errors[] = sprintf(
                                '%s #%s (%s) : %s',
                                $this->l('Product'),
                                $id_product,
                                $id_product_attribute,
                                $this->l('No EAN code whereas the matching mode is enabled. EAN is compulsory.')
                            );
                            continue;
                        }
                    }
                } else {
                    $p_repricing = PriceMinisterRepricing::getProductStrategy($id_product, 0, $this->id_lang);
                    if (is_array($p_repricing) && count($p_repricing)) {
                        $p_repricing = reset($p_repricing);
                    }

                    PriceMinisterOffers::$offers[$id_product][0]['export'] = true;
                    PriceMinisterOffers::$offers[$id_product][0]['combination_reference'] = $reference;

                    if ($export_method == 'ADVANCED') {
                        PriceMinisterOffers::$offers[$id_product][0]['combination_reference'] = $reference.'c0';
                    }

                    PriceMinisterOffers::$offers[$id_product][0]['has_children'] = false;
                    PriceMinisterOffers::$offers[$id_product][0]['has_attributes'] = false;
                    PriceMinisterOffers::$offers[$id_product][0]['weight'] = $product->weight;

                    if ($quantity_override === null) {
                        if (version_compare(_PS_VERSION_, '1.5', '<')) {
                            $quantity = Product::getQuantity((int)$id_product);
                        } else {
                            $quantity = Product::getRealQuantity($id_product, null, $id_warehouse);
                        }

                        $outofstock = isset($config_filters['outofstock']) && (int)$config_filters['outofstock'] ? (int)$config_filters['outofstock'] : 0;
                        if ($outofstock > $quantity) {
                            $quantity = 0;
                        }
                    } else {
                        $quantity = $quantity_override;
                    }

                    // Maximum quantity to send is 999
                    $quantity = min($quantity, 999);

                    PriceMinisterOffers::$offers[$id_product][0]['quantity'] = $quantity > -1 ? $quantity : 0;

                    if (!empty($combination_reference)) {
                        $product_identifier = sprintf('%s "%s"', $this->l('Product'), $reference);
                    } else {
                        $product_identifier = sprintf('%s: %d', $this->l('Product ID'), $id_product);
                    }

                    $duplicate = false;
                    $code_check = true;

                    if ((int)$product->ean13 && !PriceMinisterTools::eanUpcCheck($product->ean13) || PriceMinisterTools::eanUpcisPrivate($product->ean13)) {
                        $code_check = false;
                        self::$warnings[] = sprintf('%s - %s - "%s"', $product_identifier, PriceMinisterSupport::message(PriceMinisterSupport::FUNCTION_EXPORT_WRONG_EAN, $this->l('Wrong EAN Code')), $product->ean13);
                    }

                    if ((int)$product->ean13 && !PriceMinisterTools::eanUpcCheck($product->ean13) || PriceMinisterTools::eanUpcisPrivate($product->ean13)) {
                        self::$warnings[] = sprintf('%s - %s - "%s"', $product_identifier, PriceMinisterSupport::message(PriceMinisterSupport::FUNCTION_EXPORT_WRONG_EAN, $this->l('Wrong EAN Code')), $product->ean13);
                        $code_check = false;
                    }

                    if ((int)$product->ean13) {
                        if (isset($ean_history[$product->condition.$product->ean13])) {
                            self::$warnings[] = sprintf('%s - %s - "%s"', $product_identifier, PriceMinisterSupport::message(PriceMinisterSupport::FUNCTION_EXPORT_DUPLICATE_EAN, $this->l('Duplicated EAN Code')), $product->ean13);
                            $duplicate = true;
                        }
                        $ean_history[$product->condition.$product->ean13] = true;
                    }

                    if ($export_method == 'SKU') {
                        if (!empty($reference) && isset($sku_history[$reference])) {
                            self::$warnings[] = sprintf('%s - %s - "%s"', $product_identifier, PriceMinisterSupport::message(PriceMinisterSupport::FUNCTION_EXPORT_DUPLICATE, $this->l('Duplicated Reference')), $reference);
                            $duplicate = true;
                        }
                        $sku_history[$reference] = true;
                    }

                    if ($this->credentials['test']) {
                        PriceMinisterOffers::$offers[$id_product][0]['status'] = 'active';
                    }
                    if ($duplicate) {
                        PriceMinisterOffers::$offers[$id_product][0]['export'] = false;
                        PriceMinisterOffers::$offers[$id_product][0]['status'] = 'na';
                    }
                    if (!$code_check) {
                        PriceMinisterOffers::$offers[$id_product][0]['export'] = false;
                        PriceMinisterOffers::$offers[$id_product][0]['status'] = 'na';
                    } elseif (empty(PriceMinisterOffers::$offers[$id_product][0]['reference'])) {
                        PriceMinisterOffers::$offers[$id_product][0]['export'] = false;
                        PriceMinisterOffers::$offers[$id_product][0]['status'] = 'na';
                    } elseif ($quantity && $product->active) {
                        PriceMinisterOffers::$offers[$id_product][0]['status'] = 'active';
                    } elseif (!$product->active) {
                        PriceMinisterOffers::$offers[$id_product][0]['status'] = 'inactive';
                        PriceMinisterOffers::$offers[$id_product][0]['quantity'] = 0;
                    } elseif (!$quantity) {
                        PriceMinisterOffers::$offers[$id_product][0]['status'] = 'oos';
                    } else {
                        PriceMinisterOffers::$offers[$id_product][0]['status'] = 'na';
                    }

                    $new_price = null;

                    if ($price_override) {
                        $price = $price_override;
                    } else {
                        if ($use_repricing && $p_repricing && isset($p_repricing['target_price']) && $p_repricing['target_price']) {
                            $new_price = (float)$p_repricing['target_price'];
                            if ($config_parameters['taxes']) {
                                $tax = Tax::getProductTaxRate($id_product);
                                $price = Tools::ps_round($new_price * (1 + ($tax / 100)), 2);
                            }
                        } else {
                            // List Price
                            $price = $product->getPrice($config_parameters['taxes'], null, 2, null, false, false);

                            PriceMinisterOffers::$offers[$id_product][0]['price'] = $new_price ? $new_price : $price;

                            // Final Price
                            $price = $product->getPrice($config_parameters['taxes'], null, 2, null, false, $product->on_sale || $config_parameters['specials']);

                            if (array_key_exists('price_rule', $profile) && is_array($profile['price_rule'])) {
                                $new_price = PriceMinisterTools::PriceRule($price, $profile['price_rule']);
                            }
                        }
                    }

                    PriceMinisterOffers::$offers[$id_product][0]['final_price'] = sprintf('%.02f', Tools::ps_round($new_price ? $new_price : $price, 2));
                    PriceMinisterOffers::$offers[$id_product][0]['comment'] = $comment;
                    PriceMinisterOffers::$offers[$id_product][0]['private_comment'] = $private_comment;

                    if ($matching_mode && !PriceMinisterOffers::$offers[$id_product][0]['ean']) {
                        PriceMinisterOffers::$offers[$id_product][0]['export'] = false;

                        self::$errors[] = sprintf(
                            '%s #%s (%s) : %s',
                            $this->l('Product'),
                            $id_product,
                            'N/A',
                            $this->l('No EAN code whereas the matching mode is enabled. EAN is compulsory.')
                        );
                    }

                    // Price filter
                    if (isset($config_filters['price']['up']) && $config_filters['price']['up'] && $config_filters['price']['up'] < PriceMinisterOffers::$offers[$id_product][0]['final_price'] ||
                        isset($config_filters['price']['down']) && $config_filters['price']['down'] && $config_filters['price']['down'] > PriceMinisterOffers::$offers[$id_product][0]['final_price']) {
                        PriceMinisterOffers::$offers[$id_product][0]['export'] = false;
                        PriceMinisterOffers::$offers[$id_product][0]['status'] = 'na';
                    }

                    $count_products++;
                }

                // In order to send multiple XML file to Rakuten for big catalogs, we do it later in a loop
                // See below $chunks
                // $this->ExportOffer($profile, $product, PriceMinisterOffers::$offers[$id_product], $items, $matching_mode);
                PriceMinisterOffers::$offers[$id_product]['profile'] = $profile;
            }
        }

        $chunks = array_chunk(PriceMinisterOffers::$offers, 7000, true);
        foreach ($chunks as $index => $chunk) {
            $dom = new DOMDocument();
            $dom->encoding = 'UTF-8';
            $dom->formatOutput = false;
            $items = $dom->createElement('items');
            $dom->appendChild($items);
            $filename = $this->export.sprintf(
                    '%s-offers-%s-%s.xml',
                    date('Ymd-His'),
                    PriceMinisterTools::getFriendlyUrl(Configuration::get('PS_SHOP_NAME')),
                    $index
                );

            $count_products = 0;
            $count_combinations = 0;

            foreach ($chunk as $list) {
                $profile = $list['profile'];
                unset($list['profile']);

                $product = new Product((int)$list[0]['id_product'], false, $this->id_lang);

                $count_products += 1;
                $count_combinations = count($list) - 1; // $list[0] contains general info, not the real product/combination

                $this->ExportOffer($profile, $product, $list, $items, $matching_mode);
            }

            $dom->save($filename);

            $pass = true;
            $file_url = sprintf('%s/%s', $this->url.'export', basename($filename));
            $href_link = sprintf('<a href="%s" target="_blank">%s/%s</a>', $file_url, preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', $this->url.'export'), basename($filename));

            if ($count_products && $count_combinations) {
                self::$messages[] = sprintf('%s - %d %s, %d %s.', $href_link, $count_products, $this->l('products'), $count_combinations, $this->l('combinations'));
            } elseif ($count_products) {
                self::$messages[] = sprintf('%s - %d %s', $href_link, $count_products, $this->l('products'));
            } else {
                $pass = false;
                self::$warnings[] = $this->l('Nothing to export');
            }

            if ($pass && ($action == 'export' || $cron)) {
                $params = array();
                $params['file'] = '@'.$filename;

                if ($this->credentials['test']) {
                    $result = array();
                    $result['importid'] = mt_rand(100000, 999999);
                } else {
                    $p = new PriceMinisterApiProducts($this->config['api']);
                    $result = $p->importProductsXML($params);
                }

                if (is_array($result) && array_key_exists('importid', $result)) {
                    $batches = new PriceMinisterBatches(PriceMinister::CONFIG_BATCH_UPDATE);
                    $batch = new PriceMinisterBatch($this->batch_timestart);
                    $batch->id = $result['importid'];
                    $batch->timestop = time();
                    $batch->created = 0;
                    $batch->updated = $count_products + $count_combinations;
                    $batch->deleted = 0;
                    $batch->file = basename($filename);
                    $batches->add($batch);
                    $batches->save();

                    self::$messages[] = sprintf('%s: #<b>%s</b>', $this->l('File successfully submitted to RakutenFrance, Import ID'), $result['importid']);
                } elseif (is_array($result) && array_key_exists('error', $result)) {
                    $message = sprintf('API Error: %s - %s', $result['error']['code'], $result['error']['message']);

                    if (array_key_exists('details', $result['error'])) {
                        $message .= ' - '.$result['error']['details']['detail'];
                    }

                    self::$errors[] = $message;
                    die;
                } else {
                    self::$errors[] = sprintf('API Error: %s', $this->l('Error while sending to RakutenFrance'));
                    die;
                }
            }
        }
//        $dom->save($filename);
//
//        $pass = true;
//
//        $file_url = sprintf('%s/%s', $this->url.'export', basename($filename));
//        $href_link = sprintf('<a href="%s" target="_blank">%s/%s</a>', $file_url, preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', $this->url.'export'), basename($filename));
//
//        if ($count_products && $count_combinations) {
//            self::$messages[] = sprintf('%s - %d %s, %d %s.', $href_link, $count_products, $this->l('products'), $count_combinations, $this->l('combinations'));
//        } elseif ($count_products) {
//            self::$messages[] = sprintf('%s - %d %s', $href_link, $count_products, $this->l('products'));
//        } else {
//            $pass = false;
//            self::$warnings[] = $this->l('Nothing to export');
//        }
//
//        if ($pass && ($action == 'export' || $cron)) {
//            $params = array();
//            $params['file'] = '@'.$filename;
//
//            if ($this->credentials['test']) {
//                $result = array();
//                $result['importid'] = mt_rand(100000, 999999);
//            } else {
//                $p = new PriceMinisterApiProducts($this->config['api']);
//                $result = $p->importProductsXML($params);
//            }
//
//            if (is_array($result) && array_key_exists('importid', $result)) {
//                $batches = new PriceMinisterBatches(PriceMinister::CONFIG_BATCH_UPDATE);
//                $batch = new PriceMinisterBatch($this->batch_timestart);
//                $batch->id = $result['importid'];
//                $batch->timestop = time();
//                $batch->created = 0;
//                $batch->updated = $count_products + $count_combinations;
//                $batch->deleted = 0;
//                $batch->file = basename($filename);
//                $batches->add($batch);
//                $batches->save();
//
//                self::$messages[] = sprintf('%s: #<b>%s</b>', $this->l('File successfully submitted to RakutenFrance, Import ID'), $result['importid']);
//            } elseif (is_array($result) && array_key_exists('error', $result)) {
//                $message = sprintf('API Error: %s - %s', $result['error']['code'], $result['error']['message']);
//
//                if (array_key_exists('details', $result['error'])) {
//                    $message .= ' - '.$result['error']['details']['detail'];
//                }
//
//                self::$errors[] = $message;
//                die;
//            } else {
//                self::$errors[] = sprintf('API Error: %s', $this->l('Error while sending to RakutenFrance'));
//                die;
//            }
//        }
    }

    private function cleanup()
    {
        $now = time();

        $output_dir = sprintf('%s/', rtrim($this->export, '/'));

        if (!is_dir($output_dir)) {
            return null;
        }

        $files = glob($output_dir.'*.xml');

        if (!is_array($files) || !count($files)) {
            return null;
        }

        // Sort by date
        foreach ($files as $file) {
            if (filemtime($file) < $now - (86400 * 3)) {
                unlink($file);
            }
        }
    }

    private function ExportOffer($profile_data, $product, $group_details, &$items, $matching_mode = false)
    {
        if (!$items instanceof DOMElement) {
            self::$errors[] = sprintf($this->l('There was an error during the XML export generation'));

            return;
        }

        $product_features = $product->getFeatures();
        $features = array();
        foreach ($product_features as $f) {
            $features[$f['id_feature']] = $f;
        }

        if ($matching_mode) {
            $offers_template = array('product', 'advert');
            $offer_tags = array(
                'product' => array(
                    'codebarres' => ''
                ),
                'advert' => array(
                    'aid' => '',
                    'state' => '',
                    'comment' => '',
                    'sellingPrice' => '',
                    'qty' => '',
                    'sellerReference' => ''
                )
            );
        } else {
            $offers_template = array('advert');
            $offer_tags = array(
                'advert' => array(
                    'aid' => '',
                    'state' => '',
                    'comment' => '',
                    'sellingPrice' => '',
                    'qty' => '',
                    'sellerReference' => ''
                )
            );
        }

        foreach ($group_details as $id_product_attribute => $combination) {
            if ($id_product_attribute == 0 && $combination['has_children']) {
                continue;
            }
            if (!$combination['export']) {
                continue;
            }

            if ($matching_mode) {
                if (isset($combination['combination_ean']) && !$combination['combination_ean']) { // || isset($combination['ean']) && !$combination['ean']
                    self::$errors[] = sprintf(
                        '%s #%s (%s) : %s',
                        $this->l('Product'),
                        $combination['id_product'],
                        $combination['combination_reference'] ? $combination['combination_reference'] : 'N/A',
                        $this->l('No EAN code whereas the matching mode is enabled. EAN is compulsory.')
                    );
                    continue;
                }
            }

            $item = $items->ownerDocument->createElement('item');
            $attributes = $items->ownerDocument->createElement('attributes');
            $item->appendChild($attributes);

            foreach ($offers_template as $attr) {
                $attribute_classification = $items->ownerDocument->createElement($attr);
                $child_assigned = false;
                if (isset($offer_tags[$attr])) {
                    $product_type_mapped = $offer_tags[$attr];

                    foreach ($product_type_mapped as $k => $v) {
                        if (isset(PriceMinisterForm::$excluded[$k])) {
                            $p_key = PriceMinisterForm::$excluded[$k];

                            // Matching - get EAN
                            if (is_array($p_key)) {
                                foreach ($p_key as $ex_key) {
                                    if (isset($combination[$ex_key]) && $combination[$ex_key]) {
                                        $v = $combination[$ex_key];
                                        $p_key = $ex_key;
                                        break;
                                    }
                                }
                            } else {
                                $v = isset($product->$p_key) ? $product->$p_key : '';
                            }

                            //as this value is obtained from the product, this might be a multilingua field
                            if (is_array($v) && isset($v[$this->id_lang])) {
                                $v = $v[$this->id_lang];
                            }

                            if (is_array($p_key)) {
                                $p_key = reset($p_key);
                            }

                            if (isset($combination[$p_key])) {
                                $v = $combination[$p_key];
                            }
                        }

                        $attribute = $items->ownerDocument->createElement('attribute');
                        $key = $items->ownerDocument->createElement('key');
                        $key->nodeValue = $k;
                        $attribute->appendChild($key);

                        if (!is_array($v)) {
                            if ($v === '' || $v === null) {
                                continue;
                            }

                            $value = $items->ownerDocument->createElement('value');
                            $value_val = $items->ownerDocument->createCDATASection($this->getValue($k, $v));
                            $value->appendChild($value_val);
                            $attribute->appendChild($value);
                        } else {
                            $found = false;
                            foreach ($v as $list_key => $list_val) {
                                if ($list_val == '') {
                                    continue;
                                }

                                $value = $items->ownerDocument->createElement('value');
                                $value_val = $items->ownerDocument->createCDATASection($this->getValue($k, $list_key));
                                $value->appendChild($value_val);
                                $attribute->appendChild($value);

                                $found = true;
                            }
                            if (!$found) {
                                continue;
                            }
                        }

                        $attribute_classification->appendChild($attribute);
                        $child_assigned = true;
                    }
                    if ($child_assigned) {
                        $attributes->appendChild($attribute_classification);
                    }
                }
            }

            if (($shipping_per_item_table = PriceMinisterProductExt::shippingPerItem($combination['weight'], $combination['price']))) {
                $shipping = $items->ownerDocument->createElement('shipping');
                $shipping_configuration = $items->ownerDocument->createElement('configuration');

                $attributes->appendChild($shipping);
                $shipping->appendChild($shipping_configuration);

                foreach ($shipping_per_item_table as $zone_name => $table) {
                    if ($zone_name == 'Drom_Com') {
                        $zone_name = 'FRANCE_DROM_COM';
                    } elseif ($zone_name == 'Monde') {
                        $zone_name = 'WORLD';
                    }

                    $zone = $items->ownerDocument->createElement('zone');
                    $shipping_configuration->appendChild($zone);

                    $name = $items->ownerDocument->createElement('name');
                    $zone->appendChild($name);
                    $name->nodeValue = $zone_name;

                    foreach ($table as $mode => $values) {
                        $type = $items->ownerDocument->createElement('type');
                        $zone->appendChild($type);

                        $type_name = $items->ownerDocument->createElement('name');
                        $type_authorization = $items->ownerDocument->createElement('authorization');
                        $type_leader_price = $items->ownerDocument->createElement('leader_price');

                        $type->appendChild($type_name);
                        $type->appendChild($type_authorization);
                        $type->appendChild($type_leader_price);

                        $type_name->nodeValue = $mode;
                        $type_authorization->nodeValue = $values['authorization'];
                        $type_leader_price->nodeValue = $values['leader_price'];

                        $type_follower_price = $items->ownerDocument->createElement('follower_price');
                        $type->appendChild($type_follower_price);
                        $type_follower_price->nodeValue = isset($values['follower_price']) ? $values['follower_price'] : $values['leader_price'];
                    }
                }
            } else {
                $shipping = $items->ownerDocument->createElement('shipping');
                $package_weight = $items->ownerDocument->createElement('package_weight');
                $package_weight->nodeValue = (int)abs($combination['weight'] * 1000);
                $attributes->appendChild($shipping);
                $shipping->appendChild($package_weight);
            }
            $items->appendChild($item);
        }
    }

    private function getValue($key, $val)
    {
        static $params = null;
        $value = $val;

        if ($params == null) {
            $params = unserialize(Configuration::get(PriceMinister::CONFIG_PM_PARAMETERS));
        }

        if ($key == 'state') {
            $list = isset($params)
            && isset($params['condition_map'])
            && is_array($params['condition_map'])
                ? $params['condition_map'] : array('N' => 'new');
            $state_key = array_search($value, $list);
            //if state is mapped
            if ($state_key !== false) {
                $value = $this->_conditions_pm[$state_key];
            } else {
                $value = '';
            }
        } elseif (isset($this->_lists[$key])) {
            $value = PriceMinisterTools::decodeHtml($val);
        }

        return $value;
    }
}

$OffersList = new PriceMinisterOffers(microtime(true));
$OffersList->Dispatch();
