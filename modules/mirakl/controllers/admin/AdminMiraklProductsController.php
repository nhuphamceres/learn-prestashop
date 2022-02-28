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
 *
 * @author    Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 *  Support by mail  :  support.mirakl@common-services.com
 */

require_once(dirname(__FILE__).'/../../mirakl.php');
require_once(dirname(__FILE__).'/../../classes/tools.class.php');
require_once(dirname(__FILE__).'/AdminMiraklProductsController.inc.php');

if (!class_exists('AdminMiraklProductsController')) {
    class AdminMiraklProductsController extends ModuleAdminController
    {
        public $name   = null;
        public $module = null;
        public $url;
        public $images;
        public $id_lang;
        public $mirakl_product_controller;

        public function __construct()
        {
            $this->path = _PS_MODULE_DIR_.$this->name.'/';

            $this->name = Tools::strtolower(preg_replace('/Admin(.*)Products/', '$1', Tools::getValue('controller')));
            $this->module = $this->name;

            $this->className = Tools::ucfirst($this->name);
            $this->display = 'edit';

            $this->id_lang = (int)Context::getContext()->language->id;
            $this->lang = true;
            $this->deleted = false;
            $this->color_on_background = false;

            $this->url = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/';
            $this->images = $this->url.'views/img/';

            $this->context = Context::getContext();

            parent::__construct();
            $this->bootstrap = true;

            $context_data = new Context;
            $context_data->shop = $this->context->shop;
            $context_data->employee = $this->context->employee;
            $context_data->currency = $this->context->currency;

            $this->mirakl_product_controller = new AdminMiraklProductsControllerExt($this->id_lang, $context_data, $this->name);
        }

        public function renderForm()
        {
            $this->addJqueryUI('ui.datepicker');
            $this->addCSS($this->url.'views/css/products.css', 'screen');
            $this->addJS($this->url.'views/js/products.js');
            $html = $this->mirakl_product_controller->content($this->context->smarty);

            return $html.$this->content.parent::renderForm();
        }
    }
}
