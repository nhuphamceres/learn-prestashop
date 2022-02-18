{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
 * Contact by Email :  support.priceminister@common-services.com
 *}


<tr>
    <td colspan="2"><span id="productpm-options">
                    <img src="{$module_url|escape:'htmlall':'UTF-8'}logo.svg" alt="" class="logo-wide" />&nbsp;&nbsp;<b>PriceMinister</b>&nbsp;&nbsp;&nbsp;<span
                    style="color:grey">[</span>
                        <img src="{$module_url|escape:'htmlall':'UTF-8'}views/img/plus.png" rel="{$module_url|escape:'htmlall':'UTF-8'}views/img/minus.png" alt="" style="position:relative;top:-1px;"
                             id="pm-toggle-img"/>
                            <span style="color:grey;margin-left:-1px;">]</span></span>
        <input type="hidden" name="pm_context_key" value="{$pm_context_key|escape:'htmlall':'UTF-8'}"/>
    </td>
</tr>
<tr class="productpm-details">
    <td style="padding-bottom:5px;"><br/>
        <input type="hidden" name="id_product" value="{$id_product|escape:'htmlall':'UTF-8'}"/>
        <input type="hidden" name="pm_option_lang[]" value="{$id_lang|escape:'htmlall':'UTF-8'}"/>
        <input type="hidden" name="pm_token" value="{$pm_token|escape:'htmlall':'UTF-8'}" id="token"/>
        <input type="hidden" id="pm-text-propagate-cat"
               value="{l s='Be carefull ! Are you sure to want to propagate this extra text to all the products of this categories ?' mod='priceminister'}"/>
        <input type="hidden" id="pm-text-propagate-shop"
               value="{l s='Be carefull ! Are you sure to want to propagate this extra text to the entire shop ?' mod='priceminister'}"/>
        <input type="hidden" id="pm-text-propagate-manufacturer"
               value="{l s='Be carefull ! Are you sure to want to force the stock for all the products for this Manufacturer for RakutenFrance ?' mod='priceminister'}"/>

        <input type="hidden" id="pm-text-propagate-disable-cat"
               value="{l s='Be carefull ! Are you sure to want to disable the products of this categories for RakutenFrance ?' mod='priceminister'}"/>
        <input type="hidden" id="pm-text-propagate-disable-shop"
               value="{l s='Be carefull ! Are you sure to want to disable all products of the entire shop for RakutenFrance ?' mod='priceminister'}"/>
        <input type="hidden" id="pm-text-propagate-disable-manufacturer"
               value="{l s='Be carefull ! Are you sure to want to force the stock for all the products for this Manufacturer for RakutenFrance ?' mod='priceminister'}"/>

        <input type="hidden" id="pm-text-propagate-force-cat"
               value="{l s='Be carefull ! Are you sure to want to force the stock for all the products of this categories for RakutenFrance ?' mod='priceminister'}"/>
        <input type="hidden" id="pm-text-propagate-force-shop"
               value="{l s='Be carefull ! Are you sure to want to force the stock for all the products of the entire shop for RakutenFrance ?' mod='priceminister'}"/>
        <input type="hidden" id="pm-text-propagate-force-manufacturer"
               value="{l s='Be carefull ! Are you sure to want to force the stock for all the products for this Manufacturer for RakutenFrance ?' mod='priceminister'}"/>


        <input type="hidden" id="pm-text-propagate-disable-manufacturer"
               value="{l s='Be carefull ! Are you sure to want to force the stock for all the products for this Manufacturer for RakutenFrance ?' mod='priceminister'}"/>
    </td>
</tr>

<tr class="productpm-details">
    <td class="col-left">{l s='Disabled' mod='priceminister'} </td>
    <td style="padding-bottom:5px;">
        <input type="checkbox" name="disable" value="1" {$force_unavailable.checked|escape:'htmlall':'UTF-8'} />
        <span style="margin-left:10px">{l s='Check this box to make this product unavailable on RakutenFrance' mod='priceminister'}</span>

        <div style="font-size:0.9em;color:grey;line-height:150%">{l s='Make all the products unavailable in this' mod='priceminister'}
            :
            <a href="javascript:void(0)" class="pm-propagate-disable-cat propagate">[ {l s='Category' mod='priceminister'}
                ]</a>&nbsp;&nbsp;
            <a href="javascript:void(0)" class="pm-propagate-disable-shop propagate">[ {l s='Shop' mod='priceminister'}
                ]</a>&nbsp;&nbsp;
            <a href="javascript:void(0)" class="pm-propagate-disable-manufacturer propagate">[ {l s='Manufacturer' mod='priceminister'}
                ]</a></span></span>
            <span id="pm-extra-disable-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif" style="margin-left:5px;" alt=""/>
        </div>

    </td>
