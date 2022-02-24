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
 * ...........................................................................
 *
 * @author    Alexandre D. & Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  contact@common-services.com
 */

require_once dirname(__FILE__).'/env.php';
require_once(dirname(__FILE__).'/../classes/fnac.webservice.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.product.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.tools.class.php');
require_once(dirname(__FILE__).'/../fnac.php');
require_once(dirname(__FILE__).'/../classes/fnac.context.class.php');

@set_time_limit(7200);

class FNAC_SynchOffers extends FNAC
{


    public function __construct()
    {
        parent::__construct();

        FNAC_Context::restore($this->context, Validate::isLoadedObject($this->context->shop) ? $this->context->shop : null);
        // For product visibility purpose, if controller_type is front and product visibility is null, then product won't be loaded
        if (!$this->context->controller) {
            $this->context->controller = new FrontController();
        }
        $this->context->controller->controller_type = 'configuration';
    }


    public function l($string, $specific = false, $id_lang = null)
    {
        $id_lang = $this->id_lang;

        try {
            if (version_compare(_PS_VERSION_, '1.7.6.0', '>=')) {
                $id_lang = new Language($id_lang);
                $id_lang = $id_lang->locale;
            }

            return (parent::l($string, 'products', $id_lang));
        } catch (Exception $e) {
            return $string;
        }
    }


    public function DoIt()
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $this->context = Context::getContext();
            $this->context->currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        } else {
            require_once(dirname(__FILE__).'/../backward_compatibility/backward.php');

            if (is_object(Context::getContext()->cart)) {
                Context::getContext()->cart->id_currency = (int)Configuration::get('PS_CURRENCY_DEFAULT');
            }
        }

        $productsUpdate = array();
        $productsDelete = array();
        $u = $d = 0;

        $only_active = false;
        $only_in_stock = false;

        // Parameters
        //
        if (Tools::getValue('update-active')) {
            $only_active = true;
        }

        if (Tools::getValue('update-in-stock')) {
            $only_in_stock = true;
        }

        $currentDate = date('Y-m-d H:i:s');
        $platform = Tools::getValue('platform');
        if (!in_array($platform, array('fr', 'es', 'pt', 'be'))) {
            echo $this->l('The platform is not available.').'<br />';
            die;
        }

        $fnac_id_lang = Language::getIdByIso($platform);
        if (!isset($fnac_id_lang)) {
            echo $this->l('The language ID is not available.').'<br />';
            die;
        }
        //French might not be set as language for the shop...
        if (!$fnac_id_lang) {
            $default_lang = Language::getLanguages(true);
            $fnac_id_lang = (count($default_lang) > 0) ? $default_lang[0]['id_lang'] : 1;
        }


        $toCurrency = new Currency((int)Currency::getIdByIsoCode('EUR'));
        $fromCurrency = new Currency((int)(Configuration::get('PS_CURRENCY_DEFAULT')));

        // Cron Mode
        if (Tools::getValue('cron')) {
            $cr = "\n"; // carriage return
            $flag = Tools::strtoupper(Tools::getValue('platform'));
            if ($platform == 'fr') {
                $dateFrom = Configuration::get('FNAC_CRON_LAST_UPDATED');
            } else {
                $dateFrom = Configuration::get('FNAC_'.$flag.'_CRON_LAST_UPDATED');
            }

            $dateTo = preg_replace('/ .*/', '', $currentDate);

            $action = 'update';
            $cronMode = true;
            $lang = Tools::getValue('lang') ? Tools::getValue('lang') : 'fr';

            echo $this->l('Starting Update in WS API Cron Mode').' - '.$currentDate.$cr;

            $categories = unserialize(FNAC_Tools::decode(Configuration::get('FNAC_CATEGORIES')));
        }
        // Web Mode
        else {
            if (Tools::getValue('fnac_token') !== Configuration::get('FNAC_INSTANT_TOKEN', null, 0, 0)) {
                die($this->l('Wrong Token'));
            }

            $cronMode = false;

            $cr = '<br />'; // carriage return

            $dateFrom = Tools::getValue('datepickerFrom1');
            $dateTo = Tools::getValue('datepickerTo1');
            $action = Tools::getValue('action');

            echo $this->l('Starting Update in WS API Mode').' - '.$currentDate.$cr;

            $categories = Tools::getValue('categoryBox');
        }

        if (!count($categories)) {
            echo $this->l('No categories selected or saved, unable to process').$cr;
            die;
        }

        $logistic_type_ids = unserialize(FNAC_Tools::decode(Configuration::get('FNAC_LOGISTIC_TYPES')));

        // FNAC API - France by default
        //
        if ($platform == 'fr') {
            $flag = '';
        } else {
            $flag = Tools::strtoupper($platform).'_';
        }

        $partner_id = Configuration::get('FNAC_'.$flag.'PARTNER_ID');
        $shop_id = Configuration::get('FNAC_'.$flag.'SHOP_ID');
        $api_key = Configuration::get('FNAC_'.$flag.'API_KEY');
        $api_url = Configuration::get('FNAC_'.$flag.'API_URL');
        $debug = Configuration::get('FNAC_DEBUG');


        if (Tools::getValue('debug')) {
            $debug = true;
        }

        if ($debug) {
            @ini_set('display_errors', 'on');
            @define('_PS_DEBUG_SQL_', true);
            @error_reporting(E_ALL | E_STRICT);
        }

        // Etats du produits (neuf usag� etc...)
        //
        $conditionMap = array_flip(unserialize(FNAC_Tools::decode(Configuration::get('FNAC_CONDITION_MAP'))));

        // Stock
        //
        $outOfStock = Configuration::get('FNAC_OUT_OF_STOCK');

        // Use Discount/Specials
        //
        $discount = (bool)Configuration::get('FNAC_DISCOUNT');

        // Price Formula & Callback
        //
        $formula = Configuration::get('FNAC_PRICE_FORMULA');
        $callback = Configuration::get('FNAC_PRICE_CALLBACK');

        // Time to Ship
        $default_time_to_ship = (int)Configuration::get('FNAC_TIME_TO_SHIP');

        // Shop (PS 1.5+)
        $id_shop = Configuration::get('FNAC_SHOP');
        if (!$id_shop) {
            $id_shop = 1;
        }

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            Shop::setContext(Shop::CONTEXT_SHOP, $id_shop ? $id_shop : 1);
        }

        // For Advanced Stock Management
        $id_warehouse = Configuration::get('FNAC_WAREHOUSE');
        if (!$id_warehouse) {
            $id_warehouse = null;
        }

        // Filters
        $price_limiter = Tools::unSerialize(Configuration::get('FNAC_PRICE_LIMITER'));

        $history = array();

