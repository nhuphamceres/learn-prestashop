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

// Sep-25-2018: Use only 1 main class for all marketplaces

require_once(dirname(__FILE__).'/env.php');
require_once(dirname(__FILE__).'/../mirakl.php');
require_once(dirname(__FILE__).'/../classes/context.class.php');
require_once(dirname(__FILE__).'/../classes/cronjobs.class.php');

// Sep-25-2018: Use only 1 main class for all marketplaces

class MiraklAjaxTools extends Mirakl
{
    const LF = "\n";

    private $debug;

    public function __construct()
    {
        parent::__construct();
        MiraklContext::restore($this->context);
        $this->init();
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    /**
     * @return bool
     */
    public function init()
    {
        ob_start();

        if (Tools::getValue('debug') == true) {
            $this->debug = true;
        } else {
            $this->debug = (bool)Mirakl::getConfig(Mirakl::CONFIG_DEBUG);
        }

        if ($this->debug) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }

        //  Check Access Tokens
        $token = Tools::getValue('instant_token');

        if (!$token || $token != Mirakl::getConfigGlobalShop(Mirakl::CONFIG_INSTANT_TOKEN)) {
            // die('Wrong Token');
        }

        return (true);
    }

    public function dispatch()
    {
        switch (Tools::getValue('action')) {
            case 'install-cron-jobs':
                // todo: Apply for all actions. Override context by new implementation
                MiraklContext::set();
                return $this->installCronJobs();
            case 'refresh-config-file':
                return $this->refreshConfigFile();
            case 'import-old-profiles':
                return $this->importOldProfiles();
            default:
                die('Wrong way');
        }
    }

    public function installCronJobs()
    {
        $cron_jobs_params = Tools::getValue('prestashop-cronjobs-params');
        $callback = Tools::getValue('callback');

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_shop = (int)Context::getContext()->shop->id;
            $id_shop_group = (int)Context::getContext()->shop->id_shop_group;
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
                // Remove Mirakl cron jobs of current marketplace for specific shop
                $query = 'DELETE FROM `'._DB_PREFIX_.'cronjobs`
                    WHERE `id_shop` = '.pSQL($id_shop).' AND `id_shop_group` = '.pSQL($id_shop_group).' 
                    AND `task` LIKE "%'.pSQL(urlencode('/'.$this->name.'/')).'%"
                    AND `task` LIKE "%'.pSQL(urlencode('selected-mkp='.MiraklMarketplace::getCurrentMarketplace())).'%"';
                Db::getInstance()->execute($query);

                foreach ($cronjobs_lines as $cronjobs_line) {
                    $params = explode("|", trim($cronjobs_line));

                    if (count($params) < 4) {
                        continue;
                    }

                    $title = trim($params[0]);
                    //$lang = trim($params[1]);
                    $frequency = (int)trim($params[2]);
                    $url = trim($params[3]);

                    // Setup the cron
                    $hour = (int)$frequency;
                    $day = (int)-1;
                    $month = (int)-1;
                    $day_of_week = (int)-1;
                    $description = $title;
                    $hours = array();

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
                if ($count && class_exists('CronJobsMirakl')) {
                    $cronJob = new CronJobsMirakl();
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
    }

    /**
     * Delete config file for this shop. To be downloaded again.
     */
    protected function refreshConfigFile()
    {
        // At the beginning, configuration is not saved ---> Cannot use context restoring
        $idShopGroup = Tools::getValue('id_shop_group');
        $idShop = Tools::getValue('id_shop');
        $shopName = Configuration::get('PS_SHOP_NAME', null, $idShopGroup, $idShop);
        $shopKey = md5($shopName);
        $success = true;

        $md5_file = new SplFileInfo(dirname(__FILE__) . '/../mkps/' . $shopKey . '.ini');

        if ($md5_file->isFile() && $md5_file->isReadable() && $md5_file->getSize() >= 10) {
            $success = unlink($md5_file->getRealPath());
        }

        echo json_encode(array(
            'success' => $success,
            'shop_key' => $shopKey
        ));
    }

    /**
     * Get encoded profiles string, and import to current shop + current marketplace.
     */
    protected function importOldProfiles()
    {
        // Marketplace is initialized, by `selected-mkp` key
        // Override context by new implementation
        MiraklContext::set();
        $id_lang = Context::getContext()->language->id;
        $id_shop_group = Shop::getContextShopGroupID(true);
        $id_shop = Shop::getContextShopID(true);

        $callback = Tools::getValue('callback');
        $old_profiles = Tools::getValue('old_profiles');

        // Get saved profiles of current marketplace + current shop context
        $saved_profiles = Mirakl::getConfig(Mirakl::CONFIG_PROFILES, true, $id_lang, $id_shop_group, $id_shop);
        if (!is_array($saved_profiles)) {
            $saved_profiles = array(
                'name'                  => array(),
                'price_rule'            => array(),
                'shipping_rule'         => array(),
                'warranty'              => array(),
                'combinations_attr'     => array(),
                'min_quantity_alert'    => array(),
                'logistic_class'        => array()
            );
        }

        if (!$old_profiles) {
            $response = array('success' => false, 'msg' => $this->l('Empty input.'));
        } else {
            $old_profiles = MiraklTools::tryBase64Decode($old_profiles);
            $old_profiles = MiraklTools::unSerialize($old_profiles);
            if (!is_array($old_profiles)) {
                $response = array('success' => false, 'msg' => $this->l('Broken input.'));
            } else {
                $changed = false;
                foreach ($old_profiles as $key => $data) {
                    if (!in_array($key, array('name', 'price_rule', 'shipping_rule', 'warranty', 'combinations_attr', 'min_quantity_alert', 'logistic_class'))) {
                        continue;
                    }
                    // $data is array for all profiles. Ex: 'name' => array(1 => Profile 1, 2 => Profile 2, _key_ => Master)
                    if (is_array($data)) {
                        foreach ($data as $index => $value) {
                            // Ignore master profile
                            if ('_key_' === $index) {
                                continue;
                            }

                            // Modify name a little.
                            if ('name' === $key && is_string($value)) {
                                $value .= ' (old)';
                            }

                            // Append to saved profiles.
                            $saved_profiles[$key][] = $value;
                            $changed = true;
                        }
                    }
                }

                if (!$changed) {
                    $response = array('success' => false, 'msg' => $this->l('Nothing changes.'));
                } else {
                    // Update profile for current mkp + current store
                    Mirakl::updateConfig(Mirakl::CONFIG_PROFILES, $saved_profiles, true, false, $id_shop_group, $id_shop);
                    $response = array('success' => true, 'msg' => $this->l('Import successfully.'));
                }
            }
        }

        $json = Tools::jsonEncode($response);
        die((string)$callback."($json)");
    }
}

$miraklAjaxTools = new MiraklAjaxTools();
$miraklAjaxTools->dispatch();
