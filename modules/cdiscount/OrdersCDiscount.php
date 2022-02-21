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

// PS < 1.5
if (defined('PS_ADMIN_DIR')) {
    include_once(PS_ADMIN_DIR.'/../classes/AdminTab.php');
}

require_once(dirname(__FILE__).'/cdiscount.php');
require_once(dirname(__FILE__).'/classes/cdiscount.tools.class.php');
require_once(dirname(__FILE__).'/controllers/admin/AdminCDiscountOrdersController.inc.php');

class OrdersCDiscount extends AdminTab
{
    private $module = 'cdiscount';

    private $cdiscountOrdersController;
    public $multishop_context;
    public $multishop_context_group;
    public $id_lang;

    public function __construct()
    {
        require(_PS_MODULE_DIR_.$this->module.'/backward_compatibility/backward.php');

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $this->multishop_context = Shop::CONTEXT_ALL;
        }
        $this->url = __PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->module.'/';

        $this->debug = Configuration::get('CDISCOUNT_DEBUG') ? true : false;

        parent::__construct();

        $this->id_lang = (int)Context::getContext()->language->id;
        $this->cdiscountOrdersController = new AdminCDiscountOrdersControllerExt($this->id_lang);
    }

    public function display()
    {
        $this->context = Context::getContext();
        $smarty = $this->context->smarty;

        $this->addCSS($this->url.'views/css/orders.css', 'screen');

        $this->addJS($this->url.'views/js/orders_common.js');
        $this->addJS($this->url.'views/js/orders_accept.js');
        $this->addJS($this->url.'views/js/orders_import.js');

        @includeDatepicker('nothing');

        echo $this->cdiscountOrdersController->content($smarty);
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
