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

class AmazonOrderItem
{
    public static $errors = array();
    public static $table = Amazon::TABLE_MARKETPLACE_ORDER_ITEMS;

    public $mp_order_id = null;
    public $order_item_id = null;
    public $id_order = null;
    public $id_product = null;
    public $id_product_attribute = null;
    public $quantity = null;
    public $sku = null;
    public $asin = null;
    public $carrier_code = null;
    public $carrier_name = null;
    public $shipping_method = null;
    public $tracking_number = null;
    public $item_status = null;
    public $reason = null;//For cancelation
    public $id_order_detail;    // Map with ps_order_detail
    public $additional_info = array();

    /** @var array|null */
    public $customization = null;

    protected static $required_values = array('mp_order_id', 'order_item_id', 'id_order', 'id_product', 'sku');

    
    public function __construct($mp_order_id = null, $order_item_id = null)
    {
        if (Tools::strlen($mp_order_id) && Tools::strlen($order_item_id)) {
            $this->setOrderItem($mp_order_id, $order_item_id);
        }
    }
    
    public static function getErrors()
    {
        $errors = self::$errors;
        self::$errors = array();
        
        return $errors;
    }

    public function saveOrderItem()
    {
        $values = self::$required_values;
        self::$errors = array();
        $table = _DB_PREFIX_ . Amazon::TABLE_MARKETPLACE_ORDER_ITEMS;

        if (!AmazonTools::tableExists($table)) {
            self::$errors[] = "Missing table: $table";
            return false;
        }

        if (! $this->_compatibility($table)) {
            return false;
        }

        foreach ($values as $value) {
            if ($this->{$value} == null) {
                self::$errors[] = sprintf('%s: "%s"', 'Missing value', $value);
            }
        }
        if (is_array(self::$errors) && count(self::$errors)) {
            return false;
        }

        $sql = 'REPLACE INTO `'.$table.'` (`mp_order_id`, `order_item_id`, `id_order`, `id_product`, `id_product_attribute`, `quantity`, `sku`, `asin`, `carrier_code`, `carrier_name`, `shipping_method`, `tracking_number`, `item_status`, `reason`, `customization`, `id_order_detail`, `additional_info`) 
             VALUES (
                "'.pSQL($this->mp_order_id).'",
                "'.pSQL($this->order_item_id).'",
                '.(int)$this->id_order.',
                '.(int)$this->id_product.',
                '.($this->id_product_attribute == null ? 'NULL' : (int)$this->id_product_attribute).',
                '.($this->quantity == null ? 'NULL' : (int)$this->quantity).',
                "'.pSQL($this->sku).'",
                "'.pSQL($this->asin).'",
                "'.pSQL($this->carrier_code).'",
                "'.pSQL($this->carrier_name).'",
                "'.pSQL($this->shipping_method).'",
                "'.pSQL($this->tracking_number).'",
                '.($this->item_status == null ? 'NULL' : (int)$this->item_status).',
                "'.pSQL($this->reason).'",
                "'.($this->customization ? pSQL(serialize($this->customization)) : '').'",
                "'.(is_null($this->id_order_detail) ? 'NULL' : (int)$this->id_order_detail).'",
                "' . pSQL(count($this->additional_info) ? json_encode($this->additional_info) : null) . '"
            )';

        $result = Db::getInstance()->execute($sql);

        if (Amazon::$debug_mode) {
            AmazonTools::pre(array(
                sprintf('%s - %s::%s - line #%d'.Amazon::LF, basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__),
                'SQL: '.print_r($sql, true).Amazon::LF,
                'Result: '.print_r($result, true).Amazon::LF
            ));
        }

        if (!$result) {
            self::$errors[] = sprintf('Save error: %s', Db::getInstance()->getMsgError());
            return false;
        }

        return true;
    }

