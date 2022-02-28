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
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

class AmazonFulfillmentMessage
{
    // Move all Amazon carrier codes to setting file amazon_carrier_codes.ini

    public $amzId;
    public $psId;
    public $carrierCode;
    public $carrierName;
    public $shippingNumber;
    public $shippingMethod;
    public $timestamp;

    private $isAmzCarrier;

    public function __construct($amzId, $psId, $carrier, $shippingNumber, $shippingMethod, $timestamp)
    {
        $this->amzId = $amzId;
        $this->psId = $psId;
        $this->shippingNumber = $shippingNumber;
        $this->shippingMethod = $shippingMethod;
        $this->timestamp = $timestamp;
        $this->resolveCarrier($carrier);
    }

    public function hasCarrier()
    {
        return $this->carrierCode || $this->carrierName;
    }

    /**
     * https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_4_1/OrderFulfillment.xsd
     * @param DOMDocument $Document
     * @param $messageID
     * @return DOMElement|false
     */
    public function buildXML(DOMDocument $Document, $messageID)
    {
        if (!$Document) {
            $Document = new DOMDocument();
        }
        $message = $Document->createElement('Message');

        $messageIDText = $Document->createTextNode($messageID);
        $messageIDX = $Document->createElement('MessageID');
        $messageIDX->appendChild($messageIDText);

        $AmazonOrderIDText = $Document->createTextNode($this->amzId);
        $AmazonOrderID = $Document->createElement('AmazonOrderID');
        $AmazonOrderID->appendChild($AmazonOrderIDText);
        $FulfillmentDateText = $Document->createTextNode($this->getDate());
        $FulfillmentDate = $Document->createElement('FulfillmentDate');
        $FulfillmentDate->appendChild($FulfillmentDateText);
        $OrderFulfillment = $Document->createElement('OrderFulfillment');
        $OrderFulfillment->appendChild($AmazonOrderID);
        $OrderFulfillment->appendChild($FulfillmentDate);

        $message->appendChild($messageIDX);
        $message->appendChild($OrderFulfillment);

        if ($this->hasCarrier()) {
            $FulfillmentData = $Document->createElement('FulfillmentData');
            $OrderFulfillment->appendChild($FulfillmentData);
            if ($this->isAmzCarrier) {
                $CarrierCodeText = $Document->createTextNode($this->carrierCode);
                $CarrierCode = $Document->createElement('CarrierCode');
                $CarrierCode->appendChild($CarrierCodeText);
                $FulfillmentData->appendChild($CarrierCode);
            } else {
                $CarrierNameText = $Document->createTextNode($this->carrierName);
                $CarrierName = $Document->createElement('CarrierName');
                $CarrierName->appendChild($CarrierNameText);
                $FulfillmentData->appendChild($CarrierName);
            }

            if ($this->shippingMethod) {
                $ShippingMethodText = $Document->createTextNode($this->shippingMethod);
                $ShippingMethod = $Document->createElement('ShippingMethod');
                $ShippingMethod->appendChild($ShippingMethodText);
                $FulfillmentData->appendChild($ShippingMethod);
            }

            if ($this->shippingNumber) {
                $ShipperTrackingNumberText = $Document->createTextNode($this->shippingNumber);
                $ShipperTrackingNumber = $Document->createElement('ShipperTrackingNumber');
                $ShipperTrackingNumber->appendChild($ShipperTrackingNumberText);
                $FulfillmentData->appendChild($ShipperTrackingNumber);
            }
        }

        return $message;
    }

    public function toString()
    {
        $timestamp = $this->timestamp ? date('Y-m-d H:i:s', $this->timestamp) : 'n/a';

        return sprintf(
            'Order: %s | Merchant order id: %d | Shipping number: %s | Carrier: %s, carrier alt./name: %s | Shipping method: %s | Shipping date/time: %s',
            $this->amzId, $this->psId, $this->shippingNumber, $this->carrierCode, $this->carrierName, $this->shippingMethod, $timestamp
        );
    }

    private function getDate()
    {
        $timestamp = $this->timestamp;
        if (!$timestamp || !is_numeric($timestamp)) {
            $timestamp = time() - 5400;
            if (!date_default_timezone_get()) {
                date_default_timezone_set('Europe/Helsinki');
            }
        }

        return gmdate('c', $timestamp);
    }

    private function resolveCarrier($carrier)
    {
        require_once dirname(__FILE__) . '/../../classes/amazon.shipping.outgoing.php';
        $carrier = trim($carrier);

        if (in_array(trim($carrier), AmazonShippingOutgoing::loadCarriersDefaultCache())) {
            $this->isAmzCarrier = true;
            $this->carrierCode = $carrier;
        } else {
            $this->isAmzCarrier = false;
            $this->carrierCode = 'Other';
        }
        $this->carrierName = $carrier;
    }
}
