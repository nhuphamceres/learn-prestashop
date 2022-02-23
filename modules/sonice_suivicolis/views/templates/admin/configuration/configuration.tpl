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
 * ...........................................................................
 *
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice@common-services.com
 *}

<!-- URLS -->
<input type="hidden" id="snsc_function_dir" value="{$snsc_module_functions|escape:'htmlall':'UTF-8'}">
<input type="hidden" id="snsc_checklogin_url" value="{$snsc_checklogin_url|escape:'htmlall':'UTF-8'}">

<!-- TEXTS -->
<input type="hidden" id="snsc_network_error" value="{$snsc_network_error|escape:'htmlall':'UTF-8'}">

<!-- FORM START -->
<form id="configuration" class="defaultForm snsc" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}" autocomplete="off" method="post" enctype="multipart/form-data">
    <!-- SELECTED TAB -->
    <input type="hidden" name="selected_tab" id="selected_tab" value="{$selected_tab|escape:'htmlall':'UTF-8'}">
    <!-- ID SHOP -->
    <input type="hidden" id="snsc_id_shop" value="{$snsc_id_shop|intval}">

    <!-- FIELDSET -->
    <fieldset id="tabList" class="panel form-horizontal">
		{foreach $tab_list as $tab}
			{include file="$snsc_module_path/views/templates/admin/configuration/conf_"|cat:$tab['id']|cat:".tpl"}
		{/foreach}
        {include file="$snsc_module_path/views/templates/admin/configuration/glossary.tpl"}
    </fieldset>
</form>
<br>