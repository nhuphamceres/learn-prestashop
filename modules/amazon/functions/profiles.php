<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Feed.biz
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Feed.biz is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Feed.biz.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 *
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

require_once(dirname(__FILE__).'/env.php');

require_once(dirname(__FILE__).'/../classes/amazon.tools.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.specificfield.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.valid_values.class.php');

require_once(dirname(__FILE__).'/../amazon.php');

class AmazonProfiles extends Amazon
{
    public $errors;

    public function __construct()
    {
        parent::__construct();

        AmazonContext::restore($this->context);

        if (Amazon::$debug_mode) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }
    }

    public function dispatch()
    {
        $callback = Tools::getValue('callback');
        $action = Tools::getValue('action');
        
        if ($callback == '?') {
            $callback = 'jsonp_'.time();
        }

        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s:%s - %s', basename(__FILE__), __LINE__, $action));
        }

        switch ($action) {
            case 'get_profile':
                $this->getProfileByKey();
                break;
            default:
                die($this->l('No action was choose'));
        }
        
        $console = ob_get_clean();

        $data = array('profiles' => null, 'errors' => $this->errors, 'console' => $console);
        echo $callback.'('.Tools::jsonEncode($data).')';
        die;
    }

    public function getProfileByKey()
    {
        $html = '';

        $profile_id = Tools::getValue('profile_id');
          
        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s:%s - profile_id : %s', basename(__FILE__), __LINE__, $profile_id));
        }
        
        $profiles = AmazonConfiguration::get('profiles');
        $profiles = AmazonSpecificField::migrateProfilesFromV3($profiles);
        
        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s:%s - profiles : %s', basename(__FILE__), __LINE__, print_r($profiles, true)));
        }
        
        if (!isset($profiles['name'])) {
            $profiles['name'] = array();
        }
        
        // Amazon Features
        $feature = $this->amazon_features;
        
        // Shipping Configuration
        //
        $feature['shipping'] = AmazonConfiguration::get('SHIPPING');
        $feature['shipping_methods'] = AmazonConfiguration::get('SHIPPING_METHODS');
        
        // Region
        $regions = AmazonConfiguration::get('REGION');
            
        // Currency
        $current_currency = Currency::getDefaultCurrency();
        $empty_price_rule = array(
                'currency_sign' => isset($current_currency->sign) ? $current_currency->sign : null,
                'type' => 'percent',
                'rule' => array(
                        'from'      => array(0 => ''),
                        'to'        => array(0 => ''),
                        'percent'   => array(0 => ''),
                        'value'     => array(0 => '')
                )
        );
        
        // Language
        $languages = AmazonTools::languages();

        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s:%s - languages :  %s', basename(__FILE__), __LINE__, print_r($languages, true)));
        }
        
        // Country
        $marketplace_countries = AmazonSpecificField::countrySelector();
        
        // Bullet point stragety
        $current_description_strategy = Configuration::get('AMAZON_DESCRIPTION_FIELD');
        $bullet_point_strategy_shortd = $current_description_strategy
            && !in_array($current_description_strategy, 
            array(Amazon::FIELD_DESCRIPTION_SHORT, Amazon::FIELD_DESCRIPTION_BOTH));
        
        $view_params = array();
        $view_params['profiles'] = array(
            'config'        => array(),
            'module_path'   => $this->path,
            'images_url'    => $this->images,
            'expert_mode'   => (bool)$feature['expert_mode'],
            'tutorial'      => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_PROFILES),
            'xsd_path'           => str_replace('\\', '/', realpath(dirname(__FILE__))).'/xsd/',
            'xsd_operations_url' => $this->url.'functions/xsd_operations.php',
            'amazon_profiles_url' => $this->url.'functions/profiles.php',
            'xsd_ajax_error'     => $this->l('An unexpected server side error occured').$this->l('In most cases, this is a permission problem. Please apply write permission (777) to amazon/validate/xsd directory.'),
            'error_profile_name' => $this->l('First, you must enter a profile name'),
            'exemptions' => array(
                'none'              => self::EXEMPTION_NONE,
                'compatibility'     => self::EXEMPTION_COMPATIBILITY,
                'model_number'      => self::EXEMPTION_MODEL_NUMBER,
                'model_name'        => self::EXEMPTION_MODEL_NAME,
                'mfr_part_number'   => self::EXEMPTION_MFR_PART_NUMBER,
                'catalog_number'    => self::EXEMPTION_CATALOG_NUMBER,
                'style_number'      => self::EXEMPTION_STYLE_NUMBER,
                'attr_ean'          => self::EXEMPTION_ATTR_EAN,
                'generic'           => self::EXEMPTION_GENERIC
            ),
            'categories_english' => Tools::strtolower(Language::getIsoById($this->id_lang)) === 'fr',
            'marketplaces' => array(
                'countries' => $marketplace_countries,
                'show'      => is_array($marketplace_countries) && count($marketplace_countries) > 1
            ),
            'bullet_point_strategy_a'   => self::BULLET_POINT_STRATEGY_ATTRIBUTES,
                'bullet_point_strategy_af'  => self::BULLET_POINT_STRATEGY_ATTRIBUTES_FEATURES,
            'bullet_point_strategy_f'   => self::BULLET_POINT_STRATEGY_FEATURES,
                'bullet_point_strategy_d'   => self::BULLET_POINT_STRATEGY_DESC,
                'bullet_point_strategy_daf' => self::BULLET_POINT_STRATEGY_DESC_ATTRIBUTES_FEATURES,
                'bullet_point_strategy_df'  => self::BULLET_POINT_STRATEGY_DESC_FEATURES,
            'bullet_point_strategy_shortd' => $bullet_point_strategy_shortd,
            'universes' => array(),
            'empty_profile_header' => array(
                'name'       => null,
                'profile_id' => 0
            )
        );
        
        $actives = AmazonConfiguration::get('ACTIVE');
    
        foreach ($languages as $language) {
            $id_lang = $language['id_lang'];
            
            // Repricing Strategies
            $strategies = $this->getStrategies($id_lang);

            if (isset($regions) && is_array($regions) && isset($regions[$id_lang])) {
                $region = $regions[$id_lang];
            } else {
                $region = null;
            }
            
            if (!(isset($actives[$id_lang]) && (int)$actives[$id_lang])) {
                continue;
            }
            
            $view_params['profile'] = array();
            $view_params['id_lang'] = $id_lang;
            
            $view_params['universes'][$id_lang] = AmazonSpecificField::universes($region);
            
            $profile_name = isset($profiles['name'][$profile_id]) ? $profiles['name'][$profile_id] : null;

            if (!Tools::strlen($profile_key = AmazonTools::toKey($profile_name)) && $profile_id != 65535) {
                return ;
            }
            
            $view_params['profile_key'] = $profile_key;
            
            $p_universe = isset($profiles['universe'][$profile_id][$id_lang]) ? $profiles['universe'][$profile_id][$id_lang] : '';

            $p_product_type = isset($profiles['product_type'][$profile_id][$id_lang]) ? $profiles['product_type'][$profile_id][$id_lang] : '';
            $p_extra = isset($profiles['extra'][$profile_key][$id_lang]) ? $profiles['extra'][$profile_key][$id_lang] : '';

            if (!$product_type_translation = AmazonSettings::getProductTypeTranslation($region, $p_universe, $p_product_type)) {
                $product_type_translation = $p_product_type;
            }

            if ($p_universe && $p_product_type) {
                $type = sprintf('%s&nbsp;&gt;&nbsp;%s', $p_universe, $p_product_type);
            } else {
                $type = $this->l('ERROR');
            }
            
            $view_params['profile']['profile_id'] = $profile_id;
            $view_params['profile']['universe'] = $p_universe;
            $view_params['profile']['product_type'] = $p_product_type;
            $view_params['profile']['product_type_translation'] = $product_type_translation;
            $view_params['profile']['type'] = $type;

            $view_params['profile']['latency'] = isset($profiles['latency'][$profile_id][$id_lang]) ? $profiles['latency'][$profile_id][$id_lang] : '';
            $view_params['profile']['combinations'] = isset($profiles['combinations'][$profile_id][$id_lang]) ? $profiles['combinations'][$profile_id][$id_lang] : '';

            $view_params['profile']['code_exemption'] = isset($profiles['code_exemption'][$profile_id][$id_lang]) ? $profiles['code_exemption'][$profile_id][$id_lang] : Amazon::EXEMPTION_NONE;

            if (isset($profiles['code_exemption'][$profile_id][$id_lang]) && $profiles['code_exemption'][$profile_id][$id_lang] == Amazon::EXEMPTION_COMPATIBILITY) {
                $view_params['profile']['code_exemption_options'] = array('private_label' => true);
            } else {
                $view_params['profile']['code_exemption_options'] = isset($profiles['code_exemption_options'][$profile_id][$id_lang]) ? $profiles['code_exemption_options'][$profile_id][$id_lang] : null;
            }

            $view_params['profile']['sku_as_supplier_reference'] = isset($profiles['sku_as_supplier_reference'][$profile_id][$id_lang]) ? $profiles['sku_as_supplier_reference'][$profile_id][$id_lang] : 0;
            $view_params['profile']['sku_as_sup_ref_unconditionnaly'] = isset($profiles['sku_as_sup_ref_unconditionnaly'][$profile_id][$id_lang]) ? $profiles['sku_as_sup_ref_unconditionnaly'][$profile_id][$id_lang] : 0;

            $view_params['profile']['item_type'] = isset($profiles['item_type'][$profile_id][$id_lang]) ? $profiles['item_type'][$profile_id][$id_lang] : '';

            if (isset($profiles['price_rule'][$profile_id][$id_lang]) && is_array($profiles['price_rule'][$profile_id][$id_lang])
                && isset($profiles['price_rule'][$profile_id][$id_lang]['rule']['from']) && is_array($profiles['price_rule'][$profile_id][$id_lang]['rule']['from'])
                && isset($profiles['price_rule'][$profile_id][$id_lang]['rule']['to']) && is_array($profiles['price_rule'][$profile_id][$id_lang]['rule']['to']))
            {
                $view_params['profile']['price_rule']['currency_sign'] = isset($current_currency->sign) ? $current_currency->sign : null;
                $view_params['profile']['price_rule']['type'] = isset($profiles['price_rule'][$profile_id][$id_lang]['type']) ? $profiles['price_rule'][$profile_id][$id_lang]['type'] : 'percent';
                $view_params['profile']['price_rule']['rule'] = $profiles['price_rule'][$profile_id][$id_lang]['rule'];
                // Jan-21-2019: Profile's price rules have higher priority than marketplace's price rules.
            } else {
                // first use
                $view_params['profile']['price_rule'] = $empty_price_rule;
            }

            $view_params['profile']['universe'] = $p_universe;
            $view_params['profile']['specifics'] = AmazonSpecificField::displayFields($id_lang, $profile_name, $p_extra);
            $view_params['profile']['bullet_point_strategy'] = isset($profiles['bullet_point_strategy'][$profile_id][$id_lang]) ? $profiles['bullet_point_strategy'][$profile_id][$id_lang] : null;
            $view_params['profile']['bullet_point_labels'] = isset($profiles['bullet_point_labels'][$profile_id][$id_lang]) ? $profiles['bullet_point_labels'][$profile_id][$id_lang] : null;

            $p_browsenode = isset($profiles['browsenode'][$profile_id][$id_lang]) ? $profiles['browsenode'][$profile_id][$id_lang] : null;

            $view_params['profile']['browse_node'] = str_replace(array(
                ';',
                ':',
                '-',
                ','
            ), ', ', $p_browsenode);

            $view_params['profile']['strategies'] = $strategies;
            $view_params['profile']['repricing'] = isset($profiles['repricing'][$profile_id][$id_lang]) ? $profiles['repricing'][$profile_id][$id_lang] : '';
            
            if ($feature['shipping']) {
                // Shipping Templates
                $view_params['profile']['shipping_group'] = isset($profiles['shipping_group'][$profile_id][$id_lang]) ? $profiles['shipping_group'][$profile_id][$id_lang] : null;

                $view_params['profile']['shipping_templates']['enabled'] = $shipping_templates = is_array($feature['shipping']) && isset($feature['shipping']['shipping_templates']) && (bool)$feature['shipping']['shipping_templates'];
                $view_params['profile']['shipping_templates']['groups'][$id_lang] = array();

                if ($shipping_templates) {
                    $configured_group_names = AmazonConfiguration::get('shipping_groups');

                    if (is_array($configured_group_names) && count($configured_group_names)) {
                        foreach ($configured_group_names as $group_region => $group_names) {
                            if ($group_region != $region) {
                                continue;
                            }

                            $view_params['profile']['shipping_templates']['groups'][$id_lang] = array();

                            if (is_array($group_names) && count($group_names)) {
                                foreach ($group_names as $group_key => $group_name) {
                                    $view_params['profile']['shipping_templates']['groups'][$id_lang][$group_key] = $group_name;
                                }
                            }
                        }
                    }
                }
            }
            
            $view_params['profile']['ptc'] = AmazonTaxes::getPtcList($region);
            $view_params['profile']['ptc_selected'] = isset($profiles['ptc'][$profile_id][$id_lang]) ? $profiles['ptc'][$profile_id][$id_lang] : null;
            
            $view_params['marketplace'] = isset($marketplace_countries[$id_lang]) ? $marketplace_countries[$id_lang] : null ;
            
            $this->context->smarty->assign($view_params);

            $html .= '<div class="col-lg-12 amazon-tab amazon-tab-'.$id_lang.'" rel="'.$language['iso_code'].'" ';
            if (!isset($view_params['profiles']['marketplaces']['countries'][$id_lang]['default']) 
                    || !$view_params['profiles']['marketplaces']['countries'][$id_lang]['default']) {
                $html .= 'style="display:none"';
            }
            $html .= '>';
            $html .= $this->context->smarty->fetch($this->path.'views/templates/admin/configure/profiles/profile_body.tpl');
            $html .= '</div>';
        }
        
        if (Tools::strlen($html)){
            if (Amazon::$debug_mode) {
                $data = array('profiles' => $html, 'errors' => $this->errors);
                CommonTools::p(sprintf('%s:%s - ', basename(__FILE__), __LINE__));
                CommonTools::p('Return : ' . print_r($data, true));
                die;
            } else {
                echo html_entity_decode($html);
                die;
            }
        } 
        
        $this->errors = $this->l('Empty profiles');
        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s:%s - ', basename(__FILE__), __LINE__));
            CommonTools::p($this->errors);
        }
        
        return false;
    }
}

$amazonProfiles = new AmazonProfiles();
$amazonProfiles->dispatch();
