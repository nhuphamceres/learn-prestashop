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
 * @copyright Copyright (c) 2011-2017 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   CDiscount
  * Support by mail:  support.cdiscount@common-services.com
 */

if (!(defined('PS_ADMIN_DIR') || defined('_PS_ADMIN_DIR_')) && isset($_SERVER['DropBox']) && $_SERVER['DropBox']) {
    require_once(readlink(dirname($_SERVER['SCRIPT_FILENAME']).'/../../../config/config.inc.php'));

    if (version_compare(_PS_VERSION_, '1.5', '<')) {
        require_once(readlink(dirname($_SERVER['SCRIPT_FILENAME']).'/../../../init.php'));
    }
} elseif (!(defined('PS_ADMIN_DIR') || defined('_PS_ADMIN_DIR_'))) {
    /**
     * Warning in Prestashop >= 1.3.6 - 1.4
     * to prevent notice in Tools class in E_STRICT | E_ALL mode :
     * Notice: Undefined index:  HTTP_HOST in /classes/Tools.php on line 71
     */

    require_once(dirname(__FILE__).'/../../../config/config.inc.php');

    if (version_compare(_PS_VERSION_, '1.5', '<')) {
        @require_once(dirname(__FILE__).'/../../../init.php');
    }
}

if (Tools::getValue('debug')) {
    @ini_set('display_errors', 'on');
    @error_reporting(E_ALL | E_STRICT);
}
