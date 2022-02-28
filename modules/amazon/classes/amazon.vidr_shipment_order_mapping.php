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

require_once dirname(__FILE__) . '/../includes/amazon.db.manager.php';

class AmazonVIDRShipmentOrderMapping
{
    const VAT_NO_PS_PENDING = 0;
    const VAT_NO_PS_PROCESSING = 1;
    const VAT_NO_PS_DONE = 2;
    const VAT_NO_PS_EMPTY = 3;
    const VAT_NO_PS_NO_IMPORTED_ORDER = 5;
    const VAT_NO_PS_BILLING_INVALID = 11;
    const VAT_NO_PS_SHIPPING_INVALID = 12;

    const BILL_PS_PENDING = 0;
    const BILL_PS_PROCESSING = 1;
    const BILL_PS_DONE = 2;
    const BILL_PS_EMPTY = 3;
    const BILL_PS_NO_IMPORTED_ORDER = 5;
    const BILL_PS_INVALID = 11;
    const BILL_PS_INVALID_COUNTRY = 12;
    const BILL_PS_INVALID_STATE = 13;

    public $shipmentId;
    public $mpOrderId;
    public $buyerVatNo;
    public $processVatNoStatus;
    public $billingAddress = array();
    public $processBillingAddress;

    protected $saved = false;

    protected $error;

    public function __construct($shipmentId, $mpOrderId)
    {
        $this->shipmentId = $shipmentId;
        $this->mpOrderId = $mpOrderId;

        $savedEntry = $this->getMapping();
        if ($savedEntry && is_array($savedEntry)) {
            $this->saved = true;
            $this->buyerVatNo = trim($savedEntry['buyer_vat_number']);
            $this->processVatNoStatus = $savedEntry['buyer_vat_number_process_status'];
            if (isset($savedEntry['billing_address']) && $savedEntry['billing_address']) {
                $this->billingAddress = unserialize($savedEntry['billing_address']);
            }
            if (isset($savedEntry['billing_address_process_status'])) {
                $this->processBillingAddress = $savedEntry['billing_address_process_status'];
            }
        }
    }

    public function updateBuyerVatNumberForOrder()
    {
        if (!$this->buyerVatNo) {
            $this->error = sprintf('ShipmentOrder: Missing buyer VAT number: %s - %s', $this->mpOrderId, $this->shipmentId);
            return self::VAT_NO_PS_EMPTY;
        }
        $psOrderId = AmazonOrder::checkByMpId($this->mpOrderId);
        if (!$psOrderId) {
            $this->error = sprintf('ShipmentOrder: Order is not imported: %s - %s', $this->mpOrderId, $this->shipmentId);
            return self::VAT_NO_PS_NO_IMPORTED_ORDER;
        }

        $psOrder = new Order($psOrderId);
        $idAddressBilling = $psOrder->id_address_invoice;

        // Update billing address first
        $billingAddress = new Address($idAddressBilling);
        if (!Validate::isLoadedObject($billingAddress)) {
            $this->error = sprintf('ShipmentOrder: Invalid billing address: %s (%d) - %d', $this->mpOrderId, $psOrderId, $idAddressBilling);
            return self::VAT_NO_PS_BILLING_INVALID;
        }
        $billingAddress->vat_number = $this->buyerVatNo;
        $billingAddress->save();

        // Not update VAT number for shipping address

        return self::VAT_NO_PS_DONE;
    }

