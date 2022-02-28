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
 * @author    Alexandre DEBUSSCHERE <alexandre@common-services.com>
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 *  Support by mail  :  support.mirakl@common-services.com
 */

require_once dirname(__FILE__).'/env.php';
require_once dirname(__FILE__).'/../classes/mirakl.marketplace.php';

$config_tokens = Mirakl::getConfigGlobalMarketplace(Mirakl::CONFIG_PS_TOKEN, true);
if (Tools::getValue('token') !== $config_tokens) {
    die('Wrong token...');
}

$mkp_order_id = Tools::getValue('mkp_order_id');
$mkp = MiraklMarketplace::init();
$api_keys = Tools::unSerialize(Configuration::get('MIRAKL_API_KEY'));

$url = $mkp['endpoint'].'orders/documents/download?api_key='.$api_keys[$mkp['name']].
    '&order_ids='.$mkp_order_id.'&document_codes=SYSTEM_DELIVERY_BILL';

$docs = Tools::file_get_contents($url);
file_put_contents(sys_get_temp_dir().'/mirakl.zip', $docs);

$zip = new ZipArchive;
$res = $zip->open(sys_get_temp_dir().'/mirakl.zip');

if ($res !== true) {
    echo 'Une erreur s\'est produite, impossible de récupérer les fichiers de Mirakl.';
    die;
}

$mirakl_dir = '/mirakl'.$mkp_order_id;

$zip->extractTo(sys_get_temp_dir().$mirakl_dir);
$zip->close();

$dirs = array_filter(scandir(sys_get_temp_dir().$mirakl_dir), function ($dir) {
    return !in_array($dir, array('.', '..'));
});
$dir = reset($dirs);

$pdf = glob(sys_get_temp_dir().$mirakl_dir.'/'.$dir.'/*.pdf');
$pdf = reset($pdf);

header_remove();
header('Content-type: application/pdf');
header('Content-Disposition: inline; filename='.basename($pdf));
header('Cache-Control: public, must-revalidate, max-age=0');
header('Pragma: public');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');

echo Tools::file_get_contents($pdf);
die;
