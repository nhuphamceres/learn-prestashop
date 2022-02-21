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

require_once(dirname(__FILE__).'/../includes/cdiscount.db.manager.php');

class CDiscountPickupPoint
{
    const MODULE = CDiscount::MODULE;
    /* Common variables */

    const MONDIAL_RELAY_TABLE = 'mr_selected';
    const SO_COLISSIMO_TABLE = 'socolissimo_delivery_info';
    const MONDIAL_RELAY_TYPE = 'mr';
    const SO_COLISSIMO_TYPE = 'so';
    public $id;
    public $pickup_type;
    public $id_customer;
    public $id_method;
    public $id_cart;
    public $id_order;
    public $name;
    public $address1;
    public $address2 = '';
    public $address3 = '';

    /* Specific Mondial Relay */
    public $address4     = '';
    public $zipcode;
    public $city;
    public $country;
    public $weight       = null;
    public $insurance    = 0;
    public $tracking_url = null;

    /* Specific So Colissimo */
    public $label_url  = null;
    public $exp_number = null;
    public $date_add   = '0000-00-00 00:00:00';

    /* Constants */
    public $date_upd = '0000-00-00 00:00:00';
    public $phone;
    public $email;
    public $company  = '';

    public static function isMondialRelayInstalled()
    {
        static $hasMondialRelay = null; // cache it

        if ($hasMondialRelay !== null) {
            return ($hasMondialRelay);
        }

        $mr_carriers = Db::getInstance()->executeS('SELECT id_carrier FROM `'._DB_PREFIX_.'carrier` WHERE `external_module_name` = "mondialrelay" AND `deleted` = 0');

        if (is_array($mr_carriers) && count($mr_carriers)) {
            $mb_tables = Db::getInstance()->executeS('SHOW TABLES LIKE "'._DB_PREFIX_.'mr_method"');

            if (is_array($mb_tables) && count($mb_tables)) {
                $hasMondialRelay = true;

                return (true);
            }
        }
        $hasMondialRelay = false;

        return (false);
    }

    public static function isSoColissimoInstalled()
    {
        static $hasSoColissimo = null; // cache it

        if ($hasSoColissimo !== null) {
            return ($hasSoColissimo);
        }

        $carriers = Db::getInstance()->executeS('SELECT `id_carrier` FROM `'._DB_PREFIX_.'carrier` WHERE `external_module_name` = "socolissimo" AND `deleted` = 0 OR `external_module_name` = "soliberte" AND `deleted` = 0 OR `external_module_name` = "soflexibilite" AND `deleted` = 0');

        if (is_array($carriers) && count($carriers)) {
            $mb_tables = Db::getInstance()->executeS('SHOW TABLES LIKE "'._DB_PREFIX_.'socolissimo_delivery_info"');

            if (is_array($mb_tables) && count($mb_tables)) {
                $hasSoColissimo = true;

                return (true);
            }
        }
        $hasSoColissimo = false;

        return (false);
    }

    public static function getMondialRelayMarketplaceOrdersStatesByIdLang($id_lang, $id_order_state, $period = 15)
    {
        $sql = 'SELECT o.`id_order`, o.`id_lang`, o.`mp_order_id`, o.`id_carrier`, mr.`exp_number` as shipping_number 
                FROM `'._DB_PREFIX_.'orders` o
                LEFT JOIN `'._DB_PREFIX_.'order_history` oh ON (o.`id_order` = oh.`id_order`)
                LEFT JOIN `'._DB_PREFIX_.self::MONDIAL_RELAY_TABLE.'` mr ON (o.`id_order` = mr.`id_order`)
                LEFT JOIN `'._DB_PREFIX_.CDiscountDBManager::TABLE_MARKETPLACE_ORDERS.'` tco ON (o.`id_order` = tco.`id_order`)
                WHERE o.`module` = "'.self::MODULE.'" AND oh.`id_order_state` = '.(int)$id_order_state.' 
                AND o.`id_lang` = '.$id_lang.' AND tco.`mp_order_id` > ""
                AND o.`date_add` > DATE_ADD(NOW(), INTERVAL -'.(int)$period.' DAY)
                GROUP by o.`id_order`, o.`mp_order_id`
                HAVING shipping_number != ""';

        $result = Db::getInstance()->ExecuteS($sql);

        if (!is_array($result) || !count($result)) {
            return (array());
        } else {
            return ($result);
        }
    }

    public function save()
    {
        switch ($this->pickup_type) {
            case (self::MONDIAL_RELAY_TYPE):
                return $this->_saveMondialRelay();
            case (self::SO_COLISSIMO_TYPE):
                return $this->_saveSoColissimo();
            default:
                return null;
        }
    }

    private function _saveMondialRelay()
    {
        $params = array(
            'id_customer' => (int)$this->id_customer,
            'id_method' => (int)$this->id_method,
            'id_cart' => (int)$this->id_cart,
            'id_order' => (int)$this->id_order,
            'MR_poids' => (float)$this->weight,
            'MR_insurance' => pSQL($this->insurance),
            'MR_Selected_Num' => pSQL(sprintf('%06s', $this->id)),
            'MR_Selected_LgAdr1' => pSQL($this->name),
            'MR_Selected_LgAdr2' => pSQL($this->address2),
            'MR_Selected_LgAdr3' => pSQL($this->address1),
            'MR_Selected_LgAdr4' => pSQL($this->address4),
            'MR_Selected_CP' => pSQL($this->zipcode),
            'MR_Selected_Ville' => pSQL($this->city),
            'MR_Selected_Pays' => pSQL($this->country),
            'url_suivi' => pSQL($this->tracking_url),
            'url_etiquette' => pSQL($this->label_url),
            'exp_number' => pSQL($this->exp_number),
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s')
        );

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            return (Db::getInstance()->insert(self::MONDIAL_RELAY_TABLE, $params));
        }

        return (Db::getInstance()->autoExecute(_DB_PREFIX_.self::MONDIAL_RELAY_TABLE, $params, 'INSERT'));
    }

    private function _saveSoColissimo()
    {
        $params = array(
            'id_cart' => (int)$this->id_cart,
            'id_customer' => (int)$this->id_customer,
            'delivery_mode' => 'A2P',
            'prid' => pSQL($this->id),
            'prname' => pSQL($this->name),
            'pradress1' => pSQL($this->address1),
            'pradress2' => pSQL($this->address2),
            'pradress3' => pSQL($this->address3),
            'pradress4' => pSQL($this->address4),
            'przipcode' => pSQL($this->zipcode),
            'prtown' => pSQL($this->city),
            'cecountry' => pSQL($this->country),
            'cephonenumber' => pSQL($this->phone),
            'ceemail' => pSQL($this->email),
            'cecompanyname' => pSQL($this->company),
        );

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            return (Db::getInstance()->insert(self::SO_COLISSIMO_TABLE, $params));
        }

        return (Db::getInstance()->autoExecute(_DB_PREFIX_.self::SO_COLISSIMO_TABLE, $params, 'INSERT'));
    }
}
