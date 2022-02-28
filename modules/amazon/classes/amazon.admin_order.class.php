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

require_once(_PS_MODULE_DIR_.'/amazon/classes/amazon.order_cancel.class.php');
require_once(_PS_MODULE_DIR_.'/amazon/classes/amazon.order_info.class.php');
require_once(_PS_MODULE_DIR_.'/amazon/classes/amazon.order.class.php');
require_once(_PS_MODULE_DIR_.'/amazon/classes/amazon.tools.class.php');
require_once(_PS_MODULE_DIR_.'/amazon/classes/amazon.multichannel.class.php');
require_once(_PS_MODULE_DIR_.'/amazon/classes/amazon.order_item.class.php');

class AmazonAdminOrder extends Amazon
{
    protected $debugFilePrefix = 'AmazonAdminOrder';

    private $_tokens;
    private $_send_email;
    private $_amazon_features;
    private $_order_id_lang;

    /**
     * Displays Order Informations
     * @param $params
     * @return string
     */
    public function marketplaceOrderDisplay($params)
    {
        $multichannel = (bool)Configuration::get('AMAZON_FBA_MULTICHANNEL');
        $canceled_state = AmazonConfiguration::get('CANCELED_STATE');

        $this->context = Context::getContext();

        $id_order = (int)$params['id_order'];

        $amazonOrder = new AmazonOrder($id_order);

        if (!Validate::isLoadedObject($amazonOrder)) {
            if (Amazon::$debug_mode) {
                die(Tools::displayError(sprintf('%s/%d: Unable to load order: %d', basename(__FILE__), __LINE__, $id_order)));
            }

            return (false);
        }

        if (Tools::strtolower($amazonOrder->module) != 'amazon' && $amazonOrder->marketPlaceChannel != AmazonMultiChannel::AMAZON_FBA_MULTICHANNEL && !$multichannel) {
            return (false);
        }

        $this->_tokens = AmazonConfiguration::get('CRON_TOKEN');
        $this->_send_email = (bool)Configuration::get('AMAZON_EMAIL');
        $this->_amazon_features = AmazonConfiguration::get('FEATURES');
        
        $this->_order_id_lang = $this->id_lang;
        $this->_multichannel = $multichannel;

        $cancel_stage = false;

        if ($amazonOrder->id_lang) {
            $this->_order_id_lang = $amazonOrder->id_lang;
        }

        $token = (is_array($this->_tokens) ? max($this->_tokens) : null);
        if (!$token) {
            if (Amazon::$debug_mode) {
                CommonTools::p(sprintf('id_lang: %s'."\n", $this->id_lang));
                CommonTools::p(sprintf('order id_lang: %s'."\n", $this->_order_id_lang));
                CommonTools::p(sprintf('tokens: %s'."\n", print_r($this->_tokens, true)));
                die(Tools::displayError(sprintf('%s/%d: Unavailable Amazon token', basename(__FILE__), __LINE__)));
            }

            return (false);
        }

        $view_params = array(
            'url' => $this->url,
            'module_path' => $this->path,
            'images_url' => $this->images,
            'context_key' => null,
            'debug' => Amazon::$debug_mode,
            'class_warning' => 'warn '.($this->ps16x ? 'alert alert-warning' : ''),
            'class_error' => 'error '.($this->ps16x ? 'alert alert-danger' : ''),
            'class_success' => 'confirm '.($this->ps16x ? 'alert alert-success' : 'conf'),
            'class_info' => 'hint '.($this->ps16x ? 'alert alert-info' : 'conf'),
            'ps_version_is_16' => version_compare(_PS_VERSION_, '1.6', '>='),
            'ps_version_is_15' => version_compare(_PS_VERSION_, '1.5', '>=') && version_compare(_PS_VERSION_, '1.6', '<'),
            'css_url' => $this->url.'views/css/admin_order.css',
            
            'amazon_token' => $token,
            'id_order' => $amazonOrder->id,
            'id_lang' => $this->_order_id_lang,
            'marketplace_region' => AmazonTools::idToDomain($this->_order_id_lang),
            'marketplace_order_id' => null,
            'marketplace_channel' => null,
            'glossary' => AmazonSettings::getGlossary(Language::getIsoById($this->id_lang), 'admin_order_details'),
        );

        if (isset($this->context) && $this->context instanceof Context && file_exists(_PS_MODULE_DIR_.'/amazon/classes/amazon.context.class.php')) {
            require_once(_PS_MODULE_DIR_.'/amazon/classes/amazon.context.class.php');
            $view_params['context_key'] = AmazonContext::getKey($this->context->shop);
        } else {
            $view_params['context_key'] = null;
        }

        if ($amazonOrder->marketPlaceOrderId) {
            $view_params['marketplace_order_id'] = $amazonOrder->marketPlaceOrderId;
        }

        if (isset($amazonOrder->marketPlaceChannel) && $amazonOrder->marketPlaceChannel) {
            switch ($amazonOrder->marketPlaceChannel) {
                case AmazonMultiChannel::AMAZON_FBA_AMAZON:
                    $view_params['marketplace_channel'] = $this->l('Fulfilled By Amazon');
                    break;
                case AmazonMultiChannel::AMAZON_FBA_MULTICHANNEL:
                    $view_params['marketplace_channel'] = $this->l('Multi-Channel Order / Fulfilled By Amazon');
                    break;
            }
        }

        if ($amazonOrder->amazon_order_info instanceof AmazonOrderInfo && $amazonOrder->amazon_order_info->is_extended_feature_available && $amazonOrder->amazon_order_info->getOrderInfo()) {
            $isoCode = $this->context->language->iso_code;
            $translateFile = basename(__FILE__, '.php');

            $amazonOrderInfo = array();
            if ($amazonOrder->amazon_order_info->is_prime) {
                $amazonOrderInfo['is_prime'] = array(
                    'label' => $this->l('Prime Order', $translateFile, $isoCode),
                    'value' => $this->l('Yes', $translateFile, $isoCode),
                    'bold' => true,
                    'color' => 'red'
                );
            }

            $view_params['amazon_order_info'] = $amazonOrderInfo;

            if ($amazonOrder->amazon_order_info->is_premium) {
                $view_params['amazon_order_info']['is_premium']['label'] = $this->l('Premium Order', basename(__FILE__, '.php'), $this->context->language->iso_code);
                $view_params['amazon_order_info']['is_premium']['value'] = $this->l('Yes', basename(__FILE__, '.php'), $this->context->language->iso_code);
                $view_params['amazon_order_info']['is_premium']['bold'] = true;
                $view_params['amazon_order_info']['is_premium']['color'] = 'red';
            }

            if ($amazonOrder->amazon_order_info->is_business) {
                $view_params['amazon_order_info']['is_business']['label'] = $this->l('Business Order', basename(__FILE__, '.php'), $this->context->language->iso_code);
                $view_params['amazon_order_info']['is_business']['value'] = $this->l('Yes', basename(__FILE__, '.php'), $this->context->language->iso_code);
                $view_params['amazon_order_info']['is_business']['bold'] = true;
                $view_params['amazon_order_info']['is_business']['color'] = 'darkblue';
            }

            if ($amazonOrder->amazon_order_info->sales_channel) {
                $view_params['amazon_order_info']['sales_channel']['label'] = $this->l('Sales Channel', basename(__FILE__, '.php'), $this->context->language->iso_code);
                $view_params['amazon_order_info']['sales_channel']['value'] = $amazonOrder->amazon_order_info->sales_channel;
                $view_params['amazon_order_info']['sales_channel']['bold'] = false;
                $view_params['amazon_order_info']['sales_channel']['color'] = null;
            }

            if ($amazonOrder->amazon_order_info->order_channel) {
                $view_params['amazon_order_info']['order_channel']['label'] = $this->l('Order Channel', basename(__FILE__, '.php'), $this->context->language->iso_code);
                $view_params['amazon_order_info']['order_channel']['value'] = $amazonOrder->amazon_order_info->order_channel;
                $view_params['amazon_order_info']['order_channel']['bold'] = false;
                $view_params['amazon_order_info']['order_channel']['color'] = null;
            }

            if ($amazonOrder->amazon_order_info->ship_service_level) {
                $view_params['amazon_order_info']['ship_service_level']['label'] = $this->l('Ship Service Level', basename(__FILE__, '.php'), $this->context->language->iso_code);
                $view_params['amazon_order_info']['ship_service_level']['value'] = $amazonOrder->amazon_order_info->ship_service_level;
                $view_params['amazon_order_info']['ship_service_level']['bold'] = false;
                $view_params['amazon_order_info']['ship_service_level']['color'] = null;
            }

            if ($amazonOrder->amazon_order_info->earliest_ship_date) {
                $view_params['amazon_order_info']['earliest_ship_date']['label'] = $this->l('Earliest Ship Date', basename(__FILE__, '.php'), $this->context->language->iso_code);
                $view_params['amazon_order_info']['earliest_ship_date']['value'] = $amazonOrder->amazon_order_info->earliest_ship_date;
                $view_params['amazon_order_info']['earliest_ship_date']['bold'] = true;
                $view_params['amazon_order_info']['earliest_ship_date']['color'] = time() > strtotime($amazonOrder->amazon_order_info->earliest_ship_date) ? 'red' : 'green';
            }

            if ($amazonOrder->amazon_order_info->latest_ship_date) {
                $view_params['amazon_order_info']['latest_ship_date']['label'] = $this->l('Latest Ship Date', basename(__FILE__, '.php'), $this->context->language->iso_code);
                $view_params['amazon_order_info']['latest_ship_date']['value'] = $amazonOrder->amazon_order_info->latest_ship_date;
                $view_params['amazon_order_info']['latest_ship_date']['bold'] = true;
                $view_params['amazon_order_info']['latest_ship_date']['color'] = time() > strtotime($amazonOrder->amazon_order_info->latest_ship_date) ? 'red' : 'green';
            }

            if ($amazonOrder->amazon_order_info->earliest_delivery_date) {
                $view_params['amazon_order_info']['earliest_delivery_date']['label'] = $this->l('Earliest Delivery Date', basename(__FILE__, '.php'), $this->context->language->iso_code);
                $view_params['amazon_order_info']['earliest_delivery_date']['value'] = $amazonOrder->amazon_order_info->earliest_delivery_date;
                $view_params['amazon_order_info']['earliest_delivery_date']['bold'] = true;
                $view_params['amazon_order_info']['earliest_delivery_date']['color'] = time() > strtotime($amazonOrder->amazon_order_info->earliest_delivery_date) ? 'red' : 'green';
            }
            
            $enabled_shipping_label = $this->isPrimeEnable();
                    
            $view_params['amazon_order_info']['shipping_services']['display'] = $enabled_shipping_label;
            
            if ($enabled_shipping_label && $amazonOrder->amazon_order_info->shipping_services && !empty($amazonOrder->amazon_order_info->shipping_services)) {
                $view_params['amazon_order_info']['shipping_services']['reload'] = 'true';
                
                $shipping_services = $amazonOrder->amazon_order_info->shipping_services ;
                
                // ERROR
                if (isset($shipping_services->errors) && !empty($shipping_services->errors)) { 
                    $shipping_service_errors = unserialize($shipping_services->errors) ;
                    $view_params['amazon_order_info']['shipping_services']['error'] = $shipping_service_errors;
                } 
                
                // Shipping Services
                if (isset($shipping_services->shipping_services) && !empty($shipping_services->shipping_services)) {
                    
                    $shipping_services = unserialize($shipping_services->shipping_services);
                    $view_params['amazon_order_info']['shipping_services']['reload'] = 'false';
                    
                    // file label exist
                    if (isset($shipping_services['ShipmentId']) && isset($shipping_services['url'])) {
                        $url = $this->url.$shipping_services['url'];
                        $view_params['amazon_order_info']['shipping_services']['url'] = $url;
                    }
                    
                    // Shipment created and already have ShipmentId
                    if (isset($shipping_services['ShipmentId']) && !empty($shipping_services['ShipmentId'])) { 
                        $view_params['amazon_order_info']['shipping_services']['ShipmentId']  = $shipping_services['ShipmentId'];
                    // Other step
                    } else {
                        foreach ($shipping_services as $ShippingServiceId => $shipping_service) {
                            $view_params['amazon_order_info']['shipping_services']['list'][$ShippingServiceId]['CarrierName'] = isset($shipping_service['CarrierName']) ? $shipping_service['CarrierName'] : null ;
                            $view_params['amazon_order_info']['shipping_services']['list'][$ShippingServiceId]['Rate'] = isset($shipping_service['Rate']) ? $shipping_service['Rate'] : null ;
                            $view_params['amazon_order_info']['shipping_services']['list'][$ShippingServiceId]['AvailableLabelFormats'] = isset($shipping_service['AvailableLabelFormats']['LabelFormat']) ? $shipping_service['AvailableLabelFormats']['LabelFormat'] : null ;
                        }
                    }
                }
            }

            // Refactor Prime
            $view_params['amazon_order_prime_info'] = $this->buildPrimeParams($amazonOrder);

            if ((int)$amazonOrder->amazon_order_info->mp_status && in_array($amazonOrder->amazon_order_info->mp_status, array(AmazonOrder::TO_CANCEL, AmazonOrder::CANCELED, AmazonOrder::PROCESS_CANCEL))) {
                if ((int)$canceled_state && $amazonOrder->current_state == $canceled_state) {
                    $cancel_stage = true;
                }
            }
        }
        // 2020-09-25: More debug about payment method(s)
        if ($this->ps16x || $this->ps17x) {
            $amazonOrder->csOrderPayment = $amazonOrder->getOrderPaymentCollection()->getResults();
        }
        $this->debugDetails->adminOrder('Order details', get_object_vars($amazonOrder));
        $orderInvoiceTaxes = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'order_invoice_tax t
            JOIN ' . _DB_PREFIX_ . 'order_invoice i ON (t.id_order_invoice = i.id_order_invoice)
            WHERE i.id_order = ' . (int)$id_order);
        $this->debugDetails->adminOrder('Order invoice tax', $orderInvoiceTaxes);

        if ($cancel_stage) {
            //
            // Standard Amazon Order
            //
            return ($this->marketplaceOrderDisplayToCancelOrder($view_params, $amazonOrder, $params));
        } elseif (Tools::strtolower($amazonOrder->module) != 'amazon' && $amazonOrder->marketPlaceChannel != AmazonMultiChannel::AMAZON_FBA_MULTICHANNEL && $this->_multichannel) {
            // Normal Order - Possible to convert it to a multi-channel order
            //
            return ($this->marketplaceOrderDisplayFbaEligibleToMultichannel($view_params, $amazonOrder, $params));
        } elseif ($amazonOrder->marketPlaceChannel == AmazonMultiChannel::AMAZON_FBA_MULTICHANNEL) {
            // Multi-Channel Order
            //
            return ($this->marketplaceOrderDisplayFbaMultichannel($view_params, $amazonOrder, $params));
        } else {
            //
            // Standard Amazon Order
            //
            return ($this->marketplaceOrderDisplayStandardOrder($view_params, $amazonOrder, $params));
        }
    }

