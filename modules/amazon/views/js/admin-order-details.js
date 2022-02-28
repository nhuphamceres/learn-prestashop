/**
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
 */
(function ($) {
    $(document).ready(function () {
        $('input[name="product_id_order_detail"]', $('#orderProducts')).each(function () {
            var $tr = $(this).parents('tr').first(),
                idOrderDetail = $(this).val(),
                $customization = $('.marketplace_detail.amz_customization[data-value=' + idOrderDetail + ']'),
                $additionalInfo = $('.marketplace_detail.amz_additional_info[data-value=' + idOrderDetail + ']'),
                colLength = $tr.find('td:visible').length;

            if ($additionalInfo.length) {
                $tr.after('<tr class="product-line-custom"><td></td><td colspan="' + (colLength - 1) + '">' + $additionalInfo.html() + '</td></tr>');
                $tr.find('td').css('border-bottom', 'none');
            }
            if ($customization.length) {
                $tr.after('<tr class="product-line-custom"><td></td><td colspan="' + (colLength - 1) + '">' + $customization.html() + '</td></tr>');
                $tr.find('td').css('border-bottom', 'none');
            }
        });
    });
})(jQuery);
