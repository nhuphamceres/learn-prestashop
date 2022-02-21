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

var cdiscountInitialized1 = false;
$(document).ready(function () {
    if (cdiscountInitialized1) return;
    cdiscountInitialized1 = true;


    var marketplace_options = $('meta[name="cdiscount-options"]').attr('content');
    var marketplace_options_json = $('meta[name="cdiscount-options-json"]').attr('content');

   // var section = '#cdiscount ';
   var section = '';
    if (!marketplace_options.length) return (false);

    // PS 1.4 or 1.5
    if ($('#product').length) {
        CDiscountMarketplace_Init();
    }
    else {
        $(section + '.marketplace-details').not('.stay-hidden').show();
        setTimeout(CDiscountMarketplace_Init, 2000);
    }

    function CDiscountMarketplace_Init() {
        if (window.console)
            console.log('Init CDiscount');

        $.ajax({
            type: 'GET',
            url: marketplace_options,
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
                CDiscountProductOptionInit();
            }
        });
    }

    function CDiscountProductOptionInit() {
        if (window.console)
            console.log(section + '#productmarketplace-options');
        $(section + '#productmarketplace-save-options').click(function () {
            $.ajax({
                type: 'POST',
                url: marketplace_options_json,
                data: $(section + ' input').serialize() + '&action=set&rand=' + new Date().getTime() + '&context_key=' + $('input[name=context_key]').val() + '&callback=?',
                beforeSend: function (data) {
                    $(section + '#result-marketplace').html('').hide()
                },
                success: function (data) {
                    $(section + '#result-marketplace').html(data).show()
                }
            });


        });

        $('.propagate').click(function ()
        {
            var classes = $(this).attr('class').split(" ");
            var params = classes[0].split("-");
            var action = params[2];
            var scope = params[3];

            if (!confirm($(section + '#marketplace-text-propagate-cat').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: marketplace_options_json,
                data: $(section + ' input').serialize() + '&context_key=' + $('input[name=context_key]').val() + '&action=propagate-' + action + '-' + scope + '&rand=' + new Date().valueOf() + '&callback=?',
                beforeSend: function (data) {
                    $(section + '#marketplace-extra-'+action+'-loader').show();
                    $(section + '#result-marketplace').html('').hide()
                },
                success: function (data) {
                    $(section + '#marketplace-extra-'+action+'-loader').hide();
                    $(section + '#result-marketplace').html(data).show()
                }
            });
        });

        $(section + '#productmarketplace-options').click(function () {

            image = $(section + '#marketplace-toggle-img');

            newImage = image.attr('rel');
            oldImage = image.attr('src');

            image.attr('src', newImage);
            image.attr('rel', oldImage);

            if ($(section + '.marketplace-details').is(':visible'))
                $(section + '.marketplace-details').hide();
            else
                $(section + '.marketplace-details').not('.stay-hidden').show();

        });

        $(section + ' input[name^="marketplace-clogistique-"]').click(function () {
            if ($(this).attr('checked'))
            {
                $(section + '.marketplace-details.clogistique').show();
            }
            else
            {
                $(section + '.marketplace-details.clogistique').hide();
            }
        });

        $(section + ' input[name^="marketplace-valueadded-"]').blur(function () {
            DisplayPrice($(this));
        });
        $(section + ' input[name^="marketplace-price-"]').blur(function () {
            DisplayPrice($(this));
        });

        $(section + ' input[name^="marketplace-align"]').blur(function () {
            DisplayPrice($(this));
        });
        function DisplayPrice(obj) {
            price = obj.val();
            if (price <= 0 || !price) return;

            price = parseFloat(price.replace(',', '.'));
            price = price.toFixed(2);
            if (isNaN(price)) price = '';
            obj.val(price);
        }

        function comments(text) {
            /*
             * Ajout dans le .tpl du tag maxlength="200" qui limite le nombre de caractere (a 200)
             * Donc pas besoin de substr() pour supprimer les caracteres en trop
             */
            var left = 200 - parseInt(text.val().length);
            $(section + '#c-count').html(left);
        }

        /*
         * Si .keypress, il ne compte pas le premier caractere dans le input, qui n'apparait qu'apres que .keypress soit trigger
         * Avec .keyup, le caractere a le temps de se mettre dans le input et etre comptabilise dans la fonction comments
         */
        $(section + 'input[name^="marketplace-text-"]').keyup(function () {
            comments($(this));
        });

    }
});

