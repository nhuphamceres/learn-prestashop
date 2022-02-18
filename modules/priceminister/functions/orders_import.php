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

//ini_set('display_errors', true);
//error_reporting(E_ALL|E_STRICT);

if (isset($_SERVER['DropBox']) && $_SERVER['DropBox']) {
    require_once(readlink(dirname($_SERVER['SCRIPT_FILENAME']).'/../../../config/config.inc.php'));
    require_once(readlink(dirname($_SERVER['SCRIPT_FILENAME']).'/../../../init.php'));
} else {
    @require_once(dirname(__FILE__).'/../../../config/config.inc.php');
    @require_once(dirname(__FILE__).'/../../../init.php');
}

require_once(dirname(__FILE__).'/../priceminister.php');
require_once(dirname(__FILE__).'/../classes/priceminister.api.webservices.php');
require_once(dirname(__FILE__).'/../classes/priceminister.sales.api.php');
require_once(dirname(__FILE__).'/../classes/priceminister.tools.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.cart.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.order.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.product.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.orderhistory.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.payment.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.address.class.php');
require_once(dirname(__FILE__).'/../classes/priceminister.context.class.php');

class PriceMinisterOrderImport extends PriceMinister
{

    public static $errors = array();
    public static $warnings = array();
    public static $messages = array();
    public static $orders = array();
    public static $id_warehouse = 0;

    protected $psShippingFromCountry;

    public function __construct()
    {
        parent::__construct();

        PriceMinisterContext::restore($this->context);

        // Set the correct shop context in the global context
        // Usefull for function to get image or stock for exemple
        if ($this->context->shop && Validate::isLoadedObject($this->context->shop)) {
            Context::getContext()->shop = $this->context->shop;
        }

        parent::loadGeneralModuleConfig();

        if (Tools::getValue('debug')) {
            $this->debug = true;
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }

        $this->credentials = unserialize(Configuration::get(PriceMinister::CONFIG_PM_CREDENTIALS));
    }

    public static function JSON_Display_Exit()
    {
        $result = trim(ob_get_clean());

        if (!empty($result)) {
            PriceMinisterOrderImport::$warnings[] = trim($result);
        }

        $json = Tools::jsonEncode(
            array(
                'order' => count(PriceMinisterOrderImport::$orders),
                'orders' => PriceMinisterOrderImport::$orders,
                'count' => count(PriceMinisterOrderImport::$orders),
                'error' => (count(PriceMinisterOrderImport::$errors) ? true : false),
                'errors' => PriceMinisterOrderImport::$errors,
                'warning' => (count(PriceMinisterOrderImport::$warnings) ? true : false),
                'warnings' => PriceMinisterOrderImport::$warnings,
                'message' => count(PriceMinisterOrderImport::$messages),
                'messages' => PriceMinisterOrderImport::$messages
            )
        );

        if (($callback = Tools::getValue('callback'))) { // jquery
            echo (string)$callback.'('.$json.')';
        } else {
            echo "<pre>\n";
            echo PriceMinisterTools::jsonPrettyPrint($json);
            echo "<pre>\n";
        }
    }

    public function Dispatch()
    {
        ob_start();
        register_shutdown_function(array('PriceMinisterOrderImport', 'JSON_Display_Exit'));

        // Check Access Tokens
        $pm_token = Configuration::get(PriceMinister::CONFIG_PM_CRON_TOKEN);

        if (!$this->credentials['test'] && $pm_token != Tools::getValue('pm_token')) {
            self::$errors[] = $this->l('Wrong Token');
            die;
        }
        
        $this->initConfiguration();

        $cron = Tools::getValue('cron', 0);

        switch (Tools::getValue('action')) {
            case 'accept':
                if ($cron) {
                    $last_accept = Configuration::get('PM_LAST_ACCEPT');
                    $diff_from_last_accept = time() - strtotime($last_accept);

                    if ($diff_from_last_accept <= 600) {
                        self::$warnings[] = sprintf(
                            $this->l('The call to this web service is limited to 1 per 10 minutes. Please try again in %s seconds.'),
                            600 - (int)$diff_from_last_accept
                        );
                        die;
                    }

                    if ($sales = $this->OrderList('accept')) {
                        $this->OrdersAccept($sales, $cron);
                    }
                } else {
                    if ($sales = $this->OrderSelection('accept')) {
                        $this->OrdersAccept($sales, $cron);
                    }
                }
                break;

            case 'import':
                if ($cron) {
                    $last_import = Configuration::get('PM_LAST_IMPORT');
                    $diff_from_last_import = time() - strtotime($last_import);

//                    if ($diff_from_last_import <= 600) {
//                        self::$warnings[] = sprintf(
//                            $this->l('The call to this web service is limited to 1 per 10 minutes. Please try again in %s seconds.'),
//                            600 - (int)$diff_from_last_import
//                        );
//                        die;
//                    }

                    if ($sales = $this->OrderList('import')) {
                        $this->OrdersImport($sales, $cron);
                    }
                } else {
                    if ($sales = $this->OrderSelection('import')) {
                        $this->OrdersImport($sales);
                    }
                }
                break;
        }
    }
    
