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
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice@common-services.com
 */

require_once(_PS_MODULE_DIR_.'sonice_suivicolis/sonice_suivicolis.php');
require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviTools.php');
require_once(_PS_MODULE_DIR_.'sonice_suivicolis/classes/SoNiceSuiviWebService.php');

class SoNiceMessaging extends SoNice_SuiviColis
{

    /*
     * Class parameters
     */
    public $debug;
    private $iso_lang;

    /** @var $order Order */
    public $order;

    /** @var $customer Customer */
    public $customer;

    /** @var $event SoNiceSuiviEvent */
    public $event;

    /** @var $tracking SoNiceSuiviWebService */
    public $tracking;
    public $template = '';
    public $letter_subject = '';
    public $path_mail_sc_tpl = '';
    public $path_mail_ps_tpl = '';
    public $attachment_file = array();


    public function __construct($debug = false, $shipping_number = null)
    {
        parent::__construct();

        $this->debug = $debug;
        $this->path = _PS_MODULE_DIR_.'sonice_suivicolis/';
        $this->path_mail_sc_tpl = $this->path.'mails/';
        $this->path_mail_ps_tpl = __PS_BASE_URI__.'mails/';

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $this->id_lang = (int)Context::getContext()->language->id;
        } else {
            require_once(dirname(__FILE__).'/../backward_compatibility/backward.php');

            $this->context = Context::getContext();

            if (!isset($this->context->language->id)) {
                $this->id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
            } else {
                $this->id_lang = (int)$this->context->language->id;
            }
        }

        $this->iso_lang = Language::getIsoById($this->id_lang);
        if (!$this->iso_lang) {
            $this->iso_lang = 'fr';
        }

