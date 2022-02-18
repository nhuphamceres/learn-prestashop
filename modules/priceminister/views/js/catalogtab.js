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
 */

var pmPageInitialized1 = false;
$(document).ready(function () {
    if (pmPageInitialized1) return;
    pmPageInitialized1 = true;

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

    $('#catalog-offers-export').click(function () {
        var url = $('#catalog-offers-url').val();
        var pAjax = new Object();

        $('#catalog-offers-form .offer-table-heading').hide();
        $('#catalog-offers-form table.offer tbody tr:gt(0)').remove();

        if ($('#catalog-offers-form input[name=send]').attr('checked'))
            action = 'export';
        else
            action = 'check';

        pAjax.type = 'POST';
        pAjax.url = url + '?action=' + action + '&all_shop=' + $('#pm_shop_all').val() + '&callback=?';
        pAjax.data = $('#catalog-offers-form').serialize() + '&context_key=' + $('input[name=context_key]').val();
        pAjax.data_type = 'json';

        if (window.console) {
            console.log("Orders URL is :" + url);
        }
        var div_loader = $('#catalog-offers-loader');
        var div_errors = $('#catalog-offers-error');
        var div_warnings = $('#catalog-offers-warning');
        var div_result = $('#catalog-offers-result');

        div_loader.show();

        div_result.html('').hide();
        div_errors.html('').hide();
        div_warnings.html('').hide();

        $.ajax({
            type: pAjax.type,
            url: pAjax.url,
            data: pAjax.data,
            dataType: pAjax.data_type,
            success: function (data) {
                div_loader.hide();
                div_errors.hide();
                div_result.hide();
                div_warnings.hide();

                if (window.console)
                    console.log(data);

                if (data.output) {
                    div_result.show();

                    $.each(data.output, function (o, output) {
                        div_result.append(output + '<br/>');
                    });
                }

                if (data.message) {
                    div_result.show();

                    $.each(data.messages, function (o, message) {
                        div_result.append(message + '<br/>');
                    });
                }
                if (data.warning) {
                    div_warnings.show();

                    $.each(data.warnings, function (w, warning) {
                        div_warnings.append(warning + '<br/>');
                    });
                }

                if (data.error) {
                    div_errors.show();

                    $.each(data.errors, function (e, errormsg) {
                        div_errors.append(errormsg + '<br/>');
                    });

                }

                if (data.count && $('#display_offers_result').attr('checked')) {
                    DisplayExportResult(data.offers);
                    listReports();
                }

            },
            error: function (data) {
                div_loader.hide();
                div_errors.show();
                div_errors.html('AJAX Error<br><br>' + data.responseText);

                ManageAjaxError(pAjax, data, div_errors);

                if (window.console)
                    console.log(data);
            }
        });
        return (false);
    });

    function DisplayExportResult(offersset) {
        var irow = 0;
        $('#catalog-offers-form table.offer tbody tr:gt(0)').remove();

        $.each(offersset, function (id_product, offers) {
            $.each(offers, function (id_attribute, offer) {
                if (irow == 0)
                    $('#catalog-offers-form .order-offers-heading').show();

                if (window.console && false)
                    console.log(offer);

                keyp = id_product + '_' + id_attribute;

                // Clone Line, Append to the table and fill the order data
                offer_line = $('#catalog-offers-form .offer-model:first').clone().appendTo('#catalog-offers-form table.table.offer tbody.offers');
                offer_line.attr('id', 'O' + keyp);

                offer_line.children('[rel=status]').html($('#offers-legend img[rel=' + offer.status + ']').clone());

                if (offer.has_attributes) {
                    offer_line.children('[rel=name]').html(offer.name);
                }
                else {
                    offer_line.children('[rel=name]').html(offer.name);
                }

                if (offer.combination_ean)
                    offer_line.children('[rel=code]').html(offer.combination_ean);
                else if (offer.ean)
                    offer_line.children('[rel=code]').html(offer.ean);

                if (Number(id_attribute) || !offer.has_attributes) {
                    offer_line.children('[rel=qty]').html(offer.quantity);
                    offer_line.children('[rel=price]').html(offer.price);
                    offer_line.children('[rel=final_price]').html(offer.final_price);
                }

                if (offer.has_children) {
                    offer_line.addClass('master_reference');
                }

                // Fill Lines
                //
                if (offer.combination_reference)
                    offer_line.children('[rel=reference]').html(offer.combination_reference);
                else if (offer.reference)
                    offer_line.children('[rel=reference]').html(offer.reference);

                offer_line.addClass(irow++ % 2 ? 'alt_row' : '');
                // checkbox = offer_line.children('td[rel=checkbox]').children('input');
                // checkbox.attr('name', 'offer_id[' + keyp + ']').val(keyp);

                offer_line.show();
            });
        });
        $('#catalog-offers-form .offer-table-heading').show();
        $('#catalog-offers-legend').show();
    }

    /*FOR PRODUCTS CREATE TAB*/


    $('#catalog-products-export, #catalog-products-check').click(function () {
        var url = $('#catalog-products-url').val();
        var pAjax = new Object();

        $('#catalog-products-form .product-table-heading').hide();
        $('#catalog-products-form table.product tbody tr:gt(0)').remove();

        if ($('#catalog-products-form input[name=send]').attr('checked'))
            action = 'export';
        else
            action = 'check';

        pAjax.type = 'POST';
        pAjax.url = url + '?action=' + action + '&all_shop=' + $('#pm_shop_all').val() + '&callback=?';
        pAjax.data = $('#catalog-products-form').serialize() + '&context_key=' + $('input[name=context_key]').val();
        pAjax.data_type = 'json';

        if (window.console) {
            console.log("Orders URL is :" + url);
        }
        var div_loader = $('#catalog-products-loader');
        var div_errors = $('#catalog-products-error');
        var div_warnings = $('#catalog-products-warning');
        var div_result = $('#catalog-products-result');

        div_loader.show();

        div_result.html('').hide();
        div_errors.html('').hide();
        div_warnings.html('').hide();

        $.ajax({
            type: pAjax.type,
            url: pAjax.url,
            data: pAjax.data,
            dataType: pAjax.data_type,
            success: function (data) {
                div_loader.hide();
                div_errors.hide();
                div_result.hide();
                div_warnings.hide();

                if (window.console)
                    console.log(data);

                if (data.output) {
                    div_result.show();

                    $.each(data.output, function (o, output) {
                        div_result.append(output + '<br/>');
                    });
                }

                if (data.message) {
                    div_result.show();

                    $.each(data.messages, function (o, message) {
                        div_result.append(message + '<br/>');
                    });
                }
                if (data.warning) {
                    div_warnings.show();

                    $.each(data.warnings, function (w, warning) {
                        div_warnings.append(warning + '<br/>');
                    });
                }

                if (data.error) {
                    div_errors.show();

                    $.each(data.errors, function (e, errormsg) {
                        div_errors.append(errormsg + '<br/>');
                    });

                }

                if (data.count && $('#display_products_result').attr('checked')) {
                    DisplayProductExportResult(data.products);
                    listReports();
                }

            },
            error: function (data) {
                div_loader.hide();
                div_errors.show();
                div_errors.html('AJAX Error<br><br>' + data.responseText);

                ManageAjaxError(pAjax, data, div_errors);

                if (window.console)
                    console.log(data);
            }
        });
        return (false);
    });

    function DisplayProductExportResult(productsset) {
        var irow = 0;
        $('#catalog-products-form table.product tbody tr:gt(0)').remove();

        $.each(productsset, function (id_product, products) {
            $.each(products, function (id_attribute, product) {
                if (irow == 0)
                    $('#catalog-products-form .order-products-heading').show();

                if (window.console && true)
                    console.log(product);

                keyp = id_product + '_' + id_attribute;

                // Clone Line, Append to the table and fill the order data
                product_line = $('#catalog-products-form .product-model:first').clone().appendTo('#catalog-products-form table.table.product tbody.products');
                product_line.attr('id', 'O' + keyp);

                product_line.children('[rel=status]').html($('#products-legend img[rel=' + product.status + ']').clone());

                if (product.has_attributes) {
                    product_line.children('[rel=name]').html(product.name/* + ' - ' + product.attributes_list*/);
                }
                else {
                    product_line.children('[rel=name]').html(product.name);
                }

                if (product.combination_ean)
                    product_line.children('[rel=code]').html(product.combination_ean);
                else if (product.ean)
                    product_line.children('[rel=code]').html(product.ean);

                if (Number(id_attribute) || !product.has_attributes) {
                    product_line.children('[rel=qty]').html(product.quantity);
                    product_line.children('[rel=price]').html(product.price);
                    product_line.children('[rel=final_price]').html(product.final_price);
                }

                if (product.has_children) {
                    product_line.addClass('master_reference');
                }
                else if (typeof(product.has_children) !== 'undefined' && !product.has_children) {
                    product_line.addClass('master_reference');
                }

                // Fill Lines
                //
                if (product.combination_reference)
                    product_line.children('[rel=reference]').html(product.combination_reference);
                else if (product.reference)
                    product_line.children('[rel=reference]').html(product.reference);

                product_line.addClass(irow++ % 2 ? 'alt_row' : '');
                // checkbox = product_line.children('td[rel=checkbox]').children('input');
                // checkbox.attr('name', 'product_id[' + keyp + ']').val(keyp);

                product_line.show();
            });
        });
        $('#catalog-products-form .product-table-heading').show();
        $('#catalog-products-legend').show();
    }

    // OnLoad Report
    $(function () {
        listReports();
    });

    function listReports() {
        var url = $('#catalog-reports-url').val();
        var pAjax = new Object();

        pAjax.type = 'POST';
        pAjax.url = url + '?action=list&all_shop=' + $('#pm_shop_all').val() + '&callback=?';
        pAjax.data = $('#catalog-reports-form').serialize() + '&context_key=' + $('input[name=context_key]').val();
        pAjax.data_type = 'json';

        if (window.console) {
            console.log("Reports URL is :" + url);
        }
        var div_loader = $('#catalog-reports-loader');
        var div_errors = $('#catalog-reports-error');
        var div_warnings = $('#catalog-reports-warning');
        var div_result = $('#catalog-reports-result');

        $('#catalog-report-summary').hide().html('');
        $('#catalog-report-details').hide().html('');

        $('#reports-none-available').hide();

        div_loader.show();

        div_result.html('').hide();
        div_errors.html('').hide();
        div_warnings.html('').hide();

        $.ajax({
            type: pAjax.type,
            url: pAjax.url,
            data: pAjax.data,
            dataType: pAjax.data_type,
            success: function (data) {
                div_loader.hide();
                div_errors.hide();
                div_result.hide();
                div_warnings.hide();

                if (window.console)
                    console.log(data);

                if (data.output) {
                    div_result.show();

                    $.each(data.output, function (o, output) {
                        div_result.append(output + '<br/>');
                    });
                }

                if (data.message) {
                    div_result.show();

                    $.each(data.messages, function (o, message) {
                        div_result.append(message + '<br/>');
                    });
                }
                if (data.warning) {
                    div_warnings.show();

                    $.each(data.warnings, function (w, warning) {
                        div_warnings.append(warning + '<br/>');
                    });
                }

                if (data.error) {
                    div_errors.show();

                    $.each(data.errors, function (e, errormsg) {
                        div_errors.append(errormsg + '<br/>');
                    });

                }

                if (data.count) {
                    $('#reports-none-available').hide();
                    DisplayReportList(data.reports);
                }
                else {
                    $('#reports-none-available').show();
                }

            },
            error: function (data) {
                div_loader.hide();
                div_errors.show();
                div_errors.html('AJAX Error<br><br>' + data.responseText);

                ManageAjaxError(pAjax, data, div_errors);

                if (window.console)
                    console.log(data);
            }
        });
        return (false);
    };


    function DisplayReportList(reportset) {
        var irow = 0;
        $('#catalog-reports-form table.report tbody tr:gt(0)').remove();

        $.each(reportset, function (r, report) {
            if (irow == 0)
                $('#catalog-reports-form .report-table-heading').show();

            if (window.console)
                console.log(report);

            // Clone Line, Append to the table and fill the order data
            report_line = $('#catalog-reports-form .report-model:first').clone().appendTo('#catalog-reports-form table.table.report tbody.reports');
            report_line.attr('rel', report.id);

            report_line.children('[rel=id]').html(report.id);
            report_line.children('[rel=start]').html(report.timestart);
            report_line.children('[rel=stop]').html(report.timestop);
            report_line.children('[rel=duration]').html(report.duration);

            if (report.link)
                report_line.children('[rel=file]').html('<a href="' + report.link + '" target="_blank">' + report.file + '</a>');
            else
                report_line.children('[rel=file]').html(report.file);

            report_line.children('[rel=items]').html(report.records);

            report_line.addClass(irow++ % 2 ? 'alt_row' : '');
            report_line.show();
        });
        $('#catalog-reports-form .report-table-heading').show();
    }

    $('#conf-reports').delegate('table.report tbody tr', 'click', function () {
        $('#conf-reports table.report tbody tr').removeClass('report-selected');
        $(this).addClass('report-selected');
    });

    $('#catalog-reports-report').click(function () {
        var url = $('#catalog-reports-url').val();
        var pAjax = new Object();

        if (!$('#conf-reports table.report tbody tr.report-selected').length) {
            alert($('#catalog-reports-select-msg').val());
            return (false);
        }
        reportid = $('#conf-reports table.report tbody tr.report-selected').attr('rel');

        pAjax.type = 'POST';
        pAjax.url = url + '?action=report&reportid=' + reportid + '&callback=?';
        pAjax.data = $('#catalog-reports-form').serialize() + '&context_key=' + $('input[name=context_key]').val();
        pAjax.data_type = 'json';

        if (window.console) {
            console.log("Report URL is :" + url);
        }
        var div_loader = $('#catalog-report-loader');
        var div_errors = $('#catalog-reports-error');
        var div_warnings = $('#catalog-reports-warning');
        var div_result = $('#catalog-reports-result');

        $('#catalog-report-summary').hide().html('');
        $('#catalog-report-details').hide().html('');

        div_loader.show();

        div_result.html('').hide();
        div_errors.html('').hide();
        div_warnings.html('').hide();

        $.ajax({
            type: pAjax.type,
            url: pAjax.url,
            data: pAjax.data,
            dataType: pAjax.data_type,
            success: function (data) {
                div_loader.hide();
                div_errors.hide();
                div_result.hide();
                div_warnings.hide();

                if (window.console)
                    console.log(data);

                if (data.output) {
                    div_result.show();

                    $.each(data.output, function (o, output) {
                        div_result.append(output + '<br/>');
                    });
                }

                if (data.message) {
                    div_result.show();

                    $.each(data.messages, function (o, message) {
                        div_result.append(message + '<br/>');
                    });
                }
                if (data.warning) {
                    div_warnings.show();

                    $.each(data.warnings, function (w, warning) {
                        div_warnings.append(warning + '<br/>');
                    });
                }

                if (data.error) {
                    div_errors.show();

                    $.each(data.errors, function (e, errormsg) {
                        div_errors.append(errormsg + '<br/>');
                    });

                }

                if (data.count) {
                    $('#catalog-report-summary').show().html(data.reports.summary);
                    $('#catalog-report-details').show().html(data.reports.details);
                }

            },
            error: function (data) {
                div_loader.hide();
                div_errors.show();
                div_errors.html('AJAX Error<br><br>' + data.responseText);

                ManageAjaxError(pAjax, data, div_errors);

                if (window.console)
                    console.log(data);
            }
        });
        return (false);
    });

});