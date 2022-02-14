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
 * @copyright Copyright (c) 2011-2018 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

require_once(dirname(__FILE__).'/../common/configuration.class.php');


/**
 * No need to check base64 encode.
 * CommonConfiguration::returnValue() already check it.
 */
class PriceMinisterConfiguration extends CommonConfiguration
{
    public static $module = 'PM';
    public static $configuration_table = PriceMinister::TABLE_PRICEMINISTER_CONFIGURATION_COMMON;

    public static function updateGlobalValue($key, $values, $html = false)
    {
        try {
            return self::updateValue($key, $values, $html, 0, 0);
        } catch (PrestaShopDatabaseException $e) {
            return false;
        }
    }

    public static function getGlobalValue($key, $id_lang = null)
    {
        try {
            return self::get($key, $id_lang, 0, 0);
        } catch (PrestaShopDatabaseException $e) {
            return false;
        }
    }
}