    /**
     * Displays options for a FBA-multichannel eligible order
     * @param $view_params
     * @param $order
     * @param $params
     * @return bool|string
     * @throws Exception
     * @throws SmartyException
     */
    private function marketplaceOrderDisplayFbaEligibleToMultichannel(&$view_params, &$order, &$params)
    {
        if (!($mc_order = AmazonMultiChannel::isEligible($order->id))) {
            if (Amazon::$debug_mode) {
                echo Tools::displayError(sprintf('%s/%d: This order is not eligible to FBA Multichannel: %d', basename(__FILE__), __LINE__, $order->id));
            }

            return (false);
        } else {
            $order = $mc_order;
        }

        $marketPlaceIds = AmazonConfiguration::get('MARKETPLACE_ID');
        $europe = false;

        if (isset($marketPlaceIds[$order->id_lang]) && $marketPlaceIds[$order->id_lang]) {
            $europe = AmazonTools::isEuropeMarketplaceId($marketPlaceIds[$order->id_lang]);
        }

        $view_params['js_urls'] = array(
            $this->url . 'views/js/adminorderfba.js?v=' . $this->version,
            $this->url . 'views/js/debug_details.js?v=' . $this->version,
        );
        $this->_marketplaceOrderDetail($view_params, $params);

        if ($europe) {
            $view_params['fbaorder_url'] = $this->url.'functions/fbaorder.php?europe=1';
            $view_params['marketplace_flag'] = $this->images.'geo_flags_web2/flag_eu_32px.png';
        } else {
            $marketPlaceRegion = AmazonConfiguration::get('REGION');

            if (isset($marketPlaceRegion[$this->id_lang])) {
                $lang = 'lang='.$marketPlaceRegion[$this->id_lang];
            } else {
                $lang = null;
            }

            $view_params['fbaorder_url'] = $this->url.'functions/fbaorder.php?'.$lang;
            $view_params['marketplace_flag'] = $this->images.'geo_flags_web2/flag_'.$view_params['marketplace_region'].'_32px.png';
        }

        if (Amazon::$debug_mode) {
            AmazonTools::pre(array(
                "Amazon, Debug Mode" . Amazon::LF,
                sprintf('%s, line %d'."\n", basename(__FILE__), __LINE__),
                nl2br(print_r($view_params, true))
            ));
        }

        $templatePath = $this->path . '/views/templates/admin/admin_order/multichannels_eligible/' .
            ($this->ps177x ? 'ps1.7.7/' : '') . 'AdminOrderMultichannelEligible.tpl';

        return $this->context->smarty->assign($view_params)->fetch($templatePath);
    }

