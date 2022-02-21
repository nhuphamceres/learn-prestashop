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

{if $tab_update_data}
    <div id="conf-update"
         class="tabItem {$tab_update_data.tab_selected_update|escape:'htmlall':'UTF-8'} form-horizontal" rel="update">
        <h3 style="text-align:center;margin:10px 0 0 0; border: none;">{l s='Update Mode' mod='cdiscount'}</h3>
        <hr style="width:60%;margin-top:5px;"/>


        <form action="{$tab_update_data.request_uri|escape:'htmlall':'UTF-8'}" method="post" id="update-products-form">

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
                <label class="control-label col-lg-3" style="color:grey">{l s='Parameters' mod='cdiscount'}</label>

                <div class="margin-form col-lg-9">
                    <br/>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Send All Offers' mod='cdiscount'}</label>

                <div class="margin-form col-lg-9">
                    <input type="checkbox" name="all-offers" value="1"/> <span
                            class="ccb-title">{l s='Yes, send all the offers' mod='cdiscount'}</span>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Purge/Replace' mod='cdiscount'}</label>

                <div class="margin-form col-lg-9">
                    <input type="checkbox" name="purge-replace" id="purge-replace" value="1"/> <span
                            class="ccb-title">{l s='Yes, clear all my inventory and send a new one' mod='cdiscount'}</span>
                    <span id="purge-warning">{l s='Be carefull, by choosing this option, you will override your entire catalog !' mod='cdiscount'}</span>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Do Not Send' mod='cdiscount'}</label>

                <div class="margin-form col-lg-9">
                    <input type="checkbox" name="do-not-send" value="1"/> <span
                            class="ccb-title">{l s='Yes, Generate the file only and do not send it to CDiscount' mod='cdiscount'}</span>
                </div>
            </div>

            <hr style="width:60%;margin-bottom:25px;"/>

            <div id="update-loader"></div>

            <div class="{$alert_class.success|escape:'htmlall':'UTF-8'}" id="update-products-result"
                 style="display:none"></div>
            <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'}" id="update-products-error"
                 style="display:none"></div>

            {if (isset($tab_update_data.multitenants) && is_array($tab_update_data.multitenants))}
            <span id="update-products-channels">
                <table>
                    <tr>
                        <td>{l s='Channel' mod='cdiscount'}&nbsp;</td>
                        <td>
                            <select id="update-products-channel" name="channel">
                                {foreach from=$tab_update_data.multitenants item=multitenant}
                                    {if isset($multitenant.Checked) && $multitenant.Checked}
                                        <option value="{$multitenant.Id|escape:'htmlall':'UTF-8'}" {if ($multitenant.Id == 1)}selected{/if}>{$multitenant.Description|escape:'htmlall':'UTF-8'}</option>
                                    {/if}
                                {/foreach}
                            </select>
                        </td>

                    </tr>
                </table>
            </span>
            {/if}
            <input type="hidden" id="update-products-url" value="{$tab_update_data.products_url|escape:'htmlall':'UTF-8'}"/>
            <input type="button" id="update-products" value="{l s='Update CDiscount' mod='cdiscount'}" class="button btn"/>

            <div class="cleaner"></div>

            <hr style="width:60%;margin-bottom:25px;"/>

            <div id="update-products-history" style="display:none">
                &nbsp;
            </div>
            <br/>

        </form>
    </div>
{/if}