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
 * @author    debuss-a
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice_suivicolis@common-services.com
 */

/**
 * @param SoNice_SuiviColis $sonice_suivicolis
 * @return bool
 */
function upgrade_module_2_0_01($sonice_suivicolis)
{
    if (!Configuration::get('SONICE_SUIVI_TOKEN')) {
        $module_configuration = Tools::unSerialize(Configuration::get('SONICE_SUIVICOLIS_CONF'));

        if (!array_key_exists('login', $module_configuration) || !array_key_exists('pwd', $module_configuration)) {
            $sonice_suivicolis->displayError(sprintf(
                '[%s] : %s %s %s',
                $sonice_suivicolis->displayName,
                'Login and/or password not found in module configuration.',
                'The security token was not created.',
                'Please save module configuration.'
            ));

            return false;
        }

        return (bool)Configuration::updateValue(
            'SONICE_SUIVI_TOKEN',
            md5($module_configuration['login'].'@%@#$'.$module_configuration['pwd'])
        );
    }

    return true;
}
