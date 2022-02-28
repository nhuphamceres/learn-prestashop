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
 *
 * @author    Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 *  Support by mail  :  support.mirakl@common-services.com
 */

require_once(dirname(__FILE__).'/../../classes/context.class.php');

class AdminMiraklProductsControllerExt
{
    public $name        = null;
    public $module      = null;
    public $marketplace = null;

    public function __construct($id_lang, $context_data = null, $name = null)
    {
        $this->name = $name;

        /** @var Mirakl marketplace */
        $this->marketplace = new $name();
        $this->marketplace->debug = (bool)Mirakl::getConfig(Mirakl::CONFIG_DEBUG);

        $this->context_data = $context_data;
    }

    public function l($string, $class = null, $addslashes = false, $htmlentities = true)
    {
        if (_PS_VERSION_ < '1.5') {
            return ($string);
        } else {
            return (Translate::getModuleTranslation($this->name, $string, basename(__FILE__, '.php')));
        }
    }

    /**
     * @param Smarty $smarty
     * @return mixed
     */
    public function content($smarty)
    {
        require_once(_PS_MODULE_DIR_.$this->name.'/classes/shared/configure_tab.class.php');

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $smarty->assign('context_key', MiraklContext::getKey($this->context_data->shop));
        }

        $smarty->assign('path', $this->marketplace->url);
        $smarty->assign('images', $this->marketplace->images);

        $smarty->assign('products_js', $this->marketplace->url.'/views/js/products.js');
        $smarty->assign('image_loading', $this->marketplace->url.'/views/img/loading.gif');
        $smarty->assign('marketplace_logo', isset(Mirakl::$marketplace_params['marketplace_logo']) ? Mirakl::$marketplace_params['marketplace_logo'] : null);

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'update';

        $smarty->assign('tab_selected_update', $selected_tab == 'update' ? 'selected' : '');

        if ($this->marketplace->debug) {
            $smarty->assign('debug', $this->marketplace->debug);
        }

        if (method_exists($smarty, 'setTemplateDir')) {
            $current_templates = $smarty->getTemplateDir();
            $additionnal_templates = array($this->marketplace->path.'views/templates/admin/catalog/');
            $smarty->setTemplateDir(is_array($current_templates) ? array_merge($current_templates, $additionnal_templates) : $additionnal_templates);
        } else {
            $smarty->template_dir = $this->marketplace->path.'views/templates/admin/catalog/';
        }

        $smarty->assign('tab_update_data', $this->update());
        $smarty->assign('tab_create_data', $this->create());

        if ($this->marketplace->debug) {
            $smarty->assign('tab_debug_data', $this->debug());
        }

        $alert_class = array();
        $alert_class['danger'] = $this->marketplace->ps16x ? 'alert alert-danger' : 'error';
        $alert_class['warning'] = $this->marketplace->ps16x ? 'alert alert-warning' : 'warn';
        $alert_class['success'] = $this->marketplace->ps16x ? 'alert alert-success' : 'conf';
        $alert_class['info'] = $this->marketplace->ps16x ? 'alert alert-info' : 'info';
        $smarty->assign('alert_class', $alert_class);

        $tab_list = array(
            array('id' => 'update', 'img' => 'update', 'name' => $this->l('Update Mode'), 'selected' => true),
            array('id' => 'create', 'img' => 'file_extension_csv', 'name' => $this->l('Create CSV'), 'selected' => false)
        );

        $smarty->assign('menuTab', ConfigureTab::generateTabs($tab_list, $this->name));
        $smarty->assign('mkps', MiraklMarketplace::getMarketplaces());

