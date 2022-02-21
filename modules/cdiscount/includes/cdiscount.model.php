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
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * @package   CDiscount
 * Support by mail:  support.cdiscount@common-services.com
 */

class CDiscountModuleModel
{
    public $id;
    public $name;
    public $universe;
    public $categoryId;
    public $categoryName;
    public $modelId;
    public $modelName;

    public $public;
    public $gender;
    public $forceVariant = false;

    public $fashionSize;
    public $featureSize;
    public $defaultSize;
    public $fashionColor;
    public $featureColor;
    public $defaultColor;

//    public $specificFields;

    public function __construct($internalId, $dataAsArray = array())
    {
        $this->id = $internalId;
        foreach ($dataAsArray as $fieldName => $fieldValue) {
            $sanityName = $this->transformPropertyName($fieldName);
            $this->$sanityName = $fieldValue;
        }

        if (!$this->categoryId) {
            $this->categoryId = isset($this->category) ? $this->category : null;
        }
        if (!$this->modelId) {
            $this->modelId = isset($this->model) ? $this->model : null;
        }
        $this->modelName = isset($this->modelExternalName) ? $this->modelExternalName : null;
        $this->forceVariant = isset($this->variant) ? $this->variant : null;
    }

    /**
     * Query in model_variant.tpl
     * @return bool|mixed
     */
    public function hasVariation()
    {
        return Cdiscount::getInstanceCDiscountModel()->isVariationModel($this->categoryId, $this->modelId);
    }

    /**
     * Query in models.tpl
     * @return string
     */
    public function getBreadcrumb()
    {
        return implode(' > ', array_filter(array(
            $this->universe,
            $this->categoryName,
            html_entity_decode($this->modelName, ENT_COMPAT, 'UTF-8'),
            $this->public,
            $this->gender
        )));
    }

    private function transformPropertyName($fieldName)
    {
        $segment = explode('_', $fieldName);
        $upper = array_map(function ($item) {
            return Tools::ucfirst($item);
        }, $segment);

        return lcfirst(implode('', $upper));
    }
}
