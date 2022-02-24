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
require_once(dirname(__FILE__).'/../classes/fnac.context.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.product.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.tools.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.address.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.order.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.orderhistory.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.cart.class.php');
require_once(dirname(__FILE__).'/../classes/fnac.payment.class.php');

if (Tools::getValue('debug')) {
    @ini_set('display_errors', 'on');
    @error_reporting(E_ALL | E_STRICT);
}

class Fnac_Import extends Fnac
{
    public function __construct()
    {
        parent::__construct();

        FNAC_Context::restore($this->context, Validate::isLoadedObject($this->context->shop) ? $this->context->shop : null);
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, 'import'));
    }

    public function fnacImport()
    {
        $debug = Tools::getValue('debug', Configuration::get('FNAC_DEBUG'));

        $cronMode = 0;
        $currentDate = date('Y-m-d H:i:s');

        $platform = Tools::getValue('platform');

        if ($platform == 'fr') {
            $flag = '';
        } else {
            $flag = Tools::strtoupper($platform).'_';
        }

        $previousDate = Configuration::get('FNAC_'.$flag.'_ORDERS_RETRIEVED');
        $fnac_id_lang = Language::getIdByIso($platform);
        //French might not be set as language for the shop...
        if (!$fnac_id_lang) {
            $default_lang = Language::getLanguages(true);
            $fnac_id_lang = (count($default_lang) > 0) ? $default_lang[0]['id_lang'] : 1;
        }


        $id_lang = $fnac_id_lang;

        if (Tools::getValue('cron')) {
            $cr = "<br />\n"; // carriage return
            $cronMode = 1;

            echo $this->l('Starting Order Query in WS API/Cron Mode').' - '.$currentDate.$cr;
            if (!$previousDate) {
                // first time ...
                $date1 = date('c', strtotime('today -10 day'));
            } else {
                $date1 = date('c', strtotime($previousDate) - (432000 * 2)); // - 5 * 2 days
            }

            $date2 = date('c', strtotime('now - 5 min'));

            echo "Date: $date1 - $date2".$cr;
        } else {
            $cr = '<br />'; // carriage return
            echo $this->l('Starting Order Query in WS API/Web Mode').' - '.$currentDate.$cr;
            $order_id = (string)Tools::getValue('order_id');
        }

        $partner_id = Configuration::get('FNAC_'.$flag.'PARTNER_ID');
        $shop_id = Configuration::get('FNAC_'.$flag.'SHOP_ID');
        $api_key = Configuration::get('FNAC_'.$flag.'API_KEY');
        $api_url = Configuration::get('FNAC_'.$flag.'API_URL');

        // Carriers
        //
        $carrier_20 = (int)Tools::stripslashes(Configuration::get('FNAC_CARRIER_20'));
        $carrier_21 = (int)Tools::stripslashes(Configuration::get('FNAC_CARRIER_21'));
        $carrier_22 = (int)Tools::stripslashes(Configuration::get('FNAC_CARRIER_22'));
        $carrier_55 = (int)Tools::stripslashes(Configuration::get('FNAC_CARRIER_55'));

        // TODO
        // Carrier envoimoinscher, force to MR pour le momment.
        // https://marketplace.ws.fd-recette.net/docs/api/2.6/services/type/state.html#shipping-method
        $carrier_63 = (int)Tools::stripslashes(Configuration::get('FNAC_CARRIER_55'));

        $order_id = Tools::getValue('order_id');
        $tokenOrder = Tools::getValue('token_order');

        // Acces WebService
        //
        $fnac = new FnacAPI($partner_id, $shop_id, $api_key, $api_url, $debug);

        if (!$fnac->Login()) {
            echo $this->l('Unable to login to the FNAC MarketPlace').'<br />';
            die;
        }

        $pass = true;

        if ($cronMode) {
            $FNACOrders = null;

            // if (!$FNACOrders = $fnac->OrderQueryByDate($date1, $date2, 'Created')) {
            if (!$FNACOrders = $fnac->OrderQueryByDate($date1, $date2)) {
                echo 'no order';
                $pass = false;
            }
        } else {
            $FNACOrders = array();
            $FNACOrders[] = (object)array('order_id' => $order_id);
        }

        $default_customer = new Customer(Configuration::get('FNAC_CUSTOMER_ID'));

        if (!Validate::isLoadedObject($default_customer)) {
            echo $this->l('Unable to load default Customer').'<br />';
            die;
        }

        if ($pass) {
            foreach ($FNACOrders as $FNACOrder) {
                $order_id = $FNACOrder->order_id;

                if (!$xml = $fnac->OrderQueryById($order_id)) {
                    echo $this->l('Unable to retrieve order id').' '.$order_id.'<br />';
                    die;
                }

                $order = $xml->order;
                $mpOrderId = (string)$order->order_id;

                if (FNAC_Order::checkByMpId($mpOrderId)) {
                    echo '<br /><span style="color:red;">';
                    printf($this->l('Order ID (%s) Was already imported...'), $mpOrderId);
                    echo '</span><hr />';
                    continue;
                }

                $fnac_address_iso3 = new FNAC_Address();
                if (array_key_exists((string)$order->shipping_address->country, $fnac_address_iso3->iso3)) {
                    $order->shipping_address->country = $fnac_address_iso3->iso3[(string)$order->shipping_address->country];
                }
                if (array_key_exists((string)$order->billing_address->country, $fnac_address_iso3->iso3)) {
                    $order->billing_address->country = $fnac_address_iso3->iso3[(string)$order->billing_address->country];
                }

                if (!Country::getByIso(Tools::substr((string)$order->shipping_address->country, 0, 2))) {
                    echo '<br /><span style="color:red;">';
                    printf($this->l('Unable to create customer, invalid country code').': %s (#%s)', (string)$order->shipping_address->country, __LINE__);
                    echo '</span><hr />';
                    continue;
                }

                if (!Country::getByIso(Tools::substr((string)$order->billing_address->country, 0, 2))) {
                    echo '<br /><span style="color:red;">';
                    printf($this->l('Unable to create customer, invalid country code').': %s (#%s)', (string)$order->billing_address->country, __LINE__);
                    echo '</span><hr />';
                    continue;
                }

                if (!isset($order->client_email) && isset($order->client_id)) {
                    $order->client_email = (string)$order->client_id.'@marketplace.fnac.com';
                }

                if (isset($order->client_email) && Validate::isEmail((string)$order->client_email)) {
                    $email_address = (string)$order->client_email;


                    $customer = new Customer();
                    $customer->getByEmail($email_address);

                    if (Validate::isLoadedObject($customer)) {
                        $id_customer = $customer->id;
                    } else {
                        // Fix missing lastname and firstname
                        if ((string)$order->client_lastname == '') {
                            $order->client_lastname = $order->shipping_address->lastname;
                        }
                        if ((string)$order->client_firstname == '') {
                            $order->client_firstname = $order->shipping_address->firstname;
                        }

                        $customer->firstname = FNAC_Address::cleanLogin((string)$order->client_firstname);
                        $customer->lastname = FNAC_Address::cleanLogin((string)$order->client_lastname);

                        $customer->email = $email_address;
                        $customer->passwd = md5(rand());

                        $customer->optin = false;
                        $customer->newsletter = false;

                        if (!$customer->add()) {
                            echo '<br /><span style="color:red;">';
                            printf($this->l('Couldn\'t add this customer').': %s', (string)$email_address);
                            echo '</span><hr />';
                            continue;
                        } else {
                            $id_customer = $customer->id;
                        }
                    }
                } else {
                    $id_customer = $default_customer->id;
                }

                // FROM FNAC to PRESTASHOP !
                //
                $shipping_address = new FNAC_Address();
                $shipping_address->id_customer = $id_customer;
                $shipping_address_id = $shipping_address->lookupOrCreateFnacAddress($order->shipping_address);

                $billing_address = new FNAC_Address();
                $billing_address->id_customer = $id_customer;
                $billing_address_id = $billing_address->lookupOrCreateFnacAddress($order->billing_address);

                $created_at_time = strtotime($order->created_at);
                $created_at = FNAC_Tools::displayDate(date('Y-m-d H:i:s', strtotime($order->created_at)), $id_lang, true);
                $fees = (float)$xml->order->fees;
                $irow = 0;
                $shipping_price = 0;

                if (!$cronMode) {
                    echo '<h3>'.$this->l('Order ID').': '.$order->order_id.'</h3>';

                    echo '<table width="100%" class="table">';

                    echo '<thead>
                <th><input type="checkbox" name="checkme" value="%s" /></th>'.
                        '<th>'.$this->l('Reference').'</th>'.
                        '<th>'.$this->l('Product Name').'</th>'.
                        '<th>'.$this->l('Shipping').'</th>'.
                        '<th>'.$this->l('Qty').'</th>'.
                        '<th>'.$this->l('Price').'</th>'.
                        '<th>'.$this->l('Shipping').'</th>'.
                        '<th>'.$this->l('Total').'</th>'.
                        '</thead>';
                } else {
                    echo $this->l('Order ID').': '.$order->order_id.$cr;
                    echo $this->l('Reference')."\t";
                    echo $this->l('Id FNAC')."\t";
                    echo $this->l('Product Name')."\t";
                    echo $this->l('Qty')."\t";
                    echo $this->l('Price')."\t";
                    echo $this->l('Total')."\t";
                }

                $itemDetails = array();

                // Building Cart
                //
                $cart = new FNAC_Cart();
                $cart->id_address_delivery = $shipping_address_id;
                $cart->id_address_invoice = $billing_address_id;

                $cart->id_currency = Currency::getIdByIsoCode('EUR');
                $cart->id_customer = $id_customer;
                $cart->id_lang = $id_lang;
                $cart->add();

                // %Y-%m-%d %H:%M:%S.

                foreach ($order->order_detail as $key => $products) {
                    $product_name = (string)$products->product_name;
                    $quantity = (int)$products->quantity;
                    $price = (float)$products->price;
                    // Fee is global, no need to duplicate it for each products, see previous $fees
//                    $fees += (float)$products->fees;
                    $shipping_price += (float)$products->shipping_price;
                    $shipping_method = (int)$products->shipping_method ? (int)$products->shipping_method : 0;
                    $offer_seller_id = (string)$products->offer_seller_id;

                    $product = FNAC_Product::getByReference($offer_seller_id);

                    // FIX Boutique mesvyniles.fr
                     $product = new FNAC_Product($offer_seller_id);
                     $offer_seller_id = FNAC_Product::getProductById($offer_seller_id);
                    // !FIX Boutique mesvyniles.fr

                    if (!isset($product) || !$product) {
                        echo $this->l('Cannot find product Reference').' : '.$offer_seller_id.'<br>';
                        continue(2);
                    } elseif (!Validate::isLoadedObject($product)) {
                        echo $this->l('Product cannot be loaded properly').' : '.$offer_seller_id.'<br>';
                        continue(2);
                    }
                    $productId = $product->id;

                    if (!$productId) {
                        echo '<br /><span style="color:red;">';
                        printf($this->l('Unknown error occurs while importing order #%s product : %s'), $order_id, $offer_seller_id);
                        echo '</span><hr />';
                        continue;
                    }

                    // Pas de stock - on restocke :/
                    if ($product->quantity <= 0) {
                        $product->quantity = ($product->quantity * 0) + $quantity;
                        $product->update();
                    } elseif ($product->quantity < $quantity) {
                        $product->quantity += $quantity - $product->quantity;
                        $product->update();
                    }

                    // Plusieurs articles avec la m???me r???f???rence
                    if (isset($itemDetails[$offer_seller_id])) {
                        $itemDetails[$offer_seller_id]['qty'] += $quantity;
                    } else {
                        $itemDetails[$offer_seller_id] =
                            array(
                                'id' => $productId,
                                'qty' => $quantity,
                                'price' => $price,
                                'name' => $product_name,
                                'shipping' => (float)$products->shipping_price,
                                'fees' => (float)$products->fees
                            );
                    }

                    // Tax Calculation - PS 1.4 sinon 1.3
                    //
                    if (method_exists('Tax', 'getProductTaxRate')) {
                        $product_tax_rate = (float)(Tax::getProductTaxRate($product->id, $shipping_address_id));
                    } else {
                        $product_tax_rate = (float)(Tax::getApplicableTax($product->id_tax, $product->tax_rate, $shipping_address_id));
                    }


                    // Modif YB
                    $itemDetails[$offer_seller_id]['tax_rate'] = $product_tax_rate;
                    $itemDetails[$offer_seller_id]['id_tax'] = isset($product->id_tax) ? $product->id_tax : false;
                    $itemDetails[$offer_seller_id]['id_product'] = $product->id;
                    $itemDetails[$offer_seller_id]['id_address_delivery'] = $shipping_address_id;

                    if ($cronMode) {
                        printf("%s\t%s\t%s\t%s\t%s\t%s\n", $offer_seller_id, $products->product_fnac_id, $product_name, $quantity, FNAC_Tools::displayPrice((float)$price * $quantity), FNAC_Tools::displayPrice(($price * $quantity) + (float)$products->shipping_price)
                        );
                    } else {
                        printf('
                        <tr class="'.($irow++ % 2 ? 'alt_row' : '').'">
                            <td>
                            <input type="checkbox" id="order_'.$irow.'" value="line_%s" />
                            </td>
                            <!-- offer_seller_id -->
                            <td>%s</td>
                            <!-- product_name -->
                            <td style="width:300px">%s</td>
                            <!-- shipping_method -->
                            <td>%s</td>
                            <!-- quantities -->
                            <td align="right">%s</td>
                            <!-- product price -->
                            <td align="right">%s</td>
                            <!-- shipping -->
                            <td align="right">%s</td>
                            <!-- total -->
                            <td align="right">%s</td>
                        </tr>
                            ', $irow, $offer_seller_id, $product_name, $shipping_method, $quantity, FNAC_Tools::displayPrice((float)$price), FNAC_Tools::displayPrice((float)$products->shipping_price), FNAC_Tools::displayPrice(($price * $quantity) + (float)$products->shipping_price)
                        );
                    }

                    // Product Combinations
                    //
                    if (version_compare(_PS_VERSION_, '1.5', '<')) {
                        $combinations = $product->getAttributeCombinaisons($id_lang);
                    } else {
                        $combinations = $product->getAttributeCombinations($id_lang);
                    }

                    $id_product_attribute = 0;

                    if ($combinations) {
                        foreach ($combinations as $key => $combination) {
                            if ($combination['reference'] == $offer_seller_id) {
                                $id_product_attribute = $combination['id_product_attribute'];
                            }
                        }
                    }

                    if (version_compare(_PS_VERSION_, '1.5', '<')) {
                        $productQuantity = Product::getQuantity((int)$productId, $id_product_attribute ? $id_product_attribute : null);
                    } else {
                        $productQuantity = StockAvailable::getQuantityAvailableByProduct($productId, $id_product_attribute ? $id_product_attribute : null);
                    }

//                    if ($productQuantity - $quantity < 0) {
//                        printf('%s Product FNAC ID: %s SKU: %s'.$cr, $this->l('Not enough stock to import this product'), $products->product_fnac_id, $offer_seller_id);
//                        unset($itemDetails[$offer_seller_id]);
//                        continue;
//                    }
                    
                    if ($cart->updateQty($quantity, $productId, $id_product_attribute) < 0) {
                        echo $this->l('Couldn\'t update cart quantity: not enough stock ?').' (FNAC:'.$products->product_fnac_id.' SKU:'.$offer_seller_id.')'.$cr;
                        unset($itemDetails[$offer_seller_id]);
                        continue;
                    }
                }

                if (!count($itemDetails)) {
                    echo $this->l('Empty cart, cannot continue').$cr;
                    if (Validate::isLoadedObject($cart)) {
                        $cart->delete();
                    }
                    continue;
                }

                $cart->update();

                if (!$cronMode) {
                    echo '</table>';
                }

                switch ((int)$shipping_method) {
                    case 20 :
                        $cart->id_carrier = $carrier_20;
                        break;
                    case 21 :
                        $cart->id_carrier = $carrier_21;
                        break;
                    case 22 :
                        $cart->id_carrier = $carrier_22;
                        break;
                    case 55 :
                    case 63:
                        // Todo 63 forced to MR
                        $cart->id_carrier = $carrier_55;
                        break;
                    default :
                        $cart->id_carrier = $carrier_20;
                }


                // State Number
                //
                $mpStatusId = constant('FnacAPI::'.$order->state);

                // Order States / PS State & MP State
                //
                $statuses = unserialize(Configuration::get('FNAC_STATUSES_MAP'));
                $mpStatus = constant('FnacAPI::'.$order->state);

                if (isset($statuses[$mpStatusId])) {
                    $psStatus = $statuses[$mpStatusId];
                } else {
                    //				$psStatus = _PS_OS_PREPARATION_;
                    printf($this->l('The status "%s" has not been mapped for the order #%s'), $order->state, $order_id).$cr;
                    if (Validate::isLoadedObject($cart)) {
                        $cart->delete();
                    }
                    continue;
                }

                if (!(int)$psStatus) {
                    printf('%s'.$cr, $this->l('You must configure all the orders statuses'));
                    if (Validate::isLoadedObject($cart)) {
                        $cart->delete();
                    }
                    continue;
                }


                // Using price, shipping details etc... from the Market Place
                $cart->fnacProducts = $itemDetails;
                $cart->fnacShipping = $shipping_price;
                $cart->fnacFees = $fees;

                // duplication du panier, important !!!
                $fcart = $cart;
                $order_id = (string)$order->order_id;
                $payment = new FNAC_PaymentModule();

                if ($newOrderId = $payment->validateMarketplaceOrder($cart->id, $psStatus, 'FNAC MarketPlace', $order_id, $mpStatusId, $fcart, true)) {
                    echo '<br />';
                    printf('<span style="color:green;display:inline-block;margin-bottom:5px;">'.$this->l('Order ID %s(%s) Successfully imported in the Order Tab').'</span><br />', $order_id, $newOrderId);
                    printf('%s: <img src="%sdetails.gif" style="cursor:pointer;" onclick="window.open(\'?tab=AdminOrders&id_order=%s&vieworder&token=%s\')">', $this->l('Go to the order in a new window'), $this->images, $newOrderId, $tokenOrder);

                    echo '<hr />';

                    if (in_array(_DB_PREFIX_.'marketplace_orders', FNAC_Tools::getTables())) {
                        Db::getInstance()->insert(
                            'marketplace_orders',
                            array(
                                'id_order' => (int)$newOrderId,
                                'mp_order_id' => pSQL($mpOrderId)
                            )
                        );
                    }

                    $fnac->OrderAccept($order_id, $platform);
                } else {
                    if (Validate::isLoadedObject($cart)) {
                        $cart->delete();
                    }

                    echo '<br /><span style="color:red;">';
                    printf($this->l('Unknown error occurs while importing order #%s'), $order_id);
                    echo '</span><hr />';
                }
            }
        }

        Configuration::updateValue('FNAC_'.$flag.'_ORDERS_RETRIEVED', date('c'));
    }
}

$fnac = new Fnac_Import();
$fnac->fnacImport();
