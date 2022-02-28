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

require_once(dirname(__FILE__).'/../classes/amazon.webservice.class.php');

class AmazonFulfillment extends AmazonWebService
{
    public static $errors;
    
    public static $required_field = array(
        'AmazonOrderId',
        //'MustArriveByDate',
        'PackageDimensions',
        'Weight',
        'ShipFromAddress',
        'ShippingServiceOptions',
        'ItemList'
    );

    public static $ShipFromAddress = array(
        'Name' => null,
        'AddressLine1' => null,
        'City' => null,
        'StateOrProvinceCode' => null,
        'PostalCode' => null,
        'CountryCode' => null,
        'Email' => null,
        'Phone' => null
    );
    public static $PackageDimensions = array(
        'Length' => null,
        'Width' => null,
        'Height' => null,
        'Unit' => null
    );
    public static $Weight = array(
        'Value' => null,
        'Unit' => null
    );
    public static $ShippingServiceOptions = array(
        'DeliveryExperience' => 'DeliveryConfirmationWithoutSignature',
        'CarrierWillPickUp' => 'false',
        'DeclaredValue_CurrencyCode' => null,
        'DeclaredValue_Amount' => null
    );

     public static $DeliveryExperience = array(
        'DeliveryConfirmationWithAdultSignature',
        'DeliveryConfirmationWithSignature', // Required for DPD (UK).
        'DeliveryConfirmationWithoutSignature',
        'NoTracking'
    );

    // todo: Build full structure
    protected static $validation = array(
        'AmazonOrderId' => array('r' => 1),
        'PackageDimensions' => array('r' => 1),
        'Weight' => array('r' => 1),
        'ShippingServiceOptions' => array('r' => 1),
        'ItemList' => array('r' => 1),
        'ShipFromAddress' => array(
            'r' => 1,
            'c' => array(
                'AddressLine1' => array('r' => 1),
                'City' => array('r' => 1),
                'StateOrProvinceCode' => array('r' => 0),
                'PostalCode' => array('r' => 1),
                'CountryCode' => array('r' => 1),
                'Name' => array('r' => 1),
                'Email' => array('r' => 1),
                'Phone' => array('r' => 1),
            )
        ),
        // MustArriveByDate removed
    );
    
    // todo: Remove this
    public function __construct($auth, $from, $marketPlaces = null)
    {
        parent::__construct($auth, $from, $marketPlaces, Amazon::$debug_mode);
    }
    
    /**
     * @param $ShipmentRequestDetails
     *
     * @return bool|SimpleXMLElement
     */
    public function getEligibleShippingServices($ShipmentRequestDetails)
    {
        $pass = true ;
        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s(#%d): GetEligibleShippingServices call', basename(__FILE__), __LINE__));
        }

        $params = array();
        $params['Action'] = 'GetEligibleShippingServices';
        $params['Marketplace'] = $this->mpid;

        if (empty($ShipmentRequestDetails)) {
            if (Amazon::$debug_mode) {
                CommonTools::p(sprintf('%s(#%d): GetEligibleShippingServices ERROR empty Shipment Request Details', basename(__FILE__), __LINE__));
            }
            self::$errors = sprintf('Empty Shipment Request Details');
            return (false);
        }

        $validate_shipment_request_details = self::validateShipmentRequestDetails($ShipmentRequestDetails);
        $pass = $validate_shipment_request_details['valid'];
        $missing_field = $validate_shipment_request_details['missing'];
       
        if(!$pass) {
            if (Amazon::$debug_mode) {
                CommonTools::p(sprintf('%s(#%d): GetEligibleShippingServices ERROR fields are required : [%s]', basename(__FILE__), __LINE__, implode(", ", $missing_field)));
            }
            self::$errors = sprintf('Fields are required : [%s]', implode(", ", $missing_field));
            return (false);
        }

        foreach ($ShipmentRequestDetails as $fields => $details) {

            if (!isset($details))
                continue;
            if ($fields == 'ItemList') {
                foreach ($details as $key => $detail) {
                     if (!isset($detail))
                        continue;
                    $params['ShipmentRequestDetails.ItemList.Item.'.($key+1).'.OrderItemId'] = $detail['OrderItemId'];
                    $params['ShipmentRequestDetails.ItemList.Item.'.($key+1).'.Quantity'] = (int)$detail['Quantity'];
                }
            } else {
                if (is_array($details)) {
                    foreach ($details as $field => $detail) {
                        if (!isset($detail))
                            continue;
                        $field = str_replace("_", ".", $field);
                        $params['ShipmentRequestDetails.'.$fields.'.'.$field] = $detail;
                    }
                } else {
                    $params['ShipmentRequestDetails.'.$fields] = $details;
                }
            }
        }

        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s(#%d): GetEligibleShippingServices params: %s', basename(__FILE__), __LINE__, nl2br(print_r($params, true))));
        }

        $response = $this->_simpleCallWs(self::WSTR_MERCHANT_FULFILLMENT, $params);

        return $this->handleError($response, 'GetEligibleShippingServices', __LINE__) ? $response : false;
    }

