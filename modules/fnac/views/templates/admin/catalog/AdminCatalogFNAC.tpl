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

<div>
	{if (isset($context_key))}
		<input type="hidden" name="context_key" value="{$context_key|escape:'htmlall':'UTF-8'}"/>
	{/if}
	<input type="hidden" id="fnac_token" value="{$fnac_token|escape:'htmlall':'UTF-8'}"/>
	<input type="hidden" id="serror"
		   value="{l s='A server-side error has occured. Please contact your server administrator, hostmaster or webmaster' mod='fnac'}"/>
	<input type="hidden" id="sdebug" value="{l s='You should click on this link to submit again in debug mode' mod='fnac'}"/>
	<img style="float:right;" src="{$images|escape:'htmlall':'UTF-8'}fnac.png" alt="{l s='FNAC' mod='fnac'}"/>
</div>
<div style="clear:both;padding-bottom:20px;"></div>

<ps-panel {*icon="icon-search"*} img="../img/t/admin.gif" header="{l s='Export' mod='fnac'}" id="pending">
    <p><em>{l s='Please choose the context' mod='fnac'}</em></p>
    <ps-tabs position="top">
        {if (isset($fnac_update))}
            <ps-tab label="{l s='Update/Send Offers' mod='fnac'}" active="{($selected_tab == 'update') ? 'true' : 'false'}" id="update" panel="true" icon="icon-repeat">
                {include file="products_update.tpl" tab_update_data=$fnac_update}
            </ps-tab>
        {/if}
        {if (isset($fnac_masscsv))}
            <ps-tab label="{l s='Generate Mass CSV' mod='fnac'}" active="{($selected_tab == 'masscsv') ? 'true' : 'false'}" id="masscsv" panel="true" icon="icon-table">
                {include file="products_masscsv.tpl" tab_create_data=$fnac_masscsv}
            </ps-tab>
        {/if}
        {if (isset($fnac_csv))}
            <ps-tab label="{l s='Product Creation' mod='fnac'}" active="{($selected_tab == 'csv') ? 'true' : 'false'}" id="csv" panel="true" icon="icon-plus-circle">
                {include file="products_csv.tpl" tab_csv_data=$fnac_csv}
            </ps-tab>
        {/if}
        {if (isset($fnac_debug))}
            <ps-tab label="{l s='Debug Mode' mod='fnac'}" active="{($selected_tab == 'debug') ? 'true' : 'false'}" id="debug" panel="true" icon="icon-bug">
                {include file="products_debug.tpl" tab_debug_data=$fnac_debug}
            </ps-tab>
        {/if}
    </ps-tabs>
</ps-panel>
{*
<fieldset class="panel">
    <legend><img src="{$images|escape:'htmlall':'UTF-8'}admin.gif" alt="" class="middle"/> {l s='Export' mod='fnac'}</legend>
    <p><em>{l s='Please choose the context' mod='fnac'}</em></p>
    <br/>
    <ul id="menuTab">
        <li id="menu-update" class="menuTabButton {$tab_selected_update|escape:'htmlall':'UTF-8'}">
            <span> <img src="{$images|escape:'htmlall':'UTF-8'}update.jpg"
                        alt="{l s='Update/Send Offers' mod='fnac'}"/> {l s='Update/Send Offers' mod='fnac'}</span>
        </li>
        <li id="menu-masscsv" class="menuTabButton {$tab_selected_masscsv|escape:'htmlall':'UTF-8'}">
            <span> <img src="{$images|escape:'htmlall':'UTF-8'}csv.png"
                        alt="{l s='Generate Mass CSV' mod='fnac'}"/> {l s='Generate Mass CSV' mod='fnac'}</span>
        </li>
        {if 1}
            <li id="menu-csv" class="menuTabButton {$tab_selected_masscsv|escape:'htmlall':'UTF-8'}">
                <span> <img src="{$images|escape:'htmlall':'UTF-8'}add.png"
                            alt="{l s='Product Creation' mod='fnac'}"/> {l s='Product Creation' mod='fnac'}</span>
            </li>
        {/if}
        {if 1}
            <li id="menu-debug" class="menuTabButton {$tab_selected_debug|escape:'htmlall':'UTF-8'}">
                <span> <img src="{$images|escape:'htmlall':'UTF-8'}bug.png" alt="{l s='Debug Mode' mod='fnac'}"/>{l s='Debug Mode' mod='fnac'}</span>
            </li>
        {/if}
    </ul>
    <div id="tabList" class="panel">
        {if (isset($fnac_update))}
            {include file="products_update.tpl" tab_update_data=$fnac_update}
        {/if}
        {if (isset($fnac_masscsv))}
            {include file="products_masscsv.tpl" tab_create_data=$fnac_masscsv}
        {/if}
        {if 0}
            {if (isset($fnac_csv))}
                {include file="products_csv.tpl" tab_csv_data=$fnac_csv}
            {/if}
        {/if}
        {if (isset($fnac_debug))}
            {include file="products_debug.tpl" tab_debug_data=$fnac_debug}
        {/if}
    </div>
    <br/>
</fieldset>*}