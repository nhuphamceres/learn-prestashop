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

require_once(dirname(__FILE__).'/env.php');
require_once(dirname(__FILE__).'/../cdiscount.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.context.class.php');

$cronjobs = _PS_MODULE_DIR_.'cronjobs/cronjobs.php';

if (file_exists($cronjobs)) {
    require_once($cronjobs);

    class CronJobsCdiscount extends CronJobs
    {
        public function updateWebserviceExt()
        {
            $this->updateWebservice(true);
        }
    }
}

class CDiscountAjaxTools extends CDiscount
{
    const LF = "\n";

    public function __construct()
    {
        parent::__construct();

        CDiscountContext::restore($this->context);
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    public function initCd()
    {
        ob_start();

        if (Tools::getValue('debug') == true) {
            $this->debug = true;
        } else {
            $this->debug = (bool)Configuration::get(parent::KEY.'_DEBUG') ? true : false;
        }

        if ($this->debug) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }

        //  Check Access Tokens
        //
        $token = Tools::getValue('instant_token');

        if (!$token || $token != Configuration::get(parent::KEY.'_INSTANT_TOKEN', null, 0, 0)) {
            die('Wrong Token');
        }

        return (true);
    }

    public function dispatch()
    {
        switch (Tools::getValue('action')) {
            case 'install-cron-jobs':
                die($this->installCronJobs());
            case 'get-size-list':
                $categoryId = Tools::getValue('category_id');
                $modelId = Tools::getValue('marketplace_model_id');
                die($this->getSizeList($categoryId, $modelId));
        }
    }

    public function installCronJobs()
    {
        $pass = false;
        $count = 0;
        $cron_jobs_params = Tools::getValue('prestashop-cronjobs-params');
        $callback = Tools::getValue('callback');

        $this->initCd();

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_shop = (int)$this->context->shop->id;
            $id_shop_group = (int)$this->context->shop->id_shop_group;
        } else {
            $id_shop = 1;
            $id_shop_group = 1;
        }

        $pass = true;
        $count = 0;
        $this->debug = true;

        if (!empty($cron_jobs_params)) {
            $cronjobs_lines = explode("!", $cron_jobs_params);

            if (count($cronjobs_lines)) {
                $query = 'DELETE FROM '._DB_PREFIX_.'cronjobs WHERE `task` LIKE "%'.urlencode('/'.$this->name.'/').'%"';

                if (version_compare(_PS_VERSION_, '1.5', '>=') && Shop::isFeatureActive()) {
                    $query .= ' AND `id_shop`='.(int)$id_shop . ' AND `id_shop_group`='.(int)$id_shop_group;
                }
                
                Db::getInstance()->execute($query);

                foreach ($cronjobs_lines as $cronjobs_line) {
                    $params = explode("|", trim($cronjobs_line));

                    if (count($params) < 4) {
                        continue;
                    }

                    $title = trim($params[0]);
                    $lang = trim($params[1]);
                    $frequency = (int)trim($params[2]);
                    $url = trim($params[3]);

                    // Setup the cron
                    $hour = (int)-1;
                    $day = (int)-1;
                    $month = (int)-1;
                    $day_of_week = (int)-1;
                    $description = $title;
                    $hours = array();

                    if ($frequency > 1) {
                        for ($i = 0; $i < 24; $i += $frequency) {
                            $hours[] = $i;
                        }
                    } elseif ($frequency == -1) {
                        $hours[] = $frequency;
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

                if ($count) {
                    $cronJob = new CronJobsCdiscount();

                    $cronJob->updateWebserviceExt();
                }
            }
        }

        if ($pass) {
            $msg = sprintf('%d %s', $count, $this->l('tasks successfully added to Prestashop Cronjobs module'));
        } else {
            $msg = $this->l('An unexpected error occurs while creating tasks');
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

    public function getSizeList($categoryId, $modelId)
    {
        if ($categoryId && $modelId) {
            require_once(dirname(__FILE__).'/../classes/'.self::MODULE.'.model.class.php');
            require_once(dirname(__FILE__).'/../classes/'.self::MODULE.'.specificfield.class.php');

            if ($models_variation_values = self::getInstanceCDiscountModel()->getModelVariations($categoryId, $modelId)) {
                if (is_array($models_variation_values) && count($models_variation_values)) {
                    foreach ($models_variation_values as $variation_key => $variation_value) {
                        printf('<option>%s</option>'."\n", $variation_value);
                    }
                    return;
                }
            }
        }
        
        printf('<option disabled>%s</option>', $this->l('No Data'));
        return;
    }
}

$cdiscountAjaxTools = new CDiscountAjaxTools();
$cdiscountAjaxTools->Dispatch();
