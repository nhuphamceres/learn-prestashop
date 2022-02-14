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

<select name="{$select_id}" {$select_multiple} {$select_cssClass} {$select_disabled} style="{if isset($select_style)}{$select_style|escape:'htmlall':'UTF-8'}{/if}">
    <option value="" {$select_selected_non}>--{l s='select an option' mod='priceminister'}--</option>
    {foreach from=$select_options item=option}
        <option value="{$option.value}" {$option.selected} >{$option.desc}</option>
    {/foreach}
</select>{if $select_mandatory}<span class="pm-required">*</span>{/if}