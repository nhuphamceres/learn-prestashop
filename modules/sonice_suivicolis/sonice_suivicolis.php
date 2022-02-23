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
 * @copyright Copyright (c) 2011-2018 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice@common-services.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviOrderHelperList.php');
require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviEvent.php');
require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviTools.php');

if (!class_exists('ConfigureMessage')) {
    require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/shared/configure_message.class.php');
}

class SoNice_SuiviColis extends Module
{

    const ADD = 'a';
    const REMOVE = 'd';
    const UPDATE = 'u';
    public $debug = false;
    public $id_lang;
    protected $ps15x;
    protected $ps16x;
    protected $ps17x;
    protected $protocol;
    protected $url;
    protected $link;
    protected $img;
    protected $function;
    protected $path;
    protected $update_parcels;
    protected $mail_parcels;
    protected $incentive_mail;

    public function __construct()
    {
        $this->name = 'sonice_suivicolis';
        $this->tab = 'shipping_logistics';
        $this->version = '2.0.10';
        $this->author = 'Common-Services';
        $this->need_instance = 0;
        $this->module_key = '5b35e540d64f04a2a2195a49121f273f';
        $this->author_address = '0x96116FE33A6268AE9E878Dbc609A02BdCcc285E0';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = 'SoNice Suivi de Colis';
        $this->description = $this->l('This service return parcel delivery informations.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall ?');

        $this->path = _PS_MODULE_DIR_.$this->name.'/';
        $this->protocol = Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://';
        $this->link = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/';
        $this->url = $this->protocol.htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').
            __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/';
        $this->img = $this->url.'views/img/';
        $this->mail = $this->path.'mails/';
        $this->function = $this->url.'functions/';
        $this->update_parcels = $this->url.'functions/getparcels.php';
        $this->mail_parcels = $this->url.'functions/sendmails.php';
        $this->incentive_mail = $this->url.'functions/incentivemail_cron.php';

        $debug = unserialize(Configuration::get('SONICE_SUIVICOLIS_CONF'));
        $this->debug = (isset($debug['debug']) && $debug['debug']);

        $this->initContext();
    }

    /**
     * Initialize the context in order to have same data architecture in PS 1.4 & 1.5
     */
    private function initContext()
    {
        $this->ps15x = version_compare(_PS_VERSION_, '1.5', '>=');
        $this->ps16x = version_compare(_PS_VERSION_, '1.6', '>=');
        $this->ps17x = version_compare(_PS_VERSION_, '1.7', '>=');

        if (!$this->ps15x) {
            require_once(_PS_MODULE_DIR_.$this->name.'/backward_compatibility/backward.php');
        }

        $this->context = Context::getContext();
        $this->id_lang = (isset($this->context->language) && Validate::isLoadedObject($this->context->language)) ?
            (int)$this->context->language->id : (int)Configuration::get('PS_LANG_DEFAULT');
        $this->context->smarty->assign('snsc_ps15x', $this->ps15x);
    }

    public function install()
    {
        $pass = true;

        if (!parent::install()) {
            $this->_errors[] = $this->l('An error occured while installing with parent::install().');
            $pass = false;
        }

        if (!$this->hookSetup(SoNice_SuiviColis::ADD)) {
            $this->_errors[] = $this->l('An error occured while registering hooks.');
            $pass = false;
        }

        $this->tabSetup(self::ADD);

        // Prestashop state's defaut mapping
        Configuration::updateValue('SONICE_SUIVICOLIS_MAPPING', serialize(array(
            4 => array (
                0 => 'COMCFM',
                1 => 'PCHCFM',
            ),
            5 => array (
                0 => 'LIVCFM',
                1 => 'LIVGAR',
                2 => 'RENAVI',
                3 => 'MLVARS',
            )
        )));
        Configuration::updateValue('SONICE_SUIVICOLIS_CONF', serialize(array(
            'login' => '',
            'pwd' => '',
            'invoice_tpl' => '0',
            'delivery_slip_tpl' => '0',
            'incentive_time' => '',
            'incentive_mail_tpl' => 'delivered',
            'incentive_state' => '4',
            'rating_service' => '',
            'demo' => '0',
            'debug' => '0',
            'auto_update_order' => '0',
            'cron_employee' => '1'
        )));

        $query = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'sonice_suivicolis (
			 `shipping_number` VARCHAR(32) NOT NULL,
			 `id_order` INT(10) UNSIGNED NOT NULL,
			 `coliposte_state` VARCHAR(150) NOT NULL,
			 `coliposte_date` VARCHAR(32) NOT NULL,
			 `coliposte_location` VARCHAR(64) NOT NULL,
			 `mail` TINYINT(1) UNSIGNED DEFAULT 0,
			 `incentive` TINYINT(1) UNSIGNED DEFAULT 0,
			 `date_add` DATETIME NOT NULL,
			 `date_upd` DATETIME NOT NULL,
			 PRIMARY KEY (`shipping_number`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        if (!Db::getInstance()->Execute($query)) {
            $pass = false;
        }

        return ($pass);
    }

    public function uninstall()
    {
        $pass = true;

        if (!$this->hookSetup(SoNice_SuiviColis::REMOVE)) {
            $this->_errors[] = $this->l('An error occured while unregistering hooks.');
            $pass = false;
        }

        if (!parent::uninstall()) {
            $this->_errors[] = $this->l('An error occured while uninstalling with parent::uninstall().');
            $pass = false;
        }

        if (!$this->tabSetup(self::REMOVE)) {
            $pass = false;
        }

        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'sonice_suivicolis');

        Configuration::deleteByName('SONICE_SUIVICOLIS_CONF');
        Configuration::deleteByName('SONICE_SUIVICOLIS_MAIL');
        Configuration::deleteByName('SONICE_SUIVICOLIS_MAIL_PJ');
        Configuration::deleteByName('SONICE_SUIVICOLIS_MAPPING');
        Configuration::deleteByName('SONICE_SUIVICOLIS_CARRIER');
        Configuration::deleteByName('SONICE_SUIVICOLIS_PAYMENT');
        Configuration::deleteByName('SONICE_SUIVICOLIS_STATUSES');
        Configuration::deleteByName('SONICE_SUIVI_TOKEN');

        return ($pass);
    }

