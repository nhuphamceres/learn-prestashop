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

if (version_compare(_PS_VERSION_, '1.5', '<') && defined('PS_ADMIN_DIR') && file_exists(PS_ADMIN_DIR.'/../classes/AdminTab.php')) {
    include_once(PS_ADMIN_DIR.'/../classes/AdminTab.php');
}

require_once(dirname(__FILE__).'/amazon.php');
require_once(dirname(__FILE__).'/classes/amazon.tools.class.php');
require_once(dirname(__FILE__).'/classes/amazon.support.class.php');

class OrdersAmazon extends AdminTab
{
    public $name = 'amazon';
    public $id_lang;
    private $_amazon;

    public function __construct()
    {
        $this->context = Context::getContext();
        $this->id_lang = (int)$this->context->language->id;

        $this->url = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/';
        $this->images = $this->url.'views/img/';

        $this->path = str_replace('\\', '/', dirname(__FILE__)).'/';
        $this->_amazon = new Amazon();
        $this->psIs16 = false;

        parent::__construct();
    }

    public function l($string, $class = false, $addslashes = false, $htmlentities = true)
    {
        return (parent::l($string, __CLASS__, $addslashes = false, $htmlentities = true));
    }

    public function display()
    {
        require_once dirname(__FILE__) . '/classes/amazon.cron_failed_order.class.php';

        $this->addCSS($this->url.'views/css/admin_controller/general.css', 'screen');
        $this->addCSS($this->url.'views/css/OrdersAmazon.css', 'screen');
        $this->addCSS($this->url.'views/css/OrdersAmazon.compat.css', 'screen');

        $this->addJS($this->url.'views/js/orders.js');
        $this->addJS($this->url.'views/js/reports.js');

        $smarty = $this->context->smarty->assign(array(
            'path' => $this->url,
            'images' => $this->images,
            'tpl_path' => $this->path,
            'debug' => (bool)Configuration::get('AMAZON_DEBUG_MODE'),
            'selected_tab' => 'import',
            'ps16x' => false,
            'psIs16' => $this->psIs16,
            'support' => null,
            'instant_token' => Configuration::get('AMAZON_INSTANT_TOKEN', null, 0, 0),
            'experimental' => Amazon::ENABLE_EXPERIMENTAL_FEATURES,
            'report_url' => $this->url.'functions/products_report.php',
            'cron_failed_orders' => AmazonCronFailedOrder::getAllCronFailedOrderInstances(),
            'alert_class' => array(
                'danger' => $this->psIs16 ? 'alert alert-danger' : 'error',
                'warning' => $this->psIs16 ? 'alert alert-warning' : 'warn',
                'success' => $this->psIs16 ? 'alert alert-success' : 'conf',
                'info' => $this->psIs16 ? 'alert alert-info' : 'info',
            )
        ));

        echo $this->tabHeader() . $this->languageSelector() . $smarty->fetch($this->path . 'views/templates/admin/AdminOrdersAmazon.tpl');
    }

    public function addCSS($css)
    {
        echo html_entity_decode('&lt;link type="text/css" rel="stylesheet" href="' . $css . '" /&gt;');

        return;
    }

    public function addJS($js)
    {
        echo html_entity_decode('&lt;script type="text/javascript" src="' . $js . '"&gt;&lt;/script&gt;');

        return;
    }

    public function tabHeader()
    {
        $smarty = &$this->context->smarty;
        $cookie = &$this->context->cookie;

        $smarty->assign('images', $this->images);

        $amazonTokens = AmazonConfiguration::get('CRON_TOKEN');

        $tokenOrders = Tools::getAdminToken('AdminOrders'.(int)Tab::getIdFromClassName('AdminOrders').(int)$cookie->id_employee);

        $day = 86400;
        $days = 7;
        $startDate = date('Y-m-d', time() - ($day * $days));
        $currentDate = date('Y-m-d');

        includeDatepicker('nothing');

        $smarty->assign('context_key', null);
        $smarty->assign('tokens', $amazonTokens);
        $smarty->assign('token_order', $tokenOrders);
        $smarty->assign('orders_url', $this->url.'functions/orders.php');
        $smarty->assign('import_url', $this->url.'functions/import.php');
        $smarty->assign('report_url', $this->url.'functions/products_report.php');
        $smarty->assign('img_loader', $this->images.'loading.gif');
        $smarty->assign('img_loader_small', $this->images.'small-loader.gif');
        $smarty->assign('current_date', $currentDate);
        $smarty->assign('start_date', $startDate);

        $smarty->assign('id_lang', $this->id_lang);

        $documentation = AmazonTools::documentation($this->id_lang);
        $faq = AmazonTools::documentation($this->id_lang, 'faq');

        $smarty->assign('documentation', $documentation);
        $smarty->assign('faq', $faq);

        return ($smarty->fetch($this->path.'views/templates/admin/items/orders_header.tpl'));
    }

