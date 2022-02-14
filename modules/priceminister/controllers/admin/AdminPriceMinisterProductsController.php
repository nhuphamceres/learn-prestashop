<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
 * Contact by Email :  support.priceminister@common-services.com
 */

require_once(_PS_MODULE_DIR_.'priceminister/priceminister.php');
require_once(_PS_MODULE_DIR_.'priceminister/classes/priceminister.tools.class.php');

require_once(dirname(__FILE__).'/AdminPriceMinisterProductsController.inc.php');

class AdminPriceMinisterProductsController extends ModuleAdminController
{

    public $name = 'priceminister';

    public function __construct()
    {
        $this->className = 'priceminister';
        $this->display = 'edit';

        parent::__construct();

        $this->lang = true;
        $this->deleted = false;
        $this->colorOnBackground = false;

        $this->context = Context::getContext();

        $this->priceministerProductController = new AdminPriceMinisterProductsControllerExt($this->context);
        $this->bootstrap = true;

        $this->url = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/';
    }

    public function renderForm()
    {
        $this->addJqueryUI('ui.datepicker');
        $this->addCSS($this->url.'views/css/products.css');
        $this->addJS($this->url.'views/js/catalogtab.js');

        $html = $this->priceministerProductController->content($this->context->smarty);

        return $html.$this->content.parent::renderForm();
    }

    /**
     * Replace display name
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        if ($this->page_header_toolbar_title) {
            $this->page_header_toolbar_title = str_replace($this->className, 'Rakuten France', $this->page_header_toolbar_title);
        }
    }
}