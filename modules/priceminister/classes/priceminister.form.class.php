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

class PriceMinisterForm extends PriceMinister
{

    public static $excluded = array(
        'pid' => null,
        'codebarres' => array('combination_ean', 'combination_upc', 'ean'), /* upc */
        'submitterreference' => 'reference',
        'fabricant' => 'Manufacturer',
        'manufacturer' => 'manufacturer_name',
        'fabricant' => 'manufacturer_name',
//		'marque' => 'marque',
        'referencefabricant' => 'supplier_reference',
//        'referencefabricant' => 'reference', // Fix for ez-markt with duplicate product because of reference_fabricant. if no work then set to null
        'mpnVariant' => null,
        'eanVariant' => 'combination_ean',
        'privateComment' => 'private_comment',
        'prdEdito' => 'description',
        'XXX' => 'id_category_default',
        'state' => 'condition',
        'XXX' => 'Tag/Metatags/Keywords',
        'customizedAdvertDetail' => 'price_information',
        // 'customizedAdvertDetail' => 'description',
        'originalPrice' => 'price',
        'productsCampaignPrice' => null,
        'productsCampaignRefPrice' => null,
        'productsCampaignPct' => null,
        'sellingPrice' => 'final_price',
        'qty' => 'quantity',
        'image_url' => 'product_images',
        'comment' => 'comment',
        /* Familiar Fields
        'color' => '',
        'public' => Enfant / Adulte
        */
        'titre' => 'name',
        'title' => 'name',
        'aid' => 'XXX',
        'sellerReference' => 'combination_reference',
        'poids' => 'pm_weight'
    );
    private static $product_types = false;
    private static $product_type_templates = array();
    private static $keep_pm_structure = array('typedeproduit' => true, 'categorie' => true, 'poids' => true);
    private $_lists = array();

    /* inline-select
     * already defined items (excluded elements)
     * "XXX" = unmapped
     */

    public function __construct()
    {
        parent::__construct();
        $this->loadGeneralModuleConfig();
    }

    public function getProductTypes($selected = null, $return_xml = false, $cache_only = false)
    {
        $config = PriceMinisterTools::Auth();
        $p = new PriceMinisterApiProducts($config);
        $params = array();

        if ($cache_only) {
            $p_t = simplexml_load_file(dirname(__FILE__).'/../functions/cache_template.xml');
        } else {
            $p_t = $p->getProductTypes($params, $cache_only);
        }

        if ($return_xml) {
            return $p_t;
        }

        $product_type_options = array();

        if (isset($p_t->response) && isset($p_t->response->producttypetemplate)) {
            foreach ($p_t->response->producttypetemplate as $template) {
                $apt = array();
                $apt['value'] = (string)$template->alias;
                $apt['desc'] = PriceMinisterTools::encodeHtml((string)$template->label);
                $apt['selected'] = ($apt['value'] === $selected) ? 'selected' : '';
                $product_type_options[] = $apt;
            }
        }
        sort($product_type_options);

        return $product_type_options;
    }

