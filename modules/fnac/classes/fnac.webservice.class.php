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

class FnacAPI extends Module
{
    const Unknown = 0;
    const Created = 1;
    const Accepted = 2;
    const Refused = 3;
    const ToShip = 4;
    const Shipped = 5;
    const NotReceived = 6;
    const Received = 7;
    const Refunded = 8;
    const Cancelled = 9;
    const Error = 10;
    const Update = 11;

    public $statuses = array();
    public $statusName = array();

    private $_header = '<?xml version="1.0" encoding="utf-8"?>';

    private $_xmlns = 'http://www.fnac.com/schemas/mp-dialog.xsd';
    private $response = null;

    // WebService -> Validation Files
    private $_validate = array(
        'auth' => 'AuthenticationService.xsd',
        'offers_query' => 'OffersQueryService.xsd',
        'offers_update' => 'OffersUpdateService.xsd',
        'orders_query' => 'OrdersQueryService.xsd',
        'orders_update' => 'OrdersUpdateService.xsd',
        'batch_query' => 'BatchQueryService.xsd',
        'batch_status' => 'BatchStatusService.xsd'
    );
    private $_service = null;
    private $module = 'fnac';

    public function __construct($partner_id, $shop_id, $key, $url, $debug = false)
    {
        $this->_auth = array(
            'partner_id' => $partner_id,
            'shop_id' => $shop_id,
            'key' => $key // change to 'token'  ?
        );
        $this->_url = $url;

        $this->statuses[self::Created] = 'Created';
        $this->statuses[self::Accepted] = 'Accepted';
        $this->statuses[self::Refused] = 'Refused';
        $this->statuses[self::ToShip] = 'ToShip';
        $this->statuses[self::Shipped] = 'Shipped';
        $this->statuses[self::NotReceived] = 'NotReceived';
        $this->statuses[self::Received] = 'Received';
        $this->statuses[self::Refunded] = 'Refunded';
        $this->statuses[self::Cancelled] = 'Cancelled';
        $this->statuses[self::Error] = 'Error';
        $this->statuses[self::Update] = 'Updated';

        $this->debug = $debug;

        $this->_xsdpath = dirname(__FILE__).'/../xsd/';
    }

    public function getStatusesNames()
    {
        $statusName = array();
        $statusName[FnacAPI::Created] = $this->l('Created - Waiting for Approval');
        $statusName[FnacAPI::Accepted] = $this->l('Accepted - Waiting for Confirmation');
        $statusName[FnacAPI::Refused] = $this->l('Refused - Refused by us');
        $statusName[FnacAPI::Update] = $this->l('Update - Update the Order');
        $statusName[FnacAPI::ToShip] = $this->l('ToShip - Approved, Waiting for Shipping');
        $statusName[FnacAPI::Shipped] = $this->l('Shipped');
        $statusName[FnacAPI::NotReceived] = $this->l('NotReceived - Customer didn\'t receive all the items');
        $statusName[FnacAPI::Received] = $this->l('Received - This order was successfuly delivered');
        $statusName[FnacAPI::Refunded] = $this->l('Partially Refunded - Some products could not be received');
        $statusName[FnacAPI::Cancelled] = $this->l('Cancelled - The order has been canceled');
        $statusName[FnacAPI::Error] = $this->l('Error - Status is in an unauthorized or inconsistent state');

        return ($statusName);
    }

    /* Return an XML Date */
    private function _iso8601($time = false)
    {
        if ($time === false) {
            $time = time();
        }
        $date = date('Y-m-d\TH:i:sO', $time);

        return (Tools::substr($date, 0, Tools::strlen($date) - 2).':'.Tools::substr($date, -2));
    }

