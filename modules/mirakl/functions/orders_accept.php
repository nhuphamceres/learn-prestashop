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

require_once(dirname(__FILE__).'/../classes/context.class.php');
require_once(dirname(__FILE__).'/../classes/tools.class.php');
require_once(dirname(__FILE__).'/../classes/product.class.php');
require_once(dirname(__FILE__).'/../classes/cart.class.php');
require_once(dirname(__FILE__).'/../classes/order.class.php');
require_once(dirname(__FILE__).'/../classes/orderhistory.class.php');
require_once(dirname(__FILE__).'/../classes/mirakl.api.orders.php');

// Sep-25-2018: Use only 1 main class for all marketplaces

class MiraklOrdersAccept extends Mirakl
{
    private $errors = array();
    private static $brlf;

    /**
     * Mirakl auth
     */
    private $username;
    private $password;
    private $debug;
    private $preproduction;
    private $export;

    public function __construct()
    {
        parent::__construct();

        MiraklContext::restore($this->context);

        $debug_mode = (bool)Mirakl::getConfig(Mirakl::CONFIG_DEBUG);
        $this->debug = (int)$debug_mode ? true : false;

        if ($this->debug || Tools::getValue('debug')) {
            ini_set('display_errors', 'on');
            error_reporting(E_ALL | E_STRICT);
        }
        $this->export = $this->path.'export/';
        self::$brlf = nl2br("\n")."\n";
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return parent::l($string, basename(__FILE__, '.php'), $id_lang);
    }

    public function dispatch()
    {
        $metoken = Tools::getValue('metoken');
        $action = Tools::getValue('action');

        ob_start();
        //  Check Access Tokens
        $token = Mirakl::getConfigGlobalMarketplace(Mirakl::CONFIG_PS_TOKEN, true);

        if ($metoken != $token) {
            $this->dieOnError($this->l('Wrong Token'));
        }

        $this->username = Mirakl::getConfig(Mirakl::CONFIG_USERNAME);
        $this->password = Mirakl::getConfig(Mirakl::CONFIG_API_KEY);
        $this->debug = (bool)Mirakl::getConfig(Mirakl::CONFIG_DEBUG);
        $this->preproduction = (bool)Mirakl::getConfig(Mirakl::CONFIG_PREPRODUCTION);

        if ($this->debug) {
            ini_set('display_errors', 'on');
            if (!defined('_PS_DEBUG_SQL_')) {
                define('_PS_DEBUG_SQL_', true);
            }
            error_reporting(E_ALL | E_STRICT);
        }

        switch ($action) {
            case 'orders':
                $this->listOrders();
                break;
            case 'accept':
                $this->acceptOrders();
                break;
            case 'cron':
                // todo: Apply for all actions. Override context by new implementation
                MiraklContext::set();
                if ($this->listOrders(true) > 0) {
                    $this->acceptOrders(true);
                }
                break;
        }
    }

    private function dieOnError($msg)
    {
        echo $msg;
        $output = ob_get_clean().self::$brlf;
        $json = Tools::jsonEncode(array('error' => true, 'output' => $output));

        // jQuery Output or PHP Output
        if ($callback = Tools::getValue('callback')) { // jquery
            echo (string)$callback.'('.$json.')';
        } else { // cron
            return $json;
        }
        die;
    }

    private function acceptOrders($cron = false)
    {
        $console = null;
        $error = false;
        $count = 0;
        $output = array();
        $id_shop = 1;

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_shop = $this->context->shop->id;
        }

        $mirakl_params = self::$marketplace_params;
        $mirakl_params['debug'] = $this->debug;
        $mirakl_params['api_key'] = Mirakl::getConfig(Mirakl::CONFIG_API_KEY);

        $mirakl = new MiraklApiOrders($mirakl_params);

        // Modif YB - implementation cron pour la validation des commandes
        if ($cron) {
            $selected_orders = array();
            $item_list = array();
            $callback = '';
        } else {
            $selected_orders = Tools::getValue('selected_orders');
            $item_list = Tools::getValue('item_list');
            $callback = Tools::getValue('callback');
        }

