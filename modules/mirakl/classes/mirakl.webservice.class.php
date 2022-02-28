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

require_once dirname(__FILE__).'/helper/mirakl.array2xml.php';
require_once dirname(__FILE__).'/helper/mirakl.xml2array.php';
require_once dirname(__FILE__).'/helper/mirakl.query.php';
require_once dirname(__FILE__).'/helper/mirakl.errors.php';

if (!class_exists('MiraklWebservice')) {
    class MiraklWebservice
    {
        #const MIRAKL_API_ENDPOINT = 'https://moa-recette.mirakl.net/api/';

        protected $config               = array();
        protected $service_url          = null;
        protected $service_method       = null;
        protected $service              = null;
        protected $service_code         = null;
        protected $service_child        = null;
        protected $errors               = null;
        protected $valid_http_responses = array('200', '201', '204');

        //taken from config class;
        protected $error_debug = false;
        protected $api_key     = '';

        /**
         * Initializes global values used in Mirakl Module
         */
        public function __construct($marketplace_params)
        {
            $this->api_key = $marketplace_params['api_key'];
            $this->endpoint = $marketplace_params['endpoint'];
            $this->error_debug = (bool)(isset($marketplace_params['debug']) && $marketplace_params['debug']);

            $this->config['output'] = 'xml';
            //$this->config['if_xml_output_raw'] = true;
        }


        public function get($filename = '', $request_type = 'json')
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            // Some stores have non-working API if this option present: https://support.common-services.com/a/tickets/104447
            curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__).'/libraries/cacert.pem');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json'));

            if ($request_type == 'xml') {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml', 'Accept: application/xml'));
            } elseif ($request_type == 'json') {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Accept: application/json'
                ));
            }

            if (!empty($filename)) {
                $fp = fopen($filename, 'w');
                curl_setopt($ch, CURLOPT_FILE, $fp);
            }
            curl_setopt($ch, CURLOPT_URL, $this->service_url);
            $data = curl_exec($ch);
            $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($this->error_debug) {
                MiraklTools::pre(array(
                    $this->service_url,
                    nl2br(print_r(curl_getinfo($ch), true)),
                    $data
                ));
            }

            //display errors
            $cErr = curl_errno($ch);
            if ($cErr != '') {
                $err = 'cURL ERROR: '.curl_error($ch).': '.$cErr.nl2br("\n");
                foreach (curl_getinfo($ch) as $k => $v) {
                    if (is_array($v)) {
                        $v = print_r($v, true);
                    }
                    $err .= "$k: $v".nl2br("\n");
                }
                curl_close($ch);
                if (isset($fp)) {
                    fclose($fp);
                }

                return $this->errors($http_status, $err, false);
            }
            curl_close($ch);
            if (isset($fp)) {
                fclose($fp);
            }

            if (!in_array($http_status, $this->valid_http_responses)) {
                return $this->errors($http_status, $data, false);
            } else {
                if (Tools::strlen($data) < 2) {
                    return $this->errors($http_status, 'REQUEST_SUCCESS');
                }
            }

            return $data;
        }

        public function put($request_type = 'json', $params = array())
        {
            return $this->postPut('put', $request_type, $params);
        }

        // end func

        public function post($request_type = 'json', $params = array())
        {
            return $this->postPut('post', $request_type, $params);
        }

        // end func

        protected function parse($json)
        {
            if (is_array($json)) {
                $json = Tools::jsonEncode($json);
            }
            $response_type = $this->responseType($json);
            if ($response_type == 'invalid') {
                return $json;
            }

            if (!isset($this->config['output']) || $this->config['output'] == 'json') {
                return $json;
            } elseif ($this->config['output'] == 'array') {
                return $this->json2array($json);
            } elseif ($this->config['output'] == 'xml') {
                $json = str_replace(array('`', '\\b'), '', $json);
                $array = $this->json2array($json);
                $xml = MiraklHelperArray2XML::createXML('mirakl_'.Tools::strtolower(str_replace('/', '_', $this->service)), $array);

                return $xml->saveXML();
            } else {
                return $json;
            }
        }

        // end func

        protected function json2array($json, $assoc = true)
        {
            return Tools::jsonDecode($json, $assoc);
        }

        // end func

        protected function errors($code = 0, $reason = '', $parse = true)
        {
            $info = array();
            $info['error_code'] = $code;
            $info['service'] = ucwords($this->service);
            $info['service_child'] = ucwords($this->service_child);
            $info['service_code'] = $this->service_code;
            $info['service_method'] = $this->service_method;
            $info['error'] = $reason;

            $errors = MiraklHelperErrors::explain($info);
            if ($code != 200) {
                $this->service = 'Errors';
            }

            if ($this->error_debug) {
                printf('%s(#%d): errors: %s', basename(__FILE__), __LINE__, nl2br(print_r($info, true)));
            }

            return ($parse) ? $this->parse($errors) : $errors;
        }

        // end func

        private function postPut($type = 'post', $request_type = 'json', $params = array())
        {
            if ($request_type == 'csv') {
                $headers = array('Accept: application/json', 'Content-Type: multipart/form-data');
            //$params['type']='text/csv';
            } elseif ($request_type == 'json') {
                $headers = array('Accept: application/json', 'Content-Type: application/json');
                $params = Tools::jsonEncode($params);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->service_url);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            // Some stores have non-working API if this option present: https://support.common-services.com/a/tickets/104447
            curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__).'/libraries/cacert.pem');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            //$f = fopen(dirname(__FILE__).'/request.txt', 'w');
            //curl_setopt($ch, CURLOPT_STDERR, $f);

            if ($type == 'put') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            } else {
                curl_setopt($ch, CURLOPT_POST, 1);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

            if ($this->error_debug) {
                printf(
                    '%s(#%d): Webservice url: %s query: %s',
                    basename(__FILE__),
                    __LINE__,
                    $this->service_url,
                    nl2br(print_r($params, true))
                );
            }

            $data = curl_exec($ch);
            $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            //display errors
            $cErr = curl_errno($ch);
            if ($cErr != '') {
                $err = 'cURL ERROR: '.$cErr.': '.curl_error($ch)."\r\n";
                foreach (curl_getinfo($ch) as $k => $v) {
                    if (is_array($v)) {
                        $err .= "$k: ".print_r($v, true)."\r\n";
                    } else {
                        $err .= "$k: $v"."\r\n";
                    }
                }
                curl_close($ch);

                return $this->errors($http_status, $err, false);
            }

            curl_close($ch);
            if (!in_array($http_status, $this->valid_http_responses)) {
                return $this->errors($http_status, $data, false);
            }

            return $data;
        }

        // end func

        private function responseType($data = '')
        {
            $data = trim($data);
            if (isset($data[0]) && $data[0] == '<') {
                return 'xml';
            } elseif (isset($data[0]) && ($data[0] == '{' || $data[0] == '[')) {
                return 'json';
            } else {
                return 'invalid';
            }
        }

        // end func
    }
}
