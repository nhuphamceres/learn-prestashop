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
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
*/

require_once(_PS_MODULE_DIR_.'/amazon/common/cart.class.php');

class AmazonCart extends CommonCart
{
    /**
     * @var bool
     */
    public $marketplace = true;

    /**
     * @var array
     *  SKU => [
     *      id_product_attribute => ,
     *      tax_rate => ,
     *      id_tax_rules_group => ,
     *      id_tax => ,
     *      id_product => ,
     *      id_address_delivery => ,
     *      cs_items => [
     *          cs_non_customization => [],
     *          OrderItemId => [],          
     *      ]
     *  ],
     */
    public $amazonProducts = null;
    /**
     * @var int
     */
    public $amzShippingPriceTaxIncl = 0;
    public $amzShippingPriceTaxExcl = 0;
    public $amazonShippingTax = 0;
    public $amzShippingTaxRate = 0;
    /**
     * @var null
     */
    public $amazonChannel  = null;

    /**
     * @var null
     */
    public $amazon_order_info = null;

    /**
     * @var null
     */
    public $id_country  = null;

    /**
     * In FBA order, seller country and buyer country can be different. We should apply tax of buyer country
     * This tax will be apply for both carrier + product. So we should calculate 1 time only and store it to $_tax_rate_for_fba
     * @var object('active', 'id_tax_rule', 'id_country')
     */
    public $tax_for_fba;

    // Store tax rate for special FBA order
    private $_tax_rate_for_fba = null;

    public $tax_on_business_order = false;

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
     */

    public function getAmazonOrderTotal($withTaxes = true, $type = 3, $products = null, $id_carrier = null, $use_cache = true, $keepOrderPrices = false)
    {
        $amazonProducts = $this->amazonProducts;
        if (!is_array($amazonProducts) || !count($amazonProducts)) {
            return (false);
        }

        $type = (int)$type;
        if (!in_array($type, array(1, 2, 3, 4, 5, 6, 7, 8))) {
            die(Tools::displayError('no type specified'));
        }

        $this->marketplaceCalculationMethod(Configuration::get('AMAZON_FORCE_TAXES'));
        
        // Shipping prices
        if ($type === 5) {
            return $this->getAmzOrderTotalShipping($withTaxes);
        }

        $total_price_tax_incl = 0;
        $total_price_tax_excl = 0;

        $total_wrapping_tax_incl = 0;
        $total_wrapping_tax_excl = 0;

        $amazon_has_tax = null;

        foreach ($amazonProducts as $sku) {
            $calculateWrappingOnly1 = true;

            foreach ($sku['cs_items'] as $product) {
                $product_tax_rate = 0;
                $quantity = (int)$product['qty'];

                if ($product['amazon_has_tax']) {
                    if ($product['europe']) {
                        $unit_price_tax_excl = $product['price'] - ((float)$product['amazon_item_tax'] / $quantity);
                        $unit_price_tax_incl = $product['price'];
                    } else if(isset($this->amazon_order_info->sales_channel) && (string)$this->amazon_order_info->sales_channel == "Amazon.co.jp") { // Japan exception
                        $unit_price_tax_excl = $product['price'] - ((float)$product['amazon_item_tax'] / $quantity);
                        $unit_price_tax_incl = $product['price'];
                    } else {
                        // why this case: https://support.common-services.com/helpdesk/tickets/36978
                        // seems in USA, the product price in the feed is tax excluded
                        $unit_price_tax_excl = $product['price'];
                        $unit_price_tax_incl = $product['price'] + ((float)$product['amazon_item_tax'] / $quantity);
                    }

                    // 2020-10-06: Calculate shipping tax separately

                    if ($amazon_has_tax === null) {
                        $amazon_has_tax = true;
                    }
                } else {
                    // 2020-10-06: Calculate shipping tax separately
                    $product_tax_rate = $this->getTaxRate('product', $product);

                    $unit_price_tax_excl = $product_tax_rate ? $product['price'] / ((100 + $product_tax_rate) / 100) : $product['price'];
                    $unit_price_tax_incl = (float)$product['price'];
                }

                $total_price_tax_incl += ($unit_price_tax_incl * (int)$product['qty']);
                $total_price_tax_excl += ($unit_price_tax_excl * (int)$product['qty']);

                // Calculate gift wrap
                if (isset($product['giftwrap']) && $calculateWrappingOnly1) {
                    if (!$product['amazon_has_tax']) {
                        $unit_wrapping_tax_excl = $product_tax_rate ? ($product['giftwrap'] / $quantity) / ((100 + $product_tax_rate) / 100) : ($product['giftwrap'] / $quantity);
                        $unit_wrapping_tax_incl = $product['giftwrap'] / $quantity;
                    } else {
                        $unit_wrapping_tax_excl = $product['giftwrap'] - ($product['amazon_giftwrap_tax'] / $quantity);
                        $unit_wrapping_tax_incl = $product['giftwrap'];
                    }
                    $total_wrapping_tax_incl += $unit_wrapping_tax_incl;
                    $total_wrapping_tax_excl += $unit_wrapping_tax_excl;
                    $calculateWrappingOnly1 = false;
                }
            }
        }

        // 2020-10-06: Calculate shipping tax separately

        $wrapping_fees = ($withTaxes ? $total_wrapping_tax_incl : $total_wrapping_tax_excl);

        switch ($type) {
            case 1:
            case 8:
                $amount = ($withTaxes ? $total_price_tax_incl : $total_price_tax_excl);
                break;
            case 3:
                $amount = ($withTaxes ? $total_price_tax_incl : $total_price_tax_excl) + $this->getAmzOrderTotalShipping($withTaxes) + $wrapping_fees;
                break;
            case 4:
                $amount = ($withTaxes ? $total_price_tax_incl : $total_price_tax_excl) + $wrapping_fees;
                break;
            case 2:
                return (0);
            // 2020-10-06: Calculate shipping tax separately
            case 6:
                $amount = $wrapping_fees;
                break;
            case 7:
                $amount = $withTaxes ? $total_price_tax_incl : $total_price_tax_excl;
                break;
            default:
                $amount = 0;
        }

        return Tools::ps_round(max(0, $amount), 2);
    }

