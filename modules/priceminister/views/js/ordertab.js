/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
 * Contact by Email :  support.priceminister@common-services.com
 * Contact on Prestashop forum : delete (Olivier B)
 */

var pmPageInitialized1 = false;
$(document).ready(function () {
    if (pmPageInitialized1) return;
    pmPageInitialized1 = true;

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

        $("#datepickerFromA").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"
        });

        $("#datepickerToA").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"
        });
    }
    function ManageAjaxError(aCall, data, outdiv) {
        if (window.console) {
            console.log('Ajax Error');
            console.log(aCall)
            console.log(data);
        }
        outdiv.show().html($('input[name=text_ajax_error]').val())

        if (data.output)
            outdiv.append('<br />' + data.output);

        if (data.responseText)
            outdiv.append('<br />' + data.responseText);

        outdiv.append('<hr />');
        outdiv.append($('#sdebug').val() + ':  ');

        outdiv.append('<form method="' + aCall.type + '" action="' + aCall.url + '&debug=1&' + aCall.data + '" target="_blank">' +
            '<input type="submit" class="button btn btn-default" id="send-debug" value="Execute in Debug Mode" /></form>');
    }


    $('#accept-orders').click(function () {
        orders_list_action('accept');
    });
    $('#import-orders').click(function () {
        orders_list_action('import');
    });


    $('input[id^="accept-order-"]').click(function () {
        orders_action('accept');
    });
    $('input[id^="import-order-"]').click(function () {
        orders_action('import');
    });

    function orders_action(method) {
        var url = $('#' + method + '-order-url').val();
        var pAjax = new Object();

        pAjax.type = 'POST';
        pAjax.url = url + '&callback=?';
        pAjax.data = $('#' + method + '-orders-form').serialize() + '&context_key=' + $('input[name=context_key]').val();
        pAjax.data_type = 'json';

        if (window.console) {
            console.log("Method :" + method);
            console.log("Orders URL is :" + url);
        }
        //$('input[id^="' + method + '-order-"]').hide();

        var orders_loader = $('#' + method + '-loader');
        var orders_errors = $('#' + method + '-orders-error');
        var orders_warnings = $('#' + method + '-orders-warning');
        var orders_result = $('#' + method + '-orders-result');

        orders_loader.show();

        orders_result.html('').hide();
        orders_errors.html('').hide();
        orders_warnings.html('').hide();

        $.ajax({
            type: pAjax.type,
            url: pAjax.url,
            data: pAjax.data,
            dataType: pAjax.data_type,
            success: function (data) {
                orders_loader.hide();
                orders_errors.hide();
                orders_result.hide();
                orders_warnings.hide();

                if (window.console)
                    console.log(data);

                if (data.output) {
                    orders_result.show();

                    $.each(data.output, function (o, output) {
                        orders_result.append(output + '<br/>');
                    });
                }

                if (data.message) {
                    orders_result.show();

                    $.each(data.messages, function (o, message) {
                        orders_result.append(message + '<br/>');
                    });
                }
                if (data.warning) {
                    orders_warnings.show();

                    $.each(data.warnings, function (w, warning) {
                        orders_warnings.append(warning + '<br/>');
                    });
                }

                if (data.error) {
                    orders_errors.show();

                    $.each(data.errors, function (e, errormsg) {
                        orders_errors.append(errormsg + '<br/>');
                    });

                }

                DisplayImportOrderResult(data.orders, method);
            },
            error: function (data) {
                orders_loader.hide();
                orders_errors.show();
                orders_errors.html('AJAX Error<br><br>' + data.responseText);

                ManageAjaxError(pAjax, data, orders_errors);

                if (window.console)
                    console.log(data);
            }
        });
        return (false);
    };
    function DisplayImportOrderResult(orders, method) {
        $.each(orders, function (o, order) {

            order_line = $('#O' + order.purchaseid);

            if (order.status) {
                order_line.find('input[type=checkbox]').attr('disabled', true);
                order_line.addClass('imported_row2 success');
            }
            else
                order_line.addClass('error_row danger');

            if (order.link)
                order_line.find('td[rel=id]').html(order.link);

            if (order.items) {
                $.each(order.items, function (i, item) {

                    item_line = $('#OI-' + order.purchaseid + '-' + item.itemid);

                    if (order.status && item.status) {
                        item_line.find('input[type=checkbox]').attr('disabled', true);
                    }
                    else if (!item.status) {
                        item_line.addClass('error_row danger');
                    }
                });
            }
        });
    }

    function orders_list_action(method) {
        var url = $('#' + method + '-orders-url').val();
        var pAjax = new Object();

        pAjax.type = 'POST';
        pAjax.url = url + '&callback=?';
        pAjax.data = $('#' + method + '-orders-form').serialize() + '&context_key=' + $('input[name=context_key]').val();
        pAjax.data_type = 'json';

        if (window.console) {
            console.log("Method :" + method);
            console.log("Orders URL is :" + url);
        }
        $('input[id^="' + method + '-order-"]').hide();

        var orders_loader = $('#' + method + '-loader');
        var orders_errors = $('#' + method + '-orders-error');
        var orders_result = $('#' + method + '-orders-result');
        var orders_warnings = $('#' + method + '-orders-warning');

        orders_loader.show();

        orders_result.html('').hide();
        orders_errors.html('').hide();
        orders_warnings.html('').hide();

        $.ajax({
            type: pAjax.type,
            url: pAjax.url,
            data: pAjax.data,
            dataType: pAjax.data_type,
            success: function (data) {
                orders_loader.hide();
                orders_errors.hide();
                orders_result.hide();
                orders_warnings.hide();

                if (window.console)
                    console.log(data);

                if (data.output) {
                    orders_result.show();

                    $.each(data.output, function (o, output) {
                        orders_result.append(output + '<br/>');
                    });
                }

                if (data.message) {
                    orders_result.show();

                    $.each(data.messages, function (o, message) {
                        orders_result.append(message + '<br/>');
                    });
                }

                if (data.warning) {
                    orders_warnings.show();

                    $.each(data.warnings, function (o, warning) {
                        orders_warnings.append(warning + '<br/>');
                    });
                }

                if (data.error) {
                    orders_errors.show();

                    $.each(data.errors, function (e, errormsg) {
                        orders_errors.append(errormsg + '<br/>');
                    });

                }

                if (data.order) {
                    $('input[id^="' + method + '-order-"]').show();
                    $('#' + method + '-orders-form textarea[name="encoded-xml"]').val(data.encoded_xml);
                    DisplayImportOrders(data.orders, method);
                }


            },
            error: function (data) {
                orders_loader.hide();
                orders_errors.show();
                orders_errors.html('AJAX Error<br><br>' + data.responseText);

                ManageAjaxError(pAjax, data, orders_errors);

                if (window.console)
                    console.log(data);
            }
        });
        return (false);
    };


    function DisplayImportOrders(orders, method) {
        var irow = 0;
        $('#' + method + '-orders-form table.order tbody tr:gt(0)').remove();

        $.each(orders, function (o, order) {
            if (irow == 0)
                $('#' + method + '-orders-form .order-table-heading').show();

            if (window.console)
                console.log(order);

            //if (order.status == 'Pending' && $('#statuses').val() != 'Pending')
            //    return;

            // Clone Line, Append to the table and fill the order data
            order_line = $('#' + method + '-orders-form .order-model:first').clone().appendTo('#' + method + '-orders-form table.table.order tbody.orders');
            order_line.attr('id', 'O' + o);

            // Fill Lines
            //
            order_line.children('[rel=date]').html(order.purchasedate);
            order_line.children('[rel=id]').html(order.link);
            order_line.children('[rel=customer]').html(order.customer);
            order_line.children('[rel=shipping]').html(order.deliveryinformation.shippingtype);
            order_line.children('[rel=fulfillment]').html(order.deliveryinformation.isfullrsl);
            order_line.children('[rel=quantity]').html(order.quantity);
            order_line.children('[rel=total]').html(order.amount);
            order_line.addClass(irow++ % 2 ? 'alt_row' : '');
            checkbox = order_line.children('td[rel=checkbox]').children('input');
            checkbox.attr('name', 'order_id[' + o + ']').val(o);

            if (order.imported) {
                checkbox.remove();
                order_line.children('[rel=checkbox]').html($('#' + method + '-orders-form #import-legend img[rel=imported]').clone());
                order_line.addClass('imported_row2 success');
            }
            else if (order.importable) {
                checkbox.attr('checked', true);
            }
            else {
                checkbox.attr('checked', false).attr('disabled', true);
            }

            DisplayImportOrderItems(order, method);

            order_line.show();
        });

        $('#' + method + '-legend').show();
        $('#' + method + '-order').show();
    }

    function DisplayImportOrderItems(order, method) {
        var irow = 0;

        var purchaseid = order.purchaseid;
        var items = order.items;

        var item_table_id = 'I' + purchaseid;
        var item_table = $('#' + method + '-orders-form .item-model:first').clone().attr('id', item_table_id);

        $('#O' + purchaseid).after(item_table);

        $.each(items.item, function (i, item) {

            if (!item)
                return;

            if (window.console)
                console.log(item);

            // Clone Line, Append to the table and fill the order data
            item_line = item_table.find('.order-items:first').clone().appendTo(item_table.find('table tbody'));
            item_line.show();

            // Fill Lines
            //
            item_line.attr('id', 'OI-' + purchaseid + '-' + item.itemid);
            item_line.children('[rel=sku]').html(item.sku);
            item_line.children('[rel=itemid]').html(item.itemid);
            item_line.children('[rel=headline]').html(item.headline);
            item_line.children('[rel=ispreorder]').html(item.ispreorder);
            item_line.children('[rel=itemstatus]').html(item.itemstatus);

            item_line.addClass(irow++ % 2 ? 'alt_row' : '');

            var oos_image = item_line.children('td[rel=checkbox]').children('img[rel=oos]');
            var unknown_image = item_line.children('td[rel=checkbox]').children('img[rel=un]');
            var unimportable_image = item_line.children('td[rel=checkbox]').children('img[rel=non]');
            var checkbox = item_line.children('td[rel=checkbox]').children('input');

            checkbox.attr('name', 'items[' + purchaseid + '][' + item.itemid + ']').val(item.itemid);

            if (order.imported) {
                checkbox.attr('disabled', true);
                item_line.addClass('imported_row2 success');

                unimportable_image.show();
            }
            else {
                if (!item.exists)
                    unknown_image.show();
                else if (!item.stock)
                    oos_image.show();
                else if (item.importable)
                    checkbox.attr('checked', true).show();
                else
                    unimportable_image.show();
            }

            item_line.show();
        });

    }

    if ($.isFunction($(document).on)) {
        $(document).on('click', 'table.order tr', function (e) {
            if (e.target.type !== 'checkbox') {
                $(':checkbox', this).trigger('click');
            }
        });
        $(document).on('click', '.order-item', function (e) {
            if (e.target.type !== 'checkbox') {
                $(':checkbox', this).trigger('click');
            }
        });
    }

    $('#check_all_orders').change(function () {
        var state = Boolean($(this).attr('checked'));
        $('.order-check').not(':disabled').attr('checked', state);
    });

});
