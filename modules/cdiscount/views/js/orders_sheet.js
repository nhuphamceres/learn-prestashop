/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Common-Services Co., Ltd. is strictly forbidden.
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
 * @package   CDiscount
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail:  support.cdiscount@common-services.com
 */

$(document).ready(function () {
    // For PS 1.5017
    if ($('select[name="id_address"]'))
        $('select[name="id_address"]').css('width', '400px');


    $('#cd-update').click(function () {
        $(this).hide();
        $('#cd-update-loader').show();
    });

    $('#cd-valid').click(function () {
        $(this).hide();
        $('#cd-valid-loader').show();
    });

    var tab_selector = $('#cdiscount-order-ps16');

    if (tab_selector.length) {
        $(tab_selector).insertBefore('#formAddPaymentPanel')
    }
console.log('x');
});
