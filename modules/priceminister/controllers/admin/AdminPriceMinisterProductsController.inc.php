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
require_once(dirname(__FILE__).'/../../classes/priceminister.batch.class.php');
require_once(dirname(__FILE__).'/../../classes/priceminister.product.class.php');
require_once(dirname(__FILE__).'/../../classes/priceminister.support.class.php');

class AdminPriceMinisterProductsControllerExt extends PriceMinister
{

    public function __construct($context = null)
    {
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

        $support = new PriceMinisterSupport($this->id_lang);

        $smarty->assign('support_language', $support->lang);
        $smarty->assign('path', $this->url);
        $smarty->assign('images', $this->images);
        $smarty->assign('js_file', 'priceminister/views/js/catalogtab.js');
        $smarty->assign('image_loading', $this->images.'loading.gif');

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'accept';

        $smarty->assign('tab_selected_offers', $selected_tab == 'accept' ? 'selected' : '');
        $smarty->assign('tab_selected_products', $selected_tab == 'import' ? 'selected' : '');

        if (method_exists($smarty, 'setTemplateDir')) {
            $currentTemplates = $smarty->getTemplateDir();
            $additionnalTemplates = array($this->path.'views/templates/admin/catalog/');
            $smarty->setTemplateDir(is_array($currentTemplates) ? array_merge($currentTemplates, $additionnalTemplates) : $additionnalTemplates);
        } else {
            $smarty->template_dir = $this->path.'views/templates/admin/catalog/';
        }

        $smarty->assign('tab_offers_data', $this->_offers());
        $smarty->assign('tab_products_data', $this->_products());
        $smarty->assign('tab_reports_data', $this->_reports());

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $smarty->assign('all_shop', Shop::getContext() == Shop::CONTEXT_ALL ? 1 : 0);
        } else {
            $smarty->assign('all_shop', 0);
        }

        $view_params = array();
        $view_params['alert_class'] = array();
        $view_params['alert_class']['danger'] = $this->ps16x ? 'alert alert-danger' : 'error';
        $view_params['alert_class']['warning'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        $view_params['alert_class']['success'] = $this->ps16x ? 'alert alert-success' : 'conf';
        $view_params['alert_class']['info'] = $this->ps16x ? 'alert alert-info' : 'info';
        $smarty->assign($view_params);

        $html = '';
        $tab_list = array();
        $tab_list[] = array('id' => 'offers', 'img' => 'update', 'name' => $this->l('Offers'), 'selected' => true);
        $tab_list[] = array('id' => 'products', 'img' => 'add', 'name' => $this->l('Products'), 'selected' => false);
        $tab_list[] = array('id' => 'reports', 'img' => 'report', 'name' => $this->l('Reports'), 'selected' => false);

        $html .= $smarty->fetch($this->path.'views/templates/admin/catalog/header.tpl');
        $html .= ConfigureTab::generateTabs($tab_list, 'priceminister');
        $html .= $smarty->fetch($this->path.'views/templates/admin/catalog/catalog.tpl');

        return ($html);
    }

    private function _offers()
    {
        $smarty_data = array();

        $batches = new PriceMinisterBatches(PriceMinister::CONFIG_BATCH_UPDATE);

        $last_update = $batches->getLast();

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'offers';
        $smarty_data['selected_tab'] = ($selected_tab == 'offers' ? 'selected' : '');
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];
        $smarty_data['start_date'] = date('Y-m-d', strtotime('now -7 days'));
        $smarty_data['current_date'] = date('Y-m-d');
        $smarty_data['pm_token'] = Configuration::get(PriceMinister::CONFIG_PM_CRON_TOKEN);
        $smarty_data['offers_url'] = $this->url.'functions/offers.php';
        $smarty_data['last_update'] = $last_update;

        return $smarty_data;
    }

    private function _products()
    {
        $smarty_data = array();

        $batches = new PriceMinisterBatches(PriceMinister::CONFIG_BATCH_CREATE);

        if (!$last_export = $batches->getLast()) {
            $last_export = date('Y-m-d', strtotime('-1 YEAR'));
        } else {
            $last_export = strstr($last_export, ' ', true);
        }

        // Force to 10 years ago so merchant dont complains there is no products to send....
        $last_export = date('Y-m-d', strtotime('-10 YEAR'));

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'offers';
        $smarty_data['selected_tab'] = ($selected_tab == 'products' ? 'selected' : '');
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];
        $smarty_data['pm_token'] = Configuration::get(PriceMinister::CONFIG_PM_CRON_TOKEN);
        $smarty_data['products_url'] = $this->url.'functions/products.php';

        $smarty_data['current_date'] = date('Y-m-d');
        $smarty_data['last_export'] = $last_export;

        $smarty_data['token_catalog'] = Tools::getAdminToken('AdminCatalog'.(int)Tab::getIdFromClassName('AdminCatalog').(int)$this->context->cookie->id_employee);

        return $smarty_data;
    }

    private function _reports()
    {
        $smarty_data = array();

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'offers';
        $smarty_data['selected_tab'] = ($selected_tab == 'reports' ? 'selected' : '');
        $smarty_data['request_uri'] = $_SERVER['REQUEST_URI'];
        $smarty_data['pm_token'] = Configuration::get(PriceMinister::CONFIG_PM_CRON_TOKEN);
        $smarty_data['reports_url'] = $this->url.'functions/reports.php';

        return $smarty_data;
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }
}