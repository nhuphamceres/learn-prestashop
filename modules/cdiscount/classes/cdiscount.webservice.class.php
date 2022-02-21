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

require_once(dirname(__FILE__).'/../common/configuration.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.certificates.class.php');

class CDiscountWebservice extends CDiscount
{
    const TIMEOUT_REGULAR_POST = 360;
    const TIMEOUT_HUGE_POST = 780;

    const TIMEOUT_CONNECT = 120;

    private static $cdapi = 'cdapi.common-services.com';

    public static $configuration = array(
        'fr' => array(
            'API_SITE_ID' => '100',
            'CATALOG_ID' => '1',
            'CUSTOMER_POOL_ID' => '1',
            'COUNTRY' => 'Fr',
            'CURRENCY' => 'Eur',
            'LANGUAGE' => 'Fr',
            'URL_STS_PREPROD' => 'https://sts.preprod-cdiscount.com/users/httpIssue.svc/?realm=https://wsvc.preprod-cdiscount.com/MarketplaceAPIService.svc?channel=common-services',
            'URL_STS_PROD' => 'https://sts.cdiscount.com/users/httpIssue.svc/?realm=https://wsvc.cdiscount.com/MarketplaceAPIService.svc?channel=common-services',
            'URL_PREPROD' => 'https://wsvc.preprod-cdiscount.com/MarketplaceAPIService.svc?channel=common-services',
            'URL_PROD' => 'https://wsvc.cdiscount.com/MarketplaceAPIService.svc?channel=common-services'
        ),
        'co' => array(
            'API_SITE_ID' => '1003',
            'CATALOG_ID' => '11',
            'CUSTOMER_POOL_ID' => '11',
            'COUNTRY' => 'CO',
            'CURRENCY' => 'Cop',
            'LANGUAGE' => 'Es',
            'URL_STS_PREPROD' => 'https://sts.preprod-cds-co.net/users/httpIssue.svc/?realm=https://wsvc.preprod-cds-co.net/MarketplaceAPIService.svc ?channel=common-services',
            'URL_STS_PROD' => 'https://sts.cdiscount.com.co/users/httpIssue.svc/?realm=https://wsvc.cdiscount.com.co/MarketplaceAPIService.svc?channel=common-services',
            'URL_PREPROD' => 'https://wsvc.preprod-cds-co.net/MarketplaceAPIService.svc?channel=common-services',
            'URL_PROD' => 'https://wsvc.cdiscount.com.co/MarketplaceAPIService.svc?channel=common-services'
        )
    );

    public $token;

    protected $url;
    protected $url_sts;
    public $platform;
    protected $username;
    protected $password;
    protected $production;
    protected $demo;
    protected $genericReturn = array();

    private $logContent = '';

    protected $_cr = "<br />\n";

    protected $debug_caller = 'cdiscount.webservice';

    public function __construct($username, $password, $prod = true, $debug = false, $demo = false)
    {
        $cr = $this->_cr;

        $this->username = $username;
        $this->password = $password;
        $this->demo = $demo;
        $this->production = $prod;

        $this->locales();

        if ($debug) {
            $this->debug = true;
        }

        // Not sure why don't call parent constructor. Init debug manually
        require_once(dirname(__FILE__) . '/../includes/cdiscount.debug.php');
        $this->debugDetails = new CDiscountDebugDetails();
        $this->debugDetails->webservice('Initializing Marketplace(%s) - login: %s - password length: %d - called by %s', $prod ? 'prod' : 'preprod', $username, Tools::strlen($password));
    }

    public function locales()
    {
        $country_iso_code = Tools::strtolower(Configuration::get('PS_LOCALE_COUNTRY'));

        switch ($country_iso_code) {
            case 'co':
                $this->platform = 'co';
                break;
            default: // fr
                $this->platform = 'fr';
                break;
        }
        if ($this->production) {
            $this->url = self::$configuration[$this->platform]['URL_PROD'];
            $this->url_sts = self::$configuration[$this->platform]['URL_STS_PROD'];
        } else {
            $this->url = self::$configuration[$this->platform]['URL_PREPROD'];
            $this->url_sts = self::$configuration[$this->platform]['URL_STS_PREPROD'];
        }
    }


    protected function _caller()
    {
        $trace = debug_backtrace();
        $caller = $trace[2];

        $ret = 'called by '.$caller['function'].'() ';
        if (isset($caller['class'])) {
            $ret .= 'in '.$caller['class'];
        }

        return ($ret);
    }

