<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

/**
 * @since 1.5
 */
class HTMLTemplateAmazonInvoice extends HTMLTemplate
{
    public $order;
    public $order_invoice;
    public $available_in_your_account = false;
    
    protected $ps17x = false;
    protected $ps16x = false;
    protected $ps15x = false;
    protected $ps14x = false;

    /**
     * @param OrderInvoice $order_invoice
     * @param $smarty
     *
     * @throws PrestaShopException
     */
    public function __construct(OrderInvoice $order_invoice, $smarty, $bulk_mode = false)
    {
        $this->order_invoice = $order_invoice;
        $this->order = new Order((int) $this->order_invoice->id_order);
        $this->smarty = $smarty;

        $this->detectPSVersion();

        if ($this->detectPSModernVersion()) {
            // If shop_address is null, then update it with current one.
            // But no DB save required here to avoid massive updates for bulk PDF generation case.
            // (DB: bug fixed in 1.6.1.1 with upgrade SQL script to avoid null shop_address in old orderInvoices)
            if (!isset($this->order_invoice->shop_address) || !$this->order_invoice->shop_address) {
                $this->order_invoice->shop_address = OrderInvoice::getCurrentFormattedShopAddress((int) $this->order->id_shop);
                if (!$bulk_mode) {
                    OrderInvoice::fixAllShopAddresses();
                }
            }            
        }

        // header information
        // VIDR: Set invoice date is today (the date it's generated)
        $this->date = Tools::displayDate(date('Y-m-d'));

        $id_lang = Context::getContext()->language->id;
        // VIDR: Override invoice title by shipmentId
        $overrideTitle = Context::getContext()->smarty->getTemplateVars('invoice_title');
        $titlePrefix = Context::getContext()->smarty->getTemplateVars('vcs_title_prefix');
        $this->title = $overrideTitle ? $overrideTitle
            : (($titlePrefix ? $titlePrefix : '') . $order_invoice->getInvoiceNumberFormatted($id_lang));

        $this->shop = new Shop((int) $this->order->id_shop);
    }

    /**
     * Returns the template's HTML header.
     *
     * @return string HTML header
     */
    public function getHeader()
    {
        if ($this->detectPSModernVersion()) {
            $isCreditNote = Context::getContext()->smarty->getTemplateVars('is_credit_note');
            $this->assignCommonHeaderData();
            if ($this->ps17x) {
                if ($isCreditNote) {
                    $translation = Context::getContext()->getTranslator()->trans('Credit Note', array(), 'Shop.Pdf');
                } else {
                    $translation = Context::getContext()->getTranslator()->trans('Invoice', array(), 'Shop.Pdf');
                }
            } else {
                if ($isCreditNote) {
                    $translation = HTMLTemplateInvoice::l('Credit Note');
                } else {
                    $translation = HTMLTemplateInvoice::l('Invoice');       
                }
            }
            $this->smarty->assign(array('header' => $translation));

            return $this->smarty->fetch($this->getTemplate('header'));
        } else {
            return parent::getHeader();
        }
    }

    /**
     * Compute layout elements size.
     *
     * @param $params Array Layout elements
     *
     * @return array Layout elements columns size
     */
    protected function computeLayout($params)
    {
        $layout = array(
            'reference' => array(
                'width' => 15,
            ),
            'product' => array(
                'width' => 40,
            ),
            'quantity' => array(
                'width' => 8,
            ),
            'tax_code' => array(
                'width' => 8,
            ),
            'unit_price_tax_excl' => array(
                'width' => 0,
            ),
            'total_tax_excl' => array(
                'width' => 0,
            ),
        );

        if (isset($params['has_discount']) && $params['has_discount']) {
            $layout['before_discount'] = array('width' => 0);
            $layout['product']['width'] -= 7;
            $layout['reference']['width'] -= 3;
        }

        $total_width = 0;
        $free_columns_count = 0;
        foreach ($layout as $data) {
            if ($data['width'] === 0) {
                ++$free_columns_count;
            }

            $total_width += $data['width'];
        }

        $delta = 100 - $total_width;

        foreach ($layout as $row => $data) {
            if ($data['width'] === 0) {
                $layout[$row]['width'] = $delta / $free_columns_count;
            }
        }

        $layout['_colCount'] = count($layout);

        return $layout;
    }

