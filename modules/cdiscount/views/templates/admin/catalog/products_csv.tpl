{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Common-Services Co., Ltd. is strictly forbidden.
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
 * @package   CDiscount
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail:  support.cdiscount@common-services.com
*}

{if $tab_csv_data}
    <div id="conf-csv" class="tabItem {$tab_csv_data.tab_selected_csv|escape:'htmlall':'UTF-8'} form-horizontal"
         rel="csv">
        <h3 style="text-align:center;margin:10px 0 0 0; border: none;">{l s='Creation Mode' mod='cdiscount'}</h3>
        <h4 style="text-align:center;margin:0;">{$tab_csv_data.last_export_title|escape:'htmlall':'UTF-8'}</h4>
        <hr style="width: 60%; margin-top:5px;"/>

        <form action="{$tab_csv_data.request_uri|escape:'htmlall':'UTF-8'}" method="post" id="csv-products-form">
            <h4 style="text-align:center;color:red;">{l s='CSV Files are no longuer supported by CDiscount, please use XML instead' mod='cdiscount'}</h4>

            <div class="form-group">
                <label class="control-label col-lg-3" style="color:grey">{l s='Parameters' mod='cdiscount'}</label>

                <div class="margin-form col-lg-9">
                    <br/>
                    <br/>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Only Active Products' mod='cdiscount'}</label>

                <div class="margin-form col-lg-9">
                    <input type="checkbox" name="csv-active" value="1" checked="true"/> <span
                            class="ccb-title">{l s='Yes' mod='cdiscount'}</span>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Only In Stock Products' mod='cdiscount'}</label>

                <div class="margin-form col-lg-9">
                    <input type="checkbox" name="csv-in-stock" value="1" checked="true"/> <span
                            class="ccb-title">{l s='Yes' mod='cdiscount'}</span>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Date Range' mod='cdiscount'}</label>

                <div class="margin-form col-lg-9">
                    <span class="update-dt">&nbsp;&nbsp;{l s='From' mod='cdiscount'}&nbsp;&nbsp;</span>
                    <input type="text" name="datepickerFrom2" id="datepickerFrom2"
                           value="{$tab_csv_data.last_export|escape:'htmlall':'UTF-8'}">
                    <span class="update-dt">&nbsp;{l s='To' mod='cdiscount'}&nbsp;&nbsp;</span>
                    <input type="text" name="datepickerTo2" id="datepickerTo2"
                           value="{$tab_csv_data.current_date|escape:'htmlall':'UTF-8'}">
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Categories' mod='cdiscount'}</label>

                <div class="margin-form col-lg-9">
                    <table>
                        <thead>
                        <tr>
                            <th></th>
                            <th style="padding-right:30px">{l s='Category' mod='cdiscount'}</th>
                            <th>{l s='Profile' mod='cdiscount'}</th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach from=$tab_csv_data.categories item=category}
                            <tr>
                                <td><input type="checkbox" name="categories[]"
                                           value="{$category.id_category|escape:'htmlall':'UTF-8'}" checked/>
                                </td>
                                <td>{$category.desc_category|escape:'htmlall':'UTF-8'}</td>
                                <td>{$category.profile_name|escape:'htmlall':'UTF-8'}</td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>


            <hr style="width:600px;margin-bottom:25px;"/>
            <div id="csv-loader"></div>
            <div class="{$alert_class.success|escape:'htmlall':'UTF-8'}" id="csv-products-result"
                 style="display:none"></div>
            <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'}" id="csv-products-error"
                 style="display:none"></div>
            <br/>
            <input type="hidden" id="csv-products-url"
                   value="{$tab_csv_data.products_csv_url|escape:'htmlall':'UTF-8'}"/>
            <input type="button" id="csv-products" value="{l s='Export the File' mod='cdiscount'}" class="button"/>
            <br/><br/>
            <hr style="width:600px;margin-bottom:25px;display:none" id="csv-products-latest-hr"/>
            <div id="csv-products-latest" style="display:none"></div>
        </form>
    </div>
{/if}