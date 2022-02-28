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

require_once(dirname(__FILE__).'/../common/tools.class.php');

if (!class_exists('MiraklTools')) {
    class MiraklTools extends CommonTools
    {

        public static function displayPrice($price, $currency = null, $no_utf8 = false, Context $context = null)
        {
            if (version_compare(_PS_VERSION_, '1.7.6', '>=')) {
                if ($currency === null) {
                    $currency = $context->currency;
                } elseif (is_int($currency)) {
                    $currency = Currency::getCurrencyInstance((int)$currency);
                }

                return static::getContextLocale(Context::getContext())->formatPrice($price, $currency->iso_code);
            }

            return parent::displayPrice($price, $currency, $no_utf8, $context);
        }

        /**
         * @param string $iso_code
         * @return array
         */
        public static function language($iso_code)
        {
            static $display_inactive = true;
            static $languages = null;
            $available_language = array();

            if (!$languages) {
                $languages = Language::getLanguages(false);
            }

            foreach ($languages as $language) {
                // For active languages
                if (!$display_inactive && $language['active'] == false) {
                    continue;
                }

                if (Tools::strtolower($language['iso_code']) != Tools::strtolower($iso_code)) {
                    continue;
                }
                return($available_language = $language);
            }

            return ($available_language);
        }


        public static function displayDate($date, $id_lang = null, $full = false, $separator = '-')
        {
            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                $id_lang = null;

                return Tools::displayDate($date, $id_lang, $full);
            } else {
                return Tools::displayDate($date, $id_lang, $full, $separator);
            }
        }

        public static function oldest()
        {
            $sql = 'SELECT MIN(date_add) as date_min FROM `'._DB_PREFIX_.'product` WHERE date_add > 0;';

            if (($rq = Db::getInstance()->ExecuteS($sql)) && is_array($rq)) {
                $result = array_shift($rq);

                return str_replace('-', '/', $result['date_min']);
            } else {
                return false;
            }
        }

        public static function getFriendlyUrl($text)
        {
            $text = htmlentities($text);
            $text = preg_replace(
                array('/&szlig;/', '/&(..)lig;/', '/&([aouAOU])uml;/', '/&(.)[^;]*;/'),
                array(
                    'ss',
                    '$1',
                    '$1e',
                    '$1'
                ),
                $text
            );
            $text = preg_replace('/[\x00-\x1F\x21-\x2B\x3A-\x3F\x5B-\x60\x7B-\x7F]/', '', $text); // remove non printable
            $text = preg_replace('/[ \t]+/', '-', $text);
            $text = str_replace(array('_', ',', '.', '/', '+', '?', '&', '='), '-', $text);

            return Tools::strtolower(trim($text));
        }

        public static function getProductImages($id_product, $id_product_attribute, $id_lang)
        {
            $product = new Product($id_product);

            if ((int)$id_product_attribute) {
                $images = $product->getCombinationImages($id_lang);
                $id_images = array();

                if (is_array($images) && count($images)) {
                    if (isset($images[$id_product_attribute])) {
                        foreach ($images[$id_product_attribute] as $image) {
                            $id_images[] = $image['id_image'];
                        }
                    } else {
                        $id_images = false;
                    }
                } else {
                    $images = $product->getImages($id_lang);

                    if (is_array($images) && count($images)) {
                        foreach ($images as $image) {
                            $id_images[] = $image['id_image'];
                        }
                    } else {
                        $id_images = false;
                    }
                }
            } else {
                $images = $product->getImages($id_lang);
                if (is_array($images) && count($images)) {
                    foreach ($images as $image) {
                        $id_images[] = $image['id_image'];
                    }
                } else {
                    $id_images = false;
                }
            }
            $images = array();

            if ($id_images) {
                foreach ($id_images as $id_image) {
                    $images[] = self::getImageUrl($id_image, $id_product);
                }
            }

            return $images;
        }

        public static function getImageUrl($id_image, $productid)
        {
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

            if (version_compare(_PS_VERSION_, '1.5', '>=')) {
                if (!Mirakl::$marketplace_key) {
                    Mirakl::$marketplace_key = 'MIRAKL';
                }
                // TODO pour chaque produit le image_type est rechargé => static ?
                $image_type = Mirakl::getConfig(Mirakl::CONFIG_IMAGE_TYPE);

                if (empty($image_type)) {
                    $image_type = ImageType::getFormatedName('thickbox');
                }

                $imageurl = sprintf('%s-%s.%s', $imageurl, $image_type, $ext);
            } else {
                $imageurl = sprintf('%s.%s', $imageurl, $ext);
            }

            return $imageurl;
        }

        public static function eanupcCheck($code)
        {
            if (!is_numeric($code) || Tools::strlen($code) < 12) {
                return (false);
            }
            //first change digits to a string so that we can access individual numbers
            $digits = sprintf('%012s', Tools::substr(sprintf('%013s', $code), 0, 12));
            // 1. Add the values of the digits in the even-numbered positions: 2, 4, 6, etc.
            $even_sum = $digits{1}
            + $digits{3}
            + $digits{5}
            + $digits{7}
            + $digits{9}
            + $digits{11};
            // 2. Multiply this result by 3.
            $even_sum_three = $even_sum * 3;
            // 3. Add the values of the digits in the odd-numbered positions: 1, 3, 5, etc.
            $odd_sum = $digits{0}
            + $digits{2}
            + $digits{4}
            + $digits{6}
            + $digits{8}
            + $digits{10};
            // 4. Sum the results of steps 2 and 3.
            $total_sum = $even_sum_three + $odd_sum;
            // 5. The check character is the smallest number which, when added to the result in step 4,  produces a multiple of 10.
            $next_ten = (ceil($total_sum / 10)) * 10;
            $check_digit = (int)$next_ten - $total_sum;
            $last_digit = (int)Tools::substr($code, Tools::strlen($code) - 1, 1);

            return ((int)$last_digit == (int)$check_digit);
        }

        public static function eanupcIsPrivate($code)
        {
            return in_array(Tools::substr(sprintf('%013s', $code), 0, 1), array('2'));
        }

        public static function moduleIsInstalled($module_name)
        {
            // Prestashop 1.2 / 1.3 compat
            if (method_exists('Module', 'isInstalled')) {
                return Module::isInstalled(Tools::strtolower($module_name));
            } else {
                Db::getInstance()->ExecuteS('SELECT `id_module` FROM `'._DB_PREFIX_.'module` WHERE `name` = \''.pSQL(Tools::strtolower($module_name)).'\'');

                return (bool)Db::getInstance()->NumRows();
            }
        }

        /*
        * For PS 1.2 compatibility
        */
        public static function getHttpHost($http = false, $entities = false, $ignore_port = false)
        {
            if (method_exists('Tools', 'getHttpHost')) {
                return Tools::getHttpHost($http, $entities, $ignore_port);
            } else {
                $host = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST']);
                if ($entities) {
                    $host = htmlspecialchars($host, ENT_COMPAT, 'UTF-8');
                }
                if ($http) {
                    $host = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').$host;
                }

                return $host;
            }
        }

        public static function stripInvalidXml($value)
        {
            // http://stackoverflow.com/questions/3466035/how-to-skip-invalid-characters-in-xml-file-using-php
            $ret = '';
            $current = null;
            if (empty($value)) {
                return $ret;
            }

            $length = strlen($value);//TODO: Validation - multibyte dance, do not use Prestashop function Tools::strlen
            for ($i = 0; $i < $length; $i++) {
                $current = ord($value{$i});
                if (($current == 0x9) || ($current == 0xA) || ($current == 0xD) || (($current >= 0x20) && ($current <= 0xD7FF)) || (($current >= 0xE000) && ($current <= 0xFFFD)) || (($current >= 0x10000) && ($current <= 0x10FFFF))) {
                    $ret .= chr($current);
                } else {
                    $ret .= ' ';
                }
            }

            return $ret;
        }

        public static function formatDescription($html, $decription_html = false)
        {
            $text = self::stripInvalidXml($html);

            if ($decription_html) {
                $text = iconv('UTF-8', 'UTF-8//TRANSLIT', $text);
                $text = str_replace('&#39;', "'", $text);
                $text = str_replace('&', '&amp;', $text);
            } else {
                $text = str_replace(html_entity_decode('&lt;/p&gt;'), "\n".html_entity_decode('&lt;/p&gt;'), $html);
                $text = str_replace(html_entity_decode('&lt;/li&gt;'), "\n".html_entity_decode('&lt;/li&gt;'), $text);
                $text = str_replace(html_entity_decode('&lt;br'), "\n".html_entity_decode('&lt;br'), $text);
                $text = strip_tags($text);

                $text = iconv('UTF-8', 'UTF-8//TRANSLIT', $text);
                $text = str_replace('&#39;', "'", $text);

                $text = mb_convert_encoding($text, 'HTML-ENTITIES');
                $text = str_replace('&nbsp;', ' ', $text);
                $text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
                $text = str_replace('&', '&amp;', $text);

                $text = preg_replace('#\s+[\n|\r]+$#i', '', $text); // empty
                $text = preg_replace('#[\n|\r]+#i', "\n", $text); // multiple-return
                $text = preg_replace('#\n+#i', "\n", $text); // multiple-return
                $text = preg_replace('#^[\n\r\s]#i', '', $text);
            }

            return trim($text);
        }

        /**
         * Check if the current page use SSL connection on not
         *
         * @return bool uses SSL
         */
        public static function usingSecureMode()
        {
            if (method_exists('Tools', 'usingSecureMode')) {
                return Tools::usingSecureMode();
            }

            if (isset($_SERVER['HTTPS'])) {
                return in_array(Tools::strtolower($_SERVER['HTTPS']), array(1, 'on'));
            }
            // $_SERVER['SSL'] exists only in some specific configuration
            if (isset($_SERVER['SSL'])) {
                return in_array(Tools::strtolower($_SERVER['SSL']), array(1, 'on'));
            }
            // $_SERVER['REDIRECT_HTTPS'] exists only in some specific configuration
            if (isset($_SERVER['REDIRECT_HTTPS'])) {
                return in_array(Tools::strtolower($_SERVER['REDIRECT_HTTPS']), array(1, 'on'));
            }
            if (isset($_SERVER['HTTP_SSL'])) {
                return in_array(Tools::strtolower($_SERVER['HTTP_SSL']), array(1, 'on'));
            }

            return false;
        }

        public static function getAttachmentsList($id_product, $id_lang, $id_shop = null)
        {
            $link = new Link();
            $p = new Product($id_product, true);
            $attachments = $p->getAttachments($id_lang);

            $ssl = (self::usingSecureMode()) ? true : false;

            //PS 1.3
            $ps_13 = version_compare(_PS_VERSION_, '1.4', '<');

            //PS 1.4
            $ps_14 = version_compare(_PS_VERSION_, '1.5', '<');

            $attachments_tbl = array();
            $i = 0;

            foreach ($attachments as $a) {
                if ($ps_13) {
                    $alink = _PS_BASE_URL_.__PS_BASE_URI__.'attachment.php?id_attachment='.$a['id_attachment'];
                    $name = $a['name'];
                } elseif ($ps_14) {
                    $alink = $link->getPageLink('attachment.php', $ssl, $id_lang).'?id_attachment='.$a['id_attachment'];
                    $name = $a['name'];
                } else {
                    $alink = $link->getPageLink('attachment', $ssl, $id_lang, 'id_attachment='.$a['id_attachment'], false, $id_shop);
                    $name = $a['name'];
                }
                $attachments_tbl[$i] = array();
                $attachments_tbl[$i]['name'] = $name;
                $attachments_tbl[$i]['link'] = $alink;
                $i++;
            }

            return $attachments_tbl;
        }

        public static function encode($data = '')
        {
            return base64_encode($data);//TODO: Validation - We need base64_encode to encode somes configurations values
        }

        public static function decode($data = '')
        {
            return base64_decode($data);//TODO: Validation - We need base64_encode to decode some configurations values
        }

        /**
         * @param $str
         * @return string
         */
        public static function tryBase64Decode($str)
        {
            $decodeMaybe = base64_decode($str, true);

            if ($str === base64_encode($decodeMaybe)) {
                return $decodeMaybe;
            } else {
                return $str;
            }
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

        public static function toKey($str)
        {
            $str = str_replace(
                array('-', ',', '.', '/', '+', '.', ':', ';', '>', '<', '?', '(', ')', '!'),
                array(
                    '_',
                    'a',
                    'b',
                    'c',
                    'd',
                    'e',
                    'f',
                    'g',
                    'h',
                    'i',
                    'j',
                    'k',
                    'l',
                    'm'
                ),
                $str
            );
            $str = Tools::strtolower(preg_replace('/[^A-Za-z0-9_]/', '', $str));

            return $str;
        }

        public static function ucfirst($str)
        {
            if (method_exists('Tools', 'ucfirst')) {
                return Tools::ucfirst($str);
            }

            return Tools::strtoupper(Tools::substr($str, 0, 1)).Tools::substr($str, 1);
        }

        public static function ucwords($str)
        {
            if (method_exists('Tools', 'ucwords')) {
                return Tools::ucwords($str);
            }

            return ucwords(Tools::strtolower($str));
        }

        public static function copy($source, $destination, $stream_context = null)
        {
            if (method_exists('Tools', 'copy')) {
                if (is_null($stream_context) && !preg_match('/^https?:\/\//', $source)) {
                    return @copy($source, $destination);
                } //TODO: Validation - PS1.4 compat
                return @file_put_contents($destination, Tools::file_get_contents($source, false, $stream_context));//TODO: Validation - PS1.4 compat
            } else {
                return @copy($source, $destination);
            }
        }

        // todo: Upgrade mismatch, remove in future
        protected static $dbFieldExist = array();
        public static function fieldExistsR($table, $field, $cache = true)
        {
            if (!$cache || !isset(self::$dbFieldExist[$table])) {
                self::$dbFieldExist[$table] = array();
                $query = Db::getInstance()->ExecuteS('SHOW COLUMNS FROM `'.pSQL($table).'`');
                if (is_array($query) && count($query)) {
                    foreach ($query as $row) {
                        self::$dbFieldExist[$table][$row['Field']] = true;
                    }
                }
            }

            return isset(self::$dbFieldExist[$table], self::$dbFieldExist[$table][$field]);
        }

        // todo: Migrate to fieldExistsR
        public static function fieldExists($table, $field)
        {
            static $field_exists = array();
            $fields = array();

            if (isset($field_exists[$table.$field])) {
                return $field_exists[$table.$field];
            }

            $sql = 'SHOW COLUMNS FROM  `'.$table.'` IN `'.pSQL(_DB_NAME_).'`';

            // Check if exists
            //
            $query = Db::getInstance()->ExecuteS($sql, true, false);

            if (!is_array($query) || !count($query)) {
                return (null);
            }

            foreach ($query as $row) {
                $fields[$row['Field']] = 1;
            }

            if (isset($fields[$field])) {
                $field_exists[$table.$field] = true;
            } else {
                $field_exists[$table.$field] = false;
            }

            return $field_exists[$table.$field];
        }

        public static function tableExists($table, $use_cache = true)
        {
            static $table_exists = array();
            static $show_tables_content = null;

            if (isset($table_exists[$table])) {
                return $table_exists[$table];
            }

            // Check if exists
            //
            if ($show_tables_content === null) {
                $tables = array();

                $query_result = Db::getInstance()->ExecuteS('SHOW TABLES FROM `'.pSQL(_DB_NAME_).'`', true, false);

                if (!is_array($query_result) || !count($query_result)) {
                    return (null);
                }

                $show_tables_content = $query_result;
            }

            foreach ($show_tables_content as $rows) {
                foreach ($rows as $table_check) {
                    $tables[$table_check] = 1;
                }
            }

            if (isset($tables[$table])) {
                $table_exists[$table] = true;
            } else {
                $table_exists[$table] = false;
            }

            return $table_exists[$table];
        }

        public static function getProtocol($use_ssl = null)
        {
            return ((Configuration::get('PS_SSL_ENABLED') || (!empty($_SERVER['HTTPS']) && Tools::strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://');
        }

        //http://php.net/manual/fr/function.glob.php#106595
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
        public static function xml2array($xml)
        {
            $content = $xml->asXML();

            $content = str_replace(array("\n", "\r", "\t"), '', $content);

            $content = trim(str_replace('"', "'", $content));

            $content = simplexml_load_string($content);

            $array = Tools::jsonDecode(Tools::jsonEncode($content), true);

            return $array;
        }

        /**
         * @param $serialized
         * @param bool|false $object
         * @return bool|mixed
         */
        public static function unSerialize($serialized, $object = true)
        {
            if (method_exists('Tools', 'unSerialize')) {
                return (Tools::unSerialize($serialized, $object));
            } elseif (is_string($serialized) && preg_match('/^[OA]:[0-9]+:/i', $serialized)) {
                return @unserialize($serialized); //TODO: Validation - For compatibility with PS1.4
            }

            return false;
        }

        /**
         * @param $url
         * @param bool $use_include_path
         * @param null $stream_context
         * @param int $curl_timeout
         *
         * @return bool|mixed|string
         */
        public static function fileGetContents($url, $use_include_path = false, $stream_context = null, $curl_timeout = 30, $certificate = null, $disable_ssl_check = null)
        {
            if ($stream_context == null && preg_match('/^https?:\/\//', $url)) {
                if (preg_match('/^https:\/\//', $url)) {
                    $contextOptions = array(
                        'ssl' => array(
                            'verify_peer'   => true,
                            'cafile'        => sprintf('%s/%s', dirname(dirname(__FILE__)), 'cert/cacert.pem'),
                        )
                    );
                } else {
                    $contextOptions = array();
                }

                $stream_context = @stream_context_create(array('http' => array('timeout' => $curl_timeout)), $contextOptions);
            }
            if (in_array(ini_get('allow_url_fopen'), array('On', 'on', '1')) || !preg_match('/^https?:\/\//', $url)) {
                return file_get_contents($url, $use_include_path, $stream_context);//TODO Validation: http://forge.prestashop.com/browse/PSCSX-7758
            } elseif (function_exists('curl_init')) {
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($curl, CURLOPT_TIMEOUT, $curl_timeout);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 2);
                curl_setopt($curl, CURLOPT_CAINFO, sprintf('%s/%s', dirname(dirname(__FILE__)), 'cert/cacert.pem'));
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
                curl_close($curl);
                return $content;
            } else {
                return false;
            }
        }


        /**
         * @param $price
         *
         * @return string
         */
        public static function smartRounding($price, $separator = '.')
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

        public static function bold($text)
        {
            return html_entity_decode('&lt;b&gt;'.$text.'&lt;/b&gt;');
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
                    || !$context->controller->getContainer()) {
                    $originCurrency = $context->currency;
                    // For any reason which causes the container not present,
                    // we create the controller and init again, PS does all the jobs behind
                    $context->controller = new FrontController();
                    $context->controller->init();
                    // Restore origin currency, FrontController::init() overrides our currency. Do the same for other contexts if need
                    if ($originCurrency instanceof Currency) {
                        $context->currency = $originCurrency;
                    }

                    return array('ps1.7.6' => true, 'reinit' => true, 'context' => $context);
                }
            }

            return array('ps1.7.6' => false, 'reinit' => false, 'context' => $context);
        }
    }
}
