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

var pageInitialized1 = false;
$(document).ready(function () {
    if (pageInitialized1) return;
    pageInitialized1 = true;
    $('.hint').slideDown();

    $('input[name="amazon_lang"]:first').attr('checked', 'checked');

    $('li[id^="menu-"]').click(function () {
        result = $(this).attr('id').match('^(.*)-(.*)$');
        tab = result[2];

        $('input[name=selected_tab]').val(tab);

        if (!$(this).hasClass('selected')) {
            $('li[id^="menu-"]').removeClass('selected');
            $(this).addClass('selected');
            $('div[id^="menudiv-"]').hide();
            $('div[id^="menudiv-' + tab + '"]').show();
        }
    });

    function ManageAjaxError(aCall, data, outdiv) {
        if (window.console) {
            console.log('Ajax Error', aCall, data);
        }
        outdiv.show().html($('#serror').val());

        if (data.output)
            outdiv.append('<br />' + data.output);

        if (data.responseText)
            outdiv.append('<br />' + data.responseText);

        outdiv.append('<hr />');
        outdiv.append($('#sdebug').val() + ':  ');

        outdiv.append('<form method="' + aCall.type + '" action="' + aCall.url + '?debug=1&debug_header&' + aCall.data + '" target="_blank">' +
            '<input type="submit" class="button" id="send-debug" value="Execute in Debug Mode" /></form>');
    }

    $('#remove-failed-orders').click(function() {
        var $selected = $(this),
            $warningZone = $('#amazon-remove-failed-orders-warning'),
            $errorZone = $('#amazon-remove-failed-orders-error'),
            $successZone = $('#amazon-remove-failed-orders-success'),
            $loader = $('#amazon-remove-failed-orders-loader');

        if ($selected.is(":disabled")) {
            return false;
        }

        $.ajax({
            type: 'POST',
            url: $('#import_url').val(),
            dataType: 'jsonp',
            data: [
                $('#amazonParams').serialize(),
                $.param({
                    action: 'clear_failed_orders',
                    context_key: $('#context_key').val(),
                }),
            ].join('&'),
            beforeSend: function() {
                $warningZone.hide();
                $errorZone.hide();
                $successZone.hide();
                $loader.fadeIn();
                $selected.attr('disabled', 'disabled');
            },
            complete: function () {
                $loader.fadeOut();
                $selected.removeAttr('disabled');
            },
            success: function (data) {
                $warningZone.html('');
                $errorZone.html('');
                $successZone.html('');

                if (data.warning && data.warnings.length) {
                    $.each(data.warnings, function (e, message) {
                        $warningZone.append(message + '<br />');
                    });
                    $warningZone.show();
                }

                if (data.error && data.errors.length) {
                    $.each(data.errors, function (e, message) {
                        $errorZone.append(message + '<br />');
                    });
                    $errorZone.show();
                }

                // removed successfully
                if (data.message && data.messages.length) {
                    $('#cron_failed_orders tbody').empty();

                    $.each(data.messages, function (e, message) {
                        $successZone.append(message + '<br />');
                    });
                    $successZone.show();
                }
            },
        });
    });

    $('#submit-orders-list').click(function () {
        var $warningZone = $('#amazon-import-warning'),
            $errorZone = $('#amazon-import-error'),
            $loader = $('#amz-loader');

        if (!$('input[name=amazon_lang]:checked').length && !$('input[name="amazon_lang"]').val()) {
            alert($('#msg_lang').val());
            return false;
        }

        if ($('input[name="amazon_lang"][value="europe"]:checked').length)
            europe = $('input[name="amazon_lang"][value="europe"]:checked').attr('rel');
        else
            europe = '0';

        pAjax = {
            type: 'POST',
            url: $('#orders_url').val(),
            data_type: 'jsonp',
            data: $('#amazonParams').serialize() + '&' + $('#amazonOrderOptions').serialize() + '&amazon_lang=' + $('input[name="amazon_lang"]:checked').val() + '&europe=' + europe + '&context_key=' + $('#context_key').val() + '&rand=' + new Date().valueOf(),
        };

        $.ajax({
            type: pAjax.type,
            url: pAjax.url,
            dataType: pAjax.data_type,
            data: pAjax.data,
            beforeSend: function() {
                $warningZone.hide().html('');
                $errorZone.hide().html('');
                $loader.fadeIn();
            },
            complete: function() {
                $loader.fadeOut();
            },
            success: function (data) {
                if (window.console)
                    console.log(data);

                if (data.orders && data.count) {
                    DisplayOrders(data.orders);
                }
                else if (!data.error)
                {
                    $('#amazon-import-error').append($('#no_orders').val() + '<br />').show();
                    $('table.order tbody tr:gt(0)').remove();
                }

                if (data.warning && data.warnings) {
                    $.each(data.warnings, function (e, message) {
                        $warningZone.append(message + '<br />');
                    });
                    $warningZone.show();
                }

                if (data.output && data.output.length)
                    $('#amazon-import-error').append(data.output + '<br />').show();
                if (data.errors && data.errors.length)
                    $('#amazon-import-error').append(data.errors + '<br />').show();
            },
            error: function (data) {
                ManageAjaxError(pAjax, data, $('#amazon-import-error'));
            }
        });

    });

    function DisplayOrders(orders) {
        var irow = 0;
        $('table.order tbody tr:gt(0)').remove();

        $.each(orders, function (o, order) {
            if (irow == 0)
                $('#order-table-heading').show();

            if (window.console)
                console.log(order);

            // Clone Line, Append to the table and fill the order data
            order_line = $('#order-model').clone().appendTo('table.table.order tbody');
            order_line.attr('id', 'O' + o);

            // Fill Lines
            //
            order_line.children('[rel=flag]').html(order.flag);
            order_line.children('[rel=date]').html(order.date);
            order_line.children('[rel=id]').html(order.link);
            order_line.children('[rel=status]').html(order.status);
            if (order.invoice)
                order_line.children('[rel=invoice]').html(order.invoice); // KAM_CHG
            order_line.children('[rel=customer]').html(order.customer);
            order_line.children('[rel=shipping]').html(order.shipping);
            order_line.children('[rel=fulfillment]').html(order.fulfillment);
            order_line.children('[rel=business]').html(order.business);
            order_line.children('[rel=quantity]').html(order.quantity);
            order_line.children('[rel=total]').html(order.total);
            order_line.addClass(irow++ % 2 ? 'alt_row' : '');
            checkbox = order_line.children('td[rel=checkbox]').children('input');
            checkbox.attr('name', 'order_id[' + o + ']').val(o);

            if (order.imported || order.canceled || order.pending) {
                checkbox.attr('disabled', true);
                order_line.addClass('imported_row2');
            } else {
                checkbox.attr('disabled', false);
            }

            order_line.show();
        });
    }

    // Import Orders
    //
    $('#submit-orders-import').click(function () {
        var $orderList = $('.order-check:checked');
        if (!$orderList.length && !$('input[name="amazon_lang"]').val()) {
            alert($('#msg_select').val());
            return false;
        }
        $('#amazon-import-error').hide();
        $('#amazon-import-warning').hide();

        // Hide Checkboxes, Display Loader
        $.each($orderList.not(':disabled'), function (e, message) {
            $(this).hide();
            $(this).after('<img src="' + $('#img_loader_small').val() + '" alt="" class="amz-tmp-loader" />');
        });

        var europe = $('input[name="amazon_lang"][value="europe"]:checked').attr('rel');
        europe = typeof europe === 'undefined' ? '0' : europe;
        
        var pAjax = {
            type: 'POST',
            url: $('#import_url').val(),
            dataType: 'jsonp',
            data: [
                $('#amazonParams').serialize(),
                $('#amazonOrderOptions').serialize(),
                $('#amazonOrders').serialize(),
                $.param({
                    amazon_lang: $('input[name="amazon_lang"]:checked').val(),
                    europe: europe,
                    context_key: $('#context_key').val(),
                    rand: new Date().valueOf(),
                }),
            ].join('&'),
        };

        $.ajax({
            type: pAjax.type,
            url: pAjax.url,
            dataType: pAjax.dataType,
            data: pAjax.data,
            success: function (data) {
                if (window.console)
                    console.log(data);
                $('#amz-loader').fadeOut();

                // Restore Checkboxes
                $orderList.attr('disabled', true).show();
                $('.amz-tmp-loader').remove();

                $('#amazon-import-error').html('');
                $('#amazon-import-warning').html('');

                if ((typeof(data.error) != 'undefined' && data.error) || (typeof(data.message) != 'undefined' && data.message))
                {
                    if (window.console)
                        console.log(data.messages);

                    $.each(data.errors, function (e, message) {
                        $('#amazon-import-error').append(message + '<br />');
                    });
                    $.each(data.messages, function (e, message) {
                        $('#amazon-import-error').append(message + '<br />');
                    });
                    $('#amazon-import-error').show();
                }

                if (typeof(data.warning) != 'undefined' && data.warning) {
                    if (window.console)
                        console.log(data.warnings);

                    $.each(data.warnings, function (e, message) {
                        $('#amazon-import-warning').append(message + '<br />');
                    });
                    $('#amazon-import-warning').show();
                }

                if (typeof(data.count) != 'undefined' && data.count) {

                    if (window.console)
                        console.log(data.count + ' orders');

                    DisplayImported(data.orders);
                }
                else {
                    $('#amazon-import-error').append($('#no_orders').val() + '<br />').show();
                }
            },
            error: function (data) {
                $('#amz-loader').fadeOut();

                ManageAjaxError(pAjax, data, $('#amazon-import-error'));
            }
        });

    });

    function DisplayImported(orders) {
        $.each(orders, function (o, order) {
            if (order.status != true) {
                $('#O' + o).removeClass('alt_row').addClass('error_row');
                return;
            }
            else
                $('#O' + o).removeClass('alt_row').addClass('imported_row');

            $('#O' + o).after('<tr><td colspan="3"> </td><td colspan="7"><table id="OD' + o + '" class="order-line"></table></td></tr>');
            $('#O' + o + ' td[rel=id]').html(order.link);

            $.each(order.products, function (p, product) {

                console.log(product);
                product_info =
                    '<tr>\n' +
                    '<td>' + product.SKU + '</td>' +
                    '<td>' + product.ASIN + '</td>' +
                    '<td>' + product.product + '</td>' +
                    '<td>' + product.quantity + '</td>' +
                    '<td>' + product.currency + '</td>' +
                    '<td align="right">' + product.price + '</td>' +
                    '<tr>' + '\n';

                $('#OD' + o).append(product_info);
            });
        });
    }

    if ($.isFunction($(document).on)) {
        // Misc Functions
        //
        $(document).on('click', 'table.order tbody tr', function (e) {
            if (e.target.type !== 'checkbox') {
                $(':checkbox', this).trigger('click');
            }
        });

        $('#checkme').on('click', function () {
            $('.order-check').each(function () {
                if ($(this).attr('checked'))
                    $(this).attr('checked', false);
                else if (!$(this).attr('disabled'))
                    $(this).attr('checked', 'checked');
            });
        });
    }

    if ($.datepicker.initialized !== 'undefined') {
        $("#datepickerTo").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"
        });

        $("#datepickerFrom").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"
        });
    }

    $('#cron_failed_orders_handler').click(function() {
        $(this).toggleClass('dropup').toggleClass('dropdown');
        $('#cron_failed_orders').slideToggle();
    });
});
