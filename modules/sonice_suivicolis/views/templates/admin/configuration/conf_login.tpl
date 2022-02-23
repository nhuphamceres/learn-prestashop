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
 * ...........................................................................
 *
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice@common-services.com
 *}

<div id="conf-login" style="display: none;">
    <h2>{l s='Login' mod='sonice_suivicolis'}</h2>

    <div class="form-group">
        <label class="control-label col-lg-3" rel="login">{l s='Login' mod='sonice_suivicolis'}</label>
        <div class="margin-form snsc_typo_conftab col-lg-9">
            <input type="text" name="return_info[login]" value="{if isset($snsc_config.login)}{$snsc_config.login|escape:'htmlall':'UTF-8'}{/if}" class="connectParam" autocomplete="off">
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3" rel="pwd">{l s='Password' mod='sonice_suivicolis'}</label>
        <div class="margin-form snsc_typo_conftab col-lg-9">
            <input type="password" name="return_info[pwd]" value="{if isset($snsc_config.pwd)}{$snsc_config.pwd|escape:'htmlall':'UTF-8'}{/if}" class="connectParam">
        </div>
    </div>
    <input type="hidden" id="empty_field" value="{l s='You must fill the login and password field !' mod='sonice_suivicolis'}">

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>
        <div class="margin-form snsc_typo_conftab col-lg-9">
            <input type="button" class="button snsc_button btn btn-default" id="login_checker" value="{l s='Check your login' mod='sonice_suivicolis'}">
            &nbsp;<img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}loader.gif" alt="loader" id="etg_loader" style="width: 20px; display: none;">
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>
        <div class="margin-form snsc_typo_conftab col-lg-9">
            <div class="{$alert_class.success|escape:'htmlall':'UTF-8'}" id="login_ok" style="display: none;">
                {l s='Your login is correct !' mod='sonice_suivicolis'}
            </div>
            <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'}" id="login_not_ok" style="display:none">
                {l s='Your login is incorrect :' mod='sonice_suivicolis'}<br>
                <strong>ID :</strong> <span id="errorID"></span><br>
                <strong>Message :</strong> <span id="error"></span>
                <span id="error_request"></span>
                <span id="error_response"></span>
                <span id="error_output"></span>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3" rel="debug_mode">{l s='Debug Mode' mod='sonice_suivicolis'}</label>
        <div class="margin-form snsc_typo_conftab col-lg-9">
            <span class="switch prestashop-switch fixed-width-lg">
                <input type="radio" name="return_info[debug]" id="return_debug_1" value="1" {if isset($snsc_config.debug) && $snsc_config.debug}checked{/if} /><label for="return_debug_1" class="label-checkbox">{l s='Yes' mod='sonice_suivicolis'}</label>
                <input type="radio" name="return_info[debug]" id="return_debug_0" value="0" {if isset($snsc_config.debug) && $snsc_config.debug == 0}checked{/if} /><label for="return_debug_0" class="label-checkbox">{l s='No' mod='sonice_suivicolis'}</label>
                <a class="slide-button btn"></a>
            </span>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3" rel="test_mode">{l s='Test Mode' mod='sonice_suivicolis'}</label>
        <div class="margin-form snsc_typo_conftab col-lg-9">
            <span class="switch prestashop-switch fixed-width-lg">
                <input type="radio" name="return_info[demo]" id="return_info_1" value="1" {if isset($snsc_config.demo) && $snsc_config.demo}checked{/if} /><label for="return_info_1" class="label-checkbox">{l s='Yes' mod='sonice_suivicolis'}</label>
                <input type="radio" name="return_info[demo]" id="return_info_0" value="0" {if isset($snsc_config.demo) && !$snsc_config.demo}checked{/if} /><label for="return_info_0" class="label-checkbox">{l s='No' mod='sonice_suivicolis'}</label>
                <a class="slide-button btn"></a>
            </span>
        </div>
    </div>

    {include file="$snsc_module_path/views/templates/admin/configuration/validate.tpl"}
</div>