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

{if $model_data->hasVariation()}
    <div class="has_variation">
        {* Introduction *}
        <label class="control-label col-lg-3" style="color:grey">
            {l s='Variants/Combinations' mod='cdiscount'}
            <span class="label-option">({l s='Optional' mod='cdiscount'})</span>
        </label>
        <div class="col-lg-9"></div>

        <label class="control-label col-lg-3" style="clear: both;"></label>
        <div class="margin-form col-lg-9">
            <p class="model-help">{l s='Setup these entries only if you want to send this product as variations (parent/child)' mod='cdiscount'}</p>
            <p style="color:brown;">
                {l s='Variants must be configured by pairs. Size and Color are both required if variant is configured.' mod='cdiscount'}
                <br>
                {l s='Please refer to the documentation for more details' mod='cdiscount'}:&nbsp;
                <a href="http://documentation.common-services.com/cdiscount/exporter-des-declinaisons/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                   target="_blank">http://documentation.common-services.com/cdiscount/exporter-des-declinaisons/</a>
                <br>
            </p>
        </div>

        {* Force variant *}
        {if $expert_mode}
            <label class="control-label col-lg-3">
                {l s='Force Variant' mod='cdiscount'}
                <span class="expert">{l s='Expert' mod='cdiscount'}</span>
            </label>
            <div class="margin-form col-lg-9">
            <span>
                <input type="checkbox" name="{'models['|cat:$model_data->id:'][variant]'}"
                       style="width:20px;height:20px;" value="1"
                       {if $model_data->forceVariant}checked{/if} title="{l s='Force Variant' mod='cdiscount'}" />
                <span class="model-free-label">{l s='Yes' mod='cdiscount'}</span>
            </span>
                <br />
                <p class="model-help">
                    {l s='Force this model as a variant even product doesn\'t have combinations' mod='cdiscount'}<br />
                </p>
            </div>
        {/if}

        {* Size *}
        <label class="control-label col-lg-3">{l s='Size' mod='cdiscount'}</label>
        <div class="margin-form col-lg-9">
            <table class="cdiscount-variant">
                <thead>
                <tr>
                    <td>{l s='Attribute' mod='cdiscount'}</td>
                    <td style="color:navy">&nbsp;&nbsp;{l s='or' mod='cdiscount'}&nbsp;&nbsp;</td>
                    <td>{l s='Feature' mod='cdiscount'}</td>
                    <td style="color:navy">&nbsp;&nbsp;{l s='else' mod='cdiscount'}&nbsp;&nbsp;</td>
                    <td>{l s='Default' mod='cdiscount'}</td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <select name="{'models['|cat:$model_data->id:'][fashion_size]'}"
                                title="{l s='Attribute' mod='cdiscount'}">
                            <option></option>
                            {foreach from=$attribute_options item=attribute_option}
                                <option value="{$attribute_option.value|escape:'htmlall':'UTF-8'}"
                                        {if $model_data->fashionSize == $attribute_option.value}selected{/if}>
                                    {$attribute_option.desc|escape:'htmlall':'UTF-8'}
                                </option>
                            {/foreach}
                        </select>
                    </td>
                    <td><span>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;</span></td>
                    <td>
                        <select name="{'models['|cat:$model_data->id:'][feature_size]'}"
                                title="{l s='Feature' mod='cdiscount'}">
                            <option></option>
                            {foreach from=$feature_size_options item=feature_size_option}
                                <option value="{$feature_size_option.value|escape:'htmlall':'UTF-8'}"
                                        {if $model_data->featureSize == $feature_size_option.value}selected{/if}>
                                    {$feature_size_option.desc|escape:'htmlall':'UTF-8'}
                                </option>
                            {/foreach}
                        </select>
                    </td>
                    <td><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;</span></td>
                    <td style="white-space:nowrap" class="mapping-box-scope">
                        <select name="{'models['|cat:$model_data->id:'][default_size]'}"
                                title="{l s='Default' mod='cdiscount'}">
                            <option></option>
                            {if !empty($model_data->defaultSize)}
                                <option selected>{$model_data->defaultSize|escape:'htmlall':'UTF-8'}</option>
                            {/if}
                        </select>
                        <span class="mapping-box-search" rel="{$model_data->modelId|escape:'htmlall':'UTF-8'}"
                              data-category-id="{$model_data->categoryId|escape:'htmlall':'UTF-8'}">
                            <img src="{$images_url|escape:'htmlall':'UTF-8'}icon-search.png" alt="Search" />
                        </span>
                    </td>
                </tr>
                </tbody>
            </table>
            <p class="model-help" style="margin:10px;">
                {l s='Please enter the field corresponding to the CDiscount\'s size field.' mod='cdiscount'}
            </p>
        </div>

        {* Color *}
        <label class="control-label col-lg-3">{l s='Color' mod='cdiscount'}</label>
        <div class="margin-form col-lg-9">
            <table class="cdiscount-variant">
                <thead>
                <tr>
                    <td>{l s='Attribute' mod='cdiscount'}</td>
                    <td style="color:navy">&nbsp;&nbsp;{l s='or' mod='cdiscount'}&nbsp;&nbsp;</td>
                    <td>{l s='Feature' mod='cdiscount'}</td>
                    <td style="color:navy">&nbsp;&nbsp;&nbsp;{l s='else' mod='cdiscount'}&nbsp;&nbsp;</td>
                    <td>{l s='Default' mod='cdiscount'}</td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <select name="{'models['|cat:$model_data->id:'][fashion_color]'}"
                                title="{l s='Attribute' mod='cdiscount'}">
                            <option></option>
                            {foreach from=$attribute_options item=attribute_option}
                                <option value="{$attribute_option.value|escape:'htmlall':'UTF-8'}"
                                        {if $model_data->fashionColor == $attribute_option.value}selected{/if}>
                                    {$attribute_option.desc|escape:'htmlall':'UTF-8'}
                                </option>
                            {/foreach}
                        </select>
                    </td>
                    <td><span>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;</span></td>
                    <td>
                        <select name="{'models['|cat:$model_data->id:'][feature_color]'}"
                                title="{l s='Feature' mod='cdiscount'}">
                            <option></option>
                            {foreach from=$feature_color_options item=feature_color_option}
                                <option value="{$feature_color_option.value|escape:'htmlall':'UTF-8'}"
                                        {if $model_data->featureColor == $feature_color_option.value}selected{/if}>
                                    {$feature_color_option.desc|escape:'htmlall':'UTF-8'}
                                </option>
                            {/foreach}
                        </select>
                    </td>
                    <td><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;</span></td>
                    <td>
                        <input id="default_color" name="{'models['|cat:$model_data->id:'][default_color]'}"
                               value="{$model_data->defaultColor|escape:'htmlall':'UTF-8'}"
                               title="{l s='Default' mod='cdiscount'}">
                    </td>
                </tr>
                </tbody>
            </table>
            <p class="model-help">{l s='Please enter the field corresponding to the CDiscount\'s color field.' mod='cdiscount'}</p>
        </div>
    </div>
{/if}
