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
require_once(dirname(__FILE__).'/../classes/cdiscount.tools.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.webservice.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.zip.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.configuration.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.config.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.context.class.php');
require_once(dirname(__FILE__).'/../includes/cdiscount.db.manager.php');

class CDiscountConnectionCheck extends CDiscount
{
    private $callback;

    public function __construct()
    {
        parent::__construct();

        CDiscountContext::restore($this->context);
    }

    public function dispatch()
    {
        $callback = Tools::getValue('callback');

        if (empty($callback) || $callback == '?') {
            $this->callback = 'jsonp_'.time();
        } else {
            $this->callback = $callback;
        }

        $action = Tools::getValue('action');

        $token = Tools::getValue('instant_token');

        if (!$token || $token != Configuration::get(parent::KEY.'_INSTANT_TOKEN', null, 0, 0)) {
            print('Wrong Token');

            if (version_compare(_PS_VERSION_, 1.5, '>=')) {
                $check_duplicate_sql
                    = 'SELECT `name`, `id_shop`, `id_shop_group`, COUNT(*) as count
                FROM `'._DB_PREFIX_.'configuration` WHERE name like "%CDISCOUNT%"
                GROUP BY `name`, `id_shop`,  `id_shop_group`
                HAVING COUNT(*) > 1';

                $results = Db::getInstance()->ExecuteS($check_duplicate_sql);

                if (is_array($results) && count($results)) {
                    echo "\n";
                    echo 'Reason is: '."\n";
                    echo 'Duplicated configuration keys: '."\n";

                    foreach ($results as $result) {
                        echo $result['name'];
                        echo ": ";
                        echo $result['count'];
                        echo "\n";
                    }
                }
            }

            die;
        }

        switch ($action) {
            case 'configuration-minimum':
                Configuration::updateValue(parent::KEY.'_MININUM', 1);
                break;
            case 'configuration-normal':
                Configuration::updateValue(parent::KEY.'_MININUM', 0);
                break;
            case 'check':
                $this->check();
                break;
            case 'php-info':
                $this->phpInfo();
                break;
            case 'prestashop-info':
                $this->prestashopInfo();
                break;
            case 'support_zip_file':
                $this->downloadSupportZipFile();
                break;
            case 'mode-dev':
                $this->prestashopModeDev();
                break;
            default:
                $this->dieAndAlert('Missing parameter, nothing to do !');
        }
    }

