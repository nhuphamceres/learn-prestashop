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
require_once(dirname(__FILE__).'/../classes/priceminister.form.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.support.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.batch.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.api.products.class.php');

//ini_set('max_execution_time', 900);

class PriceMinisterProducts extends PriceMinister
{

    public static $errors = array();
    public static $warnings = array();
    public static $messages = array();
    public static $products = array();
    public static $id_warehouse = 0;
    public $export;
    protected $batch_timestart = 0;
    protected $max_execution_time = 0;
    protected $memory_limit = 0;
    protected $php_limits = 0;
    protected $start_time = 0;
    private $ps_images;

    public function __construct($start_time)
    {
        parent::__construct();

        PriceMinisterContext::restore($this->context);

        // Set the correct shop context in the global context
        // Usefull for function to get image or stock for exemple
        if ($this->context->shop && Validate::isLoadedObject($this->context->shop)) {
            Context::getContext()->shop = $this->context->shop;
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
        // $this->ps_images = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'img/p/';
        // PriceMinister ne semble prendre que les images en HTTP, pas HTTPS
        $this->ps_images = 'http://'.htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'img/p/';
        $this->id_lang = (int)Language::getIdByIso('FR');
    }

    public static function JSON_Display_Exit()
    {
        $result = trim(ob_get_clean());

        if (!empty($result)) {
            PriceMinisterProducts::$warnings[] = trim($result);
        }

        $json = Tools::jsonEncode(
            array(
                'count' => count(PriceMinisterProducts::$products),
                'products' => PriceMinisterProducts::$products,
                'error' => (count(PriceMinisterProducts::$errors) ? true : false),
                'errors' => PriceMinisterProducts::$errors,
                'warning' => (count(PriceMinisterProducts::$warnings) ? true : false),
                'warnings' => PriceMinisterProducts::$warnings,
                'message' => count(PriceMinisterProducts::$messages),
                'messages' => PriceMinisterProducts::$messages
            )
        );

        if (($callback = Tools::getValue('callback'))) {
            echo (string)$callback.'('.$json.')';
        } else {
            echo "<pre>\n";
            echo PriceMinisterTools::jsonPrettyPrint($json);
            echo "<pre>\n";
        }
    }

    public function Dispatch()
    {
        ob_start();
        register_shutdown_function(array('PriceMinisterProducts', 'JSON_Display_Exit'));

        //  Check Access Tokens
        //
        $pm_token = Configuration::get(PriceMinister::CONFIG_PM_CRON_TOKEN);

        if ($pm_token != Tools::getValue('pm_token')) {
            self::$errors[] = $this->l('Wrong Token');
            die;
        }

        $this->export = $this->path.'export/';

        $cron = Tools::getValue('cron', 0);
        $action = Tools::getValue('action');

        switch ($action) {
            case 'check':
            case 'export':
                $this->ProductsList(false, $action);
                break;
            case 'cron':
                $this->ProductsList(true, $action);
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

    public function ProductsList($cron = false, $action = 'export')
    {
        if ($cron) {
            $batches = new PriceMinisterBatches(PriceMinister::CONFIG_BATCH_CREATE);
            $diff_from_last_update = time() - strtotime($batches->getLast());

            if ($diff_from_last_update <= 300) {
                self::$warnings[] = sprintf(
                    $this->l('The call to this web service is limited to 1 per 5 minutes. Please try again in %s seconds.'),
                    300 - (int)$diff_from_last_update
                );
                die;
            }
        }
        $count_products = 0;
        $count_combinations = 0;
        $i = 0;

        $date_create_from = Tools::getValue('date-create-from', null);
        $date_create_to = Tools::getValue('date-create-to', null);
        
        if ($date_create_from) {
            $date_create_from .= ' 00:00:00';
        }

        if ($date_create_to) {
            $date_create_to .= ' 23:59:59';
        }

        $loop_start_time = microtime(true);

        $config_parameters = parent::getConfig(PriceMinister::CONFIG_PM_PARAMETERS);
        $config_filters = parent::getConfig(PriceMinister::CONFIG_PM_FILTERS);

        if (empty($this->config['api']['login']) || empty($this->config['api']['pwd'])) {
            self::$errors[] = sprintf($this->l('You must configure your keypairs first'), $this->export);
            die;
        }

        $export_method = $config_parameters['import_method'];

        $private_comment = sprintf('%s %s', $this->l('Product creation from'), PriceMinisterTools::getFriendlyUrl(Configuration::get('PS_SHOP_NAME')));

        $id_warehouse = (int)$config_parameters['warehouse'] ? $config_parameters['warehouse'] : null;

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

        $filename = $this->export.sprintf('%s-products-%s.xml', date('Ymd-His'), PriceMinisterTools::getFriendlyUrl(Configuration::get('PS_SHOP_NAME')));

        $active = Tools::getValue('active');
        $in_stock = Tools::getValue('in-stock', false); // 2019-03-21 Previously was true

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

        //FIXME: Function to retrieve the categories

        //FORM: category[] = id_category
        //id_categories_checked = array_values($category);

        $id_categories_checked = PriceMinisterCategories::getAll();

        if (!is_array($id_categories_checked) || !count($id_categories_checked)) {
            self::$errors[] = sprintf($this->l('You must select categories first'));
            die;
        }

        // FORM: profile2category[id_category] = profile_name
        $profile2category = PriceMinisterProfiles2Categories::getAll();
        if (!is_array($profile2category) || !count($profile2category)) {
            self::$errors[] = sprintf($this->l('You must select profiles first'));
            die;
        }
        //Remove unselected profiles2categories
        foreach ($profile2category as $key => $val) {
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
        $ean_history = array();
        $sku_history = array();

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

            $send_ean_code = isset($profile['no_ean']) && $profile['no_ean'] ? false : true;

            $products = PriceMinisterProductExt::getCreateProducts($id_categories, $active, $in_stock, $date_create_from, $date_create_to, $this->debug);

            if (!count($products) && $this->debug) {
                printf('%s(#%d): No Products for: %s/%s', basename(__FILE__), __LINE__, $profile_name, implode(',', $id_categories));
                continue;
            }

            foreach ($products as $product_list_entry) {
                if ($this->php_limits) {
                    $loop_average = (microtime(true) - $loop_start_time) / ++$i;

                    if ($this->max_execution_time && (($loop_start_time - $this->start_time) + $loop_average * $i * 1.3) >= $this->max_execution_time) {
                        self::$errors[] = $this->l('PHP max_execution_time is about to be reached, process interrupted.');
                        die;
                    }

                    if ($this->memory_limit != -1 && memory_get_peak_usage() * 1.3 > Tools::convertBytes($this->memory_limit)) {
                        self::$errors[] = $this->l('PHP memory_limit is about to be reached, process interrupted.');
                        die;
                    }
                }

                $id_product = (int)$product_list_entry['id_product'];
                $pass = true;

                if (isset($products_history[$id_product])) {
                    continue;
                }
                $products_history[$id_product] = true;

                $id_shop = Tools::getValue('all_shop') ? Db::getInstance()->getValue(
                    'SELECT `id_shop_default`
                    FROM `'._DB_PREFIX_.'product`
                    WHERE `id_product` = '.(int)$id_product
                ) : null;

                if ($id_shop) {
                    Shop::setContext(Shop::CONTEXT_SHOP, $id_shop);
                    $this->context->shop = new Shop($id_shop);
                }

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

                $manufacturer_name = Manufacturer::getNameById((int)$product->id_manufacturer);

                PriceMinisterProducts::$products[$id_product] = array();
                PriceMinisterProducts::$products[$id_product][0]['id_product'] = $id_product;
                PriceMinisterProducts::$products[$id_product][0]['reference'] = $reference;
                PriceMinisterProducts::$products[$id_product][0]['active'] = (bool)$product->active;
                PriceMinisterProducts::$products[$id_product][0]['ean'] = $send_ean_code && (int)$product->ean13 ? $product->ean13 : null;
                PriceMinisterProducts::$products[$id_product][0]['upc'] = (int)$product->upc ? sprintf('%013s', $product->upc) : null;
                PriceMinisterProducts::$products[$id_product][0]['condition'] = $product->condition;
                PriceMinisterProducts::$products[$id_product][0]['name'] = $product->name;
                PriceMinisterProducts::$products[$id_product][0]['status'] = null;
                PriceMinisterProducts::$products[$id_product][0]['export'] = true;

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
                    printf('%s(#%d): Product: %s ', basename(__FILE__), __LINE__, print_r(PriceMinisterProducts::$products[$id_product], true));
                    printf('%s(#%d): Product Options: %s', basename(__FILE__), __LINE__, implode(', ', $options));
                }

                $quantity_override = null;
                $price_override = null;

                $disabled = (bool)$options['disable'];
                $force = (bool)$options['force'];
                $comment = $options['text'] ? trim(Tools::substr($options['text'], 0, 200)) : $this->_default_comment;

                if ($disabled) {
                    $product->active = false;
                    $quantity_override = 0;
                } elseif ($force) {
                    $product->active = true;
                    $quantity_override = (int)$options['force'];
                }

                if (!empty($options['price']) && is_numeric((float)$options['price'])) {
                    $price_override = (float)$options['price'];
                }

                // Children
                if ($product->hasAttributes()) {
                    $combinations = $product->{$get_combination}($this->id_lang);
                    $count_products++;

                    PriceMinisterProducts::$products[$id_product][0]['has_children'] = true;

                    foreach ($combinations as $key => $combination) {
                        $id_product_attribute = (int)$combination['id_product_attribute'];

                        $combination_options = PriceMinisterProductExt::getProductOptions($id_product, $id_product_attribute, $this->id_lang);

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
                        } elseif ($quantity_override) {
                            $product->active = true;
                            // $quantity_override = $force; // $quantity_override
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

                        if (!$send_ean_code) {
                            $combination['ean13'] = null;
                        }

                        if (!isset(PriceMinisterProducts::$products[$id_product][$id_product_attribute])) {
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

                            PriceMinisterProducts::$products[$id_product][$id_product_attribute] = array();
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['export'] = true;
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['id_product'] = $id_product;
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['reference'] = trim($product->reference); // TODO might need to change here to p[ID]c[COMB]

                            if ($export_method == 'ADVANCED') {
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['reference'] = $reference;
                            }

                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['combination_reference'] = $combination_reference;
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['combination_ean'] = $send_ean_code && (int)$combination['ean13'] ? $combination['ean13'] : null;
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['combination_upc'] = (int)$combination['upc'] ? sprintf('%013s', $combination['upc']) : null;
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['condition'] = $product->condition;
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['attributes_list'] = null;
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['has_attributes'] = true;
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['name'] = $product->name;
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['weight'] = $product->weight;

                            if (is_array($product->name)) {
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['name'] =
                                    array_key_exists($this->id_lang, $product->name) ?
                                        $product->name[$this->id_lang] : reset($product->name);
                            }

                            $supplier_reference = null;

                            if ($product->id_supplier) {
                                if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                                    $supplier_reference = ProductSupplier::getProductSupplierReference($id_product, $id_product_attribute, $product->id_supplier);
                                } else {
                                    $supplier_reference = $product->supplier_reference;
                                }

                                $supplier_reference = Tools::substr($supplier_reference, 0, 15);
                            }

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

                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['quantity'] = $quantity > -1 ? $quantity : 0;

                            if ($this->credentials['test']) {
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['status'] = 'active';
                            } elseif ($duplicate) {
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['export'] = false;
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['status'] = 'na';
                            } elseif (!$code_check) {
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['export'] = false;
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['status'] = 'na';
                            } elseif (empty(PriceMinisterProducts::$products[$id_product][$id_product_attribute]['combination_reference'])) {
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['export'] = false;
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['status'] = 'na';
                                self::$warnings[] = sprintf('%s - %s', $product_identifier, PriceMinisterSupport::message(PriceMinisterSupport::FUNCTION_EXPORT_EMPTY_REFERENCE, $this->l('Missing Reference')));
                            } elseif ($quantity && $product->active) {
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['status'] = 'active';
                            } elseif (!$product->active) {
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['status'] = 'inactive';
                            } elseif (!$quantity) {
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['export'] = false;
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['status'] = 'oos';
                            } else {
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['status'] = 'na';
                            }

                            // List Price
                            $regular_price = $product->getPrice($config_parameters['taxes'], $id_product_attribute, 2, null, false, false);

                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['price'] = sprintf('%.02f', Tools::ps_round($regular_price, 2));

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

                            $images = array();

                            foreach (PriceMinisterTools::getProductImages($id_product, $id_product_attribute, $this->id_lang) as $image) {
                                $file_image = _PS_PROD_IMG_DIR_.$image;

                                if (file_exists($file_image)) {
                                    $images[] = $this->ps_images.$image;
                                } else {
                                    self::$warnings[] = sprintf($this->l('Unable to find image %s in %s'), $image, _PS_PROD_IMG_DIR_);
                                }
                            }
                            if (count($images)) {
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['product_images'] = $images;
                            }

                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['manufacturer_name'] = $manufacturer_name;
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['supplier_reference'] = $supplier_reference;
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['final_price'] = sprintf('%.02f', Tools::ps_round($new_price ? $new_price : $price, 2));
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['comment'] = $comment;
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['private_comment'] = $private_comment;

                            // Price filter
                            if (isset($config_filters['price']['up']) && $config_filters['price']['up'] && $config_filters['price']['up'] < PriceMinisterProducts::$products[$id_product][$id_product_attribute]['final_price'] ||
                                isset($config_filters['price']['down']) && $config_filters['price']['down'] && $config_filters['price']['down'] > PriceMinisterProducts::$products[$id_product][$id_product_attribute]['final_price']) {
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['export'] = false;
                                PriceMinisterProducts::$products[$id_product][$id_product_attribute]['status'] = 'na';
                            }

                            $count_combinations++;
                        }
                        $id_attribute = (int)$combination['id_attribute'];

                        PriceMinisterProducts::$products[$id_product][$id_product_attribute]['attributes'][$id_attribute]['id_attribute_group'] = $combination['id_attribute_group'];
                        PriceMinisterProducts::$products[$id_product][$id_product_attribute]['attributes'][$id_attribute]['group_name'] = $combination['group_name'];
                        PriceMinisterProducts::$products[$id_product][$id_product_attribute]['attributes'][$id_attribute]['attribute_name'] = $combination['attribute_name'];
                        PriceMinisterProducts::$products[$id_product][$id_product_attribute]['attributes'][$id_attribute]['is_color_group'] = $combination['is_color_group'];

                        if (PriceMinisterProducts::$products[$id_product][$id_product_attribute]['attributes_list']) {
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['attributes_list'] .= ' - '.$combination['attribute_name'];
                        } else {
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['attributes_list'] = $combination['attribute_name'];
                        }

                        PriceMinisterProducts::$products[$id_product][$id_product_attribute]['weight'] += $combination['weight'];

                        if (isset($profile['name_with_attributes']) && $profile['name_with_attributes'] && PriceMinisterProducts::$products[$id_product][$id_product_attribute]['attributes_list']) {
                            if (is_array($combination['attribute_name'])) {
                                $combination['attribute_name'] = array_key_exists($this->id_lang, $combination['attribute_name']) ?
                                    $combination['attribute_name'][$this->id_lang] : reset($combination['attribute_name']);
                            }

                            // Combinations uses name of main products, let say : Boucles D'oreilles Fantaisie - Blanc
                            // To avoid other colors to have "Blanc" in the name, remove attribute name from name.
                            PriceMinisterProducts::$products[$id_product][$id_product_attribute]['name'] .= ' - '.$combination['attribute_name'];
                        }
                    }
                } else {
                    $p_repricing = PriceMinisterRepricing::getProductStrategy($id_product, 0, $this->id_lang);
                    if (is_array($p_repricing) && count($p_repricing)) {
                        $p_repricing = reset($p_repricing);
                    }

                    PriceMinisterProducts::$products[$id_product][0]['export'] = true;
                    PriceMinisterProducts::$products[$id_product][0]['has_children'] = false;
                    PriceMinisterProducts::$products[$id_product][0]['has_attributes'] = false;

                    $supplier_reference = null;

                    if ($product->id_supplier) {
                        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                            $supplier_reference = ProductSupplier::getProductSupplierReference($id_product, null, $product->id_supplier);
                        } else {
                            $supplier_reference = $product->supplier_reference;
                        }

                        $supplier_reference = Tools::substr($supplier_reference, 0, 15);
                    }

                    if ($quantity_override === null) {
                        if (version_compare(_PS_VERSION_, '1.5', '<')) {
                            $quantity = Product::getQuantity((int)$id_product, null);
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

                    PriceMinisterProducts::$products[$id_product][0]['quantity'] = $quantity > -1 ? $quantity : 0;

                    if (!empty($combination_reference)) {
                        $product_identifier = sprintf('%s "%s"', $this->l('Product'), $reference);
                    } else {
                        $product_identifier = sprintf('%s: %d', $this->l('Product ID'), $id_product);
                    }

                    $duplicate = false;
                    $code_check = true;

                    if (!$send_ean_code) {
                        $product->ean13 = null;
                    }

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
                        PriceMinisterProducts::$products[$id_product][0]['status'] = 'active';
                    } elseif ($duplicate) {
                        PriceMinisterProducts::$products[$id_product][0]['export'] = false;
                        PriceMinisterProducts::$products[$id_product][0]['status'] = 'na';
                    } elseif (!$code_check) {
                        PriceMinisterProducts::$products[$id_product][0]['export'] = false;
                        PriceMinisterProducts::$products[$id_product][0]['status'] = 'na';
                    } elseif (empty(PriceMinisterProducts::$products[$id_product][0]['reference'])) {
                        PriceMinisterProducts::$products[$id_product][0]['export'] = false;
                        PriceMinisterProducts::$products[$id_product][0]['status'] = 'na';
                        self::$warnings[] = sprintf('%s - %s', $product_identifier, PriceMinisterSupport::message(PriceMinisterSupport::FUNCTION_EXPORT_EMPTY_REFERENCE, $this->l('Missing Reference')));
                    } elseif ($quantity && $product->active) {
                        PriceMinisterProducts::$products[$id_product][0]['status'] = 'active';
                    } elseif (!$product->active) {
                        PriceMinisterProducts::$products[$id_product][0]['status'] = 'inactive';
                    } elseif (!$quantity) {
                        PriceMinisterProducts::$products[$id_product][0]['export'] = false;
                        PriceMinisterProducts::$products[$id_product][0]['status'] = 'oos';
                    } else {
                        PriceMinisterProducts::$products[$id_product][0]['status'] = 'na';
                    }

                    // List Price
                    $regular_price = $product->getPrice($config_parameters['taxes'], null, 2, null, false, false);
                    $new_price = null;

                    PriceMinisterProducts::$products[$id_product][0]['price'] = $regular_price;

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
                            $price = $product->getPrice($config_parameters['taxes'], null, 2, null, false, $product->on_sale || $config_parameters['specials']);

                            if (array_key_exists('price_rule', $profile) && is_array($profile['price_rule'])) {
                                $new_price = PriceMinisterTools::PriceRule($price, $profile['price_rule']);
                            }
                        }
                    }

                    $images = array();

                    foreach (PriceMinisterTools::getProductImages($id_product, null, $this->id_lang) as $image) {
                        $file_image = _PS_PROD_IMG_DIR_.$image;

                        if (!file_exists($file_image)) {
                            self::$warnings[] = sprintf($this->l('Unable to find image %s in %s'), $image, _PS_PROD_IMG_DIR_);
                            //continue;
                        }

                        $images[] = $this->ps_images.$image;
                    }
                    if (count($images)) {
                        PriceMinisterProducts::$products[$id_product][0]['product_images'] = $images;
                    }

                    PriceMinisterProducts::$products[$id_product][0]['manufacturer_name'] = $manufacturer_name;
                    PriceMinisterProducts::$products[$id_product][0]['supplier_reference'] = $supplier_reference;
                    PriceMinisterProducts::$products[$id_product][0]['weight'] = $product->weight;
                    PriceMinisterProducts::$products[$id_product][0]['combination_reference'] = $reference;

                    if ($export_method == 'ADVANCED') {
                        PriceMinisterProducts::$products[$id_product][0]['combination_reference'] = $reference.'c0';
                    }

                    PriceMinisterProducts::$products[$id_product][0]['combination_ean'] = (int)$product->ean13 ? $product->ean13 : null;
                    PriceMinisterProducts::$products[$id_product][0]['combination_upc'] = (int)$product->upc ? sprintf('%013s', $product->upc) : null;

                    PriceMinisterProducts::$products[$id_product][0]['final_price'] = sprintf('%.02f', Tools::ps_round($new_price ? $new_price : $price, 2));
                    PriceMinisterProducts::$products[$id_product][0]['comment'] = $comment;
                    PriceMinisterProducts::$products[$id_product][0]['private_comment'] = $private_comment;

                    // Price filter
                    if (isset($config_filters['price']['up']) && $config_filters['price']['up'] && $config_filters['price']['up'] < PriceMinisterProducts::$products[$id_product][0]['final_price'] ||
                        isset($config_filters['price']['down']) && $config_filters['price']['down'] && $config_filters['price']['down'] > PriceMinisterProducts::$products[$id_product][0]['final_price']) {
                        PriceMinisterProducts::$products[$id_product][0]['export'] = false;
                        PriceMinisterProducts::$products[$id_product][0]['status'] = 'na';
                    }

                    $count_products++;
                }

                if (isset($profile['short_long_description']) && $profile['short_long_description']) {
                    if (is_array($product->description) && isset($product->description[$this->id_lang])) {
                        $product->description[$this->id_lang] = $product->description_short[$this->id_lang].'<br>'.$product->description[$this->id_lang];
                    } else {
                        $product->description = $product->description_short.'<br>'.$product->description;
                    }
                }

                if (isset($profile['filter_description']) && $profile['filter_description']) {
                    if (is_array($product->description) && isset($product->description[$this->id_lang])) {
                        $product->description[$this->id_lang] = strip_tags($product->description[$this->id_lang], '<li><br><strong><i>');
                    } else {
                        $product->description = strip_tags($product->description, '<li><br><strong><i>');
                    }
                }

                if (is_array($product->description) && isset($product->description[$this->id_lang])) {
                    $product->description[$this->id_lang] = Tools::substr($product->description[$this->id_lang], 0, 4000);
                } else {
                    $product->description = Tools::substr($product->description, 0, 4000);
                }

                // In order to send multiple XML file to Rakuten for big catalogs, we do it later in a loop
                // See below $chunks
                // $this->ExportProduct($profile, $product, PriceMinisterProducts::$products[$id_product], $items);
                PriceMinisterProducts::$products[$id_product]['profile'] = $profile;
            }
        }

