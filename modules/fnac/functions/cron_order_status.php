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
 * ...........................................................................
 *
 * @author    debuss-a
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.fnac@common-services.com
 */

require_once(dirname(__FILE__).'/env.php');
require_once(dirname(__FILE__).'/../fnac.php');
require_once(dirname(__FILE__).'/../classes/fnac.tools.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.product.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.webservice.class.php');

$id_orders = FNAC_Tools::arrayColumn(Db::getInstance()->executeS(
    'SELECT `id_order`
    FROM `'._DB_PREFIX_.'orders`
    WHERE `module` = "fnac"
    AND `date_add` >= "'.pSQL(date('Y-m-d H:i:s', strtotime('LAST WEEK'))).'"'
), 'id_order');

$fnac = new Fnac();

foreach ($id_orders as $id_order) {
    try {
        $order = new Order((int)$id_order);
        if (!Validate::isLoadedObject($order)) {
            printf('Order %d : can not be loaded.<br>', $id_order);
            continue;
        }

        $params = array(
            'id_order' => $order->id,
            'newOrderStatus' => $order->getCurrentOrderState()
        );

        if ($fnac->hookActionOrderStatusPostUpdate($params)) {
            printf('Order %d : status updated.<br>', $order->id);
        } else {
            printf('Order %d : status not updated.<br>', $order->id);
        }
    } catch (Exception $exception) {
        printf('%s<br>', $exception->getMessage());
    }
}
