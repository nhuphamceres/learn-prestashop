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
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail:  support.amazon@common-services.com
*}

{if $debug}
    <p>
        <span class="amazon_label">{l s='Debug Mode' mod='amazon'}:</span>
        <span class="amazon_text" style="color:red;font-weight:bold">{l s='Active' mod='amazon'}</span><br />

        <span class="amazon_label">{l s='Tracking Number' mod='amazon'}:</span>
        <span class="amazon_text">
            {if $tracking_number}<b>{$tracking_number|escape:'html':'UTF-8'}</b>
            {else}<b style="color:red">{l s='None' mod='amazon'}</b>
            {/if}
        </span>
    </p>
    <br />
{/if}

{if (!empty($marketplace_channel))}
    <p>{l s='Fulfillment' mod='amazon'} : 
        <span style="color:red;font-weight:bold">{$marketplace_channel|escape:'html':'UTF-8'}</span>
    </p>
    {if (!empty($fulfillment_center_id))}
        <p>{l s='Fulfillment Center ID' mod='amazon'} : 
            <span style="color:red;font-weight:bold">{$fulfillment_center_id|escape:'html':'UTF-8'}</span>
        </p>
    {/if}
{/if}

<p>
    <label class="control-label">{l s='Marketplace Order ID' mod='amazon'}
        : </label>&nbsp;<b>{$marketplace_order_id|escape:'html':'UTF-8'}</b>
    <input type="button" class="button btn btn-primary" id="amazon_go" style="float: right;"
           value="{l s='Go to Amazon Seller Central' mod='amazon'}" />
</p>

{if isset($amazon_order_info) && is_array($amazon_order_info) && count($amazon_order_info)}
    {include file=$template_path|cat:'order_info.tpl'
    amazon_order_info=$amazon_order_info url=$url id_order=$id_order shipping_method=$shipping_method}
{/if}

{if $vidr.enable && $vidr.preview_invoice_url}
    {include file=$template_path|cat:'vidr_invoice.tpl' preview_invoice_url=$vidr.preview_invoice_url ps_version_is_15=$ps_version_is_15}
{/if}

{* Use enhanced template *}
<div id="amazon-order-prime">
    {include file=$template_path|cat:'prime.tpl' prime=$amazon_order_prime_info}
</div>

{include file=$module_path|cat:'views/templates/admin/debug_details.tpl' amz_detailed_debug=$amz_detailed_debug}

<!-- Html to injection -->
{*todo: Better place for this*}
<a id="amazon-switch" class="amazon-switch-name btn btn-default" href="{$endpoint|escape:'html':'UTF-8'}"
   title="{l s='Switch firstname/lastname' mod='amazon'}">
    <i class="icon-exchange"></i>
    <span class="amazon-switch-text">&nbsp;{l s='Switch firstname/lastname' mod='amazon'}</span>
</a>
<!-- Html to injection -->
