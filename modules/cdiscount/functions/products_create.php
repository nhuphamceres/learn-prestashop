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
require_once(dirname(__FILE__).'/../classes/cdiscount.webservice.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.specificfield.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.categories.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.support.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.context.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.support.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.zip.class.php');

require_once(dirname(__FILE__).'/../includes/cdiscount.model.php');

require_once(dirname(__FILE__).'/../common/tools.class.php');
require_once(dirname(__FILE__).'/../common/configuration.class.php');

class CDiscountExportProducts extends CDiscount
{
    const DIM_WIDTH = 1;
    const DIM_HEIGHT = 2;
    const DIM_DEPTH = 3;
    const XML = 'xml';
    const CSV = 'csv';
    const LF = "\n";
    const CRLF = "\r\n";
    private static $features_mapping = null;
    public $directory;
    public $export;
    private $errors           = array();
    private $xml_dir          = 'create';
    private $csv_dir          = 'csv';
    private $_cr              = "\n";
    private $zipfile;
    private $_debug = false;
    private $ps_images; // Image URL

    public function __construct()
    {
        parent::__construct();

        $useSsl = (bool)Configuration::get('PS_SSL_ENABLED_EVERYWHERE');
        $this->ps_images = ($useSsl ? 'https://' : 'http://') .
            htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'img/p/';

        $debugMode = Configuration::get(parent::KEY.'_DEBUG');
        $this->_debug = (bool)((int)$debugMode);

        $this->directory = str_replace('\\', '/', realpath(basename(__FILE__.'/../')));
        $this->export = $this->directory.'/export/';
        $this->export_csv = $this->url.'export/csv/';

        $this->export_url = $this->url.'functions/downloadxml.php?filename=';
        $this->zip_url = $this->url.'functions/downloadzip.php?filename=';
        $this->pickup_url = CommonTools::getHttpHost(true, true).$this->url.'export/';

        CDiscountContext::restore($this->context);

        $this->zipfile = CDiscountTools::getFriendlyUrl(Configuration::get('PS_SHOP_NAME')).'-create.zip';

        $this->dev_mode = (bool)Configuration::get(parent::KEY.'_DEV_MODE');
    }

    public function filter($text)
    {
        $text = html_entity_decode($text, ENT_COMPAT, 'UTF-8');
        $text = utf8_encode(utf8_decode($text));
        $text = str_replace('"', '\'', $text);
        $text = preg_replace('/-$/', '', $text);

        return ($text);
    }

    public function dispatch()
    {
        $cdtoken = Tools::getValue('cdtoken');
        $action = Tools::getValue('action');
        // Check Access Tokens
        $token = parent::decode(Configuration::get(parent::KEY.'_PS_TOKEN'));

        if ($cdtoken != $token) {
            $this->dieOnError($this->l('Wrong Token'));
        }

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            if (Shop::isFeatureActive()) {
                $id_shop = (int)Validate::isLoadedObject($this->context->shop) && (int)$this->context->shop->id ? $this->context->shop->id : 1;

                Shop::setContext(Shop::CONTEXT_SHOP, $id_shop);
            }
        }

