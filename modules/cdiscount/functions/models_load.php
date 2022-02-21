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

require_once(dirname(__FILE__).'/env.php');
require_once(dirname(__FILE__).'/../cdiscount.php');

require_once(dirname(__FILE__).'/../classes/cdiscount.tools.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.model.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.context.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.specificfield.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.support.class.php');
require_once(dirname(__FILE__).'/../classes/cdiscount.categories.class.php');

require_once(dirname(__FILE__).'/../includes/cdiscount.model.php');

class CDiscountModelLoad extends CDiscount
{
    const LF = "\n";

    public function __construct()
    {
        parent::__construct();
        CDiscountContext::restore($this->context, null, Cdiscount::$debug_mode);
    }

    public function dispatch()
    {
        $idLang = Tools::getValue('id_lang');   // Already did in Cdiscount::constructor
        if ($idLang) {
            $this->context->language = new Language($this->id_lang);
        }

        switch (Tools::getValue('action')) {
            case 'load_saved_model':
                $modelInternalId = Tools::getValue('modelInternalId');
                $response = $this->loadSavedModel($modelInternalId);
                break;
            case 'u2c':
                $universe = Tools::getValue('universe');
                $modelInternalId = Tools::getValue('modelInternalId');
                $response = $this->universe2Category($universe, $modelInternalId);
                break;
            case 'models_by_category':
                $categoryId = Tools::getValue('category_id');
                $modelInternalId = Tools::getValue('modelInternalId');
                $response = $this->loadModelsByCategory($categoryId, $modelInternalId);
                break;
            case 'model_public_and_gender_and_variant':
                $categoryId = Tools::getValue('category_id');
                $modelId = Tools::getValue('model_id');
                $modelInternalId = Tools::getValue('modelInternalId');
                $response = $this->loadPublicAndGenderAndVariant($categoryId, $modelId, $modelInternalId);
                break;
            default:
                $response = array();
                break;
        }

        $callback = Tools::getValue('callback', 'jsonp_' . time());
        die((string)$callback . '(' . json_encode($response) . ')');
    }

    // 2020-07-13: GetAllModelList is removed

    public function l($string, $specific = false, $id_lang = null)
    {
        return (parent::l($string, basename(__FILE__, '.php'), $id_lang));
    }

    protected function loadSavedModel($modelInternalId)
    {
        CDiscountCategories::setPath();
        $models = $this->loadModels();
        if (!isset($models[$modelInternalId])) {
            return array('error' => true, 'tpl' => '', 'reason' => $this->pdr('Model not exist!'));
        }

        $model = new CDiscountModuleModel($modelInternalId, $models[$modelInternalId]);

        $support = new CDiscountSupport($this->id_lang);
        $this->loadAttributes();
        $psAttributesGroups = Cdiscount::$attributes_groups;
        $psAttributes = Cdiscount::$attributes;
        $features = Cdiscount::$features;

        $tpl = $this->context->smarty->assign(array_merge($this->viewParamsAlertClass(), array(
            'module_path' => $this->path,
            'images_url' => $this->images,
            'alert_class' => $this->viewParamsAlertClass(),
            'universe_options' => $this->getUniverseOptions(),
            'category_options' => $this->getCategoryOptions($model->universe),
            'model_options' => $this->getModelOptions($model->categoryId),
            'public_options' => $this->getPublicOptions($model->categoryId, $model->modelId),
            'gender_options' => $this->getGenderOptions($model->categoryId, $model->modelId),
            'support_language' => $support->lang,
            'expert_mode' => Configuration::get(self::KEY . '_EXPERT_MODE'),
            'attribute_options' => $this->_attributeOptions($psAttributes, $psAttributesGroups),
            'feature_size_options' => $this->_featureOptions($features, false),
            'feature_color_options' => $this->_featureOptions($features, true),
            'specific_fields' => CDiscountSpecificField::displayFieldset($model->modelId, $modelInternalId, $model->categoryId),
            'model_data' => $model,
        )))->fetch(_PS_MODULE_DIR_ . $this->name . DS . 'views/templates/admin/configure/model/model_normal.tpl');
        
        return array('error' => !$tpl, 'tpl' => $tpl, 'reason' => !$tpl ? 'Empty template!' : '');
    }

    protected function universe2Category($universe, $modelInternalId)
    {
        CDiscountCategories::setPath();

        if (empty($universe) || !$this->LoadAllowedCategoryTree()) {
            return array('error' => true, 'tpl' => '', 'reason' => 'Failed to load category from universe!');
        }

        $tpl = $this->context->smarty->assign(array(
            'images_url' => $this->images,
            'alert_class' => $this->viewParamsAlertClass(),
            'category_options' => $this->getCategoryOptions($universe),
            'model_data' => new CDiscountModuleModel($modelInternalId, array('universe' => $universe)),
        ))->fetch(_PS_MODULE_DIR_ . $this->name . DS . 'views/templates/admin/configure/model/model_category.tpl');

        return array('error' => !$tpl, 'tpl' => $tpl, 'reason' => !$tpl ? 'Empty template!' : '');
    }

