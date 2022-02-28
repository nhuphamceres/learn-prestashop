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
*
* @package    Mirakl
* @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @author     Olivier B.
* @license    Commercial license
* Support by mail  :  support.mirakl@common-services.com
*}

<div id="{$module|escape:'quotes':'UTF-8'}-product-tab" class="product-tab" rel="{$module|escape:'quotes':'UTF-8'}">
<link href="{$module_url|escape:'quotes':'UTF-8'}views/css/product_tab.css" rel="stylesheet" type="text/css"/>

<input type="hidden" value="{$module|escape:'quotes':'UTF-8'}" name="module-name" />
<input type="hidden" value="{$module_json|escape:'quotes':'UTF-8'}" name="module-json" />
<input type="hidden" value="{l s='Parameters successfully saved' mod='mirakl'}" class="product-options-message-success" />
<input type="hidden" value="{l s='Unable to save parameters...' mod='mirakl'}" class="product-options-message-error" />

<script type="text/javascript">
    var module = "{$module|escape:'quotes':'UTF-8'}";
    var marketplace_options_json = "{$module_json|escape:'quotes':'UTF-8'}";
</script>

<script type="text/javascript" src="{$module_url|escape:'quotes':'UTF-8'}views/js/product_tab.js"></script>

    <div class="panel">
          <h3 class="tab"> <img width="32" src="{$images|escape:'htmlall':'UTF-8'}{$logo|escape:'htmlall':'UTF-8'}" alt=""/>&nbsp;&nbsp;{$marketplace|escape:'htmlall':'UTF-8'}&nbsp;&nbsp;&nbsp;</h3>

        <table id="mirakl">

                <tr class="mirakl-details">
                    <td style="padding-bottom:5px;"><br />
                    <input type="hidden" name="id_product" value="{$id_product|intval}" />
                    <input type="hidden" name="mirakl_option_lang[]" value="{$id_lang|intval}" />
                    <input type="hidden" id="{$module|escape:'quotes':'UTF-8'}-propagate-cat" value="{l s='Be careful ! Are you sure to want set this value for all the products of this Category ?' mod='mirakl'}" />
                    <input type="hidden" id="{$module|escape:'quotes':'UTF-8'}-propagate-shop" value="{l s='Be careful ! Are you sure to want to set this value for all the products of the entire Shop ?' mod='mirakl'}" />
                    <input type="hidden" id="{$module|escape:'quotes':'UTF-8'}-propagate-man" value="{l s='Be careful ! Are you sure to want to set this value for all the products for this Manufacturer ?' mod='mirakl'}" />
                    </td>
                </tr>
                <tr class="mirakl-details">
                    <td class="column-left">{l s='Disabled' mod='mirakl'}: </td>
                    <td style="padding-bottom:5px;">
                    <input type="checkbox" name="mirakl-disable-{$id_lang|intval}" value="1" {$forceUnavailableChecked|escape:'htmlall':'UTF-8'} />
                    <span style="margin-left:10px">{l s='Check this box to make this product unavailable on Mirakl' mod='mirakl'}</span><br />
                    <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Make all the products unavailable in this' mod='mirakl'} :
                        <a href="javascript:void(0)" class="mirakl-propagate-disable-cat propagate" rel="cat">[ {l s='Category' mod='mirakl'} ]</a>&nbsp;&nbsp;
                        <a href="javascript:void(0)" class="mirakl-propagate-disable-shop propagate" rel="shop">[ {l s='Shop' mod='mirakl'} ]</a>&nbsp;&nbsp;
                        <a href="javascript:void(0)" class="mirakl-propagate-disable-manufacturer propagate" rel="man">[ {l s='Manufacturer' mod='mirakl'} ]</a></span></span>
                    <span id="mirakl-extra-disable-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif" style="margin-left:5px;" alt="" /></span>
                    </td>
                </tr>
                <tr class="mirakl-details">
                    <td class="column-left">{l s='Force in Stock' mod='mirakl'}: </td>
                    <td style="padding-bottom:5px;">
                    <input type="checkbox" name="mirakl-force-{$id_lang|intval}" value="1" {$forceInStockChecked|escape:'htmlall':'UTF-8'} />
                    <span style="margin-left:10px">{l s='The product will always appear on Mirakl, even it\'s out of Stock' mod='mirakl'}</span><br />
                    <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Force as available in stock for all products in this' mod='mirakl'} :
                        <a href="javascript:void(0)" class="mirakl-propagate-force-cat propagate" rel="cat">[ {l s='Category' mod='mirakl'} ]</a>&nbsp;&nbsp;
                        <a href="javascript:void(0)" class="mirakl-propagate-force-shop propagate" rel="shop">[ {l s='Shop' mod='mirakl'} ]</a>&nbsp;&nbsp;
                        <a href="javascript:void(0)" class="mirakl-propagate-force-manufacturer propagate" rel="man">[ {l s='Manufacturer' mod='mirakl'} ]</a></span></span>
                    <span id="mirakl-extra-force-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif" style="margin-left:5px;" alt="" /></span>
                    </td>
                </tr>

                <tr class="mirakl-details">
                    <td class="column-left">{l s='Price Override' mod='mirakl'}: </td>
                    <td style="padding-bottom:5px;">
                    <input type="text" name="mirakl-price-{$id_lang|intval}" value="{$extraPrice|escape:'htmlall':'UTF-8'}" style="width:95px" />
                    <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Net Price for Mirakl Marketplace. This value will override your Shop Price' mod='mirakl'}</span>
                    </td>
                </tr>

                <tr class="mirakl-details">
                    <td class="column-left">{l s='Shipping Delay Override' mod='mirakl'}: </td>
                    <td style="padding-bottom:5px;">
                    <input type="text" name="mirakl-shipping-delay-{$id_lang|intval}" value="{$shippingDelay|escape:'htmlall':'UTF-8'}" style="width:95px" />
                    </td>
                </tr>

            {*todo: Translation*}
            {if $enable_mkp_specific_fields|count}
                <tr class="mirakl-details">
                    <td class="column-left">{l s='Marketplace specific fields' mod='mirakl'}:</td>
                    <td style="padding-bottom: 5px;">
                        <select class="specific_fields_select_mkp">
                            <option></option>
                            {foreach from=$enable_mkp_specific_fields item=mkp}
                                <option value="{$mkp.name|escape:'htmlall':'UTF-8'}">
                                    {$mkp.display_name|escape:'htmlall':'UTF-8'}
                                </option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
            {/if}

            {foreach from=$enable_mkp_specific_fields item=$mkp}
                {foreach from=$mkp.specific_fields key=fieldName item=specificField}
                    <tr class="mirakl-details mkp_specific_fields {$mkp.name|escape:'htmlall':'UTF-8'}" style="display: none;">
                        <td>{$specificField.label|escape:'htmlall':'UTF-8'}:</td>
                        <td style="padding-bottom: 5px;">
                            {assign var="specificFieldNameHtml" value="specific_field_{$id_lang|intval}[{$mkp.name}][{$fieldName}]"}
                            {if isset($selected_specific_fields[$mkp.name], $selected_specific_fields[$mkp.name][$fieldName])}
                                {assign var="specificFieldSavedValue" value=$selected_specific_fields[$mkp.name][$fieldName]}                                
                            {else}
                                {assign var="specificFieldSavedValue" value=""}
                            {/if}

                            {if $specificField.type == 'input'}
                                <input type="text" name="{$specificFieldNameHtml|escape:'htmlall':'UTF-8'}"
                                       value="{$specificFieldSavedValue|escape:'htmlall':'UTF-8'}"
                                       title="{$specificField.label|escape:'htmlall':'UTF-8'}" />
                            {else}
                                <select name="{$specificFieldNameHtml|escape:'htmlall':'UTF-8'}"
                                        title="{$specificField.label|escape:'htmlall':'UTF-8'}">
                                    <option></option>
                                    {foreach from=$specificField.value item=fieldValue}
                                        <option value="{$fieldValue|escape:'htmlall':'UTF-8'}"
                                                {if $fieldValue == $specificFieldSavedValue}selected{/if}>
                                            {$fieldValue|escape:'htmlall':'UTF-8'}
                                        </option>
                                    {/foreach}
                                </select>    
                            {/if}
                        </td>
                    </tr>
                {/foreach}
            {/foreach}

        </table>
    </div>
</div>
