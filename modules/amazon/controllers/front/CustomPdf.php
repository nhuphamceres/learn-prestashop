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

require_once _PS_MODULE_DIR_ . 'amazon/classes/amazon.vidr_shipment_order_mapping.php';
require_once _PS_MODULE_DIR_ . 'amazon/classes/amazon.vidr_shipment_invoice.php';

// todo: Haven't found any usage of this controller, consider remove
class AmazonCustomPdfModuleFrontController extends ModuleFrontController
{
    public function display()
    {
        if (Tools::isSubmit('mp_order_id') && Tools::isSubmit('marketplace')) {
            $mpOrderId = Tools::getValue('mp_order_id');
            $marketplace = Tools::getValue('marketplace');
            $shipment = AmazonVIDRShipmentOrderMapping::getFirstShipmentByOrderAndMarketplace($mpOrderId, $marketplace);
            if (!$shipment) {
                AmazonTools::d('Not found any shipment!');
                return;
            }

            $shipmentId = $shipment['shipping_id'];
            $mkpCode = $shipment['marketplace'];
            $shipmentData = $shipment['shipment_data'];
            $currencyId = $shipment['id_currency'];
            $shipmentDate = $shipment['shipment_date'];
            $transactionType = $shipment['transaction_type'];
            $transactionId = isset($shipment['transaction_id']) ? $shipment['transaction_id'] : null; // Not exist earlier

            $vidrInvoice = new AmazonVIDRShipmentInvoice($this->context, $shipmentId, $mkpCode,
                $transactionId, $transactionType, $shipmentData, $shipmentDate, $currencyId, Amazon::$debug_mode);
            $invoicePdf = $vidrInvoice->generateVatInvoice(true);

            // If has any error, print it. Otherwise, browser will download pdf invoice
            if (!$invoicePdf) {
                echo $vidrInvoice->getDebugContent();
            }
        }
    }
}
