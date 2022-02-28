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
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

require_once(dirname(__FILE__) . '/../../classes/amazon.order.class.php');
require_once(dirname(__FILE__) . '/../../classes/amazon.vidr_shipment.class.php');
require_once(dirname(__FILE__) . '/../../classes/amazon.vidr_shipment_order_mapping.php');
require_once(dirname(__FILE__) . '/../../classes/amazon.vidr_shipment_invoice.php');
require_once(dirname(__FILE__) . '/../../includes/amazon.admin_configure.php');

/**
 * Class AmazonFunctionUploadVATInvoice
 */
class AmazonFunctionVIDRUploadVATInvoice extends AmazonFunctionWrapper
{
    // Throttle limit: 1 invoice upload per 3 seconds, or 20 invoices per minute, or 1200 invoices per hour, or 28800 invoices per day
    const MAX_SHIPMENT_TO_PROCESS = 300;

    const SUBMIT_STATUS = '_SUBMITTED_';

    /** @var string Store generated invoice for debug */
    protected $generatedInvoicePathAndPrefix;
    protected $function = 'upload_vat_invoice';

    /** @var string */
    protected $lastAPIResult;

    protected $mpOrderId;
    protected $isDemo = false;
    protected $limitUpload = true;
    protected $limitUploadNumber = 1;
    protected $countUpload = 0;

    // Module configuration
    protected $amzVCS;
    protected $amzVCSUpload;
    protected $amzVCSUpdateVatNo;

    public function __construct(
        $cronToken,
        $amazonLang,
        $mpOrderId,
        $isDemo = false,
        $limitUpload = false,
        $limitUploadNumber = self::MAX_SHIPMENT_TO_PROCESS
    )
    {
        parent::__construct($cronToken, $amazonLang);
        $this->mpOrderId = $mpOrderId;
        $this->isDemo = $isDemo;
        $this->limitUpload = $limitUpload;
        $this->limitUploadNumber = $limitUploadNumber;

        $this->amzVCS = (bool)AmazonConfiguration::get(AmazonConstant::CONFIG_VCS_ENABLED);
        $this->amzVCSUpload = (bool)AmazonConfiguration::get(AmazonConstant::CONFIG_VCS_SEND_INVOICE);
        $this->amzVCSUpdateVatNo = (bool)AmazonConfiguration::get(AmazonConstant::CONFIG_VCS_UPDATE_CUSTOMER_VAT_NUMBER);
    }

    public function bulkUpload()
    {
        // 1. Validations
        if (!$this->preCheckRequest()
            || !$this->preCheckModuleSetting()
            || !$this->resolveNecessaryParameters()) {
            return;
        }

        // 2. Init Amazon webservice. Do this first to generate necessary components related to merchant (file_inventory)
        $this->initWebservice();
        if (!$this->api) {
            $this->pd($this->l('Unable to login'));
            return;
        }

        // 3. Build path to store PDF invoice
        $this->buildFileInventory();

        // 4. Set correct context
        $this->setContext();

        // 5. Processing shipments
        if ($this->mpOrderId) {
            $this->processAnOrder();
        } else {
            $this->processAllShipments();       
        }
    }

    /**
     * todo: Consider below
     * Let upload run as long as there are pending shipment, even when VIDR is not enable
     */
    protected function preCheckModuleSetting()
    {
        if (!$this->amzVCS || !$this->amzVCSUpload) {
            $this->pd('VIDR is not enable');
            return false;
        }

        return true;
    }

    protected function buildFileInventory()
    {
        $platform = $this->amazonPlatform;
        $merchantId = trim($platform['auth']['MerchantID']);
        $this->generatedInvoicePathAndPrefix = $this->path . 'vidr/invoices/' . $merchantId . '_';
    }

    protected function setContext()
    {
        $amazonLang = $this->amazonLang;
        $psLangId = Language::getIdByIso($amazonLang);
        if ($psLangId) {
            $psLang = new Language($psLangId);
            if (Validate::isLoadedObject($psLang)) {
                $this->context->language = $psLang;
            }
        }
        $this->fixPdfContextForPS17x();
    }
    
    protected function processAnOrder()
    {
        $mappings = AmazonVIDRShipmentOrderMapping::getAllMappingsByOrderId($this->mpOrderId);
        if (!$mappings || !is_array($mappings)) {
            $this->pd(sprintf('Order not exist: %s', $this->mpOrderId));
        } else {
            foreach ($mappings as $mapping) {
                // Beware: Shipment and mapping are connect by shipment_id only
                $shipmentId = $mapping['shipment_id'];
                $shipment = AmazonVIDRShipment::getShipmentByShipmentId($shipmentId);
                if (!$shipment) {
                    $this->pd(sprintf('Shipment disappears while looping: %s!', $shipmentId));
                } else {
                    $this->processShipment($shipment);
                }
            }
        }
    }

