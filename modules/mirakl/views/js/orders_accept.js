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
 *
 * @package   Mirakl
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.mirakl@common-services.com
 */

$(document).ready(function () {

    $('#accept-orders-search').click(function () {
        var url = $('#accept-orders-url').val();

        if (window.console) {
            console.log("Import Orders");
            console.log("URL is :" + url);
        }
        $('#accept-loader').show();
        $('#accept-orders-result').html('').hide();
        $('#accept-orders-error').html('').hide();
        $('#accept_order_list').html('').hide();
        $('#accept-orders-success').hide();

        $('#console').html('').show();

        $.ajax({
            type: 'POST',
            url: url + '&callback=?',
            data: $('#accept-orders-form').serialize() + '&context_key=' + $('input[name=context_key]').val(),
            dataType: 'json',
            success: function (data) {
                $('#accept-loader').hide();
                $('#accept-orders-success').html('')

                if (window.console)
                    console.log(data);

                if (data.stdout && data.stdout.length)
                    $('#accept-console').append(data.stdout).show();

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
                        InitOrder();
                    }

                }

            },
            error: function (data) {
                $('#accept-loader').hide();
                $('#accept-orders-error').show();
                $('#accept-orders-error').html('AJAX Error');
                if (window.console)
                    console.log(data);
            }
        });
        return (false);

    });

    function InitOrder() {
        var acceptButton = $('#accept-order-button'),
            loader = $('#accept-loader'),
            acceptSuccess = $('#accept-orders-success'),
            acceptError = $('#accept-orders-error'),
            acceptHr = $('#accept-orders-hr');

        acceptButton.show();
        acceptButton.click(function (event) {
            url = $('#accept-order-url').val();
            order_list = $('input[name="selected_orders[]"]').serialize();

            if (window.console) {
                console.log("Import Orders");
                console.log("URL is :" + url);
            }

            if (!order_list.length) {
                alert($('#text-select-accept-orders').val());
                return (false);
            }
            loader.show();
            $('#console').html('').show();

            if (window.console)
                console.log("Orders:" + order_list);

            $.ajax({
                type: 'POST',
                url: url + '&callback=?',
                data: $('#accept-orders-form').serialize() + '&context_key=' + $('input[name=context_key]').val(),
                dataType: 'json',
                success: function (data) {
                    acceptSuccess.html('');
                    $('#accept-orders-result').hide();
                    acceptHr.hide();

                    loader.hide();

                    if (data.stdout)
                        $('#console').append(data.stdout);

                    if (data)
                        console.log(data);

                    if (data.error) {
                        acceptHr.show();
                        acceptError.show();
                        $('#accept-order').hide();
                        $.each(data.errors, function (e, errormsg) {
                            acceptError.append(errormsg);
                        });
                    }
                    if (data.count) {
                        acceptSuccess.show();

                        $.each(data.output, function (o, output) {
                            acceptSuccess.append(output);
                        });
                        $.each(data.orders, function (o, order) {
                            $('#o-' + o).attr('disabled', true);
                        });
                    }
                },
                error: function (data) {
                    loader.hide();
                    acceptSuccess.html('');
                    $('#accept-orders-result').hide().html('');
                    acceptError.html('');
                    $('#order_list').hide().html('');
                    loader.hide();
                    acceptError.html('AJAX Error').show();

                    if (window.console)
                        console.log(data);
                }
            });


        });

    }


});
