{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Common-Services Co., Ltd. is strictly forbidden.
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
 * @package   CDiscount
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail:  support.cdiscount@common-services.com
*}

<tr>
                <td colspan="2"><span id="productmarketplace-options"><img src="{$images|escape:'htmlall':'UTF-8'}logo.gif" alt="" />&nbsp;&nbsp;CDiscount MarketPlace&nbsp;&nbsp;&nbsp;<span style="color:grey">[</span><img src="{$module_url|escape:'quotes':'UTF-8'}views/img/plus.png" rel="{$module_url|escape:'quotes':'UTF-8'}views/img/minus.png" alt="" style="position:relative;top:-1px;" id="marketplace-toggle-img" /><span style="color:grey;margin-left:-1px;">]</span></span></td>
            </tr>
  
     
     
    <tr class="marketplace-details">
        <td colspan="2" style="padding-bottom:15px;"><br/>
            <input type="hidden" name="id_product" value="{$id_product|escape:'htmlall':'UTF-8'}"/>
            <input type="hidden" name="marketplace_option_lang[]" value="{$id_lang|escape:'htmlall':'UTF-8'}"/>
            <input type="hidden" id="marketplace-text-propagate-cat"
                   value="{l s='Be careful ! Are you sure that you want to set this value for all the products of this Category ?' mod='cdiscount'}"/>
            <input type="hidden" id="marketplace-text-propagate-shop"
                   value="{l s='Be careful ! Are you sure that you want to set this value for all the products of the entire Shop ?' mod='cdiscount'}"/>
            <input type="hidden" id="marketplace-text-propagate-man"
                   value="{l s='Be careful ! Are you sure that you want to set this value for all the products for this Manufacturer ?' mod='cdiscount'}"/>
        </td>
    </tr>
    <tr class="marketplace-details">
        <td class="column-left">{l s='Disabled' mod='cdiscount'}</td>
        <td style="padding-bottom:15px;">
            <input type="checkbox" name="marketplace-disable-{$id_lang|escape:'htmlall':'UTF-8'}"value="1" {$forceUnavailableChecked|escape:'htmlall':'UTF-8'} />
            <span style="margin-left:10px">{l s='Check this box to make this product unavailable on CDiscount' mod='cdiscount'}</span><br/>
            <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Make all the products unavailable in this' mod='cdiscount'}
                :
                <a href="javascript:void(0)"
                   class="marketplace-propagate-disable-cat propagate">[ {l s='Category' mod='cdiscount'} ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)"
                   class="marketplace-propagate-disable-shop propagate">[ {l s='Shop' mod='cdiscount'} ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)"
                   class="marketplace-propagate-disable-manufacturer propagate">[ {l s='Manufacturer' mod='cdiscount'}
                    ]</a></span></span>
        <span id="marketplace-extra-disable-loader" style="display:none"><img
                    src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif"
                    style="margin-left:5px;" alt=""/></span>
        </td>
    </tr>
    <tr class="marketplace-details">
        <td class="column-left">{l s='Force in Stock' mod='cdiscount'}</td>
        <td style="padding-bottom:15px;">
            <input type="checkbox" name="marketplace-force-{$id_lang|escape:'htmlall':'UTF-8'}"
                   value="1" {$forceInStockChecked|escape:'htmlall':'UTF-8'} />
            <span style="margin-left:10px">{l s='The product will always appear on CDiscount, even it\'s out of Stock' mod='cdiscount'}</span><br/>
            <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Force as available in stock for all products in this' mod='cdiscount'}
                :
                <a href="javascript:void(0)"
                   class="marketplace-propagate-force-cat propagate">[ {l s='Category' mod='cdiscount'} ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)"
                   class="marketplace-propagate-force-shop propagate">[ {l s='Shop' mod='cdiscount'} ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)"
                   class="marketplace-propagate-force-manufacturer propagate">[ {l s='Manufacturer' mod='cdiscount'}
                    ]</a></span></span>
        <span id="marketplace-extra-force-loader" style="display:none"><img
                    src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif"
                    style="margin-left:5px;" alt=""/></span>
        </td>
    </tr>
    <tr class="marketplace-details">
        <td class="column-left tip" rel="condition"><span>{l s='Extra Text' mod='cdiscount'}</span></td>
        <td style="padding-bottom:15px;">
            <input type="text" name="marketplace-text-{$id_lang|escape:'htmlall':'UTF-8'}"
                   value="{$extraText|escape:'htmlall':'UTF-8'}" maxlength="200"
                   style="width:400px"/>
            <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Short text which will appear on the product sheet on CDiscount Marketplace' mod='cdiscount'}</span><br/>
        <span style="font-size:0.9em;color:grey;line-height:150%"><span
                    id="c-count">{$extraTextCount|escape:'htmlall':'UTF-8'}</span> {l s='characters left...' mod='cdiscount'}</span><br/>
            <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Propagate this text to' mod='cdiscount'} :
                <a href="javascript:void(0)"
                   class="marketplace-propagate-text-cat propagate">[ {l s='Category' mod='cdiscount'} ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)"
                   class="marketplace-propagate-text-shop propagate">[ {l s='Shop' mod='cdiscount'} ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)"
                   class="marketplace-propagate-text-manufacturer propagate">[ {l s='Manufacturer' mod='cdiscount'}
                    ]</a></span></span>
        <span id="marketplace-extra-text-loader" style="display:none"><img
                    src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif"
                    style="margin-left:5px;" alt=""/></span>
        </td>
    </tr>
    <tr class="marketplace-details">
        <td class="column-left">{l s='Price Override' mod='cdiscount'}</td>
        <td style="padding-bottom:15px;">
            <input type="text" name="marketplace-price-{$id_lang|escape:'htmlall':'UTF-8'}"
                   value="{$extraPrice|escape:'htmlall':'UTF-8'}" style="width:95px"/>
            <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Net Price for CDiscount Marketplace. This value will override your Shop Price' mod='cdiscount'}</span><br/>
        </td>
    </tr>

    {if $alignment}
    <tr class="marketplace-details">
        <td class="column-left">{l s='Auto Alignment' mod='cdiscount'}</td>
        <td style="padding-bottom:15px;">
            <input type="text" name="marketplace-aligndown-{$id_lang|escape:'htmlall':'UTF-8'}"
                   value="{$priceDown|escape:'htmlall':'UTF-8'}" style="width:60px"/><span
                    style="color:grey;font-size:0.8em;margin:5px;">&nbsp;</span><img
                    src="{$images|escape:'htmlall':'UTF-8'}down.png"
                    alt="{l s='Price Down' mod='cdiscount'}"/>
            &nbsp;&nbsp;&nbsp;
            <input type="text" name="marketplace-alignup-{$id_lang|escape:'htmlall':'UTF-8'}"
                   value="{$priceUp|escape:'htmlall':'UTF-8'}" style="width:60px"/><span
                    style="color:grey;font-size:0.8em;margin:5px;">&nbsp;</span><img
                    src="{$images|escape:'htmlall':'UTF-8'}up.png"
                    alt="{l s='Price Up' mod='cdiscount'}"/>
            <br/>
            <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Competitor alignment. Up and down prices for competition' mod='cdiscount'}</span><br/>
        </td>
    </tr>
    {/if}

    <tr class="marketplace-details">
        <td class="column-left tip" rel="latency"><span>{l s='Shipping Delay' mod='cdiscount'}</span></td>
        <td style="padding-bottom:15px;">
            <input type="text" name="marketplace-shipping_delay-{$id_lang|escape:'htmlall':'UTF-8'}"
                   value="{$extraDelay|escape:'htmlall':'UTF-8'}" style="width:95px"/>
            <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Additionnal delay in days. It will add this delay to the existing delay.' mod='cdiscount'}</span><br/>
            <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Propagate this value to' mod='cdiscount'} :
                <a href="javascript:void(0)"
                   class="marketplace-propagate-shipping_delay-cat propagate">[ {l s='Category' mod='cdiscount'} ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)"
                   class="marketplace-propagate-shipping_delay-shop propagate">[ {l s='Shop' mod='cdiscount'} ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)"
                   class="marketplace-propagate-shipping_delay-manufacturer propagate">[ {l s='Manufacturer' mod='cdiscount'}
                    ]</a></span></span>
        <span id="marketplace-extra-delay-loader" style="display:none"><img
                    src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif"
                    style="margin-left:5px;" alt=""/></span>
        </td>
    </tr>

    {if $conf_clogistique}
    <tr class="marketplace-details">
        <td class="column-left">{l s='C Logistique' mod='cdiscount'}</td>
        <td style="padding-bottom:15px;">
            <input type="checkbox" name="marketplace-clogistique-{$id_lang|escape:'htmlall':'UTF-8'}" {if $clogistique}checked{/if} value="1" /><br/>
            <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Check this box if this product is shipped by C Logistique' mod='cdiscount'}.</span><br/>
            <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Propagate this value to' mod='cdiscount'} :
                <a href="javascript:void(0)" class="marketplace-propagate-clogistique-cat propagate">[ {l s='Category' mod='cdiscount'} ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)" class="marketplace-propagate-clogistique-shop propagate">[ {l s='Shop' mod='cdiscount'} ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)" class="marketplace-propagate-clogistique-manufacturer propagate">[ {l s='Manufacturer' mod='cdiscount'}
                    ]</a></span></span>
            <span id="marketplace-clogistique-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif" style="margin-left:5px;" alt=""/></span>
        </td>
    </tr>

    <tr class="marketplace-details clogistique {if !$conf_clogistique}stay-hidden{/if}" style="{if !$conf_clogistique}display:none;{/if}">
        <td class="column-left">{l s='Value Added' mod='cdiscount'}</td>
        <td style="padding-bottom:15px;">
            <input type="text" name="marketplace-valueadded-{$id_lang|escape:'htmlall':'UTF-8'}" value="{$valueadded|escape:'htmlall':'UTF-8'}" style="width:60px" /><br/>
            <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Value Added for C Logistique handling' mod='cdiscount'}.</span><br/>
            <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Propagate this value to' mod='cdiscount'} :
                <a href="javascript:void(0)" class="marketplace-propagate-valueadded-cat propagate">[ {l s='Category' mod='cdiscount'} ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)" class="marketplace-propagate-valueadded-shop propagate">[ {l s='Shop' mod='cdiscount'} ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)" class="marketplace-propagate-valueadded-manufacturer propagate">[ {l s='Manufacturer' mod='cdiscount'} ]</a></span></span>
            <span id="marketplace-valueadded-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif" style="margin-left:5px;" alt=""/></span>
        </td>
    </tr>
    {/if}

    <tr class="panel-footer marketplace-details">
        <td class="column-left"></td>
        <td >
            <div class="{$class_success|escape:'htmlall':'UTF-8'}" style="display:none" id="result-marketplace"></div>
           <input type="button" style="float:right" id="productmarketplace-save-options" class="button btn btn-default pull-right"
                 value="{l s='Save MarketPlace Parameters' mod='cdiscount'}"/>
           <!--  <button style="margin-top:20px" id="productmarketplace-save-options" type="submit" name="productmarketplace-save-options" class="btn btn-default pull-right"><i class="process-icon-save"></i>{l s='Save MarketPlace Parameters' mod='cdiscount'}</button>-->
        </td>
    </tr>
    
        <tr>
            <td colspan="2" style="padding-bottom:15px;">              
                <hr style="width:100%"/>
            </td>
        </tr>
   
     


            