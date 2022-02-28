{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Feed.biz
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Feed.biz is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Feed.biz.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 *
 * @package   Amazon Market Place
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * Support by mail:  support.amazon@common-services.com
*}
<div id="menudiv-parameters" class="tabItem {if $parameters.selected_tab}selected{/if} panel form-horizontal">
    <h3>{l s='Parameters' mod='amazon'}</h3>

    {if !$amazon.is_lite}
    <div class="form-group">
        <div class="margin-form">
            <div class="amz-info-level-info {if $psIsGt15}alert alert-info col-lg-offset-3{/if}" style="font-size:1.1em">
                <ul>
                    <li>{l s='Please read our online tutorial' mod='amazon'}:</li>
                    <li>{$parameters.tutorial|escape:'quotes':'UTF-8'}</li>
                </ul>
            </div>
        </div>
    </div>
    {/if}

    <div class="form-group">

        <!-- General Settings -->

        <label class="control-label col-lg-3" style="color:grey;">{l s='General Settings' mod='amazon'}</label>

        <div class="col-lg-9">
            <span class="config-type">[ + ] {l s='Advanced Settings' mod='amazon'}</span>
            <span class="config-type" style="display:none">[ - ] {l s='Quick Settings' mod='amazon'}</span><br/><br/>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3" rel="discount"><span>{l s='Discount/Specials' mod='amazon'}</span></label>

        <div class="margin-form col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="specials" id="special-chk-1" value="1"
                               {if $parameters.settings.discount}checked="checked"{/if} /><label for="special-chk-1"
                                                                                                 class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                        <input type="radio" name="specials" id="special-chk-2" value="0"
                               {if (!$parameters.settings.discount)}checked="checked"{/if} /><label for="special-chk-2"
                                                                                                    class="label-checkbox">{l s='No' mod='amazon'}</label>
                        <a class="slide-button btn"></a>
                    </span>

            <div rel="amazon-expert-mode" class="amazon-expert-mode">
                <span id="specials-apply-rules-section" {if (!$parameters.settings.discount)}style="display:none"{/if}>
                            <input type="checkbox" value="1" name="specials_apply_rules" id="specials_apply_rules"
                                   style="margin-left:10px"
                                   {if ($parameters.settings.specials_apply_rules)}checked="checked"{/if} /><label
                            for="specials_apply_rules"
                            class="label-checkbox">{l s='Apply price rules to promotions/sales if checked' mod='amazon'}
                        <span class="expert">{l s='Expert' mod='amazon'}</span></label>
                        </span>

            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3" rel="preorder"><span>{l s='Preorder' mod='amazon'}</span></label>

        <div class="margin-form col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="preorder" id="preorder-chk-1" value="1"
                               {if $parameters.settings.preorder}checked="checked"{/if} /><label for="preorder-chk-1"
                                                                                                 class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                        <input type="radio" name="preorder" id="preorder-chk-2" value="0"
                               {if (!$parameters.settings.preorder)}checked="checked"{/if} /><label for="preorder-chk-2"
                                                                                                    class="label-checkbox">{l s='No' mod='amazon'}</label>
                        <a class="slide-button btn"></a>
                    </span>


        </div>
    </div>

    <div rel="amazon-expert-mode" class="amazon-expert-mode">
        <div class="form-group">
            <label class="control-label col-lg-3" rel="account_type"><span>{l s='Account Type' mod='amazon'}</span><sup
                        class="expert">{l s='Expert' mod='amazon'}</sup></label>

            <div class="margin-form col-lg-9">
                <input type="radio" name="account_type"
                       value="{$parameters.settings.account_type_global_value|escape:'htmlall':'UTF-8'}"
                       {if $parameters.settings.account_type_global_selected}checked{/if} />&nbsp;<span
                        class="span_text"">&nbsp;{l s='Global' mod='amazon'}</span>
                <input type="radio" name="account_type"
                       value="{$parameters.settings.account_type_individual_value|escape:'htmlall':'UTF-8'}"
                       {if $parameters.settings.account_type_individual_selected}checked{/if}
                       style="margin-left:15px;"/>&nbsp;<span
                        class="span_text">&nbsp;{l s='Individual' mod='amazon'}</span>


            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3" rel="title_format"><span>{l s='Title Format' mod='amazon'}</span></label>
        <div class="margin-form col-lg-9">
            <input type="radio" name="title_format"
                   value="{$parameters.settings.title_format_value_1|escape:'htmlall':'UTF-8'}"
                   {if $parameters.settings.title_format_selected_1}checked{/if} />&nbsp;<span
                    class="span_text">&nbsp;{l s='Standard Title, Attributes' mod='amazon'}</span>&nbsp;&nbsp;&nbsp;
            <input type="radio" style="margin-left:15px;" name="title_format"
                   value="{$parameters.settings.title_format_value_2|escape:'htmlall':'UTF-8'}"
                   {if $parameters.settings.title_format_selected_2}checked{/if} />&nbsp;<span
                    class="span_text">&nbsp;{l s='Manufacturer, Title, Attributes' mod='amazon'}</span>&nbsp;&nbsp;&nbsp;
            <input type="radio" style="margin-left:15px;" name="title_format"
                   value="{$parameters.settings.title_format_value_3|escape:'htmlall':'UTF-8'}"
                   {if $parameters.settings.title_format_selected_3}checked{/if} />&nbsp;<span
                    class="span_text">&nbsp;{l s='Manufacturer, Title, Reference, Attributes' mod='amazon'}</span>
        </div>
    </div>

    {* Taxes option *}
    <div class="form-group">
        <label class="control-label col-lg-3" rel="taxe"><span>{l s='Taxes' mod='amazon'}</span></label>
        <div class="margin-form col-lg-2">
            <span class="switch prestashop-switch fixed-width-lg">
                <input type="radio" name="taxes" id="taxes-1" value="1" {if $parameters.settings.taxes}checked{/if} />
                <label for="taxes-1" class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                <input type="radio" name="taxes" id="taxes-2" value="0" {if (!$parameters.settings.taxes)}checked{/if} />
                <label for="taxes-2" class="label-checkbox">{l s='No' mod='amazon'}</label>
                <a class="slide-button btn"></a>
            </span>
        </div>
    </div>
    <div class="form-group available_if_tax_enable {if !$parameters.settings.taxes}hidden{/if}">
        <div class="row">
            <label class="control-label col-lg-offset-3 col-lg-2">
                <span>{l s='Comply EU Vat rules' mod='amazon'}</span>
            </label>
            <div class="margin-form col-lg-2">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="taxes_comply_eu_vat_rules" id="taxes-comply-eu-vat-rules-1"
                           value="1" {if $parameters.settings.taxes_comply_eu_vat_rules}checked{/if} />
                    <label for="taxes-comply-eu-vat-rules-1" class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                    <input type="radio" name="taxes_comply_eu_vat_rules" id="taxes-comply-eu-vat-rules-2"
                           value="0" {if !$parameters.settings.taxes_comply_eu_vat_rules}checked{/if} />
                    <label for="taxes-comply-eu-vat-rules-2" class="label-checkbox">{l s='No' mod='amazon'}</label>
                    <a class="slide-button btn"></a>
                </span>
            </div>
        </div>
        <div class="row">
            <label class="control-label col-lg-offset-3 col-lg-2">
                <span>{l s='Force tax on business orders' mod='amazon'}</span>
            </label>
            <div class="margin-form col-lg-2">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="taxes_on_business_orders" id="taxes-on-business-orders-1"
                           value="1" {if $parameters.settings.taxes_on_business_orders}checked{/if} />
                    <label for="taxes-on-business-orders-1" class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                    <input type="radio" name="taxes_on_business_orders" id="taxes-on-business-orders-2"
                           value="0" {if !$parameters.settings.taxes_on_business_orders}checked{/if} />
                    <label for="taxes-on-business-orders-2" class="label-checkbox">{l s='No' mod='amazon'}</label>
                    <a class="slide-button btn"></a>
                </span>
            </div>
        </div>
        <div class="row">
            <label class="control-label col-lg-offset-3 col-lg-2" rel="oi_force_taxes_recalculation">
                <span>{l s='Force taxes recalculation' mod='amazon'}</span>
            </label>
            <div class="margin-form col-lg-2">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="force_vat_recalculation" id="force-vat-recalculation-1"
                           value="1" {if $parameters.settings.force_vat_recalculation}checked{/if} />
                    <label for="force-vat-recalculation-1" class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                    <input type="radio" name="force_vat_recalculation" id="force-vat-recalculation-2"
                           value="0" {if !$parameters.settings.force_vat_recalculation}checked{/if} />
                    <label for="force-vat-recalculation-2" class="label-checkbox">{l s='No' mod='amazon'}</label>
                    <a class="slide-button btn"></a>
                </span>
            </div>
        </div>
    </div>
    {* End Taxes option *}

    <div>
        <!-- Advanced Settings -->
        <div class="advanced-settings" style="display:none">

            {* Include attribute name in combination title *}
            <div class="form-group">
                <label class="control-label col-lg-3" rel="attribute_name_in_title"><span>{l s='Include attribute name in title' mod='amazon'}</span></label>
                <div class="margin-form col-lg-2">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="attribute_name_in_title" id="attribute_name_in_title-1" value="1" {if $parameters.settings.attribute_name_in_title}checked{/if} />
                        <label for="attribute_name_in_title-1" class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                        <input type="radio" name="attribute_name_in_title" id="attribute_name_in_title-2" value="0" {if (!$parameters.settings.attribute_name_in_title)}checked{/if} />
                        <label for="attribute_name_in_title-2" class="label-checkbox">{l s='No' mod='amazon'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                </div>
            </div>


            <div class="form-group">
                <label class="control-label col-lg-3"
                       rel="delete_product"><span>{l s='Delete Products' mod='amazon'}</span></label>

                <div class="margin-form col-lg-9">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="delete_products" id="delete_products-1" value="1"
                                       {if $parameters.settings.delete_products}checked="checked"{/if} /><label
                                        for="delete_products-1" class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                                <input type="radio" name="delete_products" id="delete_products-2" value="0"
                                       {if (!$parameters.settings.delete_products)}checked="checked"{/if} /><label
                                        for="delete_products-2" class="label-checkbox">{l s='No' mod='amazon'}</label>
                                <a class="slide-button btn"></a>
                            </span>


                </div>
            </div>


            <div class="form-group">
                <label class="control-label col-lg-3"
                       rel="desc_field"><span>{l s='Description Field' mod='amazon'}</span></label>

                <div class="margin-form col-lg-9">
                    <input type="radio" name="description_field"
                           value="{$parameters.settings.description_field_value_1|escape:'htmlall':'UTF-8'}"
                           {if $parameters.settings.description_field_selected_1}checked{/if} />&nbsp;<span
                            class="span_text">&nbsp;{l s='Description' mod='amazon'}</span>&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="description_field"
                           value="{$parameters.settings.description_field_value_2|escape:'htmlall':'UTF-8'}"
                           {if $parameters.settings.description_field_selected_2}checked{/if} />&nbsp;<span
                            class="span_text">&nbsp;{l s='Short Description' mod='amazon'}</span>&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="description_field"
                           value="{$parameters.settings.description_field_value_3|escape:'htmlall':'UTF-8'}"
                           {if $parameters.settings.description_field_selected_3}checked{/if} />&nbsp;<span
                            class="span_text">&nbsp;{l s='Both' mod='amazon'}</span>&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="description_field"
                           value="{$parameters.settings.description_field_value_4|escape:'htmlall':'UTF-8'}"
                           {if $parameters.settings.description_field_selected_4}checked{/if} />&nbsp;<span
                            class="span_text">&nbsp;{l s='None' mod='amazon'}</span>&nbsp;&nbsp;&nbsp;

                </div>
            </div>

            <!-- Expert Mode under Advanced Settings -->
            <div rel="amazon-expert-mode" class="amazon-expert-mode">

                <div class="form-group">
                    <label class="control-label col-lg-3" rel="html"><span>{l s='HTML Descriptions' mod='amazon'}</span><sup
                                class="expert">{l s='Expert' mod='amazon'}</sup></label>

                    <div class="margin-form col-lg-9">
                                <span class="switch prestashop-switch fixed-width-lg">
                                    <input type="radio" name="html_descriptions" id="html_descriptions-1" value="1"
                                           {if $parameters.settings.html_descriptions}checked="checked"{/if} /><label
                                            for="html_descriptions-1"
                                            class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                                    <input type="radio" name="html_descriptions" id="html_descriptions-2" value="0"
                                           {if (!$parameters.settings.html_descriptions)}checked="checked"{/if} /><label
                                            for="html_descriptions-2"
                                            class="label-checkbox">{l s='No' mod='amazon'}</label>
                                    <a class="slide-button btn"></a>
                                </span>
                    </div>
                </div>

                {*Aug-23-2018: Remove Carriers/Modules option*}

                <div class="form-group">
                    <label class="control-label col-lg-3"
                           rel="safe_encode"><span>{l s='Safe Encoding' mod='amazon'}</span><sup
                                class="expert">{l s='Expert' mod='amazon'}</sup></label>

                    <div class="margin-form col-lg-9">
                                <span class="switch prestashop-switch fixed-width-lg">
                                    <input type="radio" name="safe_encoding" id="safe_encoding-1" value="1"
                                           {if $parameters.settings.safe_encoding}checked="checked"{/if} /><label
                                            for="safe_encoding-1"
                                            class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                                    <input type="radio" name="safe_encoding" id="safe_encoding-2" value="0"
                                           {if (!$parameters.settings.safe_encoding)}checked="checked"{/if} /><label
                                            for="safe_encoding-2" class="label-checkbox">{l s='No' mod='amazon'}</label>
                                    <a class="slide-button btn"></a>
                                </span>


                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3"
                           rel="priceonly"><span>{l s='Prices Only' mod='amazon'}</span><sup
                                class="expert">{l s='Expert' mod='amazon'}</sup></label>

                    <div class="margin-form col-lg-9">
                                <span class="switch prestashop-switch fixed-width-lg">
                                    <input type="radio" name="prices_only" id="prices_only-1" value="1"
                                           {if $parameters.settings.prices_only}checked="checked"{/if} /><label
                                            for="prices_only-1" class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                                    <input type="radio" name="prices_only" id="prices_only-2" value="0"
                                           {if (!$parameters.settings.prices_only)}checked="checked"{/if} /><label
                                            for="prices_only-2" class="label-checkbox">{l s='No' mod='amazon'}</label>
                                    <a class="slide-button btn"></a>
                                </span>


                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3"
                           rel="stockonly"><span>{l s='Stock Only' mod='amazon'}</span><sup
                                class="expert">{l s='Expert' mod='amazon'}</sup></label>

                    <div class="margin-form col-lg-9">
                        <input type="hidden" name="stock_only_changed"
                               value="{l s='ATTENTION: Your prices will not be sent anymore on Amazon !' mod='amazon'}"/>
                                <span class="switch prestashop-switch fixed-width-lg">
                                    <input type="radio" name="stock_only" id="stock_only-1" value="1"
                                           {if $parameters.settings.stock_only}checked="checked"{/if} /><label
                                            for="stock_only-1" class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                                    <input type="radio" name="stock_only" id="stock_only-2" value="0"
                                           {if (!$parameters.settings.stock_only)}checked="checked"{/if} /><label
                                            for="stock_only-2" class="label-checkbox">{l s='No' mod='amazon'}</label>
                                    <a class="slide-button btn"></a>
                                </span>

                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 glossary_target" rel="product_update_condition_ignore">
                        <span>{l s='Ignore product condition' mod='amazon'}</span>
                        <sup class="expert">{l s='Expert' mod='amazon'}</sup>
                    </label>
                    <div class="margin-form col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="product_update_condition_ignore" id="product_update_condition_ignore-1"
                                   value="1" {if $parameters.settings.product_update_condition_ignore}checked="checked"{/if} />
                            <label for="product_update_condition_ignore-1" class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                            <input type="radio" name="product_update_condition_ignore" id="product_update_condition_ignore-2"
                                   value="0" {if (!$parameters.settings.product_update_condition_ignore)}checked="checked"{/if} />
                            <label for="product_update_condition_ignore-2" class="label-checkbox">{l s='No' mod='amazon'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3"
                           rel="origine_payment"><span>{l s='Payment Region' mod='amazon'}</span><sup
                                class="expert">{l s='Expert' mod='amazon'}</sup></label>

                    <div class="margin-form col-lg-9">
                                <span class="switch prestashop-switch fixed-width-lg">
                                    <input type="radio" name="payment_region" id="payment_region-1" value="1"
                                           {if $parameters.settings.payment_region}checked="checked"{/if} /><label
                                            for="payment_region-1"
                                            class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                                    <input type="radio" name="payment_region" id="payment_region-2" value="0"
                                           {if (!$parameters.settings.payment_region)}checked="checked"{/if} /><label
                                            for="payment_region-2"
                                            class="label-checkbox">{l s='No' mod='amazon'}</label>
                                    <a class="slide-button btn"></a>
                                </span>

                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3"
                           rel="auto_create_product"><span>{l s='Create Product (Orders Import)' mod='amazon'}</span><sup
                                class="expert">{l s='Expert' mod='amazon'}</sup></label>

                    <div class="margin-form col-lg-9">
                                <span class="switch prestashop-switch fixed-width-lg">
                                    <input type="radio" name="auto_create" id="auto_create" value="1"
                                           {if $parameters.settings.auto_create}checked="checked"{/if} /><label for="auto_create"
                                                                                                          class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                                    <input type="radio" name="auto_create" id="auto_create-2" value="0"
                                           {if (!$parameters.settings.auto_create)}checked="checked"{/if} /><label
                                            for="auto_create-2"
                                            class="label-checkbox">{l s='No' mod='amazon'}</label>
                                    <a class="slide-button btn"></a>
                                </span>
                    </div>
                </div>


                <div class="form-group">
                    <label class="control-label col-lg-3"
                           rel="disp_inactive_lang"><span>{l s='Display Inactives Languages' mod='amazon'}</span><sup
                                class="expert">{l s='Expert' mod='amazon'}</sup></label>

                    <div class="margin-form col-lg-9">
                                <span class="switch prestashop-switch fixed-width-lg">
                                    <input type="radio" name="inactive_languages" id="inactive_languages-1" value="1"
                                           {if $parameters.settings.inactive_languages}checked="checked"{/if} /><label
                                            for="inactive_languages-1"
                                            class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                                    <input type="radio" name="inactive_languages" id="inactive_languages-2" value="0"
                                           {if (!$parameters.settings.inactive_languages)}checked="checked"{/if} /><label
                                            for="inactive_languages-2"
                                            class="label-checkbox">{l s='No' mod='amazon'}</label>
                                    <a class="slide-button btn"></a>
                                </span>


                    </div>
                </div>
                {if isset($parameters.settings.alternative_content)}
                <div class="form-group">
                    <label class="control-label col-lg-3"
                           rel="disp_alternative_content"><span>{l s='Alternate title/description' mod='amazon'}</span><sup
                                class="expert">{l s='Expert' mod='amazon'}</sup></label>

                    <div class="margin-form col-lg-9">
                                <span class="switch prestashop-switch fixed-width-lg">
                                    <input type="radio" name="alternative_content" id="alternative_content-1" value="1"
                                           {if isset($parameters.settings.alternative_content) && $parameters.settings.alternative_content}checked="checked"{/if} /><label
                                            for="alternative_content-1"
                                            class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                                    <input type="radio" name="alternative_content" id="alternative_content-2" value="0"
                                           {if !isset($parameters.settings.alternative_content) || (!$parameters.settings.alternative_content)}checked="checked"{/if} /><label
                                            for="alternative_content-2"
                                            class="label-checkbox">{l s='No' mod='amazon'}</label>
                                    <a class="slide-button btn"></a>
                                </span>


                    </div>
                </div>
                {/if}


                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span>{l s='Disable SSL Check' mod='amazon'}</span>
                        <sup class="expert">{l s='Expert' mod='amazon'}</sup>
                    </label>

                    <div class="margin-form col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="disable_ssl_check" id="disable_ssl_check-1" value="1"
                                   {if $parameters.settings.disable_ssl_check}checked="checked"{/if} />
                            <label for="disable_ssl_check-1" class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                            <input type="radio" name="disable_ssl_check" id="disable_ssl_check-2" value="0"
                                   {if (!$parameters.settings.disable_ssl_check)}checked="checked"{/if} />
                            <label for="disable_ssl_check-2" class="label-checkbox">{l s='No' mod='amazon'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>

                {*todo: Translation*}
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span>{l s='Get configuration without cache' mod='amazon'}</span>
                        <sup class="expert">{l s='Expert' mod='amazon'}</sup>
                    </label>
                    <div class="margin-form col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="parameter_get_config_by_direct_sql" id="vidr-get-config-no-cache-1"
                               value="1" {if $parameters.settings.get_config_no_cache}checked="checked"{/if} />
                        <label for="vidr-get-config-no-cache-1" class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                        <input type="radio" name="parameter_get_config_by_direct_sql" id="vidr-get-config-no-cache-2"
                               value="0" {if !$parameters.settings.get_config_no_cache}checked="checked"{/if} />
                        <label for="vidr-get-config-no-cache-2" class="label-checkbox">{l s='No' mod='amazon'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3 glossary_target" rel="use_vcs_lite">
                        <span>{l s='Use VCS Lite' mod='amazon'}</span>
                        <sup class="expert">{l s='Expert' mod='amazon'}</sup>
                    </label>
                    <div class="margin-form col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="parameter_vidr" id="vidr-1" value="1"
                               {if $parameters.settings.vidr}checked="checked"{/if} />
                        <label for="vidr-1" class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                        <input type="radio" name="parameter_vidr" id="vidr-2" value="0"
                               {if (!$parameters.settings.vidr)}checked="checked"{/if} />
                        <label for="vidr-2" class="label-checkbox">{l s='No' mod='amazon'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    </div>
                </div>
                <div class="form-group available_if_vcs_enable {if !$parameters.settings.vidr}hidden{/if}">
                    <div class="row">
                        <label class="control-label col-lg-offset-3 col-lg-2 glossary_target" rel="vcs_upload_invoice">
                            <span>{l s='Upload VAT invoice' mod='amazon'}</span>
                            <sup class="expert">{l s='Expert' mod='amazon'}</sup>
                        </label>
                        <div class="margin-form col-lg-2">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="parameter_vidr_send_invoice" id="vidr-send-invoice-1" value="1"
                                       {if $parameters.settings.vidr_send_invoice}checked{/if} />
                                <label for="vidr-send-invoice-1" class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                                <input type="radio" name="parameter_vidr_send_invoice" id="vidr-send-invoice-2" value="0"
                                       {if (!$parameters.settings.vidr_send_invoice)}checked{/if} />
                                <label for="vidr-send-invoice-2" class="label-checkbox">{l s='No' mod='amazon'}</label>
                                <a class="slide-button btn"></a>
                            </span>
                        </div>
                    </div>
                    <div class="row">
                        <label class="control-label col-lg-offset-3 col-lg-2 glossary_target" rel="vcs_update_buyer_vat_number">
                            <span>{l s='Update customer VAT number' mod='amazon'}</span>
                            <sup class="expert">{l s='Expert' mod='amazon'}</sup>
                        </label>
                        <div class="margin-form col-lg-2">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="parameter_vidr_update_customer_vat_number" id="vidr-update-customer-vat-number-1" value="1"
                                       {if $parameters.settings.vidr_update_customer_vat_number}checked{/if} />
                                <label for="vidr-update-customer-vat-number-1" class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                                <input type="radio" name="parameter_vidr_update_customer_vat_number" id="vidr-update-customer-vat-number-2" value="0"
                                       {if (!$parameters.settings.vidr_update_customer_vat_number)}checked{/if} />
                                <label for="vidr-update-customer-vat-number-2" class="label-checkbox">{l s='No' mod='amazon'}</label>
                                <a class="slide-button btn"></a>
                            </span>
                        </div>
                    </div>
                    <div class="row">
                        <label class="control-label col-lg-offset-3 col-lg-2 glossary_target" rel="vcs_update_billing_address">
                            <span>{l s='Update billing address' mod='amazon'}</span>
                            <sup class="expert">{l s='Expert' mod='amazon'}</sup>
                        </label>
                        <div class="margin-form col-lg-2">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="parameter_vidr_update_billing_address" id="vidr-update-billing-address-1" value="1"
                                       {if $parameters.settings.vidr_update_billing_address}checked{/if} />
                                <label for="vidr-update-billing-address-1" class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                                <input type="radio" name="parameter_vidr_update_billing_address" id="vidr-update-billing-address-2" value="0"
                                       {if (!$parameters.settings.vidr_update_billing_address)}checked{/if} />
                                <label for="vidr-update-billing-address-2" class="label-checkbox">{l s='No' mod='amazon'}</label>
                                <a class="slide-button btn"></a>
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            {if $parameters.settings.image_type}
                <div class="form-group">
                    <label class="control-label col-lg-3"
                           rel="typeimage"><span>{l s='Image Type' mod='amazon'}</span></label>

                    <div class="margin-form col-lg-9">
                        <select name="image_type" id="image_type" style="width:500px;">
                        <option></option>
                        {foreach from=$parameters.settings.image_type key=image_type item=selected}
                            <option value="{$image_type|escape:'htmlall':'UTF-8'}"
                                    {if $selected}selected{/if}>{$image_type|escape:'htmlall':'UTF-8'}</option>
                        {/foreach}
                        </select>

                    </div>
                </div>
            {/if}


            <div class="form-group">
                <label class="control-label col-lg-3"
                       rel="log_email"><span>{l s='Log by Email' mod='amazon'}</span></label>

                <div class="margin-form col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="email" id="email-1" value="1"
                                   {if $parameters.settings.email}checked="checked"{/if} /><label for="email-1"
                                                                                                  class="label-checkbox">{l s='Yes' mod='amazon'}</label>
                            <input type="radio" name="email" id="email-2" value="0"
                                   {if (!$parameters.settings.email)}checked="checked"{/if} /><label for="email-2"
                                                                                                     class="label-checkbox">{l s='No' mod='amazon'}</label>
                            <a class="slide-button btn"></a>
                        </span>


                </div>
            </div>

        </div>
        <!-- eof div advanced-settings -->

        <!-- eof Expert Mode under Advanced Settings -->
    </div>

    <div class="cleaner"><br/></div>

    <div class="form-groupcol-lg-12">
        <hr class="amz-separator" style="width:30%"/>
    </div>

    <!-- General Settings / Part II -->
    {include file="{$module_path|escape:'htmlall':'UTF-8'}/views/templates/admin/configure/parameters/parameters.order_states.tpl"
    template_path=$module_path|cat:'/views/templates/admin/configure/parameters'
    ps_order_states=$parameters.settings.ps_order_states
    order_states=$parameters.settings.order_states
    osIncomingPossibleAttrs=$parameters.settings.os_incoming_possible_attrs
    osIncomingCombination=$parameters.settings.os_incoming_combination}

{*    Move to Prime tab*}

    <div class="cleaner"><br/></div>

    <div class="form-group col-lg-12">
	<hr class="amz-separator" style="width:30%"/>
    </div>

    <div class="cleaner"><br/></div>

    <div class="form-group">
        <label class="control-label col-lg-3" rel="set_employee"><span>{l s='Employee' mod='amazon'}</span></label>

        <div class="margin-form col-lg-9">
            <select name="employee" style="width:500px;">
                <option value="" disabled="disabled">{l s='Choose' mod='amazon'}</option>
                {foreach from=$parameters.settings.employee key=id_employee item=employee}
                    <option value="{$id_employee|intval}"
                            {if $employee.selected}selected{/if}>{$employee.name|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3"
               rel="customer_group"><span>{l s='Customer Group' mod='amazon'}</span></label>

        <div class="margin-form col-lg-9">
            <select name="id_group" style="width:500px;">
                <option value="" disabled="disabled">{l s='Choose' mod='amazon'}</option>
                {foreach from=$parameters.settings.customer_groups key=id_customer_group item=customer_group}
                    <option value="{$id_customer_group|intval}"
                            {if $customer_group.selected}selected{/if}>{$customer_group.name|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>
        </div>
    </div>

    {if ($parameters.settings.business)}
    <div class="form-group" rel="amazon-business" >
        <label class="control-label col-lg-3"
               rel="business_customer_group"><span>{l s='Business Customer Group' mod='amazon'}</span></label>

        <div class="margin-form col-lg-9">
            <select name="id_business_group" style="width:500px;">
                <option value="" disabled="disabled">{l s='Choose' mod='amazon'}</option>
                <option></option>
                {foreach from=$parameters.settings.customer_groups key=id_customer_group item=customer_group}
                    <option value="{$id_customer_group|intval}"
                            {if $customer_group.business_selected}selected{/if}>{$customer_group.name|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>
        </div>
    </div>
    {/if}

    <div class="form-group">
        <hr class="amz-separator" style="width:30%"/>
    </div>

    {if $parameters.settings.warehouse}
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Warehouse' mod='amazon'}</label>

            <div class="margin-form col-lg-9">
                <select name="warehouse" style="width:500px;">
                    <option value="" disabled="disabled">{l s='Choose' mod='amazon'}</option>
                    {foreach from=$parameters.settings.warehouse key=id_warehouse item=warehouse}
                        <option value="{$id_warehouse|intval}"
                                {if $warehouse.selected}selected{/if}>{$warehouse.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>

                <p>{l s='Choose a warehouse for Amazon products pickup (for Advanced Stock Management)' mod='amazon'}</p>
            </div>
        </div>
        <div class="form-groupcol-lg-12">
            <hr class="amz-separator" style="width:30%"/>
        </div>
    {/if}

    <div class="amazon-second-hand" rel="amazon-second-hand" style="display:none;">
        {if $parameters.settings.product_condition}
            <div class="form-group">
                <label class="control-label col-lg-3"
                       rel="product_cond"><span>{l s='Products Condition' mod='amazon'}</span></label>

                <div class="margin-form condition-map col-lg-9">
                    {foreach from=$parameters.settings.product_conditions key=name item=product_condition}
                        <input type="text" readonly="true" style="width:200px;"
                               value="{$name|escape:'htmlall':'UTF-8'}">
                        <span style="position:relative;top:-4px">&nbsp;&nbsp;
                                <img src="{$parameters.images_url|escape:'quotes':'UTF-8'}next.png"
                                     style="max-height:16px;opacity:0.5" alt=""/>
                                &nbsp;&nbsp;
                            </span>
                        <select name="condition_map[{$name|escape:'htmlall':'UTF-8'}]"
                                id="condition_map-{$product_condition.index|escape:'htmlall':'UTF-8'}"
                                style="width:200px; display: inline;">
                            <option value=""></option>
                            {foreach from=$product_condition.selector key=key item=selector}
                                <option value="{$key|escape:'htmlall':'UTF-8'}"
                                        {if $selector.selected}selected{/if}>{$selector.name|escape:'htmlall':'UTF-8'}</option>
                            {/foreach}
                        </select>
                        <br/>
                    {/foreach}

                </div>
            </div>
        {else}
            <input type="hidden" name="condition_map[New]" value="new"/>
        {/if}
        <div class="form-groupcol-lg-12">
            <hr class="amz-separator" style="width:30%"/>
        </div>
    </div>

    <!-- Amazon Europe -->
    <div rel="amazon-europe" class="amazon-europe" style="display:none;">
        <div class="form-group">
            <label class="control-label col-lg-3"
                   style="color:grey">{l s='Amazon Europe' mod='amazon'}</label><br/><br/>

            <label class="control-label col-lg-3" rel="amazon_europe"><span>{l s='Master Platform' mod='amazon'}</span></label>

            <div class="margin-form col-lg-9">
                <select name="marketPlaceMaster" id="marketPlaceMaster"
                        class="{$parameters.settings.europe.class|escape:'htmlall':'UTF-8'}" style="width:280px">
                    <option value=""
                            disabled="disabled">{l s='Choose the platform for this region' mod='amazon'}</option>
                    <option value=""></option>
                    {foreach from=$parameters.settings.europe.selector key=iso_code item=selector}
                        <option value="{$iso_code|escape:'htmlall':'UTF-8'}"
                                {if $selector.selected}selected{/if}>{$selector.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>


    {$parameters.validation|escape:'quotes':'UTF-8'}

</div>
<!-- eof menudiv parameters -->