    /**
     * Install or uninstall module's Hooks
     *
     * @param string $action
     * @return bool
     * @throws PrestaShopException
     */
    private function hookSetup($action)
    {
        $expected_hooks = $this->ps15x ?
            array(
                'displayAdminOrder', 'actionCarrierUpdate',
                'displayOrderDetail', 'actionAdminOrdersListingFieldsModifier'
            ) :
            array('adminOrder', 'updateCarrier');

        $pass = true;

        if (in_array($action, array(self::REMOVE, self::UPDATE))) {
            foreach ($expected_hooks as $expected_hook) {
                if (!$this->unregisterHook($expected_hook)) {
                    $pass = false;
                }
            }
        }

        if (in_array($action, array(self::ADD, self::UPDATE))) {
            foreach ($expected_hooks as $expected_hook) {
                if (!$this->registerHook($expected_hook)) {
                    $pass = false;
                }
            }
        }

        return ($pass);
    }

    /**
     * Install, update or remove module's tab
     *
     * @param string $action Action to perform
     * @return bool
     */
    private function tabSetup($action)
    {
        $pass = true;

        switch ($action) {
            case SoNice_SuiviColis::ADD:
                if ($this->ps15x) {
                    if (Tab::getIdFromClassName('AdminOrdersSoniceSuivi')) {
                        return (true);
                    }

                    $admin_order = $this->ps17x ? 'AdminParentOrders' : 'AdminOrders';

                    if (!$this->installModuleTab('AdminOrdersSoniceSuivi', 'SoNice - Suivi', $admin_order)) {
                        $this->_errors[] = $this->l('Unable to install: OrdersSoniceSuivi');
                        $pass = false;
                    }
                } else {
                    if (Tab::getIdFromClassName('OrdersSoniceSuivi')) {
                        return (true);
                    }

                    if (!$this->installModuleTab('OrdersSoniceSuivi', 'SoNice - Suivi', 'AdminOrders')) {
                        $this->_errors[] = $this->l('Unable to install: OrdersSoniceSuivi');
                        $pass = false;
                    }
                }
                break;

            case SoNice_SuiviColis::UPDATE:
                if ($this->ps15x) {
                    // Removing Old AdminTabs
                    if (Tab::getIdFromClassName('OrdersSoniceSuivi')) {
                        if (!$this->uninstallModuleTab('OrdersSoniceSuivi')) {
                            $this->_errors[] = $this->l('Unable to uninstall: OrdersSoniceSuivi Tab');
                        }
                    }

                    // Adding New
                    return ($this->tabSetup(SoNice_SuiviColis::ADD));
                }
                break;

            case SoNice_SuiviColis::REMOVE:
                // Removing New AdminTabs
                if (Tab::getIdFromClassName('AdminOrdersSoniceSuivi')) {
                    if (!$this->uninstallModuleTab('AdminOrdersSoniceSuivi')) {
                        $this->_errors[] = $this->l('Unable to uninstall: AdminOrdersSoniceSuivi Tab');
                        $pass = false;
                    }
                }
                // Removing Old AdminTabs
                if (Tab::getIdFromClassName('OrdersSoniceSuivi')) {
                    if (!$this->uninstallModuleTab('OrdersSoniceSuivi')) {
                        $this->_errors[] = $this->l('Unable to uninstall: OrdersSoniceSuivi Tab');
                        $pass = false;
                    }
                }
                break;
        }

        return ($pass);
    }

    /**
     * Install tab for the module
     *
     * @param string $tabClass
     * @param string $tabName
     * @param string $tabParent
     * @return bool
     */
    private function installModuleTab($tabClass, $tabName, $tabParent)
    {
        $tabNameLang = array();

        if (version_compare(_PS_VERSION_, '1.5.5.0', '>=')) {
            @Tools::copy(_PS_MODULE_DIR_.$this->name.'/logo.gif', _PS_IMG_DIR_.'t/'.$tabClass.'.gif');
        } else {
            copy(_PS_MODULE_DIR_.$this->name.'/logo.gif', _PS_IMG_DIR_.'t/'.$tabClass.'.gif');
        }

        foreach (Language::getLanguages() as $language) {
            $tabNameLang[$language['id_lang']] = $tabName;
        }

        $tab = new Tab();
        $tab->name = $tabNameLang;
        $tab->class_name = $tabClass;
        $tab->module = $this->name;
        $tab->id_parent = Tab::getIdFromClassName($tabParent);

        // For Prestashop 1.2
        if ($this->ps15x) {
            $pass = $tab->add();
        } else {
            $pass = $tab->save();
        }

        return ($pass);
    }

    /**
     * Uninstall tab for the module
     *
     * @param string $tabClass
     * @return bool
     */
    private function uninstallModuleTab($tabClass)
    {
        $pass = true;
        @unlink(_PS_IMG_DIR_.'t/'.$tabClass.'.gif');
        $idTab = Tab::getIdFromClassName($tabClass);
        if ($idTab != 0) {
            $tab = new Tab($idTab);
            $pass = $tab->delete();
        }

        return ($pass);
    }

    private function filterTableName($table_array)
    {
        return reset($table_array);
    }

