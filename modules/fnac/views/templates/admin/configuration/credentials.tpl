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
* @author    Alexandre Debusschère
* @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @license   Commercial license
* Support by mail  :  support.fnac@common-services.com
*}

{*<ps-form-group label="">
<ps-alert-success id="login_ok" style="display: none;">{l s='Your login is correct !' mod='fnac'}</ps-alert-success>
<ps-alert-error id="login_not_ok" style="display:none">{l s='Your login is incorrect !' mod='fnac'}</ps-alert-error>
<ps-alert-error id="login_error" style="display:none"></ps-alert-error>

<ps-panel-divider></ps-panel-divider>
</ps-form-group>*}

<ps-form-group label="">
    <ul class="nav nav-pills">
        <li class="fnac-country-tab active" id="france">
            <a href="#">
                <img src="{$images_url|escape:'htmlall':'UTF-8'}flag_fr_64px.png" width="40px" height="40px" alt="France"/>
                <div>FNAC France</div>
            </a>
        </li>
        <li class="fnac-country-tab" id="spain">
            <a href="#">
                <img src="{$images_url|escape:'htmlall':'UTF-8'}flag_es_64px.png" width="40px" height="40px" alt="Spain"/>
                <div>FNAC Spain</div>
            </a>
        </li>
        <li class="fnac-country-tab" id="portugal">
            <a href="#">
                <img src="{$images_url|escape:'htmlall':'UTF-8'}flag_pt_64px.png" width="40px" height="40px" alt="Portugal"/>
                <div>FNAC Portugal</div>
            </a>
        </li>
        <li class="fnac-country-tab" id="belgium">
            <a href="#">
                <img src="{$images_url|escape:'htmlall':'UTF-8'}flag_be_64px.png" width="40px" height="40px" alt="Belgium"/>
                <div>FNAC Belgique</div>
            </a>
        </li>
    </ul>
</ps-form-group>

<div class="fnac-country-container" rel="france">
    <ps-input-text label="* {l s='Partner ID' mod='fnac'}" id="login" name="partner_id[fr]" value="{$fnac_cretentials.fr.partner_id|escape:'htmlall':'UTF-8'}" fixed-width="xxxl"></ps-input-text>
    <ps-input-text label="* {l s='Shop ID' mod='fnac'}" id="login" name="shop_id[fr]" value="{$fnac_cretentials.fr.shop_id|escape:'htmlall':'UTF-8'}" fixed-width="xxxl"></ps-input-text>
    <ps-input-text label="* {l s='API Key' mod='fnac'}" id="login" name="api_key[fr]" value="{$fnac_cretentials.fr.api_key|escape:'htmlall':'UTF-8'}" fixed-width="xxxl"></ps-input-text>
    <ps-input-text label="* {l s='Web Service URL' mod='fnac'}" id="fr" name="api_url[fr]" value="{$fnac_cretentials.fr.api_url|escape:'htmlall':'UTF-8'}" fixed-width="xxxl"
                   help="{l s='These informations are provided by contract from the FNAC Customers Services' mod='fnac'}"></ps-input-text>
</div>

<div class="fnac-country-container" rel="spain" style="display:none">
    <ps-input-text label="* {l s='Partner ID' mod='fnac'}" id="login" name="partner_id[es]" value="{$fnac_cretentials.es.partner_id|escape:'htmlall':'UTF-8'}" fixed-width="xxxl"></ps-input-text>
    <ps-input-text label="* {l s='Shop ID' mod='fnac'}" id="login" name="shop_id[es]" value="{$fnac_cretentials.es.shop_id|escape:'htmlall':'UTF-8'}" fixed-width="xxxl"></ps-input-text>
    <ps-input-text label="* {l s='API Key' mod='fnac'}" id="login" name="api_key[es]" value="{$fnac_cretentials.es.api_key|escape:'htmlall':'UTF-8'}" fixed-width="xxxl"></ps-input-text>
    <ps-input-text label="* {l s='Web Service URL' mod='fnac'}" id="es" name="api_url[es]" value="{$fnac_cretentials.es.api_url|escape:'htmlall':'UTF-8'}" fixed-width="xxxl"
                   help="{l s='These informations are provided by contract from the FNAC Customers Services' mod='fnac'}"></ps-input-text>
</div>

<div class="fnac-country-container" rel="portugal" style="display:none">
    <ps-input-text label="* {l s='Partner ID' mod='fnac'}" id="login" name="partner_id[pt]" value="{$fnac_cretentials.pt.partner_id|escape:'htmlall':'UTF-8'}" fixed-width="xxxl"></ps-input-text>
    <ps-input-text label="* {l s='Shop ID' mod='fnac'}" id="login" name="shop_id[pt]" value="{$fnac_cretentials.pt.shop_id|escape:'htmlall':'UTF-8'}" fixed-width="xxxl"></ps-input-text>
    <ps-input-text label="* {l s='API Key' mod='fnac'}" id="login" name="api_key[pt]" value="{$fnac_cretentials.pt.api_key|escape:'htmlall':'UTF-8'}" fixed-width="xxxl"></ps-input-text>
    <ps-input-text label="* {l s='Web Service URL' mod='fnac'}" id="es" name="api_url[pt]" value="{$fnac_cretentials.pt.api_url|escape:'htmlall':'UTF-8'}" fixed-width="xxxl"
                   help="{l s='These informations are provided by contract from the FNAC Customers Services' mod='fnac'}"></ps-input-text>
</div>

<div class="fnac-country-container" rel="belgium" style="display:none">
    <ps-input-text label="* {l s='Partner ID' mod='fnac'}" id="login" name="partner_id[be]" value="{$fnac_cretentials.be.partner_id|escape:'htmlall':'UTF-8'}" fixed-width="xxxl"></ps-input-text>
    <ps-input-text label="* {l s='Shop ID' mod='fnac'}" id="login" name="shop_id[be]" value="{$fnac_cretentials.be.shop_id|escape:'htmlall':'UTF-8'}" fixed-width="xxxl"></ps-input-text>
    <ps-input-text label="* {l s='API Key' mod='fnac'}" id="login" name="api_key[be]" value="{$fnac_cretentials.be.api_key|escape:'htmlall':'UTF-8'}" fixed-width="xxxl"></ps-input-text>
    <ps-input-text label="* {l s='Web Service URL' mod='fnac'}" id="es" name="api_url[be]" value="{$fnac_cretentials.be.api_url|escape:'htmlall':'UTF-8'}" fixed-width="xxxl"
                   help="{l s='These informations are provided by contract from the FNAC Customers Services' mod='fnac'}"></ps-input-text>
</div>

<ps-switch label="{l s='Debug Mode' mod='fnac'}" name="fnac_debug"  yes="{l s='Yes' mod='fnac'}" no="{l s='No' mod='fnac'}"
           active="{($fnac_cretentials.debug_checked|default:0) ? 'true' : 'false'}"
           help="{l s='Debug mode. Enable traces for debugging and developpment purpose.' mod='fnac'}"></ps-switch>