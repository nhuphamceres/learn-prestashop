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

class PriceMinisterPaymentModule extends PaymentModule
{

    public $name = 'priceminister';

    public function validateMarketplaceOrder($id_cart, $id_order_state, $paymentMethod = 'Unknown', $mpOrderId = null, $mpOrderStatus = null, $mpShipping = null, $mpItems = null, $pmCart = null, $useTaxes = false, $date_add = false)
    {
        // Copying data from cart
        $order = new PriceMinisterOrder();

        $order->id_carrier = (int)$pmCart->id_carrier;
        $order->id_customer = (int)$pmCart->id_customer;
        $order->id_address_invoice = (int)$pmCart->id_address_invoice;
        $order->id_address_delivery = (int)$pmCart->id_address_delivery;
        $order->id_currency = (int)$pmCart->id_currency;
        $order->id_lang = (int)$pmCart->id_lang;
        $order->id_cart = (int)$pmCart->id;

        $customer = new Customer((int)$order->id_customer);

        if (Validate::isLoadedObject($customer)) {
            if (!Validate::isLoadedObject($customer)) {
                die(Tools::displayError('Customer is wrong.'));
            }
        }

        $order->secure_key = pSQL($customer->secure_key);
        if (!$order->secure_key) {
            $order->secure_key = md5(rand());
        }
        $order->send_email = false;
        $order->payment = Tools::substr($paymentMethod, 0, 32);
        $order->module = $this->name;
        $order->recyclable = (bool)Configuration::get('PS_RECYCLABLE_PACK');
        $order->gift = (bool)$pmCart->gift;
        $order->gift_message = $pmCart->gift_message;

        $order->total_products = (float)$pmCart->getOrderTotal(false, 1);
        $order->total_products_wt = (float)$pmCart->getOrderTotal($useTaxes, 1);
        $order->total_discounts = (float)abs($pmCart->getOrderTotal(false, 2));
        $order->total_shipping = (float)$pmCart->getOrderTotal($useTaxes, 5);
        $order->total_wrapping = (float)abs($pmCart->getOrderTotal(false, 6));
        $order->total_paid_real = (float)$pmCart->getOrderTotal($useTaxes, 3);
        $order->total_paid = (float)$pmCart->getOrderTotal($useTaxes, 3);

        $order->id_order_state = $id_order_state;
        $order->shipping_number = '';
        $order->delivery_number = 0;
        $order->exported = '';
        $order->carrier_tax_rate = 0;

        // Modif YB : ajout de la tva sur le transporteur
        if ($useTaxes) {
            // If PS1.4>
            if (method_exists('Tax', 'getCarrierTaxRate')) {
                $order->carrier_tax_rate = (float)Tax::getCarrierTaxRate($pmCart->id_carrier, (int)$order->id_address_delivery);
            }
        }

        $id_warehouse = null;

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $order->reference = Order::generateReference();

            $order->total_paid_tax_excl = (float)$pmCart->getOrderTotal(false, 3);
            $order->total_paid_tax_incl = (float)$pmCart->getOrderTotal(true, 3);

            $order->total_shipping_tax_excl = (float)$pmCart->getOrderTotal(false, 5);
            $order->total_shipping_tax_incl = (float)$pmCart->getOrderTotal(true, 5);

            $order->total_paid_real = 0;

            $order->current_state = (int)$id_order_state;

            if (is_numeric(PriceMinisterOrderImport::$id_warehouse) && PriceMinisterOrderImport::$id_warehouse) {
                $id_warehouse = PriceMinisterOrderImport::$id_warehouse;
            }

            if (Validate::isLoadedObject($this->context->shop)) {
                $shop = $this->context->shop;
                $order->id_shop = $shop->id;
                $order->id_shop_group = $shop->id_shop_group;
            } else {
                $order->id_shop = 1;
                $order->id_shop_group = 1;
            }
        } else {
            $order->id_shop = 1;
        }
        if ($date_add) {
            $order->date_add = $date_add;
            $order->date_upd = $date_add;
            $autodate = false;
        } else {
            $autodate = true;
        }

        if (!Validate::isLoadedObject($pmCart)) {
            die(Tools::displayError('RakutenFrance Cart is wrong.'));
        }

        $null_date = '0000-00-00 00:00:00';
        $order->invoice_date = $null_date;
        $order->delivery_date = $null_date;

        $currency = new Currency($pmCart->id_currency);
        $order->conversion_rate = $currency->conversion_rate ? $currency->conversion_rate : 1;

        if (!($products = $pmCart->getProducts())) {
            echo Tools::displayError('Unable to get product from cart.');

            return (false);
        }

        // Prevent to import ducplicate order
        usleep(rand(1000, 10000));

