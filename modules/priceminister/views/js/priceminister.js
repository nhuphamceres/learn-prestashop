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
    if (pmPageInitialized1)
        return;

    pmPageInitialized1 = true;

    $('#croninfo').show();

    // Information used in jQuery Validate
    var already_has_model = ($('.pm-model').length >= 2) ? true : false;
    var already_has_profile = ($('.pm-model').length >= 2) ? true : false;

    jQuery.validator.addMethod("validateNotZero", function (value, element, param) {
        if (value === '0')
            return (false);
        return (true);
    }, '0');

    jQuery.validator.addMethod("validateShippingMatrix", function (value, element, param) {
        // If no model/profile configured yet, we skip, seller must be able to save a model and a profile first
        if (!already_has_model || !already_has_profile)
            return (true);

        var pm_shippings = $('#conf-shipping').find('[name^="pm_shipping[pm_carriers]"]');
        var ps_shippings = $('#conf-shipping').find('[name^="pm_shipping[ps_carriers]"]');
        var result = false;

        pm_shippings.each(function (ind) {
            var pm_value = $(this).val();
            var ps_value = $(ps_shippings[ind]).val();

            if (typeof(pm_value) !== 'undefined' && typeof(ps_value) !== 'undefined') {
                if (pm_value !== null && pm_value !== '' && ps_value !== null && ps_value !== '') {
                    // if something on both select of the lane, then result = true
                    // and quit loop with return false
                    result = true;
                    return (false);
                }
            }
        });

        return (result);
    }, "");

    jQuery.validator.addMethod("validateCategoriesSelect", function (value, element, param) {
        if (!already_has_model || !already_has_profile)
            return (true);

        var pm_checkboxes = $('#conf-categories').find('[name="category[]"]:checked');

        var result = false;
        if (pm_checkboxes.length > 0)
            result = true;

        return (result);
    }, '');

    $("#configuration_form").validate({
        // Specify the validation rules
        ignore: "",
        rules: {},

        // Specify the validation error messages
        messages: {},
        errorPlacement: function (error, element) {
            var error_div = $('#validation_error');
            if (!error_div.is(':visible'))
                error_div.show();
            var section = element.closest('div.tabItem.panel').find('h3').first().text().trim();
            var entity_name = '';
            if (section == 'Profiles')
                entity_name = 'Profile "' + element.closest('div.pm-profile-body').find('input.profile-profile-name').val() + '" - ';
            else if (section == 'Models')
                entity_name = 'Model "' + element.closest('div.pm-model-body').find('input.model-model-name').val() + '" - ';
            else if (section == 'Categories') {
                if (element.attr('name') != 'categories-select-validate')
                    entity_name = 'Category "' + element.closest('tr').find('label.t').text() + '" - ';
            }

            var span_error_msg = $('<span></span>');
            span_error_msg.html(section + " - " + entity_name + error.text() + '<br>');
            span_error_msg.appendTo(error_div);
            element.rules('remove');
        }
    });


    // AJAX Checker
    $(function () {
        pAjax = new Object();
        pAjax.url = $('#env_check_url').val();
        pAjax.type = 'GET';
        pAjax.data_type = 'jsonp';
        pAjax.data = null;

        if (window.console)
            console.log(pAjax);

        to_display = '.pm-env-infos-' + $('#env_check_url').attr('rel');

        $.ajax({
            type: pAjax.type,
            url: pAjax.url,
            dataType: pAjax.data_type,
            success: function (data) {
                if (window.console)
                    console.log(data);

                if (!data.pass) {
                    $('#pm-env-infos').show();
                    $(to_display).show();
                }
            },
            error: function (data) {
                if (window.console)
                    console.log(data);
                $('#pm-env-infos').show();

                if (data.responseText.length)
                    $('#env-info-details').show().find('span').html(data.responseText).show();

                $(to_display).show();
            }
        });
    });

    // When Document Loaded
    //
    index = 0;

    $('#test').click(function () {
        $('#check-loader').show();

        var login = encodeURIComponent($('#pm_login').val());
        var token = encodeURIComponent($('#pm_token').val());

        $.ajax({
            type: 'POST',
            url: $('#testurl').val() + '&action=check&login=' + login + '&token=' + token,
            beforeProcess: $('#pmresponse').show(),
            success: function (data) {
                $('#pmresponse').html(data);
                $('#check-loader').hide();
            },
            error: function (data) {
                $('#pmresponse').html('AJAX Error');
                $('#check-loader').hide();
            }
        });
    });


    $('input[name=checkme]').click(function () {

        $('input[id^=categoryBox]').each(function () {
            if ($(this).attr('checked'))
                $(this).attr('checked', false);
            else
                $(this).attr('checked', 'checked');
        });

    });

    $('.cat-line').click(function (event) {
        checkbox = $(this).find('input[type=checkbox]');

        if (event.target.type !== 'checkbox')
            $(checkbox, this).trigger('click');
    });

    // Condition/State Mapping
    //
    $('select[id^=condition_map-]').change(function () {
        var selectCondition = $(this).val(),
            thisId = $(this).attr('id');
        $('select[id^=condition_map-]:not(#' + thisId + ')').each(function() {
            if ($(this).val() === selectCondition) {
                $(this).val(0);
            }
        });
    });

    $('input.customer_account_type').change(function() {
        if ($('input.customer_account_type:checked').val() == $('#customer_account_same').val()) {
            $('#set-domain').hide();
        } else {
            $('#set-domain').show();
        }
    });

    $('#support-informations-prestashop, #support-informations-php').click(function () {
        $('.support-informations-loader').show();

        $.ajax({
            type: 'POST',
            url: $(this).attr('rel') + '&callback=?',
            success: function (data) {
                $('.support-informations-loader').hide();
                $('#support-informations-content').html(data).slideDown();
            },
            error: function (data) {
                $('.support-informations-loader').hide();
                $('#support-informations-content').html(data).slideDown();
            }
        });
    });

    //
    // Manufacturer Include/Exclude
    //
    $('#manufacturer-move-right').click(function () {
        return !$('#selected-manufacturers option:selected').remove().appendTo('#available-manufacturers');
    });
    $('#manufacturer-move-left').click(function () {
        return !$('#available-manufacturers option:selected').remove().appendTo('#selected-manufacturers');
    });

    // Suppliers Include/Exclude
    //
    $('#supplier-move-right').click(function () {
        return !$('#selected-suppliers option:selected').remove().appendTo('#available-suppliers');
    });
    $('#supplier-move-left').click(function () {
        return !$('#available-suppliers option:selected').remove().appendTo('#selected-suppliers');
    });

    function DisplayPrice(obj) {
        price = obj.val();
        if (price <= 0 || !price) return;
        price = parseFloat(price.replace(',', '.'));
        if (isNaN(price)) price = 0;
        price = price.toFixed(2);

        obj.val(price);
    }

    $('.shipping-default').blur(function () {
        DisplayPrice($(this));
    });


    $('input[rel="shipping_per_item"]').click(function () {
        if ($(this).is(':checked'))
            $('#shipping_per_item').slideDown();
        else
            $('#shipping_per_item').slideUp();
    });

    $('.shipping-option').click(function () {
        item = $(this).attr('rel');
        if ($(this).is(':checked')) {
            $('#shipping-table-carrier-' + item).slideDown();
            $('#shipping-table-default-' + item).slideDown();
        }
        else {
            $('#shipping-table-carrier-' + item).find('input.editable, select.editable').val('');
            $('#shipping-table-default-' + item).find('input.editable, select.editable').val('');
            $('#shipping-table-carrier-' + item).slideUp();
            $('#shipping-table-default-' + item).slideUp();
        }
    });


    $('.arrow-cat-duplicate').click(function () {
        var current_line = $(this).parents(':eq(1)');
        var current_cat_checkbox = current_line.find('input[type=checkbox]');
        var current_cat_select = current_line.find('select');
        var next_lines = current_line.nextAll();

        if (current_cat_select.val().length)
            current_cat_checkbox.attr('checked', true);
        else
            current_cat_checkbox.attr('checked', false);

        next_lines.each(function () {
            var selector = $(this).find('select');

            if (current_cat_select.val().length) {
                if (selector.val().length && selector.val() != current_cat_select.val())
                    return (false);
                $(this).find('input[type=checkbox]').attr('checked', true);
            }
            else
                $(this).find('input[type=checkbox]').attr('checked', false);

            selector.val(current_cat_select.val());
        });
    });


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

    // show on load
    $('input[name="submit"], button[name="submit"]').show();

    $('#configuration_form').submit(function () {
        $('#available-suppliers option').attr('selected', true);
        $('#selected-suppliers option').attr('selected', true);
        $('#available-manufacturers option').attr('selected', true);
        $('#selected-manufacturers option').attr('selected', true);

        $('#pm-master-profile').remove();

        var selected_tab = $('#menuTab').find('li.selected').attr('id');

        $('#pm-profile-container .pm-profile').each(function (ind) {
            var elements = $(this).find('input, select, textarea');
            $(elements).each(function () {
                var name = $(this).attr('name');
                $(this).attr('name', name.replace('_key_', ind));
            });

            var profile_name_msg = $('#profile-error-profile-name').val();
            var associated_model_msg = $('#profile-error-associated-model').val();
            var from_msg = $('#profile-error-from-value').val();
            var to_msg = $('#profile-error-to-value').val();
            var percent_msg = $('#profile-error-percent-value').val();
            var value_msg = $('#profile-error-amount-value').val();
            var pm_profile = $(this);

            if ($(this).find('[name="pm_profiles[' + ind + '][name]"]').length > 0) {
                $(this).find('[name="pm_profiles[' + ind + '][name]"]').rules('add', {
                    required: true,
                    messages: {required: profile_name_msg}
                });
            }
            if ($(this).find('[name="pm_profiles[' + ind + '][model]"]').length > 0) {
                $(this).find('[name="pm_profiles[' + ind + '][model]"]').rules('add', {
                    required: true,
                    messages: {required: associated_model_msg}
                });
            }

            $(this).find('[name^="pm_profiles[' + ind + '][price_rule][rule][from]"]').each(function (ind1) {
                $(this).attr('name', 'pm_profiles[' + ind + '][price_rule][rule][from][' + ind1 + ']');
                $(this).rules('add', {required: true, messages: {required: from_msg}});
            });

            $(this).find('[name^="pm_profiles[' + ind + '][price_rule][rule][to]"]').each(function (ind1) {
                $(this).attr('name', 'pm_profiles[' + ind + '][price_rule][rule][to][' + ind1 + ']');
                $(this).rules('add', {required: true, messages: {required: to_msg}});
            });

            $(this).find('[name^="pm_profiles[' + ind + '][price_rule][rule][percent]"]').each(function (ind1) {
                $(this).attr('name', 'pm_profiles[' + ind + '][price_rule][rule][percent][' + ind1 + ']');
                $(this).rules('add', {
                    required: {
                        depends: function () {
                            if ($(this).css('display') == 'none')
                                return false;
                            return true;
                        }
                    }, messages: {required: percent_msg}
                });
            });

            $(this).find('[name^="pm_profiles[' + ind + '][price_rule][rule][value]"]').each(function (ind1) {
                $(this).attr('name', 'pm_profiles[' + ind + '][price_rule][rule][value][' + ind1 + ']');
                $(this).rules('add', {
                    required: {
                        depends: function () {
                            if ($(this).css('display') == 'none')
                                return false;
                            return true;
                        }
                    }, messages: {required: value_msg}
                });
            });
        });


        // REPRICING
        max_index_used = 0;
        $('.pm-repricing').each(function () {
            if ($(this).attr('rel').length && !isNaN($(this).attr('rel')) && parseInt($(this).attr('rel')) >= max_index_used)
                max_index_used = parseInt($(this).attr('rel')) + 1;
        });
        $('#pm-repricing-container div.pm-repricing').each(function (ind) {
            var elements = $(this).find('input, select');

            ind += max_index_used;

            $(elements).each(function () {
                var name = $(this).attr('name');
                if (typeof name !== "undefined") {
                    $(this).attr('name', name.replace('_key_', ind));
                }
            });
        });

        // MODEL
        max_index_used = 0;
        $('.pm-model').each(function () {
            if ($(this).attr('rel').length && !isNaN($(this).attr('rel')) && parseInt($(this).attr('rel')) >= max_index_used)
                max_index_used = parseInt($(this).attr('rel')) + 1;
        });
        $('[name^="models[]"]').removeAttr('name');
        $('#pm-model-container div.pm-model').each(function (ind) {
            var elements = $(this).find('input, select, textarea');

            ind += max_index_used;

            $(elements).each(function () {
                var name = $(this).attr('name');
                if (typeof name !== "undefined") {
                    $(this).attr('name', name.replace('_key_', ind));
                }
            });

            var name_msg = $('#model-error-model-name').val();
            var product_type_msg = $('#model-error-product-type').val();
            var platform_opt_msg = $('#model-error-platform-opt').val();
            var platform_attr_msg = $('#model-error-platform-attr').val();
            var typedeproduit_msg = $('#model-error-typedeproduit').val();
            var select_word = $('#model-error-select').val();
            var attribute_word = $('#model-error-attribute').val();
            var attribute_value_word = $('#model-error-attribute-value').val();
            $(this).find('[name="models[' + ind + '][name]"]').rules('add', {
                required: true,
                messages: {required: name_msg}
            });
            $(this).find('[name="models[' + ind + '][product_type]"]').rules('add', {
                required: true,
                messages: {required: product_type_msg}
            });
            $(this).find('select[name^="models[' + ind + ']"]').each(function (ind0) {
                if ($(this).css('display') != 'none' && $(this).parent().is('div.margin-group') && $(this).parent().children('span.pm-required').length > 0) {
                    console.log($(this).attr('name'));
                    $(this).rules('add', {
                        required: true,
                        messages: {required: select_word + " " + ($(this).parent().find('select').index($(this)) == 0 ? attribute_word : attribute_value_word) + " '" + $(this).parent().parent().children('label').text() + "'"}
                    });
                }
            });
        });

        var categories_select_msg = $('#categories-error-selectone').val();
        $('#conf-categories input[id^=category_]').each(function (ind) {
            var _val = $(this).val();
            var profile_select_msg = $('#categories-error-profile').val();
            var cur_elm = $(this);
            $(this).closest('tr').find('[name="profile2category[' + _val + ']"]').rules('add', {
                required: {
                    depends: function () {
                        return cur_elm.is(':checked');
                    }
                }, messages: {required: profile_select_msg}
            });
        });
        $('#conf-categories input[name=categories-select-validate]').rules('add', {
            "validateCategoriesSelect": '',
            messages: {"validateCategoriesSelect": categories_select_msg}
        });

        var pm_orders_status_incoming = $('#conf-orders').find('[name="pm_orders[status_incoming]"]');
        var pm_orders_status_sent = $('#conf-orders').find('[name="pm_orders[status_sent]"]');
        var status_incoming_msg = $('#orders-error-status-incoming').val();
        var status_sent_msg = $('#orders-error-status-sent').val();
        pm_orders_status_incoming.rules('add', {
            "validateNotZero": $('[name="pm_orders[status_incoming]"]').val(),
            messages: {"validateNotZero": status_incoming_msg}
        });
        pm_orders_status_sent.rules('add', {
            "validateNotZero": $('[name="pm_orders[status_sent]"]').val(),
            messages: {"validateNotZero": status_sent_msg}
        });

        //validateShippingMatrix
        var shipping_matrix_msg = $('#shipping-error-matrix').val();
        //$('#conf-shipping div.shipping-matrix').attr('name','shipping-matrix[]');
        $('#conf-shipping input[name=shipping-validate-matrix]').rules('add', {
            "validateShippingMatrix": '',
            messages: {"validateShippingMatrix": shipping_matrix_msg}
        });

        var credentials_login_msg = $('#credentials-error-login').val();
        var credentials_token_msg = $('#credentials-error-token').val();
        $('#conf-credentials input[name="pm_credentials[login]"]').rules('add', {
            required: true,
            messages: {required: credentials_login_msg}
        });
        $('#conf-credentials input[name="pm_credentials[token]"]').rules('add', {
            required: true,
            messages: {required: credentials_token_msg}
        });

        $('#validation_error').hide();
    });

    // chosen
    chosen_param = {
        width: '250px'
    };
    if (typeof($.fn.chosen) === 'function') {
        // remove first element because it is master model
        $('.product_type').slice(1).chosen(chosen_param);
        //$('[name^="mapping"][rel="mapping"]').chosen(chosen_param);
    }
    // cron
    $('.cron-mode').click(function () {
        div_id = $(this).attr('rel');

        if ($('#' + div_id).is(':visible')) {
            $('#' + div_id + '.cron-toggle').slideUp('slow');
            return (false);
        }

        $('.cron-toggle').hide();
        $('#' + div_id + '.cron-toggle').slideDown('slow');
    });

    $('#install-cronjobs').click(function () {

        $('#cronjob-loader').show();

        $.ajax({
            type: 'POST',
            url: $('#cronjobs_url').val(),
            dataType: 'jsonp',
            data: {
                'action': 'install-cron-jobs',
                'prestashop-cronjobs-params': $('#prestashop-cronjobs-params').text()
            },
            success: function (data) {
                $('#cronjob-loader').hide();

                if (window.console)
                    console.log(data);

                if (data.error == true) {
                    $('#cronjobs_success').hide();
                    $('#cronjobs_error').show();
                    $('#cronjobs_error').html(data.output);
                }
                else {
                    $('#cronjobs_success').show();
                    $('#cronjobs_error').hide();
                    $('#cronjobs_success').html(data.output);
                }

            },
            error: function (data) {
                if (window.console)
                    console.log(data);

                $('#cronjob-loader').hide();
                $('#cronjobs_success').hide();
                $('#cronjobs_error').show();
                $('#cronjobs_error').html($('#tools_ajax_error').val());
            }
        });

        return (false);
    });

    $('#detailed_debug_controller').click(function() {
        $(this).toggleClass('dropup').toggleClass('dropdown');
        $('#detailed_debug_content').slideToggle();
    });
});