        switch ($action) {
            case 'export':
                $this->productCreate(self::XML);
                break;
            case 'export_csv':
                $this->productCreate(self::CSV);
                break;
            case 'last_export':
                $this->lastExport();
                break;
            case 'report':
                $this->getReport();
                break;
            case 'products_send':
                $this->productsSend();
                break;
        }
    }

    private function dieOnError($msg)
    {
        echo $msg;

        $output = ob_get_clean();
        $json = Tools::jsonEncode(array('error' => true, 'msg' => $msg, 'output' => $output));
        // jQuery Output or PHP Output
        if (($callback = Tools::getValue('callback'))) {
            // jquery
            echo (string)$callback.'('.$json.')';
        } else {
            // cron
            return ($json);
        }
        die;
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    protected static $modelVariation = array();
    public function getSizeList($categoryId, $modelId)
    {
        if (!isset(self::$modelVariation[$modelId])) {
            self::$modelVariation[$modelId] = self::getInstanceCDiscountModel()->getModelVariations($categoryId, $modelId);
        }
        return self::$modelVariation[$modelId];
    }

    private function productCreate($export_type)
    {
        $id_shop = null;

        parent::loadAttributes();

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $this->context = Context::getContext();
            // Always init language, in some rare cases, language is stdClass instead of Language instance?
            $this->context->language = new Language(Configuration::get('PS_LANG_DEFAULT'));
            $this->context->currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));

            $controller = new FrontController();
            $controller->init();
            if (Shop::isFeatureActive()) {
                $id_shop = (int)Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
            }
            $id_warehouse = (int)Configuration::get(parent::KEY.'_WAREHOUSE');
        }


        $error = $create_active = $create_in_stock = false;
        $count = 0;

        $CDiscountSupport = new CDiscountSupport($this->id_lang);

        $handle = null;
        $history = array();
        $sku_history = array();
        $product_errors = array();
        $errorsCategoryToProfile = array();

        $product_filtered = array();
        $product_filtered['manufacturer'] = array();
        $product_filtered['supplier'] = array();

        $valid_values = array();

        // Force French
        $id_lang = Language::getIdByIso('fr');

        $cr = $this->_cr;

        $finalFile = null;
        $handles = array();

        $name = 'Products_from_'.CommonTools::ucwords(CDiscountTools::getFriendlyUrl(Configuration::get('PS_SHOP_NAME')));
        $exportedFileHeader = '';

        if ($export_type == self::XML) {
            $exportedFileHeader = '<ProductPackage Name="'.$name.'" xmlns="clr-namespace:Cdiscount.Service.ProductIntegration.Pivot;assembly=Cdiscount.Service.ProductIntegration" xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml">'."\n\t".'<ProductPackage.Products>'."\n"."\t<ProductCollection Capacity=\"0\">\n";
        }

        $ProductCollectionCapacity = 0;
        $productsCollectionToExport = array();

        $xmlFooter = "\t</ProductCollection>\n\t</ProductPackage.Products>\n</ProductPackage>\n";
        $modelFields = array();

        ob_start();

        // Parameters
        if (($export_type == self::XML && Tools::getValue('create-active')) || ($export_type == self::CSV && Tools::getValue('csv-active'))) {
            $create_active = true;
        }

        if (($export_type == self::XML && Tools::getValue('create-in-stock')) || ($export_type == self::CSV && Tools::getValue('csv-in-stock'))) {
            $create_in_stock = true;
        }

        if ($v = Tools::getValue('datepickerFrom2')) {
            $dateFrom = $v;
        } else {
            $dateFrom = null;
        }
        
        // Max products to create
        $limit = Tools::getValue('limit');

        if ($v = Tools::getValue('datepickerTo2')) {
            $dateTo = $v;
        } else {
            $dateTo = null;
        }
        // By SKU or by ID
        $import_type = Configuration::get(parent::KEY.'_IMPORT_TYPE');
        $import_type = ($import_type ? $import_type : Cdiscount::IMPORT_BY_ID);

        $title_format = Configuration::get(parent::KEY.'_TITLE_FORMAT');
        
        // Selected categories in the form
        $selected_categories = Tools::getValue('categories');

        if (!is_array($selected_categories) || !max($selected_categories)) {
            $this->errors[] = sprintf('%s(#%d): %s', basename(__FILE__), __LINE__, $this->l('You must configure categories to export'));
            $error = true;
        }
        // EAN Policy
        $ean_policy = Configuration::get(parent::KEY.'_EAN_POLICY');

        if (!$ean_policy) {
            $this->errors[] = sprintf('%s(%s): %s'.$cr, basename(__FILE__), __LINE__, $this->l('You must configure your EAN policy in your module configuration'));
            $error = true;
        }

        $send_marketing_description = Configuration::get(parent::KEY.'_MARKETING_DESCRIPTION');

        // Condition Map
        //
        $conditionMap = unserialize(parent::decode(Configuration::get(parent::KEY.'_CONDITION_MAP')));
        if (is_array($conditionMap)) {
            $conditionMap = array_flip($conditionMap);
        }

        if ($this->_debug) {
            CommonTools::p("Condition Map: ".print_r($conditionMap, true));
        }

        // Profiles & Models
        $models = $this->loadModels();
        $default_profiles = $this->loadProfiles();
        $default_profiles2categories = CDiscountConfiguration::get('profiles_categories');

        if (!is_array($default_profiles)) {
            $this->errors[] = sprintf('%s(#%d): %s', basename(__FILE__), __LINE__, $this->l('You must configure your profiles first'));
            $error = true;
        }

        if (!$error) {
            foreach ($default_profiles['name'] as $key => $name) {
                if (empty($name)) {
                    continue;
                }

                if (($index = array_search($name, $default_profiles2categories)) === false) {
                    continue;
                }
                if (!isset($selected_categories[$index]) || !$selected_categories[$index]) {
                    continue;
                }

                if (!isset($default_profiles['model'][$key]) || empty($default_profiles['model'][$key])) {
                    $this->errors[] = $this->pdd(sprintf('%s - "%s"', $this->l('You must configure model for profile'), $name), __LINE__, false, false);
                    $error = true;
                }
                
                $modelInternalId = $default_profiles['model'][$key];
                if (!isset($models[$modelInternalId])) {
                    $this->errors[] = $this->sdE(sprintf('%s - "%s"', $this->l('Model not found'), $modelInternalId));
                    $error = true;
                }
                $moduleModel = new CDiscountModuleModel($modelInternalId, $models[$modelInternalId]);
                if (!$moduleModel->universe) {
                    $this->errors[] = $this->sdE(sprintf('%s - "%s"', $this->l('You must configure universe for model'), $modelInternalId));
                    $error = true;
                }
                if (!$moduleModel->categoryId) {
                    $this->errors[] = $this->sdE(sprintf('%s - "%s"', $this->l('You must configure category for model'), $modelInternalId));
                    $error = true;
                }
            }
        }

        // 2020-12-23: Load u2c when use in loop

        if (!Tools::getValue('ignore-existing')) {
            $existing_products = CDiscountProduct::getExistingProducts();
        } else {
            $existing_products = array();
        }

        $weight_unit = Tools::strtoupper(preg_replace('/[^A-Za-z]/', '', Configuration::get('PS_WEIGHT_UNIT')));

        // Mappings (Color/Size)
        $fashion_right = unserialize(parent::decode(Configuration::get(parent::KEY.'_ATTRIBUTES_MAPPING_R')));

        $decription_field = Configuration::get(parent::KEY.'_DESCRIPTION_FIELD');
        $decription_field = ($decription_field ? $decription_field : Cdiscount::FIELD_DESCRIPTION_SHORT);

        $long_description_field = Configuration::get(parent::KEY.'_LONG_DESCRIPTION_FIELD');
        $long_description_field = ($long_description_field ? $long_description_field : Cdiscount::FIELD_DESCRIPTION_LONG);


        // Filter Price
        //
        // Prices Parameters
        //
        $useTaxes = (bool)Configuration::get(parent::KEY.'_USE_TAXES');
        $useSpecials = (bool)Configuration::get(parent::KEY.'_USE_SPECIALS');

        $params = unserialize(parent::decode(Configuration::get(parent::KEY.'_PRICE_FILTER')));
        $priceFilter = is_array($params) && count($params) ? $params : array();

        //Filter Stock
        //
        $stockMinFilter = (int)Configuration::get(parent::KEY.'_STOCK_FILTER');


        // Exclusions
        //
        $excluded_manufacturers = unserialize(parent::decode(Configuration::get(parent::KEY.'_FILTER_MANUFACTURERS')));
        $excluded_suppliers = unserialize(parent::decode(Configuration::get(parent::KEY.'_FILTER_SUPPLIERS')));

        $duplicateAlongCategories = array();

        if ($this->_debug) {
            CommonTools::p("Categories: ".print_r($selected_categories, true));
            CommonTools::p("Profiles Mapping: ".print_r($default_profiles2categories, true));
        }

        if (!is_array($default_profiles2categories) || !max($default_profiles2categories)) {
            $this->errors[] = sprintf('%s(#%s): %s', basename(__FILE__), __LINE__, $this->l('You must assign at least one profile to one category')).$cr;
            $error = true;
        }

        // Path to XML
        //
        $output_dir = sprintf('%s%s', $this->export, $this->xml_dir);
        $csv_dir = sprintf('%s%s', $this->export, $this->csv_dir);

        if ($this->_debug) {
            CommonTools::p("output_dir: ".$output_dir);
        }

        // Files
        //
        $rel_dir = $output_dir.'/_rels';
        $rel_file = $output_dir.'/_rels/.rels';
        $cont_dir = $output_dir.'/Content';
        $type_file = $output_dir.'/[Content_Types].xml';
        $products_file = $output_dir.'/Content/Products.xml';

        if (!is_dir($output_dir) && !mkdir($output_dir)) {
            $this->errors[] = sprintf('%s(%s): %s(%s)', basename(__FILE__), __LINE__, $this->l('Unable to create the directory'), $output_dir).$cr;
            $error = true;
        }
        if (!is_dir($rel_dir) && !mkdir($rel_dir)) {
            $this->errors[] = sprintf('%s(%s): %s(%s)', basename(__FILE__), __LINE__, $this->l('Unable to create the directory'), $rel_dir).$cr;
            $error = true;
        }
        if (!is_dir($cont_dir) && !mkdir($cont_dir)) {
            $this->errors[] = sprintf('%s(%s): %s(%s)', basename(__FILE__), __LINE__, $this->l('Unable to create the directory'), $rel_dir).$cr;
            $error = true;
        }
        if (!is_dir($csv_dir) && !mkdir($csv_dir)) {
            $this->errors[] = sprintf('%s(%s): %s(%s)', basename(__FILE__), __LINE__, $this->l('Unable to create the directory'), $csv_dir).$cr;
            $error = true;
        }

        // Check rights
        //
        if (!is_writable($this->export)) {
            chmod($this->export, Cdiscount::PERMISSIONS_DIRECTORY);
        }

        if (!is_writable($output_dir)) {
            chmod($output_dir, Cdiscount::PERMISSIONS_DIRECTORY);
        }

        if (!is_writable($rel_dir)) {
            chmod($rel_dir, Cdiscount::PERMISSIONS_DIRECTORY);
        }

        if (!is_writable($cont_dir)) {
            chmod($rel_dir, Cdiscount::PERMISSIONS_DIRECTORY);
        }


        if ($export_type == self::XML) {
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
                $this->errors[] = sprintf('%s(#%d): %s(%s)', basename(__FILE__), __LINE__, $this->l('Unwriteable file'), $rel_file).$cr;
                $error = true;
            }
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

        if ($export_type == self::XML) {
            if (!$this->createRelationships($rel_file)) {
                $this->errors[] = sprintf('%s(#%d): %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable create relationships file'), $rel_file).$cr;
                $error = true;
            }

            if (!$this->createContentType($type_file)) {
                $this->errors[] = sprintf('%s(#%d): %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable create content type file'), $type_file).$cr;
                $error = true;
            }
        }
        $profile2category = CDiscountConfiguration::get('profiles_categories');
        $ps_categories = CDiscountConfiguration::get('categories');

        // Export Loop
        if (!$error && $selected_categories) {
            $includeModelHeader = false;
            $currentModel = null;
            foreach ($selected_categories as $id_category) {
                $includeModelHeader = false;
                // Category Check
                if (!isset($default_profiles2categories[$id_category]) || empty($default_profiles2categories[$id_category])) {
                    // category has no profile : ignored
                    continue;
                }

                // Profile settings
                $profile = $default_profiles2categories[$id_category];

                if (!$profile) {
                    $this->errors[] = sprintf('%s(#%d): %s: %s', basename(__FILE__), __LINE__, $this->l('You must sets a profile for this category'), $profile);
                    $error = true;
                    continue;
                }
                // Index of selected profile
                $selected_profile = false;
                $profile_name = null;
                foreach ($default_profiles['name'] as $selected_profile => $profile_name) {
                    if ($profile_name === $profile) {
                        break;
                    }
                }

                if ($selected_profile === false || $profile_name == null || !isset($default_profiles['model'])) {
                    $this->errors[] = sprintf('%s(#%d): %s - %s', basename(__FILE__), __LINE__, $this->l('You must configure your profiles first'), $profile);
                    $error = true;
                    continue;
                }

                // Model internal id of selected profile
                $profile_model = isset($default_profiles['model'][$selected_profile]) ? $default_profiles['model'][$selected_profile] : null;

                // Select Model
                if (empty($profile_model)) {
                    $this->errors[] = sprintf('%s(#%d): %s - %s', basename(__FILE__), __LINE__, $this->l('You have to configure a model for this profile'), $profile_name);
                    $error = true;
                    continue;
                }

                $profile_name = isset($default_profiles['name'][$selected_profile]) ? $default_profiles['name'][$selected_profile] : null;
                $selectedModel = new CDiscountModuleModel($profile_model, isset($models[$profile_model]) ? $models[$profile_model] : array());
                $model_name = $selectedModel->name;
                $model_category = $selectedModel->categoryId;
                $marketplace_model_name = $selectedModel->modelName;

                $model_name_key = CDiscountModel::toKey($model_name);

                if (!($specificsFields = CDiscountSpecificField::getInstance()->getConfigurationByKey($selectedModel->id))) {
                    $this->errors[] = sprintf('%s(#%d): %s - %s', basename(__FILE__), __LINE__, $this->l('You have to configure specifics field for this model'), $model_name);
                    $error = true;
                    continue;
                }

                $model_id = $selectedModel->modelId;
                if ($currentModel == null || $currentModel != $model_id) {
                    $includeModelHeader = true;
                    $currentModel = $model_id;
                }
                if ($export_type == self::XML) {
                    $includeModelHeader = false;
                }

                if (!$model_id) {
                    $this->errors[] = sprintf('%s(#%d): %s: %s', basename(__FILE__), __LINE__, $this->l('You have to configure your model for'), $model_name);
                    $error = true;
                    continue;
                }

                if ($this->_debug) {
                    CommonTools::p("Model: ".print_r($model_name, true));
                    CommonTools::p("Specific Field Id: ".print_r($model_id, true));
                }

                $public = $selectedModel->public;
                $gender = $selectedModel->gender;

                $size_field = $selectedModel->fashionSize;
                $size_default_value = $selectedModel->defaultSize;
                $size_feature_field = $selectedModel->featureSize;
                $color_field = $selectedModel->fashionColor;
                $color_feature_field = $selectedModel->featureColor;
                $color_default_value = $selectedModel->defaultColor;
                $force_variant = $selectedModel->forceVariant;

                $cdCatsOfMdUniverse = CDiscountCategories::universeToCategories($selectedModel->universe);
                if (!$cdCatsOfMdUniverse || !is_array($cdCatsOfMdUniverse) || !isset($cdCatsOfMdUniverse[$selectedModel->categoryId])) {
                    if (!isset($errorsCategoryToProfile[$profile_name])) {
                        if (!$cdCatsOfMdUniverse || !is_array($cdCatsOfMdUniverse)) {
                            $this->errors[] = $this->sdE(sprintf(
                                '%s: %s - %s',
                                $this->l('Cannot fetch categories of the universe'),
                                $selectedModel->universe,
                                $profile_name
                            ));
                        } else {
                            $this->errors[] = $this->sdE(sprintf(
                                '%s: %s - %s - %s',
                                $this->l('The universe does not contain specific category'),
                                $selectedModel->universe,
                                $selectedModel->categoryId,
                                $profile_name
                            ));
                        }
                    }

                    $errorsCategoryToProfile[$profile_name] = true;
                    $error = true;
                    continue;
                }

                // List Products
                $products = CDiscountProduct::getExportProducts($id_category, $create_active, $create_in_stock, $dateFrom, $dateTo, $this->context->shop->id, $this->_debug);

                if (!count($products)) {
                    if ($this->_debug) {
                        $this->errors[] = sprintf('%s(#%d): %s (%d)', basename(__FILE__), __LINE__, $this->l('No product to export for this category'), $id_category);
                    }
                }

                if ($this->_debug) {
                    CommonTools::p("Products: ".count($products)." Items");
                }


                if ($limit && $count > $limit) {
                    if ($this->_debug) {
                        printf("Limit of $limit reached <br />\n");
                    }
                    break;
                }

//OLD CODE
                self::setFeatureMapping($id_lang);
// NEW CODE

                $features_mappings_map = CDiscountConfiguration::get('features_mapping');
                $sizes_mappings_map = CDiscountConfiguration::get('sizes_mapping');

                if ($products) {
                    foreach ($products as $product) {
                        $id_product = $product['id_product'];
                        //                       $features_product = Product::getFeaturesStatic($id_product);

                        $details = new Product($id_product);

                        if ($this->_debug) {
                            CommonTools::p("Product Data:".print_r(get_object_vars($details), true));
                        }


                        if (!Validate::isLoadedObject($details)) {
                            $this->errors[] = $error_msg = sprintf($this->l('Could not load the product id: %d'), $id_product);
                            CommonTools::p($error_msg);
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
                        }


                        if ($this->_debug) {
                            CommonTools::p("Category Set: ".print_r($category_set, true));
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
                                            $this->errors[] = $error_msg = sprintf($this->l('Product "%s" has several profiles in serveral categories !'), $id_product);
                                            CommonTools::p($error_msg);
                                        }
                                    }
                                }
                            }
                        } elseif ($details->id_category_default) {
                            $id_category = (int)$details->id_category_default;
                        } else {
                            if (CDiscount::$debug_mode) {
                                $error_msg = sprintf('Product has no category: %d'.$cr, $id_product);
                                CommonTools::p($error_msg);
                            }
                            continue;
                        }
                        if ($this->_debug) {
                            CommonTools::p("Category: ".print_r($id_category, true));
                        }
                        // Products with multiples categories ;
                        if (isset($duplicateAlongCategories[$id_product])) {
                            if ($this->_debug) {
                                CommonTools::p("Duplicated in categories:".print_r(get_object_vars($details), true));
                            }
                            continue;
                        }
                        $duplicateAlongCategories[$id_product] = true;

                        $features = $details->getFeatures();

                        if (!isset($modelFields[$model_id])) {
                            $modelFields[$model_id] = CDiscountSpecificField::getFields($model_category, $model_id);

                            if (!$modelFields[$model_id]) {
                                $this->errors[] = $error_msg = sprintf('%s(#%d): %s: %s'.$cr, basename(__FILE__), __LINE__, $this->l('Unable to get specific fields for model'), $model_id);
                                CommonTools::p($error_msg);
                                continue;
                            }
                        }

                        $valid_values = self::getInstanceCDiscountModel()->getModelValuesByModelId($model_category, $model_id);

                        $has_model_color = false;
                        $has_model_size = false;
                        $has_model_gender = null;
                        $has_model_public = null;

                        $model_features = array();
                        $size_field_value = null;
                        $color_field_value = null;

                        // Specific Model Fields
                        foreach ($modelFields[$model_id] as $array_key => $field_array) {
                            if ($array_key != $model_id) {
                                continue;
                            }

                            if (is_array($field_array)) {
                                foreach ($field_array as $id => $fieldset) {
                                    // 1- Basic Model Fields
                                    if (isset($fieldset['type'])) {
                                        // Color, Gender, Public
                                        switch ($fieldset['type']) {
                                            case 'color':
                                                $has_model_color = true;
                                                break;
                                            case 'size':
                                                $has_model_size = true;
                                                break;
                                            case 'gender':
                                                $has_model_gender = true;
                                                break;
                                            case 'public':
                                                $has_model_public = true;
                                                break;
                                        }
                                        continue;
                                    }
                                    if (!isset($fieldset['html_id']) || !Tools::strlen($fieldset['html_id'])) {
                                        continue;
                                    }

                                    // 2 - Advanced Model Fields

                                    $cdiscount_attribute_key = CDiscountSpecificField::getKey($fieldset['xml_tag']);

                                    if (isset($specificsFields[$cdiscount_attribute_key]['value']) && Tools::strlen($specificsFields[$cdiscount_attribute_key]['value'])) {
                                        // has a default value

                                        $default_feature_value = $specificsFields[$cdiscount_attribute_key]['value'];
                                    } else {
                                        $default_feature_value = null;
                                    }

                                    $mapped_feature_value = null;

                                    if (array_key_exists($cdiscount_attribute_key, $specificsFields) && $id_feature_mapping = (int)$specificsFields[$cdiscount_attribute_key]['feature']) {
                                        // has a mapping

                                        // seek features
                                        if (is_array($features) && count($features)) {
                                            foreach ($features as $feature) {
                                                if ($feature['id_feature'] != $id_feature_mapping) {
                                                    continue;
                                                }

                                                $id_feature_value = $feature['id_feature_value'];

                                                if ((bool)$feature['custom']) {
                                                    $feature_allow_custom = true;
                                                } //TODO: not yet implemented

                                                if (in_array($id_feature_value, array_keys(parent::$features_values[$id_feature_mapping]))) {   // TODO Validation: Yes, it exists
                                                    // Feature Found
                                                    $initial_feature_value = parent::$features_values[$id_feature_mapping][$id_feature_value];

                                                    $feature_self_key = CDiscountModel::toKey($initial_feature_value['value']);

                                                    // Matching values left and right - The value allowed (no mapping needed, eg: Red > Red)
                                                    if (array_key_exists($cdiscount_attribute_key, $valid_values) && array_key_exists($feature_self_key, $valid_values[$cdiscount_attribute_key]['values'])) {
                                                        // Valid Value

                                                        $mapped_feature_value = $valid_values[$cdiscount_attribute_key]['values'][$feature_self_key];
                                                        $model_features[$fieldset['name']] = $mapped_feature_value;
                                                    } elseif (array_key_exists($model_name_key, $features_mappings_map) && array_key_exists($cdiscount_attribute_key, $features_mappings_map[$model_name_key])) {
                                                        // Looking for a mapping
                                                        $feature_mapping = array_flip($features_mappings_map[$model_name_key][$cdiscount_attribute_key]);

                                                        if (array_key_exists($id_feature_value, $feature_mapping)) {
                                                            // Mapping Found !

                                                            $valid_value_key_found = $feature_mapping[$id_feature_value];

                                                            if (array_key_exists($cdiscount_attribute_key, $valid_values) && array_key_exists($valid_value_key_found, $valid_values[$cdiscount_attribute_key]['values'])) {
                                                                // Valid Value

                                                                $mapped_feature_value = $valid_values[$cdiscount_attribute_key]['values'][$valid_value_key_found];
                                                                $model_features[$fieldset['name']] = $mapped_feature_value;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    if (!Tools::strlen($mapped_feature_value) && Tools::strlen($default_feature_value)) {
                                        $model_features[$fieldset['name']] = $default_feature_value;
                                    }
                                }
                            }
                        }
                        if ($this->_debug) {
                            CommonTools::p("Model Features: ".print_r($model_features, true));
                        }

                        $feature_values = null;
                        $valid_size_values = $this->getSizeList($model_category, $model_id);

                        // Feature: Size Field - From Mapping
                        if ((int)$size_feature_field && array_key_exists('feature', $sizes_mappings_map) && array_key_exists($model_name_key, $sizes_mappings_map['feature']) && array_key_exists($size_feature_field, $sizes_mappings_map['feature'][$model_name_key])) {
                            if (is_array($features) && count($features)) {
                                foreach ($features as $feature) {
                                    if ($feature['id_feature'] != $size_feature_field) {
                                        continue;
                                    }

                                    $id_feature_value = $feature['id_feature_value'];

                                    if (array_key_exists($id_feature_value, $sizes_mappings_map['feature'][$model_name_key][$size_feature_field]) && Tools::strlen($sizes_mappings_map['feature'][$model_name_key][$size_feature_field][$id_feature_value])) {
                                        $size_field_value = $sizes_mappings_map['feature'][$model_name_key][$size_feature_field][$id_feature_value];
                                    }
                                }
                            }
                        }

                        // Feature: Size Field - Value from product sheet but if is in list of valid values
                        if (!Tools::strlen($size_field_value) && (int)$size_feature_field && is_array($features) && count($features)) {
                            foreach ($features as $feature) {
                                if ($feature['id_feature'] != $size_feature_field) {
                                    continue;
                                }

                                $id_feature_value = $feature['id_feature_value'];

                                if ($feature_values == null) {
                                    $feature_values = FeatureValue::getFeatureValuesWithLang($id_lang, $feature['id_feature'], (bool)$feature['custom']);
                                }

                                foreach ($feature_values as $fv) {
                                    if ($fv['id_feature_value'] == $id_feature_value) {
                                        $feature_value_key = CDiscountModel::toKey($fv['value']);

                                        if (array_key_exists($feature_value_key, $valid_size_values)) {
                                            $size_field_value = $valid_size_values[$feature_value_key];
                                        }
                                    }
                                }
                            }
                        }

                        // Feature: Size - Default Value (from Model)
                        if (!Tools::strlen($size_field_value)) {
                            $size_field_value = $size_default_value;
                        }
                        if ($this->_debug) {
                            CommonTools::p("Size field value: ".print_r($size_field_value, true));
                        }

                        // Feature Color Field
                        if ($color_feature_field) {
                            //Look for product features
                            foreach ($features as $fp) {
                                $id_feature = (int)$fp['id_feature'];
                                $id_feature_value = (int)$fp['id_feature_value'];

                                if ((int)$color_feature_field != $id_feature) {
                                    continue;
                                }

                                //Get values for options of feature
                                if ($feature_values == null) {
                                    $feature_values = FeatureValue::getFeatureValuesWithLang($id_lang, $id_feature, (bool)$fp['custom']);
                                }

                                if (!is_array($feature_values) || !count($feature_values)) {
                                    continue;
                                }

                                foreach ($feature_values as $fv) {
                                    if ($id_feature_value != $fv['id_feature_value']) {
                                        continue;
                                    }

                                    if (isset(self::$features_mapping[$id_feature][$id_feature_value][$id_lang])) {
                                        $color_field_value = self::$features_mapping[$id_feature][$id_feature_value][$id_lang];
                                    } else {
                                        $color_field_value = $fv['value'];
                                    }
                                    break;
                                }
                            }
                        }

                        // Feature: Color - Default Value
                        if (empty($color_field_value)) {
                            $color_field_value = $color_default_value;
                        }
                        if ($this->_debug) {
                            CommonTools::p("Color field value: ".print_r($color_field_value, true));
                        }

                        if ($this->_debug) {
                            CommonTools::p("Entering filters section: ".print_r($product_filtered, true));
                            CommonTools::p("Manufacturers: ".print_r($excluded_manufacturers, true));
                            CommonTools::p("Suppliers: ".print_r($excluded_suppliers, true));
                        }

                        // Filtering Manufacturer & Supplier
                        //
                        if ($details->id_manufacturer) {
                            if (is_array($excluded_manufacturers) && in_array($details->id_manufacturer, $excluded_manufacturers)) {
                                if (!isset($product_filtered['manufacturer'][$details->id_manufacturer])) {
                                    $product_filtered['manufacturer'][$details->id_manufacturer] = 0;
                                }
                                $product_filtered['manufacturer'][$details->id_manufacturer]++;
                                if ($this->_debug) {
                                    CommonTools::p("Filtered Manufacturer: ".print_r($details->id_manufacturer, true));
                                }
                                continue;
                            }
                        }

                        if ($details->id_supplier) {
                            if (is_array($excluded_suppliers) && in_array($details->id_supplier, $excluded_suppliers)) {
                                if (!isset($product_filtered['supplier'][$details->id_supplier])) {
                                    $product_filtered['supplier'][$details->id_supplier] = 0;
                                }
                                $product_filtered['supplier'][$details->id_supplier]++;
                                if ($this->_debug) {
                                    CommonTools::p("Filtered Manufacturer: ".print_r($details->id_supplier, true));
                                }
                                continue;
                            }
                        }

                        if (!(isset($details->condition) && $details->condition && isset($conditionMap[$details->condition]))) {
                            if ($this->_debug) {
                                $this->errors[] = $error_msg = sprintf($this->l('Condition map is not filled for this condition - Product "%d" Condition "%s"').$cr, $id_product, $details->condition);
                                CommonTools::p($error_msg);
                                $error = true;
                            }
                            continue;
                        }

                        if ($this->_debug) {
                            CommonTools::p("Exit from filters section");
                        }

                        // Product Options
                        $options = CDiscountProduct::getProductOptions($id_product, $id_lang);

                        if ($this->_debug) {
                            CommonTools::p("Product Options:".print_r($options, true));
                        }

                        if ($options['disable']) {
                            continue;
                        }
                        // Product Combinations
                        if (version_compare(_PS_VERSION_, '1.5', '<')) {
                            $combinations = $details->getAttributeCombinaisons($id_lang);
                        } else {
                            $combinations = $details->getAttributeCombinations($id_lang);
                        }

                        $parent_id = false;

                        // Pas de combinaison, on en cr?e une fictive pour rentrer dans la boucle
                        if (!is_array($combinations) or empty($combinations)) {
                            $combinations = array(
                                0 => array(
                                    'reference' => $details->reference,
                                    'ean13' => $details->ean13,
                                    'upc' => $details->upc,
                                    'id_product_attribute' => 0,
                                    'weight' => 0
                                )
                            );
                        }
                        // Grouping Combinations
                        asort($combinations);

                        $group_details = array();

                        foreach ($combinations as $comb => $combination) {
                            $id_product_attribute = isset($combination['id_product_attribute']) ? $combination['id_product_attribute'] : 0;
                            $id_attribute_group = isset($combination['id_attribute_group']) ? $combination['id_attribute_group'] : 0;
                            $id_attribute = isset($combination['id_attribute']) ? $combination['id_attribute'] : 0;

                            $group_details[$id_product_attribute][$id_attribute_group] = array();
                            $group_details[$id_product_attribute][$id_attribute_group]['reference'] = $combination['reference'];
                            // Synch Field (EAN, UPC, SKU ...)
                            $group_details[$id_product_attribute][$id_attribute_group]['ean13'] = $combination['ean13'];
                            $group_details[$id_product_attribute][$id_attribute_group]['upc'] = isset($combination['upc']) ? $combination['upc'] : null;
                            $group_details[$id_product_attribute][$id_attribute_group]['weight'] = $combination['weight'];

                            if (isset($combination['attribute_name'])) {
                                $group_details[$id_product_attribute][$id_attribute_group]['attribute_name'] = $combination['attribute_name'];
                            } else {
                                $group_details[$id_product_attribute][$id_attribute_group]['attribute_name'] = '';
                            }

                            if (isset($combination['id_attribute'])) {
                                $group_details[$id_product_attribute][$id_attribute_group]['id_attribute'] = $combination['id_attribute'];
                            } else {
                                $group_details[$id_product_attribute][$id_attribute_group]['id_attribute'] = '';
                            }

                            if (isset($combination['group_name'])) {
                                $group_details[$id_product_attribute][$id_attribute_group]['group_name'] = $combination['group_name'];
                            } else {
                                $group_details[$id_product_attribute][$id_attribute_group]['group_name'] = '';
                            }
                        }

                        if ($this->_debug) {
                            CommonTools::p("Combinations: ".print_r($group_details, true));
                        }

                        $first = 0;

                        $idx = 0;

                        // Export Combinations or Products Alone
                        foreach ($group_details as $id_product_attribute => $combination) {
                            $idx++;
                            $group_detail = array();

                            if (!$first && $id_product_attribute) {
                                $parent_id = sprintf('%s_%s', $details->id, $id_product_attribute);
                            }

                            $first = 1;
                            $attributesShort = array(); // value only
                            $attributesLong = array();  // Label and value
                            $ean13 = $reference = null;

                            $weight = $details->weight;


                            //Filter Price
                            $stdPrice = $details->getPrice($useTaxes, $id_product_attribute, 2, null, false, !$details->on_sale && $useSpecials);
                            if ($priceFilter && isset($priceFilter['gt']) && (int)$priceFilter['gt'] && (float)$stdPrice > (float)$priceFilter['gt']) {
                                printf($this->l('Skipping filtered product: price %.2f > %.2f').$cr, $stdPrice, $priceFilter['gt']);
                                continue;
                            } elseif ($priceFilter && isset($priceFilter['lt']) && (int)$priceFilter['lt'] && (float)$stdPrice < (float)$priceFilter['lt']) {
                                printf($this->l('Skipping filtered product: price %.2f < %.2f').$cr, $stdPrice, $priceFilter['lt']);
                                continue;
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


                            //Filter Stock
                            if ($stockMinFilter && (int)$quantity < $stockMinFilter) {
                                printf($this->l('Skipping filtered product: quantity %d < %d').$cr, $quantity, $stockMinFilter);
                                continue;
                            }


                            foreach ($combination as $id_group => $group_detail) {
                                if ($group_detail['id_attribute']) {
                                    $id_attribute = $group_detail['id_attribute'];
                                } else {
                                    $id_attribute = null;
                                }

                                if ($group_detail['attribute_name']) {
                                    $attributesShort[] = $group_detail['attribute_name'];  // Push to array
                                }
                                if ($group_detail['group_name'] && $group_detail['attribute_name']) {
                                    $attributesLong[] = sprintf('%s: %s', $group_detail['group_name'], $group_detail['attribute_name']); // Push to array
                                }

                                if (isset($group_detail['reference'])) {
                                    $reference = $group_detail['reference'];
                                }
                                if (isset($group_detail['ean13']) && $group_detail['ean13']) {
                                    $ean13 = $group_detail['ean13'];
                                } elseif (isset($group_detail['upc']) && $group_detail['upc']) {
                                    $ean13 = $group_detail['upc'];
                                }

                                if (isset($group_detail['weight'])) {
                                    $weight = $details->weight + $group_detail['weight'];
                                }

                                if ((int)$size_field && (int)$id_attribute && $size_field == $id_group) {
                                    if (is_array($sizes_mappings_map) && array_key_exists('attribute', $sizes_mappings_map) && array_key_exists($model_name_key, $sizes_mappings_map['attribute']) && array_key_exists($size_field, $sizes_mappings_map['attribute'][$model_name_key])) {
                                        if (array_key_exists($id_attribute, $sizes_mappings_map['attribute'][$model_name_key][$size_field])) {
                                            if (Tools::strlen($sizes_mappings_map['attribute'][$model_name_key][$size_field][$id_attribute])) {
                                                $size_field_value = $sizes_mappings_map['attribute'][$model_name_key][$size_field][$id_attribute];
                                            }
                                        }
                                    }
                                } elseif ((int)$color_field && (int)$id_attribute && $color_field == $id_group) {
                                    if (is_array($sizes_mappings_map) && array_key_exists($color_field, $fashion_right) && array_key_exists($id_attribute, $fashion_right[$color_field])) {
                                        if (Tools::strlen($fashion_right[$color_field][$id_attribute])) {
                                            $color_field_value = $fashion_right[$color_field][$id_attribute];
                                        } elseif (Tools::strlen($combination[$color_field]['attribute_name'])) {
                                            $color_field_value = $combination[$color_field]['attribute_name'];
                                        }
                                    } elseif (Tools::strlen($combination[$color_field]['attribute_name'])) {
                                        $color_field_value = $combination[$color_field]['attribute_name'];
                                    }
                                }

                                if (!$this->dev_mode && $ean_policy == Cdiscount::EAN_POLICY_NORMAL || ($ean_policy == Cdiscount::EAN_POLICY_PERMISSIVE && (int)$ean13)) {
                                    if (empty($ean13)) {
                                        $product_errors['missing_ean'][$id_product] = array(
                                            'reference' => $reference,
                                            'id_product' => $id_product,
                                            'id_product_attribute' => $id_product_attribute
                                        );

                                        if ($this->_debug) {
                                            CommonTools::p("Missing EAN:".print_r($product_errors['missing_ean'][$id_product], true));
                                        }

                                        continue;
                                    }
                                    if (!CommonTools::eanUpcCheck($ean13)) {
                                        $product_errors['wrong_ean'][$ean13] = array(
                                            'ean13' => $ean13,
                                            'id_product' => $id_product,
                                            'id_product_attribute' => $id_product_attribute
                                        );

                                        if ($this->_debug) {
                                            CommonTools::p("eanUpcCheck failed:".print_r($product_errors['wrong_ean'][$ean13], true));
                                        }
                                        continue;
                                    }
                                }
                            }

                            // Export as a variant
                            //
                            $isVariant = ($id_product_attribute && $size_field_value && $color_field_value);

                            // Doesn't have combination but category requires the product to be a variant
                            if ($force_variant) {
                                $parent_id = sprintf('%s_%s', $details->id, $id_product_attribute);
                                $isVariant = true;
                            }

                            $name_short = $details->name[$id_lang];
                            $name_long = $details->name[$id_lang];

                            $titleAttributesShort = '';
                            $titleAttributesLong = '';
                            if ($id_product_attribute) {
                                if (count($attributesShort)) {
                                    $titleAttributesShort = trim(implode(' - ', $attributesShort));
                                }
                                if (count($attributesLong)) {
                                    $titleAttributesLong = implode(' - ', $attributesLong);
                                }
                            }

                            // Manufacturer
                            $manufacturer_name = Manufacturer::getNameById((int)$details->id_manufacturer);

                            $marketplace_categories = explode(' / ', $cdCatsOfMdUniverse[$model_category]);

                            if (is_array($marketplace_categories) && count($marketplace_categories)) {
                                $last_leaf = end($marketplace_categories);
                                $product_title_category = CommonTools::ucwords($last_leaf);
                            } else {
                                $product_title_category = null;
                            }

                            switch ($title_format) {
                                case Cdiscount::TITLE_NAME_ATTRIBUTES_WITH_LABEL:
                                    if ($titleAttributesLong) {
                                        $name_long = sprintf('%s (%s)', $name_long, $titleAttributesLong);
                                    }
                                    break;
                                case Cdiscount::TITLE_BRAND_NAME_ATTRIBUTES:
                                    if (Tools::strlen($manufacturer_name)) {
                                        $name_long = sprintf('%s - %s - %s', trim($manufacturer_name), trim($name_long), $titleAttributesShort);
                                    }
                                    break;
                                case Cdiscount::TITLE_CATEGORY_BRAND_NAME_ATTRIBUTES:
                                    if (Tools::strlen($manufacturer_name) && Tools::strlen($product_title_category)) {
                                        $name_long = sprintf('%s - %s - %s - %s', trim($product_title_category), trim($manufacturer_name), trim($name_long), $titleAttributesShort);
                                    } elseif (Tools::strlen($manufacturer_name)) {
                                        $name_long = sprintf('%s - %s - %s', trim($manufacturer_name), trim($name_long), $titleAttributesShort);
                                    } elseif (Tools::strlen($product_title_category)) {
                                        $name_long = sprintf('%s - %s - %s', trim($product_title_category), trim($name_long), $titleAttributesShort);
                                    }
                                    break;
                                case Cdiscount::TITLE_CATEGORY_NAME_ATTRIBUTES:
                                    if (Tools::strlen($product_title_category)) {
                                        $name_long = sprintf('%s - %s', trim($product_title_category), trim($name_long));
                                    }
                                    break;
                                case Cdiscount::TITLE_NAME_REFERENCE:
                                    $name_long = sprintf('%s - %s', trim($name_long), trim($reference));
                                    break;
                                default:
                                    if ($titleAttributesShort) {
                                        $name_long = sprintf('%s %s', $name_long, $titleAttributesShort);
                                    }
                                    break;
                            }
                            $name_long = rtrim($name_long, ' -');

                            if (method_exists('Product', 'externalName')) {
                                $name_short = Product::externalName($id_lang, $id_product, $id_product_attribute, true);
                                $name_long = Product::externalName($id_lang, $id_product, $id_product_attribute);
                            }

                            if ($import_type == Cdiscount::IMPORT_BY_SKU && empty($reference)) {
                                $product_errors['empty_reference'][] = array(
                                    'ean13' => $ean13,
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute
                                );
                                continue;
                            }

                            if ($this->_debug) {
                                CommonTools::p(sprintf("Product ID: %d/%d", $id_product, $id_product_attribute));
                                CommonTools::p(sprintf("Product SKU: %s", $reference));
                                CommonTools::p(sprintf("Title Format: %s", $title_format));
                                CommonTools::p(sprintf("Title: %s", $name_long));
                            }

                            if (!CDiscountTools::validateSKU($reference)) {
                                $product_errors['invalid_reference'][] = array(
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute,
                                    'reference' => $reference
                                );
                                continue;
                            }

                            if ($import_type == Cdiscount::IMPORT_BY_SKU) {
                                //
                                $sku = $reference;
                            } else {
                                // Product ID / Declination_ID
                                if ($id_product_attribute) {
                                    $sku = sprintf('%s_%s', $details->id, $id_product_attribute);
                                } else {
                                    $sku = sprintf('%s', $details->id);
                                }
                            }

                            if ($export_type == self::XML && in_array($sku, $existing_products)) {
                                $product_errors['existing_product'][] = array(
                                'id_product' => $id_product,
                                'id_product_attribute' => $id_product_attribute,
                                'reference' => $reference
                                );

                                continue;
                            }

                            if ($ean13 && isset($history[$ean13])) {
                                $product_errors['duplicate_ean'][] = array(
                                    'ean13' => $ean13,
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute,
                                    'reference' => $reference
                                );
                                continue;
                            }

                            if ((int)$ean13 && ($ean_policy == Cdiscount::EAN_POLICY_NORMAL || $ean_policy == Cdiscount::EAN_POLICY_PERMISSIVE)) {
                                $history[$ean13] = sprintf('%d/%d (%s:%s)', $id_product, $id_product_attribute, $reference, $ean13);
                            }

                            if ($import_type == Cdiscount::IMPORT_BY_SKU && !empty($reference) && isset($sku_history[$reference])) {
                                $product_errors['duplicate_reference'][] = array(
                                    'reference' => $reference,
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute
                                );
                                continue;
                            }
                            $sku_history[$reference] = sprintf('%d/%d (%s:%s)', $id_product, $id_product_attribute, $reference, $ean13);


                            // This is in the documentation (name shortened to 30 & 50 chars)
                            $var = explode('|', wordwrap($name_short, 30, '|'));
                            $truncated_name_short = $var[0];
                            if (Tools::strlen($truncated_name_short) >= 30) {
                                $truncated_name_short = Tools::substr($truncated_name_short, 0, 30);
                            }

                            $var = explode('|', wordwrap($name_long, 500, '|'));
                            $truncated_name_long = $var[0];
                            if (Tools::strlen($truncated_name_long) >= 500) {
                                $truncated_name_long = Tools::substr($truncated_name_long, 0, 500);
                            }

                            // Dimensions
                            if (version_compare(_PS_VERSION_, '1.4', '>=')) {
                                $dimensions = array();
                                $dimensions[self::DIM_WIDTH] = null;
                                $dimensions[self::DIM_HEIGHT] = null;
                                $dimensions[self::DIM_DEPTH] = null;

                                if (isset($details->width) && (float)$details->width) {
                                    $dimensions[self::DIM_WIDTH] = (float)$details->width;
                                }
                                if (isset($details->height) && (float)$details->height) {
                                    $dimensions[self::DIM_HEIGHT] = (float)$details->height;
                                }
                                if (isset($details->depth) && (float)$details->depth) {
                                    $dimensions[self::DIM_DEPTH] = (float)$details->depth;
                                }
                            } else {
                                $dimensions = null;
                            } // no matching for PS < 1.4

                            // Descriptions
                            if ($decription_field == Cdiscount::FIELD_DESCRIPTION_LONG) {
                                $decription_field_name = 'description';
                            } else {
                                $decription_field_name = 'description_short';
                            }

                            $description = self::description($details->{$decription_field_name}[$id_lang]);

                            // Description Longue

                            if ($long_description_field == Cdiscount::FIELD_DESCRIPTION_LONG) {
                                $decription_field_name = 'description';
                            } else {
                                $decription_field_name = 'description_short';
                            }

                            if (Tools::strlen($details->{$decription_field_name}[$id_lang])) {
                                $description_long_raw = $details->{$decription_field_name}[$id_lang];
                                $description_long = Tools::substr(parent::encode($description_long_raw), 0, 5000);
                            } else {
                                $description_long_raw = $description_long = null;
                            }

                            if (method_exists('Product', 'externalDescription')) {
                                $description = Product::externalDescription($id_lang, $id_product);
                                $description_long_raw = Product::externalDescription($id_lang, $id_product, true);
                                $description_long = Tools::substr(parent::encode($description_long_raw), 0, 5000);
                            }

                            if ($this->_debug) {
                                CommonTools::p(sprintf('Description External: %s', method_exists('Product', 'externalDescription') ? 'Yes' : 'No'));
                                CommonTools::p(sprintf('Description Field: %s', $decription_field_name));
                                CommonTools::p(sprintf('Description: %s', Tools::substr($description, 0, 32)));
                                CommonTools::p(sprintf('Description Long: %s', Tools::substr($description_long, 0, 32)));
                                CommonTools::p(sprintf('Description Long Raw: %s', Tools::substr($description_long_raw, 0, 32)));
                            }

                            // Images
                            $images = array();

                            foreach (CDiscountTools::getProductImages($details->id, $id_product_attribute, $id_lang, $this->context) as $image) {
                                $file_image = _PS_PROD_IMG_DIR_.$image;

                                if (!file_exists($file_image)) {
                                    $this->errors[] = sprintf('%s(#%d):'.$this->l('Unable to find image %s in %s').' - "%s"', basename(__FILE__), __LINE__, $image, _PS_PROD_IMG_DIR_, $group_detail['reference']);
                                    continue;
                                }
                                if (!$this->checkImage($file_image)) {
                                    continue;
                                }

                                $images[] = $this->ps_images.$image;
                            }

                            if ($isVariant && !count($images)) {
                                foreach (CDiscountTools::getProductImages($details->id, null, $id_lang, $this->context) as $image) {
                                    $file_image = _PS_PROD_IMG_DIR_.$image;

                                    if (!file_exists($file_image)) {
                                        $this->errors[] = sprintf('%s(#%d):'.$this->l('Unable to find image %s in %s').' - "%s"', basename(__FILE__), __LINE__, $image, _PS_PROD_IMG_DIR_, $group_detail['reference']);
                                        continue;
                                    }
                                    if (!$this->checkImage($file_image)) {
                                        if ($this->_debug) {
                                            CommonTools::p("Image Check failed:".print_r($file_image, true));
                                        }
                                        continue;
                                    }
                                    $images[] = $this->ps_images.$image;
                                }
                            }

                            if (!count($images)) {
                                $product_errors['missing_image'][] = array(
                                    'reference' => $group_detail['reference'],
                                    'id_product' => $id_product,
                                    'id_product_attribute' => $id_product_attribute
                                );
                                continue;
                            }

                            $image = $image2 = $image3 = $image4 = '';

                            if (isset($images[0])) {
                                $image = $images[0];
                            }
                            if (isset($images[1])) {
                                $image2 = $images[1];
                            }
                            if (isset($images[2])) {
                                $image3 = $images[2];
                            }
                            if (isset($images[3])) {
                                $image4 = $images[3];
                            }

                            if ($images) {
                                if ($this->_debug) {
                                    CommonTools::p(sprintf('Products Images: %s', print_r($images, true)));
                                }
                            }

                            /**
                             * MAIN FIELDS :
                             * 'SKU', 'EAN', 'Brand', 'Parentage', 'Type', 'NameShort', 'NameLong', 'Description', 'Image1',
                             * 'Size', 'ParentSKU', 'MarketingColor'
                             * 'MarketingDescription', 'Image2', 'Image3', 'Image4', 'Classification', 'ISBN', 'MFPN', 'Lenght', 'Width', 'Height', 'Weight'
                             * 'Parts', 'MainColor'
                             */
                            $data = array();
                            $data['EAN'] = '';

                            if ((int)$ean13 && ($ean_policy == Cdiscount::EAN_POLICY_NORMAL || $ean_policy == Cdiscount::EAN_POLICY_PERMISSIVE)) {
                                $data['EAN'] = trim($ean13);
                            }

                            if (empty($manufacturer_name)) {
                                $data['BrandName'] = 'No Name';
                            } else {
                                $data['BrandName'] = self::cleanup($manufacturer_name);
                            }

                            $data['SellerProductId'] = trim(str_replace('&', '&amp;', $sku));

                            $data['ProductKind'] = $isVariant ? 'Variant' : 'Standard';
                            $data['CategoryCode'] = trim($model_category);

                            if ($isVariant) {
                                $data['SellerProductFamily'] = $parent_id;
                                $data['Size'] = null; // initializing
                                $data['SellerProductColorName'] = null;  // initializing
                            } else {
                                unset($data['Size']);
                                unset($data['SellerProductColorName']);
                            }

                            $data['ShortLabel'] = self::cleanup($truncated_name_short);
                            $data['LongLabel'] = self::cleanup($truncated_name_long);

                            if (!empty($description) && Tools::strlen($description)) {
                                // 2021-04-23: CDiscount limit the description to 420 (ticket 93103, 95000)
                                $description = Tools::substr($description, 0, 420);
                                $description = preg_replace('/\&(?!amp;)/', '', $description);
                                $data['Description'] = $description;
                            }

                            if (!empty($description_long) && $send_marketing_description) {
                                if (strstr($description_long_raw, 'http://') !== false || strstr($description_long_raw, 'https://') !== false) {
                                    $product_errors['url_in_description'][] = array(
                                        'reference' => $reference,
                                        'id_product' => $id_product,
                                        'id_product_attribute' => $id_product_attribute
                                    );
                                    if ($this->_debug) {
                                        CommonTools::p("Product has URL in description:".print_r($reference, true));
                                    }
                                } else {
                                    $data['EncodedMarketingDescription'] = $description_long;
                                }
                            }

                            if ($isVariant) {
                                if (!$size_field_value || !$color_field_value) {
                                    $product_errors['missing_sizecolor'][] = array(
                                        'reference' => $group_detail['reference'],
                                        'id_product' => $id_product,
                                        'id_product_attribute' => $id_product_attribute
                                    );
                                    if ($this->_debug) {
                                        CommonTools::p("Missing Size/Color Field:".print_r($group_detail['reference'], true));
                                    }
                                    continue;
                                }

                                $data['Size'] = self::cleanup($size_field_value);
                                $data['SellerProductColorName'] = self::cleanup($color_field_value);
                            }

                            if ($export_type == self::XML) {
                                //XML
                                $data['Model'] = self::cleanup($marketplace_model_name);
                            } else {
                                //CSV
                                $data['Model'] = self::cleanup($model_name);
                            }

                            $data['Image1'] = $image;
                            $data['Image2'] = $image2;
                            $data['Image3'] = $image3;
                            $data['Image4'] = $image4;

                            $data['Navigation'] = str_replace('&', '&amp;', sprintf('%s / %s', $selectedModel->universe, $cdCatsOfMdUniverse[$model_category]));

                            if ($this->_debug) {
                                CommonTools::p(sprintf('Navigation: %s', print_r($data['Navigation'], true)));
                            }

                            if ($dimensions[self::DIM_DEPTH]) {
                                $data['Length'] = $dimensions[self::DIM_DEPTH];
                            }
                            if ($dimensions[self::DIM_WIDTH]) {
                                $data['Width'] = $dimensions[self::DIM_WIDTH];
                            }
                            if ($dimensions[self::DIM_HEIGHT]) {
                                $data['Height'] = $dimensions[self::DIM_HEIGHT];
                            }

                            if ((float)$weight > 0) {
                                if ($export_type == self::XML) {
                                    if (in_array(Tools::strtoupper($weight_unit), array('KG', 'K', 'KG.'))) {
                                    } elseif (in_array(Tools::strtoupper($weight_unit), array('GR', 'G', 'GR.'))) {
                                        $data['Weight'] = (int)$weight / 100;
                                    } else {
                                        $data['Weight'] = 1;
                                    }
                                } else {
                                    $data['Weight'] = $weight;
                                }
                            }
                            // Consistency Check
                            //
                            $pass = true;
                            foreach (CDiscountSpecificField::$genericRequired as $required) {
                                if ($required == 'EAN' && $ean_policy == Cdiscount::EAN_POLICY_EXEMPT || $ean_policy == Cdiscount::EAN_POLICY_PERMISSIVE) {
                                    continue;
                                }

                                if (!isset($data[$required]) || empty($data[$required])) {
                                    if ($required == 'EAN') {
                                        $product_errors['missing_ean'][] = array(
                                            'reference' => $reference,
                                            'id_product' => $id_product,
                                            'id_product_attribute' => $id_product_attribute
                                        );
                                        $pass = false;
                                        continue;
                                    } else {
                                        $this->errors[] = sprintf('%s(#%d): '.$this->l('Inconsistency for product %s(%d/%d) - Missing %s for Product'), basename(__FILE__), __LINE__, $group_detail['reference'], $id_product, $id_product_attribute, $required);
                                        $pass = false;
                                    }
                                }
                            }
                            if (!$pass) {
                                if ($this->_debug) {
                                    CommonTools::p("Didn't pass EAN Check:".print_r($group_detail['reference'], true));
                                }
                                continue;
                            }

                            // Specific Template Mapping
                            if (isset(CDiscountSpecificField::$csvConfig[$model_id])) {
                                //
                                $xmlTemplate = CDiscountSpecificField::$csvConfig[$model_id];
                            } else {
                                if ($export_type == self::XML) {
                                    $xmlTemplate = CDiscountSpecificField::$csvConfig[CDiscountSpecificField::MODEL_XML];
                                } else {
                                    $xmlTemplate = CDiscountSpecificField::$csvConfig[CDiscountSpecificField::MODEL_CSV];
                                }
                            }

                            $csvConfig = array_flip($xmlTemplate);
                            // XML Output
                            $CSV = array();

                            if (Tools::strtolower($export_type) == self::CSV) {
                                // XML Output
                                $CSV = $csvConfig;

                                foreach ($CSV as $idx_csv => $csv_element) {
                                    $CSV[$idx_csv] = '';
                                }

                                $headers = false;
                                if ($includeModelHeader) {
                                    $includeModelHeader = false;
                                    $headers = 'Model Name: ' . ($selectedModel->modelName ? $selectedModel->modelName : $currentModel);
                                }
                            }
                            // Sorting Fields according to the Model
                            foreach ($csvConfig as $col => $index) {
                                if (isset($data[$col])) {
                                    if ($col == 'EAN') {
                                        if ($export_type == self::XML) {
                                            $CSV['EanList'][$col] = ($ean_policy != Cdiscount::EAN_POLICY_EXEMPT) ? $data[$col] : '';
                                        } else {
                                            $CSV[$col] = ($ean_policy != Cdiscount::EAN_POLICY_EXEMPT) ? $data[$col] : '';
                                        }
                                    } elseif (Tools::substr($col, 0, 5) == 'Image') {
                                        if ($export_type == self::XML) {
                                            $CSV['Pictures'][$col] = $data[$col];
                                        } else {
                                            $CSV[$col] = $data[$col];
                                        }
                                    } elseif (in_array($col, CDiscountSpecificField::$genericRequired)) {
                                        $CSV[$col] = $data[$col];
                                    } elseif ($isVariant && in_array($col, CDiscountSpecificField::$variantRequired)) {
                                        $CSV[$col] = $data[$col];
                                    } elseif (is_string($data[$col]) or is_numeric($data[$col])) {
                                        $CSV[$col] = $data[$col];
                                    }
                                } elseif (in_array($col, CDiscountSpecificField::$genericRequired) || ($isVariant && in_array($col, CDiscountSpecificField::$variantRequired))) {
                                    $CSV[$col] = '';
                                }
                            }


                            if ($has_model_color && $color_field_value) {
                                if ($export_type == self::XML) {
                                    $CSV['ModelProperties'][$fieldset['name']] = self::cleanup($color_field_value);
                                } else {
                                    $CSV[$fieldset['name']] = self::cleanup($color_field_value);
                                }
                            }

                            if ($has_model_gender && $export_type == self::CSV) {
                                $CSV[$fieldset['name']] = $gender;
                            }

                            if ($has_model_public && $export_type == self::CSV) {
                                $CSV[$fieldset['name']] = $public;
                            }

                            if (is_array($model_features) && count($model_features)) {
                                foreach ($model_features as $model_feature_name => $model_feature_value) {
                                    if (!Tools::strlen($model_feature_value)) {
                                        continue;
                                    }

                                    if ($export_type == self::XML) {
                                        $CSV['ModelProperties'][$model_feature_name] = htmlentities($model_feature_value, null, 'UTF-8');
                                    } else {
                                        $CSV[$model_feature_name] = htmlentities($model_feature_value, null, 'UTF-8');
                                    }
                                }
                            }

                            $count++;

                            if ($this->_debug) {
                                CommonTools::p(sprintf("Exporting Product: %d id: %d name: %s", $idx, $details->id, $name_short));
                                CommonTools::p($CSV);
                            }
                            if ($export_type == self::XML) {
                                if ($gender) {
                                    $CSV['ModelProperties']['Genre'] = $gender;
                                }

                                if ($public) {
                                    $CSV['ModelProperties']['Type de public'] = $public;
                                }

                                if ($isVariant && !isset($CSV['SellerProductColorName'])) {
                                    $CSV['SellerProductColorName'] = null;
                                }

                                if (($XML = $this->csvProductToXml($CSV))) {
                                    $productsCollectionToExport[] = $XML;
                                }
                            } else {/* CSV */
                                $index_model = CDiscountTools::getFriendlyUrl($CSV['Model']);
                                if (!isset($productsCollectionToExport[$index_model])) {
                                    $productsCollectionToExport[$index_model] = array();
                                }

                                if ($headers !== false) {
                                    $productsCollectionToExport[$index_model][] = $headers;
                                }

                                unset($CSV['Model']);
                                $productsCollectionToExport[$index_model][] = $CSV;
                                $headers = false;
                            }
                        } # endif foreach combinations/group
                    } # endif foreach products
                } # end if products
            } # endif foreach categories

            $ProductCollectionCapacity = count($productsCollectionToExport);
            $xmlHeader_collect = str_replace('Capacity="0"', 'Capacity="'.$ProductCollectionCapacity.'"', $exportedFileHeader);

            $files = array();

            if ($export_type == self::XML) {
                $fileName = 'Products.xml';
                $fileout = $this->export.$this->xml_dir.'/Content/'.$fileName;

                if (!($handle = fopen($fileout, 'a'))) {
                    $this->errors[] = sprintf('%s(#%d): %s - %s', basename(__FILE__), __LINE__, $this->l('Unable to open output file for writing'));
                    $error = true;
                }

                if ($this->_debug) {
                    CommonTools::p(sprintf("fileName: %s", print_r($fileout, true)));
                }

                if ($handle) {
                    if (file_put_contents($fileout, $xmlHeader_collect) === false) {
                        $this->errors[] = sprintf('%s(#%d): %s - %s', basename(__FILE__), __LINE__, $this->l('Unable to open output file for writing'));
                        $error = true;
                    }

                    foreach ($productsCollectionToExport as $exported_product) {
                        if (fwrite($handle, $exported_product) === false) {
                            $this->errors[] = sprintf('%s(#%d): %s - %s', basename(__FILE__), __LINE__, $this->l('Unable to open output file for writing'));
                            $error = true;
                            continue;
                        }
                    }

                    if (fwrite($handle, $xmlFooter) === false) {
                        $this->errors[] = sprintf('%s(#%d): %s - %s', basename(__FILE__), __LINE__, $this->l('Unable to open output file for writing'));
                        $error = true;
                        exit;
                    }
                    fclose($handle);
                }
            } else { //EXPORT AS CSV
                foreach ($productsCollectionToExport as $model_file => $model_data) {
                    //sanitize filename
                    $fileName = $model_file.'.csv';
                    $fileout = $this->export.'csv/'.$fileName;
                    $files[] = array($fileName, $this->export_csv.$fileName);

                    if (!isset($handles[$fileout])) {
                        $handles[$fileout] = null;

                        if (file_exists($fileout) && !is_writable($fileout)) {
                            $this->errors[] = sprintf('%s(#%d): %s - %s', basename(__FILE__), __LINE__, $this->l('Unable to open output file for writing'), $fileout);
                            $error = true;
                            continue;
                        }
                        if (!($handle = fopen($fileout, 'w'))) {
                            $this->errors[] = sprintf('%s(#%d): %s - %s', basename(__FILE__), __LINE__, $this->l('Unable to open output file for writing'), $fileout);
                            $error = true;
                            continue;
                        }
                        $handles[$fileout] = $handle;
                    } else {
                        $handle = $handles[$fileout];
                    }

                    if ($this->_debug) {
                        CommonTools::p(sprintf("fileName: %s", print_r($fileout, true)));
                    }

                    if ($handle) {
                        foreach ($model_data as $idx_coll => $exported_product) {
                            $value = $exported_product;
                            if (!is_array($exported_product)) {
                                $value_arr = array();
                                fputcsv($handle, array(mb_convert_encoding($exported_product, 'ISO-8859-1')), ';');
                                foreach ($model_data[$idx_coll + 1] as $header_key => $value_el) {
                                    $value_arr[] = mb_convert_encoding(html_entity_decode($header_key, ENT_QUOTES, 'UTF-8'), 'ISO-8859-1');
                                }

                                fputcsv($handle, $value_arr, ';');
                            } else {
                                if (is_array($value) && count($value)) {
                                    foreach ($value as $key => $item) {
                                        $value[$key] = mb_convert_encoding(html_entity_decode($item, ENT_QUOTES, 'UTF-8'), 'ISO-8859-1');
                                    }
                                }

                                fputcsv($handle, $value, ';');
                            }
                        }
                    }//handle
                }//files loop
            }

            if (is_array($product_errors)) {
                foreach (array(
                             'empty_reference',
                             'duplicate_ean',
                             'duplicate_reference',
                             'wrong_ean',
                             'missing_ean',
                             'missing_sizecolor',
                             'invalid_reference',
                             'existing_product',
                             'missing_image'
                         ) as $error_type) {
                    if (isset($product_errors[$error_type]) && is_array($product_errors[$error_type]) && count($product_errors[$error_type])) {
                        $msg = null;
                        foreach ($product_errors[$error_type] as $product_error) {
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

                                case 'missing_sizecolor':
                                    if ($msg == null) {
                                        $msg = $this->l('Size/Color is missing, References').': [';
                                    }

                                    $msg .= sprintf('%s, ', $product_error['reference'] ? $product_error['reference'] : '#'.$product_error['id_product']);
                                    break;

                                case 'missing_image':
                                    if ($msg == null) {
                                        $msg = $this->l('Missing images, images are mandatory, product ignored').': [';
                                    }

                                    $msg .= sprintf('%s, ', $product_error['reference'] ? $product_error['reference'] : '#'.$product_error['id_product']);
                                    break;


                                case 'url_in_description':
                                    if ($msg == null) {
                                        $msg = $this->l('Marketing description contains an URL, that is not allowed by CDiscount');
                                    }

                                    $msg .= sprintf('%s, ', $product_error['reference'] ? $product_error['reference'] : '#'.$product_error['id_product']);
                                    break;

                                case 'existing_product':
                                    if ($msg == null) {
                                        $msg = $this->l('Product is already existing in your CDiscount inventory').': [';
                                    }

                                    $msg .= sprintf('%s, ', $product_error['reference'] ? $product_error['reference'] : '#'.$product_error['id_product']);
                                    break;
                            }
                        }

                        if ($msg) {
                            $msg = rtrim($msg, ', ').']';

                            switch ($error_type) {
                                case 'empty_reference':
                                    $msg = $CDiscountSupport->message($msg, CDiscountSupport::FUNCTION_EXPORT_EMPTY_REFERENCE);
                                    break;
                                case 'duplicate_reference':
                                    $msg = $CDiscountSupport->message($msg, CDiscountSupport::FUNCTION_EXPORT_DUPLICATE);
                                    break;
                                case 'wrong_ean':
                                    $msg = $CDiscountSupport->message($msg, CDiscountSupport::FUNCTION_EXPORT_WRONG_EAN);
                                    break;
                                case 'missing_ean':
                                    $msg = $CDiscountSupport->message($msg, CDiscountSupport::FUNCTION_EXPORT_MISSING_EAN);
                                    break;
                                case 'duplicate_ean':
                                    $msg = $CDiscountSupport->message($msg, CDiscountSupport::FUNCTION_EXPORT_DUPLICATE_EAN);
                                    break;
                                default:
                                    $msg .= '<br /><br />';
                                    break;
                            }
                            $this->errors[] = $msg;
                        }
                    }
                }
                $msg = null;
            }

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
                                    $msg = $this->l('Products Filters - Supplier filter summary').':<br /> ';
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
                    $this->errors[] = $msg;
                }
            }


            if (is_array($handles) && count($handles)) {
                foreach ($handles as $fn => $handle) {
                    fclose($handle);
                }
            }

            // ZIP
            //
            $from = array();
            $from[] = $rel_file;
            $from[] = $products_file;
            $from[] = $type_file;

            if ($export_type == self::XML) {
                if ($this->_debug) {
                    echo 'from: '.nl2br(print_r($from, true)).$cr;
                }

                if ($this->createZip($this->zipfile, str_replace($output_dir.'/', '', $from)) < 0) {
                    $this->errors[] = sprintf($this->l('createZip() failed...')).$cr;
                    $error = true;
                }
                $finalFile = $this->pickup_url.$this->zipfile;
            } else {
                $finalFile = $files;
            }
        } // endif error
        // Export Output
        $output = ob_get_clean();

        if (!$error && !count($this->errors) && $count) {
            //
            $msg = $this->l('Products Successfully Exported');
        } else {
            if ($count > 0) {
                $msg = $this->l('Some Products Successfully Exported');
                $this->errors[] = sprintf('%s(#%d): %s', basename(__FILE__), __LINE__, $this->l('An error occured while exporting the products, but a part of them were exported'));
                $error = true;
            } else {
                $msg = $this->l('Nothing has been exported');
                $this->errors[] = sprintf('%s(#%d): %s', basename(__FILE__), __LINE__, $this->l('An error occured while exporting the products'));
                $error = true;
                $finalFile = null;
            }
        }

        if ($count) {
            Configuration::updateValue(parent::KEY.'_LAST_EXPORT', parent::encode(serialize(date('Y-m-d H:i:s'))));
        }

        $json = Tools::jsonEncode(array(
            'error' => $error,
            'errors' => $this->errors,
            'msg' => $msg,
            'output' => $output,
            'file' => $finalFile,
            'count' => $count
        ));

        $callback = Tools::getValue('callback');

        if ($callback) {   // jquery
            if ($callback == '?') {
                $callback = 'jsonp_'.time();
            }

            echo (string)$callback.'('.$json.')';
        } else {
            // cron
            return ($json);
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
        $Relationship->setAttribute('Target', '/Content/Products.xml');
        $Relationship->setAttribute('Id', 'RId1'.uniqid());

        return ($this->saveXML($file, $Document));
    }

    private function saveXML($file, $Document)
    {
        $cr = $this->_cr;

        $current_wd = str_replace('\\', '/', getcwd());
        $output_dir = $this->export;

        if (!chdir($dir = realpath($output_dir.$this->xml_dir))) {
            $this->errors[] = sprintf('%s(%s): '.$this->l('Failed to change directory: %s'), $dir).$cr;

            return (false);
        }

        if (!$content = $Document->saveXML()) {
            $this->errors[] = sprintf('%s(%s): %s', basename(__FILE__), __LINE__, $this->l('Unable to create XML')).$cr;

            return (false);
        }

        // Windows Format
        //$content = str_replace(self::LF, self::CRLF, $content) ;

        if (file_put_contents($file, $content) === false) {
            $this->errors[] = sprintf('%s(%s): %s', basename(__FILE__), __LINE__, $this->l('Unable to write to the XML file')).$cr;

            return (false);
        }

        chdir($current_wd);

        if ($this->_debug) {
            CommonTools::p($content);
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

        $Default = $Types->appendChild($Document->createElement('Default'));
        $Default->setAttribute('Extension', 'png');
        $Default->setAttribute('ContentType', 'image/jpeg');

        return ($this->saveXML($file, $Document));
    }

    private function setFeatureMapping($id_lang)
    {
        $features_mapping_left = unserialize(parent::decode(Configuration::get(parent::KEY.'_FEATURES_MAPPING_L')));
        $features_mapping_right = unserialize(parent::decode(Configuration::get(parent::KEY.'_FEATURES_MAPPING_R')));

        $features = Feature::getFeatures($id_lang);
        $features_mapping = array();

        if (is_array($features) && count($features)) {
            //Look for product features
            foreach ($features as $fp) {
                $id_feature = $fp['id_feature'];

                //Get values for options of feature
                $feature_values = FeatureValue::getFeatureValuesWithLang($id_lang, $id_feature);

                if (!is_array($feature_values) || !count($feature_values)) {
                    continue;
                }

                foreach ($feature_values as $fv) {
                    $id_feature_value = (int)$fv['id_feature_value'];

                    if (isset($features_mapping[$id_feature][$id_feature_value][$id_lang])) {
                        continue;
                    }

                    if (isset($features_mapping_right[$id_feature]) && isset($features_mapping_right[$id_feature][$id_feature_value]) && !empty($features_mapping_right[$id_feature][$id_feature_value])) {
                        if (!isset($features_mapping[$id_feature])) {
                            $features_mapping[$id_feature] = array();
                        }
                        if (!isset($features_mapping[$id_feature][$id_feature_value])) {
                            $features_mapping[$id_feature][$id_feature_value] = array();
                        }

                        $features_mapping[$id_feature][$id_feature_value][$id_lang] = $features_mapping_right[$id_feature][$id_feature_value];
                    }
                }
            }
        }

        return self::$features_mapping = $features_mapping;
    }

    public static function description($html)
    {
        $text = $html;

        $text = self::stripInvalidXml($text);

        $text = str_replace('</p>', "\n</p>", $text);
        $text = str_replace('</li>', "\n</li>", $text);
        $text = str_replace('<br', "\n<br", $text);

        $text = strip_tags($text);

        #$text = iconv("UTF-8", "UTF-8//TRANSLIT", $text);
        $text = str_replace('&#39;', "'", $text);
        $text = str_replace('"', "'", $text);

        $text = mb_convert_encoding($text, 'HTML-ENTITIES');
        $text = str_replace('&nbsp;', ' ', $text);
        $text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
        $text = str_replace('&', '&amp;', $text);

        $text = preg_replace('#\s+[\n|\r]+$#i', '', $text); // empty
        $text = preg_replace('#[\n|\r]+#i', "\n", $text); // multiple-return
        $text = preg_replace('#\n+#i', "\n", $text); // multiple-return
        $text = preg_replace('#^[\n\r\s]#i', '', $text);

        $text = preg_replace('/[\x{0001}-\x{0009}]/u', '', $text);
        $text = preg_replace('/[\x{000b}-\x{001f}]/u', '', $text);
        $text = preg_replace('/[\x{0080}-\x{009F}]/u', '', $text);
        $text = preg_replace('/[\x{0600}-\x{FFFF}]/u', '', $text);

        $text = preg_replace('/\x{000a}/', "\n", $text);

        return (trim($text));
    }

    public static function stripInvalidXml($value)
    {
        $ret = '';
        if (empty($value)) {
            return $ret;
        }

        $length = strlen($value); //TODO: Multibyte dance - DO NOT REPLACE BY Tools::strlen
        for ($i = 0; $i < $length; $i++) {
            $current = ord($value{$i});
            if (($current == 0x9) ||
                ($current == 0xA) ||
                ($current == 0xD) ||
                (($current >= 0x20) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                (($current >= 0x10000) && ($current <= 0x10FFFF))
            ) {
                $ret .= chr($current);
            } else {
                $ret .= ' ';
            }
        }

        return $ret;
    }

    public static function cleanup($text)
    {
        $text = self::stripInvalidXml($text);

        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');

        $text = iconv('UTF-8', 'UTF-8//TRANSLIT', $text);
        $text = str_replace('&#39;', "'", $text);
        $text = str_replace('"', "'", $text);
        $text = str_replace('&', '&amp;', $text);

        return (trim($text));
    }

    public function csvProductToXml($CSV)
    {
        $P = '';
        $I = '';
        $E = '';
        $M = '';

        while (list($k, $v) = each($CSV)) {
            if (is_array($v)) {
                while (list($k2, $v2) = each($v)) {
                    if ($v2 != '' || $k == 'EanList') {
                        switch ($k) {
                            case 'Pictures':
                                $I .= ($I == '' ? "\t\t\t<Product.Pictures>\n" : '')."\t\t\t\t<ProductImage Uri=\"".self::xmlEncode($v2)."\"/>\n";
                                break;
                            case 'EanList':
                                $E .= ($E == '' ? "\t\t\t<Product.EanList>\n" : '')."\t\t\t\t<ProductEan Ean=\"".self::xmlEncode($v2)."\"/>\n";
                                break;
                            case 'ModelProperties':
                                $M .= ($M == '' ? "\t\t\t<Product.ModelProperties>\n" : '')."\t\t\t\t<x:String x:Key=\"".str_replace('&', '&amp;', html_entity_decode(self::xmlEncode($k2), ENT_QUOTES, 'UTF-8')).'">'.html_entity_decode(self::xmlEncode($v2), ENT_QUOTES, 'UTF-8')."</x:String>\n";
                                break;
                        }
                    }
                }
            } else {
                $P .= ($P == '' ? "\t\t<Product" : '').' '.self::xmlEncode($k).'="'.($v == '' ? '{x:Null}' : self::xmlEncode($v)).'"';
            }
        }

        $P .= ($P == '' ? '' : ">\n");
        $E .= ($E == '' ? '' : "\t\t\t</Product.EanList>\n");
        $M .= ($M == '' ? '' : "\t\t\t</Product.ModelProperties>\n");
        $I .= ($I == '' ? '' : "\t\t\t</Product.Pictures>\n");
        $XML = $P.$E.$M.$I;
        $XML .= ($XML == '' ? '' : "\t\t</Product>\n");

        return $XML;
    }

    public static function xmlEncode($s)
    {
        $s = str_replace('"', '&quot;', $s);
        $s = str_replace('<', '&lt;', $s);
        $s = str_replace('>', '&gt;', $s);

        return $s;
    }

    /* http://www.php.net/manual/en/class.ziparchive.php */

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

        if ($this->_debug) {
            CommonTools::p($zipfile);
            CommonTools::p($from);
        }

        $cdiscountZip = new CdiscountZip();
        $result = $cdiscountZip->createZip($zipfile, $from);

        chdir($current_wd);

        return($result);
    }

    private function checkImage($image_path)
    {
        $pathinfo = pathinfo($image_path);

        if (is_array($pathinfo) && array_key_exists('extension', $pathinfo) && Tools::strlen($pathinfo['extension'])) {
            switch (Tools::strtolower($pathinfo['extension'])) {
                case 'png':
                case 'jpeg':
                case 'jpg':
                case 'gif':
                case 'png':
                    break;
                default:
                    $this->errors[] = sprintf('%s(#%d):'.$this->l('Invalid extension "%s" for image "%s"'), basename(__FILE__), __LINE__, $pathinfo['extension'], basename($image_path));
                    return(false);
                    break;
            }
        }
        return(true);
    }

    private function lastExport()
    {
        $date = unserialize(parent::decode(Configuration::get(parent::KEY.'_LAST_EXPORT')));

        if (!$date) {
            return;
        }

        $smarty_data = array();
        $smarty_data['id_lang'] = $this->id_lang;
        $smarty_data['zipfile'] = $this->zipfile;
        $smarty_data['zip_url'] = $this->zip_url;
        $smarty_data['images'] = $this->images;
        $smarty_data['date'] = $date;

        $this->context->smarty->assign($smarty_data);

        $html = $this->context->smarty->fetch($this->path.'views/templates/admin/catalog/products_create_result.tpl');

        die($html);
    }

    private function getReport()
    {
        $cr = $this->_cr;

        $packageId = Tools::getValue('id');
        $callback = Tools::getValue('callback');

        printf('Report: %s', $packageId);

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

        if (!($result = $webservice->GetProductPackageSubmissionResult($params))) {
            $this->errors[] = sprintf($this->l('SubmitProductPackage failed...')).$cr;
            $error = true;
        }

        printf('#'.__LINE__."%s: %s<br>\n", $this->l('Exporting Package Id'), nl2br(print_r($result, true))).$cr;

        $msg = '';
        $json = Tools::jsonEncode(array('msg' => $msg));

        echo (string)$callback.'('.$json.')';
        die;
    }

    private function productsSend()
    {
        $username = Configuration::get(parent::KEY.'_USERNAME');
        $password = Configuration::get(parent::KEY.'_PASSWORD');
        $production = !(Configuration::get(parent::KEY.'_PREPRODUCTION') ? true : false);

        $webservice = new CDiscountWebservice($username, $password, $production, $this->_debug, $this->dev_mode);
        $webservice->token = CDiscountTools::auth();

        if (!$webservice->token) {
            $this->debugDetails->productExport('Auth failed');
            $this->errors[] = sprintf('%s(#%d): %s', basename(__FILE__), __LINE__, $this->l('Auth failed'));
        }

        $params = array('FILE' => sprintf('%s%s', $this->pickup_url, $this->zipfile));
        $this->debugDetails->productExport('Export params', print_r($params, true));

        if (!($packageId = $webservice->SubmitProductPackage($params))) {
            $this->debugDetails->productExport('SubmitProductPackage failed...');
        }

        if ($packageId > 0) {
            die(json_encode(array(
                'success' => true,
                'data' => sprintf('<div class="conf">%s(#%d): %s: %d</div>', basename(__FILE__), __LINE__, $this->l('Exporting Package Id'), $packageId),
                'debug' => implode('', $this->debugDetails->getAll()),
            )));
        } else {
            die(json_encode(array(
                'success' => false,
                'data' => sprintf(
                    '<div class="error">%s(#%d): %s: %s<br />%s</div>',
                    basename(__FILE__),
                    __LINE__,
                    $this->l('Exporting Package Id'),
                    $this->l('Failed'),
                    $this->l('SubmitProductPackage failed...')
                ),
                'debug' => implode('', $this->debugDetails->getAll()),
            )));
        }
    }

    protected function pd($message, $debugModeOnly = false)
    {
        if (!$debugModeOnly || $this->debug) {
            CommonTools::p($message);
        }
    }

    protected function pdd($message, $line, $debugModeOnly = false, $print = true)
    {
        if (!$debugModeOnly || $this->debug) {
            $output = sprintf("%s(#%d): $message", basename(__FILE__), $line);
            if ($print) {
                CommonTools::p($output);
            } else {
                return $output;
            }
        }
    }

    public function sdE()
    {
        $args = func_get_args();
        $backTrace = debug_backtrace();
        $caller = array_shift($backTrace);
        $fileSegment = explode(DIRECTORY_SEPARATOR, $caller['file']);
        $file = array_pop($fileSegment);
        $output = array();

        foreach ($args as $arg) {
            $output[] = sprintf('%s(#%d): %s', $file, $caller['line'], $arg);
        }

        return implode(nl2br(Cdiscount::LF), $output);
    }
}

$MarketplaceProductsCreate = new CDiscountExportProducts;
$MarketplaceProductsCreate->dispatch();
