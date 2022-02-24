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

require_once(dirname(__FILE__).'/env.php');
require_once(dirname(__FILE__).'/../fnac.php');
require_once(dirname(__FILE__).'/../classes/fnac.webservice.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.product.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.tools.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.address.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.order.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.orderhistory.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.cart.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.payment.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.context.class.php');

class FnacUpdateOrder extends FNAC
{
    public function __construct()
    {
        parent::__construct();

        FNAC_Context::restore($this->context, Validate::isLoadedObject($this->context->shop) ? $this->context->shop : null);
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, 'updateorder', $this->id_lang));
    }

    public function doIt()
    {
        $cookie = $this->context->cookie;

        $platform = Tools::getValue('platform');
        if (!in_array($platform, array('fr', 'es', 'pt'))) {
            echo $this->l('The platform is not available.').'<br />';
            die;
        }
        $fnac_id_lang = Language::getIdByIso($platform);
        if (!isset($fnac_id_lang)) {
            echo $this->l('The language ID is not available.').'<br />';
            die;
        }
        //French might not be set as language for the shop...
        if (!$fnac_id_lang) {
            $default_lang = Language::getLanguages(true);
            $fnac_id_lang = (count($default_lang) > 0) ? $default_lang[0]['id_lang'] : 1;
        }


        // FNAC API - France by default
        if ($platform == 'fr') {
            $flag = '';
        } else {
            $flag = Tools::strtoupper($platform).'_';
        }

        $partner_id = Configuration::get('FNAC_'.$flag.'PARTNER_ID');
        $shop_id = Configuration::get('FNAC_'.$flag.'SHOP_ID');
        $api_key = Configuration::get('FNAC_'.$flag.'API_KEY');
        $api_url = Configuration::get('FNAC_'.$flag.'API_URL');

        $id_lang = $fnac_id_lang;

        $fnac_order_id = Tools::getValue('fnac_order_id');
        $order_id = Tools::getValue('order_id');
        $action = Tools::getValue('action');
        $callback = Tools::getValue('callback');
        $debug = Tools::getValue('debug');

        // Acces WebService
        $fnac = new FnacAPI($partner_id, $shop_id, $api_key, $api_url, $debug);

        if (!$fnac->Login()) {
            echo $this->l('Unable to login to the FNAC MarketPlace').'<br />';
            die;
        }
        //* déja défini dans fnac.webservice.class.php mais bug avec la translation i18n/smarty de prestashop ? */
        //* http://www.prestashop.com/forums/viewthread/97423/rapports_de_bugs/i18n__panneau_dadministration__outils__traductions___classes_adjacentes___constantes_/ */
        $statusName = $fnac->getStatusesNames();

        switch ($action) {
            case 'status' :
                // Query FNAC WS
                //
                if (!$xml = $fnac->OrderQueryById($fnac_order_id)) {
                    echo $this->l('Unable to retrieve order id');
                    die;
                }
                $status = constant('FnacAPI::'.(string)$xml->order->state);

                // Update the table
                //
                $fnacOrder = new FNAC_Order($order_id);
                $fnacOrder->updateMpStatus((int)$status);

                // Order States / PS State & MP State
                //
                $statuses = unserialize(Configuration::get('FNAC_STATUSES_MAP'));
                $mpStatus = $fnacOrder->marketPlaceOrderStatus;

                if (isset($statuses[$status])) {
                    $psStatus = $statuses[$status];
                } else {
                    $psStatus = _PS_OS_PREPARATION_;
                }

                // Set order state in order history ONLY even if the "out of stock" status has not been yet reached
                // So you migth have two order states

                $new_history = new FNAC_OrderHistory();
                $new_history->id_order = (int)$fnacOrder->id;
                $new_history->id_employee = (int)$cookie->id_employee;
                $new_history->changeIdOrderState($psStatus, (int)$fnacOrder->id);
                $new_history->addWithOutEmail(true);

                // Return JSON
                //
                $data = array(
                    'id' => (int)$status,
                    'name' => $statusName[$status]
                );
                echo $callback.'('.Tools::jsonEncode($data).')';
                break;

            case 'shipping' :
                $tracking_number = Tools::getValue('tracking_number');
                $tracking_company = Tools::getValue('tracking_company');

                $params = array(
                    'number' => $tracking_number,
                    'company' => $tracking_company,
                );

                // Query FNAC WS
                //
                if (!$fnac->OrderSend($fnac_order_id, $params)) {
                    $message = $this->l('Unable to Update Order');
                    $pass = false;
                } else {
                    $message = $this->l('Order Successfully Updated');
                    $pass = true;

                    $fnacOrder = new FNAC_Order($order_id);
                    $fnacOrder->updateMpStatus(FnacAPI::Shipped);
                    $fnacOrder->shipping_company = $tracking_company;
                    $fnacOrder->shipping_number = $tracking_number;
                    $fnacOrder->update();

                    if (!$callback) {
                        // Order States / PS State & MP State
                        //
                        $statuses = unserialize(Configuration::get('FNAC_STATUSES_MAP'));
                        $mpStatus = $fnacOrder->marketPlaceOrderStatus;

                        if (isset($statuses[FnacAPI::Shipped])) {
                            $psStatus = $statuses[FnacAPI::Shipped];
                        } else {
                            $psStatus = _PS_OS_PREPARATION_;
                        }

                        // Set order state in order history ONLY even if the "out of stock" status has not been yet reached
                        // So you migth have two order states

                        $new_history = new FNAC_OrderHistory();
                        $new_history->id_order = (int)$fnacOrder->id;
                        $new_history->id_employee = (int)$cookie->id_employee;
                        $new_history->changeIdOrderState($psStatus, (int)$fnacOrder->id);
                        $new_history->addWithOutEmail(true);
                    }
                }

                $data = array(
                    'result' => (int)$pass,
                    'state' => FnacAPI::Shipped,
                    'msg' => $message
                );
                echo $callback.'('.Tools::jsonEncode($data).')';

                unset($fnacOrder);

                break;

            case 'shipping_number' :
                $tracking_number = Tools::getValue('tracking_number');
                $tracking_company = Tools::getValue('tracking_company');

                $params = array(
                    'number' => $tracking_number,
                    'company' => $tracking_company,
                );

                // Query FNAC WS
                //
                if (!$fnac->OrderSend($fnac_order_id, $params, 'update', 'Updated')) {
                    $message = $this->l('Unable to Update Order Tracking Number');
                    $pass = false;
                } else {
                    $message = $this->l('Order Tracking Number Successfully Updated');
                    $pass = true;
                }

                $data = array(
                    'result' => (int)$pass,
                    'state' => FnacAPI::Update,
                    'msg' => $message
                );

                echo $callback.'('.Tools::jsonEncode($data).')';
                break;
        }
    }
}

$_FnacUpdateOrder = new FnacUpdateOrder;
$_FnacUpdateOrder->doIt();
