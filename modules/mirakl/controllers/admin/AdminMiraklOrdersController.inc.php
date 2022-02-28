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

class AdminMiraklOrdersControllerExt
{
    public $name   = null;
    public $module = null;

    private $context_data;

    public function __construct($id_lang, $context_data = null, $name = null)
    {
        $this->name = $name;

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
        $smarty->assign('js_file', $this->marketplace->name.'/views/js/orders_common.js');
        $smarty->assign('image_loading', $this->marketplace->url.'/views/img/loading.gif');
        $display = ' style="display:none" ';

        $smarty->assign('marketplace_logo', isset(Mirakl::$marketplace_params['marketplace_logo']) ? Mirakl::$marketplace_params['marketplace_logo'] : null);

        if ($this->marketplace->debug) {
            $smarty->assign('debug', '1');
            $display = ' style="display:none;" ';
        } else {
            $display = ' style="display:none;visibility:hidden;" ';
        }

        $smarty->assign('console_display', $display);
        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'accept';

        $smarty->assign('tab_selected_accept', $selected_tab == 'accept' ? 'selected' : '');
        $smarty->assign('tab_selected_import', $selected_tab == 'import' ? 'selected' : '');
        $smarty->assign('tab_selected_debug', $selected_tab == 'debug' ? 'selected' : '');

        $alert_class = array();
        $alert_class['danger'] = $this->marketplace->ps16x ? 'alert alert-danger' : 'error';
        $alert_class['warning'] = $this->marketplace->ps16x ? 'alert alert-warning' : 'warn';
        $alert_class['success'] = $this->marketplace->ps16x ? 'alert alert-success' : 'conf';
        $alert_class['info'] = $this->marketplace->ps16x ? 'alert alert-info' : 'info';

        $smarty->assign('alert_class', $alert_class);

        if (method_exists($smarty, 'setTemplateDir')) {
            $current_templates = $smarty->getTemplateDir();
            $additionnal_templates = array($this->marketplace->path.'views/templates/admin/orders/');
            $smarty->setTemplateDir(is_array($current_templates) ? array_merge($current_templates, $additionnal_templates) : $additionnal_templates);
        } else {
            $smarty->template_dir = $this->marketplace->path.'views/templates/admin/orders/';
        }

        $smarty->assign('tab_accept_data', $this->accept());
        $smarty->assign('tab_import_data', $this->import());

        if ($this->marketplace->debug) {
            $smarty->assign('tab_debug_data', $this->debug());
        }

        $tab_list = array(
            array('id' => 'accept', 'img' => 'accept', 'name' => $this->l('Accept Orders'), 'selected' => true),
            array('id' => 'import', 'img' => 'document_import', 'name' => $this->l('Import Orders'), 'selected' => false)
        );

        $smarty->assign('menuTab', ConfigureTab::generateTabs($tab_list, $this->name));
        $smarty->assign('mkps', MiraklMarketplace::getMarketplaces());

        return $smarty->fetch($this->marketplace->path.'views/templates/admin/orders/AdminOrdersMirakl.tpl');
    }

    private function accept()
    {
        $smarty_data = array();

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'accept';
        $smarty_data['selected_tab'] = ($selected_tab == 'accept' ? 'selected' : '');
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];
        $smarty_data['start_date'] = date('Y-m-d', strtotime('now -7 days'));
        $smarty_data['current_date'] = date('Y-m-d');

        $token = Mirakl::getConfigGlobalMarketplace(Mirakl::CONFIG_PS_TOKEN, true);

        $smarty_data['accept_orders_url'] = $this->marketplace->url.'functions/orders_accept.php?action=orders&metoken='.$token;
        $smarty_data['accept_order_url'] = $this->marketplace->url.'functions/orders_accept.php?action=accept&metoken='.$token;

        return $smarty_data;
    }

    private function import()
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            require_once(dirname(__FILE__).'/../../backward_compatibility/backward.php');
        }

        $cookie = Context::getContext()->cookie;
        $smarty_data = array();

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'accept';
        $smarty_data['selected_tab'] = ($selected_tab == 'import' ? 'selected' : '');
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];
        $smarty_data['start_date'] = date('Y-m-d', strtotime('now -7 days'));
        $smarty_data['current_date'] = date('Y-m-d');
        $smarty_data['token'] = Mirakl::getConfigGlobalMarketplace(Mirakl::CONFIG_PS_TOKEN, true);
        $smarty_data['import_orders_url'] = $this->marketplace->url.'functions/orders_import.php?action=orders&metoken='.$smarty_data['token'].'&id_lang='.$cookie->id_lang;
        $smarty_data['import_order_url'] = $this->marketplace->url.'/functions/orders_import.php?action=import&metoken='.$smarty_data['token'];
        $smarty_data['token_orders'] = Tools::getAdminToken('AdminOrders'.(int)Tab::getIdFromClassName('AdminOrders').(int)$cookie->id_employee);

        if (!$last_import = Mirakl::getConfig(Mirakl::CONFIG_LAST_IMPORT, true)) {
            $smarty_data['last_import_title'] = sprintf('%s : %s', $this->l('Last Import'), $this->l('Never'));
        } else {
            $smarty_data['last_import_title'] = sprintf('%s : %s', $this->l('Last Import'), $last_import);
        }

        return $smarty_data;
    }

    private function debug()
    {
        $smarty_data = array();

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'update';
        $smarty_data['selected_tab'] = ($selected_tab == 'debug' ? 'selected' : '');
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];
        $smarty_data['token'] = Mirakl::getConfigGlobalMarketplace(Mirakl::CONFIG_PS_TOKEN, true);
        $smarty_data['debug_orders_url'] = $this->marketplace->url.'/functions/orders_debug.php?metoken='.$smarty_data['token'];

        return $smarty_data;
    }
}
