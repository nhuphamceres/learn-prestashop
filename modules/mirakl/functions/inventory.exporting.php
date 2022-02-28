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

require_once(dirname(__FILE__) . '/env.php');
require_once(dirname(__FILE__) . '/../mirakl.php');
require_once(dirname(__FILE__) . '/../classes/context.class.php');
require_once(dirname(__FILE__) . '/../classes/tools.class.php');
require_once(dirname(__FILE__) . '/../classes/product.class.php');
require_once(dirname(__FILE__) . '/../classes/support.class.php');
require_once(dirname(__FILE__) . '/../classes/mirakl.marketplace.php');

class MiraklInventoryExporting extends Mirakl
{
    protected $runModeCreation;
    protected $runModeCron;

    protected $useTax;
    protected $useSpecialPrice;
    protected $onlyActiveOne;

    public function __construct($creationMode)
    {
        parent::__construct();
        $this->runModeCreation = $creationMode;

        $this->useTax = (bool)Mirakl::getConfig(Mirakl::CONFIG_USE_TAXES);
        $this->useSpecialPrice = (bool)Mirakl::getConfig(Mirakl::CONFIG_USE_SPECIALS);
    }

    protected function initRuntimeParameters()
    {
        // Not sure why this complex condition
        if ($this->runModeCreation) {
            // Creation mode, cron always considers active ones
            $this->onlyActiveOne = $this->runModeCron || Tools::getValue('active-only');
        } else {
            // Updating mode, cron always considers all
            $this->onlyActiveOne = !$this->runModeCron && Tools::getValue('active-only');
        }

        return true;
    }

    protected function resolvePrice($productOptions, $profile_price_rule, $profile_shipping_rule, $details, $id_product_attribute)
    {
        $price_override = (!empty($productOptions['price']) && is_numeric((float)$productOptions['price']))
            ? (float)$productOptions['price'] : false;

        if ($price_override) {
            $price = $price_override;
            $price_tax_excl = null;
        } elseif (!empty($profile_price_rule) && is_array($profile_price_rule) && count($profile_price_rule)) {
            $price = $details->getPrice($this->useTax, $id_product_attribute, 2, null, false, false);
            $price = MiraklTools::priceRule($price, $profile_price_rule);
            // add shipping increase/decrease from profile
            $price += $profile_shipping_rule;

            $price_tax_excl = $details->getPrice(false, $id_product_attribute, 2, null, false, false);
            $price_tax_excl = MiraklTools::priceRule($price_tax_excl, $profile_price_rule);
            $price_tax_excl += $profile_shipping_rule;
        } else {
            $price = $details->getPrice($this->useTax, $id_product_attribute, 2, null, false, $this->useSpecialPrice);
            $price_tax_excl = $details->getPrice(false, $id_product_attribute, 2, null, false, false);
        }

        return array('tax_incl' => $price, 'tax_excl' => $price_tax_excl);
    }
}
