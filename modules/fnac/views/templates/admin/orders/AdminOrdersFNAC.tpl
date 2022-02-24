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

<img src="{$path|escape:'htmlall':'UTF-8'}/views/img/fnac.png" alt="" style="float:right"/>
<div style="clear:both;padding-bottom:20px;"></div>
{if (isset($context_key))}
    <input type="hidden" name="context_key" value="{$context_key|escape:'htmlall':'UTF-8'}"/>
{/if}

<ps-tabs position="top">
    {if 0}
        {if (isset($fnac_accept))}
            <ps-tab label="{l s='Accept Orders' mod='fnac'}" active="{($selected_tab == 'accept') ? 'true' : 'false'}" id="accept" panel="false" icon="icon-check-circle">
                {include file="orders_accept.tpl" tab_accept=$fnac_accept}
            </ps-tab>
        {/if}
    {/if}
    {*if (isset($fnac_import))*}
        <ps-tab label="{l s='Import Orders' mod='fnac'}" active="{($selected_tab == 'import') ? 'true' : 'false'}" id="import" panel="false" icon="icon-cloud-download">
            {if (isset($fnac_import))}
                <h2>{l s='Import Orders' mod='fnac'}</h2>
                {include file="orders_import.tpl" tab_import=$fnac_import}
            {/if}
        </ps-tab>
    {*/if*}
    {if isset($fnac_debug) && $fnac_debug}
        <ps-tab label="{l s='Debug Mode' mod='fnac'}" active="{($selected_tab == 'debug') ? 'true' : 'false'}" id="debug" panel="false" icon="icon-bug">
            <h2>{l s='Debug Mode' mod='fnac'}</h2>
            {include file="orders_debug.tpl" tab_debug=$fnac_debug}
        </ps-tab>
    {/if}
</ps-tabs>

{*<ul id="menuTab">
    {if 1}
        <li id="menu-accept" class="menuTabButton {$tab_selected_accept|escape:'htmlall':'UTF-8'}">
            <span> <img src="{$images|escape:'htmlall':'UTF-8'}accept.png" alt="Accept Orders"/> {l s='Accept Orders' mod='fnac'}</span>
        </li>
    {/if}
    <li id="menu-import" class="menuTabButton {$tab_selected_import|escape:'htmlall':'UTF-8'}">
        <span> <img src="{$images|escape:'htmlall':'UTF-8'}orders.png"
                    alt="{l s='Import Orders' mod='fnac'}"/> {l s='Import Orders' mod='fnac'}</span>
    </li>
    {if isset($debug)}
        <li id="menu-debug" class="menuTabButton {$tab_selected_debug|escape:'htmlall':'UTF-8'}">
            <span> <img src="{$images|escape:'htmlall':'UTF-8'}bug.png" alt="{l s='Debug Mode' mod='fnac'}"/> {l s='Debug Mode' mod='fnac'}</span>
        </li>
    {/if}
</ul>
<div id="tabList">
    {if 0}
        {if (isset($fnac_accept))}
            {include file="orders_accept.tpl" tab_accept=$fnac_accept}
        {/if}
    {/if}
    {if (isset($fnac_import))}
        {include file="orders_import.tpl" tab_import=$fnac_import}
    {/if}
    {if isset($fnac_debug) && $fnac_debug}
        {include file="orders_debug.tpl" tab_debug=$fnac_debug}
    {/if}
</div>
<br/>
<div id="console" {$console_display|escape:'quotes':'UTF-8'}></div>*}