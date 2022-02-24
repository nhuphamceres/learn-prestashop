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

require_once(dirname(__FILE__).'/env.php');
require_once(dirname(__FILE__).'/../classes/fnac.tools.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.product.class.php');
require_once(dirname(__FILE__).'/../fnac.php');
require_once(dirname(__FILE__).'/../classes/fnac.context.class.php');

class FNACExportProdcutsCSV extends FNAC
{

    private $outOfStock;
    private $formula;
    private $callback;
    private $conditionMap;
    private $currentDate;

    public function __construct()
    {
        parent::__construct();

        FNAC_Context::restore($this->context, Validate::isLoadedObject($this->context->shop) ? $this->context->shop : null);
        // Stock
        //
        $this->outOfStock = Configuration::get('FNAC_OUT_OF_STOCK');

        // Price Formula & Callback
        //
        $this->formula = Configuration::get('FNAC_PRICE_FORMULA');
        $this->callback = Configuration::get('FNAC_PRICE_CALLBACK');
        $this->conditionMap = array_flip(unserialize(FNAC_Tools::decode(Configuration::get('FNAC_CONDITION_MAP'))));

        $this->currentDate = date('Y-m-d H:i:s');

        $this->ps_images = 'http://'.htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'img/p/';
    }

    public function dispatch()
    {
        $action = Tools::getValue('action');

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_shop = Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
            Shop::setContext(Shop::CONTEXT_SHOP, $id_shop);
        }
        switch ($action) {
            case 'export_products_as_csv' :
                $this->exportProductsAsCSV();
                break;
        }
    }

    public function exportProductsAsCSV()
    {
        ob_start();
        $only_active = false;
        $only_in_stock = false;
        $outOfStockMin = 0;
        $output = null;
        $history = array();
        $header = '';
        // Parameters
        if (Tools::getValue('products-active-csv')) {
            $only_active = true;
        }

        if (Tools::getValue('products-in-stock-csv')) {
            $only_in_stock = true;
        }

        if (Tools::getValue('products-in-stock-csv')) {
            $outOfStockMin = (int)Configuration::get('FNAC_OUT_OF_STOCK');
        }

        $categoriesSet = Tools::getValue('categoryBox_csv');
        $headerSet = false;

        foreach ($categoriesSet as $val) {
            $categorieId = (int)$val;

            $cat = new Category($categorieId, $this->id_lang);
            $catName = $cat->name;
            $parentCat = null;
            $parentCatName = $cat->name;

            if (isset($cat->id_parent) && $cat->id_parent) {
                $parentCat = new Category($cat->id_parent, $this->id_lang);
                $parentCatName = $parentCat->name;
            }

            $products = new Product(null, true, $this->id_lang);
            $p = $products->getProducts($this->id_lang, 0, 0, 'id_product', 'desc', $categorieId, $only_active);

            $public = '';
            $gender = '';
            $size_field = '';
            $size_feature_field = '';
            $color_field = '';
            $color_feature_field = '';


            foreach ($p as $product) {
                $id = $product['id_product'];

                if (isset($history[$id])) {
                    continue;
                }

                // Unicit des produits
                //
                $history[$id] = true;

                $details = new Product($id, $this->id_lang);
                $features_product = Product::getFeaturesStatic($id);


                $mapped_color = null;
                $mapped_size = null;
                $color = null;
                $size = null;

                // Product Combinations
                //
                if (version_compare(_PS_VERSION_, '1.5', '<')) {
                    $combinations = $details->getAttributeCombinaisons($this->id_lang);
                } else {
                    $combinations = $details->getAttributeCombinations($this->id_lang);
                }

                $details->price = $details->getPrice();

                // Product Options
                //
                $options = Fnac_Product::getProductOptions($id, $this->id_lang);

                $disabled = $options['disable'] ? true : false;
                $force = $options['force'] ? true : false;
                $text = $options['text'];

                if (!is_array($combinations) || empty($combinations)) {
                    $combinations = array(0 => array(
                        'reference' => $details->reference,
                        'price' => 0,
                        'ecotax' => $details->ecotax,
                        'quantity' => $details->quantity,
                        'ean13' => $details->ean13
                    ));
                }
                $previousId = null;


                $group_details = array();
                $parent_id = false;
                if (!is_array($combinations) || empty($combinations)) {
                    $combinations = array(0 => array(
                        'reference' => $details->reference,
                        'ean13' => $details->ean13,
                        'id_product_attribute' => 0
                    ));
                }
                // Grouping Combinations
                asort($combinations);

                foreach ($combinations as $combination) {
                    $id_product_attribute = isset($combination['id_product_attribute']) ? $combination['id_product_attribute'] : 0;
                    $id_attribute_group = isset($combination['id_attribute_group']) ? $combination['id_attribute_group'] : 0;
                    //$id_attribute = isset($combination['id_attribute']) ? $combination['id_attribute'] : 0;

                    $group_details[$id_product_attribute][$id_attribute_group] = array();
                    $group_details[$id_product_attribute][$id_attribute_group]['reference'] = $combination['reference'];
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

                $first = 0;
                $idx = 0;

                foreach ($group_details as $id_product_attribute => $combination) {
                    $idx++;
                    $group_detail = array();

                    if (!$first && $id_product_attribute) {
                        $parent_id = sprintf('%s_%s', $details->id, $id_product_attribute);
                    }


                    foreach ($combination as $id_group => $group_detail) {
                        if (isset($group_detail['reference'])) {
                            $reference = $group_detail['reference'];
                        }
                        if (isset($group_detail['ean13'])) {
                            $ean13 = $group_detail['ean13'];
                        }


                        if (isset($size_field) && $size_field == $id_group) {
                            $size = $group_detail['attribute_name'];

                            if (isset($group_detail['attr_mapping'])) {
                                $attributeSizeFound = true;
                                $mapped_size = $group_detail['attr_mapping'];
                            } else {
                                $attributeSizeFound = false;
                                $mapped_size = $group_detail['attribute_name'];
                            }
                        } elseif (isset($color_field) && $color_field == $id_group) {
                            $color = $group_detail['attribute_name'];
                            if (isset($group_detail['attr_mapping'])) {
                                $attributeColorFound = true;
                                $mapped_color = $group_detail['attr_mapping'];
                            } else {
                                $attributeColorFound = false;
                                $mapped_color = $group_detail['attribute_name'];
                            }
                        }
                    }

                    $features = Feature::getFeatures($this->id_lang);

                    $features_mapping_right = array(); // was undefinied, set like this to avoid error
                    if (!isset($color_field) || $color_field == '') {
                        //Look for product features
                        foreach ($features_product as $fp) {
                            $id_feature = $fp['id_feature'];
                            $id_feature_value = $fp['id_feature_value'];
                            if ($color_feature_field != $id_feature) {
                                continue;
                            }

                            $feature = Feature::getFeature($this->id_lang, $id_feature);
                            $color = $feature['name'];
                            //Get values for options of feature
                            $feature_values = FeatureValue::getFeatureValuesWithLang($this->id_lang, $id_feature);

                            if (!count($feature_values)) {
                                continue;
                            }

                            foreach ($feature_values as $fv) {
                                if ($id_feature_value != $fv['id_feature_value']) {
                                    continue;
                                }
                                $color = $fv['value'];
                                if (isset($features_mapping_right[$id_feature]) && isset($features_mapping_right[$id_feature][$id_feature_value])) {
                                    $mapped_color = $features_mapping_right[$id_feature][$id_feature_value];
                                } else {
                                    $mapped_color = $fv['value'];
                                }
                            }
                        }
                    }

                    if (!isset($size_field) || $size_field == '') {
                        //Look for product features
                        foreach ($features_product as $fp) {
                            $id_feature = $fp['id_feature'];
                            $id_feature_value = $fp['id_feature_value'];
                            if ($size_feature_field != $id_feature) {
                                continue;
                            }

                            $feature = Feature::getFeature($this->id_lang, $id_feature);

                            $size = $feature['name'];
                            //Get values for options of feature
                            $feature_values = FeatureValue::getFeatureValuesWithLang($this->id_lang, $id_feature);
                            if (!count($feature_values)) {
                                continue;
                            }

                            foreach ($feature_values as $fv) {
                                if ($id_feature_value != $fv['id_feature_value']) {
                                    continue;
                                }
                                $size = $fv['value'];
                                if (isset($features_mapping_right[$id_feature]) && isset($features_mapping_right[$id_feature][$id_feature_value])) {
                                    $mapped_size = $features_mapping_right[$id_feature][$id_feature_value];
                                } else {
                                    $mapped_size = $fv['value'];
                                }
                            }
                        }
                    }


                    if (isset($previousId) && $combination['id_product_attribute'] == $previousId) {
                        continue;
                    }
                    $previousId = isset($combination['id_product_attribute']) ? $combination['id_product_attribute'] : null;

                    $details->price = $details->getPrice(true, $previousId, 6, null, false, true);

                    $id_product_attribute = isset($combination['id_product_attribute']) ? $combination['id_product_attribute'] : null;
                    $images = array();

                    foreach (FNAC_Tools::getProductImages($details->id, $id_product_attribute, $this->id_lang) as $image) {
                        $images[] = $this->ps_images.$image;
                    }

                    $conditionMap = array(); // was undefinied, set like this to avoid error.
                    $condition = isset($conditionMap[$details->condition]) ? $conditionMap[$details->condition] : '11';

                    $name = $product['name'];
                    $description = FNAC_Tools::hfilter(Tools::substr($product['description'], 0, 255));
                    $description_short = FNAC_Tools::hfilter($product['description_short']);
                    $manufacturer_name = $product['manufacturer_name'];


                    if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                        $quantity = StockAvailable::getQuantityAvailableByProduct($details->id, $id_product_attribute ? $id_product_attribute : null);
                    } else {
                        $quantity = $combination['quantity'];
                    }

                    if ($force && $quantity <= 1) {
                        $quantity = 999;
                    }

                    if ($disabled || ($only_in_stock && $quantity < $outOfStockMin)) {
                        continue;
                    } elseif (!$details->active) {
                        continue;
                    }

                    $fields = array();
                    $fields['EAN'] = '';

                    if ((int)$ean13) {
                        $fields['EAN'] = sprintf('%013s', $ean13);
                        $typeid = 4;
                    } else {
                        $fields['EAN'] = sprintf('%s', $reference);
                        $typeid = 3;
                    }

                    if (empty($reference)) {
                        continue;
                    }

                    $fields['SKU PART'] = '';
                    $fields['M??ta-Type article'] = $parentCatName;
                    $fields['Type article'] = FNAC_Tools::cPath($categorieId, $this->id_lang);
                    $fields['Support'] = '';
                    $fields['Arborescences r??f??rencement N1'] = '';
                    $fields['Arborescences r??f??rencement N2'] = '';
                    $fields['Arborescences r??f??rencement N3'] = '';
                    $fields['Arborescences r??f??rencement N4'] = '';
                    $fields['Arborescences r??f??rencement N5'] = '';
                    $fields['Mots-cl??s'] = '';
                    $fields['Titre'] = $name;
                    $fields['description'] = $description_short;
                    $fields['Constructeur / Marque'] = empty($manufacturer_name) ? 'No Name' : $manufacturer_name;
                    $fields['Coloris'] = (isset($mapped_color) && $mapped_color) ? $mapped_color : $color;
                    $fields['Date de sortie'] = '';
                    $fields['S??rie / Edition limit??e'] = '';
                    $fields['Images principale (Jaquette avant)'] = isset($images[0]) ? $images[0] : '';
                    $fields['Type autre image (Jaquette arri??re, screenshot, visuel,???)) '] = isset($images[1]) ? $images[1] : '';
                    $fields['Autre Image'] = isset($images[2]) ? $images[2] : '';
                    $fields['Vid??o'] = '';
                    $fields['Hauteur'] = $details->height;
                    $fields['Longueur'] = '';
                    $fields['Largeur'] = $details->width;
                    $fields['Poids'] = $details->weight;
                    $fields['D??tails Techniques'] = '';
                    $fields['Prix Public'] = $details->price;
                    $fields['Garantie'] = '';
                    $fields['Fonctions'] = '';
                    $fields['Langue de la notice'] = '';
                    $fields['Configuration requise'] = '';
                    $fields['Configuration recommand??e'] = '';
                    $fields['Accessoires indispensables'] = '';
                    $fields['Accessoires facultatifs'] = '';
                    $fields['Pile requises'] = '';

                    if (!$headerSet) {
                        foreach ($fields as $col => $field) {
                            $header .= str_replace(';', ':', $col).';';
                        }
                        $header .= "\n";
                        $headerSet = true;
                    }

                    foreach ($fields as $field) {
                        $output .= str_replace(';', ':', $field).';';
                    }

                    $output .= "\n";
                }
            }
        }
        $file = date('Y-m-d').'_'.$this->name.'-create.csv';
        $outputUrl = $this->url.'exports/'.$file;
        $outputFile = $this->path.'exports/'.$file;

        $output = $header.$output;

        if (file_put_contents($outputFile, $output)) {
            echo '<a href="'.$outputUrl.'" alt="">'.$outputUrl.'</a>';
            Configuration::updateValue('FNAC_LAST_IMPORTED', $this->currentDate);
        }

        $results = ob_get_contents();
        ob_end_clean();
        echo $results;
    }

    public static function cleanup($text)
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
        $text = filter_var($text, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        $text = str_replace('&#39;', "'", $text);
        $text = str_replace('"', "'", $text);
        $text = str_replace('&', '&amp;', $text);

        return ($text);
    }
}

$fnac_export = new FNACExportProdcutsCSV();
$fnac_export->dispatch();
