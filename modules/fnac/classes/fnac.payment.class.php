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

class FNAC_PaymentModule extends PaymentModule
{

    public $name = 'fnac';

    public function validateOrder($id_cart, $id_order_state, $amount_paid, $payment_method = 'Unknown',
                                  $message = null, $extra_vars = array(), $currency_special = null, $dont_touch_amount = false,
                                  $secure_key = false, Shop $shop = null)
    {
        return $this->validateMarketplaceOrder($id_cart, $id_order_state, $payment_method);
    }

    /**
     * @param $id_cart
     * @param $id_order_state
     * @param $paymentMethod
     * @param null $mpOrderId
     * @param null $mpOrderStatus
     * @param FNAC_Cart $fnacCart
     * @param bool $useTaxes
     * @param bool $date_add
     * @return bool|int
     * @throws PrestaShopException
     */
    public function validateMarketplaceOrder($id_cart, $id_order_state, $paymentMethod, $mpOrderId = null, $mpOrderStatus = null, $fnacCart = null, $useTaxes = false, $date_add = false)
    {
        if (Validate::isLoadedObject($fnacCart) && $fnacCart->orderExists()) {
            $this->currentOrder = (int)Order::getOrderByCartId($fnacCart->id);
            return $this->currentOrder;
        }

        // Copying data from cart
        $order = new FNAC_Order();

        $order->id_carrier = (int)$fnacCart->id_carrier;
        $order->id_customer = (int)$fnacCart->id_customer;
        $order->id_address_invoice = (int)$fnacCart->id_address_invoice;
        $order->id_address_delivery = (int)$fnacCart->id_address_delivery;
        $order->id_currency = (int)$fnacCart->id_currency;
        $order->id_lang = (int)$fnacCart->id_lang;
        $order->id_cart = (int)$fnacCart->id;

        $customer = new Customer((int)$order->id_customer);

        if (Validate::isLoadedObject($customer)) {
            if (!Validate::isLoadedObject($customer)) {
                die(Tools::displayError('Customer is wrong.'));
            }
        }

        $order->secure_key = pSQL($customer->secure_key);
        if (!$order->secure_key) {
            $order->secure_key = md5(time());
        }

        $order->send_email = false;
        $order->payment = Tools::substr($paymentMethod, 0, 32);
        $order->module = $this->name;
        $order->recyclable = (bool)Configuration::get('PS_RECYCLABLE_PACK');
        $order->gift = (bool)$fnacCart->gift;
        $order->gift_message = $fnacCart->gift_message;
        $currency = new Currency($order->id_currency);

        $order->total_products = (float)$fnacCart->getOrderTotal(false, 1);
        $order->total_products_wt = (float)$fnacCart->getOrderTotal($useTaxes, 1);
        $order->total_discounts = (float)abs($fnacCart->getOrderTotal(false, 2));
        $order->total_shipping = (float)$fnacCart->getOrderTotal($useTaxes, 5);
        $order->total_wrapping = (float)abs($fnacCart->getOrderTotal(false, 6));
        $order->total_paid_real = (float)$fnacCart->getOrderTotal($useTaxes, 3);
        $order->total_paid = (float)$fnacCart->getOrderTotal($useTaxes, 3);

        $order->id_order_state = $id_order_state;
        $order->shipping_number = '';
        $order->delivery_number = 0;
        $order->exported = '';
        $order->carrier_tax_rate = 0;

        // Modif YB : ajout de la tva sur le transporteur
        if ($useTaxes) {
            // If PS1.4>
            if (method_exists('Tax', 'getCarrierTaxRate')) {
                $order->carrier_tax_rate = (float)Tax::getCarrierTaxRate($fnacCart->id_carrier, (int)$order->id_address_delivery);
            }
        }

        $id_warehouse = null;
        $id_shop = 1;

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $order->reference = Order::generateReference();

            $order->total_paid_tax_excl = (float)$fnacCart->getOrderTotal(false, 3);
            $order->total_paid_tax_incl = (float)$fnacCart->getOrderTotal(true, 3);

            $order->total_shipping_tax_excl = (float)$fnacCart->getOrderTotal(false, 5);
            $order->total_shipping_tax_incl = (float)$fnacCart->getOrderTotal(true, 5);

            $order->total_paid_real = 0;

            $order->current_state = (int)$id_order_state;

            // For Advanced Stock Management
            $id_warehouse = Configuration::get('FNAC_WAREHOUSE');
            if (!$id_warehouse) {
                $id_warehouse = null;
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

        if (!Validate::isLoadedObject($fnacCart)) {
            die(Tools::displayError('FNAC Cart is wrong.'));
        }

        $null_date = '0000-00-00 00:00:00';
        $order->invoice_date = $null_date;
        $order->delivery_date = $null_date;

        $currency = new Currency($fnacCart->id_currency);
        $order->conversion_rate = $currency->conversion_rate ? $currency->conversion_rate : 1;

        if (!($products = $fnacCart->getProducts())) {
            echo(Tools::displayError('Unable to get product from cart.'));
            return (false);
        }

        // Prevent to import duplicate order
        usleep(rand(100, 1000));

        // Check For Cart Mismatch
        foreach ($products as $product) {
            $SKU = trim((string)$product['reference']);

            if (!isset($fnacCart->fnacProducts[$SKU])) {
                echo Tools::displayError('Product cart mismatch.').'<br>';
                echo 'SKU = '.$SKU.'<br>';
                echo 'fnacProducts = <pre>'.print_r($fnacCart->fnacProducts, 1).'</pre>';
                echo 'products = <pre>'.print_r($products, 1).'</pre>';
                return (false);
            }
        }

        // Verify again if the order is already imported
        if ($order->checkByMpId($mpOrderId)) {
            printf('<br /><span style="color:red;">'.$this->l('Order ID (%s) Was already imported...').'</span><hr />'."\n", $mpOrderId);
            return (false);
        }

        // Add FNAC Order
//        $order->total_paid -= $fnacCart->fnacFees;
//        $order->total_paid_tax_incl -= $fnacCart->fnacFees;

        $result = $order->add($autodate, null, $mpOrderId, $mpOrderStatus);

        // Apply Fees
//        $order_cart_rule = new OrderCartRule();
//        $order_cart_rule->id_order = (int)$order->id;
//        $order_cart_rule->id_cart_rule = 1;
//        $order_cart_rule->id_order_invoice = $order->invoice_number;
//        $order_cart_rule->name = 'FNAC Fees';
//        $order_cart_rule->value = (float)$fnacCart->fnacFees;
//        $order_cart_rule->value_tax_excl = (float)$fnacCart->fnacFees;
//        $order_cart_rule->free_shipping = 0;
//        $order_cart_rule->add(true, true);

        // Next !
        if (Validate::isLoadedObject($order)) {
            $outOfStock = false;

            foreach ($products as $product) {
                // Main SKU / Reference
                $SKU = trim((string)$product['reference']);

                $id_product = (int)$product['id_product'];
                $id_product_attribute = $product['id_product_attribute'] ? (int)$product['id_product_attribute'] : null;

                // Must be always true
                $update_stocks = true;

                $productQuantity = Product::getRealQuantity($id_product, $id_product_attribute, $id_warehouse, $order->id_shop);
                $quantityInStock = $productQuantity - $product['cart_quantity'];

                // updates stock in shops PS 1.5
                if ($update_stocks) {
                    StockAvailable::updateQuantity($id_product, $id_product_attribute, $product['cart_quantity'] * -1, $order->id_shop);

                    // updates stock in shops
                    if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                        if (StockAvailable::dependsOnStock($id_product)) {
                            StockAvailable::synchronize($id_product, $order->id_shop);
                        }
                    }
                }

                // default taxes informations
                $product['id_tax'] = 0;
                $product['tax'] = null;
                $product['rate'] = 0;

                // Include VAT
                if (!Tax::excludeTaxeOption() || $useTaxes == true) {
                    if (isset($fnacCart->fnacProducts[$SKU]['tax_rate']) && $fnacCart->fnacProducts[$SKU]['tax_rate']) {
                        $tax_rate = $fnacCart->fnacProducts[$SKU]['tax_rate'];

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

                $unit_price_tax_incl = (float)$fnacCart->fnacProducts[$SKU]['price'];
                $unit_price_tax_excl = (float)Tools::ps_round($unit_price_tax_incl / (1 + ($product['rate'] / 100)), 2);

                $total_price_tax_incl = (float)Tools::ps_round($unit_price_tax_incl, 2) * $quantity;
                $total_price_tax_excl = (float)Tools::ps_round($unit_price_tax_excl, 2) * $quantity;

                $taxes = (float)Tools::ps_round($total_price_tax_incl - $total_price_tax_excl, 2);

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
                $order_detail->product_quantity_in_stock = (int)$quantityInStock;

                // product references
                $order_detail->product_price = (float)$unit_price_tax_excl;
                $order_detail->product_ean13 = $product['ean13'] ? $product['ean13'] : null;
                $order_detail->product_reference = $SKU;
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
                $order_detail->unit_price_tax_incl = (float)$unit_price_tax_incl;
                $order_detail->unit_price_tax_excl = (float)$unit_price_tax_excl;

                // shop and warehouse
                $order_detail->id_shop = (int)$order->id_shop;
                $order_detail->id_warehouse = (int)$id_warehouse;

                // add into db
                $order_detail->add();

                if (!Validate::isLoadedObject($order_detail)) {
                    print(Tools::displayError('OrderDetail::add() - Failed'));
                    echo Db::getInstance()->getMsgError();
                    echo($order_detail);
                    die;
                }

                /*
                 * Fill order_detail_tax PS1.5 table
                 * Where is the OrderDetailTax object in PS 1.5 ??? !!!
                 */

                if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                    $id_order_detail = $order_detail->id;

                    $tax_query = 'INSERT INTO `'._DB_PREFIX_.'order_detail_tax` (id_order_detail, id_tax, unit_amount, total_amount) VALUES '.
                        sprintf('(%d, %d, %f, %f) ;', (int)$id_order_detail, (int)$product['id_tax'], (float)$total_price_tax_excl, (float)$taxes);

                    if (!($tax_result = Db::getInstance()->Execute($tax_query))) {
                        echo nl2br(print_r($tax_query, true));
                        print(Tools::displayError('Failed to add tax details.'));
                        die(Db::getInstance()->getMsgError());
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
                    @Hook::newOrder($fnacCart, $order, $customer, $currency, $orderStatus);
                } else {
                    // Hook validate order
                    Hook::exec('actionValidateOrder', array(
                        'cart' => $fnacCart,
                        'order' => $order,
                        'customer' => $customer,
                        'currency' => $currency,
                        'orderStatus' => $orderStatus
                    ));
                }
                foreach ($fnacCart->getProducts() as $product) {
                    if ($orderStatus->logable) {
                        ProductSale::addProductSale((int)$product['id_product'], (int)$product['cart_quantity']);
                    }
                }
            }
            // Order is reloaded because the status just changed
            // @see class PaymentModule.php
            $order = new Order($order->id);
            if (!Validate::isLoadedObject($order)) {
                echo Tools::displayError(sprintf('%s(#%d): %s', basename(__FILE__), __LINE__, 'Order creation failed.'));
                return (false);
            }

            $this->addToHistory($order->id, $id_order_state);

            $this->currentOrder = (int)$order->id;

            return $this->currentOrder;
        } else {
            die(Tools::displayError('Order creation failed.'));
        }
    }

    private function addToHistory($id_order, $id_order_state)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            if (isset($this->context->cookie->id_employee)) {
                $id_employee = $this->context->cookie->id_employee;
            } else {
                $id_employee = 1;
            }
        } else {
            require_once(dirname(__FILE__).'/../backward_compatibility/backward.php');
            $this->context = Context::getContext();

            $id_employee = isset($this->context->cookie->id_employee) ? $this->context->cookie->id_employee : 1;
        }

        // Add History
        $new_history = new FNAC_OrderHistory();
        $new_history->id_order = (int)$id_order;
        $new_history->id_employee = $id_employee;
        $new_history->changeIdOrderState($id_order_state, $id_order);
        $new_history->addWithOutEmail(true);

        return;
    }
}
