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
require_once(dirname(__FILE__).'/../classes/product.class.php');

// Sep-25-2018: Use only 1 main class for all marketplaces

class ProductMiraklExtManagerJSON extends Mirakl
{
    public function l($string, $specific = false, $id_lang = null)
    {
        return parent::l($string, basename(__FILE__, '.php'), $id_lang);
    }

    public function doIt()
    {
        $callback = Tools::getValue('callback');

        if (Tools::getValue('instant_token') !== Mirakl::getConfigGlobalShop(Mirakl::CONFIG_INSTANT_TOKEN)) {
//            die(
//                (string)$callback.'('.Tools::jsonEncode(array('error' => true, 'msg' => 'Wrong token...')).')'
//            );
        }

        $langs = Tools::getValue('mirakl_option_lang');
        $id_product = Tools::getValue('id_product');
        $id_category = Tools::getValue('id_category_default');
        $id_manufacturer = Tools::getValue('id_manufacturer');
        $msg = '';

        // Sep-25-2018: Share mirakl_product_option for all marketplaces

        switch (Tools::getValue('action')) {
            case 'set': // Set Product options
                $pass = true;

                foreach ($langs as $val) {
                    $id_lang = (int)$val;
                    if (!MiraklProduct::setProductOptions($id_product, $id_lang, array(
                        'force' => Tools::getValue('mirakl-force-'. $id_lang) ? 1 : 0,
                        'disable' => Tools::getValue('mirakl-disable-'. $id_lang) ? 1 : 0,
                        'price' => str_replace(',', '.', Tools::getValue('mirakl-price-'. $id_lang)),
                        'shipping' => Tools::getValue('mirakl-shipping-delay-'. $id_lang),
                        'text' => Tools::getValue('mirakl-text-'. $id_lang) ? Tools::getValue('mirakl-text-'. $id_lang) : null,
                        'mkp_specific_fields' => json_encode((array)Tools::getValue('specific_field_' . $id_lang)),
                    ))) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    $msg = $this->l('Parameters successfully saved');
                } else {
                    $msg = $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-text-cat': // Propagate product option text
                $pass = true;

                foreach ($langs as $val) {
                    $id_lang = (int)$val;
                    $text = Tools::getValue('mirakl-text-'.(int)$id_lang);

                    $options = array('text' => $text);

                    if (!MiraklProduct::propagateProductOptionsText($id_product, $id_lang, $id_category, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    $msg = $this->l('Parameters successfully saved');
                } else {
                    $msg = $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-text-shop': // Propagate product option text
                $pass = true;

                foreach ($langs as $val) {
                    $id_lang = (int)$val;
                    $text = Tools::getValue('mirakl-text-'.(int)$id_lang);

                    $options = array('text' => $text);

                    if (!MiraklProduct::propagateToShopProductOptionsText($id_product, $id_lang, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    $msg = $this->l('Parameters successfully saved');
                } else {
                    $msg = $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-text-manufacturer': // Propagate product option text
                $pass = true;

                foreach ($langs as $val) {
                    $id_lang = (int)$val;
                    $text = Tools::getValue('mirakl-text-'.(int)$id_lang);

                    $options = array('text' => $text);

                    if (!MiraklProduct::propagateToManufacturerProductOptionsText($id_product, $id_lang, $id_manufacturer, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    $msg = $this->l('Parameters successfully saved');
                } else {
                    $msg = $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-disable-cat': // Propagate product option text
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;
                    $value = Tools::getValue('mirakl-disable-'.(int)$id_lang);

                    $options = array('disable' => $value);

                    if (!MiraklProduct::propagateProductOptionsDisable($id_product, $id_lang, $id_category, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    $msg = $this->l('Parameters successfully saved');
                } else {
                    $msg = $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-disable-shop': // Propagate product option text
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;
                    $value = Tools::getValue('mirakl-disable-'.(int)$id_lang);

                    $options = array('disable' => $value);

                    if (!MiraklProduct::propagateToShopProductOptionsDisable($id_product, $id_lang, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    $msg = $this->l('Parameters successfully saved');
                } else {
                    $msg = $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-disable-manufacturer': // Propagate product option text
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;
                    $value = Tools::getValue('mirakl-disable-'.(int)$id_lang);

                    $options = array('disable' => $value);

                    if (!MiraklProduct::propagateToManufacturerProductOptionsDisable($id_product, $id_lang, $id_manufacturer, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    $msg = $this->l('Parameters successfully saved');
                } else {
                    $msg = $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-force-cat': // Propagate product option text
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;
                    $text = Tools::getValue('mirakl-force-'.(int)$id_lang);

                    $options = array('force' => $text);

                    if (!MiraklProduct::propagateProductOptionsForce($id_product, $id_lang, $id_category, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    $msg = $this->l('Parameters successfully saved');
                } else {
                    $msg = $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-force-shop': // Propagate product option text
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;
                    $value = Tools::getValue('mirakl-force-'.(int)$id_lang);

                    $options = array('force' => $value);

                    if (!MiraklProduct::propagateToShopProductOptionsForce($id_product, $id_lang, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    $msg = $this->l('Parameters successfully saved');
                } else {
                    $msg = $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-force-manufacturer': // Propagate product option text
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;
                    $value = Tools::getValue('mirakl-force-'.(int)$id_lang);

                    $options = array('force' => $value);

                    if (!MiraklProduct::propagateToManufacturerProductOptionsForce($id_product, $id_lang, $id_manufacturer, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    $msg = $this->l('Parameters successfully saved');
                } else {
                    $msg = $this->l('Unable to save parameters...');
                }

                break;

            default:
                break;
        }

        die(
            (string)$callback.'('.Tools::jsonEncode(array('error' => !(bool)$pass, 'msg' => $msg)).')'
        );
    }
}

$ext = new ProductMiraklExtManagerJSON();
$ext->doIt();
