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

require_once(dirname(__FILE__).'/../common/order.class.php');
require_once(dirname(__FILE__).'/../classes/amazon.order_info.class.php');

class AmazonOrder extends CommonOrder
{
    // PS 1.5 compat
    const ROUND_ITEM = 1;
    const ROUND_LINE = 2;
    const ROUND_TOTAL = 3;

    const PENDING = 1;
    const UNSHIPPED = 2;
    const PARTIALLYSHIPPED = 3;
    const SHIPPED = 4;
    const CANCELED = 5;
    const CHECKED = 6;
    const TO_CANCEL = 7;
    const PROCESS_CANCEL = 8;
    const REVERT_CANCEL = 9;

    const REGULAR_ORDER = 0;
    const PRIME_ORDER = 1;
    const PREMIUM_ORDER = 2;

    const ORDER_PENDING_AVAILABILITY = 'PendingAvailability';
    const ORDER_PENDING = 'Pending';
    const ORDER_UNSHIPPED = 'Unshipped';
    const ORDER_PARTIALLYSHIPPED = 'PartiallyShipped';
    const ORDER_SHIPPED = 'Shipped';
    const ORDER_INVOICE_UNCONFIRMED = 'InvoiceUnconfirmed';
    const ORDER_CANCELED = 'Canceled';
    const ORDER_UNFULFILLABLE = 'Unfulfillable';
    const ORDER_IN_CART = 'In Cart';// Really: Pending
    const ORDER_PROCESSING = 'Processing';// Really: Waiting for Report

    const SHIP_CATEGORY_EXPEDITED  = 'Expedited';
    const SHIP_CATEGORY_FREEECONOMY  = 'FreeEconomy';
    const SHIP_CATEGORY_NEXTDAY  = 'NextDay';
    const SHIP_CATEGORY_SAMEDAY  = 'SameDay';
    const SHIP_CATEGORY_SECONDDAY = 'SecondDay';
    const SHIP_CATEGORY_SCHEDULED = 'Scheduled';
    const SHIP_CATEGORY_STANDARD = 'Standard';

    public static $table_name = Amazon::TABLE_MARKETPLACE_ORDERS;

    public $marketPlaceOrderId     = null;
    public $marketPlaceOrderStatus = null;
    public $marketPlaceShipping    = null;
    public $marketPlaceChannel     = null; /*CHECKED by the module for "status.php" automaton*/
    public $fulfillmentCenterId;
    
    public $amazon_order_info     = null; /*New method 2016/10*/

    public static $errors = array();

