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

require_once(dirname(__FILE__).'/../classes/cdiscount.product.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.context.class.php');

class CDiscountProductExtManagerJSON extends CDiscount
{
    public function __construct()
    {
        parent::__construct();
        CDiscountContext::restore($this->context);
    }

    public function doIt()
    {
        $callback = Tools::getValue('callback');
        $langs = Tools::getValue('marketplace_option_lang');
        $id_product = Tools::getValue('id_product');
        $id_category = null;
        $id_manufacturer = null;
        $id_supplier = null;

        if (empty($id_product) || !is_numeric($id_product)) {
            $action = null;
        } else {
            $product = new Product($id_product);

            if (Validate::isLoadedObject($product)) {
                $id_category = $product->id_category_default;
                $id_manufacturer = $product->id_manufacturer;
            }
            $action = Tools::getValue('action');
        }

        $scope = Tools::getValue('scope');
        $param = Tools::getValue('param');

        if (preg_match('/^propagate-(.*)-(.*)/', $action, $result)) {
            $action = 'propagate';
            $param = trim($result[1]);
            $scope = trim($result[2]);
        }

        switch ($action) {
            case 'set': // Set Product Opton
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;
                    $disable = Tools::getValue('marketplace-disable-'.(int)$id_lang) ? 1 : 0;
                    $force = (bool)Tools::getValue('marketplace-force-'.(int)$id_lang) ? 1 : 0;
                    $price = Tools::getValue('marketplace-price-'.(int)$id_lang);
                    $shipping = Tools::getValue('marketplace-shipping-'.(int)$id_lang);
                    $delay = (int)Tools::getValue('marketplace-shipping_delay-'.(int)$id_lang);
                    $price_up = Tools::getValue('marketplace-alignup-'.(int)$id_lang);
                    $price_down = Tools::getValue('marketplace-aligndown-'.(int)$id_lang);
                    $text = Tools::getValue('marketplace-text-'.(int)$id_lang);
                    $clogistique = Tools::getValue('marketplace-clogistique-'.(int)$id_lang);
                    $valueadded = Tools::getValue('marketplace-valueadded-'.(int)$id_lang);

                    $price_up = (float)trim(str_replace(',', '.', $price_up));
                    $price_down = (float)trim(str_replace(',', '.', $price_down));
                    $valueadded = (float)trim(str_replace(',', '.', $valueadded));
                    $price = (float)str_replace(',', '.', $price);
                    $delay = (int)trim($delay);

                    $options = array(
                        'force' => $force,
                        'disable' => $disable,
                        'price' => $price,
                        'price_up' => $price_up,
                        'price_down' => $price_down,
                        'shipping' => $shipping,
                        'shipping_delay' => $delay,
                        'clogistique' => $clogistique,
                        'valueadded' => $valueadded,
                        'text' => $text
                    );

                    if (!CDiscountProduct::setProductOptions($id_product, $id_lang, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    echo $this->l('Parameters successfully saved');
                } else {
                    echo $this->l('Unable to save parameters...');
                }

                break;
            case 'propagate':
                switch ($scope) {
                    case 'shop':
                        $pass = true;

                        foreach ($langs as $key => $val) {
                            $id_lang = (int)$val;
                            $value = Tools::getValue('marketplace-'.$param.'-'.(int)$id_lang);

                            //
                            if (!CDiscountProduct::propagateProductOptionToShop($id_product, $id_lang, $param, $value)) {
                                $pass = false;
                            }
                        }
                        if ($pass) {
                            echo $this->l('Parameters successfully saved');
                        } else {
                            echo $this->l('Unable to save parameters...');
                        }

                        break;

                    case 'cat':
                        $pass = true;

                        foreach ($langs as $key => $val) {
                            $id_lang = (int)$val;
                            $value = Tools::getValue('marketplace-'.$param.'-'.(int)$id_lang);

                            if (!CDiscountProduct::propagateProductOptionToCategory($id_product, $id_lang, $id_category, $param, $value)) {
                                $pass = false;
                            }
                        }
                        if ($pass) {
                            echo $this->l('Parameters successfully saved');
                        } else {
                            echo $this->l('Unable to save parameters...');
                        }

                        break;
                    case 'manufacturer':
                        $pass = true;

                        foreach ($langs as $key => $val) {
                            $id_lang = (int)$val;
                            $value = Tools::getValue('marketplace-'.$param.'-'.(int)$id_lang);

                            if (!CDiscountProduct::propagateProductOptionToManufacturer($id_product, $id_lang, $id_manufacturer, $param, $value)) {
                                $pass = false;
                            }
                        }
                        if ($pass) {
                            echo $this->l('Parameters successfully saved');
                        } else {
                            echo $this->l('Unable to save parameters...');
                        }

                        break;
                }
                break;
            default:
                echo $this->l('Unknown action...');
                break;
        }
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }
}

$ext = new CDiscountProductExtManagerJSON();
$ext->doIt();
