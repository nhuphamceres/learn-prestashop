<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Common-Services Co., Ltd.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 *
 * @author    Alexandre DEBUSSCHERE <alexandre@common-services.com>
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 *  Support by mail  :  support.mirakl@common-services.com
 */

require_once dirname(__FILE__) . '/env.php';
require_once dirname(__FILE__) . '/../classes/mirakl.marketplace.php';
require_once dirname(__FILE__) . '/../classes/order.class.php';

if ((bool)Tools::getValue('debug') == true) {
    @ini_set('display_errors', 'on');
    @error_reporting(E_ALL | E_STRICT);
}

Context::getContext()->shop = new Shop((int)Tools::getValue('id_shop'));
Shop::setContext(
    Shop::CONTEXT_SHOP,
    (int)Tools::getValue('id_shop', Context::getContext()->shop->id)
);

MiraklMarketplace::init();
$marketplaces = array_map('pSQL', array_merge(
    array('mirakl'),
    array_keys(MiraklMarketplace::getMarketplaces())
));

$mirakl = new Mirakl();

$lookBackDays = (int)Tools::getValue('look_back_days', 7);
$fromDate = date('Y-m-d H:i:s', strtotime('-' . $lookBackDays . ' days'));
$orders = MiraklOrder::getMiraklOrdersByDate($fromDate);
if (!count($orders)) {
    dbt(sprintf('No available order since %s', $fromDate));
    return;
}

foreach ($orders as $miraklOrder) {
    try {
        $idOrder = $miraklOrder['id_order'];
        $mpIdOrder = $miraklOrder['mp_order_id'];
        // true if the order was NOT synced before
        $isSyncingShipping = $miraklOrder['mp_status'] != MiraklOrder::CHECKED;

        if (!$miraklOrder['ps_id_order']) {
            printf('Order not exist in PS: %d-%s.<br>', $idOrder, $mpIdOrder);
            continue;
        }

        $order = new Order($idOrder);
        if (!Validate::isLoadedObject($order)) {
            printf('Order %d : can not be loaded.<br>', $idOrder);
            continue;
        }

        $params = array(
            'id_order' => $order->id,
            'newOrderStatus' => $order->getCurrentOrderState(),
            'is_syncing_shipping' => $isSyncingShipping,
            'is_cron' => true
        );

        $_GET['selected-mkp'] = Tools::strtolower($order->payment); // Or 1 cron by marketplace
        Mirakl::$marketplace_params = MiraklMarketplace::init();

        if ($mirakl->hookActionOrderStatusUpdate($params)) {
            // todo: Set status after done to bypass next time
            printf('Order %d : status updated.<br>', $order->id);
        } else {
            printf('Order %d : status not updated.<br>', $order->id);
        }
    } catch (Exception $exception) {
        printf('%s<br>', $exception->getMessage());
    }
}

function dbt()
{
    $backTraces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);    // Get 3 back trace
    $callerStack = array();
    foreach ($backTraces as $backTrace) {
        $fileSegment = explode(DIRECTORY_SEPARATOR, $backTrace['file']);
        $file = array_pop($fileSegment);
        $callerStack[] = sprintf('%s(#%d)', $file, $backTrace['line']);
    }

    $callerStackStr = implode(' - ', $callerStack) . ': ';
    $args = func_get_args();
    array_unshift($args, $callerStackStr);

    echo MiraklTools::pre($args, true) . Mirakl::LF;
}
