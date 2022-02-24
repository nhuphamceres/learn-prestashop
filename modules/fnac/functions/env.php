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
 * @author    Alexandre D. & Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  contact@common-services.com
 */

@set_time_limit(7200);


$file = new SplFileInfo($_SERVER['SCRIPT_FILENAME']);

if (file_exists(dirname(dirname(dirname($file->getPath()))).'/config/config.inc.php')) {
    require_once dirname(dirname(dirname($file->getPath()))).'/config/config.inc.php';
} elseif (file_exists(dirname($file->getPath()))) {
    // Product page extension
    // Path: /var/www/html/ps1760/admin0217/index.php
    require_once dirname($file->getPath()).'/config/config.inc.php';
} else {
    throw new Exception('[Fnac] Unable to load config.inc.php file.');
}

if (Tools::getValue('debug')) {
    @ini_set('display_errors', 'on');
    @error_reporting(E_ALL | E_STRICT);
}
