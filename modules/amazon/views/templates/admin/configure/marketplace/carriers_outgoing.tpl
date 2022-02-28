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

{*todo: Refactor input names. Already saved a refactor configuration*}
{*todo: Move all translations*}
<label class="control-label col-lg-3" rel="outgoing_order"><span>{l s='For Outgoing Orders' mod='amazon'}</span></label>

<div class="margin-form col-lg-9">
    <div class="row">
        <div class="col-sm-offset-8 col-sm-4">
            {if $outgoing.requires_shipping_method}
                <label class="glossary_target glossary_vertical_inversion" rel="shipping_services">
                    <span>{l s='Mandatory Shipping service / Shipping method' mod='amazon'}</span>
                </label>
            {/if}
        </div>
    </div>

    {foreach from=$outgoing.mapping key=index item=carrier}
        <div id="outgoing-carrier-group-{$id_lang|intval}-{$index|escape:'htmlall':'UTF-8'}"
             class="carrier-group carrier-group-outgoing row" style="margin-bottom: 15px;">
            <div class="col-sm-3">
                <select title="PrestaShop carrier" style="width: 100%;"
                        name="carrier_default[{$id_lang|intval}][prestashop][]">
                    <option disabled="disabled">{l s='Choose one of the following' mod='amazon'}</option>
                    <option value="0"></option>
                    {foreach from=$ps_carriers key=ps_id_carrier item=ps_carrier}
                        <option value="{$ps_id_carrier|intval}" {if $ps_id_carrier == $carrier.ps_carrier}selected{/if}>
                            {$ps_carrier.name|escape:'htmlall':'UTF-8'}{if $ps_carrier.is_module}&nbsp;({l s='Module' mod='amazon'}){/if}
                        </option>
                    {/foreach}
                </select>
            </div>
            <span class="col-sm-1 gutter">
                &nbsp;&nbsp;<img src="{$images_url|escape:'quotes':'UTF-8'}next.png" alt="" />&nbsp;&nbsp;
            </span>
            <div class="col-sm-3">
                <select name="carrier_default[{$id_lang|intval}][amazon][]" style="width: 100%;"
                        class="carrier_default_outgoing" title="Amazon carrier">
                    <option disabled>{l s='Choose one of the following' mod='amazon'}</option>
                    <option value=""></option>
                    {foreach from=$outgoing.carriers_and_methods item=amazonCarrier}
                        <option value="{$amazonCarrier.carrier|escape:'htmlall':'UTF-8'}"
                                data-carrier-key="{$amazonCarrier.carrier_key|escape:'htmlall':'UTF-8'}"
                                {if $amazonCarrier.carrier == $carrier.amazon_carrier}selected{/if}>
                            {$amazonCarrier.carrier|escape:'htmlall':'UTF-8'}
                        </option>
                    {/foreach}
                    <option value="Other" {if 'Other' == $carrier.amazon_carrier}selected{/if}>Other</option>
                </select>
                <input type="text" name="carrier_default[{$id_lang|intval}][custom_value][]"
                       class="carrier_default_outgoing_custom" title="Custom carrier"
                       value="{$carrier.custom_value.value|escape:'htmlall':'UTF-8'}"
                       style="display: {if $carrier.custom_value.show}inline{else}none{/if}" />
            </div>

            {* Only show shipping method for 5 EU marketplaces *}
            {if $outgoing.requires_shipping_method}
                <span class="col-sm-1 gutter">&nbsp;&nbsp;
                    <img src="{$images_url|escape:'quotes':'UTF-8'}next.png" alt="" />&nbsp;&nbsp;
                </span>
                <div class="col-sm-3">
                    <select name="carrier_default[{$id_lang|intval}][shipping_service][]" style="width: 100%;"
                            class="carrier_outgoing_shipping_service" title="Shipping service / Shipping method">
                        <option disabled class="chosen_default_option">Shipping service / Shipping method</option>
                        <option value="" class="chosen_default_option"></option>
                        {foreach from=$outgoing.carriers_and_methods item=amazonShippingMethod}
                            {foreach from=$amazonShippingMethod.shipping_method item=shippingMethod}
                                <option value="{$shippingMethod|escape:'htmlall':'UTF-8'}"
                                        data-belong-to-carrier-key="{$amazonShippingMethod.carrier_key|escape:'htmlall':'UTF-8'}"
                                        {if $shippingMethod == $carrier.shipping_service}selected{/if}
                                        {if $amazonShippingMethod.carrier_key != $carrier.amazon_carrier_key}style="display: none;"{/if}>
                                    {$shippingMethod|escape:'htmlall':'UTF-8'}
                                </option>
                            {/foreach}
                        {/foreach}
                        <option value="Other" class="chosen_default_option"
                                {if 'Other' == $carrier.shipping_service}selected{/if}>Other</option>
                    </select>
                    <input type="text" name="carrier_default[{$id_lang|intval}][custom_method][]"
                           class="carrier_default_outgoing_custom_method" title="Custom shipping method"
                           value="{$carrier.custom_method.value|escape:'htmlall':'UTF-8'}"
                           style="display: {if $carrier.custom_method.show}inline{else}none{/if}" />
                </div>
            {/if}

            <div class="col-sm-1">
                &nbsp;&nbsp;
                {if $index === 0}
                    <span class="add-carrier add_outgoing_carrier" rel="{$index|escape:'htmlall':'UTF-8'}">
                        <img src="{$images_url|escape:'quotes':'UTF-8'}plus.png"
                             alt="{l s='Add a new carrier' mod='amazon'}" />
                    </span>
                {/if}
                <span class="remove-carrier remove_outgoing_carrier" rel="{$index|escape:'htmlall':'UTF-8'}"
                      {if $index === 0}style="display: none;"{/if}>
                    <img src="{$images_url|escape:'quotes':'UTF-8'}minus.png" alt="Remove mapping" />
                </span>
            </div>
        </div>
    {/foreach}

    <div id="outgoing-new-carriers-{$id_lang|intval}" class="carrier-group-outgoing"></div>
</div>
