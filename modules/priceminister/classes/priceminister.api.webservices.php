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

interface iPriceminister
{

    public function make_request($url, $params);

    public function data_check($result);

    public function process_response($result);

    public function jsondata($response);

    public function post_curl($url, $params);

    public function xml2array($contents, $get_attributes = 1, $priority = 'tag');
}

abstract class PriceministerWebservices implements iPriceminister
{

    public $config = array();
    public $params = array();

    public static function xmlpp($xml, $html_output = false)
    {
        $xml_obj = new SimpleXMLElement($xml);
        $level = 4;
        $indent = 0; // current indentation level
        $pretty = array();

        // get an array containing each XML element
        $xml = explode("\n", preg_replace('/>\s*</', ">\n<", $xml_obj->asXML()));

        // shift off opening XML tag if present
        if (count($xml) && preg_match('/^<\?\s*xml/', $xml[0])) {
            $pretty[] = array_shift($xml);
        }

        foreach ($xml as $el) {
            if (preg_match('/^<([\w])+[^>\/]*>$/U', $el)) {
                // opening tag, increase indent
                $pretty[] = str_repeat(' ', $indent).$el;
                $indent += $level;
            } else {
                if (preg_match('/^<\/.+>$/', $el)) {
                    $indent -= $level;
                }  // closing tag, decrease indent
                if ($indent < 0) {
                    $indent += $level;
                }
                $pretty[] = str_repeat(' ', $indent).$el;
            }
        }

        $xml = implode('\n', $pretty);

        return ($html_output) ? htmlentities($xml) : $xml;
    }

    /**
     * Name of the alias or template to be fetched
     *
     * @param type $name used to verify if file is already stored
     * @param type $url used to make post request in case data needs to be refreshed
     * @param type $params used to make post request in case data needs to be refreshed
     * @return type
     */
    public function cache($name, $url, $params, $array = false, $cache_only = false)
    {
        $dir = PriceMinister::getTemplateDir();
        $output_file = PriceMinister::getTemplateFilename($name);

        try {
            if (file_exists($output_file) && filesize($output_file) > 1024) {
                if ($cache_only) {
                    $contents = PriceMinisterTools::file_get_contents($output_file);
                    if ($contents) {
                        return (simplexml_load_string($contents));
                    }
                }
                // the condition was changed, from ">" to "<" as it was always fetching URL, instead of local file
                if ((time() - filectime($output_file)) < ((60 * 60 * 24 * 30) + rand(86400, 86400 * 7))) {
                    $contents = PriceMinisterTools::file_get_contents($output_file);
                    if (!empty($contents)) {
                        return simplexml_load_string($contents);
                    }
                }
            }
            $contents = $this->make_request($url, $params, $array);

            if (file_exists($output_file) && filesize($output_file) > 1024 && empty($contents)) {
                return (PriceMinisterTools::file_get_contents($output_file));
            } elseif (empty($contents)) {
                return (false);
            }

            if (file_exists($output_file)) {
                unlink($output_file);
            }

            if (!is_dir($dir)) {
                if (!mkdir($dir)) {
                    return (false);
                }
            }

            if (!is_writable($dir)) {
                chmod($dir, 0775);
            }

            if (!$contents->asXML($output_file)) {
                return ($this->make_request($url, $params, $array));
            }
        } catch (Exception $e) {
            $contents = new SimpleXMLElement('<errorresponse>'.$e->getMessage().'</errorresponse>');
        }

        return ($contents);
    }

    public function make_request($url, $params, $array = true)
    {
        $result = $this->post_curl($url, $params);
        $result = str_replace(array('`', '\\b'), '', $result);

        if ($array) {
            try {
                $response_array = $this->xml2array($result);
                $response = $this->data_check($response_array, $array);
            } catch (Exception $e) {
                $response = array('errorresponse' => $e->getMessage());
            }

            return (isset($this->config['output']) && $this->config['output'] == 'json') ? $this->jsondata($response) : $response;
        } else {
            try {
                $response_xml = $this->xmldata($result);
            } catch (Exception $e) {
                $response_xml = new SimpleXMLElement('<errorresponse>'.$e->getMessage().'</errorresponse>');
            }
            if ($response_xml === false || !($response_xml instanceof SimpleXMLElement)) {
                $response_xml = new SimpleXMLElement('<errorresponse>An error occured, no valid response could be obtained from the Rakuten API</errorresponse>');
            }

            return $response_xml;
        }
    }

    public function post_curl($url, $params)
    {
        $ch = curl_init();

        // Patch - la soumission ne fonctionne pas sur certaines configuration (?)
        //
        /*
                $currentDir = getcwd() ;
                if ( isset($params['file']) && $params['file'] )
                {
                  $file = realpathTools::substr($params['file'], 1)) ;
                  chdir(dirname($file));
                  $file = basename($file);
                  $params['file'] = '@'.$file ;
                }
        */

        if (isset($params['file']) && $params['file']) {
            $sourcefile = strpos($params['file'], '@') !== false ? Tools::substr($params['file'], 1) : $params['file'];
            $uploaddir = realpath(dirname($sourcefile)).DIRECTORY_SEPARATOR;
            $uploadfile = $uploaddir.basename($sourcefile);

            if (class_exists('CURLFile')) {
                $params['file'] = new CurlFile($uploadfile, basename($sourcefile));
            } else {
                $params['file'] = '@'.$uploadfile;
            }
        }

        if (isset($this->config['debug']) && $this->config['debug']) {
            print_r($params, true);
        }

        // &channel=common-services-shop
        $url = rtrim($url, '&').'&channel=common-services';

        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);

        $page = curl_exec($ch);

