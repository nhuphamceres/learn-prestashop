<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Feed.biz
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Feed.biz is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Feed.biz.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 *
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
*/

class AmazonPaymentModule extends PaymentModule
{
    /*
     * 2013/07/13
     * Fix Notice: previously the name was "Amazon" renamed to "amazon" because causing a bug with Module::getInstanceByName
     * for previous version users, in case of update, that'll require to UPDATE ps_orders and change "Amazon to amazon" in the module column
     */
    public $name = 'amazon';
    
    /** @var AmazonCart */
    protected $amazonCart;
    
    /** @var bool */
    protected $useTaxes;
    
    /** @var string|null Datetime string */
    protected $dateAdd;
    
    /** @var int */
    protected $idWarehouse = null;

    /** @var string */
    protected $error;

    /**
     * @param $id_cart
     * @param $id_order_state
     * @param string $paymentMethod
     * @param null $mpOrderId
     * @param null $mpOrderStatus
     * @param AmazonCart $amazonCart
     * @param bool $useTaxes
     * @param bool $date_add
     * @return bool|int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function validateMarketplaceOrder($id_cart, $id_order_state, $paymentMethod = 'Unknown', $mpOrderId = null, $mpOrderStatus = null, $amazonCart = null, $useTaxes = false, $date_add = false)
    {
        $this->amazonCart = $amazonCart;
        $this->useTaxes = $useTaxes;
        $this->dateAdd = $date_add;
        
        // Copying data from cart
        $order = new AmazonOrder();

        $order->id_carrier = (int)$amazonCart->id_carrier;
        $order->id_customer = (int)$amazonCart->id_customer;
        $order->id_address_invoice = (int)$amazonCart->id_address_invoice;
        $order->id_address_delivery = (int)$amazonCart->id_address_delivery;
        $order->id_currency = (int)$amazonCart->id_currency;
        $order->id_lang = (int)$amazonCart->id_lang;
        $order->id_cart = (int)$amazonCart->id;

        $this->removeCartRuleWithoutCode($amazonCart);

        $customer = new Customer((int)$order->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            $this->handleError(Tools::displayError(sprintf('%s(#%d): %s', basename(__FILE__), __LINE__, 'Customer is wrong.')));

            return (false);
        }

        $order->secure_key = pSQL($customer->secure_key);
        $order->secure_key = pSQL($customer->secure_key);
        if (!$order->secure_key) {
            $order->secure_key = md5(time());
        }

        $order->send_email = false;
        $order->payment = Tools::substr($paymentMethod, 0, 32);
        $order->module = $this->name;
        $order->recyclable = Configuration::get('AMAZON_RECYCLABLE_PACK') !== false ? (bool)Configuration::get('AMAZON_RECYCLABLE_PACK') : (bool)Configuration::get('PS_RECYCLABLE_PACK');

        $order->total_products = (float)$amazonCart->getAmazonOrderTotal(false, 1);
        $order->total_products_wt = (float)$amazonCart->getAmazonOrderTotal($useTaxes, 1);
        $order->total_discounts = (float)abs($amazonCart->getAmazonOrderTotal(false, 2));
        $order->total_shipping = (float)($useTaxes ? $amazonCart->amzShippingPriceTaxIncl : $amazonCart->amzShippingPriceTaxExcl);
        $order->total_wrapping = (float)abs($amazonCart->getAmazonOrderTotal(false, 6));
        $order->total_paid_real = (float)$amazonCart->getAmazonOrderTotal(true, 3);
        $order->total_paid = (float)$amazonCart->getAmazonOrderTotal(true, 3);

        $order->id_order_state = $id_order_state;
        $order->shipping_number = '';
        $order->delivery_number = 0;
        $order->exported = '';
        $order->carrier_tax_rate = $amazonCart->amzShippingTaxRate;

        $order->round_mode = Configuration::get('PS_PRICE_ROUND_MODE');
        $order->round_type = Configuration::get('PS_ROUND_TYPE');
        
        $id_warehouse = 0;
        $id_shop = 1;

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            AmazonContext::restore($this->context);

            $order->reference = Order::generateReference();

            $order->total_paid_tax_excl = (float)$amazonCart->getAmazonOrderTotal(false, 3);
            $order->total_paid_tax_incl = (float)$amazonCart->getAmazonOrderTotal(true, 3);

            $order->total_shipping_tax_excl = (float)$amazonCart->amzShippingPriceTaxExcl;
            $order->total_shipping_tax_incl = (float)$amazonCart->amzShippingPriceTaxIncl;

            $order->total_paid_real = 0;

            $order->current_state = (int)$id_order_state;

            $id_shop = (int)$this->context->shop->id;
            $id_shop_group = (int)$this->context->shop->id_shop_group;

            $id_warehouse = (int)Configuration::get('AMAZON_WAREHOUSE');

            if ($id_shop) {
                $order->id_shop = $id_shop;
                $order->id_shop_group = $id_shop_group;
            } else {
                $order->id_shop = 1;
                $order->id_shop_group = 1;
            }
        } else {
            $order->id_shop = $id_shop;
        }

        if ($date_add) {
            $order->date_add = $date_add;
            $order->date_upd = $date_add;
            $autodate = false;
        } else {
            $autodate = true;
        }

        if (!Validate::isLoadedObject($amazonCart)) {
            $this->handleError(Tools::displayError(sprintf('%s(#%d): %s', basename(__FILE__), __LINE__, 'Amazon Cart is wrong.')));

            return (false);
        }
        $null_date = '0000-00-00 00:00:00';
        $order->invoice_date = $null_date;
        $order->delivery_date = $null_date;

        $currency = new Currency($amazonCart->id_currency);
        $order->conversion_rate = $currency->conversion_rate ? $currency->conversion_rate : 1;

        $order->round_mode = Configuration::get('PS_PRICE_ROUND_MODE');
        $order->round_type = Configuration::get('PS_ROUND_TYPE');

        $total_wrapping_tax_incl = 0;
        $total_wrapping_tax_excl = 0;

        $order_weight = 0;

        if (!($products = $amazonCart->getProducts())) {
            $this->handleError(Tools::displayError(sprintf('%s(#%d): %s - %s', basename(__FILE__), __LINE__, 'Unable to get product from cart.', print_r($amazonCart->amazonProducts, true))));

            return (false);
        }
        // Check For Cart Mismatch
        if (!$this->checkCartMismatch($products)) {
            return false;
        }

        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('Cart Content: %s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
            CommonTools::p($products);
        }

        // Prevent to import duplicate order
        usleep(rand(100, 1000));

        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('Duplicate check: %s - %s::%s - line #%d - %s %s %s %s'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__, $date_add, $order->total_paid, $paymentMethod, $this->name));
            CommonTools::p($products);
        }


        // Prevent duplicates
        $exist_ps_order_id = $order->isExistingOrder($date_add, $order->total_paid, $paymentMethod, $this->name);
        if ($amazonCart->amazonChannel != Amazon::AFN && !$autodate) {
            if ($exist_ps_order_id) {
                // In rare case, customer order multiple orders on same day, of the same amount, make additional check before mark it as duplicate.
                if (AmazonOrder::checkByMpId($mpOrderId)) {
                    if (Amazon::$debug_mode) {
                        CommonTools::p(sprintf('%s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
                    }
                    $this->handleError(sprintf($this->l('Order ID (%s) has already been imported...').' - id_order: %d', $mpOrderId, $exist_ps_order_id));

                    return (false);
                }
            }
        }

        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
            CommonTools::p(get_object_vars($order));
        }

        $orderValidation = $order->validateFields(false, true);
        if ($orderValidation !== true) {
            $this->handleError(Tools::displayError('Validation Failed. ' . $orderValidation));

            return (false);
        }

        if ($amazonCart->amazon_order_info instanceof AmazonOrderInfo) {
            $available_fields = get_object_vars($amazonCart->amazon_order_info);

            foreach ($available_fields as $field => $value) {
                if (Tools::strlen($value)) {
                    $order->amazon_order_info->{$field} = $value;
                }
            }
        }

        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('Order Info: - %s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
            CommonTools::p(get_object_vars($order->amazon_order_info));
        }

        // Add Amazon Order
        //
        $order->add($autodate, null, $mpOrderId, $mpOrderStatus, $amazonCart->amazonChannel);

        // Next !
        if (Validate::isLoadedObject($order)) {
            if ($order->amazon_order_info instanceof AmazonOrderInfo) {
                $order->amazon_order_info->id_order = (int)$order->id;
                $order->amazon_order_info->saveOrderInfo();
            }

            foreach ($products as $product) {
                // Main SKU / Reference
                $SKU = trim((string)$product['reference']);

                $id_product = (int)$product['id_product'];
                $id_product_attribute = $product['id_product_attribute'] ? (int)$product['id_product_attribute'] : null;

                // Must be always true
                //
                $update_stocks = (bool)Configuration::get('PS_STOCK_MANAGEMENT');

                if (version_compare(_PS_VERSION_, '1.5', '<')) {
                    $productQuantity = (int)AmazonProduct::getQuantity($id_product, $id_product_attribute);
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
                } else {
                    $productQuantity = Product::getRealQuantity($id_product, $id_product_attribute, $id_warehouse, $order->id_shop);
                    $quantityInStock = $productQuantity - $product['cart_quantity'];

                    // updates stock in shops PS 1.5

                    if ($update_stocks) {
                        if (Amazon::$debug_mode) {
                            CommonTools::p(sprintf('Cart: %s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
                            CommonTools::p('stock update: '.($product['cart_quantity'] * -1));
                        }
                        StockAvailable::updateQuantity($id_product, $id_product_attribute, $product['cart_quantity'] * -1, $order->id_shop);
                    }
                }

                // 2020-09-28: Use quantity from $amazonCart->amazonProducts instead of $product['cart_quantity'] (cart combine same products by SKU)
                $amzItemsBySku = $this->amazonCart->amazonProducts[$SKU];
                foreach ($amzItemsBySku['cs_items'] as $amazonProduct) {
                    // todo: Bring $tax_rate + $id_tax_rules_group to child function
                    // 2021-07-21: `cs_items` already calculated tax, just grab it here
                    $id_tax_rules_group = $amazonProduct['id_tax_rules_group'];
                    $tax_rate = $amazonProduct['tax_rate'];
                    // 2021-06-14: Use calculated tax information (id, rule, rate) instead of origin product's tax
                    // Even in case of tax_rate = 0
                    $product['id_tax'] = $amazonProduct['id_tax'];
                    $product['rate'] = $tax_rate;

                    $orderCalculate = $this->addProductToOrder(
                        $order, $product, $amazonProduct,
                        $quantityInStock,
                        $tax_rate, $id_tax_rules_group,
                        $order_weight, $total_wrapping_tax_excl, $total_wrapping_tax_incl
                    );
                    $id_order_detail         = $orderCalculate['id_order_detail'];
                    $order->gift             = $orderCalculate['gift'];
                    $order->gift_message     = $orderCalculate['gift_msg'];
                    $order_weight            = $orderCalculate['weight'];  // Maybe not use.
                    $total_wrapping_tax_excl = $orderCalculate['wrapping_tax_excl'];
                    $total_wrapping_tax_incl = $orderCalculate['wrapping_tax_incl'];

                    $this->saveAmazonOrderItem($mpOrderId, (int)$order->id, $id_order_detail, $product, $amazonProduct);
                }

            } // end foreach ($products)

            $order_update = false;

            // Update Order for Wrapping Fees / Gift
            if ($total_wrapping_tax_incl) {
                $order->total_wrapping = $total_wrapping_tax_incl;
                $order->total_wrapping_tax_incl = $total_wrapping_tax_incl;
                $order->total_wrapping_tax_excl = $total_wrapping_tax_excl;
                $order_update = true;
            }


            if (Tools::strlen($order->gift_message)) {
                $order->gift_message = rtrim($order->gift_message, ' - ');
                $order->gift_message = preg_replace('/[^<>{}]/i', '', $order->gift_message);
                $order_update = true;
            }

            if ($order_update) {
                $order->update();
            }

            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                // Adding an entry in order_carrier table
                if ($order->id_carrier) {
                    $order_carrier = new OrderCarrier();
                    $order_carrier->id_order = (int)$order->id;
                    $order_carrier->date_add = $date_add;
                    $order_carrier->date_upd = $date_add;
                    $order_carrier->id_carrier = $order->id_carrier;
                    $order_carrier->weight = (float)$order->getTotalWeight();
                    $order_carrier->shipping_cost_tax_excl = $order->total_shipping_tax_excl;
                    $order_carrier->shipping_cost_tax_incl = $order->total_shipping_tax_incl;
                    $order_carrier->add(Tools::strlen($date_add) ? false : true);

                    if (Amazon::$debug_mode) {
                        CommonTools::p("Order Carrier:");
                        CommonTools::p(get_object_vars($order_carrier));
                    }
                }
            }

            // 2020-12-18: Execute hook after change state (setInvoice).
            // To compatible with different module (ps_emailalerts + send mail extension) (3301369144 - 89109).

            $this->addToHistory($order->id, $id_order_state, $autodate ? null : $order->date_add);

            // Order is reloaded because the status just changed
            // @see class PaymentModule.php
            $order = new Order($order->id);

            if (!Validate::isLoadedObject($order)) {
                $this->handleError(Tools::displayError(sprintf('%s(#%d): %s', basename(__FILE__), __LINE__, 'Order creation failed.')));
                return (false);
            }

            if (Amazon::$debug_mode) {
                AmazonTools::pre(array(
                    "Add To History \n",
                    "autodate:".$autodate."\n",
                    "date:".$order->date_add."\n"
                ));
            }

            $this->hookActivation($id_order_state, $amazonCart, $order, $customer, $currency);

            // Update payment date
            if (version_compare(_PS_VERSION_, '1.5', '>') && Tools::strlen($order->reference) && Tools::strlen($date_add)) {
                Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'order_payment` SET `date_add` = "'.pSQL($date_add).'" WHERE `order_reference` = "'.pSQL($order->reference).'"');
            }

            // updates stock in shops
            if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                foreach ($products as $key => $product) {
                    if (StockAvailable::dependsOnStock((int)$product['id_product'])) {
                        StockAvailable::synchronize((int)$product['id_product'], $order->id_shop);
                    }
                }
            }

            $this->currentOrder = (int)$order->id;

            return $this->currentOrder;
        } else {
            $this->handleError(Tools::displayError(sprintf('%s(#%d): %s', basename(__FILE__), __LINE__, 'Order creation failed.')));

            return (false);
        }
    }


    public function getIdTax($id_country, $id_tax_rules_group)
    {
        $sql = 'SELECT `id_tax` FROM `'._DB_PREFIX_.'tax_rule` WHERE `id_tax_rules_group`= '.(int)$id_tax_rules_group.' AND `id_country`= '.(int)$id_country;

        $id_tax = (int)Db::getInstance()->getValue($sql);

        if (Amazon::$debug_mode) {
            AmazonTools::pre(array(
                "SQL: ".print_r($sql, true)."\n",
                "id_tax:".print_r($id_tax, true)."\n"
            ));
        }

        return($id_tax);
    }

    private function addToHistory($id_order, $id_order_state, $date_add)
    {
        $id_employee = Configuration::get('AMAZON_EMPLOYEE');
        // Add History
        $new_history = new AmazonOrderHistory();
        $new_history->id_order = (int)$id_order;
        $new_history->id_employee = (int)$id_employee ? (int)$id_employee : 1;
        $new_history->id_order_state = $id_order_state;
        $new_history->date_add = $date_add;
        $new_history->date_upd = $date_add;
        $new_history->changeIdOrderState($id_order_state, $id_order);
        $new_history->addWithOutEmail(Tools::strlen($date_add) ? false : true);

        if (Amazon::$debug_mode) {
            CommonTools::p(sprintf('Order History: %s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
            CommonTools::p(get_object_vars($new_history));
        }
    }

    /**
     * @param AmazonCart $cart
     */
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
     * @param array $products
     * @return bool
     * @throws PrestaShopException
     */
    protected function checkCartMismatch($products)
    {
        $amazonCart = $this->amazonCart;
        foreach ($products as $product) {
            $SKU = trim((string)$product['reference']);

            if (!isset($amazonCart->amazonProducts[$SKU])) {
                $error = Tools::displayError(sprintf('%s(#%d): %s', basename(__FILE__), __LINE__, 'Product cart mismatch.'));
                $this->handleError($error);
                AmazonTools::pre(array(
                    sprintf('%s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__),
                    "Cart:".print_r($products, true),
                    "Amazon Cart:".print_r($amazonCart->amazonProducts, true)
                ));

                return false;
            }
        }
        
        return true;
    }

    /**
     * @param AmazonOrder $order
     * @param $psProduct
     * @param $amazonProduct
     * @param $quantityInStock
     * @param $tax_rate
     * @param $id_tax_rules_group
     * @param $order_weight
     * @param $total_wrapping_tax_excl
     * @param $total_wrapping_tax_incl
     * @return array [weight, wrapping_tax_excl, wrapping_tax_incl, gift, gift_msg]
     */
    protected function addProductToOrder(
        $order, $psProduct, $amazonProduct,
        $quantityInStock,
        $tax_rate, $id_tax_rules_group,
        $order_weight, $total_wrapping_tax_excl, $total_wrapping_tax_incl
    )
    {
        // todo: Why not calculate in import at once?
        $quantity = $amazonProduct['qty'];
        if (!$amazonProduct['amazon_has_tax']) {
            // $psProduct['rate'] is got from PS settings.
            $amazon_has_tax = false;
            $unit_price_tax_incl = Tools::ps_round((float)$amazonProduct['price'], 2);
            $unit_price_tax_excl = Tools::ps_round($unit_price_tax_incl / (1 + ($psProduct['rate'] / 100)), 2);

            $total_price_tax_incl = Tools::ps_round($unit_price_tax_incl * $quantity, 2);
            $total_price_tax_excl = Tools::ps_round($unit_price_tax_excl * $quantity, 2);

            $unit_wrapping_tax_excl = Tools::ps_round((float)$amazonProduct['giftwrap'] / ((100 + $psProduct['rate']) / 100), 2);
            $unit_wrapping_tax_incl = Tools::ps_round((float)$amazonProduct['giftwrap'], 2);
        } else {
            $amazon_has_tax = true;
            if ($amazonProduct['europe']) {
                $unit_price_tax_incl = Tools::ps_round((float)$amazonProduct['price'], 2);
                $unit_price_tax_excl = Tools::ps_round((float)$amazonProduct['price'] - ($amazonProduct['amazon_item_tax'] / $quantity), 2);
            } else if(isset($this->amazonCart->amazon_order_info->sales_channel) && (string)$this->amazonCart->amazon_order_info->sales_channel == "Amazon.co.jp") { // Japan exception
                $unit_price_tax_incl = Tools::ps_round((float)$amazonProduct['price'], 2);
                $unit_price_tax_excl = Tools::ps_round((float)$amazonProduct['price'] - ($amazonProduct['amazon_item_tax'] / $quantity), 2);
            } else {
                $unit_price_tax_incl = Tools::ps_round((float)$amazonProduct['price'] + ($amazonProduct['amazon_item_tax'] / $quantity), 2);
                $unit_price_tax_excl = Tools::ps_round((float)$amazonProduct['price'], 2);
            }

            $total_price_tax_incl = Tools::ps_round($unit_price_tax_incl * $quantity, 2);
            $total_price_tax_excl = Tools::ps_round($unit_price_tax_excl * $quantity, 2);

            $unit_wrapping_tax_excl = Tools::ps_round(((float)$amazonProduct['giftwrap'] / $quantity) / ((100 + $psProduct['rate']) / 100), 2);
            $unit_wrapping_tax_incl = Tools::ps_round(((float)$amazonProduct['giftwrap'] / $quantity), 2);
        }

        $total_wrapping_tax_incl += $unit_wrapping_tax_incl;
        $total_wrapping_tax_excl += $unit_wrapping_tax_excl;
        
        $order_detail = $this->initOrderDetail(
            $order, $psProduct, $amazonProduct,
            $quantityInStock, $unit_price_tax_excl, $unit_price_tax_incl,
            $tax_rate, $id_tax_rules_group,
            $total_price_tax_excl, $total_price_tax_incl
        );
        $order_detail = $this->saveOrderDetail(
            $order_detail, $order,
            $amazon_has_tax, $tax_rate, $id_tax_rules_group,
            $total_price_tax_excl, $total_price_tax_incl
        );
        
        $order_weight += $order_detail->product_weight;
        $giftMsg = $order->gift_message;
        if (Tools::strlen($amazonProduct['giftmsg'])) {
            $giftMsg = sprintf('%s - %s', $giftMsg, $amazonProduct['giftmsg']);
        }

        return array(
            'id_order_detail' => $order_detail->id,
            'weight' => $order_weight,
            'wrapping_tax_excl' => $total_wrapping_tax_excl,
            'wrapping_tax_incl' => $total_wrapping_tax_incl,
            'gift' => $order->gift || (bool)$amazonProduct['giftwrap'],
            'gift_msg' => $giftMsg,
        );
    }

    protected function initOrderDetail(
        $order,
        $psProduct,
        $amazonProduct,
        $quantityInStock,
        $unit_price_tax_excl,
        $unit_price_tax_incl,
        $tax_rate,
        $id_tax_rules_group,
        $total_price_tax_excl,
        $total_price_tax_incl
    )
    {
        $quantity = $amazonProduct['qty'];
        $order_detail = new OrderDetail(null, null, isset($this->context) ? $this->context : null);

        $order_detail->date_add = $this->dateAdd;
        $order_detail->date_upd = $this->dateAdd;

        // order details
        $order_detail->id_order = (int)$order->id;

        // product information
        $order_detail->product_name = $this->getProductName($psProduct, $amazonProduct);
        $order_detail->product_id = $this->getIdProduct($psProduct);
        $order_detail->product_attribute_id = $this->getIdProductAttribute($psProduct);

        // quantities
        $order_detail->product_quantity = (int)$quantity;
        $order_detail->product_quantity_in_stock = (int)$quantityInStock;

        $products_weight = (float)Tools::ps_round($psProduct['id_product_attribute'] && $psProduct['weight_attribute'] ? $psProduct['weight_attribute'] : $psProduct['weight'], 4);

        // product references
        $order_detail->product_price = (float)$unit_price_tax_excl;
        $order_detail->product_ean13 = $psProduct['ean13'] ? $psProduct['ean13'] : null;
        $order_detail->product_upc = $psProduct['upc'] ? $psProduct['upc'] : null;
        $order_detail->product_reference = $this->getProductSku($psProduct);
        $order_detail->product_supplier_reference = $psProduct['supplier_reference'] ? $psProduct['supplier_reference'] : null;
        $order_detail->product_weight = $products_weight;

        // taxes
        // $order_detail->tax_name = Tools::substr($product['tax'], 0, 16); // deprecated - has bug also; size in ps_order_detail: 16 - in tax_lang: 32
        $order_detail->tax_rate = (float)$tax_rate;
        $order_detail->id_tax_rules_group = (int)$id_tax_rules_group;
        $order_detail->ecotax = $psProduct['ecotax'];

        // For PS 1.4
        $order_detail->download_deadline = $this->getNullDate();

        // For PS 1.5+
        // price details
        $order_detail->total_price_tax_incl = (float)$total_price_tax_incl;
        $order_detail->total_price_tax_excl = (float)$total_price_tax_excl;
        $order_detail->unit_price_tax_incl = (float)$unit_price_tax_incl;
        $order_detail->unit_price_tax_excl = (float)$unit_price_tax_excl;
        $order_detail->tax_computation_method = $this->amazonCart->marketplaceCalculationMethod();

        $order_detail->original_product_price = (float)$unit_price_tax_excl;
        $order_detail->purchase_supplier_price = isset($psProduct['wholesale_price']) ? Tools::ps_round((float)$psProduct['wholesale_price'], 2) : 0;

        // shop and warehouse
        $order_detail->id_shop = (int)$order->id_shop;
        $order_detail->id_warehouse = $this->getIdWarehouse();

        if (Amazon::$debug_mode) {
            CommonTools::p("Order Details:");
            CommonTools::p(get_object_vars($order_detail));
        }
        
        return $order_detail;
    }

    /**
     * @param OrderDetail $order_detail
     * @param AmazonOrder $order
     * @param bool $amazon_has_tax
     * @param int $tax_rate
     * @param int $id_tax_rules_group
     * @param float $total_price_tax_excl
     * @param float $total_price_tax_incl
     * @return OrderDetail
     */
    protected function saveOrderDetail($order_detail, $order, $amazon_has_tax, $tax_rate, $id_tax_rules_group, $total_price_tax_excl, $total_price_tax_incl)
    {
        $autoDate = !Tools::strlen($this->dateAdd);
        $order_detail->add($autoDate);
        if (!Validate::isLoadedObject($order_detail)) {
            print Tools::displayError('OrderDetail::add() - Failed');
            die;
        }
        
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_order_detail = $order_detail->id;

            if ($tax_rate) {
                $address_delivery = new Address($order->id_address_delivery);

                if (Validate::isLoadedObject($address_delivery)) {
                    $amazonCart = $this->amazonCart;
                    /**
                     * Dec-5, 2018: If this order is special FBA + item doesn't have tax from Amazon,
                     * save order_detail_tax by overrode country + tax rule.
                     */
                    if (!$amazon_has_tax && $amazonCart->isTaxForFBA()) {
                        $id_tax = $this->getIdTax($amazonCart->tax_for_fba->id_country, $amazonCart->tax_for_fba->id_tax_rule);
                    } else {
                        $id_tax = $this->getIdTax($address_delivery->id_country, $id_tax_rules_group);
                    }

                    $tax_query = 'INSERT INTO `'._DB_PREFIX_.'order_detail_tax` (id_order_detail, id_tax, unit_amount, total_amount) VALUES ' . 
                        sprintf('(%d, %d, %f, %f) ;', $id_order_detail, $id_tax, $total_price_tax_excl, $total_price_tax_incl - $total_price_tax_excl);

                    if (!($tax_result = Db::getInstance()->execute($tax_query))) {
                        AmazonTools::pre(array(
                            "Order:\n",
                            sprintf('%s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__),
                            nl2br(print_r($tax_query, true)),
                            Tools::displayError('Failed to add tax details.')
                        ));
                    }

                    if (Amazon::$debug_mode) {
                        CommonTools::p(sprintf('%s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
                        CommonTools::p('Tax Query'.$tax_query);
                        CommonTools::p("Result:".(!$tax_result ? 'Failed' : 'OK'));
                    }
                }
            }
        }

        if (!Validate::isLoadedObject($order_detail)) {
            CommonTools::p(sprintf('%s - %s::%s - line #%d'."\n", basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
            CommonTools::p(Tools::displayError('OrderDetail::add() - Failed'));
            die;
        }
        
        return $order_detail;
    }
    
    protected function saveAmazonOrderItem($mpOrderId, $orderId, $id_order_detail, $psProduct, $amzProduct)
    {
        $idProduct = $this->getIdProduct($psProduct);
        $idProductAttribute = $this->getIdProductAttribute($psProduct);

        $order_item = new AmazonOrderItem();
        $order_item->mp_order_id = $mpOrderId;
        $order_item->order_item_id = $amzProduct['order_item_id'];
        $order_item->id_order = $orderId;
        $order_item->id_product = $idProduct;
        $order_item->id_product_attribute = $idProductAttribute;
        $order_item->sku = $this->getProductSku($psProduct);
        $order_item->asin = $amzProduct['asin'];
        $order_item->quantity = $amzProduct['qty'];
        // 2020-09-28: Each item has different customization now have separate row in db.
        $order_item->customization = is_array($amzProduct['customization']) && count($amzProduct['customization']) ? $amzProduct['customization'] : null;
        $order_item->id_order_detail = $id_order_detail;
        $order_item->additional_info = (array)$amzProduct['additional_info'];

        if (!$order_item->saveOrderItem()) {
            print implode('. ', AmazonOrderItem::getErrors());   
            print Tools::displayError(sprintf('%s - %s (%d/%d). ', $this->l('Unable to add item to ordered item table'), $orderId, $idProduct, $idProductAttribute));
        }
    }

    /**
     * @return int
     */
    protected function getIdWarehouse()
    {
        if (is_null($this->idWarehouse)) {
            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                $this->idWarehouse = (int)Configuration::get('AMAZON_WAREHOUSE');
            } else {
                $this->idWarehouse = 0;
            }
        }
        
        return $this->idWarehouse;
    }
    
    protected function getNullDate()
    {
        return '0000-00-00 00:00:00';
    }

    protected function getProductSku($psProduct)
    {
        return trim((string)$psProduct['reference']);
    }
    
    protected function getIdProduct($psProduct)
    {
        return (int)$psProduct['id_product'];
    }
    
    protected function getIdProductAttribute($psProduct)
    {
        return $psProduct['id_product_attribute'] ? (int)$psProduct['id_product_attribute'] : null;
    }
    
    /**
     * @param array $psProduct
     * @param array $amazonProduct
     * @return string
     */
    protected function getProductName($psProduct, $amazonProduct)
    {
        $product_name = $psProduct['name'].((isset($psProduct['attributes']) && $psProduct['attributes'] != null) ? ' - '.$psProduct['attributes'] : '');
        $product_name = trim($product_name);

        if (empty($product_name) || !Tools::strlen($product_name) || Tools::strlen($product_name) <= 3) {
            $product_name = isset($amazonProduct['name']) ? (string) $amazonProduct['name'] : 'unknown' ;
        }
        
        return $product_name;
    }

    /**
     * @param $id_order_state
     * @param AmazonCart $amazonCart
     * @param $order
     * @param $customer
     * @param $currency
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function hookActivation($id_order_state, $amazonCart, $order, $customer, $currency)
    {
        $orderStatus = new OrderState((int)$id_order_state);

        if (Validate::isLoadedObject($orderStatus)) {
            if (version_compare(_PS_VERSION_, '1.5', '<')) {
                Hook::newOrder($amazonCart, $order, $customer, $currency, $orderStatus);
            } else {
                $reinitContext = AmazonTools::reInitContextControllerIfNeed($this->context);
                if ($reinitContext['reinit']) {
                    $this->context = $reinitContext['context'];
                }
                Hook::exec('actionValidateOrder', array(
                    'cart' => $amazonCart,
                    'order' => $order,
                    'customer' => $customer,
                    'currency' => $currency,
                    'orderStatus' => $orderStatus
                ));
            }
            foreach ($amazonCart->getProducts() as $product) {
                if ($orderStatus->logable) {
                    ProductSale::addProductSale((int)$product['id_product'], (int)$product['cart_quantity']);
                }
            }
        }
    }

    public function getError()
    {
        return $this->error;
    }

    protected function handleError($error)
    {
        $this->error = $error;
        echo $error;
    }
}
