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
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 *  Support by mail  :  support.mirakl@common-services.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Sep-25-2018: Use only 1 main class for all marketplaces

require_once(dirname(__FILE__) . '/../mirakl.php');
require_once(dirname(__FILE__) . '/../classes/tools.class.php');
require_once(dirname(__FILE__) . '/../classes/product.class.php');

// Sep-25-2018: Use only 1 main class for all marketplaces

if (!class_exists('MiraklProductTab')) {
    // Sep-25-2018: Use only 1 main class for all marketplaces

    class MiraklExtManager extends Mirakl
    {
        // Sep-25-2018: Share mirakl_product_option for all marketplaces

        public function l($string, $specific = false, $id_lang = null)
        {
            return parent::l($string, basename(__FILE__, '.php'), $id_lang);
        }

        public function marketplaceProductTabContent($params)
        {
            $id_lang = $this->marketplace_id_lang ? $this->marketplace_id_lang : $this->id_lang;
            $id_product = Tools::getValue('id_product', $params['id_product']);
            $defaults = MiraklProduct::getProductOptions($id_product, $id_lang);
            $enableMkps = array_keys($this->externalMkp->availableMkps);

            $view_params = array(
                'id_lang' => $id_lang,
                'images' => $this->images,
                'id_product' => $id_product,
                'module' => $this->name,
                'marketplace' => $this->displayName,
                'logo' => $this->externalMkp->logo,
                'module_url' => $this->url,
                'module_json' => $this->url . 'functions/product_ext.json.php?instant_token=' . Mirakl::getConfigGlobalShop(Mirakl::CONFIG_INSTANT_TOKEN),
                'forceUnavailableChecked' => $defaults['disable'] ? 'checked="checked"' : '',
                'forceInStockChecked' => $defaults['force'] ? 'checked="checked"' : '',
                'extraText' => $defaults['text'],
                'extraTextCount' => (200 - Tools::strlen($defaults['text'])),
                'extraPrice' => ((float)$defaults['price'] ? sprintf('%.02f', $defaults['price']) : ''),
                'shippingDelay' => ($defaults['shipping']),
                'class_success' => $this->ps16x ? 'alert alert-success' : 'conf',
                'enable_mkp_specific_fields' => array_filter(MiraklMarketplace::getAllSpecificFields(), function ($mkp) use ($enableMkps) {
                    return in_array($mkp['name'], $enableMkps);
                }),
                'selected_specific_fields' => json_decode($defaults['mkp_specific_fields'], true),
            );

            return $this->context->smarty->assign($view_params)
                ->fetch($this->path . 'views/templates/admin/catalog/product_tab.tpl');
        }
    }
}
