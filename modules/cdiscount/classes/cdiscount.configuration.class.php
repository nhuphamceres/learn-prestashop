<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Common-Services Co., Ltd. is strictly forbidden.
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
 * @package   CDiscount
 * Support by mail:  support.cdiscount@common-services.com
 */

if (!class_exists('CommonConfiguration')) {
    require_once(_PS_MODULE_DIR_ . 'cdiscount/common/configuration.class.php');
}

class CDiscountConfiguration extends CommonConfiguration
{
    public static $module = 'CDISCOUNT';
    public static $configuration_table = 'cdiscount_configuration';

    /**
     * todo: Remove in future because identical with parent. This is added due to outdated CommonConfiguration on other modules (but loaded before)
     * @param string $key
     * @param null $idShopGroup
     * @param null $idShop
     * @return int
     */
    public static function getIdByName($key, $idShopGroup = null, $idShop = null)
    {
        self::setDefinition();
        $configuration = ConfigurationCore::getIdByName($key, $idShopGroup, $idShop);
        self::unsetDefinition();

        return $configuration;
    }
}
