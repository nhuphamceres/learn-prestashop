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

class CDiscountPostProcessManager
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

    public function saveConfiguration()
    {
        $this->saveModelsAndSpecificFields();
    }

    protected function saveModelsAndSpecificFields()
    {
        $inputModels = Tools::getValue('models', array());
        $inputSpecificFields = Tools::getValue('specifics', array());

        $savedModels = $this->module->loadModels();
        $specificFields = $this->module->loadSpecificFields();

        $resultModels = array();
        $resultSpecificFields = array();

        foreach ($inputModels as $idModel => $modelData) {
            $state = isset($modelData['state']) ? $modelData['state'] : 'as-is';
            switch ($state) {
                case 'unchanged':
                    if (isset($savedModels[$idModel])) {
                        $resultModels[$idModel] = $savedModels[$idModel];
                    }
                    if (isset($specificFields[$idModel])) {
                        $resultSpecificFields[$idModel] = $specificFields[$idModel];
                    }
                    break;
                default:
                    $resultModels[$idModel] = $modelData;
                    if (isset($inputSpecificFields[$idModel])) {
                        $resultSpecificFields[$idModel] = $inputSpecificFields[$idModel];
                    }
                    break;
            }
        }

        CDiscountConfiguration::updateValue(CDiscountConstant::CONFIG_MODELS, $resultModels);
        CDiscountConfiguration::updateValue(CDiscountConstant::CONFIG_SPECIFIC_FIELDS, $resultSpecificFields);

        $this->module->unLoadModels()->unloadSpecificFields();
    }
}
