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

setTimeout(function () {
    var pmPageInitialized = false;
    $(document).ready(function () {
        if (pmPageInitialized) return;
        pmPageInitialized = true;

        if (typeof path === 'undefined')
            path = '/';

        var marketplace_options = $('meta[name="priceminister-options"]').attr('content');
        var marketplace_options_json = $('meta[name="priceminister-options-json"]').attr('content');

        // PS 1.4 or 1.5
        if ($('#product').length)
            var product_form = $('#product');
        else
            var product_form = $('.productpm-details input'); //.serialize(); $('#product_form');

        $.ajax({
            type: 'GET',
            url: marketplace_options,
            data: $('form[name=product]').attr('action') + '&rand=' + new Date().valueOf(),
            beforeSend: function (data) {
                $('html').append('<span id="pm-first-loader">Please Wait</span>');
            },
            success: function (data) {
                $('#pm-first-loader').remove();

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
                    $("<hr><div class='bootstrap'><table><tbody>" + data + "</tbody></table></div>").insertAfter('div#product_options');
                }

                ProductOptionInit();
            }
        });

        function ProductOptionInit() {
            $('#productpm-save-options').click(function () {

                // need to set it again, weird....
                if ($('#product').length)
                    var product_form = $('#product');
                else
                    var product_form = $('.productpm-details input'); //.serialize(); $('#product_form');

                $.ajax({
                    type: 'POST',
                    url: marketplace_options_json,
                    data: product_form.serialize() + '&action=set&rand=' + new Date().getTime() + '&context_key=' + $('input[name=context_key]').val() +
                    '&id_lang=' + (typeof(id_language) == 'number' ? id_language : 1) + '&id_product_attribute=0&callback=?',
                    beforeSend: function (data) {
                        $('#result-pm').html('').hide(),
                            $('html').append('<span id="pm-first-loader">Please Wait</span>');
                    },
                    success: function (data) {
                        $('#pm-first-loader').remove(),
                            $('#result-pm').html(data).show()
                    }
                });


            });

            $('#productpm-options').click(function () {
                image = $('#pm-toggle-img');

                newImage = image.attr('rel');
                oldImage = image.attr('src');

                image.attr('src', newImage);
                image.attr('rel', oldImage);

                if ($('.productpm-details').is(':visible'))
                    $('.productpm-details').hide();
                else
                    $('.productpm-details').show();

            });

            $('.pm-propagate-text-cat').click(function () {

                if (!confirm($('#pm-text-propagate-cat').val()))  return (false);

                $.ajax({
                    type: 'POST',
                    url: marketplace_options_json,
                    data: product_form.serialize() + '&context_key=' + $('input[name=pm_context_key]').val() + '&action=propagate-text-cat&path=' + path + '&rand=' + new Date().valueOf() + '&callback=?',
                    beforeSend: function (data) {
                        $('#pm-extra-text-loader').show();
                        $('#result-pm').html('').hide(),
                            $('html').append('<span id="pm-first-loader">Please Wait</span>');
                    },
                    success: function (data) {
                        $('#pm-extra-text-loader').hide();
                        $('#pm-first-loader').remove(),
                            $('#result-pm').html(data).show()
                    }
                });
            });

            $('.pm-propagate-text-shop').click(function () {

                if (!confirm($('#pm-text-propagate-shop').val()))  return (false);

                $.ajax({
                    type: 'POST',
                    url: marketplace_options_json,
                    data: product_form.serialize() + '&context_key=' + $('input[name=pm_context_key]').val() + '&action=propagate-text-shop&path=' + path + '&rand=' + new Date().valueOf() + '&callback=?',
                    beforeSend: function (data) {
                        $('#pm-extra-text-loader').show();
                        $('#result-pm').html('').hide(),
                            $('html').append('<span id="pm-first-loader">Please Wait</span>');
                    },
                    success: function (data) {
                        $('#pm-extra-text-loader').hide();
                        $('#pm-first-loader').remove(),
                            $('#result-pm').html(data).show()
                    }
                });
            });


            $('.pm-propagate-text-manufacturer').click(function () {

                if (!confirm($('#pm-text-propagate-manufacturer').val()))  return (false);

                $.ajax({
                    type: 'POST',
                    url: marketplace_options_json,
                    data: product_form.serialize() + '&context_key=' + $('input[name=pm_context_key]').val() + '&action=propagate-text-manufacturer&path=' + path + '&rand=' + new Date().valueOf() + '&callback=?',
                    beforeSend: function (data) {
                        $('#pm-extra-text-loader').show();
                        $('#result-pm').html('').hide(),
                            $('html').append('<span id="pm-first-loader">Please Wait</span>');
                    },
                    success: function (data) {
                        $('#pm-extra-text-loader').hide();
                        $('#pm-first-loader').remove(),
                            $('#result-pm').html(data).show()
                    }
                });
            });


            // Disabling Products
            $('.pm-propagate-disable-cat').click(function () {

                if (!confirm($('#pm-text-propagate-disable-cat').val()))  return (false);

                $.ajax({
                    type: 'POST',
                    url: marketplace_options_json,
                    data: product_form.serialize() + '&context_key=' + $('input[name=pm_context_key]').val() + '&action=propagate-disable-cat&path=' + path + '&rand=' + new Date().valueOf() + '&callback=?',
                    beforeSend: function (data) {
                        $('#pm-extra-disable-loader').show();
                        $('#result-pm').html('').hide(),
                            $('html').append('<span id="pm-first-loader">Please Wait</span>');
                    },
                    success: function (data) {
                        $('#pm-extra-disable-loader').hide();
                        $('#pm-first-loader').remove(),
                            $('#result-pm').html(data).show()
                    }
                });
            });

            $('.pm-propagate-disable-shop').click(function () {

                if (!confirm($('#pm-text-propagate-disable-shop').val()))  return (false);

                $.ajax({
                    type: 'POST',
                    url: marketplace_options_json,
                    data: product_form.serialize() + '&context_key=' + $('input[name=pm_context_key]').val() + '&action=propagate-disable-shop&path=' + path + '&rand=' + new Date().valueOf() + '&callback=?',
                    beforeSend: function (data) {
                        $('#pm-extra-disable-loader').show();
                        $('#result-pm').html('').hide(),
                            $('html').append('<span id="pm-first-loader">Please Wait</span>');
                    },
                    success: function (data) {
                        $('#pm-extra-disable-loader').hide();
                        $('#pm-first-loader').remove(),
                            $('#result-pm').html(data).show()
                    }
                });
            });

            $('.pm-propagate-disable-manufacturer').click(function () {

                if (!confirm($('#pm-text-propagate-disable-manufacturer').val()))  return (false);

                $.ajax({
                    type: 'POST',
                    url: marketplace_options_json,
                    data: product_form.serialize() + '&context_key=' + $('input[name=pm_context_key]').val() + '&action=propagate-disable-manufacturer&path=' + path + '&rand=' + new Date().valueOf() + '&callback=?',
                    beforeSend: function (data) {
                        $('#pm-extra-disable-loader').show();
                        $('#result-pm').html('').hide(),
                            $('html').append('<span id="pm-first-loader">Please Wait</span>');
                    },
                    success: function (data) {
                        $('#pm-extra-disable-loader').hide();
                        $('#pm-first-loader').remove(),
                            $('#result-pm').html(data).show()
                    }
                });
            });


            // Force Product force
            $('.pm-propagate-force-cat').click(function () {

                if (!confirm($('#pm-text-propagate-force-cat').val()))  return (false);

                $.ajax({
                    type: 'POST',
                    url: marketplace_options_json,
                    data: product_form.serialize() + '&context_key=' + $('input[name=pm_context_key]').val() + '&action=propagate-force-cat&path=' + path + '&rand=' + new Date().valueOf() + '&callback=?',
                    beforeSend: function (data) {
                        $('#pm-extra-force-loader').show();
                        $('#result-pm').html('').hide(),
                            $('html').append('<span id="pm-first-loader">Please Wait</span>');
                    },
                    success: function (data) {
                        $('#pm-extra-force-loader').hide();
                        $('#pm-first-loader').remove(),
                            $('#result-pm').html(data).show()
                    }
                });
            });

            $('.pm-propagate-force-shop').click(function () {

                if (!confirm($('#pm-text-propagate-force-shop').val()))  return (false);

                $.ajax({
                    type: 'POST',
                    url: marketplace_options_json,
                    data: product_form.serialize() + '&context_key=' + $('input[name=pm_context_key]').val() + '&action=propagate-force-shop&path=' + path + '&rand=' + new Date().valueOf() + '&callback=?',
                    beforeSend: function (data) {
                        $('#pm-extra-force-loader').show();
                        $('#result-pm').html('').hide(),
                            $('html').append('<span id="pm-first-loader">Please Wait</span>');
                    },
                    success: function (data) {
                        $('#pm-extra-force-loader').hide();
                        $('#pm-first-loader').remove(),
                            $('#result-pm').html(data).show()
                    }
                });
            });

            $('.pm-propagate-force-manufacturer').click(function () {

                if (!confirm($('#pm-text-propagate-force-manufacturer').val()))  return (false);

                $.ajax({
                    type: 'POST',
                    url: marketplace_options_json,
                    data: product_form.serialize() + '&context_key=' + $('input[name=pm_context_key]').val() + '&action=propagate-force-manufacturer&path=' + path + '&rand=' + new Date().valueOf() + '&callback=?',
                    beforeSend: function (data) {
                        $('#pm-extra-manufacturer-loader').show();
                        $('#result-pm').html('').hide(),
                            $('html').append('<span id="pm-first-loader">Please Wait</span>');
                    },
                    success: function (data) {
                        $('#pm-extra-manufacturer-loader').hide();
                        $('#pm-first-loader').remove(),
                            $('#result-pm').html(data).show()
                    }
                });
            });


            $('input[name^="pm-price-"]').blur(function () {
                DisplayPrice($(this));
            });

            function DisplayPrice(obj) {
                price = obj.val();
                if (price <= 0 || !price) return;
                price = parseFloat(price.replace(',', '.'));
                price = price.toFixed(2);

                obj.val(price);
            }
        }
    });
}, 2000);