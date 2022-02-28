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
<table id="summary-tab" width="100%">
	<tr>
        <th class="header small" valign="middle">
            {if $is_credit_note}
                {l s='Credit Note Number' d='Shop.Pdf' pdf='true' mod='amazon'}
            {else}
                {l s='Invoice Number' d='Shop.Pdf' pdf='true' mod='amazon'}
            {/if}
        </th>
        <th class="header small" valign="middle">
            {if $is_credit_note}
                {l s='Credit Note Date' d='Shop.Pdf' pdf='true' mod='amazon'}
            {else}
                {l s='Invoice Date' d='Shop.Pdf' pdf='true' mod='amazon'}
            {/if}
        </th>
        {* VIDR: Replace Order Reference / Order data by Shipping Reference / Shipping date *}
		<th class="header small" valign="middle">{l s='Order Reference' d='Shop.Pdf' pdf='true' mod='amazon'}</th>
		<th class="header small" valign="middle">{l s='Shipping date' d='Shop.Pdf' pdf='true' mod='amazon'}</th>
	</tr>
	<tr>
		<td class="center small white">{$title|escape:'html':'UTF-8'}</td>
        <td class="center small white">{dateFormat date=$now full=0}</td>
		<td class="center small white">{$vidr_order_refs|escape:'htmlall':'UTF-8'}</td>
		<td class="center small white">{dateFormat date=$vidr_shipment_date full=0}</td>
	</tr>
</table>
