/**
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
 */

$(document).ready(function () {
    // For PS 1.5017
    if ($('select[name="id_address"]'))
        $('select[name="id_address"]').css('width', '400px');

    $('#update_status').click(function () {

        $('#pending').fadeIn();
        $('#pending').html('<img src="' + $('#img_loader').val() + '" alt="" />');

        $.ajax({
            type: 'POST',
            url: $('#update_url').val(),
            data: $('#fnacUpdateOrder').serialize() + '&context_key=' + $('input[name=context_key]').val() + '&action=status&callback=?',
            dataType: 'json',
            success: function (data) {
                $('#pending').html(data.name)
                document.location.href = document.location.href;
            }
        });

    });

    $('#statuses').change(function () {
        if ($('#fnac_order_state').val() == 5)  return;

        if ($('#statuses').val() == 5)
            $('#trackingFields').slideDown();
        else
            $('#trackingFields').slideUp();
    });

    $('#amazon_set_status').click(function () {

        var state = parseInt($('#statuses').val());

        //if ( state != 5  )  return ;

        $('#amazon_set_status').after('&nbsp;&nbsp;<img src="' + $('#img_loader').val() + '" alt="" />');

        $.ajax({
            type: 'POST',
            url: $('#update_url').val(),
            data: $('#fnacUpdateOrder').serialize() + '&action=shipping&callback=?',
            dataType: 'json',
            success: function (data) {
                $('#fnac_order_state').val(data.state);
                $('#update_st').html(data.msg);
                document.location.href = document.location.href;
            }
        });
    });
});
