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

<label class="profile-obj-title control-label col-lg-3">{l s='CDiscount Category' mod='cdiscount'}</label>
<div class="margin-form col-lg-9">
    <select name="{'models['|cat:$model_data->id:'][category]'}" style="max-width:450px" class="model-category chosen-select-large"
            title="{l s='CDiscount Category' mod='cdiscount'}">
        <option></option>
        {foreach from=$category_options item=category_option}
            <option value="{$category_option.value|escape:'htmlall':'UTF-8'}"
                    {if $model_data->categoryId == $category_option.value}selected{/if}>
                {$category_option.desc|escape:'htmlall':'UTF-8'}
            </option>
        {/foreach}
    </select>
    <span class="required">*</span>
    <img src="{$images_url|escape:'htmlall':'UTF-8'}loader-connection.gif"
         alt="{l s='Loading Models' mod='cdiscount'}" class="model-category-loader" />

    <div class="model-help">{l s='Please select the main category for this profile' mod='cdiscount'}</div>
    <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'} model-category-error"
         style="display:none"></div>
    <div class="cleaner"></div>

    <input type="hidden" class="model-category-name" name="{'models['|cat:$model_data->id:'][category_name]'}"
           value="{$model_data->categoryName|escape:'htmlall':'UTF-8'}" />
</div>
