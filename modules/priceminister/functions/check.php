<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
 * Contact by Email :  support.priceminister@common-services.com
 */

$file = new SplFileInfo($_SERVER['SCRIPT_FILENAME']);

require_once dirname(dirname(dirname($file->getPath()))).'/config/config.inc.php';
require_once dirname(dirname(dirname($file->getPath()))).'/init.php';

require_once(dirname(__FILE__).'/../priceminister.php');
require_once(dirname(__FILE__).'/../classes/priceminister.api.webservices.php');
require_once(dirname(__FILE__).'/../classes/priceminister.api.wallet.php');
require_once(dirname(__FILE__).'/../classes/priceminister.tools.class.php');

class PriceMinisterConnexionCheck extends PriceMinister
{

    public function __construct()
    {
        $this->name = 'priceminister';

        parent::__construct();
        parent::loadGeneralModuleConfig();

        require_once(dirname(__FILE__).'/../classes/priceminister.context.class.php');

        PriceMinisterContext::restore($this->context, Validate::isLoadedObject($this->context->shop) ? $this->context->shop : null);
        $this->test_mode = unserialize(Configuration::get(PriceMinister::CONFIG_PM_CREDENTIALS));
        $this->test_mode = isset($this->test_mode['test']) && $this->test_mode['test'];
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return parent::l($string, basename(__FILE__, '.php'), $id_lang);
    }

    public function dispatch()
    {
        ob_start();

        $token = Tools::getValue('instant_token');

        if (!$token || $token != Configuration::get('PM_INSTANT_TOKEN', null, 0, 0)) {
            die('Wrong Token');
        }

        switch (Tools::getValue('action')) {
            case 'check' :
                $this->check();
                break;
            case 'php-info' :
                $this->PHP_Info();
                break;
            case 'prestashop-info' :
                $this->Prestashop_Info();
                break;
        }
    }

    public function check()
    {
        $pm_debug = Configuration::get(PriceMinister::CONFIG_PM_DEBUG);

        if ($pm_debug || Tools::getValue('debug')) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }
        // PM Configuration
        //
        $config = PriceMinisterTools::Auth(Tools::getValue('login'), Tools::getValue('token'));
        $config['output'] = 'json';
        $config['output_type'] = 'pair';

        $params = array();

        $wallet = new PM_Wallet($config);
        $result = $wallet->getoperations($params);

        $json = PriceMinisterTools::jsonDecode($result);

        if ($pm_debug) {
            echo ob_get_clean();
        } else {
            ob_get_clean();
        }

        if (isset($this->test_mode) && (int)$this->test_mode) {
            die('<span class="connectivity-ok">'.$this->l('Test Mode, assuming the Connexion is Ok !').'</span>');
        }

