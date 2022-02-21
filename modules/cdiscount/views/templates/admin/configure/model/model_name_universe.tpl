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

<label class="model-obj-title control-label col-lg-3" style="color:green;">
    {l s='Model Name' mod='cdiscount'}
</label>

<div class="margin-form col-lg-9">
    <input type="text" name="{'models['|cat:$model_id:'][name]'}" class="model-name" data-name="name"
           value="{$model_name|escape:'htmlall':'UTF-8'}" {if !$model_id}disabled{/if}
           title="{l s='Model Name' mod='cdiscount'}"
           style="width:245px"/>
    <span class="required">*</span>
    <p class="model-help">
        {l s='Name of model. Use a friendly name to remember it.' mod='cdiscount'}<br/>
        {l s='Do not forget to click on the save button at the bottom right of the page !' mod='cdiscount'}
    </p>
</div>

{* Universe *}
<label class="profile-obj-title control-label col-lg-3">{l s='Universe' mod='cdiscount'}</label>
<div class="margin-form col-lg-9">
    <select name="{'models['|cat:$model_id:'][universe]'}" class="model-universe" data-name="universe"
            {if !$model_id}disabled{/if}
            title="{l s='Universe' mod='cdiscount'}" style="width:350px">
        <option></option>
        {foreach from=$universe_options item=universe_option}
            <option value="{$universe_option.value|escape:'htmlall':'UTF-8'}"
                    {if $universe == $universe_option.value}selected{/if}>
                {$universe_option.desc|escape:'htmlall':'UTF-8'}
            </option>
        {/foreach}
    </select>
    <span class="required">*</span>
    <img src="{$images_url|escape:'htmlall':'UTF-8'}loader-connection.gif"
         alt="{l s='Loading Categories' mod='cdiscount'}" class="model-universe-loader"/>

    <div class="model-help">
        {l s='Take the name of the main CDiscount category (eg: INFORMATIQUE).' mod='cdiscount'}
    </div>
    <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'} model-universe-error" style="display:none"></div>
    <div class="cleaner"></div>
</div>