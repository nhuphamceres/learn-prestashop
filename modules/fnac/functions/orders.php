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

require_once dirname(__FILE__).'/env.php';
require_once(dirname(__FILE__).'/../fnac.php');
require_once(dirname(__FILE__).'/../classes/fnac.webservice.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.product.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.context.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.tools.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.address.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.order.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.cart.class.php');

class Fnac_Orders extends FNAC
{
    public function __construct()
    {
        parent::__construct();

        FNAC_Context::restore($this->context, Validate::isLoadedObject($this->context->shop) ? $this->context->shop : null);
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, 'orders'));
    }

    public function Orders()
    {
        $id_lang = $this->id_lang;

        $platform = Tools::getValue('platform');
        if (!in_array($platform, array('fr', 'es', 'pt', 'be'))) {
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
        //
        if ($platform == 'fr') {
            $flag = '';
        } else {
            $flag = Tools::strtoupper($platform).'_';
        }

        $partner_id = Configuration::get('FNAC_'.$flag.'PARTNER_ID');
        $shop_id = Configuration::get('FNAC_'.$flag.'SHOP_ID');
        $api_key = Configuration::get('FNAC_'.$flag.'API_KEY');
        $api_url = Configuration::get('FNAC_'.$flag.'API_URL');


        // Etats du produits (neuf occasion etc...)
        //
        $default_id = Configuration::get('FNAC_FEATURE_DEFAULT');
        $features_id = Configuration::get('FNAC_FEATURE_ID');

        $date1 = Tools::getValue('datepickerFrom');
        $date2 = Tools::getValue('datepickerTo');

        if (date('Ymd', strtotime(str_replace('-', '/', $date2))) >= date('Ymd') || empty($date2)) {
            $date1 = date('Y-m-d H:i:s', strtotime($date1.' 00:00:00'));
            $date2 = date('Y-m-d H:i:s', strtotime('now - 5 min'));
        } elseif (date('Ymd', strtotime($date1)) >= date('Ymd', strtotime($date2))) {
            $date1 = date('Y-m-d H:i:s', strtotime($date1.' 00:00:00'));
            $date2 = date('Y-m-d H:i:s', strtotime($date2.' 23:59:59'));
        } elseif ($date1 && $date2 && strtotime($date1) < time() && date('Ymd', strtotime($date2)) < date('Ymd')) {
            $date1 = date('Y-m-d H:i:s', strtotime($date1.' 00:00:00'));
            $date2 = date('Y-m-d H:i:s', strtotime($date2.' 23:59:59'));
        } else {
            $date1 = date('Y-m-d H:i:s', strtotime('yesterday midnight'));
            $date2 = date('Y-m-d H:i:s', strtotime('now - 5 min'));
        }

        $currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));

        $debug = (bool)Configuration::get('FNAC_DEBUG');

        if ($debug) {
            @ini_set('display_errors', 'on');
            @define('_PS_DEBUG_SQL_', true);
            @error_reporting(E_ALL | E_STRICT);
        }

        $fnac = new FnacAPI($partner_id, $shop_id, $api_key, $api_url, $debug);

        $status = (string)Tools::getValue('statuses');

        if (!$fnac->Login()) {
            echo $this->l('Unable to login').'<br />';
            die;
        }

        if (!$xml = $fnac->OrderQueryByDate($date1, $date2, $status)) {
            echo $this->l('Unable to Query the FNAC MarketPlace').'<br />';
            die;
        }

        // ddd($xml->asXML());

        if (!count($xml->order)) {
            echo '<h1>'.$this->l('No pending order for the selected period').'</h1>';
            die;
        }

        echo '<form name="orders"><table width="100%" class="table">';
        echo '<thead>
          <th><input type="checkbox" name="checkme" value="%s" /></th>'.
            '<th>'.$this->l('Date').'</th>'.
            '<th>'.$this->l('ID').'</th>'.
            '<th>'.$this->l('State').'</th>'.
            '<th>'.$this->l('Customer').'</th>'.
            '<th>'.$this->l('Quantity').'</th>'.
            '<th>'.$this->l('Products').'</th>'.
            '<th>'.$this->l('Shipping').'</th>'.
            '<th>'.$this->l('Total').'</th>'.
            '</thead>';


        $irow = 0;

        foreach ($xml as $key => $order) {
            $total = 0;
            $shipping = 0;
            $quantities = 0;
            $fees = (float)$order->fees;
            $total = (float)$order->OrderTotalAmount;
            $quantities = (int)$order->NumberOfItemsUnshipped;
            $retrieved = FNAC_Order::checkByMpId($order->order_id) ? true : false;

            if ($retrieved) {
                $disabled = ' disabled="disabled" ';
            } else {
                $disabled = '';
            }

            // Fix missing lastname and firstname
            if ((string)$order->client_lastname == '') {
                $order->client_lastname = $order->shipping_address->lastname;
            }
            if ((string)$order->client_firstname == '') {
                $order->client_firstname = $order->shipping_address->firstname;
            }

            foreach ($order->order_detail as $details) {
                $total += (float)$details->price * $details->quantity;
                $shipping += (float)$details->shipping_price;
                $fees += (float)$details->fees;
                $quantities += $details->quantity;
            }
            $class = ($irow++ % 2 ? 'alt_row' : '');
            printf('
       <tr class="'.$class.'">
        <td>
          <input type="checkbox" id="order_'.$irow.'" '.$disabled.' name="order_id[]" value="%s" />
        </td>
        <!-- created_at -->
        <td>%s</td>
        <!-- order_id -->
        <td class="order_id">%s</td>
        <!-- state -->
        <td>%s</td>
        <!-- customer -->
        <td>%s</td>
        <!-- quantities -->
        <td align="right">%d</td>
        <!-- products -->
        <td align="right">%s</td>
        <!-- shipping -->
        <td align="right">%s</td>
        <!-- total -->
        <td align="right">%s</td>
       </tr>
        ', $order->order_id, FNAC_Tools::displayDate(date('Y-m-d H:i:s', strtotime($order->created_at)), $id_lang, true), $order->order_id, $order->state, $order->client_firstname.' '.$order->client_lastname, $quantities, FNAC_Tools::displayPrice($total, $currency), FNAC_Tools::displayPrice($shipping, $currency), FNAC_Tools::displayPrice($total + $shipping, $currency)
            );

            if (($xmlDetails = $fnac->OrderQueryById($order->order_id))) {
                if (isset($xmlDetails->order->order_detail)) {
                    $orderDetails = null;

                    foreach ($xmlDetails->order->order_detail as $key => $product) {
                        $orderDetails .= sprintf('%s x %s (%s) - %s'."<br />\n", (int)$product->quantity, (string)$product->product_name, (string)$product->offer_seller_id, FNAC_Tools::displayPrice((float)$product->price)
                        );
                    }

                    // Order Details
                    printf('
                    <tr class="'.$class.'">
                        <td>&nbsp;</td><td>&nbsp;</td><td colspan="7"><em>%s</em></td>
                    </tr>  ', $orderDetails
                    );
                }
            }
        }

        echo '</table></form>
    <br />
    <input type="button" class="button btn btn-default" id="retrieve" value="'.$this->l('Retrieve Selected Orders').'" />
    ';
    }
}

$fnac = new Fnac_Orders();
$fnac->Orders();
