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

class AmazonVIDRShipmentInvoice
{
    /**
     * @var Context
     */
    protected $context;

    protected $shippingId;
    protected $mkpCode;
    protected $transactionId;
    protected $transactionType;
    protected $shipmentData;
    protected $shipmentDate;
    protected $currencyId;

    protected $feedOptions;

    protected $debug;

    protected $debugContent;

    protected $status;

    public function __construct(
        $context,
        $shippingId,
        $mkpCode,
        $transactionId,
        $transactionType,
        $shipmentData,
        $shipmentDate,
        $currencyId,
        $debug
    )
    {
        $this->context = $context;
        $this->shippingId = $shippingId;
        $this->mkpCode = Tools::strtolower($mkpCode);
        $this->transactionId = $transactionId;
        $this->transactionType = $transactionType;
        $this->shipmentData = $shipmentData;
        $this->shipmentDate = $shipmentDate;
        $this->currencyId = $currencyId;
        $this->debug = $debug;
    }

    public function getFeedOptions()
    {
        return $this->feedOptions;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @param $download
     * @return string
     */
    public function generateVatInvoice($download)
    {
        require_once _PS_MODULE_DIR_ . 'amazon/classes/amazon.order.class.php';
        require_once _PS_MODULE_DIR_ . 'amazon/classes/amazon.vidr_shipment.class.php';
        require_once _PS_MODULE_DIR_ . 'amazon/classes/amazon.vidr_shipment_order_mapping.php';

        $shipmentId = $this->shippingId;
        $shipmentData = $this->shipmentData;

        // 1. Validate shipment data
        if (!$shipmentData) {
            $this->pdd(sprintf('Shipment data is empty for shipment: %s', $shipmentId), __LINE__);
            $this->setStatus(AmazonVIDRShipment::PROCESS_STATUS_ERROR);
            return '';
        }
        $shipmentData = AmazonTools::unSerialize($shipmentData);
        // Also ignore legacy RETURN / REFUND shipments
        if (false === $shipmentData || !count($shipmentData)) {
            $this->pdd(sprintf('Shipment data is wrong for shipment: %s', $shipmentId), __LINE__);
            $this->setStatus(AmazonVIDRShipment::PROCESS_STATUS_ERROR);
            return '';
        }

        // 2. Validate order
        reset($shipmentData);
        $firstMpOrderId = key($shipmentData);
        $psOrderId = AmazonOrder::checkByMpId($firstMpOrderId);
        if (!$psOrderId) {
            $this->pdd(
                sprintf('Order %s from shipment %s is not imported yet', $firstMpOrderId, $shipmentId),
                __LINE__
            );
            $this->setStatus(AmazonVIDRShipment::PROCESS_STATUS_NO_IMPORTED_ORDER);
            return '';
        }
        $order = new Order($psOrderId);

        // todo: Not use at this moment. Use PS default invoice / credit note number
        // Generate invoice number. By Amazon's law, invoice numbers must be sequence numbers. Use PS order id.
        // Multi-shipment order should have this invoice number format: PS_OID/1, PS_OID/2, PS_OID/3
        // Only invoice need this complex number. For credit note, just use order PS_ID for now
        if (AmazonVIDRShipment::TRANSACTION_TYPE_SHIPMENT == $this->transactionType) {
            $isCreditNote = false;
            $shipmentOrderMappingInfo = AmazonVIDRShipmentOrderMapping::findMappingInfo($shipmentId, $firstMpOrderId);
            if (!$shipmentOrderMappingInfo['found']) {
                $this->pdd(
                    sprintf('Mapping not found for this shipment-order: %s-%s', $shipmentId, $firstMpOrderId),
                    __LINE__
                );
                $this->setStatus(AmazonVIDRShipment::PROCESS_STATUS_ERROR);
                return '';
            }
            $pdfFormattedNumber = $this->generateInvoiceNumber(
                $psOrderId,
                $shipmentOrderMappingInfo['single'],
                $shipmentOrderMappingInfo['index']
            );
        } else {
            $isCreditNote = true;
            $pdfFormattedNumber = $psOrderId;
        }

        // 3. Build invoice params, $shipmentData is un-serialized
        $pdfParams = $this->buildInvoiceParamsAndFeedOption($firstMpOrderId, $shipmentData, $pdfFormattedNumber, $isCreditNote);
        if (!count($pdfParams)) {
            $this->setStatus(AmazonVIDRShipment::PROCESS_STATUS_ERROR);
            return '';
        }

        // Generate PDF
        // This may have many problems, just print debug content so far
        echo $this->getDebugContent();
        $pdf = $this->generatePdf($order, $pdfParams, $download);
        if (!$pdf) {
            $this->pdd('Invoice is empty, an error occupied!', __LINE__);
            $this->setStatus(AmazonVIDRShipment::PROCESS_STATUS_GENERATE_PDF_FAILED);
            return '';
        }

        // 2021-03-04: Experiment: Extra status for Italian non-business order
        if ($this->mkpCode == AmazonVIDRShipment::MARKETPLACE_IT) {
            // Can be got in VCS data itself, store this info the DB before use
            $mpOrder = AmazonOrder::getByOrderId($psOrderId);
            if (!$mpOrder['is_business']) {
                $this->pddE('Non-business Italian order, generate PDF file only');
                $this->setStatus(AmazonVIDRShipment::PROCESS_STATUS_IT_NON_BUSINESS_ORDER);
                // Return PDF as normal
            }
        }

        return $pdf;
    }

    /**
     * @param $psOrderId
     * @param bool $isSingleShipmentOrder
     * @param int $shipmentIndexOfOrder
     * @return string
     */
    protected function generateInvoiceNumber($psOrderId, $isSingleShipmentOrder, $shipmentIndexOfOrder)
    {
        return $isSingleShipmentOrder ? $psOrderId : ($psOrderId . '/' . $shipmentIndexOfOrder);
    }

    protected function buildInvoiceParamsAndFeedOption($firstMpOrderId, $shipmentData, $invoiceFormattedNumber, $isCreditNote)
    {
        // Init final params use in PDF
        $mpOrderIds = array_keys($shipmentData);
        $shipAddress = $billAddress = '';
        $orderDetails = array();
        $productsTotalVatExclAmount = $productsTotalVatInclAmount = 0;
        // Promotions are always negative numbers for normal invoice, opposite for credit note
        $itemsPromoVatExcl = $itemsPromoVatIncl = 0;
        $shippingPromoVatExcl = $shippingPromoVatIncl = 0;
        $wrappingPromoVatExcl = $wrappingPromoVatIncl = 0;
        $shippingVatExclAmount = $shippingVatInclAmount = 0;
        $wrappingVatExclAmount = $wrappingVatInclAmount = 0;
        $citation = '';
        // Better not show VAT rate for total promotion
        $shippingVatRate = $wrappingVatRate = '';

        // Process each order in shipment
        foreach ($shipmentData as $mpOrderId => $shipmentOrder) {
            // Process each item in order
            foreach ($shipmentOrder as $shipmentItem) {
                // 1. Addresses and date
                $shipAddress = $shipAddress ? $shipAddress : $this->formatAddress(
                    $shipmentItem[AmazonVIDRShipment::RP_RECEIVER_NAME],
                    $shipmentItem[AmazonVIDRShipment::RP_SHIP_ADDR_1],
                    $shipmentItem[AmazonVIDRShipment::RP_SHIP_ADDR_2],
                    $shipmentItem[AmazonVIDRShipment::RP_SHIP_ADDR_3],
                    $shipmentItem[AmazonVIDRShipment::RP_SHIP_CITY],
                    $shipmentItem[AmazonVIDRShipment::RP_SHIP_STATE],
                    $shipmentItem[AmazonVIDRShipment::RP_SHIP_POSTCODE],
                    $shipmentItem[AmazonVIDRShipment::RP_SHIP_COUNTRY]
                );
                $billAddress = $billAddress ? $billAddress : $this->formatAddress(
                    $shipmentItem[AmazonVIDRShipment::RP_BILLING_NAME],
                    $shipmentItem[AmazonVIDRShipment::RP_BILL_ADDR_1],
                    $shipmentItem[AmazonVIDRShipment::RP_BILL_ADDR_2],
                    $shipmentItem[AmazonVIDRShipment::RP_BILL_ADDR_3],
                    $shipmentItem[AmazonVIDRShipment::RP_BILL_CITY],
                    $shipmentItem[AmazonVIDRShipment::RP_BILL_STATE],
                    $shipmentItem[AmazonVIDRShipment::RP_BILL_POSTCODE],
                    $shipmentItem[AmazonVIDRShipment::RP_BILL_COUNTRY],
                    $shipmentItem[AmazonVIDRShipment::RP_BUYER_VAT_NUMBER]
                );

                // 2. Product info
                // Item prices in report are multiplied with quantity already
                $itemQuantity = (int)$shipmentItem[AmazonVIDRShipment::RP_ITEM_QTY];
                $totalItemVatExclAmount = (float)$shipmentItem[AmazonVIDRShipment::RP_ITEM_VAT_EXCL_AMOUNT];
                $totalItemVatInclAmount = (float)$shipmentItem[AmazonVIDRShipment::RP_ITEM_VAT_INCL_AMOUNT];
                $itemVatExclAmount = $totalItemVatExclAmount / $itemQuantity;
//                $itemVatInclAmount = $totalItemVatInclAmount / $itemQuantity;     // Not used
                $orderDetails[] = array(
                    'product_reference' => $shipmentItem[AmazonVIDRShipment::RP_SKU],
                    'product_name' => $shipmentItem[AmazonVIDRShipment::RP_PRODUCT_NAME],
                    'order_detail_tax_label' => $this->formatTaxRate($shipmentItem[AmazonVIDRShipment::RP_ITEM_VAT_RATE]),
                    'unit_price_tax_excl_including_ecotax' => $itemVatExclAmount,
                    'ecotax_tax_excl' => 0,
                    'product_quantity' => $itemQuantity,
                    'total_price_tax_excl_including_ecotax' => $totalItemVatExclAmount,
                    'unit_price_tax_excl_before_specific_price' => 0,   // May never use, placeholder only
                    'customizedDatas' => array(),
                    // VAT table of each item
                    'vat' => array(
                        'item_vat' => (float)$shipmentItem[AmazonVIDRShipment::RP_ITEM_VAT_AMOUNT],
                        'item_promo_vat' => (float)$shipmentItem[AmazonVIDRShipment::RP_ITEM_PROMO_VAT_AMOUNT],
                        'gift_vat' => (float)$shipmentItem[AmazonVIDRShipment::RP_WRAPPING_VAT_AMOUNT],
                        'gift_promo_vat' => (float)$shipmentItem[AmazonVIDRShipment::RP_WRAPPING_PROMO_VAT_AMOUNT],
                        'shipping_vat' => (float)$shipmentItem[AmazonVIDRShipment::RP_SHIPPING_VAT_AMOUNT],
                        'shipping_promo_vat' => (float)$shipmentItem[AmazonVIDRShipment::RP_SHIPPING_PROMO_VAT_AMOUNT]
                    )
                );

                // 3. Calculate total for this shipment
                $productsTotalVatExclAmount += $totalItemVatExclAmount;
                $productsTotalVatInclAmount += $totalItemVatInclAmount;

                // 4. Promotion contains item promotion & shipping promotion & wrapping promo. All promo are calculate separate
                $shippingPromoVatExcl += (float)$shipmentItem[AmazonVIDRShipment::RP_SHIPPING_PROMO_VAT_EXCL_AMOUNT];
                $shippingPromoVatIncl += (float)$shipmentItem[AmazonVIDRShipment::RP_SHIPPING_PROMO_VAT_INCL_AMOUNT];
                // Need additional check because this is added later
                $wrappingPromoVatExcl += isset($shipmentItem[AmazonVIDRShipment::RP_WRAPPING_PROMO_VAT_EXCL_AMOUNT]) ?
                    (float)$shipmentItem[AmazonVIDRShipment::RP_WRAPPING_PROMO_VAT_EXCL_AMOUNT] : 0;
                $wrappingPromoVatIncl += isset($shipmentItem[AmazonVIDRShipment::RP_WRAPPING_PROMO_VAT_INCL_AMOUNT]) ?
                    (float)$shipmentItem[AmazonVIDRShipment::RP_WRAPPING_PROMO_VAT_INCL_AMOUNT] : 0;
                $itemsPromoVatExcl += (float)$shipmentItem[AmazonVIDRShipment::RP_ITEM_PROMO_VAT_EXCL_AMOUNT];
                $itemsPromoVatIncl += (float)$shipmentItem[AmazonVIDRShipment::RP_ITEM_PROMO_VAT_INCL_AMOUNT];

                // 5. Shipping cost & wrapping cost
                $shippingVatExclAmount += (float)$shipmentItem[AmazonVIDRShipment::RP_SHIPPING_VAT_EXCL_AMOUNT];
                $shippingVatInclAmount += (float)$shipmentItem[AmazonVIDRShipment::RP_SHIPPING_VAT_INCL_AMOUNT];
                $wrappingVatExclAmount += (float)$shipmentItem[AmazonVIDRShipment::RP_WRAPPING_VAT_EXCL_AMOUNT];
                $wrappingVatInclAmount += (float)$shipmentItem[AmazonVIDRShipment::RP_WRAPPING_VAT_INCL_AMOUNT];
                // Better not show VAT rate for total promotion
                $shippingVatRate = $shippingVatRate ? $shippingVatRate :
                    $this->formatTaxRate($shipmentItem[AmazonVIDRShipment::RP_SHIPPING_VAT_RATE]);
                $wrappingVatRate = $wrappingVatRate ? $wrappingVatRate :
                    $this->formatTaxRate($shipmentItem[AmazonVIDRShipment::RP_WRAPPING_VAT_RATE]);

                // 6. Get citation
                if (!$citation) {
                    $langIsoCode = $this->context->language->iso_code;
                    $citationKey = 'citation-' . $langIsoCode;
                    $citation = isset($shipmentItem[$citationKey]) ? $shipmentItem[$citationKey] : '';
                }
            }
        }

        // Promotions are negative for normal SHIPMENT, positive for RETURN / REFUND
        $promotionVatExcl = $itemsPromoVatExcl + $shippingPromoVatExcl + $wrappingPromoVatExcl;
        $promotionVatIncl = $itemsPromoVatIncl + $shippingPromoVatIncl + $wrappingPromoVatIncl;

        // Calculate total & vat
        $finalVatExclAmount = $productsTotalVatExclAmount + $promotionVatExcl + $shippingVatExclAmount + $wrappingVatExclAmount;
        $finalVatInclAmount = $productsTotalVatInclAmount + $promotionVatIncl + $shippingVatInclAmount + $wrappingVatInclAmount;
        $totalTax = $finalVatInclAmount - $finalVatExclAmount;

        // Build upload options, to submit to Amazon
        $feedOptions = $this->buildFeedOptions($finalVatInclAmount, $totalTax, $firstMpOrderId, $isCreditNote);
        if (!$feedOptions) {
            return array();
        }

        return array(
            'is_credit_note' => $isCreditNote,
            // Override invoice title by shipmentID. Using default PS invoice number at this moment
//            'invoice_title' => $invoiceFormattedNumber,
            'vcs_title_prefix' => $isCreditNote ? Configuration::get('PS_CREDIT_SLIP_PREFIX', (int)$this->context->language->id) : '',
            'delivery_address' => $shipAddress,
            'invoice_address' => $billAddress,
            'now' => date('Y-m-d H:i:s'),
            'vidr_order_refs' => $this->eachComponentInALine($mpOrderIds),
            'vidr_shipment_date' => $this->shipmentDate,
            'id_currency' => $this->currencyId,   // Must set currency context, otherwise it throw error
            'display_product_images' => false,
            'order_details' => $orderDetails,
            'vidr_non_product_costs_bg_class' => 'color_line_even', // Always even, maybe an Smarty cycle bug?
            'total_paid_tax_incl' => $finalVatInclAmount,
            'footer' => array(
                'products_before_discounts_tax_excl' => $productsTotalVatExclAmount,    // 2020-04-07: Should product only
                'product_discounts_tax_excl' => $promotionVatExcl,                      // 2020-04-07: Despite the name, include all promotions
                'products_after_discounts_tax_excl' => $productsTotalVatExclAmount + $itemsPromoVatExcl, // Not found
                'products_before_discounts_tax_incl' => $productsTotalVatInclAmount,                     // Not found
                'product_discounts_tax_incl' => $itemsPromoVatIncl,                                      // Not found
                'products_after_discounts_tax_incl' => $productsTotalVatInclAmount + $itemsPromoVatIncl, // Not found
                // todo: Fix ignore values 0, not use those in PS1.7 maybe
                'product_taxes' => 0,
                'shipping_tax_excl' => $shippingVatExclAmount,
                'shipping_taxes' => 0,
                'shipping_tax_incl' => $shippingVatInclAmount,
                'wrapping_tax_excl' => $wrappingVatExclAmount,
                'wrapping_taxes' => 0,
                'wrapping_tax_incl' => $wrappingVatInclAmount,
                'ecotax_taxes' => 0,
                'total_taxes' => $totalTax,
                'total_paid_tax_excl' => $finalVatExclAmount,
                'total_paid_tax_incl' => $finalVatInclAmount,
            ),
            'citation' => $citation,
            'vidr_non_product_prices' => array(
                'shipping_vat_excl' => $shippingVatExclAmount,
                'shipping_vat_incl' => $shippingVatInclAmount,
                'shipping_vat_rate' => $shippingVatRate,
                'wrapping_vat_excl' => $wrappingVatExclAmount,
                'wrapping_vat_incl' => $wrappingVatInclAmount,
                'wrapping_vat_rate' => $wrappingVatRate,
                'promo_vat_excl' => $promotionVatExcl,
                'promo_vat_incl' => $promotionVatIncl
            )
        );
    }

    protected function buildFeedOptions($finalVatInclAmount, $totalTax, $firstMpOrderId, $isCreditNote)
    {
        $shippingId = $this->shippingId;
        $options = array(
            "metadata:shippingId=$shippingId",
            "metadata:totalAmount=$finalVatInclAmount",
            "metadata:totalVatAmount=$totalTax",
            "metadata:invoiceNumber=$firstMpOrderId",
        );

        // Credit note must have transaction ID
        if ($isCreditNote) {
            $documentType = 'CreditNote';
            $transactionId = $this->transactionId;
            if (!$transactionId) {
                $this->pd(sprintf('Credit note shipment (%s) does not have transaction ID', $shippingId));
                return false;
            }
            $options[] = "metadata:transactionId=$transactionId";
        } else {
            $documentType = 'Invoice';
        }

        $options[] = "metadata:documentType=$documentType";

        $this->feedOptions = implode(';', $options);

        return true;
    }

    /**
     * String contains all address components, each line is a component
     * @param $name
     * @param $line1
     * @param $line2
     * @param $line3
     * @param $city
     * @param $state
     * @param $postcode
     * @param $country
     * @param $line5
     * @return array|string
     */
    protected function formatAddress($name, $line1, $line2, $line3, $city, $state, $postcode, $country, $line5 = '')
    {
        $line4 = array_filter(array(trim($postcode), trim($city), trim($state)));
        $line4 = implode(' ', $line4);

        return $this->eachComponentInALine(array($name, $line1, $line2, $line3, $line4, $country, $line5));
    }

    protected function eachComponentInALine($components)
    {
        $components = array_map('trim', $components);
        $components = array_filter($components);
        return implode('<br />', $components);
    }

    /**
     * Get tax rate percentage
     * @param $rate
     * @return string
     */
    protected function formatTaxRate($rate)
    {
        if ($rate) {
            return (number_format((float)$rate * 100, 2)) . '%';
        }

        return '';
    }

    /**
     * @param Order $order
     * @param $pdfParams
     * @param $download
     * @return mixed
     * @throws PrestaShopException
     */
    protected function generatePdf($order, $pdfParams, $download)
    {
        $this->context->smarty->assign($pdfParams);

        $ps17x = version_compare(_PS_VERSION_, '1.7', '>=');

        // Placeholder order invoice to generate PDF
        $orderInvoiceList = $order->getInvoicesCollection();
        require_once _PS_MODULE_DIR_ . 'amazon/HTMLTemplateAmazonInvoice.php';
        $pdf = new PDF($orderInvoiceList, 'AmazonInvoice', $this->context->smarty);

        // Have some notices in tcpdf on PS1.6 & PS1.5, ignore them
        $renderMode = $download ? true : 'S';
        if (version_compare(_PS_VERSION_, '1.5', '>=') && !$ps17x) {
            $currentErrorReporting = error_reporting();
            error_reporting(E_ALL ^ E_NOTICE);
            $invoice = $pdf->render($renderMode);
            error_reporting($currentErrorReporting);
        } else {
            $invoice = $pdf->render($renderMode);
        }

        return $invoice;
    }

    protected function pd($message, $debugModeOnly = false)
    {
        if (!$debugModeOnly || $this->debug) {
            $this->debugContent .= AmazonTools::pre(array($message), true) . "\n";
        }
    }

    // todo: Migrate to pddE()
    protected function pdd($message, $line, $debugModeOnly = false)
    {
        if (!$debugModeOnly || $this->debug) {
            $this->debugContent .=
                AmazonTools::pre(array(sprintf('vidr_shipment_invoice(#%d): ', $line), $message), true)
                . "\n";
        }
    }
    
    protected function pddE()
    {
        if ($this->debug) {
            $this->debugContent .= $this->_debug(func_get_args());
        }
    }

    protected function _debug($args)
    {
        $debug = '';
        $backTrace = debug_backtrace();
        $caller = array_shift($backTrace);
        $fileSegment = explode(DIRECTORY_SEPARATOR, $caller['file']);
        $file = array_pop($fileSegment);

        foreach ($args as $arg) {
            $debug .= AmazonTools::pre(array(sprintf('%s(#%d): %s', $file, $caller['line'], $arg)), true) . "\n";
        }

        return $debug;
    }

    public function getDebugContent()
    {
        $content = $this->debugContent;
        $this->debugContent = '';
        return $content;
    }
}