</tr>
<tr class="productpm-details">
    <td class="col-left">{l s='Force in Stock' mod='priceminister'} </td>
    <td style="padding-bottom:5px;">
        <input type="checkbox" name="force" value="1" {$force_in_stock.checked|escape:'htmlall':'UTF-8'} />
        <span style="margin-left:10px">{l s='The product will always appear on RakutenFrance, even it\'s out of Stock' mod='priceminister'}</span>

        <div style="font-size:0.9em;color:grey;line-height:150%">{l s='Force the stock to all products in this' mod='priceminister'}
            :
            <a href="javascript:void(0)" class="pm-propagate-force-cat propagate">[ {l s='Category' mod='priceminister'}
                ]</a>&nbsp;&nbsp;
            <a href="javascript:void(0)" class="pm-propagate-force-shop propagate">[ {l s='Shop' mod='priceminister'}
                ]</a>&nbsp;&nbsp;
            <a href="javascript:void(0)" class="pm-propagate-force-manufacturer propagate">[ {l s='Manufacturer' mod='priceminister'}
                ]</a></span></span>
            <span id="pm-extra-force-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif" style="margin-left:5px;" alt=""/>
        </div>
        <br/>
    </td>
</tr>
<tr class="productpm-details">
    <td class="col-left">{l s='Extra Text' mod='priceminister'}</td>
    <td style="padding-bottom:5px;">
        <input type="text" name="text" value="{$force_extra_text.value|escape:'htmlall':'UTF-8'}" style="width:400px"/>
        <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Short text which will appear on the product sheet on RakutenFrance MarketPlace' mod='priceminister'}</span><br/>

        <div style="font-size:0.9em;color:grey;line-height:150%">{l s='This text overrides the defaut text sets in the module configuration' mod='priceminister'}</div>
        <div style="font-size:0.9em;color:grey;line-height:150%">{l s='Propagate this text to all products in this' mod='priceminister'}
            :
            <a href="javascript:void(0)" class="pm-propagate-text-cat propagate">[ {l s='Category' mod='priceminister'}
                ]</a>&nbsp;&nbsp;
            <a href="javascript:void(0)" class="pm-propagate-text-shop propagate">[ {l s='Shop' mod='priceminister'}
                ]</a>&nbsp;&nbsp;
            <a href="javascript:void(0)" class="pm-propagate-text-manufacturer propagate">[ {l s='Manufacturer' mod='priceminister'}
                ]</a></span></span>
            <span id="pm-extra-text-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif" style="margin-left:5px;" alt=""/>
        </div>
        <br/>
    </td>
</tr>
<tr class="productpm-details">
    <td class="col-left">{l s='Price Override' mod='priceminister'}</td>
    <td style="padding-bottom:5px;">
        <input type="text" name="pm_price" value="{$force_extra_price.value|escape:'htmlall':'UTF-8'}" style="width:95px"/>

        <div style="font-size:0.9em;color:grey;line-height:150%">{l s='Net Price for RakutenFrance. This value will override your Shop Price' mod='priceminister'}</div>
        <br/>
    </td>
</tr>

<tr class="productpm-details">
    <td style="padding:0 30px 5px 0;" colspan="2">
        <div class="{$class_success|escape:'htmlall':'UTF-8'}" style="display:none" id="result-pm"></div>
        <input type="button" style="float:right" id="productpm-save-options" class="button btn btn-default"
               value="{l s='Save Rakuten France Parameters' mod='priceminister'}"/>
    </td>
</tr>
{if isset($PS14)}
    <tr>
        <td colspan="2" style="padding-bottom:5px;">
            <hr style="width:100%"/>
        </td>
    </tr>
{/if}