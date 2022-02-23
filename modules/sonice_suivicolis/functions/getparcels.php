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

$file = new SplFileInfo($_SERVER['SCRIPT_FILENAME']);

require_once dirname(dirname(dirname($file->getPath()))).'/config/config.inc.php';
require_once dirname(dirname(dirname($file->getPath()))).'/init.php';
require_once(_PS_MODULE_DIR_.'sonice_suivicolis/sonice_suivicolis.php');
require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviMessaging.php');
require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviOrderHistory.php');
require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviTools.php');
require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviEvent.php');
require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviWebService.php');

class SoNiceSuiviGetParcels extends SoNice_SuiviColis
{

    /** @var bool */
    protected $cron = false;

    /** @var Context */
    protected $context;

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

        $current_shop = new Shop((int)Tools::getValue('id_shop', 1));
        if (Validate::isLoadedObject($current_shop)) {
            Context::getContext()->shop = $current_shop;
            $this->context->shop = $current_shop;
        } else {
            die('Unable to set the current shop context.');
        }

        $this->cron = (bool)Tools::getValue('cron', false);

        if (!isset(Context::getContext()->employee) || !Validate::isLoadedObject(Context::getContext()->employee)) {
            $id_employee = $configuration['cron_employee'] ?
                (int)$configuration['cron_employee'] : (int)Db::getInstance()->getValue(
                    'SELECT `id_employee`
                    FROM `'._DB_PREFIX_.'employee`
                    WHERE `active` = 1
                    ORDER BY `id_employee` DESC
                    LIMIT 1'
                );

            Context::getContext()->employee = new Employee($id_employee);
        }
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
        if (Tools::strlen($v['shipping_number']) < 10) {
            $v = null;
            echo 'The order #'.$v['id_order'].' was skipped because no shipping number were found.<br>';
        } else {
            $v = $v['shipping_number'].'|'.$v['id_order'];
        }
    }


    public function exec()
    {
        ob_start();

        if (!Tools::getValue('token') || !Tools::strlen(Tools::getValue('token')) ||
            Tools::getValue('token') !== Configuration::get('SONICE_SUIVI_TOKEN')) {
            die('Wrong token, execution stopped.');
        }

        if ($this->cron) {
            $order_helper = new SoNiceOrderHelperList();

            $checkboxes = (array)$order_helper->getOrders();
            array_walk($checkboxes, array($this, 'formatDataCron'));
            $checkboxes = array_filter($checkboxes);

            printf(
                'Cron started for SoNice Suivi Colis - %s order(s) to treat.<br>%s<br><br>',
                count($checkboxes),
                '==================================='
            );

            echo '<pre>'.print_r($checkboxes, true).'</pre><br><br>';
        } else {
            $checkboxes = Tools::getValue('checkbox');
        }

        $mapping_conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_MAPPING'));
        $payment_methods_excluded = unserialize(Configuration::get('SONICE_SUIVICOLIS_PAYMENT'));
        $status_methods_excluded = unserialize(Configuration::get('SONICE_SUIVICOLIS_STATUSES'));
        $conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_CONF'));

        if (!is_array($mapping_conf) || !count($mapping_conf)) {
            echo $this->l('It seems that the statuses mapping is not done, please check the module configuration.');
            die;
        }

        $parcels = array();
        $count = 1;
        $error = false;

        $sonice_event = new SoNiceSuiviEvent();

        if (is_array($checkboxes) && count($checkboxes)) {
            foreach ($checkboxes as $key => $shipping_number) {
                $checkbox_data = explode('|', $shipping_number);

                $shipping_number = reset($checkbox_data);
                $shipping_number = str_replace(' ', '', $shipping_number);
                $id_order = (int)$checkbox_data[1];
                $order = new Order($id_order);

                if (!Validate::isLoadedObject($order)) {
                    echo $this->l('Fail to load the order').' '.$id_order.'.';
                    $error = true;
                    $parcels[$key] = null;
                    continue;
                }

                if (Tools::strlen($shipping_number) < 10) {
                    echo $this->l('No tracking number for order').' '.$id_order.'.<br><hr>';
                    $error = true;
                    $parcels[$key] = null;
                    continue;
                }

                try {
                    $current_state = version_compare(_PS_VERSION_, '1.5', '>=') ?
                        $order->current_state : $order->getCurrentState();

                    if (in_array($order->payment, $payment_methods_excluded)) {
                        echo $this->l('Excluded payment method for the order ').' #'.$id_order.
                            ' ('.$order->payment.').<br><hr>';
                        $parcels[$key] = null;
                        continue;
                    } elseif (in_array($current_state, $status_methods_excluded)) {
                        echo $this->l('Excluded order status for the order ').' #'.$order->id.
                            ' ('.$order->current_state.').<br><br>';
                        $parcels[$key] = null;
                        continue;
                    }

                    $tracking = new SoNiceSuiviWebService($shipping_number);

                    try {
                        $tracking->call()->setResponse()->parse();
                    } catch (Exception $excp) {
                        echo $this->l('Fail tracking parcel').' '.$shipping_number.'.<br><br>';
                        $error = true;
                        $parcels[$key] = null;
                        continue;
                    }

                    if ($this->cron) {
                        echo '<pre>'.print_r($tracking->response, true).'</pre><br><br>';
                    }

                    $error_code = isset($tracking->error_code) ? (int)$tracking->error_code : false;
                    $error_message = isset($tracking->error_message) ?
                        (string)$tracking->error_message :
                        $this->l('An unknown error occured, refer to the error code.');

                    if ($error_code && $error_code > 0) {
                        echo $error_message.' (#'.(int)$id_order.')<br>';

                        if ($error_code == 201 && $this->debug && $this->cron) {
                            var_dump($tracking->module_params);
                        }

                        $error = true;
                        $parcels[$key] = null;
                        continue;
                    }

                    $parcels[$key] = $tracking->response->Body->trackResponse->return;

                    foreach ($mapping_conf as $state_id => $state) {
                        $event = (string)$tracking->event_code;

                        foreach ($state as $inovert) {
                            if ($inovert === $event) {
                                if (version_compare(_PS_VERSION_, '1.5', '>=') && $state_id == $order->current_state) {
                                    if ($this->cron) {
                                        echo '#'.$id_order.' ==> Same status, no need to change.<br><br>';
                                    }
                                    break 2;
                                } elseif ($state_id == $order->getCurrentState()) {
                                    if ($this->cron) {
                                        echo '#'.$id_order.' ==> Same status, no need to change.<br><br>';
                                    }
                                    break 2;
                                }

                                // Add the OrderState id
                                $parcels[$key]->addChild('orderState', (int)$state_id);

                                // Update order with tracking if not done yet
                                // To avoid {followup} missing
                                if (!trim($order->shipping_number)) {
                                    $order->shipping_number = $shipping_number;
                                    $order->update();
                                }
                                // Same in OrderCarrier
                                $id_order_carrier = (int)Db::getInstance()->getValue(
                                    'SELECT `id_order_carrier`
                                    FROM `'._DB_PREFIX_.'order_carrier`
                                    WHERE `id_order` = '.(int)$order->id
                                );
                                if ($id_order_carrier) {
                                    $order_carrier = new OrderCarrier($id_order_carrier);

                                    if (Validate::isLoadedObject($order_carrier) &&
                                        !trim($order_carrier->tracking_number)) {
                                        $order_carrier->tracking_number = $shipping_number;
                                        $order_carrier->update();
                                    }
                                }

                                // Add History
                                $new_history = new SoNiceOrderHistory();
                                $new_history->id_order = (int)$id_order;
                                $new_history->id_employee = isset($conf['cron_employee']) ?
                                    (int)$conf['cron_employee'] : 1;
                                $new_history->changeIdOrderState($state_id, $id_order);
                                $add_with_email = $new_history->addWithemail(true, array(
                                    '{followup}' => 'https://www.laposte.fr/particulier/outils/suivre-vos-envois?code='.
                                        $shipping_number
                                ));

                                if (!$add_with_email) {
                                    if ($this->cron) {
                                        echo '#'.$id_order.' ==> Error with call to SoNiceOrderHistory::addWithemail().
                                            <br><br>';
                                    }
                                }

                                // Change Order current state
                                if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                                    $order->current_state = (int)$state_id;
                                }

                                $order->update();

                                if ($this->cron) {
                                    echo '#'.$id_order.' ==> Status changed.<br>';
                                }

                                break;
                            }
                        }
                    }

                    $tracking->saveParcelInformation($id_order, 0);

                    // If parcel is late -> red clock
                    if (SoNiceSuiviTools::parcelIsLate((string)$tracking->skybillnumber)) {
                        $parcels[$key]->addChild('late', true);
                    }

                    $parcels[$key]->addChild('pictoState', $sonice_event->getPictoID($tracking->event_code));

                    // To avoid to be blocked by ColiPoste
                    if (($count++) >= 3) {
                        sleep(1);

                        if (!$this->cron) {
                            break;
                        }
                    }

                    if ($this->cron) {
                        echo '<hr>';
                    }
                } catch (PrestaShopException $psexception) {
                    echo '<strong><u>';
                    echo $this->l('/!\\ A PrestaShop Exception occured with order #').$order->id.
                        ' ('.$shipping_number.').<br>';
                    echo '</strong></u>';
                    echo '<pre style="border: 1px solid gainsboro; background-color:whitesmoke; padding: 5px;">'.
                        $psexception->getMessage().'</pre>';
                    echo '<pre style="border: 1px solid gainsboro; background-color:whitesmoke; padding: 5px;">'.
                        $psexception->getTraceAsString().'</pre><br>';
                    $error = true;
                    $parcels[$key] = null;
                    continue;
                }
            }
        } else {
            echo $this->l('No order to track.');
            $error = true;
        }

        if (!$this->cron) {
            $callback = Tools::getValue('callback');
            $output = ob_get_clean();

            if (Tools::strlen($output) < 5) {
                $output = null;
            }

            die($callback.'('.Tools::jsonEncode(
                array('parcels' => $parcels, 'error' => $error, 'console' => $output)
            ).')');
        }
    }
}

$get_parcels = new SoNiceSuiviGetParcels;
$get_parcels->exec();
