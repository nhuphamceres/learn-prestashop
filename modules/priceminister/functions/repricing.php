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
 * ...........................................................................
 *
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.priceminister@common-services.com
 */

if (isset($_SERVER['DropBox']) && $_SERVER['DropBox']) {
    require_once(readlink(dirname($_SERVER['SCRIPT_FILENAME']).'/../../../config/config.inc.php'));
    require_once(readlink(dirname($_SERVER['SCRIPT_FILENAME']).'/../../../init.php'));
} else {
    @require_once(dirname(__FILE__).'/../../../config/config.inc.php');
    @require_once(dirname(__FILE__).'/../../../init.php');
}

require_once dirname(__FILE__).'/../priceminister.php';
require_once dirname(__FILE__).'/../classes/priceminister.context.class.php';
require_once dirname(__FILE__).'/../classes/priceminister.tools.class.php';
require_once dirname(__FILE__).'/../classes/priceminister.repricing.class.php';
require_once dirname(__FILE__).'/../classes/priceminister.profiles.class.php';
require_once dirname(__FILE__).'/../classes/priceminister.api.webservices.php';
require_once dirname(__FILE__).'/../classes/priceminister.api.products.class.php';
require_once dirname(__FILE__).'/../classes/priceminister.product.class.php';
require_once dirname(__FILE__).'/../classes/priceminister.categories.class.php';
require_once dirname(__FILE__).'/../classes/priceminister.prof2categories.class.php';

class PriceMinisterRepricingAutomaton extends PriceMinister
{

