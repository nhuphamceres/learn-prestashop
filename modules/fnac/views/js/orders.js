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

    $(document).ajaxError(function (data) {
        $('#result').toggleClass('conf error');

        if (data.responseText)
            $('#result').append('<br />' + data.responseText);

        if (window.console)
            console.log(data);
    });
    $('#lookup').click(function () {

        if (!$('#datepickerFrom').val() || !$('#datepickerTo').val()) {
            alert($('#msg_date').val());
            return (false);
        }
        $('#pending').fadeIn();

        $('#result').html('<img src="' + $('#img_loader').val() + '" class="floader" alt="" />');


        $.ajax({
            type: 'POST',
            url: $('#orders_url').val(),
            data: $('#updateFnac').serialize() + '&context_key=' + $('input[name=context_key]').val(),
            success: function (data) {
                $('#result').html(data);
                initOrders();
            },
            error: function(err) {
                window.console && console.log(err);
            }
        });

    });

    function initOrders() {
        $('input[name=checkme]').click(function () {
            $('input[id^=order_]').each(function () {
                if ($(this).attr('checked'))
                    $(this).attr('checked', false);
                else
                    $(this).attr('checked', 'checked');
            });
        });


        $('#retrieve').click(function () {
            if (!$('input[id^=order_]:checked').length) {
                alert('nothing to do');
                return (false);
            }

            $('#result2').html('<img id="floader" class="floader" src="' + $('#img_loader').val() + '" alt="" />');
            $('#result2').fadeIn();

            $('input[id^=order_]:checked').each(function () {

                order_id = $(this).val();
                console.log(order_id);

                $.ajax({
                    type: 'POST',
                    url: $('#import_url').val() + '?platform=' + $('input[name=platform]:checked').val() + '&token_order=' + $('input[name=token_order]').val(),
                    data: 'order_id=' + order_id + '&context_key=' + $('input[name=context_key]').val(),
                    success: function (data) {
                        $('#floader').remove(),
                            $('#result2').append(data);
                    }
                });

                $(this).attr('checked', false);
                $(this).attr('disabled', 'disabled');
            });

        });
    }

    // Tabs
    //
    $('li[id^="menu-"]').click(function () {
        result = $(this).attr('id').match('^(.*)-(.*)$');
        lang = result[2];

        $('input[name=selected_tab]').val(lang);

        if (!$(this).hasClass('selected')) {
            $('li[id^="menu-"]').removeClass('selected');
            $(this).addClass('selected');
            $('div[id^="menudiv-"]').hide();
            $('div[id^="menudiv-' + lang + '"]').show();
        }
    });

    if ($.datepicker.initialized !== 'undefined') {
        $("#datepickerFrom").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"
        });

        $("#datepickerTo").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"
        });
    }
});