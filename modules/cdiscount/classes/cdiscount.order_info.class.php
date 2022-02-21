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
 * @author    Tran Pham
 * @copyright Copyright (c) 2011-2020 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   CDiscount
 * Support by mail:  support.cdiscount@common-services.com
 */

require_once(dirname(__FILE__) . '/../includes/cdiscount.db.manager.php');

class CDiscountOrderInfo
{
    const ORDER_CHANNEL = 'Cdiscount';

    public $id_order;
    public $mp_order_id;
    public $earliest_ship_date;
    public $latest_ship_date;
    public $earliest_delivery_date;
    public $latest_delivery_date;
    public $cd_channel_id;
    public $cd_channel_name;
    public $clogistique;
    // Use this exist column to differ Amazon orders and CDiscount orders
    public $order_channel;

    public function __construct(
        $id_order,
        $mp_order_id = null,
        $cd_channel_id = 1,
        $cd_channel_name = null,
        $clogistique = 0,
        $earliest_ship_date = null,
        $latest_ship_date = null,
        $earliest_delivery_date = null,
        $latest_delivery_date = null
    ) {
        $this->id_order = $id_order;
        $this->mp_order_id = $mp_order_id;
        $this->cd_channel_id = $cd_channel_id;
        $this->cd_channel_name = $cd_channel_name;
        $this->clogistique = $clogistique;
        $this->earliest_ship_date = $earliest_ship_date;
        $this->latest_ship_date = $latest_ship_date;
        $this->earliest_delivery_date = $earliest_delivery_date;
        $this->latest_delivery_date = $latest_delivery_date;
        $this->order_channel = self::ORDER_CHANNEL;
    }

    public static function orderExist($id_order)
    {
        return (bool)Db::getInstance()->getRow(
            'SELECT * FROM `' . self::getTableName() . '` WHERE `id_order` = ' . (int)$id_order
        );
    }

    public static function getByMpOrderId($mpOrderId)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `' . self::getTableName() . '` 
            WHERE `mp_order_id` = "' . pSQL($mpOrderId) . '" AND `order_channel` = "' . pSQL(self::ORDER_CHANNEL) . '"'
        );
    }

    /**
     * @return bool
     */
    public function saveOrderInfo()
    {
        $columns = self::getColumns();
        $properties = array();
        foreach (get_object_vars($this) as $property => $value) {
            if (in_array($property, $columns) && !empty($value)) {
                $properties[$property] = $value;
            }
        }

        if (self::orderExist($this->id_order)) {
            $updateValues = array();
            foreach ($properties as $property => $value) {
                $updateValue = is_numeric($value) || is_bool($value) ? (int)$value : sprintf('"%s"', pSQL($value));
                $updateValues[] = sprintf('`%s` = %s', $property, $updateValue);
            }
            $updateSql = 'UPDATE IGNORE `' . self::getTableName() . '` ' .
                ' SET ' . implode(', ', $updateValues) .
                ' WHERE `id_order` = ' . (int)$this->id_order;
            $result = Db::getInstance()->execute($updateSql);
        } else {
            $insertFields = array();
            $insertValues = array();
            foreach ($properties as $property => $value) {
                $fieldType = $this->getPropertyType($property);
                $insertFields[] = '`' . pSQL($property) . '`';
                $insertValues[] = in_array($fieldType, array('int', 'bool')) ? (int)$value : sprintf('"%s"', pSQL($value));
            }
            $insertSql = 'INSERT INTO `' . self::getTableName() . '` (' . implode(', ', $insertFields) . ') 
                          VALUES (' . implode(', ', $insertValues) . ')';
            $result = Db::getInstance()->execute($insertSql);
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function getOrderInfo()
    {
        $columns = self::getColumns();
        $sql = 'SELECT * FROM `' . self::getTableName() . '` WHERE `id_order` = ' . (int)$this->id_order;
        $result = Db::getInstance()->getRow($sql);

        if ($result) {
            foreach ($result as $field => $value) {
                if (in_array($field, $columns) && property_exists($this, $field)) {
                    $this->{$field} = $value;
                }
            }

            return true;
        }

        return false;
    }

    protected static function getTableName()
    {
        return _DB_PREFIX_ . CDiscountDBManager::TABLE_MARKETPLACE_ORDERS;
    }

    protected static function getColumns()
    {
        $columns = array();
        $result = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . self::getTableName() . '`');
        if (is_array($result) && count($result)) {
            foreach ($result as $row) {
                $columns[] = $row['Field'];
            }
        }

        return $columns;
    }

    private function getPropertyType($property)
    {
        switch ($property) {
            case 'id_order':
            case 'cd_channel_id':
                return 'int';
            case 'clogistique':
                return 'bool';
            default:
                return 'str';
        }
    }
}