    /**
     * Displays an automatic FBA multichannel order
     * @param $view_params
     * @param $order
     * @param $params
     * @return string
     * @throws Exception
     * @throws SmartyException
     */
    private function marketplaceOrderDisplayFbaMultichannel(&$view_params, &$order, &$params)
    {
        if (!Validate::isLoadedObject($order)) {
            return (false);
        }

        $multiChannelOrder = new AmazonMultiChannel($order->id);

        if (!Validate::isLoadedObject($multiChannelOrder)) {
            if (Amazon::$debug_mode) {
                Tools::displayError(sprintf('%s/%d: This order an invalid FBA Multichannel order: %d', basename(__FILE__), __LINE__, $order->id));
            }

            return (false);
        }

        switch (Tools::strtolower($multiChannelOrder->marketPlaceChannelStatus)) {
            case AmazonMultiChannel::AMAZON_FBA_STATUS_SUBMITED:
                $currentStatus = $this->l('Submited');
                break;
            case AmazonMultiChannel::AMAZON_FBA_STATUS_RECEIVED:
                $currentStatus = $this->l('Received');
                break;
            case AmazonMultiChannel::AMAZON_FBA_STATUS_INVALID:
                $currentStatus = $this->l('Invalid');
                break;
            case AmazonMultiChannel::AMAZON_FBA_STATUS_PLANNING:
                $currentStatus = $this->l('Planning');
                break;
            case AmazonMultiChannel::AMAZON_FBA_STATUS_PROCESSING:
                $currentStatus = $this->l('Processing');
                break;
            case AmazonMultiChannel::AMAZON_FBA_STATUS_CANCELLED:
                $currentStatus = $this->l('Canceled');
                break;
            case AmazonMultiChannel::AMAZON_FBA_STATUS_COMPLETE:
                $currentStatus = $this->l('Complete');
                break;
            case AmazonMultiChannel::AMAZON_FBA_STATUS_COMPLETEPARTIALLED:
                $currentStatus = $this->l('Partially Completed');
                break;
            case AmazonMultiChannel::AMAZON_FBA_STATUS_UNFULFILLABLE:
                $currentStatus = $this->l('Unfulfillable');
                break;
            default:
                $currentStatus = $this->l('Unknown');
        }

        switch (Tools::strtolower($multiChannelOrder->marketPlaceChannelStatus)) {
            case AmazonMultiChannel::AMAZON_FBA_STATUS_CANCELLED:
            case AmazonMultiChannel::AMAZON_FBA_STATUS_UNFULFILLABLE:
            case AmazonMultiChannel::AMAZON_FBA_STATUS_INVALID:
                $canceled = true;
                break;
            default:
                $canceled = false;
                break;
        }
        $marketPlaceIds = AmazonConfiguration::get('MARKETPLACE_ID');
        $europe = false;

        if (isset($marketPlaceIds[$order->id_lang]) && $marketPlaceIds[$order->id_lang]) {
            $europe = AmazonTools::isEuropeMarketplaceId($marketPlaceIds[$order->id_lang]);
        }

        $view_params['js_urls'] = array(
            $this->url . 'views/js/adminorderfba.js?v=' . $this->version,
            $this->url . 'views/js/debug_details.js?v=' . $this->version
        );
        $this->_marketplaceOrderDetail($view_params, $params);

        if ($europe) {
            $view_params['fbaorder_url'] = $this->url.'functions/fbaorder.php?europe=1';
            $view_params['marketplace_flag'] = $this->images.'geo_flags_web2/flag_eu_32px.png';
        } else {
            $marketPlaceRegion = AmazonConfiguration::get('REGION');

            if (isset($marketPlaceRegion[$this->id_lang])) {
                $lang = 'lang='.$marketPlaceRegion[$this->id_lang];
            } else {
                $lang = null;
            }

            $view_params['fbaorder_url'] = $this->url.'functions/fbaorder.php?'.$lang;
            $view_params['marketplace_flag'] = $this->images.'geo_flags_web2/flag_'.$view_params['marketplace_region'].'_32px.png';
        }

        $view_params['marketplace_status'] = $currentStatus;
        $view_params['marketplace_canceled'] = $canceled;

        if (Amazon::$debug_mode) {
            AmazonTools::pre(array(
                "Amazon, Debug Mode" . Amazon::LF,
                sprintf('%s, line %d'."\n", basename(__FILE__), __LINE__),
                nl2br(print_r($view_params, true))
            ));
        }

        $templatePath = $this->path . '/views/templates/admin/admin_order/fba_multichannels/' .
            ($this->ps177x ? 'ps1.7.7/' : '') . 'AdminOrderMultichannel.tpl';

        return $this->context->smarty->assign($view_params)->fetch($templatePath);
    }

