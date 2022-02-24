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

class Fnac extends Module
{

    public $id_lang;
    private $_html = '';
    private $_postErrors = array();


    const ADD = 'a';
    const REMOVE = 'd';
    const UPDATE = 'u';

    public static $country = null;
    public static $instant_token = null;

    private $_config = array(
        /*
         *  API Access
         */
        'FNAC_PARTNER_ID' => null,
        'FNAC_SHOP_ID' => null,
        'FNAC_API_KEY' => null,
        'FNAC_API_URL' => 'https://vendeur.fnac.com/api.php/',
        'FNAC_DEBUG' => false,
        /*
         *  Caracteristiques Produits (Neuf/Occaz...)
         */
        'FNAC_FEATURE_DEFAULT' => 11,
        'FNAC_CONDITION_MAP' => null,
        'FNAC_STATUSES_MAP' => null,
        /*
         * Parametres G n raux
         */
        'FNAC_CATEGORIES' => array(),
        'FNAC_OUT_OF_STOCK' => 1,
        'FNAC_PRICE_FORMULA' => '@ + 10%',
        'FNAC_PRICE_CALLBACK' => 'number_format(round(@,1), 2, ".", "");',
        'FNAC_CUSTOMER_ID' => null,
        /*
         *  Imports / Exports
         */
        'FNAC_LAST_IMPORTED' => null,
        'FNAC_LAST_UPDATED' => null,
        'FNAC_ORDERS_REQUEST' => null,
        /*
         *  Carriers
         */
        'FNAC_CARRIER_20' => null,
        'FNAC_CARRIER_21' => null,
        'FNAC_CARRIER_22' => null,
        'FNAC_CARRIER_55' => null,
        /*
         *  Extra Fields
         */
        'FNAC_EXTRA_FIELD' => null,
        'FNAC_INSTANT_TOKEN' => null,
    );

    const Unknown = 0;
    const Created = 1;
    const Accepted = 2;
    const Refused = 3;
    const ToShip = 4;
    const Shipped = 5;
    const NotReceived = 6;
    const Received = 7;
    const Refunded = 8;
    const Cancelled = 9;
    const Error = 10;
    const Update = 11;

    public $conditions = array(
        '11' => 'Neuf',
        '1' => 'Occasion - Comme neuf',
        '2' => 'Occasion - Très bon état',
        '3' => 'Occasion - Bon &eacute;tat',
        '4' => 'Occasion - Etat correct',
        '5' => 'Collection - Comme neuf',
        '6' => 'Collection - Très bon état',
        '7' => 'Collection - Bon &eacute;tat',
        '8' => 'Collection - Etat correct',
        '10' => 'Reconditionné - À neuf'
    );
    public $statusName;

    public function __construct()
    {
        $this->name = 'fnac';
        $this->page = basename(__FILE__, '.php');
        $this->tab = 'market_place';
        $this->version = '4.0.03';
        $this->author = 'Common-Services';
        $this->module_key = 'e304c6dc71dd3c5afbe63100548ae0a6';

        parent::__construct();

        $this->displayName = $this->l('Fnac Market Place');
        $this->description = $this->l('This extension allow to sell products and retrieve orders from the FNAC MarketPlace');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        if ((defined('PS_ADMIN_DIR') || defined('_PS_ADMIN_DIR_')) && self::isInstalled($this->name)) {
            if (!function_exists('curl_init')) {
                $this->warning = $this->l('PHP cURL must be installed for this module working...');
            }

            if (!Configuration::get('PS_SHOP_ENABLE')) {
                $this->warning = $this->l('Be carefull, your shop is in maintenance mode, the module could not work in that mode');
            }

            if (!$this->active) {
                $this->warning = $this->l('Be carefull, your module is inactive, this mode stops all pending operations for this module, please change the status to active in your module list');
            }
        }

        $this->url = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/';
        $this->images = $this->url.'views/img/';
        $this->js = $this->url.'views/js/';
        $this->path = str_replace('\\', '/', dirname(__FILE__)).'/';

        $this->bootstrap = true;
        self::$country = array(
            'fr' => $this->l('France'),
            'es' => $this->l('Spain'),
            'pt' => $this->l('Portugal'),
            'be' => $this->l('Belgium')
        );

        $this->initContext();
    }

