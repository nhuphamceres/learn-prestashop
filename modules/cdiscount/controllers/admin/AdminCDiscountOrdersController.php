<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Common-Services Co., Ltd. is strictly forbidden.
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
 *
 * @author    Olivier B.
 * @copyright Copyright (c) 2011-2017 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   CDiscount
  * Support by mail:  support.cdiscount@common-services.com
 */

require_once(dirname(__FILE__).'/../../cdiscount.php');
require_once(dirname(__FILE__).'/../../classes/cdiscount.tools.class.php');
require_once(dirname(__FILE__).'/../../classes/cdiscount.context.class.php');
require_once(dirname(__FILE__).'/AdminCDiscountOrdersController.inc.php');


class AdminCDiscountOrdersController extends ModuleAdminController
{
    const SUB_CONTROLLER = 'AdminCDiscountOrdersControllerExt';
    const MODULE = CDiscount::MODULE;

    public function __construct()
    {
        $this->path = _PS_MODULE_DIR_.self::MODULE.'/';

        $this->className = self::MODULE;
        $this->display = 'edit';

        $this->id_lang = (int)Context::getContext()->language->id;

        $this->lang = true;
        $this->deleted = false;
        $this->colorOnBackground = false;

        $this->url = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.self::MODULE.'/';
        $this->images = $this->url.'views/img/';

        $this->context = Context::getContext();
        $subcontroller = self::SUB_CONTROLLER;
        $this->ordersController = new $subcontroller($this->id_lang);

        $this->bootstrap = true;

        parent::__construct();
    }

    public function renderForm()
    {
        $this->addJqueryUI('ui.datepicker');

        $this->addCSS($this->url.'views/css/orders.css', 'screen');

        $this->addJS($this->url.'views/js/orders_common.js');
        $this->addJS($this->url.'views/js/orders_accept.js');
        $this->addJS($this->url.'views/js/orders_import.js');

        $html = $this->ordersController->content($this->context->smarty);

        return $html.$this->content.parent::renderForm();
    }
}
