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

if (!defined('_PS_VERSION_')) {
    exit;
}

if (!class_exists('MiraklPaymentModule')) {
    class MiraklPaymentModule extends PaymentModule
    {
        public $name = 'mirakl';

        /**
         * Validate an order in database
         * Function called from a payment module
         * @param int $id_cart
         * @param int $id_order_state
         * @param float $amount_paid
         * @param string $payment_method
         * @param null $message
         * @param array $extra_vars
         * @param null $currency_special
         * @param bool $dont_touch_amount
         * @param bool $secure_key
         * @param Shop|null $shop
         * @param null $me_order_id
         * @param null $me_order_status
         * @param null|MiraklCart $me_cart
         * @return bool|int
         * @throws PrestaShopDatabaseException
         * @throws PrestaShopException
         */
        public function validateOrder(
            $id_cart,
            $id_order_state,
            $amount_paid,
            $payment_method = 'Unknown',
            $message = null,
            $extra_vars = array(),
            $currency_special = null,
            $dont_touch_amount = true,
            $secure_key = false,
            Shop $shop = null,
            $me_order_id = null,
            $me_order_status = null,
            $me_cart = null,
            $shippingDeadline = null
        ) {
            if (version_compare(_PS_VERSION_, '1.5', '<')) {
                $id_shop = 1;
                $id_warehouse = null;
            } else {
                if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                    $id_warehouse = Mirakl::getConfig(Mirakl::CONFIG_WAREHOUSE);
                }

                if (empty($id_warehouse) || !is_numeric($id_warehouse)) {
                    $id_warehouse = null;
                }

                if (Shop::isFeatureActive()) {
                    $id_shop = (int)Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
                } else {
                    $id_shop = 1;
                }
            }

            // Copying data from cart
            $order = new MiraklOrder();

            $order->date_add = $me_cart->me_date;
            $order->date_upd = date('Y-m-d H:i:s');

            $order->id_shop_group = (int)$me_cart->id_shop_group;
            $order->id_shop = (int)$me_cart->id_shop;

            $order->id_carrier = (int)$me_cart->id_carrier;
            $order->id_customer = (int)$me_cart->id_customer;
            $order->id_address_invoice = (int)$me_cart->id_address_invoice;
            $order->id_address_delivery = (int)$me_cart->id_address_delivery;
            $order->id_currency = ($currency_special ? (int)$currency_special : (int)$me_cart->id_currency);
            $order->id_lang = (int)$me_cart->id_lang;
            $order->id_cart = (int)$me_cart->id;
            $customer = new Customer((int)$order->id_customer);
            $order->secure_key = pSQL($customer->secure_key);

            if (!$order->secure_key) {
                $order->secure_key = md5(time());
            }

            $order->payment = Tools::ucfirst(Tools::strtolower(Tools::substr($payment_method, 0, 32)));
            $order->module = $this->name;
            $order->recyclable = (bool)Configuration::get('PS_RECYCLABLE_PACK');
            $order->gift = (int)$me_cart->gift;
            $order->gift_message = $me_cart->gift_message;

            $order->total_products = (float)$me_cart->getMiraklOrderTotal(false, 1);
            $order->total_products_wt = (float)$me_cart->getMiraklOrderTotal(true, 1);
            $order->total_discounts = (float)abs($me_cart->getMiraklOrderTotal(false, 2));
            $order->total_shipping = (float)$me_cart->getMiraklOrderTotal(true, 5);
            $order->total_wrapping = (float)abs($me_cart->getMiraklOrderTotal(false, 6));
            $order->total_paid_real = (float)$me_cart->getMiraklOrderTotal(true, 3);
            $order->total_paid = (float)$me_cart->getMiraklOrderTotal(true, 3);
            $order->carrier_tax_rate = $me_cart->marketplaceGetCarrierTaxRate() ?: 20;

            $null_date = '0000-00-00 00:00:00';
            $order->invoice_date = $null_date;
            $order->delivery_date = $null_date;

            $id_warehouse = (int)Mirakl::getConfig(Mirakl::CONFIG_WAREHOUSE);

            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                $order->reference = Order::generateReference();
                // $order->total_paid_real = 0; // Why set to 0 ?
                $order->total_paid_tax_excl = (float)$me_cart->getMiraklOrderTotal(false, 3);
                $order->total_paid_tax_incl = (float)$me_cart->getMiraklOrderTotal(true, 3);
                $order->total_shipping_tax_excl = (float)$me_cart->getMiraklOrderTotal(false, 5);
                $order->total_shipping_tax_incl = (float)$me_cart->getMiraklOrderTotal(true, 5);
                $order->current_state = (int)$id_order_state;

                $this->context = Context::getContext();
            } else {
                $order->id_shop = 1;
            }

            $currency = new Currency($currency_special);
            $order->conversion_rate = $currency->conversion_rate;

            if (!$order->total_products) {
                ob_start();
                echo "Order : \n";
                var_dump($me_cart->me_products);
                var_dump($order);
                $dump = ob_get_clean();
                echo $this->l('Unable to import an empty order...')."\n".$dump."\n";

                return false;
            }

            if (!($products = $me_cart->getProducts())) {
                echo Tools::displayError('Unable to get product from cart.');

                return false;
            }

            if (MiraklOrder::checkByMpId($me_order_id, $order->id_shop)) {
                echo Tools::displayError('Order already imported.');

                return false;
            }

            $order->marketplace_order_id = $me_order_id;
            $order->marketplace_channel = Tools::strtolower($me_cart->marketplace_key);
            $order->shipping_deadline = $shippingDeadline;
            $order->add();
            $order->addMarketplaceDetails();

            if (Validate::isLoadedObject($order)) {
                $update_stocks = true;

                foreach ($products as $product) {
                    $id_product = (int)$product['id_product'];
                    $id_product_attribute = $product['id_product_attribute'] ? (int)$product['id_product_attribute'] : null;

                    if (version_compare(_PS_VERSION_, '1.5', '<')) {
                        $product_quantity = (int)Product::getQuantity($id_product, $id_product_attribute);
                        $quantity_in_stock = ($product_quantity - (int)$product['cart_quantity'] < 0) ? $product_quantity : (int)$product['cart_quantity'];

                        if ($update_stocks) {
                            Product::updateQuantity($product);
                        }

                        if ($id_product_attribute) {
                            $product['quantity_attribute'] -= $product['cart_quantity'];
                        }

                        $product['stock_quantity'] -= $product['cart_quantity'];

                        if ($product['stock_quantity'] < 0) {
                            $product['stock_quantity'] = 0;
                        }

                        if ($update_stocks && version_compare(_PS_VERSION_, '1.4', '<')) {
                            Hook::updateQuantity($product, $order);
                        }
                    } else {
                        $product_quantity = Product::getRealQuantity($id_product, $id_product_attribute, $id_warehouse, $id_shop);
                        $quantity_in_stock = $product_quantity - $product['cart_quantity'];

                        // updates stock in shops PS 1.5
                        if ($update_stocks) {
                            StockAvailable::updateQuantity($id_product, $id_product_attribute, $product['cart_quantity'] * -1);
                        }
                    }

                    $quantity = (int)$product['cart_quantity'];
                    $product_identifier = sprintf('%d_%d', $id_product, $id_product_attribute);
                    // if the product does not exist in me_products, it's a gift product so the price must be 0
                    $unitprice = isset($me_cart->me_products[$product_identifier]['price']) ? $me_cart->me_products[$product_identifier]['price'] : 0;

                    if (!$unitprice && !$me_cart->has_gift_product) {
                        ob_start();
                        echo 'ID Product : '.$id_product."\n";
                        var_dump($me_cart->me_products);
                        $dump = ob_get_clean();
                        echo $this->l('Product price is zero or null...')."\n".$dump;

                        return false;
                    }

                    // default taxes information
                    $product['id_tax'] = 0;
                    $product['tax'] = null;
                    $product['rate'] = 0;

                    // Include VAT
                    if (!Tax::excludeTaxeOption()) {
                        if (isset($me_cart->me_products[$product_identifier]['tax_rate']) && $me_cart->me_products[$product_identifier]['tax_rate']) {
                            $tax_rate = $me_cart->me_products[$product_identifier]['tax_rate'];
                            $taxes = Tax::getTaxes($order->id_lang);

                            foreach ($taxes as $tax) {
                                if ((float)$tax['rate'] == (float)$tax_rate) {
                                    $product['id_tax'] = $tax['id_tax'];
                                    $product['tax'] = $tax['name'];
                                    $product['rate'] = $tax_rate;
                                    break;
                                }
                            }
                        }
                    }

                    $unit_price_tax_excl = (float)Tools::ps_round($unitprice / (1 + ($product['rate'] / 100)), 2);
                    $unit_price_tax_incl = (float)$unitprice;

                    $total_price_tax_incl = (float)Tools::ps_round($unit_price_tax_incl, 2) * $quantity;
                    $total_price_tax_excl = (float)Tools::ps_round($unit_price_tax_excl, 2) * $quantity;

                    $taxes = (float)Tools::ps_round(($unit_price_tax_incl - $unit_price_tax_excl) * $quantity, 2);

                    $product_name = $product['name'].((isset($product['attributes']) && $product['attributes'] != null) ? ' - '.$product['attributes'] : '');

                    // Order Detail entry
                    $order_detail = new OrderDetail;

                    // order details
                    $order_detail->id_order = (int)$order->id;

                    // product informations
                    $order_detail->product_name = $product_name;
                    $order_detail->product_id = $id_product;
                    $order_detail->product_attribute_id = $id_product_attribute;

                    // quantities
                    $order_detail->product_quantity = (int)$product['cart_quantity'];
                    $order_detail->product_quantity_in_stock = (int)$quantity_in_stock;

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

                    // For PS 1.4
                    $order_detail->download_deadline = $null_date;

                    // For PS 1.5+
                    // price details
                    $order_detail->total_price_tax_incl = (float)$total_price_tax_incl;
                    $order_detail->total_price_tax_excl = (float)$total_price_tax_excl;
                    // $unit_price_tax_incl is not rounded yet. Excess of 9 decimal digits will make validation failed
                    $order_detail->unit_price_tax_incl = (float)Tools::ps_round($unit_price_tax_incl, 9);
                    $order_detail->unit_price_tax_excl = (float)$unit_price_tax_excl;

                    // shop
                    if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                        $order_detail->id_shop = (int)$id_shop;
                        $order_detail->id_warehouse = (int)$id_warehouse;
                    }

                    // add into db
                    $order_detail->add();

                    if (!Validate::isLoadedObject($order_detail)) {
                        print(Tools::displayError('OrderDetail::add() - Failed'));
                        echo mysql_error();
                        die;
                    }

                    /*
                    * Fill order_detail_tax PS1.5 table
                    * Where is the OrderDetailTax object in PS 1.5 ??? !!!
                    */

                    if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                        $id_order_detail = $order_detail->id;

                        $tax_query = 'INSERT INTO `'._DB_PREFIX_.'order_detail_tax` (id_order_detail, id_tax, unit_amount, total_amount) VALUES '.sprintf('(%d, %d, %f, %f) ;', $id_order_detail, $product['id_tax'], $total_price_tax_excl, (float)Tools::ps_round(($total_price_tax_incl - $total_price_tax_excl), 2));

                        if (!Db::getInstance()->Execute($tax_query)) {
                            echo nl2br(print_r($tax_query, true));
                            print(Tools::displayError('Failed to add tax details.'));
                            die();
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

                if (!isset($this->context->employee->id)) {
                    $id_employee = Context::getContext()->employee->id;
                } else {
                    $id_employee = $this->context->employee->id;
                }

                $new_history = new MiraklOrderHistory();
                $new_history->id_order = (int)$order->id;
                $new_history->id_employee = (int)$id_employee ? $id_employee : 1;
                $new_history->changeIdOrderState($id_order_state, $order->id);
                $new_history->addWithOutEmail(true, $extra_vars);

                // Order is reloaded because the status just changed
                $order = new Order($order->id);

                if (!Validate::isLoadedObject($order)) {
                    print(Tools::displayError(sprintf('%s(#%d): %s', basename(__FILE__), __LINE__, 'Order creation failed.')));
                    return (false);
                }

                // Execute hook after change state (setInvoice).
                // To compatible with different module (ps_emailalerts + send mail extension).

                // Hook new order
                $this->hookActivation($id_order_state, $me_cart, $order, $customer, $currency);

                // updates stock in shops
                if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                    if (StockAvailable::dependsOnStock($id_product)) {
                        foreach ($products as $product) {
                            StockAvailable::synchronize((int)$product['id_product'], $order->id_shop);
                        }
                    }
                }

                $this->current_order = (int)$order->id;

                return $this->current_order;
            } else {
                echo $this->l('Order creation failed');

                return false;
            }
        }

        /**
         * @param $id_order_state
         * @param Cart $mCart
         * @param $order
         * @param $customer
         * @param $currency
         * @throws PrestaShopDatabaseException
         * @throws PrestaShopException
         */
        private function hookActivation($id_order_state, $mCart, $order, $customer, $currency)
        {
            $orderStatus = new OrderState((int)$id_order_state);

            if (Validate::isLoadedObject($orderStatus)) {
                if (version_compare(_PS_VERSION_, '1.5', '<')) {
                    Hook::newOrder($mCart, $order, $customer, $currency, $orderStatus);
                } else {
                    $reinitContext = MiraklTools::reInitContextControllerIfNeed($this->context);
                    if ($reinitContext['reinit']) {
                        $this->context = $reinitContext['context'];
                    }
                    Hook::exec('actionValidateOrder', array(
                        'cart' => $mCart,
                        'order' => $order,
                        'customer' => $customer,
                        'currency' => $currency,
                        'orderStatus' => $orderStatus
                    ));
                }
                foreach ($mCart->getProducts() as $product) {
                    if ($orderStatus->logable) {
                        ProductSale::addProductSale((int)$product['id_product'], (int)$product['cart_quantity']);
                    }
                }
            }
        }
    }
}
