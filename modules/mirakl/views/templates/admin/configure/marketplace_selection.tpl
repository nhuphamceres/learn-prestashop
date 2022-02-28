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

<div id="mkp-selector" class="panel form-horizontal {if !count($mkps)}hide{/if}">
    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Selected Marketplace' mod='mirakl'}</label>

        <div class="margin-form col-lg-9">
            <select id="selected-mkp" name="selected-mkp" class="col-lg-4" title="{l s='Selected Marketplace' mod='mirakl'}">
                {foreach $mkps as $id_mkp => $mkp}
                    <option value="{$id_mkp}" {if $current_mkp == $id_mkp}selected{/if}>
                        {$mkp.display_name}
                    </option>
                {/foreach}
            </select>

            &nbsp;&nbsp;

            <img src="{$images_url|escape:'quotes':'UTF-8'}loading.gif" alt="Loading..." style="display: none;">

            {if $ps16x}
                <button type="submit" value="1" name="mirakl-marketplace" class="btn btn-default hide">
                    {l s='Change marketplace context' mod='mirakl'}
                </button>
            {else}
                <input type="submit" class="button btn" style="display:none" name="mirakl-marketplace" value="{l s='Change marketplace context' mod='mirakl'}" />
            {/if}

            <input type="hidden" id="before-changing-mkp-1" value="{l s='When changing the marketplace context, current modifications are not saved.' mod='mirakl'}">
            <input type="hidden" id="before-changing-mkp-2" value="{l s='Do you want to change context?' mod='mirakl'}">
        </div>
    </div>
</div>