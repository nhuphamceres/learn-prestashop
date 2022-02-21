<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Common-Services Co., Ltd. is strictly forbidden.
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
 * @copyright Copyright (c) 2011-2017 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   CDiscount
  * Support by mail:  support.cdiscount@common-services.com
 */

class CDiscountPaymentModule extends PaymentModule
{
    const KEY = CDiscount::KEY;
    const MODULE = CDiscount::MODULE;

    /**
     * Validate an order in database
     * Function called from a payment module
     */
    public function validateMarketplaceOrder($id_order_state, $paymentMethod = 'Unknown', $currency_special = null, $cdOrderId = null, $cdOrderStatus = null, $cdShipping = null, $mpCart = null, $destock = true)
    {
        // Copying data from cart
        $order = new CDiscountOrder();

        $order->date_add = $mpCart->cdDate;
        $order->date_upd = date('Y-m-d H:i:s');

        if (CDiscountTools::moduleIsInstalled('opensi')) {
            $order->date_add = date('Y-m-d H:i:s');
        }

        $order->id_carrier = (int)$mpCart->id_carrier;
        $order->id_customer = (int)$mpCart->id_customer;
        $order->id_address_invoice = (int)$mpCart->id_address_invoice;
        $order->id_address_delivery = (int)$mpCart->id_address_delivery;
        $order->id_currency = ($currency_special ? (int)$currency_special : (int)$mpCart->id_currency);
        $order->id_lang = (int)$mpCart->id_lang;
        $order->id_cart = (int)$mpCart->id;
        $customer = new Customer((int)$order->id_customer);
        $order->secure_key = pSQL($customer->secure_key);
        if (!$order->secure_key) {
            $order->secure_key = md5(time());
        }
        $order->payment = Tools::substr($paymentMethod, 0, 32);
        $order->module = self::MODULE;
        $order->recyclable = (bool)Configuration::get('PS_RECYCLABLE_PACK');
        $order->gift = (int)$mpCart->gift;
        $order->gift_message = $mpCart->gift_message;
        $currency = new Currency($order->id_currency);

        $order->total_products = (float)$mpCart->getOrderTotal(false, 1);
        $order->total_products_wt = (float)$mpCart->getOrderTotal(true, 1);
        $order->total_discounts = (float)abs($mpCart->getOrderTotal(false, 2));
        $order->total_shipping = (float)$mpCart->getOrderTotal(true, 5);
        $order->total_wrapping = (float)abs($mpCart->getOrderTotal(false, 6));
        $order->total_paid_real = (float)$mpCart->getOrderTotal(true, 3);
        $order->total_paid = (float)$mpCart->getOrderTotal(true, 3);
        $order->carrier_tax_rate = $mpCart->marketplaceGetCarrierTaxRate();

        $null_date = '0000-00-00 00:00:00';
        $order->invoice_date = $null_date;
        $order->delivery_date = $null_date;

        $this->removeCartRuleWithoutCode($mpCart);

        $id_warehouse = (int)Configuration::get(self::KEY.'_WAREHOUSE');

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $reference = Order::generateReference();

            $order->reference = $reference;

            $order->total_paid_real = 0;

            $order->total_paid_tax_excl = (float)$mpCart->getOrderTotal(false, 3);
            $order->total_paid_tax_incl = (float)$mpCart->getOrderTotal(true, 3);

            $order->total_shipping_tax_excl = (float)$mpCart->getOrderTotal(false, 5);
            $order->total_shipping_tax_incl = (float)$mpCart->getOrderTotal(true, 5);

            $order->current_state = (int)$id_order_state;


            if (Shop::isFeatureActive()) {
                $id_shop = (int)Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : 1;
            } else {
                $id_shop = null;
            }

            if ($id_shop) {
                $shop = new Shop($id_shop);
                $order->id_shop = $shop->id;
                $order->id_shop_group = $shop->id_shop_group;
            } else {
                $order->id_shop = 1;
                $order->id_shop_group = 1;
            }
        } else {
            $order->id_shop = null;
        }

        $currency = new Currency($currency_special);
        $order->conversion_rate = $currency->conversion_rate;

        if (!$order->total_products) {
            ob_start();
            echo "Order : \n";
            var_dump($mpCart->cdProducts);
            var_dump($order);
            $dump = ob_get_clean();
            echo $this->l('Unable to import an empty order...')."\n".$dump."\n";

            return (false);
        }

        if (!($products = $mpCart->getProducts())) {
            echo Tools::displayError('Unable to get product from cart.');
            return (false);
        }


        if (Validate::isDate($order->date_add)) {
            $autodate = false;
        } else {
            $autodate = true;
        }

        if (!$order->validateFields(false, false)) {
            echo Tools::displayError('Validation Failed.');
            return (false);
        }

        // Prevent to import duplicate order
        usleep(rand(100, 1000));

        if (CDiscountOrder::checkByMpId($cdOrderId)) {
            echo Tools::displayError('Order already imported.');
            return (false);
        }

