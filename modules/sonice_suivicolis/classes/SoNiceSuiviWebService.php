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

class SoNiceSuiviWebService
{

    /** Supervision link to check web service availability */
    const WS_SUPERVISION = 'http://ws.colissimo.fr/supervisionWSShipping/supervision.jsp';

    /** Web Service URL*/
    const SCSC_WS = 'https://www.coliposte.fr/tracking-chargeur-cxf/TrackingServiceWS/track?';

    /** @var mixed The web service response */
    public $response = null;

    /** @var mixed The web service response, remain untouched */
    public $origin_response = null;

    /** @var mixed The web service request */
    public $origin_request = null;

    protected $debug = false;

    public $skybillnumber;
    public $error_code;
    public $error_message;
    public $event_code;
    public $event_date;
    public $event_libelle;
    public $event_site;
    public $coliposte_location;
    public $coliposte_state;
    public $coliposte_date;
    public $recipient_city;
    public $recipient_country_code;
    public $recipient_zip_code;
    public $mail;
    public $incentive;
    public $inovert;

    public function __construct($skybillnumber = null, $login = null, $pwd = null)
    {
        $this->skybillnumber = $skybillnumber;
        if ($this->skybillnumber) {
            $tracking = Db::getInstance()->executeS(
                'SELECT *
                FROM `'._DB_PREFIX_.'sonice_suivicolis`
                WHERE `shipping_number` = "'.pSQL($this->skybillnumber).'"'
            );

            $tracking = (array)reset($tracking);
            array_walk($tracking, array($this, 'mapValueToclass'));

            $location = explode(',', $this->coliposte_location);
            $this->event_site = isset($location[0]) ? trim($location[0]) : '';
            $this->recipient_zip_code = isset($location[1]) ? trim($location[1]) : '';
            $this->recipient_city = isset($location[2]) ? trim($location[2]) : '';
            $this->recipient_country_code = isset($location[3]) ? trim($location[3]) : '';
            $this->event_libelle = $this->coliposte_state;
            $this->event_date = $this->coliposte_date;
        }

        $this->module_params = unserialize(Configuration::get('SONICE_SUIVICOLIS_CONF'));
        $this->module_params = is_array($this->module_params) ? $this->module_params : array();

        $this->debug_mode = (bool)array_key_exists('debug', $this->module_params) ?
            $this->module_params['debug'] : false;

        $this->test_mode = (bool)array_key_exists('demo', $this->module_params) ?
            $this->module_params['demo'] : false;

        if (!$this->debug_mode && Tools::getValue('debug')) {
            $this->debug_mode = true;
        }

        if ($login && $pwd) {
            $this->module_params['login'] = $login;
            $this->module_params['pwd'] = $pwd;
        }

        if ($this->debug_mode) {
            ini_set('display_errors', 'on');
            error_reporting(E_ALL|E_STRICT);
        }
    }

    private function mapValueToclass($v, $k)
    {
        $this->{trim($k)} = trim($v);
    }


    public function call()
    {
        $url = sprintf(
            '%s%s',
            self::SCSC_WS,
            http_build_query(array(
                'accountNumber' => $this->module_params['login'],
                'password' => $this->module_params['pwd'],
                'skybillNumber' => $this->skybillnumber
            ))
        );

        $this->origin_request = $url;
        $this->origin_response = Tools::file_get_contents($url);

        return ($this);
    }

    public function setResponse()
    {
        $this->origin_response = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $this->origin_response);
        $this->origin_response = preg_replace('/[a-zA-Z0-9]+:([a-zA-Z0-9]+[ =>])/', '$1', $this->origin_response);

        $this->response = simplexml_load_string(
            $this->origin_response,
            'SimpleXmlElement',
            LIBXML_NOERROR|LIBXML_ERR_FATAL|LIBXML_ERR_NONE
        );

        try {
            $this->response->asXML();
        } catch (Exception $e) {
            $this->response = simplexml_load_string(
                '<errorResponse>
                    <httpCode></httpCode>
                    <httpMessage></httpMessage>
                    <moreInformation>No response from Coliposte API. '.$e->getMessage().'</moreInformation>
                </errorResponse>'
            );
        }

