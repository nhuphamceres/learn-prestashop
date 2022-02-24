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
 * ...........................................................................
 *
 * @author    Alexandre D. & Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  contact@common-services.com
 */

if (document.readyState != 'loading'){
  FnacEngine();
} else if (document.addEventListener) {
  document.addEventListener('DOMContentLoaded', FnacEngine);
} else {
  document.attachEvent('onreadystatechange', function() {
    if (document.readyState != 'loading')
      FnacEngine();
  });
}

function FnacEngine () {
    var fnac_options = $('meta[name="fnac-options"]').attr('content');
    var fnac_options_json = $('meta[name="fnac-options-json"]').attr('content');

    if (!fnac_options.length) return (false);

    if (window.console)
        console.log("FNAC Marketplace");

    // PS 1.4 or 1.5
    if ($('#product').length) {
        var product_form = $('#product');
        FNAC_Init();
    } else if ($('#product_form').length) {
        var product_form = $('#product_form');
        setTimeout(FNAC_Init, 2000);
    } else if ($('form#form.product-page').length) {
        var product_form = $('form#form.product-page');
        setTimeout(FNAC_Init, 2000);
    } else {
        window.console && console.log('[FNAC] : Error, no product form found...');
        return false;
    }

    function FNAC_Init() {

        var post_data = (product_form.attr('action') + '&rand=' + new Date().valueOf()).split('?')[1];
        if ($('#add_feature_button').length) {
            post_data = {
                id_product: $('#form_id_product').val()
            }
        }

        $.ajax({
            type: 'POST',
            url: fnac_options + '?context_key=' + $('input[name=context_key]').val(),
            data: post_data,
            success: function (data) {
                // PS 1.5
                if ($('#step1 .separation:eq(2)').length) {
                    $('#step1 .separation:eq(2)').parent().after().append(data);
                    $('#step1 .separation:eq(2)').parent().after().append('<div class="separation"></div>');
                }
                // ps 1.7
                else if ($('#add_feature_button').length) {
                    $('#add_feature_button').parent().append('<div class="separation"><br></div>');
                    $('#add_feature_button').parent().append(data);
                }
                // PS < 1.5
                else if ($('#step1 hr:eq(1)').length) {
                    $('#step1 hr:eq(1)').parent().parent().after(data);
                }
                //P.S. 1.6
                else if ($('div#product_options').length) {
                    $("<hr><div class='bootstrap'><table><tbody>" + data + "</tbody></table></div>").insertAfter('div#product_options');
                }
                ProductOptionInit();
            }
        });
    }

    function ProductOptionInit() {
        $('#productfnac-save-options').click(function () {
            $.ajax({
                type: 'POST',
                url: fnac_options_json,
                data: product_form.serialize() + '&context_key=' + $('input[name=context_key]').val() + '&action=set&rand=' + new Date().getTime() + '&callback=?',
                beforeSend: function (data) {
                    $('#result-fnac').html('').hide()
                },
                success: function (data) {
                    $('#result-fnac').append(data).show()
                }
            });


        });


        $('.fnac-propagate-text-cat').click(function () {

            if (!confirm($('#fnac-text-propagate-cat').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: fnac_options_json,
                data: product_form.serialize() + '&context_key=' + $('input[name=context_key]').val() + '&action=propagate-text-cat&rand=' + new Date().valueOf() + '&callback=?',
                beforeSend: function (data) {
                    $('#fnac-extra-text-loader').show();
                    $('#result-fnac').html('').hide()
                },
                success: function (data) {
                    $('#fnac-extra-text-loader').hide();
                    $('#result-fnac').append(data).show();
                }
            });
        });

        $('.fnac-propagate-text-shop').click(function () {

            if (!confirm($('#fnac-text-propagate-shop').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: fnac_options_json,
                data: product_form.serialize() + '&context_key=' + $('input[name=context_key]').val() + '&action=propagate-text-shop&rand=' + new Date().valueOf() + '&callback=?',
                beforeSend: function (data) {
                    $('#fnac-extra-text-loader').show();
                    $('#result-fnac').append('').hide()
                },
                success: function (data) {
                    $('#fnac-extra-text-loader').hide();
                    $('#result-fnac').append(data).show()
                }
            });
        });


        $('.fnac-propagate-text-manufacturer').click(function () {

            if (!confirm($('#fnac-text-propagate-man').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: fnac_options_json,
                data: product_form.serialize() + '&context_key=' + $('input[name=context_key]').val() + '&action=propagate-text-manufacturer&rand=' + new Date().valueOf() + '&callback=?',
                beforeSend: function (data) {
                    $('#fnac-extra-text-loader').show();
                    $('#result-fnac').html('').hide()
                },
                success: function (data) {
                    $('#fnac-extra-text-loader').hide();
                    $('#result-fnac').append(data).show()
                }
            });
        });


        // Disabling Products
        //
        $('.fnac-propagate-disable-cat').click(function () {

            if (!confirm($('#fnac-text-propagate-cat').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: fnac_options_json,
                data: product_form.serialize() + '&context_key=' + $('input[name=context_key]').val() + '&action=propagate-disable-cat&rand=' + new Date().valueOf() + '&callback=?',
                beforeSend: function (data) {
                    $('#fnac-extra-disable-loader').show();
                    $('#result-fnac').html('').hide()
                },
                success: function (data) {
                    $('#fnac-extra-disable-loader').hide();
                    $('#result-fnac').append(data).show()
                }
            });
        });

        $('.fnac-propagate-disable-shop').click(function () {

            if (!confirm($('#fnac-text-propagate-shop').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: fnac_options_json,
                data: product_form.serialize() + '&context_key=' + $('input[name=context_key]').val() + '&action=propagate-disable-shop&rand=' + new Date().valueOf() + '&callback=?',
                beforeSend: function (data) {
                    $('#fnac-extra-disable-loader').show();
                    $('#result-fnac').html('').hide()
                },
                success: function (data) {
                    $('#fnac-extra-disable-loader').hide();
                    $('#result-fnac').append(data).show()
                }
            });
        });

        $('.fnac-propagate-disable-manufacturer').click(function () {

            if (!confirm($('#fnac-text-propagate-man').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: fnac_options_json,
                data: product_form.serialize() + '&context_key=' + $('input[name=context_key]').val() + '&action=propagate-disable-manufacturer&rand=' + new Date().valueOf() + '&callback=?',
                beforeSend: function (data) {
                    $('#fnac-extra-disable-loader').show();
                    $('#result-fnac').html('').hide()
                },
                success: function (data) {
                    $('#fnac-extra-disable-loader').hide();
                    $('#result-fnac').append(data).show()
                }
            });
        });


        // Force Product force
        //
        $('.fnac-propagate-force-cat').click(function () {

            if (!confirm($('#fnac-text-propagate-cat').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: fnac_options_json,
                data: product_form.serialize() + '&context_key=' + $('input[name=context_key]').val() + '&action=propagate-force-cat&rand=' + new Date().valueOf() + '&callback=?',
                beforeSend: function (data) {
                    $('#fnac-extra-force-loader').show();
                    $('#result-fnac').html('').hide()
                },
                success: function (data) {
                    $('#fnac-extra-force-loader').hide();
                    $('#result-fnac').append(data).show()
                }
            });
        });

        $('.fnac-propagate-force-shop').click(function () {

            if (!confirm($('#fnac-text-propagate-shop').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: fnac_options_json,
                data: product_form.serialize() + '&context_key=' + $('input[name=context_key]').val() + '&action=propagate-force-shop&rand=' + new Date().valueOf() + '&callback=?',
                beforeSend: function (data) {
                    $('#fnac-extra-force-loader').show();
                    $('#result-fnac').html('').hide()
                },
                success: function (data) {
                    $('#fnac-extra-force-loader').hide();
                    $('#result-fnac').append(data).show()
                }
            });
        });

        $('.fnac-propagate-force-manufacturer').click(function () {

            if (!confirm($('#fnac-text-propagate-man').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: fnac_options_json,
                data: product_form.serialize() + '&context_key=' + $('input[name=context_key]').val() + '&action=propagate-force-manufacturer&rand=' + new Date().valueOf() + '&callback=?',
                beforeSend: function (data) {
                    $('#fnac-extra-manufacturer-loader').show();
                    $('#result-fnac').html('').hide()
                },
                success: function (data) {
                    $('#fnac-extra-manufacturer-loader').hide();
                    $('#result-fnac').append(data).show()
                }
            });
        });


        $('#productfnac-options').click(function () {
            image = $('#fnac-toggle-img');

            newImage = image.attr('rel');
            oldImage = image.attr('src');

            image.attr('src', newImage);
            image.attr('rel', oldImage);

            if ($('.fnac-details').is(':visible'))
                $('.fnac-details').hide();
            else
                $('.fnac-details').show();

        });

        $('input[name^="fnac-price-"]').blur(function () {
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
};

