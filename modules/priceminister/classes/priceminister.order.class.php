<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
 * Contact by Email :  support.priceminister@common-services.com
 */

require_once(_PS_MODULE_DIR_.'priceminister/common/order.class.php');

class PriceMinisterOrder extends CommonOrder
{

    public static function checkByMpId($marketPlaceOrderId)
    {
        if (PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_ORDERS)) {
            $sql = 'SELECT `id_order`, `mp_order_id` FROM `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_ORDERS.'` WHERE `mp_order_id` = '.pSQL($marketPlaceOrderId); //.' LIMIT 1';

            if ($result = Db::getInstance()->getRow($sql)) {
                return $result;
            }
        }
        if (PriceMinisterTools::fieldExists(_DB_PREFIX_.'orders', 'mp_order_id')) {
            /* OLD METHOD for compatibility */
            $sql = 'SELECT `id_order`, `mp_order_id` FROM `'._DB_PREFIX_.'orders`
			where `mp_order_id` = "'.pSQL($marketPlaceOrderId).'" ORDER BY `id_order` DESC ;';

            if (!($result = Db::getInstance()->ExecuteS($sql))) {
                return false;
            }

            return ($result[0]);
        }

        return (false);
    }

    public static function addOrderExt($params = null)
    {
        if (!is_array($params) || !count($params)) {
            return false;
        }

        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_ORDERS)) {
            return false;
        }

        $sql = 'INSERT IGNORE INTO `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_ORDERS.'`
			(`id_order`, `mp_order_id`, `shipping_type`, `relay`)
			VALUES ('.(int)$params['id_order'].', "'.pSQL($params['mp_order_id']).'", "'.pSQL($params['shipping_type']).'", "'.pSQL($params['relay']).'");';

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        try {
            @Db::getInstance()->execute(
                'UPDATE `'._DB_PREFIX_.'orders`
                SET `mp_order_id` = "'.pSQL($params['mp_order_id']).'"
                WHERE `id_order` = '.(int)$params['id_order']
            );
        } catch (Exception $exception) {
            
        }

        return true;
    }

    public static function updateOrderExt($params = null, $id_order = null)
    {
        if (!is_array($params) || !count($params)) {
            return false;
        }

        $id_order = array_key_exists('id_order', $params) ? $params['id_order'] : $id_order;

        if (!$id_order) {
            return false;
        } elseif (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_ORDERS)) {
            return false;
        }

        $set_values = '';
        foreach ($params as $set => $value) {
            $set_values .= '`'.pSQL($set).'` = "'.pSQL($value).'", ';
        }
        $set_values = trim($set_values, ' ,');

        $sql = 'UPDATE `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_ORDERS.'`
			SET '.$set_values.'
			WHERE `id_order` = '.(int)$id_order;

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        return true;
    }

    public static function getOrderExt($id_order)
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_ORDERS.'`
          WHERE `id_order` = '.(int)$id_order.' LIMIT 1 ;';

        if ($result = Db::getInstance()->executeS($sql)) {
            return array_shift($result);
        }

        return false;
    }

    public function addMarketplaceItem($id_product, $id_product_attribute, $itemid)
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_PRODUCT_ORDERED)) {
            return false;
        }

        $sql = 'REPLACE INTO `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_PRODUCT_ORDERED.'`
                        (`id_order`, `id_product`, `id_product_attribute`, `itemid`) 
                VALUES  ('.(int)$this->id.', '.(int)$id_product.', '.($id_product_attribute ? $id_product_attribute : 'NULL').', '.(int)$itemid.') ;';

        if (!Db::getInstance()->Execute($sql)) {
            return false;
        }

        return (true);
    }

    public function getMarketplaceItem()
    {
        if (!PriceMinisterTools::tableExists(_DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_PRODUCT_ORDERED)) {
            return false;
        }

        $sql = 'SELECT * FROM `'._DB_PREFIX_.PriceMinister::TABLE_PRICEMINISTER_PRODUCT_ORDERED.'` WHERE `id_order` = '.(int)$this->id;

        if (!($result = Db::getInstance()->ExecuteS($sql))) {
            return false;
        }

        return $result;
    }
}