    /**
     * Displays a standard order
     * @param $view_params
     * @param AmazonOrder $order
     * @param $params
     * @return string
     * @throws Exception
     * @throws SmartyException
     */
    private function marketplaceOrderDisplayStandardOrder($view_params, $order, &$params)
    {
        $moduleUrl = $this->url;
        $moduleVersion = $this->version;
        $assertVersionQuery = "?v=$moduleVersion";

        $view_params = array_merge(
            $view_params,
            array(
                'js_urls' => array(
                    $moduleUrl . 'views/js/common.js' . $assertVersionQuery,
                    $moduleUrl . 'views/js/adminorder.js' . $assertVersionQuery,
                    $moduleUrl . 'views/js/prime.js' . $assertVersionQuery,
                    $moduleUrl . 'views/js/jquery.qtip.js' . $assertVersionQuery,
                    $moduleUrl . 'views/js/debug_details.js?v=' . $assertVersionQuery,
                ),
                'css_urls' => array(
                    $moduleUrl . 'views/css/common.css' . $assertVersionQuery,
                    $view_params['css_url'],
                    $moduleUrl . 'views/css/jquery.qtip.css' . $assertVersionQuery,
                ),
                'instant_token' => Configuration::get('AMAZON_INSTANT_TOKEN', null, 0, 0),
                'marketplace_url' => AmazonTools::sellerCentralUrl($this->_order_id_lang, $order->marketPlaceOrderId),
                'fulfillment_url' => $this->url . 'functions/fulfillment.php?id_lang=' . $this->id_lang,
                'href_url' => $this->url,
                'marketplace_flag' => $this->images . 'geo_flags_web2/flag_' . $this->geoFlag($this->_order_id_lang) . '_32px.png',
                'tracking_number' => AmazonOrder::getShippingNumber($order),
                'endpoint' => $this->url . 'functions/tools.php?id_lang=' . $this->id_lang,
                'shipping_method' => $order->amazon_order_info->shipping_method,
                'fulfillment_center_id' => $order->amazon_order_info->fulfillment_center_id,
            ),
            $this->buildVIDRParams($view_params)    // Url to generate VCS invoice
        );
        $this->_marketplaceOrderDetail($view_params, $params);

        $this->debugDetails->adminOrder('Amazon view parameters', $view_params);
        $view_params['amz_detailed_debug'] = $this->debugDetails->getAll();
        $this->ed('Amazon data', print_r($view_params, true));
        
        $templatePath = $this->path . '/views/templates/admin/admin_order/standard/' .
            ($this->ps177x ? 'ps1.7.7/' : '') . 'AdminOrderStandard.tpl';

        return $this->context->smarty->assign($view_params)->fetch($templatePath);

        // 2021-11-05: Tran removed Live-Edit an Amazon order
    }

