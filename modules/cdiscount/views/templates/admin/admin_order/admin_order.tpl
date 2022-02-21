{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Common-Services Co., Ltd. is strictly forbidden.
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
 * @package   CDiscount
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail:  support.cdiscount@common-services.com
*}

<script type="text/javascript" src="{$js_url|escape:'quotes':'UTF-8'}"></script>
<link rel="stylesheet" type="text/css" href="{$css_url|escape:'quotes':'UTF-8'}">

<div id="cdiscount-order-ps16">
    <form name="cdUpdateOrder" id="cdUpdateOrder" method="post"/>
    <input type="hidden" name="cd-order-id" value="{$marketplace_order_id|escape:'htmlall':'UTF-8'}"/>
    <br>
    <fieldset class="panel">

        <legend><img src="{$images_url|escape:'htmlall':'UTF-8'}logo.gif"
                     alt="{l s='CDiscount' mod='cdiscount'}"/>&nbsp;{l s='CDiscount' mod='cdiscount'}</legend>

        {if $debug}
            <span class="cd_label">{l s='Debug Mode' mod='cdiscount'}:</span>
            <span class="cd_text" style="color:red;font-weight:bold">{l s='Active' mod='cdiscount'}</span>
            <br/>
            <span class="cd_label">{l s='Bulk Mode' mod='cdiscount'}:</span>
            <span class="cd_text"><b>{if $bulk_mode}{l s='Active' mod='cdiscount'}{else}{l s='Inactive' mod='cdiscount'}{/if}</b></span>
            <br/>
            <span class="cd_label">{l s='Tracking Number' mod='cdiscount'}:</span>
            <span class="cd_text">{if $tracking_number}<b>{$tracking_number|escape:'htmlall':'UTF-8'}</b>{else}<b
                        style="color:red">{l s='None' mod='cdiscount'}</b>{/if}</span>
            <br/>
        {/if}

        <span class="cd_label">{l s='Order ID' mod='cdiscount'}:</span><span class="cd_text">{$marketplace_order_id|escape:'htmlall':'UTF-8'}</span>
        <br/>



        <span class="cd_label">{l s='Go To' mod='cdiscount'}</span>
            <span class="cd_text">
                <a href="{$orders_url|escape:'htmlall':'UTF-8'}" title="{l s='Cdiscount Marketplace' mod='cdiscount'}" target="_blank">
                    <img src="{$images_url|escape:'htmlall':'UTF-8'}clemarche_100px.png" style="position:relative;vertical-align: middle;" alt="{l s='C le Marche' mod='cdiscount'}"/>
                </a>
        </span>
        <br/>

        {if isset($optionnal_carrier) && $optionnal_carrier}
            <span class="cd_label">{l s='Delivery Point' mod='cdiscount'}:</span>
            <span class="cd_text">{$optionnal_carrier_info.name|escape:'htmlall':'UTF-8'}
                (#{$optionnal_carrier_info.pickup_id|escape:'htmlall':'UTF-8'})</span>
            <br/>
        {/if}

        {if isset($order_ext) && is_array($order_ext) && count($order_ext)}
            {if $order_ext.show_channel}
                <span class="cd_label">{l s='Channel' mod='cdiscount'}:</span>
                <span class="cd_text"><em style="color:{$order_ext.channel_color|escape:'htmlall':'UTF-8'}">{$order_ext.channel_name|escape:'htmlall':'UTF-8'}</em></span>
                <br/>
            {/if}
            {if $order_ext.clogistique}
                <span class="cd_label">{l s='C Logistique' mod='cdiscount'}:</span>
                <span class="cd_text"><b style="color:red">{l s='Yes' mod='cdiscount'}</b></span>
                <br/>
            {/if}

            {foreach from=$order_ext.the_dates item=the_date}
                {if $the_date.value}
                    <p>
                        <label class="control-label">{$the_date.label|escape:'html':'UTF-8'}:</label>&nbsp;
                        <span style="font-weight:bold;color:{$the_date.color|escape:'html':'UTF-8'}">{$the_date.value|escape:'html':'UTF-8'}</span>
                    </p>
                {/if}
            {/foreach}
        {/if}

    </fieldset>
    </form>
</div>
