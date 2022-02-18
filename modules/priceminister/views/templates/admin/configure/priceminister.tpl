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

<form action="{$request_uri}" method="post" id="configuration_form" novalidate="novalidate">
    <input type="hidden" value="{$module_url}" id="pm_module_url"/>
    <fieldset id="tabList" class="form-horizontal">
        <!-- PRICEMINISTER START -->
        <div id="conf-priceminister" class="tabItem panel {$selected_tab_priceminister}"
             {if (empty($selected_tab_priceminister))}style="display:none;"{/if}>
            <div class="form-group">
                <label class="col-lg-3"><h2>{l s='RakutenFrance' mod='priceminister'} v{$version|escape:'none'}</h2>
                </label>
                <div class="margin-form col-lg-9">
                    <p class="descriptionBold">
                        <span style="color: navy;">{$module_description|escape:'htmlall':'UTF-8'}</span><br>
                        {l s='The following features are provided with this module :' mod='priceminister'}
                    </p>
                    <ul class="descriptionList">
                        <li>{l s='Order management' mod='priceminister'}</li>
                        <li>{l s='Products Exports' mod='priceminister'}</li>
                        <li>{l s='Products Synchronization' mod='priceminister'}</li>
                        <li>{l s='Shipping Notification by Email' mod='priceminister'}</li>
                    </ul>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Informations' mod='priceminister'}</label>
                <div class="margin-form col-lg-9" style="margin-top: 6px;">
                    <span style="color:navy">{l s='This module is provided by' mod='priceminister'} :</span>
                    Common-Services<br>
                    <br>
                    <span style="color:navy">{l s='Informations, follow up on our blog' mod='priceminister'}
                        :</span><br>
                    <a href="http://www.common-services.com" target="_blank">http://www.common-services.com</a><br>
                    <br>
                    <span style="color:navy">{l s='More informations about us on Prestashop website' mod='priceminister'}
                        :</span><br>
                    <a href="http://www.prestashop.com/fr/agences-web-partenaires/or/common-services" target="_blank">http://www.prestashop.com/fr/agences-web-partenaires/or/common-services</a><br>
                    <br>
                    <span style="color:navy">{l s='You will appreciate our others modules' mod='priceminister'} :</span><br>
                    <a href="http://addons.prestashop.com/fr/58_common-services" target="_blank">http://addons.prestashop.com/fr/58_common-services</a><br>
                </div>
            </div>
            <br>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Documentation' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <div class="col-lg-1"><img src="{$images_url|escape:'htmlall':'UTF-8'}books.png" alt="docs"/></div>
                    <div class="col-lg-11">
                    <span style="color:red; font-weight:bold;">{l s='Please, first read the provided documentation' mod='priceminister'}
                        :</span><br>
                        <a href="http://documentation.common-services.com/priceminister" target="_blank">http://documentation.common-services.com/priceminister</a>
                    </div>
                </div>
            </div>
            <br>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Support' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <div class="col-lg-1">
                        <img src="{$images_url}submit_support_request.png" alt="support"></div>
                    <div class="col-lg-11">
                        <span style="color:red; font-weight:bold;">
                            {l s='The technical support is available by e-mail only.' mod='priceminister'}
                        </span><br>
                        <span style="color: navy;">
                            {l s='For any support, please provide us' mod='priceminister'} :<br>
                        </span>
                        <ul>
                            <li>{l s='A detailled description of the issue or encountered problem' mod='priceminister'}</li>
                            <li>{l s='Your Pretashop Addons Order ID available in your Prestashop Addons order history' mod='priceminister'}</li>
                            <li>{l s='Your Prestashop version' mod='priceminister'} :
                                <span style="color: red;">Prestashop {$ps_version}</span></li>
                            <li>{l s='Your module version' mod='priceminister'} :
                                <span style="color: red;">RakutenFrance v{$version}</span></li>
                        </ul>
                        <br>
                        <span style="color:navy">{l s='Support Common-Services' mod='priceminister'} :</span>
                        <a href="mailto:support.priceminister@common-services.com?subject={l s='Support for RakutenFrance' mod='priceminister'}&body={l s='Dear Support, I am currently having some trouble with your module v%s on my Prestashop v%s.' sprintf=[$version, $ps_version] mod='priceminister'}"
                           title="Email">
                            support.priceminister@common-services.com
                        </a><br>
                        <br>
                        {l s='Support RakutenFrance' mod='priceminister'}:<br/>
                        &nbsp;&nbsp;- {l s='Support Pro' mod='priceminister'} :
                        <a href="mailto:support.pro@priceminister.com" alt=""/>support.pro@priceminister.com</a><br/>
                        &nbsp;&nbsp;- {l s='Support Commercial' mod='priceminister'} :
                        <a href="mailto:infopro@priceminister.com" alt=""/>infopro@priceminister.com</a>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Licence' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <p>
                        {l s='This module is subject to a commercial license from SARL SMC.' mod='priceminister'}<br/>
                        {l s='To obtain a license, please contact us: contact@common-services.com' mod='priceminister'}
                        <br/>
                        {l s='In case of acquisition on Prestastore, the invoice itself is a proof of license' mod='priceminister'}
                        <br/>
                    </p>
                </div>
            </div>
        </div>
        <!-- PRICEMINISTER END -->

        <!-- INFORMATIONS START -->
        <div id="conf-informations" class="tabItem panel {$selected_tab_informations}"
             {if (empty($selected_tab_informations))}style="display:none;"{/if}>

            <h3>{l s='Configuration Check' mod='priceminister'}</h3>

            <div class="form-group">
                <label class="control-label col-lg-3 dropup" id="detailed_debug_controller" style="cursor: pointer;">
                    {l s='Debug info' mod='priceminister'}
                    <span class="caret"></span>
                </label>
                <div align="left" class="margin-form col-lg-9" id="detailed_debug_content" style="display: none;">
                    {foreach from=$rkt_detailed_debug item=rkt_debug}
                        {$rkt_debug|@print_r}
                    {/foreach}
                </div>
            </div>

            <div class="form-group" id="pm-env-infos" style="display:none">
                <label class="control-label col-lg-3">{l s='Environment' mod='priceminister'}</label>
                <div align="left" class="margin-form col-lg-9 pm-info">
                    {if $informations.env_infos}
                        {foreach from=$informations.env_infos key=env_name item=env_info}
                            {if $env_info.script}
                                <!-- script URL -->
                                <input type="hidden" id="{$env_info.script.name|escape:'htmlall':'UTF-8'}"
                                       value="{$env_info.script.url}" rel="{$env_name}"/>
                            {/if}
                            <p class="{$env_info.level} pm-env-infos-{$env_name}"
                               style="display:none">
                                <span>{$env_info.message}</span>
                                <span id="env-info-details"
                                      style="display: none;"><br><u>Informations</u> :<br><span></span></span>
                            </p>
                            <hr style="width:30%; margin-top:10px;"/>
                        {/foreach}
                    {/if}
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Module Settings' mod='priceminister'}</label>
                <div align="left" class="margin-form col-lg-9 pm-info">
                    {if ! $informations.module_info_ok}
                        {foreach from=$informations.module_infos item=module_info}
                            <p class="{$module_info.level}">
                                <span>{$module_info.message}</span>
                                {if isset($module_info.link)}
                                    <br/>
                                    <span class="pm-info-link">{l s='Please read more about it on:' mod='priceminister'}
                                        : <a href="{$module_info.link}"
                                             target="_blank">{$module_info.link}</a></span>
                                {/if}
                            </p>
                            <hr style="width:30%;margin-top:10px;"/>
                        {/foreach}
                    {else}
                        <p class="{$alert_class.success}">
                            <span>{l s='Your Module configuration has been checked and passed successfully...' mod='priceminister'}</span>
                        </p>
                        <hr style="width:30%;margin-top:10px;"/>
                    {/if}
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='PHP Settings' mod='priceminister'}</label>
                <div align="left" class="margin-form col-lg-9 pm-info">
                    {if ! $informations.php_info_ok}
                        {foreach from=$informations.php_infos item=php_info}
                            <p class="{$php_info.level}">
                                <span>{$php_info.message}</span>
                                {if isset($php_info.link)}
                                    <br/>
                                    <span class="pm-info-link">{l s='Please read more about it on:' mod='priceminister'}
                                        : <a href="{$php_info.link}"
                                             target="_blank">{$php_info.link}</a></span>
                                {/if}
                            </p>
                            <hr style="width:30%;margin-top:10px;"/>
                        {/foreach}
                    {else}
                        <p class="{$alert_class.success}">
                            <span>{l s='Your PHP configuration for the module has been checked and passed successfully...' mod='priceminister'}</span>
                        </p>
                        <hr style="width:30%;margin-top:10px;"/>
                    {/if}
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Prestashop Settings' mod='priceminister'}</label>
                <div align="left" class="margin-form col-lg-9 pm-info">
                    {if ! $informations.prestashop_info_ok}
                        {foreach from=$informations.prestashop_infos item=prestashop_info}
                            <p class="{$prestashop_info.level}">
                                <span>{$prestashop_info.message}</span>
                                {if isset($prestashop_info.link)}
                                    <br/>
                                    <span class="pm-info-link">{l s='Please read more about it on:' mod='priceminister'}
                                        : <a href="{$prestashop_info.link}"
                                             target="_blank">{$prestashop_info.link}</a></span>
                                {/if}
                            </p>
                            <hr style="width:30%;margin-top:10px;"/>
                        {/foreach}
                    {else}
                        <p class="{$alert_class.success}">
                            <span>{l s='Your Prestashop configuration for the module has been checked and passed successfully...' mod='priceminister'}</span>
                        <hr style="width:30%;margin-top:10px;"/>
                        </p>
                    {/if}
                </div>
            </div>


            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Additionnal Support Informations' mod='priceminister'}</label>
                <div align="left" class="margin-form col-lg-9">
                    <input type="button" class="button btn btn btn-default" id="support-informations-prestashop"
                           value="{l s='Prestashop Info' mod='priceminister'}"
                           rel="{$informations.support_informations_url}&action=prestashop-info"/>&nbsp;&nbsp;
                    <input type="button" class="button btn btn btn-default" id="support-informations-php"
                           value="{l s='PHP Info' mod='priceminister'}"
                           rel="{$informations.support_informations_url}&action=php-info"/>&nbsp;&nbsp;
                    <img src="{$informations.images}loader-connection.gif"
                         alt="{l s='Support Informations' mod='priceminister'}"
                         class="support-informations-loader"/><br/><br/>
                    <div id="support-informations-content">

                    </div>
                </div>
            </div>

        </div>
        <!-- INFORMATIONS END -->

        <!--CREDENTIALS START-->
        {if (isset($credentials) && is_array($credentials))}
            <div id="conf-credentials" class="tabItem panel conf-config {$selected_tab_credentials}"
                 {if (empty($selected_tab_credentials))}style="display:none;"{/if}>
                <h3>
                    {l s='Account Settings' mod='priceminister'}
                </h3>
                <input type="hidden" id="credentials-error-login"
                       value="{l s='You must enter a login name' mod='priceminister'}"/>
                <input type="hidden" id="credentials-error-token"
                       value="{l s='You must enter a token' mod='priceminister'}"/>

                <div class="form-group">
                    <label class="control-label col-lg-3">&nbsp;</label>

                    <div class="margin-form col-lg-9">
                        <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                            {l s='Please follow our online tutorial' mod='priceminister'} :<br>
                            <a href="http://documentation.common-services.com/priceminister/configurer-les-parametres-de-connexion/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                               target="_blank">http://documentation.common-services.com/priceminister/configurer-les-parametres-de-connexion/</a><br>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Login' mod='priceminister'}</label>
                    <div class="margin-form col-lg-9">
                        <input type="text" id="pm_login" class="login" name="pm_credentials[login]" style="width:300px;"
                               value="{$credentials.login}"/>
                        <span class="pm-required">*</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Token' mod='priceminister'}</label>
                    <div class="margin-form col-lg-9">
                        <input type="password" id="pm_token" class="login" name="pm_credentials[token]"
                               style="width:300px;" value="{$credentials.token}" maxlength="32"/>
                        <span class="pm-required">*</span>
                        <p style="font-size:1.2em;margin-top:5px;">{l s='Obtaining your keypairs' mod='priceminister'} :<br/>
                            <a href="https://www.priceminister.com/usersecure?action=usrwstokenaccess" target="_blank"
                               alt="">https://www.priceminister.com/usersecure?action=usrwstokenaccess</a>
                        </p>
                    </div>
                </div>

                <hr style="width:30%;margin-bottom:25px;"/>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Connexion' mod='priceminister'}</label>
                    <div class="margin-form col-lg-9">
                        <input type="hidden" id="testurl" value="{$credentials.check_url}"/>
                        <div id="pmresponse" class="connectivity-result">&nbsp;</div>
                        <input type="button" class="button btn btn-default connectivity-button" id="test"
                               value="{l s='Check Now' mod='priceminister'}"/>&nbsp;&nbsp;
                        <img src="{$images_url}loader-connection.gif" alt="" id="check-loader"
                             class="connectivity-check"/><br/>
                        <p style="font-size:1.2em;margin-top:5px;">{l s='Establish and test a connexion with the Rakuten France Web Service.' mod='priceminister'}</p>
                    </div>
                </div>

                <hr style="width:30%;margin-bottom:25px;"/>

                {if ($credentials.locahost)}
                    <div class="form-group">
                        <label class="control-label col-lg-3">{l s='Test Mode' mod='priceminister'}</label>

                        <div class="margin-form col-lg-9">
                                    <span class="switch prestashop-switch fixed-width-lg">
                                        <input type="radio" name="pm_credentials[test]" id="test_0" value="0"
                                               {if ! $credentials.test}checked{/if} /><label for="test_0"
                                                                                             class="label-checkbox">{l s='No' mod='priceminister'}</label>
                                        <input type="radio" name="pm_credentials[test]" id="test_1" value="1"
                                               {if $credentials.test}checked{/if} /><label for="test_1"
                                                                                           class="label-checkbox">{l s='Yes' mod='priceminister'}</label>
                                        <a class="slide-button btn"></a>
                                    </span>
                            <br/>
                            <p>{l s='Test mode. This is a demonstration or developpment mode, API calls are fakes.' mod='priceminister'}
                                <br/>{l s='Use for developpment purpose only or for tests and validate the module under this environment' mod='priceminister'}
                                <br/><b {$credentials.test_style}>{l s='In exploitation this option must not be active !' mod='priceminister'} </b>
                            </p>
                        </div>
                    </div>
                {else}
                    <input type="hidden" name="pm_credentials[test]" value="0"/>
                {/if}

                <div class="form-group">

                    <label class="control-label col-lg-3">{l s='Debug Mode' mod='priceminister'}</label>
                    <div class="margin-form col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="debug" id="debug_1" value="1" {if $credentials.debug}checked{/if} />
                            <label for="debug_1" class="label-checkbox">{l s='Yes' mod='priceminister'}</label>
                            <input type="radio" name="debug" id="debug_0" value="0" {if !$credentials.debug}checked{/if} />
                            <label for="debug_0" class="label-checkbox">{l s='No' mod='priceminister'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                        <br />
                        <p>{l s='Debug mode. Enable traces for debugging and developpment purpose.' mod='priceminister'}
                            <br /><b {$credentials.debug_style}>{l s='In exploitation this option must not be active !' mod='priceminister'} </b>
                        </p>
                    </div>

                </div>

                <hr style="width:30%;"/>

                {include file="$module_path/views/templates/admin/configure/validate.tpl"}
            </div>
        {/if}
        <!--CREDENTIALS END-->

        <!--PROFILES START-->
        <div id="conf-profiles" class="tabItem panel {$selected_tab_profiles}"
             {if (empty($selected_tab_profiles))}style="display:none;"{/if}>
            <h3>
                {l s='Profiles' mod='priceminister'}
            </h3>

            <div class="form-group">
                <label class="control-label col-lg-3">&nbsp;</label>

                <div class="margin-form col-lg-9">
                    <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                        {l s='Please follow our online tutorial' mod='priceminister'} :<br>
                        <a href="http://documentation.common-services.com/priceminister/configurer-un-profil/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                           target="_blank">http://documentation.common-services.com/priceminister/configurer-un-profil/</a><br>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3"></label>
                <div class="margin-form col-lg-9">
                    <button class="btn btn-default" style="float:right" id="profile-add">
                        <img src="{$images_url}plus.png" class="new-profile-img"
                             alt=""/>&nbsp;&nbsp; {l s='Add a new profile' mod='priceminister'}
                    </button>
                </div>
            </div>

            {*<hr style="width: 30%;"/>*}

            <div class="form-group">
                <label class="control-label col-lg-3">&nbsp;</label>
                <div class="margin-form col-lg-9">
                    <h2 class="pm-profile-title">{l s='New Profile' mod='priceminister'}</h2>
                </div>
            </div>

            <input type="hidden" id="profile-error-profile-name"
                   value="{l s='You must enter a profile name' mod='priceminister'}"/>
            <input type="hidden" id="profile-error-associated-model"
                   value="{l s='You must select an associated model' mod='priceminister'}"/>
            <input type="hidden" id="profile-error-from-value"
                   value="{l s='You must select a \'from\' value' mod='priceminister'}"/>
            <input type="hidden" id="profile-error-to-value"
                   value="{l s='You must select a \'to\' value' mod='priceminister'}"/>
            <input type="hidden" id="profile-error-percent-value"
                   value="{l s='You must select a \'percent\' value' mod='priceminister'}"/>
            <input type="hidden" id="profile-error-amount-value"
                   value="{l s='You must select an \'amount\' value' mod='priceminister'}"/>

            {assign var=profile value=$profiles.profile_empty}

            <div class="form-group" id="pm-master-profile" style="display:none">
                {include file="$module_path/views/templates/admin/configure/profile.tpl" profile=$profile}
            </div>


            <div class="form-group pm-profile-group" id="pm-profile-container">
                {foreach from=$profiles.profiles_data item=profile}
                    {include file="$module_path/views/templates/admin/configure/profile.tpl" profile=$profile}
                {/foreach}
            </div>

            <span class="pm-required">*</span>:&nbsp;{l s='Required' mod='priceminister'}

            {include file="$module_path/views/templates/admin/configure/validate.tpl"}
        </div>
        <!--PROFILES END-->

        <!--MODELS START-->
        <div id="conf-models" class="tabItem panel {$selected_tab_models}"
             {if (empty($selected_tab_models))}style="display:none;"{/if}>
            <h3>
                {l s='Models' mod='priceminister'}
            </h3>

            <div class="form-group">
                <label class="control-label col-lg-3">&nbsp;</label>

                <div class="margin-form col-lg-9">
                    <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                        {l s='Please follow our online tutorial' mod='priceminister'} :<br>
                        <a href="http://documentation.common-services.com/priceminister/configurer-un-modele/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                           target="_blank">http://documentation.common-services.com/priceminister/configurer-un-modele/</a><br>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3"></label>
                <div class="margin-form col-lg-9">
                    <button class="btn btn-default" style="float:right" id="model-add">
                        <img src="{$images_url}plus.png" class="new-model-img"
                             alt=""/>&nbsp;&nbsp; {l s='Add a new model' mod='priceminister'}
                    </button>
                </div>
            </div>

            {*<hr style="width: 30%;"/>*}

            <div class="form-group">
                <label class="control-label col-lg-3">&nbsp;</label>
                <div class="margin-form col-lg-9">
                    <h2 class="pm-model-title">{l s='New Model' mod='priceminister'}</h2>
                </div>
            </div>


            <input type="hidden" id="model-error-product-type"
                   value="{l s='You must enter a product type' mod='priceminister'}"/>
            <input type="hidden" id="model-error-model-name"
                   value="{l s='You must enter a model name' mod='priceminister'}"/>
            <input type="hidden" id="model-error-platform-opt"
                   value="{l s='You must enter a platform' mod='priceminister'}"/>
            <input type="hidden" id="model-error-platform-attr"
                   value="{l s='You must enter a platform attribute' mod='priceminister'}"/>
            <input type="hidden" id="model-error-typedeproduit"
                   value="{l s='You must enter a type de produit' mod='priceminister'}"/>
            <input type="hidden" id="model-error-select" value="{l s='You must select' mod='priceminister'}"/>
            <input type="hidden" id="model-error-attribute" value="{l s='attribute' mod='priceminister'}"/>
            <input type="hidden" id="model-error-attribute-value" value="{l s='attribute value' mod='priceminister'}"/>
            <div class="form-group" id="pm-master-model" style="display:none">
                {include file="$module_path/views/templates/admin/configure/model.tpl" model=$models.model_default}
            </div>

            <div class="form-group pm-model-group" id="pm-model-container">
                {foreach from=$models.pm_models key=model_id item=model}
                    {include file="$module_path/views/templates/admin/configure/model.tpl"}
                {/foreach}
            </div>

            <span class="pm-required">*</span>:&nbsp;{l s='Required' mod='priceminister'}

            {include file="$module_path/views/templates/admin/configure/validate.tpl"}
        </div>
        <!--MODELS END-->

        <!--CATEGORIES START -->
        <div id="conf-categories" class="tabItem panel {$selected_tab_categories}"
             {if (empty($selected_tab_categories))}style="display:none;"{/if}>
            <h3>
                {l s='Categories' mod='priceminister'}
            </h3>
            <input type="hidden" id="categories-error-profile"
                   value="{l s='You must select a profile' mod='priceminister'}"/>
            <input type="hidden" id="categories-error-selectone"
                   value="{l s='You must select at least one category with a profile' mod='priceminister'}"/>
            <div class="form-group">
                <label class="control-label col-lg-3">&nbsp;</label>

                <div class="margin-form col-lg-9">
                    <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                        {l s='Please follow our online tutorial' mod='priceminister'} :<br>
                        <a href="http://documentation.common-services.com/priceminister/selection-de-categories/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                           target="_blank">http://documentation.common-services.com/priceminister/selection-de-categories/</a><br>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Categories' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <table cellspacing="0" cellpadding="0" class="table">
                        <tr class="active">
                            <th>ID</th>
                            <th>{l s='Name' mod='priceminister'}</th>
                            <th style="width: 235px">{l s='Profile' mod='priceminister'}</th>
                        </tr>
                        {* Ajout debuss-a *}
                        {if isset($categories) && is_array($categories.list) && count($categories.list)}
                            {foreach $categories.list as $id_category => $details}
                                <tr class="cat-line{($details.alt_row|intval) ? ' alt_row' : ''}">
                                    <td>
                                        {if !$details.disabled}
                                            <input type="checkbox" name="category[]"
                                                   class="category{($details.id_category_default|intval == $id_category|intval) ? ' id_category_default' : ''}"
                                                   id="category_{$id_category|intval}"
                                                   value="{$id_category|intval}" {$details.checked|escape:'htmlall':'UTF-8'}/>
                                        {/if}
                                    </td>
                                    <td style="cursor:pointer">
                                        <img src="{$details.img_level|escape:'htmlall':'UTF-8'}" alt=""/> &nbsp;<label
                                                for="category_{$id_category|intval}"
                                                class="t">{$details.name|escape:'htmlall':'UTF-8'}</label>
                                    </td>
                                    <td>
                                        {if !$details.disabled}
                                            <select rel="profile2category[{$id_category|intval}]"
                                                    style="width:180px;margin-right:10px;"
                                                    name="profile2category[{$id_category|intval}]">
                                                <option value="">{l s='Please choose a profile' mod='priceminister'}</option>
                                                {foreach $categories.profiles as $profile}
                                                    <option value="{$profile.name|escape:'htmlall':'UTF-8'}"
                                                            {if $profile.name == $details.profile}selected="selected"{/if}>{$profile.name|escape:'htmlall':'UTF-8'}</option>
                                                {/foreach}
                                            </select>
                                            &nbsp;
                                            <span class="arrow-cat-duplicate"></span>
                                        {/if}
                                    </td>
                                </tr>
                            {/foreach}
                        {else}
                            <tr>
                                <td colspan="3">
                                    {l s='No category were found.' mod='priceminister'}
                                </td>
                            </tr>
                        {/if}
                        {* !Ajout debuss-a *}
                    </table>
                </div>
            </div>
            <input type="text" id="categories-select-validate" name="categories-select-validate" value=""
                   style="visibility:hidden;"/>
            {include file="$module_path/views/templates/admin/configure/validate.tpl"}
        </div>
        <!--CATEGORIES END-->

        <!--PARAMETERS START -->
        <div id="conf-parameters" class="tabItem panel  {$selected_tab_parameters}"
             {if (empty($selected_tab_parameters))}style="display:none;"{/if}>
            <h3>
                {l s='Parameters' mod='priceminister'}
            </h3>

            <div class="form-group">
                <label class="control-label col-lg-3">&nbsp;</label>

                <div class="margin-form col-lg-9">
                    <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                        {l s='Please follow our online tutorial' mod='priceminister'} :<br>
                        <a href="http://documentation.common-services.com/priceminister/parametres/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                           target="_blank">http://documentation.common-services.com/priceminister/parametres/</a><br>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3"
                       style="color:grey">{l s='Import/Export' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <br>
                    <br>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Import Method' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <input type="radio" name="pm_parameters[import_method]" style="float:left"
                           {if $parameters.import_method == 'SKU'}checked{/if} value="SKU"/>
                    <label for="import-method" class="import-method-list">{l s='SKU' mod='priceminister'} -
                        <span style="font-size:1.0em;color:red">{l s='This option is recommended' mod='priceminister'}</span></label><br/>
                    <p>{l s='Import with SKU, mean the Reference field on Prestashop side (eg: IPOD-8Go)' mod='priceminister'}</p>
                    <br/>
                    <input type="radio" name="pm_parameters[import_method]" style="float:left"
                           {if $parameters.import_method == 'ID'}checked{/if} value="ID"/>
                    <label for="import-method" class="import-method-list">{l s='Product Id' mod='priceminister'}</label><br/>
                    <p>{l s='Product ID, Declination ID, separated by an underscore (eg: 2_31)' mod='priceminister'}</p>

                    <br/>
                    <input type="radio" name="pm_parameters[import_method]" style="float:left"
                           {if $parameters.import_method == 'ADVANCED'}checked{/if} value="ADVANCED"/>
                    <label for="import-method" class="import-method-list">{l s='Advanced Product ID' mod='priceminister'}</label><br/>
                    <p>{l s='Product ID, Declination ID, separated by a character p and c (eg: p2c31)' mod='priceminister'}</p>
                </div>
            </div>

            <div class="form-group">
                <input type="hidden" name="pm_parameters[taxes]" value="1"/>
                <label class="control-label col-lg-3">{l s='Discount/Specials' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="pm_parameters[specials]" id="specials_1" value="1"
                                       {if $parameters.specials}checked{/if} /><label for="specials_1"
                                                                                      class="label-checkbox">{l s='Yes' mod='priceminister'}</label>
                                <input type="radio" name="pm_parameters[specials]" id="specials_0" value="0"
                                       {if ! $parameters.specials}checked{/if} /><label for="specials_0"
                                                                                        class="label-checkbox">{l s='No' mod='priceminister'}</label>
                                <a class="slide-button btn"></a>
                            </span>
                    <br/>
                    <p>{l s='Export specials prices if is sets to Yes. If unsets the normal price will be exported' mod='priceminister'}</p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Customer Group' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <select name="pm_parameters[customer_group]" style="width:500px;">
                        <option disabled>{l s='Choose' mod='priceminister'}</option>
                        {foreach from=$parameters.customer_groups item=customer_group}
                            <option value="{$customer_group.id_group|intval}" {($customer_group.selected) ? 'selected' : ''}>{$customer_group.name|escape:'htmlall':'UTF-8'}</option>
                        {/foreach}
                    </select>
                    <br/>
                    <p>
                        {l s='You can create in PrestaShop customed customer group for your export in Customers > Groups' mod='priceminister'}
                        <br>
                        {l s='So, you can apply discounts only for your RakutenFrance exports.' mod='priceminister'}
                        <br>
                        {l s='Customers created while importing orders will also belong to that group.' mod='priceminister'}
                    </p>
                </div>
            </div>

            <hr style="width:30%;margin-bottom:15px;"/>

            {if isset($parameters.version_1_5)}
                {if isset($parameters.advanced_management)}
                    <div class="form-group">
                        <label class="control-label col-lg-3">{l s='Warehouse' mod='priceminister'}</label>
                        <div class="margin-form col-lg-9">
                            <select name="pm_parameters[warehouse]" style="width:500px;">
                                <option disabled>{l s='Choose' mod='priceminister'}</option>
                                {foreach from=$parameters.advanced_management_options item=advmgt_option}
                                    <option value="{$advmgt_option.value}" {$advmgt_option.selected}>{$advmgt_option.desc}</option>
                                {/foreach}
                            </select>
                            <p>{l s='Choose a warehouse for RakutenFrance products pickup (for Advanced Stock Management)' mod='priceminister'}</p>
                        </div>
                    </div>
                    <hr style="width:30%;margin-bottom:15px;"/>
                {/if}
            {/if}

            <div class="form-group">
                <label class="control-label col-lg-3">{l  s='Image Type' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <select name="pm_parameters[image_type]" id="image_type" style="width:200px;"/>
                    <option disabled>{l s='Choose' mod='priceminister'}</option>
                    {foreach from=$parameters.image_types item=image_type}
                        <option value="{$image_type.value}" {$image_type.selected}>{$image_type.desc}</option>
                    {/foreach}
                    </select>
                    <p>{l s='Kind of image which will be use for RakutenFrance (Please refer to Preference > Images for more informations)' mod='priceminister'}</p>
                </div>
            </div>

            {*
            <div class="form-group">
                <label class="control-label col-lg-3">{l  s='Allows no image' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="pm_parameters[image_optionnal]" id="image_optionnal_1" value="1" {if $parameters.image_optionnal}checked{/if} /><label for="image_optionnal_1" class="label-checkbox">{l s='Yes' mod='priceminister'}</label>
                                <input type="radio" name="pm_parameters[image_optionnal]" id="image_optionnal_0" value="0"  {if ! $parameters.image_optionnal}checked{/if} /><label for="image_optionnal_0" class="label-checkbox">{l s='No' mod='priceminister'}</label>
                                <a class="slide-button btn"></a>
                            </span>
                    <br/>
                    <p>{l s='Allow exports product without images' mod='priceminister'}</p>
                </div>
            </div>
            *}

            <hr style="width:30%;margin-bottom:15px;"/>

            {if isset($parameters.product_conditions)}
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Products Condition' mod='priceminister'}</label>
                    <div class="margin-form col-lg-9 condition-map">
                        {foreach from=$parameters.product_conditions item=product_condition}
                            <input type="text" readonly="true" style="width:200px;" readonly=true
                                   value="{$product_condition.value}">
                            <span>&nbsp;&nbsp;
                                <img src="{$images_url}next.png" alt="" class="condition-next"/>&nbsp;&nbsp;
                            </span>
                            <select name="pm_parameters[condition_map][{$product_condition.key}]"
                                    id="condition_map-{$product_condition.index}" style="width:200px">
                                <option value=""></option>
                                {foreach from=$product_condition.options item=product_condition_option}
                                    <option value="{$product_condition_option.value}" {$product_condition_option.selected}>{$product_condition_option.desc}</option>
                                {/foreach}
                            </select>
                            <br/>
                        {/foreach}
                        <p>{l s='RakutenFrance condition side / Prestashop condition side, please associate the parameters wished' mod='priceminister'}</p>
                    </div>
                </div>
            {else}
                <input type="hidden" name="pm_parameters[condition_map][N]" value="new"/>
            {/if}

            {include file="$module_path/views/templates/admin/configure/validate.tpl"}
        </div>
        <!--PARAMETERS END -->

        <!-- SHIPPING START -->
        <div id="conf-shipping" class="tabItem panel {$selected_tab_shipping}"
             {if (empty($selected_tab_shipping))}style="display:none;"{/if}>
            <h3>
                {l s='Shippings' mod='priceminister'}
            </h3>
            <input type="hidden" id="shipping-error-matrix"
                   value="{l s='You must fill at least one line of shipping matrix' mod='priceminister'}"/>

            <div class="form-group">
                <label class="control-label col-lg-3">&nbsp;</label>

                <div class="margin-form col-lg-9">
                    <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                        {l s='Please follow our online tutorial' mod='priceminister'} :<br>
                        <a href="http://documentation.common-services.com/priceminister/transport/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                           target="_blank">http://documentation.common-services.com/priceminister/transport/</a><br>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Shipping Matrix' mod='priceminister'}</label>
                <div class="margin-form col-lg-9 shipping-matrix">
                    {foreach from=$shippings.parameters.shipping_methods item=shipping}
                        <input type="text" name="pm_shipping[shipping_methods][{$shipping.method}]"
                               value="{$shipping.method}" class="shipping_method" readonly/>
                        <span>&nbsp;&nbsp;
                            <img src="{$images_url}next.png" class="shipping-next" alt=""/>&nbsp;&nbsp;
                        </span>
                        <select name="pm_shipping[pm_carriers][{$shipping.method}]" style="width:200px;">
                            <option value=""></option>
                            {foreach from=$shipping.pm_carriers item=carrier}
                                <option value="{$carrier.value}" {$carrier.selected}>{$carrier.desc}</option>
                            {/foreach}
                        </select>
                        <span>&nbsp;&nbsp;
                            <img src="{$images_url}next.png" class="shipping-next" alt=""/>&nbsp;&nbsp;
                        </span>
                        <select name="pm_shipping[ps_carriers][{$shipping.method}]" style="width:200px;">
                            <option value=""></option>
                            {foreach from=$shipping.ps_carriers item=carrier}
                                <option value="{$carrier.value}" {$carrier.selected}>{$carrier.desc}</option>
                            {/foreach}
                        </select>
                        <br/>
                    {/foreach}
                    <p>{l s='Please associate the PriceMinister shipping method to your Prestashop carrier. This setting is used for orders imports and orders statuses updates.' mod='priceminister'}</p>
                    <input type="text" id="shipping-validate-matrix" name="shipping-validate-matrix" value=""
                           style="visibility:hidden;"/>
                </div>
            </div>

            <hr style="width:600px;margin-bottom:25px;"/>

            <div class="form-group">
                <label class="control-label col-lg-3"
                       style="color:grey">{l s='Per Article Shipping Fees' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <input type="checkbox" name="pm_shipping[shipping_per_item]" rel="shipping_per_item" value="1"
                           {if ($shippings.shipping_per_item)}checked{/if} />
                    <label class="label-checkbox">{l s='Yes' mod='priceminister'}</label><br/>
                    <p>{l s='Active per article shipping fees. This configuration require certains knowledges/skills about RakutenFrance marketplace.' mod='priceminister'}
                </div>
            </div>

            <div id="shipping_per_item" {if (!$shippings.shipping_per_item)}style="display:none;"{/if}>
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Shipping Options' mod='priceminister'}</label>
                    <div class="margin-form col-lg-9 shipping-options">
                        {foreach from=$shippings.parameters.shipping_options item=item}
                            <input type="text" value="{$item.name}" disabled/>
                            <span>
                                &nbsp;&nbsp;<img src="{$images_url}next.png" class="shipping-next"
                                                 alt=""/>&nbsp;&nbsp;
                            </span>
                            {foreach from=$item.options item=option}
                                <input type="checkbox"
                                       name="pm_shipping[shipping_options][{$item.key}][{$option.key}]"
                                       class="shipping-option"
                                       rel="{$item.key}-{$option.key}" value="1"
                                       {if $option.selected}checked{/if} />
                                <span class="shipping-option-label">&nbsp;{$option.name}</span>
                            {/foreach}
                            <br/>
                        {/foreach}
                        <p>{l s='Please configure the actives shipping options on RakutenFrance.' mod='priceminister'}</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Shipping Defaults' mod='priceminister'}</label>
                    <div class="margin-form col-lg-9 shipping-defaults">
                        <table>
                            <tr>
                                <td style="width:240px"></td>
                                <td style="width:130px">{l s='Minimum' mod='priceminister'}</td>
                                <td style="width:130px">{l s='Additionnal' mod='priceminister'}</td>
                            </tr>
                        </table>
                        {foreach from=$shippings.parameters.shipping_table item=item}
                            <div {if (! $item.display)}style="display:none;"{/if}
                                 id="shipping-table-default-{$item.key1}-{$item.key2}">
                                <input type="text" value="{$item.name}" class="method" disabled/>
                                <span>
                                    &nbsp;&nbsp;<img src="{$images_url}next.png" class="shipping-next"
                                                     alt=""/>&nbsp;&nbsp;
                                </span>
                                <input type="text"
                                       name="pm_shipping[shipping_defaults][minimum][{$item.key1}][{$item.key2}]"
                                       style="width:100px;" class="shipping-default editable"
                                       value="{$item.minimum}">&nbsp;&nbsp;&euro;
                                <span>
                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                </span>
                                <input type="text"
                                       name="pm_shipping[shipping_defaults][additionnal][{$item.key1}][{$item.key2}]"
                                       style="width:100px;" class="shipping-additionnal editable"
                                       value="{$item.additionnal}">&nbsp;&nbsp;&#37;
                                <br/>
                            </div>
                        {/foreach}
                        <p>
                            {l s='Please indicate your defaults/minimum shipping rates.' mod='priceminister'}<br>
                            {l s='Please indicate in the second column the additionnal fees for additionnal items, in percentage (of the initial price)' mod='priceminister'}
                        </p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Carriers Associations' mod='priceminister'}</label>
                    <div class="margin-form col-lg-9 shipping-zones">
                        {foreach from=$shippings.parameters.shipping_table item=item}
                            <div {if (! $item.display)}style="display:none"{/if}
                                 id="shipping-table-carrier-{$item.key1}-{$item.key2}">
                                <input type="text" value="{$item.name}" disabled/>
                                <span>
                                    &nbsp;&nbsp;<img src="{$images_url}next.png" class="shipping-next"
                                                     alt=""/>&nbsp;&nbsp;
                                </span>
                                <select name="pm_shipping[shipping_table][carrier][{$item.key1}][{$item.key2}]"
                                        class="editable" style="width:200px;">
                                    <option disabled
                                            style="color:grey">{l s='Please choose a carrier' mod='priceminister'}</option>
                                    <option value=""></option>
                                    {foreach from=$shipping.ps_carriers item=carrier}
                                        <option value="{$carrier.value}"
                                                {if ($item.selected_carrier == $carrier.value)}selected{/if}>{$carrier.desc}</option>
                                    {/foreach}
                                </select>
                                <span>
                                    &nbsp;&nbsp;<img src="{$images_url}slash.png" class="shipping-next"
                                                     alt=""/>&nbsp;&nbsp;
                                </span>
                                <select name="pm_shipping[shipping_table][zone][{$item.key1}][{$item.key2}]"
                                        class="editable">
                                    <option disabled
                                            style="color:grey">{l s='Please choose a zone' mod='priceminister'}</option>
                                    <option value=""></option>
                                    {foreach from=$shippings.parameters.ps_zones item=zone}
                                        <option value="{$zone.id_zone}"
                                                {if ($item.selected_zone == $zone.id_zone)}selected{/if}>{$zone.name}</option>
                                    {/foreach}
                                </select>
                                <br/>
                            </div>
                        {/foreach}
                        <p>{l s='Please associate the Prestashop zone and carrier to your RakutenFrance shipping configuration. This configuration is used to determine the shipping rates.' mod='priceminister'}</p>
                    </div>
                </div>
            </div>

            {include file="$module_path/views/templates/admin/configure/validate.tpl"}
        </div>
        <!-- SHIPPING END -->

        <!--MAPPINGS START -->
        {include file="$module_path/views/templates/admin/configure/mapping.tpl"}
        <!--MAPPINGS END -->

        <!-- FILTERS START -->
        <div id="conf-filters" class="tabItem panel {$selected_tab_filters}" {if (empty($selected_tab_filters))}style="display:none;"{/if}>
            <h3>{l s='Filters' mod='priceminister'}</h3>

            <div class="form-group">
                <label class="control-label col-lg-3">&nbsp;</label>

                <div class="margin-form col-lg-9">
                    <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                        {l s='Please follow our online tutorial' mod='priceminister'} :<br>
                        <a href="http://documentation.common-services.com/priceminister/filtres/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                           target="_blank">http://documentation.common-services.com/priceminister/filtres/</a><br>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class='control-label col-lg-3'>{l s='Out of Stock' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <input type="text" name="pm_filters[outofstock]" value="{$filters.outofstock|intval}"
                           style="width: 250px;"/>
                    <p>{l s='Minimum quantity in stock to export the product' mod='priceminister'}</p>
                </div>
            </div>

            <div class="form-group">
                <label class='control-label col-lg-3'>{l s='Export only products with price between' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <input type="text" name="pm_filters[price][down]" value="{$filters.price.down|default:0|escape:'htmlall':'UTF-8'}" style="width: 150px; display: inline-block;"/>
                    {l s='and' mod='priceminister'}
                    <input type="text" name="pm_filters[price][up]" value="{$filters.price.up|default:10000|escape:'htmlall':'UTF-8'}" style="width: 150px; display: inline-block;"/>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3"
                       style="color:grey">{l s='Manufacturers Filters' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <div class="manufacturer-heading">
                        <span><img src="{$images_url}cross.png"
                                   alt="{l s='Excluded' mod='priceminister'}"/></span>
                        <span><img src="{$images_url}checked.png"
                                   alt="{l s='Included' mod='priceminister'}"/></span>
                    </div>
                    <select name="pm_filters[manufacturers][]" class="selected-manufacturers"
                            id="selected-manufacturers" multiple="multiple">
                        <option value="0" disabled
                                style="color:orange;">{l s='Excluded Manufacturers' mod='priceminister'}</option>
                        {foreach from=$filters.manufacturers.filtered key=id_manufacturer item=name}
                            <option value="{$id_manufacturer}">{$name}</option>
                        {/foreach}
                    </select>
                    <div class="sep">
                        <img src="{$images_url}previous.png" class="move" id="manufacturer-move-left"
                             alt="Left"/><br/><br/>
                        <img src="{$images_url}next.png" class="move" id="manufacturer-move-right"
                             alt="Right"/>
                    </div>
                    <select class="available-manufacturers" id="available-manufacturers" multiple="multiple">

                        <option value="0" disabled
                                style="color:green;">{l s='Included Manufacturers' mod='priceminister'}</option>
                        {foreach from=$filters.manufacturers.available key=id_manufacturer item=name}
                            <option value="{$id_manufacturer}">{$name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="cleaner"></div>
            <hr style="width:30%;margin-top:10px;"/>

            <div class="form-group">
                <label class="control-label col-lg-3"
                       style="color:grey">{l s='Suppliers Filters' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <div class="supplier-heading">
                        <span><img src="{$images_url}cross.png"
                                   alt="{l s='Excluded' mod='priceminister'}"/></span>
                        <span><img src="{$images_url}checked.png"
                                   alt="{l s='Included' mod='priceminister'}"/></span>
                    </div>
                    <select name="pm_filters[suppliers][]" class="selected-suppliers" id="selected-suppliers"
                            multiple="multiple">
                        <option value="0" disabled
                                style="color:orange;">{l s='Excluded Suppliers' mod='priceminister'}</option>
                        {foreach from=$filters.suppliers.filtered key=id_supplier item=name}
                            <option value="{$id_supplier}">{$name}</option>
                        {/foreach}
                    </select>
                    <div class="sep">
                        <img src="{$images_url}previous.png" class="move" id="supplier-move-left"
                             alt="Left"/><br/><br/>
                        <img src="{$images_url}next.png" class="move" id="supplier-move-right"
                             alt="Right"/>
                    </div>
                    <select class="available-suppliers" id="available-suppliers" multiple="multiple">
                        <option value="0" disabled
                                style="color:green;">{l s='Included Suppliers' mod='priceminister'}</option>
                        {foreach from=$filters.suppliers.available key=id_supplier item=name}
                            <option value="{$id_supplier}">{$name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="cleaner"></div>
            {include file="$module_path/views/templates/admin/configure/validate.tpl"}
        </div>
        <!-- FILTERS END -->


        <!-- ORDERS START -->
        <div id="conf-orders" class="tabItem panel {$selected_tab_orders}"
             {if (empty($selected_tab_orders))}style="display:none;"{/if}>
            <h3>
                {l s='Orders' mod='priceminister'}
            </h3>
            <input type="hidden" id="orders-error-status-incoming"
                   value="{l s='You must enter a valid incoming status' mod='priceminister'}"/>
            <input type="hidden" id="orders-error-status-sent"
                   value="{l s='You must enter a valid sent status' mod='priceminister'}"/>

            <div class="form-group">
                <label class="control-label col-lg-3">&nbsp;</label>

                <div class="margin-form col-lg-9">
                    <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                        {l s='Please follow our online tutorial' mod='priceminister'} :<br>
                        <a href="http://documentation.common-services.com/priceminister/statuts-commandes/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                           target="_blank">http://documentation.common-services.com/priceminister/statuts-commandes/</a><br>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Orders Statuses' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <select name="pm_orders[status_incoming]" style="width:500px;">
                        <option value="0">{l s='Choose a default incoming order status for Rakuten France' mod='priceminister'}</option>
                        {foreach from=$orders.order_states item=order_state}
                            <option value="{$order_state.value}"
                                    {if $orders.status_incoming == $order_state.value}selected{/if}>{$order_state.desc}</option>
                        {/foreach}
                    </select>
                    <p>{l s='Choose the default order state for new incoming orders' mod='priceminister'}</p>
                </div>

                <label class="control-label col-lg-3">&nbsp;</label>
                <div class="margin-form col-lg-9">
                    <select name="pm_orders[status_sent]" style="width:500px;">
                        <option value="0">{l s='Choose a default sent order status for Rakuten France' mod='priceminister'}</option>
                        {foreach from=$orders.order_states item=order_state}
                            <option value="{$order_state.value}"
                                    {if $orders.status_sent == $order_state.value}selected{/if}>{$order_state.desc}</option>
                        {/foreach}
                    </select>
                    <p>{l s='Choose the default order state for sent orders' mod='priceminister'}</p>
                </div>
            </div>

            <hr style="width:30%;margin-bottom: 25px;">

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Customer Account' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <input type="radio" name="pm_orders[customer_account]" id="customer_account_same"
                           class="customer_account_type"
                           style="float:left" {if $orders.customer_account == $orders.one_customer_account}checked{/if}
                           value="{$orders.one_customer_account}"/>
                    <label for="customer_account_same"
                           class="customer-account-list">{l s='One Account' mod='priceminister'}</label><br/>
                    <p>{l s='This method create one global PriceMinister account for all the customers on orders import' mod='priceminister'}</p>
                    <br/>
                    <input type="radio" name="pm_orders[customer_account]" id="customer_account_individual"
                           class="customer_account_type"
                           style="float:left" {if $orders.customer_account == $orders.individual_customer_account}checked{/if}
                           value="{$orders.individual_customer_account}"/>
                    <label for="customer_account_individual"
                           class="customer-account-list">{l s='Individual Customer Account' mod='priceminister'}</label><br/>
                    <p>{l s='This method create individual accounts per customer on orders import' mod='priceminister'}</p>
                </div>
            </div>

            <div class="form-group" id="set-domain"
                 {if $orders.customer_account == $orders.one_customer_account}style="display: none;"{/if}>
                <label class="control-label col-lg-3">&nbsp;</label>
                <div class="margin-form col-lg-9">
                    <input type="text" name="pm_orders[email_domain]" id="customer_domain"
                           value="{$orders.email_domain}" style="width:300px;"/>
                    <p>{l s='Please choose a domain name for the customer\'s email addresses' mod='priceminister'}<br/>
                        <span style="color:red">{l s='Please note this configuration must be managed by a professional (eg: Webmaster, Web Agency)' mod='priceminister'}</span>
                    </p>
                </div>
            </div>

            <div class="margin-form col-lg-offset-3">
                <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                    <p>
                        {l s='From July 1st, 2021, the marketplace requires some additional fields when accepting orders' mod='priceminister'}
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Shipping from country' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <select name="pm_orders[shippingfromcountry]" style="width: 250px;" title="Shipping from country">
                        <option value="0">{l s='Choose the country from which the item has been sent' mod='priceminister'}</option>
                        {foreach from=$orders.shipping_from_countries item=shippingFromCountry}
                            <option value="{$shippingFromCountry|escape:'htmlall':'UTF-8'}"
                                    {if $shippingFromCountry == $orders.shippingfromcountry}selected{/if}>
                                {$shippingFromCountry|escape:'htmlall':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                    <p>{l s='Choose the country from which the item has been sent' mod='priceminister'}</p>
                </div>
            </div>

            {include file="$module_path/views/templates/admin/configure/validate.tpl"}
        </div>
        <!-- ORDERS END -->

        <!-- REPRICING START -->
        <div id="conf-repricing" class="tabItem panel {$selected_tab_orders}"
             {if (empty($selected_tab_orders))}style="display:none;"{/if}>
            <h3>
                {l s='Repricing' mod='priceminister'}
            </h3>

            <div class="form-group">
                <label class="control-label col-lg-3">&nbsp;</label>

                <div class="margin-form col-lg-9">
                    <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                        {l s='Please follow our online tutorial' mod='priceminister'} :<br>
                        <a href="http://documentation.common-services.com/priceminister/configurer-le-repricing/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                           target="_blank">http://documentation.common-services.com/priceminister/repricing/
                        </a><br>
                        <hr>
                        <p>
                            <strong>Note importante :</strong> Le repricing est une fonctionnalité expérimentale,
                            vérifiez et testez bien vos offres afin d'être sûr de ce que vous envoyez à
                            RakutenFrance.<br>
                            <strong>Bug connu :</strong> Un bug venant de RakutenFrance survient parfois, le prix de
                            livraison d'un concurrent n'est pas toujours le plus bas. Le web service de RakutenFrance
                            nous envoi un prix de livraison mais pour le même produit il existe au moins une offre de
                            livraison moins ch&egrave;re. Cela fausse par conséquent le repricing de certains produits.
                        </p>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3"></label>
                <div class="margin-form col-lg-9">
                    <button class="btn btn-default" style="float:right" id="repricing-add">
                        <img src="{$images_url}plus.png" class="new-repricing-img"
                             alt=""/>&nbsp;&nbsp; {l s='Add a new repricing strategy' mod='priceminister'}
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">&nbsp;</label>
                <div class="margin-form col-lg-9">
                    <h2 class="pm-model-title">{l s='New Repricing Strategy' mod='priceminister'}</h2>
                </div>
            </div>

            <div id="pm-master-repricing" style="display: none;">
                <div class="margin-form col-lg-offset-3 pm-repricing" rel>
                    <fieldset>
                        <div class="pm-repricing-body form-group">
                            <label class="control-label col-lg-3">Nom</label>
                            <div class="margin-form col-lg-9">
                                <input type="text" name="strategies[_key_][name]" value="">
                            </div>
                            <div class="clearfix">&nbsp;</div>

                            <label class="control-label col-lg-3">Activer</label>
                            <div class="margin-form col-lg-9">
                                <span class="switch prestashop-switch fixed-width-lg">
                                    <input type="radio" name="strategies[_key_][active]" id="strategie-active-1_key_"
                                           value="1" checked>
                                    <label for="strategie-active-1_key_" class="label-checkbox">Oui</label>
                                    <input type="radio" name="strategies[_key_][active]" id="strategie-active-2_key_"
                                           value="0">
                                    <label for="strategie-active-2_key_" class="label-checkbox">Non</label>
                                    <a class="slide-button btn"></a>
                                </span>
                            </div>
                            <div class="clearfix">&nbsp;</div>

                            <label class="control-label col-lg-3">Agressivité</label>
                            <div class="margin-form col-lg-9">
                                <select name="strategies[_key_][aggressiveness]">
                                    <option value=""></option>
                                    {section name=aggressiveness loop=10}
                                        <option value="{$smarty.section.aggressiveness.iteration|escape:'htmlall':'UTF-8'}">
                                            {$smarty.section.aggressiveness.iteration|escape:'htmlall':'UTF-8'}
                                        </option>
                                    {/section}
                                </select>
                            </div>
                            <div class="clearfix">&nbsp;</div>

                            <label class="control-label col-lg-3">Base</label>
                            <div class="margin-form col-lg-9">
                                <select name="strategies[_key_][base]" class="pm-repricing-base">
                                    <option value="1" {($strategie.base|default:0|intval == 1) ? 'selected' : ''}>{l s='Wholesale Price' mod='priceminister'}</option>
                                    <option value="2" {($strategie.base|default:0|intval == 2) ? 'selected' : ''}>{l s='Normal Price' mod='priceminister'}</option>
                                </select>
                            </div>
                            <div class="clearfix">&nbsp;</div>

                            <label class="control-label col-lg-3">Limite</label>
                            <div class="margin-form col-lg-9">
                                <select name="strategies[_key_][limit]">
                                    <option value=""></option>
                                    {section name=limit loop=100}
                                        <option value="-{$smarty.section.limit.iteration|escape:'htmlall':'UTF-8'}">
                                            - {$smarty.section.limit.iteration|escape:'htmlall':'UTF-8'}%
                                        </option>
                                    {/section}
                                    {section name=limit loop=200}
                                        <option value="{$smarty.section.limit.iteration|escape:'htmlall':'UTF-8'}">
                                            + {$smarty.section.limit.iteration|escape:'htmlall':'UTF-8'}%
                                        </option>
                                    {/section}
                                </select>
                            </div>
                            <div class="clearfix">&nbsp;</div>

                            <label class="control-label col-lg-3">Delta</label>
                            <div class="margin-form col-lg-9">
                                <select name="strategies[_key_][delta][min]">
                                    <option value=""></option>
                                    {section name=delta loop=100}
                                        <option value="-{$smarty.section.delta.iteration|escape:'htmlall':'UTF-8'}">
                                            - {$smarty.section.delta.iteration|escape:'htmlall':'UTF-8'}%
                                        </option>
                                    {/section}
                                    {section name=delta loop=200}
                                        <option value="{$smarty.section.delta.iteration|escape:'htmlall':'UTF-8'}">
                                            + {$smarty.section.delta.iteration|escape:'htmlall':'UTF-8'}%
                                        </option>
                                    {/section}
                                </select>
                                &nbsp;&nbsp;-&nbsp;&nbsp;
                                <select name="strategies[_key_][delta][max]">
                                    <option value="0"></option>
                                    {section name=delta loop=100}
                                        <option value="-{$smarty.section.delta.iteration|escape:'htmlall':'UTF-8'}">
                                            - {$smarty.section.delta.iteration|escape:'htmlall':'UTF-8'}%
                                        </option>
                                    {/section}
                                    {section name=delta loop=200}
                                        <option value="{$smarty.section.delta.iteration|escape:'htmlall':'UTF-8'}">
                                            + {$smarty.section.delta.iteration|escape:'htmlall':'UTF-8'}%
                                        </option>
                                    {/section}
                                </select>
                            </div>
                            <div class="clearfix">&nbsp;</div>

                            <div class="pm-repricing-delete">
                                <button type="button" class="btn btn-default"><img
                                            src="{$images_url}cross.png" class="del-repricing-img"
                                            alt="">&nbsp;&nbsp; {l s='Delete' mod='priceminister'}</button>
                            </div>
                        </div>
                    </fieldset>
                    <hr style="width: 30%;">
                </div>
            </div>

            <div class="form-group pm-repricing-group ui-sortable" id="pm-repricing-container">
                {if is_array($repricing.repricing_strategies) && count($repricing.repricing_strategies)}
                    {foreach $repricing.repricing_strategies as $id_repricing => $strategie}
                        <div class="margin-form col-lg-offset-3 pm-repricing" rel="{$id_repricing|escape:'htmlall':'UTF-8'}">
                            <fieldset>
                                <div class="pm-repricing-header">
                                    <div>
                                        <label class="pm-repricing-header-repricing-name">{$strategie.name|escape:'htmlall':'UTF-8'}</label>
                                        <span class="pm-repricing-action">
                                            <img src="{$images_url}cross.png"
                                                 class="pm-repricing-action-delete" alt="">
                                            <img src="{$images_url}edit.png"
                                                 class="pm-repricing-action-edit" alt="">
                                        </span>
                                    </div>
                                </div>
                                <div class="pm-repricing-body form-group" style="display: none;">
                                    <label class="control-label col-lg-3">{l s='Name' mod='priceminister'}</label>
                                    <div class="margin-form col-lg-9">
                                        <input type="text" name="strategies[{$id_repricing|escape:'htmlall':'UTF-8'}][name]"
                                               value="{$strategie.name|escape:'htmlall':'UTF-8'}">
                                    </div>
                                    <div class="clearfix">&nbsp;</div>

                                    <label class="control-label col-lg-3">{l s='Active' mod='priceminister'}</label>
                                    <div class="margin-form col-lg-9">
                                        <span class="switch prestashop-switch fixed-width-lg">
                                            <input type="radio" name="strategies[{$id_repricing|escape:'htmlall':'UTF-8'}][active]"
                                                   id="strategie-active-{$id_repricing|escape:'htmlall':'UTF-8'}-1" value="1" {($strategie.active|default:0|intval == 1) ? 'checked' : ''}>
                                            <label for="strategie-active-{$id_repricing|escape:'htmlall':'UTF-8'}-1"
                                                   class="label-checkbox">{l s='Yes' mod='priceminister'}</label>
                                            <input type="radio" name="strategies[{$id_repricing|escape:'htmlall':'UTF-8'}][active]"
                                                   id="strategie-active-{$id_repricing|escape:'htmlall':'UTF-8'}-2" value="0" {($strategie.active|default:0|intval != 1) ? 'checked' : ''}>
                                            <label for="strategie-active-{$id_repricing|escape:'htmlall':'UTF-8'}-2"
                                                   class="label-checkbox">{l s='No' mod='priceminister'}</label>
                                            <a class="slide-button btn"></a>
                                        </span>
                                    </div>
                                    <div class="clearfix">&nbsp;</div>

                                    <label class="control-label col-lg-3">{l s='Aggressiveness' mod='priceminister'}</label>
                                    <div class="margin-form col-lg-9">
                                        <select name="strategies[{$id_repricing|escape:'htmlall':'UTF-8'}][aggressiveness]">
                                            <option value=""></option>
                                            {section name=aggressiveness loop=10}
                                                <option value="{$smarty.section.aggressiveness.iteration|escape:'htmlall':'UTF-8'}"
                                                        {($strategie.aggressiveness|default:0|intval == $smarty.section.aggressiveness.iteration) ? 'selected' : ''}>
                                                    {$smarty.section.aggressiveness.iteration|escape:'htmlall':'UTF-8'}
                                                </option>
                                            {/section}
                                        </select>
                                    </div>
                                    <div class="clearfix">&nbsp;</div>

                                    <label class="control-label col-lg-3">Base</label>
                                    <div class="margin-form col-lg-9">
                                        <select name="strategies[{$id_repricing|escape:'htmlall':'UTF-8'}][base]" class="pm-repricing-base">
                                            <option value="1" {($strategie.base|default:0|intval == 1) ? 'selected' : ''}>{l s='Wholesale Price' mod='priceminister'}</option>
                                            <option value="2" {($strategie.base|default:0|intval == 2) ? 'selected' : ''}>{l s='Normal Price' mod='priceminister'}</option>
                                        </select>
                                    </div>
                                    <div class="clearfix">&nbsp;</div>

                                    <label class="control-label col-lg-3">{l s='Limit' mod='priceminister'}</label>
                                    <div class="margin-form col-lg-9">
                                        <select name="strategies[{$id_repricing|escape:'htmlall':'UTF-8'}][limit]">
                                            <option value=""></option>
                                            {section name=limit loop=100}
                                                <option value="-{$smarty.section.limit.iteration|escape:'htmlall':'UTF-8'}"
                                                        {($strategie.limit|default:10|intval == -$smarty.section.limit.iteration) ? 'selected' : ''}>
                                                    - {$smarty.section.limit.iteration|escape:'htmlall':'UTF-8'}%
                                                </option>
                                            {/section}
                                            {section name=limit loop=200}
                                                <option value="{$smarty.section.limit.iteration|escape:'htmlall':'UTF-8'}"
                                                        {($strategie.limit|default:10|intval == $smarty.section.limit.iteration) ? 'selected' : ''}>
                                                    + {$smarty.section.limit.iteration|escape:'htmlall':'UTF-8'}%
                                                </option>
                                            {/section}
                                        </select>
                                    </div>
                                    <div class="clearfix">&nbsp;</div>

                                    <label class="control-label col-lg-3">Delta</label>
                                    <div class="margin-form col-lg-9">
                                        <select name="strategies[{$id_repricing|escape:'htmlall':'UTF-8'}][delta][min]">
                                            <option value="0"></option>
                                            {section name=delta loop=100}
                                                <option value="-{$smarty.section.delta.iteration|escape:'htmlall':'UTF-8'}"
                                                        {($strategie.delta.min|default:0|intval == -$smarty.section.delta.iteration) ? 'selected' : ''}>
                                                    - {$smarty.section.delta.iteration|escape:'htmlall':'UTF-8'}%
                                                </option>
                                            {/section}
                                            {section name=delta loop=200}
                                                <option value="{$smarty.section.delta.iteration|escape:'htmlall':'UTF-8'}"
                                                        {($strategie.delta.min|default:0|intval == $smarty.section.delta.iteration) ? 'selected' : ''}>
                                                    + {$smarty.section.delta.iteration|escape:'htmlall':'UTF-8'}%
                                                </option>
                                            {/section}
                                        </select>
                                        &nbsp;&nbsp;-&nbsp;&nbsp;
                                        <select name="strategies[{$id_repricing|escape:'htmlall':'UTF-8'}][delta][max]">
                                            <option value="0"></option>
                                            {section name=delta loop=100}
                                                <option value="-{$smarty.section.delta.iteration|escape:'htmlall':'UTF-8'}"
                                                        {($strategie.delta.max|default:0|intval == -$smarty.section.delta.iteration) ? 'selected' : ''}>
                                                    - {$smarty.section.delta.iteration|escape:'htmlall':'UTF-8'}%
                                                </option>
                                            {/section}
                                            {section name=delta loop=200}
                                                <option value="{$smarty.section.delta.iteration|escape:'htmlall':'UTF-8'}"
                                                        {($strategie.delta.max|default:0|intval == $smarty.section.delta.iteration) ? 'selected' : ''}>
                                                    + {$smarty.section.delta.iteration|escape:'htmlall':'UTF-8'}%
                                                </option>
                                            {/section}
                                        </select>
                                    </div>
                                    <div class="clearfix">&nbsp;</div>

                                    <div class="pm-repricing-delete">
                                        <button type="button" class="btn btn-default"><img
                                                    src="{$images_url}cross.png" class="del-repricing-img"
                                                    alt="">&nbsp;&nbsp; {l s='Delete' mod='priceminister'}</button>
                                    </div>
                                    <div class="pm-repricing-minimize">
                                        <button type="button" class="btn btn-default"><img
                                                    src="{$images_url}minimize.png"
                                                    class="min-repricing-img"
                                                    alt="">&nbsp;&nbsp; {l s='Minimize' mod='priceminister'}</button>
                                    </div>
                                </div>
                            </fieldset>
                            <hr style="width: 30%;">
                        </div>
                    {/foreach}
                {/if}
            </div>

            <span class="pm-required">*</span>:&nbsp;{l s='Required' mod='priceminister'}

            {include file="$module_path/views/templates/admin/configure/validate.tpl"}
        </div>
        <!-- REPRICING END -->

        <!-- CRON START -->
        <div id="conf-cron" class="tabItem panel {$selected_tab_cron}"
             {if (empty($selected_tab_cron))}style="display:none;"{/if}>
            <h3>
                {l s='Cron' mod='priceminister'}
            </h3>

            <div class="form-group">
                <label class="control-label col-lg-3">&nbsp;</label>

                <div class="margin-form col-lg-9">
                    <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                        {l s='Please follow our online tutorial' mod='priceminister'} :<br>
                        <a href="http://documentation.common-services.com/priceminister/taches-planifiees/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                           target="_blank">http://documentation.common-services.com/priceminister/taches-planifiees/</a><br>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="margin-form col-lg-offset-3">
                    <div id="cronjobs_success" class="{$alert_class.success}" style="display:none;">
                    </div>

                    <div id="cronjobs_error" class="{$alert_class.danger}" style="display:none;">
                    </div>
                </div>
            </div>

            {if !$cron.synch_url}
                <div class="form-group">
                    <div class="margin-form col-lg-offset-3">
                        <div class="{$alert_class.danger}">
                            {l s='Your module is not yet configured' mod='priceminister'}
                        </div>
                    </div>
                </div>
            {else}
                <div class="form-group">
                    <div class="margin-form col-lg-offset-3">
                        <div class="cron-mode" rel="prestashop-cron">
                            <img src="{$images_url}/prestashop-cronjobs-icon.png"
                                 title="{l s='Prestashop Cronjobs (Module)' mod='priceminister'}"/>
                            <h4>{l s='Prestashop Cronjobs (Module)' mod='priceminister'}</h4>
                            <div style="float:right" class="cron-prestashop">
                                {if $cron.cronjobs.installed}
                                    <span style="color:green">{l s='Installed' mod='priceminister'}</span>
                                {elseif $cron.cronjobs.exists}
                                    <span style="color:red">{l s='Detected, Not installed' mod='priceminister'}</span>
                                {else}
                                    <span style="color:red">{l s='Not detected' mod='priceminister'}</span>
                                {/if}
                            </div>
                        </div>

                    </div>
                </div>
                <div id="prestashop-cron" class="cron-toggle" {if !$cron.cronjobs.installed}style="display:none"{/if} >
                    <div class="form-group">
                        <div class="margin-form col-lg-offset-3">
                            {if !$cron.cronjobs.installed}
                                <div class="{$alert_class.warning}">{l s='Prestashop Cronjobs is not installed.' mod='priceminister'}
                                    {if !$cron.cronjobs.exists}
                                        <br>
                                        (
                                        <a href="https://github.com/PrestaShop/cronjobs/archive/master.zip"
                                           target="_blank">https://github.com/PrestaShop/cronjobs</a>
                                        )
                                    {/if}
                                </div>
                            {else}
                                <span class="title">{l s='Those lines will be added in Prestashop Cronjobs module' mod='priceminister'}
                                    :</span>
                                <div id="prestashop-cronjobs-lines">
                                    {if $cron.stdtypes}
                                        {foreach from=$cron.stdtypes item=type}
                                            <b>{$cron[$type].title}</b>
                                            : {l s='each' mod='priceminister'} {$cron[$type].frequency} {if $cron[$type].frequency > 1}{l s='hours' mod='priceminister'}{else}{l s='hour' mod='priceminister'}{/if}, {l s='url' mod='priceminister'} :
                                            <a href="{$cron[$type].url}"
                                               target="_blank">{$cron[$type].url_short}</a>
                                            <br>
                                        {/foreach}
                                    {/if}
                                </div>
                                <textarea id="prestashop-cronjobs-params" name="prestashop-cronjobs-params"
                                          style="display:none">
									{if $cron.stdtypes}
                                        {foreach from=$cron.stdtypes item=type}
                                            {$cron[$type].title}|0|{$cron[$type].frequency}|{$cron[$type].url}!
                                        {/foreach}
                                    {/if}
								</textarea>
                                <br/>
                                {if $cron.cronjobs.installed}
                                    <span style="color:green">{l s='Click on install/update button to auto-configure your Prestashop cronjobs module' mod='priceminister'}
                                        :</span>
                                    <button class="button btn btn-default" style="float:right" id="install-cronjobs">
                                        <img src="{$images_url}plus.png" alt=""/>
                                        {l s='Install/Update' mod='priceminister'}
                                    </button>
                                    <img src="{$images_url}loader-connection.gif" alt=""
                                         id="cronjob-loader"/>
                                {/if}
                            {/if}
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="margin-form col-lg-offset-3">
                        <div class="cron-mode" rel="manual-cron">
                            <img src="{$images_url}/terminal.png"
                                 title="{l s='Manual Cron URLs' mod='priceminister'}"/>
                            <h4>{l s='Manual Cron URLs' mod='priceminister'}</h4>
                        </div>
                    </div>
                </div>
                <div id="manual-cron" class="cron-toggle" {if $cron.cronjobs.installed}style="display:none"{/if}>
                    <div class="form-group">
                        <label class="control-label col-lg-3"></label>

                        <div class="margin-form col-lg-9">
                            <input type="text" style="color:grey;background-color:#fdfdfd;width: 100%;"
                                   value="{$cron.create_url.url}"/><br/>
                            <p>{l s='URL to send your entire catalog to be used to configure your crontab.' mod='priceminister'}</p>

                            <input type="text" style="color:grey;background-color:#fdfdfd;width: 100%;"
                                   value="{$cron.synch_url.url}"/><br/>
                            <p>{l s='URL to update your offers to be used to configure your crontab.' mod='priceminister'}</p>

                            <input type="text" style="color:grey;background-color:#fdfdfd;width: 100%;"
                                   value="{$cron.accept_url.url}"/><br/>
                            <p>{l s='URL to accept orders to be used to configure your crontab.' mod='priceminister'}</p>

                            <input type="text" style="color:grey;background-color:#fdfdfd;width: 100%;"
                                   value="{$cron.import_url.url}"/><br/>
                            <p>{l s='URL to import orders to be used to configure your crontab.' mod='priceminister'}</p>

                            <input type="text" style="color:grey;background-color:#fdfdfd;width: 100%;"
                                   value="{$cron.order_update.url}"/><br/>
                            <p>{l s='URL to update your orders shipping information, to be used to configure your crontab.' mod='priceminister'}</p>

                            <input type="text" style="color:grey;background-color:#fdfdfd;width: 100%;"
                                   value="{$cron.repricing_fetch.url}"/><br/>
                            <p>{l s='URL to fetch the repricing file.' mod='priceminister'}</p>

                            <input type="text" style="color:grey;background-color:#fdfdfd;width: 100%;"
                                   value="{$cron.repricing_treat.url}"/><br/>
                            <p>{l s='URL to treat the repricing file.' mod='priceminister'}</p>

                            <span style="color:brown;font-weight:bold;">
								{l s='Be carefull ! Importing orders by cron can skips somes orders (eg: out of stock), you must check also manually' mod='priceminister'}
							</span>
                        </div>
                    </div>
                </div>
            {/if}


        </div>
        <div class="cleaner"></div>
    </fieldset>
    <input type="hidden" id="selected_tab" name="selected_tab" value="{$selected_tab}"/>
</form>
