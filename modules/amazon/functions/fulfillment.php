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
require_once(dirname(__FILE__).'/../classes/amazon.fulfillment.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.order.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.order_info.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.order_item.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.logger.class.php');

/**
 * Class AmazonMerchantFulfillment
 * Use to handle ajax calls from order details + an additional step while import order.
 * Todo: Prime - Consider transform to controller
 */
class AmazonMerchantFulfillment extends Amazon
{
    const STEP_GET_SHIPPING_SERVICE = 1;
    const STEP_CREATE_SHIPMENT = 2;

    public static $get_shipping_service = 'get-shipping-service';   // Get for old / new order, which does not have any fulfillment data
    public static $create_shipment = 'create-shipment';             // Step 2, create from chosen [ShippingServiceId]
    public static $get_shipment = 'get-shipment';                   // Get exist shipment, already created
    
    public $errors = array();
    public $amazon_order_id;

    protected $debugFilePrefix = 'fulfillment';

    /** @var AmazonLogger */
    protected $log;
    
    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    public function dispatch($action = null, $id_order = null)
    {
        $this->log = new AmazonLogger(AmazonLogger::CHANNEL_PRIME);
        $returnTemplate = Tools::getValue('return_template', false);
        $token = Tools::getValue('instant_token');
        $callback = Tools::getValue('callback');
        if ($callback == '?') {
            $callback = 'jsonp_'.time();
        }
        
        $data = array();
        $step = 0;
        
        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s:%s - ', basename(__FILE__), __LINE__));
        } else {
            ob_start();
        }

        if (!isset($action)) {
            $action = Tools::getValue('action');
        }

        if (!isset($id_order)) {
            $id_order = Tools::getValue('id_order');
        }

        if (!$id_order) {
            $this->errors[] = $this->l('Missing id order');
        } elseif (Tools::getValue('action') && 
            (!$token || $token != Configuration::get('AMAZON_INSTANT_TOKEN', null, 0, 0))) {
            $this->errors[] = $this->l('Wrong token');
        } else {
            $this->pdd(sprintf('Action :%s - ', $action), __LINE__, true);
            switch ($action) {
                case self::$get_shipping_service:
                    $step = self::STEP_GET_SHIPPING_SERVICE;
                    if ($result = $this->getShippingService($id_order)) {
                        $data['result'] = $result;
                    }
                    break;
                case self::$create_shipment:
                    $step = self::STEP_CREATE_SHIPMENT;
                    if ($result = $this->createShipment($id_order)) {
                        $data['result'] = $result;
                    }
                    break;
                case self::$get_shipment:
                    $step = self::STEP_CREATE_SHIPMENT;
                    if (!$ShipmentId = Tools::getValue('ShipmentId')) {
                        // todo: Error here causes a bug of unserializing when display again.
                        $this->errors[] = $this->l('Missing Shipment Id');
                    } else {
                        if ($result = $this->getShipment($id_order, $ShipmentId)) {
                            $data['result'] = $result;
                        }
                    }
                    break;
            }
        }
        
