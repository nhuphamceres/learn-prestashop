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

require_once(dirname(__FILE__) . '/../../includes/amazon.admin_configure.php');
require_once(dirname(__FILE__) . '/../../classes/amazon.vidr_shipment_order_mapping.php');
require_once(dirname(__FILE__) . '/../../classes/amazon.order.class.php');

class AmazonFunctionVIDRUpdateAddress extends AmazonFunctionWrapper
{
    const MAX_MAPPING_TO_PROCESS = 300;

    protected $function = 'upd_buyer_vat_number';

    protected $mpOrderId;

    private $vcsUpdateCustomerVatNumber;
    private $vcsUpdateBillingAddress;

    public function __construct($cronToken, $amazonLang, $mpOrderId)
    {
        parent::__construct($cronToken, $amazonLang);
        $this->mpOrderId = $mpOrderId;
    }

    public function updateCustomerAddress()
    {
        // Pre-check
        if (!$this->preCheckRequest()
            || !$this->preCheckModuleSetting()
            || !$this->resolveNecessaryParameters()) {
            return;
        }

        if ($this->mpOrderId) {
            $this->processAnOrder($this->mpOrderId);
            $this->pd("----- Done updating address for order {$this->mpOrderId}. -----");
        } else {
            $this->processAllUnfinishedOrders();
            $this->pd("----- Done updating address this turn. -----");
        }
    }

    protected function preCheckModuleSetting()
    {
        $settingMaster = (bool)AmazonConfiguration::get(AmazonConstant::CONFIG_VCS_ENABLED);
        $this->vcsUpdateCustomerVatNumber = (bool)AmazonConfiguration::get(AmazonConstant::CONFIG_VCS_UPDATE_CUSTOMER_VAT_NUMBER);
        $this->vcsUpdateBillingAddress = (bool)AmazonConfiguration::get(AmazonConstant::CONFIG_VCS_UPDATE_BILLING_ADDRESS);
        if (!$settingMaster || (!$this->vcsUpdateCustomerVatNumber && !$this->vcsUpdateBillingAddress)) {
            $this->pd('VCS is not enable');
            return false;
        }

        return true;
    }

    protected function processAnOrder($mpOrderId)
    {
        $mapping = AmazonVIDRShipmentOrderMapping::getMappingByOrderId($mpOrderId);
        if ($mapping && is_array($mapping)) {
            $shipmentOrder = new AmazonVIDRShipmentOrderMapping($mapping['shipment_id'], $mapping['mp_order_id']);
            // Update billing first, in case of create new billing
            $this->_updateBillingAddress($shipmentOrder);
            $this->_updateCustomerVatNumber($shipmentOrder);
        } else {
            $this->pd(sprintf('Order not exist: %s', $this->mpOrderId));
        }
    }

    protected function processAllUnfinishedOrders()
    {
        $unfinishedMappings = AmazonVIDRShipmentOrderMapping::getUnfinishedUpdateAddressMapping(
            $this->amazonLang,
            self::MAX_MAPPING_TO_PROCESS
        );
        if (!$unfinishedMappings || !is_array($unfinishedMappings) || !count($unfinishedMappings)) {
            $this->pd('No pending order mapping to update address!');
            return;
        }

        foreach ($unfinishedMappings as $unfinishedMapping) {
            $shipmentId = $unfinishedMapping['shipment_id'];
            $mpOrderId = $unfinishedMapping['mp_order_id'];

            $shipmentOrder = new AmazonVIDRShipmentOrderMapping($shipmentId, $mpOrderId);
            if (!$shipmentOrder->isSaved()) {
                // Check if the mapping exist while loop
                $this->pd(sprintf('Order mapping disappears while looping: %s - %s!', $shipmentId, $mpOrderId));
            } else {
                $this->_updateBillingAddress($shipmentOrder);
                $this->_updateCustomerVatNumber($shipmentOrder);
            }
        }
    }

    /**
     * @param AmazonVIDRShipmentOrderMapping $shipmentOrder
     */
    private function _updateCustomerVatNumber($shipmentOrder)
    {
        $statusProcessing = AmazonVIDRShipmentOrderMapping::VAT_NO_PS_PROCESSING;
        if ($shipmentOrder->processVatNoStatus != $statusProcessing) {
            $shipmentOrder->updateBuyerVatNoProcessStatus($statusProcessing);
        }

        if ($this->vcsUpdateCustomerVatNumber) {
            $processStatus = $shipmentOrder->updateBuyerVatNumberForOrder();
            if ($processStatus == AmazonVIDRShipmentOrderMapping::VAT_NO_PS_DONE) {
                $this->pd(sprintf('Updated buyer Vat number for order: %s - %s', $shipmentOrder->mpOrderId, $shipmentOrder->buyerVatNo));
            } else {
                $this->pd(sprintf('Failed to update buyer VAT number for order: %s!. Warning msg: %s!', $shipmentOrder->mpOrderId, $shipmentOrder->getError()));
            }
            $shipmentOrder->updateBuyerVatNoProcessStatus($processStatus);
        }
    }

    /**
     * @param AmazonVIDRShipmentOrderMapping $shipmentOrder
     */
    private function _updateBillingAddress($shipmentOrder)
    {
        $statusProcessing = AmazonVIDRShipmentOrderMapping::BILL_PS_PROCESSING;
        if ($shipmentOrder->processBillingAddress != $statusProcessing) {
            $shipmentOrder->updateBillingAddressProcessStatus($statusProcessing);
        }

        if ($this->vcsUpdateBillingAddress) {
            $processStatus = $shipmentOrder->updateBuyerBillingAddress();
            if ($processStatus == AmazonVIDRShipmentOrderMapping::BILL_PS_DONE) {
                $this->pd(sprintf('Updated buyer billing address for order: %s - %s', $shipmentOrder->mpOrderId, print_r($shipmentOrder->billingAddress, true)));
            } else {
                $this->pd(sprintf('Failed to update buyer billing address for order: %s!. Warning msg: %s!', $shipmentOrder->mpOrderId, $shipmentOrder->getError()));
            }
            $shipmentOrder->updateBillingAddressProcessStatus($processStatus);
        }
    }
}
