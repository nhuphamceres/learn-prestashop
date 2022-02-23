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
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice@common-services.com
 */

if (isset($_SERVER['DropBox']) && $_SERVER['DropBox']) {
    require_once(readlink(dirname(__FILE__).'/../../sonice_suivicolis.php'));
    require_once(readlink(dirname(__FILE__).'/../../classes/SoNiceSuiviOrderHelperList.php'));
    require_once(readlink(dirname(__FILE__).'/AdminOrdersSoniceSuiviController.inc.php'));
} else {
    require_once(dirname(__FILE__).'/../../sonice_suivicolis.php');
    require_once(dirname(__FILE__).'/../../classes/SoNiceSuiviOrderHelperList.php');
    require_once(dirname(__FILE__).'/AdminOrdersSoniceSuiviController.inc.php');
}


class AdminOrdersSoniceSuiviController extends ModuleAdminController
{
    public $module = 'sonice_suivicolis';
    public $name = 'sonice_suivicolis';
    public $id_lang;
    public $url;
    public $js;
    public $css;
    public $images;
    public $bootstrap;
    public $ps16x;
    public $ps15x;

    /** @var SoNiceOrderHelperList */
    protected $orders;

    /** @var AdminSoniceOrdersControllerExt */
    protected $soniceOrdersController;

    public function __construct()
    {
        $this->path = _PS_MODULE_DIR_.$this->module.'/';

        $this->className = 'sonice_suivicolis';
        $this->display = 'edit';

        $this->multishop_context = Shop::CONTEXT_SHOP;
        $this->id_lang = (int)Context::getContext()->language->id;

        $this->lang = true;
        $this->deleted = false;
        $this->colorOnBackground = false;

        $this->url = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/';
        $this->js = $this->url.'views/js/';
        $this->css = $this->url.'views/css/';
        $this->images = $this->url.'views/img/';

        if (isset(Context::getContext()->cookie) && isset(Context::getContext()->cookie->shopContext)) {
            $shop_context = explode('-', Context::getContext()->cookie->shopContext);

            if (is_array($shop_context) && count($shop_context) == 2 && $shop_context[0] == 's') {
                Context::getContext()->shop = new Shop((int)$shop_context[1]);
            }
        }

        $this->context = Context::getContext();

        $this->soniceOrdersController = new AdminSoniceOrdersControllerExt($this->id_lang);

        // Orders list
        $this->orders = new SoNiceOrderHelperList($this->id_lang);

        $this->bootstrap = true;

        $this->ps16x = version_compare(_PS_VERSION_, '1.6', '>=');

        parent::__construct();
    }


    public function renderForm()
    {
        $alert_class = array();
        $alert_class['danger'] = $this->ps16x ? 'alert alert-danger' : 'error';
        $alert_class['warning'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        $alert_class['success'] = $this->ps16x ? 'alert alert-success' : 'conf';
        $alert_class['info'] = $this->ps16x ? 'alert alert-info' : 'info';

        $snsc_orders = $this->orders->getOrders();
        foreach ($snsc_orders as $key => $order) {
            $destination = explode(', ', $order['coliposte_location']);
            $snsc_orders[$key]['coliposte_location'] = array_shift($destination);
            $snsc_orders[$key]['coliposte_destination'] = implode(', ', $destination);
        }

        $this->context->smarty->assign(
            array(
                'alert_class' => $alert_class,
                'snsc_orders' => $snsc_orders,
                'sne_sql_query' => preg_replace('/^[\s]{16}/m', '', $this->orders->getNewPSQuery()),
                'ps15x' => true
            )
        );

        $this->addJS($this->js.'orders.js');
        $this->addCSS($this->css.'orders.css');
        $this->addJqueryUI('ui.datepicker');
        $html = $this->soniceOrdersController->content($this->context->smarty);

        return ($html.$this->content.parent::renderForm());
    }
}
