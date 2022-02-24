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

    $('#export').click(function () {

        if (!$('input[name="categoryBox[]"]').serialize().length) {
            $('#result').fadeOut();
            alert($('#msg_choose').val());
            return (false);
        }

        $('#result').fadeIn();
        $('#result').html('<img src="' + $('#img_loader').val() + '" alt="" />');


        // Affichage de la fenetre principale soColissimo
        //
        $.ajax({
            type: 'POST',
            url: $('#export_url').val(),
            data: $('#exportFnac').serialize() + '&context_key=' + $('input[name=context_key]').val(),
            success: function (data) {
                $('#result').html(data);
            }
        });
        return (false);
    });

    $('input[name=checkme]').click(function () {

        $('input[id^=categoryBox]').each(function () {
            if ($(this).attr('checked'))
                $(this).attr('checked', false);
            else
                $(this).attr('checked', 'checked');
        });

    });

});
 
 