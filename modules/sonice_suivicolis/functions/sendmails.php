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
} else {
    require_once(dirname(__FILE__).'/../../../config/config.inc.php');
}
require_once(_PS_MODULE_DIR_.'sonice_suivicolis/sonice_suivicolis.php');
require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviEvent.php');
require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviWebService.php');
require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviMessaging.php');


class SoNiceSuiviSendMails extends SoNice_SuiviColis
{

    /** @var bool */
    protected $cron = false;

    /** @var Context */
    protected $context;

    public function __construct()
    {
        parent::__construct();

        if (Tools::getValue('debug')) {
            $this->debug = true;
        }

        if ($this->debug) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }

        $current_shop = new Shop((int)Tools::getValue('id_shop', Configuration::get('PS_SHOP_DEFAULT')));
        if (Validate::isLoadedObject($current_shop)) {
            Context::getContext()->shop = $current_shop;
            $this->context->shop = $current_shop;
        } else {
            die('Unable to set the current shop context.');
        }

        $this->cron = (bool)Tools::getValue('cron', false);
    }


    public function l($string, $specific = null, $id_lang = null)
    {
        if (!$specific) {
            $specific = basename(__FILE__, '.php');
        }

        return (parent::l($string, $specific, $id_lang));
    }


    private function formatDataCron(&$v)
    {
        $v = reset($v);
    }


    public function exec()
    {
        ob_start();

        if (Tools::getValue('token') !== Configuration::get('SONICE_SUIVI_TOKEN')) {
            die('Wrong token, execution stopped.');
        }

        $conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_CONF'));

        if (!isset($conf['nb_weeks']) || !$conf['nb_weeks']) {
            $conf['nb_weeks'] = 1;
        }

        if ($this->cron) {
            $parcels = Db::getInstance()->executeS(
                'SELECT CONCAT_WS("|", `shipping_number`, `id_order`, `inovert`)
                FROM `'._DB_PREFIX_.'sonice_suivicolis`
                WHERE `mail` = 0
                AND `inovert` <> ""
                AND `coliposte_date` > DATE_ADD(NOW(), INTERVAL - '.(int)$conf['nb_weeks'].' WEEK)
                ORDER BY `id_order` DESC'
            );

            array_walk($parcels, array($this, 'formatDataCron'));

            if ($this->cron) {
                printf(
                    'Cron started for SoNice Suivi Colis - %s email(s) to treat.<br>%s<br><br>',
                    count($parcels),
                    '==================================='
                );

                echo '<pre>'.print_r($parcels, true).'</pre><br><br>';
            }
        } else {
            $parcels = Tools::getValue('orders');
        }

        if (isset($conf['demo']) && $conf['demo']) {
            foreach ($parcels as $key => $parcel) {
                $order_data = explode('|', $parcel);

                $shipping_number = reset($order_data);
                $id_order = (int)$order_data[1];

                $parcel_information = new SoNiceSuiviWebService($shipping_number);
                $parcel_information->saveParcelInformation($id_order, 1);
            }

            $callback = Tools::getValue('callback');

            die($callback.'('.Tools::jsonEncode(array(
                    'parcels' => $parcels, 'error' => false, 'console' => ''
                )).')');
        }

        $mapping_conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_MAPPING'));
        $mail = unserialize(Configuration::get('SONICE_SUIVICOLIS_MAIL'));
        $mail_pj = unserialize(Configuration::get('SONICE_SUIVICOLIS_MAIL_PJ'));

        $error = false;

        if (is_array($parcels) && count($parcels)) {
            foreach ($parcels as $key => $parcel) {
                if (Tools::strlen($parcel)) {
                    $order_data = explode('|', $parcel);

                    $shipping_number = reset($order_data);
                    $id_order = (int)$order_data[1];
                    $state = $order_data[2];

                    if ($state) {
                        foreach ($mapping_conf as $id => $conf) {
                            // [IF] state found in mapping configuration [THEN] look for the mail template and send it
                            if (in_array($state, $conf)) {
                                // DataBase checking
                                $parcel_information = new SoNiceSuiviWebService($shipping_number);
                                if ($parcel_information->mail) {
                                    if ($this->cron) {
                                        echo 'Email already sent for '.$id_order.'<br><br>';
                                    }
                                    break;
                                }

                                $order = new Order($id_order);
                                if (!Validate::isLoadedObject($order)) {
                                    if ($this->cron) {
                                        echo 'Unable to load Order #'.$id_order.'<br><br>';
                                    }
                                    break;
                                }

                                $customer = new Customer((int)$order->id_customer);
                                if (!Validate::isLoadedObject($customer)) {
                                    if ($this->cron) {
                                        echo 'Unable to load Customer #'.$order->id_customer.'<br><br>';
                                    }
                                    break;
                                }

                                if (!isset($mail) || !isset($mail[$id])) {
                                    if ($this->cron) {
                                        echo 'No mail set for this status.<br><br>';
                                    }
                                    break;
                                }

                                // Send mail
                                $email = new SoNiceMessaging();
                                $email->order = $order;
                                $email->customer = $customer;
                                $email->tracking = $parcel_information;

                                $customer_language = isset($email->order->id_lang) ?
                                    Language::getIsoById($email->order->id_lang) : 'fr';

                                $email->event = new SoNiceSuiviEvent($customer_language);

                                $email->template = $mail[$id];
                                $invoice_attachment = false;
                                $delivery_slip_attachment = false;

                                if (!$email->isLoadedClass()) {
                                    echo $this->l('Impossible to set email parameters.');
                                    $error = true;
                                    continue;
                                }

                                if (isset($mail_pj[$id]['invoice']) && $mail_pj[$id]['invoice']) {
                                    $invoice_attachment = true;
                                }

                                if (isset($mail_pj[$id]['shipping']) && $mail_pj[$id]['shipping']) {
                                    $delivery_slip_attachment = true;
                                }

                                if ($email->order->hasBeenShipped()) {
                                    $email->letter_subject = sprintf(
                                        ' %s %s %d.',
                                        html_entity_decode('&#10004;', ENT_NOQUOTES, 'UTF-8'),
                                        html_entity_decode(
                                            $this->l('New message about your order number'),
                                            ENT_COMPAT,
                                            'UTF-8'
                                        ),
                                        (int)$email->order->id
                                    );
                                } else {
                                    $email->letter_subject = sprintf(
                                        ' %s %d.',
                                        html_entity_decode(
                                            $this->l('New message about your order number'),
                                            ENT_COMPAT,
                                            'UTF-8'
                                        ),
                                        (int)$email->order->id
                                    );
                                }

                                $result = $email->sendMail(
                                    $invoice_attachment,
                                    $delivery_slip_attachment,
                                    $shipping_number
                                );

                                if (!$result) {
                                    echo $this->l('Error while sending the mail :').' '.$result;
                                    $parcels[$key]['error'] = $this->l(
                                        'Error while sending the mail :',
                                        'sendmails'
                                    ).' '.$result;
                                    $error = true;
                                    continue;
                                } else {
                                    // Save in Db
                                    $parcel_information->saveParcelInformation($id_order, 1);
                                    if ($this->cron) {
                                        echo 'Mail sent for Order #'.$id_order.'<br><hr>';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!$this->cron) {
            $callback = Tools::getValue('callback');
            $output = ob_get_clean();
            die($callback.'('.Tools::jsonEncode(array('parcels' => $parcels, 'error' => $error, 'console' => $output)).')');
        }
    }
}

$Sonice_SuiviColis_SendMails = new SoNiceSuiviSendMails();
$Sonice_SuiviColis_SendMails->exec();
