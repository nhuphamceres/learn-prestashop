<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
 * Contact by Email :  support.priceminister@common-services.com
 */

require_once(_PS_MODULE_DIR_.'priceminister/common/tools.class.php');

class PriceMinisterTools extends CommonTools
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

    public static function languages($force_display = false)
    {
        static $languages = null;
        static $available_languages = array();

        if ($available_languages) {
            return ($available_languages);
        }

        if (!$languages) {
            $languages = Language::getLanguages(false);
        }

        foreach ($languages as $language) {
            // For active languages
            if (!$force_display && $language['active'] == false) {
                continue;
            }

            $iso_code = Tools::strtolower($language['iso_code']);
            // Only FR
            if ($iso_code !== 'fr') {
                continue;
            }

            $language['active'] = true;
            $language['default'] = ($iso_code == 'us' ? true : false);

            $image = sprintf('geo_flags_web2/flag_%s_64px.png', $language['iso_code']);
            $image_path = _PS_MODULE_DIR_.'feedbiz/images/'.$image;

            $image_native = 'img/p/'.sprintf('%d.jpg', $language['id_lang']);
            $image_native_path = _PS_ROOT_DIR_.'/'.$image_native;

            if (file_exists($image_path)) {
                $language['image'] = $image;
            } elseif (file_exists($image_native_path)) {
                $language['image'] = $image_native;
            } else {
                $language['image'] = null;
            }

            $available_languages[$language['id_lang']] = $language;
        }

        return ($available_languages);
    }

	/**
	 * @param bool $force_login
	 * @param bool $force_token
	 *
	 * @return array
	 */
    public static function Auth($force_login = false, $force_token = false)
    {
        $sandbox = false;

        $pm_http_url = 'http://ws.fr.shopping.rakuten.com/';
        $pm_https_url = 'https://ws.fr.shopping.rakuten.com/';

        $pm_sandbox_http_url = 'http://sandbox.fr.shopping.rakuten.com/';
        $pm_sandbox_https_url = 'https://sandbox.fr.shopping.rakuten.com/';

        if ($force_login && $force_token) {
            $pm_login = $force_login;
            $pm_token = $force_token;
        } else {
            $pm_credentials = PriceMinister::getConfig(PriceMinister::CONFIG_PM_CREDENTIALS);

            if (isset($pm_credentials['test']) && $pm_credentials['test']) {
                $sandbox = true;
            }

            if (empty($pm_credentials['login']) || empty($pm_credentials['token'])) {
                return false;
            }

            $pm_login = $pm_credentials['login'];
            $pm_token = $pm_credentials['token'];
        }

        // Price Minister Parameters
        //
        $pm_config = array(
        	'login' => $pm_login,
	        'pwd'   => $pm_token
        );

        if ($sandbox) {
            $pm_config['http_url'] = $pm_sandbox_http_url;
            $pm_config['https_url'] = $pm_sandbox_https_url;
        } else {
            $pm_config['http_url'] = $pm_http_url;
            $pm_config['https_url'] = $pm_https_url;
        }

        return ($pm_config);
    }

    public static function hfilter($str)
    {
        $str = htmlspecialchars(strip_tags($str));
        $str = preg_replace("/\\n/i", '<br>', $str);
        $str = preg_replace("/\\r/i", '<br>', $str);

        return ($str);
    }

    public static function Formula($price, $formula)
    {
        $formula = trim(str_replace(',', '.', $formula));
        $formula = preg_replace("/\\n/i", '', $formula);
        $formula = preg_replace("/\\r/i", '', $formula);

        if (preg_match('#([0-9\., ]*)%#', $formula, $result)) {
            $toPercent = $price * ((float)$result[1] / 100);
            $formula = preg_replace('#([0-9\., ]*)%#', $toPercent, $formula);
        }
        $formula = str_replace('%', '', $formula);
        $equation = str_replace('@', $price ? $price : 0, $formula);

        $result = self::_matheval($equation);

        return ($result);
    }

    private static function _matheval($equation)
    {
        $equation = preg_replace('/[^0-9+\-.*\/()%]/', '', $equation);
        $equation = preg_replace('/([+-])([0-9]+)(%)/', '*(1\$1.\$2)', $equation);
        // you could use str_replace on this next line
        // if you really, really want to fine-tune this equation
        $equation = preg_replace('/([0-9]+)(%)/', '.\$1', $equation);
        if ($equation == '') {
            $return = 0;
        } else {
            eval('\$return='.$equation.';');
        }

        return $return;
    }

    /*
      found there :
      http://fr.php.net/eval
      David Schumann
      04-Nov-2003 08:17
      To evaluate math expressions (multiply, divide, addition, subtraction, percentages),
      use the following function, based on Taras Young's 'evalsum' function posted earlier
      MERCI !
     */

    public static function toCurrency($price, $to_currency = null)
    {
        $c_rate = (is_array($to_currency) ? $to_currency['conversion_rate'] : $to_currency->conversion_rate);

        if ($to_currency) {
            $price /= $c_rate;
        }

        return $price;
    }

    public static function fromCurrency($price, $from_currency = null)
    {
        $c_rate = (is_array($from_currency) ? $from_currency['conversion_rate'] : $to_currency->conversion_rate);

        if ($from_currency) {
            $price *= $c_rate;
        }

        return $price;
    }

    public static function getCategories($categories, $current, $id_category = 1, $init = false)
    {
        global $done;
        global $html;
        $html = isset($html) ? $html : null;

        if (!isset($done[$current['infos']['id_parent']])) {
            $done[$current['infos']['id_parent']] = 0;
        }
        $done[$current['infos']['id_parent']] += 1;

        if (isset($current['infos']['id_parent'])) {
            $todo = count($categories[$current['infos']['id_parent']]);
        } else {
            $todo = null;
        }

        $doneC = $done[$current['infos']['id_parent']];

        $level = $current['infos']['level_depth'] + 1;

        for ($spacer = '', $i = 0; $i < $level; $i++) {
            if ($level != 1) {
                $spacer .= '&nbsp;&nbsp;';
            }
        }

        if ($init) {
            $html[$id_category] = $spacer.Tools::stripslashes($current['infos']['name']);
        }

        if (isset($categories[$id_category])) {
            foreach ($categories[$id_category] as $key => $row) {
                if ($key != 'infos') {
                    self::getCategories($categories, $categories[$id_category][$key], $key, 1);
                }
            }
        }

        return ($html);
    }

    public static function orderPageURL($order_id)
    {
        return (sprintf('http://www.priceminister.com/purchase?action=saleview&purchaseid=%s', $order_id));
    }

    public static function cPath($id_category)
    {
        $c = new Category($id_category, Context::getContext()->cookie->id_lang);

        $category = '';

        if ($c->id_parent && $c->id_parent != 1) {
            $category .= self::cPath($c->id_parent).' > ';
        }

        $category .= $c->name;

        return (rtrim($category, ' > '));
    }

    public static function cleanShippingMethod($shipping_method)
    {
        return (preg_replace(array('/&szlig;/', '/&(..)lig;/', '/&([aouAOU])uml;/', '/&(.)[^;]*;/'), array('ss', '$1', '$1'.'e', '$1'), htmlentities($shipping_method, ENT_NOQUOTES, 'UTF-8')));
    }

    /* Simply remove accents from shipping methods !
     * $order->deliveryinformation->shippingtype */

    public static function getProductImages($id_product, $id_product_attribute, $id_lang)
    {
        $product = new Product($id_product);

        if (($cover = Product::getCover($id_product))) {
            $id_image_cover = (int)$cover['id_image'];
        } else {
            $id_image_cover = null;
        }

        $images = $product->getImages($id_lang);
        $image_set = array();
        if (is_array($images) && count($images)) {
            foreach ($images as $image) {
                $image_set[] = $image['id_image'];
            }
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
                $images = $product->getImages($id_lang);
                if (is_array($images) && count($images)) {
                    foreach ($images as $key => $image) {
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
            $images = $product->getImages($id_lang);
            $id_images = array();

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
                if (is_array($image_set) && in_array($id_image, $image_set)) {
                    // Multistore workaround: getCombinationImages returns image from other shops
                    $images[] = self::getImageUrl($id_image, $id_product);
                }
            }
        }

        return ($images);
    }

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

        $pm_param = unserialize(Configuration::get(PriceMinister::CONFIG_PM_PARAMETERS));
        if (isset($pm_param['image_type']) && $pm_param['image_type']) {
            $image_type = $pm_param['image_type'];
        }

        if (empty($image_type) && method_exists('ImageType', 'getFormatedName')) {
            $image_type = ImageType::getFormatedName('large');
        } elseif (empty($image_type)) {
            $image_type = 'large';
        }

        $imageurl = sprintf('%s-%s.%s', $imageurl, $image_type, $ext);

        return $imageurl;
    }

    public static function getHttpHost($http = false, $entities = false, $ignore_port = false)
    {
        if (method_exists('Tools', 'getHttpHost')) {
            return (Tools::getHttpHost($http, $entities, $ignore_port));
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

    /*
     * For PS 1.2 compatibility
     */

    public static function safeText($string)
    {
        $string = @utf8_encode(utf8_decode($string));
        $string = html_entity_decode($string, ENT_COMPAT, 'UTF-8');

        if (function_exists('filter_var')) {
            $string = filter_var($string, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
        }
        $string = str_replace('&#39;', "'", $string);

        return ($string);
    }

    public static function jsonPrettyPrint($json)
    {
        $result = '';
        $level = 0;
        $prev_char = '';
        $in_quotes = false;
        $ends_line_level = null;
        $json_length = Tools::strlen($json);

        for ($i = 0; $i < $json_length; $i++) {
            $char = $json[$i];
            $new_line_level = null;
            $post = '';
            if ($ends_line_level !== null) {
                $new_line_level = $ends_line_level;
                $ends_line_level = null;
            }
            if ($char === '"' && $prev_char != '\\') {
                $in_quotes = !$in_quotes;
            } else {
                if (!$in_quotes) {
                    switch ($char) {
                        case '}':
                        case ']':
                            $level--;
                            $ends_line_level = null;
                            $new_line_level = $level;
                            break;

                        case '{':
                        case '[':
                            $level++;
                            // no break
                        case ',':
                            $ends_line_level = $level;
                            break;

                        case ':':
                            $post = ' ';
                            break;

                        case ' ':
                        case '\t':
                        case '\n':
                        case '\r':
                            $char = '';
                            $ends_line_level = $new_line_level;
                            $new_line_level = null;
                            break;
                    }
                }
            }
            if ($new_line_level !== null) {
                $result .= "\n".str_repeat("\t", $new_line_level);
            }
            $result .= $char.$post;
            $prev_char = $char;
        }

        return $result;
    }

    public static function encode($data)
    {
        return ($data);
    }

    public static function decode($data)
    {
        return ($data);
    }

    public static function is_dir_writeable($path)
    {
        $path = rtrim($path, '/\\');

        $testfile = sprintf('%s%stestfile_%s.chk', $path, DIRECTORY_SEPARATOR, uniqid());
        $timestamp = time();

        if (@file_put_contents($testfile, $timestamp)) {
            $result = trim(PriceMinisterTools::file_get_contents($testfile));
            @unlink($testfile);

            if ((int)$result == (int)$timestamp) {
                return (true);
            }
        }

        return (false);
    }

    public static function file_get_contents($url, $use_include_path = false, $stream_context = null, $curl_timeout = 30, $fallback = false)
    {
        if ($stream_context == null && preg_match('/^https?:\/\//', $url)) {
            if (preg_match('/^https:\/\//', $url)) {
                $contextOptions = array(
                    'ssl' => array(
                        'verify_peer' => true,
                        'cafile' => sprintf('%s/%s', dirname(dirname(__FILE__)), 'cert/cacert.pem'),
                    )
                );
            } else {
                $contextOptions = array();
            }

            $stream_context = @stream_context_create(array('http' => array('timeout' => $curl_timeout)), $contextOptions);
        }
        if (in_array(ini_get('allow_url_fopen'), array('On', 'on', '1')) || !preg_match('/^https?:\/\//', $url)) {
            return file_get_contents($url, $use_include_path, $stream_context); // TODO Validation: http://forge.prestashop.com/browse/PSCSX-7758
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

    public static function getFriendlyUrl($text)
    {
        $text = htmlentities($text);
        $text = preg_replace(array('/&szlig;/', '/&(..)lig;/', '/&([aouAOU])uml;/', '/&(.)[^;]*;/'), array('ss', '$1', '$1'.'e', '$1'), $text);
        $text = preg_replace('/[\x00-\x1F\x21-\x2B\x3A-\x3F\x5B-\x60\x7B-\x7F]/', '', $text); // remove non printable
        $text = preg_replace('/[ \t]+/', '-', $text);
        $text = str_replace(array('_', ',', '.', '/', '+', '?', '&', '='), '-', $text);

        return Tools::strtolower(trim($text));
    }

    public static function ucfirst($str)
    {
        if (method_exists('Tools', 'ucfirst')) {
            return Tools::ucfirst($str);
        }

        return Tools::strtoupper(Tools::substr($str, 0, 1)).Tools::substr($str, 1);
    }

    /* HTML ENCODING / DECODING */
    public static function encodeHtml($str)
    {
        $str = (string)$str;
        $val = htmlentities($str, ENT_QUOTES, 'UTF-8', false);

        return $val;
    }

    public static function decodeHtml($str)
    {
        $str = (string)$str;
        $val = html_entity_decode($str, ENT_QUOTES, 'UTF-8');

        return $val;
    }

    public static function fieldExists($table, $field)
    {
        static $field_exists = array();

        if (isset($field_exists[$table.$field])) {
            return $field_exists[$table.$field];
        }

        // Check if exists
        $fields = array();
        $query = Db::getInstance()->ExecuteS('SHOW COLUMNS FROM `'.$table.'`');
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
        if (isset($table_exists[$table])) {
            return $table_exists[$table];
        }

        static $query;
        if (!is_array($query)) {
            $query = Db::getInstance()->executeS('SHOW TABLES', true, false);
        }

        // Check if exists
        $tables = array();
        foreach ($query as $rows) {
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

    public static function EAN_UPC_Check($code)
    {
        return true;
        if (!is_numeric($code) || Tools::strlen($code) < 12) {
            return (false);
        }

        // first change digits to a string so that we can access individual numbers
        $digits = sprintf('%012s', Tools::substr(sprintf('%013s', $code), 0, 12));
        // 1. Add the values of the digits in the even-numbered positions: 2, 4, 6, etc.
        $even_sum = $digits{1} + $digits{3} + $digits{5} + $digits{7} + $digits{9} + $digits{11};
        // 2. Multiply this result by 3.
        $even_sum_three = $even_sum * 3;
        // 3. Add the values of the digits in the odd-numbered positions: 1, 3, 5, etc.
        $odd_sum = $digits{0} + $digits{2} + $digits{4} + $digits{6} + $digits{8} + $digits{10};
        // 4. Sum the results of steps 2 and 3.
        $total_sum = $even_sum_three + $odd_sum;
        // 5. The check character is the smallest number which, when added to the result in step 4,  produces a multiple of 10.
        $next_ten = (ceil($total_sum / 10)) * 10;
        $check_digit = (int)$next_ten - $total_sum;
        $last_digit = (int)Tools::substr($code, Tools::strlen($code) - 1, 1);

        return ((int)$last_digit == (int)$check_digit);
    }

    /*Source : http://www.edmondscommerce.co.uk/php/ean13-barcode-check-digit-with-php/*/

    public static function EAN_UPC_isPrivate($code)
    {
        return (in_array(Tools::substr(sprintf('%013s', $code), 0, 1), array('2')));
    }

    public static function PriceRule($price, $rule)
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

    public static function moduleIsInstalled($module_name)
    {
        if (method_exists('Module', 'isInstalled')) {
            return (Module::isInstalled($module_name));
        } else {
            Db::getInstance()->ExecuteS('SELECT `id_module` FROM `'._DB_PREFIX_.'module` WHERE `name` = "'.pSQL($module_name).'"');

            return ((bool)Db::getInstance()->NumRows());
        }
    }

    //http://php.net/manual/fr/function.glob.php#106595
    public static function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        if (is_array($files) && count($files)) {
            $dirs = glob(dirname($pattern).'/*', GLOB_ONLYDIR | GLOB_NOSORT);

            if (is_array($dirs) && count($dirs)) {
                foreach ($dirs as $dir) {
                    $other_files = self::glob_recursive($dir.'/'.basename($pattern), $flags);

                    if (is_array($other_files) && count($other_files)) {
                        $files = array_merge($files, $other_files);
                    }
                }
            }
        }

        return $files;
    }

    public static function arrayColumn($array, $column_name, $index_key = null)
    {
        if (function_exists('array_column')) {
            return array_column($array, $column_name);
        }

        return array_map(
            array(self, 'arrayColumnCallback'),
            $array,
            array_fill(0, count($array), $column_name)
        );
    }

    private static function arrayColumnCallback($element, $column_name)
    {
        return $element[$column_name];
    }

    public static function base64Encode($str)
    {
        return base64_encode($str);
    }

    public static function base64Decode($str, $strict = null)
    {
        return base64_decode($str, $strict);
    }
}
