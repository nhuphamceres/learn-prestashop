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

ini_set('error_reporting', 1);
error_reporting(E_ALL|E_STRICT);

$file = new SplFileInfo($_SERVER['SCRIPT_FILENAME']);

require_once dirname(dirname(dirname($file->getPath()))).'/config/config.inc.php';
require_once dirname(dirname(dirname($file->getPath()))).'/init.php';

require_once(dirname(__FILE__).'/../priceminister.php');
require_once(dirname(__FILE__).'/../classes/priceminister.api.webservices.php');
require_once(dirname(__FILE__).'/../classes/priceminister.sales.api.php');
require_once(dirname(__FILE__).'/../classes/priceminister.tools.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.order.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.product.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.context.class.php');

class PriceMinisterOrderList extends PriceMinister
{

    public static $errors = array();
    public static $warnings = array();
    public static $messages = array();
    public static $orders = array();
    public static $encoded_xml = null;

    public function __construct()
    {
        parent::__construct();

        PriceMinisterContext::restore($this->context);

        // Set the correct shop context in the global context
        // Usefull for function to get image or stock for exemple
        if ($this->context->shop && Validate::isLoadedObject($this->context->shop)) {
            Context::getContext()->shop = $this->context->shop;
        }

        $this->credentials = parent::getConfig(PriceMinister::CONFIG_PM_CREDENTIALS);
        $this->parameters = parent::getConfig(PriceMinister::CONFIG_PM_PARAMETERS);
    }

    public static function JSON_Display_Exit()
    {
        if (!count(PriceMinisterOrderList::$orders)) {
            $result = trim(ob_get_clean());
            if (trim($result)) {
                PriceMinisterOrderList::$warnings[] = trim($result);
            }
        } else {
            $result = trim(ob_get_clean());
            if (trim($result)) {
                PriceMinisterOrderList::$messages[] = trim($result);
            }
        }

        $json = Tools::jsonEncode(
            array(
                'order' => count(PriceMinisterOrderList::$orders),
                'orders' => PriceMinisterOrderList::$orders,
                'count' => count(PriceMinisterOrderList::$orders),
                'error' => (count(PriceMinisterOrderList::$errors) ? true : false),
                'errors' => PriceMinisterOrderList::$errors,
                'warning' => (count(PriceMinisterOrderList::$warnings) ? true : false),
                'warnings' => PriceMinisterOrderList::$warnings,
                'message' => count(PriceMinisterOrderList::$messages),
                'messages' => PriceMinisterOrderList::$messages,
                'encoded_xml' => PriceMinisterOrderList::$encoded_xml
            )
        );

        if (($callback = Tools::getValue('callback'))) { // jquery
            echo (string)$callback.'('.$json.')';
        } else { // cron
            echo $json;
        }
    }

