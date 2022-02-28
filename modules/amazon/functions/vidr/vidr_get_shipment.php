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

require_once(dirname(__FILE__) . '/../../classes/amazon.vidr_shipment.class.php');
require_once(dirname(__FILE__) . '/../../classes/amazon.vidr_shipment_order_mapping.php');
require_once(dirname(__FILE__) . '/../../includes/amazon.admin_configure.php');

/**
 * Class AmazonFunctionGetVIDRShipment
 */
class AmazonFunctionVIDRGetShipment extends AmazonFunctionWrapper
{
    const REPORT_TYPE_FLAT = '_GET_FLAT_FILE_VAT_INVOICE_DATA_REPORT_';

    protected $report_prefix = 'vat_invoice_data_report';
    protected $function = 'get_vidr_shipment';
    /**
     * @var string 
     * array(
     *  fr => array(last_time => timestamp, report_request_id => number),
     *  ...
     * )
     */
    protected $reportConfigKey = 'GET_VAT_INVOICE_DATA_REPORT';

    protected static $reportRequiredColumns = array(
        AmazonVIDRShipment::RP_SHIPPING_ID,
        AmazonVIDRShipment::RP_ORDER_ID,
        AmazonVIDRShipment::RP_TRANSACTION_TYPE,
        AmazonVIDRShipment::RP_SHIPMENT_DATE,
        AmazonVIDRShipment::RP_ORDER_DATE,
        AmazonVIDRShipment::RP_CURRENCY,
        AmazonVIDRShipment::RP_MARKETPLACE_ID,
        AmazonVIDRShipment::RP_IS_AMAZON_INVOICED,
        AmazonVIDRShipment::RP_SKU,
        AmazonVIDRShipment::RP_ASIN,
        AmazonVIDRShipment::RP_PRODUCT_NAME,
        AmazonVIDRShipment::RP_ITEM_QTY,
        AmazonVIDRShipment::RP_ITEM_VAT_INCL_AMOUNT,
        AmazonVIDRShipment::RP_ITEM_VAT_EXCL_AMOUNT,
        AmazonVIDRShipment::RP_ITEM_VAT_AMOUNT,
        AmazonVIDRShipment::RP_ITEM_VAT_RATE
    );

