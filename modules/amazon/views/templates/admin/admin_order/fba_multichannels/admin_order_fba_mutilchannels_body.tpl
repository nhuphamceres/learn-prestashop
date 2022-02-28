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

<img src="{$images_url|escape:'quotes':'UTF-8'}green-loader.gif" alt="{l s='Loading' mod='amazon'}"
     id="order-fba-loader"/>

<p>{l s='Fulfillment' mod='amazon'} : 
    <span style="color:red;font-weight:bold">{l s='Multi-Channel Order / Fulfilled By Amazon' mod='amazon'}</span>
</p>

<p>{l s='Current Status' mod='amazon'} :<span>{$marketplace_status|escape:'quotes':'UTF-8'}</span></p>

<p id="order-fba-ajax-error">{l s='Unexpected error while fetching data from Amazon' mod='amazon'}</p>

<p id="order-fba-error-message"></p>

<p id="order-fba-message"></p>

<hr id="order-fba-detail-spacer"/>
<table id="order-fba-detail" style="display:none;border-spacing: 5px;">
    <tr>
        <td style="text-align:right;">{l s='Order ID' mod='amazon'}&nbsp;</td>
        <td rel="DisplayableOrderId" style="font-weight:bold"></td>
    </tr>
    <tr>
        <td style="text-align:right;">{l s='Order Status' mod='amazon'}&nbsp;</td>
        <td rel="FulfillmentOrderStatus" style="font-weight:bold"></td>
    </tr>
    <tr>
        <td style="text-align:right;">{l s='Received On' mod='amazon'}&nbsp;</td>
        <td rel="ReceivedDateTime"></td>
    </tr>
    <tr>
        <td style="text-align:right;">{l s='Last Update' mod='amazon'}&nbsp;</td>
        <td rel="StatusUpdatedDateTime"></td>
    </tr>
    <tr>
        <td style="text-align:right;">{l s='Fulfillment Method' mod='amazon'}&nbsp;</td>
        <td rel="FulfillmentMethod"></td>
    </tr>
    <tr>
        <td style="text-align:right;">{l s='Items' mod='amazon'}&nbsp;</td>
        <td rel="Items" style="color:navy"></td>
    </tr>
    <tr>
        <td style="text-align:right;">{l s='Shipping Time Category' mod='amazon'}&nbsp;</td>
        <td rel="ShippingSpeedCategory"></td>
    </tr>
    <tr>
        <td style="text-align:right;color:green;">{l s='Estimated Shipping Date' mod='amazon'}&nbsp;</td>
        <td rel="EstimatedShipDateTime" style="color:green;"></td>
    </tr>
    <tr>
        <td style="text-align:right;color:green;">{l s='Estimated Arrival Date' mod='amazon'}&nbsp;</td>
        <td rel="EstimatedArrivalDateTime" style="font-weight:bold;color:green"></td>
    </tr>
</table>

<hr id="order-fba-detail-spacer2"/>
<input type="button" class="button" style="float:left" id="amazon_cancel_fba"
       value="{l s='Cancel Shipping' mod='amazon'}"/>
<input type="button" class="button" style="float:right" id="amazon_get_details"
       value="{l s='Get Details' mod='amazon'}"/>

{if $marketplace_canceled}
    <p id="order-fba-canceled" style="font-weight:bold;">
        <img src="{$images_url|escape:'quotes':'UTF-8'}cross.png"
             alt="{l s='Canceled' mod='amazon'}"/>&nbsp;&nbsp;{l s='This FBA shipping has been canceled' mod='amazon'}
    </p>
{/if}

{if $debug}
    <p style="clear:both">
        <span class="amazon_label">{l s='Debug Mode' mod='amazon'}:</span>
        <span class="amazon_text" style="color:red;font-weight:bold">{l s='Active' mod='amazon'}</span><br/>
        <span>Debug:</span>
    <pre id="amazon-output">&nbsp;</pre>
    </p>
    <br/>
{else}
    <pre id="amazon-output" style="display:none">&nbsp;</pre>
{/if}
