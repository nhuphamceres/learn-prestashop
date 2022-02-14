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

<script type="text/javascript" src="{$pm_url|escape:'htmlall':'UTF-8'}views/js/order.js"></script>
<form name="pmUpdateOrder" id="pmUpdateOrder" method="post">
    <fieldset class="panel col-lg-7">
        <h3>
            <span><img src="{$pm_images|escape:'htmlall':'UTF-8'}logo.png" alt="Rakuten France" class="logo-circle">&nbsp;{l s='Rakuten France' mod='priceminister'}</span>
        </h3>
        <br>
        <span class="mp_bold">{l s='Purchase ID' mod='priceminister'} :</span>
        <span>{$pm_marketPlaceOrderId|escape:'htmlall':'UTF-8'}</span><br>

        <input type="button" class="button btn btn-default" style="margin:15px 0 0 0;cursor:pointer" onclick="javascript:window.open('{$pm_order_page_url|escape:'htmlall':'UTF-8'}');" value="Voir sur Rakuten France"/>
        &nbsp;&nbsp;
        {if isset($prepaidlabelurl) && $prepaidlabelurl}
            <input type="button" class="button btn btn-primary" style="margin:15px 0 0 0;cursor:pointer" onclick="javascript:window.open('{$prepaidlabelurl|escape:'htmlall':'UTF-8'}');" value="Télécharger l'étiquette"/>
        {/if}
    </fieldset>
    <input type="hidden" id="order_id" value="{$pm_id_order|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" id="pm_order_id" value="{$pm_marketPlaceOrderId|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" id="pm_order_lang" value="{$pm_order_lang|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" id="pm_tokens" name="pm_token[{$pm_id_order|escape:'htmlall':'UTF-8'}]" value="{$pm_token|escape:'htmlall':'UTF-8'}"/>
    <input type="hidden" id="pm_order_url" value="{$pm_url|escape:'htmlall':'UTF-8'}functions/orders.php&context_key={$pm_context_key|escape:'htmlall':'UTF-8'}"/>
</form>