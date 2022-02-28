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

{*todo: Translation*}
<div id="conf-mkp_specific" class="tabItem" style="display:none;">
    <h2>{$configuration.display_name|escape:'htmlall':'UTF-8'}</h2>
    <div class="margin-form col-lg-offset-3">
        <div style="font-size:1.2em;line-height:140%;" class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
            <p>
                {l s='From July 1st, 2021, the marketplace requires some additional fields when sending offers' mod='mirakl'}
            </p>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3" for="ubaldi-shipment-from-eu">
            {$configuration.specific_fields.shipment_from_eu.label|escape:'htmlall':'UTF-8'}
        </label>
        <div class="margin-form col-lg-9">
            <select id="ubaldi-shipment-from-eu" name="mkp_specific_fields[shipment_from_eu]" style="width: 300px;">
                <option></option>
                {foreach from=$configuration.specific_fields.shipment_from_eu.value item=choice}
                    <option value="{$choice}"
                            {if isset($configuration.selected.shipment_from_eu) && $configuration.selected.shipment_from_eu == $choice}selected{/if} >
                        {$choice|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>
        </div>
    </div>

    {include file="$module_path/views/templates/admin/configure/validate.tpl"}
</div>