    public function languageSelector()
    {
        $smarty = &$this->context->smarty;

        $html = null;

        $amazon_features = Amazon::getAmazonFeatures();
        $europe = $amazon_features['amazon_europe'];

        $actives = AmazonConfiguration::get('ACTIVE');
        $regions = AmazonConfiguration::get('REGION');
        $marketPlaceIds = AmazonConfiguration::get('MARKETPLACE_ID');

        $this->addCSS($this->url.'/views/css/country_selector.css', 'screen');

        $marketplacesNotEuro = array();
        $marketplaces = array();

        if ($europe) {
            $marketplacesEuro = array();

            if (is_array($actives)) {
                foreach (AmazonTools::languages() as $language) {
                    $id_lang = $language['id_lang'];

                    if (!isset($actives[$id_lang]) || !$actives[$id_lang]) {
                        continue;
                    }

                    if (!isset($regions[$id_lang]) || empty($regions[$id_lang])) {
                        continue;
                    }

                    if (!isset($marketPlaceIds[$id_lang])) {
                        continue;
                    }

                    if (AmazonTools::isEuropeMarketplaceId($marketPlaceIds[$id_lang]) && AmazonTools::isEuroMarketplaceId($marketPlaceIds[$id_lang])) {
                        // Euro Zone Area
                        //
                        $marketplacesEuro[$id_lang] = array();
                        $marketplacesEuro[$id_lang]['name'] = sprintf('www.amazon.%s', AmazonTools::idToDomain($id_lang));
                        $marketplacesEuro[$id_lang]['region'] = $regions[$id_lang];
                        $marketplacesEuro[$id_lang]['id_lang'] = $id_lang;
                        $marketplacesEuro[$id_lang]['lang'] = $language['iso_code'];
                        $marketplacesEuro[$id_lang]['image'] = $this->images.'geo_flags_web2/flag_'.$regions[$id_lang].'_64px.png';
                    } else {
                        // Outside Euro Zone Area
                        //
                        $marketplacesNotEuro[$id_lang] = array();
                        $marketplacesNotEuro[$id_lang]['name'] = sprintf('www.amazon.%s', AmazonTools::idToDomain($id_lang));
                        $marketplacesNotEuro[$id_lang]['region'] = $regions[$id_lang];
                        $marketplacesNotEuro[$id_lang]['id_lang'] = $id_lang;
                        $marketplacesNotEuro[$id_lang]['lang'] = $language['iso_code'];
                        $marketplacesNotEuro[$id_lang]['image'] = $this->images.'geo_flags_web2/flag_'.$regions[$id_lang].'_64px.png';
                    }
                }
            }

            $europeEuroArea = is_array($marketplacesEuro) && count($marketplacesEuro);
            $europeNotEuroArea = is_array($marketplacesNotEuro) && count($marketplacesNotEuro);
            $showCountrySelector = is_array($marketplacesEuro) && is_array($marketplacesNotEuro)
                && ((count($marketplacesEuro) + count($marketplacesNotEuro)) > 1);

            return $smarty->assign(array(
                'images' => $this->images,
                'europeEuroArea' => $europeEuroArea,
                'europeNotEuroArea' => $europeNotEuroArea,
                'europe_flag' => $this->images.'geo_flags_web2/flag_eu_64px.png',
                'marketplacesEuro' => $marketplacesEuro,
                'marketplacesNotEuro' => $marketplacesNotEuro,
                'show_country_selector' => $showCountrySelector,
                'psIs16' => $this->psIs16,
                'psIsGt15' => $this->_amazon->psIsGt15x,
            ))->fetch($this->path.'views/templates/admin/items/europe_selector.tpl');
        } else {
            if (is_array($actives)) {
                foreach (AmazonTools::languages() as $language) {
                    $id_lang = $language['id_lang'];

                    if (!isset($actives[$id_lang]) || !$actives[$id_lang]) {
                        continue;
                    }

                    if (!isset($regions[$id_lang]) || empty($regions[$id_lang])) {
                        continue;
                    }

                    $marketplaces[$id_lang] = array();
                    $marketplaces[$id_lang]['name'] = sprintf('www.amazon.%s', AmazonTools::idToDomain($id_lang));
                    $marketplaces[$id_lang]['region'] = $regions[$id_lang];
                    $marketplaces[$id_lang]['id_lang'] = $id_lang;
                    $marketplaces[$id_lang]['lang'] = $language['iso_code'];
                    $marketplaces[$id_lang]['image'] = $this->images.'geo_flags_web2/flag_'.$regions[$id_lang].'_64px.png';
                }
            }
            
            return $smarty->assign(array(
                'images' => $this->images,
                'marketplaces' => $marketplaces,
                'show_country_selector' => count($marketplaces) > 1,
                'psIs16' => $this->psIs16,
                'psIsGt15' => $this->_amazon->psIsGt15x,
            ))->fetch($this->path.'views/templates/admin/items/country_selector.tpl');
        }
    }
}