        return $smarty->fetch($this->marketplace->path.'views/templates/admin/catalog/AdminCatalogMirakl.tpl');
    }

    private function update()
    {
        $smarty_data = array();

        if (!$last_export = Mirakl::getConfig(Mirakl::CONFIG_LAST_UPDATE)) {
            $vars = explode(' ', MiraklTools::oldest());
            $last_export = str_replace('/', '-', $vars[0]);
            $last_export_title = sprintf('%s : %s', $this->l('Last Update'), $this->l('Never'));
        } else {
            $last_export_title = sprintf('%s : %s', $this->l('Last Update'), $last_export);
            $vars = explode(' ', $last_export);
            $last_export = $vars[0];
        }

        if ($last_cron_export = Mirakl::getConfig(Mirakl::CONFIG_LAST_UPDATE_CRON)) {
            if (!Validate::isDate($last_cron_export)) {
                $last_cron_export_title = sprintf(' / %s : %s', $this->l('Last Update'), $this->l('Never'));
            } else {
                $last_cron_export_title = sprintf(' / %s : %s', $this->l('Last Update'), $last_cron_export);
            }
        } else {
            $last_cron_export_title = null;
        }

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'update';

        $smarty_data['tab_selected_update'] = $selected_tab == 'update' ? 'selected' : '';
        $smarty_data['last_export_title'] = $last_export_title;
        $smarty_data['last_cron_export_title'] = $last_cron_export_title;
        $smarty_data['last_export'] = $last_export;
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];

        $token = Mirakl::getConfigGlobalMarketplace(Mirakl::CONFIG_PS_TOKEN, true);

        $smarty_data['products_url'] = $this->marketplace->url.'functions/products_update.php?action=export&metoken='.$token;
        $smarty_data['current_date'] = date('Y-m-d');

        return $smarty_data;
    }

    private function debug()
    {
        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'update';
        $smarty_data = array();
        $smarty_data['tab_selected_debug'] = $selected_tab == 'debug' ? 'selected' : '';
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];

        $token = Mirakl::getConfigGlobalMarketplace(Mirakl::CONFIG_PS_TOKEN, true);
        $smarty_data['debug_products_url'] = $this->marketplace->url.'/functions/products_debug.php?metoken='.$token;

        return $smarty_data;
    }


    private function create()
    {
        $smarty_data = array();

        if (!$last_export = Mirakl::getConfig(Mirakl::CONFIG_LAST_CREATE)) {
            $vars = explode(' ', MiraklTools::oldest());
            $last_export = str_replace('/', '-', $vars[0]);
            $last_export_title = sprintf('%s : %s', $this->l('Last Create'), $this->l('Never'));
        } else {
            $last_export_title = sprintf('%s : %s', $this->l('Last Create'), $last_export);
            $vars = explode(' ', $last_export);
            $last_export = $vars[0];
        }

        if ($last_cron_export = Mirakl::getConfig(Mirakl::CONFIG_LAST_CREATE_CRON)) {
            if (!Validate::isDate($last_cron_export)) {
                $last_cron_export_title = sprintf(' / %s : %s', $this->l('Last Create'), $this->l('Never'));
            } else {
                $last_cron_export_title = sprintf(' / %s : %s', $this->l('Last Create'), $last_cron_export);
            }
        } else {
            $last_cron_export_title = null;
        }

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'create';

        $smarty_data['tab_selected_create'] = $selected_tab == 'create' ? 'selected' : '';
        $smarty_data['last_export_title'] = $last_export_title;
        $smarty_data['last_cron_export_title'] = $last_cron_export_title;
        $smarty_data['last_export'] = $last_export;
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];

        $token = Mirakl::getConfigGlobalMarketplace(Mirakl::CONFIG_PS_TOKEN, true);

        $smarty_data['products_url'] = $this->marketplace->url.'functions/products_create.php?action=export&metoken='.$token;
        $smarty_data['current_date'] = date('Y-m-d');

        // Categories
        // Disabled for now has it is not possible to load categories for each marketplace configuration
        // All is exported
        /*
        $default_categories = (array)Mirakl::getConfig(Mirakl::CONFIG_CATEGORIES);

        $categories = array();
        $root_category = Category::getRootCategory();
        $root_category_id = $root_category->id;

        foreach ($default_categories as $id_category) {
            if ($id_category == $root_category_id) {
                continue;
            }

            $categories[$id_category] = $this->cPath($id_category);
        }

        $smarty_data['categories'] = $categories;
        */

        return $smarty_data;
    }

    /**
     * @param $id_category
     * @param bool $id_lang
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function cPath($id_category, $id_lang = false)
    {
        $c = new Category($id_category);

        if (!$id_lang) {
            $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        }

        $category = '';
        if ($c->id_parent && $c->id_parent != 1) {
            $category .= self::cPath($c->id_parent, $id_lang).' > ';
        }

        if (is_array($c->name)) {
            if (isset($c->name[$id_lang])) {
                $category .= $c->name[$id_lang];
            } else {
                $category .= reset($c->name);
            }
        } else {
            $category .= $c->name;
        }

        return (rtrim($category, ' > '));
    }
}