    protected static $reportOptionalColumns = array(
        AmazonVIDRShipment::RP_TRANSACTION_ID,
        AmazonVIDRShipment::RP_INVOICE_STATUS,
        AmazonVIDRShipment::RP_INVOICE_STATUS_DESCRIPTION,
        AmazonVIDRShipment::RP_IS_BUSINESS_ORDER,
        // Addresses
        AmazonVIDRShipment::RP_RECEIVER_NAME,
        AmazonVIDRShipment::RP_SHIP_ADDR_1,
        AmazonVIDRShipment::RP_SHIP_ADDR_2,
        AmazonVIDRShipment::RP_SHIP_ADDR_3,
        AmazonVIDRShipment::RP_SHIP_CITY,
        AmazonVIDRShipment::RP_SHIP_STATE,
        AmazonVIDRShipment::RP_SHIP_POSTCODE,
        AmazonVIDRShipment::RP_SHIP_COUNTRY,
        AmazonVIDRShipment::RP_BILLING_NAME,
        AmazonVIDRShipment::RP_BILL_ADDR_1,
        AmazonVIDRShipment::RP_BILL_ADDR_2,
        AmazonVIDRShipment::RP_BILL_ADDR_3,
        AmazonVIDRShipment::RP_BILL_CITY,
        AmazonVIDRShipment::RP_BILL_STATE,
        AmazonVIDRShipment::RP_BILL_POSTCODE,
        AmazonVIDRShipment::RP_BILL_COUNTRY,
        AmazonVIDRShipment::RP_BILL_PHONE,
        AmazonVIDRShipment::RP_ITEM_PROMO_VAT_EXCL_AMOUNT,
        AmazonVIDRShipment::RP_ITEM_PROMO_VAT_INCL_AMOUNT,
        AmazonVIDRShipment::RP_ITEM_PROMO_VAT_RATE,
        AmazonVIDRShipment::RP_ITEM_PROMO_VAT_AMOUNT,
        AmazonVIDRShipment::RP_ITEM_PROMO_ID,
        // Shipping
        AmazonVIDRShipment::RP_SHIPPING_VAT_EXCL_AMOUNT,
        AmazonVIDRShipment::RP_SHIPPING_VAT_INCL_AMOUNT,
        AmazonVIDRShipment::RP_SHIPPING_VAT_RATE,
        AmazonVIDRShipment::RP_SHIPPING_VAT_AMOUNT,
        AmazonVIDRShipment::RP_SHIPPING_PROMO_VAT_EXCL_AMOUNT,
        AmazonVIDRShipment::RP_SHIPPING_PROMO_VAT_INCL_AMOUNT,
        AmazonVIDRShipment::RP_SHIPPING_PROMO_VAT_RATE,
        AmazonVIDRShipment::RP_SHIPPING_PROMO_VAT_AMOUNT,
        AmazonVIDRShipment::RP_SHIPPING_PROMO_ID,
        // Gift wrapping
        AmazonVIDRShipment::RP_WRAPPING_VAT_EXCL_AMOUNT,
        AmazonVIDRShipment::RP_WRAPPING_VAT_INCL_AMOUNT,
        AmazonVIDRShipment::RP_WRAPPING_VAT_RATE,
        AmazonVIDRShipment::RP_WRAPPING_VAT_AMOUNT,
        AmazonVIDRShipment::RP_WRAPPING_PROMO_VAT_EXCL_AMOUNT,
        AmazonVIDRShipment::RP_WRAPPING_PROMO_VAT_INCL_AMOUNT,
        AmazonVIDRShipment::RP_WRAPPING_PROMO_VAT_RATE,
        AmazonVIDRShipment::RP_WRAPPING_PROMO_VAT_AMOUNT,
        AmazonVIDRShipment::RP_WRAPPING_PROMO_ID,
        // Other
        AmazonVIDRShipment::RP_CITATION_DE,
        AmazonVIDRShipment::RP_CITATION_EN,
        AmazonVIDRShipment::RP_CITATION_ES,
        AmazonVIDRShipment::RP_CITATION_FR,
        AmazonVIDRShipment::RP_CITATION_IT,
        AmazonVIDRShipment::RP_SELLER_VAT_NUMBER,
        AmazonVIDRShipment::RP_BUYER_VAT_NUMBER,
    );

    public function __construct(
        $cronToken,
        $amazonLang,
        $forceUseFile,
        $forceDlReport,
        $forceRqReportOption,
        $forceGetRqList,
        $testXRowsOnly
    )
    {
        parent::__construct($cronToken, $amazonLang);

        $this->forceUseFile = $forceUseFile;
        $this->forceDlReport = $forceDlReport;
        $this->forceRqReport = $forceRqReportOption;
        $this->forceGetRqList = $forceGetRqList;
        $this->testXRowsOnly = $testXRowsOnly;
    }

    public function runGetVIDRShipments()
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

        // 3. Build report file path, after init webservice
        $this->buildFileInventory();

        // 4. Main flow
        require_once dirname(__FILE__) . '/../../includes/amazon.report_request.php';
        $this->amzReportRequest = new AmazonReportRequest(
            $this->reportConfigKey,
            $this->amazonLang,
            self::REPORT_TYPE_FLAT,
            $this->expired_report_request_seconds,
            $this->api
        );
        $hasReportFile = $this->reportUseDownloadedFileOrDownloadNew();
        if (!$hasReportFile) {
            $this->pd('Nothing more to do. End here.');
        } else {
            $this->pd('Preparing to process file');
            $this->processReportFile();
        }

