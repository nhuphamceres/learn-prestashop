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

function tabActive(tab) {
    $(tab).find('input, select, textarea').each(function () {
        if ($(this).attr('type') == 'checkbox') return (true);
        if ($(this).attr('name') == 'submit') return (true);
        $(this).attr('readonly', false).attr('disabled', false).removeClass('disabled');
    });
}

function tabInactive(tab) {
    $(tab).find('input, select, textarea').each(function () {
        if ($(this).attr('type') == 'checkbox') return (true);
        if ($(this).attr('name') == 'submit') return (true);
        $(this).attr('readonly', 'readonly').attr('disabled', 'disabled').addClass('disabled');
    });
}

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

function FnacEngine() {
    var currentTab = $('input[name=selected_tab]').val();
    //reset default tab selected
    $('#menuTab > li.active').removeClass('active');
    $('#tabList > div.active').removeClass('active').hide();
    //Show active tab
    $('#menuTab > li[id^="menu-' + currentTab + '"]').addClass('active');
    $('div[id^="menudiv-' + currentTab + '"]').show();


    // Condition/State Mapping
    //
    $('select[id^=condition_map-]').change(function () {
        value = $(this).val();

        $('select[id^=condition_map-] option[value="' + value + '"]:selected').parent().val(0);
        $('#' + $(this).attr('id') + ' option[value="' + value + '"]').attr('selected', true);
    });

    //ADDED FOR TABS DISPLAY 09/Sept/2013
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
        result = $(this).attr('id').match('^(.*)-(.*)$');
        lang = result[2];
        currentTab = $('#menudiv-' + lang);

        if (!parseInt(currentTab.find('input[name^="actives"]:checked').val()))
            tabInactive(currentTab);
        else
            tabActive(currentTab);

    });


    $('li[id^="menu-"]').click(function (e) {
        e.preventDefault();
        result = $(this).attr('id').match('^(.*)-(.*)$');
        lang = result[2];

        $('input[name=selected_tab]').val(lang);

        if (!$(this).hasClass('active')) {
            $('li[id^="menu-"]').removeClass('active');
            $(this).addClass('active');
            $('div[id^="menudiv-"]').hide();
            //$('div[id^="menudiv-"]').fadeOut('fast')
            $('div[id^="menudiv-' + lang + '"]').show();
            //$('div[id^="menudiv-' + lang + '"]').delay(195).fadeIn('slow');
        }

    });

    $('.fnac-country-tab').click(function (e) {
        e.preventDefault();

        $('.fnac-country-tab').removeClass('active');
        var currentid = $(this).attr('id');
        $(this).addClass('active');
        $('.fnac-country-container').hide();

        $("div.fnac-country-container[rel=" + currentid + "]").show();


    });


    $('input[name=checkme]').change(function () {
        var state = Boolean($(this).attr('checked'));
        $('input[id^=categoryBox]').attr('checked', state);
    });

    //Show active tab
    $('div[id^="menudiv-' + $('input[name=selected_tab]').val() + '"]').show();


    $('#support-informations-prestashop, #support-informations-php').click(function () {
        $('.support-informations-loader').show();

        console.log($(this).attr('rel') + '&callback=?');
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
            url: $('#fnac_tools_url').val(),
            dataType: 'jsonp',
            data: {

                'action': 'install-cron-jobs',
                'prestashop-cronjobs-params': $('#prestashop-cronjobs-params').text()
            },
            success: function (data) {
                window.console && console.log(data);

                $('#cronjob-loader').hide();

                if (data.error == true) {
                    $('#cronjobs_success').hide();
                    $('#cronjobs_error').show().find('div').html(data.output);
                } else {
                    $('#cronjobs_success').show().find('div').html(data.output);
                    $('#cronjobs_error').hide();
                }

            },
            error: function (data) {
                if (window.console)
                    console.log(data);

                $('#cronjob-loader').hide();
                $('#cronjobs_success').hide();
                $('#cronjobs_error').show().html($('#tools_ajax_error').val());
            }
        });
        return (false);
    });

    // Copy shipping categories template in each selector
    var logistic_type_id_template = $('.logistic_type_id_template').html().trim();
    $('.logistic_type_id').each(function () {
        var selected_category = $(this).attr('rel');

        $(this).append(logistic_type_id_template);
        selected_category.length && $(this).find('option[value="' + selected_category + '"]').attr('selected', true);
    });

    $(".control-label span").each(function() {
        if($(this).html().indexOf("*") > -1) {
          $(this).html($(this).html().replace("*","<span style=\"color:red;\">*</span>"));
        }
     });

}