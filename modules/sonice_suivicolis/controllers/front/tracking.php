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
 * @package   sonice_suivicolis
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice_suivicolis@common-services.com
 */

require_once dirname(__FILE__).'/../../sonice_suivicolis.php';
require_once dirname(__FILE__).'/../../classes/SoNiceSuiviMessaging.php';
require_once dirname(__FILE__).'/../../classes/SoNiceSuiviOrderHistory.php';
require_once dirname(__FILE__).'/../../classes/SoNiceSuiviTools.php';
require_once dirname(__FILE__).'/../../classes/SoNiceSuiviEvent.php';
require_once dirname(__FILE__).'/../../classes/SoNiceSuiviWebService.php';

/**
 * Class tracking
 */
class SoNice_SuiviColisTrackingModuleFrontController extends ModuleFrontController
{

    /** @var bool If set to true, will be redirected to authentication page */
    public $auth = false;

    /** @var bool */
    public $ajax;

    /** @var bool */
    protected $debug;

    /**
     * @return bool|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function display()
    {
        $this->ajax = 1;
        $this->debug = Tools::getValue('debug', false);

        if ($this->debug) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL|E_STRICT);
        }

        $current_shop = new Shop((int)Tools::getValue('id_shop', 1));
        if (!Validate::isLoadedObject($current_shop)) {
            die('Unable to set the current shop context.');
        }

        Context::getContext()->shop = $current_shop;
        Shop::setContext(Shop::CONTEXT_SHOP, $current_shop->id);

        $token = Tools::getValue('token');
        if (!$token || !Tools::strlen($token) || $token !== Configuration::get('SONICE_SUIVI_TOKEN')) {
            $this->ajaxDie('Wrong token, execution stopped.');
        }

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

        $mapping_conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_MAPPING'));
        $payment_methods_excluded = unserialize(Configuration::get('SONICE_SUIVICOLIS_PAYMENT'));
        $status_methods_excluded = unserialize(Configuration::get('SONICE_SUIVICOLIS_STATUSES'));
        $conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_CONF'));

        if (!is_array($mapping_conf) || !count($mapping_conf)) {
            echo 'It seems that the statuses mapping is not done, please check the module configuration.';
            die;
        }

        $parcels = array();
        $count = 1;

        $sonice_event = new SoNiceSuiviEvent();

        if (!is_array($checkboxes) || !count($checkboxes)) {
            echo 'No order to track.';
            die;
        }

        foreach ($checkboxes as $key => $shipping_number) {
            $checkbox_data = explode('|', $shipping_number);

            $shipping_number = reset($checkbox_data);
            $shipping_number = str_replace(' ', '', $shipping_number);
            $id_order = (int)$checkbox_data[1];
            $order = new Order($id_order);

            if (!Validate::isLoadedObject($order)) {
                echo 'Fail to load the order '.$id_order.'.';
                $parcels[$key] = null;
                continue;
            }

            if (Tools::strlen($shipping_number) < 10) {
                echo 'No tracking number for order '.$id_order.'.<br><hr>';
                $parcels[$key] = null;
                continue;
            }

            try {
                $current_state = version_compare(_PS_VERSION_, '1.5', '>=') ?
                    $order->current_state : $order->getCurrentState();

                if (in_array($order->payment, $payment_methods_excluded)) {
                    echo 'Excluded payment method for the order  #'.$id_order.
                        ' ('.$order->payment.').<br><hr>';
                    $parcels[$key] = null;
                    continue;
                } elseif (in_array($current_state, $status_methods_excluded)) {
                    echo 'Excluded order status for the order  #'.$order->id.
                        ' ('.$order->current_state.').<br><br>';
                    $parcels[$key] = null;
                    continue;
                }

                $tracking = new SoNiceSuiviWebService($shipping_number);

                try {
                    $tracking->call()->setResponse()->parse();
                } catch (Exception $excp) {
                    echo 'Fail tracking parcel '.$shipping_number.'.<br><br>';
                    $parcels[$key] = null;
                    continue;
                }

                echo '<pre>'.print_r($tracking->response, true).'</pre><br><br>';

                $error_code = isset($tracking->error_code) ? (int)$tracking->error_code : false;
                $error_message = isset($tracking->error_message) ?
                    (string)$tracking->error_message :
                    'An unknown error occured, refer to the error code.';

                if ($error_code && $error_code > 0) {
                    echo $error_message.' (#'.(int)$id_order.')<br>';

                    if ($error_code == 201 && $this->debug) {
                        var_dump($tracking->module_params);
                    }

                    $parcels[$key] = null;
                    continue;
                }

                $parcels[$key] = $tracking->response->Body->trackResponse->return;

                foreach ($mapping_conf as $state_id => $state) {
                    $event = (string)$tracking->event_code;

                    foreach ($state as $inovert) {
                        if ($inovert === $event) {
                            if (version_compare(_PS_VERSION_, '1.5', '>=') && $state_id == $order->current_state) {
                                echo '#'.$id_order.' ==> Same status, no need to change.<br><br>';
                                break 2;
                            } elseif ($state_id == $order->getCurrentState()) {
                                echo '#'.$id_order.' ==> Same status, no need to change.<br><br>';
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
                                echo '#'.$id_order.' ==> Error with call to SoNiceOrderHistory::addWithemail().<br><br>';
                            }

                            // Change Order current state
                            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                                $order->current_state = (int)$state_id;
                            }

                            $order->update();

                            echo '#'.$id_order.' ==> Status changed.<br>';

                            break;
                        }
                    }
                }

                $tracking->saveParcelInformation($id_order, 0);

                // To avoid to be blocked by ColiPoste
                if (($count++) >= 3) {
                    sleep(1);
                }

                echo '<hr>';
            } catch (PrestaShopException $psexception) {
                echo '<strong><u>';
                echo '/!\\ A PrestaShop Exception occured with order #'.$order->id.' ('.$shipping_number.').<br>';
                echo '</strong></u>';
                echo '<pre style="border: 1px solid gainsboro; background-color:whitesmoke; padding: 5px;">'.$psexception->getMessage().'</pre>';
                echo '<pre style="border: 1px solid gainsboro; background-color:whitesmoke; padding: 5px;">'.$psexception->getTraceAsString().'</pre><br>';

                $parcels[$key] = null;
                continue;
            }
        }

        exit;
    }

    /**
     * @param null|array $v
     */
    private function formatDataCron(&$v)
    {
        if (Tools::strlen($v['shipping_number']) < 10) {
            $v = null;
            echo 'The order #'.$v['id_order'].' was skipped because no shipping number were found.<br>';
        } else {
            $v = $v['shipping_number'].'|'.$v['id_order'];
        }
    }
}
