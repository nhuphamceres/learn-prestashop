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
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * todo: Migrate all admin configurations to this class
 * Class AmazonAdminConfigure
 */
class AmazonAdminConfigure
{
    const TEMPLATE_HEADER = 1;
    const TEMPLATE_FOOTER = 2;
    const TEMPLATE_LICENSE = 3;
    const TEMPLATE_VALIDATE = 4;
    const TEMPLATE_CONFIGURE = 5;
    const TEMPLATE_TABS = 10;
    const TEMPLATE_TAB_SETTINGS = 19;
    const TEMPLATE_TAB_TOOLS = 20;
    const TEMPLATE_TAB_CRON = 21;
    const TEMPLATE_TAB_FILTERS = 22;
    const TEMPLATE_TAB_PARAMETERS = 23;
    const TEMPLATE_TAB_CATEGORIES = 24;
    const TEMPLATE_TAB_PROFILES = 25;
    const TEMPLATE_TAB_MAPPING = 26;
    const TEMPLATE_TAB_INFO = 27;
    const TEMPLATE_TAB_SHIPPING = 28;
    const TEMPLATE_TAB_MESSAGING = 29;
    const TEMPLATE_TAB_FBA = 30;
    const TEMPLATE_TAB_FEATURES = 31;
    const TEMPLATE_TAB_REPRICING = 32;
    const TEMPLATE_TAB_AMAZON = 43;
    const TEMPLATE_TAB_GLOSSARY = 44;

    private static $templates = array(
        self::TEMPLATE_TABS => 'views/templates/admin/configure/tabs.tpl',
        self::TEMPLATE_HEADER => 'views/templates/admin/configure/header.tpl',
        self::TEMPLATE_FOOTER => 'views/templates/admin/configure/footer.tpl',
        self::TEMPLATE_VALIDATE => 'views/templates/admin/configure/validate.tpl',
        self::TEMPLATE_CONFIGURE => 'views/templates/admin/configure/configure.tpl',
        self::TEMPLATE_LICENSE => 'views/templates/admin/configure/license.tpl',
        self::TEMPLATE_TAB_SETTINGS => 'views/templates/admin/configure/settings.tab.tpl',
        self::TEMPLATE_TAB_TOOLS => 'views/templates/admin/configure/tools.tab.tpl',
        self::TEMPLATE_TAB_CRON => 'views/templates/admin/configure/cron.tab.tpl',
        self::TEMPLATE_TAB_FILTERS => 'views/templates/admin/configure/filters.tab.tpl',
        self::TEMPLATE_TAB_PARAMETERS => 'views/templates/admin/configure/parameters.tab.tpl',
        self::TEMPLATE_TAB_PROFILES => 'views/templates/admin/configure/profiles.tab.tpl',
        self::TEMPLATE_TAB_CATEGORIES => 'views/templates/admin/configure/categories.tab.tpl',
        self::TEMPLATE_TAB_MAPPING => 'views/templates/admin/configure/mapping.tab.tpl',
        self::TEMPLATE_TAB_INFO => 'views/templates/admin/configure/informations.tab.tpl',
        self::TEMPLATE_TAB_FEATURES => 'views/templates/admin/configure/features.tab.tpl',
        self::TEMPLATE_TAB_SHIPPING => 'views/templates/admin/configure/shipping.tab.tpl',
        self::TEMPLATE_TAB_MESSAGING => 'views/templates/admin/configure/messaging.tab.tpl',
        self::TEMPLATE_TAB_FBA => 'views/templates/admin/configure/fba.tab.tpl',
        self::TEMPLATE_TAB_REPRICING => 'views/templates/admin/configure/repricing.tab.tpl',
        self::TEMPLATE_TAB_AMAZON => 'views/templates/admin/configure/amazon.tab.tpl',
        self::TEMPLATE_TAB_GLOSSARY => 'views/templates/admin/configure/glossary.tpl',
    );

    // Amazon config
    const PRIME_ADDRESS = 'PRIME';

