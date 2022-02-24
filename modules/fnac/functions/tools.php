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

require_once(dirname(__FILE__).'/env.php');
require_once(dirname(__FILE__).'/../fnac.php');
require_once(dirname(__FILE__).'/../classes/fnac.context.class.php');

class FnacAjaxTools extends Fnac
{
    const LF = "\n";

    public function __construct()
    {
        parent::__construct();

        FNAC_Context::restore($this->context);
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    public function Init()
    {
        ob_start();

        if (Tools::getValue('debug') == true) {
            $this->debug = true;
        } else {
            $this->debug = (bool)Configuration::get('FNAC_DEBUG') ? true : false;
        }

        if ($this->debug) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }

        //  Check Access Tokens
        //
        $token = Tools::getValue('instant_token');

        $token_cfg = Configuration::get('FNAC_INSTANT_TOKEN');


        if (!$token || $token != Configuration::get('FNAC_INSTANT_TOKEN')) {
            die('Wrong Token');
        }


        return (true);
    }

    public function Dispatch()
    {
        switch (Tools::getValue('action')) {
            case 'install-cron-jobs' :
                die($this->InstallCronJobs());

                break;
        }
    }

    public function InstallCronJobs()
    {
        $pass = false;
        $count = 0;

        $cron_jobs_params = Tools::getValue('prestashop-cronjobs-params');

        $callback = Tools::getValue('callback');

        $this->Init();

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_shop = (int)Context::getContext()->shop->id;
            $id_shop_group = (int)Context::getContext()->shop->id_shop_group;
        } else {
            $id_shop = 1;
            $id_shop_group = 1;
        }


        $count = 0;
        $this->debug = true;

        if (!empty($cron_jobs_params)) {
            $cronjobs_lines = explode('!', $cron_jobs_params);

            if (count($cronjobs_lines)) {
                $query = 'DELETE FROM `'._DB_PREFIX_.'cronjobs`
                    WHERE `task` LIKE "%'.pSQL(urlencode('/'.$this->name.'/')).'%"
                    AND `task` LIKE "%'.pSQL(urlencode('context_key='.Tools::getValue('context_key'))).'%"';

                Db::getInstance()->execute($query);

                foreach ($cronjobs_lines as $cronjobs_line) {
                    $params = explode('|', trim($cronjobs_line));

                    if (count($params) < 4) {
                        continue;
                    }

                    $title = trim($params[0]);
                    $lang = trim($params[1]);
                    $frequency = (int)trim($params[2]);
                    $url = trim($params[3]);

                    // Setup the cron
                    $hour = (int)$frequency;
                    $day = (int)- 1;
                    $month = (int)- 1;
                    $day_of_week = (int)- 1;
                    $description = $title;

                    $query = 'INSERT INTO '._DB_PREFIX_.'cronjobs
						(`description`, `task`, `hour`, `day`, `month`, `day_of_week`, `updated_at`, `one_shot`, `active`, `id_shop`, `id_shop_group`)
						VALUES ("'.pSQL($description).'", "'.pSQL(urlencode($url)).'", '.pSQL($hour).', '.pSQL($day).', '.pSQL($month).', '.pSQL($day_of_week).',
							NULL, FALSE, TRUE, '.(int)$id_shop.', '.(int)$id_shop_group.')';

                    //$pass = Db::getInstance()->execute($query) && $pass;
                    $pass = Db::getInstance()->execute($query);
                    $count++;
                }
            }
        }

        if ($pass) {
            $msg = sprintf('%d %s', $count, $this->l('tasks successfully added to Prestashop Cronjobs module'));
        } else {
            $msg = $this->l('An unexpected error occurs while creating tasks'.$count);
        }

        if ($this->debug) {
            $msg .= trim(ob_get_clean());
        } else {
            ob_get_clean();
        }

        $json = Tools::jsonEncode(
            array(
                'error' => !$pass,
                'count' => $count,
                'output' => $msg,
            ));

        echo (string)$callback.'('.$json.')';
        exit;
    }
}

$FnacAjaxTools = new FnacAjaxTools();
$FnacAjaxTools->Dispatch();
