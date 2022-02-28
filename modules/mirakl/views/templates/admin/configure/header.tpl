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
*
* @package    Mirakl
* @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @author     Olivier B.
* @license    Commercial license
* Support by mail  :  support.mirakl@common-services.com
*}


<input type="hidden" id="id_lang" value="{$id_lang|intval}" />
<input type="hidden" id="check_url" value="{$check_url|escape:'quotes':'UTF-8'}" />
<script type="text/javascript" src="{$module_url|escape:'quotes':'UTF-8'}views/js/mirakl.js?v={$me_version|escape:'htmlall':'UTF-8'}"></script>
<link rel="stylesheet" type="text/css" href="{$module_url|escape:'quotes':'UTF-8'}views/css/mirakl.css?v={$me_version|escape:'htmlall':'UTF-8'}">

<!-- Heading -->
<div style="min-height: 100px;margin-bottom:30px;">
    <img src="{if $marketplace_logo}{$images_url|escape:'htmlall':'UTF-8'}{$marketplace_logo|escape:'htmlall':'UTF-8'}{else}{$images_url|escape:'htmlall':'UTF-8'}banner.png{/if}" alt="{l s='Mirakl' mod='mirakl'}" style="float:right; text-align:right;max-width:125px;" />
    <img src="{$images_url|escape:'htmlall':'UTF-8'}common-services_48px.png" alt="Common-Services" />
</div>
<div style="clear:both;padding-bottom:20px;"></div>

{*Context params*}
{include file="$module_path/views/templates/admin/context_params.tpl" context=$context}
<!-- End Heading -->
