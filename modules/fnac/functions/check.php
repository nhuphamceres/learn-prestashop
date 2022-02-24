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

/*
  Warning in Prestashop >= 1.3.6 - 1.4
  @ to prevent notice in Tools class in E_STRICT | E_ALL mode :
  Notice: Undefined index:  HTTP_HOST in /classes/Tools.php on line 71
 */
require_once(dirname(__FILE__).'/env.php');
require_once(dirname(__FILE__).'/../fnac.php');
require_once(dirname(__FILE__).'/../classes/fnac.tools.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.product.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.webservice.class.php');

class Fnac_ConnectionCheck extends Fnac
{
    private $errors = array();
    private $_cr = "<br />\n";
    private $callback;

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    public function Dispatch()
    {
        $callback = Tools::getValue('callback');

        if (empty($callback) || $callback == '?') {
            $this->callback = 'jsonp_'.time();
        } else {
            $this->callback = $callback;
        }

        $action = Tools::getValue('action');

        switch ($action) {
            case 'check' :
                $this->Check();
                break;

            case 'php-info' :

                $this->PHP_Info();
                break;
            case 'prestashop-info' :
                $this->Prestashop_Info();
                break;

            default:
                $this->dieAndAlert('Missing parameter, nothing to do !');
        }
    }

    private function dieAndAlert($msg)
    {
        $json = FNAC_Tools::jsonEncode(array('alert' => $msg));

        echo (string)$this->callback.'('.$json.')';
        die;
    }

    private function Check()
    {
        $errors = array();
        $error = false;

        $cr = $this->_cr;

        $username = Tools::getValue('username');
        $password = Tools::getValue('password');
        $preproduction = Tools::getValue('preprod');
        $debug = Tools::getValue('debug');

        if ($debug) {
            ob_start();
        }

        if (empty($username) || empty($password)) {
            $this->dieAndAlert($this->l('Please enter a username and password first'));
            die;
        }

        $result = array();
        if (is_array($result)) {
            $message = sprintf($this->l('Connection test successfull to Fnac'));
            $error = false;
        } else {
            $message = $this->l('Failed to connect to Fnac');
            $error = true;
        }

        if ($debug) {
            $output = ob_get_clean();
        } else {
            $output = null;
        }

        $json = FNAC_Tools::jsonEncode(array('message' => $message, 'error' => $error, 'debug' => $output));

        echo (string)$this->callback.'('.$json.')';
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

        $results = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'configuration` WHERE `name` LIKE "PS_%" OR `name` LIKE "FNAC_%" '.$sort);

        $ps_configuration = null;

        foreach ($results as $result) {
            if (strpos($result['name'], 'USERNAME') || strpos($result['name'], 'KEY') || strpos($result['name'], 'EMAIL') || strpos($result['name'], 'PASSWORD') || strpos($result['name'], 'PASSWD')) {
                continue;
            }

            $value = $result['value'];

            if (FNAC_Tools::encode(FNAC_Tools::decode($value, true)) === $value) {
                $value = FNAC_Tools::decode($value, true);
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

        echo '<h1>Prestashop</h1>';
        echo '<pre>';
        echo 'Version: '._PS_VERSION_."\n\n";
        echo 'Catalog: '."\n";
        printf('%-58s : <b>%s</b>'."\n", 'Categories', Db::getInstance()->getValue('SELECT count(`id_category`) as count FROM `'._DB_PREFIX_.'category`'));
        printf('%-58s : <b>%s</b>'."\n", 'Products', Db::getInstance()->getValue('SELECT count(`id_product`) as count FROM `'._DB_PREFIX_.'product`'));
        printf('%-58s : <b>%s</b>'."\n", 'Attributes', Db::getInstance()->getValue('SELECT count(`id_attribute`) as count FROM `'._DB_PREFIX_.'attribute`'));
        printf('%-58s : <b>%s</b>'."\n", 'Features', Db::getInstance()->getValue('SELECT count(`id_feature_value`) as count FROM `'._DB_PREFIX_.'feature_value`'));

        echo "\n";
        echo 'Configuration: '."\n";
        echo $ps_configuration;

        echo '</pre>'."\n\n";

        echo $header_errors;
        die;
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
}

$fnac_ConnectionCheck = new Fnac_ConnectionCheck;
$fnac_ConnectionCheck->Dispatch();
