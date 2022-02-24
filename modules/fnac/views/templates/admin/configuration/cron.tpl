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

<ps-form-group label="">
    <ps-alert-success id="cronjobs_success" style="display:none"></ps-alert-success>
    <ps-alert-error id="cronjobs_error" style="display:none"></ps-alert-error>
</ps-form-group>

<ps-form-group label="">
    <div class="cron-mode" rel="prestashop-cron">

        <img src="{$images_url|escape:'htmlall':'UTF-8'}/prestashop-cronjobs-icon.png"
             title="{l s='Prestashop Cronjobs (Module)' mod='fnac'}" alt="Cronjobs Module"/>
        <h4>{l s='Prestashop Cronjobs (Module)' mod='fnac'}</h4>

        <div style="float:right" class="cron-prestashop">

            {if $fnac_cron.cronjobs.installed}
                <span style="color:green">{l s='Installed' mod='fnac'}</span>
            {elseif $fnac_cron.cronjobs.exists}
                <span style="color:red">{l s='Detected, Not installed' mod='fnac'}</span>
            {else}
                <span style="color:red">{l s='Not detected' mod='fnac'}</span>
            {/if}

        </div>
    </div>    
</ps-form-group>

<div id="prestashop-cron" class="cron-toggle" {if !$fnac_cron.cronjobs.installed} style="display:none" {/if} >
    <ps-form-group label="" class="margin-form">
        {if !$fnac_cron.cronjobs.installed}
            <div>
                <div class="{$alert_class.warning|escape:'htmlall':'UTF-8'}">
                    {l s='Prestashop Cronjobs is not installed.' mod='fnac'}
                    {if !$fnac_cron.cronjobs.exists}(
                        <a href="https://github.com/PrestaShop/cronjobs/archive/master.zip" target="_blank">https://github.com/PrestaShop/cronjobs</a>
                        ){/if}
                    </div>
                </div>
            {else}
                <span class="title">{l s='Those lines will be added in Prestashop Cronjobs module' mod='fnac'}:</span>
                <div id="prestashop-cronjobs-lines">
                    {foreach from=$fnac_cron.country key=iso_code item=country}
                        {if isset($fnac_cron[$iso_code].cron)}
                            {if $fnac_cron.stdtypes}
                                {foreach from=$fnac_cron.stdtypes item=type}
                                    <p>
                                        <b>{$fnac_cron[$type][$iso_code].title|escape:'htmlall':'UTF-8'} ({$iso_code|strtoupper})</b>: {l s='each' mod='fnac'} {$fnac_cron[$type].frequency|escape:'htmlall':'UTF-8'}
                                    {if $fnac_cron[$type].frequency > 1}{l s='hours' mod='fnac'}{else}{l s='hour' mod='fnac'}
                                    {/if}, {l s='url' mod='fnac'}: <a
                                        href="{$fnac_cron[$type][$iso_code].url|escape:'htmlall':'UTF-8'}"
                                        target="_blank">{$fnac_cron[$type][$iso_code].url_short|escape:'htmlall':'UTF-8'}</a>
                                </p>
                            {/foreach}

                        {/if}
                    {/if}
                {/foreach}
            </div>
            <textarea id="prestashop-cronjobs-params" name="prestashop-cronjobs-params" style="display:none">
                {foreach from=$fnac_cron.country key=iso_code item=country}
                    {if isset($fnac_cron[$iso_code].cron)}
                        {if $fnac_cron.stdtypes}
                            {foreach from=$fnac_cron.stdtypes item=type}

                                {$fnac_cron[$type][$iso_code].title|escape:'htmlall':'UTF-8'}|0|{$fnac_cron[$type].frequency|escape:'htmlall':'UTF-8'}|{$fnac_cron[$type][$iso_code].url|escape:'htmlall':'UTF-8'}!

                            {/foreach}
                        {/if}
                    {/if}
                {/foreach}
            </textarea>
            <br/>
            {if $fnac_cron.cronjobs.installed}
                <span style="color:green">{l s='Click on install/update button to auto-configure your Prestashop cronjobs module' mod='fnac'}
                    :</span>
                <button class="button btn btn-default" id="install-cronjobs" style="float:right">
                    <img
                        src="{$images_url|escape:'htmlall':'UTF-8'}plus.png"
                        alt=""/>&nbsp;&nbsp; {l s='Install/Update' mod='fnac'}
                </button>
                <img src="{$images_url|escape:'htmlall':'UTF-8'}loader-connection.gif" alt="" id="cronjob-loader"/>
            {/if}
        {/if}
    </ps-form-group>
</div>


<ps-form-group label="" class="margin-form">
    <div class="cron-mode" rel="manual-cron">
        <img src="{$images_url|escape:'htmlall':'UTF-8'}/terminal.png" title="{l s='Manual Cron URLs' mod='fnac'}"/>
        <h4>{l s='Manual Cron URLs' mod='fnac'}</h4>
    </div>
</ps-form-group>
<!--end Copy  -->
<div id="manual-cron" class="cron-toggle" {if $fnac_cron.cronjobs.installed}style="display: none"{/if}>
    <ps-form-group label="{l s='Cron URLs' mod='fnac'}" class="margin-form">
        {foreach from=$fnac_cron.country key=iso_code item=country}
            <input class="width600" type="text" style="background-color:#EFEFEF;max-width:600px;"
                   value="{$fnac_cron[$iso_code].products_url_cron|escape:'htmlall':'UTF-8'}"/>
            <div class="help-block">
                <p>{$iso_code|strtoupper} - {l s='URL to synchronize products to be used to configure your crontab.' mod='fnac'}</p>
            </div>
            <input class="width600" type="text" style="background-color:#EFEFEF;max-width:600px;"
                   value="{$fnac_cron[$iso_code].orders_url_cron|escape:'htmlall':'UTF-8'}"/>
            <div class="help-block">
                <p>{$iso_code|strtoupper} - {l s='URL to import orders to be used to configure your crontab.' mod='fnac'}</p>
            </div>
        {/foreach}
        <div class="help-block">
            <p>
                <span style="color:brown;font-weight:bold">{l s='Be carefull ! Importing orders by cron can skips somes orders (eg: out of stock), you must check also manually' mod='fnac'}</span>
            </p>
        </div>
    </ps-form-group>
</div>
