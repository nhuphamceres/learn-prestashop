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
require_once(dirname(__FILE__).'/../classes/context.class.php');
require_once(dirname(__FILE__).'/../classes/tools.class.php');
require_once(dirname(__FILE__).'/../classes/product.class.php');
require_once(dirname(__FILE__).'/../classes/address.class.php');
require_once(dirname(__FILE__).'/../classes/carrier.class.php');
require_once(dirname(__FILE__).'/../classes/pickuppoint.class.php');
require_once(dirname(__FILE__).'/../classes/cart.class.php');
require_once(dirname(__FILE__).'/../classes/order.class.php');
require_once(dirname(__FILE__).'/../classes/orderhistory.class.php');
require_once(dirname(__FILE__).'/../classes/payment.class.php');
require_once(dirname(__FILE__).'/../classes/logger.class.php');
require_once(dirname(__FILE__).'/../classes/mirakl.api.orders.php');

// Sep-25-2018: Use only 1 main class for all marketplaces

class MiraklOrdersImport extends Mirakl
{
    private $errors = array();

    private $password;
    private $debug;
    private $export;

    private static $logContent = '';

    public function __construct()
    {
        $this->debug = Tools::getValue('debug', self::getConfig(self::CONFIG_DEBUG));

        parent::__construct();

        MiraklContext::restore($this->context);

        if (version_compare(_PS_VERSION_, '1.5', '>')) {
            $employee = null;
            $id_employee = Mirakl::getConfig(Mirakl::CONFIG_ID_EMPLOYEE);

            if (!is_numeric($id_employee)) {
                $id_employee = 1;
            }

            if ($id_employee) {
                $employee = new Employee($id_employee);
            }

            if (!Validate::isLoadedObject($employee)) {
                die($this->l('Wrong Employee, please save the module configuration'));
            }

            $this->context->customer->is_guest = true;
            $this->context->customer->id_default_group = (int)Configuration::get('PS_CUSTOMER_GROUP');
            $this->context->cart = new Cart();
            $this->context->employee = $employee;
            $this->context->currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
            $this->context->cart->id_currency = $this->context->currency->id;
            $this->context->cart->id_lang = $this->marketplace_id_lang ? $this->marketplace_id_lang : $this->id_lang;
        } else {
            $default_currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
            $cart = $this->context->cart;
            $cookie = $this->context->cookie;
            $cart->id_currency = $cookie->id_currency = $default_currency->id;
        }

        if ((int)Tools::getValue('id_lang')) {
            $this->id_lang = (int)Tools::getValue('id_lang');
        }

        $this->export = $this->path.'export/';
    }

    public static function logExit()
    {
        $logger = new MiraklLogger(MiraklLogger::CHANNEL_ORDER_IMPORT);
        $logger->debug(self::getLogContent());
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

        $this->password = Mirakl::getConfig(Mirakl::CONFIG_API_KEY);
        $this->debug = (bool)Mirakl::getConfig(Mirakl::CONFIG_DEBUG);

        if ($this->debug) {
            ini_set('display_errors', 'on');
            error_reporting(E_ALL | E_STRICT);
        }

        switch ($action) {
            case 'orders':
                $this->listOrders();
                break;
            case 'import':
                $this->importOrders();
                break;
            case 'cron':
                // todo: Apply for all actions. Override context by new implementation
                MiraklContext::set();
                if ($this->listOrders(true) > 0) {
                    $this->importOrders(true);
                }
                break;
        }
    }

    private function dieOnError($msg)
    {
        echo $msg;
        $output = ob_get_clean().nl2br("\n")."\n";
        $json = Tools::jsonEncode(array('error' => true, 'output' => $output));
        // jQuery Output or PHP Output
        if ($callback = Tools::getValue('callback')) { // jquery
            echo (string)$callback.'('.$json.')';
        } else { // cron
            return $json;
        }
        die;
    }

    private function importOrders($cron = false)
    {
        require_once dirname(__FILE__) . '/../classes/mirakl.mkp.order.php';

        if (Tools::getValue('callback')) {
            self::logContent('Manual import');
        } else {
            self::logContent('Cron import');
        }

        register_shutdown_function(array('MiraklOrdersImport', 'logExit'));

        $id_lang = $this->marketplace_id_lang ? $this->marketplace_id_lang : $this->id_lang;
        $id_shop_group = 1;

        $mirakl_params = self::$marketplace_params;

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $id_shop = null;
        } else {
            if (Shop::isFeatureActive()) {
                $id_shop = (int)Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
                $id_shop_group = $this->context->shop->id_shop_group;
            } else {
                $id_shop = 1;
            }
        }


        $cr = nl2br("\n")."\n";

        if ($cron) {
            $callback = '';
            $selected_orders = array();
            $token_orders = '';
        } else {
            $callback = Tools::getValue('callback');
            $selected_orders = Tools::getValue('selected_orders');
            $token_orders = Tools::getValue('token_order');
        }

        $error = false;
        $count = 0;
        $output = array();

        if (!$cron && !count($selected_orders)) {
            $this->errors[] = sprintf('%s(%s): %s', basename(__FILE__), __LINE__, $this->l('No selected orders')).$cr;

            return false;
        }

        $orders = json_decode(Tools::file_get_contents($this->export.'orders.json'), true);

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

        $use_taxes = (bool)Mirakl::getConfig(Mirakl::CONFIG_USE_TAXES);

