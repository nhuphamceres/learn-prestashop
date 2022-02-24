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
 * @author    Alexandre D. & Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  contact@common-services.com
 */

class FNAC_Order extends Order
{
    public $marketPlaceOrderId = null;
    public $marketPlaceOrderStatus = null;

    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id, $id_lang);

        if ($id) {
            $this->_getMpFields();
        }
    }

    public function add($autodate = true, $nullValues = false, $marketPlaceOrderId = false, $marketPlaceOrderStatus = false)
    {
        $pass = parent::add($autodate);

        $this->marketPlaceOrderId = $marketPlaceOrderId;
        $this->marketPlaceOrderStatus = $marketPlaceOrderStatus;

        $pass = $pass === false ? $pass : $this->_updOrder();

        return $pass;
    }

    public static function checkByMpId($marketPlaceOrderId)
    {
        $sql = '
			SELECT `id_order`, `mp_order_id`, `mp_status`
			FROM `'._DB_PREFIX_.'orders`
			WHERE `mp_order_id` = "'.pSQL($marketPlaceOrderId).'"
			ORDER BY `id_order` DESC';

        if (!$result = Db::getInstance()->ExecuteS($sql)) {
            return (false);
        }

        return ($result[0]['id_order']);
    }

    public function updateMpStatus($marketPlaceStatus)
    {
        $this->marketPlaceOrderStatus = $marketPlaceStatus;

        $sql = 'UPDATE  `'._DB_PREFIX_.'orders`
          set mp_status = "'.pSQL($this->marketPlaceOrderStatus).'"
          where `id_order` = "'.(int)$this->id.'" ;';

        if (!Db::getInstance()->Execute($sql)) {
            return false;
        }

        return (true);
    }

    private function _getMpFields()
    {
        $sql = '
			SELECT `mp_order_id`, `mp_status`
			FROM `'._DB_PREFIX_.'orders`
          	WHERE `id_order` = '.(int)$this->id.'
          	LIMIT 1';

        if ($result = Db::getInstance()->ExecuteS($sql)) {
            $result = array_shift($result);
            $this->marketPlaceOrderId = $result['mp_order_id'];
            $this->marketPlaceOrderStatus = $result['mp_status'];

            return (true);
        } else {
            return (false);
        }
    }

    private function _updOrder()
    {
        $sql = 'UPDATE  `'._DB_PREFIX_.'orders`
          set mp_order_id = "'.pSQL($this->marketPlaceOrderId).'",
              mp_status = "'.pSQL($this->marketPlaceOrderStatus).'"
          where `id_order` = "'.(int)$this->id.'" ;';

        if (!Db::getInstance()->Execute($sql)) {
            return false;
        }

        return (true);
    }
}
