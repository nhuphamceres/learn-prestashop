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
 * @copyright Copyright (c) 2011-2021 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

class AmazonOrderImportingOrderShipping
{
    protected $mkpId;
    protected $totalPrice;
    protected $taxAmount;

    public function __construct($mkpId, $totalPrice, $taxAmount)
    {
        $this->mkpId = $mkpId;
        $this->totalPrice = $totalPrice;
        $this->taxAmount = $taxAmount;
    }

    public function getPriceTaxIncl()
    {
        return $this->isPriceTaxExcluded() ? ($this->totalPrice + $this->taxAmount) : $this->totalPrice;
    }

    public function getPriceTaxExcl()
    {
        return $this->isPriceTaxExcluded() ? $this->totalPrice : ($this->totalPrice - $this->taxAmount);
    }

    public function getTaxAmount()
    {
        return $this->taxAmount;
    }

    private function isPriceTaxExcluded()
    {
        return in_array($this->mkpId, array(AmazonConstant::MKP_US));
    }
}
