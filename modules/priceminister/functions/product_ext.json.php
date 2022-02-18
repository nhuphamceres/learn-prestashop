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
require_once(dirname(__FILE__).'/../classes/priceminister.product_tab.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.categories.class.php');

class Product_Ext_ManagerJSON extends PriceMinister
{

    private static $sustitute = array(
        'pm_price' => 'price',
        'pm_repricing_min' => 'repricing_min',
        'pm_repricing_max' => 'repricing_max'
    );

    public function __construct()
    {
        parent::__construct();

        PriceMinisterContext::restore($this->context);

        $this->debug = (bool)Configuration::get(PriceMinister::CONFIG_PM_DEBUG) || Tools::getValue('debug');

        if ($this->debug) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }

        $id_employee = isset($this->context->employee) ? (int)$this->context->employee->id :
            (int)Db::getInstance()->getValue('SELECT `id_employee` FROM `'._DB_PREFIX_.'employee` WHERE `id_profile` = 1');

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $employee = null;

            if ($id_employee) {
                $employee = new Employee($id_employee);
            }

            if (!Validate::isLoadedObject($employee)) {
                die($this->l('Wrong Employee, please save the module configuration'));
            }

            $this->context = Context::getContext();
            $this->context->customer->is_guest = true;
            $this->context->cart = new Cart();
            $this->context->employee = $employee;
            $this->context->currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
            $this->context->customer->is_guest = true;
        }
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    public function doIt()
    {
        $field = null;
        $pass = true;
        ob_start();
        $callback = Tools::getValue('callback');
        $id_lang = Tools::getValue('id_lang');
        $id_product = Tools::getValue('pm_id_product') ? Tools::getValue('pm_id_product') : Tools::getValue('id_product');
        $id_product_attribute = (int)Tools::getValue('id_product_attribute');
        $region = Tools::getValue('region');
        $id_category = Tools::getValue('pm_id_category_default');
        $id_manufacturer = Tools::getValue('pm_id_manufacturer');
        $id_supplier = Tools::getValue('pm_id_supplier');
        $token = Tools::getValue('pm_token');
        $action = Tools::getValue('action');

        if (!Tools::strlen($token) || $token != Configuration::get(PriceMinister::CONFIG_PM_CRON_TOKEN)) {
            die('Wrong Token...');
        }
        if ($action == 'propagate') {
            $action = sprintf('%s-%s-%s', $action, Tools::getValue('scope'), Tools::getValue('entity'));
            $field = Tools::getValue('field');
        }

        switch ($action) {
            case 'set':
                $fields = array(
                    'id_product',
                    'id_product_attribute',
                    'id_lang',
                    'force',
                    'disable',
                    'price',
                    'text',
                    'repricing_min',
                    'repricing_max'
                );
                // id_product,id_product_attribute,id_lang,force,disable,price,text

                $product_options = array();
                $substitutions = array_flip(self::$sustitute);

                foreach ($fields as $field) {
                    if (array_key_exists($field, $substitutions)) {
                        $form_field = $substitutions[$field];
                    } else {
                        $form_field = $field;
                    }

                    $value = Tools::getValue($form_field);

                    if (Tools::strlen($value)) {
                        $product_options[$field] = $value;
                    } else {
                        $product_options[$field] = null;
                    }
                }

                $result = PriceMinisterProductExt::setProductOptions($id_product, $id_lang, $product_options, $id_product_attribute ? $id_product_attribute : null);

                if ($result) {
                    PriceMinisterProductExt::updateProductDate($id_product);
                } else {
                    $pass = false;
                }

                if (!$pass) {
                    echo sprintf('%s(%d): %s', basename(__FILE__), __LINE__, $this->l('Unable to save parameters...'));
                }

                break;

            case 'delete-pm':
                $result = PriceMinisterProductExt::deleteProductOptions($id_product, $id_lang, $id_product_attribute ? $id_product_attribute : null);

                if ($result) {
                    PriceMinisterProductExt::updateProductDate($id_product);
                } else {
                    $pass = false;
                }

                if (!$pass) {
                    echo sprintf('%s(%d): %s', basename(__FILE__), __LINE__, $this->l('Unable to save parameters...'));
                }

                break;

            case 'update-field':
                $field = Tools::getValue('field');
                $value = Tools::getValue('value');
                $pass = false;

                switch ($field) {
                    case 'ean13':
                    case 'upc':
                        if (Tools::strlen($value) && !is_numeric($value)) {
                            die;
                        }
                    //TODO: DO NOT BREAK HERE

                    // no break
                    case 'reference':
                        $sql = null;

                        if ($id_product_attribute) {
                            $sql = 'UPDATE `'._DB_PREFIX_.'product_attribute` set `'.pSQL($field).'` = "'.pSQL($value).'" WHERE `id_product`='.(int)$id_product.' and `id_product_attribute` = '.(int)$id_product_attribute;
                        } elseif ($id_product) {
                            $sql = 'UPDATE `'._DB_PREFIX_.'product` set `'.pSQL($field).'` = "'.pSQL($value).'" WHERE `id_product`='.(int)$id_product;
                        }

                        if ($sql) {
                            if (Db::getInstance()->Execute($sql)) {
                                $pass = true;
                            }
                        }
                        break;
                }

                if (!$pass) {
                    echo sprintf('%s(%d): %s', basename(__FILE__), __LINE__, $this->l('Unable to save parameters...'));
                }

                break;

            case 'propagate-cat-pm':
                $pass = true;
                $value = Tools::getValue($field);

                if (!PriceMinisterProductExt::propagateProductOptionToCategory($id_product, $id_lang, $id_category, $field, $value)) {
                    $pass = false;
                }

                if (!$pass) {
                    echo sprintf('%s(%d): %s', basename(__FILE__), __LINE__, $this->l('Unable to save parameters...'));
                }

                break;

            case 'propagate-shop-pm':
                $pass = true;
                $value = Tools::getValue($field);

                if (!PriceMinisterProductExt::propagateProductOptionToShop($id_product, $id_lang, $field, $value)) {
                    $pass = false;
                }

                if (!$pass) {
                    echo sprintf('%s(%d): %s', basename(__FILE__), __LINE__, $this->l('Unable to save parameters...'));
                }

                break;

            case 'propagate-manufacturer-pm':
                $pass = true;
                $value = Tools::getValue($field);

                if (!PriceMinisterProductExt::propagateProductOptionToManufacturer($id_product, $id_lang, $id_manufacturer, $field, $value)) {
                    $pass = false;
                }

                if (!$pass) {
                    echo sprintf('%s(%d): %s', basename(__FILE__), __LINE__, $this->l('Unable to save parameters...'));
                }

                break;

            default:
                die('Unknown Action');
                break;
        }

        if ($callback && Tools::strlen($callback) > 2) {
            $json = Tools::jsonEncode(array('error' => !$pass, 'output' => ob_get_clean()));
            die($callback.'('.$json.')');
        } else {
            echo $this->l('Parameters successfully saved');
        }
    }
}

$ext = new Product_Ext_ManagerJSON();
$ext->doIt();
