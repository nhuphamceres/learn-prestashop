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
 * @author    Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 *  Support by mail  :  support.mirakl@common-services.com
 */

if (!class_exists('MiraklCart')) {
    class MiraklCart extends Cart
    {
        public $me_products     = null;
        public $me_shipping     = 0;
        public $me_fees         = 0;
        public $marketplace_key = 'mirakl';
        public $has_gift_product = false;

        public $tax_calculation_method = PS_TAX_EXC;

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
         * @param boolean $with_taxes With or without taxes
         * @param integer $type Total type
         *
         * @return float Order total
         */
        public function getMiraklOrderTotal($with_taxes = true, $type = 3, $products = null, $id_carrier = null, $use_cache = true, $keepOrderPrices = false)
        {
            $type = (int)$type;
            if (!in_array($type, array(1, 2, 3, 4, 5, 6, 7, 8))) {
                die(Tools::displayError('no type specified'));
            }

            $this->marketplaceSetTaxCalculationMethod();

            $total_price_tax_incl = 0;
            $total_price_tax_excl = 0;
            $carrier_tax_rate = $this->marketplaceGetCarrierTaxRate();

            foreach ($this->me_products as $product) {
                $product_tax_rate = $this->marketplaceGetTaxRate($product);

                $unit_price_tax_incl = (float)Tools::ps_round($product['price'], 2);
                $unit_price_tax_excl = (float)Tools::ps_round($product['price'] / ((100 + $product_tax_rate) / 100), 2);

                $total_price_tax_incl += ($unit_price_tax_incl * $product['qty']);
                $total_price_tax_excl += ($unit_price_tax_excl * $product['qty']);
            }

            $wrapping_fees_withtaxes = 0;
            $wrapping_fees_wot = 0;
            $total_shipping_tax_incl = (float)Tools::ps_round($this->me_shipping, 2);
            $total_shipping_tax_excl = (float)Tools::ps_round(($this->me_shipping / ((100 + $carrier_tax_rate) / 100)), 2);

            switch ($type) {
                case 1:
                case 8:
                    $amount = ($with_taxes ? $total_price_tax_incl : $total_price_tax_excl);
                    break;
                case 3:
                    $amount = $with_taxes ? ($total_price_tax_incl + $wrapping_fees_withtaxes + $total_shipping_tax_incl) : ($total_price_tax_excl + $wrapping_fees_wot + $total_shipping_tax_excl);
                    break;
                case 2:
                    return (0);
                case 4:
                case 7:
                    $amount = $with_taxes ? $total_price_tax_incl : $total_price_tax_excl;
                    break;
                case 5:
                    $amount = $with_taxes ? $total_shipping_tax_incl : $total_shipping_tax_excl;
                    break;
                case 6:
                    $amount = $with_taxes ? $wrapping_fees_withtaxes : $wrapping_fees_wot;
                    break;
                default:
                    $amount = 0;
            }

            return Tools::ps_round($amount, 2);
        }

        private function marketplaceSetTaxCalculationMethod()
        {
            if ($this->id_customer) {
                $customer = new Customer((int)$this->id_customer);
                $this->tax_calculation_method = !Group::getPriceDisplayMethod((int)$customer->id_default_group);
            } else {
                $this->tax_calculation_method = !Group::getDefaultPriceDisplayMethod();
            }
        }

        public function marketplaceGetCarrierTaxRate()
        {
            $carrier_tax_rate = 0;

            if (!$this->id_carrier) {
                return false;
            }

            $address_type = Configuration::get('PS_TAX_ADDRESS_TYPE');

            if (empty($address_type)) {
                $address_type = 'id_address_delivery';
            }

            $address = new Address($this->{$address_type});

            if (!Validate::isLoadedObject($address)) {
                return false;
            }

            if ($this->tax_calculation_method) {
                // Carrier Taxes
                if (method_exists('Carrier', 'getTaxesRate')) {
                    $carrier = new Carrier($this->id_carrier);

                    if (Validate::isLoadedObject($carrier)) {
                        $carrier_tax_rate = (float)$carrier->getTaxesRate($address);
                    }
                } elseif (method_exists('Tax', 'getCarrierTaxRate')) {
                    $carrier_tax_rate = (float)Tax::getCarrierTaxRate($this->id_carrier, (int)$address->id);
                }
            }

            return $carrier_tax_rate;
        }

        public function marketplaceGetTaxRate($product)
        {
            $product_tax_rate = 0;

            if ($this->tax_calculation_method) {
                if (method_exists('Tax', 'getProductTaxRate')) {
                    $product_tax_rate = (float)Tax::getProductTaxRate((int)$product['id_product'], (int)$product['id_address_delivery']);
                } else {
                    $product_tax_rate = (float)Tax::getApplicableTax((int)$product['id_tax'], $product['tax_rate'], (int)$product['id_address_delivery']);
                }
            }

            return $product_tax_rate;
        }
    }
}
