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

class FNAC_Cart extends Cart
{
    public $fnacProducts = array();
    public $fnacShipping = 0;
    public $fnacFees = 0;

    /* Modif YB pour gerer les taxes */
    public $taxCalculationMethod = PS_TAX_EXC;


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
    public function getOrderTotal($withTaxes = true, $type = 3, $products = null, $id_carrier = null, $use_cache = true)
    {
        $type = (int)$type;
        if (!in_array($type, array(1, 2, 3, 4, 5, 6, 7, 8))) {
            die(Tools::displayError('no type specified'));
        }

        $this->marketplaceSetTaxCalculationMethod();

        $total_price_tax_incl = 0;
        $total_price_tax_excl = 0;
        $carrier_tax_rate = $this->marketplaceGetCarrierTaxRate();

        foreach ($this->fnacProducts as $product) {
            $product_tax_rate = $this->marketplaceGetTaxRate($product);

            $unit_price_tax_excl = Tools::ps_round($product['price'] / ((100 + $product_tax_rate) / 100), 2);
            $unit_price_tax_incl = (float)$product['price'];

            $total_price_tax_incl += ($unit_price_tax_incl * (int)$product['qty']);
            $total_price_tax_excl += ($unit_price_tax_excl * (int)$product['qty']);
        }

        $total_shipping_tax_excl = (float)Tools::ps_round($this->fnacShipping / ((100 + $carrier_tax_rate) / 100), 2);
        $total_shipping_tax_incl = (float)Tools::ps_round($this->fnacShipping, 2);

        switch ($type) {
            case 1 :
            case 8 :
                $amount = ($withTaxes ? $total_price_tax_incl : $total_price_tax_excl);
                break;
            case 3 :
                $amount = ($withTaxes ? $total_price_tax_incl + $total_shipping_tax_incl : $total_price_tax_excl + $total_shipping_tax_excl); // +  (float)$this->fnacFees   ;
                break;
            case 4 :
                $amount = $withTaxes ? $total_price_tax_incl : $total_price_tax_excl;
                break;
            case 2 :
                return (0); // (float)$this->fnacFees ;
            case 4 :
                $amount = $withTaxes ? $total_price_tax_incl : $total_price_tax_excl;
                break;
            case 5 :
                $amount = $withTaxes ? $total_shipping_tax_incl : $total_shipping_tax_excl;
                break;
            case 6 :
                $amount = 0; // (float)$this->fnacFees ;
                break;
            case 7 :
                $amount = $withTaxes ? $total_price_tax_incl : $total_price_tax_excl;
                break;
            default :
                $amount = 0;
        }

        return Tools::ps_round($amount, 2);
    }

    private function marketplaceSetTaxCalculationMethod()
    {
        if ($this->id_customer) {
            $customer = new Customer((int)($this->id_customer));
            $this->taxCalculationMethod = !Group::getPriceDisplayMethod((int)($customer->id_default_group));
        } else {
            $this->taxCalculationMethod = !Group::getDefaultPriceDisplayMethod();
        }
    }

    private function marketplaceGetCarrierTaxRate()
    {
        $carrier_tax_rate = 0;

        if ($this->taxCalculationMethod) {
            if ($this->id_address_delivery) {
                // If PS1.4>
                if (method_exists('Tax', 'getCarrierTaxRate')) {
                    $carrier_tax_rate = (float)Tax::getCarrierTaxRate($this->id_carrier, (int)$this->id_address_delivery);
                } else {
                    $carrier_tax_rate = 0;
                }
            }
        }

        return ($carrier_tax_rate);
    }

    private function marketplaceGetTaxRate($product)
    {
        $product_tax_rate = 0;

        // Modif YB : ajout de la tva sur les produits
        // Remodif Olivier pour la compatibilite PS 1.3
        if ($this->taxCalculationMethod) {
            if (method_exists('Tax', 'getProductTaxRate')) {
                $product_tax_rate = (float)Tax::getProductTaxRate((int)$product['id_product'], (int)$product['id_address_delivery']);
            } else {
                $product_tax_rate = (float)Tax::getApplicableTax((int)$product['id_tax'], $product['tax_rate'], (int)$product['id_address_delivery']);
            }
        }

        return ($product_tax_rate);
    }
}
