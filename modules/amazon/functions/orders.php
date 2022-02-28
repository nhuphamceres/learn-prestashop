<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Feed.biz
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Feed.biz is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Feed.biz.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 *
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
*/

require_once(dirname(__FILE__).'/env.php');
require_once(dirname(__FILE__).'/../amazon.php');

require_once(dirname(__FILE__).'/../classes/amazon.context.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.product.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.tools.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.batch.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.address.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.stat.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.order_info.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.order.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.orderhistory.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.cart.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.payment.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.webservice.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.orders_reports.class.php');

class AmazonListOrder extends Amazon
{
    public static $errors     = array();
    public static $warnings   = array();
    public static $messages   = array();
    public static $orders     = array();

    /** @var $_amazonApi AmazonWebService */
    private $_amazonApi = null;

    private $marketplace_iso2id = null;
    private $marketplace_region = null;
    private $marketplace_lang2region = null;
    private $marketplace_id_lang = null;
    private $marketplace_setup = null;
    private $orders_reports_management = false;
    private $_debug     = false;

    public function __construct()
    {
        parent::__construct();

        AmazonContext::restore($this->context);

        $this->amazon_features = $this->getAmazonFeatures();
    }

    public static function jsonDisplayExit()
    {
        $buffer = ob_get_clean();
        $output = $buffer ? $buffer : '';

        $json = json_encode(array(
            'orders' => AmazonListOrder::$orders,
            'count' => count(AmazonListOrder::$orders),
            'error' => (count(AmazonListOrder::$errors) ? true : false),
            'errors' => AmazonListOrder::$errors,
            'warning' => count(AmazonListOrder::$warnings) > 0,
            'warnings' => AmazonListOrder::$warnings,
            'output' => $output
        ));

        $callback = Tools::getValue('callback');
        echo $callback ? ((string)$callback.'('.$json.')') : $json;
    }

    public function dispatch($action)
    {
        $this->_debug = (bool)Configuration::get('AMAZON_DEBUG_MODE');

        // Merge Order API results & Reports results
        //
        $this->orders_reports_management = (bool)$this->amazon_features['orders_reports'];

        if (Tools::getValue('debug')) {
            $this->_debug = true;
        }

        if ($this->_debug) {
            @ini_set('display_errors', 'on');

            @error_reporting(E_ALL | E_STRICT);

            // Generate a warning to identify which program/module sent the headers previously
            if (Tools::getValue('debug_header') !== false) {
                echo ob_get_clean();
                header('Pragma: no-cache');
            }
        }
        $this->init();

        switch ($action) {
            case 'list':
                $this->displayList();
                break;
        }
    }

    public function init()
    {
        ob_start();
        register_shutdown_function(array('AmazonListOrder', 'jsonDisplayExit'));

        // Identify the order's country
        //
        $marketplace_ids = AmazonConfiguration::get('MARKETPLACE_ID');
        $this->marketplace_iso2id = array_flip($marketplace_ids);

        // Regions
        //
        $this->marketplace_region = AmazonConfiguration::get('REGION');
        $this->marketplace_lang2region = array_flip($this->marketplace_region);
        $this->marketplace_id_lang = (int)Tools::getValue('amazon_lang');

        if ((int)Tools::getValue('europe')) {
            $masterMarketplace = AmazonConfiguration::get('MASTER');

            if (isset($this->marketplace_lang2region[$masterMarketplace]) && $this->marketplace_lang2region[$masterMarketplace]) {
                $this->marketplace_id_lang = $this->marketplace_lang2region[$masterMarketplace];
            } else {
                $this->marketplace_id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
            }

            $this->europe = 1;
        } else {
            $this->europe = 0;
        }

        if (!(int)$this->marketplace_id_lang) {
            AmazonListOrder::$errors[] = $this->l('No selected language, nothing to do...');
            die;
        }

        //  Check Access Tokens
        //
        $tokens = Tools::getValue('amazon_token');

        if (!AmazonTools::checkToken($tokens)) {
            AmazonListOrder::$errors[] = $this->l('Wrong Token');
        }

        // Init
        //
        if ($this->europe) {
            $this->marketplace_setup = AmazonTools::selectEuropeanPlatforms($this->_debug);
        } else {
            $this->marketplace_setup = AmazonTools::selectPlatforms($this->marketplace_id_lang, $this->_debug);
        }
    }
    