    public function getProductTypeTemplate($alias, $valuesIncluded = false, $return_xml = false, $model_id = '_key_')
    {
        if (!$alias) {
            return '';
        }

        if ($valuesIncluded === false && count(self::$product_type_templates) > 1 && isset(self::$product_type_templates[$alias])) {
            return self::$product_type_templates[$alias];
        }

        //initialize values
        //////////////////////ID LANG IS HARD CODED- DEMO//////////////////////
        if (array_key_exists($this->id_lang, self::$attributes)) {
            $attributes = self::$attributes[$this->id_lang];
        } else {
            $attributes = array();
        }

        if (isset(self::$attributes_groups[$this->id_lang])) {
            foreach (self::$attributes_groups[$this->id_lang] as $ag) {
                self::$attributes_groups_options[$ag['id_attribute_group']] = $ag['name'];
            }
        }
        if (isset(self::$features[$this->id_lang])) {
            foreach (self::$features[$this->id_lang] as $f) {
                if (!isset($f['name'])) {
                    continue;
                }
                self::$features_options[$f['id_feature']] = $f['name'];
            }
        }

        $values = array();
        $product_type_template = array(
            'product',
            'advert',
            'media',
            'campaigns'
        );

        if ($valuesIncluded && is_array($valuesIncluded) && count($valuesIncluded) > 0) {
            $values = $valuesIncluded;
        }

        $config = PriceMinisterTools::Auth();
        $p = new PriceMinisterApiProducts($config);

        $params = array();
        $params['alias'] = $alias;
        $params['scope'] = 'VALUES';

        $p_t_t = $p->getProductModel($params);

        if ($return_xml) {
            return $p_t_t;
        }

        //Constant values, as these are not required by now
        $additionalStyle = '';
        $includeLabel = true;
        $removable_option = false;
        $html = '';

        $this->_lists = array();
        $this->context->smarty->assign('pm_module_path', $this->path);
        foreach ($product_type_template as $attr) {
            if ($attr != 'campaigns') {
                if ($attr == 'product') {
                    $headerTitle = $this->l('Product');
                } elseif ($attr == 'advert') {
                    $headerTitle = $this->l('Attributes');
                } else {
                    $headerTitle = $attr;
                }

                if (isset($p_t_t->response) && isset($p_t_t->response->attributes) && isset($p_t_t->response->attributes->$attr)) {
                    foreach ($p_t_t->response->attributes->$attr->attribute as $input_data) {
                        $key = (string)$input_data->key;
                        $value = (isset($values[$attr][$key])) ? $values[$attr][$key] : '';

                        if (array_key_exists($key, self::$excluded)) {
                            if (self::$excluded[$key] != null) {
                                $html .= $this->getInputFieldHtml($input_data, $value, 'models['.$model_id.']['.$attr.']');
                            }
                            continue;
                        }

                        $key_exceptions = array(
                            'personnage', 'auteur', 'editeur', 'nbPage', 'dateParution', 'longueur', 'largeur',
                            'hauteur', 'date'/*, 'hauteurCm', 'largeurCm'*/, 'frequenceduprocesseur', 'memoirevive',
                            'capacitedudisquedur', 'TailleEcran1', 'artistegroupe'
                        );

                        // Exception for key == personnage|auteur|editeur|nbPage|dateParution
                        if (!isset(self::$keep_pm_structure[$key]) && (int)$input_data->multivalued == 0 && (int)$input_data->hasvalues > 0 ||
                            in_array((string)$input_data->key, $key_exceptions) // revues // autocollant
                        ) {
                            $input_data->multivalued = 1;
                            $input_html = $this->getSelectAttrFeatHTML($input_data, (isset($values[$attr]) ? $values[$attr] : array()), 'models['.$model_id.']['.$attr.']', $key);
                        } else {
                            $input_html = $this->getInputFieldHtml($input_data, $value, 'models['.$model_id.']['.$attr.']');
                        }

                        if (isset($input_data->units) && isset($input_data->units->unit) && $input_data->units->unit) {
                            $this->context->smarty->assign('unit', array(
                                'index' => 'models['.$model_id.']['.$attr.']['.$key.'_unit]',
                                'value' => $input_data->units->unit
                            ));
                        } else {
                            $this->context->smarty->assign('unit', false);
                        }

                        $this->context->smarty->assign('additionalStyle', $additionalStyle);

                        if ($headerTitle) {
                            $this->context->smarty->assign('headerTitle', $headerTitle);
                        } else {
                            $this->context->smarty->assign('headerTitle', '');
                        }

                        $this->context->smarty->assign('input_html', $input_html);
                        $this->context->smarty->assign('includeLabel', $includeLabel);
                        $this->context->smarty->assign('label', '');
                        $this->context->smarty->assign('displayName', PriceMinisterTools::encodeHtml((string)$input_data->label));
                        $this->context->smarty->assign('removable_option', $removable_option);

                        if ((int)$input_data->multivalued) {
                            $this->context->smarty->assign('addicionalContainerClass', 'div-table');
                        }

                        $html .= $this->context->smarty->fetch($this->path.'views/templates/admin/pm_helpers/fieldgroup.tpl');
                        $headerTitle = false;
                    }
                }
            } else {
                if (isset($p_t_t->response) && isset($p_t_t->response->attributes) && isset($p_t_t->response->attributes->$attr)) {
                    foreach ($p_t_t->response->attributes->$attr->campaign as $campaign) {
                        $headerTitleCampaign = $this->l('Campaign');
                        $idx = 0;
                        foreach ($campaign->attribute as $input_data) {
                            $key = (string)$input_data->key;

                            $value = (isset($values[$attr][$key])
                                && is_array($values[$attr][$key])
                                && isset($values[$attr][$key][$idx])) ? $values[$attr][$key][$idx] : '';

                            if (array_key_exists($key, self::$excluded)) {
                                if (self::$excluded[$key] !== null) {
                                    $html .= $this->getInputFieldHtml($input_data, $value, 'models['.$model_id.'][campaigns]');
                                }
                                continue;
                            }

                            $input_html = $this->getInputFieldHtml($input_data, $value, 'models['.$model_id.'][campaigns]');
                            $this->context->smarty->assign('additionalStyle', $additionalStyle);
                            if ($headerTitleCampaign) {
                                $this->context->smarty->assign('headerTitle', $headerTitleCampaign);
                            } else {
                                $this->context->smarty->assign('headerTitle', '');
                            }
                            $this->context->smarty->assign('input_html', $input_html);
                            $this->context->smarty->assign('includeLabel', $includeLabel);
                            $this->context->smarty->assign('label', '');
                            $this->context->smarty->assign('displayName', PriceMinisterTools::encodeHtml((string)$input_data->label));
                            $this->context->smarty->assign('removable_option', $removable_option);

                            if ((int)$input_data->multivalued) {
                                $this->context->smarty->assign('addicionalContainerClass', ' div-table');
                            } else {
                                $this->context->smarty->assign('addicionalContainerClass', ' campaign');
                            }

                            $html .= $this->context->smarty->fetch($this->path.'views/templates/admin/pm_helpers/fieldgroup.tpl');
                            $headerTitleCampaign = false;
                        }
                        $idx++;
                    }
                }
            }
        }

        if ($valuesIncluded === false) {
            self::$product_type_templates[$alias] = $html;
        }

        //referesh cache of lists
        return $html;
    }