        $errors = !empty($this->errors) ? $this->errors : '';
        $results = isset($data['result']) && $data['result'] ? $data['result'] : '';
       
        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s:%s - ', basename(__FILE__), __LINE__));
            CommonTools::p(sprintf('RESULT :%s - ', print_r($results, true)));
            CommonTools::p(sprintf('ERROR :%s - ', print_r($errors, true)));
        } else {
            $console = ob_get_clean();
            $data['console'] = $console;
            $data['errors'] = $errors;
        }
        
        // Update. Beware, $this->amazon_order_id not exist if failed on pre-checks
        AmazonOrder::updOrderMerchantFulfillment($id_order, $this->amazon_order_id, $results, $step, $errors);
        
        // Todo: Prime - Nicer response handler
        if ($callback && Tools::getValue('action')) {
            // Handle ajax from order details
            if ($returnTemplate) {
                // Build prime data
                $amazonOrder = new AmazonOrder($id_order);
                $primeParams = $this->buildPrimeParams($amazonOrder);

                // Return template prime.tpl
                $this->context->smarty->assign(array('prime' => $primeParams));
                $template = $this->context->smarty->fetch($this->path . 'views/templates/admin/admin_order/prime.tpl');
                echo $callback.'('.json_encode(array('success' => true, 'template' => $template)).')';
            } else {
                echo $callback.'('.Tools::jsonEncode($data).')';
            }
            die;
        }

        return $data;
    }

    public function getShippingService($id_order)
    {
        // get amazon order by id_order
        $order = new AmazonOrder($id_order);

        if (!Validate::isLoadedObject($order)) {
            $this->errors[] = sprintf('%s/%d: %s [%s]', basename(__FILE__), __LINE__, $this->l('Unable to load order object'), $id_order);
            return (false);
        }
        
        // check Amazon Order Id
        if (!isset($order->amazon_order_info) || !isset($order->amazon_order_info->mp_order_id)) {
            $this->errors[] = sprintf('%s/%d: %s [%s]', basename(__FILE__), __LINE__, $this->l('Missing Amazon order id'), $id_order);
            return (false);
        }

        if (isset($order->amazon_order_info->shipping_services) 
                && !empty($order->amazon_order_info->shipping_services)) {
            $shipping_services = $order->amazon_order_info->shipping_services ;
            $shipping_services = unserialize($shipping_services->shipping_services) ;

            if (Amazon::$debug_mode) {
                CommonTools::p(sprintf('%s - %s::%s - line #%d', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
                CommonTools::p("Shipping services : ". print_r($shipping_services, true));
            }

            // when already exit
            if (isset($shipping_services['ShipmentId']) && !empty($shipping_services['ShipmentId'])) {
                if (Amazon::$debug_mode) {
                    CommonTools::p(sprintf('%s - %s::%s - line #%d', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
                    CommonTools::p("The order already have a shipment id : ". print_r($shipping_services['ShipmentId'], true));
                }
                return ($shipping_services);
            }
        }

        $id_lang = $order->id_lang;
        $this->amazon_order_id = (string) $order->amazon_order_info->mp_order_id ;
        
        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s:%s - ', basename(__FILE__), __LINE__));
            CommonTools::p(sprintf('getShippingService Order ID :%s - ', $this->amazon_order_id));
        }
            
        $params = array();
        $params['AmazonOrderId'] = $this->amazon_order_id ;
        /*$params['MustArriveByDate'] = isset($order->amazon_order_info->latest_delivery_date) ? 
                date('c', strtotime($order->amazon_order_info->latest_delivery_date)) : 
                (isset($order->amazon_order_info->latest_ship_date) ? 
                date('c', strtotime($order->amazon_order_info->latest_ship_date)) : null); */
        $params['PackageDimensions'] = null;
        $params['Weight'] = null;
        $params['ShipFromAddress'] = AmazonFulfillment::getShipFromAddress($order->id_shop);
        $params['ShippingServiceOptions'] = AmazonFulfillment::$ShippingServiceOptions;

        // Delivery Experience
        // 
        $delivery_experience = AmazonConfiguration::get('DELIVERY_EXPERIENCE');
        if (isset($delivery_experience) && !empty($delivery_experience) 
                && Tools::strlen($delivery_experience) > 3) {
            $params['ShippingServiceOptions']['DeliveryExperience'] = $delivery_experience;
        }

        // Carrier will pick up
        $carrier_will_pickup = AmazonConfiguration::get('CARRIER_WILL_PICKUP');       
        if (isset($carrier_will_pickup)) {
            $params['ShippingServiceOptions']['CarrierWillPickUp'] = ($carrier_will_pickup) ? 'true' : 'false' ;
        }
        
        // get order product
        $order_items = AmazonOrderItem::getAllByMpOrderIds($params['AmazonOrderId']);

        if (empty($order_items) || !$order_items) {
            $this->errors[] = sprintf('%s/%d: %s [%s]', basename(__FILE__), __LINE__, $this->l('Missing Amazon order items'), $id_order);
            return (false);
        }

        if(!$dimension_unit = $this->unit_dimensions()) {
            $this->errors[] = sprintf($this->l('%s/%d: Amazon couldn\'t match this dimension unit: %s, Allow values: inches or centimeters'), basename(__FILE__), __LINE__, Configuration::get('PS_DIMENSION_UNIT'));
            return (false);
        }

        if(!$weight_unit = $this->unit_weight()) {
            $this->errors[] = sprintf($this->l('%s/%d: Amazon couldn\'t match this weight unit: %s, Allow values: oz (for ounces) or g (for grams)'), basename(__FILE__), __LINE__, Configuration::get('PS_WEIGHT_UNIT'));
            return (false);
        }

        $items = array();
        foreach ($order_items as $order_item) {
            if (!isset($order_item['order_item_id']) || !$order_item['order_item_id']) {
                $this->errors[] = sprintf('%s/%d: %s [Order ID %s]', basename(__FILE__), __LINE__, $this->l('Missing order item id'), $id_order);
                return (false);
            }
            if (!isset($order_item['id_product']) || !$order_item['id_product']) {
                $this->errors[] = sprintf('%s/%d: %s [Order ID %s]', basename(__FILE__), __LINE__, $this->l('Missing id_product, order id item:').$order_item['order_item_id'], $id_order);
                return (false);
            }

            $id_product = (int) $order_item['id_product'];
            $product = new Product($id_product);

            if (!Validate::isLoadedObject($product)) {
                $this->errors[] = sprintf('%s/%d: %s [Product ID %s]', basename(__FILE__), __LINE__, $this->l('Unable to load product object'), $id_product);
                return (false);
            }

            // get product dimension            
            if ($product->height > 0) {
                $params['PackageDimensions']['Height'] = (float) $product->height;
            }
            if ($product->depth > 0) {
                $params['PackageDimensions']['Length'] = (float) $product->depth;
            }
            if ($product->width > 0) {
                $params['PackageDimensions']['Width'] = (float) $product->width;
            }
            if ($product->height > 0 && $product->depth > 0 && $product->width > 0) {
                $params['PackageDimensions']['Unit'] = $dimension_unit;
            }
            
            // get product weight
            if($product->weight > 0) {
                if ($weight_unit == 'kg') { // kg to g
                    $product->weight = $product->weight * 1000;
                    $weight_unit = 'g';
                }
                $params['Weight']['Value'] = (float) $product->weight;
                $params['Weight']['Unit'] = $weight_unit;
            }
           
            // Items
            $items[] = array(
                'OrderItemId'=> $order_item['order_item_id'],
                'Quantity'=> $order_item['quantity']
            );
            
        }
        
        if (empty($items)) {
            $this->errors[] = sprintf('%s/%d: %s [Order ID %s]', basename(__FILE__), __LINE__, $this->l('Missing Item List'), $id_order);
            return (false);
        }
        
        $params['ItemList'] = $items;

        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s:%s - ', basename(__FILE__), __LINE__));
            CommonTools::p(sprintf('getShippingService Order ID :%s - ', print_r($params, true)));
        }
        
        // Amazon Fulfillment
        $amazon = AmazonTools::selectPlatforms($id_lang, Amazon::$debug_mode);
        $amazonFulfillment = new AmazonFulfillment($amazon['auth'], $amazon['params'], $amazon['platforms'], Amazon::$debug_mode);
        $result = $amazonFulfillment->getEligibleShippingServices($params) ;

        // Handle error
        if (!$this->handleResponseError($result, $this->l('Get Eligible Shipping Services failed!'))) {
            $this->log->error('Get Eligible Shipping Services failed!', array('order' => $id_order, 'errors' => $this->errors));
            return false;
        }

        if (!isset($result->GetEligibleShippingServicesResult->ShippingServiceList->ShippingService)) {
            $this->errors[] = sprintf('%s/%d: %s [Order ID %s]', basename(__FILE__), __LINE__, $this->l('Empty Shipping Service List'), $id_order);
//            $this->log->error('Empty shipping service list', array('order' => $id_order, 'response' => $result->asXML()));
            return (false);
        }

        $ShippingServices = array();
        foreach ($result->GetEligibleShippingServicesResult->ShippingServiceList->ShippingService as $key => $ShippingService) {
            $key = isset($ShippingService->ShippingServiceId) ? (string) $ShippingService->ShippingServiceId : null ;
            if (isset($key)) {
                $ShippingServices[$key] = json_decode(json_encode($ShippingService), true) ;
            }
        }

        $this->log->debug('getShippingService() result', array('order' => $id_order, 'result' => $ShippingServices, 'errors' => $this->errors));
        $this->pdd('getShippingService(). Errors: ' . print_r($this->errors, true), __LINE__, true);

        return $ShippingServices;
    }

    public function createShipment($id_order)
    {       
        // get amazon order by id_order
        $order = new AmazonOrder($id_order);

        if (!Validate::isLoadedObject($order)) {
            $this->errors[] = sprintf('%s [%s] - (%s/%d)', $this->l('Unable to load order object'), $id_order, basename(__FILE__), __LINE__);
            return (false);
        }

        // check Amazon Order Id
        if (!isset($order->amazon_order_info) || !isset($order->amazon_order_info->mp_order_id)) {
            $this->errors[] = sprintf('%s [%s] - (%s/%d)', $this->l('Missing Amazon order information'), $id_order, basename(__FILE__), __LINE__);
            return (false);
        }
        
        $this->amazon_order_id = (string) $order->amazon_order_info->mp_order_id ;

        // check Shipping Service Id
        if (!$ShippingServiceId = Tools::getValue('ShippingServiceId')) {
            $this->errors[] = sprintf('%s [%s] - (%s/%d)', $this->l('Please select the Shipping Service Id'), $id_order, basename(__FILE__), __LINE__);
            return (false);
        }
        
        // check Available Label Formats
        if (!$AvailableLabelFormats = Tools::getValue('AvailableLabelFormats')) {
            $this->errors[] = sprintf('%s [%s] - (%s/%d)', $this->l('Please select the Label Format'), $id_order, basename(__FILE__), __LINE__);
            return (false);
        }

        $CustomTextForLabel = Tools::getValue('CustomTextForLabel');
            
        $id_lang = $order->id_lang;

        $params = array();
        $params['ShippingServiceId'] = $ShippingServiceId;
        $params['AmazonOrderId'] = $this->amazon_order_id ;
        //$params['LabelCustomization']['CustomTextForLabel'] = !empty($CustomTextForLabel) ? substr($CustomTextForLabel, 0, 14) : '';
        //$params['LabelCustomization']['StandardIdForLabel'] = $this->amazon_order_id ;
        //$params['MustArriveByDate'] = isset($order->amazon_order_info->latest_delivery_date) ? $order->amazon_order_info->latest_delivery_date : null;
        $params['PackageDimensions'] = null;
        $params['Weight'] = null;
        $params['ShipFromAddress'] = AmazonFulfillment::getShipFromAddress($order->id_shop);

        // Shipping Service Options
        if (isset($order->amazon_order_info->shipping_services) && !empty($order->amazon_order_info->shipping_services)) {
            $shipping_services = $order->amazon_order_info->shipping_services ;
            $shipping_services = unserialize($shipping_services->shipping_services) ;

            if (Amazon::$debug_mode) {
                CommonTools::p(sprintf('%s - %s::%s - line #%d', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
                CommonTools::p("Shipping services ID : ". print_r($ShippingServiceId, true));
                CommonTools::p("Shipping services : ". print_r($shipping_services, true));
            }
            
            // when already exit
            if (isset($shipping_services['ShipmentId']) && !empty($shipping_services['ShipmentId'])) {
                return $this->getShipment($id_order, (string)$shipping_services['ShipmentId']);
            }
            
            // check the selected Shipping Service Id
            if (isset($shipping_services[$ShippingServiceId]['ShippingServiceOptions'])
                && !empty($shipping_services[$ShippingServiceId]['ShippingServiceOptions'])) {
                $ShippingServiceOptions = $shipping_services[$ShippingServiceId]['ShippingServiceOptions'] ;
                
                $delivery_experience = AmazonConfiguration::get('DELIVERY_EXPERIENCE');
                if (isset($ShippingServiceOptions['DeliveryExperience']) && Tools::strlen($ShippingServiceOptions['DeliveryExperience'])) {
                    $delivery_experience = trim($ShippingServiceOptions['DeliveryExperience']);
                }

                $carrier_will_pickup = (bool)AmazonConfiguration::get('CARRIER_WILL_PICKUP');
                if (isset($ShippingServiceOptions['CarrierWillPickUp'])) {
                    $carrier_will_pickup = (string)$ShippingServiceOptions['CarrierWillPickUp'] ;
                }
                
                /*if (isset($ShippingServiceOptions['DeclaredValue']['Amount']) && isset($ShippingServiceOptions['DeclaredValue']['CurrencyCode'])) {
                    $declared_currency = (string) $ShippingServiceOptions['DeclaredValue']['CurrencyCode'] ;
                    $declared_amount = (float) $ShippingServiceOptions['DeclaredValue']['Amount'] ;
                } else {
                    $this->errors[] = sprintf('%s [%s] - (%s/%d)', $this->l('Declared value are required'), $id_order, basename(__FILE__), __LINE__);
                    return (false);
                }*/
                
                $params['ShippingServiceOptions'] = array(
                    'DeliveryExperience' => $delivery_experience,
                    'CarrierWillPickUp' => $carrier_will_pickup,
                    //'DeclaredValue_CurrencyCode' => $declared_currency,
                    //'DeclaredValue_Amount' => $declared_amount,
                    'LabelFormat' => $AvailableLabelFormats
                );

            } else {
                $this->errors[] = sprintf('%s [%s] - (%s/%d)', $this->l('Shipping Service Options is not valid'), $id_order, basename(__FILE__), __LINE__);
                return (false);
            }

        } else {
            $this->errors[] = sprintf('%s [%s] - (%s/%d)', $this->l('The Get Eligible Shipping Services is not completely'), $id_order, basename(__FILE__), __LINE__);
            return (false);
        }
        
        // get order product
        $order_items = AmazonOrderItem::getAllByMpOrderIds($params['AmazonOrderId']);

        if (empty($order_items) || !$order_items) {
            $this->errors[] = sprintf('%s/%d: %s [%s]', basename(__FILE__), __LINE__, $this->l('Missing Amazon order items'), $id_order);
            return (false);
        }

        if(!$dimension_unit = $this->unit_dimensions()) {
            $this->errors[] = sprintf($this->l('Amazon couldn\'t match this dimension unit: %s, Allow values: inches or centimeters'), Configuration::get('PS_DIMENSION_UNIT'));
            return (false);
        }

        if(!$weight_unit = $this->unit_weight()) {
            $this->errors[] = sprintf($this->l('Amazon couldn\'t match this weight unit: %s, Allow values: oz (for ounces) or g (for grams)'), Configuration::get('PS_WEIGHT_UNIT'));
            return (false);
        }

        $items = array();
        foreach ($order_items as $order_item) {
            if (!isset($order_item['order_item_id']) || !$order_item['order_item_id']) {
                $this->errors[] = sprintf('%s [Order ID %s]', $this->l('Missing order item id'), $id_order);
                return (false);
            }
            if (!isset($order_item['id_product']) || !$order_item['id_product']) {
                $this->errors[] = sprintf('%s [Order ID %s]', $this->l('Missing id_product, order id item:').$order_item['order_item_id'], $id_order);
                return (false);
            }

            $id_product = (int) $order_item['id_product'];
            $product = new Product($id_product);

            if (!Validate::isLoadedObject($product)) {
                $this->errors[] = sprintf('%s [Product ID %s]', $this->l('Unable to load product object'), $id_product);
                return (false);
            }

            // get product dimension           
            if ($product->height > 0) {
                $params['PackageDimensions']['Height'] = (float) $product->height;
            }
            if ($product->depth > 0) {
                $params['PackageDimensions']['Length'] = (float) $product->depth;
            }
            if ($product->width > 0) {
                $params['PackageDimensions']['Width'] = (float) $product->width;
            }
            if ($product->height > 0 && $product->depth > 0 && $product->width > 0) {
                $params['PackageDimensions']['Unit'] = $dimension_unit;
            }

            // get product weight
            if($product->weight > 0) {
                if ($weight_unit == 'kg') { // kg to g
                    $product->weight = $product->weight * 1000;
                    $weight_unit = 'g';
                }
                $params['Weight']['Value'] = (float) $product->weight;
                $params['Weight']['Unit'] = $weight_unit;
            }

            // Items
            $items[] = array(
                'OrderItemId'=> $order_item['order_item_id'],
                'Quantity'=> $order_item['quantity']
            );
        }

        if (empty($items)) {
            $this->errors[] = sprintf('%s [Order ID %s]', $this->l('Missing Item List'), $id_order);
            return (false);
        }

        $params['ItemList'] = $items;

        // Amazon Fulfillment
        $amazon = AmazonTools::selectPlatforms($id_lang, Amazon::$debug_mode);
        $amazonFulfillment = new AmazonFulfillment($amazon['auth'], $amazon['params'], $amazon['platforms'], Amazon::$debug_mode);
        $result = $amazonFulfillment->createShipment($params) ;

        // Handle error
        if (!$this->handleResponseError($result, $this->l('Create shipping label failed!'))) {
            $this->log->error('Create shipping label failed!', array('order' => $id_order, 'errors' => $this->errors));
            return false;
        }
        $this->log->success('Create shipping label successfully', array('order' => $id_order, 'data' => $result));

        if (!isset($result->CreateShipmentResult->Shipment->ShipmentId)) {
            $this->errors[] = sprintf('%s [Order ID %s]', $this->l('Empty Shipment Id'), $id_order);
            return (false);
        }

        $Shipment = array ();

        // generate the label
        if (isset($result->CreateShipmentResult->Shipment->Label) 
            && isset($result->CreateShipmentResult->Shipment->Label->FileContents)
            && isset($result->CreateShipmentResult->Shipment->Label->FileContents->Contents)) {

            // File Format
            if (isset($result->CreateShipmentResult->Shipment->Label->LabelFormat)) {
                $file_format = AmazonTools::strtolower((string)$result->CreateShipmentResult->Shipment->Label->LabelFormat);
            } else {
                $file_format = 'png';
            }
            
            if (!isset($file_format) || empty($file_format)) {
                if (isset($result->GetShipmentResult->Shipment->Label->FileContents->FileType)) {
                    $filetype = AmazonTools::strtolower((string)$result->CreateShipmentResult->Shipment->Label->FileContents->FileType);
                    switch ($filetype) {
                        case 'application/pdf' :
                            $file_format = 'pdf';
                            break;
                        case 'application/zpl' :
                            $file_format = 'zpl';
                            break;
                        default :
                            $file_format = 'png';
                    }
                } else {
                    $file_format = 'png';
                }
            }
            $this->log->debug('createShipment(). Label file type: ' . $file_format);

            // To extract document data from a compressed file
            $ShipmentLabel = (string) $result->CreateShipmentResult->Shipment->Label->FileContents->Contents;
            $Shipment['url'] = $this->generateLabel($ShipmentLabel, $file_format);
        }
        
        // to save ShipmentId
        $Shipment['ShipmentId'] = (string)$result->CreateShipmentResult->Shipment->ShipmentId;
        
        return $Shipment ;
    }

    public function getShipment($id_order, $ShipmentId)
    {
        // get amazon order by id_order
        $order = new AmazonOrder($id_order);

        if (!Validate::isLoadedObject($order)) {
            $this->errors[] = sprintf('%s/%d: %s [%s]', basename(__FILE__), __LINE__, $this->l('Unable to load order object'), $id_order);
            return (false);
        }
        
        // check Amazon Order Id
        if (!isset($order->amazon_order_info) || !isset($order->amazon_order_info->mp_order_id)) {
            $this->errors[] = sprintf('%s/%d: %s [%s]', basename(__FILE__), __LINE__, $this->l('Missing Amazon order id'), $id_order);
            return (false);
        }

        $this->amazon_order_id = (string) $order->amazon_order_info->mp_order_id ;
        
        $id_lang = $order->id_lang;
        
        // check Amazon Order Id
        if (!isset($ShipmentId) || empty($ShipmentId)) {
            $this->errors[] = sprintf('%s/%d: %s [%s]', basename(__FILE__), __LINE__, $this->l('Missing ShipmentId'), $id_order);
            
            return (false);
        }
            
        $params = array();
        $params['ShipmentId'] = (string)$ShipmentId ;
        
        // Amazon Fulfillment
        $amazon = AmazonTools::selectPlatforms($id_lang, Amazon::$debug_mode);
        $amazonFulfillment = new AmazonFulfillment($amazon['auth'], $amazon['params'], $amazon['platforms'], Amazon::$debug_mode);
        $result = $amazonFulfillment->getShipment($params) ;

        // Handle error
        if (!$this->handleResponseError($result, $this->l('getShipment by ShipmentID unsuccessfully'))) {
            $this->log->error('Get shipping label by shipment id failed!', array('order' => $id_order, 'shipment' => $ShipmentId, 'errors' => $this->errors));
            return false;
        }

        $Shipment = array ();

        // generate the label
        if (isset($result->GetShipmentResult->Shipment->Label) 
            && isset($result->GetShipmentResult->Shipment->Label->FileContents)
            && isset($result->GetShipmentResult->Shipment->Label->FileContents->Contents)) {

            // File Format
            if (isset($result->GetShipmentResult->Shipment->Label->LabelFormat)) {
                $file_format = AmazonTools::strtolower((string)$result->CreateShipmentResult->Shipment->Label->LabelFormat);
            } 
            
            if (!isset($file_format) || empty($file_format)) {
                if (isset($result->GetShipmentResult->Shipment->Label->FileContents->FileType)) {
                    $filetype = AmazonTools::strtolower((string)$result->CreateShipmentResult->Shipment->Label->FileContents->FileType);
                    switch ($filetype) {
                        case 'application/pdf' :
                            $file_format = 'pdf';
                            break;
                        case 'application/zpl' :
                            $file_format = 'zpl';
                            break;
                        default :
                            $file_format = 'png';
                            break;
                    }
                } else {
                    $file_format = 'png';
                }
            }

            // To extract document data from a compressed file
            $ShipmentLabel = (string) $result->GetShipmentResult->Shipment->Label->FileContents->Contents;
            $Shipment['url'] = $this->generateLabel($ShipmentLabel, $file_format);
        }
        
        // to save ShipmentId
        $Shipment['ShipmentId'] = (string)$result->GetShipmentResult->Shipment->ShipmentId;
        
        return $Shipment;
    }
    
    public function unit_dimensions()
    {
        // Package Dimensions Unit values: inches or centimeters
        $dimensionUnit = Tools::strtolower(preg_replace('/[^A-Za-z]/', '', Configuration::get('PS_DIMENSION_UNIT')));
        $dimension_unit = false ;

        switch ($dimensionUnit) {
            case 'cm':
            case 'centimeter':
            case 'centimeters':
                $dimension_unit = 'centimeters';
                break;
            case 'in':
            case 'inch':
            case 'inches':
                $dimension_unit = 'inches';
                break;
        }
        
        return $dimension_unit;
    }

    public function unit_weight()
    {
       // Weight Unit values: oz (for ounces) or g (for grams)
        $weightUnit = Tools::strtolower(preg_replace('/[^A-Za-z]/', '', Configuration::get('PS_WEIGHT_UNIT')));
        $weight_unit = false ;

        switch ($weightUnit) {
            case 'oz ':
            case 'ounce':
            case 'ounces':
                $weight_unit = 'oz';
                break;
            case 'g':
            case 'gram':
            case 'grams':
                $weight_unit = 'g';
                break;
            case 'kg':
            case 'kilogram':
            case 'kilograms':
                $weight_unit = 'kg';
                break;
        }

        return $weight_unit;
    }
    
    /* To extract document data from a compressed file

    * Decode the Base64-encoded string.
    * Save the decoded string with a “.gzip” extension.
    * Extract the PDF, PNG, or ZPL file from the GZIP file.
    * */
    public function generateLabel($source, $file_format = 'png')
    { 
        ini_set("zlib.output_compression", 1); 
        
        // File path
        $file_path = dirname(__FILE__).'/../labels/';
        
        if (!is_dir($file_path)) {
            if (!@mkdir($file_path)) {
                $this->pdd('Unable to create directory: ' . $file_path, __LINE__, true);
                $this->log->error('Unable to create directory: ' . $file_path);
                $this->errors[] = $this->l('Unable to create directory') . ': ' . ($file_path);
                return (false);
            }
            if (!@chmod($file_path, 0777)) {
                $this->pdd('Unable to set permission on directory: ' . $file_path, __LINE__, true);
                $this->log->error('Unable to set permission on directory: ' . $file_path);
            }
        }
        
        // File name
        $file_name = 'label-'. $this->amazon_order_id .'.'.$file_format ;

        // 1. Decode the Base64-encoded string.
        $content = base64_decode($source);//TODO: Validation: Use to evaluate base64 encoded values, required

        // 2. Save the decoded string with a ".gzip" extension.
        if(!empty($content)) {
            if(file_exists($file_path . $file_name)) { // remove old file
                unlink($file_path . $file_name);
            } 
            
            // 3. Extract the PDF, PNG, or ZPL file from the GZIP file.
            $fp = fopen($file_path . $file_name.'.gz', "w");
            fwrite($fp, $content);
            fclose($fp);
            
            exec("gzip -d ".$file_path . $file_name.".gz");
            $url = 'labels/'.$file_name;
            $this->pdd('Label file path: ' . $url, __LINE__, true);
            $this->log->success('Generate Label successfully.', array('url' => $url));
            return $url;
        } else {
            $this->log->error('Generate Label failed! Invalid source content', array('decoded' => $content, 'source' => $source));
            $this->errors[] = $this->l('Generate Label failed!') .' ' . $this->l('Invalid content');
            return (false);
        }
    }

    protected function handleResponseError($responseXml, $generalError)
    {
        if (!$responseXml) {
            $this->errors[] = (isset(AmazonFulfillment::$errors) && !empty(AmazonFulfillment::$errors)) ?
                AmazonFulfillment::$errors : $generalError;
            return false;
        }

        return true;
    }
}

if(Tools::getValue('action')) {
    $amazonMerchantFulfillment = new AmazonMerchantFulfillment();
    $amazonMerchantFulfillment->dispatch();
}
