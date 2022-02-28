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

require_once dirname(__FILE__) . '/../includes/amazon.db.manager.php';

class AmazonVIDRShipment
{
    const MARKETPLACE_IT = 'it';

    const TRANSACTION_TYPE_SHIPMENT = 'SHIPMENT';
    const TRANSACTION_TYPE_REFUND = 'REFUND';
    const TRANSACTION_TYPE_RETURN = 'RETURN';

    const PROCESS_STATUS_PENDING = 0;
    const PROCESS_STATUS_PROCESSING = 1;
    const PROCESS_STATUS_DONE = 2;
    const PROCESS_STATUS_ERROR = 3;
    const PROCESS_STATUS_THROTTLED = 4;
    const PROCESS_STATUS_NO_IMPORTED_ORDER = 5;
    const PROCESS_STATUS_IT_NON_BUSINESS_ORDER = 51;   // Italy has strict rule for non-business orders
    const PROCESS_STATUS_GENERATE_PDF_FAILED = 6;

    /***************************************** Report fields **********************************************************/
    const RP_SHIPPING_ID = 'shipping-id';
    const RP_ORDER_ID = 'order-id';
    const RP_TRANSACTION_TYPE = 'transaction-type';
    const RP_TRANSACTION_ID = 'transaction-id';
    const RP_SHIPMENT_DATE = 'shipment-date';
    const RP_ORDER_DATE = 'order-date';
    const RP_CURRENCY = 'currency';
    const RP_MARKETPLACE_ID = 'marketplace-id';
    const RP_INVOICE_STATUS = 'invoice-status';
    const RP_INVOICE_STATUS_DESCRIPTION = 'invoice-status-description';
    const RP_IS_AMAZON_INVOICED = 'is-amazon-invoiced';
    const RP_SELLER_VAT_NUMBER = 'seller-vat-number';
    const RP_BUYER_VAT_NUMBER = 'buyer-vat-number';
    const RP_IS_BUSINESS_ORDER = 'is-business-order';
    // Product
    const RP_SKU = 'sku';
    const RP_ASIN = 'asin';
    const RP_PRODUCT_NAME = 'product-name';
    const RP_ITEM_QTY = 'quantity-purchased';
    const RP_ITEM_VAT_INCL_AMOUNT = 'item-vat-incl-amount';
    const RP_ITEM_VAT_EXCL_AMOUNT = 'item-vat-excl-amount';
    const RP_ITEM_VAT_AMOUNT = 'item-vat-amount';
    const RP_ITEM_VAT_RATE = 'item-vat-rate';
    // Promotion
    const RP_ITEM_PROMO_VAT_EXCL_AMOUNT = 'item-promo-vat-excl-amount';
    const RP_ITEM_PROMO_VAT_INCL_AMOUNT = 'item-promo-vat-incl-amount';
    const RP_ITEM_PROMO_ID = 'item-promotion-id';
    const RP_SHIPPING_PROMO_VAT_EXCL_AMOUNT = 'shipping-promo-vat-excl-amount';
    const RP_SHIPPING_PROMO_VAT_INCL_AMOUNT = 'shipping-promo-vat-incl-amount';
    const RP_SHIPPING_PROMO_ID = 'ship-promotion-id';
    const RP_WRAPPING_PROMO_VAT_EXCL_AMOUNT = 'gift-promo-vat-excl-amount';
    const RP_WRAPPING_PROMO_VAT_INCL_AMOUNT = 'gift-promo-vat-incl-amount';
    const RP_WRAPPING_PROMO_ID = 'gift-promotion-id';
    // Shipping & wrapping costs
    const RP_SHIPPING_VAT_EXCL_AMOUNT = 'shipping-vat-excl-amount';
    const RP_SHIPPING_VAT_INCL_AMOUNT = 'shipping-vat-incl-amount';
    const RP_WRAPPING_VAT_EXCL_AMOUNT = 'gift-wrap-vat-excl-amount';
    const RP_WRAPPING_VAT_INCL_AMOUNT = 'gift-wrap-vat-incl-amount';
    // Vat amount
    const RP_ITEM_PROMO_VAT_AMOUNT = 'item-promo-vat-amount';
    const RP_SHIPPING_VAT_AMOUNT = 'shipping-vat-amount';
    const RP_SHIPPING_PROMO_VAT_AMOUNT = 'shipping-promo-vat-amount';
    const RP_WRAPPING_VAT_AMOUNT = 'gift-wrap-vat-amount';
    const RP_WRAPPING_PROMO_VAT_AMOUNT = 'gift-promo-vat-amount';
    // Vat rate
    const RP_ITEM_PROMO_VAT_RATE = 'item-promo-vat-rate';
    const RP_SHIPPING_VAT_RATE = 'shipping-vat-rate';
    const RP_SHIPPING_PROMO_VAT_RATE = 'shipping-promo-vat-rate';
    const RP_WRAPPING_VAT_RATE = 'gift-wrap-vat-rate';
    const RP_WRAPPING_PROMO_VAT_RATE = 'gift-promo-vat-rate';
    // Addresses
    const RP_BUYER_NAME = 'buyer-name';
    const RP_RECEIVER_NAME = 'recipient-name';
    const RP_BILLING_NAME = 'billing-name';
    const RP_SHIP_ADDR_1 = 'ship-address-1';
    const RP_SHIP_ADDR_2 = 'ship-address-2';
    const RP_SHIP_ADDR_3 = 'ship-address-3';
    const RP_SHIP_CITY = 'ship-city';
    const RP_SHIP_STATE = 'ship-state';
    const RP_SHIP_POSTCODE = 'ship-postal-code';
    const RP_SHIP_COUNTRY = 'ship-country';
    const RP_BILL_ADDR_1 = 'bill-address-1';
    const RP_BILL_ADDR_2 = 'bill-address-2';
    const RP_BILL_ADDR_3 = 'bill-address-3';
    const RP_BILL_CITY = 'bill-city';
    const RP_BILL_STATE = 'bill-state';
    const RP_BILL_POSTCODE = 'bill-postal-code';
    const RP_BILL_COUNTRY = 'bill-country';
    const RP_BILL_PHONE = 'billing-phone-number';
    // Citation
    const RP_CITATION_ES = 'citation-es';
    const RP_CITATION_IT = 'citation-it';
    const RP_CITATION_FR = 'citation-fr';
    const RP_CITATION_DE = 'citation-de';
    const RP_CITATION_EN = 'citation-en';

