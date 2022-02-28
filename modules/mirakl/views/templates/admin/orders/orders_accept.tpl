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

{if $tab_accept_data} 
    <div id="menudiv-accept"  class="{$tab_accept_data.selected_tab|escape:'htmlall':'UTF-8'}" rel="update">
        <h2 style="text-align:center;margin:10px 0 0 0;">{l s='Accept Orders' mod='mirakl'}</h2>

        <form action="{$tab_accept_data.request_uri|escape:'htmlall':'UTF-8'}" method="post" id="accept-orders-form">

            <hr style="width:600px;display:none" id="accept-orders-hr" />

            <!-- Marketplace selector -->
            {include file="./../marketplace_selection.tpl"}

            <div class="form-group">
                <input type="hidden" id="accept-orders-url" value="{$tab_accept_data.accept_orders_url|escape:'htmlall':'UTF-8'}" />
                <input type="button" id="accept-orders-search" value="{l s='Lookup' mod='mirakl'}" class="button btn btn-default" /><br /><br />
            </div>

            <hr style="width:600px;margin-bottom:15px;" />

            <div class="form-group">
                <div id="accept-loader"></div>
                <div id="accept-orders-success"></div>
                <div class="{$alert_class.success|escape:'htmlall':'UTF-8'}" id="accept-orders-result" style="display:none"></div>
                <br />
                <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'}" id="accept-orders-error" style="display:none;"></div>
            </div>

            <div class="form-group">
                <input type="hidden" id="accept-order-url" value="{$tab_accept_data.accept_order_url|escape:'htmlall':'UTF-8'}" />
                <input type="button" id="accept-order-button" value="{l s='Import Selected Orders' mod='mirakl'}" class="button btn btn-default" /><br />
                <input type="hidden" id="text-select-accept-orders" value="{l s='Please select one or more orders to accept !' mod='mirakl'}" />
                <pre id="accept-console" style="display: none;"></pre>
            </div>

            <!-- Order table -->
            <div class="form-group">
                <table id="accept-table" class="accept-table" style="display:none">
                <thead>
                    <td align="center"><input type="checkbox" class="select-all" /></td>
                    <td>{l s='Order ID' mod='mirakl'}</td>
                    <td>{l s='Date' mod='mirakl'}</td>
                    <td>{l s='Name' mod='mirakl'}</td>
                    <td>{l s='Ship' mod='mirakl'}</td>
                    <!-- <td>{l s='Validation' mod='mirakl'}</td>
                    <td>{l s='Status' mod='mirakl'}</td> -->
                    <td>{l s='Total' mod='mirakl'}</td>
                </thead>
                <tbody id="accept_order_list">
                </tbody>
                </table>
            </div>
            <!-- End Order table -->
            <br /><br />
        </form>
    </div>
{/if}    