        $chunks = array_chunk(PriceMinisterProducts::$products, 5000, true);
        foreach ($chunks as $index => $chunk) {
            $dom = new DOMDocument();
            $dom->encoding = 'UTF-8';
            $dom->formatOutput = false;
            $items = $dom->createElement('items');
            $dom->appendChild($items);
            $filename = $this->export.sprintf(
                    '%s-products-%s-%s.xml',
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

                $this->ExportProduct($profile, $product, $list, $items);
            }

            $dom->save($filename);

            $pass = true;
            $file_url = sprintf('%s/%s', $this->url.'export', basename($filename));
            $href_link = sprintf('<a href="%s" target="_blank">%s/%s</a>', $file_url, preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', $this->url.'export'), basename($filename));

            if ($count_products && $count_combinations) {
                self::$messages[] = sprintf('%s - %d %s, %d %s', $href_link, $count_products, $this->l('products'), $count_combinations, $this->l('combinations'));
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
                    $batches = new PriceMinisterBatches(PriceMinister::CONFIG_BATCH_CREATE);
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
//            self::$messages[] = sprintf('%s - %d %s, %d %s', $href_link, $count_products, $this->l('products'), $count_combinations, $this->l('combinations'));
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
//                $batches = new PriceMinisterBatches(PriceMinister::CONFIG_BATCH_CREATE);
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

    private function ExportProduct($profile_data, $product, &$group_details, $items)
    {
        static $profiles = null, $models = null;

        if (!$items instanceof DOMElement) {
            self::$errors[] = sprintf($this->l('There was an error on thex XML export generation'));

            return;
        }
        if ($profiles == null) {
            $profiles = PriceMinisterProfiles::getAll();
        }

        if ($models == null) {
            $models = PriceMinisterModels::getAll();
        }

        $profile = isset($profile_data['profile_id']) ? $profile_data['profile_id'] : -1;
        $model_info = false;
        $model_id = isset($profiles[$profile]['model']) ? $profiles[$profile]['model'] : -1;

        if (!isset($profiles[$profile]['model']) || is_null($profiles[$profile]['model'])) {
            self::$warnings[] = sprintf('%s: %s', $this->l('Missing model for profile'), $profiles[$profile]['name']);

            return (false);
        }

        if ($model_id === -1) {
            die($this->l('No Model found for profile id: '.$profile));
        }

        $product_features = $product->getFeatures();
        $features = array();
        foreach ($product_features as $f) {
            $features[$f['id_feature']] = $f;
        }
        $models_list = &$models;

        if (isset($models_list) && isset($models_list[$model_id])) {
            $model_info = $models_list[$model_id];
        }

        if (!$model_info) {
            die($this->l('No Model Detailed Information found for profile id: '.$profile.' and Model Id: '.$model_id));
        }

        $product_type_template = array(
            'product',
            'advert',
            'media'
        );

        // Pre-Check remove from export entries with missing mappings
        foreach ($group_details as $id_product_attribute => $combination) {
            if ($id_product_attribute == 0 && $combination['has_children']) {
                continue;
            }

            if (!$combination['export']) {
                continue;
            }

            if (!(int)$combination['weight']) {
                /*
                self::$warnings[] = sprintf('%s "%s" - %s: %s', $this->l('Product'), $combination['combination_reference'], $this->l('Missing product weight'));
                $group_details[$id_product_attribute]['export'] = false;
                $group_details[$id_product_attribute]['status'] = 'na';
                */
            }

            foreach ($product_type_template as $attr) {
                $product_type = $models[$model_id];

                if (isset($product_type[$attr])) {
                    if (!($product_type_mapped = $this->map_product_type($attr, $product_type[$attr], $combination, $features))) {
                        self::$warnings[] = sprintf('%s "%s" - %s: %s', $this->l('Product'), $combination['combination_reference'], $this->l('Missing module configuration...'));
                        $group_details[$id_product_attribute]['export'] = false;
                        $group_details[$id_product_attribute]['status'] = 'na';
                        continue;
                    }
                }
            }
        }

        // Export
        $saved_description_for_customized_advert_detail = $product->description;
        $iterator = 1;
        foreach ($group_details as $id_product_attribute => $combination) {
            if ($id_product_attribute == 0 && $combination['has_children']) {
                continue;
            }

            if (!$combination['export']) {
                continue;
            }

            $combination['pm_weight'] = $this->pm_weight($combination['weight']);

            $product_type = $models[$model_id];
            $item = $items->ownerDocument->createElement('item');
            $alias = $items->ownerDocument->createElement('alias');

            $alias->nodeValue = $product_type['product_type'];
            $item->appendChild($alias);
            $attributes = $items->ownerDocument->createElement('attributes');
            $item->appendChild($attributes);

            foreach ($product_type_template as $attr) {
                $attribute_classification = $items->ownerDocument->createElement($attr);
                $child_assigned = false;

                if (isset($product_type[$attr])) {
                    if (!($product_type_mapped = $this->map_product_type($attr, $product_type[$attr], $combination, $features))) {
                        continue;
                    }

                    foreach ($product_type_mapped as $k => $v) {
                        // FIX poids
                        if ($k === 'poids' && !$v) {
                            $v = $combination['pm_weight'];
                        }

                        // Change english date to french date for revues
                        if ($attr == 'product' && $k == 'date' && $model_info['product_type'] == 'revues_produit') {
                            $v = str_replace('.', '-', $v);
                            $v = date('d/m/Y', strtotime($v));
                        }

                        // IF declination accepted and <product> THEN all info in first <product> and only "submitterreference" in other products
                        if (/*$product_type['product_type'] !== 'decoration_interieur' && */
                            isset($product_type['advert']['eanVariant']) && $attr == 'product' && !in_array($k, array('submitterreference', 'title')) && $iterator > 1
                        ) {
                            continue;
                        }
                        // IF declinaisons are not accepted THEN remove "submitterreference" values so products are not linked as declinaition
                        if (/*$product_type['product_type'] !== 'decoration_interieur' && */
                            !isset($product_type['advert']['eanVariant']) && $attr == 'product'
                        ) {
                            $combination['reference'] = $combination['combination_reference'];
                            $product->reference = $combination['combination_reference'];
                        }
                        // IF no tag <prdEdito> THEN use <customizedAdvertDetail>
                        // $saved_description_for_customized_advert_detail because in $attr=product the product description is substr 4000
                        if (!isset($product_type['product']['prdEdito']) && $attr == 'advert') {
                            $product->price_information = $saved_description_for_customized_advert_detail ?
                                $saved_description_for_customized_advert_detail : $product->description;
                        } elseif (is_array($product->description) && isset($product->description[$this->id_lang])) {
                            $product->description[$this->id_lang] = Tools::substr($product->description[$this->id_lang], 0, 4000);
                        } else {
                            $product->description = Tools::substr($product->description, 0, 4000);
                        }

                        $product->price_information = $saved_description_for_customized_advert_detail ?
                            $saved_description_for_customized_advert_detail : $product->description;

                        if (isset(PriceMinisterForm::$excluded[$k]) && $k !== 'poids'/* && $product_type['product_type'] !== 'laptop_produit'*/) {
                            // exception laptop_produit - "poids" with unit

                            $p_key = PriceMinisterForm::$excluded[$k];

                            if (is_array($p_key) && count($p_key)) {
                                // case EAN or UPC
                                foreach ($p_key as $ex_key) {
                                    if (isset($combination[$ex_key]) && $combination[$ex_key]) {
                                        $v = $combination[$ex_key];
                                        break;
                                    }
                                }
                            } else {
                                $v = isset($product->$p_key) ? $product->$p_key : '';
                                // as this value is obtained from the product, this might be a multilingua field
                                if (is_array($v) && isset($v[$this->id_lang])) {
                                    $v = $v[$this->id_lang];
                                }
                                if (isset($combination[$p_key])) {
                                    $v = $combination[$p_key];
                                }
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
                            // value & unit
                            if (isset($v['value']) && isset($v['unit']) && $v['value'] !== null && $v['unit']) {
                                $value = $items->ownerDocument->createElement('value');
                                $value_val = $items->ownerDocument->createCDATASection($v['value']);
                                $value->appendChild($value_val);
                                $attribute->appendChild($value);

                                $unit = $items->ownerDocument->createElement('unit');
                                $unit_val = $items->ownerDocument->createCDATASection($v['unit']);
                                $unit->appendChild($unit_val);
                                $attribute->appendChild($unit);
                            } // images
                            else {
                                $found = false;
                                $concat = null;
                                foreach ($v as $list_key => $list_val) {
                                    if (empty($list_val)) {
                                        continue;
                                    }
                                    $concat .= $list_val.'|';
                                    $found = true;
                                }
                                $concat = rtrim($concat, '|');
                                if (!$found) {
                                    continue;
                                } else {
                                    $value = $items->ownerDocument->createElement('value');
                                    $value_val = $items->ownerDocument->createCDATASection($this->getValue($k, $concat));
                                    $value->appendChild($value_val);
                                    $attribute->appendChild($value);
                                }
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

            //Campaigns
            if (isset($product_type['campaigns']) && isset($product_type['campaigns']['productsCampaignId'])) {
                $campaigns = $items->ownerDocument->createElement('campaigns');
                $child_assigned = false;
                $tags = array('productsCampaignId', 'productsCampaignRefPrice', 'productsCampaignPct', 'productsCampaignPrice');

                foreach ($product_type['campaigns']['productsCampaignId'] as $idx => $productsCampaignId) {
                    $campaign = $items->ownerDocument->createElement('campaign');
                    $campaign_attr = $items->ownerDocument->createElement('attribute');
                    $campaign_key = $items->ownerDocument->createElement('key');

                    foreach ($tags as $tag) {
                        if (isset($product_type['campaigns'][$tag]) && isset($product_type['campaigns'][$tag][$idx]) && $product_type['campaigns'][$tag][$idx] != '') {
                            $productsCampaignId_attr = $items->ownerDocument->createElement('attribute');
                            $productsCampaignId_key = $items->ownerDocument->createElement('key', $tag);
                            $productsCampaignId_value = $items->ownerDocument->createElement('value', $this->getValue($tag, $product_type['campaigns'][$tag][$idx]));

//							$productsCampaignId_node = $items->ownerDocument->createElement($tag);
//							$productsCampaignId_val = $items->ownerDocument->createCDATASection($this->getValue($tag, $product_type['campaigns'][$tag][$idx]));
//							$productsCampaignId_node->appendChild($productsCampaignId_val);

                            $productsCampaignId_attr->appendChild($productsCampaignId_key);
                            $productsCampaignId_attr->appendChild($productsCampaignId_value);
                            $campaign->appendChild($productsCampaignId_attr);

//							$campaign->appendChild($productsCampaignId_node);
                            $child_assigned = true;
                        }
                    }

                    if (!$child_assigned) {
                        continue;
                    }
                    $campaigns->appendChild($campaign);
                }

                if ($child_assigned) {
                    $attributes->appendChild($campaigns);
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
                        $type_follower_price->nodeValue = isset($values['follower_price']) ? $values['follower_price'] : $values['leader_price']; // TODO
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
            $iterator += 1;
        }
    }

    private function map_product_type($name, $data, $combination, $features)
    {
        $result = array();
        $processed = array();
        static $pm_attributes_values = array();

        static $models_filename = null;

        if (!is_array($models_filename)) {
            $models_filename = PriceMinisterModels::getXMLModelsFileName();
        }

        foreach ($data as $k => $v) {
            if (isset($processed[$k])) {
                continue;
            }
            $processed[$k] = true;

            if (Tools::strlen($k) > 3 && strripos($k, '_opt', -3) !== false) {
                $base = Tools::substr($k, 0, -4);
                $processed[$base] = true;
                $processed[$base.'_opt'] = true;
                $processed[$base.'_feat'] = true;
                $processed[$base.'_attr'] = true;

                switch ($v) {
                    case self::ATTRIBUTE:
                        $id_attribute_group = $data[$base.'_attr'];

                        if (!isset($combination['has_attributes']) || !$combination['has_attributes']) {
                            $result[$base] = (isset($data[$base.'_def']) && !empty($data[$base.'_def'])) ? $data[$base.'_def'] : null;
                            break; // Previously "continue", maybe replace by "continue 2" to skip product
                        }

                        $current_comb = false;
                        if (isset($combination['attributes'])) {
                            foreach ($combination['attributes'] as $id_attr => $attr_info) {
                                if (isset($attr_info['id_attribute_group']) && $attr_info['id_attribute_group'] == $id_attribute_group) {
                                    $current_comb = $attr_info;
                                    $current_comb['attribute_id'] = $id_attr;
                                    break;
                                }
                            }
                        }

                        if (!$current_comb) {
                            // default
                            $result[$base] = (isset($data[$base.'_def']) && !empty($data[$base.'_def'])) ? $data[$base.'_def'] : null;
                            break; // Previously "continue", maybe replace by "continue 2" to skip product
                        }

                        /* Find a matching attribute value in existing PriceMinister attr value*/
                        if (!array_key_exists($base, $pm_attributes_values)) {
                            $pm_attributes_values[$base] = PriceMinister::loadList($base, $models_filename);
                        }

                        if (is_array($pm_attributes_values[$base]) && count($pm_attributes_values[$base])) {
                            $idx = array_search((string)$current_comb['attribute_name'], $pm_attributes_values[$base]);
                            if ($idx !== false) {
                                $result[$base] = $pm_attributes_values[$base][$idx];
                                break;
                            }
                        }

                        /* Then, find corresponding mapping */
                        if (isset($this->config['attributes_mapping_left'][$id_attribute_group])
                            && isset($this->config['attributes_mapping_left'][$id_attribute_group][$base])
                            && in_array($current_comb['attribute_id'], $this->config['attributes_mapping_left'][$id_attribute_group][$base])
                        ) {
                            $key = array_search($current_comb['attribute_id'], $this->config['attributes_mapping_left'][$id_attribute_group][$base]);
                            $value = $this->config['attributes_mapping_right'][$id_attribute_group][$base][$key];
                            $result[$base] = ($value != '') ? $value : $data[$base.'_def'];
                        } else {/* Then, find a default value */
                            //default
                            $result[$base] = (isset($data[$base]) && !empty($data[$base])) ? $data[$base] : $data[$base.'_def'];
                        }

                        if (!Tools::strlen($result[$base]) && $base == 'size') {
                            $result[$base] = $current_comb['attribute_name'];
                        }

                        if (!Tools::strlen($result[$base])) {
                            self::$warnings[] = sprintf('%s "%s" - %s: %s', $this->l('Product'), $combination['combination_reference'], $this->l('Missing attribute or mapping for'), $base);

                            return (false);
                        }
                        break;
                    case self::FEATURE:
                        $id_feature = $data[$base.'_feat'];

                        if (!isset($features[$id_feature])) {
                            if (isset($data[$base.'_def'])) {
                                $result[$base] = $data[$base.'_def'];
                            }
                            break; // Previously "continue", maybe replace by "continue 2" to skip product
                        }
                        $current_feat = $features[$id_feature];

                        /* Find a matching attribute value in existing PriceMinister attr value*/
                        if (!array_key_exists($base, $pm_attributes_values)) {
                            $pm_attributes_values[$base] = PriceMinister::loadList($base, $models_filename);
                        }

                        if (isset($this->config['features_mapping_left'][$id_feature]) && isset($this->config['features_mapping_left'][$id_feature][$base])
                            && array_key_exists($current_feat['id_feature'], $this->config['features_mapping_left'][$id_feature][$base])
                        ) {
                            $key = array_search($current_feat['id_feature_value'], $this->config['features_mapping_left'][$id_feature][$base]);
                            $value = $this->config['features_mapping_right'][$id_feature][$base][$key];
                            $result[$base] = ($value != '') ? $value : $data[$base];
                        } elseif (isset($this->config['features_mapping_left'][$id_feature]) && isset($this->config['features_mapping_left'][$id_feature][$base])
                            && array_search($current_feat['id_feature_value'], $this->config['features_mapping_left'][$id_feature][$base]) !== false
                        ) {
                            $key = array_search($current_feat['id_feature_value'], $this->config['features_mapping_left'][$id_feature][$base]);
                            $value = $this->config['features_mapping_right'][$id_feature][$base][$key];
                            $result[$base] = ($value != '') ? $value : $data[$base];
                        } else {
                            // Custom feature value
                            $feature_value = new FeatureValue($current_feat['id_feature_value']);
                            if (Validate::isLoadedObject($feature_value) && isset($feature_value->value[$this->id_lang]) && $feature_value->value[$this->id_lang]) {
                                $result[$base] = $feature_value->value[$this->id_lang];
                            } else {
                                // default
                                $result[$base] = isset($data[$base.'_def']) ? $data[$base.'_def'] : '';
                            }
                        }

                        if (!Tools::strlen($result[$base])) {
                            self::$warnings[] = sprintf('%s "%s" - %s: %s', $this->l('Product'), $combination['combination_reference'], $this->l('Missing feature or mapping for'), $base);

                            return (false);
                        }
                        break;
                    default:
                        $result[$base] = isset($data[$base]) ? $data[$base] : '';
                }
                continue;
            } elseif (Tools::strlen($k) > 4 && strripos($k, '_unit', -4) !== false) {
                $base = Tools::substr($k, 0, -5);

                $processed[$base] = true;
                $processed[$base.'_unit'] = true;

                $result[$base] = array(
                    'value' => (isset($result[$base]) && $result[$base]) ? $result[$base] : $data[$base],
                    'unit' => $v
                );

                continue;
            }

            if ($this->is_ignored($k)) {
                continue;
            }

            $processed[$k] = true;
            // Exception Poids for laptop_produit
            if (isset($data['frequenceduprocesseur']) && $k == 'poids') {
                $result[$k] = array(
                    'value' => (int)$v,
                    'unit' => 'Kg'
                );
            } else {
                $result[$k] = $v;
            }
        }

        return $result;
    }

    private function is_ignored($k)
    {
        return (Tools::strlen($k) >= 4 && strripos($k, '_opt', -3) !== false) ||
            (Tools::strlen($k) >= 4 && strripos($k, '_def', -3) !== false) ||
            (Tools::strlen($k) >= 5 && strripos($k, '_attr', -4) !== false) ||
            (Tools::strlen($k) >= 5 && strripos($k, '_feat', -4) !== false);
    }

    private function pm_weight($product_weight)
    {
        $weight_label = $this->weight_table[0];

        foreach ($this->weight_table as $weight => $weight_label) {
            if (($product_weight * 1000) > $weight) {
                break;
            }
        }

        return (PriceMinisterTools::decodeHtml($weight_label));
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

$ProductsList = new PriceMinisterProducts(microtime(true));
$ProductsList->Dispatch();
