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
require_once(dirname(__FILE__).'/../classes/tools.class.php');
require_once(dirname(__FILE__).'/../classes/product.class.php');
require_once(dirname(__FILE__).'/../classes/mirakl.api.account.php');
require_once(dirname(__FILE__).'/../classes/mirakl.api.products.php');

// Sep-25-2018: Use only 1 main class for all marketplaces

class MiraklConnectionCheck extends Mirakl
{
    private $callback;
    private $debug;

    public function __construct()
    {
        parent::__construct();

        MiraklContext::restore($this->context);

        $this->debug = (bool)Mirakl::getConfig(Mirakl::CONFIG_DEBUG);

        if (Tools::getValue('instant_token') !== Mirakl::getConfigGlobalShop(Mirakl::CONFIG_INSTANT_TOKEN)) {
            die('Wrong token...');
        }
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return parent::l($string, basename(__FILE__, '.php'), $id_lang);
    }

    public function dispatch()
    {
        ob_start();
        $callback = Tools::getValue('callback');

        if (empty($callback) || $callback == '?') {
            $this->callback = 'jsonp_'.time();
        } else {
            $this->callback = $callback;
        }

        $action = Tools::getValue('action');

        switch ($action) {
            case 'check':
                $this->check();
                break;
            case 'php-info':
                $this->phpInfo();
                break;
            case 'prestashop-info':
                $this->prestashopInfo();
                break;
            default:
                $this->dieAndAlert('Missing parameter, nothing to do !');
                break;
        }
    }

    public function prestashopInfo()
    {
        $content = '';
        $header_errors = ob_get_clean();

        if (version_compare(_PS_VERSION_, 1.5, '>=')) {
            $sort = 'ORDER by `name`,`id_shop`';
            $ps15 = true;
        } else {
            $sort = 'ORDER by `name`';
            $ps15 = false;
        }

        $results = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'configuration` WHERE `name` LIKE "PS_%" OR `name` LIKE "'.pSQL($this->name).'_%" '.pSQL($sort));

        $ps_configuration = null;

        foreach ($results as $result) {
            if (strpos($result['name'], 'USERNAME') || strpos($result['name'], 'KEY') || strpos($result['name'], 'EMAIL') || strpos($result['name'], 'PASSWORD') || strpos($result['name'], 'PASSWD')) {
                continue;
            }

            $value = $result['value'];

            if (base64_encode(base64_decode($value, true)) === $value) { //TODO: VALIDATION - Necessary in this context
                $value = base64_decode($value, true);//TODO: VALIDATION - Necessary in this context
            } else {
                $value = $result['value'];
            }

            if (@serialize(@MiraklTools::unSerialize($value)) == $value) {
                $value = MiraklTools::pre(array(print_r(MiraklTools::unSerialize($value), true)), true);
            } else {
                $value = Tools::strlen($result['value']) > 128 ? Tools::substr($result['value'], 0, 128).'...' : $result['value'];
            }

            if ($ps15) {
                $ps_configuration .= sprintf('%-50s %03d %03d : %s'."\n", $result['name'], $result['id_shop'], $result['id_shop_group'], $value);
            } else {
                $ps_configuration .= sprintf('%-50s : %s'."\n", $result['name'], $value);
            }
        }

        $content .= html_entity_decode('&lt;h1&gt;Prestashop&lt;/h1&gt;');
        $content .= 'Module: '.$this->name.' '.$this->version."\n\n";
        $content .= 'Version: '._PS_VERSION_."\n\n";

        $content .= 'Live Configuration Fields: '.Tools::getValue('fields')."\n";
        $content .= 'Max Input Vars: '.@ini_get('max_input_vars').'/'.@get_cfg_var('max_input_vars')."\n";
        $content .= 'Memory Limit: '.@ini_get('memory_limit').'/'.@get_cfg_var('memory_limit')."\n";
        $content .= "\n";


        $content .= 'Catalog: '."\n";
        $content .= sprintf('%-58s : '.html_entity_decode('&lt;b&gt;%s&lt;/b&gt;')."\n", 'Categories', Db::getInstance()->getValue('SELECT count(`id_category`) as count FROM `'._DB_PREFIX_.'category`'));
        $content .= sprintf('%-58s : '.html_entity_decode('&lt;b&gt;%s&lt;/b&gt;')."\n", 'Products', Db::getInstance()->getValue('SELECT count(`id_product`) as count FROM `'._DB_PREFIX_.'product`'));
        $content .= sprintf('%-58s : '.html_entity_decode('&lt;b&gt;%s&lt;/b&gt;')."\n", 'Attributes', Db::getInstance()->getValue('SELECT count(`id_attribute`) as count FROM `'._DB_PREFIX_.'attribute`'));
        $content .= sprintf('%-58s : '.html_entity_decode('&lt;b&gt;%s&lt;/b&gt;')."\n", 'Features', Db::getInstance()->getValue('SELECT count(`id_feature_value`) as count FROM `'._DB_PREFIX_.'feature_value`'));