        // Modif YB - implementation cron pour la validation des commandes
        if (!$cron && !count($selected_orders)) {
            $this->errors[] = sprintf('%s(%s): %s', basename(__FILE__), __LINE__, $this->l('No selected orders')).self::$brlf;

            return false;
        }

        $orders = MiraklTools::unSerialize(Tools::file_get_contents($this->export.'/orders.out'), true);

        if (!$orders) {
            $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Unable to read orders...')).self::$brlf;
            $error = true;
        }

        if ($cron) {
            if (is_array($orders) && count($orders)) {
                foreach ($orders as $oid => $order) {
                    $selected_orders[] = (string)$oid;

                    foreach ($order['order_lines'] as $product) {
                        $item_list[$oid][$product['product_sku']] = true;
                    }
                }
            } else {
                $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('No pending orders..')).self::$brlf;
                $error = true;
            }
        }
        $accept_order = array();

        foreach (array_values($selected_orders) as $oid) {
            $oid = (string)$oid;

            if (!isset($orders[$oid]) || !isset($orders[$oid]['order_id'])) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable to read order ID'), $oid).self::$brlf;

                $error = true;
                continue;
            }
            $order = $orders[$oid];

            if (MiraklOrder::checkByMpId($oid, $id_shop)) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('This order was already imported'), $oid).self::$brlf;
                $error = true;
                continue;
            }

            $ordered_count = 0;
            $selected_count = 0;

            $order_info = array();
            $order_info['order_lines'] = array();

            if (is_array($order['order_lines'])) {
                foreach ($order['order_lines'] as $ordered_item) {
                    $ordered_count++;
                    $order_info['order_lines'][] = array(
                        'accepted' => true,
                        'id' => $ordered_item['order_line_id']
                    );
                }
            }

            if (is_array($item_list[$oid])) {
                $selected_count += count($item_list[$oid]);
            }

            if (!$ordered_count || !$selected_count || $ordered_count != $selected_count) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('This order is not completly fulfilled'), $oid).self::$brlf;

                if ($this->debug) {
                    printf('%s(#%d): %s', basename(__FILE__), __LINE__, print_r($order, true));
                }

                $error = true;
                continue;
            }
            $order_id = $orders[$oid]['order_id'];
            $response = $mirakl->accept($order_id, $order_info);

            if (file_put_contents(dirname(__FILE__).'/../xml/accept_order.'.$oid.'.xml', $response) === false) {
                $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Unable to save API response')).self::$brlf;
                $error = true;
            }

            if (Tools::strlen($response)) {
                $xml = @simplexml_load_string($response);

                if (!$xml instanceof SimpleXMLElement) {
                    $this->errors[] = sprintf('%s: %s'.self::$brlf, $this->l('Remote service return unexpected content'), nl2br(print_r($response, true)));
                    $error = true;
                    continue;
                }
                if (isset($xml->error_code) && (int)$xml->error_code) {
                    $error = true;
                    $error_str = $xml->error;

                    if (isset($xml->service_code) && !empty($xml->service_code)) {
                        $error_str .= ' - '.MiraklTools::bold('Service Code').': '.(string)$xml->service_code.self::$brlf;
                    }

                    if (isset($xml->error_details)) {
                        $error_str .= ' - '.MiraklTools::bold('Error Message').': '.(string)$xml->error_details.self::$brlf;
                    }

                    $this->errors[] = sprintf(MiraklTools::bold('Webservice Error').': %s'.self::$brlf, $error_str);
                    continue;
                } else {
                    $this->errors[] = sprintf('%s - "%s"'.self::$brlf, $this->l('Remote service provided a wrong response'), nl2br(print_r($response, true)));
                    $error = true;
                    continue;
                }
            } elseif (!$error) {
                $output[] = html_entity_decode('&lt;p&gt;&lt;img src="'.$this->images.'accept.png" alt="" /&gt;&nbsp;')
                    .sprintf($this->l('Order ID %s Successfully Accepted').self::$brlf, MiraklTools::bold($oid))
                    .html_entity_decode('&lt;/p&gt;');
            }


            $count++;
        } // foreach orders

        if (!$cron) {
            $console = ob_get_clean();
        } else {
            @ob_end_flush();
        }

        $json = Tools::jsonEncode(
            array(
                'stdout' => $console,
                'output' => $output,
                'error' => $error,
                'count' => $count,
                'orders' => $accept_order,
                'errors' => $this->errors
            )
        );

        if (!$cron) {
            echo (string)$callback.'('.$json.')';
            die;
        } else {
            echo $json;

            return true;
        }
    }

    private function listOrders($cron = false)
    {
        $action = null;
        $output = array();
        $callback = Tools::getValue('callback');

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $id_shop = 1;
            $id_warehouse = null;
        } else {
            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                $id_warehouse = Mirakl::getConfig(Mirakl::CONFIG_WAREHOUSE);
            }

            if (empty($id_warehouse) || !is_numeric($id_warehouse)) {
                $id_warehouse = null;
            }

            if (Shop::isFeatureActive()) {
                $id_shop = (int)Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
            } else {
                $id_shop = 1;
            }
        }

        $mirakl_params = self::$marketplace_params;
        $mirakl_params['debug'] = $this->debug;
        $mirakl_params['api_key'] = Mirakl::getConfig(Mirakl::CONFIG_API_KEY);

        $mirakl = new MiraklApiOrders($mirakl_params);

        //states must be according to=> "order_line_state":
        //'STAGING'
        //'WAITING_ACCEPTANCE'
        //'WAITING_DEBIT' (-)
        //'WAITING_DEBIT_PAYMENT' (-)
        //'SHIPPING'(-)
        //'SHIPPED' (-)
        //'RECEIVED' (-)
        //'CLOSED' (-)
        //'REFUSED' (-)
        //'CANCELED' (-)
        //'INCIDENT_OPEN' (-)
        //'INCIDENT_CLOSED' (-)
        //'WAITING_REFUND' (-)
        //'WAITING_REFUND_PAYMENT' (-)
        //'REFUNDED' (-)
        $params = array(
            'order_state_codes' => MiraklApiOrders::STATUS_WAITING_ACCEPTANCE
        );
        $response = $mirakl->orders($params);

        $total_count = 0;
        $error = false;

        if (empty($response)) {
            if ($this->debug) {
                printf('%s(#%d): %s - %s', basename(__FILE__), __LINE__, $this->l('Web/Service Error'), print_r($response, true));
            }

            $this->errors[] = sprintf('%s', $this->l('Web/Service Error')).self::$brlf;
            $error = true;
        } else {
            $xml = simplexml_load_string($response);

            if (!$xml instanceof SimpleXMLElement) {
                if ($this->debug) {
                    printf('%s(#%d): %s', basename(__FILE__), __LINE__, print_r($response, true));
                }

                $this->errors[] = sprintf('%s', $this->l('XML Error')).self::$brlf;
                $error = true;
                $result = array();
            } else {
                $result = $xml->xpath('//mirakl_orders/orders');
                $xcount = $xml->xpath('//mirakl_orders/total_count');

                if (is_array($xcount) && count($xcount)) {
                    $total_count = (int)$xcount[0];
                } else {
                    $total_count = 0;
                }
            }
        }

        if (!$total_count) {
            $this->errors[] = sprintf('%s', $this->l('No new order to import')).self::$brlf;
            $error = true;
        }

        $orders = array();
        $order_list = array();

        foreach ($result as $order) {
            $oid = (string)$order->commercial_id;
            $order_datetime = MiraklTools::displayDate(date('Y-m-d H:i:s', strtotime($order->created_date)), $this->id_lang, true);
            $order_name = (string)$order->customer->firstname.' '.(string)$order->customer->lastname;

            if (MiraklOrder::checkByMpId($oid, $id_shop)) {
                $disabled = ' disabled="disabled"';
            } else {
                $disabled = '';
            }

            $products_row = 0;
            $order_detail = array();

            if (isset($order->order_lines) && $order->order_lines instanceof SimpleXMLElement) {
                foreach ($order->order_lines as $orderlist) {
                    if (isset($orderlist) && !empty($orderlist)) {
                        $products_row += 1;
                    }

                    $id = (string)MiraklProduct::getProductBySKU($orderlist->offer_sku, $id_shop);

                    if ($id === null) {
                        $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable to retrieve the SKU for this product'), $orderlist->product_sku).self::$brlf;
                        $error = true;
                        continue;
                    }

                    // Product With Combination
                    if (strpos($id, '_')) {
                        $split_combination = explode('_', $id);
                        $id_product = (int)$split_combination[0];
                        $id_product_attribute = (int)$split_combination[1];
                    } else {
                        $id_product = (int)$id;
                        $id_product_attribute = false;
                    }

                    /*if (!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                        $quantity = 999;
                    } else*/
                    if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                        $quantity = Product::getRealQuantity(
                            $id_product,
                            $id_product_attribute ? $id_product_attribute : null,
                            $id_warehouse,
                            $id_shop
                        );
                    } else {
                        $quantity = (int)MiraklProduct::getProductQuantity(
                            $id_product,
                            $id_product_attribute
                        );
                    }

                    $order_detail[] = array(
                        'ps_qty' => $quantity,
                        'order_qty' => $orderlist->quantity,
                        'offer_sku' => $orderlist->offer_sku,
                        'products_row' => $products_row,
                        'offer_id' => $orderlist->offer_id,
                        'product_title' => $orderlist->product_title,
                        'shipping_price' => $orderlist->shipping_price,
                        'price' => $orderlist->price
                    );
                }
            }

            $this->context->smarty->assign(array(
                'image_path' => $this->images,
                'disabled' => $disabled,
                'oid' => $oid,
                'order_datetime' => $order_datetime,
                'order_name' => $order_name,
                'shipping_price' => $order->shipping_price,
                'total_price' => $order->total_price,
                'details' => $order_detail
            ));
            $orders[$oid] = $this->context->smarty->fetch($this->path.'views/templates/admin/orders/order_accept_list.tpl');

            $products = $order->xpath('order_lines');

            if (!is_array($products)) {
                $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('No products in this order...')).self::$brlf;
                $error = true;
            }
            $order_list[$oid] = (array)MiraklTools::xml2array($order);

            unset($order_list[$oid]['order_lines']);

            $order_list[$oid]['order_lines'] = array();

            foreach ($products as $product) {
                $product_array = MiraklTools::xml2array($product);
                $order_list[$oid]['order_lines'][] = $product_array;
            }

            if (!$order_list[$oid]) {
                $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Unable to convert xml to array...')).self::$brlf;
                $error = true;
            }

            if (!$order_list[$oid]) {
                $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Unable to convert xml to array...')).self::$brlf;
                $error = true;
            }
        }

        // todo: Migrate to json later
        file_put_contents($this->export . '/orders.json', json_encode($order_list));
        if (file_put_contents($this->export.'/orders.out', serialize($order_list)) === false) {
            $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Unable to save orders...')).self::$brlf;
            $error = true;
        }

        if (!$error) {
            if (count($orders) == 1) {
                $output[] = sprintf($this->l('One pending order'));
            } elseif (count($orders) >= 1) {
                $output[] = sprintf($this->l('%d pending orders'), count($orders));
            } else {
                $output[] = sprintf($this->l('No pending orders'));
            }
        } // if ! error

        $console = ob_get_clean();

        $json = Tools::jsonEncode(
            array(
                'stdout' => $console,
                'output' => $output,
                'error' => $error,
                'orders' => $orders,
                'errors' => $this->errors
            )
        );

        if (!$cron) {
            echo (string)$callback.'('.$json.')';
            die;
        } else {
            echo $json;

            return count($orders);
        }
    }
}

$mirakl_orders_accept = new MiraklOrdersAccept();
$mirakl_orders_accept->dispatch();
