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

require_once(_PS_MODULE_DIR_.'cdiscount/classes/cdiscount.order.class.php');
require_once(_PS_MODULE_DIR_.'cdiscount/classes/cdiscount.tools.class.php');
require_once(_PS_MODULE_DIR_.'cdiscount/classes/cdiscount.webservice.class.php');
require_once(_PS_MODULE_DIR_.'cdiscount/classes/cdiscount.order_info.class.php');

class CDiscountAdminOrder extends CDiscount
{
    const PROD_ORDERS_URL = 'https://seller.cdiscount.com/Orders.html';
    const PREPROD_ORDERS_URL = 'https://seller.preprod-cdiscount.com/Orders.html';
    private static $debug_array = array();

    private static function debugDisplay()
    {
        if (is_array(self::$debug_array)) {
            $output = ob_get_contents();
            ob_get_clean();

            if (isset(self::$debug_array['Informations'])) {
                printf('<h3>Informations</h3><pre>%s</pre>', nl2br(print_r(self::$debug_array['Informations'], true)));
            }

            if (isset(self::$debug_array['Parameters'])) {
                printf('<h3>API Parameters</h3><pre>%s</pre>', self::$debug_array['Parameters']);
            }

            printf('<h3>Other Outputs</h3><pre>%s</pre>', $output);

            die;
        }

        return;
    }

    public function marketplaceOrderDisplay($params)
    {
        if (!array_key_exists('id_order', $params)) {
            return (false);
        }

        $id_order = (int)$params['id_order'];

        $order = new CDiscountOrder($id_order);

        if (!Validate::isLoadedObject($order) || $order->module != $this->name) {
            return '';
        }

        // For Quick Access
        //
        $production = !(Configuration::get(parent::KEY.'_PREPRODUCTION') ? true : false);
        $bulk_mode = Configuration::get(parent::KEY.'_BULK_MODE') ? true : false;
        $debug = Configuration::get(parent::KEY.'_DEBUG') ? true : false;

        if ($production) {
            $orders_url = self::PROD_ORDERS_URL;
        } else {
            $orders_url = self::PREPROD_ORDERS_URL;
        }

        if (isset($order->shipping_number) && !empty($order->shipping_number)) {
            $trackingNumber = $order->shipping_number;
        } else {
            $trackingNumber = CDiscountOrder::getShippingNumber($order);
        }

        $view_params = array(
            'bulk_mode' => $bulk_mode,
            'debug' => $debug,
            'orders_url' => $orders_url,
            'css_url' => $this->url.'views/css/orders_sheet.css',
            'js_url' => $this->url.'views/js/orders_sheet.js',
            'images_url' => $this->images,
            'ps_version_is_16' => version_compare(_PS_VERSION_, '1.6', '>='),
            'ps_version_is_15' => version_compare(_PS_VERSION_, '1.5', '>=') && version_compare(_PS_VERSION_, '1.6', '<'),
            'marketplace_order_id' => $order->marketPlaceOrderId,
            'tracking_number' => $trackingNumber,
            'order_ext' => $this->loadOrderExtendedData($id_order),
        );

        // If carrier is RCO then display it
        $carriers_info = unserialize(parent::decode(Configuration::get(parent::KEY.'_CARRIERS_INFO')));
        if (is_array($carriers_info) && in_array($order->id_carrier, $carriers_info)) {
            switch (array_search($order->id_carrier, $carriers_info)) {
                case 'RelaisColis':
                    $pickup_address = new Address($order->id_address_delivery);

                    if (!Validate::isLoadedObject($pickup_address)) {
                        break;
                    }

                    $result = explode(' - ', $pickup_address->other);

                    if ($result[0] && strpos($result[0], 'RELAY_ID_') !== false) {
                        $pickup_id = Tools::substr($result[0], 9);
                    } else {
                        $pickup_id = $this->l('Can\'t find the pickup ID.');
                    }

                    $view_params['optionnal_carrier'] = true;
                    $view_params['optionnal_carrier_info'] = array(
                        'name' => 'Relais Colis',
                        'pickup_id' => $pickup_id
                    );
                    break;

                default:
                    $view_params['optionnal_carrier'] = false;
                    $view_params['optionnal_carrier_info'] = array();
                    break;
            }
        }

        $this->context->smarty->assign($view_params);
        return $this->context->smarty->fetch($this->path.'views/templates/admin/admin_order/admin_order.tpl');
    }

