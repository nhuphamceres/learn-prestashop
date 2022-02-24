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

class FNAC_Tools extends Tools
{

    /**
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public static function getTables()
    {
        return array_map('reset', Db::getInstance()->executeS('SHOW TABLES'));
    }

    /**
     * Replacement for array_column, only available from PHP 5.5.0
     *
     * @see http://php.net/manual/fr/function.array-column.php
     * @param $array
     * @param $column_name
     * @return array
     */
    public static function arrayColumn($array, $column_name)
    {
        if (function_exists('array_column')) {
            return array_column($array, $column_name);
        }

        return array_map(
            array(__CLASS__, 'arrayColumnFunctionDoubleParameters'),
            $array,
            array_fill(0, count($array), $column_name)
        );
    }

    /**
     * @param array $element
     * @param string $column_name
     * @return mixed
     */
    private static function arrayColumnFunctionDoubleParameters($element, $column_name)
    {
        return $element[$column_name];
    }

    public static function displayPrice($price, $currency = null, $no_utf8 = false, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        if (!$currency) {
            $currency = Currency::getCurrency(Configuration::get('PS_CURRENCY_DEFAULT'));
            $currency = $currency['iso_code'];
        }

        if (version_compare(_PS_VERSION_, '1.7.6', '>=')) {
            try {
                if (!isset($context->controller) || !$context->controller instanceof Controller) {
                    $context->controller = new AdminController();
                }
                // Important to be able to access the SymfonyContainer::getInstance()->get() method !
                Context::getContext()->language = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
                Context::getContext()->controller = new FrontController();
                $context->controller->init();

                // Get currency iso
                if ($currency instanceof Currency) {
                    $currency = $currency->iso_code;
                }

                return static::getContextLocale($context)->formatPrice($price, $currency);
            } catch (Exception $exception) {
                return number_format($price, 2);
            }
        }

        return parent::displayPrice($price, $currency, $no_utf8, $context);
    }

    public static function hfilter($str)
    {
        $str = htmlspecialchars(strip_tags($str));
        $str = str_replace(';', '', $str);
        $str = preg_replace("/\\n/i", '<br>', $str);
        $str = preg_replace("/\\r/i", '<br>', $str);

        return ($str);
    }