    /** @var Amazon */
    public $module;

    /** @var Context */
    public $context;

    /** @var bool */
    public $enable_experimental_features = false;

    protected $config = array();

    /**
     * All tabs use same footer
     * @var string html
     */
    protected $tab_footer;

    public function __construct($module, $context, $enable_experimental_features)
    {
        $this->module = $module;
        $this->context = $context;
        $this->enable_experimental_features = $enable_experimental_features;
    }

    public function loadSettings()
    {
        if (!count($this->config)) {
            $this->config = array(
                'taxes_comply_eu_vat_rules' => (bool)AmazonConfiguration::get(AmazonConstant::CONFIG_OI_TAXES_COMPLY_EU_VAT_RULES),
                'taxes_on_business_orders' => (bool)AmazonConfiguration::get(AmazonConstant::CONFIG_OI_TAXES_ON_BUSINESS_ORDERS),
                'force_vat_recalculation' => (bool)AmazonConfiguration::get(AmazonConstant::CONFIG_OI_TAXES_FORCE_RECALCULATION),
                'vidr' => (bool)AmazonConfiguration::get(AmazonConstant::CONFIG_VCS_ENABLED),
                'vidr_send_invoice' => (bool)AmazonConfiguration::get(AmazonConstant::CONFIG_VCS_SEND_INVOICE),
                'vidr_update_customer_vat_number' => (bool)AmazonConfiguration::get(AmazonConstant::CONFIG_VCS_UPDATE_CUSTOMER_VAT_NUMBER),
                'vidr_update_billing_address' => (bool)AmazonConfiguration::get(AmazonConstant::CONFIG_VCS_UPDATE_BILLING_ADDRESS),
            );
        }

        return $this->config;
    }

    public function postProcess()
    {
        // Dedicated configuration
        $booleanSettings = array(
            array('form_name' => 'parameter_vidr', 'db_key' => AmazonConstant::CONFIG_VCS_ENABLED),
            array('form_name' => 'parameter_vidr_send_invoice', 'db_key' => AmazonConstant::CONFIG_VCS_SEND_INVOICE),
            array('form_name' => 'parameter_vidr_update_customer_vat_number', 'db_key' => AmazonConstant::CONFIG_VCS_UPDATE_CUSTOMER_VAT_NUMBER),
            array('form_name' => 'parameter_vidr_update_billing_address', 'db_key' => AmazonConstant::CONFIG_VCS_UPDATE_BILLING_ADDRESS),
            array('form_name' => 'taxes_comply_eu_vat_rules', 'db_key' => AmazonConstant::CONFIG_OI_TAXES_COMPLY_EU_VAT_RULES),
            array('form_name' => 'taxes_on_business_orders', 'db_key' => AmazonConstant::CONFIG_OI_TAXES_ON_BUSINESS_ORDERS),
            array('form_name' => 'force_vat_recalculation', 'db_key' => AmazonConstant::CONFIG_OI_TAXES_FORCE_RECALCULATION),
            array('form_name' => 'product_update_condition_ignore', 'db_key' => AmazonConstant::CONFIG_PRODUCT_UPDATE_CONDITION_IGNORE),
        );
        foreach ($booleanSettings as $booleanSetting) {
            $value = (bool)Tools::getValue($booleanSetting['form_name']);
            AmazonConfiguration::updateValue($booleanSetting['db_key'], $value);
        }

        // todo: Continue to migrate carrier mapping outgoing. Currently, always save new config, prepare to transform
        // todo: Possible to validate input mapping
        $this->module->migrationManager->migrateDuringSaveConfiguration();
    }

