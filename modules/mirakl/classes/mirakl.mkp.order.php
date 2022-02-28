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
 *
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 *  Support by mail  :  support.mirakl@common-services.com
 */

class MiraklMkpOrder
{
    protected $marketplace;
    // todo: Use internally
    public $priceAlreadyIncludedTax = false;

    protected $priceShipping;

    protected $orderLines = array();

    public function __construct($marketplace, $data)
    {
        $this->marketplace = $marketplace;
        // 95889, 97385, 97469, https://common-services-com.slack.com/archives/D015CKDCL8Y/p1625579703001300
        // In this marketplace, price_unit already includes VAT
        $this->priceAlreadyIncludedTax = in_array($marketplace, MiraklMarketplace::$oiPricesTaxIncludedMkps);

        $this->priceShipping = (float)$data['shipping_price'];

        foreach ($data['order_lines'] as $item) {
            $this->orderLines[] = new MiraklMkpOrderItem($this->priceAlreadyIncludedTax, $item);
        }
    }

    public function getShippingPriceTaxIncl()
    {
        if ($this->priceAlreadyIncludedTax) {
            return $this->priceShipping;
        }

        return array_reduce($this->orderLines, function ($carry, MiraklMkpOrderItem $item) {
            return $carry + $item->getShippingTaxAmount();
        }, $this->priceShipping);
    }

    public function getOrderLines()
    {
        return $this->orderLines;
    }
}

class MiraklMkpOrderItem
{
    protected $priceAlreadyIncludedTax = false;

    protected $quantity;
    protected $priceUnit;
    protected $taxes;
    protected $taxesShipping;

    public function __construct($priceAlreadyIncludedTax, $data)
    {
        $this->priceAlreadyIncludedTax = $priceAlreadyIncludedTax;

        $this->quantity = (int)$data['quantity'];
        $this->priceUnit = (float)$data['price_unit'];
        $this->taxes = new MiraklMkpOrderTaxDefinition($data['taxes']);
        $this->taxesShipping = new MiraklMkpOrderTaxDefinition($data['shipping_taxes']);
    }

    /**
     * @return float
     */
    public function getPriceTaxIncl()
    {
        if ($this->priceAlreadyIncludedTax) {
            return $this->priceUnit;
        }

        return $this->priceUnit + $this->taxes->getAmount() / $this->quantity;
    }

    public function getShippingTaxAmount()
    {
        return $this->taxesShipping->getAmount();
    }
}

class MiraklMkpOrderTaxDefinition
{
    protected $amount;

    public function __construct($data)
    {
        $this->amount = (float)(isset($data['amount']) ? $data['amount'] : 0);
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }
}