    /**
     * @param $ShipmentRequestDetails
     * @return bool|SimpleXMLElement
     */
    public function createShipment($ShipmentRequestDetails)
    {
        $this->pdd('createShipment call', __LINE__, true);
        if (empty($ShipmentRequestDetails)) {
            $this->pdd('CreateShipment ERROR empty Shipment Request Details', __LINE__, true);
            self::$errors = 'Empty Shipment Request Details';
            return (false);
        }

        $params = array(
            'Action' => 'CreateShipment',
            'Marketplace' => $this->mpid
        );

        $validate_shipment_request_details = self::validateShipmentRequestDetails($ShipmentRequestDetails);
        $missing_field = $validate_shipment_request_details['missing'];

        if(!$validate_shipment_request_details['valid']) {
            $missing_field_concat = implode(", ", $missing_field);
            $this->pdd(sprintf('CreateShipment ERROR fields are required: [%s]', $missing_field_concat), __LINE__, true);
            self::$errors = sprintf('Fields are required : [%s]', $missing_field_concat);
            return (false);
        }

        foreach ($ShipmentRequestDetails as $fields => $details) {
            if (!isset($details))
                continue;

            if ($fields == "ShippingServiceId") {
                $params['ShippingServiceId'] = $details;

            } elseif ($fields == 'ItemList') {
                foreach ($details as $key => $detail) {
                     if (!isset($detail))
                        continue;
                    $params['ShipmentRequestDetails.ItemList.Item.'.($key+1).'.OrderItemId'] = $detail['OrderItemId'];
                    $params['ShipmentRequestDetails.ItemList.Item.'.($key+1).'.Quantity'] = (int)$detail['Quantity'];
                }

            } else {
                if (is_array($details)) {
                    foreach ($details as $field => $detail) {
                        if (!isset($detail))
                            continue;
                        $field = str_replace("_", ".", $field);
                        $params['ShipmentRequestDetails.'.$fields.'.'.$field] = $detail;
                    }
                } else {
                    $params['ShipmentRequestDetails.'.$fields] = $details;
                }
            }
        }

        $this->pdd(sprintf('CreateShipment params: %s', nl2br(print_r($params, true))), __LINE__, true);

        $response = $this->_simpleCallWs(self::WSTR_MERCHANT_FULFILLMENT, $params);

        return $this->handleError($response, 'CreateShipment', __LINE__) ? $response : false;
    }

    /**
     * @param $ShipmentRequestDetails
     * @return bool|SimpleXMLElement
     */
    public function getShipment($ShipmentRequestDetails)
    {
        $this->pdd('createShipment call', __LINE__, true);
        if (empty($ShipmentRequestDetails)) {
            $this->pdd('CreateShipment ERROR empty Shipment Request Details', __LINE__, true);
            self::$errors = sprintf('Empty Shipment Request Details');
            return (false);
        }

        $params = array(
            'Action' => 'GetShipment',
            'Marketplace' => $this->mpid
        );

        if(!isset($ShipmentRequestDetails['ShipmentId']) || empty($ShipmentRequestDetails['ShipmentId'])) {
            $this->pdd('GetShipment ERROR fields are required: [ShipmentId]', __LINE__, true);
            self::$errors = 'GetShipment ERROR fields are required: [ShipmentId]';
            return (false);
        }

        foreach ($ShipmentRequestDetails as $fields => $details) {
            $params[$fields] = $details;
        }

        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s(#%d): GetShipment params: %s', basename(__FILE__), __LINE__, nl2br(print_r($params, true))));
        }

        $response = $this->_simpleCallWs(self::WSTR_MERCHANT_FULFILLMENT, $params);

        return $this->handleError($response, 'GetShipment', __LINE__) ? $response : false;
    }

