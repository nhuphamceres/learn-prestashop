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
 * @author    Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 *  Support by mail  :  support.mirakl@common-services.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

if (!class_exists('MiraklOrder')) {
    class MiraklOrder extends Order
    {
        const ORDER_CHANNEL = 'Mirakl';

        public $marketplace_order_id  = null;
        public $marketplace_channel  = null;
        public $shipping_deadline;

        /**
         * @var CHECKED - Checked by the module for "status.php" automaton
         */
        const CHECKED = 6;

        public function __construct($id = null, $id_lang = null)
        {
            parent::__construct($id, $id_lang);

            if ($id) {
                $this->getMpFields();
            }
        }

        public static function checkByMpId($marketplace_order_id, $id_shop)
        {
            $sql = "SELECT `id_order`, `mp_order_id` FROM `" . _DB_PREFIX_ . MiraklDBManager::TABLE_MKP_ORDERS .  "` 
                    WHERE `mp_order_id` = '" . pSQL($marketplace_order_id) . "' ORDER BY `id_order` DESC";

            return Db::getInstance()->getRow($sql);
        }

        private function getMpFields()
        {
            $sql = 'SELECT `mp_order_id`, `sales_channel`, `latest_ship_date`
                FROM `'._DB_PREFIX_.MiraklDBManager::TABLE_MKP_ORDERS.'` 
                WHERE `id_order` = "'.(int)$this->id.'" LIMIT 1 ;';

            if ($result = Db::getInstance()->ExecuteS($sql)) {
                $result = array_shift($result);
                $this->marketplace_order_id = $result['mp_order_id'];
                $this->marketplace_channel = $result['sales_channel'];
                $this->shipping_deadline = $result['latest_ship_date'];
            }
        }

        public function addMarketplaceDetails()
        {
            $sql = 'REPLACE INTO `'._DB_PREFIX_.MiraklDBManager::TABLE_MKP_ORDERS.'`
                    (`id_order`, `mp_order_id`, `mp_status`, `sales_channel`, `order_channel`, `latest_ship_date`)
                    VALUES (' . (int)$this->id . ', "' . pSQL($this->marketplace_order_id) . '", 0,
                            "' . pSQL($this->marketplace_channel) . '", "' . pSQL(self::ORDER_CHANNEL) . '",
                            "' . pSQL($this->shipping_deadline) . '")';

            if (!Db::getInstance()->Execute($sql)) {
                echo 'Unable to add mp_order_id ['.$this->marketplace_order_id.'/'.pSQL($this->marketplace_order_id).'] in table '._DB_PREFIX_.'orders<br>';
                echo Db::getInstance()->getMsgError();
                die;
                // return false;
            }

            return true;
        }

        public static function orderedItems($id_order)
        {
            $sql
                = 'SELECT
                            od.product_reference as SKU, od.product_quantity as Quantity FROM `'._DB_PREFIX_.'order_detail` od
                            LEFT JOIN `'._DB_PREFIX_.'product` p on (p.id_product = od.product_id)
                            WHERE od.id_order = '.(int)$id_order;

            return Db::getInstance()->ExecuteS($sql);
        }

        // 2021-05-07: Remove unused getMarketplaceOrdersStatesDateStartByIdLang(), getMarketplaceOrdersStatesByIdLang()

        public static function getShippingNumber($order)
        {
            if (!Validate::isLoadedObject($order)) {
                return (null);
            }

            if (!empty($order->shipping_number)) {
                return ($order->shipping_number);
            } else {
                if (version_compare(_PS_VERSION_, '1.5', '>')) {
                    $id_order_carrier = Db::getInstance()->getValue(
                        '
						SELECT `id_order_carrier`
						FROM `'._DB_PREFIX_.'order_carrier`
						WHERE `id_order` = '.(int)$order->id
                    );

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

        public static function getByOrderId($id)
        {
            $sql = "SELECT * FROM `" . _DB_PREFIX_ . pSQL(MiraklDBManager::TABLE_MKP_ORDERS) . "` WHERE `id_order` = " . (int)$id;
            return Db::getInstance()->getRow($sql);
        }

        /**
         * Return an array of all the Mirakl orders with not synced status to Mirakl
         *
         * @return array
         */
        public static function getMiraklOrdersByDate($fromDate)
        {
            $mpOrderTbl = _DB_PREFIX_ . pSQL(MiraklDBManager::TABLE_MKP_ORDERS);
            $orderTbl = _DB_PREFIX_ . 'orders';
            $sql = "SELECT mo.id_order, mo.mp_order_id, mo.mp_status, mo.sales_channel, mo.order_channel, o.id_order AS ps_id_order
                    FROM `$mpOrderTbl` mo
                    LEFT JOIN `$orderTbl` o ON mo.id_order = o.id_order
                    WHERE `order_channel` = '" . pSQL(self::ORDER_CHANNEL) . "' AND `date_add` >= '" . pSQL($fromDate) . "'";
            
            return Db::getInstance()->executeS($sql);
        }
    }
}
