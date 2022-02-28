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

class AmazonConstant
{
    const MKP_FR = 'A13V1IB3VIYZZH';
    const MKP_ES = 'A1RKKUPIHCS9HS';
    const MKP_DE = 'A1PA6795UKMFR9';
    const MKP_IT = 'APJ6JRA9NG5V4';
    const MKP_UK = 'A1F83G8C2ARO7P';
    const MKP_US = 'ATVPDKIKX0DER';
    const MKP_EG = 'ARBP9OOSHTCHU';
    const PLATFORM_EGYPT = 'eg';

    const TABLE_MARKETPLACE_PRODUCT_ACTION = 'marketplace_product_action';
    const TABLE_MARKETPLACE_PRODUCT_OPTION = 'marketplace_product_option';
    const TABLE_AMZ_STATES = 'amazon_states';

    const CONFIG_LANG_TO_REGION = 'REGION';
    const CONFIG_TAXES = 'TAXES';
    const CONFIG_OI_TAXES_COMPLY_EU_VAT_RULES = 'OI_TAXES_COMPLY_EU_VAT_RULES';
    const CONFIG_OI_TAXES_ON_BUSINESS_ORDERS = 'TAXES_ON_BUSINESS_ORDERS';
    const CONFIG_OI_TAXES_FORCE_RECALCULATION = 'OI_FORCE_VAT_RECALCULATION';

    // todo: Migrate to dedicated table
    const CONFIG_LEGACY_PRE_ORDER = 'AMAZON_PREORDER';
    const CONFIG_PRE_ORDER = 'PREORDER';

    /**
     * todo: Move from Amazon
     * const ORDER_STATE_STANDARD = 'STD';
     * const ORDER_STATE_BUSINESS = 'BUSINESS';
     * const ORDER_STATE_PREORDER = 'PRE';
     * const ORDER_STATE_PRIMEORDER = 'PRIME';
     */
    const ORDER_INCOMING_TYPE_PREORDER = 'PRE';
    const ORDER_INCOMING_TYPE_FBA = 'FBA';
    const ORDER_INCOMING_TYPE_PRIME = 'PRIME';
    const ORDER_INCOMING_TYPE_BUSINESS = 'BUSINESS';
    const ORDER_INCOMING_TYPE_STANDARD = 'STD';
    const ORDER_SENT_TYPE = 'sent_state';
    const ORDER_CANCELED_TYPE = 'canceled_state';
    /**
     * [
     *   [
     *      [attr => [FBA, Prime, Business]],
     *      [state] => int
     *   ],
     *   ...
     * ]
     */
    const CONFIG_ORDER_STATES_INCOMING_OF_ORDER_ATTRS_COMBINATION = 'OS_INCOMING_OF_ORDER_ATTRS_COMBINATION';

    const CONFIG_CARRIER_MAPPING_OUTGOING = 'CARRIER_OUTGOING';

    const LENGTH_TITLE = 500;
    const LENGTH_BULLET_POINT = 500;
    const LENGTH_DESCRIPTION = 2000;

    const CONFIG_VCS_ENABLED = 'VIDR_ENABLED';
    const CONFIG_VCS_SEND_INVOICE = 'VIDR_SEND_INVOICE';  // Some customers just want to access VCS db, but not upload any invoice
    const CONFIG_VCS_UPDATE_CUSTOMER_VAT_NUMBER = 'VIDR_UPDATE_CUSTOMER_VAT_NUMBER';  // Use data of VCS to update customer VAT number (in address of imported order)
    const CONFIG_VCS_UPDATE_BILLING_ADDRESS = 'VIDR_UPDATE_BILLING_ADDRESS';   // Update entire billing address by VCS data

    const CONFIG_CRON_PARAMS = 'CRON_PARAMS';

    // orders importing order status params
    const OI_ORDER_STATUS_ALL = 'All';
    const OI_ORDER_STATUS_PENDING = 'Pending';
    const OI_ORDER_STATUS_UNSHIPPED = 'Unshipped';
    const OI_ORDER_STATUS_PARTIALLY_SHIPPED = 'PartiallyShipped';
    const OI_ORDER_STATUS_SHIPPED = 'Shipped';

    // Use ps_configuration, json type to overcome unicode and break lines
    const IMPORT_ORDERS_CRON_FAILED_LIST = 'AMAZON_OI_CRON_FAILED_LIST';

    const CONFIG_PRODUCT_UPDATE_CONDITION_IGNORE = 'PU_CONDITION_IGNORE';

    // FBA
    const FBA_MC_CURRENCY = 'FBA_MULTICHANNEL_CURRENCY';   // Currency use while sending external order to Amazon, use from selectPlatforms() if leave empty

    const CONFIG_GET_BY_DIRECT_SQL = 'AMAZON_CONFIG_GET_BY_DIRECT_SQL';

    // Filters
    const FILTER_STATUS = 'STATUS_FILTER';

    const PE_DISCOUNT = 'SPECIALS';
}
