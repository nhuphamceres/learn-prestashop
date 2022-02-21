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


    $('#import-orders').click(function () {
        var debug = parseInt($('#cdiscount-debug').val());

        var url = $('#import-orders-url').val();
        var pAjax = new Object();

        pAjax.type = 'POST';
        pAjax.url = url + '&callback=?';
        pAjax.data = $('#import-orders-form').serialize() + '&context_key=' + $('input[name=context_key]').val();
        pAjax.data_type = 'json';

        if (window.console) {
            console.log("Import Orders");
            console.log("URL is :" + url);
        }
        $('#import-loader').show();
        $('#import-orders-result').html('').hide();
        $('#import-orders-error').html('').hide();
        $('#order_list').html('').hide();
        $('#console').html('');

        $.ajax({
            type: pAjax.type,
            url: pAjax.url,
            data: pAjax.data,
            dataType: pAjax.data_type,
            success: function (data) {
                $('#import-loader').hide();
                $('#import-orders-success').html('').hide();

                if (window.console) {
                    console.log("Success");
                    console.log(data);
                }

                if (debug && data.stdout && data.stdout.length)
                    $('#console').append(data.stdout).show();

                if (data.error) {
                    $('#import-orders-error').show();
                    $('#import-orders-error').append(data.output);

                    $.each(data.errors, function (e, errormsg) {
                        $('#import-orders-error').append(errormsg);
                    });
                }
                else
                {
                    if (debug && data.stdout && data.stdout.length)
                        $('#console').append('<pre>' + data.stdout + '</pre>');
                }

                if (data.orders)
                {
                    if (data.output && !data.error) {
                        $('#import-orders-hr').show();
                        $('#import-orders-result').show().html('');
                    }

                    $('#import-table').show().find('.select-all').attr('checked', false);
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

                    if (data.clogistique)
                        $('#conf-import td[rel="clogistique"]').show();

                    if (data.multichannel)
                        $('#conf-import td[rel="multichannel"]').show();

                    var addcol=0;
                    if (data.multichannel)
                        addcol++;
                    if (data.clogistique)
                        addcol++;
                    if (addcol)
                        $('#conf-import td[rel="order-container"]').attr('colspan', 8 + addcol);

                    $('#import-legend').show();

                    InitOrder();
                }


            },
            error: function (data) {
                $('#import-loader').hide();
                $('#import-orders-error').show();
                $('#import-orders-error').html('AJAX Error');

                ManageAjaxErrorImport(pAjax, data, $('#import-orders-error'));

                if (window.console)
                    console.log(data);
            }
        });
        return (false);

    });

    function ManageAjaxErrorImport(aCall, data, outdiv) {
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
        $('#import-order').unbind('click');
        $('#import-order').click(function (event) {
            var url = $('#import-order-url').val();
            var pAjax = new Object();
            var order_list = $('input[name="selected_orders[]"]').serialize();

            pAjax.type = 'POST';
            pAjax.url = url + '&context_key=' + $('input[name=context_key]').val() + '&callback=?';
            pAjax.data = $('#import-orders-form').serialize();
            pAjax.data_type = 'json';

            $('#import-orders-error').html('').hide();

            if (window.console) {
                console.log("Import Orders");
                console.log("URL is :" + url);
            }

            if (!order_list.length) {
                alert($('#text-select-orders').val());
                return (false);
            }
            $('#import-loader').show();

            if (window.console)
                console.log("Orders:" + order_list);

            $.ajax({
                type: pAjax.type,
                url: pAjax.url,
                data: pAjax.data,
                dataType: pAjax.data_type,
                success: function (data) {
                    $('#import-orders-success').html('').hide();
                    $('#import-orders-result').hide();
                    $('#import-orders-hr').hide();
                    /*
                     if ( data.msg )
                     $('#import-orders-result').show().html(data.msg);
                     */
                    $('#import-loader').hide();
                    if (data.error) {
                        $('#import-orders-error').show();

                        $.each(data.errors, function (e, errormsg) {
                            $('#import-orders-error').append(errormsg);
                        });
                    }
                    if (typeof debug !== 'undefined' && debug && data.console && data.console.length) {
                        $('#console').append(data.console);
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
                    $('#import-loader').hide();
                    $('#import-orders-success').html('').hide();
                    $('#import-orders-result').hide().html('');
                    $('#import-orders-error').html('');

                    $('#import-loader').hide();
                    $('#import-orders-error').html('AJAX Error').show();

                    ManageAjaxErrorImport(pAjax, data, $('#import-orders-error'));

                    if (window.console)
                        console.log(data);
                }
            });


        });

    }


});
