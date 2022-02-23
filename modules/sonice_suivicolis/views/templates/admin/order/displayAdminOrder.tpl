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

{if !$snsc_ps15x}
    <link type="text/css" rel="stylesheet" href="{$snsc_css_dir|escape:'htmlall':'UTF-8'}order_recap.css">
    <script type="text/javascript" src="{$snsc_js_dir|escape:'htmlall':'UTF-8'}order_recap.js"></script>
{/if}

{if !$snsc_ps16x}<br>{/if}

<div class="row" id="sonice_suivicolis">
    <div>
        <fieldset class="panel">
            <div class="col-lg-6 text-center">
                <img src="{$snsc_module_dir|escape:'htmlall':'UTF-8'}logo.png" alt="SoNice Suivi Colis" style="height: 64px;"><br>
                <br>
                <strong style="font-size: 16px;">SoNice Suivi Colis</strong><br>
                <a href="{$snsc_carrier_url|escape:'htmlall':'UTF-8'|replace:'@':''}{$snsc_tracking_information.shipping_number|escape:'htmlall':'UTF-8'}" target="_blank" style="color: #fb4f14;">
                    <strong>{$snsc_tracking_information.shipping_number|escape:'htmlall':'UTF-8'}</strong>
                </a>
                <img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}loader.gif" width="16px" id="snsc_loader" style="display: none;">
                <img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}mail_green.png" alt="mail" id="snsc_mail_green" style="display: none;">
            </div>
            <div class="col-lg-6">
                <input type="checkbox" name="checkbox[0]" value="{$snsc_tracking_information.shipping_number|escape:'htmlall':'UTF-8'}|{$snsc_tracking_information.id_order|escape:'htmlall':'UTF-8'}" style="display: none;" checked>
                <div>
                    <strong>{l s='Status' mod='sonice_suivicolis'} :</strong> <span id="snsc_status">{$snsc_tracking_information.coliposte_state|escape:'htmlall':'UTF-8'}</span><br>
                    <strong>{l s='Location' mod='sonice_suivicolis'} :</strong> <span id="snsc_location">{$snsc_tracking_information.coliposte_location|escape:'htmlall':'UTF-8'}</span><br>
                    <strong>{l s='Destination' mod='sonice_suivicolis'} :</strong> <span id="snsc_destination">{$snsc_tracking_information.coliposte_destination|escape:'htmlall':'UTF-8'}</span><br>
                </div>
                <div>
                    <strong>{l s='Last CRON update was on' mod='sonice_suivicolis'} :</strong> <span id="snsc_last_update">{$snsc_tracking_information.date_upd|escape:'htmlall':'UTF-8'}</span>
                </div>
                <br><br>
                <div>
                    <button id="snsc_update_tracking" class="button btn btn-primary"><i class="icon-refresh"></i> {l s='Update' mod='sonice_suivicolis'}</button>
                    <button id="snsc_notify_customer" class="button mail_disable btn btn-primary" disabled><i class="icon-envelope"></i> {l s='Notify customer' mod='sonice_suivicolis'}</button>
                </div>
            </div>
            <div class="col-lg-12">
                <div id="snsc_error_display" style="display: none;">
                    <br><br>
                    <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'}">
                        <span id="snsc_error_msg"></span>
                    </div>
                </div>
            </div>
            <!-- URL -->
            <input type="hidden" id="snsc_get_parcel" value="{$snsc_get_parcel|escape:'htmlall':'UTF-8'}">
            <input type="hidden" id="snsc_send_mail" value="{$snsc_send_mail|escape:'htmlall':'UTF-8'}">
            <!-- VARIABLES -->
            <input type="hidden" id="snsc_auto_update" value="{$snsc_auto_update|escape:'htmlall':'UTF-8'}">
        </fieldset>
    </div>
</div>

{literal}
    <script>
        $(document).ready(function () {
            if ($('#myTab').length) {
                setTimeout(function() {
                    $('#myTab').parent().append('<hr>');
                    $('#sonice_suivicolis').appendTo($('#myTab').parent());
                }, 150);
            }
        });
    </script>
{/literal}