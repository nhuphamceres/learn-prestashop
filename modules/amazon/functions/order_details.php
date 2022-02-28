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
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

require_once dirname(__FILE__) . '/wrapper.php';

class AmazonFunctionOrderDetails extends AmazonFunctionWrapper
{
    public function dispatch()
    {
        $action = Tools::getValue('action');
        $idOrder = Tools::getValue('id_order');
        $shippingService = Tools::getValue('shipping_service');

        switch ($action) {
            default:
                echo json_encode($this->updateShippingService($idOrder, $shippingService));
                break;
        }
    }

    // todo: Translation
    protected function updateShippingService($idOrder, $shippingService)
    {
        require_once dirname(dirname(__FILE__)) . '/classes/amazon.order.class.php';

        if (!$idOrder) {
            return array('success' => false, 'msg' => 'Empty order id!');
        }

        $mkpOrder = AmazonOrder::getByOrderId($idOrder);
        if (!$mkpOrder) {
            return array('success' => false, 'msg' => 'Order does not exist!');
        }

        if (AmazonOrder::updateShippingService($idOrder, $shippingService)) {
            return array('success' => true, 'msg' => 'Updated shipping service');
        } else {
            return array('success' => false, 'msg' => 'Failed to update shipping service!');
        }
    }
}

$orderDetailsFunc = new AmazonFunctionOrderDetails(null, null);
$orderDetailsFunc->dispatch();
