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

var pageInitialized1 = false;
$(document).ready(function () {
    var contextKey = $('#context_key').val();
    if (pageInitialized1) return;
    pageInitialized1 = true;

    var chosen_params = {'width': '200px', 'search_contains': true},
        chosen_params_large = {'width': '400px', 'search_contains': true},
        profileTab = $('#conf-profiles');

    profileTab.find('div[id^="profile-"].stored-profile .chosen-select').chosen(chosen_params);
    profileTab.find('div[id^="profile-"].stored-profile .chosen-select-large').chosen(chosen_params_large);


    if ('function' !== typeof($.fn.prop)) {
        jQuery.fn.extend({
            prop: function() {
                return this;
            }
        });
    }

    $('.hint').show();

    // AJAX Checker
    //
    $(function () {
        pAjax = new Object();
        pAjax.url = $('#env_check_url').val();
        pAjax.type = 'GET';
        pAjax.data_type = 'jsonp';
        pAjax.data = null;

        if (window.console)
            console.log(pAjax);

        var to_display = '.cd-env-infos-' + $('#env_check_url').attr('rel');

        $.ajax({
            type: pAjax.type,
            url: pAjax.url,
            dataType: pAjax.data_type,
            success: function (data) {
                if (window.console)
                    console.log(data);

                if (!data.pass) {
                    $('#cd-env-infos').show();
                    $(to_display).parent().show();
                }
            },
            error: function (data) {
                if (window.console)
                    console.log(data);
                $('#cd-env-infos').show();
                $(to_display).parent().show();
            }
        });
    });



    // max_input_vars checker
    //
    $(function ()
    {
        var max_input_vars = parseInt($('#max_input_vars').val());
        var cur_input_vars = $('input, select, textarea, button').length;

        if (max_input_vars && max_input_vars < cur_input_vars)
        {
            $('#cd-env-infos').show();
            $('.cd-env-infos-miv').parent().show();
        }
    });

    // Oct-05-2018: Move to separate file

    /*
     * AUTOMATIC IMPORT FUNCTIONS
     */

    // 2020-07-13: No longer use AllModelList file

    function reloadUniverses(force) {
        $('#universes-renew').fadeIn();

        $.ajax({
            type: 'POST',
            url: $('#load_categories').val(),
            dataType: 'json',
            data: 'action=universes&force=' + force + '&seed=' + new Date().valueOf(),
            success: function (data) {
                console.log('Loaded universe', data);
                $('#universes-info').html(data.output)
                    .removeClass('conf cd-info-level-info error warn success cdiscount-warn alert alert-danger alert-info');

                // Update universe list
                $('#master_model .model-universe').each(function(index, select) {
                    $(select).find('option:gt(0)').remove();
                    $.each(data.universe, function(index, universe) {
                        $(select).append($('<option></option>').attr('value', universe.value).text(universe.desc));
                    });
                })
            },
            error: function (data) {
                console.log(data);
                $('#universes-info').html(data)
                    .removeClass('conf cd-info-level-info error warn success cdiscount-warn alert-info');
            },
            complete: function() {
                $('#universes-renew').fadeOut().hide();
            }
        });
    }

    function reloadCategories(force) {
        $('#categories-renew').fadeIn();

        $.ajax({
            type: 'POST',
            url: $('#load_categories').val(),
            data: 'action=categories&force=' + force + '&seed=' + new Date().valueOf(),
            beforeProcess: function (data) {

            },
            success: function (data) {
                $('#categories-renew').fadeOut().hide();
                $('#categories-info').removeClass('conf cd-info-level-info error warn success cdiscount-warn alert alert-danger alert-info');
                $('#categories-info').html(data);
            },
            error: function (data) {
                if (window.console)
                    console.log(data);
                $('#categories-renew').fadeOut().hide();
                $('#categories-info').removeClass('conf cd-info-level-info error warn success cdiscount-warn alert-info');
                $('#categories-info').html(data);
            }
        });
    }

    $('#reset-xml').click(function () {
        $('#reset-xml').attr('disabled', true);
        reloadCategories(1);
        reloadUniverses(1);
    });

    // Onload
    $(function () {
        if (parseInt($('#categories-renew-status').val())) {
            reloadCategories(0);
        }
        if (parseInt($('#universes-renew-status').val())) {
            reloadUniverses(0);
        }
    });
    // Tab active or not active
    //
    $('div[id^=menudiv-]').each(function () {

        if ($(this).find('input[name^="actives"]').length !== 0 && !parseInt($(this).find('input[name^="actives"]:checked').val())) {
            tabInactive($(this));
        }
        else if ($(this).find('input[name^="actives"]').length !== 0) {
            tabActive($(this));
        }
    });

    $('input[id^="active-"]').click(function () {
        result = $(this).attr('id').match('^(.*)-(.*)$');
        lang = result[2];
        currentTab = $('#menudiv-' + lang);

        if (!parseInt(currentTab.find('input[name^="actives"]:checked').val()))
            tabInactive(currentTab);
        else
            tabActive(currentTab);

    });
    function tabActive(tab) {
        $(tab).find('input, select, textarea').each(function () {
            if ($(this).attr('type') === 'checkbox')
                return (true);
            if ($(this).attr('name') === 'submit')
                return (true);
            $(this).attr('readonly', false).attr('disabled', false).removeClass('disabled');
        });
    }

    function tabInactive(tab) {
        $(tab).find('input, select, textarea').each(function () {
            if ($(this).attr('type') === 'checkbox')
                return (true);
            if ($(this).attr('name') === 'submit')
                return (true);
            $(this).attr('readonly', 'readonly').attr('disabled', 'disabled').addClass('disabled');
        });
    }

    $('input[name=wcheckme]').click(function () {

        $('input[id^=warnings]').each(function () {
            if ($(this).attr('checked'))
                $(this).attr('checked', false);
            else
                $(this).attr('checked', 'checked');
        });

    });


    $('#connection-check').click(function () {

        var username = $('#username').val();
        var password = $('#password').val();

        $('#connection-check *').toggle();
        $('#marketplace-response-success, #marketplace-response-danger').html('').hide();


        $.ajax({
            type: 'GET',
            url: $('#check_url').val() + '&callback=?',
            dataType: 'json',
            data: {
                'id_lang': $('#id_lang').val(),
                'action': 'check',
                'preprod': ($('input[name=preproduction]').attr('checked') ? '1' : '0'),
                'debug': ($('input[name=debug]').attr('checked') ? '1' : '0'),
                'username': encodeURIComponent(username),
                'password': encodeURIComponent(password)
            },
            success: function (data) {
                $('#connection-check *').toggle();

                if (window.console)
                    console.log(data);

                if (data.alert) {
                    alert(data.alert);
                    return (false);
                }

                if (data.message && !data.error) {
                    $('#marketplace-response-success').html(data.message).show();
                }
                else if (data.message && data.error) {
                    $('#marketplace-response-danger').html(data.message).show();
                }
                if ($('input[name=debug]').attr('checked')) {
                    $('#marketplace-response-danger').after('<hr /><pre>' + data.message + '</pre>');
                }
            },
            error: function (data) {
                if(window.console)
                    console.log(data);
                $('#connection-check *').toggle();
                $('#marketplace-response-danger').html('Connection Error').show();
                $('#marketplace-response-danger').after('<hr /><pre>' + data.responseText + '</pre>');
            }
        });

    });


    $('.addnewmapping').click(function () {
        var result = $(this).attr('id').match('.*-.*-(.*)-(.*)$');
        var id_group = result[1];
        var cloned = $('div[id^="attribute-group-' + id_group + '-"]').not('label').last().clone().appendTo('#new-mapping-' + id_group);
        var indexX = $(cloned).attr('rel');
        var newIndex = parseInt(indexX) + 1;

        $(this).parent().attr('rel', newIndex);
        $(cloned).attr('rel', newIndex);

        $(cloned).attr('id', 'attribute-group-' + id_group + '-' + newIndex);
        $(cloned).attr('name', 'fashion[group][' + result[1] + ']' + '[' + newIndex + ']');
        $(cloned).find('select, input').val('').attr('disabled', false);
        $(cloned).find('select:first').attr('rel', id_group + '-' + newIndex).val($(this).parent().find('select:first').val());
        $(cloned).find('input:first').val($(this).parent().find('input:first').val());
        $(cloned).find('.addnewmapping').remove();
        $(cloned).find('.removemapping').show().click(function () {
            $(this).parent().remove();
        });
        $(cloned).find('select').attr('name', 'fashion[prestashop][' + result[1] + '][' + newIndex + ']');
        $(cloned).find('input').attr('name', 'fashion[cdiscount][' + result[1] + '][' + newIndex + ']').removeAttr('value');

        // Remove used options
        var selected_select = $(cloned).find('select');
        $('select[name^="fashion[prestashop]"] option:selected').each(function (index, option) {
            selected_select.find('option[value="' + option.value + '"]').remove();
        });

        cloned.find('select[name^="fashion[prestashop]"]').change(function () {
            var result;

            result = $(this).attr('rel').match('(.*)-(.*)$');
            $(this).attr('name', 'fashion[prestashop][' + result[1] + '][' + $(this).val() + ']');
            $(this).parent().find('input').attr('name', 'fashion[cdiscount][' + result[1] + '][' + $(this).val() + ']');
        });

        if (!$(cloned).find('option').length) {
            $(cloned).remove();
            return (false);
        }
    });

    $('select[name^="fashion[prestashop]"]').change(function () {
        var result;

        result = $(this).attr('rel').match('(.*)-(.*)$');
        $(this).attr('name', 'fashion[prestashop][' + result[1] + '][' + $(this).val() + ']');
        $(this).parent().find('input').attr('name', 'fashion[cdiscount][' + result[1] + '][' + $(this).val() + ']');
    });

    $('.removemapping').click(function () {
        $(this).parent().remove();
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


    $('#individual_1, #individual_0').click(function () {
        if(!$(this).hasClass('expert-mode'))
            return;
        if ($(this).val() === '1')
            $('#set-domain').show();
        else
            $('#set-domain').hide();
    });
    $('input[name="validateForm"]').click(function () {
        if ($('select[name="orderstate[CDISCOUNT_CA]"] :selected').index() === 0) {
            alert($('select[name="orderstate[CDISCOUNT_CA]"] option:eq(0)').val() + ' !');
            return (false);
        }
        if ($('select[name="orderstate[CDISCOUNT_CE]"] :selected').index() === 0) {
            alert($('select[name="orderstate[CDISCOUNT_CE]"] option:eq(0)').val() + ' !');
            return (false);
        }
        if ($('select[name="orderstate[CDISCOUNT_CL]"] :selected').index() === 0) {
            alert($('select[name="orderstate[CDISCOUNT_CL]"] option:eq(0)').val() + ' !');
            return (false);
        }
    });

    /*
     *  PROFILES MANAGEMENT
     */

    $('#profile-add').click(function () {
        var cloned = $('#master-profile').clone().prependTo('#profile-container').slideDown('slow');
        cloned.removeAttr('id');
		cloned.removeClass('master');
        cloned.find('.profile-del-2').click(function () {
            $(this).parent().slideUp('slow', function () {
                $(this).remove();
            });
        });
        // Move universe to models
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

        cloned.find('.chosen-select').chosen(chosen_params);
        cloned.find('.chosen-select-large').chosen(chosen_params_large);

        cloned.find('.price-rule-remove').click(function () {
            $(this).parent().remove();
        });
    });

    /*
     * General Price Rules
     */
    $('.price-rule-add').click(function () {
        var source_i = '';
        source_i = $(this).parent();

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
        cloned = source_i.clone().appendTo(dest_i);
        cloned.find('input').val('');
        cloned.find('input[rel=from]').val(parseInt(to_val + 1));

        cloned.find('.price-rule-add, .price-rule-remove').toggle();
        cloned.find('.price-rule-remove').
            click(function () {
                $(this).parent().remove();
            });
    });
    $('.price-rule-remove').click(function () {
        $(this).parent().remove();
    });
    $('.price-rule-type').change(function () {
        var type = $(this).val();
        if (type !== 'percent' && type !== 'value')
            return (false);
        $(this).parent().find('select[rel="percent"], select[rel="value"]').hide();
        $(this).parent().find('select[rel="' + type + '"]').show();
    });

    // Move universe to models

    // Not use anymore

    /*************** ADD **************/
    $('.profile-edit-img').click(function () {
        var profile_id = $(this).attr('rel');
        $('#profile-' + profile_id).slideToggle('slow');
    });
    /**********************************/

    $('.profile-del-img').click(function () {
        var profile_id = $(this).attr('rel');
        $('#profile-' + profile_id).slideUp('slow', function () {
            $(this).remove();
        });
        $('#profile-header-' + profile_id).slideUp('slow', function () {
            $(this).remove();
        });
    });

    $('#reset-categories').click(function () {
        if (confirm($('#reset-categories-alert').val()))
            return (true);
        return (false);
    });


    // 2020-07-13: No longer use AllModelList file

    /*********************************************** Models management ************************************************/
    var $modelContainer = $('#model-items'),
        $modelLoader = $('#loader_models'),
        $masterModel = $('#master_model'),
        modelEndpoint = $('#load_models').val(),
        $idLang = $('#id_lang').val();

    $('#model-add').click(function () {
        var modelId = 'model_' + new Date().getTime().toString(),
            $master = $masterModel.clone().attr('id', modelId);
        $master.find('input, select').each(function(index, item) {
            $(item).attr('name', 'models[' + modelId + '][' + $(item).data('name') + ']');
        });
        $master.prependTo($modelContainer).slideDown('slow');
        loadModelBodyDoneAddEvents($master);
    });

    $('.model-edit-img').click(function () {
        var $editBtn = $(this),
            modelInternalId = $editBtn.attr('rel'),
            $modelBody = $('#' + modelInternalId);
        if ($modelBody.length) {
            $modelBody.slideToggle('slow');
        } else {
            var $modelHeader = $editBtn.parents('.model-header').first();
            if (!$modelHeader.hasClass('loading')) {
                loadSavedModel(modelInternalId, $modelHeader);       
            }
        }
    });

    $('.model-refresh-img').click(function () {
        var $modelHeader = $(this).parents('.model-header').first();
        if (!$modelHeader.hasClass('loading')) {
            var modelInternalId = $(this).attr('rel'),
                $modelBody = $('#' + modelInternalId);
            $modelBody.remove();
            loadSavedModel(modelInternalId, $modelHeader);
        }
    });

    $('.model-del-img').click(function () {
        var profile_id = $(this).attr('rel');
        $('#model-' + profile_id).slideUp('slow', function () {
            $(this).remove();
        });
        $('#model-header-' + profile_id).slideUp('slow', function () {
            $(this).remove();
        });
    });

    function loadSavedModel(modelInternalId, $modelHeader) {
        var $loader = $modelLoader.clone();

        $.ajax({
            url: modelEndpoint,
            type: 'GET',
            dataType: 'jsonp',
            data: {action: 'load_saved_model', modelInternalId: modelInternalId, id_lang: $idLang, context_key: contextKey},
            beforeSend: function () {
                $modelHeader.addClass('loading').after($loader);
                $loader.show();
            },
            complete: function () {
                $modelHeader.removeClass('loading');
                $loader.remove();
            },
            success: function(data) {
                if (data.error || !data.tpl) {
                    alert('Cannot load model data');
                } else {
                    var $body = $(data.tpl).insertAfter($modelHeader).hide().slideDown('slow');
                    loadModelBodyDoneAddEvents($body);
                    $('#model-header-' + modelInternalId).find('.model_state').val('as-is');    // Change state
                }
            },
            error: function(error) {
                alert('Cannot load model data');
                console.log('Failed to load saved model', error);
            },
        });
    }

    function loadModelBodyDoneAddEvents($modelBody) {
        $modelBody.find('.model-del-2').click(function () {
            $(this).parent().slideUp('slow', function () {
                $(this).remove();
            });
        });
        $modelBody.find('.model-name').prop('disabled', false).change(modelNameChange);
        $modelBody.find('.model-universe').change(loadCDiscountCategoriesOfUniverse);
        $modelBody.find('.model-category').change(loadCDiscountModelOfCategory);
        $modelBody.find('.model-model').change(loadModelData);
        $modelBody.find('.chosen-select').chosen(chosen_params);
        $modelBody.find('.chosen-select-large').chosen(chosen_params_large);
    }

    function modelNameChange() {
        var hasName = !!$(this).val(),
            $container = $(this).parent().parent();
        $container.find('.model-universe').prop('disabled', !hasName);
        $container.find('.model-category').prop('disabled', !hasName).trigger('chosen:updated');
        $container.find('.model-model').prop('disabled', !hasName);
        $container.find('.model-public').prop('disabled', !hasName);
        $container.find('.model-gender').prop('disabled', !hasName);
    }

    function loadCDiscountCategoriesOfUniverse() {
        var $target = $(this),
            $parent = $target.parent(),
            modelInternalId = $parent.parent().attr('id'),
            $loader = $parent.find('.model-universe-loader'),
            $error = $parent.find('.model-universe-error');

        $.ajax({
            type: 'GET',
            dataType: 'jsonp',
            url: modelEndpoint,
            data: { action: 'u2c', universe: $target.val(), modelInternalId: modelInternalId, id_lang: $idLang },
            beforeSend: function () {
                $loader.show();
            },
            complete: function() {
                $loader.hide();
            },
            success: function (ret) {
                $parent.nextAll().remove();
                if (ret.error) {
                    $error.html(ret.output).show();
                } else {
                    $error.hide();
                    $parent.after(ret.tpl).parent().find('select.model-category')
                        .chosen(chosen_params_large).change(loadCDiscountModelOfCategory);
                }
            },
            error: function (data) {
                console.log('Error while load categories!', data);
                $error.html(data.responseText).show();
            },
        });
    }

    function loadCDiscountModelOfCategory() {
        var $category = $(this),
            $parent = $category.parent(),
            modelInternalId = $parent.parent().attr('id'),
            $loader = $parent.find('.model-category-loader');

        // Dynamic set name of chosen category
        $parent.find('input.model-category-name').val($.trim($category.find('option:selected').text()));

        // Get list of models
        $.ajax({
            type: 'POST',
            dataType: 'jsonp',
            url: modelEndpoint,
            data: {
                action: 'models_by_category',
                category_id: $category.val(),
                modelInternalId: modelInternalId,
                id_lang: $idLang
            },
            beforeSend: function() {
                $loader.show();
            },
            complete: function() {
                $loader.hide();
            },
            success: function(response) {
                $parent.nextAll().remove();
                if (!response.error) {
                    $parent.after(response.tpl).parent().find('select.model-model').change(loadModelData);
                }
            },
            error: function(jqXHR) {
                console.log('Error while load model!', jqXHR);
                $parent.find('.model-category-error').html(jqXHR.responseText).show();
            },
        });
    }

    function loadModelData() {
        var $model = $(this),
            $parent = $model.parent(),
            $modelDiv = $parent.parent(),
            modelInternalId = $modelDiv.attr('id'),
            idModel = $model.val(),
            $categorySelection = $modelDiv.find('select.model-category'),
            $modelExternalName = $modelDiv.find('.model-external-name'),
            profileName = $modelDiv.find('.model-name').val();

        // Update model external name
        $modelExternalName.val($.trim($model.find('option:selected').text()));

        // Validate name
        if (!profileName.length) {
            alert($('.text_must_fill_name:first').val());
            return false;
        }

        // Load both "public" and "gender" properties
        $.ajax({
            type: 'GET',
            url: modelEndpoint,
            dataType: 'jsonp',
            data: {
                action: 'model_public_and_gender_and_variant',
                category_id: $categorySelection.val(),
                model_id: idModel,
                modelInternalId: modelInternalId,
                id_lang: $idLang,
            },
            success: function(response) {
                $parent.nextAll().remove();
                if (!response.error && response.tpl) {
                    $parent.after(response.tpl);

                    // Continue load specific data
                    $.ajax({
                        type: 'GET',
                        url: $('.get_specific_data:first').val(),
                        dataType: 'json',
                        data: {
                            modelInternalId: modelInternalId,
                            id_model: idModel,
                            id_category: $categorySelection.val(),
                            id_lang: $idLang,
                            seed: new Date().valueOf(),
                        },
                        success: function (data) {
                            if (!data.error && data.tpl) {
                                $modelDiv.append(data.tpl);
                            }
                        },
                        error: function (data) {
                            alert('Failed to load specific data');
                            console.log('Failed to load specific data', data);
                        }
                    });
                }
            },
            error: function(jqXHR) {
                alert('Error while load model public & gender & variant');
                console.log('Error while load model public & gender & variant', jqXHR);
            },
        });
    }
    /************************************************ Models management end *******************************************/

    $('#advanced_shipping_on').click(function () {
        $('#advanced-shipping-mgt').slideToggle();
    });

    $('#clogistique_0, #clogistique_1').click(function () {
        if ($(this).attr('checked') && $(this).val() == '1')
            $('#clogistique_destock_section').show();
        else
            $('#clogistique_destock_section').hide();
    });

    // http://stackoverflow.com/questions/6565480/javascript-jquery-regex-replace-input-field-with-valid-characters
    $('.typerightname').keyup(function () {
        var input = $(this), text = input.val().replace(/[^a-zA-Z0-9-_\s]/g, "");

        if (/_|\s/.test(text)) {
            text = text.replace(/_|\s/g, "");
            // logic to notify user of replacement
        }
        input.val(text);
    });

    $('.adv-ship-add').click(function () {

        var item_name = $('input[name^="advanced_shipping[name]"]:first').val();

        if (!item_name.length)
            return (false);

        var new_name = 'adv-ship-' + item_name;

        // Clone Item
        var cloned = $('#adv-ship-model').clone().prependTo('#adv-ship-container').slideDown('slow');
        cloned.attr('id', new_name);
        cloned.find('input').removeClass('advship-readonly');

        // Clear origin fields
        $('#adv-ship-model input').val('');

        $(cloned).find('input').each(function () {
            // Change display
            if ($(this).attr('rel') == 'ro')
                $(this).attr('readonly', 'readonly').addClass('advship-readonly');

            // change item ID and Name
            new_identifier = $(this).attr('id') + '[' + item_name + ']';
            $(this).attr('id', new_identifier);
            $(this).attr('name', new_identifier);
        });

        // Display remove button
        $(cloned).find('.adv-ship-add').hide();
        $(cloned).find('.adv-ship-del').show();

        // Handle delete button
        $(cloned).find('.adv-ship-del').click(function () {
            $(this).parent().parent().parent().parent().slideUp('slow').remove();
        });
    });
    $('.adv-ship-del').click(function () {
        $(this).parent().parent().parent().parent().slideUp('slow').remove();
    });

    // Features Fields MAPPING
    //
    $('.addnew-feature-mapping').click(function () {
        var result = $(this).attr('id').match('.*-(.*)-(.*)$');
        var id_group = result[1];
        var cloned = $('div[id^="feature-group-' + id_group + '-"]').not('label').last().clone().appendTo('#new-feature-mapping-' + id_group);
        var indexX = $(cloned).attr('rel');
        var newIndex = parseInt(indexX) + 1;
        $(this).parent().attr('rel', newIndex);
        $(cloned).attr('rel', newIndex);

        $(cloned).attr('id', 'feature-group-' + id_group + '-' + newIndex);
        $(cloned).attr('name', 'fashion[group][' + result[1] + ']' + '[' + newIndex + ']');
        $(cloned).find('select, input').val('').attr('disabled', false);
        $(cloned).find('select:first').attr('rel', id_group + '-' + newIndex).val($(this).parent().find('select:first').val());
        $(cloned).find('input:first').val($(this).parent().find('input:first').val());
        $(cloned).find('.addnew-feature-mapping').remove();
        $(cloned).find('.remove-feature-mapping').show().click(function () {
            $(this).parent().remove();
        });
        $(cloned).find('select').attr('name', 'features_mapping[ps][' + result[1] + '][' + newIndex + ']');
        $(cloned).find('input').attr('name', 'features_mapping[cd][' + result[1] + '][' + newIndex + ']').removeAttr('value');

        // Remove used options
        var selected_select = $(cloned).find('select');
        $('select[name^="features_mapping[ps]"] option:selected').each(function (index, option) {
            selected_select.find('option[value="' + option.value + '"]').remove();
        });

        if (!$(cloned).find('option').length) {
            $(cloned).remove();
            return (false);
        }
    });

    $('select[name^="features_mapping[prestashop]"]').change(function () {
        var result;

        result = $(this).attr('rel').match('-(.*)-(.*)$');
        $(this).attr('name', 'features_mapping[ps][' + result[1] + '][' + $(this).val() + ']');
        $(this).parent().find('input').attr('name', 'features_mapping[cd][' + result[1] + '][' + $(this).val() + ']');
    });

    $('.remove-feature-mapping').click(function () {
        $(this).parent().remove();
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

    $('input[name=specials]').click(function () {
        if (parseInt($(this).val()) === 1) {
            $('#on_sale_period').slideDown();
            $('#formula_on_specials').slideDown();
        }
        else {
            $('#on_sale_period').slideUp();
            $('#formula_on_specials').slideUp();
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
                    var is_checked = !!e.target.checked;
                    $spec.slice(
                        Math.min($spec.index(lastCheckbox), $spec.index(e.target)),
                        Math.max($spec.index(lastCheckbox), $spec.index(e.target)) + 1
                    ).attr('checked', is_checked).prop('checked', is_checked);
                }
                lastCheckbox = e.target;
            });
        };
    })(jQuery);

    $('.category').enableCheckboxRangeSelection();

    $('input[name=checkme]').click(function () {

        $('input[id^=category]').each(function () {
            if ($(this).attr('checked'))
                $(this).attr('checked', false);
            else
                $(this).attr('checked', 'checked');
        });

    });

    $('#cdiscount_form').submit(function () {

        $('input[rel^=category]:checked').attr('name', 'category[]');

        $('select[rel^=profile2category] option:selected').parent().each(function () {
            $(this).attr('name', $(this).attr('rel'));
        });

        $('#available-suppliers option').attr('selected', true);
        $('#selected-suppliers option').attr('selected', true);
        $('#available-manufacturers option').attr('selected', true);
        $('#excluded-manufacturers option').attr('selected', true);

        /* Profile optimization */
        $('.profile-create').not('.master').each(function (ind) {
            var elements = $(this).find('input, select, textarea');

            $(elements).each(function () {
                var name = $(this).attr('name');
                if (name != undefined && name.length)
                    $(this).attr('name', name.replace('_key_', ind));
            });
        });

        return (true);
    });

    // Use to insure the script has been loaded: prevent save configuration if script/JS is not completely loaded
    $('input[name=validateForm], button[name=validateForm]').show();


    /* Open model and profile like an accordion when click on the <tr> containing the name */
    $('.profile-table tbody tr td:nth-child(1)').click(function () {
        $(this).next().next().find('img').click();
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

    profileTab.delegate('input.is-price', 'blur', function (ev) {
        DisplayPrice($(this));
    });
    $('#conf-cron').delegate('input.is-price', 'blur', function (ev) {
        DisplayPrice($(this));
    });

    $('.price').blur(function () {
        DisplayPrice($(this));
    });
    $('.carrier-params').blur(function () {
        DisplayPrice($(this));
    });

    $('.cron-mode').click(function () {
        div_id=$(this).attr('rel');

        if( $('#' + div_id).is(':visible') )
        {
            $('#' + div_id + '.cron-toggle').slideUp('slow');
            return(false);
        }

        $('.cron-toggle').hide();
        $('#' + div_id + '.cron-toggle').slideDown('slow');
    });

    $('#install-cronjobs').click(function () {

        $('#cronjob-loader').show();

        $.ajax({
            type: 'POST',
            url: $('#cdiscount_tools_url').val(),
            dataType: 'jsonp',
            data: {
                'action': 'install-cron-jobs',
                'prestashop-cronjobs-params': $('#prestashop-cronjobs-params').text(),
                'context_key': contextKey
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
                else
                {
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

                if (data.status && data.status.length)
                    $('#cronjobs_error').append('<b>Status Code:'+data.status+'</b>');
                if (data.statusText && data.statusText.length)
                    $('#cronjobs_error').append('<b>Status Text:'+data.statusText+'</b>');
                if (data.responseText && data.responseText.length)
                    $('#cronjobs_error').append('<b>Response:</b>'+data.responseText);
            }
        });
        return (false);
    });


    $('.feature-mapping-item-action').click(function () {
        target = $(this).parent().parent();
        target.find('.feature-mapping-item-action > a').toggle();
        target.find('.feature-mapping-item').toggle();
    });

    $('#feature-mapping-collapse').click(function () {
        $('#feature-mapping-collapse a').toggle();
        $('#feature-mapping').toggle();
    });

    $('#attribute-mapping-collapse').click(function () {
        $('#attribute-mapping-collapse a').toggle();
        $('#attribute-mapping').toggle();
    });

    $('#size-field-mapping-collapse').click(function () {
        $('#size-field-mapping-collapse a').toggle();
        $('#size-attribute-mapping-section').toggle();
        $('#size-feature-mapping-section').toggle();
    });

    var lv_target_button = null;

    $(document).on('click', '.mapping-box-search', function(ev) {
        mapping_box_search_click($(this), ev);
    });

    function mapping_box_search_click(obj, ev)
    {
        var mapping_section = obj.parents().get(5);

        if (window.console)
            console.log(mapping_section);

        ev.preventDefault();

        lv_target_button = obj;

        $('#mapping-box .available-values option:gt(0)').remove();
        $('#mapping-box .selected-values option:gt(0)').remove();
        $('#mapping-box').css('top', window.pageYOffset).show();
        $('#mapping-box .mapping-box-values-loader').show();
        $('#mapping-box .values-search').val(null);

        $.ajax({
            type: 'POST',
            url: $('#cdiscount_tools_url').val(),
            dataType: 'html',
            data: {
                action: 'get-size-list',
                marketplace_model_id: obj.attr('rel'),
                category_id: obj.data('categoryId'),
            },
            success: function (data) {
                $('#mapping-box .mapping-box-values-loader').hide();
                $('#mapping-box .available-values').append(data);
            },
            error: function (data) {
                if (window.console)
                    console.log(data);

                $('#mapping-box .mapping-box-values-loader').hide();
            }
        });
        return (false);
    }
    $('.mapping-box .close-box').click(function () {
        $(this).parent().parent().fadeOut();
    });

    $('.mapping-box-values-move-right').click(function () {
        var box = $(this).parents().get(2);
        return !$(box).find('.available-values option:selected').remove().appendTo( $(box).find('.selected-values') );
    });
    $('.mapping-box-values-move-left').click(function () {
        var box = $(this).parents().get(2);
        return !$(box).find('.selected-values option:selected').remove().appendTo( $(box).find('.available-values') );
    });

    //http://kilianvalkhof.com/2010/javascript/how-to-build-a-fast-simple-list-filter-with-jquery/
    jQuery.expr[':'].Contains = function(a,i,m){
        return (a.textContent || a.innerText || "").toUpperCase().indexOf(m[3].toUpperCase())>=0;
    };
    jQuery.expr[':'].Eq = function(a,i,m){
        return (a.textContent || a.innerText || "").toUpperCase() == m[3].toUpperCase();
    };

    $('.mapping-box .values-search').change( function () {
        var box = $(this).parents().get(2);
        var filter = $(this).val(); // get the value of the input, which we filter on

        if(!filter.length)
        {
          $(box).find('.available-values option:gt(0)').show();
          return;
        }

        $(box).find('.available-values option:gt(0)').hide();
        $(box).find('.available-values option:Contains(' + filter + ')').show();
    });

    $('.mapping-box .values-search').keyup( function () {
        $('.mapping-box .values-search').change();
    });

    $('.mapping-box .mapping-box-valid').click( function (ev) {
        var box = $(this).parents().get(2);
        ev.preventDefault();

        if (!lv_target_button)
            return;

        var target_div = lv_target_button.closest('.mapping-box-scope');

        if (window.console)
            console.log(target_div);

        target_div.find('option[value=""]').remove();
        target_div.find('option:gt(0)').not('.stored-item').remove();
        target_div.find('select').append($('<option>'));
        target_div.find('select').append( $(box).find('.selected-values option:gt(0)').clone() );
        target_div.find('select').addClass('filled');

        target_div.find('input[rel="value"]').each(function () {
            var mapping_entry = $(this).parents().get(0);
            var mapping_left_value = $(mapping_entry).find('input[rel="value"]').val();
            var mapping_right_option = $(mapping_entry).find('select.select-mapping-entry option:Eq(' + mapping_left_value + ')');

            if (typeof(mapping_right_option) == 'object' && mapping_right_option.length)
            {
                $(this).parent().val(null);
                mapping_right_option.attr('selected', true);
            }
        });
        $(box).find('.mapping-box .close-box').click();
        return(false);
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
                    $spec.slice(
                        Math.min($spec.index(lastCheckbox), $spec.index(e.target)),
                        Math.max($spec.index(lastCheckbox), $spec.index(e.target)) + 1
                    ).attr({checked: e.target.checked ? "checked" : ""});
                }
                lastCheckbox = e.target;
            });
        };
    })(jQuery);

    // Mapping - Other mappings - Copy from PS to CD
    $('#conf-mapping').on('click', '#attribute-mapping span.can-copy-mapping', function() {
        var $parent = $(this).parent();
        $parent.find('input.can-receive-copy-mapping').val($parent.find('select.can-be-copied-mapping option:selected').text());
    })
    
    // Settings: Dependency
    $('#marketing_description_1, #marketing_description_0').change(function() {
        $(this).parents('#conf-settings').find('.available_if_mkt_desc_enable').toggleClass('hidden');
    });
    
    $('#detailed_debug_controller').click(function() {
        $(this).toggleClass('dropup').toggleClass('dropdown');
        $('#detailed_debug_content').slideToggle();
    });
});
