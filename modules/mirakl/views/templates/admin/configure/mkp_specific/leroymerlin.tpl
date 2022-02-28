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
* @author     Tran Pham
* @license    Commercial license
* Support by mail  :  support.mirakl@common-services.com
*}

{*todo: Translation domain changed*}
<div id="conf-mkp_specific" class="tabItem" style="display:none;">
    <h2>{$configuration.display_name|escape:'htmlall':'UTF-8'}</h2>
    <div class="margin-form col-lg-offset-3">
        <div style="font-size:1.2em;line-height:140%;" class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
            <p>
                {l s='From July 1st, 2021, the marketplace requires some additional fields when sending offers' mod='mirakl'}
            </p>
        </div>
    </div>

    {foreach from=$configuration.specific_fields key=$specificFieldKey item=$specificField}
        <div class="form-group">
            <label class="control-label col-lg-3" for="{$specificFieldKey|escape:'htmlall':'UTF-8'}">
                {$specificField.label|escape:'htmlall':'UTF-8'}
            </label>
            <div class="margin-form col-lg-9">
                <select id="{$specificFieldKey|escape:'htmlall':'UTF-8'}" name="mkp_specific_fields[{$specificFieldKey|escape:'htmlall':'UTF-8'}]" style="width: 300px;">
                    <option></option>
                    {foreach from=$specificField.value item=value}
                        <option value="{$value|escape:'htmlall':'UTF-8'}"
                                {if isset($configuration['selected'][$specificFieldKey]) && $configuration['selected'][$specificFieldKey] == $value}selected{/if} >
                            {$value|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    {/foreach}

    {include file="$module_path/views/templates/admin/configure/validate.tpl"}
</div>
