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

{if $post_process !== null}
    {if $post_process}
        <ps-alert-success>{l s='Configuration saved with success !' mod='fnac'}</ps-alert-success>
    {else}
        <ps-alert-error>{l s='Oops, something went wrong !' mod='fnac'}</ps-alert-error>
    {/if}
{/if}

<form class="form-horizontal" action="{$request_uri|escape:'htmlall':'UTF-8'}" method="post" id="main_fnac_form">
    <input type="hidden" id="fnac_tools_url" value="{$tools_url|escape:'htmlall':'UTF-8'}"/>

    <ps-tabs position="top">
        <ps-tab label="{$module_name}" active="{($selected_tab == 'fnac') ? 'true' : 'false'}" id="fnac" panel="false"
                icon="icon-AdminParentModules">
            {include file="./fnac.tpl"}
        </ps-tab>
        <ps-tab label="{l s='Informations' mod='fnac'}" active="{($selected_tab == 'informations') ? 'true' : 'false'}"
                id="informations" icon="icon-question">
            <h2>{l s='Configuration check' mod='fnac'}</h2>

            {include file="./informations.tpl"}
        </ps-tab>
        <ps-tab label="{l s='Credentials' mod='fnac'}" active="{($selected_tab == 'credentials') ? 'true' : 'false'}"
                id="credentials" icon="icon-key">
            <h2>{l s='Credentials' mod='fnac'}</h2>

            {include file="./credentials.tpl"}
            <ps-panel-footer>
                <ps-panel-footer-submit title="{l s='Save Configuration' mod='fnac'}" icon="process-icon-save"
                                        direction="right" name="submit" value="credentials"></ps-panel-footer-submit>
            </ps-panel-footer>
        </ps-tab>
        <ps-tab label="{l s='Categories' mod='fnac'}" active="{($selected_tab == 'categories') ? 'true' : 'false'}"
                id="categories" icon="icon-folder">
            <h2>{l s='Categories' mod='fnac'}</h2>
            {include file="./categories.tpl"}
            <ps-panel-footer>
                <ps-panel-footer-submit title="{l s='Save Configuration' mod='fnac'}" icon="process-icon-save"
                                        direction="right" name="submit" value="categories"></ps-panel-footer-submit>
            </ps-panel-footer>
        </ps-tab>
        <ps-tab label="{l s='Transport' mod='fnac'}" active="{($selected_tab == 'transport') ? 'true' : 'false'}"
                id="transport" icon="icon-truck">
            <h2>{l s='Transport' mod='fnac'}</h2>
            {include file="./transport.tpl"}
            <ps-panel-footer>
                <ps-panel-footer-submit title="{l s='Save Configuration' mod='fnac'}" icon="process-icon-save"
                                        direction="right" name="submit" value="transport"></ps-panel-footer-submit>
            </ps-panel-footer>
        </ps-tab>
        <ps-tab label="{l s='Orders' mod='fnac'}" active="{($selected_tab == 'orders') ? 'true' : 'false'}" id="orders"
                icon="icon-calculator">
            <h2>{l s='Orders' mod='fnac'}</h2>
            {include file="./orders.tpl"}
            <ps-panel-footer>
                <ps-panel-footer-submit title="{l s='Save Configuration' mod='fnac'}" icon="process-icon-save"
                                        direction="right" name="submit" value="orders"></ps-panel-footer-submit>
            </ps-panel-footer>
        </ps-tab>
        <ps-tab label="{l s='Settings' mod='fnac'}" active="{($selected_tab == 'settings') ? 'true' : 'false'}"
                id="settings" icon="icon-cog">
            <h2>{l s='Settings' mod='fnac'}</h2>
            {include file="./settings.tpl"}
            <ps-panel-footer>
                <ps-panel-footer-submit title="{l s='Save Configuration' mod='fnac'}" icon="process-icon-save"
                                        direction="right" name="submit" value="settings"></ps-panel-footer-submit>
            </ps-panel-footer>
        </ps-tab>
        <ps-tab label="{l s='Filters' mod='fnac'}" active="{($selected_tab == 'filters') ? 'true' : 'false'}"
                id="filters" icon="icon-filter">
            <h2>{l s='Filters' mod='fnac'}</h2>
            {include file="./filters.tpl"}
            <ps-panel-footer>
                <ps-panel-footer-submit title="{l s='Save Configuration' mod='fnac'}" icon="process-icon-save"
                                        direction="right" name="submit" value="filters"></ps-panel-footer-submit>
            </ps-panel-footer>
        </ps-tab>
        <ps-tab label="{l s='Cron' mod='fnac'}" active="{($selected_tab == 'cron') ? 'true' : 'false'}" id="cron"
                icon="icon-clock-o">
            <h2>{l s='Cron' mod='fnac'}</h2>
            {include file="./cron.tpl"}
        </ps-tab>
    </ps-tabs>
</form>