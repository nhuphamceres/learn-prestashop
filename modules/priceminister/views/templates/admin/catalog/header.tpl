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

<input type="hidden" id="serror" value="{l s='A server-side error has occured. Please contact your server administrator, hostmaster or webmaster' mod='priceminister'}"/>
<input type="hidden" id="sdebug" value="{l s='You should click on this link to submit again in debug mode' mod='priceminister'}"/>

<input type="hidden" id="pm_shop_all" value="{$all_shop|intval}">

<div>
    {if (isset($context_key))}
        <input type="hidden" name="context_key" value="{$context_key|escape:'htmlall':'UTF-8'}"/>
    {/if}
    <img src="{$images|escape:'htmlall':'UTF-8'}logo.svg" alt="{l s='Rakuten' mod='priceminister'}" class="logo-wide" />

</div>
<div style="clear:both;padding-bottom:20px;"></div>

<fieldset class="panel" id="priceminister-catalog">
    <h3>
        <img src="{$images|escape:'htmlall':'UTF-8'}logo.png" alt="" class="middle logo-circle"/>
        <span style="vertical-align: middle;"> {l s='Catalog' mod='priceminister'}</span>
    </h3>
    <br/>