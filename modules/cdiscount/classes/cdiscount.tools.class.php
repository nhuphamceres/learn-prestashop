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

require_once(_PS_MODULE_DIR_.'cdiscount/common/certificates.class.php');

class CDiscountTools extends CDiscount
{
    public static $_order_states
        = array(
            1 => 'CancelledByCustomer',
            2 => 'WaitingForSellerAcceptation',
            3 => 'AcceptedBySeller',
            4 => 'PaymentInProgress',
            5 => 'WaitingForShipmentAcceptation',
            6 => 'Shipped',
            7 => 'RefusedBySeller',
            8 => 'AutomaticCancellation',
            9 => 'PaymentRefused',
            10 => 'ShipmentRefusedBySeller',
            11 => 'None',
            12 => 'RefusedNoShipment',
            13 => 'AvailableOnStore',
            14 => 'NonPickedUpByCustomer',
            15 => 'PickedUp'
        );

    public static function toKey($str)
    {
        $str = str_replace(array('-', ',', '.', '/', '+', '.', ':', ';', '>', '<', '?', '(', ')', '!'), array('_', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm'), $str);
        $str = Tools::strtolower(preg_replace('/[^A-Za-z0-9_]/', '', $str));

        return $str;
    }

    public static function priceRule($price, $rule)
    {
        // Integrity check
        if (!isset($rule['rule']) || !isset($rule['rule']['from']) || !isset($rule['rule']['to'])) {
            return ((float)$price);
        }

        if (!is_array($rule['rule']) || !is_array($rule['rule']['from']) || !is_array($rule['rule']['to'])) {
            return ((float)$price);
        }

        if ($rule['type'] == 'percent' && !(isset($rule['rule']['percent']) || !is_array($rule['rule']['percent']) || !max($rule['rule']['percent']))) {
            return ((float)$price);
        }
        if ($rule['type'] == 'value' && !(isset($rule['rule']['value']) || !is_array($rule['rule']['value']) || !max($rule['rule']['value']))) {
            return ((float)$price);
        }

        $index = null;

        if (is_array($rule['rule']['to']) && is_array($rule['rule']['from']) && max($rule['rule']['to']) >= $price) {
            foreach ($rule['rule']['from'] as $key => $val1) {
                if ((int)$price >= (int)$val1 && (int)$price <= (int)$rule['rule']['to'][$key]) {
                    $index = $key;
                }
            }
        }

        if ($index === null) {
            return ((float)$price);
        }

        if ($rule['type'] == 'value') {
            $price += (float)$rule['rule']['value'][$index];
        } elseif ($rule['type'] == 'percent') {
            $price += $price * ((float)$rule['rule']['percent'][$index] / 100);
        }

        return ((float)$price);
    }

    public static function currentToken()
    {
        static $token = null;
        static $validity = null;

        $now = time();

        if ($token == null) {
            $token = Configuration::get(parent::KEY.'_TOKEN');
            $validity = Configuration::get(parent::KEY.'_TOKEN_VALIDITY');
        }

        if (!preg_match('/[0-9a-f]{32}/i', $token)) {
            return (false);
        }

        if ($now < $validity && $token) {
            return ($token);
        }

        return (false);
    }

    public static function auth($force_username = null, $force_password = null, $force_token = null)
    {
        $now = time();

        $token = Configuration::get(parent::KEY.'_TOKEN');
        $validity = Configuration::get(parent::KEY.'_TOKEN_VALIDITY');

        /** @var Cdiscount $module */
        $module = Module::getInstanceByName('cdiscount');
        $module->debugDetails->webservice(sprintf('getToken() - current token: %s, validity: %s', $token, $validity));
        if ($force_token) {
            $token = null;
        }

        if ($now < $validity && Tools::strlen($token)) {
            $module->debugDetails->webservice(sprintf('getToken() - using valid token: %s', $token));
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

        $marketplace = new CDiscountWebservice($username, $password, $production, Cdiscount::$debug_mode);

        $ret = $marketplace->getToken(Cdiscount::$debug_mode);

        $module->debugDetails->webservice(sprintf('getToken() - returned: %s', print_r($ret, true)));

        if (isset($ret['Error']) && $ret['Error']) {
            return (false);
        }

        if (!$force_token && Tools::strlen($ret['Token'])) {
            Configuration::updateValue(parent::KEY.'_TOKEN', $ret['Token']);
            Configuration::updateValue(parent::KEY.'_TOKEN_VALIDITY', $ret['Validity']);
        }

        return ($ret['Token']);
    }

    public static function orderStateToId($orderState)
    {
        $states = array_flip(self::$_order_states);

        return ($states[$orderState]);
    }


    public static function orderIdToState($orderStateId)
    {
        return (isset(self::$_order_states[$orderStateId]) ? self::$_order_states[$orderStateId] : 'Unknown');
    }


    public static function oldest()
    {
        $sql
            = '
            SELECT MIN(`date_add`) as date_min FROM `'._DB_PREFIX_.'product`;';
        if (($rq = Db::getInstance()->ExecuteS($sql)) && is_array($rq)) {
            $result = array_shift($rq);

            return ($result['date_min']);
        } else {
            return (false);
        }
    }


    public static function getFriendlyUrl($text)
    {
        $text = htmlentities($text);
        $text = preg_replace(array('/&szlig;/', '/&(..)lig;/', '/&([aouAOU])uml;/', '/&(.)[^;]*;/'), array(
            'ss',
            '$1',
            '$1'.'e',
            '$1'
        ), $text);
        $text = preg_replace('/[\x00-\x1F\x21-\x2B\x3A-\x3F\x5B-\x60\x7B-\x7F]/', '', $text); // remove non printable
        $text = preg_replace('/[ \t]+/', '-', $text);
        $text = str_replace(array('_', ',', '.', '/', '+', '?', '&', '='), '-', $text);

        return Tools::strtolower(trim($text));
    }

    public static function getProductImages($id_product, $id_product_attribute, $id_lang, $context = null)
    {
        $product = new Product($id_product, false, $id_lang, isset($context->shop->id) && $context->shop->id ? $context->shop->id : null, $context);

        if (($cover = Product::getCover($id_product, $context))) {
            $id_image_cover = (int)$cover['id_image'];
        } else {
            $id_image_cover = null;
        }

        $images = $product->getImages($id_lang, $context);

        if (is_array($images) && count($images)) {
            $image_set = array();
            foreach ($images as $image) {
                $image_set[] = $image['id_image'];
            }
        } else {
            $image_set = array();
        }

        if ((int)$id_product_attribute) {
            $images = $product->getCombinationImages($id_lang);
            $id_images = array();

            if (is_array($images) && count($images)) {
                if (isset($images[$id_product_attribute])) {
                    foreach ($images[$id_product_attribute] as $image) {
                        if ($id_image_cover && $image['id_image'] == $id_image_cover) {
                            array_splice($id_images, 0, 0, array($image['id_image']));
                        } else {
                            $id_images[] = $image['id_image'];
                        }
                    }
                } else {
                    $id_images = false;
                }
            } else {
                $images = $product->getImages($id_lang, $context);
                if (is_array($images) && count($images)) {
                    foreach ($images as $image) {
                        if ($id_image_cover && $image['id_image'] == $id_image_cover) {
                            array_splice($id_images, 0, 0, array($image['id_image']));
                        } else {
                            $id_images[] = $image['id_image'];
                        }
                    }
                } else {
                    $id_images = false;
                }
            }
        } else {
            $id_images = array();
            $images = $product->getImages($id_lang, $context);
            if (is_array($images) && count($images)) {
                foreach ($images as $image) {
                    if ($id_image_cover && $image['id_image'] == $id_image_cover) {
                        array_splice($id_images, 0, 0, array($image['id_image']));
                    } else {
                        $id_images[] = $image['id_image'];
                    }
                }
            } else {
                $id_images = false;
            }
        }
        $images = array();

        if ($id_images) {
            foreach ($id_images as $id_image) {
                if (is_array($image_set) && in_array($id_image, $image_set)) { // multistore workarround: getCombinationImages returns images from other shops
                    $images[] = self::getImageUrl($id_image, $id_product);
                }
            }
        }

        return ($images);
    }

    /**
     * @param $id_image
     * @param $productid
     *
     * @return bool|string
     */
    public static function getImageUrl($id_image, $productid)
    {
        $image_type = null;
        $ext = 'jpg';

        #image url
        if (version_compare(_PS_VERSION_, '1.4', '>=')) {
            $image_obj = new Image($id_image);

            // PS > 1.4.3
            if (method_exists($image_obj, 'getExistingImgPath')) {
                $img_path = $image_obj->getExistingImgPath();
                $imageurl = $img_path;
            } else {
                $imageurl = $productid.'-'.$id_image;
            }
        } else {
            $imageurl = $productid.'-'.$id_image;
        }

        if (method_exists('ImageType', 'getFormatedName')) {
            $image_type = Configuration::get(parent::KEY.'_IMAGE_TYPE');
        }

        if (Tools::strlen($image_type)) {
            $imageurl = sprintf('%s-%s.%s', $imageurl, $image_type, $ext);
        } else {
            $imageurl = sprintf('%s.%s', $imageurl, $ext);
        }


        return $imageurl;
    }

    /*  XML 2 ARRAY
      Event handler called by the expat library when an element's end tag is encountered.
     */

    public static function xml2array($contents, $get_attributes = 1, $priority = 'tag')
    {
        $attributes = $value = null;
        if (!$contents) {
            return array();
        }

        if (!function_exists('xml_parser_create')) {
            //print "'xml_parser_create()' function not found!";
            return array();
        }

        //Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8'); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);

        if (!$xml_values) {
            return;
        } //Hmm...

        $xml_array = array();

        $current = &$xml_array; //Refference
        //Go through the tags.
        $repeated_tag_index = array(); //Multiple tags with same name will be turned into an array
        foreach ($xml_values as $data) {
            unset($attributes, $value); //Remove existing values, or there will be trouble
            //This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data); //We could use the array by itself, but this cooler.

            $result = array();
            $attributes_data = array();

            if (isset($value)) {
                if ($priority == 'tag') {
                    $result = $value;
                } else {
                    $result['value'] = $value;
                } //Put the value in a assoc array if we are in the 'Attribute' mode
            }

            //Set the attributes too.
            if (isset($attributes) && $get_attributes) {
                foreach ($attributes as $attr => $val) {
                    if ($priority == 'tag') {
                        $attributes_data[$attr] = $val;
                    } else {
                        $result['attr'][$attr] = $val;
                    } //Set all the attributes in a array called 'attr'
                }
            }

            //See tag status and do the needed.
            if ($type == 'open') {
                //The starting of the tag '<tag>'
                $parent[$level - 1] = &$current;
                if (!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    if ($attributes_data) {
                        $current[$tag.'_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag.'_'.$level] = 1;

                    $current = &$current[$tag];
                } else { //There was another element with the same tag name
                    if (isset($current[$tag][0])) {
                        //If there is a 0th element it is already an array
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                        $repeated_tag_index[$tag.'_'.$level]++;
                    } else {
                        //This section will make the value an array if multiple tags with the same name appear together
                        $current[$tag] = array(
                            $current[$tag],
                            $result
                        ); //This will combine the existing item and the new item together to make an array
                        $repeated_tag_index[$tag.'_'.$level] = 2;

                        if (isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                            unset($current[$tag.'_attr']);
                        }
                    }
                    $last_item_index = $repeated_tag_index[$tag.'_'.$level] - 1;
                    $current = &$current[$tag][$last_item_index];
                }
            } elseif ($type == 'complete') { //Tags that ends in 1 line '<tag />'
                //See if the key is already taken.
                if (!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    if ($priority == 'tag' && $attributes_data) {
                        $current[$tag.'_attr'] = $attributes_data;
                    }
                } else { //If taken, put all things inside a list(array)
                    if (isset($current[$tag][0]) && is_array($current[$tag])) {
                        //If it is already an array...
                        // ...push the new element into that array.
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;

                        if ($priority == 'tag' && $get_attributes && $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level].'_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag.'_'.$level]++;
                    } else { //If it is not an array...
                        $current[$tag] = array(
                            $current[$tag],
                            $result
                        ); //...Make it an array using using the existing value and the new value
                        $repeated_tag_index[$tag.'_'.$level] = 1;
                        if ($priority == 'tag' && $get_attributes) {
                            if (isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                                $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                                unset($current[$tag.'_attr']);
                            }

                            if ($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag.'_'.$level].'_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
                    }
                }
            } elseif ($type == 'close') {
                //End of tag '</tag>'
                $current = &$parent[$level - 1];
            }
        }

        return ($xml_array);
    }

    public static function cPath($id_category, $id_lang)
    {
        $c = new Category($id_category);

        if (!isset($category)) {
            $category = '';
        }

        if ($c->id_parent && $c->id_parent != 1) {
            $category .= self::cPath($c->id_parent, $id_lang).' > ';
        }

        if (is_array($c->name)) {
            if (isset($c->name[$id_lang])) {
                $category .= $c->name[$id_lang];
            } else {
                $category .= $c->name[0];
            }
        } else {
            $category .= $c->name;
        }

        return (rtrim($category, ' > '));
    }

    public static function trimArray($Input)
    {
        if (!is_array($Input)) {
            return trim($Input);
        }

        return array_map(array(__CLASS__, 'trimArray'), $Input);
    }

    public static function isDirWriteable($path)
    {
        $path = rtrim($path, '/\\');

        $testfile = sprintf('%s%stestfile_%s.chk', $path, DIRECTORY_SEPARATOR, uniqid());
        $timestamp = time();

        if (@file_put_contents($testfile, $timestamp)) {
            $result = trim(CDiscountTools::file_get_contents($testfile));
            @unlink($testfile);

            if ((int)$result == (int)$timestamp) {
                return (true);
            }
        }

        return (false);
    }

    public static function moduleIsInstalled($moduleName)
    {
        if (method_exists('Module', 'isInstalled')) {
            return (Module::isInstalled($moduleName));
        } else {
            Db::getInstance()->ExecuteS('SELECT `id_module` FROM `'._DB_PREFIX_.'module` WHERE `name` = \''.pSQL($moduleName).'\'');

            return (bool)Db::getInstance()->NumRows();
        }
    }

    public static function arrayFilterRecursive($input)
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = self::arrayFilterRecursive($value);
            }
        }

        return array_filter($input);
    }

    public static function validateSKU($SKU)
    {
        return ($SKU != null && Tools::strlen($SKU) && preg_match('/[0-9A-Za-z\/_ @\?\>=\<;:\.\-,\+\*\)\(\'\&%\$#" !\^\~\}\{\[\]]{1,64}/', $SKU) && preg_match('/[^ ]$/', $SKU) && preg_match('/^[^ ]/', $SKU));
    }

    public static function copy($source, $destination, $stream_context = null)
    {
        if (method_exists('Tools', 'copy')) {
            if (is_null($stream_context) && !preg_match('/^https?:\/\//', $source)) {
                return @copy($source, $destination);
            } //TODO: Validation - PS1.4 compat
            return @file_put_contents($destination, CDiscountTools::file_get_contents($source, false, $stream_context));//TODO: Validation - PS1.4 compat
        } else {
            return @copy($source, $destination);
        }
    }

    public static function smartRounding($price)
    {
        // Smart Price
        $plain = floor($price);
        $decimals = $price - $plain;
        $decimal_part = (int)((string)$decimals * 100); // https://www.google.fr/search?hl=fr&output=search&sclient=psy-ab&q=php+floor+bug&btnG=&gws_rd=ssl

        if (!$decimals || ($decimal_part % 10) == 0) {
            $rounded = $decimal_part;
        } else {
            $rounded = sprintf('%02d', ((number_format(round($decimals, 1) - 0.1, 2, '.', '') * 100) - 1) + 10);
        }

        $smart_price = sprintf('%d.%02d', $plain, max(0, $rounded));

        return ($smart_price);
    }


    //http://php.net/manual/fr/function.glob.php#106595
    /**
     * @param $pattern
     * @param int $flags
     *
     * @return array
     */
    public static function globRecursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        if (is_array($files) && count($files)) {
            $dirs = glob(dirname($pattern).'/*', GLOB_ONLYDIR | GLOB_NOSORT);

            if (is_array($dirs) && count($dirs)) {
                foreach ($dirs as $dir) {
                    $other_files = self::globRecursive($dir.'/'.basename($pattern), $flags);

                    if (is_array($other_files) && count($other_files)) {
                        $files = array_merge($files, $other_files);
                    }
                }
            }
        }

        return $files;
    }

    /**
     * @param $url
     * @param bool $use_include_path
     * @param null $stream_context
     * @param int $curl_timeout
     *
     * @return bool|string
     */
    public static function file_get_contents($url, $use_include_path = false, $stream_context = null, $curl_timeout = 30, $certificate = null)
    {
        if (function_exists('curl_init') && preg_match('/^https?:\/\//', $url)) {
            $curl = curl_init();
            $cert = Tools::strlen($certificate) ? $certificate : CdiscountCertificates::getCertificate();   // TODO Validation: Yes, it exists

            if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
                curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($curl, CURLOPT_TIMEOUT, $curl_timeout);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($curl, CURLOPT_CAINFO, $cert);
            
            if ($stream_context != null) {
                $opts = stream_context_get_options($stream_context);
                if (isset($opts['http']['method']) && Tools::strtolower($opts['http']['method']) == 'post') {
                    curl_setopt($curl, CURLOPT_POST, true);
                    if (isset($opts['http']['content'])) {
                        parse_str($opts['http']['content'], $post_data);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
                    }
                }
            }
            $content = curl_exec($curl);

            if (Cdiscount::$debug_mode) {
                CommonTools::p(sprintf('%s - %s::%s()/#%d', basename(__FILE__), __CLASS__, __FUNCTION__, __LINE__));
                CommonTools::p(sprintf('timeout: %s', print_r($curl_timeout, true)));
                CommonTools::p(sprintf('cert file: %s', print_r($cert, true)));
                CommonTools::p(sprintf('curl error: %s (%d)', curl_error($curl), curl_errno($curl)));
                CommonTools::p(sprintf('curl info: %s', print_r(curl_getinfo($curl), true)));
            }
            curl_close($curl);


            return $content;
        } elseif (in_array(ini_get('allow_url_fopen'), array('On', 'on', '1')) || !preg_match('/^https?:\/\//', $url)) {
            if ($stream_context == null && preg_match('/^https?:\/\//', $url)) {
                if (preg_match('/^https:\/\//', $url)) {
                    $contextOptions = array(
                        'ssl' => array(
                        'verify_peer'   => true,
                        'cafile'        => Tools::strlen($certificate) ? $certificate : CdiscountCertificates::getCertificate() // TODO Validation: Yes, it exists
                        )
                    );
                    $stream_context = @stream_context_create(array('http' => array('timeout' => $curl_timeout)), $contextOptions);
                } else {
                    $contextOptions = array();
                    $stream_context = null;
                }
            }

            if (CDiscount::$debug_mode) {
                return file_get_contents($url, $use_include_path, is_resource($stream_context) ? $stream_context : null);//TODO Validation: http://forge.prestashop.com/browse/PSCSX-7758
            } else {
                return @file_get_contents($url, $use_include_path, is_resource($stream_context) ? $stream_context : null);//TODO Validation: http://forge.prestashop.com/browse/PSCSX-7758
            }
        } else {
            return false;
        }
    }

    public static function isTlsAvailable($curl_tls_constant)
    {
        return(false); //TODO: waiting Cdiscount is fixing TLS1.x bug
        $php_version = phpversion();
        //$php_version_check = version_compare($php_version, '7', '>=');//too many problem with TLSv_x with PHP <7
        $php_version_check = true;
        return(defined($curl_tls_constant) && $php_version_check);
    }

    /**
     * @param Context $context
     * @return array
     */
    public static function reInitContextControllerIfNeed($context)
    {
        if (version_compare(_PS_VERSION_, '1.7.6', '>=')) {
            if (!isset($context->controller)
                || !$context->controller instanceof Controller
                || !$context->controller->getContainer()
            ) {
                $originCurrency = $context->currency;
                // For any reason which causes the container not present,
                // We create the controller and init again, PS does all the jobs behind
                $context->controller = new FrontController();
                $context->controller->init();
                // Restore origin currency, FrontController::init() overrides our currency. Do the same for other contexts if needed
                if ($originCurrency instanceof Currency) {
                    $context->currency = $originCurrency;
                }

                return array('ps1.7.6' => true, 'reinit' => true, 'context' => $context);
            }
        }

        return array('ps1.7.6' => false, 'reinit' => false, 'context' => $context);
    }
}