    public function l($string, $specific = false, $id_lang = null)
    {
        return parent::l($string, basename(__FILE__, '.php'), $id_lang);
    }

    private function getInputFieldHtml(SimpleXMLElement $input_data, $value, $prefix, $inline = false, $visible = true, $show_mandatory = false, $options_predefined = array(), $type = false)
    {
        $cssClass = '';
        $disabled = '';
        $additional_attributes = '';
        $html = '';
        $hidden = false;
        $suffix = (stripos($prefix, 'campaigns') !== false) ? '[]' : '';
        if (array_key_exists((string)$input_data->key, self::$excluded)) {
            if (self::$excluded[(string)$input_data->key] !== null) {
                $hidden = true;
            } // poids weight TODO
        }
        //Comparisons aremade using HTMLENTITIES
        if (!is_array($value)) {
            $value = PriceMinisterTools::encodeHtml($value);
        }

        $temp = array('label' => '',
            'key' => '',
            'mandatory' => false,
            'valuetype' => 'Text',
            'hasvalues' => false,
            'multivalued' => false,
            'valueslist' => array()
        );

        if (isset($input_data->label)) {
            $temp['label'] = PriceMinisterTools::encodeHtml((string)$input_data->label);
        }
        if (isset($input_data->key)) {
            $temp['key'] = PriceMinisterTools::encodeHtml((string)$input_data->key);
        }
        if (isset($input_data->mandatory)) {
            $temp['mandatory'] = (int)$input_data->mandatory;
        }
        if (isset($input_data->valuetype)) {
            $temp['valuetype'] = (string)$input_data->valuetype;
        }
        if (isset($input_data->hasvalues)) {
            $temp['hasvalues'] = (int)$input_data->hasvalues;
        }
        if (isset($input_data->multivalued)) {
            $temp['multivalued'] = (int)$input_data->multivalued;
        }
        if (isset($input_data->valueslist)) {
            if (count($options_predefined) == 0) {
                PriceMinister::saveList($temp['key'], $input_data);

                foreach ($input_data->valueslist->value as $option_val) {
                    $key = PriceMinisterTools::encodeHtml((string)$option_val); //to avoid problems of characters, when used this value must be decoded
                    $temp['valueslist'][$key] = $key;
                }
            } else {
                foreach ($options_predefined as $val => $desc) {
                    $key = PriceMinisterTools::encodeHtml($val); //to avoid problems of characters, when used this value must be decoded
                    $temp['valueslist'][$key] = PriceMinisterTools::encodeHtml($desc);
                }
            }
        }

        /*******************************************************************
         *                 inputSelectHTML template
         *******************************************************************/
        if (!$hidden && $temp['multivalued'] > 0 && count($temp['valueslist']) > 0) {
//            $this->_lists[$temp['key']] = $temp['key'];
//
//            foreach($temp['valueslist'] as $val=>$desc){
//                $checked = (is_array($value) && isset($value[$val])) ? ' checked' : '';
//                $this->context->smarty->assign('multiple_choices', true);
//                $this->context->smarty->assign('checkbox_label', $desc);
//                $this->context->smarty->assign('checkbox_id', $prefix . '[' . $temp['key'] . "][" . $val .']' . $suffix);
//                $this->context->smarty->assign('checkbox_cssClass', $cssClass);
//                $this->context->smarty->assign('checkbox_checked', $checked);
//                $this->context->smarty->assign('checkbox_disabled', $disabled);
//                $html .= $this->context->smarty->fetch($this->path . 'views/templates/admin/pm_helpers/checkbox.tpl');
//            }
            $select_multiple = '';
            $select_template = 'inputselect.tpl';

            if ($inline) {
                $cssClass = ' class="inline-element col-lg-3" ';
            }

            if (!$visible) {
                $cssClass .= ' style="display:none ;" ';
            } else {
                $cssClass = ' class="inline-element main col-lg-3" ';
            }
            $this->_lists[$temp['key']] = $temp['key'];

            $this->context->smarty->assign('select_id', $prefix.'['.$temp['key'].']'.$suffix);
            $this->context->smarty->assign('select_cssClass', $cssClass);
            $this->context->smarty->assign('select_disabled', $disabled);
            $this->context->smarty->assign('select_mandatory', $show_mandatory && $temp['mandatory'] ? true : false);
            $this->context->smarty->assign('select_style', 'width: 200px;');

            $this->context->smarty->assign('select_multiple', $select_multiple);
            $this->context->smarty->assign('select_selected_non', ((string)$value ? '' : ' selected '));

            $options = array();
            foreach ($temp['valueslist'] as $val => $desc) {
                $option = array();
                $selected = ((string)$value == (string)$val) ? ' selected ' : '';
                $option['value'] = $val;
                $option['selected'] = $selected;
                $option['desc'] = $desc;
                $options[] = $option;
            }
            $this->context->smarty->assign('select_options', $options);

            $html .= $this->context->smarty->fetch($this->path.'views/templates/admin/pm_helpers/'.$select_template);
        } elseif (!$hidden && $temp['hasvalues']) {
            $select_multiple = '';
            $select_template = 'inputselect.tpl';
            $class_type = '';

            switch ($type) {
                case PriceMinister::ATTRIBUTE:
                    $class_type = 'pmAttribute';
                    break;
                case PriceMinister::FEATURE:
                    $class_type = 'pmFeature';
                    break;
                default:
                    break;
            }

            if ($inline) {
                $cssClass = ' class="inline-element col-lg-3 '.$class_type.'" ';
            }

            if (!$visible) {
                $cssClass .= ' style="display:none ;" ';
            } else {
                $cssClass = ' class="inline-element main col-lg-3 '.$class_type.'" ';
            }

            $this->_lists[$temp['key']] = $temp['key'];

            $this->context->smarty->assign('select_id', $prefix.'['.$temp['key'].']'.$suffix);
            $this->context->smarty->assign('select_cssClass', $cssClass);
            $this->context->smarty->assign('select_disabled', $disabled);
            $this->context->smarty->assign('select_mandatory', $visible ? $temp['mandatory'] : null);

            $this->context->smarty->assign('select_multiple', $select_multiple);
            $this->context->smarty->assign('select_selected_non', ((string)$value ? '' : ' selected '));

            $options = array();
            foreach ($temp['valueslist'] as $val => $desc) {
                $option = array();
                $selected = ((string)$value == (string)$val) ? ' selected ' : '';
                $option['value'] = $val;
                $option['selected'] = $selected;
                $option['desc'] = $desc;
                $options[] = $option;
            }

            $this->context->smarty->assign('select_options', $options);

            $html .= $this->context->smarty->fetch($this->path.'views/templates/admin/pm_helpers/'.$select_template);
        } elseif (!$hidden && $temp['valuetype'] == 'Boolean') {
            /*******************************************************************
             *                 inputCheckboxHTML template
             *******************************************************************/

            $checked = ($value) ? ' checked' : '';
            $this->context->smarty->assign('checkbox_id', $prefix.'['.$temp['key'].']'.$suffix);
            $this->context->smarty->assign('checkbox_cssClass', $cssClass);
            $this->context->smarty->assign('checkbox_checked', $checked);
            $this->context->smarty->assign('checkbox_disabled', $disabled);
            $html .= $this->context->smarty->fetch($this->path.'views/templates/admin/pm_helpers/checkbox.tpl');
        } else {
            //------------TEXT------------//
            /*******************************************************************
             *                 inputTextHTML template
             *******************************************************************/
            if (!$visible) {
                $cssClass .= ' style="display:none ;" ';
            }

            $this->context->smarty->assign('text_id', $prefix.'['.$temp['key'].']'.$suffix);
            $this->context->smarty->assign('text_value', $value);
            $this->context->smarty->assign('text_additional_attributes', $additional_attributes);
            $this->context->smarty->assign('text_cssClass', $cssClass);
            $this->context->smarty->assign('text_disabled', $disabled);

            if (!$hidden) {
                $html .= $this->context->smarty->fetch($this->path.'views/templates/admin/pm_helpers/inputtext.tpl');
            } else {
                $html .= $this->context->smarty->fetch($this->path.'views/templates/admin/pm_helpers/inputhidden.tpl');
            }
        }

        return $html;
    }

