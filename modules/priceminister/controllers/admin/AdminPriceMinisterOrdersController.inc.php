<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
 * Contact by Email :  support.priceminister@common-services.com
 */

require_once(dirname(__FILE__).'/../../classes/priceminister.context.class.php');

class AdminPriceMinisterOrdersControllerExt extends PriceMinister
{

    public function __construct($id_lang, $context = null)
    {
        $this->id_lang = $id_lang;
        $this->context = $context;

        parent::__construct();
        parent::loadGeneralModuleConfig();
    }

    public function content($smarty)
    {
        require_once(_PS_MODULE_DIR_.'priceminister/classes/shared/configure_tab.class.php');

        $smarty->assign('ps16x', version_compare(_PS_VERSION_, '1.6', '>='));

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $smarty->assign('context_key', PriceMinisterContext::getKey($this->context->shop));
        } else {
            $smarty->assign('context_key', '0');
        }

        $smarty->assign('path', $this->url);
        $smarty->assign('images', $this->images);
        $smarty->assign('js_file', 'priceminister/views/js/orders_common.js');
        $smarty->assign('image_loading', $this->images.'loading.gif');

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'accept';

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

        $view_params = array();
        $view_params['alert_class'] = array();
        $view_params['alert_class']['danger'] = $this->ps16x ? 'alert alert-danger' : 'error';
        $view_params['alert_class']['warning'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        $view_params['alert_class']['success'] = $this->ps16x ? 'alert alert-success' : 'conf';
        $view_params['alert_class']['info'] = $this->ps16x ? 'alert alert-info' : 'info';
        $smarty->assign($view_params);

        $html = '';
        $tabList = array();
        $tabList[] = array('id' => 'accept', 'img' => 'accept', 'name' => $this->l('Accept Orders'), 'selected' => true);
        $tabList[] = array('id' => 'import', 'img' => 'document_import', 'name' => $this->l('Import Orders'), 'selected' => false);
        if ($this->debug) {
            $tabList[] = array('id' => 'debug', 'img' => 'bug', 'name' => $this->l('Debug Mode'), 'selected' => false);
        }

        $html .= $smarty->fetch($this->path.'views/templates/admin/orders/header.tpl');
        $html .= ConfigureTab::generateTabs($tabList, 'priceminister');
        $html .= $smarty->fetch($this->path.'views/templates/admin/orders/adminorders.tpl');

        return ($html);
    }

    private function _accept()
    {
        $smarty_data = array();

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'accept';
        $smarty_data['selected_tab'] = ($selected_tab == 'accept' ? 'selected' : '');
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];
        $smarty_data['start_date'] = date('Y-m-d', strtotime('now -7 days'));
        $smarty_data['current_date'] = date('Y-m-d');
        $smarty_data['pm_token'] = Configuration::get(PriceMinister::CONFIG_PM_CRON_TOKEN);
        $smarty_data['accept_orders_url'] = $this->url.'functions/orders_list.php?method=accept&action=list';
        $smarty_data['accept_order_url'] = $this->url.'functions/orders_import.php?action=accept';

        return $smarty_data;
    }

    private function _import()
    {
        $smarty_data = array();

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'accept';
        $smarty_data['selected_tab'] = ($selected_tab == 'import' ? 'selected' : '');
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];
        $smarty_data['start_date'] = date('Y-m-d', strtotime('now -7 days'));
        $smarty_data['current_date'] = date('Y-m-d');
        $smarty_data['pm_token'] = Configuration::get(PriceMinister::CONFIG_PM_CRON_TOKEN);
        $smarty_data['import_orders_url'] = $this->url.'functions/orders_list.php?method=import&action=list';
        $smarty_data['import_order_url'] = $this->url.'functions/orders_import.php?action=import';
        $smarty_data['token_orders'] = Tools::getAdminToken('AdminOrders'.(int)Tab::getIdFromClassName('AdminOrders').(int)$this->context->cookie->id_employee);
        $smarty_data['orders_statuses'] = array(
            RakutenConstant::OI_STATUS_IMPORTABLE => 'Ready to be Imported',
            RakutenConstant::OI_STATUS_ALL => 'All',
        );

        #if (!$last_import = parent::decode(unserialize(Configuration::get(parent::KEY.'_LAST_IMPORT'))))
        $smarty_data['last_import_title'] = sprintf('%s : %s', $this->l('Last Import'), $this->l('Never'));
        #else
        #$smarty_data['last_import_title'] = sprintf('%s : %s', $this->l('Last Import'), $last_import);

        return $smarty_data;
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }
}