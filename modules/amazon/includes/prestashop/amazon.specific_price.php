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
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

class AmazonSpecificPrice extends SpecificPrice
{
    // Native class does not have such function. Only offer price by quantity.
    // To get all prices, get all possible quantities, then query prices base on them. 
    public static function getAllSpecificPrices($id_product, $id_product_attribute, $id_shop, $id_currency, $id_country, $id_group)
    {
        $prices = array();
        $possibleQuantityThreshold = self::getAllPossibleQuantityThreshold($id_shop, $id_currency, $id_country, $id_group);
        if (is_array($possibleQuantityThreshold)) {
            foreach ($possibleQuantityThreshold as $quantity) {
                $price = self::getSpecificPrice($id_product, $id_shop, $id_currency, $id_country, $id_group, $quantity, $id_product_attribute);
                $prices[] = new AmazonSingleSpecificPrice($price);
            }
        }

        return $prices;
    }

    protected static function getAllPossibleQuantityThreshold($id_shop, $id_currency, $id_country, $id_group)
    {
        $result = array();
        $sql = 'SELECT DISTINCT(`from_quantity`) as `quantity` FROM `' . _DB_PREFIX_ . 'specific_price`
            WHERE `id_shop` ' . self::amazonFormatIntInQuery(0, $id_shop) . ' AND
            `id_currency` ' . self::amazonFormatIntInQuery(0, $id_currency) . ' AND
            `id_country` ' . self::amazonFormatIntInQuery(0, $id_country) . ' AND
            `id_group` ' . self::amazonFormatIntInQuery(0, $id_group) .
            ' ORDER BY `quantity` ASC';
        $queryResult = Db::getInstance()->executeS($sql);
        if ($queryResult && is_array($queryResult)) {
            foreach ($queryResult as $row) {
                $result[] = $row['quantity'];
            }
        }

        return $result;
    }

    /**
     * SpecificPriceCore::formatIntInQuery was not included in PS 1.6.0.9
     *
     * @param [type] $first_value
     * @param [type] $second_value
     * @return void
     */
    protected static function amazonFormatIntInQuery($first_value, $second_value) {
        $first_value = (int)$first_value;
        $second_value = (int)$second_value;
        if ($first_value != $second_value) {
            return 'IN ('.$first_value.', '.$second_value.')';
        } else {
            return ' = '.$first_value;
        }
    }
}