    public function allTabControllerParams()
    {
        $module = $this->module;
        $selected_tab = $this->selectedTab();
        $country_iso_code = Configuration::get('PS_LOCALE_COUNTRY');

        $platforms = array();
        foreach (AmazonTools::languages() as $language) {
            $index = $language['iso_code'];
            $platforms[$index] = array(
                'iso_code' => $index,
                'name_short' => preg_replace('/ .*/', '', $language['name']),
                'name_long' => $language['name'],
                'selected' => ($selected_tab === $language['iso_code'] ? 'selected' : ''),
                'geo_flag' => $module->geoFlag($language['id_lang']),
                'area' => $language['area'],
                'display' => Tools::strtolower($country_iso_code) === Tools::strtolower($language['country_iso_code']) ? true : false
            );
        }

        $tabsParams = array(
            'images_url' => $module->images,
            'amazon' => 'Amazon',
            'informations' => $module->l('Informations'),
            'features' => $module->l('Features'),
            'platforms' => $platforms,
            'parameters' => $module->l('Parameters'),
            'categories' => $module->l('Categories'),
            'mapping' => $module->l('Mappings'),
            'profiles' => $module->l('Profiles'),
            'shipping' => $module->l('Shipping'),
            'filters' => $module->l('Filters'),
            'messaging' => $module->l('Messaging'),
            'fba' => $module->l('Amazon FBA'),
            'repricing' => $module->l('Repricing'),
            'prime' => $module->l('Prime'),
            'tools' => $module->l('Tools'),
            'cron' => $module->l('Scheduled Tasks'),
            'debug' => $module->l('Debug Mode'),
        );
        // 'amazon_selected', 'informations_selected', 'features_selected', 'parameters_selected', 'categories_selected',
        // 'mapping_selected', 'profiles_selected', 'shipping_selected', 'filters_selected', 'messaging_selected',
        // 'fba_selected', 'repricing_selected', 'prime_selected', 'tools_selected', 'cron_selected', 'debug_selected'
        $tabs = array(
            'amazon',
            'informations',
            'features',
            'parameters',
            'categories',
            'mapping',
            'profiles',
            'shipping',
            'filters',
            'messaging',
            'fba',
            'repricing',
            'prime',
            'tools',
            'cron',
            'debug'
        );
        foreach ($tabs as $tab_name) {
            $tabsParams[$tab_name . '_selected'] = ($selected_tab === $tab_name ? 'selected' : '');
        }

        return $tabsParams;
    }

    /****************************************************** Features ***************************************************
     * All view params of feature tab
     * @param $view_params
     * @return mixed
     */
    public function tabFeatures(&$view_params)
    {
        $view_params['features']['selected_tab'] = $this->selectedTab() === 'features';
        $view_params['features']['images'] = $this->module->images;
        $view_params['features']['experimental'] = $this->enable_experimental_features;
        $view_params['features']['documentation'] = AmazonSupport::gethreflink();
        $view_params['features']['tutorial'] = AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_FEATURES);
        $view_params['features']['images_url'] = $this->module->images;
        $view_params['features']['version'] = $this->module->version;
        $view_params['features']['ps_version'] = _PS_VERSION_;