    public static function getShipFromAddress($id_shop)
    {
        require_once(dirname(__FILE__) . '/../includes/amazon.admin_configure.php');
        $prime_address = AmazonConfiguration::get(AmazonAdminConfigure::PRIME_ADDRESS);

        // Can not merge country to $address_components, because we need CountryCode (differ from other components)
        $id_shop_country = Configuration::get('PS_SHOP_COUNTRY_ID', null, null, $id_shop);
        $fallback_country = new Country($id_shop_country);
        $country_code = isset($prime_address['country']) && $prime_address['country'] ? $prime_address['country'] : $fallback_country->iso_code;
        $address = array('CountryCode' => Tools::strtoupper($country_code));

        $address_components = array(
            array('ak' => 'Name',         'pk' => 'shop_name', 'ps' => 'PS_SHOP_NAME',  'r' => 1),
            array('ak' => 'AddressLine1', 'pk' => 'address1',  'ps' => 'PS_SHOP_ADDR1', 'r' => 1),
            array('ak' => 'AddressLine2', 'pk' => 'address2',  'ps' => 'PS_SHOP_ADDR2', 'r' => 0),
            array('ak' => 'City',         'pk' => 'city',      'ps' => 'PS_SHOP_CITY',  'r' => 1, 'l' => 30),
            array('ak' => 'PostalCode',   'pk' => 'postcode',  'ps' => 'PS_SHOP_CODE',  'r' => 1),
            array('ak' => 'Email',        'pk' => 'email',     'ps' => 'PS_SHOP_EMAIL', 'r' => 1),
            array('ak' => 'Phone',        'pk' => 'phone',     'ps' => 'PS_SHOP_PHONE', 'r' => 1),
        );
        foreach ($address_components as $component) {
            $amazon_key = $component['ak'];
            $prime_key = $component['pk'];
            $ps_default_key = $component['ps'];
            $value = isset($prime_address[$prime_key]) && $prime_address[$prime_key]
                ? $prime_address[$prime_key] : Configuration::get($ps_default_key, null, null, $id_shop);
            // Truncate component if it has length limit
            if (isset($component['l']) && $component['l'] > 0) {
                $value = Tools::substr($value, 0, $component['l']);
            }
            if ($component['r'] || $value) {
                $address[$amazon_key] = $value;
            }
        }

        return $address;
    }

    // todo: Validate & parse data
    protected static function validateShipmentRequestDetails($data)
    {
        $missing_fields = array();

        foreach (self::$validation as $item_key => $item) {
            // Validate parent key
            $item_required = $item['r'];
            if ($item_required
                && (!isset($data[$item_key]) || empty($data[$item_key]))
            ) {
                $missing_fields[] = $item_key;
                break;
            }

            $item_data = $data[$item_key];

            // Validate children
            if (isset($item['c'])) {
                foreach ($item['c'] as $child_key => $child) {
                    $child_required = $child['r'];
                    if ($child_required
                        && (!isset($item_data[$child_key]) || empty($item_data[$child_key]))
                    ) {
                        $missing_fields[] = "$item_key.$child_key";
                        break;
                    }
                }
            }
        }

        return array('valid' => !count($missing_fields), 'missing' => $missing_fields);
    }

    /**
     * @param $response
     * @param $action
     * @param $line
     * @return bool
     */
    protected function handleError($response, $action, $line)
    {
        if (!$response) {
            $responseError = $this->getResponseError();
            $responseMsg = $responseError->Code . ': ' . $responseError->Message;

            if (!$responseError->Code && !$responseError->Message) {
                $msg = sprintf('%s ERROR - response is false or null', $action);
            } else {
                $msg = sprintf("%s ERROR - response is: %s", $action, $responseMsg);
            }
            $this->pdd($msg, $line, true);
            self::$errors = $msg;

            return false;
        }

        return true;
    }

    protected function pd($message, $debugModeOnly = false)
    {
        if (!$debugModeOnly || Amazon::$debug_mode) {
            CommonTools::p($message);
        }
    }

    protected function pdd($message, $line, $debugModeOnly = false)
    {
        if (!$debugModeOnly || Amazon::$debug_mode) {
            CommonTools::p(sprintf("%s(#%d): $message", 'amazon.fulfillment.class', $line));
        }
    }
}
