<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Common-Services Co., Ltd. is strictly forbidden.
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
 * @author    Olivier B.
 * @copyright Copyright (c) 2011-2017 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   CDiscount
  * Support by mail:  support.cdiscount@common-services.com
 */

require_once(dirname(__FILE__).'/../common/configuration.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.order_info.class.php');

class CDiscountOrder extends Order
{
    const MODULE = CDiscount::MODULE;

    const IMPORT_BY_ID = Cdiscount::IMPORT_BY_ID;

    public $marketPlaceOrderId     = null;

    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id, $id_lang);

        if ($id) {
            $orderInfo = new CDiscountOrderInfo($id);
            if ($orderInfo->getOrderInfo() && $orderInfo->mp_order_id) {
                $this->marketPlaceOrderId = $orderInfo->mp_order_id;
            }
        }
    }

    /**
     * @param $marketPlaceOrderId
     * @return array|bool|object|null
     */
    public static function checkByMpId($marketPlaceOrderId)
    {
        return CDiscountOrderInfo::getByMpOrderId($marketPlaceOrderId);
    }

    public static function isExistingOrder($date_add, $amount, $payment_title)
    {
        $sql = 'SELECT `id_order` FROM `'._DB_PREFIX_.'orders`
			WHERE `payment` = "'.pSQL($payment_title).'" AND  `module`="cdiscount" AND `date_add`="'.$date_add.'" AND `total_paid`='.(float)$amount;

        $result = Db::getInstance()->executeS($sql, true, false);

        if (is_array($result) && count($result)) {
            return ($result[0]['id_order']);
        }

        return (false);
    }

    public static function orderedItems($id_order, $mode = self::IMPORT_BY_ID)
    {
        if ($mode == self::IMPORT_BY_ID) {
            $sql
                = 'SELECT
                        IF ( od.product_attribute_id, CONCAT(od.product_id, "_", od.product_attribute_id), od.product_id) as SellerProductId, p.condition  FROM `'._DB_PREFIX_.'order_detail` od
                        LEFT JOIN `'._DB_PREFIX_.'product` p on (p.id_product = od.product_id)
                        WHERE od.id_order = '.(int)$id_order;
        } else {
            $sql
                = 'SELECT
                        TRIM(od.product_reference) as SellerProductId, p.condition  FROM `'._DB_PREFIX_.'order_detail` od
                        LEFT JOIN `'._DB_PREFIX_.'product` p on (p.id_product = od.product_id)
                        WHERE od.id_order = '.(int)$id_order;
        }

        return ($result = Db::getInstance()->ExecuteS($sql));
    }

    /**
     * @param $id_lang
     * @param $id_order_state
     * @param int $period
     * @param false $debug
     * @return array|bool|object|null
     */
    public static function getMarketplaceOrdersStatesDateStartByIdLang($id_lang, $id_order_state, $period = 15, $debug = false)
    {
        $sql = 'SELECT DATE_FORMAT(MIN(o.`date_add`),"%Y-%m-%d") as date_start, DATE_FORMAT(MAX(o.`date_add`),"%Y-%m-%d") as date_end 
                FROM `' . _DB_PREFIX_ . 'orders` o
                LEFT JOIN `' . _DB_PREFIX_ . CDiscountDBManager::TABLE_MARKETPLACE_ORDERS . '` tco ON (o.`id_order` = tco.`id_order`)
                LEFT JOIN `' . _DB_PREFIX_ . 'order_history` oh ON (o.`id_order` = oh.`id_order`)
                WHERE o.`module` = "' . self::MODULE . '" AND oh.`id_order_state` = ' . (int)$id_order_state . ' 
                AND o.`id_lang` =' . (int)$id_lang . ' AND tco.`mp_order_id` > ""
                AND CAST(o.`date_add` AS DATE) >= DATE_ADD(NOW(), INTERVAL -' . (int)$period . ' DAY)';
        $result = Db::getInstance()->getRow($sql);

        if ($debug) {
            CommonTools::p($sql);
            CommonTools::p($result);
        }

        if (!isset($result['date_start']) || !$result['date_start']) {
            return false;
        }

        return $result;
    }

    /**
     * @param $id_lang
     * @param $id_order_state
     * @param int $period
     * @param false $debug
     * @return array|bool|mysqli_result|PDOStatement|resource
     */
    public static function getMarketplaceOrdersStatesByIdLang($id_lang, $id_order_state, $period = 15, $debug = false)
    {
        $mpOrderTable = CDiscountDBManager::TABLE_MARKETPLACE_ORDERS;
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $sql = 'SELECT o.`id_order`, o.`id_lang`, tco.`mp_order_id`, o.`id_carrier`, 
                    IF(LENGTH(oc.`tracking_number`), oc.`tracking_number`, o.`shipping_number`) as shipping_number 
                    FROM `'._DB_PREFIX_.'orders` o
                    LEFT JOIN `'._DB_PREFIX_.$mpOrderTable.'` tco ON (o.`id_order` = tco.`id_order`)
                    LEFT JOIN `'._DB_PREFIX_.'order_history` oh ON (o.`id_order` = oh.`id_order`)
                    LEFT JOIN `'._DB_PREFIX_.'order_carrier` oc ON (o.`id_order` = oc.`id_order`)
                    WHERE o.`module` = "'.self::MODULE.'" AND oh.`id_order_state` = '.(int)$id_order_state.' 
                    AND o.`id_lang` ='.(int)$id_lang.' AND tco.`mp_order_id` > ""
                    AND CAST(o.`date_add`AS DATE) >= DATE_ADD(NOW(), INTERVAL -'.(int)$period.' DAY)
                    GROUP by o.`id_order`, tco.`mp_order_id`';
        } else {
            $sql = 'SELECT o.`id_order`, o.`id_lang`, tco.`mp_order_id`, o.`id_carrier`, o.`shipping_number`  FROM `'._DB_PREFIX_.'orders` o
                    LEFT JOIN `'._DB_PREFIX_.$mpOrderTable.'` tco ON (o.`id_order` = tco.`id_order`)
                    LEFT JOIN `'._DB_PREFIX_.'order_history` oh ON (o.`id_order` = oh.`id_order`)
                    WHERE o.`module` = "'.self::MODULE.'" AND oh.`id_order_state` = '.(int)$id_order_state.' 
                    AND o.`id_lang` ='.(int)$id_lang.' AND tco.`mp_order_id` > ""
                    AND CAST(o.`date_add` AS DATE) >= DATE_ADD(NOW(), INTERVAL -'.(int)$period.' DAY)
                    GROUP by o.`id_order`, tco.`mp_order_id`';
        }

        $result = Db::getInstance()->ExecuteS($sql);

        if ($debug) {
            CommonTools::p($sql);
            CommonTools::p($result);
        }

        if (!$result) {
            return false;
        }

        return $result;
    }

    public static function getShippingNumber($order)
    {
        if (!Validate::isLoadedObject($order)) {
            return (null);
        }

        if (!empty($order->shipping_number)) {
            return ($order->shipping_number);
        } else {
            if (version_compare(_PS_VERSION_, '1.5', '>')) {
                $id_order_carrier = Db::getInstance()->getValue('
						SELECT `id_order_carrier`
						FROM `'._DB_PREFIX_.'order_carrier`
						WHERE `id_order` = '.(int)$order->id);

                if ($id_order_carrier) {
                    $order_carrier = new OrderCarrier($id_order_carrier);

                    if (Validate::isLoadedObject($order_carrier)) {
                        if (!empty($order_carrier->tracking_number)) {
                            return ($order_carrier->tracking_number);
                        }
                    }
                }
            }
        }

        return (null);
    }
}
