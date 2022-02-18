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

{if $tab_reports_data}
    <div id="conf-reports" class="tabItem {$tab_reports_data.selected_tab|escape:'htmlall':'UTF-8'} form-horizontal" style="display:none">
        <h3 style="text-align:center;margin:10px 0 0 0; border: none;">{l s='Reports' mod='priceminister'}</h3>
        <hr style="width:60%;margin-top:5px;"/>

        <form action="{$tab_reports_data.request_uri|escape:'htmlall':'UTF-8'}" method="post" id="catalog-reports-form">
            <input type="hidden" name="pm_token" value="{$tab_reports_data.pm_token|escape:'htmlall':'UTF-8'}"/>
            <div class="form-group">
                <label class="control-label col-lg-3" style="color:grey">{l s='Ressources' mod='priceminister'}</label>

                <div class="margin-form col-lg-9">

                    <div class="{$alert_class.warning|escape:'htmlall':'UTF-8'} col-lg-12" id="reports-none-available" style="display:none;">{l s='No available report' mod='priceminister'}</div>

                    <div id="catalog-reports-loader" style="display:none;"></div>

                    <table class="table report table-hover" cellpadding="0" cellspacing="0">

                        <thead class="report-table-heading" style="display:none">
                            <tr class="active">
                                <th class="left">{l s='Id' mod='priceminister'}</th>
                                <th class="left">{l s='Start' mod='priceminister'}</th>
                                <th class="left">{l s='Stop' mod='priceminister'}</th>
                                <th class="left">{l s='Duration' mod='priceminister'}</th>
                                <th class="left">{l s='File' mod='priceminister'}</th>
                                <th class="left">{l s='Items' mod='priceminister'}</th>
                            </tr>
                        </thead>
                        <tbody class="reports">
                            <tr class="row_hover report-model" style="display:none;">
                                <td class="left" rel="id"></td>
                                <td class="left" rel="start"></td>
                                <td class="left" rel="stop">&nbsp;</td>
                                <td class="left" rel="duration">&nbsp;</td>
                                <td class="left" rel="file">&nbsp;</td>
                                <td class="left" rel="items">&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>

            <hr style="width:30%;"/>

            <div class="form-group">
                <div class="margin-form col-lg-offset-3">

                    <input type="hidden" id="catalog-reports-select-msg" value="{l s='You must select a report' mod='priceminister'}"/>
                    <input type="hidden" id="catalog-reports-url" value="{$tab_reports_data.reports_url|escape:'htmlall':'UTF-8'}"/>
                    <input type="button" id="catalog-reports-report" value="{l s='Show Report' mod='priceminister'}" class="button btn btn-default"/>
                    <br/><br/><br/>

                    <div class="{$alert_class.success|escape:'htmlall':'UTF-8'} col-lg-12" id="catalog-reports-result" style="display:none;"></div>
                    <div class="{$alert_class.danger|escape:'htmlall':'UTF-8'} col-lg-12" id="catalog-reports-error" style="display:none;"></div>
                    <div class="{$alert_class.warning|escape:'htmlall':'UTF-8'} col-lg-12" id="catalog-reports-warning" style="display:none"></div>

                    <div id="catalog-report-loader"></div>

                    <pre id="catalog-report-summary" style="display: none;"></pre>
                    <pre id="catalog-report-details" style="display: none;"></pre>
                </div>
            </div>


        </form>
    </div>
{/if}