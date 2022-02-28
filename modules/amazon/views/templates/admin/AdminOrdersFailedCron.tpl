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
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * Support by mail:  support.amazon@common-services.com
*}

<div style="padding-bottom: 5px;">
    <span id="cron_failed_orders_handler" class="dropup" style="cursor: pointer; margin-top: auto;">
        <strong>{l s='Cron failed orders' mod='amazon'} </strong><span class="caret"></span>
    </span>
    <div id="remove-failed-orders" class="button-proceed btn" style="margin-top: auto; margin-bottom: 4px; padding: 0px;">
        <img src="{$images|escape:'quotes':'UTF-8'}trash-big.png" alt="{l s='Remove failed orders' mod='amazon'}" style="width: 15px; padding-bottom: 5px;">
        <span>{l s='Clear' mod='amazon'}</span>
    </div>
</div>

<div class="conf" id="amazon-remove-failed-orders-loader" style="display:none;">
    <img src="{$images|escape:'quotes':'UTF-8'}loading.gif" alt="" style="margin-left: 50%;"/>
</div>
<div class="{$alert_class.warning|escape:'quotes':'UTF-8'}" id="amazon-remove-failed-orders-warning" style="display: none; margin-top: 20px;"></div>
<div class="{$alert_class.danger|escape:'quotes':'UTF-8'}" id="amazon-remove-failed-orders-error" style="display: none; margin-top: 20px;"></div>
<div class="{$alert_class.success|escape:'quotes':'UTF-8'}" id="amazon-remove-failed-orders-success" style="display: none; margin-top: 20px;"></div>

<table class="table table-hover" id="cron_failed_orders" style="width: 100%; margin-bottom:30px; display:none;">
    <thead>
    <tr class="active">
        <th></th>
        <th>Order</th>
        <th>Date</th>
        <th style="text-align: right">Attempt</th>
        <th>Reason</th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$cron_failed_orders item=failed_order}
        <tr class="row_hover">
            <td>
                {if $failed_order->getMarketplaceCode()}
                    <img src="{$images|cat:'geo_flags/':$failed_order->getMarketplaceCode():'.jpg'}" alt="Flag" />
                {/if}
            </td>
            <td>{$failed_order->mpOrderId|escape:'htmlall':'UTF-8'}</td>
            <td>{$failed_order->purchaseDate|escape:'htmlall':'UTF-8'}</td>
            <td style="text-align: right">{$failed_order->attempt|intval}</td>
            <td>{$failed_order->reason|escape:'htmlall':'UTF-8'}</td>
        </tr>
    {/foreach}
    </tbody>
</table>
