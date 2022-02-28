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

{if $tab_update_data}
    <div id="menudiv-update"  class="{$tab_update_data.tab_selected_update|escape:'htmlall':'UTF-8'}" rel="update" >
        <h2 style="text-align:center;margin:10px 0 0 0;">{l s='Update Mode' mod='mirakl'}</h2>
        <h3 style="text-align:center;margin:0;">{$tab_update_data.last_export_title|escape:'htmlall':'UTF-8'}{$tab_update_data.last_cron_export_title|escape:'htmlall':'UTF-8'}</h3>
        
        <form action="{$tab_update_data.request_uri|escape:'htmlall':'UTF-8'}" method="post" id="update-products-form">
            <br />

            <div class="form-group">
                <label class="control-label col-lg-3" style="color:grey">{l s='Feed Type' mod='mirakl'}</label>
                <div class="margin-form col-lg-9">
                    <br />
                </div>
            </div>

            <!-- Marketplace selector -->
            {include file="./../marketplace_selection.tpl"}

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Matching' mod='mirakl'}</label>
                <div class="margin-form col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="matching" id="matching_on" value="1" /><label for="matching_on" class="label-checkbox">{l s='Yes' mod='mirakl'}</label>
                        <input type="radio" name="matching" id="matching_off" value="0" checked /><label for="matching_off" class="label-checkbox">{l s='No' mod='mirakl'}</label>
                        <a class="slide-button btn"></a>
                    </span>

                </div>
            </div>

            <div class="form-group">
            <label class="control-label col-lg-3" style="color:grey">{l s='Parameters' mod='mirakl'}</label>
                <div class="margin-form col-lg-9">
            <br />
                </div>
            </div>


            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Export All Offers (ignore last export date)' mod='mirakl'}</label>
                <div class="margin-form col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="all-offers" id="all-offers_on" value="1" /><label for="all-offers_on" class="label-checkbox">{l s='Yes' mod='mirakl'}</label>
                        <input type="radio" name="all-offers" id="all-offers_off" value="0" checked /><label for="all-offers_off" class="label-checkbox">{l s='No' mod='mirakl'}</label>
                        <a class="slide-button btn"></a>
                    </span>

                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Purge and Replace' mod='mirakl'}</label>
                <div class="margin-form col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="replace" id="replace_on" value="1" /><label for="replace_on" class="label-checkbox">{l s='Yes' mod='mirakl'}</label>
                        <input type="radio" name="replace" id="replace_off" value="0" checked /><label for="replace_off" class="label-checkbox">{l s='No' mod='mirakl'}</label>
                        <a class="slide-button btn"></a>
                    </span>

                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Only Active Offers' mod='mirakl'}</label>
                <div class="margin-form col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="active-only" id="active-only_on" value="1" /><label for="active-only_on" class="label-checkbox">{l s='Yes' mod='mirakl'}</label>
                        <input type="radio" name="active-only" id="active-only_off" value="0" checked /><label for="active-only_off" class="label-checkbox">{l s='No' mod='mirakl'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Send to Mirakl' mod='mirakl'}</label>
                <div class="margin-form col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="send-offers" id="send_offers_on" value="1" checked /><label for="send_offers_on" class="label-checkbox">{l s='Yes' mod='mirakl'}</label>
                        <input type="radio" name="send-offers" id="send_offers_off" value="0"  /><label for="send_offers_off" class="label-checkbox">{l s='No' mod='mirakl'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                </div>
            </div>


            <input type="hidden" name="last-update" value="{$tab_update_data.last_export|escape:'htmlall':'UTF-8'}" />

            <hr style="width:600px;margin-bottom:25px;" />
            
            <div class="{$alert_class.success|escape:'htmlall':'UTF-8'}" id="update-products-result" style="display:none"></div>
            <div class="{$alert_class.warning|escape:'htmlall':'UTF-8'}" id="update-products-warning" style="display:none"></div>
            <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'}" id="update-products-error" style="display:none"></div>
            <input type="hidden" id="update-products-url" value="{$tab_update_data.products_url|escape:'htmlall':'UTF-8'}" />
            <input type="button" id="update-products" value="{l s='Export' mod='mirakl'}" class="button btn btn-default" /><br /><br />

            <hr style="width:600px;margin-bottom:25px;display:none" id="update-products-latest-hr" />
            <div class="{$alert_class.success|escape:'htmlall':'UTF-8'}" id="update-products-latest" style="display:none"></div>
            <br />
            <br />
            <div class="form-group">
                <div class="margin-form col-lg-12" id="update-loader">
                    &nbsp;
                </div>
            </div>
        </form>        
    </div>
{/if}                
