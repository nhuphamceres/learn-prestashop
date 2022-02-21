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

<!-- body start -->
<form action="{$request_uri|escape:'quotes':'UTF-8'}" method="post" autocomplete="off" id="cdiscount_form">
<input type="hidden" name="selected_tab" value="{$selected_tab|escape:'htmlall':'UTF-8'}" id="selected_tab"/>
<input type="hidden" id="cdiscount_tools_url" value="{$tools_url|escape:'quotes':'UTF-8'}" />
<input type="hidden" id="context_key" value="{$context_key|escape:'quotes':'UTF-8'}" />

<div style="clear:both"></div>
<fieldset class="panel form-horizontal">
<div id="tabList">

<!-- CDISCOUNT / CLEMARCHE START -->
<div id="conf-cdiscount" class="tabItem">


    <div class="form-group">
        <label class="col-lg-3"><h2>CDiscount {$version|escape:'htmlall':'UTF-8'}</h2></label>

        <div class="margin-form col-lg-9">
            <p class="descriptionBold">
                <span style="color: navy;">{$module_description|escape:'htmlall':'UTF-8'}</span><br>
                {l s='The following features are provided with this module :' mod='cdiscount'}
            </p>
            <ul class="descriptionList">
                <li>{l s='Retrieve Orders from the Market Place by Web Service' mod='cdiscount'}</li>
                <li>{l s='Update Orders Status in the Market Place by Web Service' mod='cdiscount'}</li>
                <li>{l s='Update & Create Products in the Market Place' mod='cdiscount'}</li>
            </ul>
            <hr/>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Informations' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9" style="margin-top: 6px;">
            <span style="color:navy">{l s='This module is provided by' mod='cdiscount'} :</span> Common-Services<br>
            <br>
            <span style="color:navy">{l s='Informations, follow up on our blog' mod='cdiscount'} :</span><br>
            <a href="http://www.common-services.com" target="_blank">http://www.common-services.com</a><br>
            <br>
            <span style="color:navy">{l s='More informations about us on Prestashop website' mod='cdiscount'}
                :</span><br>
            <a href="http://www.prestashop.com/fr/agences-web-partenaires/or/common-services" target="_blank">http://www.prestashop.com/fr/agences-web-partenaires/or/common-services</a><br>
            <br>
            <span style="color:navy">{l s='You will appreciate our other modules' mod='cdiscount'} :</span><br>
            <a href="http://addons.prestashop.com/fr/58_common-services" target="_blank">http://addons.prestashop.com/fr/58_common-services</a><br>
            <br>
            <span style="color:navy">{l s='Inscription Cdiscount Marketplace' mod='cdiscount'}:</span><br>
            <a href="https://marketplace-registration.cdiscount.com/?broughtBy=0015800000aSchc" target="_blank">https://marketplace-registration.cdiscount.com/?broughtBy=0015800000aSchc</a>
        </div>
    </div>
    <br>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Documentation' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
            <div class="col-lg-1"><img src="{$images_url|escape:'htmlall':'UTF-8'}books.png" alt="docs"/></div>
            <div class="col-lg-11">
                <span style="color:red; font-weight:bold;">{l s='Please, first read the provided documentation' mod='cdiscount'}
                    :</span><br>
                <a href="http://documentation.common-services.com/cdiscount" target="_blank">http://documentation.common-services.com/cdiscount</a>
            </div>
        </div>
    </div>
    <br>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Support' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
            <div class="col-lg-1"><img src="{$images_url|escape:'htmlall':'UTF-8'}submit_support_request.png"
                                       alt="support"></div>
            <div class="col-lg-11">
                            <span style="color:red; font-weight:bold;">
                                {l s='The technical support is available by e-mail only.' mod='cdiscount'}
                            </span><br>
                            <span style="color: navy;">
                                {l s='For any support, please provide us' mod='cdiscount'} :<br>
                            </span>
                <ul>
                    <li>{l s='A detailled description of the issue or encountered problem' mod='cdiscount'}</li>
                    <li>{l s='Your Pretashop Addons Order ID available in your Prestashop Addons order history' mod='cdiscount'}</li>
                    <li>{l s='Your Prestashop version' mod='cdiscount'} : <span
                                style="color: red;">Prestashop {$ps_version|escape:'htmlall':'UTF-8'}</span></li>
                    <li>{l s='Your module version' mod='cdiscount'} : <span
                                style="color: red;">CDiscount v{$version|escape:'htmlall':'UTF-8'}</span></li>
                </ul>
                <br>
                <span style="color:navy">{l s='Support Common-Services' mod='cdiscount'} :</span>
                <a href="mailto:support.cdiscount@common-services.com?subject={l s='Support for CDiscount' mod='cdiscount'}&body={l s='Dear Support, I am currently having some trouble with your module v%s on my Prestashop v%s.'|sprintf:$version:$ps_version mod='cdiscount'}"
                   title="Email">
                    support.cdiscount@common-services.com
                </a><br>
                <br>
            </div>
            <hr style="clear: both;">
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Licence' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
            <p>
                {l s='This add-on is under a commercial licence from S.A.R.L. SMC' mod='cdiscount'}.<br>
                {l s='In case of purchase on Prestashop Addons, the invoice is the final proof of license.' mod='cdiscount'}
                <br>
                {l s='Contact us to obtain a license only in other cases' mod='cdiscount'} :
                support.cdiscount@common-services.com
            </p>
        </div>
    </div>
</div>
<!-- CDISCOUNT / CLEMARCHE END -->

<!-- INFORMATION START -->
<div id="conf-informations" class="tabItem">
    <input type="hidden" id="max_input_vars" value="{$cd_informations.max_input_vars|intval}" />

    <h2>{l s='Configuration Check' mod='cdiscount'}</h2>

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>

        <div class="margin-form col-lg-9">
            <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                {l s='If an error appear, please follow our online tutorial' mod='cdiscount'}:<br>
                <a href="http://documentation.common-services.com/cdiscount/informations-erreurs-courrantes/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                   target="_blank">http://documentation.common-services.com/cdiscount/informations-erreurs-courrantes/</a>
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label class="control-label col-lg-3 dropup" id="detailed_debug_controller" style="cursor: pointer;">
            {l s='Debug info' mod='cdiscount'}
            <span class="caret"></span>
        </label>
        <div align="left" class="margin-form col-lg-9" id="detailed_debug_content" style="display: none;">
            {foreach from=$cd_detailed_debug item=cd_debug}
                {$cd_debug|@print_r}
            {/foreach}
        </div>
    </div>

    <div class="form-group" id="cd-module-infos">
        <label class="control-label col-lg-3">{l s='Module' mod='cdiscount'}</label>

        <div align="left" class="margin-form col-lg-9 cd-info">

            {if $cd_informations.module_info_ok}
                {if $cd_informations.module_infos}
                    {foreach from=$cd_informations.module_infos key=module_name item=module_info}
                        {if isset($module_info.script) && isset($module_info.script.url)}
                            <!-- script URL -->
                            <input type="hidden" id="{$module_info.script.name|escape:'htmlall':'UTF-8'}"
                                   value="{$module_info.script.url|escape:'htmlall':'UTF-8'}"
                                   rel="{$module_name|escape:'htmlall':'UTF-8'}"/>
                        {/if}

                        <div class="{$module_info.level|escape:'htmlall':'UTF-8'} cd-env-infos-{$module_name|escape:'htmlall':'UTF-8'}" {if isset($module_info.display) && !$module_info.display}style="display:none"{/if}>
                            <p><span>{$module_info.message|escape:'htmlall':'UTF-8'}</span>
                                {if isset($module_info.tutorial)}
                                <br/>
                                    <pre>{l s='Please follow our online tutorial' mod='cdiscount'} : {$module_info.tutorial|escape:'quotes':'UTF-8'}</pre>
                                {/if}
                            </p>
                        </div>
                    {/foreach}
                {/if}
            {else}
                <p class="{$alert_class.success|escape:'htmlall':'UTF-8'}">
                    <span>{l s='Module\'s environment seems to be healthy' mod='cdiscount'}</span>
                <hr style="width:30%;margin-top:10px;"/>
                </p>
            {/if}
            <hr style="clear:both;width:30%;margin-top:10px;"/>
        </div>
    </div>

    <div class="form-group" id="cd-env-infos" {if !$cd_informations.display_env}style="display:none"{/if}>
        <label class="control-label col-lg-3">{l s='Environment' mod='cdiscount'}</label>

        <div align="left" class="margin-form col-lg-9 cd-info">
            {if $cd_informations.env_infos}
                {foreach from=$cd_informations.env_infos key=env_name item=env_info}
                    {if isset($env_info.script) && isset($env_info.script.url)}
                        <!-- script URL -->
                        <input type="hidden" id="{$env_info.script.name|escape:'htmlall':'UTF-8'}"
                               value="{$env_info.script.url|escape:'htmlall':'UTF-8'}"
                               rel="{$env_name|escape:'htmlall':'UTF-8'}"/>
                    {/if}

                    <div {if !isset($env_info.display) || !$env_info.display}style="display:none"{/if}>
                        <p class="{$env_info.level|escape:'htmlall':'UTF-8'} cd-env-infos-{$env_name|escape:'htmlall':'UTF-8'}" ><span>{$env_info.message|escape:'htmlall':'UTF-8'}</span>
                        </p>
                        <hr style="clear;width:30%;margin-top:10px;"/>
                    </div>
                {/foreach}
            {/if}
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='PHP Settings' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9 cd-info">
            <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                {l s='We recommend to apply these settings:' mod='cdiscount'} {$cd_informations.tutorial_php|escape:'quotes':'UTF-8'}
            </div>
        </div>

        <div align="left" class="margin-form col-lg-9 col-lg-offset-3">
            {if ! $cd_informations.php_info_ok}
                {foreach from=$cd_informations.php_infos item=php_info}
                    <p class="{$php_info.level|escape:'htmlall':'UTF-8'}">
                        <span>{$php_info.message|escape:'htmlall':'UTF-8'}</span>
                        {if isset($php_info.link)}
                            <br/>
                            <span class="cd-info-link">{l s='Please read more about it on:' mod='cdiscount'}: <a
                                        href="{$php_info.link|escape:'htmlall':'UTF-8'}"
                                        target="_blank">{$php_info.link|escape:'htmlall':'UTF-8'}</a></span>
                        {/if}
                    <hr style="width:30%;margin-top:10px;"/>
                    </p>
                {/foreach}
            {else}

                <p class="{$alert_class.success|escape:'htmlall':'UTF-8'}">
                    <span>{l s='Your PHP configuration for the module has been checked and passed successfully...' mod='cdiscount'}</span>
                <hr style="width:30%;margin-top:10px;"/>
                </p>
            {/if}
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Prestashop Settings' mod='cdiscount'}</label>

        <div align="left" class="margin-form col-lg-9 cd-info">
            {if ! $cd_informations.prestashop_info_ok}
                {foreach from=$cd_informations.prestashop_infos item=prestashop_info}
                    <p class="{$prestashop_info.level|escape:'htmlall':'UTF-8'}" {if isset($prestashop_info.id)}id="{$prestashop_info.id|escape:'htmlall':'UTF-8'}"{/if}>
                        <span>{$prestashop_info.message|escape:'htmlall':'UTF-8'}</span>
                        {if isset($prestashop_info.link)}
                            <br/>
                            <span class="cd-info-link">{l s='Please read more about it on:' mod='cdiscount'}: <a
                                        href="{$prestashop_info.link|escape:'htmlall':'UTF-8'}"
                                        target="_blank">{$prestashop_info.link|escape:'htmlall':'UTF-8'}</a></span>
                        {/if}
                    </p>
                    <hr style="width:30%;margin-top:10px;"/>
                {/foreach}
            {else}

                <p class="{$alert_class.success|escape:'htmlall':'UTF-8'}">
                    <span>{l s='Your Prestashop configuration for the module has been checked and passed successfully...' mod='cdiscount'}</span>
                <hr style="width:30%;margin-top:10px;"/>
                </p>
            {/if}
        </div>
    </div>


    <div class="form-group">
        <label class="control-label col-lg-3">{l s='CDiscount Settings' mod='cdiscount'}</label>

        <div align="left" class="margin-form col-lg-9 cd-info">
            {if $cd_informations.cdiscount_info_ok}
                {foreach from=$cd_informations.cdiscount_infos item=cdiscount_info}
                    <p class="{$cdiscount_info.level|escape:'htmlall':'UTF-8'}" {if isset($cdiscount_info.id)}id="{$cdiscount_info.id|escape:'htmlall':'UTF-8'}"{/if}>
                        <span>{$cdiscount_info.message|escape:'htmlall':'UTF-8'}</span>
                        {if isset($cdiscount_info.link)}
                            <br/>
                            <span class="cd-info-link">{l s='Please read more about it on:' mod='cdiscount'}: <a
                                        href="{$cdiscount_info.link|escape:'htmlall':'UTF-8'}"
                                        target="_blank">{$cdiscount_info.link|escape:'htmlall':'UTF-8'}</a></span>
                        {/if}
                    </p>
                    <hr style="width:30%;margin-top:10px;"/>
                {/foreach}
            {else}
                <p class="{$alert_class.warning|escape:'htmlall':'UTF-8'}">
                    <span>{l s='You are not yet connected to cdiscount, please configure the authentication tab' mod='cdiscount'}.</span>
                <hr style="width:30%;margin-top:10px;"/>
                </p>
            {/if}
        </div>
    </div>


    <h2>{l s='Additionnal Support Informations' mod='cdiscount'}</h2>
    <br/>
    <div class="form-group">
        <label class="control-label col-lg-3"></label>
        <!-- Show loader when request support zip file -->
        <div class="margin-form col-lg-9" id="support-information-file-loader" >
            <img src="{$cd_informations.images|escape:'htmlall':'UTF-8'}loading.gif" alt="{l s='Support Information' mod='cdiscount'}"/>
        </div>
        <div class="margin-form col-lg-9" id="support-information-download">
            <a href="{$cd_informations.support_information_url|escape:'htmlall':'UTF-8'}" class="support-url"
               data-file-name="{$cd_informations.support_information_file_name|escape:'htmlall':'UTF-8'}">
                <img src="{$cd_informations.images|escape:'htmlall':'UTF-8'}/zip64.png" class="support-file" title="Support Details" />
                <span>{l s='Download' mod='cdiscount'}</span>
            </a>
            <br/>
            <p><em>{l s='This file contains support informations we would need for a faster diagnosis' mod='cdiscount'}</em></p>
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-lg-3"></label>
        <div class="margin-form col-lg-9">
            <a target="_blank" href="{$cd_informations.support_information_url|escape:'htmlall':'UTF-8'}">
                {l s='Mirror link' mod='cdiscount'}
            </a>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label><br/>
        <div align="left" class="margin-form amz-info col-lg-9">
            <input type="button" class="button btn" id="support-information-prestashop" value="{l s='Prestashop Info' mod='cdiscount'}" rel="{$cd_informations.support_information_url|escape:'htmlall':'UTF-8'}&action=prestashop-info"/>&nbsp;&nbsp;
            <input type="button" class="button btn" id="support-information-php" value="{l s='PHP Info' mod='cdiscount'}" rel="{$cd_informations.support_information_url|escape:'htmlall':'UTF-8'}&action=php-info"/>&nbsp;&nbsp;
            {if $cd_informations.expert_mode}
                <input type="hidden" id="mode_dev-status" value="{if !$cd_informations.mode_dev}1{else}0{/if}" />
                <input type="hidden" id="mode_dev-status-on" value="{l s='Switch On DEV_MODE' mod='cdiscount'}" />
                <input type="hidden" id="mode_dev-status-off" value="{l s='Switch Off DEV_MODE' mod='cdiscount'}" />
                <input type="button" class="button btn" id="support-mode_dev"
                {if !$cd_informations.mode_dev}value="{l s='Switch On DEV_MODE' mod='cdiscount'}"{else}value="{l s='Switch Off DEV_MODE' mod='cdiscount'}"{/if} rel="{$cd_informations.support_information_url|escape:'quotes':'UTF-8'}&action=mode-dev"/>&nbsp;&nbsp;
            {/if}
            <img src="{$cd_informations.images|escape:'htmlall':'UTF-8'}loader-connection.gif" alt="{l s='Support Informations' mod='cdiscount'}" class="support-information-loader"/><br/><br/>

            <div id="devmode-response">
                <div id="devmode-response-success" class="{$alert_class.success|escape:'htmlall':'UTF-8'}" style="display: none;"></div>
                <div id="devmode-response-danger" class="{$alert_class.danger|escape:'htmlall':'UTF-8'}" style="display: none;"></div>
            </div>

            <!-- PS info / Php info detail go here -->
            <div id="support-information-content"></div>
        </div>
    </div>


