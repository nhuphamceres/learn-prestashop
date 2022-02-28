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
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * Support by mail:  support.amazon@common-services.com
*}

<legend style="font-size:14px;padding-bottom: 5px;">
    <b>{l s='VCS Lite invoice' mod='amazon'}</b>
    <a class="btn btn-default _blank" target="_blank" title="{l s='Download VCS Lite invoice' mod='amazon'}"
       href="{$preview_invoice_url|escape:'html':'UTF-8'}">
        {if $ps_version_is_15}
            <img src="../img/admin/tab-invoice.gif" alt="{l s='Download VCS Lite invoice' mod='amazon'}">
        {else}
            <i class="icon-file-pdf-o"></i>
        {/if}
    </a>
</legend>
