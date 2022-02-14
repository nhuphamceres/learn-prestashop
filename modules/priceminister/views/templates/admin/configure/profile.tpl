{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
 * Contact by Email :  support.priceminister@common-services.com
 *}

<div class="margin-form col-lg-offset-3 pm-profile">
    <fieldset>
        <div class="pm-profile-header">
            <div>
                <label class="pm-profile-header-profile-name">{$profile.name}</label><span style="color:grey"></span>
                {if ($profile.model_name)}
                    <span class="pm-profile-header-separator">&nbsp;&nbsp;&gt;&nbsp;&nbsp;</span>
                    <span class="pm-profile-header-model-name">{$profile.model_name}</span>
                {/if}
                <span class="pm-profile-action">
                        <img src="{$images_url}cross.png" class="pm-profile-action-delete" alt=""/>
                        <img src="{$images_url}edit.png" class="pm-profile-action-edit" alt=""/>
                    </span>
            </div>
        </div>
        <div class="pm-profile-body" style="display:none">
            <div class="pm-profile-item">
                <label>{l s='Profile Name' mod='priceminister'}</label>&nbsp;&nbsp;
                <input type="text" name="pm_profiles[_key_][name]" class="profile-profile-name" value="{$profile.name}"/><span class="pm-required">*</span>
            </div>

            <div class="pm-profile-item">
                <label>{l s='Associated Model' mod='priceminister'}</label>&nbsp;&nbsp;
                {if isset($profiles.model_options) && count($profiles.model_options)}
                    <select name="pm_profiles[_key_][model]" class="profile-model-name">
                        <option value=""></option>
                        {foreach $profiles.model_options item=option}
                            <option value="{$option.value|escape:'htmlall':'UTF-8'}" {if $option.value==$profile.model} selected="selected" {/if}>{$option.desc}</option>
                        {/foreach}
                    </select>
                    <span class="pm-required">*</span>
                {else}
                    <span style="color:red;font-size:1.2em;">{l s='First, you must create models in model tab' mod='priceminister'}</span>
                    :
                    <img src="{$images_url}arrow-go.png" class="pm-go-models" alt=""/>
                {/if}
            </div>

            <div class="pm-profile-item">
                <label>{l s='Repricing Strategy' mod='priceminister'}</label>&nbsp;&nbsp;
                {if isset($repricing.repricing_strategies) && count($repricing.repricing_strategies)}
                    <select name="pm_profiles[_key_][repricing_strategie]" class="profile-model-name">
                        <option value=""></option>
                        {foreach $repricing.repricing_strategies item=option}
                            <option value="{$option.id_repricing|escape:'htmlall':'UTF-8'}" {if $option.id_repricing==$profile.repricing_strategie|default:0}selected{/if}>{$option.name}</option>
                        {/foreach}
                    </select>
                {else}
                    <span style="color:red;font-size:1.2em;">{l s='First, you must create a repricing strategie in Repricing tab' mod='priceminister'}</span>
                    :
                    <img src="{$images_url}arrow-go.png" class="pm-go-repricing" alt=""/>
                {/if}
            </div>

            <hr style="width:30%"/>

            <div class="pm-profile-item">
                <label>{l s='Price Rule' mod='priceminister'}</label>

                <div class="price-rule-container">
                    <select name="pm_profiles[_key_][price_rule][type]" class="price-rule-type" value="" style="display: inline-block; max-width:150px;"/>
                    <option value="percent" {if ($profile.price_rule.type == 'percent')}selected{/if}>{l s='Percentage' mod='priceminister'}</option>
                    {if isset($profile.price_rule.currency_sign)}
                        <option value="value" {if ($profile.price_rule.type == 'value')}selected{/if}>{l s='Value' mod='priceminister'}</option>
                    {/if}
                    </select>
                    &nbsp;&nbsp;
                    <div id="default-price-rule" class="default-price-rule" style="display: inline-block;">
                        {foreach from=$profile.price_rule.rule.from key=index item=value}
                            <div class="price-rule">
                                <input type="text" name="pm_profiles[_key_][price_rule][rule][from][]" rel="from" style="width:50px" value="{$profile.price_rule.rule.from[$index]}"/>&nbsp;&nbsp;{$profile.price_rule.currency_sign}
                                <span>
                                        &nbsp;&nbsp;<img src="{$images_url}slash.png" class="price-rule-slash" alt=""/>&nbsp;&nbsp;
                                    </span>
                                <input type="text" name="pm_profiles[_key_][price_rule][rule][to][]" rel="to" style="width:50px" value="{$profile.price_rule.rule.to[$index]|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;{$profile.price_rule.currency_sign}
                                <span>
                                        &nbsp;&nbsp;<img src="{$images_url}next.png" class="price-rule-next" alt=""/>&nbsp;&nbsp;
                                    </span>
                                <select name="pm_profiles[_key_][price_rule][rule][percent][]" rel="percent" style="width:100px;{if ($profile.price_rule.type != 'percent')}display:none;{/if}"/>
                                <option value="0">0 %</option>
                                {section name=price_rule_percent loop=99}
                                    <option value="{$smarty.section.price_rule_percent.iteration|escape:'htmlall':'UTF-8'}" {if $profile.price_rule.rule.percent[$index] == $smarty.section.price_rule_percent.iteration}selected{/if}>{$smarty.section.price_rule_percent.iteration|escape:'htmlall':'UTF-8'}
                                        &#37;</option>
                                {/section}
                                <option disabled>--</option>
                                {section name=price_rule_percent loop=99}
                                    <option value="-{$smarty.section.price_rule_percent.iteration|escape:'htmlall':'UTF-8'}" {if $profile.price_rule.rule.percent[$index] == ($smarty.section.price_rule_percent.iteration * -1)}selected{/if}>
                                        -{$smarty.section.price_rule_percent.iteration|escape:'htmlall':'UTF-8'} &#37;</option>
                                {/section}
                                </select>
                                {if isset($profile.price_rule.currency_sign)}
                                    <select name="pm_profiles[_key_][price_rule][rule][value][]" rel="value" style="width:100px;{if ($profile.price_rule.type != 'value')}display:none;{/if}"/>
                                    <option value="0">0 {$profile.price_rule.currency_sign}</option>
                                    {section name=price_rule_value loop=99}
                                        <option value="{$smarty.section.price_rule_value.iteration|escape:'htmlall':'UTF-8'}" {if $profile.price_rule.rule.value[$index] == $smarty.section.price_rule_value.iteration}selected{/if}>{$smarty.section.price_rule_value.iteration|escape:'htmlall':'UTF-8'} {$profile.price_rule.currency_sign}</option>
                                    {/section}
                                    <option disabled>--</option>
                                    {section name=price_rule_value loop=99}
                                        <option value="-{$smarty.section.price_rule_value.iteration|escape:'htmlall':'UTF-8'}" {if $profile.price_rule.rule.value[$index] == ($smarty.section.price_rule_value.iteration * -1)}selected{/if}>
                                            -{$smarty.section.price_rule_value.iteration|escape:'htmlall':'UTF-8'} {$profile.price_rule.currency_sign}</option>
                                    {/section}
                                    </select>
                                {/if}
                                <span class="price-rule-add" {if $index > 0}style="display:none;"{/if}><img src="{$images_url}plus.png" alt="{l s='Add a rule' mod='priceminister'}"/></span>
                                <span class="price-rule-remove" {if $index == 0}style="display:none;"{/if}><img src="{$images_url}minus.png" alt="{l s='Remove a rule' mod='priceminister'}"/></span>
                            </div>
                        {/foreach}
                    </div>
                </div>
            </div>

            <hr style="width:30%"/>

            <div class="pm-profile-item">
                <label>{l s='Images' mod='priceminister'}</label>&nbsp;&nbsp;
                <input type="checkbox" name="pm_profiles[_key_][images_optionnals]" {if ($profile.images_optionnals)}checked{/if} value="1"/>
                <span class="checkbox-label">{l s='Allow products without images' mod='priceminister'}</span>&nbsp;
                <span class="checkbox-label" style="color:red">({l s='Not recommended' mod='priceminister'})</span>
            </div>

            <div class="pm-profile-item">
                <label>{l s='Name + attributs' mod='priceminister'}</label>&nbsp;&nbsp;
                <input type="checkbox" name="pm_profiles[_key_][name_with_attributes]" {if isset($profile.name_with_attributes) && $profile.name_with_attributes}checked{/if} value="1"/>
                <span class="checkbox-label">{l s='Add the product attributes in the product name' mod='priceminister'}</span>&nbsp;
            </div>

            <div class="pm-profile-item">
                <label>{l s='Short Description' mod='priceminister'}</label>&nbsp;&nbsp;
                <input type="checkbox" name="pm_profiles[_key_][short_long_description]" {if isset($profile.short_long_description) && $profile.short_long_description}checked{/if} value="1"/>
                <span class="checkbox-label">{l s='Product description will be composed of Short and Long Description' mod='priceminister'}</span>&nbsp;
            </div>

            <div class="pm-profile-item">
                <label>{l s='Filter Description' mod='priceminister'}</label>&nbsp;&nbsp;
                <input type="checkbox" name="pm_profiles[_key_][filter_description]" {if isset($profile.filter_description) && $profile.filter_description}checked{/if} value="1"/>
                <span class="checkbox-label">{l s='Filter descriptions, remove parasite characters and noise' mod='priceminister'}</span>&nbsp;
            </div>

            <div class="pm-profile-item">
                <label>{l s='Do not send EAN code' mod='priceminister'}</label>&nbsp;&nbsp;
                <input type="checkbox" name="pm_profiles[_key_][no_ean]" {if isset($profile.no_ean) && $profile.no_ean}checked{/if} value="1"/>
                <span class="checkbox-label">{l s='If checked, the ean code of your products will not be sent to RakutenFrance' mod='priceminister'}</span>&nbsp;
            </div>

            <hr/>

            <div class="pm-profile-delete">
                <button class="btn btn-default">
                    <img src="{$images_url}cross.png" class="del-profile-img" alt=""/>&nbsp;&nbsp; {l s='Delete' mod='priceminister'}
                </button>
            </div>

            <div class="pm-profile-minimize">
                <button class="btn btn-default">
                    <img src="{$images_url}minimize.png" class="min-profile-img" alt=""/>&nbsp;&nbsp; {l s='Minimize' mod='priceminister'}
                </button>
            </div>
        </div>
    </fieldset>
    <hr style="width: 30%;"/>

</div>