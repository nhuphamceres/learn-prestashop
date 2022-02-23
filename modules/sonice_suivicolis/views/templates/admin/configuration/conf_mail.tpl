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


<div id="conf-mail" style="display: none;">
    <h2>Mails</h2>

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>
        <div class="margin-form col-lg-9">
            <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                {l s='This tools allow you to deal with mail to send to your customers when a status order change.' mod='sonice_suivicolis'}
                <br>
                {l s='You can add your own template in the folder' mod='sonice_suivicolis'}
                <strong class="snsc_black">{$snsc_module_path|escape:'htmlall':'UTF-8'}mail/</strong>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Mail template by state' mod='sonice_suivicolis'}</label>
        <div class="margin-form snsc_typo_conftab col-lg-9">
            {foreach $snsc_order_state as $key => $state}
                <div class="snsc_state_env">
                    <div class="snsc_ps_state float-left">{$state['name']|escape:'htmlall':'UTF-8'}</div>
                    <img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}next.png" class="snsc_next float-left" alt="next">

                    {if !isset($snsc_mail_tpl.default_tpl[ $state['id_order_state'] ])}
                        <select class="float-left" name="filtered_mails[{$state['id_order_state']|escape:'htmlall':'UTF-8'}]" id="available-mails_{$state['id_order_state']|escape:'htmlall':'UTF-8'}" style="margin-left: 10px;"
                                {if !isset($snsc_coliposte_state.filtered[ $state['id_order_state'] ])}
                                    disabled
                                {/if}
                        >
                            <option value="0">-- {l s='Disabled' mod='sonice_suivicolis'}</option>
                            {foreach $snsc_mail_tpl.available as $tpl}
                                {if isset($snsc_mail_tpl.filtered[ $state['id_order_state'] ]) && $snsc_mail_tpl.filtered[ $state['id_order_state'] ] == $tpl}
                                    <option value="{$tpl|escape:'htmlall':'UTF-8'}" selected>{$tpl|escape:'htmlall':'UTF-8'}</option>
                                {else}
                                    <option value="{$tpl|escape:'htmlall':'UTF-8'}">{$tpl|escape:'htmlall':'UTF-8'}</option>
                                {/if}
                            {/foreach}
                        </select>
                        <div class="cleaner"></div>
                        <div>
                            <span class="snsc_options">
                            <input type="checkbox" name="invoice_shipping[{$state['id_order_state']|escape:'htmlall':'UTF-8'}][invoice]" id="send_invoice_{$state['id_order_state']|escape:'htmlall':'UTF-8'}" value="1"
                                   {if isset($snsc_mail_pj[$state['id_order_state']]['invoice'])}checked{/if}> {l s='Send invoice' mod='sonice_suivicolis'}
                                <br>
                            </span>
                            <span class="snsc_options">
                            <input type="checkbox" name="invoice_shipping[{$state['id_order_state']|escape:'htmlall':'UTF-8'}][shipping]" id="send_delivery_{$state['id_order_state']|escape:'htmlall':'UTF-8'}"
                                   {if isset($snsc_mail_pj[$state['id_order_state']]['shipping'])}checked{/if}> {l s='Send delivery slip' mod='sonice_suivicolis'}
                            </span>
                        </div>
                        {if !isset($snsc_mail_tpl.filtered[ $state['id_order_state'] ])}
                            <div class="cleaner so-warning">
                                {l s='You cannot select a mail template for this state because you need to map this state with a Colissimo State.' mod='sonice_suivicolis'}
                            </div>
                        {/if}
                    {else}
                        <input type="text" class="snsc_ps_state" value="{$snsc_mail_tpl.default_tpl[ $state['id_order_state'] ]|escape:'htmlall':'UTF-8'}" style="margin-left: 10px;" disabled>
                        <div class="cleaner so-warning">
                            {l s='You cannot select a mail template for this state because it already has a default template in PrestaShop Settings.' mod='sonice_suivicolis'}
                        </div>
                        <br>
                    {/if}

                    <div class="clear-both-clean"></div>
                </div>
            {/foreach}
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>
        <div class="margin-form snsc_typo_conftab col-lg-9">
            <hr>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Invoice and delivery slip' mod='sonice_suivicolis'}</label>
        <div class="margin-form snsc_typo_conftab col-lg-9">
            <div class="snsc_state_env">
                <div class="snsc_ps_state float-left">{l s='Invoice' mod='sonice_suivicolis'}</div>
                <img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}next.png" class="snsc_next float-left" alt="next">
                <select class="float-left" name="return_info[invoice_tpl]" style="margin-left: 10px;">
                    <option value="0">-- {l s='Disabled' mod='sonice_suivicolis'}</option>
                    {foreach $snsc_mail_tpl.available as $tpl}
                        <option value="{$tpl|escape:'htmlall':'UTF-8'}" {if isset($snsc_config.invoice_tpl) && $snsc_config.invoice_tpl == $tpl}selected{/if}>{$tpl|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
                <div class="clear-both-clean"></div>
            </div>

            <div class="snsc_state_env">
                <div class="snsc_ps_state float-left">{l s='Delivery slip' mod='sonice_suivicolis'}</div>
                <img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}next.png" class="snsc_next float-left" alt="next">
                <select class="float-left" name="return_info[delivery_slip_tpl]" style="margin-left: 10px;">
                    <option value="0">-- {l s='Disabled' mod='sonice_suivicolis'}</option>
                    {foreach $snsc_mail_tpl.available as $tpl}
                        <option value="{$tpl|escape:'htmlall':'UTF-8'}" {if isset($snsc_config.delivery_slip_tpl) && $snsc_config.delivery_slip_tpl == $tpl}selected{/if}>{$tpl|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
                <div class="clear-both-clean"></div>
            </div>

            <div class="cleaner so-warning">
                {l s='Select a template corresponding to the invoice and delivery slip mail.' mod='sonice_suivicolis'}
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>
        <div class="margin-form snsc_typo_conftab col-lg-9">
            <hr>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Incentive Mail' mod='sonice_suivicolis'}</label>
        <div class="margin-form snsc_typo_conftab col-lg-9">
            {l s='days' mod='sonice_suivicolis'} :
            <input type="text" name="return_info[incentive_time]" value="{if isset($snsc_config.incentive_time)}{$snsc_config.incentive_time|escape:'htmlall':'UTF-8'}{/if}" style="width: 20px; margin-left: 13px;"><br>
            {l s='Template' mod='sonice_suivicolis'} :
            <select name="return_info[incentive_mail_tpl]">
                {foreach $snsc_mail_tpl.available as $tpl}
                    <option value="{$tpl|escape:'htmlall':'UTF-8'}" {if isset($snsc_config.incentive_mail_tpl) && ($snsc_config.incentive_mail_tpl === $tpl)}selected{/if}>{$tpl|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select><br>
            {l s='After which state to send the incentive mail' mod='sonice_suivicolis'} :
            <select name="return_info[incentive_state]">
                {foreach $snsc_order_state as $state}
                    <option value="{$state['id_order_state']|escape:'htmlall':'UTF-8'}" {if isset($snsc_config.incentive_state) && ($snsc_config.incentive_state === $state['id_order_state'])}selected{/if}>{$state['name']|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select><br>
            {l s='Rating Service URL' mod='sonice_suivicolis'} :
            <input type="text" class="cron_task" name="return_info[rating_service]" value="{if isset($snsc_config.rating_service) && $snsc_config.rating_service}{$snsc_config.rating_service|escape:'htmlall':'UTF-8'}{/if}" style="width: 70%;"><br>
            {l s='CRON Task' mod='sonice_suivicolis'} :&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" class="cron_task" value="{$snsc_incentive_cron_task|escape:'htmlall':'UTF-8'}" style="width: 70%;">
            <span class="so-warning">
                {l s='If you would like your customers to give their feedback in your rating service, set a number of days to wait before sending a mail to them.' mod='sonice_suivicolis'}
                <br>
                {l s='0 day to cancel this option.' mod='sonice_suivicolis'}
            </span>
        </div>
    </div>

    {include file="$snsc_module_path/views/templates/admin/configuration/validate.tpl"}
</div>