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

<form action="{$fnac_csv.request_uri|escape:'htmlall':'UTF-8'}" method="post" id="createCSVFnac" class="form-horizontal">
    <input type="hidden" id="export_url_csv" value="{$fnac_csv.export|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" id="msg_choose_csv" value="{l s='Please choose categories to export' mod='fnac'}"/>
    <input type="hidden" id="msg_date_csv" value="{l s='You have to choose a date range' mod='fnac'}"/>
    <input type="hidden" id="img_loader_csv" value="{$fnac_csv.loader|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" name="currentDate" value="{$fnac_csv.current_date|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" name="outputFile" value="{$fnac_csv.outputFile|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" name="outputUrl" value="{$fnac_csv.outputUrl|escape:'htmlall':'UTF-8'}"/>

    <ps-switch label="{l s='Only Active Products' mod='fnac'}" name="products-active-csv"  yes="{l s='Yes' mod='fnac'}" no="{l s='No' mod='fnac'}"></ps-switch>
    <ps-switch label="{l s='Only In Stock Products' mod='fnac'}" name="products-in-stock-csv"  yes="{l s='Yes' mod='fnac'}" no="{l s='No' mod='fnac'}"></ps-switch>

    <ps-panel-divider></ps-panel-divider>

    <ps-form-group label="{l s='Date Range' mod='fnac'}">
        <span class="update-dt">&nbsp;&nbsp;{l s='From' mod='fnac'}&nbsp;&nbsp;</span>
        <input type="text" name="datepickerFrom2" id="datepickerFrom2" value="{$fnac_csv.dateCSV|escape:'htmlall':'UTF-8'}">
        <span class="update-dt">&nbsp;{l s='To' mod='fnac'}&nbsp;&nbsp;</span>
        <input type="text" name="datepickerTo2_csv" id="datepickerTo2" value="{$fnac_csv.current_date|escape:'htmlall':'UTF-8'}">
    </ps-form-group>


    <ps-form-group label="{l s='Categories' mod='fnac'}">
        <table>
            <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th style="padding-left:10px; width: 400px;">{l s='Name' mod='fnac'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$fnac_csv.categories item=category}
                    <tr>
                        <td><input type="checkbox" name="categoryBox_csv[]" id="categoryBox_{$category.id_category|escape:'htmlall':'UTF-8'}"
                                   value="{$category.id_category|escape:'htmlall':'UTF-8'}" checked/></td>
                        <td style="padding-left:10px;">{$category.desc_category|escape:'htmlall':'UTF-8'}</td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </ps-form-group>

    <div class="conf" id="result_csv" style="display:none"></div>
    <br/>
    <ps-form-group class="text-right">
        <input type="submit" name="csv-products" {*id="csv-products"*} value="{l s='Generate CSV' mod='fnac'}" class="button btn btn-default"/>
    </ps-form-group>
</form>