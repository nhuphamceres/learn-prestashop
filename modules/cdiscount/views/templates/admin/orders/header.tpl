{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Common-Services Co., Ltd. is strictly forbidden.
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
 *
 * @package   CDiscount
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail:  support.cdiscount@common-services.com
*}

<input type="hidden" id="serror"
       value="{l s='A server-side error has occured. Please contact your server administrator, hostmaster or webmaster' mod='cdiscount'}"/>
<input type="hidden" id="sdebug"
       value="{l s='You should click on this link to submit again in debug mode' mod='cdiscount'}"/>

<div>
    {if (isset($context_key))}
        <input type="hidden" name="context_key" value="{$context_key|escape:'htmlall':'UTF-8'}"/>
    {/if}
    <img style="float:right;" src="{$images|escape:'htmlall':'UTF-8'}clemarche_1.png"
         alt="{l s='Cdiscount Marketplace' mod='cdiscount'}"/>
    <img style="float:left;" src="{$images|escape:'htmlall':'UTF-8'}cdiscount.png"
         alt="{l s='CDiscount' mod='cdiscount'}"/>
</div>
<div style="clear:both;padding-bottom:20px;"></div>

<fieldset class="panel">
    <h3>
        <img src="{$images|escape:'htmlall':'UTF-8'}logo.gif" alt="" class="middle"/> <span
                style="vertical-align: middle;"> {l s='Orders' mod='cdiscount'}</span>
    </h3>

    <p>
        <u>{l s='Quick Access' mod='cdiscount'}</u>:&nbsp;&nbsp;
    <span class="quick-access">
        <a href="{$auth_url|escape:'htmlall':'UTF-8'}" alt="{l s='Authentification' mod='cdiscount'}" target="_blank">
            <img src="{$images|escape:'htmlall':'UTF-8'}profiles.png"
                 title="{l s='Authentification' mod='cdiscount'}"/> {l s='Authentification' mod='cdiscount'}
        </a>
    </span>
    <span class="quick-access">
        <a href="{$home_url|escape:'htmlall':'UTF-8'}" alt="{l s='Home Page' mod='cdiscount'}" target="_blank">
            <img src="{$images|escape:'htmlall':'UTF-8'}home.png"
                 title="{l s='Home Page' mod='cdiscount'}"/> {l s='Home Page' mod='cdiscount'}
        </a>
    </span>
    <span class="quick-access">
        <a href="{$orders_url|escape:'htmlall':'UTF-8'}" alt="{l s='Orders Page' mod='cdiscount'}" target="_blank">
            <img src="{$images|escape:'htmlall':'UTF-8'}orders.gif"
                 title="{l s='Orders Page' mod='cdiscount'}"/> {l s='Orders Page' mod='cdiscount'}
        </a>
    </span>
    <span class="quick-access">
        <img src="{$images|escape:'htmlall':'UTF-8'}mails_stack.png"
             title="{l s='Messaging' mod='cdiscount'}:"/> {l s='Messaging' mod='cdiscount'}:
        <a href="{$ordersq_url|escape:'htmlall':'UTF-8'}" alt="{l s='Orders' mod='cdiscount'} :"
           target="_blank"> {l s='Orders' mod='cdiscount'}</a> /
        <a href="{$offersq_url|escape:'htmlall':'UTF-8'}" alt="{l s='Products' mod='cdiscount'} :"
           target="_blank"> {l s='Products' mod='cdiscount'}</a>
    </span>
    </p>
    <br/>