    /**
     * @deprecated This function should never used
     * @param $withTaxes
     * @return int|mixed
     */
    protected function getAmzOrderTotalShipping($withTaxes)
    {
        return $withTaxes ? $this->amzShippingPriceTaxIncl : $this->amzShippingPriceTaxExcl;
    }

    /**
     * @param string $type carrier|product
     * @param array|null $product
     * @return float|int
     */
    public function getTaxRate($type, $product)
    {
        if ($this->isTaxRateFBAApplicable()) {
            // Calculate tax for FBA or return cache.
            if (is_null($this->_tax_rate_for_fba)) {
                $tax_rate_for_fba = $this->getTaxRateFBA();
                $this->_tax_rate_for_fba = $tax_rate_for_fba;
            }

            return $this->_tax_rate_for_fba;
        } else {
            // For normal order, calculate normal tax.
            return $this->getTaxRateNormal($type, $product);
        }
    }

    /**
     * Calculate tax rate for special FBA order.
     * Only use this function if isTaxRateFBAApplicable() is true.
     * @return float
     */
    public function getTaxRateFBA()
    {
        $address             = new Address();
        $address->id_country = $this->tax_for_fba->id_country;
        $address->id_state   = 0;
        $address->postcode   = 0;

        $tax_manager = TaxManagerFactory::getManager($address, $this->tax_for_fba->id_tax_rule);
        $tax_calculator = $tax_manager->getTaxCalculator();

        return $tax_calculator->getTotalRate();
    }

    /**
     * @param string $type carrier|product
     * @param array $product
     * @return float|int
     */
    protected function getTaxRateNormal($type, $product)
    {
        if ('carrier' == $type) {
            return parent::marketplaceGetCarrierTaxRate();
        } elseif ('product' == $type) {
            return parent::marketplaceGetTaxRate($product);
        }

        return 0;
    }

    /**
     * Check if this cart is applied special tax for FBA order,
     * and if we can calculate tax manually (by using TaxManagerFactory).
     * @return bool
     */
    public function isTaxRateFBAApplicable()
    {
        return $this->isTaxForFBA() && $this->canCalculateTaxManually();
    }

    public function isTaxForFBA()
    {
        return $this->tax_for_fba->active && $this->tax_for_fba->id_country && $this->tax_for_fba->id_tax_rule;
    }

    protected function canCalculateTaxManually()
    {
        return class_exists('TaxManagerFactory')
            && method_exists('TaxManagerFactory', 'getManager');
    }
}
