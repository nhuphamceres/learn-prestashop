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

<div class="amazon-orders">
    <div class="form-group">
        <label class="control-label col-lg-3 color-grey">{l s='Orders Statuses' mod='amazon'}</label>
    </div>

    <div class="cleaner"><br/></div>

    {include file="{$template_path|escape:'htmlall':'UTF-8'}/parameters.order_state.tpl"
    order_states=$order_states ps_order_states=$ps_order_states}


    {* Advanced order states *}
    <div class="form-group margin-top-15px">
        <label class="control-label col-lg-3 color-grey" rel="advanced_order_states_settings"><span>{l s='Advanced order states settings' mod='amazon'}</span></label>
        <div class="col-lg-9">
            <span class="advanced_order_states_control">[ + ] {l s='Advanced Settings' mod='amazon'}</span>
            <span class="advanced_order_states_control" style="display: none;">[ - ] {l s='Quick Settings' mod='amazon'}</span><br/><br/>
        </div>
    </div>

    <div class="form-group advanced_order_states_content" style="display: none;">
        <label class="control-label col-lg-3 glossary_target" rel="glossary_os_incoming_order_attributes">
            <span>{l s='Based on multiple order attributes' mod='amazon'}</span>
        </label>
        <div class="margin-form col-lg-9 chosen_allow_single_deselect" id="os_combination_wrapper">
            <span class="add_os_combination"><img src="{$images_url|escape:'htmlall':'UTF-8'}plus.png" alt="Add"/></span>

            {* A combination placeholder *}
            <div id="os_a_combination_placeholder" class="os_a_combination" style="display: none">
                <div class="os_a_combination_attributes">
                    {for $index=1 to $osIncomingPossibleAttrs|count}
                        <select class="placeholder" title="{l s='Select order attribute' mod='amazon'}"
                                data-dynamic-name="os_incoming_combination[x][attr][]">
                            <option></option>
                            {foreach from=$osIncomingPossibleAttrs key=optionValue item=optionName}
                                <option value="{$optionValue|escape:'htmlall':'UTF-8'}">
                                    {$optionName|escape:'htmlall':'UTF-8'}
                                </option>
                            {/foreach}
                        </select>
                    {/for}
                </div>
                <span><img class="arrow-next" src="{$images_url|escape:'htmlall':'UTF-8'}next.png" alt="Set to"/></span>
                <div>
                    <select class="placeholder chosen_longer" data-dynamic-name="os_incoming_combination[x][state]"
                            title="{l s='Select order state' mod='amazon'}">
                        <option></option>
                        {foreach from=$ps_order_states item=order_state}
                            <option value="{$order_state.id_order_state|intval}">
                                {$order_state.name|escape:'htmlall':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                </div>
                &nbsp;&nbsp;
                <span class="remove_os_combination"><img src="{$images_url|escape:'htmlall':'UTF-8'}minus.png" alt="Remove"/></span>
            </div>

            {* Save combinations *}
            {if is_array($osIncomingCombination) && count($osIncomingCombination)}
                {foreach from=$osIncomingCombination key=combinationId item=combination}
                    <div class="os_a_combination">
                        <div class="os_a_combination_attributes">
                            {foreach from=$combination['attr'] item=combinationAttr}
                                <select name="{'os_incoming_combination['|cat:$combinationId:'][attr][]'}"
                                        title="{l s='Select order attribute' mod='amazon'}">
                                    <option></option>
                                    {foreach from=$osIncomingPossibleAttrs key=optionValue item=optionName}
                                        <option value="{$optionValue|escape:'htmlall':'UTF-8'}" {if $combinationAttr == $optionValue}selected{/if}>
                                            {$optionName|escape:'htmlall':'UTF-8'}
                                        </option>
                                    {/foreach}
                                </select>
                            {/foreach}
                        </div>
                        <span><img class="arrow-next" src="{$images_url|escape:'htmlall':'UTF-8'}next.png" alt=""/></span>
                        <div>
                            <select class="chosen_longer" name="{'os_incoming_combination['|cat:$combinationId:'][state]'}"
                                    title="{l s='Select order state' mod='amazon'}">
                                <option></option>
                                {foreach from=$ps_order_states item=order_state}
                                    <option value="{$order_state.id_order_state|intval}"
                                            {if $combination['state'] == $order_state.id_order_state}selected{/if}>
                                        {$order_state.name|escape:'htmlall':'UTF-8'}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        &nbsp;&nbsp;
                        <span class="remove_os_combination"><img src="{$images_url|escape:'htmlall':'UTF-8'}minus.png" alt="Remove"/></span>
                    </div>
                {/foreach}
            {/if}
        </div>
    </div>

    <div class="cleaner"><br/><br/></div>
</div>
