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

<div class="row {if $placeholder}incoming_carrier_mapping_placeholder hide{else}incoming_carrier_mapping_entry{/if}">
    <select class="col-lg-3" {if !$placeholder}name="carrier_incoming[{$mappingId|intval}][mkp]"{/if}
            data-name-format="carrier_incoming[:index:][mkp]" title="Marketplace carrier">
        <option>-- Marketplace carrier --</option>
        {foreach from=$mkpCarriers item=mkp_carrier}
            <option value="{$mkp_carrier.carrier_code|escape:'htmlall':'UTF-8'}"
                    {if !$placeholder && $mapping.mkp == $mkp_carrier.carrier_code}selected{/if}>
                {$mkp_carrier.carrier_name|escape:'htmlall':'UTF-8'}
            </option>
        {/foreach}
    </select>
    <span class="col-lg-1 gutter" style="position: relative; top: 10px; text-align: center">
        <img src="{$images_url|escape:'quotes':'UTF-8'}next.png" alt="Map to" style="max-height: 16px; opacity: 0.5;" />
    </span>
    <select class="col-lg-3" {if !$placeholder}name="carrier_incoming[{$mappingId|intval}][ps]"{/if}
            data-name-format="carrier_incoming[:index:][ps]" title="map to PS carrier">
        <option>-- PrestaShop carrier --</option>
        {foreach from=$psCarriers item=ps_carrier}
            <option value="{$ps_carrier.id_carrier|intval}"
                    {if !$placeholder && $mapping.ps == $ps_carrier.id_carrier}selected{/if}>
                {$ps_carrier.carrier_name|escape:'htmlall':'UTF-8'}
            </option>
        {/foreach}
    </select>
    <div class="col-lg-1">
        <span class="incoming_carrier_mapping_remove">
            <img src="{$images_url|escape:'quotes':'UTF-8'}minus.png" alt="Remove mapping" />
        </span>
    </div>
</div>
