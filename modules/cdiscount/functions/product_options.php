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

require_once(dirname(__FILE__).'/env.php');
require_once(dirname(__FILE__).'/../cdiscount.php');

require_once(dirname(__FILE__).'/../classes/cdiscount.tools.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.product.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.context.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.settings.class.php');

class CDiscountExtManager extends CDiscount
{
    public $clogistique = false;

    public function __construct()
    {
        parent::__construct();

        CDiscountContext::restore($this->context);

        $this->clogistique = (bool)Configuration::get(parent::KEY.'_CLOGISTIQUE');
        $this->alignment = (bool)Configuration::get(self::KEY.'_ALIGNMENT_ACTIVE');
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    public function doIt()
    {
        $id_lang = Language::getIdByIso('fr');
        //French might not be set as language for the shop...
        if (!$id_lang) {
            $default_lang = Language::getLanguages(true);
            $id_lang = (count($default_lang) > 0) ? $default_lang[0]['id_lang'] : 1;
        }
        $lang = Language::getIsoById($id_lang);


        $view_params = array();
        $view_params['module_url'] = $this->url;
        $view_params['version'] = $this->version;
        $view_params['id_lang'] = $id_lang;
        $view_params['marketplace'] = parent::MODULE;
        $view_params['images'] = $this->images;
        $view_params['id_product'] = Tools::getValue('id_product');

        $defaults = CDiscountProduct::getProductOptions($view_params['id_product'], $id_lang);

        $view_params['forceUnavailableChecked'] = $defaults['disable'] ? 'checked="checked"' : '';
        $view_params['forceInStockChecked'] = $defaults['force'] ? 'checked="checked"' : '';
        $view_params['extraText'] = $defaults['text'];
        $view_params['extraTextCount'] = (200 - Tools::strlen($defaults['text']));
        $view_params['extraPrice'] = ((float)$defaults['price'] ? sprintf('%.02f', $defaults['price']) : '');
        $view_params['extraDelay'] = ($defaults['shipping_delay'] && is_numeric($defaults['shipping_delay']) ? (int)$defaults['shipping_delay'] : '');
        $view_params['priceDown'] = ($defaults['price_down'] ? sprintf('%.02f', $defaults['price_down']) : '');
        $view_params['priceUp'] = ($defaults['price_up'] ? sprintf('%.02f', $defaults['price_up']) : '');
        $view_params['conf_clogistique'] = $this->clogistique;
        $view_params['alignment'] = (bool)$this->alignment;
        $view_params['clogistique'] = ($this->clogistique && isset($defaults['clogistique']) && (bool)$defaults['clogistique']) ? true : false;
        $view_params['valueadded'] = ($this->clogistique && isset($defaults['valueadded']) && $defaults['valueadded'] ? sprintf('%.02f', $defaults['valueadded']) : null);

        $view_params['class_success'] = $this->ps16x ? 'alert alert-success' : 'conf';

        $view_params['glossary'] = CdiscountSettings::getGlossary($lang, 'product_tab');

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $view_params['PS14'] = '1';
        }

        $this->context->smarty->assign($view_params);
        echo $this->context->smarty->fetch($this->path.'views/templates/admin/catalog/product_options.tpl');
    }

    public function isPS14()
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            return true;
        } else {
            return false;
        }
    }
}

$prodExtManagercdiscount = new CDiscountExtManager();
if ($prodExtManagercdiscount->isPS14()) {
    $prodExtManagercdiscount->doIt();
} else {
    $prodExtManagercdiscount = null;
}