    public static function Formula($price, $formula)
    {
        $formula = trim($formula);
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

    public static function CallBack($price, $callback)
    {
        $callback = preg_replace("/\\n/i", '', $callback);
        $callback = preg_replace("/\\r/i", '', $callback);

        $code = str_replace('@', $price, $callback);

        return (eval("return $code ;"));
    }

    /*
    trouvé ici :
    http://fr.php.net/eval
    David Schumann
    04-Nov-2003 08:17
    To evaluate math expressions (multiply, divide, addition, subtraction, percentages),
    use the following function, based on Taras Young's 'evalsum' function posted earlier
    MERCI !
    */
    private static function _matheval($equation)
    {
        $equation = preg_replace('/[^0-9+\-.*\/()%]/', '', $equation);
        $equation = preg_replace('/([+-])([0-9]+)(%)/', '*(1\$1.\$2)', $equation);
        // you could use str_replace on this next line
        // if you really, really want to fine-tune this equation
        $equation = preg_replace("/([0-9]+)(%)/", ".\$1", $equation);
        if ($equation == "") {
            $return = 0;
        } else {
            eval("\$return=".$equation.";");
        }

        return $return;
    }

    public static function toCurrency($price, $to_currency = null)
    {
        //$currency = Currency::getCurrent();

        $c_rate = (is_array($to_currency) ? $to_currency['conversion_rate'] : $to_currency->conversion_rate);

        if ($to_currency) {
            $price /= $c_rate;
        }

        return $price;
    }

    public static function fromCurrency($price, $from_currency = null)
    {
        //$currency = Currency::getCurrent();

        $c_rate = (is_array($from_currency) ? $from_currency['conversion_rate'] : '');

        if ($from_currency) {
            $price *= $c_rate;
        }

        return $price;
    }

    /**
     * Build a categories tree
     *
     * @param array $indexedCategories Array with categories where product is indexed (in order to check checkbox)
     * @param array $categories Categories to list
     * @param array $current Current category
     * @param integer $id_category Current category id
     * @return string html
     */
    public static function recurseCategoryForInclude($indexedCategories, $categories, $current, $id_category = 1, $id_category_default = null, $default_categories = array(), $image_dir, $init = false, $logistic_type_ids = array())
    {
        static $done;
        static $irow;
        static $html;

        if (!isset($html)) {
            $html = null;
        }
        if (!isset($done) || !is_array($done)) {
            $done = array();
        }

        if (is_array($default_categories) && in_array($id_category, $default_categories)) {
            $checked = ' checked="checked"';
        } else {
            $checked = '';
        }

        $logistic_type_id = isset($logistic_type_ids[$id_category]) && $logistic_type_ids[$id_category] ?
            $logistic_type_ids[$id_category] : '';

        if (!isset($done[$current['infos']['id_parent']])) {
            $done[$current['infos']['id_parent']] = 0;
        }
        $done[$current['infos']['id_parent']] += 1;
        $index = $current['infos']['id_parent'];
        if ($index) {
            $todo = sizeof($categories[$index]);
        } else {
            $todo = null;
        }
        $doneC = $done[$current['infos']['id_parent']];

        $level = $current['infos']['level_depth'] + 1;
        $img = $init ? 'lv1.gif' : 'lv'.$level.'_'.($todo == $doneC ? 'f' : 'b').'.gif';

        $html .= '
		<tr class="'.($irow++ % 2 ? 'alt_row' : '').'">
			<td>
				<input type="checkbox" name="categoryBox[]" class="categoryBox'.($id_category_default == $id_category ? ' id_category_default' : '').'" id="categoryBox_'.$id_category.'" value="'.$id_category.'"'.$checked.' />
			</td>
			<td>
				'.$id_category.'
			</td>
			<td>
				<img src="'.$image_dir.$img.'" alt="" /> &nbsp;<label for="categoryBox_'.$id_category.'" class="t">'.Tools::stripslashes($current['infos']['name']).'</label>
			</td>
			<td>
			    <select class="logistic_type_id" name="logistic_type_id['.$id_category.']" rel="'.$logistic_type_id.'"></select>
            </td>
		</tr>';

        if (isset($categories[$id_category])) {
            foreach ($categories[$id_category] as $key => $row) {
                if ($key != 'infos') {
                    FNAC_Tools::recurseCategoryForInclude($indexedCategories, $categories, $categories[$id_category][$key], $key, $id_category_default, $default_categories, $image_dir, false, $logistic_type_ids);
                }
            }
        }

        return ($html);
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


    /*
     * For PS 1.2 compatibility
     */
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

    public static function cPath($id_category, $id_lang = false)
    {
        $c = new Category($id_category);

        if (!$id_lang) {
            $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        }

        $category = '';
        if ($c->id_parent && $c->id_parent != 1) {
            $category .= self::cPath($c->id_parent, $id_lang).' > ';
        }

        if (is_array($c->name)) {
            if (isset($c->name[$id_lang])) {
                $category .= $c->name[$id_lang];
            } else {
                $category .= reset($c->name);
            }
        } else {
            $category .= $c->name;
        }

        return (rtrim($category, ' > '));
    }

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
        $ps_images = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').
            htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'img/p/';

        if ($id_images) {
            foreach ($id_images as $id_image) {
                if (is_array($image_set) && in_array($id_image, $image_set)) {
                    // Multistore workaround: getCombinationImages returns image from other shops
                    $images[] = $ps_images.self::getImageUrl($id_image, $id_product);
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

        $pm_param = unserialize(Configuration::get('PM_PARAMETERS'));
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

    /* Source : http://www.edmondscommerce.co.uk/php/ean13-barcode-check-digit-with-php/
       Many thanks ;) */
    public static function EAN_UPC_Check($code)
    {
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
        $check_digit = $next_ten - $total_sum;

        return ((int)$code == (int)($digits.$check_digit));
    }

    public static function EAN_UPC_isPrivate($code)
    {
        return (in_array(Tools::substr(sprintf('%013s', $code), 0, 1), array('2')));
    }

    public static function displayDate($date, $id_lang = null, $full = false, $separator = '-')
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $id_lang = null;

            return (Tools::displayDate($date, $id_lang, $full));
        } else {
            return (Tools::displayDate($date, $id_lang, $full, $separator));
        }
    }

    /* http://stackoverflow.com/questions/6054033/pretty-printing-json-with-php */
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
            $post = "";
            if ($ends_line_level !== null) {
                $new_line_level = $ends_line_level;
                $ends_line_level = null;
            }
            if ($char === '"' && $prev_char != '\\') {
                $in_quotes = !$in_quotes;
            } elseif (!$in_quotes) {
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
                    case ',':
                        $ends_line_level = $level;
                        break;

                    case ':':
                        $post = " ";
                        break;

                    case " ":
                    case "\t":
                    case "\n":
                    case "\r":
                        $char = "";
                        $ends_line_level = $new_line_level;
                        $new_line_level = null;
                        break;
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

    /**
     * jsonDecode convert json string to php array / object
     *
     * @param string $json
     * @param boolean $assoc (since 1.4.2.4) if true, convert to associativ array
     * @return array
     */
    public static function jsonDecode($json, $assoc = false, $depth = 512, $options = 0)
    {
        if (function_exists('json_decode')) {
            return Tools::jsonDecode($json, $assoc, $depth, $options);
        } else {
            include_once(dirname(__FILE__).'/json/json.php');
            $pearJson = new Services_JSON(($assoc) ? SERVICES_JSON_LOOSE_TYPE : 0);

            return $pearJson->decode($json);
        }
    }

    /**
     * Convert an array to json string
     *
     * @param array $data
     * @param bool $prettyprint
     * @param int $depth
     * @return string json
     */
    public static function jsonEncode($data, $prettyprint = true, $depth = 512)
    {
        if (function_exists('json_encode')) {
            $json = Tools::jsonEncode($data);
        } else {
            include_once(dirname(__FILE__).'/json/json.php');
            $pearJson = new Services_JSON();
            $json = $pearJson->encode($data);
        }
        if ($prettyprint) {
            return (self::jsonPrettyPrint($json));
        } else {
            return ($json);
        }
    }

    public static function encode($configuration)
    {
        return base64_encode($configuration); // TODO: Validation: Configuration Requirement
    }

    public static function decode($configuration)
    {
        return base64_decode($configuration); // TODO: Validation: Configuration Requirement
    }
}
