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

class AmazonShippingOutgoing
{
    protected static $requiredShippingMethodMkps = array(
        'A13V1IB3VIYZZH',   // Fr
        'A1RKKUPIHCS9HS',   // Es
        'A1PA6795UKMFR9',   // De
        'APJ6JRA9NG5V4',    // It
        'A1F83G8C2ARO7P',   // Uk
        'A39IBJ37TRP1C6',   // Au
    );

    protected $mkpId;
    protected $isRequiredShippingMethod = false;

    private $data;
    private static $defaultCarriers;

    public function __construct($mkpId)
    {
        $this->mkpId = $mkpId;
        $this->isRequiredShippingMethod = in_array($mkpId, self::$requiredShippingMethodMkps);
    }

    public function isRequiredShippingMethod()
    {
        return $this->isRequiredShippingMethod;
    }

    public function getAllCarriersAndMethods()
    {
        $result = array();
        foreach ($this->getAllCarriers() as $carrier) {
            $result[] = array(
                'carrier' => $carrier,
                'carrier_key' => AmazonTools::toKey($carrier),
                'shipping_method' => $this->getAllMethods($carrier),
            );
        }

        return $result;
    }

    public function getAllCarriers()
    {
        if ($this->isRequiredShippingMethod()) {
            return array_unique(array_map(function ($row) {
                return $row['carrier'];
            }, $this->loadDataCache()));
        } else {
            return self::loadCarriersDefaultCache();
        }
    }

    public function getAllMethods($carrier)
    {
        if ($this->isRequiredShippingMethod()) {
            return array_map(function ($filteredRow) {
                return $filteredRow['delivery'];
            }, array_filter($this->loadDataCache(), function ($row) use ($carrier) {
                return $carrier == $row['carrier']  // Get shipping methods of input carrier only
                    && trim($row['delivery']);      // Some carriers don't have any method, remove the empty ones too 
            }));
        } else {
            return array();
        }
    }

    private function loadDataCache()
    {
        if (is_null($this->data)) {
            $this->data = $this->loadData();
        }

        return $this->data;
    }

    private function loadData()
    {
        if (!$this->mkpId || !$this->isRequiredShippingMethod) {
            return array();
        }

        $staticFile = _PS_MODULE_DIR_ . "amazon/settings/carriers/outgoing/shipping_method/$this->mkpId.json";

        if (!file_exists($staticFile) || !is_readable($staticFile)) {
            return array();
        }

        return json_decode(Tools::file_get_contents($staticFile), true);
    }

    public static function loadCarriersDefaultCache()
    {
        if (is_null(self::$defaultCarriers)) {
            self::$defaultCarriers = self::loadCarriersDefault();
        }

        return self::$defaultCarriers;
    }

    private static function loadCarriersDefault()
    {
        return AmazonSettings::getSettings('/settings/carriers/outgoing/amazon_carrier_codes.ini');
    }
}