        //chdir($currentDir) ; // pour le patch

        if (isset($this->config['debug']) && $this->config['debug']) {
        	PriceMinisterTools::pre(array(
		        print_r(curl_getinfo($ch), true),       // get error info
		        'cURL error number:'.curl_errno($ch)."\n",     // print error info
		        'cURL error:'.curl_error($ch)."\n",
		        htmlspecialchars(print_r($page, true))
	        ));
        }

        curl_close($ch);

        return $page;
    }

    public function xml2array($contents, $get_attributes = 1, $priority = 'tag')
    {
        if (!$contents) {
            return array();
        }

        if (!function_exists('xml_parser_create')) {
            print "'xml_parser_create()' function not found!";

            return array();
        }

        //Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
        // http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);

        if (!$xml_values) {
            return;
        }

        //Initializations
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();

        $current = &$xml_array; //Refference

        // Thanks to Eric, all of these values are undeclared
        // What the fuck is this function for ? Half of it is useless... -____-"
        $attributes = null;
        $value = null;
        $type = null;
        $parent = null;
        $level = null;
        $tag = null;
        //Go through the tags.
        $repeated_tag_index = array();//Multiple tags with same name will be turned into an array
        foreach ($xml_values as $data) {
            unset($attributes, $value);//Remove existing values, or there will be trouble

            //This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data);//We could use the array by itself, but this cooler.

            $result = array();
            $attributes_data = array();

            if (isset($value) && $value) {
                if ($priority == 'tag') {
                    $result = $value;
                } else {
                    $result['value'] = $value;
                } //Put the value in a assoc array if we are in the 'Attribute' mode
            }

            //Set the attributes too.
            if (isset($attributes) && $attributes && $get_attributes) {
                foreach ($attributes as $attr => $val) {
                    if ($priority == 'tag') {
                        $attributes_data[$attr] = $val;
                    } else {
                        $result['attr'][$attr] = $val;
                    } //Set all the attributes in a array called 'attr'
                }
            }

            //See tag status and do the needed.
            if ($type == 'open') {//The starting of the tag '<tag>'
                $parent[$level - 1] = &$current;
                if (!is_array($current) || (!in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    if ($attributes_data) {
                        $current[$tag.'_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag.'_'.$level] = 1;

                    $current = &$current[$tag];
                } else { //There was another element with the same tag name

                    if (isset($current[$tag][0])) {//If there is a 0th element it is already an array
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                        $repeated_tag_index[$tag.'_'.$level]++;
                    } else {//This section will make the value an array if multiple tags with the same name appear together
                        $current[$tag] = array($current[$tag], $result);//This will combine the existing item and the new item together to make an array
                        $repeated_tag_index[$tag.'_'.$level] = 2;

                        if (isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                            unset($current[$tag.'_attr']);
                        }
                    }
                    $last_item_index = $repeated_tag_index[$tag.'_'.$level] - 1;
                    $current = &$current[$tag][$last_item_index];
                }
            } elseif ($type == 'complete') {
                //Tags that ends in 1 line '<tag />'
                //See if the key is already taken.
                if (!isset($current[$tag])) {
                    //New Key
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    if ($priority == 'tag' && $attributes_data) {
                        $current[$tag.'_attr'] = $attributes_data;
                    }
                } else {
                    //If taken, put all things inside a list(array)
                    if (isset($current[$tag][0]) && is_array($current[$tag])) {//If it is already an array...

                        // ...push the new element into that array.
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;

                        if ($priority == 'tag' && $get_attributes && $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level].'_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag.'_'.$level]++;
                    } else { //If it is not an array...
                        $current[$tag] = array($current[$tag], $result); //...Make it an array using using the existing value and the new value
                        $repeated_tag_index[$tag.'_'.$level] = 1;
                        if ($priority == 'tag' && $get_attributes) {
                            if (isset($current[$tag.'_attr'])) {
                                //The attribute of the last(0th) tag must be moved as well
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
            } elseif ($type == 'close') { //End of tag '</tag>'
                $current = &$parent[$level - 1];
            }
        }

        return ($xml_array);
    }

    public function data_check($result)
    {
        return (isset($result['errorresponse'])) ? $result['errorresponse'] : $this->process_response($result);
    }

    public function process_response($result)
    {
        if (isset($this->config['output_type']) && $this->config['output_type'] == 'raw') {
            return $result;
        } else {
            $result = array_shift($result);

            return (isset($this->config['output_type']) && $this->config['output_type'] == 'pair') ? $result : (isset($result['response']) ? $result['response'] : null);
        }
    }

    public function jsondata($response)
    {
        return PriceMinisterTools::jsonEncode($response);
    }

    public function xmldata($page)
    {
        if (empty($page)) {
            if (isset($this->config['debug']) && $this->config['debug']) {
                printf('%s(#%d): empty string passed to the function', basename(__FILE__), __LINE__);
            }

            return (null);
        }

        // remove namespaces
        $page = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $page);
        $page = preg_replace('/[a-zA-Z]+:([a-zA-Z]+[=>])/', '$1', $page);

        // filters
        $page = str_replace(array("\n", "\r", "\t"), '', $page);
        $page = str_replace('&', '&amp;', $page);
        $page = trim(str_replace('"', "'", $page));

        $page = str_replace(array(''), '', $page);

        $xml = simplexml_load_string($page, null, LIBXML_NOCDATA);

        if (!$xml instanceof SimpleXMLElement) {
            if (isset($this->config['debug']) && $this->config['debug']) {
                printf('%s(#%d): invalid string passed to the function: "%s"', basename(__FILE__), __LINE__, $page);
            }

            return (null);
        }

        return ($xml);
    }
}
