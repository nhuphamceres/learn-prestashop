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

{foreach from=$js_urls item=js_url}
    <script type="text/javascript" src="{$js_url|escape:'quotes':'UTF-8'}"></script>
{/foreach}
<link rel="stylesheet" type="text/css" href="{$css_url|escape:'quotes':'UTF-8'}">

<input type="hidden" id="fbaorder_url" value="{$fbaorder_url|escape:'quotes':'UTF-8'}"/>
<input type="hidden" id="amazon_order_id" value="{$id_order|escape:'quotes':'UTF-8'}"/>
<input type="hidden" id="amazon_token" value="{$amazon_token|escape:'quotes':'UTF-8'}"/>
<input type="hidden" id="amazon_id_lang" value="{$id_lang|escape:'quotes':'UTF-8'}"/>
<input type="hidden" id="amazon_debug" value="{$debug|escape:'quotes':'UTF-8'}"/>
<input type="hidden" id="context_key" value="{$context_key|escape:'quotes':'UTF-8'}"/>

{include file=$template_path|cat:'MarketplaceDetail.tpl' details=$marketplace_detail}
