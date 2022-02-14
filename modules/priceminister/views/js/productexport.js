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


    // get parameters - credits :
    // http://wowmotty.blogspot.com/2010/04/get-parameters-from-your-script-tag.html
    // extract out the parameters
    function gup(n, s) {
        n = n.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var p = (new RegExp("[\\?&]" + n + "=([^&#]*)")).exec(s);
        return (p === null) ? "" : p[1];
    }

    var scriptSrc = $('script[src*="productexport.js"]').attr('src');
    var path = gup('path', scriptSrc);

    // load CSS
    //
    $('head').append("<link>");
    cssi = $("head").children(":last");
    cssi.attr({
        rel: "stylesheet",
        type: "text/css",
        href: path + '/views/css/priceminister.css'
    });


    function DisplayErrors(data, id_lang) {
        $('#error-' + id_lang).html('<ul />').hide();

        if (!data.errors) return;

        var display = false;
        $.each(data.errors, function (m, errormsg) {
            if (errormsg.length) {
                display = true;
                $('#error-' + id_lang + ' ul').append('<li>' + errormsg + '</li>');
            }
        });
        if (display)
            $('#error-' + id_lang).show();

    }


    $('input[name=generateproducts]').click(function () {

        var loader = '<div align="center"><img src="' + $('#loader').val() + '" alt="" style="text-align:center" /></div>';

        lang = 'fr';
        form = $('#menudiv-' + lang + ' form');
        id_lang = $('#id_lang-' + lang).val();

        $.ajax({
            type: 'POST',
            url: $('#export_url').val() + '?action=export&lang=' + lang + '&id_lang=' + id_lang + '&callback=?',
            data: form.serialize() + '&context_key=' + $('input[name=context_key]').val(),
            dataType: 'json',
            beforeProcess: $('#result-' + id_lang).html(loader).show(),
            success: function (data) {
                $('#result-' + id_lang).html('');

                if (window.console)
                    console.log(data);

                $.each(data, function (j, json) {
                    var profile_id = json.profile;

                    $('#server-response-' + profile_id).html('');

                    if (json.outputs.length)
                        $('#result-' + id_lang).append('<span class="success">' + json.outputs + '</span><br />');
                    $.each(json.messages, function (m, message) {
                        if (message.length)
                            $('#result-' + id_lang).append('<span class="success">' + message + '</span><br />');
                    });

                    DisplayErrors(json, id_lang);

                    report = $('#report-' + profile_id);

                    if (json.server) {
                        $('#menudiv-' + lang + ' .pm-exported').show();
                        $.each(json.server, function (s, server) {
                            $('#server-response-' + profile_id).append('<b>' + s + '</b><br />' + JSON.stringify(server, null, '\t') + '<br />');
                        });
                    }
                    else {
                        $('#server-response-' + profile_id).append('<b>No answer from server or wrong response...</b><br />');
                    }
                });
            },
            error: function (data, e) {
                if (window.console) {
                    console.log(data);
                    console.log(e);
                }
            }
        });
        return;
    });


    $('input[name=synchronize]').click(function () {

        var loader = '<div align="center"><img src="' + $('#loader').val() + '" alt="" style="text-align:center" /></div>';

        lang = 'fr';
        form = $('#menudiv-' + lang + ' form');
        id_lang = $('#id_lang-' + lang).val();

        $.ajax({
            type: 'POST',
            url: $('#export_url').val() + '?action=synchronize&lang=' + lang + '&id_lang=' + id_lang + '&callback=?',
            data: form.serialize() + '&context_key=' + $('input[name=context_key]').val(),
            dataType: 'json',
            beforeProcess: $('#result-' + id_lang).html(loader).show(),
            success: function (data) {
                $('#result-' + id_lang).html(''),

                    $.each(data, function (j, json) {
                        var profile_id = json.profile;

                        $('#server-response-' + profile_id).html('');
                        $('#result-' + id_lang).html('');

                        if (json.outputs.length)
                            $('#result-' + id_lang).append('<span class="success">' + json.outputs + '</span><br />');
                        $.each(json.messages, function (m, message) {
                            if (message.length)
                                $('#result-' + id_lang).append('<span class="success">' + message + '</span><br />');
                        });

                        DisplayErrors(json, id_lang);

                        report = $('#report-' + profile_id);

                        if (window.console)
                            console.log(json);

                        if (json.server) {
                            $.each(json.server, function (s, server) {
                                $('#server-response-' + profile_id).append('<b>' + s + '</b><br />' + JSON.stringify(server, null, '\t') + '<br />');
                            });
                        }
                        else {
                            $('#server-response-' + profile_id).append('<b>No answer from server or wrong response...</b><br />');
                        }
                    });
            },
            error: function (data, e) {
                if (window.console) {
                    console.log(data);
                    console.log(e);
                }
            }
        });
        return;
    });


    $('li[id^="menu-"]').click(function () {
        result = $(this).attr('id').match('^(.*)-(.*)$');
        lang = 'fr';

        $('input[name=selected_tab]').val(lang);

        if (!$(this).hasClass('selected')) {
            $('li[id^="menu-"]').removeClass('selected');
            $(this).addClass('selected');
            $('div[id^="menudiv-"]').hide();
            $('div[id^="menudiv-' + lang + '"]').show();
        }
    });

});
