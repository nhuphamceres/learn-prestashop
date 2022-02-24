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
require_once(dirname(__FILE__).'/../classes/fnac.product.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.context.class.php');

class ProductFnac_Ext_ManagerJSON extends Fnac
{
    public function __construct()
    {
        parent::__construct();
        FNAC_Context::restore($this->context, Validate::isLoadedObject($this->context->shop) ? $this->context->shop : null);
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, 'product_ext.json'));
    }

    public function doIt()
    {
        $langs = Tools::getValue('fnac_option_lang');
        $id_product = Tools::getValue('id_product');
        $id_category = Tools::getValue('id_category_default');
        $id_manufacturer = Tools::getValue('id_manufacturer');

        if (!$id_manufacturer) {
            $product = new Product($id_product);
            if (Validate::isLoadedObject($product)) {
                $id_manufacturer = $product->id_manufacturer;
            }
        }

        switch (Tools::getValue('action')) {
            case 'set' : // Set Product Opton
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;

                    $disable = Tools::getValue('fnac-disable-'.(int)$id_lang) ? 1 : 0;
                    $force = Tools::getValue('fnac-force-'.(int)$id_lang) ? 1 : 0;
                    $price = Tools::getValue('fnac-price-'.(int)$id_lang);
                    $text = Tools::getValue('fnac-text-'.(int)$id_lang);
                    $text_es = Tools::getValue('fnac-text-es-'.(int)$id_lang);
                    $text_pt = Tools::getValue('fnac-text-pt-'.(int)$id_lang);
                    $time_to_ship = Tools::getValue('fnac-time-'.(int)$id_lang);
                    
                    $options = array(
                        'force' => $force,
                        'disable' => $disable,
                        'price' => $price,
                        'text' => $text,
                        'text_es' => $text_es,
                        'text_pt' => $text_pt,
                        'time_to_ship' => (int)$time_to_ship
                    );

                    if (!Fnac_Product::setProductOptions($id_product, $id_lang, $options)) {
                        $pass = false;
                    }
                }

                if ($pass) {
                    echo $this->l('Parameters successfully saved');
                } else {
                    echo $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-text-cat' : // Propagate product option text
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;
                    $text = Tools::getValue('fnac-text-'.(int)$id_lang);
                    $text_es = Tools::getValue('fnac-text-es-'.(int)$id_lang);
                    $text_pt = Tools::getValue('fnac-text-pt-'.(int)$id_lang);
                    $options = array('text' => $text,'text_es' => $text_es, 'text_pt' => $text_pt );

                    if (!FNAC_Product::propagateProductOptionsText($id_product, $id_lang, $id_category, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    echo $this->l('Parameters successfully saved');
                } else {
                    echo $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-text-shop' : // Propagate product option text
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;
                    $text = Tools::getValue('fnac-text-'.(int)$id_lang);
                    $text_es = Tools::getValue('fnac-text-es-'.(int)$id_lang);
                    $text_pt = Tools::getValue('fnac-text-pt-'.(int)$id_lang);
                    $options = array('text' => $text,'text_es' => $text_es, 'text_pt' => $text_pt );

                    if (!FNAC_Product::propagateToShopProductOptionsText($id_product, $id_lang, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    echo $this->l('Parameters successfully saved');
                } else {
                    echo $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-text-manufacturer' : // Propagate product option text
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;
                    $text = Tools::getValue('fnac-text-'.(int)$id_lang);
                    $text_es = Tools::getValue('fnac-text-es-'.(int)$id_lang);
                    $text_pt = Tools::getValue('fnac-text-pt-'.(int)$id_lang);
                    $options = array('text' => $text,'text_es' => $text_es,  'text_pt' => $text_pt );

                    if (!FNAC_Product::propagateToManufacturerProductOptionsText($id_product, $id_lang, $id_manufacturer, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    echo $this->l('Parameters successfully saved');
                } else {
                    echo $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-disable-cat' : // Propagate product option text
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;
                    $value = Tools::getValue('fnac-disable-'.(int)$id_lang);

                    $options = array('disable' => $value);

                    if (!FNAC_Product::propagateProductOptionsDisable($id_product, $id_lang, $id_category, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    echo $this->l('Parameters successfully saved');
                } else {
                    echo $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-disable-shop' : // Propagate product option text
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;
                    $value = Tools::getValue('fnac-disable-'.(int)$id_lang);

                    $options = array('disable' => $value);

                    if (!FNAC_Product::propagateToShopProductOptionsDisable($id_product, $id_lang, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    echo $this->l('Parameters successfully saved');
                } else {
                    echo $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-disable-manufacturer' : // Propagate product option text
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;
                    $value = Tools::getValue('fnac-disable-'.(int)$id_lang);

                    $options = array('disable' => $value);

                    if (!FNAC_Product::propagateToManufacturerProductOptionsDisable($id_product, $id_lang, $id_manufacturer, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    echo $this->l('Parameters successfully saved');
                } else {
                    echo $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-force-cat' : // Propagate product option text
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;
                    $text = Tools::getValue('fnac-force-'.(int)$id_lang);

                    $options = array('force' => $text);

                    if (!FNAC_Product::propagateProductOptionsForce($id_product, $id_lang, $id_category, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    echo $this->l('Parameters successfully saved');
                } else {
                    echo $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-force-shop' : // Propagate product option text
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;
                    $value = Tools::getValue('fnac-force-'.(int)$id_lang);

                    $options = array('force' => $value);

                    if (!FNAC_Product::propagateToShopProductOptionsForce($id_product, $id_lang, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    echo $this->l('Parameters successfully saved');
                } else {
                    echo $this->l('Unable to save parameters...');
                }

                break;

            case 'propagate-force-manufacturer' : // Propagate product option text
                $pass = true;

                foreach ($langs as $key => $val) {
                    $id_lang = (int)$val;
                    $value = Tools::getValue('fnac-force-'.(int)$id_lang);

                    $options = array('force' => $value);

                    if (!FNAC_Product::propagateToManufacturerProductOptionsForce($id_product, $id_lang, $id_manufacturer, $options)) {
                        $pass = false;
                    }
                }
                if ($pass) {
                    echo $this->l('Parameters successfully saved');
                } else {
                    echo $this->l('Unable to save parameters...');
                }

                break;


            default :
                break;
        }
    }
}

$ext = new ProductFnac_Ext_ManagerJSON();
$ext->doIt();
