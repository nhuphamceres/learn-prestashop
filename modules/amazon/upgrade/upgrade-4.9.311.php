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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Add `id_shop` into ps_marketplace_product_action & ps_marketplace_product_option
 * @param Amazon $module
 * @return bool
 */
function upgrade_module_4_9_311($module)
{
    $notSuitableHook = 'displayAdminOrder';
    $newHook = 'displayAdminOrderMain';

    if (version_compare(_PS_VERSION_, '1.7.7', '>=')) {
        $transplantedNewHook = true;
        if (!$module->isRegisteredInHook($newHook)) {
            $transplantedNewHook = $module->registerHook($newHook);
        }

        if ($transplantedNewHook) {
            if ($module->isRegisteredInHook($notSuitableHook)) {
                return $module->registerHook('displayAdminOrder');
            }
        }

        return false;
    }

    return true;
}