    public function getContent()
    {
        if (Tools::isSubmit('submit'.$this->name)) {
            $pass = true;
            $values = Tools::getValue('return_info');
            $values['login'] = trim($values['login']);
            $values['pwd'] = trim($values['pwd']);

            $pass &= Configuration::updateValue(
                'SONICE_SUIVICOLIS_MAIL',
                serialize(Tools::getValue('filtered_mails'))
            );
            $pass &= Configuration::updateValue(
                'SONICE_SUIVICOLIS_MAIL_PJ',
                serialize(Tools::getValue('invoice_shipping'))
            );
            $pass &= Configuration::updateValue(
                'SONICE_SUIVICOLIS_MAPPING',
                serialize(Tools::getValue('filtered_states'))
            );
            $pass &= Configuration::updateValue(
                'SONICE_SUIVICOLIS_CARRIER',
                serialize(Tools::getValue('filtered_carriers'))
            );
            $pass &= Configuration::updateValue(
                'SONICE_SUIVICOLIS_CONF',
                serialize($values)
            );
            $pass &= Configuration::updateValue(
                'SONICE_SUIVICOLIS_PAYMENT',
                serialize(Tools::getValue('filtered_payment_methods', array()))
            );
            $pass &= Configuration::updateValue(
                'SONICE_SUIVICOLIS_STATUSES',
                serialize(Tools::getValue('filtered_status', array()))
            );

            $pass &= Configuration::updateValue('SONICE_SUIVI_TOKEN', md5($values['login'].'@%@#$'.$values['pwd']));

            $table = _DB_PREFIX_.'sonice_suivicolis';
            $tables = array_map(array($this, 'filterTableName'), (array)Db::getInstance()->executeS('SHOW TABLES'));
            if (!in_array($table, $tables)) {
                $query = 'CREATE TABLE IF NOT EXISTS `'.$table.'` (
                     `shipping_number` VARCHAR(32) NOT NULL,
                     `id_order` INT(10) UNSIGNED NOT NULL,
                     `coliposte_state` VARCHAR(150) NOT NULL,
                     `coliposte_date` VARCHAR(32) NOT NULL,
                     `coliposte_location` VARCHAR(64) NOT NULL,
                     `mail` TINYINT(1) UNSIGNED DEFAULT 0,
                     `incentive` TINYINT(1) UNSIGNED DEFAULT 0,
                     `date_add` DATETIME NOT NULL,
                     `date_upd` DATETIME NOT NULL,
                     PRIMARY KEY (`shipping_number`)
                ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

                if (!Db::getInstance()->execute($query)) {
                    ConfigureMessage::error($this->l('Unable to create table '._DB_PREFIX_.'sonice_suivicolis'));
                }
            }

            $fields = array();
            $query = Db::getInstance()->executeS('SHOW COLUMNS FROM `'.$table.'`');
            if ($query) {
                foreach ($query as $row) {
                    $fields[$row['Field']] = 1;
                }
            }

            if (!isset($fields['inovert'])) {
                $pass = Db::getInstance()->execute(
                    'ALTER TABLE `'.$table.'` ADD  `inovert` VARCHAR(6) NULL DEFAULT NULL AFTER `id_order`'
                );

                if (!$pass) {
                    ConfigureMessage::error($this->l('Unable to update table '._DB_PREFIX_.'sonice_suivicolis'));
                }
            }

            $pass &= $this->hookSetup(self::UPDATE);

            if ($pass) {
                ConfigureMessage::success($this->l('Options updated.'));
            } else {
                ConfigureMessage::error($this->l('The system failed to save your options.'));
            }

            $this->tabSetup(self::UPDATE);
        }

        return (ConfigureMessage::display().$this->displayForm());
    }

    public function displayFormV2()
    {
        $this->context->controller->addJS(array(
            'https://cdnjs.cloudflare.com/ajax/libs/riot/3.11.1/riot+compiler.min.js',
            $this->link.'views/js/configurationv2.js'
        ));

        $this->context->controller->addCSS($this->link.'views/css/configurationv2.css');

        $this->context->smarty->assign(array(
            'module_name' => $this->displayName,
            'snsc_module_version' => $this->version,
            'snsc_module_description' => $this->description,
            'snsc_url' => $this->url,
            'snsc_img_dir' => $this->img,
            'snsc_cron_task_url' => $this->url.'functions/cronjobs.php'.'?token='.md5(_COOKIE_IV_),
            'snsc_infos' => $this->backOfficeInformations(),
            'snsc_config' => unserialize(Configuration::get('SONICE_SUIVICOLIS_CONF')),
            'snsc_checklogin_url' => $this->url.'functions/checklogin.php?token='.md5(_COOKIE_IV_).
                '&id_shop='.$this->context->shop->id,
        ));

        $html = $this->display(__FILE__, 'views/templates/admin/configurationv2/configuration.tpl');

        return $html.$this->display(__FILE__, 'views/templates/admin/prestui/ps-tags.tpl');
    }

