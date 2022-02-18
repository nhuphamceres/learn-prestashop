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

{if $tab_offers_data}
    <div id="conf-offers" class="tabItem {$tab_offers_data.selected_tab|escape:'htmlall':'UTF-8'} form-horizontal"
         rel="update">
        <h3 style="text-align:center;margin:10px 0 0 0; border: none;">{l s='Export Offers (Update)' mod='priceminister'}</h3>
        <hr style="width:60%;margin-top:5px;"/>

        <div class="form-group">
            <label class="control-label col-lg-3">&nbsp;</label>

            <div class="margin-form col-lg-9">
                <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                    {l s='Please follow our online tutorial' mod='priceminister'}:<br>
                    <a href="http://documentation.common-services.com/priceminister/envoi-des-offres-mise-a-jour/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                       target="_blank">http://documentation.common-services.com/priceminister/envoi-des-offres-mise-a-jour/</a>
                </div>
            </div>
        </div>

        <form action="{$tab_offers_data.request_uri|escape:'htmlall':'UTF-8'}" method="post" id="catalog-offers-form">
            <input type="hidden" name="pm_token" value="{$tab_offers_data.pm_token|escape:'htmlall':'UTF-8'}"/>
            <div class="form-group">
                <label class="control-label col-lg-3" style="color:grey">{l s='Parameters' mod='priceminister'}</label>

                <div class="margin-form col-lg-9">
                    <br/>
                    <input type="checkbox" name="debug" value="1" style="display:none"/>
                    <br/>
                </div>
            </div>

            <div class="form-group parameters">
                <label class="control-label col-lg-3">{l s='All Offers' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <input type="checkbox" name="all-offers" value="1" checked>
                    <span class="checkbox-label">{l s='Yes' mod='priceminister'}</span>
                    <p class="checkbox-help">{l s='Send all the offers: it will update all your catalog on RakutenFrance' mod='priceminister'}</p>
                </div>

                <div class="form-group parameters">
                    <label class="control-label col-lg-3">{l s='Only active products' mod='priceminister'}</label>

                    <div class="margin-form col-lg-9">
                        <input type="checkbox" name="active" value="1" checked>
                        <span class="checkbox-label">{l s='Yes' mod='priceminister'}</span>
                        <p class="checkbox-help">{l s='Export only active products' mod='priceminister'}</p>
                    </div>
                </div>

                <label class="control-label col-lg-3">Matching</label>
                <div class="margin-form col-lg-9">
                    <input type="checkbox" name="matching" value="1">
                    <span class="checkbox-label">{l s='Yes' mod='priceminister'}</span>
                    <p class="checkbox-help">{l s='Offers will be created with your references, stock and price but on the base of an existing product on RakutenFrance.' mod='priceminister'}</p>
                    <p class="checkbox-help">
                        (<a href="http://documentation.common-services.com/priceminister/quest-ce-que-le-mode-matching/" target="_blank">{l s='What is the matching mode ?' mod='priceminister'}</a>)
                    </p>
                </div>

                <label class="control-label col-lg-3">{l s='Submit' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <input type="checkbox" name="send" value="1" {if !isset($smarty.server.DropBox)}checked{/if}>
                    <span class="checkbox-label">{l s='Yes' mod='priceminister'}</span>
                    <p class="checkbox-help">{l s='Submit this feed to RakutenFrance' mod='priceminister'}</p>
                </div>

                <label class="control-label col-lg-3">{l s='Display Result' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <input type="checkbox" id="display_offers_result" value="1">
                    <span class="checkbox-label">{l s='Yes' mod='priceminister'}</span>
                    <p class="checkbox-help">{l s='Display the submission result, may be laggy in case of many products.' mod='priceminister'}</p>
                </div>
            </div>

            <hr style="width:30%;"/>

            <div class="form-group">
                <div class="margin-form col-lg-offset-3">

                    <input type="hidden" id="catalog-offers-url" value="{$tab_offers_data.offers_url|escape:'htmlall':'UTF-8'}"/>
                    <input type="button" id="catalog-offers-export" rel="export" value="{l s='Export' mod='priceminister'}" class="button btn btn-default"/>
                    <br/><br/><br/>

                    <div class="{$alert_class.success|escape:'htmlall':'UTF-8'} col-lg-12" id="catalog-offers-result" style="display:none;"></div>
                    <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'} col-lg-12" id="catalog-offers-error" style="display:none;"></div>
                    <div class="{$alert_class.warning|escape:'htmlall':'UTF-8'} col-lg-12" id="catalog-offers-warning" style="display:none"></div>

                    <div id="catalog-offers-loader"></div>
                </div>
            </div>

            <hr style="width:30%;margin-bottom:15px;" id="catalog-offers-hr"/>


            <div class="form-group">
                <div class="form-group col-lg-offset-3">

                    <table class="table offer table-hover" cellpadding="0" cellspacing="0">

                        <thead class="offer-table-heading" style="display:none">
                            <tr class="active">
                                <th class="center" width="20px"></th>
                                <th class="left">{l s='Reference' mod='priceminister'}</th>
                                <th class="left">{l s='Code' mod='priceminister'}</th>
                                <th class="left">{l s='Name' mod='priceminister'}</th>
                                <th class="left">{l s='Qty' mod='priceminister'}</th>
                                <th class="center">{l s='Price' mod='priceminister'}</th>
                                <th class="center">{l s='F.Price' mod='priceminister'}</th>
                            </tr>
                        </thead>
                        <tbody class="offers">
                            <tr class="row_hover offer-model" style="display:none;">
                                <td class="left" rel="status"></td>
                                <td class="left" rel="reference">&nbsp;</td>
                                <td class="left" rel="code">&nbsp;</td>
                                <td class="left" rel="name">&nbsp;</td>
                                <td class="left" rel="qty">&nbsp;</td>
                                <td class="right" rel="price">&nbsp;</td>
                                <td class="right" rel="final_price">&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>

            <br/>
            <hr style="width:30%;margin-top:15px;"/>
            <br/>

            <ul id="offers-legend" style="/*display:none*/">
                <li>{l s='Legend' mod='priceminister'}</li>
                <li>
                    <img src="{$images|escape:'htmlall':'UTF-8'}icon_valid_16.png" rel="active" alt="{l s='Active, In Stock' mod='priceminister'}"/>&nbsp;{l s='Active, In Stock' mod='priceminister'}
                </li>
                <li>
                    <img src="{$images|escape:'htmlall':'UTF-8'}soos.png" rel="oos" alt="{l s='Out of stock' mod='priceminister'}"/>&nbsp;{l s='Out of stock' mod='priceminister'}
                </li>
                <li>
                    <img src="{$images|escape:'htmlall':'UTF-8'}cross_bw.png" rel="na" alt="{l s='Non Exportable' mod='priceminister'}"/>&nbsp;{l s='Non Exportable' mod='priceminister'}
                </li>
                <li>
                    <img src="{$images|escape:'htmlall':'UTF-8'}cross.png" rel="inactive" alt="{l s='Inactive' mod='priceminister'}"/>&nbsp;{l s='Inactive' mod='priceminister'}
                </li>
            </ul>
        </form>
    </div>
{/if}    