        $view_params['features']['links'] = array(
            'synchronization' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_SYNCHRONIZATION),
            'creation' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_CREATION),
            'second_hand' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_SECOND_HAND),
            'prices_rules' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_PRICES_RULES),
            'europe' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_EUROPE),
            'orders' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_ORDERS_IMPORT),
            'gcid' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_GCID),
            'filters' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_FILTERS),
            'import_products' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_IMPORT_PRODUCTS),
            'offers' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_OFFERS),
            'fba' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_FBA),
            'repricing' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_REPRICING),
            'remote_cart' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_REMOTE_CART),
            'shipping_template' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_SHIPPING_TEMPLATE),
            'messaging' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_MESSAGING),
            'cancel_orders' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_CANCEL_ORDERS),
            'expert_mode' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_EXPERT_MODE),
            'debug_express' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_DEBUG_EXPRESS),
            'business' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_BUSINESS),
            'orders_reports' => AmazonSupport::gethreflink(AmazonSupport::TUTORIAL_BUSINESS),
        );

        $moduleConfig = $this->module->getConfig();
        $view_params['features']['config'] = $moduleConfig['features'];
        $view_params['features']['config']['noway'] =
            in_array($moduleConfig['features']['module'], array('amazonlite', 'ready'));
        $view_params['features']['validation'] = $this->tabFooter();

        return ($view_params);
    }

    /*************************************************** Parameters ****************************************************
     * @return array
     */
    public function tabParameterSettings()
    {
        $config = $this->module->getConfig();
        $orderStates = $config['order_state'];
        $osIncomingCombination = AmazonConfiguration::get(AmazonConstant::CONFIG_ORDER_STATES_INCOMING_OF_ORDER_ATTRS_COMBINATION);

        $osTypeStd = AmazonConstant::ORDER_INCOMING_TYPE_STANDARD;
        $osTypePrime = AmazonConstant::ORDER_INCOMING_TYPE_PRIME;
        $osTypeBusiness = AmazonConstant::ORDER_INCOMING_TYPE_BUSINESS;
        $osTypePreOrder = AmazonConstant::ORDER_INCOMING_TYPE_PREORDER;
        $osTypeSent = AmazonConstant::ORDER_SENT_TYPE;
        $osTypeCanceled = AmazonConstant::ORDER_CANCELED_TYPE;

        return array(
            // Orders States
            'ps_order_states' => OrderState::getOrderStates($this->module->id_lang),    // todo: Use this in all places
            'order_states' => array(
                'standard' => array(
                    'enabled' => true,
                    'active' => true,
                    'allow_deselect' => false,
                    'name' => "order_state[$osTypeStd]",
                    'title' => $this->module->l('Incoming Orders'),
                    'desc' => $this->module->l('Choose a default incoming order status for Amazon'),
                    'glossary' => 'set_incoming_order_state',
                    'value' => isset($orderStates[$osTypeStd]) ? $orderStates[$osTypeStd] : '',
                ),
                'prime' => array(
                    'enabled' => true,
                    'active' => true,
                    'allow_deselect' => true,
                    'name' => "order_state[$osTypePrime]",
                    'title' => $this->module->l('Prime Orders'),
                    'desc' => $this->module->l('Choose a default incoming Prime order status for Amazon'),
                    'glossary' => 'set_incoming_order_state_prime',
                    'value' => isset($orderStates[$osTypePrime]) ? $orderStates[$osTypePrime] : '',
                ),
                'business' => array(
                    'enabled' => true,
                    'active' => true,
                    'allow_deselect' => true,
                    'name' => "order_state[$osTypeBusiness]",
                    'title' => $this->module->l('Business Orders'),
                    'desc' => $this->module->l('Choose a default incoming Business order status for Amazon'),
                    'glossary' => 'set_incoming_order_state_business',
                    'value' => isset($orderStates[$osTypeBusiness]) ? $orderStates[$osTypeBusiness] : '',
                ),
                'preorder' => array(
                    'enabled' => version_compare(_PS_VERSION_, '1.5', '>='),
                    'active' => $config['preorder'],
                    'allow_deselect' => false,
                    'id' => 'order-state-preorder',
                    'name' => "order_state[$osTypePreOrder]",
                    'title' => $this->module->l('Pre-Orders'),
                    'desc' => $this->module->l('Choose a default PreOrder order status for Amazon'),
                    'glossary' => 'set_incoming_preorder',
                    'value' => isset($orderStates[$osTypePreOrder]) ? $orderStates[$osTypePreOrder] : '',
                ),
                'sent' => array(
                    'enabled' => true,
                    'active' => true,
                    'allow_deselect' => false,
                    'name' => $osTypeSent,
                    'title' => $this->module->l('Orders Sent'),
                    'desc' => $this->module->l('Choose a default sent order status for Amazon'),
                    'glossary' => 'set_order_sent',
                    'value' => isset($config[$osTypeSent]) ? $config[$osTypeSent] : '',
                ),
                'canceled' => array(
                    'enabled' => true,
                    'active' => true,
                    'allow_deselect' => false,
                    'name' => $osTypeCanceled,
                    'title' => $this->module->l('Canceled Orders'),
                    'desc' => $this->module->l('Choose a default canceled order status for Amazon'),
                    'glossary' => 'set_order_cancel',
                    'value' => isset($config[$osTypeCanceled]) ? $config[$osTypeCanceled] : '',
                    'rel' => 'amazon-cancel-orders' // toggle feature
                ),
            ),
            'os_incoming_possible_attrs' => array(
                AmazonConstant::ORDER_INCOMING_TYPE_FBA => $this->module->l('FBA'),
                AmazonConstant::ORDER_INCOMING_TYPE_PRIME => $this->module->l('Prime'),
                AmazonConstant::ORDER_INCOMING_TYPE_BUSINESS => $this->module->l('Business'),
            ),
            'os_incoming_combination' => $osIncomingCombination,

            'get_config_no_cache' => Configuration::get(AmazonConstant::CONFIG_GET_BY_DIRECT_SQL),

            // VCS
            'vidr' => $config['vidr'],
            'vidr_send_invoice' => $config['vidr_send_invoice'],
            'vidr_update_customer_vat_number' => $config['vidr_update_customer_vat_number'],
            'vidr_update_billing_address' => $config['vidr_update_billing_address'],

            'discount' => (bool)$config['specials'],
            'specials_apply_rules' => (bool)$config['specials_apply_rules'],
            'regular_to_rpr' => (bool)$config['regular_to_rpr'],
            'preorder' => (bool)$config['preorder'],
            'taxes' => (int)$config['taxes'],
            'taxes_comply_eu_vat_rules' => $config['taxes_comply_eu_vat_rules'],
            'taxes_on_business_orders' => $config['taxes_on_business_orders'],
            'force_vat_recalculation' => $config['force_vat_recalculation'],
            'product_update_condition_ignore' => AmazonConfiguration::get(AmazonConstant::CONFIG_PRODUCT_UPDATE_CONDITION_IGNORE),
        );
    }

    public function tabPrime()
    {
        $prime_address = AmazonConfiguration::get(self::PRIME_ADDRESS);
        return array(
            'selected_tab' => $this->selectedTab() === 'prime',
            'shop_name' => isset($prime_address['shop_name']) ? $prime_address['shop_name'] : '',
            'address1' => isset($prime_address['address1']) ? $prime_address['address1'] : '',
            'address2' => isset($prime_address['address2']) ? $prime_address['address2'] : '',
            'city' => isset($prime_address['city']) ? $prime_address['city'] : '',
            'postcode' => isset($prime_address['postcode']) ? $prime_address['postcode'] : '',
            'email' => isset($prime_address['email']) ? $prime_address['email'] : '',
            'phone' => isset($prime_address['phone']) ? $prime_address['phone'] : '',
            'country' => isset($prime_address['country']) ? $prime_address['country'] : '',
            'platforms' => $this->module->getPlatforms(),   // country
            'tab_footer' => $this->tabFooter()
        );
    }

    /**
     * Additional view params of cron tab
     * @param $marketPlaceIds
     * @param $activeMarketplaces
     * @param $tokens
     * @return array
     */
    public function tabCronAdditionalParams($marketPlaceIds, $activeMarketplaces, $tokens)
    {
        $moduleConfig = $this->module->getConfig();
        $expertMode = isset($moduleConfig['features'], $moduleConfig['features']['expert_mode']) && (bool)$moduleConfig['features']['expert_mode'];

        $cronViewParams = array(
            'vidr' => array(
                'enable' => isset($moduleConfig['vidr']) && $expertMode && $moduleConfig['vidr'],
                'jobs' => array(),
            ),
        );
        if (!is_array($marketPlaceIds) || !count($marketPlaceIds)) {
            return $cronViewParams;
        }

        $base_url = AmazonTools::getHttpHost(true, true) . __PS_BASE_URI__ .
            basename(_PS_MODULE_DIR_) . '/' . $this->module->name . '/functions/vidr/vidr.php';

        foreach (AmazonTools::languages() as $language) {
            $id_lang = $language['id_lang'];
            $langIso = $language['iso_code'];
            if (!(isset($activeMarketplaces[$id_lang]) && (int)$activeMarketplaces[$id_lang])) {
                continue;
            }
            if (!isset($marketPlaceIds[$id_lang]) || !AmazonTools::isEuropeMarketplaceId($marketPlaceIds[$id_lang])) {
                continue;
            }

            $cronToken = $tokens[$id_lang];
            $flag = $this->module->images . 'geo_flags/' . $this->module->geoFlag($id_lang) . '.gif';

            // VIDR all jobs in 1 script
            $uploadInvoiceQueryParams = array('cron_token' => $cronToken, 'lang' => $langIso);
            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                $context_key = AmazonContext::getKey($this->context->shop);
                $uploadInvoiceQueryParams['context_key'] = $context_key;
            }
            $uploadInvoiceFullUrl = $base_url . '?' . http_build_query($uploadInvoiceQueryParams);
            $cronViewParams['vidr']['jobs'][$id_lang] = array(
                'id_lang' => $id_lang,
                'lang' => $langIso, // Use lang iso instead of our definition code. Eg 'gb' instead of 'uk'
                'flag' => $flag,
                'url' => $uploadInvoiceFullUrl,
                'short_url' => preg_replace('/(?<=^.{64}).{4,}(?=.{24}$)/', '...', $uploadInvoiceFullUrl),
                'title' => $this->module->l('VCS jobs'),
                'frequency' => 30
            );
        }

        return $cronViewParams;
    }

    /**
     * @return array [status, iso_code, error]
     */
    public function checkCountryConsistency()
    {
        $countryIsoCode = Tools::strtoupper(Configuration::get('PS_LOCALE_COUNTRY'));

        if (!empty($countryIsoCode)) {
            if (!Validate::isLanguageIsoCode($countryIsoCode) || !Country::getByIso($countryIsoCode)) {
                return array('status' => false, 'iso_code' => $countryIsoCode, 'error' => 'Localization > Locale Country is not valid');
            }
        } else {
            return array('status' => false, 'iso_code' => $countryIsoCode, 'error' => 'Localization > Locale Country setting is empty !');
        }

        return array('status' => true, 'iso_code' => $countryIsoCode, 'error' => '');
    }

    /**
     * @return array [status, iso_code, error]
     */
    public function checkLanguageConsistency()
    {
        $langIsoCode = Tools::strtolower(Configuration::get('PS_LOCALE_LANGUAGE'));

        if (!empty($langIsoCode)) {
            if (!Validate::isLanguageIsoCode($langIsoCode) || !Language::getIdByIso($langIsoCode)) {
                return array('status' => false, 'iso_code' => $langIsoCode, 'error' => 'Localization > Locale Language setting doesnt match any lang in Prestashop tables');
            } elseif (!AmazonTools::lang2MarketplaceId($langIsoCode)) {
                return array('status' => false, 'iso_code' => $langIsoCode, 'error' => 'Support Info: Localization > Locale Language setting doesnt match any Amazon platform');
            } else {
                return array('status' => true, 'iso_code' => $langIsoCode, 'error' => '');
            }
        } else {
            return array('status' => false, 'iso_code' => $langIsoCode, 'error' => 'Localization > Locale Language setting is empty !');
        }
    }

    protected function selectedTab()
    {
        return (($selected_tab = Tools::getValue('selected_tab')) ? $selected_tab : 'amazon');
    }

    /**
     * Submit button
     * @return string
     * @throws SmartyException
     */
    protected function tabFooter()
    {
        if (!$this->tab_footer) {
            $this->tab_footer = $this->context->smarty->fetch($this->module->path . self::$templates[self::TEMPLATE_VALIDATE]);
        }

        return $this->tab_footer;
    }
}
