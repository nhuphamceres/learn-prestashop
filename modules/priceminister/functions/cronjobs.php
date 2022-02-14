<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 *
 * @author    debuss-a <alexandre@common-services.com>
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
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
     * Notice: Undefined index:  HTTP_HOST in /classes/Tools.php on line 71
     */

    @require_once(dirname(__FILE__).'/../../../config/config.inc.php');

    if (version_compare(_PS_VERSION_, '1.5', '<')) {
        @require_once(dirname(__FILE__).'/../../../init.php');
    }
}
require_once(dirname(__FILE__).'/../priceminister.php');
require_once(dirname(__FILE__).'/../classes/priceminister.context.class.php');

class PrestashopCronJobs extends PriceMinister
{

    const LF = "\n";

    public function __construct()
    {
        parent::__construct();
        PriceMinisterContext::restore($this->context);
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
        $pass = false;
        $count = 0;
        $cron_jobs_params = Tools::getValue('prestashop-cronjobs-params');
        $callback = Tools::getValue('callback');

        $this->init();

        ob_start();

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_shop = (int)Context::getContext()->shop->id;
            $id_shop_group = (int)Context::getContext()->shop->id_shop_group;
        } else {
            $id_shop = 1;
            $id_shop_group = 1;
        }

        $pass = true;
        $count = 0;
        $this->_debug = true;

        if (!empty($cron_jobs_params)) {
            $cronjobs_lines = explode('!', $cron_jobs_params);

            if (count($cronjobs_lines)) {
                $query = 'DELETE FROM '._DB_PREFIX_.'cronjobs WHERE `task` LIKE "%'.urlencode('/'.$this->name.'/').'%"';
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
                    $hours = array();

                    // Setup the cron
                    $hour = (int)$frequency;
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
                        $query = 'INSERT INTO '._DB_PREFIX_.'cronjobs
						(`description`, `task`, `hour`, `day`, `month`, `day_of_week`, `updated_at`, `one_shot`, `active`, `id_shop`, `id_shop_group`)
						VALUES ("'.pSQL($description).'", "'.pSQL(urlencode($url)).'", '.pSQL($hour).', '.pSQL($day).', '.pSQL($month).', '.pSQL($day_of_week).',
							NULL, FALSE, TRUE, '.$id_shop.', '.$id_shop_group.')';

                        $pass = Db::getInstance()->execute($query) && $pass;
                        $count++;
                    }
                }
            }
        }

        if ($pass) {
            $msg = sprintf('%d %s', $count, $this->l('tasks successfully added to Prestashop Cronjobs module', 'cronjobs'));
        } else {
            $msg = $this->l('An unexpected error occurs while creating tasks', 'cronjobs');
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
            )
        );

        echo (string)$callback.'('.$json.')';
        exit;
    }

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
}

$prestashop_cronjobs = new PrestashopCronJobs();
$prestashop_cronjobs->dispatch();