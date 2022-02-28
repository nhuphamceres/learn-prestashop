{* NOTICE OF LICENSE
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
* Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
* ...........................................................................
* @package    CommonServices
* @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @author     debuss-a
*}

{if $ps16x}
    <link href="{$module_url|escape:'htmlall':'UTF-8'}/views/css/shared/shared_conf16.css" rel="stylesheet" type="text/css" media="all">
{else}
    <link href="{$module_url|escape:'htmlall':'UTF-8'}/views/css/shared/shared_conf.css" rel="stylesheet" type="text/css" media="all">
{/if}

<script type="text/javascript" src="{$module_url|escape:'htmlall':'UTF-8'}/views/js/shared/configure_tabs.js"></script>

{if $has_line}
	{for $i = 1 to $line_number}
		{if is_array($tab_list) && count($tab_list)}
			<ul class="nav" id="menuTab">
				{foreach from=$tab_list  item=tab}
					{if isset($tab.line) && $tab.line == $i || !isset($tab.line) && $i == 1}
						<li id="menu-{$tab.id|escape:'htmlall':'UTF-8'}" class="menuTabButton {if $tab.selected}selected{/if}">
							{if $tab.img === $module_name}
								<a href="#"><span>&nbsp;<img src="{$module_url|escape:'htmlall':'UTF-8'}logo.png" style="max-width: 32px; height: 32px;" alt=""/>&nbsp;{$tab.name|escape:none:'UTF-8'}</span></a>
							{else}
								<a href="#"><span>&nbsp;<img src="{$img_dir|escape:'htmlall':'UTF-8'}{$tab.img|escape:'htmlall':'UTF-8'}.png" alt=""/>&nbsp;{$tab.name|escape:none:'UTF-8'}</span></a>
							{/if}
						</li>
					{/if}
				{/foreach}
			</ul>
			{*<div class="clearfix">&nbsp;</div>*}
		{/if}
	{/for}
{else}
	<ul class="nav" id="menuTab">
		{if is_array($tab_list) && count($tab_list)}
			{foreach from=$tab_list  item=tab}
				<li id="menu-{$tab.id|escape:'htmlall':'UTF-8'}" class="menuTabButton {if $tab.selected}selected{/if}">
					{if $tab.img === $module_name}
						<a href="#"><span>&nbsp;<img src="{$module_url|escape:'htmlall':'UTF-8'}logo.png" style="max-width: 32px; height: 32px;" alt=""/>&nbsp;{$tab.name|escape:none:'UTF-8'}</span></a>
					{else}
						<a href="#"><span>&nbsp;<img src="{$img_dir|escape:'htmlall':'UTF-8'}{$tab.img|escape:'htmlall':'UTF-8'}.png" alt=""/>&nbsp;{$tab.name|escape:none:'UTF-8'}</span></a>
					{/if}
				</li>
			{/foreach}
		{/if}
	</ul>
{/if}
<div id="ps16_tabs_separator"></div>