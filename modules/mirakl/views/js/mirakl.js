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
$(document).ready(function () {
    var idShop = $('[name="id_shop"]').val(),
        idShopGroup = $('[name="id_shop_group"]').val();

    if (pageInitialized1) return;
    pageInitialized1 = true;

    // Marketplace selector
    var previous_selected_mkp;
    $('#selected-mkp').on('focus', function () {
        previous_selected_mkp = $(this).val();
    }).change(function () {
        var msg = $('#before-changing-mkp-1').val() + '\n\n' + $('#before-changing-mkp-2').val();

        console.log(previous_selected_mkp);

        confirm(msg) && $(this).parent().find('img').show() && $(this).parent().find('[type="submit"]').click() ||
        $(this).find('option[value="' + previous_selected_mkp + '"]').attr('selected', true);

        previous_selected_mkp = $(this).val();
    });

    $('.hint').show();

    if ('function' !== typeof($.fn.prop)) {
        jQuery.fn.extend({
            prop: function() {
                return this;
            }
        });
    }

    $('#support-informations-prestashop, #support-informations-php').click(function () {
        $('.support-informations-loader').show();

        $.ajax({
            type: 'POST',
            url: $(this).attr('rel') + '&callback=?',

            data: {
                'fields': $('input, select, textarea, button').length
            },

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


    $('.cron-mode').click(function () {
        var div_id = $(this).attr('rel');

        if ($('#' + div_id).is(':visible')) {
            $('#' + div_id + '.cron-toggle').slideUp('slow');
            return (false);
        }

        $('.cron-toggle').hide();
        $('#' + div_id + '.cron-toggle').slideDown('slow');
    });

    $('#install-cronjobs').click(function () {
        var $loader     = $('#cronjob-loader'),
            $success    = $('#cronjobs_success'),
            $error      = $('#cronjobs_error'),
            data = mergeContext({
                'action': 'install-cron-jobs',
                'prestashop-cronjobs-params': $('#prestashop-cronjobs-params').text()
            });

        $.ajax({
            type: 'POST',
            url: $('#mirakl_tools_url').val(),
            dataType: 'jsonp',
            data: data,
            beforeSend: function() {
                $loader.show();
                $success.hide();
                $error.hide();
            },
            success: function (data) {
                if (window.console)
                    console.log(data);

                if (data.error) {
                    $error.html(data.output).show();
                } else {
                    $success.html(data.output).show();
                }
            },
            error: function (data) {
                if (window.console)
                    console.log(data);
                $error.html($('#tools_ajax_error').val()).show();
            },
            complete: function() {
                $loader.hide();
            }
        });
        return (false);
    });


    // Tab active or not active
    //
    $('div[id^=menudiv-]').each(function () {

        if ($(this).find('input[name^="actives"]').length != 0 && !parseInt($(this).find('input[name^="actives"]:checked').val())) {
            tabInactive($(this));
        }
        else if ($(this).find('input[name^="actives"]').length != 0) {
            tabActive($(this));
        }
    });

    $('input[id^="active-"]').click(function () {
        var result = $(this).attr('id').match('^(.*)-(.*)$');
        var lang = result[2];
        var currentTab = $('#menudiv-' + lang);

        if (!parseInt(currentTab.find('input[name^="actives"]:checked').val()))
            tabInactive(currentTab);
        else
            tabActive(currentTab);

    });
    function tabActive(tab) {
        $(tab).find('input, select, textarea').each(function () {
            if ($(this).attr('type') == 'checkbox')
                return (true);
            if ($(this).attr('name') == 'submit')
                return (true);
            $(this).attr('readonly', false).attr('disabled', false).removeClass('disabled');
        });
    }

    function tabInactive(tab) {
        $(tab).find('input, select, textarea').each(function () {
            if ($(this).attr('type') == 'checkbox')
                return (true);
            if ($(this).attr('name') == 'submit')
                return (true);
            $(this).attr('readonly', 'readonly').attr('disabled', 'disabled').addClass('disabled');
        });
    }

    $('input[name=checkme]').click(function () {
        var state = Boolean($(this).attr('checked'));
        $('input[id^=category]').attr('checked', state);
    });

    /**
     * Authentication check
     */
    $('#connection-check').click(function () {
        var loader = $('#connection-check-loader'),
            api_key = $('input[name=mirakl_api_key]').val();

        $.ajax({
            type: 'GET',
            url: $('#check_url').val() + '&context_key=' + $('input[name=mirakl_context_key]').val() + '&callback=?',
            dataType: 'json',
            data: {
                'id_lang': $('#id_lang').val(),
                'action': 'check',
                'preprod': ($('#preproduction').is(':checked') ? '1' : '0'),
                'debug': ($('#debug').is(':checked') ? '1' : '0'),
                'api_key': api_key,
                'selected-mkp': $('#selected-mkp').val(),
                'id_shop': idShop,
                'id_shop_group': idShopGroup
            },
            beforeSend: function() {
                loader.show();
            },
            success: function (data) {
                $('#mirakl-response div').html('').hide();

                if (window.console)
                    console.log(data);

                if (data.alert) {
                    alert(data.alert);
                    return (false);
                }

                if (data.message && !data.error) {
                    $('#mirakl-response .yes').html(data.message).show();
                }
                else if (data.message && data.error) {
                    $('#mirakl-response .no').html(data.message).show();
                }
                if ($('#debug').is(':checked') && data.debug) {
                    $('#mirakl-response').after('<hr /><pre>' + data.debug + '</pre>');
                }
            },
            error: function (data) {
                window.console && console.log(data);
                $('#mirakl-response .no').html('Connection Error').show().append('<br>' + data.responseText);
            },
            complete: function() {
                loader.hide();
            }
        });

    });

    function comments() {
        $('#comments').val($('#comments').val().substr(0, 200));
        var left = 200 - parseInt($('#comments').val().length);
        $('#c-count').html(left);
        return (true);
    }

    $('#comments').keypress(function () {
        comments();
    });
    $('#comments').change(function () {
        comments();
    });

    function DisplayPrice(obj) {
        var price = obj.val();
        if (price <= 0 || !price)
            return;
        price = parseFloat(price.replace(',', '.'));

        if (isNaN(price))
            price = 0;

        price = price.toFixed(2);

        obj.val(price);
    }

    $('.price').blur(function () {
        DisplayPrice($(this));
    });


    $('input[name="validateForm"]').click(function () {
        if ($('select[name="orderstate[MIRAKL_CA]"] :selected').index() == 0) {
            alert($('select[name="orderstate[MIRAKL_CA]"] option:eq(0)').val() + ' !');
            return (false);
        }
        if ($('select[name="orderstate[MIRAKL_CE]"] :selected').index() == 0) {
            alert($('select[name="orderstate[MIRAKL_CE]"] option:eq(0)').val() + ' !');
            return (false);
        }
        if ($('select[name="orderstate[MIRAKL_CL]"] :selected').index() == 0) {
            alert($('select[name="orderstate[MIRAKL_CL]"] option:eq(0)').val() + ' !');
            return (false);
        }
    });

    /*
     *  PROFILES MANAGEMENT
     */
    $('#profile-add').click(function () {
        var cloned = $('#master-profile').clone().prependTo('#profile-container').slideDown('slow');

        cloned.find('.mirakl_date').each(function (i, v) {
            mirakl_set_datepicker(v);
        });

        cloned.removeAttr('id');
        cloned.find('.profile-del-2').click(function () {
            $(this).parent().slideUp('slow', function () {
                $(this).remove();
            });
        });

        cloned.find('.price-rule-add').click(function () {
            var source_i = $(this).parent();
            var dest_i = $(this).parent().parent();
            var is_first_rule = $(dest_i).find('.price-rule').length;

            var from_val = Number(dest_i.find('input[rel=from]:last').val());
            var to_val = Number(dest_i.find('input[rel=to]:last').val());

            dest_i.find('input[rel=from]:last,input[rel=to]:last').removeClass('required');

            if (!dest_i.find('input[rel=from]:last').val().length || (is_first_rule !== 1 && !parseInt(from_val)))
                from_val = null;

            if (!dest_i.find('input[rel=to]:last').val().length || !parseInt(to_val))
                to_val = null;

            if (parseInt(from_val) && parseInt(to_val) && from_val > to_val && from_val >= (parseInt(source_i.find('input[rel=from]').val()) + 1)) {
                dest_i.find('input[rel=to]:last').val('');
                to_val = null;
            }
            else if (parseInt(from_val) && parseInt(to_val) && from_val > to_val) {
                dest_i.find('input[rel=from]:last').val('');
                from_val = null;
            }

            if (!to_val || (is_first_rule !== 1 && !from_val)) {
                if (from_val === null)
                    dest_i.find('input[rel=from]:last').addClass('required');
                if (to_val === null)
                    dest_i.find('input[rel=to]:last').addClass('required');
                return (false);
            }
            var cloned2 = source_i.clone().appendTo(dest_i);
            cloned2.find('input').val('');
            cloned2.find('input[rel=from]').val(parseInt(to_val + 1));

            cloned2.find('.price-rule-add, .price-rule-remove').toggle();
            cloned2.find('.price-rule-remove').
                click(function () {
                    $(this).parent().remove();
                });
        });
        cloned.find('.price-rule-remove').click(function () {
            $(this).parent().remove();
        });

        //cloned.find('.profile_category').change(function() {
        //    loadSubCategories($(this));
        //});
        //cloned.find('.profile_sub_category').change(function() {
        //    loadCategoryAttributes($(this));
        //});
    });

    /*
     * General Price Rules
     */
    $('.price-rule-add').click(function () {
        var source_i = $(this).parent();

        var dest_i = $(this).parent().parent();
        var is_first_rule = $(dest_i).find('.price-rule').length;

        var from_val = Number(dest_i.find('input[rel=from]:last').val());
        var to_val = Number(dest_i.find('input[rel=to]:last').val());

        dest_i.find('input[rel=from]:last, input[rel=to]:last').removeClass('required');

        if (!dest_i.find('input[rel=from]:last').val().length || (is_first_rule !== 1 && !parseInt(from_val)))
            from_val = null;

        if (!dest_i.find('input[rel=to]:last').val().length || !parseInt(to_val))
            to_val = null;

        if (parseInt(from_val) && parseInt(to_val) && from_val > to_val && from_val >= (parseInt(source_i.find('input[rel=from]').val()) + 1)) {
            dest_i.find('input[rel=to]:last').val('');
            to_val = null;
        }
        else if (parseInt(from_val) && parseInt(to_val) && from_val > to_val) {
            dest_i.find('input[rel=from]:last').val('');
            from_val = null;
        }

        if (!to_val || (is_first_rule !== 1 && !from_val)) {
            if (from_val === null)
                dest_i.find('input[rel=from]:last').addClass('required');
            if (to_val === null)
                dest_i.find('input[rel=to]:last').addClass('required');

            return (false);
        }

        var cloned = source_i.clone().appendTo(dest_i);
        cloned.find('input').val('');
        cloned.find('input[rel=from]').val(parseInt(to_val + 1));

        cloned.find('.price-rule-add, .price-rule-remove').toggle();
        cloned.find('.price-rule-remove').click(function () {
            $(this).parent().remove();
        });
    });

    // Fix Price Rule which doesnt change the select from percentage to value
    $('#conf-profiles').delegate('.price-rule-type', 'change', function() {
        $(this).parent().find('[rel="value"], [rel="percent"]').toggle();
    });
    
    $('.price-rule-remove').click(function () {
        $(this).parent().remove();
    });

    $('.profile-edit-img').click(function () {
        var profile_id = $(this).attr('rel');
        $('#profile-' + profile_id).slideToggle('slow');
    });

    $('.profile-del-2').click(function () {
        var profile_id = $(this).attr('rel');
        $('#profile-' + profile_id).slideUp('slow', function () {
            $(this).remove();
        });
        $('#profile-header-' + profile_id).slideUp('slow', function () {
            $(this).remove();
        });
    });
    $('.profile-del-img').click(function () {
        var profile_id = $(this).attr('rel');
        $('#profile-header-' + profile_id).remove();
        $('#profile-' + profile_id).slideUp('slow', function () {
            $(this).remove();
        });
        $('#profile-header-' + profile_id).slideUp('slow', function () {
            $(this).remove();
        });
    });

    /* Click on gray line open profile edition */
    $('.profile-table tbody tr td:nth-child(1)').click(function () {
        $(this).parent().find('.profile-edit-img').click();
    });

    $('#reset-categories').click(function () {
        if (confirm($('#reset-categories-alert').val()))
            return (true);
        else
            return (false);
    });

    // http://stackoverflow.com/questions/6565480/javascript-jquery-regex-replace-input-field-with-valid-characters
    $('.typerightname').keyup(function () {
        var input = $(this),
            text = input.val().replace(/[^a-zA-Z0-9-_\s]/g, "");
        if (/_|\s/.test(text)) {
            text = text.replace(/_|\s/g, "");
            // logic to notify user of replacement
        }
        input.val(text);
    });

    //
    // Manufacturer Include/Exclude
    //
    $('#manufacturer-move-right').click(function () {
        return !$('#excluded-manufacturers option:selected').remove().appendTo('#available-manufacturers');
    });
    $('#manufacturer-move-left').click(function () {
        return !$('#available-manufacturers option:selected').remove().appendTo('#excluded-manufacturers');
    });

    $('input[name="submit"]').click(function () {
        $('#available-manufacturers option').attr('selected', true);
        $('#excluded-manufacturers option').attr('selected', true);
    });

    //
    // Suppliers Include/Exclude
    //
    $('#supplier-move-right').click(function () {
        return !$('#selected-suppliers option:selected').remove().appendTo('#available-suppliers');
    });
    $('#supplier-move-left').click(function () {
        return !$('#available-suppliers option:selected').remove().appendTo('#selected-suppliers');
    });

    $('.pmhint').show();

    $('form').submit(function () {
        $('#available-suppliers option').attr('selected', true);
        $('#selected-suppliers option').attr('selected', true);
        $('#available-manufacturers option').attr('selected', true);
        $('#excluded-manufacturers option').attr('selected', true);
        $('div.profile-create select:disabled').attr('disabled', false);

        var last = 0;
        $('#conf-profiles').find('.profile').slice(0, -1).each(function() {
            if (parseInt($(this).attr('rel')) > last) {
                last = parseInt($(this).attr('rel'));
            }
        });

        /* Profile optimization */
        $('.profile-create').slice(0, -1).each(function (ind) {
            var elements = $(this).find('input, select, textarea');

            $(elements).each(function () {
                var name = $(this).attr('name');
                $(this).attr('name', name.replace('_key_', last+ind+1));
            });
        });

        return (true);
    });


    $('.arrow-cat-duplicate', $('#content .tabItem')).click(function () {
        var current_line = $(this).parents().get(1);
        var current_cat_checkbox = $(current_line).find('input[type=checkbox]');
        var current_cat_select = $(current_line).find('select');
        var next_lines = $(current_line).nextAll();

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
			{
                $(this).find('input[type=checkbox]').attr('checked', false);
			}
            selector.val(current_cat_select.val());
        });
    });

    $('.cat-line td:nth-child(2)', $('#conf-categories')).click(function (ev) {
        target_line = $(this).parent();
        $(':input', target_line).trigger('click');
    });

    $('.cat-line select', $('#conf-categories')).change(function (ev) {

        if($(this).val() && $(this).val().length) {
            var target_line = $(this).parents().get(1);
            var status = $(':input', target_line).is(':checked')
            console.log(status);
            if (status == false) {
                $(':input', target_line).attr('checked', true).prop('checked', true);
            }
        }
    });

    // Multiple selection for checkboxes
    (function ($) {
        $.fn.enableCheckboxRangeSelection = function () {
            var lastCheckbox = null;
            var $spec = this;
            $spec.unbind("click.checkboxrange");
            $spec.bind("click.checkboxrange", function (e) {
                if (lastCheckbox !== null && (e.shiftKey || e.metaKey)) {
                    var is_checked = e.target.checked ? true : false;
                    $spec.slice(
                        Math.min($spec.index(lastCheckbox), $spec.index(e.target)),
                        Math.max($spec.index(lastCheckbox), $spec.index(e.target)) + 1
                    ).attr('checked', is_checked).prop('checked', is_checked);
                }
                lastCheckbox = e.target;
            });
        };
    })(jQuery);

    $('.category', $('#conf-categories')).enableCheckboxRangeSelection();

    /**
     * Merge context data to input
     * @param input
     * @returns {*}
     */
    function mergeContext(input) {
        // Get all context data
        var context_data = {};
        $('#context_params').find('input').each(function() {
            context_data[$(this).attr('name')] = $(this).val();
        });

        for (var context_item in context_data) {
            if (context_data.hasOwnProperty(context_item)) {
                input[context_item] = context_data[context_item];
            }
        }

        // Get marketplace if exist (on some modules)
        var $selected_mkp = $('#selected-mkp');
        if ($selected_mkp.length && $selected_mkp.val()) {
            input['selected-mkp'] = $selected_mkp.val();
        }

        return input;
    }

    /*********************************************************************
     * Information tab
     *********************************************************************/
    // Delete marketplace config file, refresh configuration.
    $('#refresh-config-file').click(function () {
        var _this = $(this),
            toggle = function() {
                _this.find('*').toggle();
            };

        $.ajax({
            type: 'POST',
            url: $('#mirakl_tools_url').val(),
            dataType: 'json',
            data: {action: 'refresh-config-file', id_shop: idShop, id_shop_group: idShopGroup},
            beforeSend: toggle,
            complete: toggle,
            success: function () {
                typeof showSuccessMessage === 'function' && showSuccessMessage($('#mirakl-message-conf-file-ok').val());
            },
            error: function () {
                typeof showErrorMessage === 'function' && showErrorMessage($('#mirakl-message-conf-file-nok').val());
            },
        });
    });
});

/*********************************************************************
 * Profiles tab
 *********************************************************************/
(function($) {
    $(document).ready(function() {
        // Prevent submit on specific field
        $('#old_profiles_encoded').keypress(function(e) {
            if (13 === e.keyCode) {
                e.preventDefault();
                return false;
            }
        });

        // Import old profiles from encoded config
        $('#old_profiles_restore').click(function() {
            var $input = $('#old_profiles_encoded');
            if (!$input.val()) {
                // Handle empty
                showErrorMessage('Empty input');
                return false;
            }

            $.ajax({
                type: 'POST',
                url: $('#mirakl_tools_url').val(),
                dataType: 'jsonp',
                data: mergeContextParams({
                    'action': 'import-old-profiles',
                    'old_profiles': $input.val(),
                    'selected-mkp': $('#selected-mkp').val(),
                }),
                beforeSend: function() {
                    $input.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        showSuccessMessage(response.msg);
                        // Reload for new profiles.
                        window.location = window.location.href;
                    } else {
                        showErrorMessage(response.msg);
                    }
                },
                error: function() {
                    showErrorMessage($('#mirakl-message-conf-file-nok').val());
                },
                complete: function() {
                    $input.prop('disabled', false);
                }
            });
        });
    });

    function mergeContextParams(input) {
        var context_data = {};
        $('#context_params').find('input').each(function() {
            context_data[$(this).attr('name')] = $(this).val();
        });

        for (var context_item in context_data) {
            if (context_data.hasOwnProperty(context_item)) {
                input[context_item] = context_data[context_item];
            }
        }

        return input;
    }
})(jQuery);

/*********************************************************************
 * Transport tab
 *********************************************************************/
(function ($) {
    $(document).ready(function () {
        var $transportTab = $('#conf-transport');

        $transportTab.on('click', '.incoming_carrier_mapping_remove', function () {
            $(this).parents('.incoming_carrier_mapping_entry').first().remove();
        });
        $('.incoming_carrier_mapping_add', $transportTab).click(function () {
            var $mappingWrapper = $(this).parents('.incoming_carrier_mapping_wrapper').first(),
                $mappingPlaceholder = $mappingWrapper.find('.incoming_carrier_mapping_placeholder').first(),
                $newMapping = $mappingPlaceholder.clone(),
                newMappingIndex = new Date().getTime().toString();
            $newMapping.find('select').each(function(index, select) {
                $(select).attr('name', $(select).data('nameFormat').replace(':index:', newMappingIndex))
            });
            $newMapping.addClass('incoming_carrier_mapping_entry')
                .removeClass('incoming_carrier_mapping_placeholder').removeClass('hide');
            $mappingWrapper.append($newMapping);
        });
    });
})(jQuery);
