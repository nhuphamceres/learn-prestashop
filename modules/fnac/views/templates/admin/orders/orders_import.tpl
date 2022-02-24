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

<div id="menudiv-import" class="tabItem panel {$fnac_import.selected_tab|escape:'htmlall':'UTF-8'}" rel="import">
    <form action="{$fnac_import.request_uri|escape:'htmlall':'UTF-8'}" method="post" id="updateFnac" class="form-horizontal">
        <div>
            <input type="hidden" id="orders_url" value="{$fnac_import.lookup|escape:'htmlall':'UTF-8'}"/>
            <input type="hidden" id="import_url" value="{$fnac_import.import|escape:'htmlall':'UTF-8'}"/>
            <input type="hidden" id="msg_date" value="{l s='You have to choose a date range' mod='fnac'}"/>
            <input type="hidden" id="img_loader" value="{$fnac_import.loader|escape:'htmlall':'UTF-8'}"/>
            <input type="hidden" name="currentDate" value="{$fnac_import.currentDate|escape:'htmlall':'UTF-8'}"/>
            <input type="hidden" name="token_order" value="{$fnac_import.tokenOrders|escape:'htmlall':'UTF-8'}"/>
            <fieldset class="lookup">

                <ps-radios label="{l s='Select a Platform' mod='fnac'}">
                    <div class="fnac-platform">
                        <ps-radio name="platform" value="fr" checked="true"><span class="fnac-country-title"> <img src="{$images|escape:'htmlall':'UTF-8'}fr.gif" alt="France" /> {l s='FNAC France' mod='fnac'}</span></ps-radio>
                        <ps-radio name="platform" value="es"><span class="fnac-country-title"> <img src="{$images|escape:'htmlall':'UTF-8'}es.gif" alt="Spain" /> {l s='FNAC Spain' mod='fnac'}</span></ps-radio>
                        <ps-radio name="platform" value="pt"><span class="fnac-country-title"> <img src="{$images|escape:'htmlall':'UTF-8'}pt.png" alt="Portugal" /> {l s='FNAC Portugal' mod='fnac'}</span></ps-radio>
                        <ps-radio name="platform" value="be"><span class="fnac-country-title"> <img src="{$images|escape:'htmlall':'UTF-8'}be.png" alt="Belgium" /> {l s='FNAC Belgium' mod='fnac'}</span></ps-radio>
                    </div>
                </ps-radios>

                <ps-form-group label="{l s='Date Range' mod='fnac'}">
                    <span>{l s='From' mod='fnac'}&nbsp;&nbsp;</span>
                    <input type="text" name="datepickerFrom" id="datepickerFrom" value="{$fnac_import.initialDate|escape:'htmlall':'UTF-8'}">
                    <span>&nbsp;{l s='To' mod='fnac'}&nbsp;&nbsp;</span>
                    <input type="text" name="datepickerTo" id="datepickerTo" value="{$fnac_import.currentDate|escape:'htmlall':'UTF-8'}">
                </ps-form-group>

                <ps-form-group label="{l s='Status' mod='fnac'}">
                    <div class="col-md-5 fnac-platform">
                        <select name="statuses">
                            <option value="">{l s='Retrieve all pending orders' mod='fnac'}</option>
                            {foreach from=$fnac_import.statuses item=option}
                                <option value="{$option.value|escape:'htmlall':'UTF-8'}" {$option.selected|escape:'htmlall':'UTF-8'}>{$option.desc|escape:'htmlall':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </div>
                </ps-form-group>
                <input type="button" style="float:right" class="button btn btn-default" id="lookup" value="{l s='Lookup' mod='fnac'}"/>
            </fieldset>
        </div>
        <br/>
        <ps-panel icon="icon-search" img="../img/admin/search.gif" header="{l s='Pending Orders from the FNAC Market Place' mod='fnac'}" id="pending" style="display:none">
            <div id="result"></div>
            <br/>
            <fieldset id="result2" style="display:none;"></fieldset>
        </ps-panel>
    </form>
</div>