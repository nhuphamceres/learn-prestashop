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

if (defined('PS_ADMIN_DIR')) {
    include_once(PS_ADMIN_DIR.'/../classes/AdminTab.php');
}

require_once(dirname(__FILE__).'/priceminister.php');

require_once(dirname(__FILE__).'/classes/priceminister.api.webservices.php');
require_once(dirname(__FILE__).'/classes/priceminister.sales.api.php');
require_once(dirname(__FILE__).'/controllers/admin/AdminPriceMinisterOrdersController.inc.php');

class PriceMinisterOrder extends AdminTab
{

    public $module = 'priceminister';
    public $multishop_context;
    public $multishop_context_group;
    private $url;

    /** @var Context */
    public $context;

    public function __construct()
    {
        require_once dirname(__FILE__).'/backward_compatibility/backward.php';

        $this->context = Context::getContext();
        $this->id_lang = (int)$this->context->cookie->id_lang;
        $this->url = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->module.'/';
        $this->images = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->module.'/views/img/';

        $this->priceMinisterOrdersController = new AdminPriceMinisterOrdersControllerExt($this->id_lang);

        parent::__construct();
    }

    public function display()
    {
        @includeDatepicker('nothing');

        $this->addCSS($this->url.'views/css/ordertab.css', 'screen');

        $this->addJS($this->url.'views/js/ordertab.js');

        echo $this->priceMinisterOrdersController->content($this->context->smarty);
    }

    public function addCSS($css)
    {
        echo '<link type="text/css" rel="stylesheet" href="'.$css.'" />'."\n";
    }

    public function addJS($js)
    {
        echo '<script type="text/javascript" src="'.$js.'"></script>'."\n";
    }
}