    protected static $debugContent;

    public static function getDebugContent()
    {
        return self::$debugContent;
    }

    public static function addShipment(
        $shippingId,
        $marketPlace,
        $transactionId,
        $transactionType,
        $invoiceStatus,
        $invoiceStatusDescription,
        $isAmazonInvoiced,
        $shipmentDate,
        $sellerVatNumber,
        $idCurrency,
        $orderCount,
        $itemCount,
        $shipmentData,
        $processStatus = self::PROCESS_STATUS_PENDING
    ) {
        self::resetDebugContent();
        $tbl = self::getTableName();
        $marketPlace = Tools::strtolower($marketPlace);    // Save as lowercase form

        // todo: Remove in future when VIDR works fine
        AmazonDBManager::upgradeStructureTableVIDRShipment();
        AmazonDBManager::upgradeStructureTableVIDRShipment2();  // 2021-06-01

        // Clear old records if already saved
        Db::getInstance()->execute(
            "DELETE FROM `$tbl` 
            WHERE `shipping_id` = '" . pSQL($shippingId) . "' 
                AND `marketplace` = '" . pSQL($marketPlace) . "'
                AND `transaction_id` = '" . pSQL($transactionId) . "' 
                AND `transaction_type` = '" . pSQL($transactionType) . "'"
        );

        $sql = "INSERT INTO `$tbl`(
                    `shipping_id`, `marketplace`, `transaction_id`, `transaction_type`,
                    `invoice_status_text`, `invoice_status_description`, `is_amazon_invoiced`, `shipment_date`,
                    `seller_vat_number`, `id_currency`, `shipment_data`,
                    `order`, `item`,
                    `process_status`, `date_add`
                ) VALUES (
                    '" . pSQL($shippingId) . "', '" . pSQL($marketPlace) . "',
                    '" . pSQL($transactionId) . "', '" . pSQL($transactionType) . "',
                    '" . pSQL($invoiceStatus) . "', '" . pSQL($invoiceStatusDescription) . "',
                    " . (int)$isAmazonInvoiced . ", '" . pSQL($shipmentDate) . "',
                    '" . pSQL($sellerVatNumber) . "', " . (int)$idCurrency . ", '" . pSQL($shipmentData) . "',
                    " . (int)$orderCount . ", " . (int)$itemCount . ",
                    " . (int)$processStatus . ", '" . pSQL(date('Y-m-d H:i:s')) . "'
                )";

        try {
            if (!Db::getInstance()->execute($sql)) {
                self::pdd(sprintf('Unable to insert VIDR shipment. SQL failed - %s', $sql), __LINE__);
                return false;
            }
        } catch (PrestaShopDatabaseException $exception) {
            self::pdd(sprintf('Unable to insert VIDR shipment: %s', $exception->getMessage()), __LINE__);
            self::pdd(sprintf('SQL failed - %s', $sql), __LINE__);
            return false;
        }