        // Prevent duplicates
        if ($id_order = $order->isExistingOrder($order->date_add, $order->total_paid, $order->payment)) {
            echo "<pre>\n";
            if (Cdiscount::$debug_mode) {
                printf('%s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__);
            }
            printf('<br /><span style="color:red;">'.$this->l('Order ID (%s) has already been imported...').' - id_order: %d</span><hr />'."\n", $cdOrderId, $id_order);
            echo "</pre>\n";

            return (false);
        }

        $order->add($autodate);

        if (Validate::isLoadedObject($order)) {
            $update_stocks = $destock;

            foreach ($products as $product) {
                $id_product = (int)$product['id_product'];
                $id_product_attribute = $product['id_product_attribute'] ? (int)$product['id_product_attribute'] : null;

                if (version_compare(_PS_VERSION_, '1.5', '<')) {
                    $productQuantity = (int)Product::getQuantity($id_product, $id_product_attribute);
                    $quantityInStock = ($productQuantity - (int)$product['cart_quantity'] < 0) ? $productQuantity : (int)$product['cart_quantity'];

                    if ($update_stocks) {
                        if ((($updateResult = Product::updateQuantity($product)) === false or $updateResult === -1)) {
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
                        StockAvailable::updateQuantity($id_product, $id_product_attribute, ($product['cart_quantity'] * -1), $order->id_shop);
                    }
                }

                $quantity = (int)$product['cart_quantity'];
                $product_identifier = sprintf('%d_%d', $id_product, $id_product_attribute);

                $unitprice = $mpCart->cdProducts[$product_identifier]['price'] / $mpCart->cdProducts[$product_identifier]['qty'];

                if (!$unitprice) {
                    ob_start();
                    echo 'ID Product : '.$id_product.'\n';
                    var_dump($mpCart->cdProducts);
                    $dump = ob_get_clean();
                    echo $this->l('Produc price is zero or null...')."\n".$dump;

                    return (false);
                }

                // default taxes informations
                $product['id_tax'] = 0;
                $product['tax'] = null;

                $id_tax_rules_group = 0;
                $tax_rate = 0;

                // Include VAT (Prestashop 1.5);
                if (!Tax::excludeTaxeOption()) {
                    if (isset($mpCart->cdProducts[$product_identifier]['tax_rate']) && $mpCart->cdProducts[$product_identifier]['tax_rate']) {
                        $tax_rate = $mpCart->cdProducts[$product_identifier]['tax_rate'];
                        $id_tax_rules_group = $mpCart->cdProducts[$product_identifier]['id_tax_rules_group'];

                        $product['id_tax'] = $mpCart->cdProducts[$product_identifier]['id_tax'];
                        $product['rate'] = $mpCart->cdProducts[$product_identifier]['tax_rate'];
                    }
                }

                $unit_price_tax_excl = (float)Tools::ps_round($unitprice / (1 + ($product['rate'] / 100)), 2);
                $unit_price_tax_incl = (float)$unitprice;

                $total_price_tax_incl = (float)Tools::ps_round($unit_price_tax_incl, 2) * $quantity;
                $total_price_tax_excl = (float)Tools::ps_round($unit_price_tax_excl, 2) * $quantity;

                //$taxes = (float)Tools::ps_round($unit_price_tax_incl - $unit_price_tax_excl, 2);

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
                $order_detail->product_upc = $product['upc'] ? $product['upc'] : null;
                $order_detail->product_reference = $product['reference'];
                $order_detail->product_supplier_reference = $product['supplier_reference'] ? $product['supplier_reference'] : null;
                $order_detail->product_weight = (float)Tools::ps_round($product['id_product_attribute'] ? $product['weight_attribute'] : $product['weight'], 2);

                // taxes
                $order_detail->tax_name = $product['tax'];
                $order_detail->tax_rate = (float)$product['rate'];
                $order_detail->id_tax_rules_group = (int)$id_tax_rules_group;
                $order_detail->ecotax = $product['ecotax'];

                // For PS 1.4
                $order_detail->download_deadline = $null_date;

                // For PS 1.5+
                // price details
                $order_detail->total_price_tax_incl = (float)$total_price_tax_incl;
                $order_detail->total_price_tax_excl = (float)$total_price_tax_excl;
                $order_detail->unit_price_tax_incl = (float)$unit_price_tax_incl;
                $order_detail->unit_price_tax_excl = (float)$unit_price_tax_excl;

                $order_detail->original_product_price = (float)$unit_price_tax_excl;
                $order_detail->purchase_supplier_price = isset($product['wholesale_price']) ? Tools::ps_round((float)$product['wholesale_price'], 2) : 0;

                // shop and warehouse
                $order_detail->id_shop = (int)$order->id_shop;
                $order_detail->id_warehouse = (int)$id_warehouse;

                // add into db
                if (version_compare(_PS_VERSION_, '1.5', '<')) {
                    $order_detail->add();

                    if (!Validate::isLoadedObject($order_detail)) {
                        print Tools::displayError('OrderDetail::add() - Failed');
                        die;
                    }
                } else {
                    $order_detail->add();

                    if (!Validate::isLoadedObject($order_detail)) {
                        print Tools::displayError('OrderDetail::add() - Failed');
                        die;
                    }

                    $id_order_detail = $order_detail->id;

                    if ($tax_rate) {
                        $address_delivery = new Address($order->id_address_delivery);

                        if (Validate::isLoadedObject($address_delivery)) {
                            $id_tax = $this->getIdTax($address_delivery->id_country, $id_tax_rules_group);

                            $tax_query = 'INSERT INTO `'._DB_PREFIX_.'order_detail_tax` (id_order_detail, id_tax, unit_amount, total_amount) VALUES '.sprintf('(%d, %d, %f, %f) ;', $id_order_detail, $id_tax, $total_price_tax_excl, $total_price_tax_incl - $total_price_tax_excl);

                            if (!($tax_result = Db::getInstance()->execute($tax_query))) {
                                echo nl2br(print_r($tax_query, true));
                                print Tools::displayError('Failed to add tax details.');
                            }

                            if (CDiscount::$debug_mode) {
                                CommonTools::p("Tax Query: " . $tax_query);
                                CommonTools::p("Result:".(!$tax_result ? 'Failed' : 'OK'));
                            }
                        }
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

            $cookie = &$this->context->cookie;
            $id_cdiscount_employee = (int)Configuration::get(self::KEY.'_EMPLOYEE');

            $employee = new Employee((int)$id_cdiscount_employee ? (int)$id_cdiscount_employee : $cookie->id_employee);

            if (Validate::isLoadedObject($employee)) {
                $id_employee = $employee->id;
            } else {
                $id_employee = $cookie->id_employee;
            }

            $new_history = new CDiscountOrderHistory();
            $new_history->id_order = (int)$order->id;
            $new_history->id_employee = $id_employee;
            $new_history->changeIdOrderState($id_order_state, $order->id);
            $new_history->addWithOutEmail(true);

            // Order is reloaded because the status just changed
            // @see class PaymentModule.php
            $order = new Order($order->id);

            if (!Validate::isLoadedObject($order)) {
                echo Tools::displayError(sprintf('%s(#%d): %s', basename(__FILE__), __LINE__, 'Order creation failed.'));

                return (false);
            }

            // Execute hook after change state (setInvoice).
            // To compatible with different module (ps_emailalerts + send mail extension) (3301369144 - 89109).
            $this->hookActivation($id_order_state, $mpCart, $order, $customer, $currency);

            // updates stock in shops
            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                if (StockAvailable::dependsOnStock($id_product)) {
                    foreach ($products as $product) {
                        StockAvailable::synchronize((int)$product['id_product'], $order->id_shop);
                    }
                }
            }

            $this->currentOrder = (int)$order->id;

            return $this->currentOrder;
        } else {
            echo Tools::displayError(sprintf('%s(#%d): %s', basename(__FILE__), __LINE__, 'Order creation failed.'));

            return (false);
        }
    }
    public function getIdTax($id_country, $id_tax_rules_group)
    {
        $sql = 'SELECT `id_tax` FROM `'._DB_PREFIX_.'tax_rule` WHERE `id_tax_rules_group`= '.(int)$id_tax_rules_group.' AND `id_country`= '.(int)$id_country;

        $id_tax = Db::getInstance()->getValue($sql);

        if (CDiscount::$debug_mode) {
            echo "<pre>getIdTax:\n";
            print_r($id_tax);
            echo "</pre>\n";
        }

        return($id_tax);
    }

    public function removeCartRuleWithoutCode(&$cart)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>') && Validate::isLoadedObject($cart)) {
            $cart_rules = $cart->getCartRules();

            if (is_array($cart_rules) && count($cart_rules)) {
                foreach ($cart_rules as $cr) {
                    if (!Tools::strlen($cr['code'])) {
                        $cart->removeCartRule($cr['id_cart_rule']);
                    }
                }
            }
            $cart->update();
        }
    }

    /**
     * @param $id_order_state
     * @param CDiscountCart $cdCart
     * @param $order
     * @param $customer
     * @param $currency
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function hookActivation($id_order_state, $cdCart, $order, $customer, $currency)
    {
        $orderStatus = new OrderState((int)$id_order_state);

        if (Validate::isLoadedObject($orderStatus)) {
            if (version_compare(_PS_VERSION_, '1.5', '<')) {
                Hook::newOrder($cdCart, $order, $customer, $currency, $orderStatus);
            } else {
                $reinitContext = CDiscountTools::reInitContextControllerIfNeed($this->context);
                if ($reinitContext['reinit']) {
                    $this->context = $reinitContext['context'];
                }
                Hook::exec('actionValidateOrder', array(
                    'cart' => $cdCart,
                    'order' => $order,
                    'customer' => $customer,
                    'currency' => $currency,
                    'orderStatus' => $orderStatus
                ));
            }
            foreach ($cdCart->getProducts() as $product) {
                if ($orderStatus->logable) {
                    ProductSale::addProductSale((int)$product['id_product'], (int)$product['cart_quantity']);
                }
            }
        }
    }
}