        $this->event = new SoNiceSuiviEvent($this->iso_lang);
        $this->tracking = new SoNiceSuiviWebService($shipping_number);
    }


    public function l($str, $specific = null, $id_lang = null)
    {
        if (!$specific) {
            $specific = basename(__FILE__, '.php');
        }

        return (parent::l($str, $specific, $id_lang));
    }


    /**
     * Check if every Object is properly loaded
     *
     * @return boolean
     */
    public function isLoadedClass()
    {
        if (!Validate::isLoadedObject($this->order) || !Validate::isLoadedObject($this->customer)
            || empty($this->template)) {
            return (false);
        }

        return (true);
    }


    public function sendMail($invoice = false, $delivery_slip = false, $shipping_number = null)
    {
        if (isset($this->order->id_lang) && $this->order->id_lang) {
            $this->iso_lang = Language::getIsoById($this->order->id_lang);
        }

        if (!file_exists($this->path_mail_sc_tpl.$this->iso_lang.'/'.$this->template.'.html') ||
            !file_exists($this->path_mail_sc_tpl.$this->iso_lang.'/'.$this->template.'.txt')) {
            var_dump(
                'File HTML or TXT does not exist.',
                $this->path_mail_sc_tpl.$this->iso_lang.'/'.$this->template.'.html/txt'
            );

            return (false);
        }

        $conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_CONF'));
        $carrier = new Carrier((int)$this->order->id_carrier);
        $followup = '';
        $invoice_result = 0;
        $delivery_slip_result = 0;

        if (Validate::isLoadedObject($carrier) && isset($shipping_number) && $shipping_number) {
            $followup = str_replace('@', $shipping_number, $carrier->url);
        }

        $template_vars = array();
        $template_vars['{firstname}'] = htmlentities($this->customer->firstname, ENT_COMPAT, 'UTF-8');
        $template_vars['{lastname}'] = htmlentities($this->customer->lastname, ENT_COMPAT, 'UTF-8');
        $template_vars['{service_notation_url}'] = (isset($conf['rating_service']) && $conf['rating_service']) ?
            $conf['rating_service'] : 'N/A';
        $template_vars['{followup}'] = $followup;
        $template_vars['{order_id}'] = $this->order->id;
        $template_vars['{order_reference}'] = isset($this->order->reference) ?
            $this->order->reference : $this->order->id;
        $template_vars['{order_date}'] = SoNiceSuiviTools::displayDate(($this->order->date_add));

        $coliposte_state = $this->event->getCodes($this->tracking->inovert);

        $template_vars['{text_status}'] = $coliposte_state;
        
        $template_vars['{date_status}'] = $this->tracking->coliposte_date;

        if (Tools::strtolower($this->iso_lang) !== 'fr' && $coliposte_state) {
            $this->letter_subject = Tools::substr($coliposte_state, 0, 32);
            if (Tools::strlen($coliposte_state) > 32) {
                $this->letter_subject .= '...';
            }
        }

        ob_start();

        if ($invoice) {
            $invoice_result = $this->sendInvoiceMail($template_vars);
        }

        if ($delivery_slip && version_compare(_PS_VERSION_, '1.5', '>=')) {
            $delivery_slip_result = $this->sendDeliverySlipMail($template_vars);
        }

        ob_get_clean();

        $result = Mail::Send(
            $this->order->id_lang, // id_lang
            $this->template, // template
            $this->letter_subject, // subject
            $template_vars, // templateVars
            Tools::strtolower($this->customer->email), // to
            sprintf('%s %s', $this->customer->firstname, $this->customer->lastname), // To Name
            null, // From
            null, // From Name !!
            null, // $this->attachment_file, // Attachment
            null, // SMTP
            $this->path_mail_sc_tpl
        );

        if (!$result) {
            var_dump('Problem with function Mail::Send from PrestaShop...');
        }

        return ($result + $invoice_result + $delivery_slip_result);
    }


    public function sendInvoiceMail($template_vars)
    {
        if (empty($template_vars)) {
            return (false);
        }

        $conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_CONF'));

        if (!(isset($conf['invoice_tpl']) && $conf['invoice_tpl'])) {
            return (false);
        }

        if (!file_exists($this->path_mail_sc_tpl.$this->iso_lang.'/'.$conf['invoice_tpl'].'.html') ||
            !file_exists($this->path_mail_sc_tpl.$this->iso_lang.'/'.$conf['invoice_tpl'].'.txt')) {
            return (false);
        }

        $send_invoice = new SoNiceMessaging();
        $send_invoice->order = $this->order;
        $send_invoice->customer = $this->customer;
        $send_invoice->template = $conf['invoice_tpl'];
        $send_invoice->letter_subject = sprintf(
            '%s %d.',
            html_entity_decode($this->l('Invoice for your order'), ENT_COMPAT, 'UTF-8'),
            (int)$send_invoice->order->id
        );

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $pdf = new PDF($this->order->getInvoicesCollection(), PDF::TEMPLATE_INVOICE, Context::getContext()->smarty);
            $send_invoice->attachment_file[0]['content'] = $pdf->render(false);
            $send_invoice->attachment_file[0]['name'] = Configuration::get(
                'PS_INVOICE_PREFIX',
                (int)$this->order->id_lang,
                null,
                $this->order->id_shop
            ).sprintf('%06d', $this->order->invoice_number).'.pdf';
            $send_invoice->attachment_file[0]['mime'] = 'application/pdf';
        } else {
            $file_attachement = array();
            $file_attachement['content'] = PDF::invoice($this->order, 'S');
            $file_attachement['name'] = Configuration::get(
                'PS_INVOICE_PREFIX',
                (int)$this->order->id_lang
            ).sprintf('%06d', $this->order->invoice_number).'.pdf';
            $file_attachement['mime'] = 'application/pdf';
        }

        $invoice_result = Mail::Send(
            $this->order->id_lang, // id_lang
            $send_invoice->template, // template
            $send_invoice->letter_subject, // subject
            $template_vars, // templateVars
            Tools::strtolower($this->customer->email), // to
            sprintf('%s %s', $this->customer->firstname, $this->customer->lastname), // To Name
            null, // From
            null, // From Name
            $send_invoice->attachment_file, // Attachment
            null, // SMTP
            $this->path_mail_sc_tpl
        );

        return ($invoice_result);
    }


    public function sendDeliverySlipMail($template_vars)
    {
        if (!version_compare(_PS_VERSION_, '1.5', '>=')) {
            return (true);
        }

        if (empty($template_vars)) {
            return (false);
        }

        $conf = unserialize(Configuration::get('SONICE_SUIVICOLIS_CONF'));

        if (!(isset($conf['delivery_slip_tpl']) && $conf['delivery_slip_tpl'])) {
            return (false);
        }

        if (!file_exists($this->path_mail_sc_tpl.$this->iso_lang.'/'.$conf['delivery_slip_tpl'].'.html') ||
            !file_exists($this->path_mail_sc_tpl.$this->iso_lang.'/'.$conf['delivery_slip_tpl'].'.txt')) {
            return (false);
        }

        $send_delivery_slip = new SoNiceMessaging();
        $send_delivery_slip->order = $this->order;
        $send_delivery_slip->customer = $this->customer;
        $send_delivery_slip->template = $conf['delivery_slip_tpl'];
        $send_delivery_slip->letter_subject = sprintf(
            '%s %d.',
            html_entity_decode($this->l('Delivery slip for your order'), ENT_COMPAT, 'UTF-8'),
            (int)$send_delivery_slip->order->id
        );

        $pdf = new PDF(
            $this->order->getDeliverySlipsCollection(),
            PDF::TEMPLATE_DELIVERY_SLIP,
            Context::getContext()->smarty
        );
        $send_delivery_slip->attachment_file[0]['content'] = $pdf->render(false);
        $send_delivery_slip->attachment_file[0]['name'] = Configuration::get(
            'PS_DELIVERY_PREFIX',
            (int)$this->order->id_lang,
            null,
            $this->order->id_shop
        ).sprintf('%06d', $this->order->invoice_number).'.pdf';
        $send_delivery_slip->attachment_file[0]['mime'] = 'application/pdf';

        $delivery_slip_result = Mail::Send(
            $this->order->id_lang, // id_lang
            $send_delivery_slip->template, // template
            $send_delivery_slip->letter_subject, // subject
            $template_vars, // templateVars
            Tools::strtolower($this->customer->email), // to
            sprintf('%s %s', $this->customer->firstname, $this->customer->lastname), // To Name
            null, // From
            null, // From Name
            $send_delivery_slip->attachment_file, // Attachment
            null, // SMTP
            $this->path_mail_sc_tpl
        );

        return ($delivery_slip_result);
    }
}