    protected function loadModelsByCategory($categoryId, $modelInternalId)
    {
        $tpl = $this->context->smarty->assign(array(
            'model_options' => $this->getModelOptions($categoryId),
            'model_data' => new CDiscountModuleModel($modelInternalId, array('categoryId' => $categoryId)),
        ))->fetch(_PS_MODULE_DIR_ . $this->name . DS . 'views/templates/admin/configure/model/model_model.tpl');

        return array('error' => !$tpl, 'tpl' => $tpl, 'reason' => !$tpl ? 'Empty template!' : '');
    }

    protected function loadPublicAndGenderAndVariant($categoryId, $modelId, $modelInternalId)
    {
        if (!$categoryId || !$modelId) {
            return array('error' => true, 'tpl' => '', 'reason' => 'Empty category or model!');
        } else {
            $modelDataAsArray = array('categoryId' => $categoryId, 'modelId' => $modelId);
            $this->context->smarty->assign(array_merge(
                $this->_loadPublishAndGender($categoryId, $modelId),
                $this->_loadVariant(),
                array(
                    'images_url' => $this->images,
                    'model_data' => new CDiscountModuleModel($modelInternalId, $modelDataAsArray),
                )
            ));

            $templatePath = _PS_MODULE_DIR_ . $this->name . DS . 'views/templates/admin/configure/model/';
            $tpl = $this->context->smarty->fetch($templatePath . 'model_public_gender.tpl')
                . $this->context->smarty->fetch($templatePath . 'model_variant.tpl');
        }

        return array('error' => !$tpl, 'tpl' => $tpl, 'reason' => !$tpl ? 'Empty template!' : '');
    }

    private function getPublicOptions($categoryId, $modelId)
    {
        $publicOptions = array();
        if (!$categoryId || !$modelId) {
            return $publicOptions;
        }
        
        $publicValues = $this->getInstanceCDiscountModel()->getModelPublicValues($categoryId, $modelId);
        if (count($publicValues)) {
            foreach ($publicValues as $public) {
                $publicOptions[] = array(
                    'value' => $public,
                    'desc' => html_entity_decode($public, ENT_COMPAT, 'UTF-8')
                );
            }
        }

        return $publicOptions;
    }

    private function getGenderOptions($categoryId, $modelId)
    {
        $genderOptions = array();
        if (!$categoryId || !$modelId) {
            return $genderOptions;
        }
        
        $genderValues = $this->getInstanceCDiscountModel()->getModelGenderValues($categoryId, $modelId);
        if (count($genderValues)) {
            foreach ($genderValues as $gender) {
                $genderOptions[] = array(
                    'value' => $gender,
                    'desc' => html_entity_decode($gender, ENT_COMPAT, 'UTF-8'),
                );
            }
        }

        return $genderOptions;
    }

    private function _loadPublishAndGender($categoryId, $modelId)
    {
        return array(
            'public_options' => $this->getPublicOptions($categoryId, $modelId),
            'gender_options' => $this->getGenderOptions($categoryId, $modelId),
        );
    }

    private function _loadVariant()
    {
        $support = new CDiscountSupport($this->id_lang);

        $this->loadAttributes();
        $psAttributesGroups = Cdiscount::$attributes_groups;
        $psAttributes = Cdiscount::$attributes;
        $features = Cdiscount::$features;

        return array(
            'support_language' => $support->lang,
            'expert_mode' => Configuration::get(self::KEY.'_EXPERT_MODE'),
            'attribute_options' => $this->_attributeOptions($psAttributes, $psAttributesGroups),
            'feature_size_options' => $this->_featureOptions($features, false),
            'feature_color_options' => $this->_featureOptions($features, true),
        );
    }

    private function _attributeOptions($psAttributes, $psAttributesGroups)
    {
        $result = array();
        if ($psAttributes) {
            foreach ($psAttributes as $idAttr => $group) {
                if ($idAttr) {
                    foreach ($psAttributesGroups as $attributes_group) {
                        if (isset($attributes_group['id_attribute_group']) && $attributes_group['id_attribute_group'] == $idAttr) {
                            $result[] = array('value' => $idAttr, 'desc' => $attributes_group['name']);
                            break;
                        }
                    }
                }
            }
        }

        return $result;
    }

