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
 * @author    Tran Pham
 * @copyright Copyright (c) 2011-2020 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   CDiscount
 * Support by mail:  support.cdiscount@common-services.com
 */

require_once(dirname(__FILE__) . '/cdiscount.model.php');

// todo: Move to sub dir /configuration
class CDiscountAdminConfigure
{
    /** @var Cdiscount */
    public $module;

    /** @var Context */
    public $context;

    public function __construct($module, $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    /** @var CDiscountModel */
    protected $cdModel;

    public function tabProfiles()
    {
        $view_params = array();

        $profiles = $this->module->loadProfiles();

        if (CDiscountCategories::isLoaded()) {
            $current_currency = Currency::getDefaultCurrency();
            $view_params['multitenants'] = $this->getMultiTenants();

            // Dummy profile, used for cloning
            $profiles['name'][65635] = null;
            $profiles['master'][65635] = true;
            $profiles['model'][65635] = null;
            $profiles['type'][65635] = null;
            $profiles['formula'][65635] = null;
            $profiles['price_align'][65635] = null;
            $profiles['shipping_rule'][65635] = null;
            $profiles['preparation_time'][65635] = null;
            $profiles['multitenant'][65635] = null;

            $view_params['profiles_data'] = array();

            // Use same model list for all profiles
            $models = $this->module->loadModels();
            $modelOptions = array();
            foreach ($models as $modelInternalId => $modelData) {
                $modelOptions[] = array('value' => $modelInternalId, 'desc' => $modelData['name']);
            }
            $view_params['model_options'] = $modelOptions;

            foreach ($profiles['name'] as $profile_id => $profile_name) {
                // Skip empty entries
                if (empty($profile_name) && (!isset($profiles['master'][$profile_id]) || !$profiles['master'][$profile_id])) {
                    continue;
                }

                // 1. General profile data
                if ($profile_name === null) {
                    $profile_class = 'master';
                    $profile_div = 'id="master-profile" style="display:none"';
                } else {
                    $profile_class = 'stored-profile';
                    $profile_div = 'id="profile-' . $profile_id . '"';
                }
                $profile_data = array(
                    'profile_div' => $profile_div,
                    'profile_id' => $profile_id,
                    'profile_class' => $profile_class,
                    'name' => isset($profiles['name'][$profile_id]) ? $profiles['name'][$profile_id] : '',
                    'model' => isset($profiles['model'][$profile_id]) ? $profiles['model'][$profile_id] : '',
                    'type' => isset($profiles['type'][$profile_id]) ? $profiles['type'][$profile_id] : '',
                    'price_align' => isset($profiles['price_align'][$profile_id]) ? $profiles['price_align'][$profile_id] : '',
                    'formula' => isset($profiles['formula'][$profile_id]) ? $profiles['formula'][$profile_id] : '',
                    'shipping_rule' => isset($profiles['shipping_rule'][$profile_id]) ? $profiles['shipping_rule'][$profile_id] : '',
                    'preparation_time' => isset($profiles['preparation_time'][$profile_id]) ? $profiles['preparation_time'][$profile_id] : '',
                    'shipping_free' => isset($profiles['shipping_free'][$profile_id]) ? $profiles['shipping_free'][$profile_id] : array(),
                    'shipping_include' => isset($profiles['shipping_include'][$profile_id]) ? $profiles['shipping_include'][$profile_id] : '',
                    'shipping_include_percentage' => isset($profiles['shipping_include_percentage'][$profile_id]) ? $profiles['shipping_include_percentage'][$profile_id] : '',
                    'shipping_include_limit' => isset($profiles['shipping_include_limit'][$profile_id]) ? $profiles['shipping_include_limit'][$profile_id] : '',
                    'multitenant' => isset($profiles['multitenant'][$profile_id]) && is_array($profiles['multitenant'][$profile_id]) ? $profiles['multitenant'][$profile_id] : null,
                    'cdav' => isset($profiles['cdav'][$profile_id]) ? $profiles['cdav'][$profile_id] : null,
                    'cdav_max' => isset($profiles['cdav_max'][$profile_id]) ? $profiles['cdav_max'][$profile_id] : null,
                );

                // 2. Price rules profile data
                if (isset($profiles['price_rule'][$profile_id]) && is_array($profiles['price_rule'][$profile_id]) && isset($profiles['price_rule'][$profile_id]['rule']['from']) && is_array($profiles['price_rule'][$profile_id]['rule']['from']) && isset($profiles['price_rule'][$profile_id]['rule']['to']) && is_array($profiles['price_rule'][$profile_id]['rule']['to'])) {
                    $priceRules['currency_sign'] = isset($current_currency->sign) ? $current_currency->sign : null;
                    $priceRules['type'] = isset($profiles['price_rule'][$profile_id]['type']) ? $profiles['price_rule'][$profile_id]['type'] : 'percent';

                    if (isset($profiles['price_rule'][$profile_id]['rule']['from']) && is_array($profiles['price_rule'][$profile_id]['rule']['from']) && isset($profiles['price_rule'][$profile_id]['rule']['to']) && is_array($profiles['price_rule'][$profile_id]['rule']['to'])) {
                        $priceRules['rule'] = $profiles['price_rule'][$profile_id]['rule'];
                        if (!count($profiles['price_rule'][$profile_id]['rule']['from']) && !count($profiles['price_rule'][$profile_id]['rule']['to']) && !count($profiles['price_rule'][$profile_id]['rule']['value'])) {
                            $priceRules['rule']['from'][0] = '';
                            $priceRules['rule']['to'][0] = '';
                            $priceRules['rule']['percent'][0] = '';
                            $priceRules['rule']['value'][0] = '';
                        }
                    }
                } else {
                    // first use
                    $priceRules = array(
                        'currency_sign' => isset($current_currency->sign) ? $current_currency->sign : null,
                        'type' => 'percent',
                        'rule' => array(
                            'from' => array(''),
                            'to' => array(''),
                            'percent' => array(''),
                            'value' => array(''),
                        ),
                    );
                }
                $profile_data['price_rule'] = $priceRules;

                // 3. Models & breadcrumb profile data
                $breadcrumb_model = '';
                foreach ($modelOptions as $modelOption) {
                    if ($profile_data['model'] == $modelOption['value']) {
                        $breadcrumb_model = $modelOption['desc'];
                    }
                }
                $profile_data['type'] = $breadcrumb_model;

                // EO Models
                // Price formula
                if ((bool)Configuration::get(Cdiscount::KEY . '_ALIGNMENT_ACTIVE')) {
                    // Price Align
                    $profile_data['aligment_active'] = '1';
                }

                $view_params['profiles_data'][md5($profile_name)] = $profile_data;
            }
            $view_params['loaded'] = true;
            $view_params['saved'] = is_array($profiles) && count($profiles);
        } else {
            $view_params['loaded'] = false;
            $view_params['saved'] = is_array($profiles) && count($profiles);
        }

        if (is_array(Cdiscount::$carriers_info)) {
            $carrierInfo = array();
            $available_carrier_methods_list = array();

            if (is_array(Cdiscount::$seller_informations) && isset(Cdiscount::$seller_informations['DeliveryModeInformation']) && is_array(Cdiscount::$seller_informations['DeliveryModeInformation'])) {
                foreach (Cdiscount::$seller_informations['DeliveryModeInformation'] as $dmi) {
                    if (!is_array($dmi)) {
                        continue;
                    }
                    if (!isset(Cdiscount::$predefined_carriers[$dmi['Code']])) {
                        continue;
                    }

                    $carrier_index = Cdiscount::$predefined_carriers[$dmi['Code']];
                    $available_carrier_methods_list[$carrier_index] = true;
                }
            }
            foreach (Cdiscount::$carriers_info as $carrier_name => $val) {
                if (!(int)$val) {
                    continue;
                }

                if (!array_key_exists($carrier_name, Cdiscount::$carrier_labels)) {
                    continue;
                }

                if (!is_array($available_carrier_methods_list) || !count($available_carrier_methods_list) || !array_key_exists($carrier_name, $available_carrier_methods_list)) {
                    continue;
                }

                $carrierInfo[$carrier_name] = array(
                    'carrier_name' => $carrier_name,
                    'carrier_label' => Cdiscount::$carrier_labels[$carrier_name],
                );
            }
            $view_params['carrier_info'] = $carrierInfo;
        }

        return $view_params;
    }

    public function tabModels()
    {
        $modelsData = array();
        $models = $this->module->loadModels();
        foreach ($models as $internalId => $modelData) {
            $model = new CDiscountModuleModel($internalId, $modelData);
            $modelsData[] = $model;
        }

        return array(
            'models_data' => $modelsData,
            'universe_options' => $this->module->getUniverseOptions(),
        );
    }

    // Todo: Convert to private after move tabMapping here
    public function tabMappingHasSizeFields()
    {
        $models = $this->module->loadModels();
        $attributes = array();
        $features = array();

        foreach ($models as $modelInternalId => $modelData) {
            $moduleModel = new CDiscountModuleModel($modelInternalId, $modelData);
            if ($moduleModel->fashionSize) {
                $attributes[] = $moduleModel->fashionSize;
            }
            if ($moduleModel->featureSize) {
                $features[] = $moduleModel->featureSize;
            }
        }

        return array(
            'size_attributes' => $attributes,
            'size_features' => $features,
        );
    }

    protected function getModelPublicOptions($categoryId, $modelId, $modelSelectedPublic)
    {
        $publicOptions = array();
        $publicValues = $this->getInstanceCDiscountModel()->getModelPublicValues($categoryId, $modelId);
        if (count($publicValues)) {
            foreach ($publicValues as $public) {
                $publicOptions[] = array(
                    'value' => $public,
                    'desc' => html_entity_decode($public, ENT_COMPAT, 'UTF-8'),
                    'selected' => $modelSelectedPublic == $public ? 'selected="selected"' : '',
                );
            }
        }

        return $publicOptions;
    }

    protected function getModelGenderOptions($categoryId, $modelId, $modelSelectedGender)
    {
        $genderOptions = array();
        $genderValues = $this->getInstanceCDiscountModel()->getModelGenderValues($categoryId, $modelId);
        if (count($genderValues)) {
            foreach ($genderValues as $gender) {
                $genderOptions[] = array(
                    'value' => $gender,
                    'desc' => html_entity_decode($gender, ENT_COMPAT, 'UTF-8'),
                    'selected' => $modelSelectedGender == $gender ? 'selected="selected"' : '',
                );
            }
        }

        return $genderOptions;
    }

    protected function getMultiTenants()
    {
        $result = null;

        if (isset(Cdiscount::$seller_informations['Multichannel']) && Cdiscount::$seller_informations['Multichannel']['Multitenant']) {
            $multitenants = Cdiscount::multitenantGetList();
        } else {
            $multitenants = array();
        }

        if (is_array($multitenants) && count($multitenants)) {
            $result = array();
            foreach ($multitenants as $multitenant) {
                $Id = (int)$multitenant['Id'];
                if (!isset($multitenant['Checked']) || !(bool)$multitenant['Checked']) {
                    continue;
                }
                $result[$Id] = $multitenant;
            }
        }

        return $result;
    }

    protected function getInstanceCDiscountModel()
    {
        if (!$this->cdModel) {
            $username = Configuration::get(Cdiscount::KEY . '_USERNAME');
            $password = Configuration::get(Cdiscount::KEY . '_PASSWORD');
            $production = !Configuration::get(Cdiscount::KEY . '_PREPRODUCTION');
            $this->cdModel = CDiscountModel::getInstance($username, $password, $production, $this->module->debug);
        }
        return $this->cdModel;
    }
}
