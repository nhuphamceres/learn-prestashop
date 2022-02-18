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

require_once(_PS_MODULE_DIR_.'priceminister/common/cart.class.php');

class PSPM_Cart extends CommonCart
{

    public $pmProducts = null;
    public $pmShipping = 0;
    public $pmDate = null;

    /**
     * This function returns the total cart amount
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
    public function getOrderTotal($withTaxes = true, $type = 3, $products = null, $id_carrier = null, $use_cache = true)
    {
        $type = (int)$type;
        if (!in_array($type, array(1, 2, 3, 4, 5, 6, 7, 8))) {
            die(Tools::displayError('no type specified'));
        }

        $this->marketplaceCalculationMethod();

        $total_price_tax_incl = 0;
        $total_price_tax_excl = 0;
        $carrier_tax_rate = $this->marketplaceGetCarrierTaxRate();

        foreach ($this->pmProducts as $product) {
            $product_tax_rate = $this->marketplaceGetTaxRate($product);

            $unit_price = (float)($product['price'] / $product['qty']);

            $unit_price_tax_excl = Tools::ps_round($unit_price / ((100 + $product_tax_rate) / 100), 2);
            $unit_price_tax_incl = (float)$unit_price;

            $total_price_tax_incl += ($unit_price_tax_incl * (int)$product['qty']);
            $total_price_tax_excl += ($unit_price_tax_excl * (int)$product['qty']);
        }

        $total_shipping_tax_excl = (float)Tools::ps_round($this->pmShipping / ((100 + $carrier_tax_rate) / 100), 2);
        $total_shipping_tax_incl = (float)Tools::ps_round($this->pmShipping, 2);

        switch ($type) {
            case 1:
            case 8:
                $amount = ($withTaxes ? $total_price_tax_incl : $total_price_tax_excl);
                break;
            case 3:
                $amount = ($withTaxes ? $total_price_tax_incl + $total_shipping_tax_incl : $total_price_tax_excl + $total_shipping_tax_excl);
                break;
            case 4:
                $amount = $withTaxes ? $total_price_tax_incl : $total_price_tax_excl;
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
                $amount = 0;
                break;
            case 7:
                $amount = $withTaxes ? $total_price_tax_incl : $total_price_tax_excl;
                break;
            default:
                $amount = 0;
        }

        return Tools::ps_round($amount, 2);
    }

    // Oct-18-2018: Already in CommonCart::marketplaceCalculationMethod()
}