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
    // get parameters - credits :
    // http://wowmotty.blogspot.com/2010/04/get-parameters-from-your-script-tag.html
    // extract out the parameters
    function gup(n, s) {
        n = n.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var p = (new RegExp("[\\?&]" + n + "=([^&#]*)")).exec(s);
        return (p === null) ? "" : p[1];
    }

    var scriptSrc = $('script[src*="product_extme.js"]').attr('src');
    var marketplace = gup('marketplace', scriptSrc);

    if (window.console)
        console.log('Loading', marketplace);

    var section = $('#product-options-' + marketplace);

    var mirakl_options = $('meta[name="' + marketplace + '-options"]').attr('content');
    var mirakl_options_json = $('meta[name="' + marketplace + '-options-json"]').attr('content');

    if (!mirakl_options || !mirakl_options.length) return (false);

    // PS 1.4 or 1.5
    if ($('#product').length) {
        var product_form = $('#product');
        Mirakl_Init();
    }
    else {
        var product_form = $('#product_form');
        setTimeout(Mirakl_Init, 2000);
    }

    function Mirakl_Init() {
        if (window.console)
            console.log('Init Mirakl');

        $.ajax({
            type: 'GET',
            url: mirakl_options,
            data: $('form[name=product]').attr('action') + '&rand=' + new Date().valueOf(),
            beforeSend: function (data) {
            },
            success: function (data) {

                // PS 1.5
                if ($('#step1 .separation:eq(2)').length) {
                    $('#step1 .separation:eq(2)').parent().after().append(data);
                    $('#step1 .separation:eq(2)').parent().after().append('<div class="separation"></div>');
                }
                else if ($('#step1 hr:eq(1)').length) {
                    // PS < 1.5
                    $('#step1 hr:eq(1)').parent().parent().after(data);
                }
                //P.S. 1.6
                else if ($('div#product_options').length) {
                    $("<hr></hr><div class='bootstrap'><table><tbody>" + data + "</tbody></table></div>").insertAfter('div#product_options');
                }
                ProductMiraklOptionInit();
            }
        });
    }

    function ProductMiraklOptionInit() {
        $('#productmirakl-save-options').click(function () {
            $.ajax({
                type: 'POST',
                url: mirakl_options_json,
                data: product_form.serialize() + '&action=set&rand=' + new Date().getTime() + '&callback=?',
                beforeSend: function (data) {
                    $('#result-mirakl').html('').hide()
                },
                success: function (data) {
                    $('#result-mirakl').html(data).show()
                }
            });
        });

        // Disabling Products
        //
        $('.mirakl-propagate-disable-cat').click(function () {

            if (!confirm($('#mirakl-text-propagate-cat').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: mirakl_options_json,
                data: product_form.serialize() + '&action=propagate-disable-cat&rand=' + new Date().valueOf() + '&callback=?',
                beforeSend: function (data) {
                    $('#mirakl-extra-disable-loader').show();
                    $('#result-mirakl').html('').hide()
                },
                success: function (data) {
                    $('#mirakl-extra-disable-loader').hide();
                    $('#result-mirakl').html(data).show()
                }
            });
        });

        $('.mirakl-propagate-disable-shop').click(function () {

            if (!confirm($('#mirakl-text-propagate-shop').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: mirakl_options_json,
                data: product_form.serialize() + '&action=propagate-disable-shop&rand=' + new Date().valueOf() + '&callback=?',
                beforeSend: function (data) {
                    $('#mirakl-extra-disable-loader').show();
                    $('#result-mirakl').html('').hide()
                },
                success: function (data) {
                    $('#mirakl-extra-disable-loader').hide();
                    $('#result-mirakl').html(data).show()
                }
            });
        });

        $('.mirakl-propagate-disable-manufacturer').click(function () {

            if (!confirm($('#mirakl-text-propagate-man').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: mirakl_options_json,
                data: product_form.serialize() + '&action=propagate-disable-manufacturer&rand=' + new Date().valueOf() + '&callback=?',
                beforeSend: function (data) {
                    $('#mirakl-extra-disable-loader').show();
                    $('#result-mirakl').html('').hide()
                },
                success: function (data) {
                    $('#mirakl-extra-disable-loader').hide();
                    $('#result-mirakl').html(data).show()
                }
            });
        });


        // Force Product force
        //
        $('.mirakl-propagate-force-cat').click(function () {

            if (!confirm($('#mirakl-text-propagate-cat').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: mirakl_options_json,
                data: product_form.serialize() + '&action=propagate-force-cat&rand=' + new Date().valueOf() + '&callback=?',
                beforeSend: function (data) {
                    $('#mirakl-extra-force-loader').show();
                    $('#result-mirakl').html('').hide()
                },
                success: function (data) {
                    $('#mirakl-extra-force-loader').hide();
                    $('#result-mirakl').html(data).show()
                }
            });
        });

        $('.mirakl-propagate-force-shop').click(function () {

            if (!confirm($('#mirakl-text-propagate-shop').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: mirakl_options_json,
                data: product_form.serialize() + '&action=propagate-force-shop&rand=' + new Date().valueOf() + '&callback=?',
                beforeSend: function (data) {
                    $('#mirakl-extra-force-loader').show();
                    $('#result-mirakl').html('').hide()
                },
                success: function (data) {
                    $('#mirakl-extra-force-loader').hide();
                    $('#result-mirakl').html(data).show()
                }
            });
        });

        $('.mirakl-propagate-force-manufacturer').click(function () {

            if (!confirm($('#mirakl-text-propagate-man').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: mirakl_options_json,
                data: product_form.serialize() + '&action=propagate-force-manufacturer&rand=' + new Date().valueOf() + '&callback=?',
                beforeSend: function (data) {
                    $('#mirakl-extra-manufacturer-loader').show();
                    $('#result-mirakl').html('').hide()
                },
                success: function (data) {
                    $('#mirakl-extra-manufacturer-loader').hide();
                    $('#result-mirakl').html(data).show()
                }
            });
        });


        $('#productmirakl-options').click(function () {
            image = $('#mirakl-toggle-img');

            var newImage = image.attr('rel');
            var oldImage = image.attr('src');

            image.attr('src', newImage);
            image.attr('rel', oldImage);

            if ($('.mirakl-details').is(':visible'))
                $('.mirakl-details').hide();
            else
                $('.mirakl-details').show();

        });

        $('input[name^="mirakl-price-"]').blur(function () {
            DisplayPrice($(this));
        });


        function comments(text) {
            text.val(text.val().substr(0, 200));
            var left = 200 - parseInt(text.val().length);
            $('#c-count').html(left);
            return (true);
        }

        $('input[name^="mirakl-text-"]').keypress(function () {
            return (comments($(this)));
        });
        $('input[name^="mirakl-text-"]').change(function () {
            return (comments($(this)));
        });

    }
});

