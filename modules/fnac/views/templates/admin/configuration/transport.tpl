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

<ps-form-group label="{l s='Shipping Methods' mod='fnac'}">
    <select name="carrier_20" style="width:365px;">
        <option value="">{l s='Choose appropriate carrier for : 20 - Normal' mod='fnac'}</option>
        {foreach from=$fnac_transport.carrier_20_options item=option}
            <option value="{$option.value|escape:'htmlall':'UTF-8'}" {$option.selected|escape:'htmlall':'UTF-8'} >{$option.desc|escape:'htmlall':'UTF-8'}</option>
        {/foreach}
    </select><br/>
    <select name="carrier_21" style="width:365px;">
        <option value="">{l s='Choose appropriate carrier for : 21 - Suivi (send with tracking)' mod='fnac'}</option>
        {foreach from=$fnac_transport.carrier_21_options item=option}
            <option value="{$option.value|escape:'htmlall':'UTF-8'}" {$option.selected|escape:'htmlall':'UTF-8'} >{$option.desc|escape:'htmlall':'UTF-8'}</option>
        {/foreach}
    </select><br/>
    <select name="carrier_22" style="width:365px;">
        <option value="">{l s='Choose appropriate carrier for : 22 - Recommande (registered mail)' mod='fnac'}</option>
        {foreach from=$fnac_transport.carrier_22_options item=option}
            <option value="{$option.value|escape:'htmlall':'UTF-8'}" {$option.selected|escape:'htmlall':'UTF-8'} >{$option.desc|escape:'htmlall':'UTF-8'}</option>
        {/foreach}
    </select><br/>
    {* Relais Colis *}
    <select name="carrier_55" style="width:365px;">
        <option value="">{l s='Choose appropriate carrier for : 55 - Relais Colis' mod='fnac'}</option>
        {foreach from=$fnac_transport.carrier_55_options item=option}
            <option value="{$option.value|escape:'htmlall':'UTF-8'}" {$option.selected|escape:'htmlall':'UTF-8'} >{$option.desc|escape:'htmlall':'UTF-8'}</option>
        {/foreach}
    </select>
    <div class="help-block">
        <p>
            {assign var="helpmethods" value={l s='Please select the associated Carrier for each shipping method<br /> Fnac marketplace use only the 3 last one (20, 21 and 22).<br /> You can find more information related to shipping (price and delivery) here (in french)' mod='fnac'}}
            {$helpmethods} :
            <a href="http://www.fnac.com/help/marketplace.asp?NID=-11&RNID=-11#theme04" target="_blank">http://www.fnac.com/help/marketplace.asp?NID=-11&RNID=-11#theme04</a>
            <br/>
        </p>
    </div>
    <ps-panel-divider></ps-panel-divider>
</ps-form-group>

{* Carriers mapping *}
<ps-form-group label="{l s='Carrier Mapping' mod='fnac'}">
    {foreach $fnac_transport.selected_carriers as $carrier}
        <input type="text" value="{$carrier.name|escape:'htmlall':'UTF-8'}" tabindex="-1" style="width:365px; display: inline-block;" readonly>
        <span>
            &nbsp;&nbsp;
            <img src="{$images_url|escape:'htmlall':'UTF-8'}list-next.gif" alt=""/>
            &nbsp;&nbsp;
        </span>
        <select name="carriers_mapping[{$carrier.id_carrier|intval}]" style="display: inline-block; width: 165px;">
            {foreach $fnac_transport.fnac_carriers as $fnac_carrier_code => $fnac_carrier_name}
                <option value="{$fnac_carrier_code|escape:'htmlall':'UTF-8'}"
                        {(is_array($fnac_transport.selected_carriers_mapping) && array_key_exists($carrier.id_carrier, $fnac_transport.selected_carriers_mapping) && $fnac_transport.selected_carriers_mapping[$carrier.id_carrier] == $fnac_carrier_code) ? 'selected' : ''}>
                    {$fnac_carrier_name|escape:'htmlall':'UTF-8'}
                </option>
            {/foreach}
        </select>
        <br>
        <br>
    {/foreach}
    <ps-panel-divider></ps-panel-divider>
</ps-form-group>

{* Time to ship *}
<ps-form-group label="{l s='Time to ship' mod='fnac'}">
    <select name="time_to_ship" style="display: inline-block; width: 365px;">
        <option value=""></option>
        {section name=days loop=50}
            <option value="{$smarty.section.days.iteration}" {if $fnac_transport.time_to_ship|default:'' == $smarty.section.days.iteration}selected{/if}>
                + {$smarty.section.days.iteration}
                {if $smarty.section.days.iteration < 2}
                    {l s='day' mod='fnac'}
                {else}
                    {l s='day(s)' mod='fnac'}
                {/if}
            </option>
        {/section}
    </select>
    <div class="help-block">
        <p>{l s='The default time to ship is 21 days. For some products, this delay is too short. If your account is authorized, you can overload this time.' mod='fnac'}</p>
    </div>
</ps-form-group>

{* Warehouse *}
{if isset($fnac_transport.version_15_shop)}
    {if isset($fnac_transport.ps_asm)}
        <ps-form-group label="{l s='Warehouses' mod='fnac'}">
            <ps-panel-divider></ps-panel-divider>

            <select name="warehouse" style="width:500px;">
                <option value="0">{l s='Choose' mod='fnac'}</option>
                {foreach from=$fnac_transport.asm_options item=option}
                    <option value="{$option.value|escape:'htmlall':'UTF-8'}" {$option.selected|escape:'htmlall':'UTF-8'} >{$option.desc|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>

            <p>{l s='Choose a warehouse for FNAC products pickup (for Advanced Stock Management)' mod='fnac'}</p>
        </ps-form-group>
    {/if}
{/if}