    /* Retrocompatibility 1.4/1.5 */
    private function initContext()
    {
        $this->ps16x = false;
        if (version_compare(_PS_VERSION_, '1.6', '>=')) {
            $this->ps16x = true;
        }

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $this->context = Context::getContext();

            if (!$this->context->controller instanceof AdminModulesController &&
                !$this->context->controller instanceof AdminOrdersController &&
                !strstr($_SERVER['REQUEST_URI'], $this->url)
            ) {
                return;
            }

            if (!isset($this->context->currency) || !$this->context->currency instanceof Currency) {
                $this->context->currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
            }

            if (!isset($this->context->employee->id_lang) || !$this->context->employee->id_lang) {
                $id_employee = null;
                $employee = new Employee($id_employee ? $id_employee : 1);

                // Force context for F/O scripts (ie: products options, import orders)
                $this->context->employee = $employee;
                $this->context->language = new Language($employee->id_lang);

                if (isset($this->context->language->id) && $this->context->language->id) {
                    $this->id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
                } else {
                    $this->id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
                }
            } else {
                $this->id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
            }
        } else {
            require_once(_PS_MODULE_DIR_.'fnac/backward_compatibility/backward.php');

            $this->context = Context::getContext();

            if (!isset($this->context->cookie->id_lang) || !$this->context->cookie->id_lang) {
                $this->id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
            } else {
                $this->id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
            }
        }
    }

    public function install()
    {
        require_once(dirname(__FILE__).'/classes/fnac.tools.class.php');

        $pass = true;

        foreach ($this->_config as $key => $value) {
            if (is_null($value)) {
                $value = '';
            } elseif (is_array($value)) {
                $value = FNAC_Tools::encode(serialize($value));
            }
            if (!Configuration::updateValue($key, $value)) {
                $this->_errors[] = sprintf('%s - key: %s, value: %s', $this->l('Unable to install : Some configuration values'), $key, nl2br(print_r($value, true)));
                $pass = false;
            }
        }

        if (!parent::install() || !$this->_createCustomer() || !$this->_addMarketPlaceField() || !$this->_tabSetup(self::ADD)) {
            $pass = false;
        }

        if (!$this->_addMarketPlaceTables()) {
            $this->_errors[] = $this->l('Unable to uninstall: _addMarketPlaceTables()');
            $pass = false;
        }

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            @copy(dirname(__FILE__).DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.'fnac16.gif', dirname(__FILE__).DIRECTORY_SEPARATOR.'logo.gif');
        } else {
            @copy(dirname(__FILE__).DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.'fnac32.gif', dirname(__FILE__).DIRECTORY_SEPARATOR.'logo.gif');
        }

        $this->_hookSetup(self::ADD);

        if ($pass) {
            require_once(dirname(__FILE__).'/classes/fnac.context.class.php');
            $pass = FNAC_Context::save($this->context);
        }

        return ((bool)$pass);
    }

    public function uninstall()
    {
        $pass = true;

        $this->_hookSetup(self::REMOVE);

        if (!parent::uninstall() || !$this->_deleteCustomer() || !$this->_removeMarketPlaceField() || !$this->_tabSetup(self::REMOVE)) {
            $pass = false;
        }

        if (!$this->_removeMarketPlaceTables()) {
            $this->_errors[] = $this->l('Unable to uninstall: MarketPlace Tables') && $pass = false;
        }

        foreach ($this->_config as $key => $value) {
            if (!Configuration::deleteByName($key)) {
                $pass = false;
            }
        }

        return ((bool)$pass);
    }

    public function _tabSetup($action)
    {
        // Adding Tab
        switch ($action) {
            case self::ADD :
                if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                    // For PS 1.5+
                    if (Tab::getIdFromClassName('AdminFNACProducts') && Tab::getIdFromClassName('AdminFNACOrders')) {
                        return (true);
                    }

                    if (!$this->installModuleTab('AdminFNACProducts', 'FNAC', 'AdminCatalog')) {
                        $this->_errors[] = $this->l('Unable to install: ProductsFNAC)');

                        return false;
                    }

                    $order_parent_tab = version_compare(_PS_VERSION_, '1.7', '>=') ?
                        'AdminParentOrders' : 'AdminOrders';


                    if (!$this->installModuleTab('AdminFNACOrders', 'FNAC', $order_parent_tab)) {
                        $this->_errors[] = $this->l('Unable to install: OrderFNAC');

                        return false;
                    }
                } else {
                    // For PS < 1.5
                    if (Tab::getIdFromClassName('ProductsFNAC') && Tab::getIdFromClassName('OrderFNAC')) {
                        return (true);
                    }

                    if (!$this->installModuleTab('ProductsFNAC', 'FNAC', 'AdminCatalog')) {
                        $this->_errors[] = $this->l('Unable to install: ProductsFNAC)');

                        return false;
                    }
                    if (!$this->installModuleTab('OrderFNAC', 'FNAC', 'AdminOrders')) {
                        $this->_errors[] = $this->l('Unable to install: OrderFNAC');

                        return false;
                    }
                }
                break;
            case self::UPDATE :
                if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                    // Removing Old AdminTabs
                    if (Tab::getIdFromClassName('ProductsFNAC') && Tab::getIdFromClassName('OrderFNAC')) {
                        if (!$this->uninstallModuleTab('ProductsFNAC')) {
                            $this->_errors[] = $this->l('Unable to uninstall: ProductsFNAC Tab');

                            return false;
                        }
                        if (!$this->uninstallModuleTab('OrderFNAC')) {
                            $this->_errors[] = $this->l('Unable to uninstall: OrderFNAC Tab');

                            return false;
                        }
                    }

                    // Adding New
                    return ($this->_tabSetup(self::ADD));
                }
                break;
            case self::REMOVE :
                // Removing New AdminTabs
                if (Tab::getIdFromClassName('AdminFNACOrders') && Tab::getIdFromClassName('AdminFNACProducts')) {
                    if (!$this->uninstallModuleTab('AdminFNACProducts')) {
                        $this->_errors[] = $this->l('Unable to uninstall: AdminFNACProducts Tab');

                        return false;
                    }
                    if (!$this->uninstallModuleTab('AdminFNACOrders')) {
                        $this->_errors[] = $this->l('Unable to uninstall: AdminFNACOrders Tab');

                        return false;
                    }
                }
                // Removing Old AdminTabs
                if (Tab::getIdFromClassName('ProductsFNAC') && Tab::getIdFromClassName('OrderFNAC')) {
                    if (!$this->uninstallModuleTab('ProductsFNAC')) {
                        $this->_errors[] = $this->l('Unable to uninstall: ProductsFNAC Tab');

                        return false;
                    }
                    if (!$this->uninstallModuleTab('OrderFNAC')) {
                        $this->_errors[] = $this->l('Unable to uninstall: OrderFNAC Tab');

                        return false;
                    }
                }
                break;
        }

        return true;
    }

    private function getStatusesStates()
    {
        $this->statusState = array();
        $this->statusState[self::Unknown] = 'Unknown';
        $this->statusState[self::Created] = 'Created';
        $this->statusState[self::Accepted] = 'Accepted';
        $this->statusState[self::Refused] = 'Refused';
        $this->statusState[self::ToShip] = 'ToShip';
        $this->statusState[self::Shipped] = 'Shipped';
        $this->statusState[self::NotReceived] = 'NotReceived';
        $this->statusState[self::Received] = 'Received';
        $this->statusState[self::Refunded] = 'Refunded';
        $this->statusState[self::Cancelled] = 'Cancelled';
        $this->statusState[self::Error] = 'Error';
        $this->statusState[self::Update] = 'Updated';

        return ($this->statusState);
    }

    private function getStatusesNames()
    {
        $this->statusName = array();
        $this->statusName[self::Unknown] = $this->l('Unknown');
        $this->statusName[self::Created] = $this->l('Created - Waiting for Approval');
        $this->statusName[self::Accepted] = $this->l('Accepted - Waiting for Confirmation');
        $this->statusName[self::Refused] = $this->l('Refused - Refused by us');
        $this->statusName[self::Update] = $this->l('Update - Update the Order');
        $this->statusName[self::ToShip] = $this->l('ToShip - Approved, Waiting for Shipping');
        $this->statusName[self::Shipped] = $this->l('Shipped');
        $this->statusName[self::NotReceived] = $this->l('NotReceived - Customer didn\'t receive all the items');
        $this->statusName[self::Received] = $this->l('Received - This order was successfuly delivered');
        $this->statusName[self::Refunded] = $this->l('Partially Refunded - Some products could not be received');
        $this->statusName[self::Cancelled] = $this->l('Cancelled - The order has been canceled');
        $this->statusName[self::Error] = $this->l('Error - Status is in an unauthorized or inconsistent state');
        $this->statusName[self::Update] = $this->l('Updated - Updated (Shop Side)');

        return ($this->statusName);
    }

    private function _setStatusesNames()
    {
        $statusesNames = $this->getStatusesNames();

        return (Configuration::updateValue('FNAC_STATUS_NAMES', serialize($statusesNames)));
    }

    /* HOOKS FOR BACKWARD COMPATIBILITY - PRESTASHOP 1.3 and 1.4 */

    public function hookUpdateQuantity($params)
    {
        return ($this->hookActionProductUpdate($params));
    }

    public function hookUpdateProduct($params)
    {
        return ($this->hookActionProductUpdate($params));
    }

    public function hookActionCarrierUpdate($params)
    {
        return ($this->hookUpdateCarrier($params));
    }

    public function hookAdminOrder($params)
    {
        return ($this->hookDisplayAdminOrder($params));
    }

    // HOOKs SETUP for all Prestashop releases
    private function _hookSetup($action)
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $expectedHooks = array(
                'updateQuantity',
                'updateproduct',
                'updateCarrier',
                'adminOrder',
                'backOfficeHeader',
                'postUpdateOrderStatus'
            );
        } else {
            $expectedHooks = array(
                'displayAdminOrder',
                'displayBackOfficeHeader',
                'actionCarrierUpdate',
                'actionUpdateQuantity',
                'actionProductUpdate',
                'actionOrderStatusPostUpdate',
                'actionOrderStatusUpdate',
                'displayAdminProductsExtra',
            );
        }

        $pass = true;

        if ($action == self::ADD) {
            foreach ($expectedHooks as $expectedHook) {
                if (!$this->isRegisteredInHook($expectedHook)) {
                    if (!$this->registerHook($expectedHook)) {
                        $this->_postErrors[] = $this->l('Unable to Register Hook').':'.$expectedHook;
                        $pass = false;
                    }
                }
            }
        }

        if ($action == self::REMOVE) {
            foreach ($expectedHooks as $expectedHook) {
                if ($this->isRegisteredInHook($expectedHook)) {
                    if (!$this->unregisterHook($expectedHook)) {
                        $this->_postErrors[] = $this->l('Unable to Unregister Hook').':'.$expectedHook;
                        $pass = false;
                    }
                }
            }
        }

        return ($pass);
    }

    private function _autoAddJS($url)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            if (isset($this->context->controller) && method_exists($this->context->controller, 'addJquery')) {
                $this->context->controller->addJquery();
            }

            return ($this->context->controller->addJS($url) && '');
        } else {
            return (sprintf('<script type="text/javascript" src="%s"></script>', $url));
        }
    }

    private function _autoAddCSS($url, $media = 'all')
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            return ($this->context->controller->addCSS($url, $media) && '');
        } else {
            return (sprintf('<link rel="stylesheet" type="text/css" href="%s">', $url));
        }
    }

    public function hookDisplayBackOfficeHeader($params)
    {
        if (Tools::getValue('module_name', Tools::getValue('configure')) == $this->name) {
            $this->context->controller->addJS(array(
                $this->_path.'views/js/fnac.js',
                (Configuration::get('PS_SSL_ENABLED') ? 'https:' : 'http:').
                '//cdnjs.cloudflare.com/ajax/libs/riot/3.11.1/riot+compiler.min.js'
            ));
            $this->context->controller->addCSS(array(
                $this->_path.'views/css/back.css'
            ));
        }
        
        return $this->hookbackOfficeHeader($params);
    }

    public function hookbackOfficeHeader($params)
    {
        if (!$this->active) {
            return false;
        }

        $html = null;

        $controller = Tools::strtolower(Tools::getValue('controller'));
        $tab = Tools::strtolower(Tools::getValue('tab'));
        $updateproduct = (Tools::getValue('addproduct') !== false || Tools::getValue('updateproduct') !== false) && Tools::getValue('id_product') !== false;

        if ((version_compare(_PS_VERSION_, '1.5', '<') && $tab == 'admincatalog' && $updateproduct) ||
            (version_compare(_PS_VERSION_, '1.5', '>=') && $controller == 'adminproducts' && $updateproduct) ||
            (version_compare(_PS_VERSION_, '1.7', '>=') && $controller == 'adminproducts')) {
            
            $this->context->controller->addJS(array(
                $this->_path.'views/js/back.js',
                '//cdnjs.cloudflare.com/ajax/libs/riot/3.11.1/riot+compiler.min.js'
            ));
            
            $html .= '<meta name="fnac-options" content="'.$this->url.'functions/product_ext.php" />'."\n";
            $html .= '<meta name="fnac-options-json" content="'.$this->url.'functions/product_ext.json.php" />'."\n";
            //$html .= '<meta name="fnac-options-json" content="'.$this->url.'functions/product_ext.json.php" />'."\n";

            $html .= $this->_autoAddCSS($this->url.'views/css/product_ext.css');
            $html .= $this->_autoAddJS($this->url.'views/js/product_extfnac.js');
        }

        if (version_compare(_PS_VERSION_, '1.4', '<')) {
            print($html);
        } else {
            return ($html);
        }
    }

    // Update date_upd on stock moves ! (This is a Prestashop bug: PS doesn't update date_upd field on stock moves...)
    public function hookActionUpdateQuantity($params)
    {
        return ($this->hookActionProductUpdate($params));
    }

    public function hookActionProductUpdate($params)
    {
        if (file_exists(dirname(__FILE__).'/classes/fnac.product.class.php')) {
            require_once(dirname(__FILE__).'/classes/fnac.product.class.php');
        }

        $id_product = 0;

        // Maintain compatibility accross PS releases ...
        if (isset($params['product']) && is_object($params['product'])) {
            $id_product = (int)$params['product']->id;
        } elseif (isset($params['id_product'])) {
            $id_product = $params['id_product'];
        } elseif (isset($params['product']['id_product'])) {
            $id_product = (int)$params['product']['id_product'];
        }

        FNAC_Product::updateProductDate($id_product);
    }

    private function _postValidation()
    {
        $partner_id = Tools::getValue('partner_id');
        $shop_id = Tools::getValue('shop_id');
        $api_key = Tools::getValue('api_key');
        $api_url = Tools::getValue('api_url');
        $marketplace_set = array();

        foreach (array_keys(self::$country) as $key) {
            if (is_array($partner_id) && (string)$partner_id[$key] || is_array($shop_id) && (string)$shop_id[$key] || is_array($api_key) &&
                (string)$api_key[$key] || is_array($api_url) && (string)$api_url[$key]
            ) {
                $marketplace_set[] = $key;
            }
        }

        if (!count($marketplace_set)) {
            $this->_postErrors[] = $this->l('You must configure at least one FNAC Marketplace.');
        } else {
            foreach (self::$country as $key => $value) {
                if (in_array($key, $marketplace_set) && (!is_array($partner_id) || !((string)$partner_id[$key]))) {
                    $this->_postErrors[] = $this->l('You must enter : Partner ID for ').$value;
                }
                if (in_array($key, $marketplace_set) && (!is_array($shop_id) || !((string)$shop_id[$key]))) {
                    $this->_postErrors[] = $this->l('You must enter : Shop ID for ').$value;
                }
                if (in_array($key, $marketplace_set) && (!is_array($api_key) || !((string)$api_key[$key]))) {
                    $this->_postErrors[] = $this->l('You must enter : API Key for ').$value;
                }
                if (in_array($key, $marketplace_set) && (!is_array($api_url) || !((string)$api_url[$key]))) {
                    $this->_postErrors[] = $this->l('You must enter : API URL for ').$value;
                }

                if (in_array($key, $marketplace_set) && !preg_match('/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/', $partner_id[$key])) {
                    $this->_postErrors[] = sprintf('Partner id %s %s', $value, $this->l('is not valide'));
                }
                if (in_array($key, $marketplace_set) && !preg_match('/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/', $shop_id[$key])) {
                    $this->_postErrors[] = sprintf('Shop id %s %s', $value, $this->l('is not valide'));
                }
            }
        }

        if (!(string)Tools::getValue('carrier_20') || !(string)Tools::getValue('carrier_21') || !(string)Tools::getValue('carrier_22')) {
            $this->_postErrors[] = $this->l('You must selected an appropriate carrier for each shipping mode');
        }

        $condition_map = Tools::getValue('condition_map');
        // PS < 1.4
        if (property_exists('Product', 'condition')) {
            if (!isset($condition_map) || empty($condition_map[11])) {
                $this->_postErrors[] = $this->l('The condition map must be filled').'<br />';
            }
        }

        if (!is_array(Tools::getValue('categoryBox')) || !count(Tools::getValue('categoryBox'))) {
            $this->_postErrors[] = $this->l('You must select at least 1 category.');
        }

        // For Updates
        if (!$this->_addMarketPlaceField()) {
            $this->warning = '_addMarketPlaceField() failed';
        }
        if (!$this->_addMarketPlaceTables()) {
            $this->warning = '_addMarketPlaceTables() failed';
        }
    }

    private function _postProcess()
    {
        if (file_exists(dirname(__FILE__).'/classes/fnac.tools.class.php')) {
            require_once(dirname(__FILE__).'/classes/fnac.tools.class.php');
        }
        require_once(dirname(__FILE__).'/classes/fnac.context.class.php');

        $this->_hookSetup(self::ADD);
        $this->_tabSetup(self::UPDATE);

        FNAC_Context::save($this->context);

        $partner_id = Tools::getValue('partner_id');
        $shop_id = Tools::getValue('shop_id');
        $api_key = Tools::getValue('api_key');
        $api_url = Tools::getValue('api_url');

        if (is_array($partner_id) && isset($partner_id['fr'])) {
            Configuration::updateValue('FNAC_PARTNER_ID', $partner_id['fr']);
            Configuration::updateValue('FNAC_SHOP_ID', $shop_id['fr']);
            Configuration::updateValue('FNAC_API_KEY', $api_key['fr']);
            Configuration::updateValue('FNAC_API_URL', $api_url['fr']);
        }
        if (is_array($partner_id) && isset($partner_id['es'])) {
            Configuration::updateValue('FNAC_ES_PARTNER_ID', $partner_id['es']);
            Configuration::updateValue('FNAC_ES_SHOP_ID', $shop_id['es']);
            Configuration::updateValue('FNAC_ES_API_KEY', $api_key['es']);
            Configuration::updateValue('FNAC_ES_API_URL', $api_url['es']);
        }
        if (is_array($partner_id) && isset($partner_id['pt'])) {
            Configuration::updateValue('FNAC_PT_PARTNER_ID', $partner_id['pt']);
            Configuration::updateValue('FNAC_PT_SHOP_ID', $shop_id['pt']);
            Configuration::updateValue('FNAC_PT_API_KEY', $api_key['pt']);
            Configuration::updateValue('FNAC_PT_API_URL', $api_url['pt']);
        }
        if (is_array($partner_id) && isset($partner_id['be'])) {
            Configuration::updateValue('FNAC_BE_PARTNER_ID', $partner_id['be']);
            Configuration::updateValue('FNAC_BE_SHOP_ID', $shop_id['be']);
            Configuration::updateValue('FNAC_BE_API_KEY', $api_key['be']);
            Configuration::updateValue('FNAC_BE_API_URL', $api_url['be']);
        }
        Configuration::updateValue('FNAC_DEBUG', Tools::getValue('fnac_debug'));
        Configuration::updateValue('FNAC_OUT_OF_STOCK', (int)Tools::getValue('outofstock'));
        Configuration::updateValue('FNAC_DISCOUNT', (int)Tools::getValue('discount'));
        Configuration::updateValue('FNAC_SALES', (int)Tools::getValue('sales'));
        Configuration::updateValue('FNAC_NAME_REF', (int)Tools::getValue('name_ref'));
        Configuration::updateValue('FNAC_PRICE_FORMULA', Tools::getValue('formula'));
        Configuration::updateValue('FNAC_PRICE_CALLBACK', Tools::getValue('pcallback'));
        Configuration::updateValue('FNAC_CARRIER_20', (int)Tools::getValue('carrier_20'));
        Configuration::updateValue('FNAC_CARRIER_21', (int)Tools::getValue('carrier_21'));
        Configuration::updateValue('FNAC_CARRIER_22', (int)Tools::getValue('carrier_22'));
        Configuration::updateValue('FNAC_CARRIER_55', (int)Tools::getValue('carrier_55'));
        Configuration::updateValue('FNAC_TIME_TO_SHIP', (int)Tools::getValue('time_to_ship'));

        Configuration::updateValue('FNAC_PRICE_LIMITER', serialize(Tools::getValue('price_limiter')));

        Configuration::updateValue(
            'FNAC_CARRIERS_MAPPING',
            serialize((array)Tools::getValue('carriers_mapping'))
        );

        $categories = Tools::getValue("categoryBox");
        Configuration::updateValue('FNAC_CATEGORIES', FNAC_Tools::encode(serialize($categories)));
        Configuration::updateValue('FNAC_LOGISTIC_TYPES', FNAC_Tools::encode(serialize(array_filter(Tools::getValue('logistic_type_id')))));

        // For PS 1.5+
        Configuration::updateValue('FNAC_WAREHOUSE', (int)Tools::getValue('warehouse'));

        $condition_map = Tools::getValue('condition_map');
        foreach ($condition_map as $key => $condition) {
            $condition_map[htmlentities($key, ENT_NOQUOTES, "UTF-8")] = htmlentities($condition, ENT_NOQUOTES, "UTF-8");
        }

        Configuration::updateValue('FNAC_CONDITION_MAP', FNAC_Tools::encode(serialize($condition_map)));

        $context_key = FNAC_Context::getKey($this->context->shop);

        // Cron URLs
        foreach (self::$country as $key => $value) {
            if ($key == 'fr') {
                $lang = '';
            } else {
                $lang = Tools::strtoupper($key).'_';
            }

            if (isset($api_key[$key])) {
                $cron_token = md5(trim($api_key[$key]));

                $productsCronUrl = FNAC_Tools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/functions/products.php?cron=1&platform='.$key.'&action=update&context_key='.$context_key.'&cron_token='.$cron_token;
                $ordersCronUrl = FNAC_Tools::getHttpHost(true, true).__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/functions/import.php?cron=1&platform='.$key.'&context_key='.$context_key.'&cron_token='.$cron_token;

                Configuration::updateValue('FNAC_'.$lang.'CRON_URL_ORDERS', $ordersCronUrl);
                Configuration::updateValue('FNAC_'.$lang.'CRON_URL_PRODUCTS', $productsCronUrl);
            }
        }

        // Statuses Map
        $statuses = Tools::getValue('order_state_map_mp'); // marketplace statuses

        Configuration::updateValue('FNAC_STATUSES_MAP', serialize($statuses));

        if ($this->ps16x) {
            $this->_html .= '<div class="bootstrap">'.$this->displayConfirmation('Configuration updated').'</div>';
        } else {
            $this->_html .= '<div class="conf confirm">'.$this->l('Configuration updated').'</div>';
        }

        @chmod(dirname(__FILE__).DIRECTORY_SEPARATOR.'exports/', 0775);

        $this->_addMarketPlaceField();

        $fnac_customer = new Customer((int)Configuration::get('FNAC_CUSTOMER_ID'));
        if (!Validate::isLoadedObject($fnac_customer)) {
            $this->_createCustomer();
        }
    }

    private function selected_tab()
    {
        return Tools::getValue('selected_tab', $this->name);
    }

    public function getContent()
    {
        require_once(dirname(__FILE__).'/classes/fnac.context.class.php');

        Configuration::updateValue('FNAC_INSTANT_TOKEN', self::$instant_token = md5(_PS_ROOT_DIR_._PS_VERSION_.(isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time())), false, 0, 0);

        $view_params = array();
        $view_params['PS16_class'] = '';

        if (version_compare(_PS_VERSION_, '1.6', '>=')) {
            $view_params['PS16_class'] = true;
        }

        $view_params['display_name'] = $this->displayName;
        $view_params['errors_list'] = array();

        if (Tools::isSubmit('submit')) {
            $this->_postValidation();
            $this->_postProcess();

            if (count($this->_postErrors) > 0) {
                foreach ($this->_postErrors as $err) {
                    $view_params['errors_list'][] = $err;
                }
            }
        }

        $view_params['path'] = $this->url;
        $view_params['js_url'] = $this->js;
        $view_params['images_url'] = $this->images;
        $view_params['module_url'] = $this->url;
        $view_params['request_uri'] = $_SERVER['REQUEST_URI'];
        $view_params['version'] = $this->version;
        $view_params['description'] = $this->description;
        $view_params['loader'] = $this->images.'loading.gif';

        $context_key = FNAC_Context::getKey($this->context->shop);
        $export = $this->url.'functions/parameters.php?context_key='.$context_key;

        $view_params['export_url'] = $export;

        if (version_compare(_PS_VERSION_, '1.4', '<')) {
            $view_params['style'] = 'style="width:98%"';
        } else {
            $view_params['style'] = '';
        }

        $alert_class = array();
        $alert_class['danger'] = $this->ps16x ? 'alert alert-danger' : 'error';
        $alert_class['warning'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        $alert_class['success'] = $this->ps16x ? 'alert alert-success' : 'conf';
        $alert_class['info'] = $this->ps16x ? 'alert alert-info' : 'info';
        $view_params['alert_class'] = $alert_class;

        //Tabs Data
        $view_params['tools_url'] = $this->url.'functions/tools.php?instant_token='.self::$instant_token.'&context_key='.
            FNAC_Context::getKey($this->context->shop);
        $view_params['fnac_informations'] = $this->_informations();
        $view_params['fnac_cretentials'] = $this->_credentials();
        $view_params['fnac_categories'] = $this->_categories();
        $view_params['fnac_transport'] = $this->_transport();
        $view_params['fnac_orders'] = $this->_orders();
        $view_params['fnac_settings'] = $this->_settings();
        $view_params['fnac_filters'] = $this->_filters();
        $view_params['fnac_cron'] = $this->_cron();

        $post_process = null;
        if (((bool)Tools::isSubmit('submit')) == true) {
            $post_process = true;
        }
        
        $view_params['module_dir'] = $this->_path;
        $view_params['module_name'] = $this->displayName;
        $view_params['module_version'] = $this->version;
        $view_params['module_description'] = $this->description;
        $view_params['images_url'] = $this->_path.'views/img/';
        $view_params['module_img_dir'] = $this->_path.'views/img/';
        $view_params['post_process'] = $post_process;
        $view_params['selected_tab'] = Tools::getValue('submit', $this->selected_tab());

        $this->context->smarty->assign($view_params);

        return $this->display(__FILE__, 'views/templates/admin/configuration/configure.tpl').
            $this->display(__FILE__, 'views/templates/admin/prestui/ps-tags.tpl');
    }

    public function _cron()
    {
        $token = FNAC_Tools::decode(Configuration::get('FNAC_PS_TOKEN'));
        $view_params = array();
        $context_key = null;
        $view_params['country'] = self::$country;

        foreach (self::$country as $key => $value) {
            if ($key == 'fr') {
                $lang = '';
            } else {
                $lang = Tools::strtoupper($key).'_';
            }

            $partner_id = Configuration::get('FNAC_'.$lang.'PARTNER_ID');
            $shop_id = Configuration::get('FNAC_'.$lang.'SHOP_ID');
            $api_key = Configuration::get('FNAC_'.$lang.'API_KEY');
            $api_url = Configuration::get('FNAC_'.$lang.'API_URL');

            if ($partner_id == null || $shop_id == null || $api_key == null || $api_url == null) {
                $view_params[$key]['cron'] = null;
            } else {
                $view_params[$key]['cron'] = '1';
            }

            $view_params[$key]['products_url_cron'] = Configuration::get('FNAC_'.$lang.'CRON_URL_PRODUCTS');
            $view_params[$key]['orders_url_cron'] = Configuration::get('FNAC_'.$lang.'CRON_URL_ORDERS');
        }

        //check cron installed?
        $view_params['cronjobs']['exists'] = is_dir(_PS_MODULE_DIR_.'cronjobs/');
        $view_params['cronjobs']['installed'] = (bool)FNAC_Tools::moduleIsInstalled('cronjobs');
        $view_params['stdtypes'] = array('update', 'import');

        foreach (self::$country as $key => $value) {
            $view_params['update'][$key]['title'] = $this->l($value.' Offers Update');
            $view_params['import'][$key]['title'] = $this->l($value.' Orders Import');

            $view_params['update'][$key]['url'] = $view_params[$key]['products_url_cron'];
            $view_params['update'][$key]['url_short'] = preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', $view_params[$key]['products_url_cron']);


            $view_params['import'][$key]['url'] = $view_params[$key]['orders_url_cron'];
            $view_params['import'][$key]['url_short'] = preg_replace('/(?<=^.{16}).{4,}(?=.{16}$)/', '...', $view_params[$key]['orders_url_cron']);
        }
        $view_params['update']['frequency'] = 1;
        $view_params['import']['frequency'] = 1;


        return ($view_params);
    }


    private function _informations()
    {
        if ((bool)Configuration::get('PS_FORCE_SMARTY_2') == true) {
            die(sprintf('<div class="error">%s</span>', Tools::displayError('This module is not compatible with Smarty v2. Please switch to Smarty v3 in Preferences Tab.')));
        }

        $lang = Language::getIsoById($this->id_lang);
        $display = false;

        $php_infos = array();
        $prestashop_infos = array();

        // PHP Configuration Check
        if (!function_exists('curl_init')) {
            $php_infos['curl'] = array();
            $php_infos['curl']['message'] = $this->l('PHP cURL must be installed on this server. The module require the cURL library and can\'t work without');
            $php_infos['curl']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
            $php_infos['curl']['level_text'] = 'error';
            $php_infos['curl']['link'] = 'http://php.net/manual/'.$lang.'/book.curl.php';
        }
        $rootDir = _PS_MODULE_DIR_.'fnac/';

        if (is_dir($rootDir.'export') && !is_writable($rootDir.'export')) {
            $php_infos['export_permissions']['message'] = sprintf($this->l('You have to set write permissions to the %s directory and its subsequents files'), $rootDir.'export');
            $php_infos['export_permissions']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
            $php_infos['export_permissions']['level_text'] = 'error';
        }
        if (is_dir($rootDir.'export/xml') && !is_writable($rootDir.'export/xml')) {
            $php_infos['export_permissions_xml']['message'] = sprintf($this->l('You have to set write permissions to the %s directory and its subsequents files'), $rootDir.'export');
            $php_infos['export_permissions_xml']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
            $php_infos['export_permissions_xml']['level_text'] = 'error';
        }
        if (is_dir($rootDir.'export/csv') && !is_writable($rootDir.'export/csv')) {
            $php_infos['export_permissions_csv']['message'] = sprintf($this->l('You have to set write permissions to the %s directory and its subsequents files'), $rootDir.'csv');
            $php_infos['export_permissions_csv']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
            $php_infos['export_permissions_csv']['level_text'] = 'error';
        }

        if (($max_execution_time = ini_get('max_execution_time')) && $max_execution_time < 120) {
            $php_infos['maintenance']['message'] = sprintf($this->l('PHP value: max_execution_time recommended value is at least 120. your limit is currently set to %d').'<br />', $max_execution_time);
            $php_infos['maintenance']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
            $php_infos['maintenance']['level_text'] = 'error';
        }

        // Memory Limit
        $memory_limit = ini_get('memory_limit');
        $unit = Tools::strtolower(Tools::substr($memory_limit, -1));
        $val = (float)preg_replace('[^0-9]', '', $memory_limit);
        switch ($unit) {
            case 'g' :
                $val = $val * 1024 * 1024 * 1024;
                break;
            case 'm' :
                $val = $val * 1024 * 1024;
                break;
            case 'k' :
                $val = $val * 1024;
                break;
            default :
                $val = false;
        }

        // Switch to MB
        $memory_limit = $val / (1024 * 1024);
        $required_memory = 128;

        if ($memory_limit < $required_memory) {
            $php_infos['memory']['message'] = sprintf($this->l('PHP value: memory_limit recommended value is at least %sMB. your limit is currently set to %sMB').'<br />', $required_memory, $memory_limit);
            $php_infos['memory']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
            $php_infos['memory']['level_text'] = 'warn';
        }

        // Prestashop Configuration Check
        if (!(int)Configuration::get('PS_SHOP_ENABLE')) {
            $prestashop_infos['maintenance']['message'] = $this->l('Be carefull, your shop is in maintenance mode, the module might not work in that mode');
            $prestashop_infos['maintenance']['level'] = $this->ps16x ? 'alert alert-warning' : 'warn';
            $prestashop_infos['maintenance']['level_text'] = 'warn';
        }

        // Check if birthday is mandatory
        $pass = true;
        $customerCheck = new Customer();

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $customRequiredFields = $customerCheck->getfieldsRequiredDatabase();

            if (is_array($customRequiredFields) && count($customRequiredFields)) {
                foreach ($customRequiredFields as $customRequiredField) {
                    if (isset($customRequiredField['field_name']) && $customRequiredField['field_name'] == 'birthday') {
                        $pass = false;
                    }
                }
            }
        }

        $customerRules = $customerCheck->getValidationRules('Customer');
        $pass = $pass && !(is_array($customerRules['required']) && in_array('birthday', $customerRules['required']));

        if (!$pass) {
            $prestashop_infos['birthday_issue']['message'] = $this->l('Birthday field is required which is not the default in Prestashop core program. This configuration is not allowed by Marketplaces modules. Please fix it !');
            $prestashop_infos['birthday_issue']['level'] = $this->ps16x ? 'alert alert-danger' : 'error';
            $prestashop_infos['birthday_issue']['level_text'] = 'error';
        }


        if (!count($prestashop_infos)) {
            $prestashop_info_ok = true;
        } else {
            $prestashop_info_ok = false;
        }

        if (!count($php_infos)) {
            $php_info_ok = true;
        } else {
            $php_info_ok = false;
        }


        $view_params = array();
        $view_params['images'] = $this->images;
        $view_params['display'] = $display;
        $view_params['php_infos'] = $php_infos;
        $view_params['php_info_ok'] = $php_info_ok;
        $view_params['prestashop_infos'] = $prestashop_infos;
        $view_params['prestashop_info_ok'] = $prestashop_info_ok;
        $view_params['url'] = $this->url;
        $view_params['support_informations_url'] = $this->url.'functions/check.php?id_lang='.$this->id_lang.'&instant_token='.self::$instant_token;

        return $view_params;
    }

    private function _credentials()
    {
        $view_params = array();

        $debug = Configuration::get('FNAC_DEBUG');
        foreach (self::$country as $key => $value) {
            if ($key == 'fr') {
                $lang = '';
            } else {
                $lang = Tools::strtoupper($key).'_';
            }

            $partner_id = Configuration::get('FNAC_'.$lang.'PARTNER_ID');
            $shop_id = Configuration::get('FNAC_'.$lang.'SHOP_ID');
            $api_key = Configuration::get('FNAC_'.$lang.'API_KEY');
            $api_url = Configuration::get('FNAC_'.$lang.'API_URL');

            $view_params[$key] = array();
            $view_params[$key]['partner_id'] = $partner_id;
            $view_params[$key]['shop_id'] = $shop_id;
            $view_params[$key]['api_key'] = $api_key;
            $view_params[$key]['api_url'] = $api_url;
        }

        // Global
        $view_params['images_url'] = $this->images;

        $view_params['debug_checked'] = $debug ? 'checked="checked"' : '';
        $view_params['debug_style'] = $debug ? ' style="color:red" ' : '';

        return $view_params;
    }

    private function _categories()
    {
        require_once(dirname(__FILE__).'/classes/fnac.tools.class.php');

        $view_params = array();
        $id_shop = Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;

        //Categories
        // prefered categories
        if (($categories = Configuration::get('FNAC_CATEGORIES'))) {
            $default_categories = unserialize(FNAC_Tools::decode($categories));
        } else {
            $default_categories = null;
        }

        // logistic_type_id
        $logistic_type_ids = unserialize(FNAC_Tools::decode(Configuration::get('FNAC_LOGISTIC_TYPES')));

        // Parsing categories tree
        $categories = Category::getCategories((int)$this->id_lang, false);

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $shop = new Shop($id_shop);
            $first = null;

            foreach ($categories as $categories1) {
                foreach ($categories1 as $category) {
                    if ($category['infos']['id_category'] == Category::getRootCategory(null, $shop)->id_category) {
                        $first = $category;
                    }
                }
            }

            $default_category = $shop->id_category;
        } else {
            foreach ($categories as $first1 => $categories_array) {
                foreach ($categories_array as $first2 => $categories_array2) {
                    $first = $categories[$first1][$first2];
                    break;
                }
            }
            $default_category = 1;
        }

        $index = array();
        $view_params['categories_html'] = FNAC_Tools::recurseCategoryForInclude($index, $categories, $first, $default_category, null, $default_categories, $this->images, true, $logistic_type_ids);

        return $view_params;
    }

    private function _transport()
    {
        require_once(dirname(__FILE__).'/classes/fnac.carrier.enum.class.php');

        $view_params = array();

        $id_shop = Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
        if (!$id_shop) {
            $id_shop = 1;
        }

        $carriers = Carrier::getCarriers($this->id_lang, false, false, false, null, Carrier::ALL_CARRIERS);
        $selected_carrier_20 = (int)Configuration::get('FNAC_CARRIER_20');
        $selected_carrier_21 = (int)Configuration::get('FNAC_CARRIER_21');
        $selected_carrier_22 = (int)Configuration::get('FNAC_CARRIER_22');
        $selected_carrier_55 = (int)Configuration::get('FNAC_CARRIER_55');
        $selected_carriers = array(
            $selected_carrier_20,
            $selected_carrier_21,
            $selected_carrier_22,
            $selected_carrier_55
        );

        $view_params['carrier_20_options'] = array();
        $view_params['carrier_21_options'] = array();
        $view_params['carrier_22_options'] = array();
        $view_params['carrier_55_options'] = array();

        foreach ($carriers as $carrier) {
            $view_params['carrier_20_options'][] = array(
                'selected' => (int)$carrier['id_carrier'] == $selected_carrier_20 ? 'selected="selected"' : '',
                'value' => (int)$carrier['id_carrier'],
                'desc' => $carrier['name']
            );
            $view_params['carrier_21_options'][] = array(
                'selected' => (int)$carrier['id_carrier'] == $selected_carrier_21 ? 'selected="selected"' : '',
                'value' => (int)$carrier['id_carrier'],
                'desc' => $carrier['name']
            );
            $view_params['carrier_22_options'][] = array(
                'selected' => (int)$carrier['id_carrier'] == $selected_carrier_22 ? 'selected="selected"' : '',
                'value' => (int)$carrier['id_carrier'],
                'desc' => $carrier['name']
            );
            $view_params['carrier_55_options'][] = array(
                'selected' => (int)$carrier['id_carrier'] == $selected_carrier_55 ? 'selected="selected"' : '',
                'value' => (int)$carrier['id_carrier'],
                'desc' => $carrier['name']
            );
        }

        $id_warehouse = Configuration::get('FNAC_WAREHOUSE');

        $view_params['version_15'] = version_compare(_PS_VERSION_, '1.5', '>=');
        // Shop Configuration
        if ($view_params['version_15']) {
            $view_params['version_15_shop'] = '1';
            $current_id_shop = (int)$id_shop;
            $view_params['shop_options'] = array();

            foreach (Shop::getShops() as $shop) {
                if ((int)$shop['id_shop'] == $current_id_shop) {
                    $selected = 'selected="selected"';
                } else {
                    $selected = '';
                }
                $view_params['shop_options'][] = array(
                    'selected' => $selected,
                    'value' => (int)$shop['id_shop'],
                    'desc' => $shop['name']
                );
            }

            // Warehouse (PS 1.5 with Stock Management)
            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                $view_params['ps_asm'] = '1';
                $view_params['asm_options'] = array();
                $current_id_warehouse = (int)$id_warehouse;

                foreach (Warehouse::getWarehouses(true) as $warehouse) {
                    if ((int)$warehouse['id_warehouse'] == $current_id_warehouse) {
                        $selected = 'selected="selected"';
                    } else {
                        $selected = '';
                    }
                    $view_params['asm_options'][] = array(
                        'selected' => $selected,
                        'value' => (int)$warehouse['id_warehouse'],
                        'desc' => $warehouse['name']
                    );
                }
            }
        }

        // Mapping
        $view_params['fnac_carriers'] = FNAC_CarrierEnum::$carriers;
        $view_params['selected_carriers'] = array_filter($carriers, function ($carrier) use ($selected_carriers) {
            return in_array($carrier['id_carrier'], $selected_carriers);
        });
        $view_params['selected_carriers_mapping'] = unserialize(Configuration::get('FNAC_CARRIERS_MAPPING'));

        // Time to ship
        $view_params['time_to_ship'] = Configuration::get('FNAC_TIME_TO_SHIP');

        return $view_params;
    }

    private function _orders()
    {
        $view_params = array();

        // Carto des status FNAC et Prestashop
        $orderStates = new OrderState();
        $stateList = $orderStates->getOrderStates($this->id_lang);
        $statuses = $this->getStatusesNames();

        // Already saved statuses
        $mp_statuses = Configuration::get('FNAC_STATUSES_MAP');

        if (isset($mp_statuses) && !empty($mp_statuses)) {
            $mp_statuses = unserialize($mp_statuses);
        } else {
            $mp_statuses = array();
        }

        $view_params['statuses'] = array();
        $limit = count($statuses);
        for ($i = 0; $i < $limit; $i++) {
            $status_options = array();
            foreach ($stateList as $orderState) {
                if (isset($mp_statuses[$i])) {
                    $selected = $orderState['id_order_state'] == $mp_statuses[$i] ? 'selected="selected"' : '';
                } else {
                    $selected = '';
                }
                $status_options[] = array(
                    'selected' => $selected,
                    'value' => $orderState['id_order_state'],
                    'desc' => $orderState['name']
                );
            }
            $view_params['statuses'][] = array(
                'status' => $statuses[$i],
                'options' => $status_options
            );
        }

        return $view_params;
    }

    private function _settings()
    {
        $view_params = array();
        $id_shop = Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
        if (!$id_shop) {
            $id_shop = 1;
        }

        $view_params['version_15'] = version_compare(_PS_VERSION_, '1.5', '>=');

        // Stock
        $outOfStock = Configuration::get('FNAC_OUT_OF_STOCK');

        // Use Discount/Specials
        $discount = Configuration::get('FNAC_DISCOUNT');
        $sales = Configuration::get('FNAC_SALES');

        // Ref in product name
        $name_ref = Configuration::get('FNAC_NAME_REF');

        if ($discount === false) { // default value
            $discount = true;
        }

        // Price Formula and Callback
        $formula = Configuration::get('FNAC_PRICE_FORMULA');
        $callback = Configuration::get('FNAC_PRICE_CALLBACK');

        if (empty($formula)) {
            $formula = '@';
        }

        if (empty($callback)) {
            $callback = '@';
        }

        $view_params['out_of_stock'] = $outOfStock;
        $view_params['discount'] = $discount;
        $view_params['sales'] = $sales;
        $view_params['name_ref'] = $name_ref;
        $view_params['formula'] = $formula;
        $view_params['callback'] = $callback;

        $conditionMap = unserialize(FNAC_Tools::decode(Configuration::get('FNAC_CONDITION_MAP')));

        // Products Condition/State
        $sql = 'SHOW COLUMNS FROM `'._DB_PREFIX_.'product` where Field = "condition"';
        $query = Db::getInstance()->ExecuteS($sql);

        if (is_array($query)) {
            $query = array_shift($query);
        }


        if (isset($query['Field']) && $query['Field'] == 'condition') {
            $view_params['product_conditions'] = array();
            $ps_conditions = array();
            // For i18n
            $default_conditions = array($this->l('new'), $this->l('used'), $this->l('refurbished'));

            // Fetch columns names
            preg_match_all("/'([\w ]*)'/", $query['Type'], $ps_conditions);

            $i = 1;
            foreach ($this->conditions as $key => $condition) {
                $options = array();
                foreach ($ps_conditions[1] as $ps_condition) {
                    $currentCondition = $key;
                    if (isset($conditionMap[$currentCondition]) && !empty($conditionMap[$currentCondition]) && $conditionMap[$currentCondition] == $ps_condition) {
                        $selected = 'selected="selected"';
                    } else {
                        $selected = '';
                    }
                    $options[] = array(
                        'selected' => $selected,
                        'value' => $ps_condition,
                        'desc' => Tools::ucfirst($this->l($ps_condition))
                    );
                }

                $view_params['product_conditions'][] = array(
                    'condition' => $condition,
                    'key' => $key,
                    'index' => $i,
                    'options' => $options
                );
                $i++;
            }
        }


        return $view_params;
    }


    public function _filters()
    {
        return array(
            'price_limiter' => Tools::unSerialize(Configuration::get('FNAC_PRICE_LIMITER'))
        );
    }

    public function hookUpdateCarrier($params)
    {
        if ((int)$params['id_carrier'] == Configuration::get('FNAC_CARRIER_20')) {
            Configuration::updateValue('FNAC_CARRIER_20', (int)$params['carrier']->id);
        }
        if ((int)$params['id_carrier'] == Configuration::get('FNAC_CARRIER_21')) {
            Configuration::updateValue('FNAC_CARRIER_21', (int)$params['carrier']->id);
        }
        if ((int)$params['id_carrier'] == Configuration::get('FNAC_CARRIER_22')) {
            Configuration::updateValue('FNAC_CARRIER_22', (int)$params['carrier']->id);
        }
        if ((int)$params['id_carrier'] == Configuration::get('FNAC_CARRIER_55')) {
            Configuration::updateValue('FNAC_CARRIER_55', (int)$params['carrier']->id);
        }
    }

    /* Gestion des relations avec la MarketPlace sur la commande */
    public function hookDisplayAdminOrder($params)
    {
        require_once(dirname(__FILE__).'/classes/fnac.webservice.class.php');

        require_once(dirname(__FILE__).'/classes/fnac.tools.class.php');
        require_once(dirname(__FILE__).'/classes/fnac.order.class.php');
        require_once(dirname(__FILE__).'/classes/fnac.context.class.php');

        // d? d?ni dans fnac.webservice.class.php mais bug avec la translation i18n/smarty de prestashop ? */
        // http://www.prestashop.com/forums/viewthread/97423/rapports_de_bugs/i18n__panneau_dadministration__outils__traductions___classes_adjacentes___constantes_/ */

        $statusName = array();
        $statusName[FnacAPI::Unknown] = $this->l('Unknown');
        $statusName[FnacAPI::Created] = $this->l('Created - Waiting for Approval');
        $statusName[FnacAPI::Accepted] = $this->l('Accepted - Waiting for Confirmation');
        $statusName[FnacAPI::Refused] = $this->l('Refused - Refused by us');
        $statusName[FnacAPI::Update] = $this->l('Update - Update the Order');
        $statusName[FnacAPI::ToShip] = $this->l('ToShip - Approved, Waiting for Shipping');
        $statusName[FnacAPI::Shipped] = $this->l('Shipped');
        $statusName[FnacAPI::NotReceived] = $this->l('NotReceived - Customer didn\'t receive all the items');
        $statusName[FnacAPI::Received] = $this->l('Received - This order was successfuly delivered');
        $statusName[FnacAPI::Refunded] = $this->l('Partially Refunded - Some products could not be received');
        $statusName[FnacAPI::Cancelled] = $this->l('Cancelled - The order has been canceled');
        $statusName[FnacAPI::Error] = $this->l('Error - Status is in an unauthorized or inconsistent state');

        // jQuery
        $jsFile = dirname(__FILE__).'/views/js/adminorder.js';
        $context_key = FNAC_Context::getKey($this->context->shop);

        $img = $this->images.'logo.png';
        $loader = $this->images.'small-loader.gif';
        $update = $this->url.'functions/updateorder.php?context_key='.$context_key;


        $partner_id = Configuration::get('FNAC_PARTNER_ID');
        $shop_id = Configuration::get('FNAC_SHOP_ID');
        $api_key = Configuration::get('FNAC_API_KEY');
        $api_url = Configuration::get('FNAC_API_URL');

        $debug = Configuration::get('FNAC_DEBUG');

        $fnac = new FnacAPI($partner_id, $shop_id, $api_key, $api_url, $debug);

        $fnacOrder = new FNAC_Order($params['id_order']);

        if (Tools::strtolower($fnacOrder->module) != $this->name) {
            return (false);
        }

        $statuses = $fnac->getStatusesNames();

        // Onlny Allowed statuses
        unset($statuses[FnacAPI::Created]);
        unset($statuses[FnacAPI::ToShip]);
        unset($statuses[FnacAPI::NotReceived]);
        unset($statuses[FnacAPI::Accepted]);
        unset($statuses[FnacAPI::Received]);
        unset($statuses[FnacAPI::Refunded]);
        unset($statuses[FnacAPI::Cancelled]);
        unset($statuses[FnacAPI::Error]);
        unset($statuses[FnacAPI::Update]);

        $selectStatus = '
           <select name="statuses" id="statuses" style="width:250px;">
            <option value="" disabled="disabled">'.$this->l('Update Status').'</option>';

        foreach ($statuses as $key => $val) {
            if ($key == FnacAPI::Shipped) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }

            $selectStatus .= '<option value="'.$key.'"'.$selected.'>'.$val.'</option>';
        }

        $selectStatus .= '</select>';

        $this->_html .= '<br />';

        $trackingFields = '
            <div id="trackingFields" style="display:none;">
            <table width="100%">
            <tr>
                <td>'.$this->l('Tracking Company').'</td>
                <td>'.$this->l('Tracking Number').'</td>
            </tr>
            <tr>
                <td><input type="text" id="tracking_company" name="tracking_company" /></td>
                <td><input type="text" id="tracking_number" name="tracking_number" /></td>
            </tr>
            </table>
            </div>';

        $currentStatus = isset($fnacOrder->marketPlaceOrderStatus) ? $fnacOrder->marketPlaceOrderStatus : FnacAPI::Unknown;

        $style_legend = '';
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $style = 'style="width:400px"';
        } else {
            $style = '';
            $style_legend = 'style="font-family: Ubuntu Condensed, Helvetica, Arial, sans-serif;
				font-weight: 400;
				color: inherit;
				background-color: transparent;
				position: relative;
				top: 16px;
				display: block;
				padding: 0 0 0 5px;
				border: 0;
				border-bottom: 1px solid #eeeeee;
				border-top: 1px solid #e5e5e5;
				font-size: 1.2em;
				line-height: 2.2em;
				text-transform: uppercase;
				margin: -20px -16px 15px -16px;"';
        }

        $this->_html .= sprintf('
            <form name="fnacUpdateOrder" id="fnacUpdateOrder" method="post" />
            <input type="hidden" id="update_url" value="'.$update.'" />
            <input type="hidden" name="platform" value="'.Language::getIsoById($fnacOrder->id_lang).'" />
            <input type="hidden" id="img_loader" value="'.$loader.'" />
            <input type="hidden" name="order_id" value="'.$params['id_order'].'" />
            <input type="hidden" id="fnac_order_state" name="fnac_order_state" value="'.$fnacOrder->marketPlaceOrderStatus.'" />
            <input type="hidden" name="fnac_order_id" value="'.$fnacOrder->marketPlaceOrderId.'" />

            <fieldset class="panel" '.$style.'>
            	<legend '.$style_legend.'><img src="%s" alt="%s" style="width: 24px;"> %s</legend>

            <p>%s : <b>%s</b></p>
            <p>%s : <b><span id="pending">%s</b></p>
            <p><input type="button" class="button btn btn-default" id="update_status" value="%s" /></p>
            <p>%s&nbsp;&nbsp;<span id="amazon_update_st"></span>%s<br /><input type="button" class="button btn btn-default" id="amazon_set_status" value="%s" /></p>
            ', $img, $this->name, $this->l('FNAC MarketPlace'), $this->l('FNAC Order ID'), $fnacOrder->marketPlaceOrderId, $this->l('State'), $statusName[$currentStatus], $this->l('Retrieve and Update Current Order State'), $selectStatus, $trackingFields, $this->l('Update')
        );

        $this->_html .= '</fieldset></form>';


        // jQuery Functions
        $js = Tools::file_get_contents($jsFile);

        $this->_html .= '<script type="text/javascript">'.$js.'</script>';

        return ($this->_html);
    }

    /* Could be used by hookUpdateOrderStatus */
    private function _file_get_contents_curl($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');

        $data = curl_exec($ch);

        curl_close($ch);

        return $data;
    }
    
    public function hookDisplayAdminProductsExtra($param) {
        $id_product = (int)Tools::getValue(
            'id_product',
            is_array($param) && array_key_exists('id_product', $param) ? $param['id_product'] : 0
        );

        $module_url = Tools::getHttpHost(true).__PS_BASE_URI__.'modules/'.$this->name.'/';
        $functions_base_url = $module_url.'functions/';
        $token = self::$instant_token;

        $this->context->smarty->assign(array(
            'id_product' => $id_product,
            'img_url' => $this->_path.'views/img/',
            'update_product_hscode_url' => $functions_base_url.'update_product_hscode.php?token='.$token,
            'propagate_product_hscode_url' => $functions_base_url.'propagate_product_hscode.php?token='.$token,
            'id_lang' => $this->context->language->id
        ));

        require_once(dirname(__FILE__).'/functions/product_ext.php');
        $productExtManager = new Product_FnacExt_Manager();

        return $productExtManager->DoIt($id_product);
    }

    public function hookPostUpdateOrderStatus($params)
    {
        return ($this->hookActionOrderStatusPostUpdate($params));
    }

    public function hookActionOrderStatusUpdate($params)
    {
        return $this->hookActionOrderStatusPostUpdate($params);
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        require_once(dirname(__FILE__).'/classes/fnac.webservice.class.php');
        require_once(dirname(__FILE__).'/classes/fnac.tools.class.php');
        require_once(dirname(__FILE__).'/classes/fnac.order.class.php');

        // Recuperation et test de commande
        $id_order = (int)$params['id_order'];
        if ($id_order <= 0) {
            return false;
        }

        $fnacOrder = new FNAC_Order($id_order);
        if (!$fnacOrder) {
            return (false);
        }

        // Not a FNAC order
        if (Tools::strtolower($fnacOrder->module) != Tools::strtolower($this->name)) {
            return;
        }

        // Recuperation des croisements de statuts
        $mp_statuses = Configuration::get('FNAC_STATUSES_MAP');

        if (isset($mp_statuses) && !empty($mp_statuses)) {
            $mp_statuses = unserialize(Tools::stripslashes($mp_statuses));
        } else {
            return false;
        }

        if (!isset($mp_statuses[FnacAPI::Shipped])) {
            return false;
        }
        // Statut prestashop correspondant au statut FNAC "expedie"
        $sentstate = $mp_statuses[FnacAPI::Shipped];

        $fnac_order_id = $fnacOrder->marketPlaceOrderId;

        // Not a marketplace order
        if ($fnac_order_id == '') {
            return false;
        }

        $tracking_number = $fnacOrder->shipping_number;
        if (!$tracking_number && version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_order_carrier = (int)Db::getInstance()->getValue('SELECT `id_order_carrier` FROM `'._DB_PREFIX_.'order_carrier` WHERE `id_order` = '.(int)$id_order);

            $order_carrier = new OrderCarrier($id_order_carrier);
            if (Validate::isLoadedObject($order_carrier) && $order_carrier->tracking_number) {
                $tracking_number = $order_carrier->tracking_number;
            }
        }

        $carrier = new Carrier($fnacOrder->id_carrier);
        $carriers_mapping = unserialize(Configuration::get('FNAC_CARRIERS_MAPPING'));
        $tracking_company = is_array($carriers_mapping) && array_key_exists($carrier->id, $carriers_mapping) ?
            $carriers_mapping[$carrier->id] : $carrier->name;

        $ret = true;

        // Matching Order Status
        // La commande est expedier, on peut appeler l'URL de mise jour
        if ($params['newOrderStatus']->id == $sentstate) {
            $flag = Tools::strtoupper(Language::getIsoById($fnacOrder->id_lang));
            if ($flag == 'FR') {
                $flag = '';
            } elseif ($flag == 'NL') {
                $flag = 'BE_';
            } else {
                $flag .= '_';
            }

            $partner_id = Configuration::get('FNAC_'.$flag.'PARTNER_ID');
            $shop_id = Configuration::get('FNAC_'.$flag.'SHOP_ID');
            $api_key = Configuration::get('FNAC_'.$flag.'API_KEY');
            $api_url = rtrim(Configuration::get('FNAC_'.$flag.'API_URL'), '/').'/orders_update';

            $debug = Configuration::get('FNAC_DEBUG');

            // Get Token
            $login_request_xml =
'<?xml version="1.0" encoding="utf-8"?>
<auth xmlns="http://www.fnac.com/schemas/mp-dialog.xsd">
	<partner_id>'.$partner_id.'</partner_id>
	<shop_id>'.$shop_id.'</shop_id>
	<key>'.$api_key.'</key>
</auth>';

            $ret = $this->doPostRequest(rtrim(Configuration::get('FNAC_'.$flag.'API_URL'), '/').'/auth', $login_request_xml);

            $saved_ret = $ret;

            $ret = @simplexml_load_string($ret);
            $ret = Tools::jsonDecode(Tools::jsonEncode($ret), 1);

            if (!is_array($ret) || !array_key_exists('token', $ret)) {
                echo('FNAC : Unable to get the Access Token from FNAC, the order will not be updated.');
                echo($login_request_xml);
                echo($saved_ret);
                var_dump($flag, $partner_id, $shop_id, $api_key, $api_url);
                return false;
            }

            $api_key = $ret['token'];

            // Pass order as accepted in case it is not done yet
            $offers_update_request_xml =
'<?xml version="1.0" encoding="utf-8"?>
<orders_update xmlns="http://www.fnac.com/schemas/mp-dialog.xsd" shop_id="'.$shop_id.'" partner_id="'.$partner_id.'" token="'.$api_key.'">
    <order order_id="'.$fnac_order_id.'" action="accept_all_orders">
        <order_detail>
            <action><![CDATA[Accepted]]></action>
        </order_detail>
    </order>
</orders_update>';

            // Already done during order import.
            $this->doPostRequest($api_url, $offers_update_request_xml);

            // Needed to sleep it a little else there will be an error
            // I guess it is some cache on FNAC side
            sleep(3);

            // Update order as SHIPPED
            $offers_update_request_xml =
'<?xml version="1.0" encoding="utf-8"?>
<orders_update xmlns="http://www.fnac.com/schemas/mp-dialog.xsd" shop_id="'.$shop_id.'" partner_id="'.$partner_id.'" token="'.$api_key.'">
  <order order_id="'.$fnac_order_id.'" action="confirm_all_to_send">
    <order_detail>
      <action><![CDATA[Shipped]]></action>
    </order_detail>
  </order>
</orders_update>';

            $ret = $this->doPostRequest($api_url, $offers_update_request_xml);
            if ($ret === false) {
                echo('FLAG : '.$flag);
                echo($offers_update_request_xml);
                echo nl2br(print_r($ret, true));
                ddd(error_get_last());
            }

            if ($debug) {
                echo('FLAG : '.$flag);
                echo($offers_update_request_xml);
                echo nl2br(print_r($ret, true));
                die;
            }

            // Needed to sleep it a little else there will be an error
            // I guess it is some cache on FNAC side
            sleep(3);

            // Update tracking number
            $offers_update_request_xml =
'<?xml version="1.0" encoding="utf-8"?>
<orders_update xmlns="http://www.fnac.com/schemas/mp-dialog.xsd" shop_id="'.$shop_id.'" partner_id="'.$partner_id.'" token="'.$api_key.'">
  <order order_id="'.$fnac_order_id.'" action="update">
    <order_detail>
      <order_detail_id>1</order_detail_id>
      <action><![CDATA[Updated]]></action>
      <tracking_number><![CDATA['.$tracking_number.']]></tracking_number>
      <tracking_company><![CDATA['.$tracking_company.']]></tracking_company>
    </order_detail>
  </order>
</orders_update>';

            $ret = $this->doPostRequest($api_url, $offers_update_request_xml);
            if ($ret === false) {
                echo($offers_update_request_xml);
                echo nl2br(print_r($ret, true));
                ddd(error_get_last());
            }

            if ($debug) {
                echo($offers_update_request_xml);
                echo nl2br(print_r($ret, true));
                die;
            }
        }

        return ($ret);
    }

    private function doPostRequest($url, $data)
    {
        $ch = curl_init();

        // Depending on your system, you may add other options or modify the following ones.
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /* MarketPlace Optionnal Table */
    private function _addMarketPlaceTables()
    {
        $pass = true;

        // Check if exists
        $tables = array();
        $query = Db::getInstance()->ExecuteS('show tables');
        foreach ($query as $rows) {
            foreach ($rows as $table) {
                $tables[$table] = 1;
            }
        }

        if (isset($tables[_DB_PREFIX_.'fnac_product_option'])) {
            Configuration::updateValue('KEEP_TABLE_PRODUCT_OPTION', true);
            $pass = true;
            $sqls = array();

            $fields = array();

            // Fnac Update - Add new fields
            $query = Db::getInstance()->ExecuteS('show columns from `'._DB_PREFIX_.'fnac_product_option`');
            foreach ($query as $row) {
                $fields[$row['Field']] = 1;
            }

            if (!isset($fields['disable'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.'fnac_product_option` ADD  `disable` TINYINT NULL DEFAULT NULL AFTER `force`';
            }
            if (!isset($fields['price'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.'fnac_product_option` ADD  `price` FLOAT NULL DEFAULT NULL AFTER `disable`';
            }
            if (!isset($fields['text'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.'fnac_product_option` ADD  `text` VARCHAR(128) NULL DEFAULT NULL AFTER `price`';
            }
            if (!isset($fields['text_es'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.'fnac_product_option` ADD  `text_es` VARCHAR(128) NULL DEFAULT NULL AFTER `text`';
            }
            if (!isset($fields['text_pt'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.'fnac_product_option` ADD  `text_pt` VARCHAR(128) NULL DEFAULT NULL AFTER `text_es`';
            }
            if (!isset($fields['time_to_ship'])) {
                $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.'fnac_product_option` ADD  `time_to_ship` INT NULL DEFAULT NULL AFTER `text_pt`';
            }

            foreach ($sqls as $sql) {
                $pass = Db::getInstance()->Execute($sql) && $pass;
            }

            return ($pass);
        } else {
            $sql = 'CREATE TABLE  `'._DB_PREFIX_.'fnac_product_option` (
                  `id_product` INT NOT NULL ,
                  `id_lang` INT NOT NULL ,
                  `force` TINYINT NOT NULL DEFAULT  "0",
                  `disable` TINYINT NULL DEFAULT NULL,
                  `price` FLOAT NULL DEFAULT NULL,
                  `text` VARCHAR(128) NULL DEFAULT NULL,
                  `text_es` VARCHAR(128) NULL DEFAULT NULL,
                  `text_pt` VARCHAR(128) NULL DEFAULT NULL,
                   UNIQUE KEY `id_product` (`id_product`,`id_lang`)
                  ) ;';

            if (!Db::getInstance()->Execute($sql)) {
                $pass = false;
            }
        }

        return ($pass);
    }

    private function _removeMarketPlaceTables()
    {
        $pass = true;

        // Check if exists
        $tables = array();
        $query = Db::getInstance()->ExecuteS('show tables');
        foreach ($query as $rows) {
            foreach ($rows as $table) {
                $tables[$table] = 1;
            }
        }

        if (isset($tables[_DB_PREFIX_.'fnac_product_option'])) {
            if (Configuration::get('KEEP_TABLE_PRODUCT_OPTION') === true) {
                return (true);
            }

            $sql = 'DROP TABLE  `'._DB_PREFIX_.'fnac_product_option` ; ';

            if (!Db::getInstance()->Execute($sql)) {
                $pass = false;
            }
        }

        return ($pass);
    }

    /* Add MarketPlace OrderID in the order table */
    private function _addMarketPlaceField()
    {
        $pass = true;
        $fields = array();
        $sqls = array();
        $query = Db::getInstance()->ExecuteS('show columns from `'._DB_PREFIX_.'orders`');
        foreach ($query as $row) {
            $fields[$row['Field']] = 1;
        }

        if (!isset($fields['mp_order_id'])) {
            $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.'orders`
                    ADD  `mp_order_id` VARCHAR( 32 ) NULL DEFAULT NULL COMMENT "For MarketPlaces",
                    ADD INDEX (  `mp_order_id` ) ;';
        }

        if (!isset($fields['mp_status'])) {
            $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.'orders`
                    ADD  `mp_status` INT NULL DEFAULT NULL ;';
        }

        if ($sqls) {
            foreach ($sqls as $sql) {
                $pass = Db::getInstance()->Execute($sql) && $pass;
            }
        }

        return ($pass);
    }

    private function _removeMarketPlaceField()
    {
        $pass = true;

        //* On enleve cette fonction pour eviter les deconvenues ..... */

        return ($pass);

        $sqls = array();
        $fields = array();
        $query = Db::getInstance()->ExecuteS('show columns from `'._DB_PREFIX_.'orders`');
        foreach ($query as $row) {
            $fields[$row['Field']] = 1;
        }

        if (isset($fields['mp_order_id'])) {
            $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.'orders` DROP `mp_order_id` ;';
        }
        if (isset($fields['mp_status'])) {
            $sqls[] = 'ALTER TABLE  `'._DB_PREFIX_.'orders` DROP `mp_status` ;';
        }

        foreach ($sqls as $sql) {
            $pass = Db::getInstance()->Execute($sql) && $pass;
        }

        return ($pass);
    }

    /* Add a customer / it will hold the market place orders */
    private function _createCustomer()
    {
        $pass = true;

        // Fakemail
        $var = explode('@', Configuration::get('PS_SHOP_EMAIL'));
        $email = 'no-reply-'.rand(10000, 100000).'@'.$var[1];

        $customer = new Customer();
        $customer->firstname = 'FNAC';
        $customer->lastname = 'FNAC Market Place';
        $customer->email = $email;
        $customer->newsletter = false;
        $customer->optin = false;
        $customer->passwd = md5(rand(500, 9999999999));
        $customer->active = true;

        $pass = $customer->add();

        Configuration::updateValue('FNAC_CUSTOMER_ID', $customer->id);

        return ($pass);
    }

    private function _deleteCustomer()
    {
        $customer = new Customer();
        $customer->id = Configuration::get('FNAC_CUSTOMER_ID');

        return ($customer->delete());
    }

    private function installModuleTab($tabClass, $tabName, $tabParent)
    {
        $pass = true;
        $tabNameLang = array();

        @copy(_PS_MODULE_DIR_.$this->name.'/logo.gif', _PS_IMG_DIR_.'t/'.$tabClass.'.gif');

        foreach (Language::getLanguages() as $language) {
            $tabNameLang[$language['id_lang']] = $tabName;
        }

        $tab = new Tab();
        $tab->name = $tabNameLang;
        $tab->class_name = $tabClass;
        $tab->module = $this->name;
        $tab->id_parent = (int)Tab::getIdFromClassName($tabParent);

        // For Prestashop 1.2
        if (version_compare(_PS_VERSION_, '1.3', '<')) {
            $pass = $tab->add();
        } else {
            $pass = $tab->save();
        }

        return ($pass);
    }

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
}
