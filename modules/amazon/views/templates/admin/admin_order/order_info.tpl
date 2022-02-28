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
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail:  support.amazon@common-services.com
*}

{foreach from=$amazon_order_info key=order_info_field item=order_info_detail}
    {if $order_info_field != "shipping_services"}
        <p>
            {if ($order_info_detail.label)}
                <label class="control-label">{$order_info_detail.label|escape:'html':'UTF-8'}:</label>
                &nbsp;
            {/if}

            {if ($order_info_detail.bold && $order_info_detail.color)}
                <span style="font-weight:bold;color:{$order_info_detail.color|escape:'html':'UTF-8'}">{$order_info_detail.value|escape:'html':'UTF-8'}</span>
            {elseif ($order_info_detail.bold)}
                <span style="font-weight:bold;color:{$order_info_detail.color|escape:'html':'UTF-8'}">{$order_info_detail.value|escape:'html':'UTF-8'}</span>
            {else}
                <span>{$order_info_detail.value|escape:'html':'UTF-8'}</span>
            {/if}
        </p>
    {/if}
{/foreach}

<div class="form-inline" style="margin-bottom: 10px;">
    <div class="form-group">
        <label class="control-label glossary_target" rel="shipping_services" for="order_info_shipping_service">
            <span>{l s='Shipping service' mod='amazon'}:&nbsp;</span>
        </label>
        {* todo: translation *}
        <input type="text" class="form-control" id="order_info_shipping_service"
               title="leave empty to use setting in outgoing mapping"
               placeholder="leave empty to use setting in outgoing mapping"
               value="{$shipping_method|escape:'html':'UTF-8'}" />
    </div>
    <button class="button btn btn-primary" id="save_shipping_service"
            data-href="{$url|cat:'functions/order_details.php'|escape:'html':'UTF-8'}"
            data-id-order="{$id_order|intval}">
        <i class="icon-truck"></i> {l s='Save shipping service' mod='amazon'}
    </button>
</div>
