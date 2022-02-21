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

class CDiscountFulfillmentWebservice extends CDiscount
{
    public static $configuration
        = array(
            'fr' => array(
                'API_SITE_ID' => '100',
                'CATALOG_ID' => '1',
                'CUSTOMER_POOL_ID' => '1',
                'COUNTRY' => 'Fr',
                'CURRENCY' => 'Eur',
                'LANGUAGE' => 'Fr',
                'URL_STS_PREPROD' => 'https://cdiscount.com/users/httpIssue.svc/?realm=https://wsvc.preprod-cdiscount.com/FulfillmentAPIService.svc?channel=common-services',
                'URL_STS_PROD' => 'https://sts.cdiscount.com/users/httpIssue.svc/?realm=https://wsvc.cdiscount.com/FulfillmentAPIService.svc?channel=common-services',
                'URL_PREPROD' => 'https://wsvc.preprod-cdiscount.com/FulfillmentAPIService.svc?channel=common-services',
                'URL_PROD' => 'https://wsvc.cdiscount.com/FulfillmentAPIService.svc?channel=common-services'
            )
        );

    public $token;

    protected $url;
    protected $url_sts;
    protected $platform;
    protected $username;
    protected $password;
    protected $production;
    protected $demo;
    protected $genericReturn = array();

    protected $_cr = "<br />\n";

    protected $errorReturn
        = array(
            'ErrorMessage',
            'OperationSuccess',
            'ErrorList'
        );

    protected $specificReturn;

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

        if ($debug) {
            CommonTools::p(sprintf('%s/%s: Initializing Marketplace(%s) - login: %s - password length: %d - called by %s', basename(__FILE__), __LINE__, $prod ? 'prod' : 'preprod', $username, Tools::strlen($password), $this->_caller()));
        }
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


    public function auth($force_username = null, $force_password = null, $force_token = null)
    {
        $now = time();

        $token = Configuration::get(parent::KEY.'_TOKEN');
        $validity = Configuration::get(parent::KEY.'_TOKEN_VALIDITY');

        $debug = Configuration::get(parent::KEY.'_DEBUG') ? true : false;

        if ($force_token) {
            $token = null;
        }

        if ($now < $validity && $token) {
            if ($debug) {
                printf('%s/%s: getToken() - using valid token: %s', basename(__FILE__), __LINE__, $token);
            }

            return ($token);
        }
        require_once(dirname(__FILE__).'/../classes/'.parent::MODULE.'.webservice.class.php');

        if (!$force_username && !$force_password) {
            $username = Configuration::get(parent::KEY.'_USERNAME');
            $password = Configuration::get(parent::KEY.'_PASSWORD');
        } else {
            $username = $force_username;
            $password = $force_password;
        }

        $production = !(Configuration::get(parent::KEY.'_PREPRODUCTION') ? true : false);

        $ret = $this->getToken($debug);

        if (isset($ret['Error']) && $ret['Error']) {
            return (false);
        }

        if (!$force_token) {
            Configuration::updateValue(parent::KEY.'_TOKEN', $ret['Token']);
            Configuration::updateValue(parent::KEY.'_TOKEN_VALIDITY', $ret['Validity']);
        }

        return ($ret['Token']);
    }