        if (isset($json->error->message)) {
            print ('<span class="connectivity-error">Connexion to Rakuten France: ERROR !</span><br />');
            die($json->error->message);
        } elseif (!isset($json->response)) {
            print ('<span class="connectivity-error">Connexion to Rakuten France: ERROR !</span><br />');
            die(nl2br(print_r($json, true)));
        } else {
            die('<span class="connectivity-ok">Connexion to Rakuten France: OK !</span>');
        }
    }

    public function PHP_Info()
    {
        $header_errors = ob_get_clean();
        ob_start();
        phpinfo(INFO_ALL & ~INFO_CREDITS & ~INFO_LICENSE & ~INFO_ENVIRONMENT & ~INFO_VARIABLES);
        $phpinfos = ob_get_clean();

        $phpinfos = preg_replace('/(a:link.*)|(body, td, th, h1, h2.*)|(img.*)/', '', $phpinfos);

        echo '</pre>'."\n\n";

        echo '<h1>PHP</h1>'."\n";
        echo '<div class="phpinfo">';
        echo $phpinfos;
        echo '</div>';

        echo $header_errors;
        die;
    }

    public function Prestashop_Info()
    {
        $header_errors = ob_get_clean();

        if (version_compare(_PS_VERSION_, 1.5, '>=')) {
            $sort = 'ORDER by `name`,`id_shop`';
            $ps15 = true;
        } else {
            $sort = 'ORDER by `name`';
            $ps15 = false;
        }

        $results = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'configuration` WHERE `name` LIKE "PS_%" OR `name` LIKE "PM_%" '.$sort);

        $ps_configuration = null;

        foreach ($results as $result) {
            if (strpos($result['name'], 'KEY') || strpos($result['name'], 'EMAIL') || strpos($result['name'], 'PASSWORD') || strpos($result['name'], 'PASSWD') || strpos($result['name'], 'CONTEXT_DATA')) {
                continue;
            }

            $value = $result['value'];

            if (PriceMinisterTools::base64Encode(PriceMinisterTools::base64Decode($value, true)) === $value) {
                $value = PriceMinisterTools::base64Decode($value, true);
            } else {
                $value = $result['value'];
            }

            if (@serialize(@unserialize($value)) == $value) {
                $value = '<div class="print_r">'.print_r(unserialize($value), true).'</div>';
            } else {
                $value = Tools::strlen($result['value']) > 128 ? Tools::substr($result['value'], 0, 128).'...' : $result['value'];
            }

            if ($ps15) {
                $ps_configuration .= sprintf('%-50s %03d %03d : %s'."\n", $result['name'], $result['id_shop'], $result['id_shop_group'], $value);
            } else {
                $ps_configuration .= sprintf('%-50s : %s'."\n", $result['name'], $value);
            }
        }

        $output = array(
            '<h1>Prestashop</h1>',
            '<pre>',
            'Version: ' . _PS_VERSION_,
            'Module: ' . sprintf('%s', $this->version),
            "\n",
            'Catalog:',
            sprintf('%-58s : <b>%s</b>', 'Categories', Db::getInstance()->getValue('SELECT count(`id_category`) as count FROM `'._DB_PREFIX_.'category`')),
            sprintf('%-58s : <b>%s</b>', 'Products', Db::getInstance()->getValue('SELECT count(`id_product`) as count FROM `'._DB_PREFIX_.'product`')),
            sprintf('%-58s : <b>%s</b>', 'Attributes', Db::getInstance()->getValue('SELECT count(`id_attribute`) as count FROM `'._DB_PREFIX_.'attribute`')),
            sprintf('%-58s : <b>%s</b>', 'Features', Db::getInstance()->getValue('SELECT count(`id_feature_value`) as count FROM `'._DB_PREFIX_.'feature_value`')),
            "\n",
        );
        $overrides = $this->psOverride();
        $output[] = 'Running Overrides: ' . ($overrides['has_override'] ? 'Yes' : 'No');
        $output = array_merge($output, $overrides['overrides'], array("\n"));
        echo implode("\n", $output);

        // MODEL
        $models = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'priceminister_model` ORDER BY `model_id`');

        echo "\n";
        echo 'Models: '."\n";
        echo '<table class="table-information">';
        printf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            'model_id',
            'field_group',
            'field_name',
            'field_idx',
            'field_value',
            'field_multiple'
        );
        if (is_array($models) && count($models)) {
            foreach ($models as $model) {
                printf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    $model['model_id'],
                    $model['field_group'],
                    $model['field_name'],
                    $model['field_idx'],
                    $model['field_value'],
                    $model['field_multiple']
                );
            }
        } else {
            echo 'N/A';
        }
        echo '</table>';

        // PROFILES
        $profiles = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'pm_configuration` ORDER BY `conf_id`');

        echo "\n";
        echo 'Profiles: '."\n";
        echo '<table class="table-information">';
        printf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            'conf_id',
            'conf_type',
            'field_group',
            'field_name',
            'field_idx',
            'field_value',
            'field_multiple'
        );
        if (is_array($profiles) && count($profiles)) {
            foreach ($profiles as $profile) {
                printf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    $profile['conf_id'],
                    $profile['conf_type'],
                    $profile['field_group'],
                    $profile['field_name'],
                    $profile['field_idx'],
                    $profile['field_value'],
                    $profile['field_multiple']
                );
            }
        } else {
            echo 'N/A';
        }
        echo '</table>';

        // MAPPING
        $mappings = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'priceminister_mappings` ORDER BY `id_pm_mapping`');

        echo "\n";
        echo 'Mappings: '."\n";
        echo '<table class="table-information">';
        printf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            'id_pm_mapping',
            'id_prestashop',
            'id_priceminister',
            'type',
            'default_value'
        );
        if (is_array($mappings) && count($mappings)) {
            foreach ($mappings as $mapping) {
                printf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    $mapping['id_pm_mapping'],
                    $mapping['id_prestashop'],
                    $mapping['id_priceminister'],
                    $mapping['type'],
                    $mapping['default_value']
                );
            }
        } else {
            echo 'N/A';
        }
        echo '</table>';

        // MAPPING DET
        $mappings = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'priceminister_mappings_det` ORDER BY `id_pm_mapping`');

        echo "\n";
        echo 'Mappings DET: '."\n";
        echo '<table class="table-information">';
        printf(
            '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
            'id_pm_mapping',
            'ps_value',
            'pm_value'
        );
        if (is_array($mappings) && count($mappings)) {
            foreach ($mappings as $mapping) {
                printf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
                    $mapping['id_pm_mapping'],
                    $mapping['ps_value'],
                    $mapping['pm_value']
                );
            }
        } else {
            echo 'N/A';
        }
        echo '</table>';

        // REPRICING
        $mappings = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'priceminister_repricing` ORDER BY `id_repricing`');

        echo "\n";
        echo 'Repricing: '."\n";
        echo '<table class="table-information">';
        printf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            'id_repricing',
            'name',
            'active',
            'aggressiveness',
            'base',
            'limit',
            'delta'
        );
        if (is_array($mappings) && count($mappings)) {
            foreach ($mappings as $mapping) {
                printf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    $mapping['id_repricing'],
                    $mapping['name'],
                    $mapping['active'],
                    $mapping['aggressiveness'],
                    $mapping['base'],
                    $mapping['limit'],
                    $mapping['delta']
                );
            }
        } else {
            echo 'N/A';
        }
        echo '</table>';

        // ORDERS
        $orderss = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'priceminister_orders` ORDER BY `id_order`');

        echo "\n";
        echo 'Orders: '."\n";
        echo '<table class="table-information">';
        printf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            'id_order',
            'mp_order_id',
            'shipping_type',
            'relay'
        );
        if (is_array($orderss) && count($orderss)) {
            foreach ($orderss as $orders) {
                printf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    $orders['id_order'],
                    $orders['mp_order_id'],
                    $orders['shipping_type'],
                    $orders['relay']
                );
            }
        } else {
            echo 'N/A';
        }
        echo '</table>';

        // PRODUCT OPTIONS
        $product_options = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'priceminister_product_option` ORDER BY `id_product`');

        echo "\n";
        echo 'Product options: '."\n";
        echo '<table class="table-information">';
        printf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            'id_product',
            'id_lang',
            'force',
            'disable',
            'price',
            'text'
        );
        if (is_array($product_options) && count($product_options)) {
            foreach ($product_options as $product_option) {
                printf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    $product_option['id_product'],
                    $product_option['id_lang'],
                    $product_option['force'],
                    $product_option['disable'],
                    $product_option['price'],
                    $product_option['text']
                );
            }
        } else {
            echo 'N/A';
        }
        echo '</table>';

        // PRODUCT ORDERED
        $product_ordereds = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'priceminister_product_ordered` ORDER BY `id_order`');

        echo "\n";
        echo 'Product ordered: '."\n";
        echo '<table class="table-information">';
        printf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            'id_order',
            'id_product',
            'id_product_attribute',
            'itemid'
        );
        if (is_array($product_ordereds) && count($product_ordereds)) {
            foreach ($product_ordereds as $product_ordered) {
                printf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    $product_ordered['id_order'],
                    $product_ordered['id_product'],
                    $product_ordered['id_product_attribute'],
                    $product_ordered['itemid']
                );
            }
        } else {
            echo 'N/A';
        }
        echo '</table>';

        echo "\n";
        echo 'Configuration: '."\n";
        echo $ps_configuration;

        echo '</pre>'."\n\n";

        echo $header_errors;
        die;
    }

    private function psOverride()
    {
        $hasOverride = false;
        if (defined('_PS_OVERRIDE_DIR_') && !Configuration::get('PS_DISABLE_OVERRIDES')
            && ($override_content = CommonTools::globRecursive(_PS_OVERRIDE_DIR_ . '*.php'))) {
            foreach ($override_content as $fn) {
                if (preg_match('/[A-Z]\w+.php$/', $fn)) {
                    $hasOverride = true;
                    break;
                }
            }
        }

        if (class_exists('PrestaShopAutoload')) {
            $prestashopAutoLoad = PrestaShopAutoload::getInstance();
            $prestashopAutoLoad->generateIndex();
            $overrides = array();

            if (is_array($prestashopAutoLoad->index) && count($prestashopAutoLoad->index)) {
                foreach ($prestashopAutoLoad->index as $item) {
                    if (stripos($item['path'], 'override/') !== false) {
                        $overrides[] = $item['path'];
                    }
                }
            }
        }

        return array('has_override' => $hasOverride, 'overrides' => $overrides);
    }
}

$pmConnexionCheck = new PriceMinisterConnexionCheck();
$pmConnexionCheck->dispatch();