        self::pdd(sprintf('SQL - %s', $sql), __LINE__);

        return true;
    }

    public static function getShipmentByInternalId($shipmentInternalId)
    {
        $tbl = self::getTableName();
        $sql = "SELECT * FROM `$tbl` WHERE `id` = '" . (int)$shipmentInternalId . "'";
        return Db::getInstance()->getRow($sql);
    }

    public static function getShipmentByShipmentId($shipmentId)
    {
        $tbl = self::getTableName();
        $sql = "SELECT * FROM `$tbl` WHERE `shipping_id` = '" . pSQL($shipmentId) . "' ORDER BY `id` DESC";
        return Db::getInstance()->getRow($sql);
    }

    /**
     * Get ordered list of unfinished shipments
     * @param string $marketplace
     * @param int $limit
     * @return array|false|mysqli_result|PDOStatement|resource|null
     * @throws PrestaShopDatabaseException
     */
    public static function getUnfinishedShipments($marketplace, $limit)
    {
        $table = self::getTableName();
        $marketplace = Tools::strtolower($marketplace);
        $statusPending = AmazonVIDRShipment::PROCESS_STATUS_PENDING;
        $statusProcessing = AmazonVIDRShipment::PROCESS_STATUS_PROCESSING;
        $statusThrottled = AmazonVIDRShipment::PROCESS_STATUS_THROTTLED;
        $statusNoImportOrder = AmazonVIDRShipment::PROCESS_STATUS_NO_IMPORTED_ORDER;
        $unfinishedStatuses = implode(
            ',', 
            array(
                $statusPending,
                $statusProcessing,
                $statusThrottled,
                $statusNoImportOrder
            )
        );

        // Get shipments in pending status, order by: pending > processing > missingOrder
        $sql = "SELECT `id`, `shipping_id`, `process_status` 
            FROM `$table` WHERE `marketplace` = '" . pSQL($marketplace) . "' 
                AND `process_status` IN (" . pSQL($unfinishedStatuses) . ")
            ORDER BY `process_status`, `date_add` LIMIT " . (int)$limit;

        return Db::getInstance()->executeS($sql);
    }

    public static function pullShipment($marketplace, $processStatus)
    {
        $table = self::getTableName();
        $marketplace = Tools::strtolower($marketplace);
        $sql = "SELECT * FROM `$table` WHERE `marketplace` = '" . pSQL($marketplace) . "' 
                AND `process_status` = " . (int)$processStatus . " ORDER BY `date_add`";
        return Db::getInstance()->getRow($sql);
    }

    public static function updateShipmentProcessStatus($shipmentInternalId, $processStatus)
    {
        $tbl = self::getTableName();
        $sql = "UPDATE `$tbl` SET `process_status` = " . (int)$processStatus . " WHERE `id` = '" . (int)$shipmentInternalId . "'";
        return Db::getInstance()->execute($sql);
    }

    public static function updateShipmentProcessedResult($shipmentInternalId, $processStatus, $processedData)
    {
        self::resetDebugContent();
        $tbl = self::getTableName();
        $sql = "UPDATE `$tbl` SET `process_status` = " . (int)$processStatus . ", 
                `processed_data` = '" . pSQL($processedData, true) . "'
                 WHERE `id` = '" . (int)($shipmentInternalId) . "'";

        try {
            return Db::getInstance()->execute($sql);
        } catch (PrestaShopDatabaseException $exception) {
            self::pdd(sprintf('Unable to update VIDR shipment process result: %s', $exception->getMessage()), __LINE__);
            self::pdd(sprintf('SQL failed - %s', $sql), __LINE__);
            return false;
        }
    }

    protected static function resetDebugContent()
    {
        self::$debugContent = '';
    }

    protected static function pd($message, $debugModeOnly = false)
    {
        if (!$debugModeOnly || Amazon::$debug_mode) {
            self::$debugContent .= AmazonTools::pre(array($message), true) . "\n";
        }
    }

    protected static function pdd($message, $line, $debugModeOnly = false)
    {
        if (!$debugModeOnly || Amazon::$debug_mode) {
            self::$debugContent .= AmazonTools::pre(array(
                    sprintf('%s(#%d): ', basename(__FILE__), $line),
                    $message
                ), true)
                . "\n";
        }
    }

    public static function getTableName()
    {
        return _DB_PREFIX_ . AmazonDBManager::TABLE_MARKETPLACE_VIDR_SHIPMENT;
    }
}
