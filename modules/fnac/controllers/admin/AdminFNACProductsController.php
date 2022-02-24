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

require_once(dirname(__FILE__).'/../../fnac.php');
require_once(dirname(__FILE__).'/../../classes/fnac.tools.class.php');
require_once(dirname(__FILE__).'/AdminFNACProductsController.inc.php');

class AdminFNACProductsController extends ModuleAdminController
{
    public $name = 'fnac';

    public function __construct()
    {
        parent::__construct();

        $this->multishop_context = Shop::CONTEXT_SHOP;

        $this->path = _PS_MODULE_DIR_.$this->name.'/';

        $this->className = 'fnac';
        $this->display = 'edit';

        $this->id_lang = (int)Context::getContext()->language->id;

        $this->lang = true;
        $this->deleted = false;
        $this->colorOnBackground = false;

        $this->url = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/';
        $this->images = $this->url.'views/img/';

        $this->context = Context::getContext();
        $this->fnacProductController = new AdminFNACProductsControllerExt($this->id_lang);

        $this->bootstrap = true;
        $this->ps16x = (bool)version_compare(_PS_VERSION_, '1.6', '>=');
    }

    public function renderForm()
    {
        $this->addJqueryUI('ui.datepicker');

        $this->addCSS($this->url.'views/css/products.css', 'screen');

        $this->addJS($this->url.'views/js/products.js');

        $alert_class = array();
        $alert_class['danger'] = $this->ps16x ? 'alert alert-danger' : 'error';
        $alert_class['warning'] = $this->ps16x ? 'alert alert-warning' : 'warn';
        $alert_class['success'] = $this->ps16x ? 'alert alert-success' : 'conf';
        $alert_class['info'] = $this->ps16x ? 'alert alert-info' : 'info';
        $this->context->smarty->assign('alert_class', $alert_class);

        $html = $this->fnacProductController->content($this->context->smarty);

        return $html.$this->content.parent::renderForm();
    }
}
