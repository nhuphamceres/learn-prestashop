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

    $('#import-orders').click(function () {
        var url = $('#import-orders-url').val();

        if (window.console) {
            console.log("Import Orders");
            console.log("URL is :" + url);
        }
        $('#import-loader').show();
        $('#import-orders-result').html('').hide();
        $('#import-orders-error').html('').hide();
        $('#order_list').html('').hide();
        $('#console').html('').show();
        $('#import-console').html('');

        $.ajax({
            type: 'POST',
            url: url + '&callback=?',
            data: $('#import-orders-form').serialize() + '&context_key=' + $('input[name=context_key]').val(),
            dataType: 'json',
            success: function (data) {
                $('#import-loader').hide();
                $('#import-orders-success').html('');

                if (window.console) {
                    console.log("Success");
                    console.log(data);
                }

                if (data.stdout && data.stdout.length)
                    $('#import-console').append(data.stdout).show();

                if (data.error) {
                    $('#import-orders-error').show();
                    $('#import-orders-error').append(data.output);

                    $.each(data.errors, function (e, errormsg) {
                        $('#import-orders-error').append(errormsg);
                    });
                }
                if (data.orders) {
                    if (data.output && !data.error) {
                        $('#import-orders-hr').show();
                        $('#import-orders-result').show().html('');
                    }

                    $('#import-table').show();
                    $('#order_list').html('').show();
                    $('#import-order').show();

                    $.each(data.orders, function (o, order) {
                        if (window.console)
                            console.log(o);

                        $('#order_list').append(order);
                    });
                    $.each(data.output, function (o, output) {
                        $('#import-orders-result').append(output);
                    });
                    InitOrder();
                }


            },
            error: function (data) {
                $('#import-loader').hide();
                $('#import-orders-error').show();
                $('#import-orders-error').html('AJAX Error');
                if (data.responseText)
                    $('#import-orders-error').append('<br />' + data.responseText);
                if (window.console)
                    console.log(data);
            }
        });
        return (false);

    });

    function InitOrder() {
        var importOrder = $('#import-order'),
            importLoader = $('#import-loader'),
            importError = $('#import-orders-error');

        importOrder.unbind('click');
        importOrder.click(function (event) {
            var url = $('#import-order-url').val();
            var order_list = $('input[name="selected_orders[]"]').serialize();

            if (window.console) {
                console.log("Import Orders");
                console.log("URL is :" + url);
            }

            if (!order_list.length) {
                alert($('#text-select-import-orders').val());
                return (false);
            }
            importLoader.show();

            if (window.console)
                console.log("Orders:" + order_list);

            $.ajax({
                type: 'POST',
                url: url + '&callback=?',
                data: $('#import-orders-form').serialize() + '&context_key=' + $('input[name=context_key]').val(),
                dataType: 'json',
                success: function (data) {
                    $('#import-orders-success').html('');
                    $('#import-orders-result').hide();
                    $('#import-orders-hr').hide();
                    /*
                     if ( data.msg )
                     $('#import-orders-result').show().html(data.msg);
                     */
                    importLoader.hide();
                    if (data.error) {
                        importError.show();

                        $.each(data.errors, function (e, errormsg) {
                            importError.append(errormsg);
                        });
                    }
                    if (data.count) {
                        $.each(data.output, function (o, output) {
                            $('#import-orders-success').append(output).show();
                        });
                        $.each(data.orders, function (o, order) {
                            $('#o-' + o).attr('disabled', true);
                        });
                    }
                },
                error: function (data) {
                    importLoader.hide();
                    $('#import-orders-success').html('');
                    $('#import-orders-result').hide().html('');
                    importError.html('');
                    $('#order_list').hide().html('');
                    importLoader.hide();
                    importError.html('AJAX Error').show();
                    if (data.responseText)
                        $('#import-orders-error').append('<br />' + data.responseText);

                    if (window.console)
                        console.log(data);
                }
            });


        });

    }


});
