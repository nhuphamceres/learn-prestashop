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

$(document).ready(function () {
    var context = $('#pm-product-tab');
    var id_product = parseInt($('#id-product').val());
    var id_product_attribute = 0;
    var complex_id_product = null;

    $('.pm-product-options', context).not('.main').find('.propagation').hide();


    if (sessionStorage == null) // browser doesn't support sessionStorage, we hide copy/paste functions
    {
        $('.table.pm-item .copy-product-option').parent().hide();
        $('.table.pm-item .paste-product-option').parent().hide();
    }

    /*
     * Infinite loop on PS1.7
     * TODO FIX
     */
    function getComplexId() {
        if ($('input[name=complex_id_product]:checked', context) && $('input[name=complex_id_product]:checked', context).val() && $('input[name=complex_id_product]:checked', context).val().length)
            complex_id_product = $('input[name=complex_id_product]:checked', context).val();
        else {
            $('input[name=complex_id_product]:first', context).attr('checked', true).parent().parent().trigger('click');
            complex_id_product = id_product + '_0';
        }

        id_product = $('input[name=complex_id_product]:checked', context).attr('data-id-product');
        id_product_attribute = $('input[name=complex_id_product]:checked', context).attr('data-id-product-attribute');

        if (window.console)
            console.log('getComplexId - RakutenFrance', complex_id_product, id_product, id_product_attribute);

        return (complex_id_product);
    }

    getComplexId(); // onload

    /**
     * Implementation of $.fn.prop() for older version of jQuery.
     * So there is no error in the code below with :
     * $(this).find('input[type=radio]', context).attr('checked', true).prop('checked', true).change();
     */
    if ('function' !== typeof($.fn.prop)) {

        jQuery.fn.extend({
            prop: function () {
                return this;
            }
        });

    }

    $('#element').attr('checked') // returns “checked” (string)
    $('#element').prop('checked') // returns true (Boolean)


    /*
     * Products & combinations lines
     */
    $('.table.pm-item tbody tr', context).click(function (e) {
        if (e.target.type == 'checkbox')
            return false;

        if (window.console)
            console.log('Item Selector', complex_id_product, id_product, id_product_attribute);

        $('.table.pm-item tbody tr', context).find('input[type=radio]').attr('checked', false);
        $('.table.pm-item tbody tr', context).removeClass('highlighted');

        var target_complex_id = $(this).attr('rel');

        $(this).addClass('highlighted');
        $(this).find('input[type=radio]', context).attr('checked', true).prop('checked', true).change();

        var target_tab = $('#pm-product-tab .marketplace-subtab[data-complex-id="' + target_complex_id + '"]');

        TabSelector(target_tab);
    });

    /*
     * Tab Selector
     */
    function TabSelector(target_tab) {
        if (!$(this).hasClass('active')) {
            if (window.console)
                console.log('Tab Selector', target_tab);

            $('div.marketplace-subtab', context).removeClass('active').hide();
            $(target_tab, context).addClass('active').fadeIn('slow');

            getComplexId();
        }
    }


    /*
     * Edit functions: copy, paste, delete
     */
    $('.table.pm-item .copy-product-option', context).click(function (ev) {
        var current_tab = $('.marketplace-tab:visible');
        var current_marketplace = $('input[name=context]', current_tab).val();

        var inputs = $(':input[name]:not([type=hidden]), :input[rel]:not([type=hidden])', current_tab);
        var input_values = inputs.serializeArray();

        sessionStorage['pm-copy' + current_marketplace] = JSON.stringify(input_values);

        if (window.console)
            console.log('Copy buffer for' + current_marketplace, input_values);

        showSuccessMessage($('#pm-product-options-copy').val());

        return (false);
    });

    $('.table.pm-item .paste-product-option', context).click(function (ev) {
        var current_tab = $('.marketplace-tab:visible');
        var current_marketplace = $('input[name=context]', current_tab).val();

        var paste_buffer = sessionStorage['pm-copy' + current_marketplace];

        if (window.console)
            console.log('Paste buffer for' + current_marketplace, paste_buffer);

        if (paste_buffer != null) {
            var paste_items = JSON.parse(paste_buffer);

            if (window.console)
                console.log(paste_items);

            if (paste_items) {
                $(':input[name][type=checkbox]', current_tab).attr('checked', false);
                $(':input[name][type=radio]', current_tab).attr('checked', false);
                $(':input[name][type=text]', current_tab).val(null);

                $.each(paste_items, function (i, item) {
                    var target_input = $('input[name="' + item.name + '"]', current_tab);

                    console.log('Paste:', item);

                    if ($(target_input).attr('type') == 'text') {
                        $(target_input).val(item.value);
                        if (!$(target_input).parent().is(':visible') && item.value.length) // for bullet points
                            $(target_input).parent().show();
                    }
                    else if ($(target_input).attr('type') == 'checkbox' || $(target_input).attr('type') == 'radio') {
                        $('input[name="' + item.name + '"][value="' + item.value + '"]', current_tab).attr('checked', true);
                    }

                });
                showSuccessMessage($('#pm-product-options-paste').val());

                $('input[name]:visible:first', current_tab).trigger('change');//triggers ajax post
            }

        }
        return (false);
    });

    /*
     * Delete
     */

    $('.table.pm-item tbody tr td .delete-product-option', context).click(function (e) {
        var current_tab = $('.marketplace-subtab:visible');
        var current_marketplace = $('input[name=context]', current_tab).val();

        if (current_marketplace != 'jet')
            return;

        if (window.console)
            console.log('Delete Product Option - Jet.com', current_tab, current_marketplace);

        var id_product_attribute = parseInt($('input[name=id_product_attribute]', current_tab).val());
        var id_lang = $(':input[name=id_lang]', current_tab).val();
        var region = $(current_tab).attr('data-iso-code');

        if (window.console)
            console.log('Data', id_lang, id_product_attribute, region);

        if (id_product_attribute) // this is a combination, we delete only the subtab
            var target_tab = current_tab;
        else // this is the main product, we delete the product option and options for combinations
            var target_tab = $('.pm-product-options[data-iso-code="' + region + '"]', context);

        if (window.console)
            console.log('target_tab', target_tab);

        $(':input[name][type=checkbox]', target_tab).attr('checked', false);
        $(':input[name][type=radio]', target_tab).attr('checked', false);
        $(':input[name][type=text]', target_tab).val(null);

        jetAjaxAction('delete-jet', current_tab);

        return (false);
    });


    $('.pm-product-options', context).delegate('input', 'change', function (ev) {

        var target_subtab = ev.delegateTarget;

        jetAjaxAction('set', target_subtab);
    });

    function jetAjaxAction(action, target_tab) {
        var global_values = $('input[name]', $('#pm-global-values')).serialize();

        $('#pm-product-tab .debug').html('');

        $.ajax({
            type: 'POST',
            dataType: 'jsonp',
            url: $('#pm-product-options-json-url').val() + '&action=' + action + '&seed=' + new Date().getTime() + '&callback=?',
            data: global_values + '&' + $('input[name], select[name]', target_tab).serialize(),
            success: function (data) {

                if (data.output && data.output.length)
                    $('#pm-product-tab .debug').append('<pre>Response:' + data.output + '</pre>');

                if (data.error)
                    showErrorMessage($('#pm-product-options-message-error').val());
                else
                    showSuccessMessage($('#pm-product-options-message-success').val());
            },
            error: function (data) {
                if (window.console)
                    console.log('Error', data);

                showErrorMessage('Error');

                if (data.status && data.status.length)
                    $('#pm-product-tab .debug').append('<pre>Status Code:' + data.status + '</pre>');
                if (data.statusText && data.statusText.length)
                    $('#pm-product-tab .debug').append('<pre>Status Text:' + data.statusText + '</pre>');
                if (data.responseText && data.responseText.length)
                    $('#pm-product-tab .debug').append('<pre>Response:' + data.responseText + '</pre>');
            }
        });
    }

    /*
     * SKU/EAN/UPC Editor
     */
    $('.table.pm-item', context).delegate('.pm-editable', 'click', function (ev) {
        var target_text = $(this).text().trim();
        var target_field = $(this).attr('rel');
        var target_cell = $(this);

        var complex_id_product = getComplexId();

        var global_values = $('input[name]', $('#pm-global-values')).serialize();

        if (!$(':input', target_cell) || !$(':input', target_cell).length) {
            target_cell.html('<input type="text" value="">');

            $(':input', target_cell).val(target_text).focus();
            target_cell.attr('data-initial', target_text);

            $(':input', target_cell).blur(function (ev) {
                var target_cell = $(this).parent();
                var updated_value = $(this).val().trim();
                var pass = true;

                if (target_cell.attr('data-initial') == updated_value)
                    pass = false;

                $(this).parent().text(updated_value);

                if (pass) {
                    pAjax = new Object();
                    pAjax.url = $('#pm-product-options-json-url').val() + '&seed=' + new Date().getTime() + '&callback=?';
                    pAjax.type = 'POST';
                    pAjax.data_type = 'jsonp';

                    var params = {
                        'action': 'update-field',
                        'id_product': id_product,
                        'id_product_attribute': id_product_attribute,
                        'field': target_field,
                        'value': updated_value
                    };

                    $.ajax({
                        success: function (data) {
                            if (window.console)
                                console.log(data);

                            if (data.error) {
                                target_cell.html(target_cell.attr('data-initial'));
                                showErrorMessage($('#pm-product-options-message-error').val());
                            }
                            else
                                showSuccessMessage($('#pm-product-options-message-success').val());

                            if (data.output)
                                $('#pm-product-tab .debug').html(data.output);
                        },
                        type: pAjax.type,
                        url: pAjax.url,
                        dataType: pAjax.data_type,
                        data: $.param(params) + '&' + global_values,
                        error: function (data) {
                            if (window.console)
                                console.log('ERROR', data);
                            target_cell.html(target_cell.attr('data-initial'));
                            showErrorMessage($('#pm-product-options-message-error').val());

                            if (data.status && data.status.length)
                                $('#pm-product-tab .debug').append('<pre>Status Code:' + data.status + '</pre>');
                            if (data.statusText && data.statusText.length)
                                $('#pm-product-tab .debug').append('<pre>Status Text:' + data.statusText + '</pre>');
                            if (data.responseText && data.responseText.length)
                                $('#pm-product-tab .debug').append('<pre>Response:' + data.responseText + '</pre>');
                        }
                    });
                }
            });
        }
    });

    function DisplayPrice(obj) {
        price = obj.val();
        if (price <= 0 || !price) return;

        price = parseFloat(price.replace(',', '.'));
        price = price.toFixed(2);
        if (isNaN(price)) price = '';
        obj.val(price);
    }

    $('.pm-product-options', context).delegate('.propagate', 'click', function (ev) {
        var target_tab = ev.delegateTarget;
        var classes = $(this).attr('class').split(" ");
        var params = classes[0].split("-");
        var field = params[2];
        var scope = params[3];

        console.log(classes, params);


        $('#pm-product-tab .debug').html('');

        var global_values = $('input[name]', $('#pm-global-values')).serialize();

        if (window.console)
            console.log(target_tab, field, scope);

        if (!confirm($('.marketplace-text-propagate-cat', context).val()))  return (false);

        $.ajax({
            type: 'POST',
            dataType: 'jsonp',
            url: $('#pm-product-options-json-url').val() + '&action=propagate&field=' + field + '&scope=' + scope + '&entity=jet&seed=' + new Date().getTime() + '&callback=?',
            data: global_values + '&' + $('input[name], select[name]', target_tab).serialize(),
            success: function (data) {
                if (window.console)
                    console.log(data);

                if (data.output && data.output.length)
                    $('#pm-product-tab .debug').append('<pre>Response:' + data.output + '</pre>');

                if (data.error)
                    showErrorMessage($('#pm-product-options-message-error').val());
                else
                    showSuccessMessage($('#pm-product-options-message-success').val());
            },
            error: function (data) {
                if (window.console)
                    console.log('Error', data);

                showErrorMessage('Error');

                if (data.status && data.status.length)
                    $('#pm-product-tab .debug').append('<pre>Status Code:' + data.status + '</pre>');
                if (data.statusText && data.statusText.length)
                    $('#pm-product-tab .debug').append('<pre>Status Text:' + data.statusText + '</pre>');
                if (data.responseText && data.responseText.length)
                    $('#pm-product-tab .debug').append('<pre>Response:' + data.responseText + '</pre>');
            }
        });
    });

});

