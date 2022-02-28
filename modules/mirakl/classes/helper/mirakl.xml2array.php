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

if (!class_exists('MiraklHelperXML2Array')) {
    class MiraklHelperXML2Array
    {
        private static $xml      = null;
        private static $encoding = 'UTF-8';

        /*
         * Initialize the root XML node [optional]
         *
         * @param $version
         * @param $encoding
         * @param $format_output
         */
        public static function init($version = '1.0', $encoding = 'UTF-8', $format_output = true)
        {
            self::$xml = new DOMDocument($version, $encoding);
            self::$xml->formatOutput = $format_output;
            self::$encoding = $encoding;
        }

        /*
         * Convert an XML to Array
         *
         * @param string $node_name - name of the root node to be converted
         * @param array $arr - aray to be converterd
         *
         * @return DOMDocument
         */
        public static function &createArray($input_xml)
        {
            $array = array();
            $xml = self::getXMLRoot();
            if (is_string($input_xml)) {
                $parsed = $xml->loadXML($input_xml);
                if (!$parsed) {
                    throw new Exception('[XML2Array] Error parsing the XML string.');
                }
            } else {
                if (get_class($input_xml) != 'DOMDocument') {
                    throw new Exception('[XML2Array] The input XML object should be of type: DOMDocument.');
                }
                $xml = self::$xml = $input_xml;
            }
            $array[$xml->documentElement->tagName] = self::convert($xml->documentElement);
            self::$xml = null;    // clear the xml node in the class for 2nd time use.
            return $array;
        }

        /*
         * Convert an Array to XML
         *
         * @param mixed $node - XML as a string or as an object of DOMDocument
         *
         * @return mixed
         */
        private static function &convert($node)
        {
            $output = array();

            switch ($node->nodeType) {
                case XML_CDATA_SECTION_NODE:
                    $output['@cdata'] = trim($node->textContent);
                    break;

                case XML_TEXT_NODE:
                    $output = trim($node->textContent);
                    break;

                case XML_ELEMENT_NODE:
                    // for each child node, call the covert function recursively
                    for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
                        $child = $node->childNodes->item($i);
                        $v = self::convert($child);
                        if (isset($child->tagName)) {
                            $t = $child->tagName;

                            // assume more nodes of same kind are coming
                            if (!isset($output[$t])) {
                                $output[$t] = array();
                            }
                            $output[$t][] = $v;
                        } else {
                            //check if it is not an empty text node
                            if ($v !== '') {
                                $output = $v;
                            }
                        }
                    }

                    if (is_array($output)) {
                        // if only one node of its kind, assign it directly instead if array($value);
                        foreach ($output as $t => $v) {
                            if (is_array($v) && count($v) == 1) {
                                $output[$t] = $v[0];
                            }
                        }
                        if (empty($output)) {
                            //for empty nodes
                            $output = '';
                        }
                    }

                    // loop through the attributes and collect them
                    if ($node->attributes->length) {
                        $a = array();
                        foreach ($node->attributes as $attrName => $attrNode) {
                            $a[$attrName] = (string)$attrNode->value;
                        }
                        // if its an leaf node, store the value in @value instead of directly storing it.
                        if (!is_array($output)) {
                            $output = array('@value' => $output);
                        }
                        $output['@attributes'] = $a;
                    }
                    break;
            }

            return $output;
        }

        /*
         * Get the root XML node, if there isn't one, create it.
         */
        private static function getXMLRoot()
        {
            if (empty(self::$xml)) {
                self::init();
            }

            return self::$xml;
        }
    }
}
