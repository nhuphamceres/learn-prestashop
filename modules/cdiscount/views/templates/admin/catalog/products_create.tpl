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
<script type="text/javascript" src="{$module_url|escape:'htmlall':'UTF-8'}views/js/products.js?version={$version|escape:'htmlall':'UTF-8'}"></script>
{if $tab_create_data}
    <div id="conf-create" class="tabItem {$tab_create_data.tab_selected_create|escape:'htmlall':'UTF-8'} form-horizontal" rel="create">
        <input type="hidden" id="create-products-url" value="{$tab_create_data.products_create_url|escape:'htmlall':'UTF-8'}"/>
        <input type="hidden" id="merge-products-url" value="{$tab_create_data.products_merge_url|escape:'htmlall':'UTF-8'}"/>

        <div>
            <h3 style="text-align:center;margin:10px 0 0 0; border: none;">{l s='Creation Mode' mod='cdiscount'}</h3>
            <h4 style="text-align:center;margin:0;">{$tab_create_data.last_export_title|escape:'htmlall':'UTF-8'}</h4>
            <hr style="width:60%;margin-top:5px;"/>
            {if isset($shop_warning) && $shop_warning}
                <div class="form-group">
                    <label class="control-label col-lg-3">&nbsp;</label>
                    <div class="margin-form col-lg-9">
                        <div class="{$alert_class.warning|escape:'htmlall':'UTF-8'}">
                            {$shop_warning|escape:'html':'UTF-8'}
                        </div>
                    </div>
                </div>
            {/if}

            <div class="form-group">
             <div id="create-loader"></div>
            </div>


            <div class="form-group" id="merge-products">
                <label class="control-label col-lg-3"></label>

                <div class="margin-form col-lg-9">
                    <br/>
                    <p>{l s='The module downloads your inventory from CDiscount, please wait a while' mod='cdiscount'}...</p>
                    <div class="{$alert_class.success|escape:'htmlall':'UTF-8'}" id="merge-products-result" style="display:none"></div>
                    <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'}" id="merge-products-error" style="display:none"></div>
                    <br/>
                </div>
            </div>


            <form action="{$tab_create_data.request_uri|escape:'htmlall':'UTF-8'}" method="post" id="create-products-form" style="display:none" >


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
                        <input type="checkbox" name="create-active" value="1" checked="true"/> <span
                                class="ccb-title">{l s='Yes' mod='cdiscount'}</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Only In Stock Products' mod='cdiscount'}</label>

                    <div class="margin-form col-lg-9">
                        <input type="checkbox" name="create-in-stock" value="1" checked="true"/> <span
                                class="ccb-title">{l s='Yes' mod='cdiscount'}</span>
                    </div>
                </div>

                {if $expert_mode}
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Ignore Existing Inventory' mod='cdiscount'}</label>

                    <div class="margin-form col-lg-9">
                        <input type="checkbox" name="ignore-existing" value="1" /> <span
                                class="ccb-title">{l s='Yes' mod='cdiscount'}</span>
                    </div>
                </div>
                {/if}

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Limit' mod='cdiscount'}</label>

                    <div class="margin-form col-lg-9">
                        <select name="limit">
                            <option value="0">{l s='No Limit' mod='cdiscount'}</option>
                            <option value="100">{l s='100 Products' mod='cdiscount'}</option>
                            <option value="500">{l s='500 Products' mod='cdiscount'}</option>
                            <option value="1000">{l s='1000 Products' mod='cdiscount'}</option>
                        </select>
                    <span class="limit-pdt">&nbsp;&nbsp;{l s='Quantity max of products to be exported (depending your PHP configuration)' mod='cdiscount'}
                        &nbsp;&nbsp;</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Date Range' mod='cdiscount'}</label>

                    <div class="margin-form col-lg-9">
                        <span class="update-dt">{l s='From' mod='cdiscount'}&nbsp;&nbsp;</span>
                        <input type="text" name="datepickerFrom2" id="datepickerFrom2" value="{$tab_create_data.last_export|escape:'htmlall':'UTF-8'}">
                        <span class="update-dt">&nbsp;{l s='To' mod='cdiscount'}&nbsp;&nbsp;</span>
                        <input type="text" name="datepickerTo2" id="datepickerTo2" value="{$tab_create_data.current_date|escape:'htmlall':'UTF-8'}">
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
                            {foreach from=$tab_create_data.categories item=category}
                                <tr>
                                    <td><input type="checkbox" name="categories[]"
                                               value="{$category.id_category|escape:'htmlall':'UTF-8'}"
                                               class="category" checked/></td>
                                    <td>{$category.desc_category|escape:'htmlall':'UTF-8'}</td>
                                    <td>{$category.profile_name|escape:'htmlall':'UTF-8'}</td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>


                <hr style="width:600px;margin-bottom:25px;"/>
                <div class="{$alert_class.success|escape:'htmlall':'UTF-8'}" id="create-products-result"
                     style="display:none"></div>
                <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'}" id="create-products-error"
                     style="display:none"></div>
                <br/>

                <input type="button" id="create-products" value="{l s='Export the File' mod='cdiscount'}"
                       class="button btn"/>

                <div class="cleaner"></div>


                <br/>

                <div class="{$alert_class.warning|escape:'htmlall':'UTF-8'}" style="display:block;position:relative;">
                    <p style="font-weight:normal;">
                        {l s='First, please use the CSV Mode and generate a file to check your products informations before submitting to CDiscount.' mod='cdiscount'}
                        <br/>
                        {l s='The CSV file will be usefull and helpfull to verify your catalog consistency' mod='cdiscount'}
                    </p>
                    <hr/>
                    {l s='Warning: Do not submit the file to CDiscount often, please plan your actions before' mod='cdiscount'}
                    <br/>
                    {l s='More you are submitting files, more your submissions will take time ! You have to discuss about this topic with your account manager.' mod='cdiscount'}
                    <br/>
                </div>
                <hr style="width:600px;display:none" id="create-products-latest-hr"/>
                <div id="create-products-latest" style="display:none"></div>
                <div id="send-loader"></div>
            </form>

        </div>

    </div>
{/if}    