    public function displayForm()
    {

        require_once(dirname(__FILE__).'/classes/shared/configure_tab.class.php');

        $html = '';

        $alert_class = array();
        $alert_class['danger'] = $this->ps16x ? 'alert alert-danger' : 'error';
        $alert_class['warning'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        $alert_class['success'] = $this->ps16x ? 'alert alert-success' : 'conf';
        $alert_class['info'] = $this->ps16x ? 'alert alert-info' : 'info';

        $token = Configuration::get('SONICE_SUIVI_TOKEN');

        $this->context->smarty->assign(
            array(
                'snsc_img_dir' => $this->img,
                'snsc_module_name' => $this->displayName,
                'snsc_module_version' => $this->version,
                'ps_version' => _PS_VERSION_,
                'snsc_module_description' => $this->description,
                'snsc_module_directory' => $this->url,
                'snsc_module_path' => $this->path,
                'snsc_module_functions' => $this->function,
                'snsc_network_error' => $this->l(
                    'We cannot check your login now because you lost internet connection.'
                ),
                'snsc_infos' => $this->backOfficeInformations(),
                'snsc_form_action' => Tools::htmlentitiesUTF8(filter_input(INPUT_SERVER, 'REQUEST_URI')),
                'snsc_config' => unserialize(Configuration::get('SONICE_SUIVICOLIS_CONF')),
                'snsc_mail_pj' => unserialize(Configuration::get('SONICE_SUIVICOLIS_MAIL_PJ')),
                'snsc_carriers' => $this->carriers(),
                'snsc_order_state' => $this->getOrderStateShipped(),
                'snsc_coliposte_state' => $this->mapping(),
                'snsc_filters' => $this->filters(),
                'snsc_mail_tpl' => $this->getMailTemplate(),
                // 'snsc_cron_task' => $this->update_parcels.'?cron=1&id_shop='.$this->context->shop->id.'&token='.$token,
                'snsc_cron_task' => Context::getContext()->link->getModuleLink(
                    $this->name,
                    'tracking',
                    array(
                        'token' => $token,
                        'id_shop' => $this->context->shop->id
                    )
                ),
                'snsc_cron_task_mail' => $this->mail_parcels.'?cron=1&id_shop='.$this->context->shop->id.
                    '&token='.$token,
                'snsc_cron_task_url' => $this->url.'functions/cronjobs.php'.'?token='.md5(_COOKIE_IV_),
                'snsc_incentive_cron_task' => $this->incentive_mail.'?cron=1&id_shop='.$this->context->shop->id.
                    '&token='.$token,
                'snsc_checklogin_url' => $this->url.'functions/checklogin.php?token='.md5(_COOKIE_IV_).
                    '&id_shop='.$this->context->shop->id,
                'snsc_employee_list' => @Employee::getEmployees(),
                'alert_class' => $alert_class,
                'selected_tab' => Tools::getValue('selected_tab', 'menu-sonice'),
                'status_order_list' => OrderState::getOrderStates($this->id_lang),
                'snsc_cron' => $this->cronTab(),
                'snsc_id_shop' => $this->context->shop->id
            )
        );

        $this->autoAddCSS($this->link.'views/css/configuration.css');
        $this->autoAddCSS($this->link.'views/css/jquery.qtip.min.css');
        $this->autoAddJS($this->link.'views/js/configuration.js');
        $this->autoAddJS($this->link.'views/js/jquery.qtip.min.js');

        $tab_list = array();
        $tab_list[] = array(
            'id' => 'sonice',
            'img' => 'sonice_suivicolis',
            'name' => 'SoNice Suivi de Colis',
            'selected' => true
        );
        $tab_list[] = array(
            'id' => 'informations', 'img' => 'information', 'name' => 'Informations', 'selected' => false
        );
        $tab_list[] = array(
            'id' => 'login', 'img' => 'account_functions', 'name' => $this->l('Login'), 'selected' => false
        );
        $tab_list[] = array('id' => 'carrier', 'img' => 'lorry', 'name' => $this->l('Carrier'), 'selected' => false);
        $tab_list[] = array('id' => 'mapping', 'img' => 'mapping', 'name' => 'Mapping', 'selected' => false);
//        $tab_list[] = array(
//            'id' => 'overrides',
//            'img' => 'google_webmaster_tools',
//            'name' => 'Overrides',
//            'selected' => false
//        );
        $tab_list[] = array('id' => 'mail', 'img' => 'mail', 'name' => 'Mail', 'selected' => false);
        $tab_list[] = array(
            'id' => 'params', 'img' => 'cog_edit', 'name' => $this->l('Parameters'), 'selected' => false
        );
        $tab_list[] = array('id' => 'filters', 'img' => 'filter', 'name' => $this->l('Filters'), 'selected' => false);
        $tab_list[] = array('id' => 'cron', 'img' => 'clock', 'name' => $this->l('Cron Task'), 'selected' => false);

        $html .= $this->context->smarty->fetch($this->path.'views/templates/admin/configuration/header.tpl');
        $html .= ConfigureTab::generateTabs($tab_list);
        $html .= $this->context->smarty->fetch($this->path.'views/templates/admin/configuration/configuration.tpl');

        return ($html);
    }

    public function filters()
    {
        $view_params = array();
        $payment_methods = array();

        // Payment methods
        $query_payment_methods = Db::getInstance()->executeS('SELECT DISTINCT `payment` FROM `'._DB_PREFIX_.'orders`;');

        if (!is_array($query_payment_methods) && count($query_payment_methods)) {
            $view_params['payment_methods'] = array();
            $view_params['payment_methods_excluded'] = array();
            ConfigureMessage::warning(
                $this->l('It appears you do not have any payment methods, please check your shop integrity.')
            );
        } else {
            foreach ($query_payment_methods as $payment_method) {
                $payment_methods[] = $payment_method['payment'];
            }

            $payment_methods_excluded = unserialize(Configuration::get('SONICE_SUIVICOLIS_PAYMENT'));
            $view_params['payment_methods'] = $payment_methods;
            $view_params['payment_methods_excluded'] = is_array($payment_methods_excluded) ?
                $payment_methods_excluded : array();
        }

        // Order status
        $order_statuses = OrderState::getOrderStates($this->context->language->id);

        if (!is_array($order_statuses) || !count($order_statuses)) {
            $view_params['order_statuses'] = array();
            $view_params['order_statuses_excluded'] = array();
            ConfigureMessage::warning(
                $this->l('It appears you do not have any order statuses, please check your shop integrity.')
            );
        } else {
            $order_statuses_excluded = unserialize(Configuration::get('SONICE_SUIVICOLIS_STATUSES'));
            $view_params['order_statuses'] = $order_statuses;
            $view_params['order_statuses_excluded'] = is_array($order_statuses_excluded) ?
                $order_statuses_excluded : array();
        }

        return ($view_params);
    }

    public function cronTab()
    {
        $view_params = array();
        $view_params['exists'] = is_dir(_PS_MODULE_DIR_.'cronjobs/');
        $view_params['installed'] = (bool)Module::isInstalled('cronjobs');
        $view_params['frequency'] = 6;

        return ($view_params);
    }

    protected function autoAddCSS($url)
    {
        if ($this->ps15x) {
            return ($this->context->controller->addCSS($url, 'all'));
        } else {
            echo '<link href="'.$url.'" rel="stylesheet" type="text/css" media="all">';
        }
    }

    protected function autoAddJS($url)
    {
        if ($this->ps15x) {
            $this->context->controller->addJS($url);
        } else {
            echo '<script type="text/javascript" src="'.$url.'"></script>';
        }
    }

    private function backOfficeInformations()
    {
        if ((bool)Configuration::get('PS_FORCE_SMARTY_2') == true) {
            die(sprintf(
                '<div class="error">%s</span>',
                Tools::displayError(
                    'This module is not compatible with Smarty v2. Please switch to Smarty v3 in Preferences Tab.'
                )
            ));
        }

        $module_infos = array();
        $prestashop_infos = array();
        $php_infos = array();
        $module_config = unserialize(Configuration::get('SONICE_SUIVICOLIS_CONF'));

        // Module settings
        if (!isset($module_config['login']) || empty($module_config['login'])) {
            $module_infos['ws_login']['message'] = sprintf(
                $this->l('You did not set a login yet, please fill the login field in the Login tab.').'<br />'
            );
            $module_infos['ws_login']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }
        if (!isset($module_config['pwd']) || empty($module_config['pwd'])) {
            $module_infos['ws_pwd']['message'] = sprintf(
                $this->l('You did not set a password yet, please fill the password field in the Login tab.').'<br />'
            );
            $module_infos['ws_pwd']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }
        if (!$this->active) {
            $module_infos['inactive']['message'] = $this->l(
                'Be careful, your module is inactive, this mode stops all pending operations for this module, 
                please change the status to "Enable" in your module list.'
            );
            $module_infos['inactive']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }
        if (!is_writable(dirname(__FILE__).'/settings')) {
            $module_infos['writable']['message'] = sprintf(
                $this->l('The directory %s is not writable, please changed the permissions.'),
                dirname(__FILE__).'/settings'
            );
            $module_infos['writable']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        }

        // PHP
        $max_execution_time = ini_get('max_execution_time');
        if ($max_execution_time > 0 && $max_execution_time < 60) {
            $php_infos['max_execution_time']['message'] = sprintf(
                $this->l('PHP value: max_execution_time recommended value is at least 60. your limit is currently set to %d').'<br />',
                $max_execution_time
            );
            $php_infos['max_execution_time']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }
        $memory_limit = $this->toMegaBytes(ini_get('memory_limit'));
        if ($memory_limit < 128) {
            $php_infos['memory']['message'] = sprintf(
                $this->l('PHP value: memory_limit recommended value is at least 128MB. your limit is currently set to %sMB').'<br />',
                $memory_limit
            );
            $php_infos['memory']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }
        if (!function_exists('curl_init')) {
            $php_infos['curl']['message'] = $this->l('cURL extension must be available on your server.');
            $php_infos['curl']['level'] = version_compare(_PS_VERSION_, '1.6', '>=') ? 'alert alert-danger' : 'error';
            $php_infos['curl']['link'] = 'http://php.net/manual/'.Language::getIsoById($this->id_lang).'/book.curl.php';
        }
        if (in_array(@Tools::strtolower(ini_get('display_errors')), array('1', 'on'))) {
            $php_infos['display_errors']['message'] = $this->l('PHP variable display_errors is On.');
            $php_infos['display_errors']['level'] = $this->ps16x ? 'alert alert-info' : 'info';
        }

        // PrestaShop settings
        if (!(int)Configuration::Get('PS_SHOP_ENABLE')) {
            $prestashop_infos['maintenance']['message'] = $this->l(
                'Be carefull, your shop is in maintenance mode, the module might not work in that mode'
            );
            $prestashop_infos['maintenance']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }
        if (_PS_MODE_DEV_) {
            $prestashop_infos['dev_mode']['message'] = $this->l('The Prestashop constant _PS_MODE_DEV_ is enabled.');
            $prestashop_infos['dev_mode']['level'] = $this->ps16x ? 'alert alert-info' : 'info';
        }
        if (Configuration::get('PS_CATALOG_MODE')) {
            $prestashop_infos['catalog_mode']['message'] = $this->l(
                'Your shop is in catalog mode, the module can not work in this mode.'
            );
            $prestashop_infos['catalog_mode']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        }

        // URL issues for Ajax call
        $pass = true;
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            if (Shop::isFeatureActive()) {
                $shop = Context::getContext()->shop;

                if ($_SERVER['HTTP_HOST'] != $shop->domain && $_SERVER['HTTP_HOST'] != $shop->domain_ssl) {
                    $pass = false;
                }
            } else {
                $urls = ShopUrl::getShopUrls($this->context->shop->id)->where('main', '=', 1)->getFirst();
                if ($_SERVER['HTTP_HOST'] != $urls->domain && $_SERVER['HTTP_HOST'] != $urls->domain_ssl) {
                    $pass = false;
                }
            }
        } elseif (version_compare(_PS_VERSION_, '1.4', '>=')) {
            if ($_SERVER['HTTP_HOST'] != Configuration::get('PS_SHOP_DOMAIN') &&
                $_SERVER['HTTP_HOST'] != Configuration::get('PS_SHOP_DOMAIN_SSL')
            ) {
                $pass = false;
            }
        }

        if (!$pass) {
            $prestashop_infos['wrong_domain']['message'] = $this->l(
                'Your are currently connected with the following domain name:'
            ).' <span style="color:navy">'.$_SERVER['HTTP_HOST'].'</span><br />'.$this->l(
                'This one is different from the main shop domain name set in "Preferences > SEO & URLs":'
            ).' <span style="color:green">'.Configuration::get('PS_SHOP_DOMAIN').'</span>';
            $prestashop_infos['wrong_domain']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        }

        if (!Validate::isLoadedObject(new Employee(1))) {
            $prestashop_infos['employee_issue']['message'] = $this->l(
                'Employee #1 is missing and mandatory. This is the SuperAdministrator, 
                it has certainly be removed by mistake, please restore it.'
            );
            $prestashop_infos['employee_issue']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
        }

        if (!count($prestashop_infos)) {
            $prestashop_info_ok = true;
        } else {
            $prestashop_info_ok = false;
        }

        if (!count($php_infos)) {
            $php_infos_ok = true;
        } else {
            $php_infos_ok = false;
        }

        if (!count($module_infos)) {
            $module_info_ok = true;
        } else {
            $module_info_ok = false;
        }

        $view_params = array();
        $view_params['module_infos'] = $module_infos;
        $view_params['module_info_ok'] = $module_info_ok;
        $view_params['php_infos'] = $php_infos;
        $view_params['php_info_ok'] = $php_infos_ok;
        $view_params['prestashop_infos'] = $prestashop_infos;
        $view_params['prestashop_info_ok'] = $prestashop_info_ok;

        $disable_functions = array_map('trim', explode(',', ini_get('disable_functions')));
        if (in_array('phpinfo', $disable_functions)) {
            $view_params['phpinfo_str'] = $this->l('phpinfo()  has been disabled  for security reasons.');
        } else {
            ob_start();
            try {
                @phpinfo(INFO_ALL & ~INFO_CREDITS & ~INFO_LICENSE & ~INFO_ENVIRONMENT & ~INFO_VARIABLES);
            } catch (Exception $excp) {
                echo $excp->getMessage();
            }
            $phpinfos = ob_get_clean();
            $phpinfos = preg_replace(
                '/(a:link.*)|(body, td, th, h1, h2.*)|(img.*)|(td, th.*)|(a:hover.*)|(class="center")/',
                '',
                $phpinfos
            );
            $view_params['phpinfo_str'] = empty($phpinfos) ?
                $this->l('phpinfo()  has been disabled  for security reasons.') : $phpinfos;
        }

        $view_params['psinfo_str'] = $this->prestashopInfo();
        $view_params['dbinfo_str'] = $this->dbInfo();

        return ($view_params);
    }