    public function Dispatch()
    {
        ob_start();
        register_shutdown_function(array('PriceMinisterOrderList', 'JSON_Display_Exit'));

        //  Check Access Tokens
        //
        $pm_token = Configuration::get(PriceMinister::CONFIG_PM_CRON_TOKEN);

        if ($pm_token != Tools::getValue('pm_token')) {
            self::$errors[] = $this->l('Wrong Token');
            die;
        }

        $cron = Tools::getValue('cron', 0);

        switch (Tools::getValue('action')) {
            case 'list':
                if ($cron) {
                    $this->ListOrders(Tools::getValue('method'));
                } else {
                    $this->ListOrders(Tools::getValue('method'));
                }

                break;
        }
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    public function ListOrders($method, $cron = false)
    {
        $dateStart = Tools::getValue('datepickerFrom');
        $dateEnd = Tools::getValue('datepickerTo');
        $token_orders = Tools::getValue('token_orders');
        $orders_statuses = Tools::getValue('orders-statuses');

        // PM Configuration
        //
        $config = PriceMinisterTools::Auth();

        if (!is_array($config)) {
            self::$errors[] = $this->l('Unexpected error, missing configuration');
            die;
        }

        // Orders Output Settings
        //
        $config['output'] = 'xml';

        // PM Settings
        //
        $params = array();
        $params['debug'] = $this->debug;
        $params['field'] = '';

        $pmSales = new PM_Sales($config);

        if (Validate::isDate($dateStart)) {
            $params['purchasedate'] = $dateStart;
        }

        $import_method = $this->parameters['import_method'];

        if ($this->credentials['test']) {
            $record = 0;
            # Record
            if (isset($record) && $record) {
                if ($method == 'accept') {
                    $result_xml_tag = 'getnewsalesresult';
                    $sales = $pmSales->getnewsales($params, false);
                } else {
                    $result_xml_tag = 'getcurrentsales';
                    $sales = $pmSales->getcurrentsales($params, false);
                }

                $sales->asXML('orders'.$method.'.out');
                self::$messages[] = 'Orders saved to file';
                die;
            } else {
                if ($method == 'accept') {
                    $result_xml_tag = 'getnewsalesresult';
                } else {
                    $result_xml_tag = 'getcurrentsalesresult';
                }

                $xml_string = PriceMinisterTools::file_get_contents(dirname(__FILE__).'/orders'.$method.'.xml');
                $sales = simplexml_load_string($xml_string, null, LIBXML_NOCDATA);
            }
        } elseif ($method == 'accept') {
            //
            $result_xml_tag = 'getnewsalesresult';
            $sales = $pmSales->getnewsales($params, false);
        } elseif ($method == 'import') {
            //
            $result_xml_tag = 'getcurrentsalesresult';
            $sales = $pmSales->getcurrentsales($params, false);
        } else {
            self::$errors[] = sprintf('%s(#%d): Unknown method', basename(__FILE__), __LINE__);
            die;
        }

        if (!$sales instanceof SimpleXMLElement) {
            self::$errors[] = $this->l('Unable to connect to RakutenFrance');
            die;
        }

        if (isset($sales->error->code) && !empty($sales->error->code)) {
            self::$errors[] = sprintf('%s: %s', $sales->error->code, $sales->error->message);
            die;
        }

        if (!isset($sales->response->sellerid) || empty($sales->response->sellerid)) {
            self::$errors[] = sprintf('<b>%s</b><br /><pre>%s</pre>', $this->l('RakutenFrance returned an unexpected content'), nl2br(print_r($sales, true)));
            die;
        }

        $sales_array = $sales->xpath('/'.$result_xml_tag.'/response/sales/sale');

        if (is_array($sales_array) && count($sales_array)) {
            PriceMinisterOrderList::$encoded_xml = PriceMinisterTools::base64Encode($sales->asXML());

            foreach ($sales_array as $sale) {
                // Convert to JSON
                $json = Tools::jsonEncode($sale);

                // Revert to Array
                $order = Tools::jsonDecode($json, true);

                // skip imported order
                if (PriceMinisterOrder::checkByMpId($order['purchaseid'])
                    && $orders_statuses != RakutenConstant::OI_STATUS_ALL
                ) {
                    continue;
                }

                $date_order = sprintf('%s-%s-%s', Tools::substr($order['purchasedate'], 6, 4), Tools::substr($order['purchasedate'], 3, 2), Tools::substr($order['purchasedate'], 0, 2));
                $time_order = sprintf('%s:%s:00', Tools::substr($order['purchasedate'], 11, 2), Tools::substr($order['purchasedate'], 14, 2));

                if (!$this->credentials['test'] && Validate::isDate($dateStart) && Validate::isDate($dateEnd) && $date_order < $dateStart || $date_order > $dateEnd) {
                    continue;
                } elseif (!$this->credentials['test'] && Validate::isDate($dateStart) && $date_order < $dateStart) {
                    continue;
                } elseif (!$this->credentials['test'] && Validate::isDate($dateEnd) && $date_order > $dateEnd) {
                    continue;
                }

                if (isset($order['deliveryinformation']['deliveryaddress'])) {
                    $order['deliveryinformation']['deliveryaddress'] = array_filter($order['deliveryinformation']['deliveryaddress']);
                    if (!isset($order['address'])) {
                        $order['address'] = $order['deliveryinformation']['deliveryaddress'];
                    }
                }

                if (isset($order['deliveryinformation']['billingaddress'])) {
                    $order['deliveryinformation']['billingaddress'] = array_filter($order['deliveryinformation']['billingaddress']);
                    if (!isset($order['address'])) {
                        $order['address'] = $order['deliveryinformation']['billingaddress'];
                    }
                }

                if (isset($order['address'])) {
                    $order['customer'] = sprintf('%s %s', Tools::ucfirst($order['address']['firstname']), Tools::ucfirst($order['address']['lastname']));
                } elseif (isset($order['deliveryinformation']['purchasebuyerlogin'])) {
                    $order['customer'] = $order['deliveryinformation']['purchasebuyerlogin'];
                } else {
                    $order['customer'] = $order['purchaseid'];
                }

                $order['amount'] = 0;

                if (!isset($order['items']['item'][0])) {
                    $items = array(0 => $order['items']['item']);
                } else {
                    $items = $order['items']['item'];
                }
                $order['items']['item'] = $items;

                $pass = true;
                $item_currency = null;

                foreach ($items as $key => $item) {
                    $order['items']['item'][$key]['importable'] = true;
                    $order['items']['item'][$key]['exists'] = true;

                    $order['amount'] += $item['price']['amount'];
                    $item_currency = $item['price']['currency'];
                    $sku = $item['sku'];

                    $id_product = null;
                    $id_product_attribute = null;
                    $identifier = null;
                    $quantity = null;

                    if ($this->credentials['test']) {
                        $identifier = PriceMinisterProductExt::getProductBySKUDemo();
                    } else {
                        if ($import_method == 'SKU') {
                            $product_check = PriceMinisterProductExt::checkProduct($sku, $this->context->shop, $this->debug);

                            if (!$product_check) {
                                if ($this->debug) {
                                    self::$errors[] = sprintf('%s (%s)', $this->l('Unable to retrieve the SKU for this product'), $sku);
                                }
                                $order['items']['item'][$key]['importable'] = false;
                                $order['items']['item'][$key]['exists'] = false;
                            } elseif ($product_check > 1) {
                                self::$errors[] = sprintf('%s (%s)', $this->l('Can\'t import a duplicate Reference/SKU'), $sku);
                                $order['items']['item'][$key]['importable'] = false;
                            } else {
                                $identifier = PriceMinisterProductExt::getProductBySKU($sku);

                                if ($identifier == null) {
                                    if ($this->debug) {
                                        self::$errors[] = sprintf('%s (%s)', $this->l('Unable to retrieve the SKU for this product'), $sku);
                                    }
                                    $order['items']['item'][$key]['importable'] = false;
                                    $order['items']['item'][$key]['exists'] = false;
                                }
                            }
                        } else {
                            $identifier = $sku;
                        }
                    }

                    if ($import_method == 'ADVANCED') {
                        $split_combination = explode('c', $identifier);

                        $id_product = (int)Tools::substr($split_combination[0], 1); // Remove the 'p'
                        $id_product_attribute = (int)$split_combination[1]; // get the c (removed by explode)
                    } elseif (strpos($identifier, '_') !== false) {
                        $split_combination = explode('_', $identifier);

                        $id_product = (int)$split_combination[0];
                        $id_product_attribute = (int)$split_combination[1];
                    } elseif (is_numeric($identifier)) {
                        $id_product = (int)$identifier;
                        $id_product_attribute = false;
                    } elseif ($import_method == 'ID') {
                        self::$errors[] = sprintf('%s (%s)', $this->l('Incorrect format for product ID'), $identifier);
                    }

                    if ($id_product) {
                        $product = new Product($id_product, false, $this->id_lang);

                        if (!Validate::isLoadedObject($product)) {
                            self::$errors[] = sprintf('%s (%s - %s)', $this->l('Unable to find product'), $sku, $identifier);
                        } else {
                            if ($this->credentials['test']) {
                                $order['items']['item'][$key]['headline'] = $product->name;
                            }

                            if ((bool)Configuration::get('PS_STOCK_MANAGEMENT')) {
                                if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                                    // Looks for the product id_shop, if many, take the first one
                                    $id_shop = array_map('intval', PriceMinisterTools::arrayColumn(
                                        Product::getShopsByProduct($id_product),
                                        'id_shop'
                                    ));
                                    $id_shop = reset($id_shop);

                                    $quantity = (int)Product::getRealQuantity(
                                        $id_product,
                                        $id_product_attribute && $id_product_attribute != $id_product ?
                                            $id_product_attribute : null,
                                        (int)$this->parameters['warehouse'],
                                        $id_shop
                                    );
                                } else {
                                    $quantity = (int)PriceMinisterProductExt::getProductQuantity($id_product, $id_product_attribute);
                                }
                            } else {
                                $quantity = true;
                            }
                        }
                    } else {
                        $order['items']['item'][$key]['importable'] = false;
                    }

                    if (!$order['items']['item'][$key]['importable']) {
                        $pass = false;
                    }

                    $order['items']['item'][$key]['stock'] = $quantity;
                }
                $order['importable'] = $pass;
                $order['amount'] = PriceMinisterTools::displayPrice($order['amount'], (int)Currency::getIdByIsoCode($item_currency));
                $order['quantity'] = count($order['items']['item']);

                if (($mp_order = PriceMinisterOrder::checkByMpId($order['purchaseid']))) {
                    $order['imported'] = true;
                    $order['id_order'] = $mp_order['id_order'];
                    $url = '?tab=AdminOrders&id_order='.$mp_order['id_order'].'&vieworder&token='.$token_orders;
                    $order['link'] = '<a href="'.$url.'" title="" target="_blank" >'.$order['purchaseid'].'('.$mp_order['id_order'].')</a>';
                } else {
                    $order['imported'] = false;
                    $order['id_order'] = null;
                    $order['link'] = $order['purchaseid'];
                }

                self::$orders[$order['purchaseid']] = $order;
            }
            self::$messages[] = sprintf('%d %s', count(self::$orders), $this->l('pending orders'));
            die;
        } else {
            self::$messages[] = sprintf('%s', $this->l('No new orders'));
            die;
        }
    }
}

$orderlist = new PriceMinisterOrderList();
$orderlist->Dispatch();