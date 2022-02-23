{**
* NOTICE OF LICENSE
*
* This source file is subject to a commercial license from Common-Services Co., Ltd.
* Use, copy, modification or distribution of this source file without written
* license agreement from the SARL SMC is strictly forbidden.
* In order to obtain a license, please contact us: support.mondialrelay@common-services.com
* ...........................................................................
* INFORMATION SUR LA LICENCE D'UTILISATION
*
* L'utilisation de ce fichier source est soumise a une licence commerciale
* concedee par la societe Common-Services Co., Ltd.
* Toute utilisation, reproduction, modification ou distribution du present
* fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
* expressement interdite.
* Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: support.mondialrelay@common-services.com
* ...........................................................................
*
* @package   sonice_suivicolis
* @author    debuss-a
* @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @license   Commercial license
* Support by mail  :  support.sonice_suivicolis@common-services.com
*}

<ps-input-text label="{l s='Login' mod='sonice_suivicolis'}" name="return_info[login]" value="{$snsc_config.login|default:''}" fixed-width="xl"></ps-input-text>

<ps-password label="{l s='Password' mod='sonice_suivicolis'}" name="return_info[pwd]" value="{$snsc_config.pwd|default:''}"  fixed-width="xl"></ps-password>

<input type="hidden" id="empty_field" value="{l s='You must fill the login and password field !' mod='sonice_suivicolis'}">

<ps-form-group label="">
    <input type="button" class="button snsc_button btn btn-default" id="login_checker" value="{l s='Check your login' mod='sonice_suivicolis'}">
    &nbsp;<img src="{$snsc_img_dir}loader.gif" alt="loader" id="etg_loader" style="width: 20px; display: none;">
</ps-form-group>

<ps-form-group label="">
    <ps-alert-success id="login_ok" style="display: none;">{l s='Your login is correct !' mod='sonice_suivicolis'}</ps-alert-success>
    <ps-alert-error id="login_not_ok" style="display:none">
        {l s='Your login is incorrect :' mod='sonice_suivicolis'}<br>
        <strong>ID :</strong> <span id="errorID"></span><br>
        <strong>Message :</strong> <span id="error"></span>
        <span id="error_request"></span>
        <span id="error_response"></span>
        <span id="error_output"></span>
    </ps-alert-error>
</ps-form-group>

<ps-panel-divider></ps-panel-divider>

<ps-switch label="{l s='Debug Mode' mod='sonice_suivicolis'}" name="return_info[debug]"  yes="{l s='Yes' mod='sonice_suivicolis'}" no="{l s='No' mod='sonice_suivicolis'}" active="{$snsc_config.debug|default:0}"></ps-switch>
<ps-switch label="{l s='Test Mode' mod='sonice_suivicolis'}" name="return_info[demo]"  yes="{l s='Yes' mod='sonice_suivicolis'}" no="{l s='No' mod='sonice_suivicolis'}" active="{$snsc_config.demo|default:0}"></ps-switch>