    public function updateBuyerBillingAddress()
    {
        require_once dirname(__FILE__) . '/amazon.address.class.php';

        $billingAddress = $this->billingAddress;
        if (!$billingAddress || !is_array($billingAddress)) {
            $this->error = sprintf('ShipmentOrder: Missing billing address: %s - %s', $this->mpOrderId, $this->shipmentId);
            return self::BILL_PS_EMPTY;
        }

        $psOrderId = AmazonOrder::checkByMpId($this->mpOrderId);
        if (!$psOrderId) {
            $this->error = sprintf('ShipmentOrder: Order is not imported: %s - %s', $this->mpOrderId, $this->shipmentId);
            return self::BILL_PS_NO_IMPORTED_ORDER;
        }

        $psOrder = new Order($psOrderId);
        $psIdShipping = $psOrder->id_address_delivery;
        $psIdBilling = $psOrder->id_address_invoice;
        if ($psIdBilling == $psIdShipping) {
            $psBillingAddress = new Address();
            $psBillingAddress->id_customer = $psOrder->id_customer;
            $billingConcat = array_reduce($billingAddress, function ($carry, $item) {
                return $carry . $item;
            }, '');
            $psBillingAddress->alias = md5($billingConcat);
        } else {
            $psBillingAddress = new Address($psIdBilling);
            if (!Validate::isLoadedObject($psBillingAddress)) {
                $this->error = sprintf('ShipmentOrder: Invalid billing address: %s (%d) - %d', $this->mpOrderId, $psOrderId, $psOrder->id_address_invoice);
                return self::BILL_PS_INVALID;
            }
        }

        $addressProps = array('address1', 'address2', 'city', 'postcode', 'phone');
        foreach ($billingAddress as $property => $value) {
            if ($value && in_array($property, $addressProps)) {
                $psBillingAddress->$property = $value;
            }
        }
        // Country
        $billingCountry = $billingAddress['country'];
        if ($billingCountry) {
            $idCountry = Country::getByIso($billingCountry);
            if (!$idCountry) {
                $this->error = sprintf('ShipmentOrder: Invalid billing country: %s (%d) - %s', $this->mpOrderId, $psOrderId, $billingCountry);
                return self::BILL_PS_INVALID_COUNTRY;
            }
            $psBillingAddress->id_country = $idCountry;

            // State
            $billingState = $billingAddress['state'];
            if ($billingState) {
                $idState = AmazonAddress::getIdStateByNameAndCountry($billingState, $idCountry);
                if (!$idState) {
                    // Just a warning
                    $this->error = sprintf('ShipmentOrder: Cannot find billing state: %s (%d) - %s', $this->mpOrderId, $psOrderId, $billingState);
                } else {
                    $psBillingAddress->id_state = $idState;
                }
            }
        }
        // Name
        require_once dirname(__FILE__) . '/amazon.address.class.php';
        $amazonName = AmazonAddress::getAmazonName($billingAddress['name']);
        $psBillingAddress->firstname = $amazonName['firstname'];
        $psBillingAddress->lastname = $amazonName['lastname'];

        try {
            $psBillingAddress->save();
            // Save new billing address if use same address for shipping & billing
            if ($psIdShipping == $psIdBilling) {
                $psOrder->id_address_invoice = $psBillingAddress->id;
                $psOrder->save();
            }
        } catch (PrestaShopException $exception) {
            $this->error = sprintf('ShipmentOrder: Failed to update billing address. Error: %s', $exception->getMessage());
            return self::BILL_PS_INVALID;
        }

        return self::BILL_PS_DONE;
    }

    public function getError()
    {
        $error = $this->error;
        $this->error = '';
        return $error;
    }

    public function isSaved()
    {
        return $this->saved;
    }

    public static function getAllMappingByShipmentId($shipmentId)
    {
        $table = self::getTableName();
        $sql = "SELECT * FROM `$table` WHERE `shipment_id` = '" . pSQL($shipmentId) . "' ORDER BY `id_shipment_order`";
        return Db::getInstance()->executeS($sql);
    }

    /**
     * Use pair of shipmentId & orderId.
     * Check if pair exists? Order spreads many shipments or not? And order's index if in multiple shipments
     * @param $shippingId
     * @param $mpOrderId
     * @return array
     */
    public static function findMappingInfo($shippingId, $mpOrderId)
    {
        $mappings = self::getAllMappingsByOrderWithSortLevel($mpOrderId);
        if (count($mappings)) {
            foreach ($mappings as $mapping) {
                if ($shippingId == $mapping['shipment_id']) {
                    return array('found' => true, 'single' => count($mappings) === 1, 'index' => $mapping['index']);
                }
            }
        }

        return array('found' => false, 'single' => null, 'index' => 0);
    }

    /**
     * @param $mpOrderId
     * @return array
     */
    public static function getAllMappingsByOrderWithSortLevel($mpOrderId)
    {
        $result = array();
        $mappings = self::getAllMappingsByOrderId($mpOrderId);
        if ($mappings && count($mappings)) {
            $index = 0;
            foreach ($mappings as $mapping) {
                $mapping['index'] = ++$index;
                $result[] = $mapping;
            }
        }

        return $result;
    }

    /**
     * @param $mpOrderId
     * @return array|false|mysqli_result|PDOStatement|resource|null
     */
    public static function getAllMappingsByOrderId($mpOrderId)
    {
        $table = self::getTableName();
        $sql = "SELECT * FROM `$table` WHERE `mp_order_id` = '" . pSQL($mpOrderId) . "' ORDER BY `id_shipment_order`";
        return Db::getInstance()->executeS($sql);
    }

    public static function getMappingByOrderId($mpOrderId)
    {
        $table = self::getTableName();
        $sql = "SELECT * FROM `$table` WHERE `mp_order_id` = '" . pSQL($mpOrderId) . "' ORDER BY `id_shipment_order` DESC";
        return Db::getInstance()->getRow($sql);
    }

    public static function getFirstShipmentByOrderAndMarketplace($mpOrderId, $marketplace)
    {
        require_once dirname(__FILE__) . '/amazon.vidr_shipment.class.php';
        $tblShipment = _DB_PREFIX_ . AmazonDBManager::TABLE_MARKETPLACE_VIDR_SHIPMENT;
        $tblMapping = self::getTableName();

        $sql = "SELECT * FROM `$tblMapping` m
                JOIN `$tblShipment` s ON (m.`shipment_id` = s.`shipping_id`)
                WHERE m.`mp_order_id` = '" . pSQL($mpOrderId) . "' AND s.`marketplace` = '" . pSQL($marketplace) . "'
                AND s.`transaction_type` = '" . pSQL(AmazonVIDRShipment::TRANSACTION_TYPE_SHIPMENT) . "'";

        return Db::getInstance()->getRow($sql);
    }

