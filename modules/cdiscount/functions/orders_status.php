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
require_once(dirname(__FILE__).'/../classes/cdiscount.address.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.order.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.pickuppoint.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.webservice.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.config.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.context.class.php');

class CDiscountStatuses extends CDiscount
{
    const DEFAULT_PERIOD_IN_DAYS = 15;

    private $_cr = "<br />\n";

    private $username;
    private $password;
    private $production;

    public function __construct()
    {
        parent::__construct();

        CDiscountContext::restore($this->context);

        if ((int)Tools::getValue('id_lang')) {
            $this->id_lang = (int)Tools::getValue('id_lang');
        }

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_shop = (int)Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;

            if ($id_shop) {
                $shop = new Shop($id_shop);
                $id_shop_group = $shop->id_shop_group;
            } else {
                $id_shop = 1;
                $id_shop_group = 1;
            }
            CDiscountContext::restore($this->context);
            Shop::setContext(Shop::CONTEXT_SHOP, $id_shop);
        }
    }

    public function dispatch()
    {
        $cdtoken = Tools::getValue('cdtoken');
        $action = Tools::getValue('action');

        //  Check Access Tokens
        //
        $token = parent::decode(Configuration::get(parent::KEY.'_PS_TOKEN'));

        if ($cdtoken != $token) {
            $this->dieOnError($this->l('Wrong Token'));
        }

        if (!$token = CDiscountTools::auth()) {
            $this->dieOnError($this->l('Authentication Failed'));
        }

        $this->username = Configuration::get(parent::KEY.'_USERNAME');
        $this->password = Configuration::get(parent::KEY.'_PASSWORD');
        $this->debug = Configuration::get(parent::KEY.'_DEBUG') ? true : false;
        $this->production = !(Configuration::get(parent::KEY.'_PREPRODUCTION') ? true : false);

        if (Tools::getValue('debug')) {
            $this->debug = true;
        }

        if ($this->debug) {
            echo "Debug Mode On";
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }

        switch ($action) {
            case 'cron':
                $this->listOrders();
                break;
        }
    }

    private function dieOnError($msg)
    {
        CommonTools::d($msg);
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

        $period_override = (int)Tools::getValue('period', 15);
        $period = max(self::DEFAULT_PERIOD_IN_DAYS, $period_override);

        $marketplace_id_lang = Language::getIdByIso('fr');
        $marketplace_lang_name = 'French';

        //French might not be set as language for the shop...
        if (!$marketplace_id_lang) {
            $default_lang = Language::getLanguages(true);
            $marketplace_id_lang = (count($default_lang) > 0) ? $default_lang[0]['id_lang'] : 1;
            $marketplace_lang_name = (count($default_lang) > 0) ? $default_lang[0]['name'] : 1;
        }
        $order_states = unserialize(parent::decode(Configuration::get(parent::KEY.'_ORDERS_STATES')));
        $callback = '';

        $marketplace = new CDiscountWebservice($this->username, $this->password, $this->production, $this->debug);
        $marketplace->token = CDiscountTools::auth();

        if (is_array($order_states)) {
            $order_state_accepted = $order_states[parent::KEY.'_CA'];
            $order_state_sent = $order_states[parent::KEY.'_CE'];
            $order_state_delivered = $order_states[parent::KEY.'_CL'];
        } else {
            $this->dieOnError('Orders States are not yet configured...');
        }


        if ($this->debug) {
            CommonTools::p("lang:".$marketplace_lang_name);
            CommonTools::p("id_lang: ".$marketplace_id_lang);
            CommonTools::p("order states:");
            CommonTools::p($order_states);
        }

        if (!$marketplace->token) {
            $this->dieOnError(sprintf('%s(%s): %s', basename(__FILE__), __LINE__, $this->l('Auth failed')).$cr);
        }


        $order_state = new OrderState($order_state_sent, $this->id_lang);

        if (!Validate::isLoadedObject($order_state)) {
            $this->dieOnError('Failed to load order state id:'.$order_state_sent);
        }

        CommonTools::p(str_repeat('-', 160));
        CommonTools::p(sprintf("Looking for pending orders width OrderState %s(%d) last %d days", $order_state->name, $order_state_sent, $period));

        $date_range = CDiscountOrder::getMarketplaceOrdersStatesDateStartByIdLang($marketplace_id_lang, $order_state_sent, $period, $this->debug);

        if (!$date_range) {
            printf($this->l('No Orders - exiting normally'));
            die;
        }

        CommonTools::p(str_repeat('-', 160));
        CommonTools::p(sprintf('Available orders returned, with date range from %s to %s', $date_range['date_start'], $date_range['date_end']));

        $dateStart = sprintf('%sT00:00:00.000', $date_range['date_start']);
        $dateEnd = sprintf('%sT23:59:59.000', $date_range['date_end']);
        $dateEnd2 = date('Y-m-d\T23:59:59.000', time());

        self::$global_informations = CDiscountConfig::GetGlobalConfiguration($this->debug);
        self::$tracking_mapping = unserialize(self::decode(Configuration::get(self::KEY.'_TRACKING_MAPPING')));

        $has_carrier_list = is_array(self::$global_informations) && array_key_exists('Carriers', self::$global_informations) && count(self::$global_informations);

        // Fetch Orders
        $ps_orders = CDiscountOrder::getMarketplaceOrdersStatesByIdLang($marketplace_id_lang, $order_state_sent, $period, $this->debug);

        // Merge Mondial Relay - Specifics
        if (CDiscountPickupPoint::isMondialRelayInstalled()) {
            $mr_orders = CDiscountPickupPoint::getMondialRelayMarketplaceOrdersStatesByIdLang($marketplace_id_lang, $order_state_sent, $period);

            $orders = array_merge($ps_orders, $mr_orders);
        } else {
            $orders = $ps_orders;
        }

        if (!is_array($orders) || !count($orders)) {
            $this->dieOnError($this->l('No Orders - exiting normally'));
        }

        CommonTools::p("Eligible orders returned:");
        foreach ($orders as $key => $order) {
            $carrier_name = 'Unknown Carrier';
            $id_carrier = (int)$order['id_carrier'];

            if ($id_carrier) {
                $carrier = new Carrier($order['id_carrier'], $this->id_lang);

                if (Validate::isLoadedObject($carrier)) {
                    $carrier_name = $carrier->name;
                }
            }

            $shipping_number = null;

            if (empty($order['shipping_number'])) {
                $id_order = (int)$order['id_order'];
                $ps_order = new Order($id_order);

                if (Validate::isLoadedObject($ps_order)) {
                    $shipping_number = CDiscountOrder::getShippingNumber($order);
                }
            } else {
                $shipping_number = $order['shipping_number'];
            }

            $orders[$key]['shipping_number'] = $shipping_number;
            $orders[$key]['carrier_name'] = $carrier_name;

            printf('id_order: %d, id_lang: %d, mp_order_id: %s, id_carrier: %d, shipping_number: %s via %s'.Cdiscount::LF, $order['id_order'], $order['id_lang'], $order['mp_order_id'], $order['id_carrier'], $shipping_number, $carrier_name);
        }

        CommonTools::p(str_repeat('-', 160));
        CommonTools::p("Building item lists:");

        foreach ($orders as $key => $order) {
            unset($orders[$key]);

            $orders[$order['mp_order_id']] = $order;
            $orders[$order['mp_order_id']]['ordered_items'] = CDiscountOrder::orderedItems((int)$order['id_order'], Configuration::get(parent::KEY.'_IMPORT_TYPE'));

            printf('id_order: %d, id_lang: %d, mp_order_id: %s'.Cdiscount::LF, $order['id_order'], $order['id_lang'], $order['mp_order_id']);

            foreach ($orders[$order['mp_order_id']]['ordered_items'] as $itemkey => $item) {
                echo str_repeat('&nbsp;', 5);
                printf('SellerProductId: %s, condition: %s'.Cdiscount::LF, $item['SellerProductId'], $item['condition']);

                $orders[$order['mp_order_id']]['ordered_items'][$item['SellerProductId']] = $item;
                unset($orders[$order['mp_order_id']]['ordered_items'][$itemkey]);
            }
            if ($this->debug && !count($orders[$order['mp_order_id']]['ordered_items'])) {
                $message = sprintf('%s/%s: %s: %s', basename(__FILE__), __LINE__, $this->l('No ordered items for Order ID'), $order['mp_order_id']).$cr;
                print $message;
            }
        }
        CommonTools::p(str_repeat('-', 160));

        $params = array(
            $dateStart,
            $dateStart,
            $dateEnd2,
            $dateEnd2,
            true,
            //
            false, // CancelledByCustomer
            false, // WaitingForSellerAcceptation
            true,  // AcceptedBySeller
            false, // PaymentInProgress
            true,  // WaitingForShipmentAcceptation
            false,  // SHIPPED
            false, // RefusedBySeller
            false, // AutomaticCancellation
            false, // PaymentRefused
            false, // ShipmentRefusedBySeller
            false, // None
            false, // ValidatedFianet
            false,  // RefusedNoShipment
            false,  // AvailableOnStore
            false,  // NonPickedUpByCustomer
            false  // PickedUp
        );

        if (!($xml = $marketplace->GetOrderList($params))) {
            $this->dieOnError(sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('GetOrderList failed...')));
        }

        if ($this->debug) {
            $dom = dom_import_simplexml($xml)->ownerDocument;
            $dom->formatOutput = true;

            CommonTools::p(htmlspecialchars($dom->saveXML()));
        }

        if (!$xml instanceof SimpleXMLElement) {
            $this->dieOnError(sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('No pending order...')));
        }

        $result = $xml->xpath('//OrderList/Order');

        if (!count($result)) {
            $this->dieOnError(sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('No pending order...')));
        }

        $acceptOrder = array();

        foreach ($result as $Order) {
            $oID = (string)$Order->OrderNumber;

            $isCLogistique = isset($Order->IsCLogistiqueOrder) && (string)$Order->IsCLogistiqueOrder ? trim((string)$Order->IsCLogistiqueOrder) : null;

            if ($isCLogistique == 'true') {
                if ($this->debug) {
                    printf('%s/%s: C Logistique Order - %s, non administrable, order ignored', basename(__FILE__), __LINE__, $oID);
                }
                continue;
            }

            if (!isset($orders[$oID]['id_order'])) {
                if ($this->debug) {
                    printf('%s/%s: missing order ID - %s', basename(__FILE__), __LINE__, $oID);
                }
                continue;
            }
            if (!isset($orders[$oID]['ordered_items']) || !count($orders[$oID]['ordered_items'])) {
                if ($this->debug) {
                    printf('%s/%s: ordered_items list is empty - %s', basename(__FILE__), __LINE__, $oID);
                }
                continue;
            }
            $id_order = (int)$orders[$oID]['id_order'];
            $order = new Order($id_order);

            if (!Validate::isLoadedObject($order)) {
                if ($this->debug) {
                    printf('%s/%s: Unable to load order - %d', basename(__FILE__), __LINE__, $id_order);
                }
                continue;
            }
            $acceptOrder[$oID]['OrderState'] = 'Shipped';
            $acceptOrder[$oID]['Items'] = array();
            $acceptOrder[$oID]['Count'] = 0;
            $acceptOrder[$oID]['ProductCondition'] = array();


            $carrier = new Carrier($order->id_carrier);
            $carrier_name = utf8_decode(CDiscountAddress::_filter($carrier->name));
            $id_carrier = (int)$carrier->id;
            $cdiscount_tracking_url = null;

            if ($has_carrier_list && is_array(self::$tracking_mapping) && count(self::$tracking_mapping)) {
                if (array_key_exists($id_carrier, self::$tracking_mapping)) {
                    $id_carrier_cdiscount = self::$tracking_mapping[$id_carrier];

                    if (array_key_exists($id_carrier_cdiscount, self::$global_informations['Carriers'])) {
                        $carrier_name = self::$global_informations['Carriers'][$id_carrier_cdiscount]['Name'];
                        $cdiscount_tracking_url = self::$global_informations['Carriers'][$id_carrier_cdiscount]['DefaultURL'];

                        if ($this->debug) {
                            printf('%s/%s: Carrier Mapping matched - %s', basename(__FILE__), __LINE__, $carrier_name);
                        }
                    }
                }
            }

            $acceptOrder[$oID]['CarrierName'] = html_entity_decode(strip_tags($carrier_name));

            if (isset($orders[$oID]['shipping_number']) && Tools::strlen($orders[$oID]['shipping_number'])) {
                $acceptOrder[$oID]['TrackingNumber'] = htmlentities(utf8_decode(trim($orders[$oID]['shipping_number'])));
            }

            if (Tools::strlen($carrier->url) && Tools::strlen($orders[$oID]['shipping_number'])) {
                $acceptOrder[$oID]['TrackingUrl'] = $url = htmlentities(utf8_decode(str_replace('@', trim($orders[$oID]['shipping_number']), $carrier->url)));

                if ($this->debug) {
                    printf('%s/%s: Using custom URL - %s', basename(__FILE__), __LINE__, $url);
                }
            } elseif (Tools::strlen($cdiscount_tracking_url) && Tools::strlen($orders[$oID]['shipping_number'])) {
                $acceptOrder[$oID]['TrackingUrl'] = trim($cdiscount_tracking_url);

                if ($this->debug) {
                    printf('%s/%s: Using CDiscount default URL - %s', basename(__FILE__), __LINE__, $cdiscount_tracking_url);
                }
            }

            foreach ($Order->xpath('OrderLineList/OrderLine') as $item) {
                $sku = trim((string)$item->SellerProductId);

                if (isset($item->ProductId) && (string)$item->ProductId == 'INTERETBCA') {
                    // Should not be exported
                    //$item->SellerProductId = 'INTERETBCA' ;
                    continue;
                } elseif (!isset($orders[$oID]['ordered_items'][$sku])) {
                    if ($this->debug) {
                        printf('%s/%s: Remote order mismatch - %s', basename(__FILE__), __LINE__, nl2br(print_r($orders[$oID], true)));
                    }
                    continue;
                }
                $acceptOrder[$oID]['Items'][$sku] = 'ShippedBySeller';
                $acceptOrder[$oID]['ProductCondition'][$sku] = trim((string)$item->ProductCondition);
            }
            if (!count($acceptOrder[$oID]['Items'])) {
                if ($this->debug) {
                    printf('%s/%s: No items - %s', basename(__FILE__), __LINE__, nl2br(print_r($acceptOrder, true)));
                }
                unset($acceptOrder[$oID]);
            }
        }

        if ($this->debug) {
            CommonTools::p($acceptOrder);
        }
        $result = null;

        if ($acceptOrder) {
            if (!($result = $marketplace->ValidateOrderList($acceptOrder))) {
                printf('%s/%s: %s %s - %s', basename(__FILE__), __LINE__, $this->l('Unable to set order status on'), parent::NAME, nl2br(print_r($result, true)));
            }
        } else {
            echo $this->l('No pending order to switch, exiting normally');
        }
    }
}

$marketplaceOrdersStatuses = new CDiscountStatuses;
$marketplaceOrdersStatuses->dispatch();
