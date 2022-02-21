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

    var debug = parseInt($('#cdiscount-debug').val());

    $('#accept-orders').click(function () {
        var url = $('#accept-orders-url').val();
        var pAjax = new Object();

        pAjax.type = 'POST';
        pAjax.url = url + '&callback=?';
        pAjax.data = $('#accept-orders-form').serialize() + '&context_key=' + $('input[name=context_key]').val();
        pAjax.data_type = 'json';

        if (window.console) {
            console.log("Import Orders");
            console.log("URL is :" + url);
        }
        $('#accept-loader').show();
        $('#accept-orders-result').html('').hide();
        $('#accept-orders-error').html('').hide();
        $('#accept_order_list').html('').hide();
        $('#accept-orders-success').hide();

        $('#console').html('').hide();

        $.ajax({
            type: pAjax.type,
            url: pAjax.url,
            data: pAjax.data,
            dataType: pAjax.data_type,
            success: function (data) {
                $('#accept-loader').hide();
                $('#accept-orders-success').html('');

                if (window.console)
                    console.log(data);

                if (debug && typeof(data.stdout) !== 'undefined' && data.length)
                    $('#console').append(data.stdout).show();

                if (data.error) {
                    $('#accept-orders-result').hide();
                    $('#accept-orders-hr').show();
                    $('#accept-orders-error').show();
                    $('#accept-orders-error').append(data.output);
                    $('#accept-order').hide();
                    $.each(data.errors, function (e, errormsg) {
                        $('#accept-orders-error').append(errormsg);
                    });
                    $.each(data.output, function (o, output) {
                        $('#accept-orders-result').append(output);
                    });
                }
                else {

                    $('#accept-orders-error').hide();
                    $('#accept-orders-hr').show();
                    $('#accept-orders-result').show();

                    $.each(data.output, function (o, output) {
                        $('#accept-orders-result').append(output);
                    });

                    if (data.orders) {
                        $('#accept-table').show();
                        $('#accept-order').show();
                        $('#accept_order_list').show();

                        $.each(data.orders, function (o, order) {
                            $('#accept_order_list').append(order);
                        });

                        if (data.clogistique)
                            $('#menu-accept td[rel="clogistique"]').show();

                        if (data.multichannel)
                            $('#menu-accept td[rel="multichannel"]').show();

                        var addcol=0;
                        if (data.multichannel)
                            addcol++;
                        if (data.clogistique)
                            addcol++;
                        if (addcol)
                            $('#menu-accept td[rel="order-container"]').attr('colspan', 8 + addcol);

                        $('#accept-legend').show();

                        InitOrder();
                    }

                }

            },
            error: function (data) {
                $('#accept-loader').hide();
                $('#accept-orders-error').show();
                $('#accept-orders-error').html('AJAX Error<br><br>' + data.responseText);

                ManageAjaxErrorAccept(pAjax, data, $('#accept-orders-error'));

                if (window.console)
                    console.log(data);
            }
        });
        return (false);

    });

    function ManageAjaxErrorAccept(aCall, data, outdiv) {
        if (window.console) {
            console.log('Ajax Error');
            console.log(aCall)
            console.log(data);
        }
        outdiv.show().html($('#serror').val())

        if (data.output)
            outdiv.append('<br />' + data.output);

        if (data.responseText)
            outdiv.append('<br />' + data.responseText);

        outdiv.append('<hr />');
        outdiv.append($('#sdebug').val() + ':  ');

        outdiv.append('<form method="' + aCall.type + '" action="' + aCall.url + '&debug=1&' + aCall.data + '" target="_blank">' +
        '<input type="submit" class="button" id="send-debug" value="Execute in Debug Mode" /></form>');
    }

    function InitOrder() {
        $('#accept-order').unbind('click');
        $('#accept-order').click(function (event) {
            url = $('#accept-order-url').val();
            order_list = $('input[name="selected_orders[]"]').serialize();

            if (window.console) {
                console.log("Import Orders");
                console.log("URL is :" + url);
            }

            if (!order_list.length) {
                alert($('#text-select-orders').val());
                return (false);
            }
            $('#accept-loader').show();


            $('#console').html('');


            if (window.console)
                console.log("Orders:" + order_list);

            $.ajax({
                type: 'POST',
                url: url + '&callback=?',
                data: $('#accept-orders-form').serialize() + '&context_key=' + $('input[name=context_key]').val(),
                dataType: 'json',
                success: function (data) {
                    $('#accept-orders-success').html('');
                    $('#accept-orders-result').hide();
                    $('#accept-orders-hr').hide();

                    $('#accept-loader').hide();

                    if (debug && data.stdout && data.stdout.length) {
                        $('#console').append(data.stdout).show();
                        console.log(data);
                    }

                    if (data.error) {
                        $('#accept-orders-hr').show();
                        $('#accept-orders-error').show();
                        $('#accept-order').hide();
                        $.each(data.errors, function (e, errormsg) {
                            $('#accept-orders-error').append(errormsg);
                        });
                    }
                    if (data.count) {
                        $('#accept-orders-success').show();

                        $.each(data.output, function (o, output) {
                            $('#accept-orders-success').append(output);
                        });
                        $.each(data.orders, function (o, order) {
                            $('#o-' + o).attr('disabled', true);
                        });
                    }
                },
                error: function (data) {
                    $('#accept-loader').hide();
                    $('#accept-orders-success').html('')
                    $('#accept-orders-result').hide().html('');
                    $('#accept-orders-error').html('');
                    $('#order_list').hide().html('');
                    $('#accept-loader').hide();
                    $('#accept-orders-error').html('AJAX Error').show();

                    if (window.console)
                        console.log(data);
                }
            });


        });

    }


});
