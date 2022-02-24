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
require_once(dirname(__FILE__).'/../classes/fnac.tools.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.product.class.php');
require_once(dirname(__FILE__).'/../fnac.php');
require_once(dirname(__FILE__).'/../classes/fnac.context.class.php');

@set_time_limit(7200);

class FNAC_MassCSV extends FNAC
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
        return (parent::l($string, 'masscsv'));
    }


    public function doIt()
    {
        if (Tools::getValue('fnac_token') !== Configuration::get('FNAC_INSTANT_TOKEN', null, 0, 0)) {
            die($this->l('Wrong Token'));
        }

        $only_in_stock = $only_active = true;

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $this->context = Context::getContext();
            $this->context->currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        } else {
            require_once(dirname(__FILE__).'/../backward_compatibility/backward.php');

            if (is_object(Context::getContext()->cart)) {
                Context::getContext()->cart->id_currency = (int)Configuration::get('PS_CURRENCY_DEFAULT');
            }
        }

        // Parameters
        //
        if (!Tools::getValue('masscsv-active')) {
            $only_active = false;
        }

        if (!Tools::getValue('masscsv-in-stock')) {
            $only_in_stock = false;
        }

        // Shop (PS 1.5+)
        $id_shop = Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
        if (!$id_shop) {
            $id_shop = 1;
        }

        // For Advanced Stock Management
        $id_warehouse = Configuration::get('FNAC_WAREHOUSE');
        if (!$id_warehouse) {
            $id_warehouse = null;
        }

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            Shop::setContext(Shop::CONTEXT_SHOP, $id_shop ? $id_shop : 1);
        }

        $toCurrency = new Currency((int)Currency::getIdByIsoCode('EUR'));
        $fromCurrency = new Currency((int)(Configuration::get('PS_CURRENCY_DEFAULT')));

        // Stock
        //
        $outOfStock = Configuration::get('FNAC_OUT_OF_STOCK');

        // Use Discount/Specials
        //
        $discount = (bool)Configuration::get('FNAC_DISCOUNT');

        // Name ref
        $name_ref = (bool)Configuration::get('FNAC_NAME_REF');

        // Price Formula & Callback
        //
        $formula = Configuration::get('FNAC_PRICE_FORMULA');
        $callback = Configuration::get('FNAC_PRICE_CALLBACK');
        $conditionMap = array_flip(unserialize(FNAC_Tools::decode(Configuration::get('FNAC_CONDITION_MAP'))));

        // Filters
        $price_limiter = Tools::unSerialize(Configuration::get('FNAC_PRICE_LIMITER'));

        $currentDate = date('Y-m-d H:i:s');

        $platform = Tools::strtoupper(Tools::getValue('platform', 'fr'));
        $id_lang = Language::getIdByIso($platform);

        if (!$id_lang) {
            header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error', true, 500);
            die($this->l(sprintf('The language for %s is not installed on your shop.', $platform)));
        }

        $file = Tools::strtoupper(Language::getIsoById($id_lang)).'_'.date('Y-m-d').'_'.$this->name.'.csv';
        $outputUrl = $this->url.'exports/'.$file;
        $outputFile = $this->path.'exports/'.$file;

        //id-produit;sku-vendeur;prix;condition;stock;infos;traitement;type-id-produit;commentaire-interne;numero-vitrine;constructeur;
        /*

            INFORMATIONS SUR L'API FNAC >>>>

            id-produit;
             EAN
            sku-vendeur;
              la reference unique de l'offre dans votre stock (elle peut contenir des lettres, des chiffres, et les caracteres suivants : ? _ ? ; ? - ? ; ? + ? ; ? # ? ; ? / ? ; ? \ ?, ainsi que les espaces)
              Attention, votre reference interne (ou sku-vendeur) est associee definitivement e un produit dans un etat donne. Chaque reference interne doit donc etre unique au sein de votre fichier et ne doit en aucun cas etre reutilisee pour une autre offre lors des prochains imports.
            prix;
              prix de l'offre (separation des centimes par une virgule)
            condition;
                    11 : Neuf
                1 : Occasion - Comme neuf
                2 : Occasion - Tres bon etat
                3 : Occasion - Bon etat
                4 : Occasion - Etat correct
                5 : Collection - Comme neuf
                6 : Collection - Tres bon etat
                7 : Collection - Bon etat
                8 : Collection - Etat correct
            stock;
            infos;
              Ce texte apparaetra en ligne et sera visible pour le client.
            traitement;
            traitement (definit le traitement e effectuer sur la ligne en cas de non-ecrasement des donnees ;
              'd' pour supprimer l'offre)
            type-id-produit;
                    definit le type de code ayant ete indique dans le champ ? id-produit ?
                    Les valeurs de ce champ peuvent etre :

                    2 : pour les ISBN
                    3 : pour les SKU partenaire
                    4 : pour les EAN
                      Si ce champ est laisse vide, nous considererons qu'il s'agit d'un EAN.
            commentaire-interne;
                     : informations que vous pouvez ajouter pour vous aider e traiter vos commandes (ce commentaire neest visible que par vous.)
            numero-vitrine;
                    Numero de l'offre dans la vitrine (Ce champ vous permet de selectionner jusquee 100 offres pour votre vitrine en les numerotant. Cette numerotation determine egalement leur ordre deaffichage. Votre Vitrine est visible dans votre espace boutique sur Fnac.com.)
            constructeur;
                  Marque / constructeur (Ce champ vous permet de specifier un constructeur dans le cas oe vous ajoutez des produits via des references constructeurs.)
        */

        $logistic_type_ids = unserialize(FNAC_Tools::decode(Configuration::get('FNAC_LOGISTIC_TYPES')));

        $header = 'id-produit;sku-vendeur;prix;stock;condition;infos;traitement;type-id-produit;commentaire-interne;numero-vitrine;constructeur;'."\n";
        $header2 = array(
            'EAN',
            'SKU PART',
            'Méta-Type article',
            'Type article',
            'Support',
            'Titre',
            'Description',
            'Marque',
            'Image Principale',
            'Autre Image 1',
            'Autre Image 2',
            'Autre Image 3',
            'Accessoires inclus',
            'Garantie',
            'prix',
            'condition',
            'stock',
            'Description Offre',
            'Traitement',
            'type-id-produit',
            'commentaire-interne',
            'numero-vitrine',
            'category-1',
            'category-2',
            'category-3',
            'Classe Logistique'
        );
        $fields2 = array();
        $output = null;
        $history = array();

        $categories = Tools::getValue('categoryBox');
        if (!is_array($categories) || !count($categories)) {
            die($this->l('You must select categories'));
        }

        $categories_name = array();
        if (method_exists('Category', 'getAllCategoriesName')) {
            $category_iterator = Category::getAllCategoriesName(null, $id_lang);
        } else {
            $category_iterator = Category::getSimpleCategories($id_lang);
        }

        foreach ($category_iterator as $category) {
            $categories_name[$category['id_category']] = $category['name'];
        }

        foreach ($categories as $key => $val) {
            $categorieId = (int)$val;

            $p = Product::getProducts($id_lang, 0, 0, 'id_product', 'desc', $categorieId, $only_active);

            foreach ($p as $product) {
                $id = $product['id_product'];

                if (isset($history[$id])) {
                    continue;
                }

                // Unicite des produits
                //
                $history[$id] = true;

                $details = new Product($id, false, $id_lang);

                // Product Combinations
                //
                if (version_compare(_PS_VERSION_, '1.5', '<')) {
                    $combinations = $details->getAttributeCombinaisons($id_lang);
                } else {
                    $combinations = $details->getAttributeCombinations($id_lang);
                }

                // Product Options
                //
                $options = Fnac_Product::getProductOptions($id, $id_lang);

                $disabled = $options['disable'] ? true : false;
                $force = $options['force'] ? true : false;
                $text = $options['text'];

                if (!empty($options['price']) && is_numeric((float)$options['price'])) {
                    $priceOverride = (float)$options['price'];
                } else {
                    $priceOverride = false;
                }

                if (!is_array($combinations) || empty($combinations)) {
                    $combinations = array(0 => array(
                        'reference' => $details->reference,
                        'ecotax' => $details->ecotax,
                        'ean13' => $details->ean13,
                        'id_product_attribute' => null
                    ));
                }

                $previousId = null;
                foreach ($combinations as $combination) {
                    $details->reference = $combination['reference'];
                    $details->ecotax = $combination['ecotax'];
                    $details->ean13 = $combination['ean13'];

                    if (isset($previousId) && $combination['id_product_attribute'] && $combination['id_product_attribute'] == $previousId) {
                        continue;
                    }

                    $previousId = isset($combination['id_product_attribute']) ? $combination['id_product_attribute'] : null;

                    $details->price = $details->getPrice(true, $previousId, 6, null, false, $discount);

                    $id_product_attribute = isset($combination['id_product_attribute']) ? $combination['id_product_attribute'] : null;

                    // Recuperer l'etat du produit (neuf, usage etc...)
                    $condition = isset($conditionMap[$details->condition]) ? $conditionMap[$details->condition] : '11';

                    $name = $product['name'];
                    $description = FNAC_Tools::hfilter(Tools::substr($product['description'], 0, 255));
                    $description_short = FNAC_Tools::hfilter($product['description_short']);
                    $manufacturer_name = $product['manufacturer_name'];

                    if ($fromCurrency->iso_code != $toCurrency->iso_code) {
                        $details->price = Tools::convertPrice($details->price, $toCurrency);
                    }

                    // Price Formula (see Admin > Module > Fnac)
                    //
                    $newPrice = FNAC_Tools::Formula($details->price, $formula);

                    // Price CallBack (see Admin > Module > Fnac)
                    //
                    $newPrice = FNAC_Tools::CallBack($newPrice, $callback);


                    $public_price = $newPrice;

                    if ($priceOverride) {
                        $public_price = $priceOverride;
                    }

                    $reference = $details->reference;

                    if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                        $details->quantity = Product::getRealQuantity($details->id, $id_product_attribute ? $id_product_attribute : null, $id_warehouse, $id_shop);
                    } else {
                        $details->quantity = Product::getQuantity($details->id, $id_product_attribute ? $id_product_attribute : null);
                    }

                    $quantity = (int)$details->quantity;

                    if (!$force && $only_in_stock && $quantity < $outOfStock) {
                        continue;
                    }

                    if ($force && $quantity <= 1) {
                        $quantity = 999;
                    }

                    /*
                       // Supprime le produit quand il n'est plus en stock
                       if ( $quantity <= 0 || $quantity < $outOfStock || ! $details->active )
                         $traitement = 'd' ; // d pour supprimer le produit
                       else
                         $traitement = '' ;
                    */


                    // Finalment non ! on supprime si le produit n'est plus actif !
                    // On supprime le produit quand il n'est plus en stock
                    // d pour supprimer le produit
                    // a pour ajouter le produit
                    if ($disabled || !$details->active) {
                        $traitement = 'd';
                    } else {
                        $traitement = 'a';
                    }

                    $fields = array();
                    $ean13 = $details->ean13;

                    if ((int)$ean13) {
                        $fields[] = sprintf('%013s', $ean13);
                        $typeid = 4;
                    } else {
                        $fields[] = '';//sprintf('%s', $reference);
                        $typeid = 3;
                    }
                    // 0         1           2     3         4     5     6          7              8                    9               10
                    //id-produit;sku-vendeur;prix;condition;stock;infos;traitement;type-id-produit;commentaire-interne;numero-vitrine;constructeur;
                    //EAN;sku-vendeur;prix;condition;stock;infos;traitement;type-id-produit;commentaire-interne;numero-vitrine;constructeur;img-principale;img-1;img-2;img-3;description;category-1;category-2;category-3

                    if (empty($reference)) {
                        continue;
                    }

                    if (is_array($price_limiter) &&
                        ($public_price < $price_limiter['down'] || $public_price > $price_limiter['up'])) {
                        continue;
                    }

                    $name_extention = '';
                    if ($name_ref) {
                        $name_extention = ' - '.$reference;
                    }

                    $fields[] = $reference;
                    $fields[] = '';
                    $fields[] = '';
                    $fields[] = '';
                    $fields[] = (is_array($details->name) ? $details->name[$id_lang] : $details->name).$name_extention;
//                    $fields[] = strip_tags(
//                        nl2br(
//                            FNAC_Tools::hfilter(str_replace('<br />', '<br>', is_array($details->description) ? $details->description[$this->id_lang] : $details->description))
//                        ),
//                        '<br>'
//                    );
                    $fields[] = strip_tags(
                        nl2br(
                            /*FNAC_Tools::hfilter*/(str_replace('<br />', '<br>', is_array($details->description) ? $details->description[$this->id_lang] : $details->description))
                        ),
                        '<br>'
                    );


                    $fields[] = $manufacturer_name;
                    $fields = array_merge(
                        $fields,
                        array_pad(array_slice(
                            FNAC_Tools::getProductImages($details->id, $combination['id_product_attribute'], $this->id_lang),
                            0,
                            4
                        ), 4, null)
                    );
                    $fields[] = '';
                    $fields[] = '';
                    $fields[] = $public_price;
                    $fields[] = $condition;
                    $fields[] = $quantity;
                    $fields[] = $text;
                    $fields[] = $traitement;
                    $fields[] = $typeid;
                    $fields[] = '';
                    $fields[] = '';

                    // Categories
                    $prd_categories = array_values(array_filter($details->getCategories(), function ($id_category) {
                        return $id_category != 2; // !acceuil
                    }));

                    // $categories_name
                    foreach ($prd_categories as $key => $id_category) {
                        $prd_categories[$key] = array_key_exists($id_category, $categories_name) ?
                            $categories_name[$id_category] : '';
                    }

                    $fields = array_merge(
                        $fields,
                        array_pad(array_slice(
                            $prd_categories,
                            0,
                            3
                        ), 3, null)
                    );

                    // Attributes
                    $attribute_combinations = $details->getAttributeCombinationsById($combination['id_product_attribute'], $id_lang);
                    if (count($attribute_combinations)) {
                        foreach ($attribute_combinations as $attribute_combination) {
                            if (!array_search($attribute_combination['group_name'], $header2)) {
                                $header2[] = $attribute_combination['group_name'];
                            }

                            $fields[$attribute_combination['group_name']] = $attribute_combination['attribute_name'];
                        }
                    }

                    // Features
                    foreach ($details->getFrontFeatures($id_lang) as $feature) {
                        if (!array_search($feature['name'], $header2)) {
                            $header2[] = $feature['name'];
                        }

                        $fields[$feature['name']] = $feature['value'];
                    }

                    // Logistic class
                    $fields[] = isset($logistic_type_ids[$categorieId]) && $logistic_type_ids[$categorieId] ?
                        $logistic_type_ids[$categorieId] : '';

//                    echo($header2);
//                    ddd($fields);
                    $fields2[] = $fields;
                    continue;

                    $limit = count($fields);
                    for ($i = 0; $i < $limit; $i++) {
                        $output .= str_replace(';', ':', $fields[$i]).';';
                    }
                    $output .= "\n";
                }
            }
        }

        $header = implode(';', $header2).";\n";
        $output = '';
        foreach ($fields2 as $fields) {
            foreach ($header2 as $hk => $hv) {
                if (in_array($hk, array_keys($fields))) {
                    $output .= '"'.str_replace(';', ':', $fields[$hk]).'";';
                } elseif (in_array($hv, array_keys($fields)) && array_key_exists($hv, $fields)) {
                    $output .= '"'.str_replace(';', ':', $fields[$hv]).'";';
                } else {
                    $output .= '"";';
                }
            }

            $output .= "\n";
        }
//        echo($header2);
//        ddd($fields2);

        if (file_put_contents($outputFile, $header.$output)) {
            echo '<a href="'.$outputUrl.'" alt="" >'.$outputUrl.'</a>';

            Configuration::updateValue('FNAC_LAST_IMPORTED', $currentDate);
        }
    }
}

$_FNAC_MassCSV = new FNAC_MassCSV();
$_FNAC_MassCSV->doIt();
