{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Feed.biz
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Feed.biz is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Feed.biz.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 *
 * @package   Amazon Market Place
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * Support by mail:  support.amazon@common-services.com
*}
{if $debug}
    <img style="float:left;margin-right:10px" src="{$images|escape:'htmlall':'UTF-8'}bug.png"
         alt="{l s='Debug Mode' mod='amazon'}"/>
{/if}
<img style="float:right" src="{$images|escape:'htmlall':'UTF-8'}amazon.png"
     alt="{l s='Amazon Market Place' mod='amazon'}"/>
<br/>
<span class="common-services">Amazon Marketplace for Prestashop by <i><a href="http://blog.common-services.com/"
                                                                         title="Common-Services" target="_blank">Common-Services.com</a></i></span>
<br/><br/>
<span class="usage1">{l s='Please read the documentation before usage' mod='amazon'} : </span>
<span>{$documentation|escape:'quotes':'UTF-8'}</span><br />
<span class="usage1">{l s='Support' mod='amazon'} : </span>
<span>{$support|escape:'quotes':'UTF-8'}</span>

<div style="clear:both">&nbsp;</div>
<br/>
<!-- Hidden Configuration Fields -->

<form action="#" id="amazonParams" name="amazonParams" method="POST">
    {foreach from=$tokens key=k item=token}
        <input type="hidden" name="amazon_token[{$k|escape:'htmlall':'UTF-8'}]"
               value="{$token|escape:'htmlall':'UTF-8'}"/>
    {/foreach}

    <input type="hidden" name="token_order" value="{$token_order|escape:'htmlall':'UTF-8'}"/>

    <input type="hidden" id="context_key" value="{$context_key|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" id="instant_token" value="{$instant_token|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" id="orders_url" value="{$orders_url|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" id="import_url" value="{$import_url|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" id="img_loader" value="{$img_loader|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" id="img_loader_small" value="{$img_loader_small|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" name="current_date" value="{$current_date|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" name="start_date" value="{$start_date|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" id="id_lang" name="id_lang" value="{$id_lang|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" id="orders_report_url" name="orders_report_url" value="{$orders_report_url|escape:'htmlall':'UTF-8'}"/>

    <!-- Translated Message for Javascript -->
    <input type="hidden" id="msg_lang" value="{l s='Please choose an Amazon platform' mod='amazon'}"/>
    <input type="hidden" id="msg_select" value="{l s='Please select one or more orders' mod='amazon'}"/>
    <input type="hidden" id="msg_date" value="{l s='You have to choose a date range' mod='amazon'}"/>
    <input type="hidden" id="no_orders" value="{l s='No orders for the selected range' mod='amazon'}"/>
    <input type="hidden"
           value="{l s='A server-side error has occurred. Please contact you server administrator' mod='amazon'}"
           id="serror"/>
    <input type="hidden"
           value="{l s='To obtain support about this error, you should click on this link to submit again in debug mode' mod='amazon'}"
           id="sdebug"/>
</form>

{if isset($shop_warning) && $shop_warning}
    <div class="form-group">
        <div class="margin-form col-lg-12">
            <div class="{$alert_class.warning|escape:'htmlall':'UTF-8'}">
                {$shop_warning|escape:'htmlall':'UTF-8'}
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
{/if}