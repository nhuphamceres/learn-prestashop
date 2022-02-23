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
    require_once(readlink(_PS_MODULE_DIR_.'sonice_suivicolis/sonice_suivicolis.php'));
    require_once(readlink(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviMessaging.php'));
} else {
    require_once(dirname(__FILE__).'/../../../config/config.inc.php');
    require_once(_PS_MODULE_DIR_.'sonice_suivicolis/sonice_suivicolis.php');
    require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviMessaging.php');
}


class SendMailCron extends SoNice_SuiviColis
{

    public function __construct()
    {
        parent::__construct();

        $configuration = Tools::unSerialize(Configuration::get('SONICE_SUIVICOLIS_CONF'));
        $this->debug = Tools::getValue('debug', $configuration['debug']);

        if ($this->debug) {
            @ini_set('display_errors', 'on');
            @define('_PS_DEBUG_SQL_', true);
            @error_reporting(E_ALL|E_STRICT);
        }

        $current_shop = new Shop((int)Tools::getValue('id_shop', Configuration::get('PS_SHOP_DEFAULT')));
        if (Validate::isLoadedObject($current_shop)) {
            Context::getContext()->shop = $current_shop;
            $this->context->shop = $current_shop;
        } else {
            die('Unable to set the current shop context.');
        }
    }


    public function l($string, $id_lang = null, $locale = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }


    public function exec()
    {
        if (!Tools::getValue('token') || !Tools::strlen(Tools::getValue('token')) ||
            Tools::getValue('token') !== Configuration::get('SONICE_SUIVI_TOKEN')) {
            die('Wrong token, execution stopped.');
        }

        echo '== INCENTIVE CHECK BEGIN ==<br>';

        $conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_CONF'));
        if (isset($conf['demo']) && $conf['demo']) {
            echo $this->l('Mail has been sent').'<br>';
            echo '== INCENTIVE CHECK DONE ==';

            return (true);
        }

        $tracked_orders = Db::getInstance()->executeS(
            'SELECT *
            FROM `'._DB_PREFIX_.'sonice_suivicolis`
            WHERE `incentive` = 0
            AND `date_upd` > DATE_ADD(NOW(), INTERVAL - 1 MONTH)'
        );

        if (!isset($conf['incentive_time']) || $conf['incentive_time'] == 0 || empty($tracked_orders)) {
            echo '<br>No orders...<br><br>';
            echo '== INCENTIVE CHECK DONE ==';

            return false;
        }

        foreach ($tracked_orders as $tracked) {
            if ($tracked['id_order'] !== '0') {
                $order = new Order((int)$tracked['id_order']);

                if (!Validate::isLoadedObject($order)) {
                    continue;
                }

                if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                    $order_state = (int)$order->current_state;
                } else {
                    $order_state = (int)$order->getCurrentState();
                }

                if ($order_state === (int)$conf['incentive_state']) {
                    $date_track = strtotime($tracked['date_upd']);

                    $today = strtotime('now');
                    $interval = ($today - $date_track) / (24 * 60 * 60);

                    if ($interval >= (int)$conf['incentive_time']) {
                        echo sprintf('%s %d - ', $this->l('Create incentive mail for order'), (int)$order->id);

                        $email = new SoNiceMessaging(Tools::getValue('debug'), $tracked['shipping_number']);
                        $email->order = $order;
                        $email->customer = new Customer((int)$order->id_customer);
                        $email->template = $conf['incentive_mail_tpl'];

                        $invoice_attachment = false;
                        $delivery_slip_attachment = false;

                        if (!$email->isLoadedClass()) {
                            echo $this->l('Impossible to set email parameters.').'<br>';
                            continue;
                        }

                        $email->letter_subject = html_entity_decode(
                            $this->l('Incentive request', 'sendmails'),
                            ENT_COMPAT,
                            'UTF-8'
                        );

                        $result = $email->sendMail($invoice_attachment, $delivery_slip_attachment);

                        if (!$result) {
                            echo $this->l('Error while sending the mail').'<br>';
                            continue;
                        }

                        // Save in Db
                        Db::getInstance()->execute(
                            'UPDATE `'._DB_PREFIX_.'sonice_suivicolis`
                            SET `incentive` = 1
                            WHERE `shipping_number` = "'.pSQL($tracked['shipping_number']).'"'
                        );

                        echo $this->l('Sent').'<br>';
                    } else {
                        echo $this->l('Time interval is too short to send an email').'<br>';
                    }
                } else {
                    echo $this->l('Status not matched. Order skipped.').'<br>';
                }
            }
        }

        echo '== INCENTIVE CHECK DONE ==';

        return true;
    }
}


$cron = new SendMailCron();
$cron->exec();