    public function prestashopInfo($return_data = false)
    {
        $header_errors = ob_get_clean();

        if (version_compare(_PS_VERSION_, 1.5, '>=')) {
            $sort = 'ORDER by `name`,`id_shop`';
            $ps15 = true;
        } else {
            $sort = 'ORDER by `name`';
            $ps15 = false;
        }

        if (defined('Carrier::ALL_CARRIERS')) {
            $all_carriers = Carrier::ALL_CARRIERS;
        } elseif (defined('ALL_CARRIERS')) {
            $all_carriers = ALL_CARRIERS;
        } else {
            $all_carriers = 5;
        }

        $carriers = Carrier::getCarriers($this->id_lang, false, false, false, null, $all_carriers);

        $contentR = array(
            '<h1>Prestashop</h1>',
            'Version: '._PS_VERSION_,
            'Module: '.sprintf('%s/%s', $this->name, $this->version),
            '',
            'Live Configuration Fields: '.Tools::getValue('fields'),
            'Max Input Vars: '.@ini_get('max_input_vars').'/'.@get_cfg_var('max_input_vars'),
            'Memory Limit: '.@ini_get('memory_limit').'/'.@get_cfg_var('memory_limit'),
            'Expert Mode: '.((bool)Configuration::get(self::KEY.'_EXPERT_MODE') ? 'Yes' : 'No'),
            '',
        );

        // Overrides
        $isUsingOverride = CDiscountToolsR::isUsingOverride();
        $contentR[] = 'Running Overrides: ' . ($isUsingOverride ? 'Yes' : 'No');
        if ($isUsingOverride) {
            $overrides = CDiscountToolsR::getOverrideClasses();
            if (is_array($overrides) && count($overrides)) {
                $contentR[] = 'Overrides:';
                $contentR = array_merge($contentR, $overrides);
            }
        }
        $contentR[] = '';
        
        $content = implode("\n", $contentR);

        if (version_compare(_PS_VERSION_, 1.5, '>=')) {
            $check_duplicate_sql
                = 'SELECT `name`, `id_shop`, `id_shop_group`, COUNT(*) as count
                FROM `'._DB_PREFIX_.'configuration` WHERE name like "%CDISCOUNT%"
                GROUP BY `name`, `id_shop`,  `id_shop_group`
                HAVING COUNT(*) > 1';

            $results = Db::getInstance()->ExecuteS($check_duplicate_sql);

            if (is_array($results) && count($results)) {
                $content .= "\n";
                $content .= 'Duplicated configuration keys: '."\n";

                foreach ($results as $result) {
                    $content .= $result['name'];
                    $content .= ": ";
                    $content .= $result['count'];
                    $content .= "\n";
                }
            }
        }

        $content .= $this->getRecentOrders();

        $content .= 'Catalog: '."\n";
        $content .= sprintf('%-58s : <b>%s</b>'."\n", 'Categories', Db::getInstance()->getValue('SELECT count(`id_category`) as count FROM `'._DB_PREFIX_.'category`'));
        $content .= sprintf('%-58s : <b>%s</b>'."\n", 'Products', Db::getInstance()->getValue('SELECT count(`id_product`) as count FROM `'._DB_PREFIX_.'product`'));
        $content .= sprintf('%-58s : <b>%s</b>'."\n", 'Combinations', Db::getInstance()->getValue('SELECT count(`id_product`) as count FROM `'._DB_PREFIX_.'product_attribute`'));
        $content .= sprintf('%-58s : <b>%s</b>'."\n", 'Attributes', Db::getInstance()->getValue('SELECT count(`id_attribute`) as count FROM `'._DB_PREFIX_.'attribute`'));
        $content .= sprintf('%-58s : <b>%s</b>'."\n", 'Features', Db::getInstance()->getValue('SELECT count(`id_feature_value`) as count FROM `'._DB_PREFIX_.'feature_value`'));
        $content .= sprintf('%-58s : <b>%s</b>'."\n", 'Specific Price', Db::getInstance()->getValue('SELECT count(`id_specific_price`) as count FROM `'._DB_PREFIX_.'specific_price`'));

        $orders_states = OrderState::getOrderStates($this->id_lang);
        $content .= "\n";
        $content .= 'OrderStates: '."\n";

        foreach ($orders_states as $key => $orders_state) {
            if ($key == 0) {
                $content .= implode(', ', array_keys($orders_state))."\n";
            }
            $content .= implode(', ', $orders_state)."\n";
        }

        $content .= "\n";
        $content .= 'Carriers'."\n";

        if (is_array($carriers) && count($carriers)) {
            foreach ($carriers as $key => $carrier) {
                if ($key == 0) {
                    $content .= implode(', ', array_keys($carrier))."\n";
                }
                $content .= implode(', ', $carrier)."\n";
            }
        }

        $content .= $this->getConfiguration($sort, $ps15);

        $models = $this->loadModels();
        $profiles = $this->loadProfiles();
        $categories = CDiscountConfiguration::get('categories');

        $content .= "\n";
        $content .= 'CDiscount Categories: '."\n";
        $content .= print_r($categories, true);

        $content .= "\n";
        $content .= 'CDiscount Profiles: '."\n";
        $content .= print_r($profiles, true);

        $content .= "\n";
        $content .= 'CDiscount Models: '."\n";
        $content .= print_r($models, true);

        $content .= $header_errors;

        if ($return_data) {
            return $content;
        } else {
            CommonTools::pre(array($content));
            die;
        }
    }

    public function phpInfo($return_data = false)
    {
        $content = '';
        $header_errors = ob_get_clean();
        ob_start();
        phpinfo(INFO_ALL & ~INFO_CREDITS & ~INFO_LICENSE & ~INFO_ENVIRONMENT & ~INFO_VARIABLES);
        $php_info = ob_get_clean();
        $php_info = preg_replace('/(a:link.*)|(body, td, th, h1, h2.*)|(img.*)/', '', $php_info);

        $content .= '</pre>'."\n\n";

        $content .= '<h1>PHP</h1>'."\n";
        $content .= '<div class="phpinfo">';
        $content .= $php_info;
        $content .= '</div>';

        $content .= $header_errors;

        if ($return_data) {
            return $content;
        } else {
            die($content);
        }
    }

