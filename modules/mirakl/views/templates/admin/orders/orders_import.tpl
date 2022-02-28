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

{if $tab_import_data}
    <div id="menudiv-import"  class="{$tab_import_data.selected_tab|escape:'htmlall':'UTF-8'}" rel="update" style="display: none;">
        <h2 style="text-align:center;margin:10px 0 0 0;">{l s='Import Orders' mod='mirakl'}</h2>
        <h4 style="text-align:center;margin:0;">{$tab_import_data.last_import_title|escape:'htmlall':'UTF-8'}</h4>

        <form action="{$tab_import_data.request_uri|escape:'htmlall':'UTF-8'}" method="post" id="import-orders-form">
            <br />

            <div class="form-group">
                <label class="control-label col-lg-3" style="color:grey">{l s='Parameters' mod='mirakl'}</label>
                <div class="margin-form col-lg-9">
                    <br>
                </div>
            </div>

            <!-- Marketplace selector -->
            {include file="./../marketplace_selection.tpl"}

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Date Range' mod='mirakl'}</label>
                <div class="margin-form col-lg-9">
                    <input type="text" name="datepickerFrom" id="datepickerFrom" value="{$tab_import_data.start_date|escape:'htmlall':'UTF-8'}" style="margin-left:5px">
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Order Status' mod='mirakl'}</label>
                <div class="margin-form col-lg-9">
                    <select name="orders-statuses" id="orders-statuses" >
                        <option value="Importable">{l s='Ready to be Imported' mod='mirakl'}</option>
                        <option value="All">{l s='All Pending Orders' mod='mirakl'}</option>

                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">&nbsp;</label>       
                <div class="margin-form col-lg-9">
                    <input type="hidden" id="import-orders-url" value="{$tab_import_data.import_orders_url|escape:'htmlall':'UTF-8'}" />
                    <input type="button" id="import-orders" value="{l s='Lookup' mod='mirakl'}" class="button btn btn-default" /><br /><br />
                    <div id="import-loader">
                    </div>
                </div>
            </div>
                    
            <hr style="width:600px;display:none" id="import-orders-hr" />
            
            <div class="form-group">
                <div class="margin-form">
                    <div  class="{$alert_class.success|escape:'htmlall':'UTF-8'}" id="import-orders-success" style="display:none"></div>
                    <div class="{$alert_class.success|escape:'htmlall':'UTF-8'}" id="import-orders-result" style="display:none"></div><br />
                    <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'}" id="import-orders-error" style="display:none"></div>
                    <pre id="import-console" style="display: none;"></pre>
                </div>
            </div>
                
            <hr style="width:600px;margin-bottom:15px;" />

            <div class="form-group">
                <label class="control-label col-lg-3">&nbsp;</label>
                <div class="margin-form col-lg-9">
                    <input type="hidden" name="token_order" value="{$tab_import_data.token_orders|escape:'htmlall':'UTF-8'}" />
                    <input type="hidden" id="import-order-url" value="{$tab_import_data.import_order_url|escape:'htmlall':'UTF-8'}" />
                    <input type="button" id="import-order" value="{l s='Import Selected Orders' mod='mirakl'}" class="button btn btn-default" /><br />
                    <input type="hidden" id="text-select-import-orders" value="{l s='Please select one or more orders to import !' mod='mirakl'}" />
                </div>
            </div>

            <!-- Order table -->
            <div class="form-group">
                <table id="import-table" class="import-table" style="display:none;">
                    <thead>
                        <td align="center"><input type="checkbox" class="select-all" /></td>
                        <td>{l s='Order ID' mod='mirakl'}</td>
                        <td>{l s='Date' mod='mirakl'}</td>
                        <td>{l s='Name' mod='mirakl'}</td>
                        <td>{l s='C.ID' mod='mirakl'}</td>
                        <td>{l s='Payment' mod='mirakl'}</td>
                        <td>{l s='Ship' mod='mirakl'}</td>
                        <td>{l s='Total' mod='mirakl'}</td>
                    </thead>
                    <tbody id="order_list">
                    </tbody>
                </table>
            </div>
            <!-- End Order table -->
        </form>
    </div>
{/if}   