    private function setOrderItem($mp_order_id, $order_item_id)
    {
        self::$errors = array();

        if (!AmazonTools::tableExists(_DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDER_ITEMS)) {
            self::$errors[] = 'Missing table: '._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDER_ITEMS;
            return(false);
        }

        $sql = 'SELECT * FROM `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDER_ITEMS.'` WHERE `mp_order_id`="'.pSQL($mp_order_id).'" AND `order_item_id`="'.pSQL($order_item_id).'"';

        $result = Db::getInstance()->getRow($sql);

        if (Amazon::$debug_mode) {
            AmazonTools::pre(array(
                sprintf('%s - %s::%s - line #%d'.Amazon::LF, basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__),
                'SQL: '.print_r($sql, true).Amazon::LF,
                'Result: '.print_r($result, true).Amazon::LF
            ));
        }

        if (!is_array($result) || !count($result)) {
            return(false);
        }

        $this->mp_order_id = $result['mp_order_id'];
        $this->order_item_id = $result['order_item_id'];
        $this->id_order = (int)$result['id_order'];
        $this->id_product = (int)$result['id_product'];
        $this->id_product_attribute = (int)$result['id_product_attribute'];
        $this->quantity = (int)$result['quantity'];
        $this->sku = $result['sku'];
        $this->asin = $result['asin'];
        $this->carrier_code = $result['carrier_code'];
        $this->carrier_name = $result['carrier_name'];
        $this->shipping_method = $result['shipping_method'];
        $this->tracking_number = $result['tracking_number'];
        $this->item_status = $result['item_status'];
        $this->reason = $result['reason'];

        return(true);
    }
    
    public static function getOrderItems($mp_order_id)
    {
        self::$errors = array();
        $ordered_items_id = array();

        if (!AmazonTools::tableExists(_DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDER_ITEMS)) {
            if (Amazon::$debug_mode) {
                AmazonTools::pre(array(
                    sprintf('%s - %s::%s - line #%d'.Amazon::LF, basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__),
                    'Missing Table: '._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDER_ITEMS.Amazon::LF
                ));
            }
            return(false);
        }

        $sql = 'SELECT `order_item_id` FROM `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDER_ITEMS.'` WHERE `mp_order_id`="'.pSQL($mp_order_id).'"';

        $result = Db::getInstance()->ExecuteS($sql);

        if (Amazon::$debug_mode) {
            AmazonTools::pre(array(
                sprintf('%s - %s::%s - line #%d'.Amazon::LF, basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__),
                'SQL: '.print_r($sql, true).Amazon::LF,
                'Result: '.print_r($result, true).Amazon::LF
            ));
        }

        if (!is_array($result) || !count($result)) {
            return(false);
        } else {
            foreach ($result as $order_item) {
                $ordered_items_id[] = $order_item['order_item_id'];
            }
        }

        return($ordered_items_id);
    }

    public static function getItemByIdOrderDetail($id_order_detail)
    {
        $table = _DB_PREFIX_ . Amazon::TABLE_MARKETPLACE_ORDER_ITEMS;
        $sql = "SELECT * FROM `".pSQL($table)."` WHERE `id_order_detail` = ".(int)$id_order_detail;
        return Db::getInstance()->getRow($sql);
    }
    
    public static function getItemByIdOrderAndIdProduct($id_order, $id_product, $id_product_attribute = null)
    {
        $table = _DB_PREFIX_ . Amazon::TABLE_MARKETPLACE_ORDER_ITEMS;
        $sql = "SELECT * FROM `".pSQL($table)."` WHERE `id_order` = ".(int)$id_order." AND `id_product` = ".(int)$id_product;
        if ($id_product_attribute) {
            $sql .= " AND `id_product_attribute` = ".(int)$id_product_attribute;
        }
        return Db::getInstance()->getRow($sql);
    }

    /**
     * @param $id_order_detail
     * @param null $id_order
     * @param null $id_product
     * @param null $id_product_attribute
     * @return array|bool|object|null
     */
    public static function getItem($id_order_detail, $id_order = null, $id_product = null, $id_product_attribute = null)
    {
        // Try new structure by id_order_detail, then try legacy one
        $result = self::getItemByIdOrderDetail($id_order_detail);
        if (!$result) {
            $result = self::getItemByIdOrderAndIdProduct($id_order, $id_product, $id_product_attribute);
        }

        // Parse customization a little
        if ($result && isset($result['customization']) && $result['customization'] && Tools::strlen($result['customization'])) {
            $customization = unserialize($result['customization']);
            $result['customization'] = is_array($customization) ? self::parseCustomization($customization) : null;
        }
        // Parse additional info also
        if ($result && isset($result['additional_info']) && $result['additional_info'] && Tools::strlen($result['additional_info'])) {
            $additionalInfo = json_decode($result['additional_info'], true);
            $result['additional_info'] = $additionalInfo;
        }
        
        return $result;
    }

