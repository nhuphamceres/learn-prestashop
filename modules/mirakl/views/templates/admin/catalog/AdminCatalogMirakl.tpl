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
*
* @package    Mirakl
* @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @author     Olivier B.
* @license    Commercial license
* Support by mail  :  support.mirakl@common-services.com
*}

<div>
    {if (isset($context_key))}
        <input type="hidden" name="context_key" value="{$context_key|escape:'htmlall':'UTF-8'}" />
    {/if}
    <input type="hidden" id="serror" value="{l s='A server-side error has occured. Please contact your server administrator, hostmaster or webmaster' mod='mirakl'}" />
    <input type="hidden" id="sdebug" value="{l s='You should click on this link to submit again in debug mode' mod='mirakl'}" />
    <img style="float:right;margin:30px;max-width:300px;" src="{$images|escape:'htmlall':'UTF-8'}banner.png" style="max-width:400px;"/>
</div>
<div style="clear:both;padding-bottom:20px;"></div>

<fieldset class="panel form-horizontal">
    <h3><img src="{$path|escape:'htmlall':'UTF-8'}logo.png" alt="" style="vertical-align: middle;width:32px;" /> {l s='Export' mod='mirakl'}</h3>
	{* No escape because already made in the PHP *}
    {$menuTab|escape:'quotes':'UTF-8'}
    <div id="tabList" class="panel tabItem">
        {if (isset($tab_update_data))}
            {include file="products_update.tpl" tab_update_data=$tab_update_data}
        {/if}
        {if (isset($tab_update_data))}
            {include file="products_create.tpl" tab_update_data=$tab_create_data}
        {/if}
     </div>
</fieldset>
     