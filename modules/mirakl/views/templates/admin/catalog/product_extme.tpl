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

<tr id="product-options-{$marketplace|escape:'htmlall':'UTF-8'}">
<td colspan="2"><table>
        <tr>
            <td colspan="2">
            <span id="productmirakl-options">
            <img src="{$images|escape:'htmlall':'UTF-8'}logo.gif" alt="" style="border:1px solid silver;position:relative;top:-2px;" />
            &nbsp;&nbsp;<b>Mirakl MarketPlace</b>&nbsp;&nbsp;&nbsp;
            <span style="color:grey">[</span>
            <img src="{$images|escape:'htmlall':'UTF-8'}plus.png" rel="{$images|escape:'htmlall':'UTF-8'}minus.png" alt="" style="position:relative;top:-1px;" id="mirakl-toggle-img" />
            <span style="color:grey;margin-left:-1px;">]</span>
            </div>
            </td>
        </tr>
        <tr class="mirakl-details">
            <td style="padding-bottom:5px;"><br />
            <input type="hidden" name="id_product" value="{$id_product|intval}" />
            <input type="hidden" name="mirakl_option_lang[]" value="{$id_lang|intval}" />
            <input type="hidden" id="mirakl-text-propagate-cat" value="{l s='Be careful ! Are you sure to want set this value for all the products of this Category ?' mod='mirakl'}" />
            <input type="hidden" id="mirakl-text-propagate-shop" value="{l s='Be careful ! Are you sure to want to set this value for all the products of the entire Shop ?' mod='mirakl'}" />
            <input type="hidden" id="mirakl-text-propagate-man" value="{l s='Be careful ! Are you sure to want to set this value for all the products for this Manufacturer ?' mod='mirakl'}" />
            </td>
        </tr>
        <tr class="mirakl-details">
            <td class="col-left">{l s='Disabled' mod='mirakl'}: </td>
            <td style="padding-bottom:5px;">
            <input type="checkbox" name="mirakl-disable-{$id_lang|intval}" value="1" {$forceUnavailableChecked|escape:'htmlall':'UTF-8'} />
            <span style="margin-left:10px">{l s='Check this box to make this product unavailable on Mirakl' mod='mirakl'}</span><br />
            <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Make all the products unavailable in this' mod='mirakl'} :
                <a href="javascript:void(0)" class="mirakl-propagate-disable-cat propagate">[ {l s='Category' mod='mirakl'} ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)" class="mirakl-propagate-disable-shop propagate">[ {l s='Shop' mod='mirakl'} ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)" class="mirakl-propagate-disable-manufacturer propagate">[ {l s='Manufacturer' mod='mirakl'} ]</a></span></span>
            <span id="mirakl-extra-disable-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif" style="margin-left:5px;" alt="" /></span>
            </td>
        </tr>
        <tr class="mirakl-details">
            <td class="col-left">{l s='Force in Stock' mod='mirakl'}: </td>
            <td style="padding-bottom:5px;">
            <input type="checkbox" name="mirakl-force-{$id_lang|intval}" value="1" {$forceInStockChecked|escape:'htmlall':'UTF-8'} />
            <span style="margin-left:10px">{l s='The product will always appear on Mirakl, even it\'s out of Stock' mod='mirakl'}</span><br />
            <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Force as available in stock for all products in this' mod='mirakl'} :
                <a href="javascript:void(0)" class="mirakl-propagate-force-cat propagate">[ {l s='Category' mod='mirakl'} ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)" class="mirakl-propagate-force-shop propagate">[ {l s='Shop' mod='mirakl'} ]</a>&nbsp;&nbsp;
                <a href="javascript:void(0)" class="mirakl-propagate-force-manufacturer propagate">[ {l s='Manufacturer' mod='mirakl'} ]</a></span></span>
            <span id="mirakl-extra-force-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif" style="margin-left:5px;" alt="" /></span>
            </td>
        </tr>
        
        <tr class="mirakl-details">
            <td class="col-left">{l s='Price Override' mod='mirakl'}: </td>
            <td style="padding-bottom:5px;">
            <input type="text" name="mirakl-price-{$id_lang|intval}" value="{$extraPrice|escape:'htmlall':'UTF-8'}" style="width:95px" />
            <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Net Price for Mirakl Marketplace. This value will override your Shop Price' mod='mirakl'}</span>
            </td>
        </tr>
        
        <tr class="mirakl-details">
            <td class="col-left">{l s='Shipping Delay Override' mod='mirakl'}: </td>
            <td style="padding-bottom:5px;">
            <input type="text" name="mirakl-shipping-delay-{$id_lang|intval}" value="{$shippingDelay|escape:'htmlall':'UTF-8'}" style="width:95px" />
            <span style="font-size:0.9em;color:grey;line-height:150%">{l s='' mod='mirakl'}</span>
            </td>
        </tr>

        <tr class="mirakl-details">
            <td colspan="2" style="padding-bottom:5px;">
                <hr style="margin-left:25%;width:50%" />
                <span style="color:brown;font-weight:bold;font-size:0.8em">{l s='Don\'t forget to click on the record button linked to this sub-tab if you modify this configuration !' mod='mirakl'}</span>
            </td>
        </tr>
        <tr class="mirakl-details">
          <td class="col-left"></td>
          <td style="padding:0 30px 5px 0;float:right;">
            <div class="{$class_success|escape:'htmlall':'UTF-8'}" style="display:none" id="result-mirakl"></div>
            <input type="button" style="float:right" id="productmirakl-save-options" class="button" value="{l s='Save Mirakl MarketPlace Parameters' mod='mirakl'}" />
          </td>
        </tr>
        {if isset($PS14)}
        <tr>
            <td colspan="2" style="padding-bottom:5px;"><hr style="width:100%" /></td>
        </tr>
        {/if}
        </table></td>
</tr>