    protected function initConfiguration()
    {
        $orderConfig = parent::getConfig(PriceMinister::CONFIG_PM_ORDERS);
        $this->psShippingFromCountry = isset($orderConfig['shippingfromcountry']) ? $orderConfig['shippingfromcountry'] : '';
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    public function OrderList($method)
    {
        $dateStart = date('Y-m-d', time() - 604800);

        $config_credentials = parent::getConfig(PriceMinister::CONFIG_PM_CREDENTIALS);

        // PM Configuration
        $config = PriceMinisterTools::Auth();

        if (!is_array($config)) {
            self::$errors[] = $this->l('Unexpected error, missing configuration');
            die;
        }

        // Orders Output Settings
        $config['output'] = 'xml';

        // PM Settings
        $params = array();
        $params['debug'] = $this->debug;
        $params['field'] = '';

        $pmSales = new PM_Sales($config);

        if (Validate::isDate($dateStart)) {
            $params['purchasedate'] = $dateStart;
        }

        if ($config_credentials['test']) {
            $record = 0;
            # Record
            if (isset($record) && $record) {
                if ($method == 'accept') {
                    $result_xml_tag = 'getnewsalesresult';
                    $sales = $pmSales->getnewsales($params);
                } else {
                    $result_xml_tag = 'getcurrentsales';
                    $sales = $pmSales->getcurrentsales($params);
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

                $xml_string = PriceMinisterTools::file_get_contents('orders'.$method.'.xml');
                $sales = simplexml_load_string($xml_string, null, LIBXML_NOCDATA);
            }
        } elseif ($method == 'accept') {
            $result_xml_tag = 'getnewsalesresult';
            $sales = $pmSales->getnewsales($params, false);
        } elseif ($method == 'import') {
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
            return ($sales);
        } else {
            self::$messages[] = sprintf('%s', $this->l('No new orders'));
            die;
        }
    }

    public function OrdersAccept($sales, $cron = false)
    {
        $count = 0;
        $token_order = Tools::getValue('token_order');

        $config_parameters = parent::getConfig(PriceMinister::CONFIG_PM_PARAMETERS);
        $config_credentials = parent::getConfig(PriceMinister::CONFIG_PM_CREDENTIALS);

        $import_method = $config_parameters['import_method'];
        $id_warehouse = (int)$config_parameters['warehouse'];

        if (!$sales instanceof SimpleXMLElement) {
            self::$errors[] = 'Wrong argument passed to the function';
            die;
        }

        $sales_array = $sales->xpath('/getnewsalesresult/response/sales/sale');

        if (!is_array($sales_array) || !count($sales_array)) {
            self::$warnings[] = $this->l('No order to import');
            die;
        }
        PriceMinisterOrderImport::$orders = array();

        foreach ($sales_array as $sale) {
            $pass = true;
            $id_customer_order = null;

            if (!$cron && $sale->importable != '1') {
                continue;
            }

            $id_order = null;
            $purchaseid = trim((string)$sale->purchaseid);

            PriceMinisterOrderImport::$orders[$purchaseid] = array();
            PriceMinisterOrderImport::$orders[$purchaseid]['purchaseid'] = $purchaseid;
            PriceMinisterOrderImport::$orders[$purchaseid]['status'] = false;

            if (($mp_order = PriceMinisterOrder::checkByMpId($purchaseid))) {
                self::$warnings[] = $this->l('This order has been already imported').' : '.$mp_order['mp_order_id'].'('.$mp_order['id_order'].')';
                $id_order = $mp_order['id_order'];
                continue;
            }

            PriceMinisterOrderImport::$orders[$purchaseid]['items'] = array();

            foreach ($sale->items->item as $item) {
                $sku = trim((string)$item->sku);
                $itemid = trim((string)$item->itemid);
                $quantity = 1;

                // If is negotiation, do not accept the order
                if ((string)$item->isnego == 'Y') {
                    self::$messages[] = sprintf(
                        $this->l('Order %s / Product %s : Not accepted because the item price is actually in negociation, please accept (or not) the negociation directly on RakutenFrance.'),
                        $purchaseid,
                        $sku
                    );
                    continue;
                }

                PriceMinisterOrderImport::$orders[$purchaseid]['items'][$itemid] = array();
                PriceMinisterOrderImport::$orders[$purchaseid]['items'][$itemid]['itemid'] = $itemid;
                PriceMinisterOrderImport::$orders[$purchaseid]['items'][$itemid]['status'] = false;
                PriceMinisterOrderImport::$orders[$purchaseid]['items'][$itemid]['sku'] = $sku;

                if (!in_array((string)$item->itemstatus, array('TO_CONFIRM', 'REQUESTED'))) {
                    self::$errors[] = 'Status Error : '.(string)$item->itemstatus;
                    $pass = false;
                    continue;
                }
                if ($item->paymentstatus != 'INCOMING') {
                    self::$errors[] = 'Payment Status Error : '.(string)$item->paymentstatus;
                    $pass = false;
                    continue;
                }

                if (!($product = $this->productLoad($sku, $import_method))) {
                    $pass = false;
                    continue;
                }

                if (version_compare(_PS_VERSION_, '1.5', '<')) {
                    $productQuantity = Product::getQuantity((int)$product->id, $product->id_product_attribute ? $product->id_product_attribute : null);
                } else {
                    $productQuantity = Product::getRealQuantity(
                        $product->id,
                        $product->id_product_attribute && $product->id_product_attribute != $product->id ?
                            $product->id_product_attribute : null,
                        $id_warehouse
                    );
                }

                // Temp fix not stock
                $force_import = true;
                if (!$force_import && $productQuantity - $quantity < 0) {
                    self::$errors[] = sprintf('%s ID: %s SKU: %s', $this->l('Not enough stock for this product'), $product->id, $sku);
                    $pass = false;
                    continue;
                }

                PriceMinisterOrderImport::$orders[$purchaseid]['items'][$itemid]['status'] = true;
            } // end of foreach items

            if ($pass && isset(PriceMinisterOrderImport::$orders[$purchaseid]['items']) && count(PriceMinisterOrderImport::$orders[$purchaseid]['items'])) {
                foreach (PriceMinisterOrderImport::$orders[$purchaseid]['items'] as $item) {
                    $sale->checked = false;

                    if (!$config_credentials['test']) {
                        $config = PriceMinisterTools::Auth();
                        $config['output'] = 'json';

                        $aSales = new PM_Sales($config);
                        $result = $aSales->acceptsale(array(
                            'itemid' => $item['itemid'],
                            'shippingfromcountry' => $this->psShippingFromCountry,
                        ));

                        if ($result) {
                            $sale->checked = true;
                        }
                    } else {
                        $sale->checked = true;
                    }
                }
            }

            if ($id_order && !empty($token_order)) {
                $url = '?tab=AdminOrders&id_order='.$id_order.'&vieworder&token='.$token_order;
                $order_link = '<a href="'.$url.'" title="" target="_blank" >'.$purchaseid.' ('.$id_order.')</a>';

                PriceMinisterOrderImport::$orders[$purchaseid]['link'] = $order_link;
            } else {
                PriceMinisterOrderImport::$orders[$purchaseid]['link'] = null;
            }

            if ($pass && $sale->checked) {
                PriceMinisterOrderImport::$orders[$purchaseid]['status'] = true;
            } else {
                PriceMinisterOrderImport::$orders[$purchaseid]['status'] = false;

                self::$warnings[] = sprintf('%s: %s', $this->l('Order has not been accepted'), $purchaseid);
            }
            $count++;
        }

        if ($count) {
            Configuration::updateValue('PM_LAST_ACCEPT', date('Y-m-d H:i:s'));
            if ($count == 1) {
                self::$messages[] = $this->l('One order successfully accepted');
            } else {
                self::$messages[] = sprintf('%d %s', $count, $this->l('orders successfully accepted'));
            }
        } else {
            self::$warnings[] = $this->l('No order to accept');
        }
    }

    public function productLoad($sku, $import_method = 'SKU')
    {
        if (empty($sku)) {
            if ($this->debug) {
                echo 'Missing SKU - line:'.__LINE__;
            }

            return (false);
        }

        // Product Combinations
        $id_product_attribute = false;
        $id_combination = false;

        if ($this->credentials['test']) {
            $product_item = PriceMinisterProductExt::getProductBySKUDemo();
            $product_info = explode('_', $product_item);
            $id_product = $product_info[0];
            $id_product_attribute = isset($product_info[1]) ? $product_info[1] : null;
        } else {
            if ($import_method == 'SKU') {
                $product_item = PriceMinisterProductExt::getBySKU($sku, $this->context->shop);

                if (!$product_item) {
                    self::$errors[] = $this->l('Unable to find this product, reference(sku)').' : '.$sku.' - line ('.__LINE__.')';

                    return (false);
                }
                $id_product = $product_item->id_product;
                $id_product_attribute = $product_item->id_product_attribute;
            } elseif ($import_method == 'ID') {
                // Product has Combination
                if (strpos($sku, '_')) {
                    $split_combination = explode('_', $sku);
                    $id_product = (int)$split_combination[0];
                    $id_product_attribute = (int)$split_combination[1];
                } else {
                    $id_product_attribute = false;
                    $id_product = (int)$sku;
                }
            } else {
                $split_combination = explode('c', $sku);

                $id_product = (int)Tools::substr($split_combination[0], 1); // Remove the 'p'
                $id_product_attribute = (int)$split_combination[1]; // get the c (removed by explode)
            }
        }

        // Load Product
        $product = new Product($id_product, $this->id_lang);

        if (!Validate::isLoadedObject($product)) {
            self::$errors[] = 'Unable to load product : '.$id_product.' line ('.__LINE__.')';

            return (false);
        }

        // Load Combination
        if ($id_product_attribute) {
            if (version_compare(_PS_VERSION_, '1.5', '<')) {
                $combinations = $product->getAttributeCombinaisons($this->id_lang);
            } else {
                $combinations = $product->getAttributeCombinations($this->id_lang);
            }

            if ($combinations) {
                foreach ($combinations as $key => $combination) {
                    if ($combination['id_product_attribute'] == $id_product_attribute) {
                        $id_product_attribute = $combination['id_product_attribute'];
                    }
                }
            }

            if (!$id_product_attribute) {
                self::$errors[] = sprintf('%s : %d (%d)', $this->l('Couln\'t match product attributes for product'), $id_product, $id_combination);
                $pass = false;

                return (false);
            }
            $product->id_product_attribute = $id_product_attribute;
        } else {
            $product->id_product_attribute = null;
        }

        return ($product);
    }

    public function OrderSelection($method)
    {
        $encoded_xml = Tools::getValue('encoded-xml');
        $order_ids = Tools::getValue('order_id', array());
        $items_ids = Tools::getValue('items', array());

        if (empty($encoded_xml)) {
            self::$errors[] = 'Missing XML';
            die;
        }

        $decoded_xml = PriceMinisterTools::base64Decode($encoded_xml);

        if (empty($decoded_xml)) {
            self::$errors[] = 'Unable to decode XML';
            die;
        }

        $sales = simplexml_load_string($decoded_xml);

        if (!$sales instanceof SimpleXMLElement) {
            self::$errors[] = 'Unable to parse XML';
            die;
        }

        if ($method == 'accept') {
            $sales_array = $sales->xpath('/getnewsalesresult/response/sales/sale');
        } else {
            $sales_array = $sales->xpath('/getcurrentsalesresult/response/sales/sale');
        }

        if (!is_array($order_ids)) {
            $order_ids = array();
        }

        if (is_array($sales_array) && count($sales_array)) {
            foreach ($sales_array as $sale) {
                $purchaseid = (string)$sale->purchaseid;
                $sale->importable = 1;

                if (!in_array($purchaseid, $order_ids)) {
                    if ($this->debug) {
                        printf('%s(#%d): Line is not selected - %s'.'<br />', basename(__FILE__), __LINE__, $sale->purchaseid);
                    }
                    $sale->importable = 0;
                    continue;
                }

                if (!array_key_exists($purchaseid, $items_ids)) {
                    if ($this->debug) {
                        printf('%s(#%d): No items for this line - %s'.'<br />', basename(__FILE__), __LINE__, $purchaseid);
                    }

                    $sale->importable = 0;
                    continue;
                }

                if (($mp_order = PriceMinisterOrder::checkByMpId($purchaseid))) {
                    self::$warnings[] = $this->l('This order has been already imported').' : '.$mp_order['mp_order_id'].'('.$mp_order['id_order'].')';
                    $sale->importable = 0;
                    continue;
                }

                foreach ($sale->items->item as $item) {
                    $itemid = (string)$item->itemid;

                    if (!in_array($itemid, $items_ids[$purchaseid])) {
                        if ($this->debug) {
                            printf('%s(#%d): Item is not selected - %s<br />', basename(__FILE__), __LINE__, $itemid);
                        }
                        $sale->importable = 0;
                    }
                }

                if ($this->debug && !$sale->importable) {
                    printf('%s(#%d): Order is NOT importable - %s<br />', basename(__FILE__), __LINE__, $purchaseid);
                } elseif ($this->debug && $sale->importable) {
                    printf('%s(#%d): Order is importable - %s<br />', basename(__FILE__), __LINE__, $purchaseid);
                }
            }
        }

        return ($sales);
    }

    public function OrdersImport($sales, $cron = false)
    {
        $count = 0;
        $token_order = Tools::getValue('token_order');

        if (!$sales instanceof SimpleXMLElement) {
            self::$errors[] = 'Wrong argument passed to the function';
            die;
        }

        $config_credentials = parent::getConfig(PriceMinister::CONFIG_PM_CREDENTIALS);
        $config_parameters = parent::getConfig(PriceMinister::CONFIG_PM_PARAMETERS);
        $config_shipping = parent::getConfig(PriceMinister::CONFIG_PM_SHIPPING);
        $config_orders = parent::getConfig(PriceMinister::CONFIG_PM_ORDERS);

        $id_customer = Configuration::get(PriceMinister::CONFIG_PM_CUSTOMER_ID);

        $customer_account = $config_orders['customer_account'];
        $customer_email_domain = $config_orders['email_domain'];
        $status_incoming = $config_orders['status_incoming'];

        $import_method = $config_parameters['import_method'];
        self::$id_warehouse = (int)$config_parameters['warehouse'];

        $shipping_methods = $config_shipping['shipping_methods'];
        $pm_carriers = $config_shipping['pm_carriers'];
        $ps_carriers = $config_shipping['ps_carriers'];

        $sales_array = $sales->xpath('/getcurrentsalesresult/response/sales/sale');

        if (!is_array($sales_array) || !count($sales_array)) {
            self::$warnings[] = $this->l('No order to import');
            die;
        }
        PriceMinisterOrderImport::$orders = array();

        foreach ($sales_array as $sale) {
            $pass = true;
            $id_customer_order = null;

            if (!$cron && $sale->importable != '1') {
                continue;
            }

            $id_order = null;
            $purchaseid = trim((string)$sale->purchaseid);

            PriceMinisterOrderImport::$orders[$purchaseid] = array();
            PriceMinisterOrderImport::$orders[$purchaseid]['purchaseid'] = $purchaseid;
            PriceMinisterOrderImport::$orders[$purchaseid]['status'] = false;

            if (($mp_order = PriceMinisterOrder::checkByMpId($purchaseid))) {
                self::$warnings[] = $this->l('This order has been already imported').' : '.$mp_order['mp_order_id'].'('.$mp_order['id_order'].')';
                $id_order = $mp_order['id_order'];
                continue;
            }

            /*
            On 16/10/2012 19:22, Yan Gueguen wrote:
            > Vous devez indiquer :
            > -  deliveryaddress lors de commande par relais, colis standard etc..
            > -  billingaddress lors de commande avec collissimo, chronopost ..
            > Yan Gueguen
            > PriceMinister
            */

            $deliveryaddress = null;
            $billingaddress = null;

            if (isset($sale->deliveryinformation->deliveryaddress)) {
                $deliveryaddress = $sale->deliveryinformation->deliveryaddress;
            }

            if (isset($sale->deliveryinformation->billingaddress)) {
                $billingaddress = $sale->deliveryinformation->billingaddress;
            }

            if (!$deliveryaddress) {
                $deliveryaddress = $billingaddress;
            }

            // Customer individual account
            //
            if ($customer_account == PriceMinister::INDIVIDUAL_CUSTOMER_ACCOUNT) {
                $name = PriceMinisterAddress::toEmailAddress($deliveryaddress);

                if (!isset($name['firstname']) || empty($name['firstname']) || !isset($name['lastname']) || empty($name['lastname'])) {
                    self::$errors[] = sprintf('%s: %s', $this->l('Couldn\'t add this customer'), nl2br(print_r($name, true)));
                    continue;
                }

                $firstname = $name['firstname'];
                $lastname = $name['lastname'];

                $email_address = sprintf('%s.%s@%s', $firstname, $lastname, str_replace('@', '', $customer_email_domain));

                // Use buyer email instead generated email
                if (isset($sale->deliveryinformation->purchasebuyeremail) && Validate::isEmail($sale->deliveryinformation->purchasebuyeremail)) {
                    $email_address = $sale->deliveryinformation->purchasebuyeremail;
                }

                if (!Validate::isEmail($email_address)) {
                    self::$errors[] = sprintf('%s (%s)', $this->l('Invalid email address'), $email_address);
                    continue;
                }
                $customer = new Customer();
                $customer->getByEmail($email_address);

                if ($customer->id) {
                    $id_customer_order = $customer->id;
                } else {
                    $customer->firstname = Tools::substr($firstname, 0, 32);
                    $customer->lastname = Tools::substr($lastname, 0, 32);
                    $customer->email = $email_address;
                    $customer->passwd = md5(rand());

                    $customer_id_group = (int)$config_parameters['customer_group'];
                    if (!$customer_id_group) {
                        $customer_id_group = version_compare(_PS_VERSION_, '1.5', '>=') ?
                            (int)Configuration::get('PS_CUSTOMER_GROUP') : (int)_PS_DEFAULT_CUSTOMER_GROUP_;
                    }

                    $customer->id_default_group = $customer_id_group;

                    if (!$customer->add()) {
                        self::$errors[] = sprintf('%s/%s: %s (%s)', basename(__FILE__), __LINE__, $this->l('Couldn\'t add this customer'), $email_address);
                        continue;
                    } else {
                        $id_customer_order = $customer->id;
                    }
                }
            }

            if (!$id_customer_order) {
                $id_customer_order = $id_customer;
            }

            /*
             * Matching the method with the shipping matrix
             */
            $shipping_method = PriceMinisterTools::cleanShippingMethod((string)$sale->deliveryinformation->shippingtype);

            if (!isset($shipping_methods[$shipping_method]) || !isset($pm_carriers[$shipping_method]) || !isset($ps_carriers[$shipping_method]) || !$pm_carriers[$shipping_method] || !$ps_carriers[$shipping_method]) {
                self::$errors[] = sprintf($this->l('The shipping matrix doesn\'t match this case: %s - Please configure your shipping matrix in your module configuration'), $shipping_method);
                $pass = false;
                continue;
            }
            $mpShipping = $shipping_method;

            $carrier = new Carrier((int)$ps_carriers[$shipping_method]);

            if (!Validate::isLoadedObject($carrier)) {
                self::$errors[] = sprintf($this->l('Carrier validation failed for: %s - Please configure the carrier in your shipping matrix in your module configuration'), $shipping_method);
                $pass = false;
                continue;
            }

            // 2014/06/09 - Pickup Point Delivery
            if (isset($sale->deliveryinformation->collectionpointaddress->id) && (string)$sale->deliveryinformation->collectionpointaddress->id) {
                $deliveryinformation = $sale->deliveryinformation;
                $ppId = trim((string)$sale->deliveryinformation->collectionpointaddress->id);

                if (!($id_address = PriceMinisterAddress::pickupPointIdAddressByAlias($ppId, $id_customer_order, $deliveryinformation->collectionpointaddress))) {
                    $id_address = PriceMinisterAddress::createAddressForPickupPoint($ppId, $id_customer_order, $deliveryinformation->collectionpointaddress);

                    if (!$id_address) {
                        self::$errors[] = sprintf('%s - data: %s', $this->l('Unable to create an Address entry for this Collection Point Address - please contact the support'), print_r($sale->deliveryinformation->collectionpointaddress, true));
                        $pass = false;
                        continue;
                    }
                }
                $shipping_address_id = $id_address;
            } else {
                // Create or get address book entry
                //
                $shipping_address = new PriceMinisterAddress();
                $shipping_address->id_customer = $id_customer_order;
                $shipping_address_id = $shipping_address->lookupOrCreateAddress($deliveryaddress);
            }

            if ($billingaddress) {
                $billing_address = new PriceMinisterAddress();
                $billing_address->id_customer = $id_customer_order;
                $billing_address_id = $billing_address->lookupOrCreateAddress($billingaddress);
            } else {
                $billing_address_id = $shipping_address_id;
            }
            $created_at = sprintf('%d-%02d-%02d %02d:%02d:00', Tools::substr($sale->purchasedate, 6, 4), Tools::substr($sale->purchasedate, 3, 2), Tools::substr($sale->purchasedate, 0, 2), Tools::substr($sale->purchasedate, 11, 2), Tools::substr($sale->purchasedate, 14, 2));

            $itemDetails = array();

            // Building Cart
            //
            $cart = new PSPM_Cart();
            $cart->id_address_delivery = $shipping_address_id;
            $cart->id_address_invoice = $billing_address_id;
            $cart->id_carrier = $carrier->id;
            $cart->id_currency = false;
            $cart->id_customer = $id_customer_order;
            $cart->id_lang = $this->id_lang;

            PriceMinisterOrderImport::$orders[$purchaseid]['items'] = array();

            foreach ($sale->items->item as $item) {
                $sku = trim((string)$item->sku);
                $itemid = trim((string)$item->itemid);

                PriceMinisterOrderImport::$orders[$purchaseid]['items'][$itemid] = array();
                PriceMinisterOrderImport::$orders[$purchaseid]['items'][$itemid]['itemid'] = $itemid;
                PriceMinisterOrderImport::$orders[$purchaseid]['items'][$itemid]['status'] = false;
                PriceMinisterOrderImport::$orders[$purchaseid]['items'][$itemid]['sku'] = $sku;
                
                if (!$this->credentials['test'] && (string)$item->paymentstatus != 'INCOMING') {
                    self::$errors[] = 'Payment Status Error : '.(string)$item->paymentstatus;
                    $pass = false;
                    continue;
                }

                $product_name = (string)$item->headline;
                $quantity = 1; // !
                $price = (float)$item->price->amount;

                // Initializing cart & currency on first item
                //
                if ($cart->id_currency === false) {
                    $id_currency = Currency::getIdByIsoCode((string)$item->price->currency);

                    if (!$id_currency) {
                        self::$errors[] = 'Missing Currency:'.(string)$item->price->currency;
                        $pass = false;
                        continue;
                    }
                    $cart->id_currency = $id_currency;
                    $cart->add();
                }

                if (!($product = $this->productLoad($sku, $import_method))) {
                    $pass = false;
                    continue;
                }

                // Bug Fix
                // TODO
                $product->id_product_attribute = $product->id_product_attribute != $product->id ?
                    $product->id_product_attribute : null;

                if (version_compare(_PS_VERSION_, '1.5', '<')) {
                    $productQuantity = Product::getQuantity((int)$product->id, $product->id_product_attribute);
                } else {
                    // Looks for the product id_shop, if many, take the first one
                    $id_shop = array_map('intval', PriceMinisterTools::arrayColumn(
                        Product::getShopsByProduct($product->id),
                        'id_shop'
                    ));
                    $id_shop = reset($id_shop);

                    $productQuantity = Product::getRealQuantity(
                        $product->id,
                        $product->id_product_attribute && $product->id_product_attribute != $product->id ?
                            $product->id_product_attribute : null,
                        self::$id_warehouse,
                        $id_shop
                    );
                }

                // Temp fix not stock
                $force_import = true;
                if (!$force_import && $productQuantity - $quantity < 0) {
                    self::$errors[] = sprintf('%s ID: %s SKU: %s', $this->l('Not enough stock for this product'), $product->id, $sku);
                    $pass = false;
                    continue;
                }

                // Need all parameters to be able to send the Shop
                if ($cart->updateQty($quantity, $product->id, $product->id_product_attribute, false, 'up', 0, new Shop($id_shop)) < 0) {
                // if ($cart->updateQty($quantity, $product->id, $product->id_product_attribute, false, 'up') < 0) {
                    self::$errors[] = sprintf('%s : ID: %d - SKU: %d - %s', $this->l('Not enough stock for this product'), $product->id, $sku, $product_name);
                    $pass = false;
                    continue;
                }

                // PS 1.4 sinon 1.3
                if (method_exists('Tax', 'getProductTaxRate')) {
                    $product_tax_rate = (float)(Tax::getProductTaxRate($product->id, $shipping_address_id));
                } else {
                    $product_tax_rate = (float)(Tax::getApplicableTax($product->id_tax, $product->tax_rate, $shipping_address_id));
                }

                if (isset($itemDetails[(string)$item->itemid])) {
                    $itemDetails[(string)$item->itemid]['qty'] += $quantity;
                } else {
                    $itemDetails[(string)$item->itemid] = array(
                        'id_product' => (int)$product->id,
                        'id_attribute' => $product->id_product_attribute,
                        'qty' => $quantity,
                        'price' => $price,
                        'name' => $product_name,
                        'itemid' => (string)$item->itemid,
                        'tax_rate' => $product_tax_rate,
                        'id_tax' => isset($product->id_tax) ? $product->id_tax : false,
                        'id_address_delivery' => $shipping_address_id
                    );
                }
                PriceMinisterOrderImport::$orders[$purchaseid]['items'][$itemid]['status'] = true;
            }

            if (!count($itemDetails)) {
                if ($pass) {
                    self::$errors[] = $this->l('Cart empty, could save order').' ('.$sale->purchaseid.')';
                    $pass = false;
                }

                if (Validate::isLoadedObject($cart)) {
                    $cart->delete();
                }

                continue;
            }

            if (!($order_total = $this->GetOrderTotal($purchaseid, $config_credentials['test']))) {
                if (Validate::isLoadedObject($cart)) {
                    $cart->delete();
                }

                continue;
            }
            $shippingprice = $order_total['shipping'];

            // Using price, shipping details etc... from the Market Place
            //
            $cart->pmProducts = $itemDetails;
            $cart->pmShipping = $shippingprice;
            $cart->pmDate = $created_at;

            $mpStatusId = true;
            $mpItems = null; // obsolete

            // duplication du panier, important !!!
            //
            $acart = $cart;

            $payment = new PriceMinisterPaymentModule();

            if ($pass) {

                if (!($id_order = $payment->validateMarketplaceOrder($cart->id, $status_incoming, 'Rakuten France', $purchaseid, $mpStatusId, $mpShipping, $mpItems, $acart, true, $created_at))) {
                    $pass = false;
                    $cart->delete();
                }
                $delivery_point = null;

                if ($pass && isset($sale->deliveryinformation->collectionpointaddress->id) && !empty($sale->deliveryinformation->collectionpointaddress->id)) {
                    require_once(dirname(__FILE__).'/../classes/priceminister.pickuppoint.class.php');

                    $delivery_point = $sale->deliveryinformation->collectionpointaddress;

                    $pickup_point = new PriceMinisterPickupPoint();
                    $pickup_point->id = trim((string)$delivery_point->id);
                    $pickup_point->name = trim((string)$delivery_point->name);
                    $pickup_point->address1 = trim((string)$delivery_point->address);
                    $pickup_point->zipcode = trim((string)$delivery_point->zipcode);
                    $pickup_point->city = trim((string)$delivery_point->city);
                    $pickup_point->country = trim((string)$delivery_point->country);

                    $pickup_point->id_order = (int)$id_order;
                    $pickup_point->id_customer = (int)$id_customer_order;
                    $pickup_point->id_cart = (int)$cart->id;

                    switch ($sale->deliveryinformation->shippingtype) { // Normally, if we arrived until here we are supposed to have this value
                        case 'Point relais Mondial Relay':
                            // $pickup_point->id = call_user_func('reset', explode('/', Tools::substr($pickup_point->id, 3)));
                            $pickup_point->country = Tools::substr($pickup_point->country, 0, 2);
                            $pickup_point->pickup_type = PriceMinisterPickupPoint::MONDIAL_RELAY_TYPE;
                            $pickup_point->id_method = 0;

                            if (PriceMinisterPickupPoint::tableExists(PriceMinisterPickupPoint::MONDIAL_RELAY_TABLE)) {
                                $pickup_point->id_method = (int)Db::getInstance()->getValue(
                                    'SELECT `id_mr_method`
                                    FROM `'._DB_PREFIX_.'mr_method`
                                    WHERE `id_carrier` = '.$cart->id_carrier
                                );
                                $pickup_point->save();
                            }
                            break;

                        case ('So Colissimo'):
                            $pickup_point->pickup_type = PriceMinisterPickupPoint::SO_COLISSIMO_TYPE;
                            $pickup_point->phone = $sale->deliveryinformation->billingaddress->phonenumber1 ? $sale->deliveryinformation->billingaddress->phonenumber1 :
                                $sale->deliveryinformation->billingaddress->phonenumber2 ? (string)$sale->deliveryinformation->billingaddress->phonenumber2 : '0661123456';
                            $pickup_point->email = (string)$sale->deliveryinformation->purchasebuyeremail;

                            if (PriceMinisterPickupPoint::tableExists(PriceMinisterPickupPoint::SO_COLISSIMO_TABLE)) {
                                $pickup_point->save();
                            }
                            break;

                        default:
                            self::$errors[] = sprintf($this->l('Unknown Pickup Point Delivery Method for the order #%s'), $purchaseid);
                            break;
                    }
                }

                if ($pass) {
                    $deliverypoint_id = isset($delivery_point->id) ? (string)$delivery_point->id : null;
                    $shippingtype = isset($sale->deliveryinformation->shippingtype) ? (string)$sale->deliveryinformation->shippingtype : null;

                    $params = array('id_order' => $id_order, 'mp_order_id' => $purchaseid, 'shipping_type' => $shippingtype, 'relay' => $deliverypoint_id);

                    if (!$this->credentials['test'] && !PriceMinisterOrder::addOrderExt($params)) {
                        $this->errors[] = $this->l('Unable to save extra order informations').': ('.nl2br(print_r($params, true)).')'.PHP_EOL;
                        $error = true;
                    }

                    $count++;
                }
            }

            if ($id_order && !empty($token_order)) {
                $url = '?tab=AdminOrders&id_order='.$id_order.'&vieworder&token='.$token_order;
                $order_link = '<a href="'.$url.'" title="" target="_blank" >'.$purchaseid.' ('.$id_order.')</a>';

                PriceMinisterOrderImport::$orders[$purchaseid]['link'] = $order_link;
            } else {
                PriceMinisterOrderImport::$orders[$purchaseid]['link'] = null;
            }

            if ($pass) {
                PriceMinisterOrderImport::$orders[$purchaseid]['status'] = true;
            } else {
                PriceMinisterOrderImport::$orders[$purchaseid]['status'] = false;

                self::$warnings[] = sprintf('%s: %s', $this->l('Order has not been imported'), $purchaseid);
            }
        }
        if ($count) {
            Configuration::updateValue('PM_LAST_IMPORT', date('Y-m-d H:i:s'));

            if ($count == 1) {
                self::$messages[] = $this->l('One order successfully imported');
            } else {
                self::$messages[] = sprintf('%d %s', $count, $this->l('orders successfully imported'));
            }
        } else {
            self::$warnings[] = $this->l('No order to import');
        }
    }

    public function GetOrderTotal($pm_order_id, $test_mode = false)
    {
        if ($test_mode) {
            return array('amount' => 0, 'shipping' => 0);
        }
        // PM Configuration
        $config = PriceMinisterTools::Auth();

        if (!is_array($config)) {
            self::$errors[] = $this->l('Unexpected error, missing configuration');

            return (null);
        }
        $config['output_type'] = 'juicy';
        $config['output'] = 'json';

        $params = array();
        $params['purchaseid'] = $pm_order_id;

        $aSales = new PM_Sales($config);

        $aSale = $aSales->getbillinginformation($params);
        $billinginformation = Tools::jsonDecode($aSale);

        if (!isset($billinginformation->billinginformation)) {
            self::$errors[] = $this->l('Unable to retrieve order informations');

            return (null);
        }

        $shippingprice = 0;
        $paymentamount = 0;

        // Plusieurs Articles
        //
        if (isset($billinginformation->billinginformation->items->item) && is_array($billinginformation->billinginformation->items->item)) {
            $billinginformation->billinginformation->items = $billinginformation->billinginformation->items->item;
        }

        foreach ($billinginformation->billinginformation->items as $item) {
            $shippingprice += $item->shippingsaleprice->amount;
            $paymentamount += $item->itemsaleprice->amount;
        }

        return (array('amount' => $paymentamount, 'shipping' => $shippingprice));
    }
}

$orderlist = new PriceMinisterOrderImport();
$orderlist->Dispatch();
