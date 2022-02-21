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

require_once(dirname(__FILE__).'/cdiscount.webservice.class.php');
require_once(dirname(__FILE__).'/cdiscount.configuration.class.php');
require_once(dirname(__FILE__).'/../common/configuration.class.php');
require_once(dirname(__FILE__).'/cdiscount.tools.class.php');

class CDiscountModel extends CDiscountWebservice
{
    const MARKETPLACE = 'CDiscount';
    
    // GetModelList-categoryId is valid in 15 days (and 5 minutes) before need to re-download
    // The same as old file allModelsList.xml.gz
    const VALID_TIME_OF_GET_MODEL_LIST_FILE = 1296300;
    
    const PROPERTY_PUBLIC = 'Type de public';
    const PROPERTY_GENDER = 'Genre';

    protected $debug_caller = 'cdiscount.model';

    private static $instance;
    public static function getInstance($username, $pass, $production, $debug)
    {
        if (!self::$instance) {
            self::$instance = new self($username, $pass, $production, $debug);
        }
        return self::$instance;
    }
    
    public function __construct($username, $password, $prod = true, $debug = false, $demo = false)
    {
        parent::__construct($username, $password, $prod, $debug, $demo);
    }

    public static function toKey($str)
    {
        $str = str_replace(array('-', ',', '.', '/', '+', '.', ':', ';', '>', '<', '?', '(', ')', '!', '"', "'"), array('_', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o'), $str);
        $str = Tools::strtolower(preg_replace('/[^A-Za-z0-9_]/', '', $str));

        return $str;
    }

    // 2021-11-23: Removed unused setType()

    // 2020-07-10: Removed model2Public(), use getModelPublicValues() instead

    public function getModelPublicValues($categoryId, $modelId)
    {
        return $this->modelProperty2Values2020($categoryId, $modelId, self::PROPERTY_PUBLIC);
    }
    
    public function getModelGenderValues($categoryId, $modelId)
    {
        return $this->modelProperty2Values2020($categoryId, $modelId, self::PROPERTY_GENDER);
    }

    /**
     * @param $categoryId
     * @param $modelId
     * @param $property
     * @return array
     */
    protected function modelProperty2Values2020($categoryId, $modelId, $property)
    {
        $modelList = $this->GetModelList($categoryId);
        if (!$modelList) {
            $this->debugDetails->webservice('Failed to GetModelList!');
            return array();
        }

        $propertyValues = array();
        $modelListXml = simplexml_load_string($modelList);
        if ($modelListXml === false) {
            return array();
        }
        $modelListXml->registerXPathNamespace('m', 'http://www.cdiscount.com');
        $modelListXml->registerXPathNamespace('a', 'http://schemas.microsoft.com/2003/10/Serialization/Arrays');
        
        $propertyXpath = sprintf(
            '//s:Envelope/s:Body/m:GetModelListResponse/m:GetModelListResult/m:ModelList/m:ProductModel[m:ModelId="%s"]/m:Definition/m:ListProperties/*[a:Key="%s"]/a:Value/a:string',
            $modelId,
            $property
        );
        $xpathResults = $modelListXml->xpath($propertyXpath);
        if (is_array($xpathResults) && count($xpathResults)) {
            foreach ($xpathResults as $xpathResult) {
                $propertyValues[htmlspecialchars((string)$xpathResult)] = true;
            }
        }

        return array_keys($propertyValues);
    }
    
    // 2020-07-07: Remove unused supportData()
    // 2020-07-13: GetAllModelList is removed. Removed model2Gender(), use getModelGenderValues() instead

    public function getModelListFields($categoryId, $modelId)
    {
        $result = array();
        $model = $this->getModelByModelId($categoryId, $modelId);
        if (!$model instanceof DOMDocument) {
            return array();
        }

        $fieldProperties = $model->getElementsByTagName('KeyValueOfstringArrayOfstringty7Ep6D1');
        foreach ($fieldProperties as $fieldProperty) {
            $fieldXmlName = $fieldProperty->getElementsByTagName('Key')->item(0)->textContent;
            $fieldName = self::getStringFormatted($fieldXmlName, 'HtmlFormField');
            $fieldType = self::getFieldType($fieldXmlName);
            
            $property = array(
                'xml_tag' => $fieldProperty->getElementsByTagName('Key')->item(0)->textContent,
                'html_id' => self::getStringFormatted($fieldXmlName, 'HtmlFieldName'),
                'name' => $fieldName,
                'display' => self::isDisplayedField($fieldXmlName),
                'mandatory' => self::isMandatoryField($fieldXmlName),
                'size' => '60',
            );
            if ($fieldType) {
                $property['type'] = $fieldType;
            } else {
                $property['field'] = $fieldName;
            }
            
            $result[] = $property;
        }
        
        return $result;
    }

    // 2020-07-10: Remove getAllModelListFields(), use getModelListFields() instead
    // 2020-07-13: Remove getModelVariationValues(), use getModelVariations() instead

    public function getModelVariations($categoryId, $modelId)
    {
        $attributes = array();
        $variations = $this->_getModelVariations($categoryId, $modelId);

        if (is_array($variations) && count($variations)) {
            foreach ($variations as $variation) {
                if (!$variation instanceof SimpleXMLElement) {
                    continue;
                }
                if ((string)$variation->VariantValueId && Tools::strlen((string)$variation->VariantValueDescription)) {
                    $variation_key = self::toKey((string)$variation->VariantValueDescription);
                    $attributes[$variation_key] = trim((string)$variation->VariantValueDescription);
                }
            }
        }

        return $attributes;
    }

    /**
     * @param $categoryId
     * @param $modelId
     * @return bool
     */
    protected function checkModelVariation($categoryId, $modelId)
    {
        $variations = $this->_getModelVariations($categoryId, $modelId);
        return is_array($variations) && count($variations);
    }

    /**
     * @param $categoryId
     * @param $modelId
     * @return bool|SimpleXMLElement[]
     */
    protected function _getModelVariations($categoryId, $modelId)
    {
        $modelList = $this->GetModelList($categoryId);
        if (!$modelList) {
            $this->debugDetails->webservice('Failed to GetModelList: ' . $categoryId);
            return false;
        }
        $modelListXml = simplexml_load_string($modelList);
        if ($modelListXml === false) {
            return false;
        }

        $modelListXml->registerXPathNamespace('m', 'http://www.cdiscount.com');
        $variationXpath = sprintf(
            '//s:Envelope/s:Body/m:GetModelListResponse/m:GetModelListResult/m:ModelList/m:ProductModel[m:ModelId="%s"]/m:VariationValueList/m:VariationDescription',
            $modelId
        );

        return $modelListXml->xpath($variationXpath);
    }

    protected $modelValuesByModelId = array();
    public function getModelValuesByModelId($categoryId, $modelId)
    {
        $cache = $this->modelValuesByModelId;
        if (isset($cache[$modelId]) && count($cache[$modelId])) {
            return $cache[$modelId];
        }
        
        $modelList = $this->GetModelList($categoryId);
        if (!$modelList) {
            $this->debugDetails->webservice('Failed to GetModelList: ' . $categoryId);
            return array();
        }
        $modelListXml = simplexml_load_string($modelList);
        if ($modelListXml === false) {
            return array();
        }

        $modelListXml->registerXPathNamespace('m', 'http://www.cdiscount.com');
        $modelListXml->registerXPathNamespace('a', 'http://schemas.microsoft.com/2003/10/Serialization/Arrays');
        $definitionsXpath = sprintf(
            '//s:Envelope/s:Body/m:GetModelListResponse/m:GetModelListResult/m:ModelList/m:ProductModel[m:ModelId="%s"]/m:Definition/m:ListProperties/a:*',
            $modelId
        );
        $definitions = $modelListXml->xpath($definitionsXpath);
        
        if (!is_array($definitions) || !count($definitions)) {
            return array();
        }

        foreach ($definitions as $definition) {
            $stripNamespace = str_replace('<a:', '<', $definition->saveXML());
            $stripNamespace = str_replace('</a:', '</', $stripNamespace);
            $definitionWtNs = simplexml_load_string($stripNamespace);
            if ($definitionWtNs instanceof SimpleXMLElement && $definitionWtNs->Key && $definitionWtNs->Value->string) {
                $attributeName = (string)$definitionWtNs->Key;
                $attributeNameKey = self::toKey((string)$definitionWtNs->Key);

                $attributeItems = array();
                foreach ($definitionWtNs->Value->string as $attribute) {
                    $attributeItem = (string)$attribute;
                    $attributeItems[self::toKey($attributeItem)] = $attributeItem;
                }
                $cache[$modelId][$attributeNameKey] = array(
                    'title' => $attributeName,
                    'values' => $attributeItems,
                );
                $this->modelValuesByModelId = $cache;
            }
        }
        
        return $cache[$modelId];
    }

    // 2020-07-13: Remove getModelValues(), use getModelValuesByModelId() instead

    public function getModelByModelId($categoryId, $modelId)
    {
        $modelList = $this->GetModelList($categoryId);
        if (!$modelList) {
            $this->debugDetails->webservice('Failed to GetModelList: ' . $categoryId);
            return false;
        }
        $modelListXml = simplexml_load_string($modelList);
        if ($modelListXml === false) {
            return false;
        }

        $modelListXml->registerXPathNamespace('m', 'http://www.cdiscount.com');
        $productModelXpath = sprintf(
            '//s:Envelope/s:Body/m:GetModelListResponse/m:GetModelListResult/m:ModelList/m:ProductModel[m:ModelId="%s"]',
            $modelId
        );
        $productModel = $modelListXml->xpath($productModelXpath);

        if (!is_array($productModel) || !count($productModel)) {
            return false;
        }

        $productXmlStructure = (string)$productModel[0]->ProductXmlStructure;
        if (empty($productXmlStructure)) {
            $productModel[0]->ProductXmlStructure = null;
        }

        // Creates DOM instance of XML Structure
        $output = new DOMDocument();
        $output->loadXML($productModel[0]->asXML());
        $output->formatOutput = true;
        $structureElement = $output->getElementsByTagName('ProductXmlStructure')->item(0);

        // Find Node to be replaced (ProductXmlStructure)
        $dom = new DOMDocument();
        $dom->loadXML($productXmlStructure);
        $dom->formatOutput = true;
        $nodeList = $dom->getElementsByTagName('Product');
        foreach ($nodeList as $node) {
            $importedNode = $output->importNode($node->cloneNode(true), true);
            $structureElement->appendChild($importedNode);
        }

        return $output;
    }

    // 2020-07-10: Remove getModel(), use getModelByModelId() instead

    /**
     * Returns a String formatted
     * @param type $str
     * @param type $format
     * @return type
     */
    public static function getStringFormatted($str, $format = null)
    {
        if ($format == null) {
            return $str;
        }

        if ($format == 'CSV') {
            $str = str_ireplace('-', '_', $str);
            $str = Tools::strtolower(str_ireplace('  ', ' ', $str));
            $str = Tools::strtolower($str.'.csv');
            $str = preg_replace('/\s+/', '', $str);
        } elseif ($format == 'HtmlFieldName') {
            //
            $str = str_replace('&', ':$:', htmlentities($str, null, 'UTF-8'));
        } elseif ($format == 'HtmlFormField') {
            $str = htmlentities($str, null, 'UTF-8');
        } elseif ($format == 'decoded') {
            $str = html_entity_decode(str_replace(':$:', '&', $str), ENT_COMPAT, 'UTF-8');
        }

        return $str;
    }

    private static function isMandatoryField($field_name = null)
    {
        if ($field_name == null) {
            return false;
        }

        return false;
    }

    private static function isDisplayedField($field_name = null)
    {
        if (in_array($field_name, array('Genre', 'Type de public', 'Marque'))) {
            return false;
        }

        return true;
    }

    private static function getFieldType($field_name = null)
    {
        if ($field_name == 'Genre') {
            return 'gender';
        }

        return false;
    }

    // 2020-07-13: GetAllModelList is removed.

    /**
     * Extracts all modules list from Web Service response
     * @param string $xml_response
     * @return bool|SimpleXMLElement
     */
    public static function parseAllModelList($xml_response)
    {
        $pos = stripos($xml_response, '<s:Envelope');

        if ($pos) {
            $xml_response = substr($xml_response, $pos); //TODO: DO NOT SUBSTITUTE BY Tools::substr !!!

            $pos = stripos($xml_response, '</s:Envelope');

            if ($pos != false) {
                $xml = simplexml_load_string($xml_response);

                if ($xml instanceof SimpleXMLElement) {
                    $xml->registerXPathNamespace('m', 'http://www.cdiscount.com');
                    $xPath = '//s:Envelope/s:Body/m:GetAllModelListResponse/m:GetAllModelListResult/m:ModelList';
                    $productModels = $xml->xpath($xPath);

                    if ($productModels[0] instanceof SimpleXMLElement && isset($productModels[0]->ProductModel)) {
                        return $productModels[0];
                    } else {
                        self::printErrorMessage($xml);
                    }
                } else {
                    printf('%s/%d: API returned unexpected content: %s', basename(__FILE__), __LINE__, $xml_response);
                }
            } else {
                printf('%s/%d: API returned unexpected content: %s', basename(__FILE__), __LINE__, $xml_response);
            }
        } else {
            printf('%s/%d: API returned unexpected content: %s', basename(__FILE__), __LINE__, $xml_response);
        }

        return false;
    }

    /**
     * @param $xmlResponse
     * @return bool|SimpleXMLElement[]
     */
    public function parseModelList($xmlResponse)
    {
        $sanitizedXml = $this->sanityXmlResponse($xmlResponse);
        if ($sanitizedXml instanceof SimpleXMLElement) {
            $sanitizedXml->registerXPathNamespace('m', 'http://www.cdiscount.com');
            $xPath = '//s:Envelope/s:Body/m:GetModelListResponse/m:GetModelListResult/m:ModelList/m:ProductModel';
            return $sanitizedXml->xpath($xPath);
        }
        
        return false;
    }

    /**
     * @param $xmlResponse
     * @return bool|SimpleXMLElement
     */
    protected function sanityXmlResponse($xmlResponse)
    {
        $start = stripos($xmlResponse, '<s:Envelope');
        $end = stripos($xmlResponse, '</s:Envelope');
        if (false !== $start && false !== $end) {
            $xmlResponse = substr($xmlResponse, $start); //TODO: DO NOT SUBSTITUTE BY Tools::substr !!!
            $xml = simplexml_load_string($xmlResponse);
            if ($xml instanceof SimpleXMLElement) {
                return $xml;
            } else {
                $this->pdd('API returned unexpected content: ' . $xmlResponse, __LINE__);
            }
        }
        
        return false;
    }

    public static function printErrorMessage($xml)
    {
        $xml->registerXPathNamespace('mns', 'http://schemas.datacontract.org/2004/07/Cdiscount.Framework.Core.Communication.Messages');

        $xPath = '//*[mns:ErrorMessage or mns:ErrorList]';
        $xpath_result = $xml->xpath($xPath);

        if (is_array($xpath_result) && count($xpath_result)) {
            foreach ($xpath_result as $item) {
                if (isset($item->ErrorMessage)) {
                    CommonTools::p(sprintf('Error: %s', (string)$item->ErrorMessage));
                }
                if (isset($item->SellerLogin)) {
                    CommonTools::p(sprintf('SellerLogin: %s', (string)$item->SellerLogin));
                }
                if (isset($item->TokenId)) {
                    CommonTools::p(sprintf('TokenId: %s', (string)$item->TokenId));
                }

                if (isset($item->ErrorList)) {
                    foreach ($item->ErrorList->Error as $error_item) {
                        CommonTools::p(sprintf('Error - Type: %s Message: %s', (string)$error_item->ErrorType, (string)$error_item->Message));
                    }
                }
            }
        }
    }

    public static function getIdFeatureByTitle($id_product, $id_lang, $title)
    {
        $sql
            = '
            SELECT md5(fl.`name`) as md5, fl.`id_feature` as id_feature, fl.`name`, fvl.`value`, fp.id_feature_value FROM `'._DB_PREFIX_.'feature_product`  fp
            LEFT JOIN `'._DB_PREFIX_.'feature_value_lang` fvl on (fvl.id_feature_value = fp.id_feature_value and fvl.id_lang = '.(int)$id_lang.')
            LEFT JOIN `'._DB_PREFIX_.'feature_lang` fl on (fl.id_feature = fp.id_feature and fl.id_lang = '.(int)$id_lang.')
            WHERE fp.id_product =  '.(int)$id_product.'
            HAVING md5 = md5("'.pSQL($title).'")';

        $query = Db::getInstance()->ExecuteS($sql);

        if ($query) {
            return ($query[0]);
        } else {
            return (false);
        }
    }

    public function getModelListFriendly($categoryId)
    {
        $result = array();
        $modelListString = $this->GetModelList($categoryId);
        $productModels = $this->parseModelList($modelListString);

        if ($productModels && count($productModels)) {
            foreach ($productModels as $productModel) {
                if (isset($productModel->ModelId, $productModel->Name)) {
                    $result[] = array(
                        'id' => (string)$productModel->ModelId,
                        'name' => (string)$productModel->Name
                    );
                }
            }
        }

        return $result;
    }
    
    protected static $isVariation = array();
    public function isVariationModel($categoryId, $modelId)
    {
        if (!isset(self::$isVariation[$modelId])) {
            self::$isVariation[$modelId] = $this->checkModelVariation($categoryId, $modelId);
        }
        return self::$isVariation[$modelId];
    }

    /**
     * Function name is important
     * @param $categoryId
     * @return string
     */
    protected function GetModelList($categoryId)
    {
        $this->debugDetails->webservice("GetModelList of category: $categoryId");
        $modelListString = $this->getModelListCache($categoryId);

        if (!$modelListString) {
            $modelFilter =
                '<modelFilter xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                    <CategoryCodeList xmlns:a="http://schemas.microsoft.com/2003/10/Serialization/Arrays">
                        <a:string>' . $categoryId . '</a:string>
                    </CategoryCodeList>
                </modelFilter>';

            $modelListString = $this->_callAfterSetToken(__FUNCTION__, $modelFilter);
            if (!$modelListString) {
                $this->pdd('GetModelList API failed!', __LINE__, true);
                return '';
            }

            $this->saveModelListCache($categoryId, $modelListString);
        }

        return $modelListString;
    }

    /**
     * @param $categoryId
     * @return bool|string
     */
    protected function getModelListCache($categoryId)
    {
        $filePath = $this->modelListFilePath($categoryId);
        if (!file_exists($filePath) || (time() - filectime($filePath)) > self::VALID_TIME_OF_GET_MODEL_LIST_FILE) {
            return false;
        }

        return (CDiscountTools::file_get_contents('compress.zlib://' . $filePath));
    }

    /**
     * @param string $categoryId
     * @param string $xmlString
     */
    protected function saveModelListCache($categoryId, $xmlString)
    {
        $filePath = $this->modelListFilePath($categoryId);
        $xml = $this->sanityXmlResponse($xmlString);
        if ($xml instanceof SimpleXMLElement) {
            $xml->asXML('compress.zlib://' . $filePath);
        }
    }

    protected function modelListFilePath($categoryId)
    {
        return dirname(dirname(__FILE__)) . DS . Cdiscount::XML_DIRECTORY . DS . 'model' . DS . "GetModelList-$categoryId.xml.gz";
    }
}
