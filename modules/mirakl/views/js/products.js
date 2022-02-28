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
var pageInitialized1 = false;
$(document).ready(function() {
    if(pageInitialized1) return;
    pageInitialized1 = true;

    if ($.datepicker.initialized !== 'undefined')
    {
        $("#datepickerTo2").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"});

        $("#datepickerFrom2").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"});
    }

    function ManageAjaxError(aCall, data, outdiv)
    {
        if (window.console)
        {
            console.log('Ajax Error') ;
            console.log(aCall)
            console.log(data) ;
        }
        outdiv.show().html( $('#serror').val() )

        if (data.output)
            outdiv.append('<br /><pre>' + data.output + '</pre>');

        if (data.responseText)
            outdiv.append('<br /><pre>' + data.responseText + '</pre>');

        outdiv.append('<hr />') ;
        outdiv.append( $('#sdebug').val() + ':  ') ;

        outdiv.append('<form method="' + aCall.type + '" action="' + aCall.url + '&debug=1&' + aCall.data + '" target="_blank">' +
            '<input type="submit" class="button" id="send-debug" value="Execute in Debug Mode" /></form>') ;
    }

    $('li[id^="menu-"]').click(function()
    {
        var result = $(this).attr('id').match('^(.*)-(.*)$');
        var lang = result[2];

        $('input[name=selected_tab]').val(lang);

        if (!$(this).hasClass('selected'))
        {
            $('li[id^="menu-"]').removeClass('selected');
            $(this).addClass('selected');
            $('div[id^="menudiv-"]').hide();
            $('div[id^="menudiv-' + lang + '"]').show();
        }
    });

    function DisplayLastExport()
    {
        $('#send-loader').show()

        // Display last export files
        //
        $.ajax({
            type: 'POST',
            url: $('#create-products-url').val() + '&action=last_export&callback=?',
            dataType:'jsonp',
            success: function(data) {
                $('#send-loader').hide();

                if (window.console)
                    console.log("Loading last exported files...");

                if (data.length)
                {
                    $('#create-products-latest').html(data).show();
                }

                $('#send-products').click(function() {
                    $('#send-loader').show();
                    $.ajax({
                        type: 'POST',
                        url: $('#create-products-url').val() + '&action=products_send&callback=?',
                        success: function(data) {

                            $('#send-loader').hide();

                            if (data.length)
                                $('#send-products-result').show().html(data);
                        }
                    });
                });

            }
        });
    }
    DisplayLastExport();

    function DisplayCreateError(data)
    {
        if (!data.errors)
            return(false);

        $('#create-products-error').html('').show();

        if (data.output)
            $('#create-products-error').append(data.output);

        $.each(data.errors, function(e, errormsg) {
            $('#create-products-error').append(errormsg + '<br />');
        });
    }

    function DisplayLastUpdate()
    {
        var dest = $('#update-products-url').val() + '&action=last_update&callback=?';
        // Display last export files
        //
        $.ajax({
            type: 'POST',
            url: dest, //$('#update-products-url').val() + '&action=last_update&callback=?',
            data: 'context_key=' + $('input[name=context_key]').val(),
            dataType: 'json',
            beforeProcess: function(data) {
                $('#update-loader').show();
            },
            success: function(data) {
                $('#update-loader').hide();

                if (window.console)
                {
                    console.log("Loading last updated files...");
                    console.log(data);
                }

                if (typeof data == 'undefined')
                    return;

                if (data.msg && data.urls)
                {
                    $('#update-products-latest-hr').show();
                    $('#update-products-latest').html('<b>' + data.msg + '</b>');
                    $('#update-products-latest').append('<ul>');
                    $.each(data.urls, function(u, download) {
                        $('#update-products-latest').append('<li><a href="' + download + '&seed=' + new Date().valueOf() + '" class="url" target="_blank" title=' + download + '>' + u + '</a></li>');
                    });

                    $('#update-products-latest').append('</ul>');
                    $('#update-products-latest').show();
                }

            }
        });
    }
    DisplayLastUpdate();


    $('#update-products').click(function() {
        var url = $('#update-products-url').val();
        var loader = $('#update-loader'),
            errorElement = $('#update-products-error'),
            resultElement = $('#update-products-result');

        resultElement.html('').hide();
        errorElement.html('').hide();

        if (window.console)
        {
            console.log("update Products");
            console.log("URL is :" + url);
        }
        loader.show();

        var pAjax        = {};
        pAjax.type       = 'POST' ;
        pAjax.url        = url ;
        pAjax.data       = $('#update-products-form').serialize() + '&context_key=' + $('input[name=context_key]').val();
        pAjax.data_type  = 'jsonp';

        $.ajax(pAjax.url, {
            method: 'POST',
            data: pAjax.data ,
            success: function(data) {
                loader.hide();
                errorElement.hide();

                if (data.error) {
                    resultElement.hide();
                    errorElement.show();

                    if (data.msg && !data.count) {
                        errorElement.html(data.msg + '<br />');
                        data.msg = null;
                    } else if (data.output)
                        errorElement.append(data.output);

                    if (data.errors) {
                        $.each(data.errors, function(e, errormsg) {
                            errorElement.append(errormsg + '<br />');
                        });
                    }
                }

                if ( data.msg || data.output ) {
                    resultElement.show();

                    if (data.msg && data.msg.length) {
                        resultElement.html(data.msg + '<br />').show();
                    }

                    if (data.output && data.output.length) {
                        resultElement.append(data.output);
                    }

                    if (data.urls) {
                        resultElement.append('<br /><ul>');
                        $.each(data.urls, function(u, url) {
                            resultElement.append('<li><a href="' + url + '" class="url" target="_blank" title=' + url + '>' + u + '</a></li>');
                        });
                        resultElement.append('</ul>');
                    }
                }
            },
            error: function(data) {
                loader.hide();
                ManageAjaxError(pAjax, data, errorElement) ;
            }
        });
        return(false);

    });


    $('#create-products').click(function() {
        var url = $('#create-products-url').val();
        var resultElement = $('#create-products-result'),
            errorElement = $('#create-products-error'),
            loader = $('#create-loader');

        resultElement.html('').hide();
        errorElement.html('').hide();

        if (window.console) {
            console.log("Create Products");
            console.log("URL is :" + url);
        }
        loader.show();

        var pAjax        = {};
        pAjax.type       = 'POST';
        pAjax.url        = url + '&context_key=' + $('input[name=context_key]').val() + '&action=export&callback=?';
        pAjax.data       = $('#create-products-form').serialize();

        if ( window.console )
            console.log(pAjax) ;

        $.ajax(pAjax.url, {
            method: 'POST',
            data: pAjax.data,
            success: function(data) {
                loader.hide();

                if (window.console) {
                    console.log("Success");
                    console.log(data);
                }

                if (data.error && data.errors.length) {
                    DisplayCreateError(data);
                }
                resultElement.show();
                resultElement.html(data.msg);

                if (data.output && data.output.length) {
                    resultElement.append(data.output);
                }

                if (data.file && data.file.length) {
                    resultElement.append('<ul />');
                    resultElement.append('<li><a href="' + data.file + '" target="_blank" >' + data.file + '</a></li>');
                }

                if (data.urls) {
                    resultElement.append('<br /><ul>');
                    $.each(data.urls, function(u, url) {
                        resultElement.append('<li><a href="' + url + '" class="url" target="_blank" title=' + url + '>' + u + '</a></li>');
                    });
                    resultElement.append('</ul>');
                }
                DisplayLastExport();
            },
            error: function(data) {
                loader.hide();
                ManageAjaxError(pAjax, data, $('#create-products-error')) ;
            }
        });
        return(false);

    });

    $("#menuTab > li.selected").first().trigger("click");
});