//        $fnac = new FnacAPI($partner_id, $shop_id, $api_key, $api_url, $debug);
//
//        if (!$fnac->Login()) {
//            echo $this->l('Unable to login').$cr;
//            die;
//        }

        switch ($action) {
            case 'update' :
                // Send Offers
                foreach ($categories as $key => $categorieId) {
                    $products = new FNAC_Product(null, true, $fnac_id_lang);

                    $p = $products->productsBetween($fnac_id_lang, $dateFrom, $dateTo, $categorieId);

                    foreach ($p as $key => $val) {
                        $id = $val['id_product'];

                        if (isset($history[$id])) {
                            continue;
                        }

                        // Unicit� des produits
                        //
                        $history[$id] = true;

                        // Product Options
                        //
                        $options = Fnac_Product::getProductOptions($id, $fnac_id_lang);

                        $disabled = $options['disable'] ? true : false;
                        $force = $options['force'] ? true : false;
                        $time_to_ship = (int)$options['time_to_ship'] ?: $default_time_to_ship;

                        if ($platform == 'fr') {
                            $text = $options['text'];
                        } else {
                            $text = $options['text_'.$platform];
                        }

                        if (!empty($options['price']) && is_numeric((float)$options['price'])) {
                            $priceOverride = (float)$options['price'];
                        } else {
                            $priceOverride = false;
                        }

                        $details = new Product($id, $fnac_id_lang);

                        // Product Combinations
                        //
                        if (version_compare(_PS_VERSION_, '1.5', '<')) {
                            $combinations = $details->getAttributeCombinaisons($this->id_lang);
                        } else {
                            $combinations = $details->getAttributeCombinations($this->id_lang);
                        }

                        if (!is_array($combinations) || !count($combinations)) {
                            $combinations = array(0 => array(
                                'reference' => $details->reference,
                                'ecotax' => $details->ecotax,
                                'ean13' => $details->ean13,
                                'id_product_attribute' => 0
                            ));
                        }

                        $previousId = null;
                        foreach ($combinations as $combination) {
                            $id_product_attribute = isset($combination['id_product_attribute']) ? $combination['id_product_attribute'] : null;
                            $details->reference = $combination['reference'];

                            $details->ecotax = $combination['ecotax'];
                            $details->ean13 = $combination['ean13'];

                            if (isset($previousId) && $combination['id_product_attribute'] && $combination['id_product_attribute'] == $previousId) {
                                continue;
                            }

                            $previousId = isset($combination['id_product_attribute']) ? $combination['id_product_attribute'] : null;

                            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                                $details->quantity = Product::getRealQuantity($details->id, $id_product_attribute ? $id_product_attribute : null, $id_warehouse, $id_shop);
                            } else {
                                $details->quantity = Product::getQuantity($details->id, $id_product_attribute ? $id_product_attribute : null);
                            }

                            $price = $details->getPrice($useTaxes = true, $id_product_attribute, 6, null, false, $discount);
                            if ($fromCurrency->iso_code != $toCurrency->iso_code) {
                                $price = Tools::convertPrice($price, $toCurrency);
                            }

                            // Price Formula (see Admin > Module > Fnac)
                            $newPrice = FNAC_Tools::Formula($price, $formula);
                            // Price CallBack (see Admin > Module > Fnac)
                            $newPrice = FNAC_Tools::CallBack($newPrice, $callback);
                            $newPrice = (float)$newPrice;

                            // Price without promotion
                            $price_without_reduc = (float)$details->getPrice($useTaxes, $id_product_attribute, 6, null, false, false);
                            if ($fromCurrency->iso_code != $toCurrency->iso_code) {
                                $price_without_reduc = Tools::convertPrice($price_without_reduc, $toCurrency);
                            }

                            // Price Formula (see Admin > Module > Fnac)
                            $price_without_reduc = FNAC_Tools::Formula($price_without_reduc, $formula);
                            // Price CallBack (see Admin > Module > Fnac)
                            $price_without_reduc = FNAC_Tools::CallBack($price_without_reduc, $callback);
                            $price_without_reduc = (float)$price_without_reduc;

                            if ($priceOverride) {
                                $price_without_reduc = $newPrice = $priceOverride;
                            }

                            // Discount !
                            $promotion = null;
                            if ($newPrice < $price_without_reduc) {
                                $specific_price = SpecificPrice::getSpecificPrice(
                                    $details->id,
                                    Context::getContext()->shop->id,
                                    $toCurrency->id,
                                    (int)Country::getByIso($platform),
                                    (int)Context::getContext()->customer->id_default_group,
                                    1,
                                    $id_product_attribute,
                                    0,
                                    0,
                                    1
                                );

                                $specific_price['from'] = date('c', strtotime('TODAY'));
                                $specific_price['to'] = date('c', strtotime('+5 DAYS 11:59PM'));

                                $fnac_sales = (int)Configuration::get('FNAC_SALES');
                                $promotion = array(
                                    'type' => $fnac_sales ? 'Sales' : 'FlashSale',
                                    'sales_period_reference' => $fnac_sales ?
                                        $this->getSalesPeriodReference() : null,
                                    'promotion_uid' => 'PRESTASHOP',
                                    'starts_at' => $specific_price['from'],
                                    'ends_at' => $specific_price['to'],
                                    'discount_type' => $specific_price['reduction_type'] == 'percentage' ?
                                        'percentage' : 'fixed',
                                    'discount_value' => $specific_price['reduction_type'] == 'percentage' ?
                                        ($specific_price['reduction'] * 100) : $specific_price['reduction']
                                );
                            }

                            $qty = $details->quantity;

                            if (!$details->ean13) {
                                if ($debug) {
                                    printf('Product %s(%d/%d) has no EAN13, skipped'.$cr, $details->reference, $details->id, $id_product_attribute);
                                }
                                continue;
                            }

                            if (!FNAC_Tools::EAN_UPC_Check($details->ean13)) {
                                printf($this->l('Inconsistency for product %s(%d/%d) - Product EAN/UPC(%s) seems to be wrong - Skipping product').$cr, $details->reference, $details->id, $id_product_attribute, $details->ean13);
                                continue;
                            }

                            if (FNAC_Tools::EAN_UPC_isPrivate($details->ean13)) {
                                printf($this->l('Inconsistency for product %s(%d/%d) - Product EAN/UPC(%s) is reserved for private use - Skipping product').$cr, $details->reference, $details->id, $id_product_attribute, $details->ean13);
                                continue;
                            }

                            if ($details->quantity < $outOfStock && !$force) {
                                if ($debug) {
                                    printf('Product %s(%d/%d) outOfStock'.$cr, $details->reference, $details->id, $id_product_attribute);
                                }
                                $qty = 0;
                            }
                            if (!$details->active || $disabled && !$force) {
                                if ($debug) {
                                    printf('Product %s(%d/%d) Inactive'.$cr, $details->reference, $details->id, $id_product_attribute);
                                }
                                $qty = 0;
                            }

//                            if ($force && $quantity <= 1)
//                                $quantity = 999;

                            // Recuperer l'etat du produit (neuf, usage etc...)
                            //
                            $condition = isset($conditionMap[$details->condition]) ? $conditionMap[$details->condition] : '11';

                            if ($qty <= 0 && $only_in_stock) {
                                continue;
                            }

                            // TODO
                            if (is_array($price_limiter) &&
                                ($newPrice < $price_limiter['down'] || $newPrice > $price_limiter['up'])) {
                                continue;
                            }

                            // FIX Boutique mesvyniles.fr
                            $details->reference = $details->id;
                            // !FIX Boutique mesvyniles.fr

                            if ($qty <= 0 && !$force) {
                                $productsDelete[$d] = array(
                                    'id' => $details->reference,
                                    'ean' => $details->ean13,
                                    'condition' => $condition,
                                    'qty' => $qty,
                                    'price' => $newPrice,
                                    'delete' => 1,
                                    'comment' => ''
                                );
                                $d++;
                            } else {
                                $productsUpdate[$u] = array(
                                    'id' => $details->reference,
                                    'ean' => $details->ean13,
                                    'condition' => $condition,
                                    'qty' => $qty,
                                    'description' => $text,
                                    // 'price' => $newPrice,
                                    'price' => $price_without_reduc,
                                    'delete' => 0, // never delete at this time
                                    'comment' => '',
                                    'logistic_type_id' => isset($logistic_type_ids[$categorieId]) && $logistic_type_ids[$categorieId] ?
                                        $logistic_type_ids[$categorieId] : '',
                                    'promotion' => $promotion,
                                    'time_to_ship' => $time_to_ship
                                );
                                $u++;
                            }
                        }
                    }
                }

                $fnac = new FnacAPI($partner_id, $shop_id, $api_key, $api_url, $debug);

                if (!$fnac->Login()) {
                    echo $this->l('Unable to login').$cr;
                    die;
                }

                if (!count($productsUpdate)) {
                    printf($this->l('No updated offers').$cr);
                } else {
                    if ($fnac->BatchOfferUpdate($productsUpdate)) {
                        printf($this->l('%s offers sent').$cr, count($productsUpdate));

                        if ($platform == 'fr' && $cronMode) {
                            Configuration::updateValue('FNAC_CRON_LAST_UPDATED', $currentDate);
                        } elseif ($platform == 'fr' && !$cronMode) {
                            Configuration::updateValue('FNAC_LAST_UPDATED', $currentDate);
                        } elseif ($platform == 'es' && $cronMode) {
                            Configuration::updateValue('FNAC_ES_CRON_LAST_UPDATED', $currentDate);
                        } elseif ($platform == 'es' && !$cronMode) {
                            Configuration::updateValue('FNAC_ES_LAST_UPDATED', $currentDate);
                        } elseif ($platform == 'pt' && !$cronMode) {
                            Configuration::updateValue('FNAC_PT_CRON_LAST_UPDATED', $currentDate);
                        } elseif ($platform == 'pt' && !$cronMode) {
                            Configuration::updateValue('FNAC_PT_LAST_UPDATED', $currentDate);
                        }
                    } else {
                        echo $this->l('Unable to update offers').$cr;
                    }
                }

                if ($d) {
                    printf($this->l('Inactives/Out Of Stock/Sold: %s offers to delete').$cr, count($d));

                    if ($fnac->BatchOfferUpdate($productsDelete)) {
                        printf($this->l('%s delete products').$cr, count($productsDelete));
                    } else {
                        echo $this->l('Unable to delete products').$cr;
                    }
                }
                break;

            // Display Offers count
            default :
                foreach ($categories as $key => $categorieId) {
                    $categorie = new Category($categorieId, $this->id_lang);

                    $products = new FNAC_Product(null, true, $this->id_lang);

                    $p = $products->productsBetween($fnac_id_lang, $dateFrom, $dateTo, $categorieId, $only_active);

                    if (count($p)) {
                        printf($this->l('Categorie : %s, %s products to update').$cr, $categorie->name, count($p));
                    } else {
                        printf($this->l('Categorie : %s, no products to update').$cr, $categorie->name);
                    }
                }
                break;
        }
    }

    protected function getSalesPeriodReference()
    {
        $month = date('n');
        $year = date('Y');

        if ($month >= 5) {
            return 'SUMMER_'.$year;
        }

        return 'WINTER_'.$year;
    }
}

$fnacOffers = new FNAC_SynchOffers();
$fnacOffers->DoIt();