    /**
     * Returns the template's HTML content.
     *
     * @return string HTML content
     */
    public function getContent()
    {
        $invoice_address = new Address((int) $this->order->id_address_invoice);

        $delivery_address = null;
        if (isset($this->order->id_address_delivery) && $this->order->id_address_delivery) {
            $delivery_address = new Address((int) $this->order->id_address_delivery);
        }

        $customer = new Customer((int) $this->order->id_customer);
        $carrier = new Carrier((int) $this->order->id_carrier);

        // 2020-02-25: No need to get $order_details. Because we build own one

        $has_discount = false;
        // 2020-02-25: No need to get $order_details. Because we build own one

        $cart_rules = $this->order->getCartRules($this->order_invoice->id);
        $free_shipping = false;
        foreach ($cart_rules as $key => $cart_rule) {
            if ($cart_rule['free_shipping']) {
                $free_shipping = true;
                /*
                 * Adjust cart rule value to remove the amount of the shipping.
                 * We're not interested in displaying the shipping discount as it is already shown as "Free Shipping".
                 */
                $cart_rules[$key]['value_tax_excl'] -= $this->order_invoice->total_shipping_tax_excl;
                $cart_rules[$key]['value'] -= $this->order_invoice->total_shipping_tax_incl;

                /*
                 * Don't display cart rules that are only about free shipping and don't create
                 * a discount on products.
                 */
                if ($cart_rules[$key]['value'] == 0) {
                    unset($cart_rules[$key]);
                }
            }
        }

        $product_taxes = 0;
        foreach ($this->order_invoice->getProductTaxesBreakdown($this->order) as $details) {
            $product_taxes += $details['total_amount'];
        }

        // 2020-02-25: No need to get $footer. Because we build own one

        /**
         * Need the $round_mode for the tests.
         */
        $round_type = null;
        switch (Configuration::get('PS_ROUND_TYPE')) {
            case AmazonOrder::ROUND_TOTAL:
                $round_type = 'total';

                break;
            case AmazonOrder::ROUND_LINE:
                $round_type = 'line';

                break;
            case AmazonOrder::ROUND_ITEM:
                $round_type = 'item';

                break;
            default:
                $round_type = 'line';

                break;
        }

        $display_product_images = Configuration::get('PS_PDF_IMG_INVOICE');
        $tax_excluded_display = Group::getPriceDisplayMethod($customer->id_default_group);

        $layout = $this->computeLayout(array('has_discount' => $has_discount));

        $legal_free_text = Hook::exec('displayInvoiceLegalFreeText', array('order' => $this->order));
        if (!$legal_free_text) {
            $legal_free_text = Configuration::get('PS_INVOICE_LEGAL_FREE_TEXT', (int) Context::getContext()->language->id, null, (int) $this->order->id_shop);
        }

        // Not pass some variables because we've already overwrote them 
        $data = array(
            'order' => $this->order,
            'order_invoice' => $this->order_invoice,
//            'order_details' => $order_details,
            'carrier' => $carrier,
            'cart_rules' => $cart_rules,
//            'delivery_address' => $formatted_delivery_address,
//            'invoice_address' => $formatted_invoice_address,
            'addresses' => array('invoice' => $invoice_address, 'delivery' => $delivery_address),
            'tax_excluded_display' => $tax_excluded_display,
            'display_product_images' => $display_product_images,
            'layout' => $layout,
            'tax_tab' => $this->getTaxTabContent(),
            'customer' => $customer,
//            'footer' => $footer,
            // Fixed precision for lower PS versions, because they don't have it.
            'ps_price_compute_precision' => ($this->ps17x || $this->ps16x) ? _PS_PRICE_COMPUTE_PRECISION_ : 2,
            'round_type' => $round_type,
            'legal_free_text' => $legal_free_text,
        );

        if (Tools::getValue('debug')) {
            print_r('PDF complete data');
            print_r(json_encode($data));
        }

        $this->smarty->assign($data);

        //KAM_CHG
        $tpls = array(
            'style_tab' => $this->smarty->fetch($this->getAmazonTemplatePath('invoice.style-tab')),
            'addresses_tab' => $this->smarty->fetch($this->getAmazonTemplatePath('invoice.addresses-tab')),
            'summary_tab' => $this->smarty->fetch($this->getAmazonTemplatePath('vidr-invoice.summary-tab')),
            'vat_tab' => $this->smarty->fetch($this->getAmazonTemplatePath('invoice.vat-tab')),
            'product_tab' => $this->smarty->fetch($this->getAmazonTemplatePath('vidr-invoice.product-tab')),
            'payment_tab' => $this->smarty->fetch($this->getAmazonTemplatePath('vidr-invoice.payment-tab')),
            'note_tab' => $this->smarty->fetch($this->getAmazonTemplatePath('invoice.note-tab')),
            'total_tab' => $this->smarty->fetch($this->getAmazonTemplatePath('vidr-invoice.total-tab')),
            'shipping_tab' => $this->smarty->fetch($this->getAmazonTemplatePath('invoice.shipping-tab')),
        );
        $this->smarty->assign($tpls);

        return $this->smarty->fetch($this->getAmazonTemplatePath('vidr-invoice'));
    }

