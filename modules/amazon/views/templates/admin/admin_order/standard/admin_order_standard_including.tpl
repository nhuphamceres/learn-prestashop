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
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail:  support.amazon@common-services.com
*}

{foreach from=$js_urls item=js_url}
    <script type="text/javascript" src="{$js_url|escape:'quotes':'UTF-8'}"></script>
{/foreach}
{foreach from=$css_urls item=css_url}
    <link rel="stylesheet" type="text/css" href="{$css_url|escape:'quotes':'UTF-8'}" />
{/foreach}

<input type="hidden" id="amazon_url" value="{$marketplace_url|escape:'quotes':'UTF-8'}" />
<input type="hidden" id="href_url" value="{$href_url|escape:'quotes':'UTF-8'}" />
<input type="hidden" id="fulfillment_url" value="{$fulfillment_url|escape:'quotes':'UTF-8'}" />
<input type="hidden" id="instant_token" value="{$instant_token|escape:'htmlall':'UTF-8'}" />

{include file=$template_path|cat:'MarketplaceDetail.tpl' details=$marketplace_detail}

{include file="{$module_path|escape:'htmlall':'UTF-8'}/views/templates/admin/glossary.tpl" glossary=$glossary}