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

require_once(dirname(__FILE__).'/env.php');
require_once(dirname(__FILE__).'/../mirakl.php');
require_once(dirname(__FILE__).'/../classes/tools.class.php');

// Sep-25-2018: Use only 1 main class for all marketplaces

class ProductMiraklExtManager extends Mirakl
{
    public function l($string, $specific = false, $id_lang = null)
    {
        return parent::l($string, basename(__FILE__, '.php'), $id_lang);
    }

    public function doIt()
    {
        $mirakl_params = self::$marketplace_params;

        // Sep-25-2018: Share mirakl_product_option for all marketplaces

        $id_lang = Language::getIdByIso('fr');
        $view_params = array();
        $view_params['id_lang'] = $id_lang;
        $view_params['images'] = $this->images;
        $view_params['marketplace'] = $mirakl_params['module'];
        $view_params['id_product'] = Tools::getValue('id_product');

        $defaults = MiraklProduct::getProductOptions($view_params['id_product'], $id_lang);

        $view_params['forceUnavailableChecked'] = $defaults['disable'] ? 'checked="checked"' : '';
        $view_params['forceInStockChecked'] = $defaults['force'] ? 'checked="checked"' : '';
        $view_params['extraText'] = $defaults['text'];
        $view_params['extraTextCount'] = (200 - Tools::strlen($defaults['text']));
        $view_params['extraPrice'] = ((float)$defaults['price'] ? sprintf('%.02f', $defaults['price']) : '');
        $view_params['shippingDelay'] = ($defaults['shipping']);

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $view_params['PS14'] = '1';
        }

        $view_params['class_success'] = $this->ps16x ? 'alert alert-success' : 'conf';
        $view_params['PS14'] = version_compare(_PS_VERSION_, '1.5', '<');

        $this->context->smarty->assign($view_params);
        echo $this->context->smarty->fetch($this->path.'views/templates/admin/catalog/product_extme.tpl');
    }
}

$product_ext_manager = new ProductMiraklExtManager();
$product_ext_manager->DoIt();
