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
* @author    Alexandre D. & Olivier B.
* @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @license   Commercial license
* Support by mail  :  contact@common-services.com
*}

<ps-input-text name="outofstock" label="{l s='Out of Stock' mod='fnac'}" help="{l s='Minimum of quantity in stock to export the product' mod='fnac'}" size="10" value="{$fnac_settings.out_of_stock|escape:'htmlall':'UTF-8'}" fixed-width="lg"></ps-input-text>

<ps-switch label="{l s='Discount/Specials' mod='fnac'}" name="discount"  yes="{l s='Yes' mod='fnac'}" no="{l s='No' mod='fnac'}"
           active="{($fnac_settings.discount|default:0) ? 'true' : 'false'}"
           help="{l s='The module will export discounted prices if checked' mod='fnac'}"></ps-switch>

<ps-switch label="{l s='Sales' mod='fnac'}" name="sales"  yes="{l s='Yes' mod='fnac'}" no="{l s='No' mod='fnac'}"
           active="{($fnac_settings.sales|default:0) ? 'true' : 'false'}"
           help="{l s='Promotions will be send as "Sales" instead of "FlashSales", enable only during the "Sales" period.' mod='fnac'}"></ps-switch>

<ps-switch label="{l s='Add reference in the product name' mod='fnac'}" name="name_ref"  yes="{l s='Yes' mod='fnac'}" no="{l s='No' mod='fnac'}"
           active="{($fnac_settings.name_ref|default:0) ? 'true' : 'false'}"
           help="{l s='The module will add the product/combination reference at the end of the product name' mod='fnac'}"></ps-switch>

<ps-form-group label="{l s='Price Formula' mod='fnac'}">
    <div class="col-sm-5"><textarea name="formula" rows="2" cols="80">{$fnac_settings.formula|escape:'htmlall':'UTF-8'}</textarea></div>
    <div class="help-block col-md-12">
        <p>{l s='Formula to be applied on all the exported products prices (multiply, divide, addition, subtraction, percentages)' mod='fnac'}</p>

        <p>{l s='Use the @ symbol as price reference (eg: @ + ' mod='fnac'}10%{l s=' mean you will add ' mod='fnac'}
            10%{l s=' to the initial price)' mod='fnac'}</p>
    </div>
</ps-form-group>

<ps-form-group label="{l s='Price CallBack' mod='fnac'}">
    <div class="col-sm-5"><textarea name="pcallback" rows="2" cols="80">{$fnac_settings.callback|escape:'htmlall':'UTF-8'}</textarea></div>
    <div class="help-block col-md-12">
        <p>{l s='PHP Code to be applied to the price (for rounding, formating etc...)' mod='fnac'}</p>

        <p>{l s='Use the @ symbol as price reference (eg: round(@, 2) mean you\'ll round to 2 decimals...)' mod='fnac'}</p>
    </div>
</ps-form-group>

<ps-form-group label="">
    <ps-panel-divider></ps-panel-divider>
</ps-form-group>

{if isset($fnac_settings.product_conditions)}
    <ps-form-group label="{l s='Products Condition' mod='fnac'}">
        {foreach from=$fnac_settings.product_conditions item=product_condition}
            <div class="form-group" style="padding-bottom:1px">
                <div class="col-xs-5"><input type="text" readonly="readonly" value="{$product_condition.condition|escape:'htmlall':'UTF-8'}"></div>
                <div class="col-xs-2 text-center"><span style="position:relative;top:8px">&nbsp;&nbsp;
                        <img src="{$images_url|escape:'htmlall':'UTF-8'}list-next.gif" alt="" />
                        &nbsp;&nbsp;
                    </span></div>
                <div class="col-xs-5">
                    <select name="condition_map[{$product_condition.key|escape:'htmlall':'UTF-8'}]"
                            id="condition_map-{$product_condition.index|escape:'htmlall':'UTF-8'}">
                        <option value=""></option>
                        {foreach from=$product_condition.options item=option}
                            <option value="{$option.value|escape:'htmlall':'UTF-8'}" {$option.selected|escape:'htmlall':'UTF-8'} >{$option.desc|escape:'htmlall':'UTF-8'}</option>
                        {/foreach}
                    </select>
                </div>
                </div>
            {/foreach}
            <div class="help-block">
                <p>{l s='Fnac MarketPlace conditions side / Prestashop conditions side, please associate the parameters wished' mod='fnac'}</p>
            </div>
    </ps-form-group>
{else}
    <input type="hidden" name="condition_map[New]" value="new"/>
{/if}