        // Verify again if the order is already imported
        if ($order->checkByMpId($mpOrderId)) {
            printf('<br /><span style="color:red;">'.$this->l('Order ID (%s) Was already imported...').'</span><hr />', $mpOrderId);

            return (false);
        }

        // ADD PriceMinister Order
        $order->add($autodate, null, $mpOrderId, $mpOrderStatus, $mpShipping);

        // Next !
        if (Validate::isLoadedObject($order)) {
            $outOfStock = false;
            foreach ($products as $product) {
                $id_product = (int)$product['id_product'];
                $id_product_attribute = $product['id_product_attribute'] ? (int)$product['id_product_attribute'] : null;

                // Dummy Products handling
                $update_stocks = true;

                if (version_compare(_PS_VERSION_, '1.5', '<')) {
                    $productQuantity = (int)Product::getQuantity($id_product, $id_product_attribute);
                    $quantityInStock = ($productQuantity - (int)$product['cart_quantity'] < 0) ? $productQuantity : (int)$product['cart_quantity'];

                    if ($update_stocks) {
                        if ((($updateResult = Product::updateQuantity($product)) === false || $updateResult === -1)) {
                            $outOfStock = true;
                        }
                    }

                    if ($id_product_attribute) {
                        $product['quantity_attribute'] -= $product['cart_quantity'];
                    }
                    $product['stock_quantity'] -= $product['cart_quantity'];

                    if ($product['stock_quantity'] < 0) {
                        $product['stock_quantity'] = 0;
                    }

                    if ($update_stocks && version_compare(_PS_VERSION_, '1.4', '<')) {
                        @Hook::updateQuantity($product, $order);
                    }
                } else {
                    $productQuantity = Product::getRealQuantity($id_product, $id_product_attribute, $id_warehouse, $order->id_shop);
                    $quantityInStock = $productQuantity - $product['cart_quantity'];

                    // updates stock in shops PS 1.5

                    if ($update_stocks) {
                        StockAvailable::updateQuantity($id_product, $id_product_attribute, $product['cart_quantity'] * -1, $order->id_shop);
                    }
                }
                $itemid = null;
                foreach ($pmCart->pmProducts as $item) {
                    if ($item['id_product'] == $id_product && $item['id_attribute'] == $id_product_attribute) {
                        $itemid = $item['itemid'];
                        break;
                    }
                }

                // default taxes informations
                $product['id_tax'] = 0;
                $product['tax'] = null;
                $product['rate'] = 0;

                // Include VAT
                if (!Tax::excludeTaxeOption() || $useTaxes == true) {
                    if (isset($pmCart->pmProducts[$itemid]['tax_rate']) && $pmCart->pmProducts[$itemid]['tax_rate']) {
                        $tax_rate = $pmCart->pmProducts[$itemid]['tax_rate'];

                        $taxes = Tax::getTaxes($order->id_lang);
                        foreach ($taxes as $tax) {
                            if ((float)$tax['rate'] == (float)$tax_rate) {
                                $product['id_tax'] = $tax['id_tax'];
                                $product['tax'] = $tax['name'];
                                $product['rate'] = $tax_rate;
                            }
                        }
                    }
                }
                $quantity = (int)$product['cart_quantity'];

                $unit_price = $pmCart->pmProducts[$itemid]['price'] / $pmCart->pmProducts[$itemid]['qty'];
                $unit_price_tax_incl = (float)$unit_price;
                $unit_price_tax_excl = (float)Tools::ps_round($unit_price_tax_incl / (1 + ($product['rate'] / 100)), 2);

                $total_price_tax_incl = (float)Tools::ps_round($unit_price_tax_incl, 2) * $quantity;
                $total_price_tax_excl = (float)Tools::ps_round($unit_price_tax_excl, 2) * $quantity;

                $taxes = (float)Tools::ps_round($total_price_tax_incl - $total_price_tax_excl, 2);

                $product_name = $product['name'].((isset($product['attributes']) && $product['attributes'] != null) ? ' - '.$product['attributes'] : '');

                //
                // Order Detail entry
                //
                $order_detail = new OrderDetail;

                // order details
                $order_detail->id_order = (int)$order->id;

                // product informations
                $order_detail->product_name = $product_name;
                $order_detail->product_id = $id_product;
                $order_detail->product_attribute_id = $id_product_attribute;

                // quantities
                $order_detail->product_quantity = (int)$product['cart_quantity'];
                $order_detail->product_quantity_in_stock = (int)$quantityInStock;

                // product references
                $order_detail->product_price = (float)$unit_price_tax_excl;
                $order_detail->product_ean13 = $product['ean13'] ? $product['ean13'] : null;
                $order_detail->product_reference = $product['reference'];
                $order_detail->product_supplier_reference = $product['supplier_reference'] ? $product['supplier_reference'] : null;
                $order_detail->product_weight = (float)Tools::ps_round($product['id_product_attribute'] ? $product['weight_attribute'] : $product['weight'], 2);

                // taxes
                $order_detail->tax_name = $product['tax'];
                $order_detail->tax_rate = (float)$product['rate'];
                $order_detail->ecotax = $product['ecotax'];
                if (version_compare(_PS_VERSION_, '1.5', '<')) {
                    $order_detail->ecotax = 0.0;
                }

                // For PS 1.4
                $order_detail->download_deadline = $null_date;

                // For PS 1.5+
                // price details
                $order_detail->total_price_tax_incl = (float)$total_price_tax_incl;
                $order_detail->total_price_tax_excl = (float)$total_price_tax_excl;
                $order_detail->unit_price_tax_incl = (float)$unit_price_tax_incl;
                $order_detail->unit_price_tax_excl = (float)$unit_price_tax_excl;

                // shop and warehouse
                $order_detail->id_shop = (int)$order->id_shop;
                $order_detail->id_warehouse = (int)$id_warehouse;

                // add into db
                $order_detail->add();

                if (!Validate::isLoadedObject($order_detail)) {
                    print(Tools::displayError('OrderDetail::add() - Failed'));
                    echo mysql_error();
                    die;
                }

                /*
                 * Fill price minister ordered items table
                 */
                $order->addMarketplaceItem($id_product, $id_product_attribute, $itemid);

                /*
                 * Fill order_detail_tax PS1.5 table
                 * Where is the OrderDetailTax object in PS 1.5 ??? !!!
                 */

                if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                    $id_order_detail = $order_detail->id;

                    $tax_query = 'INSERT INTO `'._DB_PREFIX_.'order_detail_tax` (id_order_detail, id_tax, unit_amount, total_amount) VALUES '.
                        sprintf('(%d, %d, %f, %f) ;', $id_order_detail, $product['id_tax'], $total_price_tax_excl, $taxes);

                    if (!($tax_result = Db::getInstance()->Execute($tax_query))) {
                        echo nl2br(print_r($tax_query, true));
                        print(Tools::displayError('Failed to add tax details.'));
                        die(mysql_error());
                    }
                }
            } // end foreach ($products)

            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                // Adding an entry in order_carrier table
                if ($order->id_carrier) {
                    $order_carrier = new OrderCarrier();
                    $order_carrier->id_order = (int)$order->id;
                    $order_carrier->id_carrier = $order->id_carrier;
                    $order_carrier->weight = (float)$order->getTotalWeight();
                    $order_carrier->shipping_cost_tax_excl = $order->total_shipping_tax_excl;
                    $order_carrier->shipping_cost_tax_incl = $order->total_shipping_tax_incl;
                    $order_carrier->add();
                }
            }
            // New Order Status
            $orderStatus = new OrderState((int)$id_order_state);

            // Hook New Order
            if (Validate::isLoadedObject($orderStatus)) {
                if (version_compare(_PS_VERSION_, '1.5', '<')) {
                    @Hook::newOrder($pmCart, $order, $customer, $currency, $orderStatus);
                } else {
                    // Hook validate order
                    Hook::exec('actionValidateOrder', array(
                        'cart' => $pmCart,
                        'order' => $order,
                        'customer' => $customer,
                        'currency' => $currency,
                        'orderStatus' => $orderStatus
                    ));
                }
                foreach ($pmCart->getProducts() as $product) {
                    if ($orderStatus->logable) {
                        ProductSale::addProductSale((int)$product['id_product'], (int)$product['cart_quantity']);
                    }
                }
            }

            if (!Validate::isLoadedObject($order)) {
                echo Tools::displayError(sprintf('%s(#%d): %s', basename(__FILE__), __LINE__, 'Order creation failed.'));

                return (false);
            }

            $this->addToHistory($order->id, $id_order_state);

            // updates stock in shops
            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                if (StockAvailable::dependsOnStock($id_product)) {
                    foreach ($products as $key => $product) {
                        StockAvailable::synchronize((int)$product['id_product'], $order->id_shop);
                    }
                }
            }

            $this->currentOrder = (int)$order->id;

            return $this->currentOrder;
        } else {
            die(Tools::displayError('Order creation failed.'));
        }
    }

    private function addToHistory($id_order, $id_order_state)
    {
        $cookie = isset($this->context->cookie) ? $this->context->cookie : Context::getContext()->cookie;

        // ADD History
        $new_history = new PriceMinisterOrderHistory();
        $new_history->id_order = (int)$id_order;
        $new_history->id_employee = (int)$cookie->id_employee;
        $new_history->changeIdOrderState($id_order_state, $id_order);
        $new_history->addWithOutEmail(true);

        return;
    }
}