    protected function processAllShipments()
    {
        $amazonLang = $this->amazonLang;
        $statusProcessing = AmazonVIDRShipment::PROCESS_STATUS_PROCESSING;

        // To prevent infinity loop, or data changes during loop, get list of pending shipment first
        $unfinishedShipments = AmazonVIDRShipment::getUnfinishedShipments($amazonLang, self::MAX_SHIPMENT_TO_PROCESS);
        if (!$unfinishedShipments || !is_array($unfinishedShipments) || !count($unfinishedShipments)) {
            $this->pd('No pending shipment to process!');
        } else {
            foreach ($unfinishedShipments as $unfinishedShipment) {
                if ($this->reachLimitUpload()) {
                    $this->pd(sprintf('Limit upload: %d', $this->limitUploadNumber));
                    break;
                }

                $shipmentInternalId = $unfinishedShipment['id'];
                $shipmentId = $unfinishedShipment['shipping_id'];
                $shipmentStatus = $unfinishedShipment['process_status'];
                $shipment = AmazonVIDRShipment::getShipmentByInternalId($shipmentInternalId);
                if (!$shipment) {
                    $this->pd(sprintf('Shipment disappears while looping: %s!', $shipmentId));
                } else {
                    // Mark shipment as processing
                    if ($statusProcessing != $shipmentStatus) {
                        AmazonVIDRShipment::updateShipmentProcessStatus($shipmentInternalId, $statusProcessing);
                    }

                    $processStatus = $this->processShipment($shipment);
                    if ($processStatus == AmazonVIDRShipment::PROCESS_STATUS_THROTTLED) {
                        break;
                    }
                }
            }

            $this->pd('Done for this turn.');
        }
    }

    protected function reachLimitUpload()
    {
        return $this->limitUpload && ($this->countUpload >= $this->limitUploadNumber);
    }

    protected function processShipment($shipment)
    {
        $shipmentInternalId = $shipment['id'];
        $shippingId = $shipment['shipping_id'];

        // Process shipment
        $processStatus = $this->processReportGenerateInvoiceAndUpload($shipment);
        $success = AmazonVIDRShipment::PROCESS_STATUS_DONE == $processStatus;

        // Update status after done
        $updateShipmentProcessedResult = AmazonVIDRShipment::updateShipmentProcessedResult(
            $shipmentInternalId,
            $processStatus,
            $this->getLastAPIResult()
        );

        // Print status
        if ($success) {
            if ($updateShipmentProcessedResult) {
                $this->pd(sprintf('Processed shipment: %s', $shippingId));
            } else {
                $this->pdd(sprintf(
                    'Processed successfully but cannot update process result for shipment: %s',
                    $shippingId
                ), __LINE__);
                $this->pd(AmazonVIDRShipment::getDebugContent(), true);
            }
        } else {
            if ($updateShipmentProcessedResult) {
                $this->pdd(sprintf('Process shipment failed! - %s', $shippingId), __LINE__);
            } else {
                $this->pdd(sprintf(
                    'Processed failed and cannot update process result for shipment: %s',
                    $shippingId
                ), __LINE__);
                $this->pd(AmazonVIDRShipment::getDebugContent(), true);
            }
        }

        $this->separate();

        return $processStatus;
    }

    /**
     * @param $shipment
     * @return int
     */
    protected function processReportGenerateInvoiceAndUpload($shipment)
    {
        $shipmentId = $shipment['shipping_id'];
        $mkpCode = $shipment['marketplace'];
        $shipmentData = $shipment['shipment_data'];
        $currencyId = $shipment['id_currency'];
        $shipmentDate = $shipment['shipment_date'];
        $transactionType = $shipment['transaction_type'];
        $transactionId = isset($shipment['transaction_id']) ? $shipment['transaction_id'] : null; // Not exist earlier

        $vidrInvoice = new AmazonVIDRShipmentInvoice($this->context, $shipmentId, $mkpCode,
            $transactionId, $transactionType, $shipmentData, $shipmentDate, $currencyId, $this->debug);
        $invoicePdf = $vidrInvoice->generateVatInvoice(false);
        if (!$invoicePdf) {
            // Grab invoice debug if any before leave
            $this->concatDebug($vidrInvoice->getDebugContent());
            return $vidrInvoice->getStatus();
        }
        $this->storeInvoicePdf($shipmentId, $invoicePdf, $transactionType);

        // 2021-03-04: Experiment: Stop uploading for non-business order
        if ($vidrInvoice->getStatus() == AmazonVIDRShipment::PROCESS_STATUS_IT_NON_BUSINESS_ORDER) {
            $this->pd('Stop upload for IT non-business order');
            return AmazonVIDRShipment::PROCESS_STATUS_IT_NON_BUSINESS_ORDER;
        } else {
            // Upload invoice and return status
            return $this->uploadInvoice($shipmentId, $invoicePdf, $vidrInvoice->getFeedOptions());       
        }
    }

