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
 * ...........................................................................
 *
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice@common-services.com
 *}

<div id="conf-params" style="display: none;">
    <h2>{l s='Parameters' mod='sonice_suivicolis'}</h2>
    <div class="cleaner">&nbsp;</div>
    
    <div class="form-group">
        <label class="control-label col-lg-3" rel="auto_update">{l s='Activate automatic update in order panel' mod='sonice_suivicolis'}</label>
        <div class="margin-form snsc_typo_conftab col-lg-9">
            <span class="switch prestashop-switch fixed-width-lg">
                <input type="radio" name="return_info[auto_update_order]" id="auto_update_order_1" value="1" {if isset($snsc_config.auto_update_order) && $snsc_config.auto_update_order}checked{/if} /><label for="auto_update_order_1" class="label-checkbox">{l s='Yes' mod='sonice_suivicolis'}</label>
                <input type="radio" name="return_info[auto_update_order]" id="auto_update_order_0" value="0" {if isset($snsc_config.auto_update_order) && $snsc_config.auto_update_order == 0}checked{/if} /><label for="auto_update_order_0" class="label-checkbox">{l s='No' mod='sonice_suivicolis'}</label>
                <a class="slide-button btn"></a>
            </span>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3" rel="order_until">{l s='Get order until ' mod='sonice_suivicolis'}</label>
        <div class="margin-form snsc_typo_conftab col-lg-9">
            <select name="return_info[nb_weeks]">
				{section name=nb_weeks start=1 loop=8}
					<option value="{$smarty.section.nb_weeks.index|escape:'htmlall':'UTF-8'}" {if isset($snsc_config.nb_weeks) && $snsc_config.nb_weeks == $smarty.section.nb_weeks.index}selected{/if}>{$smarty.section.nb_weeks.index|escape:'htmlall':'UTF-8'}</option>
				{/section}
                <option value="25" {if isset($snsc_config.nb_weeks) && $snsc_config.nb_weeks == 25}selected{/if}>25 (DEBUG)</option>
            </select> {l s='weeks ago.' mod='sonice_suivicolis'}
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3" rel="employee_cron">{l s='CRON Task Employee' mod='sonice_suivicolis'}</label>
        <div class="margin-form snsc_typo_conftab col-lg-9">
            <select name="return_info[cron_employee]">
                {foreach $snsc_employee_list as $employee}
                    <option value="{$employee.id_employee|escape:'htmlall':'UTF-8'}" {if isset($snsc_config.cron_employee) && $snsc_config.cron_employee === $employee.id_employee}selected{/if}>
                        {if isset($employee.name) && $employee.name}{$employee.name|escape:'htmlall':'UTF-8'}{else}{$employee.firstname|escape:'htmlall':'UTF-8'} {$employee.lastname|escape:'htmlall':'UTF-8'}{/if}
                    </option>
                {/foreach}
            </select>
        </div>
    </div>
    
    {include file="$snsc_module_path/views/templates/admin/configuration/validate.tpl"}
</div>