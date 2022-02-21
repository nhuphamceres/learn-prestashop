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
require_once(dirname(__FILE__).'/../classes/cdiscount.cart.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.order.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.orderhistory.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.webservice.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.context.class.php');

require_once(dirname(__FILE__).'/../common/configuration.class.php');

class CDiscountOrdersAccept extends CDiscount
{
    private $errors = array();
    private $_cr    = "<br />\n";
    // todo: Not used
    private $_conditions = array(
        1 => 'LikeNew',
        2 => 'VeryGoodState',
        3 => 'GoodState',
        4 => 'AverageState',
        5 => 'Refurbished',
        6 => 'New'
    );

    private $username;
    private $password;
    private $production;

    public function __construct()
    {
        parent::__construct();

        $this->debug = Configuration::get(parent::KEY.'_DEBUG');

        if ($this->debug || Tools::getValue('debug')) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }
        $this->export = $this->path.'export/';

        // Dev Mode
        $this->dev_mode = (bool)Configuration::get(parent::KEY.'_DEV_MODE');

        CDiscountContext::restore($this->context);
    }

    public function dispatch()
    {
        $cdtoken = Tools::getValue('cdtoken');
        $action = Tools::getValue('action');

        ob_start();

        if ($this->debug || Tools::getValue('debug')) {
            echo 'Starting in Debug Mode<br />\n';
        }

        //  Check Access Tokens
        //
        $token = parent::decode(Configuration::get(parent::KEY.'_PS_TOKEN'));

        if ($cdtoken != $token) {
            $this->dieOnError($this->l('Wrong Token'));
        }

        if (!$this->dev_mode && !$token = CDiscountTools::auth()) {
            $this->dieOnError($this->l('Authentication Failed'));
        }

        $this->username = Configuration::get(parent::KEY.'_USERNAME');
        $this->password = Configuration::get(parent::KEY.'_PASSWORD');
        $this->debug = (bool)Configuration::get(parent::KEY.'_DEBUG');
        $this->production = !(bool)Configuration::get(parent::KEY.'_PREPRODUCTION');

        if ($this->debug) {
            @ini_set('display_errors', 'on');
            @define('_PS_DEBUG_SQL_', true);
            @error_reporting(E_ALL | E_STRICT);
        }

        switch ($action) {
            case 'orders':
                $this->listOrders();
                break;
            case 'accept':
                $this->acceptOrders();
                break;

            case 'cron':
                // Modif YB - implementation cron pour la validation des commandes
                if ($this->listOrders(true) > 0) {
                    $this->acceptOrders(true);
                }
                break;
        }
    }

    private function dieOnError($msg)
    {
        echo $msg;
        $output = ob_get_clean().$this->_cr;
        $json = Tools::jsonEncode(array('error' => true, 'output' => $output));
        // jQuery Output or PHP Output
        //
        if ($callback = Tools::getValue('callback')) {
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

    private function listOrders($cron = false)
    {
        $cr = $this->_cr;
        $errors = array();
        $error = false;
        $action = null;
        $output = array();
        $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $id_shop = 1;
        $id_shop_group = 1;

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_shop = (int)Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;

            if ($id_shop) {
                $shop = new Shop($id_shop);
                $id_shop_group = $shop->id_shop_group;
            }
        }

        if ($cron) {
            $dateStart = date('Y-m-d\T00:00:00.000', strtotime('now -7 days'));
            $dateEnd = date('Y-m-d\T23:59:59.000');
            $callback = '';
        } else {
            $dateStart = date('Y-m-d\T00:00:00.000', strtotime(Tools::getValue('datepickerFromA')));
            $dateEnd = date('Y-m-d\T23:59:59.000', strtotime(Tools::getValue('datepickerToA')));
            $callback = Tools::getValue('callback');
        }
        $orders_statuses = 'WaitingForSellerAcceptation';

        $id_warehouse = null;

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_warehouse = (int)Configuration::get(parent::KEY.'_WAREHOUSE');
        }

        $import_type = Configuration::get(parent::KEY.'_IMPORT_TYPE');

        $marketplace = new CDiscountWebservice($this->username, $this->password, $this->production, $this->debug, $this->dev_mode);

        // Dev Mode
        if (!$this->dev_mode) {
            $marketplace->token = CDiscountTools::auth();
            if (!$marketplace->token) {
                $this->dieOnError(sprintf('%s(%s): %s', basename(__FILE__), __LINE__, $this->l('Auth failed')).$cr);
                die;
            }
        }
        $type = Cdiscount::IMPORT_BY_SKU ? $this->l('SKU') : $this->l('Prod.Id');

        $clogistique = false;
        $multichannel = false;
        $channels = array();

        $order_table
            = '
        <table class="import-table">
        <thead>
         <tr class="heading">
            <td></td>
            <td>'.$this->l('Row').'</td>
            <td>'.$type.'</td>
            <td>'.$this->l('Name').'</td>
            <td>'.$this->l('SKU').'</td>
            <td>'.$this->l('EAN13').'</td>
            <td>'.$this->l('Qty.').'</td>
            <td align="center">'.$this->l('Ship.').'</td>
            <td align="center">'.$this->l('Price').'</td>
         </tr>
        </thead>
        <tbody>
                %s
        </tbody>
        </table>';

        $result = array();

        if (!$orders_statuses) {
            $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Order status must be selected...')).$cr;
            $error = true;
        } else {
            $params = array(
                $dateStart,
                $dateStart,
                $dateEnd,
                $dateEnd,
                true,
                $orders_statuses == 'CancelledByCustomer',
                $orders_statuses == 'WaitingForSellerAcceptation',
                $orders_statuses == 'AcceptedBySeller',
                $orders_statuses == 'PaymentInProgress',
                $orders_statuses == 'WaitingForShipmentAcceptation',
                $orders_statuses == 'Shipped',
                $orders_statuses == 'RefusedBySeller',
                $orders_statuses == 'AutomaticCancellation',
                $orders_statuses == 'PaymentRefused',
                $orders_statuses == 'ShipmentRefusedBySeller',
                $orders_statuses == 'None',
                $orders_statuses == 'ValidatedFianet',
                $orders_statuses == 'RefusedNoShipment',
                $orders_statuses == 'AvailableOnStore',
                $orders_statuses == 'NonPickedUpByCustomer',
                $orders_statuses == 'PickedUp',
            );

            // Dev Mode
            if ($this->dev_mode) {
                $result = CDiscountTools::file_get_contents('test_orders2.xml');
                $result = $marketplace->response('GetOrderList', $result);
            } else {
                if (!($result = $marketplace->GetOrderList($params))) {
                    $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('GetOrderList failed...')).$cr;
                    $error = true;
                }
            }

            if (!isset($result->OrderList)) {
                $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('No pending order...')).$cr;
                $error = true;
            }

            $orders = array();
            $order_list = array();

            if ($result) {
                if (isset($result->OrderList)) {
                    foreach ($result->OrderList->children() as $order) {
                        $oId = trim((string)$order->OrderNumber);

                        if (CDiscountOrder::checkByMpId($oId)) {
                            $disabled = ' disabled="disabled"';
                            $exists = true;
                        } else {
                            $disabled = '';
                            $exists = false;
                        }

                        if (isset($order->Corporation->CorporationName) && !empty($order->Corporation->CorporationName)) {
                            $channel_id = (int)$order->Corporation->CorporationId;
                            $channel_name = (string)$order->Corporation->CorporationName;

                            $channels[$channel_name] = $channel_id;

                            if (isset(CDiscount::$channel_colors[$channel_id])) {
                                $channel_color = CDiscount::$channel_colors[$channel_id];
                            } else {
                                $channel_color = 'black';
                            }

                            $channel_content = sprintf('<em style="color:%s">%s</em>', $channel_color, $channel_name);
                        }

                        if (isset($order->IsCLogistiqueOrder) && $order->IsCLogistiqueOrder == 'true') {
                            $clogistique = true;
                            $clogistique_content = sprintf('<em style="color:red">%s</em>', $this->l('C Logistique'));
                        } else {
                            $clogistique_content = sprintf('<em>%s</em>', $this->l('Standard'));
                        }
                        $clogistique = true;
                        $orders[$oId]
                            = '<tr class="order-item">'.
                            '<td align="center"><input type="checkbox" name="selected_orders[]" '.$disabled.' id="o-'.$oId.'" value="'.$oId.'" /></td>'.
                            '<td>'.$oId.'</td>'.
                            '<td>'.Tools::substr($order->CreationDate, 0, 10).'</td>'.
                            '<td>'.Tools::substr($order->Customer->LastName, 0, 30).'</td>'.
                            '<td>'.$order->ShippingCode.'</td>'.
                            '<td rel="clogistique" style="display:none">'.$clogistique_content.'</td>'.
                            '<td rel="multichannel" style="display:none">'.$channel_content.'</td>'.
                            '<td>'.$order->ValidationStatus.'</td>'.
                            '<td>'.$order->Status.'</td>'.
                            '<td align="right">'.sprintf('%.02f', $order->InitialTotalAmount).'</td>'.
                            '</tr>';

                        $details = '';

                        if (isset($order->OrderLineList)) {
                            foreach ($order->OrderLineList->children() as $orderList) {
                                $order->Acceptable = true;
                                $quantity = true;

                                if ($this->debug) {
                                    printf('GetOrderList() - OrderLineList: SellerProductId %s', $orderList->SellerProductId).$cr;
                                }

                                if ($orderList->Sku != 'FRAISTRAITEMENT' && $orderList->Sku != 'INTERETBCA') {
                                    if ($import_type == Cdiscount::IMPORT_BY_SKU) {
                                        $id = CDiscountProduct::getProductBySKU($orderList->SellerProductId, $id_shop);

                                        // Dev Mode
                                        if (!$this->dev_mode && $id === null) {
                                            $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable to retrieve the SKU for this product'), $orderList->SellerProductId).$cr;
                                            $error = true;
                                        }
                                    } else {
                                        $id = (string)$orderList->SellerProductId;
                                    }

                                    // Product With Combination
                                    //
                                    if (strpos($id, '_')) {
                                        $split_combination = explode('_', $id);
                                        $id_product = (int)$split_combination[0];
                                        $id_product_attribute = (int)$split_combination[1];
                                    } else {
                                        $id_product = (int)$id;
                                        $id_product_attribute = false;
                                    }

                                    $product = new Product($id_product);
                                    $quantity = 0;

                                    if (!Validate::isLoadedObject($product)) {
                                        $id_product = null;
                                    } else {
                                        if ((bool)Configuration::get('PS_STOCK_MANAGEMENT')) {
                                            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                                                $quantity = (int)Product::getRealQuantity($id_product, $id_product_attribute ? $id_product_attribute : null, $id_warehouse);
                                            } else {
                                                $quantity = (int)CDiscountProduct::getProductQuantity($id_product, $id_product_attribute);
                                            }
                                        } else {
                                            $quantity = true;
                                        }
                                        $outofstock_but_orderable = !$quantity && Product::isAvailableWhenOutOfStock($product->out_of_stock);
                                    }
                                }


                                if (!$id_product) {
                                    $image = '<img src="'.$this->images.'cross.png" style="background:transparent;" title="'.$this->l('Unknown Product').'" />';
                                    $action = '<td style="padding-left:8px;width:20px">'.$image.'</td>';
                                    $order->Acceptable = false;
                                } elseif (!$quantity && !$outofstock_but_orderable) {
                                    $image = '<img src="'.$this->images.'soos.png" style="background:transparent;" title="'.$this->l('Out of Stock').'" />';
                                    $action = '<td style="padding-left:8px;width:20px">'.$image.'</td>';
                                    $order->Acceptable = false;
                                } else {
                                    if ($orderList->Sku == 'FRAISTRAITEMENT' || $orderList->Sku == 'INTERETBCA') {
                                        $str = 'checked="checked" readonly="readonly" onclick="return false;"';
                                    } elseif (!$exists) {
                                        $str = 'name="item_list['.$oId.']['.$orderList->SellerProductId.']" checked="checked"';
                                    } else {
                                        $str = '';
                                    }

                                    // Product List
                                    //
                                    $action = '<td align="center" style="width:20px"><input type="checkbox" id="pl-'.$oId.'-'.$orderList->SellerProductId.'" value="1" '.$str.' /></td>';
                                }

                                $identifier = Cdiscount::IMPORT_BY_SKU ? (string)$orderList->SellerProductId : $id;
                                $details
                                    .= '<tr>'.
                                    $action.
                                    '<td>'.$orderList->RowId.'</td>'.
                                    '<td>'.$identifier.'</td>'.
                                    '<td>'.($orderList->Sku != 'FRAISTRAITEMENT' && $orderList->Sku != 'INTERETBCA' ? CDiscountProduct::getSimpleProductName($id_product, $id_lang) : '').'</td>'.
                                    '<td>'.$orderList->Sku.'</td>'.
                                    '<td>'.$orderList->ProductEan.'</td>'.
                                    '<td>'.$orderList->Quantity.'</td>'.
                                    '<td align="right">'.sprintf('%.02f', $orderList->UnitShippingCharges).'</td>'.
                                    '<td align="right">'.sprintf('%.02f', $orderList->PurchasePrice).'</td>'.
                                    '</tr>'."\n";
                            }
                        }


                        $orders[$oId] .= '<tr><td rel="order-container" colspan="8">'.sprintf($order_table, $details).'</td></tr>';

                        $order_list[$oId] = CDiscountTools::xml2array($order->asXML());

                        if (!$order_list[$oId]) {
                            $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Unable to convert xml to array...')).$cr;
                            $error = true;
                        }
                    }
                }

                if (file_put_contents($this->export.'/orders.out', serialize($order_list)) === false) {
                    $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Unable to save orders...')).$cr;
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
            } // foreach
        } // if result

        $console = ob_get_clean();

        $json = Tools::jsonEncode(array(
            'stdout' => $console,
            'output' => $output,
            'error' => $error,
            'orders' => $orders,
            'clogistique' => $clogistique,
            'multichannel' => $multichannel,
            'errors' => $this->errors
        ));

        if (!$cron) {
            echo (string)$callback.'('.$json.')';
            die;
        } else {
            echo $json;

            return count($orders);
        }
    }

    private function acceptOrders($cron = false)
    {
        $console = null;
        $id_lang = $this->id_lang;
        $cr = $this->_cr;
        // Modif YB - implementation cron pour la validation des commandes
        // $callback  = Tools::getValue('callback') ;
        $error = false;
        $count = 0;
        $output = array();

        $id_warehouse = null;

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_shop = (int)Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;

            if ($id_shop) {
                $shop = new Shop($id_shop);
                $id_shop_group = $shop->id_shop_group;
            } else {
                $id_shop = 1;
                $id_shop_group = 1;
            }
            Shop::setContext(Shop::CONTEXT_SHOP, $id_shop);

            $id_warehouse = (int)Configuration::get(parent::KEY.'_WAREHOUSE');
        }

        // Modif YB - implementation cron pour la validation des commandes
        if ($cron) {
            $selected_orders = array();
            $item_list = array();
            $tokenOrders = '';
            $callback = '';
        } else {
            $selected_orders = Tools::getValue('selected_orders');
            $item_list = Tools::getValue('item_list');
            $tokenOrders = Tools::getValue('token_order');
            $callback = Tools::getValue('callback');
        }

        // Modif YB - implementation cron pour la validation des commandes
        if (!$cron && !count($selected_orders)) {
            $this->errors[] = sprintf('%s(%s): %s', basename(__FILE__), __LINE__, $this->l('No selected orders')).$cr;

            return (false);
        }

        // Dev Mode
        // Had been generated by the dev mode file so can use it
        $orders = unserialize(CDiscountTools::file_get_contents($this->export.'/orders.out'));

        if (!$orders) {
            $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Unable to read orders...')).$cr;
            $error = true;
        }

        // Modif YB : la liste des commandes ?? importer est la liste complete des commandes list??es
        if ($cron) {
            $selected_orders = array();
            foreach ($orders as $keyorder => $order) {
                if (!(bool)$order['Order']['Acceptable']) {
                    $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, 'Incomplete order', $keyorder).$cr;
                    continue;
                }

                $selected_orders[] = $keyorder;

                if (isset($order['OrderLineList']['OrderLine']['AcceptationState'])) {
                    $order['OrderLineList']['OrderLine'] = array($order['OrderLineList']['OrderLine']);
                }

                foreach ($order['Order']['OrderLineList'] as $order_lines) {
                    // More than 1 item
                    if (!isset($order_lines[0]['AcceptationState'])) {
                        $order_lines = array($order_lines);
                    }

                    foreach ($order_lines as $order_line) {
                        if (isset($order_line['SellerProductId']) && !is_array($order_line['SellerProductId'])) {
                            $item_list[$keyorder][$order_line['SellerProductId']] = true;
                        }
                    }
                }
            }
        }

        $acceptOrder = array();

        foreach ($selected_orders as $key => $oID) {
            $stop = false;
            if (!isset($orders[$oID]['Order'])) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable to read order ID'), $oID).$cr;
                $error = true;
            }
            $order = $orders[$oID]['Order'];

            if (CDiscountOrder::checkByMpId($oID)) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('This order was already imported'), $oID).$cr;
                $error = true;
                continue;
            }


            if (!isset($acceptOrder[$oID])) {
                $acceptOrder[$oID] = array();
            }

            $acceptOrder[$oID]['OrderState'] = 'AcceptedBySeller';
            $acceptOrder[$oID]['Items'] = array();
            $acceptOrder[$oID]['Count'] = 0;
            $acceptOrder[$oID]['ProductCondition'] = array();

            foreach ($item_list[$oID] as $SellerProductId => $key) {
                if (!$SellerProductId) {
                    continue;
                }

                $acceptOrder[$oID]['Items'][(string)$SellerProductId] = 'AcceptedBySeller';
            }

            // Important !
            //
            if (isset($order['OrderLineList']['OrderLine']['AcceptationState'])) {
                $order['OrderLineList']['OrderLine'] = array($order['OrderLineList']['OrderLine']);
            }

            // Product Loop - Exclude unselected products
            //
            foreach ($order['OrderLineList'] as $order_lines) {
                foreach ($order_lines as $item) {
                    if ($item['Sku'] == 'FRAISTRAITEMENT' || $item['Sku'] == 'INTERETBCA') {
                        continue;
                    }

                    // Fill Condition (required by the API)
                    //
                    $acceptOrder[$oID]['ProductCondition'][(string)$item['SellerProductId']] = $item['ProductCondition'];

                    // Refuse other products
                    //
                    if (!isset($acceptOrder[$oID]['Items'][(string)$item['SellerProductId']])) {
                        //$acceptOrder[$oID]['Items'][ $item['SellerProductId'] ] = 'RefusedBySeller' ;
                        // Do not refuse the Order
                        if (isset($acceptOrder[$oID])) {
                            unset($acceptOrder[$oID]);
                            $stop = true;
                            break;
                        }
                        continue;
                    }

                    if ($stop) {
                        break;
                    }

                    // Count
                    //
                    $acceptOrder[$oID]['Count']++;

                    if ($this->debug) {
                        echo "---------------------------------------------------\n";
                        echo nl2br(print_r($acceptOrder[$oID], true));
                    }
                }
                if ($stop) {
                    break;
                }
            }

            if ($stop) {
                $this->errors[] = $this->l('Skipping Order: this order seems to be incomplete').' ('.$oID.')'.$cr;
                $error = true;
                continue;
            }
            if (!$acceptOrder[$oID]['Count']) {
                $this->errors[] = $this->l('No item selected, could not accept order').' ('.$oID.')'.$cr;
                $error = true;
                continue;
            } else {
                $output[] = '<p><img src="'.$this->images.'accept.png" alt="" />&nbsp;'.
                    sprintf($this->l('Order ID %s Successfully Accepted').$cr, '<b>'.$oID.'</b>').
                    '</p>';
            }
            $count++;
        } // foreach orders

        $marketplace = new CDiscountWebservice($this->username, $this->password, $this->production, $this->debug, $this->dev_mode);
        $marketplace->token = CDiscountTools::auth();

        // Dev Mode
        if (!$this->dev_mode && !$marketplace->token) {
            $this->errors[] = sprintf('%s(%s): %s', basename(__FILE__), __LINE__, $this->l('Auth failed')).$cr;
            $error = true;
        } else {
            // Dev Mode
            // @see cdiscount.webservice.class.php (358)
            if ($this->dev_mode) {
                $response = CDiscountTools::file_get_contents('validateorderlist.xml');
                $result = $marketplace->response('ValidateOrderList', $response);
                $validateOrderResults = $result->ValidateOrderResults;
                $return = array();

                foreach ($validateOrderResults->children() as $validateOrderResult) {
                    $return[(string)$validateOrderResult->OrderNumber] = (string)$validateOrderResult->Validated;
                }

                $result = $return;
            } else {
                if (!($result = $marketplace->ValidateOrderList($acceptOrder))) {
                    $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('ValidateOrderList failed...')).$cr;
                    $error = true;
                }
            }
        }

        if (!$cron) {
            $console = ob_get_clean();
        } else {
            ob_end_flush();
        }

        $json = Tools::jsonEncode(array(
            'stdout' => $console,
            'output' => $output,
            'error' => $error,
            'count' => $count,
            'orders' => $acceptOrder,
            'errors' => $this->errors
        ));

        if (!$cron) {
            echo (string)$callback.'('.$json.')';
            die;
        } else {
            echo $json;

            return true;
        }
    }
}

$marketplaceOrdersAccept = new CDiscountOrdersAccept;
$marketplaceOrdersAccept->dispatch();
