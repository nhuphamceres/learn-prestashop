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

require_once(dirname(__FILE__).'/env.php');
require_once(dirname(__FILE__).'/../fnac.php');
require_once(dirname(__FILE__).'/../classes/fnac.tools.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.product.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.context.class.php');

class Product_FnacExt_Manager extends Fnac
{

    public $id_product;
    public $images;
    public $url;
    
    public $fnacmodule;

    public function __construct()
    {
        parent::__construct();
        FNAC_Context::restore($this->context, Validate::isLoadedObject($this->context->shop) ? $this->context->shop : null);
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, 'product_ext', $this->id_lang));
    }

    private function _forceUnavailable($default)
    {
        $view_params = array();
        $view_params['checked'] = $default ? 'checked="checked"' : '';

        return ($view_params);
    }


    private function _forceInStock($default)
    {
        $view_params = array();
        $view_params['checked'] = $default ? 'checked="checked"' : '';

        return ($view_params);
    }

    private function _extraText($default, $default_es, $default_pt)
    {
        $view_params = array();
        $view_params['value'] = $default;
        $view_params['value_es'] = $default_es;
        $view_params['value_pt'] = $default_pt;

        return ($view_params);
    }

    private function _extraPrice($default)
    {
        $view_params = array();
        $view_params['value'] = ((float)$default ? sprintf('%.02f', $default) : '');

        return ($view_params);
    }

    public function doIt($id_product = null)
    {
        $id_lang = $this->context->language->id;

        $this->id_product = Tools::getValue('id_product', $id_product);

        $view_params = array();
        $view_params['id_lang'] = $id_lang;
        $view_params['images'] = $this->images;
        $view_params['module_url'] = $this->url;
        $view_params['id_product'] = Tools::getValue('id_product');

        $defaults = Fnac_Product::getProductOptions($this->id_product, $id_lang);

        $view_params['force_unavailable'] = $this->_forceUnavailable($defaults['disable']);
        $view_params['force_in_stock'] = $this->_forceInStock($defaults['force']);
        $view_params['extra_text'] = $this->_extraText($defaults['text'], $defaults['text_es'], $defaults['text_pt']);
        $view_params['extra_price'] = $this->_extraPrice($defaults['price']);
        $view_params['time_to_ship'] = $defaults['time_to_ship'];

        if (version_compare(_PS_VERSION_, '1.5', '>') &&
            version_compare(_PS_VERSION_, '1.7', '<')) {
            $view_params['PS16'] = 1;
        } else {
            $view_params['PS17'] = 1;
        }

        $this->context->smarty->assign($view_params);

        return $this->context->smarty->fetch($this->path.'views/templates/admin/catalog/product_extfnac.tpl');
    }
}

$productExtManager = new Product_FnacExt_Manager();
$productExtManager->DoIt();
