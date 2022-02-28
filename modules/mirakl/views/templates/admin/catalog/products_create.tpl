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

{if $tab_create_data} 
    <div id="menudiv-create"  class="{$tab_create_data.tab_selected_create|escape:'htmlall':'UTF-8'}" rel="create" style="display:none;">
        <h2 style="text-align:center;margin:10px 0 0 0;">{l s='Creation Mode' mod='mirakl'}</h2>
        <h3 style="text-align:center;margin:0;">{$tab_create_data.last_export_title|escape:'htmlall':'UTF-8'}{$tab_create_data.last_cron_export_title|escape:'htmlall':'UTF-8'}</h3>

        <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}" style="margin-top: 17px;">
            {l s='The module does not allow to create products by API.' mod='mirakl'}<br />
            {l s='To create your products you need to generate the CSV file here, download it and import it into your Mirakl back office.' mod='mirakl'}<br />
            {l s='There will be a mapping to be done to match the columns of your CSV with the data requested by Mirakl and that\'s it.' mod='mirakl'}<br />
            {l s='You only need to do this mapping once, after that it is automatic.' mod='mirakl'}<br />
            {* 2021-05-17: Remove outdated mapping documentation *}
        </div>
        
        <form action="{$tab_create_data.request_uri|escape:'htmlall':'UTF-8'}" method="post" id="create-products-form">
            <div class="form-group">
            <label class="control-label col-lg-3" style="color:grey">{l s='Parameters' mod='mirakl'}</label>
                <div class="margin-form col-lg-9">
            <br />
                </div>
            </div>

            <!-- Marketplace selector -->
            {include file="./../marketplace_selection.tpl"}
            
            <div class="form-group">
            <label class="control-label col-lg-3">{l s='Export All Products (ignore last export date)' mod='mirakl'}</label>
                <div class="margin-form col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="create-all-products" id="create-all-products_on" value="1" checked /><label for="create-all-products_on" class="label-checkbox">{l s='Yes' mod='mirakl'}</label>
                        <input type="radio" name="create-all-products" id="create-all-products_off" value="0" /><label for="create-all-products_off" class="label-checkbox">{l s='No' mod='mirakl'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    
                </div>
            </div>
            <div class="form-group">
            <label class="control-label col-lg-3">{l s='Only In Stock Products' mod='mirakl'}</label>
                <div class="margin-form col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="create-in-stock" id="create-in-stock_on" value="1" /><label for="create-in-stock_on" class="label-checkbox">{l s='Yes' mod='mirakl'}</label>
                        <input type="radio" name="create-in-stock" id="create-in-stock_off" value="0" checked /><label for="create-in-stock_off" class="label-checkbox">{l s='No' mod='mirakl'}</label>
                        <a class="slide-button btn"></a>
                    </span>
            </div>
            </div>

{*            <div class="form-group">*}
{*                <label class="control-label col-lg-3">{l s='Send to Mirakl' mod='mirakl'}</label>*}
{*                <div class="margin-form col-lg-9">*}
{*                    <span class="switch prestashop-switch fixed-width-lg">*}
{*                        <input type="radio" name="send-products" id="send_on" value="1" /><label for="send_on" class="label-checkbox">{l s='Yes' mod='mirakl'}</label>*}
{*                        <input type="radio" name="send-products" id="send_off" value="0" checked /><label for="send_off" class="label-checkbox">{l s='No' mod='mirakl'}</label>*}
{*                        <a class="slide-button btn"></a>*}
{*                    </span>*}
{*                </div>*}
{*            </div>*}

            {*<div class="form-group">*}
                {*<label class="control-label col-lg-3">{l s='Categories' mod='mirakl'}</label>*}
                {*<div class="margin-form col-lg-9">*}
                    {*<br>*}
                    {*{foreach $tab_create_data.categories as $id_category => $name}*}
                        {*<input type="checkbox" name="categories[]" value="{$id_category}" checked> {$name}<br>*}
                    {*{/foreach}*}
                {*</div>*}
            {*</div>*}

            <input type="hidden" name="last-create" value="{$tab_create_data.last_export|escape:'htmlall':'UTF-8'}" />
            {*<input type="hidden" name="send-products" id="send_on" value="0" />*}

            <hr style="width:600px;margin-bottom:25px;" />
            
            <div class="{$alert_class.success|escape:'htmlall':'UTF-8'}" id="create-products-result" style="display:none"></div>
            <div class="{$alert_class.warning|escape:'htmlall':'UTF-8'}" id="create-products-warning" style="display:none"></div>
            <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'}" id="create-products-error" style="display:none"></div>
            <input type="hidden" id="create-products-url" value="{$tab_create_data.products_url|escape:'htmlall':'UTF-8'}" />
            <input type="button" id="create-products" value="{l s='Export' mod='mirakl'}" class="button btn btn-default" /><br /><br />

            <hr style="width:600px;margin-bottom:25px;display:none" id="create-products-latest-hr" />
            <div class="{$alert_class.success|escape:'htmlall':'UTF-8'}" id="create-products-latest" style="display:none"></div>
            <br />
            <br />
            <div class="form-group">
                <div class="margin-form col-lg-12" id="create-loader">
                    &nbsp;
                </div>
            </div>
        </form>        
    </div>
{/if}    