    public function dbInfo()
    {
        try {
            $table = _DB_PREFIX_.'sonice_suivicolis';
            $order_by = 'shipping_number';

            $query = Db::getInstance()->executeS('SHOW TABLES');
            $tables = array();
            foreach ($query as $rows) {
                foreach ($rows as $t) {
                    $tables[$t] = 1;
                }
            }

            if (!isset($tables[$table])) {
                ConfigureMessage::error($this->l(
                    'No SoNice Suivi de Colis table found in your database. 
                    Please save configuration to create the table and check again.'
                ).' : '.$table);

                return (false);
            }

            $results = Db::getInstance()->executeS('SELECT * FROM `'.$table.'` ORDER BY `'.$order_by.'` DESC LIMIT 5');

            $fields = array();
            $query = Db::getInstance()->executeS('SHOW COLUMNS FROM `'.$table.'`');
            foreach ($query as $row) {
                $fields[] = $row['Field'];
            }

            $db_info = '<h1>'.$this->l('Database').'</h1>';
            $db_info .= '<pre>';
            $db_info .= 'SHOW COLUMNS FROM `'.$table.'` : '.print_r($fields, true);
            $db_info .= 'SELECT * FROM `'.$table.'` ORDER BY '.$order_by.' DESC LIMIT 5 : '.print_r($results, true);
            $db_info .= '</pre>';
        } catch (Exception $excp) {
            $db_info = '<pre>'.$excp->getMessage().'</pre>';
        }

        return ($db_info);
    }

