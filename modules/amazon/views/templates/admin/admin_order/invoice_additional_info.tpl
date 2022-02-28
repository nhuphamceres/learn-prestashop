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

<p>{l s='Marketplace Order ID: %s' sprintf=[$amazon_order_id] mod='amazon'}</p>

{if is_array($customization_by_items) && count($customization_by_items)}
    <b>{l s='Customization:' mod='amazon' pdf='true'}</b>
    <ul>
        {foreach from=$customization_by_items item="customization_by_item"}
            <li>
                {$customization_by_item.item_name|escape:'htmlall':'UTF-8'}: X {$customization_by_item.item_qty|intval}

                <ul>
                    {* todo: Legacy is compatible only, remove in future *}
                    {if $customization_by_item.type == 'legacy'}
                        {foreach from=$customization_by_item.data item="customization_item"}
                            {*Get display value: $optionValue or $text*}
                            {if isset($customization_item.optionValue)}
                                {assign var=customizationValue value=$customization_item.optionValue}
                            {elseif isset($customization_item.text)}
                                {assign var=customizationValue value=$customization_item.text}
                            {else}
                                {assign var=customizationValue value=""}
                            {/if}

                            <li>{$customization_item.label|escape:'htmlall':'UTF-8'}: {$customizationValue|escape:'htmlall':'UTF-8'}</li>
                        {/foreach}
                    {elseif $customization_by_item.type == 'complete'}
                        {foreach from=$customization_by_item.data item="customization_item_complete"}
                            <li>{$customization_item_complete.label|escape:'htmlall':'UTF-8'}: {$customization_item_complete.value|escape:'htmlall':'UTF-8'}</li>
                        {/foreach}
                    {/if}
                </ul>
            </li>
        {/foreach}
    </ul>
{/if}
