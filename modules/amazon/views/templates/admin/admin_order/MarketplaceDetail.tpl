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

{foreach from=$details item=detail}
    {* Customization *}
    {if isset($detail.marketplace_detail.customization) && is_array($detail.marketplace_detail.customization)
    && isset($detail.marketplace_detail.customization.type) && isset($detail.marketplace_detail.customization.data)
    && is_array($detail.marketplace_detail.customization.data) && $detail.marketplace_detail.customization.data|count}
        <div class="marketplace_detail amz_customization hide" data-value="{$detail.id_order_detail|escape:'htmlall':'UTF-8'}">
            <b>{l s='Customizations' mod='amazon'}:</b><br>
            <ul>
                {* todo: Legacy is compatible only, remove in future *}
                {if $detail.marketplace_detail.customization.type == 'legacy'}
                    {assign var="customizations" value=$detail.marketplace_detail.customization.data}
                    {if 1 === $customizations|count}
                        {foreach from=$customizations[0] item="customization"}
                            <li>{include file=$template_path|cat:'CustomizationItem.tpl' customization=$customization}</li>
                        {/foreach}
                    {else}
                        {foreach from=$customizations key="index" item="sku_customization"}
                            <li>{"No. "|cat:$index|escape:'htmlall':'UTF-8'}:
                                <ul>
                                    {foreach from=$sku_customization item="customization"}
                                        <li>{include file=$template_path|cat:'CustomizationItem.tpl' customization=$customization}</li>
                                    {/foreach}
                                </ul>
                            </li>
                        {/foreach}
                    {/if}
                {elseif $detail.marketplace_detail.customization.type == 'complete'}
                    {foreach from=$detail.marketplace_detail.customization.data item="customizationComplete"}
                        <li>{$customizationComplete.label|escape:'htmlall':'UTF-8'}: {$customizationComplete.value|escape:'htmlall':'UTF-8'}</li>
                    {/foreach}
                {/if}
            </ul>
        </div>
    {/if}

    {* IOSS / OSS *}
    {if isset($detail.marketplace_detail.additional_info) && is_array($detail.marketplace_detail.additional_info) && $detail.marketplace_detail.additional_info|count}
        <div class="marketplace_detail amz_additional_info hide" data-value="{$detail.id_order_detail|escape:'htmlall':'UTF-8'}">
            {foreach from=$detail.marketplace_detail.additional_info item=additionalInfo}
                <b>{$additionalInfo.display_name|escape:'htmlall':'UTF-8'}:</b> {$additionalInfo.value|escape:'htmlall':'UTF-8'}<br>
            {/foreach}
        </div>
    {/if}
{/foreach}
