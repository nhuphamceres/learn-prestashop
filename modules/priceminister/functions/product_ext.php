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

if (isset($_SERVER['DropBox']) && $_SERVER['DropBox']) {
    require_once(readlink(dirname($_SERVER['SCRIPT_FILENAME']).'/../../../config/config.inc.php'));
    require_once(readlink(dirname($_SERVER['SCRIPT_FILENAME']).'/../../../init.php'));
} else {
    @require_once(dirname(__FILE__).'/../../../config/config.inc.php');
    @require_once(dirname(__FILE__).'/../../../init.php');
}

require_once(dirname(__FILE__).'/../priceminister.php');
require_once(dirname(__FILE__).'/../classes/priceminister.product.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.context.class.php');

class Product_Ext_Manager extends PriceMinister
{

    public function __construct()
    {
        parent::__construct();
        PriceMinisterContext::restore($this->context, Validate::isLoadedObject($this->context->shop) ? $this->context->shop : null);
    }

    public function doIt()
    {
        $id_lang = $this->id_lang;

        $view_params = array();
        $view_params['id_lang'] = $id_lang;
        $view_params['images'] = $this->images;
        $view_params['module_url'] = $this->url;
        $view_params['id_product'] = (int)Tools::getValue('id_product');
        $view_params['pm_token'] = Configuration::get(PriceMinister::CONFIG_PM_CRON_TOKEN);

        $view_params['pm_context_key'] = '';

        if (Validate::isLoadedObject($this->context->shop)) {
            $view_params['pm_context_key'] = PriceMinisterContext::getKey($this->context->shop);
        }

        $defaults = PriceMinisterProductExt::getProductOptions($view_params['id_product'], null, $id_lang);
        if (!is_array($defaults) || !count($defaults)) {
            $defaults['disable'] = null;
            $defaults['force'] = null;
            $defaults['text'] = null;
            $defaults['price'] = null;
        } else {
            $defaults = reset($defaults);
        }

        $view_params['force_unavailable'] = $this->_forceUnavailable($defaults['disable']);

        $view_params['force_in_stock'] = $this->_forceInStock($defaults['force']);

        $view_params['force_extra_text'] = $this->_extraText($defaults['text']);

        $view_params['force_extra_price'] = $this->_extraPrice($defaults['price']);

        $view_params['class_success'] = $this->ps16x ? 'alert alert-success' : 'conf';

        $this->context->smarty->assign($view_params);
        echo $this->context->smarty->fetch($this->path.'views/templates/admin/catalog/product_extpm.tpl');
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

    private function _extraText($default)
    {
        $view_params = array();
        $view_params['value'] = $default;

        return ($view_params);
    }

    private function _extraPrice($default)
    {
        $view_params = array();
        $view_params['value'] = ((float)$default ? sprintf('%.02f', $default) : '');

        return ($view_params);
    }

    private function _displayCheckBox($name, $value, $fieldvalue = 1)
    {
        if ($value == $fieldvalue) {
            $checked = 'checked="checked"';
        } else {
            $checked = '';
        }

        $html = '
        <tr class="productpm-details">
          <td class="col-left">'.$this->l($name).': </td>
          <td style="padding-bottom:5px;">
          <input type="checkbox" name="'.$this->normalstring($name).'" value="'.$fieldvalue.'" '.$checked.' />
          </td>
        </tr>';

        return ($html);
    }

    private function _displayInput($name, $value)
    {
        $html = '
        <tr class="productpm-details">
          <td class="col-left">'.$this->l($name).': </td>
          <td style="padding-bottom:5px;">
          <input type="text" name="'.$this->normalstring($name).'" value="'.$value.'" />
          </td>
        </tr>';

        return ($html);
    }

    private function _displaySelectBox($name, $value, $fieldvalue = array())
    {
        $html = '
        <tr class="productpm-details">
          <td class="col-left">'.$this->l($name).': </td>
          <td style="padding-bottom:5px;">
          <select name="'.$this->normalstring($name).'" style="width:150px" >';
        foreach ($fieldvalue as $key => $item) {
            if ($value == $key) {
                $checked = 'selected="selected"';
            } else {
                $checked = '';
            }
            $html .= '<option value="'.$key.'" '.$checked.'>'.$item.'</option>'."\n";
        }

        $html .= '
          </select>
          </td>
        </tr>
        ';

        return ($html);
    }

    private function _displayRadio($name, $value, $fieldvalue = array())
    {
        $html = '
        <tr class="productpm-details">
          <td class="col-left">'.$this->l($name).': </td>
          <td style="padding-bottom:5px;">';
        foreach ($fieldvalue as $key => $item) {
            if ($value == $key) {
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }
            $html .= '<input type="radio" name="'.$this->normalstring($name).'" value="'.$key.'" '.$checked.' />&nbsp;'.$item.'&nbsp;&nbsp;&nbsp;\n';
        }

        $html .= '
          </td>
        </tr>';

        return ($html);
    }
}

$productExtManager = new Product_Ext_Manager();
$productExtManager->DoIt();