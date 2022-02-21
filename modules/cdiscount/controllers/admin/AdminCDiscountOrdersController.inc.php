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

class AdminCDiscountOrdersControllerExt extends CDiscount
{
    const URL_PROD_LOGIN = 'https://seller.cdiscount.com/login.html';
    const URL_PROD_ORDERS = 'https://seller.cdiscount.com/Orders.html';
    const URL_PROD_HOME = 'https://seller.cdiscount.com/home.html';
    const URL_PROD_OFFERS = 'https://seller.cdiscount.com/Messagerie_offres.html';
    const URL_PROD_MSG = 'https://seller.cdiscount.com/Messagerie_commandes.html';
    const URL_PREPROD_LOGIN = 'https://seller.preprod-cdiscount.com/login.html';
    const URL_PREPROD_ORDERS = 'https://seller.preprod-cdiscount.com/Orders.html';
    const URL_PREPROD_HOME = 'https://seller.preprod-cdiscount.com/home.html';
    const URL_PREPROD_OFFERS = 'https://seller.preprod-cdiscount.com/Messagerie_offres.html';
    const URL_PREPROD_MSG = 'https://seller.preprod-cdiscount.com/Messagerie_commandes.html';

    public function __construct($id_lang)
    {
        $this->id_lang = $id_lang;

        parent::__construct();

        $this->debug = Configuration::get(parent::KEY.'_DEBUG') ? true : false;
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

        require_once(_PS_MODULE_DIR_.parent::MODULE.'/classes/shared/configure_tab.class.php');

        $smarty->assign('ps16x', version_compare(_PS_VERSION_, '1.6', '>='));

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $smarty->assign('context_key', CDiscountContext::getKey($this->context->shop));
        } else {
            $smarty->assign('context_key', '0');
        }

        $smarty->assign('path', $this->url);
        $smarty->assign('images', $this->images);
        $smarty->assign('js_file', parent::MODULE.'/views/js/orders_common.js');
        $smarty->assign('image_loading', $this->images.'loading.gif');

        if ($this->debug) {
            $smarty->assign('debug', '1');
            $display = '';
        } else {
            $display = 'display:none;';
        }

        $smarty->assign('console_display', $display);
        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'accept';
        $production = !(bool)Configuration::get(parent::KEY.'_PREPRODUCTION');

        if ($production) {
            $smarty->assign('auth_url', self::URL_PROD_LOGIN);
            $smarty->assign('orders_url', self::URL_PROD_ORDERS);
            $smarty->assign('home_url', self::URL_PROD_HOME);
            $smarty->assign('offersq_url', self::URL_PROD_OFFERS);
            $smarty->assign('ordersq_url', self::URL_PROD_MSG);
        } else {
            $smarty->assign('auth_url', self::URL_PREPROD_LOGIN);
            $smarty->assign('orders_url', self::URL_PREPROD_ORDERS);
            $smarty->assign('home_url', self::URL_PREPROD_HOME);
            $smarty->assign('offersq_url', self::URL_PREPROD_OFFERS);
            $smarty->assign('ordersq_url', self::URL_PREPROD_MSG);
        }


        $smarty->assign('tab_selected_accept', $selected_tab == 'accept' ? 'selected' : '');
        $smarty->assign('tab_selected_import', $selected_tab == 'import' ? 'selected' : '');
        $smarty->assign('tab_selected_debug', $selected_tab == 'debug' ? 'selected' : '');

        if (method_exists($smarty, 'setTemplateDir')) {
            $currentTemplates = $smarty->getTemplateDir();
            $additionnalTemplates = array($this->path.'views/templates/admin/orders/');
            $smarty->setTemplateDir(is_array($currentTemplates) ? array_merge($currentTemplates, $additionnalTemplates) : $additionnalTemplates);
        } else {
            $smarty->template_dir = $this->path.'views/templates/admin/orders/';
        }

        $smarty->assign('tab_accept_data', $this->_accept());
        $smarty->assign('tab_import_data', $this->_import());

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
        $tabList[] = array(
            'id' => 'accept',
            'img' => 'accept',
            'name' => $this->l('Accept Orders'),
            'selected' => true
        );
        $tabList[] = array(
            'id' => 'import',
            'img' => 'document_import',
            'name' => $this->l('Import Orders'),
            'selected' => false
        );
        if ($this->debug) {
            $tabList[] = array('id' => 'debug', 'img' => 'bug', 'name' => $this->l('Debug Mode'), 'selected' => false);
        }

        $html .= $smarty->fetch($this->path.'views/templates/admin/orders/header.tpl');
        $html .= ConfigureTab::generateTabs($tabList, 'cdiscount');
        $html .= $smarty->fetch($this->path.'views/templates/admin/orders/AdminOrdersCDiscount.tpl');

        return ($html);
    }

    private function _accept()
    {
        $smarty_data = array();

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'accept';
        $smarty_data['selected_tab'] = ($selected_tab == 'accept' ? 'selected' : '');
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];
        $smarty_data['start_date'] = date('Y-m-d', strtotime('now -3 days'));
        $smarty_data['current_date'] = date('Y-m-d');
        $smarty_data['token'] = parent::decode(Configuration::get(parent::KEY.'_PS_TOKEN'));
        $smarty_data['accept_orders_url'] = $this->url.'functions/orders_accept.php?action=orders&cdtoken='.$smarty_data['token'];
        $smarty_data['accept_order_url'] = $this->url.'functions/orders_accept.php?action=accept&cdtoken='.$smarty_data['token'];

        return $smarty_data;
    }

    private function _import()
    {
        $smarty_data = array();

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'accept';
        $smarty_data['selected_tab'] = ($selected_tab == 'import' ? 'selected' : '');
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];
        $smarty_data['start_date'] = date('Y-m-d', strtotime('now -3 days'));
        $smarty_data['current_date'] = date('Y-m-d');
        $smarty_data['token'] = parent::decode(Configuration::get(parent::KEY.'_PS_TOKEN'));
        $smarty_data['import_orders_url'] = $this->url.'functions/orders_import.php?action=orders&cdtoken='.$smarty_data['token'].'&id_lang='.$this->context->cookie->id_lang;
        $smarty_data['import_order_url'] = $this->url.'functions/orders_import.php?action=import&cdtoken='.$smarty_data['token'];
        $smarty_data['token_orders'] = Tools::getAdminToken('AdminOrders'.(int)Tab::getIdFromClassName('AdminOrders').(int)$this->context->cookie->id_employee);

        // Aug-23-2018: Remove Carriers/Modules option

        if (!$last_import = parent::decode(unserialize(Configuration::get(parent::KEY.'_LAST_IMPORT')))) {
            $smarty_data['last_import_title'] = sprintf('%s : %s', $this->l('Last Import'), $this->l('Never'));
        } else {
            $smarty_data['last_import_title'] = sprintf('%s : %s', $this->l('Last Import'), $last_import);
        }

        return $smarty_data;
    }

    private function _debug()
    {
        $smarty_data = array();

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'update';
        $smarty_data['selected_tab'] = ($selected_tab == 'debug' ? 'selected' : '');
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];
        $smarty_data['token'] = parent::decode(Configuration::get(parent::KEY.'_PS_TOKEN'));

        return $smarty_data;
    }
}
