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

if (version_compare(_PS_VERSION_, '1.5', '<') && defined('PS_ADMIN_DIR') && file_exists(PS_ADMIN_DIR.'/../classes/AdminTab.php')) {
    include_once(PS_ADMIN_DIR.'/../classes/AdminTab.php');
}

require_once(dirname(__FILE__).'/sonice_suivicolis.php');
require_once(dirname(__FILE__).'/controllers/admin/AdminOrdersSoniceSuiviController.inc.php');
require_once(dirname(__FILE__).'/classes/SoNiceSuiviOrderHelperList.php');

class OrdersSoniceSuivi extends AdminTab
{

    private $module = 'sonice_suivicolis';

    public $protocol;
    public $url;

    public function __construct()
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $this->multishop_context = Shop::CONTEXT_ALL;
            $this->id_lang = (int)Context::getContext()->language->id;
        } else {
            require_once(dirname(__FILE__).'/backward_compatibility/backward.php');
            $this->context = Context::getContext();
            $this->id_lang = (int)Context::getContext()->language->id;
        }

        $this->protocol = Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://';
        $this->url = $this->protocol.htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.
            basename(_PS_MODULE_DIR_).'/'.$this->module.'/';
        $this->sonice_controller = new AdminSoniceOrdersControllerExt($this->id_lang);

        // Orders list
        $this->orders = new SoNiceOrderHelperList($this->id_lang);

        parent::__construct();
    }


    public function display()
    {
        $this->_addCSS($this->url.'views/css/orders.css');
        $this->_addCSS($this->url.'views/css/orders_compat.css');
        $this->_addJS($this->url.'views/js/orders.js');
        
        $this->context->smarty->assign(
            array(
                'snsc_orders' => $this->orders->getOrders(),
                'ps15x' => false,
                'snsc_token_order' => Tools::getAdminToken('AdminOrders'.(int)Tab::getIdFromClassName('AdminOrders').(int)$this->context->employee->id)
            )
        );

        echo $this->sonice_controller->content($this->context->smarty);
    }

    private function _addCSS($css)
    {
        echo '<link type="text/css" rel="stylesheet" href="'.$css.'" />'."\n";

        return true;
    }

    private function _addJS($js)
    {
        echo '<script type="text/javascript" src="'.$js.'"></script>'."\n";

        return true;
    }
}
