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

var pageInitialized1 = false;
$(document).ready(function () {
    if (pageInitialized1) return;
    pageInitialized1 = true;

    if ($.datepicker.initialized !== 'undefined') {
        $("#datepickerTo2").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"
        });

        $("#datepickerFrom2").datepicker({
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


    function DisplayLastExport() {
        $('#send-loader').show()

        // Display last export files
        //
        $.ajax({
            type: 'POST',
            url: $('#create-products-url').val() + '&context_key=' + $('input[name="context_key"]').val() + '&action=last_export&callback=?',
            success: function (data) {
                $('#send-loader').hide();

                if (window.console)
                    console.log("Loading last exported files... context key:"+ $('input[name="context_key"]').val());

                $('#create-products-latest').html(data).show();

                $('#send-products').click(function () {
                    $('#send-loader').show();
                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: $('#create-products-url').val() + '&context_key=' + $('input[name="context_key"]').val() + '&action=products_send',
                        success: function (response) {
                            $('#send-loader').hide();
                            var $sentDebug = $('#send-products-debug'),
                                $sentDebugContent = $sentDebug.find('div');
                            $('#send-products-result').html(response.data).show();
                            $sentDebugContent.html(response.debug);
                            $sentDebug.show();
                        },
                    });
                });

            }
        });
    }

    DisplayLastExport();

    function DisplayCreateError(data) {
        if (!data.output && !data.errors)
            return (false);
        $('#create-products-error').html('');
        $('#create-products-error').show();
        $('#create-products-error').append(data.output);
        $.each(data.errors, function (e, errormsg) {
            $('#create-products-error').append(errormsg + '<hr style="width:30%" />');
        });
    }

    $('#menu-create, #test-button').click(function () {
        var url = $('#merge-products-url').val();

        if (window.console) {
            console.log("Merge Products");
            console.log("URL is :" + url);
        }

        $('#create-loader').show();

        pAjax = new Object();
        pAjax.type = 'POST';
        pAjax.url = url + '&action=export&callback=?';
        pAjax.data = '&context_key=' + $('input[name="context_key"]').val();
        pAjax.data_type = 'json';

        if (window.console)
            console.log(pAjax);


        $.ajax({
            type: pAjax.type, url: pAjax.url, data: pAjax.data, dataType: pAjax.data_type,
            success: function (data) {
                $('#create-loader').hide();

                if (window.console) {
                    console.log("Success");
                    console.log(data);
                }

                if (data.error) {
                    DisplayCreateError(data);
                }

                $('#merge-products-result').show();

                if (data.count) {
                    $('#merge-products-result').html(data.msg + ' (' + data.count + ')<br />');
                } else {
                    $('#merge-products-result').html(data.msg);
                }

                if (data.output) {
                    $('#merge-products-result').append(data.output);
                }

                $('#create-products-form').fadeIn('slow');

                if (!data.error) {
                    $('#merge-products').fadeOut('slow');
                }
            },
            error: function (data) {
                $('#create-loader').hide();
                $('#create-products-form').fadeIn('slow');

                ManageAjaxError(pAjax, data, $('#merge-products-error'));
            }
        });
        return (false);


    });

    $('#create-products').click(function () {
        var url = $('#create-products-url').val();

        $('#create-products-result').html('').hide();
        $('#create-products-error').html('').hide();

        if (window.console) {
            console.log("Create Products");
            console.log("URL is :" + url);
        }
        $('#create-loader').show();

        pAjax = new Object();
        pAjax.type = 'POST';
        pAjax.url = url + '&action=export&callback=?';
        pAjax.data = $('#create-products-form').serialize() + '&context_key=' + $('input[name="context_key"]').val();
        pAjax.data_type = 'json';

        if (window.console)
            console.log(pAjax);

        $.ajax({
            type: pAjax.type, url: pAjax.url, data: pAjax.data, dataType: pAjax.data_type,
            success: function (data) {
                $('#create-loader').hide();

                if (window.console) {
                    console.log("Success");
                    console.log(data);
                }

                if (data.error) {
                    DisplayCreateError(data);
                }
                $('#create-products-result').show();
                $('#create-products-result').html(data.msg + ' (' + data.count + ')<br />');
                if (data.output) {
                    $('#create-products-result').append(data.output);
                }
                if (data.file) {
                    $('#create-products-result').append('<ul />');
                    $('#create-products-result').append('<li><a href="' + data.file + '" target="_blank" >' + data.file + '</a></li>');

                    DisplayLastExport();
                }
            },
            error: function (data) {
                $('#create-loader').hide();

                ManageAjaxError(pAjax, data, $('#create-products-error'));
            }
        });
        return (false);

    });


    function DisplayCSVError(data) {
        if (!data.output && !data.errors)
            return (false);
        $('#csv-products-result').hide();
        if (data.errors) {
            $('#csv-products-error').show();
            $('#csv-products-error').append(data.output);
            $.each(data.errors, function (e, errormsg) {
                $('#csv-products-error').append(errormsg + '<hr style="width:30%" />');
            });
        }
    }

    $('#csv-products').click(function () {
        url = $('#csv-products-url').val();
        $("#csv-products-error").html("").hide();
        if (window.console) {
            console.log("Create Products");
            console.log("URL is :" + url);
        }
        $('#csv-loader').show();

        pAjax = new Object();
        pAjax.type = 'POST';
        pAjax.url = url + '&action=export_csv&callback=?';
        pAjax.data = $('#csv-products-form').serialize() + '&context_key=' + $('input[name="context_key"]').val(),
            pAjax.data_type = 'json',

            $.ajax({
                type: pAjax.type, url: pAjax.url, data: pAjax.data, dataType: pAjax.data_type,
                success: function (data) {
                    $('#csv-loader').hide();

                    if (window.console) {
                        console.log("Success");
                        console.log(data);
                    }
                    $('#csv-products-error').html('').hide();

                    DisplayCSVError(data);

                    $('#csv-products-result').show();
                    $('#csv-products-result').html(data.msg + ' (' + data.count + ')<br />');
                    if (data.output) {
                        $('#csv-products-result').append(data.output);
                    }
                    if (data.file) {
                        var l = data.file.length;

                        if (l == 1) {
                            $('#csv-products-result').append('<ul />');
                            $('#csv-products-result ul').append('<li><a href="' + data.file[0][1] + '" target="_blank" >1.- ' + data.file[0][0] + '</a></li>');
                        } else if (l > 1) {
                            $('#csv-products-result').append('<ul />');
                            for (var i = 0; i < data.file.length; i++) {
                                $('#csv-products-result ul').append('<li><a href="' + data.file[i][1] + '" target="_blank" >' + (i + 1) + ".- " + data.file[i][0] + '</a></li>');
                            }

                        }
                    }

                },
                error: function (data) {
                    $('#csv-loader').hide();

                    ManageAjaxError(pAjax, data, $('#csv-products-error'));
                }
            });
        return (false);

    });


    $('#update-products').click(function () {
        url = $('#update-products-url').val();

        $('#update-products-result').html('').hide();
        $('#update-products-error').html('').hide();

        if (window.console) {
            console.log("update Products");
            console.log("URL is :" + url);
        }
        $('#update-loader').show();

        pAjax = new Object();
        pAjax.type = 'POST';
        pAjax.url = url + '&action=export&callback=?';
        pAjax.data = $('#update-products-form').serialize() + '&context_key=' + $('input[name="context_key"]').val(),
            pAjax.data_type = 'json',

            $.ajax({
                type: pAjax.type,
                url: pAjax.url,
                data: pAjax.data,
                dataType: pAjax.data_type,
                success: function (data) {
                    $('#update-loader').hide();
                    if (window.console)
                        console.log(data);
                    if (data.error) {
                        if (data.msg) {
                            $('#update-products-result').show();
                            $('#update-products-result').html(data.msg);
                        }
                        else
                            $('#update-products-result').hide();

                        $('#update-products-error').show();
                        $('#update-products-error').html(data.output);
                        $.each(data.errors, function (e, errormsg) {
                            $('#update-products-error').append(errormsg + '<br />');
                        });
                    }
                    else {
                        $('#update-products-error').hide();
                        $('#update-products-result').show();
                        $('#update-products-result').html(data.msg + '<br />');
                        if (data.output.length) {
                            $('#update-products-result').append(data.output);
                        }
                    }
                    DisplayHistory();
                },
                error: function (data) {
                    $('#update-loader').hide();

                    ManageAjaxError(pAjax, data, $('#update-products-error'));
                }
            });
        return (false);

    });

    $('#purge-replace').click(function (event) {

        if ($(this).attr('checked'))
            $('#purge-warning').fadeIn();
        else
            $('#purge-warning').fadeOut();

    });


    function DisplayReport(data) {
        $('#history-report-content').show();
        $('#history-report-content pre').html('');

        /*
         LogDate: "2014-07-30T10:06:22.793"
         LogMessage: "Offre mise à jour↵"
         OfferIntegrationStatus: "Integrated"
         ProductEan: "0696198666021"
         SellerProductId: "26936"
         Sku: "MEZ0696198666021"
         Validated: "true"
         */
        $('#history-report-content pre').html(data.content);
    }

    function GetReport(report_line) {
        PackageID = parseInt($(report_line).html());

        if (!PackageID)
            return;

        pAjax = new Object();
        pAjax.type = 'GET';
        pAjax.url = $('#update-products-url').val() + '&context_key=' + $('input[name="context_key"]').val() + '&id=' + PackageID + '&action=report&callback=?';
        pAjax.data = '';
        pAjax.dataType = 'jsonp';

        $('#history-loader').show();

        $.ajax({
            type: pAjax.type,
            dataType: pAjax.dataType,
            url: pAjax.url,
            success: function (data, status, xhr) {
                $('#history-loader').hide();
                $('#history-report-content pre').html('');
                $('#history-report-content').hide();

                if (data.success) {
                    console.log('success');
                    DisplayReport(data);
                }
                else {
                    console.log('no success');
                }
            },
            error: function (data, status, xhr) {

                $('#history-loader').hide();

                ManageAjaxError(pAjax, data, $('#update-products-error'));
            }
        });
    }

    function DisplayHistory() {
        $('.reportid').unbind('click');

        pAjax = new Object();
        pAjax.type = 'GET';
        pAjax.url = $('#update-products-url').val() + '&context_key=' + $('input[name="context_key"]').val() + '&action=history&callback=?';
        pAjax.data = '';

        if (window.console)
            console.log(pAjax);

        $.ajax({
            type: pAjax.type,
            url: pAjax.url,
            data: pAjax.data,
            success: function (data, status, xhr) {
                if (xhr.status == '204')
                    $('#update-products-history').html('').hide();
                else {
                    $('#update-products-history').html(data).show();
                    $('.reportid').click(function () {
                        GetReport(this);

                    });
                }
            },
            error: function (data, status, xhr) {

                //$('#create-loader').hide();

                ManageAjaxError(pAjax, data, $('#update-products-error'));

            }
        });
    }

    DisplayHistory();

    // Multiple selection for checkboxes
    (function ($) {
        $.fn.enableCheckboxRangeSelection = function () {
            var lastCheckbox = null;
            var $spec = this;
            $spec.unbind("click.checkboxrange");
            $spec.bind("click.checkboxrange", function (e) {
                if (lastCheckbox !== null && (e.shiftKey || e.metaKey)) {
                    $spec.slice(
                        Math.min($spec.index(lastCheckbox), $spec.index(e.target)),
                        Math.max($spec.index(lastCheckbox), $spec.index(e.target)) + 1
                    ).attr({checked: e.target.checked ? "checked" : ""});
                }
                lastCheckbox = e.target;
            });
        };
    })(jQuery);

    $('.category').enableCheckboxRangeSelection();

});
