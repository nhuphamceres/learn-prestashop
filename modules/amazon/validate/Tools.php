<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Feed.biz
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Feed.biz is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Feed.biz.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 *
 * @author    Erick Turcios
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

class AmazonXSDTools
{
    public static function searchPath($product_instance, $searched_element, $reference_element, $casesensitive = true, $exclude = null)
    {
        if ($reference_element && Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s:#%d searchPath - searched element: %s reference element: %s'."\n", basename(__FILE__), __LINE__, print_r($searched_element, true), print_r($reference_element, true)));
        }
        $path = AmazonXSD::getPathToElement($product_instance, $searched_element, trim($reference_element), true, null, $casesensitive, $exclude = null);

        if ($path && Amazon::$debug_mode) {
            CommonTools::p(sprintf('%s:#%d path: %s'."\n", basename(__FILE__), __LINE__, print_r($path, true)));
        }

        if ($path) {
            $refElements = explode('->', $path);
            $product = array();
            $i = 0;
            foreach ($refElements as $key => $val) {
                if ($i++ == 0) {
                    continue;
                }
                $product[] = $val;
            }

            return ($product);
        } elseif ($reference_element) {
            return (self::searchPath($product_instance, $searched_element, null, $casesensitive));
        }

        return (null);
    }

    public static function parseVariationData($product_instance, $universe, $productType)
    {
        static $p = null;
        $variation = array();

        if ($product_instance == null && !isset($p[$universe])) {
            $productFactory = new AmazonXSD($universe.'.xsd');

            if (!($product_instance = $productFactory->getInstance())) {
                return (false);
            }

            $p[$universe] = $product_instance;
        } elseif ($product_instance == null && isset($p[$universe])) {
            $product_instance = $p[$universe];
        } elseif (!$product_instance) {
            return (null);
        }

        if (in_array($universe, array('Sports'))) {
            $targetTag = $universe;
        } elseif (in_array($universe, array('ClothingAccessories', 'Shoes'))) {
            $targetTag = $universe;
        } elseif ($universe == 'ProductClothing') {
            $targetTag = 'Clothing';
        } elseif ($universe == 'CE') {
            $targetTag = 'ConsumerElectronics';
        } elseif ($universe == 'Toys') {
            $targetTag = 'Toys';
        } else {
            $targetTag = $productType;
        }

        $path = AmazonXSD::getPathToElement($product_instance, 'VariationData', $targetTag);

        if ($path) {
            $path .= '->VariationTheme->allowedValues';
            $refElements = explode('->', $path);
            $targetElement = $product_instance;
            $variationData = null;

            if (is_array($refElements) && count($refElements)) {
                foreach ($refElements as $refElement) {
                    $targetElement = &$targetElement->$refElement;

                    if ($refElement == 'VariationData') {
                        $variationData = &$targetElement;
                    }
                }
                if ($targetElement != $product_instance && is_array($targetElement)) {
                    foreach ($targetElement as $variationThemeItem) {
                        // Size/Color exception
                        $variationThemeItem = str_ireplace(
                            array(
                                'SizeColor', 
                                'ColorSize',
                                'Size_name', 
                                'Sizename',
                                'Color_name',
                                'Colorname'), 
                            array(
                                'Size-Color',
                                'Color-Size',
                                'Size',
                                'Size',
                                'Color',
                                'Color'
                            ), $variationThemeItem);
                        
                        $variationThemeItemRef = $variationThemeItem;
                        
                        if (Tools::strtoupper($variationThemeItem) == $variationThemeItem) {
                            $variationThemeItemRef = ucwords(Tools::strtolower($variationThemeItem));
                        } elseif (Tools::strtolower($variationThemeItem) == $variationThemeItem) {
                            $variationThemeItemRef = ucwords(Tools::strtolower($variationThemeItem));
                        }

                        // Set of items ex: Color-ColorSize, Cupsize-Color-Size
                        if (strpos($variationThemeItemRef, '-')) {
                            $variationFields = explode('-', $variationThemeItemRef);

                            $variation[$variationThemeItem]['fields'] = $variationFields;
                        } else {
                            $variation[$variationThemeItem]['fields'] = array($variationThemeItemRef);
                        }

                        foreach ($variation[$variationThemeItem]['fields'] as $variationField) {
                            if (Tools::strtoupper($variationField) == $variationField) {
                                $variationField = AmazonTools::ucfirst(Tools::strtolower($variationField));
                            } elseif (Tools::strtolower($variationField) == $variationField) {
                                $variationField = AmazonTools::ucfirst(Tools::strtolower($variationField));
                            }

                            $attributesFound = false;
                            
                            // Variation Fields have Attributes
                            if ($variationData instanceof stdClass && property_exists($variationData, $variationField) && property_exists($variationData->{$variationField}, 'attr')) {
                                if (property_exists($variationData->{$variationField}->attr, 'unitOfMeasure') && property_exists($variationData->{$variationField}->attr->unitOfMeasure, 'allowedValues')) {
                                    $variation[$variationThemeItem]['attributes'][$variationField] = AmazonTools::encode(serialize($variationData->{$variationField}->attr->unitOfMeasure->allowedValues));
                                    $attributesFound = true;
                                }
                            } 
                            
                            if(!$attributesFound && $product_instance instanceof stdClass && isset($product_instance->ProductData->{$universe})) {
                                $productAttrs = $product_instance->ProductData->{$universe};

                                if (property_exists($productAttrs, $variationField) && property_exists($productAttrs->{$variationField}, 'attr') && property_exists($productAttrs->{$variationField}->attr, 'unitOfMeasure') && property_exists($productAttrs->{$variationField}->attr->unitOfMeasure, 'allowedValues')) {
                                    $variation[$variationThemeItem]['attributes'][$variationField] = AmazonTools::encode(serialize($productAttrs->{$variationField}->attr->unitOfMeasure->allowedValues));
                                    $attributesFound = true;
                                }
                            }
                            
                            if(!$attributesFound && $product_instance instanceof stdClass && isset($product_instance->ProductData->{$universe}->ProductType->{$productType})) {
                                $productAttrs = $product_instance->ProductData->{$universe}->ProductType->{$productType};

                                if (property_exists($productAttrs, $variationField) && property_exists($productAttrs->{$variationField}, 'attr') && property_exists($productAttrs->{$variationField}->attr, 'unitOfMeasure') && property_exists($productAttrs->{$variationField}->attr->unitOfMeasure, 'allowedValues')) {
                                    $variation[$variationThemeItem]['attributes'][$variationField] = AmazonTools::encode(serialize($productAttrs->{$variationField}->attr->unitOfMeasure->allowedValues));
                                    $attributesFound = true;
                                }
                            }
                        }
                    }
                }
            }
        }
        ksort($variation, SORT_NATURAL | SORT_FLAG_CASE);
        return ($variation);
    }
}
