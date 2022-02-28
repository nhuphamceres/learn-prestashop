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
 * @author    Artem B, Olivier B., Eric Turcios
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
*/

if (!defined('AMAZON_MARKETPLACE_VERSION')) {
    define('AMAZON_MARKETPLACE_VERSION', '4.0');
}
require_once(dirname(__FILE__).'/../classes/amazon.certificates.class.php');

if (0 && isset($_SERVER['DropBox'])) {
    require_once(dirname(__FILE__).'/../classes/amazon.webservice.dev.php');
} else {
    require_once(dirname(__FILE__).'/../classes/amazon.webservice.prod.php');
}
