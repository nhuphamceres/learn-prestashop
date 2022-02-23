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
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 * ...........................................................................
 * @package    SO COLISSIMO
 * @copyright  Copyright(c) 2010-2013 S.A.R.L S.M.C - http://www.common-services.com
 * @author     Debusschere A.
 * @license    Commercial license
 *}


<div id="conf-filters" style="display: none;">
	<h2>{l s='Filters' mod='sonice_suivicolis'}</h2>

	<div class="form-group">
		<label class="control-label col-lg-3" rel="filter_payment">{l s='Filter by order payment' mod='sonice_suivicolis'}</label>
		<div class="margin-form col-lg-9">
			<div class="snsc_multi_select_heading">
				<span><img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}tick.png" alt="Excluded" /></span>
				<span><img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}cross.png" alt="Included" /></span>
			</div>
			<br>

			<!-- Included state -->
			<select class="snsc_multi_select float-left" id="unfiltered_order_payment" style="margin-left: 10px;" multiple>
				<option value="0" disabled style="color:green;">{l s='Included paynent methods' mod='sonice_suivicolis'}</option>
				{foreach $snsc_filters.payment_methods as $method}
					{if !in_array($method, $snsc_filters.payment_methods_excluded)}
						<option value="{$method|escape:'htmlall':'UTF-8'}" rel="{$method|escape:'htmlall':'UTF-8'}">{$method|escape:'htmlall':'UTF-8'}</option>
					{/if}
				{/foreach}
			</select>

			<!-- Arrows -->
			<div class="snsc_sep float-left">
				<img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}next.png" class="snsc_move" id="move-right-payment" alt="Right" /><br /><br />
				<img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}previous.png" class="snsc_move" id="move-left-payment" alt="Left" />
			</div>

			<!-- Excluded state -->
			<select class="snsc_multi_select float-left" id="filtered_order_payment" name="filtered_payment_methods[]" style="margin-left: 10px;" multiple>
				<option value="0" disabled style="color:red;">{l s='Excluded paynent methods' mod='sonice_suivicolis'}</option>
				{foreach $snsc_filters.payment_methods as $method}
					{if in_array($method, $snsc_filters.payment_methods_excluded)}
						<option value="{$method|escape:'htmlall':'UTF-8'}" rel="{$method|escape:'htmlall':'UTF-8'}">{$method|escape:'htmlall':'UTF-8'}</option>
					{/if}
				{/foreach}
			</select>
		</div>
	</div>

	<div class="clearfix">&nbsp;</div>
	<div class="clearfix">&nbsp;</div>
	<div class="clearfix">&nbsp;</div>

	<div class="form-group">
		<label class="control-label col-lg-3" rel="filter_status">{l s='Filter by order status' mod='sonice_suivicolis'}</label>
		<div class="margin-form col-lg-9">
			<div class="snsc_multi_select_heading">
				<span><img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}tick.png" alt="Excluded" /></span>
				<span><img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}cross.png" alt="Included" /></span>
			</div>
			<br>

			<!-- Included state -->
			<select class="snsc_multi_select float-left" id="unfiltered_order_status" style="margin-left: 10px;" multiple>
				<option value="0" disabled style="color:green;">{l s='Included Order Statuses' mod='sonice_suivicolis'}</option>
				{foreach $snsc_filters.order_statuses as $status}
					{if !in_array($status.id_order_state, $snsc_filters.order_statuses_excluded)}
						<option value="{$status.id_order_state|escape:'htmlall':'UTF-8'}" rel="{$status.id_order_state|escape:'htmlall':'UTF-8'}">{$status.name|escape:'htmlall':'UTF-8'}</option>
					{/if}
				{/foreach}
			</select>

			<!-- Arrows -->
			<div class="snsc_sep float-left">
				<img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}next.png" class="snsc_move" id="move-right-status" alt="Right" /><br /><br />
				<img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}previous.png" class="snsc_move" id="move-left-status" alt="Left" />
			</div>

			<!-- Excluded state -->
			<select class="snsc_multi_select float-left" id="filtered_order_status" name="filtered_status[]" style="margin-left: 10px;" multiple>
				<option value="0" disabled style="color:red;">{l s='Excluded Order Statuses' mod='sonice_suivicolis'}</option>
				{foreach $snsc_filters.order_statuses as $status}
					{if in_array($status.id_order_state, $snsc_filters.order_statuses_excluded)}
						<option value="{$status.id_order_state|escape:'htmlall':'UTF-8'}" rel="{$status.id_order_state|escape:'htmlall':'UTF-8'}">{$status.name|escape:'htmlall':'UTF-8'}</option>
					{/if}
				{/foreach}
			</select>
		</div>
	</div>

	{include file="$snsc_module_path/views/templates/admin/configuration/validate.tpl"}
</div>