    protected function storeInvoicePdf($shipmentId, $invoice, $transactionType)
    {
        $orderIds = array();
        $ordersInShipment = AmazonVIDRShipmentOrderMapping::getOrdersByShipment($shipmentId);
        if ($ordersInShipment && is_array($ordersInShipment) && count($ordersInShipment)) {
            foreach ($ordersInShipment as $orderInShipment) {
                $orderIds[] = $orderInShipment['mp_order_id'];
            }
        }
        $orderIdsSuffix = implode('_', $orderIds);

        $fileName = "{$this->generatedInvoicePathAndPrefix}{$shipmentId}_{$transactionType}_{$orderIdsSuffix}.pdf";
        $this->pdd('Saving invoice to: ' . $fileName, __LINE__);

        $saveInvoice = file_put_contents($fileName, $invoice);
        if (false === $saveInvoice) {
            $this->pdd('Cannot save invoice: ' . $fileName, __LINE__);
        }
    }

    protected function uploadInvoice($shipmentId, $invoice, $feedOptions)
    {
        if ($this->isDemo) {
            $uploadInvoiceResult = $this->demoUploadInvoiceFailed();
        } else {
            $uploadInvoiceResult = $this->api->uploadVATInvoice($invoice, $feedOptions);
        }
        $this->countUpload++;
        $this->lastAPIResult = $uploadInvoiceResult;
        if (!$uploadInvoiceResult) {
            $responseError = $this->api->getResponseError();
            $rawResponse = print_r($responseError, true);
            $this->lastAPIResult = $rawResponse;
            $this->pdd($this->l('Unable to send data to Amazon!'), __LINE__);
            $this->pdE($rawResponse);

            if (is_object($responseError)
                && ((isset($responseError->Code) && $responseError->Code == 'RequestThrottled')
                    || (isset($responseError->Message) && $responseError->Message == 'Request is throttled')
                )
            ) {
                return AmazonVIDRShipment::PROCESS_STATUS_THROTTLED;
            }

            return AmazonVIDRShipment::PROCESS_STATUS_ERROR;

        } else {
            if (!$uploadInvoiceResult instanceof SimpleXMLElement
                || !isset($uploadInvoiceResult->SubmitFeedResult, $uploadInvoiceResult->SubmitFeedResult->FeedSubmissionInfo, $uploadInvoiceResult->SubmitFeedResult->FeedSubmissionInfo->FeedProcessingStatus)
                || Tools::strtolower(self::SUBMIT_STATUS) != Tools::strtolower($uploadInvoiceResult->SubmitFeedResult->FeedSubmissionInfo->FeedProcessingStatus)) {
                $this->pd('Upload invoice done with error! Shipment: ' . $shipmentId);
                $this->pd($uploadInvoiceResult->asXML());
                return AmazonVIDRShipment::PROCESS_STATUS_ERROR;
            } else {
                $this->pd('Upload invoice successfully: ' . $shipmentId);
                return AmazonVIDRShipment::PROCESS_STATUS_DONE;
            }
        }
    }

    protected function demoUploadInvoiceFailed()
    {
        return new SimpleXMLElement('<?xml version="1.0"?><SubmitFeedResponse xmlns="http://mws.amazonaws.com/doc/2009-01-01/"><SubmitFeedResult><FeedSubmissionInfo><FeedSubmissionId>233084018316</FeedSubmissionId><FeedType>_UPLOAD_VAT_INVOICE_</FeedType><SubmittedDate>2020-02-24T06:21:10+00:00</SubmittedDate><FeedProcessingStatus>_SOME_ERROR_</FeedProcessingStatus></FeedSubmissionInfo></SubmitFeedResult><ResponseMetadata><RequestId>8ff121e9-ee21-4f8d-a0ef-81e4b7d61ef0</RequestId></ResponseMetadata></SubmitFeedResponse>');
    }

    /**
     * @return string
     */
    protected function getLastAPIResult()
    {
        $result = $this->lastAPIResult;
        $this->lastAPIResult = null;

        if ($result instanceof SimpleXMLElement) {
            return $result->asXML();
        }

        return $result;
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        static $lang = null;

        if (!$lang) {
            $lang = Amazon::availableLang(Language::getIsoById($this->id_lang));
        }

        return (parent::l($string, basename(__FILE__, '.php'), $lang));
    }
}