    public function displayList()
    {
        $cr = nl2br(Amazon::LF); // carriage return
        $date_max = null;

        /*if ($this->orders_reports_management) {
            $sql = 'SELECT MAX(`date`) as date_max FROM `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDER_ADDRESS.'`';
            $result = Db::getInstance()->getValue($sql);

            if (Tools::strlen($result)) {
                $date_max = date('Y-m-d H:i:s', min(time(), strtotime($result)));
            }
        }*/

        $current_version = Configuration::get('AMAZON_CURRENT_VERSION', null, 0, 0);

        if (version_compare($current_version, $this->version, '<')) {
            //AmazonListOrder::$errors[] = $this->l('Module version and configuration mismatch, please edit and save your module configuration').$cr;
            //die;
        }

        $currentDate = date('Y-m-d H:i:s');

        AmazonListOrder::$messages[] = $this->l('Starting Order Query in WS API/Web Mode').' - '.$currentDate.$cr;

        $status = (string)Tools::getValue('statuses');

        $date1 = str_replace('-', '/', Tools::getValue('datepickerFrom'));
        $date2 = str_replace('-', '/', Tools::getValue('datepickerTo'));
        $channel = Tools::getValue('channel');

        if (!in_array($channel, array(self::AFN, self::MFN))) {
            $channel = null;
        }

        // Today - 5 minutes de temps de latence afin d'�viter les erreur de synchro dus a l'heure systeme
        //
        if (isset($date_max)) {
            $date1 = date('c', min(strtotime($date1.' 00:00'), strtotime($date_max) - 86400));
            $date2 = date('c', min(strtotime($date2.' 23:59:59'), strtotime($date_max)));
        } elseif (date('Ymd', strtotime($date2)) >= date('Ymd') || empty($date2)) {
            $date1 = date('c', strtotime($date1));
            $date2 = date('c', strtotime('now - 5 min'));
        } elseif (date('Ymd', strtotime($date1)) >= date('Ymd', strtotime($date2))) {
            $date1 = date('c', strtotime($date1.' 00:00'));
            $date2 = date('c', strtotime($date2.' 23:59:59'));
        } else {
            $date1 = date('c', strtotime($date1.' 00:00'));
            $date2 = date('c', strtotime($date2.' 23:59:59'));
        }

        $tokenOrders = Tools::getValue('token_order');

        if (!($this->_amazonApi = new AmazonWebService($this->marketplace_setup['auth'], $this->marketplace_setup['params'], $this->marketplace_setup['platforms'], $this->_debug))) {
            AmazonListOrder::$errors[] = $this->l('Unable to login');
            die;
        }

        if ($this->_debug) {
            echo nl2br(print_r($this->marketplace_setup['auth'], true).print_r($this->marketplace_setup['params'], true).print_r($this->marketplace_setup['platforms'], true));
        }

        // Fix the server's clock drift
        $result = $this->_amazonApi->serviceStatus(true);

        if (isset($result->GetServiceStatusResult)) {
            if (isset($result->GetServiceStatusResult->Timestamp) && strtotime($result->GetServiceStatusResult->Timestamp) > strtotime('today midnight')) {
                $to_date = min(strtotime((string)$result->GetServiceStatusResult->Timestamp), strtotime($date2));
                $date2 = date('c', $to_date);
            }
        }

        if ($this->amazon_features['demo_mode']) {
            $this->_amazonApi->demo = true;
        }
        $no_pending_order_string = sprintf('%s: %s - %s', $this->l('No pending order for the selected period'), date('Y-m-d', strtotime($date1)), date('Y-m-d', strtotime($date2)));

        // Listing Orders
        // todo: Despite returnXML = true, array returned
        $orders = $this->_amazonApi->GetUnshippedOrdersListv4($date1, $date2, $status, $channel, true, false);

        if (!$orders || !is_array($orders) || !count($orders)) {
            AmazonListOrder::$warnings[] = sprintf('%s (%d/%s)', $no_pending_order_string, __LINE__, 'List Orders');
            die;
        }
        // Has data but warning
        $apiError = $this->_amazonApi->getResponseError();
        if ($apiError && $apiError->HasError) {
            AmazonListOrder::$warnings[] = sprintf($this->l('Error during fetching orders: %s'), $apiError->Message);
        }

        $orderCheck = new AmazonOrder();

        foreach ($orders as $key => $order) {
            $purchased_date = isset($order->PurchaseDate) ? strtotime($order->PurchaseDate) : null;
            
            if ($purchased_date && $purchased_date < strtotime($date1) || $purchased_date > strtotime($date2)) {
                continue;
            }

            // Langue de la Commande
            //
            if (is_array($this->marketplace_iso2id) && count($this->marketplace_iso2id) && isset($this->marketplace_iso2id[(string)$order->MarketPlaceId])) {
                $this->marketplace_id_lang = $this->marketplace_iso2id[(string)$order->MarketPlaceId];
            } else {
                $this->marketplace_id_lang = $this->context->language->id;
            }

            $pass = true;


            switch ((string)$order->OrderStatus) {
                case AmazonOrder::ORDER_PENDING:
                    $status_string = $this->l('In Cart');

                    if ($status != 'All' && $status != AmazonOrder::ORDER_IN_CART) {
                        $pass = false;
                    }
                    break;

                default:
                    $status_string = (string)$order->OrderStatus;
                    break;
            }

            switch ($status) {
                case AmazonOrder::ORDER_PENDING:
                    if (!in_array((string)$order->OrderStatus, array(AmazonOrder::ORDER_UNSHIPPED, AmazonOrder::ORDER_PARTIALLYSHIPPED))) {
                        $pass = false;
                    }
                    break;
                case AmazonOrder::ORDER_SHIPPED:
                    if (!in_array((string)$order->OrderStatus, array(AmazonOrder::ORDER_SHIPPED))) {
                        $pass = false;
                    }
                    break;
                case AmazonOrder::ORDER_PARTIALLYSHIPPED:
                    if (!in_array($order->OrderStatus, array(AmazonOrder::ORDER_PARTIALLYSHIPPED))) {
                        $pass = false;
                    }
                    break;
                case AmazonOrder::ORDER_UNSHIPPED:
                    if (!in_array($order->OrderStatus, array(AmazonOrder::ORDER_UNSHIPPED))) {
                        $pass = false;
                    }
                    break;
                case AmazonOrder::ORDER_CANCELED:
                    if (!in_array($order->OrderStatus, array(AmazonOrder::ORDER_CANCELED))) {
                        $pass = false;
                    }
                    break;
                case 'All':
                    $pass = true;
                    break;
            }

            if (!$pass) {
                continue;
            }

            $currency = (int)Currency::getIdByIsoCode((string)$order->OrderTotalCurrency);

            $total = (float)$order->OrderTotalAmount;
            $retrieved = $orderCheck->checkByMpId($order->AmazonOrderId);

            $url = '?tab=AdminOrders&id_order='.$retrieved.'&vieworder&token='.$tokenOrders;

            $order_link = $retrieved ? (html_entity_decode('&lt;a href="'.$url.'" title="" target="_blank" &gt;'.$order->AmazonOrderId.'('.$retrieved.')&lt;/a&gt;')) : $order->AmazonOrderId;

            if(!$this->context->link){
                $this->context->link = new Link();
            }
            
            $url = $this->context->link->getModuleLink('amazon', 'CustomPdf', array('submitAction' => 'generateInvoicePDF', 'id_order' => $retrieved)); // KAM_CHG
            $invoice_link = $retrieved ? (html_entity_decode('&lt;a href="'.$url.'" title="" target="_blank" &gt;'.'<i class="icon-file-text"></i>'.'&lt;/a&gt;')) : '';

            $name = AmazonAddress::getAmazonName((string)$order->Address->Name, $this->marketplace_id_lang);
            $oID = (string)$order->AmazonOrderId;
            // todo: Leave the HTML part for template
            self::$orders[$oID] = array(
                'id' => (string)$order->AmazonOrderId,
                'flag' => $this->marketplace_id_lang ? html_entity_decode('&lt;img src="'.$this->images.'geo_flags/'.$this->marketplace_region[$this->marketplace_id_lang].'.jpg" alt="" /&gt;') : '',
                // May cause date mismatch.
                // If pass Amazon ISO 8601 datetime, PS throws error because of failure of Validate::isDate($date)
                // If parse date before, the date() function is manipulated by timezone already ---> wrong datetime
                // The only way is to write our own displayDate function, which is expensive. Let's keep it at this time
                'date' => AmazonTools::displayDate(date('Y-m-d H:i:s', strtotime($order->PurchaseDate)), $this->id_lang),
                'id_lang' => $this->marketplace_id_lang,
                'link' => $order_link,
                'status' => $status_string,
                'imported' => (bool)$retrieved,
                'pending' => (string)$order->OrderStatus == AmazonOrder::ORDER_PENDING,
                'canceled' => (string)$order->OrderStatus == AmazonOrder::ORDER_CANCELED,
                'customer' => htmlspecialchars(sprintf('%s %s', $name['firstname'], $name['lastname'])),
                'shipping' => (string)$order->ShipServiceLevel,
                'business' => $order->IsBusinessOrder ? html_entity_decode('&lt;img src="'.$this->images.'checked.png" alt="" /&gt;') : '',
                'fulfillment' => $order->IsPrime ? sprintf(html_entity_decode('%s &lt;b&gt;(Prime)&lt;/b&gt;'), (string)$order->FulfillmentChannel) : (string)$order->FulfillmentChannel,
                'quantity' => (int)((int)$order->NumberOfItemsUnshipped + (int)$order->NumberOfItemsShipped),
                'total' => AmazonTools::displayPrice($total, $currency, false, $this->context),
            );

            if (Amazon::ENABLE_EXPERIMENTAL_FEATURES)
                self::$orders[$oID]['invoice'] = $invoice_link;
        }
    }
    public function l($string, $specific = false, $id_lang = null)
    {
        static $lang = null;

        if(!$lang)
            $lang = Amazon::availableLang(Language::getIsoById($this->id_lang));

        return (parent::l($string, basename(__FILE__, '.php'), $lang));
    }
}

$amazonOrders = new AmazonListOrder();
$amazonOrders->dispatch(Tools::getValue('action', 'list'));
