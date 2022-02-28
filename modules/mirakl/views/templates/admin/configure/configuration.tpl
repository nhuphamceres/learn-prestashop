{**
* NOTICE OF LICENSE
*
* This source file is subject to a commercial license from Common-Services Co., Ltd.
* Use, copy, modification or distribution of this source file without written
* license agreement from the SARL SMC is strictly forbidden.
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
* @package    Mirakl
* @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @author     Olivier B.
* @license    Commercial license
* Support by mail  :  support.mirakl@common-services.com
*}

<form action="{$request_uri|escape:'quotes':'UTF-8'}" method="post" autocomplete="off">
    {if (isset($mirakl_context_key))}
        <input type="hidden" name="mirakl_context_key" value="{$mirakl_context_key|escape:'htmlall':'UTF-8'}"/>
    {/if}
    <input type="hidden" id="selected_tab" name="selected_tab" value="{$selected_tab|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" id="mirakl_tools_url" value="{$tools_url|escape:'quotes':'UTF-8'}"/>
    <div style="clear:both"></div>

    <!-- Select marketplace -->
    {include file="$module_path/views/templates/admin/configure/marketplace_selection.tpl"}

    <fieldset class="panel form-horizontal">
        <div id="tabList">
            {foreach from=$mirakl_tab_list item=miraklTab}
                {if $miraklTab.id == 'mirakl'}
                    <div id="conf-mirakl" class="tabItem " style="display:none;">
                        <div class="form-group">
                            <label class="col-lg-3">
                                <h2>{$me_name|escape:'htmlall':'UTF-8'} {$me_version|escape:'htmlall':'UTF-8'}</h2></label>
                            <div class="margin-form col-lg-9">
                                <p class="descriptionBold">
                                    <span style="color: navy;">{$module_description|escape:'htmlall':'UTF-8'}</span><br>
                                    {l s='The following features are provided with this module :' mod='mirakl'}
                                </p>
                                <ul class="descriptionList">
                                    <li>{l s='Retrieve Orders from the Market Place by Web Service' mod='mirakl'}</li>
                                    <li>{l s='Update Orders Status in the Market Place by Web Service' mod='mirakl'}</li>
                                    <li>{l s='Update & Create Products in the Market Place' mod='mirakl'}</li>
                                </ul>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='Informations' mod='mirakl'}</label>
                            <div class="margin-form col-lg-9">
                                <span style="color:navy">{l s='This module is provided by' mod='mirakl'} :</span>
                                Common-Services<br>
                                <br>
                                <span style="color:navy">{l s='Informations, follow up on our blog' mod='mirakl'}:</span><br>
                                <a href="http://www.common-services.com" target="_blank">http://www.common-services.com</a><br>
                                <br>
                                <span style="color:navy">{l s='More informations about us on Prestashop website' mod='mirakl'}
                            :</span><br>
                                <a href="http://www.prestashop.com/fr/agences-web-partenaires/or/common-services"
                                   target="_blank">http://www.prestashop.com/fr/agences-web-partenaires/or/common-services</a><br>
                                <br>
                                <span style="color:navy">{l s='You will appreciate our others modules' mod='mirakl'}
                            :</span><br>
                                <a href="http://addons.prestashop.com/fr/58_common-services" target="_blank">http://addons.prestashop.com/fr/58_common-services</a><br>
                            </div>
                        </div>
                        <br>

                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='Documentation' mod='mirakl'}</label>
                            <div class="margin-form col-lg-9">
                                <div class="col-lg-1"><img src="{$images_url|escape:'htmlall':'UTF-8'}books.png" alt="docs"/>
                                </div>
                                <div class="col-lg-11">
                            <span style="color:red; font-weight:bold;">{l s='Please first read the provided documentation' mod='mirakl'}
                                :</span><br>
                                    <a href="http://documentation.common-services.com/mirakl" target="_blank">http://documentation.common-services.com/mirakl</a>
                                </div>
                            </div>
                        </div>
                        <br>

                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='Support' mod='mirakl'}</label>
                            <div class="margin-form col-lg-9">
                                <div class="col-lg-1">
                                    <img src="{$images_url|escape:'htmlall':'UTF-8'}submit_support_request.png" alt="support">
                                </div>
                                <div class="col-lg-11">
                            <span style="color:red; font-weight:bold;">
                                {l s='The technical support is available by e-mail only.' mod='mirakl'}
                            </span><br>
                                    <span style="color: navy;">
                                {l s='For any support, please provide us' mod='mirakl'} :<br>
                            </span>
                                    <ul>
                                        <li>{l s='A detailled description of the issue or encountered problem' mod='mirakl'}</li>
                                        <li>{l s='Your Pretashop Addons Order ID available in your Prestashop Addons order history' mod='mirakl'}</li>
                                        <li>{l s='Your Prestashop version' mod='mirakl'} : <span
                                                    style="color: red;">Prestashop {$ps_version|escape:'htmlall':'UTF-8'}</span>
                                        </li>
                                        <li>{l s='Your module version' mod='mirakl'} :
                                            <span style="color: red;">{$me_name|escape:'htmlall':'UTF-8'}
                                        v{$version|escape:'htmlall':'UTF-8'}</span></li>
                                    </ul>
                                    <br>
                                    <span style="color:navy">{l s='Support Common-Services' mod='mirakl'} :</span>
                                    <a href="mailto:contact@common-services.com?subject={l s='Support for' mod='mirakl'} {$me_name|escape:'htmlall':'UTF-8'}&body={l s='Dear Support, I am currently having some trouble with your module v%s on my Prestashop v%s.'|sprintf:$version:$ps_version mod='mirakl'}"
                                       title="Email">
                                        contact@common-services.com
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='Licence' mod='mirakl'}</label>
                            <div class="margin-form col-lg-9">
                                <p>
                                    {l s='This module is subject to a commercial license from SARL SMC.' mod='mirakl'}
                                    <br/>
                                    {l s='To obtain a license, please contact us: contact@common-services.com' mod='mirakl'}
                                    <br/>
                                    {l s='In case of acquisition on Prestastore, the invoice itself is a proof of license' mod='mirakl'}
                                    <br/>
                                </p>
                            </div>
                        </div>
        </div>                    
                {/if}

                {if $miraklTab.id == 'informations'}
                    <!-- INFORMATION START -->
                    <div id="conf-informations" class="tabItem" style="display:none;">
                        <h2>{l s='Information' mod='mirakl'}</h2>

                        <!-- Refresh marketplace config file -->
                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='Module Shop Key' mod='mirakl'}</label>
                            <div align="left" class="margin-form col-lg-9">
                                <input type="text" value="{$md5_shop_name}" readonly
                                       style="width: 300px; display: inline-block; padding: 6px 8px;">&nbsp;&nbsp;
                                <button type="button" id="refresh-config-file" class="btn btn-default" style="width: 250px;">
                                    <span>{l s='Refresh configuration file' mod='mirakl'}</span>
                                    <img src="{$images_url}loader-connection.gif" alt="..." style="display: none;">
                                </button>
                                <br>
                                <br>
                                <div class="{$alert_class.info}" {if $current_mkp == 'temp'}style="font-size: 30px;padding-left: 90px;font-weight: bold;"{/if}>
                                    <p>
                                        {l s='Please communicate this key to our support team so that we can enable your Mirakl marketplaces for your module account' mod='mirakl'}
                                        :<br>
                                        <a href="mailto:support.mirakl@common-services.com?subject={l s='Mirakl - Add marketplace' mod='mirakl'}&body="
                                           title="Email">
                                            support.mirakl@common-services.com
                                        </a>
                                    </p>
                                </div>
                                <hr style="width:30%;margin-top:10px;"/>
                            </div>

                            <input type="hidden"
                                   value="{l s='Configuration file deleted, please reload configuration.' mod='mirakl'}"
                                   id="mirakl-message-conf-file-ok"/>
                            <input type="hidden" value="{l s='An error occurred...' mod='mirakl'}"
                                   id="mirakl-message-conf-file-nok"/>
                        </div>
                        <!-- End Refresh marketplace config file -->

                        <br/>
                        <h4>{l s='Configuration Check' mod='mirakl'}</h4>

                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='PHP Settings' mod='mirakl'}</label>
                            <div align="left" class="margin-form me-info col-lg-9">
                                {if ! $me_informations.php_info_ok}
                                    {foreach from=$me_informations.php_infos item=php_info}
                                        <p class="{$php_info.level|escape:'quotes':'UTF-8'}">
                                            <span>{$php_info.message|escape:'quotes':'UTF-8'}</span>
                                            {if isset($php_info.link)}
                                                <br/>
                                                <span>{l s='Please read more about it on:' mod='mirakl'}
                                            <a href="{$php_info.link|escape:'htmlall':'UTF-8'}"
                                               target="_blank">{$php_info.link|escape:'htmlall':'UTF-8'}</a></span>
                                            {/if}
                                        </p>
                                    {/foreach}
                                {else}
                                    <p class="{$alert_class.success|escape:'htmlall':'UTF-8'}">
                                        <span>{l s='Your PHP configuration for the module has been checked and passed successfully...' mod='mirakl'}</span>
                                    </p>
                                {/if}
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='Prestashop Settings' mod='mirakl'}</label>
                            <div align="left" class="margin-form me-info col-lg-9">
                                {if ! $me_informations.prestashop_info_ok}
                                    {foreach from=$me_informations.prestashop_infos item=prestashop_info}
                                        <p class="{$prestashop_info.level|escape:'htmlall':'UTF-8'}">
                                            <span>{$prestashop_info.message|escape:'htmlall':'UTF-8'}</span>
                                            {if isset($prestashop_info.link)}
                                                <br/>
                                                <span>{l s='Please read more about it on:' mod='mirakl'}
                                            : <a href="{$prestashop_info.link|escape:'htmlall':'UTF-8'}"
                                                 target="_blank">{$prestashop_info.link|escape:'htmlall':'UTF-8'}</a></span>
                                            {/if}
                                        </p>
                                    {/foreach}
                                {else}
                                    <p class="{$alert_class.success|escape:'htmlall':'UTF-8'}">
                                        <span>{l s='Your Prestashop configuration for the module has been checked and passed successfully...' mod='mirakl'}</span>
                                    </p>
                                {/if}
                            </div>
                        </div>
                        <h2>{l s='Additionnal Support Informations' mod='mirakl'}</h2>
                        <br/>

                        <div class="form-group">
                            <label class="control-label col-lg-3">&nbsp;</label><br/>
                            <div align="left" class="margin-form amz-info col-lg-9">
                                <input type="button" class="button btn" id="support-informations-prestashop"
                                       value="{l s='Prestashop Info' mod='mirakl'}"
                                       rel="{$me_informations.support_informations_url|escape:'htmlall':'UTF-8'}&action=prestashop-info"/>&nbsp;&nbsp;
                                <input type="button" class="button btn" id="support-informations-php"
                                       value="{l s='PHP Info' mod='mirakl'}"
                                       rel="{$me_informations.support_informations_url|escape:'htmlall':'UTF-8'}&action=php-info"/>&nbsp;&nbsp;
                                <img src="{$me_informations.images|escape:'htmlall':'UTF-8'}loading.gif"
                                     alt="{l s='Support Informations' mod='mirakl'}"
                                     class="support-informations-loader"/><br/><br/>
                                <div id="support-informations-content">
                                </div>
                            </div>
                        </div>


                    </div>
                    <!-- INFORMATIONS END -->                    
                {/if}

                {if $miraklTab.id == 'credentials'}
                    <!-- CREDENTIALS START -->
                    <div id="conf-credentials" class="tabItem" style="display:none;">
                        <h2>{l s='Authentification' mod='mirakl'}</h2>

                        <div class="margin-form col-lg-offset-3">
                            <div style="font-size:1.2em;line-height:140%;" class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                                <ul>
                                    <li>{l s='Please read our online tutorial' mod='mirakl'}:</li>
                                    <li>{$me_tutorial_credentials|escape:'quotes':'UTF-8'}</li>
                                </ul>
                            </div>
                        </div>


                        <div class="form-group">
                            <label class="control-label col-lg-3" for="password">{l s='API Key' mod='mirakl'}</label>
                            <div class="margin-form col-lg-9">
                                <input id="mirakl_api_key" type="password" style="width: 300px;" name="mirakl_api_key"
                                       value="{$me_mirakl_api_key|escape:'htmlall':'UTF-8'}"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-lg-3">&nbsp;</label>
                            <div class="margin-form col-lg-9">
                                <input id="connection-check" class="button btn btn-default" type="button" style="width: 100px;"
                                       name="connection-check" value="{l s='Verify' mod='mirakl'}"/>&nbsp;&nbsp;
                                <img src="{$images_url|escape:'htmlall':'UTF-8'}loader-connection.gif"
                                     alt="{l s='Verify' mod='mirakl'}" id="connection-check-loader"
                                     style="position:relative;display:none;"/>
                                <br/>
                                <br/>
                                <div id="mirakl-response">
                                    <div class="yes {$alert_class.success|escape:'htmlall':'UTF-8'}"
                                         style="display:none;"></div>
                                    <div class="no {$alert_class.danger|escape:'htmlall':'UTF-8'}" style="display:none;"></div>
                                </div>
                            </div>
                        </div>

                        <hr style="width:600px;"/>


                        <div class="form-group">

                            <label class="control-label col-lg-3" for="debug">{l s='Debug Mode' mod='mirakl'}</label>
                            <div class="margin-form col-lg-9">
                                <input id="debug" type="checkbox" name="debug" value="1" {$me_debug|escape:'htmlall':'UTF-8'} />
                                <span style="font-size:1.2em;">&nbsp;&nbsp;{l s='Yes' mod='mirakl'}</span>
                                <p>{l s='Debug mode. Enable traces for debugging and developpment purpose.' mod='mirakl'}
                                    <br/>
                                    <b {$me_debug_style|escape:'htmlall':'UTF-8'}>{l s='In exploitation this option must not be active !' mod='mirakl'}</b>
                                </p>
                            </div>
                        </div>

                        {include file="$module_path/views/templates/admin/configure/validate.tpl"}
                    </div>
                    <!-- CREDENTIALS END -->                    
                {/if}

                {if $miraklTab.id == 'profiles'}
                    <!--PROFILES START -->
                    <div id="conf-profiles" class="tabItem" style="display:none;">
                        <h2>{l s='Profiles' mod='mirakl'}</h2>

                        <div class="margin-form col-lg-offset-3">
                            <div style="font-size:1.2em;line-height:140%;" class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                                <ul>
                                    <li>{l s='Please read our online tutorial' mod='mirakl'}:</li>
                                    {* No escape because already made in the PHP *}
                                    <li>{$me_tutorial_profiles|escape:'quotes':'UTF-8'}</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Import old profiles -->
                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='Migrate profiles from old module' mod='mirakl'}</label>
                            <div class="margin-form col-lg-6">
                                <input id="old_profiles_encoded" type="text" placeholder="{l s='Encoded profiles' mod='mirakl'}"
                                       style="margin: 0;">
                            </div>
                            <button id="old_profiles_restore" type="button"
                                    class="col-lg-2 btn btn-default">{l s='Migrate' mod='mirakl'}</button>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='Profiles Configuration' mod='mirakl'}</label>
                            <div class="margin-form col-lg-9" class="profiles">
                                <div id="profile-add">
                                    <span class="profile-add">{l s='Add a profile to the list' mod='mirakl'}</span>&nbsp;&nbsp;
                                    <span class="profile-add-img"><img
                                                src="{$images_url|escape:'htmlall':'UTF-8'}add.png"/></span>
                                </div>
                                <div class="cleaner"></div>
                            </div>
                        </div>

                        <!-- Container to receive the profiles -->
                        <div id="profile-container" class="form-group">
                            {if isset($me_profiles.profiles_data)}
                                <input type="hidden" id="hierarchies_url"
                                       value="{$me_profiles.hierarchies_url|escape:'htmlall':'UTF-8'}"/>
                                <div id="profile-items">
                                    {foreach from=$me_profiles.profiles_data item=profile_data}
                                        <div class="profile-content">
                                            {* Profile header *}
                                            {if $profile_data.name}
                                                {* No escape because already made in the PHP *}
                                                <div class="profile-header"
                                                     id="profile-header-{$profile_data.profile_id|escape:'quotes':'UTF-8'}">
                                                    <br/>
                                                    <label class="control-label col-lg-3"
                                                           style="color:navy">{$profile_data.name|escape:'htmlall':'UTF-8'}</label>
                                                    <div class="margin-form col-lg-9">
                                                        <table class="profile-table">
                                                            <tr>
                                                                <td style="width: 85%;">
                                                                    &nbsp;
                                                                </td>
                                                                <td align="center" style="width: 50px">
                                                                    <img src="{$images_url|escape:'htmlall':'UTF-8'}cross.png"
                                                                         class="profile-del-img" alt="Delete"
                                                                         rel="{$profile_data.profile_id|escape:'htmlall':'UTF-8'}"/>
                                                                </td>
                                                                <td align="center" style="width: 50px">
                                                                    <img src="{$images_url|escape:'htmlall':'UTF-8'}edit.png"
                                                                         class="profile-edit-img" alt="Edit"
                                                                         rel="{$profile_data.profile_id|escape:'htmlall':'UTF-8'}"/>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </div>
                                            {/if}

                                            {* Profile DIV *}
                                            {* No escape because already made in the PHP *}
                                            <div {$profile_data.profile_div|escape:'quotes':'UTF-8'}
                                                    class="profile-create profile form-group" style="display: none;"
                                                    rel="{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{/if}">
                                                {if !$profile_data.name}
                                                    <span class="profile-del-2">{l s='Remove this profile from the list' mod='mirakl'}
                                                &nbsp;&nbsp;<img src="{$images_url|escape:'htmlall':'UTF-8'}cross.png"
                                                                 class="model-del-img2"/></span>
                                                    <h2>{l s='New Profile' mod='mirakl'}</h2>
                                                {/if}

                                                <label class="profile-obj-title control-label col-lg-3"
                                                       style="color:green;">{l s='Profile Name'  mod='mirakl'}</label>
                                                <div class="margin-form col-lg-9">
                                                    <input type="text"
                                                           name="profiles[name][{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{else}_key_{/if}]"
                                                           class="profile-name"
                                                           value="{$profile_data.name|escape:'htmlall':'UTF-8'}"
                                                           style="width:250px"/>
                                                    <p class="profile-help">
                                                        {l s='Name of profile. Use a friendly name to remember it.' mod='mirakl'}
                                                        <br/>
                                                        {l s='Do not forget to click on the save button at the bottom right of the page !'  mod='mirakl'}
                                                    </p>
                                                </div>
                                                {*<label class="profile-obj-title control-label col-lg-3">{l s='Price Formula' mod='mirakl'}</label>
                                                <div class="margin-form col-lg-9">
                                                    <input type="text" name="profiles[formula][]" style="width:100px" value="{$profile_data.formula|escape:'htmlall':'UTF-8'}" />
                                                    <p class="profile-help">{l s='Apply a specific price formula for selected categories which will override the main setting (Default Price Formula)' mod='mirakl'}</p>
                                                </div>*}
                                                <label class="profile-obj-title control-label col-lg-3">{l s='Price Rule' mod='mirakl'}</label>
                                                <div class="margin-form col-lg-9">
                                                    <select name="profiles[price_rule][{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{else}_key_{/if}][type]"
                                                            class="price-rule-type" value=""
                                                            style="display: inline; max-width:150px;"/>
                                                    <option
                                                            value="percent"
                                                            {if ($profile_data.price_rule.type == 'percent')}selected{/if}
                                                    >
                                                        {l s='Percentage' mod='mirakl'}
                                                    </option>
                                                    {if isset($profile_data.price_rule.currency_sign)}
                                                        <option
                                                                value="value"
                                                                {if ($profile_data.price_rule.type == 'value')}selected{/if}
                                                        >
                                                            {l s='Value' mod='mirakl'}
                                                        </option>
                                                    {/if}
                                                    </select>
                                                    &nbsp;&nbsp;
                                                    <div id="default-price-rule-{$id_lang|intval}" class="default-price-rule"
                                                         style="display: inline-block;">
                                                        {foreach from=$profile_data.price_rule.rule.from key=index item=value}
                                                            <div class="price-rule">
                                                                <input type="text"
                                                                       name="profiles[price_rule][{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{else}_key_{/if}][rule][from][]"
                                                                       rel="from" style="width:50px"
                                                                       value="{$profile_data.price_rule.rule.from[$index]|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;{$profile_data.price_rule.currency_sign|escape:'htmlall':'UTF-8'}
                                                                <span>
                                                            &nbsp;&nbsp;<img
                                                                            src="{$images_url|escape:'htmlall':'UTF-8'}slash.png"
                                                                            class="price-rule-slash" alt=""/>&nbsp;&nbsp;
                                                        </span>
                                                                <input type="text"
                                                                       name="profiles[price_rule][{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{else}_key_{/if}][rule][to][]"
                                                                       rel="to" style="width:50px"
                                                                       value="{$profile_data.price_rule.rule.to[$index]|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;{$profile_data.price_rule.currency_sign|escape:'htmlall':'UTF-8'}
                                                                <span>
                                                            &nbsp;&nbsp;<img
                                                                            src="{$images_url|escape:'htmlall':'UTF-8'}next.png"
                                                                            class="price-rule-next" alt=""/>&nbsp;&nbsp;
                                                        </span>
                                                                <select name="profiles[price_rule][{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{else}_key_{/if}][rule][percent][]"
                                                                        rel="percent"
                                                                        style="width:100px;{if ($profile_data.price_rule.type != 'percent')}display:none;{/if}"/>
                                                                <option></option>
                                                                {section name=price_rule_percent loop=99}
                                                                    <option value="{$smarty.section.price_rule_percent.iteration|escape:'htmlall':'UTF-8'}"
                                                                            {if $profile_data.price_rule.rule.percent[$index] == $smarty.section.price_rule_percent.iteration}selected{/if}>{$smarty.section.price_rule_percent.iteration|escape:'htmlall':'UTF-8'}
                                                                        &#37;
                                                                    </option>
                                                                {/section}
                                                                <option disabled>--</option>
                                                                {section name=price_rule_percent loop=99}
                                                                    <option value="-{$smarty.section.price_rule_percent.iteration|escape:'htmlall':'UTF-8'}"
                                                                            {if $profile_data.price_rule.rule.percent[$index] == ($smarty.section.price_rule_percent.iteration * -1)}selected{/if}>
                                                                        -{$smarty.section.price_rule_percent.iteration|escape:'htmlall':'UTF-8'}
                                                                        &#37;
                                                                    </option>
                                                                {/section}
                                                                </select>
                                                                {if isset($profile_data.price_rule.currency_sign)}
                                                                    <select
                                                                            name="profiles[price_rule][{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{else}_key_{/if}][rule][value][]"
                                                                            rel="value"
                                                                            style="width:100px;{if ($profile_data.price_rule.type != 'value')}display:none;{/if}"
                                                                    >
                                                                        <option></option>
                                                                        {foreach range(0, 0.99, 0.01) as $val}
                                                                            <option value="{$val}" {if $profile_data.price_rule.rule.value[$index] == $val}selected{/if}>{$val} {$profile_data.price_rule.currency_sign|escape:'htmlall':'UTF-8'}</option>
                                                                        {/foreach}
                                                                        {section name=price_rule_value loop=99}
                                                                            <option value="{$smarty.section.price_rule_value.iteration|escape:'htmlall':'UTF-8'}"
                                                                                    {if $profile_data.price_rule.rule.value[$index] == $smarty.section.price_rule_value.iteration}selected{/if}>{$smarty.section.price_rule_value.iteration|escape:'htmlall':'UTF-8'} {$profile_data.price_rule.currency_sign|escape:'htmlall':'UTF-8'}</option>
                                                                        {/section}
                                                                        <option disabled>--</option>
                                                                        {foreach range(0, 0.99, 0.01) as $val}
                                                                            <option value="-{$val}" {if $profile_data.price_rule.rule.value[$index] == -$val}selected{/if}>-{$val} {$profile_data.price_rule.currency_sign|escape:'htmlall':'UTF-8'}</option>
                                                                        {/foreach}
                                                                        {section name=price_rule_value loop=99}
                                                                            <option value="-{$smarty.section.price_rule_value.iteration|escape:'htmlall':'UTF-8'}"
                                                                                    {if $profile_data.price_rule.rule.value[$index] == ($smarty.section.price_rule_value.iteration * -1)}selected{/if}>
                                                                                -{$smarty.section.price_rule_value.iteration|escape:'htmlall':'UTF-8'} {$profile_data.price_rule.currency_sign|escape:'htmlall':'UTF-8'}</option>
                                                                        {/section}
                                                                    </select>
                                                                {/if}
                                                                <span class="price-rule-add"
                                                                      {if $index > 0}style="display:none;"{/if}><img
                                                                            src="{$images_url|escape:'htmlall':'UTF-8'}plus.png"
                                                                            alt="{l s='Add a rule' mod='mirakl'}"/></span>
                                                                <span class="price-rule-remove"
                                                                      {if $index == 0}style="display:none;"{/if}><img
                                                                            src="{$images_url|escape:'htmlall':'UTF-8'}minus.png"
                                                                            alt="{l s='Remove a rule' mod='mirakl'}"/></span>
                                                            </div>
                                                        {/foreach}
                                                    </div>
                                                    <div style="clear:both">&nbsp;</div>
                                                    <p>{l s='You should configure a price rule in value or percentage for one or several prices ranges.' mod='mirakl'}</p>
                                                </div>
                                                <label class="profile-obj-title control-label col-lg-3">{l s='Shipping Increase/Decrease' mod='mirakl'}</label>
                                                <div class="margin-form col-lg-9">
                                                    <input type="text"
                                                           name="profiles[shipping_rule][{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{else}_key_{/if}]"
                                                           style="width:200px"
                                                           value="{$profile_data.shipping_rule|escape:'htmlall':'UTF-8'}"/>
                                                    <p class="profile-help">{l s='Decrease or Increase the shipping fees for this profile (for exemple: -1 will deduce 1 currency unit for the shipping.)' mod='mirakl'}</p>
                                                </div>
                                                <label class="profile-obj-title control-label col-lg-3">{l s='Warranty' mod='mirakl'}</label>
                                                <div class="margin-form col-lg-9">
                                                    <input type="text"
                                                           name="profiles[warranty][{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{else}_key_{/if}]"
                                                           style="width:50px"
                                                           value="{$profile_data.warranty|escape:'htmlall':'UTF-8'}"/>
                                                    <p class="profile-help">{l s='Manufacturer warranty duration (in digits in years, ie: 2)'  mod='mirakl'}</p>
                                                </div>
                                                <label class="profile-obj-title control-label col-lg-3">{l s='Combinations Parameters' mod='mirakl'}</label>
                                                <div class="margin-form col-lg-9">
                                                    <input type="radio"
                                                           name="profiles[combinations_attr][{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{else}_key_{/if}]"
                                                           value="{$me_profiles.attributes_short|intval}"
                                                           {if $profile_data.combinations_attr == $me_profiles.attributes_short || $profile_data.combinations_attr == null}checked="checked"{/if}/>&nbsp;<span>{l s='Short Attributes' mod='mirakl'}</span>&nbsp;&nbsp;&nbsp;
                                                    <input type="radio"
                                                           name="profiles[combinations_attr][{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{else}_key_{/if}]"
                                                           value="{$me_profiles.attributes_long|intval}"
                                                           {if $profile_data.combinations_attr == $me_profiles.attributes_long}checked="checked"{/if}/>&nbsp;<span>{l s='Long Attributes' mod='mirakl'}</span>&nbsp;&nbsp;&nbsp;
                                                    <input type="radio"
                                                           name="profiles[combinations_attr][{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{else}_key_{/if}]"
                                                           value="{$me_profiles.attributes_no|intval}"
                                                           {if $profile_data.combinations_attr == $me_profiles.attributes_no}checked="checked"{/if}/>&nbsp;<span>{l s='No Attributes' mod='mirakl'}</span>
                                                    <p class="profile-help">{l s='Long Attributes' mod='mirakl'}
                                                        : {l s='Export long attributes title instead short. (ie: Heigth - 30cm against 30cm)'  mod='mirakl'}</p>
                                                    <p class="profile-help">{l s='No Attributes' mod='mirakl'}
                                                        : {l s='Do not export attributes in product title'  mod='mirakl'}</p>
                                                </div>
                                                <label class="profile-obj-title control-label col-lg-3">{l s='Min Quantity Alert' mod='mirakl'}</label>
                                                <div class="margin-form col-lg-9">
                                                    <input type="text"
                                                           name="profiles[min_quantity_alert][{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{else}_key_{/if}]"
                                                           style="width:100px"
                                                           value="{$profile_data.min_quantity_alert|escape:'htmlall':'UTF-8'}"/>
                                                    <p class="profile-help">{l s='Minimum quantity of the offer' mod='mirakl'}</p>
                                                </div>
                                                <label class="profile-obj-title control-label col-lg-3">{l s='Logistic Class' mod='mirakl'}</label>
                                                <div class="margin-form col-lg-9">
                                                    <input type="text"
                                                           name="profiles[logistic_class][{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{else}_key_{/if}]"
                                                           style="width:100px"
                                                           value="{$profile_data.logistic_class|escape:'htmlall':'UTF-8'}"/>
                                                </div>

                                                <!-- marketplace specific fields -->
                                                {if isset($profile_data.specific_fields)}
                                                    <label class="profile-obj-title control-label col-lg-3">
                                                        {if $profile_data.specific_fields|count == 1}
                                                            {l s='Specific Field' mod='mirakl'}
                                                        {else}
                                                            {l s='Specific Fields' mod='mirakl'}
                                                        {/if}
                                                    </label>
                                                    <div class="margin-form col-lg-9">
                                                        {foreach from=$profile_data.specific_fields key=fieldName item=specificField}
                                                            {if $specificField.type == 'input'}
                                                                <input type="text"
                                                                       title="{$specificField.label}"
                                                                       name="profiles[specific_fields][{$fieldName}][{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{else}_key_{/if}]"
                                                                       value="{$specificField.selected}"/>
                                                            {else}
                                                                <select title="{$specificField.label}"
                                                                        name="profiles[specific_fields][{$fieldName}][{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{else}_key_{/if}]"
                                                                        style="display: inline; max-width:150px;"
                                                                        value="{$specificField.selected}">
                                                                    <option></option>
                                                                    {foreach from=$specificField.value item=fieldValue}
                                                                        <option value="{$fieldValue}" {if $specificField.selected == $fieldValue}selected{/if}>
                                                                            {$fieldValue}
                                                                        </option>
                                                                    {/foreach}
                                                                </select>    
                                                            {/if}
                                                            <p class="profile-help">{$specificField.label}</p>
                                                        {/foreach}
                                                    </div>
                                                {/if}

                                                {if isset($profile_data.additionnals) && is_array($profile_data.additionnals) && count($profile_data.additionnals)}
                                                    {foreach from=$profile_data.additionnals item=additionnal}
                                                        <label class="profile-obj-title control-label col-lg-3">{$additionnal.mirakl|escape:'htmlall':'UTF-8'}</label>
                                                        <div class="margin-form col-lg-9">
                                                            <select name="profiles[{$additionnal.mirakl|escape:'htmlall':'UTF-8'}][{if $profile_data.profile_id != '_key_'}{$profile_data.profile_id|intval}{else}_key_{/if}]"
                                                                    class="fixed-width-xl">
                                                                <option value="" disabled
                                                                        style="color:grey">{l s='Choose a field' mod='mirakl'}</option>
                                                                <option value=""></option>

                                                                {if isset($profile_data.attributes) && is_array($profile_data.attributes) && count($profile_data.attributes)}
                                                                    <option disabled
                                                                            style="color:silver">{l s='Attributes' mod='mirakl'}</option>
                                                                    {foreach from=$profile_data.attributes item=attribute}
                                                                        <option value="a-{$attribute.id_attribute_group|escape:'htmlall':'UTF-8'}"
                                                                                {if isset($additionnal.selected) && $additionnal.selected.type == 'a' && $additionnal.selected.id == $attribute.id_attribute_group}selected{/if} >
                                                                            &nbsp;&nbsp;{$attribute.name|escape:'htmlall':'UTF-8'}</option>
                                                                    {/foreach}
                                                                {/if}

                                                                {if isset($profile_data.features) && is_array($profile_data.features) && count($profile_data.features)}
                                                                    <option disabled
                                                                            style="color:silver">{l s='Features' mod='mirakl'}</option>
                                                                    {foreach from=$profile_data.features item=feature}
                                                                        <option value="f-{$feature.id_feature|escape:'htmlall':'UTF-8'}"
                                                                                {if isset($additionnal.selected) && $additionnal.selected.type == 'f' && $additionnal.selected.id == $feature.id_feature}selected{/if}>
                                                                            &nbsp;&nbsp;{$feature.name|escape:'htmlall':'UTF-8'}</option>
                                                                    {/foreach}
                                                                {/if}

                                                                {if isset($profile_data.prestashop_fields) && is_array($profile_data.prestashop_fields) && count($profile_data.prestashop_fields)}
                                                                    <option disabled
                                                                            style="color:silver">{l s='Field' mod='mirakl'}</option>
                                                                    {foreach from=$profile_data.prestashop_fields key=id item=prestashop_field}
                                                                        <option value="p-{$id|escape:'htmlall':'UTF-8'}"
                                                                                {if isset($additionnal.selected) && $additionnal.selected.type == 'p' && $additionnal.selected.id == $id}selected{/if} >
                                                                            &nbsp;&nbsp;{$prestashop_field|escape:'htmlall':'UTF-8'}</option>
                                                                    {/foreach}
                                                                {/if}

                                                            </select>
                                                        </div>
                                                    {/foreach}
                                                {/if}

                                                <hr style="width:30%"/>
                                            </div>
                                        </div>
                                    {/foreach}
                                </div>
                            {/if}
                        </div>

                        {include file="$module_path/views/templates/admin/configure/validate.tpl"}
                    </div>
                    <!--PROFILES END-->                    
                {/if}

                {if $miraklTab.id == 'categories'}
                    <!--CATEGORIES START -->
                    <div id="conf-categories" class="tabItem" style="display:none;">
                        <h2>{l s='Categories' mod='mirakl'}</h2>

                        <div class="margin-form col-lg-offset-3">
                            <div style="font-size:1.2em;line-height:140%;" class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                                <ul>
                                    <li>{l s='Please read our online tutorial' mod='mirakl'}:</li>
                                    <li>{$me_tutorial_categories|escape:'quotes':'UTF-8'}</li>
                                </ul>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-lg-3">&nbsp;</label>
                            <div class="margin-form col-lg-9">
                                <table cellspacing="0" cellpadding="0" width="100%" class="table">
                                    <tr class="active">
                                        <th style="margin:0"><input type="checkbox" name="checkme"/></th>
                                        <th>{l s='Name' mod='mirakl'}</th>
                                        <th style="text-align:right;padding-right:60px; width: 250px;">{l s='Profiles Mapping' mod='mirakl'}</th>
                                    </tr>
                                    {if isset($me_categories)  && is_array($me_categories.list) && count($me_categories.list)}
                                        {*{$me_categories.html|escape:'quotes':'UTF-8'}*}
                                        {foreach $me_categories.list as $id_category => $details}
                                            <tr class="cat-line{($details.alt_row|intval) ? ' alt_row' : ''}">
                                                <td>
                                                    <input type="checkbox" name="category[]"
                                                           class="category{($details.id_category_default|intval == $id_category|intval) ? ' id_category_default' : ''}"
                                                           id="category_{$id_category|intval}"
                                                           value="{$id_category|intval}" {$details.checked|escape:'htmlall':'UTF-8'}/>
                                                </td>
                                                <td style="cursor:pointer">
                                                    <img src="{$details.img_level|escape:'htmlall':'UTF-8'}" alt=""/>
                                                    &nbsp;<label for="category_{$id_category|intval}"
                                                                 class="t">{$details.name|escape:'htmlall':'UTF-8'}</label>
                                                </td>
                                                <td>
                                                    <select rel="profile2category[{$id_category|intval}]"
                                                            style="width:180px;margin-right:10px;"
                                                            name="profile2category[{$id_category|intval}]">
                                                        <option value="">{l s='Please choose a profile' mod='mirakl'}</option>
                                                        {foreach $me_categories.profiles.name as $id_profile => $profile}
                                                            {if !is_numeric($id_profile)}
                                                                {continue}
                                                            {/if}
                                                            <option value="{$profile|escape:'htmlall':'UTF-8'}"
                                                                    {if $profile == $details.profile}selected="selected"{/if}>{$profile|escape:'htmlall':'UTF-8'}</option>
                                                        {/foreach}
                                                    </select>
                                                    &nbsp;<span class="arrow-cat-duplicate"></span>
                                                </td>
                                            </tr>
                                        {/foreach}
                                    {else}
                                        <tr>
                                            <td colspan="3">
                                                {l s='No category were found.' mod='mirakl'}
                                            </td>
                                        </tr>
                                    {/if}
                                </table>
                                <p>{l s='Map the Prestashop category to a normalized Mirakl category' mod='mirakl'}<br/>
                                </p>
                            </div>
                        </div>

                        {include file="$module_path/views/templates/admin/configure/validate.tpl"}
                    </div>
                    <!--CATEGORIES END-->                    
                {/if}

                {if $miraklTab.id == 'transport'}
                    <!--TRANSPORT START -->
                    {if isset($me_transport) && $me_transport}
                        <div id="conf-transport" class="tabItem" style="display:none;">
                            <h2>{l s='Transport' mod='mirakl'}</h2>

                            <div class="margin-form col-lg-offset-3">
                                <div style="font-size:1.2em;line-height:140%;"
                                     class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                                    <ul>
                                        <li>{l s='Please read our online tutorial' mod='mirakl'}:</li>
                                        {* No escape because already made in the PHP *}
                                        <li>{$me_tutorial_transport|escape:'quotes':'UTF-8'}</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-lg-3"
                                       style="color:grey">{l s='Shipping Delay' mod='mirakl'}</label>
                                <div class="margin-form col-lg-9">
                                    <br>
                                    <br>
                                </div>
                                <label class="control-label col-lg-3"
                                       style="color:grey">{l s='Delivery time' mod='mirakl'}</label>
                                <div class="margin-form col-lg-9">
                                    <input id="delivery_time" type="text" style="width:50px;" name="delivery_time"
                                           value="{$me_transport.delivery_time|default:1|escape:'htmlall':'UTF-8'}"/>&nbsp;{l s='Days' mod='mirakl'}
                                    <br/>
                                    <span class="input_note">{l s='Please indicate a shipping delay you are used to apply for your shipping' mod='mirakl'}</span>
                                    <hr>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-lg-3"
                                       style="color:grey">{l s='Shipping Fees' mod='mirakl'}</label>
                                <div class="margin-form col-lg-9">
                                    <br>
                                    <br>
                                </div>
                                <label class="control-label col-lg-3" style="color:grey">{l s='Additional' mod='mirakl'}</label>
                                <div class="margin-form col-lg-9">
                                    <input id="additional_shipping_price" class="price" type="text" style="width:50px;"
                                           name="additional_shipping_price"
                                           value="{$me_transport.additional_shipping_price|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;
                                    <span class="input_note">{l s='Additional Shipping fees for product delivery (%)' mod='mirakl'}</span>
                                    <hr>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-lg-3" style="color:grey">{l s='Carriers' mod='mirakl'}</label>
                                <div class="margin-form col-lg-9">
                                    <br>
                                    <br>
                                </div>

                                <label class="control-label col-lg-3">{l s='Standard' mod='mirakl'}</label>
                                <div class="margin-form col-lg-9">
                                    <select name="carrier" style="width:500px;">
                                        <option value="" disabled
                                                style="color:grey">{l s='Choose an appropriate carrier for standard shipping' mod='mirakl'}</option>
                                        {foreach from=$me_transport.ps_carriers item=ps_carrier}
                                            <option value="{$ps_carrier.id_carrier|intval}"
                                                    {if $ps_carrier.id_carrier == $me_transport.std_carrier_selected}selected{/if}>
                                                {$ps_carrier.carrier_name|escape:'htmlall':'UTF-8'}
                                            </option>
                                        {/foreach}
                                    </select>
                                </div>

                                <div style="display: none;"><!-- future use -->
                                    <label class="control-label col-lg-3">{l s='Relay' mod='mirakl'}</label>
                                    <div class="margin-form col-lg-9">
                                        <select name="carrier_relay" style="width:500px;">
                                            <option value="" disabled
                                                    style="color:grey">{l s='Choose an appropriate carrier for relay shipping' mod='mirakl'}</option>
                                            <option></option>
                                            {foreach from=$me_transport.ps_carriers item=ps_carrier}
                                                <option value="{$ps_carrier.id_carrier|intval}"
                                                        {if $ps_carrier.id_carrier == $me_transport.relay_carrier_selected}selected{/if}>
                                                    {$ps_carrier.carrier_name|escape:'htmlall':'UTF-8'}
                                                </option>
                                            {/foreach}
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {*todo: Translation*}
                            <div class="form-group">
                                <div class="col-lg-3" style="text-align: right;">
                                    <label class="control-label">
                                        Incoming Order - Carrier Mapping
                                    </label>
                                    <div>
                                        <a href="#menu-credentials" onClick="$('#menu-credentials').click()">Verify connection to update the list of Mirakl carriers</a>
                                    </div>
                                </div>
                                <div class="margin-form col-lg-9 incoming_carrier_mapping_wrapper">
                                    <div class="row">
                                <span class="incoming_carrier_mapping_add">
                                    <img src="{$images_url|escape:'quotes':'UTF-8'}plus.png" alt="Add mapping"/>
                                </span>
                                    </div>
                                    {include file="$module_path/views/templates/admin/configure/transport_carrier_incoming_mapping_entry.tpl"
                                    images_url=$images_url placeholder=true
                                    mkpCarriers=$me_transport.mkp_carriers psCarriers=$me_transport.ps_carriers}
                                    {foreach from=$me_transport.incoming_mapping key=mappingId item=incomingMapping}
                                        {include file="$module_path/views/templates/admin/configure/transport_carrier_incoming_mapping_entry.tpl"
                                        images_url=$images_url placeholder=false
                                        mkpCarriers=$me_transport.mkp_carriers psCarriers=$me_transport.ps_carriers
                                        mappingId=$mappingId mapping=$incomingMapping}
                                    {/foreach}
                                </div>
                            </div>

                            {include file="$module_path/views/templates/admin/configure/validate.tpl"}
                        </div>
                    {/if}
                    <!--TRANSPORT END -->                    
                {/if}

                {if $miraklTab.id == 'orders'}
                    <!--ORDERS START -->
                    {if isset($me_orders) && $me_orders}
                        <div id="conf-orders" class="tabItem" style="display:none;"><br/><br/>
                            <h2>{l s='Orders' mod='mirakl'}</h2>

                            <div class="margin-form col-lg-offset-3">
                                <div style="font-size:1.2em;line-height:140%;"
                                     class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                                    <ul>
                                        <li>{l s='Please read our online tutorial' mod='mirakl'}:</li>
                                        {* No escape because already made in the PHP *}
                                        <li>{$me_tutorial_orders|escape:'quotes':'UTF-8'}</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="form-group">
                                {foreach from=$me_orders.mi_order_states item=mi_order_state name=mi_order_state_loop}
                                    {if $mi_order_state.enable}
                                        <label class="control-label col-lg-3">
                                            {if $smarty.foreach.mi_order_state_loop.first}{l s='Orders Statuses' mod='mirakl'}{/if}
                                        </label>
                                        <div class="margin-form col-lg-9">
                                            <select name="orderstate[{$mi_order_state.name|escape:'htmlall':'UTF-8'}]" style="width:500px;">
                                                <option>{$mi_order_state.desc|escape:'htmlall':'UTF-8'}</option>
                                                {foreach from=$me_orders.ps_order_states item=ps_order_state}
                                                    <option value="{$ps_order_state.id_order_state|intval}"
                                                            {if $ps_order_state.id_order_state == $mi_order_state.value}selected{/if}>
                                                        {$ps_order_state.name|escape:'htmlall':'UTF-8'}</option>
                                                {/foreach}
                                            </select>
                                            <p>{$mi_order_state.help_text|escape:'htmlall':'UTF-8'}</p>
                                        </div>
                                    {/if}
                                {/foreach}
                            </div>
                            {*
                                                 <hr style="width:30%" />
        
                                                <div class="form-group">
                                                    <label class="control-label col-lg-3" style="color:grey">{l s='Carriers' mod='mirakl'}</label>
                                                    <div class="margin-form col-lg-9">
                                                        <br>
                                                        <br>
                                                    </div>
        
                                                    <label class="control-label col-lg-3">{l s='Standard' mod='mirakl'}</label>
                                                    <div class="margin-form col-lg-9">
                                                        <select name="order_carriers[standard]" style="width:500px;">
                                                            <option value="" disabled>{l s='Choose an appropriate carrier for standard shipping' mod='mirakl'}</option>
                                                            {foreach from=$me_orders.order_carriers.standard item=carrier_option}
                                                                <option value="{$carrier_option.value|escape:'htmlall':'UTF-8'}" {$carrier_option.selected|escape:'htmlall':'UTF-8'} >{$carrier_option.desc|escape:'htmlall':'UTF-8'} </option>
                                                            {/foreach}
                                                        </select>
                                                    </div>
                                                    <br />
        
                                                    <div style="display: none;"><!-- future use -->
                                                        <label class="control-label col-lg-3">{l s='Relay' mod='mirakl'}</label>
                                                        <div class="margin-form col-lg-9">
                                                            <select name="order_carriers[relay]" style="width:500px;">
                                                                <option value="" disabled>{l s='Choose an appropriate carrier for relay shipping' mod='mirakl'}</option>
                                                                {foreach from=$me_orders.order_carriers.relay item=carrier_option}
                                                                    <option value="{$carrier_option.value|escape:'htmlall':'UTF-8'}" {$carrier_option.selected|escape:'htmlall':'UTF-8'} >{$carrier_option.desc|escape:'htmlall':'UTF-8'} </option>
                                                                {/foreach}
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                            *}
                            {include file="$module_path/views/templates/admin/configure/validate.tpl"}
                        </div>
                    {/if}
                    <!--ORDERS END -->
                {/if}

                {if $miraklTab.id == 'settings'}
                    <!--SETTINGS START -->
                    {if isset($me_settings) && $me_settings}
                        <div id="conf-settings" class="tabItem" style="display:none;">
                            <h2>{l s='Settings' mod='mirakl'}</h2>

                            <div class="margin-form col-lg-offset-3">
                                <div style="font-size:1.2em;line-height:140%;"
                                     class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                                    <ul>
                                        <li>{l s='Please read our online tutorial' mod='mirakl'}:</li>
                                        {* No escape because already made in the PHP *}
                                        <li>{$me_tutorial_parameters|escape:'quotes':'UTF-8'}</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-lg-3">{l s='Discount/Specials' mod='mirakl'}</label>
                                <div class="margin-form col-lg-9">
                                    <input type="checkbox" name="specials"
                                           value="1" {$me_settings.specials|escape:'htmlall':'UTF-8'} />&nbsp;
                                    <span style="color:black;font-size:12px;">&nbsp;{l s='Yes' mod='mirakl'}</span>
                                    <br/>
                                    <p>{l s='Export specials prices if is sets Yes. If unsets the discounted prices will be ignorates' mod='mirakl'}</p>
                                </div>
                                <label class="control-label col-lg-3">{l s='Taxes' mod='mirakl'}</label>
                                <div class="margin-form col-lg-9">
                                    <input type="checkbox" name="taxes"
                                           value="1" {$me_settings.taxes|escape:'htmlall':'UTF-8'} />&nbsp;
                                    <span style="color:black;font-size:12px;">&nbsp;{l s='Yes' mod='mirakl'}</span>
                                    <br/>
                                    <p>{l s='Add taxes to products and calculate order\'s taxes if sets to yes' mod='mirakl'}</p>
                                </div>

                                <label class="control-label col-lg-3">{l s='Smart Rounding' mod='mirakl'}</label>
                                <div class="margin-form col-lg-9">
                                    <input type="checkbox" name="smart_rounding"
                                           value="1" {$me_settings.smart_rounding|default:0|escape:'htmlall':'UTF-8'} />&nbsp;
                                    <span style="color:black;font-size:12px;">&nbsp;{l s='Yes' mod='mirakl'}</span>
                                    <br/>
                                    <p>{l s='Round the price in a smart way (eg: 15.46 will become 15.49)' mod='mirakl'}</p>
                                </div>

                                <label class="control-label col-lg-3">{l s='Product Name' mod='mirakl'}</label>
                                <div class="margin-form col-lg-9">
                                    <input type="radio" name="product_name"
                                           {if $me_settings.product_name_only_checked}checked="checked"{/if}
                                           value="{$me_settings.name_name_only|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;<span>{l s='Name Only' mod='mirakl'}</span>&nbsp;&nbsp;&nbsp;
                                    <input type="radio" name="product_name"
                                           {if $me_settings.product_name_attr_checked}checked="checked"
                                           {/if}value="{$me_settings.name_name_attributes|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;<span>{l s='Name and Attributes' mod='mirakl'}</span>&nbsp;&nbsp;&nbsp;
                                    <input type="radio" name="product_name"
                                           {if $me_settings.product_brand_name_attr_checked}checked="checked"{/if}
                                           value="{$me_settings.name_brand_name_attributes|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;<span>{l s='Brand, Name and Attributes' mod='mirakl'}</span>&nbsp;&nbsp;&nbsp;
                                    <input type="radio" name="product_name"
                                           {if $me_settings.product_name_brand_attr_checked}checked="checked"{/if}
                                           value="{$me_settings.name_name_brand_attributes|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;<span>{l s='Name, Brand and Attributes' mod='mirakl'}</span>&nbsp;&nbsp;&nbsp;
                                    <input type="radio" name="product_name"
                                           {if $me_settings.product_name_ref_checked}checked="checked"{/if}
                                           value="{$me_settings.name_name_reference|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;<span>{l s='Name and Reference' mod='mirakl'}</span>&nbsp;&nbsp;&nbsp;
                                    <p>{l s='Format of product name' mod='mirakl'}</p>
                                </div>

                                <label class="control-label col-lg-3">{l s='Description Field' mod='mirakl'}</label>
                                <div class="margin-form col-lg-9">
                                    <input type="radio"
                                           name="description_field" {$me_settings.long_description_checked|escape:'htmlall':'UTF-8'}
                                           value="{$me_settings.long_description|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;<span>{l s='Long Description' mod='mirakl'}</span>&nbsp;&nbsp;&nbsp;
                                    <input type="radio"
                                           name="description_field" {$me_settings.short_description_checked|escape:'htmlall':'UTF-8'}
                                           value="{$me_settings.short_description|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;<span>{l s='Short Description' mod='mirakl'}</span>&nbsp;&nbsp;&nbsp;
                                    <input type="radio"
                                           name="description_field" {$me_settings.both_description_checked|escape:'htmlall':'UTF-8'}
                                           value="{$me_settings.both_description|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;<span>{l s='Both' mod='mirakl'}</span>&nbsp;&nbsp;&nbsp;
                                    <input type="radio"
                                           name="description_field" {$me_settings.none_description_checked|escape:'htmlall':'UTF-8'}
                                           value="{$me_settings.none_description|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;<span>{l s='None' mod='mirakl'}</span>&nbsp;&nbsp;&nbsp;
                                    <input type="checkbox"
                                           name="description_html" {$me_settings.description_html_checked|escape:'htmlall':'UTF-8'}
                                           value="{$me_settings.description_html|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;<span>{l s='HTML Descriptions' mod='mirakl'}</span>&nbsp;&nbsp;&nbsp;
                                    <p>{l s='Description fields to be send' mod='mirakl'}</p>
                                </div>

                                <label class="control-label col-lg-3">{l s='Ignore Products without Images' mod='mirakl'}</label>
                                <div class="margin-form col-lg-9">
                                    <input type="radio" name="no_image" {if $me_settings.no_image}checked="checked"{/if}
                                           value="1"/>&nbsp;&nbsp;<span>{l s='Yes' mod='mirakl'}</span>&nbsp;&nbsp;&nbsp;
                                    <input type="radio" name="no_image" {if !$me_settings.no_image}checked="checked"{/if}
                                           value="0"/>&nbsp;&nbsp;<span>{l s='No (Not Recommended)' mod='mirakl'}</span>&nbsp;
                                    <p>{l s='Description fields to be send' mod='mirakl'}</p>
                                    <hr style="width:50%"/>
                                </div>
                            </div>

                            {if isset($me_settings.ps_version_gt_15_or_equal)}
                                {if isset($me_settings.ps_advanced_stock_management)}
                                    <div class="form-group">
                                        <label class="control-label col-lg-3">{l s='Warehouses' mod='mirakl'}</label>
                                        <div class="margin-form col-lg-9">
                                            <select name="warehouse" style="width:500px;">
                                                <option value="">{l s='Choose' mod='mirakl'}</option>
                                                {foreach from=$me_settings.warehouse_options item=warehouse_option}
                                                    <option value="{$warehouse_option.value|escape:'htmlall':'UTF-8'}" {$warehouse_option.selected|escape:'htmlall':'UTF-8'}>{$warehouse_option.desc|escape:'htmlall':'UTF-8'}</option>
                                                {/foreach}
                                            </select>
                                            <p>{l s='Choose a warehouse for products pickup (for Advanced Stock Management)' mod='mirakl'}</p>
                                        </div>
                                    </div>
                                    <hr style="width:50%"/>
                                {/if}
                            {/if}

                            {if isset($me_settings.image_types)}
                                <div class="form-group">
                                    <label class="control-label col-lg-3">{l  s='Image Type' mod='mirakl'}</label>
                                    <div class="margin-form col-lg-9">
                                        <select name="image_type" id="image_type" style="width:200px;">
                                            <option disabled>{l s='Choose' mod='mirakl'}</option>
                                            {foreach from=$me_settings.image_types item=image_type}
                                                <option value="{$image_type.value|escape:'htmlall':'UTF-8'}" {$image_type.selected|escape:'htmlall':'UTF-8'}>{$image_type.desc|escape:'htmlall':'UTF-8'}</option>
                                            {/foreach}
                                        </select>
                                        <p>{l s='Kind of image which will be use (Please refer to Preference > Images for more informations)' mod='mirakl'}</p>
                                    </div>
                                </div>
                            {/if}
                            {*<label class="control-label col-lg-3">{l s='Customer Email' mod='mirakl'}</label>
                            <div class="margin-form col-lg-9">
                            <input type="checkbox" name="individual" id="individual" value="1" {$me_settings.individual|escape:'htmlall':'UTF-8'} />&nbsp;
                            <span style="color:black;font-size:12px;">&nbsp;{l s='Yes' mod='mirakl'}</span><br />
                            <p>{l s='Use individual customer account instead of global, that require the configuration of a NULL SMTP server' mod='mirakl'}</p>
                            <p style="color:red;">{l s='This option should be used only if it is managed by someone skilled to understand and configure it' mod='mirakl'}</p>
                            </div>
                            <div class="margin-form col-lg-9" id="set-domain" style="{$me_settings.style|escape:'htmlall':'UTF-8'}">
                            <input type="text" name="domain" id="domain" value="{$me_settings.domain|escape:'htmlall':'UTF-8'}" style="width:300px;" />
                            <p>{l s='Please choose a domain name for the customer\'s email addresses' mod='mirakl'}</p>
                            </div> *}

                            <div class="form-group">
                                <label class="control-label col-lg-3">{l  s='Customer Group' mod='mirakl'}</label>
                                <div class="margin-form col-lg-9">
                                    <select name="customer_group" id="customer_group" style="width:200px;">
                                        <option disabled>{l s='Choose' mod='mirakl'}</option>
                                        {foreach from=$me_settings.customer_groups item=customer_group}
                                            <option value="{$customer_group.value|escape:'htmlall':'UTF-8'}" {$customer_group.selected|escape:'htmlall':'UTF-8'}>{$customer_group.desc|escape:'htmlall':'UTF-8'}</option>
                                        {/foreach}
                                    </select>
                                    <p>{l s='The customer group used for this marketplace, useful to apply promotions.' mod='mirakl'}</p>
                                </div>
                            </div>

                            {include file="$module_path/views/templates/admin/configure/validate.tpl"}
                        </div>
                    {/if}
                    <!--SETTINGS END -->                    
                {/if}

                {if $miraklTab.id == 'filters'}
                    <!-- FILTERS START -->
                    <div id="conf-filters" class="tabItem" style="display:none;">
                        <h2>{l s='Filters' mod='mirakl'}</h2>

                        <div class="margin-form col-lg-offset-3">
                            <div style="font-size:1.2em;line-height:140%;" class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                                <ul>
                                    <li>{l s='Please read our online tutorial' mod='mirakl'}:</li>
                                    {* No escape because already made in the PHP *}
                                    <li>{$me_tutorial_filters|escape:'quotes':'UTF-8'}</li>
                                </ul>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-lg-3"
                                   style="color:grey">{l s='Manufacturers Filters' mod='mirakl'}</label>
                            <div class="margin-form col-lg-9">
                                <div class="manufacturer-heading">
                            <span><img src="{$images_url|escape:'htmlall':'UTF-8'}cross.png"
                                       alt="{l s='Excluded' mod='mirakl'}"/></span>
                                    <span><img src="{$images_url|escape:'htmlall':'UTF-8'}checked.png"
                                               alt="{l s='Included' mod='mirakl'}"/></span>
                                </div>
                                <select name="excluded-manufacturers[]" class="excluded-manufacturers"
                                        id="excluded-manufacturers" multiple="multiple">
                                    <option value="0" disabled
                                            style="color:orange;">{l s='Excluded Manufacturers' mod='mirakl'}</option>
                                    {foreach from=$me_filters.manufacturers.filtered key=id_manufacturer item=name}
                                        <option value="{$id_manufacturer|intval}">{$name|escape:'htmlall':'UTF-8'}</option>
                                    {/foreach}
                                </select>
                                <div class="sep">
                                    <img src="{$images_url|escape:'htmlall':'UTF-8'}prev.png" class="move"
                                         id="manufacturer-move-left" alt="Left"/><br/><br/>
                                    <img src="{$images_url|escape:'htmlall':'UTF-8'}next.png" class="move"
                                         id="manufacturer-move-right" alt="Right"/>
                                </div>
                                <select name="available-manufacturers[]" class="available-manufacturers"
                                        id="available-manufacturers" multiple="multiple">
                                    <option value="0" disabled
                                            style="color:green;">{l s='Included Manufacturers' mod='mirakl'}</option>
                                    {foreach from=$me_filters.manufacturers.available key=id_manufacturer item=name}
                                        <option value="{$id_manufacturer|intval}">{$name|escape:'htmlall':'UTF-8'}</option>
                                    {/foreach}
                                </select>
                            </div>
                            <div class="cleaner"></div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-lg-3"
                                   style="color:grey">{l s='Suppliers Filters' mod='mirakl'}</label>
                            <div class="margin-form col-lg-9">
                                <div class="supplier-heading">
                            <span><img src="{$images_url|escape:'htmlall':'UTF-8'}cross.png"
                                       alt="{l s='Excluded' mod='mirakl'}"/></span>
                                    <span><img src="{$images_url|escape:'htmlall':'UTF-8'}checked.png"
                                               alt="{l s='Included' mod='mirakl'}"/></span>
                                </div>
                                <select name="selected-suppliers[]" class="selected-suppliers" id="selected-suppliers"
                                        multiple="multiple">
                                    <option value="0" disabled
                                            style="color:orange;">{l s='Excluded Suppliers' mod='mirakl'}</option>
                                    {foreach from=$me_filters.suppliers.filtered key=id_supplier item=name}
                                        <option value="{$id_supplier|intval}">{$name|escape:'htmlall':'UTF-8'}</option>
                                    {/foreach}
                                </select>
                                <div class="sep">
                                    <img src="{$images_url|escape:'htmlall':'UTF-8'}prev.png" class="move"
                                         id="supplier-move-left" alt="Left"/><br/><br/>
                                    <img src="{$images_url|escape:'htmlall':'UTF-8'}next.png" class="move"
                                         id="supplier-move-right" alt="Right"/>
                                </div>
                                <select name="available-suppliers[]" class="available-suppliers" id="available-suppliers"
                                        multiple="multiple">
                                    <option value="0" disabled
                                            style="color:green;">{l s='Included Suppliers' mod='mirakl'}</option>
                                    {foreach from=$me_filters.suppliers.available key=id_supplier item=name}
                                        <option value="{$id_supplier|intval}">{$name|escape:'htmlall':'UTF-8'}</option>
                                    {/foreach}
                                </select>
                            </div>
                            <div class="cleaner"></div>
                        </div>

                        {include file="$module_path/views/templates/admin/configure/validate.tpl"}
                    </div>
                    <!-- FILTERS END -->
                {/if}

                {if $miraklTab.id == 'cron'}
                    <!--CRON START -->
                    {if isset($me_cron) && $me_cron}
                        <div id="conf-cron" class="tabItem" style="display:none;">
                            <h2>{l s='Cron' mod='mirakl'}</h2>

                            <div class="margin-form col-lg-offset-3">
                                <div style="font-size:1.2em;line-height:140%;"
                                     class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                                    <ul>
                                        <li>{l s='Please read our online tutorial' mod='mirakl'}:</li>
                                        {* No escape because already made in the PHP *}
                                        <li>{$me_tutorial_cron|escape:'quotes':'UTF-8'}</li>
                                    </ul>
                                </div>
                            </div>
                            <br/>
                            <div class="form-group">
                                <div class="margin-form col-lg-offset-3">
                                    <div id="cronjobs_success" class="{$alert_class.success|escape:'htmlall':'UTF-8'}"
                                         style="display:none">
                                    </div>

                                    <div id="cronjobs_error" class="{$alert_class.danger|escape:'htmlall':'UTF-8'}"
                                         style="display:none">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="margin-form col-lg-offset-3">
                                    <div class="cron-mode" rel="prestashop-cron">
                                        <img src="{$images_url|escape:'quotes':'UTF-8'}/prestashop-cronjobs-icon.png"
                                             title="{l s='Prestashop Cronjobs (Module)' mod='mirakl'}"/>
                                        <h4>{l s='Prestashop Cronjobs (Module)' mod='mirakl'}</h4>
                                        <div style="float:right" class="cron-prestashop">
                                            {if $me_cron.cronjobs.installed}
                                                <span style="color:green">{l s='Installed' mod='mirakl'}</span>
                                            {elseif $me_cron.cronjobs.exists}
                                                <span style="color:red">{l s='Detected, Not installed' mod='mirakl'}</span>
                                            {else}
                                                <span style="color:red">{l s='Not detected' mod='mirakl'}</span>
                                            {/if}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="prestashop-cron" class="cron-toggle"
                                 {if !$me_cron.cronjobs.installed}style="display:none"{/if} >
                                <div class="form-group">
                                    <div class="margin-form col-lg-offset-3">

                                        {if !$me_cron.cronjobs.installed}
                                            <div class="margin-form col-lg-9">
                                                <div class="{$alert_class.warning|escape:'htmlall':'UTF-8'}">{l s='Prestashop Cronjobs is not installed.' mod='mirakl'}  {if !$me_cron.cronjobs.exists}(
                                                        <a href="https://github.com/PrestaShop/cronjobs/archive/master.zip"
                                                           target="_blank">https://github.com/PrestaShop/cronjobs</a>
                                                        ){/if}</div>
                                            </div>
                                        {else}
                                            <span class="title">{l s='Those lines will be added in Prestashop Cronjobs module' mod='mirakl'}
                                        :</span>
                                            <div id="prestashop-cronjobs-lines">
                                                {if $me_cron.stdtypes}
                                                    {foreach from=$me_cron.stdtypes item=type}
                                                        <b>{$me_cron[$type].title|escape:'htmlall':'UTF-8'}</b>
                                                        : {l s='each' mod='mirakl'} {$me_cron[$type].frequency|escape:'htmlall':'UTF-8'} {if $me_cron[$type].frequency > 1}{l s='hours' mod='mirakl'}{else}{l s='hour' mod='mirakl'}{/if}, {l s='url' mod='mirakl'}:
                                                        <a href="{$me_cron[$type].url|escape:'quotes':'UTF-8'}"
                                                           target="_blank">{$me_cron[$type].url_short|escape:'quotes':'UTF-8'}</a>
                                                        <br/>
                                                    {/foreach}
                                                {/if}
                                            </div>
                                            <textarea id="prestashop-cronjobs-params" name="prestashop-cronjobs-params"
                                                      style="display:none">
{if $me_cron.stdtypes}
    {foreach from=$me_cron.stdtypes item=type}{if array_key_exists($type, $me_cron)}{$me_cron[$type].title|escape:'htmlall':'UTF-8'}|0|{$me_cron[$type].frequency|escape:'htmlall':'UTF-8'}|{$me_cron[$type].url|escape:'quotes':'UTF-8'}!{/if}{/foreach}
{/if}
</textarea>
                                            <br/>
                                            {if $me_cron.cronjobs.installed}
                                                <span style="color:green">{l s='Click on install/update button to auto-configure your Prestashop cronjobs module' mod='mirakl'}
                                            :</span>
                                                <button class="button btn btn-default" style="float:right"
                                                        id="install-cronjobs">
                                                    <img src="{$images_url|escape:'htmlall':'UTF-8'}plus.png" alt=""/>&nbsp;&nbsp; {l s='Install/Update' mod='mirakl'}
                                                </button>
                                                <img src="{$images_url|escape:'htmlall':'UTF-8'}loader-connection.gif" alt=""
                                                     id="cronjob-loader"/>
                                            {/if}
                                        {/if}
                                    </div>

                                </div>
                            </div>


                            <div class="form-group">
                                <div class="margin-form col-lg-offset-3">
                                    <div class="cron-mode" rel="manual-cron">
                                        <img src="{$images_url|escape:'quotes':'UTF-8'}/terminal.png"
                                             title="{l s='Manual Cron URLs' mod='mirakl'}"/>
                                        <h4>{l s='Manual Cron URLs' mod='mirakl'}</h4>
                                    </div>
                                </div>
                            </div>
                            <div id="manual-cron" class="cron-toggle" {if $me_cron.cronjobs.installed}style="display:none"{/if}>
                                <div class="form-group">
                                    <label class="control-label col-lg-3"></label>
                                    <div class="margin-form col-lg-9">
                                        <input type="text" style="color:grey;background-color:#fdfdfd;width:100%;"
                                               value="{$me_cron.update_url|escape:'htmlall':'UTF-8'}"/><br/>
                                        <p>{l s='URL to synchronize products to be used to configure your crontab.' mod='mirakl'}</p>

                                        {if isset($me_cron.update_lite_url)}
                                            <input type="text" style="color:grey;background-color:#fdfdfd;width:100%;"
                                                   value="{$me_cron.update_lite_url|escape:'htmlall':'UTF-8'}"/>
                                            <br/>
                                            <p>
                                                {l s='URL to synchronize products to be used to configure your crontab (LITE VERSION) (only sku and quantity).' mod='mirakl'}
                                            </p>
                                        {/if}

                                        {if isset($me_cron.update_1_month_url)}
                                            <input type="text" style="color:grey;background-color:#fdfdfd;width:100%;"
                                                   value="{$me_cron.update_1_month_url|escape:'htmlall':'UTF-8'}"/>
                                            <br/>
                                            <p>{l s='URL to synchronize products to be used to configure your crontab.' mod='mirakl'}
                                                - 24H</p>
                                        {/if}

                                        <input type="text"
                                               style="color:grey;background-color:#fdfdfd;width:100%;margin-top:5px;"
                                               value="{$me_cron.accept_url|escape:'htmlall':'UTF-8'}"/><br/>
                                        <p>{l s='URL to accept orders to be used to configure your crontab.' mod='mirakl'}</p>

                                        <input type="text"
                                               style="color:grey;background-color:#fdfdfd;width:100%;margin-top:5px;"
                                               value="{$me_cron.import_url|escape:'htmlall':'UTF-8'}"/><br/>
                                        <p>{l s='URL to import orders to be used to configure your crontab.' mod='mirakl'}</p>

                                        <input type="text"
                                               style="color:grey;background-color:#fdfdfd;width:100%;margin-top:5px;"
                                               value="{$me_cron.update_orders_url|escape:'htmlall':'UTF-8'}"/><br/>
                                        <p>{l s='URL to update orders on Mirakl to be used to configure your crontab.' mod='mirakl'}</p>

                                        <hr/>

                                        <span style="color:brown;font-weight:bold;">
                                    {l s='Be careful ! Importing orders by cron can skips somes orders (eg: out of stock), you must check also manually' mod='mirakl'}
                                </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    {/if}
                    <!--CRON END -->        
                {/if}

                {if $miraklTab.id == 'mkp_specific'}
                    {include file="$module_path/views/templates/admin/configure/mkp_specific/"|cat:$me_mkp_specific['name']:".tpl"
                    module_path=$module_path alert_class=$alert_class configuration=$me_mkp_specific}
                {/if}
            {/foreach}
        </div>
    </fieldset>

    <div class="row">
        <p>
            <span style="color:red;">&nbsp;*</span>&nbsp;: {l s='These informations are provided by' mod='mirakl'} {$me_name|escape:'htmlall':'UTF-8'}
            , {l s='Please contact the support for more informations' mod='mirakl'}
            : {$me_marketplace_email|escape:'htmlall':'UTF-8'}</p>
    </div>
</form>
<!-- ! body end -->