    /**
     * todo: Create zip in root and remove /support folder
     * Create and serve support zip file
     */
    public function downloadSupportZipFile()
    {
        $screen_shot = Tools::getValue('screenShot');
        $includeScreenshot = (bool)$screen_shot;

        $file_prefix        = CDiscountTools::getFriendlyUrl(Configuration::get('PS_SHOP_NAME'));
        $ps_info_file       = sprintf('%ssupport/%s-ps-info.txt', $this->path, $file_prefix);
        $php_info_file      = sprintf('%ssupport/%s-php-info.html', $this->path, $file_prefix);
        $screen_shot_file   = sprintf('%ssupport/%s-screen-shot.png', $this->path, $file_prefix);
        $zip_file_path      = sprintf('%ssupport/%s-support.zip', $this->path, $file_prefix);

        file_put_contents($ps_info_file, $this->prestashopInfo(true));
        file_put_contents($php_info_file, $this->phpInfo(true));
        if ($includeScreenshot) {
            file_put_contents(
                $screen_shot_file,
                base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $screen_shot)) //TODO: Validation: Use to evaluate base64 encoded values, required
            );
        }

        @unlink($zip_file_path);

        // To create zip with relative path
        chdir(sprintf('%s/support', $this->path));
        $zip = new CdiscountZip();
        $zipContent = array(basename($ps_info_file), basename($php_info_file));
        if ($includeScreenshot) {
            $zipContent[] = basename($screen_shot_file);
        }
        $zip->createZip($zip_file_path, $zipContent);

        if (file_exists($zip_file_path)) {
            header('Content-Type: application/octet-stream', true);
            header('Content-Disposition: attachment; filename='.$file_prefix.'-support.zip', true);
            echo Tools::file_get_contents($zip_file_path);
        } else {
            echo 'An error occurred...';
        }

        // Delete created files for security reasons
        @unlink($ps_info_file);
        @unlink($php_info_file);
        @unlink($screen_shot_file);
        @unlink($zip_file_path);

        exit;
    }

    public function prestashopModeDev()
    {
        $message = null;
        $new_state = Tools::getValue('status');
        $new_state_text = !(bool)$new_state ? 'false' : 'true';

        if ($new_state !== '0' && $new_state !== '1') {
            die('Target status unknown');
        }

        if (!defined('_PS_CONFIG_DIR_')) {
            define('_PS_CONFIG_DIR_', _PS_ROOT_DIR_.'/config/');
        }

        $defines_inc_php = _PS_CONFIG_DIR_.'defines.inc.php';
        $defines_inc_php_bak = _PS_CONFIG_DIR_.'defines.inc.php.bak';

        if (!file_exists($defines_inc_php) || !is_writable($defines_inc_php)) {
            die('File doesnt exists or is not writeable');
        }

        if (!($md5_orig = md5_file($defines_inc_php))) {
            die(sprintf('Unable to generate md5 of file: %s', $defines_inc_php));
        }

        if (!CDiscountTools::copy($defines_inc_php, $defines_inc_php_bak)) {
            die(sprintf('Unable to create a backup (from %s to %s)', $defines_inc_php, $defines_inc_php_bak));
        }

        if (!($md5_dest = md5_file($defines_inc_php_bak))) {
            die(sprintf('Unable to generate md5 of file: %s', $defines_inc_php_bak));
        }

        if (!Tools::strlen($md5_dest) || $md5_orig != $md5_dest) {
            die('md5sum mismatch, operation aborted');
        }

        $defines_inc_contents = CDiscountTools::file_get_contents($defines_inc_php); //TODO: VALIDATION - Malfunctions with CDiscountTools::file_get_contents.

        if (!Tools::strlen($defines_inc_php)) {
            die('Unable to get file contents, operation aborted');
        }

        if (md5($defines_inc_contents) != $md5_dest) {
            die('md5sum mismatch, operation aborted');
        }

        $defines_inc_contents_out = preg_replace('/(_PS_MODE_DEV_[\"\'][\s,]*)(true|false|TRUE|FALSE)/', '$1'.$new_state_text, $defines_inc_contents);

        $length_diff = abs(Tools::strlen($defines_inc_contents) - Tools::strlen($defines_inc_contents_out));

        if ($length_diff > 1) {
            die('messup, operation aborted');
        }

        if (!file_put_contents($defines_inc_php, $defines_inc_contents_out)) {
            if (!CDiscountTools::copy($defines_inc_php_bak, $defines_inc_php)) {
                die('/!\\ huge trouble: operation failed, backup restore failed too !');
            } else {
                die('operation failed backup restored');
            }
        } else {
            $message = sprintf('_PS_MODE_DEV_ switched to <b>%s</b> with sucess', !(bool)$new_state ? 'Off' : 'On');
        }

        $json = Tools::jsonEncode(array('status' => (bool)$new_state, 'message' => $message));

        echo (string)$this->callback.'('.$json.')';
        die;
    }

    private function check($debug = false)
    {
        $username = urldecode(Tools::getValue('username'));
        $password = urldecode(Tools::getValue('password'));
        $production = !Tools::getValue('preprod');

        if (Tools::getValue('debug') || Configuration::get(parent::KEY.'_DEBUG')) {
            $debug = true;
        }
        $dev_mode = (bool)Configuration::get(self::KEY.'_DEV_MODE');

        if ($debug) {
            ob_start();
        }

        if (empty($username) || empty($password)) {
            $this->dieAndAlert($this->l('Please enter a username and password first'));
            die;
        }
        CDiscountConfig::removeCache();

        $marketplace = new CDiscountWebservice($username, $password, $production, $debug, $dev_mode);

        $result = $marketplace->getToken($debug);

        if (is_array($result) && isset($result['Token']) && !empty($result['Token'])) {
            Configuration::updateValue(parent::KEY.'_TOKEN', $result['Token']);
            Configuration::updateValue(parent::KEY.'_TOKEN_VALIDITY', $result['Validity']);

            $message = sprintf($this->l('Connection test successfull, %s returned security Token: %s'), parent::NAME, $result['Token']);
            $error = false;
        } else {
            $message = $this->l('Failed to connect to').' '.parent::NAME;
            $error = true;
        }

        if ($debug) {
            $message .= sprintf('<br /><pre>%s</pre>', ob_get_clean());
            $message .= sprintf('<br /><pre>%s</pre>', nl2br(print_r($result, true)));
        }

        $json = Tools::jsonEncode(array('message' => $message, 'error' => $error));

        echo (string)$this->callback.'('.$json.')';
        die;
    }

    protected function getRecentOrders()
    {
        $content = array();

        $mpOrders = _DB_PREFIX_ . CDiscountDBManager::TABLE_MARKETPLACE_ORDERS;
        if (CommonTools::tableExists($mpOrders)) {
            $results = Db::getInstance()->ExecuteS('SELECT * FROM `' . $mpOrders . '` ORDER BY `id_order` DESC LIMIT 10');
            $content[] = 'Last 10 Orders:';

            if (is_array($results) && count($results) > 0) {
                $columns = implode(',', array_keys(reset($results)));
                $content[] = print_r($columns, true);
                foreach ($results as $result) {
                    $values = implode(',', $result);
                    $content[] = print_r($values, true);
                }
            }
        } else {
            $content[] = "/!\\ Missing table " . $mpOrders;
        }

        return "\n" . implode("\n", $content) . "\n";
    }

    protected function getConfiguration($sort, $ps15)
    {
        $dbConfigs = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'configuration` 
            WHERE `name` LIKE "PS_%" OR `name` LIKE "CDISCOUNT_%" ' . $sort
        );
        $ps_configuration = array('Configuration:');
        $ignoreConfigs = array('KEY', 'EMAIL', 'PASSWORD', 'PASSWD');
        $sensitiveConfigs = array('CDISCOUNT_PASSWORD');

        foreach ($dbConfigs as $dbConfig) {
            $configName = $dbConfig['name'];
            $configValue = $dbConfig['value'];

            $ignore = false;
            $sensitive = false;
            // Ignore configuration
            foreach ($ignoreConfigs as $ignoreConfig) {
                if (strpos($configName, $ignoreConfig) !== false) {
                    $ignore = true;
                    break;
                }
            }

            // Sensitive configuration
            foreach ($sensitiveConfigs as $sensitiveConfig) {
                if (strpos($configName, $sensitiveConfig) !== false) {
                    $sensitive = true;
                    break;
                }
            }

            if ($ignore && !$sensitive) {
                continue;
            }

            // Show normal or sensitive configuration
            if ($sensitive) {
                $value = base64_encode("$configName::$configValue");    // TODO Validation: Requires to show configuration
            } else {
                if (base64_encode(base64_decode($configValue, true)) === $configValue) {    // TODO Validation: Requires to validate encoded configuration
                    $value = base64_decode($configValue, true); // TODO Validation: Requires to decode configuration
                } else {
                    $value = $configValue;
                }
                if (@serialize(@unserialize($value)) == $value) {
                    $value = '<div class="print_r">' . print_r(unserialize($value), true) . '</div>';
                } else {
                    $value = Tools::strlen($configValue) > 128 ? Tools::substr($configValue, 0, 128) . '...' : $configValue;
                }
            }

            if ($ps15) {
                $ps_configuration[] = sprintf('%-50s %03d %03d : %s', $configName, $dbConfig['id_shop'], $dbConfig['id_shop_group'], $value);
            } else {
                $ps_configuration[] = sprintf('%-50s : %s', $configName, $value);
            }
        }

        return "\n" . implode("\n", $ps_configuration) . "\n";
    }

    private function dieAndAlert($msg)
    {
        $json = Tools::jsonEncode(array('alert' => $msg));

        echo (string)$this->callback.'('.$json.')';
        die;
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }
}

$marketplaceConnectionCheck = new CDiscountConnectionCheck;
$marketplaceConnectionCheck->dispatch();