        // 7. todo: Save a batch
    }

    protected function preCheckModuleSetting()
    {
        $moduleFeatures = $this->amazon_features;
        $expertMode = isset($moduleFeatures, $moduleFeatures['expert_mode']) ? (bool)($moduleFeatures['expert_mode']) : false;
        $settingEnable = $expertMode && (bool)AmazonConfiguration::get(AmazonConstant::CONFIG_VCS_ENABLED);
        if (!$settingEnable) {
            $this->pd('VIDR is not enable');
            return false;
        }

        return true;
    }

    /**
     * Build report file path
     */
    protected function buildFileInventory()
    {
        $platform = $this->amazonPlatform;
        $merchantId = trim($platform['auth']['MerchantID']);
        $region = trim($platform['params']['Country']);
        $this->file_inventory = sprintf(
            '%s%s_%s_%s.raw',
            $this->path . 'vidr/',
            $this->report_prefix,
            $merchantId,
            $region
        );
    }

    protected function processReportFile()
    {
        // 1. Debug trace & validation
        $lines = $this->processReportExplodeLines();
        if (count($lines) < 1) {
            $this->pdd('Nothing to do!', __LINE__);
            return;
        }

        // 2. Extract header
        $headerLine = array_shift($lines);
        $headerNamedKey = $this->processReportExtractHeader($headerLine);
        if (count($headerNamedKey) < 1) {
            $this->pdd('Ignore file because of header!', __LINE__);
            return;
        }

        // 3. Extract body
        $shipments = $this->processReportExtractBody($lines, $headerNamedKey);

        // 4. Save shipments to DB
        $this->processReportSaveShipments($shipments);

        // 5. Rename downloaded file
        $this->renameDownloadedFile();
    }

    /**
     * @return array
     */
    protected function processReportExplodeLines()
    {
        $this->pd('Processing report file', true);

        $reportData = AmazonTools::fileGetContents($this->file_inventory);
        if (false == $reportData) {
            $this->pdd(sprintf('Unable to read input file! (%s)', $this->file_inventory), __LINE__);
            return array();
        }

        if (!$reportData || empty($reportData)) {
            $this->pdd('Report file is empty!', __LINE__);
            return array();
        }

        $lines = explode(Amazon::LF, $reportData);
        if (!is_array($lines) || !count($lines)) {
            $this->pdd('Report file with no line!', __LINE__);
            return array();
        }

        $this->separate(true);
        $this->pd(sprintf('Report has %d line', count($lines)), true);

        return $lines;
    }

    /**
     * @param string $headerLine
     * @return array(column_name => index_in_file)
     */
    protected function processReportExtractHeader($headerLine)
    {
        if (empty($headerLine)) {
            $this->pdd('Report header is empty', __LINE__);
            return array();
        }

        $headerColumns = $this->explodeReportLine($headerLine, "\t");
        // todo: Maybe need additional check on is-amazon-invoiced
        $missingRequireColumns = array_diff(self::$reportRequiredColumns, $headerColumns);
        if (count($missingRequireColumns) > 0) {
            $this->pdd('Report misses required columns: ' . print_r($missingRequireColumns, true), __LINE__);
            return array();
        }

        return array_flip($headerColumns);
    }

    /**
     * @param array $body
     * @param array $headerNamedKey
     * @return array
     */
    protected function processReportExtractBody($body, $headerNamedKey)
    {
        // Original data, validate row
        $reportRows = $this->processReportExtractBodyOriginalData($body, $headerNamedKey);

        // Group by shipment_id -> array(info, data -> order_id -> item)
        return $this->processReportExtractBodyGroupByShipment($reportRows);
    }

    /**
     * First parse report body
     * @param array $body
     * @param array $headerNamedKey
     * @return array
     */
    protected function processReportExtractBodyOriginalData($body, $headerNamedKey)
    {
        $reportRows = array();

        foreach ($body as $lineIndex => $line) {
            if (empty($line)) {
                continue;
            }

            $lineData = $this->explodeReportLine($line, "\t");
            $reportRowFirstParse = array();
            $fieldValidation = true;

            // Check required fields
            foreach (self::$reportRequiredColumns as $mustHasColumn) {
                $columnIndex = $headerNamedKey[$mustHasColumn];
                if (!isset($lineData[$columnIndex])) {
                    $this->pdd(
                        sprintf('Row %d does not have required column: %s', $lineIndex, $mustHasColumn),
                        __LINE__
                    );

                    // Stop as long as there is invalid field in this line
                    $fieldValidation = false;
                    break;
                }

                $fieldData = $lineData[$columnIndex];
                if (!in_array($mustHasColumn,
                    array(AmazonVIDRShipment::RP_SHIPMENT_DATE, AmazonVIDRShipment::RP_ORDER_DATE))) {
                    $reportRowFirstParse[$mustHasColumn] = trim($lineData[$columnIndex]);
                } else {
                    $dateField = strtotime($fieldData);
                    if (false === $dateField) {
                        $this->pdd(
                            sprintf('%s is strange, ignore row %d: %s', $mustHasColumn, $lineIndex, $fieldData),
                            __LINE__
                        );

                        // Stop if dates are invalid
                        $fieldValidation = false;
                        break;
                    }

                    $reportRowFirstParse[$mustHasColumn] = date('Y-m-d H:i:s', $dateField);
                }
            }

            // Check optional fields
            foreach (self::$reportOptionalColumns as $optionalColumn) {
                $columnIndex = $headerNamedKey[$optionalColumn];
                $reportRowFirstParse[$optionalColumn] = isset($lineData[$columnIndex]) ? trim($lineData[$columnIndex]) : '';
            }

            // Only accept valid row
            if ($fieldValidation) {
                $reportRows[] = $reportRowFirstParse;
            }

            // Testing only, fetch x numbers of row only
            $fetchXRowsOnly = $this->testXRowsOnly;
            if ($fetchXRowsOnly && count($reportRows) > (int)$fetchXRowsOnly) {
                break;
            }
        }

        return $reportRows;
    }

    /**
     * @param array $reportRows
     * @return array
     */
    protected function processReportExtractBodyGroupByShipment($reportRows)
    {
        $shipments = array();

        foreach ($reportRows as $reportRow) {
            $shippingId = $reportRow[AmazonVIDRShipment::RP_SHIPPING_ID];
            $orderId = $reportRow[AmazonVIDRShipment::RP_ORDER_ID];
            $transactionId = $reportRow[AmazonVIDRShipment::RP_TRANSACTION_ID];
            $transactionType = $reportRow[AmazonVIDRShipment::RP_TRANSACTION_TYPE];
            $marketplace = $reportRow[AmazonVIDRShipment::RP_MARKETPLACE_ID];
            $invoiceStatus = $reportRow[AmazonVIDRShipment::RP_INVOICE_STATUS];
            $invoiceStatusDescription = $reportRow[AmazonVIDRShipment::RP_INVOICE_STATUS_DESCRIPTION];
            $isAmazonInvoiced = Tools::strtoupper($reportRow[AmazonVIDRShipment::RP_IS_AMAZON_INVOICED]) == 'TRUE';
            $isBusinessOrder = Tools::strtoupper($reportRow[AmazonVIDRShipment::RP_IS_BUSINESS_ORDER]) == 'TRUE';

            $shipmentDate = $reportRow[AmazonVIDRShipment::RP_SHIPMENT_DATE];
            $sellerVatNo = $reportRow[AmazonVIDRShipment::RP_SELLER_VAT_NUMBER];
            $buyerVatNo = $reportRow[AmazonVIDRShipment::RP_BUYER_VAT_NUMBER];
            $currency = $reportRow[AmazonVIDRShipment::RP_CURRENCY];

            $billingName = $reportRow[AmazonVIDRShipment::RP_BILLING_NAME];
            $billingAddress1 = $reportRow[AmazonVIDRShipment::RP_BILL_ADDR_1];
            $billingAddress2 = $reportRow[AmazonVIDRShipment::RP_BILL_ADDR_2];
            $billingCity = $reportRow[AmazonVIDRShipment::RP_BILL_CITY];
            $billingPostcode = $reportRow[AmazonVIDRShipment::RP_BILL_POSTCODE];
            $billingState = $reportRow[AmazonVIDRShipment::RP_BILL_STATE];
            $billingCountry = $reportRow[AmazonVIDRShipment::RP_BILL_COUNTRY];
            $billingPhone = $reportRow[AmazonVIDRShipment::RP_BILL_PHONE];

            // Init shipment info
            if (!isset($shipments[$shippingId])) {
                $shipments[$shippingId] = array(
                    'info' => array(
                        'shipping_id' => $shippingId,
                        'marketplace' => $marketplace,
                        'transaction_id' => $transactionId,
                        'transaction_type' => $transactionType,
                        'invoice_status' => $invoiceStatus,
                        'invoice_status_description' => $invoiceStatusDescription,
                        'is_amazon_invoiced' => $isAmazonInvoiced,
                        'is_business_order' => $isBusinessOrder,
                        'shipment_date' => $shipmentDate,
                        'seller_vat_number' => $sellerVatNo,
                        'currency' => $currency,
                        'order_data' => array(),
                        'order_count' => 0,
                        'item_count' => 0
                    ),
                    'data' => array()
                );
            }

            // Fulfill shipment data, for all type of shipment (SHIPMENT / RETURN / REFUND)
            $shipments[$shippingId]['data'][$orderId][] = $reportRow;
            // Save all orders data (order mapping table) of this shipment
            $ordersData = $shipments[$shippingId]['info']['order_data'];
            if (!isset($ordersData[$orderId])) {
                $ordersData[$orderId] = array(
                    'order_id' => $orderId,
                    'buyer_vat_number' => $buyerVatNo,
                    'billing_address' => array(
                        'name' => $billingName,
                        'address1' => $billingAddress1,
                        'address2' => $billingAddress2,
                        'city' => $billingCity,
                        'postcode' => $billingPostcode,
                        'state' => $billingState,
                        'country' => $billingCountry,
                        'phone' => $billingPhone,
                    ),
                );
            }
            $shipments[$shippingId]['info']['order_data'] = $ordersData;
            // Count total order / item of the shipment
            $shipments[$shippingId]['info']['order_count'] = count($ordersData); // or count($shipments[$shippingId]['data'])
            $shipments[$shippingId]['info']['item_count']++;
        }

        return $shipments;
    }

    protected function processReportSaveShipments($shipments)
    {
        if (count($shipments) < 1) {
            $this->pdd('No shipment to process, stop!', __LINE__);
            return;
        }

        if (!AmazonTools::tableExists(_DB_PREFIX_ . AmazonDBManager::TABLE_MARKETPLACE_VIDR_SHIPMENT)) {
            $this->pdd('Table amazon_vidr_shipment is missing, stop!', __LINE__);
            return;
        }
        if (!AmazonTools::tableExists(_DB_PREFIX_ . AmazonDBManager::TABLE_MARKETPLACE_VIDR_SHIPMENT_ORDER_MAPPING)) {
            $this->pdd('Table amazon_vidr_shipment_order for mapping is missing, stop!', __LINE__);
            return;
        }

        foreach ($shipments as $shipment) {
            $info = $shipment['info'];
            $shipmentId = $info['shipping_id'];
            $invoiceStatus = $info['invoice_status'];
            $transactionType = $info['transaction_type'];

            // Store shipment-order mapping, for SHIPMENT only
            if (AmazonVIDRShipment::TRANSACTION_TYPE_SHIPMENT == $transactionType) {
                $ordersData = $info['order_data'];
                AmazonVIDRShipmentOrderMapping::replaceBulkMappingsByShipment($shipmentId, $ordersData);
            }

            $marketplace = $info['marketplace'];
            $isBusinessOrder = $info['is_business_order'];
            $isNonBusinessITOrder = Tools::strtolower($marketplace) == AmazonVIDRShipment::MARKETPLACE_IT && !$isBusinessOrder;

            $addResult = AmazonVIDRShipment::addShipment(
                $shipmentId,
                $info['marketplace'],
                $info['transaction_id'],
                $transactionType,
                $invoiceStatus,
                $info['invoice_status_description'],
                $info['is_amazon_invoiced'],
                $info['shipment_date'],
                $info['seller_vat_number'],
                $this->getCurrencyByCode($info['currency']),
                $info['order_count'],
                $info['item_count'],
                serialize($shipment['data']),
                $isNonBusinessITOrder ? AmazonVIDRShipment::PROCESS_STATUS_IT_NON_BUSINESS_ORDER : AmazonVIDRShipment::PROCESS_STATUS_PENDING
            );

            if (!$addResult) {
                $this->pdd("Add failed! Shipment: $shipmentId", __LINE__);
                $this->pd(AmazonVIDRShipment::getDebugContent(), true);
            } else {
                $this->pd(sprintf('Add VIDR shipment successfully - %s', $shipmentId));
            }
        }
    }

    /**
     * todo: Same as orders_reports:_getCurrencyByCode()
     * Get currency id from code
     * @param $currencyCode
     * @return bool
     */
    protected function getCurrencyByCode($currencyCode)
    {
        $currency = Currency::getIdByIsoCode($currencyCode);

        if (is_int($currency)) {
            $currency = new Currency($currency);
        }

        return $currency ? $currency->id : false;
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
