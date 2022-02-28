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
 * @author    Erick Turcios
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

class XmlRestriction
{
    /*Restrictions available for XML Data Types*/
    private static $xmlRestrictions = array(
        'enumeration',
        'fractionDigits',
        'length',
        'maxExclusive',
        'maxInclusive',
        'maxLength',
        'minExclusive',
        'minInclusive',
        'minLength',
        'pattern',
        'totalDigits',
        'whiteSpace'
    );

    /**
     * Checks if a value is a restriction
     * @param type $value
     * @return True if $value is a restriction, False otherwise
     */
    public static function isRestriction($value)
    {
        return in_array($value, self::$xmlRestrictions);
    }
}
