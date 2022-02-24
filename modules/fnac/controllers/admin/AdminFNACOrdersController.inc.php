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

require_once(dirname(__FILE__).'/../../classes/fnac.webservice.class.php');
require_once(dirname(__FILE__).'/../../classes/fnac.product.class.php');
require_once(dirname(__FILE__).'/../../classes/fnac.tools.class.php');
require_once(dirname(__FILE__).'/../../classes/fnac.context.class.php');

class AdminFNACOrdersControllerExt extends FNAC
{
    public $id_lang;
    private $module = 'fnac';

    protected $url;
    protected $path;
    protected $images;
    protected $debug;

    public function __construct()
    {
        $this->debug = (bool)Configuration::get('FNAC_DEBUG');

        parent::__construct();
    }

    public function content($smarty)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $smarty->assign('context_key', FNAC_Context::getKey($this->context->shop));
        } else {
            $smarty->assign('context_key', '0');
        }

        $smarty->assign('path', $this->url);
        $smarty->assign('images', $this->images);
        $smarty->assign('image_loading', $this->url.'/img/loading.gif');
        $display = ' style="display:none" ';

        if ($this->debug) {
            $smarty->assign('debug', '1');
            $display = ' style="display:none;" ';
        } else {
            $display = ' style="display:none;visibility:hidden;" ';
        }
        $smarty->assign('console_display', $display);
        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'import';
        $smarty->assign('selected_tab', $selected_tab);

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

        $smarty->assign('fnac_accept', $this->_accept());
        $smarty->assign('fnac_import', $this->_import());

        if ($this->debug) {
            $smarty->assign('fnac_debug', $this->_debug());
        }
        
        $this->context->controller->addJS(array(
            $this->_path.'views/js/back.js',
            '//cdnjs.cloudflare.com/ajax/libs/riot/3.11.1/riot+compiler.min.js'
        ));
        
        return $this->display($this->path, 'views/templates/admin/orders/AdminOrdersFNAC.tpl').
            $this->display($this->path, 'views/templates/admin/prestui/ps-tags.tpl');
    }

    private function _accept()
    {
        $view_params = array();

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'accept';
        $view_params['selected_tab'] = ($selected_tab == 'accept' ? 'selected' : '');

        return $view_params;
    }


    private function _import()
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            require_once(dirname(__FILE__).'/../backward_compatibility/backward.php');
        }

        $name = 'fnac';
        $module = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$name.'/';
        $images = $module.'views/img/';
        $loader = $images.'loading.gif';
        $lookup = $module.'functions/orders.php';
        $import = $module.'functions/import.php';
        $request_uri = $_SERVER['REQUEST_URI'];
        $partner_id = Configuration::get('FNAC_PARTNER_ID');
        $shop_id = Configuration::get('FNAC_SHOP_ID');
        $api_key = Configuration::get('FNAC_API_KEY');
        $api_url = Configuration::get('FNAC_API_URL');
        $fnac = new FnacAPI($partner_id, $shop_id, $api_key, $api_url, true);

        $currentDate = date('Y-m-d');
        $date = Configuration::get('FNAC_ORDERS_REQUEST');

        if ($date) {
            $initialDate = $date;
        } else {
            $initialDate = date('Y-m-d', time() - 86400);
        }

        $tokenOrders = Tools::getAdminToken('AdminOrders'.(int)Tab::getIdFromClassName('AdminOrders').(int)Context::getContext()->cookie->id_employee);

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

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'import';

        $view_params = array();
        $view_params['selected_tab'] = ($selected_tab == 'import' ? 'selected' : '');
        $view_params['request_uri'] = $_SERVER['REQUEST_URI'];
        $view_params['img'] = $images;
        $view_params['request_uri'] = $request_uri;
        $view_params['lookup'] = $lookup;
        $view_params['import'] = $import;
        $view_params['loader'] = $loader;
        $view_params['currentDate'] = $currentDate;
        $view_params['tokenOrders'] = $tokenOrders;
        $view_params['initialDate'] = $initialDate;

        $view_params['statuses'] = array();

        foreach ($statusName as $key => $val) {
            if ($key == FnacAPI::Update) {
                continue;
            }

            if ($key == 1) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            $key = isset($fnac->statuses[$key]) ? $fnac->statuses[$key] : 0;

            $option = array();
            $option['selected'] = $selected;
            $option['value'] = $key;
            $option['desc'] = $val;
            $view_params['statuses'][] = $option;
        }

        return $view_params;
    }

    private function _debug()
    {
        $view_params = array();

        $selected_tab = ($tab = Tools::getValue('selected_tab')) ? $tab : 'update';
        $view_params['selected_tab'] = ($selected_tab == 'debug' ? 'selected' : '');

        return $view_params;
    }
}
