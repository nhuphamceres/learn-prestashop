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

<img src="{$images_url|escape:'quotes':'UTF-8'}green-loader.gif" alt="{l s='Loading' mod='amazon'}"
     id="order-fba-loader"/>

<p style="color:green">{l s='This Order is Eligible for FBA Multi-Channel Process' mod='amazon'}</p>

<p id="order-fba-ajax-error">{l s='Unexpected error while fetching data from Amazon' mod='amazon'}</p>

<p id="order-fba-error-message"></p>

<p id="order-fba-message"></p>

<hr id="order-fba-detail-spacer"/>
<hr id="order-fba-detail-spacer2"/>
<input type="button" class="button" id="amazon_fba_create" value="{l s='Ship this Order through Amazon' mod='amazon'}"/>

{if $debug}
    <p>
        <span class="amazon_label">{l s='Debug Mode' mod='amazon'}:
        </span><span class="amazon_text" style="color:red;font-weight:bold">{l s='Active' mod='amazon'}</span><br/>
        <span>Debug:</span>
    <pre id="amazon-output">&nbsp;</pre>
    </p>
    <br/>
{else}
    <pre id="amazon-output" style="display:none">&nbsp;</pre>
{/if}
