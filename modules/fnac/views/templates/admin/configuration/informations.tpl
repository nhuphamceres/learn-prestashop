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
* @author    Alexandre D. & Olivier B.
* @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @license   Commercial license
* Support by mail  :  contact@common-services.com
*}

<ps-form-group label="{l s='PHP Settings' mod='fnac'}">
    {if !$fnac_informations.php_info_ok}
        {foreach from=$fnac_informations.php_infos item=php_info}
            <ps-alert-hint>
                {$php_info.message|escape:'htmlall':'UTF-8'}
                {if isset($php_info.link)}
                    <br/>
                    <span class="fnac-info-link">{l s='Please read more about it on:' mod='fnac'}: <a
                            href="{$php_info.link|escape:'htmlall':'UTF-8'}"
                            target="_blank">{$php_info.link|escape:'htmlall':'UTF-8'}</a></span>
                    {/if}
            </ps-alert-hint>
        {/foreach}
    {else}
        <ps-alert-success>{l s='Your PHP configuration for the module has been checked and passed successfully...' mod='fnac'}</ps-alert-success>
        {/if}
</ps-form-group>

<ps-form-group label="{l s='Prestashop Settings' mod='fnac'}">
    {if !$fnac_informations.prestashop_info_ok}
        {foreach from=$fnac_informations.prestashop_infos item=prestashop_info}
            <ps-alert-hint>
                <span>{$prestashop_info.message|escape:'htmlall':'UTF-8'}</span>
                {if isset($prestashop_info.link)}
                    <br/>
                    <span class="fnac-info-link">{l s='Please read more about it on:' mod='fnac'}: <a
                            href="{$prestashop_info.link|escape:'htmlall':'UTF-8'}"
                            target="_blank">{$prestashop_info.link|escape:'htmlall':'UTF-8'}</a></span>
                    {/if}
            </ps-alert-hint>
        {/foreach}
    {else}
        <ps-alert-success>{l s='Your Prestashop configuration for the module has been checked and passed successfully...' mod='fnac'}</ps-alert-success>
        {/if}
</ps-form-group>

<ps-form-group label="{l s='Additionnal Support Informations' mod='fnac'}">
    <input type="button" class="button btn" id="support-informations-prestashop" value="{l s='Prestashop Info' mod='fnac'}"
           rel="{$fnac_informations.support_informations_url|escape:'htmlall':'UTF-8'}&action=prestashop-info"/>&nbsp;&nbsp;
    <input type="button" class="button btn" id="support-informations-php" value="{l s='PHP Info' mod='fnac'}"
           rel="{$fnac_informations.support_informations_url|escape:'htmlall':'UTF-8'}&action=php-info"/>&nbsp;&nbsp;
    <img style="display:none" src="{$fnac_informations.images|escape:'htmlall':'UTF-8'}loader-connection.gif"
         alt="{l s='Support Informations' mod='fnac'}" class="support-informations-loader"/><br/><br/>

    <div id="support-informations-content">
    </div>
</ps-form-group>