    /**
     * PriceMinisterRepricingAutomaton constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        PriceMinisterContext::restore(Context::getContext());

        parent::__construct();
    }

    public function dispatch()
    {
        echo 'Start: '.date('Y/m/d H:i:s').'<br>';

        $token = Tools::getValue('pm_token');

        if ($token !== Configuration::get(PriceMinister::CONFIG_PM_CRON_TOKEN)) {
            die('Wrong token...');
        }

        switch (Tools::getValue('action')) {
            case 'fetch':
                $this->fetch();
                break;

            case 'treat':
                $this->reprice();
                break;

            default:
                die('Wrong action...');
        }

        echo 'Stop: '.date('Y/m/d H:i:s').'<br>';
    }

    public function fetch()
    {
        echo 'Fetching Repricing datas.<br>';
        $config = PriceMinisterTools::Auth();

        if (!is_array($config)) {
            die('Unexpected error, missing configuration...');
        }

        $pm_api_prd = new PriceMinisterApiProducts($config);
        $xml = $pm_api_prd->getProductAdvert();

        if (!$xml instanceof SimpleXMLElement) {
            die('API did not return an XML element...');
        } elseif (!isset($xml->response) || !isset($xml->response->nbresults)) {
            echo 'No response returned or no results available for repricing...';
            var_dump($xml);
            die;
        }

        if (!file_put_contents(dirname(__FILE__).'/../export/repricing.json', Tools::jsonEncode($xml))) {
            die('Error while creating the repricing file on the server...');
        }

        Configuration::updateValue('PM_LAST_REPRICING_FILE', time());

        echo 'Fetching Repricing datas done.<br>';
        echo 'File created with success.<br>';
    }

    public function reprice()
    {
        $sku_items = array();
        $config_parameters = parent::getConfig(PriceMinister::CONFIG_PM_PARAMETERS);
        $config_filters = parent::getConfig(PriceMinister::CONFIG_PM_FILTERS);
        $profiles = PriceMinisterProfiles::getAll();
        $id_categories_checked = PriceMinisterCategories::getAll();
        $profile2category = PriceMinisterProfiles2Categories::getAll();
        $categories = PriceMinisterCategories::getAll();
        $strategies = PriceMinisterRepricing::getAll();

        if (!is_array($profiles) || !count($profiles)) {
            die('You must configure profiles first...');
        } elseif (!is_array($id_categories_checked) || !count($id_categories_checked)) {
            die('You must select categories first...');
        } elseif (!is_array($profile2category) || !count($profile2category)) {
            die('You must select profiles first...');
        }

        // Remove unselected profiles2categories
        foreach ($profile2category as $key => $val) {
            if (!in_array($key, $id_categories_checked)) {
                unset($profile2category[$key]);
            }
        }
        // Re-index Profiles
        foreach ($profiles as $key => $val) {
            unset($profiles[$key]);
            $val['profile_id'] = $key;
            $profiles[$val['name']] = $val;
        }

        $id_shop = $this->context->shop->id;
        $id_warehouse = isset($config_parameters['warehouse']) ? $config_parameters['warehouse'] : null;
        $export_method = $config_parameters['import_method'] == 'ID' ? 'ID' : 'SKU';

        if (!file_exists(dirname(__FILE__).'/../export/repricing.json')) {
            die('No repricing file found...');
        }

        $last_repricing_import = Configuration::get('PM_LAST_REPRICING_FILE');

        if (!$last_repricing_import || time() - $last_repricing_import > 14400) {
            die('The last repricing file is too old, please update your repricing file then try again.');
        }

        try {
            $repricing_datas = Tools::jsonDecode(PriceMinisterTools::file_get_contents(dirname(__FILE__).'/../export/repricing.json'));
        } catch (Exception $e) {
            die('Error while loading repricing file: '.$e->getMessage());
        }

        if (!$repricing_datas instanceof stdClass) {
            die('Error, repricing file was not loaded properly...');
        }

        $merchant_login = PriceMinisterTools::unSerialize(Configuration::get(PriceMinister::CONFIG_PM_CREDENTIALS));

        if ((string)$repricing_datas->response->nexttoken) {
            Configuration::updateValue('PM_RPR_NEXT_TOKEN', (string)$repricing_datas->response->nexttoken);
        }

        foreach ($repricing_datas->response->advertlist->advert as $adv) {
            $reference = isset($adv->sku) ? trim((string)$adv->sku) : 0;

            if ($export_method == 'SKU') {
                $product_item = PriceMinisterProductExt::getBySKU($reference, $this->context->shop->id);

                if (!$product_item) {
                    echo sprintf('Unable to find this product, reference(sku): %s...<br>', $reference);
                    continue;
                }

                $id_product = (int)$product_item->id_product;
                $id_product_attribute = (int)$product_item->id_product_attribute;
            } elseif ($export_method == 'ID') {
                if (strpos($reference, '_')) {
                    $split_combination = explode('_', $reference);
                    $id_product = (int)$split_combination[0];
                    $id_product_attribute = (int)$split_combination[1];
                } else {
                    $id_product_attribute = false;
                    $id_product = (int)$reference;
                }
            } else {
                $split_combination = explode('c', $reference);

                $id_product = (int)Tools::substr($split_combination[0], 1); // Remove the 'p'
                $id_product_attribute = (int)$split_combination[1]; // get the c (removed by explode)
            }

            $product = new Product($id_product);
            if (!Validate::isLoadedObject($product)) {
                echo sprintf('Unable to load Product(#%d)...<br>', $id_product);
                continue;
            } else {
                printf(
                    '<strong><u>Product #%d/%d : %s</u></strong><br>',
                    $id_product,
                    $id_product_attribute,
                    is_array($product->name) ? $product->name[$this->id_lang] : $product->name
                );
            }

            $product_data = new StdClass;
            $product_data->ean13 = trim($product->ean13);
            $product_data->upc = trim($product->upc);
            $product_data->reference = trim($product->reference);
            $product_data->wholesale_price = (float)trim($product->wholesale_price);
            $product_data->available_date = trim($product->available_date);
            $product_data->date_add = trim($product->date_add);
            $product_data->condition = trim($product->condition);
            $product_data->active = trim($product->active);

            if ($id_product_attribute) {
                if (version_compare(_PS_VERSION_, '1.5', '<')) {
                    $combinations = $product->getAttributeCombinaisons($this->id_lang);
                } else {
                    $combinations = $product->getAttributeCombinations($this->id_lang);
                }

                if (is_array($combinations) && count($combinations)) {
                    foreach ($combinations as $combination) {
                        if ((int)$combination['id_product_attribute'] === (int)$id_product_attribute) {
                            $product_data->ean13 = trim($combination['ean13']);
                            $product_data->upc = trim($combination['upc']);
                            $product_data->reference = trim($combination['reference']);
                            $product_data->available_date = trim($combination['available_date']);

                            if (($wholesale_price = (float)$combination['wholesale_price']) > 0) {
                                $product_data->wholesale_price = $wholesale_price;
                            }

                            break;
                        }
                    }
                }
            }

            $sku = $product_data->reference;

            if (!Tools::strlen($product_data->reference)) {
                printf(' > Missing or wrong reference for id_product: %d/%d.<br>', $id_product, $id_product_attribute);
                continue;
            }

            $product_option = PriceMinisterProductExt::getProductOptions($id_product, $id_product_attribute, $this->id_lang);

            if (is_array($product_option) && count($product_option)) {
                $product_option = reset($product_option);
            } else {
                $product_option = array_fill_keys(
                    array('id_product', 'id_product_attribute', 'id_lang', 'force', 'disable', 'price', 'text', 'repricing_min', 'repricing_max'),
                    null
                );
            }

            // Get category
            $category_set = PriceMinisterRepricing::getCategoryForIdProduct($product->id);

            if (is_array($category_set) && count($category_set)) {
                $id_category = reset($category_set);

                if (count($category_set) > 1) {
                    if (in_array($product->id_category_default, $category_set)) {
                        $id_category = (int)$product->id_category_default;
                    } elseif (is_array($profile2category) && is_array($categories)) {
                        // Product has multiple categories in category selection
                        if (count(array_intersect($category_set, $categories)) > 1) {
                            if (count(array_intersect($category_set, array_keys(array_unique($profile2category)))) > 1) {
                                printf($this->l(' > Product "%s" has several profiles in several categories !<br>'), $id_product);
                            }
                        }
                    }
                }
            } elseif ($product->id_category_default) {
                $id_category = (int)$product->id_category_default;
            }

            if (!in_array($id_category, $categories)) {
                printf(' > Product is not in selected categories: %d %d.<br>', $id_product, $id_category);
                continue;
            }

            $profile_id = null;
            $profile_name = null;

            if (isset($profile2category[$id_category])) {
                if (in_array($id_category, $categories)) {
                    $profile_name = $profile2category[$id_category];
                    $profile_id = false;

                    if (is_array($profiles)) {
                        foreach ($profiles as $profile_id => $profile) {
                            if ($profile['name'] == $profile_name) {
                                break;
                            }
                        }
                    }

                    if ($profile_id !== false && !empty($profile_name)) {
                        printf(' > Using profile [%s]<br>', $profile_name);
                    } else {
                        return (false);
                    }
                } else {
                    printf(' > Profil is not in profiles list [%s] id: %s.<br>', $profile_name, $profile_id);
                }
            }

            $p_repricing = isset($profile['repricing_strategie']) ? $profile['repricing_strategie'] : null;

            if (!$p_repricing) {
                printf(' > Repricing is not selected for profile: %s(%d).<br>', $profile_name, $profile_id);
                continue;
            }

            if (!(is_array($strategies) && count($strategies) && array_key_exists($p_repricing, $strategies) && is_array($strategies[$p_repricing]))) {
                printf(' > No repricing strategy available for this profile: %s(%d).<br>', $profile_name, $profile_id);
            }

            $strategy = $strategies[$p_repricing];

            if (!$strategy['active']) {
                printf(' > Strategy %s is inactive.<br>', $strategy['name']);
                continue;
            }

            $std_price = $product->getPrice(
                $config_parameters['taxes'],
                ($id_product_attribute ? (int)$id_product_attribute : null),
                2,
                null,
                false,
                $product->on_sale && $config_parameters['specials']
            );

            $wholesale_price_origin = null;
            $std_price_origin = null;
            $current_price = $std_price;

            // Price Rule
            if (array_key_exists('price_rule', $profile) && is_array($profile['price_rule'])) {
                $current_price = PriceMinisterTools::PriceRule($std_price, $profile['price_rule']);
            }
            // Price Override
            if (!empty($product_option['price']) && (float)$product_option['price']) {
                $current_price = (float)$product_option['price'];
            }

            if (version_compare(_PS_VERSION_, '1.5', '<')) {
                $product_quantity = Product::getQuantity($id_product, $id_product_attribute);
            } else {
                $product_quantity = Product::getRealQuantity($id_product, $id_product_attribute, $id_warehouse, $id_shop);
            }

            if (!$product_quantity) {
                printf(' > No stock for product %d/%d, skipped...<br>', $id_product, $id_product_attribute);
                continue;
            }

            $current_price = sprintf('%.02f', Tools::ps_round($current_price, 2));
            $base_price = null;

            switch ($strategy['base']) {
                case PriceMinisterRepricing::REPRICING_BASED_ON_WHOLESALE_PRICE:
                    if (!isset(Context::getContext()->country) || Tools::strtoupper(Context::getContext()->country->iso_code) !== 'FR') {
                        Context::getContext()->country = new Country((int)Country::getByIso('FR'));
                    }

                    if (!($tax = (float)Tax::getProductTaxRate($id_product))) {
                        // No tax on product
//                        printf(' > Unable to determine product tax for id_product:%d/%d.<br>', $id_product, $id_product_attribute);
//                        continue;
                    }

                    if (!$product_data->wholesale_price) {
                        printf(' > Missing wholesale price id_product: %d.<br>', $id_product);
                        continue;
                    }

                    $base_price = Tools::ps_round($product_data->wholesale_price * (1 + ($tax / 100)), 2);
                    break;
                case PriceMinisterRepricing::REPRICING_BASED_ON_REGULAR_PRICE:
                    $base_price = Tools::ps_round($current_price, 2);
                    break;
                default:
                    printf(' > Repricing base is not selected for strategy: %s.<br>', $strategy['name']);
                    continue;
            }

            if (!$base_price) {
                printf(' > Missing base price for product: %d/%d.<br>', $id_product, $id_product_attribute);
                continue;
            }

            $aggressiveness = max(1, (int)$strategy['aggressiveness']);
            $limit = (int)$strategy['limit'];
            $strategy_overrides = PriceMinisterRepricing::getProductStrategy($id_product, $id_product_attribute, $this->id_lang);
            $product_option_strategy = false;

            $delta_min = 0;
            $delta_max = 0;

            if ((float)$product_option['repricing_min'] && (float)$product_option['repricing_max']) {
                $product_option_strategy = true;
                $price_min = sprintf('%.02f', Tools::ps_round($product_option['repricing_min'], 2));
                $price_max = sprintf('%.02f', Tools::ps_round($product_option['repricing_max'], 2));
            } else {
                if (is_array($strategy_overrides) && count($strategy_overrides)) {
                    $strategy_override = reset($strategy_overrides);
                    $price_min = sprintf('%.02f', Tools::ps_round($strategy_override['minimum_price'], 2));
                    $price_max = sprintf('%.02f', Tools::ps_round($strategy_override['target_price'], 2));
                } else {
                    $delta_min = (int)$strategy['delta']['min'];
                    $delta_max = (int)$strategy['delta']['max'];

                    $price_min = sprintf('%.02f', Tools::ps_round($current_price * (1 + ($delta_min / 100)), 2));
                    $price_max = sprintf('%.02f', Tools::ps_round($current_price * (1 + ($delta_max / 100)), 2));
                }
            }

            $base_price_limit = sprintf('%.02f', Tools::ps_round($base_price * (1 + ($limit / 100)), 2));

            if ($product_option_strategy) {
                // Overrides limit
                $base_price_limit = sprintf('%.02f', Tools::ps_round($price_min, 2));
            }

            if (!is_numeric($limit) || $base_price_limit <= 0) {
                $base_price_limit = $price_min;
            }

            if ((string)$adv->adverttype == 'NEW') {
                $notification = $adv->productsummary->pricing->adverts->newadverts;
            } else {
                $notification = $adv->productsummary->pricing->adverts->usedadverts;
            }

            if (!is_array($notification->advert)) {
                $notification->advert = array($notification->advert);
            }

            if (isset($notification->advert[0]) && Tools::strtolower($merchant_login['login']) == Tools::strtolower($notification->advert[0]->seller->login)) {
                printf('Your offer is already the best, product price updated to higher value.<br>');
//                echo '<hr>';
//                continue;
                $notification->advert[0] = $notification->advert[1];
            }

            if (Tools::strtolower($merchant_login['login']) == Tools::strtolower($notification->advert[0]->seller->login)) {
                printf('Your offer is already the best, product skipped.<br>');
                echo '<hr>';
                continue;
            }

            $calculated = $this->getBestPrice($notification->advert[0], $adv, $aggressiveness);

            if (!$calculated) {
                $safe_price = sprintf('%.02f', max($base_price_limit, $current_price));
                printf(' >>> No competition, skipping offer...<br>');
                $reprice = false;
            } else {
                if ($calculated <= $base_price_limit || $calculated <= $price_min) {
                    $safe_price = sprintf('%.02f', max($base_price_limit, $price_min, $calculated));
                    printf(' >>> Sending Price Min.: %.02f<br>', $safe_price);
                    $reprice = $safe_price;
                } else {
                    if ($calculated >= $price_max) {
                        $safe_price = sprintf('%.02f', max($base_price_limit, $price_max, $calculated));
                        printf(' >>> Sending Price Max.: %.02f<br>', $safe_price);
                        $reprice = $safe_price;
                    } else {
                        $safe_price = sprintf('%.02f', max($base_price_limit, $calculated));
                        echo ' >>> Repriced at '.$safe_price.'<br>';
                        $reprice = $safe_price;
                    }
                }
            }

            if ($reprice) {
                $sku_items[$sku] = array();
                $sku_items[$sku]['target_price'] = sprintf('%.02f', $reprice);
                $sku_items[$sku]['id_product'] = $id_product;
                $sku_items[$sku]['id_product_attribute'] = $id_product_attribute;
                $sku_items[$sku]['id_lang'] = $this->id_lang;
                $sku_items[$sku]['minimum_price'] = null;
                $sku_items[$sku]['actual_price'] = null;
                $sku_items[$sku]['gap'] = null;
            }

            echo '<hr>';
        }

        if (is_array($sku_items) && count($sku_items)) {
            foreach ($sku_items as $item) {
                PriceMinisterRepricing::saveRepricing($item);
            }
            Configuration::updateValue('PM_LAST_REPRICING', date('Y-m-d H:i:s'));
        }
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    public function getBestPrice($best_offer, $my_offer, $aggressiveness_param)
    {
        if (!$best_offer instanceof stdClass) {
            return false;
        }

        $agressivities = array();
        for ($i = 10, $rindex = 10; $i < 110; $i += 10, $rindex--) {
            $calculated_agressivity = round($aggressiveness_param / $rindex, 2);
            $agressivities[$i] = $calculated_agressivity;
        }

        $my_price = (float)$my_offer->price->amount;
        $my_shipping = (float)$my_offer->shippingcost->amount;

        $calculated_price = null;
        $cheaper_price = $best_offer->price->amount;
        $cheaper_shipping = $best_offer->shippingcost->amount;

        printf(' > Best offer : <pre>%s</pre> > Cheaper price : %.02f<br>', print_r($best_offer, true), $cheaper_price);

        $base_price = $cheaper_price;
        $base_shipping = $cheaper_shipping;

        printf(' > Base Price: %.02f / Base Shipping: %.02f<br>', $base_price, $base_shipping);

        if ($base_price) {
            $aggressiveness = reset($agressivities);
            $aggressiveness /= 100;
            $aggressiveness_level = key($agressivities);

            $shipping_diff = (float)$my_shipping - $base_shipping;

            $raw_price = ($base_price - $shipping_diff);
            $calculated_price = (float)sprintf('%.02f', ($raw_price / (1 + $aggressiveness)));

            echo "<pre>";
            printf('Aggressiveness: %d<br>', $aggressiveness_param);
            printf('Competition on Price: %.02f, Shipping: %.02f, Price+Shipping: %.02f'."\n", $base_price, $base_shipping, $base_price + $base_shipping);
            printf('My Price: %.02f Shipping: %.02f, Price+Shipping: %.02f, Agressivity: Level: %d / Rate: %.04f, Calculated: %.02f'."\n", $my_price, $my_shipping, $my_price + $my_shipping, $aggressiveness_level, $aggressiveness, $calculated_price);
            printf('<b>Competition Result: %.02f against %.02f</b>'."\n", $calculated_price + $my_shipping, $base_price + $base_shipping);
            echo "</pre>\n";
        }

        return $calculated_price;
    }
}

$repricing_automaton = new PriceMinisterRepricingAutomaton();
$repricing_automaton->dispatch();
