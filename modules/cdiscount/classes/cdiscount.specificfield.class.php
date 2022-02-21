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

require_once(dirname(__FILE__).'/cdiscount.model.class.php');

class CDiscountSpecificField extends CDiscount
{
    const MODEL_CSV = 0;
    const MODEL_XML = 1;
    const TITLE_FIELD = 'Champ';

    const TITLE_VALUE = 'Valeur par D&eacute;faut';

    const TITLE_OR = 'Et/Ou';
    const TITLE_FEATURE = 'Caract&eacute;ristique (Facultatif)';

    public static $features = null;

    public static $csvConfig = array(
        self::MODEL_CSV => array(
            'SellerProductId',
            'EAN',
            'BrandName',
            'ProductKind',
            'CategoryCode',
            'ShortLabel',
            'LongLabel',
            'Description',
            'Image1',
            'SellerProductFamily',
            'Size',
            'SellerProductColorName',
            'Model',
            'Image2',
            'Image3',
            'Image4',
            'Navigation',
            'ISBN',
            'MFPN',
            'Lenght',
            'Width',
            'Height',
            'Weight',
            'Capacity',
            'EncodedMarketingDescription',
        ),
        self::MODEL_XML => array(
            'SellerProductId',
            'EAN',
            'BrandName',
            'ProductKind',
            'CategoryCode',
            'ShortLabel',
            'LongLabel',
            'Description',
            'SellerProductFamily',
            'Size',
            'SellerProductColorName',
            'Model',
            'Navigation',
            'ISBN',
            'MFPN',
            'Lenght',
            'Width',
            'Height',
            'Weight',
            'Capacity',
            'EncodedMarketingDescription',
            'Image1',
            'Image2',
            'Image3',
            'Image4'
        )
    );
    public static $genericRequired = array(
            'SellerProductId',
            'EAN',
            'BrandName',
            'SellerProductId',
            'ProductKind',
            'CategoryCode',
            'ShortLabel',
            'LongLabel',
            'Description',
            'Navigation',
            'Model'
        );
    public static $variantRequired = array(
            'Size'
        );
    public static $fields = array();

    private static $instance;
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function displayFieldset($selected_id_model, $modelInternalId, $categoryId)
    {
        if (!$modelInternalId) {
            return '';
        }
        
        $cdModel = self::getInstanceCDiscountModel();
        $features = self::getFeatures();    // Load Features Once
        $html = '';

        $key = $modelInternalId;
        $content = self::getInstance()->getConfigurationByKey($key);
        $data = self::getFields($categoryId, $selected_id_model);

        if (!array_key_exists($selected_id_model, self::$validValuesByModelId)) {
            self::$validValuesByModelId[$selected_id_model] = $cdModel->getModelValuesByModelId($categoryId, $selected_id_model);
        }
        // todo: Old code use reference?
        $valid_values = self::$validValuesByModelId[$selected_id_model];

        if (!is_array($data) || !count($data)) {
            $html .= '<input type="hidden" name="specifics['.$key.']" />';
        } else {
            foreach ($data as $id_model => $fieldSets) {
                if ($selected_id_model != $id_model) {
                    continue;
                }

                if (!is_array($fieldSets) || !count($fieldSets)) {
                    $html .= '<input type="hidden" name="specifics['.$key.']" />';
                } else {
                    $html
                        .= '<table>
                        <tr class="specific-title">
                            <td>'.self::TITLE_FIELD.'</td>
                            <td>'.self::TITLE_VALUE.'</td>
                            <td style="text-align:center;width:80px">'.self::TITLE_OR.'</td>
                            <td>'.self::TITLE_FEATURE.'</td>
                            <td></td>
                        </tr>';
                    foreach ($fieldSets as $fieldSet) {
                        if (isset($fieldSet['display']) && $fieldSet['display'] == false) {
                            continue;
                        }

                        $attribute_key = self::getKey($fieldSet['xml_tag']);

                        if (isset($content[$attribute_key]['value'])) {
                            $value = $content[$attribute_key]['value'];
                        } else {
                            $value = null;
                        }

                        if (isset($fieldSet['description'])) {
                            $name = $fieldSet['description'];
                        } else {
                            $name = $fieldSet['name'];
                        }

                        if (isset($fieldSet['mandatory']) && $fieldSet['mandatory']) {
                            $mandatory = true;
                        } else {
                            $mandatory = false;
                        }

                        $cdiscount_attribute_key = self::getKey($fieldSet['xml_tag']);

                        if (array_key_exists($cdiscount_attribute_key, $valid_values) && array_key_exists('values', $valid_values[$cdiscount_attribute_key])) {
                            $html .= '<tr class="specific-row">';
                            $html .= '<td>'.$name.'</td>';
                            $html .= '<td>';
                            $html .= '<select name="specifics['.$key.']['.$attribute_key.'][value]">'."\n";
                            $html .= '<option value=""></option>'."\n";

                            foreach ($valid_values[$cdiscount_attribute_key]['values'] as $option) {
                                $match = Tools::strlen(self::getKey($option)) && self::getKey($value) == self::getKey($option);

                                $html .= '<option value="'.trim($option).'" '.($match ? 'selected' : '').'>'.$option.'</option>'."\n";
                            }

                            $html .= '</td>';
                            $html .= '<td></td>';
                        } else {
                            $html .= '<tr class="specific-row">';
                            $html .= '<td>'.$name.'</td>';
                            $html
                                .= '<td>
                                    <input type="text" value="'.$value.'" name="specifics['.$key.']['.$attribute_key.'][value]" size="'.$fieldSet['size'].'"/>
                                  </td>';
                            $html .= '<td></td>';
                        }

                        if (isset($content[$attribute_key]['feature'])) {
                            $id_existing_feature = $content[$attribute_key]['feature'];
                        } else {
                            $id_existing_feature = null;
                        }

                        $html .= '<td><select name="specifics['.$key.']['.$attribute_key.'][feature]" >
                                   <option></option>';
                        foreach ($features as $feature) {
                            if ($id_existing_feature == $feature['id_feature']) {
                                $selected = 'selected="selected"';
                            } else {
                                $selected = '';
                            }

                            $html
                                .= '
                                    <option value="'.$feature['id_feature'].'" '.$selected.'>'.$feature['name'].'</option>';
                        }
                        $html .= '</select></td>';

                        if ($mandatory) {
                            $html .= '<td class="mandatory">*</td>';
                        }
                        $html .= '</tr>';
                    }
                    $html .= '</tr></table>';

                    break;
                }
            }
        }

        return ($html);
    }
    
    protected static function getFeatures()
    {
        if (self::$features === null) {
            $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
            self::$features = Feature::getFeatures($id_lang);
        }
        
        return self::$features;
    }

    /**
     * @param null $key
     * @return array|mixed|string
     */
    public function getConfigurationByKey($key = null)
    {
        $configuration = $this->loadSpecificFields();
        if (empty($configuration) || !is_array($configuration)) {
            return array();
        } elseif (isset($key) && $key && isset($configuration[$key]) && is_array($configuration[$key])) {
            return $configuration[$key];
        } elseif ($configuration) {
            return $configuration;
        } else {
            return array();
        }
    }
    
    public static function getKey($name)
    {
        if ($name == null) {
            return;
        }

        return (CDiscountModel::toKey($name));
    }

    public static function getFields($categoryId, $modelId)
    {
        if (!isset(self::$fields[$modelId])) {
            self::$fields[$modelId] = self::getInstanceCDiscountModel()->getModelListFields($categoryId, $modelId);
        }

        return self::$fields;
    }
}