    /**
     * Returns the tax tab content.
     *
     * @return string Tax tab html content
     */
    public function getTaxTabContent()
    {
        $debug = Tools::getValue('debug');

        $address = new Address((int) $this->order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        $tax_exempt = Configuration::get('VATNUMBER_MANAGEMENT')
                            && !empty($address->vat_number)
                            && $address->id_country != Configuration::get('VATNUMBER_COUNTRY');
        $carrier = new Carrier($this->order->id_carrier);

        $tax_breakdowns = $this->getTaxBreakdown();

        $data = array(
            'tax_exempt' => $tax_exempt,
            'use_one_after_another_method' => $this->order_invoice->useOneAfterAnotherTaxComputationMethod(),
            'display_tax_bases_in_breakdowns' => !$this->order_invoice->useOneAfterAnotherTaxComputationMethod(),
            'product_tax_breakdown' => $this->order_invoice->getProductTaxesBreakdown($this->order),
            'shipping_tax_breakdown' => $this->order_invoice->getShippingTaxesBreakdown($this->order),
            'ecotax_tax_breakdown' => $this->order_invoice->getEcoTaxTaxesBreakdown(),
            'wrapping_tax_breakdown' => $this->order_invoice->getWrappingTaxesBreakdown(),
            'tax_breakdowns' => $tax_breakdowns,
            'order' => $debug ? null : $this->order,
            'order_invoice' => $debug ? null : $this->order_invoice,
            'carrier' => $debug ? null : $carrier,
        );

        if ($debug) {
            print_r('PDF tax tab');
            print_r(json_encode($data));
        }

        $this->smarty->assign($data);

        return $this->smarty->fetch($this->getAmazonTemplatePath('invoice.tax-tab'));
    }

    /**
     * Returns different tax breakdown elements.
     *
     * @return array Different tax breakdown elements
     */
    protected function getTaxBreakdown()
    {
        $breakdowns = array(
            'product_tax' => $this->order_invoice->getProductTaxesBreakdown($this->order),
            'shipping_tax' => $this->order_invoice->getShippingTaxesBreakdown($this->order),
            'ecotax_tax' => $this->order_invoice->getEcoTaxTaxesBreakdown(),
            'wrapping_tax' => $this->order_invoice->getWrappingTaxesBreakdown(),
        );

        foreach ($breakdowns as $type => $bd) {
            if (empty($bd)) {
                unset($breakdowns[$type]);
            }
        }

        if (empty($breakdowns)) {
            $breakdowns = false;
        }

        if (isset($breakdowns['product_tax'])) {
            foreach ($breakdowns['product_tax'] as &$bd) {
                $bd['total_tax_excl'] = $bd['total_price_tax_excl'];
            }
        }

        if (isset($breakdowns['ecotax_tax'])) {
            foreach ($breakdowns['ecotax_tax'] as &$bd) {
                $bd['total_tax_excl'] = $bd['ecotax_tax_excl'];
                $bd['total_amount'] = $bd['ecotax_tax_incl'] - $bd['ecotax_tax_excl'];
            }
        }

        return $breakdowns;
    }

    /*
    protected function getTaxLabel($tax_breakdowns)
    {
        $tax_label = '';
        $all_taxes = array();

        foreach ($tax_breakdowns as $type => $bd)
            foreach ($bd as $line)
                if(isset($line['id_tax']))
                    $all_taxes[] = $line['id_tax'];

        $taxes = array_unique($all_taxes);

        foreach ($taxes as $id_tax) {
            $tax = new Tax($id_tax);
            $tax_label .= $tax->id.': '.$tax->name[$this->order->id_lang].' ('.$tax->rate.'%) ';
        }

        return $tax_label;
    }
    */

    /**
     * Returns the invoice template associated to the country iso_code.
     *
     * @param string $iso_country
     */
    protected function getTemplateByCountry($iso_country)
    {
        $file = Configuration::get('PS_INVOICE_MODEL');

        // try to fetch the iso template
        $template = $this->getTemplate($file . '.' . $iso_country);

        // else use the default one
        if (!$template) {
            $template = $this->getTemplate($file);
        }

        return $template;
    }

    /**
     * Returns the template filename when using bulk rendering.
     *
     * @return string filename
     */
    public function getBulkFilename()
    {
        return 'custom_invoices.pdf';
    }

    /**
     * Returns the template filename.
     *
     * @return string filename
     */
    public function getFilename()
    {
        $id_lang = Context::getContext()->language->id;
        $id_shop = (int) $this->order->id_shop;
        $format = '%1$s%2$06d';

        if (Configuration::get('PS_INVOICE_USE_YEAR')) {
            $format = Configuration::get('PS_INVOICE_YEAR_POS') ? '%1$s%3$s-%2$06d' : '%1$s%2$06d-%3$s';
        }

        return sprintf(
            $format,
            'Amazon_'.Configuration::get('PS_INVOICE_PREFIX', $id_lang, null, $id_shop),
            $this->order_invoice->number,
            date('Y', strtotime($this->order_invoice->date_add))
        ) . '.pdf';
    }
    
    protected function detectPSVersion()
    {
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->ps17x = true;
        } elseif (version_compare(_PS_VERSION_, '1.6', '>=')) {
            $this->ps16x = true;
        } elseif (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $this->ps15x = true;
        } else {
            $this->ps14x = true;
        }
    }

    protected function detectPSModernVersion()
    {
        return version_compare(_PS_VERSION_, '1.6.1.1', '>=');
    }
    
    protected function getAmazonTemplatePath($templateName)
    {
        if ($this->ps17x) {
            return "module:amazon/views/templates/front/$templateName.tpl";
        } else {
            return _PS_MODULE_DIR_."/amazon/views/templates/front/$templateName.tpl";
        }
    }
}
