{**
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
 * @package   CDiscount
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail:  support.cdiscount@common-services.com
*}

<div id="{$model_data->id|escape:'htmlall':'UTF-8'}" class="model-create model form-group stored-model">
    {include file="$module_path/views/templates/admin/configure/model/model_name_universe.tpl"
    model_id=$model_data->id model_name=$model_data->name universe=$model_data->universe}

    {if $model_data->universe}
        {include file="$module_path/views/templates/admin/configure/model/model_category.tpl"}
        {if $model_data->categoryId}
            {include file="$module_path/views/templates/admin/configure/model/model_model.tpl"}
            {if $model_data->modelId}
                {include file="$module_path/views/templates/admin/configure/model/model_public_gender.tpl"}
                {include file="$module_path/views/templates/admin/configure/model/model_variant.tpl"}
                {include file="$module_path/views/templates/admin/configure/model/model_specific_data.tpl"}
            {/if}
        {/if}
    {/if}
</div>
