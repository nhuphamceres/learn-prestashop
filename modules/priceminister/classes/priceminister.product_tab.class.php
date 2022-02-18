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
 * Support by mail  :  support.spartoo@common-services.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__).'/../priceminister.php');
require_once(dirname(__FILE__).'/../classes/priceminister.tools.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.product.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.context.class.php');

class PriceMinisterProductTab extends PriceMinister
{

    public function doIt($params)
    {
        if (Tools::getValue('debug')) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }

        $id_product = (int)Tools::getValue(
            'id_product',
            is_array($params) && array_key_exists('id_product', $params) ? $params['id_product'] : 0
        );

        if (!is_numeric($id_product)) {
            return (false);
        }

        $product = new Product($id_product);

        if (!Validate::isLoadedObject($product)) {
            return (false);
        }

        $view_params = array();

        if (version_compare(_PS_VERSION_, '1.5', '>=') && Shop::isFeatureActive() && in_array($this->context->shop->getContext(), array(Shop::CONTEXT_GROUP, Shop::CONTEXT_ALL))) {
            $view_params['shop_warning'] = $this->l('You are in multishop environment. To use this module, you must select a target shop.');
        }

        $combinations = array();
        $has_attributes = false;

        $this->context = Context::getContext();

        $id_lang = $this->context->language->id;

        // Just FR
        $languages = PriceMinisterTools::languages(true);

        $view_params['id_lang'] = $id_lang;
        $view_params['images'] = $this->images;
        $view_params['id_product'] = (int)$id_product;
        $view_params['module_url'] = $this->url;
        $view_params['module_path'] = $this->path;
        $view_params['version'] = $this->version;
        $view_params['ps16x'] = version_compare(_PS_VERSION_, '1.6', '>=');
        $view_params['ps15'] = version_compare(_PS_VERSION_, '1.6', '<');
        $view_params['id_product'] = (int)$id_product;
        $view_params['pm_token'] = Configuration::get(PriceMinister::CONFIG_PM_CRON_TOKEN);
        $view_params['json_url'] = $this->url.'functions/product_ext.json.php?context_key='.PriceMinisterContext::getKey($this->context->shop);

        $view_params['active'] = $active = true;

