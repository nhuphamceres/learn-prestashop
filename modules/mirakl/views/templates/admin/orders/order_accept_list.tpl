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
* @author     Tran Pham
* @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @license    Commercial license
* @package    Mirakl
* Support by mail  :  support.mirakl@common-services.com
*}

<!-- Order overview -->
<tr class="order-item">
    <td align="center">
        <input type="checkbox" name="selected_orders[]" {if $disabled}disabled{/if}
               id="o-{$oid|escape:'htmlall':'UTF-8'}" value="{$oid|escape:'htmlall':'UTF-8'}" />
    </td>
    <td>{$oid|escape:'htmlall':'UTF-8'}</td>
    <td>{$order_datetime|escape:'htmlall':'UTF-8'}</td>
    <td>{$order_name|escape:'htmlall':'UTF-8'}</td>
    <td align="right">{$shipping_price|string_format:"%.02f"}</td>
    <td align="right">{$total_price|string_format:"%.02f"}</td>
</tr>

<!-- Order items -->
<tr>
    <td colspan="8">
        <table class="import-table">
            <thead>
            <tr class="heading">
                <td></td>
                <td>{l s='Row' mod='mirakl'}</td>
                <td>{l s='Product ID' mod='mirakl'}</td>
                <td>{l s='Name' mod='mirakl'}</td>
                <td>{l s='SKU' mod='mirakl'}</td>
                <td align="center">{l s='Ship.' mod='mirakl'}</td>
                <td align="center">{l s='Qty.' mod='mirakl'}</td>
                <td align="center">{l s='Price' mod='mirakl'}</td>
            </tr>
            </thead>

            <tbody>
            {foreach from=$details item="detail"}
                <tr>
                    {if !$detail.ps_qty}
                        <td style="padding-left:8px;width:20px">
                            <img src="{$image_path|escape:'htmlall':'UTF-8'}soos.png" style="background:transparent;"
                                 alt="{l s='Out of Stock' mod='mirakl'}" title="{l s='Out of Stock' mod='mirakl'}" />
                        </td>
                    {else}
                        <td align="center" style="width:20px">
                            <img src="{$image_path|escape:'htmlall':'UTF-8'}sis.png" style="background:transparent;"
                                 title="{$detail.offer_sku|escape:'htmlall':'UTF-8'}" />
                            <input type="hidden" id="pl-{$oid|escape:'htmlall':'UTF-8'}-{$detail.offer_sku|escape:'htmlall':'UTF-8'}"
                                   value="1" name="item_list[{$oid|escape:'htmlall':'UTF-8'}][{$detail.offer_sku|escape:'htmlall':'UTF-8'}]" />
                        </td>
                    {/if}

                    <td>{$detail.products_row|escape:'htmlall':'UTF-8'}</td>
                    <td>{$detail.offer_id|escape:'htmlall':'UTF-8'}</td>
                    <td>{$detail.product_title|escape:'htmlall':'UTF-8'}</td>
                    <td>{$detail.offer_sku|escape:'htmlall':'UTF-8'}</td>
                    <td align="right">{$detail.shipping_price|string_format:'%.02f'}</td>
                    <td align="center">{$detail.order_qty|escape:'htmlall':'UTF-8'}</td>
                    <td align="right">{$detail.price|string_format:'%.02f'}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </td>
</tr>