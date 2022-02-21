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

    if ('function' !== typeof($.fn.prop)) {
        jQuery.fn.extend({
            prop: function() {
                return this;
            }
        });
    }
     /* Glossary */

     $('#cdiscount-product-tab .column-left[rel]').each(function () {

        var target_glossary_key = $(this).attr('rel');
        var target_glossary_div = $('#cdiscount-product-tab div.glossary[rel='+target_glossary_key+']');

        if (target_glossary_div && target_glossary_div.length)
        {
            if ($('span', this) && $('span', this))
                var title = $('span', this).text();
            else
                var title = null;

            $(this).qtip({

                content: {
                    text: target_glossary_div.html(),
                    title: title
                },
                hide: {
                    fixed: true,
                    delay: 300
                }
            });
            $(this).addClass('tip');
        }
    });
    
     var section = '#cdiscount ';
     var marketplace_options_json = $('meta[name="cdiscount-options-json"]').attr('content');
    
     CDiscountProductOptionInit();
    
    function CDiscountProductOptionInit() {
        if (window.console)
            console.log(section + ' #product marketplace-options');

        /* Prestashop 1.7 - put logo into card-header*/
        module_div = $('#module_cdiscount');
        module_div.parent().parent().css('margin-top', '10px');
        if (module_div.length) {
            $('.card-header', module_div.parent()).prepend( $('h3 > img', module_div) ) ;
        }

       $('#cdiscount').delegate('input', 'change', function (ev)
        {
           
    
                $.ajax({
                    type: 'POST',
                    url: marketplace_options_json,
                   data: $(section + ' input').serialize() + '&action=set&rand=' + new Date().getTime() + '&context_key=' + $('input[name=context_key]').val() + '&callback=?',
                    success: function (data)
                    {
     
                        if (data.error)
                            showErrorMessage( $('#cdiscount-product-options-message-error').val() );
                        else
                            showSuccessMessage( $('#cdiscount-product-options-message-success').val() );
                    },
                    error: function (data) {
                        if (window.console)
                            console.log('Error', data);
    
                        showErrorMessage( 'Error' );
    
                        if (data.status && data.status.length)
                            $('#cdiscount-product-tab .debug').append('<pre>Status Code:'+data.status+'</pre>');
                        if (data.statusText && data.statusText.length)
                            $('#cdiscount-product-tab .debug').append('<pre>Status Text:'+data.statusText+'</pre>');
                        if (data.responseText && data.responseText.length)
                            $('#cdiscount-product-tab .debug').append('<pre>Response:'+data.responseText+'</pre>');
                    }
                });
           
    
        }); 
        
      

        $('#cdiscount-product-tab .propagate').click(function ()
        {
            if (window.console)
                console.log(section + marketplace_options_json);

            var classes = $(this).attr('class').split(" ");
            var params = classes[0].split("-");
            var param = params[2];
            var scope = params[3];

            if (!confirm($(section + '#marketplace-text-propagate-cat').val()))  return (false);

            $.ajax({
                type: 'POST',
                url: marketplace_options_json,
               data: $(section + ' input').serialize() + '&action=propagate&scope=' + scope + '&param=' + param + '&rand=' + new Date().getTime() + '&context_key=' + $('input[name=context_key]').val() + '&callback=?',
                success: function (data)
                {
 
                    if (data.error)
                        showErrorMessage( $('#cdiscount-product-options-message-error').val() );
                    else
                        showSuccessMessage( $('#cdiscount-product-options-message-success').val() );
                },
                error: function (data) {
                    if (window.console)
                        console.log('Error', data);

                    showErrorMessage( 'Error' );

                    if (data.status && data.status.length)
                        $('#cdiscount-product-tab .debug').append('<pre>Status Code:'+data.status+'</pre>');
                    if (data.statusText && data.statusText.length)
                        $('#cdiscount-product-tab .debug').append('<pre>Status Text:'+data.statusText+'</pre>');
                    if (data.responseText && data.responseText.length)
                        $('#cdiscount-product-tab .debug').append('<pre>Response:'+data.responseText+'</pre>');
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