        $content .= "\n";
        $content .= 'Configuration: '."\n";
        $content .= $ps_configuration;

        MiraklTools::pre(array($content));

        echo $header_errors;
        die;
    }

    public function phpInfo()
    {
        $header_errors = ob_get_clean();
        ob_start();
        phpinfo(INFO_ALL & ~INFO_CREDITS & ~INFO_LICENSE & ~INFO_ENVIRONMENT & ~INFO_VARIABLES);
        $phpinfos = ob_get_clean();

        $phpinfos = preg_replace('/(a:link.*)|(body, td, th, h1, h2.*)|(img.*)/', '', $phpinfos);

        echo html_entity_decode('&lt;/pre&gt;')."\n\n";

        echo html_entity_decode('&lt;h1&gt;PHP&lt;/h1&gt;')."\n";
        echo html_entity_decode('&lt;div class="phpinfo"&gt;');
        echo $phpinfos;
        echo html_entity_decode('&lt;/div&gt;');

        echo $header_errors;
        die;
    }

    private function dieAndAlert($msg)
    {
        $json = Tools::jsonEncode(array('alert' => $msg));

        echo $this->callback.'('.$json.')';
        die;
    }

    private function check()
    {
        $message = null;
        $response = null;
        $error = false;

        $this->debug = Tools::getValue('debug');

        if ($this->debug) {
            ob_start();
        }

        $api_key = Tools::getValue('api_key');

        if (empty($api_key)) {
            $message .= $this->l('Please provide an api key');
            $error = true;
        }

        if (!$error) {
            $mirakl_params = self::$marketplace_params;
            $mirakl_params['debug'] = $this->debug;
            $mirakl_params['api_key'] = $api_key;

            $mirakl = new MiraklApiAccount($mirakl_params);

            $response = $mirakl->account();

            if (is_array($response)) {
                $message .= 'API Key:'.htmlspecialchars($api_key, ENT_QUOTES, 'UTF-8').nl2br("\n");
                $message .= nl2br(print_r($response, true));
            } elseif (!empty($response) && strstr($response, '<')) {
                $result = simplexml_load_string($response);
            } else {
                $result = null;
            }

            if (isset($result->shop_name)) {
                $message .= sprintf($this->l('Connection test successfull to Mirakl.').nl2br("\n")
                    .'Store: '.$result->shop_name.nl2br("\n")
                    .'Status: '.$result->shop_state);

                $error = false;
            } else {
                $message .= $this->l('Failed to connect to Mirakl');
                $error = true;
            }
            
            if (isset($result->shippings)) {
                $this->saveMiraklShipping($result->shippings);
            }
        }

        if ($this->debug) {
            MiraklTools::pre(array($response));
            $output = ob_get_clean();
        } else {
            $output = null;
        }

        $json = Tools::jsonEncode(array('message' => $message, 'error' => $error, 'debug' => $output));

        echo (string)$this->callback.'('.$json.')';
        die;
    }

    private function saveMiraklShipping(SimpleXMLElement $shippingXML)
    {
        $result = array();
        foreach ($shippingXML->shipping as $shipping) {
            $shippingCode = (string)$shipping->shipping_type_code;
            $shippingLabel = (string)$shipping->shipping_type_label;
            $shippingZoneCode = isset($shipping->shipping_zone_code) ? (string)$shipping->shipping_zone_code : '';
            $shippingZoneLabel = isset($shipping->shipping_zone_label) ? (string)$shipping->shipping_zone_label : '';
            $result[] = array(
                // Shipping labels can be duplicated
                'carrier_code' => $shippingCode . ($shippingZoneCode ? "::$shippingZoneCode" : ''),
                'carrier_name' => $shippingLabel . ($shippingZoneLabel ? " ($shippingZoneLabel)" : ''),
            );
        }
        Mirakl::updateConfig(MiraklConstant::CONFIG_CARRIERS_MKP, $result);
    }
}

$mirakl_connection_check = new MiraklConnectionCheck;
$mirakl_connection_check->dispatch();
