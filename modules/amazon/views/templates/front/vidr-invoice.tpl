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

{$style_tab}


<table width="100%" id="body" border="0" cellpadding="0" cellspacing="0" style="margin:0;">
    <!-- Invoicing -->
    <tr>
        <td colspan="12">
            {$addresses_tab}
        </td>
    </tr>

    <tr>
        <td colspan="12" height="30">&nbsp;</td>
    </tr>

    <!-- TVA Info -->
    <tr>
        <td colspan="12">
            {$summary_tab}
        </td>
    </tr>

    <tr>
        <td colspan="12" height="20">&nbsp;</td>
    </tr>

    <!-- Product -->
    <tr>
        <td colspan="12">
            {$product_tab}
        </td>
    </tr>

    <tr>
        <td colspan="12" height="10">&nbsp;</td>
    </tr>

    <!-- TVA -->
    <tr>
        <!-- Code TVA -->
        <td colspan="6" class="left"></td>
        <td colspan="1">&nbsp;</td>
        <!-- Calcule TVA -->
        <td colspan="6" rowspan="5" class="right">
            {$total_tab}
        </td>
    </tr>

    <tr>
        <td colspan="12" height="10">&nbsp;</td>
    </tr>

    <tr>
        <td colspan="6" class="left">
            {$payment_tab}
        </td>
        <td colspan="1">&nbsp;</td>
    </tr>

    <tr>
        <td colspan="6" class="left">
            {$shipping_tab}
        </td>

        <td colspan="1">&nbsp;</td>
    </tr>

    <tr>
        <td colspan="12" height="20">&nbsp;</td>
    </tr>

    <tr>
        <!-- Code TVA -->
        <td colspan="6" class="left">
            {$tax_tab}
        </td>

        <td colspan="1">&nbsp;</td>
        <!-- Calcule TVA -->
    </tr>

    <tr>
        <td colspan="12" height="20">&nbsp;</td>
    </tr>

    <tr>
        <td colspan="12">
            {$vat_tab}
        </td>
    </tr>

    <!-- Hook -->
    {if isset($HOOK_DISPLAY_PDF)}
        <tr>
            <td colspan="12" height="30">&nbsp;</td>
        </tr>

        <tr>
            {*<td colspan="2">&nbsp;</td>*}
            <td colspan="6" rowspan="2" class="right" style="margin-right: -50px;">
                {$HOOK_DISPLAY_PDF}
            </td>
            <td colspan="1">&nbsp;</td>
        </tr>
    {/if}

    <tr>
        <td colspan="12" height="80">&nbsp;</td>
    </tr>

    <tr>
        <td colspan="2">&nbsp;</td>
        <td colspan="10">
            <table>
                <tr>
                    <td>
                        <p>{$citation}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

</table>
