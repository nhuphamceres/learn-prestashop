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
require_once(dirname(__FILE__).'/../classes/cdiscount.address.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.cart.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.order.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.orderhistory.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.payment.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.webservice.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.context.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.admin_order.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.mail.logger.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.order_info.class.php');

require_once(dirname(__FILE__).'/../common/tools.class.php');
require_once(dirname(__FILE__).'/../common/configuration.class.php');

//header('Content-type: text/html; charset=utf-8');

class CDiscountOrdersImport extends CDiscount
{
    private $errors = array();
    private $_cr    = "<br />\n";

    private $username;
    private $password;
    private $production;

    public static $allowed_acceptation_states = array('AcceptedBySeller', 'ShippedBySeller');

    public function __construct()
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=') && (!isset($this->context->controller) || $this->context->controller == null)) {
            $controller = new FrontController();
            $controller->init();
        }

        parent::__construct();

        if ((int)Tools::getValue('id_lang')) {
            $this->id_lang = (int)Tools::getValue('id_lang');
        }

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            CDiscountContext::restore($this->context);

            if (Shop::isFeatureActive()) {
                $id_shop = (int)Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
            } else {
                $id_shop = null;
            }

            $this->id_shop = $id_shop;

            if ($this->id_shop) {
                Shop::setContext(Shop::CONTEXT_SHOP, $id_shop);
            }
        }
        $this->export = $this->path.'export/';

        // Dev Mode
        $this->dev_mode = (bool)Configuration::get(parent::KEY.'_DEV_MODE');
    }

    public function dispatch()
    {
        $cdtoken = Tools::getValue('cdtoken');
        $action = Tools::getValue('action');

        if (!Cdiscount::$debug_mode) {
            ob_start();
        }

        $token = parent::decode(Configuration::get(parent::KEY.'_PS_TOKEN'));

        if (!$this->dev_mode) {
            if ($cdtoken != $token) {
                $this->dieOnError($this->l('Wrong Token'));
            }

            if (!$token = CDiscountTools::auth()) {
                $this->dieOnError($this->l('Authentication Failed'));
            }
        }

        $this->username = Configuration::get(parent::KEY.'_USERNAME');
        $this->password = Configuration::get(parent::KEY.'_PASSWORD');
        $this->production = !(Configuration::get(parent::KEY.'_PREPRODUCTION') ? true : false);

        if (Cdiscount::$debug_mode) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }

        switch ($action) {
            case 'orders':
                $this->listOrders();
                break;
            case 'import':
                $this->importOrders();
                break;
            case 'cron':
                if ($this->listOrders(true) > 0) {
                    $this->importOrders(true);
                }
                break;
        }
    }

    private function getExtraChargeProduct($name)
    {
        $identifier = CDiscountProduct::getProductBySKU($name, $this->id_shop);

        if ($identifier) {
            return($identifier);
        }

        $id_lang_default = (int)Configuration::get('PS_LANG_DEFAULT');
        $languages = Language::getLanguages(false);
        $language_array = array();
        $language_array[$id_lang_default] = null;

        foreach ($languages as $language) {
            if ($language['id_lang'] != $id_lang_default) {
                $language_array[ $language['id_lang'] ] = &$language_array[$id_lang_default];
            }
        }

        $language_array[$id_lang_default] = $name;

        $name_array = $language_array;
        $name_array[$id_lang_default] = $name;

        $link_array = $language_array;
        $link_array[$id_lang_default] = Tools::link_rewrite($name);

        $product = new Product();
        $product->name = $name_array;
        $product->reference = $name;
        $product->active = true;
        $product->available_for_order = true;
        $product->visibility = 'none';
        $product->id_tax_rules_group = 0;
        $product->is_virtual = 1;
        $product->tax_name = null;
        $product->tax_rate = 0;
        $product->link_rewrite = $link_array;

        if ($product->validateFields(false, true)) {
            $product->add();

            if (!Validate::isLoadedObject($product)) {
                return(false);
            }

            if (method_exists('StockAvailable', 'setProductOutOfStock')) {
                StockAvailable::setProductOutOfStock((int)$product->id, 1);
            }

            return($product->id);
        } else {
            return(false);
        }
    }

    private function dieOnError($msg)
    {
        echo $msg;

        if (Cdiscount::$debug_mode) {
            die;
        }

        $output = ob_get_clean().$this->_cr;
        $json = Tools::jsonEncode(array('error' => true, 'output' => $output));

        if ($callback = Tools::getValue('callback')) {
            echo (string)$callback.'('.$json.')';
        } else {
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
        $error = false;
        $action = null;
        $output = array();

        $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $this->clogistique = (bool)Configuration::get(self::KEY.'_CLOGISTIQUE');

        if ($cron) {
            $dateStart = date('Y-m-d\T00:00:00.000', strtotime('now -7 days'));
            $dateEnd = date('Y-m-d\T23:59:59.000');
            $orders_statuses = 'Importable';

            if (Tools::getValue('status') != '' && in_array(Tools::getValue('status'), array('All', 'Importable'))) {
                $orders_statuses = Tools::getValue('status');
            }

            $token_order = '';
            $callback = '';
        } else {
            $dateStart = date('Y-m-d\T00:00:00.000', strtotime(Tools::getValue('datepickerFrom')));
            $dateEnd = date('Y-m-d\T23:59:59.000', strtotime(Tools::getValue('datepickerTo')));

            $orders_statuses = Tools::getValue('orders-statuses');
            $token_order = Tools::getValue('token_order');
            $callback = Tools::getValue('callback');
        }

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_warehouse = (int)Configuration::get(parent::KEY.'_WAREHOUSE');
        }

        $import_type = Configuration::get(parent::KEY.'_IMPORT_TYPE');

        $marketplace = new CDiscountWebservice($this->username, $this->password, $this->production, Cdiscount::$debug_mode, $this->dev_mode);

        if (!$this->dev_mode) {
            $marketplace->token = CDiscountTools::auth();

            if (!$marketplace->token) {
                $this->dieOnError(sprintf('%s(%s): %s', basename(__FILE__), __LINE__, $this->l('Auth failed')).$cr);

                return (false);
            }
        }

        if (!is_dir($this->export)) {
            if (!mkdir($this->export)) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable to create output directory'), $this->export).$cr;
                $error = true;
            }
        }

        $result = array();

        if (!$orders_statuses) {
            $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Order status must be selected...')).$cr;
            $error = true;
        } else {
            if ($orders_statuses == 'Importable') {
                $unimportable = false;
            } else {
                $unimportable = true;
            }

            $params = array(
                $dateStart,
                $dateStart,
                $dateEnd,
                $dateEnd,
                true,
                //
                $unimportable, // CancelledByCustomer
                false, // WaitingForSellerAcceptation
                $unimportable, // AcceptedBySeller
                $unimportable, // PaymentInProgress
                true, // WaitingForShipmentAcceptation
                $unimportable, // SHIPPED
                $unimportable, // RefusedBySeller
                $unimportable, // AutomaticCancellation
                $unimportable, // PaymentRefused
                $unimportable, // ShipmentRefusedBySeller
                $unimportable, // None
                $unimportable, // ValidatedFianet
                $unimportable,       // RefusedNoShipment
                $unimportable,       // AvailableOnStore
                $unimportable,       // NonPickedUpByCustomer
                $unimportable       // PickedUp
            );

            if (!($result = $marketplace->GetOrderList($params))) {
                $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('GetOrderList failed...')).$cr;
                $error = true;
            }

            if (!isset($result->OrderList)) {
                $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('No pending order...')).$cr;
                $error = true;
            }

            $clogistique = false;
            $multichannel = false;
            $channels = array();

            if ($import_type == Cdiscount::IMPORT_BY_SKU) {
                $import_type_display = $this->l('SKU');
            } else {
                $import_type_display = $this->l('Prod.Id');
            }


            $order_table
                = '
				<table class="import-table">
				<thead>
				 <tr class="heading">
					<td></td>
					<td>'.$import_type_display.'</td>
					<td>'.$this->l('Name').'</td>
					<td>'.$this->l('SKU').' ('.parent::NAME.')</td>
					<td>'.$this->l('EAN13').'</td>
					<td>'.$this->l('CDaV').'</td>
					<td>'.$this->l('Qty.').'</td>
					<td align="center">'.$this->l('Ship.').'</td>
					<td align="center">'.$this->l('Price').'</td>
					<td>'.$this->l('Status').'</td>
				 </tr>
				</thead>
				<tbody>
						%s
				</tbody>
				</table>';


            $orders = array();
            $order_list = array();

            if ($result) {
                foreach ($result->OrderList->children() as $order) {
                    $id_order = null;
                    $oId = (string)trim($order->OrderNumber);

                    if (($existing = CDiscountOrder::checkByMpId($oId))) {
                        if ($orders_statuses != 'All') {
                            continue;
                        }

                        $disabled = ' disabled="disabled"';
                        $can_be_imported = false;
                        $id_order = $existing['id_order'];
                    } elseif (in_array($order->OrderState, array(
                        'AcceptedBySeller',
                        'WaitingForShipmentAcceptation',
                        'AvailableOnStore',
                        'Shipped'
                    ))) {
                        $disabled = '';
                        $can_be_imported = true;
                    } else {
                        $disabled = ' disabled="disabled"';
                        $can_be_imported = false;
                    }

                    if ($id_order) {
                        $order_link = sprintf('<a href="#" class="order_link" onclick="window.open(\'?tab=AdminOrders&id_order=%s&vieworder&token=%s\')">%s(%s)</a>', $id_order, $token_order, $oId, $id_order);
                    } else {
                        $order_link = $oId;
                    }

                    $details = '';
                    $items_in_order = $items_importable = 0;
                    if (isset($order->OrderLineList)) {
                        foreach ($order->OrderLineList->children() as $orderList) {
                            $id_product = null;
                            $pass = true;

                            if ($orderList->Sku != 'FRAISTRAITEMENT' && $orderList->Sku != 'INTERETBCA') {
                                $id_product_attribute = null;
                                $identifier = null;
                                $items_in_order++;

                                if ($import_type == Cdiscount::IMPORT_BY_SKU) {
                                    $sku = trim((string)$orderList->SellerProductId);
                                    $productCheck = CDiscountProduct::checkProduct($sku, $this->id_shop);

                                    if ($this->dev_mode) {
                                        $productCheck = 1;
                                    }

                                    if (!$productCheck) {
                                        $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable to retrieve the SKU for this product'), $sku).$cr;
                                        $error = true;
                                        $pass = false;
                                    } elseif ($productCheck > 1) {
                                        $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Can\'t import a duplicate Reference/SKU'), $sku).$cr;
                                        $error = true;
                                        $pass = false;
                                    } else {
                                        if ($this->dev_mode) {
                                            $identifier = CDiscountProduct::getProductBySKUDemo($sku);
                                        } else {
                                            $identifier = CDiscountProduct::getProductBySKU($sku, $this->id_shop);
                                        }

                                        if ($identifier == null) {
                                            $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable to retrieve the SKU for this product'), $orderList->SellerProductId).$cr;
                                            $error = true;
                                            $pass = false;
                                        }
                                    }
                                } else {
                                    $identifier = (string)$orderList->SellerProductId;
                                }

                                // Product With Combination
                                //
                                if (strpos($identifier, '_') !== false) {
                                    $split_combination = explode('_', $identifier);
                                    $id_product = (int)$split_combination[0];
                                    $id_product_attribute = (int)$split_combination[1];
                                } elseif (is_numeric(trim($identifier))) {
                                    $id_product = (int)trim($identifier);
                                    $id_product_attribute = false;
                                } elseif ($import_type == Cdiscount::IMPORT_BY_ID) {
                                    $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Incorrect format for product ID'), $identifier).$cr;
                                    $error = true;
                                    $pass = false;
                                }
                                $outofstock_but_orderable = false;

                                if ($pass) {
                                    $product = new Product($id_product, false, $this->id_lang);


                                    if (!Validate::isLoadedObject($product)) {
                                        $this->errors[] = sprintf('%s/%s: %s (%s - %s)', basename(__FILE__), __LINE__, $this->l('Unable to find this product'), $orderList->SellerProductId, $orderList->Sku).$cr;
                                        $error = true;
                                        $pass = false;
                                    }
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
                                } else {
                                    $quantity = 0;
                                }

                                if ($this->dev_mode) {
                                    $orderList->SellerProductId = CDiscountProduct::getProductSKUById($id_product, $id_product_attribute);
                                }

                                if ($pass && $existing) {
                                    $image = '<img src="'.$this->images.'icon_valid_16.png" title="'.$this->l('Already Imported').'" />';
                                    $action = '<td style="padding-left:8px;width:20px">'.$image.'</td>';
                                    $order->Skip = true;
                                } elseif ($pass && (!$quantity && !$outofstock_but_orderable)) {
                                    $image = '<img src="'.$this->images.'soos.png" title="'.$this->l('Out of Stock').'" />';
                                    $action = '<td style="padding-left:8px;width:20px">'.$image.'</td>';
                                    $order->Skip = true;
                                } elseif ($pass) {
                                    $items_importable++;
                                    if ($can_be_imported && in_array($orderList->AcceptationState, array(
                                            'AcceptedBySeller',
                                            'WaitingForShipmentAcceptation',
                                            'AvailableOnStore'
                                        ))
                                    ) {
                                        $checked = 'checked';
                                    } else {
                                        $checked = '';
                                    }
                                    $action = '<td align="center" style="width:20px"><input type="checkbox" id="pl-'.$oId.'-'.$orderList->Sku.'" name="item_list['.$oId.']['.$orderList->Sku.']" value="1" '.$checked.' /></td>';
                                } else {
                                    $image = '<img src="'.$this->images.'cross.png" title="'.$this->l('Product not found').'" />';
                                    $action = '<td style="padding-left:8px;width:20px">'.$image.'</td>';
                                    $order->Skip = true;
                                }
                            } else {
                                $image = '<img src="'.$this->images.'valid.png" title="'.$this->l('Fees').'" />';
                                $action = '<td style="padding-left:8px;width:20px">'.$image.'</td>';
                            }

                            if ($id_product && $pass) {
                                $product_name = CDiscountProduct::getSimpleProductName($id_product, $id_lang);
                            } else {
                                $product_name = ' - ';
                            }


                            $displayed_id = (string)$orderList->SellerProductId ? (string)$orderList->SellerProductId : ($import_type == Cdiscount::IMPORT_BY_ID ? sprintf('(%s)', $identifier) : $identifier);

                            $details
                                .= '<tr>'.
                                $action.
                                '<td>'.$displayed_id.'</td>'.
                                '<td>'.($orderList->Sku != 'FRAISTRAITEMENT' || $orderList->Sku != 'INTERETBCA' ? $product_name : '-').'</td>'.
                                '<td>'.$orderList->Sku.'</td>'.
                                '<td>'.$orderList->ProductEan.'</td>'.
                                '<td>'.($orderList->IsCDAV == 'true' ? $this->l('Yes') : $this->l('No')).'</td>'.
                                '<td>'.$orderList->Quantity.'</td>'.
                                '<td align="right">'.sprintf('%.02f', $orderList->UnitShippingCharges).'</td>'.
                                '<td align="right">'.sprintf('%.02f', $orderList->PurchasePrice).'</td>'.
                                '<td>'.$orderList->AcceptationState.'</td>'.
                                '</tr>'."\n";
                        }
                    }
                    if ($items_in_order != $items_importable) {
                        $disabled = ' disabled="disabled"';
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

                        switch (Tools::strtoupper($channel_name)) {// Skip orders from other channels
                            case 'AMAZON':
                            case 'EBAY':
                            case 'PRICEMINISTER':
                                $order->Skip = true;
                                $disabled = ' disabled="disabled"';
                                break;
                        }
                    } else {
                        $clogistique_content = sprintf('<em>%s</em>', $this->l('Standard'));
                    }


                    $customer_name = CommonTools::ucwords(Tools::strtolower(sprintf('%s %s', Tools::substr($order->Customer->LastName, 0, 30), Tools::substr($order->Customer->FirstName, 0, 30))));

                    $orders[$oId]
                        = '<tr class="order-item">'.
                        '<td align="center"><input type="checkbox" name="selected_orders[]" '.$disabled.' id="o-'.$oId.'" value="'.$oId.'" '.($can_be_imported ? 'checked' : '').' /></td>'.
                        '<td>'.$order_link.'</td>'.
                        '<td>'.Tools::substr($order->CreationDate, 0, 10).'</td>'.
                        '<td><b>'.$customer_name.'</b></td>'.
                        '<td>'.$order->ShippingCode.'</td>'.
                        '<td rel="clogistique" style="display:none">'.$clogistique_content.'</td>'.
                        '<td rel="multichannel" style="display:none">'.$channel_content.'</td>'.
                        '<td>'.$order->ValidationStatus.'</td>'.
                        '<td>'.$order->OrderState.'</td>'.
                        '<td align="right">'.sprintf('%.02f', $order->InitialTotalAmount).'</td>'.
                        '</tr>';
                    $orders[$oId] .= '<tr><td rel="order-container" colspan="8">'.sprintf($order_table, $details).'</td></tr>';

                    $order_list[$oId] = CDiscountTools::xml2array($order->asXML());

                    if (!$order_list[$oId]) {
                        $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Unable to convert xml to array...')).$cr;
                        $error = true;
                    }
                }
                $fileout = $this->export.'orders.out';

                if (file_exists($fileout) && !is_writeable($fileout)) {
                    $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('File is not writeable, please verify directory permissions'), $fileout).$cr;
                    $error = true;
                }

                if (file_put_contents($fileout, serialize($order_list)) === false) {
                    $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Unable to save orders...')).$cr;
                    $error = true;
                }
                // todo: Use json instead
                @file_put_contents($this->export . 'orders.json', json_encode($order_list));

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

        if (isset($channels) && count($channels) > 1) {
            $multichannel = true;
        }

        $console = ob_get_clean();

        $finalOutput = array(
            'stdout' => $console,
            'output' => $output,
            'error' => $error,
            'result' => $result,
            'orders' => $orders,
            'clogistique' => $clogistique,
            'multichannel' => $multichannel,
            'errors' => $this->errors
        );
        $json = json_encode($finalOutput);

        if (!$cron) {
            echo (string)$callback.'('.$json.')';
            die;
        } else {
            $this->pd('Order list:', print_r($finalOutput, true));
            return count($orders);
        }
    }

    private function importOrders($cron = false)
    {
        $id_lang = $this->id_lang;
        
        $this->clogistique = (bool)Configuration::get(self::KEY.'_CLOGISTIQUE');

        if ($this->clogistique) {
            $clogistique_destock = (bool)Configuration::get(self::KEY.'_CLOGISTIQUE_DESTOCK');
        } else {
            $clogistique_destock = false;
        }
        
        $channels = array();
        $cr = $this->_cr;

        if ($cron) {
            $callback = '';
            $selected_orders = array();
            $tokenOrders = '';
        } else {
            $callback = Tools::getValue('callback');
            $selected_orders = Tools::getValue('selected_orders');
            $tokenOrders = Tools::getValue('token_order');
        }
        $import_type = Configuration::get(parent::KEY.'_IMPORT_TYPE');
        $extra_fees = Configuration::get(self::KEY.'_EXTRA_FEES');

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_default_customer_group = Configuration::get('PS_CUSTOMER_GROUP');
        } else {
            $id_default_customer_group = (int)_PS_DEFAULT_CUSTOMER_GROUP_;
        }

        $id_currency = (int)Currency::getIdByIsoCode('EUR');

        if (!is_numeric($id_currency)) {
            $this->errors[] = sprintf('%s(%s): %s', basename(__FILE__), __LINE__, $this->l('Unable to find EUR currency')).$cr;

            return (false);
        }

        // Customer Group
        $id_customer_group = (int)Configuration::get(parent::KEY.'_CUSTOMER_GROUP');

        if ((int)$id_customer_group && is_numeric($id_customer_group)) {
            $group = new Group($id_customer_group);

            if (!Validate::isLoadedObject($group)) {
                $id_customer_group = $id_default_customer_group;
            }

            unset($group);
        }

        $marketplace = new CDiscountWebservice($this->username, $this->password, $this->production, Cdiscount::$debug_mode, $this->dev_mode);

        if (!$this->dev_mode) {
            $marketplace->token = CDiscountTools::auth();

            if (!$marketplace->token) {
                $this->errors[] = sprintf('%s(%s): %s', basename(__FILE__), __LINE__, $this->l('Auth failed')).$cr;

                return (false);
            }
        }

        $error = false;
        $count = 0;
        $output = array();

        if (!$cron && !count($selected_orders)) {
            $this->errors[] = sprintf('%s(%s): %s', basename(__FILE__), __LINE__, $this->l('No selected orders')).$cr;

            return (false);
        }

        $orders = unserialize(CDiscountTools::file_get_contents($this->export.'orders.out'));

        if (!$orders) {
            $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Unable to read orders...')).$cr;

            return false;
        }

        if ($cron) {
            $selected_orders = array();
            foreach ($orders as $keyorder => $order) {
                $selected_orders[] = $keyorder;
            }
        }

        $carriers_info = unserialize(parent::decode(Configuration::get(parent::KEY.'_CARRIERS_INFO')));
        $carriers_params = unserialize(parent::decode(Configuration::get(parent::KEY.'_CARRIERS_PARAMS')));
        $carriers_clogistique = unserialize(parent::decode(Configuration::get(parent::KEY.'_CARRIERS_CLOGISTIQUE')));

        $id_customer = Configuration::get(parent::KEY.'_CUSTOMER_ID');
        $useTaxes = Configuration::get(parent::KEY.'_USE_TAXES') ? true : false;

        $order_states = unserialize(parent::decode(Configuration::get(parent::KEY.'_ORDERS_STATES')));

        $individual = Configuration::get(parent::KEY.'_INDIVIDUAL_ACCOUNT') ? true : false;
        $domain = Configuration::get(parent::KEY.'_INDIVIDUAL_DOMAIN');


        foreach ($selected_orders as $key => $oID) {
            $previousIdProduct = null;

            $itemDetails = array();
            $fees = 0;

            if (!isset($orders[$oID]['Order'])) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable to read order ID'), $oID).$cr;
                $error = true;
                unset($selected_orders[$key]);
            }

            if (isset($orders[$oID]['Order']['Skip']) && (int)$orders[$oID]['Order']['Skip']) {
                continue;
            }

            $order = $orders[$oID]['Order'];

            // if there is only 1 order line, the API returns an order line directly not an array of 1 order line
            // need to cast to array
            if (isset($order['OrderLineList']['OrderLine']['AcceptationState'])) {
                $order['OrderLineList']['OrderLine'] = array($order['OrderLineList']['OrderLine']);
            }

            if ($this->isFBCOrder($order)) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Cannot import FBC order'), $oID).$cr;
                $error = true;
                unset($selected_orders[$key]);

                continue;
            }

            if (($cd_order = CDiscountOrder::checkByMpId($oID))) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('This order was already imported'), $oID).$cr;
                $error = true;
                unset($selected_orders[$key]);
                continue;
            }

            $date_add = date('Y-m-d H:i:s', strtotime($order['CreationDate']));

            $customerId = $order['Customer']['CustomerId'];
            $customerFirstname = CommonTools::ucwords(Tools::strtolower($order['Customer']['FirstName']));
            $customerLastname = CommonTools::ucwords(Tools::strtolower($order['Customer']['LastName']));
            $customerMobile = preg_replace('/[^0-9]/', '', $order['Customer']['MobilePhone']);
            $customerPhone = preg_replace('/[^0-9]/', '', $order['Customer']['Phone']);
            $shippingFirstName = CommonTools::ucwords(Tools::strtolower($order['Customer']['ShippingFirstName']));
            $shippingLastName = CommonTools::ucwords(Tools::strtolower($order['Customer']['ShippingLastName']));
            if (isset($order['Customer']['EncryptedEmail'])) {
                $encrypted_email = $order['Customer']['EncryptedEmail'];

                if (!Validate::isEmail($encrypted_email)) {
                    $encrypted_email = null;
                }
            } else {
                $encrypted_email = null;
            }
            $shippingPoint = CommonTools::ucwords(Tools::strtolower($order['ShippingAddress']['FirstName']));

            $order['BillingAddress']['MobilePhone'] = $customerMobile;
            $order['BillingAddress']['Phone'] = $customerPhone;
            $order['ShippingAddress']['MobilePhone'] = $customerMobile;
            $order['ShippingAddress']['Phone'] = $customerPhone;
            $order['ShippingAddress']['ShippingPoint'] = $shippingPoint;

            $billing_address_id = null;

            // Customer individual account
            //
            if ($individual) {
                $billing_address = new CDiscountAddress();
                $billing_address_id = $billing_address->lookupOrCreateAddress($order['BillingAddress'], $id_lang, false);
                $id_customer = null;

                if ($billing_address_id) {
                    $customer_address = new Address($billing_address_id);

                    if (Validate::isLoadedObject($customer_address) && $customer_address->id_customer) {
                        $id_customer = $customer_address->id_customer;
                    }
                }

                if (!$id_customer) {
                    // Try to retrieve a valid address
                    //
                    if ($encrypted_email) {
                        $email_address = $encrypted_email;
                    } elseif (($result = $marketplace->GenerateDiscussionMailGuid(array('OrderId' => $oID)))) {
                        $email_address = (string)$result;
                    } else {
                        $email_address = sprintf('%s@%s', $customerId, str_replace('@', '', Cdiscount::TRASH_DOMAIN));
                    }

                    if (!Validate::isEmail($email_address)) {
                        $email_address = sprintf('%s@%s', $customerId, str_replace('@', '', $domain));
                    }

                    $customer = new Customer();
                    $customer->getByEmail($email_address);

                    if ($customer->id) {
                        // Existing Customer
                        $id_customer = $customer->id;
                    } else {
                        $customer->firstname = CDiscountAddress::cleanLogin($customerFirstname);
                        $customer->lastname = CDiscountAddress::cleanLogin($customerLastname);
                        $customer->newsletter = false;
                        $customer->optin = false;
                        $customer->id_default_group = $id_customer_group;
                        $customer->email = $email_address;
                        $customer->passwd = md5(rand());

                        $pass = true;

                        if (!$customer->validateFields(false, false)) {
                            $pass = false;
                        } elseif (!$customer->add()) {
                            $pass = false;
                        }

                        if (!$pass) {
                            $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Couldn\'t add this customer'), $email_address).$cr;
                            $error = true;
                            unset($selected_orders[$key]);
                            continue;
                        } else {
                            $id_customer = $customer->id;
                        }
                    }
                }
            }

            // Create or get address book entry
            //
            if (!count($order['ShippingAddress'])) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Missing Shipping Address'), $oID).$cr;
                $error = true;
                unset($orders[$oID]);
                continue;
            }

            // Address components :
            //  City, Country, ZipCode, ApartmentNumber, Building, Civility, CompanyName, FirstName, Instructions, LastName, PlaceName, Street

            $shipping_address = new CDiscountAddress();
            $shipping_address->id_customer = $id_customer;

            $order = CDiscountTools::trimArray($order);

            $orderState = $order['OrderState'];
            $shippingCode = $order['ShippingCode'];
            $isCasinoOrder = isset($order['Corporation']['CorporationCode']) && $order['Corporation']['CorporationCode'] == 'CASIFR';

            // 2014-04-23 - Patch : code could be COL instead STD but this is identical.
            // 2021-11-16: Casino order uses 'standard' instead of 'STD'. They're fixing on their side. Meanwhile, we modify it on the fly.
            if ($shippingCode == 'COL' || ($isCasinoOrder && Tools::strtolower($shippingCode) == 'standard')) {
                $shippingCode = 'STD';
            }

            // Shipping Point
            $order['ShippingAddress']['ShippingCode'] = $shippingCode;
            $order['ShippingAddress']['Labels'] = array();
            $order['ShippingAddress']['Labels']['ShippingCode'] = $order['ShippingCode'];
            $order['ShippingAddress']['Labels']['ApartmentNumber'] = $this->l('Apt');
            $order['ShippingAddress']['Labels']['Building'] = $this->l('Building');
            $order['ShippingAddress']['Labels']['PlaceName'] = $this->l('Place');

            $order['BillingAddress']['Labels'] = $order['ShippingAddress']['Labels'];
            $order['BillingAddress']['ShippingCode'] = $shippingCode;

            $billing_address = new CDiscountAddress();
            $billing_address->id_customer = $id_customer;
            $billing_address_id = $billing_address_id ? $billing_address_id : $billing_address->lookupOrCreateAddress($order['BillingAddress'], $id_lang);

            $order['ShippingAddress']['FirstName'] = $shippingFirstName;
            $order['ShippingAddress']['LastName'] = $shippingLastName;

            $shipping_address_id = $shipping_address->lookupOrCreateAddress($order['ShippingAddress'], $id_lang);

            $orderTotal = $order['ValidatedTotalAmount'];
            $orderShippingTotal = $order['ValidatedTotalShippingCharges'];

            $cdOrderStateId = CDiscountTools::orderStateToId($orderState);

            if (Cdiscount::$debug_mode) {
                echo "---------------------------------------------------\n";
                echo "addressId : $shipping_address_id / $billing_address_id \n";
                print_r($customerFirstname);
                print_r($customerLastname);
                print_r($customerMobile);
                print_r($customerPhone);
                print_r($orderState);
                print_r($shippingCode);
                print_r($orderTotal);
                print_r($orderShippingTotal);
                echo "\n";
            }

            $id_carrier = null;

            if (isset($order['IsCLogistiqueOrder']) && $order['IsCLogistiqueOrder'] == 'true') {
                $clogistique = true;
            } else {
                $clogistique = false;
            }

            if (!$clogistique && is_array($carriers_params) && count($carriers_params)) {
                foreach ($carriers_params as $type => $carrier_param) {
                    if (isset($carrier_param['Code']) && $carrier_param['Code'] == $shippingCode && isset($carriers_info[$type]) && $carriers_info[$type]) {
                        $id_carrier = (int)$carriers_info[$type];
                    }
                }
            }

            if ($clogistique && is_array($carriers_clogistique) && count($carriers_clogistique)) {
                if (array_key_exists($shippingCode, $carriers_clogistique) && (int)$carriers_clogistique[$shippingCode]) {
                    $id_carrier = (int)$carriers_clogistique[$shippingCode];
                }
            }

            if (!$id_carrier) {
                if (Cdiscount::$debug_mode) {
                    echo "---------------------------------------------------\n";
                    echo "Carrier Info:\n";
                    echo nl2br(print_r($carriers_info, true));
                    echo "Carrier Params:\n";
                    echo nl2br(print_r($carriers_params, true));
                    echo "\n";
                }
                if (array_key_exists($shippingCode, Cdiscount::$predefined_carriers)) {
                    $display_carrier = sprintf('"%s" (%s)', Cdiscount::$predefined_carriers[$shippingCode], $shippingCode);
                } elseif (array_key_exists($shippingCode, Cdiscount::$carrier_for_clogistique)) {
                    $display_carrier = sprintf('"%s" (%s)', Cdiscount::$carrier_for_clogistique[$shippingCode], $shippingCode);
                } else {
                    $display_carrier = $shippingCode;
                }

                $this->errors[] = sprintf($this->l('Unmatched shipping method for order #%s, you have to configure your carrier mapping the module configuration %s').$cr, $oID, $display_carrier);
                $error = true;
                continue;
            }

            if ($clogistique && array_key_exists(parent::KEY.'_CLCL', $order_states) && (int)$order_states[parent::KEY.'_CLCL']) {
                $order_state = $order_states[parent::KEY.'_CLCL']; // commande C Logistique
                $destock = (bool)$clogistique_destock;
            } else {
                $order_state = $order_states[parent::KEY.'_CA']; // commande acceptee
                $destock = true;
            }

            // Building Cart
            //
            $cart = new CDiscountCart();
            $cart->id_address_delivery = $shipping_address_id;
            $cart->id_address_invoice = $billing_address_id;
            $cart->id_carrier = $id_carrier;
            $cart->id_currency = (int)$id_currency;
            $cart->id_customer = $id_customer;

            $order_lang_id = $id_lang;

            // Modif YB : definition de la langue selon l'adresse du client !
            if ($orderBillingAddress = new Address($cart->id_address_invoice)) {
                if ($addressCountry = new Country($orderBillingAddress->id_country)) {
                    if ($addressLangId = Language::getIdByIso(Tools::strtolower($addressCountry->iso_code))) {
                        $order_lang_id = (int)$addressLangId;

                        $order_lang = new Language($order_lang_id);

                        if (!Validate::isLoadedObject($order_lang)) {
                            $order_lang_id = $id_lang;
                        }
                    }
                }
            }

            $cart->id_lang = $order_lang_id;

            $cart->add();
            $previousIdProduct = null;
            $totalQuantity = $totalSaleableQuantity = 0;

            if (isset($order['Corporation']['CorporationName']) && !empty($order['Corporation']['CorporationName'])) {
                $channel_id = (int)$order['Corporation']['CorporationId'];
                $channel_name = (string)$order['Corporation']['CorporationName'];

                $channels[$channel_name] = $channel_id;
            } else {
                $channel_id = 1;
                $channel_name = 'Cdiscount';
            }

            // Product Loop
            //
            $shippingDateMinTs = null;
            $shippingDateMaxTs = null;
            $deliveryDateMinTs = null;
            $deliveryDateMaxTs = null;
            foreach ($order['OrderLineList'] as $order_lines) {
                foreach ($order_lines as $item) {
                    $isVirtualProduct = $this->isVirtualProduct($item['Sku']);
                    if (!$extra_fees && $isVirtualProduct) {
                        $fees += $item['PurchasePrice'];

                        // Adding handling fees to the product price
                        //
                        if ($previousIdProduct && isset($itemDetails[$previousIdProduct]['price'])) {
                            $itemDetails[$previousIdProduct]['price'] += (float)$item['PurchasePrice'];
                        }
                        continue;
                    }

                    $sku = (string)$item['Sku'];

                    if (!isset($item['AcceptationState']) || !in_array($item['AcceptationState'], self::$allowed_acceptation_states)) {
                        $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, 'Unallowed acceptation state', $sku).$cr;
                        if (Cdiscount::$debug_mode) {
                            $this->errors[] = print_r($item, true);
                        }
                        $error = true;
                        continue;
                    }

                    if ($extra_fees && $isVirtualProduct) {
                        $id_product = (int)$this->getExtraChargeProduct(CommonTools::ucfirst($sku));

                        if (!$id_product) {
                            $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, 'Unable to create extra fee virtual product', $id_product).$cr;
                            $error = true;
                            continue;
                        }

                        $id_combination = null;
                        $id_product_attribute = null;
                        $is_extra_fees = true;
                    } else {
                        $is_extra_fees = false;

                        if ($import_type == Cdiscount::IMPORT_BY_SKU) {
                            if ($this->dev_mode) {
                                $identifier = CDiscountProduct::getProductBySKUDemo($sku);
                            } else {
                                $identifier = CDiscountProduct::getProductBySKU($item['SellerProductId'], $this->id_shop);
                            }

                            if ($identifier == null) {
                                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable to retrieve the SKU for this product'), $item['SellerProductId']).$cr;
                                $error = true;
                                continue;
                            }
                        } else {
                            $identifier = (string)$item['SellerProductId'];
                        }
                        // Product With Combination
                        //
                        if (strpos($identifier, '_') !== false) {
                            // Modif YB : remplacement de la variable $sku par celle qui contient vraiment l'id produit et combi
                            $split_combination = explode('_', $identifier);
                            $id_product = (int)$split_combination[0];
                            $id_combination = (int)$split_combination[1];
                        } else {
                            $id_product = (int)$identifier;
                            $id_combination = false;
                        }
                    }

                    $price = (float)$item['PurchasePrice'];
                    $quantity = (int)$item['Quantity'];
                    $totalQuantity += (int)$quantity;

                    if (Cdiscount::$debug_mode) {
                        echo "---------------------------------------------------\n";
                        echo "Item:\n";
                        echo nl2br(print_r($item, true));
                        echo "\n";
                    }

                    // Load Product
                    //
                    $product = new Product($id_product, true, $order_lang_id);

                    if (!Validate::isLoadedObject($product)) {
                        if (Cdiscount::$debug_mode) {
                            echo "---------------------------------------------------\n";
                            echo "Product Instance:\n";
                            echo nl2br(print_r(get_object_vars($product), true));
                            echo "\n";
                        }

                        $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable to import product'), $id_product).$cr;
                        $error = true;
                        continue;
                    }

                    if (!$product->validateFields(false, false)) {
                        if (Cdiscount::$debug_mode) {
                            echo "---------------------------------------------------\n";
                            echo "Product Instance:\n";
                            echo nl2br(print_r(get_object_vars($product), true));
                            echo "\n";
                        }
                        
                        $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable to import product - validation failed'), $id_product).$cr;
                        $error = true;
                        continue;
                    }

                    $id_product_attribute = null;

                    if ($product->minimal_quantity > 1) {
                        $this->errors[] = sprintf('%s : %d (%d)', $this->l('Couln\'t import a product with a minimal quantity greater than 1'), $id_product, $id_product_attribute);
                        $error = true;
                        continue;
                    }

                    if (!$is_extra_fees && isset($product->available_for_order) && !$product->available_for_order) {
                        $this->errors[] = sprintf('%s : %d (%d)', $this->l('Couln\'t import a product unavailable for order'), $id_product, $id_product_attribute);
                        $error = true;
                        continue;
                    }

                    $product_name = $product->name;
                    $minimal_quantity = $product->minimal_quantity;

                    // Load Combination
                    //
                    if ($id_combination) {
                        if (version_compare(_PS_VERSION_, '1.5', '<')) {
                            $combinations = $product->getAttributeCombinaisons($order_lang_id /* Modif YB $id_lang */);
                        } else {
                            $combinations = $product->getAttributeCombinations($order_lang_id /* Modif YB $id_lang */);
                        }

                        if ($combinations) {
                            foreach ($combinations as $keycombi => $combination) {
                                if ($combination['id_product_attribute'] == $id_combination) {
                                    $id_product_attribute = $combination['id_product_attribute'];
                                    break;
                                }
                            }
                        }
                        if (!$id_product_attribute) {
                            $this->errors[] = sprintf('%s : %d (%d)', $this->l('Couln\'t match product attributes for product'), $id_product, $id_combination);
                            $error = true;
                            continue;
                        } else {
                            $minimal_quantity = $combination['minimal_quantity'];
                        }
                    }

                    if ($minimal_quantity > 1) {
                        $this->errors[] = sprintf('%s : %d (%d)', $this->l('Couln\'t import a product with a minimal quantity greater than 1'), $id_product, $id_product_attribute);
                        $error = true;
                        continue;
                    }

                    if (!$is_extra_fees && $useTaxes) {
                        // PS 1.4 sinon 1.3
                        //

                        if (method_exists('Tax', 'getProductTaxRate')) {
                            $product_tax_rate = (float)(Tax::getProductTaxRate($product->id, $shipping_address_id));
                        } else {
                            $product_tax_rate = (float)(Tax::getApplicableTax($product->id_tax, $product->tax_rate, $shipping_address_id));
                        }
                    } else {
                        $product_tax_rate = 0;
                    }

                    if ($cart->updateQty($quantity, $id_product, $id_product_attribute) < 0) {
                        $this->errors[] = sprintf('%s : ID: %d/%d - %s', $this->l('Not enough stock for this product'), $id_product, $id_product_attribute, $product_name);
                        $error = true;
                        continue;
                    }

                    $product_identifier = sprintf('%d_%d', $id_product, $id_product_attribute);
                    // For handling fees
                    $previousIdProduct = $product_identifier;

                    if (isset($itemDetails[$product_identifier])) {
                        $itemDetails[$product_identifier]['qty'] += $quantity;
                    } else {
                        $itemDetails[$product_identifier] = array(
                            'id_product' => $id_product,
                            'qty' => $quantity,
                            'sku' => $sku,
                            'price' => (float)$price,
                            'name' => $product_name,
                            'tax_rate' => $product_tax_rate,
                            'id_tax' => isset($product->id_tax) ? $product->id_tax : false,
                            'id_tax_rules_group' => isset($product->id_tax_rules_group) ? $product->id_tax_rules_group : false,
                            'id_address_delivery' => $shipping_address_id
                        );
                    }
                    $outofstock_but_orderable = !$quantity && Product::isAvailableWhenOutOfStock($product->out_of_stock);

                    $totalSaleableQuantity += ($outofstock_but_orderable ? $totalQuantity : (int)$quantity);

                    if (!$isVirtualProduct) {
                        $shippingDateMinTs = $this->getMostDateTs($shippingDateMinTs, $item['ShippingDateMin'], false);
                        $shippingDateMaxTs = $this->getMostDateTs($shippingDateMaxTs, $item['ShippingDateMax'], true);
                        $deliveryDateMinTs = $this->getMostDateTs($deliveryDateMinTs, $item['DeliveryDateMin'], false);
                        $deliveryDateMaxTs = $this->getMostDateTs($deliveryDateMaxTs, $item['DeliveryDateMax'], true);
                    }
                } // foreach products line
            } // foreach products line

            if ($totalQuantity != $totalSaleableQuantity) {
                $this->errors[] = $this->l('Skipping Order: Product count mismatch, impossible to import an incomplete order').' ('.$oID.')'.$cr;
                $error = true;

                if (Validate::isLoadedObject($cart)) {
                    $cart->delete();
                }

                continue;
            }

            if (!count($itemDetails)) {
                $this->errors[] = $this->l('Cart empty, could not save order').' ('.$oID.')'.$cr;
                $error = true;

                if (Validate::isLoadedObject($cart)) {
                    $cart->delete();
                }

                continue;
            }

            // Using price, shipping details etc... from the Market Place
            //
            $cart->cdProducts = $itemDetails;
            $cart->cdShipping = (float)$order['ValidatedTotalShippingCharges'];
            $cart->cdDate = $date_add;
            $cart->cdFees = $fees;

            // duplication du panier, important !!!
            //
            $acart = $cart;

            if (Cdiscount::$debug_mode) {
                echo "---------------------------------------------------\n";
                echo "Items:\n";
                echo nl2br(print_r($itemDetails, true));
                echo "Cart:\n";
                echo nl2br(print_r(get_object_vars($acart), true));
                echo "\n";
            }

            $payment = new CDiscountPaymentModule();

            $newOrderId = null;

            if (($newOrderId = $payment->validateMarketplaceOrder($order_state, parent::NAME, $cart->id_currency, $oID, $cdOrderStateId, $cart->cdShipping, $acart, $destock))) {
                Configuration::updateValue(parent::KEY.'_LAST_IMPORT', serialize(parent::encode(date('Y-m-d H:i:s'))));
                $count++;
            } else {
                $this->errors[] = sprintf($this->l('1 or more error occurs, unable to import order ID : %s'), $oID);
                $error = true;
                unset($selected_orders[$key]);

                if (Validate::isLoadedObject($cart)) {
                    $cart->delete();
                }
            }

            // Pickup Point Import - debuss-a
            if ($newOrderId && in_array($shippingCode, array(
                    'SO1',
                    'REL'
                )) && isset($order['ShippingAddress']['RelayId']) && !empty($order['ShippingAddress']['RelayId'])
            ) {
                require_once(dirname(__FILE__).'/../classes/'.parent::MODULE.'.pickuppoint.class.php');

                $pickup_point = new CDiscountPickupPoint();
                $pickup_point->id = trim($order['ShippingAddress']['RelayId']);
                $pickup_point->name = trim($shippingPoint);
                $pickup_point->address1 = trim($order['ShippingAddress']['Street']);
                $pickup_point->zipcode = trim($order['ShippingAddress']['ZipCode']);
                $pickup_point->city = trim($order['ShippingAddress']['City']);
                $pickup_point->country = Tools::strtoupper(trim($order['ShippingAddress']['Country']));

                $pickup_point->id_order = (int)$newOrderId;
                $pickup_point->id_customer = (int)$cart->id_customer;
                $pickup_point->id_cart = (int)$cart->id;

                switch ($shippingCode) {
                    case ('REL'):
                        if (CDiscountPickupPoint::isMondialRelayInstalled()) {
                            $pickup_point->pickup_type = CDiscountPickupPoint::MONDIAL_RELAY_TYPE;
                            $pickup_point->id_method = (int)Db::getInstance()->getValue('SELECT `id_mr_method` FROM `'._DB_PREFIX_.'mr_method` WHERE `id_carrier` = '.$cart->id_carrier);

                            if (!$pickup_point->save()) {
                                printf('%s(%s): %s (%s)', basename(__FILE__), __LINE__, $this->l('Failed to save Mondial Relay table entry for order'), $newOrderId).$cr;
                                print nl2br(print_r($pickup_point, true));
                            }
                        }
                        break;

                    case ('SO1'):
                        if (CDiscountPickupPoint::isSoColissimoInstalled()) {
                            $pickup_point->pickup_type = CDiscountPickupPoint::SO_COLISSIMO_TYPE;
                            $pickup_point->phone = trim($order['Customer']['MobilePhone']) ? trim($order['Customer']['MobilePhone']) : '06661010203';
                            $pickup_point->email = trim($order['Customer']['Email']) ? trim($order['Customer']['Email']) : 'no-reply@nomail.fr';

                            if (!$pickup_point->save()) {
                                printf('%s(%s): %s (%s)', basename(__FILE__), __LINE__, $this->l('Failed to save Mondial Relay table entry for order'), $newOrderId).$cr;
                                print nl2br(print_r($pickup_point, true));
                            }
                        }
                        break;

                    default:
                        $this->errors[] = sprintf($this->l('Unknown Pickup Point Delivery Method for the order #%s'), $oID);
                        break;
                }
            }

            if ($newOrderId) {
                if (!$this->saveOrderInfo(
                    $newOrderId,
                    $oID,
                    $channel_id,
                    $channel_name,
                    $clogistique,
                    $shippingDateMinTs,
                    $shippingDateMaxTs,
                    $deliveryDateMinTs,
                    $deliveryDateMaxTs
                )) {
                    $error = true;
                }

                $imported = '';
                $imported .= sprintf('<span style="color:green;display:inline-block;">'.$this->l('Order ID %s(%s) Successfully imported in the Order Tab').'</span>'.$cr, $oID, $newOrderId);
                $imported .= sprintf('<span style="margin-bottom:5px;display: block;">%s: <img src="%sdetails.gif" style="cursor:pointer;" onclick="window.open(\'?tab=AdminOrders&id_order=%s&vieworder&token=%s\')"></span><br />', $this->l('Go to the order in a new window'), $this->images, $newOrderId, $tokenOrders);
                $output[] = $imported;
            } else {
                $output[] = printf($this->l('Order ID %s Skipped').$cr, $oID);

                if (Validate::isLoadedObject($cart)) {
                    $cart->delete();
                }
            }
        }

        if (isset($selected_orders) && is_array($selected_orders)) {
            $selected_orders = array_flip($selected_orders);
        } else {
            $selected_orders = null;
        }

        $console = ob_get_clean();

        $json = Tools::jsonEncode(array(
            'console' => $console,
            'error' => $error,
            'count' => $count,
            'output' => $output,
            'orders' => $selected_orders,
            'errors' => $this->errors
        ));
        if (Configuration::get('CDISCOUNT_EMAIL_LOGS')) {
            if (count($this->errors)) {
                CdiscountMailLogger::message($this->l('Import orders').' - '.date('Y-m-d H:i:s')."\n");
                CdiscountMailLogger::message($this->l('Error while importing orders').":\n\n");
                foreach ($this->errors as $error_msg) {
                    CdiscountMailLogger::message($error_msg);
                }
            }
        }
        echo (string)$callback.'('.$json.')';
        die;
    }

    protected function isVirtualProduct($sku)
    {
        return $sku == 'FRAISTRAITEMENT' || $sku == 'INTERETBCA';
    }

    /**
     * @param array $order
     *
     * @return bool
     */
    private function isFBCOrder($order)
    {
        $customer_firstname = Tools::strtolower($order['Customer']['FirstName']);
        $customer_lastname = Tools::strtolower($order['Customer']['LastName']);

        // check if any order line has price = 1
        $has_price_euqal_1 = false;
        foreach ($order['OrderLineList']['OrderLine'] as $order_line) {
            if ($order_line['PurchasePrice'] == $order_line['Quantity']) {
                $has_price_euqal_1 = true;
                break;
            }
        }

        return (
            $customer_firstname == 'support'
            && $customer_lastname == 'c logistique'
            && isset($order['IsCLogistiqueOrder'])
            && $order['IsCLogistiqueOrder'] == 'true'
            && $has_price_euqal_1
        );
    }
    

    /**
     * @param int $mostDateTimestamp
     * @param string $input Formatted date, currently ISO 8601
     * @param bool $max
     * @return int
     */
    protected function getMostDateTs($mostDateTimestamp, $input, $max)
    {
        $inputTimestamp = strtotime($input);
        if ($inputTimestamp) {
            if (is_int($mostDateTimestamp)) {
                if ($max) {
                    return $mostDateTimestamp >= $inputTimestamp ? $mostDateTimestamp : $inputTimestamp;
                } else {
                    return $mostDateTimestamp <= $inputTimestamp ? $mostDateTimestamp : $inputTimestamp;
                }
            } else {
                return $inputTimestamp;
            }
        }

        return $mostDateTimestamp;
    }

    protected function saveOrderInfo($idOrder, $mpOrderId, $channelId, $channelName, $clogistique, $shipMinTs, $shipMaxTs, $dlvMinTs, $dlvMaxTs)
    {
        $orderInfo = new CDiscountOrderInfo($idOrder, $mpOrderId, $channelId, $channelName, $clogistique);
        $orderInfo->earliest_ship_date = $shipMinTs ? date('Y-m-d H:i:s', $shipMinTs) : null;
        $orderInfo->latest_ship_date = $shipMaxTs ? date('Y-m-d H:i:s', $shipMaxTs) : null;
        $orderInfo->earliest_delivery_date = $dlvMinTs ? date('Y-m-d H:i:s', $dlvMinTs) : null;
        $orderInfo->latest_delivery_date = $dlvMaxTs ? date('Y-m-d H:i:s', $dlvMaxTs) : null;

        if (!$orderInfo->saveOrderInfo()) {
            $this->errors[] = $this->l('Unable to save extra order information') .
                ': (' . nl2br(print_r(get_object_vars($orderInfo), true)) . ')' . $this->_cr;
            return false;
        }

        return true;
    }

    protected function pd()
    {
        require_once dirname(__FILE__).'/../classes/cdiscount.tools_r.class.php';
        
        if (Cdiscount::$debug_mode) {
            $backTrace = debug_backtrace();
            $caller = array_shift($backTrace);
            $fileSegment = explode(DIRECTORY_SEPARATOR, $caller['file']);
            $file = array_pop($fileSegment);

            $debug = array_map(function ($arg) use ($file, $caller) {
                return sprintf('%s(#%d): %s', $file, $caller['line'], $arg);
            }, func_get_args());
            CDiscountToolsR::pre($debug);
        }
    }
}

$marketplaceOrdersImport = new CDiscountOrdersImport;
$marketplaceOrdersImport->dispatch();