    private function _featureOptions($features, $uniqueResult)
    {
        $result = array();
        $unique = array();

        foreach ($features as $feature) {
            $id_feature = $feature['id_feature'];
            if (!$uniqueResult || !isset($unique[$id_feature])) {
                $result[] = array('value' => $id_feature, 'desc' => $feature['name']);
            }
            $unique[$id_feature] = true;
        }

        return $result;
    }

    /**
     * todo: Migrate from categories_load.php. Move it dependent first getCategoryOptions(). Could make this child of CDiscountCategoriesLoad?
     * @return false|mixed|SimpleXMLElement|string
     * @throws PrestaShopException
     */
    private function loadAllowedCategoryTree()
    {
        $force_load = false;
        $username = Configuration::get(parent::KEY.'_USERNAME');
        $password = Configuration::get(parent::KEY.'_PASSWORD');
        $production = !(Configuration::get(parent::KEY.'_PREPRODUCTION') ? true : false);

        $marketplace = new CDiscountCategories($username, $password, $production, Cdiscount::$debug_mode);
        $marketplace->token = CDiscountTools::auth();

        $filecheck = CDiscountCategories::getAllowedCategoriesTimestamp();

        if (Cdiscount::$debug_mode) {
            printf('%s/%s: Timestamp: %d - %s'.self::LF, basename(__FILE__), __LINE__, $filecheck, CommonTools::displayDate(date('Y-m-d H:i:s', $filecheck), $this->id_lang));
        }

        if (!$filecheck || $filecheck < (time() - 86400 * 15) || (bool)Tools::getValue('force')) {
            if ($this->ps16x) {
                $class_error = 'alert alert-danger';
            } else {
                $class_error = parent::MODULE.'-error';
            }

            if (!$marketplace->token) {
                die('<div class="'.$class_error.'">'.$this->l('Authentication process has failed').' '.parent::NAME.'</div>');
            }

            if (Cdiscount::$debug_mode) {
                printf('%s/%s: Load through Web/Service'.self::LF, basename(__FILE__), __LINE__);
            }

            $result = $marketplace->GetAllowedCategoryTree();

            if ($result instanceof SimpleXMLElement && isset($result->CategoryTree)) {
                // Fix trailing whitespace issue
                $output = str_replace(' </Name>', '</Name>', $result->asXML());

                if (!file_put_contents('compress.zlib://'.CDiscountCategories::$allowed_category_file, $output)) {
                    if (Cdiscount::$debug_mode) {
                        printf('%s/%s: LoadAllowedCategoryTree() failed'.self::LF, basename(__FILE__), __LINE__);
                    }

                    $force_load = true;
                }
            } else {
                $force_load = true;
            }
        } else {
            $force_load = true;
        }

        if ($force_load) {
            if (Cdiscount::$debug_mode) {
                printf('%s/%s: Load from File: %s'.self::LF, basename(__FILE__), __LINE__, CDiscountCategories::$allowed_category_file);
            }

            if (!($result = simplexml_load_file('compress.zlib://'.CDiscountCategories::$allowed_category_file))) {
                if (Cdiscount::$debug_mode) {
                    printf('%s/%s: LoadAlowedlCategoryTree() failed'.self::LF, basename(__FILE__), __LINE__);
                }

                return (false);
            }
        }

        return ($result);
    }

    private function getCategoryOptions($universe)
    {
        $options = array();
        if (!$universe) {
            return $options;
        }
        
        $categories_array = CDiscountCategories::universeToCategories($universe);

        if (is_array($categories_array) && count($categories_array)) {
            asort($categories_array);
            $options[] = array('value' => '', 'desc' => '');
            foreach ($categories_array as $code => $categoryName) {
                $options[] = array('value' => $code, 'desc' => $categoryName);
            }
        } else {
            $options[] = array('value' => '', 'desc' => parent::l('No categories available'));
        }

        return $options;
    }

    private function getModelOptions($categoryId)
    {
        $options = array();
        if (!$categoryId) {
            return $options;
        }
        
        $models = $this->getInstanceCDiscountModel()->getModelListFriendly($categoryId);
        foreach ($models as $model) {
            $options[] = array('value' => $model['id'], 'desc' => $model['name']);
        }

        return $options;
    }

    public function pdr()
    {
        $backTrace = debug_backtrace();
        $caller = array_shift($backTrace);
        $fileSegment = explode('/', $caller['file']);
        $file = array_pop($fileSegment);
        $output = '';

        foreach (func_get_args() as $arg) {
            $output .= CommonTools::p(sprintf('%s(#%d): %s', $file, $caller['line'], $arg));
        }
        
        return $output;
    }
}

$marketplaceModelLoad = new CDiscountModelLoad();
$marketplaceModelLoad->dispatch();
