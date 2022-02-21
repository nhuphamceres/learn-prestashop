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
require_once(dirname(__FILE__).'/../classes/cdiscount.webservice.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.context.class.php');



class CDiscountGetOfferList extends CDiscount
{
    public function __construct()
    {
        parent::__construct();

        CDiscountContext::restore($this->context);
    }

    public function dispatch()
    {
        $action = Tools::getValue('action');
        $cdtoken = Tools::getValue('cdtoken');

        // Check Access Tokens
        $token = parent::decode(Configuration::get(parent::KEY.'_PS_TOKEN'));

        if ($cdtoken != $token) {
            die(Tools::displayError($this->l('Wrong Token')));
        }

        switch ($action) {
            default:
                $this->test();
        }
    }

    public function test()
    {
        $error = false;
        $msg = null;
        $offer_list = array();
        $id_shop = null;

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            if (Shop::isFeatureActive()) {
                $id_shop = (int)Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
            } else {
                $id_shop = null;
            }

            if ($id_shop) {
                Shop::setContext(Shop::CONTEXT_SHOP, $id_shop);
            }
        }
        if (!CommonTools::tableExists(_DB_PREFIX_.self::TABLE_CDISCOUNT_OFFERS)) {
            echo Tools::displayError($this->l('CDiscount offers tables doesn\'t exist yet, please go the module configuration and click on save'));
            die;
        }
        $username = Configuration::get(parent::KEY.'_USERNAME');
        $password = Configuration::get(parent::KEY.'_PASSWORD');
        $debug = parent::$debug_mode;   // TODO Validation: Yes, it exists
        $production = !(Configuration::get(parent::KEY.'_PREPRODUCTION') ? true : false);
        $demo = (bool)Configuration::get(parent::KEY.'_DEV_MODE');
        $records = 0;
        $update = false;
        $sql = 'SELECT `date` FROM `'._DB_PREFIX_.self::TABLE_CDISCOUNT_OFFERS.'`';

        $result = Db::getInstance()->getValue($sql);

        if (Tools::strlen($result)) {
            $timestamp = strtotime($result);
            if ($timestamp < time() - (3600 * 4)) {
                $update = true;
            } else {
                $update = false;
                $msg = $this->l('Offers up to date');
            }
        } else {
            $update = true;
        }
        $page = 1;

        if ($update) {
            $marketplace = new CDiscountWebservice($username, $password, $production, $debug, $demo);
            $marketplace->token = CDiscountTools::auth();

            if ($marketplace->token) {
                $params = array();
                $continue = true;
                $i = 0;

                while ($continue) {
                    $result = $marketplace->GetOfferListPaginated($params, $page++);

                    if (!$result instanceof SimpleXMLElement) {
                        die(Tools::displayError($this->l('Failed to retrieve offer list')));
                    }
                    $xpath_result = $result->xpath('//OfferList/Offer');

                    if (is_array($xpath_result) && count($xpath_result)) {
                        foreach ($xpath_result as $offer) {
                            $seller_reference = trim((string)$offer->SellerProductId);
                            $cdiscount_reference = trim((string)$offer->ProductId);

                            $offer_list[$seller_reference] = $cdiscount_reference;
                        }
                    } elseif ($i == 0) {
                        $msg = $this->l('No offers on CDiscount');
                        $continue = false;
                    } else {
                        $continue = false;
                    }
                    $i++;
                }


                if (is_array($offer_list) && count($offer_list)) {
                    $sql = 'TRUNCATE `'._DB_PREFIX_.self::TABLE_CDISCOUNT_OFFERS.'`';
                    $result = Db::getInstance()->Execute($sql);

                    foreach ($offer_list as $sku => $cdiscount_sku) {
                        if (CDiscountTools::validateSKU($sku)) {
                            $sql = 'INSERT INTO `'._DB_PREFIX_.self::TABLE_CDISCOUNT_OFFERS.'` VALUES("'.pSQL($sku).'", "'.pSQL($cdiscount_sku).'", "'.date("Y/m/d H:i:s").'")';

                            $result = Db::getInstance()->Execute($sql);

                            if ($result) {
                                $records++;
                            }
                        }
                    }
                }
            } else {
                $error = true;
                $msg = $this->l('Unable to connect to CDiscount');
            }
        }
        if ($records && !$error) {
            $msg = sprintf('%d %s', $records, $this->l('offers updated from CDiscount'));
        }

        $json = Tools::jsonEncode(array('error' => $error, 'msg' => $msg, 'output' => ob_get_clean()));
        // jQuery Output or PHP Output
        if (($callback = Tools::getValue('callback'))) {
            // jquery
            echo (string)$callback.'('.$json.')';
        } else {
            // cron
            return ($json);
        }
        die;
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }
}

$marketplace = new CDiscountGetOfferList;
$marketplace->dispatch();
