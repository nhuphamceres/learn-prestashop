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
* @author    Alexandre D. & Olivier B.
* @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @license   Commercial license
* Support by mail  :  contact@common-services.com
*}

<ps-form-group label="{l s='Order States Map' mod='fnac'}">
	{foreach from=$fnac_orders.statuses item=status}
		<div class="form-group" style="padding-bottom:1px">
			<div class="col-xs-5">
				<input type="text" readonly="readonly" value="{$status.status|escape:'htmlall':'UTF-8'}">
			</div>
			<div class="col-xs-2 text-center"><span style="position:relative;top:8px">&nbsp;&nbsp;
					<img src="{$images_url|escape:'htmlall':'UTF-8'}list-next.gif" alt="" />
					&nbsp;&nbsp;
				</span></div>
			<div class="col-xs-5">
				<select name="order_state_map_mp[]">
					<option value="0">{l s='Choose in the List' mod='fnac'}</option>
					{foreach from=$status.options item=option}
						<option value="{$option.value|escape:'htmlall':'UTF-8'}" {$option.selected|escape:'htmlall':'UTF-8'} >{$option.desc|escape:'htmlall':'UTF-8'}</option>
					{/foreach}
				</select>
			</div>
		</div>
	{/foreach}
</ps-form-group>