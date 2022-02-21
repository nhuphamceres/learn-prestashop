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

{if $public_options|count}
    <div class="model-public-container">
        <label class="model-obj-title control-label col-lg-3">{l s='Public' mod='cdiscount'}</label>
        <div class="margin-form col-lg-9">
            <select name="{'models['|cat:$model_data->id:'][public]'}" class="model-public" style="width:210px"
                    title="{l s='Public' mod='cdiscount'}">
                <option>{l s='Please Choose in the List' mod='cdiscount'}</option>
                {foreach from=$public_options item=public_option}
                    <option value="{$public_option.value|escape:'htmlall':'UTF-8'}"
                            {if $model_data->public == $public_option.value}selected{/if}>
                        {$public_option.desc|escape:'htmlall':'UTF-8'}
                    </option>
                {/foreach}
            </select>

            <span class="required">*</span>
            {*            <img src="{$images_url|escape:'htmlall':'UTF-8'}loader-connection.gif"*}
            {*                 alt="{l s='Loading Model Informations' mod='cdiscount'}" class="model-public-loader" />*}

            <p class="model-help">{l s='Please choose a kind of public for this model' mod='cdiscount'}</p>
        </div>
    </div>
{/if}

<div class="cleaner"></div>

{if $gender_options|count}
    <div class="model-gender-container">
        <label class="model-obj-title control-label col-lg-3">{l s='Gender' mod='cdiscount'}</label>
        <div class="margin-form col-lg-9">
            <select name="{'models['|cat:$model_data->id:'][gender]'}" class="select-gender-profile model-gender" style="width:210px"
                    title="{l s='Gender' mod='cdiscount'}">
                <option value="">{l s='Please Choose in the List' mod='cdiscount'}</option>
                {foreach from=$gender_options item=gender_option}
                    <option value="{$gender_option.value|escape:'htmlall':'UTF-8'}"
                            {if $model_data->gender == $gender_option.value}selected{/if}>
                        {$gender_option.desc|escape:'htmlall':'UTF-8'}
                    </option>
                {/foreach}
            </select>

            <span class="required">*</span>
{*            <img src="{$images_url|escape:'htmlall':'UTF-8'}loader-connection.gif"*}
{*                 alt="{l s='Loading Model Informations' mod='cdiscount'}" class="model-gender-loader" />*}

            <p class="model-help"> {l s='Please choose a gender for this model' mod='cdiscount'}</p>
        </div>
    </div>
{/if}

<div class="cleaner"></div>
