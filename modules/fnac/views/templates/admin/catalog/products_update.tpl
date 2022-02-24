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

<form action="{$fnac_update.request_uri|escape:'htmlall':'UTF-8'}" method="post" id="updateFnac" class="form-horizontal">
    <input type="hidden" id="update_url" value="{$fnac_update.export|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" id="msg_choose" value="{l s='Please choose categories to export' mod='fnac'}"/>
    <input type="hidden" id="msg_date" value="{l s='You have to choose a date range' mod='fnac'}"/>
    <input type="hidden" id="img_loader" value="{$fnac_update.loader|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" name="currentDate" value="{$fnac_update.currentDate|escape:'htmlall':'UTF-8'}"/>

    <ps-radios label="{l s='Select a Platform' mod='fnac'}">
        <ps-radio name="platform" value="fr" checked="true"><span class="fnac-country-title"> <img src="{$images|escape:'htmlall':'UTF-8'}fr.gif" alt="France" /> {l s='FNAC France' mod='fnac'}</span></ps-radio>
        <ps-radio name="platform" value="es"><span class="fnac-country-title"> <img src="{$images|escape:'htmlall':'UTF-8'}es.gif" alt="Spain" /> {l s='FNAC Spain' mod='fnac'}</span></ps-radio>
        <ps-radio name="platform" value="pt"><span class="fnac-country-title"> <img src="{$images|escape:'htmlall':'UTF-8'}pt.png" alt="Portugal" /> {l s='FNAC Portugal' mod='fnac'}</span></ps-radio>
        <ps-radio name="platform" value="be"><span class="fnac-country-title"> <img src="{$images|escape:'htmlall':'UTF-8'}be.png" alt="Belgium" /> {l s='FNAC Belgium' mod='fnac'}</span></ps-radio>
    </ps-radios>

    <ps-panel-divider></ps-panel-divider>

    <ps-switch label="{l s='Only Active Products' mod='fnac'}" name="update-active"  yes="{l s='Yes' mod='fnac'}" no="{l s='No' mod='fnac'}"></ps-switch>
    <ps-switch label="{l s='Only In Stock Products' mod='fnac'}" name="update-in-stock"  yes="{l s='Yes' mod='fnac'}" no="{l s='No' mod='fnac'}"></ps-switch>

    <ps-panel-divider></ps-panel-divider>

    <ps-form-group label="{l s='Categories' mod='fnac'}">
        <table>
            <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th style="padding-left:10px; width: 400px;">{l s='Name' mod='fnac'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$fnac_update.categories item=category}
                    <tr>
                        <td><input type="checkbox" name="categoryBox[]" id="categoryBox_{$category.id_category|escape:'htmlall':'UTF-8'}"
                                   value="{$category.id_category|escape:'htmlall':'UTF-8'}"
                                   checked/></td>
                        <td style="padding-left:10px;">{$category.desc_category|escape:'htmlall':'UTF-8'}</td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </ps-form-group>

    <ps-panel-divider></ps-panel-divider>

    <ps-form-group label="{l s='Date Range' mod='fnac'}">
        <span class="update-dt">&nbsp;&nbsp;{l s='From' mod='fnac'}&nbsp;&nbsp;</span>
        <input type="text" name="datepickerFrom1" id="datepickerFrom1" value="{$fnac_update.initialDate|escape:'htmlall':'UTF-8'}">
        <span class="update-dt">&nbsp;{l s='To' mod='fnac'}&nbsp;&nbsp;</span>
        <input type="text" name="datepickerTo1" id="datepickerTo1" value="{$fnac_update.currentDate|escape:'htmlall':'UTF-8'}">
    </ps-form-group>

    <ps-panel-divider></ps-panel-divider>

    <ps-alert-success id="result_update" style="display:none"></ps-alert-success>
    <ps-alert-error id="result_update_error" style="display:none"></ps-alert-error>

    <input type="submit" class="update-products button btn btn-default" id="lookup-products" rel="lookup" value="{l s='Verify' mod='fnac'}" style="float:left" />
    <input type="submit" class="update-products button btn btn-default" id="update-products" rel="update" value="{l s='Update and Create Selection' mod='fnac'}" style="float:right" />

    <div class="clearfix">&nbsp;</div>
    <br/>
    <ps-alert-success id="result_update" style="display:none"></ps-alert-success>
    <br/>
    <div class="clearfix">&nbsp;</div>
    
    <ps-alert-hint>{l s='Latest offers export date' mod='fnac'} : {$fnac_update.dateWS|escape:'htmlall':'UTF-8'}</ps-alert-hint>

    {if $fnac_update.errorImport}
        {$fnac_update.errorImport}
    {/if}
</form>