    public function getToken($debug = false)
    {
        $cr = $this->_cr;

        $demo_file = dirname(dirname(__FILE__)).DS.Cdiscount::XML_DIRECTORY.DS.'demo/token_result.demo.out';

        if ($this->demo && file_exists($demo_file)) {
            sleep(2);
            $response = CDiscountTools::file_get_contents($demo_file);
        } else {
            $authentication = base64_encode($this->username.':'.$this->password);   // TODO Validation: Authentication by marketplace
            $httpheader = array('Authorization: Basic '.$authentication);

            if ($this->debug || $debug) {
                CommonTools::p(sprintf('%s/%s: getToken() - Authorization: login: %s password length(%d) - %s', basename(__FILE__), __LINE__, $this->username, Tools::strlen($this->password), $this->_caller()));
                CommonTools::p(sprintf('%s/%s: getToken() - HTTP Header: %s', basename(__FILE__), __LINE__, nl2br(print_r($httpheader, true))));
                //Commented out for security issue.
                //printf('%s/%s: getToken() - Password: %s' . $cr, basename(__FILE__), __LINE__, $this->password);
                //printf('%s/%s: getToken() - Data: %s' . $cr, basename(__FILE__), __LINE__, $authentication);
            }
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $this->url_sts);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CAINFO, sprintf('%s/%s', dirname(dirname(__FILE__)), 'cert/cacert.pem'));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);

            /*
                Date : Fri, 24 Jul 2015 09:15:01 +0200
                Dear Seller,
                In order to ensure optimum security, we ‘ll make changes concerning TLS/SSL cryptography for all of our services.
                We’ll disable SSL protocol (V2 and V3) and therefore, all negotiations must be done in TLS( preferably TLS 1.2).
             */
            if (defined('CURL_SSLVERSION_TLSv1_2')) {
                curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
            } elseif (defined('CURL_SSLVERSION_TLSv1')) {
                curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            } else {
                curl_setopt($ch, CURLOPT_SSLVERSION, 1);
            }

            // D�veloppement ...
            //curl_setopt($ch, CURLOPT_PROXY, '91.121.29.110:3129');

            $response = curl_exec($ch);

            $curlError = curl_error($ch);

            if ($this->debug || $debug) {
                CommonTools::p(curl_getinfo($ch));
                CommonTools::p('cURL error number:'.curl_errno($ch));
                CommonTools::p('cURL error:'.$curlError);

                CommonTools::p('Response: ' . htmlspecialchars(print_r($response, true)));
            }
            curl_close($ch);

            if (!file_exists($demo_file)) {
                file_put_contents('compress.zlib://'.$demo_file, $response);
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
            if ($this->debug || $debug) {
                $validity = Tools::strlen($this->token) ? date('c', $result['Validity']) : 'FAILED';
                printf('%s/%s: Token: %s - Valid Until: %s'.$cr, basename(__FILE__), __LINE__, $this->token, $validity);
            }
        }

        return $result;
    }

    protected function _call($func, $params = false, $largefile = false)
    {
        $cr = $this->_cr;
        $demo_file = dirname(dirname(__FILE__)).DS.Cdiscount::XML_DIRECTORY.DS.'demo/'.$func.'.out.gz';

        if ($this->demo && file_exists($demo_file)) {
            $response = CDiscountTools::file_get_contents('compress.zlib://'.$demo_file);
        } else {
            if (!$this->token) {
                CommonTools::p(sprintf('%s/%s: _call() - Authorization: Missing Token - %s', basename(__FILE__), __LINE__, $this->_caller()));
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
            $data .= '<a:TokenId>'.$this->token.'</a:TokenId>'."\n";
            $data .= '<a:UserName i:nil="true" />'."\n";
            $data .= '</a:Security>'."\n";
            $data .= '<a:Version>1.0</a:Version>'."\n";
            $data .= '</headerMessage>'."\n";
            $data .= $params;
            $data .= '</'.$func.'>'."\n";
            $data .= '</s:Body>'."\n";
            $data .= '</s:Envelope>'."\n";

            $callHeaderHttp = array(
                'Content-Type: text/xml;charset=UTF-8',
                'SOAPAction: '.'"http://www.cdiscount.com/IFulfillmentApiService/'.$func.'"'
            );

            $output_file = null;
            $fp = null;

            if ($largefile) {
                $output_file = dirname(dirname(__FILE__)).DS.Cdiscount::XML_DIRECTORY.DS.$func.'.out';

                $fp = fopen($output_file, 'w+');

                if (!$fp) {
                    printf('%s/%s: _call() - Unable to open file ("%s") for writing'.$cr, basename(__FILE__), __LINE__, $output_file);

                    return (false);
                }
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_POST, true);

            curl_setopt($ch, CURLOPT_CAINFO, sprintf('%s/%s', dirname(dirname(__FILE__)), 'cert/cacert.pem'));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $callHeaderHttp);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);

            /*
                Date : Fri, 24 Jul 2015 09:15:01 +0200
                Dear Seller,
                In order to ensure optimum security, we ‘ll make changes concerning TLS/SSL cryptography for all of our services.
                We’ll disable SSL protocol (V2 and V3) and therefore, all negotiations must be done in TLS( preferably TLS 1.2).
             */
            if (defined('CURL_SSLVERSION_TLSv1_2')) {
                curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
            } elseif (defined('CURL_SSLVERSION_TLSv1')) {
                curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            } else {
                curl_setopt($ch, CURLOPT_SSLVERSION, 1);
            }

            if ($largefile) {
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_TIMEOUT, 300);
            }

            // Developpement ...
            $this->setProxySettings($ch);

            if ($this->debug) {
                CommonTools::p(sprintf('%s/%s: _call() - URL: %s - %s', basename(__FILE__), __LINE__, $this->url, $this->_caller()));
                CommonTools::p(sprintf('%s/%s: _call() - Action: %s', basename(__FILE__), __LINE__, $func));
                CommonTools::p(sprintf('%s/%s: _call() - Data: ', basename(__FILE__), __LINE__));
                CommonTools::p(htmlspecialchars(print_r($data, true)));
            }

            if ($largefile) {
                curl_exec($ch);

                if ($this->debug) {
                    CommonTools::p(curl_getinfo($ch));
                    CommonTools::p('cURL error number:'.curl_errno($ch));
                    CommonTools::p('cURL error:'.curl_error($ch));
                    CommonTools::p('Response:');
                }

                if (curl_errno($ch) == 35) {
                    print('<span style="color:red;">SSL issue, please try to switch to SSLv2</span>');
                }

                curl_close($ch);
                rewind($fp);
                fclose($fp);

                usleep(300); //TODO: Please keep this sleep

                $response = CDiscountTools::file_get_contents($output_file);

                if (file_exists($output_file)) {
                    unlink($output_file);
                }
            } else {
                $response = curl_exec($ch);

                if ($this->debug) {
                    CommonTools::p(curl_getinfo($ch));
                    CommonTools::p('cURL error number:'.curl_errno($ch));
                    CommonTools::p('cURL error:'.curl_error($ch));
                    CommonTools::p('Response:');

                    if (!$largefile) {
                        CommonTools::p(htmlspecialchars(print_r($response, true)));
                    }
                }

                if (curl_errno($ch) == 35) {
                    print('<span style="color:red;">SSL issue, please try to switch to SSLv2</span>');
                }

                curl_close($ch);
            }

            if (!file_exists($demo_file)) {
                file_put_contents('compress.zlib://'.$demo_file, $response);
            }
        }

        return ($response);
    }

    private function setProxySettings(&$ch)
    {
        /*
        curl_setopt($ch, CURLOPT_PROXY, self::PROXY_IP);
        curl_setopt($ch, CURLOPT_PROXYPORT, self::PROXY_PORT);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, ($this->proxy_user.':'.$this->proxy_pswd));
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

    public function GetProductStockList($args = array())
    {
        $largefile = true;
        $cr = $this->_cr;

        if ($this->debug) {
            printf('%s/%s: GetProductStockList'.$cr, basename(__FILE__), __LINE__);
        }

        $data = null;

        /*
        $data = '<request xmlns:cdf="http://schemas.datacontract.org/2004/07/Cdiscount.Service.Fulfillment.API.External.Contract.Data.InputParameters" xmlns:arr="http://schemas.microsoft.com/2003/10/Serialization/Arrays">'."\n";
        $data .= '<cdf:BarCodeList><arr:string>123</arr:string></cdf:BarCodeList>'."\n";
        $data .= '<cdf:IsFulfillmentReferenced>1</cdf:IsFulfillmentReferenced>'."\n";
        $data .= '</Request>'."\n";
        */

        $data = '<request xmlns:cdf="http://schemas.datacontract.org/2004/07/Cdiscount.Service.Fulfillment.API.External.Contract.Data.InputParameters" xmlns:arr="http://schemas.microsoft.com/2003/10/Serialization/Arrays">'."\n";
        $data .= '<cdf:BarCodeList></cdf:BarCodeList>'."\n";
        $data .= '<cdf:IsFulfillmentReferenced></cdf:IsFulfillmentReferenced>'."\n";
        $data .= '</request>'."\n";

        if ($this->debug) {
            CommonTools::p(sprintf('%s/%s: _call() - data:', basename(__FILE__), __LINE__));
            CommonTools::p(htmlspecialchars(print_r($data, true)));
        }

        $response = $this->_call(__FUNCTION__, $data, true);

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
            CommonTools::p(htmlspecialchars($xmlObject->ErrorList->asXML()));
        }

        if ($this->debug) {
            CommonTools::p(sprintf('%s/%s: _call() packageId: %s - response:', basename(__FILE__), __LINE__, $xmlObject->asXML()));
            CommonTools::p(htmlspecialchars(print_r($xmlObject, true)));
        }


        return ($xmlObject);
    }
}