        return $this;
    }

    public function parse()
    {
        if (!$this->response instanceof SimpleXMLElement) {
            return $this;
        }

        $this->error_code = (int)$this->response->Body->trackResponse->return->errorCode;
        $this->error_message = isset($this->response->Body->trackResponse->return->errorMessage) ?
            (string)$this->response->Body->trackResponse->return->errorMessage : '';
        $this->event_code = (string)$this->response->Body->trackResponse->return->eventCode;
        $this->event_date = (string)$this->response->Body->trackResponse->return->eventDate;
        $this->event_libelle = (string)$this->response->Body->trackResponse->return->eventLibelle;
        $this->event_site = (string)$this->response->Body->trackResponse->return->eventSite;
        $this->recipient_city = (string)$this->response->Body->trackResponse->return->recipientCity;
        $this->recipient_country_code = (string)$this->response->Body->trackResponse->return->recipientCountryCode;
        $this->recipient_zip_code = (string)$this->response->Body->trackResponse->return->recipientZipCode;
        $this->inovert = (string)$this->response->Body->trackResponse->return->eventCode;

        return $this;
    }


    /**
     * Check the availability of ColiPoste web services
     *
     * @return boolean Supervision status
     */
    public function webServiceSupervision()
    {
        if ($this->debug) {
            return (true);
        }

        $supervision = Tools::file_get_contents(self::WS_SUPERVISION);

        if (!$supervision) {
            return (false);
        }

        if (preg_match('[OK]', $supervision)) {
            return (true);
        }

        return (false);
    }

    public function xmlpp($xml, $html_output = false)
    {
        $xml_obj = new SimpleXMLElement($xml);
        $level = 4;
        $indent = 0;
        $pretty = array();

        $xml = explode("\n", preg_replace('/>\s*</', ">\n<", $xml_obj->asXML()));

        if (count($xml) && preg_match('/^<\?\s*xml/', $xml[0])) {
            $pretty[] = array_shift($xml);
        }

        foreach ($xml as $el) {
            if (preg_match('/^<([\w])+[^>\/]*>$/U', $el)) {
                $pretty[] = str_repeat(' ', $indent).$el;
                $indent += $level;
            } else {
                if (preg_match('/^<\/.+>$/', $el)) {
                    $indent -= $level;
                }
                if ($indent < 0) {
                    $indent += $level;
                }
                $pretty[] = str_repeat(' ', $indent).$el;
            }
        }

        $xml = implode("\n", $pretty);

        return ($html_output) ? htmlentities($xml) : $xml;
    }

    public function saveParcelInformation($id_order = null, $mail = 0)
    {
        if (!$id_order) {
            return false;
        }

        $check_mail = array(
            'mail' => null,
            'date_add' => null
        );

        if (!$mail) {
            $check_mail = Db::getInstance()->getRow(
                'SELECT `mail`, `date_add`
                FROM `'._DB_PREFIX_.'sonice_suivicolis`
                WHERE `shipping_number` = "'.pSQL($this->skybillnumber).'"'
            );
        }

        $sql = 'REPLACE INTO `'._DB_PREFIX_.'sonice_suivicolis` VALUES ("'.
            pSQL($this->skybillnumber).'", "'.
            pSQL($id_order).'", "'.
            pSQL($this->inovert).'", "'.
            pSQL($this->event_libelle).'", "'.
            pSQL(date('Y-m-d', strtotime($this->event_date))).'", "'.
            pSQL(
                $this->event_site.', '.$this->recipient_zip_code.' '.$this->recipient_city.
                ', '.$this->recipient_country_code
            ).'", "'.
            (isset($check_mail['mail']) && $check_mail['mail'] ? (int)$check_mail['mail'] : (int)$mail).'", "'.
            pSQL('0').'", "'.
            (isset($check_mail['date_add']) && $check_mail['date_add'] ?
                pSQL($check_mail['date_add']) : pSQL(date('Y-m-d H:i:s'))).'", "'.
            pSQL(date('Y-m-d H:i:s')).'")';

        return (bool)Db::getInstance()->execute($sql);
    }
}