    /**
     * Nov-08-2018: Fix static variable for CommonOrder::isExistingOrder()
     * @var bool
     */
    public static $debug_mode;
    
    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id, $id_lang);

        $this->amazon_order_info = new AmazonOrderInfo($id, $id_lang);
        
        if ($id) {
            $this->_getMpFields();
        }
    }

    private function _getMpFields()
    {
        if ($this->amazon_order_info->is_standard_feature_available) {
            if ($this->amazon_order_info->getOrderInfo()) {
                // For compatibility
                $this->marketPlaceOrderId = $this->amazon_order_info->mp_order_id;
                $this->marketPlaceOrderStatus = $this->amazon_order_info->mp_status;
                $this->marketPlaceChannel = $this->amazon_order_info->channel;
                $this->fulfillmentCenterId = $this->amazon_order_info->fulfillment_center_id;

                return (true);
            }
        }

        // For compatibility
        if (!Tools::strlen($this->marketPlaceOrderId)
            && AmazonTools::fieldExists(_DB_PREFIX_.'orders', 'mp_order_id')
            && AmazonTools::fieldExists(_DB_PREFIX_.'orders', 'mp_channel')
        ) {
            $sql = 'SELECT `mp_order_id`, `mp_status`, `mp_channel` FROM `'._DB_PREFIX_.'orders`
                    WHERE `id_order` = "'.(int)$this->id.'" LIMIT 1 ;';

            if ($result = Db::getInstance()->executeS($sql)) {
                $result = array_shift($result);
                
                if (Tools::strlen($result['mp_order_id'])) {
                    $this->marketPlaceOrderId = $result['mp_order_id'];
                }
                if (Tools::strlen($result['mp_status'])) {
                    $this->marketPlaceOrderStatus = $result['mp_status'];
                }
                if (Tools::strlen($result['mp_channel'])) {
                    $this->marketPlaceChannel = $result['mp_channel'];
                }

                return (true);
            }
        }
        return (false);
    }

    public static function getMarketplaceOrdersStatesByIdLang($id_lang_list, $id_order_state, $delay, $force = false, $debug = false)
    {
        $result = array();
        $result1 = array();
        $result2 = array();
        
        if (!$force) {
            $status = 'AND o.`mp_status` != '.(int)self::CHECKED;
        } else {
            $status = null;
        }

        if (AmazonTools::fieldExists(_DB_PREFIX_.'orders', 'mp_order_id')
            && ($force || AmazonTools::fieldExists(_DB_PREFIX_.'orders', 'mp_status'))) {
            $sql = 'SELECT o.`id_order`, o.`id_lang`, o.`mp_order_id`, o.`id_carrier`, o.`shipping_number`, oh.`date_add` 
                FROM `'._DB_PREFIX_.'orders` o
                LEFT JOIN `'._DB_PREFIX_.'order_history` oh ON (o.`id_order` = oh.`id_order`)
                WHERE (o.`module` = "amazon" OR o.`module` = "Amazon")
                    AND oh.`id_order_state` = '.(int)$id_order_state.'
                    AND o.`id_lang` IN ('.pSQL($id_lang_list).')
                    AND o.`mp_order_id` > "" '.$status.'
                    AND o.`date_add` > DATE_ADD(NOW(), INTERVAL -'.(int)$delay.' DAY)
                GROUP by o.`id_order`, o.`mp_order_id`';

            if ($debug) {
                CommonTools::p(sprintf('Query Result 1: %s', nl2br(print_r($sql, true))));
            }

            if (!($result1 = Db::getInstance()->executeS($sql))) {
                $result1 = array();
            }
        }


        if (!$force) {
            $status = 'AND mp.`mp_status` != '.(int)self::CHECKED;
        } else {
            $status = null;
        }

        if (AmazonTools::tableExists(_DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDERS)) {
            $sql = 'SELECT o.`id_order`, o.`id_lang`, mp.`mp_order_id`, o.`id_carrier`, o.`shipping_number`, oh.`date_add`  
                FROM `'._DB_PREFIX_.'orders` o
                LEFT JOIN `'._DB_PREFIX_.'order_history` oh ON (o.`id_order` = oh.`id_order`)
                LEFT JOIN `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDERS.'` mp ON (o.`id_order` = mp.`id_order`)
                WHERE (o.`module` = "amazon" OR o.`module` = "Amazon") 
                    AND oh.`id_order_state` = '.(int)$id_order_state.' 
                    AND o.`id_lang` IN ('.pSQL($id_lang_list).') 
                    AND mp.`mp_order_id` > "" '.$status.'
                    AND o.`date_add` > DATE_ADD(NOW(), INTERVAL -'.(int)$delay.' DAY)
                GROUP by o.`id_order`, mp.`mp_order_id`';

            if ($debug) {
                CommonTools::p(sprintf('Query Result 2: %s', nl2br(print_r($sql, true))));
            }

            if (!($result2 = Db::getInstance()->executeS($sql))) {
                $result2 = array();
            }
        }

        if (is_array($result1) && count($result1)) {
            $result = $result1;
        }
        if (is_array($result2) && count($result2)) {
            $result = array_merge($result, $result2);
        }
        return ($result);
    }

    /**
     * Return PS order id if exist
     * @param $marketplace_order_id
     * @return bool|int|mixed
     */
    public static function checkByMpId($marketplace_order_id)
    {
        if (AmazonTools::fieldExists(_DB_PREFIX_.'orders', 'mp_order_id')) {
            /* prevent duplicate imports with older version */
            $sql = 'SELECT `id_order`, `mp_order_id` 
                FROM `'._DB_PREFIX_.'orders`
                WHERE `mp_order_id` = "'.pSQL($marketplace_order_id).'" 
                ORDER BY `id_order` DESC';

            $result = Db::getInstance()->getRow($sql, false);

            if (is_array($result) && !empty($result['id_order']) && !empty($result['mp_order_id'])) {
                return ($result['id_order']);
            }
        }

        if (AmazonTools::tableExists(_DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDERS)) {
            $sql = 'SELECT `id_order` FROM `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDERS.'`
          		WHERE `mp_order_id` = "'.pSQL($marketplace_order_id).'" ORDER BY `id_order` DESC';

            $result = Db::getInstance()->getRow($sql, false);

            if (!($result)) {
                return (false);
            }

            $id_order = (int)$result['id_order'];

            $order = new Order($id_order);

            if (Validate::isLoadedObject($order)) {
                return((int)$order->id);
            } else {
                return(false);
            }
        }

        return (false);
    }

    public static function updateMarketplaceStatus($id_order, $marketplace_status)
    {
        if (AmazonTools::tableExists(_DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDERS)) {
            $sql = 'UPDATE `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDERS.'`
				  SET  `mp_status` = '.(int)$marketplace_status.'
				  WHERE `id_order` = '.(int)$id_order;

            $result = Db::getInstance()->execute($sql);
            
            if (Amazon::$debug_mode) {
                AmazonTools::pre(array(
                    "_updOrder:".Amazon::LF,
                    sprintf('%s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__),
                    "SQL:".$sql.Amazon::LF,
                    $result
                ));
            }

            if (!$result) {
                return false;
            }
        }

        if (AmazonTools::fieldExists(_DB_PREFIX_.'orders', 'mp_order_id')) {
            $sql = 'UPDATE `'._DB_PREFIX_.'orders`
				  SET  `mp_status` = '.(int)$marketplace_status.'
				  WHERE `id_order` = '.(int)$id_order;

            if (!Db::getInstance()->execute($sql)) {
                return false;
            }
        }
        return (true);
    }

    public function add($autodate = true, $nullValues = true, $marketPlaceOrderId = false, $marketPlaceOrderStatus = false, $marketPlaceChannel = false)
    {
        if (!parent::add($autodate, true)) {
            return (false);
        }

        $this->marketPlaceOrderId = $marketPlaceOrderId;
        $this->marketPlaceOrderStatus = $marketPlaceOrderStatus;
        $this->marketPlaceChannel = $marketPlaceChannel;

        if (!$this->_updOrder()) {
            return (false);
        }

        return (true);
    }

    private function _updOrder()
    {
        if (AmazonTools::tableExists(_DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDERS)) {
            $sql = 'REPLACE INTO `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDERS.'`
                    (`id_order`, `mp_order_id`, `mp_status`, `channel`) 
                VALUES (
                    '.(int)$this->id.', 
                    "'.pSQL($this->marketPlaceOrderId).'",
                    '.(int)$this->marketPlaceOrderStatus.',
                    "'.pSQL($this->marketPlaceChannel).'"
                );';

            $result = Db::getInstance()->execute($sql);

            if (Amazon::$debug_mode) {
                AmazonTools::pre(array(
                    "_updOrder:".Amazon::LF,
                    sprintf('%s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__),
                    "SQL:".$sql.Amazon::LF,
                    $result
                ));
            }

            if (!$result) {
                return false;
            } else {
                return (true);
            }
        }

        if (AmazonTools::fieldExists(_DB_PREFIX_.'orders', 'mp_order_id')
            && AmazonTools::fieldExists(_DB_PREFIX_.'orders', 'mp_channel')
        ) {
            $sql = 'UPDATE  `'._DB_PREFIX_.'orders`
                  SET `mp_order_id` = "'.pSQL($this->marketPlaceOrderId).'",
                      `mp_status` = "'.pSQL($this->marketPlaceOrderStatus).'",
                      `mp_channel` = "'.pSQL($this->marketPlaceChannel).'"
                  WHERE `id_order` = "'.pSQL($this->id).'" ;';


            $result = Db::getInstance()->execute($sql);

            if (Amazon::$debug_mode) {
                AmazonTools::pre(array(
                    "_updOrder:".Amazon::LF,
                    sprintf('%s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__),
                    "SQL:".$sql.Amazon::LF,
                    $result
                ));
            }

            if (!$result) {
                return false;
            }
        }

        return (true);
    }

    public function updateMpStatus($marketPlaceStatus)
    {
        $this->marketPlaceOrderStatus = $marketPlaceStatus;

        if (AmazonTools::tableExists(_DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDERS)) {
            $sql = 'UPDATE  `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_ORDERS.'`
                SET `mp_status` = "'.pSQL($this->marketPlaceOrderStatus).'"
                WHERE `id_order` = "'.pSQL($this->id).'" ;';

            $result = Db::getInstance()->execute($sql);

            if (Amazon::$debug_mode) {
                AmazonTools::pre(array(
                    "_updOrder:".Amazon::LF,
                    sprintf('%s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__),
                    "SQL:".$sql.Amazon::LF,
                    $result
                ));
            }

            if (!$result) {
                return(false);
            } else {
                return(true);
            }
        }
        
        if (AmazonTools::fieldExists(_DB_PREFIX_.'orders', 'mp_order_id') && AmazonTools::fieldExists(_DB_PREFIX_.'orders', 'mp_channel')) {
            $sql = 'UPDATE  `'._DB_PREFIX_.'orders` SET `mp_status` = "'.pSQL($this->marketPlaceOrderStatus).'" WHERE `id_order` = "'.pSQL($this->id).'";';

            if (!Db::getInstance()->execute($sql)) {
                return(false);
            }
        }

        return (true);
    }

    /**
     * This is MerchantFulfillment
     * @param $id_order
     * @param $marketPlaceOrderId
     * @param string $shippingServices
     * @param int $step
     * @param string $errors
     * @return bool
     */
    public static function updOrderMerchantFulfillment($id_order, $marketPlaceOrderId, $shippingServices = '', $step = 0, $errors = '')
    {
        if (AmazonTools::tableExists(_DB_PREFIX_.Amazon::TABLE_MARKETPLACE_SHIPPING_SERVICE)) {
            if (is_array($shippingServices) && !empty($shippingServices)) {
                $shippingServices = serialize($shippingServices) ;
            }
            
            if (is_array($errors) && !empty($errors)) {
                $errors = serialize($errors) ;
            }
            
            $select_sql = 'SELECT `id_order` FROM `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_SHIPPING_SERVICE.'`
          		WHERE `id_order` = '.(int)$id_order.' ORDER BY `id_order` DESC';

            $select_result = Db::getInstance()->getRow($select_sql, false);

            if (!($select_result)) {
                $sql = 'INSERT INTO `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_SHIPPING_SERVICE.'`
                        (`id_order`, `mp_order_id`, `process_step`, `shipping_services`, `errors`) 
                        VALUES ('.(int)$id_order.', "'.pSQL($marketPlaceOrderId).'",
                        '.(int)$step.', "'.pSQL($shippingServices).'", "'.pSQL($errors).'") ;';
            } else {
                // Why set `mp_order_id` again, io_order-mp_order_id should only set once on create.
                $sql = 'UPDATE `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_SHIPPING_SERVICE.'`
                        SET `process_step` = '.(int)$step.', 
                        '. (!empty($shippingServices) ? '`shipping_services` = "'.pSQL($shippingServices).'" ,' : '') .'
                        `errors` = "'.pSQL($errors).'"
                        WHERE `id_order` = '.(int)$id_order;
            }

            $result = Db::getInstance()->execute($sql);
            if (Amazon::$debug_mode) {
                AmazonTools::pre(array(
                    "updOrderShippingServices:".Amazon::LF,
                    sprintf('%s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__),
                    "SQL:".$sql.Amazon::LF,
                    $result
                ));
            }

            if (!$result) {
                return false;
            } else {
                return (true);
            }
        }
        
        return (true);
    }

    public static function updateShippingService($idOrder, $shippingService)
    {
        $mkpOrder = _DB_PREFIX_ . AmazonDBManager::TABLE_MKP_ORDERS;
        $sql = "UPDATE `$mkpOrder` 
            SET `shipping_services` = '" . pSQL(trim($shippingService)) . "' 
            WHERE `id_order` = " . (int)$idOrder;
        return Db::getInstance()->execute($sql);
    }
    
    public static function createShippingServiceTable()
    {
        $pass = true;
        $sql = '
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.Amazon::TABLE_MARKETPLACE_SHIPPING_SERVICE.'` (
                `id_order` INT NOT NULL ,
                `mp_order_id` VARCHAR( 32 ) NOT NULL,
                `process_step` INT NOT NULL DEFAULT 0,
                `shipping_services` TEXT NULL,
                `errors` TEXT NULL,
                PRIMARY KEY (`id_order`),
                KEY (`mp_order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';

        if (!Db::getInstance()->execute($sql)) {
            $error = 'SQL: '.$sql.Amazon::LF.'ERROR: '. Db::getInstance()->getMsgError();
            self::$errors[] = $error;
            $pass = false;
        }
        return($pass);
    }
    
    // Remove db management: updateTable

    public static function getByOrderId($id)
    {
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . pSQL(AmazonDBManager::TABLE_MKP_ORDERS) . "` 
            WHERE `id_order` = " . (int)$id;
        return Db::getInstance()->getRow($sql);
    }

    /**
     * Get all rows by buyer name, loose rule
     * @param $name
     * @return array|bool|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     */
    public static function getAllMpOrderIdsByBuyerName($name)
    {
        $names = AmazonTools::buildQueryConditionIn($name, true);

        $sql = 'SELECT `mp_order_id` 
                FROM `'._DB_PREFIX_.self::$table_name.'` 
                WHERE LOWER(`buyer_name`) IN ('.$names.')';

        return Db::getInstance()->executeS($sql);
    }

    /**
     * @param $mp_order_ids
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     */
    public static function getAllByMpOrderIds($mp_order_ids)
    {
        $ids = AmazonTools::buildQueryConditionIn($mp_order_ids, false);

        $sql = 'SELECT * FROM `'._DB_PREFIX_.self::$table_name.'` 
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

        $sql = 'DELETE FROM `'._DB_PREFIX_.self::$table_name.'` 
                WHERE `mp_order_id` IN ('.$ids.')';

        return Db::getInstance()->execute($sql);
    }
}
