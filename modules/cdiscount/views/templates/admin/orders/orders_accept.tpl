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

{if $tab_accept_data}
    <div id="conf-accept" class="tabItem {$tab_accept_data.selected_tab|escape:'htmlall':'UTF-8'} form-horizontal"
         rel="update">
        <h3 style="text-align:center;margin:10px 0 0 0; border: none;">{l s='Accept Orders' mod='cdiscount'}</h3>
        <h4 style="text-align:center;margin:0;">{l s='Validate Pending Orders' mod='cdiscount'}</h4>
        <hr style="width:60%;margin-top:5px;"/>

        <form action="{$tab_accept_data.request_uri|escape:'htmlall':'UTF-8'}" method="post" id="accept-orders-form">

            {if isset($shop_warning) && $shop_warning}
                <div class="form-group">
                    <label class="control-label col-lg-3">&nbsp;</label>
                    <div class="margin-form col-lg-9">
                        <div class="{$alert_class.warning|escape:'htmlall':'UTF-8'}">
                            {$shop_warning|escape:'htmlall':'UTF-8'}
                        </div>
                    </div>
                </div>
            {/if}

            <div class="form-group">
                <label class="control-label col-lg-3" style="color:grey">{l s='Parameters' mod='cdiscount'}</label>

                <div class="margin-form col-lg-9">
                    <br/>
                    <br/>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Date Range' mod='cdiscount'}</label>

                <div class="margin-form col-lg-9">
                    <input type="text" name="datepickerFromA" id="datepickerFromA"
                           value="{$tab_accept_data.start_date|escape:'htmlall':'UTF-8'}"
                           style="margin-left:5px">
                    <span class="accept-dt">&nbsp;{l s='To' mod='cdiscount'}&nbsp;&nbsp;</span>
                    <input type="text" name="datepickerToA" id="datepickerToA"
                           value="{$tab_accept_data.current_date|escape:'htmlall':'UTF-8'}">
                </div>
            </div>

            <div class="form-group">
                <div class="margin-form col-lg-offset-3">
                    <input type="hidden" id="accept-orders-url"
                           value="{$tab_accept_data.accept_orders_url|escape:'htmlall':'UTF-8'}"/>
                    <input type="button" id="accept-orders" value="{l s='Lookup' mod='cdiscount'}"
                           class="button btn"/><br/><br/>

                    <div id="accept-loader"></div>
                </div>
            </div>

            <hr style="width:600px;display:none" id="accept-orders-hr"/>

            <div class="form-group">
                <div id="accept-orders-success" class="col-lg-offset-3"></div>
            </div>
            <div class="form-group">
                <div class="{$alert_class.success|escape:'htmlall':'UTF-8'} col-lg-12" id="accept-orders-result"
                     style="display:none;"></div>
            </div>
            <br/>

            <div class="form-group">
                <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'} col-lg-12" id="accept-orders-error"
                     style="display:none;">
                </div>
            </div>

            <hr style="width:600px;margin-bottom:15px;"/>

            <div class="form-group">
                <div class="margin-form col-lg-offset-3">
                    <input type="hidden" id="accept-order-url"
                           value="{$tab_accept_data.accept_order_url|escape:'htmlall':'UTF-8'}"/>
                    <input type="button" id="accept-order" value="{l s='Import Selected Orders' mod='cdiscount'}"
                           class="button btn"/><br/>
                    <input type="hidden" id="text-select-orders"
                           value="{l s='Please select one or more orders to accept !' mod='cdiscount'}"/>
                </div>
            </div>

            <div class="form-group">
                <div class="form-group col-lg-offset-3">
                    <table id="accept-table" class="accept-table" style="display:none">
                        <thead>
                        <td align="center"><input type="checkbox" class="select-all"></td>
                        <td>{l s='Order ID' mod='cdiscount'}</td>
                        <td>{l s='Date' mod='cdiscount'}</td>
                        <td>{l s='Name' mod='cdiscount'}</td>
                        <td>{l s='Ship' mod='cdiscount'}</td>
                        <td>{l s='Validation' mod='cdiscount'}</td>
                        <td rel="multichannel" style="display:none">{l s='Channel' mod='cdiscount'}</td>
                        <td rel="clogistique" style="display:none">{l s='C Logistique' mod='cdiscount'}</td>
                        <td>{l s='Status' mod='cdiscount'}</td>
                        <td>{l s='Total' mod='cdiscount'}</td>
                        </thead>
                        <tbody id="accept_order_list">

                        </tbody>
                    </table>
                </div>
            </div>

            <ul id="accept-legend" style="display:none">
                <li>{l s='Legend' mod='cdiscount'}</li>
                <li><img src="{$images|escape:'htmlall':'UTF-8'}icon_valid_16.png" alt="{l s='Already Imported' mod='cdiscount'}"/>&nbsp;{l s='Already Imported' mod='cdiscount'}</li>
                <li><img src="{$images|escape:'htmlall':'UTF-8'}soos.png" alt="{l s='Out of stock' mod='cdiscount'}"/>&nbsp;{l s='Out of stock' mod='cdiscount'}</li>
                <li><img src="{$images|escape:'htmlall':'UTF-8'}valid.png" alt="{l s='Non Importable' mod='cdiscount'}"/>&nbsp;{l s='Non Importable' mod='cdiscount'}</li>
                <li><img src="{$images|escape:'htmlall':'UTF-8'}cross.png" alt="{l s='Unknown Product' mod='cdiscount'}"/>&nbsp;{l s='Unknown Product' mod='cdiscount'}</li>
            </ul>
        </form>
    </div>
{/if}    