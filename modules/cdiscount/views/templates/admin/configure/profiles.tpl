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
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail:  support.cdiscount@common-services.com
*}

<div id="conf-profiles" class="tabItem">
    <h2>{l s='Profiles' mod='cdiscount'}</h2>

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>

        <div class="margin-form col-lg-9">
            <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                {l s='Please follow our online tutorial' mod='cdiscount'} :<br>
                <a href="http://documentation.common-services.com/cdiscount/configurer-un-profil/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                   target="_blank">http://documentation.common-services.com/cdiscount/configurer-un-profil/</a><br>
            </div>
        </div>
    </div>

    {if ($cd_profiles.loaded)}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Profiles Configuration' mod='cdiscount'}</label>

            <div class="margin-form col-lg-9" class="profiles">
                <div id="profile-add">
                    <span class="profile-add">{l s='Add a profile to the list' mod='cdiscount'}</span>&nbsp;&nbsp;
                    <span class="profile-add-img"><img src="{$images_url|escape:'htmlall':'UTF-8'}add.png" alt="add"/></span>
                </div>
                <div class="cleaner"></div>
            </div>
        </div>


        <!-- Container to receive the profiles -->

        <div id="profile-container">
            {if isset($cd_profiles.profiles_data)}
                <div id="profile-items">
                    {foreach from=$cd_profiles.profiles_data item=profile_data}
                        <div>
                            <div class="profile-content">
                                {* Profile header *}
                                {if $profile_data.name}
                                    <div class="profile-header" id="profile-header-{$profile_data.profile_id|intval}">
                                        <br/>
                                        <label class="control-label col-lg-3"
                                               style="color:navy">{$profile_data.name|escape:'htmlall':'UTF-8'}</label>

                                        <div class="margin-form col-lg-9">
                                            <table class="profile-table">
                                                <tr>
                                                    <td>
                                                        <span class="type">{$profile_data.type|escape:'htmlall':'UTF-8'}</span>
                                                    </td>
                                                    <td align="center" width="50px">
                                                        <img src="{$images_url|escape:'htmlall':'UTF-8'}cross.png" class="profile-del-img"
                                                             alt="{l s='Delete' mod='cdiscount'}"
                                                             rel="{$profile_data.profile_id|intval}"/>
                                                    </td>
                                                    <td align="center" width="50px">
                                                        <img src="{$images_url|escape:'htmlall':'UTF-8'}edit.png" class="profile-edit-img"
                                                             alt="{l s='Edit' mod='cdiscount'}"
                                                             rel="{$profile_data.profile_id|intval}"/>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                {/if}

                                {* Profile DIV *}
                                <div {$profile_data.profile_div|escape:'quotes':'UTF-8'} class="profile-create profile form-group {$profile_data.profile_class|escape:'htmlall':'UTF-8'}" style="display: none;">
                                    {if !$profile_data.name}
                                        <span class="profile-del-2">{l s='Remove this profile from the list' mod='cdiscount'}
                        &nbsp;&nbsp;<img src="{$images_url|escape:'htmlall':'UTF-8'}cross.png"
                                         class="model-del-img2"/></span>
                                        <h2>{l s='New Profile' mod='cdiscount'}</h2>
                                    {/if}

                                    <label class="profile-obj-title control-label col-lg-3"
                                           style="color:green;">{l s='Profile Name'  mod='cdiscount'}</label>

                                    <div class="margin-form col-lg-9">
                                        <input type="text" name="profiles[name][_key_]" class="profile-name"
                                               value="{$profile_data.name|escape:'htmlall':'UTF-8'}" style="width:245px"/>
                                        <span class="required">*</span>

                                        <div class="profile-help">
                                            {l s='Name of profile. Use a friendly name to remember it.' mod='cdiscount'}
                                        </div>
                                        <div class="profile-help">
                                            {l s='Do not forget to click on the save button at the bottom right of the page !'  mod='cdiscount'}
                                        </div>
                                    </div>

                                    {* Move universe & category to model*}

                                    <!-- EO Category -->
                                    <div class="cleaner"></div>
                                    <label class="profile-obj-title control-label col-lg-3">{l s='Model' mod='cdiscount'}</label>

                                    <div class="margin-form col-lg-9">
                                        {if $cd_profiles.model_options|count}
                                            <select name="profiles[model][_key_]" style="width:210px" class="chosen-select">
                                                <option value>{l s='Please Choose in the List' mod='cdiscount'}</option>
                                                {foreach from=$cd_profiles.model_options item=model_option}
                                                    <option value="{$model_option.value|escape:'htmlall':'UTF-8'}"
                                                            {if $model_option.value == $profile_data.model}selected{/if}>
                                                        {$model_option.desc|escape:'htmlall':'UTF-8'}
                                                    </option>
                                                {/foreach}
                                            </select>
                                            <span class="required">*</span>
                                            <div class="profile-help">{l s='Please choose a model for this profile' mod='cdiscount'}</div>

                                        {else}

                                            <div class="profile-help"
                                                 style="color:brown">{l s='Please create models first' mod='cdiscount'}</div>
                                        {/if}
                                    </div>
                                    <!-- EO Model -->

                                    <div class="cleaner"></div>

                                    <label class="profile-obj-title control-label col-lg-3">{l s='Price Rules' mod='cdiscount'}</label>

                                    <div class="margin-form col-lg-9">
                                        <select name="profiles[price_rule][_key_][type]" class="price-rule-type" value="" style="display: inline; max-width:150px;"/>
                                        <option value=""></option>
                                        <option value="percent" {if ($profile_data.price_rule.type == 'percent')}selected{/if}>{l s='Percentage' mod='cdiscount'}</option>
                                        {if isset($profile_data.price_rule.currency_sign)}
                                            <option value="value" {if ($profile_data.price_rule.type == 'value')}selected{/if}>{l s='Value' mod='cdiscount'}</option>
                                        {/if}
                                        </select>
                                        &nbsp;&nbsp;
                                        <div id="default-price-rule-{$id_lang|intval}" class="default-price-rule"
                                             style="display: inline-block;">
                                            {foreach from=$profile_data.price_rule.rule.from key=index item=value}
                                                <div class="price-rule">
                                                    <input type="text" name="profiles[price_rule][_key_][rule][from][]" rel="from" style="width:50px" value="{$profile_data.price_rule.rule.from[$index]|escape:'htmlall':'UTF-8'}" />&nbsp;&nbsp;{$profile_data.price_rule.currency_sign|escape:'htmlall':'UTF-8'}
                                                    <span>
									&nbsp;&nbsp;<img src="{$images_url|escape:'htmlall':'UTF-8'}slash.png" class="price-rule-slash" alt=""/>&nbsp;&nbsp;</span>
                                                    <input type="text" name="profiles[price_rule][_key_][rule][to][]" rel="to" style="width:50px" value="{$profile_data.price_rule.rule.to[$index]|escape:'htmlall':'UTF-8'}" />&nbsp;&nbsp;{$profile_data.price_rule.currency_sign|escape:'htmlall':'UTF-8'}
                                                    <span>
                                &nbsp;&nbsp;<img src="{$images_url|escape:'htmlall':'UTF-8'}next.png" class="price-rule-next" alt=""/>&nbsp;&nbsp;</span>
                                                    <select name="profiles[price_rule][_key_][rule][percent][]" rel="percent" style="width:100px;{if ($profile_data.price_rule.type != 'percent')}display:none;{/if}"/>
                                                    <option></option>
                                                    {section name=price_rule_percent loop=99}
                                                        <option value="{$smarty.section.price_rule_percent.iteration|escape:'htmlall':'UTF-8'}" {if $profile_data.price_rule.rule.percent[$index] == $smarty.section.price_rule_percent.iteration}selected{/if}>{$smarty.section.price_rule_percent.iteration|escape:'htmlall':'UTF-8'} &#37;</option>
                                                    {/section}
                                                    <option disabled>--</option>
                                                    {section name=price_rule_percent loop=99}
                                                        <option value="-{$smarty.section.price_rule_percent.iteration|escape:'htmlall':'UTF-8'}" {if $profile_data.price_rule.rule.percent[$index] == ($smarty.section.price_rule_percent.iteration * -1)}selected{/if}> -{$smarty.section.price_rule_percent.iteration|escape:'htmlall':'UTF-8'}&#37;</option>
                                                    {/section}
                                                    </select>
                                                    {if isset($profile_data.price_rule.currency_sign)}
                                                        <select name="profiles[price_rule][_key_][rule][value][]" rel="value" style="width:100px;{if ($profile_data.price_rule.type != 'value')}display:none;{/if}"/>
                                                        <option></option>

                                                        {section name=price_rule_value loop=99}
                                                            <option value="{$smarty.section.price_rule_value.iteration|escape:'htmlall':'UTF-8'}" {if $profile_data.price_rule.rule.value[$index] == $smarty.section.price_rule_value.iteration}selected{/if}>{$smarty.section.price_rule_value.iteration|escape:'htmlall':'UTF-8'} {$profile_data.price_rule.currency_sign|escape:'htmlall':'UTF-8'}</option>
                                                        {/section}

                                                        <option disabled>--</option>

                                                        {section name=price_rule_value loop=99}
                                                            <option value="-{$smarty.section.price_rule_value.iteration|escape:'htmlall':'UTF-8'}" {if $profile_data.price_rule.rule.value[$index] == ($smarty.section.price_rule_value.iteration * -1)}selected{/if}> -{$smarty.section.price_rule_value.iteration|escape:'htmlall':'UTF-8'} {$profile_data.price_rule.currency_sign|escape:'htmlall':'UTF-8'}</option>
                                                        {/section}
                                                        </select>
                                                    {/if}
                                                    <span class="price-rule-add" {if $index > 0}style="display:none;"{/if}>
									<img src="{$images_url|escape:'htmlall':'UTF-8'}plus.png" alt="{l s='Add a rule' mod='cdiscount'}"/></span>
                                                    <span class="price-rule-remove" {if $index == 0}style="display:none;"{/if}>
									<img src="{$images_url|escape:'htmlall':'UTF-8'}minus.png" alt="{l s='Remove a rule' mod='cdiscount'}"/></span>
                                                </div>
                                            {/foreach}
                                        </div>
                                        <div style="clear:both">&nbsp;</div>
                                        <p class="profile-help">{l s='You should configure a price rule in value or percentage for one or several prices ranges.' mod='cdiscount'}</p>
                                    </div>
                                    <hr style="width:30%"/>

                                    {if isset($profile_data.aligment_active)}
                                        <label class="profile-obj-title control-label col-lg-3">{l s='Price Alignment' mod='cdiscount'}</label>
                                        <div class="margin-form col-lg-9">
                                            <input type="text" name="profiles[price_align][_key_]" style="width:100px" value="{$profile_data.price_align|escape:'htmlall':'UTF-8'}" class="is-price"/>

                                            <div class="profile-help">{l s='Automatically align the price upon to this percentage, if filled (eg: 10%)' mod='cdiscount'}</div>
                                        </div>
                                        <hr style="width:30%"/>
                                    {/if}

                                    <label class="profile-obj-title control-label col-lg-3">{l s='Shipping Increase/Decrease' mod='cdiscount'}</label>

                                    <div class="margin-form col-lg-9">
                                        <input type="text" name="profiles[shipping_rule][_key_]" style="width:200px" value="{$profile_data.shipping_rule|escape:'htmlall':'UTF-8'}"/>

                                        <div class="profile-help">{l s='Decrease or Increase the shipping fees for this profile (for exemple: -1 will deduce 1 currency unit for the shipping.)'  mod='cdiscount'}</div>
                                    </div>

                                    <label class="profile-obj-title control-label col-lg-3">{l s='Preparation Time' mod='cdiscount'}</label>

                                    <div class="margin-form col-lg-9">
                                        <input type="text" name="profiles[preparation_time][_key_]" style="width:50px" value="{$profile_data.preparation_time|escape:'htmlall':'UTF-8'}"/>

                                        <div class="profile-help">{l s='Lead time before shipping'  mod='cdiscount'}</div>
                                    </div>

                                    {if (isset($cd_profiles.carrier_info) && count($cd_profiles.carrier_info))}
                                        <label class="profile-obj-title control-label col-lg-3">{l s='Free shipping for' mod='cdiscount'}</label>
                                        <div class="margin-form col-lg-9">
                                            {foreach from=$cd_profiles.carrier_info item=carrier_info}
                                                <input type="checkbox" name="profiles[shipping_free][_key_][{$carrier_info.carrier_name|escape:'htmlall':'UTF-8'}]" style="width:20px;height:20px;" value="{$carrier_info.carrier_name|escape:'htmlall':'UTF-8'}" {if (isset($profile_data.shipping_free) && in_array($carrier_info.carrier_name, $profile_data.shipping_free))}checked{/if}  />&nbsp;&nbsp;
                                                <span class="profile-shipping-free-label">{$carrier_info.carrier_label|escape:'quotes':'UTF-8'}</span>&nbsp;&nbsp;&nbsp;&nbsp;
                                            {/foreach}
                                            <div class="profile-help">{l s='Shipping will be free for selected carriers'  mod='cdiscount'}</div>
                                        </div>
                                        <label class="profile-obj-title control-label col-lg-3">&nbsp;</label>
                                        <div class="margin-form col-lg-9">

                                            <input type="checkbox" name="profiles[shipping_include][_key_]" style="width:20px;height:20px;" value="1" {if (isset($profile_data.shipping_include) && $profile_data.shipping_include)}checked{/if}/>&nbsp;&nbsp;
                                            <span class="profile-shipping-free-label">{l s='Include' mod='cdiscount'}</span>&nbsp;
                                            <select name="profiles[shipping_include_percentage][_key_]" style="width:70px;" class="profile-shipping-free-select">
                                                {section name=shipping_include_percent name=percentage loop=101 start=0}
                                                    <option value="{$smarty.section.percentage.index|escape:'htmlall':'UTF-8'}" {if (isset($profile_data.shipping_include_percentage) && $profile_data.shipping_include_percentage == $smarty.section.percentage.index)}selected{/if}>{$smarty.section.percentage.index|escape:'htmlall':'UTF-8'} &#37;</option>
                                                {/section}
                                            </select>&nbsp;
                                            <span class="profile-shipping-free-label">{l s='of calculated shipping into the product price, with a limit of' mod='cdiscount'}</span>&nbsp;
                                            <select name="profiles[shipping_include_limit][_key_]" style="width:70px;" class="profile-shipping-free-select">
                                                <option value="0">-</option>
                                                {section name=shipping_include_percent name=percentage loop=100 start=1}
                                                    <option value="{$smarty.section.percentage.index|escape:'htmlall':'UTF-8'}" {if (isset($profile_data.shipping_include_limit) && $profile_data.shipping_include_limit == $smarty.section.percentage.index)}selected{/if}>{$smarty.section.percentage.index|escape:'htmlall':'UTF-8'} &#37;</option>
                                                {/section}
                                            </select>&nbsp;<span class="profile-shipping-free-label">{l s='product price' mod='cdiscount'}</span>&nbsp;



                                            <div class="profile-help">{l s='If free shipping is activated, applies the selected percentage of calculated shipping in the product price'  mod='cdiscount'}.</div>
                                        </div>
                                    {/if}
                                    <hr style="width:30%"/>

                                    {if isset($cd_profiles.multitenants) && is_array($cd_profiles.multitenants) && count($cd_profiles.multitenants) >= 1}
                                        <label class="profile-obj-title control-label col-lg-3">{l s='Multitenant' mod='cdiscount'}</label>
                                        <div class="margin-form col-lg-9">
                                            {foreach from=$cd_profiles.multitenants item=multitenant}
                                                <span class="profile-multitenant">
                                <input type="checkbox" name="profiles[multitenant][_key_][{$multitenant.Id|intval}]" {if isset($profile_data.multitenant[$multitenant.Id])}checked{/if} value="1"/>
                                <label>{$multitenant.Description|escape:'htmlall':'UTF-8'}</label>
						</span>
                                            {/foreach}
                                            <div class="profile-help">{l s='Select channels'  mod='cdiscount'}</div>
                                        </div>
                                        <hr style="width:30%"/>
                                    {else}
                                        <input type="hidden" name="profiles[multitenant][_key_]" value="0">
                                    {/if}

                                    <label class="profile-obj-title control-label col-lg-3">{l s='CDaV' mod='cdiscount'}</label>
                                    <div class="margin-form col-lg-9">
                    <span>
                            <input type="checkbox" name="profiles[cdav][_key_]" {if isset($profile_data.cdav)}checked{/if} value="1" style="width:20px;height:20px;"/>
                            <label class="cdav-label">{l s='CDiscount a volonte' mod='cdiscount'}</label>
                    </span>
                                        &nbsp;&nbsp;&nbsp;&nbsp;
                                        <span>
                            <input type="text" name="profiles[cdav_max][_key_]" {if isset($profile_data.cdav_max)}value="{$profile_data.cdav_max|escape:'htmlall':'UTF-8'}"{/if} style="width:80px;" class="price" />
                            <label class="cdav-max-label">{l s='Ceiling price for the product to be eligible' mod='cdiscount'}</label>
                    </span>
                                        <div class="profile-help">{l s='Activate CDaV option for this profile, CDaV is a marketing feature, please Google it to understand the meaning'  mod='cdiscount'}</div>
                                    </div>
                                    <hr style="width:30%"/>


                                </div>
                                <!-- Profiles -->
                            </div>
                        </div>
                    {/foreach}
                </div>
            {/if}
        </div>
        <br>

    {else}
        <div class="margin-form col-lg-offset-3">
            {if ($cd_profiles.saved)}
                <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'}">
                    {l s='Your previous configuration is kept saved but XML has not been loaded well, please verify in imports tab.' mod='cdiscount'}
                </div>
            {else}
                <div class="{$alert_class.warning|escape:'htmlall':'UTF-8'}">
                    {l s='XML has not been loaded yet, please verify your credentials and the imports tab.' mod='cdiscount'}
                </div>
            {/if}
        </div>
    {/if}

    {include file="$module_path/views/templates/admin/configure/validate.tpl"}
</div>