    public function prestashopInfo()
    {
        $prestashop_info = '';

        if ($this->ps15x) {
            $sort = 'ORDER by `name`,`id_shop`';
        } else {
            $sort = 'ORDER by `name`';
        }

        $results = Db::getInstance()->executeS(
            'SELECT *
			FROM `'._DB_PREFIX_.'configuration`
			WHERE `name` LIKE "PS_%"
			OR `name` LIKE "SONICE_SUIVICOLIS%"
			'.$sort
        );

        $ps_configuration = null;

        foreach ($results as $result) {
            if (strpos($result['name'], 'KEY') || strpos($result['name'], 'EMAIL') ||
                strpos($result['name'], 'PASSWORD') || strpos($result['name'], 'PASSWD') ||
                strpos($result['name'], 'CONTEXT_DATA')
            ) {
                continue;
            }

            $value = $result['value'];

            if (@serialize(@unserialize($value)) == $value) {
                $value = '<div class="print_r">'.print_r(unserialize($value), true).'</div>';
            } else {
                $value = Tools::strlen($result['value']) > 128 ?
                    Tools::substr($result['value'], 0, 128).'...' : $result['value'];
            }

            if ($this->ps15x) {
                $ps_configuration .= sprintf(
                    '%-50s %03d %03d : %s'."\n",
                    $result['name'],
                    $result['id_shop'],
                    $result['id_shop_group'],
                    $value
                );
            } else {
                $ps_configuration .= sprintf('%-50s : %s'."\n", $result['name'], $value);
            }
        }

        $prestashop_info .= '<h1>Prestashop</h1>';
        $prestashop_info .= '<pre>';
        $prestashop_info .= 'Version: '._PS_VERSION_."\n";
        $prestashop_info .= 'Module Version: SoNice Suivi Colis/'.$this->version."\n";
        $prestashop_info .= 'Mode DEV: '.(_PS_MODE_DEV_ ? 'Yes' : 'No')."\n\n";

        $prestashop_info .= 'Max input vars: '.ini_get('max_input_vars')."\n";
        $prestashop_info .= 'Max execution time'.ini_get('max_execution_time')."\n";
        $prestashop_info .= 'Memory limit: '.ini_get('memory_limit')."\n";

        $prestashop_info .= "\n";
        $prestashop_info .= $ps_configuration;

        $prestashop_info .= '</pre>'."\n\n";

        return ($prestashop_info);
    }

    public function toMegaBytes($memsize)
    {
        $unit = Tools::strtolower(Tools::substr($memsize, -1));
        $val = (float)preg_replace('[^0-9]', '', $memsize);
        switch ($unit) {
            case 'g':
                $val = $val * 1024 * 1024 * 1024;
                break;
            case 'm':
                $val = $val * 1024 * 1024;
                break;
            case 'k':
                $val = $val * 1024;
                break;
            default:
                $val = false;
        }

        $memsize = $val / (1024 * 1024);

        return ($memsize);
    }

    private function carriers()
    {
        // Carriers Filtering
        $carrier_conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_CARRIER'));
        $carrier_list = Carrier::getCarriers(
            $this->id_lang,
            false,
            false,
            false,
            null,
            $this->ps15x ? Carrier::ALL_CARRIERS : 5
        );

        if (isset($carrier_conf) && is_array($carrier_conf)) {
            $filtered_carriers = array_flip($carrier_conf);
        } else {
            $filtered_carriers = null;
        }

        $available_carriers = array();
        $selected_carriers = array();
        foreach ($carrier_list as $carrier) {
            if (isset($filtered_carriers) && isset($filtered_carriers[(int)$carrier['id_carrier']])) {
                $selected_carriers[] = $carrier;
            } else {
                $available_carriers[] = $carrier;
            }
        }

        $view_params = array();
        $view_params['available'] = $available_carriers;
        $view_params['filtered'] = $selected_carriers;

        return ($view_params);
    }

    private function filterMap($v)
    {
        return is_array($v);
    }

    private function filterEvent(&$v, $k, $list_selected)
    {
        if (in_array($k, $list_selected)) {
            $v = null;
        }
    }