    /**
     * @param $mp_order_ids
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     */
    public static function getAllByMpOrderIds($mp_order_ids)
    {
        $ids = AmazonTools::buildQueryConditionIn($mp_order_ids, false);

        $sql = 'SELECT * FROM `'._DB_PREFIX_.self::$table.'` 
                WHERE `mp_order_id` IN ('.$ids.')';

        return Db::getInstance()->executeS($sql);
    }

    /**
     * @param $mp_order_ids
     * @return bool
     */
    public static function deleteAllByMpOrderIds($mp_order_ids)
    {
        $ids = AmazonTools::buildQueryConditionIn($mp_order_ids, false);

        $sql = 'DELETE FROM `'._DB_PREFIX_.self::$table.'` 
                WHERE `mp_order_id` IN ('.$ids.')';

        return Db::getInstance()->execute($sql);
    }

    /**
     * 2020-10-01: Remove a pre-parse customization function before save
     *
     * @param $input
     * @return array
     */
    public static function parseCustomization($input)
    {
        // From 4.6.41, customization should store complete Amazon customization data
        $parseFromComplete = self::parseCompleteCustomization($input);
        if (count($parseFromComplete)) {
            return array('type' => 'complete', 'data' => $parseFromComplete);
        }

        // Keep data as-is, because it's already parsed in older version
        return array('type' => 'legacy', 'data' => $input);
    }

    /**
     * @param $input
     * @return array [[label => , value => ], [label => , value => ],]
     */
    protected static function parseCompleteCustomization($input)
    {
        $result = array();
        $custom = array();
        
        if (is_array($input)
            && isset($input['customizationInfo'], $input['customizationInfo']['version3.0'], $input['customizationInfo']['version3.0']['surfaces'])
            && is_array($input['customizationInfo']['version3.0']['surfaces'])) {
            foreach ($input['customizationInfo']['version3.0']['surfaces'] as $surface) {
                if (isset($surface['areas']) && is_array($surface['areas'])) {
                    foreach ($surface['areas'] as $area) {
                        if (isset($area['label']) && (isset($area['optionValue']) || isset($area['text']))) {
                            $result[] = self::customizationDisplay($area);
                        }
                        // 2021-03-05: Special case for 92093, whose line item has 'colorName' & 'fontFamily'
                        // This existed before but Tran removed it, need to add again.
                        foreach ($area as $areaKey => $areaValue) {
                            if (stripos($areaKey, 'color') !== false || stripos($areaKey, 'font') !== false) {
                                $custom[$areaKey] = array('label' => $areaKey, 'value' => $areaValue);
                            }
                        }
                    }
                }
            }
        }
        
        return array_merge($result, $custom);
    }

    /**
     * @param $area
     * @return array
     */
    protected static function customizationDisplay($area)
    {
        if (isset($area['optionValue'])) {
            $customizationValue = $area['optionValue'];
        } elseif ($area['text']) {
            $customizationValue = $area['text'];
        } else {
            $customizationValue = '';
        }
        
        return array('label' => $area['label'], 'value' => $customizationValue);
    }

    /**
     * Compatibility with PS 1.5 and earlier
     * @param $table
     *
     * @return bool
     */
    private function _compatibility($table)
    {
        if (! AmazonTools::amazonFieldExists($table, 'customization')) {
            $sql = "ALTER TABLE `" . pSQL($table) . "` ADD COLUMN `customization` TEXT NULL AFTER `reason`";
            if (! Db::getInstance()->execute($sql)) {
                self::$errors[] = "Cannot insert column customization";
                return false;
            }
        }

        return true;
    }
}
