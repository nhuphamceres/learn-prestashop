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
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

class AmazonCronFailedOrder
{
    public $mpOrderId;
    public $purchaseDate;
    public $marketplace;
    public $attempt;
    public $reason;

    protected $mkps = array();
    protected $regions = array();

    public function __construct($mpOrderId, $data)
    {
        $this->mpOrderId = $mpOrderId;
        $this->purchaseDate = $data['purchase_date'];
        $this->marketplace = $data['marketplace'];
        $this->attempt = $data['attempt'];
        $this->reason = $data['reason'];
    }

    public function getMarketplaceCode()
    {
        $idLang = array_search($this->marketplace, $this->getAllMarketplaces());
        $regions = $this->getAllRegions();
        return isset($regions[$idLang]) ? $regions[$idLang] : '';
    }

    public static function getAllCronFailedOrders($mkpIds = array())
    {
        $previousFailedOrders = Configuration::get(AmazonConstant::IMPORT_ORDERS_CRON_FAILED_LIST);
        $previousFailedOrders = json_decode($previousFailedOrders, true);
        $previousFailedOrders = is_array($previousFailedOrders) ? $previousFailedOrders : array();

        if (empty($mkpIds)) {
            return $previousFailedOrders;
        }

        $currentMkpsPreviousFailedOrders = array();
        foreach ($previousFailedOrders as $orderId => $order) {
            if (in_array($order["marketplace"], $mkpIds)) {
                $currentMkpsPreviousFailedOrders[$orderId] = $order;
            }
        }

        return $currentMkpsPreviousFailedOrders;
    }

    /**
     * @return AmazonCronFailedOrder[]
     */
    public static function getAllCronFailedOrderInstances()
    {
        $result = array();
        $previousFailedOrders = self::getAllCronFailedOrders();
        foreach ($previousFailedOrders as $mpOrderId => $previousFailedOrder) {
            $result[] = new AmazonCronFailedOrder($mpOrderId, $previousFailedOrder);
        }

        return $result;
    }

    public static function removeAllCronFailedOrders()
    {
        return Configuration::updateValue(AmazonConstant::IMPORT_ORDERS_CRON_FAILED_LIST, json_encode(array()));
    }

    protected function getAllMarketplaces()
    {
        if (!count($this->mkps)) {
            $this->mkps = AmazonConfiguration::get('MARKETPLACE_ID');
        }

        return $this->mkps;
    }

    protected function getAllRegions()
    {
        if (!count($this->regions)) {
            $this->regions = AmazonConfiguration::get('REGION');
        }

        return $this->regions;
    }
}