    private function mapping()
    {
        $event = new SoNiceSuiviEvent($this->context->language->iso_code);
        $mapping_conf = (array)unserialize(Configuration::get('SONICE_SUIVICOLIS_MAPPING'));

        $filtered_map = array_filter($mapping_conf, array($this, 'filterMap'));

        $list_selected = array();
        foreach ($filtered_map as $filtered) {
            $list_selected = array_merge($list_selected, $filtered);
        }

        $available_events = $event->getCodes();
        array_walk($available_events, array($this, 'filterEvent'), $list_selected);
        $available_events = array_filter($available_events);

        foreach ($mapping_conf as $id_status => $map) {
            if (!is_array($map)) {
                continue;
            }

            $mapping_conf[$id_status] = array_flip($map);

            foreach (array_keys($mapping_conf[$id_status]) as $inovert) {
                $mapping_conf[$id_status][$inovert] = $event->getCodes($inovert);
            }

            $mapping_conf[$id_status] = array_filter($mapping_conf[$id_status]);
        }

        $view_params = array();
        $view_params['available'] = $available_events;
        $view_params['filtered'] = $mapping_conf;

        return ($view_params);
    }

    private function getMailTemplate()
    {
        $iso_lang = Language::getIsoById($this->id_lang).'/';
        $mail_conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_MAIL'));
        $mapping_conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_MAPPING'));

        $files = glob($this->mail.$iso_lang.'*.html');
        if (!$files) {
            return (false);
        }

        $available_mail_tpl = array();
        $filtered_mail_tpl = array();
        foreach ($files as $file) {
            $info = pathinfo($file);
            if ($mail_conf && $mapping_conf && in_array($info['filename'], $mail_conf)) {
                $id_order_state = array_search($info['filename'], $mail_conf);

                if (in_array($id_order_state, array_keys($mapping_conf))) {
                    $filtered_mail_tpl[$id_order_state] = $info['filename'];
                }
            }
            $available_mail_tpl[] = $info['filename'];
        }

        // check for exceptions : already a template mail in ps_order_state_lang.template
        $order_state = OrderState::getOrderStates($this->id_lang);
        $default_mail_tpl = array();
        foreach ($order_state as $state) {
            if (empty($state['template'])) {
                continue;
            }

            if (in_array($state['id_order_state'], array_keys($available_mail_tpl))) {
                $filtered_mail_tpl[$state['id_order_state']] = $state['template'];
                $default_mail_tpl[$state['id_order_state']] = $state['template'];
            }
        }

        $view_params = array();
        $view_params['available'] = $available_mail_tpl;
        $view_params['filtered'] = $filtered_mail_tpl;
        $view_params['default_tpl'] = $default_mail_tpl;

        return ($view_params);
    }

    private function getOrderStateShipped()
    {
        $states = null;

        if ($this->ps15x) {
            $states = Db::getInstance()->executeS(
                'SELECT osl.*
                FROM `'._DB_PREFIX_.'order_state_lang` osl, `'._DB_PREFIX_.'order_state` os
                WHERE os.`shipped` = 1
                AND os.`id_order_state` = osl.`id_order_state`
                AND osl.`id_lang` = '.(int)$this->id_lang
            );
        } else {
            $states = Db::getInstance()->executeS(
                'SELECT osl.*
                FROM `'._DB_PREFIX_.'order_state_lang` osl, `'._DB_PREFIX_.'order_state` os
                WHERE os.`delivery` = 1
                AND os.`id_order_state` = osl.`id_order_state`
                AND osl.`id_lang` = '.(int)$this->id_lang
            );
        }

        return ($states);
    }

    public function hookAdminOrder($params)
    {
        return ($this->hookDisplayAdminOrder($params));
    }

