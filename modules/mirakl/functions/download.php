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
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 *  Support by mail  :  support.mirakl@common-services.com
 */

require_once(dirname(__FILE__).'/env.php');

$config_tokens = Mirakl::getConfigGlobalMarketplace(Mirakl::CONFIG_PS_TOKEN, true);
if (Tools::getValue('token') !== $config_tokens) {//TODO: Validation - yes module_name exists in env.php
    die('Wrong token...');
}

$filename = Tools::getValue('filename');
$file_directory = explode('/', $filename);
$file_directory = reset($file_directory);

if (!in_array($file_directory, array('update', 'create'))) {
    die('Download directory is not correct...');
}

$xmlpath = dirname(__FILE__).'/../export/'.$file_directory.'/'.basename($filename);

header('Pragma: public');
header('Cache-Control: no-cache');
header('Content-Type: text/xml; charset=utf-8');
header('Content-Disposition: attachment; filename="'.basename($filename).'"');
echo Tools::file_get_contents($xmlpath);
exit;
