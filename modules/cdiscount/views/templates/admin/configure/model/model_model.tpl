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
 * @author    Olivier B., Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail:  support.cdiscount@common-services.com
*}

<label class="model-obj-title control-label col-lg-3">{l s='Model' mod='cdiscount'}</label>
<div class="margin-form col-lg-9">
    <select name="{'models['|cat:$model_data->id:'][model]'}" class="model-model" title="{l s='Model' mod='cdiscount'}" style="width:250px">
        <option>{l s='Please Choose in the List' mod='cdiscount'}</option>
        {foreach from=$model_options item=model_option}
            <option value="{$model_option.value|escape:'htmlall':'UTF-8'}"
                    {if $model_data->modelId == $model_option.value}selected{/if}>
                {$model_option.desc|escape:'htmlall':'UTF-8'}
            </option>
        {/foreach}
    </select>
    <span class="required">*</span>

    <p class="model-help">{l s='Please choose the model template which is corresponding to your products' mod='cdiscount'}
        <br />
    </p>
    <div class="cleaner"></div>

    <input type="hidden" class="model-external-name" name="{'models['|cat:$model_data->id:'][model_external_name]'}"
           value="{$model_data->modelName|escape:'htmlall':'UTF-8'}" />
</div>