</div>
<!-- INFORMATIONS END -->

<!-- CREDENTIALS START -->
<div id="conf-credentials" class="tabItem">
    <h2>{l s='Authentification' mod='cdiscount'}</h2>

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>

        <div class="margin-form col-lg-9">
            <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                {l s='Please follow our online tutorial' mod='cdiscount'} :<br>
                <a href="http://documentation.common-services.com/cdiscount/configurer-les-parametres-de-connexion/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                   target="_blank">http://documentation.common-services.com/cdiscount/configurer-les-parametres-de-connexion/</a><br><br>
                {l s='Open an account on Cdiscount Marketplace' mod='cdiscount'} :<br>
                <a href="https://marketplace-registration.cdiscount.com/?broughtBy=0015800000aSchc"
                   target="_blank">https://marketplace-registration.cdiscount.com/?broughtBy=0015800000aSchc/</a>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3" for="username">{l s='Username' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
            <input id="username" type="text" style="width: 200px;" name="username" value="{$cd_username|escape:'htmlall':'UTF-8'}"/>
        </div>

        <label class="control-label col-lg-3" for="password">{l s='Password' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
            <input id="password" type="password" style="width: 200px;" name="password" autocomplete="off"
                   value="{$cd_password|escape:'htmlall':'UTF-8'}"/>
        </div>

        <label class="control-label col-lg-3">&nbsp;</label>

        <div class="margin-form col-lg-9">
            <button type="button" id="connection-check" class="button btn" name="connection-check">
                <span id="connection-check-txt">{l s='Verify' mod='cdiscount'}</span>
                <img src="{$images_url|escape:'htmlall':'UTF-8'}loader-connection.gif"
                     alt="{l s='Verify' mod='cdiscount'}"
                     id="connection-check-loader" style="position:relative;margin: 2px 0px;display:none;"/>
            </button>
            <br/>
            <br/>

            <div id="marketplace-response">
                <div id="marketplace-response-success" class="{$alert_class.success|escape:'htmlall':'UTF-8'} marketplace-log" style="display: none;"></div>
				<div id="marketplace-response-danger" class="{$alert_class.danger|escape:'htmlall':'UTF-8'} marketplace-log" style="display: none;"></div>
            </div>

        </div>
    </div>

    {if isset($cd_has_multichannel) && $cd_has_multichannel}

    <div class="margin-form col-lg-12">
        <hr style="width:30%"/>
    </div>

    <div class="form-group">
        <label for="multichannel" class="control-label col-lg-3">{l s='Multichannel' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9 multichannel">

            <ul>
                <li>
                    <span>
                        <span class="mc-checkbox">
                            <input type="checkbox" checked disabled title="{l s='Default' mod='cdiscount'}" />
                            <input type="hidden" name="multichannel[france]" value="1" />
                        </span>
                        <span class="mc-title">{l s='CDiscount France' mod='cdiscount'}</span>
                    </span>
                </li>
                <li>
                    <span>
                        <span class="mc-checkbox">
                            <input type="checkbox"  name="multichannel[belgium]" {if (isset($cd_multichannel.belgium) && $cd_multichannel.belgium == 1)}checked{/if} value="1" title="{l s='CDiscount Belgium' mod='cdiscount'}" />
                        </span>
                        <span class="mc-title">{l s='CDiscount Belgium' mod='cdiscount'}</span>
                    </span>
                </li>
            </ul>

            <p> {l s='Select additionnal countries you want to send your offers' mod='cdiscount'}</p>
        </div>
    </div>
    {/if}

    <div class="margin-form col-lg-12">
        <hr style="width:30%"/>
    </div>
    {if $cd_expert_mode}
        <div class="form-group">
            <label for="preproduction" class="control-label col-lg-3">{l s='Pre-Production' mod='cdiscount'}<span class="expert">{l s='Expert' mod='cdiscount'}</span></label>

            <div class="margin-form col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="preproduction" id="preproduction_1" value="1"
                                   {if $cd_preproduction_check}checked{/if} /><label for="preproduction_1"
                                                                                     class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                            <input type="radio" name="preproduction" id="preproduction_0" value="0"
                                   {if !$cd_preproduction_check}checked{/if}  /><label for="preproduction_0"
                                                                                       class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                            <a class="slide-button btn"></a>
                        </span>

                <p> {l s='Preproduction Mode, in most cases must be unchecked' mod='cdiscount'}</p>
            </div>
        </div>
    {/if}

    <div class="form-group">
        <label for="debug" class="control-label col-lg-3">{l s='Debug Mode' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="debug" id="debug_1" value="1" {if $cd_debug}checked{/if} /><label
                                    for="debug_1" class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                            <input type="radio" name="debug" id="debug_0" value="0"
                                   {if !$cd_debug}checked{/if}  /><label for="debug_0"
                                                                         class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                            <a class="slide-button btn"></a>
                        </span>

            <p>{l s='Debug mode. Enable traces for debugging and developpment purpose.' mod='cdiscount'}<br/>
                <b {$cd_debug_style|escape:'htmlall':'UTF-8'}>{l s='In exploitation this option must not be active !' mod='cdiscount'}</b>
            </p>
        </div>
    </div>

    {if $dev_mode}
        <div class="margin-form col-lg-12">
            <hr style="width:30%"/>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Dev Mode' mod='cdiscount'}</label>

            <div class="margin-form col-lg-9">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="dev_mode_value" id="dev_mode_1" value="1"
                                       {if $dev_mode_value}checked{/if} /><label for="dev_mode_1"
                                                                                 class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                                <input type="radio" name="dev_mode_value" id="dev_mode_0" value="0"
                                       {if !$dev_mode_value}checked{/if}  /><label for="dev_mode_0"
                                                                                   class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                                <a class="slide-button btn"></a>
                            </span>

                <p>
                    {l s='Used for test and development purposes' mod='cdiscount'}<br/>
                    <b {$cd_debug_style|escape:'htmlall':'UTF-8'}>{l s='In exploitation this option must not be active !' mod='cdiscount'}</b>
                </p>
            </div>
        </div>
    {/if}

    {include file="$module_path/views/templates/admin/configure/validate.tpl"}
</div>
<!-- CREDENTIALS END -->

<!--IMPORTS TAB START -->
<div id="conf-imports" class="tabItem">
    <input type="hidden" class="get_specific_data"
           value="{$module_url|escape:'htmlall':'UTF-8'}functions/specifics_load.php"/>
    <input type="hidden" id="load_models" value="{$module_url|escape:'htmlall':'UTF-8'}functions/models_load.php"/>
    <input type="hidden" id="load_categories"
           value="{$module_url|escape:'htmlall':'UTF-8'}functions/categories_load.php"/>

    <h2>{l s='Imports' mod='cdiscount'}</h2>

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>

        <div class="margin-form col-lg-9">
            <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                {l s='To know more about the imports, please follow our online tutorial' mod='cdiscount'}:<br>
                <a href="http://documentation.common-services.com/cdiscount/imports/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                   target="_blank">http://documentation.common-services.com/cdiscount/imports/</a>
            </div>
        </div>
    </div>

    {if $cd_imports.ean_exemption}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Import EAN Matching File' mod='cdiscount'}</label>

            <div align="left" class="margin-form col-lg-9">
                <span class="field_label">{l s='Send a CSV File' mod='cdiscount'}</span>&nbsp;
                <input type="file" id="eanmatchingcsv" name="eanmatchingcsv" style="width:450px;"/>&nbsp;&nbsp;
                <input type="submit" name="validateForm" value="{l s='Send' mod='cdiscount'}" class="button btn"/>
            </div>
        </div>
        <hr style="width:30%"/>
    {/if}

    {* 2020-07-13: Remove status / functional of AllModelList *}

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Universes' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
            <p>{l s='This import should be automatic, please only report error messages, if present.' mod='cdiscount'}</p>

            <div id="universes-info"
                 class="{$cd_imports.universesClass|escape:'htmlall':'UTF-8'}">{$cd_imports.universesInfo|escape:'htmlall':'UTF-8'}</div>
            <br/>
            <input type="hidden" id="universes-renew-status" value="{if $cd_imports.universesRenew}1{else}0{/if}"/>

            <div id="universes-renew" class="cd-info-level-reload" style="display:none">
                <span style="color:black;font-weight:bold;margin-right:30px;">{$cd_imports.universesRenewInfo|escape:'htmlall':'UTF-8'}</span>
                <img src="{$images_url|escape:'htmlall':'UTF-8'}loader-connection.gif"
                     alt="{l s='Loading Universes' mod='cdiscount'}"/>
            </div>
            <hr style="width:30%;"/>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Categories' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
            <p>{l s='This import should be automatic, please only report error messages, if present.' mod='cdiscount'}</p>

            <div id="categories-info"
                 class="{$cd_imports.categoriesClass|escape:'htmlall':'UTF-8'}">{$cd_imports.categoriesInfo|escape:'htmlall':'UTF-8'}</div>
            <br/>
            <input type="hidden" id="categories-renew-status" value="{if $cd_imports.categoriesRenew}1{else}0{/if}"/>

            <div id="categories-renew" class="cd-info-level-reload" style="display:none">
                <span style="color:black;font-weight:bold;margin-right:30px;">{$cd_imports.categoriesRenewInfo|escape:'htmlall':'UTF-8'}</span>
                <img src="{$images_url|escape:'htmlall':'UTF-8'}loader-connection.gif"
                     alt="{l s='Loading Categories' mod='cdiscount'}"/>
            </div>
        </div>
    </div>

    {if (! $cd_imports.universesRenew AND ! $cd_imports.categoriesRenew)}
        <h2>{l s='Maintenance' mod='cdiscount'}</h2>
        <div class="form-group">
            <label class="control-label col-lg-3">&nbsp;</label>

            <div class="margin-form col-lg-9" class="models">
                <p style="color:navy">{l s='You could use the button below to force the update of your models & categories definitions from CDiscount.' mod='cdiscount'}</p>

                <p><input type="button" class="button btn btn-default" id="reset-xml"
                          value="{l s='Update Models and Categories' mod='cdiscount'}"/>
            </div>
        </div>
    {/if}

    {include file="$module_path/views/templates/admin/configure/validate.tpl"}
</div>
<!--IMPORTS TAB END-->

<!--MODELS START -->
    {include file="$module_path/views/templates/admin/configure/model/models.tpl"}
<!--MODELS END-->

<!--PROFILES START -->
    {include file="$module_path/views/templates/admin/configure/profiles.tpl"}
<!--PROFILES END-->

<!--CATEGORIES START -->
<div id="conf-categories" class="tabItem">
    <h2>{l s='Categories' mod='cdiscount'}</h2>

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>

        <div class="margin-form col-lg-9">
            <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                {l s='Please follow our online tutorial' mod='cdiscount'} :<br>
                <a href="http://documentation.common-services.com/cdiscount/selection-de-categories/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                   target="_blank">http://documentation.common-services.com/cdiscount/selection-de-categories/</a><br>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Categories' mod='cdiscount'}</label>
        <div class="margin-form col-lg-9">
            <table cellspacing="0" cellpadding="0" class="table">
                <tr class="active">
                    <th style="width:30px"></th>
                    <th>{l s='Name' mod='cdiscount'}</th>
                    <th style="width: 235px">{l s='Profile' mod='cdiscount'}</th>
                </tr>
                {if isset($cd_categories) && is_array($cd_categories.list) && count($cd_categories.list)}
                    {foreach $cd_categories.list as $id_category => $details}
                        <tr class="cat-line{($details.alt_row|intval) ? ' alt_row' : ''}">
                            <td>
                                {if !$details.disabled}
                                    <input type="checkbox" rel="category[]" class="category{($details.id_category_default|intval == $id_category|intval) ? ' id_category_default' : ''}" id="category_{$id_category|intval}" value="{$id_category|intval}" {$details.checked|escape:'htmlall':'UTF-8'}/>
                                {/if}
                            </td>
                            <td style="cursor:pointer">
                                <img src="{$details.img_level|escape:'htmlall':'UTF-8'}" alt="" /> &nbsp;<label for="category_{$id_category|intval}" class="t">{$details.name|escape:'htmlall':'UTF-8'}</label>
                            </td>
                            <td>
                                {if !$details.disabled}
                                    <select rel="profile2category[{$id_category|intval}]" style="width:180px;margin-right:10px;">
                                        <option value="">{l s='Please choose a profile' mod='cdiscount'}</option>
                                        {foreach $cd_categories.profiles.name as $profile}
                                            <option value="{$profile|escape:'htmlall':'UTF-8'}" {if $profile == $details.profile}selected="selected"{/if}>{$profile|escape:'htmlall':'UTF-8'}</option>
                                        {/foreach}
                                    </select>
                                    &nbsp;<span class="arrow-cat-duplicate"></span>
                                {/if}
                            </td>
                        </tr>
                    {/foreach}
                {else}
                    <tr>
                        <td colspan="3">
                            {l s='No category were found.' mod='cdiscount'}
                        </td>
                    </tr>
                {/if}
                {* !Ajout debuss-a *}
            </table>
        </div>
    </div>

    {include file="$module_path/views/templates/admin/configure/validate.tpl"}
</div>
<!--CATEGORIES END-->

<!--MAPPINGS START -->
{if isset($cd_mappings) && $cd_mappings}
    <div id="conf-mapping" class="tabItem">
        <h2>{l s='Mapping' mod='cdiscount'}</h2>

        <div class="form-group">
            <label class="control-label col-lg-3">&nbsp;</label>

            <div class="margin-form col-lg-9">
                <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                    {l s='Please follow our online tutorial' mod='cdiscount'} :<br>
                    <a href="http://documentation.common-services.com/cdiscount/configurer-les-mappings/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                       target="_blank">http://documentation.common-services.com/cdiscount/configurer-les-mappings/</a><br>
                </div>
            </div>
        </div>

        {if ($cd_mappings.has_size_fields)}

                <div class="form-group" id="size-field-mapping" >
                    <label class="control-label col-lg-3" style="color:green">{l s='Size Fields' mod='cdiscount'}</label>
                                <span id="size-field-mapping-collapse" class="col-lg-9">
                                    <a href="javascript:void(0)">&nbsp;&nbsp;[ + ]&nbsp;&nbsp; {l s='Show' mod='cdiscount'}</a>
                                    <a href="javascript:void(0)" style="display:none;">&nbsp;&nbsp;[ - ]&nbsp;&nbsp; {l s='Hide' mod='cdiscount'}</a>
                                </span>

                    <div class="col-lg-12"><br/>

                        {if ($cd_mappings.has_size_attributes_fields)}
                        <div id="size-attribute-mapping-section" class="col-lg-offset-3 margin-form" style="display:none;">
                            {if isset($cd_mappings.sizes_mapping) && is_array($cd_mappings.sizes_mapping)}
                                {foreach from=$cd_mappings.sizes_mapping['attributes'] key=model_key item=mapping}
                                    <h4>{$mapping.title|escape:'htmlall':'UTF-8'}</h4>

                                    <div class="form-group size-group mapping-box-scope">
                                        <div class="margin-form col-lg-9 mapping-item" style="/*display:none*/" >
                                                {foreach from=$mapping.attributes key=id_attribute item=attribute}
                                                <div>
                                                    <input type="text" rel="value" readonly value="{$attribute|escape:'htmlall':'UTF-8'}" style="width:250px"></option>
                                                    <span class="mapping-next">&nbsp;&nbsp;<img src="{$images_url|escape:'htmlall':'UTF-8'}next.png" alt=""/>&nbsp;&nbsp;</span>
                                                    <select class="select-mapping-entry" name="sizes_mapping[attribute][{$model_key|escape:'htmlall':'UTF-8'}][{$mapping.id_attribute_group|intval}][{$id_attribute|intval}]" style="width:250px">
                                                        {if isset($mapping.selected_attributes[$id_attribute])}
                                                            <option></option>
                                                            <option value="{$mapping.selected_attributes[$id_attribute|intval]}" selected class="stored-item">{$mapping.selected_attributes[$id_attribute|intval]}</option>
                                                        {/if}
                                                    </select>
                                                    {if $attribute@iteration == 1}
                                                        <span class="mapping-box-search"
                                                              rel="{$mapping.model_id|escape:'htmlall':'UTF-8'}"
                                                              data-category-id="{$mapping.category_id|escape:'htmlall':'UTF-8'}">
                                                            <img src="{$images_url|escape:'htmlall':'UTF-8'}icon-search.png" />
                                                        </span>
                                                    {/if}
                                                </div>
                                                {/foreach}

                                        </div>
                                        <div class="col-lg-9">
                                            <hr style="width:50%" />
                                        </div>
                                    </div>
                                {/foreach}
                            {/if}
                        </div><!-- size-attribute-mapping-section -->
                        {/if}

                        {if ($cd_mappings.has_size_features_fields)}
                            <div id="size-feature-mapping-section" class="col-lg-offset-3 margin-form" style="display:none;">
                                {if isset($cd_mappings.sizes_mapping) && is_array($cd_mappings.sizes_mapping)}
                                    {foreach from=$cd_mappings.sizes_mapping['features'] key=model_key item=mapping}
                                        <h4>{$mapping.title|escape:'htmlall':'UTF-8'}</h4>

                                        <div class="form-group size-group mapping-box-scope">
                                            <div class="margin-form col-lg-9 mapping-item" style="/*display:none*/" >
                                                {foreach from=$mapping.features_values key=id_feature_value item=feature_value}
                                                    <div>
                                                        <input type="text" rel="value" readonly value="{$feature_value.value|escape:'htmlall':'UTF-8'}" style="width:250px"></option>
                                                        <span class="mapping-next">&nbsp;&nbsp;<img src="{$images_url|escape:'htmlall':'UTF-8'}next.png" alt=""/>&nbsp;&nbsp;</span>
                                                        <select class="select-mapping-entry" name="sizes_mapping[feature][{$model_key|escape:'htmlall':'UTF-8'}][{$mapping.id_feature|intval}][{$id_feature_value|intval}]" style="width:250px">
                                                            {if isset($mapping.selected_features[$id_feature_value])}
                                                                <option></option>
                                                                <option value="{$mapping.selected_features[$id_feature_value|intval]}" selected class="stored-item">{$mapping.selected_features[$id_feature_value|intval]}</option>
                                                            {/if}
                                                        </select>
                                                        {if $feature_value@iteration == 1}
                                                        <span class="mapping-box-search"
                                                              rel="{$mapping.model_id|escape:'htmlall':'UTF-8'}"
                                                              data-category-id="{$mapping.category_id|escape:'htmlall':'UTF-8'}">
                                                            <img src="{$images_url|escape:'htmlall':'UTF-8'}icon-search.png" />
                                                        </span>
                                                        {/if}
                                                    </div>
                                                {/foreach}

                                            </div>
                                            <div class="col-lg-9">
                                                <hr style="width:50%" />
                                            </div>
                                        </div>
                                    {/foreach}
                                {/if}
                            </div><!-- size-feature-mapping-section -->
                        {/if}
                    </div>
                </div><!-- size-field-mapping -->
            {/if}



        <div class="form-group">
            <label class="control-label col-lg-3" style="color:green">{l s='Features' mod='cdiscount'}</label>
                        <span id="feature-mapping-collapse" class="col-lg-9">
                            <a href="javascript:void(0)">&nbsp;&nbsp;[ + ]&nbsp;&nbsp; {l s='Show' mod='cdiscount'}</a>
                            <a href="javascript:void(0)" style="display:none;">&nbsp;&nbsp;[ -
                                ]&nbsp;&nbsp; {l s='Hide' mod='cdiscount'}</a>
                        </span>

            <div class="col-lg-12"><br/>

                <div id="feature-mapping" style="display:none;">
                    {if isset($cd_mappings.features_mapping) && is_array($cd_mappings.features_mapping)}
                        {foreach from=$cd_mappings.features_mapping key=model item=mappings}
                            {foreach from=$mappings key=cdiscount_attribute_key item=mapping}
                                {if {!empty($mapping.title)}}
                                <div class="col-lg-offset-3 margin-form">
                                    <h4>{$mapping.title|escape:'htmlall':'UTF-8'}</h4>
                                </div>
                                {/if}
                                <div class="form-group">
                                    <label class="control-label col-lg-3" style="color:grey">{$mapping.name|escape:'htmlall':'UTF-8'}
                                        <span class="feature-mapping-item-action" class="col-lg-9">
                                            <a href="javascript:void(0)">&nbsp;&nbsp;[ + ]&nbsp;&nbsp; {l s='Show' mod='cdiscount'}</a>
                                            <a href="javascript:void(0)" style="display:none;">&nbsp;&nbsp;[ - ]&nbsp;&nbsp; {l s='Hide' mod='cdiscount'}</a>
                                        </span>
                                    </label>
                                    <div class="margin-form col-lg-9 feature-mapping-item" style="display:none">
                                        {foreach from=$mapping.valid_values key=valid_value_key item=valid_value_name}
                                            <div class="attribute-group">

                                                <input type="text" value="{$valid_value_name|escape:'htmlall':'UTF-8'}" style="width:250px">

                                                <span class="mapping-next">&nbsp;&nbsp;<img src="{$images_url|escape:'htmlall':'UTF-8'}next.png" alt=""/>&nbsp;&nbsp;</span>

                                                <select name="features_mapping[{$mapping.model_key|escape:'htmlall':'UTF-8'}][{$cdiscount_attribute_key|escape:'htmlall':'UTF-8'}][{$valid_value_key|escape:'htmlall':'UTF-8'}]" style="width:250px" >
                                                    <option disabled>{$mapping.feature.name|escape:'htmlall':'UTF-8'}</option>
                                                    <option></option>
                                                    {foreach from=$mapping.feature_values item=feature_value}
                                                        <option value="{$feature_value.id_feature_value|escape:'htmlall':'UTF-8'}" {if (isset($mapping.selected_values[$valid_value_key]) && $feature_value.id_feature_value == $mapping.selected_values[$valid_value_key])}selected{/if}>{$feature_value.value|escape:'htmlall':'UTF-8'}</option>
                                                    {/foreach}
                                                </select>

                                            </div>

                                        {/foreach}

                                        {if isset($mapping.matched)}
                                            <div class="form-group" style="margin-top:15px;">
                                                <div class="margin-form col-lg-offset-3">
                                                    <span style="color:darkgrey">{l s='Those values have been automatically matched' mod='cdiscount'}: </span><span style="color:darkgreen">{$mapping.matched|escape:'htmlall':'UTF-8'}</span>
                                                </div>
                                            </div>
                                        {/if}
                                        </select>
                                    </div>
                                    <div class="col-lg-9">
                                        <hr style="width:50%" />
                                    </div>
                                </div>
                            {/foreach}
                        {/foreach}
                    {/if}
                </div>
                <!-- feature mapping -->
            </div>
        </div>



        <div class="form-group">
            <label class="control-label col-lg-3" style="color:grey">{l s='Others Mappings' mod='cdiscount'}</label>
                        <span id="attribute-mapping-collapse" class="col-lg-9"><a style="display: inline;" href="javascript:void(0)">&nbsp;&nbsp;[ + ]&nbsp;&nbsp;{l s='Show' mod='cdiscount'}</a>
                            <a href="javascript:void(0)" style="display: none;">&nbsp;&nbsp;[ - ]&nbsp;&nbsp;{l s='Hide' mod='cdiscount'}</a>
                        </span>

            <div class="col-lg-12"><br/>

                <div id="attribute-mapping" style="display: none;">
                    {if isset($cd_mappings.attr_saved_groups) && is_array($cd_mappings.attr_saved_groups) && count($cd_mappings.attr_saved_groups)}
                        {foreach from=$cd_mappings.attr_saved_groups item=attr_saved_group}
                            {if $attr_saved_group.index == 0}
                                <label for="attribute-group-{$attr_saved_group.idgrp|escape:'htmlall':'UTF-8'}" class="control-label col-lg-3">{$attr_saved_group.attr_group_name|escape:'htmlall':'UTF-8'}</label>
                                <div class="margin-form col-lg-9">
                            {/if}
                            <div id="attribute-group-{$attr_saved_group.idgrp|escape:'htmlall':'UTF-8'}-{$attr_saved_group.index|escape:'htmlall':'UTF-8'}" type="hidden" name="fashion[group][{$attr_saved_group.idgrp|escape:'htmlall':'UTF-8'}][{$attr_saved_group.index|escape:'htmlall':'UTF-8'}]" rel="{$attr_saved_group.index|escape:'htmlall':'UTF-8'}">
                                <select name="fashion[prestashop][{$attr_saved_group.idgrp|escape:'htmlall':'UTF-8'}][{$attr_saved_group.selected|escape:'htmlall':'UTF-8'}]" rel="{$attr_saved_group.idgrp|escape:'htmlall':'UTF-8'}-{$attr_saved_group.selected|escape:'htmlall':'UTF-8'}"
                                        class="can-be-copied-mapping"
                                        style="width:250px"  {$attr_saved_group.disabled|escape:'htmlall':'UTF-8'}>
                                    <option value=""></option>
                                    {if isset($attr_saved_group.groups)}
                                        {foreach from=$attr_saved_group.groups item=cd_group_attr}
                                            <option value="{$cd_group_attr.value|escape:'htmlall':'UTF-8'}" {$cd_group_attr.selected|escape:'htmlall':'UTF-8'} >{$cd_group_attr.desc|escape:'htmlall':'UTF-8'}</option>
                                        {/foreach}
                                    {/if}
                                </select>
                                <span class="mapping-next can-copy-mapping">&nbsp;&nbsp;<img src="{$images_url|escape:'htmlall':'UTF-8'}next.png" alt=""/>&nbsp;&nbsp;</span>
                                <input type="text" name="fashion[cdiscount][{$attr_saved_group.idgrp|escape:'htmlall':'UTF-8'}][{$attr_saved_group.selected|escape:'htmlall':'UTF-8'}]"
                                       class="can-receive-copy-mapping"
                                       value="{$attr_saved_group.fashion_cd_value|escape:'htmlall':'UTF-8'}" style="width:250px" {$attr_saved_group.disabled|escape:'htmlall':'UTF-8'} />
                                <!--</select>&nbsp;&nbsp;-->&nbsp;&nbsp;
                                <img src="{$images_url|escape:'htmlall':'UTF-8'}plus.png" class="addnewmapping" id="button-add-{$attr_saved_group.idgrp|escape:'htmlall':'UTF-8'}-{$attr_saved_group.index|escape:'htmlall':'UTF-8'}" alt="{l s='Add a new mapping' mod='cdiscount'}" style="{$attr_saved_group.display_add|escape:'htmlall':'UTF-8'}"/>
                                <img src="{$images_url|escape:'htmlall':'UTF-8'}minus.png" class="removemapping" id="button-del-{$attr_saved_group.idgrp|escape:'htmlall':'UTF-8'}-{$attr_saved_group.index|escape:'htmlall':'UTF-8'}" alt="{l s='Remove mapping' mod='cdiscount'}" style="{$attr_saved_group.display_del|escape:'htmlall':'UTF-8'}"/>
                            </div>
                            {if isset($attr_saved_group.last)}
                                <div id="new-mapping-{$attr_saved_group.idgrp|escape:'htmlall':'UTF-8'}"></div>

                                <hr style="width:50%" />
                                </div>
                            {/if}

                        {/foreach}
                    {/if}
                </div>
            </div>
        </div>

        {include file="$module_path/views/templates/admin/configure/validate.tpl"}
    </div>
{/if}
<!--MAPPINGS END -->

<!--TRANSPORT START -->
{if isset($cd_transport) && $cd_transport}
    <div id="conf-transport" class="tabItem">
    <h2>{l s='Transport' mod='cdiscount'}</h2>

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>

        <div class="margin-form col-lg-9">
            <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                {l s='Please follow our online tutorial' mod='cdiscount'} :<br>
                <a href="http://documentation.common-services.com/cdiscount/transport/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                   target="_blank">http://documentation.common-services.com/cdiscount/transport/</a><br>
            </div>
        </div>
    </div>


    {*Aug-23-2018: Remove Carriers/Modules option. We list all carriers, include module's carriers*}

    <div class="form-group">
        <label class="control-label col-lg-3" style="color:grey">{l s='Preparation Time' mod='cdiscount'}</label><br/>

        <div class="margin-form col-lg-9">&nbsp;</div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3"
               for="preparation_time"></label>

        <div class="margin-form col-lg-9">
            <input id="preparation_time" type="text" style="width:50px;" name="preparation_time"
                   value="{$cd_transport.preparation_time|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;
            <span class="input_note">{l s='Delay before shipment' mod='cdiscount'}</span><br/>
            <hr style="width:50%; margin-top:15px;"/>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3" style="color:grey">{l s='Shipping Fees' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">&nbsp;</div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>

        <div class="margin-form carrier-info col-lg-9">
            <table class="titles">
                <tr>
                    <td style="width:320px;">
                        <span>{l s='Carrier' mod='cdiscount'}</span>
                    </td>
                    <td style="width:80px;">
                        <span>{l s='Min Charge' mod='cdiscount'}*</span>
                    </td>
                    <td style="width:80px;opacity:0.3;">
                        <span>{l s='Add.Charge' mod='cdiscount'}</span>
                    </td>
                </tr>
            </table>
            <em>{l s='Standards' mod='cdiscount'}</em><br/>
        </div>
    </div>

    <div class="form-group">

        {foreach from=$cd_transport.carriers_params key=name item=cdiscount_carrier}
            {if !$cdiscount_carrier.optionnal}

                <label class="control-label col-lg-3">{$cd_transport.carriers_labels[$name]|escape:'quotes':'UTF-8'}</label>
                <div class="margin-form col-lg-9">
                    <select name="carriers_mapping[{$name|escape:'htmlall':'UTF-8'}]" style="width:300px;">
                        <option value="">{l s='Choose a suitable carrier for this shipping method' mod='cdiscount'}</option>
                        {foreach from=$cd_transport.carriers_info[$name] item=carrier_option}
                            <option value="{$carrier_option.value|escape:'htmlall':'UTF-8'}" {$carrier_option.selected|escape:'htmlall':'UTF-8'} >{$carrier_option.desc|escape:'htmlall':'UTF-8'} </option>
                        {/foreach}
                    </select>
                    <input type="hidden" name="carriers_params[{$name|escape:'htmlall':'UTF-8'}][Code]" value="{$cd_transport.carriers_params[$name].Code|escape:'htmlall':'UTF-8'}"/>
                    <input name="carriers_params[{$name|escape:'htmlall':'UTF-8'}][ChargeMin]" class="carrier-params carrier-mandatory"
                           value="{$cd_transport.carriers_params[$name].ChargeMin|escape:'htmlall':'UTF-8'}"/>
                    <input name="carriers_params[{$name|escape:'htmlall':'UTF-8'}][ChargeAdd]" class="carrier-params"
                           value="{$cd_transport.carriers_params[$name].ChargeAdd|escape:'htmlall':'UTF-8'}"/>
                </div>
            {/if}
        {/foreach}

    </div>

    {if (isset($cd_transport.carriers_optionnals) && $cd_transport.carriers_optionnals)}
        <div class="form-group">
            <label class="control-label col-lg-3">&nbsp;</label>

            <div class="margin-form col-lg-9">
                <em>{l s='Optionals' mod='cdiscount'}</em>
                <br><br>
            </div>
            {foreach from=$cd_transport.carriers_params key=name item=cdiscount_carrier}
                {if $cdiscount_carrier.optionnal}
                    <label class="control-label col-lg-3" style="color:grey">{$cd_transport.carriers_labels[$name]|escape:'quotes':'UTF-8'}</label>
                    <div class="margin-form col-lg-9">
                        <select name="carriers_mapping[{$name|escape:'htmlall':'UTF-8'}]" style="width:300px;">
                            <option value="">{l s='Choose an appropriate carrier for' mod='cdiscount'}&nbsp;{$name|escape:'htmlall':'UTF-8'}</option>
                            {foreach from=$cd_transport.carriers_info[$name] item=carrier_option}
                                <option value="{$carrier_option.value|escape:'htmlall':'UTF-8'}" {$carrier_option.selected|escape:'htmlall':'UTF-8'} >{$carrier_option.desc|escape:'htmlall':'UTF-8'} </option>
                            {/foreach}
                        </select>
                        <input type="hidden" name="carriers_params[{$name|escape:'htmlall':'UTF-8'}][Code]" value="{$cdiscount_carrier.Code|escape:'htmlall':'UTF-8'}"/>
                        <input name="carriers_params[{$name|escape:'htmlall':'UTF-8'}][ChargeMin]" class="carrier-params"
                               value="{$cdiscount_carrier.ChargeMin|escape:'htmlall':'UTF-8'}"/>
                        <input name="carriers_params[{$name|escape:'htmlall':'UTF-8'}][ChargeAdd]" class="carrier-params"
                               value="{$cdiscount_carrier.ChargeAdd|escape:'htmlall':'UTF-8'}"/>
                    </div>
                {/if}
            {/foreach}

            <label class="control-label col-lg-3">&nbsp;</label>

            <div class="margin-form col-lg-9">
                <p>{l s='Please associate the carriers and configure their additionnals parameters, please refer to the documentation for more informations about this configuration' mod='cdiscount'}</p>
            </div>
        </div>
    {/if}

    {if (isset($cd_transport.carriers_clogistique) && count($cd_transport.carriers_clogistique))}
    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>

        <div class="margin-form col-lg-9">
            <em>{l s='C Logistique' mod='cdiscount'}</em>
            <br><br>
        </div>
        {if isset($cd_transport.carriers_clogistique.clogistique)}
            {foreach from=$cd_transport.carriers_clogistique.clogistique key=clogistique_carrier_key item=clogistique_carrier}
                <label class="control-label col-lg-3" style="color:grey">{$clogistique_carrier.label|escape:'htmlall':'UTF-8'}</label>
                <div class="margin-form col-lg-9">
                    <select name="carriers_clogistique[{$clogistique_carrier_key|escape:'htmlall':'UTF-8'}]" style="width:300px;">
                        <option value="">{l s='Choose an appropriate carrier for' mod='cdiscount'} {$clogistique_carrier.label|escape:'htmlall':'UTF-8'}</option>
                        {foreach from=$cd_transport.carriers_clogistique.prestashop item=prestashop_carrier}
                            <option value="{$prestashop_carrier.id_carrier|escape:'htmlall':'UTF-8'}" {if $cd_transport.carriers_clogistique.mapping.$clogistique_carrier_key == $prestashop_carrier.id_carrier}selected{/if} >{$prestashop_carrier.desc|escape:'htmlall':'UTF-8'} </option>
                        {/foreach}
                    </select>
                </div>
            {/foreach}
        {/if}
    </div>
    {/if}


    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Tracking Notifications' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">&nbsp;</div>
    </div>
    {if is_array($cd_transport.tracking_mapping) && count($cd_transport.tracking_mapping)}
            {if is_array($cd_transport.tracking_carriers) && count($cd_transport.tracking_carriers)}

                <div class="form-group">
                    <label class="control-label col-lg-3">&nbsp;</label>
                    <div class="margin-form carrier-info col-lg-9">
                        <table class="titles">
                            <tr>
                                <td style="width:320px;">
                                    <span>{l s='Associated Service' mod='cdiscount'}</span>
                                </td>
                                <td style="opacity:0.3;">
                                    <span>{l s='Tracking URL' mod='cdiscount'}</span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="form-group">
                {foreach from=$cd_transport.tracking_mapping key=id_carrier item=tracking_mapping}
                    <label class="control-label col-lg-3" style="color:grey">{$tracking_mapping.prestashop_name|escape:'htmlall':'UTF-8'}</label>
                    <div class="margin-form col-lg-9">
                        <select name="tracking_mapping[{$id_carrier|escape:'htmlall':'UTF-8'}]" style="width:300px;">
                            <option value="">{l s='Choose a service to associate with this carrier' mod='cdiscount'}</option>
                            {foreach from=$cd_transport.tracking_carriers key=cdiscount_carrier_id item=cdiscount_carrier}
                                <option value="{$cdiscount_carrier_id|intval}" {if $tracking_mapping.cdiscount_id == $cdiscount_carrier_id}selected{/if}>{$cdiscount_carrier.Name|escape:'htmlall':'UTF-8'}</option>
                            {/foreach}
                        </select>
                        &nbsp;&nbsp;&nbsp;&nbsp;
                        {foreach from=$cd_transport.tracking_carriers key=cdiscount_carrier_id item=cdiscount_carrier}
                            {if $tracking_mapping.cdiscount_id == $cdiscount_carrier_id}
                                <span><a href="{$cdiscount_carrier.DefaultURL|escape:'htmlall':'UTF-8'}" target="_blank" title="{$cdiscount_carrier.Name|escape:'htmlall':'UTF-8'}" >{$cdiscount_carrier.DefaultURL|escape:'htmlall':'UTF-8'}</a></span>
                            {/if}
                        {/foreach}
                    </div>
                {/foreach}
                </div>
            {/if}

    {else}
        <div class="form-group">
            <label class="control-label col-lg-3">&nbsp;</label>
            <div class="margin-form carrier-info col-lg-9">
               <p>{l s='Please configure your carrier selection first, then save the configuration to be able to access to this section' mod='cdiscount'}</p>
            </div>
        </div>
    {/if}




    <div class="form-group">
        <label class="control-label col-lg-3">* {l s='Min Charge' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
                {l s='This is not the shipping fees amount, this is the ***minimum*** charge, for more informations please refer to our tutorial' mod='cdiscount'} :<br>
                <a href="http://documentation.common-services.com/cdiscount/transport/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                   target="_blank">http://documentation.common-services.com/cdiscount/transport/</a><br>
        </div>
    </div>

    {include file="$module_path/views/templates/admin/configure/validate.tpl"}
    </div>
{/if}
<!--TRANSPORT END -->

<!--ORDERS START -->
{if isset($cd_orders) && $cd_orders}
    <div id="conf-orders" class="tabItem">
        <h2>{l s='Orders' mod='cdiscount'}</h2>

        <div class="form-group">
            <label class="control-label col-lg-3">&nbsp;</label>

            <div class="margin-form col-lg-9">
                <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                    {l s='Please follow our online tutorial' mod='cdiscount'} :<br>
                    <a href="http://documentation.common-services.com/cdiscount/statuts-commandes/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                       target="_blank">http://documentation.common-services.com/cdiscount/statuts-commandes/</a><br>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Orders Statuses' mod='cdiscount'}</label>

            <div class="margin-form col-lg-9">
                <select name="orderstate[CDISCOUNT_CA]" style="width:500px;">
                    <option>{l s='Choose a default incoming order status for CDiscount' mod='cdiscount'}</option>
                    {foreach from=$cd_orders.cd_mapping_order_states_01 item=cd_mapping_state}
                        <option value="{$cd_mapping_state.value|escape:'htmlall':'UTF-8'}" {$cd_mapping_state.selected|escape:'htmlall':'UTF-8'}>{$cd_mapping_state.desc|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>

                <p>{l s='Choose the default order state for new incoming orders' mod='cdiscount'}</p>
            </div>
            <label class="control-label col-lg-3">&nbsp;</label>

            <div class="margin-form col-lg-9">
                <select name="orderstate[CDISCOUNT_CE]" style="width:500px;">
                    <option>{l s='Choose a default sent order status for CDiscount' mod='cdiscount'}</option>
                    '
                    {foreach from=$cd_orders.cd_mapping_order_states_02 item=cd_mapping_state}
                        <option value="{$cd_mapping_state.value|escape:'htmlall':'UTF-8'}" {$cd_mapping_state.selected|escape:'htmlall':'UTF-8'}>{$cd_mapping_state.desc|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>

                <p>{l s='Choose the default order state for sent orders' mod='cdiscount'}</p>
            </div>
            <label class="control-label col-lg-3">&nbsp;</label>

            <div class="margin-form col-lg-9">
                <select name="orderstate[CDISCOUNT_CL]" style="width:500px;">
                    <option>{l s='Choose a default sent order status for CDiscount' mod='cdiscount'}</option>
                    {foreach from=$cd_orders.cd_mapping_order_states_03 item=cd_mapping_state}
                        <option value="{$cd_mapping_state.value|escape:'htmlall':'UTF-8'}" {$cd_mapping_state.selected|escape:'htmlall':'UTF-8'}>{$cd_mapping_state.desc|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>

                <p>{l s='Choose the default order state for delivered orders' mod='cdiscount'}</p>
            </div>

            {if isset($cd_orders.cd_mapping_order_states_cl)}
            <label class="control-label col-lg-3">&nbsp;</label>

            <div class="margin-form col-lg-9">
                <select name="orderstate[CDISCOUNT_CLCL]" style="width:500px;">
                    <option>{l s='Choose a default sent order status for CDiscount C Logistique' mod='cdiscount'}</option>
                    {foreach from=$cd_orders.cd_mapping_order_states_cl item=cd_mapping_state}
                        <option value="{$cd_mapping_state.value|escape:'htmlall':'UTF-8'}" {$cd_mapping_state.selected|escape:'htmlall':'UTF-8'}>{$cd_mapping_state.desc|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>

                <p>{l s='Choose the default order state for delivered C Logistique orders' mod='cdiscount'}</p>
            </div>
            {/if}
        </div>
        <br/>
        <hr style="width:30%"/>
        {if ( $cd_orders.expert_mode )}
            <br/>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Bulk Mode' mod='cdiscount'}<span class="expert">{l s='Expert' mod='cdiscount'}</span></label>

                <div class="margin-form col-lg-9">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="bulk_mode" id="bulk_mode_1" value="1"
                                       {if strlen($cd_orders.bulk_mode)}checked{/if} /><label for="bulk_mode_1"
                                                                                              class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                                <input type="radio" name="bulk_mode" id="bulk_mode_0" value="0"
                                       {if !strlen($cd_orders.bulk_mode)}checked{/if}  /><label for="bulk_mode_0"
                                                                                                class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                                <a class="slide-button btn"></a>
                            </span>
                    <br/>

                    <p>{l s='Update the orders statuses in bulk mode (a new cron task will appear in scheduled taskes tab)' mod='cdiscount'}</p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Extra Fees' mod='cdiscount'}<span class="expert">{l s='Expert' mod='cdiscount'}</span></label>

                <div class="margin-form col-lg-9">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="extra_fees" id="extra_fees_1" value="1"
                                       {if strlen($cd_orders.extra_fees)}checked{/if} /><label for="extra_fees_1"
                                                                                              class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                                <input type="radio" name="extra_fees" id="extra_fees_0" value="0"
                                       {if !strlen($cd_orders.extra_fees)}checked{/if}  /><label for="extra_fees_0"
                                                                                                class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                                <a class="slide-button btn"></a>
                            </span>
                    <br/>

                    <p>{l s='Import extra fees, handling fees, all charges applied by CDiscount as a virtual product' mod='cdiscount'}</p>
                </div>
            </div>
        {else}
            <input type="hidden" name="bulk_mode" value="1"/>
            <input type="hidden" name="extra_fees" value="0"/>
        {/if}
        {include file="$module_path/views/templates/admin/configure/validate.tpl"}
    </div>
{/if}
<!--ORDERS END -->

<!--SETTINGS START -->
{if (isset($cd_settings) && $cd_settings)}
    <div id="conf-settings" class="tabItem">
    <h2>{l s='Settings' mod='cdiscount'}</h2>

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>

        <div class="margin-form col-lg-9">
            <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                {l s='Please follow our online tutorial' mod='cdiscount'} :<br>
                <a href="http://documentation.common-services.com/cdiscount/parametres/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                   target="_blank">http://documentation.common-services.com/cdiscount/parametres/</a><br>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Discount/Specials' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="specials" id="specials_1" value="1"
                                       {if strlen($cd_settings.specials)}checked{/if} /><label for="specials_1"
                                                                                               class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                                <input type="radio" name="specials" id="specials_0" value="0"
                                       {if !strlen($cd_settings.specials)}checked{/if}  /><label for="specials_0"
                                                                                                 class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                                <a class="slide-button btn"></a>
                            </span>
            <br/>

            <p>{l s='Export specials prices if is sets Yes. If unsets the discounted prices will be ignorates' mod='cdiscount'}</p>
        </div>

        {if $cd_settings.expert_mode_checked}
        <div id="on_sale_period" style="{if ! strlen($cd_settings.specials)}display:none;{/if}">
            <label class="control-label col-lg-3">{l s='Sales Season' mod='cdiscount'}<span class="expert">{l s='Expert' mod='cdiscount'}</span></label>

            <div class="margin-form col-lg-9">
                                <span class="switch prestashop-switch fixed-width-lg">
                                    <input type="radio" name="on_sale_period" id="on_sale_period_1" value="1"
                                           {if strlen($cd_settings.on_sale_period)}checked{/if} /><label
                                            for="on_sale_period_1"
                                            class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                                    <input type="radio" name="on_sale_period" id="on_sale_period_0" value="0"
                                           {if !strlen($cd_settings.on_sale_period)}checked{/if}  /><label
                                            for="on_sale_period_0"
                                            class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                                    <a class="slide-button btn"></a>
                                </span>
                <br/>

                <p>{l s='We are in sales season, send products tagged "On Sale" as sales' mod='cdiscount'}</p>
            </div>
        </div>
        {else}
            <input type="hidden" name="on_sale_period" value="1" />
        {/if}


        <div id="formula_on_specials" style="{if ! strlen($cd_settings.specials)}display:none;{/if}">
            <label class="control-label col-lg-3">{l s='Price Rule on Discount' mod='cdiscount'}</label>

            <div class="margin-form col-lg-9">
                                <span class="switch prestashop-switch fixed-width-lg">
                                    <input type="radio" name="formula_on_specials" id="formula_on_specials_1" value="1"
                                           {if strlen($cd_settings.formula_on_specials)}checked{/if} /><label
                                            for="formula_on_specials_1"
                                            class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                                    <input type="radio" name="formula_on_specials" id="formula_on_specials_0" value="0"
                                           {if !strlen($cd_settings.formula_on_specials)}checked{/if}  /><label
                                            for="formula_on_specials_0"
                                            class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                                    <a class="slide-button btn"></a>
                                </span>
                <br/>

                <p>{l s='Apply price rule on specials prices' mod='cdiscount'}</p>
            </div>
        </div>

        {if $cd_settings.expert_mode_checked}
            <label class="control-label col-lg-3">{l s='Taxes' mod='cdiscount'}<span class="expert">{l s='Expert' mod='cdiscount'}</span></label>
            <div class="margin-form col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="taxes" id="taxes_1" value="1"
                           {if strlen($cd_settings.taxes)}checked{/if} /><label for="taxes_1"
                                                                                class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                    <input type="radio" name="taxes" id="taxes_0" value="0"
                           {if !strlen($cd_settings.taxes)}checked{/if}  /><label for="taxes_0"
                                                                                  class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                    <a class="slide-button btn"></a>
                </span>
                <br/>

                <p>{l s='Add taxes to products and calculate order\'s taxes if sets to yes' mod='cdiscount'}</p>
             </div>
        {else}

            <input type="hidden" name="taxes" value="1"/>
        {/if}


        {if $cd_settings.expert_mode_checked}
            <label class="control-label col-lg-3">{l s='C Logistique' mod='cdiscount'}<span class="expert">{l s='Expert' mod='cdiscount'}</span></label>
            <div class="margin-form col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="clogistique" id="clogistique_1" value="1"
                           {if strlen($cd_settings.clogistique)}checked{/if} /><label for="clogistique_1"
                                                                                class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                    <input type="radio" name="clogistique" id="clogistique_0" value="0"
                           {if !strlen($cd_settings.clogistique)}checked{/if}  /><label for="clogistique_0"
                                                                                  class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                    <a class="slide-button btn"></a>
                </span>
                <br/>
                <p>{l s='Activate C Logistique features' mod='cdiscount'}</p>
            </div>

            <div id="clogistique_destock_section" {if !strlen($cd_settings.clogistique)}style="display:none"{/if}>
                <label class="control-label col-lg-3">{l s='Decrease Quantities (C Logistique)' mod='cdiscount'}<span class="expert">{l s='Expert' mod='cdiscount'}</span></label>
                <div class="margin-form col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="clogistique_destock" id="clogistique_destock_1" value="1"
                               {if strlen($cd_settings.clogistique_destock)}checked{/if} /><label for="clogistique_destock_1" class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                        <input type="radio" name="clogistique_destock" id="clogistique_destock_0" value="0"
                               {if !strlen($cd_settings.clogistique_destock)}checked{/if}  /><label for="clogistique_destock_0" class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <br/>
                    <p>{l s='Decrease quantities for C Logistiques ordered products' mod='cdiscount'}</p>
                </div>
            </div>
        {else}
            <input type="hidden" name="clogistique" value="0"/>
        {/if}




        {if $cd_settings.expert_mode_checked}
            <label class="control-label col-lg-3">{l s='Disabling Proxy' mod='cdiscount'}<span class="expert">{l s='Expert' mod='cdiscount'}</span></label>
            <div class="margin-form col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="disable_proxy" id="disable_proxy_1" value="1"
                           {if strlen($cd_settings.disable_proxy)}checked{/if} /><label for="disable_proxy_1"
                                                                                      class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                    <input type="radio" name="disable_proxy" id="disable_proxy_0" value="0"
                           {if !strlen($cd_settings.disable_proxy)}checked{/if}  /><label for="disable_proxy_0"
                                                                                        class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                    <a class="slide-button btn"></a>
                </span>
                <br/>
                <p>{l s='Proxy mode is activated by default to improve performances and prevent blacklisting' mod='cdiscount'}</p>
            </div>

            <div id="clogistique_destock_section" {if !strlen($cd_settings.clogistique)}style="display:none"{/if}>
                <label class="control-label col-lg-3">{l s='Decrease Quantities (C Logistique)' mod='cdiscount'}<span class="expert">{l s='Expert' mod='cdiscount'}</span></label>
                <div class="margin-form col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="clogistique_destock" id="clogistique_destock_1" value="1"
                               {if strlen($cd_settings.clogistique_destock)}checked{/if} /><label for="clogistique_destock_1" class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                        <input type="radio" name="clogistique_destock" id="clogistique_destock_0" value="0"
                               {if !strlen($cd_settings.clogistique_destock)}checked{/if}  /><label for="clogistique_destock_0" class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <br/>
                    <p>{l s='Decrease quantities for C Logistiques ordered products' mod='cdiscount'}</p>
                </div>
            </div>
        {else}
            <input type="hidden" name="clogistique" value="0"/>
        {/if}


        <label class="control-label col-lg-3">{l s='Smart Rounding' mod='cdiscount'}</label>
            <div class="margin-form col-lg-9">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="smart_rounding" id="smart_rounding_1" value="1"
                                       {if strlen($cd_settings.smart_rounding)}checked{/if} /><label for="smart_rounding_1"
                                                                                                     class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                                <input type="radio" name="smart_rounding" id="smart_rounding_0" value="0"
                                       {if !strlen($cd_settings.smart_rounding)}checked{/if}  /><label for="smart_rounding_0"
                                                                                                       class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                                <a class="slide-button btn"></a>
                            </span>
            <br/>

            <p>{l s='Smart rounds prices. Adjusts 10.53 to 10.59, 10.00 to 9.99' mod='cdiscount'}</p>
        </div>

        {*
        <label for="allow_oos">{l s='Allow Out Of Stock' mod='cdiscount'}</label>
        <div class="margin-form col-lg-9">
        <input id="allow_oos" type="checkbox" name="allow_oos" value="1" {$cd_settings.oos_checked|escape:'htmlall':'UTF-8'}/><br />
        <p>{l s='If Checked, Allow to Export Out of Stock Products' mod='cdiscount'}</p>
        </div>
        *}

        <input id="allow_oos" type="hidden" name="allow_oos" value="0"/>

        {if $cd_settings.expert_mode_checked}
        <label class="control-label col-lg-3" for="import_type">{l s='Import/Export Type' mod='cdiscount'}<span class="expert">{l s='Expert' mod='cdiscount'}</span></label>

        <div class="margin-form col-lg-9">
            <input name="import_type" {$cd_settings.import_by_id_checked|escape:'htmlall':'UTF-8'} value="{$cd_settings.import_by_id|escape:'htmlall':'UTF-8'}" type="radio">&nbsp;&nbsp;
            <span style="position:relative;top:+3px">{l s='Import By ID' mod='cdiscount'} </span>&nbsp;&nbsp;&nbsp;
            <input name="import_type" {$cd_settings.import_by_sku_checked|escape:'htmlall':'UTF-8'} value="{$cd_settings.import_by_sku|escape:'htmlall':'UTF-8'}" type="radio">&nbsp;&nbsp;
            <span style="position:relative;top:+3px">{l s='Import By SKU' mod='cdiscount'}
                <span style="color:red;">&nbsp;({l s='Highly Recommended' mod='cdiscount'}
                    )</span></span>

            <p>{l s='Please choose the indexation method for products' mod='cdiscount'}</p>
        </div>
        {else}
            <input type="hidden" name="import_type" {if $cd_settings.import_by_id_checked}value="{$cd_settings.import_by_id|escape:'htmlall':'UTF-8'}"{else}value="{$cd_settings.import_by_sku|escape:'htmlall':'UTF-8'}"{/if} >
        {/if}

        <label class="control-label col-lg-3" for="description_field">{l s='Short Description Field' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
            <input type="radio" name="description_field" {$cd_settings.short_description_checked|escape:'htmlall':'UTF-8'} value="{$cd_settings.short_description|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;
            <span style="position:relative;top:+3px">{l s='Short Description' mod='cdiscount'}</span>&nbsp;&nbsp;&nbsp;
            <input type="radio" name="description_field" {$cd_settings.long_description_checked|escape:'htmlall':'UTF-8'} value="{$cd_settings.long_description|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;
            <span style="position:relative;top:+3px">{l s='Long Description' mod='cdiscount'}</span>

            <p>{l s='Field used as product description' mod='cdiscount'}</p>
        </div>


        <label class="control-label col-lg-3">{l s='Marketing Description' mod='cdiscount'}</label>
        <div class="margin-form col-lg-9">
            <span class="switch prestashop-switch fixed-width-lg">
                <input type="radio" name="marketing_description" id="marketing_description_1" value="1" {if $cd_settings.marketing_description}checked{/if} />
                <label for="marketing_description_1" class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                <input type="radio" name="marketing_description" id="marketing_description_0" value="0" {if !$cd_settings.marketing_description}checked{/if}  />
                <label for="marketing_description_0" class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                <a class="slide-button btn"></a>
            </span>
            <br/>
            <p>{l s='Check this box if you want to export your Description as a Marketing Description, HTML is allowed and sent. Do not check this option if your descriptions contain URLs or advertising' mod='cdiscount'}</p>
        </div>
        <div class="available_if_mkt_desc_enable {if !$cd_settings.marketing_description}hidden{/if}">
            <label class="control-label col-lg-3" for="long_description_field">{l s='Long Description Field' mod='cdiscount'}</label>
            <div class="margin-form col-lg-9">
                <input type="radio" name="long_description_field" {$cd_settings.long_description_field_short_checked|escape:'htmlall':'UTF-8'} value="{$cd_settings.short_description|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;
                <span style="position:relative;top:+3px">{l s='Short Description' mod='cdiscount'}</span>&nbsp;&nbsp;&nbsp;
                <input type="radio" name="long_description_field" {$cd_settings.long_description_field_long_checked|escape:'htmlall':'UTF-8'} value="{$cd_settings.long_description|escape:'htmlall':'UTF-8'}"/>&nbsp;&nbsp;
                <span style="position:relative;top:+3px">{l s='Long Description' mod='cdiscount'}</span>
                <p>{l s='Field used as long product description - this field can contain HTML' mod='cdiscount'}</p>
            </div>
        </div>

        <label class="control-label col-lg-3">{l s='Product Title' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9 title-formats">
            <input type="radio" name="title_format" value="{$cd_settings.title_formats.title_name_attributes_with_label|escape:'htmlall':'UTF-8'}"
                   {if ($cd_settings.title_format == $cd_settings.title_formats.title_name_attributes_with_label)}checked{/if} id="title-format-6" />
            <label class="label-checkbox" for="title-format-6">{l s='Name, Attributes with label' mod='cdiscount'}</label>
            {l s='eg: Mickey (Color: Blue - Size: L)' mod='cdiscount'}
            <br/>
            <input type="radio" name="title_format" value="{$cd_settings.title_formats.title_name_attributes|escape:'htmlall':'UTF-8'}"
                   {if ($cd_settings.title_format == $cd_settings.title_formats.title_name_attributes)}checked{/if} id="title-format-1" />
            <label class="label-checkbox" for="title-format-1">{l s='Name, Attributes' mod='cdiscount'}</label>
            {l s='eg: Mickey (Blue - L)' mod='cdiscount'}
            <br/>
            <input type="radio" name="title_format" value="{$cd_settings.title_formats.title_brand_name_attributes|escape:'htmlall':'UTF-8'}"
                   {if ($cd_settings.title_format == $cd_settings.title_formats.title_brand_name_attributes)}checked{/if} id="title-format-2" />
            <label class="label-checkbox" for="title-format-2">{l s='Brand (Manufacturer), Name, Attributes' mod='cdiscount'}</label>
            {l s='eg: Disney - Mickey (Blue - L)' mod='cdiscount'}
            <br/>
            <input type="radio" name="title_format" value="{$cd_settings.title_formats.title_category_name_attributes|escape:'htmlall':'UTF-8'}"
                   {if ($cd_settings.title_format == $cd_settings.title_formats.title_category_name_attributes)}checked{/if} id="title-format-3" />
            <label class="label-checkbox" for="title-format-3">{l s='Category (CDiscount), Name, Attributes' mod='cdiscount'}</label>
            {l s='eg: T-Shirt - Mickey (Blue - L)' mod='cdiscount'}
            <br/>
            <input type="radio" name="title_format" value="{$cd_settings.title_formats.title_category_brand_name_attributes|escape:'htmlall':'UTF-8'}"
                   {if ($cd_settings.title_format == $cd_settings.title_formats.title_category_brand_name_attributes)}checked{/if} id="title-format-4" />
            <label class="label-checkbox" for="title-format-4">{l s='Category (CDiscount), Brand, Name, Attributes' mod='cdiscount'}</label>
            {l s='eg: T-Shirt - Disney - Mickey (Blue - L)' mod='cdiscount'}
            <br/>
            <input type="radio" name="title_format" value="{$cd_settings.title_formats.title_name_reference|escape:'htmlall':'UTF-8'}"
                   {if ($cd_settings.title_format == $cd_settings.title_formats.title_name_reference)}checked{/if} id="title-format-5" />
            <label class="label-checkbox" for="title-format-5">{l s='Name, Reference' mod='cdiscount'}</label>
            {l s='eg: T-Shirt - TSHIRT-BLUE-XL' mod='cdiscount'}
            <br/>
            <br>
        </div>

        <label class="control-label col-lg-3">{l s='EAN Policy' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9 ean-policies">
            <input type="radio" name="ean_policy" value="{$cd_settings.ean_policies.normal|escape:'htmlall':'UTF-8'}"
                   {if ($cd_settings.ean_policy == $cd_settings.ean_policies.normal)}checked{/if} /><label
                    class="label-checkbox">{l s='Normal' mod='cdiscount'}</label>{l s='Export product with EAN13 codes only' mod='cdiscount'}
            <br/>
            <input type="radio" name="ean_policy" value="{$cd_settings.ean_policies.exempt|escape:'htmlall':'UTF-8'}"
                   {if ($cd_settings.ean_policy == $cd_settings.ean_policies.exempt)}checked{/if} /><label
                    class="label-checkbox">{l s='Exempted' mod='cdiscount'}</label>{l s='You are allowed to export all your catalog without EAN13 codes' mod='cdiscount'}
            <br/>
            <input type="radio" name="ean_policy"
                   value="{$cd_settings.ean_policies.permissive|escape:'htmlall':'UTF-8'}"
                   {if ($cd_settings.ean_policy == $cd_settings.ean_policies.permissive)}checked{/if} /><label
                    class="label-checkbox">{l s='Permissive' mod='cdiscount'}</label>{l s='Allows to export products without EAN13 code' mod='cdiscount'}
            <br/>
            <br>
        </div>

        <label class="control-label col-lg-3">{l s='Automatic Repricing/Alignment' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="align_active" id="align_active_1" value="1"
                                       {if strlen($cd_settings.align_active_checked)}checked{/if} /><label
                                        for="align_active_1" class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                                <input type="radio" name="align_active" id="align_active_0" value="0"
                                       {if !strlen($cd_settings.align_active_checked)}checked{/if}  /><label
                                        for="align_active_0" class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                                <a class="slide-button btn"></a>
                            </span>
            <br>

            <p>{l s='Check this box if you want to active the repricing feature' mod='cdiscount'}</p>
        </div>


        <label class="control-label col-lg-3"
               style="{if $cd_settings.expert_mode_checked}color:red{/if}">{l s='Expert Mode' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="expert_mode" id="expert_mode_1" value="1"
                                       {if strlen($cd_settings.expert_mode_checked)}checked{/if} /><label
                                        for="expert_mode_1" class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                                <input type="radio" name="expert_mode" id="expert_mode_0" value="0"
                                       {if !strlen($cd_settings.expert_mode_checked)}checked{/if}  /><label
                                        for="expert_mode_0" class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                                <a class="slide-button btn"></a>
                            </span>
            <br>

            <p>{l s='Active the Expert Mode. Do not active this option without the recommandation of our technical support. Otherwise, your support could be restricted.' mod='cdiscount'}</p>
        </div>

    </div>

    <hr style="width:50%"/>

    {if isset($cd_settings.ps_version_gt_15_or_equal)}
        {if isset($cd_settings.ps_advanced_stock_management)}
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Warehouses' mod='cdiscount'}</label>

                <div class="margin-form col-lg-9">
                    <select name="warehouse" style="width:500px;">
                        <option value="">{l s='Choose' mod='cdiscount'}</option>
                        {foreach from=$cd_settings.warehouse_options item=warehouse_option}
                            <option value="{$warehouse_option.value|escape:'htmlall':'UTF-8'}" {$warehouse_option.selected|escape:'htmlall':'UTF-8'}>{$warehouse_option.desc|escape:'htmlall':'UTF-8'}</option>
                        {/foreach}
                    </select>

                    <p>{l s='Choose a warehouse for CDiscount products pickup (for Advanced Stock Management)' mod='cdiscount'}</p>
                </div>
            </div>
            <hr style="width:50%"/>
        {/if}
    {/if}


    {if (isset($cd_settings.ps_version_gt_141) && $cd_settings.expert_mode_checked)}
        <div class="form-group">
            <label class="control-label col-lg-3">{l  s='Image Type' mod='cdiscount'}<span class="expert">{l s='Expert' mod='cdiscount'}</span></label>

            <div class="margin-form col-lg-9">
                <select name="image_type" id="image_type" style="width:200px;"/>
                <option disabled>{l s='Choose' mod='cdiscount'}</option>
                <option></option>
                {foreach from=$cd_settings.image_types item=image_type}
                    <option value="{$image_type.value|escape:'htmlall':'UTF-8'}" {$image_type.selected|escape:'htmlall':'UTF-8'}>{$image_type.desc|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
                </select>
                <p>{l s='Kind of image which will be use for CDiscount (Please refer to Preference > Images for more informations)' mod='cdiscount'}</p>
            </div>
        </div>
        <hr style="width:50%"/>
    {/if}

    {if (isset($cd_settings.ps_version_gt_15_or_equal) && $cd_settings.expert_mode_checked)}
        <label class="control-label col-lg-3">{l s='Logs by Email' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="email_logs" id="email_logs_1" value="1"
                                       {if strlen($cd_settings.email_logs_checked)}checked{/if} /><label
                                        for="email_logs_1" class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                                <input type="radio" name="email_logs" id="email_logs_0" value="0"
                                       {if !strlen($cd_settings.email_logs_checked)}checked{/if}  /><label
                                        for="email_logs_0" class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                                <a class="slide-button btn"></a>
                            </span>
            <br>

            <p>{l s='Check this box if you want to reveive errors notifications by email' mod='cdiscount'}</p>
        </div>
    {/if}


    {if isset($cd_settings.field_condition)}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Products Condition' mod='cdiscount'}</label>

            <div class="margin-form condition-map col-lg-9">
                {foreach from=$cd_settings.product_conditions item=product_cond}
                    <div class="col-lg-12" style="margin-bottom: 5px;">
                        <input type="text" style="width:200px;"
                               value="{$product_cond.key_condition|escape:'quotes':'UTF-8'}" readonly>
                                        <span>&nbsp;&nbsp;
                                            <img src="{$images_url|escape:'htmlall':'UTF-8'}next.png" alt=""
                                                 class="condition-next"/>&nbsp;&nbsp;
                                        </span>
                        <select name="condition_map[{$product_cond.key|intval}]"
                                id="condition_map-{$product_cond.idx|intval}"
                                style="width:200px" {$product_cond.disabled|escape:'htmlall':'UTF-8'}>
                            <option value=""></option>
                            {foreach from=$product_cond.conditions_list item=condition}
                                <option value="{$condition.value|escape:'quotes':'UTF-8'}" {$condition.selected|escape:'html':'UTF-8'}>{$condition.desc|escape:'quotes':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </div>
                {/foreach}
                <p>
                    {l s='CDiscount condition side / Prestashop condition side, please associate the expected parameters' mod='cdiscount'}
                    <br/>
                    {l s='You can use only unique condition on the right column (Prestashop side)' mod='cdiscount'}
                </p>
            </div>
        </div>

                {else}

        <input type="hidden" name="condition_map[6]" value="new"/>
    {/if}

    <hr style="width:50%"/>

    <div class="form-group">
        <label class="control-label col-lg-3"><span>{l s='Employee' mod='cdiscount'}</span></label>

        <div class="margin-form col-lg-9">
            <select name="employee" style="width:500px;">
                <option value="" disabled="disabled">{l s='Choose' mod='cdiscount'}</option>
                <option></option>
                {foreach from=$cd_settings.employee key=id_employee item=employee}
                    <option value="{$id_employee|intval}"
                            {if $employee.selected}selected{/if}>{$employee.name|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>
            <p>
                {l s='This employee will be used to execute automated tasks' mod='cdiscount'}
            </p>
        </div>
    </div>


    <hr style="width:50%"/>


    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Default Comment' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
            <textarea name="comments" id="comments" rows="2"
                      cols="100"/>{$cd_settings.comments|escape:'htmlall':'UTF-8'}</textarea>
            <p>{l s='Default comment text for CDiscount\'s product sheets. It could be override by the comment on your Shop\'s product sheet' mod='cdiscount'}
                <br/>
                <span id="c-count">200</span>&nbsp;{l s='characters left...' mod='cdiscount'}</p>
        </div>
    </div>

    {if $cd_settings.expert_mode_checked}
    <hr style="width:50%"/>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Customer Email' mod='cdiscount'}<span class="expert">{l s='Expert' mod='cdiscount'}</span></label>

        <div class="margin-form col-lg-9">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="individual" id="individual_1" value="1" {if $cd_settings.expert_mode_checked}class="expert-mode"{/if}
                                       {if strlen($cd_settings.individual)}checked{/if} /><label for="individual_1"
                                                                                                 class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
                                <input type="radio" name="individual" id="individual_0" value="0" {if $cd_settings.expert_mode_checked}class="expert-mode"{/if}
                                       {if !strlen($cd_settings.individual)}checked{/if}  /><label for="individual_0"
                                                                                                   class="label-checkbox">{l s='No' mod='cdiscount'}</label>
                                <a class="slide-button btn"></a>
                            </span>
            <br/>

            <p>{l s='Use individual customer account instead of global' mod='cdiscount'}</p>
        </div>
        <div class="margin-form col-lg-offset-3" id="set-domain" style="{$cd_settings.style|escape:'htmlall':'UTF-8'}">
            <input type="text" name="domain" id="domain" value="{$cd_settings.domain|escape:'htmlall':'UTF-8'}"
                   style="width:300px;"/>

            <p>{l s='Please choose a domain name for the customer\'s email addresses if the module can\'t retrieve the encrypted email address from CDiscount' mod='cdiscount'}</p>
        </div>
    </div>
    {else}
        <input type="hidden" name="individual" value="1" />
    {/if}

    {if $cd_settings.expert_mode_checked}
    <hr style="width:50%"/>

    <div class="form-group">
        <label class="control-label col-lg-3" rel="customer_group"><span>{l s='Customer Group' mod='cdiscount'}</span><span class="expert">{l s='Expert' mod='cdiscount'}</span></label>

        <div class="margin-form col-lg-9">
            <select name="id_group" style="width:500px;">
                <option value="" disabled="disabled">{l s='Choose' mod='cdiscount'}</option>
                {foreach from=$cd_settings.customer_groups key=id_customer_group item=customer_group}
                    <option value="{$id_customer_group|intval}"
                            {if $customer_group.selected}selected{/if}>{$customer_group.name|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>
        </div>
    </div>
    {else}
        <input type="hidden" name="id_group" value="{$cd_settings.id_default_customer_group|intval}" />
    {/if}

    {include file="$module_path/views/templates/admin/configure/validate.tpl"}
    </div>
    <!-- settings -->
{/if}
<!--SETTINGS END -->

<!-- FILTERS START -->
<div id="conf-filters" class="tabItem">
    <h2>{l s='Filters' mod='cdiscount'}</h2>

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>

					<div class="margin-form col-lg-9">
						<div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
							{l s='Please follow our online tutorial' mod='cdiscount'} :<br>
							<a href="http://documentation.common-services.com/cdiscount/filtres/?lang={$support_language|escape:'htmlall':'UTF-8'}"
							   target="_blank">http://documentation.common-services.com/cdiscount/filtres/</a><br>
						</div>
					</div>
				</div>
     <div class="form-group">
        <label class="control-label col-lg-3" style="color:grey">{l s='Price Filters' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
            &nbsp;
        </div>
        <div class="cleaner"></div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Greater than' mod='cdiscount'}<span style="font-weight:bold"> > </span></label>

        <div class="margin-form col-lg-9">
            <input type="text" name="price_filter[gt]" class="price-filter-value" style="width:100px; display: inline;"
                   value="{$cd_filters.prices.gt|escape:'htmlall':'UTF-8'}" class="is-price"/>&nbsp;<span
                    style="font-size:1.2em;color:navy;"> {$cd_filters.prices.currency_sign|escape:'htmlall':'UTF-8'} </span>

            <p>{l s='Exclude products where price is greater than this value' mod='cdiscount'}</p>
        </div>
        <div class="cleaner"></div>
        <hr style="width:30%"/>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Less than' mod='cdiscount'}<span
                    style="font-weight:bold"> < </span></label>

        <div class="margin-form col-lg-9">
            <input type="text" name="price_filter[lt]" class="price-filter-value" style="width:100px; display: inline;"
                   value="{$cd_filters.prices.lt|escape:'htmlall':'UTF-8'}" class="is-price" />&nbsp;<span
                    style="font-size:1.2em;color:navy;"> {$cd_filters.prices.currency_sign|escape:'htmlall':'UTF-8'} </span>

            <p>{l s='Exclude products where price is less than this value' mod='cdiscount'}</p>
        </div>
        <div class="cleaner"></div>
        <hr style="width:30%"/>
    </div>

     <div class="form-group">
        <label class="control-label col-lg-3" style="color:grey">{l s='Stock Filter' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
            &nbsp;
        </div>
        <div class="cleaner"></div>
    </div>

       <div class="form-group">
        <label class="control-label col-lg-3">{l s='Stock minimum' mod='cdiscount'}<span class="expert">{l s='Expert' mod='cdiscount'}</span></label>

        <div class="margin-form col-lg-9">

            <select name="stock_mininum" style="width:100px;" {if !$cd_expert_mode}disabled{/if}>
                <option value=""></option>
                                {section name=qty_stock loop=100}
                                    <option value="{$smarty.section.qty_stock.iteration|escape:'htmlall':'UTF-8'}"
                                            {if $cd_filters.stock == $smarty.section.qty_stock.iteration}selected{/if}>{$smarty.section.qty_stock.iteration|escape:'htmlall':'UTF-8'}</option>
                                {/section}

                                </select>


            <p>{l s='Stock breakeven and stock minimum to export the product' mod='cdiscount'}</p>
        </div>
        <div class="cleaner"></div>
        <hr style="width:30%"/>
    </div>






    <div class="form-group">

        <label class="control-label col-lg-3" style="color:grey">{l s='Manufacturers Filters' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
            <div class="manufacturer-heading">
                <span><img src="{$images_url|escape:'htmlall':'UTF-8'}cross.png" alt="{l s='Excluded' mod='cdiscount'}"/></span>
                <span><img src="{$images_url|escape:'htmlall':'UTF-8'}checked.png" alt="{l s='Included' mod='cdiscount'}"/></span>
            </div>
            <select name="excluded-manufacturers[]" class="excluded-manufacturers" id="excluded-manufacturers"
                    multiple="multiple">
                <option value="0" disabled style="color:orange;">{l s='Excluded Manufacturers' mod='cdiscount'}</option>
                {foreach from=$cd_filters.manufacturers.filtered key=id_manufacturer item=name}
                    <option value="{$id_manufacturer|intval}">{$name|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>

            <div class="sep">
                <img src="{$images_url|escape:'htmlall':'UTF-8'}prev.png" class="move" id="manufacturer-move-left"
                     alt="Left"/><br/><br/>
                <img src="{$images_url|escape:'htmlall':'UTF-8'}next.png" class="move" id="manufacturer-move-right"
                     alt="Right"/>
            </div>
            <select name="available-manufacturers[]" class="available-manufacturers" id="available-manufacturers"
                    multiple="multiple">

                <option value="0" disabled style="color:green;">{l s='Included Manufacturers' mod='cdiscount'}</option>
                {foreach from=$cd_filters.manufacturers.available key=id_manufacturer item=name}
                    <option value="{$id_manufacturer|intval}">{$name|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>
        </div>
    </div>

    <div class="cleaner"></div>
    <hr style="width:30%"/>

    <div class="form-group">
        <label class="control-label col-lg-3" style="color:grey">{l s='Suppliers Filters' mod='cdiscount'}</label>

        <div class="margin-form col-lg-9">
            <div class="supplier-heading">
                <span><img src="{$images_url|escape:'htmlall':'UTF-8'}cross.png"
                           alt="{l s='Excluded' mod='cdiscount'}"/></span>
                <span><img src="{$images_url|escape:'htmlall':'UTF-8'}checked.png"
                           alt="{l s='Included' mod='cdiscount'}"/></span>
            </div>
            <select name="selected-suppliers[]" class="selected-suppliers" id="selected-suppliers" multiple="multiple">
                <option value="0" disabled style="color:orange;">{l s='Excluded Suppliers' mod='cdiscount'}</option>
                {foreach from=$cd_filters.suppliers.filtered key=id_supplier item=name}
                    <option value="{$id_supplier|intval}">{$name|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>

            <div class="sep">
                <img src="{$images_url|escape:'htmlall':'UTF-8'}prev.png" class="move" id="supplier-move-left"
                     alt="Left"/><br/><br/>
                <img src="{$images_url|escape:'htmlall':'UTF-8'}next.png" class="move" id="supplier-move-right"
                     alt="Right"/>
            </div>
            <select name="available-suppliers[]" class="available-suppliers" id="available-suppliers"
                    multiple="multiple">
                <option value="0" disabled style="color:green;">{l s='Included Suppliers' mod='cdiscount'}</option>
                {foreach from=$cd_filters.suppliers.available key=id_supplier item=name}
                    <option value="{$id_supplier|intval}">{$name|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>
        </div>
    </div>
    <div class="cleaner"></div>

    {include file="$module_path/views/templates/admin/configure/validate.tpl"}
</div>
<!-- FILTERS END -->


			<!-- MULTITENANT START -->
			{if isset($cd_multitenant) && $cd_multitenant && $cd_multitenant.display}
				<div id="conf-multitenant" class="tabItem">
					<h2>{l s='Multitenant' mod='cdiscount'}</h2>

        <div class="form-group">
            <label class="control-label col-lg-3">&nbsp;</label>

            <div class="margin-form col-lg-9">
                <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                    {l s='Please follow our online tutorial' mod='cdiscount'} :<br>
                    <a href="http://documentation.common-services.com/cdiscount/multitenant/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                       target="_blank">http://documentation.common-services.com/cdiscount/multitenant/</a><br>
                </div>
            </div>
        </div>

        <hr style="width:50%"/>

        {if ($cd_multitenant.status == 'auth')}
            <div class="form-group">
                <label class="control-label col-lg-3">&nbsp;</label>

                <div align="left" class="margin-form col-lg-9 cd-info">
                    <p class="{$alert_class.warning|escape:'htmlall':'UTF-8'}">
                        <span>{l s='You must enter and test your keypairs to display this tab. Please read instructions on "Authentification" tab.' mod='cdiscount'}
                            <br></span>
                    </p>
                </div>
            </div>

                        {elseif ($cd_multitenant.status == 'failed')}

            <div class="form-group">
                <label class="control-label col-lg-3">&nbsp;</label>

                <div align="left" class="margin-form col-lg-9 cd-info">
                    <p class="{$alert_class.danger|escape:'htmlall':'UTF-8'}">
                        <span>{l s='Sorry, failed to retrieve Seller Informations' mod='cdiscount'}<br></span>
                    </p>
                </div>
            </div>

                        {elseif ($cd_multitenant.offerpool)}

            <div class="form-group">

                <label class="control-label col-lg-3">&nbsp;</label>

                <div align="left" class="margin-form col-lg-9 cd-info">

                    <table class="multitenant-list">
                        {foreach from=$cd_multitenant.offerpool item=offerpool}
                            <tr>
                                <td>
                                    <input type="checkbox" id="checkbox-mtid-{$offerpool.Id|intval}" name="multitenant[{$offerpool.Id|escape:'htmlall':'UTF-8'}]" value="1" {if (isset($offerpool.Checked))}checked{/if} class="regular-checkbox big-checkbox">
                                    <label for="checkbox-mtid-{$offerpool.Id|escape:'htmlall':'UTF-8'}"></label>
                                </td>
                                <td>
                                    <h4>{$offerpool.Description|escape:'htmlall':'UTF-8'}</h4>
                                </td>
                                <td>
                                    {if (isset($offerpool.Image))}
                                        <img src="{$offerpool.Image|escape:'htmlall':'UTF-8'}"
                                             alt="{$offerpool.Description|escape:'htmlall':'UTF-8'}"/>
                                    {/if}
                                </td>
                            </tr>

                                                            {if (! isset($offerpool.Last))}
                            <tr>
                                    <td colspan="3">
                                        <hr/>
                                    </td>
                                </tr>
                        {/if}
                        {/foreach}
                    </table>
                </div>

            </div>
        {/if}
        <div class="cleaner"></div>

					{include file="$module_path/views/templates/admin/configure/validate.tpl"}
				</div>
			{/if}
			<!-- MULTITENANT END -->

			<!-- MAIL START -->
			{if isset($cd_messaging) && $cd_messaging}
				<div id="conf-messaging" class="tabItem">
					<h2>{l s='Message' mod='cdiscount'}</h2>

					<div class="form-group">
						<label class="control-label col-lg-3">{l s='Send invoice by email' mod='cdiscount'}</label>
						<div class="margin-form col-lg-9">&nbsp;</div>
					</div>
					<div class="form-group">
						<label class="control-label col-lg-3">{l s='Active' mod='cdiscount'}</label>
						<div class="margin-form col-lg-9">
							<span class="switch prestashop-switch fixed-width-lg">
								<input type="radio" name="mail_invoice[active]" id="mail_invoice" value="1"
									   {if ($cd_messaging.mail_invoice.active)}checked{/if} /><label for="mail_invoice"
									   class="label-checkbox">{l s='Yes' mod='cdiscount'}</label>
								<input type="radio" name="mail_invoice[active]" id="mail_invoice-2" value="0"
									   {if !($cd_messaging.mail_invoice.active)}checked{/if} /><label for="mail_invoice-2"
									   class="label-checkbox">{l s='No' mod='cdiscount'}</label>
								<a class="slide-button btn"></a>
							</span>
							<p>{l s='Activate invoice per email automation. The module will send the email to the customers with the invoice as an attached document (PDF)' mod='cdiscount'}</p>
						</div>
					</div>

					<div id="mail_invoice_activated" style="{if !$cd_messaging.mail_invoice.active}display:none{/if}">
						<div class="form-group">
							<label class="control-label col-lg-3">{l s='Orders Statuses' mod='cdiscount'}</label>
							<div class="margin-form col-lg-9">
								<select name="mail_invoice[order_state]" style="width:300px;">
									<option value="0">{l s='Choose the order status' mod='cdiscount'}</option>
									{foreach from=$cd_messaging.order_states item=order_state}
										<option value="{$order_state.value|escape:'htmlall':'UTF-8'}"
												{if ($cd_messaging.mail_invoice.order_state == $order_state.value)}selected="selected"{/if}>{$order_state.name|escape:'htmlall':'UTF-8'}</option>
									{/foreach}
								</select>
								<p>
									<span>{l s='Choose the order status which will trigger the invoice sending' mod='cdiscount'}</span><br/>
									<span>{l s='Only the eligible states are shown (email set to off, invoice to on)' mod='cdiscount'}</span>
								</p>
							</div>
						</div>

						<div class="form-group">
							<label class="control-label col-lg-3">{l s='Choose Mail Template' mod='cdiscount'}</label>
							<div align="left" class="margin-form col-lg-9">
								<select name="mail_invoice[template]" style="width:210px">
									<option value="0">{l s='Please Choose in the List' mod='cdiscount'}</option>
									{foreach from=$cd_messaging.mail_templates item=mail_template}
										<option value="{$mail_template|escape:'htmlall':'UTF-8'}"
												{if ($cd_messaging.mail_invoice.template == $mail_template)}selected="selected"{/if}>{$mail_template|escape:'htmlall':'UTF-8'}</option>
									{/foreach}
								</select>
								<p>
									<span>{l s='File template which will be used to announce the invoice.' mod='cdiscount'}</span><br/>
									<span>{l s='You can add or edit files from the modules/cdiscount/mail directory' mod='cdiscount'}</span>
								</p>
							</div>
						</div>

						{if ($cd_messaging.is_ps15)}
							<div class="form-group">
								<label class="control-label col-lg-3">{l s='Additionnal File' mod='cdiscount'}</label>
								<div align="left" class="margin-form col-lg-9">
									<select name="mail_invoice[additionnal]" style="width:210px">
										<option value="0">{l s='Please Choose in the List' mod='cdiscount'}</option>
										{foreach from=$cd_messaging.mail_add_files item=mail_add_file}
											<option value="{$mail_add_file|escape:'htmlall':'UTF-8'}"
													{if ($cd_messaging.mail_invoice.additionnal == $mail_add_file)}selected="selected"{/if}>{$mail_add_file|escape:'htmlall':'UTF-8'}</option>
										{/foreach}
									</select>

									<p>{l s='Additionnal PDF file to send as attachment, which could be for example: Terms & Conditions.' mod='cdiscount'}
										{if !$cd_messaging.mail_add_files}
											<br/>
											<span style="color:navy">{l s='Your list is currently empty. You can put your extra PDF file in the modules/cdiscount/pdf directory.' mod='cdiscount'}</span>
										{/if}
									</p>
								</div>
							</div>
						{/if}
					</div>

					{include file="$module_path/views/templates/admin/configure/validate.tpl"}
				</div>
			{/if}
			<!-- MAIL END -->

<!--CRON START -->
{if isset($cd_cron) && $cd_cron}
    <div id="conf-cron" class="tabItem">
        <h2>{l s='Cron' mod='cdiscount'}</h2>

        <div class="form-group">
            <label class="control-label col-lg-3">&nbsp;</label>

            <div class="margin-form col-lg-9">
                <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                    {l s='Please follow our online tutorial' mod='cdiscount'} :<br>
                    <a href="http://documentation.common-services.com/cdiscount/taches-planifiees/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                       target="_blank">http://documentation.common-services.com/cdiscount/taches-planifiees/</a><br>
                </div>
            </div>
        </div>

        <br/>



        <div class="form-group">
            <div class="margin-form col-lg-offset-3">
                <div id="cronjobs_success" class="{$alert_class.success|escape:'htmlall':'UTF-8'}" style="display:none">
                </div>

                <div id="cronjobs_error" class="{$alert_class.danger|escape:'htmlall':'UTF-8'}" style="display:none">
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="margin-form col-lg-offset-3">
                <div class="cron-mode" rel="prestashop-cron">
                    <img src="{$images_url|escape:'quotes':'UTF-8'}/prestashop-cronjobs-icon.png" title="{l s='Prestashop Cronjobs (Module)' mod='cdiscount'}" />
                    <h4>{l s='Prestashop Cronjobs (Module)' mod='cdiscount'}</h4>
                    <div style="float:right" class="cron-prestashop">
                        {if $cd_cron.cronjobs.installed}
                            <span style="color:green">{l s='Installed' mod='cdiscount'}</span>
                        {elseif $cd_cron.cronjobs.exists}
                            <span style="color:red">{l s='Detected, Not installed' mod='cdiscount'}</span>
                        {else}
                            <span style="color:red">{l s='Not detected' mod='cdiscount'}</span>
                        {/if}
                    </div>
                </div>

            </div>
        </div>

        <div id="prestashop-cron" class="cron-toggle" {if !$cd_cron.cronjobs.installed}style="display:none"{/if} >
            <div class="form-group">
                <div class="margin-form col-lg-offset-3">

                    {if !$cd_cron.cronjobs.installed}
                        <div class="margin-form col-lg-9">
                            <div class="{$alert_class.warning|escape:'htmlall':'UTF-8'}">{l s='Prestashop Cronjobs is not installed.' mod='cdiscount'}  {if !$cd_cron.cronjobs.exists}(<a href="https://github.com/PrestaShop/cronjobs/archive/master.zip" target="_blank">https://github.com/PrestaShop/cronjobs</a>){/if}</div>
                        </div>
                    {else}
                        <span class="title">{l s='Those lines will be added in Prestashop Cronjobs module' mod='cdiscount'}:</span>

                        <div id="prestashop-cronjobs-lines">
{if $cd_cron.stdtypes}
{foreach from=$cd_cron.stdtypes item=type}<b>{$cd_cron[$type].title|escape:'htmlall':'UTF-8'}</b>: {l s='each' mod='cdiscount'} {$cd_cron[$type].frequency|intval|abs} {if $cd_cron[$type].frequency > 1}{l s='hours' mod='cdiscount'}{else}{l s='hour' mod='cdiscount'}{/if}, {l s='url' mod='cdiscount'}: <a href="{$cd_cron[$type].url|escape:'quotes':'UTF-8'}" target="_blank">{$cd_cron[$type].url_short|escape:'quotes':'UTF-8'}</a>
{/foreach}
{/if}
{if isset($cd_cron.multitenants) && is_array($cd_cron.multitenants)}
{foreach from=$cd_cron.multitenants item=multitenant}<b>{$multitenant.title|escape:'htmlall':'UTF-8'}</b>: {l s='each' mod='cdiscount'} {$multitenant.frequency|intval|abs} {if $multitenant.frequency > 1}{l s='hours' mod='cdiscount'}{else}{l s='hour' mod='cdiscount'}{/if}, {l s='url' mod='cdiscount'}: <a href="{$multitenant.url|escape:'quotes':'UTF-8'}" target="_blank">{$multitenant.url_short|escape:'quotes':'UTF-8'}</a>
{/foreach}
{/if}

                        </div>
<textarea id="prestashop-cronjobs-params" name="prestashop-cronjobs-params" style="display:none">
{if $cd_cron.stdtypes}
{foreach from=$cd_cron.stdtypes item=type}{$cd_cron[$type].title|escape:'htmlall':'UTF-8'}|0|{$cd_cron[$type].frequency|escape:'htmlall':'UTF-8'}|{$cd_cron[$type].url|escape:'quotes':'UTF-8'}!{/foreach}
{/if}
{if isset($cd_cron.multitenants) && is_array($cd_cron.multitenants)}
{foreach from=$cd_cron.multitenants item=multitenant}{$multitenant.title|escape:'htmlall':'UTF-8'}|0|{$multitenant.frequency|escape:'htmlall':'UTF-8'}|{$multitenant.url|escape:'quotes':'UTF-8'}!{/foreach}
{/if}
</textarea>
                        <br />
                        {if $cd_cron.cronjobs.installed}
                            <span style="color:green">{l s='Click on install/update button to auto-configure your Prestashop cronjobs module' mod='cdiscount'}:</span>
                            <button class="button btn btn-default" style="float:right" id="install-cronjobs"><img src="{$images_url|escape:'htmlall':'UTF-8'}plus.png" alt=""/>&nbsp;&nbsp; {l s='Install/Update' mod='cdiscount'}</button>
                            <img src="{$images_url|escape:'htmlall':'UTF-8'}loader-connection.gif" alt="" id="cronjob-loader" />
                        {/if}
                    {/if}
                </div>

            </div>
        </div>

        <div class="form-group">
            <div class="margin-form col-lg-offset-3">
                <div class="cron-mode" rel="manual-cron">
                    <img src="{$images_url|escape:'quotes':'UTF-8'}/terminal.png" title="{l s='Manual Cron URLs' mod='cdiscount'}" />
                    <h4>{l s='Manual Cron URLs' mod='cdiscount'}</h4>
                </div>
            </div>
        </div>

        <div id="manual-cron" class="cron-toggle" {if $cd_cron.cronjobs.installed}style="display:none"{/if}>
            <div class="form-group">
                <label class="control-label col-lg-3"></label>

                <div class="margin-form col-lg-9">
                    <input type="text" style="color:grey;background-color:#fdfdfd;width:100%;"
                           value="{$cd_cron.update.url|escape:'htmlall':'UTF-8'}"/><br/>

                    <p>{l s='URL to synchronize products to be used to configure your crontab.' mod='cdiscount'}</p>
                    <input type="text" style="color:grey;background-color:#fdfdfd;width:100%;margin-top:5px;" value="{$cd_cron.accept.url|escape:'htmlall':'UTF-8'}"/><br/>

                    <p>{l s='URL to accept orders to be used to configure your crontab.' mod='cdiscount'}</p>
                    <input type="text" style="color:grey;background-color:#fdfdfd;width:100%;margin-top:5px;" value="{$cd_cron.import.url|escape:'htmlall':'UTF-8'}"/><br/>

                    <p>{l s='URL to import orders to be used to configure your crontab.' mod='cdiscount'}</p>
                                <span style="color:brown;font-weight:bold;">
                                    {l s='Be carefull ! Importing orders by cron can skips somes orders (eg: out of stock), you must check also manually' mod='cdiscount'}
                                </span><br/>
                    {if $cd_cron.bulk_mode}
                        <input type="text" style="color:grey;background-color:#fdfdfd;width:100%;margin-top:5px;" value="{$cd_cron.status.url|escape:'htmlall':'UTF-8'}"/>
                        <br/>
                        <p>{l s='URL to update orders statuses (ie: shipped w/the tracking number) to be used to configure your crontab.' mod='cdiscount'}</p>
                    {/if}
                </div>
            </div>

            {if isset($cd_cron.multitenants) && is_array($cd_cron.multitenants)}
                <div class="form-group">
                <label class="control-label col-lg-3">{l s='Multitenant URLs' mod='cdiscount'}</label>
                <div class="margin-form col-lg-9">
                {foreach from=$cd_cron.multitenants item=cron_multitenant}
                    <input type="text" style="color:grey;background-color:#fdfdfd;width:100%;" value="{$cron_multitenant.url|escape:'htmlall':'UTF-8'}"/>
                    <br/>
                    <p><b>{$cron_multitenant.Description|escape:'htmlall':'UTF-8'}</b> - {l s='URL to synchronize products to be used to configure your crontab.' mod='cdiscount'}</p>
                {/foreach}
                </div>
            </div>
            {/if}
    </div>
    </div>
{/if}
<!--CRON END -->
<!-- body end -->
</div>
<div id="mapping-box" class="mapping-box">
    <div class="main-box">
        <div>
            <label>{l s='Please select all sizes matching your usage' mod='cdiscount'}</label>
        </div>
        <span class="close-box">[ x ]</span>
        <div class="selectors">
            <div class="mapping-box-values">
                <div class="mapping-box-values-loader"></div>
                <span><img src="{$images_url|escape:'htmlall':'UTF-8'}cross.png" alt="{l s='Available' mod='cdiscount'}"/></span><br />
                <select name="available-values[]" class="available-values" multiple="multiple">
                    <option value="0" disabled style="color:orange;">{l s='Available Values' mod='cdiscount'}</option>
                </select>
                <input type="text" class="values-search" placeholder="{l s='Search a value' mod='cdiscount'}" />
            </div>

            <div class="sep-values">
                <img src="{$images_url|escape:'htmlall':'UTF-8'}prev.png" class="move mapping-box-values-move-left" alt="Left"/><br/><br/>
                <img src="{$images_url|escape:'htmlall':'UTF-8'}next.png" class="move mapping-box-values-move-right" alt="Right"/>
            </div>

            <div class="mapping-box-values">
                <span><img src="{$images_url|escape:'htmlall':'UTF-8'}checked.png" alt="{l s='Selected' mod='cdiscount'}"/></span><br />
                <select name="selected-values[]" class="selected-values"  multiple="multiple">
                    <option value="0" disabled style="color:green;">{l s='Selected Values' mod='cdiscount'}</option>
                </select>
            </div>
        </div>
        <button class="button btn btn-defaul mapping-box-valid" style="float:right;" class="mapping-values-insert">{l s='Use' mod='cdiscount'}</button>
    </div>
</div><!--mapping box-->
</fieldset>
</form>
<!-- ! body end -->