    /**
     * @param $shippingId
     * @param array $ordersData ([order_id] => array('order_id', 'buyer_vat_number'))
     */
    public static function replaceBulkMappingsByShipment($shippingId, $ordersData)
    {
        self::deleteByShipment($shippingId);

        // Update db structure if need
        AmazonDBManager::upgradeStructureTableVIDRShipmentOrderMapping();

        foreach ($ordersData as $orderData) {
            self::addMapping($shippingId, $orderData);
        }
    }

    private static function deleteByShipment($shippingId)
    {
        $table = self::getTableName();
        $sql = "DELETE FROM `$table` WHERE `shipment_id` = '" . pSQL($shippingId) . "'";
        return Db::getInstance()->execute($sql);
    }

    /**
     * @param $shippingId
     * @param $mpOrderData
     * @return bool
     */
    private static function addMapping($shippingId, $mpOrderData)
    {
        $table = self::getTableName();
        $billingAddress = serialize($mpOrderData['billing_address']);

        $sql = "INSERT INTO `$table`(`shipment_id`, `mp_order_id`, `buyer_vat_number`, `buyer_vat_number_process_status`, `billing_address`, `billing_address_process_status`)
                VALUES('" . pSQL($shippingId) . "', '" . pSQL($mpOrderData['order_id']) . "', '" . pSQL($mpOrderData['buyer_vat_number']) . "', 0, '" . pSQL($billingAddress) . "', 0)";
        return Db::getInstance()->execute($sql);
    }

    public static function getUnfinishedUpdateAddressMapping($marketplace, $limit)
    {
        $tblShipment = _DB_PREFIX_ . AmazonDBManager::TABLE_MARKETPLACE_VIDR_SHIPMENT;
        $tblMapping = self::getTableName();

        $vatStatusPending = self::VAT_NO_PS_PENDING;
        $vatStatusProcessing = self::VAT_NO_PS_PROCESSING;
        $vatStatusNoImportOrder = self::VAT_NO_PS_NO_IMPORTED_ORDER;
        $vatUnfinishedStatuses = implode(',', array($vatStatusPending, $vatStatusProcessing, $vatStatusNoImportOrder));

        $billingStatusPending = self::BILL_PS_PENDING;
        $billingStatusProcessing = self::BILL_PS_PROCESSING;
        $billingStatusNoImportOrder = self::BILL_PS_NO_IMPORTED_ORDER;
        $billingUnfinishedStatuses = implode(',', array($billingStatusPending, $billingStatusProcessing, $billingStatusNoImportOrder));

        $sql =
            "SELECT m.* FROM `$tblMapping` AS m
            JOIN `$tblShipment` AS s ON (m.`shipment_id` = s.`shipping_id`)
            WHERE s.`marketplace` = '" . pSQL($marketplace) . "' AND
            (
                (
                    m.`buyer_vat_number_process_status` IN (" . pSQL($vatUnfinishedStatuses) . ")
                    AND m.`buyer_vat_number` IS NOT NULL AND m.`buyer_vat_number` <> ''
                )
                OR
                (
                    m.`billing_address_process_status` IN (" . pSQL($billingUnfinishedStatuses) . ")
                    AND m.`billing_address` IS NOT NULL AND m.`billing_address` <> ''
                )
            )
            LIMIT " . (int)$limit;

        // pdt($sql);
        return Db::getInstance()->executeS($sql);
    }

    public function updateBuyerVatNoProcessStatus($status)
    {
        $tbl = self::getTableName();
        $sql = "UPDATE `$tbl` SET `buyer_vat_number_process_status` = " . (int)$status . "
                WHERE `shipment_id` = '" . pSQL($this->shipmentId) . "' AND `mp_order_id` = '" . pSQL($this->mpOrderId) . "'";

        return Db::getInstance()->execute($sql);
    }

    public function updateBillingAddressProcessStatus($status)
    {
        $tbl = self::getTableName();
        $sql = "UPDATE `$tbl` SET `billing_address_process_status` = " . (int)$status . "
                WHERE `shipment_id` = '" . pSQL($this->shipmentId) . "' AND `mp_order_id` = '" . pSQL($this->mpOrderId) . "'";

        return Db::getInstance()->execute($sql);
    }

    public static function getOrdersByShipment($shipmentId)
    {
        $tbl = self::getTableName();
        $sql = "SELECT `mp_order_id` FROM `$tbl` WHERE `shipment_id` = '" . pSQL($shipmentId) . "'";
        return Db::getInstance()->executeS($sql);
    }

    protected static function getTableName()
    {
        return _DB_PREFIX_ . AmazonDBManager::TABLE_MARKETPLACE_VIDR_SHIPMENT_ORDER_MAPPING;
    }

    private function getMapping()
    {
        $tbl = self::getTableName();
        $sql = "SELECT * FROM `$tbl` WHERE `shipment_id` = '" . pSQL($this->shipmentId) . "' AND `mp_order_id` = '" . pSQL($this->mpOrderId) . "'";

        return Db::getInstance()->getRow($sql);
    }
}