    public function hookDisplayAdminOrder($params)
    {
        $conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_CONF'));
        $sql = 'SELECT * FROM '._DB_PREFIX_.'sonice_suivicolis WHERE `id_order` = '.(int)$params['id_order'];
        $order = new Order((int)$params['id_order']);

        if (!Validate::isLoadedObject($order)) {
            return false;
        }

        $carrier = new Carrier((int)$order->id_carrier);

        if (!Validate::isLoadedObject($carrier)) {
            return false;
        }

        if (!($result = Db::getInstance()->getRow($sql))) {
            $carrier_conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_CARRIER'));

            if (!$carrier_conf || !in_array($order->id_carrier, $carrier_conf)) {
                return false;
            }

            $result = array();
            $result['id_order'] = (int)$params['id_order'];
            $result['shipping_number'] = $order->shipping_number;
            $result['mail'] = 0;
            $result['coliposte_state'] = '';
            $result['coliposte_date'] = '';
            $result['coliposte_location'] = '';
            $result['coliposte_destination'] = '';
            $result['date_upd'] = '';
        } else {
            $destination = explode(', ', $result['coliposte_location']);
            $result['coliposte_location'] = array_shift($destination);
            $result['coliposte_destination'] = implode(', ', $destination);
        }

        if ($this->ps15x) {
            $id_order_carrier = (int)Db::getInstance()->getValue(
                'SELECT `id_order_carrier`
				FROM `'._DB_PREFIX_.'order_carrier`
				WHERE `id_order` = '.(int)$order->id.'
                ORDER BY `id_order_carrier` DESC'
            );

            $order_carrier = new OrderCarrier($id_order_carrier);

            if (Validate::isLoadedObject($order_carrier) && $order_carrier->tracking_number &&
                $order->shipping_number !== $order_carrier->tracking_number) {
                $order->shipping_number = $order_carrier->tracking_number;
            }

            if (Validate::isLoadedObject($order_carrier) && $order_carrier->tracking_number &&
                $result['shipping_number'] !== $order_carrier->tracking_number) {
                Db::getInstance()->execute(
                    'DELETE FROM `'._DB_PREFIX_.'sonice_suivicolis`
                    WHERE `shipping_number` = "'.pSQL($result['shipping_number']).'"'
                );

                Db::getInstance()->execute(
                    'UPDATE `'._DB_PREFIX_.'sonice_suivicolis`
					SET `shipping_number` = "'.pSQL($order_carrier->tracking_number).'"
					WHERE `id_order` = '.(int)$order->id
                );

                $result['shipping_number'] = $order_carrier->tracking_number;
            }
        }

        if (isset($result['date_upd']) && $result['date_upd']) {
            $timestamp = strtotime($result['date_upd']);
            $result['date_upd'] = SoNiceSuiviTools::displayDate(date('Y-m-d H:i:s', $timestamp), $this->id_lang, true);
        }

        $alert_class = array();
        $alert_class['danger'] = $this->ps16x ? 'alert alert-danger' : 'error';
        $alert_class['warning'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        $alert_class['success'] = $this->ps16x ? 'alert alert-success' : 'conf';
        $alert_class['info'] = $this->ps16x ? 'alert alert-info' : 'info';

        $token = Configuration::get('SONICE_SUIVI_TOKEN');

        $this->context->smarty->assign(
            array(
                'snsc_css_dir' => $this->url.'views/css/',
                'snsc_js_dir' => $this->url.'views/js/',
                'snsc_img_dir' => $this->img,
                'snsc_module_dir' => $this->url,
                'snsc_tracking_information' => $result,
                'snsc_get_parcel' => $this->function.'getparcels.php?token='.$token,
                'snsc_send_mail' => $this->function.'sendmails.php?token='.$token,
                'snsc_id_customer' => $order->id_customer,
                'snsc_auto_update' => (isset($conf['auto_update_order']) && $conf['auto_update_order']) ?
                    $conf['auto_update_order'] : '0',
                'snsc_token' => Tools::getAdminToken(
                    'AdminOrdersSoniceSuivi'.(int)Tab::getIdFromClassName('AdminOrdersSoniceSuivi').
                    (int)$this->context->employee->id
                ),
                'snsc_ps16x' => $this->ps16x,
                'alert_class' => $alert_class,
                'snsc_carrier_url' => $carrier->url
            )
        );

        if ($this->ps15x) {
            $this->context->controller->addCSS($this->link.'views/css/order_recap.css');
            $this->context->controller->addJS($this->link.'views/js/order_recap.js');
        }

        return $this->context->smarty->fetch($this->path.'views/templates/admin/order/displayAdminOrder.tpl');
    }

    public function hookActionAdminOrdersListingFieldsModifier($params)
    {
//        if (!isset($params['fields']['tracking_number'])) {
//            $params['fields']['tracking_number'] = array(
//                'title' => $this->l('Tracking number'),
//                'align' => 'text-center',
//                'class' => 'fixed-width-xs',
//            );
//        }
//
//        if (isset($params['select']) && !$this->trackingNumberAlreadyInSelect($params['select'])) {
//            $params['select'] = trim($params['select'], ', ').',
//                IF(a.`shipping_number`, a.`shipping_number`, oc.`tracking_number`) AS `tracking_number`';
//        }
//
//        if (isset($params['join']) && !$this->trackingNumberAlreadyInJoin($params['join'])) {
//            $params['join'] .= '
//                LEFT JOIN `'._DB_PREFIX_.'order_carrier` oc ON (a.`id_order` = oc.`id_order`)';
//        }
    }

    private function trackingNumberAlreadyInSelect($select)
    {
        $keywords = array(
            'IF(a.`shipping_number`, a.`shipping_number`, oc.`tracking_number`) AS `tracking_number`',
            'AS `tracking_number`',
            'AS tracking_number'
        );

        foreach ($keywords as $keyword) {
            if (strpos($select, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function trackingNumberAlreadyInJoin($join)
    {
        $keywords = array(
            _DB_PREFIX_.'order_carrier',
            'oc ON',
            '= oc.`id_order`'
        );

        foreach ($keywords as $keyword) {
            if (strpos($join, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    public function hookDisplayOrderDetail($params)
    {
        $this->context->smarty->assign('snc_info', print_r($params, true));

        $sql = 'SELECT * FROM '._DB_PREFIX_.'sonice_suivicolis WHERE `id_order` = '.(int)$params['order']->id;
        $order = new Order((int)$params['order']->id);

        if (!($result = Db::getInstance()->getRow($sql))) {
            $carrier_conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_CARRIER'));

            if (!$carrier_conf) {
                return false;
            }

            if (!Validate::isLoadedObject($order)) {
                return false;
            }

            if (empty($order->shipping_number)) {
                return false;
            }

            if (!in_array($order->id_carrier, $carrier_conf)) {
                return false;
            }

            $result = array();
            $result['id_order'] = (int)$params['order']->id;
            $result['shipping_number'] = $order->shipping_number;
            $result['mail'] = 0;
            $result['coliposte_state'] = '';
            $result['coliposte_date'] = '';
            $result['coliposte_location'] = '';
            $result['date_upd'] = '';
            $result['tracking_url'] =
                'http://www.colissimo.fr/portail_colissimo/suivreResultat.do?parcelnumber='.
                $order->shipping_number;
        }

        if (isset($result['date_upd']) && $result['date_upd']) {
            $timestamp = strtotime($result['date_upd']);
            $result['date_upd'] = SoNiceSuiviTools::displayDate(date('Y-m-d H:i:s', $timestamp), $this->id_lang, true);
        }

        if (!Validate::isLoadedObject($order)) {
            return (false);
        }

        $this->autoAddCSS($this->url.'views/css/order_details.css');

        $this->context->smarty->assign(
            array(
                'snsc_img_dir' => $this->img,
                'snsc_tracking_information' => $result,
                'snsc_ps16x' => $this->ps16x
            )
        );

        $html = $this->context->smarty->fetch($this->path.'views/templates/admin/tab/displayOrderDetail.tpl');

        return $html;
    }

    public function hookUpdateCarrier($params)
    {
        return ($this->hookActionCarrierUpdate($params));
    }

    public function hookActionCarrierUpdate($params)
    {
        $shops = array(
            0 => null
        );

        if ($this->ps15x && Shop::isFeatureActive()) {
            $shops = Shop::getShops(true, null, true);
        }

        foreach ($shops as $id_shop) {
            $id_shop_group = method_exists('Shop', 'getGroupFromShop') ?
                (int)Shop::getGroupFromShop($id_shop, true) : null;

            $carriers = unserialize(Configuration::get('SONICE_SUIVICOLIS_CARRIER', null, $id_shop_group, $id_shop));
            if ($carriers && count($carriers) && in_array($params['id_carrier'], $carriers)) {
                $carriers[] = $params['carrier']->id;
                Configuration::updateValue(
                    'SONICE_SUIVICOLIS_CARRIER',
                    serialize($carriers),
                    false,
                    $id_shop_group,
                    $id_shop
                );
            }
        }
    }
}