    /**
     * Displays a cancelable order
     * @param $view_params
     * @param $order
     * @param $params
     * @return string
     * @throws Exception
     * @throws SmartyException
     */
    private function marketplaceOrderDisplayToCancelOrder(&$view_params, &$order, &$params)
    {
        $view_params['js_urls'] = array(
            $this->url . 'views/js/adminordercancel.js',
            $this->url . 'views/js/debug_details.js',
        );
        $this->_marketplaceOrderDetail($view_params, $params);
        $view_params['cancel_url'] = $this->url.'functions/canceled.php?id_lang='.$this->id_lang;
        
        $view_params['marketplace_url'] = AmazonTools::sellerCentralUrl($this->_order_id_lang, $order->marketPlaceOrderId);
        $view_params['marketplace_flag'] = $this->images.'geo_flags_web2/flag_'.$view_params['marketplace_region'].'_32px.png';
        $view_params['scenario'] = null;
        

        $amazon_order_cancel = new AmazonOrderCancel();

        switch ((int)$order->amazon_order_info->mp_status) {
            case AmazonOrder::PROCESS_CANCEL:
                $view_params['scenario'] = 'cancel_cancel';
                $view_params['cancel_status'] = AmazonOrder::REVERT_CANCEL;
                break;
            case AmazonOrder::TO_CANCEL:
                $view_params['scenario'] = 'to_cancel';
                $view_params['cancel_status'] = AmazonOrder::PROCESS_CANCEL;
                break;
            case AmazonOrder::CANCELED:
                $view_params['scenario'] = 'canceled';
                $view_params['cancel_status'] = AmazonOrder::CANCELED;
                break;
        }
        $view_params['reasons'] = $amazon_order_cancel->getReasons();

        if (Amazon::$debug_mode) {
            AmazonTools::pre(array(
                "Amazon, Debug Mode".Amazon::LF,
                sprintf('%s, line %d'."\n", basename(__FILE__), __LINE__),
                nl2br(print_r($view_params, true))
            ));
        }

        $templatePath = $this->path . '/views/templates/admin/admin_order/cancel/' .
            ($this->ps177x ? 'ps1.7.7/' : '') . 'AdminOrderCancel.tpl';

        return $this->context->smarty->assign($view_params)->fetch($templatePath);
    }