        $order_states = Mirakl::getConfig(Mirakl::CONFIG_ORDER_STATES);
        $order_state = $order_states['MIRAKL_CA']; // commande acceptee

        $from_currency = new Currency((int)(Configuration::get('PS_CURRENCY_DEFAULT')));
        $to_currency = $from_currency;

        if (array_key_exists('currency', Mirakl::$marketplace_params) && Tools::strlen(Mirakl::$marketplace_params['currency'])) {
            $to_currency = new Currency(Currency::getIdByIsoCode(Mirakl::$marketplace_params['currency']));

            if (!Validate::isLoadedObject($to_currency)) {
                $this->errors[] = sprintf('%s(%d): %s(%s)', basename(__FILE__), __LINE__, $this->l('Unable to load currency'), Mirakl::$marketplace_params['currency']);
                $error = true;
            }
        }

        if ($from_currency->iso_code != $to_currency->iso_code) {
            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                $this->context->currency = $to_currency;
            } else {
                $cart = $this->context->cart;
                $cookie = $this->context->cookie;
                $cart->id_currency = $cookie->id_currency = $to_currency->id;
            }
        }
        $id_currency = $to_currency->id;

        foreach (array_values($selected_orders) as $key => $oid) {
            $oid = (string)$oid;
            $order_lang_id = $this->id_lang;

            $item_details = array();
            $fees = 0;

            if (!isset($orders[$oid])) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable to read order ID'), $oid).$cr;
                $error = true;
                unset($selected_orders[$key]);
                continue;
            }

            $order = $orders[$oid];

            if ($this->debug) {
                MiraklTools::pre(array("Order:".print_r($order, true)));
            }
            $this->logContent("Order:".print_r($order, true));

            if ((miraklOrder::checkByMpId($oid, $id_shop))) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('This order was already imported'), $oid).$cr;
                $error = true;
                unset($selected_orders[$key]);
                continue;
            }

            if (!count($order['customer']['billing_address'])) {
                $order['customer']['billing_address'] = $order['customer']['shipping_address'];
            }

            if (!count($order['customer']['shipping_address'])) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('This order has no shipping address'), $oid).$cr;
                $error = true;
                unset($selected_orders[$key]);
                continue;
            }

            $date_add = date('Y-m-d H:i:s', strtotime($order['created_date']));

            $customer_firstname = ucwords(Tools::strtolower($order['customer']['firstname']));
            $customer_lastname = ucwords(Tools::strtolower($order['customer']['lastname']));

            $customer_email = null;
            $customer_vat_id = null;

            if (array_key_exists('order_additional_fields', $order) && count($order['order_additional_fields'])) {
                if (!is_int(array_keys($order['order_additional_fields'])[0])) {
                    $order['order_additional_fields'] = array($order['order_additional_fields']);
                }

                foreach ($order['order_additional_fields'] as $additional_field) {
                    if (is_array($additional_field) && array_key_exists('code', $additional_field) &&
                        $additional_field['code'] == 'useremail' && Validate::isEmail($additional_field['value'])) {
                        $customer_email = $additional_field['value'];
                    }

                    if (is_array($additional_field) && array_key_exists('code', $additional_field) &&
                        in_array($additional_field['code'], array('vatid'))) {
                        $customer_vat_id = $additional_field['value'];
                    }
                }
            }

            if (!$customer_vat_id && isset($order['customer']['shipping_address']['additional_info'])) {
                $address_additional_info = $order['customer']['shipping_address']['additional_info'];

                if (Tools::substr($address_additional_info, 0, 4) == 'Nif:') {
                    $customer_vat_id = trim(Tools::substr($address_additional_info, 4));
                }
            }

            if (!$customer_email) {
                if (Tools::strlen(filter_var($order['customer']['customer_id'], FILTER_VALIDATE_EMAIL))) {
                    $customer_email = $order['customer']['customer_id'];
                } elseif (isset($order['customer_notification_email']) &&
                    Tools::strlen(filter_var($order['customer_notification_email'], FILTER_VALIDATE_EMAIL))) {
                    $customer_email = $order['customer_notification_email'];
                } else {
                    $customer_email = sprintf('%s-%s@%s', $order['customer']['customer_id'], Tools::substr(MiraklTools::toKey($mirakl_params['name']), 0, 16), parent::TRASH_DOMAIN);
                }
            }

            $customer_phone = preg_replace('/[^0-9]/', '', isset($order['customer']['shipping_address']['phone']) ? $order['customer']['shipping_address']['phone'] : (isset($order['customer']['billing_address']['phone']) ? $order['customer']['billing_address']['phone'] : null));

            // Customer individual account
            if (!isset($customer_email) || !Validate::isEmail($customer_email)) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Invalid customer email address'), isset($customer_email) ? $customer_email : null).$cr;
                $error = true;
                unset($selected_orders[$key]);
                continue;
            }

            $customer = new Customer((int)Db::getInstance()->getValue(
                'SELECT `id_customer`
                FROM `'._DB_PREFIX_.'customer`
                WHERE `email` = "'.pSQL($customer_email).'"
                AND `is_guest` = 0
                AND `deleted` = 0'
            ));

            if ($customer->id) {
                $id_customer = $customer->id;
            } else {
                $customer->firstname = MiraklTools::ucfirst(miraklAddress::cleanLogin($customer_firstname));
                $customer->lastname = MiraklTools::ucfirst(miraklAddress::cleanLogin($customer_lastname));
                $customer->email = $customer_email;
                $customer->passwd = md5(rand());
                $customer->optin = false;
                $customer->newsletter = false;

                if ($this->debug) {
                    MiraklTools::pre(array("New Customer:".print_r(get_object_vars($customer), true)));
                }

                if (!$customer->validateFields(false, false)) {
                    var_dump($customer->lastname, $customer->validateFields(false, true));
                    $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Couldn\'t add this customer'), $customer_email).$cr;
                    unset($selected_orders[$key]);
                    continue;
                }

                if (!$customer->add()) {
                    $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Couldn\'t add this customer'), $customer_email).$cr;
                    unset($selected_orders[$key]);
                    continue;
                } else {
                    $id_customer = $customer->id;
                }
            }

            $shipping_address = new MiraklAddress();
            $shipping_address->id_customer = $id_customer;

            // Create or get address book entry
            if (!isset($order['customer']['shipping_address']) || !is_array($order['customer']['shipping_address']) || !count($order['customer']['shipping_address'])) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Missing Shipping Address'), $oid).$cr;
                $error = true;
                unset($orders[$oid]);
                continue;
            }

            if (!isset($order['customer']['shipping_address']['firstname']) || empty($order['customer']['shipping_address']['firstname'])) {
                $order['customer']['shipping_address']['firstname'] = $order['customer']['firstname'];
            }
            if (!isset($order['customer']['shipping_address']['lastname']) || empty($order['customer']['shipping_address']['lastname'])) {
                $order['customer']['shipping_address']['lastname'] = $order['customer']['lastname'];
            }

            $shipping_address_id = $shipping_address->lookupOrCreateAddress($order['customer']['shipping_address'], $id_lang, $customer_vat_id);

            if (!isset($order['customer']['billing_address']['firstname']) || empty($order['customer']['billing_address']['firstname'])) {
                $order['customer']['billing_address']['firstname'] = $order['customer']['firstname'];
            }
            if (!isset($order['customer']['billing_address']['lastname']) || empty($order['customer']['billing_address']['lastname'])) {
                $order['customer']['billing_address']['lastname'] = $order['customer']['lastname'];
            }

            $billing_address = new miraklAddress();
            $billing_address->id_customer = $id_customer;
            $billing_address_id = $billing_address->lookupOrCreateAddress($order['customer']['billing_address'], $id_lang, $customer_vat_id);

            if ($this->debug) {
                echo "---------------------------------------------------\n";
                echo "addressId : $shipping_address_id / $billing_address_id \n";
                echo "$customer_firstname $customer_lastname $customer_phone\n";
                echo "\n";
            }

            $carrier_code = isset($order['shipping_type_code']) && !empty($order['shipping_type_code']) ? $order['shipping_type_code'] : null;
            $carrierMkpName = isset($order['shipping_type_label']) ? $order['shipping_type_label'] : '';
            $carrierMkpZoneCode = isset($order['shipping_zone_code']) ? $order['shipping_zone_code'] : '';
            $carrierMkpZoneName = isset($order['shipping_zone_label']) ? $order['shipping_zone_label'] : '';
            $id_carrier = null;
            $pickup_point_id = false;
            $related_carrier_code = null;
            $related_carrier_module = null;
            $related_carrier_field = null;

            $this->logContent(print_r(array(
                'Carrier marketplace name' => $carrierMkpName,
                'Carrier code' => $carrier_code,
                'Carrier marketplace zone name' => $carrierMkpZoneName,
                'Carrier marketplace zone code' => $carrierMkpZoneCode
            ), true));

            if (Tools::strlen($carrier_code) && array_key_exists('carriers', $mirakl_params) && is_array($mirakl_params['carriers']) && array_key_exists($carrier_code, $mirakl_params['carriers'])) {
                // Carrier is a module listed in the config file
                if ($this->debug) {
                    echo "---------------------------------------------------\n";
                    echo "Using Carrier/Module: $carrier_code\n";
                    print_r($mirakl_params['carriers'][$carrier_code]);
                    echo "\n";
                }
                $this->logContent('Using Carrier/Module: '.$carrier_code);
                $this->logContent(print_r($mirakl_params['carriers'][$carrier_code], true));

                $related_carrier = $mirakl_params['carriers'][$carrier_code];

                switch ($related_carrier['module']) {
                    case 'mondialrelay':
                        if (MiraklPickupPoint::isMondialRelayInstalled()) {
                            $id_carrier = MiraklPickupPoint::getMondialRelayCarriedId($related_carrier['code']);

                            if ($id_carrier) {
                                $related_carrier_code = $related_carrier['code'];
                                $related_carrier_field = $related_carrier['field'];
                                $related_carrier_module = $related_carrier['module'];
                            }
                        }
                        break;
                }

                if (!$id_carrier) {
                    $this->errors[] = sprintf('%s/%s:'.$this->l('Shipping method requires an external module "%s", supporting the method "%s"'), basename(__FILE__), __LINE__, $related_carrier['module'], $related_carrier['code']).$cr;
                    $error = true;
                    continue;
                }
            } else {
                // Regular Carrier
                if ($this->debug) {
                    echo "---------------------------------------------------\n";
                    echo "Using Regular Carrier: $carrierMkpName ($carrier_code) - $carrierMkpZoneName ($carrierMkpZoneCode)\n";
                    echo "\n";
                }
                $this->logContent("Using Regular Carrier: $carrierMkpName ($carrier_code) - $carrierMkpZoneName ($carrierMkpZoneCode)");

                $id_carrier = $this->resolvePsCarrier($carrier_code, $carrierMkpName, $carrierMkpZoneCode, $carrierMkpZoneName);

                if (!$id_carrier) {
                    $this->errors[] = sprintf('%s/%s: %s - "%s"', basename(__FILE__), __LINE__, $this->l('Unable to create carrier'), "$carrierMkpName ($carrier_code)").$cr;
                    $this->logContent(sprintf('%s - "%s"', 'Unable to create carrier', "$carrierMkpName ($carrier_code)"));
                    $error = true;
                    continue;
                }
            }

            if (!$id_carrier) {
                $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Missing carrier configuration for orders')).$cr;
                $this->logContent('Missing carrier configuration for orders');
                $error = true;
                continue;
            }

            $this->logContent('Carrier ID: '.$id_carrier);

            // Building Cart
            $cart = new MiraklCart();
            $cart->id_address_delivery = $shipping_address_id;
            $cart->id_address_invoice = $billing_address_id;
            $cart->id_carrier = $id_carrier;
            $cart->id_currency = $id_currency;
            $cart->id_customer = $id_customer;
            $cart->id_shop_group = (int)$id_shop_group;
            $cart->id_shop = (int)$id_shop;

            $order_lang_id = $id_lang;

            $order_billing_address = new Address($cart->id_address_invoice);

            if (Validate::isLoadedObject($order_billing_address)) {
                $address_country = new Country($order_billing_address->id_country);
                if (($address_lang_id = Language::getIdByIso(Tools::strtolower($address_country->iso_code)))) {
                    $order_lang_id = (int)$address_lang_id;
                }
            }

            $cart->id_lang = $order_lang_id;
            $cart->add();

            // total products in the cart
            $total_cart_products = 0;
            $miraklOrder = new MiraklMkpOrder(MiraklMarketplace::getCurrentMarketplace(), $order);

            // Product Loop
            // todo: Migrate to MiraklMkpOrder->getOrderLines()
            foreach ($order['order_lines'] as $item) {
                $miraklOrderItem = new MiraklMkpOrderItem($miraklOrder->priceAlreadyIncludedTax, $item);

                $sku = $item['offer_sku'];
                $order_line_state = isset($item['order_line_state']) ? $item['order_line_state'] : null;

                switch ($order_line_state) {
                    case MiraklApiOrders::STATUS_SHIPPING:
                    case MiraklApiOrders::STATUS_SHIPPED:
                    case MiraklApiOrders::STATUS_RECEIVED:
                        break;
                    default:
                        $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Order state incompatible with import action'), $order_line_state).$cr;
                        $error = true;
                        continue 2;
                }

                if (!isset($item['checked']) || !(int)$item['checked']) {
                    continue;
                }

                $order_line_additional_fields = array();

                if (array_key_exists('order_line_additional_fields', $item) && is_array($item['order_line_additional_fields']) && count($item['order_line_additional_fields'])) {
                    // Order line has extra fields
                    foreach ($item['order_line_additional_fields'] as $order_line_additional_field) {
                        if (is_array($order_line_additional_field) && count($order_line_additional_field)) {
                            if (!array_key_exists('code', $order_line_additional_field)) {
                                continue;
                            }

                            $order_line_additional_field_code = (string)$order_line_additional_field['code'];
                            $order_line_additional_fields[$order_line_additional_field_code] = $order_line_additional_field;
                        }
                    }
                }

                if (Tools::strlen($related_carrier_field) && array_key_exists($related_carrier_field, $order_line_additional_fields) && is_array($order_line_additional_fields[$related_carrier_field]) && Tools::strlen($order_line_additional_fields[$related_carrier_field]['value'])) {
                    $pickup_point_id = (string)$order_line_additional_fields[$related_carrier_field]['value'];
                } elseif (Tools::strlen($related_carrier_field)) {
                    $this->errors[] = sprintf('%s/%s:'.$this->l('Shipping method requires the extra field "%s" to be filled'), basename(__FILE__), __LINE__, $related_carrier_field).$cr;
                    $error = true;
                    continue;
                }

                $identifier = MiraklProduct::getProductBySKU($sku, $id_shop);

                if ($identifier == false && is_array($order_line_additional_fields) && isset($order_line_additional_fields['supplier-sku'])) {
                    $identifier = MiraklProduct::getProductBySKU($order_line_additional_fields['supplier-sku']['value'], $id_shop);
                }

                if ($identifier == null) {
                    $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable to retrieve the SKU for this product'), $sku).$cr;
                    $error = true;
                    continue;
                }

                // Product With Combination
                if (strpos($identifier, '_') !== false) {
                    $split_combination = explode('_', $identifier);
                    $id_product = (int)$split_combination[0];
                    $id_combination = (int)$split_combination[1];
                } else {
                    $id_product = (int)$identifier;
                    $id_combination = false;
                }

                if (!isset($item['quantity']) || !is_numeric($item['quantity'])) {
                    $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Invalid quantity for this order line'), $sku).$cr;
                    $error = true;
                    continue;
                }

                $quantity = $item['quantity'];

                if ($this->debug) {
                    echo "---------------------------------------------------\n";
                    echo nl2br(print_r($item, true));
                    echo "\n";
                }

                // Load Product
                $product = new Product($id_product, true, $order_lang_id);
                if (!Validate::isLoadedObject($product)) {
                    $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable to import product'), $id_product).$cr;
                    $error = true;
                    continue;
                }

                $product_name = $product->name;
                $id_product_attribute = false;

                // Load Combination
                if ($id_combination) {
                    if (version_compare(_PS_VERSION_, '1.5', '<')) {
                        $combinations = $product->getAttributeCombinaisons($order_lang_id);
                    } else {
                        $combinations = $product->getAttributeCombinations($order_lang_id);
                    }

                    if ($combinations) {
                        foreach ($combinations as $combination) {
                            if ($combination['id_product_attribute'] == $id_combination) {
                                $id_product_attribute = $combination['id_product_attribute'];
                            }
                        }
                    }

                    if (!$id_product_attribute) {
                        $this->errors[] = sprintf('%s : %d (%d)', $this->l('Couldn\'t match product attributes for product'), $id_product, $id_product_attribute);
                        $error = true;
                        continue;
                    }
                }

                if ($use_taxes) {
                    if (method_exists('Tax', 'getProductTaxRate')) {
                        $product_tax_rate = (float)Tax::getProductTaxRate($product->id, $shipping_address_id);
                    } else {
                        $product_tax_rate = (float)Tax::getApplicableTax($product->id_tax, $product->tax_rate, $shipping_address_id);
                    }
                } else {
                    $product_tax_rate = 0;
                }

                $tryAddProductToCart = $cart->updateQty($quantity, $id_product, $id_product_attribute);
                if (!$tryAddProductToCart || $tryAddProductToCart < 0) {
                    $this->errors[] = sprintf('%s : ID: %d/%d - %s', $this->l('Not enough stock for this product'), $id_product, $id_product_attribute, $product_name);
                    $error = true;
                    $this->deleteCart($cart);
                    continue 2; // Break out this order too
                }

                $total_cart_products_new = $cart->nbProducts();
                // after added this product to cart. if total products > total products before + product's quantity
                // that means there is a gift product added by the cart rule automatically
                if ($total_cart_products_new != $total_cart_products + $quantity) {
                    $cart->has_gift_product = true;
                }

                $product_identifier = sprintf('%d_%d', $id_product, $id_product_attribute);

                if (isset($item_details[$product_identifier])) {
                    $item_details[$product_identifier]['qty'] += $quantity;
                } else {
                    // todo: Migrate all to $miraklOrderItem
                    $item_details[$product_identifier] = array(
                        'id_product' => $id_product,
                        'qty' => $quantity,
                        'sku' => $sku,
                        'price' => $miraklOrderItem->getPriceTaxIncl(),
                        'name' => $product_name,
                        'tax_rate' => $product_tax_rate,
                        'id_address_delivery' => $shipping_address_id,
                    );
                    if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                        $item_details[$product_identifier]['id_shop'] = (int)$id_shop;
                    }
                }

                $total_cart_products = $total_cart_products_new;
            } // foreach products line

            if (!count($item_details)) {
                $this->errors[] = $this->l('Cart empty, could not save order').' ('.$oid.')'.$cr;
                $error = true;

                if (Validate::isLoadedObject($cart)) {
                    $cart->delete();
                }

                continue;
            }

            // Using price, shipping details etc... from the Market Place
            $cart->me_products = $item_details;
            $cart->me_shipping = $miraklOrder->getShippingPriceTaxIncl();
            $cart->me_date = $date_add;
            $cart->me_fees = $fees;
            $cart->marketplace_key = self::$marketplace_key;
            $cart->id_shop_group = (int)$id_shop_group;
            $cart->id_shop = (int)$id_shop;

            // duplication du panier, important !!!
            $acart = $cart;

            $payment = new MiraklPaymentModule();

            $new_order_id = $payment->validateOrder(
                $cart->id,
                $order_state,
                $cart->getMiraklOrderTotal(3),
                self::$marketplace_params['name'],
                null,
                array(),
                $cart->id_currency,
                false,
                false,
                null,
                $oid,
                $order_state,
                $acart,
                $order['shipping_deadline']
            );

            if ($new_order_id) {
                Mirakl::updateConfig(Mirakl::CONFIG_LAST_IMPORT, date('Y-m-d H:i:s'), true);
                $count++;
            } else {
                $this->errors[] = sprintf($this->l('1 or more error occurs, unable to import order ID : %s'), $oid);
                $error = true;

                if (Validate::isLoadedObject($cart)) {
                    $cart->delete();
                }

                unset($selected_orders[$key]);
            }

            if ($new_order_id) {
                // Pickup Point Import - debuss-a
                if ($pickup_point_id) {
                    $address = &$order['customer']['shipping_address'];
                    $name = sprintf('%s %s %s', trim($address['civility']), trim($address['firstname']), trim($address['lastname']));

                    $pickup_point = new MiraklPickupPoint();
                    $pickup_point->id = $pickup_point_id;
                    $pickup_point->name = $name;
                    $pickup_point->address1 = trim($address['street_1']);
                    $pickup_point->zipcode = trim($address['zip_code']);
                    $pickup_point->city = trim($address['city']);
                    $pickup_point->country = trim($address['country']);

                    if (array_key_exists('phone_secondary', $address)) {
                        $pickup_point->phone = trim($address['phone_secondary']);
                    }

                    if (!Tools::strlen($pickup_point->phone)) {
                        $pickup_point->phone = trim($address['phone']);
                    }

                    $pickup_point->id_order = (int)$new_order_id;
                    $pickup_point->id_customer = (int)$cart->id_customer;
                    $pickup_point->id_cart = (int)$cart->id;

                    switch ($related_carrier_module) {
                        case ('mondialrelay'):
                            if (MiraklPickupPoint::isMondialRelayInstalled()) {
                                $pickup_point->pickup_type = MiraklPickupPoint::MONDIAL_RELAY_TYPE;
                                $pickup_point->id_method = $related_carrier_code;

                                if (!$pickup_point->save()) {
                                    printf('%s(%s): %s (%s)', basename(__FILE__), __LINE__, $this->l('Failed to save Mondial Relay table entry for order'), $new_order_id).$cr;
                                    print nl2br(print_r($pickup_point, true));
                                }
                            }
                            break;

                        case ('socolissimo'):
                            if (MiraklPickupPoint::isSoColissimoInstalled()) {
                                $pickup_point->pickup_type = MiraklPickupPoint::SO_COLISSIMO_TYPE;
                                $pickup_point->email = trim($customer->email) ? trim($customer->email) : 'no-reply@nomail.fr';

                                if (!$pickup_point->save()) {
                                    printf('%s(%s): %s (%s)', basename(__FILE__), __LINE__, $this->l('Failed to save So Colissimo table entry for order'), $new_order_id).$cr;
                                    print nl2br(print_r($pickup_point, true));
                                }
                            }
                            break;

                        default:
                            $this->errors[] = sprintf($this->l('Unknown Pickup Point Delivery Method for the order #%s'), $new_order_id);
                            break;
                    }
                }

                // Order additionnal fields
                if (isset($order['order_additional_fields']) && is_array($order['order_additional_fields'])) {
                    foreach ($order['order_additional_fields'] as $order_additional_field) {
                        if (!is_array($order_additional_field)) {
                            // only 1 additional field
                            Db::getInstance()->insert('mirakl_order_additional_fields', array(
                                'id_order' => $new_order_id,
                                'code' => $order['order_additional_fields']['code'],
                                'value' => $order['order_additional_fields']['value']
                            ));
                            break;
                        }

                        // Many additional fields
                        Db::getInstance()->insert('mirakl_order_additional_fields', array(
                            'id_order' => $new_order_id,
                            'code' => $order_additional_field['code'],
                            'value' => pSQL($order_additional_field['value']),
                        ));
                    }
                }

                $imported = '';
                $imported .= sprintf(
                    html_entity_decode('&lt;span style="color:green;display:inline-block;margin-bottom:5px;"&gt;')
                    .$this->l('Order ID %s(%s) Successfully imported in the Order Tab')
                    .html_entity_decode('&lt;/span&gt;').$cr,
                    $oid,
                    $new_order_id
                );

                if (!$cron) {
                    $imported .= sprintf(
                        html_entity_decode('%s: &lt;img src="%sdetails.gif" style="cursor:pointer;" onclick="window.open(\'?tab=AdminOrders&id_order=%s&vieworder&token=%s\')"&gt;').nl2br("\n"),
                        $this->l('Go to the order in a new window'),
                        $this->images,
                        $new_order_id,
                        $token_orders
                    );
                }

                $output[] = $imported;
            } else {
                $output[] = printf($this->l('Order ID %s Skipped').$cr, $oid);
            }
        } // foreach orders

        $console = null;

        if (!$cron) {
            $console = ob_get_clean();
        } else {
            echo print_r($this->errors, true);
            echo print_r($output, true);
            @ob_end_flush();
        }
        if (isset($selected_orders) && is_array($selected_orders)) {
            $selected_orders = array_flip($selected_orders);
        } else {
            $selected_orders = null;
        }

        $json = Tools::jsonEncode(
            array(
                'console' => $console,
                'error' => $error,
                'count' => $count,
                'output' => $output,
                'orders' => $selected_orders,
                'errors' => $this->errors
            )
        );

        echo (string)$callback.'('.$json.')';
        die;
    }

    private function shortenCarrierName($name1, $name2 = null)
    {
        if (Tools::strlen($name2)) {
            $name = sprintf('%s (%s)', $name1, $name2);

            if (Tools::strlen($name) > 64) {
                if (strpos($name, ')') !== false) {
                    $cut = Tools::substr($name, 0, 60);
                    $name = sprintf('%s...)', $cut);

                    return $name;
                } else {
                    $cut = Tools::substr($name, 0, 61);
                    $name = sprintf('%s...', $cut);

                    return $name;
                }
            }
        } elseif (Tools::strlen($name1) > 64) {
            $cut = Tools::substr($name1, 0, 61);
            $name = sprintf('%s...', $cut);
        } else {
            $name = $name1;
        }

        return $name;
    }

    private function listOrders($cron = false)
    {
        $cr = nl2br("\n")."\n";
        $error = false;
        $action = null;
        $output = array();

        $id_lang = $this->id_lang;
        $id_shop = 1;

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $id_shop = null;
        } else {
            if (Shop::isFeatureActive()) {
                $id_shop = (int)Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
            } else {
                $id_shop = 1;
            }
        }
        if ($cron) {
            $date_start = date('Y-m-d', strtotime('now -3 days'));
            $orders_statuses = 'Importable';
            $token_order = '';
            $callback = '';
        } else {
            $date_start = date('Y-m-d', strtotime(Tools::getValue('datepickerFrom')));
            $orders_statuses = Tools::getValue('orders-statuses');
            $token_order = Tools::getValue('token_order');
            $callback = Tools::getValue('callback');
        }

        $id_warehouse = null;

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                $id_warehouse = Mirakl::getConfig(Mirakl::CONFIG_WAREHOUSE);
            }

            if (empty($id_warehouse) || !is_numeric($id_warehouse)) {
                $id_warehouse = null;
            }
        }

        $mirakl_params = self::$marketplace_params;
        $mirakl_params['debug'] = $this->debug;
        $mirakl_params['api_key'] = Mirakl::getConfig(Mirakl::CONFIG_API_KEY);

        $mirakl = new MiraklApiOrders($mirakl_params);

        if (!is_dir($this->export)) {
            if (!mkdir($this->export)) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable to create output directory'), $this->export).$cr;
                $error = true;
            }
        }

        $result = array();

        if (!Validate::isDate($date_start)) {
            $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('You must select a start date..')).$cr;
            $error = true;
        } else {
            if (Tools::getValue('orders-statuses') == 'All') {
                $params = array(
                    'order_state_codes' => null,
                    'start_date' => $date_start,
                    'paginate' => "false"
                );
            } else {
                //states must be according to=> "order_line_state":
                $params = array(
                    'order_state_codes' => null,
                    'start_date' => $date_start,
                    'paginate' => "false"
                );
            }

            $response = $mirakl->orders($params);
            if (empty($response)) {
                if ($this->debug) {
                    printf('%s(#%d): %s - %s', basename(__FILE__), __LINE__, $this->l('Web/Service Error'), print_r($response, true));
                }

                $this->errors[] = sprintf('%s', $this->l('Web/Service Error')).$cr;
                $error = true;
            }

            $total_count = 0;
            $xml = simplexml_load_string($response);

//             var_dump($xml->asXML());die;

            if (!$xml instanceof SimpleXMLElement) {
                if ($this->debug) {
                    printf('%s(#%d): %s', basename(__FILE__), __LINE__, print_r($response, true));
                }

                $this->errors[] = sprintf('%s', $this->l('XML Error')).$cr;
                $error = true;
                $result = array();
                $total_count = 0;
            } else {
                $result = $xml->xpath('//mirakl_orders/orders');

                $xcount = $xml->xpath('//mirakl_orders/total_count');

                if (is_array($xcount) && count($xcount)) {
                    $total_count = (int)reset($xcount);
                }
            }

            if ($total_count == 0) {
                $this->errors[] = sprintf('%s', $this->l('No new order to import')).$cr;
                $result = array();
                $error = true;
            }

            $orders = array();
            $order_list = array();

            foreach (array_reverse($result) as $order) {
                $id_order = null;
                $oid = (string)$order->order_id;

                $order_datetime = MiraklTools::displayDate(date('Y-m-d H:i:s', strtotime((string)$order->created_date)), $this->id_lang, true);
                $order_name = (string)$order->customer->firstname.' '.(string)$order->customer->lastname;
                $disabled = null;
                $can_be_imported = true;

                if (($existing = miraklOrder::checkByMpId($oid, $id_shop))) {
                    if ($orders_statuses != 'All') {
                        continue;
                    }

                    $disabled = ' disabled="disabled"';
                    $can_be_imported = false;
                    $id_order = $existing['id_order'];
                }

                // order_lines -> total_commission
                switch ($order->order_state) {
                    case 'STAGING':
                    case 'WAITING_ACCEPTANCE':
                    case 'WAITING_DEBIT':
                    case 'WAITING_DEBIT_PAYMENT':
                    case 'REFUSED':
                    case 'CANCELED':
                    case 'INCIDENT_OPEN':
                    case 'WAITING_REFUND':
                    case 'WAITING_REFUND_PAYMENT':
                    case 'REFUNDED':
                        $this->errors[] = sprintf(
                            '%s/%s: %s: "%s"',
                            basename(__FILE__),
                            __LINE__,
                            $this->l('Order is not importable, order state'),
                            (string)$order->order_state
                        ).$cr;
                        $error = true;
                        continue 2;
                }

                $products_row = 0;
                $order_detail = array();

                if (isset($order->order_lines) && $order->order_lines instanceof SimpleXMLElement) {
                    foreach ($order->order_lines as $orderlist) {
                        if (isset($orderlist) && !empty($orderlist)) {
                            $products_row += 1;
                        }

                        $pass = true;

                        $id_product_attribute = null;

                        $identifier = MiraklProduct::getProductBySKU((string)$orderlist->offer_sku, $id_shop);

                        if ($identifier == false && isset($orderlist->order_line_additional_fields)) {
                            foreach ($orderlist->order_line_additional_fields as $field) {
                                if (((string)$field->code) == 'supplier-sku') {
                                    $identifier = MiraklProduct::getProductBySKU((string)$field->value, $id_shop);
                                    break;
                                }
                            }
                        }

                        if ($identifier == false) {
                            $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Unable to retrieve the SKU for this product'), $orderlist->offer_sku).$cr;
                            $error = true;
                            $pass = false;
                        }

                        // Product With Combination
                        if (strpos($identifier, '_') !== false) {
                            $split_combination = explode('_', $identifier);
                            $id_product = (int)$split_combination[0];
                            $id_product_attribute = (int)$split_combination[1];
                        } else {
                            $id_product = (int)$identifier;
                            $id_product_attribute = false;
                        }

                        if ($pass) {
                            $product = new Product($id_product, false, $id_lang);

                            if (!Validate::isLoadedObject($product)) {
                                $this->errors[] = sprintf('%s/%s: %s (%s - %s)', basename(__FILE__), __LINE__, $this->l('Unable to find this product'), $id_product, $orderlist->offer_sku).$cr;
                                $error = true;
                                $pass = false;
                            }

                            if (!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                                $quantity = 999;
                            } elseif (version_compare(_PS_VERSION_, '1.5', '>=')) {
                                $quantity = (int)Product::getRealQuantity($id_product, $id_product_attribute ? $id_product_attribute : null, $id_warehouse, $id_shop);
                            } else {
                                $quantity = (int)MiraklProduct::getProductQuantity($id_product, $id_product_attribute);
                            }
                        } else {
                            $quantity = 0;
                        }

                        $orderlist->checked = false;
                        $checked = '';

                        if ($pass && !$existing && $quantity) {
                            if ($can_be_imported && $orderlist->order_line_state == 'STAGING') {
                                $checked = 'checked="checked"';
                            } else {
                                $checked = '';
                            }
                            $orderlist->checked = true;
                        }

                        $orderLineState = MiraklTools::ucwords(str_replace('_', ' ', (string)$orderlist->order_line_state));

                        $order_detail[] = array(
                            'pass' => $pass,
                            'existing' => $existing,
                            'ps_qty' => $quantity,
                            'order_qty' => $orderlist->quantity,
                            'offer_sku' => $orderlist->offer_sku,
                            'products_row' => $products_row,
                            'product_sku' => $orderlist->product_sku,
                            'product_title' => $orderlist->product_title,
                            'orderLineState' => $orderLineState,
                            'shipping_price' => $orderlist->shipping_price,
                            'price' => $orderlist->price
                        );
                    }
                }

                $this->context->smarty->assign(array(
                    'image_path' => $this->images,
                    'id_order' => $id_order,
                    'token_order' => $token_order,
                    'oid' => $oid,
                    'disabled' => $disabled,
                    'checked' => $checked,
                    'order_datetime' => $order_datetime,
                    'order_name' => $order_name,
                    'customer_id' => $order->customer->customer_id,
                    'payment_type' => $order->payment_type,
                    'shipping_price' => $order->shipping_price,
                    'shipping_deadline' => $order->shipping_deadline,
                    'total_price' => $order->total_price,
                    'details' => $order_detail
                ));
                $orders[$oid] = $this->context->smarty->fetch($this->path.'views/templates/admin/orders/order_import_list.tpl');

                $products = $order->xpath('order_lines');

                if (!is_array($products)) {
                    $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('No products in this order...')).$cr;
                    $error = true;
                }

                $order_list[$oid] = MiraklTools::xml2array($order);


                $order_list[$oid] = $order_list[$oid];
                $order_list[$oid]['order_lines'] = array();

                foreach ($products as $product) {
                    if ($product->checked) {
                        $order_list[$oid]['order_lines'][] = MiraklTools::xml2array($product);
                    }
                }

                if (!$order_list[$oid]) {
                    $this->errors[] = sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Unable to convert xml to array...')).$cr;
                    $error = true;
                }
            }
            $fileout = $this->export.'orders.json';

            if (file_exists($fileout) && !is_writeable($fileout)) {
                $this->errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('File is not writeable, please verify directory permissions'), $fileout).$cr;
                $error = true;
            }

            if (file_put_contents($fileout, json_encode($order_list)) === false) {
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
        }

        $console = ob_get_clean();

        $json = Tools::jsonEncode(
            array(
                'stdout' => $console,
                'output' => $output,
                'error' => $error,
                'result' => $result,
                'orders' => $orders,
                'errors' => $this->errors
            )
        );

        if (!$cron) {
            echo (string)$callback.'('.$json.')';
            die;
        } else {
            return count($orders);
        }
    }

    /**
     * @param $carrierCode
     * @param $carrierName
     * @param $zoneCode
     * @param $zoneName
     * @return false|int
     */
    protected function resolvePsCarrier($carrierCode, $carrierName, $zoneCode, $zoneName)
    {
        $carrierMappings = Mirakl::getConfig(MiraklConstant::CONFIG_CARRIER_INCOMING_MAPPING);
        if ($carrierMappings && is_array($carrierMappings)) {
            foreach ($carrierMappings as $carrierMapping) {
                $mkpCode = $carrierCode . ($zoneCode ? "::$zoneCode" : '');
                if ($carrierMapping['mkp'] == $mkpCode && $carrierMapping['ps']) {
                    $carrier = new Carrier($carrierMapping['ps']);
                    if (Validate::isLoadedObject($carrier) && !$carrier->deleted) {
                        return $carrier->id;
                    }
                }
            }
        }

        // Create / retrieve carrier by its name in marketplace
        return MiraklCarrier::lookupOrCreateCarrier($this->shortenCarrierName($carrierName, $zoneName));
    }

    /**
     * @param MiraklCart $cart
     */
    private function deleteCart($cart)
    {
        if (Validate::isLoadedObject($cart)) {
            $cart->delete();
        }
    }
    private static function logContent($log)
    {
        self::$logContent .= $log . Mirakl::LF;
    }
    
    private static function getLogContent()
    {
        $logContent = self::$logContent;
        self::$logContent = '';
        return $logContent;
    }
}

$mirakl_orders_import = new MiraklOrdersImport;
$mirakl_orders_import->dispatch();
