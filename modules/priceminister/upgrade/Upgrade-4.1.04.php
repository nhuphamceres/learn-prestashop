<?php
/**
 * NOTICE OF LICENSE
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
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
 * Support by mail  :  support.priceminister@common-services.com
 */

/**
 * @param PriceMinister $module
 * @return bool
 */
function upgrade_module_4_1_04($module)
{
    return Db::getInstance()->execute(
        'ALTER TABLE `'._DB_PREFIX_.$module::TABLE_PRICEMINISTER_ORDERS.'`
        ADD `prepaidlabelurl` VARCHAR(256) NULL DEFAULT NULL'
    );
}
