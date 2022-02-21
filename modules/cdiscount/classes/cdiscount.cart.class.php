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

require_once(_PS_MODULE_DIR_.'cdiscount/common/cart.class.php');

class CDiscountCart extends CommonCart
{
    public $cdProducts = null;
    public $cdShipping = 0;
    public $cdFees     = 0;

    /**
     * This function returns the total cart amount
     *
     * type = 1 : only products
     * type = 2 : only discounts
     * type = 3 : both
     * type = 4 : both but without shipping
     * type = 5 : only shipping
     * type = 6 : only wrapping
     * type = 7 : only products without shipping
     *
     * @param boolean $withTaxes With or without taxes
     * @param integer $type Total type
     * @return float Order total
     */
    public function getOrderTotal($withTaxes = true, $type = 3, $products = null, $id_carrier = null, $use_cache = true, bool $keepOrderPrices = false)
    {
        $type = (int)$type;
        if (!in_array($type, array(1, 2, 3, 4, 5, 6, 7, 8))) {
            die(Tools::displayError('no type specified'));
        }

        $this->marketplaceCalculationMethod();

        $total_price_tax_incl = 0;
        $total_price_tax_excl = 0;
        $carrier_tax_rate = $this->marketplaceGetCarrierTaxRate();

        foreach ($this->cdProducts as $product) {
            $product_tax_rate = $this->marketplaceGetTaxRate($product);

            $unit_price_tax_incl = (float)Tools::ps_round($product['price'] / $product['qty'], 2);
            $unit_price_tax_excl = (float)Tools::ps_round(($product['price'] / $product['qty']) / ((100 + $product_tax_rate) / 100), 2);

            $total_price_tax_incl += ($unit_price_tax_incl * $product['qty']);
            $total_price_tax_excl += ($unit_price_tax_excl * $product['qty']);
        }

        $wrapping_fees_withtaxes = 0;
        $wrapping_fees_wot = 0;

        $total_shipping_tax_excl = (float)Tools::ps_round($this->cdShipping / ((100 + $carrier_tax_rate) / 100), 2);
        $total_shipping_tax_incl = (float)Tools::ps_round($this->cdShipping, 2);

        /*
          // Modif YB : mise en place de TVA sur les frais d'emballage
          // Wrapping Fees
          $wrapping_fees_withtaxes = $this->cdFees;
          $wrapping_fees_tax = new Tax((int)(Configuration::get('PS_GIFT_WRAPPING_TAX')));
          $wrapping_fees_wot = $wrapping_fees_withtaxes / (1 + (((float)($wrapping_fees_tax->rate) / 100)));
          $wrapping_fees_wot = Tools::ps_round($wrapping_fees_wot, 2);
         */

        switch ($type) {
            case 1:
            case 8:
                $amount = ($withTaxes ? $total_price_tax_incl : $total_price_tax_excl);
                break;
            case 3:
                // Modif YB : mise en place de TVA sur les frais d'emballage
                //$amount = ($withTaxes ? $total_price_tax_incl : $total_price_tax_excl) + $this->cdShipping + $this->cdFees ;
                $amount = $withTaxes ? ($total_price_tax_incl + $wrapping_fees_withtaxes + $total_shipping_tax_incl) : ($total_price_tax_excl + $wrapping_fees_wot + $total_shipping_tax_excl);

                break;
            case 4:
                $amount = ($withTaxes ? $total_price_tax_incl : $total_price_tax_excl);
                break;
            case 2:
                return (0);
            case 4:
                $amount = $withTaxes ? $total_price_tax_incl : $total_price_tax_excl;
                break;
            case 5:
                $amount = $withTaxes ? $total_shipping_tax_incl : $total_shipping_tax_excl;
                break;
            case 6:
                $amount = $withTaxes ? $wrapping_fees_withtaxes : $wrapping_fees_wot;
                break;
            case 7:
                $amount = $withTaxes ? $total_price_tax_incl : $total_price_tax_excl;
                break;
            default:
                $amount = 0;
        }

        return Tools::ps_round($amount, 2);
    }
}