    public function getToken($debug = false)
    {
        $cr = $this->_cr;

        if ($debug) {
            $this->debug = true;
        }
        $demo_dir = dirname(dirname(__FILE__)).DS.Cdiscount::XML_DIRECTORY.DS.'demo/';
        $demo_file = $demo_dir.'token_result.demo.out';

        if ($this->demo && is_dir($demo_dir) && file_exists($demo_file)) {
            sleep(2);
            $response = CDiscountTools::file_get_contents($demo_file);
        } else {
            $authentication = base64_encode($this->username.':'.$this->password);   // TODO Validation: Authentication by marketplace
            $httpheader = array('Authorization: Basic '.$authentication);

            if ($this->debug) {
                print "<pre>\n";
                printf('%s/%s: getToken() - Authorization: login: %s password length(%d) - %s'.$cr, basename(__FILE__), __LINE__, $this->username, Tools::strlen($this->password), $this->_caller());
                printf('%s/%s: getToken() - HTTP Header: %s'.$cr, basename(__FILE__), __LINE__, nl2br(print_r($httpheader, true)));
                print "</pre>\n";
                //Commented out for security issue.
                //printf('%s/%s: getToken() - Password: %s' . $cr, basename(__FILE__), __LINE__, $this->password);
                //printf('%s/%s: getToken() - Data: %s' . $cr, basename(__FILE__), __LINE__, $authentication);
            }
            $ch = curl_init();


            if (defined('CURLOPT_ENCODING')) {
                $httpheader[] = 'Accept-Encoding: gzip,deflate';
                curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            }

            curl_setopt($ch, CURLOPT_URL, $this->url_sts);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CAINFO, $cert = CdiscountCertificates::getCertificate());
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT_REGULAR_POST);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT_CONNECT);

            if (!Configuration::get('CDISCOUNT_DISABLE_PROXY')) {
                curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
                curl_setopt($ch, CURLOPT_PROXY, self::$cdapi);
                curl_setopt($ch, CURLOPT_PROXYPORT, '8888');

                if ($this->debug) {
                    echo '<pre>'."\n";
                    echo 'Using proxy: '.self::$cdapi.'\n';
                    echo '</pre>'."\n";
                }
            }

            /*
                Date : Fri, 24 Jul 2015 09:15:01 +0200
                Dear Seller,
                In order to ensure optimum security, we ‘ll make changes concerning TLS/SSL cryptography for all of our services.
                We’ll disable SSL protocol (V2 and V3) and therefore, all negotiations must be done in TLS( preferably TLS 1.2).
             */


            if (CDiscountTools::isTlsAvailable('CURL_SSLVERSION_TLSv1_2')) {
                curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
                $ssl_protocol = 'CURL_SSLVERSION_TLSv1_2';
            } elseif (CDiscountTools::isTlsAvailable('CURL_SSLVERSION_TLSv1_1')) {
                curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_1);
                $ssl_protocol = 'CURL_SSLVERSION_TLSv1_1';
            } elseif (CDiscountTools::isTlsAvailable('CURL_SSLVERSION_TLSv1')) {
                curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
                $ssl_protocol = 'CURL_SSLVERSION_TLSv1';
            } else {
                curl_setopt($ch, CURLOPT_SSLVERSION, 1);
                $ssl_protocol = '1';
            }

            if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            }

            $response = curl_exec($ch);

            $curlError = curl_error($ch);

            if ($this->debug) {
                echo '<pre>'."\n";
                echo print_r($cert, true);  //
                echo print_r(curl_getinfo($ch, CURLINFO_SSL_VERIFYRESULT|CURLINFO_CERTINFO));
                echo 'Header:'.print_r($httpheader, true)."\n";
                echo 'URL:'.$this->url_sts."\n";
                echo 'SSL Protocol:'.$ssl_protocol."\n";
                echo 'cURL error number:'.curl_errno($ch)."\n"; // print error info
                echo 'cURL error:'.$curlError."\n";
                echo '</pre>'."\n";

                echo 'Response:'.$cr;
                echo '<pre>';
                echo htmlspecialchars(print_r(str_replace('>', ">\n", $response), true));
                echo '</pre><br />';
            }
            curl_close($ch);

            if (!file_exists($demo_file) && is_dir($demo_dir)) {
                file_put_contents($demo_file, $response);
            }
        }

        $xmlResponse = simplexml_load_string(trim($response));

        $result = array();
        $result['Token'] = (string)$xmlResponse;
        $result['Validity'] = time() + (23 * 60 * 60 * 2); // valid 47h instead 48

        if (isset($result['Error']) && $result['Error'] = $curlError || empty($result['Token'])) {
            $this->token = null;
        } else {
            $this->token = $result['Token'];
            if ($this->debug) {
                $validity = Tools::strlen($this->token) ? date('c', $result['Validity']) : 'FAILED';
                printf('%s/%s: Token: %s - Valid Until: %s'.$cr, basename(__FILE__), __LINE__, $this->token, $validity);
            }
        }

        return $result;
    }

    public function GetOrderList($args = array())
    {
        $cr = $this->_cr;

        if (!$args) {
            return false;
        }

        if ($this->debug) {
            printf('%s/%s: GetOrderList: %s'.$cr, basename(__FILE__), __LINE__, nl2br(print_r($args, true)));
        }

        $data = '<orderFilter xmlns:i="http://www.w3.org/2001/XMLSchema-instance">';
        $data .= '<BeginCreationDate>'.$args[0].'</BeginCreationDate>';
        if (Tools::strlen($args[1])) {
            $data .= '<BeginModificationDate>'.$args[1].'</BeginModificationDate>';
        }
        $data .= '<EndCreationDate>'.$args[2].'</EndCreationDate>';
        if (Tools::strlen($args[3])) {
            $data .= '<EndModificationDate>'.$args[3].'</EndModificationDate>';
        }
        $data .= '<FetchOrderLines>'.$args[4].'</FetchOrderLines>';
        $data .= '<States>';
        $data .= (int)$args[5] ? '<OrderStateEnum>CancelledByCustomer</OrderStateEnum>' : '';
        $data .= (int)$args[6] ? '<OrderStateEnum>WaitingForSellerAcceptation</OrderStateEnum>' : '';
        $data .= (int)$args[7] ? '<OrderStateEnum>AcceptedBySeller</OrderStateEnum>' : '';
        $data .= (int)$args[8] ? '<OrderStateEnum>PaymentInProgress</OrderStateEnum>' : '';
        $data .= (int)$args[9] ? '<OrderStateEnum>WaitingForShipmentAcceptation</OrderStateEnum>' : '';
        $data .= (int)$args[10] ? '<OrderStateEnum>Shipped</OrderStateEnum>' : '';
        $data .= (int)$args[11] ? '<OrderStateEnum>RefusedBySeller</OrderStateEnum>' : '';
        $data .= (int)$args[12] ? '<OrderStateEnum>AutomaticCancellation</OrderStateEnum>' : '';
        $data .= (int)$args[13] ? '<OrderStateEnum>PaymentRefused</OrderStateEnum>' : '';
        $data .= (int)$args[14] ? '<OrderStateEnum>ShipmentRefusedBySeller</OrderStateEnum>' : '';
        $data .= (int)$args[15] ? '<OrderStateEnum>None</OrderStateEnum>' : '';
        $data .= (int)$args[16] ? '<OrderStateEnum>ValidatedFianet</OrderStateEnum>' : '';
        $data .= (int)$args[17] ? '<OrderStateEnum>RefusedNoShipment</OrderStateEnum>' : '';
        $data .= (int)$args[18] ? '<OrderStateEnum>AvailableOnStore</OrderStateEnum>' : '';
        $data .= (int)$args[19] ? '<OrderStateEnum>NonPickedUpByCustomer</OrderStateEnum>' : '';
        $data .= (int)$args[20] ? '<OrderStateEnum>PickedUp</OrderStateEnum>' : '';
        $data .= '</States>';
        $data .= '</orderFilter>';

        if ($this->debug) {
            printf('%s/%s: _call()'.$cr, basename(__FILE__), __LINE__);
            echo '<pre>';
            echo htmlspecialchars(print_r($data, true));
            echo '</pre><br />'.$cr;
        }

        $response = $this->_call(__FUNCTION__, $data);

        if ($response) {
            //
            $result = $this->response(__FUNCTION__, $response);
        } else {
            if ($this->debug) {
                printf('%s/%s: _call() failed'.$cr, basename(__FILE__), __LINE__);
            }

            return (false);
        }


        $xmlObject = $result;

        if ($this->debug) {
            printf('%s/%s: _call() - response:'.$cr, basename(__FILE__), __LINE__);
            echo '<pre>';
            echo nl2br(htmlspecialchars(print_r($xmlObject, true)));
            echo '</pre><br />'.$cr;
        }

        return ($xmlObject);
    }

    protected function _callAfterSetToken($func, $params)
    {
        if (!$this->token) {
            $this->token = CDiscountTools::auth();
        }
        return $this->_call($func, $params);
    }

    protected function _call($func, $params = false, $largefile = false, $soap_action = null, $force_token = null)
    {
        $cr = $this->_cr;
        $demo_dir = dirname(dirname(__FILE__)).DS.Cdiscount::XML_DIRECTORY.DS.'demo/';
        $demo_file = $demo_dir.$func.'.out.gz';

        if (!is_dir(dirname($demo_file))) {
            $demo_file = null;
            $this->demo = false;
        }

        if ($this->demo && file_exists($demo_file)) {
            $response = CDiscountTools::file_get_contents('compress.zlib://'.$demo_file);
        } else {
            if (!$this->token) {
                $this->debugDetails->webservice('Authorization: Missing Token');
                return (false);
            }

            $data = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">'."\n";
            $data .= '<s:Body>'."\n";
            $data .= '<'.$func.' xmlns="http://www.cdiscount.com">'."\n";
            $data .= '<headerMessage xmlns:a="http://schemas.datacontract.org/2004/07/Cdiscount.Framework.Core.Communication.Messages" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">'."\n";
            $data .= '<a:Context>'."\n";
            $data .= '<a:CatalogID>'.self::$configuration[$this->platform]['CATALOG_ID'].'</a:CatalogID>'."\n";
            $data .= '<a:CustomerPoolID>'.self::$configuration[$this->platform]['CUSTOMER_POOL_ID'].'</a:CustomerPoolID>'."\n";
            $data .= '<a:SiteID>'.self::$configuration[$this->platform]['API_SITE_ID'].'</a:SiteID>'."\n";
            $data .= '</a:Context>'."\n";
            $data .= '<a:Localization>'."\n";
            $data .= '<a:Country>'.self::$configuration[$this->platform]['COUNTRY'].'</a:Country>'."\n";
            $data .= '<a:Currency>'.self::$configuration[$this->platform]['CURRENCY'].'</a:Currency>'."\n";
            $data .= '<a:DecimalPosition>2</a:DecimalPosition>'."\n";
            $data .= '<a:Language>'.self::$configuration[$this->platform]['LANGUAGE'].'</a:Language>'."\n";
            $data .= '</a:Localization>'."\n";
            $data .= '<a:Security>'."\n";
            $data .= '<a:DomainRightsList i:nil="true" />'."\n";
            $data .= '<a:IssuerID i:nil="true" />'."\n";
            $data .= '<a:SessionID i:nil="true" />'."\n";
            $data .= '<a:SubjectLocality i:nil="true" />'."\n";
            $data .= '<a:TokenId>'.($force_token ? $force_token : $this->token).'</a:TokenId>'."\n";
            $data .= '<a:UserName>Common-Services</a:UserName>'."\n";
            $data .= '</a:Security>'."\n";
            $data .= '<a:Version>1.0</a:Version>'."\n";
            $data .= '</headerMessage>'."\n";
            $data .= $params;
            $data .= '</'.$func.'>'."\n";
            $data .= '</s:Body>'."\n";
            $data .= '</s:Envelope>'."\n";

            if ($soap_action) {
                $callHeaderHttp = array(
                'Content-Type: text/xml;charset=UTF-8',
                'SOAPAction: '.'"http://www.cdiscount.com/IMarketplaceAPIService/'.$soap_action.'"'
                );
            } else {
                $callHeaderHttp = array(
                'Content-Type: text/xml;charset=UTF-8',
                'SOAPAction: '.'"http://www.cdiscount.com/IMarketplaceAPIService/'.$func.'"'
                );
            }

            $output_file = null;
            $fp = null;

            if ($largefile) {
                $output_file = dirname(dirname(__FILE__)).DS.Cdiscount::XML_DIRECTORY.DS.$func.'.out';

                $fp = fopen($output_file, 'w+');

                if (!$fp) {
                    $this->debugDetails->webservice(sprintf('Unable to open file ("%s") for writing', $output_file));
                    printf('%s/%s: _call() - Unable to open file ("%s") for writing'.$cr, basename(__FILE__), __LINE__, $output_file);

                    return (false);
                }
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_POST, true);

            if (defined('CURLOPT_ENCODING')) {
                $callHeaderHttp[] = 'Accept-Encoding: gzip,deflate';
                curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            }

            if (!self::$configuration[$this->platform]['COUNTRY'] == 'CO') {
                // SSL Issue with Colombia

                curl_setopt($ch, CURLOPT_SSLVERSION, 3);
            }

            /*
                Date : Fri, 24 Jul 2015 09:15:01 +0200
                Dear Seller,
                In order to ensure optimum security, we ‘ll make changes concerning TLS/SSL cryptography for all of our services.
                We’ll disable SSL protocol (V2 and V3) and therefore, all negotiations must be done in TLS( preferably TLS 1.2).
             */
            if (CDiscountTools::isTlsAvailable('CURL_SSLVERSION_TLSv1_2')) {
                curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
                $ssl_protocol = 'CURL_SSLVERSION_TLSv1_2';
            } elseif (CDiscountTools::isTlsAvailable('CURL_SSLVERSION_TLSv1_1')) {
                curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_1);
                $ssl_protocol = 'CURL_SSLVERSION_TLSv1_1';
            } elseif (CDiscountTools::isTlsAvailable('CURL_SSLVERSION_TLSv1')) {
                curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
                $ssl_protocol = 'CURL_SSLVERSION_TLSv1';
            } else {
                curl_setopt($ch, CURLOPT_SSLVERSION, 1);
                $ssl_protocol = '1';
            }

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CAINFO, $cert = CdiscountCertificates::getCertificate());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $callHeaderHttp);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT_REGULAR_POST);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT_CONNECT);

            if (!Configuration::get('CDISCOUNT_DISABLE_PROXY')) {
                curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
                curl_setopt($ch, CURLOPT_PROXY, self::$cdapi);
                curl_setopt($ch, CURLOPT_PROXYPORT, '8888');
                $this->debugDetails->webservice('Using proxy: ' . self::$cdapi);
            }

            if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            }

            if ($largefile) {
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT_HUGE_POST);
            }

            // Developpement ...
            $this->setProxySettings($ch);
            $this->debugDetails->webservice('Headers:', $callHeaderHttp, 'URL:', $this->url, 'Action:', $func, 'Data:', htmlspecialchars(print_r($data, true)));

            $response = curl_exec($ch);
            $this->debugDetails->webservice('Cert:'.$cert, curl_getinfo($ch), 'SSL Protocol:'.$ssl_protocol, 'cURL error number:'.curl_errno($ch), 'cURL error:'.curl_error($ch), 'Response:', htmlspecialchars(print_r(str_replace('>', ">\n", $response), true)));
            if (curl_errno($ch) == 35) {
                print('<span style="color:red;">SSL issue, please try to switch to SSLv2</span>');
            }

            curl_close($ch);
            if ($largefile) {
                rewind($fp);
                fclose($fp);
                usleep(300); //TODO: Please keep this sleep
                $response = CDiscountTools::file_get_contents($output_file);
                if (file_exists($output_file)) {
                    unlink($output_file);
                }
            } else {
                if (is_dir($demo_dir) && $demo_file && !file_exists($demo_file)) {
                    file_put_contents('compress.zlib://'.$demo_file, $response);
                }
            }
        }

        return ($response);
    }

    private function setProxySettings(&$ch)
    {
        /*
        if (!$this->production) {
            curl_setopt($ch, CURLOPT_PROXY, self::PROXY_IP);
            curl_setopt($ch, CURLOPT_PROXYPORT, self::PROXY_PORT);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, ($this->proxy_user.':'.$this->proxy_pswd));
        }
        */
    }

    public function response($class, $return = '')
    {
        $return = str_replace(array(
            '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><'.$class.'Response xmlns="http://www.cdiscount.com">',
            '</'.$class.'Response></s:Body></s:Envelope>'
        ), '', $return);
        $return = explode('<', $return, 2);
        $return = '<'.$return[1];
        if (!empty($return)) {
            $xmlObject = simplexml_load_string($return);
            $returnText = array();
            $domsToDelete = array();
            foreach ($xmlObject as $key => $value) {
                if (in_array($key, $this->genericReturn)) {
                    $returnText[] = $key.' : '.$value;
                    $domsToDelete[] = dom_import_simplexml($value);
                }
            }
            foreach ($domsToDelete as $dom) {
                $dom->parentNode->removeChild($dom);
            }
        }

        return ($xmlObject);
    }

    public function ValidateOrderList($args = array())
    {
        $cr = $this->_cr;

        if (!$args) {
            return false;
        }

        if ($this->debug) {
            printf('%s/%s: ValidateOrderList: %s'.$cr, basename(__FILE__), __LINE__, nl2br(print_r($args, true)));
        }

        $data = '<validateOrderListMessage xmlns:i="http://www.w3.org/2001/XMLSchema-instance">';
        $data .= '<OrderList>';
        foreach ($args as $oID => $order) {
            $data .= '<ValidateOrder>';

            // The tags must be sorted by alphabetical order
            if (isset($order['CarrierName'])) {
                $data .= '<CarrierName>'.$order['CarrierName'].'</CarrierName>';
            }

            $data .= '<OrderLineList>';
            foreach ($order['Items'] as $SellerProductId => $state) {
                $data .= '<ValidateOrderLine>';
                $data .= '<AcceptationState>'.$state.'</AcceptationState>';
                if (isset($order['ProductCondition'][$SellerProductId])) {
                    $data .= '<ProductCondition>'.$order['ProductCondition'][$SellerProductId].'</ProductCondition>';
                }
                $data .= '<SellerProductId>'.$SellerProductId.'</SellerProductId>';
                $data .= '</ValidateOrderLine>';
            }
            $data .= '</OrderLineList>';
            $data .= '<OrderNumber>'.$oID.'</OrderNumber>';
            $data .= '<OrderState>'.$order['OrderState'].'</OrderState>';

            if (isset($order['TrackingNumber'])) {
                $data .= '<TrackingNumber>'.$order['TrackingNumber'].'</TrackingNumber>';
            }

            if (isset($order['TrackingUrl'])) {
                $data .= '<TrackingUrl>'.$order['TrackingUrl'].'</TrackingUrl>';
            }

            $data .= '</ValidateOrder>';
        }
        $data .= '</OrderList>';
        $data .= '</validateOrderListMessage>';

        if ($this->debug) {
            printf('%s/%s: _call() - data:'.$cr, basename(__FILE__), __LINE__);
            echo '<pre>';
            echo nl2br(htmlspecialchars(print_r($data, true)));
            echo '</pre><br />'.$cr;
        }
        $response = $this->_call(__FUNCTION__, $data);

        if ($response) {
            $result = $this->response(__FUNCTION__, $response);

            $validateOrderResults = $result->ValidateOrderResults;
            $return = array();

            foreach ($validateOrderResults->children() as $validateOrderResult) {
                $return[(string)$validateOrderResult->OrderNumber] = (string)$validateOrderResult->Validated;
            }

            if ($this->debug) {
                printf('%s/%s: _call() - response: %s'.$cr, basename(__FILE__), __LINE__, print_r($return, true));
            }

            return ($return);
        } else {
            if ($this->debug) {
                printf('%s/%s: _call() failed'.$cr, basename(__FILE__), __LINE__);
            }

            return (false);
        }
    }

    public function SubmitOfferPackage($args = array())
    {
        $cr = $this->_cr;

        if (empty($args['FILE'])) {
            return false;
        }

        if ($this->debug) {
            printf('%s/%s: SubmitOfferPackage: %s'.$cr, basename(__FILE__), __LINE__, $args['FILE']);
        }

        $data = '<offerPackageRequest xmlns:i="http://www.w3.org/2001/XMLSchema-instance">'."\n";
        $data .= '<ZipFileFullPath>'.$args['FILE'].'</ZipFileFullPath>'."\n";
        $data .= '</offerPackageRequest>'."\n";

        if ($this->debug) {
            printf('%s/%s: _call() - data:'.$cr, basename(__FILE__), __LINE__);
            echo '<pre>';
            echo htmlspecialchars(print_r($data, true));
            echo '</pre><br />'.$cr;
        }

        $response = $this->_call(__FUNCTION__, $data);

        if ($response) {
            $result = $this->response(__FUNCTION__, $response);
        } else {
            if ($this->debug) {
                printf('%s/%s: _call() failed'.$cr, basename(__FILE__), __LINE__);
            }

            return (false);
        }

        $xmlObject = $result;

        if (isset($xmlObject->ErrorList) && isset($xmlObject->ErrorList->Error) && count($xmlObject->ErrorList->Error)) {
            echo '<pre style="color:red;">';
            echo htmlspecialchars(print_r(str_replace('>', ">\n", $xmlObject->ErrorList->asXML()), true));
            echo '</pre><br />'.$cr;
        }

        if (!isset($xmlObject->PackageId) || !(int)$xmlObject->PackageId) {
            if ($this->debug) {
                printf('%s/%s: SubmitOfferPackage() ERROR: PackageId missing - Response: %s'.$cr, basename(__FILE__), __LINE__, (string)$xmlObject->PackageId, nl2br(htmlspecialchars(print_r($xmlObject, true))));
            }
            $this->logContent(sprintf(
                '%s/%s: SubmitOfferPackage() ERROR: PackageId missing - Response: %s',
                basename(__FILE__),
                __LINE__,
                $xmlObject->saveXML()
            ));

            return (false);
        }
        $packageId = (string)$xmlObject->PackageId;

        if ($this->debug) {
            printf('%s/%s: _call() packageId: %s - response:'.$cr, basename(__FILE__), __LINE__, $packageId);
            echo '<pre>';
            echo htmlspecialchars(print_r($xmlObject, true));
            echo '</pre><br />'.$cr;
        }
        $this->logContent(sprintf(
            '%s/%s: _call() packageId: %s - response: %s',
            basename(__FILE__),
            __LINE__,
            $packageId,
            $xmlObject->saveXML()
        ));


        return ($packageId);
    }

    public function GetOfferPackageSubmissionResult($args = array())
    {
        $cr = $this->_cr;

        if (empty($args['PackageID'])) {
            return false;
        }

        if ($this->debug) {
            printf('%s/%s: GetOfferPackageSubmissionResult: PackageID [%s]'.$cr, basename(__FILE__), __LINE__, $args['PackageID']);
        }

        $data = '<offerPackageFilter xmlns:i="http://www.w3.org/2001/XMLSchema-instance">';
        $data .= '<PackageID>'.$args['PackageID'].'</PackageID>';
        $data .= '</offerPackageFilter>';

        if ($this->debug) {
            printf('%s/%s: _call() - data:'.$cr, basename(__FILE__), __LINE__);
            echo '<pre>';
            echo htmlspecialchars(print_r($data, true));
            echo '</pre><br />'.$cr;
        }

        $response = $this->_call(__FUNCTION__, $data);

        if ($response) {
            $result = $this->response(__FUNCTION__, $response);
        } else {
            if ($this->debug) {
                printf('%s/%s: _call() failed'.$cr, basename(__FILE__), __LINE__);
            }

            return (false);
        }

        $xmlObject = $result;

        if (!isset($xmlObject->PackageId) || !(int)$xmlObject->PackageId) {
            if ($this->debug) {
                printf('%s/%s: GetOfferPackageSubmissionResult() ERROR: PackageId missing - Response: %s'.$cr, basename(__FILE__), __LINE__, (string)$xmlObject->PackageId, nl2br(htmlspecialchars(print_r($xmlObject, true))));
            }

            return (null);
        }
        $packageId = (string)$xmlObject->PackageId;

        if ($this->debug) {
            printf('%s/%s: _call() packageId: %s - response:'.$cr, basename(__FILE__), __LINE__, $packageId);
            echo '<pre>';
            echo htmlspecialchars(print_r($xmlObject, true));
            echo '</pre><br />'.$cr;
        }

        $OfferLog = $xmlObject->xpath('//OfferReportLog');

        if (is_array($OfferLog) && reset($OfferLog) instanceof SimpleXMLElement) {
            return ($OfferLog);
        } else {
            return (null);
        }
    }

    public function GetProductList($args = array())
    {
        $cr = $this->_cr;

        if (empty($args['CategoryCode'])) {
            return false;
        }

        if ($this->debug) {
            printf('%s/%s: GetProductList - CategoryCode: %s'.$cr, basename(__FILE__), __LINE__, $args['CategoryCode']);
        }

        $data = '<productFilter xmlns:i="http://www.w3.org/2001/XMLSchema-instance">';
        $data .= '<CategoryCode>'.$args['CategoryCode'].'</CategoryCode>';
        $data .= '</productFilter>';

        if ($this->debug) {
            printf('%s/%s: _call() - data:'.$cr, basename(__FILE__), __LINE__);
            echo '<pre>';
            echo htmlspecialchars(print_r($data, true));
            echo '</pre><br />'.$cr;
        }

        $response = $this->_call(__FUNCTION__, $data);

        if ($response) {
            $result = $this->response(__FUNCTION__, $response);
        } else {
            if ($this->debug) {
                printf('%s/%s: _call() failed'.$cr, basename(__FILE__), __LINE__);
            }

            return (false);
        }

        $xmlObject = $result;

        if ($this->debug) {
            printf('%s/%s: _call() - response:'.$cr, basename(__FILE__), __LINE__);
            echo '<pre>';
            echo htmlspecialchars(print_r($xmlObject, true));
            echo '</pre><br />'.$cr;
        }

        return ($xmlObject);
    }

    /*
      <GetAllModelList xmlns="http://www.cdiscount.com">
      </GetAllModelList>
     */

    // 2020-07-13: GetAllModelList is removed.

    public function SubmitProductPackage($args = array())
    {
        if (empty($args['FILE'])) {
            return false;
        }

        $data = '<productPackageRequest xmlns:i="http://www.w3.org/2001/XMLSchema-instance">'."\n";
        $data .= '<ZipFileFullPath>'.$args['FILE'].'</ZipFileFullPath>'."\n";
        $data .= '</productPackageRequest>'."\n";
        $this->debugDetails->webservice('SubmitProductPackage: ' . $args['FILE'], htmlspecialchars(print_r($data, true)));

        $response = $this->_call(__FUNCTION__, $data);

        if ($response) {
            $result = $this->response(__FUNCTION__, $response);
        } else {
            $this->debugDetails->productExport('Failed to call webservice!');
            return (false);
        }

        $xmlObject = $result;

        if (isset($xmlObject->ErrorList) && isset($xmlObject->ErrorList->Error) && count($xmlObject->ErrorList->Error)) {
            $this->debugDetails->productExport(htmlspecialchars(print_r(str_replace('>', ">\n", $xmlObject->ErrorList->asXML()), true)));
        }

        $this->debugDetails->productExport('SubmitProductPackage() - Response:', htmlspecialchars(print_r($xmlObject, true)));
        if (!isset($xmlObject->PackageId) || !(int)$xmlObject->PackageId) {
            return (false);
        }

        return (string)$xmlObject->PackageId;
    }

    public function GetProductPackageSubmissionResult($args = array())
    {
        $cr = $this->_cr;

        if (empty($args['PackageID'])) {
            return false;
        }

        if ($this->debug) {
            printf('%s/%s: GetProductPackageSubmissionResult: PackageID [%s]'.$cr, basename(__FILE__), __LINE__, $args['PackageID']);
        }

        $data = '<productPackageFilter xmlns:i="http://www.w3.org/2001/XMLSchema-instance">';
        $data .= '<PackageID>'.$args['PackageID'].'</PackageID>';
        $data .= '</productPackageFilter>';

        if ($this->debug) {
            printf('%s/%s: _call() - data:'.$cr, basename(__FILE__), __LINE__);
            echo '<pre>';
            echo nl2br(htmlspecialchars(print_r($data, true)));
            echo '</pre><br />'.$cr;
        }

        $response = $this->_call(__FUNCTION__, $data);

        if ($response) {
            $result = $this->response(__FUNCTION__, $response);
        } else {
            if ($this->debug) {
                printf('%s/%s: _call() failed'.$cr, basename(__FILE__), __LINE__);
            }

            return (false);
        }


        $xmlObject = $result;

        if (!isset($xmlObject->PackageId) || !(int)$xmlObject->PackageId) {
            if ($this->debug) {
                printf('%s/%s: GetProductPackageSubmissionResult() ERROR: PackageId missing - Response: %s'.$cr, basename(__FILE__), __LINE__, (string)$xmlObject->PackageId, nl2br(htmlspecialchars(print_r($xmlObject, true))));
            }

            return (false);
        }
        $packageId = (string)$xmlObject->PackageId;

        if ($this->debug) {
            printf('%s/%s: _call() packageId: %s - response:'.$cr, basename(__FILE__), __LINE__, $packageId);
            echo '<pre>';
            echo nl2br(htmlspecialchars(print_r($xmlObject, true)));
            echo '</pre><br />'.$cr;
        }

        return ($packageId);
    }

    public function GenerateDiscussionMailGuid($args = array())
    {
        $cr = $this->_cr;

        if (empty($args['OrderId'])) {
            return false;
        }

        if ($this->debug) {
            printf('%s/%s: GenerateDiscussionMailGuid - OrderId: %s'.$cr, basename(__FILE__), __LINE__, $args['OrderId']);
        }

        $data = '<request xmlns:cdm="http://schemas.datacontract.org/2004/07/Cdiscount.Service.Marketplace.API.External.Contract.Data.Mail">'."\n";
        $data .= '<cdm:ScopusId>'.$args['OrderId'].'</cdm:ScopusId>'."\n";
        $data .= '</request>'."\n";

        if ($this->debug) {
            printf('%s/%s: _call() - data:'.$cr, basename(__FILE__), __LINE__);
            echo '<pre>';
            echo htmlspecialchars(print_r($data, true));
            echo '</pre><br />'.$cr;
        }

        $response = $this->_call(__FUNCTION__, $data);

        if ($response) {
            $result = $this->response(__FUNCTION__, $response);
        } else {
            if ($this->debug) {
                printf('%s/%s: _call() failed'.$cr, basename(__FILE__), __LINE__);
            }

            return (false);
        }

        if ($result instanceof SimpleXMLElement) {
            $xmlResult = $result->asXML();

            if ($this->debug) {
                printf('%s/%s: _call() - response:'.$cr, basename(__FILE__), __LINE__);
                echo '<pre>';
                echo nl2br(htmlspecialchars(print_r($xmlResult, true)));
                echo '</pre><br />'.$cr;
            }
            $email_array = $result->xpath('//a:MailGuid');

            if (isset($email_array[0])) {
                return ((string)$email_array[0]);
            } else {
                return (null);
            }
        } else {
            $xmlResult = null;

            if ($this->debug) {
                printf('%s/%s: _call() - response: %s'.$cr, basename(__FILE__), __LINE__, nl2br(htmlspecialchars(print_r($result, true))));
            }
        }

        return (null);
    }

    public function GetSellerInformation()
    {
        $response = $this->_call(__FUNCTION__);

        if ($response) {
            $xmlObject = $this->response(__FUNCTION__, $response);
        } else {
            $this->debugDetails->webservice("_call() failed");
            return (false);
        }

        $this->debugDetails->webservice('_call() - data:', htmlspecialchars(print_r($xmlObject, true)));
        if (!$xmlObject instanceof SimpleXMLElement) {
            $this->debugDetails->webservice("GetSellerInformation() Failed - Response");
            return (false);
        }

        if (!isset($xmlObject->OperationSuccess) || (string)$xmlObject->OperationSuccess != 'true') {
            return (false);
        }

        return ($xmlObject);
    }


    public function GetGlobalConfigurationCarriers()
    {
        $response = $this->_call('GetGlobalConfiguration', false, false, $soap_action = 'GetGlobalConfiguration');

        if ($response) {
            $xmlObject = $this->response('GetGlobalConfiguration', $response);
        } else {
            $this->debugDetails->webservice("_call() failed");
            return (false);
        }

        $this->debugDetails->webservice('_call() - data:', htmlspecialchars(print_r($xmlObject, true)));
        if (!$xmlObject instanceof SimpleXMLElement) {
            $this->debugDetails->webservice("GetGlobalConfiguration() Failed - Response");
            return (false);
        }

        if (!isset($xmlObject->OperationSuccess) || (string)$xmlObject->OperationSuccess != 'true') {
            return (false);
        }

        return ($xmlObject);
    }


    public function GetOfferList($args = array())
    {
        $cr = $this->_cr;

        if ($this->debug) {
            printf('%s/%s: GetOfferList - params: %s'.$cr, basename(__FILE__), __LINE__, print_r($args, true));
        }

        $data = '<offerFilter>'.Cdiscount::LF;
        $data .= '  <OfferPoolId>1</OfferPoolId>'.Cdiscount::LF;
        $data .= '</offerFilter>'.Cdiscount::LF;

        if ($this->debug) {
            printf('%s/%s: _call() - data:'.$cr, basename(__FILE__), __LINE__);
            echo '<pre>';
            echo htmlspecialchars(print_r($data, true));
            echo '</pre><br />'.$cr;
        }

        $response = $this->_call(__FUNCTION__, $data);
        if ($response) {
            $result = $this->response(__FUNCTION__, $response);
        } else {
            if ($this->debug) {
                printf('%s/%s: _call() failed'.$cr, basename(__FILE__), __LINE__);
            }

            return (false);
        }

        $xmlObject = $result;

        if ($this->debug) {
            printf('%s/%s: _call() - response:'.$cr, basename(__FILE__), __LINE__);
            echo '<pre>';
            echo htmlspecialchars(print_r($xmlObject, true));
            echo '</pre><br />'.$cr;
        }

        return ($xmlObject);
    }

    public function GetOfferListPaginated($args = array(), $page_number = 1)
    {
        $cr = $this->_cr;

        if ($this->debug) {
            printf('%s/%s: GetOfferListPaginated - params: %s'.$cr, basename(__FILE__), __LINE__, print_r($args, true));
        }

        $data = '<offerFilter>'.Cdiscount::LF;
        $data .= '  <OfferPoolId>1</OfferPoolId>'.Cdiscount::LF;
        $data .= '  <PageNumber>'.(int)$page_number.'</PageNumber>'.Cdiscount::LF;
        $data .= '</offerFilter>'.Cdiscount::LF;

        if ($this->debug) {
            printf('%s/%s: _call() - data:'.$cr, basename(__FILE__), __LINE__);
            echo '<pre>';
            echo htmlspecialchars(print_r($data, true));
            echo '</pre><br />'.$cr;
        }

        $response = $this->_call(__FUNCTION__, $data);
        if ($response) {
            $result = $this->response(__FUNCTION__, $response);
        } else {
            if ($this->debug) {
                printf('%s/%s: _call() failed'.$cr, basename(__FILE__), __LINE__);
            }

            return (false);
        }

        $xmlObject = $result;

        if ($this->debug) {
            printf('%s/%s: _call() - response:'.$cr, basename(__FILE__), __LINE__);
            echo '<pre>';
            echo htmlspecialchars(print_r($xmlObject, true));
            echo '</pre><br />'.$cr;
        }

        return ($xmlObject);
    }

    protected function pdd($message, $line, $debugModeOnly = false)
    {
        if (!$debugModeOnly || $this->debug) {
            CommonTools::p(sprintf("%s(#%d): $message", $this->debug_caller, $line));
        }
    }

    private function logContent($log)
    {
        if (!empty($log)) {
            $this->logContent .= $log . self::LF;
        }
    }

    public function getLogContent()
    {
        $logContent = $this->logContent;
        $this->logContent = '';
        return $logContent;
    }
}
