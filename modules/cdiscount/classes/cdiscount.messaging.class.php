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

require_once(dirname(__FILE__).'/../classes/cdiscount.order.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.tools.class.php');

class CDiscountMessaging extends Cdiscount
{
    public static $invoice_subjects
        = array(
            'fr' => 'Facture pour votre commande',
            'en' => 'Invoice for your order',
            'de' => 'Rechnung f&uuml;r ihre bestellung',
            'it' => 'Fattura per il vostro ordine',
            'es' => 'Factura de su pedido'
        );
    public static $review_subjects
        = array(
            'fr' => 'Evaluation concernant votre commande N&ordm;',
            'en' => 'Seller Rating for your order No.',
            'de' => 'Bewertung Ihrer Bestellung Nr.',
            'it' => 'Valutazione del Suo ordine N&ordm;',
            'es' => 'Evaluaci&oacute;n relativa a su pedido N&ordm;'
        );
    private $_debug;

    public function __construct($debug = false)
    {
        if ($debug) {
            $this->_debug = true;
        } else {
            $this->_debug = false;
        }

        $this->path_pdf = $this->path.'pdf/';
        $this->path_mail = $this->path.'mail/';

        parent::__construct();
    }

    public function sendInvoice($id_order)
    {
        $mail_invoice = unserialize(CDiscountTools::decode(Configuration::get('CDISCOUNT_MESSAGING')));

        if (!isset($mail_invoice['active']) || !(int)$mail_invoice['active']) {
            return (false);
        }

        if (!isset($mail_invoice['template']) || empty($mail_invoice['template'])) {
            if ($this->_debug) {
                printf('%s:#%d You must select an email template'."<br />\n", basename(__FILE__), __LINE__);
            }

            return (false);
        }

        $order = new CDiscountOrder($id_order);

        if (!Validate::isLoadedObject($order) || !$order->id_lang || !isset($order->marketPlaceOrderId) || empty($order->marketPlaceOrderId)) {
            if ($this->_debug) {
                printf('%s:#%d Invalid Order (%d)'."<br />\n", basename(__FILE__), __LINE__, $id_order);
            }

            return (false);
        }

        if (!$order->invoice_number) {
            if ($this->_debug) {
                printf('%s:#%d Invalid processing for Order (%d) - Order has no invoice number'."<br />\n", basename(__FILE__), __LINE__, $id_order);
            }

            return (false);
        }

        $customer = new Customer($order->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            if ($this->_debug) {
                printf('%s:#%d Invalid Customer (%d)'."<br />\n", basename(__FILE__), __LINE__, $order->id_customer);
            }

            return (false);
        }

        $id_lang = $order->id_lang;
        $lang = Language::getIsoById($id_lang);

        $template_file = sprintf('%s%s/%s.html', $this->path_mail, $lang, $mail_invoice['template']);

        if (!file_exists($template_file)) {
            if ($this->_debug) {
                printf('%s:#%d Template file doesn\'t exists for this lang: %s(%d)'."<br />\n", basename(__FILE__), __LINE__, $lang, $id_lang);
            }

            return (false);
        }

        $template_vars = array();

        $template_vars['{firstname}'] = htmlentities($customer->firstname, ENT_COMPAT, 'UTF-8');
        $template_vars['{lastname}'] = htmlentities($customer->lastname, ENT_COMPAT, 'UTF-8');

        $template_vars['{cdiscount_order_id}'] = $order->marketPlaceOrderId;
        $template_vars['{cdiscount_order_date}'] = CommonTools::displayDate($order->date_add, $id_lang);

        if (isset(self::$invoice_subjects[$lang])) {
            $title = self::$invoice_subjects[$lang];
        } else {
            $title = self::$invoice_subjects['en'];
        }

        $email_subject = sprintf('%s %s', html_entity_decode($title, ENT_COMPAT, 'UTF-8'), $order->marketPlaceOrderId);

        $email = $customer->email;
        $to_name = sprintf('%s %s', $customer->firstname, $customer->lastname);

        ob_start(); // prevent output

        $file_attachement = array();

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $pdf = new PDF($order->getInvoicesCollection(), PDF::TEMPLATE_INVOICE, Context::getContext()->smarty);
            $file_attachement[0]['content'] = $pdf->render(false);
            $file_attachement[0]['name'] = Configuration::get('PS_INVOICE_PREFIX', (int)$order->id_lang, null, $order->id_shop).sprintf('%06d', $order->invoice_number).'.pdf';
            $file_attachement[0]['mime'] = 'application/pdf';

            if (isset($mail_invoice['additionnal']) && file_exists($this->path_pdf.$mail_invoice['additionnal'])) {
                $file_attachement[1]['content'] = CDiscountTools::file_get_contents($this->path_pdf.$mail_invoice['additionnal']);
                $file_attachement[1]['name'] = $mail_invoice['additionnal'];
                $file_attachement[1]['mime'] = 'application/pdf';
            }
        } else {
            $this->context = Context::getContext();
            $cookie = &$this->context->cookie;

            $id_cdiscount_employee = (int)Configuration::get(self::KEY.'_EMPLOYEE');

            $employee = new Employee((int)$id_cdiscount_employee ? (int)$id_cdiscount_employee : $cookie->id_employee);

            if (Validate::isLoadedObject($employee)) {
                $cookie->id_employee = $employee->id;
            }

            $file_attachement['content'] = PDF::invoice($order, 'S');
            $file_attachement['name'] = Configuration::get('PS_INVOICE_PREFIX', (int)$order->id_lang).sprintf('%06d', $order->invoice_number).'.pdf';
            $file_attachement['mime'] = 'application/pdf';
        }

        if ($this->_debug) {
            printf('%s:#%d Attachments: %s'."<br />\n", basename(__FILE__), __LINE__, nl2br(print_r($file_attachement, true)));
        } else {
            ob_get_clean();
        }

        $result = Mail::Send(
            $id_lang, // id_lang
            $mail_invoice['template'], // template
            $email_subject, // subject
            $template_vars, // templateVars
            $email, // to
            $to_name, // To Name
            null, // From
            null, // From Name
            $file_attachement, // Attachment
            null, // SMTP
            $this->path_mail
        );

        return ($result);
    }
}
