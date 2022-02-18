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
* @package   PriceMinister
* @author    Olivier B. / Debusschere A.
* @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
* @license   Commercial license
* Contact by Email :  support.priceminister@common-services.com
*}

<link href="{$module_url|escape:'quotes':'UTF-8'}views/css/product_tab.css?v={$version|escape:'htmlall':'UTF-8'}" rel="stylesheet" type="text/css"/>

{if isset($shop_warning) && $shop_warning}
    <div class="form-group">
        <div class="margin-form col-lg-12">
            <div class="{$class_warning|escape:'htmlall':'UTF-8'}">
                {$shop_warning|escape:'htmlall':'UTF-8'}
            </div>
        </div>
    </div>
{else}
    <div id="pm-product-tab">
        <script type="text/javascript" src="{$module_url|escape:'quotes':'UTF-8'}views/js/product_tab.js"></script>

        <input type="hidden" value="{$json_url|escape:'quotes':'UTF-8'}" id="pm-product-options-json-url"/>
        <input type="hidden" value="{l s='Parameters successfully saved' mod='priceminister'}" id="pm-product-options-message-success"/>
        <input type="hidden" value="{l s='Unable to save parameters...' mod='priceminister'}" id="pm-product-options-message-error"/>
        <input type="hidden" value="{l s='Copied' mod='priceminister'}" id="pm-product-options-copy"/>
        <input type="hidden" value="{l s='Pasted' mod='priceminister'}" id="pm-product-options-paste"/>

        <input type="hidden" class="marketplace-text-propagate-cat" value="{l s='Be careful ! Are you sure to want set this value for all the products of this Category ?' mod='priceminister'}"/>
        <input type="hidden" class="marketplace-text-propagate-shop" value="{l s='Be careful ! Are you sure to want to set this value for all the products of the entire Shop ?' mod='priceminister'}"/>
        <input type="hidden" class="marketplace-text-propagate-man" value="{l s='Be careful ! Are you sure to want to set this value for all the products for this Manufacturer ?' mod='priceminister'}"/>

        <div id="pm-global-values">
            <input type="hidden" name="pm_token" value="{$pm_token|escape:'htmlall':'UTF-8'}" id="token"/>
            <input type="hidden" name="pm_id_product" value="{$product_tab.id_product|intval}" id="id-product"/>
            <input type="hidden" name="pm_id_manufacturer" value="{$product_tab.id_manufacturer|intval}"/>
            <input type="hidden" name="pm_id_category_default" value="{$product_tab.id_category_default|intval}"/>
            <input type="hidden" name="pm_id_supplier" value="{$product_tab.id_supplier|intval}"/>
        </div>

        <div class="panel">
            <h3 class="tab">{l s='Product' mod='priceminister'}</h3>
            <div class="form-group" style="margin-bottom: 25px;">
                <table id="pm-table-product" class="table pm-item">
                    <thead>
                        <tr class="nodrag nodrop">
                            <th>
                            </th>
                            <th class="left title">
                                <span class="title_box">{l s='Name'}</span><!-- Validation: Prestashop translations -->
                            </th>
                            <th class="left reference">
                                <span class="title_box">{l s='Reference code'}</span><!-- Validation: Prestashop translations -->
                            </th>
                            <th class="left reference">
                                <span class="title_box">EAN13</span>
                            </th>
                            <th class="left reference">
                                <span class="title_box">UPC</span>
                            </th>
                            <th class="center action"></th>
                            <th class="center action"></th>
                            <th class="center action"></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr class="highlighted" rel="{$product_tab.id_product|escape:'htmlall':'UTF-8'}_0">
                            <td class="left">
                                <input type="radio" id="pm-item-radio" name="complex_id_product" value="{$product_tab.product.complex_id|escape:'htmlall':'UTF-8'}" data-id-product="{$product_tab.product.id_product|intval}" data-id-product-attribute="0" checked>
                            </td>
                            <td class="left title" rel="name">
                                {$product_tab.product.name|escape:'html':'UTF-8'}
                            </td>
                            <td class="left pm-editable reference" rel="reference">
                                {$product_tab.product.reference|escape:'html':'UTF-8'}
                            </td>
                            <td class="left pm-editable reference" rel="ean13">
                                {$product_tab.product.ean13|escape:'html':'UTF-8'}
                            </td>
                            <td class="left pm-editable reference" rel="upc">
                                {$product_tab.product.upc|escape:'html':'UTF-8'}
                            </td>
                            <td class="center action">
                                <img src="{$images|escape:'htmlall':'UTF-8'}cross.png" class="delete-product-option" rel="{$product_tab.product.complex_id|escape:'htmlall':'UTF-8'}" title="{l s='Delete product option entry' mod='priceminister'}"/>
                            </td>
                            <td class="center action">
                                <img src="{$images|escape:'htmlall':'UTF-8'}page_white_copy.png" class="copy-product-option" rel="{$product_tab.product.complex_id|escape:'htmlall':'UTF-8'}" title="{l s='Copy product option entry' mod='priceminister'}"/>
                            </td>
                            <td class="center action">
                                <img src="{$images|escape:'htmlall':'UTF-8'}paste_plain.png" class="paste-product-option" rel="{$product_tab.product.complex_id|escape:'htmlall':'UTF-8'}" title="{l s='Paste product option entry' mod='priceminister'}"/>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {if is_array($product_tab.combinations) && count($product_tab.combinations)}
                <div class="form-group">
                    <h3 class="tab">&nbsp;&nbsp;{l s='Combinations'}</h3><!-- Validation: Prestashop translations -->

                    <div class="table-responsive">
                        <table id="pm-table-combinations" class="table pm-item">
                            <thead>
                                <tr class="nodrag nodrop">
                                    <th></th>
                                    <th class="left title">
                                        <span class="title_box">{l s='Attribute'}</span><!-- Validation: Prestashop translations -->
                                    </th>
                                    <th class="left reference">
                                        <span class="title_box">{l s='Reference code'}</span><!-- Validation: Prestashop translations -->
                                    </th>
                                    <th class="left reference">
                                        <span class="title_box">EAN13</span>
                                    </th>
                                    <th class="left reference">
                                        <span class="title_box">UPC</span>
                                    </th>
                                    <th class="center action"></th>
                                    <th class="center action"></th>
                                    <th class="center action"></th>
                                </tr>
                            </thead>

                            <tbody>
                                {foreach from=$product_tab.combinations item=combination}
                                    <tr rel="{$combination.complex_id|escape:'htmlall':'UTF-8'}">
                                        <td class="left">
                                            <input type="radio" id="pm-item-radio" name="complex_id_product" value="{$combination.complex_id|escape:'htmlall':'UTF-8'}" data-id-product="{$product_tab.id_product|intval}" data-id-product-attribute="{$combination.id_product_attribute|intval}">
                                        </td>
                                        <td class="left" rel="name">
                                            {$combination.name|escape:'html':'UTF-8'}
                                        </td>
                                        <td class="left pm-editable" rel="reference">
                                            {$combination.reference|escape:'html':'UTF-8'}
                                        </td>
                                        <td class="left pm-editable" rel="ean13">
                                            {$combination.ean13|escape:'html':'UTF-8'}
                                        </td>
                                        <td class="left pm-editable" rel="upc">
                                            {$combination.upc|escape:'html':'UTF-8'}
                                        </td>
                                        <td class="center action">
                                            <img src="{$images|escape:'htmlall':'UTF-8'}cross.png" class="delete-product-option" rel="{$combination.complex_id|escape:'htmlall':'UTF-8'}" title="{l s='Delete product option entry' mod='priceminister'}"/>
                                        </td>
                                        <td class="center action">
                                            <img src="{$images|escape:'htmlall':'UTF-8'}page_white_copy.png" class="copy-product-option" rel="{$combination.complex_id|escape:'htmlall':'UTF-8'}" title="{l s='Copy product option entry' mod='priceminister'}"/>
                                        </td>
                                        <td class="center action">
                                            <img src="{$images|escape:'htmlall':'UTF-8'}paste_plain.png" class="paste-product-option" rel="{$combination.complex_id|escape:'htmlall':'UTF-8'}" title="{l s='Paste product option entry' mod='priceminister'}"/>
                                        </td>
                                    </tr>
                                {/foreach}
                            </tbody>

                        </table>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                        </div>
                    </div>

                    <div class="clearfix"></div>
                </div>
            {/if}
        </div>

        <div class="panel">


            {if isset($product_tab.pm)}

                {foreach from=$product_tab.languages key=id_lang item=language}
                    <div class="pm-sub-tabs">
                        {foreach from=$product_tab.pm[$id_lang] key=complex_id item=product_option}
                            <div id="pm-product-subtab" class="marketplace-subtab" rel="pm" data-complex-id="{$complex_id|escape:'htmlall':'UTF-8'}" {if $product_option.id_product_attribute != 0}style="display:none"{/if}>
                                <div
                                        id="pm-product-options-{$language.id_lang|intval}-{$complex_id|escape:'htmlall':'UTF-8'}"
                                        class="pm-product-options marketplace-tab{if !$product_option.id_product_attribute} main{/if}"
                                        data-iso-code="{$language.iso_code|escape:'htmlall':'UTF-8'}"
                                        data-complex-id="{$complex_id|escape:'htmlall':'UTF-8'}">
                                    <input type="hidden" name="id_product_attribute" value="{$product_option.id_product_attribute|intval}"/>
                                    <input type="hidden" name="context" value="pm"/>
                                    <div style="width:100%;text-align:right">
                                        <em>{$product_option.title|escape:'htmlall':'UTF-8'}</em>
                                    </div>

                                    <table class="product-options">

                                        <tr class="pm-details">
                                            <td style="padding-bottom:5px;"><br/>
                                                <input type="hidden" name="entity" value="pm"/>
                                                <input type="hidden" name="id_lang" value="{$id_lang|intval}"/>
                                            </td>
                                        </tr>

                                        {if array_key_exists('disable', $product_option)}
                                            <tr class="pm-details">
                                                <td class="column-left">{l s='Disabled' mod='priceminister'}</td>
                                                <td style="padding-bottom:20px;">
                                                    <input type="checkbox" name="disable" value="1" {if $product_option.disable}checked{/if} />
                                                    <span style="margin-left:10px">{l s='Check this box to make this product unavailable on rakuten.com' mod='priceminister'}</span><br/>
                                                    <span style="font-size:0.9em;color:grey;line-height:150%" class="propagation">{l s='Make all the products unavailable in this' mod='priceminister'}
                                                        :
                                    <a href="javascript:void(0)" class="pm-propagate-disable-cat propagate">[ {l s='Category' mod='priceminister'}
                                        ]</a>&nbsp;&nbsp;
                                    <a href="javascript:void(0)" class="pm-propagate-disable-shop propagate">[ {l s='Shop' mod='priceminister'}
                                        ]</a>&nbsp;&nbsp;
                                    <a href="javascript:void(0)" class="pm-propagate-disable-manufacturer propagate">[ {l s='Manufacturer' mod='priceminister'}
                                        ]</a></span></span>
                                                    <span id="pm-extra-disable-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}"/>green-loader.gif" style="margin-left:5px;" alt=""/></span>
                                                </td>
                                            </tr>
                                        {/if}

                                        {if array_key_exists('force', $product_option)}
                                            <tr class="pm-details">
                                                <td class="column-left">{l s='Force in Stock' mod='priceminister'}</td>
                                                <td style="padding-bottom:20px;">


                                                    <input type="text" name="force" value="{$product_option.force|escape:'htmlall':'UTF-8'}" style="width:95px"/>
                                                    <span style="margin-left:10px">{l s='The product will always appear on rakuten.com, even it\'s out of Stock' mod='priceminister'}</span><br/>
                                                    <span style="font-size:0.9em;color:grey;line-height:150%" class="propagation">{l s='Force as available in stock for all products in this' mod='priceminister'}
                                                        :
                                    <a href="javascript:void(0)" class="pm-propagate-force-cat propagate">[ {l s='Category' mod='priceminister'}
                                        ]</a>&nbsp;&nbsp;
                                    <a href="javascript:void(0)" class="pm-propagate-force-shop propagate">[ {l s='Shop' mod='priceminister'}
                                        ]</a>&nbsp;&nbsp;
                                    <a href="javascript:void(0)" class="pm-propagate-force-manufacturer propagate">[ {l s='Manufacturer' mod='priceminister'}
                                        ]</a></span></span>
                                                    <span id="pm-extra-force-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}"/>green-loader.gif" style="margin-left:5px;" alt=""/></span>
                                                </td>
                                            </tr>
                                        {/if}

                                        {if array_key_exists('text', $product_option)}
                                            <tr class="pm-details">
                                                <td class="column-left">{l s='Extra Text' mod='priceminister'}</td>
                                                <td style="padding-bottom:20px;">
                                                    <input type="text" name="text" value="{$product_option.text|escape:'htmlall':'UTF-8'}" style="width:400px"/>
                                                    <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Short text which will appear on the product sheet on RakutenFrance MarketPlace' mod='priceminister'}</span><br/>

                                                    <div style="font-size:0.9em;color:grey;line-height:150%">{l s='This text overrides the defaut text sets in the module configuration' mod='priceminister'}</div>
                                                    <div style="font-size:0.9em;color:grey;line-height:150%">{l s='Propagate this text to all products in this' mod='priceminister'}
                                                        :
                                                        <a href="javascript:void(0)" class="pm-propagate-text-cat propagate">[ {l s='Category' mod='priceminister'}
                                                            ]</a>&nbsp;&nbsp;
                                                        <a href="javascript:void(0)" class="pm-propagate-text-shop propagate">[ {l s='Shop' mod='priceminister'}
                                                            ]</a>&nbsp;&nbsp;
                                                        <a href="javascript:void(0)" class="pm-propagate-text-manufacturer propagate">[ {l s='Manufacturer' mod='priceminister'}
                                                            ]</a></span></span>
                                                        <span id="pm-extra-text-loader" style="display:none"><img src="{$images|escape:'htmlall':'UTF-8'}green-loader.gif" style="margin-left:5px;" alt=""/>
                                                    </div>
                                                    <br/>
                                                </td>
                                            </tr>
                                        {/if}

                                        {if array_key_exists('price', $product_option)}
                                            <tr class="pm-details">
                                                <td class="column-left">{l s='Price Override' mod='priceminister'}</td>
                                                <td style="padding-bottom:20px;">
                                                    <input type="text" name="pm_price" value="{$product_option.price|escape:'htmlall':'UTF-8'}" class="marketplace-price" style="width:95px"/>
                                                    <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Net Price for rakuten.com. This value will override your Shop Price' mod='priceminister'}</span><br/>
                                                </td>
                                            </tr>
                                        {/if}

                                        {if array_key_exists('repricing_min', $product_option)}
                                            <tr class="pm-details">
                                                <td class="column-left">{l s='Custom strategy' mod='priceminister'}</td>
                                                <td style="padding-bottom:20px;">
                                                    <input type="text" name="pm_repricing_min" value="{$product_option.repricing_min|escape:'htmlall':'UTF-8'}" class="marketplace-repricing-min" style="width:95px; display: inline-block;"/>
                                                    <img src="{$images|escape:'htmlall':'UTF-8'}down.png" alt="down">
                                                    <input type="text" name="pm_repricing_max" value="{$product_option.repricing_max|escape:'htmlall':'UTF-8'}" class="marketplace-repricing-min" style="width:95px; display: inline-block;"/>
                                                    <img src="{$images|escape:'htmlall':'UTF-8'}up.png" alt="up">

                                                    <br>
                                                    {* Remplace la stratégie de profil par cette stratégie personnalisée *}
                                                    <span style="font-size:0.9em;color:grey;line-height:150%">{l s='Replace the profil strategy by this custom strategy.' mod='priceminister'}</span><br/>
                                                </td>
                                            </tr>
                                        {/if}
                                    </table>
                                </div>
                            </div>
                        {/foreach}
                    </div>
                {/foreach}

            {/if}

        </div>

        {if isset($glossary) && is_array($glossary) && count($glossary)}
            <div id="glossary">
                {foreach from=$glossary key=item item=content}
                    <div class="glossary" rel="{$item|escape:'quotes':'UTF-8'}" style="display:none">
                        {$content|escape:'quotes':'UTF-8'}
                    </div>
                {/foreach}
            </div>
        {/if}
        <div class="debug"></div>
    </div>
{/if}