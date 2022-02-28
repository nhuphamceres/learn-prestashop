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

// Sep-25-2018: Use only 1 main class for all marketplaces

if (version_compare(_PS_VERSION_, '1.5', '<') && defined('PS_ADMIN_DIR') && file_exists(PS_ADMIN_DIR.'/../classes/AdminTab.php')) {
    include_once(PS_ADMIN_DIR.'/../classes/AdminTab.php');
}

require_once(dirname(__FILE__).'/mirakl.php');
require_once(dirname(__FILE__).'/classes/tools.class.php');
require_once(dirname(__FILE__).'/controllers/admin/AdminMiraklProductsController.inc.php');

// Sep-25-2018: Use only 1 main class for all marketplaces

class ProductsMirakl extends AdminTab
{
    private $mirakl;
    private $mirakl_product_controller = null;
    public $multishop_context;
    public $multishop_context_group;
    public $id_lang;
    public $url;

    public function __construct()
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            require_once(dirname(__FILE__).'/backward_compatibility/backward.php');
        }

        $cookie = Context::getContext()->cookie;

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $this->multishop_context = Shop::CONTEXT_ALL;
            $this->id_lang = (int)Context::getContext()->language->id;
        } else {
            $this->id_lang = (int)$cookie->id_lang;
        }
        $this->mirakl = new Mirakl();

        $this->url = $this->mirakl->url;
        $this->mirakl_product_controller = new AdminMiraklProductsControllerExt($this->id_lang, null, $this->mirakl->name);

        parent::__construct();
    }

    public function l($string, $class = false, $addslashes = false, $htmlentities = true)
    {
        return $this->mirakl->l($string, __CLASS__, $addslashes, $htmlentities);
    }

    public function display()
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            require_once(dirname(__FILE__).'/backward_compatibility/backward.php');
        }

        $smarty = Context::getContext()->smarty;

        $this->addCSS($this->url.'views/css/products.css', 'screen');
        $this->addJS($this->url.'views/js/products.js');

        @includeDatepicker('nothing');

        echo $this->mirakl_product_controller->content($smarty);
    }

    public function addCSS($css)
    {
        echo html_entity_decode('&lt;link type="text/css" rel="stylesheet" href="'.$css.'" /&gt;')."\n";
    }

    public function addJS($js)
    {
        echo html_entity_decode('&lt;script type="text/javascript" src="'.$js.'"&gt;&lt;/script&gt;')."\n";
    }
}

// Sep-25-2018: Use only 1 main class for all marketplaces
