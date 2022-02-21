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

require_once(_PS_MODULE_DIR_.'cdiscount/classes/shared/configure_tab.class.php');

class AdminCDiscountProductsControllerExt extends CDiscount
{
    public $ps16x;
    public $categories = null;

    public function __construct()
    {
        $this->debug = (bool)Configuration::get(parent::KEY.'_DEBUG');
        $this->ps16x = version_compare(_PS_VERSION_, '1.6', '>=');
        $this->ps17x = version_compare(_PS_VERSION_, '1.7', '>=');

        parent::__construct();
    }

    public function content($smarty)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=') && Shop::isFeatureActive() && in_array($this->context->shop->getContext(), array(
                Shop::CONTEXT_GROUP,
                Shop::CONTEXT_ALL
            ))
        ) {
            $smarty->assign('shop_warning', $this->l('You are in multishop environment without any selected shop, this is wrong. Please select a shop to pursue normal operations.'));
        }

        // We build a category table only on PS 1.5+ because of the multishop feature
        if (version_compare(_PS_VERSION_, '1.5', '>=') && Shop::isFeatureActive()) {
            $categories = Category::getCategories((int)$this->id_lang, false);

            $id_shop = Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;

            $shop = new Shop($id_shop);
            $first = null;

            foreach ($categories as $categories1) {
                foreach ($categories1 as $category) {
                    if ($category['infos']['id_category'] == Category::getRootCategory(null, $shop)->id_category) {
                        $first = $category;
                    }
                }
            }

            $this->categories = Cdiscount::recurseCategoryForInclude(array(), $categories, $first, $shop->id_category);
        }

        $smarty->assign('ps16x', version_compare(_PS_VERSION_, '1.6', '>='));

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $smarty->assign('context_key', CDiscountContext::getKey($this->context->shop));
        } else {
            $smarty->assign('context_key', '0');
        }

        $smarty->assign('path', $this->url);
        $smarty->assign('images', $this->images);
        $smarty->assign('version', $this->version);

        $smarty->assign('products_js', $this->url.'views/js/products.js');
        $smarty->assign('image_loading', $this->url.'views/img/loading.gif');
        $smarty->assign('expert_mode', (bool)Configuration::get(self::KEY.'_EXPERT_MODE'));
        
        $selected_tab = (Tools::getValue('selected_tab') ? Tools::getValue('selected_tab') : 'menu-update');

        $smarty->assign('tab_selected_update', $selected_tab == 'update' ? 'selected' : '');
        $smarty->assign('tab_selected_create', $selected_tab == 'create' ? 'selected' : '');
        $smarty->assign('tab_selected_csv', $selected_tab == 'csv' ? 'selected' : '');
        $smarty->assign('tab_selected_debug', $selected_tab == 'debug' ? 'selected' : '');

        if ($this->debug) {
            $smarty->assign('debug', $this->debug);
        }

        if (method_exists($smarty, 'setTemplateDir')) {
            $currentTemplates = $smarty->getTemplateDir();
            $additionnalTemplates = array($this->path.'views/templates/admin/catalog/');
            $smarty->setTemplateDir(is_array($currentTemplates) ? array_merge($currentTemplates, $additionnalTemplates) : $additionnalTemplates);
        } else {
            $smarty->template_dir = $this->path.'views/templates/admin/catalog/';
        }

        $smarty->assign('tab_create_data', $this->_create());
        $smarty->assign('tab_update_data', $this->_update());
        $smarty->assign('tab_csv_data', $this->_csv());
        $smarty->assign('module_url', _PS_MODULE_DIR_.parent::MODULE.'/');

        if ($this->debug) {
            $smarty->assign('tab_debug_data', $this->_debug());
        }

        $view_params = array();
        $view_params['alert_class'] = array();
        $view_params['alert_class']['danger'] = $this->ps16x ? 'alert alert-danger' : 'error';
        $view_params['alert_class']['warning'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        $view_params['alert_class']['success'] = $this->ps16x ? 'alert alert-success' : 'conf';
        $view_params['alert_class']['info'] = $this->ps16x ? 'alert alert-info' : 'info';
        $smarty->assign($view_params);

        $html = '';
        $tabList = array();
        $tabList[] = array('id' => 'update', 'img' => 'update', 'name' => $this->l('Update Mode'), 'selected' => true);
        $tabList[] = array('id' => 'create', 'img' => 'add', 'name' => $this->l('Creation Mode'), 'selected' => false);
        $tabList[] = array(
            'id' => 'csv',
            'img' => 'file_extension_csv',
            'name' => $this->l('CSV Mode'),
            'selected' => false
        );
        if ($this->debug) {
            $tabList[] = array('id' => 'debug', 'img' => 'bug', 'name' => $this->l('Debug Mode'), 'selected' => false);
        }

        $html .= $smarty->fetch($this->path.'views/templates/admin/catalog/header.tpl');
        $html .= ConfigureTab::generateTabs($tabList, 'cdiscount');
        $html .= $smarty->fetch($this->path.'views/templates/admin/catalog/AdminCatalogCDiscount.tpl');

        return ($html);
    }

    private function _update()
    {
        $smarty_data = array();
        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'update';

        $smarty_data['tab_selected_update'] = $selected_tab == 'update' ? 'selected' : '';

        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];

        $token = parent::decode(Configuration::get(parent::KEY.'_PS_TOKEN'));

        require_once(_PS_MODULE_DIR_.'cdiscount/classes/cdiscount.config.class.php');

        // Collect merchant data from cache or webservice
        //
        self::$seller_informations = CDiscountConfig::getSellerInformation($this->debug);

        //$smarty_data['multitenants'] = parent::multitenantGetList();
        $smarty_data['multitenants'] = null;
        $smarty_data['products_url'] = $this->url.'functions/products_update.php?cdtoken='.$token;
        $smarty_data['current_date'] = date('Y-m-d');

        return $smarty_data;
    }

    private function _create()
    {
        $smarty_data = array();
        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'update';

        $smarty_data['tab_selected_create'] = $selected_tab == 'menu-create' ? 'selected' : '';
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];

        require_once(_PS_MODULE_DIR_.'cdiscount/classes/cdiscount.configuration.class.php');

        $default_categories = CDiscountConfiguration::get('categories');
        $default_profiles2categories = CDiscountConfiguration::get('profiles_categories');
        $default_profiles = $this->loadProfiles();

        $last_export = unserialize(parent::decode(Configuration::get(parent::KEY.'_LAST_EXPORT')));

        if (!$last_export) {
            $vars = explode(' ', CDiscountTools::oldest());
            $last_export = $vars[0];
            $last_export_title = sprintf('%s : %s', $this->l('Last Export'), $this->l('Never'));
        } else {
            $last_export_title = sprintf('%s : %s', $this->l('Last Export'), $last_export);
            $vars = explode(' ', CDiscountTools::oldest());
            $last_export = $vars[0];
        }

        $smarty_data['last_export'] = $last_export;
        $smarty_data['last_export_title'] = $last_export_title;
        $smarty_data['current_date'] = date('Y-m-d');
        $smarty_data['categories'] = array();


        if (is_array($default_categories)) {
            foreach ($default_categories as $id_category) {
                $profile_name = null;

                // Check category is in shop
                if (is_array($this->categories) && !array_key_exists($id_category, $this->categories)) {
                    continue;
                }

                if (isset($default_profiles2categories[$id_category]) && !empty($default_profiles2categories[$id_category])) {
                    foreach ($default_profiles['name'] as $profile) {
                        if ($profile == $default_profiles2categories[$id_category]) {
                            $profile_name = $profile;
                        }
                    }
                }
                if (!$profile_name) {
                    continue;
                }

                $cat_array = array();
                $cat_array['id_category'] = $id_category;
                $cat_array['desc_category'] = CDiscountTools::cPath($id_category, $this->id_lang);
                $cat_array['profile_name'] = $profile_name;
                $smarty_data['categories'][] = $cat_array;
            }
        }

        $token = CDiscount::decode(Configuration::get(parent::KEY.'_PS_TOKEN'));
        $smarty_data['products_create_url'] = $this->url.'functions/products_create.php?cdtoken='.$token;
        $smarty_data['products_merge_url'] = $this->url.'functions/merge.php?cdtoken='.$token;

        return $smarty_data;
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        $specific = basename(__FILE__, '.php');

        return (parent::l($string, $specific, $id_lang));
    }

    private function _csv()
    {
        $smarty_data = array();
        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'update';

        $smarty_data['tab_selected_csv'] = $selected_tab == 'menu-csv' ? 'selected' : '';
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];

        require_once(_PS_MODULE_DIR_.'cdiscount/classes/cdiscount.configuration.class.php');

        $default_categories = CDiscountConfiguration::get('categories');
        $default_profiles2categories = CDiscountConfiguration::get('profiles_categories');
        $default_profiles = $this->loadProfiles();

        $vars = explode(' ', CdiscountTools::oldest());
        $last_export = str_replace('/', '-', $vars[0]);


        if (!$last_export) {
            $vars = explode(' ', CDiscountTools::oldest());
            $last_export = str_replace('/', '-', $vars[0]);
            $last_export_title = sprintf('%s : %s', $this->l('Last Export'), $this->l('Never'));
        } else {
            $last_export_title = sprintf('%s : %s', $this->l('Last Export'), $last_export);
            //$vars = explode(' ', $last_export) ;
            $vars = explode(' ', CDiscountTools::oldest());
            $last_export = str_replace('/', '-', $vars[0]);
        }

        $smarty_data['last_export'] = $last_export;
        $smarty_data['last_export_title'] = $last_export_title;
        $smarty_data['current_date'] = date('Y-m-d');
        $smarty_data['categories'] = array();

        if (is_array($default_categories)) {
            foreach ($default_categories as $id_category) {
                $profile_name = null;

                // Check category is in shop
                if (is_array($this->categories) && !array_key_exists($id_category, $this->categories)) {
                    continue;
                }

                if (isset($default_profiles2categories[$id_category]) && !empty($default_profiles2categories[$id_category])) {
                    foreach ($default_profiles['name'] as $profile) {
                        if ($profile == $default_profiles2categories[$id_category]) {
                            $profile_name = $profile;
                        }
                    }
                }
                if (!$profile_name) {
                    continue;
                }

                $cat_array = array();
                $cat_array['id_category'] = $id_category;
                $cat_array['desc_category'] = CDiscountTools::cPath($id_category, $this->id_lang);
                $cat_array['profile_name'] = $profile_name;
                $smarty_data['categories'][] = $cat_array;
            }
        }

        $token = CDiscount::decode(Configuration::get(parent::KEY.'_PS_TOKEN'));
        $smarty_data['products_csv_url'] = $this->url.'functions/products_create.php?action=export_csv&cdtoken='.$token;

        return $smarty_data;
    }


    private function _debug()
    {
        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'update';
        $smarty_data = array();
        $smarty_data['tab_selected_debug'] = $selected_tab == 'debug' ? 'selected' : '';
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];

        $token = parent::decode(Configuration::get(parent::KEY.'_PS_TOKEN'));

        return $smarty_data;
    }
}
