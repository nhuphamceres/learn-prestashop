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

var pmProfilesInitialized1 = false;
$(document).ready(function () {
    if (pmProfilesInitialized1) return;
    pmProfilesInitialized1 = true;

    if (typeof($.fn.sortable) == 'function')
        $("#pm-profile-container").sortable();


    $('#pm-profile-container')
        .delegate('.pm-go-models', 'click', function () {
            $('#menu-models').click();
        })
        .delegate('.pm-go-repricing', 'click', function () {
            $('#menu-repricing').click();
        });

    $('#profile-add').click(function () {

        var product_type = $('#pm-profile-container').find('.profile-product-type:visible');

        if (product_type.length && !product_type.val().length) {
            alert($('#profile-error-product-type').val());
            return (false);
        }

        var profile_name = $('#pm-profile-container').find('.profile-profile-name:visible');

        if ($(profile_name).length && !$(profile_name).val().length) {
            alert($('#profile-error-profile-name').val());
            return (false);
        }

        var cloned = $('#pm-master-profile .pm-profile').clone().prependTo('.pm-profile-group');

        $(cloned).find('.pm-profile-header').hide();
        $(cloned).find('.pm-profile-body').slideDown();

        return (false);
    });

    $('#pm-profile-container').delegate('.pm-profile-delete', 'click', function () {
        $(this).parents().get(2).remove();
        return (false);
    });

    $('#pm-profile-container').delegate('.pm-profile-action-delete', 'click', function () {
        $(this).parents().get(4).remove();
        return (false);
    });

    $('#pm-profile-container').delegate('.pm-profile-minimize', 'click', function () {
        var profile = $(this).parents().get(2);

        if (!$(profile).find('.profile-profile-name').val().length) {
            alert($('#profile-error-profile-name').val());
            return (false);
        }

        $(profile).find('.pm-profile-header-profile-name').html($(profile).find('.profile-profile-name').val());
        $(profile).find('.pm-profile-header-model-name').html($(profile).find('.profile-model-name option:selected').text());

        if ($(profile).find('.profile-model-name').val() && $(profile).find('.profile-model-name').val().length)
            $(profile).find('.pm-profile-header-separator').show();
        else
            $(profile).find('.pm-profile-header-separator').hide();

        $(profile).find('.pm-profile-body').slideUp();
        $(profile).find('.pm-profile-header').slideDown();
        return (false);
    });

    $('#pm-profile-container').delegate('.pm-profile-action-edit', 'click', function () {
        var profile = $(this).parents().get(4);
        $(profile).find('.pm-profile-header').slideUp();
        $(profile).find('.pm-profile-body').slideDown();
        return (false);
    });


    /* Price Rules */
    $('#pm-profile-container').delegate('.price-rule-add', 'click', function () {
        var source_i = '';
        source_i = $(this).parent();

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

    });

    $('#pm-profile-container').delegate('.price-rule-type', 'change', function () {
        var type = $(this).val();
        if (type !== 'percent' && type !== 'value')
            return (false);
        $(this).parent().find('select[rel="percent"], select[rel="value"]').hide();
        $(this).parent().find('select[rel="' + type + '"]').show();
    });

    $('#pm-profile-container').delegate('.price-rule-remove', 'click', function () {
        $(this).parent().remove();
    });


});