    /* Calling Web Service */
    private function _call($content, $service = null)
    {
        $data = $this->_header;
        $data .= "\n";
        // $data .= $content->asXml(); // Added asXML() 2018-12-14
        $data .= $content; // Added asXML() 2018-12-14

//        var_dump($data);die;

        if ($this->debug) {
            echo '<pre>';
            var_dump($data);
            echo '</pre>';
        }

        try {
            $ch = curl_init();

            if ($service == 'auth' && !strstr($this->_url, '?')) {
                $service = '?'.$service;
            } elseif (!preg_match('#/$#', $this->_url)) {
                $this->_url .= '/';
            }

            if ($this->debug) {
                echo '<pre>';
                var_dump(
                    str_replace('/ /', '/', trim($this->_url).trim($service))
                );
                echo '</pre>';
            }

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_URL, str_replace('/ /', '/', trim($this->_url).trim($service)));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/xml'
            ));

            $response = curl_exec($ch);

            if ($this->debug) {
                if ($response == false) {
                    echo nl2br(print_r(curl_getinfo($ch), true));  // get error info
                    echo 'cURL error number:'.curl_errno($ch)."\n<br/>"; // print error info
                    echo 'cURL error message:'.curl_error($ch)."\n<br/>";
                }
            }
            curl_close($ch);
        } catch (Exception $e) {
            echo 'Exception caught : ', $e->getMessage(), "\n";

            return (false);
        }

        return $response;
    }

    /* Format XML */
    private function _format($xml)
    {
        $dom = new DomDocument;

        try {
            $dom->loadXML($xml);
        } catch (Exception $e) {
            echo 'Exception caught : ', $e->getMessage(), "\n";

            return (false);
        }
        $dom->formatOutput = true;
        $str = $dom->saveXML();
        unset($dom);

        return ($str);
    }

    /* XSD Validation */
    private function _validate($xml, $service)
    {
//        return $xml->asXML();
        // Validate
        $dom = new DOMDocument;
        $dom->loadXML($xml->asXML());

//        libxml_use_internal_errors(true);
        try {
            $dom->schemaValidate($this->_xsdpath.$this->_validate[$service]);
        } catch (Exception $e) {
            echo 'Exception caught : ', $e->getMessage(), "\n";

//            libxml_use_internal_errors(false);
            return (false);
        }

//        libxml_use_internal_errors(false);

        return ($xml->asXML());
    }

    /* Set security parameters to the request */
    private function _security(&$item)
    {
        foreach ($this->_auth as $attr => $val) {
            $item->addAttribute($attr, $val);
        }
    }

    /* Create a new service (new ws/query) */
    private function _service($service, $security = false)
    {
        // SimpleXMLElement can't treat a main node item, only children
        // we create a fake main and remove it after
        $xml = '<root></root>';

        $xml = new SimpleXMLElement($xml);

        // Create Service
        $xml->addChild($service);

        // Item need namespace
        $xml->$service->addAttribute('xmlns', $this->_xmlns);

        if ($security) {
            // Set Secure Header
            $this->_security($xml->$service);
        }

        return ($xml);
    }

    /* Return the query string */
    private function _query($xml, $service)
    {
        // Matching instance
        $realItem = $xml->xpath($service);
        $xml = $realItem[0];

        return ($xml);
    }

    private function _submit($xml, $service)
    {
        if ($this->debug) {
            echo nl2br(htmlentities($this->_format($xml->asXML())));
        }

        // Get query string
        $xml = $this->_query($xml, $service);

        // For debug
        if ($this->debug) {
            echo nl2br(htmlentities($this->_format($xml->asXML())));
        }

        // Validation
        if (!$validated = $this->_validate($xml, $service)) {
            if ($this->debug) {
                echo 'validate: return false';
            }

            return (false);
        }
//        $validated = $xml;

        // if no XML version, add it
//        if (Tools::substr($validated, 0, 19) != '<?xml version="1.0"') {
/*            $validated = '<?xml version="1.0"?>'.$validated;*/
//        }

        if ($this->debug) {
            echo 'call:'.nl2br(print_r($service, true));
            var_dump($validated);
        }

        //  Calling Web Service
        if (!$result = $this->_call($validated, $service)) {
            if ($this->debug) {
                echo '_call: return false';
            }

            return (false);
        }

        if ($this->debug) {
            echo $this->_format($result);
        }
        // UTF8 Cleanup
        #$result = @iconv("UTF-8","UTF-8//IGNORE", utf8_decode($result));

        // Get Result
        if ($this->debug) {
            var_dump($result);
        }
        $xml = new SimpleXMLElement($result);

        $status = $xml['status'];

        if ($status == 'OK') {
            if ($this->debug) {
                echo "Status : OK\n";
            }

            // Important, resultat de la requete !
            $this->response = $result;

            return (true);
        } else {
            if ($this->debug) {
                echo "Status : $status";
            }

            return (false);
        }
    }

    private function doPostRequest($url, $data)
    {
        $ch = curl_init();

        // Depending on your system, you may add other options or modify the following ones.
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /*
     <orders_update xmlns="http://www.fnac.com/schemas/mp-dialog.xsd" shop_id="BBBFA40E-3A94-2EE1-762A-2858EDE4F9BB" partner_id="C906104B-9B13-611D-6104-261780F88E38" token="AE2F633D-48E4-CEB8-39C7-8713AAE28DE3">
     <order order_id="1P3HCS98E4QYE" action="accept_all_orders">
       <order_detail>
         <action><![CDATA[Accepted]]></action>
       </order_detail>
     </order>
     </orders_update>
    */
    public function OrderAccept($order_id = null, $platform = 'fr')
    {
        // Does not seems to work.
        // Updated it
        $flag = $platform == 'fr' ? '' : (Tools::strtoupper($platform).'_');

        $partner_id = Configuration::get('FNAC_'.$flag.'PARTNER_ID');
        $shop_id = Configuration::get('FNAC_'.$flag.'SHOP_ID');
        $api_key = Configuration::get('FNAC_'.$flag.'API_KEY');
        $api_url = rtrim(Configuration::get('FNAC_'.$flag.'API_URL'), '/').'/';

        // Get Token
        $login_request_xml =
'<?xml version="1.0" encoding="utf-8"?>
<auth xmlns="http://www.fnac.com/schemas/mp-dialog.xsd">
    <partner_id>'.$partner_id.'</partner_id>
    <shop_id>'.$shop_id.'</shop_id>
    <key>'.$api_key.'</key>
</auth>';

        $ret = $this->doPostRequest($api_url.'auth', $login_request_xml);
        $ret_origin = $ret;

        $ret = @simplexml_load_string($ret);
        $ret = Tools::jsonDecode(Tools::jsonEncode($ret), 1);

        if (!is_array($ret) || !array_key_exists('token', $ret) || !$ret['token']) {
            printf('Error, unable to get the API token to accept this order : '.$order_id);
            var_dump($platform, $flag);
            var_dump($login_request_xml);
            var_dump($api_url.'auth');
            var_dump($ret_origin);

            return false;
        }

        $api_key = $ret['token'];

        // Pass order as accepted in case it is not done yet
        $offers_update_request_xml =
            '<?xml version="1.0" encoding="utf-8"?>
            <orders_update xmlns="http://www.fnac.com/schemas/mp-dialog.xsd" shop_id="'.$shop_id.'" partner_id="'.$partner_id.'" token="'.$api_key.'">
                <order order_id="'.$order_id.'" action="accept_all_orders">
                    <order_detail>
                        <action><![CDATA[Accepted]]></action>
                    </order_detail>
                </order>
            </orders_update>';

        $this->doPostRequest($api_url.'orders_update', $offers_update_request_xml);

        return true;
    }

    /*
    <orders_update partner_id="17B13D72-9938-9549-39B7-BC3FF73CDA84"
    shop_id="859A6B21-6916-58FB-8B71-88EAD9A6A1F0" token="890BB66D-EB43-0FD8-AB19-
    8330B054F1DD" xmlns="http://www.fnac.com/schemas/mp-dialog.xsd">
    <order order_id="0FTAGAE32QICK" action="update_all">
      <order_detail>
        <action>Updated
        </action>
        <tracking_number>007
        </tracking_number>
        <tracking_company>UPS
        </tracking_company>
      </order_detail>
    </order>
    </orders_update>
    */
    public function OrderSend($order_id = null, $tracking = array(), $method = 'confirm_all_to_send', $action = 'Shipped')
    {
        $service = 'orders_update';

        // Create Instance
        $xml = $this->_service($service, true);


        // Acceptation ou Refus
        $xml->$service->addChild('order');
        $xml->$service->order->addAttribute('order_id', $order_id);
        $xml->$service->order->addAttribute('action', $method);

        $xml->$service->order->addChild('order_detail');
        $xml->$service->order->order_detail->addChild('action', $action);

        // Tracking
        $xml->$service->order->order_detail->addChild('tracking_number', $tracking['number']);
        $xml->$service->order->order_detail->addChild('tracking_company', $tracking['company']);

        if ($this->_submit($xml, $service)) {
            if ($this->debug) {
                echo $this->_format($this->response);
            }

            return (true);
        }

        return (false);
    }

    public function OrderQueryByID($id)
    {
        $service = 'orders_query';

        // Create Instance
        $xml = $this->_service($service, true);

        $orderParam = (object)array(
            'results_count' => '1'
        );

        if ($orderParam->results_count) {
            $xml->$service->addAttribute('results_count', $orderParam->results_count);
        }

        // ID
        $xml->$service->addChild('order_fnac_id', $id);

        if ($this->_submit($xml, $service)) {
            if ($this->debug) {
                echo $this->_format($this->response);
            }

            // UTF8 Cleanup
            #$result = @iconv("UTF-8","UTF-8//IGNORE", utf8_decode($this->response));
            $result = $this->response;

            // Get Result
            $xml = new SimpleXMLElement($result);


            $status = $xml['status'];

            if ($status == 'OK') {
                if ($this->debug) {
                    echo "Status : OK<br>\n";
                }
            } else {
                return (false);
            }

            return ($xml);
        }

        return (false);
    }


    public function OrderQueryByDate($date1, $date2, $state = null)
    {
        $service = 'orders_query';

        // Create Instance
        $xml = $this->_service($service, true);


        $orderParam = (object)array(
            'results_count' => '50'
        );

        if ($orderParam->results_count) {
            $xml->$service->addAttribute('results_count', $orderParam->results_count);
        }

        // Query Parameters, Period
        $xml->$service->addChild('date');
        $xml->$service->date->addAttribute('type', 'CreatedAt');

        if ($state) {
            $xml->$service->addChild('state', $this->statuses[constant("self::$state")]);
        }

        $time1 = strtotime($date1);
        $time2 = strtotime($date2);

        $min = $this->_iso8601($time1);
        $max = $this->_iso8601($time2);

        $xml->$service->date->addChild('min', $min);
        $xml->$service->date->addChild('max', $max);

        if ($this->_submit($xml, $service)) {
            if ($this->debug) {
                echo $this->_format($this->response);
            }

            // UTF8 Cleanup
            #$result = @iconv("UTF-8","UTF-8//IGNORE", utf8_decode($this->response));
            $result = $this->response;
            // Get Result
            $xml = new SimpleXMLElement($result);

            $status = $xml['status'];

            if ($status == 'OK') {
                if ($this->debug) {
                    echo "Status : OK<br>\n";
                }
            } else {
                return (false);
            }

            return ($xml);
        }

        return (false);
    }

    /*
  Offer Query Sample : 
--- CALL ---
  <?xml version="1.0" encoding="utf-8"?>
  <offers_query results_count="10" partner_id="87B13D72-9938-9549-39B7- BC3FF73CDA84" shop_id="859A6B21-6916-58FB-8B71-88EAD9A6A1F0" token="890BB66DEB43- 0FD8-AB19-8330B054F1DD" xmlns="http://www.fnac.com/schemas/mpdialog. xsd">
    <paging>1
    </paging>
    <date type="Created">
      <min>2009-01-28T11:53:24+02:00
      </min>
      <max>2009-09-28T11:53:24+02:00
        </max >
    </date>
    <product_fnac_id>4BB8F29D-2F7A-57A0-630E-47403CDE1109
    </product_fnac_id>
    <offer_fnac_id>78978978
    </offer_fnac_id>
    <offer_seller_id>78978789
    </offer_seller_id>
  </offers_query>
--- ANSWER ---
<offers_query_response xmlns="http://www.fnac.com/schemas/mp-dialog.xsd" status='OK'>
  <offer>
    <product_name><![CDATA[Toshiba RDH 100DT ( Lecteur - enregistreur DVD ) - Combin?? lecteur-enregistreur DVD / disque dur]]></product_name>
    <product_fnac_id>2626007</product_fnac_id>
    <offer_fnac_id>5755F0F2-1C2B-2CEE-ED3E-9665D2352F50</offer_fnac_id>
    <offer_seller_id><![CDATA[Toshiba-RDH-100DT]]></offer_seller_id>
    <product_state>11</product_state>
    <price>50</price>
    <quantity>99</quantity>
    <description><![CDATA[test]]></description>
    <internal_comment><![CDATA[]]></internal_comment>
    <product_url><![CDATA[http://www4.rec3.fnac.dev/marketplace/articleoffers.aspx?prid=2659493&ref=Fnac.com]]></product_url>
    <image><![CDATA[http://multimedia.fnac.com/multimedia/images_produits/Grandes150/0/9/0/5017151623090.gif]]></image>
    <nb_messages>0</nb_messages>
    <showcase/>
  </offer>

  </offers_query_response>
*/

    public function OfferQuery()
    {
        $service = 'offers_query';

        // Create Instance
        $xml = $this->_service($service, true);


        $offerParam = (object)array(
            'results_count' => '5'
        );

        if ($offerParam->results_count) {
            $xml->$service->addAttribute('results_count', $offerParam->results_count);
        }

        // Query Parameters
        $xml->$service->addChild('paging', 1);

        // Quantity
        /*
        $xml->$service->addChild('quantity') ;
          $xml->$service->quantity->addAttribute('mode', 'Equals');
          $xml->$service->quantity->addAttribute('value', '1');
        */
        // Query Parameters, Period
        $xml->$service->addChild('date');
        $xml->$service->date->addAttribute('type', 'Created');


        $time1 = time() - (86400 * 15);
        $time2 = time();

        $min = $this->_iso8601($time1);
        $max = $this->_iso8601($time2);

        $xml->$service->date->addChild('min', $min);
        $xml->$service->date->addChild('max', $max);


        #$xml->$service->addChild('offer_seller_id', '78978789') ;

        if ($this->_submit($xml, $service)) {
            if ($this->debug) {
                echo $this->_format($this->response);
            }
        }

        return (false);
    }


    public function OfferUpdate($ref, $qty, $price, $delete = false)
    {
        $service = 'offers_update';

        // Create Instance
        $xml = $this->_service($service, true);

        $xml->$service->addChild('offer');

        $xml->$service->offer->addChild('offer_reference', $ref);
        $xml->$service->offer->offer_reference->addAttribute('type', 'SellerSku');

        if ($delete) {
            // Quantity
            $xml->$service->addChild('quantity', $qty);

            // Price
            $xml->$service->addChild('price', $price);
        } else {
            $xml->$service->addChild('treatment', 'delete');
        }


        if ($this->_submit($xml, $service)) {
            if ($this->debug) {
                echo $this->_format($this->response);
            }
        }

        return (false);
    }

    public function BatchOfferUpdate($products)
    {
        $service = 'offers_update';

        // Create Instance
        $xml = $this->_service($service, true);

        $i = 0;
        foreach ($products as $key => $product) {
            if ((float)$product['price'] < 0.89) {
                if ($this->debug) {
                    echo 'Product #'.$product['id'].' : The price is lower than 0.90EUR, product skipped.<br>';
                }
                continue;
            }
            $xml->$service->addChild('offer');

            @$xml->$service->offer[$i]->addChild('offer_reference', $product['id']);
            @$xml->$service->offer[$i]->offer_reference->addAttribute('type', 'SellerSku');

            if (!$product['delete']) {
                if (isset($product['ean'])) {
                    $xml->$service->offer[$i]->addChild('product_reference', $product['ean']);
                    $xml->$service->offer[$i]->product_reference->addAttribute('type', 'Ean');
                }

                if ($product['qty'] > 999) {
                    $product['qty'] = 999;
                }

                $xml->$service->offer[$i]->addChild('quantity', $product['qty']);

                $xml->$service->offer[$i]->addChild('price', $product['price']);

                $xml->$service->offer[$i]->addChild('internal_comment', $product['comment']);

                if (isset($product['logistic_type_id']) && $product['logistic_type_id']) {
                    $xml->$service->offer[$i]->addChild('logistic_type_id', $product['logistic_type_id']);
                }

                if (isset($product['description'])) {
                    $xml->$service->offer[$i]->addChild('description', $product['description']);
                }

                if (isset($product['condition'])) {
                    $xml->$service->offer[$i]->addChild('product_state', $product['condition']);
                }

                if (isset($product['promotion']) && is_array($product['promotion'])) {
                    $promotion = $xml->$service->offer[$i]->addChild('promotion');
                    $promotion->addAttribute('type', $product['promotion']['type']);

                    if (Tools::strlen($product['promotion']['sales_period_reference'])) {
                        $promotion->addChild('sales_period_reference', $product['promotion']['sales_period_reference']);
                    }

                    $promotion->addChild('promotion_uid', $product['promotion']['promotion_uid']);
                    $promotion->addChild('starts_at', $product['promotion']['starts_at']);
                    $promotion->addChild('ends_at', $product['promotion']['ends_at']);
                    $promotion->addChild('discount_type', $product['promotion']['discount_type']);
                    $promotion->addChild('discount_value', $product['promotion']['discount_value']);
                }

                if (isset($product['time_to_ship']) && $product['time_to_ship']) {
                    $xml->$service->offer[$i]->addChild('time_to_ship', (int)$product['time_to_ship']);
                }
            } elseif ($product['delete']) {
                $xml->$service->offer[$i]->addChild('treatment', 'delete');
            }
            $i++;
        }

        if ($this->_submit($xml, $service)) {
            if ($this->debug) {
                echo '========== RESPONSE ==========<br>';
                echo $this->_format($this->response);
                echo '============= END ============<br><br>';
            }

            return (true);
        }

        return (false);
    }


    /* Login Sample
    <auth xmlns="http://www.fnac.com/schemas/mp-dialog.xsd">
      <partner_id>DBC3EFEA-1779-F84E-2617-A1D480D6F811</partner_id>
      <shop_id>81078A6E-D809-234F-F9C5-E1E192FAD87D</shop_id>
      <key>F0C748FD-13CA-412E-CE90-7B89D490D372</key>
    </auth>
    */
    public function Login()
    {
        $service = 'auth';

        // Create Instance
        $xml = $this->_service($service);

        // Auth Parameters
        foreach ($this->_auth as $key => $val) {
            $xml->$service->addChild($key, $val);
        }

        if ($this->_submit($xml, $service)) {
            if ($this->debug) {
                echo $this->_format($this->response);
            }

            // UTF8 Cleanup
            #$result = @iconv("UTF-8","UTF-8//IGNORE", utf8_decode($this->response));
            $result = $this->response;
            // Get Result
            $xml = new SimpleXMLElement($result);

            $token = (string)$xml->token;

            // Replace FNAC API by the given Token
            unset($this->_auth['key']);
            $this->_auth['token'] = (string)$token;

            return (true);
        }

        return (false);
    }
}
