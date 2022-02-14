<?php
/**
 * NOTICE OF LICENSE
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Common-Services Co., Ltd.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 * ...........................................................................
 *
 * @author    Alexandre, Tran Pham
 * @copyright Copyright (c) 2011-2021 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.priceminister@common-services.com
 */

if (isset($_SERVER['DropBox']) && $_SERVER['DropBox']) {
    require_once(readlink(dirname($_SERVER['SCRIPT_FILENAME']).'/../../../config/config.inc.php'));
    if (version_compare(_PS_VERSION_, '1.4', '<')) {
        require_once(readlink(dirname($_SERVER['SCRIPT_FILENAME']).'/../../../init.php'));
    }
} else {
    @require_once(dirname(__FILE__).'/../../../config/config.inc.php');
    if (version_compare(_PS_VERSION_, '1.4', '<')) {
        @require_once(dirname(__FILE__).'/../../../init.php');
    }
}

require_once(dirname(__FILE__).'/../priceminister.php');
require_once(dirname(__FILE__).'/../classes/priceminister.api.webservices.php');
require_once(dirname(__FILE__).'/../classes/priceminister.api.wallet.php');
require_once(dirname(__FILE__).'/../classes/priceminister.tools.class.php');

$id_orders = array_column(Db::getInstance()->executeS(
    'SELECT `id_order`
    FROM `'._DB_PREFIX_.'orders`
    WHERE `module` = "priceminister"
    AND `date_add` >= "'.pSQL(date('Y-m-d H:i:s', strtotime('LAST WEEK'))).'"'
), 'id_order');
printf('Number of orders to be updated: %d', count($id_orders));

$priceminister = new PriceMinister();

foreach ($id_orders as $id_order) {
    $order = new Order((int)$id_order);
    if (!Validate::isLoadedObject($order)) {
        printf('Order %d : can not be loaded.<br>', $id_order);
        continue;
    }

    $params = array(
        'id_order' => $order->id,
        'newOrderStatus' => $order->getCurrentOrderState()
    );

    if ($priceminister->hookActionOrderStatusUpdate($params)) {
        printf('Order %d : status updated.<br>', $order->id);
    } else {
        printf('Order %d : status not updated.<br>', $order->id);
    }
}
