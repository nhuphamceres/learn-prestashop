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


<div id="conf-mapping" style="display: none;">
    <h2>Mapping</h2>

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>
        <div class="margin-form col-lg-9">
            <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                {l s='This tools allow you to map PrestaShop order state and ColiPoste delivery state.' mod='sonice_suivicolis'}
            </div>
        </div>
    </div>


    {foreach $snsc_order_state as $key => $state}
        <div class="form-group select_changer">
            <label class="control-label col-lg-3">{$state['name']|escape:'htmlall':'UTF-8'}</label>
            <div class="margin-form col-lg-9">
                <select name="filtered_states[{$state['id_order_state']|escape:'htmlall':'UTF-8'}][]" class="mapping_selector" multiple>
                    {if isset($snsc_coliposte_state.filtered[$state['id_order_state']]) && is_array($snsc_coliposte_state.filtered[$state['id_order_state']])}
                        {foreach $snsc_coliposte_state.filtered[$state['id_order_state']] as $inovert => $str}
                            <option value="{$inovert|escape:'htmlall':'UTF-8'}" rel="{$state['id_order_state']|escape:'htmlall':'UTF-8'}" selected>
                                {$str|escape:'quotes':'UTF-8'|replace:"\'":"'"}
                            </option>
                        {/foreach}
                    {/if}
                    {foreach $snsc_coliposte_state.available as $inovert => $str}
                        <option value="{$inovert|escape:'htmlall':'UTF-8'}" rel="{$state['id_order_state']|escape:'htmlall':'UTF-8'}">
                            {$str|escape:'quotes':'UTF-8'|replace:"\'":"'"}
                        </option>
                    {/foreach}
                </select>
            </div>
        </div>

        <div class="clear-both-clean"></div>
    {/foreach}

    <hr>

    {if false}
        <div class="form-group">
            <label class="control-label col-lg-3">Overrides</label>
            <div class="margin-form col-lg-9">
                <div>
                    <button type="button" class="button btn btn-primary">{l s='Add a rule' mod='sonice_suivicolis'}</button>
                </div>

                <div class="clearfix"><br></div>

                <div style="border: 1px solid gainsboro; padding: 10px;">
                    <div style="display: inline-block; width: 170px;">{l s='IF STATUS CHANGE TO' mod='sonice_suivicolis'}</div>
                    <select>
                        {foreach $snsc_order_state as $state}
                            <option value="{$state['id_order_state']|intval}">{$state['name']|escape:'htmlall':'UTF-8'}</option>
                        {/foreach}
                    </select>
                    <br>
                    <div style="display: inline-block; width: 170px;">{l s='AND PAYMENT METHOD IS' mod='sonice_suivicolis'}</div>
                    <select>
                        {foreach $snsc_filters.payment_methods as $method}
                            <option value="{$method|escape:'htmlall':'UTF-8'}">{$method|escape:'htmlall':'UTF-8'}</option>
                        {/foreach}
                    </select>
                    <br>
                    <div style="display: inline-block; width: 170px;">{l s='CHANGE ORDER STATUS TO' mod='sonice_suivicolis'}</div>
                    <select>
                        {foreach OrderState::getOrderStates(1) as $state}
                            <option value="{$state.id_order_state|escape:'htmlall':'UTF-8'}">{$state.name|escape:'htmlall':'UTF-8'}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        </div>
    {/if}

    {include file="$snsc_module_path/views/templates/admin/configuration/validate.tpl"}
</div>