{**
* NOTICE OF LICENSE
*
* This source file is subject to a commercial license from Common-Services Co., Ltd.
* Use, copy, modification or distribution of this source file without written
* license agreement from the SARL SMC is strictly forbidden.
* In order to obtain a license, please contact us: support.mondialrelay@common-services.com
* ...........................................................................
* INFORMATION SUR LA LICENCE D'UTILISATION
*
* L'utilisation de ce fichier source est soumise a une licence commerciale
* concedee par la societe Common-Services Co., Ltd.
* Toute utilisation, reproduction, modification ou distribution du present
* fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
* expressement interdite.
* Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: support.mondialrelay@common-services.com
* ...........................................................................
*
* @package   sonice_suivicolis
* @author    debuss-a
* @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @license   Commercial license
* Support by mail  :  support.sonice_suivicolis@common-services.com
*}

<div id="conf-overrides" style="display: none;">
    <h2>Overrides</h2>

    <div class="form-group">
        <label class="control-label col-lg-1">&nbsp;</label>
        <div class="margin-form col-lg-11">
            <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                {l s='This tools allow you to override the default module behavior' mod='sonice_suivicolis'}
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-1">{l s='Rules' mod='sonice_suivicolis'}</label>
        <div class="margin-form col-lg-11">

            <div class="margin-form col-lg-12">
                <div style="display: inline-block;width: 35px;">{l s='ON' mod='sonice_suivicolis'}</div>
                <select>
                    <option value="status_update" selected>{l s='Status update' mod='sonice_suivicolis'}</option>
                </select>
            </div>

            <div class="margin-form col-lg-12">
                <div style="display: inline-block;width: 35px;">{l s='IF' mod='sonice_suivicolis'}</div>
                <select>
                    {*<option value="order_status">{l s='Order Status' mod='sonice_suivicolis'}</option>*}
                    {*<option value="order_id">{l s='ID Order' mod='sonice_suivicolis'}</option>*}
                    <option value="colissimo_status">{l s='Colissimo Status' mod='sonice_suivicolis'}</option>
                </select>
                <select style="width: 50px;">
                    <option value="is_equal_to">=</option>
                    <option value="is_not_equal_to">!=</option>
                    {*<option value="is_lower_than"><</option>*}
                    {*<option value="is_lower_than_or_equal"><=</option>*}
                    {*<option value="is_upper_than">></option>*}
                    {*<option value="is_upper_than_or_equal"><=</option>*}
                </select>
                <select>
                    {foreach $snsc_coliposte_state.all as $inovert => $str}
                        <option value="{$inovert|escape:'htmlall':'UTF-8'}">
                            {$str|escape:'quotes':'UTF-8'|replace:"\'":"'"}
                        </option>
                    {/foreach}
                </select>
            </div>

            <div class="margin-form col-lg-12">
                <div style="display: inline-block;width: 35px;">{l s='AND' mod='sonice_suivicolis'}</div>
                <select>
                    <option value="order_status">{l s='Order Status' mod='sonice_suivicolis'}</option>
                    <option value="order_id">{l s='ID Order' mod='sonice_suivicolis'}</option>
                    <option value="colissimo_status">{l s='Colissimo Status' mod='sonice_suivicolis'}</option>
                </select>
                <select style="width: 50px;">
                    {foreach $snsc_coliposte_state.filtered[$state['id_order_state']] as $inovert => $str}
                        <option value="{$inovert|escape:'htmlall':'UTF-8'}" rel="{$state['id_order_state']|escape:'htmlall':'UTF-8'}" selected>
                            {$str|escape:'quotes':'UTF-8'|replace:"\'":"'"}
                        </option>
                    {/foreach}
                </select>
                <input type="text">
            </div>

            <div class="margin-form col-lg-12">
                <div style="display: inline-block;width: 35px;">{l s='THEN' mod='sonice_suivicolis'}</div>
                <select>
                    <option value="order_status">{l s='Set Order Status' mod='sonice_suivicolis'}</option>
                </select>
                <div style="display: inline-block;width: 50px; text-align: center;">{l s='TO' mod='sonice_suivicolis'}</div>
                <select>
                    {foreach OrderState::getOrderStates(1) as $key => $state}
                        <option value="{$state['id_order_state']|intval}">{$state['name']|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>



            <div class="clearfix">&nbsp;</div>
            <br>
            <br>
            <br>
            <br>
            <table class="table">
                <thead>
                    <tr class="active">
                        <th>{l s='Trigger' mod='sonice_suivicolis'}</th>
                        <th>{l s='Conditions' mod='sonice_suivicolis'}</th>
                        <th>{l s='Actions' mod='sonice_suivicolis'}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            {l s='ON' mod='sonice_suivicolis'}
                            <select>
                                <option value="status_update" selected>{l s='Status update' mod='sonice_suivicolis'}</option>
                            </select>
                        </td>
                        <td>
                            {l s='AND' mod='sonice_suivicolis'}
                            <select>
                                <option value="order_status">{l s='Order Status' mod='sonice_suivicolis'}</option>
                                <option value="order_id">{l s='ID Order' mod='sonice_suivicolis'}</option>
                                <option value="colissimo_status">{l s='Colissimo Status' mod='sonice_suivicolis'}</option>
                            </select>
                            <select style="width: 50px;">
                                <option value="is_equal_to">=</option>
                                <option value="is_not_equal_to">!=</option>
                                <option value="is_lower_than"><</option>
                                <option value="is_lower_than_or_equal"><=</option>
                                <option value="is_upper_than">></option>
                                <option value="is_upper_than_or_equal"><=</option>
                            </select>
                            <input type="text">
                        </td>
                        <td>
                            {l s='SET' mod='sonice_suivicolis'}
                            <select>
                                <option value="order_status">{l s='Order Status' mod='sonice_suivicolis'}</option>
                                <option value="order_id">{l s='ID Order' mod='sonice_suivicolis'}</option>
                                <option value="colissimo_status">{l s='Colissimo Status' mod='sonice_suivicolis'}</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {include file="$snsc_module_path/views/templates/admin/configuration/validate.tpl"}
</div>