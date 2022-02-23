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
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice@common-services.com
 *}

<!-- URLs -->
<input type="hidden" id="snsc_webservice_url" value="{$snsc_webservice_url|escape:'htmlall':'UTF-8'}">
<input type="hidden" id="snsc_sendmail_url" value="{$snsc_sendmail_url|escape:'htmlall':'UTF-8'}">

<!-- TEXTS -->
<input type="hidden" id="snsc_ws_no_data" value="<span style='color: red;'>{l s='No data received.' mod='sonice_suivicolis'}</span>">
<input type="hidden" id="snsc_ajax_error" value="{l s='An error occured.' mod='sonice_suivicolis'}">
<input type="hidden" id="snsc_pagination_error" value="{l s='An error occured while page changement.' mod='sonice_suivicolis'}">
<input type="hidden" id="snsc_search_error" value="{l s='An error occured while searching your parcel.' mod='sonice_suivicolis'}">
<input type="hidden" id="snsc_ps15x" value="{if $ps15x}1{else}0{/if}">

<!-- DEMO BANNER -->
{if $snsc_demo}
    <div class="alert alert-warning">
        {l s='You are currently in demo mode, you can update parcel tracking and simulate email sending in this mode.' mod='sonice_suivicolis'}
        <br>
        {l s='No mail will be sent even if you click on the corresponding buttons.' mod='sonice_suivicolis'}
    </div>
{/if}

<div class="container-fluid">
    <div class="row well">
        <h3><span class="current_obj">{l s='Orders' mod='sonice_suivicolis'}</span></h3>
        <div class="col-lg-8">&nbsp;</div>
        <div class="col-lg-2">
            <div id="customer_notification" class="box-stats" style="display: none;">
                <span><img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}mailing_list.png" id="img_notification" alt="notification"></span>
                <div>
                    {l s='Notify customers' mod='sonice_suivicolis'}
                    <img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}loader-connection.gif" alt="Loading" class="snsc_loader" style="display: none;">
                </div>
            </div>
        </div>
        <div class="col-lg-2">
            <div id="update_parcel" class="box-stats">
                <span>
                    <img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}update.png" id="img_update" alt="update">
                </span>
                <div>
                    {l s='Update' mod='sonice_suivicolis'}<br>
                    <img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}loader-connection.gif" alt="Loading" class="snsc_loader" style="display: none;">
                </div>
            </div>
        </div>

        <div class="clearfix">&nbsp;</div>
    </div>
</div>

<div id="snsc_error_display" class="{$alert_class.danger|escape:'htmlall':'UTF-8'}" style="display: none;">
    {l s='An unknown error occured.' mod='sonice_suivicolis'}
</div>

{if isset($snsc_debug) && $snsc_debug}
    <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
        <pre>{$sne_sql_query|escape:'htmlall':'UTF-8'}</pre>
    </div>
{/if}

<div style="padding-left: 10px;">
    <input type="checkbox" value="0" id="one_checkbox_to_rule_them_all" checked>
    <label for="one_checkbox_to_rule_them_all">
        <h3 style="padding-left: 20px;"><strong>{l s='Select all' mod='sonice_suivicolis'}</strong></h3>
    </label>
</div>
<div class="clearfix">&nbsp;</div>

<div id="order_list">
    {if !is_array($snsc_orders) || !count($snsc_orders)}
        <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
            {l s='No order to track.' mod='sonice_suivicolis'}
        </div>
    {else}
        {foreach $snsc_orders as $key => $order}
            <div class="panel suivicolis" rel="{$key|intval}">
                <input type="hidden" class="current_state" value="{$order.current_state|intval}">
                <h3>
                    <input type="checkbox" class="snsc_checkbox" name="checkbox[{$key|intval}]" value="{$order['shipping_number']|escape:'htmlall':'UTF-8'}|{$order['id_order']|intval}" class="snsc_checkbox" checked>
                    <strong># <a href="?tab=AdminOrders&id_order={$order['id_order']|intval}&vieworder&token={$snsc_token_order|escape:'htmlall':'UTF-8'}" target="_blank" style="color: inherit;">
                            {$order['id_order']|intval}
                        </a></strong> |
                    Colis n&deg; :
                    <a href="{$order['url']|escape:'quotes':'UTF-8'|replace:'@':''}{$order['shipping_number']|escape:'htmlall':'UTF-8'}" target="_blank">{$order['shipping_number']|escape:'quotes':'UTF-8'}</a> |
                    {$order['firstname']|escape:'quotes':'UTF-8'|replace:'<br>':''} {$order['lastname']|escape:'quotes':'UTF-8'|replace:'<br>':''} |
                    <span class="label color_field" style="background-color: {$order['color']|escape:'quotes':'UTF-8'};">{$order['current_state_name']|escape:'quotes':'UTF-8'}</span> |
                    {$order['carrier_name']|escape:'htmlall':'UTF-8'}
                </h3>
                <table class="table table-responsive" {if !$order['coliposte_state'] || !$order['coliposte_date'] || !$order['coliposte_location']}style="display: none;"{/if}>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Libellé</th>
                            <th>Localisation</th>
                            <th>Destination</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="snsc_date">{$order['coliposte_date']|escape:'htmlall':'UTF-8'|default:''}</td>
                            <td class="snsc_description">{$order['coliposte_state']|escape:'htmlall':'UTF-8'|default:''}</td>
                            <td class="snsc_location">{$order['coliposte_location']|escape:'htmlall':'UTF-8'|default:''}</td>
                            <td class="snsc_destination">{$order['coliposte_destination']|escape:'htmlall':'UTF-8'|default:''}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        {/foreach}
    {/if}
</div>

{* OrderState Templates *}
{foreach $snsc_orderstate_template as $orderstate}
    <span class="label color_field" id="snsc_orderstate_tpl_{$orderstate.id_order_state|escape:'htmlall':'UTF-8'}" style="color: white; background-color: {$orderstate.color|escape:'htmlall':'UTF-8'}; display: none;">{$orderstate.name|escape:'htmlall':'UTF-8'}</span>
{/foreach}

<div id="msg_update_done" class="{$alert_class.success|escape:'htmlall':'UTF-8'}" style="display: none;">
    {l s='Parcel update done without problem' mod='sonice_suivicolis'}
</div>