    /**
     * @param $id_order
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     */
    public static function getOrderDetailList($id_order)
    {
        if (method_exists('OrderDetail', 'getList')) {
            return(OrderDetail::getList($id_order));
        } else {
            return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'order_detail` WHERE `id_order` = '.(int)$id_order);
        }
    }

    /**
     * Assign marketplace detail to template
     * @param $view_params
     * @param $params
     */
    private function _marketplaceOrderDetail(&$view_params, $params)
    {
        if (isset($params['id_order'])) {
            $id_order = $params['id_order'];
            $order_detail = self::getOrderDetailList($id_order);
            if (is_array($order_detail) && count($order_detail)) {
                foreach ($order_detail as $key => $detail) {
                    $marketplace_detail = AmazonOrderItem::getItem($detail['id_order_detail'], $id_order, $detail['product_id'], $detail['product_attribute_id']);
                    if ($marketplace_detail) {
                        $order_detail[$key]['marketplace_detail'] = $marketplace_detail;
                    }
                }
            }
            $view_params['marketplace_detail'] = $order_detail;
            // todo: Use 'module_path' instead
            $view_params['template_path'] = $this->path.'views/templates/admin/admin_order/';
        }

        // Push additional js file
        $view_params['js_urls'][] = $this->url . 'views/js/admin-order-details.js?v=' . $this->version;
    }

    // 2021-11-05: Tran removed Live-Edit an Amazon order

    protected function buildVIDRParams($view_params)
    {
        require_once _PS_MODULE_DIR_ . 'amazon/classes/amazon.vidr_shipment_order_mapping.php';
        $vcsEnable = (bool)AmazonConfiguration::get(AmazonConstant::CONFIG_VCS_ENABLED);
        $previewInvoiceUrl = '';

        if ($vcsEnable && isset($view_params['marketplace_order_id'], $view_params['marketplace_region'])) {
            $mpOrderId = $view_params['marketplace_order_id'];
            $marketplace = $view_params['marketplace_region'];
            $shipment = AmazonVIDRShipmentOrderMapping::getFirstShipmentByOrderAndMarketplace($mpOrderId, $marketplace);
            if ($shipment) {
                $previewInvoiceUrl = $this->context->link->getModuleLink(
                    'amazon',
                    'CustomPdf',
                    array('mp_order_id' => $mpOrderId, 'marketplace' => $marketplace)
                );
            }
        }

        return array(
            'vidr' => array(
                'enable' => $vcsEnable,
                'preview_invoice_url' => $previewInvoiceUrl,
            ),
        );
    }

    private function ed()
    {
        if (Amazon::$debug_mode) {
            $backTrace = debug_backtrace();
            $caller = array_shift($backTrace);
            $fileSegment = explode(DIRECTORY_SEPARATOR, $caller['file']);
            $file = array_pop($fileSegment);

            $debug = array_map(function ($arg) use ($file, $caller) {
                return sprintf('%s(#%d): %s', $file, $caller['line'], $arg);
            }, func_get_args());
            AmazonTools::pre($debug);       
        }
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        static $lang = null;

        if(!$lang)
            $lang = Amazon::availableLang(Language::getIsoById($this->id_lang));

        // Possible cache exception "Language not found in database: en".
        // SqlTranslationLoader->load('validators.en.db', 'en', 'validators')
        // in vendor/symfony/symfony/src/Symfony/Component/Translation/Translator.php (line 381)
        try {
            return (parent::l($string, basename(__FILE__, '.php'), $lang));       
        } catch (Exception $e) {
            return $string;
        }
    }
}
