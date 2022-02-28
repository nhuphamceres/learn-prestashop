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
		<th class="product header small" >{l s='Qty' d='Shop.Pdf' pdf='true' mod='amazon'}</th>
		<th class="product header small" >{l s='Item' d='Shop.Pdf' pdf='true' mod='amazon'}</th>
		<th class="product header small" >{l s='Promotion' d='Shop.Pdf' pdf='true' mod='amazon'}</th>
		<th class="product header small" >{l s='Gift' d='Shop.Pdf' pdf='true' mod='amazon'}</th>
		<th class="product header small" >{l s='Promotion' d='Shop.Pdf' pdf='true' mod='amazon'}</th>
		<th class="product header small" >{l s='Shipping' d='Shop.Pdf' pdf='true' mod='amazon'}</th>
		<th class="product header small" >{l s='Promotion' d='Shop.Pdf' pdf='true' mod='amazon'}</th>
		
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

            <td class="product center">
                {$order_detail.product_quantity|escape:'htmlall':'UTF-8'}
            </td>

            <td class="product center">
                {displayPrice currency=$order->id_currency price=$order_detail.vat.item_vat}
            </td>

            <td class="product center">
                {displayPrice currency=$order->id_currency price=$order_detail.vat.item_promo_vat}
            </td>

            <td class="product center">
                {displayPrice currency=$order->id_currency price=$order_detail.vat.gift_vat}
            </td>

            <td class="product center">
                {displayPrice currency=$order->id_currency price=$order_detail.vat.gift_promo_vat}
            </td>
            <td class="product center">
                {displayPrice currency=$order->id_currency price=$order_detail.vat.shipping_vat}
            </td>

            <td class="product center">
                {displayPrice currency=$order->id_currency price=$order_detail.vat.shipping_promo_vat}
            </td>
		</tr>

	{/foreach}
	<!-- END PRODUCTS -->

	</tbody>

</table>
