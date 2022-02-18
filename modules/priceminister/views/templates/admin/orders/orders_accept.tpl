{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
 * Contact by Email :  support.priceminister@common-services.com
 *}

{if $tab_accept_data}
    <div id="conf-accept" class="tabItem {$tab_accept_data.selected_tab|escape:'htmlall':'UTF-8'} form-horizontal"
         rel="update">
        <h3 style="text-align:center;margin:10px 0 0 0; border: none;">{l s='Accept Orders' mod='priceminister'}</h3>
        <h4 style="text-align:center;margin:0;">{l s='Validate Pending Orders' mod='priceminister'}</h4>
        <hr style="width:60%;margin-top:5px;"/>

        <form action="{$tab_accept_data.request_uri|escape:'htmlall':'UTF-8'}" method="post" id="accept-orders-form">
            <input type="hidden" name="pm_token" value="{$tab_accept_data.pm_token|escape:'htmlall':'UTF-8'}"/>
            <div class="form-group">
                <label class="control-label col-lg-3" style="color:grey">{l s='Parameters' mod='priceminister'}</label>

                <div class="margin-form col-lg-9">
                    <br/>
                    <input type="checkbox" name="debug" value="1" style="display:none"/>
                    <br/>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Date Range' mod='priceminister'}</label>

                <div class="margin-form col-lg-9">
                    <input type="text" name="datepickerFrom" id="datepickerFromA" value="{$tab_accept_data.start_date|escape:'htmlall':'UTF-8'}" style="margin-left:5px">
                    <span class="accept-dt">&nbsp;{l s='To' mod='priceminister'}&nbsp;&nbsp;</span>
                    <input type="text" name="datepickerTo" id="datepickerToA" value="{$tab_accept_data.current_date|escape:'htmlall':'UTF-8'}">
                </div>
            </div>

            <div class="form-group">
                <div class="margin-form col-lg-offset-3">
                    <input type="hidden" id="accept-orders-url" value="{$tab_accept_data.accept_orders_url|escape:'htmlall':'UTF-8'}"/>
                    <input type="button" id="accept-orders" value="{l s='Lookup' mod='priceminister'}" class="button btn btn-default"/><br/><br/>
                    <div id="accept-loader"></div>
                </div>
            </div>
            <textarea name="encoded-xml" style="display:none;"></textarea>

            <hr style="width:30%;display:none" id="accept-orders-hr"/>

            <div class="form-group">
                <div class="{$alert_class.success|escape:'htmlall':'UTF-8'} col-lg-12" id="accept-orders-result" style="display:none;"></div>
                <br/>
                <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'} col-lg-12" id="accept-orders-error" style="display:none;"></div>
                <br/>
                <div class="{$alert_class.warning|escape:'htmlall':'UTF-8'} col-lg-12" id="accept-orders-warning" style="display:none"></div>
                <br/>
            </div>

            <hr style="width:30%;margin-bottom:15px;"/>


            <div class="form-group">
                <div class="margin-form col-lg-offset-3">
                    <input type="hidden" id="accept-order-url" value="{$tab_accept_data.accept_order_url|escape:'htmlall':'UTF-8'}"/>
                    <input type="hidden" id="text-select-orders" value="{l s='Please select one or more orders to accept !' mod='priceminister'}"/>
                    <input type="button" id="accept-order-1" value="{l s='Accept Selected Orders' mod='priceminister'}" class="button btn btn-default accept-order-button"/><br/>
                </div>
            </div>


            <div class="form-group">
                <div class="form-group col-lg-offset-3">

                    <table style="display:none;">
                        <tr class="order-item item-model">
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td colspan="6">
                                <table style="width:100%">
                                    <tr class="row_hover order-items" style="display:none;">

                                        <td class="center" rel="checkbox">
                                            <input type="checkbox" class="item-check" style="display:none;"/>
                                            <img src="{$images|escape:'htmlall':'UTF-8'}soos.png" alt="{l s='Out of stock' mod='priceminister'}" rel="oos" style="display:none;"/>
                                            <img src="{$images|escape:'htmlall':'UTF-8'}cross.png" alt="{l s='Unknown Product' mod='priceminister'}" rel="un" style="display:none;"/>
                                            <img src="{$images|escape:'htmlall':'UTF-8'}valid.png" alt="{l s='Non Importable' mod='priceminister'}" rel="non" style="display:none;"/>
                                        </td>
                                        <td class="left" rel="sku">&nbsp;</td>
                                        <td class="left" rel="itemid">&nbsp;</td>
                                        <td class="left" rel="itemstatus">&nbsp;</td>
                                        <td class="left" rel="headline">&nbsp;</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <table class="table order table-hover" cellpadding="0" cellspacing="0"
                           style="width: 100%; margin-bottom:10px;">

                        <thead class="order-table-heading" style="display:none">
                            <tr class="active">
                                <th class="center" width="20px"><input type="checkbox"/></th>

                                <th class="left">{l s='Date' mod='priceminister'}</th>
                                <th class="left">{l s='ID' mod='priceminister'}</th>
                                <th class="left">{l s='Customer' mod='priceminister'}</th>
                                <th class="left">{l s='Shipping' mod='priceminister'}</th>
                                <th class="center">FF</th>
                                <th class="center">{l s='Qty' mod='priceminister'}</th>
                                <th class="center">{l s='Total' mod='priceminister'}</th>
                            </tr>
                        </thead>
                        <tbody class="orders">
                            <tr class="row_hover order-model" style="display:none;">
                                <td class="left" rel="checkbox"><input type="checkbox" class="order-check"/></td>
                                <td class="left" rel="date">&nbsp;</td>
                                <td class="left order-link" rel="id">&nbsp;</td>
                                <td class="left" rel="customer">&nbsp;</td>
                                <td class="left" rel="shipping">&nbsp;</td>
                                <td class="left" rel="fulfillment">&nbsp;</td>
                                <td class="center" rel="quantity">&nbsp;</td>
                                <td class="right" rel="total">&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>

            <div class="form-group">
                <div class="margin-form col-lg-offset-3">
                    <input type="button" id="accept-order-2" value="{l s='Accept Selected Orders' mod='priceminister'}" class="button btn accept-order-button btn-default"/><br/>
                </div>
            </div>

            <br/>
            <hr style="width:30%;margin-top:15px;"/>
            <br/>

            <ul id="accept-legend" style="display:none">
                <li>{l s='Legend' mod='priceminister'}</li>
                <li>
                    <img src="{$images|escape:'htmlall':'UTF-8'}icon_valid_16.png" rel="imported" alt="{l s='Already Imported' mod='priceminister'}"/>&nbsp;{l s='Already Imported' mod='priceminister'}
                </li>
                <li>
                    <img src="{$images|escape:'htmlall':'UTF-8'}soos.png" rel="oos" alt="{l s='Out of stock' mod='priceminister'}"/>&nbsp;{l s='Out of stock' mod='priceminister'}
                </li>
                <li>
                    <img src="{$images|escape:'htmlall':'UTF-8'}valid.png" rel="nonimp" alt="{l s='Non Importable' mod='priceminister'}"/>&nbsp;{l s='Non Importable' mod='priceminister'}
                </li>
                <li>
                    <img src="{$images|escape:'htmlall':'UTF-8'}cross.png" rel="unkn" alt="{l s='Unknown Product' mod='priceminister'}"/>&nbsp;{l s='Unknown Product' mod='priceminister'}
                </li>
            </ul>
        </form>
    </div>
{/if}    