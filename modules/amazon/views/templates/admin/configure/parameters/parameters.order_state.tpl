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

{foreach from=$order_states item=state}
    {if $state.enabled}
        <div class="form-group two-px-margin-bottom {if isset($state.rel)}{$state.rel|escape:'htmlall':'UTF-8'}{/if}"
             {if isset($state.id)}id="{$state.id|escape:'htmlall':'UTF-8'}"{/if}
             {if (!$state.active)}style="display:none"{/if}>
            <label class="control-label col-lg-3" rel="{$state.glossary|escape:'htmlall':'UTF-8'}">
                <span>{$state.title|escape:'htmlall':'UTF-8'}</span>
            </label>

            <div class="margin-form col-lg-9 {if $state.allow_deselect}chosen_allow_single_deselect{/if}">
                <select class="chosen_longer" name="{$state.name|escape:'htmlall':'UTF-8'}"
                        data-placeholder="{$state.desc|escape:'htmlall':'UTF-8'}"
                        title="{$state.desc|escape:'htmlall':'UTF-8'}">
                    <option></option>
                    {foreach from=$ps_order_states item=ps_order_state}
                        <option value="{$ps_order_state.id_order_state|intval}"
                                {if $state.value == $ps_order_state.id_order_state}selected{/if}>
                            {$ps_order_state.name|escape:'htmlall':'UTF-8'}
                        </option>
                    {/foreach}
                </select>
            </div>
        </div>
    {/if}
{/foreach}
