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
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice@common-services.com
 */

$file = new SplFileInfo($_SERVER['SCRIPT_FILENAME']);

require_once dirname(dirname(dirname($file->getPath()))).'/config/config.inc.php';
require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviWebService.php');

if (Tools::getValue('token') !== md5(_COOKIE_IV_)) {
    header('HTTP/1.0 401 Unauthorized');
    die('Wrong token');
}

$_debug = true;

if ($_debug) {
    @ini_set('display_errors', 'on');
    @define('_PS_DEBUG_SQL_', true);
    @error_reporting(E_ALL | E_STRICT);
}

ob_start();

$login = Tools::getValue('return_info');
$parcel_ids = '942424242';
$current_shop = new Shop((int)Tools::getValue('id_shop', Configuration::get('PS_SHOP_DEFAULT')));
if (Validate::isLoadedObject($current_shop)) {
    Context::getContext()->shop = $current_shop;
}

if (!(isset($login['login']) && $login['login'] && isset($login['pwd']) && $login['pwd'])) {
    return (false);
}

$suivi = new SoNiceSuiviWebService($parcel_ids, $login['login'], $login['pwd']);
$suivi->call()->setResponse()->parse();

$result = ob_get_clean();

if ($result) {
    $output = $result;
} else {
    $output = '';
}

$json = array(
    'output' => $output,
    'status' => true,
    'label' => $suivi->response->Body->trackResponse->return,
    'request' => '<pre>'.$suivi->origin_request.'</pre>',
    'response' => '<pre>'.$suivi->xmlpp($suivi->origin_response, true).'</pre>'
);

$callback = Tools::getValue('callback');

die($callback.'('.Tools::jsonEncode($json).')');
