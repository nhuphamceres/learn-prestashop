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

{if $headerTitle != ""}
    <br/>
    <div class="col-lg-12 form-group">
        <h3>{$headerTitle}</h3>
    </div>
    <br/>
{/if}
<div class="form-group">
    <div style="{$additionalStyle|escape:'htmlall':'UTF-8'}" {if isset($addicionalContainerClass)} class="form-group{$addicionalContainerClass}" {/if}>
        {if $includeLabel}
            {include file="$pm_module_path/views/templates/admin/pm_helpers/label.tpl" forItem=$label classItem='' labelItem=$displayName}
        {/if}
        <div class="margin-group">
            {$input_html nofilter}
        </div>
        {if is_array($unit) && count($unit)}
            <input type="hidden" name="{$unit.index|escape:'htmlall':'UTF-8'}" value="{$unit.value|escape:'htmlall':'UTF-8'}">
        {/if}
    </div>
</div>
&nbsp;<br/>    
