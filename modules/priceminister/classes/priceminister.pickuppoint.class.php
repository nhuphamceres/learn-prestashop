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

class PriceMinisterPickupPoint
{

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
    public $address4 = '';
    public $zipcode;
    public $city;
    public $country;
    public $weight = null;
    public $insurance = 0;
    public $tracking_url = null;
    /* Specific So Colissimo */
    public $label_url = null;
    public $exp_number = null;
    public $date_add = '0000-00-00 00:00:00';
    /* Constants */
    public $date_upd = '0000-00-00 00:00:00';
    public $phone;
    public $email;
    public $company = '';

    public static function tableExists($table)
    {
        try {
            $table_exists = Db::getInstance()->ExecuteS('SHOW TABLES LIKE "'._DB_PREFIX_.$table.'"');
        } catch (Exception $ex) {
            return (false);
        }

        if (is_array($table_exists) && count($table_exists)) {
            return (true);
        }

        return (false);
    }

    public function save()
    {
        $result = false;

        switch ($this->pickup_type) {
            case (self::MONDIAL_RELAY_TYPE):
                $result = $this->_saveMondialRelay();
                break;

            case (self::SO_COLISSIMO_TYPE):
                $result = $this->_saveSoColissimo();
                break;
        }

        return ($result);
    }

    private function _saveMondialRelay()
    {
        $params = array(
            'id_customer' => (int)$this->id_customer,
            'id_method' => (int)$this->id_method,
            'id_cart' => (int)$this->id_cart,
            'id_order' => (int)$this->id_order,
            'MR_poids' => (float)$this->weight,
            'MR_insurance' => (int)$this->insurance,
            'MR_Selected_Num' => $this->id,
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
            'date_add' => pSQL(date('Y-m-d H:i:s')),
            'date_upd' => pSQL(date('Y-m-d H:i:s'))
        );

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            return (Db::getInstance()->insert(self::MONDIAL_RELAY_TABLE, $params));
        }

        return (Db::getInstance()->autoExecute(_DB_PREFIX_.self::MONDIAL_RELAY_TABLE, $params, 'INSERT'));
    }

    private function _saveSoColissimo()
    {
        $params = array(
            'id_cart' => $this->id_cart,
            'id_customer' => $this->id_customer,
            'delivery_mode' => 'A2P',
            'prid' => $this->id,
            'prname' => $this->name,
            'pradress1' => $this->address1,
            'pradress2' => $this->address2,
            'pradress3' => $this->address3,
            'pradress4' => $this->address4,
            'przipcode' => $this->zipcode,
            'prtown' => $this->city,
            'cecountry' => $this->country,
            'cephonenumber' => $this->phone,
            'ceemail' => $this->email,
            'cecompanyname' => $this->company,
        );

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            return (Db::getInstance()->insert(self::SO_COLISSIMO_TABLE, $params));
        }

        return (Db::getInstance()->autoExecute(_DB_PREFIX_.self::SO_COLISSIMO_TABLE, $params, 'INSERT'));
    }
}