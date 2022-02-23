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

if (isset($_SERVER['DropBox']) && $_SERVER['DropBox']) {
    require_once(readlink(dirname($_SERVER['SCRIPT_FILENAME']).'/../../../config/config.inc.php'));

    if (version_compare(_PS_VERSION_, '1.5', '<')) {
        require_once(readlink(dirname($_SERVER['SCRIPT_FILENAME']).'/../../../init.php'));
    }
} else {
    /**
     * Warning in Prestashop >= 1.3.6 - 1.4
     * to prevent notice in Tools class in E_STRICT | E_ALL mode :
     * Notice: Undefined index:  HTTP_HOST in /classes/SoNiceSuiviTools.php on line 71
     */
    @require_once(dirname(__FILE__).'/../../../config/config.inc.php');

    if (version_compare(_PS_VERSION_, '1.5', '<')) {
        @require_once(dirname(__FILE__).'/../../../init.php');
    }
}

if (Tools::getValue('token') !== md5(_COOKIE_IV_)) {
    header('HTTP/1.0 401 Unauthorized');
    die('Wrong token');
}

require_once(dirname(__FILE__).'/../sonice_suivicolis.php');

class PrestashopCronJobs extends SoNice_SuiviColis
{
    const LF = "\n";

    public function init()
    {
        $this->debug = false;
        if (Tools::getValue('debug') == true) {
            $this->debug = true;
        }

        if ($this->debug) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }

        return (true);
    }

    public function dispatch()
    {
        switch (Tools::getValue('action')) {
            case 'install-cron-jobs':
                die($this->installCronJobs());
        }
    }

    public function installCronJobs()
    {
        $cron_jobs_params = Tools::getValue('prestashop-cronjobs-params');
        $callback = Tools::getValue('callback');

        $this->init();

        ob_start();

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_shop = (int)Tools::getValue('id_shop', Context::getContext()->shop->id);
            Context::getContext()->shop = new Shop($id_shop);

            if (!Validate::isLoadedObject(Context::getContext()->shop)) {
                die($this->l('Unable to load current shop.', 'cronjobs'));
            }

            $id_shop_group = Context::getContext()->shop->id_shop_group;
        } else {
            $id_shop = 1;
            $id_shop_group = 1;
        }

        $pass = true;
        $count = 0;
        $this->debug = true;

        if (!empty($cron_jobs_params)) {
            $cronjobs_lines = explode('!', $cron_jobs_params);

            if (count($cronjobs_lines)) {
                Db::getInstance()->execute(
                    'DELETE FROM `'._DB_PREFIX_.'cronjobs`
                    WHERE `task` LIKE "%'.urlencode('/'.$this->name.'/').'%"
                    AND `id_shop` = '.(int)$id_shop
                );

                foreach ($cronjobs_lines as $cronjobs_line) {
                    $params = explode('|', trim($cronjobs_line));

                    if (count($params) < 3) {
                        continue;
                    }

                    $title = trim($params[0]);
                    $frequency = (int)trim($params[1]);
                    $url = trim($params[2]);

                    // Setup the cron
                    $hour = (int)$frequency;
                    $hours = array();
                    $day = -1;
                    $month = -1;
                    $day_of_week = -1;
                    $description = $title;

                    if ($frequency >= 1) {
                        for ($i = 0; $i < 24; $i += $frequency) {
                            $hours[] = $i;
                        }
                    } else {
                        $hours[] = $hour;
                    }

                    foreach ($hours as $hour) {
                        $query = 'INSERT INTO '._DB_PREFIX_.'cronjobs (
                            `description`,
                            `task`,
                            `hour`,
                            `day`,
                            `month`,
                            `day_of_week`,
                            `updated_at`,
                            `one_shot`,
                            `active`,
                            `id_shop`,
                            `id_shop_group`
                        ) VALUES (
                            "'.pSQL($description).'",
                            "'.pSQL(urlencode($url)).'",
                            '.pSQL($hour).',
                            '.pSQL($day).',
                            '.pSQL($month).',
                            '.pSQL($day_of_week).',
							NULL,
							FALSE,
							TRUE,
							'.$id_shop.',
							'.$id_shop_group.'
						)';

                        $pass = Db::getInstance()->execute($query) && $pass;
                        $count++;
                    }
                }
            }
        }

        if ($pass) {
            $msg = sprintf(
                '%d %s',
                $count,
                $this->l('tasks successfully added to Prestashop Cronjobs module', 'cronjobs')
            );
        } else {
            $msg = $this->l('An unexpected error occurs while creating tasks', 'cronjobs');
        }

        if ($this->debug) {
            $msg .= trim(ob_get_clean());
        } else {
            ob_get_clean();
        }

        $json = Tools::jsonEncode(array(
            'error' => !$pass,
            'count' => $count,
            'output' => $msg,
        ));

        echo (string)$callback.'('.$json.')';
        exit;
    }
}

$prestashop_cronjobs = new PrestashopCronJobs();
$prestashop_cronjobs->dispatch();
