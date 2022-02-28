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

<!-- Merchant fulfillment - Shipping service - Prime -->
{if $prime.enable}
    <p><b>{l s='Merchant Fulfillment' mod='amazon'}</b></p>

    <img src="{$prime.images_url|escape:'quotes':'UTF-8'}loading.gif" alt="{l s='Loading' mod='amazon'}" id="amazon-order-prime-loading" class="hidden" />
    
    <div id="amazon-order-prime-data">
        <!-- Error -->
        {if ($prime.has_error)}
            <div id="amazon-shipping-service-errors">
                <div class="alert alert-danger">
                    <p>{l s='Get shipping services have the errors :' mod='amazon'}</p>
                    <ul>
                        {foreach from=$prime.errors item=prime_error}
                            <li>{$prime_error|escape:'quotes':'UTF-8'}</li>
                        {/foreach}
                    </ul>
                </div>
                <div id="amazon-shipping-service-errors-hint" class="alert alert-info">
                    <p>{l s='Common errors and how to solve them:' mod='amazon'}</p>
                    <ul>
                        <li><p>{l s='TermsAndConditionsNotAccepted: Solve this by login at Amazon, then accept the terms and conditions for using the program' mod='amazon'}</p></li>
                        <li><p>{l s='PackageDimensions: In product details > Shipping tab, set the package dimensions' mod='amazon'}</p></li>
                        <li><p>{l s='Empty Shipping Service List: In Prime settings, you should choose Delivery Experience = Delivery confirmation without signature' mod='amazon'}</p></li>
                    </ul>
                </div>    
            </div>
        {/if}

        <!-- Prime logic -->
        {if $prime.step === -1}
            {l s='Something went wrong! Error while loading Prime order.' mod='amazon'}
        {elseif $prime.step === 0}
            {l s='Unrecognize Prime status!' mod='amazon'}
        {elseif $prime.step === 1}
            <button class="button btn btn-primary" id="amazon-get-eligible-shipping-services">
                <i class="icon-download"></i> {l s='Get the shipping services!' mod='amazon'}
            </button>
        {elseif $prime.step === 2}
            <label class="amazon-order-prime-selection" for="amazon-order-prime-carriers">
                {l s='Select the shipping service' mod='amazon'}
            </label>
            <select id="amazon-order-prime-carriers">
                <option value=""></option>
                {foreach from=$prime.carriers key=service_id item=carrier}
                    <option value="{$service_id|escape:'quotes':'UTF-8'}">
                        {$carrier.carrier_name|escape:'quotes':'UTF-8'} : {$service_id|escape:'quotes':'UTF-8'}
                        ({$carrier.carrier_rate|escape:'quotes':'UTF-8'} {$carrier.carrier_currency|escape:'quotes':'UTF-8'})
                    </option>
                {/foreach}
            </select>
            <div id="amazon-order-prime-label-formats" class="hidden">
                <div class="amazon-order-prime-selection">{l s='Select Label Format' mod='amazon'} :</div>
                {foreach from=$prime.carriers key=service_id item=carrier}
                    <select class="amazon-order-prime-label-formats-of-carrier hidden"
                            id="amazon-order-prime-label-formats-{$service_id|escape:'quotes':'UTF-8'}"
                            disabled title="{l s='Select Label Format' mod='amazon'}">
                        <option value=""></option>
                        {foreach from=$carrier.label_formats item=label_format}
                            <option value="{$label_format|escape:'quotes':'UTF-8'}">
                                {$label_format|escape:'quotes':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                {/foreach}
            </div>
            <div id="amazon-order-prime-create-shipping-label" class="hidden">
                <button class="button btn btn-primary pull-right">
                    <i class="icon-truck"></i> {l s='Create Shipping Label' mod='amazon'}
                </button>
            </div>
        {elseif $prime.step === 3}
            <div class="amazon-order-prime-selection">{l s='The label created' mod='amazon'}</div>
            <div class="amazon-order-prime-selection">
                {l s='Shipment ID' mod='amazon'} : <span>{$prime.shipment_id|escape:'htmlall':'UTF-8'}</span>
            </div>
            <br/>
            <a class="button btn btn-primary" href="{$prime.label_url|escape:'url':'UTF-8'}" title="{l s='Open the shipment label' mod='amazon'}"
               target="_blank"> <i class="icon-file-text"></i> {l s='Print label' mod='amazon'}</a>
        {/if}
    </div>
    
    <div id="amazon-order-prime-get-exist-label" class="hidden">
        <input type="text" title="Testing only" />
        <button class="button btn btn-danger pull-right">
            <i class="icon-truck"></i> Get already exist shipment
        </button>
    </div>
{/if}