    private function getSelectAttrFeatHTML(SimpleXMLElement $input_data, $values, $prefix, $key)
    {
        if (!is_array($values)) {
            $values = ($values !== null) ? array($values) : array();
        }

        $main_value = isset($values[$key]) ? (string)$values[$key] : '';
        $html3 = $this->getInputFieldHtml($input_data, $main_value, $prefix, true, false, false/*true*/);
        $clon0 = $clon1 = $clon2 = $clon3 = clone $input_data;
        ///////OPTION
        $id = $key.'_opt';
        $clon0->label = '';
        $clon0->key = $id;
        unset($clon0->valueslist->value);
        $clon0->valueslist = true;
        $options = array();
        $options[self::ATTRIBUTE] = $this->l('Prestashop Attribute');
        $options[self::FEATURE] = $this->l('Prestashop Feature');
        $options[self::PM] = $this->l('Fixed Value');

        $value0 = isset($values[$id]) ? (string)$values[$id] : '';
        $clon_prefix0 = str_replace($key, $id, $prefix);
        $html0 = $this->getInputFieldHtml($clon0, $value0, $clon_prefix0, true, true, true, $options);

        ///////ATTRIBUTE GROUP VALUE
        $id = $key.'_attr';
        $clon1->label = '';
        $clon1->key = $id;
        unset($clon1->valueslist->value);
        $clon1->valueslist = true;
        $options = array();

        foreach (self::$attributes_groups_options as $i => $v) {
            $options[$i] = $v;
        }

        $value1 = isset($values[$id]) ? (string)$values[$id] : '';
        $clon_prefix1 = str_replace($key, $id, $prefix);
        $html1 = $this->getInputFieldHtml($clon1, $value1, $clon_prefix1, true, false, false/*true*/, $options, PriceMinister::ATTRIBUTE);

        ///////FEATURE VALUE
        $clon2->label = '';
        $id = $key.'_feat';
        $clon2->key = $id;
        unset($clon2->valueslist->value);
        $options = array();
        foreach (self::$features_options as $i => $v) {
            $options[$i] = $v;
        }
        $value2 = isset($values[$id]) ? (string)$values[$id] : '';
        $clon_prefix2 = str_replace($key, $id, $prefix);
        $html2 = $this->getInputFieldHtml($clon2, $value2, $clon_prefix2, true, false, false, $options, PriceMinister::FEATURE);

        ///////DEFAULT VALUE
        //if feature value and attribute value == "", then default element is hidden
        $hide_default = ($value2 == '' && $value1 == '');

        $input_data2 = clone $input_data;

        $input_data2->label = ' Default';
        $id = $key.'_def';
        $input_data2->key = $id;
        $value3 = ((!$hide_default) && isset($values[$id])) ? (string)$values[$id] : '';
        $clon_prefix3 = str_replace($key, $id, $prefix);

        $labelTemplate = $this->context->smarty->createTemplate($this->path.'views/templates/admin/pm_helpers/label.tpl');
        $labelTemplate->assign('forItem', '');
        $labelTemplate->assign('classItem', 'pmDefaultLabel');
        $labelTemplate->assign('label_style', 'float:left;');
        $labelTemplate->assign('labelItem', (string)$input_data2->label);

        $html4_label = $labelTemplate->fetch();

        $input_data2->mandatory = false;
        $html4 = '<div '.($hide_default ? 'style="display:none;" ' : 'style="float: right;"').'>'.
            $html4_label.$this->getInputFieldHtml($input_data2, $value3, $clon_prefix3, true, true, false)
            .'</div>';

        //cache for lists
        if (!isset($this->config['pm_lists'][$key])) {
            $pm_options = array();
            if (isset($input_data->valueslist) && isset($input_data->valueslist->value)) {
                foreach ($input_data->valueslist->value as $opt_value) {
                    $pm_options[] = (string)$opt_value;
                }
            }
            $this->config['pm_lists'] = $pm_options;
        }

        return $html0.$html1.$html2.$html3.$html4;
    }

    private function is_ignored($k)
    {
        return
            (Tools::strlen($k) >= 4 && strripos($k, '_opt', -3) !== false) ||
            (Tools::strlen($k) >= 4 && strripos($k, '_def', -3) !== false) ||
            (Tools::strlen($k) >= 5 && strripos($k, '_attr', -4) !== false) ||
            (Tools::strlen($k) >= 5 && strripos($k, '_feat', -4) !== false);
    }
}
