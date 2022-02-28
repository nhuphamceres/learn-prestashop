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

{if isset($input)}
    {if isset($input.label)}
        <label class="profile-obj-title control-label col-lg-3" style="color:green;">
            {$input.label|escape:'htmlall':'UTF-8'}
        </label>
    {/if}
    <div class="margin-form col-lg-9">
            <select name="{$input.name|escape:'htmlall':'UTF-8'}"
                    style="max-width:250px;"
                    class="profile-attribute {if isset($input.class)} {$input.class|escape:'htmlall':'UTF-8'} {/if}"
                    id="{if isset($input.id)}{$input.id|escape:'htmlall':'UTF-8'}{else}{$input.name|escape:'htmlall':'UTF-8'}{/if}"
                    {if isset($input.multiple)}multiple="multiple" {/if}
                    {if isset($input.size)}size="{$input.size|escape:'htmlall':'UTF-8'}"{/if}
                    {if isset($input.onchange)}onchange="{$input.onchange|escape:'htmlall':'UTF-8'}"{/if}>

                    <option value="">{l s='Choose' mod='mirakl'}</option>
                    {foreach from=$input.options item=option}
                        <option value="{$option.value|escape:'htmlall':'UTF-8'}" {$option.selected|escape:'htmlall':'UTF-8'}>{$option.desc|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}

            </select>
        {if isset($input.required) && $input.required}<span style="color:red;">&nbsp;*</span>{/if}
        {if isset($input.help)}
            <p class="profile-help">
                {$input.help|escape:'htmlall':'UTF-8'}
            </p>
        {/if}
    </div>
{/if}