    public function marketplaceHookActionOrderStatusUpdate($params)
    {
        if (Configuration::get(parent::KEY.'_BULK_MODE')) {
            return;
        }

        $debug = Configuration::get(parent::KEY.'_DEBUG') ? true : false;

        $id_order = (int)$params['id_order'];

        $order = new Order($id_order);

        if (!Validate::isLoadedObject($order)) {
            if ($debug) {
                die(Tools::displayError(sprintf('%s/%d: Unable to load order', basename(__FILE__), __LINE__)));
            }

            return;
        }
        // Not a parent order
        //
        if ($order->module != $this->name) {
            return;
        }

        if ($debug) {
            ob_start();
            register_shutdown_function(array(__CLASS__, 'debugDisplay'));
        }

        $order_states = unserialize(parent::decode(Configuration::get(parent::KEY.'_ORDERS_STATES')));

        if (!is_array($order_states) || !count($order_states) || !max($order_states)) {
            if ($debug) {
                die(Tools::displayError(sprintf('%s/%d: Orders states are not configured yet', basename(__FILE__), __LINE__)));
            }

            return (false); // not configured yet
        }

        $order_state_accepted = $order_states[parent::KEY.'_CA'];  // commande acceptee
        $order_state_sent = $order_states[parent::KEY.'_CE'];  // commande expediee
        $order_state_delivered = $order_states[parent::KEY.'_CL'];  // commande livree
        //
        //
        // Matching Order Status
        //
        switch ($params['newOrderStatus']->id) {
            case $order_state_sent:
                break;
            case $order_state_delivered:
                // not yet implemented
                return;
            default:
                return;
        }

        $trackingNumber = null;

        if (isset($order->shipping_number) && !empty($order->shipping_number)) {
            $trackingNumber = $order->shipping_number;
        } elseif (($shipping_number = Tools::getValue('shipping_number')) && !empty($shipping_number)) {
            $trackingNumber = $shipping_number;
        } else {
            $trackingNumber = CDiscountOrder::getShippingNumber($order);
        }

        $carrier = new Carrier($order->id_carrier);

        $username = Configuration::get(parent::KEY.'_USERNAME');
        $password = Configuration::get(parent::KEY.'_PASSWORD');
        $production = !(Configuration::get(parent::KEY.'_PREPRODUCTION') ? true : false);

        $webservice = new CDiscountWebService($username, $password, $production, $debug);
        $webservice->token = CDiscountTools::auth();

        if (!$webservice->token) {
            if ($debug) {
                die(Tools::displayError(sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Unable to obtain a token'))));
            }

            return;
        }

        $oID = $order->marketPlaceOrderId;

        if ($debug) {
            self::$debug_array['Informations'] = sprintf('User: %s Token: %s'."\n", $username, $webservice->token);
            self::$debug_array['Informations'] .= sprintf('Order: %s - CDiscount: %s'."\n", $id_order, $oID);
            self::$debug_array['Informations'] .= sprintf('Tracking Number: %s'."\n", $trackingNumber);
        }

        $conditionMap = unserialize(parent::decode(Configuration::get(parent::KEY.'_CONDITION_MAP')));

        if (!is_array($conditionMap) || !count($conditionMap) || !max($conditionMap)) {
            if ($debug) {
                die(Tools::displayError(sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('Condition Map is not configured'))));
            }

            return;
        }
        $ps_conditions = array_flip($conditionMap);

        if (!$orderedItems = CDiscountOrder::orderedItems($id_order, Configuration::get(parent::KEY.'_IMPORT_TYPE'))) {
            if ($debug) {
                die(Tools::displayError(sprintf('%s/%s: %s', basename(__FILE__), __LINE__, $this->l('No ordered items found'))));
            }

            return;
        }

        $acceptOrder = array();
        $acceptOrder[$oID]['OrderState'] = 'Shipped';
        $acceptOrder[$oID]['Items'] = array();
        $acceptOrder[$oID]['Count'] = 0;
        $acceptOrder[$oID]['ProductCondition'] = array();

        if ($trackingNumber) {
            $acceptOrder[$oID]['CarrierName'] = $carrier->name;
            $acceptOrder[$oID]['TrackingNumber'] = $trackingNumber;

            if (isset($carrier->url) && !empty($carrier->url)) {
                $acceptOrder[$oID]['TrackingUrl'] = htmlentities(utf8_decode(str_replace('@', $trackingNumber, $carrier->url)));
            }
        }

        foreach ($orderedItems as $item) {
            $SellerProductId = $item['SellerProductId'];

            if (!$SellerProductId) {
                continue;
            }

            $Condition = $this->_cd_conditions[$ps_conditions[$item['condition']]];

            $acceptOrder[$oID]['Items'][$SellerProductId] = 'ShippedBySeller';
            $acceptOrder[$oID]['ProductCondition'][$SellerProductId] = $Condition;
        }

        if ($debug) {
            self::$debug_array['Parameters'] = nl2br(print_r($acceptOrder, true));
        }

        if (!($result = $webservice->ValidateOrderList($acceptOrder))) {
            if ($debug) {
                die(Tools::displayError(sprintf('%s/%s: %s %s - %s', basename(__FILE__), __LINE__, $this->l('Unable to set order status on'), parent::NAME, nl2br(print_r($result, true)))));
            }

            return;
        }

        if ($debug) {
            die;
        }
    }

    protected function loadOrderExtendedData($idOrder)
    {
        $orderInfo = new CDiscountOrderInfo($idOrder);
        if ($orderInfo->getOrderInfo()) {
            $showChannel = false;
            $channel_color = 'black';

            $channel_id = $orderInfo->cd_channel_id;
            if ($channel_id && $channel_id != 1) {
                $showChannel = true;
                if (isset(CDiscount::$channel_colors[$channel_id])) {
                    $channel_color = CDiscount::$channel_colors[$channel_id];
                }
            }

            return array(
                'show_channel' => $showChannel,
                'channel_id' => $orderInfo->cd_channel_id,
                'channel_name' => $orderInfo->cd_channel_name,
                'clogistique' => $orderInfo->clogistique,
                'channel_color' => $channel_color,
                'the_dates' => array(
                    'earliest_ship_date' => array(
                        'label' => $this->l('Earliest Ship Date'),
                        'value' => $orderInfo->earliest_ship_date,
                        'color' => time() > strtotime($orderInfo->earliest_ship_date) ? 'red' : 'green',
                    ),
                    'latest_ship_date' => array(
                        'label' => $this->l('Latest Ship Date'),
                        'value' => $orderInfo->latest_ship_date,
                        'color' => time() > strtotime($orderInfo->latest_ship_date) ? 'red' : 'green',
                    ),
                    'earliest_delivery_date' => array(
                        'label' => $this->l('Earliest Delivery Date'),
                        'value' => $orderInfo->earliest_delivery_date,
                        'color' => time() > strtotime($orderInfo->earliest_delivery_date) ? 'red' : 'green',
                    ),
                    'latest_delivery_date' => array(
                        'label' => $this->l('Latest Delivery Date'),
                        'value' => $orderInfo->latest_delivery_date,
                        'color' => time() > strtotime($orderInfo->latest_delivery_date) ? 'red' : 'green',
                    ),
                ),
            );
        }

        return array();
    }

    public function l($string, $specific = false, $locale = null)
    {
        /**
         * https://support.common-services.com/a/tickets/89317
         * Not sure why an exception appear in PS1.7.6.1:
         * src/PrestaShopBundle/Translation/Loader/SqlTranslationLoader.php::load() --> Language not found in database
         * Catch the error as a temporary workaround
         */
        try {
            return parent::l($string, basename(__FILE__, '.php'), $this->context->language->iso_code);
        } catch (Symfony\Component\Translation\Exception\NotFoundResourceException $exception) {
            return $string;
        } catch (Exception $exception) {
            return $string;
        }
    }
}
