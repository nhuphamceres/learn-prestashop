{**
 * 2007-2019 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
<table class="product" width="100%" cellpadding="4" cellspacing="0">

	<thead>
	<tr>
		<th class="product header small" width="{$layout.reference.width|escape:'htmlall':'UTF-8'}%">{l s='Reference' d='Shop.Pdf' pdf='true' mod='amazon'}</th>
		<th class="product header small" width="{$layout.product.width|escape:'htmlall':'UTF-8'}%">{l s='Product' d='Shop.Pdf' pdf='true' mod='amazon'}</th>
		<th class="product header small" width="{$layout.tax_code.width|escape:'htmlall':'UTF-8'}%">{l s='Tax Rate' d='Shop.Pdf' pdf='true' mod='amazon'}</th>

		{if isset($layout.before_discount)}
			<th class="product header small" width="{$layout.unit_price_tax_excl.width|escape:'htmlall':'UTF-8'}%">{l s='Base price' d='Shop.Pdf' pdf='true' mod='amazon'} <br /> {l s='(Tax excl.)' d='Shop.Pdf' pdf='true' mod='amazon'}</th>
		{/if}

		<th class="product header-right small" width="{$layout.unit_price_tax_excl.width|escape:'htmlall':'UTF-8'}%">{l s='Unit Price' d='Shop.Pdf' pdf='true' mod='amazon'} <br /> {l s='(Tax excl.)' d='Shop.Pdf' pdf='true' mod='amazon'}</th>
		<th class="product header small" width="{$layout.quantity.width|escape:'htmlall':'UTF-8'}%">{l s='Qty' d='Shop.Pdf' pdf='true' mod='amazon'}</th>
		<th class="product header-right small" width="{$layout.total_tax_excl.width|escape:'htmlall':'UTF-8'}%">{l s='Total' d='Shop.Pdf' pdf='true' mod='amazon'} <br /> {l s='(Tax excl.)' d='Shop.Pdf' pdf='true' mod='amazon'}</th>
	</tr>
	</thead>

	<tbody>

	<!-- PRODUCTS -->
	{foreach $order_details as $order_detail}
		{cycle values=["color_line_even", "color_line_odd"] assign=bgcolor_class}
		<tr class="product {$bgcolor_class|escape:'htmlall':'UTF-8'}">

			<td class="product center">
				{$order_detail.product_reference|escape:'htmlall':'UTF-8'}
			</td>
			<td class="product left">
				{if $display_product_images}
					<table width="100%">
						<tr>
							<td width="15%">
								{if isset($order_detail.image) && $order_detail.image->id}
									{$order_detail.image_tag|escape:'htmlall':'UTF-8'}
								{/if}
							</td>
							<td width="5%">&nbsp;</td>
							<td width="80%">
								{$order_detail.product_name|escape:'htmlall':'UTF-8'}
							</td>
						</tr>
					</table>
				{else}
					{$order_detail.product_name|escape:'htmlall':'UTF-8'}
				{/if}

			</td>
			<td class="product center">
				{$order_detail.order_detail_tax_label|escape:'htmlall':'UTF-8'}
			</td>

			{if isset($layout.before_discount)}
				<td class="product center">
					{if isset($order_detail.unit_price_tax_excl_before_specific_price)}
						{displayPrice currency=$order->id_currency price=$order_detail.unit_price_tax_excl_before_specific_price}
					{else}
						--
					{/if}
				</td>
			{/if}

			<td class="product right">
				{displayPrice currency=$order->id_currency price=$order_detail.unit_price_tax_excl_including_ecotax}
				{if $order_detail.ecotax_tax_excl > 0}
					<br>
					<small>{{displayPrice currency=$order->id_currency price=$order_detail.ecotax_tax_excl}|string_format:{l s='ecotax: %s' d='Shop.Pdf' pdf='true' mod='amazon'}|escape:'htmlall':'UTF-8'}</small>
				{/if}
			</td>
			<td class="product center">
				{$order_detail.product_quantity|escape:'htmlall':'UTF-8'}
			</td>
			<td  class="product right">
				{displayPrice currency=$order->id_currency price=$order_detail.total_price_tax_excl_including_ecotax}
			</td>
		</tr>

		{foreach $order_detail.customizedDatas as $customizationPerAddress}
			{foreach $customizationPerAddress as $customizationId => $customization}
				<tr class="customization_data {$bgcolor_class|escape:'htmlall':'UTF-8'}">
					<td class="center"> &nbsp;</td>

					<td>
						{if isset($customization.datas[$smarty.const._CUSTOMIZE_TEXTFIELD_]) && count($customization.datas[$smarty.const._CUSTOMIZE_TEXTFIELD_]) > 0}
							<table style="width: 100%;">
								{foreach $customization.datas[$smarty.const._CUSTOMIZE_TEXTFIELD_] as $customization_infos}
									<tr>
										<td style="width: 30%;">
											{$customization_infos.name|string_format:{l s='%s:' d='Shop.Pdf' pdf='true' mod='amazon'}|escape:'htmlall':'UTF-8'}
										</td>
										<td>{if (int)$customization_infos.id_module}{$customization_infos.value|escape:'htmlall':'UTF-8' nofilter}{else}{$customization_infos.value|escape:'htmlall':'UTF-8'}{/if}</td>
									</tr>
								{/foreach}
							</table>
						{/if}

						{if isset($customization.datas[$smarty.const._CUSTOMIZE_FILE_]) && count($customization.datas[$smarty.const._CUSTOMIZE_FILE_]) > 0}
							<table style="width: 100%;">
								<tr>
									<td style="width: 70%;">{l s='image(s):' d='Shop.Pdf' pdf='true' mod='amazon'}</td>
									<td>{count($customization.datas[$smarty.const._CUSTOMIZE_FILE_])|escape:'htmlall':'UTF-8'}</td>
								</tr>
							</table>
						{/if}
					</td>

					<td class="center">
						({if $customization.quantity == 0}1{else}{$customization.quantity|escape:'htmlall':'UTF-8'}{/if})
					</td>

					{assign var=end value=($layout._colCount-3)}
					{for $var=0 to $end}
						<td class="center">
							--
						</td>
					{/for}

				</tr>
				<!--if !$smarty.foreach.custo_foreach.last-->
			{/foreach}
		{/foreach}
	{/foreach}

    {* VIDR - Additional non_item prices *}
    {if isset($vidr_non_product_prices)}
        <tr class="non_product {$vidr_non_product_costs_bg_class|escape:'htmlall':'UTF-8'}">
            <td class="product center"></td>
            <td class="product left">{l s='Shipping Costs' d='Shop.Pdf' pdf='true' mod='amazon'}</td>
            <td class="product center">{$vidr_non_product_prices.shipping_vat_rate|escape:'htmlall':'UTF-8'}</td>
            {if isset($layout.before_discount)}
                <td class="product center"></td>
            {/if}
            <td class="product right">
                {displayPrice currency=$order->id_currency price=$vidr_non_product_prices.shipping_vat_excl}
            </td>
            <td class="product center"></td>
            <td class="product right">
                {displayPrice currency=$order->id_currency price=$vidr_non_product_prices.shipping_vat_excl}
            </td>
        </tr>
        <tr class="non_product {$vidr_non_product_costs_bg_class|escape:'htmlall':'UTF-8'}">
            <td class="product center"></td>
            <td class="product left">{l s='Wrapping Costs' d='Shop.Pdf' pdf='true' mod='amazon'}</td>
            <td class="product center">{$vidr_non_product_prices.wrapping_vat_rate|escape:'htmlall':'UTF-8'}</td>
            {if isset($layout.before_discount)}
                <td class="product center"></td>
            {/if}
            <td class="product right">
                {displayPrice currency=$order->id_currency price=$vidr_non_product_prices.wrapping_vat_excl}
            </td>
            <td class="product center"></td>
            <td class="product right">
                {displayPrice currency=$order->id_currency price=$vidr_non_product_prices.wrapping_vat_excl}
            </td>
        </tr>
        <tr class="non_product {$vidr_non_product_costs_bg_class|escape:'htmlall':'UTF-8'}">
            <td class="product center"></td>
            <td class="product left">{l s='Total Discounts' d='Shop.Pdf' pdf='true' mod='amazon'}</td>
            <td class="product center"></td>
            {if isset($layout.before_discount)}
                <td class="product center"></td>
            {/if}
            <td class="product right">
                {displayPrice currency=$order->id_currency price=$vidr_non_product_prices.promo_vat_excl}
            </td>
            <td class="product center"></td>
            <td class="product right">
                {displayPrice currency=$order->id_currency price=$vidr_non_product_prices.promo_vat_excl}
            </td>
        </tr>
    {/if}
	<!-- END PRODUCTS -->

	<!-- CART RULES -->

	{assign var="shipping_discount_tax_incl" value="0"}
	{foreach from=$cart_rules item=cart_rule name="cart_rules_loop"}
		{if $smarty.foreach.cart_rules_loop.first}
		<tr class="discount">
			<th class="header" colspan="{$layout._colCount|escape:'htmlall':'UTF-8'}">
				{l s='Discounts' d='Shop.Pdf' pdf='true' mod='amazon'}
			</th>
		</tr>
		{/if}
		<tr class="discount">
			<td class="white right" colspan="{$layout._colCount - 1|escape:'htmlall':'UTF-8'}">
				{$cart_rule.name|escape:'htmlall':'UTF-8'}
			</td>
			<td class="right white">
				- {displayPrice currency=$order->id_currency price=$cart_rule.value_tax_excl}
			</td>
		</tr>
	{/foreach}

	</tbody>

</table>