        $view_params['class_warning'] = 'warn '.($this->ps16x ? 'alert alert-warning' : '');
        $view_params['class_error'] = 'error '.($this->ps16x ? 'alert alert-danger' : '');
        $view_params['class_success'] = 'confirm '.($this->ps16x ? 'alert alert-success' : 'conf');
        $view_params['class_info'] = 'hint '.($this->ps16x ? 'alert alert-info' : 'conf');

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $view_params["PS14"] = "1";
        }

        $view_params['product_tab'] = array();
        $view_params['product_tab']['id_product'] = $id_product;
        $view_params['product_tab']['id_manufacturer'] = $product->id_manufacturer;
        $view_params['product_tab']['id_category_default'] = $product->id_category_default;
        $view_params['product_tab']['id_supplier'] = $product->id_supplier;

        if ($active) {
            $product_name = $product->name[$id_lang];

            if (Combination::isFeatureActive() && $product->hasAttributes()) {
                $has_attributes = true;
                $combinations = array();

                $attributes_groups = $product->getAttributesGroups($id_lang);
                $attributes = $product->getProductAttributesIds($id_product);

                if (is_array($attributes_groups) && is_array($attributes)) {
                    foreach ($attributes as $attribute) {
                        $id_product_attribute = $attribute['id_product_attribute'];
                        $complex_id = sprintf('%d_%d', $id_product, $attribute['id_product_attribute']);

                        $combinations[$complex_id] = array();

                        $combination = new Combination((int)$id_product_attribute);
                        $attributes = $combination->getAttributesName($id_lang);

                        foreach ($attributes as $attribute) {
                            $attribute_group_name = null;

                            foreach ($attributes_groups as $attribute_group) {
                                if ($attribute_group['id_attribute'] != $attribute['id_attribute']) {
                                    continue;
                                }
                                $attribute_group_name = $attribute_group['group_name'];
                            }
                            if (Tools::strlen($attribute_group_name)) {
                                $combination_pair = sprintf('%s - %s', $attribute_group_name, $attribute['name']);
                            } else {
                                $combination_pair = $attribute['name'];
                            }

                            $combinations[$complex_id]['complex_id'] = sprintf('%d_%d', $product->id, $id_product_attribute);
                            $combinations[$complex_id]['id_product'] = (int)$id_product;
                            $combinations[$complex_id]['id_product_attribute'] = (int)$id_product_attribute;
                            $combinations[$complex_id]['reference'] = $combination->reference;
                            $combinations[$complex_id]['ean13'] = $combination->ean13;
                            $combinations[$complex_id]['upc'] = $combination->upc;

                            if (array_key_exists('name', $combinations[$complex_id]) && Tools::strlen($combinations[$complex_id]['name'])) {
                                $combinations[$complex_id]['name'] .= sprintf(', %s', $combination_pair);
                            } else {
                                $combinations[$complex_id]['name'] = $combination_pair;
                            }
                        }
                    }
                }
            }

            $view_params['product_tab']['product'] = array();
            $view_params['product_tab']['product']['name'] = $product_name;
            $view_params['product_tab']['product']['complex_id'] = sprintf('%d_0', $product->id);
            $view_params['product_tab']['product']['reference'] = $product->reference;
            $view_params['product_tab']['product']['ean13'] = $product->ean13;
            $view_params['product_tab']['product']['upc'] = $product->upc;
            $view_params['product_tab']['product']['id_product'] = (int)$id_product;

            $view_params['product_tab']['combinations'] = $combinations;
        }

        $view_params['product_tab']['pm'] = array();
        $view_params['product_tab']['languages'] = array();
        $view_params['product_tab']['show_languages'] = false;

        $option_fields = array(
            'id_product',
            'id_product_attribute',
            'id_lang',
            'force',
            'disable',
            'price',
            'text',
            'repricing_min',
            'repricing_max'
        );

        if (is_array($languages) && count($languages) && count($option_fields)) {
            $view_params['product_tab']['languages'] = $languages;
            $view_params['product_tab']['show_languages'] = count($languages) > 1;

            foreach ($languages as $language) {
                $id_lang = (int)$language['id_lang'];

                $complex_id = sprintf('%d_%d', $id_product, 0);

                $product_options = PriceMinisterProductExt::getProductOptions($id_product, null, $id_lang);

                if (is_array($product_options) && count($product_options)) {
                    $view_params['product_tab']['pm'][$id_lang][$complex_id] = reset($product_options);
                } else {
                    $view_params['product_tab']['pm'][$id_lang][$complex_id] = array_fill_keys($option_fields, null);
                }

                $title = sprintf('%s - %s (%s)', !empty($product->reference) ? $product->reference : 'N/A', $product_name, $language['iso_code']);

                $view_params['product_tab']['pm'][$id_lang][$complex_id]['id_product'] = $id_product;
                $view_params['product_tab']['pm'][$id_lang][$complex_id]['id_product_attribute'] = 0;
                $view_params['product_tab']['pm'][$id_lang][$complex_id]['id_lang'] = $id_lang;
                $view_params['product_tab']['pm'][$id_lang][$complex_id]['title'] = $title;

                if ($has_attributes && count($combinations)) {
                    foreach ($combinations as $combination) {
                        $id_product_attribute = $combination['id_product_attribute'];
                        $id_product = $combination['id_product'];
                        $complex_id = sprintf('%d_%d', $id_product, $id_product_attribute);

                        $product_options = PriceMinisterProductExt::getProductOptions($id_product, $id_product_attribute ? (int)$id_product_attribute : null, $id_lang);

                        if (is_array($product_options) && count($product_options)) {
                            $view_params['product_tab']['pm'][$id_lang][$complex_id] = reset($product_options);
                        } else {
                            $view_params['product_tab']['pm'][$id_lang][$complex_id] = array_fill_keys($option_fields, null);
                        }

                        $title = sprintf('%s - %s (%s)', !empty($combinations[$complex_id]['reference']) ? $combinations[$complex_id]['reference'] : 'N/A', $combinations[$complex_id]['name'], $language['iso_code']);

                        $view_params['product_tab']['pm'][$id_lang][$complex_id]['id_product'] = $id_product;
                        $view_params['product_tab']['pm'][$id_lang][$complex_id]['id_product_attribute'] = $id_product_attribute;
                        $view_params['product_tab']['pm'][$id_lang][$complex_id]['id_lang'] = $id_lang;
                        $view_params['product_tab']['pm'][$id_lang][$complex_id]['title'] = $title;
                    }
                }
            }
        }

        $this->context->smarty->assign($view_params);
        $html = $this->context->smarty->fetch($this->path.'views/templates/admin/catalog/product_tab.tpl');

        return $html;
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        if (!$specific) {
            $specific = basename(__FILE__, '.php');
        }

        return (parent::l($string, $specific, $id_lang));
    }
}
